<?php

namespace App\Http\Controllers\Resorts\TimeAndAttendance;
use DB;
use DateTime;
use DatePeriod;
use DateInterval;
use Carbon\Carbon;
use App\Helpers\Common;
use App\Models\Employee;
use App\Models\DutyRoster;
use App\Models\ResortBenifitGrid;
use Illuminate\Http\Request;
use App\Models\EmployeeLeave;
use App\Models\LeaveCategory;
use App\Models\ShiftSettings;
use App\Models\ChildAttendace;
use App\Models\ParentAttendace;
use App\Models\DutyRosterEntry;
use App\Models\ResortPosition;
use App\Models\ResortDepartment;
use App\Models\ResortSection;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\Compliance;
use App\Models\ResortHoliday;
use App\Models\EmployeeOvertime;
use App\Models\PayrollConfig;
class DutyRosterController extends Controller
{
    protected $resort;

    protected $underEmp_id=[];

    public function __construct()
    {
        $this->resort = $resortId = auth()->guard('resort-admin')->user();
        $reporting_to = $this->resort->GetEmployee->id ?? null;

        $this->underEmp_id = Common::getSubordinates($reporting_to);
    }

    /**
     * Get public holidays for a resort (including all Fridays)
     * Returns array of dates in Y-m-d format
     */
    private function getPublicHolidays($resort_id, $startDate = null, $endDate = null)
    {
        $publicHolidays = [];

        // Get public holidays from database
        $holidays = ResortHoliday::where('resort_id', $resort_id)
            ->whereNotNull('PublicHolidaydate')
            ->get(['PublicHolidaydate']);

        foreach ($holidays as $holiday) {
            if ($holiday->PublicHolidaydate) {
                $date = Carbon::parse($holiday->PublicHolidaydate)->format('Y-m-d');
                $publicHolidays[] = $date;
            }
        }

        // Add all Fridays of the year
        $currentYear = $startDate ? Carbon::parse($startDate)->year : Carbon::now()->year;
        $start = $startDate ? Carbon::parse($startDate) : Carbon::create($currentYear, 1, 1);
        $end = $endDate ? Carbon::parse($endDate) : Carbon::create($currentYear, 12, 31);

        $currentDate = $start->copy();
        while ($currentDate->lte($end)) {
            if ($currentDate->isFriday()) {
                $fridayDate = $currentDate->format('Y-m-d');
                if (!in_array($fridayDate, $publicHolidays)) {
                    $publicHolidays[] = $fridayDate;
                }
            }
            $currentDate->addDay();
        }

        return $publicHolidays;
    }
    public function CreateDutyRoster()
    {
        $page_title = "Create Duty Roster";
        $Dept_id = $this->resort->GetEmployee->Dept_id ?? '';
        $Rank =  $this->resort->GetEmployee->rank ?? '';
        $employeeRank = Common::getEmployeeRank($this->resort->getEmployee);
        $employeeRankPosition = Common::getEmployeeRankPosition( $this->resort->getEmployee);

        if($this->resort->is_master_admin == 0){
            if($employeeRank['isHR'] != true)
            {
                $employees = Employee::join('resort_admins as t1',"t1.id","=","employees.Admin_Parent_id")
                                ->leftJoin('resort_positions as t2',"t2.id","=","employees.Position_id")
                                ->where("t1.resort_id",$this->resort->resort_id)
                                // ->whereIn('employees.id', $this->underEmp_id)
                                 ->where("employees.Dept_id",$Dept_id)
                                ->where("employees.status","Active")
                                ->get(['t1.first_name','t1.last_name','t1.profile_picture','t2.position_title','employees.*']);
            }else{
                $employees = Employee::join('resort_admins as t1',"t1.id","=","employees.Admin_Parent_id")
                                ->leftJoin('resort_positions as t2',"t2.id","=","employees.Position_id")
                                ->where("t1.resort_id",$this->resort->resort_id)
                                // ->where("employees.Dept_id",$Dept_id)
                                ->where("employees.status","Active")
                                ->get(['t1.first_name','t1.last_name','t1.profile_picture','t2.position_title','employees.*']);
            }
        }else{
            $employees = Employee::join('resort_admins as t1',"t1.id","=","employees.Admin_Parent_id")
                                ->leftJoin('resort_positions as t2',"t2.id","=","employees.Position_id")
                                ->where("t1.resort_id",$this->resort->resort_id)
                                ->where("employees.status","Active")
                                ->get(['t1.first_name','t1.last_name','t1.profile_picture','t2.position_title','employees.*']);
        }
        $ResortPosition = ResortPosition::where("dept_id", $Dept_id)
                                        ->where("resort_id",$this->resort->resort_id)->get();
        $resort_id   = $this->resort->resort_id;

        $startOfMonth = Carbon::now()->startOfMonth(); // Get the first day of the month
        $endOfMonth =Carbon::now()->endOfMonth(); // Get the last day of the month

        $WeekstartDate = Carbon::now()->startOfWeek(); //Week start Start date
        $WeekendDate = Carbon::now()->endOfWeek();

        $headers = [];
        $numberOfDays = 7;
        $days = [];
        for ($i = 0; $i < $numberOfDays; $i++)
        {
            $currentDate = $WeekstartDate->clone()->addDays($i);
            $headers[] = [
                'date' => $currentDate->format('d M'),
                'day' => $currentDate->format('D'),
                'full_date' => $currentDate
            ];
            $days[] =$currentDate->format('D');
        }


        // Same deterministic-latest-row fix as ViewDutyRoster() — see the
        // comment there for why groupBy('employees.id') can't be trusted
        // to carry the right duty_roster_id/geofence_zone_id.
        $latestRosterPerEmp = DB::table('duty_rosters')
                                ->select('Emp_id', DB::raw('MAX(id) as latest_id'))
                                ->where('resort_id', $this->resort->resort_id)
                                ->groupBy('Emp_id');

        $Rosterdata1 = Employee::join('resort_admins as t1',"t1.id","=","employees.Admin_Parent_id")
                                ->join('resort_positions as t2',"t2.id","=","employees.Position_id")
                                ->joinSub($latestRosterPerEmp, 'latest_dr', function($join) {
                                    $join->on('latest_dr.Emp_id', '=', 'employees.id');
                                })
                                ->join('duty_rosters as t3',"t3.id","=","latest_dr.latest_id")
                                ->select('t3.id as duty_roster_id', 't3.DayOfDate', 't3.geofence_zone_id', 't1.id as Parentid', 't1.first_name', 't1.last_name', 't1.profile_picture', 'employees.id as emp_id', 't2.position_title')
                                ->where('t1.resort_id', $this->resort->resort_id);

                                if($this->resort->is_master_admin == 0){
                                    if($employeeRankPosition['position'] != "HR")
                                    {
                                        if($employeeRankPosition['position'] != "EXCOM")
                                        {
                                            $Rosterdata1->whereIn('employees.id', $this->underEmp_id);
                                        }
                                    }
                                }

                                $Rosterdata=$Rosterdata1->paginate(10);
        $year = now()->year; // Current year
        $month = now()->month; // Current month
        $totalDays = Carbon::createFromDate($year, $month, 1)->daysInMonth; //

        $monthwiseheaders=[];
        for ($day = 1; $day <= $totalDays; $day++)
        {
            $date = Carbon::createFromDate($year, $month, $day); // Create a date for each day
            $dayName = $date->format('D'); // Get the day name (e.g., Mon, Tue)

            $monthwiseheaders[] = ["day"=>str_pad($day, 2, '0', STR_PAD_LEFT),"dayname" => $dayName,'date'=>$date->format('Y-m-d')];
        }
        $LeaveCategory = LeaveCategory::where("resort_id",$this->resort->resort_id)->get();
        $statusCount = [
            "Absent"=>0,
            "Present"=>0,
            "Late"=>0,
            "DayOff"=>0,
            "ShortLeave"=>0,
            "HalfDayLeave"=>0,
            "FullDayLeave"=>0,

        ];
        $ShiftSettings = ShiftSettings::where("resort_id", $this->resort->resort_id)->get(['id','ShiftName','TotalHours','StartTime','EndTime']);

        // Get public holidays (including Fridays) - use month range to include all Fridays in the month
        $publicHolidays = $this->getPublicHolidays($resort_id, $startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d'));

        // Get active geofence zones for this resort
        $geofenceZones = \App\Models\ResortGeofence::where('resort_id', $resort_id)->where('status', 'active')->orderBy('name')->get();

        return view('resorts.timeandattendance.dutyroster.CreateDutyRoster',compact('endOfMonth','startOfMonth','WeekstartDate','WeekendDate','days','page_title','headers','employees','ShiftSettings','resort_id','Rosterdata','ResortPosition','totalDays','monthwiseheaders','LeaveCategory','statusCount','publicHolidays','geofenceZones'));
    }

    public function DutyRosterandLeave(Request $request)
    {
        $id = $request->id;
        $Rank =  $this->resort->GetEmployee->rank;

        // try
        // {
            // resort_benifit_grid.emp_grade stores a resort_benefit_grade_levels
            // id (see ResortBenefitGradeLevelRank), not a raw rank number, so it
            // can no longer be joined directly against employees.rank — resolve
            // the employee's grade level via the rank-mapping table first, same
            // as every other Common::getEmpGrade() call site.
            $employees = Employee::join('resort_admins as t1',"t1.id","=","employees.Admin_Parent_id")
                                    ->join('resort_positions as t2',"t2.id","=","employees.Position_id")
                                    ->where("employees.id",$id)
                                    ->where('t1.status','Active')
                                    ->where('employees.status','Active')
                                    ->where('t1.resort_id',$this->resort->resort_id)
                                    ->first(['t1.id as Parentid','employees.rank','t1.first_name','t1.last_name','t1.profile_picture','employees.*','t2.position_title']);

            $overtime = null;
            if ($employees) {
                $gradeLevelId = Common::resolveEmpGrade($this->resort->resort_id, $employees->rank, $employees->benefit_grid_level);
                if ($gradeLevelId) {
                    $overtime = \App\Models\ResortBenifitGrid::where('resort_id', $this->resort->resort_id)
                        ->where('emp_grade', $gradeLevelId)
                        ->value('overtime');
                }
                $employees->overtime = $overtime;
            }

            $currentDay = Carbon::now()->format('Y-m-d');
            $currentMonthEnd = Carbon::now()->endOfMonth()->format('Y-m-d');

            $EmployeeLeave = Employee::join('resort_admins as t3', 't3.id', '=', 'employees.Admin_Parent_id')
                        ->join('employees_leaves as t2', 't2.emp_id', '=', 'employees.id')
                        ->join('leave_categories as t1', 't1.id', '=', 't2.leave_category_id')
                        ->where('t1.resort_id', $this->resort->resort_id)
                        ->where('t2.emp_id', $id)
                        // A leave still awaiting approval is just as relevant to a
                        // scheduler as an approved one — it was previously filtered
                        // to Approved only, so an employee's already-submitted but
                        // not-yet-approved leave request silently showed "No Leave
                        // Applied" here despite genuinely overlapping the roster
                        // period being planned.
                        ->whereIn('t2.status', ['Approved', 'Pending'])
                        ->where(function ($query) use ($currentDay,$currentMonthEnd) {
                            $query->where('t2.from_date', '>=', $currentDay) // Leave started on or before the current day
                                ->orWhere('t2.to_date', '>=', $currentDay); // Leave ends on or after the current day
                            // ->where('t2.to_date', '>=', $currentMonthEnd); // Leave ends on or after the current day
                        })
                        ->select('employees.*', 't2.from_date', 't2.to_date', 't2.reason', 't2.status as leave_status', 't1.leave_type')
                        ->get();
            $view =  view('resorts.renderfiles.dutyrosterandLeave',compact('employees','EmployeeLeave'))->render();

            $leaveDates = $EmployeeLeave->map(function ($l) {
                return ['from' => $l->from_date, 'to' => $l->to_date, 'status' => $l->leave_status];
            })->values();

            return response()->json(['success' => true, 'view' => $view,"BenfitGirdOvertime"=>$employees ? $employees->overtime : null, 'leaveDates' => $leaveDates], 200);
        // } catch (\Exception $e) {
        //     return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        // }
    }

    /**
     * Return dates (Y-m-d) on which selected employees already have a duty roster.
     * Used by create-duty-roster to disable those dates in the date range picker.
     */
    public function RosterOccupiedDates(Request $request)
    {
        $empIds = $request->input('emp_ids', []);
        if (!is_array($empIds)) {
            $empIds = $empIds ? [$empIds] : [];
        }
        $empIds = array_filter(array_map('intval', $empIds));

        $dates = [];
        if (empty($empIds)) {
            return response()->json(['dates' => $dates]);
        }

        $resort_id = $this->resort->resort_id;
        $rosters = DutyRoster::where('resort_id', $resort_id)
            ->whereIn('Emp_id', $empIds)
            ->get(['Emp_id', 'ShiftDate']);

        $today = new \DateTime();
        $today->setTime(0, 0, 0); // Remove time part

        $datesByEmployee = array_fill_keys(array_map('strval', $empIds), []);

        foreach ($rosters as $roster) {
            $shiftDate = trim($roster->ShiftDate ?? '');
            if ($shiftDate === '') {
                continue;
            }
            $parts = array_map('trim', explode(' - ', $shiftDate, 2));
            if (count($parts) !== 2) {
                continue;
            }
            $start = \DateTime::createFromFormat('m/d/Y', $parts[0]);
            $end = \DateTime::createFromFormat('m/d/Y', $parts[1]);
            if (!$start || !$end || $start > $end) {
                continue;
            }
            $empIdKey = (string) $roster->Emp_id;
            $current = clone $start;
            $endInclusive = (clone $end)->modify('+1 day');
            while ($current < $endInclusive) {
                if ($current >= $today) {
                    $d = $current->format('Y-m-d');
                    $dates[] = $d;
                    if (isset($datesByEmployee[$empIdKey]) && !in_array($d, $datesByEmployee[$empIdKey])) {
                        $datesByEmployee[$empIdKey][] = $d;
                    }
                }
                $current->modify('+1 day');
            }
        }

        $dates = array_values(array_unique($dates));
        sort($dates);
        foreach (array_keys($datesByEmployee) as $k) {
            $datesByEmployee[$k] = array_values(array_unique($datesByEmployee[$k]));
            sort($datesByEmployee[$k]);
        }

        return response()->json(['dates' => $dates, 'dates_by_employee' => $datesByEmployee]);
    }

    public function StoreDutyRoster(Request $request)
    {
        $Employees = $request->input('Emp_id', []); // Array of Employee IDs
        if(!is_array($Employees)) {
            $Employees = [$Employees];
        }

        $Shift = $request->Shift;   //ShiftId
        $TotalHours = $request->TotalHours; // Shift total hours
        // $request->resort_id was fully client-controlled and never
        // cross-checked — any resort-admin could tag a new duty roster row
        // to ANY resort_id for ANY employee id. Always use the caller's own
        // resort, never trust the posted value.
        $resort_id  = $this->resort->resort_id;
        $DefaultShiftTime = $request->DefaultShiftTime; // its checked and all theet all for the weekdays
        $MakeShift  = $request->MakeShift;// Shift date
        $hiddenInput = $request->hiddenInput; // total Week are Selected
        $DayOfDate = $request->DayOfDate;

        $geofenceZoneIds = $request->input('geofence_zone_ids', []);

        // Handle day off dates - parse comma-separated dates from DayOffDates field
        $DayOffDates = $request->DayOffDates ?? '';
        $dayOffDatesArray = [];
        if (!empty($DayOffDates)) {
            $dayOffDatesArray = array_map('trim', explode(',', $DayOffDates));
        }


        $hiddenInputArray = explode(' - ', $hiddenInput);
        $startingDate = DateTime::createFromFormat('m/d/Y', trim($hiddenInputArray[0]));
        $endingDate = DateTime::createFromFormat('m/d/Y', trim($hiddenInputArray[1]));

        $validator = Validator::make($request->all(), [
            'Emp_id' => [
                'required',
                'array',
                'min:1',
            ],
            'Emp_id.*' => [
                'required',
                Rule::exists('employees', 'id')->where('resort_id', $this->resort->resort_id),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422); // HTTP 422 Unprocessable Entity
        }

        DB::beginTransaction();
        try {
            $shitTime = ShiftSettings::where('id', $Shift)->where('resort_id', $resort_id)->first();

            if(!$shitTime) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Shift not found.',
                ], 422);
            }

            // Was: trust the client-posted employeeOvertime map (manual
            // per-employee OT entry) and gate it on a hardcoded SUP/LINE
            // WORKERS rank whitelist. OT is now computed entirely from the
            // Benefit Grid: expected daily hours = working_hrs_per_week /
            // (7 - day_off_per_week); any shift hours beyond that are OT,
            // only for employees whose grid has overtime = 'yes'. A shift
            // shorter than the grid's expected daily hours is rejected
            // outright rather than silently accepted.
            $toDecimalHours = function ($hhmm) {
                [$h, $m] = array_pad(explode(':', (string) $hhmm), 2, 0);
                return ((int) $h) + ((int) $m) / 60;
            };
            $toHHMM = function ($decimalHours) {
                $decimalHours = max(0, $decimalHours);
                $h = (int) floor($decimalHours);
                $m = (int) round(($decimalHours - $h) * 60);
                if ($m === 60) { $h++; $m = 0; }
                return sprintf('%02d:%02d', $h, $m);
            };
            $shiftHours = $toDecimalHours($TotalHours);

            $mismatchedHoursEmployees = [];
            $ineligibleOvertimeEmployees = [];
            $autoOvertimeByEmployee = [];

            $rosterEmployees = Employee::join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                ->where('employees.resort_id', $resort_id)
                ->whereIn('employees.id', $Employees)
                ->get(['employees.id', 'employees.rank', 'employees.benefit_grid_level', 'employees.Position_id', 't1.first_name', 't1.last_name']);

            foreach ($rosterEmployees as $emp) {
                $empGrade = Common::resolveEmpGrade($resort_id, $emp->rank, $emp->benefit_grid_level, $emp->Position_id);
                $grid = $empGrade
                    ? ResortBenifitGrid::where('resort_id', $resort_id)
                        ->where('emp_grade', $empGrade)
                        ->where('status', 'Active')
                        ->first(['overtime', 'working_hrs_per_week', 'day_off_per_week'])
                    : null;

                if (!$grid || empty($grid->working_hrs_per_week) || $grid->day_off_per_week === null || (int) $grid->day_off_per_week >= 7) {
                    // No usable grid policy to check against — leave this
                    // employee's hours as entered, same as before this fix.
                    continue;
                }

                $workingDaysPerWeek = 7 - (int) $grid->day_off_per_week;
                $dailyTargetHours = $grid->working_hrs_per_week / $workingDaysPerWeek;
                $empName = trim(($emp->first_name ?? '') . ' ' . ($emp->last_name ?? ''));

                if ($shiftHours < $dailyTargetHours - 0.01) {
                    $mismatchedHoursEmployees[] = $empName . ' (expects ' . round($dailyTargetHours, 2) . 'h/day)';
                    continue;
                }

                $excess = $shiftHours - $dailyTargetHours;
                if ($excess > 0.01) {
                    if ($grid->overtime !== 'yes') {
                        $ineligibleOvertimeEmployees[] = $empName;
                        continue;
                    }
                    $autoOvertimeByEmployee[$emp->id] = $toHHMM($excess);
                }
            }

            if (!empty($mismatchedHoursEmployees)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Shift hours do not meet the Benefit Grid\'s expected daily hours for: ' . implode(', ', $mismatchedHoursEmployees) . '.',
                ], 422);
            }
            if (!empty($ineligibleOvertimeEmployees)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Overtime is not applicable for the selected employee(s) per their Benefit Grid: ' . implode(', ', $ineligibleOvertimeEmployees) . '.',
                ], 422);
            }

            // Filter out employees who already have a shift during the specified date range
            $employeesToProcess = [];
            $skippedEmployees = [];

            foreach ($Employees as $empId) {
                $conflictingDates = DutyRoster::where('Emp_id', $empId)
                    ->where('ShiftDate', $hiddenInput)
                    ->exists();

                if ($conflictingDates) {
                    $employee = Employee::join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                        ->where('employees.id', $empId)
                        ->first(['t1.first_name', 't1.last_name']);
                    $empName = $employee ? trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '')) : 'Employee';
                    $skippedEmployees[] = $empName ?: 'Employee';
                } else {
                    $employeesToProcess[] = $empId;
                }
            }

            // If all employees have conflicts, return an error
            if (empty($employeesToProcess)) {
                DB::rollBack();
                $employeeNames = implode(', ', $skippedEmployees);
                return response()->json([
                    'status' => 'error',
                    'message' => "All selected employees ({$employeeNames}) already have a shift during the specified date range.",
                ], 422);
            }

            // Create duty roster only for employees without conflicts
            foreach ($employeesToProcess as $Employee) {
                $DutyRoster = DutyRoster::create([
                    "resort_id"=>$resort_id,
                    "Shift_id"=>$Shift,
                    "Emp_id"=>$Employee,
                    "ShiftDate"=> $hiddenInput,
                    "Year"=>date('Y'),
                    "DayOfDate"=> $DayOfDate,
                    "geofence_zone_id" => !empty($geofenceZoneIds) ? json_encode($geofenceZoneIds) : null,
                ]);
                if(isset($DutyRoster))
                {
                    // OT is now computed entirely from the Benefit Grid
                    // (see $autoOvertimeByEmployee above) — same value for
                    // every non-DayOff date of this employee's shift, since
                    // it's the excess over their grid's expected daily
                    // hours, not a manually-chosen per-date amount.
                    $employeeOvertimeHours = $autoOvertimeByEmployee[$Employee] ?? '00:00';

                    if ($DefaultShiftTime == "All")
                    {
                        $interval = new \DateInterval('P1D');
                        $adjustedEndDate = clone $endingDate;
                        $adjustedEndDate->add($interval); // Add one day to include the end date

                        // Ensure the start and end dates are correctly set
                        $datePeriod = new \DatePeriod($startingDate, $interval, $adjustedEndDate);

                        foreach ($datePeriod as $date)
                        {
                            $currentDate = $date->format('Y-m-d');
                            $currentDateFormatted = $date->format('Y-m-d');

                            // Check for approved leave on the current date
                            $leave = EmployeeLeave::where("emp_id", $Employee)
                            ->join('leave_categories as t1', 't1.id', '=', 'employees_leaves.leave_category_id')
                                ->where("from_date", "<=", $currentDate)
                                ->where("to_date", ">=", $currentDate)
                                ->where("status", "Approved")
                                ->first(['t1.leave_type','employees_leaves.from_date','employees_leaves.to_date']);

                            // Skip creating roster entry if employee has approved leave on this date
                            if ($leave) {
                                continue; // Skip this date - no roster entry will be created
                            }

                            // Check if current date is in the day off dates array
                            $isDayOff = in_array($currentDateFormatted, $dayOffDatesArray);
                            $status = $isDayOff ? "DayOff" : '';

                            $overtimeForThisDay = $isDayOff ? '00:00' : $employeeOvertimeHours;

                            // Create roster entry only if there's no leave
                            $DutyRosterEntry = new DutyRosterEntry;
                            $DutyRosterEntry->roster_id = $DutyRoster->id;
                            $DutyRosterEntry->Shift_id  = $DutyRoster->Shift_id;
                            $DutyRosterEntry->resort_id = $resort_id;
                            $DutyRosterEntry->Emp_id    = $DutyRoster->Emp_id;
                            $DutyRosterEntry->OverTime  = $overtimeForThisDay;
                            $DutyRosterEntry->CheckingTime      = $shitTime->StartTime;
                            $DutyRosterEntry->CheckingOutTime   = $TotalHours;
                            $DutyRosterEntry->date              = $currentDate;
                            $DutyRosterEntry->Status            = $status ? "DayOff" : "Present"; // Default to "Present" if no status is set
                            $DutyRosterEntry->DayWiseTotalHours = $TotalHours;
                            $DutyRosterEntry->save();

                        }
                    }
                    else
                    {
                        $singleDate = date('Y-m-d', strtotime($MakeShift));

                        // Check for approved leave on the single date
                        $leave = EmployeeLeave::where("emp_id", $Employee)
                            ->join('leave_categories as t1', 't1.id', '=', 'employees_leaves.leave_category_id')
                            ->where("from_date", "<=", $singleDate)
                            ->where("to_date", ">=", $singleDate)
                            ->where("status", "Approved")
                            ->first(['t1.leave_type','employees_leaves.from_date','employees_leaves.to_date']);

                        // Skip creating roster entry if employee has approved leave on this date
                        if (!$leave) {
                            $singleDateOvertime = $employeeOvertimeHours;
                            DutyRosterEntry::create([
                                "roster_id" => $DutyRoster->id,
                                "Shift_id" => $DutyRoster->Shift_id,
                                "resort_id" => $resort_id,
                                "Emp_id" => $DutyRoster->Emp_id,
                                "OverTime" => $singleDateOvertime,
                                "DayWiseTotalHours" => $TotalHours,
                                'date' => $singleDate,
                                'CheckingTime'=>$shitTime->StartTime,
                                "CheckingOutTime" => $TotalHours,
                                "Status" => "Present",
                            ]);
                        }
                    }

                    // Check compliance for overtime
                    $hasAnyOvertime = ($employeeOvertimeHours !== '00:00' && $employeeOvertimeHours !== '');
                    $CheckEmployees = Employee::with(['resortAdmin','position','department','EmployeeAttandance'])->where('id',$Employee)->where('resort_id', $this->resort->resort_id)->first();

                    if($CheckEmployees)
                    {
                        if( $CheckEmployees->entitled_overtime  =="no" && $hasAnyOvertime)
                        {

                                Compliance::firstOrCreate([
                                'resort_id' => $this->resort->resort_id,
                                'employee_id' => $CheckEmployees->id,
                                'module_name' => 'Time and Attendance',
                                'compliance_breached_name' => 'Over Time Not Eligibile',
                                'description' => "{$CheckEmployees->Emp_name} ({$CheckEmployees->Emp_id} - {$CheckEmployees->Position_name}) is not eligible for overtime.",
                                'reported_on' => Carbon::now(),
                                'status' => 'Compliant'
                            ]);
                        }
                    }

                    // Employees previously got no in-app or push notification
                    // at all when a new duty roster/shift was created for them.
                    // $hiddenInput is the raw "m/d/Y - m/d/Y" range string, not
                    // a single date — Carbon::parse() can't parse that as-is
                    // and threw, silently swallowed by this same try/catch, so
                    // the notification never actually sent. $startingDate
                    // (already correctly parsed above from the same range) is
                    // the real starting date to use here.
                    try {
                        Common::sendMobileNotification(
                            $resort_id,
                            2,
                            null,
                            null,
                            'New Duty Roster Assigned',
                            'A new duty roster has been assigned to you starting ' . $startingDate->format('d M Y') . '.',
                            'DutyRoster',
                            [$Employee],
                            $DutyRoster->id,
                            false,
                            'duty-roster-created',
                        );
                    } catch (\Exception $notificationException) {
                        \Log::warning('Duty roster notification failed for employee ' . $Employee . ': ' . $notificationException->getMessage());
                    }
                }
            }

            DB::commit();

            // Prepare success message
            $successMessage = "Duty Roster Created Successfully for " . count($employeesToProcess) . " employee(s)";
            if (!empty($skippedEmployees)) {
                $skippedNames = implode(', ', $skippedEmployees);
                $successMessage .= ". Skipped " . count($skippedEmployees) . " employee(s) who already have a shift: {$skippedNames}";
            }

            return response()->json(['success' => true, "message" => $successMessage], 200);
        }
        catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

    }

    public function UpdateDutyRoster(Request  $request)
    {
        $Attd_id = $request->Attd_id;
        $shiftdate = $request->shiftdate;
        $Shift = $request->Shiftpopup;
        $Overtime = $request->ShiftOverTime;
        $DayOfDate = $request->DayOfDate;
        $DayWiseTotalHours = $request->TotalHoursModel;
        $DayOfDateModel = $request->DayOfDateModel;

        // Attd_id is client-supplied; if present it must already belong to
        // this resort — otherwise the updateOrCreate() below would silently
        // edit another resort's duty-roster-entry row.
        if (!empty($Attd_id)) {
            $ownedEntry = DutyRosterEntry::where('id', $Attd_id)
                ->where('resort_id', $this->resort->resort_id)
                ->first();
            if (!$ownedEntry) {
                return response()->json(['success' => false, 'message' => 'Record not found.'], 404);
            }
        }

        try{

            $shift_Date = Carbon::createFromFormat('d/m/Y', $shiftdate)->startOfDay();
                DB::beginTransaction();
                    // Normalize overtime to HH:MM (store on duty_roster_entries so view shows it)
                    $overtimeValue = '00:00';
                    if (!empty($Overtime) && is_string($Overtime)) {
                        $Overtime = trim($Overtime);
                        if (preg_match('/^(\d{1,2}):(\d{2})$/', $Overtime, $m)) {
                            $overtimeValue = sprintf('%02d:%02d', (int)$m[1], (int)$m[2]);
                        }
                    }

                    // For cells that have NO existing DutyRosterEntry (the
                    // "No Shift Assigned" / fresh Day Off path), Attd_id
                    // arrives empty. The view passes emp_id + roster_id in
                    // those cases so we can create a properly-keyed row;
                    // without them the insert would land with NULL Emp_id
                    // and the downstream EmployeeOvertime insert crashes.
                    $editEmpId    = $request->emp_id ?: null;
                    $editRosterId = $request->roster_id ?: null;
                    $createPayload = [
                        "Shift_id"          => $Shift,
                        "DayWiseTotalHours" => $DayWiseTotalHours,
                        "OverTime"          => $overtimeValue,
                    ];
                    if (empty($Attd_id)) {
                        $createPayload["Emp_id"]    = $editEmpId;
                        $createPayload["roster_id"] = $editRosterId;
                        $createPayload["resort_id"] = $this->resort->resort_id;
                        $createPayload["date"]      = $shift_Date->format('Y-m-d');
                        // Status is NOT NULL on duty_roster_entries — default
                        // a freshly-created entry to Present so the row is
                        // valid; HR can flip to DayOff later if needed.
                        $createPayload["Status"]    = 'Present';
                    }

                    $DutyRosterEntry = DutyRosterEntry::updateOrCreate(['id'=>$Attd_id], $createPayload);

                    // Handle overtime from employee_overtimes table (for reporting/payroll)
                    if ($DutyRosterEntry && $Overtime) {
                        $resort_id = $this->resort->resort_id;
                        $Emp_id = $DutyRosterEntry->Emp_id ?: $editEmpId;
                        $dateFormatted = $shift_Date->format('Y-m-d');

                        // Parse overtime (format: HH:MM or H:MM)
                        $overtimeParts = explode(':', $Overtime);
                        if (count($overtimeParts) == 2) {
                            $overtimeHours = (int)$overtimeParts[0];
                            $overtimeMinutes = (int)$overtimeParts[1];

                            // Delete all existing overtime entries for this date and employee to ensure consistency
                            EmployeeOvertime::where('Emp_id', $Emp_id)
                                ->where('resort_id', $resort_id)
                                ->whereDate('date', $dateFormatted)
                                ->delete();

                            // Only create overtime if it's greater than 00:00
                            if ($overtimeHours > 0 || $overtimeMinutes > 0) {
                                // Get shift end time to calculate overtime start/end
                                $shiftSettings = \App\Models\ShiftSettings::find($Shift);
                                $shiftEndTime = $shiftSettings ? $shiftSettings->EndTime : '18:00';

                                // Calculate overtime end time (overtime starts right after shift ends)
                                $shiftEndCarbon = Carbon::createFromFormat('H:i', $shiftEndTime);
                                $overtimeEndCarbon = $shiftEndCarbon->copy()->addHours($overtimeHours)->addMinutes($overtimeMinutes);

                                // Create new overtime entry
                                EmployeeOvertime::create([
                                    'resort_id' => $resort_id,
                                    'Emp_id' => $Emp_id,
                                    'Shift_id' => $Shift,
                                    'roster_id' => $DutyRosterEntry->roster_id ?? null,
                                    'date' => $dateFormatted,
                                    'start_time' => $shiftEndTime,
                                    'end_time' => $overtimeEndCarbon->format('H:i'),
                                    'total_time' => sprintf('%02d:%02d', $overtimeHours, $overtimeMinutes),
                                    'status' => 'pending',
                                    'overtime_type' => 'after_shift',
                                ]);
                            }
                        }
                    }

                DB::commit();


                DutyRoster::where("id",$DutyRosterEntry->roster_id)
                    ->where('resort_id', $this->resort->resort_id)
                    ->update(["DayOfDate"=>$DayOfDateModel]);
                // roster_id back to the client — the "No Shift Assigned"/
                // create-on-edit path has no roster_id available client-side
                // until this resolves it (an existing entry's edit button
                // doesn't carry data-roster_id at all), and the geo-fence
                // zone follow-up save needs a real one either way.
                return response()->json(['success' => true, 'message' => "Duty roster updated successfully", 'roster_id' => $DutyRosterEntry->roster_id]);
        }
        catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }


    }

    /**
     * Re-assign the geofence zone(s) on an existing duty_rosters row — the
     * only way to change it today is re-creating the roster from scratch;
     * this lets HR/HOD change it from view-duty-roster the same way they
     * already change Shift/Overtime.
     */
    public function UpdateDutyRosterGeofence(Request $request)
    {
        // exists:duty_rosters,id / exists:resort_geofences,id were not
        // tenant-scoped, and the update below had no resort_id check at
        // all — any resort-admin could repoint another resort's duty-roster
        // row's geofence to arbitrary geofence ids.
        $request->validate([
            'roster_id' => ['required', 'integer', Rule::exists('duty_rosters', 'id')->where('resort_id', $this->resort->resort_id)],
            'geofence_zone_ids' => 'nullable|array',
            'geofence_zone_ids.*' => ['integer', Rule::exists('resort_geofences', 'id')->where('resort_id', $this->resort->resort_id)],
        ]);

        try {
            $updated = DutyRoster::where('id', $request->roster_id)
                ->where('resort_id', $this->resort->resort_id)
                ->update([
                    'geofence_zone_id' => !empty($request->geofence_zone_ids) ? json_encode($request->geofence_zone_ids) : null,
                ]);

            if (!$updated) {
                return response()->json(['success' => false, 'message' => 'Duty roster not found.'], 404);
            }

            return response()->json(['success' => true, 'message' => 'Geofence zone updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function DutyRosterSearch(Request $request)
    {
        $searchTerm = $request->input('search');
        $Position  = $request->input('Position');
        $Department = $request->input('Department');
        $dateRange = $request->input('dateRange'); // Format: "YYYY-MM-DD to YYYY-MM-DD" or "YYYY-MM-DD"
        $sendclass = $request->input('sendclass');
        $Dept_id = $this->resort->GetEmployee->Dept_id ?? '';
        $Rank =  $this->resort->GetEmployee->rank ?? '';
        $employeeRankPosition = Common::getEmployeeRankPosition( $this->resort->getEmployee);

        // Use the same query structure as ViewDutyRoster — including the
        // deterministic-latest-row subquery (see comment there); it was
        // also missing t3.geofence_zone_id from the select entirely, so
        // the zone badge could never render off a search/filter result.
        $latestRosterPerEmp = DB::table('duty_rosters')
                                ->select('Emp_id', DB::raw('MAX(id) as latest_id'))
                                ->where('resort_id', $this->resort->resort_id)
                                ->groupBy('Emp_id');

        $Rosterdata1 = Employee::join('resort_admins as t1',"t1.id","=","employees.Admin_Parent_id")
                                ->join('resort_positions as t2',"t2.id","=","employees.Position_id")
                                ->joinSub($latestRosterPerEmp, 'latest_dr', function($join) {
                                    $join->on('latest_dr.Emp_id', '=', 'employees.id');
                                })
                                ->join('duty_rosters as t3',"t3.id","=","latest_dr.latest_id")
                                ->leftJoin('resort_departments as t4',"t4.id","=","employees.Dept_id")
                                ->leftJoin('resort_sections as t5',"t5.id","=","t2.section_id")
                                ->select('t3.id as duty_roster_id', 't3.DayOfDate', 't3.geofence_zone_id', 't1.id as Parentid', 't1.first_name', 't1.last_name', 't1.profile_picture', 'employees.id as emp_id', 't2.position_title', 'employees.Dept_id', 't2.section_id as Section_id', 't4.name as dept_name', 't5.name as section_name')
                                ->where('t1.resort_id', $this->resort->resort_id);

        if($this->resort->is_master_admin == 0){
            if($employeeRankPosition['position'] != "HR")
            {
                if($employeeRankPosition['position'] != "EXCOM")
                {
                    $Rosterdata1->whereIn('employees.id', $this->underEmp_id);
                }
            }
        }

        // Apply filters
        if (isset($Department) && $Department != '') {
            $Rosterdata1->where('employees.Dept_id', $Department);
        }

        if (isset($Position) && $Position != '') {
            $Rosterdata1->where('employees.Position_id', $Position);
        }

        if (isset($searchTerm) && $searchTerm != '') {
            $Rosterdata1->where(function ($query) use ($searchTerm) {
                $query->where('employees.id', 'like', '%' . $searchTerm . '%')
                    ->orWhere('t1.last_name', 'like', '%' . $searchTerm . '%');
            });
        }

        // Date range filter - if provided, filter by roster entries date
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        if (isset($dateRange) && $dateRange != '') {
            // Parse date range - format: "YYYY-MM-DD to YYYY-MM-DD" or single date "YYYY-MM-DD"
            if (strpos($dateRange, ' to ') !== false) {
                $dates = explode(' to ', $dateRange);
                $startDate = Carbon::parse(trim($dates[0]))->startOfDay();
                $endDate = Carbon::parse(trim($dates[1]))->endOfDay();
            } else {
                // Single date - use that month
                $startDate = Carbon::parse($dateRange)->startOfMonth();
                $endDate = Carbon::parse($dateRange)->endOfMonth();
            }

            $startOfMonth = $startDate;
            $endOfMonth = $endDate;

            $Rosterdata1->leftJoin('duty_roster_entries as t6', 't6.roster_id', '=', 't3.id')
                        ->whereBetween('t6.date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
        }

        $Rosterdata = $Rosterdata1->get();

        // Date calculations - use date range if provided, otherwise use current month
        if (isset($dateRange) && $dateRange != '') {
            // Already calculated startOfMonth and endOfMonth above
            $year = $startOfMonth->format('Y');
            $month = $startOfMonth->format('m');
            $WeekstartDate = $startOfMonth->copy()->startOfWeek();
            $WeekendDate = $endOfMonth->copy()->endOfWeek();

            // Generate headers for the date range
            $monthwiseheaders = [];
            $currentDate = $startOfMonth->copy();
            while ($currentDate->lte($endOfMonth)) {
                $monthwiseheaders[] = [
                    "day" => str_pad($currentDate->format('d'), 2, '0', STR_PAD_LEFT),
                    "dayname" => $currentDate->format('D'),
                    'date' => $currentDate->format('Y-m-d')
                ];
                $currentDate->addDay();
            }
        } else {
            $year = now()->year;
            $month = now()->month;
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();
            $WeekstartDate = Carbon::now()->startOfWeek();
            $WeekendDate = Carbon::now()->endOfWeek();

            $totalDays = Carbon::createFromDate($year, $month, 1)->daysInMonth;

            // Generate monthwise headers
            $monthwiseheaders = [];
            for ($day = 1; $day <= $totalDays; $day++) {
                $date = Carbon::createFromDate($year, $month, $day);
                $dayName = $date->format('D');
                $monthwiseheaders[] = ["day"=>str_pad($day, 2, '0', STR_PAD_LEFT),"dayname" => $dayName,'date'=>$date->format('Y-m-d')];
            }
        }

        // Generate weekly headers
        $headers = [];
        $numberOfDays = 7;
        $days = [];
        for ($i = 0; $i < $numberOfDays; $i++) {
            $currentDate = $WeekstartDate->clone()->addDays($i);
            $headers[] = [
                'date' => $currentDate->format('d M'),
                'day' => $currentDate->format('D'),
                'full_date' => $currentDate
            ];
            $days[] = $currentDate->format('D');
        }

        // Group roster data by department and section (same logic as ViewDutyRoster)
        $groupedRosterData = [];

        // First, get all unique departments from roster data
        $uniqueDeptIds = $Rosterdata->pluck('Dept_id')->filter()->unique();

        // Initialize departments and load all their sections
        foreach ($uniqueDeptIds as $deptId) {
            if ($deptId && $deptId != 'no_dept') {
                $dept = ResortDepartment::where('id', $deptId)
                    ->where('resort_id', $this->resort->resort_id)
                    ->first(['id', 'name']);

                if ($dept) {
                    $groupedRosterData[$deptId] = [
                        'dept_id' => $deptId,
                        'dept_name' => $dept->name,
                        'sections' => [],
                        'employees' => []
                    ];

                    // Get all sections for this department
                    $allSections = ResortSection::where('dept_id', $deptId)
                        ->where('resort_id', $this->resort->resort_id)
                        ->get(['id', 'name']);

                    // Initialize all sections for this department
                    foreach ($allSections as $section) {
                        // Use integer key for consistent comparison
                        $sectionKey = (int)$section->id;
                        $groupedRosterData[$deptId]['sections'][$sectionKey] = [
                            'section_id' => $sectionKey,
                            'section_name' => $section->name,
                            'employees' => []
                        ];
                    }
                }
            }
        }

        // Handle employees with no department
        $noDeptRoster = $Rosterdata->filter(function($roster) {
            return !$roster->Dept_id || $roster->Dept_id == 'no_dept';
        });

        if ($noDeptRoster->count() > 0) {
            $groupedRosterData['no_dept'] = [
                'dept_id' => 'no_dept',
                'dept_name' => 'No Department',
                'sections' => [],
                'employees' => []
            ];
        }

        // Now populate employees into departments and sections
        foreach ($Rosterdata as $roster) {
            // `?? 'no_dept'` only catches a literal null — a Dept_id of 0/'0'/''
            // slipped through as itself, landing in a second, separately-keyed
            // "No Department" bucket (dept_name falls back to the same label
            // when the left-joined department row doesn't exist) instead of
            // the one already initialized above. Match the falsy check used
            // by the $noDeptRoster filter so both agree on what "no dept" is.
            $deptId = !$roster->Dept_id ? 'no_dept' : $roster->Dept_id;
            $rawSectionId = $roster->Section_id;
            $sectionName = $roster->section_name ?? null;

            // Initialize department if not exists (fallback)
            if (!isset($groupedRosterData[$deptId])) {
                $deptName = $roster->dept_name ?? 'No Department';
                $groupedRosterData[$deptId] = [
                    'dept_id' => $deptId,
                    'dept_name' => $deptName,
                    'sections' => [],
                    'employees' => []
                ];

                // Load all sections for this department if it's a valid department ID
                if ($deptId && $deptId != 'no_dept') {
                    $allSections = ResortSection::where('dept_id', $deptId)
                        ->where('resort_id', $this->resort->resort_id)
                        ->get(['id', 'name']);

                    // Initialize all sections for this department
                    foreach ($allSections as $section) {
                        // Use integer key for consistent comparison
                        $sectionKey = (int)$section->id;
                        $groupedRosterData[$deptId]['sections'][$sectionKey] = [
                            'section_id' => $sectionKey,
                            'section_name' => $section->name,
                            'employees' => []
                        ];
                    }
                }
            }

            // Check if employee has a valid section ID (from position)
            $hasSection = false;
            $sectionId = null;

            if ($rawSectionId !== null && $rawSectionId !== '' && $rawSectionId !== 0 && $rawSectionId !== '0' && $rawSectionId !== 'no_section') {
                // Convert to integer for consistent comparison
                $sectionId = (int)$rawSectionId;
                $hasSection = ($sectionId > 0);
            }

            // If employee has a valid section, add to section
            if ($hasSection && isset($groupedRosterData[$deptId])) {
                // Ensure section exists in the structure (if not, create it)
                if (!isset($groupedRosterData[$deptId]['sections'][$sectionId])) {
                    // Try to get section name from database if not in roster data
                    if (!$sectionName) {
                        $section = ResortSection::where('id', $sectionId)
                            ->where('resort_id', $this->resort->resort_id)
                            ->first(['id', 'name']);
                        $sectionName = $section ? $section->name : 'Section ' . $sectionId;
                    }
                    $groupedRosterData[$deptId]['sections'][$sectionId] = [
                        'section_id' => $sectionId,
                        'section_name' => $sectionName,
                        'employees' => []
                    ];
                }
                // Add employee to section
                $groupedRosterData[$deptId]['sections'][$sectionId]['employees'][] = $roster;
            } else {
                // Employee without section, add directly to department
                if (isset($groupedRosterData[$deptId])) {
                    $groupedRosterData[$deptId]['employees'][] = $roster;
                }
            }
        }

        $LeaveCategory = LeaveCategory::where("resort_id",$this->resort->resort_id)->get();
        $resort_id = $this->resort->resort_id;
        $ResortPosition = ResortPosition::where("dept_id", $Dept_id)
                                        ->where("resort_id",$this->resort->resort_id)->get();
        $ResortDepartment = ResortDepartment::where("resort_id", $this->resort->resort_id)
                                            ->where('status', 'active')
                                            ->orderBy('name', 'asc')
                                            ->get(['id', 'name', 'code']);
        $ShiftSettings = ShiftSettings::where("resort_id", $this->resort->resort_id)->get(['id','ShiftName','TotalHours']);

        // Get public holidays (including Fridays)
        $publicHolidays = $this->getPublicHolidays($resort_id, $startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d'));

        // Return only the accordion content that goes inside .appendData
        // This matches the structure in ViewDutyRoster.blade.php from line 58-407
        $view = view('resorts.renderfiles.DutyRosterAccordion', compact('headers','WeekstartDate','WeekendDate','monthwiseheaders','Rosterdata','groupedRosterData','resort_id','ShiftSettings','startOfMonth','endOfMonth','LeaveCategory','publicHolidays'))->render();

        return response()->json(['success' => true, 'view' => $view], 200);
    }

    public function LocationHistory(Request $request)
    {

        $page_title = "Location History";
        $Dept_id = $this->resort->GetEmployee->Dept_id;
        $Rank =  $this->resort->GetEmployee->rank;

        $searchTerm = $request->searchTerm;
        $position = $request->position;
        $date       = $request->date;

        $Rosterdata1 = Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
        ->join('resort_positions as t2', "t2.id", "=", "employees.Position_id")
        ->join('duty_rosters as t3', "t3.Emp_id", "=", "employees.id")
        ->join('resort_departments as t4', "t4.id", "=", "t2.dept_id")
        ->Join('duty_roster_entries as t6', function ($join) {
            $join->on('t6.roster_id', '=', 't3.id')
                 ->whereNotNull('t6.CheckingTime')
                 ->whereNotNull('t6.CheckingOutTime');
        })

        ->leftJoin('child_attendaces as t7', "t7.Parent_attd_id", "=", "t6.id")
        ->select('t6.date',
                 't3.id as duty_roster_id',
                 't3.DayOfDate',
                 't1.id as Parentid',
                 't1.first_name',
                 't1.last_name',
                 't1.profile_picture',
                 'employees.id as emp_id',
                 't2.position_title',
                 't6.CheckingTime as CheckIn',
                 't6.CheckingOutTime as CheckOut',
                 't7.InTime_Location',
                 't7.OutTime_Location',
                 't2.position_title as Position',
                 't2.code as PositionCode',
                 't4.name as DepartmentName',
                 't4.code as DepartmentCode'
                )
                ->groupBy('employees.id')
        ->where("employees.Dept_id", $Dept_id)
        ->where("t1.resort_id", $this->resort->resort_id)
        ->where("employees.rank", "!=", $Rank)
        ->whereIn("t6.Status",['On-Time','Late','ShortLeave','HalfDayLeave','Present']);
        if (isset($searchTerm) && !empty($searchTerm)) {
            $Rosterdata1 = $Rosterdata1->where(function ($query) use ($searchTerm) {
                $query->where('t1.first_name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('t1.last_name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('t4.name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('t4.code', 'like', '%' . $searchTerm . '%')

                    ->orWhere('t2.code', 'like', '%' . $searchTerm . '%')
                    ->orWhere('t2.position_title', 'like', '%' . $searchTerm . '%')
                    ->orWhere('t6.CheckingOutTime', 'like', '%' . $searchTerm . '%')
                    ->orWhere('t6.CheckingTime', 'like', '%' . $searchTerm . '%');
            });

        }

        if (isset($position) && !empty($position)) {
            $Rosterdata1 = $Rosterdata1->where('t2.id', $position);
        }

        if (isset($date) && !empty($date))
        {
            $parsedDate = Carbon::createFromFormat('d/m/Y', $date);
            $Rosterdata1 = $Rosterdata1->whereDate('t6.date', $parsedDate);

        }

        $Rosterdata = $Rosterdata1->whereDate('t6.date',  Carbon::today()->subday()) // Use Carbon for the date
        ->get()
        ->map(function ($item) {
            $item->CheckIn = Carbon::createFromFormat('H:i', $item->CheckIn)->format('h:i A');
            $item->CheckOut = Carbon::createFromFormat('H:i', $item->CheckOut)->format('h:i A');
            $item->date = Carbon::parse($item->date)->format('d/m/Y');
            return $item;
        });

            if ($request->ajax())
            {

                return datatables()->of($Rosterdata)
                    ->addColumn('EmployeeName', function ($row) {
                        $image =Common::getResortUserPicture($row->Parentid); // Default image if not found
                        $name = $row->first_name . ' ' . $row->last_name;

                        return'
                            <div class="tableUser-block">
                                <div class="img-circle">
                                    <img src="' . $image . '" alt="user">
                                </div>
                                <span class="userApplicants-btn">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</span>
                            </div>';
                    })

                    // Add a new column for Position with badge
                    ->addColumn('Position', function ($row) {
                        return $row->Position . ' <span class="badge badge-themeLight">' . htmlspecialchars($row->PositionCode, ENT_QUOTES, 'UTF-8') . '</span>';
                    })

                    ->addColumn('Department', function ($row) {
                        return $row->DepartmentName . ' <span class="badge badge-themeLight">' . htmlspecialchars($row->DepartmentCode, ENT_QUOTES, 'UTF-8') . '</span>';
                    })

                    ->addColumn('CheckIn', function ($row) {
                        return $row->CheckIn.'  <i data-id="checkin" data-location="'.$row->InTime_Location.'" class="LocationHistoryData fa-regular fa-location-dot me-2"></i>';
                    })
                    ->addColumn('CheckOut', function ($row) {
                        return $row->CheckOut.'  <i data-id="checkout" data-location="'.$row->OutTime_Location.'" class=" LocationHistoryData fa-regular fa-location-dot me-2"></i>';
                    })
                    // Add an action column with buttons
                    // ->addColumn('action', function ($row) {
                    //     return '<a href="javascript:void(0)" class="LocationHistoryData a-link" data-location="'.$row->InTime_Location.'"><i class="fa-regular fa-location-dot me-2"></i>View   Location</a>';
                    // })



                    ->rawColumns(['EmployeeName','Position','Department','CheckOut','CheckIn','action'])
                    ->make(true);
            }


            $ResortPosition = ResortPosition::where("dept_id", $Dept_id)
                                                 ->where("resort_id",$this->resort->resort_id)->get();
        return view('Resorts.TimeAndAttendance.dutyroster.LocationHistory',compact('page_title','ResortPosition'));
    }

    public function OverTime()
    {

        $Dept_id = $this->resort->GetEmployee->Dept_id ?? '';
        $Rank =  $this->resort->GetEmployee->rank ?? '';
        $resort_id   = $this->resort->resort_id;
        $employeeRankPosition = Common::getEmployeeRankPosition( $this->resort->getEmployee);
        $Rosterdata = Employee::join('resort_admins as t1',"t1.id","=","employees.Admin_Parent_id")
                                ->join('resort_positions as t2',"t2.id","=","employees.Position_id")
                                ->select('t1.id as Parentid', 't1.first_name', 't1.last_name', 't1.profile_picture', 'employees.id as emp_id', 'employees.Emp_id', 't2.position_title')
                                ->where("t1.resort_id",$this->resort->resort_id)
                                ->where('employees.status', 'Active');

                                if($employeeRankPosition['position'] != "HR" && $employeeRankPosition['position'] != "EXCOM")
                                {
                                    // Non-HR/EXCOM users only see their own department
                                    $Rosterdata=$Rosterdata->where('employees.Dept_id', $Dept_id);
                                }
                                // withQueryString() — pagination links previously
                                // dropped every filter param (month/year/
                                // overtime_type/search/Poitions) except page
                                // itself, so clicking "page 2" silently reset the
                                // whole view to today's defaults instead of
                                // staying on whatever period/filter was selected.
                                $Rosterdata=$Rosterdata->paginate(10)->withQueryString();

        $year = now()->year;
        $month = now()->month;

        // Get cutoff day from payroll configuration
        $cutoffDay = PayrollConfig::where('resort_id', $this->resort->resort_id)->value('cutoff_day') ?? 1;

        // Calculate cutoff period based on current month/year
        // Cutoff day = last day of period. Period starts on cutoff+1
        $baseDate = Carbon::createFromDate($year, $month, 1);
        $prevMonth = $baseDate->copy()->subMonthNoOverflow();
        $startOfMonth = $prevMonth->copy()->day(min($cutoffDay, $prevMonth->daysInMonth))->addDay();
        $endOfMonth = $baseDate->copy()->day(min($cutoffDay, $baseDate->daysInMonth));
        $totalDays = $startOfMonth->diffInDays($endOfMonth) + 1;

        // Build monthwise headers for the cutoff period
        $monthwiseheaders = [];
        $headerDate = $startOfMonth->copy();
        for ($i = 0; $i < $totalDays; $i++) {
            $monthwiseheaders[] = [
                "day" => $headerDate->format('d'),
                "dayname" => $headerDate->format('D'),
                'date' => $headerDate->format('Y-m-d'),
                'month' => $headerDate->format('M'),
            ];
            $headerDate->addDay();
        }

        $ResortPosition = ResortPosition::where("dept_id", $Dept_id)
        ->where("resort_id",$this->resort->resort_id)->get();
        $employees = Employee::join('resort_admins as t1',"t1.id","=","employees.Admin_Parent_id")
                        ->where("t1.resort_id",$this->resort->resort_id)
                        ->where("employees.status","Active");

        if($employeeRankPosition['position'] != "HR" && $employeeRankPosition['position'] != "EXCOM") {
            $employees = $employees->where('employees.Dept_id', $Dept_id);
        }

        $employees = $employees->get(['t1.first_name','t1.last_name','t1.profile_picture','employees.*']);
        $ShiftSettings = ShiftSettings::where("resort_id", $this->resort->resort_id)->get(['id','ShiftName','TotalHours']);

        // Get public holidays (including Fridays)
        $publicHolidays = $this->getPublicHolidays($resort_id, $startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d'));

        $page_title = "Over Time";
        return view('resorts.timeandattendance.dutyroster.Overtime',compact('monthwiseheaders','Rosterdata','resort_id','ResortPosition','employees','ShiftSettings','startOfMonth','endOfMonth','page_title','publicHolidays'));
    }

    public function OverTimeFilter(Request $request)
    {
        $searchTerm = $request->input('search');
        $Poitions  =  $request->input('Poitions');
        $overtime_type = $request->input('overtime_type');
        $Dept_id = $this->resort->GetEmployee->Dept_id ?? '';
        $Rank =  $this->resort->GetEmployee->rank ?? '';
        $month = $request->month;
        $year  = $request->year;

        // If month/year not selected, default to current
        if (empty($month)) $month = now()->month;
        if (empty($year)) $year = now()->year;
        $month = (int) $month;
        $year = (int) $year;
        if ($month < 1 || $month > 12) $month = now()->month;
        if ($year < 2000 || $year > 2100) $year = now()->year;

        // Calculate cutoff period based on selected month/year
        // Cutoff day = last day of period. Period starts on cutoff+1
        $cutoffDay = PayrollConfig::where('resort_id', $this->resort->resort_id)->value('cutoff_day') ?? 1;
        $baseDate = Carbon::createFromDate($year, $month, 1);
        $prevMonth = $baseDate->copy()->subMonthNoOverflow();
        $startOfMonth = $prevMonth->copy()->day(min($cutoffDay, $prevMonth->daysInMonth))->addDay();
        $endOfMonth = $baseDate->copy()->day(min($cutoffDay, $baseDate->daysInMonth));
        $totalDays = $startOfMonth->diffInDays($endOfMonth) + 1;

        $employeeRankPosition = Common::getEmployeeRankPosition($this->resort->getEmployee);

        if ($overtime_type == 'actual') {
            // Actual OT: get active employees (OT data comes from employee_overtimes in the view)
            $Rosterdata1 = Employee::join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                        ->join('resort_positions as t2', 't2.id', '=', 'employees.Position_id')
                        ->select(
                            't1.id as Parentid',
                            't1.first_name',
                            't1.last_name',
                            't1.profile_picture',
                            'employees.id as emp_id',
                            'employees.Emp_id',
                            't2.position_title'
                        )
                        ->where('t1.resort_id', $this->resort->resort_id)
                        ->where('employees.status', 'Active');
        } else {
            // Pre-Planned OT: get employees who have pre-planned OT in the cutoff period
            $Rosterdata1 = Employee::join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                        ->join('resort_positions as t2', 't2.id', '=', 'employees.Position_id')
                        ->join('duty_roster_entries as t4', function($join) use ($startOfMonth, $endOfMonth) {
                            $join->on('t4.Emp_id', '=', 'employees.id')
                                 ->where('t4.resort_id', $this->resort->resort_id)
                                 ->where('t4.OverTime', '!=', '00:00')
                                 ->whereBetween('t4.date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')]);
                        })
                        ->select(
                            't1.id as Parentid',
                            't1.first_name',
                            't1.last_name',
                            't1.profile_picture',
                            'employees.id as emp_id',
                            'employees.Emp_id as Emp_code',
                            't2.position_title',
                            DB::raw("COALESCE(t4.OTStatus, 'pending') as status")
                        )
                        ->where('t1.resort_id', $this->resort->resort_id)
                        ->where('employees.status', 'Active')
                        ->groupBy('employees.id');
        }

        if ($employeeRankPosition['position'] != "HR" && $employeeRankPosition['position'] != "EXCOM") {
            $Rosterdata1->where('employees.Dept_id', $Dept_id);
        }

        if (!empty($Poitions)) {
            $Rosterdata1->where('employees.Position_id', $Poitions);
        }

        if (!empty($searchTerm)) {
            $Rosterdata1->where('employees.id', $searchTerm);
        }

        // withQueryString() — pagination links previously dropped every
        // filter param (month/year/overtime_type/search/Poitions) except
        // page itself, so clicking "page 2" silently reset to today's
        // defaults instead of staying on whatever period/filter was
        // selected — this is the AJAX-filtered path's pagination links.
        $Rosterdata = $Rosterdata1->paginate(10)->withQueryString();

        // Build monthwise headers for the cutoff period
        $monthwiseheaders = [];
        $headerDate = $startOfMonth->copy();
        for ($i = 0; $i < $totalDays; $i++) {
            $monthwiseheaders[] = [
                "day" => $headerDate->format('d'),
                "dayname" => $headerDate->format('D'),
                'date' => $headerDate->format('Y-m-d'),
                'month' => $headerDate->format('M'),
            ];
            $headerDate->addDay();
        }
        $resort_id = $this->resort->resort_id;

        // Get public holidays (including Fridays)
        $publicHolidays = $this->getPublicHolidays($resort_id, $startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d'));

        if (!$request->get('page')) {
            if ($overtime_type == 'actual') {
                $view = view('resorts.renderfiles.OverTimeSearch', compact('Rosterdata','monthwiseheaders','resort_id','startOfMonth','endOfMonth','publicHolidays'))->render();
            } else {
                $view = view('resorts.renderfiles.OverTimeSearch2', compact('Rosterdata','monthwiseheaders','resort_id','startOfMonth','endOfMonth','publicHolidays'))->render();
            }
            return response()->json(['success' => true, 'view' => $view], 200);
        }
                    else
                    {
                        $ResortPosition = ResortPosition::where("dept_id", $Dept_id)
                                                ->where("resort_id",$this->resort->resort_id)->get();
                        $employees = Employee::join('resort_admins as t1',"t1.id","=","employees.Admin_Parent_id")->where("Dept_id",$Dept_id)
                                                ->where("t1.resort_id",$this->resort->resort_id)
                                                ->where("employees.rank","!=",$Rank)
                                                ->get(['t1.first_name','t1.last_name','t1.profile_picture','employees.*']);
                        $ShiftSettings = ShiftSettings::where("resort_id", $this->resort->resort_id)->get(['id','ShiftName','TotalHours']);

                        $page_title = 'Over Time';
                        return view('resorts.timeandattendance.dutyroster.Overtime',compact('monthwiseheaders','Rosterdata','resort_id','ResortPosition','employees','ShiftSettings','startOfMonth','endOfMonth','page_title','publicHolidays'));
                    }

    }

    public function StoreOverTime(Request $request)
    {
        $Emp_id = $request->Emp_id;
        $date = $request->date;
        $entries = $request->entries ?? [];
        $resort_id = $this->resort->resort_id;

        if (empty($entries)) {
            return response()->json(['success' => false, 'message' => 'Please provide at least one overtime entry.']);
        }

        // Department validation: everyone can only approve OT for their own department.
        // Dept_id is not resort-namespaced, so this alone doesn't prove
        // Emp_id belongs to this resort (see audit "LIKELY" finding) — verify
        // the employee is actually in this resort before trusting Dept_id.
        $loggedInDeptId = $this->resort->GetEmployee->Dept_id ?? '';
        $targetEmployee = Employee::where('id', $Emp_id)->where('resort_id', $resort_id)->first();
        if (!$targetEmployee) {
            return response()->json(['success' => false, 'message' => 'Employee not found.'], 404);
        }
        if ($loggedInDeptId != $targetEmployee->Dept_id) {
            return response()->json(['success' => false, 'message' => 'You can only manage overtime for employees in your own department.']);
        }

        $dateCarbon = Carbon::parse($date);

        // Check if employee has duty roster entry for this date — was
        // unscoped by resort_id, so it could match another resort's roster
        // entry for the same Emp_id/date and write OT onto it below.
        $DutyRosterEntry = DutyRosterEntry::where('Emp_id', $Emp_id)
            ->where('resort_id', $resort_id)
            ->whereDate('date', $dateCarbon)
            ->first();

        if ($DutyRosterEntry == NULL) {
            return response()->json(['success' => false, 'message' => 'This day employee not present in duty roster.']);
        }

        // Get existing overtime entries for this date and employee — fetched
        // before the DayOff check below so that check can tell a pure
        // reject-existing-entries request apart from a create/approve one.
        $existingEntries = EmployeeOvertime::where('Emp_id', $Emp_id)
            ->where('resort_id', $resort_id)
            ->whereDate('date', $dateCarbon)
            ->get()
            ->keyBy('id');

        // A day off blocks creating new overtime hours or approving them —
        // an employee shouldn't earn/get paid OT for a day they weren't
        // scheduled to work. But an already-logged entry on that date
        // (created before the roster was marked DayOff, or entered in
        // error) still needs to be rejectable, or a bad entry on a day-off
        // date could never be cleared out. Only bypass the block when
        // every submitted entry is an existing one being set to rejected.
        $isRejectingOnlyExistingEntries = collect($entries)->isNotEmpty() && collect($entries)->every(function ($entry) use ($existingEntries) {
            $entryId = $entry['id'] ?? null;
            return $entryId && $existingEntries->has($entryId) && ($entry['status'] ?? null) === 'rejected';
        });

        if ($DutyRosterEntry->Status == 'DayOff' && !$isRejectingOnlyExistingEntries) {
            return response()->json(['success' => false, 'message' => 'There is a day off on this date.']);
        }

        $shiftId = $DutyRosterEntry->Shift_id ?? null;
        if (!$shiftId && !$isRejectingOnlyExistingEntries) {
            return response()->json(['success' => false, 'message' => 'Shift information not found for this date.']);
        }

        $entryIds = [];
        foreach ($entries as $entry) {
            $startTime = $entry['start_time'] ?? null;
            $endTime = $entry['end_time'] ?? null;
            $entryId = $entry['id'] ?? null;
            $isExistingEntry = $entryId && $existingEntries->has($entryId);

            // Pre-planned OT is defined ahead of the actual shift — real
            // check-in/out later determines whether the employee genuinely
            // worked it (see Common::calculateOvertimeEntries at checkout,
            // which is the only other place OT entries get created, always
            // as 'pending'). Letting the creator hand-pick "Approved" here
            // approved hours before any work happened. New entries always
            // start pending; only an existing entry being reviewed/edited
            // can have its status changed.
            $status = $isExistingEntry ? ($entry['status'] ?? 'pending') : 'pending';

            if (!$startTime || !$endTime) {
                continue;
            }

            // Calculate total time
            $startCarbon = Carbon::createFromFormat('H:i', $startTime);
            $endCarbon = Carbon::createFromFormat('H:i', $endTime);

            // Handle overnight overtime
            if ($endCarbon->lt($startCarbon)) {
                $endCarbon->addDay();
            }

            $totalMinutes = $startCarbon->diffInMinutes($endCarbon);
            $totalHours = floor($totalMinutes / 60);
            $totalMins = $totalMinutes % 60;
            $totalTime = sprintf('%02d:%02d', $totalHours, $totalMins);

            // On a day off being rejected, $shiftId is null (no shift is
            // assigned) — don't clobber an existing entry's own Shift_id
            // with null, keep whatever it was created with.
            $entryShiftId = $shiftId ?? ($isExistingEntry ? $existingEntries[$entryId]->Shift_id : null);

            $overtimeData = [
                'resort_id' => $resort_id,
                'Emp_id' => $Emp_id,
                'Shift_id' => $entryShiftId,
                'roster_id' => $DutyRosterEntry->roster_id ?? null,
                'date' => $dateCarbon->format('Y-m-d'),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'total_time' => $totalTime,
                'status' => $status,
            ];

            if ($isExistingEntry) {
                // Update existing entry
                EmployeeOvertime::where('id', $entryId)->update($overtimeData);
                $entryIds[] = $entryId;
            } else {
                // Create new entry
                $newEntry = EmployeeOvertime::create($overtimeData);
                $entryIds[] = $newEntry->id;
            }
        }

        // Delete entries that were removed
        $existingEntries->each(function ($entry) use ($entryIds) {
            if (!in_array($entry->id, $entryIds)) {
                $entry->delete();
            }
        });

        // View Duty Roster's per-day "OT: X hr" label reads
        // duty_roster_entries.OverTime, not the employee_overtimes table —
        // this form only ever wrote the latter, so OT defined here never
        // showed up on the roster calendar even though it saved fine.
        // Keep both in sync: total planned minutes across every entry
        // still on this date.
        $totalMinutes = EmployeeOvertime::where('Emp_id', $Emp_id)
            ->where('resort_id', $resort_id)
            ->whereDate('date', $dateCarbon)
            ->get()
            ->sum(function ($entry) {
                [$h, $m] = array_pad(explode(':', $entry->total_time ?? '0:0'), 2, 0);
                return ((int) $h * 60) + (int) $m;
            });
        $DutyRosterEntry->OverTime = sprintf('%02d:%02d', intdiv($totalMinutes, 60), $totalMinutes % 60);
        $DutyRosterEntry->save();

        return response()->json(['success' => true, 'message' => 'Overtime saved successfully.']);
    }

  public function ViewDutyRoster(){
        $Dept_id = $this->resort->GetEmployee->Dept_id ?? '';
        $Rank =  $this->resort->GetEmployee->rank ?? '';
        $employeeRank = Common::getEmployeeRank($this->resort->getEmployee);
        $employeeRankPosition = Common::getEmployeeRankPosition( $this->resort->getEmployee);

        $isGM = ($employeeRankPosition['position'] == 'GM') || (($employeeRankPosition['rank'] ?? '') == 'GM');
        if($this->resort->is_master_admin == 0){
            if($employeeRank['isHR'] != true && !$isGM)
            {
                $employees = Employee::join('resort_admins as t1',"t1.id","=","employees.Admin_Parent_id")

                                ->where("t1.resort_id",$this->resort->resort_id)
                                // ->whereIn('employees.id', $this->underEmp_id)
                                 ->where("employees.Dept_id",$Dept_id)
                                ->where("employees.status","Active")
                                ->get(['t1.first_name','t1.last_name','t1.profile_picture','employees.*']);
            }else{
                $employees = Employee::join('resort_admins as t1',"t1.id","=","employees.Admin_Parent_id")
                                ->where("t1.resort_id",$this->resort->resort_id)
                                // ->where("employees.Dept_id",$Dept_id)
                                ->where("employees.status","Active")
                                ->get(['t1.first_name','t1.last_name','t1.profile_picture','employees.*']);
            }
        }else{
            $employees = Employee::join('resort_admins as t1',"t1.id","=","employees.Admin_Parent_id")
                                ->where("t1.resort_id",$this->resort->resort_id)
                                ->where("employees.status","Active")
                                ->get(['t1.first_name','t1.last_name','t1.profile_picture','employees.*']);
        }
        $ResortPosition = ResortPosition::where("dept_id", $Dept_id)
                                        ->where("resort_id",$this->resort->resort_id)->get();
        $resort_id   = $this->resort->resort_id;

        $startOfMonth = Carbon::now()->startOfMonth(); // Get the first day of the month
        $endOfMonth =Carbon::now()->endOfMonth(); // Get the last day of the month

        $WeekstartDate = Carbon::now()->startOfWeek(); //Week start Start date
        $WeekendDate = Carbon::now()->endOfWeek();

        $headers = [];
        $numberOfDays = 7;
        $days = [];
        for ($i = 0; $i < $numberOfDays; $i++)
        {
            $currentDate = $WeekstartDate->clone()->addDays($i);
            $headers[] = [
                'date' => $currentDate->format('d M'),
                'day' => $currentDate->format('D'),
                'full_date' => $currentDate
            ];
            $days[] =$currentDate->format('D');
        }


        // groupBy('employees.id') previously selected 't3.*' columns
        // (duty_roster_id, DayOfDate, geofence_zone_id) that aren't
        // functionally dependent on the group key — under this app's
        // non-strict MySQL connection that's legal but the row MySQL
        // picks for those hidden columns is arbitrary, NOT guaranteed to
        // be the one matching the trailing orderBy (ORDER BY runs after
        // GROUP BY collapses rows). An employee with more than one
        // duty_rosters submission could have their zone/roster_id come
        // from an unrelated older block, so the correct one's geofence
        // zone badge silently fails to render ("Assign zone" shown
        // instead). Pick the latest row per employee deterministically
        // via a subquery instead of relying on GROUP BY's picked row.
        $latestRosterPerEmp = DB::table('duty_rosters')
                                ->select('Emp_id', DB::raw('MAX(id) as latest_id'))
                                ->where('resort_id', $this->resort->resort_id)
                                ->groupBy('Emp_id');

        $Rosterdata1 = Employee::join('resort_admins as t1',"t1.id","=","employees.Admin_Parent_id")
                                ->join('resort_positions as t2',"t2.id","=","employees.Position_id")
                                ->joinSub($latestRosterPerEmp, 'latest_dr', function($join) {
                                    $join->on('latest_dr.Emp_id', '=', 'employees.id');
                                })
                                ->join('duty_rosters as t3',"t3.id","=","latest_dr.latest_id")
                                ->leftJoin('resort_departments as t4',"t4.id","=","employees.Dept_id")
                                ->leftJoin('resort_sections as t5',"t5.id","=","t2.section_id")
                                ->select('t3.id as duty_roster_id', 't3.DayOfDate', 't3.geofence_zone_id', 't1.id as Parentid', 't1.first_name', 't1.last_name', 't1.profile_picture', 'employees.id as emp_id', 't2.position_title', 'employees.Dept_id', 't2.section_id as Section_id', 't4.name as dept_name', 't5.name as section_name')
                                ->where('t1.resort_id', $this->resort->resort_id);

                                if($this->resort->is_master_admin == 0){
                                    if($employeeRankPosition['position'] != "HR")
                                    {
                                        if($employeeRankPosition['position'] != "EXCOM")
                                        {
                                            if($employeeRankPosition['position'] != "GM" && ($employeeRankPosition['rank'] ?? '') != "GM")
                                            {
                                                $Rosterdata1->whereIn('employees.id', $this->underEmp_id);
                                            }
                                        }
                                    }
                                }

                                $Rosterdata=$Rosterdata1->get();

        // Determine if user can see all departments.
        // Only HR and GM are resort-wide roles. HOD/EXCOM head a single
        // department each — they were wrongly OR'd in here, so any
        // department's HOD/EXCOM (e.g. F&B) saw every other department's
        // duty roster (Accounting, etc.) instead of just their own.
        $canViewAllDepartments = false;

        if ($this->resort->is_master_admin == 1) {
            // Master admin can see all departments
            $canViewAllDepartments = true;
        } else {
            // Check if user is HR (by department or position)
            $isHR = $employeeRank['isHR'] || ($employeeRankPosition['position'] == 'HR');

            // Check if user is GM (by rank or position) - GM sees same scope as HR
            $isGMForView = ($employeeRankPosition['position'] == 'GM') || (($employeeRankPosition['rank'] ?? '') == 'GM');

            // Only HR or GM can view all departments; HOD/EXCOM stay scoped to their own.
            $canViewAllDepartments = $isHR || $isGMForView;
        }

        // Group roster data by department and section
        $groupedRosterData = [];

        // First, get all unique departments from roster data
        $uniqueDeptIds = $Rosterdata->pluck('Dept_id')->filter()->unique();

        // Initialize departments and load all their sections
        foreach ($uniqueDeptIds as $deptId) {
            if ($deptId && $deptId != 'no_dept') {
                // Filter by department if user is not HR/HOD/EXCOM
                if (!$canViewAllDepartments && $deptId != $Dept_id) {
                    continue; // Skip departments that don't match user's department
                }

                $dept = ResortDepartment::where('id', $deptId)
                    ->where('resort_id', $this->resort->resort_id)
                    ->first(['id', 'name']);

                if ($dept) {
                    $groupedRosterData[$deptId] = [
                        'dept_id' => $deptId,
                        'dept_name' => $dept->name,
                        'sections' => [],
                        'employees' => []
                    ];

                    // Get all sections for this department
                    $allSections = ResortSection::where('dept_id', $deptId)
                        ->where('resort_id', $this->resort->resort_id)
                        ->get(['id', 'name']);

                    // Initialize all sections for this department
                    foreach ($allSections as $section) {
                        // Use integer key for consistent comparison
                        $sectionKey = (int)$section->id;
                        $groupedRosterData[$deptId]['sections'][$sectionKey] = [
                            'section_id' => $sectionKey,
                            'section_name' => $section->name,
                            'employees' => []
                        ];
                    }
                }
            }
        }

        // Handle employees with no department (only if user can view all departments)
        if ($canViewAllDepartments) {
            $noDeptRoster = $Rosterdata->filter(function($roster) {
                return !$roster->Dept_id || $roster->Dept_id == 'no_dept';
            });

            if ($noDeptRoster->count() > 0) {
                $groupedRosterData['no_dept'] = [
                    'dept_id' => 'no_dept',
                    'dept_name' => 'No Department',
                    'sections' => [],
                    'employees' => []
                ];
            }
        }

        // Now populate employees into departments and sections
        foreach ($Rosterdata as $roster) {
            // `?? 'no_dept'` only catches a literal null — a Dept_id of 0/'0'/''
            // slipped through as itself, landing in a second, separately-keyed
            // "No Department" bucket (dept_name falls back to the same label
            // when the left-joined department row doesn't exist) instead of
            // the one already initialized above. Match the falsy check used
            // by the $noDeptRoster filter so both agree on what "no dept" is.
            $deptId = !$roster->Dept_id ? 'no_dept' : $roster->Dept_id;
            $rawSectionId = $roster->Section_id;
            $sectionName = $roster->section_name ?? null;

            // Skip if department filtering is enabled and this department doesn't match
            if (!$canViewAllDepartments && $deptId != 'no_dept' && $deptId != $Dept_id) {
                continue;
            }

            // Initialize department if not exists (fallback)
            if (!isset($groupedRosterData[$deptId])) {
                $deptName = $roster->dept_name ?? 'No Department';
                $groupedRosterData[$deptId] = [
                    'dept_id' => $deptId,
                    'dept_name' => $deptName,
                    'sections' => [],
                    'employees' => []
                ];

                // Load all sections for this department if it's a valid department ID
                if ($deptId && $deptId != 'no_dept') {
                    $allSections = ResortSection::where('dept_id', $deptId)
                        ->where('resort_id', $this->resort->resort_id)
                        ->get(['id', 'name']);

                    // Initialize all sections for this department
                    foreach ($allSections as $section) {
                        // Use integer key for consistent comparison
                        $sectionKey = (int)$section->id;
                        $groupedRosterData[$deptId]['sections'][$sectionKey] = [
                            'section_id' => $sectionKey,
                            'section_name' => $section->name,
                            'employees' => []
                        ];
                    }
                }
            }

            // Check if employee has a valid section ID
            // Section_id should be not null, not 0, not empty string
            $hasSection = false;
            $sectionId = null;

            if ($rawSectionId !== null && $rawSectionId !== '' && $rawSectionId !== 0 && $rawSectionId !== '0' && $rawSectionId !== 'no_section') {
                // Convert to integer for consistent comparison
                $sectionId = (int)$rawSectionId;
                $hasSection = ($sectionId > 0);
            }

            // If employee has a valid section, add to section
            if ($hasSection && isset($groupedRosterData[$deptId])) {
                // Ensure section exists in the structure (if not, create it)
                if (!isset($groupedRosterData[$deptId]['sections'][$sectionId])) {
                    // Try to get section name from database if not in roster data
                    if (!$sectionName) {
                        $section = ResortSection::where('id', $sectionId)
                            ->where('resort_id', $this->resort->resort_id)
                            ->first(['id', 'name']);
                        $sectionName = $section ? $section->name : 'Section ' . $sectionId;
                    }
                    $groupedRosterData[$deptId]['sections'][$sectionId] = [
                        'section_id' => $sectionId,
                        'section_name' => $sectionName,
                        'employees' => []
                    ];
                }
                // Add employee to section
                $groupedRosterData[$deptId]['sections'][$sectionId]['employees'][] = $roster;
            } else {
                // Employee without section (Section_id is null, 0, or empty), add directly to department
                if (isset($groupedRosterData[$deptId])) {
                    $groupedRosterData[$deptId]['employees'][] = $roster;
                }
            }
        }
        $year = now()->year; // Current year
        $month = now()->month; // Current month
        $totalDays = Carbon::createFromDate($year, $month, 1)->daysInMonth; //

        $monthwiseheaders=[];
        for ($day = 1; $day <= $totalDays; $day++)
        {
            $date = Carbon::createFromDate($year, $month, $day); // Create a date for each day
            $dayName = $date->format('D'); // Get the day name (e.g., Mon, Tue)

            $monthwiseheaders[] = ["day"=>str_pad($day, 2, '0', STR_PAD_LEFT),"dayname" => $dayName,'date'=>$date->format('Y-m-d')];
        }
        $LeaveCategory = LeaveCategory::where("resort_id",$this->resort->resort_id)->get();
        $statusCount = [
            "Absent"=>0,
            "Present"=>0,
            "Late"=>0,
            "DayOff"=>0,
            "ShortLeave"=>0,
            "HalfDayLeave"=>0,
            "FullDayLeave"=>0,

        ];
        $ShiftSettings = ShiftSettings::where("resort_id", $this->resort->resort_id)->get(['id','ShiftName','TotalHours']);
        $ResortDepartment = ResortDepartment::where("resort_id", $this->resort->resort_id)
                                            ->where('status', 'active')
                                            ->orderBy('name', 'asc')
                                            ->get(['id', 'name', 'code']);

        // Get public holidays (including Fridays)
        $publicHolidays = $this->getPublicHolidays($resort_id, $startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d'));

        $geofenceZones = \App\Models\ResortGeofence::where('resort_id', $resort_id)->where('status', 'active')->orderBy('name')->get();

        $page_title = 'View Duty Roster';
        return view('resorts.timeandattendance.dutyroster.ViewDutyRoster',compact('headers','WeekstartDate','WeekendDate','monthwiseheaders','headers','Rosterdata','groupedRosterData','resort_id','ResortPosition','ResortDepartment','employees','ShiftSettings','startOfMonth','endOfMonth','page_title','LeaveCategory','publicHolidays','geofenceZones'));

    }


}


