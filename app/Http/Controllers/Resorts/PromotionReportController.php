<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Resorts\Concerns\PredefinedReportActions;
use App\Helpers\Common;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Predefined People Management – Promotion reports (Option B), generic view.
 *
 * Source: employee_promotions (current_position_id/new_position_id, current_
 * salary/new_salary, salary_increment_percent/amount, effective_date, status)
 * joined to resort_positions (for each side's department) and, for the
 * currently-pending approval stage, employee_promotions_approval.approval_rank
 * (see People\Promotion\PromotionController — the live module these mirror).
 */
class PromotionReportController extends Controller
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
            'promotion_register' => [
                'name' => 'Promotion Register',
                'description' => 'All employee promotions processed during the selected period, including changes in department, position, salary, and approval status. Master promotion report.',
                'filters' => ['duration', 'department', 'position', 'promotion_status'],
                'handler' => 'promotionRegister',
            ],
            'promotion_history' => [
                'name' => 'Promotion History Report',
                'description' => 'Complete promotion history of a selected employee, allowing HR to track career progression within the organization.',
                'filters' => ['duration', 'employee'],
                'handler' => 'promotionHistory',
            ],
            'pending_promotion_approval' => [
                'name' => 'Pending Promotion Approval Report',
                'description' => 'Promotion requests awaiting approval or currently on hold. Helps HR monitor pending approval workflows.',
                'filters' => ['duration', 'promotion_status', 'department'],
                'handler' => 'pendingPromotionApproval',
            ],
            'promotion_movement_analysis' => [
                'name' => 'Promotion Movement Analysis Report',
                'description' => 'Promotion movements across departments and positions, helping management understand internal career progression and organizational mobility.',
                'filters' => ['duration', 'department'],
                'handler' => 'promotionMovementAnalysis',
            ],
            'promotion_salary_impact' => [
                'name' => 'Promotion Salary Impact Report',
                'description' => 'Financial impact of employee promotions by comparing previous and revised salaries, including salary increases in both amount and percentage.',
                'filters' => ['duration', 'department'],
                'handler' => 'promotionSalaryImpact',
            ],
            'promotion_executive_summary' => [
                'name' => 'Promotion Executive Summary',
                'description' => 'Overview of promotion activities, including completed promotions, pending approvals, departmental movement, and salary impact during the selected period.',
                'filters' => ['duration'],
                'handler' => 'promotionExecutiveSummary',
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

        $filterDefs = [
            ['filter' => 'department', 'name' => 'department', 'label' => 'Department', 'type' => 'select', 'placeholder' => 'All departments', 'options' => $departments->map(fn($d) => ['value' => $d->id, 'label' => $d->name])->all()],
            ['filter' => 'position', 'name' => 'position', 'label' => 'Position', 'type' => 'select', 'placeholder' => 'All positions', 'options' => $positions->map(fn($p) => ['value' => $p->id, 'label' => $p->position_title])->all()],
            ['filter' => 'employee', 'name' => 'employee', 'label' => 'Employee Name', 'type' => 'select', 'placeholder' => 'All employees', 'options' => $employees->map(fn($e) => ['value' => $e->id, 'label' => $e->name])->all()],
            ['filter' => 'promotion_status', 'name' => 'promotion_status', 'label' => 'Promotion / Approval Status', 'type' => 'select', 'placeholder' => 'All statuses', 'options' => collect(['Pending', 'On Hold', 'Approved', 'Rejected'])->map(fn($s) => ['value' => $s, 'label' => $s])->all()],
            ['filter' => 'duration', 'name' => 'from_date', 'label' => 'From Date', 'type' => 'date'],
            ['filter' => 'duration', 'name' => 'to_date', 'label' => 'To Date', 'type' => 'date'],
        ];

        return view('resorts.reports.module_report', [
            'page_title' => 'Promotion Reports', 'reports' => $reports, 'filterDefs' => $filterDefs,
            'runRoute' => 'resort.report.promotion.run', 'exportRoute' => 'resort.report.promotion.export', 'insightsRoute' => 'resort.report.promotion.insights',
        ]);
    }

    private function filtersFrom(Request $request): array
    {
        return collect(['department', 'position', 'employee', 'promotion_status', 'from_date', 'to_date'])
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
        return DB::table('employee_promotions as pr')
            ->join('employees as e', 'e.id', '=', 'pr.employee_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->leftJoin('resort_positions as cp', 'cp.id', '=', 'pr.current_position_id')
            ->leftJoin('resort_departments as cd', 'cd.id', '=', 'cp.dept_id')
            ->leftJoin('resort_positions as np', 'np.id', '=', 'pr.new_position_id')
            ->leftJoin('resort_departments as nd', 'nd.id', '=', 'np.dept_id')
            ->where('pr.resort_id', $rid)
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped));
    }

    private function pctChange($old, $new): ?float
    {
        $old = (float) $old;
        $new = (float) $new;
        if ($old <= 0) return null;
        return round((($new - $old) / $old) * 100, 2);
    }

    private function pendingApproverFor(int $promotionId): string
    {
        $stage = DB::table('employee_promotions_approval')->where('promotion_id', $promotionId)->where('status', 'Pending')->orderBy('id')->first();
        return $stage->approval_rank ?? 'N/A';
    }

    /* --------------------------------------------------------------- reports */

    public function promotionRegister(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();

        $rows = $this->baseQuery($rid, $scoped)
            ->when($f['department'] ?? null, fn($q) => $q->where(fn($qq) => $qq->where('cp.dept_id', $f['department'])->orWhere('np.dept_id', $f['department'])))
            ->when($f['position'] ?? null, fn($q) => $q->where(fn($qq) => $qq->where('pr.current_position_id', $f['position'])->orWhere('pr.new_position_id', $f['position'])))
            ->when($f['promotion_status'] ?? null, fn($q) => $q->where('pr.status', $f['promotion_status']))
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('pr.effective_date', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('pr.effective_date', '<=', $f['to_date']))
            ->orderByDesc('pr.effective_date')
            ->get([
                'e.Emp_id', DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as employee_name"),
                'cd.name as prev_dept', 'nd.name as new_dept', 'cp.position_title as prev_position', 'np.position_title as new_position',
                'pr.current_salary', 'pr.new_salary', 'pr.salary_increment_amount', 'pr.salary_increment_percent', 'pr.effective_date', 'pr.status',
            ])
            ->map(fn($r) => [
                'Employee ID'             => $r->Emp_id ?: 'N/A',
                'Employee Name'           => trim($r->employee_name) ?: 'N/A',
                'Previous Department'     => $r->prev_dept ?? 'N/A',
                'New Department'          => $r->new_dept ?? 'N/A',
                'Previous Position'       => $r->prev_position ?? 'N/A',
                'New Position'            => $r->new_position ?? 'N/A',
                'Previous Salary'         => $r->current_salary !== null ? number_format((float) $r->current_salary, 2) : 'N/A',
                'New Salary'              => $r->new_salary !== null ? number_format((float) $r->new_salary, 2) : 'N/A',
                'Salary Increase (Amount)' => $r->salary_increment_amount !== null ? number_format((float) $r->salary_increment_amount, 2) : 'N/A',
                'Salary Increase (%)'     => $r->salary_increment_percent !== null ? $r->salary_increment_percent . '%' : 'N/A',
                'Effective Date'          => $r->effective_date ? Carbon::parse($r->effective_date)->format('d M Y') : 'N/A',
                'Promotion Status'        => $r->status ?: 'N/A',
            ])->values()->all();

        return [
            'columns' => ['Employee ID', 'Employee Name', 'Previous Department', 'New Department', 'Previous Position', 'New Position', 'Previous Salary', 'New Salary', 'Salary Increase (Amount)', 'Salary Increase (%)', 'Effective Date', 'Promotion Status'],
            'rows'    => $rows,
        ];
    }

    public function promotionHistory(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();

        $rows = $this->baseQuery($rid, $scoped)
            ->when($f['employee'] ?? null, fn($q) => $q->where('e.id', $f['employee']))
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('pr.effective_date', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('pr.effective_date', '<=', $f['to_date']))
            ->orderByDesc('pr.effective_date')
            ->get([
                'e.Emp_id', DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as employee_name"),
                'cd.name as prev_dept', 'nd.name as new_dept', 'cp.position_title as prev_position', 'np.position_title as new_position',
                'pr.current_salary', 'pr.new_salary', 'pr.salary_increment_percent', 'pr.effective_date', 'pr.status',
            ])
            ->map(fn($r) => [
                'Employee ID'          => $r->Emp_id ?: 'N/A',
                'Employee Name'        => trim($r->employee_name) ?: 'N/A',
                'Promotion Date'       => $r->effective_date ? Carbon::parse($r->effective_date)->format('d M Y') : 'N/A',
                'Previous Department'  => $r->prev_dept ?? 'N/A',
                'New Department'       => $r->new_dept ?? 'N/A',
                'Previous Position'    => $r->prev_position ?? 'N/A',
                'New Position'         => $r->new_position ?? 'N/A',
                'Previous Salary'      => $r->current_salary !== null ? number_format((float) $r->current_salary, 2) : 'N/A',
                'New Salary'           => $r->new_salary !== null ? number_format((float) $r->new_salary, 2) : 'N/A',
                'Salary Increase (%)'  => $r->salary_increment_percent !== null ? $r->salary_increment_percent . '%' : 'N/A',
                'Effective Date'       => $r->effective_date ? Carbon::parse($r->effective_date)->format('d M Y') : 'N/A',
            ])->values()->all();

        return [
            'columns' => ['Employee ID', 'Employee Name', 'Promotion Date', 'Previous Department', 'New Department', 'Previous Position', 'New Position', 'Previous Salary', 'New Salary', 'Salary Increase (%)', 'Effective Date'],
            'rows'    => $rows,
        ];
    }

    public function pendingPromotionApproval(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();

        $rows = $this->baseQuery($rid, $scoped)
            ->whereIn('pr.status', ['Pending', 'On Hold'])
            ->when($f['promotion_status'] ?? null, fn($q) => $q->where('pr.status', $f['promotion_status']))
            ->when($f['department'] ?? null, fn($q) => $q->where(fn($qq) => $qq->where('cp.dept_id', $f['department'])->orWhere('np.dept_id', $f['department'])))
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('pr.effective_date', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('pr.effective_date', '<=', $f['to_date']))
            ->orderBy('pr.effective_date')
            ->get([
                'pr.id', 'e.Emp_id', DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as employee_name"),
                'cd.name as prev_dept', 'nd.name as new_dept', 'cp.position_title as prev_position', 'np.position_title as new_position',
                'pr.new_salary', 'pr.effective_date', 'pr.status',
            ])
            ->map(fn($r) => [
                'Employee ID'             => $r->Emp_id ?: 'N/A',
                'Employee Name'           => trim($r->employee_name) ?: 'N/A',
                'Current Department'      => $r->prev_dept ?? 'N/A',
                'Proposed Department'     => $r->new_dept ?? 'N/A',
                'Current Position'        => $r->prev_position ?? 'N/A',
                'Proposed Position'       => $r->new_position ?? 'N/A',
                'Proposed Salary'         => $r->new_salary !== null ? number_format((float) $r->new_salary, 2) : 'N/A',
                'Effective Date'          => $r->effective_date ? Carbon::parse($r->effective_date)->format('d M Y') : 'N/A',
                'Current Approval Status' => $r->status ?: 'N/A',
                'Pending Approver'        => $r->status === 'Pending' ? $this->pendingApproverFor($r->id) : 'N/A',
            ])->values()->all();

        return [
            'columns' => ['Employee ID', 'Employee Name', 'Current Department', 'Proposed Department', 'Current Position', 'Proposed Position', 'Proposed Salary', 'Effective Date', 'Current Approval Status', 'Pending Approver'],
            'rows'    => $rows,
        ];
    }

    public function promotionMovementAnalysis(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();

        $rows = $this->baseQuery($rid, $scoped)
            ->where('pr.status', 'Approved')
            ->when($f['department'] ?? null, fn($q) => $q->where(fn($qq) => $qq->where('cp.dept_id', $f['department'])->orWhere('np.dept_id', $f['department'])))
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('pr.effective_date', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('pr.effective_date', '<=', $f['to_date']))
            ->orderByDesc('pr.effective_date')
            ->get([
                'e.Emp_id', DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as employee_name"),
                'cd.name as prev_dept', 'nd.name as new_dept', 'cp.position_title as prev_position', 'np.position_title as new_position', 'pr.effective_date',
            ])
            ->map(fn($r) => [
                'Employee ID'         => $r->Emp_id ?: 'N/A',
                'Employee Name'       => trim($r->employee_name) ?: 'N/A',
                'Previous Department' => $r->prev_dept ?? 'N/A',
                'New Department'      => $r->new_dept ?? 'N/A',
                'Previous Position'   => $r->prev_position ?? 'N/A',
                'New Position'        => $r->new_position ?? 'N/A',
                'Effective Date'      => $r->effective_date ? Carbon::parse($r->effective_date)->format('d M Y') : 'N/A',
            ])->values()->all();

        return [
            'columns' => ['Employee ID', 'Employee Name', 'Previous Department', 'New Department', 'Previous Position', 'New Position', 'Effective Date'],
            'rows'    => $rows,
        ];
    }

    public function promotionSalaryImpact(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();

        $rows = $this->baseQuery($rid, $scoped)
            ->where('pr.status', 'Approved')
            ->when($f['department'] ?? null, fn($q) => $q->where(fn($qq) => $qq->where('cp.dept_id', $f['department'])->orWhere('np.dept_id', $f['department'])))
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('pr.effective_date', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('pr.effective_date', '<=', $f['to_date']))
            ->orderByDesc('pr.effective_date')
            ->get([
                'e.Emp_id', DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as employee_name"),
                'pr.current_salary', 'pr.new_salary', 'pr.salary_increment_amount', 'pr.salary_increment_percent', 'pr.effective_date',
            ])
            ->map(fn($r) => [
                'Employee ID'              => $r->Emp_id ?: 'N/A',
                'Employee Name'            => trim($r->employee_name) ?: 'N/A',
                'Previous Salary'          => $r->current_salary !== null ? number_format((float) $r->current_salary, 2) : 'N/A',
                'New Salary'               => $r->new_salary !== null ? number_format((float) $r->new_salary, 2) : 'N/A',
                'Salary Increase (Amount)' => $r->salary_increment_amount !== null ? number_format((float) $r->salary_increment_amount, 2) : 'N/A',
                'Salary Increase (%)'      => $r->salary_increment_percent !== null ? $r->salary_increment_percent . '%' : 'N/A',
                'Effective Date'           => $r->effective_date ? Carbon::parse($r->effective_date)->format('d M Y') : 'N/A',
            ])->values()->all();

        return [
            'columns' => ['Employee ID', 'Employee Name', 'Previous Salary', 'New Salary', 'Salary Increase (Amount)', 'Salary Increase (%)', 'Effective Date'],
            'rows'    => $rows,
        ];
    }

    public function promotionExecutiveSummary(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();

        $base = fn() => $this->baseQuery($rid, $scoped)
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('pr.effective_date', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('pr.effective_date', '<=', $f['to_date']));

        $total = (clone $base())->count();
        $pending = (clone $base())->whereIn('pr.status', ['Pending', 'On Hold'])->count();
        $approved = (clone $base())->where('pr.status', 'Approved')->count();
        $rejected = (clone $base())->where('pr.status', 'Rejected')->count();
        $avgIncreasePct = (clone $base())->where('pr.status', 'Approved')->avg('pr.salary_increment_percent');
        $totalSalaryImpact = (clone $base())->where('pr.status', 'Approved')->sum('pr.salary_increment_amount');
        $topDept = (clone $base())->where('pr.status', 'Approved')
            ->select('nd.name', DB::raw('COUNT(*) as cnt'))->groupBy('nd.name')->orderByDesc('cnt')->first();

        $rows = [[
            'Total Promotions'                  => $total,
            'Pending Promotions'                => $pending,
            'Approved Promotions'               => $approved,
            'Rejected Promotions'               => $rejected,
            'Average Salary Increase (%)'       => $avgIncreasePct !== null ? round($avgIncreasePct, 2) . '%' : 'N/A',
            'Total Salary Impact'               => $totalSalaryImpact !== null ? number_format((float) $totalSalaryImpact, 2) : 'N/A',
            'Department with Highest Promotions' => $topDept->name ?? 'N/A',
        ]];

        return [
            'columns' => ['Total Promotions', 'Pending Promotions', 'Approved Promotions', 'Rejected Promotions', 'Average Salary Increase (%)', 'Total Salary Impact', 'Department with Highest Promotions'],
            'rows'    => $rows,
        ];
    }
}
