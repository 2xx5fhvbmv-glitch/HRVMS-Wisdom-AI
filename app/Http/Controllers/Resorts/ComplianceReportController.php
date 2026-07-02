<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Resorts\Concerns\PredefinedReportActions;
use App\Helpers\Common;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Predefined Compliance reports (Option B), generic view.
 *
 * Single source table: compliances (cross-module breach log).
 *   module_name              -> which Wisdom AI module raised the breach
 *   compliance_breached_name -> the breach type (e.g. "Minimum Wage")
 *   severity_ai              -> priority (Critical|High|Medium|Low)
 *   status                   -> 'Resolved' = closed; anything else (Breached / '') = open
 *   reported_on              -> when the breach was recorded
 *   employee_id              -> the employee involved (may be null for module-level breaches)
 */
class ComplianceReportController extends Controller
{
    use PredefinedReportActions;

    protected $resort;

    private const PRIORITIES = ['Critical', 'High', 'Medium', 'Low'];

    public function __construct()
    {
        $this->resort = auth()->guard('resort-admin')->user();
    }

    private function registry(): array
    {
        return [
            'exec_summary'  => ['name' => 'Compliance Executive Summary', 'description' => 'Consolidated overview of compliance breaches across every module.', 'filters' => ['duration'], 'handler' => 'execSummary'],
            'register'      => ['name' => 'Compliance Register', 'description' => 'Every compliance breach recorded in the system.', 'filters' => ['duration'], 'handler' => 'register'],
            'module_wise'   => ['name' => 'Module-wise Compliance Report', 'description' => 'Compliance breaches grouped by system module.', 'filters' => ['module', 'duration'], 'handler' => 'moduleWise'],
            'employee'      => ['name' => 'Employee Compliance Report', 'description' => 'Full compliance history for a selected employee.', 'filters' => ['employee'], 'handler' => 'employeeReport'],
            'breach_type'   => ['name' => 'Compliance Breach Type Report', 'description' => 'Breaches grouped by breach type to spot recurring violations.', 'filters' => ['breach_type', 'duration'], 'handler' => 'breachType'],
            'critical'      => ['name' => 'Critical Compliance Report', 'description' => 'High and Critical priority breaches needing immediate attention.', 'filters' => ['priority', 'duration'], 'handler' => 'critical'],
            'outstanding'   => ['name' => 'Outstanding Compliance Report', 'description' => 'Breaches still unresolved or under review, with days outstanding.', 'filters' => ['duration'], 'handler' => 'outstanding'],
            'trend'         => ['name' => 'Compliance Trend Report', 'description' => 'Monthly compliance breach trend for a year.', 'filters' => ['year'], 'handler' => 'trend'],
            'exec_dashboard' => ['name' => 'Compliance Executive Dashboard Report', 'description' => 'Senior-management dashboard of compliance activity across all modules.', 'filters' => ['duration'], 'handler' => 'execDashboard'],
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
            // Every report exposes the duration (From/To date) filter, like the Custom Report.
            'filters' => array_values(array_unique(array_merge($r['filters'], ['duration']))),
        ])->values();

