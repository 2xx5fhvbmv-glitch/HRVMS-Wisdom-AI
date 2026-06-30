<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Resorts\Concerns\PredefinedReportActions;
use App\Helpers\Common;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Predefined Learning & Development reports (Option B), generic view.
 * "Completion" is derived from training_attendance.status='Present' — there is no
 * employee-program completion / score / certificate table, so those are N/A.
 */
class LearningReportController extends Controller
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
            'exec_summary'        => ['name' => 'Learning Executive Summary', 'description' => 'Programs, schedules and completion overview.', 'filters' => [], 'handler' => 'execSummary'],
            'program_status'      => ['name' => 'Learning Program Status Report', 'description' => 'Scheduled programs and their completion.', 'filters' => ['duration', 'category'], 'handler' => 'programStatus'],
            'program_completion'  => ['name' => 'Program Completion Report', 'description' => 'Per-employee completion.', 'filters' => ['program', 'duration'], 'handler' => 'programCompletion'],
            'mandatory_compliance' => ['name' => 'Mandatory Training Compliance Report', 'description' => 'Mandatory program compliance per employee.', 'filters' => ['department'], 'handler' => 'mandatoryCompliance'],
            'attendance'          => ['name' => 'Learning Attendance Report', 'description' => 'Attendance per session.', 'filters' => ['program', 'duration'], 'handler' => 'attendance'],
            'attendance_by_program' => ['name' => 'Learning Attendance by Program', 'description' => 'Attendance summary per program.', 'filters' => ['program'], 'handler' => 'attendanceByProgram'],
            'schedule'            => ['name' => 'Training Schedule Report', 'description' => 'Scheduled training sessions.', 'filters' => ['duration', 'trainer'], 'handler' => 'schedule'],
            'calendar'            => ['name' => 'Learning Calendar Report', 'description' => 'Sessions by month.', 'filters' => ['month', 'year'], 'handler' => 'calendar'],
            'history'             => ['name' => 'Learning History Report', 'description' => 'Completed learning per employee.', 'filters' => ['employee', 'duration'], 'handler' => 'history'],
            'employee_record'     => ['name' => 'Employee Learning Record', 'description' => 'Programs and hours per employee.', 'filters' => ['employee'], 'handler' => 'employeeRecord'],
            'onboarding_completion' => ['name' => 'Onboarding Training Completion Report', 'description' => 'Probationary program completion.', 'filters' => ['department'], 'handler' => 'onboardingCompletion'],
            'onboarding_progress' => ['name' => 'Onboarding Progress Report', 'description' => 'Onboarding module progress.', 'filters' => ['department'], 'handler' => 'onboardingProgress'],
            'learning_hours'      => ['name' => 'Learning Hours Report', 'description' => 'Learning hours per employee.', 'filters' => ['department'], 'handler' => 'learningHours'],
            'hours_by_program'    => ['name' => 'Learning Hours by Program', 'description' => 'Hours delivered per program.', 'filters' => [], 'handler' => 'hoursByProgram'],
            'program_library'     => ['name' => 'Program Library Report', 'description' => 'Catalogue of learning programs.', 'filters' => ['category'], 'handler' => 'programLibrary'],
            'learning_request'    => ['name' => 'Learning Request Report', 'description' => 'Training requests raised.', 'filters' => ['status', 'department'], 'handler' => 'learningRequest'],
            'request_approval'    => ['name' => 'Learning Request Approval Report', 'description' => 'Approval status of requests.', 'filters' => ['status'], 'handler' => 'requestApproval'],
            'pending_actions'     => ['name' => 'Pending Learning Actions Report', 'description' => 'Requests awaiting action.', 'filters' => [], 'handler' => 'pendingActions'],
            'feedback'            => ['name' => 'Feedback & Evaluation Report', 'description' => 'Training feedback submitted.', 'filters' => ['program'], 'handler' => 'feedback'],
            'effectiveness'       => ['name' => 'Learning Effectiveness Report', 'description' => 'Completion and attendance per program.', 'filters' => ['program'], 'handler' => 'effectiveness'],
            'gap_analysis'        => ['name' => 'Learning Gap Analysis Report', 'description' => 'Skill gaps (skills data not modelled).', 'filters' => ['department'], 'handler' => 'gapAnalysis'],
            'exec_dashboard'      => ['name' => 'Learning Executive Dashboard', 'description' => 'Headline learning metrics.', 'filters' => [], 'handler' => 'execDashboard'],
        ];
    }

    public function index()
    {
        if (Common::checkRouteWisePermission('resort.report.index', config('settings.resort_permissions.view')) == false) return abort(403, 'Unauthorized access');
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();
        $reports = collect($this->registry())->map(fn($r, $key) => ['key' => $key, 'name' => $r['name'], 'description' => $r['description'], 'filters' => $r['filters']])->values();

        $programs = DB::table('learning_programs')->where('resort_id', $rid)->orderBy('name')->get(['id', 'name']);
        $categories = DB::table('learning_categories')->where('resort_id', $rid)->orderBy('category')->get(['id', 'category']);
        $departments = DB::table('resort_departments')->where('resort_id', $rid)->when($scoped !== null, fn($q) => $q->whereIn('id', $scoped))->orderBy('name')->get(['id', 'name']);
        $employees = DB::table('employees as e')->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')->where('e.resort_id', $rid)->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))->orderBy('ra.first_name')->get(['e.id', DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as name")]);
        $trainers = DB::table('learning_programs as lp')->join('employees as e', 'e.id', '=', 'lp.trainer')->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')->where('lp.resort_id', $rid)->distinct()->get(['e.id', DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as name")]);

        $opt = fn($coll, $v, $l) => $coll->map(fn($x) => ['value' => $x->$v, 'label' => $x->$l])->all();
        $filterDefs = [
            ['filter' => 'program', 'name' => 'program', 'label' => 'Program', 'type' => 'select', 'placeholder' => 'All programs', 'options' => $opt($programs, 'id', 'name')],
            ['filter' => 'category', 'name' => 'category', 'label' => 'Category', 'type' => 'select', 'placeholder' => 'All categories', 'options' => $opt($categories, 'id', 'category')],
            ['filter' => 'department', 'name' => 'department', 'label' => 'Department', 'type' => 'select', 'placeholder' => 'All departments', 'options' => $opt($departments, 'id', 'name')],
            ['filter' => 'employee', 'name' => 'employee', 'label' => 'Employee', 'type' => 'select', 'placeholder' => 'All employees', 'options' => $opt($employees, 'id', 'name')],
            ['filter' => 'trainer', 'name' => 'trainer', 'label' => 'Trainer', 'type' => 'select', 'placeholder' => 'All trainers', 'options' => $opt($trainers, 'id', 'name')],
            ['filter' => 'status', 'name' => 'status', 'label' => 'Status', 'type' => 'select', 'placeholder' => 'All statuses', 'options' => collect(['Pending', 'Approved', 'Denied', 'On Hold'])->map(fn($s) => ['value' => $s, 'label' => $s])->all()],
            ['filter' => 'month', 'name' => 'month', 'label' => 'Month', 'type' => 'select', 'placeholder' => 'All months', 'options' => collect(range(1, 12))->map(fn($m) => ['value' => $m, 'label' => Carbon::create()->month($m)->format('F')])->all()],
            ['filter' => 'year', 'name' => 'year', 'label' => 'Year', 'type' => 'select', 'options' => collect(range((int) date('Y'), (int) date('Y') - 4))->map(fn($y) => ['value' => $y, 'label' => $y])->all()],
            ['filter' => 'duration', 'name' => 'from_date', 'label' => 'From Date', 'type' => 'date'],
            ['filter' => 'duration', 'name' => 'to_date', 'label' => 'To Date', 'type' => 'date'],
        ];

        return view('resorts.reports.module_report', [
            'page_title' => 'Learning & Development Reports', 'reports' => $reports, 'filterDefs' => $filterDefs,
            'runRoute' => 'resort.report.learning.run', 'exportRoute' => 'resort.report.learning.export', 'insightsRoute' => 'resort.report.learning.insights',
        ]);
    }

    private function filtersFrom(Request $request): array
    {
        return collect(['program', 'category', 'department', 'employee', 'trainer', 'status', 'month', 'year', 'from_date', 'to_date'])
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
    private function nm() { return DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as employee_name"); }
    private function applyDuration($q, array $f, string $col) { return $q->when($f['from_date'] ?? null, fn($x) => $x->whereDate($col, '>=', $f['from_date']))->when($f['to_date'] ?? null, fn($x) => $x->whereDate($col, '<=', $f['to_date'])); }
    private function pct($n, $d): string { return $d ? round($n / $d * 100, 1) . '%' : '0%'; }
    private function dmy($d): string { return $d ? Carbon::parse($d)->format('d M Y') : 'N/A'; }

    /** training_attendance joined to schedule->program, employee, dept. */
    private function attBase(array $f)
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();
        return DB::table('training_attendance as ta')
            ->join('training_schedules as ts', 'ts.id', '=', 'ta.training_schedule_id')
            ->join('learning_programs as lp', 'lp.id', '=', 'ts.training_id')
            ->leftJoin('learning_categories as lc', 'lc.id', '=', 'lp.learning_category_id')
            ->leftJoin('employees as e', 'e.id', '=', 'ta.employee_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->leftJoin('resort_departments as d', 'd.id', '=', 'e.Dept_id')
            ->where('ts.resort_id', $rid)
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))
            ->when($f['program'] ?? null, fn($q) => $q->where('lp.id', $f['program']))
            ->when($f['department'] ?? null, fn($q) => $q->where('e.Dept_id', $f['department']))
            ->when($f['employee'] ?? null, fn($q) => $q->where('e.id', $f['employee']));
    }

    /* reports */

    public function execSummary(array $f): array
    {
        $rid = $this->resort->resort_id;
        $total = DB::table('learning_programs')->where('resort_id', $rid)->count();
        $sched = DB::table('training_schedules')->where('resort_id', $rid)->count();
        $done = DB::table('training_schedules')->where('resort_id', $rid)->whereDate('end_date', '<', Carbon::today())->count();
        $pending = DB::table('training_schedules')->where('resort_id', $rid)->whereDate('end_date', '>=', Carbon::today())->count();
        $present = DB::table('training_attendance as ta')->join('training_schedules as ts','ts.id','=','ta.training_schedule_id')->where('ts.resort_id',$rid)->where('ta.status','Present')->count();
        $att = DB::table('training_attendance as ta')->join('training_schedules as ts','ts.id','=','ta.training_schedule_id')->where('ts.resort_id',$rid)->count();
        return ['columns' => ['Total Programs', 'Scheduled Programs', 'Completed Programs', 'Pending Programs', 'Overall Completion %'],
            'rows' => [['Total Programs' => $total, 'Scheduled Programs' => $sched, 'Completed Programs' => $done, 'Pending Programs' => $pending, 'Overall Completion %' => $this->pct($present, $att)]]];
    }

    public function programStatus(array $f): array
    {
        $rid = $this->resort->resort_id;
        $rows = DB::table('training_schedules as ts')
            ->join('learning_programs as lp', 'lp.id', '=', 'ts.training_id')
            ->leftJoin('learning_categories as lc', 'lc.id', '=', 'lp.learning_category_id')
            ->leftJoin('employees as te', 'te.id', '=', 'lp.trainer')
            ->leftJoin('resort_admins as tra', 'tra.id', '=', 'te.Admin_Parent_id')
            ->where('ts.resort_id', $rid)
            ->when($f['category'], fn($q) => $q->where('lp.learning_category_id', $f['category']))
            ->when(true, fn($q) => $this->applyDuration($q, $f, 'ts.start_date'))
            ->orderByDesc('ts.start_date')
            ->get(['ts.id', 'lp.name', 'lc.category', 'ts.start_date', 'ts.end_date', 'ts.status',
                DB::raw("TRIM(CONCAT(COALESCE(tra.first_name,''),' ',COALESCE(tra.last_name,''))) as trainer")])
            ->map(function ($r) {
                $tot = DB::table('training_attendance')->where('training_schedule_id', $r->id)->count();
                $pre = DB::table('training_attendance')->where('training_schedule_id', $r->id)->where('status', 'Present')->count();
                return [
                    'Program Name' => $r->name ?? 'N/A', 'Category' => $r->category ?? 'N/A',
                    'Start Date' => $this->dmy($r->start_date), 'End Date' => $this->dmy($r->end_date),
                    'Trainer' => trim($r->trainer) ?: 'N/A',
                    'Status' => $r->status ?: (Carbon::parse($r->end_date)->isPast() ? 'Completed' : 'Scheduled'),
                    'Completion %' => $this->pct($pre, $tot),
                ];
            })->all();
        return ['columns' => ['Program Name', 'Category', 'Start Date', 'End Date', 'Trainer', 'Status', 'Completion %'], 'rows' => $rows];
    }

    public function programCompletion(array $f): array
    {
        $rows = $this->attBase($f)->when(true, fn($q) => $this->applyDuration($q, $f, 'ta.attendance_date'))
            ->orderBy('ra.first_name')
            ->get([$this->nm(), 'lp.name', 'd.name as dept', 'ta.status', 'ta.attendance_date'])
            ->map(fn($r) => [
                'Employee Name' => trim($r->employee_name) ?: 'N/A', 'Program Name' => $r->name ?? 'N/A',
                'Department' => $r->dept ?? 'N/A',
                'Completion Status' => $r->status === 'Present' ? 'Completed' : $r->status,
                'Completion Date' => $r->status === 'Present' ? $this->dmy($r->attendance_date) : 'N/A',
            ])->all();
        return ['columns' => ['Employee Name', 'Program Name', 'Department', 'Completion Status', 'Completion Date'], 'rows' => $rows];
    }

    public function mandatoryCompliance(array $f): array
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();
        $rows = DB::table('mandatory_learning_programs as m')
            ->join('learning_programs as lp', 'lp.id', '=', 'm.program_id')
            ->leftJoin('employees as e', function ($j) { $j->on('e.Dept_id', '=', 'm.department_id'); })
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->where('m.resort_id', $rid)->whereNotNull('e.id')
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))
            ->when($f['department'], fn($q) => $q->where('e.Dept_id', $f['department']))
            ->get(['e.id as emp', $this->nm(), 'lp.id as pid', 'lp.name'])
            ->map(function ($r) {
                $done = DB::table('training_attendance as ta')->join('training_schedules as ts', 'ts.id', '=', 'ta.training_schedule_id')
                    ->where('ts.training_id', $r->pid)->where('ta.employee_id', $r->emp)->where('ta.status', 'Present')->exists();
                return [
                    'Employee Name' => trim($r->employee_name) ?: 'N/A', 'Mandatory Program' => $r->name ?? 'N/A',
                    'Due Date' => 'N/A', 'Completion Status' => $done ? 'Completed' : 'Pending',
                    'Compliance %' => $done ? '100%' : '0%',
                ];
            })->all();
        return ['columns' => ['Employee Name', 'Mandatory Program', 'Due Date', 'Completion Status', 'Compliance %'], 'rows' => $rows];
    }

    public function attendance(array $f): array
    {
        $rows = $this->attBase($f)->when(true, fn($q) => $this->applyDuration($q, $f, 'ta.attendance_date'))
            ->orderByDesc('ta.attendance_date')->limit(2000)
            ->get([$this->nm(), 'lp.name', 'ta.attendance_date', 'ta.status'])
            ->map(fn($r) => [
                'Employee Name' => trim($r->employee_name) ?: 'N/A', 'Program Name' => $r->name ?? 'N/A',
                'Session Date' => $this->dmy($r->attendance_date), 'Attendance Status' => $r->status ?? 'N/A',
                'Duration Attended' => 'N/A',
            ])->all();
        return ['columns' => ['Employee Name', 'Program Name', 'Session Date', 'Attendance Status', 'Duration Attended'], 'rows' => $rows];
    }

    public function attendanceByProgram(array $f): array
    {
        $rid = $this->resort->resort_id;
        $rows = DB::table('training_attendance as ta')
            ->join('training_schedules as ts', 'ts.id', '=', 'ta.training_schedule_id')
            ->join('learning_programs as lp', 'lp.id', '=', 'ts.training_id')
            ->where('ts.resort_id', $rid)->when($f['program'], fn($q) => $q->where('lp.id', $f['program']))
            ->groupBy('lp.id', 'lp.name')
            ->select('lp.name', DB::raw('COUNT(*) total'), DB::raw("SUM(CASE WHEN ta.status='Present' THEN 1 ELSE 0 END) attended"), DB::raw("SUM(CASE WHEN ta.status='Absent' THEN 1 ELSE 0 END) absent"))
            ->orderBy('lp.name')->get()
            ->map(fn($r) => [
                'Program Name' => $r->name ?? 'N/A', 'Total Participants' => (int) $r->total,
                'Attended' => (int) $r->attended, 'Absent' => (int) $r->absent, 'Attendance %' => $this->pct($r->attended, $r->total),
            ])->all();
        return ['columns' => ['Program Name', 'Total Participants', 'Attended', 'Absent', 'Attendance %'], 'rows' => $rows];
    }

    public function schedule(array $f): array
    {
        $rid = $this->resort->resort_id;
        $rows = DB::table('training_schedules as ts')->join('learning_programs as lp', 'lp.id', '=', 'ts.training_id')
            ->leftJoin('employees as te', 'te.id', '=', 'lp.trainer')->leftJoin('resort_admins as tra', 'tra.id', '=', 'te.Admin_Parent_id')
            ->where('ts.resort_id', $rid)->when($f['trainer'], fn($q) => $q->where('lp.trainer', $f['trainer']))
            ->when(true, fn($q) => $this->applyDuration($q, $f, 'ts.start_date'))->orderByDesc('ts.start_date')
            ->get(['lp.name', 'ts.start_date', 'ts.end_date', 'ts.start_time', 'ts.end_time', 'ts.venue',
                DB::raw("TRIM(CONCAT(COALESCE(tra.first_name,''),' ',COALESCE(tra.last_name,''))) as trainer")])
            ->map(fn($r) => [
                'Program Name' => $r->name ?? 'N/A', 'Trainer' => trim($r->trainer) ?: 'N/A',
                'Start Date' => $this->dmy($r->start_date), 'End Date' => $this->dmy($r->end_date),
                'Start Time' => $r->start_time ?? 'N/A', 'End Time' => $r->end_time ?? 'N/A', 'Location' => $r->venue ?? 'N/A',
            ])->all();
        return ['columns' => ['Program Name', 'Trainer', 'Start Date', 'End Date', 'Start Time', 'End Time', 'Location'], 'rows' => $rows];
    }

    public function calendar(array $f): array
    {
        $rid = $this->resort->resort_id;
        $rows = DB::table('training_schedules as ts')->join('learning_programs as lp', 'lp.id', '=', 'ts.training_id')
            ->leftJoin('employees as te', 'te.id', '=', 'lp.trainer')->leftJoin('resort_admins as tra', 'tra.id', '=', 'te.Admin_Parent_id')
            ->where('ts.resort_id', $rid)
            ->when($f['month'], fn($q) => $q->whereRaw('MONTH(ts.start_date)=?', [$f['month']]))
            ->when($f['year'], fn($q) => $q->whereRaw('YEAR(ts.start_date)=?', [$f['year']]))
            ->orderBy('ts.start_date')
            ->get(['ts.id', 'ts.start_date', 'lp.name', DB::raw("TRIM(CONCAT(COALESCE(tra.first_name,''),' ',COALESCE(tra.last_name,''))) as trainer")])
            ->map(fn($r) => [
                'Date' => $this->dmy($r->start_date), 'Program Name' => $r->name ?? 'N/A',
                'Trainer' => trim($r->trainer) ?: 'N/A', 'Department' => 'N/A',
                'Attendee Count' => DB::table('training_attendance')->where('training_schedule_id', $r->id)->count(),
            ])->all();
        return ['columns' => ['Date', 'Program Name', 'Trainer', 'Department', 'Attendee Count'], 'rows' => $rows];
    }

    public function history(array $f): array
    {
        $rows = $this->attBase($f)->where('ta.status', 'Present')->when(true, fn($q) => $this->applyDuration($q, $f, 'ta.attendance_date'))
            ->orderByDesc('ta.attendance_date')
            ->get([$this->nm(), 'lp.name', 'lc.category', 'ta.attendance_date'])
            ->map(fn($r) => [
                'Employee Name' => trim($r->employee_name) ?: 'N/A', 'Program Name' => $r->name ?? 'N/A',
                'Category' => $r->category ?? 'N/A', 'Completion Date' => $this->dmy($r->attendance_date),
                'Score' => 'N/A', 'Certificate Status' => 'N/A',
            ])->all();
        return ['columns' => ['Employee Name', 'Program Name', 'Category', 'Completion Date', 'Score', 'Certificate Status'], 'rows' => $rows];
    }

    public function employeeRecord(array $f): array
    {
        $rows = $this->attBase($f)->where('ta.status', 'Present')
            ->groupBy('e.id', 'ra.first_name', 'ra.last_name')->orderBy('ra.first_name')
            ->select([$this->nm(), DB::raw('COUNT(DISTINCT lp.id) programs'), DB::raw('SUM(lp.hours) hours')])->get()
            ->map(fn($r) => [
                'Employee Name' => trim($r->employee_name) ?: 'N/A', 'Programs Attended' => (int) $r->programs,
                'Completion Status' => 'Completed', 'Hours Completed' => number_format((float) ($r->hours ?? 0), 1),
                'Certifications' => 'N/A',
            ])->all();
        return ['columns' => ['Employee Name', 'Programs Attended', 'Completion Status', 'Hours Completed', 'Certifications'], 'rows' => $rows];
    }

    private function onboardingRows(array $f)
    {
        $rid = $this->resort->resort_id;
        $scoped = Common::getScopedDepartmentIds();
        return DB::table('probationary_learning_programs as pp')
            ->join('learning_programs as lp', 'lp.id', '=', 'pp.program_id')
            ->join('employees as e', function ($j) use ($rid) { $j->on('e.resort_id', '=', 'pp.resort_id')->where('e.probation_status', '=', 'Active'); })
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->where('pp.resort_id', $rid)
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))
            ->when($f['department'] ?? null, fn($q) => $q->where('e.Dept_id', $f['department']));
    }

    public function onboardingCompletion(array $f): array
    {
        $rows = $this->onboardingRows($f)->orderBy('ra.first_name')
            ->get(['e.id as emp', $this->nm(), 'lp.id as pid', 'lp.name'])
            ->map(function ($r) {
                $done = DB::table('training_attendance as ta')->join('training_schedules as ts', 'ts.id', '=', 'ta.training_schedule_id')
                    ->where('ts.training_id', $r->pid)->where('ta.employee_id', $r->emp)->where('ta.status', 'Present')->first();
                return [
                    'Employee Name' => trim($r->employee_name) ?: 'N/A', 'Program Name' => $r->name ?? 'N/A',
                    'Completion Status' => $done ? 'Completed' : 'Pending',
                    'Completion Date' => $done ? $this->dmy($done->attendance_date) : 'N/A',
                ];
            })->all();
        return ['columns' => ['Employee Name', 'Program Name', 'Completion Status', 'Completion Date'], 'rows' => $rows];
    }

    public function onboardingProgress(array $f): array
    {
        $grouped = collect($this->onboardingCompletion($f)['rows'])->groupBy('Employee Name');
        $rows = $grouped->map(function ($items, $name) {
            $done = $items->where('Completion Status', 'Completed')->count();
            $total = $items->count();
            return [
                'Employee Name'   => $name,
                'Onboarding Stage' => $done >= $total ? 'Complete' : 'In Progress',
                'Completed Modules' => $done,
                'Pending Modules' => $total - $done,
            ];
        })->values()->all();
        return ['columns' => ['Employee Name', 'Onboarding Stage', 'Completed Modules', 'Pending Modules'], 'rows' => $rows];
    }

    public function learningHours(array $f): array
    {
        $rows = $this->attBase($f)->where('ta.status', 'Present')
            ->groupBy('e.id', 'ra.first_name', 'ra.last_name', 'd.name')->orderBy('ra.first_name')
            ->select([$this->nm(), 'd.name as dept', DB::raw('SUM(lp.hours) hours'), DB::raw('COUNT(DISTINCT lp.id) progs')])->get()
            ->map(fn($r) => [
                'Employee Name' => trim($r->employee_name) ?: 'N/A', 'Department' => $r->dept ?? 'N/A',
                'Total Learning Hours' => number_format((float) ($r->hours ?? 0), 1),
                'Program Hours Breakdown' => $r->progs . ' program(s)',
            ])->all();
        return ['columns' => ['Employee Name', 'Department', 'Total Learning Hours', 'Program Hours Breakdown'], 'rows' => $rows];
    }

    public function hoursByProgram(array $f): array
    {
        $rid = $this->resort->resort_id;
        $rows = DB::table('learning_programs as lp')->where('lp.resort_id', $rid)->orderBy('lp.name')->get(['lp.id', 'lp.name', 'lp.hours'])
            ->map(function ($r) {
                $sessions = DB::table('training_schedules')->where('training_id', $r->id)->count();
                $parts = DB::table('training_attendance as ta')->join('training_schedules as ts', 'ts.id', '=', 'ta.training_schedule_id')->where('ts.training_id', $r->id)->count();
                return [
                    'Program Name' => $r->name ?? 'N/A',
                    'Total Hours Delivered' => number_format((float) ($r->hours ?? 0) * $sessions, 1),
                    'Number of Sessions' => $sessions, 'Total Participants' => $parts,
                ];
            })->all();
        return ['columns' => ['Program Name', 'Total Hours Delivered', 'Number of Sessions', 'Total Participants'], 'rows' => $rows];
    }

    public function programLibrary(array $f): array
    {
        $rid = $this->resort->resort_id;
        $rows = DB::table('learning_programs as lp')->leftJoin('learning_categories as lc', 'lc.id', '=', 'lp.learning_category_id')
            ->where('lp.resort_id', $rid)->when($f['category'], fn($q) => $q->where('lp.learning_category_id', $f['category']))
            ->orderBy('lp.name')->get(['lp.name', 'lc.category', 'lp.audience_type', 'lp.hours', 'lp.days', 'lp.frequency', 'lp.delivery_mode'])
            ->map(fn($r) => [
                'Program Name' => $r->name ?? 'N/A', 'Category' => $r->category ?? 'N/A',
                'Target Audience' => $r->audience_type ?? 'N/A',
                'Duration' => trim(($r->hours ? $r->hours . 'h ' : '') . ($r->days ? $r->days . 'd' : '')) ?: 'N/A',
                'Frequency' => $r->frequency ?? 'N/A', 'Delivery Mode' => $r->delivery_mode ?? 'N/A',
            ])->all();
        return ['columns' => ['Program Name', 'Category', 'Target Audience', 'Duration', 'Frequency', 'Delivery Mode'], 'rows' => $rows];
    }

    private function requestBase(array $f)
    {
        $rid = $this->resort->resort_id;
        return DB::table('learning_requests as lr')
            ->leftJoin('learning_programs as lp', 'lp.id', '=', 'lr.learning_id')
            ->leftJoin('resort_admins as req', 'req.id', '=', 'lr.created_by')
            ->leftJoin('employees as me', 'me.id', '=', 'lr.learning_manager_id')
            ->leftJoin('resort_admins as mra', 'mra.id', '=', 'me.Admin_Parent_id')
            ->where('lr.resort_id', $rid)
            ->when($f['status'] ?? null, fn($q) => $q->where('lr.status', $f['status']));
    }

    public function learningRequest(array $f): array
    {
        $rows = $this->requestBase($f)->orderByDesc('lr.created_at')
            ->get(['lp.name', 'lr.reason', 'lr.status', 'lr.start_date', 'lr.end_date',
                DB::raw("TRIM(CONCAT(COALESCE(req.first_name,''),' ',COALESCE(req.last_name,''))) as requested_by")])
            ->map(fn($r) => [
                'Employee Name' => trim($r->requested_by) ?: 'N/A', 'Requested Program' => $r->name ?? 'N/A',
                'Reason' => $r->reason ?? 'N/A', 'Requested By' => trim($r->requested_by) ?: 'N/A',
                'Status' => $r->status ?? 'N/A',
                'Requested Dates' => ($r->start_date ? $this->dmy($r->start_date) : '') . ($r->end_date ? ' – ' . $this->dmy($r->end_date) : '') ?: 'N/A',
            ])->all();
        return ['columns' => ['Employee Name', 'Requested Program', 'Reason', 'Requested By', 'Status', 'Requested Dates'], 'rows' => $rows];
    }

    public function requestApproval(array $f): array
    {
        $rows = $this->requestBase($f)->orderByDesc('lr.updated_at')
            ->get(['lp.name', 'lr.status', 'lr.updated_at',
                DB::raw("TRIM(CONCAT(COALESCE(req.first_name,''),' ',COALESCE(req.last_name,''))) as requested_by"),
                DB::raw("TRIM(CONCAT(COALESCE(mra.first_name,''),' ',COALESCE(mra.last_name,''))) as approver")])
            ->map(fn($r) => [
                'Employee Name' => trim($r->requested_by) ?: 'N/A', 'Program Name' => $r->name ?? 'N/A',
                'Requested By' => trim($r->requested_by) ?: 'N/A', 'Approver' => trim($r->approver) ?: 'N/A',
                'Approval Status' => $r->status ?? 'N/A',
                'Approval Date' => in_array($r->status, ['Approved', 'Denied']) ? $this->dmy($r->updated_at) : 'N/A',
            ])->all();
        return ['columns' => ['Employee Name', 'Program Name', 'Requested By', 'Approver', 'Approval Status', 'Approval Date'], 'rows' => $rows];
    }

    public function pendingActions(array $f): array
    {
        $rows = $this->requestBase(['status' => 'Pending'])->orderByDesc('lr.created_at')
            ->get(['lp.name', 'lr.reason', 'lr.start_date', DB::raw("TRIM(CONCAT(COALESCE(req.first_name,''),' ',COALESCE(req.last_name,''))) as requested_by")])
            ->map(fn($r) => [
                'Program Name' => $r->name ?? 'N/A', 'Reason for Pending' => $r->reason ?? 'Awaiting approval',
                'Requested By' => trim($r->requested_by) ?: 'N/A', 'Priority' => 'N/A',
                'Suggested Date' => $this->dmy($r->start_date),
            ])->all();
        return ['columns' => ['Program Name', 'Reason for Pending', 'Requested By', 'Priority', 'Suggested Date'], 'rows' => $rows];
    }

    public function feedback(array $f): array
    {
        $rid = $this->resort->resort_id;
        $rows = DB::table('training_feedback_responses as fr')
            ->leftJoin('employees as e', 'e.id', '=', 'fr.participant_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->leftJoin('training_schedules as ts', 'ts.id', '=', 'fr.training_id')
            ->leftJoin('learning_programs as lp', 'lp.id', '=', 'ts.training_id')
            ->when($f['program'], fn($q) => $q->where('lp.id', $f['program']))
            ->orderByDesc('fr.created_at')->limit(1000)
            ->get([$this->nm(), 'lp.name'])
            ->map(fn($r) => [
                'Employee Name' => trim($r->employee_name) ?: 'N/A', 'Program Name' => $r->name ?? 'N/A',
                'Trainer Rating' => 'N/A', 'Content Rating' => 'N/A', 'Feedback Comments' => 'Submitted',
            ])->all();
        return ['columns' => ['Employee Name', 'Program Name', 'Trainer Rating', 'Content Rating', 'Feedback Comments'], 'rows' => $rows];
    }

    public function effectiveness(array $f): array
    {
        $rid = $this->resort->resort_id;
        $rows = DB::table('learning_programs as lp')->where('lp.resort_id', $rid)->when($f['program'], fn($q) => $q->where('lp.id', $f['program']))
            ->orderBy('lp.name')->get(['lp.id', 'lp.name'])
            ->map(function ($r) {
                $tot = DB::table('training_attendance as ta')->join('training_schedules as ts', 'ts.id', '=', 'ta.training_schedule_id')->where('ts.training_id', $r->id)->count();
                $pre = DB::table('training_attendance as ta')->join('training_schedules as ts', 'ts.id', '=', 'ta.training_schedule_id')->where('ts.training_id', $r->id)->where('ta.status', 'Present')->count();
                return [
                    'Program Name' => $r->name ?? 'N/A', 'Completion Rate' => $this->pct($pre, $tot),
                    'Average Feedback Score' => 'N/A', 'Attendance Rate' => $this->pct($pre, $tot),
                ];
            })->all();
        return ['columns' => ['Program Name', 'Completion Rate', 'Average Feedback Score', 'Attendance Rate'], 'rows' => $rows];
    }

    public function gapAnalysis(array $f): array
    {
        // No skills / required-competency data in the schema.
        return ['columns' => ['Role', 'Required Skills', 'Missing Programs', 'Gap Severity'], 'rows' => []];
    }

    public function execDashboard(array $f): array
    {
        $rid = $this->resort->resort_id;
        $present = DB::table('training_attendance as ta')->join('training_schedules as ts','ts.id','=','ta.training_schedule_id')->where('ts.resort_id',$rid)->where('ta.status','Present')->count();
        $att = DB::table('training_attendance as ta')->join('training_schedules as ts','ts.id','=','ta.training_schedule_id')->where('ts.resort_id',$rid)->count();
        $active = DB::table('training_schedules')->where('resort_id', $rid)->whereDate('end_date', '>=', Carbon::today())->count();
        $pendingReq = DB::table('learning_requests')->where('resort_id', $rid)->where('status', 'Pending')->count();
        $hours = (float) DB::table('training_attendance as ta')->join('training_schedules as ts', 'ts.id', '=', 'ta.training_schedule_id')->join('learning_programs as lp', 'lp.id', '=', 'ts.training_id')->where('ts.resort_id', $rid)->where('ta.status', 'Present')->sum('lp.hours');
        return ['columns' => ['Completion Rate', 'Attendance Rate', 'Active Programs', 'Pending Requests', 'Learning Hours', 'Compliance Rate'],
            'rows' => [['Completion Rate' => $this->pct($present, $att), 'Attendance Rate' => $this->pct($present, $att), 'Active Programs' => $active, 'Pending Requests' => $pendingReq, 'Learning Hours' => number_format($hours, 1), 'Compliance Rate' => $this->pct($present, $att)]]];
    }
}
