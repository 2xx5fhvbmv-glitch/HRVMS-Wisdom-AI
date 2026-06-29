<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Resorts\Concerns\PredefinedReportActions;
use App\Helpers\Common;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Predefined Payroll reports (Option B).
 *
 * Each report is a dedicated, dept-scoped query returning the same
 * { columns, rows } shape the generic builder renders, so the existing
 * resorts.renderfiles.ReportFilterData partial is reused verbatim.
 *
 * "Payroll Period" = a single payroll run (payroll.id, a date range). There is
 * no payroll-group concept in the schema. Every employee-keyed query honours the
 * department-scope rule (Common::getScopedDepartmentIds()).
 */
class PayrollReportController extends Controller
{
    use PredefinedReportActions;

    protected $resort;

    // Tunable thresholds for the Payroll Exceptions rules.
    private const OT_HOURS_THRESHOLD = 100;   // abnormal overtime per period
    private const PAY_SWING_PCT      = 40;    // % net change vs previous run

    public function __construct()
    {
        $this->resort = auth()->guard('resort-admin')->user();
    }

    private function registry(): array
    {
        return [
            'payroll_summary'        => ['name' => 'Payroll Summary', 'description' => 'Summarised payroll for the selected period.', 'filters' => ['payroll'], 'handler' => 'payrollSummary'],
            'detailed_register'      => ['name' => 'Detailed Payroll Register', 'description' => 'Full payroll breakdown per employee.', 'filters' => ['payroll', 'department'], 'handler' => 'detailedRegister'],
            'payroll_comparison'     => ['name' => 'Payroll Comparison', 'description' => 'Compare payroll cost between two periods.', 'filters' => ['from_payroll', 'to_payroll'], 'handler' => 'payrollComparison'],
            'cost_by_department'     => ['name' => 'Payroll Cost by Department', 'description' => 'Payroll expenditure per department.', 'filters' => ['payroll'], 'handler' => 'costByDepartment'],
            'cost_by_designation'    => ['name' => 'Payroll Cost by Designation', 'description' => 'Payroll expenditure per designation.', 'filters' => ['payroll'], 'handler' => 'costByDesignation'],
            'payment_distribution'   => ['name' => 'Payroll Distribution (Bank vs Cash)', 'description' => 'How salaries are split between bank and cash.', 'filters' => ['payroll'], 'handler' => 'paymentDistribution'],
            'bank_transfer'          => ['name' => 'Bank Transfer Report', 'description' => 'Employees paid by bank transfer.', 'filters' => ['payroll', 'bank'], 'handler' => 'bankTransfer'],
            'cash_payment'           => ['name' => 'Cash Payment Report', 'description' => 'Employees paid in cash.', 'filters' => ['payroll'], 'handler' => 'cashPayment'],
            'gross_salary'           => ['name' => 'Gross Salary Report', 'description' => 'Gross salary earned per employee.', 'filters' => ['payroll'], 'handler' => 'grossSalary'],
            'net_salary'             => ['name' => 'Net Salary Report', 'description' => 'Final payable salary after deductions.', 'filters' => ['payroll'], 'handler' => 'netSalary'],
            'allowance_report'       => ['name' => 'Allowance Report', 'description' => 'Allowances paid during the period.', 'filters' => ['payroll', 'allowance_type'], 'handler' => 'allowanceReport'],
            'deduction_report'       => ['name' => 'Deduction Report', 'description' => 'Deductions made during payroll processing.', 'filters' => ['payroll', 'deduction_type'], 'handler' => 'deductionReport'],
            'service_charge_dist'    => ['name' => 'Service Charge Distribution', 'description' => 'Service charge per employee.', 'filters' => ['payroll'], 'handler' => 'serviceChargeDistribution'],
            'service_charge_trend'   => ['name' => 'Service Charge Trend', 'description' => 'Monthly service charge trend.', 'filters' => ['year'], 'handler' => 'serviceChargeTrend'],
            'avg_service_charge'     => ['name' => 'Average Service Charge', 'description' => 'Average service charge per employee by department.', 'filters' => ['year', 'department'], 'handler' => 'averageServiceCharge'],
            'top_overtime'           => ['name' => 'Top Overtime Employees', 'description' => 'Highest overtime payments.', 'filters' => ['payroll'], 'handler' => 'topOvertime'],
            'ewt_report'             => ['name' => 'Employee Withholding Tax (EWT) Report', 'description' => 'EWT deducted during the period.', 'filters' => ['payroll'], 'handler' => 'ewtReport'],
            'annual_tax_summary'     => ['name' => 'Annual Tax Summary', 'description' => 'Total tax deducted per employee for the year.', 'filters' => ['year'], 'handler' => 'annualTaxSummary'],
            'ff_settlement_register' => ['name' => 'Full & Final Settlement Register', 'description' => 'Employees undergoing final settlement.', 'filters' => ['year', 'settlement_status'], 'handler' => 'ffSettlementRegister'],
            'ff_settlement_pending'  => ['name' => 'Pending Full & Final Settlement', 'description' => 'Outstanding (not finalized) settlements.', 'filters' => [], 'handler' => 'ffSettlementPending'],
            'tuckshop_deduction'     => ['name' => 'Tuck Shop Deduction Summary', 'description' => 'Tuck shop deductions per employee.', 'filters' => ['payroll'], 'handler' => 'tuckshopDeduction'],
            'tuckshop_purchases'     => ['name' => 'Tuck Shop Purchase Details', 'description' => 'Itemised tuck shop purchases.', 'filters' => ['payroll'], 'handler' => 'tuckshopPurchases'],
            'salary_advance'         => ['name' => 'Salary Advance Report', 'description' => 'Advances issued and recovered.', 'filters' => ['department'], 'handler' => 'salaryAdvance'],
            'payroll_exceptions'     => ['name' => 'Payroll Exceptions Report', 'description' => 'Anomalies to review before approval.', 'filters' => ['payroll'], 'handler' => 'payrollExceptions'],
            'payroll_audit_trail'    => ['name' => 'Payroll Audit Trail', 'description' => 'Processing history and approvals.', 'filters' => ['payroll'], 'handler' => 'payrollAuditTrail'],
            'local_vs_expat'         => ['name' => 'Local vs Expat Payroll Summary', 'description' => 'Payroll cost for local vs expatriate staff.', 'filters' => ['payroll'], 'handler' => 'localVsExpat'],
            'executive_summary'      => ['name' => 'Payroll Executive Summary', 'description' => 'Consolidated payroll overview for management.', 'filters' => ['payroll'], 'handler' => 'executiveSummary'],
        ];
    }

