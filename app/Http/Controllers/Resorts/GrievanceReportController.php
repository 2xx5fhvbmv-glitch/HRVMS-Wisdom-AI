<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Resorts\Concerns\PredefinedReportActions;
use App\Helpers\Common;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Predefined Grievance & Disciplinary reports (Option B), generic view.
 * Combines grivance_submission_models + disciplinary_submits into a normalised
 * "case" set. No stored SLA target date / issued-letter / code-of-conduct
 * acknowledgement tables -> those columns/reports are N/A or empty.
 */
class GrievanceReportController extends Controller
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
            'exec_summary'        => ['name' => 'Grievance & Disciplinary Executive Summary', 'description' => 'Overview of cases, status and resolution.', 'filters' => [], 'handler' => 'execSummary'],
            'grievance_register'  => ['name' => 'Grievance Register', 'description' => 'All grievance cases.', 'filters' => ['duration', 'status'], 'handler' => 'grievanceRegister'],
            'disciplinary_register' => ['name' => 'Disciplinary Register', 'description' => 'All disciplinary cases.', 'filters' => ['duration', 'status'], 'handler' => 'disciplinaryRegister'],
            'open_cases'          => ['name' => 'Open Case Report', 'description' => 'Cases still open.', 'filters' => ['case_type'], 'handler' => 'openCases'],
            'pending_cases'       => ['name' => 'Pending Case Report', 'description' => 'Cases pending action.', 'filters' => ['case_type'], 'handler' => 'pendingCases'],
            'closed_cases'        => ['name' => 'Closed Case Report', 'description' => 'Resolved/closed cases.', 'filters' => ['duration'], 'handler' => 'closedCases'],
            'confidential_cases'  => ['name' => 'Confidential Case Report', 'description' => 'Grievances flagged confidential.', 'filters' => [], 'handler' => 'confidentialCases'],
            'delegated_cases'     => ['name' => 'Delegated Case Report', 'description' => 'Cases assigned to committees.', 'filters' => ['case_type'], 'handler' => 'delegatedCases'],
            'pending_approval'    => ['name' => 'Pending Approval Report', 'description' => 'Cases awaiting approval.', 'filters' => [], 'handler' => 'pendingApproval'],
            'case_timeline'       => ['name' => 'Case Timeline Report', 'description' => 'Case start vs target resolution.', 'filters' => ['duration'], 'handler' => 'caseTimeline'],
            'sla_compliance'      => ['name' => 'SLA Compliance Report', 'description' => 'Resolution vs target dates.', 'filters' => ['duration'], 'handler' => 'slaCompliance'],
            'overdue_cases'       => ['name' => 'Overdue Case Report', 'description' => 'Open cases past expected resolution.', 'filters' => [], 'handler' => 'overdueCases'],
            'expired_offense'     => ['name' => 'Expired Offense Report', 'description' => 'Disciplinary actions past validity.', 'filters' => ['duration'], 'handler' => 'expiredOffense'],
            'grievance_category'  => ['name' => 'Grievance Category Analysis', 'description' => 'Grievances by category.', 'filters' => [], 'handler' => 'grievanceCategory'],
            'disciplinary_category' => ['name' => 'Disciplinary Category Analysis', 'description' => 'Disciplinary cases by category.', 'filters' => [], 'handler' => 'disciplinaryCategory'],
            'department_analysis' => ['name' => 'Department Case Analysis', 'description' => 'Cases per department.', 'filters' => ['department'], 'handler' => 'departmentAnalysis'],
            'employee_history'    => ['name' => 'Employee Case History', 'description' => 'A single employee\'s cases.', 'filters' => ['employee'], 'handler' => 'employeeHistory'],
            'investigation_progress' => ['name' => 'Investigation Progress Report', 'description' => 'Open investigations and stage.', 'filters' => ['case_type'], 'handler' => 'investigationProgress'],
            'investigation_outcome' => ['name' => 'Investigation Outcome Report', 'description' => 'Investigation results.', 'filters' => ['duration'], 'handler' => 'investigationOutcome'],
            'followup_action'     => ['name' => 'Follow-up Action Report', 'description' => 'Investigation follow-up actions.', 'filters' => ['case_type'], 'handler' => 'followupAction'],
            'resolution_summary'  => ['name' => 'Resolution Summary Report', 'description' => 'How cases were resolved.', 'filters' => ['duration'], 'handler' => 'resolutionSummary'],
            'committee_workload'  => ['name' => 'Committee Workload Report', 'description' => 'Cases per committee.', 'filters' => [], 'handler' => 'committeeWorkload'],
            'investigator_performance' => ['name' => 'Investigator Performance Report', 'description' => 'Cases and resolution time per committee/investigator.', 'filters' => [], 'handler' => 'investigatorPerformance'],
            'case_trend'          => ['name' => 'Case Trend Report', 'description' => 'Monthly case trend.', 'filters' => ['year'], 'handler' => 'caseTrend'],
            'repeat_offender'     => ['name' => 'Repeat Offender Report', 'description' => 'Employees with multiple cases.', 'filters' => [], 'handler' => 'repeatOffender'],
            'offense_frequency'   => ['name' => 'Offense Frequency Report', 'description' => 'Most frequent offenses.', 'filters' => [], 'handler' => 'offenseFrequency'],
            'code_of_conduct'     => ['name' => 'Code of Conduct Distribution Report', 'description' => 'Code acknowledgement (not tracked).', 'filters' => ['department'], 'handler' => 'codeOfConduct'],
            'letter_register'     => ['name' => 'Grievance & Disciplinary Letter Register', 'description' => 'Issued letters (only templates stored).', 'filters' => [], 'handler' => 'letterRegister'],
            'document_audit'      => ['name' => 'Document Audit Report', 'description' => 'Case documents on file.', 'filters' => ['duration'], 'handler' => 'documentAudit'],
        ];
    }

    public function index()
    {
        if (Common::checkRouteWisePermission('resort.report.index', config('settings.resort_permissions.view')) == false) return abort(403, 'Unauthorized access');
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();
        $reports = collect($this->registry())->map(fn($r, $key) => ['key' => $key, 'name' => $r['name'], 'description' => $r['description'],
            // Every report exposes the duration (From/To date) filter, like the Custom Report.
            'filters' => array_values(array_unique(array_merge($r['filters'], ['duration']))),
        ])->values();

        $departments = DB::table('resort_departments')->where('resort_id', $rid)->when($scoped !== null, fn($q) => $q->whereIn('id', $scoped))->orderBy('name')->get(['id', 'name']);
        $employees = DB::table('employees as e')->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')->where('e.resort_id', $rid)->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))->orderBy('ra.first_name')->get(['e.id', DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as name")]);
        $opt = fn($coll, $v, $l) => $coll->map(fn($x) => ['value' => $x->$v, 'label' => $x->$l])->all();

        $filterDefs = [
            ['filter' => 'case_type', 'name' => 'case_type', 'label' => 'Case Type', 'type' => 'select', 'placeholder' => 'All cases', 'options' => [['value' => 'Grievance', 'label' => 'Grievance'], ['value' => 'Disciplinary', 'label' => 'Disciplinary']]],
            ['filter' => 'status', 'name' => 'status', 'label' => 'Status', 'type' => 'select', 'placeholder' => 'All statuses', 'options' => collect(['pending', 'in_review', 'resolved', 'rejected'])->map(fn($s) => ['value' => $s, 'label' => ucfirst(str_replace('_', ' ', $s))])->all()],
            ['filter' => 'department', 'name' => 'department', 'label' => 'Department', 'type' => 'select', 'placeholder' => 'All departments', 'options' => $opt($departments, 'id', 'name')],
            ['filter' => 'employee', 'name' => 'employee', 'label' => 'Employee', 'type' => 'select', 'placeholder' => 'All employees', 'options' => $opt($employees, 'id', 'name')],
            ['filter' => 'year', 'name' => 'year', 'label' => 'Year', 'type' => 'select', 'options' => collect(range((int) date('Y'), (int) date('Y') - 4))->map(fn($y) => ['value' => $y, 'label' => $y])->all()],
            ['filter' => 'duration', 'name' => 'from_date', 'label' => 'From Date', 'type' => 'date'],
            ['filter' => 'duration', 'name' => 'to_date', 'label' => 'To Date', 'type' => 'date'],
        ];

        return view('resorts.reports.module_report', [
            'page_title' => 'Grievance & Disciplinary Reports', 'reports' => $reports, 'filterDefs' => $filterDefs,
            'runRoute' => 'resort.report.grievance.run', 'exportRoute' => 'resort.report.grievance.export', 'insightsRoute' => 'resort.report.grievance.insights',
        ]);
    }

    private function filtersFrom(Request $request): array
    {
        return collect(['case_type', 'status', 'department', 'employee', 'year', 'from_date', 'to_date'])
            ->mapWithKeys(fn($k) => [$k => $request->input($k) ?: null])->all();
    }

    private function compute(string $key, array $filters): ?array
    {
        $registry = $this->registry();
        if (!isset($registry[$key])) return null;
        $res = $this->{$registry[$key]['handler']}($filters);
        return ['name' => $registry[$key]['name'], 'description' => $registry[$key]['description'], 'columns' => $res['columns'], 'rows' => $this->appendTotalsRow($res['columns'], $res['rows'])];
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

    /* helpers */
    private function applyDuration($q, array $f, string $col)
    {
        return $q->when($f['from_date'] ?? null, fn($x) => $x->whereDate($col, '>=', $f['from_date']))
                 ->when($f['to_date'] ?? null, fn($x) => $x->whereDate($col, '<=', $f['to_date']));
    }

    private function dmy($d): string { return $d ? Carbon::parse($d)->format('d M Y') : 'N/A'; }
    private function pct($n, $d): string { return $d ? round($n / $d * 100, 1) . '%' : '0%'; }
    private function isOpen($s): bool { return in_array(strtolower((string) $s), ['pending', 'in_review']); }
    private function statusLabel($s): string { return ucfirst(str_replace('_', ' ', strtolower((string) $s))); }

    private function grievanceQuery(array $f)
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();
        return DB::table('grivance_submission_models as g')
            ->leftJoin('grievance_categories as gc', 'gc.id', '=', 'g.Grivance_Cat_id')
            ->leftJoin('employees as e', 'e.id', '=', 'g.Employee_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->leftJoin('resort_departments as d', 'd.id', '=', 'e.Dept_id')
            ->leftJoin('grievance_committee_member_parents as gcm', 'gcm.id', '=', 'g.Committee_id')
            ->where('g.resort_id', $rid)
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))
            ->when($f['department'] ?? null, fn($q) => $q->where('e.Dept_id', $f['department']))
            ->when($f['employee'] ?? null, fn($q) => $q->where('g.Employee_id', $f['employee']))
            ->when($f['status'] ?? null, fn($q) => $q->where('g.status', $f['status']));
    }

    private function disciplinaryQuery(array $f)
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();
        return DB::table('disciplinary_submits as ds')
            ->leftJoin('disciplinary_categories_models as dc', 'dc.id', '=', 'ds.Category_id')
            ->leftJoin('offenses_models as of', 'of.id', '=', 'ds.Offence_id')
            ->leftJoin('action_stores as ac', 'ac.id', '=', 'ds.Action_id')
            ->leftJoin('employees as e', 'e.id', '=', 'ds.Employee_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->leftJoin('resort_departments as d', 'd.id', '=', 'e.Dept_id')
            ->leftJoin('disciplinery_assign_committees as comm', 'comm.id', '=', 'ds.Committee_id')
            ->where('ds.resort_id', $rid)
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))
            ->when($f['department'] ?? null, fn($q) => $q->where('e.Dept_id', $f['department']))
            ->when($f['employee'] ?? null, fn($q) => $q->where('ds.Employee_id', $f['employee']))
            ->when($f['status'] ?? null, fn($q) => $q->where('ds.status', $f['status']));
    }

    private $nm = "TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as employee_name";

    /** Normalised combined case list. */
    private function allCases(array $f)
    {
        $rid = $this->resort->resort_id;
        $cases = collect();

        if (($f['case_type'] ?? null) !== 'Disciplinary') {
            $g = $this->grievanceQuery($f)->get(['g.id', 'g.Grivance_id', 'gc.Category_Name', 'g.status', 'g.created_at', 'g.Grivance_date_time', 'g.Request_Identity_Disclosure', 'gcm.Grivance_CommitteeName', 'd.name as dept', DB::raw($this->nm),
                DB::raw("(SELECT MAX(resolution_date) FROM grivance_investigation_models WHERE Grievance_s_id = g.id) as resolved")]);
            foreach ($g as $r) {
                $created = $r->Grivance_date_time ?: $r->created_at;
                $cases->push(['case_id' => $r->Grivance_id ?: ('GRV-' . $r->id), 'type' => 'Grievance', 'employee' => trim($r->employee_name) ?: 'N/A',
                    'dept' => $r->dept ?? 'N/A', 'category' => $r->Category_Name ?? 'N/A', 'offense' => 'N/A', 'created' => $created,
                    'status' => $r->status, 'resolved' => $r->resolved, 'committee' => $r->Grivance_CommitteeName ?? 'N/A',
                    'confidential' => $r->Request_Identity_Disclosure, 'expiry' => null]);
            }
        }
        if (($f['case_type'] ?? null) !== 'Grievance') {
            $ds = $this->disciplinaryQuery($f)->get(['ds.id', 'ds.Disciplinary_id', 'dc.DisciplinaryCategoryName', 'of.OffensesName', 'ds.status', 'ds.created_at', 'ds.Expiry_date', 'comm.CommitteeName', 'd.name as dept', DB::raw($this->nm),
                DB::raw("(SELECT MAX(resolution_date) FROM disciplinary_investigation_parents WHERE Disciplinary_id = ds.id) as resolved")]);
            foreach ($ds as $r) {
                $cases->push(['case_id' => $r->Disciplinary_id ?: ('DSP-' . $r->id), 'type' => 'Disciplinary', 'employee' => trim($r->employee_name) ?: 'N/A',
                    'dept' => $r->dept ?? 'N/A', 'category' => $r->DisciplinaryCategoryName ?? 'N/A', 'offense' => $r->OffensesName ?? 'N/A', 'created' => $r->created_at,
                    'status' => $r->status, 'resolved' => $r->resolved, 'committee' => $r->CommitteeName ?? 'N/A',
                    'confidential' => null, 'expiry' => $r->Expiry_date]);
            }
        }
        // Centralised duration (From/To date) filter on the normalised case-created date,
        // so every in-memory consumer of allCases() honours the duration filter.
        return $cases->filter(fn($c) => (!($f['from_date'] ?? null) || ($c['created'] && substr($c['created'], 0, 10) >= $f['from_date']))
            && (!($f['to_date'] ?? null) || ($c['created'] && substr($c['created'], 0, 10) <= $f['to_date'])))->values();
    }

    private function resolutionDays($created, $resolved)
    {
        if (!$created || !$resolved) return null;
        return Carbon::parse($created)->diffInDays(Carbon::parse($resolved));
    }

    /* reports */

    public function execSummary(array $f): array
    {
        $cases = $this->allCases(['from_date' => $f['from_date'] ?? null, 'to_date' => $f['to_date'] ?? null]);
        $griev = $cases->where('type', 'Grievance')->count();
        $disc = $cases->where('type', 'Disciplinary')->count();
        $open = $cases->filter(fn($c) => $this->isOpen($c['status']))->count();
        $closed = $cases->reject(fn($c) => $this->isOpen($c['status']))->count();
        $resTimes = $cases->map(fn($c) => $this->resolutionDays($c['created'], $c['resolved']))->filter(fn($v) => $v !== null);
        return ['columns' => ['Total Grievances', 'Total Disciplinary Cases', 'Open Cases', 'Pending Cases', 'Closed Cases', 'Average Resolution Time'],
            'rows' => [['Total Grievances' => $griev, 'Total Disciplinary Cases' => $disc, 'Open Cases' => $open, 'Pending Cases' => $cases->filter(fn($c) => strtolower($c['status']) === 'pending')->count(), 'Closed Cases' => $closed, 'Average Resolution Time' => $resTimes->count() ? round($resTimes->avg(), 1) . ' day(s)' : 'N/A']]];
    }

    public function grievanceRegister(array $f): array
    {
        $rows = $this->grievanceQuery($f)->when(true, fn($q) => $this->applyDuration($q, $f, 'g.created_at'))
            ->orderByDesc('g.created_at')->get(['g.id', 'g.Grivance_id', 'gc.Category_Name', 'g.status', 'g.created_at', 'g.Grivance_date_time', 'g.Request_Identity_Disclosure', 'd.name as dept', DB::raw($this->nm)])
            ->map(fn($r) => [
                'Grievance ID' => $r->Grivance_id ?: ('GRV-' . $r->id), 'Category' => $r->Category_Name ?? 'N/A',
                'Employee Name' => trim($r->employee_name) ?: 'N/A', 'Department' => $r->dept ?? 'N/A',
                'Created Date' => $this->dmy($r->Grivance_date_time ?: $r->created_at),
                'Confidential Status' => $r->Request_Identity_Disclosure ? (string) $r->Request_Identity_Disclosure : 'Standard',
                'Current Status' => $this->statusLabel($r->status),
            ])->all();
        return ['columns' => ['Grievance ID', 'Category', 'Employee Name', 'Department', 'Created Date', 'Confidential Status', 'Current Status'], 'rows' => $rows];
    }

    public function disciplinaryRegister(array $f): array
    {
        $rows = $this->disciplinaryQuery($f)->when(true, fn($q) => $this->applyDuration($q, $f, 'ds.created_at'))
            ->orderByDesc('ds.created_at')->get(['ds.id', 'ds.Disciplinary_id', 'dc.DisciplinaryCategoryName', 'of.OffensesName', 'ds.status', 'ds.created_at', 'ds.Expiry_date', 'd.name as dept', DB::raw($this->nm)])
            ->map(fn($r) => [
                'Case ID' => $r->Disciplinary_id ?: ('DSP-' . $r->id), 'Category' => $r->DisciplinaryCategoryName ?? 'N/A',
                'Offense' => $r->OffensesName ?? 'N/A', 'Employee Name' => trim($r->employee_name) ?: 'N/A',
                'Department' => $r->dept ?? 'N/A', 'Created Date' => $this->dmy($r->created_at),
                'Action Valid Until' => $this->dmy($r->Expiry_date), 'Status' => $this->statusLabel($r->status),
            ])->all();
        return ['columns' => ['Case ID', 'Category', 'Offense', 'Employee Name', 'Department', 'Created Date', 'Action Valid Until', 'Status'], 'rows' => $rows];
    }

    public function openCases(array $f): array
    {
        $rows = $this->allCases($f)->filter(fn($c) => $this->isOpen($c['status']))->sortByDesc('created')
            ->map(fn($c) => [
                'Case ID' => $c['case_id'], 'Employee Name' => $c['employee'], 'Category' => $c['category'],
                'Created Date' => $this->dmy($c['created']),
                'Days Open' => $c['created'] ? Carbon::parse($c['created'])->diffInDays(Carbon::today()) : 'N/A',
                'Assigned Investigator' => $c['committee'],
            ])->values()->all();
        return ['columns' => ['Case ID', 'Employee Name', 'Category', 'Created Date', 'Days Open', 'Assigned Investigator'], 'rows' => $rows];
    }

    public function pendingCases(array $f): array
    {
        $rows = $this->allCases($f)->filter(fn($c) => strtolower($c['status']) === 'pending')->sortByDesc('created')
            ->map(fn($c) => [
                'Case ID' => $c['case_id'], 'Employee Name' => $c['employee'], 'Pending Stage' => $this->statusLabel($c['status']),
                'Assigned To' => $c['committee'],
                'Days Pending' => $c['created'] ? Carbon::parse($c['created'])->diffInDays(Carbon::today()) : 'N/A',
            ])->values()->all();
        return ['columns' => ['Case ID', 'Employee Name', 'Pending Stage', 'Assigned To', 'Days Pending'], 'rows' => $rows];
    }

    public function closedCases(array $f): array
    {
        // Duration applies to the resolution date here, so bypass allCases()'s created-date filter.
        $rows = $this->allCases(['case_type' => $f['case_type'] ?? null, 'department' => $f['department'] ?? null, 'employee' => $f['employee'] ?? null, 'status' => $f['status'] ?? null])->reject(fn($c) => $this->isOpen($c['status']))
            ->when($f['from_date'], fn($col) => $col->filter(fn($c) => $c['resolved'] && substr($c['resolved'], 0, 10) >= $f['from_date']))
            ->when($f['to_date'], fn($col) => $col->filter(fn($c) => $c['resolved'] && substr($c['resolved'], 0, 10) <= $f['to_date']))
            ->sortByDesc('resolved')
            ->map(fn($c) => [
                'Case ID' => $c['case_id'], 'Employee Name' => $c['employee'], 'Resolution Date' => $this->dmy($c['resolved']),
                'Outcome' => $this->statusLabel($c['status']),
                'Resolution Time' => ($d = $this->resolutionDays($c['created'], $c['resolved'])) !== null ? $d . ' day(s)' : 'N/A',
            ])->values()->all();
        return ['columns' => ['Case ID', 'Employee Name', 'Resolution Date', 'Outcome', 'Resolution Time'], 'rows' => $rows];
    }

    public function confidentialCases(array $f): array
    {
        $rows = $this->grievanceQuery($f)->whereNotNull('g.Request_Identity_Disclosure')->where('g.Request_Identity_Disclosure', '<>', '')
            ->when(true, fn($q) => $this->applyDuration($q, $f, 'g.created_at'))
            ->orderByDesc('g.created_at')->get(['g.id', 'g.Grivance_id', 'g.Request_Identity_Disclosure', 'g.status', DB::raw($this->nm)])
            ->map(fn($r) => [
                'Case ID' => $r->Grivance_id ?: ('GRV-' . $r->id), 'Employee Name' => trim($r->employee_name) ?: 'N/A',
                'Confidential Level' => (string) $r->Request_Identity_Disclosure, 'Current Status' => $this->statusLabel($r->status),
            ])->all();
        return ['columns' => ['Case ID', 'Employee Name', 'Confidential Level', 'Current Status'], 'rows' => $rows];
    }

    public function delegatedCases(array $f): array
    {
        $rows = $this->allCases($f)->filter(fn($c) => $c['committee'] && $c['committee'] !== 'N/A')->sortByDesc('created')
            ->map(fn($c) => [
                'Case ID' => $c['case_id'], 'Employee Name' => $c['employee'], 'Assigned Committee' => $c['committee'],
                'Assigned Investigator' => $c['committee'], 'Assignment Date' => $this->dmy($c['created']),
            ])->values()->all();
        return ['columns' => ['Case ID', 'Employee Name', 'Assigned Committee', 'Assigned Investigator', 'Assignment Date'], 'rows' => $rows];
    }

    public function pendingApproval(array $f): array
    {
        $rows = $this->allCases($f)->filter(fn($c) => strtolower($c['status']) === 'in_review')->sortByDesc('created')
            ->map(fn($c) => [
                'Case ID' => $c['case_id'], 'Employee Name' => $c['employee'], 'Current Approver' => $c['committee'],
                'Pending Since' => $this->dmy($c['created']),
            ])->values()->all();
        return ['columns' => ['Case ID', 'Employee Name', 'Current Approver', 'Pending Since'], 'rows' => $rows];
    }

    public function caseTimeline(array $f): array
    {
        $rows = $this->allCases($f)->sortByDesc('created')->map(function ($c) {
            $target = $c['created'] ? Carbon::parse($c['created'])->addDays(30) : null; // no SLA target stored; 30-day convention
            $remaining = $target ? Carbon::today()->diffInDays($target, false) : null;
            return [
                'Case ID' => $c['case_id'], 'Start Date' => $this->dmy($c['created']),
                'Target Resolution Date' => $target ? $target->format('d M Y') : 'N/A',
                'Days Remaining' => $remaining === null ? 'N/A' : $remaining . ' day(s)',
                'Timeline Status' => !$this->isOpen($c['status']) ? 'Closed' : ($remaining !== null && $remaining < 0 ? 'Overdue' : 'On Track'),
            ];
        })->values()->all();
        return ['columns' => ['Case ID', 'Start Date', 'Target Resolution Date', 'Days Remaining', 'Timeline Status'], 'rows' => $rows];
    }

    public function slaCompliance(array $f): array
    {
        $rows = $this->allCases($f)->reject(fn($c) => $this->isOpen($c['status']))->sortByDesc('resolved')->map(function ($c) {
            $target = $c['created'] ? Carbon::parse($c['created'])->addDays(30) : null;
            $met = $target && $c['resolved'] && Carbon::parse($c['resolved'])->lte($target);
            return [
                'Case ID' => $c['case_id'], 'Target Resolution Date' => $target ? $target->format('d M Y') : 'N/A',
                'Actual Resolution Date' => $this->dmy($c['resolved']),
                'SLA Status' => $c['resolved'] ? ($met ? 'Met' : 'Breached') : 'N/A',
            ];
        })->values()->all();
        return ['columns' => ['Case ID', 'Target Resolution Date', 'Actual Resolution Date', 'SLA Status'], 'rows' => $rows];
    }

    public function overdueCases(array $f): array
    {
        $rows = $this->allCases($f)->filter(fn($c) => $this->isOpen($c['status']) && $c['created'] && Carbon::parse($c['created'])->addDays(30)->isPast())
            ->map(fn($c) => [
                'Case ID' => $c['case_id'], 'Employee Name' => $c['employee'],
                'Days Overdue' => Carbon::parse($c['created'])->addDays(30)->diffInDays(Carbon::today()),
                'Assigned Investigator' => $c['committee'],
            ])->values()->all();
        return ['columns' => ['Case ID', 'Employee Name', 'Days Overdue', 'Assigned Investigator'], 'rows' => $rows];
    }

    public function expiredOffense(array $f): array
    {
        $rows = $this->disciplinaryQuery($f)->whereNotNull('ds.Expiry_date')
            ->when(true, fn($q) => $this->applyDuration($q, $f, 'ds.Expiry_date'))
            ->orderByDesc('ds.Expiry_date')->get([DB::raw($this->nm), 'of.OffensesName', 'ac.ActionName', 'ds.Expiry_date'])
            ->map(fn($r) => [
                'Employee Name' => trim($r->employee_name) ?: 'N/A', 'Offense' => $r->OffensesName ?? 'N/A',
                'Action Type' => $r->ActionName ?? 'N/A', 'Expiry Date' => $this->dmy($r->Expiry_date),
            ])->all();
        return ['columns' => ['Employee Name', 'Offense', 'Action Type', 'Expiry Date'], 'rows' => $rows];
    }

    public function grievanceCategory(array $f): array
    {
        $raw = $this->grievanceQuery($f)->when(true, fn($q) => $this->applyDuration($q, $f, 'g.created_at'))->groupBy('gc.Category_Name')->select('gc.Category_Name', DB::raw('COUNT(*) c'))->get();
        $total = $raw->sum('c') ?: 1;
        return ['columns' => ['Category', 'Total Cases', 'Percentage'], 'rows' => $raw->map(fn($r) => ['Category' => $r->Category_Name ?? 'N/A', 'Total Cases' => (int) $r->c, 'Percentage' => round($r->c / $total * 100, 1) . '%'])->all()];
    }

    public function disciplinaryCategory(array $f): array
    {
        $raw = $this->disciplinaryQuery($f)->when(true, fn($q) => $this->applyDuration($q, $f, 'ds.created_at'))->groupBy('dc.DisciplinaryCategoryName')->select('dc.DisciplinaryCategoryName', DB::raw('COUNT(*) c'))->get();
        $total = $raw->sum('c') ?: 1;
        return ['columns' => ['Offense Category', 'Number of Cases', 'Percentage'], 'rows' => $raw->map(fn($r) => ['Offense Category' => $r->DisciplinaryCategoryName ?? 'N/A', 'Number of Cases' => (int) $r->c, 'Percentage' => round($r->c / $total * 100, 1) . '%'])->all()];
    }

    public function departmentAnalysis(array $f): array
    {
        $cases = $this->allCases($f)->groupBy('dept');
        $rows = $cases->map(fn($items, $dept) => [
            'Department' => $dept ?: 'N/A',
            'Grievance Cases' => $items->where('type', 'Grievance')->count(),
            'Disciplinary Cases' => $items->where('type', 'Disciplinary')->count(),
            'Total Cases' => $items->count(),
        ])->values()->all();
        return ['columns' => ['Department', 'Grievance Cases', 'Disciplinary Cases', 'Total Cases'], 'rows' => $rows];
    }

    public function employeeHistory(array $f): array
    {
        $rows = $this->allCases($f)->sortByDesc('created')->map(fn($c) => [
            'Employee Name' => $c['employee'], 'Case ID' => $c['case_id'], 'Case Type' => $c['type'],
            'Category' => $c['category'], 'Outcome' => $this->statusLabel($c['status']), 'Resolution Date' => $this->dmy($c['resolved']),
        ])->values()->all();
        return ['columns' => ['Employee Name', 'Case ID', 'Case Type', 'Category', 'Outcome', 'Resolution Date'], 'rows' => $rows];
    }

    public function investigationProgress(array $f): array
    {
        $rid = $this->resort->resort_id;
        $rows = collect();
        if (($f['case_type'] ?? null) !== 'Disciplinary') {
            $g = DB::table('grivance_investigation_child_models as c')
                ->join('grivance_investigation_models as ip', 'ip.id', '=', 'c.investigation_p_id')
                ->join('grivance_submission_models as g', 'g.id', '=', 'ip.Grievance_s_id')
                ->where('g.resort_id', $rid)->when(true, fn($q) => $this->applyDuration($q, $f, 'g.created_at'))->get(['g.Grivance_id', 'c.investigation_stage', 'c.resolution_date']);
            foreach ($g as $r) $rows->push(['Case ID' => $r->Grivance_id, 'Investigator' => 'Committee', 'Current Stage' => $r->investigation_stage ?? 'N/A', 'Completion Percentage' => $r->resolution_date ? '100%' : 'In Progress']);
        }
        if (($f['case_type'] ?? null) !== 'Grievance') {
            $d = DB::table('disciplinary_investigation_children as c')
                ->join('disciplinary_investigation_parents as ip', 'ip.id', '=', 'c.Disciplinary_P_id')
                ->join('disciplinary_submits as ds', 'ds.id', '=', 'ip.Disciplinary_id')
                ->where('ds.resort_id', $rid)->when(true, fn($q) => $this->applyDuration($q, $f, 'ds.created_at'))->get(['ds.Disciplinary_id', 'c.investigation_stage', 'ip.resolution_date']);
            foreach ($d as $r) $rows->push(['Case ID' => $r->Disciplinary_id, 'Investigator' => 'Committee', 'Current Stage' => $r->investigation_stage ?? 'N/A', 'Completion Percentage' => $r->resolution_date ? '100%' : 'In Progress']);
        }
        return ['columns' => ['Case ID', 'Investigator', 'Current Stage', 'Completion Percentage'], 'rows' => $rows->all()];
    }

    public function investigationOutcome(array $f): array
    {
        $rid = $this->resort->resort_id;
        $rows = DB::table('grivance_investigation_child_models as c')
            ->join('grivance_investigation_models as ip', 'ip.id', '=', 'c.investigation_p_id')
            ->join('grivance_submission_models as g', 'g.id', '=', 'ip.Grievance_s_id')
            ->where('g.resort_id', $rid)->whereNotNull('c.resolution_date')
            ->when(true, fn($q) => $this->applyDuration($q, $f, 'c.resolution_date'))
            ->orderByDesc('c.resolution_date')
            ->get(['g.Grivance_id', 'c.inves_find_recommendations', 'c.resolution_note'])
            ->map(fn($r) => [
                'Case ID' => $r->Grivance_id, 'Investigation Result' => $r->inves_find_recommendations ?? 'N/A',
                'Final Decision' => 'N/A', 'Resolution Notes' => $r->resolution_note ?? 'N/A',
            ])->all();
        return ['columns' => ['Case ID', 'Investigation Result', 'Final Decision', 'Resolution Notes'], 'rows' => $rows];
    }

    public function followupAction(array $f): array
    {
        $rid = $this->resort->resort_id;
        $rows = collect();
        if (($f['case_type'] ?? null) !== 'Disciplinary') {
            $g = DB::table('grivance_investigation_child_models as c')->join('grivance_investigation_models as ip', 'ip.id', '=', 'c.investigation_p_id')->join('grivance_submission_models as g', 'g.id', '=', 'ip.Grievance_s_id')->where('g.resort_id', $rid)->whereNotNull('c.follow_up_action')->when(true, fn($q) => $this->applyDuration($q, $f, 'g.created_at'))->get(['g.Grivance_id', 'c.follow_up_action', 'c.follow_up_description', 'c.resolution_date']);
            foreach ($g as $r) $rows->push(['Case ID' => $r->Grivance_id, 'Action Description' => $r->follow_up_action ?? ($r->follow_up_description ?? 'N/A'), 'Responsible Person' => 'Committee', 'Due Date' => 'N/A', 'Status' => $r->resolution_date ? 'Completed' : 'Open']);
        }
        if (($f['case_type'] ?? null) !== 'Grievance') {
            $d = DB::table('disciplinary_investigation_children as c')->join('disciplinary_investigation_parents as ip', 'ip.id', '=', 'c.Disciplinary_P_id')->join('disciplinary_submits as ds', 'ds.id', '=', 'ip.Disciplinary_id')->where('ds.resort_id', $rid)->whereNotNull('c.follow_up_action')->when(true, fn($q) => $this->applyDuration($q, $f, 'ds.created_at'))->get(['ds.Disciplinary_id', 'c.follow_up_action', 'c.follow_up_description', 'ip.resolution_date']);
            foreach ($d as $r) $rows->push(['Case ID' => $r->Disciplinary_id, 'Action Description' => $r->follow_up_action ?? ($r->follow_up_description ?? 'N/A'), 'Responsible Person' => 'Committee', 'Due Date' => 'N/A', 'Status' => $r->resolution_date ? 'Completed' : 'Open']);
        }
        return ['columns' => ['Case ID', 'Action Description', 'Responsible Person', 'Due Date', 'Status'], 'rows' => $rows->all()];
    }

    public function resolutionSummary(array $f): array
    {
        // Duration applies to the resolution date here, so bypass allCases()'s created-date filter.
        $rows = $this->allCases(['case_type' => $f['case_type'] ?? null, 'department' => $f['department'] ?? null, 'employee' => $f['employee'] ?? null, 'status' => $f['status'] ?? null])->reject(fn($c) => $this->isOpen($c['status']))
            ->when($f['from_date'], fn($col) => $col->filter(fn($c) => $c['resolved'] && substr($c['resolved'], 0, 10) >= $f['from_date']))
            ->when($f['to_date'], fn($col) => $col->filter(fn($c) => $c['resolved'] && substr($c['resolved'], 0, 10) <= $f['to_date']))
            ->sortByDesc('resolved')->map(fn($c) => [
                'Case ID' => $c['case_id'], 'Resolution Type' => $this->statusLabel($c['status']),
                'Resolution Notes' => 'N/A', 'Resolution Date' => $this->dmy($c['resolved']),
            ])->values()->all();
        return ['columns' => ['Case ID', 'Resolution Type', 'Resolution Notes', 'Resolution Date'], 'rows' => $rows];
    }

    public function committeeWorkload(array $f): array
    {
        $cases = $this->allCases($f)->groupBy('committee');
        $rows = $cases->map(fn($items, $committee) => [
            'Committee Name' => $committee ?: 'Unassigned',
            'Assigned Cases' => $items->count(),
            'Open Cases' => $items->filter(fn($c) => $this->isOpen($c['status']))->count(),
            'Closed Cases' => $items->reject(fn($c) => $this->isOpen($c['status']))->count(),
        ])->values()->all();
        return ['columns' => ['Committee Name', 'Assigned Cases', 'Open Cases', 'Closed Cases'], 'rows' => $rows];
    }

    public function investigatorPerformance(array $f): array
    {
        $cases = $this->allCases($f)->groupBy('committee');
        $rows = $cases->map(function ($items, $committee) {
            $times = $items->map(fn($c) => $this->resolutionDays($c['created'], $c['resolved']))->filter(fn($v) => $v !== null);
            return [
                'Investigator Name' => $committee ?: 'Unassigned',
                'Assigned Cases' => $items->count(),
                'Closed Cases' => $items->reject(fn($c) => $this->isOpen($c['status']))->count(),
                'Average Resolution Time' => $times->count() ? round($times->avg(), 1) . ' day(s)' : 'N/A',
            ];
        })->values()->all();
        return ['columns' => ['Investigator Name', 'Assigned Cases', 'Closed Cases', 'Average Resolution Time'], 'rows' => $rows];
    }

    public function caseTrend(array $f): array
    {
        $year = $f['year'] ?: date('Y');
        $cases = $this->allCases([]);
        $rows = [];
        for ($m = 1; $m <= 12; $m++) {
            $newC = $cases->filter(fn($c) => $c['created'] && Carbon::parse($c['created'])->year == $year && Carbon::parse($c['created'])->month == $m)->count();
            $closedC = $cases->filter(fn($c) => $c['resolved'] && Carbon::parse($c['resolved'])->year == $year && Carbon::parse($c['resolved'])->month == $m)->count();
            if ($newC == 0 && $closedC == 0) continue;
            $rows[] = ['Month' => Carbon::create()->month($m)->format('F'), 'New Cases' => $newC, 'Closed Cases' => $closedC, 'Outstanding Cases' => max(0, $newC - $closedC)];
        }
        return ['columns' => ['Month', 'New Cases', 'Closed Cases', 'Outstanding Cases'], 'rows' => $rows];
    }

    public function repeatOffender(array $f): array
    {
        $rows = $this->disciplinaryQuery($f)->when(true, fn($q) => $this->applyDuration($q, $f, 'ds.created_at'))->select('ds.Employee_id', DB::raw('COUNT(*) c'), DB::raw('MAX(of.OffensesName) latest_offense'), DB::raw('MAX(ds.status) latest_status'), DB::raw($this->nm))
            ->groupBy('ds.Employee_id', 'ra.first_name', 'ra.last_name')->having('c', '>', 1)->orderByDesc('c')->get()
            ->map(fn($r) => [
                'Employee Name' => trim($r->employee_name) ?: 'N/A', 'Number of Cases' => (int) $r->c,
                'Latest Offense' => $r->latest_offense ?? 'N/A', 'Latest Outcome' => $this->statusLabel($r->latest_status),
            ])->all();
        return ['columns' => ['Employee Name', 'Number of Cases', 'Latest Offense', 'Latest Outcome'], 'rows' => $rows];
    }

    public function offenseFrequency(array $f): array
    {
        $raw = $this->disciplinaryQuery($f)->when(true, fn($q) => $this->applyDuration($q, $f, 'ds.created_at'))->groupBy('of.OffensesName')->select('of.OffensesName', DB::raw('COUNT(*) c'))->orderByDesc(DB::raw('COUNT(*)'))->get();
        $total = $raw->sum('c') ?: 1;
        return ['columns' => ['Offense', 'Number of Occurrences', 'Percentage'], 'rows' => $raw->map(fn($r) => ['Offense' => $r->OffensesName ?? 'N/A', 'Number of Occurrences' => (int) $r->c, 'Percentage' => round($r->c / $total * 100, 1) . '%'])->all()];
    }

    public function codeOfConduct(array $f): array
    {
        // No code-of-conduct acknowledgement table in the schema.
        return ['columns' => ['Employee Name', 'Code Version', 'Acknowledgment Date', 'Status'], 'rows' => []];
    }

    public function letterRegister(array $f): array
    {
        // Only letter TEMPLATES are stored; issued letters per case are not tracked.
        $rid = $this->resort->resort_id;
        $rows = collect();
        foreach (DB::table('grievance_templete_models')->where('resort_id', $rid)->when(true, fn($q) => $this->applyDuration($q, $f, 'created_at'))->get(['Grievance_Temp_name', 'created_at']) as $r) {
            $rows->push(['Letter Name' => $r->Grievance_Temp_name, 'Employee Name' => 'N/A', 'Issue Date' => $this->dmy($r->created_at), 'Status' => 'Template']);
        }
        foreach (DB::table('disciplinery_latter_templetes')->where('resort_id', $rid)->when(true, fn($q) => $this->applyDuration($q, $f, 'created_at'))->get(['Latter_Temp_name', 'created_at']) as $r) {
            $rows->push(['Letter Name' => $r->Latter_Temp_name, 'Employee Name' => 'N/A', 'Issue Date' => $this->dmy($r->created_at), 'Status' => 'Template']);
        }
        return ['columns' => ['Letter Name', 'Employee Name', 'Issue Date', 'Status'], 'rows' => $rows->all()];
    }

    public function documentAudit(array $f): array
    {
        // Duration on the case-created (upload) date is applied centrally in allCases().
        $rows = $this->allCases($f)
            ->sortByDesc('created')->map(fn($c) => [
                'Case ID' => $c['case_id'], 'Document Type' => $c['type'] . ' attachment',
                'Uploaded By' => $c['employee'], 'Upload Date' => $this->dmy($c['created']),
            ])->values()->all();
        return ['columns' => ['Case ID', 'Document Type', 'Uploaded By', 'Upload Date'], 'rows' => $rows];
    }
}
