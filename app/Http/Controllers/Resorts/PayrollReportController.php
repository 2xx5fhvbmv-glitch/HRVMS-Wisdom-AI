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
            'upcoming_projection'    => ['name' => 'Upcoming Payroll Projection', 'description' => 'Estimated payroll for a draft/in-progress run before it is finalised.', 'filters' => ['payroll', 'department'], 'handler' => 'upcomingProjection'],
            'payroll_comparison'     => ['name' => 'Payroll Comparison', 'description' => 'Compare payroll cost between two periods.', 'filters' => ['from_payroll', 'to_payroll'], 'handler' => 'payrollComparison'],
            'cost_by_department'     => ['name' => 'Payroll Cost by Department', 'description' => 'Payroll expenditure per department.', 'filters' => ['payroll', 'department'], 'handler' => 'costByDepartment'],
            'cost_by_designation'    => ['name' => 'Payroll Cost by Designation', 'description' => 'Payroll expenditure per designation.', 'filters' => ['payroll', 'position'], 'handler' => 'costByDesignation'],
            'payment_distribution'   => ['name' => 'Payroll Distribution (Bank vs Cash)', 'description' => 'How salaries are split between bank and cash.', 'filters' => ['payroll'], 'handler' => 'paymentDistribution'],
            'bank_transfer'          => ['name' => 'Bank Transfer Report', 'description' => 'Employees paid by bank transfer.', 'filters' => ['payroll', 'bank'], 'handler' => 'bankTransfer'],
            'cash_payment'           => ['name' => 'Cash Payment Report', 'description' => 'Employees paid in cash.', 'filters' => ['payroll'], 'handler' => 'cashPayment'],
            'gross_salary'           => ['name' => 'Gross Salary Report', 'description' => 'Gross salary earned per employee.', 'filters' => ['payroll'], 'handler' => 'grossSalary'],
            'net_salary'             => ['name' => 'Net Salary Report', 'description' => 'Final payable salary after deductions.', 'filters' => ['payroll'], 'handler' => 'netSalary'],
            'allowance_report'       => ['name' => 'Allowance Report', 'description' => 'Allowances paid during the period.', 'filters' => ['payroll', 'allowance_type', 'employee'], 'handler' => 'allowanceReport'],
            'deduction_report'       => ['name' => 'Deduction Report', 'description' => 'Deductions made during payroll processing.', 'filters' => ['payroll', 'deduction_type', 'employee'], 'handler' => 'deductionReport'],
            'service_charge_dist'    => ['name' => 'Service Charge Distribution', 'description' => 'Service charge per employee.', 'filters' => ['payroll'], 'handler' => 'serviceChargeDistribution'],
            'service_charge_trend'   => ['name' => 'Service Charge Trend', 'description' => 'Monthly service charge trend.', 'filters' => ['year'], 'handler' => 'serviceChargeTrend'],
            'avg_service_charge'     => ['name' => 'Average Service Charge', 'description' => 'Average service charge per employee by department.', 'filters' => ['year', 'department', 'month'], 'handler' => 'averageServiceCharge'],
            'overtime_summary'       => ['name' => 'Overtime Summary', 'description' => 'Overtime hours and pay per employee for the period.', 'filters' => ['payroll'], 'handler' => 'overtimeSummary'],
            'top_overtime'           => ['name' => 'Top Overtime Employees', 'description' => 'Highest overtime payments.', 'filters' => ['payroll'], 'handler' => 'topOvertime'],
            'overtime_trend'         => ['name' => 'Overtime Trend', 'description' => 'Monthly overtime hours and cost.', 'filters' => ['year', 'month'], 'handler' => 'overtimeTrend'],
            'pension_contribution'   => ['name' => 'Pension Contribution Report', 'description' => 'Employee and employer pension contributions.', 'filters' => ['payroll'], 'handler' => 'pensionContribution'],
            'annual_pension'         => ['name' => 'Annual Pension Summary', 'description' => 'Pension contributions accumulated during the year.', 'filters' => ['year', 'month'], 'handler' => 'annualPension'],
            'ewt_report'             => ['name' => 'Employee Withholding Tax (EWT) Report', 'description' => 'EWT deducted during the period.', 'filters' => ['payroll'], 'handler' => 'ewtReport'],
            'annual_tax_summary'     => ['name' => 'Annual Tax Summary', 'description' => 'Total tax deducted per employee for the year.', 'filters' => ['year'], 'handler' => 'annualTaxSummary'],
            'ff_settlement_register' => ['name' => 'Full & Final Settlement Register', 'description' => 'Employees undergoing final settlement.', 'filters' => ['year', 'settlement_status', 'duration'], 'handler' => 'ffSettlementRegister'],
            'ff_settlement_pending'  => ['name' => 'Pending Full & Final Settlement', 'description' => 'Outstanding (not finalized) settlements.', 'filters' => ['duration'], 'handler' => 'ffSettlementPending'],
            'tuckshop_deduction'     => ['name' => 'Tuck Shop Deduction Summary', 'description' => 'Tuck shop deductions per employee.', 'filters' => ['payroll'], 'handler' => 'tuckshopDeduction'],
            'tuckshop_payable'       => ['name' => 'Tuck Shop Outstanding Payable', 'description' => 'Outstanding amount payable per tuck shop vendor.', 'filters' => ['payroll'], 'handler' => 'tuckshopPayable'],
            'tuckshop_purchases'     => ['name' => 'Tuck Shop Purchase Details', 'description' => 'Itemised tuck shop purchases.', 'filters' => ['payroll'], 'handler' => 'tuckshopPurchases'],
            'salary_advance'         => ['name' => 'Salary Advance Report', 'description' => 'Advances issued and recovered.', 'filters' => ['department', 'duration'], 'handler' => 'salaryAdvance'],
            'payroll_exceptions'     => ['name' => 'Payroll Exceptions Report', 'description' => 'Anomalies to review before approval.', 'filters' => ['payroll'], 'handler' => 'payrollExceptions'],
            'payroll_audit_trail'    => ['name' => 'Payroll Audit Trail', 'description' => 'Processing history and approvals.', 'filters' => ['payroll'], 'handler' => 'payrollAuditTrail'],
            'processing_status'      => ['name' => 'Payroll Processing Status', 'description' => 'Processing status of payroll runs.', 'filters' => ['payroll'], 'handler' => 'processingStatus'],
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

        $reports = collect($this->registry())->map(function ($r, $key) {
            $filters = $r['filters'];
            // Period-based reports also accept an independent Year + From/To date
            // window, so the Payroll Period can be left blank (see resolvePayrollId).
            if (in_array('payroll', $filters, true)) {
                $filters = array_values(array_unique(array_merge($filters, ['year', 'duration'])));
            }
            return ['key' => $key, 'name' => $r['name'], 'description' => $r['description'], 'filters' => $filters];
        })->values();

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

        $positions = DB::table('resort_positions')->where('resort_id', $resortId)
            ->when($scoped !== null, fn($q) => $q->whereIn('dept_id', $scoped))
            ->orderBy('position_title')->get(['id', 'position_title']);

        $employees = DB::table('employees as e')->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->where('e.resort_id', $resortId)->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))
            ->orderBy('ra.first_name')->get(['e.id', DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as name")]);

        $months = collect(range(1, 12))->map(fn($m) => ['value' => $m, 'label' => Carbon::create()->month($m)->format('F')]);

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
            'page_title', 'reports', 'payrolls', 'departments', 'positions', 'employees', 'months', 'years',
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
            'position'          => $request->input('position') ?: null,
            'employee'          => $request->input('employee') ?: null,
            'month'             => $request->input('month') ?: null,
            'year'              => $request->input('year') ?: null,
            'allowance_type'    => $request->input('allowance_type') ?: null,
            'deduction_type'    => $request->input('deduction_type') ?: null,
            'bank'              => $request->input('bank') ?: null,
            'settlement_status' => $request->input('settlement_status') ?: null,
            'from_date'         => $request->input('from_date') ?: null,
            'to_date'           => $request->input('to_date') ?: null,
        ];
    }

    /** Apply the optional duration (from/to date) to a query's date column. */
    private function applyDuration($q, array $filters, string $col)
    {
        return $q->when($filters['from_date'] ?? null, fn($x) => $x->whereDate($col, '>=', $filters['from_date']))
                 ->when($filters['to_date'] ?? null, fn($x) => $x->whereDate($col, '<=', $filters['to_date']));
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
            'rows'        => $this->appendTotalsRow($res['columns'], $res['rows']),
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

    /** Money formatter — payroll figures are stored in USD (base currency). */
    private function n($v): string
    {
        return '$' . number_format((float) $v, 2);
    }

    /** Non-currency numeric formatter (e.g. overtime hours). */
    private function hrs($v): string
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

    /** The payroll run rows for a resolved id set, ordered by start date. */
    private function payrollRuns($pids)
    {
        $pids = array_values(array_filter((array) $pids));
        if (empty($pids)) return collect();
        return DB::table('payroll')->where('resort_id', $this->resort->resort_id)
            ->whereIn('id', $pids)->orderBy('start_date')->get();
    }

    /**
     * Human period label for a resolved id set: a single run shows its own
     * period; multiple aggregated runs show the spanning date range + count.
     */
    private function periodLabelFor($pids): string
    {
        $runs = $this->payrollRuns($pids);
        if ($runs->isEmpty()) return 'N/A';
        if ($runs->count() === 1) return $this->periodLabel($runs->first());
        return Carbon::parse($runs->min('start_date'))->format('d M Y') . ' – '
            . Carbon::parse($runs->max('end_date'))->format('d M Y')
            . ' (' . $runs->count() . ' runs)';
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

    /**
     * Resolve report filters to the SET of payroll run ids to include (returns
     * an array so reports can span an independent window):
     *   - an explicit Payroll Period always wins → just that run;
     *   - otherwise a Year and/or From/To date window aggregates across every
     *     run whose period overlaps it (Payroll Period left blank);
     *   - with nothing chosen, default to the latest run that has payslips
     *     (the original single-period behaviour), so the initial view isn't empty.
     */
    private function resolvePayrollId(array $filters): array
    {
        $rid = $this->resort->resort_id;

        if (!empty($filters['payroll'])) return [(int) $filters['payroll']];

        $from = $filters['from_date'] ?? null;
        $to   = $filters['to_date'] ?? null;
        $year = $filters['year'] ?? null;

        if ($from || $to || $year) {
            $q = DB::table('payroll')->where('resort_id', $rid);
            if ($year) $q->whereRaw('YEAR(start_date) = ?', [$year]);
            // Period overlaps the window: run.start_date <= to AND run.end_date >= from.
            if ($to)   $q->whereDate('start_date', '<=', $to);
            if ($from) $q->whereDate('end_date', '>=', $from);
            return $q->orderBy('start_date')->pluck('id')->map(fn($v) => (int) $v)->all();
        }

        $latest = DB::table('payroll as p')
            ->where('p.resort_id', $rid)
            ->whereExists(fn($q) => $q->select(DB::raw(1))->from('payroll_reviews')
                ->whereColumn('payroll_reviews.payroll_id', 'p.id'))
            ->orderBy('p.start_date', 'desc')->first()
            ?: DB::table('payroll')->where('resort_id', $rid)
                ->orderBy('start_date', 'desc')->first();

        return $latest ? [(int) $latest->id] : [];
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
            ->whereIn('pr.payroll_id', (array) $payrollId)
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))
            ->when($filters['department'] ?? null, fn($q) => $q->where('e.Dept_id', $filters['department']));
    }

    /* ---------------------------------------------------------------- reports */

    /** #1 Payroll Summary. */
    public function payrollSummary(array $filters): array
    {
        $pid = $this->resolvePayrollId($filters);
        $t = $this->basePayslip($pid, $filters)->selectRaw(
            'COUNT(*) emp, SUM(pr.total_earnings) gross, SUM(pr.total_deductions) ded, SUM(pr.net_salary) net'
        )->first();

        return [
            'columns' => ['Payroll Period', 'Total Employees', 'Gross Salary', 'Total Deductions', 'Net Salary', 'Total Payroll Cost'],
            'rows'    => [[
                'Payroll Period'     => $this->periodLabelFor($pid),
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
                'Pension'       => $this->n($r->pension ?? 0),
                'Tax'           => $this->n($r->ewt ?? 0),
                'Deduction'     => $this->n($r->total_deductions),
                'Net Salary'    => $this->n($r->net_salary),
            ])->all();

        return [
            // Deductions positioned after Pension + Tax, per the requirements doc.
            'columns' => ['Employee ID', 'Employee Name', 'Department', 'Designation', 'Basic Salary', 'Allowances', 'OT', 'Service Charge', 'Gross Salary', 'Pension', 'Tax', 'Deduction', 'Net Salary'],
            'rows'    => $rows,
        ];
    }

    /** #4 Payroll Comparison. */
    public function payrollComparison(array $filters): array
    {
        $payA = $filters['from_payroll'] ? DB::table('payroll')->where('id', $filters['from_payroll'])->where('resort_id', $this->resort->resort_id)->first() : null;
        $payB = $filters['to_payroll'] ? DB::table('payroll')->where('id', $filters['to_payroll'])->where('resort_id', $this->resort->resort_id)->first() : null;
        if (!$payA || !$payB) {
            return ['columns' => ['Component', 'Month A', 'Month B', 'Difference', '% Change'],
                'rows' => [['Component' => 'Select two payroll periods to compare.', 'Month A' => '', 'Month B' => '', 'Difference' => '', '% Change' => '']]];
        }

        $totals = fn($id) => $this->basePayslip($id, $filters)
            ->selectRaw('SUM(pr.total_earnings) gross, SUM(pr.total_deductions) ded, SUM(pr.net_salary) net')->first();
        $a = $totals($payA->id);
        $b = $totals($payB->id);
        $labelA = $this->periodLabel($payA);
        $labelB = $this->periodLabel($payB);

        // One row per core component: A value, B value, absolute diff (+/-), % change (+/-).
        $components = [
            'Gross Salary'     => ['gross'],
            'Total Deductions' => ['ded'],
            'Net Salary'       => ['net'],
        ];
        $rows = [];
        foreach ($components as $label => [$key]) {
            $va = (float) ($a->$key ?? 0);
            $vb = (float) ($b->$key ?? 0);
            $diff = $vb - $va;
            $pctStr = $va == 0 ? '—' : ($diff >= 0 ? '+' : '') . round($diff / $va * 100, 1) . '%';
            $rows[] = [
                'Component' => $label,
                $labelA     => $this->n($va),
                $labelB     => $this->n($vb),
                'Difference'=> ($diff >= 0 ? '+' : '-') . $this->n(abs($diff)),
                '% Change'  => $pctStr,
            ];
        }

        return ['columns' => ['Component', $labelA, $labelB, 'Difference', '% Change'], 'rows' => $rows];
    }

    /** #5 Payroll Cost by Department. */
    public function costByDepartment(array $filters): array
    {
        $pid = $this->resolvePayrollId($filters);
        $raw = $this->basePayslip($pid, $filters)
            ->groupBy('e.Dept_id', 'd.name')
            ->select('d.name as dept', DB::raw('COUNT(*) emp'), DB::raw('SUM(pr.total_earnings) gross'), DB::raw('SUM(pr.total_deductions) ded'), DB::raw('SUM(pr.net_salary) net'))
            ->get();
        $grand = $raw->sum('gross') ?: 0;
        $rows = $raw->map(fn($r) => [
            'Department'              => $r->dept ?? 'N/A',
            'Total Employees'         => (int) $r->emp,
            'Gross Salary'            => $this->n($r->gross),
            'Deductions'              => $this->n($r->ded),
            'Net Payroll'             => $this->n($r->net),
            'Percentage of Total Payroll' => $this->pct($r->gross, $grand),
        ])->all();

        // The generic totals row blanks percentage columns; the department shares
        // add up to the whole payroll, so show 100% explicitly in the footer.
        if (count($rows) > 1) {
            $rows[] = [
                'Department' => 'Total', 'Total Employees' => (int) $raw->sum('emp'),
                'Gross Salary' => $this->n($grand), 'Deductions' => $this->n($raw->sum('ded')),
                'Net Payroll' => $this->n($raw->sum('net')), 'Percentage of Total Payroll' => $grand ? '100%' : '0%',
            ];
        }

        return [
            'columns' => ['Department', 'Total Employees', 'Gross Salary', 'Deductions', 'Net Payroll', 'Percentage of Total Payroll'],
            'rows'    => $rows,
        ];
    }

    /** #6 Payroll Cost by Designation. */
    public function costByDesignation(array $filters): array
    {
        $pid = $this->resolvePayrollId($filters);
        $rows = $this->basePayslip($pid, $filters)
            ->when($filters['position'] ?? null, fn($q) => $q->where('e.Position_id', $filters['position']))
            ->groupBy('e.Position_id', 'p.position_title')
            ->select('p.position_title', DB::raw('COUNT(*) emp'),
                DB::raw('SUM(pr.earnings_basic) basic'), DB::raw('SUM(pr.earnings_allowance) allow'),
                DB::raw('SUM(pr.total_earnings) cost'))
            ->orderByDesc(DB::raw('SUM(pr.total_earnings)'))
            ->get()
            ->map(fn($r) => [
                'Designation'       => $r->position_title ?? 'N/A',
                'Employee Count'    => (int) $r->emp,
                'Basic Salary'      => $this->n($r->basic ?? 0),
                'Total Salary'      => $this->n(($r->basic ?? 0) + ($r->allow ?? 0)),
                'Total Allowances'  => $this->n($r->allow ?? 0),
                'Total Payroll Cost'=> $this->n($r->cost),
            ])->all();

        return ['columns' => ['Designation', 'Employee Count', 'Basic Salary', 'Total Salary', 'Total Allowances', 'Total Payroll Cost'], 'rows' => $rows];
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
            ->get(['pr.Emp_id', $this->nameExpr(), 'p.position_title', 'd.name as dept', 'pr.net_salary'])
            ->map(fn($r) => [
                'Employee ID'       => $r->Emp_id,
                'Employee Name'     => $r->employee_name,
                'Employee Position' => $r->position_title ?? 'N/A',
                'Employee Department'=> $r->dept ?? 'N/A',
                'Net Salary'        => $this->n($r->net_salary),
            ])->all();

        return ['columns' => ['Employee ID', 'Employee Name', 'Employee Position', 'Employee Department', 'Net Salary'], 'rows' => $rows];
    }

    /** #11 Gross Salary Report. */
    public function grossSalary(array $filters): array
    {
        $pid = $this->resolvePayrollId($filters);
        $rows = $this->basePayslip($pid, $filters)
            ->orderBy('ra.first_name')
            ->get(['e.Emp_id', $this->nameExpr(), 'p.position_title', 'pr.earnings_basic', 'pr.earnings_allowance', 'pr.earnings_overtime', 'pr.regularOTPay', 'pr.holidayOTPay', 'pr.service_charge', 'pr.total_earnings'])
            ->map(fn($r) => [
                'Employee ID'       => $r->Emp_id ?: 'N/A',
                'Employee Name'     => $r->employee_name,
                'Employee Position' => $r->position_title ?? 'N/A',
                'Basic Salary'   => $this->n($r->earnings_basic),
                'Allowances'     => $this->n($r->earnings_allowance),
                'OT'             => $this->n($r->earnings_overtime ?: ($r->regularOTPay + $r->holidayOTPay)),
                'Service Charge' => $this->n($r->service_charge),
                'Gross Salary'   => $this->n($r->total_earnings),
            ])->all();

        return ['columns' => ['Employee ID', 'Employee Name', 'Employee Position', 'Basic Salary', 'Allowances', 'OT', 'Service Charge', 'Gross Salary'], 'rows' => $rows];
    }

    /** #12 Net Salary Report. */
    public function netSalary(array $filters): array
    {
        $pid = $this->resolvePayrollId($filters);
        $rows = $this->basePayslip($pid, $filters)
            ->orderBy('ra.first_name')
            ->get(['e.Emp_id', $this->nameExpr(), 'p.position_title', 'pr.total_earnings', 'pr.total_deductions', 'pr.net_salary'])
            ->map(fn($r) => [
                'Employee ID'       => $r->Emp_id ?: 'N/A',
                'Employee Name'     => $r->employee_name,
                'Employee Position' => $r->position_title ?? 'N/A',
                'Gross Salary'      => $this->n($r->total_earnings),
                'Total Deduction'   => $this->n($r->total_deductions),
                'Net Salary'        => $this->n($r->net_salary),
            ])->all();

        return ['columns' => ['Employee ID', 'Employee Name', 'Employee Position', 'Gross Salary', 'Total Deduction', 'Net Salary'], 'rows' => $rows];
    }

    /** #13 Allowance Report. */
    public function allowanceReport(array $filters): array
    {
        $pid = $this->resolvePayrollId($filters);
        $scoped = Common::getScopedDepartmentIds();
        $records = DB::table('payroll_review_allowances as a')
            ->join('payroll_reviews as pr', 'pr.id', '=', 'a.payroll_review_id')
            ->join('payroll as pay', 'pay.id', '=', 'pr.payroll_id')
            ->join('employees as e', 'e.id', '=', 'pr.employee_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->leftJoin('resort_positions as p', 'p.id', '=', 'e.Position_id')
            ->where('pay.resort_id', $this->resort->resort_id)
            ->whereIn('pr.payroll_id', (array) $pid)
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))
            ->when($filters['department'], fn($q) => $q->where('e.Dept_id', $filters['department']))
            ->when($filters['employee'] ?? null, fn($q) => $q->where('e.id', $filters['employee']))
            ->when($filters['allowance_type'], fn($q) => $q->where('a.allowance_type', $filters['allowance_type']))
            ->orderBy('ra.first_name')
            ->get(['e.id as eid', 'e.Emp_id', $this->nameExpr(), 'p.position_title', 'a.allowance_type', 'a.amount']);

        // De-duplicate: one row per employee, allowance types listed together.
        $rows = $records->groupBy('eid')->map(function ($grp) {
            $first = $grp->first();
            // Collapse repeated allowance types (across runs) into one entry per type.
            $types = $grp->groupBy('allowance_type')
                ->map(fn($g, $t) => $t . ' (' . $this->n($g->sum('amount')) . ')')->implode(', ');
            return [
                'Employee ID'       => $first->Emp_id ?: 'N/A',
                'Employee Name'     => $first->employee_name,
                'Employee Position' => $first->position_title ?? 'N/A',
                'Allowances'        => $types ?: 'N/A',
                'Total Allowance'   => $this->n($grp->sum('amount')),
            ];
        })->values()->all();

        return ['columns' => ['Employee ID', 'Employee Name', 'Employee Position', 'Allowances', 'Total Allowance'], 'rows' => $rows];
    }

    /** #14 Deduction Report. */
    public function deductionReport(array $filters): array
    {
        $pid = $this->resolvePayrollId($filters);
        $cols = $this->deductionColumns();
        $wanted = $filters['deduction_type'] && isset($cols[$filters['deduction_type']])
            ? [$filters['deduction_type'] => $cols[$filters['deduction_type']]] : $cols;

        $records = $this->basePayslip($pid, $filters)
            ->when($filters['employee'] ?? null, fn($q) => $q->where('e.id', $filters['employee']))
            ->join('payroll_deductions as pd', function ($j) {
                $j->on('pd.payroll_id', '=', 'pr.payroll_id')->on('pd.employee_id', '=', 'pr.employee_id');
            })
            ->orderBy('ra.first_name')
            ->get(array_merge(['e.Emp_id', $this->nameExpr(), 'p.position_title'], array_map(fn($c) => "pd.$c", array_values($wanted))));

        $rows = [];
        foreach ($records as $r) {
            foreach ($wanted as $label => $col) {
                if ((float) $r->$col == 0) continue; // only non-zero deductions
                $rows[] = [
                    'Employee ID'       => $r->Emp_id ?: 'N/A',
                    'Employee Name'     => $r->employee_name,
                    'Employee Position' => $r->position_title ?? 'N/A',
                    'Deduction Type'    => $label,
                    'Amount'            => $this->n($r->$col),
                ];
            }
        }

        return ['columns' => ['Employee ID', 'Employee Name', 'Employee Position', 'Deduction Type', 'Amount'], 'rows' => $rows];
    }

    /** #15 Service Charge Distribution. */
    public function serviceChargeDistribution(array $filters): array
    {
        $pid = $this->resolvePayrollId($filters);
        $rows = $this->basePayslip($pid, $filters)
            ->orderBy('d.name')->orderBy('ra.first_name')
            ->get(['e.Emp_id', $this->nameExpr(), 'p.position_title', 'd.name as dept', 'pr.service_charge'])
            ->map(fn($r) => [
                'Employee ID'         => $r->Emp_id ?: 'N/A',
                'Employee Name'       => $r->employee_name,
                'Employee Position'   => $r->position_title ?? 'N/A',
                'Department'          => $r->dept ?? 'N/A',
                'Service Charge Amount'=> $this->n($r->service_charge),
            ])->all();

        return ['columns' => ['Employee ID', 'Employee Name', 'Employee Position', 'Department', 'Service Charge Amount'], 'rows' => $rows];
    }

    /** #16 Service Charge Trend (by month for a year). */
    public function serviceChargeTrend(array $filters): array
    {
        $year = $filters['year'] ?: Carbon::now()->year;
        // Payroll cycles run 26th→25th, so bucket by the period's END month
        // (e.g. 26 Feb–25 Mar = March) — grouping by start month mislabels cycles
        // and drops the month whose cycle starts in the previous month.
        $raw = DB::table('payroll_reviews as pr')
            ->join('payroll as pay', 'pay.id', '=', 'pr.payroll_id')
            ->where('pay.resort_id', $this->resort->resort_id)
            ->whereRaw('YEAR(pay.end_date) = ?', [$year])
            ->groupBy(DB::raw('MONTH(pay.end_date)'))
            ->select(
                DB::raw('MONTH(pay.end_date) as m'),
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
            ->whereRaw('YEAR(pay.end_date) = ?', [$year])
            ->when($filters['month'] ?? null, fn($q) => $q->whereRaw('MONTH(pay.end_date) = ?', [$filters['month']]))
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
            ->get(['e.Emp_id', $this->nameExpr(), 'p.position_title', 'd.name as dept', 'ta.regular_ot_hours', 'ta.holiday_ot_hours', 'ta.total_ot', 'pr.regularOTPay', 'pr.holidayOTPay'])
            ->map(fn($r) => [
                'Employee ID'     => $r->Emp_id ?: 'N/A',
                'Employee Name'   => $r->employee_name,
                'Employee Position'=> $r->position_title ?? 'N/A',
                'Department'      => $r->dept ?? 'N/A',
                'Normal OT Hours' => $this->hrs($r->regular_ot_hours ?? 0),
                'Friday OT Hours' => 'N/A', // not tracked separately
                'Holiday OT Hours'=> $this->hrs($r->holiday_ot_hours ?? 0),
                'Total OT Hours'  => $this->hrs($r->total_ot ?? 0),
                'Total Amount'    => $this->n($r->regularOTPay + $r->holidayOTPay),
            ])->all();

        return ['columns' => ['Employee ID', 'Employee Name', 'Employee Position', 'Department', 'Normal OT Hours', 'Friday OT Hours', 'Holiday OT Hours', 'Total OT Hours', 'Total Amount'], 'rows' => $rows];
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
            ->get(['e.Emp_id', $this->nameExpr(), 'p.position_title', 'pr.total_earnings', 'pd.ewt'])
            ->map(function ($r) use ($brackets) {
                $bracket = $brackets->first(function ($b) use ($r) {
                    return $r->total_earnings >= $b->min_salary
                        && ($b->max_salary === null || $r->total_earnings <= $b->max_salary);
                });
                return [
                    'Employee ID'   => $r->Emp_id ?: 'N/A',
                    'Employee Name' => $r->employee_name,
                    'Employee Position'=> $r->position_title ?? 'N/A',
                    'Taxable Income'=> $this->n($r->total_earnings),
                    'Tax Bracket'   => $bracket
                        ? $this->n($bracket->min_salary) . '–' . ($bracket->max_salary === null ? '∞' : $this->n($bracket->max_salary)) . ' @ ' . $bracket->tax_rate . '%'
                        : 'N/A',
                    'EWT Amount'    => $this->n($r->ewt),
                ];
            })->all();

        return ['columns' => ['Employee ID', 'Employee Name', 'Employee Position', 'Taxable Income', 'Tax Bracket', 'EWT Amount'], 'rows' => $rows];
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
            ->leftJoin('resort_positions as p', 'p.id', '=', 'e.Position_id')
            ->where('pay.resort_id', $this->resort->resort_id)
            ->whereRaw('YEAR(pay.start_date) = ?', [$year])
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))
            ->groupBy('e.id', 'e.Emp_id', 'ra.first_name', 'ra.last_name', 'p.position_title')
            ->havingRaw('SUM(pd.ewt) > 0')
            ->select('e.Emp_id', $this->nameExpr(), 'p.position_title', DB::raw('SUM(pr.total_earnings) gross'), DB::raw('SUM(pd.ewt) ewt'))
            ->orderBy('ra.first_name')->get()
            ->map(fn($r) => [
                'Employee ID'         => $r->Emp_id ?: 'N/A',
                'Employee Name'       => $r->employee_name,
                'Employee Position'   => $r->position_title ?? 'N/A',
                'Gross Taxable Income'=> $this->n($r->gross),
                'Total EWT'           => $this->n($r->ewt),
            ])->all();

        return ['columns' => ['Employee ID', 'Employee Name', 'Employee Position', 'Gross Taxable Income', 'Total EWT'], 'rows' => $rows];
    }

    /** #25 Full & Final Settlement Register. */
    public function ffSettlementRegister(array $filters): array
    {
        $rows = $this->settlementQuery($filters)
            ->when($filters['settlement_status'], fn($q) => $q->where('fs.status', $filters['settlement_status']))
            ->when($filters['year'], fn($q) => $q->whereRaw('YEAR(fs.last_working_date) = ?', [$filters['year']]))
            ->orderByDesc('fs.last_working_date')
            ->get([
                'e.Emp_id', $this->nameExpr(), 'p.position_title', 'd.name as dept', 'fs.last_working_date', 'fs.basic_salary', 'fs.leave_encashment',
                'fs.total_deductions', 'fs.net_pay', 'fs.status',
            ])
            ->map(fn($r) => [
                'Employee ID'           => $r->Emp_id ?: 'N/A',
                'Employee Name'         => $r->employee_name,
                'Employee Position'     => $r->position_title ?? 'N/A',
                'Employee Department'   => $r->dept ?? 'N/A',
                'Earnings'              => $this->n($r->basic_salary ?? 0),
                'Deduction'             => $this->n($r->total_deductions ?? 0),
                'Leave Encashment'      => $this->n($r->leave_encashment ?? 0),
                'Final Settlement Amount'=> $this->n($r->net_pay ?? 0),
            ])->all();

        return [
            'columns' => ['Employee ID', 'Employee Name', 'Employee Position', 'Employee Department', 'Earnings', 'Deduction', 'Leave Encashment', 'Final Settlement Amount'],
            'rows'    => $rows,
        ];
    }

    /** #26 Pending Full & Final Settlement. */
    public function ffSettlementPending(array $filters): array
    {
        $rows = $this->settlementQuery($filters)
            ->where('fs.status', '<>', 'finalized')
            ->orderByDesc('fs.last_working_date')
            ->get(['e.Emp_id', $this->nameExpr(), 'p.position_title', 'd.name as dept', 'fs.last_working_date', 'fs.net_pay', 'fs.status'])
            ->map(fn($r) => [
                'Employee ID'     => $r->Emp_id ?: 'N/A',
                'Employee Name'   => $r->employee_name,
                'Employee Position'=> $r->position_title ?? 'N/A',
                'Department'      => $r->dept ?? 'N/A',
                'Separation Date' => $r->last_working_date ? Carbon::parse($r->last_working_date)->format('d M Y') : 'N/A',
                'Pending Amount'  => $this->n($r->net_pay ?? 0),
                'Status'          => ucfirst($r->status ?? ''),
            ])->all();

        return ['columns' => ['Employee ID', 'Employee Name', 'Employee Position', 'Department', 'Separation Date', 'Pending Amount', 'Status'], 'rows' => $rows];
    }

    private function settlementQuery(array $filters)
    {
        $scoped = Common::getScopedDepartmentIds();
        return DB::table('final_settlements as fs')
            ->join('employees as e', 'e.id', '=', 'fs.employee_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->leftJoin('resort_departments as d', 'd.id', '=', 'e.Dept_id')
            ->leftJoin('resort_positions as p', 'p.id', '=', 'e.Position_id')
            ->where('e.resort_id', $this->resort->resort_id)
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))
            ->when(true, fn($q) => $this->applyDuration($q, $filters, 'fs.last_working_date'));
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
            ->get(['e.Emp_id', $this->nameExpr(), 'p.position_title', 'd.name as dept', 'pd.staff_shop'])
            ->map(fn($r) => [
                'Employee ID'             => $r->Emp_id ?: 'N/A',
                'Employee Name'           => $r->employee_name,
                'Employee Position'       => $r->position_title ?? 'N/A',
                'Department'              => $r->dept ?? 'N/A',
                'Total Tuck Shop Deduction'=> $this->n($r->staff_shop),
            ])->all();

        return ['columns' => ['Employee ID', 'Employee Name', 'Employee Position', 'Department', 'Total Tuck Shop Deduction'], 'rows' => $rows];
    }

    /** #29 Tuck Shop Purchase Details (within the payroll period's date range). */
    public function tuckshopPurchases(array $filters): array
    {
        $pid = $this->resolvePayrollId($filters);
        $runs = $this->payrollRuns($pid);
        $scoped = Common::getScopedDepartmentIds();

        $q = DB::table('payments as pmt')
            ->join('employees as e', 'e.id', '=', 'pmt.emp_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->leftJoin('products as pr2', 'pr2.id', '=', 'pmt.product_id')
            ->where('e.resort_id', $this->resort->resort_id)
            ->when($scoped !== null, fn($x) => $x->whereIn('e.Dept_id', $scoped))
            ->when($filters['department'], fn($x) => $x->where('e.Dept_id', $filters['department']))
            ->when($filters['employee'] ?? null, fn($x) => $x->where('e.id', $filters['employee']));

        if ($runs->isNotEmpty()) {
            $q->whereBetween('pmt.purchased_date', [$runs->min('start_date'), $runs->max('end_date')]);
        }

        // Consolidate to one row per employee: item list + total quantity + total amount.
        $rows = $q->get(['e.id as eid', 'e.Emp_id', $this->nameExpr(), 'pr2.name as item', 'pmt.quantity', 'pmt.price'])
            ->groupBy('eid')->map(function ($grp) {
                $first = $grp->first();
                $items = $grp->map(fn($x) => $x->item ?? 'Item')->unique()->implode(', ');
                return [
                    'Employee ID'    => $first->Emp_id ?: 'N/A',
                    'Employee Name'  => $first->employee_name,
                    'Total Items'    => $items ?: 'N/A',
                    'Total Quantity' => (int) $grp->sum('quantity'),
                    'Total Amount'   => $this->n($grp->sum('price')),
                ];
            })->values()->all();

        return ['columns' => ['Employee ID', 'Employee Name', 'Total Items', 'Total Quantity', 'Total Amount'], 'rows' => $rows];
    }

    /** #30 Salary Advance Report. */
    public function salaryAdvance(array $filters): array
    {
        $scoped = Common::getScopedDepartmentIds();
        $rows = DB::table('payroll_advance as a')
            ->join('employees as e', 'e.id', '=', 'a.employee_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->leftJoin('resort_positions as p', 'p.id', '=', 'e.Position_id')
            ->leftJoin('resort_departments as d', 'd.id', '=', 'e.Dept_id')
            ->leftJoin(DB::raw('(SELECT payroll_advance_id, SUM(CASE WHEN status = "Paid" THEN amount ELSE 0 END) recovered FROM payroll_recovery_schedule GROUP BY payroll_advance_id) rs'), 'rs.payroll_advance_id', '=', 'a.id')
            ->where('a.resort_id', $this->resort->resort_id)
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))
            ->when($filters['department'], fn($q) => $q->where('e.Dept_id', $filters['department']))
            ->when(true, fn($q) => $this->applyDuration($q, $filters, 'a.request_date'))
            ->orderByDesc('a.request_date')
            ->get(['e.Emp_id', $this->nameExpr(), 'p.position_title', 'd.name as dept', 'a.request_amount', DB::raw('COALESCE(rs.recovered,0) as recovered')])
            ->map(fn($r) => [
                'Employee ID'        => $r->Emp_id ?: 'N/A',
                'Employee Name'      => $r->employee_name,
                'Employee Position'  => $r->position_title ?? 'N/A',
                'Employee Department'=> $r->dept ?? 'N/A',
                'Advance Amount'     => $this->n($r->request_amount),
                'Recovered Amount'   => $this->n($r->recovered),
                'Outstanding Balance'=> $this->n($r->request_amount - $r->recovered),
            ])->all();

        return ['columns' => ['Employee ID', 'Employee Name', 'Employee Position', 'Employee Department', 'Advance Amount', 'Recovered Amount', 'Outstanding Balance'], 'rows' => $rows];
    }

    /** #31 Payroll Exceptions Report — derived anomalies (no stored table). */
    public function payrollExceptions(array $filters): array
    {
        $pid = $this->resolvePayrollId($filters);
        $runs = $this->payrollRuns($pid);

        // Previous run (immediately before the earliest run in scope) for pay-swing comparison.
        $prevNet = [];
        if ($runs->isNotEmpty()) {
            $prev = DB::table('payroll')->where('resort_id', $this->resort->resort_id)
                ->where('start_date', '<', $runs->min('start_date'))->orderByDesc('start_date')->first();
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
                'pr.employee_id', 'e.Emp_id', $this->nameExpr(), 'p.position_title',
                'pr.total_earnings', 'pr.total_deductions', 'pr.net_salary',
                'e.payment_mode', 'bd.account_no', 'e.ewt_status', 'e.pension as emp_pension',
                'pd.ewt', 'pd.pension as ded_pension',
                'ta.present_days', 'ta.total_ot',
            ]);

        $rows = [];
        $add = function ($r, $type, $remark) use (&$rows) {
            $rows[] = [
                'Employee ID'    => $r->Emp_id ?: 'N/A',
                'Employee Name'  => $r->employee_name,
                'Employee Position' => $r->position_title ?? 'N/A',
                'Exception Type' => $type,
                'Remarks'        => $remark,
            ];
        };

        foreach ($records as $r) {
            $name = $r->employee_name;
            if ((float) $r->net_salary <= 0) {
                $add($r, 'Negative/zero net salary', 'Net salary is ' . $this->n($r->net_salary));
            }
            if ((float) $r->net_salary > (float) $r->total_earnings) {
                $add($r, 'Net exceeds gross', 'Net ' . $this->n($r->net_salary) . ' > gross ' . $this->n($r->total_earnings));
            }
            if ((float) $r->total_deductions > (float) $r->total_earnings) {
                $add($r, 'Deductions exceed earnings', 'Deductions ' . $this->n($r->total_deductions) . ' > earnings ' . $this->n($r->total_earnings));
            }
            if ($r->payment_mode === 'Bank' && empty($r->account_no)) {
                $add($r, 'Missing bank details', 'Paid by bank but no account number on file');
            }
            if ((int) ($r->present_days ?? 0) === 0 && (float) $r->net_salary > 0) {
                $add($r, 'Paid with no attendance', 'Zero present days but net ' . $this->n($r->net_salary));
            }
            if (strtolower((string) $r->ewt_status) === 'yes' && (float) ($r->ewt ?? 0) == 0) {
                $add($r, 'EWT expected but zero', 'Employee is flagged EWT-eligible (withholding tax applies once gross exceeds the tax-free threshold), and this run\'s gross is ' . $this->n($r->total_earnings) . ', yet no EWT was withheld — verify the tax bracket mapping or a manual override for this payslip.');
            }
            if ((float) ($r->emp_pension ?? 0) > 0 && (float) ($r->ded_pension ?? 0) == 0) {
                $expected = round(((float) $r->total_earnings) * 0.07, 2);
                $add($r, 'Pension expected but zero', 'Employee is enrolled in the pension scheme (7% MPRF employee contribution), so ≈' . $this->n($expected) . ' was expected on a gross of ' . $this->n($r->total_earnings) . ', but 0 was deducted — verify pension enrolment status and that the contribution was processed for this cycle.');
            }
            if ((float) ($r->total_ot ?? 0) > self::OT_HOURS_THRESHOLD) {
                $add($r, 'Abnormal overtime', $this->n($r->total_ot) . ' OT hours exceeds threshold of ' . self::OT_HOURS_THRESHOLD);
            }
            if ((float) $r->total_earnings == 0) {
                $add($r, 'Zero gross', 'Processed payslip with zero earnings');
            }
            if (isset($prevNet[$r->employee_id]) && (float) $prevNet[$r->employee_id] > 0) {
                $swing = abs((float) $r->net_salary - (float) $prevNet[$r->employee_id]) / (float) $prevNet[$r->employee_id] * 100;
                if ($swing > self::PAY_SWING_PCT) {
                    $add($r, 'Large pay swing vs last run', round($swing, 1) . '% change (prev ' . $this->n($prevNet[$r->employee_id]) . ' → now ' . $this->n($r->net_salary) . ')');
                }
            }
        }

        return ['columns' => ['Employee ID', 'Employee Name', 'Employee Position', 'Exception Type', 'Remarks'], 'rows' => $rows];
    }

    /** #32 Payroll Audit Trail. */
    public function payrollAuditTrail(array $filters): array
    {
        $pid = $this->resolvePayrollId($filters);
        $label = $this->periodLabelFor($pid);

        // Who submitted the payroll — the first ("submitted"/initiating) approval step's
        // actor if recorded; the payroll table itself stores no submitter column.
        $submittedBy = DB::table('payroll_approvals')->where('resort_id', $this->resort->resort_id)->whereIn('payroll_id', (array) $pid)
            ->whereNotNull('approver_name')->where('approver_name', '<>', '')
            ->orderBy('step_order')->value('approver_name') ?: 'N/A';

        $rows = DB::table('payroll_approvals')->where('resort_id', $this->resort->resort_id)->whereIn('payroll_id', (array) $pid)
            ->orderBy('step_order')->get()
            ->map(fn($a) => [
                'Payroll Period' => $label,
                'Submitted By'   => $submittedBy,
                'Approval Step'  => $a->role_title,
                'Approved By'    => $a->approver_name ?? '—',
                'Status'         => ucfirst($a->status),
                'Approved Date'  => $a->approved_at ? Carbon::parse($a->approved_at)->format('d M Y H:i') : '—',
            ])->all();

        return ['columns' => ['Payroll Period', 'Submitted By', 'Approval Step', 'Approved By', 'Status', 'Approved Date'], 'rows' => $rows];
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
            ->join('payroll as pay', 'pay.id', '=', 'pd.payroll_id')
            ->join('employees as e', 'e.id', '=', 'pd.employee_id')
            ->where('pay.resort_id', $this->resort->resort_id)
            ->whereIn('pd.payroll_id', (array) $pid)
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))
            ->when($filters['department'], fn($q) => $q->where('e.Dept_id', $filters['department']))
            ->selectRaw('SUM(pd.pension) pension, SUM(pd.ewt) ewt')->first();

        $pension = (float) ($d->pension ?? 0);
        $tax     = (float) ($d->ewt ?? 0);
        $totalDed = (float) ($r->ded ?? 0);
        $otherDed = max(0, $totalDed - $pension - $tax);

        return [
            // Column order per the requirements doc.
            'columns' => ['Employee Count', 'Gross Payroll', 'Total OT', 'Total Service Charge', 'Deductions', 'Tax Deductions', 'Pension Deductions', 'Total Deductions', 'Net Payroll'],
            'rows'    => [[
                'Employee Count'      => (int) ($r->emp ?? 0),
                'Gross Payroll'       => $this->n($r->gross ?? 0),
                'Total OT'            => $this->n($r->ot ?? 0),
                'Total Service Charge'=> $this->n($r->sc ?? 0),
                'Deductions'          => $this->n($otherDed),
                'Tax Deductions'      => $this->n($tax),
                'Pension Deductions'  => $this->n($pension),
                'Total Deductions'    => $this->n($totalDed),
                'Net Payroll'         => $this->n($r->net ?? 0),
            ]],
        ];
    }

    /* ------------------------------------------------ catalog completion */

    /** #3 Upcoming Payroll Projection — the selected run's payslips as estimates. */
    public function upcomingProjection(array $filters): array
    {
        $pid = $this->resolvePayrollId($filters);
        $runs = $this->payrollRuns($pid);
        $rid = $this->resort->resort_id;
        $periodStart = $runs->min('start_date');
        $periodEnd   = $runs->max('end_date');
        $year = $periodEnd ? (int) Carbon::parse($periodEnd)->year : Carbon::now()->year;

        // Year-to-date average service charge per employee (completed months so far).
        $ytdSc = DB::table('payroll_reviews as pr')->join('payroll as pay', 'pay.id', '=', 'pr.payroll_id')
            ->where('pay.resort_id', $rid)->whereRaw('YEAR(pay.end_date) = ?', [$year])
            ->groupBy('pr.employee_id')->selectRaw('pr.employee_id as eid, AVG(pr.service_charge) as sc')
            ->pluck('sc', 'eid')->toArray();

        // Third-party (Shopkeeper) purchases in the period → payroll deduction.
        $shop = [];
        if ($periodStart && $periodEnd) {
            $shop = DB::table('payments')
                ->whereBetween('purchased_date', [$periodStart, $periodEnd])
                ->groupBy('emp_id')->selectRaw('emp_id, SUM(price * quantity) as amt')
                ->pluck('amt', 'emp_id')->toArray();
        }

        $rows = $this->basePayslip($pid, $filters)
            ->leftJoin('payroll_time_and_attandance as ta', function ($j) {
                $j->on('ta.payroll_id', '=', 'pr.payroll_id')->on('ta.employee_id', '=', 'pr.employee_id');
            })
            ->orderBy('ra.first_name')
            ->get(['pr.employee_id as eid', 'e.Emp_id', $this->nameExpr(), 'p.position_title', 'e.basic_salary',
                'pr.earnings_basic', 'pr.regularOTPay', 'pr.holidayOTPay', 'pr.service_charge', 'pr.earnings_allowance',
                'ta.present_days', 'ta.absent_days'])
            ->map(function ($r) use ($ytdSc, $shop) {
                $present = (float) ($r->present_days ?? 0);
                $absent  = (float) ($r->absent_days ?? 0);
                $workDays = $present + $absent;
                // Attendance factor: present share of the working days (default full when unknown).
                $factor = $workDays > 0 ? $present / $workDays : 1.0;

                $basicMonthly = (float) ($r->basic_salary ?: $r->earnings_basic);
                $estBasic = round($basicMonthly * $factor, 2);
                $estOt    = (float) ($r->regularOTPay + $r->holidayOTPay);         // from attendance OT
                $estSc    = round((($ytdSc[$r->eid] ?? $r->service_charge) ?: 0) * $factor, 2);
                $estAllow = round((float) $r->earnings_allowance * $factor, 2);
                $absentDed = $workDays > 0 ? round($basicMonthly / $workDays * $absent, 2) : 0;
                $shopDed   = round((float) ($shop[$r->eid] ?? 0), 2);
                $estDed   = $absentDed + $shopDed;
                $estNet   = $estBasic + $estOt + $estSc + $estAllow - $estDed;

                return [
                    'Employee ID'             => $r->Emp_id ?: 'N/A',
                    'Employee Name'           => $r->employee_name,
                    'Employee Position'       => $r->position_title ?? 'N/A',
                    'Estimated Basic Salary'  => $this->n($estBasic),
                    'Estimated OT'            => $this->n($estOt),
                    'Estimated Service Charge'=> $this->n($estSc),
                    'Estimated Allowances'    => $this->n($estAllow),
                    'Estimated Deductions'    => $this->n($estDed),
                    'Estimated Net Salary'    => $this->n($estNet),
                ];
            })->all();

        return [
            'columns' => ['Employee ID', 'Employee Name', 'Employee Position', 'Estimated Basic Salary', 'Estimated OT', 'Estimated Service Charge', 'Estimated Allowances', 'Estimated Deductions', 'Estimated Net Salary'],
            'rows'    => $rows,
        ];
    }

    /** #18 Overtime Summary — OT hours + pay per employee (no Friday OT tracked). */
    public function overtimeSummary(array $filters): array
    {
        $pid = $this->resolvePayrollId($filters);
        $rows = $this->basePayslip($pid, $filters)
            ->leftJoin('payroll_time_and_attandance as ta', function ($j) {
                $j->on('ta.payroll_id', '=', 'pr.payroll_id')->on('ta.employee_id', '=', 'pr.employee_id');
            })
            ->whereRaw('(pr.regularOTPay + pr.holidayOTPay) > 0 OR ta.total_ot > 0')
            ->orderBy('ra.first_name')
            ->get(['e.id as eid', 'e.Emp_id', $this->nameExpr(), 'p.position_title', 'ta.regular_ot_hours', 'ta.holiday_ot_hours', 'pr.regularOTPay', 'pr.holidayOTPay'])
            // Consolidate to one row per employee (aggregate across runs in scope).
            ->groupBy('eid')->map(function ($grp) {
                $first = $grp->first();
                return [
                    'Employee ID'      => $first->Emp_id ?: 'N/A',
                    'Employee Name'    => $first->employee_name,
                    'Employee Position'=> $first->position_title ?? 'N/A',
                    'Normal OT Hours'  => $this->hrs($grp->sum('regular_ot_hours')),
                    'Friday OT Hours'  => 'N/A', // not tracked separately
                    'Holiday OT Hours' => $this->hrs($grp->sum('holiday_ot_hours')),
                    'OT Amount'        => $this->n($grp->sum(fn($x) => $x->regularOTPay + $x->holidayOTPay)),
                ];
            })->values()->all();

        return ['columns' => ['Employee ID', 'Employee Name', 'Employee Position', 'Normal OT Hours', 'Friday OT Hours', 'Holiday OT Hours', 'OT Amount'], 'rows' => $rows];
    }

    /** #20 Overtime Trend — monthly OT hours + cost for a year. */
    public function overtimeTrend(array $filters): array
    {
        $year = $filters['year'] ?: Carbon::now()->year;
        $rows = DB::table('payroll_time_and_attandance as ta')
            ->join('payroll as pay', 'pay.id', '=', 'ta.payroll_id')
            ->join('payroll_reviews as pr', function ($j) {
                $j->on('pr.payroll_id', '=', 'ta.payroll_id')->on('pr.employee_id', '=', 'ta.employee_id');
            })
            ->where('pay.resort_id', $this->resort->resort_id)
            ->whereRaw('YEAR(pay.end_date) = ?', [$year])
            ->when($filters['month'] ?? null, fn($q) => $q->whereRaw('MONTH(pay.end_date) = ?', [$filters['month']]))
            ->groupBy(DB::raw('MONTH(pay.end_date)'))
            ->select(
                DB::raw('MONTH(pay.end_date) as m'),
                DB::raw('SUM(ta.regular_ot_hours) as normal_h'),
                DB::raw('SUM(ta.holiday_ot_hours) as holiday_h'),
                DB::raw('SUM(pr.regularOTPay + pr.holidayOTPay) as cost')
            )->orderBy('m')->get()
            ->map(fn($r) => [
                'Month'           => Carbon::create()->month((int) $r->m)->format('F'),
                'Normal OT Hours' => $this->hrs($r->normal_h),
                'Friday OT Hours' => 'N/A',
                'Holiday OT Hours'=> $this->hrs($r->holiday_h),
                'Total OT Cost'   => $this->n($r->cost),
            ])->all();

        return ['columns' => ['Month', 'Normal OT Hours', 'Friday OT Hours', 'Holiday OT Hours', 'Total OT Cost'], 'rows' => $rows];
    }

    /** #21 Pension Contribution Report (employer share mirrors the 7% employee share). */
    public function pensionContribution(array $filters): array
    {
        $pid = $this->resolvePayrollId($filters);
        $rows = $this->basePayslip($pid, $filters)
            ->join('payroll_deductions as pd', function ($j) {
                $j->on('pd.payroll_id', '=', 'pr.payroll_id')->on('pd.employee_id', '=', 'pr.employee_id');
            })
            ->where('pd.pension', '>', 0)
            ->orderBy('ra.first_name')
            ->get(['e.Emp_id', $this->nameExpr(), 'p.position_title', 'pr.earnings_basic', 'pd.pension'])
            ->map(fn($r) => [
                'Employee ID'               => $r->Emp_id ?: 'N/A',
                'Employee Name'             => $r->employee_name,
                'Employee Position'         => $r->position_title ?? 'N/A',
                'Pensionable Salary'        => $this->n($r->earnings_basic),
                'Employee Contribution (7%)'=> $this->n($r->pension),
                'Employer Contribution (7%)'=> $this->n($r->pension),
                'Total Contribution'        => $this->n($r->pension * 2),
            ])->all();

        return ['columns' => ['Employee ID', 'Employee Name', 'Employee Position', 'Pensionable Salary', 'Employee Contribution (7%)', 'Employer Contribution (7%)', 'Total Contribution'], 'rows' => $rows];
    }

    /** #22 Annual Pension Summary (per employee, employer mirrors employee). */
    public function annualPension(array $filters): array
    {
        $year   = $filters['year'] ?: Carbon::now()->year;
        $scoped = Common::getScopedDepartmentIds();
        $rows = DB::table('payroll_deductions as pd')
            ->join('payroll as pay', 'pay.id', '=', 'pd.payroll_id')
            ->join('employees as e', 'e.id', '=', 'pd.employee_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->leftJoin('resort_positions as p', 'p.id', '=', 'e.Position_id')
            ->where('pay.resort_id', $this->resort->resort_id)
            ->whereRaw('YEAR(pay.end_date) = ?', [$year])
            ->when($filters['month'] ?? null, fn($q) => $q->whereRaw('MONTH(pay.end_date) = ?', [$filters['month']]))
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))
            ->groupBy('e.id', 'e.Emp_id', 'ra.first_name', 'ra.last_name', 'p.position_title', DB::raw('MONTH(pay.end_date)'))
            ->havingRaw('SUM(pd.pension) > 0')
            ->select('e.Emp_id', $this->nameExpr(), 'p.position_title', DB::raw('MONTH(pay.end_date) as m'), DB::raw('SUM(pd.pension) emp'))
            ->orderBy('ra.first_name')->orderBy('m')->get()
            ->map(fn($r) => [
                'Month'                      => Carbon::create()->month((int) $r->m)->format('F'),
                'Employee ID'                => $r->Emp_id ?: 'N/A',
                'Employee Name'              => $r->employee_name,
                'Employee Position'          => $r->position_title ?? 'N/A',
                'Total Employee Contribution'=> $this->n($r->emp),
                'Total Employer Contribution'=> $this->n($r->emp),
                'Total Annual Contribution'  => $this->n($r->emp * 2),
            ])->all();

        return ['columns' => ['Month', 'Employee ID', 'Employee Name', 'Employee Position', 'Total Employee Contribution', 'Total Employer Contribution', 'Total Annual Contribution'], 'rows' => $rows];
    }

    /** #28 Tuck Shop Outstanding Payable — unpaid purchases grouped by vendor. */
    public function tuckshopPayable(array $filters): array
    {
        $pid = $this->resolvePayrollId($filters);
        $runs = $this->payrollRuns($pid);
        $scoped = Common::getScopedDepartmentIds();

        $q = DB::table('payments as pmt')
            ->join('employees as e', 'e.id', '=', 'pmt.emp_id')
            ->leftJoin('shopkeepers as sk', 'sk.id', '=', 'pmt.shopkeeper_id')
            ->where('e.resort_id', $this->resort->resort_id)
            ->whereRaw('LOWER(COALESCE(pmt.status, "")) <> ?', ['paid'])   // outstanding only
            ->when($scoped !== null, fn($x) => $x->whereIn('e.Dept_id', $scoped));

        if ($runs->isNotEmpty()) {
            $q->whereBetween('pmt.purchased_date', [$runs->min('start_date'), $runs->max('end_date')]);
        }

        $rows = $q->groupBy('pmt.shopkeeper_id', 'sk.name')
            ->select('sk.name as vendor', DB::raw('SUM(pmt.price * pmt.quantity) as total'), DB::raw('COUNT(*) as items'))
            ->orderBy('sk.name')->get()
            ->map(fn($r) => [
                'Vendor Name'    => $r->vendor ?? 'N/A',
                'Invoice Number' => 'N/A', // no invoice records in schema
                'Invoice Date'   => 'N/A',
                'Total Amount'   => $this->n($r->total),
                'Payment Status' => 'Outstanding',
            ])->all();

        return ['columns' => ['Vendor Name', 'Invoice Number', 'Invoice Date', 'Total Amount', 'Payment Status'], 'rows' => $rows];
    }

    /** #33 Payroll Processing Status — status of each payroll run (no payroll groups). */
    public function processingStatus(array $filters): array
    {
        $runs = DB::table('payroll')->where('resort_id', $this->resort->resort_id)
            ->when($filters['payroll'], fn($q) => $q->where('id', $filters['payroll']))
            ->orderByDesc('start_date')->get();

        $rows = $runs->map(function ($p) {
            $approvedAt = DB::table('payroll_approvals')->where('payroll_id', $p->id)
                ->where('status', 'approved')->max('approved_at');
            return [
                'Payroll Period' => $this->periodLabel($p),
                'Status'         => ucfirst($p->status),
                'Processed Date' => $p->draft_date ? Carbon::parse($p->draft_date)->format('d M Y') : 'N/A',
                'Approved Date'  => $approvedAt ? Carbon::parse($approvedAt)->format('d M Y') : 'N/A',
            ];
        })->all();

        return ['columns' => ['Payroll Period', 'Status', 'Processed Date', 'Approved Date'], 'rows' => $rows];
    }
}
