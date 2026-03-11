<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Employee;
use App\Models\Resort;
use App\Models\ResortAdmin;
use App\Models\ShiftSettings;
use App\Models\ResortPosition;
use App\Models\ResortDepartment;
use App\Models\ParentAttendace;
use App\Models\ChildAttendace;
use App\Models\BreakAttendaces;
use App\Models\EmployeeLeave;
use App\Models\BreakNotification;
use App\Models\DutyRoster;
use App\Models\DutyRosterEntry;
use App\Models\EmployeeOvertime;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use App\Helpers\Common;
use Carbon\Carbon;
use Validator;
use File;
use Auth;
use DB;

class TimeAndAttendanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function timeAttendanceDashboard()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $user                                           =   Auth::guard('api')->user();
            $employee                                       =   $user->GetEmployee;
            $emp_id                                         =   $employee->id;
            $resort_id                                      =   $user->resort_id;

            $startOfMonth                                   =   Carbon::now()->startOfMonth();
            $endOfMonth                                     =   Carbon::now()->endOfMonth();
            $today                                          =   Carbon::now()->format('Y-m-d');
            $dates                                          =   [];
            $totalOvertime                                  =   0;

            for ($date = $startOfMonth; $date->lte($endOfMonth); $date->addDay()) {

                $formattedDate                              =   $date->format('Y-m-d'); // Keep formatted date separate
                // Present only when employee has real check-in (any date)
                $totalPresentEmployee                       =   Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                                                                    ->join('duty_rosters as t2', "t2.Emp_id", "=", "employees.id")
                                                                    ->join('parent_attendaces as t3', "t3.roster_id", "=", "t2.id")
                                                                    ->where('t3.Status', 'Present')
                                                                    ->whereNotNull('t3.CheckingTime')
                                                                    ->whereRaw("TRIM(COALESCE(t3.CheckingTime,'')) NOT IN ('','0','00:00','00:00:00')")
                                                                    ->where('t3.date', $formattedDate)
                                                                    ->where("t1.resort_id", $resort_id)
                                                                    ->where('employees.id', $emp_id)
                                                                    ->count();

                $totalPresentEmployee                       =   $totalPresentEmployee ?? 0;
                $totalAbsentEmployee                        =   Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                                                                    ->join('duty_rosters as t2', "t2.Emp_id", "=", "employees.id")
                                                                    ->join('parent_attendaces as t3', "t3.roster_id", "=", "t2.id")
                                                                    ->where("t3.date", $formattedDate)
                                                                    ->where("t3.Status", "Absent") // Ensure 'Absent' is a string
                                                                    ->whereNull('t3.CheckingTime')
                                                                    ->whereNull('t3.CheckingOutTime')
                                                                    ->where("t1.resort_id", $resort_id)
                                                                    ->where('employees.id', $emp_id)
                                                                    ->count();

                $totalAbsentEmployee                        =   $totalAbsentEmployee ?? 0;
                $totalLeaveEmployee                         =   Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                                                                    ->join('employees_leaves as t2', "t2.emp_id", "=", "employees.id")
                                                                    ->where('t2.from_date', "<=", $formattedDate) // Date should be within leave range
                                                                    ->where('t2.to_date', ">=", $formattedDate)
                                                                    ->where("t1.resort_id", $resort_id)
                                                                    ->where('employees.id', $emp_id)
                                                                    ->count();

                $totalLeaveEmployee                         =   $totalLeaveEmployee ?? 0;
                $overtimeData1                              =   Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                                                                ->join('duty_rosters as t2', "t2.Emp_id", "=", "employees.id")
                                                                ->join('parent_attendaces as pa', "pa.roster_id", "=", "t2.id")
                                                                ->join('shift_settings as ss', 'pa.Shift_id', '=', 'ss.id')
                                                                ->where('ss.resort_id', '=', $resort_id)
                                                                ->whereNotNull('pa.CheckingTime')
                                                                ->whereNotIn('pa.Status', ['Absent', 'DayOff'])
                                                                ->whereNotNull('pa.CheckingOutTime')
                                                                ->where('employees.id', $emp_id)
                                                                ->whereDate('pa.date', $formattedDate)
                                                                ->sum(DB::raw("TIME_TO_SEC(pa.OverTime) / 3600"));

                $totalOvertime                              +=  $overtimeData1;

                $dates[$formattedDate]                      =   [
                    'present'                               =>  $totalPresentEmployee,
                    'absent'                                =>  $totalAbsentEmployee,
                    'leave'                                 =>  $totalLeaveEmployee,
                    'overtime'                              =>  $overtimeData1,
                ];
            }

            $totalPresentDays                               =   array_sum(array_column($dates, 'present'));
            $totalAbsentDays                                =   array_sum(array_column($dates, 'absent'));
            $totalLeaveDays                                 =   array_sum(array_column($dates, 'leave'));
            $totalOvertime                                  =   $totalOvertime;

            $timeAttendanceData['total_present_days']       =   $totalPresentDays;
            $timeAttendanceData['total_absent_days']        =   $totalAbsentDays;
            $timeAttendanceData['total_leave_days']         =   $totalLeaveDays;
            $timeAttendanceData['total_overtime']           =   $totalOvertime;

            $todayAttendance                                =   Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                                                                    ->join('duty_rosters as t2', "t2.Emp_id", "=", "employees.id")
                                                                    ->join('parent_attendaces as t3', "t3.roster_id", "=", "t2.id")
                                                                    ->join('shift_settings as ss', 'ss.id', '=', 't3.Shift_id')
                                                                    ->where('t3.date', $today) // Use the formatted date
                                                                    ->where("t1.resort_id", $resort_id)
                                                                    ->where('employees.id', $emp_id)
                                                                    ->first();

            if (!empty($todayAttendance) && isset($todayAttendance['CheckingTime'])) {

                $startTime                                  =   Carbon::parse($todayAttendance['StartTime']);
                $checkInTime                                =   Carbon::parse($todayAttendance['CheckingTime']);
                $difference                                 =   $startTime->diffInMinutes($checkInTime, false);

                if ($difference <= 10 && $difference >= 0) {
                    $color                                  =   Common::GetThemeColor('On Time');
                    $attendanceStatus                       =   'On Time';
                } elseif ($difference > 10) {
                    $color                                  =   Common::GetThemeColor('Late');
                    $attendanceStatus                       =   'Late';
                } else {
                    $attendanceStatus                       =   'Early';
                }
            } else {
                $attendanceStatus                           =   "No Check-in";
            }

            $formattedCheckInTime                           =   !empty($todayAttendance['CheckingTime']) ? date("h:i A", strtotime($todayAttendance['CheckingTime'])) : '';
            $formattedCheckOutTime                          =   !empty($todayAttendance['CheckingOutTime']) ? date("h:i A", strtotime($todayAttendance['CheckingOutTime'])) : '';
            $WeekstartDate                                  =   Carbon::now()->startOfWeek();
            $WeekendDate                                    =   Carbon::now()->endOfWeek();

            $Rosterdata                                     =   Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                                                                    ->join('resort_positions as t2', "t2.id", "=", "employees.Position_id")
                                                                    ->join('duty_rosters as t3', "t3.Emp_id", "=", "employees.id")
                                                                    ->join('parent_attendaces as t4', "t4.roster_id", "=", "t3.id")
                                                                    ->select('t3.id as duty_roster_id', 't3.DayOfDate', 't1.id as Parentid', 't1.first_name', 't1.last_name', 't1.profile_picture', 'employees.id as emp_id', 't2.position_title')
                                                                    ->where('employees.id', $emp_id)
                                                                    ->get();


            // Data structure to hold the processed weekly data
            $weeklyData                                     =    [];

            // Loop through each day of the week
            for ($date = $WeekstartDate; $date->lte($WeekendDate); $date->addDay()) {
                $formattedDate                              =   $date->format('Y-m-d');  // The actual date (2025-01-27, etc.)
                $dayOfWeek                                  =    $date->format('D');         // Get day name (Mon, Tue, etc.)

                // Find the matching roster for the current day
                $matchingRoster                             =    $Rosterdata->where('DayOfDate', '!=', $dayOfWeek)->first(); // Get matching roster for the day

                if ($matchingRoster) {
                    // If data is found for the day, add it to the weekly data
                    $RosterInternalData                     =   Common::GetRosterdata($user->resort_id, $matchingRoster->duty_roster_id, $matchingRoster->emp_id, $WeekstartDate, $WeekendDate, '', '', 'weekly');

                    // Instead of adding every shift, ensure only one shift for that day
                    if ($RosterInternalData->isNotEmpty()) {
                        $RosterInternal                     =   $RosterInternalData->first(); // Get the first shift for the day

                        // Only add if shift data exists for this date
                        $weeklyData[]                       = [
                            'Attd_id'                       =>  $RosterInternal->Attd_id,
                            'date'                          =>  $formattedDate,
                            'Shift_id'                      =>  $RosterInternal->Shift_id,
                            'DayOfDate'                     =>  $dayOfWeek,
                            'ShiftName'                     =>  $RosterInternal->ShiftName,
                            'OverTime'                      =>  $RosterInternal->OverTime ?? null,
                            'StartTime'                     =>  $RosterInternal->StartTime,
                            'EndTime'                       =>  $RosterInternal->EndTime,
                            'DayWiseTotalHours'             =>  $RosterInternal->DayWiseTotalHours
                        ];
                    } else {
                        // If no shift data exists, show "No Shift Assigned"
                        $weeklyData[]                       = [
                            'date'                          => $formattedDate,
                            'DayOfDate'                     => $dayOfWeek,
                            'ShiftName'                     => "No Shift Assigned", // No shift data
                            'Shift_id'                      => null,
                            'OverTime'                      => null,
                            'StartTime'                     => null,
                            'EndTime'                       => null,
                            'DayWiseTotalHours'             => null
                        ];
                    }
                } else {
                    // If no matching roster entry for the day, assign "Day Off"
                    $weeklyData[] = [
                        'date'                              =>  $formattedDate,
                        'DayOfDate'                         =>  $dayOfWeek,
                        'ShiftName'                         =>  "Day Off",  // Day off when no shift is found
                        'Shift_id'                          =>  null,
                        'OverTime'                          =>  null,
                        'StartTime'                         =>  null,
                        'EndTime'                           =>  null,
                        'DayWiseTotalHours'                 =>  null
                    ];
                }
            }


            $timeAttendanceData['today_attendance']         =   array(
                'check_in_time'                             =>  $formattedCheckInTime,
                'check_out_time'                            =>  $formattedCheckOutTime,
                'attendance_status'                         =>  $attendanceStatus,
                'color'                                     =>  $color ?? '',
            );

            $timeAttendanceData['weekly_duty_roster'] = $weeklyData;

            $response['status']                             =   true;
            $response['message']                            =   'Time TimeAttendance Dashboard';
            $response['time_attendance']                    =   $timeAttendanceData;

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function resortBaseShift()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $user                                               =   Auth::guard('api')->user();
        $employee                                           =   $user->GetEmployee;

        try {

            $shiftData                                      =   ShiftSettings::where('resort_id', $user->resort_id)->get();
            $response['status']                             =   true;
            $response['message']                            =   'Shift Data';
            $response['shift_data']                         =   $shiftData;

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * POST employee duty roster: returns the logged-in employee's scheduled roster (from duty_roster_entries).
     * Body: filter = weekly | monthly (optional, default weekly); year, month (optional, for monthly).
     */
    public function employeeDutyRoster(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $user                                               =   Auth::guard('api')->user();
        $employee                                           =   $user->GetEmployee;
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee record not found.'], 403);
        }

        $filter                                             =   $request->input('filter', 'weekly');
        $resort_id                                          =   $user->resort_id;
        $emp_id                                             =   $employee->id;

        try {
            $WeekstartDate                                  =   Carbon::now(config('app.timezone'))->startOfWeek();
            $WeekendDate                                    =   Carbon::now(config('app.timezone'))->endOfWeek();
            $startOfMonth                                   =   Carbon::now(config('app.timezone'))->startOfMonth();
            $endOfMonth                                     =   Carbon::now(config('app.timezone'))->endOfMonth();
            $year                                           =   $request->input('year', $startOfMonth->format('Y'));
            $month                                          =   $request->input('month', $startOfMonth->format('m'));

            // Get roster_id from duty_roster_entries for this employee (schedule lives here, not in parent_attendaces)
            $rosterEntry                                    =   DutyRosterEntry::where('Emp_id', $emp_id)
                ->where('resort_id', $resort_id)
                ->when($filter === 'weekly', function ($q) use ($WeekstartDate, $WeekendDate) {
                    $q->whereBetween('date', [$WeekstartDate->toDateString(), $WeekendDate->toDateString()]);
                }, function ($q) use ($startOfMonth, $endOfMonth) {
                    $q->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()]);
                })
                ->orderBy('date')
                ->first();

            if (!$rosterEntry) {
                $rosterEntry                                =   DutyRosterEntry::where('Emp_id', $emp_id)
                    ->where('resort_id', $resort_id)
                    ->orderBy('date', 'desc')
                    ->first();
            }

            $timeAttendanceData                             =   [];
            if ($rosterEntry) {
                $duty_roster_id                             =   $rosterEntry->roster_id;
                if ($filter === 'weekly') {
                    $timeAttendanceData                    =   Common::GetRosterdata($resort_id, $duty_roster_id, $emp_id, $WeekstartDate, $WeekendDate, $startOfMonth, $endOfMonth, 'weekly');
                } else {
                    $timeAttendanceData                    =   Common::GetRosterdata($resort_id, $duty_roster_id, $emp_id, $WeekstartDate, $WeekendDate, $startOfMonth, $endOfMonth, 'Monthwise');
                }
                $timeAttendanceData                         =   $timeAttendanceData ? (is_array($timeAttendanceData) ? $timeAttendanceData : $timeAttendanceData->toArray()) : [];
                $todayStr                                  =   Carbon::today(config('app.timezone'))->format('Y-m-d');
                $datesInRoster                             =   array_values(array_unique(array_filter(array_map(function ($r) {
                    $d = $r['date'] ?? null;
                    return $d ? (is_object($d) ? $d->format('Y-m-d') : (string) $d) : null;
                }, $timeAttendanceData))));
                $checkIns                                  =   collect([]);
                if (!empty($datesInRoster)) {
                    $placeholders                         =   implode(',', array_fill(0, count($datesInRoster), '?'));
                    $checkIns                              =   ParentAttendace::where('resort_id', $resort_id)
                        ->where('Emp_id', $emp_id)
                        ->whereRaw("DATE(date) IN ($placeholders)", $datesInRoster)
                        ->whereIn('Status', ['Present', 'On-Time', 'Late'])
                        ->whereNotNull('CheckingTime')
                        ->whereRaw("TRIM(COALESCE(CheckingTime,'')) NOT IN ('','0','00:00','00:00:00')")
                        ->get()
                        ->keyBy(function ($pa) { return $pa->date ? \Carbon\Carbon::parse($pa->date)->format('Y-m-d') : ''; });
                }
                $minDate                                  =   !empty($datesInRoster) ? min($datesInRoster) : null;
                $maxDate                                  =   !empty($datesInRoster) ? max($datesInRoster) : null;
                $leavesByDate                              =   [];
                if ($minDate && $maxDate) {
                    $leaveRecords                          =   EmployeeLeave::where('employees_leaves.resort_id', $resort_id)
                        ->where('employees_leaves.emp_id', $emp_id)
                        ->where('employees_leaves.status', 'Approved')
                        ->where('employees_leaves.from_date', '<=', $maxDate)
                        ->where('employees_leaves.to_date', '>=', $minDate)
                        ->join('leave_categories as lc', 'lc.id', '=', 'employees_leaves.leave_category_id')
                        ->get(['employees_leaves.from_date', 'employees_leaves.to_date', 'lc.leave_type']);
                    foreach ($leaveRecords as $lev) {
                        if (empty($lev->from_date) || empty($lev->to_date)) {
                            continue;
                        }
                        try {
                            $from                          =   \Carbon\Carbon::parse($lev->from_date)->format('Y-m-d');
                            $to                            =   \Carbon\Carbon::parse($lev->to_date)->format('Y-m-d');
                            if ($from > $to) {
                                continue;
                            }
                            $d                             =   $from;
                            $maxIterations                  =   366;
                            $iterations                     =   0;
                            while ($d <= $to && $iterations < $maxIterations) {
                                if ($d >= $minDate && $d <= $maxDate) {
                                    $leavesByDate[$d]      =   $lev->leave_type ?? 'On Leave';
                                }
                                $d                          =   \Carbon\Carbon::parse($d)->addDay()->format('Y-m-d');
                                $iterations++;
                            }
                        } catch (\Exception $e) {
                            continue;
                        }
                    }
                }
                foreach ($timeAttendanceData as &$row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    $rowDate                               =   $row['date'] ?? null;
                    if ($rowDate === null || $rowDate === '') {
                        continue;
                    }
                    $dateKey                               =   is_object($rowDate) && method_exists($rowDate, 'format') ? $rowDate->format('Y-m-d') : (string) $rowDate;
                    $dateKey                               =   trim($dateKey);
                    if ($dateKey === '') {
                        continue;
                    }
                    $originalStatus                        =   $row['Status'] ?? null;
                    $hasCheckIn                            =   $checkIns->get($dateKey);
                    if ($hasCheckIn && $dateKey <= $todayStr) {
                        $row['Status']                    =   $hasCheckIn->Status ?? 'Present';
                        $row['CheckingTime']             =   $hasCheckIn->CheckingTime ?? null;
                        $row['CheckingOutTime']          =   $hasCheckIn->CheckingOutTime ?? null;
                        $row['LeaveType']                =   'Present';
                    } else {
                        $row['Status']                    =   'Scheduled';
                        $row['CheckingTime']              =   null;
                        $row['CheckingOutTime']           =   null;
                        if (isset($leavesByDate[$dateKey])) {
                            $row['LeaveType']            =   $leavesByDate[$dateKey];
                        } elseif (in_array(strtoupper(trim((string) $originalStatus)), ['DAYOFF', 'DAY OFF'], true)) {
                            $row['LeaveType']            =   'Day Off';
                        } else {
                            $row['LeaveType']            =   'Scheduled';
                        }
                    }
                    $lt                               =   $row['LeaveType'] ?? '';
                    $row['LeaveFirstName']           =   $lt !== '' ? mb_substr(trim($lt), 0, 1) : '-';
                    $dayHours                              =   $row['DayWiseTotalHours'] ?? null;
                    $startTime                             =   $row['StartTime'] ?? null;
                    $endTime                               =   $row['EndTime'] ?? null;
                    $invalidDayHours                       =   false;
                    if ($dayHours !== null && $dayHours !== '') {
                        $parts                             =   array_map('intval', explode(':', trim($dayHours)));
                        $h                                 =   $parts[0] ?? 0;
                        $m                                 =   $parts[1] ?? 0;
                        if ($h >= 24 || $h < 0 || $m < 0 || $m > 59) {
                            $invalidDayHours               =   true;
                        }
                    } else {
                        $invalidDayHours                   =   true;
                    }
                    if ($invalidDayHours && $startTime !== null && $endTime !== null && $startTime !== '' && $endTime !== '') {
                        $row['DayWiseTotalHours']         =   static::calculateShiftDuration($startTime, $endTime);
                    }
                }
                unset($row);
                $timeAttendanceData                         =   array_values($timeAttendanceData);
            }

            $response['status']                             =   true;
            $response['message']                            =   'Employee duty roster';
            $response['filter']                             =   $filter;
            $response['time_attendance']                    =   $timeAttendanceData;

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * GET employee attendance summary for dashboard cards.
     * HR dept HOD/EXCOM or HR/GM rank = whole resort; other departments = only their dept data.
     * Optional type=total|present|absent|on_leave, date=Y-m-d.
     */
    public function employeeAttendanceSummary(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $user                                               =   Auth::guard('api')->user();
        $employee                                           =   $user->GetEmployee;
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee record not found.'], 403);
        }

        $resort_id                                          =   $user->resort_id;
        $typeParam                                          =   $request->query('type');

        $rankConfig                                         =   config('settings.Position_Rank', []);
        $currentRankLabel                                   =   $rankConfig[$employee->rank ?? ''] ?? '';
        $hrDeptId                                           =   ResortDepartment::where('resort_id', $resort_id)
            ->where(function ($q) {
                $q->where('name', 'Human Resources')->orWhere('name', 'like', '%Human Resources%');
            })
            ->value('id');
        $isHRDepartmentHODOrExcom                           =   $hrDeptId && (int) $employee->Dept_id === (int) $hrDeptId && in_array(strtoupper($currentRankLabel), ['HOD', 'EXCOM'], true);
        $isHROrGMRank                                       =   in_array(strtoupper($currentRankLabel), ['HR', 'GM'], true);
        $scopeResortWide                                    =   $isHRDepartmentHODOrExcom || $isHROrGMRank;

        try {
            $dateParam                                       =   $request->query('date');
            $targetDate                                      =   $dateParam
                ? (($parsed = Carbon::createFromFormat('Y-m-d', $dateParam)) ? $parsed->format('Y-m-d') : Carbon::today(config('app.timezone'))->format('Y-m-d'))
                : Carbon::now(config('app.timezone'))->format('Y-m-d');
            $todayStr                                        =   Carbon::today(config('app.timezone'))->format('Y-m-d');
            $isFutureDate                                    =   $targetDate > $todayStr;

            $totalQuery                                      =   Employee::join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                ->where('t1.resort_id', $resort_id)
                ->where('employees.status', 'Active');
            if (!$scopeResortWide) {
                $totalQuery->where('employees.Dept_id', $employee->Dept_id);
            }
            $total_employees                                 =   (int) $totalQuery->distinct()->count('employees.id');

            $presentQuery                                   =   ParentAttendace::where('parent_attendaces.resort_id', $resort_id)
                ->where('parent_attendaces.date', '<=', $todayStr)
                ->whereDate('parent_attendaces.date', $targetDate)
                ->whereIn('parent_attendaces.Status', ['Present', 'On-Time', 'Late'])
                ->whereNotNull('parent_attendaces.CheckingTime')
                ->whereRaw("TRIM(COALESCE(parent_attendaces.CheckingTime,'')) NOT IN ('','0','00:00','00:00:00')")
                ->join('employees', 'employees.id', '=', 'parent_attendaces.Emp_id')
                ->join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                ->where('t1.resort_id', $resort_id);
            if (!$scopeResortWide) {
                $presentQuery->where('employees.Dept_id', $employee->Dept_id);
            }
            $present                                        =   $isFutureDate ? 0 : (int) $presentQuery->distinct()->count('employees.id');

            $absentQuery                                    =   ParentAttendace::where('parent_attendaces.resort_id', $resort_id)
                ->where('parent_attendaces.date', '<=', $todayStr)
                ->whereDate('parent_attendaces.date', $targetDate)
                ->whereIn('parent_attendaces.Status', ['Absent', 'Absant'])
                ->join('employees', 'employees.id', '=', 'parent_attendaces.Emp_id')
                ->join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                ->where('t1.resort_id', $resort_id);
            if (!$scopeResortWide) {
                $absentQuery->where('employees.Dept_id', $employee->Dept_id);
            }
            $absent                                         =   $isFutureDate ? 0 : (int) $absentQuery->distinct()->count('employees.id');

            $onLeaveQuery                                    =   EmployeeLeave::where('employees_leaves.resort_id', $resort_id)
                ->where('employees_leaves.status', 'Approved')
                ->where('employees_leaves.from_date', '<=', $targetDate)
                ->where('employees_leaves.to_date', '>=', $targetDate)
                ->join('employees', 'employees.id', '=', 'employees_leaves.emp_id')
                ->join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                ->where('t1.resort_id', $resort_id);
            if (!$scopeResortWide) {
                $onLeaveQuery->where('employees.Dept_id', $employee->Dept_id);
            }
            $on_leave                                       =   (int) $onLeaveQuery->distinct()->count('employees.id');

            $allowed                                        =   ['total', 'present', 'absent', 'on_leave'];
            if ($typeParam !== null && $typeParam !== '' && in_array($typeParam, $allowed, true)) {
                $count  =   $typeParam === 'total' ? $total_employees : ($typeParam === 'present' ? $present : ($typeParam === 'absent' ? $absent : $on_leave));
                return response()->json([
                    'status'  => true,
                    'message' => 'Attendance data for ' . $typeParam,
                    'date'    => $targetDate,
                    'type'    => $typeParam,
                    'scope'   => $scopeResortWide ? 'resort' : 'department',
                    'count'   => $count,
                ]);
            }

            return response()->json([
                'status'  => true,
                'message' => 'Employee attendance summary',
                'date'    => $targetDate,
                'scope'   => $scopeResortWide ? 'resort' : 'department',
                'summary' => [
                    'total_employees' => $total_employees,
                    'present'         => $present,
                    'absent'          => $absent,
                    'on_leave'        => $on_leave,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * GET list of employees for the clicked dashboard card.
     * HR dept HOD/EXCOM or HR/GM rank = whole resort; other departments = only their dept data.
     * Query: type = total | present | absent | on_leave; optional date=Y-m-d (default today).
     */
    public function employeeAttendanceSummaryList(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $user                                               =   Auth::guard('api')->user();
        $employee                                           =   $user->GetEmployee;
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee record not found.'], 403);
        }

        $type                                               =   $request->query('type', 'total');
        $allowed                                            =   ['total', 'present', 'absent', 'on_leave'];
        if (!in_array($type, $allowed, true)) {
            return response()->json(['success' => false, 'message' => 'Invalid type. Use: total, present, absent, on_leave.'], 400);
        }

        $resort_id                                          =   $user->resort_id;

        $rankConfig                                         =   config('settings.Position_Rank', []);
        $currentRankLabel                                   =   $rankConfig[$employee->rank ?? ''] ?? '';
        $hrDeptId                                           =   ResortDepartment::where('resort_id', $resort_id)
            ->where(function ($q) {
                $q->where('name', 'Human Resources')->orWhere('name', 'like', '%Human Resources%');
            })
            ->value('id');
        $isHRDepartmentHODOrExcom                           =   $hrDeptId && (int) $employee->Dept_id === (int) $hrDeptId && in_array(strtoupper($currentRankLabel), ['HOD', 'EXCOM'], true);
        $isHROrGMRank                                       =   in_array(strtoupper($currentRankLabel), ['HR', 'GM'], true);
        $scopeResortWide                                    =   $isHRDepartmentHODOrExcom || $isHROrGMRank;

        try {
            $dateParam                                       =   $request->query('date');
            $targetDate                                      =   $dateParam
                ? (($parsed = Carbon::createFromFormat('Y-m-d', $dateParam)) ? $parsed->format('Y-m-d') : Carbon::today(config('app.timezone'))->format('Y-m-d'))
                : Carbon::now(config('app.timezone'))->format('Y-m-d');
            $todayStr                                        =   Carbon::today(config('app.timezone'))->format('Y-m-d');
            $isFutureDate                                    =   $targetDate > $todayStr;
            $baseSelect                                     =   ['employees.id as emp_id', 't1.id as admin_id', 't1.first_name', 't1.last_name', 't1.profile_picture', 'rp.position_title'];

            if ($type === 'total') {
                $query                                      =   Employee::join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                    ->leftJoin('resort_positions as rp', 'rp.id', '=', 'employees.Position_id')
                    ->where('t1.resort_id', $resort_id)
                    ->where('employees.status', 'Active');
                if (!$scopeResortWide) {
                    $query->where('employees.Dept_id', $employee->Dept_id);
                }
                $list                                        =   $query->select($baseSelect)->distinct()->get();
            } elseif ($type === 'present') {
                if ($isFutureDate) {
                    $list                                    =   collect([]);
                } else {
                    $presentSelect                           =   array_merge($baseSelect, ['parent_attendaces.CheckingTime', 'parent_attendaces.CheckingOutTime']);
                    $query                                  =   ParentAttendace::where('parent_attendaces.resort_id', $resort_id)
                        ->where('parent_attendaces.date', '<=', $todayStr)
                        ->whereDate('parent_attendaces.date', $targetDate)
                        ->whereIn('parent_attendaces.Status', ['Present', 'On-Time', 'Late'])
                        ->whereNotNull('parent_attendaces.CheckingTime')
                        ->whereRaw("TRIM(COALESCE(parent_attendaces.CheckingTime,'')) NOT IN ('','0','00:00','00:00:00')")
                        ->join('employees', 'employees.id', '=', 'parent_attendaces.Emp_id')
                        ->join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                        ->leftJoin('resort_positions as rp', 'rp.id', '=', 'employees.Position_id')
                        ->where('t1.resort_id', $resort_id);
                    if (!$scopeResortWide) {
                        $query->where('employees.Dept_id', $employee->Dept_id);
                    }
                    $list                                    =   $query->select($presentSelect)->distinct()->get();
                }
            } elseif ($type === 'absent') {
                if ($isFutureDate) {
                    $list                                    =   collect([]);
                } else {
                    $query                                  =   ParentAttendace::where('parent_attendaces.resort_id', $resort_id)
                        ->where('parent_attendaces.date', '<=', $todayStr)
                        ->whereDate('parent_attendaces.date', $targetDate)
                        ->whereIn('parent_attendaces.Status', ['Absent', 'Absant'])
                        ->join('employees', 'employees.id', '=', 'parent_attendaces.Emp_id')
                        ->join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                        ->leftJoin('resort_positions as rp', 'rp.id', '=', 'employees.Position_id')
                        ->where('t1.resort_id', $resort_id);
                    if (!$scopeResortWide) {
                        $query->where('employees.Dept_id', $employee->Dept_id);
                    }
                    $list                                    =   $query->select($baseSelect)->distinct()->get();
                }
            } else {
                $query                                      =   EmployeeLeave::where('employees_leaves.resort_id', $resort_id)
                    ->where('employees_leaves.status', 'Approved')
                    ->where('employees_leaves.from_date', '<=', $targetDate)
                    ->where('employees_leaves.to_date', '>=', $targetDate)
                    ->join('employees', 'employees.id', '=', 'employees_leaves.emp_id')
                    ->join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                    ->leftJoin('resort_positions as rp', 'rp.id', '=', 'employees.Position_id')
                    ->leftJoin('leave_categories as lc', 'lc.id', '=', 'employees_leaves.leave_category_id')
                    ->where('t1.resort_id', $resort_id);
                if (!$scopeResortWide) {
                    $query->where('employees.Dept_id', $employee->Dept_id);
                }
                $list                                        =   $query->select(array_merge($baseSelect, ['employees_leaves.from_date', 'employees_leaves.to_date', 'lc.leave_type']))->distinct()->get();
            }

            $employees                                       =   $list->map(function ($row) use ($type) {
                $name   =   trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
                $item   =   [
                    'emp_id'          => $row->emp_id,
                    'admin_id'        => $row->admin_id ?? null,
                    'name'            => $name,
                    'profile_picture' => !empty($row->profile_picture) && $row->profile_picture !== '0' ? $row->profile_picture : Common::getResortUserPicture($row->admin_id ?? null),
                    'position_title'  => $row->position_title ?? null,
                ];
                if ($type === 'present') {
                    $item['check_in_time']  =   isset($row->CheckingTime) ? $row->CheckingTime : null;
                    $item['check_out_time'] =   isset($row->CheckingOutTime) ? $row->CheckingOutTime : null;
                }
                if ($type === 'on_leave' && isset($row->from_date)) {
                    $item['from_date']   =   $row->from_date;
                    $item['to_date']    =   $row->to_date ?? null;
                    $item['leave_type'] =   $row->leave_type ?? 'Leave';
                }
                return $item;
            })->values()->toArray();

            return response()->json([
                'status'    => true,
                'message'   => 'Employee list for ' . $type,
                'date'      => $targetDate,
                'type'      => $type,
                'scope'     => $scopeResortWide ? 'resort' : 'department',
                'employees' => $employees,
            ]);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function timeAttendanceHODDashboard(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $user                                               =   Auth::guard('api')->user();
        $employee                                           =   $user->GetEmployee;
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee record not found.'], 403);
        }

        $emp_id                                             =   $employee->id;
        $resort_id                                          =   $user->resort_id;
        $Rank                                               =   $employee->rank;
        $filter                                             =   $request->input('filter', 'weekly');

        $rankConfig                                         =   config('settings.Position_Rank', []);
        $currentRankLabel                                   =   $rankConfig[$employee->rank ?? ''] ?? '';
        $hrDeptId                                           =   ResortDepartment::where('resort_id', $resort_id)
            ->where(function ($q) {
                $q->where('name', 'Human Resources')->orWhere('name', 'like', '%Human Resources%');
            })
            ->value('id');
        $isHRDepartmentHODOrExcom                           =   $hrDeptId && (int) $employee->Dept_id === (int) $hrDeptId && in_array(strtoupper($currentRankLabel), ['HOD', 'EXCOM'], true);
        $isHROrGMRank                                       =   in_array(strtoupper($currentRankLabel), ['HR', 'GM'], true);

        $underEmp_id                                        =   Common::getSubordinates($emp_id);
        if ($isHRDepartmentHODOrExcom || $isHROrGMRank) {
            $underEmp_id                                    =   null;
        }
        if (empty($underEmp_id) && $underEmp_id !== null) {
            $underEmp_id                                    =   [$emp_id];
        }

        try {
            $today                                           =   Carbon::now(config('app.timezone'))->format('Y-m-d');
            if ($filter === 'weekly') {
                $startDate                                  =   Carbon::now()->startOfWeek();
                $endDate                                    =   Carbon::now()->endOfWeek();
            } else {
                $startDate                                  =   Carbon::now()->startOfMonth();
                $endDate                                    =   Carbon::now()->endOfMonth();
            }

            $empQuery                                        =   Employee::join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                ->where('t1.resort_id', $resort_id)
                ->where('employees.status', 'Active');
            if ($underEmp_id !== null) {
                $empQuery->whereIn('employees.id', $underEmp_id);
            }
            $total_employees                                 =   (int) $empQuery->distinct()->count('employees.id');

            // Today: present = parent_attendaces today with Status Present/On-Time/Late and real check-in in DB (distinct emp; no future)
            $presentQuery                                    =   ParentAttendace::where('parent_attendaces.resort_id', $resort_id)
                ->whereDate('parent_attendaces.date', $today)
                ->where('parent_attendaces.date', '<=', $today)
                ->whereIn('parent_attendaces.Status', ['Present', 'On-Time', 'Late'])
                ->whereNotNull('parent_attendaces.CheckingTime')
                ->whereRaw("TRIM(COALESCE(parent_attendaces.CheckingTime,'')) NOT IN ('','0','00:00','00:00:00')")
                ->join('employees', 'employees.id', '=', 'parent_attendaces.Emp_id')
                ->join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                ->where('t1.resort_id', $resort_id);
            if ($underEmp_id !== null) {
                $presentQuery->whereIn('employees.id', $underEmp_id);
            }
            $present                                         =   (int) $presentQuery->distinct()->count('employees.id');

            // Today: absent = parent_attendaces today with Status Absent
            $absentQuery                                     =   ParentAttendace::where('parent_attendaces.resort_id', $resort_id)
                ->whereDate('parent_attendaces.date', $today)
                ->whereIn('parent_attendaces.Status', ['Absent', 'Absant'])
                ->join('employees', 'employees.id', '=', 'parent_attendaces.Emp_id')
                ->join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                ->where('t1.resort_id', $resort_id);
            if ($underEmp_id !== null) {
                $absentQuery->whereIn('employees.id', $underEmp_id);
            }
            $absent                                          =   (int) $absentQuery->distinct()->count('employees.id');

            // Today: on_leave = employees with approved leave covering today
            $onLeaveQuery                                    =   EmployeeLeave::where('employees_leaves.resort_id', $resort_id)
                ->where('employees_leaves.status', 'Approved')
                ->where('employees_leaves.from_date', '<=', $today)
                ->where('employees_leaves.to_date', '>=', $today)
                ->join('employees', 'employees.id', '=', 'employees_leaves.emp_id')
                ->join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                ->where('t1.resort_id', $resort_id);
            if ($underEmp_id !== null) {
                $onLeaveQuery->whereIn('employees.id', $underEmp_id);
            }
            $on_leave                                        =   (int) $onLeaveQuery->distinct()->count('employees.id');

            // Approved OT hours (period) – total minutes then display as number
            $otApprovedQuery                                 =   ParentAttendace::where('parent_attendaces.resort_id', $resort_id)
                ->whereNotNull('parent_attendaces.OverTime')
                ->where('parent_attendaces.OTStatus', 'Approved')
                ->whereBetween('parent_attendaces.date', [$startDate->toDateString(), $endDate->toDateString()])
                ->join('employees', 'employees.id', '=', 'parent_attendaces.Emp_id')
                ->join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                ->where('t1.resort_id', $resort_id);
            if ($underEmp_id !== null) {
                $otApprovedQuery->whereIn('employees.id', $underEmp_id);
            }
            $otApprovedRows                                  =   $otApprovedQuery->select('parent_attendaces.OverTime')->get();
            $approvedOtMinutes                               =   $otApprovedRows->sum(function ($item) {
                if (empty($item->OverTime) || strpos($item->OverTime, ':') === false) {
                    return 0;
                }
                $parts = explode(':', $item->OverTime);
                return ((int)($parts[0] ?? 0) * 60) + (int)($parts[1] ?? 0);
            });
            $approved_ot_hours                               =   (int) round($approvedOtMinutes / 60);

            // Pending OT requests (period) – list for Figma "Pending Requests"
            $otReqQuery                                      =   ParentAttendace::where('parent_attendaces.resort_id', $resort_id)
                ->whereNotNull('parent_attendaces.OverTime')
                ->where(function ($q) {
                    $q->whereNull('parent_attendaces.OTStatus')->orWhereNotIn('parent_attendaces.OTStatus', ['Approved', 'Rejected']);
                })
                ->whereBetween('parent_attendaces.date', [$startDate->toDateString(), $endDate->toDateString()])
                ->join('employees', 'employees.id', '=', 'parent_attendaces.Emp_id')
                ->join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                ->where('t1.resort_id', $resort_id);
            if ($underEmp_id !== null) {
                $otReqQuery->whereIn('employees.id', $underEmp_id);
            }
            $otReqList                                       =   $otReqQuery->select(
                'parent_attendaces.id as attendance_id',
                'parent_attendaces.Emp_id as emp_id',
                'parent_attendaces.date',
                'parent_attendaces.OverTime',
                't1.first_name',
                't1.last_name',
                't1.id as admin_id'
            )->get();
            $ot_requests_count                               =   $otReqList->count();
            $ot_requests                                     =   $otReqList->map(function ($row) {
                $name   =   trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
                $date   =   $row->date ? \Carbon\Carbon::parse($row->date)->format('d M Y') : '';
                $hrs    =   $row->OverTime ? (explode(':', $row->OverTime)[0] ?? '0') : '0';
                $desc   =   $name ? ($name . ' Requested ot for ' . $date . ' - ' . $hrs . ' hrs') : ('OT for ' . $date . ' - ' . $hrs . ' hrs');
                return [
                    'attendance_id' => $row->attendance_id,
                    'emp_id'        => $row->emp_id,
                    'name'          => $name,
                    'profile_picture' => Common::getResortUserPicture($row->admin_id),
                    'description'   => $desc,
                    'date'          => $row->date,
                    'hours'         => $hrs,
                    'can_accept'    => true,
                    'can_reject'    => true,
                ];
            })->values()->toArray();

            // Pending leave requests
            $leaveReqQuery                                   =   EmployeeLeave::where('employees_leaves.resort_id', $resort_id)
                ->where('employees_leaves.status', 'Pending')
                ->join('employees', 'employees.id', '=', 'employees_leaves.emp_id')
                ->join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                ->leftJoin('leave_categories as lc', 'lc.id', '=', 'employees_leaves.leave_category_id')
                ->where('t1.resort_id', $resort_id);
            if ($underEmp_id !== null) {
                $leaveReqQuery->whereIn('employees.id', $underEmp_id);
            }
            $leaveReqList                                    =   $leaveReqQuery->select(
                'employees_leaves.id',
                'employees_leaves.emp_id',
                'employees_leaves.from_date',
                'employees_leaves.to_date',
                't1.first_name',
                't1.last_name',
                't1.id as admin_id',
                'lc.leave_type'
            )->get();
            $leave_requests                                  =   $leaveReqList->map(function ($row) {
                $name   =   trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
                $from   =   $row->from_date ? \Carbon\Carbon::parse($row->from_date)->format('d M Y') : '';
                $to     =   $row->to_date ? \Carbon\Carbon::parse($row->to_date)->format('d M Y') : '';
                $type   =   $row->leave_type ?? 'leave';
                $desc   =   $name ? ($name . ' requested ' . $type . ' for ' . $from . ' to ' . $to) : ('Leave for ' . $from . ' to ' . $to);
                return [
                    'id'              => $row->id,
                    'emp_id'          => $row->emp_id,
                    'name'            => $name,
                    'profile_picture' => Common::getResortUserPicture($row->admin_id),
                    'description'     => $desc,
                    'from_date'       => $row->from_date,
                    'to_date'         => $row->to_date,
                    'leave_type'      => $row->leave_type ?? 'Leave',
                    'can_accept'      => true,
                    'can_reject'      => true,
                ];
            })->values()->toArray();

            // Employee reminders: haven't taken a break after 5 hours (today, checked in, no break or last break > 5h ago)
            $todayStart                                      =   $today . ' 00:00:00';
            $fiveHoursAgo                                    =   Carbon::now(config('app.timezone'))->subHours(5)->format('H:i');
            $presentToday                                    =   ParentAttendace::where('parent_attendaces.resort_id', $resort_id)
                ->whereDate('parent_attendaces.date', $today)
                ->where('parent_attendaces.date', '<=', $today)
                ->whereIn('parent_attendaces.Status', ['Present', 'On-Time', 'Late'])
                ->whereNotNull('parent_attendaces.CheckingTime')
                ->whereRaw("TRIM(COALESCE(parent_attendaces.CheckingTime,'')) NOT IN ('','0','00:00','00:00:00')")
                ->join('employees', 'employees.id', '=', 'parent_attendaces.Emp_id')
                ->join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                ->where('t1.resort_id', $resort_id);
            if ($underEmp_id !== null) {
                $presentToday->whereIn('employees.id', $underEmp_id);
            }
            $presentToday                                    =   $presentToday->select('parent_attendaces.id as parent_attd_id', 'parent_attendaces.Emp_id', 'parent_attendaces.CheckingTime', 'parent_attendaces.CheckingOutTime', 't1.first_name', 't1.last_name', 't1.id as admin_id')->get();
            $employee_reminders                               =   [];
            foreach ($presentToday as $pa) {
                $checkIn = $pa->CheckingTime;
                if (!$checkIn) {
                    continue;
                }
                $hasBreak                                     =   BreakAttendaces::where('Parent_attd_id', $pa->parent_attd_id)->exists();
                if ($hasBreak) {
                    continue;
                }
                $name                                         =   trim(($pa->first_name ?? '') . ' ' . ($pa->last_name ?? ''));
                $employee_reminders[]                         =   [
                    'emp_id'          => $pa->Emp_id,
                    'parent_attd_id'  => $pa->parent_attd_id,
                    'name'            => $name,
                    'profile_picture' => Common::getResortUserPicture($pa->admin_id),
                    'message'         => $name ? ($name . " haven't taken a break after 5 hours") : "Haven't taken a break after 5 hours",
                    'action'          => 'Send Reminder for break',
                ];
            }

            $dashboard                                       =   [
                'filter'              => $filter,
                'summary'             => [
                    'total_employees' => $total_employees,
                    'present'         => $present,
                    'absent'          => $absent,
                    'on_leave'        => $on_leave,
                ],
                'approved_ot_hours'   => $approved_ot_hours,
                'ot_requests_count'   => $ot_requests_count,
                'employee_reminders'  => $employee_reminders,
                'pending_requests'    => [
                    'ot_requests'     => $ot_requests,
                    'leave_requests'  => $leave_requests,
                ],
            ];

            return response()->json([
                'status'    => true,
                'message'   => 'Time Attendance HOD Dashboard',
                'dashboard' => $dashboard,
            ]);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            \Log::error("Stack trace: " . $e->getTraceAsString());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function timeAttendanceHRDashboard(Request $request)
    {

        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $user                                               =   Auth::guard('api')->user();
        $employee                                           =   $user->GetEmployee ?? $user->getEmployee ?? null;
        if (!$employee) {
            return response()->json(['error' => 'Forbidden: No employee record linked'], 403);
        }

        $resort_id                                          =   $user->resort_id;
        $rankConfig                                         =   config('settings.Position_Rank', []);
        $rankLabel                                          =   strtoupper((string) ($rankConfig[$employee->rank ?? ''] ?? $rankConfig[$employee->main_rank ?? ''] ?? ''));
        $allowedRanks                                       =   ['HR', 'GM', 'HOD', 'EXCOM'];
        $hrDeptId                                           =   ResortDepartment::where('resort_id', $resort_id)
            ->where(function ($q) {
                $q->where('name', 'Human Resources')->orWhere('name', 'like', '%Human Resources%');
            })
            ->value('id');
        $isInHRDept                                         =   $hrDeptId !== null && (int) $employee->Dept_id === (int) $hrDeptId;
        if (!in_array($rankLabel, $allowedRanks, true) && !$isInHRDept) {
            return response()->json(['error' => 'Forbidden: Insufficient rank'], 403);
        }

        $filter                                             =   $request->input('filter', 'weekly');

        try {
            $resort_id                                      =   $user->resort_id;
            $EmpRank                                        =   config('settings.Position_Rank', []);
            $current_rank                                   =   $employee->rank ?? null;
            $available_rank                                 =   $EmpRank[$current_rank] ?? '';

            if ($filter === 'weekly') {
                $startDate                                  =   Carbon::now(config('app.timezone'))->startOfWeek();
                $endDate                                    =   Carbon::now(config('app.timezone'))->endOfWeek();
            } else {
                $startDate                                  =   Carbon::now(config('app.timezone'))->startOfMonth();
                $endDate                                    =   Carbon::now(config('app.timezone'))->endOfMonth();
            }

            $dateFrom                                       =   $startDate->toDateString();
            $dateTo                                         =   $endDate->toDateString();
            $today                                          =   Carbon::now(config('app.timezone'))->format('Y-m-d');

            $ResortPosition                                 =   ResortPosition::where("resort_id", $resort_id)->get();
            $ResortDepartment                               =   ResortDepartment::where("resort_id", $resort_id)->get();

            // Total active employees in resort
            $employeesCount                                 =   (int) Employee::join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                ->where('t1.resort_id', $resort_id)
                ->where('employees.status', 'Active')
                ->distinct()
                ->count('employees.id');

            // Today only: present count (employees with real check-in time in DB today; no future dates)
            $totalPresentDays                               =   (int) ParentAttendace::where('parent_attendaces.resort_id', $resort_id)
                ->whereDate('parent_attendaces.date', $today)
                ->where('parent_attendaces.date', '<=', $today)
                ->whereIn('parent_attendaces.Status', ['Present', 'HalfDay', 'On-Time', 'Late', 'ShortLeave', 'HalfDayLeave'])
                ->whereNotNull('parent_attendaces.CheckingTime')
                ->whereRaw("TRIM(COALESCE(parent_attendaces.CheckingTime,'')) NOT IN ('','0','00:00','00:00:00')")
                ->count();

            // Today only: absent count (employees marked absent today)
            $totalAbsentDays                                =   (int) ParentAttendace::where('parent_attendaces.resort_id', $resort_id)
                ->whereDate('parent_attendaces.date', $today)
                ->whereIn('parent_attendaces.Status', ['Absent', 'Absant'])
                ->count();

            $employeeOTApprvoed                             =   Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                ->join('parent_attendaces as t3', "t3.Emp_id", "=", "employees.id")
                ->where('t3.resort_id', $resort_id)
                ->whereNotNull('t3.OverTime')
                ->where('t3.OTStatus', 'Approved')
                ->whereBetween('t3.date', [$dateFrom, $dateTo])
                ->where("t1.resort_id", $resort_id)
                ->select('t3.id', 't1.first_name', 't1.last_name', 't3.OverTime', 't3.Emp_id')
                ->get();

            // Convert OverTime to Minutes and Sum (safe when OverTime is empty or missing colon)
            $OTApprvoedMin                                  =   $employeeOTApprvoed->sum(function ($item) {
                $parts = explode(':', (string) ($item->OverTime ?? '0:0'));
                $hours = (int) ($parts[0] ?? 0);
                $minutes = (int) ($parts[1] ?? 0);
                return ($hours * 60) + $minutes;
            });
            // Convert Total Minutes Back to HH:mm Format
            $totalOTHrsApproved                             =   floor($OTApprvoedMin / 60) . ':' . str_pad($OTApprvoedMin % 60, 2, '0', STR_PAD_LEFT);

            $employeeOTReq                                  =   Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                ->join('parent_attendaces as t3', "t3.Emp_id", "=", "employees.id")
                ->where('t3.resort_id', $resort_id)
                ->whereNotNull('t3.OverTime')
                ->whereNotNull('t3.CheckingTime')
                ->whereNotNull('t3.CheckingOutTime')
                ->where(function ($query) {
                    $query->whereNull('t3.OTStatus')
                        ->orWhereNotIn('t3.OTStatus', ['Approved', 'Rejected']);
                })
                ->whereBetween('t3.date', [$dateFrom, $dateTo])
                ->where("t1.resort_id", $resort_id)
                ->select('t3.id', 't1.first_name', 't1.last_name', 't3.OverTime', 't3.Emp_id', 't3.OTStatus', 't3.CheckingTime', 't3.CheckingOutTime', 't3.DayWiseTotalHours')
                ->get();

            // Convert OverTime to Minutes and Sum (safe when OverTime is empty or missing colon)
            $OTReqMin                                       =   $employeeOTReq->sum(function ($item) {
                $parts = explode(':', (string) ($item->OverTime ?? '0:0'));
                $hours = (int) ($parts[0] ?? 0);
                $minutes = (int) ($parts[1] ?? 0);
                return ($hours * 60) + $minutes;
            });

            // Convert Total Minutes Back to HH:mm Format
            $totalOTHrsReq                                  =   floor($OTReqMin / 60) . ':' . str_pad($OTReqMin % 60, 2, '0', STR_PAD_LEFT);

            $employeeOTReq                                  =   $employeeOTReq->map(function ($item) {
                if (empty($item->profile_picture) || $item->profile_picture == '0') {
                    $item->profile_picture = Common::getResortUserPicture($item->Admin_Parent_id); // Ensure it's using Parentid if 'profile_picture' is missing
                }
                return $item;
            })->toArray();

            list($approvedHours, $approvedMinutes)          =   explode(':', $totalOTHrsApproved);
            $approvedMinutesTotal                           =   ((int)$approvedHours * 60) + (int)$approvedMinutes;
            list($requestedHours, $requestedMinutes)        =   explode(':', $totalOTHrsReq);
            $requestedMinutesTotal                          =   ((int)$requestedHours * 60) + (int)$requestedMinutes;
            $totalMinutesSum                                =   $approvedMinutesTotal + $requestedMinutesTotal;
            $totalOTSum                                     =   floor($totalMinutesSum / 60) . ':' . str_pad($totalMinutesSum % 60, 2, '0', STR_PAD_LEFT);

            // Today only: employees on approved leave today
            $totalLeaveDays                                 =   (int) EmployeeLeave::where('employees_leaves.resort_id', $resort_id)
                ->where('employees_leaves.status', 'Approved')
                ->where('employees_leaves.from_date', '<=', $today)
                ->where('employees_leaves.to_date', '>=', $today)
                ->join('employees', 'employees.id', '=', 'employees_leaves.emp_id')
                ->join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                ->where('t1.resort_id', $resort_id)
                ->distinct()
                ->count('employees.id');

            //brerak notification
            $requireBreak = Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                ->join('parent_attendaces as pa', "pa.Emp_id", "=", "employees.id")
                ->join('child_attendaces as ca', "ca.Parent_attd_id", "=", "pa.id")
                ->leftJoin('break_attendaces as ba', "ba.Parent_attd_id", "=", "pa.id")
                ->where('pa.status', "=", 'Present')
                ->whereNotNull('pa.CheckInCheckOut_Type')
                ->whereRaw('pa.date = CURRENT_DATE()')
                ->whereRaw('TIMESTAMPDIFF(HOUR, STR_TO_DATE(CONCAT(pa.date, " ", pa.CheckingTime), "%Y-%m-%d %H:%i"), NOW())>2')
                ->whereRaw('TIMESTAMPDIFF(HOUR, STR_TO_DATE(CONCAT(pa.date, " ", pa.CheckingTime), "%Y-%m-%d %H:%i"), NOW()) > 0')
                ->whereNull('pa.CheckingOutTime')
                ->where(function ($query) {
                    $query->whereNull('ba.Break_InTime') // No break taken
                        ->orWhereRaw('TIMESTAMPDIFF(HOUR, STR_TO_DATE(CONCAT(pa.date, " ", ba.Break_OutTime), "%Y-%m-%d %H:%i"), NOW()) > 5'); // Last break was more than 5 hrs ago
                })

                ->select('pa.id', 'pa.Emp_id', 'pa.CheckingTime as intime', 't1.first_name', 't1.last_name', 't1.id as admin_id', \DB::raw("
                        CASE
                            WHEN t1.profile_picture = '0' THEN NULL
                            ELSE t1.profile_picture
                        END as profile_picture
                        "))
                ->get();

            $requireBreak                             =   $requireBreak->map(function ($item) {
                if ($item->profile_picture === null) {
                    $item->profile_picture = Common::getResortUserPicture($item->admin_id ?? null);
                }
                return $item;
            })->toArray();

            // Pending leave requests for dashboard (same format as HOD dashboard)
            $leaveReqList                                  =   EmployeeLeave::where('employees_leaves.resort_id', $resort_id)
                ->where('employees_leaves.status', 'Pending')
                ->join('employees', 'employees.id', '=', 'employees_leaves.emp_id')
                ->join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                ->leftJoin('leave_categories as lc', 'lc.id', '=', 'employees_leaves.leave_category_id')
                ->where('t1.resort_id', $resort_id)
                ->select(
                    'employees_leaves.id',
                    'employees_leaves.emp_id',
                    'employees_leaves.from_date',
                    'employees_leaves.to_date',
                    't1.first_name',
                    't1.last_name',
                    't1.id as admin_id',
                    'lc.leave_type'
                )->get();
            $leave_requests                                 =   $leaveReqList->map(function ($row) {
                $name   =   trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
                $from   =   $row->from_date ? Carbon::parse($row->from_date)->format('d M Y') : '';
                $to     =   $row->to_date ? Carbon::parse($row->to_date)->format('d M Y') : '';
                $type   =   $row->leave_type ?? 'leave';
                $desc   =   $name ? ($name . ' requested ' . $type . ' for ' . $from . ' to ' . $to) : ('Leave for ' . $from . ' to ' . $to);
                return [
                    'id'              => $row->id,
                    'emp_id'          => $row->emp_id,
                    'name'            => $name,
                    'profile_picture' => Common::getResortUserPicture($row->admin_id),
                    'description'     => $desc,
                    'from_date'       => $row->from_date,
                    'to_date'         => $row->to_date,
                    'leave_type'      => $row->leave_type ?? 'Leave',
                    'can_accept'      => true,
                    'can_reject'      => true,
                ];
            })->values()->toArray();

            // All data above is resort-wide (no department filter). HR dept EXCOM/HOD and HR/GM rank see whole resort.
            $timeAttendanceData['scope']                     =   'resort';
            $timeAttendanceData['total_present_emp_today']  =   $totalPresentDays;
            $timeAttendanceData['total_absent_emp_today']   =   $totalAbsentDays;
            $timeAttendanceData['total_on_leave_emp_today']=   $totalLeaveDays;
            $timeAttendanceData['employee']                =   $employeesCount;
            $timeAttendanceData['date']                     =   $today;
            $timeAttendanceData['period_from']             =   $dateFrom;
            $timeAttendanceData['period_to']               =   $dateTo;
            $timeAttendanceData['filter']                  =   $filter;
            $timeAttendanceData['ot_approved_hrs']          =   $totalOTHrsApproved;
            $timeAttendanceData['ot_request_hrs']           =   $totalOTHrsReq;
            $timeAttendanceData['ot_total_hr']              =   $totalOTSum;
            $timeAttendanceData['over_time_request']        =   $employeeOTReq;
            $timeAttendanceData['requires_break']           =   $requireBreak;
            $timeAttendanceData['leave_requests']           =   $leave_requests;


            $response['status']                             =   true;
            $response['message']                            =   'Time TimeAttendance ' . $available_rank . ' Dashboard';
            $response['time_attendance']                    =   $timeAttendanceData;

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::error('timeAttendanceHRDashboard: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function manualCheckIn(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Validate request data
        $validator = Validator::make($request->all(), [
            'current_time'                                  =>  'required',
            'current_date'                                  =>  'required',
            'in_time_location'                              =>  'required',
            'flag'                                          =>  'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        $user                                               =   Auth::guard('api')->user();
        $employee                                           =   $user->GetEmployee;
        $emp_id                                             =   $employee->id;
        $checkInTime                                        =   $request->current_time;
        $date                                               =   $request->current_date;
        // Ensure in_time_location is a string (handle array case like GPS coordinates)
        $inTimeLocationRaw                                  =   $request->in_time_location;
        $inTimeLocation                                     =   is_array($inTimeLocationRaw) ? json_encode($inTimeLocationRaw) : (string)$inTimeLocationRaw;
        $flag                                               =   $request->flag;

        try {
            DB::beginTransaction(); // Start transaction

            $ParentAttendance                               =   ParentAttendace::where('resort_id', $user->resort_id)->where('date', $date)->where('Emp_id', $emp_id)->first();

            // Get employee rank to determine if roster check is required
            $employeeRank                                    =   $employee->rank ?? null;
            $positionRankConfig                             =   config('settings.Position_Rank', []);
            $isSupervisorOrLineWorker                       =   in_array($employeeRank, ['5', '6']); // '5' => 'SUP', '6' => 'LINE WORKERS'

            $rosterData                                      =  DutyRoster::where('resort_id', $user->resort_id)->where('Emp_id', $emp_id)->first();
            $timeAttendance                                 =   [];
            $childAttendace = null;

            // Check if duty roster entry exists for this specific date
            $dutyRosterEntry = null;
            // if ($rosterData) {
                // $dutyRosterEntry = DutyRosterEntry::where('roster_id', $rosterData->id)
                if($dutyRosterEntry == null){
                    $dutyRosterEntry = DutyRosterEntry::where('Emp_id', $emp_id)
                            ->where('resort_id', $user->resort_id)
                            ->whereDate('date', $date)
                            ->first();
                }
            // }

            // Validate: Employee must have a duty roster entry for the specific date
            // This applies to ALL employees, not just supervisors/line workers
            if (!$dutyRosterEntry) {
                DB::rollBack();
                $response['status']                             =   false;
                $response['message']                            =   'Employee does not have a duty roster entry for this date. Please contact your HOD first.';
                return response()->json($response);
            }

            // Only check roster requirement for supervisors and line workers (legacy check)
            if($isSupervisorOrLineWorker && !$rosterData){
                $response['status']                             =   false;
                $response['message']                            =   'Employee does not have a duty roster for today';
                return response()->json($response);
            }

            if($rosterData && $dutyRosterEntry){
                // Use roster_id from the entry for this date so dashboard and todo list match (same table/join)
                $roster_id                                      =   $dutyRosterEntry->roster_id;
                // Use shift_id from duty roster entry if available, otherwise from roster template
                $shift_id                                       =   $dutyRosterEntry->Shift_id ?? $rosterData->Shift_id;
                $shiftData                                      =   ShiftSettings::where('resort_id', $user->resort_id)->where('id', $shift_id)->first();

                if($shiftData){
                    // Set DayWiseTotalHours to 00:00 on check-in - will be calculated on check-out
                    $dayWiseTotalHours = '00:00';

                    if ($ParentAttendance) {
                        // Check if employee has already checked out
                        // If they have checked out (CheckingOutTime is not NULL and not empty), allow a new check-in
                        $hasCheckedOut = !empty($ParentAttendance->CheckingOutTime) && $ParentAttendance->CheckingOutTime != '00:00';

                        if (!$hasCheckedOut) {
                            // Employee hasn't checked out yet, so they're already checked in
                            $timeAttendance['parent_attendance_data']       =   $ParentAttendance;
                            $timeAttendance['child_attendance_data'] = ChildAttendace::where('Parent_attd_id',$ParentAttendance->id)->first();
                            $response['status']                             =   true;
                            $response['message']                            =   'Already Checked In Today';
                            $response['attendance_data']                    =   $timeAttendance;
                            return response()->json($response);
                        }
                        // If they have checked out, continue to create a new attendance record below
                    }

                    // Create new attendance record (either no previous record, or previous record had checkout)
                    if (!$ParentAttendance || (!empty($ParentAttendance->CheckingOutTime) && $ParentAttendance->CheckingOutTime != '00:00')) {
                        $ParentAttendance = ParentAttendace::create([
                            'Emp_id'                                =>  $emp_id,
                            'date'                                  =>  $date,
                            'CheckingTime'                          =>  $checkInTime,
                            'Status'                                =>  'Present',
                            'CheckInCheckOut_Type'                  =>  $flag,
                            'resort_id'                             =>  $user->resort_id,
                            'DayWiseTotalHours'                     =>  $dayWiseTotalHours,
                            'Shift_id'                              =>  $shift_id,
                            'roster_id'                             =>  $roster_id,
                        ]);

                        $childAttendace = ChildAttendace::create([
                            'Parent_attd_id'                        =>  $ParentAttendance->id,
                            'InTime_out'                            =>  $checkInTime,
                            'OutTime_out'                           =>  '00:00', // Default for check-in
                            'InTime_Location'                       =>  $inTimeLocation
                        ]);
                    } else {
                        // This else block should not be needed, but keeping for safety
                        $childAttendace = ChildAttendace::where('Parent_attd_id', $ParentAttendance->id)->first();
                    }
                }else{
                    // Only require shift settings for supervisors and line workers
                    if($isSupervisorOrLineWorker){
                        $response['status']                             =   false;
                        $response['message']                            =   'Employee does not have a shift settings for today';
                        return response()->json($response);
                    }
                    // For non-SUP/LINE WORKER with roster but no shift, use default values
                    if(!$isSupervisorOrLineWorker){
                        // Set DayWiseTotalHours to 00:00 on check-in - will be calculated on check-out
                        $defaultDayWiseTotalHours                       =   '00:00';
                        if ($ParentAttendance) {
                            // Check if employee has already checked out
                            $hasCheckedOut = !empty($ParentAttendance->CheckingOutTime) && $ParentAttendance->CheckingOutTime != '00:00';

                            if (!$hasCheckedOut) {
                                // Employee hasn't checked out yet, so they're already checked in
                                $timeAttendance['parent_attendance_data']       =   $ParentAttendance;
                                $timeAttendance['child_attendance_data'] = ChildAttendace::where('Parent_attd_id',$ParentAttendance->id)->first();
                                $response['status']                             =   true;
                                $response['message']                            =   'Already Checked In Today';
                                $response['attendance_data']                    =   $timeAttendance;
                                return response()->json($response);
                            }
                            // If they have checked out, continue to create a new attendance record below
                        }

                        // Create new attendance record (either no previous record, or previous record had checkout)
                        if (!$ParentAttendance || (!empty($ParentAttendance->CheckingOutTime) && $ParentAttendance->CheckingOutTime != '00:00')) {
                            $ParentAttendance = ParentAttendace::create([
                                'Emp_id'                                =>  $emp_id,
                                'date'                                  =>  $date,
                                'CheckingTime'                          =>  $checkInTime,
                                'Status'                                =>  'Present',
                                'CheckInCheckOut_Type'                  =>  $flag,
                                'resort_id'                             =>  $user->resort_id,
                                'DayWiseTotalHours'                     =>  $defaultDayWiseTotalHours,
                                'Shift_id'                              =>  $shift_id ?? 0,
                                'roster_id'                             =>  $roster_id,
                            ]);

                            $childAttendace = ChildAttendace::create([
                                'Parent_attd_id'                        =>  $ParentAttendance->id,
                                'InTime_out'                            =>  $checkInTime,
                                'OutTime_out'                           =>  '00:00', // Default for check-in
                                'InTime_Location'                       =>  $inTimeLocation
                            ]);
                        }
                    }
                }
            }

            // Handle check-in for employees without roster (GM, EXCOM, HOD, MGR)
            if(!$rosterData && !$isSupervisorOrLineWorker){
                // Check if already checked in
                if ($ParentAttendance) {
                    // Check if employee has already checked out
                    $hasCheckedOut = !empty($ParentAttendance->CheckingOutTime) && $ParentAttendance->CheckingOutTime != '00:00';

                    if (!$hasCheckedOut) {
                        // Employee hasn't checked out yet, so they're already checked in
                        $timeAttendance['parent_attendance_data']       =   $ParentAttendance;
                        $timeAttendance['child_attendance_data'] = ChildAttendace::where('Parent_attd_id',$ParentAttendance->id)->first();
                        $response['status']                             =   true;
                        $response['message']                            =   'Already Checked In Today';
                        $response['attendance_data']                    =   $timeAttendance;
                        return response()->json($response);
                    }
                    // If they have checked out, continue to create a new attendance record below
                }

                // Create new attendance record (either no previous record, or previous record had checkout)
                if (!$ParentAttendance || (!empty($ParentAttendance->CheckingOutTime) && $ParentAttendance->CheckingOutTime != '00:00')) {
                    // Create attendance without roster data (use default values)
                    $defaultShiftId                                 =   0; // Default shift ID for non-roster employees
                    $defaultRosterId                                =   0; // Default roster ID for non-roster employees
                    // Set DayWiseTotalHours to 00:00 on check-in - will be calculated on check-out
                    $defaultDayWiseTotalHours                       =   '00:00';

                    $ParentAttendance = ParentAttendace::create([
                        'Emp_id'                                =>  $emp_id,
                        'date'                                  =>  $date,
                        'CheckingTime'                          =>  $checkInTime,
                        'Status'                                =>  'Present',
                        'CheckInCheckOut_Type'                  =>  $flag,
                        'resort_id'                             =>  $user->resort_id,
                        'DayWiseTotalHours'                     =>  $defaultDayWiseTotalHours,
                        'Shift_id'                              =>  $defaultShiftId,
                        'roster_id'                             =>  $defaultRosterId,
                    ]);

                    $childAttendace = ChildAttendace::create([
                        'Parent_attd_id'                        =>  $ParentAttendance->id,
                        'InTime_out'                            =>  $checkInTime,
                        'OutTime_out'                           =>  '00:00', // Default for check-in
                        'InTime_Location'                       =>  $inTimeLocation
                    ]);
                }
            }
            $timeAttendance['parent_attendance_data']   =   $ParentAttendance;
            $timeAttendance['child_attendance_data']    =   $childAttendace;
            DB::commit(); // Commit transaction

            $response['status']                             =   true;
            $response['message']                            =   'Added the Check-In Attendance Entry';
            $response['attendance_data']                    =   $timeAttendance;
            return response()->json($response);

        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction on error
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function breakCheckInCheckOut(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'attendace_id'                                  =>  'required',
            'flag'                                          =>  'required|in:break_out,break_in',
            'break_out_time'                                =>  'required_if:flag,break_out',
            'break_in_time'                                 =>  'required_if:flag,break_in',
            'out_time_location'                             =>  'required_if:flag,break_out',
            'in_time_location'                              =>  'required_if:flag,break_in',
            'break_id'                                      =>  'required_if:flag,break_in',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        $user                                               =   Auth::guard('api')->user();
        $employee                                           =   $user->GetEmployee;
        $emp_id                                             =   $employee->id;
        $attendaceId                                        =   $request->attendace_id;
        $breakOutTime                                       =   $request->break_out_time;
        $breakInTime                                        =   $request->break_in_time;
        $flag                                               =   $request->flag;
        $break_id                                           =   $request->break_id;

        try {
            DB::beginTransaction();
            if ($flag == 'break_out') {
                $breakData                                  =   BreakAttendaces::create([
                    'Parent_attd_id'                        =>  $attendaceId,
                    'Break_OutTime'                         =>  $breakOutTime,
                    'OutTime_Location'                      =>  $request->out_time_location,
                ]);
            } elseif ($flag == 'break_in') {
                $breakData                                  =   BreakAttendaces::where('Parent_attd_id', $attendaceId)->where('id', $break_id)->whereNotNull('Break_OutTime')->first();

                if (!$breakData) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => 'Break record not found'], 404);
                }

                // Get parent attendance to get the date for proper day handling
                $parentAttendance = ParentAttendace::find($attendaceId);
                $attendanceDate = $parentAttendance ? $parentAttendance->date : Carbon::today()->format('Y-m-d');

                // Parse break times with date for proper day handling
                $breakOutDateTime = Carbon::createFromFormat('Y-m-d H:i', $attendanceDate . ' ' . $breakData->Break_OutTime);
                $breakInDateTime = Carbon::createFromFormat('Y-m-d H:i', $attendanceDate . ' ' . $breakInTime);

                // Handle overnight breaks (break in is next day)
                if ($breakInDateTime->lt($breakOutDateTime)) {
                    $breakInDateTime->addDay();
                }

                // Calculate break duration in minutes
                $totalBreakMinutes = $breakOutDateTime->diffInMinutes($breakInDateTime);

                // Ensure break time is not negative
                if ($totalBreakMinutes < 0) {
                    $totalBreakMinutes = 0;
                }

                // Convert to HH:MM format
                $totalBreakHours = floor($totalBreakMinutes / 60);
                $totalBreakMins = $totalBreakMinutes % 60;
                $totalBreakTime = sprintf('%02d:%02d', $totalBreakHours, $totalBreakMins);

                // Update break record
                $breakData->Break_InTime = $request->break_in_time;
                $breakData->Total_Break_Time = $totalBreakTime;
                $breakData->InTime_Location = $request->in_time_location;
                $breakData->save();
            }

            DB::commit();
            $response['status']                             =   true;
            $response['message']                            =   'Added The Break Entry';
            $response['break_data']                         =   $breakData;
            return response()->json($response);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback on error
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function manualCheckOut(Request $request)
    {
        // #region agent log
        $logFile = 'c:\wamp64\www\Wisdom-Ai\HRVMS-Wisdom-AI\.cursor\debug.log';
        $logEntry = json_encode([
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'ENTRY',
            'location' => 'TimeAndAttendanceController.php:manualCheckOut:START',
            'message' => 'manualCheckOut method called',
            'data' => ['request_data' => $request->all()],
            'timestamp' => round(microtime(true) * 1000)
        ]) . "\n";
        @file_put_contents($logFile, $logEntry, FILE_APPEND);
        // #endregion

        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Validate request data
        $validator = Validator::make($request->all(), [
            'attendace_id'                                  =>  'required',
            'current_time'                                  =>  'required',
            'current_date'                                  =>  'required',
            'out_time_location'                             =>  'required',
        ]);

        if ($validator->fails()) {
            // #region agent log
            $logEntry = json_encode([
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'VALIDATION',
                'location' => 'TimeAndAttendanceController.php:manualCheckOut:VALIDATION_FAILED',
                'message' => 'Validation failed',
                'data' => ['errors' => $validator->errors()->toArray()],
                'timestamp' => round(microtime(true) * 1000)
            ]) . "\n";
            @file_put_contents($logFile, $logEntry, FILE_APPEND);
            // #endregion

            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        $user                                               =   Auth::guard('api')->user();
        $employee                                           =   $user->GetEmployee;
        $emp_id                                             =   $employee->id;
        $checkOutTime                                       =   $request->current_time;
        $date                                               =   $request->current_date;
        $outTimeLocation                                    =   $request->out_time_location;
        $attendaceId                                        =   $request->attendace_id;

        try {
            DB::beginTransaction(); // Start transaction

            $ParentAttendance                               =   ParentAttendace::where('resort_id', $user->resort_id)->where('date', $date)->where('Emp_id', $emp_id)->where('id', $attendaceId)->first();

            if (!$ParentAttendance) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Attendance record not found'], 404);
            }

            $childAttendace                                 =   ChildAttendace::where('Parent_attd_id', $attendaceId)->whereNotNull('InTime_out')->first();
            $breakData                                      =   BreakAttendaces::where('Parent_attd_id', $attendaceId)->get();
            $timeAttendance                                 =   [];

            // Parse check-in and check-out times
            $checkOutTimeCarbon                             =   Carbon::createFromFormat('H:i', $checkOutTime);
            $checkInTimeCarbon                              =   Carbon::createFromFormat('H:i', $ParentAttendance->CheckingTime);

            // Calculate actual worked time (check-out - check-in)
            // Handle case where checkout is next day (overnight shift)
            if ($checkOutTimeCarbon->lt($checkInTimeCarbon)) {
                $checkOutTimeCarbon->addDay();
            }

            $totalWorkMinutes                               =   $checkOutTimeCarbon->diffInMinutes($checkInTimeCarbon);

            // Get Total Break Time from Break Table
            $totalBreakMinutes                              =   0;
            if ($breakData->isNotEmpty()) {
                foreach ($breakData as $break) {
                    if (!empty($break->Total_Break_Time)) {
                        $breakParts                         =   explode(':', $break->Total_Break_Time);
                        $totalBreakMinutes                  +=  ($breakParts[0] * 60) + $breakParts[1];
                    }
                }
            }

            // Final Work Time = (CheckOut - CheckIn) - Break Time
            $finalWorkMinutes                               =   $totalWorkMinutes - $totalBreakMinutes;

            // Ensure final work time is not negative
            if ($finalWorkMinutes < 0) {
                $finalWorkMinutes                           =   0;
            }

            // Convert final work time into HH:MM format
            $finalWorkHours                                 =   floor($finalWorkMinutes / 60);
            $finalWorkMins                                  =   $finalWorkMinutes % 60;
            $finalWorkTime                                  =   sprintf('%02d:%02d', $finalWorkHours, $finalWorkMins);

            if ($ParentAttendance->CheckingOutTime == NULL || $ParentAttendance->CheckingOutTime == '') {
                // If record exists, update it
                $ParentAttendance->CheckingOutTime          =   $request->current_time;
                $ParentAttendance->DayWiseTotalHours        =   $finalWorkTime;
                $ParentAttendance->save();

                if ($childAttendace) {
                    $childAttendace->OutTime_out            =   $request->current_time;
                    $childAttendace->OutTime_Location       =   $outTimeLocation;
                    $childAttendace->save();
                }

                // Calculate and create overtime entries
                // Get shift information and expected overtime from roster
                $shiftId = $ParentAttendance->Shift_id ?? 0;
                $rosterId = $ParentAttendance->roster_id ?? null;
                $expectedOvertime = '00:00';

                // #region agent log
                $logFile = 'c:\wamp64\www\Wisdom-Ai\HRVMS-Wisdom-AI\.cursor\debug.log';
                $logEntry = json_encode([
                    'sessionId' => 'debug-session',
                    'runId' => 'run1',
                    'hypothesisId' => 'G',
                    'location' => 'TimeAndAttendanceController.php:manualCheckOut:OVERTIME_START',
                    'message' => 'Starting overtime calculation',
                    'data' => [
                        'shiftId' => $shiftId,
                        'rosterId' => $rosterId,
                        'date' => $date,
                        'checkInTime' => $ParentAttendance->CheckingTime,
                        'checkOutTime' => $checkOutTime
                    ],
                    'timestamp' => round(microtime(true) * 1000)
                ]) . "\n";
                @file_put_contents($logFile, $logEntry, FILE_APPEND);
                // #endregion

                // Get expected overtime from duty_roster_entries if roster exists
                if ($rosterId) {
                    $dutyRosterEntry = DutyRosterEntry::where('roster_id', $rosterId)
                        ->whereDate('date', $date)
                        ->first();

                    if ($dutyRosterEntry && !empty($dutyRosterEntry->OverTime) && $dutyRosterEntry->OverTime != '00:00') {
                        $expectedOvertime = $dutyRosterEntry->OverTime;
                    }

                    // #region agent log
                    $logEntry = json_encode([
                        'sessionId' => 'debug-session',
                        'runId' => 'run1',
                        'hypothesisId' => 'G',
                        'location' => 'TimeAndAttendanceController.php:manualCheckOut:ROSTER_OT',
                        'message' => 'Retrieved expected overtime from roster',
                        'data' => [
                            'dutyRosterEntryFound' => $dutyRosterEntry ? true : false,
                            'expectedOvertime' => $expectedOvertime
                        ],
                        'timestamp' => round(microtime(true) * 1000)
                    ]) . "\n";
                    @file_put_contents($logFile, $logEntry, FILE_APPEND);
                    // #endregion
                }

                // Get shift settings for shift start and end times
                $shiftData = null;
                if ($shiftId > 0) {
                    $shiftData = ShiftSettings::where('id', $shiftId)
                        ->where('resort_id', $user->resort_id)
                        ->first();
                }

                // Only calculate overtime if shift data exists
                if ($shiftData) {
                    $shiftStartTime = $shiftData->StartTime; // Format: H:i
                    $shiftEndTime = $shiftData->EndTime; // Format: H:i

                    // #region agent log
                    $logEntry = json_encode([
                        'sessionId' => 'debug-session',
                        'runId' => 'run1',
                        'hypothesisId' => 'E',
                        'location' => 'TimeAndAttendanceController.php:manualCheckOut:BEFORE_CALC',
                        'message' => 'Before calling calculateOvertimeEntries',
                        'data' => [
                            'shiftStartTime' => $shiftStartTime,
                            'shiftEndTime' => $shiftEndTime,
                            'breakDataCount' => $breakData->count()
                        ],
                        'timestamp' => round(microtime(true) * 1000)
                    ]) . "\n";
                    @file_put_contents($logFile, $logEntry, FILE_APPEND);
                    // #endregion

                    // Calculate overtime entries using helper function
                    $overtimeEntries = Common::calculateOvertimeEntries(
                        $ParentAttendance->CheckingTime, // Check-in time
                        $checkOutTime, // Check-out time
                        $shiftStartTime, // Shift start time
                        $shiftEndTime, // Shift end time
                        $date, // Date
                        $breakData->toArray(), // Break data
                        $expectedOvertime // Expected overtime from roster
                    );

                    // #region agent log
                    $logEntry = json_encode([
                        'sessionId' => 'debug-session',
                        'runId' => 'run1',
                        'hypothesisId' => 'E',
                        'location' => 'TimeAndAttendanceController.php:manualCheckOut:AFTER_CALC',
                        'message' => 'After calculateOvertimeEntries',
                        'data' => [
                            'overtimeEntriesCount' => count($overtimeEntries),
                            'overtimeEntries' => $overtimeEntries
                        ],
                        'timestamp' => round(microtime(true) * 1000)
                    ]) . "\n";
                    @file_put_contents($logFile, $logEntry, FILE_APPEND);
                    // #endregion

                    // Create overtime entries in database if any
                    if (!empty($overtimeEntries)) {
                        // Add location data to overtime entries
                        foreach ($overtimeEntries as &$entry) {
                            if ($entry['overtime_type'] === 'before_shift') {
                                $entry['start_location'] = $childAttendace->InTime_Location ?? null;
                                $entry['end_location'] = null; // Shift start location not tracked
                            } elseif ($entry['overtime_type'] === 'after_shift') {
                                $entry['start_location'] = null; // Shift end location not tracked
                                $entry['end_location'] = $outTimeLocation;
                            } else {
                                // Split overtime
                                $entry['start_location'] = $childAttendace->InTime_Location ?? null;
                                $entry['end_location'] = $outTimeLocation;
                            }
                        }

                        $createdOvertimeEntries = Common::createOvertimeEntries(
                            $user->resort_id,
                            $emp_id,
                            $shiftId,
                            $rosterId,
                            $ParentAttendance->id,
                            $date,
                            $overtimeEntries
                        );

                        // #region agent log
                        $logEntry = json_encode([
                            'sessionId' => 'debug-session',
                            'runId' => 'run1',
                            'hypothesisId' => 'E',
                            'location' => 'TimeAndAttendanceController.php:manualCheckOut:OT_CREATED',
                            'message' => 'Overtime entries created in database',
                            'data' => [
                                'createdCount' => count($createdOvertimeEntries),
                                'createdIds' => array_map(function($entry) { return $entry->id ?? null; }, $createdOvertimeEntries)
                            ],
                            'timestamp' => round(microtime(true) * 1000)
                        ]) . "\n";
                        @file_put_contents($logFile, $logEntry, FILE_APPEND);
                        // #endregion

                        $timeAttendance['overtime_entries'] = $createdOvertimeEntries;
                    }
                } else {
                    // #region agent log
                    $logEntry = json_encode([
                        'sessionId' => 'debug-session',
                        'runId' => 'run1',
                        'hypothesisId' => 'E',
                        'location' => 'TimeAndAttendanceController.php:manualCheckOut:NO_SHIFT',
                        'message' => 'No shift data found - skipping overtime calculation',
                        'data' => ['shiftId' => $shiftId],
                        'timestamp' => round(microtime(true) * 1000)
                    ]) . "\n";
                    @file_put_contents($logFile, $logEntry, FILE_APPEND);
                    // #endregion
                }
            }

            $timeAttendance['parent_attendance_data']       =   $ParentAttendance;
            $timeAttendance['child_attendance_data']        =   $childAttendace;

            DB::commit(); // Commit transaction

            $response['status']                             =   true;
            $response['message']                            =   'Added the Check-Out Attendance Entry';
            $response['attendance_data']                    =   $timeAttendance;
            return response()->json($response);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction on error
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function hrTimeAttendance(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Validate request data
        $validator = Validator::make($request->all(), [
            'filter'                                        =>  'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        $user                                               =   Auth::guard('api')->user();
        $employee                                           =   $user->GetEmployee;
        $emp_id                                             =   $employee->id;

        try {

            $Dept_id                                        =   $employee->Dept_id;
            $Rank                                           =   $employee->rank;
            $resort_id                                      =   $user->resort_id;

            $EmpRank                                        =   config('settings.Position_Rank');
            $current_rank                                   =   $Rank ?? null;
            $available_rank                                 =   $EmpRank[$current_rank] ?? '';

            $startDate                                      =   Carbon::now();
            $endDate                                        =   Carbon::now();

            if ($request->filter === 'weekly') {
                $startDate                                  =   Carbon::now()->startOfWeek();
                $endDate                                    =   Carbon::now()->endOfWeek();
            } elseif ($request->filter === 'monthly') {
                $startDate                                  =   Carbon::now()->startOfMonth();
                $endDate                                    =   Carbon::now()->endOfMonth();
            }

            $startDate                                      =   $startDate->format('Y-m-d'); // Keep formatted date separate
            $endDate                                        =   $endDate->format('Y-m-d'); // Keep formatted date separate

            $attendanceData                                 =   DB::table('resort_departments as rd')
                                                                    ->leftJoin('resort_divisions as rdiv', 'rdiv.id', '=', 'rd.division_id')  // Left join to include all divisions
                                                                    ->leftJoinSub(function ($query) use ($startDate, $endDate) {
                                                                        $query->from('employees as e')
                                                                            ->leftJoin('resort_admins as t1', 't1.id', '=', 'e.Admin_Parent_id')
                                                                            ->leftJoin('parent_attendaces as t4', 't4.Emp_id', '=', 'e.id')
                                                                            ->whereBetween('t4.date', [$startDate, $endDate])
                                                                            ->select(
                                                                                'e.Dept_id',
                                                                                't1.first_name',
                                                                                't1.last_name',
                                                                                't1.profile_picture',
                                                                                'e.id as emp_id',
                                                                                DB::raw("COUNT(DISTINCT CASE WHEN t4.Status = 'Present' AND t4.CheckingTime IS NOT NULL AND TRIM(IFNULL(t4.CheckingTime,'')) NOT IN ('', '00:00', '00:00:00') THEN t4.date END) as present_days"),
                                                                                DB::raw("COUNT(DISTINCT CASE WHEN t4.Status = 'Absent' THEN t4.date END) as absent_days")
                                                                            )
                                                                            ->groupBy('e.Dept_id', 'e.id');
                                                                    }, 'employee_data', 'employee_data.Dept_id', '=', 'rd.id')  // Left join the employee data subquery
                                                                    ->where('rd.resort_id', $resort_id) // Filter by resort_id
                                                                    ->select(
                                                                        'employee_data.emp_id',
                                                                        'rd.resort_id',
                                                                        'rd.name as department_name',
                                                                        'rdiv.name as division_name',
                                                                        'employee_data.first_name',
                                                                        'employee_data.last_name',
                                                                        DB::raw("COALESCE(employee_data.present_days, 0) as present_days"),
                                                                        DB::raw("COALESCE(employee_data.absent_days, 0) as absent_days")
                                                                    )->get();


            $response['status']                             =   true;
            $response['message']                            =   'Time TimeAttendance ' . $available_rank . ' Dashboard';
            $response['department_attendance_data']         =   $attendanceData;

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function employeeCheckinCheckoutTime($date)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $user                                               =   Auth::guard('api')->user();
        $employee                                           =   $user->GetEmployee;
        $emp_id                                             =   $employee->id;

        try {
            $empCheckInOutTime                              =   Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                                                                ->join('duty_rosters as t2', "t2.Emp_id", "=", "employees.id")
                                                                ->join('shift_settings as ss', 't2.Shift_id', '=', 'ss.id')
                                                                ->join('parent_attendaces as t4', "t4.roster_id", "=", "t2.id")
                                                                ->where('employees.id', $emp_id)
                                                                ->whereDate('t4.date', $date)
                                                                ->select('t4.id', 'employees.id as employee_id', 't1.first_name', 't1.last_name', 'employees.Emp_id', 'ss.ShiftName', 'ss.StartTime', 'ss.EndTime', 'ss.TotalHours', 't4.date')
                                                                ->first();
            if (empty($empCheckInOutTime)) {
                return response()->json(['success' => false, 'message' => 'Duty roster is not create for this employee.'], 200);
            }

            $response['status']                             =   true;
            $response['message']                            =   'Employee Check-in Check-out Time.';
            $response['employee_checkin_checkout']          =   $empCheckInOutTime;

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function timeAttendanceEmployeeLeave()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $user                                               =   Auth::guard('api')->user();
        $employee                                           =   $user->GetEmployee;
        $emp_id                                             =   $employee->id;

        try {

            $startDate                                      =   Carbon::now()->startOfMonth()->toDateString();
            $endDate                                        =   Carbon::now()->endOfMonth()->toDateString();

            $employeeLeaves                                 =   EmployeeLeave::join('leave_categories as t4', 't4.id', '=', 'employees_leaves.leave_category_id')
                                                                    ->where('employees_leaves.Emp_id', $emp_id)
                                                                    ->where('employees_leaves.status', 'Approved')
                                                                    ->where(function ($query) use ($startDate, $endDate) {
                                                                        $query->whereDate('employees_leaves.from_date', '<=', $endDate) // Starts before or in this month
                                                                            ->whereDate('employees_leaves.to_date', '>=', $startDate); // Ends after or in this month
                                                                    })
                                                                    ->get([
                                                                        't4.color',
                                                                        't4.leave_type',
                                                                        'employees_leaves.total_days',
                                                                        'employees_leaves.from_date',
                                                                        'employees_leaves.to_date',
                                                                        'employees_leaves.leave_category_id'
                                                                    ]);


            $response['status']                             =   true;
            $response['message']                            =   "Employee Leaves.";
            $response['employee_leave']                     =   $employeeLeaves;
            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function hodTimeAttendance(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Validate request data
        $validator = Validator::make($request->all(), [
            'filter'                                        =>  'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        $user                                               =   Auth::guard('api')->user();
        $employee                                           =   $user->GetEmployee;
        $resort_id                                          =   $user->resort_id;
        $emp_id                                             =   $employee->id;
        $reporting_to                                       =   $emp_id;

        // Default: subordinates under this HOD
        $underEmp_id                                        =   Common::getSubordinates($reporting_to);

        // If HOD belongs to HR department, show only HR + EXCOM rank employees across the resort
        $rankConfig                                         =   config('settings.Position_Rank', []);
        $currentRankLabel                                   =   $rankConfig[$employee->rank ?? ''] ?? '';
        $hrDeptId                                           =   ResortDepartment::where('resort_id', $resort_id)
                                                                        ->where(function ($q) {
                                                                            $q->where('name', 'Human Resources')
                                                                              ->orWhere('name', 'like', '%Human Resources%');
                                                                        })
                                                                        ->value('id');

        $isHRDepartmentHOD                                  =   ($hrDeptId && (int) $employee->Dept_id === (int) $hrDeptId && strtoupper($currentRankLabel) === 'HOD');

        if ($isHRDepartmentHOD) {
            $hrRankKey                                      =   array_search('HR', $rankConfig, true);
            $excomRankKey                                   =   array_search('EXCOM', $rankConfig, true);

            $rankKeys                                       =   array_filter([$hrRankKey, $excomRankKey], function ($v) {
                                                                    return $v !== false && $v !== null;
                                                                });

            if (!empty($rankKeys)) {
                $underEmp_id                                =   Employee::where('resort_id', $resort_id)
                                                                        ->whereIn('rank', $rankKeys)
                                                                        ->pluck('id')
                                                                        ->toArray();
            } else {
                $underEmp_id                                =   [];
            }
        }

        try {
            $currentDate                                    =   Carbon::now()->format('Y-m-d');
            $employeeAttendance                             =   Employee::join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                                                                    ->leftJoin('duty_rosters as t2', 't2.Emp_id', '=', 'employees.id')
                                                                    ->leftJoin('parent_attendaces as t3', function ($join) use ($currentDate) {
                                                                        $join->on('t3.roster_id', '=', 't2.id')
                                                                            ->whereDate('t3.date', $currentDate)
                                                                            ->whereNotNull('t3.CheckingTime')
                                                                            ->whereNotNull('t3.CheckingOutTime');
                                                                    })
                                                                    ->where('t1.resort_id', $resort_id)
                                                                    ->whereIn('employees.id', $underEmp_id)
                                                                    ->select(
                                                                        'employees.id',
                                                                        't1.first_name',
                                                                        't1.last_name',
                                                                        \DB::raw("
                                                                                                                            CASE
                                                                                                                                WHEN t1.profile_picture = '0' THEN NULL
                                                                                                                                ELSE t1.profile_picture
                                                                                                                            END as profile_picture
                                                                                                                        "),
                                                                        \DB::raw('MAX(t3.CheckingTime) as CheckingTime'),  // Ensuring only one attendance record per employee
                                                                        \DB::raw('MAX(t3.DayWiseTotalHours) as DayWiseTotalHours'),
                                                                        \DB::raw('MAX(t3.CheckingOutTime) as CheckingOutTime'),
                                                                        \DB::raw('MAX(t3.date) as date'),
                                                                        \DB::raw('MAX(t3.Status) as Status'),
                                                                        \DB::raw('MAX(t3.OverTime) as OverTime'),
                                                                        \DB::raw('MAX(t3.CheckInCheckOut_Type) as CheckInCheckOut_Type')
                                                                    )
                                                                    ->groupBy('employees.id', 't1.first_name', 't1.last_name', 't1.profile_picture') // Grouping by unique employee fields
                                                                    ->get();

            $employeeAttendance                             =   $employeeAttendance->map(function ($item) {
                if ($item->profile_picture === null) {
                    $item->profile_picture                  =   Common::getResortUserPicture($item->Admin_Parent_id);
                }
                return $item;
            })->toArray();

            if (empty($employeeAttendance)) {
                return response()->json(['success' => false,  'message' => 'No attendance records found for employees under the HOD.'], 200);
            }

            $response['status']                             =   true;
            $response['message']                            =   'Employee attendance records retrieved successfully for HOD.';
            $response['employee_attendance']                =   $employeeAttendance;

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function hodViewDutyRoster(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Validate request data
        $validator = Validator::make($request->all(), [
            'employee_id'                                   => 'required',
            'filter'                                        => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        $user                                               =  Auth::guard('api')->user();
        $employee_id                                        =  $request->employee_id;

        try {

            $WeekstartDate                                  =   '';
            $WeekendDate                                    =   '';
            $startOfMonth                                   =   '';
            $endOfMonth                                     =   '';
            $Rosterdata                                     =   Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                                                                    ->join('resort_positions as t2', "t2.id", "=", "employees.Position_id")
                                                                    ->join('duty_rosters as t3', "t3.Emp_id", "=", "employees.id")
                                                                    ->select('t3.id as duty_roster_id', 't3.DayOfDate', 't1.first_name', 't1.last_name', 't1.profile_picture', 'employees.id as emp_id', 't2.position_title')
                                                                    ->where('employees.id', $employee_id)
                                                                    ->get();

            $WeekstartDate                                  =   Carbon::now()->startOfWeek();
            $WeekendDate                                    =   Carbon::now()->endOfWeek();
            $startOfMonth                                   =   Carbon::now()->startOfMonth(); // Get the first day of the month
            $endOfMonth                                     =   Carbon::now()->endOfMonth(); // Get the last day of the month
            $year                                           =   $request->year;
            $month                                          =   $request->month;
            $employeeAttArray                               =   [];
            foreach ($Rosterdata as $roster) {

                if ($request->filter === 'weekly') {
                    $RosterInternalData                     =   Common::dutyRosterMonthAndWeekWise($user->resort_id, $roster->duty_roster_id, $roster->emp_id, $WeekstartDate, $WeekendDate, '', '', '', '', 'weekly');
                } elseif ($request->filter === 'monthly') {
                    $RosterInternalData                     =   Common::dutyRosterMonthAndWeekWise($user->resort_id, $roster->duty_roster_id, $roster->emp_id, '', '',  $startOfMonth, $endOfMonth, $year, $month, 'Monthwise');
                }

                if ($roster->profile_picture === null || $roster->profile_picture == '0') { // If profile picture is 0 or NULL, get custom image
                    $roster->profile_picture                =   Common::getResortUserPicture($roster->emp_id);
                }

                $employeeAttArray['employee_data']          =   $roster;
                $employeeAttArray['time_attendace_data']    =   $RosterInternalData;
            }

            $response['status']                             = true;
            $response['message']                            = 'Employee time attendance records for HOD retrieved successfully.';
            $response['emp_time_attendance']                = $employeeAttArray;


            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function underEmpHOD()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $user                                               =   Auth::guard('api')->user();
        $employee                                           =   $user->GetEmployee;
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee record not found.'], 403);
        }

        $resort_id                                          =   $user->resort_id;
        $emp_id                                             =   $employee->id;
        $reporting_to                                       =   $emp_id;
        $underEmp_id                                        =   Common::getSubordinates($reporting_to);

        $rankConfig                                         =   config('settings.Position_Rank', []);
        $currentRankLabel                                   =   $rankConfig[$employee->rank ?? ''] ?? '';
        $hrDeptId                                           =   ResortDepartment::where('resort_id', $resort_id)
                                                                        ->where(function ($q) {
                                                                            $q->where('name', 'Human Resources')
                                                                              ->orWhere('name', 'like', '%Human Resources%');
                                                                        })
                                                                        ->value('id');
        $isHRDepartmentHODOrExcom                           =   ($hrDeptId && (int) $employee->Dept_id === (int) $hrDeptId && in_array(strtoupper($currentRankLabel), ['HOD', 'EXCOM'], true));
        $isHROrGMRank                                       =   in_array(strtoupper($currentRankLabel), ['HR', 'GM'], true);

        if ($isHRDepartmentHODOrExcom || $isHROrGMRank) {
            $underEmp_id                                    =   null;
        }

        try {

            $currentDate                                    =   Carbon::now()->format('Y-m-d');

            // Use duty_roster_entries for "has roster today"; left join parent_attendaces by Emp_id+date so status shows Present once marked (any roster)
            $query                                           =   DutyRosterEntry::from('duty_roster_entries as dre')
                                                                    ->whereRaw('DATE(dre.date) = ?', [$currentDate])
                                                                    ->where('dre.resort_id', $resort_id)
                                                                    ->join('employees', 'employees.id', '=', 'dre.Emp_id')
                                                                    ->join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                                                                    ->join('resort_positions as rp', 'rp.id', '=', 'employees.Position_id')
                                                                    ->leftJoin('parent_attendaces as t3', function ($join) use ($currentDate, $resort_id) {
                                                                        $join->on('t3.Emp_id', '=', 'dre.Emp_id')
                                                                             ->where('t3.resort_id', $resort_id)
                                                                             ->whereRaw('DATE(t3.date) = ?', [$currentDate]);
                                                                    })
                                                                    ->where('t1.resort_id', $resort_id)
                                                                    ->select(
                                                                        't1.id as admin_id',
                                                                        't1.first_name',
                                                                        't1.last_name',
                                                                        't1.profile_picture',
                                                                        'employees.id as emp_id',
                                                                        'rp.position_title',
                                                                        't3.id as attendance_id',
                                                                        't3.Status as attendance_status',
                                                                        't3.CheckingTime',
                                                                        't3.CheckingOutTime',
                                                                        't3.CheckInCheckOut_Type'
                                                                    );

            if ($underEmp_id !== null) {
                $query->whereIn('employees.id', $underEmp_id);
            }

            $underEmp_HOD                                   =   $query->get();

            $underEmp_HOD                                   =   $underEmp_HOD->map(function ($item) {
                if (empty($item->profile_picture) || $item->profile_picture == '0') {
                    $item->profile_picture = Common::getResortUserPicture($item->admin_id);
                }

                $status                                     =   $item->attendance_status;
                $item->attendance_display_status           =   ($status === 'Present' || $status === 'On-Time' || $status === 'Late') ? 'Present' : 'Absent';
                $item->can_mark_present                     =   ($status === '' || $status === null) && !empty($item->attendance_id);

                return $item;
            })->toArray();

            $response['status']                             =   true;
            $response['message']                            =   'Employees under HOD with duty roster for today fetched successfully.';
            $response['emp_time_attendance']                =   $underEmp_HOD;

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * HOD mark attendance: employee list for marking.
     * - HR department (HR rank, or EXCOM/HOD from Human Resources dept): whole resort employees.
     * - Other departments (EXCOM/HOD): only their department employees.
     * Optional query: perms=all (whole resort) | perms=department (only my department). If not sent, scope is auto from role/dept.
     */
    public function hodMarkAttendance(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $user                                               =   Auth::guard('api')->user();
        $employee                                           =   $user->GetEmployee;
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee record not found.'], 403);
        }

        $resort_id                                          =   $user->resort_id;
        $perms                                              =   $request->query('perms', '');

        $rankConfig                                         =   config('settings.Position_Rank', []);
        $currentRankLabel                                   =   $rankConfig[$employee->rank ?? ''] ?? '';

        $hrDeptId                                           =   ResortDepartment::where('resort_id', $resort_id)
                                                                        ->where(function ($q) {
                                                                            $q->where('name', 'Human Resources')
                                                                              ->orWhere('name', 'like', '%Human Resources%');
                                                                        })
                                                                        ->value('id');

        $isHRDepartment                                     =   ($hrDeptId && (int) $employee->Dept_id === (int) $hrDeptId);
        $isHROrGM                                           =   in_array($currentRankLabel, ['HR', 'GM'], true);

        if (strtolower($perms) === 'all') {
            $employeeIds                                    =   null;
        } elseif (strtolower($perms) === 'department') {
            $employeeIds                                    =   Employee::where('resort_id', $resort_id)
                                                                        ->where('Dept_id', $employee->Dept_id)
                                                                        ->pluck('id')
                                                                        ->toArray();
            $employeeIds                                    =   empty($employeeIds) ? [-1] : $employeeIds;
        } else {
            if ($isHROrGM || $isHRDepartment) {
                $employeeIds                                =   null;
            } else {
                $employeeIds                                =   Employee::where('resort_id', $resort_id)
                                                                            ->where('Dept_id', $employee->Dept_id)
                                                                            ->pluck('id')
                                                                            ->toArray();
                $employeeIds                                =   empty($employeeIds) ? [-1] : $employeeIds;
            }
        }

        try {

            $currentDate                                    =   Carbon::today()->format('Y-m-d');
            // Return only employees who are marked present for today (Status Present/On-Time/Late and real check-in in DB; no future)
            $query                                           =   ParentAttendace::from('parent_attendaces as t3')
                                                                    ->where('t3.resort_id', $resort_id)
                                                                    ->whereRaw('DATE(t3.date) = ?', [$currentDate])
                                                                    ->whereRaw('t3.date <= ?', [$currentDate])
                                                                    ->whereIn('t3.Status', ['Present', 'On-Time', 'Late'])
                                                                    ->whereNotNull('t3.CheckingTime')
                                                                    ->whereRaw("TRIM(COALESCE(t3.CheckingTime,'')) NOT IN ('','0','00:00','00:00:00')")
                                                                    ->join('employees', 'employees.id', '=', 't3.Emp_id')
                                                                    ->join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                                                                    ->join('resort_positions as rp', 'rp.id', '=', 'employees.Position_id')
                                                                    ->where('t1.resort_id', $resort_id)
                                                                    ->select(
                                                                        't3.id as id',
                                                                        't1.id as admin_id',
                                                                        't1.first_name',
                                                                        't1.last_name',
                                                                        't1.profile_picture',
                                                                        'employees.id as emp_id',
                                                                        'rp.position_title',
                                                                        't3.Status',
                                                                        't3.CheckingTime',
                                                                        't3.CheckingOutTime',
                                                                        't3.date as date',
                                                                        't3.CheckInCheckOut_Type'
                                                                    );

            if ($employeeIds !== null) {
                $query->whereIn('employees.id', $employeeIds);
            }

            $employeeAttendance                             =   $query->get();

            $employeeAttendance                             =   $employeeAttendance->map(function ($item) {
                if (empty($item->profile_picture) || $item->profile_picture == '0') {
                    $item->profile_picture = Common::getResortUserPicture($item->admin_id);
                }
                $item->attendance_display_status             =   'Present';
                $item->can_mark_present                     =   false;
                return $item;
            })->toArray();

            $response['status']                             =   true;
            $response['message']                            =   'Employee list for HOD attendance marking retrieved successfully.';
            $response['mark_attendance']                    =   $employeeAttendance;

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function hodMarkAttendancePresent(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $user                                               =   Auth::guard('api')->user();
        $resort_id                                          =   $user->resort_id;

        // Accept either emp_id (array) or attendance_id (array); at least one required
        $empIds                                             =   $request->input('emp_id');
        $attendaceIds                                       =   $request->input('attendance_id');
        if (!is_array($empIds)) {
            $empIds                                         =   $empIds !== null && $empIds !== '' ? [(int) $empIds] : [];
        }
        if (!is_array($attendaceIds)) {
            $attendaceIds                                   =   $attendaceIds !== null && $attendaceIds !== '' ? [(int) $attendaceIds] : [];
        }

        $validator                                          =   Validator::make($request->all(), [
            'hod_location'                                  =>  'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        if (empty($empIds) && empty($attendaceIds)) {
            return response()->json(['success' => false, 'message' => 'Send either emp_id (array) or attendance_id (array).'], 400);
        }

        if (!empty($empIds)) {
            $validatorEmp                                   =   Validator::make(['emp_id' => $empIds], [
                'emp_id'                                    =>  'array|min:1',
                'emp_id.*'                                  =>  'integer',
            ]);
            if ($validatorEmp->fails()) {
                return response()->json(['success' => false, 'errors' => $validatorEmp->errors()], 400);
            }
            $empIds                                         =   array_values(array_unique(array_map('intval', $empIds)));
        }
        if (!empty($attendaceIds)) {
            $validatorAtt                                   =   Validator::make(['attendance_id' => $attendaceIds], [
                'attendance_id'                             =>  'array|min:1',
                'attendance_id.*'                           =>  'integer|exists:parent_attendaces,id',
            ]);
            if ($validatorAtt->fails()) {
                return response()->json(['success' => false, 'errors' => $validatorAtt->errors()], 400);
            }
        }

        try {
            $timeAttendance                                 =   [];
            $appTimezone                                   =   config('app.timezone', 'UTC');
            $currentDate                                    =   $request->input('date');
            if (empty($currentDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $currentDate)) {
                $currentDate                                =   Carbon::now($appTimezone)->format('Y-m-d');
            }

            DB::beginTransaction();

            // Mark by attendance_id (existing behaviour)
            foreach ($attendaceIds as $value) {
                $parentAttendance                           =   ParentAttendace::where('resort_id', $resort_id)->where('id', $value)->whereIn('Status', ['', null])->first();
                if ($parentAttendance) {
                    $shiftData                              =   ShiftSettings::where('resort_id', $resort_id)->where('id', $parentAttendance->Shift_id)->first();
                    if ($shiftData) {
                        $parentAttendance->CheckingTime     =   $shiftData->StartTime;
                        $parentAttendance->CheckingOutTime  =   $shiftData->EndTime;
                        $parentAttendance->Status           =   'Present';
                        $parentAttendance->CheckInCheckOut_Type = 'Manual';
                        $parentAttendance->save();

                        ChildAttendace::updateOrCreate(
                            ['Parent_attd_id'               =>  $parentAttendance->id],
                            [
                                'InTime_out'                =>  $shiftData->StartTime,
                                'OutTime_out'               =>  $shiftData->EndTime,
                                'InTime_Location'           =>  $request->hod_location,
                                'OutTime_Location'          =>  $request->hod_location,
                            ]
                        );
                        $timeAttendance[]                   =   [
                            'emp_id'                        =>  $parentAttendance->Emp_id,
                            'id'                            =>  $parentAttendance->id,
                            'CheckingTime'                  =>  $parentAttendance->CheckingTime,
                            'CheckingOutTime'               =>  $parentAttendance->CheckingOutTime,
                            'Status'                        =>  $parentAttendance->Status,
                            'CheckInCheckOut_Type'          =>  $parentAttendance->CheckInCheckOut_Type,
                        ];
                    }
                }
            }

            // Mark by emp_id: find any existing attendance for today or create from duty_roster_entry, then set Present.
            // Build one response entry per requested emp_id (marked = true with data, or marked = false with reason).
            $markResultByEmpId                              =   [];
            foreach ($empIds as $empId) {
                $empId                                      =   (int) $empId;
                $parentAttendance                           =   ParentAttendace::where('resort_id', $resort_id)
                    ->where('Emp_id', $empId)
                    ->whereDate('date', $currentDate)
                    ->first();
                if (!$parentAttendance) {
                    $emp                                    =   Employee::find($empId);
                    $empResortId                             =   $emp ? (int) $emp->resort_id : null;
                    if ($empResortId && $empResortId !== (int) $resort_id) {
                        $parentAttendance                   =   ParentAttendace::where('resort_id', $empResortId)
                            ->where('Emp_id', $empId)
                            ->whereDate('date', $currentDate)
                            ->first();
                    }
                }

                if (!$parentAttendance) {
                    $rosterEntry                            =   DutyRosterEntry::where('resort_id', $resort_id)
                        ->where('Emp_id', $empId)
                        ->whereDate('date', $currentDate)
                        ->first();
                    if (!$rosterEntry) {
                        $emp                                =   Employee::find($empId);
                        $empResortId                         =   $emp ? (int) $emp->resort_id : null;
                        if ($empResortId && $empResortId !== (int) $resort_id) {
                            $rosterEntry                    =   DutyRosterEntry::where('resort_id', $empResortId)
                                ->where('Emp_id', $empId)
                                ->whereDate('date', $currentDate)
                                ->first();
                        }
                    }
                    if (!$rosterEntry) {
                        $markResultByEmpId[$empId]          =   [
                            'emp_id'                        =>  $empId,
                            'marked'                        =>  false,
                            'message'                       =>  'No duty roster for this date. Ensure roster exists for ' . $currentDate . '.',
                        ];
                        continue;
                    }
                    $rosterResortId                         =   (int) $rosterEntry->resort_id;
                    $shiftData                              =   ShiftSettings::where('resort_id', $rosterResortId)->where('id', $rosterEntry->Shift_id)->first();
                    $startTime                              =   $shiftData ? $shiftData->StartTime : ($rosterEntry->CheckingTime ?? '00:00');
                    $endTime                                =   $shiftData ? $shiftData->EndTime : ($rosterEntry->CheckingOutTime ?? '00:00');
                    $parentAttendance                       =   ParentAttendace::create([
                        'Emp_id'                            =>  $empId,
                        'date'                              =>  $currentDate,
                        'roster_id'                         =>  $rosterEntry->roster_id,
                        'resort_id'                         =>  $rosterResortId,
                        'Shift_id'                          =>  $rosterEntry->Shift_id,
                        'CheckingTime'                      =>  $startTime,
                        'CheckingOutTime'                   =>  $endTime,
                        'DayWiseTotalHours'                 =>  $rosterEntry->DayWiseTotalHours ?? '00:00',
                        'Status'                            =>  'Present',
                        'CheckInCheckOut_Type'              =>  'Manual',
                    ]);
                    ChildAttendace::create([
                        'Parent_attd_id'                    =>  $parentAttendance->id,
                        'InTime_out'                        =>  $startTime,
                        'OutTime_out'                       =>  $endTime,
                        'InTime_Location'                   =>  $request->hod_location,
                        'OutTime_Location'                  =>  $request->hod_location,
                    ]);
                    $timeAttendance[]                       =   [
                        'emp_id'                            =>  $empId,
                        'id'                                =>  $parentAttendance->id,
                        'CheckingTime'                      =>  $parentAttendance->CheckingTime,
                        'CheckingOutTime'                   =>  $parentAttendance->CheckingOutTime,
                        'Status'                            =>  $parentAttendance->Status,
                        'CheckInCheckOut_Type'              =>  $parentAttendance->CheckInCheckOut_Type,
                    ];
                    $markResultByEmpId[$empId]              =   [
                        'emp_id'                            =>  $empId,
                        'marked'                            =>  true,
                        'id'                                =>  $parentAttendance->id,
                        'CheckingTime'                      =>  $parentAttendance->CheckingTime,
                        'CheckingOutTime'                   =>  $parentAttendance->CheckingOutTime,
                        'Status'                            =>  $parentAttendance->Status,
                        'CheckInCheckOut_Type'              =>  $parentAttendance->CheckInCheckOut_Type,
                    ];
                    continue;
                }

                // Update existing row to Present (even if it was Absent or other)
                $attendanceResortId                         =   (int) $parentAttendance->resort_id;
                $shiftData                                  =   ShiftSettings::where('resort_id', $attendanceResortId)->where('id', $parentAttendance->Shift_id)->first();
                $startTime                                  =   $shiftData ? $shiftData->StartTime : ($parentAttendance->CheckingTime ?? '00:00');
                $endTime                                    =   $shiftData ? $shiftData->EndTime : ($parentAttendance->CheckingOutTime ?? '00:00');
                $parentAttendance->CheckingTime             =   $startTime;
                $parentAttendance->CheckingOutTime          =   $endTime;
                $parentAttendance->Status                   =   'Present';
                $parentAttendance->CheckInCheckOut_Type     =   'Manual';
                $parentAttendance->save();

                ChildAttendace::updateOrCreate(
                    ['Parent_attd_id'                       =>  $parentAttendance->id],
                    [
                        'InTime_out'                        =>  $startTime,
                        'OutTime_out'                       =>  $endTime,
                        'InTime_Location'                   =>  $request->hod_location,
                        'OutTime_Location'                  =>  $request->hod_location,
                    ]
                );
                $timeAttendance[]                          =   [
                    'emp_id'                                =>  $parentAttendance->Emp_id,
                    'id'                                    =>  $parentAttendance->id,
                    'CheckingTime'                         =>  $parentAttendance->CheckingTime,
                    'CheckingOutTime'                      =>  $parentAttendance->CheckingOutTime,
                    'Status'                                =>  $parentAttendance->Status,
                    'CheckInCheckOut_Type'                 =>  $parentAttendance->CheckInCheckOut_Type,
                ];
                $markResultByEmpId[$empId]                  =   [
                    'emp_id'                                =>  $parentAttendance->Emp_id,
                    'marked'                                =>  true,
                    'id'                                    =>  $parentAttendance->id,
                    'CheckingTime'                         =>  $parentAttendance->CheckingTime,
                    'CheckingOutTime'                      =>  $parentAttendance->CheckingOutTime,
                    'Status'                                =>  $parentAttendance->Status,
                    'CheckInCheckOut_Type'                 =>  $parentAttendance->CheckInCheckOut_Type,
                ];
            }

            // Response: when emp_id was sent, one entry per requested emp_id (same order); else use timeAttendance (attendance_id path)
            if (!empty($empIds)) {
                $mark_attendance_response                    =   [];
                foreach ($empIds as $eid) {
                    $eid                                    =   (int) $eid;
                    $mark_attendance_response[]             =   $markResultByEmpId[$eid] ?? [
                        'emp_id'                            =>  $eid,
                        'marked'                            =>  false,
                        'message'                           =>  'No duty roster for today or could not update.',
                    ];
                }
                $response['mark_attendance']                =   $mark_attendance_response;
            } else {
                $response['mark_attendance']                =   $timeAttendance;
            }

            if (empty($timeAttendance)) {
                DB::rollBack();
                if (empty($empIds)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No attendance records updated.',
                    ], 422);
                }
                $response['status']                         =   true;
                $response['message']                        =   'No employees could be marked present. Check mark_attendance for each emp_id reason.';
                return response()->json($response);
            }

            DB::commit();
            $response['status']                             =   true;
            $response['message']                            =   'Attendance marked successfully.';
            return response()->json($response);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function approveRejectOT(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Validate request data
        $validator = Validator::make($request->all(), [
            'attendance_id'                                 =>  'required',  // Must be an array with at least 1 entry
            'ot_status'                                     =>  'required|in:Approved,Rejected',
            'note'                                          =>  'required_if:ot_status,Rejected'
        ]);


        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        $user                                               =   Auth::guard('api')->user();
        $employee                                           =   $user->GetEmployee;
        $emp_id                                             =   $employee->id;
        $resort_id                                          =   $user->resort_id;

        try {
            $attendaceId                                    =   $request->attendance_id;

            DB::beginTransaction(); // Start transaction

            $parentAttendance                               =   ParentAttendace::where('resort_id', $resort_id)->whereNotNull('OverTime')->whereNotNull('CheckingTime')->whereNotNull('CheckingOutTime')->where('id', $attendaceId)->where('Status', 'present')->first();
            if ($parentAttendance) {
                $shiftData                              =   ShiftSettings::where('resort_id', $resort_id)->where('id', $parentAttendance->Shift_id)->first();

                // Convert "HH:mm" to total minutes function
                function timeToMinutes($time)
                {
                    list($hours, $minutes)              = explode(':', $time);
                    return ((int)$hours * 60) + (int)$minutes;
                }

                // Convert both times to minutes
                $dayWiseTotalMinutes                    = timeToMinutes($parentAttendance->DayWiseTotalHours);
                $shiftTotalMinutes                      = timeToMinutes($shiftData->TotalHours);

                // Compare after normalization
                if ($shiftData) {
                    if ($request->ot_status == 'Approved') {
                        if ($dayWiseTotalMinutes === $shiftTotalMinutes || $shiftTotalMinutes <= $dayWiseTotalMinutes) {
                            $parentAttendance->OTApproved_By =   $emp_id;
                            $parentAttendance->OTStatus      =   $request->ot_status;
                            $parentAttendance->save();
                        }
                    } else {
                        $parentAttendance->DayWiseTotalHours    =   $shiftData->TotalHours;
                        $parentAttendance->OTApproved_By        =   $emp_id;
                        $parentAttendance->note                 =   $request->note;
                        $parentAttendance->OTStatus             =   $request->ot_status;
                        $parentAttendance->save();
                    }
                }
            }

            DB::commit(); // Commit transaction
            $response['status']                             =   true;
            $response['message']                            =   'Over time is ' . $request->ot_status;
            $response['attendance_data']                    =   $parentAttendance;

            return response()->json($response);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function checkEmployeeAllTimes()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $user                                               =   Auth::guard('api')->user();
        $employee                                           =   $user->GetEmployee;
        $emp_id                                             =   $employee->id;
        $resort_id                                          =   $user->resort_id;
        $today                                              =   Carbon::now()->format('Y-m-d');
        try {
            $attendance                                     =   Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                                                                ->join('duty_rosters as t2', "t2.Emp_id", "=", "employees.id")
                                                                ->join('parent_attendaces as t3', "t3.roster_id", "=", "t2.id")
                                                                ->leftjoin('child_attendaces as t4', "t4.Parent_attd_id", "=", "t3.id")
                                                                ->join('shift_settings as ss', 'ss.id', '=', 't3.Shift_id')
                                                                ->where('t3.date', $today) // Use the formatted date
                                                                ->where("t1.resort_id", $resort_id)
                                                                ->where('employees.id', $emp_id)
                                                                ->select('t1.first_name','t1.last_name','t3.id','t3.CheckingTime','t3.CheckingOutTime','t3.DayWiseTotalHours')
                                                                ->first();
            if (!$attendance) {
                return response()->json([
                    'status' => false,
                    'message' => 'No attendance record found for today.',
                    'attendance_data' => null,
                ]);
            }

            $attendance->break_data                         =   BreakAttendaces::where('Parent_attd_id', $attendance->id)->get();

            return response()->json([
                'status'                                    =>  true,
                'message'                                   =>  'Employee attendance and break times fetched successfully.',
                'emp_attendance_data'                           =>  $attendance,
            ]);


        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function getEmployeesDayAndMonthDataList(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $user = Auth::guard('api')->user();
        $employee = $user->GetEmployee;
        $emp_id = $employee->id;
        $reporting_to = $emp_id;
        $underEmp_id = Common::getSubordinates($reporting_to);
        $resort_id = $user->resort_id;
        $Rank = $employee->rank;
        $positionRankConfig = config('settings.Position_Rank', []);
        $finalRankConfig = config('settings.final_rank', []);
        $available_rank = $positionRankConfig[$Rank] ?? $finalRankConfig[$Rank] ?? '';
        $isHR = ($available_rank === "HR");
        $isHOD = ($available_rank === "HOD");

        try {
            // Get current day and month dates
            $currentDate = Carbon::now()->format('Y-m-d');
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();
            $WeekstartDate = Carbon::now()->startOfWeek();
            $WeekendDate = Carbon::now()->endOfWeek();

            // Build employee query
            $employeeQuery = Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                ->join('resort_positions as t2', "t2.id", "=", "employees.Position_id")
                ->join('duty_rosters as t3', "t3.Emp_id", "=", "employees.id")
                ->leftjoin('parent_attendaces as t4', "t4.resort_id", "=", "t3.id")
                ->select(
                    't1.id as Parentid',
                    't1.first_name',
                    't1.last_name',
                    't1.profile_picture',
                    'employees.id as emp_id',
                    'employees.Emp_id as EmployeeId',
                    't2.position_title',
                    't3.id as duty_roster_id'
                )
                ->groupBy('employees.id')
                ->where("t1.resort_id", $resort_id)
                ->where('employees.status', 'Active');

            // Apply filters based on user role
            if ($isHR) {
                // HR can filter by department if provided
                if ($request->has('department_id') && !empty($request->department_id)) {
                    $employeeQuery->where('employees.Dept_id', $request->department_id);
                }
            } elseif ($isHOD) {
                // HOD sees only their subordinates
                if (empty($underEmp_id)) {
                    $underEmp_id = [$emp_id];
                }
                $employeeQuery->whereIn('employees.id', $underEmp_id);
            } else {
                // Other ranks see only their subordinates
                if (empty($underEmp_id)) {
                    $underEmp_id = [$emp_id];
                }
                $employeeQuery->whereIn('employees.id', $underEmp_id);
            }

            // Apply search filter if provided
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $employeeQuery->where(function ($query) use ($search) {
                    $query->where('t1.first_name', 'LIKE', "%$search%")
                        ->orWhere('t1.last_name', 'LIKE', "%$search%")
                        ->orWhere('employees.Emp_id', 'LIKE', "%$search%");
                });
            }

            // Apply position filter if provided
            if ($request->has('position_id') && !empty($request->position_id)) {
                $employeeQuery->where('employees.Position_id', $request->position_id);
            }

            // Get paginated results
            $perPage = $request->get('per_page', 10);
            $page = $request->get('page', 1);
            $paginatedResults = $employeeQuery->paginate($perPage, ['*'], 'page', $page);

            // Transform the paginated collection for DAY data (current day)
            $dayEmployees = $paginatedResults->getCollection()->map(function ($item) use ($resort_id, $currentDate) {
                $item->EmployeeName = ucfirst($item->first_name . ' ' . $item->last_name);
                $item->Position = ucfirst($item->position_title);
                $item->profileImg = Common::getResortUserPicture($item->Parentid);

                // Get attendance data for current day
                $todayAttendance = ParentAttendace::join('duty_rosters as t2', 't2.id', '=', 'parent_attendaces.roster_id')
                    ->join('shift_settings as t1', 't1.id', '=', 'parent_attendaces.Shift_id')
                    ->where('parent_attendaces.Emp_id', $item->emp_id)
                    ->whereDate('parent_attendaces.date', $currentDate)
                    ->where('t1.resort_id', $resort_id)
                    ->select(
                        'parent_attendaces.Status',
                        'parent_attendaces.CheckingTime',
                        'parent_attendaces.CheckingOutTime',
                        'parent_attendaces.OverTime',
                        'parent_attendaces.date'
                    )
                    ->first();

                // Calculate day statistics
                $presentCount = 0;
                $absentCount = 0;
                $leaveCount = 0;
                $totalOtHours = 0;

                if ($todayAttendance) {
                    if ($todayAttendance->Status == "Present" && !empty($todayAttendance->CheckingTime) && trim($todayAttendance->CheckingTime ?? '') !== '' && !in_array(trim($todayAttendance->CheckingTime ?? ''), ['00:00', '00:00:00'])) {
                        $presentCount = 1;
                        if (!empty($todayAttendance->OverTime) && $todayAttendance->OverTime != "-" && $todayAttendance->OverTime != "00:00") {
                            $otParts = explode(':', (string) ($todayAttendance->OverTime ?? '0:0'));
                            $totalOtHours = (int)($otParts[0] ?? 0) + ((int)($otParts[1] ?? 0) / 60);
                        }
                    } elseif ($todayAttendance->Status == "Absent") {
                        $hasCheckIn = !empty($todayAttendance->CheckingTime) && trim($todayAttendance->CheckingTime ?? '') !== '' && !in_array(trim($todayAttendance->CheckingTime ?? ''), ['00:00', '00:00:00']);
                        if (!$hasCheckIn) {
                            $absentCount = 1;
                        }
                    }
                }

                // Check for leave on current day
                $leaveData = EmployeeLeave::join('leave_categories as lc', 'lc.id', '=', 'employees_leaves.leave_category_id')
                    ->where('employees_leaves.emp_id', $item->emp_id)
                    ->where('employees_leaves.status', 'Approved')
                    ->whereDate('employees_leaves.from_date', '<=', $currentDate)
                    ->whereDate('employees_leaves.to_date', '>=', $currentDate)
                    ->first();

                if ($leaveData) {
                    $leaveCount = 1;
                }

                return [
                    'Parentid' => $item->Parentid,
                    'first_name' => $item->first_name,
                    'last_name' => $item->last_name,
                    'profile_picture' => $item->profile_picture,
                    'emp_id' => $item->emp_id,
                    'EmployeeId' => $item->EmployeeId,
                    'position_title' => $item->position_title,
                    'duty_roster_id' => $item->duty_roster_id,
                    'EmployeeName' => $item->EmployeeName,
                    'Position' => $item->Position,
                    'profileImg' => $item->profileImg,
                    'present_count' => $presentCount,
                    'absent_count' => $absentCount,
                    'leave_count' => $leaveCount,
                    'total_ot_hours' => round($totalOtHours),
                ];
            });

            // Transform the paginated collection for MONTH data
            $monthEmployees = $paginatedResults->getCollection()->map(function ($item) use ($resort_id, $WeekstartDate, $WeekendDate, $startOfMonth, $endOfMonth) {
                $item->EmployeeName = ucfirst($item->first_name . ' ' . $item->last_name);
                $item->Position = ucfirst($item->position_title);
                $item->profileImg = Common::getResortUserPicture($item->Parentid);

                // Get attendance register data for the month
                $RosterInternalDataMonth = Common::GetAttandanceRegister(
                    $resort_id,
                    $item->duty_roster_id,
                    $item->emp_id,
                    $WeekstartDate,
                    $WeekendDate,
                    $startOfMonth,
                    $endOfMonth,
                    "Monthwise"
                );

                // Calculate summary statistics for month (per-day: unique dates; present only when has check-in)
                $presentDates = [];
                $absentDates = [];
                $leaveDates = [];
                $totalOtHours = 0;

                foreach ($RosterInternalDataMonth as $shiftData) {
                    $d = isset($shiftData->date) ? (is_object($shiftData->date) ? $shiftData->date->format('Y-m-d') : $shiftData->date) : null;
                    if (!$d) continue;
                    $hasCheckIn = !empty($shiftData->CheckingTime) && trim($shiftData->CheckingTime ?? '') !== '' && !in_array(trim($shiftData->CheckingTime ?? ''), ['00:00', '00:00:00']);
                    if ($shiftData->Status == "Present" && $hasCheckIn) {
                        $presentDates[$d] = true;
                        if (!empty($shiftData->OverTime) && $shiftData->OverTime != "-" && $shiftData->OverTime != "00:00") {
                            $otParts = explode(':', (string) ($shiftData->OverTime ?? '0:0'));
                            $totalOtHours += (int)($otParts[0] ?? 0) + ((int)($otParts[1] ?? 0) / 60);
                        }
                    } elseif ($shiftData->Status == "Absent" && !$hasCheckIn) {
                        $absentDates[$d] = true;
                    } elseif (isset($shiftData->LeaveData) && is_array($shiftData->LeaveData) && !empty($shiftData->LeaveData)) {
                        $leaveDates[$d] = true;
                    }
                }
                $presentCount = count($presentDates);
                $absentCount = count($absentDates);
                $leaveCount = count($leaveDates);

                return [
                    'Parentid' => $item->Parentid,
                    'first_name' => $item->first_name,
                    'last_name' => $item->last_name,
                    'profile_picture' => $item->profile_picture,
                    'emp_id' => $item->emp_id,
                    'EmployeeId' => $item->EmployeeId,
                    'position_title' => $item->position_title,
                    'duty_roster_id' => $item->duty_roster_id,
                    'EmployeeName' => $item->EmployeeName,
                    'Position' => $item->Position,
                    'profileImg' => $item->profileImg,
                    'present_count' => $presentCount,
                    'absent_count' => $absentCount,
                    'leave_count' => $leaveCount,
                    'total_ot_hours' => round($totalOtHours),
                ];
            });

            $response['status'] = true;
            $response['message'] = 'Employee list with attendance summary retrieved successfully.';
            $response['day_data'] = $dayEmployees;
            $response['month_data'] = $monthEmployees;
            $response['pagination'] = [
                'current_page' => $paginatedResults->currentPage(),
                'last_page' => $paginatedResults->lastPage(),
                'per_page' => $paginatedResults->perPage(),
                'total' => $paginatedResults->total(),
            ];

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            \Log::error("Stack trace: " . $e->getTraceAsString());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function getEmployeeMonthDataPreviewList(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Validate request data
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        $user = Auth::guard('api')->user();
        $resort_id = $user->resort_id;
        $employee_id = $request->employee_id;

        try {
            // Get employee details
            $employee = Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                ->join('resort_positions as t2', "t2.id", "=", "employees.Position_id")
                ->join('duty_rosters as t3', "t3.Emp_id", "=", "employees.id")
                ->select(
                    't1.id as Parentid',
                    't1.first_name',
                    't1.last_name',
                    't1.profile_picture',
                    'employees.id as emp_id',
                    'employees.Emp_id as EmployeeId',
                    't2.position_title',
                    't3.id as duty_roster_id'
                )
                ->where('employees.id', $employee_id)
                ->where("t1.resort_id", $resort_id)
                ->first();

            if (!$employee) {
                return response()->json(['success' => false, 'message' => 'Employee not found'], 404);
            }

            // Get current month dates
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();
            $WeekstartDate = Carbon::now()->startOfWeek();
            $WeekendDate = Carbon::now()->endOfWeek();

            // Get attendance register data for the month
            $RosterInternalDataMonth = Common::GetAttandanceRegister(
                $resort_id,
                $employee->duty_roster_id,
                $employee->emp_id,
                $WeekstartDate,
                $WeekendDate,
                $startOfMonth,
                $endOfMonth,
                "Monthwise"
            );

            // Get all days of current month
            $year = now()->year;
            $month = now()->month;
            $totalDays = Carbon::createFromDate($year, $month, 1)->daysInMonth;

            $monthDays = [];
            for ($day = 1; $day <= $totalDays; $day++) {
                $date = Carbon::createFromDate($year, $month, $day);
                $dateString = $date->format('Y-m-d');
                $dayName = $date->format('D');
                $isWeekend = in_array($dayName, ['Sat', 'Sun']);

                // Find attendance data for this date
                $shiftData = $RosterInternalDataMonth->firstWhere('date', $dateString);

                $dayData = [
                    'date' => $dateString,
                    'day' => str_pad($day, 2, '0', STR_PAD_LEFT),
                    'day_name' => $dayName,
                    'is_weekend' => $isWeekend,
                ];

                if ($shiftData) {
                    // Format checking times
                    $checkInTimeParsed = null;
                    $checkOutTimeParsed = null;

                    if (!empty($shiftData->CheckingTime)) {
                        try {
                            $checkInTimeParsed = Carbon::parse($shiftData->CheckingTime);
                            $dayData['checking_time'] = $checkInTimeParsed->format('H:i:s');
                            $dayData['checking_time_formatted'] = $checkInTimeParsed->format('h:i A');
                        } catch (\Exception $e) {
                            $dayData['checking_time'] = null;
                            $dayData['checking_time_formatted'] = '--:--';
                        }
                    } else {
                        $dayData['checking_time'] = null;
                        $dayData['checking_time_formatted'] = '--:--';
                    }

                    if (!empty($shiftData->CheckingOutTime)) {
                        try {
                            $checkOutTimeParsed = Carbon::parse($shiftData->CheckingOutTime);
                            $dayData['checking_out_time'] = $checkOutTimeParsed->format('H:i:s');
                            $dayData['checking_out_time_formatted'] = $checkOutTimeParsed->format('h:i A');
                        } catch (\Exception $e) {
                            $dayData['checking_out_time'] = null;
                            $dayData['checking_out_time_formatted'] = '--:--';
                        }
                    } else {
                        $dayData['checking_out_time'] = null;
                        $dayData['checking_out_time_formatted'] = '--:--';
                    }

                    $dayData['status'] = $shiftData->Status ?? null;
                    $dayData['overtime'] = $shiftData->OverTime ?? null;
                    $dayData['shift_name'] = $shiftData->ShiftName ?? null;
                    $dayData['start_time'] = $shiftData->StartTime ?? null;
                    $dayData['end_time'] = $shiftData->EndTime ?? null;
                    $dayData['day_wise_total_hours'] = $shiftData->DayWiseTotalHours ?? null;

                    // Process leave data
                    $leaveInfo = null;
                    if (isset($shiftData->LeaveData) && is_array($shiftData->LeaveData) && !empty($shiftData->LeaveData)) {
                        // Find leave that covers this date
                        foreach ($shiftData->LeaveData as $leaveData) {
                            $fromDate = Carbon::parse($leaveData['from_date']);
                            $toDate = Carbon::parse($leaveData['to_date']);
                            $currentDate = Carbon::parse($dateString);

                            if ($currentDate->between($fromDate, $toDate)) {
                                // Get leave category details
                                $leaveCategory = \App\Models\LeaveCategory::where('id', $leaveData['leave_cat_id'])
                                    ->where('resort_id', $resort_id)
                                    ->first(['leave_type', 'color']);

                                $leaveInfo = [
                                    'leave_category_id' => $leaveData['leave_cat_id'],
                                    'leave_category_name' => $leaveCategory->leave_type ?? $leaveData['leave_type'] ?? null,
                                    'leave_color' => $leaveCategory->color ?? null,
                                    'from_date' => $leaveData['from_date'],
                                    'to_date' => $leaveData['to_date'],
                                    'total_days' => $leaveData['total_days'] ?? null,
                                ];
                                break;
                            }
                        }
                    }

                    $dayData['leave_info'] = $leaveInfo;
                    $dayData['is_day_off'] = ($shiftData->Status == "DayOff");
                } else {
                    // No attendance data for this date
                    $dayData['checking_time'] = null;
                    $dayData['checking_time_formatted'] = '--:--';
                    $dayData['checking_out_time'] = null;
                    $dayData['checking_out_time_formatted'] = '--:--';
                    $dayData['status'] = null;
                    $dayData['overtime'] = null;
                    $dayData['shift_name'] = null;
                    $dayData['start_time'] = null;
                    $dayData['end_time'] = null;
                    $dayData['day_wise_total_hours'] = null;
                    $dayData['leave_info'] = null;
                    $dayData['is_day_off'] = false;
                }

                $monthDays[] = $dayData;
            }

            // Prepare employee info
            $employeeInfo = [
                'employee_id' => $employee->emp_id,
                'employee_code' => $employee->EmployeeId,
                'employee_name' => ucfirst($employee->first_name . ' ' . $employee->last_name),
                'position' => ucfirst($employee->position_title),
                'profile_picture' => Common::getResortUserPicture($employee->Parentid),
            ];

            $response['status'] = true;
            $response['message'] = 'Employee month data retrieved successfully.';
            $response['employee_info'] = $employeeInfo;
            $response['month_data'] = $monthDays;
            $response['month'] = $month;
            $response['year'] = $year;

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            \Log::error("Stack trace: " . $e->getTraceAsString());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * Calculate shift duration from StartTime and EndTime (handles overnight e.g. 20:00 to 04:00 = 8 hours).
     * Returns format "HH:MM" (max 23:59 for single day).
     */
    protected static function calculateShiftDuration($startTime, $endTime)
    {
        $start = Carbon::createFromFormat('H:i', trim($startTime));
        if (!$start) {
            $start = Carbon::createFromFormat('H:i:s', trim($startTime));
        }
        $end = Carbon::createFromFormat('H:i', trim($endTime));
        if (!$end) {
            $end = Carbon::createFromFormat('H:i:s', trim($endTime));
        }
        if (!$start || !$end) {
            return '00:00';
        }
        $endCopy = $end->copy();
        if ($endCopy->lte($start)) {
            $endCopy->addDay();
        }
        $totalMinutes = $start->diffInMinutes($endCopy);
        $hours        = (int) floor($totalMinutes / 60);
        $minutes      = (int) ($totalMinutes % 60);
        if ($hours >= 24) {
            $hours   = 23;
            $minutes = 59;
        }
        return sprintf('%02d:%02d', $hours, $minutes);
    }
}
