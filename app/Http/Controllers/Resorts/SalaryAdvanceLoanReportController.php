<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Resorts\Concerns\PredefinedReportActions;
use App\Helpers\Common;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Predefined People Management – Salary Advance & Loan reports (Option B),
 * generic view.
 *
 * Source: payroll_advance (employee_id, request_type ['Salary Advance' /
 * 'Loan Request'], request_amount, request_date, pourpose, status ['Pending'
 * / 'In-Progress' / 'Approved' / 'Rejected'], recovery_status ['Pending' /
 * 'Scheduled' / 'In Progress' / 'Completed' / 'Rejected'], hr_status /
 * finance_status / gm_status for the 3-stage approval workflow) joined to
 * employees / resort_admins / resort_positions / resort_departments, plus
 * payroll_recovery_schedule (payroll_advance_id, repayment_date, amount,
 * remaining_balance, status ['Pending' / 'Paid'], recovery_date,
 * recovered_via) for repayment/deduction data — see
 * People\Employee\AdvanceSalaryController and
 * People\Employee\AdvanceSalaryRepaymentTrackerController, the live modules
 * these mirror.
 *
 * payroll_advance has no separate "approved amount" column — request_amount
 * is the single stored figure, so Approved Amount is shown as that same
 * value once status = Approved and N/A otherwise. There is likewise no
 * per-request "On Hold" status value (unlike the Promotion module); the
 * pending/approval-workflow reports below use Pending / In-Progress /
 * Rejected as the closest real equivalent.
 */
