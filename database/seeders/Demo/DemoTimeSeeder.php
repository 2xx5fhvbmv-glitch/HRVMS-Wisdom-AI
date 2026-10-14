<?php

namespace Database\Seeders\Demo;

use App\Helpers\Common;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Demo ENV slice 3: duty rosters and 12 months of attendance.
 *
 * Rows are written in the shapes the app itself writes them:
 * - duty_rosters + duty_roster_entries = DutyRosterController@StoreDutyRoster (one roster per employee per
 *   week, one entry per day, 'Present' or 'DayOff'; a week without a day off earns a day-off credit).
 * - parent_attendaces + child_attendaces = mobile manualCheckIn/manualCheckOut ('H:i:s' punches, Status
 *   always 'Present' — On-Time/Late is derived from the shift start at read time; a no-show has no row).
 * - break_attendaces = mobile break flags: Break_OutTime is the break START, Break_InTime the END ('' while open).
 * - Overtime is written to both places the app reads it: employee_overtimes (T&A overtime screens) and
 *   parent_attendaces.OverTime/OTStatus (payroll). Older OT is decided, the last week's is pending.
 */
class DemoTimeSeeder
{
    const OFFICE_DEPTS = ['Executive Office', 'Human Resources', 'Finance'];
    const OT_RANKS = [5, 6]; // supervisors and line staff (entitled_overtime = yes)

    private Carbon $now;
    private Carbon $today;
    private object $site;          // the "Resort Island" geofence every roster is assigned
    private array $siteCentre;     // [lat, lng] inside it
    private array $counts = ['rosters' => 0, 'roster days' => 0, 'attendance days' => 0, 'breaks' => 0, 'overtime entries' => 0];

    public function __construct(private int $resortId, private int $adminId)
    {
    }

    public function seed(): array
    {
        $this->now = Carbon::now();
        $this->today = Carbon::today();
        $shifts = DB::table('shift_settings')->where('resort_id', $this->resortId)->get()->keyBy('ShiftName');
        $office = $shifts['Office Shift'];
        $rotation = [$shifts['Morning Shift'], $shifts['Afternoon Shift'], $shifts['Evening Shift']];
        $this->site = DB::table('resort_geofences')->where('resort_id', $this->resortId)->where('name', 'Resort Island')->first();
        $points = json_decode($this->site->coordinates, true);
        $this->siteCentre = [array_sum(array_column($points, 'lat')) / count($points), array_sum(array_column($points, 'lng')) / count($points)];

        $depts = DB::table('resort_departments')->where('resort_id', $this->resortId)->pluck('name', 'id');
        $staff = DB::table('employees')->where('resort_id', $this->resortId)->where('status', 'Active')->whereNull('deleted_at')
            ->orderBy('id')->get(['id', 'Dept_id', 'rank', 'Admin_Parent_id', 'joining_date', 'entitled_overtime']);
        $gm = $staff->firstWhere('rank', 8);
        // Who approves a department's overtime / reviews its out-of-zone punches: its HOD, else its EXCOM, else the GM.
        $approvers = [];
        foreach ([1, 2] as $rank) {
            foreach ($staff->where('rank', $rank) as $e) {
                $approvers[$e->Dept_id] = $e;
            }
        }

        // From the first day of the oldest payroll run (DemoPayrollSeeder::RUNS months back).
        $from = $this->today->copy()->startOfMonth()->subMonths(DemoPayrollSeeder::RUNS)->startOfWeek();
        $until = $this->today->copy()->addWeek()->endOfWeek(); // this week and next are planned ahead
        foreach ($staff as $e) {
            $isOffice = in_array($depts[$e->Dept_id] ?? '', self::OFFICE_DEPTS, true) || !in_array((int) $e->rank, self::OT_RANKS, true);
            $approver = $approvers[$e->Dept_id] ?? $gm;
            if ($approver->id === $e->id) {
                $approver = $gm;
            }
            $this->employee($e, $isOffice ? [$office] : $rotation, $isOffice, $approver, $from, $until);
        }
        return $this->counts;
    }

