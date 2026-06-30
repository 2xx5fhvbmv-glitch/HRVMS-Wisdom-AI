<?php

namespace App\Http\Controllers\Resorts\Visa;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Resorts\Concerns\PredefinedReportActions;
use App\Helpers\Common;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Predefined Visa / Immigration reports (Option B).
 *
 * Dedicated, dept-scoped queries returning { columns, rows } rendered by the
 * shared resorts.renderfiles.ReportFilterData partial. Reuses the
 * PredefinedReportActions trait (Export CSV/Excel/PDF + WAI Insights + totals row).
 *
 * Visa fees are stored in MVR; amounts are shown with their stored currency.
 * Where the schema has no column for a spec field (e.g. passport expiry date),
 * the value is shown as "N/A" rather than fabricated.
 */
class VisaReportController extends Controller
{
    use PredefinedReportActions;

    protected $resort;

    public function __construct()
    {
        $this->resort = auth()->guard('resort-admin')->user();
    }

    private function registry(): array
    {
        return [
            'exec_summary'             => ['name' => 'Visa Management Executive Summary', 'description' => 'Consolidated overview of expat employees, visa status, liabilities and deposits.', 'filters' => [], 'handler' => 'execSummary'],
            'expat_register'           => ['name' => 'Expat Employee Register', 'description' => 'All expatriate employees with visa-relevant details.', 'filters' => ['department', 'nationality'], 'handler' => 'expatRegister'],
            'nationality_distribution' => ['name' => 'Nationality Distribution Report', 'description' => 'Expat workforce distribution by nationality.', 'filters' => ['nationality'], 'handler' => 'nationalityDistribution'],
            'nationality_employees'    => ['name' => 'Nationality Employee Report', 'description' => 'Employees grouped by nationality.', 'filters' => ['nationality'], 'handler' => 'nationalityEmployees'],
            'nationality_deposit'      => ['name' => 'Nationality Deposit Summary', 'description' => 'Total deposit held per nationality.', 'filters' => ['nationality'], 'handler' => 'nationalityDeposit'],
            'deposit_rate_master'      => ['name' => 'Deposit Rate Master Report', 'description' => 'Configured deposit rate per nationality.', 'filters' => ['nationality'], 'handler' => 'depositRateMaster'],
            'wallet_balance'           => ['name' => 'Wallet Balance Summary', 'description' => 'Balances across visa wallets.', 'filters' => [], 'handler' => 'walletBalance'],
            'wallet_transactions'      => ['name' => 'Wallet Transaction Report', 'description' => 'Visa wallet transactions.', 'filters' => ['duration'], 'handler' => 'walletTransactions'],
            'wallet_movement'          => ['name' => 'Wallet Movement Report', 'description' => 'Transfers between visa wallets.', 'filters' => ['duration'], 'handler' => 'walletMovement'],
            'deposit_request'          => ['name' => 'Deposit Request Register', 'description' => 'Employee deposits on record.', 'filters' => [], 'handler' => 'depositRequest'],
            'deposit_refund_status'    => ['name' => 'Deposit Refund Status Report', 'description' => 'Deposit refund status for separated employees.', 'filters' => ['duration'], 'handler' => 'depositRefundStatus'],
            'deposit_refund_pending'   => ['name' => 'Deposit Refund Outstanding Report', 'description' => 'Deposits not yet refunded.', 'filters' => [], 'handler' => 'depositRefundPending'],
            'payment_request_register' => ['name' => 'Payment Request Register', 'description' => 'Visa payment requests.', 'filters' => ['duration', 'status'], 'handler' => 'paymentRequestRegister'],
            'payment_request_details'  => ['name' => 'Payment Request Details Report', 'description' => 'Per-employee fee breakdown within payment requests.', 'filters' => ['payment_request'], 'handler' => 'paymentRequestDetails'],
            'wp_payment_schedule'      => ['name' => 'Work Permit Payment Schedule', 'description' => 'Monthly work-permit fee schedule.', 'filters' => ['employee'], 'handler' => 'wpPaymentSchedule'],
            'slot_payment_schedule'    => ['name' => 'Quota Slot Payment Schedule', 'description' => 'Monthly quota-slot fee schedule.', 'filters' => ['employee'], 'handler' => 'slotPaymentSchedule'],
            'employee_payment_history' => ['name' => 'Employee Payment History', 'description' => 'All immigration payments made by an employee.', 'filters' => ['employee', 'duration'], 'handler' => 'employeePaymentHistory'],
            'visa_expiry'              => ['name' => 'Visa Expiry Report', 'description' => 'Visas expiring within the selected window.', 'filters' => ['expiry_period'], 'handler' => 'visaExpiry'],
            'wp_expiry'                => ['name' => 'Work Permit Expiry Report', 'description' => 'Work permits due within the window.', 'filters' => ['expiry_period'], 'handler' => 'wpExpiry'],
            'insurance_expiry'         => ['name' => 'Insurance Expiry Report', 'description' => 'Insurance policies expiring within the window.', 'filters' => ['expiry_period'], 'handler' => 'insuranceExpiry'],
            'medical_expiry'           => ['name' => 'Medical Expiry Report', 'description' => 'Medical certificates expiring within the window.', 'filters' => ['expiry_period'], 'handler' => 'medicalExpiry'],
            'passport_expiry'          => ['name' => 'Passport Expiry Report', 'description' => 'Passport numbers (expiry date not stored in the system).', 'filters' => ['expiry_period'], 'handler' => 'passportExpiry'],
            'slot_expiry'              => ['name' => 'Quota Slot Expiry Report', 'description' => 'Quota slots due within the window.', 'filters' => ['expiry_period'], 'handler' => 'slotExpiry'],
            'comprehensive_expiry'     => ['name' => 'Comprehensive Expiry Tracker', 'description' => 'All document expiries per employee.', 'filters' => ['department'], 'handler' => 'comprehensiveExpiry'],
            'liability_summary'        => ['name' => 'Liability Summary Report', 'description' => 'Outstanding visa liabilities by type.', 'filters' => [], 'handler' => 'liabilitySummary'],
            'liability_breakdown'      => ['name' => 'Liability Breakdown Report', 'description' => 'Total / paid / outstanding per liability type.', 'filters' => [], 'handler' => 'liabilityBreakdown'],
            'outstanding_liability'    => ['name' => 'Outstanding Liability Report', 'description' => 'Unpaid fees per employee.', 'filters' => ['liability_type'], 'handler' => 'outstandingLiability'],
            'liability_trend'          => ['name' => 'Liability Trend Report', 'description' => 'Monthly liability trend for a year.', 'filters' => ['year'], 'handler' => 'liabilityTrend'],
            'payment_status'           => ['name' => 'Payment Status Report', 'description' => 'Requested / paid / pending per fee type.', 'filters' => ['duration'], 'handler' => 'paymentStatus'],
            'immigration_transactions' => ['name' => 'Immigration Transaction History', 'description' => 'Wallet/immigration transactions.', 'filters' => ['duration'], 'handler' => 'immigrationTransactions'],
            'employee_immigration'     => ['name' => 'Employee Immigration Profile', 'description' => 'A single employee\'s immigration document status.', 'filters' => ['employee'], 'handler' => 'employeeImmigration'],
            'immigration_compliance'   => ['name' => 'Immigration Compliance Report', 'description' => 'Per-employee visa/WP/medical/insurance status.', 'filters' => ['department'], 'handler' => 'immigrationCompliance'],
            'immigration_audit'        => ['name' => 'Immigration Audit Trail', 'description' => 'Document activity log.', 'filters' => ['duration'], 'handler' => 'immigrationAudit'],
            'exec_dashboard'           => ['name' => 'Visa Management Executive Dashboard', 'description' => 'Headline immigration metrics.', 'filters' => [], 'handler' => 'execDashboard'],
            'renewal_forecast'         => ['name' => 'Immigration Renewal Forecast Report', 'description' => 'Upcoming renewals and estimated cost.', 'filters' => ['expiry_period'], 'handler' => 'renewalForecast'],
            'cost_by_nationality'      => ['name' => 'Immigration Cost by Nationality Report', 'description' => 'Immigration spend per nationality.', 'filters' => ['nationality'], 'handler' => 'costByNationality'],
            'deposit_exposure'         => ['name' => 'Deposit Exposure Report', 'description' => 'Employee deposit exposure and refund eligibility.', 'filters' => ['nationality'], 'handler' => 'depositExposure'],
            'document_compliance'      => ['name' => 'Document Compliance Matrix', 'description' => 'Which documents each employee has on file.', 'filters' => ['department', 'nationality'], 'handler' => 'documentCompliance'],
            'budget_forecast'          => ['name' => 'Immigration Budget Forecast Report', 'description' => 'Forecasted monthly immigration cost.', 'filters' => ['year'], 'handler' => 'budgetForecast'],
            'deposit_aging'            => ['name' => 'Deposit Aging Report', 'description' => 'How long deposits have been held.', 'filters' => ['duration'], 'handler' => 'depositAging'],
        ];
    }

