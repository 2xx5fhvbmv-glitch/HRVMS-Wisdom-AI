<?php

namespace App\Services;

use App\Helpers\Common;
use App\Models\Employee;
use App\Models\ResortAdmin;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Data for the leader master dashboard (non-HR HOD / ExCom / GM —
 * resources/views/resorts/master-dashboard/leaderdashboard.blade.php).
 *
 * Scope (founder, 2026-10-19): Common::getScopedDepartmentIds() — the GM (and any
 * full-access user) sees the whole resort, a non-HR HOD/ExCom only their own
 * department. Every query is resort-scoped. Nothing here invents a number: a value
 * the system has no data for is left out / null and the view shows "—".
 */
class LeaderDashboard
{
    public const LEAVE_STATES = ['FullDayLeave', 'HalfDayLeave'];

    private Employee $employee;
    private int $resortId;
    private ?array $deptIds; // null = whole resort
    private ?array $employeeIds = null;

    public function __construct(ResortAdmin $user)
    {
        $this->employee = $user->GetEmployee;
        $this->resortId = (int) $user->resort_id;
        $this->deptIds = Common::getScopedDepartmentIds($this->employee);
    }

    /** For the signed-in resort user, or null when they have no employee record. */
    public static function forCurrentUser(): ?self
    {
        $user = \Auth::guard('resort-admin')->user();
        return ($user && $user->GetEmployee) ? new self($user) : null;
    }

    /** Active employees in scope (ids). */
    public function employeeIds(): array
    {
        return $this->employeeIds ??= Employee::where('resort_id', $this->resortId)->where('status', 'Active')
            ->when($this->deptIds !== null, fn ($q) => $q->whereIn('Dept_id', $this->deptIds))
            ->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * What the view gets on top of the controller's own variables: `leader`, plus the
     * values gm_dashboard() doesn't compute (only filled when the controller didn't pass them).
     */
    public function viewData(array $existing): array
    {
        $data = ['leader' => $this->leader()];
        if (!array_key_exists('pendingResignationsForHod', $existing)) {
            $data['pendingResignationsForHod'] = $this->pendingResignations();
        }
        if (!array_key_exists('monthlyCheckinPerformance', $existing)) {
            $data['monthlyCheckinPerformance'] = self::monthlyCheckins($this->resortId, $this->deptIds);
        }
        if (!array_key_exists('totalOverallWorkingHours', $existing)) {
            $data['totalOverallWorkingHours'] = round($this->overtimeHours(Carbon::now()->startOfMonth(), Carbon::now()), 2);
        }
        return $data;
    }

    public function leader(): array
    {
        return [
            'absent_delta' => $this->absentDelta(),
            'health' => $this->health(),
            'requests' => $this->requests(),
            'events' => $this->events(),
        ];
    }

    /** Today's absent % minus the same weekday last week's, in percentage points (null without headcount). */
    private function absentDelta(): ?float
    {
        $headcount = count($this->employeeIds());
        if ($headcount === 0) {
            return null;
        }
        $absentOn = fn (Carbon $day) => DB::table('parent_attendaces')->where('resort_id', $this->resortId)
            ->whereIn('Emp_id', $this->employeeIds())->whereDate('date', $day)->where('Status', 'Absent')->count();
        return round(($absentOn(Carbon::today()) - $absentOn(Carbon::today()->subWeek())) / $headcount * 100, 1);
    }