        $modules = DB::table('compliances')->where('resort_id', $rid)->whereNotNull('module_name')->where('module_name', '<>', '')->distinct()->orderBy('module_name')->pluck('module_name');
        $breachTypes = DB::table('compliances')->where('resort_id', $rid)->whereNotNull('compliance_breached_name')->where('compliance_breached_name', '<>', '')->distinct()->orderBy('compliance_breached_name')->pluck('compliance_breached_name');
        $employees = DB::table('employees as e')->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->where('e.resort_id', $rid)->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))
            ->orderBy('ra.first_name')->get(['e.id', DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as name")]);

        $filterDefs = [
            ['filter' => 'module', 'name' => 'module', 'label' => 'Module Name', 'type' => 'select', 'placeholder' => 'All modules',
                'options' => $modules->map(fn($m) => ['value' => $m, 'label' => $m])->all()],
            ['filter' => 'breach_type', 'name' => 'breach_type', 'label' => 'Breach Type', 'type' => 'select', 'placeholder' => 'All breach types',
                'options' => $breachTypes->map(fn($b) => ['value' => $b, 'label' => $b])->all()],
            ['filter' => 'priority', 'name' => 'priority', 'label' => 'Priority', 'type' => 'select', 'placeholder' => 'High & Critical',
                'options' => collect(self::PRIORITIES)->map(fn($p) => ['value' => $p, 'label' => $p])->all()],
            ['filter' => 'employee', 'name' => 'employee', 'label' => 'Employee', 'type' => 'select', 'placeholder' => 'All employees',
                'options' => $employees->map(fn($e) => ['value' => $e->id, 'label' => $e->name])->all()],
            ['filter' => 'year', 'name' => 'year', 'label' => 'Year', 'type' => 'select',
                'options' => collect(range((int) date('Y'), (int) date('Y') - 4))->map(fn($y) => ['value' => $y, 'label' => $y])->all()],
            ['filter' => 'duration', 'name' => 'from_date', 'label' => 'From Date', 'type' => 'date'],
            ['filter' => 'duration', 'name' => 'to_date', 'label' => 'To Date', 'type' => 'date'],
        ];

        return view('resorts.reports.module_report', [
            'page_title' => 'Compliance Reports', 'reports' => $reports, 'filterDefs' => $filterDefs,
            'runRoute' => 'resort.report.compliance.run', 'exportRoute' => 'resort.report.compliance.export', 'insightsRoute' => 'resort.report.compliance.insights',
        ]);
    }

    private function filtersFrom(Request $request): array
    {
        return collect(['module', 'breach_type', 'priority', 'employee', 'year', 'from_date', 'to_date'])
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

    /** compliances base with employee name + Emp_id, all categorical filters applied. */
    private function base(array $f)
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();
        return DB::table('compliances as c')
            ->leftJoin('employees as e', 'e.id', '=', 'c.employee_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->where('c.resort_id', $rid)
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))
            ->when($f['module'] ?? null, fn($q) => $q->where('c.module_name', $f['module']))
            ->when($f['breach_type'] ?? null, fn($q) => $q->where('c.compliance_breached_name', $f['breach_type']))
            ->when($f['priority'] ?? null, fn($q) => $q->where('c.severity_ai', $f['priority']))
            ->when($f['employee'] ?? null, fn($q) => $q->where('c.employee_id', $f['employee']))
            ->select('c.id', 'c.module_name', 'c.compliance_breached_name', 'c.description', 'c.description_ai',
                'c.severity_ai', 'c.status', 'c.reported_on', 'c.employee_id', 'e.Emp_id as emp_code',
                DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as employee_name"));
    }

    private function isOpen($status): bool { return strtolower(trim((string) $status)) !== 'resolved'; }

    private function dmy($d): string { return $d ? Carbon::parse($d)->format('d M Y') : 'N/A'; }

    /** Shared cell values for a breach row, keyed by column label. */
    private function cells($r): array
    {
        return [
            'Module Name'       => $r->module_name ?: 'N/A',
            'Compliance Breach' => $r->compliance_breached_name ?: 'N/A',
            'Employee ID'       => $r->emp_code ?: 'N/A',
            'Employee Name'     => trim($r->employee_name) ?: 'N/A',
            'Description'       => $r->description ?: ($r->description_ai ?: 'N/A'),
            'Priority'          => $r->severity_ai ?: 'N/A',
            'Reported Date'     => $this->dmy($r->reported_on),
            'Current Status'    => $r->status ?: 'Breached',
        ];
    }

    /** Reorder cells() into the given column list. */
    private function pick(array $cells, array $columns): array
    {
        $row = [];
        foreach ($columns as $col) $row[$col] = $cells[$col] ?? 'N/A';
        return $row;
    }

    /* --------------------------------------------------------------- reports */

    public function execSummary(array $f): array
    {
        $rows = $this->base($f)->when(true, fn($q) => $this->applyDuration($q, $f, 'c.reported_on'))->get();
        $bySev = fn($s) => $rows->filter(fn($r) => strtolower(trim((string) $r->severity_ai)) === strtolower($s))->count();
        return [
            'columns' => ['Total Breaches', 'Open Breaches', 'Critical Breaches', 'High Priority Breaches', 'Medium Priority Breaches', 'Low Priority Breaches'],
            'rows' => [[
                'Total Breaches'           => $rows->count(),
                'Open Breaches'            => $rows->filter(fn($r) => $this->isOpen($r->status))->count(),
                'Critical Breaches'        => $bySev('Critical'),
                'High Priority Breaches'   => $bySev('High'),
                'Medium Priority Breaches' => $bySev('Medium'),
                'Low Priority Breaches'    => $bySev('Low'),
            ]],
        ];
    }

    public function register(array $f): array
    {
        $columns = ['Module Name', 'Compliance Breach', 'Employee ID', 'Employee Name', 'Description', 'Priority', 'Reported Date', 'Current Status'];
        $rows = $this->base($f)->when(true, fn($q) => $this->applyDuration($q, $f, 'c.reported_on'))
            ->orderByDesc('c.reported_on')->get()->map(fn($r) => $this->pick($this->cells($r), $columns))->all();
        return ['columns' => $columns, 'rows' => $rows];
    }

    public function moduleWise(array $f): array
    {
        $columns = ['Module Name', 'Compliance Breach', 'Employee ID', 'Employee Name', 'Description', 'Priority', 'Reported Date', 'Current Status'];
        $rows = $this->base($f)->when(true, fn($q) => $this->applyDuration($q, $f, 'c.reported_on'))
            ->orderBy('c.module_name')->orderByDesc('c.reported_on')->get()->map(fn($r) => $this->pick($this->cells($r), $columns))->all();
        return ['columns' => $columns, 'rows' => $rows];
    }

    public function employeeReport(array $f): array
    {
        $columns = ['Employee ID', 'Employee Name', 'Module Name', 'Compliance Breach', 'Description', 'Priority', 'Reported Date', 'Current Status'];
        $rows = $this->base($f)->when(true, fn($q) => $this->applyDuration($q, $f, 'c.reported_on'))
            ->orderByDesc('c.reported_on')->get()->map(fn($r) => $this->pick($this->cells($r), $columns))->all();
        return ['columns' => $columns, 'rows' => $rows];
    }

    public function breachType(array $f): array
    {
        $columns = ['Compliance Breach', 'Module Name', 'Employee ID', 'Employee Name', 'Description', 'Priority', 'Reported Date', 'Current Status'];
        $rows = $this->base($f)->when(true, fn($q) => $this->applyDuration($q, $f, 'c.reported_on'))
            ->orderBy('c.compliance_breached_name')->orderByDesc('c.reported_on')->get()->map(fn($r) => $this->pick($this->cells($r), $columns))->all();
        return ['columns' => $columns, 'rows' => $rows];
    }

    public function critical(array $f): array
    {
        $columns = ['Module Name', 'Compliance Breach', 'Employee ID', 'Employee Name', 'Description', 'Priority', 'Reported Date', 'Current Status'];
        $q = $this->base($f)->when(true, fn($x) => $this->applyDuration($x, $f, 'c.reported_on'));
        // With no explicit priority chosen, default to High + Critical (base() already
        // applied the filter when one was picked).
        if (empty($f['priority'])) $q->whereIn('c.severity_ai', ['High', 'Critical']);
        $rows = $q->orderByRaw("FIELD(c.severity_ai,'Critical','High','Medium','Low')")->orderByDesc('c.reported_on')
            ->get()->map(fn($r) => $this->pick($this->cells($r), $columns))->all();
        return ['columns' => $columns, 'rows' => $rows];
    }

    public function outstanding(array $f): array
    {
        $columns = ['Module Name', 'Compliance Breach', 'Employee ID', 'Employee Name', 'Description', 'Priority', 'Reported Date', 'Days Outstanding', 'Current Status'];
        $rows = $this->base($f)->where(fn($q) => $q->where('c.status', '<>', 'Resolved')->orWhereNull('c.status'))
            ->when(true, fn($q) => $this->applyDuration($q, $f, 'c.reported_on'))
            ->orderByDesc('c.reported_on')->get()->map(function ($r) use ($columns) {
                $cells = $this->cells($r);
                $cells['Days Outstanding'] = $r->reported_on ? Carbon::parse($r->reported_on)->startOfDay()->diffInDays(Carbon::today()) . ' day(s)' : 'N/A';
                return $this->pick($cells, $columns);
            })->all();
        return ['columns' => $columns, 'rows' => $rows];
    }

    public function trend(array $f): array
    {
        $rid = $this->resort->resort_id;
        $year = $f['year'] ?: date('Y');
        $rows = [];
        for ($m = 1; $m <= 12; $m++) {
            $b = $this->base($f)->whereRaw('YEAR(c.reported_on)=?', [$year])->whereRaw('MONTH(c.reported_on)=?', [$m])->get();
            if ($b->isEmpty()) continue;
            $bySev = fn($s) => $b->filter(fn($r) => strtolower(trim((string) $r->severity_ai)) === strtolower($s))->count();
            $rows[] = [
                'Month'                  => Carbon::create()->month($m)->format('F'),
                'Total Breaches'         => $b->count(),
                'Critical Breaches'      => $bySev('Critical'),
                'High Priority Breaches' => $bySev('High'),
                'Resolved Breaches'      => $b->filter(fn($r) => !$this->isOpen($r->status))->count(),
            ];
        }
        return ['columns' => ['Month', 'Total Breaches', 'Critical Breaches', 'High Priority Breaches', 'Resolved Breaches'], 'rows' => $rows];
    }

    public function execDashboard(array $f): array
    {
        $rows = $this->base($f)->when(true, fn($q) => $this->applyDuration($q, $f, 'c.reported_on'))->get();
        $topModule = $rows->groupBy(fn($r) => $r->module_name ?: 'Unspecified')->map->count()->sortDesc();
        $multi = $rows->whereNotNull('employee_id')->groupBy('employee_id')->filter(fn($g) => $g->count() > 1)->count();
        return [
            'columns' => ['Total Breaches', 'Critical Breaches', 'Open Cases', 'Resolved Cases', 'Module with Highest Breaches', 'Employees with Multiple Breaches'],
            'rows' => [[
                'Total Breaches'                   => $rows->count(),
                'Critical Breaches'                => $rows->filter(fn($r) => strtolower(trim((string) $r->severity_ai)) === 'critical')->count(),
                'Open Cases'                       => $rows->filter(fn($r) => $this->isOpen($r->status))->count(),
                'Resolved Cases'                   => $rows->filter(fn($r) => !$this->isOpen($r->status))->count(),
                'Module with Highest Breaches'     => $topModule->isNotEmpty() ? $topModule->keys()->first() . ' (' . $topModule->first() . ')' : 'N/A',
                'Employees with Multiple Breaches' => $multi,
            ]],
        ];
    }
}
