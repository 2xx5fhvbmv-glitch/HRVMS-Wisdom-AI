<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Resorts\Concerns\PredefinedReportActions;
use App\Helpers\Common;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Predefined People Management – Salary Increment reports (Option B), generic view.
 *
 * Source: people_salary_increment (employee_id, previous_salary/new_salary,
 * increment_amount, pay_increase_type/value, increment_type [plain string —
 * NOT a FK to increment_types; the create/edit forms just copy the picked
 * increment_types.name onto this column], effective_date, status) joined to
 * employees (department/position live on the employee, not the increment
 * row) and, for the currently-pending stage, people_salary_increment_status
 * (approval_rank Finance/GM) — see People\SalaryIncrementController, the
 * live module these mirror.
 *
 * Top-level `status` values seen in data: Pending, Hold, Approved, Rejected,
 * Change-Request. NOTE (diverges from Promotion): on the live module, "Hold"
 * on /summary-list hides the Approve/Reject/On-Hold action buttons for that
 * row but does NOT remove it from HR's /list view — Hold is a pause on the
 * approver's footer, not a terminal state, and a row can be edited by HR out
 * of Hold (which reopens the chain to Pending). We treat Hold the same way
 * Promotion treats "On Hold": still "pending" for reporting purposes.
 *
 * `people_salary_increment` has no `increment_percentage` column. When
 * pay_increase_type = 'Percentage', `value` IS the percentage entered by
 * HR. When pay_increase_type = 'Fixed', `value` is the flat amount (same as
 * increment_amount) and the percentage must be derived from
 * increment_amount / previous_salary * 100. See pctFor() below.
 */
