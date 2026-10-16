<?php

namespace Database\Seeders\Demo;

use App\Helpers\Common;
use App\Support\Demo\Demo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Demo ENV slice 3b: leave and island passes. Runs after DemoTimeSeeder (approved leave days are rewritten
 * in the attendance) and before DemoPayrollSeeder (which pays them).
 *
 * Rows are written in the shapes the app writes them:
 * - employees_leaves + employees_leaves_status = LeaveController@leaveAdd: one status row per approver of the
 *   rank chain (Line/Supervisor → reporting manager; Manager/HOD → reporting manager → GM; EXCOM → GM;
 *   GM → HR EXCOM), acted on in id order; total_days = Common::calculateLeaveWorkingDays. Pending rows keep
 *   approved_at '0000-00-00', as the app leaves them.
 * - A fully approved leave marks each of its days FullDayLeave in parent_attendaces, as
 *   LeaveController::markAttendanceForApprovedLeave does (nobody punched in on those days). A rostered day
 *   off inside a leave stays a day off — the app would mark it too, and payroll would then pay it twice.
 * - employee_travel_passes + employee_travel_pass_status = Common::buildIslandPassApprovalChain rows, inserted
 *   SM, HR, HOD and acted on highest id first (HOD → HR → SM). Security marks past trips departed/arrived.
 * Every balance stays within the person's benefit-grid allocation (Common::getEmployeeLeaveBalance).
 */
class DemoLeaveSeeder
{
    const AHEAD = 60; // days ahead people have already applied for
    // Leave that takes someone off the island — usually with an island pass.
    const TRAVEL = ['Annual Leave', 'Rest Relaxation Leave', 'Emergency Leave', 'Unpaid Leave', 'Paternity Leave', 'Maternity Leave'];
    const HOME = ['Indian' => 'Kochi, India', 'Sri Lankan' => 'Colombo, Sri Lanka', 'Bangladeshi' => 'Dhaka, Bangladesh',
        'Nepalese' => 'Kathmandu, Nepal', 'Filipino' => 'Manila, Philippines', 'British' => 'London, UK', 'German' => 'Munich, Germany',
        'Italian' => 'Milan, Italy'];
    const ISLANDS = ['Malé', 'Addu City', 'Fuvahmulah', 'Kulhudhuffushi', 'Thinadhoo', 'Naifaru', 'Eydhafushi', 'Maafushi'];
    const REASONS = [
        'Annual Leave' => ['Annual leave — going home to see family.', 'Annual leave, family visit.', 'Taking my annual leave for a family wedding.',
            'Annual leave to rest and travel.', 'Annual leave — home visit after the high season.'],
        'Sick Leave' => ['High fever since last night.', 'Food poisoning, seen by the doctor.', 'Back pain, advised rest for two days.',
            'Flu and sore throat.', 'Migraine, unable to work.', 'Stomach infection.'],
        'Emergency Leave' => ['Family emergency — my father is in hospital.', 'Urgent family matter at home.', 'Death in the family.'],
        'Birthday Leave' => ['Birthday leave.'],
        'Paternity Leave' => ['My wife is due to give birth.'],
        'Maternity Leave' => ['Maternity leave as advised by my doctor.'],
        'Rest Relaxation Leave' => ['R&R break after the season.', 'Rest and relaxation leave.'],
        'Circumcision Leave' => ["My son's circumcision ceremony."],
        'Unpaid Leave' => ['Personal matters to settle at home.', 'Extending my holiday to finish house work at home.'],
    ];
    const REJECTED = ['Peak occupancy that week — please choose dates after the 20th.', 'Two team members are already off on these dates.',
        'We have a VIP group arriving — please re-apply for the following week.', 'Please re-apply once the roster for that month is out.'];
    const TRIPS = ['Personal errands in Malé.', 'Bank visit in Malé.', 'Medical appointment at IGMH.', 'Visiting family on Maafushi.',
        'Shopping in Malé.', 'Passport renewal at the immigration office.'];

