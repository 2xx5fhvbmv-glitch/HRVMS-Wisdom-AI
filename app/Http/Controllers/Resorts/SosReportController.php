<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Resorts\Concerns\PredefinedReportActions;
use App\Helpers\Common;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Predefined SOS / Emergency reports (Option B), generic view.
 *
 * Main table: sos_history (status = Pending|Active|Completed|Rejected|Drill-active,
 * alert = date + time, initiator = emp_initiated_by, type = emergency_id).
 * The lifecycle timeline lives in child_sos_history_status.sos_status, one row per
 * transition with its created_at:
 *   sos_activation -> manager_acknowledgement -> team_notifications_sent
 *   -> situation_was_marked_as_under_control -> sos_completed
 * Response teams come from child_sos_history -> sos_teams.
 */
class SosReportController extends Controller
{
    use PredefinedReportActions;

    protected $resort;

    /** Active / in-flight statuses (not resolved, not rejected). */
    private const ACTIVE_STATUSES = ['Active', 'Pending', 'Drill-active'];

    public function __construct()
    {
        $this->resort = auth()->guard('resort-admin')->user();
    }

    private function registry(): array
    {
        return [
            'exec_summary'         => ['name' => 'SOS Executive Summary', 'description' => 'Consolidated overview of SOS incidents, response status and emergency trends.', 'filters' => ['duration'], 'handler' => 'execSummary'],
            'incident_register'    => ['name' => 'SOS Incident Register', 'description' => 'All SOS alerts raised within the selected period.', 'filters' => ['duration', 'sos_type', 'status'], 'handler' => 'incidentRegister'],
            'active_sos'           => ['name' => 'Active SOS Report', 'description' => 'SOS incidents currently active or under response.', 'filters' => ['sos_type'], 'handler' => 'activeSos'],
            'response_timeline'    => ['name' => 'SOS Response Timeline Report', 'description' => 'Complete timeline of each SOS incident from initiation until closure.', 'filters' => ['duration'], 'handler' => 'responseTimeline'],
            'incident_analysis'    => ['name' => 'SOS Incident Analysis Report', 'description' => 'SOS incidents grouped by emergency type to spot recurring patterns.', 'filters' => ['duration'], 'handler' => 'incidentAnalysis'],
            'location_report'      => ['name' => 'SOS Location Report', 'description' => 'SOS incidents grouped by location to identify emergency hotspots.', 'filters' => ['location'], 'handler' => 'locationReport'],
            'response_performance' => ['name' => 'SOS Response Performance Report', 'description' => 'Emergency response efficiency: response vs resolution time per incident.', 'filters' => ['duration'], 'handler' => 'responsePerformance'],
            'exec_dashboard'       => ['name' => 'SOS Executive Dashboard Report', 'description' => 'High-level overview of SOS activity, response performance and readiness.', 'filters' => ['duration'], 'handler' => 'execDashboard'],
        ];
    }

    /* --------------------------------------------------------------- plumbing */

    public function index()
    {
        if (Common::checkRouteWisePermission('resort.report.index', config('settings.resort_permissions.view')) == false) return abort(403, 'Unauthorized access');
        $rid = $this->resort->resort_id;

        $reports = collect($this->registry())->map(fn($r, $key) => [
            'key' => $key, 'name' => $r['name'], 'description' => $r['description'],
            // Every report exposes the duration (From/To date) filter, like the Custom Report.
            'filters' => array_values(array_unique(array_merge($r['filters'], ['duration']))),
        ])->values();

        $types = DB::table('sos_emergency_types')->where('resort_id', $rid)->orderBy('name')->get(['id', 'name']);
        $locations = DB::table('sos_history')->where('resort_id', $rid)->whereNotNull('location')->where('location', '<>', '')->distinct()->orderBy('location')->pluck('location');

        $filterDefs = [
            ['filter' => 'sos_type', 'name' => 'sos_type', 'label' => 'SOS Type', 'type' => 'select', 'placeholder' => 'All types',
                'options' => $types->map(fn($t) => ['value' => $t->id, 'label' => $t->name])->all()],
            ['filter' => 'status', 'name' => 'status', 'label' => 'Status', 'type' => 'select', 'placeholder' => 'All statuses',
                'options' => collect(['Pending', 'Active', 'Completed', 'Rejected', 'Drill-active'])->map(fn($s) => ['value' => $s, 'label' => $s])->all()],
            ['filter' => 'location', 'name' => 'location', 'label' => 'Location', 'type' => 'select', 'placeholder' => 'All locations',
                'options' => $locations->map(fn($l) => ['value' => $l, 'label' => $l])->all()],
            ['filter' => 'duration', 'name' => 'from_date', 'label' => 'From Date', 'type' => 'date'],
            ['filter' => 'duration', 'name' => 'to_date', 'label' => 'To Date', 'type' => 'date'],
        ];

        return view('resorts.reports.module_report', [
            'page_title' => 'SOS / Emergency Reports', 'reports' => $reports, 'filterDefs' => $filterDefs,
            'runRoute' => 'resort.report.sos.run', 'exportRoute' => 'resort.report.sos.export', 'insightsRoute' => 'resort.report.sos.insights',
        ]);
    }

