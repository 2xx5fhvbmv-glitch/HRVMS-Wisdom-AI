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
use App\Helpers\Common;
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

        // Last Payroll = most recent completed/locked payroll, fallback to any status
        $lastPayroll = Payroll::where('resort_id', $resort_id)
            ->where('end_date', '<', $today)
            ->whereIn('status', ['completed', 'locked', 'processed', 'paid'])
            ->orderBy('end_date', 'desc')
            ->first();
        if (!$lastPayroll) {
            // Fallback: show most recent payroll regardless of status
            $lastPayroll = Payroll::where('resort_id', $resort_id)
                ->where('end_date', '<', $today)
                ->orderBy('end_date', 'desc')
                ->first();
        }

        // Calculate upcoming cutoff period
        $payrollConfig = \App\Models\PayrollConfig::where('resort_id', $resort_id)->first();
        $cutoffDay = $payrollConfig->cutoff_day ?? 1;
        $cutoffPeriod = Common::getCurrentCutoffPeriod($cutoffDay);
        $upcomingCutoffDate = $cutoffPeriod['end'];

        // Upcoming Payroll: find payroll for the current cutoff period
        $upcomingPayroll = Payroll::where('resort_id', $resort_id)
            ->where('start_date', $cutoffPeriod['start']->format('Y-m-d'))
            ->where('end_date', $cutoffPeriod['end']->format('Y-m-d'))
            ->first();

        // If current period is already locked, look at the next period
        if ($upcomingPayroll && $upcomingPayroll->status === 'locked') {
            $nextStart = $cutoffPeriod['end']->copy()->addDay();
            $nextEnd = $nextStart->copy()->addMonth()->subDay();
            $upcomingPayroll = Payroll::where('resort_id', $resort_id)
                ->where('start_date', $nextStart->format('Y-m-d'))
                ->first();
            $upcomingCutoffDate = $nextEnd;
        }

        // Estimate upcoming payroll only from employees who have attendance in the current cutoff period
        $upcomingEstimated = 0;
        $isEstimated = false;
        if (!$upcomingPayroll || $upcomingPayroll->total_payroll <= 0) {
            $isEstimated = true;
            $activeEmployeeIds = Employee::where('resort_id', $resort_id)
                ->whereIn('status', ['Active', 'Probationary'])
                ->pluck('id');

            // Only include employees who have attendance records in the current cutoff period
            $employeesWithAttendance = DB::table('parent_attendaces')
                ->whereIn('Emp_id', $activeEmployeeIds)
                ->whereBetween('date', [$cutoffPeriod['start']->format('Y-m-d'), $cutoffPeriod['end']->format('Y-m-d')])
                ->distinct('Emp_id')
                ->pluck('Emp_id');

            if ($employeesWithAttendance->isNotEmpty()) {
                $totalMonthlySalary = Employee::whereIn('id', $employeesWithAttendance)->sum('basic_salary');
                $totalMonthlyAllowances = DB::table('employees_allowance')
                    ->whereIn('employee_id', $employeesWithAttendance)
                    ->sum('amount');

                $upcomingEstimated = round($totalMonthlySalary + $totalMonthlyAllowances, 2);
            }
        }

        // Only show drafts that have review data and no overlapping locked/completed payroll
        $draftPayrolls = Payroll::where('resort_id', $resort_id)
            ->where('status', 'draft')
            ->whereHas('reviews')
            ->whereNotExists(function ($query) use ($resort_id) {
                $query->select(DB::raw(1))
                    ->from('payroll as locked_p')
                    ->where('locked_p.resort_id', $resort_id)
                    ->whereIn('locked_p.status', ['locked', 'completed', 'processed', 'paid'])
                    ->whereRaw('locked_p.start_date <= payroll.end_date')
                    ->whereRaw('locked_p.end_date >= payroll.start_date');
            })
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function($p) {
                $p->employee_count = \DB::table('payroll_employees')->where('payroll_id', $p->id)->count();
                return $p;
            });

        // Payrolls in approval process (pending_approval, approved, or draft with rejection history)
        $approvalPayrolls = Payroll::where('resort_id', $resort_id)
            ->where(function($q) {
                $q->whereIn('status', ['pending_approval', 'approved'])
                  ->orWhere(function($q2) {
                      // Draft payrolls that have approval records (sent for approval before, possibly rejected)
                      $q2->where('status', 'draft')
                         ->whereHas('approvals');
                  });
            })
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function($p) {
                $p->employee_count = \DB::table('payroll_employees')->where('payroll_id', $p->id)->count();
                $p->approvals = \App\Models\PayrollApproval::where('payroll_id', $p->id)
                    ->orderBy('step_order')
                    ->get();
                $p->has_rejection = $p->approvals->where('status', 'rejected')->isNotEmpty();
                // If total_payroll is 0, calculate from reviews
                if ($p->total_payroll <= 0) {
                    $p->total_payroll = \DB::table('payroll_reviews')
                        ->where('payroll_id', $p->id)
                        ->sum('net_salary');
                }
                return $p;
            });

        // Locked (completed) payrolls
        $lockedPayrolls = Payroll::where('resort_id', $resort_id)
            ->where('status', 'locked')
            ->orderByDesc('end_date')
            ->limit(5)
            ->get();

        return view('resorts.payroll.dashboard.dashboard', compact(
            'page_title', 'total_employees', 'total_paid_employees',
            'lastPayroll', 'upcomingPayroll', 'upcomingEstimated', 'isEstimated',
            'payrollData', 'upcomingCutoffDate', 'draftPayrolls', 'approvalPayrolls', 'lockedPayrolls'
        ));
    }
    public function draftsList()
    {
        $page_title = 'Draft Payrolls';
        $resort_id = $this->resort->resort_id;
        $drafts = Payroll::where('resort_id', $resort_id)
            ->where('status', 'draft')
            ->whereHas('reviews')
            ->whereNotExists(function ($query) use ($resort_id) {
                $query->select(DB::raw(1))
                    ->from('payroll as locked_p')
                    ->where('locked_p.resort_id', $resort_id)
                    ->whereIn('locked_p.status', ['locked', 'completed', 'processed', 'paid'])
                    ->whereRaw('locked_p.start_date <= payroll.end_date')
                    ->whereRaw('locked_p.end_date >= payroll.start_date');
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->through(function($p) {
                $p->employee_count = \DB::table('payroll_employees')->where('payroll_id', $p->id)->count();
                return $p;
            });

        return view('resorts.payroll.dashboard.drafts', compact('page_title', 'drafts'));
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

        // Find payroll matching this month/year (by start_date or end_date)
        // Prioritize locked payrolls
        $payroll = Payroll::where('resort_id', $resort_id)
            ->where(function($q) use ($month, $year) {
                $q->where(function($q2) use ($month, $year) {
                    $q2->whereMonth('end_date', $month)->whereYear('end_date', $year);
                })->orWhere(function($q2) use ($month, $year) {
                    $q2->whereMonth('start_date', $month)->whereYear('start_date', $year);
                });
            })
            ->with(['employees', 'timeAndAttendances', 'serviceCharges', 'deductions', 'reviews'])
            ->orderByRaw("FIELD(status, 'locked', 'completed', 'approved', 'pending_approval', 'draft')")
            ->first();

        $start_date = $payroll ? $payroll->start_date : \Carbon\Carbon::create($year, $month, 1)->format('Y-m-d');
        $end_date = $payroll ? $payroll->end_date : \Carbon\Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d');

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

        // Only use locked payrolls for accurate data, group by end_date month
        $payrollData = DB::table('payroll')
            ->select(
                DB::raw("MONTH(end_date) as month_num"),
                DB::raw("SUM(total_payroll) as payroll_cost")
            )
            ->where('resort_id', $resort_id)
            ->where('status', 'locked')
            ->whereYear('end_date', $year)
            ->groupBy('month_num')
            ->get();

        // Use actual OT pay from payroll_reviews instead of recalculating from hours × salary
        $otData = DB::table('payroll as p')
            ->join('payroll_reviews as pr', 'p.id', '=', 'pr.payroll_id')
            ->where('p.resort_id', $resort_id)
            ->where('p.status', 'locked')
            ->whereYear('p.end_date', $year)
            ->select(
                DB::raw("MONTH(p.end_date) as month_num"),
                DB::raw("SUM(pr.earnings_overtime) as ot_cost")
            )
            ->groupBy('month_num')
            ->get();

        // Get service charge data from locked payrolls only
        $serviceChargeData = DB::table('payroll_service_charges')
            ->join('payroll', 'payroll.id', '=', 'payroll_service_charges.payroll_id')
            ->select(
                DB::raw("MONTH(payroll.end_date) as month_num"),
                DB::raw("SUM(payroll_service_charges.service_charge_amount) as service_charge")
            )
            ->where('payroll.resort_id', $resort_id)
            ->where('payroll.status', 'locked')
            ->whereYear('payroll.end_date', $year)
            ->groupBy('month_num')
            ->get();

        // Generate labels (Months)
        $months = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];

        // Initialize arrays
        $payrollCost = array_fill(0, 12, 0);
        $otCost = array_fill(0, 12, 0);
        $serviceCharge = array_fill(0, 12, 0);

        // Map database results to correct month index (month_num is 1-based)
        foreach ($payrollData as $row) {
            $payrollCost[$row->month_num - 1] = $row->payroll_cost;
        }

        foreach ($otData as $row) {
            $otCost[$row->month_num - 1] = $row->ot_cost;
        }

        foreach ($serviceChargeData as $row) {
            $serviceCharge[$row->month_num - 1] = $row->service_charge;
        }

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
        $html = view('resorts.renderfiles.payroll_comparison_card', compact('payrollData', 'selectedMonth'))->render();

        return response()->json(['html' => $html]);
    }

    private function buildPayrollComparison($selectedMonth = null)
    {
        $selectedMonth = $selectedMonth ?? now()->month;
        $currentYear = now()->year;
        $previousYear = $currentYear - 1;
        $twoYearsAgo = $currentYear - 2;
        $resort_id = $this->resort->resort_id;

        $months = [
            ['year' => $currentYear, 'month' => $selectedMonth],
            ['year' => $previousYear, 'month' => $selectedMonth],
            ['year' => $twoYearsAgo, 'month' => $selectedMonth]
        ];

        $data = [];

        foreach ($months as $m) {
            $monthName = date("F Y", strtotime("{$m['year']}-{$m['month']}-01"));

            $result = DB::table('payroll as p')
                ->join('payroll_reviews as pr', 'p.id', '=', 'pr.payroll_id')
                ->where('p.resort_id', $resort_id)
                ->where('p.status', 'locked')
                ->where(function($q) use ($m) {
                    $q->where(function($q2) use ($m) {
                        $q2->whereYear('p.start_date', $m['year'])->whereMonth('p.start_date', $m['month']);
                    })->orWhere(function($q2) use ($m) {
                        $q2->whereYear('p.end_date', $m['year'])->whereMonth('p.end_date', $m['month']);
                    });
                })
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

        // Get the latest locked payroll only
        $latestLocked = Payroll::where('resort_id', $resort_id)
            ->where('status', 'locked')
            ->orderByDesc('end_date')
            ->first();

        $payments = (object) ['total_cash_pay' => 0, 'total_bank_pay' => 0];

        if ($latestLocked) {
            // Fetch total net pay for employees grouped by payment method (Cash vs Bank)
            $payments = DB::table('payroll_employees as pe')
                ->join('payroll_reviews as pr', function ($join) {
                    $join->on('pe.employee_id', '=', 'pr.employee_id')
                         ->on('pe.payroll_id', '=', 'pr.payroll_id');
                })
                ->where('pe.payroll_id', $latestLocked->id)
                ->selectRaw("
                    SUM(CASE WHEN pe.paymentMethod = 'Cash' THEN pr.net_salary ELSE 0 END) as total_cash_pay,
                    SUM(CASE WHEN pe.paymentMethod = 'Bank' THEN pr.net_salary ELSE 0 END) as total_bank_pay
                ")
                ->first();
        }

        return response()->json([
            'cashPayments' => $payments->total_cash_pay ?? 0,
            'bankTransfers' => $payments->total_bank_pay ?? 0
        ]);
    }

    public function getDepartmentDistribution()
    {
        $resort_id = $this->resort->resort_id;

        // Fetch department-wise payroll distribution from the latest locked payroll only
        $latestLocked = Payroll::where('resort_id', $resort_id)
            ->where('status', 'locked')
            ->orderByDesc('end_date')
            ->first();

        $departments = collect();
        if ($latestLocked) {
            $departments = Payroll::join('payroll_employees as pe', 'payroll.id', '=', 'pe.payroll_id')
                ->join('payroll_reviews as pr', function ($join) {
                    $join->on('pr.employee_id', '=', 'pe.employee_id')
                         ->on('pr.payroll_id', '=', 'pe.payroll_id');
                })
                ->join('employees as e', 'e.id', '=', 'pe.employee_id')
                ->join('resort_departments as rd', 'rd.id', '=', 'e.Dept_id')
                ->where('payroll.id', $latestLocked->id)
                ->selectRaw('rd.name as department, SUM(pr.net_salary) as total')
                ->groupBy('e.Dept_id', 'rd.name')
                ->get();
        }

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
            ->where('payroll.status', 'locked')
            ->whereYear('payroll.start_date', $year)
            ->selectRaw("MONTH(payroll.start_date) as month_num, SUM(total_ot) as total_ot")
            ->groupBy('month_num')
            ->get()
            ->keyBy('month_num');

        // Build full 12-month data starting from 0
        $labels = [];
        $data = [];
        for ($m = 1; $m <= 12; $m++) {
            $labels[] = \Carbon\Carbon::create($year, $m, 1)->format('M');
            $data[] = isset($otData[$m]) ? round(floatval($otData[$m]->total_ot), 1) : 0;
        }

        return response()->json([
            'labels' => $labels,
            'data' => $data
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

        // Load tax brackets (stored in MVR)
        $brackets = EwtTaxBracket::orderBy('min_salary')->get();

        // Prepare labels — brackets are always in MVR (Maldives tax law)
        $result = [];
        foreach ($brackets as $bracket) {
            $label = is_null($bracket->max_salary)
                ? "MVR " . number_format($bracket->min_salary, 0) . "+"
                : "MVR " . number_format($bracket->min_salary, 0) . " - " . number_format($bracket->max_salary, 0);

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
            ->where('p.status', 'locked')
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
                        ? "MVR " . number_format($bracket->min_salary, 0) . "+"
                        : "MVR " . number_format($bracket->min_salary, 0) . " - " . number_format($bracket->max_salary, 0);

                    $result[$label] += floatval($rec->ewt);
                    break;
                }
            }
        }

        // Filter out zero brackets so chart only shows brackets with actual tax
        $filtered = array_filter($result, fn($v) => $v > 0);

        return response()->json([
            'labels' => array_keys($filtered),
            'data' => array_map(fn($v) => round($v, 2), array_values($filtered)),
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