    private Carbon $now;
    private Carbon $today;
    private object $staff;          // active employees by id
    private array $categories;      // leave_type => row
    private array $transport;       // option => id
    private array $budget = [];     // [emp][category id] => [allocated, prorated this year]
    private array $taken = [];      // [emp][category id][year] => days
    private array $busy = [];       // [emp][Y-m-d] => true
    private array $passChains = [];
    private array $approved = [];   // fully approved leaves, to mark in the attendance
    private array $counts = ['leave requests' => 0, 'island passes' => 0, 'leave days in attendance' => 0];

    public function __construct(private int $resortId, private int $adminId)
    {
    }

    public function seed(): array
    {
        $this->now = Carbon::now();
        $this->today = Carbon::today();
        $this->staff = DB::table('employees as e')->join('resort_admins as a', 'a.id', '=', 'e.Admin_Parent_id')
            ->where('e.resort_id', $this->resortId)->where('e.status', 'Active')->whereNull('e.deleted_at')->orderBy('e.id')
            ->get(['e.id', 'e.rank', 'e.Dept_id', 'e.reporting_to', 'e.Admin_Parent_id', 'e.nationality', 'e.religion', 'e.dob',
                'e.joining_date', 'a.gender', 'a.email'])
            ->keyBy('id');
        $this->categories = DB::table('leave_categories')->where('resort_id', $this->resortId)->get()->keyBy('leave_type')->all();
        $this->transport = DB::table('resort_transportations')->where('resort_id', $this->resortId)->pluck('id', 'transportation_option')->all();
        foreach ($this->staff as $e) {
            foreach (Common::getEmployeeLeaveBalance($e->id, $this->resortId) as $catId => $b) {
                $this->budget[$e->id][$catId] = [$b['allocated'], $b['prorated']];
            }
        }

        $start = $this->today->copy()->startOfMonth()->subMonths(DemoPayrollSeeder::RUNS);
        $end = $this->today->copy()->addDays(self::AHEAD);
        $this->maternity();
        foreach ($this->staff as $e) {
            $this->history($e, $start->copy()->max(Carbon::parse($e->joining_date ?: $start)), $end);
        }
        $this->onLeaveToday();
        $this->awaitingEachApprover();
        $this->markAttendance();
        $this->dayTrips($start);
        return $this->counts;
    }

    /** One employee's leave over the window: what people at a resort typically take in a year. */
    private function history(object $e, Carbon $start, Carbon $end): void
    {
        $expat = $e->nationality !== 'Maldivian';
        $past = $this->today->copy()->subDay();
        if ($expat) { // one long home leave, sometimes a short break
            $this->place($e, 'Annual Leave', $start, $end, mt_rand(16, 30));
            if ($this->chance(0.4)) {
                $this->place($e, 'Annual Leave', $start, $end, mt_rand(3, 6));
            }
        } else { // locals take shorter breaks more often
            for ($i = mt_rand(2, 4); $i > 0; $i--) {
                $this->place($e, 'Annual Leave', $start, $end, mt_rand(3, 8));
            }
        }
        for ($i = mt_rand(0, 3); $i > 0; $i--) {
            $this->place($e, 'Sick Leave', $start, $past, mt_rand(1, 3));
        }
        if ($this->chance(0.15)) {
            $this->place($e, 'Emergency Leave', $start, $past, mt_rand(2, 5));
        }
        if (in_array((int) $e->rank, [1, 2], true) && $this->chance(0.7)) {
            $this->place($e, 'Rest Relaxation Leave', $start, $end, mt_rand(5, 7));
        }
        if ($e->gender === 'male' && $this->chance(0.05)) {
            $this->place($e, 'Paternity Leave', $start, $end, 3);
        }
        if ($e->gender === 'male' && $e->religion === 'Muslim' && $this->chance(0.03)) {
            $this->place($e, 'Circumcision Leave', $start, $end, mt_rand(3, 5));
        }
        if ($this->chance(0.04)) {
            $this->place($e, 'Unpaid Leave', $start, $end, mt_rand(5, 10));
        }
        // Birthday leave on the birthday itself, when it falls in the window.
        if ($e->dob && $this->chance(0.5)) {
            foreach ([$start->year, $start->year + 1] as $year) {
                $day = Carbon::parse($e->dob)->setYear($year);
                if ($day->between($start, $end)) {
                    $this->place($e, 'Birthday Leave', $day, $day, 1);
                }
            }
        }
    }

