<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Resorts\Concerns\PredefinedReportActions;
use App\Helpers\Common;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Predefined People Management – Announcement reports (Option B), generic view.
 *
 * Source: announcement (one row per recipient employee — a broadcast to N
 * employees is stored as N rows), joined to announcement_category (the
 * misleadingly-named `title` column is actually the category id).
 */
class AnnouncementReportController extends Controller
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
            'announcement_register' => [
                'name' => 'Announcement Register',
                'description' => 'Master register of all announcements published within the selected period, including employee recognition, organizational announcements, and other internal communications.',
                'filters' => ['duration', 'title', 'category', 'status'],
                'handler' => 'announcementRegister',
            ],
            'employee_announcement_history' => [
                'name' => 'Employee Announcement History Report',
                'description' => 'All announcements associated with a selected employee, providing a complete history of employee recognition and announcement-related activities.',
                'filters' => ['duration', 'employee'],
                'handler' => 'employeeAnnouncementHistory',
            ],
            'announcement_activity' => [
                'name' => 'Announcement Activity Report',
                'description' => 'All announcements published during a selected period, helping HR review organizational communication and recognition activities.',
                'filters' => ['duration', 'category', 'published_by'],
                'handler' => 'announcementActivity',
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

        $categories = DB::table('announcement_category')->where('resort_id', $rid)->orderBy('name')->get(['id', 'name']);
        $employees = DB::table('employees as e')->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->where('e.resort_id', $rid)->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))
            ->orderBy('ra.first_name')->get(['e.id', DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as name")]);
        $publishers = DB::table('announcement as a')->join('resort_admins as ra', 'ra.id', '=', 'a.created_by')
            ->where('a.resort_id', $rid)->distinct()
            ->get(['ra.id', DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as name")]);

        $filterDefs = [
            ['filter' => 'title', 'name' => 'title', 'label' => 'Announcement Title', 'type' => 'text', 'placeholder' => 'Search by title/message'],
            ['filter' => 'category', 'name' => 'category', 'label' => 'Announcement Category', 'type' => 'select', 'placeholder' => 'All categories', 'options' => $categories->map(fn($c) => ['value' => $c->id, 'label' => $c->name])->all()],
            ['filter' => 'status', 'name' => 'status', 'label' => 'Publication Status', 'type' => 'select', 'placeholder' => 'All statuses', 'options' => collect(['Draft', 'Published', 'Scheduled'])->map(fn($s) => ['value' => $s, 'label' => $s])->all()],
            ['filter' => 'employee', 'name' => 'employee', 'label' => 'Employee', 'type' => 'select', 'placeholder' => 'All employees', 'options' => $employees->map(fn($e) => ['value' => $e->id, 'label' => $e->name])->all()],
            ['filter' => 'published_by', 'name' => 'published_by', 'label' => 'Published By', 'type' => 'select', 'placeholder' => 'All users', 'options' => $publishers->map(fn($p) => ['value' => $p->id, 'label' => $p->name])->all()],
            ['filter' => 'duration', 'name' => 'from_date', 'label' => 'From Date', 'type' => 'date'],
            ['filter' => 'duration', 'name' => 'to_date', 'label' => 'To Date', 'type' => 'date'],
        ];

        return view('resorts.reports.module_report', [
            'page_title' => 'Announcement Reports', 'reports' => $reports, 'filterDefs' => $filterDefs,
            'runRoute' => 'resort.report.announcement.run', 'exportRoute' => 'resort.report.announcement.export', 'insightsRoute' => 'resort.report.announcement.insights',
        ]);
    }

    private function filtersFrom(Request $request): array
    {
        return collect(['title', 'category', 'status', 'employee', 'published_by', 'from_date', 'to_date'])
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
        return DB::table('announcement as a')
            ->join('employees as e', 'e.id', '=', 'a.employee_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->leftJoin('resort_departments as d', 'd.id', '=', 'e.Dept_id')
            ->leftJoin('resort_positions as p', 'p.id', '=', 'e.Position_id')
            ->leftJoin('announcement_category as ac', 'ac.id', '=', 'a.title')
            ->leftJoin('resort_admins as pub', 'pub.id', '=', 'a.created_by')
            ->where('a.resort_id', $rid)
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped));
    }

    /* --------------------------------------------------------------- reports */

    public function announcementRegister(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();

        $rows = $this->baseQuery($rid, $scoped)
            ->when($f['title'] ?? null, fn($q, $v) => $q->where('a.message', 'LIKE', "%{$v}%"))
            ->when($f['category'] ?? null, fn($q) => $q->where('a.title', $f['category']))
            ->when($f['status'] ?? null, fn($q) => $q->where('a.status', $f['status']))
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('a.published_date', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('a.published_date', '<=', $f['to_date']))
            ->orderByDesc('a.published_date')
            ->get([
                'a.message', 'a.status', 'a.published_date',
                'e.Emp_id', DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as employee_name"),
                'd.name as dept', 'p.position_title', 'ac.name as category_name',
            ])
            ->map(fn($r) => [
                'Announcement Title'   => $r->message ? \Illuminate\Support\Str::limit($r->message, 60) : 'N/A',
                'Announcement Category' => $r->category_name ?: 'N/A',
                'Employee ID'           => $r->Emp_id ?: 'N/A',
                'Employee Name'         => trim($r->employee_name) ?: 'N/A',
                'Department'            => $r->dept ?? 'N/A',
                'Position'              => $r->position_title ?? 'N/A',
                'Publication Date'      => $r->published_date ? Carbon::parse($r->published_date)->format('d M Y') : 'N/A',
                'Status'                => $r->status ?: 'N/A',
            ])->values()->all();

        return [
            'columns' => ['Announcement Title', 'Announcement Category', 'Employee ID', 'Employee Name', 'Department', 'Position', 'Publication Date', 'Status'],
            'rows'    => $rows,
        ];
    }

    public function employeeAnnouncementHistory(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();

        $rows = $this->baseQuery($rid, $scoped)
            ->when($f['employee'] ?? null, fn($q) => $q->where('e.id', $f['employee']))
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('a.published_date', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('a.published_date', '<=', $f['to_date']))
            ->orderByDesc('a.published_date')
            ->get([
                'e.Emp_id', DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as employee_name"),
                'd.name as dept', 'p.position_title', 'a.message', 'ac.name as category_name', 'a.published_date', 'a.status',
            ])
            ->map(fn($r) => [
                'Employee ID'           => $r->Emp_id ?: 'N/A',
                'Employee Name'         => trim($r->employee_name) ?: 'N/A',
                'Department'            => $r->dept ?? 'N/A',
                'Position'              => $r->position_title ?? 'N/A',
                'Announcement Title'    => $r->message ? \Illuminate\Support\Str::limit($r->message, 60) : 'N/A',
                'Announcement Category' => $r->category_name ?: 'N/A',
                'Publication Date'      => $r->published_date ? Carbon::parse($r->published_date)->format('d M Y') : 'N/A',
                'Status'                => $r->status ?: 'N/A',
            ])->values()->all();

        return [
            'columns' => ['Employee ID', 'Employee Name', 'Department', 'Position', 'Announcement Title', 'Announcement Category', 'Publication Date', 'Status'],
            'rows'    => $rows,
        ];
    }

    public function announcementActivity(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();

        $rows = $this->baseQuery($rid, $scoped)
            ->when($f['category'] ?? null, fn($q) => $q->where('a.title', $f['category']))
            ->when($f['published_by'] ?? null, fn($q) => $q->where('a.created_by', $f['published_by']))
            ->when($f['from_date'] ?? null, fn($q) => $q->whereDate('a.published_date', '>=', $f['from_date']))
            ->when($f['to_date'] ?? null, fn($q) => $q->whereDate('a.published_date', '<=', $f['to_date']))
            ->orderByDesc('a.published_date')
            ->get([
                'a.message', 'e.Emp_id', DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as employee_name"),
                'd.name as dept', 'p.position_title', 'a.published_date', 'a.status',
            ])
            ->map(fn($r) => [
                'Announcement Title' => $r->message ? \Illuminate\Support\Str::limit($r->message, 60) : 'N/A',
                'Employee ID'        => $r->Emp_id ?: 'N/A',
                'Employee Name'      => trim($r->employee_name) ?: 'N/A',
                'Department'         => $r->dept ?? 'N/A',
                'Position'           => $r->position_title ?? 'N/A',
                'Publication Date'   => $r->published_date ? Carbon::parse($r->published_date)->format('d M Y') : 'N/A',
                'Status'             => $r->status ?: 'N/A',
            ])->values()->all();

        return [
            'columns' => ['Announcement Title', 'Employee ID', 'Employee Name', 'Department', 'Position', 'Publication Date', 'Status'],
            'rows'    => $rows,
        ];
    }
}
