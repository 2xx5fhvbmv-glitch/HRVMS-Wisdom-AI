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
use App\Models\PayrollConfig;
use App\Models\ResortPosition;
class EmployeeController extends Controller
{
    protected $resort;
    protected $underEmp_id=[];
    public function __construct()
    {
        $this->resort = $resortId = auth()->guard('resort-admin')->user();
        // Laravel instantiates the controller once to gather any
        // controller-defined middleware BEFORE auth:resort-admin ever runs,
        // so an unauthenticated/expired-session hit reaches here with
        // $this->resort null. Unguarded ->id crashed with a raw
        // ErrorException instead of the clean login redirect auth
        // middleware produces once this constructor stops fataling.
        if(isset($this->resort->GetEmployee))
        {
            $reporting_to = $this->resort->GetEmployee->id;
        }else{
            $reporting_to = $this->resort->id ?? null;
        }
        $this->underEmp_id = Common::getSubordinates($reporting_to);
    }

    private function getDetailSelectColumns($resortId, $monthStartingDate, $monthEndingDate)
    {
        return [
            DB::raw("
                (SELECT COUNT(DISTINCT pa.date)
                FROM parent_attendaces pa
                WHERE pa.Emp_id = employees.id
                AND pa.resort_id = {$resortId}
                AND pa.Status IN ('Present', 'HalfDay', 'On-Time', 'Late', 'ShortLeave', 'HalfDayLeave')
                AND pa.CheckingTime IS NOT NULL
                AND TRIM(IFNULL(pa.CheckingTime,'')) NOT IN ('', '00:00', '00:00:00')
                AND pa.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                ) as PresentCount
            "),
            DB::raw("
                -- Absent was previously '(SELECT ... WHERE pa.Status = \"Absent\")' —
                -- but no code path anywhere ever writes a parent_attendaces row
                -- with Status='Absent' (every ChildAttendace/ParentAttendace
                -- create() hardcodes Status='Present' on real check-in), so this
                -- always returned 0 and the real \"Absent\" figure shown to HR was
                -- computed elsewhere as elapsedDays - Present - DayOff - Leave —
                -- which silently folded every un-recorded day-off (see DayOffCount
                -- below, same root problem) into \"Absent\". duty_roster_entries is
                -- the actual per-day SCHEDULE (Status='DayOff' for planned rest
                -- days) — a real absence is a scheduled work day with no matching
                -- Present attendance row and no approved leave covering it.
                (SELECT COUNT(DISTINCT dre.date)
                FROM duty_roster_entries dre
                WHERE dre.Emp_id = employees.id
                AND dre.resort_id = {$resortId}
                AND dre.Shift_id IS NOT NULL
                AND (dre.Status IS NULL OR dre.Status != 'DayOff')
                AND dre.date BETWEEN GREATEST('{$monthStartingDate}', IFNULL(employees.joining_date, '{$monthStartingDate}')) AND LEAST('{$monthEndingDate}', CURDATE())
                AND NOT EXISTS (
                    SELECT 1 FROM parent_attendaces pa2
                    WHERE pa2.Emp_id = employees.id
                    AND pa2.resort_id = {$resortId}
                    AND pa2.date = dre.date
                    AND pa2.Status IN ('Present', 'HalfDay', 'On-Time', 'Late', 'ShortLeave', 'HalfDayLeave')
                    AND pa2.CheckingTime IS NOT NULL AND TRIM(IFNULL(pa2.CheckingTime,'')) NOT IN ('', '00:00', '00:00:00')
                )
                AND NOT EXISTS (
                    SELECT 1 FROM employees_leaves el2
                    WHERE el2.emp_id = employees.id
                    AND el2.resort_id = {$resortId}
                    AND el2.status = 'Approved'
                    AND dre.date BETWEEN el2.from_date AND el2.to_date
                )
                ) as AbsentCount
            "),
            DB::raw("
                (SELECT COUNT(DISTINCT pa.date)
                FROM parent_attendaces pa
                JOIN shift_settings ss2 ON pa.Shift_id = ss2.id
                WHERE pa.Emp_id = employees.id
                AND pa.resort_id = {$resortId}
                AND pa.Status IN ('Present', 'HalfDay', 'On-Time', 'Late', 'ShortLeave', 'HalfDayLeave')
                AND pa.CheckingTime IS NOT NULL AND TRIM(IFNULL(pa.CheckingTime,'')) NOT IN ('', '00:00', '00:00:00')
                AND pa.CheckingTime <= ADDTIME(ss2.StartTime, '00:10:00')
                AND pa.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                ) as OnTimeCount
            "),
            DB::raw("
                (SELECT COUNT(DISTINCT pa.date)
                FROM parent_attendaces pa
                JOIN shift_settings ss2 ON pa.Shift_id = ss2.id
                WHERE pa.Emp_id = employees.id
                AND pa.resort_id = {$resortId}
                AND pa.Status IN ('Present', 'HalfDay', 'On-Time', 'Late', 'ShortLeave', 'HalfDayLeave')
                AND pa.CheckingTime IS NOT NULL AND TRIM(IFNULL(pa.CheckingTime,'')) NOT IN ('', '00:00', '00:00:00')
                AND pa.CheckingTime > ADDTIME(ss2.StartTime, '00:10:00')
                AND pa.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                ) as LateCount
            "),
            DB::raw("
                (
                    SELECT CONCAT(
                        FLOOR(COALESCE(SUM(
                            CASE
                                WHEN pa.DayWiseTotalHours LIKE '%:%' THEN
                                    CAST(SUBSTRING_INDEX(pa.DayWiseTotalHours, ':', 1) AS SIGNED) +
                                    CAST(SUBSTRING_INDEX(pa.DayWiseTotalHours, ':', -1) AS SIGNED) / 60.0
                                ELSE CAST(IFNULL(pa.DayWiseTotalHours, 0) AS DECIMAL(10,2))
                            END
                        ), 0)),
                        ':',
                        LPAD(ROUND(MOD(COALESCE(SUM(
                            CASE
                                WHEN pa.DayWiseTotalHours LIKE '%:%' THEN
                                    CAST(SUBSTRING_INDEX(pa.DayWiseTotalHours, ':', 1) AS SIGNED) * 60 +
                                    CAST(SUBSTRING_INDEX(pa.DayWiseTotalHours, ':', -1) AS SIGNED)
                                ELSE CAST(IFNULL(pa.DayWiseTotalHours, 0) AS DECIMAL(10,2)) * 60
                            END
                        ), 0), 60)), 2, '0')
                    )
                    FROM parent_attendaces pa
                    WHERE pa.Emp_id = employees.id
                    AND pa.resort_id = {$resortId}
                    AND pa.Status IN ('Present', 'HalfDay', 'On-Time', 'Late', 'ShortLeave', 'HalfDayLeave')
                    AND pa.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                ) as TotalHoursWorked
            "),
            DB::raw("
                (
                    SELECT CONCAT(
                        FLOOR(COALESCE(SUM(
                            CASE
                                WHEN pa.OverTime LIKE '%:%' THEN
                                    CAST(SUBSTRING_INDEX(pa.OverTime, ':', 1) AS SIGNED) +
                                    CAST(SUBSTRING_INDEX(pa.OverTime, ':', -1) AS SIGNED) / 60.0
                                ELSE CAST(IFNULL(pa.OverTime, 0) AS DECIMAL(10,2))
                            END
                        ), 0)),
                        ':',
                        LPAD(ROUND(MOD(COALESCE(SUM(
                            CASE
                                WHEN pa.OverTime LIKE '%:%' THEN
                                    CAST(SUBSTRING_INDEX(pa.OverTime, ':', 1) AS SIGNED) * 60 +
                                    CAST(SUBSTRING_INDEX(pa.OverTime, ':', -1) AS SIGNED)
                                ELSE CAST(IFNULL(pa.OverTime, 0) AS DECIMAL(10,2)) * 60
                            END
                        ), 0), 60)), 2, '0')
                    )
                    FROM parent_attendaces pa
                    WHERE pa.Emp_id = employees.id
                    AND pa.resort_id = {$resortId}
                    AND pa.OTStatus = 'Approved'
                    AND pa.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                ) as TotalOverTime
            "),
            DB::raw("
                -- Day-offs are scheduled in duty_roster_entries (Status='DayOff'),
                -- never mirrored into parent_attendaces (which only ever records
                -- Status='Present' rows), so this always returned 0 regardless of
                -- how many rest days the employee actually had that period.
                (SELECT COUNT(DISTINCT dre.date) FROM duty_roster_entries dre
                WHERE dre.Emp_id = employees.id
                AND dre.resort_id = {$resortId}
                AND dre.Status = 'DayOff'
                AND dre.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                ) as DayOffCount
            "),
            DB::raw("
                (SELECT IFNULL(SUM(
                    DATEDIFF(
                        LEAST(to_date, '{$monthEndingDate}'),
                        GREATEST(from_date, '{$monthStartingDate}')
                    ) + 1
                ), 0)
                FROM employees_leaves
                WHERE resort_id = {$resortId}
                AND emp_id = employees.id
                AND status = 'Approved'
                AND from_date <= '{$monthEndingDate}'
                AND to_date >= '{$monthStartingDate}'
                ) as LeaveCount
            "),
        ];
    }

    private function getAttendanceSelectColumns($resortId, $monthStartingDate, $monthEndingDate)
    {
        return [
            DB::raw("
                (SELECT IFNULL(SUM(
                    DATEDIFF(
                        LEAST(to_date, '{$monthEndingDate}'),
                        GREATEST(from_date, '{$monthStartingDate}')
                    ) + 1
                ), 0)
                FROM employees_leaves
                WHERE resort_id = {$resortId}
                AND emp_id = employees.id
                AND status = 'Approved'
                AND from_date <= '{$monthEndingDate}'
                AND to_date >= '{$monthStartingDate}'
                ) as LeaveCount
            "),
            DB::raw("
                -- Same fix as getDetailSelectColumns()'s AbsentCount — real
                -- schedule lives in duty_roster_entries, parent_attendaces never
                -- gets a Status='Absent' row written by any code path.
                (SELECT COUNT(DISTINCT dre.date)
                FROM duty_roster_entries dre
                WHERE dre.Emp_id = employees.id
                AND dre.resort_id = {$resortId}
                AND dre.Shift_id IS NOT NULL
                AND (dre.Status IS NULL OR dre.Status != 'DayOff')
                AND dre.date BETWEEN GREATEST('{$monthStartingDate}', IFNULL(employees.joining_date, '{$monthStartingDate}')) AND LEAST('{$monthEndingDate}', CURDATE())
                AND NOT EXISTS (
                    SELECT 1 FROM parent_attendaces pa2
                    WHERE pa2.Emp_id = employees.id
                    AND pa2.resort_id = {$resortId}
                    AND pa2.date = dre.date
                    AND pa2.Status IN ('Present', 'HalfDay', 'On-Time', 'Late', 'ShortLeave', 'HalfDayLeave')
                    AND pa2.CheckingTime IS NOT NULL AND TRIM(IFNULL(pa2.CheckingTime,'')) NOT IN ('', '00:00', '00:00:00')
                )
                AND NOT EXISTS (
                    SELECT 1 FROM employees_leaves el2
                    WHERE el2.emp_id = employees.id
                    AND el2.resort_id = {$resortId}
                    AND el2.status = 'Approved'
                    AND dre.date BETWEEN el2.from_date AND el2.to_date
                )
                ) as AbsentCount
            "),
            DB::raw("
                (SELECT COUNT(DISTINCT pa.date) FROM parent_attendaces pa
                WHERE pa.Emp_id = employees.id
                AND pa.resort_id = {$resortId}
                AND pa.Status IN ('Present', 'HalfDay', 'On-Time', 'Late', 'ShortLeave', 'HalfDayLeave')
                AND pa.CheckingTime IS NOT NULL
                AND TRIM(IFNULL(pa.CheckingTime,'')) NOT IN ('', '00:00', '00:00:00')
                AND pa.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                ) as PresentCount
            "),
            DB::raw("
                -- Same fix as getDetailSelectColumns()'s DayOffCount.
                (SELECT COUNT(DISTINCT dre.date) FROM duty_roster_entries dre
                WHERE dre.Emp_id = employees.id
                AND dre.resort_id = {$resortId}
                AND dre.Status = 'DayOff'
                AND dre.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                ) as DayOffCount
            "),
            DB::raw("
                (SELECT GROUP_CONCAT(DISTINCT dr.DayOfDate)
                FROM parent_attendaces pa
                JOIN duty_rosters dr ON pa.roster_id = dr.id
                WHERE pa.Emp_id = employees.id
                AND pa.resort_id = {$resortId}
                AND dr.DayOfDate IS NOT NULL
                AND pa.date BETWEEN '{$monthStartingDate}' AND '{$monthEndingDate}'
                ) as DaysInRoster
            ")
        ];
    }

    public function index(Request $request)
    {
        // $Dept_id = $this->resort->GetEmployee->Dept_id;
        $Rank =  $this->resort->GetEmployee->rank ?? '';
        // $page_title = 'Time And Attendance';
        $ResortDepartment = ResortDepartment::where("resort_id",$this->resort->resort_id)->get();
        $cutoffDay = PayrollConfig::where('resort_id', $this->resort->resort_id)->value('cutoff_day') ?? 1;
        $cutoffPeriod = Common::getCurrentCutoffPeriod($cutoffDay);
        $monthStartingDate = $cutoffPeriod['start']->format('Y-m-d');
        $monthEndingDate = $cutoffPeriod['end']->format('Y-m-d');
        $currentMonthDays = $cutoffPeriod['start']->diffInDays($cutoffPeriod['end']) + 1;
        $currentDate = Carbon::now();
            $attendanceCols = $this->getAttendanceSelectColumns($this->resort->resort_id, $monthStartingDate, $monthEndingDate);
            $employeesQuery = Employee::select(array_merge([
                'employees.id as employee_id',
                't1.id as Parentid',
                't1.first_name',
                't1.last_name',
                't1.profile_picture',
                't2.position_title',
                'employees.*',
            ], $attendanceCols))
            ->join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
            ->join('resort_positions as t2', 't2.id', '=', 'employees.Position_id')
            ->where('t1.resort_id', $this->resort->resort_id)
            ->where('employees.status', 'Active');

            $employeeRankPosition = Common::getEmployeeRankPosition($this->resort->getEmployee);
            // getEmployeeRankPosition()'s 'position' key is only ever set to
            // HR/Finance/GM (matched by department name); HOD/MGR/EXCOM live
            // in the 'rank' key instead (mapped from raw rank via
            // config('settings.eligibilty')). Checking 'position' alone for
            // EXCOM/HOD/MGR was always false, so this fell through to the
            // subordinate-scoped else branch for every real HOD — which
            // scopes by the reporting_to chain, not current department, so
            // an employee who transferred department but still formally
            // reports to their old HOD (reporting_to unchanged) kept
            // showing up in the old department's attendance list.
            //
            // 'EXCOM'/'HOD' out of getEmployeeRankPosition()'s 'rank' key is
            // a department-agnostic seniority label (rank 1/2 regardless of
            // WHICH department) — treating it as automatic full access let
            // ANY department's EXCOM/HOD (Engineering, F&B, ...) see every
            // other department's employees too. Only HR/GM get full access
            // unconditionally; EXCOM/HOD only do when they actually head
            // the HR department.
            $isHRDeptForAccess = Common::isHRDepartment($this->resort->GetEmployee->Dept_id ?? null);
            $canViewAll = in_array($employeeRankPosition['position'], ['HR', 'GM'])
                || in_array($employeeRankPosition['rank'], ['HR', 'GM'])
                || ($isHRDeptForAccess && (in_array($employeeRankPosition['rank'], ['EXCOM', 'HOD']) || in_array($employeeRankPosition['position'], ['EXCOM', 'HOD'])));

            if (!$canViewAll) {
                $Dept_id = $this->resort->GetEmployee->Dept_id ?? '';
                if (in_array($employeeRankPosition['rank'], ['HOD', 'MGR', 'EXCOM']) || in_array($employeeRankPosition['position'], ['HOD', 'MGR', 'EXCOM'])) {
                    // HOD/MGR see their own department
                    $employeesQuery->where('employees.Dept_id', $Dept_id);
                } else {
                    // Others see only subordinates
                    $employeesQuery->whereIn('employees.id', $this->underEmp_id);
                }
            }
           $employees = $employeesQuery->paginate(10);
                $today = Carbon::today()->format('Y-m-d');
                $employees->getCollection()->transform(function ($employee) use ($currentMonthDays, $monthStartingDate, $monthEndingDate, $today) {
                $employee->name = ucfirst($employee->first_name . ' ' . $employee->last_name);
                $employee->profile_picture = Common::getResortUserPicture($employee->Parentid);
                $employee->Position = ucfirst($employee->position_title);
                $employee->TotalWorkingDays = $currentMonthDays;
                $employee->Leave = isset($employee->LeaveCount) ? $employee->LeaveCount : 0 ;
                $employee->Present = $employee->PresentCount;
                $employee->Dayoff = $employee->DayOffCount;
                $elapsedDays = Carbon::parse($monthStartingDate)->diffInDays(Carbon::parse(min($today, $monthEndingDate))) + 1;
                // Was elapsedDays - Present - DayOff - Leave (a residual/
                // subtraction), which silently folded every un-recorded
                // day-off and any pre-joining gap into "Absent" since
                // neither DayOff nor Absent rows are ever actually written
                // to parent_attendaces. AbsentCount is now a real count from
                // duty_roster_entries (see getDetailSelectColumns/
                // getAttendanceSelectColumns) — scheduled work days with no
                // matching Present attendance and no approved leave.
                $employee->Absent = $employee->AbsentCount ?? 0;
                $employee->CompletedWorkingDays = $employee->PresentCount;
                $employee->TotalDayoff = Common::getWeekCountInMonth($monthStartingDate, $monthEndingDate);
                $employee->CompletedDayoff = $employee->DayOffCount;
                return $employee;
            });




        $page_title = "Employees";
        $showDepartmentFilter = $canViewAll;

        // Pre-load positions for the position filter
        if (!$canViewAll) {
            // HOD/MGR/Others: load positions for their own department
            $userDeptId = $this->resort->GetEmployee->Dept_id ?? '';
            $ResortPositions = ResortPosition::where('resort_id', $this->resort->resort_id)
                ->where('dept_id', $userDeptId)->get();
        } else {
            // HR/EXCOM/GM: load all positions initially
            $ResortPositions = ResortPosition::where('resort_id', $this->resort->resort_id)->get();
        }

        return  view('resorts.timeandattendance.employee.index',compact('page_title','ResortDepartment','employees','showDepartmentFilter','ResortPositions'));
    }

    public function SearchEmployeegird(Request $request)
    {
        $search = $request->search;
        $department = $request->department;
        $position = $request->position;
        $Rank =  $this->resort->GetEmployee->rank;
        $Dept_id = $this->resort->GetEmployee->Dept_id;
        $currentDate = Carbon::now();
        $cutoffDay = PayrollConfig::where('resort_id', $this->resort->resort_id)->value('cutoff_day') ?? 1;
        $cutoffPeriod = Common::getCurrentCutoffPeriod($cutoffDay);
        $monthStartingDate = $cutoffPeriod['start']->format('Y-m-d');
        $monthEndingDate = $cutoffPeriod['end']->format('Y-m-d');
        $currentMonthDays = $cutoffPeriod['start']->diffInDays($cutoffPeriod['end']) + 1;
        $attendanceCols = $this->getAttendanceSelectColumns($this->resort->resort_id, $monthStartingDate, $monthEndingDate);
        $employees = Employee::select(array_merge([
            'employees.id as employee_id',
            't1.id as Parentid',
            't1.first_name',
            't1.last_name',
            't1.profile_picture',
            't2.position_title',
            'employees.*',
        ], $attendanceCols))
        ->join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
        ->join('resort_positions as t2', 't2.id', '=', 'employees.Position_id')
        ->where('t1.resort_id', $this->resort->resort_id)
        ->where('employees.status', 'Active');

        $employeeRankPosition = Common::getEmployeeRankPosition($this->resort->getEmployee);
        // See the identical fix/comment in index() above — bare
        // EXCOM/HOD rank labels are department-agnostic, so only HR/GM get
        // unconditional full access; EXCOM/HOD only when they head HR.
        $isHRDeptForAccess = Common::isHRDepartment($this->resort->GetEmployee->Dept_id ?? null);
        $canViewAll = in_array($employeeRankPosition['position'], ['HR', 'GM'])
            || in_array($employeeRankPosition['rank'], ['HR', 'GM'])
            || ($isHRDeptForAccess && (in_array($employeeRankPosition['rank'], ['EXCOM', 'HOD']) || in_array($employeeRankPosition['position'], ['EXCOM', 'HOD'])));

        if (!$canViewAll) {
            // Was missing 'EXCOM' here (present in the identical check in
            // index() and EmployeeList() right above) — an EXCOM viewing
            // this grid fell through to subordinate-chain-only scoping
            // instead of their whole department, which both excludes
            // themselves (getSubordinates() never includes the caller) and
            // drops any department member whose reporting_to happens to
            // point outside the department even though Dept_id doesn't.
            // Exactly why "Total Employees: 6" on the dashboard (a plain
            // Dept_id count) didn't match this grid showing only 4.
            if (in_array($employeeRankPosition['rank'], ['HOD', 'MGR', 'EXCOM']) || in_array($employeeRankPosition['position'], ['HOD', 'MGR', 'EXCOM'])) {
                $employees->where('employees.Dept_id', $Dept_id);
            } else {
                $employees->whereIn('employees.id', $this->underEmp_id);
            }
        }
        if ($search) {
            $employees->where(function ($query) use ($search) {
                $query->where('t1.first_name', 'LIKE', "%{$search}%")
                      ->orWhere('t1.last_name', 'LIKE', "%{$search}%")
                      ->orWhere('t2.position_title', 'LIKE', "%{$search}%")
                      ->orWhere('employees.Emp_id', 'LIKE', "%{$search}%");
            });
        }

        if ($department) {
            $employees->where('employees.Dept_id', $department);
        }

        if ($position) {
            $employees->where('employees.Position_id', $position);
        }

        // Paginate results
        $employees = $employees->paginate(10); // Adjust pagination per your needs

        // Apply the transform to the paginated results
        $employees->getCollection()->transform(function ($employee) use ($currentMonthDays, $monthStartingDate, $monthEndingDate) {
            // Add computed fields
            $employee->name = ucfirst($employee->first_name . ' ' . $employee->last_name);
            $employee->profile_picture = Common::getResortUserPicture($employee->Parentid);
            $employee->Position = ucfirst($employee->position_title);
            $employee->TotalWorkingDays = $currentMonthDays;

            $employee->Leave = isset($employee->LeaveCount) ? $employee->LeaveCount : 0 ;
            $employee->Present = $employee->PresentCount;
            $employee->Dayoff = $employee->DayOffCount;
            // Same fix as index()/EmployeeDetails()/etc — this call site was
            // missed when AbsentCount (real, duty_roster_entries-derived)
            // replaced the elapsedDays residual, so the grid view kept
            // showing a much larger, made-up Absent number than every other
            // page (detail page, list view) for the same employee.
            $employee->Absent = $employee->AbsentCount ?? 0;
            $employee->CompletedWorkingDays = $employee->PresentCount;
            $employee->TotalDayoff = Common::getWeekCountInMonth($monthStartingDate, $monthEndingDate);
            $employee->CompletedDayoff = $employee->DayOffCount;
            return $employee;
        });

        if($request->ajax())
        {
            // Branching on "was a page param sent" (rather than ajax-ness)
            // meant clicking a grid pagination link — a real anchor tag
            // pointing at ?page=2&view=grid, no AJAX involved — did a full
            // browser navigation into the ELSE branch below, whose
            // subsequent document-ready JS immediately re-fetched the grid
            // via EmployeeGrid() with no page param, snapping straight back
            // to page 1. AJAX calls (including paginated ones, once the
            // frontend intercepts pagination clicks) now always get JSON.
            $view =  view('resorts.renderfiles.timeandattendanceEmployeeGrid', compact('employees'))->render();

            return response()->json(['success'=>true,'view' => $view]);
        }
        else
        {
            $p_details = explode("?" , $request->get('page', ''));
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
            $department = $request->department;
            $position = $request->position;
            $Rank =  $this->resort->GetEmployee->rank;
            $Dept_id = $this->resort->GetEmployee->Dept_id;
            $currentDate = Carbon::now();
            $cutoffDay = PayrollConfig::where('resort_id', $this->resort->resort_id)->value('cutoff_day') ?? 1;
            $cutoffPeriod = Common::getCurrentCutoffPeriod($cutoffDay);
            $monthStartingDate = $cutoffPeriod['start']->format('Y-m-d');
            $monthEndingDate = $cutoffPeriod['end']->format('Y-m-d');
            $currentMonthDays = $cutoffPeriod['start']->diffInDays($cutoffPeriod['end']) + 1;

            $attendanceCols = $this->getAttendanceSelectColumns($this->resort->resort_id, $monthStartingDate, $monthEndingDate);
            $employees = Employee::select(array_merge([
                'employees.id as employee_id',
                't1.id as Parentid',
                't1.first_name',
                't1.last_name',
                't1.profile_picture',
                't2.position_title',
                't2.code',
                'employees.*',
            ], $attendanceCols))
            ->join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
            ->join('resort_positions as t2', 't2.id', '=', 'employees.Position_id')
            ->where('t1.resort_id', $this->resort->resort_id)
            ->where('employees.status', 'Active');

            // Raw rank=3 assumed HR/full-access and everyone else got
            // subordinate-chain-only scoping, with no department-scoped
            // branch for a real HOD/MGR at all. That's why a HOD's list
            // view showed employees who transferred to a different
            // department but still formally report to them
            // (reporting_to unchanged) — the underEmp_id chain doesn't
            // track current department, only who reports to whom.
            $employeeRankPosition = Common::getEmployeeRankPosition($this->resort->getEmployee);
            // See the identical fix/comment in index() above — bare
            // EXCOM/HOD rank labels are department-agnostic, so only HR/GM
            // get unconditional full access; EXCOM/HOD only when they head HR.
            $isHRDeptForAccess = Common::isHRDepartment($this->resort->GetEmployee->Dept_id ?? null);
            $canViewAllList = in_array($employeeRankPosition['position'], ['HR', 'GM'])
                || in_array($employeeRankPosition['rank'], ['HR', 'GM'])
                || ($isHRDeptForAccess && (in_array($employeeRankPosition['rank'], ['EXCOM', 'HOD']) || in_array($employeeRankPosition['position'], ['EXCOM', 'HOD'])));

            if (!$canViewAllList) {
                if (in_array($employeeRankPosition['rank'], ['HOD', 'MGR', 'EXCOM']) || in_array($employeeRankPosition['position'], ['HOD', 'MGR', 'EXCOM'])) {
                    $employees->where('employees.Dept_id', $Dept_id);
                } else {
                    $employees->whereIn('employees.id', $this->underEmp_id);
                }
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

            if ($department) {
                $employees->where('employees.Dept_id', $department);
            }

            if ($position) {
                $employees->where('employees.Position_id', $position);
            }

            $today = Carbon::today()->format('Y-m-d');
            $employees = $employees->get()->map(function ($employee) use ($currentMonthDays, $monthStartingDate, $monthEndingDate, $today)
            {
                $employee->name = ucfirst($employee->first_name . ' ' . $employee->last_name);
                $employee->profile_picture = Common::getResortUserPicture($employee->Parentid);
                $employee->Position = ucfirst($employee->position_title);
                $employee->TotalWorkingDays = $currentMonthDays;

                $employee->Leave = isset($employee->LeaveCount) ? $employee->LeaveCount : 0 ;
                $employee->Present = $employee->PresentCount;
                $employee->Dayoff = $employee->DayOffCount;
                $elapsedDays = Carbon::parse($monthStartingDate)->diffInDays(Carbon::parse(min($today, $monthEndingDate))) + 1;
                // Was elapsedDays - Present - DayOff - Leave (a residual/
                // subtraction), which silently folded every un-recorded
                // day-off and any pre-joining gap into "Absent" since
                // neither DayOff nor Absent rows are ever actually written
                // to parent_attendaces. AbsentCount is now a real count from
                // duty_roster_entries (see getDetailSelectColumns/
                // getAttendanceSelectColumns) — scheduled work days with no
                // matching Present attendance and no approved leave.
                $employee->Absent = $employee->AbsentCount ?? 0;
                $employee->CompletedWorkingDays = $employee->PresentCount;
                $employee->TotalDayoff = Common::getWeekCountInMonth($monthStartingDate, $monthEndingDate);
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
                return '<a target="_blank" href="'.$route.'" class="btn taa-btn-secondary btn-sm '.$edit_class.'"  data-id="' . $row->id . '">View Details</a>';
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
        $resortId = $this->resort->resort_id;
        $cutoffDay = PayrollConfig::where('resort_id', $resortId)->value('cutoff_day') ?? 1;
        $cutoffPeriod = Common::getCurrentCutoffPeriod($cutoffDay);
        $monthStartingDate = $cutoffPeriod['start']->format('Y-m-d');
        $monthEndingDate = $cutoffPeriod['end']->format('Y-m-d');
        $currentMonthDays = $cutoffPeriod['start']->diffInDays($cutoffPeriod['end']) + 1;
        $currentDate = Carbon::now();
        $detailCols = $this->getDetailSelectColumns($resortId, $monthStartingDate, $monthEndingDate);
        $employee = Employee::join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
            ->join('resort_positions as t2', 't2.id', '=', 'employees.Position_id')
            ->leftjoin('duty_rosters as t3', 't3.Emp_id', '=', 'employees.id')
            ->leftjoin('shift_settings as ss', 'ss.id', '=', 't3.Shift_id')
            ->where('employees.id', $id)
            ->select(array_merge([
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
                'employees.benefit_grid_level',
                't2.position_title',
                't2.code as PositionCode',
                'employees.Dept_id',
            ], $detailCols))
            ->first();
            $department  = ResortDepartment::where('id', $employee->Dept_id)->value('name');

            if ($employee)
            {
                $employee->name = ucfirst($employee->first_name . ' ' . $employee->last_name);
                $employee->profile_picture = Common::getResortUserPicture($employee->Parentid);
                $employee->Position = ucfirst($employee->position_title);
                $employee->TotalWorkingDays = $currentMonthDays;
                $employee->Leave = $employee->LeaveCount ?? 0;
                $employee->Present = $employee->PresentCount;
                $employee->Dayoff = $employee->DayOffCount;
                $today = Carbon::today()->format('Y-m-d');
                $elapsedDays = Carbon::parse($monthStartingDate)->diffInDays(Carbon::parse(min($today, $monthEndingDate))) + 1;
                // Was elapsedDays - Present - DayOff - Leave (a residual/
                // subtraction), which silently folded every un-recorded
                // day-off and any pre-joining gap into "Absent" since
                // neither DayOff nor Absent rows are ever actually written
                // to parent_attendaces. AbsentCount is now a real count from
                // duty_roster_entries (see getDetailSelectColumns/
                // getAttendanceSelectColumns) — scheduled work days with no
                // matching Present attendance and no approved leave.
                $employee->Absent = $employee->AbsentCount ?? 0;
                $employee->CompletedWorkingDays = $employee->PresentCount;
                $employee->TotalHoursWorked = $employee->TotalHoursWorked ?? '00:00';
                $employee->TotalOverTime = $employee->TotalOverTime ?? '00:00';
                $employee->TotalDayoff = Common::getWeekCountInMonth($monthStartingDate, $monthEndingDate);
                $employee->CompletedDayoff = $employee->DayOffCount;
                $employee->department = $department;
                if (isset($employee->PresentCount) && $employee->PresentCount > 0)
                {
                    $employee->onTimePercentage = number_format($employee->OnTimeCount / $employee->PresentCount * 100);
                    $employee->LatePercentage = number_format($employee->LateCount / $employee->PresentCount * 100);
                }
                else
                {
                    $employee->onTimePercentage = 0;
                    $employee->LatePercentage = 0;
                }
            }
            $religion = $employee->religion;

            if($religion == "1"){
                $religion = "muslim";
            }


            $rank = $employee->rank;
            $emp_grade = Common::resolveEmpGrade($this->resort->resort_id, $rank, $employee->benefit_grid_level);

            // Get the viewed employee's gender (from their resort_admin record)
            $empGender = \App\Models\ResortAdmin::where('id', $employee->Parentid)->value('gender') ?? '';

            $benefit_grid = ResortBenifitGrid::where('emp_grade', $emp_grade)
                    ->where('resort_id', $this->resort->resort_id)
                    ->first();


            $leave_categories = ResortBenifitGridChild::select(
                DB::raw('MAX(resort_benefit_grid_child.id) as id'),
                'resort_benefit_grid_child.leave_cat_id',
                'resort_benefit_grid_child.rank',
                DB::raw('MAX(resort_benefit_grid_child.allocated_days) as allocated_days'),
                'resort_benefit_grid_child.eligible_emp_type',
                            'lc.leave_type',
                            'lc.color',
                            'lc.leave_category',
                            'lc.combine_with_other',
                'lc.carry_forward',
                'lc.carry_max',
                'lc.id as leave_cat_id'
                        )
                        ->join('leave_categories as lc', 'lc.id', '=', 'resort_benefit_grid_child.leave_cat_id')
                        ->where('resort_benefit_grid_child.rank', $benefit_grid->emp_grade)
                        ->where('lc.resort_id', $this->resort->resort_id)
                        ->whereRaw('FIND_IN_SET(?, lc.eligibility)', [$rank])
                        ->where('resort_benefit_grid_child.allocated_days', '>', 0)
                        ->where(function ($query) use ($religion, $empGender) {
                                $query->where('resort_benefit_grid_child.eligible_emp_type', $empGender)
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
            $leave_categories = $this->addLeaveAvailableWithCarryForward($leave_categories, $id);
            $realAttendance = ParentAttendace::join('shift_settings as ss', 'ss.id', '=', 'parent_attendaces.Shift_id')
                ->join('employees as t1', 't1.id', '=', 'parent_attendaces.Emp_id')
                ->leftjoin('child_attendaces as t2', 't2.Parent_attd_id', '=', 'parent_attendaces.id')
                ->whereIn('parent_attendaces.Status', ['On-Time','Present','Late','DayOff','Absent','ShortLeave','HalfDayLeave'])
                ->where('t1.id', $id)
                ->where('parent_attendaces.resort_id', $resortId)
                ->whereBetween('parent_attendaces.date', [$monthStartingDate, $monthEndingDate])
                ->groupBy('parent_attendaces.id')
                ->get([
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

            // No code path ever writes a parent_attendaces row with
            // Status='Absent' (every real check-in hardcodes 'Present'), so
            // the history list above only ever contains punched days.
            // Synthesize an Absent entry for every scheduled work day in
            // range with no matching punch and no approved leave — the same
            // rule AbsentCount uses (see getDetailSelectColumns) — so this
            // list actually reflects the Absent count shown on this page
            // instead of silently skipping those days.
            $absentRows = DB::select("
                SELECT dre.date, ss.ShiftName, ss.StartTime
                FROM duty_roster_entries dre
                JOIN shift_settings ss ON ss.id = dre.Shift_id
                WHERE dre.Emp_id = ?
                AND dre.resort_id = ?
                AND dre.Shift_id IS NOT NULL
                AND (dre.Status IS NULL OR dre.Status != 'DayOff')
                AND dre.date BETWEEN GREATEST(?, IFNULL((SELECT joining_date FROM employees WHERE id = ?), ?)) AND LEAST(?, CURDATE())
                AND NOT EXISTS (
                    SELECT 1 FROM parent_attendaces pa2
                    WHERE pa2.Emp_id = ? AND pa2.resort_id = ? AND pa2.date = dre.date
                    AND pa2.Status IN ('Present','HalfDay','On-Time','Late','ShortLeave','HalfDayLeave')
                    AND pa2.CheckingTime IS NOT NULL AND TRIM(IFNULL(pa2.CheckingTime,'')) NOT IN ('','00:00','00:00:00')
                )
                AND NOT EXISTS (
                    SELECT 1 FROM employees_leaves el2
                    WHERE el2.emp_id = ? AND el2.resort_id = ? AND el2.status = 'Approved'
                    AND dre.date BETWEEN el2.from_date AND el2.to_date
                )
            ", [$id, $resortId, $monthStartingDate, $id, $monthStartingDate, $monthEndingDate, $id, $resortId, $id, $resortId]);

            $absentItems = collect($absentRows)->map(function ($row) {
                return (object) [
                    'date' => $row->date,
                    'ShiftName' => $row->ShiftName,
                    'StartTime' => $row->StartTime,
                    'CheckingTime' => null,
                    'CheckingOutTime' => null,
                    'OverTime' => null,
                    'DayWiseTotalHours' => null,
                    'note' => null,
                    'InTime_Location' => null,
                    'OutTime_Location' => null,
                    'Child_id' => null,
                    'ParentAttd_id' => null,
                    'Status' => 'Absent',
                ];
            });

            $mergedAttendance = $realAttendance->concat($absentItems)->sortBy('date')->values();
            $historyPage = (int) request('page', 1);
            $historyPerPage = 10;
            $AttendanceHistroy = new \Illuminate\Pagination\LengthAwarePaginator(
                $mergedAttendance->forPage($historyPage, $historyPerPage)->values(),
                $mergedAttendance->count(),
                $historyPerPage,
                $historyPage,
                ['path' => request()->url(), 'query' => request()->query()]
            );

            // Transform the data after pagination
            $AttendanceHistroy->setCollection(
                $AttendanceHistroy->getCollection()->map(function($h) use($currentMonthDays) {
                    $h->date = Carbon::parse($h->date)->format('d M Y');;
                    $h->shift = ucfirst($h->ShiftName);
                    $h->DayWiseTotalHours = $h->DayWiseTotalHours ?? '0:00';
                    $h->OverTime = $h->OverTime ?? '0:00';
                    


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
        // The daterangepicker's default range must match the payroll
        // cutoff period computed above — it was hardcoded to calendar
        // month-to-date in the view instead, and since the JS always sends
        // whatever's in that field as start_date/end_date on every request
        // (initial load, filter, print, export), it permanently shadowed
        // AttandanceHisotry()'s own correct cutoff-based fallback.
        $cutoffRangeStart = $cutoffPeriod['start']->format('d/m/Y');
        $cutoffRangeEnd = min($cutoffPeriod['end'], $currentDate)->format('d/m/Y');
        return  view('resorts.timeandattendance.employee.Employeesdetails',compact('AttendanceHistroy','leave_categories','page_title','employee','TotalSum','cutoffRangeStart','cutoffRangeEnd'));
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

    /**
     * Add available_days (including carry forward) to each leave category item for time-and-attendance employee details.
     */
    private function addLeaveAvailableWithCarryForward($leave_categories, $emp_id)
    {
        $lastYearStart = Carbon::now()->subYear()->startOfYear()->format('Y-m-d');
        $lastYearEnd = Carbon::now()->subYear()->endOfYear()->format('Y-m-d');
        return $leave_categories->map(function ($i) use ($emp_id, $lastYearStart, $lastYearEnd) {
            $allocated = (int) ($i->allocated_days ?? 0);
            $usedThisYear = (int) ($i->ThisYearOfused_days ?? 0);
            $available = max(0, $allocated - $usedThisYear);
            $carryForwardEnabled = !empty($i->carry_forward) && $i->carry_forward != '0';
            if ($carryForwardEnabled) {
                $lastYearUsed = (int) DB::table('employees_leaves')
                    ->where('emp_id', $emp_id)
                    ->where('leave_category_id', $i->leave_cat_id)
                    ->where('status', 'Approved')
                    ->where(function ($q) use ($lastYearStart, $lastYearEnd) {
                        $q->whereBetween('from_date', [$lastYearStart, $lastYearEnd])
                            ->orWhereBetween('to_date', [$lastYearStart, $lastYearEnd]);
                    })
                    ->sum('total_days');
                $unused = max($allocated - $lastYearUsed, 0);
                $carryMax = isset($i->carry_max) && $i->carry_max !== null && $i->carry_max !== '' ? (int) $i->carry_max : null;
                $carryForward = $carryMax !== null ? min($unused, $carryMax) : $unused;
                $available += $carryForward;
            }
            $i->available_days = max(0, $available);
            return $i;
        });
    }

    public function EmpDetailsPrint(Request $request)
    {
        $id = $request->emp_id;
        if (empty($id)) {
            return redirect()->route('resort.timeandattendance.employee')->with('error', 'Please open the print page from Employee Details using the Download button.');
        }

        $dates = isset($request->hiddenInput) ? explode("-", $request->hiddenInput) : null;
        if ($dates && count($dates) >= 2) {
            $monthStartingDate = Carbon::createFromFormat('d/m/Y', trim($dates[0]))->format('Y-m-d');
            $monthEndingDate = Carbon::createFromFormat('d/m/Y', trim($dates[1]))->format('Y-m-d');
        } elseif ($request->start_date && $request->end_date) {
            $monthStartingDate = Carbon::parse($request->start_date)->format('Y-m-d');
            $monthEndingDate = Carbon::parse($request->end_date)->format('Y-m-d');
        } else {
            $cutoffDay = PayrollConfig::where('resort_id', $this->resort->resort_id)->value('cutoff_day') ?? 1;
            $cutoffPeriod = Common::getCurrentCutoffPeriod($cutoffDay);
            $monthStartingDate = $cutoffPeriod['start']->format('Y-m-d');
            $monthEndingDate = $cutoffPeriod['end']->format('Y-m-d');
        }

        $page_title = "Employee Details";
        $Rank =  $this->resort->GetEmployee->rank;
        $resortId = $this->resort->resort_id;
        $currentMonthDays = Carbon::parse($monthStartingDate)->diffInDays(Carbon::parse($monthEndingDate)) + 1;
        $currentDate = Carbon::now();
            $detailCols = $this->getDetailSelectColumns($resortId, $monthStartingDate, $monthEndingDate);
            $employee = Employee::join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                ->join('resort_positions as t2', 't2.id', '=', 'employees.Position_id')
                ->leftjoin('duty_rosters as t3', 't3.Emp_id', '=', 'employees.id')
                ->leftjoin('shift_settings as ss', 'ss.id', '=', 't3.Shift_id')
                ->where('employees.id', $id)
                ->select(array_merge([
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
                    'employees.benefit_grid_level',
                    't2.position_title',
                    't2.code as PositionCode',
                    'employees.Dept_id',
                ], $detailCols))
                ->first();
                $department  = ResortDepartment::where('id', $employee->Dept_id)->value('name');

                if ($employee)
                {
                    $employee->name = ucfirst($employee->first_name . ' ' . $employee->last_name);
                    $employee->profile_picture = Common::getResortUserPicture($employee->Parentid);
                    $employee->Position = ucfirst($employee->position_title);
                    $employee->TotalWorkingDays = $currentMonthDays;
                    $employee->Leave = $employee->LeaveCount ?? 0;
                    $employee->Present = $employee->PresentCount;
                    $employee->Dayoff = $employee->DayOffCount;
                    $today = Carbon::today()->format('Y-m-d');
                    $elapsedDays = Carbon::parse($monthStartingDate)->diffInDays(Carbon::parse(min($today, $monthEndingDate))) + 1;
                    // Was elapsedDays - Present - DayOff - Leave (a residual/
                // subtraction), which silently folded every un-recorded
                // day-off and any pre-joining gap into "Absent" since
                // neither DayOff nor Absent rows are ever actually written
                // to parent_attendaces. AbsentCount is now a real count from
                // duty_roster_entries (see getDetailSelectColumns/
                // getAttendanceSelectColumns) — scheduled work days with no
                // matching Present attendance and no approved leave.
                $employee->Absent = $employee->AbsentCount ?? 0;
                    $employee->CompletedWorkingDays = $employee->PresentCount;
                    $employee->TotalHoursWorked = $employee->TotalHoursWorked ?? '00:00';
                    $employee->TotalOverTime = $employee->TotalOverTime ?? '00:00';
                    $employee->TotalDayoff = Common::getWeekCountInMonth($monthStartingDate, $monthEndingDate);
                    $employee->CompletedDayoff = $employee->DayOffCount;
                    $employee->department = $department;
                    if (isset($employee->PresentCount) && $employee->PresentCount > 0)
                    {
                        $employee->onTimePercentage = number_format($employee->OnTimeCount / $employee->PresentCount * 100);
                        $employee->LatePercentage = number_format($employee->LateCount / $employee->PresentCount * 100);
                    }
                    else
                    {
                        $employee->onTimePercentage = 0;
                        $employee->LatePercentage = 0;
                    }
                }
                $religion = $employee->religion;

                if($religion == "1"){
                    $religion = "muslim";
                }


                $rank = $employee->rank;
                $emp_grade = Common::resolveEmpGrade($this->resort->resort_id, $rank, $employee->benefit_grid_level);

                $benefit_grid = ResortBenifitGrid::where('emp_grade', $emp_grade)
                        ->where('resort_id', $this->resort->resort_id)
                        ->first();

                // Get the viewed employee's gender (from their resort_admin record)
                $empGender = \App\Models\ResortAdmin::where('id', $employee->Parentid)->value('gender') ?? '';

                $TotalSum=0;
                $leave_categories = ResortBenifitGridChild::select(
                    'resort_benefit_grid_child.*',
                    'lc.leave_type',
                    'lc.color',
                    'lc.leave_category',
                    'lc.combine_with_other',
                    'lc.carry_forward',
                    'lc.carry_max',
                    'lc.id as leave_cat_id'
                )
                    ->join('leave_categories as lc', 'lc.id', '=', 'resort_benefit_grid_child.leave_cat_id')
                    ->where('resort_benefit_grid_child.rank', $benefit_grid->emp_grade)
                    ->where('lc.resort_id', $this->resort->resort_id)
                    ->whereRaw('FIND_IN_SET(?, lc.eligibility)', [$rank])
                        ->where('resort_benefit_grid_child.allocated_days', '>', 0)
                    ->where(function ($query) use ($religion, $empGender) {
                        $query->where('resort_benefit_grid_child.eligible_emp_type', $empGender)
                            ->orWhere('resort_benefit_grid_child.eligible_emp_type', 'all');
                        if ($religion == 'muslim') {
                            $query->orWhere('resort_benefit_grid_child.eligible_emp_type', $religion);
                        }
                    })
                    ->get()
                    ->map(function ($i) use ($id) {
                        $i->combine_with_other = isset($i->combine_with_other) ? $i->combine_with_other : 0;
                        $i->leave_category = isset($i->leave_category) && $i->leave_category != '' ? $i->leave_category : 0;
                        $i->ThisYearOfused_days = $this->getLeaveCount($id, $i->leave_cat_id);
                        return $i;
                    });
                $leave_categories = $this->addLeaveAvailableWithCarryForward($leave_categories, $id);
                $TotalSum = $leave_categories->sum('ThisYearOfused_days');


                $previousDay = Carbon::yesterday()->toDateString(); // Format: 'YYYY-MM-DD'
                $previousMonthStart = Carbon::now()->startOfMonth()->toDateString();
                $previousMonthEnd = Carbon::now()->yesterday()->toDateString();
                $AttendanceHistroy = ParentAttendace::join('shift_settings as ss', 'ss.id', '=', 'parent_attendaces.Shift_id')
                                        ->join('employees as t1', 't1.id', '=', 'parent_attendaces.Emp_id')
                                        ->leftjoin('child_attendaces as t2', 't2.Parent_attd_id', '=', 'parent_attendaces.id')
                                        ->whereIn('parent_attendaces.Status', ['On-Time', 'Present', 'Late', 'DayOff', 'Absent', 'ShortLeave', 'HalfDayLeave'])
                                        ->where('t1.id', $id)
                                        ->whereBetween('parent_attendaces.date', [$monthStartingDate, $monthEndingDate])  // Filter based on the selected month
                                        ->get(['t2.InTime_Location', 't2.OutTime_Location', 'parent_attendaces.note', 'parent_attendaces.date', 'ss.ShiftName', 'ss.StartTime', 'parent_attendaces.CheckingTime', 't2.id as Child_id', 'parent_attendaces.CheckingOutTime', 'parent_attendaces.OverTime', 'parent_attendaces.id as ParentAttd_id', 'parent_attendaces.Status', 'parent_attendaces.DayWiseTotalHours'])
                                        ->map(function($h) use($currentMonthDays) {

                                            $h->date = Carbon::parse($h->date)->format('d M Y');;
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
                                              $h->DayWiseTotalHours = $h->DayWiseTotalHours ?? '0:00';
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
        // dd($AttendanceHistroy);
        $page_title="Employee Details Print";
        return view ('resorts.timeandattendance.employee.employeedetailsprint',compact('TotalSum','leave_categories','page_title','employee','AttendanceHistroy','monthStartingDate','monthEndingDate'));
    }

    public function AttandanceHisotry(Request $request,$id)
    {
        if($request->ajax())
        {
            $cutoffDay = PayrollConfig::where('resort_id', $this->resort->resort_id)->value('cutoff_day') ?? 1;
            $cutoffPeriod = Common::getCurrentCutoffPeriod($cutoffDay);
            $previousMonthStart = $cutoffPeriod['start']->format('Y-m-d');
            $previousMonthEnd = $cutoffPeriod['end']->format('Y-m-d');
            // Use filter dates when provided (e.g. from employee details date range)
            if ($request->filled('start_date') && $request->filled('end_date')) {
                try {
                    $previousMonthStart = Carbon::createFromFormat('d/m/Y', trim($request->start_date))->format('Y-m-d');
                    $previousMonthEnd = Carbon::createFromFormat('d/m/Y', trim($request->end_date))->format('Y-m-d');
                } catch (\Exception $e) {
                    // keep defaults on parse error
                }
            }
            $currentMonthDays = Carbon::parse($previousMonthStart)->diffInDays(Carbon::parse($previousMonthEnd)) + 1;
            $AttendanceHistroy =  ParentAttendace::join('shift_settings as ss', 'ss.id', '=', 'parent_attendaces.Shift_id')
                ->join('employees as t1', 't1.id', '=', 'parent_attendaces.Emp_id')
                ->leftjoin('child_attendaces as t2', 't2.Parent_attd_id', '=', 'parent_attendaces.id')
                ->whereIn('parent_attendaces.Status',['On-Time','Present','Late','DayOff','Absent','ShortLeave','HalfDayLeave','FullDayLeave'])
                ->where('t1.id', $id)
                ->where('parent_attendaces.resort_id', $this->resort->resort_id)
                ->whereBetween('parent_attendaces.date', [$previousMonthStart, $previousMonthEnd])
                ->groupBy('parent_attendaces.id')
                ->orderBy('parent_attendaces.date', 'ASC')
                ->get([
                            't2.InTime_Location',
                            't2.OutTime_Location',
                            'parent_attendaces.note',
                            'parent_attendaces.date',
                            'ss.ShiftName',
                            'parent_attendaces.DayWiseTotalHours',
                            'parent_attendaces.OverTime',
                            'ss.StartTime',
                            'parent_attendaces.CheckingTime',
                            't2.id as Child_id',
                            'parent_attendaces.CheckingOutTime',
                            'parent_attendaces.id as ParentAttd_id',
                            'parent_attendaces.Status',
                            'parent_attendaces.created_at'
                        ])

                ->map(function($h)use($currentMonthDays, $id){
                    $h->raw_date = Carbon::parse($h->date)->format('Y-m-d');
                    $h->date = Carbon::parse($h->date)->format('d M Y');
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
                    $h->TotalHours = (!empty($h->DayWiseTotalHours) && $h->DayWiseTotalHours !== '0:00') ? $h->DayWiseTotalHours : '-';
                    $h->OverTime = (!empty($h->OverTime) && !in_array($h->OverTime, ['', '0', '00:00', '0:00', '0:0'])) ? $h->OverTime : '-';

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
                            // Check if this is a leave day (Unpaid Leave etc.)
                            $noteText = trim($h->note ?? '');
                            if ($noteText && stripos($noteText, 'leave') !== false) {
                                $h->Status = '<span class="badge badge-themeDanger">Absent</span> <small class="text-muted d-block">' . e($noteText) . '</small>';
                            } else {
                                $h->Status = '<span class="badge badge-themeDanger">Absent</span>';
                            }
                        }
                        elseif($h->Status == "DayOff")
                        {
                            $h->Status = '<span class="badge badge-themeWarning">Day Off</span>';
                        }
                        elseif($h->Status == "FullDayLeave")
                        {
                            // Look up the actual leave type for this date
                            $leaveInfo = EmployeeLeave::join('leave_categories as lc', 'lc.id', '=', 'employees_leaves.leave_category_id')
                                ->where('employees_leaves.Emp_id', $id)
                                ->where('employees_leaves.from_date', '<=', $h->raw_date)
                                ->where('employees_leaves.to_date', '>=', $h->raw_date)
                                ->first(['lc.leave_type', 'lc.color']);
                            if ($leaveInfo) {
                                $h->Status = '<span class="badge" style="background-color:' . ($leaveInfo->color ?? '#9C27B0') . '22; color:' . ($leaveInfo->color ?? '#9C27B0') . '; border:1px solid ' . ($leaveInfo->color ?? '#9C27B0') . ';">' . e($leaveInfo->leave_type) . '</span>';
                            } else {
                                $noteText = trim($h->note ?? '');
                                $h->Status = '<span class="badge badge-themePurple">Leave</span>' . ($noteText ? ' <small class="text-muted d-block">' . e($noteText) . '</small>' : '');
                            }
                        }
                        else
                        {
                            $h->Status = '<span class="badge badge-default">' . $h->Status . '</span>';
                        }
                    }
                    // Check if there's an approved leave overlapping this date regardless of attendance status
                    $leaveOnDate = EmployeeLeave::join('leave_categories as lc', 'lc.id', '=', 'employees_leaves.leave_category_id')
                        ->where('employees_leaves.Emp_id', $id)
                        ->where('employees_leaves.status', 'Approved')
                        ->where('employees_leaves.from_date', '<=', $h->raw_date)
                        ->where('employees_leaves.to_date', '>=', $h->raw_date)
                        ->first(['lc.leave_type', 'lc.color']);
                    if ($leaveOnDate) {
                        $h->Status .= ' <span class="badge" style="background-color:' . ($leaveOnDate->color ?? '#9C27B0') . '22; color:' . ($leaveOnDate->color ?? '#9C27B0') . '; border:1px solid ' . ($leaveOnDate->color ?? '#9C27B0') . '; font-size:10px;">' . e($leaveOnDate->leave_type) . '</span>';
                    }

                    return $h;
                });

                // Same fix as EmployeeDetails()/EmpDetailsFilters() above —
                // this is the actual DataTables source for the "Attendance
                // History" list view (#EmployeeDetails table), a third,
                // separate query path pulling straight from parent_attendaces
                // (which never gets a Status='Absent' row written). Synthesize
                // an Absent entry for every scheduled work day in range with
                // no matching punch and no approved leave, pre-built with the
                // same badge HTML/shape the real rows end up with after their
                // own transform above, then merge before handing off to
                // datatables() so its own search/sort/paginate sees them too.
                $absentRows = DB::select("
                    SELECT dre.date, ss.ShiftName
                    FROM duty_roster_entries dre
                    JOIN shift_settings ss ON ss.id = dre.Shift_id
                    WHERE dre.Emp_id = ?
                    AND dre.resort_id = ?
                    AND dre.Shift_id IS NOT NULL
                    AND (dre.Status IS NULL OR dre.Status != 'DayOff')
                    AND dre.date BETWEEN GREATEST(?, IFNULL((SELECT joining_date FROM employees WHERE id = ?), ?)) AND LEAST(?, CURDATE())
                    AND NOT EXISTS (
                        SELECT 1 FROM parent_attendaces pa2
                        WHERE pa2.Emp_id = ? AND pa2.resort_id = ? AND pa2.date = dre.date
                        AND pa2.Status IN ('Present','HalfDay','On-Time','Late','ShortLeave','HalfDayLeave')
                        AND pa2.CheckingTime IS NOT NULL AND TRIM(IFNULL(pa2.CheckingTime,'')) NOT IN ('','00:00','00:00:00')
                    )
                    AND NOT EXISTS (
                        SELECT 1 FROM employees_leaves el2
                        WHERE el2.emp_id = ? AND el2.resort_id = ? AND el2.status = 'Approved'
                        AND dre.date BETWEEN el2.from_date AND el2.to_date
                    )
                ", [$id, $this->resort->resort_id, $previousMonthStart, $id, $previousMonthStart, $previousMonthEnd, $id, $this->resort->resort_id, $id, $this->resort->resort_id]);

                $absentItems = collect($absentRows)->map(function ($row) {
                    return (object) [
                        'id' => null,
                        'InTime_Location' => null,
                        'note' => null,
                        'raw_date' => $row->date,
                        'date' => Carbon::parse($row->date)->format('d M Y'),
                        'shift' => ucfirst($row->ShiftName),
                        'CheckInTime' => null,
                        'CheckOutTime' => null,
                        'CheckInTimeOne' => null,
                        'CheckOutTimeOne' => null,
                        'TotalHours' => '-',
                        'OverTime' => '-',
                        'Status' => '<span class="badge badge-themeDanger">Absent</span>',
                        'Child_id' => null,
                        'ParentAttd_id' => null,
                    ];
                });

                $AttendanceHistroy = $AttendanceHistroy->concat($absentItems)->sortBy('raw_date')->values();

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
                    ->addColumn('TotalHours', function ($row) {
                        return isset($row->TotalHours) ? $row->TotalHours : 0; // Default to 0
                    })
                    ->addColumn('OverTime', function ($row) {
                        return isset($row->OverTime) ? $row->OverTime : 0; // Default to 0
                    })
                    ->addColumn('Status', function ($row) {
                        return isset($row->Status) ? $row->Status : 0; // Default to 0
                    })
                    ->addColumn('Action', function ($row) use ($edit_class) {
                        if (!$row->ParentAttd_id) {
                            // Synthesized Absent row — nothing was punched, no location/edit target.
                            return '<span class="text-muted small">No punch recorded</span>';
                        }
                        return '<a href="#" class="btn-tableIcon taa-btn-secondary LocationHistoryData" data-location="' . $row->InTime_Location . '" data-id="' . $row->id . '">
                            <i class="fa-regular fa-location-dot"></i>
                        </a>
                        <a href="#" class="btn-tableIcon taa-btn-secondary edit-row-btn '.$edit_class.'" data-note="' . $row->note . '" data-checkinTime="' . $row->CheckInTimeOne . '"
                            data-checkouttime="' . $row->CheckOutTimeOne . '" data-overtime="' . $row->OverTime . '" data-id="' . base64_encode($row->Child_id) . '"
                            data-ParentAttd_id="' . base64_encode($row->ParentAttd_id) . '" data-bs-toggle="modal">
                            <i class="fa-solid fa-pen"></i>
                        </a>';
                    })
                    ->addColumn('sort_date', function ($row) {
                        return $row->raw_date;
                    })
                    ->rawColumns(['Date', 'Shift', 'CheckinTime', 'CheckOutTime', 'TotalHours','OverTime', 'Status', 'Action'])
                    ->make(true);
        }
    }

    public function EmpDetailsFilters(Request $request)
    {
        $dates = isset($request->hiddenInput) ? explode("-", $request->hiddenInput) : null;
        // $monthStartingDate = isset($dates[0])
        //     ? Carbon::createFromFormat('d/m/Y', $dates[0])->format('Y-m-d') // Correct date format for the '03/01/2025' format
        //     : Carbon::now()->startOfMonth()->format('Y-m-d'); // Default to the start of the month
        // $monthEndingDate = isset($dates[1])
        //     ? Carbon::createFromFormat('d/m/Y', $dates[1])->format('Y-m-d') // Correct date format for the '03/01/2025' format
        //     : Carbon::now()->endOfMonth()->format('Y-m-d'); // Default to the end of the month
        if (isset($request->start_date) && isset($request->end_date)) {
            $monthStartingDate = Carbon::createFromFormat('d/m/Y', $request->start_date)->format('Y-m-d');
            $monthEndingDate = Carbon::createFromFormat('d/m/Y', $request->end_date)->format('Y-m-d');
        } else {
            $cutoffDay = PayrollConfig::where('resort_id', $this->resort->resort_id)->value('cutoff_day') ?? 1;
            $cutoffPeriod = Common::getCurrentCutoffPeriod($cutoffDay);
            $monthStartingDate = $cutoffPeriod['start']->format('Y-m-d');
            $monthEndingDate = $cutoffPeriod['end']->format('Y-m-d');
        }
        $id = base64_decode($request->emp_id);
        $page_title = "Employee Details";
        $Rank =  $this->resort->GetEmployee->rank;
        $resortId = $this->resort->resort_id;
        $currentMonthDays = Carbon::parse($monthStartingDate)->diffInDays(Carbon::parse($monthEndingDate)) + 1;

        $currentDate = Carbon::now();
        $detailCols = $this->getDetailSelectColumns($resortId, $monthStartingDate, $monthEndingDate);
        $employee = Employee::join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
            ->join('resort_positions as t2', 't2.id', '=', 'employees.Position_id')
            ->leftjoin('duty_rosters as t3', 't3.Emp_id', '=', 'employees.id')
            ->leftjoin('shift_settings as ss', 'ss.id', '=', 't3.Shift_id')
            ->where('employees.id', $id)
            ->select(array_merge([
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
                'employees.benefit_grid_level',
                't2.position_title',
                't2.code as PositionCode',
            ], $detailCols))
            ->first();

            if ($employee)
            {
                $employee->name = ucfirst($employee->first_name . ' ' . $employee->last_name);
                $employee->profile_picture = Common::getResortUserPicture($employee->Parentid);
                $employee->Position = ucfirst($employee->position_title);
                $employee->TotalWorkingDays = $currentMonthDays;
                $employee->Leave = $employee->LeaveCount ?? 0;
                $employee->Present = $employee->PresentCount;
                $employee->Dayoff = $employee->DayOffCount;
                $today = Carbon::today()->format('Y-m-d');
                $elapsedDays = Carbon::parse($monthStartingDate)->diffInDays(Carbon::parse(min($today, $monthEndingDate))) + 1;
                // Was elapsedDays - Present - DayOff - Leave (a residual/
                // subtraction), which silently folded every un-recorded
                // day-off and any pre-joining gap into "Absent" since
                // neither DayOff nor Absent rows are ever actually written
                // to parent_attendaces. AbsentCount is now a real count from
                // duty_roster_entries (see getDetailSelectColumns/
                // getAttendanceSelectColumns) — scheduled work days with no
                // matching Present attendance and no approved leave.
                $employee->Absent = $employee->AbsentCount ?? 0;
                $employee->CompletedWorkingDays = $employee->PresentCount;
                $employee->TotalHoursWorked = $employee->TotalHoursWorked ?? '00:00';
                $employee->TotalOverTime = $employee->TotalOverTime ?? '00:00';
                $employee->TotalDayoff = Common::getWeekCountInMonth($monthStartingDate, $monthEndingDate);
                $employee->CompletedDayoff = $employee->DayOffCount;
                if (isset($employee->PresentCount) && $employee->PresentCount > 0)
                {
                    $employee->onTimePercentage = number_format($employee->OnTimeCount / $employee->PresentCount * 100);
                    $employee->LatePercentage = number_format($employee->LateCount / $employee->PresentCount * 100);
                }
                else
                {
                    $employee->onTimePercentage = 0;
                    $employee->LatePercentage = 0;
                }
            }
            $religion = $employee->religion;

            if($religion == "1"){
                $religion = "muslim";
            }


            $rank = $employee->rank;
            $emp_grade = Common::resolveEmpGrade($this->resort->resort_id, $rank, $employee->benefit_grid_level);

            // Get the viewed employee's gender (from their resort_admin record)
            $empGender = \App\Models\ResortAdmin::where('id', $employee->Parentid)->value('gender') ?? '';

            $benefit_grid = ResortBenifitGrid::where('emp_grade', $emp_grade)
                    ->where('resort_id', $this->resort->resort_id)
                    ->first();


            $leave_categories = ResortBenifitGridChild::select(
                DB::raw('MAX(resort_benefit_grid_child.id) as id'),
                'resort_benefit_grid_child.leave_cat_id',
                'resort_benefit_grid_child.rank',
                DB::raw('MAX(resort_benefit_grid_child.allocated_days) as allocated_days'),
                'resort_benefit_grid_child.eligible_emp_type',
                            'lc.leave_type',
                            'lc.color',
                            'lc.leave_category',
                            'lc.combine_with_other',
                'lc.carry_forward',
                'lc.carry_max',
                'lc.id as leave_cat_id'
                        )
                        ->join('leave_categories as lc', 'lc.id', '=', 'resort_benefit_grid_child.leave_cat_id')
                        ->where('resort_benefit_grid_child.rank', $benefit_grid->emp_grade)
                        ->where('lc.resort_id', $this->resort->resort_id)
                        ->whereRaw('FIND_IN_SET(?, lc.eligibility)', [$rank])
                        ->where('resort_benefit_grid_child.allocated_days', '>', 0)
                        ->where(function ($query) use ($religion, $empGender) {
                                $query->where('resort_benefit_grid_child.eligible_emp_type', $empGender)
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
            $realAttendance = ParentAttendace::join('shift_settings as ss', 'ss.id', '=', 'parent_attendaces.Shift_id')
                ->join('employees as t1', 't1.id', '=', 'parent_attendaces.Emp_id')
                ->leftjoin('child_attendaces as t2', 't2.Parent_attd_id', '=', 'parent_attendaces.id')
                ->whereIn('parent_attendaces.Status', ['On-Time','Present','Late','DayOff','Absent','ShortLeave','HalfDayLeave'])
                ->where('t1.id', $id)
                ->where('parent_attendaces.resort_id', $resortId)
                ->whereBetween('parent_attendaces.date', [$monthStartingDate, $monthEndingDate])
                ->groupBy('parent_attendaces.id')
                ->get([
                    'parent_attendaces.id',
                    't2.InTime_Location',
                    't2.OutTime_Location',
                    'parent_attendaces.note',
                    'parent_attendaces.date',
                    'ss.ShiftName',
                    'ss.StartTime',
                    'parent_attendaces.CheckingTime',
                    't2.id as Chilmd_id',
                    'parent_attendaces.CheckingOutTime',
                    'parent_attendaces.OverTime',
                    'parent_attendaces.id as ParentAttd_id',
                    'parent_attendaces.Status',
                    'parent_attendaces.DayWiseTotalHours'
                ]);

            // Same fix as EmployeeDetails() above — no code path ever writes
            // a parent_attendaces row with Status='Absent', so this AJAX
            // date-filter table (a separate query path from the card-view
            // history) only ever showed punched days. Synthesize an Absent
            // entry for every scheduled work day in range with no matching
            // punch and no approved leave.
            $absentRows = DB::select("
                SELECT dre.date, ss.ShiftName, ss.StartTime
                FROM duty_roster_entries dre
                JOIN shift_settings ss ON ss.id = dre.Shift_id
                WHERE dre.Emp_id = ?
                AND dre.resort_id = ?
                AND dre.Shift_id IS NOT NULL
                AND (dre.Status IS NULL OR dre.Status != 'DayOff')
                AND dre.date BETWEEN GREATEST(?, IFNULL((SELECT joining_date FROM employees WHERE id = ?), ?)) AND LEAST(?, CURDATE())
                AND NOT EXISTS (
                    SELECT 1 FROM parent_attendaces pa2
                    WHERE pa2.Emp_id = ? AND pa2.resort_id = ? AND pa2.date = dre.date
                    AND pa2.Status IN ('Present','HalfDay','On-Time','Late','ShortLeave','HalfDayLeave')
                    AND pa2.CheckingTime IS NOT NULL AND TRIM(IFNULL(pa2.CheckingTime,'')) NOT IN ('','00:00','00:00:00')
                )
                AND NOT EXISTS (
                    SELECT 1 FROM employees_leaves el2
                    WHERE el2.emp_id = ? AND el2.resort_id = ? AND el2.status = 'Approved'
                    AND dre.date BETWEEN el2.from_date AND el2.to_date
                )
            ", [$id, $resortId, $monthStartingDate, $id, $monthStartingDate, $monthEndingDate, $id, $resortId, $id, $resortId]);

            $absentItems = collect($absentRows)->map(function ($row) {
                return (object) [
                    'id' => null,
                    'InTime_Location' => null,
                    'OutTime_Location' => null,
                    'note' => null,
                    'date' => $row->date,
                    'ShiftName' => $row->ShiftName,
                    'StartTime' => $row->StartTime,
                    'CheckingTime' => null,
                    'Chilmd_id' => null,
                    'Child_id' => null,
                    'CheckingOutTime' => null,
                    'OverTime' => null,
                    'ParentAttd_id' => null,
                    'Status' => 'Absent',
                    'DayWiseTotalHours' => null,
                ];
            });

            $mergedAttendance = $realAttendance->concat($absentItems)->sortBy('date')->values();
            $historyPage = (int) $request->input('page', 1);
            $historyPerPage = 10;
            $AttendanceHistroy = new \Illuminate\Pagination\LengthAwarePaginator(
                $mergedAttendance->forPage($historyPage, $historyPerPage)->values(),
                $mergedAttendance->count(),
                $historyPerPage,
                $historyPage,
                ['path' => request()->url(), 'query' => request()->query()]
            );

            // Transform the data after pagination
            $AttendanceHistroy->setCollection(
                $AttendanceHistroy->getCollection()->map(function($h) use($currentMonthDays) {
                    // $h->date = Carbon::parse($h->date)->format('d M Y');;
                                       $h->date = Carbon::parse($h->date)->format('d M Y');;

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
                    $edit_class = '';
            if(Common::checkRouteWisePermission('resort.timeandattendance.employee',config('settings.resort_permissions.view')) == false){
                $edit_class = 'd-none';
            }

                    $h->CheckInTimeOne = $h->CheckingTime;
                    $h->CheckOutTimeOne = $h->CheckingOutTime;
                    $h->totalhour = isset($h->DayWiseTotalHours) ? $h->DayWiseTotalHours : '-';
                    $h->OverTime = isset($h->OverTime) ? $h->OverTime : '-';
                    if (!$h->ParentAttd_id) {
                        // Synthesized Absent row — nothing was actually
                        // punched, so there's no location/edit target.
                        $h->Action = '<span class="text-muted small">No punch recorded</span>';
                    } else {
                        $h->Action = '<a href="#" class="btn-tableIcon taa-btn-secondary LocationHistoryData"
                                data-location="' . $h->InTime_Location . '"
                                data-id="' . $h->id . '">
                                    <i class="fa-regular fa-location-dot"></i>
                                </a>
                                <a href="#" class="btn-tableIcon taa-btn-secondary edit-row-btn ' . $edit_class . '"
                                data-note="' . $h->note . '"
                                data-checkinTime="' . $h->CheckInTime . '"
                                data-checkouttime="' . $h->CheckOutTimeOne . '"
                                data-overtime="' . $h->OverTime . '"
                                data-id="' . base64_encode($h->Child_id) . '"
                                data-ParentAttd_id="' . base64_encode($h->ParentAttd_id) . '"
                                data-bs-toggle="modal">
                                    <i class="fa-solid fa-pen"></i>
                                </a>';
                    }

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


public function attandanceHisotryExport(Request $request)
{
    $start = $request->start_date;
    $end   = $request->end_date;
    $id    = $request->id;

    if (empty($id) || empty($start) || empty($end)) {
        return redirect()->route('resort.timeandattendance.employee')
            ->with('error', 'Export requires employee and date range. Please use Export CSV from the employee details print page.');
    }

    // ===============================
    // 1️⃣ Single Merged Query
    // ===============================

    $AttendanceHistroy = ParentAttendace::from('parent_attendaces as pa')
        ->join('employees as e', 'e.id', '=', 'pa.Emp_id')
        ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
        ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
        ->leftJoin('resort_departments as rd', 'rd.id', '=', 'e.Dept_id')
        ->join('shift_settings as ss', 'ss.id', '=', 'pa.Shift_id')
        ->where('e.id', $id)
        ->whereBetween('pa.date', [$start, $end])
        ->select(
            'pa.date',
            'pa.CheckingTime',
            'pa.CheckingOutTime',
            'pa.DayWiseTotalHours',
            'pa.OverTime',
            'pa.Status',
            'ss.ShiftName',

            // Employee details
            'ra.first_name',
            'ra.last_name',
            'e.Emp_id as Emp_Code',
            'rp.position_title',
            'rd.name as department_name'
        )
        ->get();

    $filename = "Attendance_Report_" . date('M_Y') . ".csv";
    $headers = [
        "Content-type"        => "text/csv",
        "Content-Disposition" => "attachment; filename=$filename",
        "Pragma"              => "no-cache",
        "Cache-Control"       => "must-revalidate",
        "Expires"             => "0"
    ];

    if ($AttendanceHistroy->isEmpty()) {
        $callback = function() use ($start, $end) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['No attendance records found for the selected period.']);
            fputcsv($file, ['Start Date:', $start]);
            fputcsv($file, ['End Date:', $end]);
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }

    // ===============================
    // 2️⃣ CSV Stream (with data)
    // ===============================

    $callback = function() use ($AttendanceHistroy) {

        $file = fopen('php://output', 'w');

        $firstRow = $AttendanceHistroy->first();
        $employeeName = $firstRow->first_name . ' ' . $firstRow->last_name;

        // Employee Info Section
        fputcsv($file, ['Employee Name:', $employeeName]);
        fputcsv($file, ['Employee Code:', $firstRow->Emp_Code ?? '-']);
        fputcsv($file, ['Position:', $firstRow->position_title ?? '-']);
        fputcsv($file, ['Department:', $firstRow->department_name ?? '-']);
        fputcsv($file, []);

        // Table Header
        fputcsv($file, [
            'Date',
            'Shift',
            'Check In Time',
            'Check Out Time',
            'Total Hours',
            'Over Time',
            'Status'
        ]);

        foreach ($AttendanceHistroy as $row) {

            $date = Carbon::parse($row->date)->format('d M Y');

            $checkIn = $row->CheckingTime
                ? Carbon::parse($row->CheckingTime)->format('h:i A')
                : '-';

            $checkOut = $row->CheckingOutTime
                ? Carbon::parse($row->CheckingOutTime)->format('h:i A')
                : '-';

            fputcsv($file, [
                $date,
                ucfirst($row->ShiftName),
                $checkIn,
                $checkOut,
                $row->DayWiseTotalHours ?? '-',
                $row->OverTime ?? '-',
                $row->Status
            ]);
        }

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

}
