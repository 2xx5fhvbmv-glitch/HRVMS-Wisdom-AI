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
use App\Models\Compliance;
use App\Models\ResortHoliday;
use App\Models\EmployeeOvertime;
class DutyRosterController extends Controller
{
    protected $resort;

    protected $underEmp_id=[];

    public function __construct()
    {
        $this->resort = $resortId = auth()->guard('resort-admin')->user();
        if(!$this->resort) return;
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


        $Rosterdata1 = Employee::join('resort_admins as t1',"t1.id","=","employees.Admin_Parent_id")
                                ->join('resort_positions as t2',"t2.id","=","employees.Position_id")
                                ->join('duty_rosters as t3',"t3.Emp_id","=","employees.id")
                                ->select('t3.id as duty_roster_id', 't3.DayOfDate', 't1.id as Parentid', 't1.first_name', 't1.last_name', 't1.profile_picture', 'employees.id as emp_id', 't2.position_title')
                                ->where('t1.resort_id', $this->resort->resort_id)
                                ->where('t3.resort_id', $this->resort->resort_id);

                                if($this->resort->is_master_admin == 0){
                                    if($employeeRankPosition['position'] != "HR")
                                    {
                                        if($employeeRankPosition['position'] != "EXCOM")
                                        {
                                            $Rosterdata1->whereIn('employees.id', $this->underEmp_id);
                                        }
                                    }
                                }

                                $Rosterdata=$Rosterdata1->groupBy('employees.id')
                                ->orderBy('t3.created_at', 'desc')
                                ->paginate(10);
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

        // Get public holidays (including Fridays) - use month range to include all Fridays in the month
        $publicHolidays = $this->getPublicHolidays($resort_id, $startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d'));

        return view('resorts.timeandattendance.dutyroster.CreateDutyRoster',compact('endOfMonth','startOfMonth','WeekstartDate','WeekendDate','days','page_title','headers','employees','ShiftSettings','resort_id','Rosterdata','ResortPosition','totalDays','monthwiseheaders','LeaveCategory','statusCount','publicHolidays'));
    }

    public function DutyRosterandLeave(Request $request)
    {
        $id = $request->id;
        $Rank =  $this->resort->GetEmployee->rank;

        // try
        // {
            $employees = Employee::join('resort_admins as t1',"t1.id","=","employees.Admin_Parent_id")
                                    ->join('resort_positions as t2',"t2.id","=","employees.Position_id")
                                    ->join('resort_benifit_grid as t3',"t3.emp_grade","=","employees.rank")
                                    ->where("employees.id",$id)
                                    ->where('t1.status','Active')
                                    ->where('employees.status','Active')
                                    ->where('t1.resort_id',$this->resort->resort_id)
                                    ->first(['t3.overtime','t1.id as Parentid','employees.rank','t1.first_name','t1.last_name','t1.profile_picture','employees.*','t2.position_title']);

            $currentDay = Carbon::now()->format('Y-m-d');
            $currentMonthEnd = Carbon::now()->endOfMonth()->format('Y-m-d');

            $EmployeeLeave = Employee::join('resort_admins as t3', 't3.id', '=', 'employees.Admin_Parent_id')
                        ->join('employees_leaves as t2', 't2.emp_id', '=', 'employees.id')
                        ->join('leave_categories as t1', 't1.id', '=', 't2.leave_category_id')
                        ->where('t1.resort_id', $this->resort->resort_id)
                        ->where('t2.emp_id', $id)
                        ->where('t2.status', "Approved")
                        ->where(function ($query) use ($currentDay,$currentMonthEnd) {
                            $query->where('t2.from_date', '>=', $currentDay) // Leave started on or before the current day
                                ->orWhere('t2.to_date', '>=', $currentDay); // Leave ends on or after the current day
                            // ->where('t2.to_date', '>=', $currentMonthEnd); // Leave ends on or after the current day
                        })
                        ->get();
            $view =  view('resorts.renderfiles.dutyrosterandLeave',compact('employees','EmployeeLeave'))->render();

            return response()->json(['success' => true, 'view' => $view,"BenfitGirdOvertime"=>$employees ? $employees->overtime : null], 200);
        // } catch (\Exception $e) {
        //     return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        // }
    }

    public function StoreDutyRoster(Request $request)
    {
        $Employees = $request->input('Emp_id', []); // Array of Employee IDs
        if(!is_array($Employees)) {
            $Employees = [$Employees];
        }

        $Shift = $request->Shift;   //ShiftId
        $employeeOvertimeRaw = $request->employeeOvertime ?? '{}';
        $employeeOvertime = json_decode($employeeOvertimeRaw, true) ?? []; // Overtime data with days


        $TotalHours = $request->TotalHours; // Shift total hours
        $resort_id  = $request->resort_id;
        $DefaultShiftTime = $request->DefaultShiftTime; // its checked and all theet all for the weekdays
        $MakeShift  = $request->MakeShift;// Shift date
        $hiddenInput = $request->hiddenInput; // total Week are Selected
        $DayOfDate = $request->DayOfDate;

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
                'exists:employees,id',
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

            // Filter out employees who already have a shift during the specified date range
            $employeesToProcess = [];
            $skippedEmployees = [];

            foreach ($Employees as $empId) {
                $conflictingDates = DutyRoster::where('Emp_id', $empId)
                    ->where('ShiftDate', $hiddenInput)
                    ->exists();

                if ($conflictingDates) {
                    $employee = Employee::find($empId);
                    $empName = $employee ? ($employee->first_name . ' ' . $employee->last_name) : 'Employee';
                    $skippedEmployees[] = $empName;
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
                ]);
                if(isset($DutyRoster))
                {
                    // Get overtime for this employee - handle both string and integer keys
                    $employeeOvertimeHours = '00:00';
                    $employeeOvertimeDays = [];

                    // Try to find overtime data - check both string and integer keys
                    if(isset($employeeOvertime[$Employee])) {
                        $employeeOvertimeHours = $employeeOvertime[$Employee]['overtime'] ?? '00:00';
                        $employeeOvertimeDays = $employeeOvertime[$Employee]['days'] ?? [];
                    } elseif(isset($employeeOvertime[(string)$Employee])) {
                        $employeeOvertimeHours = $employeeOvertime[(string)$Employee]['overtime'] ?? '00:00';
                        $employeeOvertimeDays = $employeeOvertime[(string)$Employee]['days'] ?? [];
                    } elseif(isset($employeeOvertime[(int)$Employee])) {
                        $employeeOvertimeHours = $employeeOvertime[(int)$Employee]['overtime'] ?? '00:00';
                        $employeeOvertimeDays = $employeeOvertime[(int)$Employee]['days'] ?? [];
                    }

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

                            // Check if this date has overtime for this employee
                            // Overtime should NOT be added on DayOff days
                            if ($isDayOff) {
                                $overtimeForThisDay = '00:00'; // No overtime on DayOff
                            } else {
                                $hasOvertime = in_array($currentDateFormatted, $employeeOvertimeDays);
                                $overtimeForThisDay = $hasOvertime ? $employeeOvertimeHours : '00:00';
                            }

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
                            DutyRosterEntry::create([
                                "roster_id" => $DutyRoster->id,
                                "Shift_id" => $DutyRoster->Shift_id,
                                "resort_id" => $resort_id,
                                "Emp_id" => $DutyRoster->Emp_id,
                                "OverTime" => isset($employeeOvertimeDays[0]) ? $employeeOvertimeHours : '00:00',
                                "DayWiseTotalHours" => $TotalHours,
                                'date' => $singleDate,
                                'CheckingTime'=>$shitTime->StartTime,
                                "CheckingOutTime" => $TotalHours,
                                "Status" => "Present",
                            ]);
                        }
                    }

                    // Check compliance for overtime
                    $CheckEmployees = Employee::with(['resortAdmin','position','department','EmployeeAttandance'])->where('id',$Employee)->where('resort_id', $this->resort->resort_id)->first();

                    if($CheckEmployees)
                    {
                        if( $CheckEmployees->entitled_overtime  =="no" &&  $employeeOvertimeHours != "00:00")
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
        $DutyRosterEntry = DutyRosterEntry::find($Attd_id);

        try{

            $shift_Date = Carbon::createFromFormat('d/m/Y', $shiftdate);
            if ($shift_Date->isPast()) {
                return response()->json(['success' => false, 'message' => "Sorry you can't updated past data"]);
            }
            else
            {
                DB::beginTransaction();
                    $DutyRosterEntry = DutyRosterEntry::updateOrCreate(['id'=>$Attd_id],[
                        "Shift_id"=>$Shift,
                        "DayWiseTotalHours"=>$DayWiseTotalHours,
                        // "Status"=>"DayOff"

                    ]);

                    // Handle overtime from employee_overtimes table
                    if ($DutyRosterEntry && $Overtime) {
                        $resort_id = $this->resort->resort_id;
                        $Emp_id = $DutyRosterEntry->Emp_id;
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
                                    'status' => 'approved',
                                    'overtime_type' => 'after_shift',
                                ]);
                            }
                        }
                    }