class SalaryIncrementReportController extends Controller
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
            'salary_increment_register' => [
                'name' => 'Salary Increment Register',
                'description' => 'Displays all salary increment requests processed during the selected period, including salary revisions, increment type, effective dates, and approval status. This serves as the master salary increment report.',
                'filters' => ['duration', 'department', 'position', 'increment_type', 'increment_status', 'employee'],
                'handler' => 'salaryIncrementRegister',
            ],
            'employee_salary_increment_history' => [
                'name' => 'Employee Salary Increment History Report',
                'description' => 'Displays the complete salary increment history of a selected employee, enabling HR to review compensation progression over time.',
                'filters' => ['duration', 'employee'],
                'handler' => 'employeeSalaryIncrementHistory',
            ],
            'pending_salary_increment_approval' => [
                'name' => 'Pending Salary Increment Approval Report',
                'description' => 'Displays salary increment requests awaiting approval or currently on hold, allowing HR and management to monitor approval workflows.',
                'filters' => ['duration', 'department', 'increment_status'],
                'handler' => 'pendingSalaryIncrementApproval',
            ],
            'salary_increment_financial_impact' => [
                'name' => 'Salary Increment Financial Impact Report',
                'description' => 'Displays the projected financial impact of proposed salary increments, helping HR and Finance evaluate payroll cost before approval. This report summarizes the selected salary increment proposals and their effect on payroll budgets.',
                'filters' => ['duration', 'department', 'increment_status'],
                'handler' => 'salaryIncrementFinancialImpact',
            ],
            'salary_increment_analysis' => [
                'name' => 'Salary Increment Analysis Report',
                'description' => 'Summarizes salary increment trends across departments and positions, helping management analyze compensation adjustments and identify distribution patterns.',
                'filters' => ['duration', 'department', 'increment_type'],
                'handler' => 'salaryIncrementAnalysis',
            ],
            'salary_increment_executive_summary' => [
                'name' => 'Salary Increment Executive Summary',
                'description' => 'Provides management with an overview of salary increment activities, including approved, pending, and rejected increments, together with their financial impact on payroll.',
                'filters' => ['duration'],
                'handler' => 'salaryIncrementExecutiveSummary',
            ],
        ];
    }

    /* --------------------------------------------------------------- plumbing */

    public function index()
    {
        if (Common::checkRouteWisePermission('resort.report.index', config('settings.resort_permissions.view')) == false) return abort(403, 'Unauthorized access');
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();

        $reports = collect($this->registry())->map(fn($r, $key) => [
            'key' => $key, 'name' => $r['name'], 'description' => $r['description'],
            'filters' => array_values(array_unique(array_merge($r['filters'], ['duration']))),
        ])->values();

        $departments = DB::table('resort_departments')->where('resort_id', $rid)->when($scoped !== null, fn($q) => $q->whereIn('id', $scoped))->orderBy('name')->get(['id', 'name']);
        $positions = DB::table('resort_positions')->where('resort_id', $rid)->orderBy('position_title')->get(['id', 'position_title']);
        $employees = DB::table('employees as e')->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->where('e.resort_id', $rid)->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))
            ->orderBy('ra.first_name')->get(['e.id', DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as name")]);
        $incrementTypes = DB::table('increment_types')->where('resort_id', $rid)->where('status', 'Active')->orderBy('name')->get(['name']);

        $filterDefs = [
            ['filter' => 'department', 'name' => 'department', 'label' => 'Department', 'type' => 'select', 'placeholder' => 'All departments', 'options' => $departments->map(fn($d) => ['value' => $d->id, 'label' => $d->name])->all()],
            ['filter' => 'position', 'name' => 'position', 'label' => 'Position', 'type' => 'select', 'placeholder' => 'All positions', 'options' => $positions->map(fn($p) => ['value' => $p->id, 'label' => $p->position_title])->all()],
            ['filter' => 'employee', 'name' => 'employee', 'label' => 'Employee Name', 'type' => 'select', 'placeholder' => 'All employees', 'options' => $employees->map(fn($e) => ['value' => $e->id, 'label' => $e->name])->all()],
            ['filter' => 'increment_type', 'name' => 'increment_type', 'label' => 'Increment Type', 'type' => 'select', 'placeholder' => 'All increment types', 'options' => $incrementTypes->map(fn($t) => ['value' => $t->name, 'label' => $t->name])->all()],
            ['filter' => 'increment_status', 'name' => 'increment_status', 'label' => 'Increment Status', 'type' => 'select', 'placeholder' => 'All statuses', 'options' => collect(['Pending', 'Hold', 'Change-Request', 'Approved', 'Rejected'])->map(fn($s) => ['value' => $s, 'label' => $s])->all()],
            ['filter' => 'duration', 'name' => 'from_date', 'label' => 'From Date', 'type' => 'date'],
            ['filter' => 'duration', 'name' => 'to_date', 'label' => 'To Date', 'type' => 'date'],
        ];

        return view('resorts.reports.module_report', [
            'page_title' => 'Salary Increment Reports', 'reports' => $reports, 'filterDefs' => $filterDefs,
            'runRoute' => 'resort.report.salary_increment.run', 'exportRoute' => 'resort.report.salary_increment.export', 'insightsRoute' => 'resort.report.salary_increment.insights',
        ]);
    }

    private function filtersFrom(Request $request): array
    {
        return collect(['department', 'position', 'employee', 'increment_type', 'increment_status', 'from_date', 'to_date'])
            ->mapWithKeys(fn($k) => [$k => $request->input($k) ?: null])->all();
    }

    private function compute(string $key, array $filters): ?array
    {
        $registry = $this->registry();
        if (!isset($registry[$key])) return null;
        $res = $this->{$registry[$key]['handler']}($filters);
        return ['name' => $registry[$key]['name'], 'description' => $registry[$key]['description'],
            'columns' => $res['columns'], 'rows' => $this->appendTotalsRow($res['columns'], $res['rows'])];
    }

    public function run(Request $request)
    {
        if (Common::checkRouteWisePermission('resort.report.index', config('settings.resort_permissions.view')) == false) return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        $c = $this->compute((string) $request->input('report'), $this->filtersFrom($request));
        if (!$c) return response()->json(['success' => false, 'message' => 'Unknown report.'], 422);
        $html = view('resorts.renderfiles.ReportFilterData', ['report' => (object) ['name' => $c['name']], 'columns' => $c['columns'], 'data' => $c['rows']])->render();
        return response()->json(['success' => true, 'html' => $html, 'count' => count($c['rows'])]);
    }

    public function export(Request $request)
    {
        if (Common::checkRouteWisePermission('resort.report.index', config('settings.resort_permissions.view')) == false) return abort(403, 'Unauthorized access');
        $c = $this->compute((string) $request->input('report'), $this->filtersFrom($request));
        if (!$c) return abort(404, 'Unknown report');
        return $this->exportComputedReport($c['name'], $c['description'], $c['columns'], $c['rows'], $request->input('format', 'pdf'));
    }

    public function insights(Request $request)
    {
        if (Common::checkRouteWisePermission('resort.report.index', config('settings.resort_permissions.view')) == false) return response()->json(['status' => false], 403);
        $c = $this->compute((string) $request->input('report'), $this->filtersFrom($request));
        if (!$c) return response()->json(['status' => false, 'message' => 'Unknown report.'], 422);
        return response()->json(['status' => true, 'data' => $this->computeAiInsightsText($c['name'], $c['description'], $c['columns'], $c['rows'])]);
    }

    /* --------------------------------------------------------------- shared query */

    private function baseQuery(int $rid, ?array $scoped)
    {
        return DB::table('people_salary_increment as psi')
            ->join('employees as e', 'e.id', '=', 'psi.employee_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->leftJoin('resort_departments as d', 'd.id', '=', 'e.Dept_id')
            ->leftJoin('resort_positions as p', 'p.id', '=', 'e.Position_id')
            ->where('psi.resort_id', $rid)
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped));
    }

    /**
     * Derive the increment percentage — the source table has no
     * increment_percentage column. When the row was entered as a straight
     * percentage, `value` already IS that percentage. When entered as a
     * fixed amount, derive it from amount / previous salary.
     */
    private function pctFor(?string $payIncreaseType, $value, $incrementAmount, $previousSalary): ?float
    {
        if ($payIncreaseType === 'Percentage') return round((float) $value, 2);
        $prev = (float) $previousSalary;
        if ($prev <= 0) return null;
        return round(((float) $incrementAmount / $prev) * 100, 2);
    }

    /**
     * Current bottleneck stage for an increment row — the first per-stage
     * status row (Finance, then GM) that is not yet Approved/Rejected.
     * Works for Pending, Hold, AND Change-Request rows alike (unlike
     * Promotion's pendingApproverFor, which only resolves for status ===
     * 'Pending' — Hold on this module is a pause on a specific stage, not a
     * separate lock, so the same lookup applies).
     */
    private function pendingApproverFor(int $incrementId): string
    {
        $stage = DB::table('people_salary_increment_status')
            ->where('people_salary_increment_id', $incrementId)
            ->whereIn('status', ['Pending', 'Hold', 'Change-Request'])
            ->orderBy('id')
            ->first();
        return $stage->approval_rank ?? 'N/A';
    }

    /* --------------------------------------------------------------- reports */

    public function salaryIncrementRegister(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();

        $rows = $this->baseQuery($rid, $scoped)
            ->when($f['department'] ?? null, fn($q) => $q->where('e.Dept_id', $f['department']))
            ->when($f['position'] ?? null, fn($q) => $q->where('e.Position_id', $f['position']))
            ->when($f['increment_type'] ?? null, fn($q) => $q->where('psi.increment_type', $f['increment_type']))
            ->when($f['increment_status'] ?? null, fn($q) => $q->where('psi.status', $f['increment_status']))
            ->when($f['employee'] ?? null, fn($q) => $q->where('e.id', $f['employee']))
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('psi.effective_date', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('psi.effective_date', '<=', $f['to_date']))
            ->orderByDesc('psi.effective_date')
            ->get([
                'e.Emp_id', DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as employee_name"),
                'd.name as dept_name', 'p.position_title',
                'psi.previous_salary', 'psi.new_salary', 'psi.increment_amount', 'psi.pay_increase_type', 'psi.value',
                'psi.increment_type', 'psi.effective_date', 'psi.remarks', 'psi.status',
            ])
            ->map(fn($r) => [
                'Employee ID'           => $r->Emp_id ?: 'N/A',
                'Employee Name'         => trim($r->employee_name) ?: 'N/A',
                'Department'            => $r->dept_name ?? 'N/A',
                'Position'              => $r->position_title ?? 'N/A',
                'Current Salary'        => $r->previous_salary !== null ? number_format((float) $r->previous_salary, 2) : 'N/A',
                'New Salary'            => $r->new_salary !== null ? number_format((float) $r->new_salary, 2) : 'N/A',
                'Increment Amount'      => $r->increment_amount !== null ? number_format((float) $r->increment_amount, 2) : 'N/A',
                'Increment Percentage'  => ($pct = $this->pctFor($r->pay_increase_type, $r->value, $r->increment_amount, $r->previous_salary)) !== null ? $pct . '%' : 'N/A',
                'Increment Type'        => $r->increment_type ?: 'N/A',
                'Effective Date'        => $r->effective_date ? Carbon::parse($r->effective_date)->format('d M Y') : 'N/A',
                'Reason'                => $r->remarks ?: 'N/A',
                'Status'                => $r->status ?: 'N/A',
            ])->values()->all();

        return [
            'columns' => ['Employee ID', 'Employee Name', 'Department', 'Position', 'Current Salary', 'New Salary', 'Increment Amount', 'Increment Percentage', 'Increment Type', 'Effective Date', 'Reason', 'Status'],
            'rows'    => $rows,
        ];
    }

    public function employeeSalaryIncrementHistory(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();

        $rows = $this->baseQuery($rid, $scoped)
            ->when($f['employee'] ?? null, fn($q) => $q->where('e.id', $f['employee']))
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('psi.effective_date', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('psi.effective_date', '<=', $f['to_date']))
            ->orderByDesc('psi.effective_date')
            ->get([
                'e.Emp_id', DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as employee_name"),
                'd.name as dept_name', 'p.position_title',
                'psi.previous_salary', 'psi.new_salary', 'psi.increment_amount', 'psi.pay_increase_type', 'psi.value',
                'psi.increment_type', 'psi.effective_date', 'psi.remarks', 'psi.status',
            ])
            ->map(fn($r) => [
                'Employee ID'           => $r->Emp_id ?: 'N/A',
                'Employee Name'         => trim($r->employee_name) ?: 'N/A',
                'Department'            => $r->dept_name ?? 'N/A',
                'Position'              => $r->position_title ?? 'N/A',
                'Previous Salary'       => $r->previous_salary !== null ? number_format((float) $r->previous_salary, 2) : 'N/A',
                'Revised Salary'        => $r->new_salary !== null ? number_format((float) $r->new_salary, 2) : 'N/A',
                'Increment Amount'      => $r->increment_amount !== null ? number_format((float) $r->increment_amount, 2) : 'N/A',
                'Increment Percentage'  => ($pct = $this->pctFor($r->pay_increase_type, $r->value, $r->increment_amount, $r->previous_salary)) !== null ? $pct . '%' : 'N/A',
                'Increment Type'        => $r->increment_type ?: 'N/A',
                'Effective Date'        => $r->effective_date ? Carbon::parse($r->effective_date)->format('d M Y') : 'N/A',
                'Reason'                => $r->remarks ?: 'N/A',
                'Status'                => $r->status ?: 'N/A',
            ])->values()->all();

        return [
            'columns' => ['Employee ID', 'Employee Name', 'Department', 'Position', 'Previous Salary', 'Revised Salary', 'Increment Amount', 'Increment Percentage', 'Increment Type', 'Effective Date', 'Reason', 'Status'],
            'rows'    => $rows,
        ];
    }

    public function pendingSalaryIncrementApproval(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();

        $rows = $this->baseQuery($rid, $scoped)
            ->whereIn('psi.status', ['Pending', 'Hold', 'Change-Request'])
            ->when($f['increment_status'] ?? null, fn($q) => $q->where('psi.status', $f['increment_status']))
            ->when($f['department'] ?? null, fn($q) => $q->where('e.Dept_id', $f['department']))
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('psi.effective_date', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('psi.effective_date', '<=', $f['to_date']))
            ->orderBy('psi.effective_date')
            ->get([
                'psi.id', 'e.Emp_id', DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as employee_name"),
                'd.name as dept_name', 'p.position_title',
                'psi.previous_salary', 'psi.new_salary', 'psi.increment_amount', 'psi.pay_increase_type', 'psi.value',
                'psi.effective_date', 'psi.status',
            ])
            ->map(fn($r) => [
                'Employee ID'           => $r->Emp_id ?: 'N/A',
                'Employee Name'         => trim($r->employee_name) ?: 'N/A',
                'Department'            => $r->dept_name ?? 'N/A',
                'Position'              => $r->position_title ?? 'N/A',
                'Current Salary'        => $r->previous_salary !== null ? number_format((float) $r->previous_salary, 2) : 'N/A',
                'Proposed Salary'       => $r->new_salary !== null ? number_format((float) $r->new_salary, 2) : 'N/A',
                'Increment Amount'      => $r->increment_amount !== null ? number_format((float) $r->increment_amount, 2) : 'N/A',
                'Increment Percentage'  => ($pct = $this->pctFor($r->pay_increase_type, $r->value, $r->increment_amount, $r->previous_salary)) !== null ? $pct . '%' : 'N/A',
                'Effective Date'        => $r->effective_date ? Carbon::parse($r->effective_date)->format('d M Y') : 'N/A',
                'Pending Approver'      => $this->pendingApproverFor((int) $r->id),
                'Status'                => $r->status ?: 'N/A',
            ])->values()->all();

        return [
            'columns' => ['Employee ID', 'Employee Name', 'Department', 'Position', 'Current Salary', 'Proposed Salary', 'Increment Amount', 'Increment Percentage', 'Effective Date', 'Pending Approver', 'Status'],
            'rows'    => $rows,
        ];
    }

    public function salaryIncrementFinancialImpact(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();

        $rows = $this->baseQuery($rid, $scoped)
            ->when($f['department'] ?? null, fn($q) => $q->where('e.Dept_id', $f['department']))
            ->when($f['increment_status'] ?? null, fn($q) => $q->where('psi.status', $f['increment_status']))
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('psi.effective_date', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('psi.effective_date', '<=', $f['to_date']))
            ->get(['psi.employee_id', 'psi.previous_salary', 'psi.new_salary', 'psi.increment_amount', 'psi.pay_increase_type', 'psi.value']);

        $employeeCount = $rows->pluck('employee_id')->unique()->count();
        $totalCurrent = $rows->sum(fn($r) => (float) $r->previous_salary);
        $totalProposed = $rows->sum(fn($r) => (float) $r->new_salary);
        // "Total Monthly Payroll Increase" = sum of increment amounts across
        // the selected rows; "Total Annual Payroll Increase" = that x 12.
        // The source data has no explicit monthly/annual distinction — this
        // is a derived assumption (increments are salary-per-month deltas).
        $totalMonthlyIncrease = $rows->sum(fn($r) => (float) $r->increment_amount);
        $totalAnnualIncrease = $totalMonthlyIncrease * 12;
        $avgIncrementAmount = $rows->count() > 0 ? $totalMonthlyIncrease / $rows->count() : null;
        $pcts = $rows->map(fn($r) => $this->pctFor($r->pay_increase_type, $r->value, $r->increment_amount, $r->previous_salary))->filter(fn($v) => $v !== null);
        $avgIncrementPct = $pcts->count() > 0 ? round($pcts->avg(), 2) : null;

        $rowsOut = [[
            'Number of Employees Selected'      => $employeeCount,
            'Total Current Basic Salary'        => number_format($totalCurrent, 2),
            'Total Proposed Basic Salary'       => number_format($totalProposed, 2),
            'Total Monthly Payroll Increase'    => number_format($totalMonthlyIncrease, 2),
            'Total Annual Payroll Increase'     => number_format($totalAnnualIncrease, 2),
            'Average Increment Amount'          => $avgIncrementAmount !== null ? number_format($avgIncrementAmount, 2) : 'N/A',
            'Average Increment Percentage'      => $avgIncrementPct !== null ? $avgIncrementPct . '%' : 'N/A',
        ]];

        return [
            'columns' => ['Number of Employees Selected', 'Total Current Basic Salary', 'Total Proposed Basic Salary', 'Total Monthly Payroll Increase', 'Total Annual Payroll Increase', 'Average Increment Amount', 'Average Increment Percentage'],
            'rows'    => $rowsOut,
        ];
    }

    public function salaryIncrementAnalysis(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();

        $rows = $this->baseQuery($rid, $scoped)
            ->when($f['department'] ?? null, fn($q) => $q->where('e.Dept_id', $f['department']))
            ->when($f['increment_type'] ?? null, fn($q) => $q->where('psi.increment_type', $f['increment_type']))
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('psi.effective_date', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('psi.effective_date', '<=', $f['to_date']))
            ->orderByDesc('psi.effective_date')
            ->get([
                'e.Emp_id', DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as employee_name"),
                'd.name as dept_name', 'p.position_title',
                'psi.previous_salary', 'psi.new_salary', 'psi.increment_amount', 'psi.pay_increase_type', 'psi.value', 'psi.effective_date',
            ])
            ->map(fn($r) => [
                'Employee ID'           => $r->Emp_id ?: 'N/A',
                'Employee Name'         => trim($r->employee_name) ?: 'N/A',
                'Department'            => $r->dept_name ?? 'N/A',
                'Position'              => $r->position_title ?? 'N/A',
                'Current Salary'        => $r->previous_salary !== null ? number_format((float) $r->previous_salary, 2) : 'N/A',
                'New Salary'            => $r->new_salary !== null ? number_format((float) $r->new_salary, 2) : 'N/A',
                'Increment Amount'      => $r->increment_amount !== null ? number_format((float) $r->increment_amount, 2) : 'N/A',
                'Increment Percentage'  => ($pct = $this->pctFor($r->pay_increase_type, $r->value, $r->increment_amount, $r->previous_salary)) !== null ? $pct . '%' : 'N/A',
                'Effective Date'        => $r->effective_date ? Carbon::parse($r->effective_date)->format('d M Y') : 'N/A',
            ])->values()->all();

        return [
            'columns' => ['Employee ID', 'Employee Name', 'Department', 'Position', 'Current Salary', 'New Salary', 'Increment Amount', 'Increment Percentage', 'Effective Date'],
            'rows'    => $rows,
        ];
    }

    public function salaryIncrementExecutiveSummary(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();

        $base = fn() => $this->baseQuery($rid, $scoped)
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('psi.effective_date', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('psi.effective_date', '<=', $f['to_date']));

        $total = (clone $base())->count();
        $approved = (clone $base())->where('psi.status', 'Approved')->count();
        // "Pending" here folds in Hold + Change-Request — same in-flight
        // treatment as pendingApproverFor()/pendingSalaryIncrementApproval()
        // above: Hold is a pause on a stage, not a separate terminal state.
        $pending = (clone $base())->whereIn('psi.status', ['Pending', 'Hold', 'Change-Request'])->count();
        $rejected = (clone $base())->where('psi.status', 'Rejected')->count();

        $financials = (clone $base())->get(['psi.previous_salary', 'psi.increment_amount', 'psi.pay_increase_type', 'psi.value']);
        // Financial impact + average figures are computed across every
        // request in the date range (not restricted to Approved only) so
        // the summary reflects total payroll exposure from all proposals,
        // matching the Financial Impact report's formula.
        $totalMonthlyIncrease = $financials->sum(fn($r) => (float) $r->increment_amount);
        $totalAnnualIncrease = $totalMonthlyIncrease * 12;
        $avgIncrementAmount = $financials->count() > 0 ? $totalMonthlyIncrease / $financials->count() : null;
        $pcts = $financials->map(fn($r) => $this->pctFor($r->pay_increase_type, $r->value, $r->increment_amount, $r->previous_salary))->filter(fn($v) => $v !== null);
        $avgIncrementPct = $pcts->count() > 0 ? round($pcts->avg(), 2) : null;

        $rows = [[
            'Total Increment Requests'          => $total,
            'Approved Increments'               => $approved,
            'Pending Increments'                => $pending,
            'Rejected Increments'               => $rejected,
            'Total Monthly Payroll Increase'    => number_format($totalMonthlyIncrease, 2),
            'Total Annual Payroll Increase'      => number_format($totalAnnualIncrease, 2),
            'Average Increment Percentage'      => $avgIncrementPct !== null ? $avgIncrementPct . '%' : 'N/A',
            'Average Increment Amount'          => $avgIncrementAmount !== null ? number_format($avgIncrementAmount, 2) : 'N/A',
        ]];

        return [
            'columns' => ['Total Increment Requests', 'Approved Increments', 'Pending Increments', 'Rejected Increments', 'Total Monthly Payroll Increase', 'Total Annual Payroll Increase', 'Average Increment Percentage', 'Average Increment Amount'],
            'rows'    => $rows,
        ];
    }
}