    private function employee(object $e, array $shifts, bool $isOffice, object $approver, Carbon $from, Carbon $until): void
    {
        $joined = Carbon::parse($e->joining_date ?: $from);
        $otEntitled = $e->entitled_overtime === 'yes';
        $stamp = ['created_by' => $this->adminId, 'modified_by' => $this->adminId];
        $entries = $credits = $worked = [];

        for ($week = $from->copy()->max($joined->copy()->startOfWeek()); $week->lte($until); $week->addWeek()) {
            // Office staff keep one shift; operational staff rotate weekly.
            $shift = $shifts[(intdiv($week->timestamp, 604800) + $e->id) % count($shifts)];
            $end = $week->copy()->addDays(6);
            $created = $week->copy()->subDays(3)->setTime(11, 0);
            $rosterId = DB::table('duty_rosters')->insertGetId($stamp + [
                'resort_id' => $this->resortId, 'Shift_id' => $shift->id, 'Emp_id' => $e->id,
                'ShiftDate' => $week->format('m/d/Y') . ' - ' . $end->format('m/d/Y'), 'Year' => $week->format('Y'),
                'DayOfDate' => null, 'geofence_zone_id' => json_encode([(string) $this->site->id]),
                'created_at' => $created, 'updated_at' => $created,
            ]);
            $this->counts['rosters']++;

            // One day off a week (Friday for the office); now and then an operational week without one → a day-off credit.
            $dayOff = $isOffice ? 4 : ($this->chance(0.04) ? null : mt_rand(0, 6));
            if ($dayOff === null) {
                $credits[] = ['resort_id' => $this->resortId, 'emp_id' => $e->id, 'week_start_date' => $week->format('Y-m-d'),
                    'roster_id' => $rosterId, 'created_at' => $created, 'updated_at' => $created];
            }
            [$h, $m] = array_map('intval', explode(':', $shift->TotalHours));
            for ($i = 0; $i < 7; $i++) {
                $day = $week->copy()->addDays($i);
                if ($day->lt($joined)) {
                    continue;
                }
                $off = $i === $dayOff;
                // A few future shifts carry pre-planned overtime for the managers to look at.
                $planned = !$off && $otEntitled && $day->gt($this->today) && $this->chance(0.05);
                $entries[] = $stamp + [
                    'roster_id' => $rosterId, 'resort_id' => $this->resortId, 'Shift_id' => $shift->id, 'Emp_id' => $e->id,
                    'date' => $day->format('Y-m-d'), 'Status' => $off ? 'DayOff' : 'Present', 'CheckingTime' => $shift->StartTime,
                    'CheckingOutTime' => $shift->EndTime, 'DayWiseTotalHours' => sprintf('%02d:%02d', $h, $m),
                    'OverTime' => $planned ? '02:00' : '00:00', 'created_at' => $created, 'updated_at' => $created,
                ];
                if (!$off && $day->lte($this->today)) {
                    $worked[] = [$day, $shift, $rosterId, $h * 60 + $m];
                }
            }
        }
        $this->insert('duty_roster_entries', $entries);
        $this->insert('employee_day_off_credits', $credits);
        $this->counts['roster days'] += count($entries);
        $this->punches($e, $worked, $otEntitled, $approver);
    }

