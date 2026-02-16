<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ParentAttendace;
use App\Models\ChildAttendace;
use App\Models\Employee;
use Carbon\Carbon;
use App\Helpers\Common;
use App\Models\ResortPosition;
use App\Models\ResortDepartment;
use App\Models\DutyRosterEntry;
use App\Models\ResortHoliday;
use App\Models\EmployeeOvertime;
use Carbon\CarbonInterval;
use DB;
use Illuminate\Support\Str;
class TimeandAttendanceDashboardController extends Controller
{

    protected $resort;
    protected $underEmp_id=[];

    public function __construct()
    {
        $this->resort = $resortId = auth()->guard('resort-admin')->user();
        if(isset($this->resort->GetEmployee))
        {
            $reporting_to = $this->resort->GetEmployee->id;
        }
        else
        {
            $reporting_to = $this->resort->id;
        }
        $this->underEmp_id = Common::getSubordinates($reporting_to);

    }

    /**
     * Check if user can view all departments
     * HR HOD (rank 2 in HR department) and HRX Com (rank 1/EXCOM) can see all departments
     * Regular HR (rank 3) can only see their own department
     */
    protected function canViewAllDepartments()
    {
        if (!isset($this->resort->GetEmployee)) {
            // #region agent log
            $logPath = base_path('.cursor/debug.log');
            @file_put_contents($logPath, json_encode(['sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'A','location'=>'TimeandAttendanceDashboardController.php:46','message'=>'canViewAllDepartments: No GetEmployee','data'=>[],'timestamp'=>time()*1000])."\n", FILE_APPEND);
            // #endregion
            return false;
        }

        $employee = $this->resort->GetEmployee;
        $rank = $employee->rank ?? null;
        $dept_id = $employee->Dept_id ?? null;

        // #region agent log
        $logPath = base_path('.cursor/debug.log');
        @file_put_contents($logPath, json_encode(['sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'A','location'=>'TimeandAttendanceDashboardController.php:54','message'=>'canViewAllDepartments: Checking rank','data'=>['rank'=>$rank,'dept_id'=>$dept_id],'timestamp'=>time()*1000])."\n", FILE_APPEND);
        // #endregion

        // Check if user is HRX Com (rank 1/EXCOM)
        // Handle both string and integer rank values
        if ($rank == 1 || $rank === 1 || $rank === '1') {
            // #region agent log
            @file_put_contents($logPath, json_encode(['sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'A','location'=>'TimeandAttendanceDashboardController.php:57','message'=>'canViewAllDepartments: EXCOM detected','data'=>['result'=>true,'rank'=>$rank,'rankType'=>gettype($rank)],'timestamp'=>time()*1000])."\n", FILE_APPEND);
            // #endregion
            return true;
        }

        // Check if user is HR HOD (rank 2 in HR department)
        if ($rank == 2 && $dept_id) {
            $department = ResortDepartment::find($dept_id);
            if ($department) {
                $deptName = strtolower(trim($department->name));
                if (in_array($deptName, ['human resources', 'hr'])) {
                    // #region agent log
                    @file_put_contents($logPath, json_encode(['sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'A','location'=>'TimeandAttendanceDashboardController.php:66','message'=>'canViewAllDepartments: HR HOD detected','data'=>['result'=>true,'deptName'=>$deptName],'timestamp'=>time()*1000])."\n", FILE_APPEND);
                    // #endregion
                    return true;
                }
            }
        }

        // #region agent log
        @file_put_contents($logPath, json_encode(['sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'A','location'=>'TimeandAttendanceDashboardController.php:70','message'=>'canViewAllDepartments: Returning false','data'=>['result'=>false],'timestamp'=>time()*1000])."\n", FILE_APPEND);
        // #endregion
        return false;
    }

    /**
     * Check if user is a department HOD (rank 2, not in HR department)
     * Department HODs can see all employees in their department (not just subordinates)
     */
    protected function isDepartmentHOD()
    {
        if (!isset($this->resort->GetEmployee)) {
            return false;
        }

        $employee = $this->resort->GetEmployee;
        $rank = $employee->rank ?? null;
        $dept_id = $employee->Dept_id ?? null;

        // Check if user is rank 2 (HOD) but not in HR department
        if ($rank == 2 && $dept_id) {
            $department = ResortDepartment::find($dept_id);
            if ($department) {
                $deptName = strtolower(trim($department->name));
                // If not HR department, then it's a department HOD
                if (!in_array($deptName, ['human resources', 'hr'])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get holiday dates (Fridays + public holidays from resortholidays table)
     * @param int $resort_id
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array Array of holiday dates in 'Y-m-d' format
     */
    protected function getHolidayDates($resort_id, $startDate, $endDate)
    {
        $holidayDates = [];

        // Get public holidays from resortholidays table for the date range
        $publicHolidays = ResortHoliday::where('resort_id', $resort_id)
            ->whereNotNull('PublicHolidaydate')
            ->whereDate('PublicHolidaydate', '>=', $startDate->format('Y-m-d'))
            ->whereDate('PublicHolidaydate', '<=', $endDate->format('Y-m-d'))
            ->get(['PublicHolidaydate']);

        foreach ($publicHolidays as $holiday) {
            if ($holiday->PublicHolidaydate) {
                $date = Carbon::parse($holiday->PublicHolidaydate)->format('Y-m-d');
                $holidayDates[] = $date;
            }
        }

        // Add all Fridays between start and end date
        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            if ($currentDate->isFriday()) {
                $fridayDate = $currentDate->format('Y-m-d');
                if (!in_array($fridayDate, $holidayDates)) {
                    $holidayDates[] = $fridayDate;
                }
            }
            $currentDate->addDay();
        }

        return $holidayDates;
    }

    /**
     * Convert time string (HH:MM) to decimal hours
     * @param string $timeString
     * @return float
     */
    protected function timeToHours($timeString)
    {
        if (empty($timeString) || $timeString == '00:00') {
            return 0;
        }

        $parts = explode(':', $timeString);
        if (count($parts) == 2) {
            $hours = (int)$parts[0];
            $minutes = (int)$parts[1];
            return $hours + ($minutes / 60);
        }

        return 0;
    }

    public function admin_dashboard()
    {
         $page_title ='Time And Attendance';
        //  $Dept_id = $this->resort->GetEmployee->Dept_id;
        //  $Rank =  $this->resort->GetEmployee->rank;
        // dd($page_title);
         $ResortPosition = ResortPosition::where("resort_id",$this->resort->resort_id)->get();
         $ResortDepartment = ResortDepartment::where("resort_id",$this->resort->resort_id)->get();


        $EmployeesCount = Employee::join('resort_admins as t1',"t1.id","=","employees.Admin_Parent_id")
                            ->where("t1.resort_id",$this->resort->resort_id)
                            ->count();

        $EmployeesCount = $EmployeesCount ?? 0;


        $totalPresentEmployee =Employee::join('resort_admins as t1',"t1.id","=","employees.Admin_Parent_id")
                                ->join('duty_rosters as t2',"t2.Emp_id","=","employees.id")
                                ->join('parent_attendaces as t3',"t3.roster_id","=","t2.id")
                                ->whereNotNull('t3.CheckingTime')
                                ->whereNotNull('t3.CheckingOutTime')
                                ->whereIn('t3.Status',['Present','HalfDay','On-Time','Late','ShortLeave','HalfDayLeave'])
                                ->where('t3.date',date('Y-m-d'))
                                ->where("t1.resort_id",$this->resort->resort_id)

                                ->count();
        $totalPresentEmployee = $totalPresentEmployee ?? 0;

        $totalAbsantEmployee =Employee::join('resort_admins as t1',"t1.id","=","employees.Admin_Parent_id")
                                        ->join('duty_rosters as t2',"t2.Emp_id","=","employees.id")
                                        ->join('parent_attendaces as t3',"t3.roster_id","=","t2.id")
                                        ->where("t3.date",date('Y-m-d'))
                                        ->where("t3.Status","Absent")
                                        ->whereNull('t3.CheckingTime')
                                        ->whereNull('t3.CheckingOutTime')
                                        ->where("t1.resort_id",$this->resort->resort_id)
                                        ->count();

                                        if ($totalAbsantEmployee != 0)
                                        {
                                            $totalAbsantEmployee = $EmployeesCount - $totalAbsantEmployee;
                                        }
                                        else
                                        {
                                            $totalAbsantEmployee = 0;
                                        }
        $totalLeaveEmployee =Employee::join('resort_admins as t1',"t1.id","=","employees.Admin_Parent_id")
                                                    ->join('employees_leaves as t2',"t2.Emp_id","=","employees.id")
                                                    ->where('t2.from_date',">=",date('Y-m-d'))
                                                    ->where('t2.to_date',"<=",date('Y-m-d'))
                                                    ->where("t1.resort_id",$this->resort->resort_id)
                                                    ->count();
        $totalLeaveEmployee = $totalLeaveEmployee ?? 0;
        return view('resorts.timeandattendance.dashboard.hrdashboard',compact('page_title','ResortPosition','ResortDepartment','EmployeesCount','totalPresentEmployee','totalAbsantEmployee','totalLeaveEmployee'));
    }



    public function HrDashobard()
    {
         // #region agent log
         $logPath = base_path('.cursor/debug.log');
         @file_put_contents($logPath, json_encode(['sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'A','location'=>'TimeandAttendanceDashboardController.php:222','message'=>'HrDashobard entry','data'=>['resort_id'=>$this->resort->resort_id??null,'hasGetEmployee'=>isset($this->resort->GetEmployee)],'timestamp'=>time()*1000])."\n", FILE_APPEND);
         // #endregion

         $page_title ='Time And Attendance';
         $Dept_id = $this->resort->GetEmployee->Dept_id ?? null;
         $Rank =  $this->resort->GetEmployee->rank ?? null;
         $canViewAll = $this->canViewAllDepartments();
         $isDeptHOD = $this->isDepartmentHOD();

         // #region agent log
         @file_put_contents($logPath, json_encode(['sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'A','location'=>'TimeandAttendanceDashboardController.php:230','message'=>'User permissions check','data'=>['Dept_id'=>$Dept_id,'Rank'=>$Rank,'canViewAll'=>$canViewAll,'isDeptHOD'=>$isDeptHOD],'timestamp'=>time()*1000])."\n", FILE_APPEND);
         // #endregion

         $attendanceDataTodoList = $this->Tododata();

         // Get positions - all departments if can view all, otherwise only user's department
         if ($canViewAll) {
             $ResortPosition = ResortPosition::where("resort_id", $this->resort->resort_id)->get();
         } else {
             $ResortPosition = ResortPosition::where("dept_id", $Dept_id)
                                             ->where("resort_id", $this->resort->resort_id)
                                             ->get();
         }

         $ResortDepartment = ResortDepartment::where("resort_id",$this->resort->resort_id)->get();

        // Employee count - all departments if can view all (only active employees)
        // HR HOD/EXCOM: see all employees
        // Department HOD: see all employees in their department
        // Others: see only subordinates in their department
        $EmployeesCountQuery = Employee::join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                                        ->where('t1.resort_id', $this->resort->resort_id)
                                        ->where('employees.status', 'Active');

        if (!$canViewAll && $Dept_id) {
            $EmployeesCountQuery->where('employees.Dept_id', $Dept_id);
        }

        // Department HODs see all employees in their department (not just subordinates)
        // Regular users see only their subordinates
        if (!$canViewAll && !$isDeptHOD) {
            $EmployeesCountQuery->whereIn('employees.id', $this->underEmp_id);
        }

        $EmployeesCount = $EmployeesCountQuery->distinct('employees.id')->count('employees.id');
        $EmployeesCount = $EmployeesCount ?? 0;

        // Total present employees (distinct count of employees who are present today)
        // An employee is considered present if they have checked in (CheckingTime is not null)
        // CheckingOutTime can be null if they're still working
        $totalPresentEmployeeQuery = Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                                            ->join('duty_rosters as t2', "t2.Emp_id", "=", "employees.id")
                                            ->join('parent_attendaces as t3', "t3.roster_id", "=", "t2.id")
                                            ->whereNotNull('t3.CheckingTime')
                                            ->whereIn('t3.Status', ['Present', 'HalfDay', 'On-Time', 'Late', 'ShortLeave', 'HalfDayLeave'])
                                            ->where('t3.date', date('Y-m-d'))
                                            ->where("t1.resort_id", $this->resort->resort_id)
                                            ->where('employees.status', 'Active');

        // Department HODs see all employees in their department (not just subordinates)
        // Regular users see only their subordinates
        if (!$canViewAll && !$isDeptHOD) {
            $totalPresentEmployeeQuery->whereIn('employees.id', $this->underEmp_id);
        }
        if (!$canViewAll && $Dept_id) {
            $totalPresentEmployeeQuery->where("employees.Dept_id", $Dept_id);
        }

        $totalPresentEmployee = $totalPresentEmployeeQuery->distinct('employees.id')->count('employees.id');

        // #region agent log
        $logPath = base_path('.cursor/debug.log');
        // Check if there are any attendance records at all for today
        $todayAttendanceCheck = DB::table('parent_attendaces as t3')
            ->join('duty_rosters as t2', 't3.roster_id', '=', 't2.id')
            ->join('employees', 't2.Emp_id', '=', 'employees.id')
            ->join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
            ->where('t3.date', date('Y-m-d'))
            ->where('t1.resort_id', $this->resort->resort_id)
            ->whereNotNull('t3.CheckingTime')
            ->count();
        @file_put_contents($logPath, json_encode(['sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'B','location'=>'TimeandAttendanceDashboardController.php:318','message'=>'HrDashobard present query check','data'=>['canViewAll'=>$canViewAll,'isDeptHOD'=>$isDeptHOD,'totalPresentEmployee'=>$totalPresentEmployee,'todayAttendanceRecords'=>$todayAttendanceCheck,'underEmpCount'=>count($this->underEmp_id)],'timestamp'=>time()*1000])."\n", FILE_APPEND);
        // #endregion
        $totalPresentEmployee = $totalPresentEmployee ?? 0;

        // Total leave employees (distinct count of employees on approved leave today)
        $totalLeaveEmployeeQuery = Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                                            ->join('employees_leaves as t2', "t2.Emp_id", "=", "employees.id")
                                            ->where('t2.from_date', "<=", date('Y-m-d'))
                                            ->where('t2.to_date', ">=", date('Y-m-d'))
                                            ->where('t2.status', 'Approved')
                                            ->where("t1.resort_id", $this->resort->resort_id)
                                            ->where('employees.status', 'Active');

        // Department HODs see all employees in their department (not just subordinates)
        // Regular users see only their subordinates
        if (!$canViewAll && !$isDeptHOD) {
            $totalLeaveEmployeeQuery->whereIn('employees.id', $this->underEmp_id);
        }
        if (!$canViewAll && $Dept_id) {
            $totalLeaveEmployeeQuery->where("employees.Dept_id", $Dept_id);
        }
        if (!$canViewAll && $Rank && !$isDeptHOD) {
            $totalLeaveEmployeeQuery->where("employees.rank", "!=", $Rank);
        }

        $totalLeaveEmployee = $totalLeaveEmployeeQuery->distinct('employees.id')->count('employees.id');
        $totalLeaveEmployee = $totalLeaveEmployee ?? 0;

        // Total absent employees = Total Employees - Present - On Leave
        // (Employees who are not present and not on leave are considered absent)
        $totalAbsantEmployee = $EmployeesCount - $totalPresentEmployee - $totalLeaveEmployee;
        $totalAbsantEmployee = max(0, $totalAbsantEmployee);

        // #region agent log
        @file_put_contents($logPath, json_encode(['sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'B','location'=>'TimeandAttendanceDashboardController.php:345','message'=>'Final dashboard counts','data'=>['EmployeesCount'=>$EmployeesCount,'totalPresentEmployee'=>$totalPresentEmployee,'totalLeaveEmployee'=>$totalLeaveEmployee,'totalAbsantEmployee'=>$totalAbsantEmployee,'todoListCount'=>count($attendanceDataTodoList)],'timestamp'=>time()*1000])."\n", FILE_APPEND);
        // #endregion

        return view('resorts.timeandattendance.dashboard.hrdashboard',compact('attendanceDataTodoList', 'page_title','ResortPosition','ResortDepartment','EmployeesCount','totalPresentEmployee','totalAbsantEmployee','totalLeaveEmployee'));
    }

    public function HrDashboardCount($date)
    {
        // #region agent log
        $logPath = base_path('.cursor/debug.log');
        @file_put_contents($logPath, json_encode(['sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'C','location'=>'TimeandAttendanceDashboardController.php:310','message'=>'HrDashboardCount entry','data'=>['date'=>$date],'timestamp'=>time()*1000])."\n", FILE_APPEND);
        // #endregion

        $Dept_id = $this->resort->GetEmployee->Dept_id ?? null;
        $Rank = $this->resort->GetEmployee->rank ?? null;
        $canViewAll = $this->canViewAllDepartments();
        $isDeptHOD = $this->isDepartmentHOD();

        // #region agent log
        @file_put_contents($logPath, json_encode(['sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'C','location'=>'TimeandAttendanceDashboardController.php:316','message'=>'HrDashboardCount permissions','data'=>['canViewAll'=>$canViewAll,'isDeptHOD'=>$isDeptHOD],'timestamp'=>time()*1000])."\n", FILE_APPEND);
        // #endregion

        // Employee count - all departments if can view all (only active employees)
        $EmployeesCountQuery = Employee::join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                                        ->where('t1.resort_id', $this->resort->resort_id)
                                        ->where('employees.status', 'Active');

        if (!$canViewAll && $Dept_id) {
            $EmployeesCountQuery->where('employees.Dept_id', $Dept_id);
        }

        // Department HODs see all employees in their department (not just subordinates)
        // Regular users see only their subordinates
        if (!$canViewAll && !$isDeptHOD) {
            $EmployeesCountQuery->whereIn('employees.id', $this->underEmp_id);
        }

        $EmployeesCount = $EmployeesCountQuery->distinct('employees.id')->count('employees.id');
        $EmployeesCount = $EmployeesCount ?? 0;

        // Total present employees (distinct count of employees who are present today)
        $totalPresentEmployeeQuery = Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                                            ->join('duty_rosters as t2', "t2.Emp_id", "=", "employees.id")
                                            ->join('parent_attendaces as t3', "t3.roster_id", "=", "t2.id")
                                            ->whereNotNull('t3.CheckingTime')
                                            ->whereIn('t3.Status', ['Present', 'HalfDay', 'On-Time', 'Late', 'ShortLeave', 'HalfDayLeave'])
                                            ->where('t3.date', $date)
                                            ->where("t1.resort_id", $this->resort->resort_id)
                                            ->where('employees.status', 'Active');

        // Department HODs see all employees in their department (not just subordinates)
        // Regular users see only their subordinates
        if (!$canViewAll && !$isDeptHOD) {
            $totalPresentEmployeeQuery->whereIn('employees.id', $this->underEmp_id);
        }
        if (!$canViewAll && $Dept_id) {
            $totalPresentEmployeeQuery->where("employees.Dept_id", $Dept_id);
        }

        $totalPresentEmployee = $totalPresentEmployeeQuery->distinct('employees.id')->count('employees.id');
        $totalPresentEmployee = $totalPresentEmployee ?? 0;

        // Total leave employees (distinct count of employees on approved leave)
        $totalLeaveEmployeeQuery = Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                                            ->join('employees_leaves as t2', "t2.Emp_id", "=", "employees.id")
                                            ->where('t2.from_date', "<=", $date)
                                            ->where('t2.to_date', ">=", $date)
                                            ->where('t2.status', 'Approved')
                                            ->where("t1.resort_id", $this->resort->resort_id)
                                            ->where('employees.status', 'Active');

        // Department HODs see all employees in their department (not just subordinates)
        // Regular users see only their subordinates
        if (!$canViewAll && !$isDeptHOD) {
            $totalLeaveEmployeeQuery->whereIn('employees.id', $this->underEmp_id);
        }
        if (!$canViewAll && $Dept_id) {
            $totalLeaveEmployeeQuery->where("employees.Dept_id", $Dept_id);
        }
        if (!$canViewAll && $Rank && !$isDeptHOD) {
            $totalLeaveEmployeeQuery->where("employees.rank", "!=", $Rank);
        }

        $totalLeaveEmployee = $totalLeaveEmployeeQuery->distinct('employees.id')->count('employees.id');
        $totalLeaveEmployee = $totalLeaveEmployee ?? 0;

        // Total absent employees = Total Employees - Present - On Leave
        $totalAbsantEmployee = $EmployeesCount - $totalPresentEmployee - $totalLeaveEmployee;
        $totalAbsantEmployee = max(0, $totalAbsantEmployee);

        return response()->json([
            'success' => true,
            'data' => [
                "totalPresentEmployee" => $totalPresentEmployee,
                "totalAbsantEmployee" => $totalAbsantEmployee,
                "totalLeaveEmployee" => $totalLeaveEmployee,
            ],
        ]);
    }



    public function HrDutyRosterdashboardTable(Request $request)
    {
        $Dept_id = $this->resort->GetEmployee->Dept_id ?? null;
        $Rank = $this->resort->GetEmployee->rank ?? null;
        $canViewAll = $this->canViewAllDepartments();
        $isDeptHOD = $this->isDepartmentHOD();

         // Accept both AJAX and regular requests for DataTables
         if($request->ajax() || $request->wantsJson() || $request->expectsJson())
        {
            // First, get the latest duty roster ID for each unique employee
            // Get all rosters, then group by employee to get the latest one per employee
            $latestRosterIdsQuery = DB::table('duty_rosters as t3')
                ->join('employees', 'employees.id', '=', 't3.Emp_id')
                ->join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                ->where('t1.resort_id', $this->resort->resort_id)
                ->where('employees.status', 'Active');

            // Apply department filter only if user cannot view all departments
            if (!$canViewAll && $Dept_id) {
                $latestRosterIdsQuery->where('employees.Dept_id', $Dept_id);
            }

            // Apply rank filter only if user cannot view all departments and not a department HOD
            if (!$canViewAll && $Rank && !$isDeptHOD) {
                $latestRosterIdsQuery->where('employees.rank', '!=', $Rank);
            }

            // Department HODs see all employees in their department (not just subordinates)
            // Regular users see only their subordinates
            if (!$canViewAll && !$isDeptHOD) {
                $latestRosterIdsQuery->whereIn('employees.id', $this->underEmp_id);
            }

            $latestRosterIds = $latestRosterIdsQuery
                ->select('employees.id as emp_id', DB::raw('MAX(t3.id) as latest_roster_id'))
                ->groupBy('employees.id')
                ->orderBy('latest_roster_id', 'DESC')
                ->limit(5)
                ->pluck('latest_roster_id');

            if ($latestRosterIds->isEmpty()) {
                return response()->json([
                    'data' => [],
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0
                ]);
            }

            // Now get the full data for these latest duty rosters
            // Using LEFT JOIN for parent_attendaces and shift_settings since they might not exist
            try {
                $Rosterdata = Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                    ->join('resort_positions as t2', "t2.id", "=", "employees.Position_id")
                    ->join('duty_rosters as t3', "t3.Emp_id", "=", "employees.id")
                    ->leftJoin('parent_attendaces as t4', "t4.roster_id", "=", "t3.id")
                    ->leftJoin('shift_settings as t5', "t5.id", "=", "t4.Shift_id")
                    ->leftJoin('child_attendaces as t7', "t7.Parent_attd_id", "=", "t4.id")
                    ->where('employees.status', 'Active')
                    ->whereIn('t3.id', $latestRosterIds)
                    ->select([
                        't3.id as duty_roster_id',
                        't3.DayOfDate',
                        't3.created_at',
                        't1.id as Parentid',
                        't1.first_name',
                        't1.last_name',
                        't1.profile_picture',
                        'employees.id as emp_id',
                        't2.position_title',
                        't5.ShiftName',
                        't5.StartTime',
                        't5.EndTime',
                        't7.InTime_Location',
                        't7.OutTime_Location',
                    ])
                    ->groupBy('t3.id', 't3.DayOfDate', 't3.created_at', 't1.id', 't1.first_name', 't1.last_name', 't1.profile_picture', 'employees.id', 't2.position_title', 't5.ShiftName', 't5.StartTime', 't5.EndTime', 't7.InTime_Location', 't7.OutTime_Location')
                    ->orderBy('t3.created_at', 'DESC')
                    ->get();
            } catch (\Exception $e) {
                return response()->json([
                    'data' => [],
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'error' => $e->getMessage()
                ]);
            }

            // Fetch the data and map results
            $Rosterdata = $Rosterdata->map(function ($item) {
                // Handle cases where shift data might be null
                if ($item->ShiftName && $item->StartTime && $item->EndTime) {
                    $startTime = Carbon::parse($item->StartTime);
                    $endTime = Carbon::parse($item->EndTime);
                    $item->Shift = ucfirst($item->ShiftName) . '-' . $startTime->format('h:i A') . ' - ' . $endTime->format('h:i A');
                } else {
                    $item->Shift = 'N/A';
                }
                $item->EmployeeName = ucfirst($item->first_name . ' ' . $item->last_name);
                $item->Position = ucfirst($item->position_title ?? 'N/A');
                $item->profileImg = Common::getResortUserPicture($item->Parentid);
                $item->action = '<a href="'.route('resort.timeandattendance.hoddashboard').'" class="btn btn-sm btn-outline-primary">View</a>';
                return $item;
            });

            // Return simple JSON response for client-side DataTables
            $responseData = [
                'data' => $Rosterdata->values()->all(),
                'recordsTotal' => $Rosterdata->count(),
                'recordsFiltered' => $Rosterdata->count()
            ];
            return response()->json($responseData);
        }

    }

    public function HRMonthOverTimeChart($Dept_id,$date)
    {
        // #region agent log
        $logPath = base_path('.cursor/debug.log');
        @file_put_contents($logPath, json_encode(['sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'E','location'=>'TimeandAttendanceDashboardController.php:560','message'=>'HRMonthOverTimeChart entry','data'=>['Dept_id'=>$Dept_id,'date'=>$date],'timestamp'=>time()*1000])."\n", FILE_APPEND);
        // #endregion

        $Dept_id = base64_decode($Dept_id);
        $currentMonth = Carbon::now()->startOfMonth();
        $months1 = [];

        // Get last 4 months including current month
        for ($i = -3; $i <= 0; $i++) {
            $months1[] = $currentMonth->copy()->addMonths($i)->format('M Y');
        }

        $Rank = isset($this->resort->GetEmployee) ? $this->resort->GetEmployee->rank : 3;
        $canViewAll = $this->canViewAllDepartments();
        $isDeptHOD = $this->isDepartmentHOD();
        $resort_id = $this->resort->resort_id;

        // #region agent log
        @file_put_contents($logPath, json_encode(['sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'E','location'=>'TimeandAttendanceDashboardController.php:575','message'=>'HRMonthOverTimeChart permissions','data'=>['decodedDept_id'=>$Dept_id,'canViewAll'=>$canViewAll,'isDeptHOD'=>$isDeptHOD],'timestamp'=>time()*1000])."\n", FILE_APPEND);
        // #endregion

        // Calculate date range for last 4 months
        $startDate = $currentMonth->copy()->subMonths(3)->startOfMonth();
        $endDate = $currentMonth->copy()->endOfMonth();

        // Get all holiday dates (Fridays + public holidays) for the date range
        $holidayDates = $this->getHolidayDates($resort_id, $startDate, $endDate);

        // Initialize month arrays
        $months = [];
        $normalOtData = [];
        $holidayOtData = [];
        $totalOtData = [];

        foreach ($months1 as $month) {
            $months[$month] = $month;
            $normalOtData[$month] = 0;
            $holidayOtData[$month] = 0;
            $totalOtData[$month] = 0;
        }

        // Query overtime data from duty_roster_entries table
        $overtimeQuery = DutyRosterEntry::join('employees', 'duty_roster_entries.Emp_id', '=', 'employees.id')
                                        ->join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                                        ->where('duty_roster_entries.resort_id', $resort_id)
                                        ->whereNotNull('duty_roster_entries.OverTime')
                                        ->where('duty_roster_entries.OverTime', '!=', '00:00')
                                        ->where('duty_roster_entries.OverTime', '!=', '')
                                        ->whereBetween('duty_roster_entries.date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                                        ->where('employees.status', 'Active')
                                        ->select(
                                            'duty_roster_entries.date',
                                            'duty_roster_entries.OverTime',
                                            'duty_roster_entries.Emp_id'
                                        );

        // Apply department filter if provided and not 'All' and user cannot view all
        if (!$canViewAll && isset($Dept_id) && $Dept_id != 'All') {
            $overtimeQuery->where('employees.Dept_id', $Dept_id);
        }

        // Department HODs see all employees in their department (not just subordinates)
        // Regular users see only their subordinates
        if (!$canViewAll && !$isDeptHOD) {
            $overtimeQuery->whereIn('employees.id', $this->underEmp_id);
        }

        $overtimeData = $overtimeQuery->get();

        // Process each overtime entry
        foreach ($overtimeData as $entry) {
            $entryDate = Carbon::parse($entry->date);
            $monthKey = $entryDate->format('M Y');

            // Skip if month is not in our range
            if (!in_array($monthKey, $months1)) {
                continue;
            }

            // Convert overtime from HH:MM to decimal hours
            $overtimeHours = $this->timeToHours($entry->OverTime);

            // Check if this date is a holiday (Friday or public holiday)
            $dateStr = $entryDate->format('Y-m-d');
            $isHoliday = in_array($dateStr, $holidayDates);

            if ($isHoliday) {
                // Add to holiday OT
                $holidayOtData[$monthKey] += $overtimeHours;
            } else {
                // Add to normal OT
                $normalOtData[$monthKey] += $overtimeHours;
            }

            // Add to total OT
            $totalOtData[$monthKey] += $overtimeHours;
        }

        // Round the values to 2 decimal places
        foreach ($months1 as $month) {
            $normalOtData[$month] = round($normalOtData[$month], 2);
            $holidayOtData[$month] = round($holidayOtData[$month], 2);
            $totalOtData[$month] = round($totalOtData[$month], 2);
        }

        $data = [
            'labels' => array_values($months),
            'datasets' => [
                [
                    'label' => 'Normal OT',
                    'data' => array_values($normalOtData),
                    'backgroundColor' => '#014653',
                    'borderColor' => '#014653',
                    'borderWidth' => 1,
                    'borderRadius' => 3,
                    'barThickness' => 14
                ],
                [
                    'label' => 'Holiday OT',
                    'data' => array_values($holidayOtData),
                    'backgroundColor' => '#2EACB3',
                    'borderColor' => '#2EACB3',
                    'borderWidth' => 1,
                    'borderRadius' => 3,
                    'barThickness' => 14
                ],
                [
                    'label' => 'Total OT Hours',
                    'data' => array_values($totalOtData),
                    'backgroundColor' => '#FED049',
                    'borderColor' => '#FED049',
                    'borderWidth' => 1,
                    'borderRadius' => 3,
                    'barThickness' => 14
                ]
            ]
        ];

        return response()->json($data);
    }

    public function GetYearHrWiseAttandanceData($Year,$Dept_id,$date)
    {
        $Dept_id = base64_decode($Dept_id);
        $Rank = isset($this->resort->GetEmployee) ? $this->resort->GetEmployee->rank : 3;
        $canViewAll = $this->canViewAllDepartments();
        $isDeptHOD = $this->isDepartmentHOD();
        $currentYear = $Year;

        // Get base employee query for counting active employees
        $employeeBaseQuery = Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                            ->where("t1.resort_id", $this->resort->resort_id)
                            ->where('employees.status', 'Active');

        // Apply department filter only if user cannot view all departments
        if (!$canViewAll && isset($Dept_id) && $Dept_id != 'All') {
            $employeeBaseQuery->where("employees.Dept_id", $Dept_id);
        }

        // Department HODs see all employees in their department (not just subordinates)
        // Regular users see only their subordinates
        if (!$canViewAll && !$isDeptHOD) {
            $employeeBaseQuery->whereIn('employees.id', $this->underEmp_id);
        }

        // Get total number of active employees (for all months - this is constant)
        $totalActiveEmployees = $employeeBaseQuery->distinct('employees.id')->count('employees.id');

        // Calculate working days per month from duty rosters
        // Working days = distinct dates where employees had duty rosters (excluding DayOff)
        $workingDaysQuery = Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                            ->join('duty_rosters as t2', "t2.Emp_id", "=", "employees.id")
                            ->join('parent_attendaces as t3', "t3.roster_id", "=", "t2.id")
                            ->where('t2.Year', $currentYear)
                            ->whereRaw("YEAR(t3.date) = ?", [$currentYear])
                            ->where("t1.resort_id", $this->resort->resort_id)
                            ->whereNotIn('t3.Status', ['DayOff'])
                            ->select(DB::raw("MONTH(t3.date) as month"), DB::raw("COUNT(DISTINCT DATE(t3.date)) as working_days"));

        // Apply department filter only if user cannot view all departments
        if (!$canViewAll && isset($Dept_id) && $Dept_id != 'All') {
            $workingDaysQuery->where("employees.Dept_id", $Dept_id);
        }

        // Department HODs see all employees in their department (not just subordinates)
        // Regular users see only their subordinates
        if (!$canViewAll && !$isDeptHOD) {
            $workingDaysQuery->whereIn('employees.id', $this->underEmp_id);
        }

        $workingDaysByMonth = $workingDaysQuery->groupBy(DB::raw("MONTH(t3.date)"))->get()->keyBy('month');

        // If no working days found for a month, calculate from calendar days
        // This ensures we have working days for all months
        for ($month = 1; $month <= 12; $month++) {
            if (!$workingDaysByMonth->has($month)) {
                $daysInMonth = Carbon::createFromDate($currentYear, $month, 1)->daysInMonth;
                // Approximate working days (excluding weekends) - roughly 22 days per month
                $estimatedWorkingDays = round($daysInMonth * 0.714); // ~22/31 ratio
                $workingDaysByMonth->put($month, (object)['month' => $month, 'working_days' => $estimatedWorkingDays]);
            }
        }

        // Get total present days per month (sum of all present days for all employees)
        // This counts each employee's present day, so if 10 employees were present on a day, that's 10 present days
        $presentDaysQuery = Employee::join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                            ->join('duty_rosters as t2', 't2.Emp_id', '=', 'employees.id')
                            ->join('parent_attendaces as t3', 't3.roster_id', '=', 't2.id')
                            ->select(
                                DB::raw("MONTH(t3.date) as month"),
                                DB::raw("COUNT(*) as total_present_days")
                            )
                            ->where("t1.resort_id", $this->resort->resort_id)
                            ->where('t2.Year', $currentYear)
                            ->whereRaw("YEAR(t3.date) = ?", [$currentYear])
                            ->whereIn('t3.Status', ['Present', 'HalfDay', 'On-Time', 'Late', 'ShortLeave', 'HalfDayLeave'])
                            ->whereNotNull('t3.CheckingTime')
                            ->whereNotNull('t3.CheckingOutTime');

        // Apply department filter only if user cannot view all departments
        if (!$canViewAll && isset($Dept_id) && $Dept_id != 'All') {
            $presentDaysQuery->where("employees.Dept_id", $Dept_id);
        }

        // Department HODs see all employees in their department (not just subordinates)
        // Regular users see only their subordinates
        if (!$canViewAll && !$isDeptHOD) {
            $presentDaysQuery->whereIn('employees.id', $this->underEmp_id);
        }

        // Final query to group and order the data
        $attendanceData = $presentDaysQuery
            ->groupBy(DB::raw("MONTH(t3.date)"))
            ->orderBy(DB::raw("MONTH(t3.date)"))
            ->get();

        // Calculate attendance percentage for each month
        $attendanceData = $attendanceData->map(function ($data) use ($workingDaysByMonth, $totalActiveEmployees) {
            $month = $data->month;
            $workingDaysInMonth = $workingDaysByMonth->get($month);
            $workingDays = $workingDaysInMonth ? $workingDaysInMonth->working_days : 0;

            // Total possible working days = Number of employees × Number of working days in the month
            $totalPossibleWorkingDays = $totalActiveEmployees * $workingDays;

            // Total present days (already calculated as count of present records)
            $totalPresentDays = $data->total_present_days;

            // Attendance percentage = (Total present days / Total possible working days) × 100
            if ($totalPossibleWorkingDays != 0) {
                $attendancePercentage = ($totalPresentDays / $totalPossibleWorkingDays) * 100;
            } else {
                $attendancePercentage = 0;
            }

            return [
                'month' => $month,
                'attendance_percentage' => round($attendancePercentage, 2)
            ];
        });


        // Format the data for Chart.js
        $labelsAttendance = [];
        $attendancePercentages = [];

        // Initialize an array to ensure all months are accounted for
        $attendanceDataFormatted = [];
        foreach ($attendanceData as $data) {
            $attendanceDataFormatted[$data['month']] = $data;
        }

        // Fill in missing months (if any)
        for ($i = 1; $i <= 12; $i++) {
            $attendancePercentages[] = isset($attendanceDataFormatted[$i]) ? $attendanceDataFormatted[$i]['attendance_percentage'] : 0;
            $labelsAttendance[] = Carbon::createFromDate($currentYear, $i, 1)->format('M Y');
        }

        // Prepare the data for the chart
        $data = [
            'labels' => $labelsAttendance,
            'datasets' => [
                [
                    'label' => 'Attendance Percentage',
                    'data' => $attendancePercentages,
                    'backgroundColor' => '#014653',
                    'borderColor' => '#014653',
                    'borderWidth' => 1,
                    'borderRadius' => 6,
                    'barThickness' => 25,
                ],
            ],
        ];

        return response()->json($data);

    }

    // END OF HR DASHBOARD
    // hod dashboard
    public function hod_dashboard()
    {
        $page_title ='Time And Attendance';
        $Dept_id = $this->resort->GetEmployee->Dept_id ?? null;
        $Rank =  $this->resort->GetEmployee->rank ?? null;
        $hod = $this->resort->GetEmployee->id ?? null;
        $canViewAll = $this->canViewAllDepartments();
        $attendanceDataTodoList = $this->Tododata();

        // Get positions - all departments if can view all, otherwise only user's department
        if ($canViewAll) {
            $ResortPosition = ResortPosition::where("resort_id", $this->resort->resort_id)->get();
        } else {
            $ResortPosition = ResortPosition::where("dept_id", $Dept_id)
                                            ->where("resort_id", $this->resort->resort_id)
                                            ->get();
        }

        // Employee count - all departments if can view all (only active employees)
        $EmployeesCountQuery = Employee::join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                                        ->where('t1.resort_id', $this->resort->resort_id)
                                        ->where('employees.status', 'Active');

        if (!$canViewAll && $Dept_id) {
            $EmployeesCountQuery->where('employees.Dept_id', $Dept_id);
        }

        if (!$canViewAll) {
            $EmployeesCountQuery->whereIn('employees.id', $this->underEmp_id);
        }

        $EmployeesCount = $EmployeesCountQuery->distinct('employees.id')->count('employees.id');
        $EmployeesCount = $EmployeesCount ?? 0;

        // Total present employees (distinct count of employees who are present today)
        // An employee is considered present if they have checked in (CheckingTime is not null)
        // CheckingOutTime can be null if they're still working
        $totalPresentEmployeeQuery = Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                                            ->join('duty_rosters as t2', "t2.Emp_id", "=", "employees.id")
                                            ->join('parent_attendaces as t3', "t3.roster_id", "=", "t2.id")
                                            ->whereNotNull('t3.CheckingTime')
                                            ->whereIn('t3.Status', ['Present', 'HalfDay', 'On-Time', 'Late', 'ShortLeave', 'HalfDayLeave'])
                                            ->where('t3.date', date('Y-m-d'))
                                            ->where("t1.resort_id", $this->resort->resort_id)
                                            ->where('employees.status', 'Active');

        if (!$canViewAll) {
            $totalPresentEmployeeQuery->whereIn('employees.id', $this->underEmp_id);
        }
        if (!$canViewAll && $Dept_id) {
            $totalPresentEmployeeQuery->where("employees.Dept_id", $Dept_id);
        }

        $totalPresentEmployee = $totalPresentEmployeeQuery->distinct('employees.id')->count('employees.id');
        $totalPresentEmployee = $totalPresentEmployee ?? 0;

        // Total leave employees (distinct count of employees on approved leave today)
        $totalLeaveEmployeeQuery = Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                                            ->join('employees_leaves as t2', "t2.Emp_id", "=", "employees.id")
                                            ->where('t2.from_date', "<=", date('Y-m-d'))
                                            ->where('t2.to_date', ">=", date('Y-m-d'))
                                            ->where('t2.status', 'Approved')
                                            ->where("t1.resort_id", $this->resort->resort_id)
                                            ->where('employees.status', 'Active');

        if (!$canViewAll) {
            $totalLeaveEmployeeQuery->whereIn('employees.id', $this->underEmp_id);
        }
        if (!$canViewAll && $Dept_id) {
            $totalLeaveEmployeeQuery->where("employees.Dept_id", $Dept_id);
        }
        if (!$canViewAll && $Rank) {
            $totalLeaveEmployeeQuery->where("employees.rank", "!=", $Rank);
        }

        $totalLeaveEmployee = $totalLeaveEmployeeQuery->distinct('employees.id')->count('employees.id');
        $totalLeaveEmployee = $totalLeaveEmployee ?? 0;

        // Total absent employees = Total Employees - Present - On Leave
        // (Employees who are not present and not on leave are considered absent)
        $totalAbsantEmployee = $EmployeesCount - $totalPresentEmployee - $totalLeaveEmployee;
        $totalAbsantEmployee = max(0, $totalAbsantEmployee);

        return view('resorts.timeandattendance.dashboard.hoddashboard', compact('attendanceDataTodoList', 'page_title', 'ResortPosition', 'EmployeesCount', 'totalPresentEmployee', 'totalAbsantEmployee', 'totalLeaveEmployee'));
    }

    public function HodDashboardCount($date)
    {
        $Dept_id = $this->resort->GetEmployee->Dept_id ?? null;
        $Rank = $this->resort->GetEmployee->rank ?? null;
        $hod = $this->resort->GetEmployee->id ?? null;
        $canViewAll = $this->canViewAllDepartments();

        // Employee count (only active employees)
        $EmployeesCountQuery = Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                                        ->where("t1.resort_id", $this->resort->resort_id)
                                        ->where('employees.status', 'Active');

        if (!$canViewAll && $Dept_id) {
            $EmployeesCountQuery->where("employees.Dept_id", $Dept_id);
        }
        if (!$canViewAll && $hod) {
            $EmployeesCountQuery->where("employees.id", "!=", $hod);
        }
        if (!$canViewAll) {
            $EmployeesCountQuery->whereIn('employees.id', $this->underEmp_id);
        }

        $EmployeesCount = $EmployeesCountQuery->distinct('employees.id')->count('employees.id');
        $EmployeesCount = $EmployeesCount ?? 0;

        // Total present employees (distinct count)
        // An employee is considered present if they have checked in (CheckingTime is not null)
        // CheckingOutTime can be null if they're still working
        $totalPresentEmployeeQuery = Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                                            ->join('duty_rosters as t2', "t2.Emp_id", "=", "employees.id")
                                            ->join('parent_attendaces as t3', "t3.roster_id", "=", "t2.id")
                                            ->whereNotNull('t3.CheckingTime')
                                            ->whereIn('t3.Status', ['Present', 'HalfDay', 'On-Time', 'Late', 'ShortLeave', 'HalfDayLeave'])
                                            ->where('t3.date', $date)
                                            ->where("t1.resort_id", $this->resort->resort_id)
                                            ->where('employees.status', 'Active');

        if (!$canViewAll) {
            $totalPresentEmployeeQuery->whereIn('employees.id', $this->underEmp_id);
        }
        if (!$canViewAll && $Dept_id) {
            $totalPresentEmployeeQuery->where("employees.Dept_id", $Dept_id);
        }

        $totalPresentEmployee = $totalPresentEmployeeQuery->distinct('employees.id')->count('employees.id');
        $totalPresentEmployee = $totalPresentEmployee ?? 0;

        // Total leave employees (distinct count of employees on approved leave)
        $totalLeaveEmployeeQuery = Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                                            ->join('employees_leaves as t2', "t2.Emp_id", "=", "employees.id")
                                            ->where('t2.from_date', "<=", $date)
                                            ->where('t2.to_date', ">=", $date)
                                            ->where('t2.status', 'Approved')
                                            ->where("t1.resort_id", $this->resort->resort_id)
                                            ->where('employees.status', 'Active');

        if (!$canViewAll) {
            $totalLeaveEmployeeQuery->whereIn('employees.id', $this->underEmp_id);
        }
        if (!$canViewAll && $Dept_id) {
            $totalLeaveEmployeeQuery->where("employees.Dept_id", $Dept_id);
        }
        if (!$canViewAll && $Rank) {
            $totalLeaveEmployeeQuery->where("employees.rank", "!=", $Rank);
        }

        $totalLeaveEmployee = $totalLeaveEmployeeQuery->distinct('employees.id')->count('employees.id');
        $totalLeaveEmployee = $totalLeaveEmployee ?? 0;

        // Total absent employees = Total Employees - Present - On Leave
        $totalAbsantEmployee = $EmployeesCount - $totalPresentEmployee - $totalLeaveEmployee;
        $totalAbsantEmployee = max(0, $totalAbsantEmployee);

        return response()->json([
            'success' => true,
            'data' => [
                "totalPresentEmployee" => $totalPresentEmployee,
                "totalAbsantEmployee" => $totalAbsantEmployee,
                "totalLeaveEmployee" => $totalLeaveEmployee,
            ],
        ]);
    }

    public function HodDutyRosterdashboardTable(Request $request)
    {
        $Dept_id = $this->resort->GetEmployee->Dept_id ?? null;
        $Rank = $this->resort->GetEmployee->rank ?? null;
        $canViewAll = $this->canViewAllDepartments();
        $isDeptHOD = $this->isDepartmentHOD();

         // Accept both AJAX and regular requests for DataTables
         if($request->ajax() || $request->wantsJson() || $request->expectsJson())
        {
            // First, get the latest duty roster ID for each unique employee
            // Get all rosters, then group by employee to get the latest one per employee
            $latestRosterIdsQuery = DB::table('duty_rosters as t3')
                ->join('employees', 'employees.id', '=', 't3.Emp_id')
                ->join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                ->where('t1.resort_id', $this->resort->resort_id)
                ->where('employees.status', 'Active');

            // Apply department filter only if user cannot view all departments
            if (!$canViewAll && $Dept_id) {
                $latestRosterIdsQuery->where('employees.Dept_id', $Dept_id);
            }

            // Apply rank filter only if user cannot view all departments and not a department HOD
            if (!$canViewAll && $Rank && !$isDeptHOD) {
                $latestRosterIdsQuery->where('employees.rank', '!=', $Rank);
            }

            // Department HODs see all employees in their department (not just subordinates)
            // Regular users see only their subordinates
            if (!$canViewAll && !$isDeptHOD) {
                $latestRosterIdsQuery->whereIn('employees.id', $this->underEmp_id);
            }

            $latestRosterIds = $latestRosterIdsQuery
                ->select('employees.id as emp_id', DB::raw('MAX(t3.id) as latest_roster_id'))
                ->groupBy('employees.id')
                ->orderBy('latest_roster_id', 'DESC')
                ->limit(5)
                ->pluck('latest_roster_id');
            // #region agent log
            $logPath = base_path('.cursor/debug.log');
            @file_put_contents($logPath, json_encode(['sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'C','location'=>'TimeandAttendanceDashboardController.php:760','message'=>'First query result','data'=>['latestRosterIds_count'=>$latestRosterIds->count(),'latestRosterIds'=>$latestRosterIds->toArray()],'timestamp'=>time()*1000])."\n", FILE_APPEND);
            // #endregion

            if ($latestRosterIds->isEmpty()) {
                // #region agent log
                $logPath = base_path('.cursor/debug.log');
                @file_put_contents($logPath, json_encode(['sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'C','location'=>'TimeandAttendanceDashboardController.php:762','message'=>'Empty roster IDs - returning empty','data'=>[],'timestamp'=>time()*1000])."\n", FILE_APPEND);
                // #endregion
                return response()->json([
                    'data' => [],
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0
                ]);
            }

            // Now get the full data for these latest duty rosters
            // Using LEFT JOIN for parent_attendaces and shift_settings since they might not exist
            // #region agent log
            $logPath = base_path('.cursor/debug.log');
            @file_put_contents($logPath, json_encode(['sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'D','location'=>'TimeandAttendanceDashboardController.php:772','message'=>'Before second query','data'=>['rosterIds'=>$latestRosterIds->toArray()],'timestamp'=>time()*1000])."\n", FILE_APPEND);
            // #endregion
            try {
                $Rosterdata = Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                    ->join('resort_positions as t2', "t2.id", "=", "employees.Position_id")
                    ->join('duty_rosters as t3', "t3.Emp_id", "=", "employees.id")
                    ->leftJoin('parent_attendaces as t4', "t4.roster_id", "=", "t3.id")
                    ->leftJoin('shift_settings as t5', "t5.id", "=", "t4.Shift_id")
                    ->leftJoin('child_attendaces as t7', "t7.Parent_attd_id", "=", "t4.id")
                    ->where('employees.status', 'Active')
                    ->whereIn('t3.id', $latestRosterIds)
                    ->select([
                        't3.id as duty_roster_id',
                        't3.DayOfDate',
                        't3.created_at',
                        't1.id as Parentid',
                        't1.first_name',
                        't1.last_name',
                        't1.profile_picture',
                        'employees.id as emp_id',
                        't2.position_title',
                        't5.ShiftName',
                        't5.StartTime',
                        't5.EndTime',
                        't7.InTime_Location',
                        't7.OutTime_Location',
                    ])
                    ->groupBy('t3.id', 't3.DayOfDate', 't3.created_at', 't1.id', 't1.first_name', 't1.last_name', 't1.profile_picture', 'employees.id', 't2.position_title', 't5.ShiftName', 't5.StartTime', 't5.EndTime', 't7.InTime_Location', 't7.OutTime_Location')
                    ->orderBy('t3.created_at', 'DESC')
                    ->get();
                // #region agent log
                $logPath = base_path('.cursor/debug.log');
                @file_put_contents($logPath, json_encode(['sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'D','location'=>'TimeandAttendanceDashboardController.php:797','message'=>'Second query result','data'=>['count'=>$Rosterdata->count()],'timestamp'=>time()*1000])."\n", FILE_APPEND);
                // #endregion
            } catch (\Exception $e) {
                // #region agent log
                $logPath = base_path('.cursor/debug.log');
                @file_put_contents($logPath, json_encode(['sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'D','location'=>'TimeandAttendanceDashboardController.php:799','message'=>'Query exception','data'=>['error'=>$e->getMessage()],'timestamp'=>time()*1000])."\n", FILE_APPEND);
                // #endregion
                return response()->json([
                    'data' => [],
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'error' => $e->getMessage()
                ]);
            }

            // Fetch the data and map results
            $Rosterdata = $Rosterdata->map(function ($item) {
                // Handle cases where shift data might be null
                if ($item->ShiftName && $item->StartTime && $item->EndTime) {
                    $startTime = Carbon::parse($item->StartTime);
                    $endTime = Carbon::parse($item->EndTime);
                    $item->Shift = ucfirst($item->ShiftName) . '-' . $startTime->format('h:i A') . ' - ' . $endTime->format('h:i A');
                } else {
                    $item->Shift = 'N/A';
                }
                $item->EmployeeName = ucfirst($item->first_name . ' ' . $item->last_name);
                $item->Position = ucfirst($item->position_title ?? 'N/A');
                $item->profileImg = Common::getResortUserPicture($item->Parentid);
                $item->action = '<a href="'.route('resort.timeandattendance.hoddashboard').'" class="btn btn-sm btn-outline-primary">View</a>';
                return $item;
            });

            // Return simple JSON response for client-side DataTables
            $responseData = [
                'data' => $Rosterdata->values()->all(),
                'recordsTotal' => $Rosterdata->count(),
                'recordsFiltered' => $Rosterdata->count()
            ];
            return response()->json($responseData);
        }
        // #region agent log
        $logPath = base_path('.cursor/debug.log');
        @file_put_contents($logPath, json_encode(['sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'B','location'=>'TimeandAttendanceDashboardController.php:838','message'=>'Not AJAX request','data'=>[],'timestamp'=>time()*1000])."\n", FILE_APPEND);
        // #endregion

    }

    public function MonthOverTimeChart($date)
    {
        $currentMonth = Carbon::now()->startOfMonth();
        $months1 = [];

        // Get last 4 months including current month
        for ($i = -3; $i <= 0; $i++) {
            $months1[] = $currentMonth->copy()->addMonths($i)->format('M Y');
        }

        $Dept_id = $this->resort->GetEmployee->Dept_id ?? null;
        $Rank = $this->resort->GetEmployee->rank ?? null;
        $resort_id = $this->resort->resort_id;
        $canViewAll = $this->canViewAllDepartments();

        // Calculate date range for last 4 months
        $startDate = $currentMonth->copy()->subMonths(3)->startOfMonth();
        $endDate = $currentMonth->copy()->endOfMonth();

        // Get all holiday dates (Fridays + public holidays) for the date range
        $holidayDates = $this->getHolidayDates($resort_id, $startDate, $endDate);

        // Initialize month arrays
        $months = [];
        $normalOtData = [];
        $holidayOtData = [];
        $totalOtData = [];

        foreach ($months1 as $month) {
            $months[$month] = $month;
            $normalOtData[$month] = 0;
            $holidayOtData[$month] = 0;
            $totalOtData[$month] = 0;
        }

        // Query overtime data from duty_roster_entries table
        $overtimeQuery = DutyRosterEntry::join('employees', 'duty_roster_entries.Emp_id', '=', 'employees.id')
                                        ->join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                                        ->where('duty_roster_entries.resort_id', $resort_id)
                                        ->whereNotNull('duty_roster_entries.OverTime')
                                        ->where('duty_roster_entries.OverTime', '!=', '00:00')
                                        ->where('duty_roster_entries.OverTime', '!=', '')
                                        ->whereBetween('duty_roster_entries.date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                                        ->where('employees.status', 'Active')
                                        ->select(
                                            'duty_roster_entries.date',
                                            'duty_roster_entries.OverTime',
                                            'duty_roster_entries.Emp_id'
                                        );

        // Apply filters based on user permissions:
        // - HR HOD (rank 2 in HR dept) and XCOM (rank 1): can see ALL employees (no filters applied)
        // - Other department HODs: can see ONLY their department employees who are their subordinates
        if (!$canViewAll) {
            // Filter by department for non-HR HOD/XCOM users
            if ($Dept_id) {
                $overtimeQuery->where('employees.Dept_id', $Dept_id);
            }

            // Filter by subordinates (employees under this HOD)
            $overtimeQuery->whereIn('employees.id', $this->underEmp_id);
        }

        $overtimeData = $overtimeQuery->get();

        // Process each overtime entry
        foreach ($overtimeData as $entry) {
            $entryDate = Carbon::parse($entry->date);
            $monthKey = $entryDate->format('M Y');

            // Skip if month is not in our range
            if (!in_array($monthKey, $months1)) {
                continue;
            }

            // Convert overtime from HH:MM to decimal hours
            $overtimeHours = $this->timeToHours($entry->OverTime);

            // Check if this date is a holiday (Friday or public holiday)
            $dateStr = $entryDate->format('Y-m-d');
            $isHoliday = in_array($dateStr, $holidayDates);

            if ($isHoliday) {
                // Add to holiday OT
                $holidayOtData[$monthKey] += $overtimeHours;
            } else {
                // Add to normal OT
                $normalOtData[$monthKey] += $overtimeHours;
            }

            // Add to total OT
            $totalOtData[$monthKey] += $overtimeHours;
        }

        // Round the values to 2 decimal places
        foreach ($months1 as $month) {
            $normalOtData[$month] = round($normalOtData[$month], 2);
            $holidayOtData[$month] = round($holidayOtData[$month], 2);
            $totalOtData[$month] = round($totalOtData[$month], 2);
        }

        $data = [
            'labels' => array_values($months),
            'datasets' => [
                [
                    'label' => 'Normal OT',
                    'data' => array_values($normalOtData),
                    'backgroundColor' => '#014653',
                    'borderColor' => '#014653',
                    'borderWidth' => 1,
                    'borderRadius' => 3,
                    'barThickness' => 14
                ],
                [
                    'label' => 'Holiday OT',
                    'data' => array_values($holidayOtData),
                    'backgroundColor' => '#2EACB3',
                    'borderColor' => '#2EACB3',
                    'borderWidth' => 1,
                    'borderRadius' => 3,
                    'barThickness' => 14
                ],
                [
                    'label' => 'Total OT Hours',
                    'data' => array_values($totalOtData),
                    'backgroundColor' => '#FED049',
                    'borderColor' => '#FED049',
                    'borderWidth' => 1,
                    'borderRadius' => 3,
                    'barThickness' => 14
                ]
            ]
        ];

        return response()->json($data);
    }

    public function GetYearWiseAttandanceData($currentYear,$date)
    {
        $Dept_id = $this->resort->GetEmployee->Dept_id ?? null;
        $Rank = $this->resort->GetEmployee->rank ?? null;
        $canViewAll = $this->canViewAllDepartments();

        // Get base employee query for counting active employees
        $employeeBaseQuery = Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                            ->where("t1.resort_id", $this->resort->resort_id)
                            ->where('employees.status', 'Active');

        // Apply department filter only if user cannot view all departments
        if (!$canViewAll && $Dept_id) {
            $employeeBaseQuery->where("employees.Dept_id", $Dept_id);
        }

        // Apply subordinate filter only if user cannot view all departments
        if (!$canViewAll) {
            $employeeBaseQuery->whereIn('employees.id', $this->underEmp_id);
        }

        // Get total number of active employees (for all months - this is constant)
        $totalActiveEmployees = $employeeBaseQuery->distinct('employees.id')->count('employees.id');

        // Calculate working days per month from duty rosters
        // Working days = distinct dates where employees had duty rosters (excluding DayOff)
        $workingDaysQuery = Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                            ->join('duty_rosters as t2', "t2.Emp_id", "=", "employees.id")
                            ->join('parent_attendaces as t3', "t3.roster_id", "=", "t2.id")
                            ->where('t2.Year', $currentYear)
                            ->whereRaw("YEAR(t3.date) = ?", [$currentYear])
                            ->where("t1.resort_id", $this->resort->resort_id)
                            ->whereNotIn('t3.Status', ['DayOff'])
                            ->select(DB::raw("MONTH(t3.date) as month"), DB::raw("COUNT(DISTINCT DATE(t3.date)) as working_days"));

        // Apply department filter only if user cannot view all departments
        if (!$canViewAll && $Dept_id) {
            $workingDaysQuery->where("employees.Dept_id", $Dept_id);
        }

        // Apply subordinate filter only if user cannot view all departments
        if (!$canViewAll) {
            $workingDaysQuery->whereIn('employees.id', $this->underEmp_id);
        }

        $workingDaysByMonth = $workingDaysQuery->groupBy(DB::raw("MONTH(t3.date)"))->get()->keyBy('month');

        // If no working days found for a month, calculate from calendar days
        // This ensures we have working days for all months
        for ($month = 1; $month <= 12; $month++) {
            if (!$workingDaysByMonth->has($month)) {
                $daysInMonth = Carbon::createFromDate($currentYear, $month, 1)->daysInMonth;
                // Approximate working days (excluding weekends) - roughly 22 days per month
                $estimatedWorkingDays = round($daysInMonth * 0.714); // ~22/31 ratio
                $workingDaysByMonth->put($month, (object)['month' => $month, 'working_days' => $estimatedWorkingDays]);
            }
        }

        // Get total present days per month (sum of all present days for all employees)
        // This counts each employee's present day, so if 10 employees were present on a day, that's 10 present days
        $presentDaysQuery = Employee::join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                            ->join('duty_rosters as t2', 't2.Emp_id', '=', 'employees.id')
                            ->join('parent_attendaces as t3', 't3.roster_id', '=', 't2.id')
                            ->select(
                                DB::raw("MONTH(t3.date) as month"),
                                DB::raw("COUNT(*) as total_present_days")
                            )
                            ->where("t1.resort_id", $this->resort->resort_id)
                            ->where('t2.Year', $currentYear)
                            ->whereRaw("YEAR(t3.date) = ?", [$currentYear])
                            ->whereIn('t3.Status', ['Present', 'HalfDay', 'On-Time', 'Late', 'ShortLeave', 'HalfDayLeave'])
                            ->whereNotNull('t3.CheckingTime')
                            ->whereNotNull('t3.CheckingOutTime');

        // Apply department filter only if user cannot view all departments
        if (!$canViewAll && $Dept_id) {
            $presentDaysQuery->where("employees.Dept_id", $Dept_id);
        }

        // Apply subordinate filter only if user cannot view all departments
        if (!$canViewAll) {
            $presentDaysQuery->whereIn('employees.id', $this->underEmp_id);
        }

        // Final query to group and order the data
        $attendanceData = $presentDaysQuery
            ->groupBy(DB::raw("MONTH(t3.date)"))
            ->orderBy(DB::raw("MONTH(t3.date)"))
            ->get();

        // Calculate attendance percentage for each month
        $attendanceData = $attendanceData->map(function ($data) use ($workingDaysByMonth, $totalActiveEmployees) {
            $month = $data->month;
            $workingDaysInMonth = $workingDaysByMonth->get($month);
            $workingDays = $workingDaysInMonth ? $workingDaysInMonth->working_days : 0;

            // Total possible working days = Number of employees × Number of working days in the month
            $totalPossibleWorkingDays = $totalActiveEmployees * $workingDays;

            // Total present days (already calculated as count of present records)
            $totalPresentDays = $data->total_present_days;

            // Attendance percentage = (Total present days / Total possible working days) × 100
            if ($totalPossibleWorkingDays != 0) {
                $attendancePercentage = ($totalPresentDays / $totalPossibleWorkingDays) * 100;
            } else {
                $attendancePercentage = 0;
            }

            return [
                'month' => $month,
                'attendance_percentage' => round($attendancePercentage, 2)
            ];
        });


        // Format the data for Chart.js
        $labelsAttendance = [];
        $attendancePercentages = [];

        // Initialize an array to ensure all months are accounted for
        $attendanceDataFormatted = [];
        foreach ($attendanceData as $data) {
            $attendanceDataFormatted[$data['month']] = $data;
        }

        // Fill in missing months (if any)
        for ($i = 1; $i <= 12; $i++) {
            $attendancePercentages[] = isset($attendanceDataFormatted[$i]) ? $attendanceDataFormatted[$i]['attendance_percentage'] : 0;
            $labelsAttendance[] = Carbon::createFromDate($currentYear, $i, 1)->format('M Y');
        }

        // Prepare the data for the chart
        $data = [
            'labels' => $labelsAttendance,
            'datasets' => [
                [
                    'label' => 'Attendance Percentage',
                    'data' => $attendancePercentages,
                    'backgroundColor' => '#014653',
                    'borderColor' => '#014653',
                    'borderWidth' => 1,
                    'borderRadius' => 6,
                    'barThickness' => 25,
                ],
            ],
        ];


        return response()->json($data);

    }

    public function Tododata()
    {
        $Rank = $this->resort->GetEmployee->rank ?? null;
        $hod = $this->resort->GetEmployee->id ?? null;
        $canViewAll = $this->canViewAllDepartments();
        $isDeptHOD = $this->isDepartmentHOD();
        $Dept_id = $this->resort->GetEmployee->Dept_id ?? null;
        $today = Carbon::today()->format('Y-m-d');
        $yesterday = Carbon::yesterday()->format('Y-m-d');
        $currentTime = Carbon::now();
        $gracePeriodMinutes = 10; // 10-minute grace period

        // Get employees with duty roster for today only
        $dutyRosterQuery = DB::table('duty_rosters as t2')
            ->join('employees', 'employees.id', '=', 't2.Emp_id')
            ->join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
            ->join('shift_settings as t4', 't4.id', '=', 't2.Shift_id')
            ->where('t1.resort_id', $this->resort->resort_id)
            ->where('employees.status', 'Active')
            ->whereDate('t2.ShiftDate', $today);

        // Apply department and subordinate filters
        if (!$canViewAll && !$isDeptHOD) {
            $dutyRosterQuery->whereIn('employees.id', $this->underEmp_id);
        }

        if (!$canViewAll && $isDeptHOD && $Dept_id) {
            $dutyRosterQuery->where('employees.Dept_id', $Dept_id);
        }

        if (!$canViewAll && $Dept_id) {
            $dutyRosterQuery->where('employees.Dept_id', $Dept_id);
        }

        $dutyRosters = $dutyRosterQuery->select([
            't2.id as roster_id',
            't2.Emp_id',
            't2.Shift_id',
            't1.first_name',
            't1.last_name',
            't1.id as Parentid',
            't4.StartTime',
            't4.EndTime',
            't4.ShiftName',
            'employees.id as employee_id',
            'employees.Emp_id',
        ])->get();

        $todoList = collect();

        // Check for previous day overtime (employees who checked out yesterday but are still working)
        $yesterdayAttendances = ParentAttendace::where('resort_id', $this->resort->resort_id)
            ->whereDate('date', $yesterday)
            ->whereNotNull('CheckingOutTime')
            ->whereHas('Employee', function ($query) use ($Dept_id, $canViewAll, $isDeptHOD) {
                if (!$canViewAll && !$isDeptHOD) {
                    $query->whereIn('id', $this->underEmp_id);
                }
                if (!$canViewAll && $isDeptHOD && $Dept_id) {
                    $query->where('Dept_id', $Dept_id);
                }
                if (!$canViewAll && $Dept_id) {
                    $query->where('Dept_id', $Dept_id);
                }
            })
            ->with(['Employee.resortAdmin', 'Getshift'])
            ->get();

        foreach ($yesterdayAttendances as $yesterdayAttendance) {
            // Check if employee has a duty roster for today (indicating they're still working)
            $todayRoster = DB::table('duty_rosters')
                ->where('Emp_id', $yesterdayAttendance->Emp_id)
                ->whereDate('ShiftDate', $today)
                ->first();

            if ($todayRoster) {
                // Get shift info for yesterday
                $yesterdayShift = DB::table('shift_settings')
                    ->where('id', $yesterdayAttendance->Shift_id)
                    ->first();

                if ($yesterdayShift) {
                    $yesterdayStartTime = Carbon::parse($yesterday . ' ' . $yesterdayShift->StartTime);
                    $yesterdayEndTime = Carbon::parse($yesterday . ' ' . $yesterdayShift->EndTime);

                    if ($yesterdayEndTime->lessThan($yesterdayStartTime)) {
                        $yesterdayEndTime->addDay();
                    }

                    // Get overtime from attendance or duty roster entry
                    $yesterdayOvertime = $yesterdayAttendance->OverTime ?? '00:00';
                    $yesterdayDutyEntry = DB::table('duty_roster_entries')
                        ->where('Emp_id', $yesterdayAttendance->Emp_id)
                        ->whereDate('date', $yesterday)
                        ->first();

                    if ($yesterdayDutyEntry && $yesterdayDutyEntry->OverTime) {
                        $yesterdayOvertime = $yesterdayDutyEntry->OverTime;
                    }

                    // Calculate end time with overtime
                    $endTimeWithOvertime = $yesterdayEndTime->copy();
                    if ($yesterdayOvertime && $yesterdayOvertime != '00:00') {
                        list($otHours, $otMinutes) = explode(':', $yesterdayOvertime);
                        $endTimeWithOvertime->addHours($otHours)->addMinutes($otMinutes);
                    }

                    // Calculate difference in hours - overtime worked beyond expected end time
                    $checkOutTime = Carbon::parse($yesterdayAttendance->CheckingOutTime);
                    // If checkout time is after expected end time, calculate the difference (overtime)
                    if ($checkOutTime->greaterThan($endTimeWithOvertime)) {
                        $differenceInMinutes = $checkOutTime->diffInMinutes($endTimeWithOvertime);
                        $diffHours = intval($differenceInMinutes / 60);
                        $diffMins = $differenceInMinutes % 60;
                        $differenceInHoursFormatted = $diffHours . ' hours and ' . $diffMins . ' minutes';
                    } else {
                        // If checkout was before expected end, no overtime
                        $differenceInHoursFormatted = '0 hours and 0 minutes';
                    }

                    $employee = $yesterdayAttendance->Employee;
                    $resortAdmin = $employee ? $employee->resortAdmin : null;

                    if ($resortAdmin) {
                        $todoItem = (object)[
                            'roster_id' => $todayRoster->id,
                            'attendance_id' => $yesterdayAttendance->id,
                            'employee_id' => $employee->id,
                            'Emp_id' => $employee->Emp_id,
                            'first_name' => $resortAdmin->first_name,
                            'last_name' => $resortAdmin->last_name,
                            'Parentid' => $resortAdmin->id,
                            'EmployeeName' => ucfirst($resortAdmin->first_name . ' ' . $resortAdmin->last_name),
                            'profileImg' => Common::getResortUserPicture($resortAdmin->id),
                            'ShiftName' => $yesterdayShift->ShiftName,
                            'StartTime' => $yesterdayStartTime->format('h:i A'),
                            'EndTime' => $yesterdayEndTime->format('h:i A'),
                            'EndTimeWithOvertime' => $endTimeWithOvertime->format('h:i A'),
                            'ExpectedEndTime' => $endTimeWithOvertime->format('h:i A'),
                            'OverTime' => $yesterdayOvertime,
                            'differenceInHours' => $differenceInHoursFormatted,
                            'flag' => 'previous_day',
                            'action_type' => 'overtime',
                            'message' => 'Overtime from yesterday',
                            'Shift_id' => $yesterdayAttendance->Shift_id,
                            'date' => $yesterday,
                            'CheckingTime' => $yesterdayAttendance->CheckingTime,
                            'CheckingOutTime' => $yesterdayAttendance->CheckingOutTime,
                            'OTStatus' => $yesterdayAttendance->OTStatus ?? null,
                        ];

                        $todoList->push($todoItem);
                    }
                }
            }
        }

        foreach ($dutyRosters as $roster) {
            // Get existing attendance record for today if any
            $attendance = ParentAttendace::where('roster_id', $roster->roster_id)
                ->where('date', $today)
                ->first();

            // Get overtime from duty_roster_entries if exists
            $dutyRosterEntry = DB::table('duty_roster_entries')
                ->where('roster_id', $roster->roster_id)
                ->whereDate('date', $today)
                ->first();

            $overtime = $dutyRosterEntry ? ($dutyRosterEntry->OverTime ?? '00:00') : '00:00';

            // Parse shift times - combine with today's date
            $shiftStartTime = Carbon::parse($today . ' ' . $roster->StartTime);
            $shiftEndTime = Carbon::parse($today . ' ' . $roster->EndTime);

            // Handle overnight shifts (end time < start time means next day)
            if ($shiftEndTime->lessThan($shiftStartTime)) {
                $shiftEndTime->addDay();
            }

            // Calculate expected end time with overtime
            $expectedEndTime = $shiftEndTime->copy();
            if ($overtime && $overtime != '00:00') {
                list($otHours, $otMinutes) = explode(':', $overtime);
                $expectedEndTime->addHours($otHours)->addMinutes($otMinutes);
            }

            // Calculate grace period deadlines
            $checkInDeadline = $shiftStartTime->copy()->addMinutes($gracePeriodMinutes);
            $checkOutDeadline = $expectedEndTime->copy()->addMinutes($gracePeriodMinutes);

            // Determine action type
            $actionType = null;
            $message = '';

            if (!$attendance || !$attendance->CheckingTime) {
                // No check-in record - check if grace period has passed
                if ($currentTime->greaterThan($checkInDeadline)) {
                    $actionType = 'check_in';
                    $message = 'Pending Check-In';
                }
            } else {
                // Check-in exists - check if check-out is missing
                if (!$attendance->CheckingOutTime) {
                    // Check if grace period has passed
                    if ($currentTime->greaterThan($checkOutDeadline)) {
                        $actionType = 'check_out';
                        $message = 'Pending Check-Out';

                        // Calculate difference in hours for today's missing checkout
                        $differenceInMinutes = $currentTime->diffInMinutes($expectedEndTime);
                        $diffHours = intval($differenceInMinutes / 60);
                        $diffMins = $differenceInMinutes % 60;
                        $differenceInHoursFormatted = $diffHours . ' hours and ' . $diffMins . ' minutes';

                        $todoItem = (object)[
                            'roster_id' => $roster->roster_id,
                            'attendance_id' => $attendance->id ?? null,
                            'employee_id' => $roster->employee_id,
                            'Emp_id' => $roster->Emp_id,
                            'first_name' => $roster->first_name,
                            'last_name' => $roster->last_name,
                            'Parentid' => $roster->Parentid,
                            'EmployeeName' => ucfirst($roster->first_name . ' ' . $roster->last_name),
                            'profileImg' => Common::getResortUserPicture($roster->Parentid),
                            'ShiftName' => $roster->ShiftName,
                            'StartTime' => $shiftStartTime->format('h:i A'),
                            'EndTime' => $shiftEndTime->format('h:i A'),
                            'EndTimeWithOvertime' => $expectedEndTime->format('h:i A'),
                            'ExpectedEndTime' => $expectedEndTime->format('h:i A'),
                            'OverTime' => $overtime,
                            'differenceInHours' => $differenceInHoursFormatted,
                            'flag' => 'today',
                            'action_type' => $actionType,
                            'message' => $message,
                            'Shift_id' => $roster->Shift_id,
                            'date' => $today,
                            'CheckingTime' => $attendance->CheckingTime ?? null,
                            'CheckingOutTime' => $attendance->CheckingOutTime ?? null,
                            'OTStatus' => $attendance->OTStatus ?? null,
                        ];

                        $todoList->push($todoItem);
                    }
                }
            }
        }

        // Fetch employees with pending overtime entries
        $overtimeQuery = EmployeeOvertime::join('employees', 'employee_overtimes.Emp_id', '=', 'employees.id')
            ->join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
            ->where('employee_overtimes.resort_id', $this->resort->resort_id)
            ->where('employee_overtimes.status', 'pending')
            ->where('employees.status', 'Active')
            ->whereDate('employee_overtimes.date', '<=', $today); // Include past dates with pending overtime

        // Apply department and subordinate filters
        if (!$canViewAll && !$isDeptHOD) {
            $overtimeQuery->whereIn('employees.id', $this->underEmp_id);
        }

        if (!$canViewAll && $isDeptHOD && $Dept_id) {
            $overtimeQuery->where('employees.Dept_id', $Dept_id);
        }

        if (!$canViewAll && $Dept_id) {
            $overtimeQuery->where('employees.Dept_id', $Dept_id);
        }

        // Get pending overtime entries grouped by employee and date
        $pendingOvertimes = $overtimeQuery->select([
            'employee_overtimes.Emp_id',
            'employee_overtimes.date',
            'employees.id as employee_id',
            't1.first_name',
            't1.last_name',
            't1.id as Parentid',
            'employees.Emp_id as Emp_code',
        ])
        ->groupBy('employee_overtimes.Emp_id', 'employee_overtimes.date', 'employees.id', 't1.first_name', 't1.last_name', 't1.id', 'employees.Emp_id')
        ->get();

        // Add overtime todo items (one per employee per date)
        foreach ($pendingOvertimes as $ot) {
            // Get employee details
            $employee = Employee::find($ot->employee_id);
            if (!$employee) continue;

            // Get duty roster entry for this date to get overtime from duty_roster_entries
            $dutyRosterEntry = DutyRosterEntry::where('Emp_id', $ot->employee_id)
                ->whereDate('date', $ot->date)
                ->first();

            $dutyRosterOvertime = $dutyRosterEntry ? ($dutyRosterEntry->OverTime ?? '00:00') : '00:00';

            // Get shift info if available
            $shiftName = 'N/A';
            $startTime = 'N/A';
            $endTime = 'N/A';

            if ($dutyRosterEntry && $dutyRosterEntry->Shift_id) {
                $shift = DB::table('shift_settings')->where('id', $dutyRosterEntry->Shift_id)->first();
                if ($shift) {
                    $shiftName = $shift->ShiftName ?? 'N/A';
                    $startTime = $shift->StartTime ?? 'N/A';
                    $endTime = $shift->EndTime ?? 'N/A';
                }
            }

            // Determine flag based on date - if it's today, use 'today', otherwise use null (won't match view conditions)
            $itemDate = Carbon::parse($ot->date);
            $itemFlag = $itemDate->isToday() ? 'today' : ($itemDate->isYesterday() ? 'previous_day' : null);

            $todoItem = (object)[
                'roster_id' => $dutyRosterEntry->roster_id ?? null,
                'attendance_id' => null,
                'employee_id' => $ot->employee_id,
                'Emp_id' => $ot->Emp_code,
                'first_name' => $ot->first_name,
                'last_name' => $ot->last_name,
                'Parentid' => $ot->Parentid,
                'EmployeeName' => ucfirst($ot->first_name . ' ' . $ot->last_name),
                'profileImg' => Common::getResortUserPicture($ot->Parentid),
                'ShiftName' => $shiftName,
                'StartTime' => $startTime,
                'EndTime' => $endTime,
                'ExpectedEndTime' => $endTime,
                'EndTimeWithOvertime' => $endTime, // Add for consistency
                'OverTime' => $dutyRosterOvertime,
                'differenceInHours' => '0 hours and 0 minutes', // Default value
                'flag' => $itemFlag, // Add flag property to prevent undefined property error
                'action_type' => 'overtime_pending',
                'message' => 'Pending OT',
                'Shift_id' => $dutyRosterEntry->Shift_id ?? null,
                'date' => $ot->date,
                'CheckingTime' => null,
                'CheckingOutTime' => null,
                'OTStatus' => null, // Add for consistency
            ];

            $todoList->push($todoItem);
        }

        return $todoList;
    }

    public function Todolist(Request $request)
    {
        $attendanceDataTodoList = $this->Tododata();

        if($request->ajax())
        {
            return datatables()->of($attendanceDataTodoList)
            ->addColumn('Applicant', function ($row) {
                $profilePicture = $row->profileImg ? $row->profileImg : 'assets/images/default-user.svg';
                return '<div class="tableUser-block">
                            <div class="img-circle"><img src="' . $profilePicture . '" alt="user"></div>
                            <span class="userApplicants-btn">' . $row->EmployeeName . '</span> <span class="badge badge-themeLight">' . $row->Emp_id . '</span>
                        </div>';
            })
            ->addColumn('Details', function ($row) {
                $employeeName = $row->EmployeeName;
                $shiftName = $row->ShiftName;
                $startTime = $row->StartTime;
                $endTime = $row->ExpectedEndTime ?? $row->EndTime;
                $message = $row->message;
                $date = $row->date ?? date('Y-m-d');

                if ($row->action_type == 'check_in') {
                    return '<div>
                                <p><strong>' . $message . '</strong></p>
                                <p>' . $employeeName . ' has not checked in for shift: ' . $shiftName . ' (' . $startTime . ' - ' . $endTime . ')</p>
                            </div>';
                } elseif ($row->action_type == 'check_out') {
                    return '<div>
                                <p><strong>' . $message . '</strong></p>
                                <p>' . $employeeName . ' has not checked out for shift: ' . $shiftName . ' (Expected: ' . $endTime . ')</p>
                            </div>';
                } elseif ($row->action_type == 'overtime_pending') {
                    $formattedDate = Carbon::parse($date)->format('d/m/Y');
                    return '<div>
                                <p><strong>' . $message . '</strong></p>
                                <p>' . $employeeName . ' has pending overtime entries for ' . $formattedDate . '</p>
                            </div>';
                }

                return '<div><p>No action required.</p></div>';
            })
            ->addColumn('Action', function ($row) {
                if ($row->action_type == 'overtime_pending') {
                    $date = $row->date ?? date('Y-m-d');
                    return '<button type="button"
                                class="btn btn-xs btn-warning update-overtime-status"
                                data-emp-id="' . $row->employee_id . '"
                                data-date="' . $date . '"
                                data-employee-name="' . htmlspecialchars($row->EmployeeName) . '">
                                <i class="fa-solid fa-clock me-1"></i>Update
                            </button>';
                } else {
                    $buttonClass = $row->action_type == 'check_in' ? 'btn-danger' : 'btn-success';
                    $buttonText = $row->action_type == 'check_in' ? 'Check-In' : 'Check-Out';
                    $icon = $row->action_type == 'check_in' ? 'fa-sign-in-alt' : 'fa-sign-out-alt';

                    return '<button type="button"
                                class="btn btn-sm ' . $buttonClass . ' manual-check-action"
                                data-roster-id="' . $row->roster_id . '"
                                data-action="' . $row->action_type . '"
                                data-employee-name="' . htmlspecialchars($row->EmployeeName) . '">
                                <i class="fa-solid ' . $icon . ' me-1"></i>' . $buttonText . '
                            </button>';
                }
            })
            ->rawColumns(['Applicant', 'Details', 'Action'])
            ->make(true);
        }

        $page_title = "To Do List";
        return view('resorts.timeandattendance.todolist', compact('page_title'));
    }

    /**
     * Handle manual check-in or check-out for employees
     */
    public function ManualCheckInOut(Request $request)
    {
        // try {
            DB::beginTransaction();

            $rosterId = $request->roster_id;
            $action = $request->action; // 'check_in' or 'check_out'
            $today = Carbon::today()->format('Y-m-d');
            $currentTime = Carbon::now()->format('H:i');

            // Get duty roster
            $dutyRoster = DB::table('duty_rosters')->where('id', $rosterId)->first();
            if (!$dutyRoster) {
                return response()->json(['success' => false, 'message' => 'Duty roster not found.']);
            }

            // Get shift settings
            $shiftSettings = DB::table('shift_settings')->where('id', $dutyRoster->Shift_id)->first();
            if (!$shiftSettings) {
                return response()->json(['success' => false, 'message' => 'Shift settings not found.']);
            }

            // Get or create attendance record
            $attendance = ParentAttendace::where('roster_id', $rosterId)
                ->where('date', $today)
                ->first();

            if ($action === 'check_in') {
                if ($attendance && $attendance->CheckingTime) {
                    return response()->json(['success' => false, 'message' => 'Employee has already checked in.']);
                }

                // Create or update attendance record for check-in
                if (!$attendance) {
                    $attendance = ParentAttendace::create([
                        'roster_id' => $rosterId,
                        'resort_id' => $this->resort->resort_id,
                        'Shift_id' => $dutyRoster->Shift_id,
                        'Emp_id' => $dutyRoster->Emp_id,
                        'date' => $today,
                        'CheckingTime' => $shiftSettings->StartTime,
                        'Status' => 'Present',
                        'CheckInCheckOut_Type' => 'Manual',
                    ]);
                } else {
                    $attendance->CheckingTime = $shiftSettings->StartTime;
                    $attendance->Status = 'Present';
                    $attendance->CheckInCheckOut_Type = 'Manual';
                    $attendance->save();
                }

                // Create child attendance record
                ChildAttendace::updateOrCreate(
                    ['Parent_attd_id' => $attendance->id],
                    [
                        'InTime_out' => $shiftSettings->StartTime,
                        'OutTime_out' => '00:00',
                    ]
                );

                DB::commit();
                return response()->json(['success' => true, 'message' => 'Check-in recorded successfully.']);

            } elseif ($action === 'check_out') {
                if (!$attendance || !$attendance->CheckingTime) {
                    return response()->json(['success' => false, 'message' => 'Employee must check in before checking out.']);
                }

                if ($attendance->CheckingOutTime) {
                    return response()->json(['success' => false, 'message' => 'Employee has already checked out.']);
                }

                // Update attendance record for check-out
                $attendance->CheckingOutTime = $shiftSettings->EndTime;
                $attendance->CheckInCheckOut_Type = 'Manual';

                // Calculate total hours worked
                $checkInTime = Carbon::parse($attendance->CheckingTime);
                $checkOutTime = Carbon::parse($shiftSettings->EndTime);
                $totalMinutes = $checkInTime->diffInMinutes($checkOutTime);
                $hours = floor($totalMinutes / 60);
                $minutes = $totalMinutes % 60;
                $attendance->DayWiseTotalHours = sprintf('%02d:%02d', $hours, $minutes);

                $attendance->save();

                // Update child attendance record
                $childAttendance = ChildAttendace::where('Parent_attd_id', $attendance->id)->first();
                if ($childAttendance) {
                    $childAttendance->OutTime_out = $currentTime;
                    $childAttendance->save();
                } else {
                    ChildAttendace::create([
                        'Parent_attd_id' => $attendance->id,
                        'InTime_out' => $attendance->CheckingTime,
                        'OutTime_out' => $shiftSettings->EndTime,
                    ]);
                }

                DB::commit();
                return response()->json(['success' => true, 'message' => 'Check-out recorded successfully.']);
            } elseif ($action === 'overtime_pending') {
                $employeeId = $dutyRoster->Emp_id; // from frontend
            
                // Get pending OT record
                $employeeOt = EmployeeOvertime::where('Emp_id', $employeeId)
                    // ->where('date', $otDate)
                    ->where('status', 'pending')
                    ->first();
                    
                  $otDate = $employeeOt->date;
                if (!$employeeOt) {
                    return response()->json(['success' => false, 'message' => 'Pending OT not found.']);
                }
            
                // Get the duty roster for that employee on that date
                $dutyRoster = DB::table('duty_rosters')
                    ->where('Emp_id', $employeeId)
                    // ->whereDate('ShiftDate', $otDate)
                    ->first();
            
                if (!$dutyRoster) {
                    return response()->json(['success' => false, 'message' => 'Duty roster not found for OT.']);
                }
            
                // Get shift settings for the roster
                
                $shift = DB::table('shift_settings')->where('id', $dutyRoster->Shift_id)->first();
                if (!$shift) {
                    return response()->json(['success' => false, 'message' => 'Shift settings not found for OT.']);   }
                    // Shift EndTime is FULL datetime like: 2026-01-01 09:18:09
                    $shiftEnd = $shift->EndTime;
                    
                    // OT end time can be manual (time only like 11:00) or fallback
                    $otEnd = $request->end_time ?? $shift->EndTime;
                    
                    // 1️⃣ OT starts exactly at shift end (FULL datetime → parse directly)
                    $startTime = Carbon::parse($shiftEnd);
                    
                    // 2️⃣ OT end handling
                    // If OT end has DATE already → parse directly
                    // Else (time only) → attach date from startTime
                    if (preg_match('/^\d{4}-\d{2}-\d{2}/', $otEnd)) {
                        $endTime = Carbon::parse($otEnd);
                    } else {
                        $endTime = Carbon::parse(
                            $startTime->format('Y-m-d') . ' ' . $otEnd
                        );
                    }
                    
                    // 3️⃣ Overnight OT handling
                    if ($endTime->lessThan($startTime)) {
                        $endTime->addDay();
                    }
                    
                    // 4️⃣ Calculate total OT
                    $totalMinutes = $startTime->diffInMinutes($endTime);
                    $hours = floor($totalMinutes / 60);
                    $minutes = $totalMinutes % 60;
                    
                    // 5️⃣ Save OT
                    $employeeOt->start_time = $startTime->format('H:i');
                    $employeeOt->end_time = $endTime->format('H:i');
                    $employeeOt->total_time = sprintf('%02d:%02d', $hours, $minutes);
                    $employeeOt->status = 'approved';
                    $employeeOt->approved_by = auth()->user()->id ?? null;
                    $employeeOt->approved_at = now();
                    $employeeOt->save();
                    
                    DB::commit();                    
                    
                return response()->json(['success' => true, 'message' => 'Pending OT recorded successfully.']);

         
        

        }
        else {
            return response()->json(['success' => false, 'message' => 'Invalid action.']);
        }
                

        // } catch (\Exception $e) {
        //     DB::rollBack();
            
        //     return response()->json([
        //         'success' => false,
        //         'message' => $e->getMessage(),
        //         'file' => $e->getFile(),
        //         'line' => $e->getLine(),
        //         'trace' => $e->getTraceAsString()
        //     ], 500);
        //     // return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
        // }
    }

    /**
     * Get overtime entries for a specific employee and date
     */
    public function GetOvertimeEntries(Request $request)
    {
        try {
            $empId = $request->emp_id;
            $date = $request->date;

            if (!$empId || !$date) {
                return response()->json(['success' => false, 'message' => 'Employee ID and date are required.']);
            }

            // Get employee details
            $employee = Employee::with('resortAdmin')->find($empId);
            if (!$employee) {
                return response()->json(['success' => false, 'message' => 'Employee not found.']);
            }

            // Get overtime entries for this employee and date
            $overtimeEntries = EmployeeOvertime::where('Emp_id', $empId)
                ->whereDate('date', $date)
                ->orderBy('start_time', 'asc')
                ->get();

            // Get duty roster entry to get overtime from duty_roster_entries
            $dutyRosterEntry = DutyRosterEntry::where('Emp_id', $empId)
                ->whereDate('date', $date)
                ->first();

            $dutyRosterOvertime = $dutyRosterEntry ? ($dutyRosterEntry->OverTime ?? '00:00') : '00:00';

            // Get shift info if available
            $shiftName = 'N/A';
            if ($dutyRosterEntry && $dutyRosterEntry->Shift_id) {
                $shift = DB::table('shift_settings')->where('id', $dutyRosterEntry->Shift_id)->first();
                if ($shift) {
                    $shiftName = $shift->ShiftName ?? 'N/A';
                }
            }

            // Get employee profile picture
            $profileImg = Common::getResortUserPicture($employee->Admin_Parent_id);

            // Get employee name
            $employeeName = 'N/A';
            if ($employee->resortAdmin) {
                $employeeName = ucfirst($employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name);
            }

            return response()->json([
                'success' => true,
                'employee' => [
                    'id' => $employee->id,
                    'name' => $employeeName,
                    'emp_id' => $employee->Emp_id,
                    'profile_img' => $profileImg,
                ],
                'date' => Carbon::parse($date)->format('d/m/Y'),
                'shift_name' => $shiftName,
                'duty_roster_overtime' => $dutyRosterOvertime,
                'overtime_entries' => $overtimeEntries->map(function($entry) {
                    return [
                        'id' => $entry->id,
                        'start_time' => $entry->start_time,
                        'end_time' => $entry->end_time,
                        'total_time' => $entry->total_time,
                        'status' => $entry->status,
                        'notes' => $entry->notes,
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    /**
     * Update overtime status
     */
    public function UpdateOvertimeStatus(Request $request)
    {
        try {
            DB::beginTransaction();

            $entries = $request->entries ?? [];
            $approvedBy = $this->resort->id;

            if (empty($entries)) {
                return response()->json(['success' => false, 'message' => 'No entries provided.']);
            }

            foreach ($entries as $entryData) {
                $entryId = $entryData['id'] ?? null;
                $status = $entryData['status'] ?? 'pending';

                if (!$entryId) {
                    continue;
                }

                $overtime = EmployeeOvertime::find($entryId);
                if (!$overtime) {
                    continue;
                }

                $updateData = [
                    'status' => $status,
                ];

                if ($status == 'approved') {
                    $updateData['approved_by'] = $approvedBy;
                    $updateData['approved_at'] = Carbon::now();
                } elseif ($status == 'rejected') {
                    $updateData['rejection_reason'] = $entryData['rejection_reason'] ?? null;
                }

                $overtime->update($updateData);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Overtime status updated successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

}
