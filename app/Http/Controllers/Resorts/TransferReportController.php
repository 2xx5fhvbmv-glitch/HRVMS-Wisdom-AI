<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Resorts\Concerns\PredefinedReportActions;
use App\Helpers\Common;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Predefined People Management – Transfer reports (Option B), generic view.
 *
 * Source: employee_transfers (current_department_id/target_department_id,
 * current_section_id/target_section_id, current_position_id/target_position_id,
 * transfer_status = the "Transfer Type" field, effective_date, status) joined
 * to resort_departments/resort_positions/resort_sections for each side, and
 * (for the currently-pending approval stage) employee_transfers_approval.
 * approval_rank — mirrors People\Transfer\TransferController, the live
 * module these reports summarise.
 *
 * Salary mapping (verified against the live module, NOT the naive
 * budgeted_salary/proposed_salary pairing): `budgeted_salary` on
 * employee_transfers is the BUDGETED salary for the TARGET position (see
 * TransferController::getBudgetedSalary() / the "BUDGETED SALARY (TARGET
 * POSITION)" form field and detail-page label) — it is not the employee's
 * current pay. The employee's actual current salary at the time of transfer
 * is snapshotted into `pre_transfer_snapshot->basic_salary` once the transfer
 * is applied (TransferController::applyTransfer()-equivalent code around
 * line 1443); before that happens (Pending/On Hold/not yet effective) the
 * live `employees.basic_salary` is the best available figure. "Current
 * Salary" below therefore reads COALESCE(pre_transfer_snapshot->basic_salary,
 * employees.basic_salary). `proposed_salary` is used as-is for "Proposed
 * Salary" — that field is correctly named/labelled in the schema already.
 */