    /** What actually happened on each rostered day up to now. */
    private function punches(object $e, array $worked, bool $otEntitled, object $approver): void
    {
        $approverAdmin = $approver->Admin_Parent_id ?: $this->adminId;
        $days = $children = [];
        foreach ($worked as [$day, $shift, $rosterId, $minutes]) {
            $start = Carbon::parse($day->format('Y-m-d') . ' ' . $shift->StartTime);
            if ($start->gt($this->now) || ($day->lt($this->today) && $this->chance(0.02))) {
                continue; // not started yet, or a no-show (the register shows Absent: rostered, no punch)
            }
            $shiftEnd = $start->copy()->addMinutes($minutes); // overnight shifts end the next day
            $in = $start->copy()->addMinutes($this->chance(0.07) ? mt_rand(12, 45) : mt_rand(-12, 8));
            $ot = $otEntitled && $this->chance(0.1) ? mt_rand(2, 10) * 15 : 0;
            $out = $shiftEnd->copy()->addMinutes($ot ?: mt_rand(-3, 12));
            $onShift = $out->gt($this->now);
            $forgotOut = !$onShift && $day->gte($this->today->copy()->subDays(10)) && $this->chance(0.015);
            $closed = !$onShift && !$forgotOut;

            $break = null;
            if ($minutes >= 300 && !$this->chance(0.04)) { // a few shifts skip the mandatory break
                $bStart = $in->copy()->addMinutes(intdiv($minutes, 2) - 30 + mt_rand(-6, 6) * 5);
                $bEnd = $bStart->copy()->addMinutes(mt_rand(6, 12) * 5);
                if ($bStart->lte($this->now)) {
                    $break = [$bStart, $bEnd->gt($this->now) ? null : $bEnd];
                }
            }
            $breakMinutes = $break && $break[1] ? (int) $break[0]->diffInMinutes($break[1]) : 0;

            [$inLat, $inLng] = $this->chance(0.03) ? $this->offSite() : $this->onSite();
            [$outLat, $outLng] = $this->onSite();
            $inWithin = Common::isWithinGeofence($inLat, $inLng, $this->site);
            $outsideZone = $inWithin === false; // flagged for the HOD's review, as Common::flagGeofenceReviewIfNeeded does
            $decided = $day->lt($this->today->copy()->subDays(7));
            $otStatus = $ot && $closed ? ($decided ? ($this->chance(0.85) ? 'approved' : 'rejected') : 'pending') : null;

            $date = $day->format('Y-m-d');
            $days[$date] = [
                'roster_id' => $rosterId, 'resort_id' => $this->resortId, 'Shift_id' => $shift->id, 'Emp_id' => $e->id,
                'date' => $date, 'Status' => 'Present', 'CheckInCheckOut_Type' => 'Geofencing',
                'CheckingTime' => $in->format('H:i:s'), 'CheckingOutTime' => $closed ? $out->format('H:i:s') : null,
                'DayWiseTotalHours' => $closed ? $this->hm((int) $in->diffInMinutes($out) - $breakMinutes) : '00:00',
                'OverTime' => $otStatus && $otStatus !== 'pending' ? $this->hm($ot) : null, // as Common::syncAttendanceOvertime leaves it
                'OTStatus' => $otStatus && $otStatus !== 'pending' ? ucfirst($otStatus) : null,
                'OTApproved_By' => $otStatus && $otStatus !== 'pending' ? $approverAdmin : null,
                'geofence_review_status' => $outsideZone ? ($day->lt($this->today->copy()->subDays(14)) ? 'confirmed' : 'pending') : null,
                'geofence_reviewed_by' => $outsideZone && $day->lt($this->today->copy()->subDays(14)) ? $approver->id : null,
                'created_by' => $e->Admin_Parent_id, 'modified_by' => $e->Admin_Parent_id,
                'created_at' => $in, 'updated_at' => $closed ? $out : $in,
            ];
            $children[$date] = compact('in', 'out', 'closed', 'inLat', 'inLng', 'inWithin', 'outLat', 'outLng', 'break', 'breakMinutes',
                'ot', 'otStatus', 'shiftEnd', 'rosterId', 'shift', 'day');
        }
        $this->insert('parent_attendaces', array_values($days));
        $this->counts['attendance days'] += count($days);

        $ids = DB::table('parent_attendaces')->where('Emp_id', $e->id)->pluck('id', 'date');
        $punches = $breaks = $overtime = [];
        foreach ($children as $date => $c) {
            $parentId = $ids[$date];
            $inUrl = $this->mapUrl($c['inLat'], $c['inLng']);
            $outUrl = $this->mapUrl($c['outLat'], $c['outLng']);
            $punches[] = [
                'Parent_attd_id' => $parentId, 'InTime_out' => $c['in']->format('H:i:s'), 'OutTime_out' => $c['closed'] ? $c['out']->format('H:i:s') : '00:00',
                'InTime_Location' => $inUrl, 'InTime_Latitude' => $c['inLat'], 'InTime_Longitude' => $c['inLng'],
                'InTime_Accuracy' => mt_rand(400, 2500) / 100, 'InTime_WithinGeofence' => $c['inWithin'],
                'InTime_GeofenceName' => $this->site->name,
                'OutTime_Location' => $c['closed'] ? $outUrl : '', 'OutTime_Latitude' => $c['closed'] ? $c['outLat'] : null,
                'OutTime_Longitude' => $c['closed'] ? $c['outLng'] : null, 'OutTime_Accuracy' => $c['closed'] ? mt_rand(400, 2500) / 100 : null,
                'OutTime_WithinGeofence' => $c['closed'] ? Common::isWithinGeofence($c['outLat'], $c['outLng'], $this->site) : null,
                'OutTime_GeofenceName' => $c['closed'] ? $this->site->name : null,
                'created_at' => $c['in'], 'updated_at' => $c['closed'] ? $c['out'] : $c['in'],
            ];
            if ($c['break']) {
                [$bStart, $bEnd] = $c['break'];
                $breaks[] = [
                    'Parent_attd_id' => $parentId, 'Break_OutTime' => $bStart->format('H:i'), 'Break_InTime' => $bEnd ? $bEnd->format('H:i') : '',
                    'Total_Break_Time' => $bEnd ? $this->hm($c['breakMinutes']) : '', 'OutTime_Location' => $outUrl, 'InTime_Location' => $bEnd ? $outUrl : '',
                    'created_at' => $bStart, 'updated_at' => $bEnd ?? $bStart,
                ];
            }
            if ($c['otStatus']) {
                $decidedAt = $c['day']->copy()->addDays(mt_rand(1, 3))->setTime(mt_rand(9, 16), mt_rand(0, 59));
                $overtime[] = [
                    'resort_id' => $this->resortId, 'Emp_id' => $e->id, 'Shift_id' => $c['shift']->id, 'roster_id' => $c['rosterId'],
                    'parent_attendance_id' => $parentId, 'date' => $date, 'start_time' => $c['shiftEnd']->format('H:i'),
                    'end_time' => $c['out']->format('H:i'), 'total_time' => $this->hm($c['ot']), 'status' => $c['otStatus'],
                    'approved_by' => $c['otStatus'] === 'approved' ? $approverAdmin : null,
                    'approved_at' => $c['otStatus'] === 'approved' ? $decidedAt : null,
                    'rejection_reason' => $c['otStatus'] === 'rejected' ? 'Overtime was not pre-approved by the department.' : null,
                    'end_location' => $outUrl, 'overtime_type' => 'after_shift', 'created_by' => $e->Admin_Parent_id, 'modified_by' => $e->Admin_Parent_id,
                    'created_at' => $c['out'], 'updated_at' => $c['otStatus'] === 'pending' ? $c['out'] : $decidedAt,
                ];
            }
        }
        $this->insert('child_attendaces', $punches);
        $this->insert('break_attendaces', $breaks);
        $this->insert('employee_overtimes', $overtime);
        $this->counts['breaks'] += count($breaks);
        $this->counts['overtime entries'] += count($overtime);
    }

    /** A point a few metres around the middle of the resort island zone. */
    private function onSite(): array
    {
        return [round($this->siteCentre[0] + mt_rand(-20, 20) / 100000, 7), round($this->siteCentre[1] + mt_rand(-20, 20) / 100000, 7)];
    }

    /** About 400 m away — a punch the HOD has to review. */
    private function offSite(): array
    {
        return [round($this->siteCentre[0] - 0.0025 + mt_rand(-30, 30) / 100000, 7), round($this->siteCentre[1] + 0.003 + mt_rand(-30, 30) / 100000, 7)];
    }

    /**
     * The phone sends a Google Maps embed URL; the app reads the coordinates back out of `center=`.
     * The keyless `output=embed` form still renders in the Location History iframe.
     */
    private function mapUrl(float $lat, float $lng): string
    {
        return "https://maps.google.com/maps?q={$lat},{$lng}&z=17&output=embed&center={$lat},{$lng}";
    }

    private function hm(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    private function insert(string $table, array $rows): void
    {
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }

    private function chance(float $p): bool
    {
        return mt_rand() / mt_getrandmax() < $p;
    }
}
