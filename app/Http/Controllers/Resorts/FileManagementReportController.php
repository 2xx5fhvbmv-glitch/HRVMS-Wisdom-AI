<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Resorts\Concerns\PredefinedReportActions;
use App\Helpers\Common;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Predefined File Management reports (Option B). Dept-scoped queries returning
 * { columns, rows } for the shared ReportFilterData partial; reuses the
 * PredefinedReportActions trait (export + WAI insights + totals row).
 *
 * The schema has no file-sharing or e-signature tables, so "Shared Files" and
 * "Unsigned Documents" are shown as 0 / empty rather than fabricated.
 */
class FileManagementReportController extends Controller
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
            'exec_summary'         => ['name' => 'File Management Executive Summary', 'description' => 'Overview of documents, folders, uploads and sharing.', 'filters' => ['duration'], 'handler' => 'execSummary'],
            'doc_register'         => ['name' => 'Employee Document Register', 'description' => 'All employee documents on file.', 'filters' => ['department', 'employee'], 'handler' => 'docRegister'],
            'folder_report'        => ['name' => 'Employee Folder Report', 'description' => 'Each employee folder and its document count.', 'filters' => ['department'], 'handler' => 'folderReport'],
            'doc_inventory'        => ['name' => 'Employee Document Inventory', 'description' => 'Which document types each employee has.', 'filters' => ['employee'], 'handler' => 'docInventory'],
            'recent_uploads'       => ['name' => 'Recently Uploaded Documents Report', 'description' => 'Documents uploaded in the period.', 'filters' => ['duration'], 'handler' => 'recentUploads'],
            'recent_modified'      => ['name' => 'Recently Modified Documents Report', 'description' => 'Documents modified in the period.', 'filters' => ['duration'], 'handler' => 'recentModified'],
            'modification_history' => ['name' => 'File Modification History Report', 'description' => 'Version history for stored files.', 'filters' => ['document_name'], 'handler' => 'modificationHistory'],
            'sharing_activity'     => ['name' => 'File Sharing Activity Report', 'description' => 'File sharing events (no sharing data in the system).', 'filters' => ['duration'], 'handler' => 'sharingActivity'],
            'storage_utilization'  => ['name' => 'Storage Utilization Report', 'description' => 'Files and storage used per department.', 'filters' => ['department'], 'handler' => 'storageUtilization'],
            'doc_summary'          => ['name' => 'Employee Document Summary', 'description' => 'Per-employee document totals.', 'filters' => ['employee'], 'handler' => 'docSummary'],
        ];
    }

    /* --------------------------------------------------------------- plumbing */

    public function index()
    {
        if (Common::checkRouteWisePermission('resort.report.index', config('settings.resort_permissions.view')) == false) {
            return abort(403, 'Unauthorized access');
        }
        $page_title = 'File Management Reports';
        $resortId   = $this->resort->resort_id;
        $scoped     = Common::getScopedDepartmentIds();

        $reports = collect($this->registry())->map(fn($r, $key) => [
            'key' => $key, 'name' => $r['name'], 'description' => $r['description'],
            // Every report exposes the duration (From/To date) filter, like the Custom Report.
            'filters' => array_values(array_unique(array_merge($r['filters'], ['duration']))),
        ])->values();

        $departments = DB::table('resort_departments')->where('resort_id', $resortId)
            ->when($scoped !== null, fn($q) => $q->whereIn('id', $scoped))
            ->orderBy('name')->get(['id', 'name']);

        $employees = DB::table('employees as e')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->where('e.resort_id', $resortId)
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))
            ->orderBy('ra.first_name')
            ->get(['e.id', DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as name")]);

        return view('resorts.reports.file_management', compact('page_title', 'reports', 'departments', 'employees'));
    }

    private function filtersFrom(Request $request): array
    {
        return [
            'department'    => $request->input('department') ?: null,
            'employee'      => $request->input('employee') ?: null,
            'document_name' => $request->input('document_name') ?: null,
            'from_date'     => $request->input('from_date') ?: null,
            'to_date'       => $request->input('to_date') ?: null,
        ];
    }

    private function compute(string $key, array $filters): ?array
    {
        $registry = $this->registry();
        if (!isset($registry[$key])) return null;
        $res = $this->{$registry[$key]['handler']}($filters);
        return [
            'name' => $registry[$key]['name'], 'description' => $registry[$key]['description'],
            'columns' => $res['columns'], 'rows' => $this->appendTotalsRow($res['columns'], $res['rows']),
        ];
    }

    public function run(Request $request)
    {
        if (Common::checkRouteWisePermission('resort.report.index', config('settings.resort_permissions.view')) == false) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
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

    private function nameExpr()
    {
        return DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as employee_name");
    }

    private function applyDuration($q, array $f, string $col)
    {
        return $q->when($f['from_date'] ?? null, fn($x) => $x->whereDate($col, '>=', $f['from_date']))
                 ->when($f['to_date'] ?? null, fn($x) => $x->whereDate($col, '<=', $f['to_date']));
    }

    /** Employee-documents base, dept-scoped. */
    private function docBase(array $f)
    {
        $scoped = Common::getScopedDepartmentIds();
        return DB::table('employees_documents as ed')
            ->join('employees as e', 'e.id', '=', 'ed.employee_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->leftJoin('resort_departments as d', 'd.id', '=', 'e.Dept_id')
            ->where('ed.resort_id', $this->resort->resort_id)
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))
            ->when($f['department'] ?? null, fn($q) => $q->where('e.Dept_id', $f['department']))
            ->when($f['employee'] ?? null, fn($q) => $q->where('e.id', $f['employee']));
    }

    private function fileSize($mb): string
    {
        return number_format((float) $mb, 2) . ' MB';
    }

    /* --------------------------------------------------------------- reports */

    /** #1 Executive Summary. */
    public function execSummary(array $f): array
    {
        $rid = $this->resort->resort_id;
        $base = $this->docBase($f);
        $this->applyDuration($base, $f, 'ed.created_at');
        $totalDocs = (clone $base)->count();
        $folders = DB::table('filemangement_systems')->where('resort_id', $rid)->where('Folder_Type', 'categorized')
            ->when(true, fn($q) => $this->applyDuration($q, $f, 'created_at'))->count();
        $recent = (clone $base)->where('ed.created_at', '>=', Carbon::today()->subDays(30))->count();
        return [
            'columns' => ['Total Documents', 'Total Employee Folders', 'Unsigned Documents', 'Shared Files', 'Recent Uploads (30d)'],
            'rows'    => [[
                'Total Documents'       => $totalDocs,
                'Total Employee Folders' => $folders,
                'Unsigned Documents'    => 'N/A',
                'Shared Files'          => 'N/A',
                'Recent Uploads (30d)'  => $recent,
            ]],
        ];
    }

    /** #2 Employee Document Register. */
    public function docRegister(array $f): array
    {
        $rid = $this->resort->resort_id;
        $rows = $this->docBase($f)
            ->when(true, fn($q) => $this->applyDuration($q, $f, 'ed.created_at'))
            ->orderBy('ra.first_name')->orderByDesc('ed.created_at')
            ->get(['e.Emp_id', $this->nameExpr(), 'ed.id as doc_id', 'ed.document_title', 'ed.document_category', 'ed.created_at'])
            ->map(function ($r) use ($rid) {
                $ver = DB::table('file_versions')->where('resort_id', $rid)->where('file_id', $r->doc_id)->max('version_number');
                return [
                    'Employee ID'       => $r->Emp_id,
                    'Employee Name'     => trim($r->employee_name) ?: 'N/A',
                    'Document Name'     => $r->document_title ?? 'N/A',
                    'Document Category' => $r->document_category ?? 'N/A',
                    'Upload Date'       => $r->created_at ? Carbon::parse($r->created_at)->format('d M Y') : 'N/A',
                    'Current Version'   => $ver ? 'v' . $ver : 'v1',
                ];
            })->all();
        return ['columns' => ['Employee ID', 'Employee Name', 'Document Name', 'Document Category', 'Upload Date', 'Current Version'], 'rows' => $rows];
    }

    /** #3 Employee Folder Report. */
    public function folderReport(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();
        $rows = DB::table('filemangement_systems as fs')
            ->join('employees as e', function ($j) use ($rid) {
                $j->on('e.Emp_id', '=', 'fs.Folder_Name')->where('e.resort_id', '=', $rid);
            })
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->where('fs.resort_id', $rid)->where('fs.Folder_Type', 'categorized')
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))
            ->when($f['department'], fn($q) => $q->where('e.Dept_id', $f['department']))
            ->when(true, fn($q) => $this->applyDuration($q, $f, 'fs.created_at'))
            ->leftJoin('child_file_management as c', 'c.Parent_File_ID', '=', 'fs.id')
            ->groupBy('fs.id', 'fs.Folder_Name', 'fs.updated_at', 'ra.first_name', 'ra.last_name')
            ->orderBy('ra.first_name')
            ->get([$this->nameExpr(), 'fs.Folder_Name', 'fs.updated_at', DB::raw('COUNT(c.id) as doc_count')])
            ->map(fn($r) => [
                'Employee Name'     => trim($r->employee_name) ?: 'N/A',
                'Folder Name'       => $r->Folder_Name,
                'Total Documents'   => (int) $r->doc_count,
                'Last Updated Date' => $r->updated_at ? Carbon::parse($r->updated_at)->format('d M Y') : 'N/A',
            ])->all();
        return ['columns' => ['Employee Name', 'Folder Name', 'Total Documents', 'Last Updated Date'], 'rows' => $rows];
    }

    /** #4 Employee Document Inventory. */
    public function docInventory(array $f): array
    {
        $scoped = Common::getScopedDepartmentIds();
        $records = DB::table('employees as e')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->leftJoin('employees_documents as ed', 'ed.employee_id', '=', 'e.id')
            ->where('e.resort_id', $this->resort->resort_id)
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))
            ->when($f['employee'], fn($q) => $q->where('e.id', $f['employee']))
            ->when(true, fn($q) => $this->applyDuration($q, $f, 'ed.created_at'))
            ->groupBy('e.id', 'ra.first_name', 'ra.last_name')
            ->orderBy('ra.first_name')
            ->get([$this->nameExpr(), DB::raw("GROUP_CONCAT(LOWER(COALESCE(ed.document_title,'')) SEPARATOR '|') as titles")]);

        $has = fn($titles, $kw) => stripos((string) $titles, $kw) !== false ? 'Yes' : 'No';
        $known = ['contract', 'passport', 'visa', 'qualif', 'cv', 'experience', 'resignation'];
        $rows = $records->map(function ($r) use ($has, $known) {
            $titles = (string) $r->titles;
            $other = 'No';
            foreach (explode('|', $titles) as $t) {
                $t = trim($t);
                if ($t === '') continue;
                if (!collect($known)->contains(fn($k) => stripos($t, $k) !== false)) { $other = 'Yes'; break; }
            }
            return [
                'Employee Name'      => trim($r->employee_name) ?: 'N/A',
                'Contract'           => $has($titles, 'contract'),
                'Passport'           => $has($titles, 'passport'),
                'Visa'               => $has($titles, 'visa'),
                'Qualifications'     => $has($titles, 'qualif'),
                'CV'                 => $has($titles, 'cv'),
                'Experience Letters' => $has($titles, 'experience'),
                'Resignation Letter' => $has($titles, 'resignation'),
                'Other Documents'    => $other,
            ];
        })->all();
        return ['columns' => ['Employee Name', 'Contract', 'Passport', 'Visa', 'Qualifications', 'CV', 'Experience Letters', 'Resignation Letter', 'Other Documents'], 'rows' => $rows];
    }

    /** #5 Recently Uploaded Documents. */
    public function recentUploads(array $f): array
    {
        $rows = $this->docBase($f)
            ->leftJoin('resort_admins as up', 'up.id', '=', 'ed.created_by')
            ->when(true, fn($q) => $this->applyDuration($q, $f, 'ed.created_at'))
            ->orderByDesc('ed.created_at')->limit(1000)
            ->get(['ed.document_title', $this->nameExpr(), 'ed.created_at',
                DB::raw("TRIM(CONCAT(COALESCE(up.first_name,''),' ',COALESCE(up.last_name,''))) as uploaded_by")])
            ->map(fn($r) => [
                'Document Name' => $r->document_title ?? 'N/A',
                'Employee Name' => trim($r->employee_name) ?: 'N/A',
                'Uploaded By'   => trim($r->uploaded_by) ?: 'N/A',
                'Upload Date'   => $r->created_at ? Carbon::parse($r->created_at)->format('d M Y H:i') : 'N/A',
            ])->all();
        return ['columns' => ['Document Name', 'Employee Name', 'Uploaded By', 'Upload Date'], 'rows' => $rows];
    }

    /** #6 Recently Modified Documents. */
    public function recentModified(array $f): array
    {
        $rows = $this->docBase($f)
            ->leftJoin('resort_admins as mb', 'mb.id', '=', 'ed.modified_by')
            ->whereColumn('ed.updated_at', '<>', 'ed.created_at')
            ->when(true, fn($q) => $this->applyDuration($q, $f, 'ed.updated_at'))
            ->orderByDesc('ed.updated_at')->limit(1000)
            ->get(['ed.document_title', 'ed.updated_at',
                DB::raw("TRIM(CONCAT(COALESCE(mb.first_name,''),' ',COALESCE(mb.last_name,''))) as modified_by")])
            ->map(fn($r) => [
                'Document Name'     => $r->document_title ?? 'N/A',
                'Modified By'       => trim($r->modified_by) ?: 'N/A',
                'Modification Date' => $r->updated_at ? Carbon::parse($r->updated_at)->format('d M Y H:i') : 'N/A',
            ])->all();
        return ['columns' => ['Document Name', 'Modified By', 'Modification Date'], 'rows' => $rows];
    }

    /** #7 File Modification History. */
    public function modificationHistory(array $f): array
    {
        $rows = DB::table('file_versions as v')
            ->leftJoin('child_file_management as c', 'c.id', '=', 'v.file_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'v.created_by')
            ->where('v.resort_id', $this->resort->resort_id)
            ->when($f['document_name'], fn($q) => $q->where('c.File_Name', 'like', '%' . $f['document_name'] . '%'))
            ->when(true, fn($q) => $this->applyDuration($q, $f, 'v.created_at'))
            ->orderBy('c.File_Name')->orderBy('v.version_number')
            ->get(['c.File_Name', 'v.version_number', 'v.created_at',
                DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as modified_by")])
            ->map(fn($r) => [
                'File Name'         => $r->File_Name ?? 'N/A',
                'Version'           => 'v' . ($r->version_number ?? 1),
                'Modified By'       => trim($r->modified_by) ?: 'N/A',
                'Modification Date' => $r->created_at ? Carbon::parse($r->created_at)->format('d M Y H:i') : 'N/A',
            ])->all();
        return ['columns' => ['File Name', 'Version', 'Modified By', 'Modification Date'], 'rows' => $rows];
    }

    /** #8 File Sharing Activity — no sharing data exists in the schema. */
    public function sharingActivity(array $f): array
    {
        return ['columns' => ['File Name', 'Shared By', 'Recipient', 'Share Date'], 'rows' => []];
    }

    /** #9 Storage Utilization (per department). */
    public function storageUtilization(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();
        $rows = DB::table('child_file_management as c')
            ->join('filemangement_systems as fs', 'fs.id', '=', 'c.Parent_File_ID')
            ->join('employees as e', function ($j) use ($rid) {
                $j->on('e.Emp_id', '=', 'fs.Folder_Name')->where('e.resort_id', '=', $rid);
            })
            ->leftJoin('resort_departments as d', 'd.id', '=', 'e.Dept_id')
            ->where('c.resort_id', $rid)->where('fs.Folder_Type', 'categorized')
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))
            ->when($f['department'], fn($q) => $q->where('e.Dept_id', $f['department']))
            ->when(true, fn($q) => $this->applyDuration($q, $f, 'c.created_at'))
            ->groupBy('e.Dept_id', 'd.name')
            ->orderBy('d.name')
            ->get(['d.name as dept', DB::raw('COUNT(c.id) as files'), DB::raw('SUM(c.File_Size) as storage')])
            ->map(fn($r) => [
                'Department'   => $r->dept ?? 'N/A',
                'Total Files'  => (int) $r->files,
                'Storage Used' => $this->fileSize($r->storage ?? 0),
            ])->all();
        return ['columns' => ['Department', 'Total Files', 'Storage Used'], 'rows' => $rows];
    }

    /** #10 Employee Document Summary. */
    public function docSummary(array $f): array
    {
        $rows = $this->docBase($f)
            ->when(true, fn($q) => $this->applyDuration($q, $f, 'ed.created_at'))
            ->groupBy('e.id', 'ra.first_name', 'ra.last_name')
            ->orderBy('ra.first_name')
            ->get([$this->nameExpr(), DB::raw('COUNT(ed.id) as total'), DB::raw('MAX(ed.updated_at) as last_updated'),
                DB::raw('MAX(ed.document_title) as latest_doc')])
            ->map(fn($r) => [
                'Employee Name'  => trim($r->employee_name) ?: 'N/A',
                'Total Documents' => (int) $r->total,
                'File Name'      => $r->latest_doc ?? 'N/A',
                'Last Updated'   => $r->last_updated ? Carbon::parse($r->last_updated)->format('d M Y') : 'N/A',
            ])->all();
        return ['columns' => ['Employee Name', 'Total Documents', 'File Name', 'Last Updated'], 'rows' => $rows];
    }
}
