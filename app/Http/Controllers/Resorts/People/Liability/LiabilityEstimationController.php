<?php
namespace App\Http\Controllers\Resorts\People\Liability;

use App\Http\Controllers\Controller;
use App\Helpers\Common;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\Events\ResortNotificationEvent;
use App\Models\Resort;
use App\Models\Employee;
use App\Models\resortAdmin;
use App\Models\ResortDepartment;
use App\Models\ResortSiteSettings;
use App\Models\StoreManningResponseParent;
use App\Models\Payroll;
use App\Models\PayrollReview;
use App\Models\PayrollReviewAllowances;
use Auth;
use Config;
use DB;
use Carbon\Carbon;

class LiabilityEstimationController extends Controller 
{
    public $resort;
    
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;
    }

    public function index()
    {
        $page_title = 'Initial Liability Estimation';
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;
        $totalVisa = $totalInsurance = $totalPermit = $totalMedical = $totalQuota = $totalChecked = 0;
        $totalInsuranceEmployee = $totalPermitEmployee = $TotalVisaEmployee=$totalMedicalEmployee = $totalQuotaEmployee = 0;
        $resortId = $this->resort->resort_id ?? null; // Optional if this is called from superadmin
       
        $resort_departments = ResortDepartment::where('resort_id', $resortId)
            ->where('status', 'active')
            ->get(); 
        $scopedDeptIds = \App\Helpers\Common::getScopedDepartmentIds();
        $employees = Employee::where('resort_id', $resortId)
            ->where('status', 'active')
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))
            ->get();
        
        // Total Estimated Liability — shared canonical calc in Common so the
        // Initial Liability headline AND the People Dashboard Liability
        // Tracker AND Budget → View Budget all agree.
        $estimated_liability = Common::computeYearlyBudgetTotal($resortId, $currentYear);
        // Cost-category breakdowns for the Estimation-vs-Actual table use the
        // same particulars-keyword approach as before (cost templates only).
        $budgetByParticular = DB::table('resort_budget_costs')
            ->where('resort_id', $resortId)
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

        // ✅ Current Liability from Payroll Reviews for the year
        $payrolls = Payroll::with('reviews')
            ->where('resort_id', $resortId)
            ->whereYear('start_date', $currentYear)
            ->get();
        // dd($payrolls);
        $totalVisa = $totalInsurance = $totalPermit = $totalMedical = $totalQuota = 0;
        $totalInsuranceEmployee = $totalPermitEmployee = $TotalVisaEmployee = $totalMedicalEmployee = $totalQuotaEmployee = 0;

        $employees = Employee::with([
            'resortAdmin', 'position', 'department',
            'VisaRenewal.VisaChild',
            'WorkPermitMedicalRenewal.WorkPermitMedicalRenewalChild',
            'WorkPermit',
            'EmployeeInsurance.InsuranceChild',
            'QuotaSlotRenewal'
        ])
        ->where("nationality", '!=', "Maldivian")
        ->where('status', 'Active')
        ->where('resort_id', $resortId)
        ->get()
        ->map(function ($employee) use (
            &$totalPermitEmployee, &$totalMedicalEmployee, &$totalQuotaEmployee,
            &$totalInsuranceEmployee, &$TotalVisaEmployee,
            &$totalVisa, &$totalInsurance, &$totalPermit, &$totalMedical, &$totalQuota,
            $currentYear
        ) {
            $hasAnyFlagData = false;

            // === VISA ===
            $visa = $employee->VisaRenewal;
            if ($visa && Carbon::parse($visa->end_date)->year == $currentYear) {
                $totalVisa += $visa->Amt;
                $TotalVisaEmployee++;
                $hasAnyFlagData = true;
            }

            // === INSURANCE ===
            $insurance = $employee->EmployeeInsurance()
                ->where('resort_id', $employee->resort_id)
                ->whereYear('insurance_end_date', $currentYear)
                ->orderBy('id', 'desc')
                ->first();

            if ($insurance) {
                $totalInsurance += $insurance->Premium;
                $totalInsuranceEmployee++;
                $hasAnyFlagData = true;
            }

            // === WORK PERMIT ===
            $currentWP = $employee->WorkPermit()
                ->where('Status', 'Paid')
                ->whereYear('Due_Date', $currentYear)
                ->orderByDesc('id')
                ->first();

            if ($currentWP) {
                $totalPermit += $currentWP->Amt;
                $totalPermitEmployee++;
                $hasAnyFlagData = true;
            }

            // === MEDICAL ===
            $med = $employee->WorkPermitMedicalRenewal;
            if ($med && Carbon::parse($med->end_date)->year == $currentYear) {
                $totalMedical += $med->Amt;
                $totalMedicalEmployee++;
                $hasAnyFlagData = true;
            }

            // === QUOTA SLOT ===
            $quotaEntries = $employee->QuotaSlotRenewal
                ->where('Status', 'Paid')
                ->filter(function ($item) use ($currentYear) {
                    return Carbon::parse($item->Expiry_Date)->year == $currentYear;
                });

            $quotaTotalAmount = $quotaEntries->sum('Amt');

            if ($quotaTotalAmount > 0) {
                $totalQuota += $quotaTotalAmount;
                $totalQuotaEmployee++;
                $hasAnyFlagData = true;
            }

            return $hasAnyFlagData ? $employee : null;
        })->filter();

        $payrollLiability = $payrolls->sum('total_payroll');

        $current_liability = $payrollLiability 
                        + $totalVisa 
                        + $totalInsurance 
                        + $totalPermit 
                        + $totalMedical 
                        + $totalQuota;

        $liability_reduction = $estimated_liability - $current_liability;
         // === Earnings ===
        $payrollReviews = DB::table('payroll_reviews')
            ->join('payroll', 'payroll_reviews.payroll_id', '=', 'payroll.id')
            ->where('payroll.resort_id', $resortId)
            ->whereYear('payroll.start_date', $currentYear)
            ->selectRaw('
                SUM(earned_salary) as salaries,
                SUM(earnings_overtime) as ota,
                SUM(earnings_allowance) as allowance,
                SUM(service_charge) as service_charge
            ')
            ->first();
        // === Allowance Breakdown (per type) ===
        $allowanceBreakdown = DB::table('payroll_review_allowances as pra')
            ->join('payroll_reviews as pr', 'pra.payroll_review_id', '=', 'pr.id')
            ->join('payroll as p', 'pr.payroll_id', '=', 'p.id')
            ->where('p.resort_id', $resortId)
            ->whereYear('p.start_date', $currentYear)
            ->select('pra.allowance_type', DB::raw('SUM(pra.amount) as total_amount'))
            ->groupBy('pra.allowance_type')
            ->pluck('total_amount', 'pra.allowance_type')
            ->toArray();       

        // Combine all values into a chart data array
        $chartData = [
            'Salaries'         => $payrollReviews->salaries ?? 0,
            'OTA'              => $payrollReviews->ota ?? 0,
            'Recruitment Fee'  => $recruitmentCosts->recruitment_fee ?? 0,
            'Work Permit'      => $totalPermit,
            'Quota Slot'       => $totalQuota,
            'Medical Permit'   => $totalMedical,
            'Insurance'        => $totalInsurance,
            'Service Charge'   => $payrollReviews->service_charge ?? 0,
        ];

        // Append each allowance type dynamically to the chart
        foreach ($allowanceBreakdown as $type => $amount) {
            $chartData["Allowance - " . ucfirst($type)] = $amount;
        }
        // dd($chartData);

       $monthlyLiability = DB::table('payroll')
        ->where('resort_id', $resortId)
        ->whereYear('start_date', $currentYear)
        ->select(
            DB::raw('MONTH(start_date) as month'),
            DB::raw('SUM(total_payroll) as total')
        )
        ->groupBy(DB::raw('MONTH(start_date)'))
        ->pluck('total', 'month') // returns [1 => 1234.00, 2 => 1523.00, ...]
        ->toArray();

        // Work Permit
        $monthlyWorkPermit = DB::table('work_permits')
            ->where('resort_id', $resortId)
            ->whereYear('Due_Date', $currentYear)
            ->where('Status', 'Paid')
            ->selectRaw('MONTH(Due_Date) as month, SUM(Amt) as total')
            ->groupBy(DB::raw('MONTH(Due_Date)'))
            ->pluck('total', 'month')->toArray();

        // Medical
        $monthlyMedical = DB::table('work_permit_medical_renewals')
            ->where('resort_id', $resortId)
            ->whereYear('end_date', $currentYear)
            ->selectRaw('MONTH(end_date) as month, SUM(Amt) as total')
            ->groupBy(DB::raw('MONTH(end_date)'))
            ->pluck('total', 'month')->toArray();

        // Insurance
        $monthlyInsurance = DB::table('employee_insurances')
            ->where('resort_id', $resortId)
            ->whereYear('insurance_end_date', $currentYear)
            ->selectRaw('MONTH(insurance_end_date) as month, SUM(Premium) as total')
            ->groupBy(DB::raw('MONTH(insurance_end_date)'))
            ->pluck('total', 'month')->toArray();

        // Quota Slot
        $monthlyQuota = DB::table('quota_slot_renewals')
            ->where('resort_id', $resortId)
            ->whereYear('Payment_Date', $currentYear)
            ->where('Status', 'Paid')
            ->selectRaw('MONTH(Payment_Date) as month, SUM(Amt) as total')
            ->groupBy(DB::raw('MONTH(Payment_Date)'))
            ->pluck('total', 'month')->toArray();

        // Visa
        $monthlyVisa = DB::table('visa_renewals')
            ->where('resort_id', $resortId)
            ->whereYear('end_date', $currentYear)
            ->selectRaw('MONTH(end_date) as month, SUM(Amt) as total')
            ->groupBy(DB::raw('MONTH(end_date)'))
            ->pluck('total', 'month')->toArray();

            // Step 3: Build Monthly Data with Reduction Logic
        $liabilityRemaining = $estimated_liability;
        $labels = [];
        $reductionData = [];

        for ($m = 1; $m <= 12; $m++) {
            $monthName = Carbon::create($currentYear, $m)->format('M Y');

            // Monthly actual paid
            $monthlyPaid = 
                ($monthlyLiability[$m] ?? 0) +
                ($monthlyWorkPermit[$m] ?? 0) +
                ($monthlyMedical[$m] ?? 0) +
                ($monthlyInsurance[$m] ?? 0) +
                ($monthlyQuota[$m] ?? 0) +
                ($monthlyVisa[$m] ?? 0);

                // dd($monthlyPaid, $monthlyLiability[$m] ?? 0, $monthlyWorkPermit[$m] ?? 0, $monthlyMedical[$m] ?? 0, $monthlyInsurance[$m] ?? 0, $monthlyQuota[$m] ?? 0, $monthlyVisa[$m] ?? 0);

            // Deduct from remaining liability
            $liabilityRemaining -= $monthlyPaid;
            $liabilityRemaining = max($liabilityRemaining, 0);

            $labels[] = $monthName;
            $reductionData[] = round($liabilityRemaining, 2);
        }

        // Allowance column set — DERIVED from the same resort_budget_costs
        // catalog that getLiabilityData() uses to build per-employee column
        // values. Anything classified as OT / Insurance / Recruitment falls
        // into its own dedicated column; everything else is treated as a
        // named allowance. Both the table <th> headers and the JS column
        // generator (`allowanceColumns` in index.blade.php) read $allowanceTypes,
        // so the labels MUST match the data keys produced server-side or
        // DataTables raises "Requested unknown parameter".
        $rawCosts = DB::table('resort_budget_costs')
            ->where('resort_id', $resortId)
            ->where('status', 'active')
            ->get(['particulars', 'cost_title']);
        $isOtCost          = fn($n) => stripos((string) $n, 'overtime') !== false || preg_match('/\bot\b/i', (string) $n);
        $isInsuranceCost   = fn($n) => stripos((string) $n, 'insurance') !== false;
        $isRecruitmentCost = fn($n) => stripos((string) $n, 'recruitment') !== false
            || stripos((string) $n, 'work permit') !== false
            || stripos((string) $n, 'work visa') !== false
            || stripos((string) $n, 'quota slot') !== false;
        $allowanceTypes = collect($rawCosts)
            ->map(fn($c) => $c->particulars ?: ($c->cost_title ?: 'Other'))
            ->reject(fn($n) => $isOtCost($n) || $isInsuranceCost($n) || $isRecruitmentCost($n))
            ->unique()
            ->values();

        // Pre-compute the Estimation vs Actual table rows on the server so the
        // view doesn't have to re-derive them (and so the `$budgetMatch`
        // closure stays controller-local).
        $estVsActualRows = [
            ['label' => 'Salaries',        'estimated' => $budgetMatch(['Payroll','Salary']),       'actual' => $chartData['Salaries']        ?? 0],
            ['label' => 'Overtime',        'estimated' => $budgetMatch(['Overtime','OT']),          'actual' => $chartData['OTA']             ?? 0],
            ['label' => 'Service Charge',  'estimated' => $budgetMatch(['Service Charge']),         'actual' => $chartData['Service Charge']  ?? 0],
            ['label' => 'Work Permit',     'estimated' => $budgetMatch(['Work Permit']),            'actual' => $chartData['Work Permit']     ?? 0],
            ['label' => 'Medical',         'estimated' => $budgetMatch(['Medical']),                'actual' => $chartData['Medical Permit']  ?? 0],
            ['label' => 'Insurance',       'estimated' => $budgetMatch(['Insurance']),              'actual' => $chartData['Insurance']       ?? 0],
            ['label' => 'Quota',           'estimated' => $budgetMatch(['Quota']),                  'actual' => $chartData['Quota Slot']      ?? 0],
            ['label' => 'Visa',            'estimated' => $budgetMatch(['Visa']),                   'actual' => array_sum((array) ($monthlyVisa ?? []))],
            ['label' => 'Recruitment Fee', 'estimated' => $budgetMatch(['Recruitment']),            'actual' => $chartData['Recruitment Fee'] ?? 0],
        ];
        foreach ($allowanceBreakdown as $type => $amount) {
            $estVsActualRows[] = [
                'label'     => 'Allowance - ' . ucfirst($type),
                'estimated' => $budgetMatch([$type]),
                'actual'    => $amount,
            ];
        }

        return view('resorts.people.liability.index', compact(
            'page_title',
            'resortId', 'current_liability',
            'resort_departments','employees','estimated_liability',
            'liability_reduction','chartData',
            'labels',
            'reductionData','allowanceTypes',
            'estVsActualRows'
        ));
    }

    public function addCost()
    {
        $page_title = 'Add Liability Cost';
        $resort_id = $this->resort->resort_id;  
        return view('resorts.people.liability.add-cost', compact(
            'page_title', 
            'resort_id', 
        ));     
    }

    public function getLiabilityData(Request $request)
    {
        $resortId = $this->resort->resort_id;
        $currentYear = now()->year;

        // ── Allowance / OT / Insurance / Recruitment column buckets ───────
        // The Employees Tab columns mirror what HR sees on Budget → View
        // Budget. Allowance types come from the BUDGET cost catalog
        // (resort_budget_costs) — that's the source view-budget renders
        // columns from. The per-row totals come from
        // resort_employee_budget_cost_configurations summed per cost across
        // all 12 months of the year. Each cost is classified by name into
        // one of: OT / Insurance / Recruitment / Allowance.
        // Pull the FULL cost row (amount/unit/frequency/details) so
        // Common::annualCostForEmployee() can compute the live fallback for
        // months without an explicit override — same rule view-budget uses.
        $resortCosts = DB::table('resort_budget_costs')
            ->where('resort_id', $resortId)
            ->where('status', 'active')
            ->get(['id', 'particulars', 'cost_title', 'amount', 'amount_unit', 'cost_type', 'frequency', 'details']);

        $classify = function ($name) {
            $n = strtolower($name ?? '');
            if (strpos($n, 'overtime') !== false || preg_match('/\bot\b/i', $n)) return 'ot';
            if (strpos($n, 'insurance') !== false)        return 'insurance';
            if (strpos($n, 'recruitment') !== false)      return 'recruitment';
            if (strpos($n, 'work permit') !== false)      return 'recruitment';
            if (strpos($n, 'work visa') !== false)        return 'recruitment';
            if (strpos($n, 'quota slot') !== false)       return 'recruitment';
            return 'allowance';
        };

        // Group COSTS (not just ids) by classification so the per-employee
        // sum function below can pass each row to annualCostForEmployee.
        $allowanceCostsByLabel = []; // ['Language Allowance' => [costObj, costObj], ...]
        $otCosts = $insuranceCosts = $recruitmentCosts = [];
        foreach ($resortCosts as $c) {
            $label = $c->particulars ?: ($c->cost_title ?: 'Other');
            switch ($classify($label)) {
                case 'ot':          $otCosts[]          = $c; break;
                case 'insurance':   $insuranceCosts[]   = $c; break;
                case 'recruitment': $recruitmentCosts[] = $c; break;
                default:
                    $allowanceCostsByLabel[$label][] = $c;
            }
        }
        $allowanceLabels = array_keys($allowanceCostsByLabel);

        $scopedDeptIds = \App\Helpers\Common::getScopedDepartmentIds();
        $searchTerm   = trim((string) $request->input('search_term', ''));
        $departmentId = $request->input('department_id');

        $query = Employee::with(['resortAdmin', 'department', 'position'])
            ->where('resort_id', $resortId)
            ->where('status', 'active')
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))
            ->when(!empty($departmentId), fn($q) => $q->where('Dept_id', $departmentId))
            ->when($searchTerm !== '', function ($q) use ($searchTerm) {
                $q->where(function ($w) use ($searchTerm) {
                    $w->where('Emp_id', 'like', "%{$searchTerm}%")
                      ->orWhereHas('resortAdmin', function ($qa) use ($searchTerm) {
                          $qa->where('first_name', 'like', "%{$searchTerm}%")
                             ->orWhere('last_name', 'like', "%{$searchTerm}%")
                             ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', "%{$searchTerm}%");
                      });
                });
            })
            ->select('id', 'Admin_Parent_id', 'Emp_id', 'Dept_id', 'Position_id', 'nationality', 'religion', 'basic_salary', 'proposed_salary', 'joining_date');

        // Helper — annual budgeted salary for one employee using the same
        // precedence as view-budget (Proposed wins when > 0, else Current,
        // fall back to employees row).
        $annualSalaryFor = function ($empRow) use ($resortId, $currentYear) {
            $sharedFallback = (float) ($empRow->proposed_salary > 0
                ? $empRow->proposed_salary
                : ($empRow->basic_salary ?? 0));

            $monthlyOverrides = DB::table('resort_employee_monthly_salaries')
                ->where('employee_id', $empRow->id)
                ->where('resort_id', $resortId)
                ->where('year', $currentYear)
                ->get(['month', 'current_salary', 'proposed_salary'])
                ->keyBy('month');

            $total = 0.0;
            for ($m = 1; $m <= 12; $m++) {
                $row = $monthlyOverrides->get($m);
                if ($row) {
                    $total += (float) ($row->proposed_salary > 0
                        ? $row->proposed_salary
                        : ($row->current_salary > 0
                            ? $row->current_salary
                            : $sharedFallback));
                } else {
                    $total += $sharedFallback;
                }
            }
            return $total;
        };

        // Helper — annual sum of a bucket of cost rows for ONE employee.
        // Uses Common::annualCostForEmployee for each cost so saved overrides
        // AND live template-fallback are included, matching what HR sees on
        // Budget → View Budget (the previous costSumFor only summed saved
        // override rows, so employees like Fatima — who have only fallback
        // values — showed $0 across every allowance column).
        $costBucketTotal = function ($empRow, array $costs) use ($resortId, $currentYear) {
            if (empty($costs)) return 0.0;
            $sum = 0.0;
            foreach ($costs as $cost) {
                $sum += Common::annualCostForEmployee($resortId, $currentYear, $cost, $empRow);
            }
            return $sum;
        };

        // All amounts below are USD (budget config persists in USD); pass
        // through Common::formatCurrency so the resort's display currency
        // and conversion are applied consistently.
        $datatable = datatables()->of($query)
            ->addColumn('employee_name', fn($row) => optional($row->resortAdmin)->full_name ?? 'N/A')
            ->addColumn('department',    fn($row) => optional($row->department)->name ?? 'N/A')
            ->addColumn('position',      fn($row) => optional($row->position)->position_title ?? 'N/A')
            ->addColumn('salary',        fn($row) => Common::formatCurrency($annualSalaryFor($row), 'USD'))

            ->addColumn('ot',         fn($row) => Common::formatCurrency($costBucketTotal($row, $otCosts),          'USD'))
            ->addColumn('insurance',  fn($row) => Common::formatCurrency($costBucketTotal($row, $insuranceCosts),   'USD'))
            ->addColumn('recruitment',fn($row) => Common::formatCurrency($costBucketTotal($row, $recruitmentCosts), 'USD'));

        // One DataTable column per allowance label. The column key matches
        // the JS bridge in index.blade.php: lowercase + underscore the label.
        foreach ($allowanceLabels as $label) {
            $columnKey = strtolower(str_replace(' ', '_', $label));
            $costs = $allowanceCostsByLabel[$label];
            $datatable->addColumn($columnKey, function ($row) use ($costs, $costBucketTotal) {
                return Common::formatCurrency($costBucketTotal($row, $costs), 'USD');
            });
        }

        $datatable->addColumn('total', function ($row) use (
            $annualSalaryFor, $costBucketTotal, $otCosts, $insuranceCosts, $recruitmentCosts, $allowanceCostsByLabel
        ) {
            $total  = $annualSalaryFor($row);
            $total += $costBucketTotal($row, $otCosts);
            $total += $costBucketTotal($row, $insuranceCosts);
            $total += $costBucketTotal($row, $recruitmentCosts);
            foreach ($allowanceCostsByLabel as $costs) {
                $total += $costBucketTotal($row, $costs);
            }
            return Common::formatCurrency($total, 'USD');
        });

        $datatable->addColumn('details', fn($row) => '');
        $datatable->rawColumns(['details']);

        return $datatable->make(true);
    }

    public function getLiabilityEmployeeData($empId)
    {
        $employee = Employee::findOrFail($empId);
        $currentYear = now()->year;

        // Fetch all distinct allowance types
        $allowanceTypes = PayrollReviewAllowances::whereHas('payrollReview', function ($q) use ($employee, $currentYear) {
            $q->where('employee_id', $employee->id)
            ->whereYear('created_at', $currentYear);
        })->select('allowance_type')->distinct()->pluck('allowance_type');

        $html = '';

        for ($month = 1; $month <= 12; $month++) {
            $monthName = Carbon::create($currentYear, $month)->format('F');

            $payrollReview = PayrollReview::where('employee_id', $employee->id)
                ->whereMonth('created_at', $month)
                ->whereYear('created_at', $currentYear)
                ->first();

            $allowances = PayrollReviewAllowances::whereHas('payrollReview', function ($q) use ($employee, $month, $currentYear) {
                $q->where('employee_id', $employee->id)
                ->whereMonth('created_at', $month)
                ->whereYear('created_at', $currentYear);
            })->get();

            $insurance = $employee->EmployeeInsurance()
                ->whereMonth('insurance_end_date', $month)
                ->whereYear('insurance_end_date', $currentYear)
                ->sum('Premium');

            $visa = $employee->VisaRenewal()
                ->whereMonth('end_date', $month)
                ->whereYear('end_date', $currentYear)
                ->sum('Amt');

            $workPermit = $employee->WorkPermit()
                ->where('Status', 'Paid')
                ->whereMonth('Due_Date', $month)
                ->whereYear('Due_Date', $currentYear)
                ->sum('Amt');

            $medical = $employee->WorkPermitMedicalRenewal()
                ->whereMonth('end_date', $month)
                ->whereYear('end_date', $currentYear)
                ->sum('Amt');

            $quota = $employee->QuotaSlotRenewal()
                ->where('Status', 'Paid')
                ->whereMonth('Payment_Date', $month)
                ->whereYear('Payment_Date', $currentYear)
                ->sum('Amt');

            $budgetAllowances = DB::table('resort_budget_costs')
                ->whereYear('created_at', $currentYear)
                ->select('particulars', DB::raw('SUM(amount) as total'))
                ->groupBy('particulars')
                ->get();

            $html .= view('resorts.renderfiles.employee_monthly_row', [
                'month'       => $monthName,
                'salary'      => $payrollReview->earned_salary ?? 0,
                'ot'          => ($payrollReview->regularOTPay ?? 0) + ($payrollReview->holidayOTPay ?? 0),
                'allowances'  => $allowances,
                'insurance'   => $insurance,
                'visa'        => $visa,
                'work_permit' => $workPermit,
                'medical'     => $medical,
                'quota'       => $quota,
                'budget_allowances' => $budgetAllowances,
                'allowance_types' => $allowanceTypes,
            ])->render();
        }

        return response()->json(['html' => $html]);
    }

    /**
     * Total annual budget across the resort, using the SAME source the Budget
     * → View Budget page aggregates from so the two headlines agree:
     *
     *   per-month employee salaries  (override → employees.proposed > current)
     * + per-month employee costs     (resort_employee_budget_cost_configurations)
     * + per-month vacant salaries    (override → resort_vacant_budget_costs.current > basic)
     * + per-month vacant costs       (resort_vacant_budget_cost_configurations)
     *
     * Both salary buckets prefer the Proposed value when non-zero, falling
     * back to the Current value — mirrors the view-budget render logic.
     */
    private function computeYearlyBudgetTotal($resortId, int $year): float
    {
        // -- 1. Employee salaries --------------------------------------------
        $activeEmployees = DB::table('employees')
            ->where('resort_id', $resortId)
            ->where('status', 'Active')
            ->get(['id', 'basic_salary', 'proposed_salary']);

        $empMonthlyOverrides = DB::table('resort_employee_monthly_salaries')
            ->where('resort_id', $resortId)
            ->where('year', $year)
            ->get(['employee_id', 'month', 'current_salary', 'proposed_salary'])
            ->groupBy('employee_id');

        $employeeSalaryTotal = 0.0;
        foreach ($activeEmployees as $emp) {
            $sharedFallback = (float) ($emp->proposed_salary > 0
                ? $emp->proposed_salary
                : ($emp->basic_salary ?? 0));

            $monthsByMonth = $empMonthlyOverrides->get($emp->id, collect())->keyBy('month');
            for ($m = 1; $m <= 12; $m++) {
                $monthly = $monthsByMonth->get($m);
                if ($monthly) {
                    $effective = (float) ($monthly->proposed_salary > 0
                        ? $monthly->proposed_salary
                        : ($monthly->current_salary > 0
                            ? $monthly->current_salary
                            : $sharedFallback));
                } else {
                    $effective = $sharedFallback;
                }
                $employeeSalaryTotal += $effective;
            }
        }

        // -- 2. Employee per-month cost configurations -----------------------
        $employeeCostTotal = (float) DB::table('resort_employee_budget_cost_configurations')
            ->where('resort_id', $resortId)
            ->where('year', $year)
            ->sum('value');

        // -- 3. Vacant salaries ----------------------------------------------
        $vacants = DB::table('resort_vacant_budget_costs')
            ->where('resort_id', $resortId)
            ->where('year', $year)
            ->get(['id', 'position_id', 'department_id', 'vacant_index', 'basic_salary', 'current_salary']);

        $vacantMonthlyOverrides = DB::table('resort_vacant_monthly_salaries')
            ->where('resort_id', $resortId)
            ->where('year', $year)
            ->get(['position_id', 'department_id', 'vacant_index', 'month', 'current_salary', 'proposed_salary'])
            ->groupBy(fn($r) => $r->position_id . '|' . $r->department_id . '|' . $r->vacant_index);

        $vacantSalaryTotal = 0.0;
        foreach ($vacants as $v) {
            // Per legacy ResortVacantBudgetCost mapping: basic_salary = Current,
            // current_salary = Proposed. Match the precedence the View Budget
            // page uses (Proposed when entered, else Current).
            $sharedFallback = (float) ($v->current_salary > 0
                ? $v->current_salary
                : ($v->basic_salary ?? 0));

            $key = $v->position_id . '|' . $v->department_id . '|' . $v->vacant_index;
            $monthsByMonth = $vacantMonthlyOverrides->get($key, collect())->keyBy('month');
            for ($m = 1; $m <= 12; $m++) {
                $monthly = $monthsByMonth->get($m);
                if ($monthly) {
                    $effective = (float) ($monthly->proposed_salary > 0
                        ? $monthly->proposed_salary
                        : ($monthly->current_salary > 0
                            ? $monthly->current_salary
                            : $sharedFallback));
                } else {
                    $effective = $sharedFallback;
                }
                $vacantSalaryTotal += $effective;
            }
        }

        // -- 4. Vacant per-month cost configurations -------------------------
        $vacantCostTotal = (float) DB::table('resort_vacant_budget_cost_configurations')
            ->where('resort_id', $resortId)
            ->where('year', $year)
            ->sum('value');

        return $employeeSalaryTotal + $employeeCostTotal + $vacantSalaryTotal + $vacantCostTotal;
    }

}