                DB::commit();


                DutyRoster::where("id",$DutyRosterEntry->roster_id)->update(["DayOfDate"=>$DayOfDateModel]);
                return response()->json(['success' => true, 'message' => "Duty roster updated successfully"]);
            }
            return response()->json(['success' => true, 'message' => 'Step data saved successfully.']);
        }
        catch (\Exception $e) {
            DB::rollBack();
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

        // Use the same query structure as ViewDutyRoster
        $Rosterdata1 = Employee::join('resort_admins as t1',"t1.id","=","employees.Admin_Parent_id")
                                ->join('resort_positions as t2',"t2.id","=","employees.Position_id")
                                ->join('duty_rosters as t3',"t3.Emp_id","=","employees.id")
                                ->leftJoin('resort_departments as t4',"t4.id","=","employees.Dept_id")
                                ->leftJoin('resort_sections as t5',"t5.id","=","t2.section_id")
                                ->select('t3.id as duty_roster_id', 't3.DayOfDate', 't1.id as Parentid', 't1.first_name', 't1.last_name', 't1.profile_picture', 'employees.id as emp_id', 't2.position_title', 'employees.Dept_id', 't2.section_id as Section_id', 't4.name as dept_name', 't5.name as section_name')
                                ->where('t1.resort_id', $this->resort->resort_id)
                                ->where('t3.resort_id', $this->resort->resort_id);

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
                $query->where('t1.first_name', 'like', '%' . $searchTerm . '%')
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

        $Rosterdata = $Rosterdata1->groupBy('employees.id')
                                ->orderBy('t3.created_at', 'desc')
                                ->get();

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
            $deptId = $roster->Dept_id ?? 'no_dept';
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
                                ->join('duty_rosters as t3',"t3.Emp_id","=","employees.id")
                                ->select('t3.id as duty_roster_id', 't3.DayOfDate', 't1.id as Parentid', 't1.first_name', 't1.last_name', 't1.profile_picture', 'employees.id as emp_id', 't2.position_title')
                                ->where("t1.resort_id",$this->resort->resort_id);

                                if($employeeRankPosition['position'] != "HR")
                                {
                                    if($employeeRankPosition['position'] != "EXCOM")
                                    {
                                        $Rosterdata=$Rosterdata->whereIn('employees.id',  $this->underEmp_id);
                                    }
                                }
                                $Rosterdata=$Rosterdata->groupBy('employees.id');
                                $Rosterdata=$Rosterdata->paginate(10);

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
        $ResortPosition = ResortPosition::where("dept_id", $Dept_id)
        ->where("resort_id",$this->resort->resort_id)->get();
        if($this->resort->is_master_admin == 0){
            $employees = Employee::join('resort_admins as t1',"t1.id","=","employees.Admin_Parent_id")->where("Dept_id",$Dept_id)
                            ->where("t1.resort_id",$this->resort->resort_id)
                            ->where("employees.rank","!=",$Rank)
                            ->get(['t1.first_name','t1.last_name','t1.profile_picture','employees.*']);
            }else{
                $employees = Employee::join('resort_admins as t1',"t1.id","=","employees.Admin_Parent_id")
                                ->where("t1.resort_id",$this->resort->resort_id)
                                ->where("employees.status","Active")
                                ->get(['t1.first_name','t1.last_name','t1.profile_picture','employees.*']);
            }

        $ShiftSettings = ShiftSettings::where("resort_id", $this->resort->resort_id)->get(['id','ShiftName','TotalHours']);

        $startOfMonth = Carbon::now()->startOfMonth(); // Get the first day of the month
        $endOfMonth =Carbon::now()->endOfMonth(); // Get the last day of the month

        // Get public holidays (including Fridays)
        $publicHolidays = $this->getPublicHolidays($resort_id, $startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d'));

        $page_title = "Over Time";
        return view('resorts.timeandattendance.dutyroster.Overtime',compact('monthwiseheaders','Rosterdata','resort_id','ResortPosition','employees','ShiftSettings','startOfMonth','endOfMonth','page_title','publicHolidays'));
    }

    public function OverTimeFilter(Request $request)
    {


        $searchTerm = $request->input('search');
        $Poitions  =  $request->input('Poitions');
        $filterDate = $request->input('date');
        $sendclass = $request->input('sendclass');
        $Dept_id = $this->resort->GetEmployee->Dept_id;
        $Rank =  $this->resort->GetEmployee->rank;
        $employeeRankPosition = Common::getEmployeeRankPosition( $this->resort->getEmployee);


        $Rosterdata1 = Employee::join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
                        ->join('resort_positions as t2', 't2.id', '=', 'employees.Position_id')
                        ->join('duty_rosters as t3', 't3.Emp_id', '=', 'employees.id')
                        ->leftjoin('duty_roster_entries as t4', 't4.roster_id', '=', 't3.id')
                        ->select(
                            't3.id as duty_roster_id',
                            't3.DayOfDate',
                            't1.id as Parentid',
                            't1.first_name',
                            't1.last_name',
                            't1.profile_picture',
                            'employees.id as emp_id',
                            't2.position_title',
                            't4.date'
                        )
                        ->groupBy('employees.id')
                        ->where('t1.resort_id', $this->resort->resort_id);

                        if($employeeRankPosition['position'] != "HR" || $employeeRankPosition['rank'] != "EXCOM")
                        {
                            $Rosterdata1->whereIn('employees.id',  $this->underEmp_id);
                        }

                    // Check for the `$Poitions` variable and apply the filter if set
                    if (isset($Poitions)) {
                        $Rosterdata1->where('employees.Position_id', $Poitions);
                    }
                    if (isset($filterDate))
                    {

                        $filterDate1 = Carbon::createFromFormat('d/m/Y', $filterDate);

                        $Rosterdata1->whereBetween('t4.date', [ $filterDate1->copy()->startOfMonth()->format('Y-m-d'), $filterDate1->copy()->endOfMonth()->format('Y-m-d')]);

                    }

                    if (isset($searchTerm)) {
                        $Rosterdata1->where(function ($query) use ($searchTerm) {
                            $query->where('t1.first_name', 'like', '%' . $searchTerm . '%')
                                ->orWhere('t1.last_name', 'like', '%' . $searchTerm . '%');
                        });
                    }

                    $Rosterdata = $Rosterdata1->paginate(10);

                    if (!isset($filterDate))
                    {

                        $year = now()->year; // Current year
                        $month = now()->month; // Current month
                        $totalDays = Carbon::createFromDate($year, $month, 1)->daysInMonth; //

                        $startOfMonth = Carbon::now()->startOfMonth(); // Get the first day of the month
                        $endOfMonth =Carbon::now()->endOfMonth(); // Get the last day of the month
                        $WeekstartDate = Carbon::now()->startOfWeek();
                        $WeekendDate = Carbon::now()->endOfWeek();


                    }
                    else
                    {
                        $filterDate1 = Carbon::createFromFormat('d/m/Y', $filterDate);
                        $year = $filterDate1->format('Y'); // Get the year
                        $month = $filterDate1->format('m'); // Get the year
                        $totalDays = Carbon::createFromDate($year, $month, 1)->daysInMonth; //
                        $startOfMonth = $filterDate1->startOfMonth();
                        $endOfMonth = $startOfMonth->copy()->endOfMonth();
                        $WeekstartDate = Carbon::createFromFormat('d/m/Y',$filterDate)->startOfWeek();
                        $WeekendDate = Carbon::createFromFormat('d/m/Y',$filterDate)->endOfWeek();
                    }

                    // dd($startOfMonth ,$endOfMonth);

                    $monthwiseheaders=[];
                    for ($day = 1; $day <= $totalDays; $day++)
                    {
                        $date = Carbon::createFromDate($year, $month, $day); // Create a date for each day
                        $dayName = $date->format('D'); // Get the day name (e.g., Mon, Tue)
                        $monthwiseheaders[] = ["day"=>str_pad($day, 2, '0', STR_PAD_LEFT),"dayname" => $dayName,'date'=>$date->format('Y-m-d')];
                    }
                    $resort_id   = $this->resort->resort_id;

                    // Get public holidays (including Fridays)
                    $publicHolidays = $this->getPublicHolidays($resort_id, $startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d'));

                    if(!$request->get('page'))
                    {
                        $view = view('resorts.renderfiles.OverTimeSearch', compact('Rosterdata','monthwiseheaders','resort_id','startOfMonth','endOfMonth','publicHolidays'))->render();
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

        $dateCarbon = Carbon::parse($date);

        // Check if employee has duty roster entry for this date
        $DutyRosterEntry = DutyRosterEntry::where('Emp_id', $Emp_id)
            ->whereDate('date', $dateCarbon)
            ->first();

        if ($DutyRosterEntry == NULL) {
            return response()->json(['success' => false, 'message' => 'This day employee not present in duty roster.']);
        }

        if ($DutyRosterEntry->Status == 'DayOff') {
            return response()->json(['success' => false, 'message' => 'There is a day off on this date.']);
        }

        $shiftId = $DutyRosterEntry->Shift_id ?? null;
        if (!$shiftId) {
            return response()->json(['success' => false, 'message' => 'Shift information not found for this date.']);
        }

        // Get existing overtime entries for this date and employee
        $existingEntries = EmployeeOvertime::where('Emp_id', $Emp_id)
            ->where('resort_id', $resort_id)
            ->whereDate('date', $dateCarbon)
            ->get()
            ->keyBy('id');

        $entryIds = [];
        foreach ($entries as $entry) {
            $startTime = $entry['start_time'] ?? null;
            $endTime = $entry['end_time'] ?? null;
            $status = $entry['status'] ?? 'pending';
            $entryId = $entry['id'] ?? null;

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

            $overtimeData = [
                'resort_id' => $resort_id,
                'Emp_id' => $Emp_id,
                'Shift_id' => $shiftId,
                'roster_id' => $DutyRosterEntry->roster_id ?? null,
                'date' => $dateCarbon->format('Y-m-d'),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'total_time' => $totalTime,
                'status' => $status,
            ];

            if ($entryId && $existingEntries->has($entryId)) {
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

        return response()->json(['success' => true, 'message' => 'Overtime saved successfully.']);
    }

    public function ViewDutyRoster(){
        $Dept_id = $this->resort->GetEmployee->Dept_id ?? '';
        $Rank =  $this->resort->GetEmployee->rank ?? '';
        $employeeRank = Common::getEmployeeRank($this->resort->getEmployee);
        $employeeRankPosition = Common::getEmployeeRankPosition( $this->resort->getEmployee);

        if($this->resort->is_master_admin == 0){
            if($employeeRank['isHR'] != true)
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


        $Rosterdata1 = Employee::join('resort_admins as t1',"t1.id","=","employees.Admin_Parent_id")
                                ->join('resort_positions as t2',"t2.id","=","employees.Position_id")
                                ->join('duty_rosters as t3',"t3.Emp_id","=","employees.id")
                                ->leftJoin('resort_departments as t4',"t4.id","=","employees.Dept_id")
                                ->leftJoin('resort_sections as t5',"t5.id","=","t2.section_id")
                                ->select('t3.id as duty_roster_id', 't3.DayOfDate', 't1.id as Parentid', 't1.first_name', 't1.last_name', 't1.profile_picture', 'employees.id as emp_id', 't2.position_title', 'employees.Dept_id', 't2.section_id as Section_id', 't4.name as dept_name', 't5.name as section_name')
                                ->where('t1.resort_id', $this->resort->resort_id)
                                ->where('t3.resort_id', $this->resort->resort_id);

                                if($this->resort->is_master_admin == 0){
                                    if($employeeRankPosition['position'] != "HR")
                                    {
                                        if($employeeRankPosition['position'] != "EXCOM")
                                        {
                                            $Rosterdata1->whereIn('employees.id', $this->underEmp_id);
                                        }
                                    }
                                }

                                $Rosterdata=$Rosterdata1->groupBy('employees.id')
                                ->orderBy('t3.created_at', 'desc')
                                ->get();

        // Determine if user can see all departments
        // User can see all departments if: HR, HOD, or EXCOM
        $canViewAllDepartments = false;

        if ($this->resort->is_master_admin == 1) {
            // Master admin can see all departments
            $canViewAllDepartments = true;
        } else {
            // Check if user is HR (by department or position)
            $isHR = $employeeRank['isHR'] || ($employeeRankPosition['position'] == 'HR');

            // Check if user is HOD (by rank)
            $isHOD = ($employeeRankPosition['rank'] == 'HOD');

            // Check if user is EXCOM (by rank or position)
            $isEXCOM = ($employeeRankPosition['rank'] == 'EXCOM') || ($employeeRankPosition['position'] == 'EXCOM');

            // User can view all departments if they are HR, HOD, or EXCOM
            $canViewAllDepartments = $isHR || $isHOD || $isEXCOM;
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
            $deptId = $roster->Dept_id ?? 'no_dept';
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

        $page_title = 'View Duty Roster';
        return view('resorts.timeandattendance.dutyroster.ViewDutyRoster',compact('headers','WeekstartDate','WeekendDate','monthwiseheaders','headers','Rosterdata','groupedRosterData','resort_id','ResortPosition','ResortDepartment','employees','ShiftSettings','startOfMonth','endOfMonth','page_title','LeaveCategory','publicHolidays'));

    }


}