class SalaryAdvanceLoanReportController extends Controller
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
            'salary_advance_loan_register' => [
                'name' => 'Salary Advance & Loan Register',
                'description' => 'Displays all salary advance and loan requests submitted during the selected period, including request details, approved amounts, repayment status, and current approval status. This serves as the master report for all employee loan and salary advance transactions.',
                'filters' => ['duration', 'department', 'position', 'request_type', 'status', 'employee'],
                'handler' => 'salaryAdvanceLoanRegister',
            ],
            'pending_salary_advance_loan_approval' => [
                'name' => 'Pending Salary Advance & Loan Approval Report',
                'description' => 'Displays salary advance and loan requests that are awaiting approval, on hold, or rejected, allowing HR and Finance to monitor approval workflows.',
                'filters' => ['duration', 'department', 'request_type', 'status'],
                'handler' => 'pendingSalaryAdvanceLoanApproval',
            ],
            'loan_salary_advance_repayment' => [
                'name' => 'Loan & Salary Advance Repayment Report',
                'description' => 'Displays repayment schedules and repayment progress for approved salary advances and employee loans, helping Finance monitor outstanding balances and future deductions.',
                'filters' => ['duration', 'department', 'repayment_status'],
                'handler' => 'loanSalaryAdvanceRepayment',
            ],
            'repayment_schedule' => [
                'name' => 'Repayment Schedule Report',
                'description' => 'Displays the complete repayment schedule for approved employee loans and salary advances, including installment amounts and planned deduction dates.',
                'filters' => ['duration', 'employee'],
                'handler' => 'repaymentSchedule',
            ],
            'payroll_deduction_history' => [
                'name' => 'Payroll Deduction History Report',
                'description' => 'Displays the history of payroll deductions made against employee salary advances and loans, enabling Finance to verify repayment records and payroll deductions.',
                'filters' => ['duration', 'department', 'employee'],
                'handler' => 'payrollDeductionHistory',
            ],
            'salary_advance_loan_executive_summary' => [
                'name' => 'Salary Advance & Loan Executive Summary',
                'description' => 'Provides management with an overview of employee loan and salary advance activities, including approved requests, pending approvals, outstanding balances, and repayment performance during the selected period.',
                'filters' => ['duration'],
                'handler' => 'salaryAdvanceLoanExecutiveSummary',
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
            ['filter' => 'request_type', 'name' => 'request_type', 'label' => 'Request Type', 'type' => 'select', 'placeholder' => 'All types', 'options' => collect(['Salary Advance', 'Loan Request'])->map(fn($s) => ['value' => $s, 'label' => $s])->all()],
            ['filter' => 'status', 'name' => 'status', 'label' => 'Status', 'type' => 'select', 'placeholder' => 'All statuses', 'options' => collect(['Pending', 'In-Progress', 'Approved', 'Rejected'])->map(fn($s) => ['value' => $s, 'label' => $s])->all()],
            ['filter' => 'repayment_status', 'name' => 'repayment_status', 'label' => 'Repayment Status', 'type' => 'select', 'placeholder' => 'All statuses', 'options' => collect(['Pending', 'Scheduled', 'In Progress', 'Completed', 'Rejected'])->map(fn($s) => ['value' => $s, 'label' => $s])->all()],
            ['filter' => 'duration', 'name' => 'from_date', 'label' => 'From Date', 'type' => 'date'],
            ['filter' => 'duration', 'name' => 'to_date', 'label' => 'To Date', 'type' => 'date'],
        ];

        return view('resorts.reports.module_report', [
            'page_title' => 'Salary Advance & Loan Reports', 'reports' => $reports, 'filterDefs' => $filterDefs,
            'runRoute' => 'resort.report.salary_advance_loan.run', 'exportRoute' => 'resort.report.salary_advance_loan.export', 'insightsRoute' => 'resort.report.salary_advance_loan.insights',
        ]);
    }

    private function filtersFrom(Request $request): array
    {
        return collect(['department', 'position', 'employee', 'request_type', 'status', 'repayment_status', 'from_date', 'to_date'])
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
        return DB::table('payroll_advance as pa')
            ->join('employees as e', 'e.id', '=', 'pa.employee_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->leftJoin('resort_positions as p', 'p.id', '=', 'e.Position_id')
            ->leftJoin('resort_departments as d', 'd.id', '=', 'e.Dept_id')
            ->where('pa.resort_id', $rid)
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped));
    }

    /** Money formatter — advance/loan figures are stored in USD (base currency). */
    private function n($v): string
    {
        if ($v === null || $v === '') return 'N/A';
        return '$' . number_format((float) $v, 2);
    }

    private function pendingApproverFor($row): string
    {
        if (!in_array($row->status, ['Pending', 'In-Progress'], true)) return 'N/A';
        if ($row->hr_status === 'Pending') return 'HR';
        if ($row->finance_status === 'Pending') return 'Finance';
        if ($row->gm_status === 'Pending') return 'GM';
        return 'N/A';
    }

    private function recoveredViaLabel(?string $v): string
    {
        return match ($v) {
            'final_settlement' => 'Final Settlement',
            'payroll_run' => 'Payroll Run',
            'manual' => 'Manual',
            default => 'N/A',
        };
    }

    /* --------------------------------------------------------------- reports */

    public function salaryAdvanceLoanRegister(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();

        $rows = $this->baseQuery($rid, $scoped)
            ->when($f['department'] ?? null, fn($q) => $q->where('e.Dept_id', $f['department']))
            ->when($f['position'] ?? null, fn($q) => $q->where('e.Position_id', $f['position']))
            ->when($f['request_type'] ?? null, fn($q) => $q->where('pa.request_type', $f['request_type']))
            ->when($f['status'] ?? null, fn($q) => $q->where('pa.status', $f['status']))
            ->when($f['employee'] ?? null, fn($q) => $q->where('e.id', $f['employee']))
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('pa.request_date', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('pa.request_date', '<=', $f['to_date']))
            ->orderByDesc('pa.request_date')
            ->get([
                'e.Emp_id', DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as employee_name"),
                'd.name as dept_name', 'p.position_title', 'pa.request_type', 'pa.request_amount', 'pa.request_date', 'pa.pourpose', 'pa.status',
            ])
            ->map(fn($r) => [
                'Employee ID'       => $r->Emp_id ?: 'N/A',
                'Employee Name'     => trim($r->employee_name) ?: 'N/A',
                'Department'        => $r->dept_name ?? 'N/A',
                'Position'          => $r->position_title ?? 'N/A',
                'Request Type'      => $r->request_type ?: 'N/A',
                'Requested Amount'  => $this->n($r->request_amount),
                'Approved Amount'   => $r->status === 'Approved' ? $this->n($r->request_amount) : 'N/A',
                'Request Date'      => $r->request_date ? Carbon::parse($r->request_date)->format('d M Y') : 'N/A',
                'Purpose'           => $r->pourpose ?: 'N/A',
                'Current Status'    => $r->status ?: 'N/A',
            ])->values()->all();

        return [
            'columns' => ['Employee ID', 'Employee Name', 'Department', 'Position', 'Request Type', 'Requested Amount', 'Approved Amount', 'Request Date', 'Purpose', 'Current Status'],
            'rows'    => $rows,
        ];
    }

    public function pendingSalaryAdvanceLoanApproval(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();

        $rows = $this->baseQuery($rid, $scoped)
            ->whereIn('pa.status', ['Pending', 'In-Progress', 'Rejected'])
            ->when($f['department'] ?? null, fn($q) => $q->where('e.Dept_id', $f['department']))
            ->when($f['request_type'] ?? null, fn($q) => $q->where('pa.request_type', $f['request_type']))
            ->when($f['status'] ?? null, fn($q) => $q->where('pa.status', $f['status']))
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('pa.request_date', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('pa.request_date', '<=', $f['to_date']))
            ->orderBy('pa.request_date')
            ->get([
                'e.Emp_id', DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as employee_name"),
                'd.name as dept_name', 'p.position_title', 'pa.request_type', 'pa.request_amount', 'pa.request_date', 'pa.pourpose',
                'pa.status', 'pa.hr_status', 'pa.finance_status', 'pa.gm_status',
            ])
            ->map(fn($r) => [
                'Employee ID'       => $r->Emp_id ?: 'N/A',
                'Employee Name'     => trim($r->employee_name) ?: 'N/A',
                'Department'        => $r->dept_name ?? 'N/A',
                'Position'          => $r->position_title ?? 'N/A',
                'Request Type'      => $r->request_type ?: 'N/A',
                'Requested Amount'  => $this->n($r->request_amount),
                'Request Date'      => $r->request_date ? Carbon::parse($r->request_date)->format('d M Y') : 'N/A',
                'Purpose'           => $r->pourpose ?: 'N/A',
                'Pending Approver'  => $this->pendingApproverFor($r),
                'Current Status'    => $r->status ?: 'N/A',
            ])->values()->all();

        return [
            'columns' => ['Employee ID', 'Employee Name', 'Department', 'Position', 'Request Type', 'Requested Amount', 'Request Date', 'Purpose', 'Pending Approver', 'Current Status'],
            'rows'    => $rows,
        ];
    }

    public function loanSalaryAdvanceRepayment(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();

        $advances = $this->baseQuery($rid, $scoped)
            ->where('pa.status', 'Approved')
            ->when($f['department'] ?? null, fn($q) => $q->where('e.Dept_id', $f['department']))
            ->when($f['repayment_status'] ?? null, fn($q) => $q->where('pa.recovery_status', $f['repayment_status']))
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('pa.request_date', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('pa.request_date', '<=', $f['to_date']))
            ->orderByDesc('pa.request_date')
            ->get([
                'pa.id', 'e.Emp_id', DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as employee_name"),
                'd.name as dept_name', 'p.position_title', 'pa.request_type', 'pa.request_amount', 'pa.recovery_status',
            ]);

        $advanceIds = $advances->pluck('id')->all();
        $schedules = DB::table('payroll_recovery_schedule')->whereIn('payroll_advance_id', $advanceIds)->orderBy('repayment_date')->get()->groupBy('payroll_advance_id');

        $rows = $advances->map(function ($r) use ($schedules) {
            $rows = $schedules->get($r->id, collect());
            $paid = $rows->where('status', 'Paid');
            $pending = $rows->where('status', 'Pending')->sortBy('repayment_date');
            $totalRepaid = $paid->sum('amount');
            $lastPaid = $paid->sortByDesc('repayment_date')->first();
            $nextPending = $pending->first();
            $lastRow = $rows->sortByDesc('repayment_date')->first();
            $outstanding = $lastRow ? $lastRow->remaining_balance : ((float) $r->request_amount - $totalRepaid);

            return [
                'Employee ID'           => $r->Emp_id ?: 'N/A',
                'Employee Name'         => trim($r->employee_name) ?: 'N/A',
                'Department'            => $r->dept_name ?? 'N/A',
                'Position'              => $r->position_title ?? 'N/A',
                'Request Type'          => $r->request_type ?: 'N/A',
                'Approved Amount'       => $this->n($r->request_amount),
                'Total Repaid'          => $this->n($totalRepaid),
                'Outstanding Balance'   => $this->n($outstanding),
                'Last Deduction Month'  => $lastPaid ? Carbon::parse($lastPaid->repayment_date)->format('F Y') : 'N/A',
                'Next Deduction Date'   => $nextPending ? Carbon::parse($nextPending->repayment_date)->format('d M Y') : 'N/A',
                'Repayment Status'      => $r->recovery_status ?: 'N/A',
            ];
        })->values()->all();

        return [
            'columns' => ['Employee ID', 'Employee Name', 'Department', 'Position', 'Request Type', 'Approved Amount', 'Total Repaid', 'Outstanding Balance', 'Last Deduction Month', 'Next Deduction Date', 'Repayment Status'],
            'rows'    => $rows,
        ];
    }

    public function repaymentSchedule(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();

        $advances = $this->baseQuery($rid, $scoped)
            ->where('pa.status', 'Approved')
            ->when($f['employee'] ?? null, fn($q) => $q->where('e.id', $f['employee']))
            ->get([
                'pa.id', 'e.Emp_id', DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as employee_name"),
                'pa.request_type', 'pa.request_amount',
            ])->keyBy('id');

        $advanceIds = $advances->keys()->all();

        $rows = DB::table('payroll_recovery_schedule')
            ->whereIn('payroll_advance_id', $advanceIds)
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('repayment_date', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('repayment_date', '<=', $f['to_date']))
            ->orderBy('payroll_advance_id')
            ->orderBy('repayment_date')
            ->get()
            ->groupBy('payroll_advance_id')
            ->flatMap(function ($schedules, $advanceId) use ($advances) {
                $adv = $advances->get($advanceId);
                if (!$adv) return collect();
                $i = 0;
                return $schedules->map(function ($s) use ($adv, &$i) {
                    $i++;
                    return [
                        'Employee ID'                  => $adv->Emp_id ?: 'N/A',
                        'Employee Name'                => trim($adv->employee_name) ?: 'N/A',
                        'Request Type'                 => $adv->request_type ?: 'N/A',
                        'Approved Amount'               => $this->n($adv->request_amount),
                        'Installment Number'           => $i,
                        'Scheduled Deduction Month'    => $s->repayment_date ? Carbon::parse($s->repayment_date)->format('F Y') : 'N/A',
                        'Scheduled Deduction Amount'   => $this->n($s->amount),
                        'Remaining Balance'            => $this->n($s->remaining_balance),
                    ];
                });
            })->values()->all();

        return [
            'columns' => ['Employee ID', 'Employee Name', 'Request Type', 'Approved Amount', 'Installment Number', 'Scheduled Deduction Month', 'Scheduled Deduction Amount', 'Remaining Balance'],
            'rows'    => $rows,
        ];
    }

    public function payrollDeductionHistory(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();

        $advances = $this->baseQuery($rid, $scoped)
            ->when($f['department'] ?? null, fn($q) => $q->where('e.Dept_id', $f['department']))
            ->when($f['employee'] ?? null, fn($q) => $q->where('e.id', $f['employee']))
            ->get([
                'pa.id', 'e.Emp_id', DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as employee_name"),
                'pa.request_type',
            ])->keyBy('id');

        $advanceIds = $advances->keys()->all();

        $rows = DB::table('payroll_recovery_schedule')
            ->whereIn('payroll_advance_id', $advanceIds)
            ->where('status', 'Paid')
            ->when($f['from_date'] ?? null, fn($q) => $q->whereRaw('DATE(COALESCE(recovery_date, repayment_date)) >= ?', [$f['from_date']]))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereRaw('DATE(COALESCE(recovery_date, repayment_date)) <= ?', [$f['to_date']]))
            ->orderByRaw('COALESCE(recovery_date, repayment_date) desc')
            ->get()
            ->map(function ($s) use ($advances) {
                $adv = $advances->get($s->payroll_advance_id);
                $deductionDate = $s->recovery_date ?: $s->repayment_date;
                return [
                    'Employee ID'       => $adv->Emp_id ?? 'N/A',
                    'Employee Name'     => $adv ? (trim($adv->employee_name) ?: 'N/A') : 'N/A',
                    'Request Type'      => $adv->request_type ?? 'N/A',
                    'Payroll Cycle'     => $this->recoveredViaLabel($s->recovered_via),
                    'Deduction Month'   => $deductionDate ? Carbon::parse($deductionDate)->format('F Y') : 'N/A',
                    'Deducted Amount'   => $this->n($s->amount),
                    'Remaining Balance' => $this->n($s->remaining_balance),
                    'Deduction Status'  => $s->status ?: 'N/A',
                ];
            })
            ->filter(fn($r) => $r['Employee ID'] !== 'N/A' || $r['Employee Name'] !== 'N/A')
            ->values()->all();

        return [
            'columns' => ['Employee ID', 'Employee Name', 'Request Type', 'Payroll Cycle', 'Deduction Month', 'Deducted Amount', 'Remaining Balance', 'Deduction Status'],
            'rows'    => $rows,
        ];
    }

    public function salaryAdvanceLoanExecutiveSummary(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();

        $base = fn() => $this->baseQuery($rid, $scoped)
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('pa.request_date', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('pa.request_date', '<=', $f['to_date']));

        $totalRequests = (clone $base())->count();
        $pendingRequests = (clone $base())->whereIn('pa.status', ['Pending', 'In-Progress'])->count();
        $totalApprovedAmount = (clone $base())->where('pa.status', 'Approved')->sum('pa.request_amount');
        $completedPlans = (clone $base())->where('pa.status', 'Approved')->where('pa.recovery_status', 'Completed')->count();

        $approvedIds = (clone $base())->where('pa.status', 'Approved')->pluck('pa.id')->all();
        $schedules = DB::table('payroll_recovery_schedule')->whereIn('payroll_advance_id', $approvedIds)->get()->groupBy('payroll_advance_id');

        $totalOutstanding = 0.0;
        $totalRecovered = 0.0;
        $activePlans = 0;
        $advanceAmounts = (clone $base())->where('pa.status', 'Approved')->pluck('pa.request_amount', 'pa.id');

        foreach ($approvedIds as $id) {
            $rows = $schedules->get($id, collect());
            $paid = $rows->where('status', 'Paid');
            $pending = $rows->where('status', 'Pending');
            $totalRecovered += (float) $paid->sum('amount');
            $lastRow = $rows->sortByDesc('repayment_date')->first();
            $requestAmount = (float) ($advanceAmounts[$id] ?? 0);
            $totalOutstanding += $lastRow ? (float) $lastRow->remaining_balance : ($requestAmount - (float) $paid->sum('amount'));
            if ($pending->count() > 0) $activePlans++;
        }

        $rows = [[
            'Total Requests'            => $totalRequests,
            'Total Approved Amount'     => $this->n($totalApprovedAmount),
            'Total Outstanding Balance' => $this->n($totalOutstanding),
            'Total Recovered Amount'    => $this->n($totalRecovered),
            'Pending Requests'          => $pendingRequests,
            'Active Repayment Plans'    => $activePlans,
            'Completed Repayment Plans' => $completedPlans,
        ]];

        return [
            'columns' => ['Total Requests', 'Total Approved Amount', 'Total Outstanding Balance', 'Total Recovered Amount', 'Pending Requests', 'Active Repayment Plans', 'Completed Repayment Plans'],
            'rows'    => $rows,
        ];
    }
}
