<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use App\Helpers\Common;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Predefined Workforce Planning reports (Option B).
 *
 * Unlike the generic report builder (ReportController), each report here is a
 * dedicated, dept-scoped query that returns computed metrics (vacancy %,
 * utilization %, …) the field-picker catalog cannot express. Output is the same
 * { columns, rows } shape the builder renders, so the existing
 * resorts.renderfiles.ReportFilterData partial is reused verbatim.
 *
 * Every query honours the department-scope rule: HR/GM/master see all
 * departments; a HOD/XCOM outside HR sees only their own
 * (Common::getScopedDepartmentIds()).
 */
class WorkforcePlanningReportController extends Controller
{
    protected $resort;

    public function __construct()
    {
        $this->resort = auth()->guard('resort-admin')->user();
    }

    /**
     * The registry of predefined reports. `filters` declares which inputs the
     * UI should show; `handler` is the controller method that runs the query.
     */
    private function registry(): array
    {
        return [
            'vacancy_analysis' => [
                'name'        => 'Vacancy Analysis',
                'description' => 'Approved positions that are currently vacant and require recruitment.',
                'filters'     => ['department', 'position'],
                'handler'     => 'vacancyAnalysis',
            ],
            'position_utilization' => [
                'name'        => 'Position Utilization',
                'description' => 'How effectively approved positions have been filled.',
                'filters'     => ['department', 'position'],
                'handler'     => 'positionUtilization',
            ],
            'department_vacancy_summary' => [
                'name'        => 'Department Vacancy Summary',
                'description' => 'Vacancies summarised across all departments.',
                'filters'     => ['department'],
                'handler'     => 'departmentVacancySummary',
            ],
            'approved_positions' => [
                'name'        => 'Approved Positions',
                'description' => 'All approved positions and their approved headcount.',
                'filters'     => ['department', 'position'],
                'handler'     => 'approvedPositions',
            ],
            'grade_wise_plan' => [
                'name'        => 'Grade-wise Workforce Plan',
                'description' => 'Planned headcount by job grade.',
                'filters'     => ['department'],
                'handler'     => 'gradeWisePlan',
            ],
            'recruitment_demand' => [
                'name'        => 'Recruitment Demand Forecast',
                'description' => 'Future hiring requirements from open vacancies.',
                'filters'     => ['department'],
                'handler'     => 'recruitmentDemand',
            ],
            'local_vs_expat' => [
                'name'        => 'Local vs Expatriate Workforce',
                'description' => 'Local and expatriate headcount distribution by department.',
                'filters'     => ['department'],
                'handler'     => 'localVsExpat',
            ],
        ];
    }

    public function index()
    {
        if (Common::checkRouteWisePermission('resort.report.index', config('settings.resort_permissions.view')) == false) {
            return abort(403, 'Unauthorized access');
        }

        $page_title = 'Workforce Planning Reports';
        $reports    = collect($this->registry())->map(fn($r, $key) => [
            'key'         => $key,
            'name'        => $r['name'],
            'description' => $r['description'],
            'filters'     => $r['filters'],
        ])->values();

        // Filter option sources (dept-scoped).
        $scoped = Common::getScopedDepartmentIds();
        $departments = DB::table('resort_departments')
            ->where('resort_id', $this->resort->resort_id)
            ->when($scoped !== null, fn($q) => $q->whereIn('id', $scoped))
            ->orderBy('name')
            ->get(['id', 'name']);

        $years = DB::table('manning_responses')
            ->where('resort_id', $this->resort->resort_id)
            ->distinct()->orderBy('year', 'desc')->pluck('year');

        $positions = DB::table('resort_positions')
            ->where('resort_id', $this->resort->resort_id)
            ->when($scoped !== null, fn($q) => $q->whereIn('dept_id', $scoped))
            ->orderBy('position_title')
            ->get(['id', 'position_title', 'dept_id']);

        return view('resorts.reports.workforce_planning', compact('page_title', 'reports', 'departments', 'years', 'positions'));
    }

    /** Run a predefined report and return the rendered table HTML. */
    public function run(Request $request)
    {
        if (Common::checkRouteWisePermission('resort.report.index', config('settings.resort_permissions.view')) == false) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $key      = (string) $request->input('report');
        $registry = $this->registry();
        if (!isset($registry[$key])) {
            return response()->json(['success' => false, 'message' => 'Unknown report.'], 422);
        }

        $filters = [
            'year'       => $request->input('year') ?: null,
            'department' => $request->input('department') ?: null,
            'position'   => $request->input('position') ?: null,
        ];

        $result  = $this->{$registry[$key]['handler']}($filters);
        $columns = $result['columns'];
        $data    = $result['rows'];

        $html = view('resorts.renderfiles.ReportFilterData', [
            'report'  => (object) ['name' => $registry[$key]['name']],
            'columns' => $columns,
            'data'    => $data,
        ])->render();

        return response()->json(['success' => true, 'html' => $html, 'count' => count($data)]);
    }

