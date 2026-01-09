<?php
namespace App\Http\Controllers\Resorts\People\Liability;

use App\Http\Controllers\Controller;
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
use Common;
use DB;
use Carbon\Carbon;

class LiabilityEstimationController extends Controller 
{
    public $resort;
    
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
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
        $employees = Employee::where('resort_id', $resortId)
            ->where('status', 'active')
            ->get();
        
        $estimated_liability = StoreManningResponseParent::with(['manningbudget' => function($query) use ($currentYear) {
            $query->where('year', $currentYear);
        }])
        ->where('resort_id', $resortId)
        ->sum('Total_Department_budget');

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

        $allowanceTypes = DB::table('resort_budget_costs')
            ->where('resort_id', $resortId)
            ->where('is_payroll_allowance', 1)
            ->distinct()
            ->pluck('particulars'); // e.g., ['Food Allowance', 'Transport']

            // dd($labels, $reductionData);
        return view('resorts.people.liability.index', compact(
            'page_title', 
            'resortId', 'current_liability',
            'resort_departments','employees','estimated_liability',
            'liability_reduction','chartData',
            'labels',
            'reductionData','allowanceTypes'
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

        // Get distinct allowance types
        $allowanceTypes = PayrollReviewAllowances::with('payrollReview')
            ->whereHas('payrollReview', fn($q) => $q->whereYear('created_at', $currentYear))
            ->select('allowance_type')
            ->distinct()
            ->pluck('allowance_type');

        $query = Employee::with(['resortAdmin', 'department', 'position'])
            ->where('resort_id', $resortId)
            ->where('status', 'active')
            ->select('id', 'Admin_Parent_id', 'Emp_id', 'Dept_id', 'Position_id', 'nationality', 'basic_salary', 'joining_date');

        $datatable = datatables()->of($query)
            ->addColumn('employee_name', fn($row) => optional($row->resortAdmin)->full_name ?? 'N/A')
            ->addColumn('department', fn($row) => optional($row->department)->name ?? 'N/A')
            ->addColumn('position', fn($row) => optional($row->position)->position_title ?? 'N/A')
            ->addColumn('salary', fn($row) => '$' . number_format($row->basic_salary, 2))

            ->addColumn('ot', function ($row) use ($currentYear) {
                $totalOT = PayrollReview::where('employee_id', $row->id)
                    ->whereYear('created_at', $currentYear)
                    ->sum(DB::raw('regularOTPay + holidayOTPay'));
                return '$' . number_format($totalOT, 2);
            })

            ->addColumn('service_charge', function ($row) use ($currentYear) {
                $totalSC = PayrollReview::where('employee_id', $row->id)
                    ->whereYear('created_at', $currentYear)
                    ->sum('service_charge');
                return '$' . number_format($totalSC, 2);
            })

            ->addColumn('insurance', function ($row) use ($currentYear) {
                $insurance = $row->EmployeeInsurance()
                    ->whereYear('insurance_end_date', $currentYear)
                    ->sum('Premium');
                return '$' . number_format($insurance, 2);
            })

            ->addColumn('recruitment', function ($row) use ($currentYear) {
                $isNewHire = $row->joining_date && Carbon::parse($row->joining_date)->year == $currentYear;

                if (!$isNewHire) return '$0.00';

                $recruitmentCost = DB::table('resort_budget_costs')
                    ->where('resort_id', $row->resort_id)
                    ->whereIn('particulars', [
                        'Recruitment Fee', 'Work Permit fee', 'Quota Slot Deposit',
                        'Work Visa Medical test fee', 'Medical Insurance'
                    ])->sum('amount');

                return '$' . number_format($recruitmentCost, 2);
            });

        // Add dynamic allowance columns
        foreach ($allowanceTypes as $type) {
            $columnKey = strtolower(str_replace(' ', '_', $type));

            $datatable->addColumn($columnKey, function ($row) use ($type, $currentYear) {
                $amount = PayrollReviewAllowances::where('allowance_type', $type)
                    ->whereHas('payrollReview', function ($q) use ($row, $currentYear) {
                        $q->where('employee_id', $row->id)
                            ->whereYear('created_at', $currentYear);
                    })->sum('amount');
                return '$' . number_format($amount, 2);
            });
        }

        $datatable->addColumn('total', function ($row) use ($currentYear) {
            $salary = $row->basic_salary;
            $ot = PayrollReview::where('employee_id', $row->id)
                ->whereYear('created_at', $currentYear)
                ->sum(DB::raw('regularOTPay + holidayOTPay'));
            $serviceCharge = PayrollReview::where('employee_id', $row->id)
                ->whereYear('created_at', $currentYear)
                ->sum('service_charge');
            $insurance = $row->EmployeeInsurance()
                ->whereYear('insurance_end_date', $currentYear)
                ->sum('Premium');
            $allowance = PayrollReviewAllowances::whereHas('payrollReview', function ($q) use ($row, $currentYear) {
                $q->where('employee_id', $row->id)->whereYear('created_at', $currentYear);
            })->sum('amount');

            return '$' . number_format($salary + $ot + $serviceCharge + $insurance + $allowance, 2);
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

}