    private function health(): array
    {
        $ids = $this->employeeIds();
        $now = Carbon::now();
        $otThis = $this->overtimeHours($now->copy()->startOfMonth(), $now);
        // Same days of last month, so a part month is compared fairly.
        $lastStart = $now->copy()->subMonthNoOverflow()->startOfMonth();
        $otLast = $this->overtimeHours($lastStart, $lastStart->copy()->addDays(min($now->day, $lastStart->daysInMonth) - 1)->endOfDay());
        $headcount = count($ids);
        $leavers = fn (int $days) => DB::table('employee_resignation as r')->join('employees as e', 'e.id', '=', 'r.employee_id')
            ->where('r.resort_id', $this->resortId)->whereIn('r.status', ['Approved', 'Completed'])
            ->whereBetween('r.last_working_day', [Carbon::today()->subDays($days)->toDateString(), Carbon::today()->toDateString()])
            ->when($this->deptIds !== null, fn ($q) => $q->whereIn('e.Dept_id', $this->deptIds))
            ->count();

        return [
            // Manager reviews not completed, in cycles not closed.
            'appraisals_pending' => DB::table('performa_child_cycles as c')->join('performance_cycles as p', 'p.id', '=', 'c.Parent_cycle_id')
                ->where('p.resort_id', $this->resortId)->whereNotIn(DB::raw('LOWER(p.status)'), ['completed', 'closed'])
                ->whereIn('c.Emp_main_id', $ids)->where(fn ($q) => $q->whereNull('c.manager_review_status')->orWhere('c.manager_review_status', '!=', 'completed'))
                ->count(),
            'in_pip' => DB::table('employee_pip_plans')->where('resort_id', $this->resortId)->where('status', 'active')->whereIn('employee_id', $ids)->count(),
            'in_pdp' => DB::table('employee_pdp_plans')->where('resort_id', $this->resortId)->where('status', 'active')->whereIn('employee_id', $ids)->count(),
            'overtime_hours' => round($otThis, 1),
            'overtime_vs_last' => $otLast > 0 ? round($otThis / $otLast, 1) : null,
            'open_grievances' => DB::table('grivance_submission_models')->where('resort_id', $this->resortId)
                ->whereIn('status', ['pending', 'in_review'])->whereIn('Employee_id', $ids)->count(),
            'disciplinary_cases' => DB::table('disciplinary_submits')->where('resort_id', $this->resortId)
                ->whereIn('status', ['pending', 'In_Review', 'Acknowledged'])->whereIn('Employee_id', $ids)->count(),
            // Leavers (approved resignations whose last working day fell in the window) ÷ current headcount.
            'turnover_annual' => $headcount ? round($leavers(365) / $headcount * 100, 1) : null,
            'turnover_quarter' => $headcount ? round($leavers(90) / $headcount * 100, 1) : null,
        ];
    }

