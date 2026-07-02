<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Resorts\Concerns\PredefinedReportActions;
use App\Helpers\Common;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Predefined Incident Management reports (Option B), generic view.
 * Department is derived from the reporter's department (incidents have no Dept_id).
 * No root-cause / incident-audit / per-action-deadline tables -> those are N/A
 * (root cause uses investigation findings); SLA uses incident_resolution_timeline.
 */
class IncidentReportController extends Controller
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
            'exec_summary'      => ['name' => 'Incident Executive Summary', 'description' => 'Incident overview and resolution.', 'filters' => [], 'handler' => 'execSummary'],
            'register'          => ['name' => 'Incident Register', 'description' => 'All incidents.', 'filters' => ['duration', 'status', 'category'], 'handler' => 'register'],
            'open'              => ['name' => 'Open Incident Report', 'description' => 'Incidents still open.', 'filters' => ['priority', 'department'], 'handler' => 'open'],
            'closed'            => ['name' => 'Closed Incident Report', 'description' => 'Closed incidents.', 'filters' => ['duration'], 'handler' => 'closed'],
            'status'            => ['name' => 'Incident Status Report', 'description' => 'Counts by workflow stage.', 'filters' => ['duration'], 'handler' => 'statusReport'],
            'severity'          => ['name' => 'Incident Severity Report', 'description' => 'Incidents by severity.', 'filters' => ['duration'], 'handler' => 'severityReport'],
            'category_analysis' => ['name' => 'Incident Category Analysis', 'description' => 'Incidents by category.', 'filters' => ['duration'], 'handler' => 'categoryAnalysis'],
            'trend'             => ['name' => 'Incident Trend Report', 'description' => 'Monthly incident trend.', 'filters' => ['year'], 'handler' => 'trend'],
            'department_summary' => ['name' => 'Department Incident Summary', 'description' => 'Incidents per reporter department.', 'filters' => ['department', 'duration'], 'handler' => 'departmentSummary'],
            'location_report'   => ['name' => 'Location Incident Report', 'description' => 'Incidents per location.', 'filters' => [], 'handler' => 'locationReport'],
            'employee_involvement' => ['name' => 'Employee Involvement Report', 'description' => 'Employees linked to incidents.', 'filters' => ['department'], 'handler' => 'employeeInvolvement'],
            'witness_register'  => ['name' => 'Witness Register', 'description' => 'Incident witnesses.', 'filters' => ['duration'], 'handler' => 'witnessRegister'],
            'investigation_progress' => ['name' => 'Investigation Progress Report', 'description' => 'Investigations in progress.', 'filters' => [], 'handler' => 'investigationProgress'],
            'investigation_timeline' => ['name' => 'Investigation Timeline Report', 'description' => 'Investigation start vs resolution.', 'filters' => ['duration'], 'handler' => 'investigationTimeline'],
            'resolution_time'   => ['name' => 'Resolution Time Report', 'description' => 'Time taken to resolve.', 'filters' => ['duration'], 'handler' => 'resolutionTime'],
            'sla_compliance'    => ['name' => 'SLA Compliance Report', 'description' => 'Resolution vs SLA target.', 'filters' => ['duration'], 'handler' => 'slaCompliance'],
            'nearing_deadline'  => ['name' => 'Cases Nearing Deadline Report', 'description' => 'Open incidents nearing deadline.', 'filters' => [], 'handler' => 'nearingDeadline'],
            'sla_breach'        => ['name' => 'SLA Breach Report', 'description' => 'Incidents that breached SLA.', 'filters' => ['duration'], 'handler' => 'slaBreach'],
            'committee_assignment' => ['name' => 'Committee Assignment Report', 'description' => 'Incidents per committee.', 'filters' => [], 'handler' => 'committeeAssignment'],
            'committee_performance' => ['name' => 'Committee Performance Report', 'description' => 'Committee resolution performance.', 'filters' => [], 'handler' => 'committeePerformance'],
            'meeting_schedule'  => ['name' => 'Incident Meeting Schedule', 'description' => 'Scheduled investigation meetings.', 'filters' => ['duration'], 'handler' => 'meetingSchedule'],
            'calendar'          => ['name' => 'Incident Calendar Report', 'description' => 'Meetings by month.', 'filters' => ['month', 'year'], 'handler' => 'calendar'],
            'attendance'        => ['name' => 'Investigation Attendance Report', 'description' => 'Meeting participants.', 'filters' => ['duration'], 'handler' => 'attendance'],
            'corrective_action' => ['name' => 'Corrective Action Report', 'description' => 'Actions taken on incidents.', 'filters' => [], 'handler' => 'correctiveAction'],
            'preventive_action' => ['name' => 'Preventive Action Report', 'description' => 'Preventive measures.', 'filters' => [], 'handler' => 'preventiveAction'],
            'pending_approval'  => ['name' => 'Pending Resolution Approval Report', 'description' => 'Incidents awaiting approval.', 'filters' => [], 'handler' => 'pendingApproval'],
            'root_cause'        => ['name' => 'Root Cause Analysis Report', 'description' => 'Investigation findings (root cause).', 'filters' => ['duration'], 'handler' => 'rootCause'],
            'recurrence'        => ['name' => 'Incident Recurrence Report', 'description' => 'Recurring incident categories.', 'filters' => [], 'handler' => 'recurrence'],
            'high_risk'         => ['name' => 'High Risk Incident Report', 'description' => 'Critical / high-severity incidents.', 'filters' => ['duration'], 'handler' => 'highRisk'],
            'audit_trail'       => ['name' => 'Incident Audit Trail', 'description' => 'Incident activity timeline.', 'filters' => ['duration'], 'handler' => 'auditTrail'],
            'exec_dashboard'    => ['name' => 'Incident Executive Dashboard Report', 'description' => 'Headline incident metrics.', 'filters' => [], 'handler' => 'execDashboard'],
        ];
    }

    public function index()
    {
        if (Common::checkRouteWisePermission('resort.report.index', config('settings.resort_permissions.view')) == false) return abort(403, 'Unauthorized access');
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();
        $reports = collect($this->registry())->map(fn($r, $key) => [
            'key' => $key, 'name' => $r['name'], 'description' => $r['description'],
            // Every report exposes the duration (From/To date) filter, like the Custom Report.
            'filters' => array_values(array_unique(array_merge($r['filters'], ['duration']))),
        ])->values();

        $departments = DB::table('resort_departments')->where('resort_id', $rid)->when($scoped !== null, fn($q) => $q->whereIn('id', $scoped))->orderBy('name')->get(['id', 'name']);
        $categories = DB::table('incident_categories')->where('resort_id', $rid)->orderBy('category_name')->get(['id', 'category_name']);
        $statuses = DB::table('incidents')->where('resort_id', $rid)->whereNotNull('status')->distinct()->pluck('status')->filter()->values();
        $opt = fn($coll, $v, $l) => $coll->map(fn($x) => ['value' => $x->$v, 'label' => $x->$l])->all();

        $filterDefs = [
            ['filter' => 'department', 'name' => 'department', 'label' => 'Department', 'type' => 'select', 'placeholder' => 'All departments', 'options' => $opt($departments, 'id', 'name')],
            ['filter' => 'category', 'name' => 'category', 'label' => 'Category', 'type' => 'select', 'placeholder' => 'All categories', 'options' => $opt($categories, 'id', 'category_name')],
            ['filter' => 'status', 'name' => 'status', 'label' => 'Status', 'type' => 'select', 'placeholder' => 'All statuses', 'options' => $statuses->map(fn($s) => ['value' => $s, 'label' => $s])->all()],
            ['filter' => 'priority', 'name' => 'priority', 'label' => 'Priority', 'type' => 'select', 'placeholder' => 'All priorities', 'options' => collect(['Low', 'Medium', 'High'])->map(fn($s) => ['value' => $s, 'label' => $s])->all()],
            ['filter' => 'month', 'name' => 'month', 'label' => 'Month', 'type' => 'select', 'placeholder' => 'All months', 'options' => collect(range(1, 12))->map(fn($m) => ['value' => $m, 'label' => Carbon::create()->month($m)->format('F')])->all()],
            ['filter' => 'year', 'name' => 'year', 'label' => 'Year', 'type' => 'select', 'options' => collect(range((int) date('Y'), (int) date('Y') - 4))->map(fn($y) => ['value' => $y, 'label' => $y])->all()],
            ['filter' => 'duration', 'name' => 'from_date', 'label' => 'From Date', 'type' => 'date'],
            ['filter' => 'duration', 'name' => 'to_date', 'label' => 'To Date', 'type' => 'date'],
        ];

        return view('resorts.reports.module_report', [
            'page_title' => 'Incident Management Reports', 'reports' => $reports, 'filterDefs' => $filterDefs,
            'runRoute' => 'resort.report.incident.run', 'exportRoute' => 'resort.report.incident.export', 'insightsRoute' => 'resort.report.incident.insights',
        ]);
    }

    private function filtersFrom(Request $request): array
    {
        return collect(['department', 'category', 'status', 'priority', 'month', 'year', 'from_date', 'to_date'])
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
    private function isClosed($s): bool { return stripos((string) $s, 'clos') !== false || stripos((string) $s, 'resolv') !== false; }
    private function slaDays($priority): int
    {
        $t = DB::table('incident_resolution_timeline')->where('resort_id', $this->resort->resort_id)->where('priority', $priority)->value('timeline');
        if (!$t) return 7;
        return (int) preg_replace('/\D/', '', (string) $t) ?: 7;
    }

    /** incidents joined to category + reporter (name + dept). */
    private function base(array $f)
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();
        return DB::table('incidents as i')
            ->leftJoin('incident_categories as ic', 'ic.id', '=', 'i.category')
            ->leftJoin('employees as e', 'e.id', '=', 'i.reporter_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->leftJoin('resort_departments as d', 'd.id', '=', 'e.Dept_id')
            ->where('i.resort_id', $rid)
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))
            ->when($f['department'] ?? null, fn($q) => $q->where('e.Dept_id', $f['department']))
            ->when($f['category'] ?? null, fn($q) => $q->where('i.category', $f['category']))
            ->when($f['status'] ?? null, fn($q) => $q->where('i.status', $f['status']))
            ->when($f['priority'] ?? null, fn($q) => $q->where('i.priority', $f['priority']))
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('i.incident_date', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('i.incident_date', '<=', $f['to_date']));
    }

    private $nm = "TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as reporter_name";

    /* reports */

    public function execSummary(array $f): array
    {
        $rid = $this->resort->resort_id;
        $b = DB::table('incidents')->where('resort_id', $rid);
        $this->applyDuration($b, $f, 'incident_date');
        $total = (clone $b)->count();
        $closed = (clone $b)->where(fn($q) => $q->where('status', 'like', '%clos%')->orWhere('status', 'like', '%resolv%'))->count();
        $invest = (clone $b)->where('status', 'like', '%investigat%')->count();
        $open = $total - $closed;
        $times = (clone $b)->whereNotNull('resolved_at')->get(['incident_date', 'resolved_at'])->map(fn($r) => Carbon::parse($r->incident_date)->diffInDays(Carbon::parse($r->resolved_at)))->filter();
        return ['columns' => ['Total Incidents', 'Open Incidents', 'Under Investigation', 'Closed Incidents', 'Average Resolution Time'],
            'rows' => [['Total Incidents' => $total, 'Open Incidents' => $open, 'Under Investigation' => $invest, 'Closed Incidents' => $closed, 'Average Resolution Time' => $times->count() ? round($times->avg(), 1) . ' day(s)' : 'N/A']]];
    }

    public function register(array $f): array
    {
        $rows = $this->base($f)->orderByDesc('i.incident_date')
            ->get(['i.incident_id', 'i.incident_name', 'ic.category_name', 'i.location', 'i.incident_date', 'i.incident_time', 'i.status', DB::raw($this->nm)])
            ->map(fn($r) => [
                'Incident ID' => $r->incident_id ?? 'N/A', 'Incident Title' => $r->incident_name ?? 'N/A',
                'Category' => $r->category_name ?? 'N/A', 'Location' => $r->location ?? 'N/A',
                'Incident Date' => $this->dmy($r->incident_date), 'Incident Time' => $r->incident_time ?? 'N/A',
                'Reported By' => trim($r->reporter_name) ?: 'N/A', 'Status' => $r->status ?? 'N/A',
            ])->all();
        return ['columns' => ['Incident ID', 'Incident Title', 'Category', 'Location', 'Incident Date', 'Incident Time', 'Reported By', 'Status'], 'rows' => $rows];
    }

    public function open(array $f): array
    {
        $rows = $this->base($f)->where(fn($q) => $q->where('i.status', 'not like', '%clos%')->where('i.status', 'not like', '%resolv%'))
            ->orderByDesc('i.incident_date')
            ->get(['i.id', 'i.incident_id', 'i.incident_name', 'i.location', 'i.incident_date'])
            ->map(function ($r) {
                $inv = DB::table('incidents_investigation')->where('incident_id', $r->id)->value('committee_id');
                $comm = $inv ? DB::table('incident_committee')->where('id', $inv)->value('commitee_name') : null;
                return [
                    'Incident ID' => $r->incident_id ?? 'N/A', 'Incident Title' => $r->incident_name ?? 'N/A',
                    'Location' => $r->location ?? 'N/A', 'Report Date' => $this->dmy($r->incident_date),
                    'Days Open' => $r->incident_date ? Carbon::parse($r->incident_date)->diffInDays(Carbon::today()) : 'N/A',
                    'Assigned Investigator' => $comm ?? 'Unassigned',
                ];
            })->all();
        return ['columns' => ['Incident ID', 'Incident Title', 'Location', 'Report Date', 'Days Open', 'Assigned Investigator'], 'rows' => $rows];
    }

    public function closed(array $f): array
    {
        $rows = $this->base($f)->whereNotNull('i.resolved_at')
            ->when($f['from_date'], fn($q) => $q->whereDate('i.resolved_at', '>=', $f['from_date']))
            ->when($f['to_date'], fn($q) => $q->whereDate('i.resolved_at', '<=', $f['to_date']))
            ->orderByDesc('i.resolved_at')
            ->get(['i.incident_id', 'i.resolved_at', 'i.incident_date', 'i.outcome_type'])
            ->map(fn($r) => [
                'Incident ID' => $r->incident_id ?? 'N/A', 'Resolution Date' => $this->dmy($r->resolved_at),
                'Resolution Time' => $r->resolved_at && $r->incident_date ? Carbon::parse($r->incident_date)->diffInDays(Carbon::parse($r->resolved_at)) . ' day(s)' : 'N/A',
                'Final Outcome' => $r->outcome_type ? 'Outcome #' . $r->outcome_type : 'N/A',
            ])->all();
        return ['columns' => ['Incident ID', 'Resolution Date', 'Resolution Time', 'Final Outcome'], 'rows' => $rows];
    }

    public function statusReport(array $f): array
    {
        $b = $this->base($f);
        $count = fn($kw) => (clone $b)->where('i.status', 'like', "%$kw%")->count();
        return ['columns' => ['Reported', 'Acknowledged', 'Under Investigation', 'Pending Approval', 'Closed'],
            'rows' => [['Reported' => $count('report'), 'Acknowledged' => $count('acknowledg'), 'Under Investigation' => $count('investigat'), 'Pending Approval' => $count('approv'), 'Closed' => $count('clos') + $count('resolv')]]];
    }

    public function severityReport(array $f): array
    {
        $rows = $this->base($f)->orderByDesc('i.incident_date')
            ->get(['i.incident_id', 'i.severity', 'ic.category_name', 'i.status'])
            ->map(fn($r) => ['Incident ID' => $r->incident_id ?? 'N/A', 'Severity' => $r->severity ?? 'N/A', 'Category' => $r->category_name ?? 'N/A', 'Status' => $r->status ?? 'N/A'])->all();
        return ['columns' => ['Incident ID', 'Severity', 'Category', 'Status'], 'rows' => $rows];
    }

    public function categoryAnalysis(array $f): array
    {
        $raw = $this->base($f)->groupBy('ic.category_name')->select('ic.category_name', DB::raw('COUNT(*) c'))->get();
        $total = $raw->sum('c') ?: 1;
        return ['columns' => ['Category', 'Number of Incidents', 'Percentage'], 'rows' => $raw->map(fn($r) => ['Category' => $r->category_name ?? 'N/A', 'Number of Incidents' => (int) $r->c, 'Percentage' => round($r->c / $total * 100, 1) . '%'])->all()];
    }

    public function trend(array $f): array
    {
        $rid = $this->resort->resort_id;
        $year = $f['year'] ?: date('Y');
        $rows = []; $prev = null;
        for ($m = 1; $m <= 12; $m++) {
            $c = DB::table('incidents')->where('resort_id', $rid)->whereRaw('YEAR(incident_date)=?', [$year])->whereRaw('MONTH(incident_date)=?', [$m])->count();
            if ($c == 0 && $prev === null) continue;
            $change = $prev === null ? 'N/A' : ($prev == 0 ? 'N/A' : round(($c - $prev) / $prev * 100, 1) . '%');
            $rows[] = ['Month' => Carbon::create()->month($m)->format('F'), 'Total Incidents' => $c, 'Increase/Decrease %' => $change, 'Trend' => $prev === null ? '—' : ($c > $prev ? 'Up' : ($c < $prev ? 'Down' : 'Flat'))];
            $prev = $c;
        }
        return ['columns' => ['Month', 'Total Incidents', 'Increase/Decrease %', 'Trend'], 'rows' => $rows];
    }

    public function departmentSummary(array $f): array
    {
        $rows = $this->base($f)->groupBy('d.name')
            ->select('d.name as dept', DB::raw('COUNT(*) total'), DB::raw("SUM(CASE WHEN i.status LIKE '%clos%' OR i.status LIKE '%resolv%' THEN 1 ELSE 0 END) closed"))
            ->orderByDesc(DB::raw('COUNT(*)'))->get()
            ->map(fn($r) => ['Department' => $r->dept ?? 'N/A', 'Total Incidents' => (int) $r->total, 'Open Cases' => (int) $r->total - (int) $r->closed, 'Closed Cases' => (int) $r->closed])->all();
        return ['columns' => ['Department', 'Total Incidents', 'Open Cases', 'Closed Cases'], 'rows' => $rows];
    }

    public function locationReport(array $f): array
    {
        $rows = $this->base($f)->groupBy('i.location')
            ->select('i.location', DB::raw('COUNT(*) total'), DB::raw("GROUP_CONCAT(DISTINCT i.severity) sevs"))
            ->orderByDesc(DB::raw('COUNT(*)'))->get()
            ->map(fn($r) => ['Location' => $r->location ?? 'N/A', 'Total Incidents' => (int) $r->total, 'Severity Distribution' => $r->sevs ?: 'N/A'])->all();
        return ['columns' => ['Location', 'Total Incidents', 'Severity Distribution'], 'rows' => $rows];
    }

    public function employeeInvolvement(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();
        $rows = DB::table('incidents_witness as w')
            ->join('incidents as i', 'i.id', '=', 'w.incident_id')
            ->leftJoin('employees as e', 'e.id', '=', 'w.witness_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->where('i.resort_id', $rid)
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))
            ->when($f['department'], fn($q) => $q->where('e.Dept_id', $f['department']))
            ->when(true, fn($q) => $this->applyDuration($q, $f, 'i.incident_date'))
            ->orderByDesc('i.incident_date')
            ->get(['i.incident_id', 'i.status', DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as emp_name")])
            ->map(fn($r) => ['Employee Name' => trim($r->emp_name) ?: 'N/A', 'Incident ID' => $r->incident_id ?? 'N/A', 'Role (Victim/Witness/Involved)' => 'Witness', 'Incident Status' => $r->status ?? 'N/A'])->all();
        return ['columns' => ['Employee Name', 'Incident ID', 'Role (Victim/Witness/Involved)', 'Incident Status'], 'rows' => $rows];
    }

    public function witnessRegister(array $f): array
    {
        $rid = $this->resort->resort_id;
        $rows = DB::table('incidents_witness as w')
            ->join('incidents as i', 'i.id', '=', 'w.incident_id')
            ->leftJoin('employees as e', 'e.id', '=', 'w.witness_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->where('i.resort_id', $rid)
            ->when($f['from_date'], fn($q) => $q->whereDate('i.incident_date', '>=', $f['from_date']))
            ->when($f['to_date'], fn($q) => $q->whereDate('i.incident_date', '<=', $f['to_date']))
            ->get(['i.incident_id', 'w.witness_status', DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as witness_name")])
            ->map(fn($r) => ['Incident ID' => $r->incident_id ?? 'N/A', 'Witness Name' => trim($r->witness_name) ?: 'N/A', 'Contact Details' => $r->witness_status ?? 'N/A'])->all();
        return ['columns' => ['Incident ID', 'Witness Name', 'Contact Details'], 'rows' => $rows];
    }

    private function investBase()
    {
        $rid = $this->resort->resort_id;
        return DB::table('incidents_investigation as ii')
            ->join('incidents as i', 'i.id', '=', 'ii.incident_id')
            ->leftJoin('incident_committee as c', 'c.id', '=', 'ii.committee_id')
            ->where('i.resort_id', $rid);
    }

    public function investigationProgress(array $f): array
    {
        $rows = $this->investBase()
            ->when(true, fn($q) => $this->applyDuration($q, $f, 'ii.start_date'))
            ->orderByDesc('ii.start_date')
            ->get(['i.incident_id', 'c.commitee_name', 'ii.start_date', 'ii.expected_resolution_date', 'i.resolved_at'])
            ->map(fn($r) => [
                'Incident ID' => $r->incident_id ?? 'N/A', 'Investigator' => $r->commitee_name ?? 'Committee',
                'Investigation Start Date' => $this->dmy($r->start_date), 'Current Stage' => $r->resolved_at ? 'Completed' : 'In Progress',
                'Expected Completion Date' => $this->dmy($r->expected_resolution_date),
            ])->all();
        return ['columns' => ['Incident ID', 'Investigator', 'Investigation Start Date', 'Current Stage', 'Expected Completion Date'], 'rows' => $rows];
    }

    public function investigationTimeline(array $f): array
    {
        $rows = $this->investBase()
            ->when($f['from_date'], fn($q) => $q->whereDate('ii.start_date', '>=', $f['from_date']))
            ->when($f['to_date'], fn($q) => $q->whereDate('ii.start_date', '<=', $f['to_date']))
            ->orderByDesc('ii.start_date')
            ->get(['i.incident_id', 'ii.start_date', 'i.resolved_at'])
            ->map(fn($r) => [
                'Incident ID' => $r->incident_id ?? 'N/A', 'Investigation Started' => $this->dmy($r->start_date),
                'Milestones Completed' => $r->resolved_at ? 'All' : 'In Progress', 'Resolution Date' => $this->dmy($r->resolved_at),
            ])->all();
        return ['columns' => ['Incident ID', 'Investigation Started', 'Milestones Completed', 'Resolution Date'], 'rows' => $rows];
    }

    public function resolutionTime(array $f): array
    {
        $rows = $this->base($f)->whereNotNull('i.resolved_at')
            ->when($f['from_date'], fn($q) => $q->whereDate('i.resolved_at', '>=', $f['from_date']))
            ->when($f['to_date'], fn($q) => $q->whereDate('i.resolved_at', '<=', $f['to_date']))
            ->orderByDesc('i.resolved_at')
            ->get(['i.incident_id', 'i.incident_date', 'i.resolved_at'])
            ->map(fn($r) => [
                'Incident ID' => $r->incident_id ?? 'N/A', 'Report Date' => $this->dmy($r->incident_date),
                'Resolution Date' => $this->dmy($r->resolved_at),
                'Resolution Time' => Carbon::parse($r->incident_date)->diffInDays(Carbon::parse($r->resolved_at)) . ' day(s)',
            ])->all();
        return ['columns' => ['Incident ID', 'Report Date', 'Resolution Date', 'Resolution Time'], 'rows' => $rows];
    }

    public function slaCompliance(array $f): array
    {
        $rows = $this->base($f)->whereNotNull('i.resolved_at')
            ->when($f['from_date'], fn($q) => $q->whereDate('i.resolved_at', '>=', $f['from_date']))
            ->when($f['to_date'], fn($q) => $q->whereDate('i.resolved_at', '<=', $f['to_date']))
            ->orderByDesc('i.resolved_at')->get(['i.incident_id', 'i.incident_date', 'i.resolved_at', 'i.priority'])
            ->map(function ($r) {
                $sla = $this->slaDays($r->priority);
                $taken = Carbon::parse($r->incident_date)->diffInDays(Carbon::parse($r->resolved_at));
                return ['Incident ID' => $r->incident_id ?? 'N/A', 'SLA Target' => $sla . ' day(s)', 'Resolution Time' => $taken . ' day(s)', 'SLA Status' => $taken <= $sla ? 'Met' : 'Breached'];
            })->all();
        return ['columns' => ['Incident ID', 'SLA Target', 'Resolution Time', 'SLA Status'], 'rows' => $rows];
    }

    public function nearingDeadline(array $f): array
    {
        $rows = $this->base($f)->whereNull('i.resolved_at')
            ->orderBy('i.incident_date')->get(['i.id', 'i.incident_id', 'i.incident_date', 'i.priority'])
            ->map(function ($r) {
                $deadline = Carbon::parse($r->incident_date)->addDays($this->slaDays($r->priority));
                $inv = DB::table('incidents_investigation')->where('incident_id', $r->id)->value('committee_id');
                $comm = $inv ? DB::table('incident_committee')->where('id', $inv)->value('commitee_name') : null;
                return ['Incident ID' => $r->incident_id ?? 'N/A', 'Assigned Investigator' => $comm ?? 'Unassigned', 'Deadline Date' => $deadline->format('d M Y'), 'Days Remaining' => Carbon::today()->diffInDays($deadline, false) . ' day(s)', '_d' => Carbon::today()->diffInDays($deadline, false)];
            })->filter(fn($r) => $r['_d'] >= 0 && $r['_d'] <= 14)->map(fn($r) => collect($r)->except('_d')->all())->values()->all();
        return ['columns' => ['Incident ID', 'Assigned Investigator', 'Deadline Date', 'Days Remaining'], 'rows' => $rows];
    }

    public function slaBreach(array $f): array
    {
        $rows = $this->base($f)
            ->when($f['from_date'], fn($q) => $q->whereDate('i.incident_date', '>=', $f['from_date']))
            ->when($f['to_date'], fn($q) => $q->whereDate('i.incident_date', '<=', $f['to_date']))
            ->get(['i.id', 'i.incident_id', 'i.incident_date', 'i.resolved_at', 'i.priority'])
            ->map(function ($r) {
                $sla = $this->slaDays($r->priority);
                $deadline = Carbon::parse($r->incident_date)->addDays($sla);
                $end = $r->resolved_at ? Carbon::parse($r->resolved_at) : Carbon::today();
                $over = $deadline->diffInDays($end, false);
                if ($over <= 0) return null;
                $inv = DB::table('incidents_investigation')->where('incident_id', $r->id)->value('committee_id');
                $comm = $inv ? DB::table('incident_committee')->where('id', $inv)->value('commitee_name') : null;
                return ['Incident ID' => $r->incident_id ?? 'N/A', 'Assigned Investigator' => $comm ?? 'Unassigned', 'Target Date' => $deadline->format('d M Y'), 'Actual Resolution Date' => $this->dmy($r->resolved_at), 'Days Overdue' => $over];
            })->filter()->values()->all();
        return ['columns' => ['Incident ID', 'Assigned Investigator', 'Target Date', 'Actual Resolution Date', 'Days Overdue'], 'rows' => $rows];
    }

    private function committeeAgg(array $f = []): \Illuminate\Support\Collection
    {
        $rid = $this->resort->resort_id;
        return DB::table('incidents_investigation as ii')->join('incidents as i', 'i.id', '=', 'ii.incident_id')
            ->join('incident_committee as c', 'c.id', '=', 'ii.committee_id')->where('i.resort_id', $rid)
            ->when(true, fn($q) => $this->applyDuration($q, $f, 'i.incident_date'))
            ->get(['c.commitee_name', 'i.status', 'i.incident_date', 'i.resolved_at'])->groupBy('commitee_name');
    }

    public function committeeAssignment(array $f): array
    {
        $rows = $this->committeeAgg($f)->map(fn($items, $name) => [
            'Committee Name' => $name ?: 'N/A', 'Assigned Cases' => $items->count(),
            'Open Cases' => $items->filter(fn($r) => !$this->isClosed($r->status))->count(),
            'Closed Cases' => $items->filter(fn($r) => $this->isClosed($r->status))->count(),
        ])->values()->all();
        return ['columns' => ['Committee Name', 'Assigned Cases', 'Open Cases', 'Closed Cases'], 'rows' => $rows];
    }

    public function committeePerformance(array $f): array
    {
        $rows = $this->committeeAgg($f)->map(function ($items, $name) {
            $times = $items->filter(fn($r) => $r->resolved_at)->map(fn($r) => Carbon::parse($r->incident_date)->diffInDays(Carbon::parse($r->resolved_at)));
            return ['Committee Name' => $name ?: 'N/A', 'Total Cases' => $items->count(), 'Completed Cases' => $items->filter(fn($r) => $this->isClosed($r->status))->count(), 'Average Resolution Time' => $times->count() ? round($times->avg(), 1) . ' day(s)' : 'N/A'];
        })->values()->all();
        return ['columns' => ['Committee Name', 'Total Cases', 'Completed Cases', 'Average Resolution Time'], 'rows' => $rows];
    }

    private function meetingBase()
    {
        $rid = $this->resort->resort_id;
        return DB::table('incidents_investigation_meetings as m')->join('incidents as i', 'i.id', '=', 'm.incident_id')->where('i.resort_id', $rid);
    }

    public function meetingSchedule(array $f): array
    {
        $rows = $this->meetingBase()
            ->when($f['from_date'], fn($q) => $q->whereDate('m.meeting_date', '>=', $f['from_date']))
            ->when($f['to_date'], fn($q) => $q->whereDate('m.meeting_date', '<=', $f['to_date']))
            ->orderByDesc('m.meeting_date')
            ->get(['m.id', 'm.meeting_subject', 'i.incident_id', 'm.meeting_date', 'm.meeting_time', 'm.location'])
            ->map(fn($r) => ['Meeting ID' => 'MTG-' . $r->id, 'Meeting Subject' => $r->meeting_subject ?? 'N/A', 'Incident ID' => $r->incident_id ?? 'N/A', 'Date' => $this->dmy($r->meeting_date), 'Time' => $r->meeting_time ?? 'N/A', 'Location' => $r->location ?? 'N/A'])->all();
        return ['columns' => ['Meeting ID', 'Meeting Subject', 'Incident ID', 'Date', 'Time', 'Location'], 'rows' => $rows];
    }

    public function calendar(array $f): array
    {
        $rows = $this->meetingBase()
            ->when($f['month'], fn($q) => $q->whereRaw('MONTH(m.meeting_date)=?', [$f['month']]))
            ->when($f['year'], fn($q) => $q->whereRaw('YEAR(m.meeting_date)=?', [$f['year']]))
            ->orderBy('m.meeting_date')
            ->get(['m.meeting_date', 'm.meeting_subject', 'i.incident_id', 'm.id'])
            ->map(fn($r) => ['Date' => $this->dmy($r->meeting_date), 'Meeting Subject' => $r->meeting_subject ?? 'N/A', 'Incident ID' => $r->incident_id ?? 'N/A', 'Committee' => 'Investigation'])->all();
        return ['columns' => ['Date', 'Meeting Subject', 'Incident ID', 'Committee'], 'rows' => $rows];
    }

    public function attendance(array $f): array
    {
        $rid = $this->resort->resort_id;
        $rows = DB::table('incidents_investigation_meetings_participants as p')
            ->join('incidents_investigation_meetings as m', 'm.id', '=', 'p.meeting_id')
            ->join('incidents as i', 'i.id', '=', 'm.incident_id')
            ->leftJoin('employees as e', 'e.id', '=', 'p.participant_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->where('i.resort_id', $rid)
            ->when($f['from_date'], fn($q) => $q->whereDate('m.meeting_date', '>=', $f['from_date']))
            ->when($f['to_date'], fn($q) => $q->whereDate('m.meeting_date', '<=', $f['to_date']))
            ->get(['m.meeting_subject', DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as participant")])
            ->map(fn($r) => ['Meeting Subject' => $r->meeting_subject ?? 'N/A', 'Participant Name' => trim($r->participant) ?: 'N/A', 'Attendance Status' => 'Invited'])->all();
        return ['columns' => ['Meeting Subject', 'Participant Name', 'Attendance Status'], 'rows' => $rows];
    }

    public function correctiveAction(array $f): array
    {
        $rows = $this->base($f)->whereNotNull('i.action_taken')->orderByDesc('i.incident_date')
            ->get(['i.incident_id', 'i.action_taken', 'i.status'])
            ->map(fn($r) => ['Incident ID' => $r->incident_id ?? 'N/A', 'Corrective Action' => $r->action_taken ? 'Action #' . $r->action_taken : 'N/A', 'Assigned To' => 'N/A', 'Due Date' => 'N/A', 'Completion Status' => $this->isClosed($r->status) ? 'Completed' : 'Open'])->all();
        return ['columns' => ['Incident ID', 'Corrective Action', 'Assigned To', 'Due Date', 'Completion Status'], 'rows' => $rows];
    }

    public function preventiveAction(array $f): array
    {
        $rows = $this->base($f)->whereNotNull('i.preventive_measures')->where('i.preventive_measures', '<>', '')->orderByDesc('i.incident_date')
            ->get(['i.incident_id', 'i.preventive_measures', 'i.status'])
            ->map(fn($r) => ['Incident ID' => $r->incident_id ?? 'N/A', 'Preventive Action' => $r->preventive_measures ?? 'N/A', 'Responsible Person' => 'N/A', 'Target Date' => 'N/A', 'Status' => $this->isClosed($r->status) ? 'Completed' : 'Open'])->all();
        return ['columns' => ['Incident ID', 'Preventive Action', 'Responsible Person', 'Target Date', 'Status'], 'rows' => $rows];
    }

    public function pendingApproval(array $f): array
    {
        $rows = $this->base($f)->where('i.approval', 0)->where('i.status', 'like', '%approv%')->orderByDesc('i.incident_date')
            ->get(['i.incident_id', 'i.status', 'i.updated_at'])
            ->map(fn($r) => ['Incident ID' => $r->incident_id ?? 'N/A', 'Investigator' => 'Committee', 'Approval Level' => 'Resolution', 'Pending Since' => $this->dmy($r->updated_at)])->all();
        return ['columns' => ['Incident ID', 'Investigator', 'Approval Level', 'Pending Since'], 'rows' => $rows];
    }

    public function rootCause(array $f): array
    {
        $rows = $this->investBase()
            ->leftJoin('incident_categories as ic', 'ic.id', '=', 'i.category')
            ->when($f['from_date'], fn($q) => $q->whereDate('ii.start_date', '>=', $f['from_date']))
            ->when($f['to_date'], fn($q) => $q->whereDate('ii.start_date', '<=', $f['to_date']))
            ->whereNotNull('ii.investigation_findings')
            ->get(['i.incident_id', 'ic.category_name', 'ii.investigation_findings'])
            ->map(fn($r) => ['Incident ID' => $r->incident_id ?? 'N/A', 'Root Cause Category' => $r->category_name ?? 'N/A', 'Root Cause Description' => $r->investigation_findings ?? 'N/A'])->all();
        return ['columns' => ['Incident ID', 'Root Cause Category', 'Root Cause Description'], 'rows' => $rows];
    }

    public function recurrence(array $f): array
    {
        $raw = $this->base($f)->groupBy('ic.category_name')->having(DB::raw('COUNT(*)'), '>', 1)
            ->select('ic.category_name', DB::raw('COUNT(*) c'), DB::raw('GROUP_CONCAT(i.incident_id) ids'))->orderByDesc(DB::raw('COUNT(*)'))->get()
            ->map(fn($r) => ['Incident Category' => $r->category_name ?? 'N/A', 'Number of Recurrences' => (int) $r->c, 'Previous Incident References' => \Illuminate\Support\Str::limit($r->ids, 60)])->all();
        return ['columns' => ['Incident Category', 'Number of Recurrences', 'Previous Incident References'], 'rows' => $raw];
    }

    public function highRisk(array $f): array
    {
        $rows = $this->base($f)->where(fn($q) => $q->where('i.severity', 'like', '%critical%')->orWhere('i.severity', 'like', '%severe%')->orWhere('i.priority', 'High'))
            ->orderByDesc('i.incident_date')
            ->get(['i.id', 'i.incident_id', 'i.severity', 'd.name as dept', 'i.status'])
            ->map(function ($r) {
                $inv = DB::table('incidents_investigation')->where('incident_id', $r->id)->value('committee_id');
                $comm = $inv ? DB::table('incident_committee')->where('id', $inv)->value('commitee_name') : null;
                return ['Incident ID' => $r->incident_id ?? 'N/A', 'Severity' => $r->severity ?? 'N/A', 'Department' => $r->dept ?? 'N/A', 'Status' => $r->status ?? 'N/A', 'Assigned Investigator' => $comm ?? 'Unassigned'];
            })->all();
        return ['columns' => ['Incident ID', 'Severity', 'Department', 'Status', 'Assigned Investigator'], 'rows' => $rows];
    }

    public function auditTrail(array $f): array
    {
        // No incident-specific audit table; surface create/resolve/approve events.
        $rows = $this->base($f)->orderByDesc('i.updated_at')->limit(1000)
            ->get(['i.incident_id', 'i.status', 'i.created_at', 'i.updated_at', 'i.resolved_at', 'i.approved_at'])
            ->map(fn($r) => ['Incident ID' => $r->incident_id ?? 'N/A', 'Activity' => 'Status: ' . ($r->status ?? 'N/A'), 'Performed By' => 'N/A', 'Date & Time' => $r->updated_at ? Carbon::parse($r->updated_at)->format('d M Y H:i') : 'N/A'])->all();
        return ['columns' => ['Incident ID', 'Activity', 'Performed By', 'Date & Time'], 'rows' => $rows];
    }

    public function execDashboard(array $f): array
    {
        $rid = $this->resort->resort_id;
        $b = DB::table('incidents')->where('resort_id', $rid);
        $this->applyDuration($b, $f, 'incident_date');
        $total = (clone $b)->count();
        $closed = (clone $b)->where(fn($q) => $q->where('status', 'like', '%clos%')->orWhere('status', 'like', '%resolv%'))->count();
        $open = $total - $closed;
        $invest = (clone $b)->where('status', 'like', '%investigat%')->count();
        $times = (clone $b)->whereNotNull('resolved_at')->get(['incident_date', 'resolved_at'])->map(fn($r) => Carbon::parse($r->incident_date)->diffInDays(Carbon::parse($r->resolved_at)));
        $high = (clone $b)->where(fn($q) => $q->where('severity', 'like', '%critical%')->orWhere('severity', 'like', '%severe%')->orWhere('priority', 'High'))->count();
        $slaMet = (clone $b)->whereNotNull('resolved_at')->get(['incident_date', 'resolved_at', 'priority'])->filter(fn($r) => Carbon::parse($r->incident_date)->diffInDays(Carbon::parse($r->resolved_at)) <= $this->slaDays($r->priority))->count();
        $resolvedCount = (clone $b)->whereNotNull('resolved_at')->count();
        return ['columns' => ['Total Incidents', 'Open Cases', 'Investigation Progress', 'Average Resolution Time', 'SLA Compliance %', 'High-Risk Incidents'],
            'rows' => [['Total Incidents' => $total, 'Open Cases' => $open, 'Investigation Progress' => $invest, 'Average Resolution Time' => $times->count() ? round($times->avg(), 1) . ' day(s)' : 'N/A', 'SLA Compliance %' => $this->pct($slaMet, $resolvedCount), 'High-Risk Incidents' => $high]]];
    }
}
