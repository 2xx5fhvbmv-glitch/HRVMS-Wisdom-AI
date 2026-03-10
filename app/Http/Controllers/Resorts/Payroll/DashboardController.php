<?php
namespace App\Http\Controllers\Resorts\Payroll;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollEmployees;
use App\Models\ResortPosition;
use App\Models\ResortDepartment;
use App\Models\ServiceCharges;
use App\Models\EwtTaxBracket;
use App\Models\ResortSiteSettings;
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
        $page_title ='Payroll Dashboard';
        $resort_id = $this->resort->resort_id;
        $currentMonth = now()->month;
        $payrollData = $this->buildPayrollComparison($currentMonth);
        // dd($payrollData);
        $total_employees = Employee::where('resort_id',$resort_id)->where('status','active')->count();

        // Count employees actually paid (net_salary > 0) in the last completed/locked payroll
        $lastCompletedPayroll = Payroll::where('resort_id', $resort_id)
            ->whereIn('status', ['locked', 'completed'])
            ->where('total_payroll', '>', 0)
            ->orderBy('end_date', 'desc')
            ->first();
        $total_paid_employees = 0;
        if ($lastCompletedPayroll) {
            $total_paid_employees = DB::table('payroll_reviews')
                ->where('payroll_id', $lastCompletedPayroll->id)
                ->where('net_salary', '>', 0)
                ->count();
        }
        $today = now();
        $currentMonth = $today->month;
        $currentYear = $today->year;

        // Get Last Month Range
        $lastMonth = $today->copy()->subMonthNoOverflow();
        $lastMonthStart = $lastMonth->copy()->startOfMonth();
        $lastMonthEnd = $lastMonth->copy()->endOfMonth();

        // Last Payroll = for April (processed in May)
        // Find the most recent completed payroll that ended before today
        $lastPayroll = Payroll::where('resort_id', $resort_id)
            ->where('end_date', '<', now())
            ->orderBy('end_date', 'desc')
            ->first();
        
        // If no payroll found with that criteria, try finding one with flexible date range
        if (!$lastPayroll) {
            $lastPayroll = Payroll::where('resort_id', $resort_id)
            ->where(function($query) use ($lastMonthStart, $lastMonthEnd) {
                $query->whereBetween('start_date', [$lastMonthStart, $lastMonthEnd])
                  ->orWhereBetween('end_date', [$lastMonthStart, $lastMonthEnd]);
            })
            ->orderBy('end_date', 'desc')
            ->first();
        }

        // dd($lastPayroll);
        // Upcoming Payroll = for May (to process in June)
        $currentMonthStart = $today->copy()->startOfMonth();
        $currentMonthEnd = $today->copy()->endOfMonth();

        // Find the upcoming payroll by looking for a period that overlaps with current month
        // or starts after today but is the closest to now
        $upcomingPayroll = Payroll::where('resort_id', $resort_id)
            ->where(function($query) use ($currentMonthStart, $currentMonthEnd, $today) {
            // Payroll that overlaps with current month
            $query->where(function($q) use ($currentMonthStart, $currentMonthEnd) {
                $q->whereBetween('start_date', [$currentMonthStart, $currentMonthEnd])
                  ->orWhereBetween('end_date', [$currentMonthStart, $currentMonthEnd])
                  ->orWhere(function($q2) use ($currentMonthStart, $currentMonthEnd) {
                  $q2->where('start_date', '<=', $currentMonthStart)
                     ->where('end_date', '>=', $currentMonthEnd);
                  });
            })
            // Or payroll starting in the future (nearest one)
            ->orWhere('start_date', '>', $today);
            })
            ->orderBy('start_date')
            ->first();

        // If upcoming payroll has no total yet, estimate from attendance + employee salaries
        $upcomingEstimated = 0;
        if ($upcomingPayroll && $upcomingPayroll->total_payroll <= 0) {
            $upcomingEstimated = DB::table('parent_attendaces as pa')
                ->join('employees as e', 'e.id', '=', 'pa.Emp_id')
                ->where('pa.resort_id', $resort_id)
                ->where('pa.Status', 'Present')
                ->whereBetween('pa.date', [$upcomingPayroll->start_date, $today->format('Y-m-d')])
                ->selectRaw('SUM(e.basic_salary / 30) as estimated_total')
                ->value('estimated_total') ?? 0;
            $upcomingEstimated = round($upcomingEstimated, 2);
        }

        return view('resorts.payroll.dashboard.dashboard',compact('page_title','total_employees','total_paid_employees','lastPayroll','upcomingPayroll','upcomingEstimated','payrollData'));
    }
    public function getServiceCharges(Request $request)
    {
        $currentYear = $request->YearWiseServichCharges;
        $resort_id = $this->resort->resort_id;
        $serviceCharges = ServiceCharges::select(
            DB::raw('MONTHNAME(CONCAT(year, "-", month, "-01")) as label'),
            'service_charge'
        )
        ->where('resort_id', $resort_id)
        ->where('year',$currentYear)
        ->orderBy('year', 'asc')
        ->orderBy('month', 'asc')
        ->get();
        $total = $serviceCharges->sum('service_charge');
        $serviceCharges = $serviceCharges->map(function ($item) use ($total) {
            $item->percentage = $total > 0 ? round(($item->service_charge / $total) * 100, 2) : 0;
            return $item;
        });
        return response()->json([
            'data' => $serviceCharges,
            'total' => number_format($total, 2), // Send total
        ]);
    }
    public function viewPayrollData(Request $request)
    {
        // dd($request->all());
        $page_title = 'View payroll';
        $resort_id = $this->resort->resort_id;
        $positions = ResortPosition::where('status', 'active')->where('resort_id', $resort_id)->get();
        $departments = ResortDepartment::where('status', 'active')->where('resort_id', $resort_id)->get();
        
        // Get selected month and year from request or use current month/year
        $month = $request->query('month', now()->month);
        $year = $request->query('year', now()->year);
        $payroll_id = 0;
        // Calculate start_date (first day of the month) and end_date (last day of the month)
        $start_date = \Carbon\Carbon::create($year, $month, 1)->startOfMonth()->format('Y-m-d');
        $end_date = \Carbon\Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d');

        // dd($start_date,$end_date,$positions,$departments);
        // Fetch payroll record for the given month and year
        $payroll = Payroll::where('resort_id', $resort_id)
            ->whereMonth('start_date', $month)
            ->whereYear('start_date', $year)
            ->with(['employees', 'timeAndAttendances', 'serviceCharges', 'deductions', 'reviews'])
            ->first();

        // If no payroll found, return a view with an empty dataset
        if (!$payroll) {
            return view('resorts.payroll.run.payroll', compact(
                'page_title', 'positions', 'departments', 'payroll', 'resort_id', 'month', 'year', 'start_date', 'end_date','payroll_id'
            ))->with('error', 'No payroll data found for the selected period.');
        }

        $payroll_id = $payroll->id;
        // dd($payroll);
        // Fetch related data
       
        // dd($departments);

        return view('resorts.payroll.run.payroll', compact(
            'page_title', 'positions', 'departments', 'payroll', 'resort_id', 'month', 'year', 'payroll_id', 'start_date', 'end_date'
        ));
    }

    public function getPayrollExpenses(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $resort_id = $this->resort->resort_id;

        // Get payroll data for the selected year
        $payrollData = DB::table('payroll')
            ->select(
                DB::raw("MONTHNAME(start_date) as month"),
                DB::raw("SUM(total_payroll) as payroll_cost")
            )
            ->where('resort_id', $resort_id)
            ->whereYear('start_date', $year)
            ->groupBy('month')
            ->get();

        $otData = DB::table('payroll as p')
        ->join('payroll_time_and_attandance as pta', 'p.id', '=', 'pta.payroll_id')
        ->join('employees as e', 'e.id', '=', 'pta.employee_id')
        ->where('p.resort_id', $resort_id)
        ->whereYear('start_date', $year)
        ->where(function ($query) {
            $query->where('pta.regular_ot_hours', '>', 0)
                ->orWhere('pta.holiday_ot_hours', '>', 0);
        }) // Only fetch employees with OT
        ->selectRaw("
            MONTHNAME(p.start_date) as month,
            pta.employee_id,
            e.basic_salary,
            pta.regular_ot_hours,
            pta.holiday_ot_hours,
            (pta.regular_ot_hours * e.basic_salary * 1.25) as regular_ot_cost,
            (pta.holiday_ot_hours * e.basic_salary * 1.50) as holiday_ot_cost,
            ((pta.regular_ot_hours * e.basic_salary * 1.25) + 
            (pta.holiday_ot_hours * e.basic_salary * 1.50)) as ot_cost
        ")
        ->get();

        // Get service charge data
        $serviceChargeData = DB::table('payroll_service_charges')
            ->join('payroll','payroll.id','=','payroll_service_charges.payroll_id')
            ->select(
                DB::raw("MONTHNAME(payroll.start_date) as month"),
                DB::raw("SUM(payroll_service_charges.service_charge_amount) as service_charge")
            )
            ->where('payroll.resort_id', $resort_id)
            ->whereYear('payroll.start_date', $year)
            ->groupBy('month')
            ->get();

        // dd($otData);

        // Generate labels (Months)
        $months = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];

        // Initialize arrays
        $payrollCost = array_fill(0, 12, 0);
        $otCost = array_fill(0, 12, 0);
        $serviceCharge = array_fill(0, 12, 0);

        // Map database results to correct month index
        foreach ($payrollData as $row) {
            $index = array_search($row->month, $months);
            if ($index !== false) {
                $payrollCost[$index] = $row->payroll_cost;
            }
        }

        // dd($otData);
        foreach ($otData as $row) {
            $index = array_search(strtolower($row->month), array_map('strtolower', $months));
        
            if ($index !== false) {
                if (!isset($otCost[$index])) {
                    $otCost[$index] = 0;
                }
                $otCost[$index] += $row->ot_cost; // Accumulate OT costs
            }
        }

        foreach ($serviceChargeData as $row) {
            $index = array_search($row->month, $months);
            if ($index !== false) {
                $serviceCharge[$index] = $row->service_charge;
            }
        }
        // dd($otCost);

        return response()->json([
            'success' => true,
            'labels' => array_map(fn($m) => substr($m, 0, 3) . " " . substr($year, -2), $months),
            'data' => [
                'payrollCost' => $payrollCost,
                'otCost' => $otCost,
                'serviceCharge' => $serviceCharge
            ]
        ]);
    }

    public function getPayrollComparison(Request $request)
    {
        $selectedMonth = $request->input('month', now()->month);
        $payrollData = $this->buildPayrollComparison($selectedMonth);
        // dd($payrollData);
        $html = view('resorts.renderfiles.payroll_comparison_card', compact('payrollData'))->render();

        return response()->json(['html' => $html]);
    }

    private function buildPayrollComparison($selectedMonth = null)
    {
        $selectedMonth = $selectedMonth ?? now()->month;
        $currentYear = now()->year;
        $previousYear = $currentYear - 1;
        $resort_id = $this->resort->resort_id;

        $months = [
            ['year' => $currentYear, 'month' => $selectedMonth],
            ['year' => $previousYear, 'month' => $selectedMonth]
        ];

        $data = [];

        foreach ($months as $m) {
            $monthName = date("F Y", strtotime("{$m['year']}-{$m['month']}-01"));

            $result = DB::table('payroll as p')
                ->join('payroll_reviews as pr', 'p.id', '=', 'pr.payroll_id')
                ->where('p.resort_id', $resort_id)
                ->whereYear('p.start_date', $m['year'])
                ->whereMonth('p.start_date', $m['month'])
                ->selectRaw("
                    SUM(pr.earned_salary) as total_basic_salary,
                    SUM(pr.service_charge) as service_charge,
                    SUM(pr.regularOTPay) as total_regular_ot_cost,
                    SUM(pr.holidayOTPay) as total_holiday_ot_cost
                ")
                ->first();

            $basicSalary = $result->total_basic_salary ?? 0;
            $serviceCharge = $result->service_charge ?? 0;
            $regularOT = $result->total_regular_ot_cost ?? 0;
            $holidayOT = $result->total_holiday_ot_cost ?? 0;
            $total = $basicSalary + $serviceCharge + $regularOT + $holidayOT;

            $data[$monthName] = [
                'basicSalary' => [
                    'amount' => $basicSalary,
                    'percentage' => $total > 0 ? round(($basicSalary / $total) * 100) : 0
                ],
                'serviceCharge' => [
                    'amount' => $serviceCharge,
                    'percentage' => $total > 0 ? round(($serviceCharge / $total) * 100) : 0
                ],
                'normalOT' => [
                    'amount' => $regularOT,
                    'percentage' => $total > 0 ? round(($regularOT / $total) * 100) : 0
                ],
                'holidayOT' => [
                    'amount' => $holidayOT,
                    'percentage' => $total > 0 ? round(($holidayOT / $total) * 100) : 0
                ],
                'total' => $total
            ];
        }
        return $data;
    }


    public function getPayrollDistribution()
    {
        $resort_id = $this->resort->resort_id;

        // Fetch total net pay for employees grouped by payment method (Cash vs Bank)
        $payments = DB::table('payroll_employees as pe')
            ->join('payroll as p', 'p.id', '=', 'pe.payroll_id')
            ->join('payroll_reviews as pr', function ($join) {
                $join->on('pe.employee_id', '=', 'pr.employee_id')
                     ->on('pe.payroll_id', '=', 'pr.payroll_id');
            })
            ->where('p.resort_id', $resort_id)
            ->selectRaw("
                SUM(CASE WHEN pe.paymentMethod = 'Cash' THEN pr.net_salary ELSE 0 END) as total_cash_pay,
                SUM(CASE WHEN pe.paymentMethod = 'Bank' THEN pr.net_salary ELSE 0 END) as total_bank_pay
            ")
            ->first();

        return response()->json([
            'cashPayments' => $payments->total_cash_pay ?? 0,
            'bankTransfers' => $payments->total_bank_pay ?? 0
        ]);
    }

    public function getDepartmentDistribution()
    {
        $resort_id = $this->resort->resort_id;

        // Fetch department-wise payroll distribution
        $departments = Payroll::join('payroll_employees as pe', 'payroll.id', '=', 'pe.payroll_id')
        ->join('payroll_reviews as pr', function ($join) {
            $join->on('pr.employee_id', '=', 'pe.employee_id')
                 ->on('pr.payroll_id', '=', 'pe.payroll_id');
        })
        ->join('resort_departments as rd', 'rd.id', '=', 'pe.department')
        ->where('payroll.resort_id', $resort_id)
        ->selectRaw('rd.name as department, SUM(pr.net_salary) as total')
        ->groupBy('pe.department', 'rd.name')
        ->get();

        // Generate unique colors dynamically for each department
        $departmentColors = [];
        foreach ($departments as $index => $dept) {
            $hue = ($index * 137.5) % 360;
            $departmentColors[$dept->department] = "hsl($hue, 70%, 60%)";
        }

        // Format data for the chart
        $data = $departments->map(function ($item) use ($departmentColors) {
            return [
                'what' => $item->department,
                'value' => $item->total,
                'color' => $departmentColors[$item->department] ?? '#999999',
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function getMonthlyPensionData(Request $request)
    {
        $currentYear = $request->YearWisePensionData;
        // dd($currentYear);
        // Fetch pension deductions grouped by month
        $resort_id = $this->resort->resort_id;
        $pensionData = Payroll::join('payroll_employees as pe', 'payroll.id', '=', 'pe.payroll_id')
            ->join('payroll_deductions as pd', function ($join) {
                $join->on('pd.employee_id', '=', 'pe.employee_id')
                     ->on('pd.payroll_id', '=', 'pe.payroll_id');
            })
            ->where('payroll.resort_id', $resort_id)
            ->selectRaw("DATE_FORMAT(payroll.start_date, '%b %Y') as month,
                        SUM(pd.pension) as employee,
                        SUM(pd.pension) as employer")
            ->whereYear('payroll.start_date', $currentYear) // Filter by year
            ->groupBy('month')
            ->orderByRaw("STR_TO_DATE(month, '%b %Y')")
            ->get();

        return response()->json($pensionData);
    }

    public function getOtTrendData(Request $request)
    {
        $year = $request->input('year', now()->year); // Default to current year

        $resort_id = $this->resort->resort_id;
        $otData = DB::table('payroll_time_and_attandance')
            ->join('payroll', 'payroll_time_and_attandance.payroll_id', '=', 'payroll.id')
            ->where('payroll.resort_id', $resort_id)
            ->whereYear('payroll.start_date', $year) // filter by selected year
            ->selectRaw("DATE_FORMAT(payroll.start_date, '%b %Y') as month, SUM(total_ot) as total_ot")
            ->groupBy('month')
            ->orderByRaw("STR_TO_DATE(month, '%b %Y')")
            ->limit(12)
            ->get();

        return response()->json([
            'labels' => $otData->pluck('month'),
            'data' => $otData->pluck('total_ot')
        ]);
    }

   public function getTaxBracketDistribution(Request $request)
    {
        $year = $request->input('year', now()->year);

        // Get resort settings for conversion
        $resortId = $this->resort->resort_id ?? null; // Optional if this is called from superadmin
        $settings = ResortSiteSettings::where('resort_id', $resortId)->first();
        
        if (!$settings || !isset($settings['DollertoMVR'])) {
            return response()->json([
                'labels' => [],
                'data' => [],
                'message' => 'Currency conversion settings not found.'
            ], 400);
        }

        $usdToMvr = floatval($settings['DollertoMVR']);

        // Load tax brackets
        $brackets = EwtTaxBracket::orderBy('min_salary')->get();

        // Prepare labels and initialize totals
        $result = [];
        foreach ($brackets as $bracket) {
            $label = is_null($bracket->max_salary)
                ? "{$bracket->min_salary}+ MVR"
                : "{$bracket->min_salary} - {$bracket->max_salary} MVR";

            $result[$label] = 0;
        }

        // Get all taxable incomes (in USD or whatever stored) and convert to MVR
        $records = DB::table('payroll as p')
            ->join('payroll_deductions as pd', 'pd.payroll_id', '=', 'p.id')
            ->join('payroll_reviews as pr', function ($join) {
                $join->on('pr.payroll_id', '=', 'p.id')
                     ->on('pr.employee_id', '=', 'pd.employee_id');
            })
            ->where('p.resort_id', $resortId)
            ->whereYear('p.start_date', $year)
            ->select('pd.ewt', DB::raw('(pr.total_earnings - pd.pension) as taxable_income'))
            ->get();

        foreach ($records as $rec) {
            $taxableMvr = floatval($rec->taxable_income) * $usdToMvr; // ✅ convert to MVR

            foreach ($brackets as $bracket) {
                $min = $bracket->min_salary;
                $max = is_null($bracket->max_salary) ? PHP_INT_MAX : $bracket->max_salary;

                if ($taxableMvr >= $min && $taxableMvr <= $max) {
                    $label = is_null($bracket->max_salary)
                        ? "{$bracket->min_salary}+ MVR"
                        : "{$bracket->min_salary} - {$bracket->max_salary} MVR";

                    $result[$label] += floatval($rec->ewt);
                    break; // Stop after first matching bracket
                }
            }
        }

        return response()->json([
            'labels' => array_keys($result),
            'data' => array_map(fn($v) => round($v, 2), array_values($result)),
        ]);
    }

    public function getBudgetComparison(Request $request)
    {
        $year = $request->input('year', now()->year);
        $resort_id = $this->resort->resort_id;

        $months = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
        ];

        $labels = [];
        $budgetedAmounts = [];
        $actualAmounts = [];

        foreach ($months as $monthNum => $monthName) {
            $labels[] = $monthName . ' ' . substr($year, -2);

            // Budgeted: employee budget configs + vacant budget configs for this month
            $employeeBudget = DB::table('resort_employee_budget_cost_configurations')
                ->where('resort_id', $resort_id)
                ->where('year', $year)
                ->where('month', $monthNum)
                ->sum('value');

            $vacantBudget = DB::table('resort_vacant_budget_cost_configurations')
                ->where('resort_id', $resort_id)
                ->where('year', $year)
                ->where('month', $monthNum)
                ->sum('value');

            $budgetedAmounts[] = round($employeeBudget + $vacantBudget, 2);

            // Actual: payroll total_payroll for this month
            $actualPayroll = DB::table('payroll')
                ->where('resort_id', $resort_id)
                ->whereYear('start_date', $year)
                ->whereMonth('start_date', $monthNum)
                ->sum('total_payroll');

            $actualAmounts[] = round($actualPayroll, 2);
        }

        return response()->json([
            'labels' => $labels,
            'budgeted' => $budgetedAmounts,
            'actual' => $actualAmounts
        ]);
    }

}

