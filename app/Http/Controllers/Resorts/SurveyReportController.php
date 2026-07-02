<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Resorts\Concerns\PredefinedReportActions;
use App\Helpers\Common;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Predefined Survey reports (Option B). Reuses the PredefinedReportActions trait
 * (export + WAI insights + totals row). Surveys are resort-wide.
 *
 * The schema has no survey "category" or "distribution method" fields, so those
 * use survey_privacy_type (the only classification) / show N/A rather than fake data.
 */
class SurveyReportController extends Controller
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
            'exec_summary'        => ['name' => 'Survey Executive Summary', 'description' => 'Overview of surveys, participation and completion.', 'filters' => [], 'handler' => 'execSummary'],
            'register'            => ['name' => 'Survey Register', 'description' => 'All surveys with status and participants.', 'filters' => ['duration', 'survey_status'], 'handler' => 'register'],
            'published'           => ['name' => 'Published Survey Report', 'description' => 'Published / ongoing surveys.', 'filters' => [], 'handler' => 'published'],
            'draft'               => ['name' => 'Draft Survey Report', 'description' => 'Surveys saved as draft.', 'filters' => [], 'handler' => 'draft'],
            'completed'           => ['name' => 'Completed Survey Report', 'description' => 'Completed surveys.', 'filters' => ['duration'], 'handler' => 'completed'],
            'open'                => ['name' => 'Open Survey Report', 'description' => 'Currently open surveys.', 'filters' => [], 'handler' => 'open'],
            'expiring'            => ['name' => 'Expiring Survey Report', 'description' => 'Surveys closing soon.', 'filters' => [], 'handler' => 'expiring'],
            'participation'       => ['name' => 'Survey Participation Report', 'description' => 'Invited vs responded per survey.', 'filters' => ['survey'], 'handler' => 'participation'],
            'response_rate'       => ['name' => 'Survey Response Rate Report', 'description' => 'Response rate across surveys.', 'filters' => ['duration'], 'handler' => 'responseRate'],
            'completion'          => ['name' => 'Survey Completion Report', 'description' => 'Per-participant completion for a survey.', 'filters' => ['survey'], 'handler' => 'completion'],
            'results_summary'     => ['name' => 'Survey Results Summary', 'description' => 'Responses and average rating per survey.', 'filters' => [], 'handler' => 'resultsSummary'],
            'question_analysis'   => ['name' => 'Survey Question Analysis', 'description' => 'Response distribution per question.', 'filters' => ['survey'], 'handler' => 'questionAnalysis'],
            'category_analysis'   => ['name' => 'Survey Category Analysis', 'description' => 'Surveys grouped by privacy category.', 'filters' => [], 'handler' => 'categoryAnalysis'],
            'dept_participation'  => ['name' => 'Department Survey Participation', 'description' => 'Participation by department for a survey.', 'filters' => ['survey'], 'handler' => 'deptParticipation'],
            'position_participation' => ['name' => 'Position Survey Participation', 'description' => 'Participation by position for a survey.', 'filters' => ['survey'], 'handler' => 'positionParticipation'],
            'trend'               => ['name' => 'Survey Trend Report', 'description' => 'Monthly survey activity for a year.', 'filters' => ['year'], 'handler' => 'trend'],
            'recent_results'      => ['name' => 'Recent Survey Results Report', 'description' => 'Recently completed surveys + ratings.', 'filters' => ['duration'], 'handler' => 'recentResults'],
            'attendance'          => ['name' => 'Survey Attendance Register', 'description' => 'Per-participant response status.', 'filters' => ['survey'], 'handler' => 'attendance'],
            'reminder'            => ['name' => 'Survey Reminder Report', 'description' => 'Pending participants for a survey.', 'filters' => ['survey'], 'handler' => 'reminder'],
            'anonymous'           => ['name' => 'Anonymous Survey Participation Report', 'description' => 'Aggregate participation for anonymous surveys.', 'filters' => ['survey'], 'handler' => 'anonymous'],
            'expiry_status'       => ['name' => 'Survey Expiry Status Report', 'description' => 'Open/close dates and status.', 'filters' => ['duration'], 'handler' => 'expiryStatus'],
            'distribution'        => ['name' => 'Survey Distribution Report', 'description' => 'Invitations sent per survey.', 'filters' => ['survey'], 'handler' => 'distribution'],
            'exec_dashboard'      => ['name' => 'Survey Executive Dashboard Report', 'description' => 'Headline survey metrics.', 'filters' => [], 'handler' => 'execDashboard'],
        ];
    }

    /* --------------------------------------------------------------- plumbing */

    public function index()
    {
        if (Common::checkRouteWisePermission('resort.report.index', config('settings.resort_permissions.view')) == false) {
            return abort(403, 'Unauthorized access');
        }
        $page_title = 'Survey Reports';
        $resortId   = $this->resort->resort_id;
        $reports = collect($this->registry())->map(fn($r, $key) => [
            'key' => $key, 'name' => $r['name'], 'description' => $r['description'],
            // Every report exposes the duration (From/To date) filter, like the Custom Report.
            'filters' => array_values(array_unique(array_merge($r['filters'], ['duration']))),
        ])->values();
        $surveys = DB::table('parent_surveys')->where('resort_id', $resortId)->orderByDesc('created_at')->get(['id', 'Surevey_title']);

        $filterDefs = [
            ['filter' => 'survey', 'name' => 'survey', 'label' => 'Survey', 'type' => 'select', 'placeholder' => 'All surveys',
                'options' => $surveys->map(fn($s) => ['value' => $s->id, 'label' => $s->Surevey_title])->all()],
            ['filter' => 'survey_status', 'name' => 'survey_status', 'label' => 'Status', 'type' => 'select', 'placeholder' => 'All statuses',
                'options' => collect(['Publish', 'SaveAsDraft', 'OnGoing', 'Complete'])->map(fn($s) => ['value' => $s, 'label' => $s])->all()],
            ['filter' => 'year', 'name' => 'year', 'label' => 'Year', 'type' => 'select',
                'options' => collect(range((int) date('Y'), (int) date('Y') - 4))->map(fn($y) => ['value' => $y, 'label' => $y])->all()],
            ['filter' => 'duration', 'name' => 'from_date', 'label' => 'From Date', 'type' => 'date'],
            ['filter' => 'duration', 'name' => 'to_date', 'label' => 'To Date', 'type' => 'date'],
        ];

        return view('resorts.reports.module_report', [
            'page_title' => $page_title, 'reports' => $reports, 'filterDefs' => $filterDefs,
            'runRoute' => 'resort.report.survey.run', 'exportRoute' => 'resort.report.survey.export', 'insightsRoute' => 'resort.report.survey.insights',
        ]);
    }

    private function filtersFrom(Request $request): array
    {
        return [
            'survey'        => $request->input('survey') ?: null,
            'survey_status' => $request->input('survey_status') ?: null,
            'year'          => $request->input('year') ?: null,
            'from_date'     => $request->input('from_date') ?: null,
            'to_date'       => $request->input('to_date') ?: null,
        ];
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

    private function pct($n, $d): string { return $d ? round($n / $d * 100, 1) . '%' : '0%'; }

    /** parent_surveys base with invited/completed/responses/rating subquery columns. */
    private function surveyBase()
    {
        $rid = $this->resort->resort_id;
        return DB::table('parent_surveys as s')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 's.created_by')
            ->where('s.resort_id', $rid)
            ->select('s.*',
                DB::raw("(SELECT COUNT(*) FROM survey_employees WHERE Parent_survey_id = s.id) as invited"),
                DB::raw("(SELECT COUNT(*) FROM survey_employees WHERE Parent_survey_id = s.id AND emp_status='yes') as completed"),
                DB::raw("(SELECT COUNT(DISTINCT Survey_emp_ta_id) FROM survey_results WHERE Parent_survey_id = s.id) as responses"),
                DB::raw("(SELECT ROUND(AVG(CAST(r.Emp_Ans AS DECIMAL(10,2))),2) FROM survey_results r JOIN survey_questions q ON q.id=r.Question_id WHERE r.Parent_survey_id=s.id AND q.type='Rating' AND r.Emp_Ans REGEXP '^[0-9.]+$') as avg_rating"),
                DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as created_by_name"));
    }

    private function dmy($d): string { return $d ? Carbon::parse($d)->format('d M Y') : 'N/A'; }

    /** Per-participant base for a survey (employee name + dept + position). */
    private function participantBase($surveyId)
    {
        return DB::table('survey_employees as se')
            ->join('parent_surveys as s', 's.id', '=', 'se.Parent_survey_id')
            ->leftJoin('employees as e', 'e.id', '=', 'se.Emp_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->leftJoin('resort_departments as d', 'd.id', '=', 'e.Dept_id')
            ->leftJoin('resort_positions as p', 'p.id', '=', 'e.Position_id')
            ->where('s.resort_id', $this->resort->resort_id)
            ->when($surveyId, fn($q) => $q->where('se.Parent_survey_id', $surveyId))
            ->selectRaw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as employee_name, d.name as dept, p.position_title, se.emp_status, se.updated_at");
    }

    /* --------------------------------------------------------------- reports */

    public function execSummary(array $f): array
    {
        $rid = $this->resort->resort_id;
        $b = DB::table('parent_surveys')->where('resort_id', $rid);
        $this->applyDuration($b, $f, 'created_at');
        $total = (clone $b)->count();
        $pub = (clone $b)->whereIn('Status', ['Publish', 'OnGoing'])->count();
        $draft = (clone $b)->where('Status', 'SaveAsDraft')->count();
        $done = (clone $b)->where('Status', 'Complete')->count();
        $inv = DB::table('survey_employees as se')->join('parent_surveys as s', 's.id', '=', 'se.Parent_survey_id')->where('s.resort_id', $rid)->when(true, fn($q) => $this->applyDuration($q, $f, 's.created_at'))->count();
        $comp = DB::table('survey_employees as se')->join('parent_surveys as s', 's.id', '=', 'se.Parent_survey_id')->where('s.resort_id', $rid)->where('se.emp_status', 'yes')->when(true, fn($q) => $this->applyDuration($q, $f, 's.created_at'))->count();
        return ['columns' => ['Total Surveys', 'Published Surveys', 'Draft Surveys', 'Completed Surveys', 'Overall Participation Rate'],
            'rows' => [['Total Surveys' => $total, 'Published Surveys' => $pub, 'Draft Surveys' => $draft, 'Completed Surveys' => $done, 'Overall Participation Rate' => $this->pct($comp, $inv)]]];
    }

    public function register(array $f): array
    {
        $rows = $this->surveyBase()
            ->when($f['survey_status'], fn($q) => $q->where('s.Status', $f['survey_status']))
            ->when(true, fn($q) => $this->applyDuration($q, $f, 's.created_at'))
            ->orderByDesc('s.created_at')->get()
            ->map(fn($r) => [
                'Survey Name'     => $r->Surevey_title ?? 'N/A',
                'Survey Category' => $r->survey_privacy_type ?? 'N/A',
                'Created By'      => trim($r->created_by_name) ?: 'N/A',
                'Created Date'    => $this->dmy($r->created_at),
                'Status'          => $r->Status ?? 'N/A',
                'Participants'    => (int) $r->invited,
            ])->all();
        return ['columns' => ['Survey Name', 'Survey Category', 'Created By', 'Created Date', 'Status', 'Participants'], 'rows' => $rows];
    }

    public function published(array $f): array
    {
        $rows = $this->surveyBase()->whereIn('s.Status', ['Publish', 'OnGoing'])
            ->when(true, fn($q) => $this->applyDuration($q, $f, 's.Start_date'))->orderByDesc('s.Start_date')->get()
            ->map(fn($r) => [
                'Survey Name'        => $r->Surevey_title ?? 'N/A',
                'Published Date'     => $this->dmy($r->Start_date),
                'Expiry Date'        => $this->dmy($r->End_date),
                'Participation Rate' => $this->pct($r->completed, $r->invited),
            ])->all();
        return ['columns' => ['Survey Name', 'Published Date', 'Expiry Date', 'Participation Rate'], 'rows' => $rows];
    }

    public function draft(array $f): array
    {
        $rows = $this->surveyBase()->where('s.Status', 'SaveAsDraft')
            ->when(true, fn($q) => $this->applyDuration($q, $f, 's.updated_at'))->orderByDesc('s.updated_at')->get()
            ->map(fn($r) => [
                'Survey Name'        => $r->Surevey_title ?? 'N/A',
                'Created By'         => trim($r->created_by_name) ?: 'N/A',
                'Last Modified Date' => $this->dmy($r->updated_at),
                'Draft Status'       => 'Draft',
            ])->all();
        return ['columns' => ['Survey Name', 'Created By', 'Last Modified Date', 'Draft Status'], 'rows' => $rows];
    }

    public function completed(array $f): array
    {
        $rows = $this->surveyBase()->where('s.Status', 'Complete')
            ->when(true, fn($q) => $this->applyDuration($q, $f, 's.End_date'))
            ->orderByDesc('s.End_date')->get()
            ->map(fn($r) => [
                'Survey Name'      => $r->Surevey_title ?? 'N/A',
                'Completion Date'  => $this->dmy($r->End_date),
                'Total Participants' => (int) $r->invited,
                'Completion Rate'  => $this->pct($r->completed, $r->invited),
            ])->all();
        return ['columns' => ['Survey Name', 'Completion Date', 'Total Participants', 'Completion Rate'], 'rows' => $rows];
    }

    public function open(array $f): array
    {
        $rows = $this->surveyBase()->where('s.Status', 'OnGoing')
            ->when(true, fn($q) => $this->applyDuration($q, $f, 's.Start_date'))->orderBy('s.End_date')->get()
            ->map(fn($r) => [
                'Survey Name'         => $r->Surevey_title ?? 'N/A',
                'Opening Date'        => $this->dmy($r->Start_date),
                'Closing Date'        => $this->dmy($r->End_date),
                'Current Response Count' => (int) $r->responses,
            ])->all();
        return ['columns' => ['Survey Name', 'Opening Date', 'Closing Date', 'Current Response Count'], 'rows' => $rows];
    }

    public function expiring(array $f): array
    {
        $rows = $this->surveyBase()->whereIn('s.Status', ['Publish', 'OnGoing'])
            ->whereDate('s.End_date', '>=', Carbon::today())
            ->when(true, fn($q) => $this->applyDuration($q, $f, 's.End_date'))->orderBy('s.End_date')->get()
            ->map(fn($r) => [
                'Survey Name'        => $r->Surevey_title ?? 'N/A',
                'Closing Date'       => $this->dmy($r->End_date),
                'Days Remaining'     => $r->End_date ? Carbon::today()->diffInDays(Carbon::parse($r->End_date), false) . ' day(s)' : 'N/A',
                'Participation Rate' => $this->pct($r->completed, $r->invited),
            ])->all();
        return ['columns' => ['Survey Name', 'Closing Date', 'Days Remaining', 'Participation Rate'], 'rows' => $rows];
    }

    public function participation(array $f): array
    {
        $rows = $this->surveyBase()->when($f['survey'], fn($q) => $q->where('s.id', $f['survey']))
            ->when(true, fn($q) => $this->applyDuration($q, $f, 's.created_at'))->orderByDesc('s.created_at')->get()
            ->map(fn($r) => [
                'Survey Name'         => $r->Surevey_title ?? 'N/A',
                'Invited Participants' => (int) $r->invited,
                'Responses Received'  => (int) $r->completed,
                'Participation Percentage' => $this->pct($r->completed, $r->invited),
            ])->all();
        return ['columns' => ['Survey Name', 'Invited Participants', 'Responses Received', 'Participation Percentage'], 'rows' => $rows];
    }

    public function responseRate(array $f): array
    {
        $rows = $this->surveyBase()->when(true, fn($q) => $this->applyDuration($q, $f, 's.created_at'))->orderByDesc('s.created_at')->get()
            ->map(fn($r) => [
                'Survey Name'       => $r->Surevey_title ?? 'N/A',
                'Total Invitations' => (int) $r->invited,
                'Responses Received' => (int) $r->responses,
                'Response Rate'     => $this->pct($r->responses, $r->invited),
            ])->all();
        return ['columns' => ['Survey Name', 'Total Invitations', 'Responses Received', 'Response Rate'], 'rows' => $rows];
    }

    public function completion(array $f): array
    {
        $rows = $this->participantBase($f['survey'])
            ->when(true, fn($q) => $this->applyDuration($q, $f, 's.created_at'))->orderBy('employee_name')->get()
            ->map(fn($r) => [
                'Employee Name'   => trim($r->employee_name) ?: 'N/A',
                'Department'      => $r->dept ?? 'N/A',
                'Survey Status'   => $r->emp_status === 'yes' ? 'Completed' : 'Pending',
                'Completion Date' => $r->emp_status === 'yes' ? $this->dmy($r->updated_at) : 'N/A',
            ])->all();
        return ['columns' => ['Employee Name', 'Department', 'Survey Status', 'Completion Date'], 'rows' => $rows];
    }

    public function resultsSummary(array $f): array
    {
        $rows = $this->surveyBase()->where('s.Status', 'Complete')
            ->when(true, fn($q) => $this->applyDuration($q, $f, 's.End_date'))->orderByDesc('s.End_date')->get()
            ->map(fn($r) => [
                'Survey Name'    => $r->Surevey_title ?? 'N/A',
                'Total Responses' => (int) $r->responses,
                'Average Score'  => $r->avg_rating !== null ? number_format($r->avg_rating, 2) : 'N/A',
                'Overall Rating' => $r->avg_rating !== null ? round($r->avg_rating, 1) . ' / 5' : 'N/A',
            ])->all();
        return ['columns' => ['Survey Name', 'Total Responses', 'Average Score', 'Overall Rating'], 'rows' => $rows];
    }

    public function questionAnalysis(array $f): array
    {
        $rid = $this->resort->resort_id;
        $rows = DB::table('survey_questions as q')
            ->join('parent_surveys as s', 's.id', '=', 'q.Parent_survey_id')
            ->where('s.resort_id', $rid)
            ->when($f['survey'], fn($x) => $x->where('q.Parent_survey_id', $f['survey']))
            ->when(true, fn($x) => $this->applyDuration($x, $f, 's.created_at'))
            ->orderBy('q.id')->get(['q.id', 'q.Question_Text', 'q.type'])
            ->map(function ($q) {
                $dist = DB::table('survey_results')->where('Question_id', $q->id)
                    ->select('Emp_Ans', DB::raw('COUNT(*) c'))->groupBy('Emp_Ans')->orderByDesc(DB::raw('COUNT(*)'))->limit(8)->get();
                $distStr = $dist->map(fn($d) => ($d->Emp_Ans ?: '—') . ': ' . $d->c)->implode(', ');
                $avg = $q->type === 'Rating'
                    ? DB::table('survey_results')->where('Question_id', $q->id)->whereRaw("Emp_Ans REGEXP '^[0-9.]+$'")->avg(DB::raw('CAST(Emp_Ans AS DECIMAL(10,2))'))
                    : null;
                return [
                    'Question'             => $q->Question_Text ?? 'N/A',
                    'Response Distribution' => $distStr ?: 'No responses',
                    'Average Rating'       => $avg !== null ? number_format($avg, 2) : 'N/A',
                ];
            })->all();
        return ['columns' => ['Question', 'Response Distribution', 'Average Rating'], 'rows' => $rows];
    }

    public function categoryAnalysis(array $f): array
    {
        $rows = $this->surveyBase()->when(true, fn($q) => $this->applyDuration($q, $f, 's.created_at'))->get()->groupBy(fn($r) => $r->survey_privacy_type ?: 'Neutral')
            ->map(function ($grp, $cat) {
                $inv = $grp->sum('invited'); $comp = $grp->sum('completed');
                $ratings = $grp->pluck('avg_rating')->filter(fn($v) => $v !== null);
                return [
                    'Category'              => $cat,
                    'Number of Surveys'     => $grp->count(),
                    'Average Participation' => $this->pct($comp, $inv),
                    'Average Rating'        => $ratings->count() ? number_format($ratings->avg(), 2) : 'N/A',
                ];
            })->values()->all();
        return ['columns' => ['Category', 'Number of Surveys', 'Average Participation', 'Average Rating'], 'rows' => $rows];
    }

    private function groupParticipation(array $f, string $col, string $label): array
    {
        $rid = $this->resort->resort_id;
        $rows = DB::table('survey_employees as se')
            ->join('parent_surveys as s', 's.id', '=', 'se.Parent_survey_id')
            ->leftJoin('employees as e', 'e.id', '=', 'se.Emp_id')
            ->leftJoin('resort_departments as d', 'd.id', '=', 'e.Dept_id')
            ->leftJoin('resort_positions as p', 'p.id', '=', 'e.Position_id')
            ->where('s.resort_id', $rid)
            ->when($f['survey'], fn($q) => $q->where('se.Parent_survey_id', $f['survey']))
            ->when(true, fn($q) => $this->applyDuration($q, $f, 's.created_at'))
            ->groupBy($col)
            ->selectRaw("$col as grp, COUNT(*) invited, SUM(CASE WHEN se.emp_status='yes' THEN 1 ELSE 0 END) responses")
            ->orderBy($col)->get()
            ->map(fn($r) => [
                $label             => $r->grp ?? 'N/A',
                'Invited Employees' => (int) $r->invited,
                'Responses'        => (int) $r->responses,
                'Participation Percentage' => $this->pct($r->responses, $r->invited),
            ])->all();
        return ['columns' => [$label, 'Invited Employees', 'Responses', 'Participation Percentage'], 'rows' => $rows];
    }

    public function deptParticipation(array $f): array { return $this->groupParticipation($f, 'd.name', 'Department'); }
    public function positionParticipation(array $f): array { return $this->groupParticipation($f, 'p.position_title', 'Position'); }

    public function trend(array $f): array
    {
        $rid = $this->resort->resort_id;
        $year = $f['year'] ?: date('Y');
        $rows = [];
        for ($m = 1; $m <= 12; $m++) {
            $surveys = DB::table('parent_surveys')->where('resort_id', $rid)->whereRaw('YEAR(created_at)=?', [$year])->whereRaw('MONTH(created_at)=?', [$m])->pluck('id');
            if ($surveys->isEmpty()) continue;
            $inv = DB::table('survey_employees')->whereIn('Parent_survey_id', $surveys)->count();
            $comp = DB::table('survey_employees')->whereIn('Parent_survey_id', $surveys)->where('emp_status', 'yes')->count();
            $avg = DB::table('survey_results as r')->join('survey_questions as q', 'q.id', '=', 'r.Question_id')
                ->whereIn('r.Parent_survey_id', $surveys)->where('q.type', 'Rating')->whereRaw("r.Emp_Ans REGEXP '^[0-9.]+$'")->avg(DB::raw('CAST(r.Emp_Ans AS DECIMAL(10,2))'));
            $rows[] = [
                'Month'              => Carbon::create()->month($m)->format('F'),
                'Surveys Conducted'  => $surveys->count(),
                'Participation Rate' => $this->pct($comp, $inv),
                'Average Score'      => $avg !== null ? number_format($avg, 2) : 'N/A',
            ];
        }
        return ['columns' => ['Month', 'Surveys Conducted', 'Participation Rate', 'Average Score'], 'rows' => $rows];
    }

    public function recentResults(array $f): array
    {
        $rows = $this->surveyBase()->where('s.Status', 'Complete')
            ->when(true, fn($q) => $this->applyDuration($q, $f, 's.End_date'))
            ->orderByDesc('s.End_date')->limit(100)->get()
            ->map(fn($r) => [
                'Survey Name'        => $r->Surevey_title ?? 'N/A',
                'Completion Date'    => $this->dmy($r->End_date),
                'Average Rating'     => $r->avg_rating !== null ? number_format($r->avg_rating, 2) : 'N/A',
                'Participation Rate' => $this->pct($r->completed, $r->invited),
            ])->all();
        return ['columns' => ['Survey Name', 'Completion Date', 'Average Rating', 'Participation Rate'], 'rows' => $rows];
    }

    public function attendance(array $f): array
    {
        $rows = $this->participantBase($f['survey'])
            ->when(true, fn($q) => $this->applyDuration($q, $f, 's.created_at'))->orderBy('employee_name')->get()
            ->map(fn($r) => [
                'Employee Name'   => trim($r->employee_name) ?: 'N/A',
                'Department'      => $r->dept ?? 'N/A',
                'Response Status' => $r->emp_status === 'yes' ? 'Responded' : 'No Response',
                'Completion Date' => $r->emp_status === 'yes' ? $this->dmy($r->updated_at) : 'N/A',
            ])->all();
        return ['columns' => ['Employee Name', 'Department', 'Response Status', 'Completion Date'], 'rows' => $rows];
    }

    public function reminder(array $f): array
    {
        $rid = $this->resort->resort_id;
        $rows = $this->participantBase($f['survey'])->where('se.emp_status', '<>', 'yes')
            ->when(true, fn($q) => $this->applyDuration($q, $f, 's.created_at'))->orderBy('employee_name')->get()
            ->map(function ($r) use ($f) {
                $end = DB::table('parent_surveys')->where('id', $f['survey'])->value('End_date');
                return [
                    'Employee Name'  => trim($r->employee_name) ?: 'N/A',
                    'Department'     => $r->dept ?? 'N/A',
                    'Days Remaining' => $end ? Carbon::today()->diffInDays(Carbon::parse($end), false) . ' day(s)' : 'N/A',
                    'Reminder Status' => 'Pending',
                ];
            })->all();
        return ['columns' => ['Employee Name', 'Department', 'Days Remaining', 'Reminder Status'], 'rows' => $rows];
    }

    public function anonymous(array $f): array
    {
        $rows = $this->surveyBase()->where('s.survey_privacy_type', 'Anonymous')
            ->when($f['survey'], fn($q) => $q->where('s.id', $f['survey']))
            ->when(true, fn($q) => $this->applyDuration($q, $f, 's.created_at'))->get()
            ->map(fn($r) => [
                'Survey Name'        => $r->Surevey_title ?? 'N/A',
                'Total Invitations'  => (int) $r->invited,
                'Total Responses'    => (int) $r->completed,
                'Participation Percentage' => $this->pct($r->completed, $r->invited),
            ])->all();
        return ['columns' => ['Survey Name', 'Total Invitations', 'Total Responses', 'Participation Percentage'], 'rows' => $rows];
    }

    public function expiryStatus(array $f): array
    {
        $rows = $this->surveyBase()->when(true, fn($q) => $this->applyDuration($q, $f, 's.Start_date'))->orderByDesc('s.Start_date')->get()
            ->map(fn($r) => [
                'Survey Name'    => $r->Surevey_title ?? 'N/A',
                'Published Date' => $this->dmy($r->Start_date),
                'Expiry Date'    => $this->dmy($r->End_date),
                'Current Status' => $r->Status ?? 'N/A',
            ])->all();
        return ['columns' => ['Survey Name', 'Published Date', 'Expiry Date', 'Current Status'], 'rows' => $rows];
    }

    public function distribution(array $f): array
    {
        $rows = $this->surveyBase()->when($f['survey'], fn($q) => $q->where('s.id', $f['survey']))
            ->when(true, fn($q) => $this->applyDuration($q, $f, 's.created_at'))->orderByDesc('s.created_at')->get()
            ->map(fn($r) => [
                'Survey Name'        => $r->Surevey_title ?? 'N/A',
                'Distribution Method' => 'In-app (invited)',
                'Target Audience'    => $r->survey_privacy_type ?? 'N/A',
                'Invitations Sent'   => (int) $r->invited,
            ])->all();
        return ['columns' => ['Survey Name', 'Distribution Method', 'Target Audience', 'Invitations Sent'], 'rows' => $rows];
    }

    public function execDashboard(array $f): array
    {
        $rid = $this->resort->resort_id;
        $b = DB::table('parent_surveys')->where('resort_id', $rid);
        $this->applyDuration($b, $f, 'created_at');
        $total = (clone $b)->count();
        $active = (clone $b)->where('Status', 'OnGoing')->count();
        $draft = (clone $b)->where('Status', 'SaveAsDraft')->count();
        $inv = DB::table('survey_employees as se')->join('parent_surveys as s', 's.id', '=', 'se.Parent_survey_id')->where('s.resort_id', $rid)->when(true, fn($q) => $this->applyDuration($q, $f, 's.created_at'))->count();
        $comp = DB::table('survey_employees as se')->join('parent_surveys as s', 's.id', '=', 'se.Parent_survey_id')->where('s.resort_id', $rid)->where('se.emp_status', 'yes')->when(true, fn($q) => $this->applyDuration($q, $f, 's.created_at'))->count();
        $avg = DB::table('survey_results as r')->join('survey_questions as q', 'q.id', '=', 'r.Question_id')->join('parent_surveys as s', 's.id', '=', 'r.Parent_survey_id')
            ->where('s.resort_id', $rid)->where('q.type', 'Rating')->whereRaw("r.Emp_Ans REGEXP '^[0-9.]+$'")->when(true, fn($q) => $this->applyDuration($q, $f, 's.created_at'))->avg(DB::raw('CAST(r.Emp_Ans AS DECIMAL(10,2))'));
        return ['columns' => ['Total Surveys', 'Active Surveys', 'Draft Surveys', 'Participation %', 'Completion %', 'Average Rating'],
            'rows' => [['Total Surveys' => $total, 'Active Surveys' => $active, 'Draft Surveys' => $draft, 'Participation %' => $this->pct($comp, $inv), 'Completion %' => $this->pct($comp, $inv), 'Average Rating' => $avg !== null ? number_format($avg, 2) : 'N/A']]];
    }
}