    /**
     * Base per-position seat query over the manning plan, dept-scoped and
     * year/department/position-filtered. One row per position with the plan's
     * approved / filled / vacant seat counts (MAX across the plan's months,
     * matching the existing WisdomTools fill-rate convention).
     */
    private function seatQuery(array $filters)
    {
        $resortId = $this->resort->resort_id;
        $scoped   = Common::getScopedDepartmentIds();

        return DB::table('position_monthly_data as pmd')
            ->join('manning_responses as mr', 'mr.id', '=', 'pmd.manning_response_id')
            ->join('resort_positions as p', 'p.id', '=', 'pmd.position_id')
            ->leftJoin('resort_departments as d', 'd.id', '=', 'mr.dept_id')
            ->where('mr.resort_id', $resortId)
            ->when($filters['year'], fn($q) => $q->where('mr.year', $filters['year']))
            ->when($scoped !== null, fn($q) => $q->whereIn('mr.dept_id', $scoped))
            ->when($filters['department'], fn($q) => $q->where('mr.dept_id', $filters['department']))
            ->when($filters['position'], fn($q) => $q->where('pmd.position_id', $filters['position']))
            ->groupBy('pmd.position_id', 'p.position_title', 'p.Rank', 'mr.dept_id', 'd.name')
            ->select(
                'mr.dept_id',
                'd.name as department',
                'p.position_title',
                'p.Rank as grade',
                DB::raw('MAX(pmd.headcount) as approved'),
                DB::raw('MAX(pmd.filledcount) as filled'),
                DB::raw('MAX(pmd.vacantcount) as vacant')
            );
    }

    private function pct($numerator, $denominator): string
    {
        if (!$denominator) return '0%';
        return round(($numerator / $denominator) * 100, 1) . '%';
    }

    /** #7 Vacancy Analysis — vacant seats per position + vacancy %. */
    public function vacancyAnalysis(array $filters): array
    {
        $rows = $this->seatQuery($filters)
            ->havingRaw('MAX(pmd.vacantcount) > 0')
            ->orderBy('d.name')->orderBy('p.position_title')
            ->get()
            ->map(fn($r) => [
                'Department'        => $r->department ?? 'N/A',
                'Position'          => $r->position_title,
                'Approved Headcount'=> (int) $r->approved,
                'Filled Headcount'  => (int) $r->filled,
                'Vacancies'         => (int) $r->vacant,
                'Vacancy (%)'       => $this->pct($r->vacant, $r->approved),
            ])->all();

        return [
            'columns' => ['Department', 'Position', 'Approved Headcount', 'Filled Headcount', 'Vacancies', 'Vacancy (%)'],
            'rows'    => $rows,
        ];
    }

    /** #9 Position Utilization — filled/approved per position. */
    public function positionUtilization(array $filters): array
    {
        $rows = $this->seatQuery($filters)
            ->orderBy('p.position_title')
            ->get()
            ->map(fn($r) => [
                'Position'           => $r->position_title,
                'Approved Headcount' => (int) $r->approved,
                'Filled Headcount'   => (int) $r->filled,
                'Utilization (%)'    => $this->pct($r->filled, $r->approved),
            ])->all();

        return [
            'columns' => ['Position', 'Approved Headcount', 'Filled Headcount', 'Utilization (%)'],
            'rows'    => $rows,
        ];
    }

    /** #18 Department Vacancy Summary — totals + vacancy % per department. */
    public function departmentVacancySummary(array $filters): array
    {
        // Aggregate the per-position seat rows up to the department level.
        $sub = $this->seatQuery($filters);

        $rows = DB::query()->fromSub($sub, 'seats')
            ->groupBy('seats.dept_id', 'seats.department')
            ->select(
                'seats.department',
                DB::raw('SUM(seats.approved) as total_positions'),
                DB::raw('SUM(seats.filled) as filled_positions'),
                DB::raw('SUM(seats.vacant) as vacant_positions')
            )
            ->orderBy('seats.department')
            ->get()
            ->map(fn($r) => [
                'Department'        => $r->department ?? 'N/A',
                'Total Positions'   => (int) $r->total_positions,
                'Filled Positions'  => (int) $r->filled_positions,
                'Vacant Positions'  => (int) $r->vacant_positions,
                'Vacancy (%)'       => $this->pct($r->vacant_positions, $r->total_positions),
            ])->all();

        return [
            'columns' => ['Department', 'Total Positions', 'Filled Positions', 'Vacant Positions', 'Vacancy (%)'],
            'rows'    => $rows,
        ];
    }

