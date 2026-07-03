<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Resorts\Concerns\PredefinedReportActions;
use App\Helpers\Common;
use Carbon\Carbon;
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
    use PredefinedReportActions;

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
            'annual_plan' => [
                'name'        => 'Annual Workforce Plan',
                'description' => 'Approved workforce plan for the year — headcount and budget across departments.',
                'filters'     => ['year', 'month'],
                'handler'     => 'annualPlan',
            ],
            'monthly_plan' => [
                'name'        => 'Monthly Workforce Plan',
                'description' => 'Workforce plan month-by-month for seasonal/operational planning.',
                'filters'     => ['year', 'month', 'department', 'position'],
                'handler'     => 'monthlyPlan',
            ],
            'department_headcount_plan' => [
                'name'        => 'Department Headcount Plan',
                'description' => 'Approved vs planned headcount per department.',
                'filters'     => ['year', 'department'],
                'handler'     => 'departmentHeadcountPlan',
            ],
            'position_wise_plan' => [
                'name'        => 'Position-wise Workforce Plan',
                'description' => 'Planned headcount and budgeted cost for every approved position.',
                'filters'     => ['year', 'position', 'month'],
                'handler'     => 'positionWisePlan',
            ],
            'budget_summary' => [
                'name'        => 'Workforce Budget Summary',
                'description' => 'Estimated workforce budget per department for the period.',
                'filters'     => ['year', 'month', 'department'],
                'handler'     => 'budgetSummary',
            ],
            'budget_vs_actual' => [
                'name'        => 'Workforce Budget vs Actual',
                'description' => 'Planned workforce cost vs actual payroll expenditure.',
                'filters'     => ['year', 'department', 'month'],
                'handler'     => 'budgetVsActual',
            ],
            'vacancy_analysis' => [
                'name'        => 'Vacancy Analysis',
                'description' => 'Approved positions that are currently vacant and require recruitment.',
                'filters'     => ['department', 'position'],
                'handler'     => 'vacancyAnalysis',
            ],
            'approved_positions' => [
                'name'        => 'Approved Positions',
                'description' => 'All approved positions and their approved headcount.',
                'filters'     => ['department', 'position'],
                'handler'     => 'approvedPositions',
            ],
            'position_utilization' => [
                'name'        => 'Position Utilization',
                'description' => 'How effectively approved positions have been filled.',
                'filters'     => ['department', 'position'],
                'handler'     => 'positionUtilization',
            ],
            'department_workforce_cost' => [
                'name'        => 'Department Workforce Cost',
                'description' => 'Planned workforce expenditure per department.',
                'filters'     => ['year', 'department', 'month'],
                'handler'     => 'departmentWorkforceCost',
            ],
            'recruitment_demand' => [
                'name'        => 'Recruitment Demand Forecast',
                'description' => 'Future hiring requirements from open vacancies.',
                'filters'     => ['year', 'department', 'duration'],
                'handler'     => 'recruitmentDemand',
            ],
            'new_position_requests' => [
                'name'        => 'New Position Requests',
                'description' => 'Newly requested positions awaiting approval or budgeting.',
                'filters'     => ['year', 'status', 'duration'],
                'handler'     => 'newPositionRequests',
            ],
            'approval_status' => [
                'name'        => 'Workforce Planning Approval Status',
                'description' => 'Approval status of workforce plans submitted for review.',
                'filters'     => ['year', 'department', 'status', 'duration'],
                'handler'     => 'approvalStatus',
            ],
            'revision_history' => [
                'name'        => 'Workforce Planning Revision History',
                'description' => 'Revisions made to workforce plans for audit and history.',
                'filters'     => ['year', 'department', 'duration'],
                'handler'     => 'revisionHistory',
            ],
            'local_vs_expat' => [
                'name'        => 'Local vs Expatriate Workforce',
                'description' => 'Local and expatriate headcount distribution by department.',
                'filters'     => ['department'],
                'handler'     => 'localVsExpat',
            ],
            'employment_type_planning' => [
                'name'        => 'Employment Type Planning',
                'description' => 'Workforce distribution by employment category.',
                'filters'     => ['department', 'employment_type'],
                'handler'     => 'employmentTypePlanning',
            ],
            'grade_wise_plan' => [
                'name'        => 'Grade-wise Workforce Plan',
                'description' => 'Planned headcount by job grade.',
                'filters'     => ['department', 'grade'],
                'handler'     => 'gradeWisePlan',
            ],
            'department_vacancy_summary' => [
                'name'        => 'Department Vacancy Summary',
                'description' => 'Vacancies summarised across all departments.',
                'filters'     => ['department'],
                'handler'     => 'departmentVacancySummary',
            ],
            'cost_by_position' => [
                'name'        => 'Workforce Cost by Position',
                'description' => 'Estimated annual workforce cost for each approved position.',
                'filters'     => ['year', 'position'],
                'handler'     => 'costByPosition',
            ],
            'executive_summary' => [
                'name'        => 'Workforce Planning Executive Summary',
                'description' => 'Consolidated overview of plan, budget, positions, vacancies.',
                'filters'     => ['year'],
                'handler'     => 'executiveSummary',
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

        $months = collect(range(1, 12))->map(fn($m) => [
            'value' => $m,
            'label' => Carbon::create()->month($m)->format('F'),
        ]);

        $employmentTypes = DB::table('employees')->where('resort_id', $this->resort->resort_id)
            ->whereNotNull('employment_type')->where('employment_type', '<>', '')
            ->distinct()->orderBy('employment_type')->pluck('employment_type');

        // Status options span both vacancy requests and plan-approval statuses.
        $statuses = collect(['Active', 'Cancelled', 'Pending', 'Approved', 'Rejected', 'Completed', 'Genrated']);
        $grades = config('settings.Position_Rank', []);

        return view('resorts.reports.workforce_planning', compact(
            'page_title', 'reports', 'departments', 'years', 'positions',
            'months', 'employmentTypes', 'statuses', 'grades'
        ));
    }

    private function filtersFrom(Request $request): array
    {
        return [
            'year'            => $request->input('year') ?: null,
            'department'      => $request->input('department') ?: null,
            'position'        => $request->input('position') ?: null,
            'grade'           => $request->input('grade') ?: null,
            'month'           => $request->input('month') ?: null,
            'status'          => $request->input('status') ?: null,
            'employment_type' => $request->input('employment_type') ?: null,
            'from_date'       => $request->input('from_date') ?: null,
            'to_date'         => $request->input('to_date') ?: null,
        ];
    }

    /** Apply the optional duration (from/to date) to a query's date column. */
    private function applyDuration($q, array $filters, string $col)
    {
        return $q->when($filters['from_date'] ?? null, fn($x) => $x->whereDate($col, '>=', $filters['from_date']))
                 ->when($filters['to_date'] ?? null, fn($x) => $x->whereDate($col, '<=', $filters['to_date']));
    }

    /** Resolve a report key + filters to ['name','description','columns','rows'] or null. */
    private function compute(string $key, array $filters): ?array
    {
        $registry = $this->registry();
        if (!isset($registry[$key])) {
            return null;
        }
        $res = $this->{$registry[$key]['handler']}($filters);
        return [
            'name'        => $registry[$key]['name'],
            'description' => $registry[$key]['description'],
            'columns'     => $res['columns'],
            'rows'        => $this->appendTotalsRow($res['columns'], $res['rows']),
        ];
    }

    /** Run a predefined report and return the rendered table HTML. */
    public function run(Request $request)
    {
        if (Common::checkRouteWisePermission('resort.report.index', config('settings.resort_permissions.view')) == false) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $c = $this->compute((string) $request->input('report'), $this->filtersFrom($request));
        if (!$c) {
            return response()->json(['success' => false, 'message' => 'Unknown report.'], 422);
        }

        $html = view('resorts.renderfiles.ReportFilterData', [
            'report'  => (object) ['name' => $c['name']],
            'columns' => $c['columns'],
            'data'    => $c['rows'],
        ])->render();

        return response()->json(['success' => true, 'html' => $html, 'count' => count($c['rows'])]);
    }

    /** Export a predefined report (csv / excel / pdf). */
    public function export(Request $request)
    {
        if (Common::checkRouteWisePermission('resort.report.index', config('settings.resort_permissions.view')) == false) {
            return abort(403, 'Unauthorized access');
        }
        $c = $this->compute((string) $request->input('report'), $this->filtersFrom($request));
        if (!$c) {
            return abort(404, 'Unknown report');
        }
        return $this->exportComputedReport($c['name'], $c['description'], $c['columns'], $c['rows'], $request->input('format', 'pdf'));
    }

    /** WAI Insights for a predefined report. */
    public function insights(Request $request)
    {
        if (Common::checkRouteWisePermission('resort.report.index', config('settings.resort_permissions.view')) == false) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }
        $c = $this->compute((string) $request->input('report'), $this->filtersFrom($request));
        if (!$c) {
            return response()->json(['status' => false, 'message' => 'Unknown report.'], 422);
        }
        $text = $this->computeAiInsightsText($c['name'], $c['description'], $c['columns'], $c['rows']);
        return response()->json(['status' => true, 'data' => $text]);
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
            ->when($filters['grade'] ?? null, fn($q) => $q->where('p.Rank', $filters['grade']))
            ->groupBy('pmd.position_id', 'p.position_title', 'p.Rank', 'mr.dept_id', 'd.name')
            ->select(
                'pmd.position_id',
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

    /** Map a hiring-requisition / vacancy state to its workflow status. */
    private function requisitionStatus($status): string
    {
        $s = strtolower(trim((string) $status));
        return match (true) {
            in_array($s, ['approved', 'active', 'open', 'published']) => 'Approved',
            in_array($s, ['rejected', 'cancelled', 'canceled', 'declined']) => 'Rejected',
            $s === '' => 'Pending',
            default => 'Pending',
        };
    }

    /** Map a position's Rank (grade id) to its grade name (EXCOM, HOD, MGR …). */
    private function gradeName($rank): string
    {
        if ($rank === null || $rank === '') return 'N/A';
        $map = config('settings.Position_Rank', []);
        return $map[(int) $rank] ?? ('Grade ' . $rank);
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
                'Grade'              => $this->gradeName($r->grade),
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
                'Grade'            => $this->gradeName($r->grade),
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
            ->when(true, fn($q) => $this->applyDuration($q, $filters, 'v.required_starting_date'))
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

    /* ----------------------------------------------------- budget helpers */
    // NOTE: the former deptBudgetMap()/deptSalaryMap()/positionBudgetMap() helpers
    // read store_manning_response_* / resort_vacant_budget_costs.basic_salary, which
    // are empty for live resorts (→ the 0.00 / N/A budget bug). They were replaced by
    // salaryMap()/allowanceMap() below, which read the actual Budget module config tables.

    /** Per-department ACTUAL payroll cost for a year (sum of gross earnings). */
    private function actualByDeptMap($year): array
    {
        return DB::table('payroll_reviews as pr')
            ->join('payroll as pay', 'pay.id', '=', 'pr.payroll_id')
            ->join('employees as e', 'e.id', '=', 'pr.employee_id')
            ->where('pay.resort_id', $this->resort->resort_id)
            ->when($year, fn($q) => $q->whereRaw('YEAR(pay.start_date) = ?', [$year]))
            ->groupBy('e.Dept_id')
            ->selectRaw('e.Dept_id as did, SUM(pr.total_earnings) as tot')
            ->pluck('tot', 'did')->toArray();
    }

    private function money($v, $cur = 'USD'): string
    {
        $c = strtoupper(trim((string) $cur));
        $prefix = ($c === 'MVR' || $c === 'RF') ? 'MVR ' : '$';
        return $prefix . number_format((float) $v, 2);
    }

    /* --------------- corrected budget sources (Budget module config tables) ---------------
     * The real approved budget lives per-employee / per-vacant-seat, PER MONTH, in
     * resort_employee_budget_cost_configurations + resort_vacant_budget_cost_configurations:
     *   - `value` holds each cost-component amount (allowances / other costs);
     *   - basic salary is NOT in these (their basic_salary is 0) — it lives on
     *     employees.basic_salary (filled seats) and resort_vacant_budget_costs.basic_salary
     *     (vacant seats), both MONTHLY figures.
     * The old deptSalaryMap()/positionBudgetMap() read empty tables -> 0.00 / N/A.
     */

    /** Monthly basic-salary budget grouped by position_id|Dept_id: [id => ['amt','cur']]. */
    private function salaryMap(string $by, $year): array
    {
        $rid = $this->resort->resort_id;
        $out = [];
        $add = function (&$out, $id, $amt, $cur) {
            $id = (int) $id;
            $out[$id]['amt'] = ($out[$id]['amt'] ?? 0) + (float) $amt;
            $out[$id]['cur'] = $out[$id]['cur'] ?? ($cur ?: 'USD');
        };
        // Filled seats — current employees' (monthly) basic salary.
        $empCol = $by === 'position' ? 'Position_id' : 'Dept_id';
        foreach (DB::table('employees')->where('resort_id', $rid)->whereNotNull($empCol)->where($empCol, '>', 0)
            ->groupBy($empCol, 'basic_salary_currency')
            ->selectRaw("$empCol as gid, basic_salary_currency as cur, SUM(basic_salary) as tot")->get() as $r) {
            $add($out, $r->gid, $r->tot, $r->cur);
        }
        // Vacant seats — configured vacant basic salary.
        $vCol = $by === 'position' ? 'position_id' : 'department_id';
        foreach (DB::table('resort_vacant_budget_costs')->where('resort_id', $rid)
            ->when($year, fn($q) => $q->where('year', $year))
            ->groupBy($vCol)->selectRaw("$vCol as gid, SUM(basic_salary) as tot")->get() as $r) {
            $add($out, $r->gid, $r->tot, 'USD');
        }
        return $out;
    }

    /** Budgeted allowances/other costs grouped by position_id|department_id.
     *  $month null = full-year (sum all months); otherwise a single month. */
    private function allowanceMap(string $by, $year, $month = null): array
    {
        $rid = $this->resort->resort_id;
        $groupCol = $by === 'position' ? 'position_id' : 'department_id';
        $out = [];
        foreach (['resort_employee_budget_cost_configurations', 'resort_vacant_budget_cost_configurations'] as $t) {
            foreach (DB::table($t)->where('resort_id', $rid)
                ->when($year, fn($q) => $q->where('year', $year))
                ->when($month, fn($q) => $q->where('month', $month))
                ->groupBy($groupCol, 'currency')
                ->selectRaw("$groupCol as gid, currency, SUM(value) as tot")->get() as $r) {
                $id = (int) $r->gid;
                $out[$id]['amt'] = ($out[$id]['amt'] ?? 0) + (float) $r->tot;
                $out[$id]['cur'] = $out[$id]['cur'] ?? ($r->currency ?: 'USD');
            }
        }
        return $out;
    }

    /** Pick a display currency for a group from salary/allowance maps (uniform, else USD). */
    private function curFor($id, array ...$maps): string
    {
        foreach ($maps as $m) {
            if (!empty($m[$id]['cur'])) return $m[$id]['cur'];
        }
        return 'USD';
    }

    /** Planned headcount per department (manning total_headcount) for a year. */
    private function deptHeadcountMap($year): array
    {
        $scoped = Common::getScopedDepartmentIds();
        return DB::table('manning_responses')->where('resort_id', $this->resort->resort_id)
            ->when($year, fn($q) => $q->where('year', $year))
            ->when($scoped !== null, fn($q) => $q->whereIn('dept_id', $scoped))
            ->groupBy('dept_id')->selectRaw('dept_id as did, SUM(total_headcount) as tot')
            ->pluck('tot', 'did')->toArray();
    }

    /* ----------------------------------------------------- budget/plan reports */

    /** #1 Annual Workforce Plan. */
    public function annualPlan(array $filters): array
    {
        $sal = $this->salaryMap('position', $filters['year']);
        $alw = $this->allowanceMap('position', $filters['year']);
        $rows = $this->seatQuery($filters)->orderBy('d.name')->orderBy('p.position_title')->get()
            ->map(function ($r) use ($sal, $alw) {
                $pid = (int) $r->position_id;
                $cur = $this->curFor($pid, $sal, $alw);
                $salary = ($sal[$pid]['amt'] ?? 0) * 12;   // annual = monthly basic × 12
                $allow  = $alw[$pid]['amt'] ?? 0;          // config values already span the year
                return [
                    'Department'         => $r->department ?? 'N/A',
                    'Position'           => $r->position_title,
                    'Grade'              => $this->gradeName($r->grade),
                    'Approved Headcount' => (int) $r->approved,
                    'Planned Headcount'  => (int) $r->approved,
                    'Budgeted Salary'    => $this->money($salary, $cur),
                    'Budgeted Allowances'=> $this->money($allow, $cur),
                    'Total Budget'       => $this->money($salary + $allow, $cur),
                ];
            })->all();

        return [
            'columns' => ['Department', 'Position', 'Grade', 'Approved Headcount', 'Planned Headcount', 'Budgeted Salary', 'Budgeted Allowances', 'Total Budget'],
            'rows'    => $rows,
        ];
    }

    /** #2 Monthly Workforce Plan. */
    public function monthlyPlan(array $filters): array
    {
        $resortId  = $this->resort->resort_id;
        $scoped    = Common::getScopedDepartmentIds();
        $sal       = $this->salaryMap('position', $filters['year']);   // monthly basic per position

        // Per-position, per-month allowances from the budget config tables.
        $alwPM = [];
        foreach (['resort_employee_budget_cost_configurations', 'resort_vacant_budget_cost_configurations'] as $t) {
            foreach (DB::table($t)->where('resort_id', $resortId)
                ->when($filters['year'], fn($q) => $q->where('year', $filters['year']))
                ->groupBy('position_id', 'month', 'currency')
                ->selectRaw('position_id as pid, month, currency, SUM(value) as tot')->get() as $r) {
                $pid = (int) $r->pid; $m = (int) $r->month;
                $alwPM[$pid][$m]['amt'] = ($alwPM[$pid][$m]['amt'] ?? 0) + (float) $r->tot;
                $alwPM[$pid][$m]['cur'] = $alwPM[$pid][$m]['cur'] ?? ($r->currency ?: 'USD');
            }
        }

        $rows = DB::table('position_monthly_data as pmd')
            ->join('manning_responses as mr', 'mr.id', '=', 'pmd.manning_response_id')
            ->join('resort_positions as p', 'p.id', '=', 'pmd.position_id')
            ->leftJoin('resort_departments as d', 'd.id', '=', 'mr.dept_id')
            ->where('mr.resort_id', $resortId)
            ->when($filters['year'], fn($q) => $q->where('mr.year', $filters['year']))
            ->when($filters['month'], fn($q) => $q->where('pmd.month', $filters['month']))
            ->when($scoped !== null, fn($q) => $q->whereIn('mr.dept_id', $scoped))
            ->when($filters['department'], fn($q) => $q->where('mr.dept_id', $filters['department']))
            ->when($filters['position'], fn($q) => $q->where('pmd.position_id', $filters['position']))
            ->orderBy('pmd.month')->orderBy('d.name')->orderBy('p.position_title')
            ->get(['pmd.month', 'd.name as department', 'p.position_title', 'pmd.position_id', 'pmd.headcount'])
            ->map(function ($r) use ($sal, $alwPM) {
                $pid = (int) $r->position_id; $m = (int) $r->month;
                $cur = $alwPM[$pid][$m]['cur'] ?? ($sal[$pid]['cur'] ?? 'USD');
                $salary = $sal[$pid]['amt'] ?? 0;              // monthly basic salary
                $allow  = $alwPM[$pid][$m]['amt'] ?? 0;        // that month's allowances
                return [
                    'Month'                 => Carbon::create()->month((int) $r->month)->format('F'),
                    'Department'            => $r->department ?? 'N/A',
                    'Position'              => $r->position_title,
                    'Planned Headcount'     => (int) $r->headcount,
                    'Planned Salary Cost'   => $this->money($salary, $cur),
                    'Planned Allowance Cost'=> $this->money($allow, $cur),
                ];
            })->all();

        return [
            'columns' => ['Month', 'Department', 'Position', 'Planned Headcount', 'Planned Salary Cost', 'Planned Allowance Cost'],
            'rows'    => $rows,
        ];
    }

    /** #3 Department Headcount Plan — approved (manning total) vs planned (positions). */
    public function departmentHeadcountPlan(array $filters): array
    {
        $resortId = $this->resort->resort_id;
        $scoped   = Common::getScopedDepartmentIds();

        // Approved = manning_responses.total_headcount per dept.
        $approved = DB::table('manning_responses')
            ->where('resort_id', $resortId)
            ->when($filters['year'], fn($q) => $q->where('year', $filters['year']))
            ->when($scoped !== null, fn($q) => $q->whereIn('dept_id', $scoped))
            ->when($filters['department'], fn($q) => $q->where('dept_id', $filters['department']))
            ->groupBy('dept_id')->selectRaw('dept_id as did, SUM(total_headcount) as tot')
            ->pluck('tot', 'did')->toArray();

        // Planned = sum of per-position approved seats from the plan rows.
        $planned = DB::query()->fromSub($this->seatQuery($filters), 'seats')
            ->groupBy('seats.dept_id')->selectRaw('seats.dept_id as did, SUM(seats.approved) as tot')
            ->pluck('tot', 'did')->toArray();

        $deptNames = DB::table('resort_departments')->where('resort_id', $resortId)
            ->when($scoped !== null, fn($q) => $q->whereIn('id', $scoped))
            ->pluck('name', 'id')->toArray();

        $rows = [];
        foreach (array_unique(array_merge(array_keys($approved), array_keys($planned))) as $did) {
            if ($filters['department'] && (int) $did !== (int) $filters['department']) continue;
            $a = (int) ($approved[$did] ?? 0);
            $p = (int) ($planned[$did] ?? 0);
            $rows[] = [
                'Department'         => $deptNames[$did] ?? 'N/A',
                'Approved Headcount' => $a,
                'Planned Headcount'  => $p,
                'Variance'           => $a - $p,
            ];
        }
        usort($rows, fn($x, $y) => strcmp($x['Department'], $y['Department']));

        return ['columns' => ['Department', 'Approved Headcount', 'Planned Headcount', 'Variance'], 'rows' => $rows];
    }

    /** #4 Position-wise Workforce Plan. */
    public function positionWisePlan(array $filters): array
    {
        $sal = $this->salaryMap('position', $filters['year']);
        $alw = $this->allowanceMap('position', $filters['year']);
        $rows = $this->seatQuery($filters)->orderBy('p.position_title')->get()
            ->map(function ($r) use ($sal, $alw) {
                $pid = (int) $r->position_id;
                $cur = $this->curFor($pid, $sal, $alw);
                // Consolidated ANNUAL budget: monthly basic × 12 + full-year allowances.
                $annual = ($sal[$pid]['amt'] ?? 0) * 12 + ($alw[$pid]['amt'] ?? 0);
                return [
                    'Position'         => $r->position_title,
                    'Grade'            => $this->gradeName($r->grade),
                    'Department'       => $r->department ?? 'N/A',
                    'Planned Headcount'=> (int) $r->approved,
                    'Budgeted Cost'    => $this->money($annual, $cur),
                ];
            })->all();

        return ['columns' => ['Position', 'Grade', 'Department', 'Planned Headcount', 'Budgeted Cost'], 'rows' => $rows];
    }

    /** #5 Workforce Budget Summary. */
    public function budgetSummary(array $filters): array
    {
        $resortId = $this->resort->resort_id;
        $scoped   = Common::getScopedDepartmentIds();
        $annualMode = empty($filters['month']);
        $factor   = $annualMode ? 12 : 1;                       // basic salary is monthly
        $salary   = $this->salaryMap('dept', $filters['year']);
        $allow    = $this->allowanceMap('dept', $filters['year'], $filters['month'] ?? null);
        $head     = $this->deptHeadcountMap($filters['year']);
        $deptNames = DB::table('resort_departments')->where('resort_id', $resortId)
            ->when($scoped !== null, fn($q) => $q->whereIn('id', $scoped))
            ->pluck('name', 'id')->toArray();

        $rows = [];
        foreach (array_unique(array_merge(array_keys($salary), array_keys($allow), array_keys($head))) as $did) {
            if ($scoped !== null && !in_array((int) $did, $scoped)) continue;
            if ($filters['department'] && (int) $did !== (int) $filters['department']) continue;
            $cur = $this->curFor($did, $salary, $allow);
            $s = ($salary[$did]['amt'] ?? 0) * $factor;
            $a = $allow[$did]['amt'] ?? 0;
            $rows[] = [
                'Department'              => $deptNames[$did] ?? 'N/A',
                'Headcount'               => (int) ($head[$did] ?? 0),
                'Salary Budget'           => $this->money($s, $cur),
                'Allowance Budget'        => $this->money($a, $cur),
                'Total Workforce Budget'  => $this->money($s + $a, $cur),
            ];
        }
        usort($rows, fn($x, $y) => strcmp($x['Department'], $y['Department']));

        return ['columns' => ['Department', 'Headcount', 'Salary Budget', 'Allowance Budget', 'Total Workforce Budget'], 'rows' => $rows];
    }

    /** #6 Workforce Budget vs Actual. */
    public function budgetVsActual(array $filters): array
    {
        $resortId = $this->resort->resort_id;
        $scoped   = Common::getScopedDepartmentIds();
        $annualMode = empty($filters['month']);
        $factor   = $annualMode ? 12 : 1;
        $salary   = $this->salaryMap('dept', $filters['year']);
        $allow    = $this->allowanceMap('dept', $filters['year'], $filters['month'] ?? null);
        $actual   = $this->actualByDeptMap($filters['year']);
        $head     = $this->deptHeadcountMap($filters['year']);
        $deptNames = DB::table('resort_departments')->where('resort_id', $resortId)
            ->when($scoped !== null, fn($q) => $q->whereIn('id', $scoped))
            ->pluck('name', 'id')->toArray();

        $rows = [];
        foreach (array_unique(array_merge(array_keys($salary), array_keys($allow), array_keys($actual))) as $did) {
            if ($scoped !== null && !in_array((int) $did, $scoped)) continue;
            if ($filters['department'] && (int) $did !== (int) $filters['department']) continue;
            $cur = $this->curFor($did, $salary, $allow);
            $b = ($salary[$did]['amt'] ?? 0) * $factor + ($allow[$did]['amt'] ?? 0);
            $a = (float) ($actual[$did] ?? 0);
            $rows[] = [
                'Department'      => $deptNames[$did] ?? 'N/A',
                'Headcount'       => (int) ($head[$did] ?? 0),
                'Budgeted Cost'   => $this->money($b, $cur),
                'Actual Cost'     => $this->money($a, $cur),
                'Budget Variance' => $this->money($b - $a, $cur),
                'Variance (%)'    => $this->pct($b - $a, $b),
            ];
        }
        usort($rows, fn($x, $y) => strcmp($x['Department'], $y['Department']));

        return ['columns' => ['Department', 'Headcount', 'Budgeted Cost', 'Actual Cost', 'Budget Variance', 'Variance (%)'], 'rows' => $rows];
    }

    /** #10 Department Workforce Cost. */
    public function departmentWorkforceCost(array $filters): array
    {
        $resortId = $this->resort->resort_id;
        $scoped   = Common::getScopedDepartmentIds();
        $annualMode = empty($filters['month']);
        $factor   = $annualMode ? 12 : 1;
        $salary   = $this->salaryMap('dept', $filters['year']);
        $allow    = $this->allowanceMap('dept', $filters['year'], $filters['month'] ?? null);
        $head     = $this->deptHeadcountMap($filters['year']);
        $deptNames = DB::table('resort_departments')->where('resort_id', $resortId)
            ->when($scoped !== null, fn($q) => $q->whereIn('id', $scoped))
            ->pluck('name', 'id')->toArray();

        $rows = [];
        foreach (array_unique(array_merge(array_keys($salary), array_keys($allow), array_keys($head))) as $did) {
            if ($scoped !== null && !in_array((int) $did, $scoped)) continue;
            if ($filters['department'] && (int) $did !== (int) $filters['department']) continue;
            $cur = $this->curFor($did, $salary, $allow);
            $s = ($salary[$did]['amt'] ?? 0) * $factor;
            $a = $allow[$did]['amt'] ?? 0;
            $rows[] = [
                'Department'        => $deptNames[$did] ?? 'N/A',
                'Headcount'         => (int) ($head[$did] ?? 0),
                'Planned Salary'    => $this->money($s, $cur),
                'Planned Allowances'=> $this->money($a, $cur),
                'Total Cost'        => $this->money($s + $a, $cur),
            ];
        }
        usort($rows, fn($x, $y) => strcmp($x['Department'], $y['Department']));

        return ['columns' => ['Department', 'Headcount', 'Planned Salary', 'Planned Allowances', 'Total Cost'], 'rows' => $rows];
    }

    /** #12 New Position Requests (from vacancies). */
    public function newPositionRequests(array $filters): array
    {
        $resortId = $this->resort->resort_id;
        $scoped   = Common::getScopedDepartmentIds();

        $rows = DB::table('vacancies as v')
            ->leftJoin('resort_departments as d', 'd.id', '=', 'v.department')
            ->leftJoin('resort_positions as p', 'p.id', '=', 'v.position')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'v.created_by')
            ->where('v.Resort_id', $resortId)
            ->when($scoped !== null, fn($q) => $q->whereIn('v.department', $scoped))
            ->when($filters['year'], fn($q) => $q->whereRaw('YEAR(v.created_at) = ?', [$filters['year']]))
            ->when($filters['status'], fn($q) => $q->where('v.status', $filters['status']))
            ->when(true, fn($q) => $this->applyDuration($q, $filters, 'v.created_at'))
            ->orderByDesc('v.created_at')
            ->get([
                'v.created_at', 'd.name as department', 'p.position_title', 'v.Total_position_required', 'v.status',
                DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as requested_by"),
            ])
            ->map(fn($r) => [
                'Request Date'       => $r->created_at ? Carbon::parse($r->created_at)->format('d M Y') : 'N/A',
                'Department'         => $r->department ?? 'N/A',
                'Position'           => $r->position_title ?? 'N/A',
                'Requested Headcount'=> (int) ($r->Total_position_required ?: 1),
                // Map the requisition workflow state to Approved / Pending / Rejected.
                'Status'             => $this->requisitionStatus($r->status),
                'Requested By'       => trim($r->requested_by) ?: 'N/A',
            ])->all();

        return ['columns' => ['Request Date', 'Department', 'Position', 'Requested Headcount', 'Status', 'Requested By'], 'rows' => $rows];
    }

    /** #13 Workforce Planning Approval Status. */
    public function approvalStatus(array $filters): array
    {
        $resortId = $this->resort->resort_id;
        $scoped   = Common::getScopedDepartmentIds();

        // All in-scope departments (so we can show "Not Submitted" ones too).
        $depts = DB::table('resort_departments')->where('resort_id', $resortId)
            ->when($scoped !== null, fn($q) => $q->whereIn('id', $scoped))
            ->when($filters['department'], fn($q) => $q->where('id', $filters['department']))
            ->orderBy('name')->get(['id', 'name']);

        // Latest submission per department for the year.
        $subs = DB::table('budget_statuses as bs')
            ->join('manning_responses as mr', 'mr.id', '=', 'bs.Budget_id')
            ->leftJoin('resort_admins as sub', 'sub.id', '=', 'bs.created_by')
            ->where('bs.resort_id', $resortId)
            ->when($filters['year'], fn($q) => $q->where('mr.year', $filters['year']))
            ->orderByDesc('bs.created_at')
            ->get([
                'bs.Department_id', 'bs.status', 'bs.created_at', 'bs.updated_at',
                DB::raw("TRIM(CONCAT(COALESCE(sub.first_name,''),' ',COALESCE(sub.last_name,''))) as submitted_by"),
            ])->groupBy('Department_id');

        $rows = [];
        foreach ($depts as $d) {
            $s = optional($subs->get($d->id))->first();
            // NOTE: the 3-level HR→Finance→GM approval chain is not stored granularly;
            // budget_statuses only records Genrated (approved) / Rejected. Approval
            // Status is mapped from that; per-level pending is shown as "Pending".
            $status = $s
                ? (strtolower((string) $s->status) === 'rejected' ? 'Rejected' : (strtolower((string) $s->status) === 'genrated' ? 'Approved' : 'Pending'))
                : 'Pending';
            $row = [
                'Department'        => $d->name,
                'Submission Status' => $s ? 'Submitted' : 'Not Submitted',
                'Submission Date'   => $s && $s->created_at ? Carbon::parse($s->created_at)->format('d M Y') : 'N/A',
                'Submitted By'      => $s ? (trim($s->submitted_by) ?: 'N/A') : 'N/A',
                'Approval Status'   => $status,
                'Action Date'       => $s && $s->updated_at ? Carbon::parse($s->updated_at)->format('d M Y') : 'N/A',
            ];
            if ($filters['status'] && strcasecmp($row['Approval Status'], $filters['status']) !== 0) continue;
            $rows[] = $row;
        }

        return ['columns' => ['Department', 'Submission Status', 'Submission Date', 'Submitted By', 'Approval Status', 'Action Date'], 'rows' => $rows];
    }

    /** #14 Workforce Planning Revision History. */
    public function revisionHistory(array $filters): array
    {
        $resortId = $this->resort->resort_id;
        $scoped   = Common::getScopedDepartmentIds();

        $records = DB::table('budget_statuses as bs')
            ->join('manning_responses as mr', 'mr.id', '=', 'bs.Budget_id')
            ->leftJoin('resort_departments as d', 'd.id', '=', 'bs.Department_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'bs.created_by')
            ->where('bs.resort_id', $resortId)
            ->when($filters['year'], fn($q) => $q->where('mr.year', $filters['year']))
            ->when($scoped !== null, fn($q) => $q->whereIn('bs.Department_id', $scoped))
            ->when($filters['department'], fn($q) => $q->where('bs.Department_id', $filters['department']))
            ->when(true, fn($q) => $this->applyDuration($q, $filters, 'bs.created_at'))
            ->orderBy('bs.Department_id')->orderBy('bs.created_at')
            ->get([
                'bs.Department_id', 'd.name as dept', 'bs.created_at', 'bs.status', 'bs.comments', 'bs.OtherComments',
                DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as requested_by"),
            ]);

        // Revision number = the Nth revision of THAT department's budget.
        $seq = [];
        $rows = [];
        foreach ($records as $r) {
            $did = (int) $r->Department_id;
            $seq[$did] = ($seq[$did] ?? 0) + 1;
            $remark = trim((string) ($r->comments ?: $r->OtherComments)) ?: 'N/A';
            $rows[] = [
                'Department'             => $r->dept ?? 'N/A',
                'Revision No.'           => $seq[$did],
                'Revision Requested Date'=> $r->created_at ? Carbon::parse($r->created_at)->format('d M Y') : 'N/A',
                'Requested By'           => trim($r->requested_by) ?: 'N/A',
                'Remark'                 => $remark,
                'Status'                 => $r->status ?: 'N/A',
            ];
        }

        return ['columns' => ['Department', 'Revision No.', 'Revision Requested Date', 'Requested By', 'Remark', 'Status'], 'rows' => $rows];
    }

    /** #16 Employment Type Planning (actual headcount by employment category). */
    public function employmentTypePlanning(array $filters): array
    {
        $resortId = $this->resort->resort_id;
        $scoped   = Common::getScopedDepartmentIds();

        $rows = DB::table('employees as e')
            ->leftJoin('resort_departments as d', 'd.id', '=', 'e.Dept_id')
            ->where('e.resort_id', $resortId)
            ->whereIn('e.status', ['Active', 'Probationary'])
            ->whereNotNull('e.employment_type')->where('e.employment_type', '<>', '')
            ->when($scoped !== null, fn($q) => $q->whereIn('e.Dept_id', $scoped))
            ->when($filters['department'], fn($q) => $q->where('e.Dept_id', $filters['department']))
            ->when($filters['employment_type'], fn($q) => $q->where('e.employment_type', $filters['employment_type']))
            ->groupBy('e.employment_type', 'e.Dept_id', 'd.name')
            ->orderBy('e.employment_type')->orderBy('d.name')
            ->select('e.employment_type', 'd.name as department', DB::raw('COUNT(*) as cnt'))
            ->get()
            ->map(fn($r) => [
                'Employment Type'  => $r->employment_type,
                'Department'       => $r->department ?? 'N/A',
                'Planned Headcount'=> (int) $r->cnt,
            ])->all();

        return ['columns' => ['Employment Type', 'Department', 'Planned Headcount'], 'rows' => $rows];
    }

    /** #19 Workforce Cost by Position. */
    public function costByPosition(array $filters): array
    {
        $sal = $this->salaryMap('position', $filters['year']);
        $alw = $this->allowanceMap('position', $filters['year']);
        $rows = $this->seatQuery($filters)->orderBy('p.position_title')->get()
            ->map(function ($r) use ($sal, $alw) {
                $pid = (int) $r->position_id;
                $cur = $this->curFor($pid, $sal, $alw);
                $salary = ($sal[$pid]['amt'] ?? 0) * 12;   // consolidated annual basic
                $allow  = $alw[$pid]['amt'] ?? 0;          // full-year allowances
                return [
                    'Position'         => $r->position_title,
                    'Grade'            => $this->gradeName($r->grade),
                    'Planned Headcount'=> (int) $r->approved,
                    'Salary Budget'    => $this->money($salary, $cur),
                    'Allowance Budget' => $this->money($allow, $cur),
                    'Total Cost'       => $this->money($salary + $allow, $cur),
                ];
            })->all();

        return ['columns' => ['Position', 'Grade', 'Planned Headcount', 'Salary Budget', 'Allowance Budget', 'Total Cost'], 'rows' => $rows];
    }

    /** #20 Workforce Planning Executive Summary. */
    public function executiveSummary(array $filters): array
    {
        $seats = $this->seatQuery($filters)->get();
        $salary = $this->salaryMap('dept', $filters['year']);
        $allow  = $this->allowanceMap('dept', $filters['year']);
        $budget = array_sum(array_map(fn($v) => ($v['amt'] ?? 0) * 12, $salary)) + array_sum(array_map(fn($v) => $v['amt'] ?? 0, $allow));
        $actual = array_sum($this->actualByDeptMap($filters['year']));

        return [
            'columns' => ['Total Approved Positions', 'Planned Headcount', 'Filled Headcount', 'Vacancies', 'Workforce Budget', 'Budget Variance'],
            'rows'    => [[
                'Total Approved Positions' => $seats->count(),
                'Planned Headcount'        => (int) $seats->sum('approved'),
                'Filled Headcount'         => (int) $seats->sum('filled'),
                'Vacancies'                => (int) $seats->sum('vacant'),
                'Workforce Budget'         => $this->money($budget),
                'Budget Variance'          => $this->money($budget - $actual),
            ]],
        ];
    }
}