    private function filtersFrom(Request $request): array
    {
        return collect(['sos_type', 'status', 'location', 'from_date', 'to_date'])
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

    /* --------------------------------------------------------------- helpers */

    private function applyDuration($q, array $f, string $col)
    {
        return $q->when($f['from_date'] ?? null, fn($x) => $x->whereDate($col, '>=', $f['from_date']))
                 ->when($f['to_date'] ?? null, fn($x) => $x->whereDate($col, '<=', $f['to_date']));
    }

    /** created_at of a given lifecycle transition for the current sos_history row. */
    private function ts(string $status): string
    {
        return "(SELECT MIN(created_at) FROM child_sos_history_status WHERE sos_history_id = h.id AND sos_status = '{$status}')";
    }

    /** sos_history base with type / initiator / department + lifecycle timestamp columns. */
    private function base(array $f)
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();
        return DB::table('sos_history as h')
            ->leftJoin('sos_emergency_types as et', 'et.id', '=', 'h.emergency_id')
            ->leftJoin('employees as e', 'e.id', '=', 'h.emp_initiated_by')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->leftJoin('resort_departments as d', 'd.id', '=', 'e.Dept_id')
            ->where('h.resort_id', $rid)
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))
            ->when($f['sos_type'] ?? null, fn($q) => $q->where('h.emergency_id', $f['sos_type']))
            ->when($f['status'] ?? null, fn($q) => $q->where('h.status', $f['status']))
            ->when($f['location'] ?? null, fn($q) => $q->where('h.location', $f['location']))
            ->select(
                'h.id', 'h.status', 'h.date', 'h.time', 'h.location', 'et.name as sos_type',
                DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as employee_name"),
                'd.name as dept',
                DB::raw($this->ts('sos_activation') . ' as t_alert'),
                DB::raw($this->ts('manager_acknowledgement') . ' as t_ack'),
                DB::raw($this->ts('team_notifications_sent') . ' as t_response'),
                DB::raw($this->ts('situation_was_marked_as_under_control') . ' as t_control'),
                DB::raw($this->ts('sos_completed') . ' as t_closed'),
                DB::raw("(SELECT GROUP_CONCAT(DISTINCT tm.name SEPARATOR ', ') FROM child_sos_history c JOIN sos_teams tm ON tm.id = c.team_id WHERE c.sos_history_id = h.id) as teams")
            );
    }

    /** Alert datetime: prefer the activation transition, else date + time. */
    private function alertAt($r): ?Carbon
    {
        if (!empty($r->t_alert)) return Carbon::parse($r->t_alert);
        if (!empty($r->date)) return Carbon::parse(trim($r->date . ' ' . ($r->time ?: '00:00:00')));
        return null;
    }

    private function dt($v): string { return $v ? Carbon::parse($v)->format('d M Y H:i') : 'N/A'; }

    private function minutesBetween($start, $end): ?float
    {
        if (!$start || !$end) return null;
        return Carbon::parse($start)->diffInMinutes(Carbon::parse($end), false);
    }

    private function fmtDur($minutes): string
    {
        if ($minutes === null) return 'N/A';
        $m = (int) round($minutes);
        if ($m < 0) return 'N/A';
        if ($m < 60) return $m . ' min';
        $h = intdiv($m, 60); $mm = $m % 60;
        return $mm ? "{$h}h {$mm}m" : "{$h}h";
    }

    /** Response = acknowledgement − alert; Resolution = closed − alert (minutes). */
    private function responseMin($r): ?float { return $this->minutesBetween($this->alertAt($r), $r->t_ack ?: $r->t_response); }
    private function resolutionMin($r): ?float { return $this->minutesBetween($this->alertAt($r), $r->t_closed); }

    private function avg(array $vals): ?float
    {
        $vals = array_filter($vals, fn($v) => $v !== null && $v >= 0);
        return count($vals) ? array_sum($vals) / count($vals) : null;
    }

    /* --------------------------------------------------------------- reports */

    public function execSummary(array $f): array
    {
        $rows = $this->base($f)->when(true, fn($q) => $this->applyDuration($q, $f, 'h.date'))->get();
        $active = $rows->whereIn('status', self::ACTIVE_STATUSES)->count();
        $resolved = $rows->where('status', 'Completed')->count();
        $avgResp = $this->avg($rows->map(fn($r) => $this->responseMin($r))->all());
        $avgResn = $this->avg($rows->map(fn($r) => $this->resolutionMin($r))->all());
        return [
            'columns' => ['Total SOS Alerts', 'Active Alerts', 'Resolved Alerts', 'Average Response Time', 'Average Resolution Time'],
            'rows' => [[
                'Total SOS Alerts'        => $rows->count(),
                'Active Alerts'           => $active,
                'Resolved Alerts'         => $resolved,
                'Average Response Time'   => $this->fmtDur($avgResp),
                'Average Resolution Time' => $this->fmtDur($avgResn),
            ]],
        ];
    }

    public function execDashboard(array $f): array
    {
        $c = $this->execSummary($f);
        // Same headline metrics; distinct report entry for management dashboards.
        $c['rows'][0] = array_combine(
            ['Total Alerts', 'Active Alerts', 'Resolved Alerts', 'Average Response Time', 'Average Resolution Time'],
            array_values($c['rows'][0])
        );
        $c['columns'] = ['Total Alerts', 'Active Alerts', 'Resolved Alerts', 'Average Response Time', 'Average Resolution Time'];
        return $c;
    }

    public function incidentRegister(array $f): array
    {
        $rows = $this->base($f)->when(true, fn($q) => $this->applyDuration($q, $f, 'h.date'))
            ->orderByDesc('h.date')->orderByDesc('h.time')->get()
            ->map(fn($r) => [
                'SOS ID'         => 'SOS-' . $r->id,
                'SOS Type'       => $r->sos_type ?? 'N/A',
                'Employee Name'  => trim($r->employee_name) ?: 'N/A',
                'Department'     => $r->dept ?? 'N/A',
                'Location'       => $r->location ?? 'N/A',
                'Alert Date'     => $r->date ? Carbon::parse($r->date)->format('d M Y') : 'N/A',
                'Alert Time'     => $r->time ? Carbon::parse($r->time)->format('H:i') : 'N/A',
                'Current Status' => $r->status ?? 'N/A',
            ])->all();
        return ['columns' => ['SOS ID', 'SOS Type', 'Employee Name', 'Department', 'Location', 'Alert Date', 'Alert Time', 'Current Status'], 'rows' => $rows];
    }

    public function activeSos(array $f): array
    {
        $rows = $this->base($f)->whereIn('h.status', self::ACTIVE_STATUSES)
            ->when(true, fn($q) => $this->applyDuration($q, $f, 'h.date'))
            ->orderByDesc('h.date')->orderByDesc('h.time')->get()
            ->map(fn($r) => [
                'SOS ID'         => 'SOS-' . $r->id,
                'Employee Name'  => trim($r->employee_name) ?: 'N/A',
                'Location'       => $r->location ?? 'N/A',
                'Initiated Time' => ($at = $this->alertAt($r)) ? $at->format('d M Y H:i') : 'N/A',
                'Response Team'  => $r->teams ?: 'N/A',
                'Current Status' => $r->status ?? 'N/A',
            ])->all();
        return ['columns' => ['SOS ID', 'Employee Name', 'Location', 'Initiated Time', 'Response Team', 'Current Status'], 'rows' => $rows];
    }

    public function responseTimeline(array $f): array
    {
        $rows = $this->base($f)->when(true, fn($q) => $this->applyDuration($q, $f, 'h.date'))
            ->orderByDesc('h.date')->orderByDesc('h.time')->get()
            ->map(fn($r) => [
                'SOS ID'                => 'SOS-' . $r->id,
                'Employee Name'         => trim($r->employee_name) ?: 'N/A',
                'Alert Time'            => ($at = $this->alertAt($r)) ? $at->format('d M Y H:i') : 'N/A',
                'Acknowledged Time'     => $this->dt($r->t_ack),
                'Response Started'      => $this->dt($r->t_response),
                'Under Control Time'    => $this->dt($r->t_control),
                'Closed Time'           => $this->dt($r->t_closed),
                'Total Resolution Time' => $this->fmtDur($this->resolutionMin($r)),
            ])->all();
        return ['columns' => ['SOS ID', 'Employee Name', 'Alert Time', 'Acknowledged Time', 'Response Started', 'Under Control Time', 'Closed Time', 'Total Resolution Time'], 'rows' => $rows];
    }

    public function incidentAnalysis(array $f): array
    {
        $rows = $this->base($f)->when(true, fn($q) => $this->applyDuration($q, $f, 'h.date'))->get();
        $total = $rows->count();
        $out = $rows->groupBy(fn($r) => $r->sos_type ?: 'Unspecified')->map(function ($grp, $type) use ($total) {
            $avgResn = $this->avg($grp->map(fn($r) => $this->resolutionMin($r))->all());
            return [
                'SOS Type'                => $type,
                'Total Incidents'         => $grp->count(),
                'Percentage'              => $total ? round($grp->count() / $total * 100, 1) . '%' : '0%',
                'Average Resolution Time' => $this->fmtDur($avgResn),
            ];
        })->sortByDesc('Total Incidents')->values()->all();
        return ['columns' => ['SOS Type', 'Total Incidents', 'Percentage', 'Average Resolution Time'], 'rows' => $out];
    }

    public function locationReport(array $f): array
    {
        $rows = $this->base($f)->when(true, fn($q) => $this->applyDuration($q, $f, 'h.date'))->get();
        $out = $rows->groupBy(fn($r) => $r->location ?: 'Unspecified')->map(function ($grp, $loc) {
            $recent = $grp->map(fn($r) => $this->alertAt($r))->filter()->max();
            $avgResp = $this->avg($grp->map(fn($r) => $this->responseMin($r))->all());
            return [
                'Location'             => $loc,
                'Total SOS Alerts'     => $grp->count(),
                'Most Recent Alert'    => $recent ? $recent->format('d M Y H:i') : 'N/A',
                'Average Response Time' => $this->fmtDur($avgResp),
            ];
        })->sortByDesc('Total SOS Alerts')->values()->all();
        return ['columns' => ['Location', 'Total SOS Alerts', 'Most Recent Alert', 'Average Response Time'], 'rows' => $out];
    }

    public function responsePerformance(array $f): array
    {
        $rows = $this->base($f)->when(true, fn($q) => $this->applyDuration($q, $f, 'h.date'))
            ->orderByDesc('h.date')->orderByDesc('h.time')->get()
            ->map(function ($r) {
                $resp = $this->responseMin($r);
                return [
                    'SOS ID'          => 'SOS-' . $r->id,
                    'Response Time'   => $this->fmtDur($resp),
                    'Resolution Time' => $this->fmtDur($this->resolutionMin($r)),
                    'Response Status' => $r->status === 'Completed' ? 'Resolved'
                        : (in_array($r->status, self::ACTIVE_STATUSES, true) ? ($resp !== null ? 'Responding' : 'Awaiting Response') : ($r->status ?? 'N/A')),
                ];
            })->all();
        return ['columns' => ['SOS ID', 'Response Time', 'Resolution Time', 'Response Status'], 'rows' => $rows];
    }
}