    /* --------------------------------------------------------------- plumbing */

    public function index()
    {
        if (Common::checkRouteWisePermission('resort.report.index', config('settings.resort_permissions.view')) == false) {
            return abort(403, 'Unauthorized access');
        }

        $page_title = 'Visa / Immigration Reports';
        $resortId   = $this->resort->resort_id;
        $scoped     = Common::getScopedDepartmentIds();

        $reports = collect($this->registry())->map(fn($r, $key) => [
            'key' => $key, 'name' => $r['name'], 'description' => $r['description'], 'filters' => $r['filters'],
        ])->values();

        $departments = DB::table('resort_departments')->where('resort_id', $resortId)
            ->when($scoped !== null, fn($q) => $q->whereIn('id', $scoped))
            ->orderBy('name')->get(['id', 'name']);

        $nationalities = DB::table('employees')->where('resort_id', $resortId)
            ->whereRaw("LOWER(TRIM(COALESCE(nationality,''))) <> 'maldivian'")
            ->whereNotNull('nationality')->where('nationality', '<>', '')
            ->when($scoped !== null, fn($q) => $q->whereIn('Dept_id', $scoped))
            ->distinct()->orderBy('nationality')->pluck('nationality');

        $employees = DB::table('employees as e')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->where('e.resort_id', $resortId)
            ->whereRaw("LOWER(TRIM(COALESCE(e.nationality,''))) <> 'maldivian'")
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))
            ->orderBy('ra.first_name')
            ->get(['e.id', DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as name")]);

        $statuses        = ['Pending', 'SendtoFinance', 'Approved', 'Rejact'];
        $liabilityTypes  = ['Work Permit', 'Quota Slot', 'Insurance', 'Medical', 'Visa'];
        $expiryPeriods   = [['v' => '30', 'l' => 'Next 30 days'], ['v' => '60', 'l' => 'Next 60 days'], ['v' => '90', 'l' => 'Next 90 days'], ['v' => '180', 'l' => 'Next 180 days'], ['v' => 'all', 'l' => 'All']];
        $years           = range((int) date('Y') + 1, (int) date('Y') - 4);
        $paymentRequests = DB::table('payment_requests')->where('resort_id', $resortId)
            ->orderByDesc('Request_date')->get(['id', 'Requestd_id', 'Request_date']);

        return view('resorts.reports.visa', compact(
            'page_title', 'reports', 'departments', 'nationalities', 'employees',
            'statuses', 'liabilityTypes', 'expiryPeriods', 'years', 'paymentRequests'
        ));
    }

    private function filtersFrom(Request $request): array
    {
        return [
            'department'      => $request->input('department') ?: null,
            'nationality'     => $request->input('nationality') ?: null,
            'employee'        => $request->input('employee') ?: null,
            'status'          => $request->input('status') ?: null,
            'liability_type'  => $request->input('liability_type') ?: null,
            'expiry_period'   => $request->input('expiry_period') ?: null,
            'year'            => $request->input('year') ?: null,
            'payment_request' => $request->input('payment_request') ?: null,
            'from_date'       => $request->input('from_date') ?: null,
            'to_date'         => $request->input('to_date') ?: null,
        ];
    }

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
            'report' => (object) ['name' => $c['name']], 'columns' => $c['columns'], 'data' => $c['rows'],
        ])->render();
        return response()->json(['success' => true, 'html' => $html, 'count' => count($c['rows'])]);
    }

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

    /* --------------------------------------------------------------- helpers */

    private function nameExpr()
    {
        return DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as employee_name");
    }

    /** Apply the optional duration (from/to date) to a query's date column. */
    private function applyDuration($q, array $filters, string $col)
    {
        return $q->when($filters['from_date'] ?? null, fn($x) => $x->whereDate($col, '>=', $filters['from_date']))
                 ->when($filters['to_date'] ?? null, fn($x) => $x->whereDate($col, '<=', $filters['to_date']));
    }

    private function money($amt, $cur = 'MVR'): string
    {
        return ($cur === 'USD' ? 'USD ' : 'MVR ') . number_format((float) $amt, 2);
    }

    private function daysLeftLabel($date): string
    {
        if (!$date) return 'N/A';
        $d = Carbon::today()->diffInDays(Carbon::parse($date), false);
        if ($d < 0)  return 'Expired ' . abs($d) . ' day(s) ago';
        if ($d === 0) return 'Expires today';
        return $d . ' day(s)';
    }

    /** Base expat-employee query, dept-scoped + filtered. */
    private function expatBase(array $f)
    {
        $scoped = Common::getScopedDepartmentIds();
        return DB::table('employees as e')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->leftJoin('resort_departments as d', 'd.id', '=', 'e.Dept_id')
            ->leftJoin('resort_positions as p', 'p.id', '=', 'e.Position_id')
            ->where('e.resort_id', $this->resort->resort_id)
            ->whereRaw("LOWER(TRIM(COALESCE(e.nationality,''))) <> 'maldivian'")
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))
            ->when($f['department'] ?? null, fn($q) => $q->where('e.Dept_id', $f['department']))
            ->when($f['nationality'] ?? null, fn($q) => $q->where('e.nationality', $f['nationality']))
            ->when($f['employee'] ?? null, fn($q) => $q->where('e.id', $f['employee']));
    }

    /** Generic per-employee latest-expiry report for a renewal table. */
    private function expiryReport(array $f, string $table, string $dateCol, array $columns, callable $extra)
    {
        $period = $f['expiry_period'] ?? null;
        $rows = $this->expatBase($f)
            ->join("$table as t", 't.employee_id', '=', 'e.id')
            ->whereIn('t.id', function ($q) use ($table, $dateCol) {
                $q->selectRaw('MAX(id)')->from($table)->groupBy('employee_id');
            })
            ->when($period && $period !== 'all', fn($q) => $q->whereDate("t.$dateCol", '<=', Carbon::today()->addDays((int) $period)))
            ->orderBy("t.$dateCol")
            ->get(array_merge([$this->nameExpr(), 'e.nationality', "t.$dateCol as expiry_date"], $extra('select')))
            ->map(fn($r) => $extra('row', $r))->all();

        return ['columns' => $columns, 'rows' => $rows];
    }

    /* --------------------------------------------------------------- reports */

    /** #1 Executive Summary. */
    public function execSummary(array $f): array
    {
        $rid = $this->resort->resort_id;
        $expat = (clone $this->expatBase($f))->count();
        $activeWP = DB::table('work_permits')->where('resort_id', $rid)->where('Status', 'Paid')->distinct('employee_id')->count('employee_id');
        $upcoming = DB::table('visa_renewals')->where('resort_id', $rid)
            ->whereBetween('end_date', [Carbon::today(), Carbon::today()->addDays(90)])->count();
        $liability = (float) DB::table('work_permits')->where('resort_id', $rid)->whereRaw("LOWER(COALESCE(Status,''))<>'paid'")->sum('Amt')
            + (float) DB::table('quota_slot_renewals')->where('resort_id', $rid)->whereRaw("LOWER(COALESCE(Status,''))<>'paid'")->sum('Amt');
        $deposit = (float) DB::table('total_expensess_since_joings')->where('resort_id', $rid)->sum('Deposit_Amt');

        return [
            'columns' => ['Total Expat Employees', 'Active Work Permits', 'Upcoming Expiries (90d)', 'Total Outstanding Liability', 'Total Deposit Balance'],
            'rows'    => [[
                'Total Expat Employees'       => $expat,
                'Active Work Permits'         => $activeWP,
                'Upcoming Expiries (90d)'     => $upcoming,
                'Total Outstanding Liability' => $this->money($liability),
                'Total Deposit Balance'       => $this->money($deposit),
            ]],
        ];
    }

    /** #2 Expat Employee Register. */
    public function expatRegister(array $f): array
    {
        $rows = $this->expatBase($f)->orderBy('ra.first_name')
            ->get(['e.Emp_id', $this->nameExpr(), 'e.nationality', 'p.position_title', 'd.name as dept', 'e.joining_date', 'e.status'])
            ->map(fn($r) => [
                'Employee ID'       => $r->Emp_id,
                'Employee Name'     => $r->employee_name,
                'Nationality'       => $r->nationality ?? 'N/A',
                'Position'          => $r->position_title ?? 'N/A',
                'Department'        => $r->dept ?? 'N/A',
                'Joining Date'      => $r->joining_date ? Carbon::parse($r->joining_date)->format('d M Y') : 'N/A',
                'Employment Status' => $r->status ?? 'N/A',
            ])->all();
        return ['columns' => ['Employee ID', 'Employee Name', 'Nationality', 'Position', 'Department', 'Joining Date', 'Employment Status'], 'rows' => $rows];
    }

    /** #3 Nationality Distribution. */
    public function nationalityDistribution(array $f): array
    {
        $raw = $this->expatBase($f)->groupBy('e.nationality')
            ->select('e.nationality', DB::raw('COUNT(*) c'))->orderByDesc(DB::raw('COUNT(*)'))->get();
        $total = $raw->sum('c') ?: 1;
        $rows = $raw->map(fn($r) => [
            'Nationality'            => $r->nationality ?? 'N/A',
            'Employee Count'         => (int) $r->c,
            'Percentage of Workforce' => round($r->c / $total * 100, 1) . '%',
        ])->all();
        return ['columns' => ['Nationality', 'Employee Count', 'Percentage of Workforce'], 'rows' => $rows];
    }

    /** #4 Nationality Employee Report. */
    public function nationalityEmployees(array $f): array
    {
        $rows = $this->expatBase($f)->orderBy('e.nationality')->orderBy('ra.first_name')
            ->get(['e.Emp_id', $this->nameExpr(), 'e.nationality', 'p.position_title', 'd.name as dept', 'e.joining_date'])
            ->map(fn($r) => [
                'Employee ID'   => $r->Emp_id,
                'Employee Name' => $r->employee_name,
                'Nationality'   => $r->nationality ?? 'N/A',
                'Position'      => $r->position_title ?? 'N/A',
                'Department'    => $r->dept ?? 'N/A',
                'Joining Date'  => $r->joining_date ? Carbon::parse($r->joining_date)->format('d M Y') : 'N/A',
            ])->all();
        return ['columns' => ['Employee ID', 'Employee Name', 'Nationality', 'Position', 'Department', 'Joining Date'], 'rows' => $rows];
    }

    /** #5 Nationality Deposit Summary. */
    public function nationalityDeposit(array $f): array
    {
        $rows = $this->expatBase($f)
            ->leftJoin('total_expensess_since_joings as t', 't.employees_id', '=', 'e.id')
            ->groupBy('e.nationality')
            ->select('e.nationality', DB::raw('COUNT(DISTINCT e.id) c'), DB::raw('SUM(t.Deposit_Amt) dep'))
            ->orderBy('e.nationality')->get()
            ->map(fn($r) => [
                'Nationality'         => $r->nationality ?? 'N/A',
                'Employee Count'      => (int) $r->c,
                'Total Deposit Amount' => $this->money($r->dep ?? 0),
            ])->all();
        return ['columns' => ['Nationality', 'Employee Count', 'Total Deposit Amount'], 'rows' => $rows];
    }

    /** #6 Deposit Rate Master. */
    public function depositRateMaster(array $f): array
    {
        $rows = DB::table('visa_nationalities')->where('resort_id', $this->resort->resort_id)
            ->when($f['nationality'], fn($q) => $q->where('nationality', $f['nationality']))
            ->orderBy('nationality')->get(['nationality', 'amt'])
            ->map(fn($r) => [
                'Nationality'    => $r->nationality,
                'Deposit Rate'   => $this->money($r->amt),
                'Effective Date' => 'N/A', // not stored
                'Status'         => 'Active',
            ])->all();
        return ['columns' => ['Nationality', 'Deposit Rate', 'Effective Date', 'Status'], 'rows' => $rows];
    }

    /** #7 Wallet Balance Summary. */
    public function walletBalance(array $f): array
    {
        $rows = DB::table('visa_wallets')->where('resort_id', $this->resort->resort_id)
            ->orderBy('WalletName')->get(['WalletName', 'Amt', 'Status'])
            ->map(fn($r) => [
                'Wallet Name'       => $r->WalletName ?? 'N/A',
                'Available Balance' => $this->money($r->Amt),
                'Reserved Balance'  => 'N/A',
                'Deposited Balance' => 'N/A',
                'Withdrawn Balance' => 'N/A',
            ])->all();
        return ['columns' => ['Wallet Name', 'Available Balance', 'Reserved Balance', 'Deposited Balance', 'Withdrawn Balance'], 'rows' => $rows];
    }

    /** #8 Wallet Transaction Report. */
    public function walletTransactions(array $f): array
    {
        $rows = DB::table('visa_transection_histories as t')
            ->leftJoin('visa_wallets as tw', 'tw.id', '=', 't.to_wallet')
            ->where('t.resort_id', $this->resort->resort_id)
            ->when(true, fn($q) => $this->applyDuration($q, $f, 't.Payment_Date'))
            ->orderByDesc('t.Payment_Date')
            ->get(['t.transaction_id', 'tw.WalletName', 't.Amt', 't.Payment_Date', 't.comments'])
            ->map(fn($r) => [
                'Transaction ID'   => $r->transaction_id ?? 'N/A',
                'Wallet Name'      => $r->WalletName ?? 'N/A',
                'Transaction Type' => 'Transfer',
                'Amount'           => $this->money($r->Amt),
                'Date'             => $r->Payment_Date ? Carbon::parse($r->Payment_Date)->format('d M Y') : 'N/A',
                'Reference'        => $r->comments ?? 'N/A',
            ])->all();
        return ['columns' => ['Transaction ID', 'Wallet Name', 'Transaction Type', 'Amount', 'Date', 'Reference'], 'rows' => $rows];
    }

    /** #9 Wallet Movement Report. */
    public function walletMovement(array $f): array
    {
        $rows = DB::table('visa_transection_histories as t')
            ->leftJoin('visa_wallets as fw', 'fw.id', '=', 't.from_wallet')
            ->leftJoin('visa_wallets as tw', 'tw.id', '=', 't.to_wallet')
            ->leftJoin('employees as e', 'e.id', '=', 't.Employee_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->where('t.resort_id', $this->resort->resort_id)
            ->when(true, fn($q) => $this->applyDuration($q, $f, 't.Payment_Date'))
            ->orderByDesc('t.Payment_Date')
            ->get([$this->nameExpr(), 'fw.WalletName as from_w', 'tw.WalletName as to_w', 't.Amt', 't.Payment_Date'])
            ->map(fn($r) => [
                'Employee Name'  => trim($r->employee_name) ?: 'N/A',
                'Previous Wallet' => $r->from_w ?? 'N/A',
                'Current Wallet' => $r->to_w ?? 'N/A',
                'Amount'         => $this->money($r->Amt),
                'Movement Date'  => $r->Payment_Date ? Carbon::parse($r->Payment_Date)->format('d M Y') : 'N/A',
            ])->all();
        return ['columns' => ['Employee Name', 'Previous Wallet', 'Current Wallet', 'Amount', 'Movement Date'], 'rows' => $rows];
    }

    /** #10 Deposit Request Register. */
    public function depositRequest(array $f): array
    {
        $rows = $this->expatBase($f)
            ->join('total_expensess_since_joings as t', 't.employees_id', '=', 'e.id')
            ->where('t.Deposit_Amt', '>', 0)
            ->orderBy('ra.first_name')
            ->get(['e.Emp_id', $this->nameExpr(), 'e.nationality', 't.Deposit_Amt', 't.Date'])
            ->map(fn($r) => [
                'Request ID'     => $r->Emp_id,
                'Employee Name'  => $r->employee_name,
                'Nationality'    => $r->nationality ?? 'N/A',
                'Deposit Amount' => $this->money($r->Deposit_Amt),
                'Request Date'   => $r->Date ? Carbon::parse($r->Date)->format('d M Y') : 'N/A',
                'Status'         => 'Deposited',
            ])->all();
        return ['columns' => ['Request ID', 'Employee Name', 'Nationality', 'Deposit Amount', 'Request Date', 'Status'], 'rows' => $rows];
    }

    /** #11 Deposit Refund Status. */
    public function depositRefundStatus(array $f): array
    {
        $rows = DB::table('employee_resignation as r')
            ->join('employees as e', 'e.id', '=', 'r.employee_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->where('r.resort_id', $this->resort->resort_id)
            ->whereNotNull('r.Deposit_Amt')
            ->when(true, fn($q) => $this->applyDuration($q, $f, 'r.last_working_day'))
            ->orderByDesc('r.last_working_day')
            ->get([$this->nameExpr(), 'r.Deposit_Amt', 'r.Deposit_withdraw', 'r.last_working_day'])
            ->map(fn($r) => [
                'Employee Name'  => trim($r->employee_name) ?: 'N/A',
                'Deposit Amount' => $this->money($r->Deposit_Amt),
                'Refund Status'  => $r->Deposit_withdraw === 'Yes' ? 'Refunded' : 'Pending',
                'Refund Date'    => $r->Deposit_withdraw === 'Yes' && $r->last_working_day ? Carbon::parse($r->last_working_day)->format('d M Y') : 'N/A',
            ])->all();
        return ['columns' => ['Employee Name', 'Deposit Amount', 'Refund Status', 'Refund Date'], 'rows' => $rows];
    }

    /** #12 Deposit Refund Outstanding. */
    public function depositRefundPending(array $f): array
    {
        $rows = DB::table('employee_resignation as r')
            ->join('employees as e', 'e.id', '=', 'r.employee_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->where('r.resort_id', $this->resort->resort_id)
            ->whereNotNull('r.Deposit_Amt')->where('r.Deposit_Amt', '>', 0)
            ->where(fn($q) => $q->whereNull('r.Deposit_withdraw')->orWhere('r.Deposit_withdraw', '<>', 'Yes'))
            ->orderBy('r.last_working_day')
            ->get([$this->nameExpr(), 'e.nationality', 'r.Deposit_Amt', 'r.last_working_day'])
            ->map(fn($r) => [
                'Employee Name'  => trim($r->employee_name) ?: 'N/A',
                'Nationality'    => $r->nationality ?? 'N/A',
                'Deposit Amount' => $this->money($r->Deposit_Amt),
                'Days Pending'   => $r->last_working_day ? Carbon::parse($r->last_working_day)->diffInDays(Carbon::today()) : 'N/A',
            ])->all();
        return ['columns' => ['Employee Name', 'Nationality', 'Deposit Amount', 'Days Pending'], 'rows' => $rows];
    }

    /** #13 Payment Request Register. */
    public function paymentRequestRegister(array $f): array
    {
        $rows = DB::table('payment_requests')->where('resort_id', $this->resort->resort_id)
            ->when($f['status'], fn($q) => $q->where('Status', $f['status']))
            ->when(true, fn($q) => $this->applyDuration($q, $f, 'Request_date'))
            ->orderByDesc('Request_date')->get()
            ->map(function ($r) {
                $total = DB::table('payment_request_children')->where('Requested_Id', $r->id)
                    ->selectRaw('SUM(WorkPermitAmt+QuotaslotAmt+InsurancePrimume+MedicalReportFees+VisaAmt) t')->value('t');
                return [
                    'Payment Request ID' => $r->Requestd_id,
                    'Request Date'       => $r->Request_date ? Carbon::parse($r->Request_date)->format('d M Y') : 'N/A',
                    'Request Type'       => 'Immigration Fees',
                    'Total Amount'       => $this->money($total ?? 0),
                    'Status'             => $r->Status,
                ];
            })->all();
        return ['columns' => ['Payment Request ID', 'Request Date', 'Request Type', 'Total Amount', 'Status'], 'rows' => $rows];
    }

    /** #14 Payment Request Details. */
    public function paymentRequestDetails(array $f): array
    {
        $rows = DB::table('payment_request_children as c')
            ->join('employees as e', 'e.id', '=', 'c.Employee_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->leftJoin('resort_departments as d', 'd.id', '=', 'e.Dept_id')
            ->leftJoin('resort_positions as p', 'p.id', '=', 'e.Position_id')
            ->when($f['payment_request'], fn($q) => $q->where('c.Requested_Id', $f['payment_request']))
            ->get([$this->nameExpr(), 'd.name as dept', 'p.position_title',
                'c.WorkPermitAmt', 'c.QuotaslotAmt', 'c.InsurancePrimume', 'c.MedicalReportFees', 'c.VisaAmt', 'c.ChildStatus']);

        $out = [];
        foreach ($rows as $r) {
            foreach ([['Work Permit', $r->WorkPermitAmt], ['Quota Slot', $r->QuotaslotAmt], ['Insurance', $r->InsurancePrimume], ['Medical', $r->MedicalReportFees], ['Visa', $r->VisaAmt]] as [$type, $amt]) {
                if ((float) $amt <= 0) continue;
                $out[] = [
                    'Employee Name' => trim($r->employee_name) ?: 'N/A',
                    'Department'    => $r->dept ?? 'N/A',
                    'Position'      => $r->position_title ?? 'N/A',
                    'Fee Type'      => $type,
                    'Amount Due'    => $this->money($amt),
                    'Payment Status' => $r->ChildStatus ?? 'N/A',
                ];
            }
        }
        return ['columns' => ['Employee Name', 'Department', 'Position', 'Fee Type', 'Amount Due', 'Payment Status'], 'rows' => $out];
    }

    private function feeSchedule(array $f, string $table): array
    {
        $rows = DB::table("$table as t")
            ->join('employees as e', 'e.id', '=', 't.employee_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->where('t.resort_id', $this->resort->resort_id)
            ->when($f['employee'], fn($q) => $q->where('t.employee_id', $f['employee']))
            ->orderBy('ra.first_name')->orderBy('t.Due_Date')
            ->get([$this->nameExpr(), 't.Month', 't.Due_Date', 't.Amt', 't.Currency', 't.Payment_Date', 't.Status'])
            ->map(fn($r) => [
                'Employee Name' => trim($r->employee_name) ?: 'N/A',
                'Month'         => $r->Month ?? ($r->Due_Date ? Carbon::parse($r->Due_Date)->format('M Y') : 'N/A'),
                'Due Date'      => $r->Due_Date ? Carbon::parse($r->Due_Date)->format('d M Y') : 'N/A',
                'Amount'        => $this->money($r->Amt, $r->Currency ?? 'MVR'),
                'Payment Date'  => $r->Payment_Date ? Carbon::parse($r->Payment_Date)->format('d M Y') : 'N/A',
                'Status'        => $r->Status ?? 'N/A',
            ])->all();
        return ['columns' => ['Employee Name', 'Month', 'Due Date', 'Amount', 'Payment Date', 'Status'], 'rows' => $rows];
    }

    /** #15 / #16 Work Permit & Quota Slot Payment Schedules. */
    public function wpPaymentSchedule(array $f): array   { return $this->feeSchedule($f, 'work_permits'); }
    public function slotPaymentSchedule(array $f): array { return $this->feeSchedule($f, 'quota_slot_renewals'); }

    /** #17 Employee Payment History. */
    public function employeePaymentHistory(array $f): array
    {
        $rid = $this->resort->resort_id;
        $sources = [
            ['work_permits', 'Work Permit', 'Payment_Date', 'Amt'],
            ['quota_slot_renewals', 'Quota Slot', 'Payment_Date', 'Amt'],
        ];
        $out = collect();
        foreach ($sources as [$table, $type, $dateCol, $amtCol]) {
            $q = DB::table("$table as t")
                ->join('employees as e', 'e.id', '=', 't.employee_id')
                ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                ->where('t.resort_id', $rid)->whereRaw("LOWER(COALESCE(t.Status,''))='paid'")
                ->when($f['employee'], fn($x) => $x->where('t.employee_id', $f['employee']))
                ->when(true, fn($x) => $this->applyDuration($x, $f, "t.$dateCol"))
                ->get([$this->nameExpr(), "t.$dateCol as pd", "t.$amtCol as amt", 't.Currency']);
            foreach ($q as $r) {
                $out->push([
                    'Employee Name' => trim($r->employee_name) ?: 'N/A',
                    'Payment Type'  => $type,
                    'Payment Date'  => $r->pd ? Carbon::parse($r->pd)->format('d M Y') : 'N/A',
                    'Amount'        => $this->money($r->amt, $r->Currency ?? 'MVR'),
                    'Status'        => 'Paid',
                    '_sort'         => $r->pd,
                ]);
            }
        }
        $rows = $out->sortByDesc('_sort')->map(fn($r) => collect($r)->except('_sort')->all())->values()->all();
        return ['columns' => ['Employee Name', 'Payment Type', 'Payment Date', 'Amount', 'Status'], 'rows' => $rows];
    }

    /* ---- expiry reports ---- */

    public function visaExpiry(array $f): array
    {
        return $this->expiryReport($f, 'visa_renewals', 'end_date',
            ['Employee Name', 'Nationality', 'Visa Expiry Date', 'Days Remaining'],
            fn($mode, $r = null) => $mode === 'select' ? [] : [
                'Employee Name'   => trim($r->employee_name) ?: 'N/A',
                'Nationality'     => $r->nationality ?? 'N/A',
                'Visa Expiry Date' => $r->expiry_date ? Carbon::parse($r->expiry_date)->format('d M Y') : 'N/A',
                'Days Remaining'  => $this->daysLeftLabel($r->expiry_date),
            ]);
    }

    public function wpExpiry(array $f): array
    {
        $period = $f['expiry_period'] ?? null;
        $rows = $this->expatBase($f)
            ->join('work_permits as t', 't.employee_id', '=', 'e.id')
            ->whereIn('t.id', fn($q) => $q->selectRaw('MAX(id)')->from('work_permits')->groupBy('employee_id'))
            ->when($period && $period !== 'all', fn($q) => $q->whereDate('t.Due_Date', '<=', Carbon::today()->addDays((int) $period)))
            ->orderBy('t.Due_Date')
            ->get([$this->nameExpr(), 't.Work_Permit_Number', 't.Due_Date'])
            ->map(fn($r) => [
                'Employee Name'     => trim($r->employee_name) ?: 'N/A',
                'Work Permit Number' => $r->Work_Permit_Number ?? 'N/A',
                'Expiry Date'       => $r->Due_Date ? Carbon::parse($r->Due_Date)->format('d M Y') : 'N/A',
                'Days Remaining'    => $this->daysLeftLabel($r->Due_Date),
            ])->all();
        return ['columns' => ['Employee Name', 'Work Permit Number', 'Expiry Date', 'Days Remaining'], 'rows' => $rows];
    }

    public function insuranceExpiry(array $f): array
    {
        $period = $f['expiry_period'] ?? null;
        $rows = $this->expatBase($f)
            ->join('employee_insurances as t', 't.employee_id', '=', 'e.id')
            ->whereIn('t.id', fn($q) => $q->selectRaw('MAX(id)')->from('employee_insurances')->groupBy('employee_id'))
            ->when($period && $period !== 'all', fn($q) => $q->whereDate('t.insurance_end_date', '<=', Carbon::today()->addDays((int) $period)))
            ->orderBy('t.insurance_end_date')
            ->get([$this->nameExpr(), 't.insurance_company', 't.insurance_end_date'])
            ->map(fn($r) => [
                'Employee Name'     => trim($r->employee_name) ?: 'N/A',
                'Insurance Provider' => $r->insurance_company ?? 'N/A',
                'Expiry Date'       => $r->insurance_end_date ? Carbon::parse($r->insurance_end_date)->format('d M Y') : 'N/A',
                'Days Remaining'    => $this->daysLeftLabel($r->insurance_end_date),
            ])->all();
        return ['columns' => ['Employee Name', 'Insurance Provider', 'Expiry Date', 'Days Remaining'], 'rows' => $rows];
    }

    public function medicalExpiry(array $f): array
    {
        $period = $f['expiry_period'] ?? null;
        $rows = $this->expatBase($f)
            ->join('work_permit_medical_renewals as t', 't.employee_id', '=', 'e.id')
            ->whereIn('t.id', fn($q) => $q->selectRaw('MAX(id)')->from('work_permit_medical_renewals')->groupBy('employee_id'))
            ->when($period && $period !== 'all', fn($q) => $q->whereDate('t.end_date', '<=', Carbon::today()->addDays((int) $period)))
            ->orderBy('t.end_date')
            ->get([$this->nameExpr(), 't.end_date'])
            ->map(fn($r) => [
                'Employee Name'      => trim($r->employee_name) ?: 'N/A',
                'Medical Expiry Date' => $r->end_date ? Carbon::parse($r->end_date)->format('d M Y') : 'N/A',
                'Days Remaining'     => $this->daysLeftLabel($r->end_date),
            ])->all();
        return ['columns' => ['Employee Name', 'Medical Expiry Date', 'Days Remaining'], 'rows' => $rows];
    }

    public function passportExpiry(array $f): array
    {
        // Passport EXPIRY date isn't stored in the schema — only passport_number.
        $rows = $this->expatBase($f)->orderBy('ra.first_name')
            ->get([$this->nameExpr(), 'e.passport_number'])
            ->map(fn($r) => [
                'Employee Name'        => trim($r->employee_name) ?: 'N/A',
                'Passport Number'      => $r->passport_number ?? 'N/A',
                'Passport Expiry Date' => 'N/A', // not stored
                'Days Remaining'       => 'N/A',
            ])->all();
        return ['columns' => ['Employee Name', 'Passport Number', 'Passport Expiry Date', 'Days Remaining'], 'rows' => $rows];
    }

    public function slotExpiry(array $f): array
    {
        $period = $f['expiry_period'] ?? null;
        $rows = $this->expatBase($f)
            ->join('quota_slot_renewals as t', 't.employee_id', '=', 'e.id')
            ->whereIn('t.id', fn($q) => $q->selectRaw('MAX(id)')->from('quota_slot_renewals')->groupBy('employee_id'))
            ->when($period && $period !== 'all', fn($q) => $q->whereDate('t.Due_Date', '<=', Carbon::today()->addDays((int) $period)))
            ->orderBy('t.Due_Date')
            ->get([$this->nameExpr(), 't.Due_Date'])
            ->map(fn($r) => [
                'Employee Name'   => trim($r->employee_name) ?: 'N/A',
                'Slot Expiry Date' => $r->Due_Date ? Carbon::parse($r->Due_Date)->format('d M Y') : 'N/A',
                'Days Remaining'  => $this->daysLeftLabel($r->Due_Date),
            ])->all();
        return ['columns' => ['Employee Name', 'Slot Expiry Date', 'Days Remaining'], 'rows' => $rows];
    }

    /** #24 Comprehensive Expiry Tracker. */
    public function comprehensiveExpiry(array $f): array
    {
        $rid = $this->resort->resort_id;
        $latest = fn($table, $col) => DB::table($table)->whereColumn('employee_id', 'e.id')
            ->where('resort_id', $rid)->orderByDesc('id')->limit(1)->select($col);

        $rows = $this->expatBase($f)->orderBy('ra.first_name')
            ->select([$this->nameExpr(),
                DB::raw('(' . $this->latestSub('visa_renewals', 'end_date', $rid) . ') as visa_exp'),
                DB::raw('(' . $this->latestSub('work_permits', 'Due_Date', $rid) . ') as wp_exp'),
                DB::raw('(' . $this->latestSub('employee_insurances', 'insurance_end_date', $rid) . ') as ins_exp'),
                DB::raw('(' . $this->latestSub('work_permit_medical_renewals', 'end_date', $rid) . ') as med_exp'),
                DB::raw('(' . $this->latestSub('quota_slot_renewals', 'Due_Date', $rid) . ') as slot_exp'),
            ])->get()
            ->map(fn($r) => [
                'Employee Name'      => trim($r->employee_name) ?: 'N/A',
                'Passport Expiry'    => 'N/A',
                'Visa Expiry'        => $r->visa_exp ? Carbon::parse($r->visa_exp)->format('d M Y') : 'N/A',
                'Work Permit Expiry' => $r->wp_exp ? Carbon::parse($r->wp_exp)->format('d M Y') : 'N/A',
                'Insurance Expiry'   => $r->ins_exp ? Carbon::parse($r->ins_exp)->format('d M Y') : 'N/A',
                'Medical Expiry'     => $r->med_exp ? Carbon::parse($r->med_exp)->format('d M Y') : 'N/A',
                'Slot Payment Expiry' => $r->slot_exp ? Carbon::parse($r->slot_exp)->format('d M Y') : 'N/A',
            ])->all();
        return ['columns' => ['Employee Name', 'Passport Expiry', 'Visa Expiry', 'Work Permit Expiry', 'Insurance Expiry', 'Medical Expiry', 'Slot Payment Expiry'], 'rows' => $rows];
    }

    private function latestSub(string $table, string $col, $rid): string
    {
        return "SELECT $col FROM $table WHERE $table.employee_id = e.id AND $table.resort_id = $rid ORDER BY id DESC LIMIT 1";
    }

    /** #25 Liability Summary. */
    public function liabilitySummary(array $f): array
    {
        $rid = $this->resort->resort_id;
        $unpaid = fn($table) => (float) DB::table($table)->where('resort_id', $rid)->whereRaw("LOWER(COALESCE(Status,''))<>'paid'")->sum('Amt');
        $wp = $unpaid('work_permits');
        $slot = $unpaid('quota_slot_renewals');
        $ins = (float) DB::table('employee_insurances')->where('resort_id', $rid)->whereRaw("LOWER(COALESCE(Status,''))<>'paid'")->sum('Premium');
        $med = (float) DB::table('work_permit_medical_renewals')->where('resort_id', $rid)->whereRaw("LOWER(COALESCE(Status,''))<>'paid'")->sum('Amt');
        return [
            'columns' => ['Work Permit Liability', 'Slot Fee Liability', 'Insurance Liability', 'Medical Liability', 'Total Liability'],
            'rows'    => [[
                'Work Permit Liability' => $this->money($wp),
                'Slot Fee Liability'    => $this->money($slot),
                'Insurance Liability'   => $this->money($ins),
                'Medical Liability'     => $this->money($med),
                'Total Liability'       => $this->money($wp + $slot + $ins + $med),
            ]],
        ];
    }

    /** #26 Liability Breakdown. */
    public function liabilityBreakdown(array $f): array
    {
        $rid = $this->resort->resort_id;
        $defs = [
            ['Work Permit', 'work_permits', 'Amt'],
            ['Quota Slot', 'quota_slot_renewals', 'Amt'],
            ['Insurance', 'employee_insurances', 'Premium'],
            ['Medical', 'work_permit_medical_renewals', 'Amt'],
        ];
        $rows = [];
        foreach ($defs as [$label, $table, $col]) {
            $total = (float) DB::table($table)->where('resort_id', $rid)->sum($col);
            $paid  = (float) DB::table($table)->where('resort_id', $rid)->whereRaw("LOWER(COALESCE(Status,''))='paid'")->sum($col);
            $rows[] = [
                'Liability Type'      => $label,
                'Total Liability'     => $this->money($total),
                'Paid Amount'         => $this->money($paid),
                'Outstanding Balance' => $this->money($total - $paid),
            ];
        }
        return ['columns' => ['Liability Type', 'Total Liability', 'Paid Amount', 'Outstanding Balance'], 'rows' => $rows];
    }

    /** #27 Outstanding Liability (per employee). */
    public function outstandingLiability(array $f): array
    {
        $rid = $this->resort->resort_id;
        $defs = [
            ['Work Permit', 'work_permits', 'Amt', 'Due_Date'],
            ['Quota Slot', 'quota_slot_renewals', 'Amt', 'Due_Date'],
            ['Insurance', 'employee_insurances', 'Premium', 'insurance_end_date'],
            ['Medical', 'work_permit_medical_renewals', 'Amt', 'end_date'],
        ];
        $out = [];
        foreach ($defs as [$label, $table, $col, $dueCol]) {
            if ($f['liability_type'] && $f['liability_type'] !== $label) continue;
            $rows = DB::table("$table as t")
                ->join('employees as e', 'e.id', '=', 't.employee_id')
                ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                ->where('t.resort_id', $rid)->whereRaw("LOWER(COALESCE(t.Status,''))<>'paid'")
                ->where("t.$col", '>', 0)
                ->get([$this->nameExpr(), "t.$col as amt", "t.$dueCol as due"]);
            foreach ($rows as $r) {
                $out[] = [
                    'Employee Name'     => trim($r->employee_name) ?: 'N/A',
                    'Liability Category' => $label,
                    'Amount Due'        => $this->money($r->amt),
                    'Due Date'          => $r->due ? Carbon::parse($r->due)->format('d M Y') : 'N/A',
                ];
            }
        }
        return ['columns' => ['Employee Name', 'Liability Category', 'Amount Due', 'Due Date'], 'rows' => $out];
    }

    /** #28 Liability Trend (monthly, by year). */
    public function liabilityTrend(array $f): array
    {
        $rid = $this->resort->resort_id;
        $year = $f['year'] ?: date('Y');
        $rows = [];
        for ($m = 1; $m <= 12; $m++) {
            $total = 0; $paid = 0;
            foreach ([['work_permits', 'Amt', 'Due_Date'], ['quota_slot_renewals', 'Amt', 'Due_Date'], ['employee_insurances', 'Premium', 'insurance_end_date'], ['work_permit_medical_renewals', 'Amt', 'end_date']] as [$table, $col, $dueCol]) {
                $base = DB::table($table)->where('resort_id', $rid)->whereRaw("YEAR($dueCol)=?", [$year])->whereRaw("MONTH($dueCol)=?", [$m]);
                $total += (float) (clone $base)->sum($col);
                $paid  += (float) (clone $base)->whereRaw("LOWER(COALESCE(Status,''))='paid'")->sum($col);
            }
            if ($total == 0 && $paid == 0) continue;
            $rows[] = [
                'Month'                => Carbon::create()->month($m)->format('F'),
                'Total Liability'      => $this->money($total),
                'Paid Liability'       => $this->money($paid),
                'Outstanding Liability' => $this->money($total - $paid),
            ];
        }
        return ['columns' => ['Month', 'Total Liability', 'Paid Liability', 'Outstanding Liability'], 'rows' => $rows];
    }

    /** #29 Payment Status. */
    public function paymentStatus(array $f): array
    {
        $rid = $this->resort->resort_id;
        $defs = [['Work Permit', 'work_permits', 'Amt'], ['Quota Slot', 'quota_slot_renewals', 'Amt'], ['Insurance', 'employee_insurances', 'Premium'], ['Medical', 'work_permit_medical_renewals', 'Amt']];
        $rows = [];
        foreach ($defs as [$label, $table, $col]) {
            $total = (float) DB::table($table)->where('resort_id', $rid)->sum($col);
            $paid  = (float) DB::table($table)->where('resort_id', $rid)->whereRaw("LOWER(COALESCE(Status,''))='paid'")->sum($col);
            $rows[] = [
                'Payment Type'     => $label,
                'Requested Amount' => $this->money($total),
                'Paid Amount'      => $this->money($paid),
                'Pending Amount'   => $this->money($total - $paid),
            ];
        }
        return ['columns' => ['Payment Type', 'Requested Amount', 'Paid Amount', 'Pending Amount'], 'rows' => $rows];
    }

    /** #30 Immigration Transaction History. */
    public function immigrationTransactions(array $f): array
    {
        $rows = DB::table('visa_transection_histories as t')
            ->leftJoin('employees as e', 'e.id', '=', 't.Employee_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->where('t.resort_id', $this->resort->resort_id)
            ->when(true, fn($q) => $this->applyDuration($q, $f, 't.Payment_Date'))
            ->orderByDesc('t.Payment_Date')
            ->get(['t.Payment_Date', $this->nameExpr(), 't.Amt', 't.transaction_id'])
            ->map(fn($r) => [
                'Transaction Date' => $r->Payment_Date ? Carbon::parse($r->Payment_Date)->format('d M Y') : 'N/A',
                'Employee Name'    => trim($r->employee_name) ?: 'N/A',
                'Transaction Type' => 'Wallet Transfer',
                'Amount'           => $this->money($r->Amt),
                'Status'           => 'Completed',
            ])->all();
        return ['columns' => ['Transaction Date', 'Employee Name', 'Transaction Type', 'Amount', 'Status'], 'rows' => $rows];
    }

    /** #31 Employee Immigration Profile. */
    public function employeeImmigration(array $f): array
    {
        $rid = $this->resort->resort_id;
        $rows = $this->expatBase($f)->orderBy('ra.first_name')
            ->select([$this->nameExpr(), 'e.passport_number',
                DB::raw('(' . $this->latestSub('visa_renewals', 'end_date', $rid) . ') as visa_exp'),
                DB::raw('(' . $this->latestSub('work_permits', 'Due_Date', $rid) . ') as wp_exp'),
                DB::raw('(' . $this->latestSub('work_permit_medical_renewals', 'end_date', $rid) . ') as med_exp'),
                DB::raw('(' . $this->latestSub('employee_insurances', 'insurance_end_date', $rid) . ') as ins_exp'),
                DB::raw('(' . $this->latestSub('quota_slot_renewals', 'Due_Date', $rid) . ') as slot_exp'),
            ])->get()
            ->map(fn($r) => [
                'Employee Name'        => trim($r->employee_name) ?: 'N/A',
                'Passport'             => $r->passport_number ?? 'N/A',
                'Visa'                 => $r->visa_exp ? 'Until ' . Carbon::parse($r->visa_exp)->format('d M Y') : 'N/A',
                'Work Permit'          => $r->wp_exp ? 'Until ' . Carbon::parse($r->wp_exp)->format('d M Y') : 'N/A',
                'Medical'              => $r->med_exp ? 'Until ' . Carbon::parse($r->med_exp)->format('d M Y') : 'N/A',
                'Insurance'            => $r->ins_exp ? 'Until ' . Carbon::parse($r->ins_exp)->format('d M Y') : 'N/A',
                'Slot Payment'         => $r->slot_exp ? 'Until ' . Carbon::parse($r->slot_exp)->format('d M Y') : 'N/A',
            ])->all();
        return ['columns' => ['Employee Name', 'Passport', 'Visa', 'Work Permit', 'Medical', 'Insurance', 'Slot Payment'], 'rows' => $rows];
    }

    /** #32 Immigration Compliance. */
    public function immigrationCompliance(array $f): array
    {
        $rid = $this->resort->resort_id;
        $rows = $this->expatBase($f)->orderBy('ra.first_name')
            ->select([$this->nameExpr(),
                DB::raw('(' . $this->latestSub('visa_renewals', 'end_date', $rid) . ') as visa_exp'),
                DB::raw('(' . $this->latestSub('work_permits', 'Due_Date', $rid) . ') as wp_exp'),
                DB::raw('(' . $this->latestSub('work_permit_medical_renewals', 'end_date', $rid) . ') as med_exp'),
                DB::raw('(' . $this->latestSub('employee_insurances', 'insurance_end_date', $rid) . ') as ins_exp'),
            ])->get()
            ->map(function ($r) {
                $ok = fn($d) => $d && Carbon::parse($d)->isFuture();
                $st = fn($d) => $ok($d) ? 'Valid' : ($d ? 'Expired' : 'Missing');
                $all = $ok($r->visa_exp) && $ok($r->wp_exp) && $ok($r->med_exp) && $ok($r->ins_exp);
                return [
                    'Employee Name'      => trim($r->employee_name) ?: 'N/A',
                    'Visa Status'        => $st($r->visa_exp),
                    'Work Permit Status' => $st($r->wp_exp),
                    'Medical Status'     => $st($r->med_exp),
                    'Insurance Status'   => $st($r->ins_exp),
                    'Compliance Status'  => $all ? 'Compliant' : 'Non-Compliant',
                ];
            })->all();
        return ['columns' => ['Employee Name', 'Visa Status', 'Work Permit Status', 'Medical Status', 'Insurance Status', 'Compliance Status'], 'rows' => $rows];
    }

    /** #33 Immigration Audit Trail. */
    public function immigrationAudit(array $f): array
    {
        $rows = DB::table('audit_logs as a')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'a.created_by')
            ->where('a.resort_id', $this->resort->resort_id)
            ->when(true, fn($q) => $this->applyDuration($q, $f, 'a.created_at'))
            ->orderByDesc('a.created_at')->limit(1000)
            ->get(['a.TypeofAction', 'a.uploaded_by', 'a.created_at',
                DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as performed_by")])
            ->map(fn($r) => [
                'Activity'     => $r->TypeofAction ?? 'N/A',
                'Performed By' => trim($r->performed_by) ?: ($r->uploaded_by ?? 'N/A'),
                'Date & Time'  => $r->created_at ? Carbon::parse($r->created_at)->format('d M Y H:i') : 'N/A',
            ])->all();
        return ['columns' => ['Activity', 'Performed By', 'Date & Time'], 'rows' => $rows];
    }

    /** #34 Executive Dashboard. */
    public function execDashboard(array $f): array
    {
        $rid = $this->resort->resort_id;
        $s = $this->execSummary($f)['rows'][0];
        $outstanding = (float) DB::table('work_permits')->where('resort_id', $rid)->whereRaw("LOWER(COALESCE(Status,''))<>'paid'")->sum('Amt')
            + (float) DB::table('quota_slot_renewals')->where('resort_id', $rid)->whereRaw("LOWER(COALESCE(Status,''))<>'paid'")->sum('Amt');
        $wallet = (float) DB::table('visa_wallets')->where('resort_id', $rid)->sum('Amt');
        $pending = DB::table('payment_requests')->where('resort_id', $rid)->whereIn('Status', ['Pending', 'SendtoFinance'])->count();
        $compliance = $this->immigrationCompliance($f)['rows'];
        $compliant = collect($compliance)->where('Compliance Status', 'Compliant')->count();
        $rate = count($compliance) ? round($compliant / count($compliance) * 100, 1) . '%' : '0%';

        return [
            'columns' => ['Total Expat Employees', 'Total Outstanding Liability', 'Wallet Balance', 'Upcoming Expiries (90d)', 'Pending Payments', 'Compliance Rate'],
            'rows'    => [[
                'Total Expat Employees'       => $s['Total Expat Employees'],
                'Total Outstanding Liability' => $this->money($outstanding),
                'Wallet Balance'              => $this->money($wallet),
                'Upcoming Expiries (90d)'     => $s['Upcoming Expiries (90d)'],
                'Pending Payments'            => $pending,
                'Compliance Rate'             => $rate,
            ]],
        ];
    }

    /** #36 Renewal Forecast. */
    public function renewalForecast(array $f): array
    {
        $rid = $this->resort->resort_id;
        $period = $f['expiry_period'] && $f['expiry_period'] !== 'all' ? (int) $f['expiry_period'] : 90;
        $cut = Carbon::today()->addDays($period);
        $defs = [
            ['Visa', 'visa_renewals', 'end_date', 'Amt'],
            ['Insurance', 'employee_insurances', 'insurance_end_date', 'Premium'],
            ['Medical', 'work_permit_medical_renewals', 'end_date', 'Amt'],
        ];
        $out = [];
        foreach ($defs as [$type, $table, $dateCol, $amtCol]) {
            $rows = DB::table("$table as t")
                ->join('employees as e', 'e.id', '=', 't.employee_id')
                ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                ->where('t.resort_id', $rid)
                ->whereIn('t.id', fn($q) => $q->selectRaw('MAX(id)')->from($table)->groupBy('employee_id'))
                ->whereDate("t.$dateCol", '<=', $cut)
                ->get([$this->nameExpr(), "t.$dateCol as d", "t.$amtCol as amt"]);
            foreach ($rows as $r) {
                $out[] = [
                    'Employee Name'  => trim($r->employee_name) ?: 'N/A',
                    'Renewal Type'   => $type,
                    'Renewal Date'   => $r->d ? Carbon::parse($r->d)->format('d M Y') : 'N/A',
                    'Estimated Cost' => $this->money($r->amt ?? 0),
                    '_sort'          => $r->d,
                ];
            }
        }
        usort($out, fn($a, $b) => strcmp($a['_sort'] ?? '', $b['_sort'] ?? ''));
        $out = array_map(fn($r) => collect($r)->except('_sort')->all(), $out);
        return ['columns' => ['Employee Name', 'Renewal Type', 'Renewal Date', 'Estimated Cost'], 'rows' => $out];
    }

    /** #37 Immigration Cost by Nationality. */
    public function costByNationality(array $f): array
    {
        $rows = $this->expatBase($f)
            ->leftJoin('total_expensess_since_joings as t', 't.employees_id', '=', 'e.id')
            ->groupBy('e.nationality')
            ->select('e.nationality', DB::raw('COUNT(DISTINCT e.id) c'),
                DB::raw('SUM(t.Deposit_Amt) dep'), DB::raw('SUM(t.Total_work_permit) wp'),
                DB::raw('SUM(t.Total_insurance_Payment) ins'), DB::raw('SUM(t.Total_Work_Permit_Medical_Payment) med'))
            ->orderBy('e.nationality')->get()
            ->map(fn($r) => [
                'Nationality'           => $r->nationality ?? 'N/A',
                'Employee Count'        => (int) $r->c,
                'Deposit Total'         => $this->money($r->dep ?? 0),
                'Permit Fees'           => $this->money($r->wp ?? 0),
                'Insurance Cost'        => $this->money($r->ins ?? 0),
                'Medical Cost'          => $this->money($r->med ?? 0),
                'Total Immigration Cost' => $this->money(($r->dep ?? 0) + ($r->wp ?? 0) + ($r->ins ?? 0) + ($r->med ?? 0)),
            ])->all();
        return ['columns' => ['Nationality', 'Employee Count', 'Deposit Total', 'Permit Fees', 'Insurance Cost', 'Medical Cost', 'Total Immigration Cost'], 'rows' => $rows];
    }

    /** #38 Deposit Exposure. */
    public function depositExposure(array $f): array
    {
        $rows = $this->expatBase($f)
            ->leftJoin('total_expensess_since_joings as t', 't.employees_id', '=', 'e.id')
            ->leftJoin('employee_resignation as r', 'r.employee_id', '=', 'e.id')
            ->where('t.Deposit_Amt', '>', 0)
            ->orderBy('ra.first_name')
            ->get([$this->nameExpr(), 'e.nationality', 't.Deposit_Amt', 'r.Deposit_withdraw', 'e.status'])
            ->map(fn($r) => [
                'Employee Name'     => trim($r->employee_name) ?: 'N/A',
                'Nationality'       => $r->nationality ?? 'N/A',
                'Deposit Amount'    => $this->money($r->Deposit_Amt),
                'Wallet Status'     => $r->status ?? 'N/A',
                'Refund Eligibility' => $r->Deposit_withdraw === 'Yes' ? 'Refunded' : (in_array($r->status, ['Resigned', 'Terminated']) ? 'Eligible' : 'Held'),
            ])->all();
        return ['columns' => ['Employee Name', 'Nationality', 'Deposit Amount', 'Wallet Status', 'Refund Eligibility'], 'rows' => $rows];
    }

    /** #39 Document Compliance Matrix. */
    public function documentCompliance(array $f): array
    {
        $rid = $this->resort->resort_id;
        $has = fn($table) => "(EXISTS (SELECT 1 FROM $table WHERE $table.employee_id = e.id AND $table.resort_id = $rid))";
        $rows = $this->expatBase($f)->orderBy('ra.first_name')
            ->select([$this->nameExpr(),
                'e.passport_number',
                DB::raw($has('visa_renewals') . ' as has_visa'),
                DB::raw($has('work_permits') . ' as has_wp'),
                DB::raw($has('employee_insurances') . ' as has_ins'),
                DB::raw($has('work_permit_medical_renewals') . ' as has_med'),
                DB::raw($has('quota_slot_renewals') . ' as has_slot'),
            ])->get()
            ->map(function ($r) {
                $y = fn($v) => $v ? 'Yes' : 'No';
                $count = ($r->passport_number ? 1 : 0) + $r->has_visa + $r->has_wp + $r->has_ins + $r->has_med + $r->has_slot;
                return [
                    'Employee Name'     => trim($r->employee_name) ?: 'N/A',
                    'Passport'          => $y($r->passport_number),
                    'Visa'              => $y($r->has_visa),
                    'Work Permit'       => $y($r->has_wp),
                    'Insurance'         => $y($r->has_ins),
                    'Medical'           => $y($r->has_med),
                    'Quota Slot'        => $y($r->has_slot),
                    'Overall Compliance' => $count >= 6 ? 'Complete' : round($count / 6 * 100) . '%',
                ];
            })->all();
        return ['columns' => ['Employee Name', 'Passport', 'Visa', 'Work Permit', 'Insurance', 'Medical', 'Quota Slot', 'Overall Compliance'], 'rows' => $rows];
    }

    /** #40 Immigration Budget Forecast (monthly, by year). */
    public function budgetForecast(array $f): array
    {
        $rid = $this->resort->resort_id;
        $year = $f['year'] ?: date('Y');
        $rows = [];
        for ($m = 1; $m <= 12; $m++) {
            $wp = (float) DB::table('work_permits')->where('resort_id', $rid)->whereRaw('YEAR(Due_Date)=?', [$year])->whereRaw('MONTH(Due_Date)=?', [$m])->sum('Amt');
            $slot = (float) DB::table('quota_slot_renewals')->where('resort_id', $rid)->whereRaw('YEAR(Due_Date)=?', [$year])->whereRaw('MONTH(Due_Date)=?', [$m])->sum('Amt');
            $ins = (float) DB::table('employee_insurances')->where('resort_id', $rid)->whereRaw('YEAR(insurance_end_date)=?', [$year])->whereRaw('MONTH(insurance_end_date)=?', [$m])->sum('Premium');
            $med = (float) DB::table('work_permit_medical_renewals')->where('resort_id', $rid)->whereRaw('YEAR(end_date)=?', [$year])->whereRaw('MONTH(end_date)=?', [$m])->sum('Amt');
            if ($wp + $slot + $ins + $med == 0) continue;
            $rows[] = [
                'Month'                 => Carbon::create()->month($m)->format('F'),
                'Work Permit Fees'      => $this->money($wp),
                'Quota Slot Fees'       => $this->money($slot),
                'Insurance'             => $this->money($ins),
                'Medical'               => $this->money($med),
                'Total Forecasted Cost' => $this->money($wp + $slot + $ins + $med),
            ];
        }
        return ['columns' => ['Month', 'Work Permit Fees', 'Quota Slot Fees', 'Insurance', 'Medical', 'Total Forecasted Cost'], 'rows' => $rows];
    }

    /** #41 Deposit Aging. */
    public function depositAging(array $f): array
    {
        $rows = $this->expatBase($f)
            ->join('total_expensess_since_joings as t', 't.employees_id', '=', 'e.id')
            ->leftJoin('employee_resignation as r', 'r.employee_id', '=', 'e.id')
            ->where('t.Deposit_Amt', '>', 0)
            ->when(true, fn($q) => $this->applyDuration($q, $f, 't.Date'))
            ->orderBy('t.Date')
            ->get([$this->nameExpr(), 'e.nationality', 't.Deposit_Amt', 't.Date', 'r.Deposit_withdraw'])
            ->map(fn($r) => [
                'Employee Name'   => trim($r->employee_name) ?: 'N/A',
                'Nationality'     => $r->nationality ?? 'N/A',
                'Deposit Amount'  => $this->money($r->Deposit_Amt),
                'Deposit Date'    => $r->Date ? Carbon::parse($r->Date)->format('d M Y') : 'N/A',
                'Days Outstanding' => $r->Date ? Carbon::parse($r->Date)->diffInDays(Carbon::today()) : 'N/A',
                'Refund Status'   => $r->Deposit_withdraw === 'Yes' ? 'Refunded' : 'Held',
            ])->all();
        return ['columns' => ['Employee Name', 'Nationality', 'Deposit Amount', 'Deposit Date', 'Days Outstanding', 'Refund Status'], 'rows' => $rows];
    }
}