    /** One maternity leave in progress, so "who is on leave" always has a long one. */
    private function maternity(): void
    {
        $mother = $this->staff->first(fn ($e) => $e->gender === 'female' && in_array((int) $e->rank, [5, 6], true) && !$this->isLogin($e)
            && Carbon::parse($e->joining_date)->lt($this->today->copy()->subYear()));
        if ($mother) {
            $from = $this->today->copy()->subDays(20);
            $this->place($mother, 'Maternity Leave', $from, $from->copy()->addDays(59), 60, 'Approved');
        }
    }

    /** At least four people are on approved leave today. */
    private function onLeaveToday(): void
    {
        $on = fn () => DB::table('employees_leaves')->where('resort_id', $this->resortId)->where('status', 'Approved')
            ->where('from_date', '<=', $this->today->format('Y-m-d'))->where('to_date', '>=', $this->today->format('Y-m-d'))->count();
        foreach ($this->staff->shuffle()->where('rank', 6) as $e) {
            if ($on() >= 4) {
                return;
            }
            $from = $this->today->copy()->subDays(mt_rand(1, 4));
            $length = mt_rand(6, 10);
            $this->place($e, 'Annual Leave', $from, $from->copy()->addDays($length - 1), $length, 'Approved');
        }
    }

    /** Every demo login that approves leave has a request waiting on them. */
    private function awaitingEachApprover(): void
    {
        foreach ($this->staff as $approver) {
            if (!$this->isLogin($approver) || $this->waitingOn($approver->id)) {
                continue;
            }
            // A HOD's only report can be their manager, whose annual leave may be used up — then unpaid leave.
            foreach (['Annual Leave', 'Unpaid Leave'] as $type) {
                foreach ($this->staff->where('reporting_to', $approver->id)->shuffle() as $e) {
                    $step = array_search($approver->id, $this->chain($e), true);
                    $from = $this->today->copy()->addDays(5);
                    if ($step !== false && $this->place($e, $type, $from, $from->copy()->addDays(self::AHEAD - 5), mt_rand(2, 5), 'Pending', $step)) {
                        continue 3;
                    }
                }
            }
        }
    }

    private function waitingOn(int $approverId): bool
    {
        return DB::table('employees_leaves_status as s')->join('employees_leaves as l', 'l.id', '=', 's.leave_request_id')
            ->where('l.resort_id', $this->resortId)->where('l.status', 'Pending')->where('s.status', 'Pending')
            ->where('s.approver_id', $approverId)
            ->whereRaw('s.id = (SELECT MIN(p.id) FROM employees_leaves_status p WHERE p.leave_request_id = l.id AND p.status = ?)', ['Pending'])
            ->exists();
    }

