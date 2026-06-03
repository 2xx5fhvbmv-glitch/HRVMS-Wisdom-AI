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
        if(!$this->resort) return;
    }

    public function HR_Dashobard()
    {
        $resort = $this->resort;
        $page_title ='People Dashboard';
        $resort_id = $this->resort->resort_id;
        $resort_divisions = ResortDivision::where('resort_id',$resort_id)->where('status','active')->get();
        $scopedDeptIds = \App\Helpers\Common::getScopedDepartmentIds();
        $scopedEmpIds  = \App\Helpers\Common::getPerformanceScopedEmpIds();
        // Shared filter: only count people who are actually still working.
        // Apply to every workforce composition tile below so a transferred
        // / resigned / inactive employee isn't counted in Total/Gender/
        // Nationality/Location splits — the dashboard was over-counting
        // because these queries had no status guard.
        $today = \Carbon\Carbon::today()->toDateString();
        $activeScope = function ($q) use ($today) {
            $q->where('status', 'Active')
              ->where(function ($qq) use ($today) {
                  $qq->whereNull('last_working_day')
                     ->orWhereDate('last_working_day', '>', $today);
              });
        };
        $total_active_employees = Employee::where('resort_id',$resort_id)
            ->whereIn('status',['Active','Resigned'])
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))
            ->count();
        $total_inactive_employees = Employee::where('resort_id',$resort_id)
            ->whereIn('status',['Inactive','Terminated','Suspended','On Leave'])
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))
            ->count();
        // "Total New Hires" = employees joined in the last 90 days who are
        // in any live hire state. Previously this counted anyone with
        // probation_status IN ('Active','Extended') with no status or date
        // filter, which over-counted (inactive/terminated rows leak in, and
        // probation_status never expires on long-tenured records) — easy to
        // spot when New Hires > Total Active.
        // Status whitelist (instead of "= Active") so that fresh hires still
        // sitting in 'Onboarding' (waiting for HR to click Activate) or in
        // 'Probationary' are counted. Excluding them caused the dashboard to
        // skip newly-created employees until HR activated them — the exact
        // "hired but still shows 9 not 10" symptom.
        $newHireSince = \Carbon\Carbon::today()->subDays(90)->toDateString();
        $total_new_hired = Employee::where('resort_id',$resort_id)
            ->whereIn('status',['Active','Onboarding','Probationary'])
            ->whereNotNull('joining_date')
            ->whereDate('joining_date','>=',$newHireSince)
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))
            ->count();
        // "Expected Employee" = Talent Acquisition candidates who accepted
        // their contract but have not yet been onboarded as an employee.
        // Mirrors the TA dashboard's "Hired" query (status 'Contract
        // Accepted'); previously hardcoded to 0.
        $expected_employees = DB::table('applicant_wise_statuses as aws')
            ->join('applicant_form_data as afd', 'afd.id', '=', 'aws.Applicant_id')
            ->where('afd.resort_id', $resort_id)
            ->where('aws.status', 'Contract Accepted')
            ->distinct('aws.Applicant_id')
            ->count('aws.Applicant_id');
        // "Employee Type" gender split — count actual Employee records
        // (gender resolved via the resortAdmin relation), the same population
        // the local/expat cards use. It previously counted ResortAdmin rows
        // with is_employee=1, which over-counted the workforce because some
        // admin accounts are flagged is_employee without an employees row.
        $male_emp = Employee::where('resort_id', $resort_id)
            ->whereHas('resortAdmin', fn($q) => $q->where('gender', 'male'))
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))
            ->tap($activeScope)
            ->count();
        $female_emp = Employee::where('resort_id', $resort_id)
            ->whereHas('resortAdmin', fn($q) => $q->where('gender', 'female'))
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))
            ->tap($activeScope)
            ->count();
        $localEmployees = Employee::where('nationality', 'Maldivian')->where('resort_id',$resort_id)
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))
            ->tap($activeScope)
            ->count();
        $expatEmployees = Employee::where('nationality', '!=', 'Maldivian')->where('resort_id',$resort_id)
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))
            ->tap($activeScope)
            ->count();
        // No limit — the dashboard Announcements table paginates client-side
        // (DataTables, 4 per page), so it needs the full list.
        // whereHas('employee') — skip announcements whose employee record was
        // deleted, so the view never hits a null relation.
        // Location-Wise Employee chart — split the workforce by the
        // `location` column ('Resorts' vs 'Malé'). Replaces hardcoded [7,15].
        $resortLocationCount = Employee::where('resort_id', $resort_id)
            ->where('location', 'LIKE', 'Resort%')
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))
            ->tap($activeScope)
            ->count();
        $maleLocationCount = Employee::where('resort_id', $resort_id)
            ->where('location', 'LIKE', 'Mal%')
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))
            ->tap($activeScope)
            ->count();
        $announcements = Announcement::where('resort_id',$this->resort->resort_id)->whereHas('employee')->orderBy('id','desc')->get();
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

        // "Total Employees On Probation" — only employees whose probation is
        // still running. probation_status defaults to 'Active' on creation and
        // is rarely maintained (every employee — even 2010 joiners — shows
        // 'Active'), so a status-only count wrongly returned the whole
        // workforce. An employee counts as on probation only when:
        //   probation_status IN (Active, Extended)
        //   AND the probation window has not ended — window end is
        //   probation_end_date, or joining_date + 3 months when it is unset.
        $probationToday  = Carbon::today();
        $probationCutoff = $probationToday->copy()->subMonths(3)->toDateString();
        // Effective probation-end date: the explicit column, or joining_date + 3 months.
        $probationWindowSql = 'COALESCE(probation_end_date, DATE_ADD(joining_date, INTERVAL 3 MONTH))';

        $probationalEmployees = Employee::where('resort_id', $resort_id)
            ->whereIn('probation_status', ['Active', 'Extended'])
            ->whereRaw("$probationWindowSql >= ?", [$probationToday->toDateString()])
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))->count();
        $activeProbationCount = Employee::where('resort_id', $resort_id)
            ->where('probation_status', 'Active')
            ->whereRaw("$probationWindowSql >= ?", [$probationToday->toDateString()])
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))->count();
        // Failed / Confirmed are probation outcomes — scoped to employees who
        // joined within the last 3 months so stale records (status never
        // cleared) don't inflate the breakdown.
        $failedProbationCount = Employee::where('resort_id', $resort_id)
            ->where('probation_status', 'Failed')
            ->whereDate('joining_date', '>=', $probationCutoff)
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))->count();
        // The probation_status "passed" value is 'Confirmed' (no 'Completed').
        $completedProbationCount = Employee::where('resort_id', $resort_id)
            ->where('probation_status', 'Confirmed')
            ->whereDate('joining_date', '>=', $probationCutoff)
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))->count();

        // Probation periods ending within the next 7 / 15 / 30 days, using the
        // same effective window-end expression.
        $probationEndingQuery = function ($days) use ($resort_id, $scopedDeptIds, $probationToday, $probationWindowSql) {
            return Employee::where('resort_id', $resort_id)
                ->whereIn('probation_status', ['Active', 'Extended'])
                ->whereRaw("$probationWindowSql BETWEEN ? AND ?", [
                    $probationToday->toDateString(),
                    $probationToday->copy()->addDays($days)->toDateString(),
                ])
                ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))
                ->count();
        };
        $count30 = $probationEndingQuery(30);
        $count15 = $probationEndingQuery(15);
        $count7  = $probationEndingQuery(7);

        // Training Completion Rate — % of the resort's probationary L&D
        // programs completed by the employees currently on probation. A
        // program counts as completed for an employee when they have a
        // training_attendance row with status 'Present' on a training_schedule
        // backed by that probationary program (same definition the Probation
        // list's Onboarding Training column uses).
        $probationEmpIds = Employee::where('resort_id', $resort_id)
            ->whereIn('probation_status', ['Active', 'Extended'])
            ->whereRaw("$probationWindowSql >= ?", [$probationToday->toDateString()])
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))
            ->pluck('id');
        $probationaryProgramIds = \App\Models\ProbationaryLearningProgram::where('resort_id', $resort_id)
            ->pluck('program_id');
        $trainingExpected  = $probationEmpIds->count() * $probationaryProgramIds->count();
        $trainingCompleted = 0;
        if ($trainingExpected > 0) {
            $trainingCompleted = DB::table('training_attendance as ta')
                ->join('training_schedules as ts', 'ts.id', '=', 'ta.training_schedule_id')
                ->where('ts.resort_id', $resort_id)
                ->whereIn('ts.training_id', $probationaryProgramIds)
                ->whereIn('ta.employee_id', $probationEmpIds)
                ->where('ta.status', 'Present')
                ->distinct()
                ->count(DB::raw("CONCAT(ta.employee_id, '-', ts.training_id)"));
        }
        $trainingCompletionRate = $trainingExpected > 0
            ? (int) round(($trainingCompleted / $trainingExpected) * 100)
            : 0;
        $total_promotions = EmployeePromotion::where('resort_id',$resortId)->count();

        $recent_promotions = EmployeePromotion::with(
            ['employee.position',
            'employee.department',
            'employee.resortAdmin',
            'currentPosition', 
            'newPosition',
            'approvals'
            ]
        )->whereHas('employee')->where('resort_id',$resortId)->orderBy('id','desc')->limit(5)->get();
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

        // "Exits initiated" = resignations still in progress. The status
        // values were misspelled ('Appoved'/'Withdarw'), so Approved and
        // Withdrawn resignations were never excluded and inflated the count.
        $totalExitInitiated = EmployeeResignation::where('resort_id', $this->resort->resort_id)->whereNotIn('status',['Approved','Rejected','Withdraw'])->count();

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
            // Resignation reason is stored in the `reason` column (FK to
            // employee_resignation_reasons) — `resignationReason` is not a
            // real attribute and silently yielded all-null before.
            $reasonIds = $exitClearances->pluck('reason')->unique()->filter();
            // Fetch all reason labels in one query 
            $reasonMap = EmployeeResignationReason::whereIn('id', $reasonIds)->pluck('reason', 'id');
           
            // Group exitClearances by reason id
            $grouped = $exitClearances->groupBy('reason');
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

        // ── Approvals aggregate — pending / approved / held / rejected
        //    across every People approval workflow. The card previously
        //    showed hardcoded 50 / 25 / 15 / 10. ──────────────────────────
        $approvalPending = $approvalApproved = $approvalHeld = $approvalRejected = 0;

        // Single-`status` workflows: promotion, transfer, resignation,
        // info-update (info-update has no "On Hold" state — harmlessly 0).
        $statusModels = [
            \App\Models\EmployeePromotion::class,
            \App\Models\EmployeeTransfer::class,
            \App\Models\EmployeeResignation::class,
            \App\Models\EmployeeInfoUpdateRequest::class,
        ];
        foreach ($statusModels as $statusModel) {
            $approvalPending  += $statusModel::where('resort_id', $resort_id)->where('status', 'Pending')->count();
            $approvalApproved += $statusModel::where('resort_id', $resort_id)->where('status', 'Approved')->count();
            $approvalHeld     += $statusModel::where('resort_id', $resort_id)->where('status', 'On Hold')->count();
            $approvalRejected += $statusModel::where('resort_id', $resort_id)->where('status', 'Rejected')->count();
        }

        // Salary advance/loan has a 3-stage approval (HR → Finance → GM);
        // collapse the three columns into one overall outcome per request.
        $advanceRequests = PayrollAdvance::where('resort_id', $resort_id)
            ->get(['hr_status', 'finance_status', 'gm_status']);
        foreach ($advanceRequests as $adv) {
            $stages = [$adv->hr_status, $adv->finance_status, $adv->gm_status];
            if (in_array('Rejected', $stages, true)) {
                $approvalRejected++;
            } elseif (in_array('Hold', $stages, true)) {
                $approvalHeld++;
            } elseif ($adv->gm_status === 'Approved') {
                $approvalApproved++;
            } else {
                $approvalPending++;
            }
        }

        // Oldest still-pending request across all workflows → "x days ago".
        $oldestPendingDates = array_filter([
            \App\Models\EmployeePromotion::where('resort_id', $resort_id)->where('status', 'Pending')->min('created_at'),
            \App\Models\EmployeeTransfer::where('resort_id', $resort_id)->where('status', 'Pending')->min('created_at'),
            \App\Models\EmployeeResignation::where('resort_id', $resort_id)->where('status', 'Pending')->min('created_at'),
            \App\Models\EmployeeInfoUpdateRequest::where('resort_id', $resort_id)->where('status', 'Pending')->min('created_at'),
            PayrollAdvance::where('resort_id', $resort_id)->where('hr_status', 'Pending')->min('created_at'),
        ]);
        $oldestPendingApproval = !empty($oldestPendingDates)
            ? Carbon::parse(min($oldestPendingDates))->diffForHumans()
            : 'N/A';

        // === Liability Tracker card ===
        $liability = $this->buildLiabilityTracker($resort_id);
        $liabilityYear              = $liability['year'];
        $liabilityEstimated         = $liability['estimated'];
        $liabilityActualPaid        = $liability['actual_paid'];
        $liabilityPaidPercent       = $liability['paid_percent'];
        $liabilityMonthlyTrend      = $liability['monthly_trend'];
        $liabilityCostComparison    = $liability['cost_comparison'];

        return view('resorts.people.employee.hrdashboard',compact('page_title','resort','totalExitInitiated','average_salary_increase','total_active_employees','total_inactive_employees','total_new_hired','expected_employees','male_emp','female_emp','resort_divisions','localEmployees','expatEmployees','announcements','totalPublished','categoryCounts','probationalEmployees','activeProbationCount','failedProbationCount','completedProbationCount','total_promotions','recent_promotions','employeeInfoUpdateRequest','advanceSalary','guarantorCount','advanceSalaryRescheduleAmount','totalLoanRequests','totalAdvanceRequests','totalSalaryIncrementShortListedEmp','SLE_basic_salary','totalProposedIncrementAmount','averageIncrementPercentage','pandingIncrement','approvedIncrement','rejectedIncrement','onHoldIncrement','totalRepayment','exitClearances','ExitClearanceFormAssignments','exit_interviews','reasonLabels','reasonCounts','attritionLabels','attritionCounts','exit_interview_form','lineData','lineLabels','departments','total_resignations','withdraw_resignations','resignationCounts','count30','count15','count7','approvalPending','approvalApproved','approvalHeld','approvalRejected','oldestPendingApproval','liabilityYear','liabilityEstimated','liabilityActualPaid','liabilityPaidPercent','liabilityMonthlyTrend','liabilityCostComparison','resortLocationCount','maleLocationCount','trainingCompletionRate'));
    }

    public function admin_dashboard()
    {
        $resort = $this->resort;
        $page_title ='People Dashboard';
        $resort_id = $this->resort->resort_id;
        $resort_divisions = ResortDivision::where('resort_id',$resort_id)->where('status','active')->get();
        $scopedDeptIds = \App\Helpers\Common::getScopedDepartmentIds();
        $scopedEmpIds  = \App\Helpers\Common::getPerformanceScopedEmpIds();
        // Shared "currently working" filter — same as the HR dashboard above.
        $today = \Carbon\Carbon::today()->toDateString();
        $activeScope = function ($q) use ($today) {
            $q->where('status', 'Active')
              ->where(function ($qq) use ($today) {
                  $qq->whereNull('last_working_day')
                     ->orWhereDate('last_working_day', '>', $today);
              });
        };
        $total_active_employees = Employee::where('resort_id',$resort_id)->whereIn('status',['Active','Resigned'])
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))->count();
        $total_inactive_employees = Employee::where('resort_id',$resort_id)->whereIn('status',['Inactive','Terminated','Suspended','On Leave'])
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))->count();
        // New Hires = Active + joined in last 90 days. See HR_Dashobard()
        // above for the bug history (probation_status filter over-counted).
        $newHireSince = \Carbon\Carbon::today()->subDays(90)->toDateString();
        $total_new_hired = Employee::where('resort_id',$resort_id)
            ->where('status','Active')
            ->whereNotNull('joining_date')
            ->whereDate('joining_date','>=',$newHireSince)
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))
            ->count();
        // "Expected Employee" = Talent Acquisition candidates who accepted
        // their contract but have not yet been onboarded as an employee.
        // Mirrors the TA dashboard's "Hired" query (status 'Contract
        // Accepted'); previously hardcoded to 0.
        $expected_employees = DB::table('applicant_wise_statuses as aws')
            ->join('applicant_form_data as afd', 'afd.id', '=', 'aws.Applicant_id')
            ->where('afd.resort_id', $resort_id)
            ->where('aws.status', 'Contract Accepted')
            ->distinct('aws.Applicant_id')
            ->count('aws.Applicant_id');
        // "Employee Type" gender split — count actual Employee records
        // (gender via the resortAdmin relation), matching the local/expat
        // cards. Previously counted ResortAdmin rows, which over-counted.
        $male_emp = Employee::where('resort_id', $resort_id)
            ->whereHas('resortAdmin', fn($q) => $q->where('gender', 'male'))
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))
            ->tap($activeScope)
            ->count();
        $female_emp = Employee::where('resort_id', $resort_id)
            ->whereHas('resortAdmin', fn($q) => $q->where('gender', 'female'))
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))
            ->tap($activeScope)
            ->count();
        $localEmployees = Employee::where('nationality', 'Maldivian')->where('resort_id',$resort_id)
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))->tap($activeScope)->count();
        $expatEmployees = Employee::where('nationality', '!=', 'Maldivian')->where('resort_id',$resort_id)
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))->tap($activeScope)->count();
        // No limit — the dashboard Announcements table paginates client-side
        // (DataTables, 4 per page), so it needs the full list.
        // whereHas('employee') — skip announcements whose employee record was
        // deleted, so the view never hits a null relation.
        // Location-Wise Employee chart — split the workforce by the
        // `location` column ('Resorts' vs 'Malé'). Replaces hardcoded [7,15].
        $resortLocationCount = Employee::where('resort_id', $resort_id)
            ->where('location', 'LIKE', 'Resort%')
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))
            ->tap($activeScope)
            ->count();
        $maleLocationCount = Employee::where('resort_id', $resort_id)
            ->where('location', 'LIKE', 'Mal%')
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))
            ->tap($activeScope)
            ->count();
        $announcements = Announcement::where('resort_id',$this->resort->resort_id)->whereHas('employee')->orderBy('id','desc')->get();
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

        // "Total Employees On Probation" — only employees whose probation is
        // still running. probation_status defaults to 'Active' on creation and
        // is rarely maintained (every employee — even 2010 joiners — shows
        // 'Active'), so a status-only count wrongly returned the whole
        // workforce. An employee counts as on probation only when:
        //   probation_status IN (Active, Extended)
        //   AND the probation window has not ended — window end is
        //   probation_end_date, or joining_date + 3 months when it is unset.
        $probationToday  = Carbon::today();
        $probationCutoff = $probationToday->copy()->subMonths(3)->toDateString();
        // Effective probation-end date: the explicit column, or joining_date + 3 months.
        $probationWindowSql = 'COALESCE(probation_end_date, DATE_ADD(joining_date, INTERVAL 3 MONTH))';

        $probationalEmployees = Employee::where('resort_id', $resort_id)
            ->whereIn('probation_status', ['Active', 'Extended'])
            ->whereRaw("$probationWindowSql >= ?", [$probationToday->toDateString()])
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))->count();
        $activeProbationCount = Employee::where('resort_id', $resort_id)
            ->where('probation_status', 'Active')
            ->whereRaw("$probationWindowSql >= ?", [$probationToday->toDateString()])
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))->count();
        // Failed / Confirmed are probation outcomes — scoped to employees who
        // joined within the last 3 months so stale records (status never
        // cleared) don't inflate the breakdown.
        $failedProbationCount = Employee::where('resort_id', $resort_id)
            ->where('probation_status', 'Failed')
            ->whereDate('joining_date', '>=', $probationCutoff)
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))->count();
        // The probation_status "passed" value is 'Confirmed' (no 'Completed').
        $completedProbationCount = Employee::where('resort_id', $resort_id)
            ->where('probation_status', 'Confirmed')
            ->whereDate('joining_date', '>=', $probationCutoff)
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))->count();

        // Probation periods ending within the next 7 / 15 / 30 days, using the
        // same effective window-end expression.
        $probationEndingQuery = function ($days) use ($resort_id, $scopedDeptIds, $probationToday, $probationWindowSql) {
            return Employee::where('resort_id', $resort_id)
                ->whereIn('probation_status', ['Active', 'Extended'])
                ->whereRaw("$probationWindowSql BETWEEN ? AND ?", [
                    $probationToday->toDateString(),
                    $probationToday->copy()->addDays($days)->toDateString(),
                ])
                ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))
                ->count();
        };
        $count30 = $probationEndingQuery(30);
        $count15 = $probationEndingQuery(15);
        $count7  = $probationEndingQuery(7);

        // Training Completion Rate — % of the resort's probationary L&D
        // programs completed by the employees currently on probation. A
        // program counts as completed for an employee when they have a
        // training_attendance row with status 'Present' on a training_schedule
        // backed by that probationary program (same definition the Probation
        // list's Onboarding Training column uses).
        $probationEmpIds = Employee::where('resort_id', $resort_id)
            ->whereIn('probation_status', ['Active', 'Extended'])
            ->whereRaw("$probationWindowSql >= ?", [$probationToday->toDateString()])
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))
            ->pluck('id');
        $probationaryProgramIds = \App\Models\ProbationaryLearningProgram::where('resort_id', $resort_id)
            ->pluck('program_id');
        $trainingExpected  = $probationEmpIds->count() * $probationaryProgramIds->count();
        $trainingCompleted = 0;
        if ($trainingExpected > 0) {
            $trainingCompleted = DB::table('training_attendance as ta')
                ->join('training_schedules as ts', 'ts.id', '=', 'ta.training_schedule_id')
                ->where('ts.resort_id', $resort_id)
                ->whereIn('ts.training_id', $probationaryProgramIds)
                ->whereIn('ta.employee_id', $probationEmpIds)
                ->where('ta.status', 'Present')
                ->distinct()
                ->count(DB::raw("CONCAT(ta.employee_id, '-', ts.training_id)"));
        }
        $trainingCompletionRate = $trainingExpected > 0
            ? (int) round(($trainingCompleted / $trainingExpected) * 100)
            : 0;
        $total_promotions = EmployeePromotion::where('resort_id',$resortId)->count();

        $recent_promotions = EmployeePromotion::with(
            ['employee.position',
            'employee.department',
            'employee.resortAdmin',
            'currentPosition', 
            'newPosition',
            'approvals'
            ]
        )->whereHas('employee')->where('resort_id',$resortId)->orderBy('id','desc')->limit(5)->get();
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

        // "Exits initiated" = resignations still in progress. The status
        // values were misspelled ('Appoved'/'Withdarw'), so Approved and
        // Withdrawn resignations were never excluded and inflated the count.
        $totalExitInitiated = EmployeeResignation::where('resort_id', $this->resort->resort_id)->whereNotIn('status',['Approved','Rejected','Withdraw'])->count();

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
            // Resignation reason is stored in the `reason` column (FK to
            // employee_resignation_reasons) — `resignationReason` is not a
            // real attribute and silently yielded all-null before.
            $reasonIds = $exitClearances->pluck('reason')->unique()->filter();
            // Fetch all reason labels in one query 
            $reasonMap = EmployeeResignationReason::whereIn('id', $reasonIds)->pluck('reason', 'id');
           
            // Group exitClearances by reason id
            $grouped = $exitClearances->groupBy('reason');
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

        // ── Approvals aggregate — pending / approved / held / rejected
        //    across every People approval workflow. The card previously
        //    showed hardcoded 50 / 25 / 15 / 10. ──────────────────────────
        $approvalPending = $approvalApproved = $approvalHeld = $approvalRejected = 0;

        // Single-`status` workflows: promotion, transfer, resignation,
        // info-update (info-update has no "On Hold" state — harmlessly 0).
        $statusModels = [
            \App\Models\EmployeePromotion::class,
            \App\Models\EmployeeTransfer::class,
            \App\Models\EmployeeResignation::class,
            \App\Models\EmployeeInfoUpdateRequest::class,
        ];
        foreach ($statusModels as $statusModel) {
            $approvalPending  += $statusModel::where('resort_id', $resort_id)->where('status', 'Pending')->count();
            $approvalApproved += $statusModel::where('resort_id', $resort_id)->where('status', 'Approved')->count();
            $approvalHeld     += $statusModel::where('resort_id', $resort_id)->where('status', 'On Hold')->count();
            $approvalRejected += $statusModel::where('resort_id', $resort_id)->where('status', 'Rejected')->count();
        }

        // Salary advance/loan has a 3-stage approval (HR → Finance → GM);
        // collapse the three columns into one overall outcome per request.
        $advanceRequests = PayrollAdvance::where('resort_id', $resort_id)
            ->get(['hr_status', 'finance_status', 'gm_status']);
        foreach ($advanceRequests as $adv) {
            $stages = [$adv->hr_status, $adv->finance_status, $adv->gm_status];
            if (in_array('Rejected', $stages, true)) {
                $approvalRejected++;
            } elseif (in_array('Hold', $stages, true)) {
                $approvalHeld++;
            } elseif ($adv->gm_status === 'Approved') {
                $approvalApproved++;
            } else {
                $approvalPending++;
            }
        }

        // Oldest still-pending request across all workflows → "x days ago".
        $oldestPendingDates = array_filter([
            \App\Models\EmployeePromotion::where('resort_id', $resort_id)->where('status', 'Pending')->min('created_at'),
            \App\Models\EmployeeTransfer::where('resort_id', $resort_id)->where('status', 'Pending')->min('created_at'),
            \App\Models\EmployeeResignation::where('resort_id', $resort_id)->where('status', 'Pending')->min('created_at'),
            \App\Models\EmployeeInfoUpdateRequest::where('resort_id', $resort_id)->where('status', 'Pending')->min('created_at'),
            PayrollAdvance::where('resort_id', $resort_id)->where('hr_status', 'Pending')->min('created_at'),
        ]);
        $oldestPendingApproval = !empty($oldestPendingDates)
            ? Carbon::parse(min($oldestPendingDates))->diffForHumans()
            : 'N/A';

        // === Liability Tracker card ===
        $liability = $this->buildLiabilityTracker($resort_id);
        $liabilityYear              = $liability['year'];
        $liabilityEstimated         = $liability['estimated'];
        $liabilityActualPaid        = $liability['actual_paid'];
        $liabilityPaidPercent       = $liability['paid_percent'];
        $liabilityMonthlyTrend      = $liability['monthly_trend'];
        $liabilityCostComparison    = $liability['cost_comparison'];

        return view('resorts.people.employee.admindashboard',compact('page_title','resort','totalExitInitiated','average_salary_increase','total_active_employees','total_inactive_employees','total_new_hired','expected_employees','male_emp','female_emp','resort_divisions','localEmployees','expatEmployees','announcements','totalPublished','categoryCounts','probationalEmployees','activeProbationCount','failedProbationCount','completedProbationCount','total_promotions','recent_promotions','employeeInfoUpdateRequest','advanceSalary','guarantorCount','advanceSalaryRescheduleAmount','totalLoanRequests','totalAdvanceRequests','totalSalaryIncrementShortListedEmp','SLE_basic_salary','totalProposedIncrementAmount','averageIncrementPercentage','pandingIncrement','approvedIncrement','rejectedIncrement','onHoldIncrement','totalRepayment','exitClearances','ExitClearanceFormAssignments','exit_interviews','reasonLabels','reasonCounts','attritionLabels','attritionCounts','exit_interview_form','lineData','lineLabels','departments','total_resignations','withdraw_resignations','resignationCounts','count30','count15','count7','approvalPending','approvalApproved','approvalHeld','approvalRejected','oldestPendingApproval','liabilityYear','liabilityEstimated','liabilityActualPaid','liabilityPaidPercent','liabilityMonthlyTrend','liabilityCostComparison','resortLocationCount','maleLocationCount','trainingCompletionRate'));
    }

    /**
     * Build the Liability Tracker card data for a resort.
     *
     * Mirrors the logic of People\Liability\LiabilityEstimationController@index:
     *  - Estimated liability  = SUM(Total_Department_budget) of department budgets
     *                           tied to a manning budget for the current year.
     *  - Actual paid          = current-year payroll + work permit + medical +
     *                           insurance + quota slot + visa renewal payments.
     *  - Monthly trend        = remaining liability after each month's actual paid.
     *  - Cost comparison      = per cost-category estimated vs actual.
     *
     * NOTE: the Liability module has NO "manual adjustments" data source, so the
     * card's "Manual Adjustments" sub-section is handled with an empty state.
     */
    private function buildLiabilityTracker($resort_id)
    {
        $currentYear = Carbon::now()->year;

        // --- Estimated liability ---
        // Canonical calc — same source as the Initial Liability Estimation
        // page AND Budget → View Budget so all three headlines agree
        // (previously this used the denormalized Total_Department_budget +
        // keyword-matched template costs, producing a much smaller figure
        // than the Initial Liability page).
        $estimated = \App\Helpers\Common::computeYearlyBudgetTotal($resort_id, (int) $currentYear);

        // --- Monthly actual paid (per month, current year) ---
        $monthlyPayroll = DB::table('payroll')
            ->where('resort_id', $resort_id)
            ->whereYear('start_date', $currentYear)
            ->selectRaw('MONTH(start_date) as m, SUM(total_payroll) as t')
            ->groupBy(DB::raw('MONTH(start_date)'))->pluck('t', 'm')->toArray();

        $monthlyWorkPermit = DB::table('work_permits')
            ->where('resort_id', $resort_id)
            ->whereYear('Due_Date', $currentYear)
            ->where('Status', 'Paid')
            ->selectRaw('MONTH(Due_Date) as m, SUM(Amt) as t')
            ->groupBy(DB::raw('MONTH(Due_Date)'))->pluck('t', 'm')->toArray();

        $monthlyMedical = DB::table('work_permit_medical_renewals')
            ->where('resort_id', $resort_id)
            ->whereYear('end_date', $currentYear)
            ->selectRaw('MONTH(end_date) as m, SUM(Amt) as t')
            ->groupBy(DB::raw('MONTH(end_date)'))->pluck('t', 'm')->toArray();

        $monthlyInsurance = DB::table('employee_insurances')
            ->where('resort_id', $resort_id)
            ->whereYear('insurance_end_date', $currentYear)
            ->selectRaw('MONTH(insurance_end_date) as m, SUM(Premium) as t')
            ->groupBy(DB::raw('MONTH(insurance_end_date)'))->pluck('t', 'm')->toArray();

        $monthlyQuota = DB::table('quota_slot_renewals')
            ->where('resort_id', $resort_id)
            ->whereYear('Payment_Date', $currentYear)
            ->where('Status', 'Paid')
            ->selectRaw('MONTH(Payment_Date) as m, SUM(Amt) as t')
            ->groupBy(DB::raw('MONTH(Payment_Date)'))->pluck('t', 'm')->toArray();

        $monthlyVisa = DB::table('visa_renewals')
            ->where('resort_id', $resort_id)
            ->whereYear('end_date', $currentYear)
            ->selectRaw('MONTH(end_date) as m, SUM(Amt) as t')
            ->groupBy(DB::raw('MONTH(end_date)'))->pluck('t', 'm')->toArray();

        // --- Per-category budget estimates (resort_budget_costs particulars).
        //     Defined here so the headline Total Estimated can be recomputed
        //     as the SUM of every category estimate — matching the
        //     Estimation-vs-Actual breakdown shown to the user. Previously
        //     Total was the manning budget only (= the Payroll category),
        //     so the other 5 categories' estimates appeared in the breakdown
        //     but were excluded from the headline figure.
        // Per-category cost templates from resort_budget_costs — used ONLY
        // for the Estimation-vs-Actual breakdown below. $estimated already
        // holds the canonical headline figure from Common::computeYearlyBudgetTotal,
        // so we do NOT re-overwrite it here (doing so produced the old
        // sub-1k figure on the People Dashboard while Initial Liability
        // showed 37k+).
        $budgetByParticular = DB::table('resort_budget_costs')
            ->where('resort_id', $resort_id)
            ->select('particulars', DB::raw('SUM(amount) as total'))
            ->groupBy('particulars')
            ->pluck('total', 'particulars')->toArray();

        $budgetMatch = function (array $keywords) use ($budgetByParticular) {
            $sum = 0.0;
            foreach ($budgetByParticular as $particular => $amount) {
                foreach ($keywords as $kw) {
                    if (stripos($particular, $kw) !== false) {
                        $sum += (float) $amount;
                        break;
                    }
                }
            }
            return $sum;
        };

        // Payroll-row estimate for the per-category table — pulled from the
        // current-year manning department-budget sum (the same field the
        // legacy headline used). Kept as a per-row figure now that the
        // headline lives in Common::computeYearlyBudgetTotal.
        $manningBudget = (float) \App\Models\StoreManningResponseParent::whereHas('manningbudget', function ($q) use ($currentYear) {
                $q->where('year', $currentYear);
            })
            ->where('Resort_id', $resort_id)
            ->sum('Total_Department_budget');

        // --- Build monthly trend (remaining liability after each month) ---
        $remaining   = $estimated;
        $actualPaid  = 0.0;
        $monthlyTrend = [];
        for ($m = 1; $m <= 12; $m++) {
            $paid = (float) (($monthlyPayroll[$m] ?? 0)
                + ($monthlyWorkPermit[$m] ?? 0)
                + ($monthlyMedical[$m] ?? 0)
                + ($monthlyInsurance[$m] ?? 0)
                + ($monthlyQuota[$m] ?? 0)
                + ($monthlyVisa[$m] ?? 0));

            // Only list months that actually had a payment, to keep the card compact.
            if ($paid > 0) {
                $actualPaid += $paid;
                $remaining = max($remaining - $paid, 0);
                $monthlyTrend[] = [
                    'month'     => Carbon::create($currentYear, $m)->format('F'),
                    'paid'      => round($paid, 2),
                    'remaining' => round($remaining, 2),
                ];
            }
        }

        $paidPercent = $estimated > 0
            ? min(round(($actualPaid / $estimated) * 100), 100)
            : 0;

        // --- Estimation vs Actual per cost category ---
        // Estimated side: resort_budget_costs grouped by particulars.
        // Actual side: matched against the real payment tables above.
        $totalWorkPermit = array_sum($monthlyWorkPermit);
        $totalMedical    = array_sum($monthlyMedical);
        $totalInsurance  = array_sum($monthlyInsurance);
        $totalQuota      = array_sum($monthlyQuota);
        $totalVisa       = array_sum($monthlyVisa);
        $totalPayroll    = array_sum($monthlyPayroll);

        // $budgetByParticular / $budgetMatch were already defined above so the
        // headline Total could include every category. Reuse them here for the
        // Estimation-vs-Actual breakdown.
        $categories = [
            'Payroll'      => ['estimated' => $manningBudget,                     'actual' => $totalPayroll],
            'Work Permit'  => ['estimated' => $budgetMatch(['Work Permit']),      'actual' => $totalWorkPermit],
            'Medical'      => ['estimated' => $budgetMatch(['Medical']),          'actual' => $totalMedical],
            'Insurance'    => ['estimated' => $budgetMatch(['Insurance']),        'actual' => $totalInsurance],
            'Quota Slot'   => ['estimated' => $budgetMatch(['Quota']),            'actual' => $totalQuota],
            'Visa'         => ['estimated' => $budgetMatch(['Visa']),             'actual' => $totalVisa],
        ];

        $costComparison = [];
        foreach ($categories as $name => $data) {
            $est = round((float) $data['estimated'], 2);
            $act = round((float) $data['actual'], 2);
            if ($est == 0 && $act == 0) {
                continue; // skip categories with no data on either side
            }
            $costComparison[] = [
                'category'  => $name,
                'estimated' => $est,
                'actual'    => $act,
                'remaining' => round($est - $act, 2),
            ];
        }

        return [
            'year'            => $currentYear,
            'estimated'       => round($estimated, 2),
            'actual_paid'     => round($actualPaid, 2),
            'paid_percent'    => $paidPercent,
            'monthly_trend'   => $monthlyTrend,
            'cost_comparison' => $costComparison,
        ];
    }


    public function getDepartmentCounts($divisionId = null)
    {
        // Two earlier bugs in this query:
        //   1. Joined `employees` only on Dept_id, with no employee.resort_id
        //      filter — so any employee from another resort that happened to
        //      share a Dept_id (FK collision in a multi-resort install)
        //      counted toward the chart.
        //   2. Counted every status (Active / Inactive / Resigned / Terminated),
        //      so the "Distribution by department" inflated past current
        //      headcount.
        // Pin both join sides to the same resort and restrict to Active.
        // LEFT JOIN so departments with zero employees still appear with 0.
        $resortId = $this->resort->resort_id;
        $query = DB::table('resort_departments')
            ->leftJoin('employees', function ($join) use ($resortId) {
                $join->on('resort_departments.id', '=', 'employees.Dept_id')
                     ->where('employees.resort_id', '=', $resortId)
                     ->where('employees.status', '=', 'Active');
            })
            ->where('resort_departments.resort_id', $resortId)
            ->where('resort_departments.status', 'active')
            ->select('resort_departments.name as department', DB::raw('count(employees.id) as count'));

        if ($divisionId) {
            $query->where('resort_departments.division_id', $divisionId);
        }

        $data = $query->groupBy('resort_departments.id', 'resort_departments.name')->get();

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
        $dateRange    = trim((string) $request->date_range);

        // Parse the "YYYY-MM-DD to YYYY-MM-DD" range. When the filter is
        // cleared the value is empty — keep $startDate/$endDate null so the
        // stats cover ALL dates instead of collapsing to "today only".
        // (Previously Carbon::parse('') silently returned now(), and the
        // `?? now()` fallback never fired because '' is not null, so a
        // cleared filter wrongly showed just the current day.)
        $startDate = $endDate = null;
        if ($dateRange !== '' && strpos($dateRange, ' to ') !== false) {
            [$from, $to] = array_pad(explode(' to ', $dateRange), 2, null);
            if (!empty($from)) {
                $startDate = Carbon::parse($from)->startOfDay();
            }
            if (!empty($to)) {
                $endDate = Carbon::parse($to)->endOfDay();
            }
        }

        // Base query for resignations
        $query = EmployeeResignation::query()
            ->where('resort_id', $this->resort->resort_id)
            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                $q->whereBetween('resignation_date', [$startDate, $endDate]);
            });
        
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
            // Get reason name from the collected reasons, or fallback to
            // 'Not Specified' — a resignation can carry a stale/empty reason
            // id, which otherwise produced a blank chart label.
            $reasonName = $reasonNames[$reasonId] ?? 'Not Specified';
            $reasonLabels[] = $reasonName;
            $reasonCounts[] = $count;
        }

        // 2. Turnover Trends (line chart) - monthly trend
        $lineLabels = [];
        $lineData = [];

        // Turnover Trends spans the full calendar year — January to December
        // of the selected range's year (or the current year when no range).
        $trendYear = ($endDate ? $endDate->year : Carbon::now()->year);
        $period = CarbonPeriod::create(
            Carbon::create($trendYear, 1, 1)->startOfMonth(),
            '1 month',
            Carbon::create($trendYear, 12, 1)->endOfMonth()
        );

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


        // 3. Attrition Rates (by department)
        $attritionLabels = [];
        $attritionCounts = [];

        // Get departments either filtered by the selected one or all.
        $deptQuery = ResortDepartment::where('resort_id', $this->resort->resort_id)
            ->where('status', 'active');
        if ($departmentId) {
            $deptQuery->where('id', $departmentId);
        }
        $attritionDepartments = $deptQuery->get();

        foreach ($attritionDepartments as $department) {
            // Count current employees in this department.
            $currentEmployees = Employee::where('Dept_id', $department->id)
                ->where('resort_id', $this->resort->resort_id)
                ->count();

            // Count resignations belonging to this department within the range.
            $resignedCount = $resignations->filter(function($resignation) use ($department) {
                return $resignation->employee &&
                    $resignation->employee->Dept_id == $department->id;
            })->count();

            // Attrition rate = leavers / (current + leavers).
            $totalEmployees = $currentEmployees + $resignedCount;
            $attritionRate = $totalEmployees > 0
                ? round(($resignedCount / $totalEmployees) * 100, 2)
                : 0;

            // Only add to chart if there's data to show.
            if ($resignedCount > 0 || !$departmentId) {
                $attritionLabels[] = $department->name;
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
