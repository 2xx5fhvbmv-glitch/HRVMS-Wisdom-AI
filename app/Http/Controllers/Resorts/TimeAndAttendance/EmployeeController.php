<?php

namespace App\Http\Controllers\Resorts\TimeAndAttendance;
use URL;
use DB;
use DateTime;
use DatePeriod;
use DateInterval;
use Carbon\Carbon;
use App\Helpers\Common;
use App\Models\Employee;
use App\Models\DutyRoster;
use Illuminate\Http\Request;
use App\Models\EmployeeLeave;
use App\Models\LeaveCategory;
use App\Models\ShiftSettings;
use App\Models\ChildAttendace;
use App\Models\ParentAttendace;
use App\Models\ResortDepartment;
use App\Models\ResortBenifitGrid;
use App\Models\ResortBenifitGridChild;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
class EmployeeController extends Controller
{
    protected $resort;
    protected $underEmp_id=[];
    public function __construct()
    {
        $this->resort = $resortId = auth()->guard('resort-admin')->user();
        if(isset($this->resort->GetEmployee))
        {
            $reporting_to = $this->resort->GetEmployee->id;
        }else{
            $reporting_to = $this->resort->id;
        }
        $this->underEmp_id = Common::getSubordinates($reporting_to);
    }
    public function index(Request $request)
    {
        // $Dept_id = $this->resort->GetEmployee->Dept_id;
        $Rank =  $this->resort->GetEmployee->rank ?? '';
        // $page_title = 'Time And Attendance';
        $ResortDepartment = ResortDepartment::where("resort_id",$this->resort->resort_id)->get();
        $currentMonthDays = Carbon::now()->daysInMonth;
        $monthStartingDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $monthEndingDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        $currentDate = Carbon::now();
            $employeesQuery = Employee::select([
                'employees.id as employee_id',
                't1.id as Parentid',
                't1.first_name',
                't1.last_name',
                't1.profile_picture',
                't2.position_title',
                'employees.*',
                DB::raw("
                    (SELECT SUM(
                        DATEDIFF(
                            LEAST(to_date, '{$monthEndingDate}'),
                            GREATEST(from_date, '{$monthStartingDate}')
                        ) + 1
                    )
                    FROM employees_leaves
                    WHERE resort_id = {$this->resort->resort_id}
                    AND emp_id = employees.id
                    AND status = 'Approved'
                    AND (
                        (from_date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}') OR
                        (to_date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}')
                    )
                    ) as LeaveCount
                "),
                DB::raw("
                    (SELECT COUNT(*) FROM parent_attendaces pa
                    JOIN duty_rosters dr ON pa.roster_id = dr.id
                    WHERE dr.emp_id = employees.id
                    AND pa.Status = 'Absent'
                    AND pa.CheckingTime IS NULL
                    AND pa.CheckingOutTime IS NULL
                    AND pa.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                    ) as AbsentCount
                "),
                DB::raw("
                    (SELECT COUNT(*) FROM parent_attendaces pa
                    JOIN duty_rosters dr ON pa.roster_id = dr.id
                    WHERE dr.emp_id = employees.id
                    AND pa.Status IN ('Present', 'HalfDay', 'On-Time', 'Late', 'ShortLeave', 'HalfDayLeave')
                    AND pa.CheckingTime IS NOT NULL
                    AND pa.CheckingOutTime IS NOT NULL
                    AND pa.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                    ) as PresentCount
                "),
                DB::raw("
                    (SELECT COUNT(*) FROM parent_attendaces pa
                    JOIN duty_rosters dr ON pa.roster_id = dr.id
                    WHERE dr.emp_id = employees.id
                    AND pa.Status = 'DayOff'
                    AND pa.CheckingTime IS NULL
                    AND pa.CheckingOutTime IS NULL
                    AND pa.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                    ) as DayOffCount
                "),
                DB::raw("
                    (SELECT GROUP_CONCAT(DISTINCT dr.DayOfDate)
                    FROM parent_attendaces pa
                    JOIN duty_rosters dr ON pa.roster_id = dr.id
                    WHERE dr.Emp_id = employees.id
                    AND dr.DayOfDate IS NOT NULL
                    AND pa.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                    ) as DaysInRoster
                ")
            ])
            ->join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
            ->join('resort_positions as t2', 't2.id', '=', 'employees.Position_id')
            ->where('t1.resort_id', $this->resort->resort_id);

            if($Rank != '3'){
                $employeesQuery->whereIn('employees.id', $this->underEmp_id);
            }
           $employees = $employeesQuery->paginate(10);
                $employees->getCollection()->transform(function ($employee) use ($currentMonthDays) {
                $employee->name = ucfirst($employee->first_name . ' ' . $employee->last_name);
                $employee->profile_picture = Common::getResortUserPicture($employee->Parentid);
                $employee->Position = ucfirst($employee->position_title);
                $employee->TotalWorkingDays = $currentMonthDays;
                $employee->Leave = isset($employee->LeaveCount) ? $employee->LeaveCount : 0 ;
                $employee->Absent = $employee->AbsentCount;
                $employee->Present = $employee->PresentCount;
                $employee->Dayoff = $employee->DayOffCount;
                $employee->CompletedWorkingDays = $employee->PresentCount;
                $employee->TotalDayoff = Common::getWeekCountInMonth();
                $employee->CompletedDayoff = $employee->DayOffCount;
                return $employee;
            });




        $page_title = "Employees";
        return  view('resorts.timeandattendance.employee.index',compact('page_title','ResortDepartment','employees'));
    }

    public function SearchEmployeegird(Request $request)
    {
        $search = $request->search;
        $Poitions = $request->Poitions;
        $Rank =  $this->resort->GetEmployee->rank;
        $Dept_id = $this->resort->GetEmployee->Dept_id;
        $currentDate = Carbon::now();
        $currentMonthDays = Carbon::now()->daysInMonth;
        $monthStartingDate = Carbon::now()->startOfMonth()->format('Y-m-d'); // Format as 'YYYY-MM-DD'
        $monthEndingDate = Carbon::now()->endOfMonth()->format('Y-m-d'); // Format as 'YYYY-MM-DD'
        $employees = Employee::select([
            'employees.id as employee_id',
            't1.id as Parentid',
            't1.first_name',
            't1.last_name',
            't1.profile_picture',
            't2.position_title',
            'employees.*',
            DB::raw("
                (SELECT SUM(
                    DATEDIFF(
                        LEAST(to_date, '{$monthEndingDate}'),
                        GREATEST(from_date, '{$monthStartingDate}')
                    ) + 1
                )
                FROM employees_leaves
                WHERE resort_id = {$this->resort->resort_id}
                  AND emp_id = employees.id
                  AND status = 'Approved'
                  AND (
                      (from_date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}') OR
                      (to_date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}')
                  )
                ) as LeaveCount
            "),
            DB::raw("
                (SELECT COUNT(*) FROM parent_attendaces pa
                 JOIN duty_rosters dr ON pa.roster_id = dr.id
                 WHERE dr.emp_id = employees.id
                   AND pa.Status = 'Absent'
                   AND pa.CheckingTime IS NULL
                   AND pa.CheckingOutTime IS NULL
                   AND pa.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                ) as AbsentCount
            "),
            DB::raw("
                (SELECT COUNT(*) FROM parent_attendaces pa
                 JOIN duty_rosters dr ON pa.roster_id = dr.id
                 WHERE dr.emp_id = employees.id
                   AND pa.Status IN ('Present', 'HalfDay', 'On-Time', 'Late', 'ShortLeave', 'HalfDayLeave')
                   AND pa.CheckingTime IS NOT NULL
                   AND pa.CheckingOutTime IS NOT NULL
                   AND pa.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                ) as PresentCount
            "),
            DB::raw("
                (SELECT COUNT(*) FROM parent_attendaces pa
                 JOIN duty_rosters dr ON pa.roster_id = dr.id
                 WHERE dr.emp_id = employees.id
                   AND pa.Status = 'DayOff'
                   AND pa.CheckingTime IS NULL
                   AND pa.CheckingOutTime IS NULL
                   AND pa.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                ) as DayOffCount
            "),
            DB::raw("
                (SELECT GROUP_CONCAT(DISTINCT dr.DayOfDate)
                FROM parent_attendaces pa
                JOIN duty_rosters dr ON pa.roster_id = dr.id
                WHERE dr.Emp_id = employees.id
                AND dr.DayOfDate IS NOT NULL
                AND pa.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                ) as DaysInRoster
            ")
        ])
        ->join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
        ->join('resort_positions as t2', 't2.id', '=', 'employees.Position_id');
        // ->where('employees.Dept_id', $Dept_id)
        // ->where('t1.resort_id', $this->resort->resort_id)
        // ->where('employees.rank', '!=', $Rank)
        // Apply filters based on search and position
        if($Rank != '3'){
            $employees->whereIn('employees.id', $this->underEmp_id);
        }
        if ($search) {
            $employees->where(function ($query) use ($search) {
                $query->where('t1.first_name', 'LIKE', "%{$search}%")
                      ->orWhere('t1.last_name', 'LIKE', "%{$search}%")
                      ->orWhere('t2.position_title', 'LIKE', "%{$search}%")
                      ->orWhere('employees.Emp_id', 'LIKE', "%{$search}%");
            });
        }

        if ($Poitions) {
            $employees->where('employees.Position_id', $Poitions);
        }

        // Paginate results
        $employees = $employees->paginate(10); // Adjust pagination per your needs

        // Apply the transform to the paginated results
        $employees->getCollection()->transform(function ($employee) use ($currentMonthDays) {
            // Add computed fields
            $employee->name = ucfirst($employee->first_name . ' ' . $employee->last_name);
            $employee->profile_picture = Common::getResortUserPicture($employee->Parentid);
            $employee->Position = ucfirst($employee->position_title);
            $employee->TotalWorkingDays = $currentMonthDays;


            $employee->Leave = isset($employee->LeaveCount) ? $employee->LeaveCount : 0 ;
            $employee->Absent = $employee->AbsentCount;
            $employee->Present = $employee->PresentCount;
            $employee->Dayoff = $employee->DayOffCount;
            $employee->CompletedWorkingDays = $employee->PresentCount;
            $employee->TotalDayoff = Common::getWeekCountInMonth();
            $employee->CompletedDayoff = $employee->DayOffCount;
            return $employee;
        });

        if(!$request->get('page'))
        {
            $view =  view('resorts.renderfiles.timeandattendanceEmployeeGrid', compact('employees'))->render();

            return response()->json(['success'=>true,'view' => $view]);
        }
        else
        {
            $p_details = explode("?" ,$request->get('page'));
            $pageNo = $p_details[0];
            // $pageView = $p_details[1];

            $page = $request->get('page', $pageNo);

            $page_title = "Employees";
            $ResortDepartment = ResortDepartment::where("resort_id",$this->resort->resort_id)->get();
            return  view('resorts.timeandattendance.employee.index',compact('page_title','ResortDepartment','employees'));

        }


    }

    public function EmployeeList(Request $request)
    {
        if($request->ajax())
        {
            $search = $request->searchTerm;
            $position = $request->position;
            $Rank =  $this->resort->GetEmployee->rank;
            $Dept_id = $this->resort->GetEmployee->Dept_id;
            $currentDate = Carbon::now();
            $currentMonthDays = Carbon::now()->daysInMonth;
            $monthStartingDate = Carbon::now()->startOfMonth()->format('Y-m-d'); // Format as 'YYYY-MM-DD'
            $monthEndingDate = Carbon::now()->endOfMonth()->format('Y-m-d'); // Format as 'YYYY-MM-DD'

            $employees = Employee::select([
                'employees.id as employee_id',
                't1.id as Parentid',
                't1.first_name',
                't1.last_name',
                't1.profile_picture',
                't2.position_title',
                't2.code',
                'employees.*',
                DB::raw("
                    (SELECT SUM(
                        DATEDIFF(
                            LEAST(to_date, '{$monthEndingDate}'),
                            GREATEST(from_date, '{$monthStartingDate}')
                        ) + 1
                    )
                    FROM employees_leaves
                    WHERE resort_id = {$this->resort->resort_id}
                      AND emp_id = employees.id
                      AND status = 'Approved'
                      AND (
                          (from_date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}') OR
                          (to_date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}')
                      )
                    ) as LeaveCount
                "),
                DB::raw("
                    (SELECT COUNT(*) FROM parent_attendaces pa
                     JOIN duty_rosters dr ON pa.roster_id = dr.id
                     WHERE dr.emp_id = employees.id
                       AND pa.Status = 'Absent'
                       AND pa.CheckingTime IS NULL
                       AND pa.CheckingOutTime IS NULL
                       AND pa.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                    ) as AbsentCount
                "),
                DB::raw("
                    (SELECT COUNT(*) FROM parent_attendaces pa
                     JOIN duty_rosters dr ON pa.roster_id = dr.id
                     WHERE dr.emp_id = employees.id
                       AND pa.Status IN ('Present', 'HalfDay', 'On-Time', 'Late', 'ShortLeave', 'HalfDayLeave')
                       AND pa.CheckingTime IS NOT NULL
                       AND pa.CheckingOutTime IS NOT NULL
                       AND pa.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                    ) as PresentCount
                "),
                DB::raw("
                    (SELECT COUNT(*) FROM parent_attendaces pa
                     JOIN duty_rosters dr ON pa.roster_id = dr.id
                     WHERE dr.emp_id = employees.id
                       AND pa.Status = 'DayOff'
                       AND pa.CheckingTime IS NULL
                       AND pa.CheckingOutTime IS NULL
                       AND pa.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                    ) as DayOffCount
                "),
                DB::raw("
                    (SELECT GROUP_CONCAT(DISTINCT dr.DayOfDate)
                    FROM parent_attendaces pa
                    JOIN duty_rosters dr ON pa.roster_id = dr.id
                    WHERE dr.Emp_id = employees.id
                    AND dr.DayOfDate IS NOT NULL
                    AND pa.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                    ) as DaysInRoster
                ")
            ])
            ->join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
            ->join('resort_positions as t2', 't2.id', '=', 'employees.Position_id')
            // ->whereIn('employees.id', $this->underEmp_id)
            ->where('t1.resort_id', $this->resort->resort_id);
            // ->where('employees.rank', '!=', $Rank);

            if($Rank != '3'){
                $employees->whereIn('employees.id', $this->underEmp_id);
            }
            // Apply filters based on search and position
            if ($search) {

                $employees->where(function ($query) use ($search) {
                    $query->where('t1.first_name', 'LIKE', "%{$search}%")
                          ->orWhere('t1.last_name', 'LIKE', "%{$search}%")
                          ->orWhere('t2.position_title', 'LIKE', "%{$search}%")
                          ->orWhere('employees.Emp_id', 'LIKE', "%{$search}%");
                });
            }

            if ($position) {
                $employees->where('employees.Position_id', $position);
            }

            $employees = $employees->get()->map(function ($employee) use ($currentMonthDays)
            {
                $employee->name = ucfirst($employee->first_name . ' ' . $employee->last_name);
                $employee->profile_picture = Common::getResortUserPicture($employee->Parentid);
                $employee->Position = ucfirst($employee->position_title);
                $employee->TotalWorkingDays = $currentMonthDays;


                $employee->Leave = isset($employee->LeaveCount) ? $employee->LeaveCount : 0 ;
                $employee->Absent = $employee->AbsentCount;
                $employee->Present = $employee->PresentCount;
                $employee->Dayoff = $employee->DayOffCount;
                $employee->CompletedWorkingDays = $employee->PresentCount;
                $employee->TotalDayoff = Common::getWeekCountInMonth();
                $employee->CompletedDayoff = $employee->DayOffCount;
                return $employee;
            });

            $edit_class = '';
            if(Common::checkRouteWisePermission('resort.timeandattendance.employee',config('settings.resort_permissions.view')) == false){
                $edit_class = 'd-none';
            }
            return datatables()->of($employees)
            ->addColumn('Applicant', function ($row) {
                $id = base64_encode($row->id);
                $profilePicture = $row->profile_picture ? $row->profile_picture : 'assets/images/default-user.svg'; // Fallback image
                return '<div class="tableUser-block">
                            <div class="img-circle"><img src="' . $profilePicture . '" alt="user"></div>
                            <span class="userApplicants-btn">' . ucfirst($row->name) . '</span> <span class="badge badge-themeLight">'.$row->Emp_id.'</span>
                        </div>';
            })
            ->addColumn('Position', function ($row) {
                return $row->position_title ;
            })
            ->addColumn('Leave', function ($row) {
                return isset($row->Leave) ? $row->Leave : 0; // Default to 0 if Leave is not set
            })
            ->addColumn('Absent', function ($row) {
                return isset($row->Absent) ? $row->Absent : 0; // Default to 0
            })
            ->addColumn('Present', function ($row) {
                return isset($row->Present) ? $row->Present : 0; // Default to 0
            })
            ->addColumn('Dayoff', function ($row) {
                return isset($row->Dayoff) ? $row->Dayoff : 0; // Default to 0
            })
            ->addColumn('TotalWorkingDay', function ($row) {
                $present = isset($row->Present) ? $row->Present : 0;
                $workingDays = isset($row->TotalWorkingDays) ? $row->TotalWorkingDays : 0;
                $dayOffs = isset($row->TotalDayoff) ? $row->TotalDayoff : 0;
                return $present . '/' . ($workingDays - $dayOffs);
            })
            ->addColumn('TotalDayOffs', function ($row) {
                $dayOff = isset($row->Dayoff) ? $row->Dayoff : 0;
                $totalDayOff = isset($row->TotalDayoff) ? $row->TotalDayoff : 0;
                return $dayOff . '/' . $totalDayOff;
            })
            ->addColumn('Action', function ($row) use ($edit_class) {
                $id = base64_encode($row->employee_id);
                $route = route('resort.timeandattendance.employee.details', [ $id]);
                return '<a target="_blank" href="'.$route.'" class="btn btn-themeSkyblue btn-sm '.$edit_class.'"  data-id="' . $row->id . '">View Details</a>';
            })
            ->rawColumns(['Applicant', 'Position', 'Leave', 'Absent', 'Present', 'Dayoff', 'TotalWorkingDay', 'TotalDayOffs', 'Action'])
            ->make(true);

        }
    }

    public function EmployeeDetails($id)
    {
        if(Common::checkRouteWisePermission('resort.timeandattendance.employee',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }
        $id = base64_decode($id);
        $page_title = "Employee Details";
        $Dept_id = $this->resort->GetEmployee->Dept_id;
        $Rank =  $this->resort->GetEmployee->rank;
        $currentMonthDays = Carbon::now()->daysInMonth;
        $monthStartingDate = Carbon::now()->startOfMonth()->format('Y-m-d'); // Format as 'YYYY-MM-DD'
        $monthEndingDate = Carbon::now()->endOfMonth()->format('Y-m-d'); // Format as 'YYYY-MM-DD'
        $currentDate = Carbon::now();
        $employee = Employee::join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
            ->join('resort_positions as t2', 't2.id', '=', 'employees.Position_id')
            ->leftjoin('duty_rosters as t3', 't3.Emp_id', '=', 'employees.id')
            ->leftjoin('shift_settings as ss', 'ss.id', '=', 't3.Shift_id') // Assuming duty_rosters has a shift_id
            // ->where('employees.Dept_id', $Dept_id)
            // ->where('t1.resort_id', $this->resort->resort_id)
            // ->where('employees.rank', '!=', $Rank)
            ->where('employees.id', $id)
            ->select(
                't3.id as duty_roster_id',
                't3.DayOfDate',
                't1.id as Parentid',
                't1.first_name',
                't1.last_name',
                't1.profile_picture',
                'employees.id as emp_id',
                'employees.Emp_id as Emp_Code',
                'employees.rank',
                'employees.religion',
                't2.position_title',
                't2.code as PositionCode',
                DB::raw("
                    (SELECT COUNT(*)
                    FROM parent_attendaces pa
                    JOIN duty_rosters dr ON pa.roster_id = dr.id
                    WHERE dr.emp_id = employees.id
                    AND pa.Status IN ('Present', 'HalfDay', 'On-Time', 'Late', 'ShortLeave', 'HalfDayLeave')
                    AND pa.CheckingTime IS NOT NULL
                    AND pa.CheckingOutTime IS NOT NULL
                    AND pa.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                    ) as PresentCount
                "),
                DB::raw("
                    (SELECT COUNT(*)
                    FROM parent_attendaces pa
                    JOIN duty_rosters dr ON pa.roster_id = dr.id
                    WHERE dr.emp_id = employees.id
                    AND pa.Status = 'Absent'
                    AND pa.CheckingTime IS NULL
                    AND pa.CheckingOutTime IS NULL
                    AND pa.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                    ) as AbsentCount
                "),
                DB::raw("
                    (SELECT COUNT(*)
                    FROM parent_attendaces pa
                    JOIN duty_rosters dr ON pa.roster_id = dr.id
                    JOIN shift_settings ss ON ss.id = dr.Shift_id
                    WHERE dr.emp_id = employees.id
                    AND pa.Status IN ('Present', 'On-Time')
                    AND pa.CheckingTime <= ADDTIME(ss.StartTime, '00:10:00')
                    AND pa.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                    ) as OnTimeCount
                "),
                DB::raw("
                    (SELECT COUNT(*)
                    FROM parent_attendaces pa
                    JOIN duty_rosters dr ON pa.roster_id = dr.id
                    JOIN shift_settings ss ON ss.id = dr.Shift_id
                    WHERE dr.emp_id = employees.id
                    AND pa.Status IN ('Present', 'Late')
                    AND pa.CheckingTime > ADDTIME(ss.StartTime, '00:10:00')
                    AND pa.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                    ) as LateCount
                "),
                DB::raw("
                    (
                        SELECT TIME_FORMAT(SEC_TO_TIME(SUM(TIME_TO_SEC(pa.DayWiseTotalHours))), '%H:%i') as TotalHoursWorked
                        FROM duty_rosters dr
                        JOIN parent_attendaces pa ON pa.roster_id = dr.id
                        WHERE dr.emp_id = employees.id
                        AND pa.Status IN ('Present', 'HalfDay', 'On-Time', 'Late', 'ShortLeave', 'HalfDayLeave')
                        AND pa.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                    ) as TotalHoursWorked
                "),
                DB::raw("
                    (
                        SELECT TIME_FORMAT(SEC_TO_TIME(SUM(TIME_TO_SEC(pa.OverTime))), '%H:%i') as TotalOverTime
                        FROM duty_rosters dr
                        JOIN parent_attendaces pa ON pa.roster_id = dr.id
                        WHERE dr.emp_id = employees.id
                        AND pa.Status IN ('Present', 'HalfDay', 'On-Time', 'Late', 'ShortLeave', 'HalfDayLeave')
                        AND pa.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                    ) as TotalOverTime
                "),
                DB::raw("
                    (SELECT COUNT(*) FROM parent_attendaces pa
                    JOIN duty_rosters dr ON pa.roster_id = dr.id
                    WHERE dr.emp_id = employees.id
                    AND pa.Status = 'DayOff'
                    AND pa.CheckingTime IS NULL
                    AND pa.CheckingOutTime IS NULL
                    AND pa.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                    ) as DayOffCount
                "),
            )
            ->first();

            if ($employee)
            {
                $employee->name = ucfirst($employee->first_name . ' ' . $employee->last_name); // Full name
                $employee->profile_picture = Common::getResortUserPicture($employee->Parentid); // Custom profile picture logic
                $employee->Position = ucfirst($employee->position_title); // Position title formatting
                $employee->TotalWorkingDays = Carbon::now()->daysInMonth; // Total days in the current month
                $employee->Leave = $employee->LeaveCount ?? 0; // Handle LeaveCount
                $employee->Absent = $employee->AbsentCount;
                $employee->Present = $employee->PresentCount;
                $employee->Dayoff = $employee->DayOffCount;
                $employee->CompletedWorkingDays = $employee->PresentCount;
                $employee->TotalHoursWorked = $employee->TotalHoursWorked ?? 0;
                $employee->TotalOverTime = $employee->TotalOverTime ?? 0;
                $employee->TotalDayoff = Common::getWeekCountInMonth(); // Assuming a utility function for week count
                $employee->CompletedDayoff = $employee->DayOffCount;
                if (($currentMonthDays - $employee->DayOffCount) > 0)
                {
                    $employee->onTimePercentage = number_format($employee->PresentCount / ($currentMonthDays - $employee->DayOffCount) * 100);
                }
                else
                {
                    $employee->onTimePercentage = 0;
                }
                if (($currentMonthDays - $employee->LateCount) > 0)
                {
                    $employee->LatePercentage= number_format(($employee->LateCount / ($currentMonthDays - $employee->DayOffCount)) * 100);
                }
                else
                {
                    $employee->LatePercentage=  0;
                }
            }
            $religion = $employee->religion;

            if($religion == "1"){
                $religion = "muslim";
            }


            $rank = $employee->rank;

            if($rank == 1 || $rank == 3 || $rank == 7 || $rank == 8){
                $emp_grade = "1";
            }
            else if($rank == 4){
                $emp_grade = "4";
            }
            else if($rank == 2){
                $emp_grade = "2";
            }
            else if($rank == 5){
                $emp_grade = "5";
            }
            else{
                $emp_grade = "6";
            }

            $benefit_grid = ResortBenifitGrid::where('emp_grade', $emp_grade)
                    ->where('resort_id', $this->resort->resort_id)
                    ->first();


            $leave_categories = ResortBenifitGridChild::select(
                'resort_benefit_grid_child.*',
                            'lc.leave_type',
                            'lc.color',
                            'lc.leave_category',
                            'lc.combine_with_other','lc.id as leave_cat_id'
                        )
                        ->join('leave_categories as lc', 'lc.id', '=', 'resort_benefit_grid_child.leave_cat_id')
                        ->leftJoin('employees_leaves as el', 'el.leave_category_id', '=', 'lc.id')
                        ->where('resort_benefit_grid_child.rank', $benefit_grid->emp_grade)
                        ->where('lc.resort_id', $this->resort->resort_id)
                        ->where(function ($query) use ($religion) {
                                $query->where('resort_benefit_grid_child.eligible_emp_type', $this->resort->gender)
                                    ->orWhere('resort_benefit_grid_child.eligible_emp_type', 'all');
                                if ($religion == 'muslim') {
                                    $query->orWhere('resort_benefit_grid_child.eligible_emp_type', $religion);
                                }

                            })

                        ->groupBy('lc.id')
                        ->get()
                        ->map(function ($i) use ($id) {
                            $i->combine_with_other = isset($i->combine_with_other) ? $i->combine_with_other : 0;
                            $i->leave_category = isset($i->leave_category) && $i->leave_category != "" ? $i->leave_category : 0;
                            $i->ThisYearOfused_days = $this->getLeaveCount($id, $i->leave_cat_id);
                            return $i;
            });
            $currentMonthDays = Carbon::now()->daysInMonth;

            $previousDay = Carbon::yesterday()->toDateString(); // Format: 'YYYY-MM-DD'
            $previousMonthStart = Carbon::now()->startOfMonth()->toDateString();
            $previousMonthEnd = Carbon::now()->today()->toDateString();
            $AttendanceHistroy = ParentAttendace::join('shift_settings as ss', 'ss.id', '=', 'parent_attendaces.Shift_id')
                ->join('employees as t1', 't1.id', '=', 'parent_attendaces.Emp_id')
                ->leftjoin('child_attendaces as t2', 't2.Parent_attd_id', '=', 'parent_attendaces.id')
                ->whereIn('parent_attendaces.Status', ['Present','Absent'])
                ->where('t1.id', $id)
                ->orderBy('parent_attendaces.date', 'ASC')
                ->where(function ($query) use ( $previousMonthStart, $previousMonthEnd) {
                    // $query->orWhereBetween('parent_attendaces.date', [$previousMonthStart, $previousMonthEnd]);
                })
                ->paginate(10, [
                    't2.InTime_Location',
                    't2.OutTime_Location',
                    'parent_attendaces.note',
                    'parent_attendaces.date',
                    'ss.ShiftName',
                    'ss.StartTime',
                    'parent_attendaces.CheckingTime',
                    't2.id as Child_id',
                    'parent_attendaces.CheckingOutTime',
                    'parent_attendaces.OverTime',
                    'parent_attendaces.id as ParentAttd_id',
                    'parent_attendaces.Status',
                    'parent_attendaces.DayWiseTotalHours'
                ]);

            // Transform the data after pagination
            $AttendanceHistroy->setCollection(
                $AttendanceHistroy->getCollection()->map(function($h) use($currentMonthDays) {
                    $h->date = Carbon::parse($h->date)->format('d/m/Y');
                    $h->shift = ucfirst($h->ShiftName);

                    // Safely parse CheckingTime
                    if ($h->CheckingTime) {
                        try {
                            // Validate time format (HH:MM or H:MM) and ensure hours are 0-23
                            if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $h->CheckingTime, $matches)) {
                                $hours = (int)$matches[1];
                                if ($hours >= 0 && $hours <= 23) {
                                    $h->CheckInTime = Carbon::parse($h->CheckingTime)->format('h:i A');
                                } else {
                                    $h->CheckInTime = $h->CheckingTime; // Return as-is if invalid
                                }
                            } else {
                                $h->CheckInTime = $h->CheckingTime; // Return as-is if invalid format
                            }
                        } catch (\Exception $e) {
                            $h->CheckInTime = $h->CheckingTime; // Return as-is on parse error
                        }
                    } else {
                        $h->CheckInTime = null;
                    }

                    // Safely parse CheckingOutTime
                    if ($h->CheckingOutTime) {
                        try {
                            // Validate time format (HH:MM or H:MM) and ensure hours are 0-23
                            if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $h->CheckingOutTime, $matches)) {
                                $hours = (int)$matches[1];
                                if ($hours >= 0 && $hours <= 23) {
                                    $h->CheckOutTime = Carbon::parse($h->CheckingOutTime)->format('h:i A');
                                } else {
                                    $h->CheckOutTime = $h->CheckingOutTime; // Return as-is if invalid
                                }
                            } else {
                                $h->CheckOutTime = $h->CheckingOutTime; // Return as-is if invalid format
                            }
                        } catch (\Exception $e) {
                            $h->CheckOutTime = $h->CheckingOutTime; // Return as-is on parse error
                        }
                    } else {
                        $h->CheckOutTime = null;
                    }

                    $h->CheckInTimeOne = $h->CheckingTime;
                    $h->CheckOutTimeOne = $h->CheckingOutTime;
                    $h->OverTime = isset($h->OverTime) ? $h->OverTime : '-';

                    if ($h->CheckingTime && $h->StartTime) {
                        try {
                            // Validate both times before parsing
                            $canParseStartTime = false;
                            $canParseCheckInTime = false;

                            if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $h->StartTime, $matches)) {
                                $hours = (int)$matches[1];
                                if ($hours >= 0 && $hours <= 23) {
                                    $canParseStartTime = true;
                                }
                            }

                            if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $h->CheckingTime, $matches)) {
                                $hours = (int)$matches[1];
                                if ($hours >= 0 && $hours <= 23) {
                                    $canParseCheckInTime = true;
                                }
                            }

                            if ($canParseStartTime && $canParseCheckInTime) {
                                $startTime = Carbon::parse($h->StartTime);
                                $checkInTime = Carbon::parse($h->CheckingTime);
                                $difference = $startTime->diffInMinutes($checkInTime, false);

                                if ($difference <= 10 && $difference >= 0) {
                                    $color = Common::GetThemeColor('On Time');
                                    $h->Status = '<span class="badge badge-default" style="background-color:'. $color.'">On Time</span>';
                                } elseif ($difference > 10) {
                                    $color = Common::GetThemeColor('Late');
                                    $h->Status = '<span class="badge bbadge-default" style="background-color:'. $color.'">Late</span>';
                                } else {
                                    $h->Status = '<span class="badge badge-themeSuccess">Early</span>';
                                }
                            } else {
                                // If times can't be parsed, set default status
                                if ($h->Status == 'Present') {
                                    $h->Status = '<span class="badge badge-themeSuccess">Present</span>';
                                } else {
                                    $h->Status = '<span class="badge badge-default">' . $h->Status . '</span>';
                                }
                            }
                        } catch (\Exception $e) {
                            // On error, set default status
                            if ($h->Status == 'Present') {
                                $h->Status = '<span class="badge badge-themeSuccess">Present</span>';
                            } else {
                                $h->Status = '<span class="badge badge-default">' . $h->Status . '</span>';
                            }
                        }
                    } else {
                        if ($h->Status == 'Absent') {
                            $h->Status = '<span class="badge badge-themeDanger">Absent</span>';
                        } elseif ($h->Status == "DayOff") {
                            $h->Status = '<span class="badge badge-themeDanger">' . $h->Status . '</span>';
                        } else {
                            $h->Status;
                        }
                    }

                    return $h;
                })
        );
        $TotalSum=0;
        $TotalSum = $leave_categories->sum('ThisYearOfused_days');
        return  view('resorts.timeandattendance.employee.Employeesdetails',compact('AttendanceHistroy','leave_categories','page_title','employee','TotalSum'));
    }

    public function HistoryUpdate(Request $request)
    {
        try{
            DB::beginTransaction();
            $child_id = base64_decode($request->attandance_id);
            $CheckingTime = $request->CheckingTime;
            $CheckingOutTime  = $request->CheckingOutTime;
            $OverTime = $request->OverTime;
            $Notes = $request->notes;
            $ParentAttd_id = base64_decode($request->ParentAttd_id);

            $ChildAttendace = ChildAttendace::find($child_id);

            if($ChildAttendace)
            {

                $ChildAttendace-> InTime_out =$CheckingTime;
                $ChildAttendace-> OutTime_out =$CheckingOutTime;
                $ChildAttendace->save();
            }

            ParentAttendace::where('id', $ParentAttd_id)->update(['OverTime' => $OverTime,"CheckingOutTime"=>$CheckingOutTime,"CheckingTime"=>$CheckingTime,"note"=>$Notes]);

            DB::commit();

            return response()->json(['success'=>true,'message' => 'History updated successfully.']);

        }
        catch(Exception $e)
        {
            DB::rollback();
            return response()->json(['success'=>false,'message' => 'Oops somthing wrong to update history.']);
        }
    }

    public function OTStatusUpdate(Request $request)
    {
        try{
            DB::beginTransaction();
            $AttdanceId = $request->AttdanceId;
            $action = $request->action;
            $Approved_id = $this->resort->id;
            $action == 'approve' ? $action = 'Approved' : $action = 'Rejected';
            if($action =="Rejected")
            {
                $ParentAttendace = ParentAttendace::where('id', $AttdanceId)->first();
                $Shift_id = $ParentAttendace->Shift_id;
                $ShiftSettings = ShiftSettings::find($Shift_id);

                if(isset($ParentAttendace->OverTime))
                {
                    $OverTime =Carbon::parse($ParentAttendace->OverTime);


                    $ShiftEndTime = Carbon::parse($ShiftSettings->EndTime);
                    list($overtimeHours, $overtimeMinutes) = explode(':', $OverTime->format('H:i'));

                    // Time add
                    $UpdatedShiftEndTime = $ShiftEndTime->copy()
                    ->addHours($overtimeHours)
                    ->addMinutes($overtimeMinutes);
                    $ParentAttendace->CheckingOutTime = $UpdatedShiftEndTime->format('H:i');
                // Add the Updated Shift End Time to DayWiseTotalHours
                    // $DayWiseTotalHours = Carbon::createFromTimeString($ParentAttendace->DayWiseTotalHours); //its stored as HH:MM

                    // $ParentAttendace->DayWiseTotalHours = $DayWiseTotalHours->addHours($overtimeHours)
                    //                                                         ->addMinutes($overtimeMinutes)
                    //                                                         ->format('H:i');
                    $ParentAttendace->OTApproved_By = $Approved_id;
                    $ParentAttendace->OTStatus =    $action;
                    $ParentAttendace->save();
                }

            }
            else
            {
                ParentAttendace::where('id', $AttdanceId)->update(["OTApproved_By"=>$Approved_id,'OTStatus' => $action]);
            }
            DB::commit();
            return response()->json(['success'=>true,'message' => 'OT '.$action.' successfully.']);
        }
        catch(Exception $e)
        {
            DB::rollback();
            return response()->json(['success'=>false,'message' => 'Oops somthing wrong to Update Ot Status.']);
        }
    }

    public function getLeaveCount($emp_id, $leave_cat_id)
    {
        $currentYearStart = Carbon::now()->startOfYear()->startOfMonth()->format('Y-m-d');
        $currentMonthEnd = Carbon::now()->endOfYear()->endOfMonth()->format('Y-m-d');
        $total_leave_days = EmployeeLeave::where('emp_id', $emp_id)
        ->where('leave_category_id', $leave_cat_id)
        ->where('status', 'Approved')
        ->where(function ($query) use ($currentYearStart, $currentMonthEnd) {
            $query->whereBetween('from_date', [$currentYearStart, $currentMonthEnd])
                  ->orWhereBetween('to_date', [$currentYearStart, $currentMonthEnd]);
        })
        ->sum('total_days');
        return isset($total_leave_days) ? $total_leave_days:0;
    }

    public function EmpDetailsPrint(Request $request)
    {
        $dates = isset($request->hiddenInput) ? explode("-", $request->hiddenInput) : null;
        $monthStartingDate = isset($dates[0])
            ? Carbon::createFromFormat('d/m/Y', $dates[0])->format('Y-m-d') // Correct date format for the '03/01/2025' format
            : Carbon::now()->startOfMonth()->format('Y-m-d'); // Default to the start of the month
        $monthEndingDate = isset($dates[1])
            ? Carbon::createFromFormat('d/m/Y', $dates[1])->format('Y-m-d') // Correct date format for the '03/01/2025' format
            : Carbon::now()->endOfMonth()->format('Y-m-d'); // Default to the end of the month
        $page_title = "Employee Details";
        $Dept_id = $this->resort->GetEmployee->Dept_id;
        $Rank =  $this->resort->GetEmployee->rank;
        $currentMonthDays = Carbon::now()->daysInMonth;
           $currentDate = Carbon::now();
        $id =$request->emp_id;
            $employee = Employee::join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                ->join('resort_positions as t2', 't2.id', '=', 'employees.Position_id')
                ->leftjoin('duty_rosters as t3', 't3.Emp_id', '=', 'employees.id')
                ->leftjoin('shift_settings as ss', 'ss.id', '=', 't3.Shift_id') // Assuming duty_rosters has a shift_id
                // ->where('employees.Dept_id', $Dept_id)
                // ->where('t1.resort_id', $this->resort->resort_id)
                // ->where('employees.rank', '!=', $Rank)
                ->where('employees.id', $id)
                ->select(
                    't3.id as duty_roster_id',
                    't3.DayOfDate',
                    't1.id as Parentid',
                    't1.first_name',
                    't1.last_name',
                    't1.profile_picture',
                    'employees.id as emp_id',
                    'employees.Emp_id as Emp_Code',
                    'employees.rank',
                    'employees.religion',
                    't2.position_title',
                    't2.code as PositionCode',
                    DB::raw("
                        (SELECT COUNT(*)
                        FROM parent_attendaces pa
                        JOIN duty_rosters dr ON pa.roster_id = dr.id
                        WHERE dr.emp_id = employees.id
                        AND pa.Status IN ('Present', 'HalfDay', 'On-Time', 'Late', 'ShortLeave', 'HalfDayLeave')
                        AND pa.CheckingTime IS NOT NULL
                        AND pa.CheckingOutTime IS NOT NULL
                        AND pa.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                        ) as PresentCount
                    "),
                    DB::raw("
                        (SELECT COUNT(*)
                        FROM parent_attendaces pa
                        JOIN duty_rosters dr ON pa.roster_id = dr.id
                        WHERE dr.emp_id = employees.id
                        AND pa.Status = 'Absent'
                        AND pa.CheckingTime IS NULL
                        AND pa.CheckingOutTime IS NULL
                        AND pa.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                        ) as AbsentCount
                    "),
                    DB::raw("
                        (SELECT COUNT(*)
                        FROM parent_attendaces pa
                        JOIN duty_rosters dr ON pa.roster_id = dr.id
                        JOIN shift_settings ss ON ss.id = dr.Shift_id
                        WHERE dr.emp_id = employees.id
                        AND pa.Status IN ('Present', 'On-Time')
                        AND pa.CheckingTime <= ADDTIME(ss.StartTime, '00:10:00')
                        AND pa.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                        ) as OnTimeCount
                    "),
                    DB::raw("
                        (SELECT COUNT(*)
                        FROM parent_attendaces pa
                        JOIN duty_rosters dr ON pa.roster_id = dr.id
                        JOIN shift_settings ss ON ss.id = dr.Shift_id
                        WHERE dr.emp_id = employees.id
                        AND pa.Status IN ('Present', 'Late')
                        AND pa.CheckingTime > ADDTIME(ss.StartTime, '00:10:00')
                        AND pa.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                        ) as LateCount
                    "),
                    DB::raw("
                        (
                            SELECT TIME_FORMAT(SEC_TO_TIME(SUM(TIME_TO_SEC(pa.DayWiseTotalHours))), '%H:%i') as TotalHoursWorked
                            FROM duty_rosters dr
                            JOIN parent_attendaces pa ON pa.roster_id = dr.id
                            WHERE dr.emp_id = employees.id
                            AND pa.Status IN ('Present', 'HalfDay', 'On-Time', 'Late', 'ShortLeave', 'HalfDayLeave')
                            AND pa.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                        ) as TotalHoursWorked
                    "),
                    DB::raw("
                        (
                            SELECT TIME_FORMAT(SEC_TO_TIME(SUM(TIME_TO_SEC(pa.OverTime))), '%H:%i') as TotalOverTime
                            FROM duty_rosters dr
                            JOIN parent_attendaces pa ON pa.roster_id = dr.id
                            WHERE dr.emp_id = employees.id
                            AND pa.Status IN ('Present', 'HalfDay', 'On-Time', 'Late', 'ShortLeave', 'HalfDayLeave')
                            AND pa.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                        ) as TotalOverTime
                    "),
                    DB::raw("
                        (SELECT COUNT(*) FROM parent_attendaces pa
                        JOIN duty_rosters dr ON pa.roster_id = dr.id
                        WHERE dr.emp_id = employees.id
                        AND pa.Status = 'DayOff'
                        AND pa.CheckingTime IS NULL
                        AND pa.CheckingOutTime IS NULL
                        AND pa.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                        ) as DayOffCount
                    "),
                )
                ->first();

                if ($employee)
                {
                    $employee->name = ucfirst($employee->first_name . ' ' . $employee->last_name); // Full name
                    $employee->profile_picture = Common::getResortUserPicture($employee->Parentid); // Custom profile picture logic
                    $employee->Position = ucfirst($employee->position_title); // Position title formatting
                    $employee->TotalWorkingDays = Carbon::now()->daysInMonth; // Total days in the current month
                    $employee->Leave = $employee->LeaveCount ?? 0; // Handle LeaveCount
                    $employee->Absent = $employee->AbsentCount;
                    $employee->Present = $employee->PresentCount;
                    $employee->Dayoff = $employee->DayOffCount;
                    $employee->CompletedWorkingDays = $employee->PresentCount;
                    $employee->TotalHoursWorked = $employee->TotalHoursWorked ?? 0;
                    $employee->TotalOverTime = $employee->TotalOverTime ?? 0;
                    $employee->TotalDayoff = Common::getWeekCountInMonth(); // Assuming a utility function for week count
                    $employee->CompletedDayoff = $employee->DayOffCount;
                    if (($currentMonthDays - $employee->DayOffCount) > 0)
                    {
                        $employee->onTimePercentage = number_format($employee->PresentCount / ($currentMonthDays - $employee->DayOffCount) * 100);
                    }
                    else
                    {
                        $employee->onTimePercentage = 0;
                    }
                    if (($currentMonthDays - $employee->LateCount) > 0)
                    {
                        $employee->LatePercentage= number_format(($employee->LateCount / ($currentMonthDays - $employee->DayOffCount)) * 100);
                    }
                    else
                    {
                        $employee->LatePercentage=  0;
                    }
                }
                $religion = $employee->religion;

                if($religion == "1"){
                    $religion = "muslim";
                }


                $rank = $employee->rank;

                if($rank == 1 || $rank == 3 || $rank == 7 || $rank == 8){
                    $emp_grade = "1";
                }
                else if($rank == 4){
                    $emp_grade = "4";
                }
                else if($rank == 2){
                    $emp_grade = "2";
                }
                else if($rank == 5){
                    $emp_grade = "5";
                }
                else{
                    $emp_grade = "6";
                }

                $benefit_grid = ResortBenifitGrid::where('emp_grade', $emp_grade)
                        ->where('resort_id', $this->resort->resort_id)
                        ->first();

                $TotalSum=0;
                $leave_categories = ResortBenifitGridChild::select(
                    'resort_benefit_grid_child.*',
                    'lc.leave_type',
                    'lc.color',
                    'lc.leave_category',
                    'lc.combine_with_other','lc.id as leave_cat_id'
                )
                ->join('leave_categories as lc', 'lc.id', '=', 'resort_benefit_grid_child.leave_cat_id')
                ->leftJoin('employees_leaves as el', 'el.leave_category_id', '=', 'lc.id')
                ->where('resort_benefit_grid_child.rank', $benefit_grid->emp_grade)
                ->where('lc.resort_id', $this->resort->resort_id)
                ->where(function ($query) use ($religion) {
                        $query->where('resort_benefit_grid_child.eligible_emp_type', $this->resort->gender)
                            ->orWhere('resort_benefit_grid_child.eligible_emp_type', 'all');
                        if ($religion == 'muslim') {
                            $query->orWhere('resort_benefit_grid_child.eligible_emp_type', $religion);
                        }
                        if($religion =="")
                        {
                            $query->Where('resort_benefit_grid_child.eligible_emp_type', 'all');
                        }
                    })
                ->get()
                ->map(function ($i) use ($id,$TotalSum) {


                    $i->combine_with_other = isset($i->combine_with_other) ? $i->combine_with_other : 0;
                    $i->leave_category = isset($i->leave_category) && $i->leave_category != "" ? $i->leave_category : 0;
                    $i->ThisYearOfused_days = $this->getLeaveCount($id, $i->leave_cat_id);
                    return $i;
                });
                $TotalSum = $leave_categories->sum('ThisYearOfused_days');


                $previousDay = Carbon::yesterday()->toDateString(); // Format: 'YYYY-MM-DD'
                $previousMonthStart = Carbon::now()->startOfMonth()->toDateString();
                $previousMonthEnd = Carbon::now()->yesterday()->toDateString();
                $AttendanceHistroy = ParentAttendace::join('shift_settings as ss', 'ss.id', '=', 'parent_attendaces.Shift_id')
                                        ->join('employees as t1', 't1.id', '=', 'parent_attendaces.Emp_id')
                                        ->leftjoin('child_attendaces as t2', 't2.Parent_attd_id', '=', 'parent_attendaces.id')
                                        ->whereIn('parent_attendaces.Status', ['On-Time', 'Present', 'Late', 'DayOff', 'absent', 'ShortLeave', 'HalfDayLeave'])
                                        ->where('t1.id', $id)
                                        ->whereBetween('parent_attendaces.date', [$monthStartingDate, $monthEndingDate])  // Filter based on the selected month
                                        ->get(['t2.InTime_Location', 't2.OutTime_Location', 'parent_attendaces.note', 'parent_attendaces.date', 'ss.ShiftName', 'ss.StartTime', 'parent_attendaces.CheckingTime', 't2.id as Child_id', 'parent_attendaces.CheckingOutTime', 'parent_attendaces.OverTime', 'parent_attendaces.id as ParentAttd_id', 'parent_attendaces.Status', 'parent_attendaces.DayWiseTotalHours'])
                                        ->map(function($h) use($currentMonthDays) {

                                            $h->date = Carbon::parse($h->date)->format('d/m/Y');
                                            $h->shift = ucfirst($h->ShiftName);

                                            // Safely parse CheckingTime
                                            if ($h->CheckingTime) {
                                                try {
                                                    // Validate time format (HH:MM or H:MM) and ensure hours are 0-23
                                                    if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $h->CheckingTime, $matches)) {
                                                        $hours = (int)$matches[1];
                                                        if ($hours >= 0 && $hours <= 23) {
                                                            $h->CheckInTime = Carbon::parse($h->CheckingTime)->format('h:i A');
                                                        } else {
                                                            $h->CheckInTime = $h->CheckingTime; // Return as-is if invalid
                                                        }
                                                    } else {
                                                        $h->CheckInTime = $h->CheckingTime; // Return as-is if invalid format
                                                    }
                                                } catch (\Exception $e) {
                                                    $h->CheckInTime = $h->CheckingTime; // Return as-is on parse error
                                                }
                                            } else {
                                                $h->CheckInTime = null;
                                            }

                                            // Safely parse CheckingOutTime
                                            if ($h->CheckingOutTime) {
                                                try {
                                                    // Validate time format (HH:MM or H:MM) and ensure hours are 0-23
                                                    if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $h->CheckingOutTime, $matches)) {
                                                        $hours = (int)$matches[1];
                                                        if ($hours >= 0 && $hours <= 23) {
                                                            $h->CheckOutTime = Carbon::parse($h->CheckingOutTime)->format('h:i A');
                                                        } else {
                                                            $h->CheckOutTime = $h->CheckingOutTime; // Return as-is if invalid
                                                        }
                                                    } else {
                                                        $h->CheckOutTime = $h->CheckingOutTime; // Return as-is if invalid format
                                                    }
                                                } catch (\Exception $e) {
                                                    $h->CheckOutTime = $h->CheckingOutTime; // Return as-is on parse error
                                                }
                                            } else {
                                                $h->CheckOutTime = null;
                                            }

                                            $h->CheckInTimeOne = $h->CheckingTime;
                                            $h->CheckOutTimeOne = $h->CheckingOutTime;
                                            $h->OverTime = isset($h->OverTime) ? $h->OverTime : '-';

                                            if ($h->CheckingTime && $h->StartTime) {
                                                try {
                                                    // Validate both times before parsing
                                                    $canParseStartTime = false;
                                                    $canParseCheckInTime = false;

                                                    if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $h->StartTime, $matches)) {
                                                        $hours = (int)$matches[1];
                                                        if ($hours >= 0 && $hours <= 23) {
                                                            $canParseStartTime = true;
                                                        }
                                                    }

                                                    if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $h->CheckingTime, $matches)) {
                                                        $hours = (int)$matches[1];
                                                        if ($hours >= 0 && $hours <= 23) {
                                                            $canParseCheckInTime = true;
                                                        }
                                                    }

                                                    if ($canParseStartTime && $canParseCheckInTime) {
                                                        $startTime = Carbon::parse($h->StartTime);
                                                        $checkInTime = Carbon::parse($h->CheckingTime);
                                                        $difference = $startTime->diffInMinutes($checkInTime, false);

                                                        if ($difference <= 10 && $difference >= 0) {
                                                            $h->Status = '<span class="badge badge-themeSuccess">On Time</span>';
                                                        } elseif ($difference > 10) {
                                                            $h->Status = '<span class="badge badge-themePurple">Late</span>';
                                                        } else {
                                                            $h->Status = '<span class="badge badge-themeSuccess">Early</span>';
                                                        }
                                                    } else {
                                                        // If times can't be parsed, set default status
                                                        if ($h->Status == 'Present') {
                                                            $h->Status = '<span class="badge badge-themeSuccess">Present</span>';
                                                        } else {
                                                            $h->Status = '<span class="badge badge-default">' . $h->Status . '</span>';
                                                        }
                                                    }
                                                } catch (\Exception $e) {
                                                    // On error, set default status
                                                    if ($h->Status == 'Present') {
                                                        $h->Status = '<span class="badge badge-themeSuccess">Present</span>';
                                                    } else {
                                                        $h->Status = '<span class="badge badge-default">' . $h->Status . '</span>';
                                                    }
                                                }
                                            } else {
                                                if ($h->Status == 'Absent') {
                                                    $h->Status = '<span class="badge badge-themeDanger">Absent</span>';
                                                } elseif ($h->Status == "DayOff") {
                                                    $h->Status = '<span class="badge badge-themeDanger">'.$h->Status.'</span>';
                                                } else {
                                                    $h->Status;
                                                }
                                            }

                                            return $h;
                                        });

        $page_title="Employee Details Print";
        return view ('resorts.timeandattendance.employee.employeedetailsprint',compact('TotalSum','leave_categories','page_title','employee','AttendanceHistroy'));
    }

    public function AttandanceHisotry(Request $request,$id)
    {
        if($request->ajax())
        {
            $currentMonthDays = Carbon::now()->daysInMonth;
            $previousDay = Carbon::yesterday()->toDateString(); // Format: 'YYYY-MM-DD'
            $previousMonthStart = Carbon::now()->startOfMonth()->toDateString();
            $previousMonthEnd = Carbon::now()->today()->toDateString();
            $AttendanceHistroy =  ParentAttendace::join('shift_settings as ss', 'ss.id', '=', 'parent_attendaces.Shift_id')
                ->join('employees as t1', 't1.id', '=', 'parent_attendaces.Emp_id')
                ->leftjoin('child_attendaces as t2', 't2.Parent_attd_id', '=', 'parent_attendaces.id')
                ->whereIn('parent_attendaces.Status',['On-Time','Present','Late','DayOff','absent','ShortLeave','HalfDayLeave'])
                ->where('t1.id', $id)
                ->where(function ($query) use ($previousDay, $previousMonthStart, $previousMonthEnd) {
                    // $query->where('parent_attendaces.date', $previousDay) // Records for the previous day
                    // dd($previousMonthStart, $previousMonthEnd);
                    $query->orWhereBetween('parent_attendaces.date', [$previousMonthStart,$previousMonthEnd]); // Records for the previous month
                })
                ->get(['t2.InTime_Location','t2.OutTime_Location','parent_attendaces.note','parent_attendaces.date','ss.ShiftName','ss.StartTime','parent_attendaces.CheckingTime','t2.id as Child_id','parent_attendaces.CheckingOutTime','parent_attendaces.OverTime','parent_attendaces.id as ParentAttd_id','parent_attendaces.Status','parent_attendaces.DayWiseTotalHours','parent_attendaces.created_at'])
                ->map(function($h)use($currentMonthDays){
                    $h->date = Carbon::parse($h->date)->format('d/m/Y');
                    $h->shift = ucfirst($h->ShiftName) ;

                    // Safely parse CheckingTime
                    if ($h->CheckingTime) {
                        try {
                            // Validate time format (HH:MM or H:MM) and ensure hours are 0-23
                            if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $h->CheckingTime, $matches)) {
                                $hours = (int)$matches[1];
                                if ($hours >= 0 && $hours <= 23) {
                                    $h->CheckInTime = Carbon::parse($h->CheckingTime)->format('h:i A');
                                } else {
                                    $h->CheckInTime = $h->CheckingTime; // Return as-is if invalid
                                }
                            } else {
                                $h->CheckInTime = $h->CheckingTime; // Return as-is if invalid format
                            }
                        } catch (\Exception $e) {
                            $h->CheckInTime = $h->CheckingTime; // Return as-is on parse error
                        }
                    } else {
                        $h->CheckInTime = null;
                    }

                    // Safely parse CheckingOutTime
                    if ($h->CheckingOutTime) {
                        try {
                            // Validate time format (HH:MM or H:MM) and ensure hours are 0-23
                            if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $h->CheckingOutTime, $matches)) {
                                $hours = (int)$matches[1];
                                if ($hours >= 0 && $hours <= 23) {
                                    $h->CheckOutTime = Carbon::parse($h->CheckingOutTime)->format('h:i A');
                                } else {
                                    $h->CheckOutTime = $h->CheckingOutTime; // Return as-is if invalid
                                }
                            } else {
                                $h->CheckOutTime = $h->CheckingOutTime; // Return as-is if invalid format
                            }
                        } catch (\Exception $e) {
                            $h->CheckOutTime = $h->CheckingOutTime; // Return as-is on parse error
                        }
                    } else {
                        $h->CheckOutTime = null;
                    }

                    $h->CheckInTimeOne = $h->CheckingTime ;
                    $h->CheckOutTimeOne = $h->CheckingOutTime;
                    $h->OverTime =isset($h->OverTime) ?  $h->OverTime : '-';

                    if ($h->CheckingTime && $h->StartTime)
                    {
                        try {
                            // Validate both times before parsing
                            $canParseStartTime = false;
                            $canParseCheckInTime = false;

                            if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $h->StartTime, $matches)) {
                                $hours = (int)$matches[1];
                                if ($hours >= 0 && $hours <= 23) {
                                    $canParseStartTime = true;
                                }
                            }

                            if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $h->CheckingTime, $matches)) {
                                $hours = (int)$matches[1];
                                if ($hours >= 0 && $hours <= 23) {
                                    $canParseCheckInTime = true;
                                }
                            }

                            if ($canParseStartTime && $canParseCheckInTime) {
                                $startTime = Carbon::parse($h->StartTime);
                                $checkInTime = Carbon::parse($h->CheckingTime);
                                $difference = $startTime->diffInMinutes($checkInTime, false); // False for negative values if CheckingTime is before StartTime

                                if ($difference <= 10 && $difference >= 0)
                                {
                                    $color = Common::GetThemeColor('On Time');
                                    $h->Status = '<span class="badge badge-default" style="background-color:'. $color.'">On Time</span>';
                                }
                                elseif ($difference > 10)
                                {
                                    $color = Common::GetThemeColor('Late');
                                    $h->Status = '<span class="badge bbadge-default" style="background-color:'. $color.'">Late</span>';
                                }
                                else
                                {
                                    $h->Status = '<span class="badge badge-themeSuccess">Early</span>';
                                }
                            } else {
                                // If times can't be parsed, set default status
                                if ($h->Status == 'Present') {
                                    $h->Status = '<span class="badge badge-themeSuccess">Present</span>';
                                } else {
                                    $h->Status = '<span class="badge badge-default">' . $h->Status . '</span>';
                                }
                            }
                        } catch (\Exception $e) {
                            // On error, set default status
                            if ($h->Status == 'Present') {
                                $h->Status = '<span class="badge badge-themeSuccess">Present</span>';
                            } else {
                                $h->Status = '<span class="badge badge-default">' . $h->Status . '</span>';
                            }
                        }
                    }
                    else
                    {
                        if($h->Status == 'Absent')
                        {
                            $h->Status = '<span class="badge badge-themeDanger">Absent</span>';
                        }
                        elseif($h->Status == "DayOff")
                        {
                            $h->Status = '<span class="badge badge-themeDanger">'.$h->Status.'</span>';

                        }
                        else
                        {
                            $h->Status;
                        }
                    }
                    return $h;
                });
                $edit_class = '';
                if(Common::checkRouteWisePermission('resort.timeandattendance.employee',config('settings.resort_permissions.edit')) == false){
                    $edit_class ='d-none';
                }
                return datatables()->of($AttendanceHistroy)
                    ->addColumn('Date', function ($row) {
                        return $row->date;
                    })
                    ->addColumn('Shift', function ($row) {
                        return $row->shift;
                    })
                    ->addColumn('CheckInTime', function ($row) {
                        return isset($row->CheckInTime) ? $row->CheckInTime : 0; // Default to 0 if CheckInTime is not set
                    })
                    ->addColumn('CheckOutTime', function ($row) {
                        return isset($row->CheckOutTime) ? $row->CheckOutTime : 0; // Default to 0
                    })
                    ->addColumn('OverTime', function ($row) {
                        return isset($row->OverTime) ? $row->OverTime : 0; // Default to 0
                    })
                    ->addColumn('Status', function ($row) {
                        return isset($row->Status) ? $row->Status : 0; // Default to 0
                    })
                    ->addColumn('Action', function ($row) use ($edit_class) {
                        // Use JavaScript interpolation or direct value to pass PHP variables to JavaScript
                        return '<a href="#" class="btn-lg-icon icon-bg-skyblue LocationHistoryData" data-location="' . $row->InTime_Location . '" data-id="' . $row->id . '">
                            <i class="fa-regular fa-location-dot"></i>
                        </a>
                        <a href="#" class="btn-lg-icon icon-bg-green edit-row-btn '.$edit_class.'" data-note="' . $row->note . '" data-checkinTime="' . $row->CheckInTimeOne . '"
                            data-checkouttime="' . $row->CheckOutTimeOne . '" data-overtime="' . $row->OverTime . '" data-id="' . base64_encode($row->Child_id) . '"
                            data-ParentAttd_id="' . base64_encode($row->ParentAttd_id) . '" data-bs-toggle="modal">
                            <img src="' . URL::asset('resorts_assets/images/edit.svg') . '" icon>
                        </a>';
                    })
                    ->addColumn('created_at', function ($row) {
                        return $row->created_at ?? '';
                    })
                    ->rawColumns(['Date', 'Shift', 'CheckinTime', 'CheckOutTime', 'OverTime', 'Status', 'Action'])
                    ->make(true);
        }
    }

    public function EmpDetailsFilters(Request $request)
    {
        $dates = isset($request->hiddenInput) ? explode("-", $request->hiddenInput) : null;
        $monthStartingDate = isset($dates[0])
            ? Carbon::createFromFormat('d/m/Y', $dates[0])->format('Y-m-d') // Correct date format for the '03/01/2025' format
            : Carbon::now()->startOfMonth()->format('Y-m-d'); // Default to the start of the month
        $monthEndingDate = isset($dates[1])
            ? Carbon::createFromFormat('d/m/Y', $dates[1])->format('Y-m-d') // Correct date format for the '03/01/2025' format
            : Carbon::now()->endOfMonth()->format('Y-m-d'); // Default to the end of the month
        $id = base64_decode($request->emp_id);
        $page_title = "Employee Details";
        $Dept_id = $this->resort->GetEmployee->Dept_id;
        $Rank =  $this->resort->GetEmployee->rank;
        $currentMonthDays = Carbon::now()->daysInMonth;

        $currentDate = Carbon::now();
        $employee = Employee::join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
            ->join('resort_positions as t2', 't2.id', '=', 'employees.Position_id')
            ->leftjoin('duty_rosters as t3', 't3.Emp_id', '=', 'employees.id')
            ->leftjoin('shift_settings as ss', 'ss.id', '=', 't3.Shift_id') // Assuming duty_rosters has a shift_id
            // ->where('employees.Dept_id', $Dept_id)
            // ->where('t1.resort_id', $this->resort->resort_id)
            // ->where('employees.rank', '!=', $Rank)
            ->where('employees.id', $id)
            ->select(
                't3.id as duty_roster_id',
                't3.DayOfDate',
                't1.id as Parentid',
                't1.first_name',
                't1.last_name',
                't1.profile_picture',
                'employees.id as emp_id',
                'employees.Emp_id as Emp_Code',
                'employees.rank',
                'employees.religion',
                't2.position_title',
                't2.code as PositionCode',
                DB::raw("
                    (SELECT COUNT(*)
                    FROM parent_attendaces pa
                    JOIN duty_rosters dr ON pa.roster_id = dr.id
                    WHERE dr.emp_id = employees.id
                    AND pa.Status IN ('Present', 'HalfDay', 'On-Time', 'Late', 'ShortLeave', 'HalfDayLeave')
                    AND pa.CheckingTime IS NOT NULL
                    AND pa.CheckingOutTime IS NOT NULL
                    AND pa.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                    ) as PresentCount
                "),
                DB::raw("
                    (SELECT COUNT(*)
                    FROM parent_attendaces pa
                    JOIN duty_rosters dr ON pa.roster_id = dr.id
                    WHERE dr.emp_id = employees.id
                    AND pa.Status = 'Absent'
                    AND pa.CheckingTime IS NULL
                    AND pa.CheckingOutTime IS NULL
                    AND pa.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                    ) as AbsentCount
                "),
                DB::raw("
                    (SELECT COUNT(*)
                    FROM parent_attendaces pa
                    JOIN duty_rosters dr ON pa.roster_id = dr.id
                    JOIN shift_settings ss ON ss.id = dr.Shift_id
                    WHERE dr.emp_id = employees.id
                    AND pa.Status IN ('Present', 'On-Time')
                    AND pa.CheckingTime <= ADDTIME(ss.StartTime, '00:10:00')
                    AND pa.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                    ) as OnTimeCount
                "),
                DB::raw("
                    (SELECT COUNT(*)
                    FROM parent_attendaces pa
                    JOIN duty_rosters dr ON pa.roster_id = dr.id
                    JOIN shift_settings ss ON ss.id = dr.Shift_id
                    WHERE dr.emp_id = employees.id
                    AND pa.Status IN ('Present', 'Late')
                    AND pa.CheckingTime > ADDTIME(ss.StartTime, '00:10:00')
                    AND pa.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                    ) as LateCount
                "),
                DB::raw("
                    (
                        SELECT TIME_FORMAT(SEC_TO_TIME(SUM(TIME_TO_SEC(pa.DayWiseTotalHours))), '%H:%i') as TotalHoursWorked
                        FROM duty_rosters dr
                        JOIN parent_attendaces pa ON pa.roster_id = dr.id
                        WHERE dr.emp_id = employees.id
                        AND pa.Status IN ('Present', 'HalfDay', 'On-Time', 'Late', 'ShortLeave', 'HalfDayLeave')
                        AND pa.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                    ) as TotalHoursWorked
                "),
                DB::raw("
                    (
                        SELECT TIME_FORMAT(SEC_TO_TIME(SUM(TIME_TO_SEC(pa.OverTime))), '%H:%i') as TotalOverTime
                        FROM duty_rosters dr
                        JOIN parent_attendaces pa ON pa.roster_id = dr.id
                        WHERE dr.emp_id = employees.id
                        AND pa.Status IN ('Present', 'HalfDay', 'On-Time', 'Late', 'ShortLeave', 'HalfDayLeave')
                        AND pa.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                    ) as TotalOverTime
                "),
                DB::raw("
                    (SELECT COUNT(*) FROM parent_attendaces pa
                    JOIN duty_rosters dr ON pa.roster_id = dr.id
                    WHERE dr.emp_id = employees.id
                    AND pa.Status = 'DayOff'
                    AND pa.CheckingTime IS NULL
                    AND pa.CheckingOutTime IS NULL
                    AND pa.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                    ) as DayOffCount
                "),
            )
            ->first();

            if ($employee)
            {
                $employee->name = ucfirst($employee->first_name . ' ' . $employee->last_name); // Full name
                $employee->profile_picture = Common::getResortUserPicture($employee->Parentid); // Custom profile picture logic
                $employee->Position = ucfirst($employee->position_title); // Position title formatting
                $employee->TotalWorkingDays = Carbon::now()->daysInMonth; // Total days in the current month
                $employee->Leave = $employee->LeaveCount ?? 0; // Handle LeaveCount
                $employee->Absent = $employee->AbsentCount;
                $employee->Present = $employee->PresentCount;
                $employee->Dayoff = $employee->DayOffCount;
                $employee->CompletedWorkingDays = $employee->PresentCount;
                $employee->TotalHoursWorked = $employee->TotalHoursWorked ?? 0;
                $employee->TotalOverTime = $employee->TotalOverTime ?? 0;
                $employee->TotalDayoff = Common::getWeekCountInMonth(); // Assuming a utility function for week count
                $employee->CompletedDayoff = $employee->DayOffCount;
                if (($currentMonthDays - $employee->DayOffCount) > 0)
                {
                    $employee->onTimePercentage = number_format($employee->PresentCount / ($currentMonthDays - $employee->DayOffCount) * 100);
                }
                else
                {
                    $employee->onTimePercentage = 0;
                }
                if (($currentMonthDays - $employee->LateCount) > 0)
                {
                    $employee->LatePercentage= number_format(($employee->LateCount / ($currentMonthDays - $employee->DayOffCount)) * 100);
                }
                else
                {
                    $employee->LatePercentage=  0;
                }
            }
            $religion = $employee->religion;

            if($religion == "1"){
                $religion = "muslim";
            }


            $rank = $employee->rank;

            if($rank == 1 || $rank == 3 || $rank == 7 || $rank == 8){
                $emp_grade = "1";
            }
            else if($rank == 4){
                $emp_grade = "4";
            }
            else if($rank == 2){
                $emp_grade = "2";
            }
            else if($rank == 5){
                $emp_grade = "5";
            }
            else{
                $emp_grade = "6";
            }

            $benefit_grid = ResortBenifitGrid::where('emp_grade', $emp_grade)
                    ->where('resort_id', $this->resort->resort_id)
                    ->first();


            $leave_categories = ResortBenifitGridChild::select(
                'resort_benefit_grid_child.*',
                            'lc.leave_type',
                            'lc.color',
                            'lc.leave_category',
                            'lc.combine_with_other','lc.id as leave_cat_id'
                        )
                        ->join('leave_categories as lc', 'lc.id', '=', 'resort_benefit_grid_child.leave_cat_id')
                        ->leftJoin('employees_leaves as el', 'el.leave_category_id', '=', 'lc.id')
                        ->where('resort_benefit_grid_child.rank', $benefit_grid->emp_grade)
                        ->where('lc.resort_id', $this->resort->resort_id)
                        ->where(function ($query) use ($religion) {
                                $query->where('resort_benefit_grid_child.eligible_emp_type', $this->resort->gender)
                                    ->orWhere('resort_benefit_grid_child.eligible_emp_type', 'all');
                                if ($religion == 'muslim') {
                                    $query->orWhere('resort_benefit_grid_child.eligible_emp_type', $religion);
                                }

                            })

                        ->groupBy('lc.id')
                        ->get()
                        ->map(function ($i) use ($id) {
                            $i->combine_with_other = isset($i->combine_with_other) ? $i->combine_with_other : 0;
                            $i->leave_category = isset($i->leave_category) && $i->leave_category != "" ? $i->leave_category : 0;
                            $i->ThisYearOfused_days = $this->getLeaveCount($id, $i->leave_cat_id);
                            return $i;
            });
            $currentMonthDays = Carbon::now()->daysInMonth;

            $previousDay = Carbon::yesterday()->toDateString(); // Format: 'YYYY-MM-DD'
            $previousMonthStart = Carbon::now()->startOfMonth()->toDateString();
            $previousMonthEnd = Carbon::now()->today()->toDateString();
            $AttendanceHistroy = ParentAttendace::join('shift_settings as ss', 'ss.id', '=', 'parent_attendaces.Shift_id')
                ->join('employees as t1', 't1.id', '=', 'parent_attendaces.Emp_id')
                ->leftjoin('child_attendaces as t2', 't2.Parent_attd_id', '=', 'parent_attendaces.id')
                ->whereIn('parent_attendaces.Status', ['Present','Absent'])
                ->where('t1.id', $id)
                ->orderBy('parent_attendaces.date', 'ASC')
                ->where(function ($query) use ( $previousMonthStart, $previousMonthEnd) {
                    // $query->orWhereBetween('parent_attendaces.date', [$previousMonthStart, $previousMonthEnd]);
                })
                ->paginate(10, [
                    't2.InTime_Location',
                    't2.OutTime_Location',
                    'parent_attendaces.note',
                    'parent_attendaces.date',
                    'ss.ShiftName',
                    'ss.StartTime',
                    'parent_attendaces.CheckingTime',
                    't2.id as Child_id',
                    'parent_attendaces.CheckingOutTime',
                    'parent_attendaces.OverTime',
                    'parent_attendaces.id as ParentAttd_id',
                    'parent_attendaces.Status',
                    'parent_attendaces.DayWiseTotalHours'
                ]);

            // Transform the data after pagination
            $AttendanceHistroy->setCollection(
                $AttendanceHistroy->getCollection()->map(function($h) use($currentMonthDays) {
                    $h->date = Carbon::parse($h->date)->format('d/m/Y');
                    $h->shift = ucfirst($h->ShiftName);

                    // Safely parse CheckingTime
                    if ($h->CheckingTime) {
                        try {
                            // Validate time format (HH:MM or H:MM) and ensure hours are 0-23
                            if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $h->CheckingTime, $matches)) {
                                $hours = (int)$matches[1];
                                if ($hours >= 0 && $hours <= 23) {
                                    $h->CheckInTime = Carbon::parse($h->CheckingTime)->format('h:i A');
                                } else {
                                    $h->CheckInTime = $h->CheckingTime; // Return as-is if invalid
                                }
                            } else {
                                $h->CheckInTime = $h->CheckingTime; // Return as-is if invalid format
                            }
                        } catch (\Exception $e) {
                            $h->CheckInTime = $h->CheckingTime; // Return as-is on parse error
                        }
                    } else {
                        $h->CheckInTime = null;
                    }

                    // Safely parse CheckingOutTime
                    if ($h->CheckingOutTime) {
                        try {
                            // Validate time format (HH:MM or H:MM) and ensure hours are 0-23
                            if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $h->CheckingOutTime, $matches)) {
                                $hours = (int)$matches[1];
                                if ($hours >= 0 && $hours <= 23) {
                                    $h->CheckOutTime = Carbon::parse($h->CheckingOutTime)->format('h:i A');
                                } else {
                                    $h->CheckOutTime = $h->CheckingOutTime; // Return as-is if invalid
                                }
                            } else {
                                $h->CheckOutTime = $h->CheckingOutTime; // Return as-is if invalid format
                            }
                        } catch (\Exception $e) {
                            $h->CheckOutTime = $h->CheckingOutTime; // Return as-is on parse error
                        }
                    } else {
                        $h->CheckOutTime = null;
                    }

                    $h->CheckInTimeOne = $h->CheckingTime;
                    $h->CheckOutTimeOne = $h->CheckingOutTime;
                    $h->OverTime = isset($h->OverTime) ? $h->OverTime : '-';

                    if ($h->CheckingTime && $h->StartTime) {
                        try {
                            // Validate both times before parsing
                            $canParseStartTime = false;
                            $canParseCheckInTime = false;

                            if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $h->StartTime, $matches)) {
                                $hours = (int)$matches[1];
                                if ($hours >= 0 && $hours <= 23) {
                                    $canParseStartTime = true;
                                }
                            }

                            if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $h->CheckingTime, $matches)) {
                                $hours = (int)$matches[1];
                                if ($hours >= 0 && $hours <= 23) {
                                    $canParseCheckInTime = true;
                                }
                            }

                            if ($canParseStartTime && $canParseCheckInTime) {
                                $startTime = Carbon::parse($h->StartTime);
                                $checkInTime = Carbon::parse($h->CheckingTime);
                                $difference = $startTime->diffInMinutes($checkInTime, false);

                                if ($difference <= 10 && $difference >= 0) {
                                    $color = Common::GetThemeColor('On Time');
                                    $h->Status = '<span class="badge badge-default" style="background-color:'. $color.'">On Time</span>';
                                } elseif ($difference > 10) {
                                    $color = Common::GetThemeColor('Late');
                                    $h->Status = '<span class="badge bbadge-default" style="background-color:'. $color.'">Late</span>';
                                } else {
                                    $h->Status = '<span class="badge badge-themeSuccess">Early</span>';
                                }
                            } else {
                                // If times can't be parsed, set default status
                                if ($h->Status == 'Present') {
                                    $h->Status = '<span class="badge badge-themeSuccess">Present</span>';
                                } else {
                                    $h->Status = '<span class="badge badge-default">' . $h->Status . '</span>';
                                }
                            }
                        } catch (\Exception $e) {
                            // On error, set default status
                            if ($h->Status == 'Present') {
                                $h->Status = '<span class="badge badge-themeSuccess">Present</span>';
                            } else {
                                $h->Status = '<span class="badge badge-default">' . $h->Status . '</span>';
                            }
                        }
                    } else {
                        if ($h->Status == 'Absent') {
                            $h->Status = '<span class="badge badge-themeDanger">Absent</span>';
                        } elseif ($h->Status == "DayOff") {
                            $h->Status = '<span class="badge badge-themeDanger">' . $h->Status . '</span>';
                        } else {
                            $h->Status;
                        }
                    }

                    return $h;
                })
        );
        $TotalSum=0;
        $TotalSum = $leave_categories->sum('ThisYearOfused_days');
        $view = view('resorts.renderfiles.time_atta_employee_dtetails',compact('AttendanceHistroy','leave_categories','page_title','employee','TotalSum'))->render();
        return response()->json(['html' => $view]);

    }
}
