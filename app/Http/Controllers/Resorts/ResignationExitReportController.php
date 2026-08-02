<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Resorts\Concerns\PredefinedReportActions;
use App\Helpers\Common;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Predefined People Management – Resignation & Exit Clearance reports
 * (Option B), generic view. Mirrors PromotionReportController's plumbing.
 *
 * SCHEMA NOTES (discovered by reading the live modules this mirrors —
 * People\ExitClearance\ExitClearanceController and
 * People\Employee\EmployeeResignationController):
 *
 *  - employee_resignation (model EmployeeResignation) is the master
 *    separation record: employee_id, reason (FK -> employee_resignation_
 *    reasons.id, NOT free text), resignation_date, last_working_day,
 *    status (Pending/On Hold/In Progress/Approved/Rejected/Completed/
 *    Withdraw), hod_status/hr_status (the resignation-approval stage,
 *    not a department clearance form), hod_meeting_status/hr_meeting_
 *    status, full_and_final_settlement (yes/no flag), departure_
 *    arrangements (JSON: international_flight, transportation_arranged,
 *    passport_validity, accommodation_arranged, documentVerifed).
 *
 *  - Exit clearance is a CONFIGURABLE per-resort list of department
 *    clearance forms, not fixed HOD/HR/Finance/IT/Accommodation columns:
 *    exit_clearance_form (template) -> exit_clearance_form_assignments
 *    (one row per department PER resignation, assigned_to_type=
 *    'department', department_id -> resort_departments.name, status
 *    Pending/Completed, completed_date) -> exit_clearance_form_responses
 *    (the filled-in answers). A resort can name its departments anything
 *    (test data has "Accounting", "F and B Service", …) — there is no
 *    guaranteed "Finance"/"IT"/"Accommodation" department. The
 *    Offboarding Checklist report (#8) below therefore keyword-matches
 *    department names for Finance/IT/Accommodation and falls back to
 *    'N/A' when the resort has no matching department assigned. HOD/HR
 *    Clearance use the always-present er.hod_status/hr_status instead
 *    (see method docblock on offboardingChecklistStatus() for the full
 *    reasoning).
 *
 *  - assigned_to_type='employee' assignments on the SAME table are what
 *    the app calls (in its own notification copy) the "Exit Interview
 *    Form" — assigned to the departing employee directly, independent
 *    of department clearance. That is the closest real "exit interview"
 *    concept in this codebase; there is no dedicated exit-interview
 *    model. ResignationMeetingSchedule (resignation_meeting_schedule)
 *    is a separate HOD/HR *approval* meeting scheduler (meeting_with =
 *    'HOD'|'HR'), not an exit interview with the leaving employee, and
 *    is unused in the local dataset (0 rows) — not used here.
 *
 *  - final_settlements (model FinalSettlement) is the real Full & Final
 *    Settlement record: employee_id, status enum('draft','review',
 *    'finalized') default 'draft', net_pay, finalized_at, doc_date. No
 *    resort_id column — scoped via the employees join. "Outstanding
 *    Amount" is modelled here as the absolute value of a NEGATIVE
 *    net_pay (amount still recoverable from the employee); a settlement
 *    with net_pay >= 0, or no settlement row at all, has nothing
 *    outstanding ('N/A').
 */
class ResignationExitReportController extends Controller
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
            'employee_separation_register' => [
                'name' => 'Employee Separation Register',
                'description' => 'Displays all employee resignations and separation cases during the selected period, including resignation details, notice period, exit progress, and current status. This serves as the master offboarding report.',
                'filters' => ['duration', 'department', 'position', 'separation_status', 'employee'],
                'handler' => 'employeeSeparationRegister',
            ],
            'pending_exit_clearance' => [
                'name' => 'Pending Exit Clearance Report',
                'description' => 'Displays employees whose exit clearance process is still incomplete, enabling HR to follow up with departments before the employee leaves the organization.',
                'filters' => ['duration', 'department', 'exit_clearance_status'],
                'handler' => 'pendingExitClearance',
            ],
            'exit_clearance_completion' => [
                'name' => 'Exit Clearance Completion Report',
                'description' => 'Displays completed exit clearance records, confirming that all departmental clearances, settlements, and exit formalities have been successfully completed.',
                'filters' => ['duration', 'department'],
                'handler' => 'exitClearanceCompletion',
            ],
            'employee_separation_analysis' => [
                'name' => 'Employee Separation Analysis Report',
                'description' => 'Analyzes employee resignations by department, position, resignation reason, and length of service to help management identify turnover trends.',
                'filters' => ['duration', 'department', 'position', 'resignation_reason'],
                'handler' => 'employeeSeparationAnalysis',
            ],
            'exit_interview_report' => [
                'name' => 'Exit Interview Report',
                'description' => 'Displays employees who have completed or are pending exit interviews, together with interview completion status and interview dates.',
                'filters' => ['duration', 'department', 'exit_interview_status'],
                'handler' => 'exitInterviewReport',
            ],
            'final_settlement_status' => [
                'name' => 'Full & Final Settlement Status Report',
                'description' => 'Displays the settlement status of resigned employees, helping HR and Finance monitor pending and completed full & final settlements before employee separation.',
                'filters' => ['duration', 'department', 'settlement_status'],
                'handler' => 'finalSettlementStatus',
            ],
            'employee_separation_executive_summary' => [
                'name' => 'Employee Separation Executive Summary',
                'description' => 'Provides management with an overview of employee resignations, pending clearances, completed separations, exit interviews, and settlement status during the selected period.',
                'filters' => ['duration'],
                'handler' => 'employeeSeparationExecutiveSummary',
            ],
            'offboarding_checklist_status' => [
                'name' => 'Offboarding Checklist Status Report',
                'description' => 'Displays the completion status of every offboarding activity assigned to a departing employee, allowing HR to ensure that no required task is missed before the employee\'s last working day.',
                'filters' => ['duration', 'department', 'employee', 'checklist_status'],
                'handler' => 'offboardingChecklistStatus',
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
        $reasons = DB::table('employee_resignation_reasons')->where('resort_id', $rid)->orderBy('reason')->get(['id', 'reason']);

        $statusOptions = ['Pending', 'On Hold', 'In Progress', 'Approved', 'Rejected', 'Completed', 'Withdraw'];
        $clearanceStatusOptions = ['Not Assigned', 'Pending', 'In Progress', 'Completed'];
        $interviewStatusOptions = ['Not Assigned', 'Pending', 'Completed'];
        $settlementStatusOptions = ['draft' => 'Draft', 'review' => 'Under Review', 'finalized' => 'Finalized'];
        $checklistStatusOptions = ['Not Started', 'In Progress', 'Completed'];

        $filterDefs = [
            ['filter' => 'department', 'name' => 'department', 'label' => 'Department', 'type' => 'select', 'placeholder' => 'All departments', 'options' => $departments->map(fn($d) => ['value' => $d->id, 'label' => $d->name])->all()],
            ['filter' => 'position', 'name' => 'position', 'label' => 'Position', 'type' => 'select', 'placeholder' => 'All positions', 'options' => $positions->map(fn($p) => ['value' => $p->id, 'label' => $p->position_title])->all()],
            ['filter' => 'employee', 'name' => 'employee', 'label' => 'Employee Name', 'type' => 'select', 'placeholder' => 'All employees', 'options' => $employees->map(fn($e) => ['value' => $e->id, 'label' => $e->name])->all()],
            ['filter' => 'separation_status', 'name' => 'separation_status', 'label' => 'Separation Status', 'type' => 'select', 'placeholder' => 'All statuses', 'options' => collect($statusOptions)->map(fn($s) => ['value' => $s, 'label' => $s])->all()],
            ['filter' => 'resignation_reason', 'name' => 'resignation_reason', 'label' => 'Resignation Reason', 'type' => 'select', 'placeholder' => 'All reasons', 'options' => $reasons->map(fn($r) => ['value' => $r->id, 'label' => $r->reason])->all()],
            ['filter' => 'exit_clearance_status', 'name' => 'exit_clearance_status', 'label' => 'Exit Clearance Status', 'type' => 'select', 'placeholder' => 'All statuses', 'options' => collect($clearanceStatusOptions)->map(fn($s) => ['value' => $s, 'label' => $s])->all()],
            ['filter' => 'exit_interview_status', 'name' => 'exit_interview_status', 'label' => 'Exit Interview Status', 'type' => 'select', 'placeholder' => 'All statuses', 'options' => collect($interviewStatusOptions)->map(fn($s) => ['value' => $s, 'label' => $s])->all()],
            ['filter' => 'settlement_status', 'name' => 'settlement_status', 'label' => 'Settlement Status', 'type' => 'select', 'placeholder' => 'All statuses', 'options' => collect($settlementStatusOptions)->map(fn($l, $v) => ['value' => $v, 'label' => $l])->values()->all()],
            ['filter' => 'checklist_status', 'name' => 'checklist_status', 'label' => 'Checklist Status', 'type' => 'select', 'placeholder' => 'All statuses', 'options' => collect($checklistStatusOptions)->map(fn($s) => ['value' => $s, 'label' => $s])->all()],
            ['filter' => 'duration', 'name' => 'from_date', 'label' => 'From Date', 'type' => 'date'],
            ['filter' => 'duration', 'name' => 'to_date', 'label' => 'To Date', 'type' => 'date'],
        ];

        return view('resorts.reports.module_report', [
            'page_title' => 'Resignation & Exit Clearance Reports', 'reports' => $reports, 'filterDefs' => $filterDefs,
            'runRoute' => 'resort.report.resignation_exit.run', 'exportRoute' => 'resort.report.resignation_exit.export', 'insightsRoute' => 'resort.report.resignation_exit.insights',
        ]);
    }

    private function filtersFrom(Request $request): array
    {
        return collect([
            'department', 'position', 'employee', 'separation_status', 'resignation_reason',
            'exit_clearance_status', 'exit_interview_status', 'settlement_status', 'checklist_status',
            'from_date', 'to_date',
        ])->mapWithKeys(fn($k) => [$k => $request->input($k) ?: null])->all();
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
        return DB::table('employee_resignation as er')
            ->join('employees as e', 'e.id', '=', 'er.employee_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->leftJoin('resort_departments as d', 'd.id', '=', 'e.Dept_id')
            ->leftJoin('resort_positions as p', 'p.id', '=', 'e.Position_id')
            ->leftJoin('employee_resignation_reasons as rr', 'rr.id', '=', 'er.reason')
            ->where('er.resort_id', $rid)
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped));
    }

    private function commonCols(): array
    {
        return [
            'er.id as resignation_id', 'e.id as employee_pk', 'e.Emp_id',
            DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as employee_name"),
            'd.name as dept_name', 'p.position_title', 'e.joining_date',
            'er.resignation_date', 'er.last_working_day', 'er.status as separation_status',
            'rr.reason as reason_text', 'er.hod_status', 'er.hr_status', 'er.departure_arrangements',
        ];
    }

    /** "3 yrs 2 mos" style duration between two dates, 'N/A' if either is missing/invalid. */
    private function lengthOfService($start, $end): string
    {
        if (!$start || !$end) return 'N/A';
        try {
            $s = Carbon::parse($start);
            $e = Carbon::parse($end);
            if ($e->lessThan($s)) return 'N/A';
            $diff = $s->diff($e);
            $parts = [];
            if ($diff->y > 0) $parts[] = $diff->y . ' yr' . ($diff->y === 1 ? '' : 's');
            if ($diff->m > 0) $parts[] = $diff->m . ' mo' . ($diff->m === 1 ? '' : 's');
            if (empty($parts)) $parts[] = max(1, $diff->d) . ' day' . ($diff->d === 1 ? '' : 's');
            return implode(' ', $parts);
        } catch (\Throwable $ex) {
            return 'N/A';
        }
    }

    /**
     * Department-clearance assignment summary for a batch of resignation ids.
     * Keyed by resignation id => ['total','completed','pending_depts'[],'completion_date','rows'[]].
     */
    private function exitClearanceSummaryMap(array $resignationIds): array
    {
        $map = [];
        foreach ($resignationIds as $id) {
            $map[$id] = ['total' => 0, 'completed' => 0, 'pending_depts' => [], 'completion_date' => null, 'rows' => []];
        }
        if (empty($resignationIds)) return $map;

        $rows = DB::table('exit_clearance_form_assignments as a')
            ->leftJoin('resort_departments as d', 'd.id', '=', 'a.department_id')
            ->whereIn('a.emp_resignation_id', $resignationIds)
            ->where('a.assigned_to_type', 'department')
            ->get(['a.emp_resignation_id', 'a.status', 'a.completed_date', 'd.name as dept_name']);

        foreach ($rows as $r) {
            $rid = $r->emp_resignation_id;
            if (!isset($map[$rid])) continue;
            $map[$rid]['total']++;
            $map[$rid]['rows'][] = $r;
            if ($r->status === 'Completed') {
                $map[$rid]['completed']++;
                if ($r->completed_date && (!$map[$rid]['completion_date'] || $r->completed_date > $map[$rid]['completion_date'])) {
                    $map[$rid]['completion_date'] = $r->completed_date;
                }
            } else {
                $map[$rid]['pending_depts'][] = $r->dept_name ?: 'Unassigned Department';
            }
        }
        return $map;
    }

    private function clearanceStatusLabel(array $summary): string
    {
        if ($summary['total'] === 0) return 'Not Assigned';
        if ($summary['completed'] === $summary['total']) return 'Completed';
        if ($summary['completed'] === 0) return 'Pending';
        return 'In Progress';
    }

    /** First department-assignment row whose department name matches $pattern, mapped to Completed/Pending, else 'N/A'. */
    private function deptClearanceKeywordLabel(array $summary, string $pattern): string
    {
        foreach ($summary['rows'] as $r) {
            if ($r->dept_name && preg_match($pattern, $r->dept_name)) {
                return $r->status === 'Completed' ? 'Completed' : 'Pending';
            }
        }
        return 'N/A';
    }

    /**
     * "Exit interview" assignment (assigned_to_type='employee') summary for a
     * batch of resignation ids. Keyed by resignation id => ['status','completed_date'] or null.
     */
    private function exitInterviewSummaryMap(array $resignationIds): array
    {
        if (empty($resignationIds)) return [];
        $rows = DB::table('exit_clearance_form_assignments')
            ->whereIn('emp_resignation_id', $resignationIds)
            ->where('assigned_to_type', 'employee')
            ->orderBy('id')
            ->get(['emp_resignation_id', 'status', 'completed_date']);
        $map = [];
        foreach ($rows as $r) {
            // Last one wins if an employee somehow has more than one form.
            $map[$r->emp_resignation_id] = ['status' => $r->status, 'completed_date' => $r->completed_date];
        }
        return $map;
    }

    private function exitInterviewLabel(?array $entry): string
    {
        if (!$entry) return 'Not Assigned';
        return $entry['status'] === 'Completed' ? 'Completed' : 'Pending';
    }

    /** Latest final_settlements row per employee id (raw stdClass or null). */
    private function settlementSummaryMap(array $employeeIds): array
    {
        if (empty($employeeIds)) return [];
        $rows = DB::table('final_settlements')->whereIn('employee_id', $employeeIds)->orderBy('id')->get();
        $map = [];
        foreach ($rows as $r) {
            $map[$r->employee_id] = $r; // last (highest id) wins
        }
        return $map;
    }

    private function settlementLabel($row): string
    {
        if (!$row) return 'Not Started';
        return match ($row->status) {
            'finalized' => 'Finalized',
            'review' => 'Under Review',
            'draft' => 'Draft',
            default => $row->status ? ucfirst($row->status) : 'Not Started',
        };
    }

    private function settlementDate($row): string
    {
        if (!$row) return 'N/A';
        $d = $row->finalized_at ?: $row->doc_date;
        return $d ? Carbon::parse($d)->format('d M Y') : 'N/A';
    }

    /** Absolute value of a negative net_pay (amount recoverable from the employee); 'N/A' if nothing outstanding. */
    private function outstandingAmount($row): string
    {
        if (!$row || $row->net_pay === null) return 'N/A';
        $net = (float) $row->net_pay;
        return $net < 0 ? Common::formatCurrency(abs($net)) : 'N/A';
    }

    private function departureFlags($raw): ?array
    {
        if (empty($raw)) return null;
        $d = is_array($raw) ? $raw : json_decode((string) $raw, true);
        return is_array($d) ? $d : null;
    }

    private function flightTransportLabel(?array $d): string
    {
        if ($d === null) return 'N/A';
        $ok = ((int) ($d['international_flight'] ?? 0) === 1) && ((int) ($d['transportation_arranged'] ?? 0) === 1);
        return $ok ? 'Completed' : 'Pending';
    }

    private function accommodationLabel(array $summary, ?array $departureFlags): string
    {
        $viaDept = $this->deptClearanceKeywordLabel($summary, '/accommodat|housing/i');
        if ($viaDept !== 'N/A') return $viaDept;
        if ($departureFlags === null) return 'N/A';
        return ((int) ($departureFlags['accommodation_arranged'] ?? 0) === 1) ? 'Completed' : 'Pending';
    }

    /** Percentage of applicable (non-null) flags that are true. 'N/A' when nothing is applicable. */
    private function overallCompletionPercent(array $flags): string
    {
        $applicable = array_filter($flags, fn($v) => $v !== null);
        if (empty($applicable)) return 'N/A';
        $completed = count(array_filter($applicable, fn($v) => $v === true));
        return round(($completed / count($applicable)) * 100) . '%';
    }

    /* --------------------------------------------------------------- reports */

    public function employeeSeparationRegister(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();

        $rows = $this->baseQuery($rid, $scoped)
            ->when($f['department'] ?? null, fn($q) => $q->where('e.Dept_id', $f['department']))
            ->when($f['position'] ?? null, fn($q) => $q->where('e.Position_id', $f['position']))
            ->when($f['separation_status'] ?? null, fn($q) => $q->where('er.status', $f['separation_status']))
            ->when($f['employee'] ?? null, fn($q) => $q->where('e.id', $f['employee']))
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('er.resignation_date', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('er.resignation_date', '<=', $f['to_date']))
            ->orderByDesc('er.resignation_date')
            ->get($this->commonCols())
            ->map(fn($r) => [
                'Employee ID'         => $r->Emp_id ?: 'N/A',
                'Employee Name'       => trim($r->employee_name) ?: 'N/A',
                'Department'          => $r->dept_name ?? 'N/A',
                'Position'            => $r->position_title ?? 'N/A',
                'Joining Date'        => $r->joining_date ? Carbon::parse($r->joining_date)->format('d M Y') : 'N/A',
                'Resignation Date'    => $r->resignation_date ? Carbon::parse($r->resignation_date)->format('d M Y') : 'N/A',
                'Last Working Date'   => $r->last_working_day ? Carbon::parse($r->last_working_day)->format('d M Y') : 'N/A',
                'Length of Service'   => $this->lengthOfService($r->joining_date, $r->last_working_day ?: $r->resignation_date),
                'Resignation Reason'  => $r->reason_text ?: 'N/A',
                'Separation Status'   => $r->separation_status ?: 'N/A',
            ])->values()->all();

        return [
            'columns' => ['Employee ID', 'Employee Name', 'Department', 'Position', 'Joining Date', 'Resignation Date', 'Last Working Date', 'Length of Service', 'Resignation Reason', 'Separation Status'],
            'rows'    => $rows,
        ];
    }

    public function pendingExitClearance(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();

        $base = $this->baseQuery($rid, $scoped)
            ->when($f['department'] ?? null, fn($q) => $q->where('e.Dept_id', $f['department']))
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('er.last_working_day', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('er.last_working_day', '<=', $f['to_date']))
            ->orderBy('er.last_working_day')
            ->get($this->commonCols());

        $ids = $base->pluck('resignation_id')->all();
        $clearanceMap = $this->exitClearanceSummaryMap($ids);
        $interviewMap = $this->exitInterviewSummaryMap($ids);
        $settlementMap = $this->settlementSummaryMap($base->pluck('employee_pk')->unique()->all());

        $rows = $base->map(function ($r) use ($clearanceMap, $interviewMap, $settlementMap) {
            $summary = $clearanceMap[$r->resignation_id];
            return [
                'Employee ID'                      => $r->Emp_id ?: 'N/A',
                'Employee Name'                     => trim($r->employee_name) ?: 'N/A',
                'Department'                        => $r->dept_name ?? 'N/A',
                'Position'                           => $r->position_title ?? 'N/A',
                'Last Working Date'                 => $r->last_working_day ? Carbon::parse($r->last_working_day)->format('d M Y') : 'N/A',
                'Exit Clearance Status'             => $this->clearanceStatusLabel($summary),
                'Pending Departments'               => $summary['total'] === 0 ? 'N/A' : (empty($summary['pending_depts']) ? 'None' : implode(', ', array_unique($summary['pending_depts']))),
                'Exit Interview Status'             => $this->exitInterviewLabel($interviewMap[$r->resignation_id] ?? null),
                'Full & Final Settlement Status'    => $this->settlementLabel($settlementMap[$r->employee_pk] ?? null),
                '__clearance_status'                => $this->clearanceStatusLabel($summary),
            ];
        })
            // Only incomplete clearances belong in this report.
            ->filter(fn($row) => $row['__clearance_status'] !== 'Completed')
            ->when($f['exit_clearance_status'] ?? null, fn($rows) => $rows->filter(fn($row) => $row['__clearance_status'] === $f['exit_clearance_status']))
            ->map(fn($row) => collect($row)->except('__clearance_status')->all())
            ->values()->all();

        return [
            'columns' => ['Employee ID', 'Employee Name', 'Department', 'Position', 'Last Working Date', 'Exit Clearance Status', 'Pending Departments', 'Exit Interview Status', 'Full & Final Settlement Status'],
            'rows'    => $rows,
        ];
    }

    public function exitClearanceCompletion(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();

        $base = $this->baseQuery($rid, $scoped)
            ->when($f['department'] ?? null, fn($q) => $q->where('e.Dept_id', $f['department']))
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('er.last_working_day', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('er.last_working_day', '<=', $f['to_date']))
            ->orderByDesc('er.last_working_day')
            ->get($this->commonCols());

        $ids = $base->pluck('resignation_id')->all();
        $clearanceMap = $this->exitClearanceSummaryMap($ids);
        $interviewMap = $this->exitInterviewSummaryMap($ids);
        $settlementMap = $this->settlementSummaryMap($base->pluck('employee_pk')->unique()->all());

        $rows = $base->map(function ($r) use ($clearanceMap, $interviewMap, $settlementMap) {
            $summary = $clearanceMap[$r->resignation_id];
            return [
                'Employee ID'                        => $r->Emp_id ?: 'N/A',
                'Employee Name'                       => trim($r->employee_name) ?: 'N/A',
                'Department'                          => $r->dept_name ?? 'N/A',
                'Position'                             => $r->position_title ?? 'N/A',
                'Last Working Date'                   => $r->last_working_day ? Carbon::parse($r->last_working_day)->format('d M Y') : 'N/A',
                'Exit Clearance Completion Date'      => $summary['completion_date'] ? Carbon::parse($summary['completion_date'])->format('d M Y') : 'N/A',
                'Department Clearance Status'         => $summary['total'] === 0 ? 'N/A' : ($summary['completed'] . '/' . $summary['total'] . ' Completed'),
                'Exit Interview Status'                => $this->exitInterviewLabel($interviewMap[$r->resignation_id] ?? null),
                'Full & Final Settlement Status'       => $this->settlementLabel($settlementMap[$r->employee_pk] ?? null),
                '__clearance_status'                   => $this->clearanceStatusLabel($summary),
            ];
        })
            ->filter(fn($row) => $row['__clearance_status'] === 'Completed')
            ->map(fn($row) => collect($row)->except('__clearance_status')->all())
            ->values()->all();

        return [
            'columns' => ['Employee ID', 'Employee Name', 'Department', 'Position', 'Last Working Date', 'Exit Clearance Completion Date', 'Department Clearance Status', 'Exit Interview Status', 'Full & Final Settlement Status'],
            'rows'    => $rows,
        ];
    }

    public function employeeSeparationAnalysis(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();

        $rows = $this->baseQuery($rid, $scoped)
            ->when($f['department'] ?? null, fn($q) => $q->where('e.Dept_id', $f['department']))
            ->when($f['position'] ?? null, fn($q) => $q->where('e.Position_id', $f['position']))
            ->when($f['resignation_reason'] ?? null, fn($q) => $q->where('er.reason', $f['resignation_reason']))
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('er.resignation_date', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('er.resignation_date', '<=', $f['to_date']))
            ->orderByDesc('er.resignation_date')
            ->get($this->commonCols())
            ->map(fn($r) => [
                'Employee ID'        => $r->Emp_id ?: 'N/A',
                'Employee Name'      => trim($r->employee_name) ?: 'N/A',
                'Department'         => $r->dept_name ?? 'N/A',
                'Position'           => $r->position_title ?? 'N/A',
                'Length of Service'  => $this->lengthOfService($r->joining_date, $r->last_working_day ?: $r->resignation_date),
                'Resignation Reason' => $r->reason_text ?: 'N/A',
                'Resignation Date'   => $r->resignation_date ? Carbon::parse($r->resignation_date)->format('d M Y') : 'N/A',
                'Last Working Date'  => $r->last_working_day ? Carbon::parse($r->last_working_day)->format('d M Y') : 'N/A',
            ])->values()->all();

        return [
            'columns' => ['Employee ID', 'Employee Name', 'Department', 'Position', 'Length of Service', 'Resignation Reason', 'Resignation Date', 'Last Working Date'],
            'rows'    => $rows,
        ];
    }

    public function exitInterviewReport(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();

        $base = $this->baseQuery($rid, $scoped)
            ->when($f['department'] ?? null, fn($q) => $q->where('e.Dept_id', $f['department']))
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('er.last_working_day', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('er.last_working_day', '<=', $f['to_date']))
            ->orderByDesc('er.last_working_day')
            ->get($this->commonCols());

        $ids = $base->pluck('resignation_id')->all();
        $interviewMap = $this->exitInterviewSummaryMap($ids);

        $rows = $base->map(function ($r) use ($interviewMap) {
            $entry = $interviewMap[$r->resignation_id] ?? null;
            return [
                'Employee ID'                => $r->Emp_id ?: 'N/A',
                'Employee Name'              => trim($r->employee_name) ?: 'N/A',
                'Department'                 => $r->dept_name ?? 'N/A',
                'Position'                    => $r->position_title ?? 'N/A',
                'Last Working Date'           => $r->last_working_day ? Carbon::parse($r->last_working_day)->format('d M Y') : 'N/A',
                'Exit Interview Status'       => $this->exitInterviewLabel($entry),
                'Interview Completion Date'   => ($entry && $entry['completed_date']) ? Carbon::parse($entry['completed_date'])->format('d M Y') : 'N/A',
                '__interview_status'          => $this->exitInterviewLabel($entry),
            ];
        })
            ->when($f['exit_interview_status'] ?? null, fn($rows) => $rows->filter(fn($row) => $row['__interview_status'] === $f['exit_interview_status']))
            ->map(fn($row) => collect($row)->except('__interview_status')->all())
            ->values()->all();

        return [
            'columns' => ['Employee ID', 'Employee Name', 'Department', 'Position', 'Last Working Date', 'Exit Interview Status', 'Interview Completion Date'],
            'rows'    => $rows,
        ];
    }

    public function finalSettlementStatus(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();

        $base = $this->baseQuery($rid, $scoped)
            ->when($f['department'] ?? null, fn($q) => $q->where('e.Dept_id', $f['department']))
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('er.last_working_day', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('er.last_working_day', '<=', $f['to_date']))
            ->orderByDesc('er.last_working_day')
            ->get($this->commonCols());

        $settlementMap = $this->settlementSummaryMap($base->pluck('employee_pk')->unique()->all());

        $rows = $base->map(function ($r) use ($settlementMap) {
            $row = $settlementMap[$r->employee_pk] ?? null;
            return [
                'Employee ID'                     => $r->Emp_id ?: 'N/A',
                'Employee Name'                    => trim($r->employee_name) ?: 'N/A',
                'Department'                       => $r->dept_name ?? 'N/A',
                'Position'                          => $r->position_title ?? 'N/A',
                'Last Working Date'                => $r->last_working_day ? Carbon::parse($r->last_working_day)->format('d M Y') : 'N/A',
                'Full & Final Settlement Status'   => $this->settlementLabel($row),
                'Settlement Date'                  => $this->settlementDate($row),
                'Outstanding Amount (if applicable)' => $this->outstandingAmount($row),
                '__status_raw'                      => $row->status ?? null,
            ];
        })
            ->when($f['settlement_status'] ?? null, fn($rows) => $rows->filter(fn($row) => $row['__status_raw'] === $f['settlement_status']))
            ->map(fn($row) => collect($row)->except('__status_raw')->all())
            ->values()->all();

        return [
            'columns' => ['Employee ID', 'Employee Name', 'Department', 'Position', 'Last Working Date', 'Full & Final Settlement Status', 'Settlement Date', 'Outstanding Amount (if applicable)'],
            'rows'    => $rows,
        ];
    }

    public function employeeSeparationExecutiveSummary(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();

        $base = fn() => $this->baseQuery($rid, $scoped)
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('er.resignation_date', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('er.resignation_date', '<=', $f['to_date']));

        $all = (clone $base())->get($this->commonCols());

        $total = $all->count();
        $pending = $all->whereIn('separation_status', ['Pending', 'On Hold', 'In Progress'])->count();
        $approved = $all->where('separation_status', 'Approved')->count();
        $yetToExit = $all->filter(fn($r) => in_array($r->separation_status, ['Approved', 'Pending', 'On Hold', 'In Progress'])
            && (!$r->last_working_day || Carbon::parse($r->last_working_day)->isFuture()))->count();

        $ids = $all->pluck('resignation_id')->all();
        $clearanceMap = $this->exitClearanceSummaryMap($ids);
        $interviewMap = $this->exitInterviewSummaryMap($ids);
        $settlementMap = $this->settlementSummaryMap($all->pluck('employee_pk')->unique()->all());

        $pendingClearances = 0;
        $completedClearances = 0;
        foreach ($ids as $id) {
            $this->clearanceStatusLabel($clearanceMap[$id]) === 'Completed' ? $completedClearances++ : $pendingClearances++;
        }

        $pendingInterviews = 0;
        foreach ($ids as $id) {
            if ($this->exitInterviewLabel($interviewMap[$id] ?? null) !== 'Completed') $pendingInterviews++;
        }

        $completedSettlements = 0;
        foreach ($all->pluck('employee_pk')->unique() as $empId) {
            if (($settlementMap[$empId]->status ?? null) === 'finalized') $completedSettlements++;
        }

        $rows = [[
            'Total Resignations'         => $total,
            'Pending Resignations'       => $pending,
            'Approved Resignations'      => $approved,
            'Employees Yet to Exit'      => $yetToExit,
            'Pending Exit Clearances'    => $pendingClearances,
            'Completed Exit Clearances'  => $completedClearances,
            'Pending Exit Interviews'    => $pendingInterviews,
            'Completed Settlements'      => $completedSettlements,
        ]];

        return [
            'columns' => ['Total Resignations', 'Pending Resignations', 'Approved Resignations', 'Employees Yet to Exit', 'Pending Exit Clearances', 'Completed Exit Clearances', 'Pending Exit Interviews', 'Completed Settlements'],
            'rows'    => $rows,
        ];
    }

    /**
     * Offboarding Checklist Status Report — the one report the spec asks for
     * with fixed HOD/HR/Finance/IT/Accommodation columns. As documented on
     * the class docblock, exit clearance is a per-resort CONFIGURABLE list of
     * department forms, so there is no guaranteed "Finance"/"IT" department
     * to key off. Column-by-column resolution used here:
     *
     *  - HOD Clearance / HR Clearance: the resignation's own hod_status /
     *    hr_status — always present, real fields (the resignation-approval
     *    stage each role must sign off).
     *  - Finance / IT Clearance: best-effort keyword match against the
     *    resort's assigned department-clearance forms (department name
     *    containing "financ"/"account" or "it"/"information technology").
     *    'N/A' when the resort has no such department assigned.
     *  - Accommodation Clearance: keyword match first (department name
     *    containing "accommodat"/"housing"), else the employee_resignation.
     *    departure_arrangements.accommodation_arranged flag ("has
     *    accommodation in Malé been arranged/vacated"). 'N/A' if neither
     *    exists.
     *  - Uniform/Asset Return Status: NOT modelled anywhere in this
     *    codebase (departure_arrangements has no uniform/asset field) —
     *    always 'N/A'.
     *  - Flight/Transportation Arrangement Status: departure_arrangements.
     *    international_flight + transportation_arranged flags.
     *  - Exit Interview Status: the assigned_to_type='employee' clearance
     *    form (the app's own "Exit Interview Form").
     *  - Full & Final Settlement Status: final_settlements.status.
     *  - Overall Completion (%): share of TRUE among the applicable
     *    (non-'N/A') items above — Uniform is always excluded, Finance/IT/
     *    Accommodation/Interview/Settlement are excluded per-row when 'N/A'.
     */
    public function offboardingChecklistStatus(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();

        $base = $this->baseQuery($rid, $scoped)
            ->when($f['department'] ?? null, fn($q) => $q->where('e.Dept_id', $f['department']))
            ->when($f['employee'] ?? null, fn($q) => $q->where('e.id', $f['employee']))
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('er.last_working_day', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('er.last_working_day', '<=', $f['to_date']))
            ->orderByDesc('er.last_working_day')
            ->get($this->commonCols());

        $ids = $base->pluck('resignation_id')->all();
        $clearanceMap = $this->exitClearanceSummaryMap($ids);
        $interviewMap = $this->exitInterviewSummaryMap($ids);
        $settlementMap = $this->settlementSummaryMap($base->pluck('employee_pk')->unique()->all());

        $rows = $base->map(function ($r) use ($clearanceMap, $interviewMap, $settlementMap) {
            $summary = $clearanceMap[$r->resignation_id];
            $departureFlags = $this->departureFlags($r->departure_arrangements);

            $hod = $r->hod_status ?: 'Pending';
            $hr = $r->hr_status ?: 'Pending';
            $finance = $this->deptClearanceKeywordLabel($summary, '/financ|account/i');
            $it = $this->deptClearanceKeywordLabel($summary, '/\bit\b|information\s*technology/i');
            $accommodation = $this->accommodationLabel($summary, $departureFlags);
            $uniform = 'N/A';
            $flight = $this->flightTransportLabel($departureFlags);
            $interview = $this->exitInterviewLabel($interviewMap[$r->resignation_id] ?? null);
            $settlementRow = $settlementMap[$r->employee_pk] ?? null;
            $settlement = $this->settlementLabel($settlementRow);

            $percent = $this->overallCompletionPercent([
                'hod' => $hod === 'Approved',
                'hr' => $hr === 'Approved',
                'finance' => $finance === 'N/A' ? null : ($finance === 'Completed'),
                'it' => $it === 'N/A' ? null : ($it === 'Completed'),
                'accommodation' => $accommodation === 'N/A' ? null : ($accommodation === 'Completed'),
                'flight' => $flight === 'N/A' ? null : ($flight === 'Completed'),
                'interview' => $interview === 'Not Assigned' ? null : ($interview === 'Completed'),
                'settlement' => $settlement === 'Finalized',
            ]);

            return [
                'Employee ID'                                  => $r->Emp_id ?: 'N/A',
                'Employee Name'                                 => trim($r->employee_name) ?: 'N/A',
                'Department'                                     => $r->dept_name ?? 'N/A',
                'Position'                                        => $r->position_title ?? 'N/A',
                'Last Working Date'                              => $r->last_working_day ? Carbon::parse($r->last_working_day)->format('d M Y') : 'N/A',
                'HOD Clearance'                                   => $hod,
                'HR Clearance'                                    => $hr,
                'Finance Clearance'                               => $finance,
                'IT Clearance'                                    => $it,
                'Accommodation Clearance'                         => $accommodation,
                'Uniform/Asset Return Status'                     => $uniform,
                'Flight/Transportation Arrangement Status'        => $flight,
                'Exit Interview Status'                           => $interview,
                'Full & Final Settlement Status'                  => $settlement,
                'Overall Completion (%)'                          => $percent,
                '__percent_raw'                                    => $percent,
            ];
        })
            ->when($f['checklist_status'] ?? null, function ($rows) use ($f) {
                return $rows->filter(function ($row) use ($f) {
                    $pct = $row['__percent_raw'] === 'N/A' ? null : (int) rtrim($row['__percent_raw'], '%');
                    $status = $pct === null ? 'Not Started' : ($pct >= 100 ? 'Completed' : ($pct <= 0 ? 'Not Started' : 'In Progress'));
                    return $status === $f['checklist_status'];
                });
            })
            ->map(fn($row) => collect($row)->except('__percent_raw')->all())
            ->values()->all();

        return [
            'columns' => ['Employee ID', 'Employee Name', 'Department', 'Position', 'Last Working Date', 'HOD Clearance', 'HR Clearance', 'Finance Clearance', 'IT Clearance', 'Accommodation Clearance', 'Uniform/Asset Return Status', 'Flight/Transportation Arrangement Status', 'Exit Interview Status', 'Full & Final Settlement Status', 'Overall Completion (%)'],
            'rows'    => $rows,
        ];
    }
}