    /** Approved overtime hours (what payroll pays) for the scope between two dates. */
    private function overtimeHours(Carbon $from, Carbon $to): float
    {
        return (float) DB::table('parent_attendaces')->where('resort_id', $this->resortId)->whereIn('Emp_id', $this->employeeIds())
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])->where('OTStatus', 'Approved')
            ->whereNotNull('OverTime')->pluck('OverTime')
            ->sum(function ($ot) {
                [$h, $m] = array_pad(explode(':', (string) $ot), 2, 0);
                return (int) $h + (int) $m / 60;
            });
    }

    /**
     * Leave and overtime awaiting THIS user's decision (their own, or as a delegate).
     * ok/no POST {id} to LeaderDashboardController::decide(), which runs the module's own
     * approve/reject. No undo: neither module can reverse a decision.
     */
    public function requests(): array
    {
        $acting = Common::actingEmployees($this->employee, $this->resortId)->pluck('id')->all();
        $rows = [];

        $leaves = DB::table('employees_leaves as l')
            ->join('employees as e', 'e.id', '=', 'l.emp_id')->join('resort_admins as a', 'a.id', '=', 'e.Admin_Parent_id')
            ->leftJoin('leave_categories as c', 'c.id', '=', 'l.leave_category_id')
            ->where('l.resort_id', $this->resortId)->where('l.status', 'Pending')
            ->whereExists(fn ($q) => $q->from('employees_leaves_status as s')->whereColumn('s.leave_request_id', 'l.id')
                ->where('s.status', 'Pending')->whereIn('s.approver_id', $acting))
            ->orderBy('l.created_at')->limit(50)
            ->get(['l.id', 'l.from_date', 'l.to_date', 'l.total_days', 'l.created_at', 'a.id as admin_id', 'a.first_name', 'a.last_name', 'c.leave_type']);
        foreach ($leaves as $l) {
            $rows[] = $this->requestRow('leave', $l->id, $l, ($l->leave_type ?: 'Leave') . ' · ' . (float) $l->total_days . ' day(s), '
                . Carbon::parse($l->from_date)->format('d M') . '–' . Carbon::parse($l->to_date)->format('d M Y') . ', awaiting your decision',
                route('leave.details', ['leave_id' => base64_encode($l->id)]));
        }

        $overtime = DB::table('employee_overtimes as o')
            ->join('employees as e', 'e.id', '=', 'o.Emp_id')->join('resort_admins as a', 'a.id', '=', 'e.Admin_Parent_id')
            ->where('o.resort_id', $this->resortId)->where('o.status', 'pending')
            ->when($this->deptIds !== null, fn ($q) => $q->whereIn('e.Dept_id', $this->deptIds))
            ->orderBy('o.date')->limit(50)
            ->get(['o.id', 'o.Emp_id', 'o.date', 'o.total_time', 'o.created_at', 'a.id as admin_id', 'a.first_name', 'a.last_name']);
        foreach ($overtime as $o) {
            // Same gate UpdateOvertimeStatus() applies per entry.
            if (!Common::canManageAttendanceFor($o->Emp_id, $this->employee)) {
                continue;
            }
            $rows[] = $this->requestRow('overtime', $o->id, $o, 'Overtime · ' . ($o->total_time ?: '—') . ' hrs on ' . Carbon::parse($o->date)->format('d M Y')
                . ', awaiting your decision', route('resort.timeandattendance.OverTime'));
        }
        return $rows;
    }

    private function requestRow(string $type, int $id, object $row, string $detail, string $view): array
    {
        return [
            'id' => $id,
            'type' => $type,
            'n' => trim($row->first_name . ' ' . $row->last_name),
            'photo' => Common::getResortUserPicture($row->admin_id),
            'r' => 'Request #' . $id,
            'd' => $detail,
            'm' => 'Submitted ' . Carbon::parse($row->created_at)->format('d M Y'),
            'view' => $view,
            'ok' => route('resort.master.leader_request', ['type' => $type, 'decision' => 'approve']),
            'no' => route('resort.master.leader_request', ['type' => $type, 'decision' => 'reject']),
        ];
    }

    /**
     * Upcoming trainings (next 60 days) with someone in scope taking part. Drills aren't
     * scheduled in the system and there's no Employee of the Month — neither is shown.
     */
    private function events(): array
    {
        $ids = $this->employeeIds();
        return DB::table('training_schedules as t')
            ->leftJoin('learning_programs as lp', 'lp.id', '=', 't.training_id')
            ->where('t.resort_id', $this->resortId)
            ->whereBetween('t.start_date', [Carbon::today()->toDateString(), Carbon::today()->addDays(60)->toDateString()])
            ->whereNotIn(DB::raw('LOWER(COALESCE(t.status, ""))'), ['cancelled', 'completed'])
            ->whereExists(fn ($q) => $q->from('training_participants as tp')->whereColumn('tp.training_schedule_id', 't.id')->whereIn('tp.employee_id', $ids))
            ->orderBy('t.start_date')->limit(20)
            ->get(['t.id', 't.start_date', 't.start_time', 't.venue', 'lp.name'])
            ->map(function ($t) use ($ids) {
                $people = DB::table('training_participants as tp')->join('employees as e', 'e.id', '=', 'tp.employee_id')
                    ->join('resort_admins as a', 'a.id', '=', 'e.Admin_Parent_id')
                    ->where('tp.training_schedule_id', $t->id)->whereIn('tp.employee_id', $ids)->limit(8)
                    ->get(['a.id', 'a.first_name', 'a.last_name'])
                    ->map(fn ($p) => ['n' => trim($p->first_name . ' ' . $p->last_name), 'p' => Common::getResortUserPicture($p->id)])->all();
                return [
                    'date' => Carbon::parse($t->start_date)->toDateString(),
                    'title' => $t->name ?: 'Training',
                    'sub' => 'Training' . ($t->start_time ? ' · ' . substr($t->start_time, 0, 5) : '') . ($t->venue ? ' · ' . $t->venue : ''),
                    'people' => $people,
                ];
            })->all();
    }

    /** Resignations waiting at the HOD step — the GM's resort-wide oversight list. */
    public function pendingResignations(): Collection
    {
        return \App\Models\EmployeeResignation::with(['employee.resortAdmin', 'employee.department', 'employee.position'])
            ->where('resort_id', $this->resortId)->where('hod_status', 'Pending')
            ->whereNotIn('status', ['Withdraw', 'Approved', 'Rejected'])
            ->whereHas('employee', fn ($q) => $q->when($this->deptIds !== null, fn ($d) => $d->whereIn('Dept_id', $this->deptIds)))
            ->orderByDesc('created_at')->get();
    }

    /** Unfinished department exit-clearance forms in scope — same query as gm_dashboard()'s resort-wide list. */
    public function pendingExitClearance(): Collection
    {
        return \App\Models\ExitClearanceFormAssignment::with([
                'exitClearanceForm:id,form_name',
                'employeeResignation:id,employee_id,last_working_day',
                'employeeResignation.employee:id,Admin_Parent_id,Position_id,Dept_id',
                'employeeResignation.employee.resortAdmin:id,first_name,last_name',
                'employeeResignation.employee.position:id,position_title',
            ])
            ->where('resort_id', $this->resortId)->where('assigned_to_type', 'department')
            ->whereIn('status', ['Pending', 'In Progress', 'On Hold'])
            ->when($this->deptIds !== null, fn ($q) => $q->whereHas('employeeResignation.employee', fn ($e) => $e->whereIn('Dept_id', $this->deptIds)))
            ->orderByRaw('CASE WHEN deadline_date IS NULL THEN 1 ELSE 0 END, deadline_date ASC')->get();
    }

    /**
     * Latest monthly check-ins in scope (null = whole resort), with each one's learning
     * status — shared with MasterDashboardController::hod_dashboard().
     */
    public static function monthlyCheckins(int $resortId, ?array $deptIds, int $limit = 5): Collection
    {
        return \App\Models\MonthlyCheckingModel::join('employees as t1', 't1.id', '=', 'monthly_checking_models.emp_id')
            ->join('resort_admins as t2', 't2.id', '=', 't1.Admin_Parent_id')
            ->join('resort_positions as t3', 't3.id', '=', 't1.Position_id')
            ->leftJoin('learning_programs as t4', 't4.id', '=', 'monthly_checking_models.tranining_id')
            ->where('t1.resort_id', $resortId)
            ->when($deptIds !== null, fn ($q) => $q->whereIn('t1.Dept_id', $deptIds))
            ->select(['t1.id as emp_orignal_id', 't1.Admin_Parent_id', 't4.name as traniningname', 't2.first_name', 't2.last_name',
                't3.position_title as PositionName', 'monthly_checking_models.*'])
            ->orderByDesc('monthly_checking_models.created_at')->limit($limit)->get()
            ->map(function ($ak) use ($resortId) {
                $l = $ak->tranining_id ? \App\Models\LearningRequest::where('learning_id', $ak->tranining_id)->where('resort_id', $resortId)
                    ->whereHas('employees', fn ($q) => $q->where('id', $ak->emp_orignal_id))->latest('id')->first() : null;
                $ak->duration = isset($l->start_date, $l->end_date) ? $l->start_date . '-' . $l->end_date : '-';
                $ak->status = $l ? ($l->status === 'Approved' ? 'In Progress' : $l->status) : 'Pending';
                $ak->new_date_of_dicussion = date('d M Y', strtotime($ak->date_discussion));
                return $ak;
            });
    }

    /**
     * Duty roster for one date: who is rostered (day-offs left out), their shift as
     * decimal hours, and whether they checked in / missed it / are on leave.
     * state 'due' = rostered but the shift hasn't started yet (not part of the original
     * contract: neither checked in nor missed).
     */
    public function roster(string $date): array
    {
        $day = Carbon::parse($date)->startOfDay();
        $now = Carbon::now();
        $entries = DB::table('duty_roster_entries as d')
            ->join('employees as e', 'e.id', '=', 'd.Emp_id')->join('resort_admins as a', 'a.id', '=', 'e.Admin_Parent_id')
            ->leftJoin('resort_positions as p', 'p.id', '=', 'e.Position_id')
            ->leftJoin('shift_settings as s', 's.id', '=', 'd.Shift_id')
            ->where('d.resort_id', $this->resortId)->whereDate('d.date', $day)->whereIn('d.Emp_id', $this->employeeIds())
            ->where(fn ($q) => $q->whereNull('d.Status')->orWhere('d.Status', '!=', 'DayOff'))
            ->get(['d.Emp_id', 'd.Status', 'a.id as admin_id', 'a.first_name', 'a.last_name', 'p.position_title', 's.ShiftName', 's.StartTime', 's.EndTime']);

        $punches = DB::table('parent_attendaces')->where('resort_id', $this->resortId)->whereDate('date', $day)
            ->whereIn('Emp_id', $entries->pluck('Emp_id'))->whereNotNull('CheckingTime')->pluck('CheckingTime', 'Emp_id');
        $onLeave = DB::table('employees_leaves')->where('resort_id', $this->resortId)->where('status', 'Approved')
            ->whereIn('emp_id', $entries->pluck('Emp_id'))->whereDate('from_date', '<=', $day)->whereDate('to_date', '>=', $day)
            ->pluck('emp_id')->flip();
        $hours = function (?string $t): ?float {
            if (!$t || !preg_match('/^(\d{1,2}):(\d{2})/', $t, $m)) return null;
            return min(24.0, (int) $m[1] + (int) $m[2] / 60);
        };

        $rows = [];
        foreach ($entries as $e) {
            $start = $hours($e->StartTime) ?? 0.0;
            $end = $hours($e->EndTime) ?? 24.0;
            if ($end <= $start) {
                $end = 24.0; // overnight: the part inside this day
            }
            $span = ($e->ShiftName ?: 'Shift') . ' ' . substr((string) $e->StartTime, 0, 5) . '–' . substr((string) $e->EndTime, 0, 5);
            $shiftStart = $day->copy()->addMinutes((int) round($start * 60));
            if (isset($onLeave[$e->Emp_id]) || in_array($e->Status, self::LEAVE_STATES, true)) {
                [$state, $tip] = ['lv', 'On leave · ' . $span];
            } elseif (isset($punches[$e->Emp_id])) {
                [$state, $tip] = ['in', 'Checked in ' . substr((string) $punches[$e->Emp_id], 0, 5) . ' · ' . $span];
            } elseif ($now->gte($shiftStart)) {
                [$state, $tip] = ['miss', 'No check-in · ' . $span];
            } else {
                [$state, $tip] = ['due', 'Starts ' . substr((string) $e->StartTime, 0, 5) . ' · ' . $span];
            }
            $rows[] = [
                'name' => trim($e->first_name . ' ' . $e->last_name),
                'position' => $e->position_title ?? '',
                'photo' => Common::getResortUserPicture($e->admin_id),
                'shift' => $e->ShiftName ?: 'Shift',
                'start' => round($start, 2),
                'end' => round($end, 2),
                'state' => $state,
                'tip' => $tip,
            ];
        }
        usort($rows, fn ($a, $b) => [$a['start'], $a['name']] <=> [$b['start'], $b['name']]);
        return $rows;
    }
}
