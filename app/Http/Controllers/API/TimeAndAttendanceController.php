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
                $totalPresentEmployee                       =   Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                                                                    ->join('duty_rosters as t2', "t2.Emp_id", "=", "employees.id")
                                                                    ->join('parent_attendaces as t3', "t3.roster_id", "=", "t2.id")
                                                                    ->whereNotNull('t3.CheckingTime')
                                                                    ->whereNotNull('t3.CheckingOutTime')
                                                                    ->where('t3.Status', 'Present') // Ensure 'Present' is a string
                                                                    ->where('t3.date', $formattedDate) // Use the formatted date
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

    public function employeeDutyRoster(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Validate request data
        $validator = Validator::make($request->all(), [
            'filter'                                        => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        $user                                               =  Auth::guard('api')->user();
        $employee                                           =  $user->GetEmployee;

        try {

            $WeekstartDate                                  =   '';
            $WeekendDate                                    =   '';
            $startOfMonth                                   =   '';
            $endOfMonth                                     =   '';
            $Rosterdata                                     =   Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                                                                    ->join('resort_positions as t2', "t2.id", "=", "employees.Position_id")
                                                                    ->join('duty_rosters as t3', "t3.Emp_id", "=", "employees.id")
                                                                    ->join('parent_attendaces as t4', "t4.roster_id", "=", "t3.id")
                                                                    ->select('t3.id as duty_roster_id', 't3.DayOfDate', 't1.id as Parentid', 't1.first_name', 't1.last_name', 't1.profile_picture', 'employees.id as emp_id', 't2.position_title')
                                                                    ->where('employees.id', $employee->id)
                                                                    ->get();
            $timeAttendanceData                             =   [];

            $WeekstartDate                                  =   Carbon::now()->startOfWeek();
            $WeekendDate                                    =   Carbon::now()->endOfWeek();
            $startOfMonth                                   =   Carbon::now()->startOfMonth(); // Get the first day of the month
            $endOfMonth                                     =   Carbon::now()->endOfMonth(); // Get the last day of the month
            $year                                           =   $request->year;
            $month                                          =   $request->month;


            foreach ($Rosterdata as $roster) {

                if ($request->filter === 'weekly') {
                    $RosterInternalData                     =   Common::dutyRosterMonthAndWeekWise($user->resort_id, $roster->duty_roster_id, $roster->emp_id, $WeekstartDate, $WeekendDate, '', '', '', '', 'weekly');
                } elseif ($request->filter === 'monthly') {
                    $RosterInternalData                     =   Common::dutyRosterMonthAndWeekWise($user->resort_id, $roster->duty_roster_id, $roster->emp_id, '', '',  $startOfMonth, $endOfMonth, $year, $month, 'Monthwise');
                }

                $timeAttendanceData                         =   $RosterInternalData;
            }

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

    public function timeAttendanceHODDashboard(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Validate request data
        $validator = Validator::make($request->all(), [
            'filter'                                        => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        $user                                               =   Auth::guard('api')->user();
        $employee                                           =   $user->GetEmployee;
        $emp_id                                             =   $employee->id;
        $reporting_to                                       =   $emp_id;
        $Rank                                               =   $employee->rank;
        $underEmp_id                                        =   Common::getSubordinates($reporting_to);
        $resort_id                                          =   $user->resort_id;

        try {

            if ($request->filter === 'weekly') {
                $startDate                                  =   Carbon::now()->startOfWeek();
                $endDate                                    =   Carbon::now()->endOfWeek();
            } else {
                $startDate                                  =   Carbon::now()->startOfMonth();
                $endDate                                    =   Carbon::now()->endOfMonth();
            }

            // Handle empty subordinates array - if empty, use HOD's own ID
            if (empty($underEmp_id)) {
                $underEmp_id = [$emp_id];
            }

            // Count total employees (subordinates)
            $employeesCountQuery = Employee::join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                ->where('t1.resort_id', $resort_id)
                ->where('employees.status', 'Active')
                ->whereIn('employees.id', $underEmp_id);

            $employeesCount = $employeesCountQuery->distinct()->count('employees.id');
            $employeesCount = $employeesCount ?? 0;

            // Count present employees - distinct count to avoid duplicates
            $totalPresentEmployeeQuery = Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                ->join('duty_rosters as t2', "t2.Emp_id", "=", "employees.id")
                ->join('parent_attendaces as t3', "t3.roster_id", "=", "t2.id")
                ->whereNotNull('t3.CheckingTime')
                ->whereNotNull('t3.CheckingOutTime')
                ->whereIn('t3.Status', ['Present', 'On-Time', 'Late']) // Include all present statuses
                ->whereBetween('t3.date', [$startDate->toDateString(), $endDate->toDateString()])
                ->where("t1.resort_id", $resort_id)
                ->whereIn('employees.id', $underEmp_id);

            $totalPresentEmployee = $totalPresentEmployeeQuery->distinct()->count('employees.id');
            $totalPresentEmployee = $totalPresentEmployee ?? 0;

            // Count absent employees - handle both 'Absent' and 'Absant' (typo in DB)
            $totalAbsentEmployeeQuery = Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                ->join('duty_rosters as t2', "t2.Emp_id", "=", "employees.id")
                ->join('parent_attendaces as t3', "t3.roster_id", "=", "t2.id")
                ->whereBetween('t3.date', [$startDate->toDateString(), $endDate->toDateString()])
                ->whereIn('t3.Status', ['Absent', 'Absant']) // Handle both spellings
                ->where(function($query) {
                    $query->whereNull('t3.CheckingTime')
                        ->orWhereNull('t3.CheckingOutTime');
                })
                ->where("t1.resort_id", $resort_id)
                ->whereIn('employees.id', $underEmp_id);

            $totalAbsentEmployee = $totalAbsentEmployeeQuery->distinct()->count('employees.id');
            $totalAbsentEmployee = $totalAbsentEmployee ?? 0;

            // Get OT Approved employees
            $employeeOTApprvoedQuery = Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                ->join('duty_rosters as t2', "t2.Emp_id", "=", "employees.id")
                ->join('parent_attendaces as t3', "t3.roster_id", "=", "t2.id")
                ->whereNotNull('t3.OverTime')
                ->where('t3.OTStatus', 'Approved')
                ->whereBetween('t3.date', [$startDate->toDateString(), $endDate->toDateString()])
                ->where("t1.resort_id", $resort_id)
                ->whereIn('employees.id', $underEmp_id);

            $employeeOTApprvoed = $employeeOTApprvoedQuery
                ->select('t3.id', 't1.first_name', 't1.last_name', 't3.OverTime', 't3.Emp_id', 't1.id as Admin_Parent_id')
                ->get();

            // Convert OverTime to Minutes and Sum
            $OTApprvoedMin = 0;
            if ($employeeOTApprvoed->isNotEmpty()) {
                $OTApprvoedMin = $employeeOTApprvoed->sum(function ($item) {
                    if (!empty($item->OverTime) && strpos($item->OverTime, ':') !== false) {
                        list($hours, $minutes) = explode(':', $item->OverTime);
                        return ((int)$hours * 60) + (int)$minutes;
                    }
                    return 0;
                });
            }

            // Convert Total Minutes Back to HH:mm Format
            $totalOTHrsApproved = floor($OTApprvoedMin / 60) . ':' . str_pad($OTApprvoedMin % 60, 2, '0', STR_PAD_LEFT);

            // Get OT Request employees - include Admin_Parent_id for profile picture
            $employeeOTReqQuery = Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                ->join('duty_rosters as t2', "t2.Emp_id", "=", "employees.id")
                ->join('parent_attendaces as t3', "t3.roster_id", "=", "t2.id")
                ->whereNotNull('t3.OverTime')
                ->whereNotNull('t3.CheckingTime')
                ->whereNotNull('t3.CheckingOutTime')
                ->where(function ($query) {
                    $query->whereNull('t3.OTStatus')
                        ->orWhereNotIn('t3.OTStatus', ['Approved', 'Rejected']);
                })
                ->whereBetween('t3.date', [$startDate->toDateString(), $endDate->toDateString()])
                ->where("t1.resort_id", $resort_id)
                ->whereIn('employees.id', $underEmp_id);

            $employeeOTReq = $employeeOTReqQuery
                ->select('t3.id', 't1.first_name', 't1.last_name', 't3.OverTime', 't3.Emp_id', 't3.OTStatus', 't3.CheckingTime', 't3.CheckingOutTime', 't3.DayWiseTotalHours', 't1.id as Admin_Parent_id')
                ->get();

            // Convert OverTime to Minutes and Sum
            $OTReqMin = 0;
            if ($employeeOTReq->isNotEmpty()) {
                $OTReqMin = $employeeOTReq->sum(function ($item) {
                    if (!empty($item->OverTime) && strpos($item->OverTime, ':') !== false) {
                        list($hours, $minutes) = explode(':', $item->OverTime);
                        return ((int)$hours * 60) + (int)$minutes;
                    }
                    return 0;
                });
            }

            // Convert Total Minutes Back to HH:mm Format
            $totalOTHrsReq = floor($OTReqMin / 60) . ':' . str_pad($OTReqMin % 60, 2, '0', STR_PAD_LEFT);

            // Map OT requests and add profile pictures
            $employeeOTReq = $employeeOTReq->map(function ($item) {
                $item->profile_picture = Common::getResortUserPicture($item->Admin_Parent_id);
                return $item;
            })->toArray();

            // Calculate leave days for each date in range
            $dates = [];
            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                $formattedDate = $date->format('Y-m-d');

                $totalLeaveEmployeeQuery = Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                    ->join('employees_leaves as t2', "t2.emp_id", "=", "employees.id")
                    ->where('t2.from_date', "<=", $formattedDate)
                    ->where('t2.to_date', ">=", $formattedDate)
                    ->where("t1.resort_id", $resort_id)
                    ->whereIn('employees.id', $underEmp_id)
                    ->where("employees.rank", "!=", $Rank);

                $totalLeaveEmployee = $totalLeaveEmployeeQuery->distinct()->count('employees.id');
                $totalLeaveEmployee = $totalLeaveEmployee ?? 0;

                $dates[] = [
                    'leave' => $totalLeaveEmployee,
                ];
            }

            $dates[] = [
                'present' => $totalPresentEmployee,
                'absent' => $totalAbsentEmployee,
                'employee' => $employeesCount,
            ];

            $totalPresentDays = array_sum(array_column($dates, 'present'));
            $totalAbsentDays = array_sum(array_column($dates, 'absent'));
            $totalLeaveDays = array_sum(array_column($dates, 'leave'));

            $timeAttendanceData['total_present_days'] = $totalPresentDays;
            $timeAttendanceData['total_absent_days'] = $totalAbsentDays;
            $timeAttendanceData['total_leave_days'] = $totalLeaveDays;
            $timeAttendanceData['employee'] = $employeesCount;
            $timeAttendanceData['ot_approved_hrs'] = $totalOTHrsApproved;
            $timeAttendanceData['ot_request_hrs'] = $totalOTHrsReq;
            $timeAttendanceData['over_time_request'] = $employeeOTReq;

            $response['status'] = true;
            $response['message'] = 'Time TimeAttendance HOD Dashboard';
            $response['time_attendance'] = $timeAttendanceData;

            return response()->json($response);
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


            if ($request->filter === 'weekly') {
                $startDate                                  =   Carbon::now()->startOfWeek();
                $endDate                                    =   Carbon::now()->endOfWeek();
            } else {
                $startDate                                  =   Carbon::now()->startOfMonth();
                $endDate                                    =   Carbon::now()->endOfMonth();
            }

            $ResortPosition                                 =   ResortPosition::where("resort_id", $resort_id)->get();
            $ResortDepartment                               =   ResortDepartment::where("resort_id", $resort_id)->get();
            $employeesCount                                 =   Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                ->where("t1.resort_id", $resort_id)
                ->count();

            $employeesCount                                 =   $employeesCount ?? 0;
            $totalPresentEmployee                           =   Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                ->join('duty_rosters as t2', "t2.Emp_id", "=", "employees.id")
                ->join('parent_attendaces as t3', "t3.roster_id", "=", "t2.id")
                ->whereNotNull('t3.CheckingTime')
                ->whereNotNull('t3.CheckingOutTime')
                ->whereIn('t3.Status', ['Present', 'HalfDay', 'On-Time', 'Late', 'ShortLeave', 'HalfDayLeave'])
                ->whereBetween('t3.date', [$startDate->toDateString(), $endDate->toDateString()])
                ->where("t1.resort_id", $resort_id)
                ->count();

            $totalPresentEmployee                           =   $totalPresentEmployee ?? 0;

            $totalAbsentEmployee                            =   Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                ->join('duty_rosters as t2', "t2.Emp_id", "=", "employees.id")
                ->join('parent_attendaces as t3', "t3.roster_id", "=", "t2.id")
                // ->where("t3.date",$formattedDate)
                ->whereBetween('t3.date', [$startDate->toDateString(), $endDate->toDateString()])
                ->where("t3.Status", "Absent")
                ->whereNull('t3.CheckingTime')
                ->whereNull('t3.CheckingOutTime')
                ->where("t1.resort_id", $resort_id)
                ->count();

            $employeeOTApprvoed                             =   Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                ->join('duty_rosters as t2', "t2.Emp_id", "=", "employees.id")
                ->join('parent_attendaces as t3', "t3.roster_id", "=", "t2.id")
                ->whereNotNull('t3.OverTime')
                ->where('t3.OTStatus', 'Approved')
                ->whereBetween('t3.date', [$startDate->toDateString(), $endDate->toDateString()])
                ->where("t1.resort_id", $resort_id)
                ->select('t3.id', 't1.first_name', 't1.last_name', 't3.OverTime', 't3.Emp_id')
                ->get();

            // Convert OverTime to Minutes and Sum
            $OTApprvoedMin                                  =   $employeeOTApprvoed->sum(function ($item) {
                list($hours, $minutes) = explode(':', $item->OverTime);  // Convert "HH:mm" to [hours, minutes]
                return ((int)$hours * 60) + (int)$minutes;  // Convert to total minutes
            });
            // Convert Total Minutes Back to HH:mm Format
            $totalOTHrsApproved                             =   floor($OTApprvoedMin / 60) . ':' . str_pad($OTApprvoedMin % 60, 2, '0', STR_PAD_LEFT);

            $employeeOTReq                                  =   Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                ->join('duty_rosters as t2', "t2.Emp_id", "=", "employees.id")
                ->join('parent_attendaces as t3', "t3.roster_id", "=", "t2.id")
                ->whereNotNull('t3.OverTime')
                ->whereNotNull('t3.CheckingTime')
                ->whereNotNull('t3.CheckingOutTime')
                ->where(function ($query) {
                    $query->whereNull('t3.OTStatus') // Include NULL values
                        ->orWhereNotIn('t3.OTStatus', ['Approved', 'Rejected']); // Exclude 'Approved' & 'Rejected'
                })

                ->whereBetween('t3.date', [$startDate->toDateString(), $endDate->toDateString()])
                ->where("t1.resort_id", $resort_id)
                ->select('t3.id', 't1.first_name', 't1.last_name', 't3.OverTime', 't3.Emp_id', 't3.OTStatus', 't3.CheckingTime', 't3.CheckingOutTime', 't3.DayWiseTotalHours')
                ->get();

            // Convert OverTime to Minutes and Sum
            $OTReqMin                                       =   $employeeOTReq->sum(function ($item) {
                list($hours, $minutes) = explode(':', $item->OverTime);  // Convert "HH:mm" to [hours, minutes]
                return ((int)$hours * 60) + (int)$minutes;  // Convert to total minutes
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

            if ($totalAbsentEmployee != 0) {
                $totalAbsentEmployee                        =   $employeesCount - $totalAbsentEmployee;
            } else {
                $totalAbsentEmployee                        =   0;
            }

            $dates                                          =   [];

            for ($date = $startDate; $date->lte($endDate); $date->addDay()) {

                $formattedDate                              =   $date->format('Y-m-d'); // Keep formatted date separate

                $totalLeaveEmployee                         =   Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                    ->join('employees_leaves as t2', "t2.Emp_id", "=", "employees.id")
                    ->where('t2.from_date', "<=", $formattedDate) // Date should be within leave range
                    ->where('t2.to_date', ">=", $formattedDate)
                    ->where("t1.resort_id", $resort_id)
                    ->count();

                $totalLeaveEmployee                         =   $totalLeaveEmployee ?? 0;
                $dates[]                                    =   [
                    'leave'                                 =>  $totalLeaveEmployee,
                ];
            }

            $dates[$formattedDate]                          =   [
                'present'                                   =>  $totalPresentEmployee,
                'absent'                                    =>  $totalAbsentEmployee,
                'employee'                                  =>  $employeesCount,
            ];

            $totalPresentDays                               =   array_sum(array_column($dates, 'present'));
            $totalAbsentDays                                =   array_sum(array_column($dates, 'absent'));
            $totalLeaveDays                                 =   array_sum(array_column($dates, 'leave'));
            $employeesCount                                 =   $employeesCount;

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

                ->select('pa.id', 'pa.Emp_id', 'pa.CheckingTime as intime', 't1.first_name', 't1.last_name', \DB::raw("
                        CASE
                            WHEN t1.profile_picture = '0' THEN NULL
                            ELSE t1.profile_picture
                        END as profile_picture
                        "))
                ->get();

            $requireBreak                             =   $requireBreak->map(function ($item) {
                if ($item->profile_picture === null) { // If profile picture is 0 or NULL, get custom image
                    $item->profile_picture = Common::getResortUserPicture($item->Admin_Parent_id);
                }
                return $item;
            })->toArray();


            $timeAttendanceData['total_present_days']       =   $totalPresentDays;
            $timeAttendanceData['total_absent_days']        =   $totalAbsentDays;
            $timeAttendanceData['total_leave_days']         =   $totalLeaveDays;
            $timeAttendanceData['employee']                 =   $employeesCount;
            $timeAttendanceData['ot_approved_hrs']          =   $totalOTHrsApproved;
            $timeAttendanceData['ot_request_hrs']           =   $totalOTHrsReq;
            $timeAttendanceData['ot_total_hr']              =   $totalOTSum;
            $timeAttendanceData['over_time_request']        =   $employeeOTReq;
            $timeAttendanceData['requires_break']           =   $requireBreak;


            $response['status']                             =   true;
            $response['message']                            =   'Time TimeAttendance ' . $available_rank . ' Dashboard';
            $response['time_attendance']                    =   $timeAttendanceData;

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
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
                $roster_id                                      =   $rosterData->id;
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
                                                                                DB::raw("COUNT(DISTINCT CASE WHEN t4.Status = 'Present' THEN t4.date END) as present_days"),
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
        $emp_id                                             =   $employee->id;
        $reporting_to                                       =   $emp_id;
        $underEmp_id                                        =   Common::getSubordinates($reporting_to);
        $resort_id                                          =   $user->resort_id;

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
        $emp_id                                             =   $employee->id;
        $reporting_to                                       =   $emp_id;
        $underEmp_id                                        =   Common::getSubordinates($reporting_to);

        try {

            $underEmp_HOD                                   =   Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                                                                    ->select('t1.id', 't1.first_name', 't1.last_name', 't1.profile_picture', 'employees.id as emp_id')
                                                                    ->whereIN('employees.id', $underEmp_id)
                                                                    ->get();

            $underEmp_HOD                                   =   $underEmp_HOD->map(function ($item) {
                if (empty($item->profile_picture) || $item->profile_picture == '0') {
                    $item->profile_picture = Common::getResortUserPicture($item->Parentid); // Ensure it's using Parentid if 'profile_picture' is missing
                }
                return $item;
            })->toArray();

            $response['status']                             =   true;
            $response['message']                            =   'Employee list under HOD fetched successfully.';
            $response['emp_time_attendance']                =   $underEmp_HOD;

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function hodMarkAttendance()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $user                                               =   Auth::guard('api')->user();
        $employee                                           =   $user->GetEmployee;
        $emp_id                                             =   $employee->id;
        $reporting_to                                       =   $emp_id;
        $underEmp_id                                        =   Common::getSubordinates($reporting_to);
        $resort_id                                          =   $user->resort_id;

        try {

            $currentDate                                    =   Carbon::now()->format('Y-m-d');
            $employeeAttendance                             =   Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                                                                    ->join('duty_rosters as t2', "t2.Emp_id", "=", "employees.id")
                                                                    ->join('parent_attendaces as t3', "t3.roster_id", "=", "t2.id")
                                                                    ->join('resort_positions as rp', "rp.id", "=", "employees.Position_id")
                                                                    ->where('t3.date', $currentDate) // Use the formatted date
                                                                    ->where("t1.resort_id", $resort_id)
                                                                    ->whereIn('employees.id', $underEmp_id)
                                                                    ->select('t3.id', 't1.first_name', 't1.last_name', 't1.profile_picture', 'employees.id as emp_id', 'rp.position_title', 't3.Status', 't3.CheckingTime', 't3.CheckingOutTime', 't3.date', 't3.CheckInCheckOut_Type')
                                                                    ->get();


            $employeeAttendance                             =   $employeeAttendance->map(function ($item) {
                if (empty($item->profile_picture) || $item->profile_picture == '0') {
                    $item->profile_picture = Common::getResortUserPicture($item->Parentid); // Ensure it's using Parentid if 'profile_picture' is missing
                }
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

        // Validate request data
        $validator = Validator::make($request->all(), [
            'attendance_id'                                 => 'required|array|min:1',  // Must be an array with at least 1 entry
            'attendance_id.*'                               => 'integer|exists:parent_attendaces,id',
            'hod_location'                                  =>  'required',
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
            $timeAttendance                                 =   [];

            DB::beginTransaction(); // Start transaction
            foreach ($attendaceId as $key => $value) {

                $parentAttendance                           =   ParentAttendace::where('resort_id', $resort_id)->where('id', $value)->where('Status', '')->first();

                if ($parentAttendance) {
                    $shiftData                                  =   ShiftSettings::where('resort_id', $resort_id)->where('id', $parentAttendance->Shift_id)->first();

                    if ($shiftData) {
                        $parentAttendance->CheckingTime         =   $shiftData->StartTime;
                        $parentAttendance->CheckingOutTime      =   $shiftData->EndTime;
                        $parentAttendance->Status               =   'Present';
                        $parentAttendance->CheckInCheckOut_Type =   'Manual';
                        $parentAttendance->save();

                        $childAttendace = ChildAttendace::updateOrCreate(
                            ['Parent_attd_id'                   =>  $parentAttendance->id], // Search condition
                            [
                                'InTime_out'                    =>  $shiftData->StartTime,
                                'OutTime_out'                   =>  $shiftData->EndTime,
                                'InTime_Location'               =>  $request->hod_location,
                                'OutTime_Location'              =>  $request->hod_location,
                            ]
                        );

                        $timeAttendance[]                       = [
                            'id'                                => $parentAttendance->id,
                            'CheckingTime'                      => $parentAttendance->CheckingTime,
                            'CheckingOutTime'                   => $parentAttendance->CheckingOutTime,
                            'Status'                            => $parentAttendance->Status,
                            'CheckInCheckOut_Type'              => $parentAttendance->CheckInCheckOut_Type,
                        ];
                    }
                }
            }

            if (empty($timeAttendance)) {
                return response()->json(['success' => false, 'message' => 'No new attendance records updated. Employee is already marked as present.'], 422);
            }
            DB::commit(); // Commit transaction

            $response['status']                             = true;
            $response['message']                            = 'Attendance data stored successfully.';
            $response['mark_attendance']                    = $timeAttendance;


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
                    if ($todayAttendance->Status == "Present") {
                        $presentCount = 1;
                        if (!empty($todayAttendance->OverTime) && $todayAttendance->OverTime != "-" && $todayAttendance->OverTime != "00:00") {
                            list($Othours, $Otminutes) = explode(':', $todayAttendance->OverTime ?? '0:0');
                            $totalOtHours = (int)$Othours + ((int)$Otminutes / 60);
                        }
                    } elseif ($todayAttendance->Status == "Absent") {
                        $absentCount = 1;
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

                // Calculate summary statistics for month
                $presentCount = 0;
                $absentCount = 0;
                $leaveCount = 0;
                $totalOtHours = 0;

                foreach ($RosterInternalDataMonth as $shiftData) {
                    if ($shiftData->Status == "Present") {
                        $presentCount++;
                        if (!empty($shiftData->OverTime) && $shiftData->OverTime != "-" && $shiftData->OverTime != "00:00") {
                            list($Othours, $Otminutes) = explode(':', $shiftData->OverTime ?? '0:0');
                            $totalOtHours += (int)$Othours + ((int)$Otminutes / 60);
                        }
                    } elseif ($shiftData->Status == "Absent") {
                        $absentCount++;
                    } elseif (isset($shiftData->LeaveData) && is_array($shiftData->LeaveData) && !empty($shiftData->LeaveData)) {
                        $leaveCount++;
                    }
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


}