    /**
     * Finds free dates for a $length-day leave between $earliest and $latest and records it, within the grid
     * allocation. $outcome/$step force the status (otherwise it follows the dates). False when it doesn't fit.
     */
    private function place(object $e, string $type, Carbon $earliest, Carbon $latest, int $length, ?string $outcome = null, ?int $step = null): bool
    {
        $cat = $this->categories[$type] ?? null;
        if (!$cat || !isset($this->budget[$e->id][$cat->id])) {
            return false;
        }
        $room = (int) $earliest->copy()->startOfDay()->diffInDays($latest->copy()->startOfDay(), false) - $length + 2; // possible first days
        for ($try = 0; $try < 15 && $room > 0; $try++) {
            $from = $earliest->copy()->startOfDay()->addDays(mt_rand(0, $room - 1));
            $to = $from->copy()->addDays($length - 1);
            if (($from->isFriday() && $length === 1) || $from->year !== $to->year) {
                continue; // Friday isn't a working day; a leave across New Year would count against both years' balance
            }
            if ($this->isLogin($e) && $from->lte($this->today->copy()->addWeek()) && $to->gte($this->today)) {
                continue; // a demo login is never away this week — the presenter signs in as them
            }
            $days = Common::calculateLeaveWorkingDays($from, $to);
            [$allocated, $prorated] = $this->budget[$e->id][$cat->id];
            $allowance = $from->year === $this->today->year ? $prorated : $allocated;
            if ($days < 1 || ($this->taken[$e->id][$cat->id][$from->year] ?? 0) + $days > $allowance || $this->overlaps($e->id, $from, $to)) {
                continue;
            }
            $this->taken[$e->id][$cat->id][$from->year] = ($this->taken[$e->id][$cat->id][$from->year] ?? 0) + $days;
            for ($d = $from->copy()->subDay(); $d->lte($to->copy()->addDay()); $d->addDay()) {
                $this->busy[$e->id][$d->format('Y-m-d')] = true; // a day's gap between two leaves
            }
            $this->record($e, $cat, $from, $to, $days, $outcome, $step);
            return true;
        }
        return false;
    }