    public function index()
    {
        if (Common::checkRouteWisePermission('resort.report.index', config('settings.resort_permissions.view')) == false) {
            return abort(403, 'Unauthorized access');
        }

        $page_title = 'Payroll Reports';
        $resortId   = $this->resort->resort_id;
        $scoped     = Common::getScopedDepartmentIds();

        $reports = collect($this->registry())->map(fn($r, $key) => [
            'key' => $key, 'name' => $r['name'], 'description' => $r['description'], 'filters' => $r['filters'],
        ])->values();

        $payrolls = DB::table('payroll')->where('resort_id', $resortId)
            ->orderBy('start_date', 'desc')
            ->get(['id', 'start_date', 'end_date', 'status'])
            ->map(fn($p) => [
                'id'    => $p->id,
                'label' => $this->periodLabel($p) . ' (' . $p->status . ')',
            ]);

        $departments = DB::table('resort_departments')->where('resort_id', $resortId)
            ->when($scoped !== null, fn($q) => $q->whereIn('id', $scoped))
            ->orderBy('name')->get(['id', 'name']);

        $years = DB::table('payroll')->where('resort_id', $resortId)
            ->selectRaw('DISTINCT YEAR(start_date) as y')->orderBy('y', 'desc')->pluck('y');

        $allowanceTypes = DB::table('payroll_review_allowances as a')
            ->join('payroll_reviews as pr', 'pr.id', '=', 'a.payroll_review_id')
            ->join('payroll as pay', 'pay.id', '=', 'pr.payroll_id')
            ->where('pay.resort_id', $resortId)
            ->distinct()->orderBy('a.allowance_type')->pluck('a.allowance_type');

        $deductionTypes = array_keys($this->deductionColumns());

        $banks = DB::table('employee_bank_details as bd')
            ->join('employees as e', 'e.id', '=', 'bd.employee_id')
            ->where('e.resort_id', $resortId)
            ->whereNotNull('bd.bank_name')->where('bd.bank_name', '<>', '')
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))
            ->distinct()->orderBy('bd.bank_name')->pluck('bd.bank_name');

        $settlementStatuses = ['draft', 'review', 'finalized'];

        return view('resorts.reports.payroll', compact(
            'page_title', 'reports', 'payrolls', 'departments', 'years',
            'allowanceTypes', 'deductionTypes', 'banks', 'settlementStatuses'
        ));
    }

    private function filtersFrom(Request $request): array
    {
        return [
            'payroll'           => $request->input('payroll') ?: null,
            'from_payroll'      => $request->input('from_payroll') ?: null,
            'to_payroll'        => $request->input('to_payroll') ?: null,
            'department'        => $request->input('department') ?: null,
            'year'              => $request->input('year') ?: null,
            'allowance_type'    => $request->input('allowance_type') ?: null,
            'deduction_type'    => $request->input('deduction_type') ?: null,
            'bank'              => $request->input('bank') ?: null,
            'settlement_status' => $request->input('settlement_status') ?: null,
        ];
    }

    /** Resolve a report key + filters to ['name','description','columns','rows'] or null. */
    private function compute(string $key, array $filters): ?array
    {
        $registry = $this->registry();
        if (!isset($registry[$key])) {
            return null;
        }
        $res = $this->{$registry[$key]['handler']}($filters);
        return [
            'name'        => $registry[$key]['name'],
            'description' => $registry[$key]['description'],
            'columns'     => $res['columns'],
            'rows'        => $res['rows'],
        ];
    }

    public function run(Request $request)
    {
        if (Common::checkRouteWisePermission('resort.report.index', config('settings.resort_permissions.view')) == false) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $c = $this->compute((string) $request->input('report'), $this->filtersFrom($request));
        if (!$c) {
            return response()->json(['success' => false, 'message' => 'Unknown report.'], 422);
        }

        $html = view('resorts.renderfiles.ReportFilterData', [
            'report'  => (object) ['name' => $c['name']],
            'columns' => $c['columns'],
            'data'    => $c['rows'],
        ])->render();

        return response()->json(['success' => true, 'html' => $html, 'count' => count($c['rows'])]);
    }

    /** Export a predefined report (csv / excel / pdf). */
    public function export(Request $request)
    {
        if (Common::checkRouteWisePermission('resort.report.index', config('settings.resort_permissions.view')) == false) {
            return abort(403, 'Unauthorized access');
        }
        $c = $this->compute((string) $request->input('report'), $this->filtersFrom($request));
        if (!$c) {
            return abort(404, 'Unknown report');
        }
        return $this->exportComputedReport($c['name'], $c['description'], $c['columns'], $c['rows'], $request->input('format', 'pdf'));
    }

    /** WAI Insights for a predefined report. */
    public function insights(Request $request)
    {
        if (Common::checkRouteWisePermission('resort.report.index', config('settings.resort_permissions.view')) == false) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }
        $c = $this->compute((string) $request->input('report'), $this->filtersFrom($request));
        if (!$c) {
            return response()->json(['status' => false, 'message' => 'Unknown report.'], 422);
        }
        $text = $this->computeAiInsightsText($c['name'], $c['description'], $c['columns'], $c['rows']);
        return response()->json(['status' => true, 'data' => $text]);
    }

    /* ---------------------------------------------------------------- helpers */

    private function n($v): string
    {
        return number_format((float) $v, 2);
    }

    private function pct($num, $den): string
    {
        if (!$den) return '0%';
        return round(($num / $den) * 100, 1) . '%';
    }

    private function periodLabel($pay): string
    {
        return Carbon::parse($pay->start_date)->format('d M Y') . ' – ' . Carbon::parse($pay->end_date)->format('d M Y');
    }

    private function nameExpr()
    {
        return DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as employee_name");
    }

    private function deductionColumns(): array
    {
        return [
            'Attendance'   => 'attendance_deduction',
            'City Ledger'  => 'city_ledger',
            'Staff Shop'   => 'staff_shop',
            'Advance Loan' => 'advance_loan',
            'Pension'      => 'pension',
            'EWT'          => 'ewt',
            'Other'        => 'other',
        ];
    }

    private function resolvePayrollId(array $filters)
    {
        if (!empty($filters['payroll'])) return $filters['payroll'];

        // Default to the most recent run that actually has payslips, so the
        // initial view isn't an empty draft. Fall back to the latest overall.
        $latest = DB::table('payroll as p')
            ->where('p.resort_id', $this->resort->resort_id)
            ->whereExists(fn($q) => $q->select(DB::raw(1))->from('payroll_reviews')
                ->whereColumn('payroll_reviews.payroll_id', 'p.id'))
            ->orderBy('p.start_date', 'desc')->first()
            ?: DB::table('payroll')->where('resort_id', $this->resort->resort_id)
                ->orderBy('start_date', 'desc')->first();

        return $latest->id ?? 0;
    }

    /** Base per-employee payslip query for a payroll run, dept-scoped. */
    private function basePayslip($payrollId, array $filters)
    {
        $scoped = Common::getScopedDepartmentIds();
        return DB::table('payroll_reviews as pr')
            ->join('payroll as pay', 'pay.id', '=', 'pr.payroll_id')
            ->join('employees as e', 'e.id', '=', 'pr.employee_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->leftJoin('resort_departments as d', 'd.id', '=', 'e.Dept_id')
            ->leftJoin('resort_positions as p', 'p.id', '=', 'e.Position_id')
            ->where('pay.resort_id', $this->resort->resort_id)
            ->where('pr.payroll_id', $payrollId)
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))
            ->when($filters['department'] ?? null, fn($q) => $q->where('e.Dept_id', $filters['department']));
    }

    /* ---------------------------------------------------------------- reports */

    /** #1 Payroll Summary. */
    public function payrollSummary(array $filters): array
    {
        $pid = $this->resolvePayrollId($filters);
        $pay = DB::table('payroll')->where('id', $pid)->first();
        $t = $this->basePayslip($pid, $filters)->selectRaw(
            'COUNT(*) emp, SUM(pr.total_earnings) gross, SUM(pr.total_deductions) ded, SUM(pr.net_salary) net'
        )->first();

        return [
            'columns' => ['Payroll Period', 'Total Employees', 'Gross Salary', 'Total Deductions', 'Net Salary', 'Total Payroll Cost'],
            'rows'    => [[
                'Payroll Period'     => $pay ? $this->periodLabel($pay) : 'N/A',
                'Total Employees'    => (int) ($t->emp ?? 0),
                'Gross Salary'       => $this->n($t->gross ?? 0),
                'Total Deductions'   => $this->n($t->ded ?? 0),
                'Net Salary'         => $this->n($t->net ?? 0),
                'Total Payroll Cost' => $this->n($t->gross ?? 0),
            ]],
        ];
    }

    /** #2 Detailed Payroll Register. */
    public function detailedRegister(array $filters): array
    {
        $pid = $this->resolvePayrollId($filters);
        $rows = $this->basePayslip($pid, $filters)
            ->leftJoin('payroll_deductions as pd', function ($j) {
                $j->on('pd.payroll_id', '=', 'pr.payroll_id')->on('pd.employee_id', '=', 'pr.employee_id');
            })
            ->orderBy('d.name')->orderBy('ra.first_name')
            ->get([
                'pr.Emp_id', $this->nameExpr(), 'd.name as dept', 'p.position_title',
                'pr.earnings_basic', 'pr.earnings_allowance', 'pr.earnings_overtime', 'pr.regularOTPay', 'pr.holidayOTPay',
                'pr.service_charge', 'pr.total_earnings', 'pr.total_deductions', 'pr.net_salary',
                'pd.pension', 'pd.ewt',
            ])
            ->map(fn($r) => [
                'Employee ID'   => $r->Emp_id,
                'Employee Name' => $r->employee_name,
                'Department'    => $r->dept ?? 'N/A',
                'Designation'   => $r->position_title ?? 'N/A',
                'Basic Salary'  => $this->n($r->earnings_basic),
                'Allowances'    => $this->n($r->earnings_allowance),
                'OT'            => $this->n(($r->earnings_overtime ?: ($r->regularOTPay + $r->holidayOTPay))),
                'Service Charge'=> $this->n($r->service_charge),
                'Gross Salary'  => $this->n($r->total_earnings),
                'Deductions'    => $this->n($r->total_deductions),
                'Pension'       => $this->n($r->pension ?? 0),
                'Tax'           => $this->n($r->ewt ?? 0),
                'Net Salary'    => $this->n($r->net_salary),
            ])->all();

        return [
            'columns' => ['Employee ID', 'Employee Name', 'Department', 'Designation', 'Basic Salary', 'Allowances', 'OT', 'Service Charge', 'Gross Salary', 'Deductions', 'Pension', 'Tax', 'Net Salary'],
            'rows'    => $rows,
        ];
    }

    /** #4 Payroll Comparison. */
    public function payrollComparison(array $filters): array
    {
        $fromId = $filters['from_payroll'];
        $toId   = $filters['to_payroll'];
        $rows = [];
        $prevGross = null;

        foreach ([$fromId, $toId] as $pid) {
            if (!$pid) continue;
            $pay = DB::table('payroll')->where('id', $pid)->where('resort_id', $this->resort->resort_id)->first();
            if (!$pay) continue;
            $t = $this->basePayslip($pid, $filters)->selectRaw('SUM(pr.total_earnings) gross, SUM(pr.net_salary) net')->first();
            $gross = (float) ($t->gross ?? 0);
            $diff  = $prevGross === null ? null : $gross - $prevGross;
            $rows[] = [
                'Payroll Period'    => $this->periodLabel($pay),
                'Gross Salary'      => $this->n($gross),
                'Net Salary'        => $this->n($t->net ?? 0),
                'Payroll Difference'=> $diff === null ? '—' : $this->n($diff),
                'Percentage Change' => $diff === null ? '—' : $this->pct($diff, $prevGross),
            ];
            $prevGross = $gross;
        }

        return [
            'columns' => ['Payroll Period', 'Gross Salary', 'Net Salary', 'Payroll Difference', 'Percentage Change'],
            'rows'    => $rows,
        ];
    }

    /** #5 Payroll Cost by Department. */
    public function costByDepartment(array $filters): array
    {
        $pid = $this->resolvePayrollId($filters);
        $raw = $this->basePayslip($pid, $filters)
            ->groupBy('e.Dept_id', 'd.name')
            ->select('d.name as dept', DB::raw('COUNT(*) emp'), DB::raw('SUM(pr.total_earnings) gross'), DB::raw('SUM(pr.net_salary) net'))
            ->get();
        $grand = $raw->sum('gross') ?: 0;
        $rows = $raw->map(fn($r) => [
            'Department'              => $r->dept ?? 'N/A',
            'Total Employees'         => (int) $r->emp,
            'Gross Payroll'           => $this->n($r->gross),
            'Net Payroll'             => $this->n($r->net),
            'Percentage of Total Payroll' => $this->pct($r->gross, $grand),
        ])->all();

        return [
            'columns' => ['Department', 'Total Employees', 'Gross Payroll', 'Net Payroll', 'Percentage of Total Payroll'],
            'rows'    => $rows,
        ];
    }

    /** #6 Payroll Cost by Designation. */
    public function costByDesignation(array $filters): array
    {
        $pid = $this->resolvePayrollId($filters);
        $rows = $this->basePayslip($pid, $filters)
            ->groupBy('e.Position_id', 'p.position_title')
            ->select('p.position_title', DB::raw('COUNT(*) emp'), DB::raw('SUM(pr.total_earnings) cost'))
            ->orderByDesc(DB::raw('SUM(pr.total_earnings)'))
            ->get()
            ->map(fn($r) => [
                'Designation'       => $r->position_title ?? 'N/A',
                'Employee Count'    => (int) $r->emp,
                'Total Payroll Cost'=> $this->n($r->cost),
            ])->all();

        return ['columns' => ['Designation', 'Employee Count', 'Total Payroll Cost'], 'rows' => $rows];
    }

    /** #7 Payroll Distribution (Bank vs Cash). */
    public function paymentDistribution(array $filters): array
    {
        $pid = $this->resolvePayrollId($filters);
        $rows = $this->basePayslip($pid, $filters)
            ->leftJoin('employee_bank_details as bd', 'bd.employee_id', '=', 'e.id')
            ->orderBy('e.payment_mode')->orderBy('ra.first_name')
            ->get([$this->nameExpr(), 'e.payment_mode', 'bd.bank_name', 'bd.account_no', 'pr.net_salary'])
            ->map(fn($r) => [
                'Employee Name'  => $r->employee_name,
                'Payment Mode'   => $r->payment_mode ?? 'N/A',
                'Bank Name'      => $r->bank_name ?? 'N/A',
                'Account Number' => $r->account_no ?? 'N/A',
                'Net Salary'     => $this->n($r->net_salary),
            ])->all();

        return ['columns' => ['Employee Name', 'Payment Mode', 'Bank Name', 'Account Number', 'Net Salary'], 'rows' => $rows];
    }

    /** #8 Bank Transfer Report. */
    public function bankTransfer(array $filters): array
    {
        $pid = $this->resolvePayrollId($filters);
        $rows = $this->basePayslip($pid, $filters)
            ->leftJoin('employee_bank_details as bd', 'bd.employee_id', '=', 'e.id')
            ->where('e.payment_mode', 'Bank')
            ->when($filters['bank'], fn($q) => $q->where('bd.bank_name', $filters['bank']))
            ->orderBy('bd.bank_name')->orderBy('ra.first_name')
            ->get([$this->nameExpr(), 'bd.bank_name', 'bd.account_no', 'pr.net_salary'])
            ->map(fn($r) => [
                'Employee Name'  => $r->employee_name,
                'Bank Name'      => $r->bank_name ?? 'N/A',
                'Account Number' => $r->account_no ?? 'N/A',
                'Net Salary'     => $this->n($r->net_salary),
            ])->all();

        return ['columns' => ['Employee Name', 'Bank Name', 'Account Number', 'Net Salary'], 'rows' => $rows];
    }

    /** #9 Cash Payment Report. */
    public function cashPayment(array $filters): array
    {
        $pid = $this->resolvePayrollId($filters);
        $rows = $this->basePayslip($pid, $filters)
            ->where('e.payment_mode', 'Cash')
            ->orderBy('d.name')->orderBy('ra.first_name')
            ->get(['pr.Emp_id', $this->nameExpr(), 'd.name as dept', 'pr.net_salary'])
            ->map(fn($r) => [
                'Employee Name' => $r->employee_name,
                'Employee ID'   => $r->Emp_id,
                'Department'    => $r->dept ?? 'N/A',
                'Net Salary'    => $this->n($r->net_salary),
            ])->all();

        return ['columns' => ['Employee Name', 'Employee ID', 'Department', 'Net Salary'], 'rows' => $rows];
    }

    /** #11 Gross Salary Report. */
    public function grossSalary(array $filters): array
    {
        $pid = $this->resolvePayrollId($filters);
        $rows = $this->basePayslip($pid, $filters)
            ->orderBy('ra.first_name')
            ->get([$this->nameExpr(), 'pr.earnings_basic', 'pr.earnings_allowance', 'pr.earnings_overtime', 'pr.regularOTPay', 'pr.holidayOTPay', 'pr.service_charge', 'pr.total_earnings'])
            ->map(fn($r) => [
                'Employee Name'  => $r->employee_name,
                'Basic Salary'   => $this->n($r->earnings_basic),
                'Allowances'     => $this->n($r->earnings_allowance),
                'OT'             => $this->n($r->earnings_overtime ?: ($r->regularOTPay + $r->holidayOTPay)),
                'Service Charge' => $this->n($r->service_charge),
                'Gross Salary'   => $this->n($r->total_earnings),
            ])->all();

        return ['columns' => ['Employee Name', 'Basic Salary', 'Allowances', 'OT', 'Service Charge', 'Gross Salary'], 'rows' => $rows];
    }

    /** #12 Net Salary Report. */
    public function netSalary(array $filters): array
    {
        $pid = $this->resolvePayrollId($filters);
        $rows = $this->basePayslip($pid, $filters)
            ->orderBy('ra.first_name')
            ->get([$this->nameExpr(), 'pr.total_earnings', 'pr.total_deductions', 'pr.net_salary'])
            ->map(fn($r) => [
                'Employee Name'   => $r->employee_name,
                'Gross Salary'    => $this->n($r->total_earnings),
                'Total Deductions'=> $this->n($r->total_deductions),
                'Net Salary'      => $this->n($r->net_salary),
            ])->all();

        return ['columns' => ['Employee Name', 'Gross Salary', 'Total Deductions', 'Net Salary'], 'rows' => $rows];
    }

    /** #13 Allowance Report. */
    public function allowanceReport(array $filters): array
    {
        $pid = $this->resolvePayrollId($filters);
        $scoped = Common::getScopedDepartmentIds();
        $rows = DB::table('payroll_review_allowances as a')
            ->join('payroll_reviews as pr', 'pr.id', '=', 'a.payroll_review_id')
            ->join('employees as e', 'e.id', '=', 'pr.employee_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->where('pr.payroll_id', $pid)
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))
            ->when($filters['department'], fn($q) => $q->where('e.Dept_id', $filters['department']))
            ->when($filters['allowance_type'], fn($q) => $q->where('a.allowance_type', $filters['allowance_type']))
            ->orderBy('ra.first_name')
            ->get([$this->nameExpr(), 'a.allowance_type', 'a.amount', 'a.amount_unit'])
            ->map(fn($r) => [
                'Employee Name'  => $r->employee_name,
                'Allowance Type' => $r->allowance_type,
                'Amount'         => $this->n($r->amount) . ' ' . ($r->amount_unit ?? ''),
            ])->all();

        return ['columns' => ['Employee Name', 'Allowance Type', 'Amount'], 'rows' => $rows];
    }

    /** #14 Deduction Report. */
    public function deductionReport(array $filters): array
    {
        $pid = $this->resolvePayrollId($filters);
        $cols = $this->deductionColumns();
        $wanted = $filters['deduction_type'] && isset($cols[$filters['deduction_type']])
            ? [$filters['deduction_type'] => $cols[$filters['deduction_type']]] : $cols;

        $records = $this->basePayslip($pid, $filters)
            ->join('payroll_deductions as pd', function ($j) {
                $j->on('pd.payroll_id', '=', 'pr.payroll_id')->on('pd.employee_id', '=', 'pr.employee_id');
            })
            ->orderBy('ra.first_name')
            ->get(array_merge([$this->nameExpr()], array_map(fn($c) => "pd.$c", array_values($wanted))));

        $rows = [];
        foreach ($records as $r) {
            foreach ($wanted as $label => $col) {
                if ((float) $r->$col == 0) continue; // only non-zero deductions
                $rows[] = [
                    'Employee Name'  => $r->employee_name,
                    'Deduction Type' => $label,
                    'Amount'         => $this->n($r->$col),
                ];
            }
        }

        return ['columns' => ['Employee Name', 'Deduction Type', 'Amount'], 'rows' => $rows];
    }

    /** #15 Service Charge Distribution. */
    public function serviceChargeDistribution(array $filters): array
    {
        $pid = $this->resolvePayrollId($filters);
        $rows = $this->basePayslip($pid, $filters)
            ->orderBy('d.name')->orderBy('ra.first_name')
            ->get([$this->nameExpr(), 'd.name as dept', 'pr.service_charge'])
            ->map(fn($r) => [
                'Employee Name'       => $r->employee_name,
                'Department'          => $r->dept ?? 'N/A',
                'Service Charge Amount'=> $this->n($r->service_charge),
            ])->all();

        return ['columns' => ['Employee Name', 'Department', 'Service Charge Amount'], 'rows' => $rows];
    }

    /** #16 Service Charge Trend (by month for a year). */
    public function serviceChargeTrend(array $filters): array
    {
        $year = $filters['year'] ?: Carbon::now()->year;
        $raw = DB::table('payroll_reviews as pr')
            ->join('payroll as pay', 'pay.id', '=', 'pr.payroll_id')
            ->where('pay.resort_id', $this->resort->resort_id)
            ->whereRaw('YEAR(pay.start_date) = ?', [$year])
            ->groupBy(DB::raw('MONTH(pay.start_date)'))
            ->select(
                DB::raw('MONTH(pay.start_date) as m'),
                DB::raw('SUM(pr.service_charge) as total'),
                DB::raw('AVG(pr.service_charge) as avg'),
                DB::raw('MAX(pr.service_charge) as high')
            )->orderBy('m')->get();

        $rows = $raw->map(fn($r) => [
            'Month'                 => Carbon::create()->month((int) $r->m)->format('F'),
            'Total Service Charge'  => $this->n($r->total),
            'Average Service Charge'=> $this->n($r->avg),
            'Highest Service Charge'=> $this->n($r->high),
        ])->all();

        return ['columns' => ['Month', 'Total Service Charge', 'Average Service Charge', 'Highest Service Charge'], 'rows' => $rows];
    }

    /** #17 Average Service Charge (by department for a year). */
    public function averageServiceCharge(array $filters): array
    {
        $year = $filters['year'] ?: Carbon::now()->year;
        $scoped = Common::getScopedDepartmentIds();
        $rows = DB::table('payroll_reviews as pr')
            ->join('payroll as pay', 'pay.id', '=', 'pr.payroll_id')
            ->join('employees as e', 'e.id', '=', 'pr.employee_id')
            ->leftJoin('resort_departments as d', 'd.id', '=', 'e.Dept_id')
            ->where('pay.resort_id', $this->resort->resort_id)
            ->whereRaw('YEAR(pay.start_date) = ?', [$year])
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))
            ->when($filters['department'], fn($q) => $q->where('e.Dept_id', $filters['department']))
            ->groupBy('e.Dept_id', 'd.name')
            ->select('d.name as dept', DB::raw('COUNT(DISTINCT e.id) emp'), DB::raw('AVG(pr.service_charge) avg'))
            ->orderBy('d.name')->get()
            ->map(fn($r) => [
                'Department'            => $r->dept ?? 'N/A',
                'Employee Count'        => (int) $r->emp,
                'Average Service Charge'=> $this->n($r->avg),
            ])->all();

        return ['columns' => ['Department', 'Employee Count', 'Average Service Charge'], 'rows' => $rows];
    }

    /** #19 Top Overtime Employees. */
    public function topOvertime(array $filters): array
    {
        $pid = $this->resolvePayrollId($filters);
        $rows = $this->basePayslip($pid, $filters)
            ->leftJoin('payroll_time_and_attandance as ta', function ($j) {
                $j->on('ta.payroll_id', '=', 'pr.payroll_id')->on('ta.employee_id', '=', 'pr.employee_id');
            })
            ->whereRaw('(pr.regularOTPay + pr.holidayOTPay) > 0')
            ->orderByDesc(DB::raw('pr.regularOTPay + pr.holidayOTPay'))
            ->get([$this->nameExpr(), 'd.name as dept', 'ta.total_ot', 'pr.regularOTPay', 'pr.holidayOTPay'])
            ->map(fn($r) => [
                'Employee Name' => $r->employee_name,
                'Department'    => $r->dept ?? 'N/A',
                'Total OT Hours'=> $this->n($r->total_ot ?? 0),
                'OT Amount'     => $this->n($r->regularOTPay + $r->holidayOTPay),
            ])->all();

        return ['columns' => ['Employee Name', 'Department', 'Total OT Hours', 'OT Amount'], 'rows' => $rows];
    }

    /** #23 EWT Report. */
    public function ewtReport(array $filters): array
    {
        $pid = $this->resolvePayrollId($filters);
        $brackets = DB::table('ewt_tax_brackets')->orderBy('min_salary')->get();

        $rows = $this->basePayslip($pid, $filters)
            ->join('payroll_deductions as pd', function ($j) {
                $j->on('pd.payroll_id', '=', 'pr.payroll_id')->on('pd.employee_id', '=', 'pr.employee_id');
            })
            ->where('pd.ewt', '>', 0)
            ->orderBy('ra.first_name')
            ->get([$this->nameExpr(), 'pr.total_earnings', 'pd.ewt'])
            ->map(function ($r) use ($brackets) {
                $bracket = $brackets->first(function ($b) use ($r) {
                    return $r->total_earnings >= $b->min_salary
                        && ($b->max_salary === null || $r->total_earnings <= $b->max_salary);
                });
                return [
                    'Employee Name' => $r->employee_name,
                    'Taxable Income'=> $this->n($r->total_earnings),
                    'Tax Bracket'   => $bracket
                        ? $this->n($bracket->min_salary) . '–' . ($bracket->max_salary === null ? '∞' : $this->n($bracket->max_salary)) . ' @ ' . $bracket->tax_rate . '%'
                        : 'N/A',
                    'EWT Amount'    => $this->n($r->ewt),
                ];
            })->all();

        return ['columns' => ['Employee Name', 'Taxable Income', 'Tax Bracket', 'EWT Amount'], 'rows' => $rows];
    }

    /** #24 Annual Tax Summary. */
    public function annualTaxSummary(array $filters): array
    {
        $year = $filters['year'] ?: Carbon::now()->year;
        $scoped = Common::getScopedDepartmentIds();
        $rows = DB::table('payroll_deductions as pd')
            ->join('payroll as pay', 'pay.id', '=', 'pd.payroll_id')
            ->join('payroll_reviews as pr', function ($j) {
                $j->on('pr.payroll_id', '=', 'pd.payroll_id')->on('pr.employee_id', '=', 'pd.employee_id');
            })
            ->join('employees as e', 'e.id', '=', 'pd.employee_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->where('pay.resort_id', $this->resort->resort_id)
            ->whereRaw('YEAR(pay.start_date) = ?', [$year])
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))
            ->groupBy('e.id', 'ra.first_name', 'ra.last_name')
            ->havingRaw('SUM(pd.ewt) > 0')
            ->select($this->nameExpr(), DB::raw('SUM(pr.total_earnings) gross'), DB::raw('SUM(pd.ewt) ewt'))
            ->orderBy('ra.first_name')->get()
            ->map(fn($r) => [
                'Employee Name'       => $r->employee_name,
                'Gross Taxable Income'=> $this->n($r->gross),
                'Total EWT'           => $this->n($r->ewt),
            ])->all();

        return ['columns' => ['Employee Name', 'Gross Taxable Income', 'Total EWT'], 'rows' => $rows];
    }

    /** #25 Full & Final Settlement Register. */
    public function ffSettlementRegister(array $filters): array
    {
        $rows = $this->settlementQuery($filters)
            ->when($filters['settlement_status'], fn($q) => $q->where('fs.status', $filters['settlement_status']))
            ->when($filters['year'], fn($q) => $q->whereRaw('YEAR(fs.last_working_date) = ?', [$filters['year']]))
            ->orderByDesc('fs.last_working_date')
            ->get([
                $this->nameExpr(), 'fs.last_working_date', 'fs.basic_salary', 'fs.leave_encashment',
                'fs.total_deductions', 'fs.net_pay', 'fs.status',
            ])
            ->map(fn($r) => [
                'Employee Name'         => $r->employee_name,
                'Separation Date'       => $r->last_working_date ? Carbon::parse($r->last_working_date)->format('d M Y') : 'N/A',
                'Final Salary'          => $this->n($r->basic_salary ?? 0),
                'Leave Encashment'      => $this->n($r->leave_encashment ?? 0),
                'Gratuity'              => 'N/A', // not modelled separately
                'Deductions'            => $this->n($r->total_deductions ?? 0),
                'Final Settlement Amount'=> $this->n($r->net_pay ?? 0),
                'Settlement Status'     => ucfirst($r->status ?? ''),
            ])->all();

        return [
            'columns' => ['Employee Name', 'Separation Date', 'Final Salary', 'Leave Encashment', 'Gratuity', 'Deductions', 'Final Settlement Amount', 'Settlement Status'],
            'rows'    => $rows,
        ];
    }

    /** #26 Pending Full & Final Settlement. */
    public function ffSettlementPending(array $filters): array
    {
        $rows = $this->settlementQuery($filters)
            ->where('fs.status', '<>', 'finalized')
            ->orderByDesc('fs.last_working_date')
            ->get([$this->nameExpr(), 'd.name as dept', 'fs.last_working_date', 'fs.net_pay'])
            ->map(fn($r) => [
                'Employee Name'   => $r->employee_name,
                'Department'      => $r->dept ?? 'N/A',
                'Separation Date' => $r->last_working_date ? Carbon::parse($r->last_working_date)->format('d M Y') : 'N/A',
                'Pending Amount'  => $this->n($r->net_pay ?? 0),
            ])->all();

        return ['columns' => ['Employee Name', 'Department', 'Separation Date', 'Pending Amount'], 'rows' => $rows];
    }

    private function settlementQuery(array $filters)
    {
        $scoped = Common::getScopedDepartmentIds();
        return DB::table('final_settlements as fs')
            ->join('employees as e', 'e.id', '=', 'fs.employee_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->leftJoin('resort_departments as d', 'd.id', '=', 'e.Dept_id')
            ->where('e.resort_id', $this->resort->resort_id)
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped));
    }

    /** #27 Tuck Shop Deduction Summary. */
    public function tuckshopDeduction(array $filters): array
    {
        $pid = $this->resolvePayrollId($filters);
        $rows = $this->basePayslip($pid, $filters)
            ->join('payroll_deductions as pd', function ($j) {
                $j->on('pd.payroll_id', '=', 'pr.payroll_id')->on('pd.employee_id', '=', 'pr.employee_id');
            })
            ->where('pd.staff_shop', '>', 0)
            ->orderBy('d.name')->orderBy('ra.first_name')
            ->get([$this->nameExpr(), 'd.name as dept', 'pd.staff_shop'])
            ->map(fn($r) => [
                'Employee Name'           => $r->employee_name,
                'Department'              => $r->dept ?? 'N/A',
                'Total Tuck Shop Deduction'=> $this->n($r->staff_shop),
            ])->all();

        return ['columns' => ['Employee Name', 'Department', 'Total Tuck Shop Deduction'], 'rows' => $rows];
    }

    /** #29 Tuck Shop Purchase Details (within the payroll period's date range). */
    public function tuckshopPurchases(array $filters): array
    {
        $pid = $this->resolvePayrollId($filters);
        $pay = DB::table('payroll')->where('id', $pid)->first();
        $scoped = Common::getScopedDepartmentIds();

        $q = DB::table('payments as pmt')
            ->join('employees as e', 'e.id', '=', 'pmt.emp_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->leftJoin('products as pr2', 'pr2.id', '=', 'pmt.product_id')
            ->leftJoin('shopkeepers as sk', 'sk.id', '=', 'pmt.shopkeeper_id')
            ->where('e.resort_id', $this->resort->resort_id)
            ->when($scoped !== null, fn($x) => $x->whereIn('e.Dept_id', $scoped))
            ->when($filters['department'], fn($x) => $x->where('e.Dept_id', $filters['department']));

        if ($pay) {
            $q->whereBetween('pmt.purchased_date', [$pay->start_date, $pay->end_date]);
        }

        $rows = $q->orderBy('pmt.purchased_date')
            ->get([$this->nameExpr(), 'pr2.name as item', 'pmt.quantity', 'pmt.price', 'sk.name as vendor'])
            ->map(fn($r) => [
                'Employee Name' => $r->employee_name,
                'Item Name'     => $r->item ?? 'N/A',
                'Quantity'      => (int) $r->quantity,
                'Amount'        => $this->n($r->price),
                'Vendor'        => $r->vendor ?? 'N/A',
            ])->all();

        return ['columns' => ['Employee Name', 'Item Name', 'Quantity', 'Amount', 'Vendor'], 'rows' => $rows];
    }

    /** #30 Salary Advance Report. */
    public function salaryAdvance(array $filters): array
    {
        $scoped = Common::getScopedDepartmentIds();
        $rows = DB::table('payroll_advance as a')
            ->join('employees as e', 'e.id', '=', 'a.employee_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->leftJoin(DB::raw('(SELECT payroll_advance_id, SUM(CASE WHEN status = "Paid" THEN amount ELSE 0 END) recovered FROM payroll_recovery_schedule GROUP BY payroll_advance_id) rs'), 'rs.payroll_advance_id', '=', 'a.id')
            ->where('a.resort_id', $this->resort->resort_id)
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))
            ->when($filters['department'], fn($q) => $q->where('e.Dept_id', $filters['department']))
            ->orderByDesc('a.request_date')
            ->get([$this->nameExpr(), 'a.request_amount', DB::raw('COALESCE(rs.recovered,0) as recovered')])
            ->map(fn($r) => [
                'Employee Name'      => $r->employee_name,
                'Advance Amount'     => $this->n($r->request_amount),
                'Recovered Amount'   => $this->n($r->recovered),
                'Outstanding Balance'=> $this->n($r->request_amount - $r->recovered),
            ])->all();

        return ['columns' => ['Employee Name', 'Advance Amount', 'Recovered Amount', 'Outstanding Balance'], 'rows' => $rows];
    }

    /** #31 Payroll Exceptions Report — derived anomalies (no stored table). */
    public function payrollExceptions(array $filters): array
    {
        $pid = $this->resolvePayrollId($filters);
        $pay = DB::table('payroll')->where('id', $pid)->where('resort_id', $this->resort->resort_id)->first();

        // Previous run (by start_date) for pay-swing comparison.
        $prevNet = [];
        if ($pay) {
            $prev = DB::table('payroll')->where('resort_id', $this->resort->resort_id)
                ->where('start_date', '<', $pay->start_date)->orderByDesc('start_date')->first();
            if ($prev) {
                $prevNet = DB::table('payroll_reviews')->where('payroll_id', $prev->id)
                    ->pluck('net_salary', 'employee_id')->toArray();
            }
        }

        $records = $this->basePayslip($pid, $filters)
            ->leftJoin('payroll_deductions as pd', function ($j) {
                $j->on('pd.payroll_id', '=', 'pr.payroll_id')->on('pd.employee_id', '=', 'pr.employee_id');
            })
            ->leftJoin('payroll_time_and_attandance as ta', function ($j) {
                $j->on('ta.payroll_id', '=', 'pr.payroll_id')->on('ta.employee_id', '=', 'pr.employee_id');
            })
            ->leftJoin('employee_bank_details as bd', 'bd.employee_id', '=', 'e.id')
            ->get([
                'pr.employee_id', $this->nameExpr(),
                'pr.total_earnings', 'pr.total_deductions', 'pr.net_salary',
                'e.payment_mode', 'bd.account_no', 'e.ewt_status', 'e.pension as emp_pension',
                'pd.ewt', 'pd.pension as ded_pension',
                'ta.present_days', 'ta.total_ot',
            ]);

        $rows = [];
        $add = function ($name, $type, $remark) use (&$rows) {
            $rows[] = ['Employee Name' => $name, 'Exception Type' => $type, 'Remarks' => $remark];
        };

        foreach ($records as $r) {
            $name = $r->employee_name;
            if ((float) $r->net_salary <= 0) {
                $add($name, 'Negative/zero net salary', 'Net salary is ' . $this->n($r->net_salary));
            }
            if ((float) $r->net_salary > (float) $r->total_earnings) {
                $add($name, 'Net exceeds gross', 'Net ' . $this->n($r->net_salary) . ' > gross ' . $this->n($r->total_earnings));
            }
            if ((float) $r->total_deductions > (float) $r->total_earnings) {
                $add($name, 'Deductions exceed earnings', 'Deductions ' . $this->n($r->total_deductions) . ' > earnings ' . $this->n($r->total_earnings));
            }
            if ($r->payment_mode === 'Bank' && empty($r->account_no)) {
                $add($name, 'Missing bank details', 'Paid by bank but no account number on file');
            }
            if ((int) ($r->present_days ?? 0) === 0 && (float) $r->net_salary > 0) {
                $add($name, 'Paid with no attendance', 'Zero present days but net ' . $this->n($r->net_salary));
            }
            if (strtolower((string) $r->ewt_status) === 'yes' && (float) ($r->ewt ?? 0) == 0) {
                $add($name, 'EWT expected but zero', 'EWT-eligible employee has no EWT deducted');
            }
            if ((float) ($r->emp_pension ?? 0) > 0 && (float) ($r->ded_pension ?? 0) == 0) {
                $add($name, 'Pension expected but zero', 'Pension-eligible employee has no pension deducted');
            }
            if ((float) ($r->total_ot ?? 0) > self::OT_HOURS_THRESHOLD) {
                $add($name, 'Abnormal overtime', $this->n($r->total_ot) . ' OT hours exceeds threshold of ' . self::OT_HOURS_THRESHOLD);
            }
            if ((float) $r->total_earnings == 0) {
                $add($name, 'Zero gross', 'Processed payslip with zero earnings');
            }
            if (isset($prevNet[$r->employee_id]) && (float) $prevNet[$r->employee_id] > 0) {
                $swing = abs((float) $r->net_salary - (float) $prevNet[$r->employee_id]) / (float) $prevNet[$r->employee_id] * 100;
                if ($swing > self::PAY_SWING_PCT) {
                    $add($name, 'Large pay swing vs last run', round($swing, 1) . '% change (prev ' . $this->n($prevNet[$r->employee_id]) . ' → now ' . $this->n($r->net_salary) . ')');
                }
            }
        }

        return ['columns' => ['Employee Name', 'Exception Type', 'Remarks'], 'rows' => $rows];
    }

    /** #32 Payroll Audit Trail. */
    public function payrollAuditTrail(array $filters): array
    {
        $pid = $this->resolvePayrollId($filters);
        $pay = DB::table('payroll')->where('id', $pid)->where('resort_id', $this->resort->resort_id)->first();
        $label = $pay ? $this->periodLabel($pay) : 'N/A';

        $rows = DB::table('payroll_approvals')->where('payroll_id', $pid)
            ->orderBy('step_order')->get()
            ->map(fn($a) => [
                'Payroll Period' => $label,
                'Approval Step'  => $a->role_title,
                'Approved By'    => $a->approver_name ?? '—',
                'Status'         => ucfirst($a->status),
                'Approved Date'  => $a->approved_at ? Carbon::parse($a->approved_at)->format('d M Y H:i') : '—',
            ])->all();

        return ['columns' => ['Payroll Period', 'Approval Step', 'Approved By', 'Status', 'Approved Date'], 'rows' => $rows];
    }

    /** #34 Local vs Expat Payroll Summary. */
    public function localVsExpat(array $filters): array
    {
        $pid = $this->resolvePayrollId($filters);
        $records = $this->basePayslip($pid, $filters)->get(['e.nationality', 'pr.total_earnings', 'pr.net_salary']);

        $buckets = ['Local' => ['c' => 0, 'g' => 0, 'n' => 0], 'Expatriate' => ['c' => 0, 'g' => 0, 'n' => 0]];
        foreach ($records as $r) {
            $key = ($r->nationality === 'Maldivian') ? 'Local' : 'Expatriate';
            $buckets[$key]['c']++;
            $buckets[$key]['g'] += (float) $r->total_earnings;
            $buckets[$key]['n'] += (float) $r->net_salary;
        }

        $rows = [];
        foreach ($buckets as $type => $b) {
            $rows[] = [
                'Employee Type'  => $type,
                'Employee Count' => $b['c'],
                'Gross Salary'   => $this->n($b['g']),
                'Net Salary'     => $this->n($b['n']),
                'Average Salary' => $this->n($b['c'] ? $b['n'] / $b['c'] : 0),
            ];
        }

        return ['columns' => ['Employee Type', 'Employee Count', 'Gross Salary', 'Net Salary', 'Average Salary'], 'rows' => $rows];
    }

    /** #35 Payroll Executive Summary. */
    public function executiveSummary(array $filters): array
    {
        $pid = $this->resolvePayrollId($filters);

        $r = $this->basePayslip($pid, $filters)->selectRaw(
            'COUNT(*) emp, SUM(pr.total_earnings) gross, SUM(pr.net_salary) net,
             SUM(pr.regularOTPay + pr.holidayOTPay) ot, SUM(pr.service_charge) sc, SUM(pr.total_deductions) ded'
        )->first();

        $scoped = Common::getScopedDepartmentIds();
        $d = DB::table('payroll_deductions as pd')
            ->join('employees as e', 'e.id', '=', 'pd.employee_id')
            ->where('pd.payroll_id', $pid)
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))
            ->when($filters['department'], fn($q) => $q->where('e.Dept_id', $filters['department']))
            ->selectRaw('SUM(pd.pension) pension, SUM(pd.ewt) ewt')->first();

        return [
            'columns' => ['Employee Count', 'Gross Payroll', 'Net Payroll', 'Total OT', 'Total Service Charge', 'Total Pension', 'Total Tax', 'Total Deductions'],
            'rows'    => [[
                'Employee Count'      => (int) ($r->emp ?? 0),
                'Gross Payroll'       => $this->n($r->gross ?? 0),
                'Net Payroll'         => $this->n($r->net ?? 0),
                'Total OT'            => $this->n($r->ot ?? 0),
                'Total Service Charge'=> $this->n($r->sc ?? 0),
                'Total Pension'       => $this->n($d->pension ?? 0),
                'Total Tax'           => $this->n($d->ewt ?? 0),
                'Total Deductions'    => $this->n($r->ded ?? 0),
            ]],
        ];
    }
}
