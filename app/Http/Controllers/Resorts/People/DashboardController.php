<?php

namespace App\Http\Controllers\Resorts\People;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\Models\Employee;
use App\Models\ResortAdmin;
use App\Models\ResortDivision;
use App\Models\Announcement;
use App\Models\AnnouncementCategory;
use App\Models\EmployeePromotion;
use App\Models\EmployeePromotionApproval;
use App\Models\EmployeeInfoUpdateRequest;
use App\Models\PayrollAdvance;
use App\Models\PayrollAdvanceGuarantor;
use App\Models\PeopleSalaryIncrement;
use App\Models\EmployeeResignation;
use App\Models\ExitClearanceFormAssignment;
use App\Models\EmployeeResignationReason;
use App\Models\ExitClearanceForm;
use App\Models\ResortDepartment;
use App\Models\ResortPosition;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Auth;
use Config;
use DB;

class DashboardController extends Controller
{
    public $resort;
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
    }

    public function HR_Dashobard()
    {
        $resort = $this->resort;
        $page_title ='People Dashboard';
        $resort_id = $this->resort->resort_id;
        $resort_divisions = ResortDivision::where('resort_id',$resort_id)->where('status','active')->get();
        $total_active_employees = Employee::where('resort_id',$resort_id)->whereIn('status',['Active','Resigned'])->count();
        $total_inactive_employees = Employee::where('resort_id',$resort_id)->whereIn('status',['Inactive','Terminated','Suspended','On Leave'])->count();
        $total_new_hired = Employee::where('resort_id',$resort_id)->whereIn('probation_status',['Active','Extended'])->count();
        $expected_employees = 0;
        $male_emp = ResortAdmin::with('EmployeeDetails')->where('resort_id', $resort_id)->where('gender','male')->where('status', 'Active')->where('is_employee', 1)->count();
        $female_emp = ResortAdmin::with('EmployeeDetails')->where('resort_id', $resort_id)->where('gender','female')->where('status', 'Active')->where('is_employee', 1)->count();
        $localEmployees = Employee::where('nationality', 'Maldivian')->where('resort_id',$resort_id)->count();
        $expatEmployees = Employee::where('nationality', '!=', 'Maldivian')->where('resort_id',$resort_id)->count();
        $announcements = Announcement::where('resort_id',$this->resort->resort_id)->orderBy('id','desc')->limit(5)->get();
        $totalPublished = Announcement::where('resort_id', $this->resort->resort_id)

        ->where('status', 'Published')
        ->count();
        $resortId = $this->resort->resort_id;
        // Count by category for published announcements
        $categoryCounts = AnnouncementCategory::withCount(['announcement' => function ($query) use ($resortId) {
            $query->where('status', 'Published')->where('resort_id', $resortId);
        }])->get();
        // dd($categoryCounts);

         $employeeInfoUpdateRequest = EmployeeInfoUpdateRequest::where('resort_id',$this->resort->resort_id)->with([
               'employee.resortAdmin',
               'department',
               'position'
          ])->where('status','Pending')->wherehas('employee.resortAdmin')->latest()->limit(5)->get();

        $probationalEmployees = Employee::where('resort_id',$this->resort->resort_id)->where('employment_type','Probationary')->count();
        $activeProbationCount = Employee::where('resort_id',$this->resort->resort_id)->where('probation_status', 'Active')->count();
        $failedProbationCount = Employee::where('resort_id',$this->resort->resort_id)->where('probation_status', 'Failed')->count();
        $completedProbationCount = Employee::where('resort_id',$this->resort->resort_id)->where('probation_status', 'Completed')->count();
        $total_promotions = EmployeePromotion::where('resort_id',$resortId)->count();

        $recent_promotions = EmployeePromotion::with(
            ['employee.position',
            'employee.department',
            'employee.resortAdmin',
            'currentPosition', 
            'newPosition',
            'approvals'
            ]
        )->where('resort_id',$resortId)->orderBy('id','desc')->limit(5)->get();
        $average_salary_increase = EmployeePromotion::whereNotNull('current_salary')
                                ->whereNotNull('new_salary')
                                ->where('resort_id',$resortId)
                                ->get()
                                ->map(function ($promo) {
                                    if ($promo->current_salary == 0) return 0; // Avoid division by zero
                                    return (($promo->new_salary - $promo->current_salary) / $promo->current_salary) * 100;
                                })->avg();

        $average_salary_increase = round($average_salary_increase, 2); 
    
        $advanceSalary = PayrollAdvance::where('resort_id',$this->resort->resort_id)->get();
        $advanceSalaryIds = $advanceSalary->pluck('id')->toArray();
        $guarantorCount = PayrollAdvanceGuarantor::whereIn('payroll_advance_id',$advanceSalaryIds)->where('status','Pending')->count();

        $advanceSalaryRescheduleAmount = PayrollAdvance::where('resort_id',$this->resort->resort_id)->where('hr_status','Approved')->whereHas('payrollRecoverySchedule')->sum('request_amount');

        $totalLoanRequests = PayrollAdvance::where('resort_id',$this->resort->resort_id)->where('request_type','Loan')->count();
        $totalAdvanceRequests = PayrollAdvance::where('resort_id',$this->resort->resort_id)->where('request_type','Salary Advance')->count();

        $totalSalaryIncrementShortListedEmp = PeopleSalaryIncrement::where('resort_id', $this->resort->resort_id)->groupBy('employee_id')->latest()->get();

        $totalNewsalary = $totalSalaryIncrementShortListedEmp->sum('new_salary');
        
        $SLE_basic_salary = $totalSalaryIncrementShortListedEmp->sum('previous_salary');
        $totalProposedIncrementAmount = abs($SLE_basic_salary - $totalNewsalary);

        $pandingIncrement = $totalSalaryIncrementShortListedEmp->where('status', 'Pending')->count();
        $approvedIncrement = $totalSalaryIncrementShortListedEmp->where('status', 'Approved')->count();
        $rejectedIncrement = $totalSalaryIncrementShortListedEmp->where('status', 'Rejected')->count();
        $onHoldIncrement = $totalSalaryIncrementShortListedEmp->where('status', 'Hold')->count();
        // Calculate average increment percentage
        
        $totalRepayment = PayrollAdvance::where('resort_id', $this->resort->resort_id)->get();

        $exitClearances = EmployeeResignation::where('resort_id', $this->resort->resort_id)->get();

        $totalExitInitiated = EmployeeResignation::where('resort_id', $this->resort->resort_id)->whereNotIn('status',['Appoved','Rejected','Withdarw'])->count();

        $ExitClearanceFormAssignments = ExitClearanceFormAssignment::where('resort_id', $this->resort->resort_id)
            ->get();
        
        $exit_interview_form = ExitClearanceForm::where('resort_id', $this->resort->resort_id)->where('type', 'exit_interview')->first();
        if ($exit_interview_form) {
            $exit_interviews= ExitClearanceFormAssignment::where('resort_id', $this->resort->resort_id)->where('assigned_to_type', 'employee')
                ->where('form_id', $exit_interview_form->id)
                ->get();   
        }else{
            $exit_interviews = collect();
        }
        $reasonLabels = [];
        $reasonCounts = [];
        if(isset($exitClearances) && $exitClearances->count()) {
            // Collect all unique reason IDs
            $reasonIds = $exitClearances->pluck('resignationReason')->unique()->filter(); 
            // Fetch all reason labels in one query 
            $reasonMap = EmployeeResignationReason::whereIn('id', $reasonIds)->pluck('reason', 'id');
           
            // Group exitClearances by reason id
            $grouped = $exitClearances->groupBy('resignationReason');
            foreach($grouped as $reasonId => $items) {
            $label = $reasonMap[$reasonId] ?? 'Unknown';
            $reasonLabels[] = $label;
            $reasonCounts[] = $items->count();
            }
        }

        $departments = \App\Models\ResortDepartment::where('resort_id', $resort->resort_id)->get();
        $assignmentsByDept = $ExitClearanceFormAssignments->groupBy('department_id');

        $attritionLabels = [];
        $attritionCounts = [];

        foreach ($departments as $department) {
            $count = $assignmentsByDept->get($department->id, collect())->count();
            $attritionLabels[] = $department->name;
            $attritionCounts[] = $count;
        }

        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $months->push(Carbon::now()->subMonths($i)->format('M Y'));
        }

        $total_resignations = EmployeeResignation::where('resort_id', $this->resort->resort_id)
           ->count();
        $withdraw_resignations = EmployeeResignation::where('resort_id', $this->resort->resort_id)
           ->where('status','Withdraw')->count();

        $resignationCounts = \App\Models\EmployeeResignation::select(
                DB::raw("DATE_FORMAT(resignation_date, '%b %Y') as month"),
                DB::raw("COUNT(*) as count")
            )
            ->where('resignation_date', '>=', Carbon::now()->subMonths(5)->startOfMonth())
            ->groupBy('month')
            ->orderByRaw("MIN(resignation_date)")
            ->pluck('count', 'month');

        $lineLabels = $months->toArray();
        $lineData = [];
        foreach ($lineLabels as $m) {
            $lineData[] = $resignationCounts[$m] ?? 0;
        }
        $averageIncrementPercentage = $SLE_basic_salary > 0 
            ? round((($totalProposedIncrementAmount / $SLE_basic_salary) * 100), 2) 
            : 0;

        return view('resorts.people.employee.hrdashboard',compact('page_title','resort','totalExitInitiated','average_salary_increase','total_active_employees','total_inactive_employees','total_new_hired','expected_employees','male_emp','female_emp','resort_divisions','localEmployees','expatEmployees','announcements','totalPublished','categoryCounts','probationalEmployees','activeProbationCount','failedProbationCount','completedProbationCount','total_promotions','recent_promotions','employeeInfoUpdateRequest','advanceSalary','guarantorCount','advanceSalaryRescheduleAmount','totalLoanRequests','totalAdvanceRequests','totalSalaryIncrementShortListedEmp','SLE_basic_salary','totalProposedIncrementAmount','averageIncrementPercentage','pandingIncrement','approvedIncrement','rejectedIncrement','onHoldIncrement','totalRepayment','exitClearances','ExitClearanceFormAssignments','exit_interviews','reasonLabels','reasonCounts','attritionLabels','attritionCounts','exit_interview_form','lineData','lineLabels','departments','total_resignations','withdraw_resignations','resignationCounts'));
    }

    public function admin_dashboard()
    {
        $resort = $this->resort;
        $page_title ='People Dashboard';
        $resort_id = $this->resort->resort_id;
        $resort_divisions = ResortDivision::where('resort_id',$resort_id)->where('status','active')->get();
        $total_active_employees = Employee::where('resort_id',$resort_id)->whereIn('status',['Active','Resigned'])->count();
        $total_inactive_employees = Employee::where('resort_id',$resort_id)->whereIn('status',['Inactive','Terminated','Suspended','On Leave'])->count();
        $total_new_hired = Employee::where('resort_id',$resort_id)->whereIn('probation_status',['Active','Extended'])->count();
        $expected_employees = 0;
        $male_emp = ResortAdmin::with('EmployeeDetails')->where('resort_id', $resort_id)->where('gender','male')->count();
        $female_emp = ResortAdmin::with('EmployeeDetails')->where('resort_id', $resort_id)->where('gender','female')->count();
        $localEmployees = Employee::where('nationality', 'Maldivian')->where('resort_id',$resort_id)->count();
        $expatEmployees = Employee::where('nationality', '!=', 'Maldivian')->where('resort_id',$resort_id)->count();
        $announcements = Announcement::where('resort_id',$this->resort->resort_id)->orderBy('id','desc')->limit(5)->get();
        $totalPublished = Announcement::where('resort_id', $this->resort->resort_id)

        ->where('status', 'Published')
        ->count();
        $resortId = $this->resort->resort_id;
        // Count by category for published announcements
        $categoryCounts = AnnouncementCategory::withCount(['announcement' => function ($query) use ($resortId) {
            $query->where('status', 'Published')->where('resort_id', $resortId);
        }])->get();
        // dd($categoryCounts);

         $employeeInfoUpdateRequest = EmployeeInfoUpdateRequest::where('resort_id',$this->resort->resort_id)->with([
               'employee.resortAdmin',
               'department',
               'position'
          ])->where('status','Pending')->wherehas('employee.resortAdmin')->latest()->limit(5)->get();

        $probationalEmployees = Employee::where('resort_id',$this->resort->resort_id)->where('employment_type','Probationary')->count();
        $activeProbationCount = Employee::where('resort_id',$this->resort->resort_id)->where('probation_status', 'Active')->count();
        $failedProbationCount = Employee::where('resort_id',$this->resort->resort_id)->where('probation_status', 'Failed')->count();
        $completedProbationCount = Employee::where('resort_id',$this->resort->resort_id)->where('probation_status', 'Completed')->count();
        $total_promotions = EmployeePromotion::where('resort_id',$resortId)->count();

        $recent_promotions = EmployeePromotion::with(
            ['employee.position',
            'employee.department',
            'employee.resortAdmin',
            'currentPosition', 
            'newPosition',
            'approvals'
            ]
        )->where('resort_id',$resortId)->orderBy('id','desc')->limit(5)->get();
        $average_salary_increase = EmployeePromotion::whereNotNull('current_salary')
                                ->whereNotNull('new_salary')
                                ->where('resort_id',$resortId)
                                ->get()
                                ->map(function ($promo) {
                                    if ($promo->current_salary == 0) return 0; // Avoid division by zero
                                    return (($promo->new_salary - $promo->current_salary) / $promo->current_salary) * 100;
                                })->avg();

        $average_salary_increase = round($average_salary_increase, 2); 
    
        $advanceSalary = PayrollAdvance::where('resort_id',$this->resort->resort_id)->get();
        $advanceSalaryIds = $advanceSalary->pluck('id')->toArray();
        $guarantorCount = PayrollAdvanceGuarantor::whereIn('payroll_advance_id',$advanceSalaryIds)->where('status','Pending')->count();

        $advanceSalaryRescheduleAmount = PayrollAdvance::where('resort_id',$this->resort->resort_id)->where('hr_status','Approved')->whereHas('payrollRecoverySchedule')->sum('request_amount');

        $totalLoanRequests = PayrollAdvance::where('resort_id',$this->resort->resort_id)->where('request_type','Loan')->count();
        $totalAdvanceRequests = PayrollAdvance::where('resort_id',$this->resort->resort_id)->where('request_type','Salary Advance')->count();

        $totalSalaryIncrementShortListedEmp = PeopleSalaryIncrement::where('resort_id', $this->resort->resort_id)->groupBy('employee_id')->latest()->get();

        $totalNewsalary = $totalSalaryIncrementShortListedEmp->sum('new_salary');
        
        $SLE_basic_salary = $totalSalaryIncrementShortListedEmp->sum('previous_salary');
        $totalProposedIncrementAmount = abs($SLE_basic_salary - $totalNewsalary);

        $pandingIncrement = $totalSalaryIncrementShortListedEmp->where('status', 'Pending')->count();
        $approvedIncrement = $totalSalaryIncrementShortListedEmp->where('status', 'Approved')->count();
        $rejectedIncrement = $totalSalaryIncrementShortListedEmp->where('status', 'Rejected')->count();
        $onHoldIncrement = $totalSalaryIncrementShortListedEmp->where('status', 'Hold')->count();
        // Calculate average increment percentage
        
        $totalRepayment = PayrollAdvance::where('resort_id', $this->resort->resort_id)->get();

        $exitClearances = EmployeeResignation::where('resort_id', $this->resort->resort_id)->get();

        $totalExitInitiated = EmployeeResignation::where('resort_id', $this->resort->resort_id)->whereNotIn('status',['Appoved','Rejected','Withdarw'])->count();

        $ExitClearanceFormAssignments = ExitClearanceFormAssignment::where('resort_id', $this->resort->resort_id)
            ->get();
        
        $exit_interview_form = ExitClearanceForm::where('resort_id', $this->resort->resort_id)->where('type', 'exit_interview')->first();
        if ($exit_interview_form) {
            $exit_interviews= ExitClearanceFormAssignment::where('resort_id', $this->resort->resort_id)->where('assigned_to_type', 'employee')
                ->where('form_id', $exit_interview_form->id)
                ->get();   
        }else{
            $exit_interviews = collect();
        }
        $reasonLabels = [];
        $reasonCounts = [];
        if(isset($exitClearances) && $exitClearances->count()) {
            // Collect all unique reason IDs
            $reasonIds = $exitClearances->pluck('resignationReason')->unique()->filter(); 
            // Fetch all reason labels in one query 
            $reasonMap = EmployeeResignationReason::whereIn('id', $reasonIds)->pluck('reason', 'id');
           
            // Group exitClearances by reason id
            $grouped = $exitClearances->groupBy('resignationReason');
            foreach($grouped as $reasonId => $items) {
            $label = $reasonMap[$reasonId] ?? 'Unknown';
            $reasonLabels[] = $label;
            $reasonCounts[] = $items->count();
            }
        }

        $departments = \App\Models\ResortDepartment::where('resort_id', $resort->resort_id)->get();
        $assignmentsByDept = $ExitClearanceFormAssignments->groupBy('department_id');

        $attritionLabels = [];
        $attritionCounts = [];

        foreach ($departments as $department) {
            $count = $assignmentsByDept->get($department->id, collect())->count();
            $attritionLabels[] = $department->name;
            $attritionCounts[] = $count;
        }

        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $months->push(Carbon::now()->subMonths($i)->format('M Y'));
        }

        $total_resignations = EmployeeResignation::where('resort_id', $this->resort->resort_id)
           ->count();
        $withdraw_resignations = EmployeeResignation::where('resort_id', $this->resort->resort_id)
           ->where('status','Withdraw')->count();

        $resignationCounts = \App\Models\EmployeeResignation::select(
                DB::raw("DATE_FORMAT(resignation_date, '%b %Y') as month"),
                DB::raw("COUNT(*) as count")
            )
            ->where('resignation_date', '>=', Carbon::now()->subMonths(5)->startOfMonth())
            ->groupBy('month')
            ->orderByRaw("MIN(resignation_date)")
            ->pluck('count', 'month');

        $lineLabels = $months->toArray();
        $lineData = [];
        foreach ($lineLabels as $m) {
            $lineData[] = $resignationCounts[$m] ?? 0;
        }
        $averageIncrementPercentage = $SLE_basic_salary > 0 
            ? round((($totalProposedIncrementAmount / $SLE_basic_salary) * 100), 2) 
            : 0;

        return view('resorts.people.employee.admindashboard',compact('page_title','resort','totalExitInitiated','average_salary_increase','total_active_employees','total_inactive_employees','total_new_hired','expected_employees','male_emp','female_emp','resort_divisions','localEmployees','expatEmployees','announcements','totalPublished','categoryCounts','probationalEmployees','activeProbationCount','failedProbationCount','completedProbationCount','total_promotions','recent_promotions','employeeInfoUpdateRequest','advanceSalary','guarantorCount','advanceSalaryRescheduleAmount','totalLoanRequests','totalAdvanceRequests','totalSalaryIncrementShortListedEmp','SLE_basic_salary','totalProposedIncrementAmount','averageIncrementPercentage','pandingIncrement','approvedIncrement','rejectedIncrement','onHoldIncrement','totalRepayment','exitClearances','ExitClearanceFormAssignments','exit_interviews','reasonLabels','reasonCounts','attritionLabels','attritionCounts','exit_interview_form','lineData','lineLabels','departments','total_resignations','withdraw_resignations','resignationCounts'));
    }


    public function getDepartmentCounts($divisionId = null)
    {
        $query = DB::table('resort_departments')
            ->join('employees', 'resort_departments.id', '=', 'employees.Dept_id')
            ->where('resort_departments.resort_id',$this->resort->resort_id)
            ->select('resort_departments.name as department', DB::raw('count(employees.id) as count'));

        if ($divisionId) {
            $query->where('resort_departments.division_id', $divisionId);
        }

        $data = $query->groupBy('resort_departments.id')->get();

        return response()->json($data);
    }

    public function getEmployeeStats()
    {
        $resort_id = $this->resort->resort_id;
        // Count local employees (Maldivian)
        $localEmployees = Employee::where('nationality', 'Maldivian')
                                ->where('resort_id', $resort_id)
                                ->count();

        // Count expatriate employees (non-Maldivian)
        $expatEmployees = Employee::where('nationality', '!=', 'Maldivian')
                                ->where('resort_id', $resort_id)
                                ->count();

        // Get the total number of employees in the resort
        $totalEmployees = $localEmployees + $expatEmployees;

        // Calculate percentages
        $localPercentage = $totalEmployees > 0 ? ($localEmployees / $totalEmployees) * 100 : 0;
        $expatPercentage = $totalEmployees > 0 ? ($expatEmployees / $totalEmployees) * 100 : 0;

        return response()->json([
            'localPercentage' => round($localPercentage, 2),
            'expatPercentage' => round($expatPercentage, 2)
        ]);
    }


    public function exitClearanceStaticstics(Request $request)
    {
      
        $departmentId = $request->department_id;
        $dateRange = $request->date_range;
        
        // Parse date range
        $dates = explode(' to ', $dateRange);
        $startDate = Carbon::parse($dates[0] ?? now()->format('Y-m-d'));
        $endDate = Carbon::parse($dates[1] ?? now()->format('Y-m-d'));

        // Base query for resignations
        $query = EmployeeResignation::query()
            ->where('resort_id', $this->resort->resort_id)
            ->whereBetween('resignation_date', [$startDate, $endDate]);
        
        // Apply department filter if selected
        if ($departmentId) {
            $query->whereHas('employee', function($q) use ($departmentId) {
                $q->where('Dept_id', $departmentId);
            });
        }
        
        $resignations = $query->get();
        $resignationCount = $resignations->count();

        // 1. Top Reasons for Leaving chart data
        $reasonLabels = [];
        $reasonCounts = [];

        // Group resignations by resignationReason ID and count
        $reasonsData = $resignations->groupBy('reason')
            ->map(function ($items) {
            return $items->count();
            })->sortDesc();

        // Get all reason records in one query
        $reasonNames = EmployeeResignationReason::whereIn('id', array_keys($reasonsData->toArray()))
            ->pluck('reason', 'id');

        foreach ($reasonsData as $reasonId => $count) {
            // Get reason name from the collected reasons, or fallback to 'Not Specified'
            $reasonName = $reasonNames[$reasonId];
            $reasonLabels[] = $reasonName;
            $reasonCounts[] = $count;
        }

        // 2. Turnover Trends (line chart) - monthly trend
        $lineLabels = [];
        $lineData = [];

        // Get the last 6 months from end date
        $period = CarbonPeriod::create($endDate->copy()->subMonths(5)->startOfMonth(), '1 month', $endDate->endOfMonth());

        foreach ($period as $date) {
            $month = $date->format('M Y');
            $lineLabels[] = $month;
            
            $monthCount = $resignations->filter(function($resignation) use ($date) {
                $resignationDate = Carbon::parse($resignation->resignation_date);
                return $resignationDate->month == $date->month && 
                    $resignationDate->year == $date->year;
            })->count();
            
            $lineData[] = $monthCount;
        }


        // 3. Attrition Rates (by position)
        $attritionLabels = [];
        $attritionCounts = [];

        // Get positions either filtered by department or all
        $positionQuery = ResortPosition::where('resort_id', $this->resort->resort_id)
            ->where('status', 'active');
        if ($departmentId) {
            $positionQuery->where('dept_id', $departmentId);
        }
        $positions = $positionQuery->get();

        foreach ($positions as $position) {
            // Count current employees in this position
            $currentEmployees = Employee::where('Position_id', $position->id)
                ->where('resort_id', $this->resort->resort_id)
                ->when($departmentId, function($query) use ($departmentId) {
                    return $query->where('Dept_id', $departmentId);
                })
                ->count();
                
            // Count resignations for this position within date range
            $resignedCount = $resignations->filter(function($resignation) use ($position) {
                return $resignation->employee && 
                    $resignation->employee->Position_id == $position->id;
            })->count();
            
            // Calculate attrition rate
            $totalEmployees = $currentEmployees + $resignedCount;
            $attritionRate = $totalEmployees > 0 
                ? round(($resignedCount / $totalEmployees) * 100, 2)
                : 0;

            // Only add to chart if there's data to show
            if ($resignedCount > 0 || !$departmentId) {
                $attritionLabels[] = $position->position_title; // Using position_title instead of name
                $attritionCounts[] = $attritionRate;
            }
        }

        // Prepare HTML for the cards
        $html = view('resorts.people.employee.exit-clearance-statistics', [
            'reasonLabels' => $reasonLabels,
            'reasonCounts' => $reasonCounts,
            'lineLabels' => $lineLabels,
            'lineData' => $lineData,
            'attritionLabels' => $attritionLabels,
            'attritionCounts' => $attritionCounts
        ])->render();

        return response()->json([
            'html' => $html,
            'resignation_count' => $resignationCount,
            'reasonLabels' => $reasonLabels,
            'reasonCounts' => $reasonCounts,
            'lineLabels' => $lineLabels,
            'lineData' => $lineData,
            'attritionLabels' => $attritionLabels,
            'attritionCounts' => $attritionCounts
        ]);
    }

}