    /** #8 Approved Positions — every planned position + approved headcount. */
    public function approvedPositions(array $filters): array
    {
        $rows = $this->seatQuery($filters)
            ->orderBy('d.name')->orderBy('p.position_title')
            ->get()
            ->map(fn($r) => [
                'Department'         => $r->department ?? 'N/A',
                'Position'           => $r->position_title,
                'Grade'              => $r->grade !== null ? (string) $r->grade : 'N/A',
                'Approved Headcount' => (int) $r->approved,
            ])->all();

        return [
            'columns' => ['Department', 'Position', 'Grade', 'Approved Headcount'],
            'rows'    => $rows,
        ];
    }

    /** #17 Grade-wise Workforce Plan — planned headcount by job grade. */
    public function gradeWisePlan(array $filters): array
    {
        $rows = $this->seatQuery($filters)
            ->orderBy('p.Rank')->orderBy('p.position_title')
            ->get()
            ->map(fn($r) => [
                'Grade'            => $r->grade !== null ? (string) $r->grade : 'N/A',
                'Position'         => $r->position_title,
                'Department'       => $r->department ?? 'N/A',
                'Planned Headcount'=> (int) $r->approved,
            ])->all();

        return [
            'columns' => ['Grade', 'Position', 'Department', 'Planned Headcount'],
            'rows'    => $rows,
        ];
    }

    /** #11 Recruitment Demand Forecast — open vacancies + expected start date. */
    public function recruitmentDemand(array $filters): array
    {
        $resortId = $this->resort->resort_id;
        $scoped   = Common::getScopedDepartmentIds();

        $rows = DB::table('vacancies as v')
            ->leftJoin('resort_departments as d', 'd.id', '=', 'v.department')
            ->leftJoin('resort_positions as p', 'p.id', '=', 'v.position')
            ->where('v.Resort_id', $resortId)
            ->when($scoped !== null, fn($q) => $q->whereIn('v.department', $scoped))
            ->when($filters['department'], fn($q) => $q->where('v.department', $filters['department']))
            ->when($filters['year'], fn($q) => $q->whereYear('v.required_starting_date', $filters['year']))
            ->orderBy('v.required_starting_date')
            ->get(['d.name as department', 'p.position_title', 'v.Total_position_required', 'v.required_starting_date'])
            ->map(fn($r) => [
                'Department'              => $r->department ?? 'N/A',
                'Position'                => $r->position_title ?? 'N/A',
                'Required Headcount'      => (int) ($r->Total_position_required ?: 1),
                'Expected Recruitment Date' => $r->required_starting_date
                    ? \Carbon\Carbon::parse($r->required_starting_date)->format('d M Y') : 'N/A',
            ])->all();

        return [
            'columns' => ['Department', 'Position', 'Required Headcount', 'Expected Recruitment Date'],
            'rows'    => $rows,
        ];
    }

    /** #15 Local vs Expatriate Workforce — headcount split by nationality. */
    public function localVsExpat(array $filters): array
    {
        $resortId = $this->resort->resort_id;
        $scoped   = Common::getScopedDepartmentIds();

        $rows = DB::table('employees as e')
            ->leftJoin('resort_departments as d', 'd.id', '=', 'e.Dept_id')
            ->where('e.resort_id', $resortId)
            ->whereIn('e.status', ['Active', 'Probationary'])
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))
            ->when($filters['department'], fn($q) => $q->where('e.Dept_id', $filters['department']))
            ->groupBy('e.Dept_id', 'd.name')
            ->orderBy('d.name')
            ->select(
                'd.name as department',
                DB::raw("SUM(CASE WHEN e.nationality = 'Maldivian' THEN 1 ELSE 0 END) as local"),
                DB::raw("SUM(CASE WHEN e.nationality <> 'Maldivian' THEN 1 ELSE 0 END) as expat"),
                DB::raw('COUNT(*) as total')
            )
            ->get()
            ->map(fn($r) => [
                'Department'           => $r->department ?? 'N/A',
                'Local Headcount'      => (int) $r->local,
                'Expatriate Headcount' => (int) $r->expat,
                'Total Headcount'      => (int) $r->total,
            ])->all();

        return [
            'columns' => ['Department', 'Local Headcount', 'Expatriate Headcount', 'Total Headcount'],
            'rows'    => $rows,
        ];
    }
}