class TransferReportController extends Controller
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
            'transfer_register' => [
                'name' => 'Transfer Register',
                'description' => 'Displays all employee transfers processed during the selected period, including department, section, position, salary changes, transfer reason, and approval status. This serves as the master transfer report.',
                'filters' => ['duration', 'department', 'transfer_type', 'transfer_status', 'employee'],
                'handler' => 'transferRegister',
            ],
            'transfer_history' => [
                'name' => 'Employee Transfer History Report',
                'description' => 'Displays the complete transfer history of a selected employee, allowing HR to track the employee\'s movement throughout the organization.',
                'filters' => ['duration', 'employee'],
                'handler' => 'transferHistory',
            ],
            'pending_transfer_approval' => [
                'name' => 'Pending Transfer Approval Report',
                'description' => 'Displays transfer requests that are pending approval, on hold, or awaiting action from the approval workflow.',
                'filters' => ['duration', 'department', 'transfer_status'],
                'handler' => 'pendingTransferApproval',
            ],
            'transfer_movement_analysis' => [
                'name' => 'Transfer Movement Analysis Report',
                'description' => 'Summarizes employee movement across departments, sections, and positions, helping management understand internal workforce mobility and organizational restructuring.',
                'filters' => ['duration', 'department', 'transfer_type'],
                'handler' => 'transferMovementAnalysis',
            ],
            'transfer_executive_summary' => [
                'name' => 'Transfer Executive Summary',
                'description' => 'Provides management with an overview of transfer activities, including completed transfers, pending approvals, permanent versus temporary transfers, and organizational movement trends during the selected period.',
                'filters' => ['duration'],
                'handler' => 'transferExecutiveSummary',
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
        $employees = DB::table('employees as e')->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->where('e.resort_id', $rid)->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))
            ->orderBy('ra.first_name')->get(['e.id', DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as name")]);

        $filterDefs = [
            ['filter' => 'department', 'name' => 'department', 'label' => 'Department', 'type' => 'select', 'placeholder' => 'All departments', 'options' => $departments->map(fn($d) => ['value' => $d->id, 'label' => $d->name])->all()],
            ['filter' => 'employee', 'name' => 'employee', 'label' => 'Employee Name', 'type' => 'select', 'placeholder' => 'All employees', 'options' => $employees->map(fn($e) => ['value' => $e->id, 'label' => $e->name])->all()],
            ['filter' => 'transfer_type', 'name' => 'transfer_type', 'label' => 'Transfer Type', 'type' => 'select', 'placeholder' => 'All types', 'options' => collect(['Permanent', 'Temporary'])->map(fn($s) => ['value' => $s, 'label' => $s])->all()],
            ['filter' => 'transfer_status', 'name' => 'transfer_status', 'label' => 'Transfer / Approval Status', 'type' => 'select', 'placeholder' => 'All statuses', 'options' => collect(['Pending', 'On Hold', 'Approved', 'Rejected'])->map(fn($s) => ['value' => $s, 'label' => $s])->all()],
            ['filter' => 'duration', 'name' => 'from_date', 'label' => 'From Date', 'type' => 'date'],
            ['filter' => 'duration', 'name' => 'to_date', 'label' => 'To Date', 'type' => 'date'],
        ];

        return view('resorts.reports.module_report', [
            'page_title' => 'Transfer Reports', 'reports' => $reports, 'filterDefs' => $filterDefs,
            'runRoute' => 'resort.report.transfer.run', 'exportRoute' => 'resort.report.transfer.export', 'insightsRoute' => 'resort.report.transfer.insights',
        ]);
    }

    private function filtersFrom(Request $request): array
    {
        return collect(['department', 'employee', 'transfer_type', 'transfer_status', 'from_date', 'to_date'])
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
        return DB::table('employee_transfers as tr')
            ->join('employees as e', 'e.id', '=', 'tr.employee_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->leftJoin('resort_departments as cd', 'cd.id', '=', 'tr.current_department_id')
            ->leftJoin('resort_departments as nd', 'nd.id', '=', 'tr.target_department_id')
            ->leftJoin('resort_sections as cs', 'cs.id', '=', 'tr.current_section_id')
            ->leftJoin('resort_sections as ns', 'ns.id', '=', 'tr.target_section_id')
            ->leftJoin('resort_positions as cp', 'cp.id', '=', 'tr.current_position_id')
            ->leftJoin('resort_positions as np', 'np.id', '=', 'tr.target_position_id')
            ->leftJoin('employees as rm', 'rm.id', '=', 'tr.reporting_manager')
            ->leftJoin('resort_admins as rmra', 'rmra.id', '=', 'rm.Admin_Parent_id')
            ->where('tr.resort_id', $rid)
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped));
    }

    /** "Current Salary" — see class-level docblock for why this isn't `budgeted_salary`. */
    private function currentSalaryExpr()
    {
        return DB::raw("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(tr.pre_transfer_snapshot, '$.basic_salary')), e.basic_salary) as current_salary");
    }

    private function pendingApproverFor(int $transferId): string
    {
        $stage = DB::table('employee_transfers_approval')->where('transfer_id', $transferId)->where('status', 'Pending')->orderBy('id')->first();
        return $stage->approval_rank ?? 'N/A';
    }

    private function reportingManagerName($r): string
    {
        return trim($r->reporting_manager_name ?? '') ?: 'N/A';
    }

    /* --------------------------------------------------------------- reports */

    public function transferRegister(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();

        $rows = $this->baseQuery($rid, $scoped)
            ->when($f['department'] ?? null, fn($q) => $q->where(fn($qq) => $qq->where('tr.current_department_id', $f['department'])->orWhere('tr.target_department_id', $f['department'])))
            ->when($f['transfer_type'] ?? null, fn($q) => $q->where('tr.transfer_status', $f['transfer_type']))
            ->when($f['transfer_status'] ?? null, fn($q) => $q->where('tr.status', $f['transfer_status']))
            ->when($f['employee'] ?? null, fn($q) => $q->where('e.id', $f['employee']))
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('tr.effective_date', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('tr.effective_date', '<=', $f['to_date']))
            ->orderByDesc('tr.effective_date')
            ->get([
                'e.Emp_id', DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as employee_name"),
                'cd.name as prev_dept', 'nd.name as new_dept', 'cs.name as prev_section', 'ns.name as new_section',
                'cp.position_title as prev_position', 'np.position_title as new_position',
                'tr.transfer_status', 'tr.effective_date', 'tr.reason_for_transfer', 'tr.proposed_salary', 'tr.status',
                $this->currentSalaryExpr(),
                DB::raw("TRIM(CONCAT(COALESCE(rmra.first_name,''),' ',COALESCE(rmra.last_name,''))) as reporting_manager_name"),
            ])
            ->map(fn($r) => [
                'Employee ID'          => $r->Emp_id ?: 'N/A',
                'Employee Name'        => trim($r->employee_name) ?: 'N/A',
                'Current Department'   => $r->prev_dept ?? 'N/A',
                'Target Department'    => $r->new_dept ?? 'N/A',
                'Current Section'      => $r->prev_section ?? 'N/A',
                'Target Section'       => $r->new_section ?? 'N/A',
                'Current Position'     => $r->prev_position ?? 'N/A',
                'Target Position'      => $r->new_position ?? 'N/A',
                'Transfer Type'        => $r->transfer_status ?: 'N/A',
                'Effective Date'       => $r->effective_date ? Carbon::parse($r->effective_date)->format('d M Y') : 'N/A',
                'Transfer Reason'      => $r->reason_for_transfer ?: 'N/A',
                'Current Salary'       => $r->current_salary !== null ? number_format((float) $r->current_salary, 2) : 'N/A',
                'Proposed Salary'      => $r->proposed_salary !== null ? number_format((float) $r->proposed_salary, 2) : 'N/A',
                'Reporting Manager'    => $this->reportingManagerName($r),
                'Status'               => $r->status ?: 'N/A',
            ])->values()->all();

        return [
            'columns' => ['Employee ID', 'Employee Name', 'Current Department', 'Target Department', 'Current Section', 'Target Section', 'Current Position', 'Target Position', 'Transfer Type', 'Effective Date', 'Transfer Reason', 'Current Salary', 'Proposed Salary', 'Reporting Manager', 'Status'],
            'rows'    => $rows,
        ];
    }

    public function transferHistory(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();

        $rows = $this->baseQuery($rid, $scoped)
            ->when($f['employee'] ?? null, fn($q) => $q->where('e.id', $f['employee']))
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('tr.effective_date', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('tr.effective_date', '<=', $f['to_date']))
            ->orderByDesc('tr.effective_date')
            ->get([
                'e.Emp_id', DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as employee_name"),
                'cd.name as prev_dept', 'nd.name as new_dept', 'cs.name as prev_section', 'ns.name as new_section',
                'cp.position_title as prev_position', 'np.position_title as new_position',
                'tr.transfer_status', 'tr.effective_date', 'tr.reason_for_transfer', 'tr.proposed_salary', 'tr.status',
                $this->currentSalaryExpr(),
                DB::raw("TRIM(CONCAT(COALESCE(rmra.first_name,''),' ',COALESCE(rmra.last_name,''))) as reporting_manager_name"),
            ])
            ->map(fn($r) => [
                'Employee ID'          => $r->Emp_id ?: 'N/A',
                'Employee Name'        => trim($r->employee_name) ?: 'N/A',
                'Effective Date'       => $r->effective_date ? Carbon::parse($r->effective_date)->format('d M Y') : 'N/A',
                'Current Department'   => $r->prev_dept ?? 'N/A',
                'Target Department'    => $r->new_dept ?? 'N/A',
                'Current Section'      => $r->prev_section ?? 'N/A',
                'Target Section'       => $r->new_section ?? 'N/A',
                'Current Position'     => $r->prev_position ?? 'N/A',
                'Target Position'      => $r->new_position ?? 'N/A',
                'Transfer Type'        => $r->transfer_status ?: 'N/A',
                'Current Salary'       => $r->current_salary !== null ? number_format((float) $r->current_salary, 2) : 'N/A',
                'Proposed Salary'      => $r->proposed_salary !== null ? number_format((float) $r->proposed_salary, 2) : 'N/A',
                'Reporting Manager'    => $this->reportingManagerName($r),
                'Transfer Reason'      => $r->reason_for_transfer ?: 'N/A',
                'Status'               => $r->status ?: 'N/A',
            ])->values()->all();

        return [
            'columns' => ['Employee ID', 'Employee Name', 'Effective Date', 'Current Department', 'Target Department', 'Current Section', 'Target Section', 'Current Position', 'Target Position', 'Transfer Type', 'Current Salary', 'Proposed Salary', 'Reporting Manager', 'Transfer Reason', 'Status'],
            'rows'    => $rows,
        ];
    }

    public function pendingTransferApproval(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();

        $rows = $this->baseQuery($rid, $scoped)
            ->whereIn('tr.status', ['Pending', 'On Hold'])
            ->when($f['transfer_status'] ?? null, fn($q) => $q->where('tr.status', $f['transfer_status']))
            ->when($f['department'] ?? null, fn($q) => $q->where(fn($qq) => $qq->where('tr.current_department_id', $f['department'])->orWhere('tr.target_department_id', $f['department'])))
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('tr.effective_date', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('tr.effective_date', '<=', $f['to_date']))
            ->orderBy('tr.effective_date')
            ->get([
                'tr.id', 'e.Emp_id', DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as employee_name"),
                'cd.name as prev_dept', 'nd.name as new_dept', 'cp.position_title as prev_position', 'np.position_title as new_position',
                'tr.effective_date', 'tr.transfer_status', 'tr.status',
            ])
            ->map(fn($r) => [
                'Employee ID'        => $r->Emp_id ?: 'N/A',
                'Employee Name'      => trim($r->employee_name) ?: 'N/A',
                'Current Department' => $r->prev_dept ?? 'N/A',
                'Target Department'  => $r->new_dept ?? 'N/A',
                'Current Position'   => $r->prev_position ?? 'N/A',
                'Target Position'    => $r->new_position ?? 'N/A',
                'Effective Date'     => $r->effective_date ? Carbon::parse($r->effective_date)->format('d M Y') : 'N/A',
                'Transfer Type'      => $r->transfer_status ?: 'N/A',
                'Pending Approver'   => $r->status === 'Pending' ? $this->pendingApproverFor($r->id) : 'N/A',
                'Approval Status'    => $r->status ?: 'N/A',
            ])->values()->all();

        return [
            'columns' => ['Employee ID', 'Employee Name', 'Current Department', 'Target Department', 'Current Position', 'Target Position', 'Effective Date', 'Transfer Type', 'Pending Approver', 'Approval Status'],
            'rows'    => $rows,
        ];
    }

    public function transferMovementAnalysis(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();

        $rows = $this->baseQuery($rid, $scoped)
            ->where('tr.status', 'Approved')
            ->when($f['department'] ?? null, fn($q) => $q->where(fn($qq) => $qq->where('tr.current_department_id', $f['department'])->orWhere('tr.target_department_id', $f['department'])))
            ->when($f['transfer_type'] ?? null, fn($q) => $q->where('tr.transfer_status', $f['transfer_type']))
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('tr.effective_date', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('tr.effective_date', '<=', $f['to_date']))
            ->orderByDesc('tr.effective_date')
            ->get([
                'e.Emp_id', DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as employee_name"),
                'cd.name as prev_dept', 'nd.name as new_dept', 'cs.name as prev_section', 'ns.name as new_section',
                'cp.position_title as prev_position', 'np.position_title as new_position',
                'tr.effective_date', 'tr.reason_for_transfer',
            ])
            ->map(fn($r) => [
                'Employee ID'        => $r->Emp_id ?: 'N/A',
                'Employee Name'      => trim($r->employee_name) ?: 'N/A',
                'Current Department' => $r->prev_dept ?? 'N/A',
                'Target Department'  => $r->new_dept ?? 'N/A',
                'Current Section'    => $r->prev_section ?? 'N/A',
                'Target Section'     => $r->new_section ?? 'N/A',
                'Current Position'   => $r->prev_position ?? 'N/A',
                'Target Position'    => $r->new_position ?? 'N/A',
                'Effective Date'     => $r->effective_date ? Carbon::parse($r->effective_date)->format('d M Y') : 'N/A',
                'Transfer Reason'    => $r->reason_for_transfer ?: 'N/A',
            ])->values()->all();

        return [
            'columns' => ['Employee ID', 'Employee Name', 'Current Department', 'Target Department', 'Current Section', 'Target Section', 'Current Position', 'Target Position', 'Effective Date', 'Transfer Reason'],
            'rows'    => $rows,
        ];
    }

    public function transferExecutiveSummary(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();

        $base = fn() => $this->baseQuery($rid, $scoped)
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('tr.effective_date', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('tr.effective_date', '<=', $f['to_date']));

        $total = (clone $base())->count();
        $permanent = (clone $base())->where('tr.transfer_status', 'Permanent')->count();
        $temporary = (clone $base())->where('tr.transfer_status', 'Temporary')->count();
        $pending = (clone $base())->whereIn('tr.status', ['Pending', 'On Hold'])->count();
        $approved = (clone $base())->where('tr.status', 'Approved')->count();
        $rejected = (clone $base())->where('tr.status', 'Rejected')->count();
        $topDept = (clone $base())->where('tr.status', 'Approved')
            ->select('nd.name', DB::raw('COUNT(*) as cnt'))->groupBy('nd.name')->orderByDesc('cnt')->first();

        $rows = [[
            'Total Transfers'                    => $total,
            'Permanent Transfers'                => $permanent,
            'Temporary Transfers'                => $temporary,
            'Pending Transfers'                  => $pending,
            'Approved Transfers'                 => $approved,
            'Rejected Transfers'                 => $rejected,
            'Departments with Highest Transfers' => $topDept->name ?? 'N/A',
        ]];

        return [
            'columns' => ['Total Transfers', 'Permanent Transfers', 'Temporary Transfers', 'Pending Transfers', 'Approved Transfers', 'Rejected Transfers', 'Departments with Highest Transfers'],
            'rows'    => $rows,
        ];
    }
}