    private function overlaps(int $empId, Carbon $from, Carbon $to): bool
    {
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            if (isset($this->busy[$empId][$d->format('Y-m-d')])) {
                return true;
            }
        }
        return false;
    }

    /** Writes the leave, its approval chain at the right stage, and (for a trip) its island pass. */
    private function record(object $e, object $cat, Carbon $from, Carbon $to, int $days, ?string $outcome, ?int $step): void
    {
        $type = $cat->leave_type;
        $planned = !in_array($type, ['Sick Leave', 'Emergency Leave'], true);
        if ($outcome === null) {
            if ($from->gt($this->today)) { // still ahead: some decided, many waiting
                $outcome = $this->chance(0.45) ? 'Approved' : ($this->chance(0.85) ? 'Pending' : 'Rejected');
            } else {
                $outcome = $planned && $to->lt($this->today) && $this->chance(0.1) ? 'Rejected' : 'Approved';
            }
        }
        $chain = $this->chain($e);
        $acted = match ($outcome) {
            'Approved' => count($chain),
            'Pending' => $step ?? mt_rand(0, count($chain) - 1),
            'Rejected' => mt_rand(0, count($chain) - 1),
        };

        // Planned leave is applied for weeks ahead; sick/emergency leave the morning it starts.
        $applied = $planned ? $from->copy()->subDays(mt_rand(7, 35))->setTime(mt_rand(9, 17), mt_rand(0, 59))
            : $from->copy()->setTime(mt_rand(6, 8), mt_rand(0, 59));
        $applied = $applied->min($this->now->copy()->subHours(mt_rand(2, 30)));
        $by = $planned ? $from->copy()->subDay()->setTime(18, 0) : $from->copy()->addDay()->setTime(12, 0);
        $times = $this->timeline($applied, $acted + ($outcome === 'Rejected' ? 1 : 0), $by);

        $colleague = $this->staff->where('Dept_id', $e->Dept_id)->where('id', '!=', $e->id)->pluck('id')->shuffle()->first();
        $destination = null;
        if (in_array($type, self::TRAVEL, true)) {
            $destination = $e->nationality === 'Maldivian' ? self::ISLANDS[array_rand(self::ISLANDS)] : (self::HOME[$e->nationality] ?? null);
        }
        $leaveId = DB::table('employees_leaves')->insertGetId([
            'resort_id' => $this->resortId, 'emp_id' => $e->id, 'leave_category_id' => $cat->id,
            'from_date' => $from->format('Y-m-d'), 'to_date' => $to->format('Y-m-d'), 'total_days' => $days, 'duration' => '',
            'reason' => $this->pick(self::REASONS[$type] ?? ['Personal leave.']), 'task_delegation' => $colleague, 'destination' => $destination,
            'status' => $outcome, 'created_at' => $applied, 'updated_at' => end($times) ?: $applied,
        ]);
        $rows = [];
        foreach ($chain as $i => $approverId) {
            $status = $i < $acted ? 'Approved' : ($outcome === 'Rejected' && $i === $acted ? 'Rejected' : 'Pending');
            $at = $times[$i] ?? null;
            $rows[] = [
                'leave_request_id' => $leaveId, 'status' => $status, 'comments' => $status === 'Rejected' ? $this->pick(self::REJECTED) : null,
                'approver_rank' => (string) $this->staff[$approverId]->rank, 'approver_id' => (string) $approverId,
                'approved_at' => $status === 'Pending' ? '0000-00-00' : $at->format('Y-m-d'),
                'created_at' => $applied, 'updated_at' => $status === 'Pending' ? $applied : $at,
            ];
        }
        DB::table('employees_leaves_status')->insert($rows);
        $this->counts['leave requests']++;

        if ($outcome === 'Approved') {
            $this->approved[] = [$e, $from, $to, end($times), $this->staff[end($chain)]->Admin_Parent_id ?: $this->adminId];
        }
        if ($outcome !== 'Rejected' && in_array($type, self::TRAVEL, true) && $days >= 2 && $this->chance(0.85)) {
            $this->pass($e, $from->copy()->setTime(...$this->pick([[7, 30], [10, 15], [14, 0]])), $to->copy()->setTime(...$this->pick([[11, 45], [15, 30], [17, 10]])),
                $outcome, $applied, $by, $leaveId, $type === 'Annual Leave' ? 'Going on annual leave.' : "Going on {$type}.", 'Returning from leave.');
        }
    }

    /** The leave approval chain LeaveController@leaveAdd builds (the demo resort has no clinic staff). */
    private function chain(object $e): array
    {
        $gm = $this->staff->firstWhere('rank', 8);
        $ids = match ((int) $e->rank) {
            8 => [$this->staff->first(fn ($s) => (int) $s->rank === 1 && $s->email === 'hr@' . config('demo.login_domain'))?->id],
            1 => [$gm->id],
            2, 4 => [$e->reporting_to, $gm->id],
            default => [$e->reporting_to],
        };
        return array_values(array_unique(array_filter($ids, fn ($id) => $id && (int) $id !== (int) $e->id && isset($this->staff[$id]))));
    }

    /**
     * An island pass: its chain from Common::buildIslandPassApprovalChain, acted on HOD first. $outcome is
     * Approved / Pending / Rejected / Cancel.
     */
    private function pass(object $e, Carbon $out, Carbon $back, string $outcome, Carbon $applied, Carbon $by, ?int $leaveId, string $why, string $whyBack): void
    {
        $chain = $this->passChains[$e->id] ??= Common::buildIslandPassApprovalChain($this->resortId, $e);
        if ($chain['blocked'] || $chain['flow']->isEmpty()) {
            return;
        }
        $flow = $chain['flow']->values();
        $n = $flow->count();
        $acted = match ($outcome) {
            'Approved' => $n,
            'Pending' => mt_rand(0, $n - 1),
            'Rejected' => mt_rand(0, $n - 1),
            'Cancel' => 0,
        };
        $times = $this->timeline($applied, $acted + ($outcome === 'Rejected' || $outcome === 'Cancel' ? 1 : 0), $by);
        $mode = (string) ($e->nationality === 'Maldivian' && $this->chance(0.2) ? $this->transport['Domestic Flight'] : $this->transport[$this->pick(['Seaplane', 'Speedboat'])]);
        $passId = DB::table('employee_travel_passes')->insertGetId([
            'resort_id' => $this->resortId, 'employee_id' => $e->id, 'leave_request_id' => $leaveId,
            'departure_date' => $out->format('Y-m-d'), 'departure_time' => $out->format('H:i'), 'departure_mode' => $mode, 'departure_reason' => $why,
            'arrival_date' => $back->format('Y-m-d'), 'arrival_time' => $back->format('H:i'), 'arrival_mode' => $mode, 'arrival_reason' => $whyBack,
            'status' => $outcome,
            'employee_departure_status' => $outcome === 'Approved' && $out->lte($this->now) ? 'departed' : null,
            'employee_arrival_status' => $outcome === 'Approved' && $back->lte($this->now) ? 'arrived' : null,
            'created_at' => $applied, 'updated_at' => end($times) ?: $applied,
        ]);
        $rows = [];
        foreach ($flow as $i => $approver) {
            $turn = $n - 1 - $i; // the last row inserted acts first
            $status = match (true) {
                $outcome === 'Cancel' => 'Cancel',
                $turn < $acted => 'Approved',
                $outcome === 'Rejected' && $turn === $acted => 'Rejected',
                default => 'Pending',
            };
            $at = $status === 'Cancel' ? $times[0] : ($times[$turn] ?? null);
            $rows[] = [
                'travel_pass_id' => $passId, 'approver_id' => (string) $approver->id, 'approver_rank' => (string) $approver->rank,
                'approver_role' => $approver->approver_role, 'status' => $status,
                'comments' => $status === 'Rejected' ? 'Not possible on that day — we are short-staffed.' : null,
                'approved_at' => in_array($status, ['Approved', 'Rejected'], true) ? $at : null,
                'created_at' => $applied, 'updated_at' => $at ?? $applied,
            ];
        }
        DB::table('employee_travel_pass_status')->insert($rows);
        $this->counts['island passes']++;
    }

    /** Day trips off the island on a rostered day off, each with its own island pass. */
    private function dayTrips(Carbon $start): void
    {
        foreach ($this->staff as $e) {
            $offs = DB::table('duty_roster_entries')->where('Emp_id', $e->id)->where('Status', 'DayOff')
                ->whereBetween('date', [$start->format('Y-m-d'), $this->today->copy()->addWeek()->endOfWeek()->format('Y-m-d')])
                ->pluck('date')->reject(fn ($d) => isset($this->busy[$e->id][$d]))->shuffle()->take($this->pick([0, 0, 1, 1, 1, 2, 2, 3]));
            foreach ($offs as $date) {
                $day = Carbon::parse($date);
                $future = $day->gte($this->today);
                $outcome = $future ? ($this->chance(0.65) ? 'Pending' : 'Approved')
                    : $this->pick(array_merge(array_fill(0, 16, 'Approved'), ['Rejected', 'Rejected', 'Cancel', 'Cancel']));
                $applied = $day->copy()->subDays(mt_rand(1, 6))->setTime(mt_rand(8, 20), mt_rand(0, 59))->min($this->now->copy()->subHours(mt_rand(1, 20)));
                $this->pass($e, $day->copy()->setTime(...$this->pick([[8, 0], [8, 45], [9, 30]])), $day->copy()->setTime(...$this->pick([[16, 0], [17, 15], [18, 30]])),
                    $outcome, $applied, $day->copy()->setTime(7, 0), null, $this->pick(self::TRIPS), 'Returning to the resort.');
            }
        }
    }

    /**
     * Approved leave in the attendance, as LeaveController::markAttendanceForApprovedLeave writes it: each day
     * becomes a manual FullDayLeave row for the rostered shift (the punches the time seeder made are removed).
     * Days past the roster get a one-day roster row first, as the app does.
     */
    private function markAttendance(): void
    {
        $shifts = DB::table('shift_settings')->where('resort_id', $this->resortId)->orderBy('id')->get()->keyBy('id');
        $fallback = $shifts->first();
        foreach ($this->approved as [$e, $from, $to, $approvedAt, $approverAdmin]) {
            $range = [$from->format('Y-m-d'), $to->format('Y-m-d')];
            $roster = DB::table('duty_roster_entries')->where('Emp_id', $e->id)->whereBetween('date', $range)->get()->keyBy('date');
            $old = DB::table('parent_attendaces')->where('Emp_id', $e->id)->whereBetween('date', $range)->pluck('id', 'date');
            $stamp = ['created_by' => $approverAdmin, 'modified_by' => $approverAdmin, 'created_at' => $approvedAt, 'updated_at' => $approvedAt];
            $remove = $days = [];
            for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
                $date = $d->format('Y-m-d');
                $entry = $roster[$date] ?? null;
                if ($entry?->Status === 'DayOff' || (!$entry && $d->lte($this->today->copy()->addWeek()->endOfWeek()))) {
                    continue; // a day off stays a day off; no roster inside the planned weeks = not employed yet
                }
                if (!$entry) {
                    $rosterId = DB::table('duty_rosters')->insertGetId($stamp + ['resort_id' => $this->resortId, 'Shift_id' => $fallback->id,
                        'Emp_id' => $e->id, 'ShiftDate' => "{$date} - {$date}", 'Year' => $d->format('Y')]);
                    DB::table('duty_roster_entries')->insert($stamp + ['roster_id' => $rosterId, 'resort_id' => $this->resortId,
                        'Shift_id' => $fallback->id, 'Emp_id' => $e->id, 'date' => $date, 'Status' => 'FullDayLeave',
                        'CheckingTime' => $fallback->StartTime, 'CheckingOutTime' => $fallback->EndTime]);
                    $entry = (object) ['roster_id' => $rosterId, 'Shift_id' => $fallback->id, 'DayWiseTotalHours' => null];
                }
                if (isset($old[$date])) {
                    $remove[] = $old[$date];
                }
                $shift = $shifts[$entry->Shift_id] ?? $fallback;
                $days[] = $stamp + ['Emp_id' => $e->id, 'date' => $date, 'roster_id' => $entry->roster_id, 'resort_id' => $this->resortId,
                    'Shift_id' => $entry->Shift_id, 'CheckingTime' => $shift->StartTime, 'CheckingOutTime' => $shift->EndTime,
                    'DayWiseTotalHours' => $entry->DayWiseTotalHours ?? '00:00', 'Status' => 'FullDayLeave', 'CheckInCheckOut_Type' => 'Manual'];
            }
            if ($remove) {
                DB::table('child_attendaces')->whereIn('Parent_attd_id', $remove)->delete();
                DB::table('break_attendaces')->whereIn('Parent_attd_id', $remove)->delete();
                DB::table('employee_overtimes')->whereIn('parent_attendance_id', $remove)->delete();
                DB::table('parent_attendaces')->whereIn('id', $remove)->delete();
            }
            if (!$days) {
                continue;
            }
            DB::table('parent_attendaces')->insert($days);
            $ids = DB::table('parent_attendaces')->where('Emp_id', $e->id)->where('Status', 'FullDayLeave')->whereBetween('date', $range)->get(['id', 'CheckingTime', 'CheckingOutTime']);
            DB::table('child_attendaces')->insert($ids->map(fn ($p) => ['Parent_attd_id' => $p->id, 'InTime_out' => $p->CheckingTime,
                'OutTime_out' => $p->CheckingOutTime, 'InTime_Location' => 'Leave Approval Auto-Mark', 'OutTime_Location' => 'Leave Approval Auto-Mark',
                'created_at' => $approvedAt, 'updated_at' => $approvedAt])->all());
            $this->counts['leave days in attendance'] += count($days);
        }
    }

    /** $steps action times after $applied, spaced out before $by and never later than now. */
    private function timeline(Carbon $applied, int $steps, Carbon $by): array
    {
        $end = $by->copy()->min($this->now)->timestamp;
        $span = max(1800, $end - $applied->timestamp);
        $times = [];
        for ($i = 0; $i < $steps; $i++) {
            $times[] = Carbon::createFromTimestamp($applied->timestamp + (int) ($span * ($i + mt_rand(30, 90) / 100) / $steps))->min($this->now);
        }
        return $times;
    }

    private function isLogin(object $e): bool
    {
        return Demo::isLogin($e->email);
    }

    private function pick(array $items)
    {
        return $items[mt_rand(0, count($items) - 1)];
    }

    private function chance(float $p): bool
    {
        return mt_rand() / mt_getrandmax() < $p;
    }
}
