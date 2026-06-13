<?php
namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ManningandbudgetingConfigfiles;
use App\Services\BudgetCalculationService;
use App\Jobs\ConsolidateBudgetImportJob;
use App\Imports\ConsolidateBudgetImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\StoreConsolidateBudgetParent;
use App\Models\StoreConsolidateBudgetChild;
use App\Models\StoreManningResponseParent;
use App\Models\StoreManningResponseChild;
use App\Models\ResortDivision;
use App\Models\Employee;
use App\Models\ResortDepartment;
use App\Models\ResortPosition;
use App\Models\ResortBudgetCost;
use App\Models\EmployeeAllowance;
use App\Helpers\Common;
use App\Models\ManningResponse;
use App\Models\PositionMonthlyData;
use URL;
use DB;
use Validator;
use Auth;
use Carbon\Carbon;
use App\Models\BudgetStatus;
use App\Models\ResortsChildNotifications;
use App\Models\ResortVacantBudgetCostAssignment;
use App\Models\ResortSection;
use App\Models\ResortEmployeeBudgetCostConfiguration;
use App\Models\ResortVacantBudgetCost;
use App\Models\ResortVacantBudgetCostConfiguration;
use App\Models\ResortSiteSettings;
use App\Models\PublicHoliday;


class BudgetController extends Controller
{
    protected $budgetCalculationService;
    protected $resort;

    public function __construct(BudgetCalculationService $budgetCalculationService)
    {
        $this->resort = $resortId = auth()->guard('resort-admin')->user();
        if(!$this->resort) return;
    }

    public function ViewManning(Request $request)
    {
        $page_title = 'View Manning';
        $year = $request->input('year') ?? date('Y');
        $resortId = auth()->guard('resort-admin')->user()->resort_id;
        $Budget_id = $data['manning_response_id'] ?? null;
        $Message_id = $data['Message_id'] ?? null;
        $departmentsData = collect();
        $rank = config('settings.Position_Rank');
        $employeeRankPosition = Common::getEmployeeRankPosition( $this->resort->getEmployee);

        if(($employeeRankPosition['position'] != "HR" && ($employeeRankPosition['rank'] != "HOD" || $employeeRankPosition['rank'] != "XCOM" )) && ($employeeRankPosition['position'] != "GM" && ($employeeRankPosition['rank'] != "HOD" || $employeeRankPosition['rank'] != "XCOM" )) && ($employeeRankPosition['position'] != "Finance" && ($employeeRankPosition['rank'] != "HOD" || $employeeRankPosition['rank'] != "XCOM" ))) {
            $departments = ResortDepartment::where('id',$this->resort->getEmployee->Dept_id)->where('resort_id', $resortId)->get();
        }elseif($employeeRankPosition['position'] == "Finance" && ($employeeRankPosition['rank'] == "HOD" || $employeeRankPosition['rank'] == "XCOM" )){
            $employeeDeptId = $this->resort->getEmployee->Dept_id;
            // Get all Finance/GM approved dept ids
            $manningResponseDeptsId = ManningResponse::where('year', $year)
                ->where('resort_id', $resortId)
                ->whereIn('budget_process_status', ['Finance', 'GM'])
                ->pluck('dept_id')
                ->toArray();

            // If no finance/GM dept found → fallback to employee dept
            $deptIds = !empty($manningResponseDeptsId)
                ? $manningResponseDeptsId
                : [$employeeDeptId];

            // Final optimized department fetch
            $departments = ResortDepartment::where('resort_id', $resortId)
                ->whereIn('id', $deptIds)
                ->get();

        }elseif($employeeRankPosition['position'] == "GM" && ($employeeRankPosition['rank'] == "HOD" || $employeeRankPosition['rank'] == "XCOM" )){
            $employeeDeptId = $this->resort->getEmployee->Dept_id;
            // Get all Finance/GM approved dept ids
            $manningResponseDeptsId = ManningResponse::where('year', $year)
                ->where('resort_id', $resortId)
                ->where('budget_process_status', 'GM')
                ->pluck('dept_id')
                ->toArray();

            // If no finance/GM dept found → fallback to employee dept
            $deptIds = !empty($manningResponseDeptsId)
                ? $manningResponseDeptsId
                : [$employeeDeptId];

            // Final optimized department fetch
            $departments = ResortDepartment::where('resort_id', $resortId)
                ->whereIn('id', $deptIds)
                ->get();
        }
        else
        {
            $departments = ResortDepartment::where('resort_id', $resortId)->get();
        }

        // Batch-prefetch every relation the inner loop needs ONCE per request
        // (was firing 4–5 queries per position × N positions × N departments,
        // which made the page sit on a "loading…" spinner for 30–60 s+).
        $departmentIds = $departments->pluck('id')->all();

        // Restrict pmd join to ONLY this year's manning_responses. Without
        // this, MAX(pmd.vacantcount/headcount) below aggregates across pmd
        // rows for every year the position has ever existed in — so a
        // position with vacantcount=10 in 2025 and 2 in 2027 returns 10
        // when viewing year=2027.
        $manningResponseIdsForYear = ManningResponse::where('year', $year)
            ->where('resort_id', $resortId)
            ->whereIn('dept_id', $departmentIds)
            ->pluck('id')
            ->all();

        $allPositionsRaw = DB::table('resort_positions as p')
            ->leftJoin('position_monthly_data as pmd', function ($join) use ($manningResponseIdsForYear) {
                $join->on('p.id', '=', 'pmd.position_id');
                if (!empty($manningResponseIdsForYear)) {
                    $join->whereIn('pmd.manning_response_id', $manningResponseIdsForYear);
                } else {
                    // No manning_responses for this year → pmd contributes nothing
                    $join->whereRaw('1 = 0');
                }
            })
            ->leftJoin('manning_responses as mr', function ($join) use ($year, $resortId) {
                $join->on('pmd.manning_response_id', '=', 'mr.id')
                    ->where('mr.year', '=', $year)
                    ->where('mr.resort_id', '=', $resortId);
            })
            ->where('p.resort_id', '=', $resortId)
            ->whereIn('p.dept_id', $departmentIds)
            ->select(
                'p.id',
                'mr.id as Budget_id',
                'p.position_title',
                'p.dept_id',
                DB::raw('COALESCE(MAX(pmd.vacantcount), 0) as vacantcount'),
                DB::raw('COALESCE(MAX(pmd.headcount), 0) as headcount')
            )
            ->groupBy('p.id', 'p.position_title', 'mr.id', 'p.dept_id')
            ->get();
        $positionsByDept = $allPositionsRaw->groupBy('dept_id');
        $positionIds = $allPositionsRaw->pluck('id')->unique()->all();

        // 1) Active employees for every position in a single query.
        //    Tight filter: same resort + dept + status='Active' AND not past
        //    their last_working_day. Without these guards stale rows leak in
        //    (transferred-out, resigned, terminated employees still tagged
        //    with the old position_id) which inflated filled counts and made
        //    vacantcount appear to be 0.
        $today = \Carbon\Carbon::today()->toDateString();
        $employeesByPosition = empty($positionIds) ? collect() : DB::table('employees as e')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->whereIn('e.position_id', $positionIds)
            ->where('e.resort_id', $resortId)
            ->whereIn('e.Dept_id', $departmentIds)
            ->where('e.status', 'Active')
            ->where(function ($q) use ($today) {
                $q->whereNull('e.last_working_day')
                  ->orWhereDate('e.last_working_day', '>', $today);
            })
            ->get([
                'e.position_id',
                'e.resort_id',
                'e.id as Empid',
                'ra.first_name',
                'ra.last_name',
                'e.Admin_Parent_id',
                'e.rank',
                'e.Dept_id',
                'e.nationality',
                'e.basic_salary',
            ])->groupBy('position_id');

        // 2) BudgetStatus rows keyed by Budget_id.
        $budgetIds = $allPositionsRaw->pluck('Budget_id')->filter()->unique()->all();
        $budgetStatusByBudgetId = empty($budgetIds) ? collect() : BudgetStatus::whereIn('Budget_id', $budgetIds)
            ->get()->keyBy('Budget_id');

        // 3) Child notifications keyed by (message_id|position_id|department_id).
        $messageIds = $budgetStatusByBudgetId->pluck('message_id')->filter()->unique()->all();
        $childNotificationsLookup = collect();
        if (!empty($messageIds)) {
            ResortsChildNotifications::whereIn('Parent_msg_id', $messageIds)
                ->whereIn('Position_id', $positionIds)
                ->whereIn('Department_id', $departmentIds)
                ->get()
                ->each(function ($row) use ($childNotificationsLookup) {
                    $childNotificationsLookup[$row->Parent_msg_id . '|' . $row->Position_id . '|' . $row->Department_id] = $row;
                });
        }

        // 4) Position monthly data grouped by (position_id, manning_response_id).
        $monthlyDataLookup = empty($positionIds) ? collect() : PositionMonthlyData::whereIn('position_id', $positionIds)
            ->whereIn('manning_response_id', $budgetIds ?: [0])
            ->get()
            ->groupBy(function ($row) {
                return $row->position_id . '|' . $row->manning_response_id;
            });

        // 5) Vacant budget cost rows for the year, grouped by (position_id, department_id).
        $vacantRecordsLookup = empty($positionIds) ? collect() : ResortVacantBudgetCost::whereIn('position_id', $positionIds)
            ->whereIn('department_id', $departmentIds)
            ->where('resort_id', $resortId)
            ->where('year', $year)
            ->orderBy('vacant_index')
            ->get()
            ->groupBy(function ($row) {
                return $row->position_id . '|' . $row->department_id;
            });

        foreach ($departments as $department) {
            // Pull the department's positions out of the prefetched bucket.
            $departmentPositions = collect($positionsByDept->get($department->id, collect()))->values();

            foreach ($departmentPositions as $position) {
                $position->employees = collect($employeesByPosition->get($position->id, collect()))->values();

                // Compute vacantcount in real time = headcount − active filled.
                // The stored pmd.vacantcount lags behind transfers/resignations
                // until the manning scheduler catches up — so we recompute from
                // the actually-active employee count above. This is what makes
                // a position with 6 budgeted / 3 filled correctly render 3
                // "Vacant" rows instead of relying on a stored 1.
                $headcount = (int) ($position->headcount ?? 0);
                $filled    = $position->employees->count();
                $position->vacantcount = max(0, $headcount - $filled);

                // Initialise vacant position properties.
                $position->proper_vacant_count = 0;
                $position->is_in_manning_request = false;
                $position->vacant_details = [];

                $budgetStatus = $budgetStatusByBudgetId->get($position->Budget_id);
                if (!$budgetStatus) continue;

                $childKey = $budgetStatus->message_id . '|' . $position->id . '|' . $position->dept_id;
                if (!isset($childNotificationsLookup[$childKey])) continue;

                $position->is_in_manning_request = true;

                // Vacant count = MAX(vacantcount) across the position's monthly data.
                $monthlyKey = $position->id . '|' . $position->Budget_id;
                $monthlyRows = $monthlyDataLookup->get($monthlyKey, collect());
                $maxVacantCount = $monthlyRows->max('vacantcount') ?? 0;
                $position->proper_vacant_count = $maxVacantCount;

                // Fallback to distinct vacant_index count from the cost rows
                // when monthly data has no vacant entry. We can derive both
                // the count and the details from the same prefetched bucket.
                $vacantKey = $position->id . '|' . $position->dept_id;
                $vacantRecords = $vacantRecordsLookup->get($vacantKey, collect());
                if ($position->proper_vacant_count == 0 && $vacantRecords->isNotEmpty()) {
                    $position->proper_vacant_count = $vacantRecords->pluck('vacant_index')->unique()->count();
                }

                if ($position->proper_vacant_count > 0 && $vacantRecords->isNotEmpty()) {
                    foreach ($vacantRecords as $vacantBudgetCost) {
                        $position->vacant_details[$vacantBudgetCost->vacant_index] = $vacantBudgetCost;
                    }
                }
            }


            // Store manning response parent for department
            if ($Budget_id) {
                $smrp = StoreManningResponseParent::updateOrCreate(
                    [
                        "Resort_id" => $resortId,
                        "Department_id" => $department->id,
                        "Budget_id" => $Budget_id
                    ],
                    [
                        "Resort_id" => $resortId,
                        "Department_id" => $department->id,
                        "Budget_id" => $Budget_id
                    ]
                );

                // Store manning response children
                foreach ($departmentPositions as $position) {
                    foreach ($position->employees as $employee)
                    {
                        StoreManningResponseChild::updateOrCreate(
                            [
                                "Parent_SMRP_id" => $smrp->id,
                                'Emp_id' => $employee->Empid
                            ],
                            [
                                "Parent_SMRP_id" => $smrp->id,
                                'Emp_id' => $employee->Empid,
                                'Current_Basic_salary' => $employee->basic_salary ?? 0,
                            ]
                        );
                    }
                }
            }

            // Get vacant positions for department
            $vacant_positions = null;
            if ($Budget_id) {
                $vacant_positions = DB::table('store_manning_response_parents as t1')
                    ->join("store_manning_response_children as t2", "t2.Parent_SMRP_id", "=", "t1.id")
                    ->join('employees as t3', 't3.id', "=", "t2.Emp_id")
                    ->join('resort_positions as t4', 't4.id', "=", "t3.Position_id")
                    ->leftJoin('position_monthly_data as pmd', 't4.id', '=', 'pmd.position_id')
                    ->leftJoin('manning_responses as mr', function($join) use ($resortId, $year) {
                        $join->on('pmd.manning_response_id', '=', 'mr.id')
                            ->where('mr.year', '=', $year);
                    })
                    ->where('t1.resort_id', '=', $resortId)
                    ->where('t1.Department_id', '=', $department->id)
                    ->where('t1.Budget_id', '=', $Budget_id)
                    ->select(
                        't1.id as smrp_id',
                        't2.id as smrp_child_id',
                        't2.Current_Basic_salary as basic_salary',
                        't4.id',
                        'mr.id as Budget_id',
                        't4.position_title',
                        't4.dept_id',
                        't2.Proposed_Basic_salary',
                        DB::raw('COALESCE(MAX(pmd.vacantcount), 0) as vacantcount'),
                        DB::raw('COALESCE(MAX(pmd.headcount), 0) as headcount')
                    )
                    ->groupBy('t4.id', 't4.position_title')
                    ->get();

                foreach ($vacant_positions as $position) {
                    $position->employees = DB::table('employees as e')
                        ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                        ->where('position_id', $position->id)
                        ->get([
                            'e.resort_id',
                            'e.id as Empid',
                            'ra.first_name',
                            'ra.last_name',
                            'e.Admin_Parent_id',
                            'e.rank',
                            'e.Dept_id',
                            'e.nationality',
                            'e.basic_salary'
                        ]);
                }
            }

            // IMPORTANT: do NOT reuse $Budget_id here — the outer-scope variable
            // is checked at the top of the next loop iteration. Stomping it
            // with a Model instance makes line 216/251's `if ($Budget_id)`
            // fire with a Model (not an ID), which corrupts the WHERE clauses
            // on subsequent iterations (and was a major reason the page hung).
            $latestBudget = ManningResponse::where('year', $year)
                ->where('resort_id', $resortId)
                ->where('dept_id', $department->id)
                ->latest()
                ->first();

            if ($latestBudget) {
                $departmentsData->push([
                    'department' => $department,
                    'positions' => $departmentPositions,
                    'vacant_positions' => $vacant_positions,
                    'Budget_id' => $latestBudget->id ?? null,
                ]);
            }
        }

        // Calculate summary statistics
        $summary = [
            'total_positions' => 0,
            'total_employees' => 0,
            'total_vacant' => 0,
            'total_budget' => 0
        ];

        foreach ($departmentsData as $deptData) {
            foreach ($deptData['positions'] as $position) {
                $summary['total_positions']++;
                $summary['total_employees'] += count($position->employees);
                $summary['total_vacant'] += $position->vacantcount;
                foreach ($position->employees as $employee) {
                    $summary['total_budget'] += $employee->basic_salary;
                }
            }
        }


        $resortDepartmentsCount = ResortDepartment::where('resort_id', $resortId)->count();
        $resortManningResponseCount = ManningResponse::where('year', $year) ->where('resort_id', $resortId)->count();
            if($resortDepartmentsCount == $resortManningResponseCount){
                $isBudgetCompleted = true;
            }else{
                $isBudgetCompleted = false;
            }
            // dd($departmentsData);

            return view('resorts.budget.manning', compact(
                'page_title',
                'Budget_id',
                'Message_id',
                'resortId',
                'departmentsData',
                'summary',
                'year',
                'employeeRankPosition',
                'isBudgetCompleted'
            ));
    }

    public function CompareBudget($deptID, $budgetId, ?Request $request = null)
    {
        if (Common::checkRouteWisePermission('resort.budget.comparebudget,{id}', config('settings.resort_permissions.view')) == false) {
            return abort(403, 'Unauthorized access');
        }

        $page_title     = 'Compare Budget';
        $rank           = config('settings.Position_Rank');
        $current_rank   = $this->resort->getEmployee->rank ?? null;
        $available_rank = $rank[$current_rank] ?? '';

        // Always pre-populate to safe empties so the view never sees undefined.
        $positions       = collect();
        $department      = null;
        $manningResponse = null;
        $aiSuggestions   = [];
        $aiStatus        = null;
        $aiGeneratedAt   = null;
        $totalHodHeadcount  = 0;
        $totalHodBudget     = 0.0;
        $totalAiHeadcount   = 0;
        $totalAiBudget      = 0.0;
        $currencySymbol     = Common::GetResortCurrencySymbol();

        if ($deptID && $budgetId) {
            $department      = ResortDepartment::where('id', $deptID)->where('resort_id', $this->resort->resort_id)->first();
            $manningResponse = ManningResponse::where('id', $budgetId)
                ->where('resort_id', $this->resort->resort_id)
                ->where('dept_id', $deptID)
                ->first();

            // Positions for this department with head/vacant counts. The two
            // leftJoins keep the row even when no position_monthly_data /
            // manning_responses entry exists yet — useful for new depts.
            $positions = DB::table('resort_positions as p')
                ->leftJoin('position_monthly_data as pmd', 'p.id', '=', 'pmd.position_id')
                ->leftJoin('manning_responses as mr', function ($join) use ($deptID, $budgetId) {
                    $join->on('pmd.manning_response_id', '=', 'mr.id')
                        ->where('mr.resort_id', '=', $this->resort->resort_id)
                        ->where('mr.dept_id', '=', $deptID)
                        ->where('mr.id', '=', $budgetId);
                })
                // Belt-and-braces: even if the $deptID URL parameter is
                // a department from another resort, p.resort_id stops
                // that resort's positions from rendering on our page.
                ->where('p.resort_id', '=', $this->resort->resort_id)
                ->where('p.dept_id', '=', $deptID)
                ->select(
                    'p.id',
                    'p.position_title',
                    'p.dept_id',
                    'mr.id as Budget_id',
                    DB::raw('COALESCE(MAX(pmd.vacantcount), 0) as vacantcount'),
                    DB::raw('COALESCE(MAX(pmd.headcount),  0) as headcount')
                )
                ->groupBy('p.id', 'p.position_title', 'p.dept_id')
                ->get();

            // Hydrate the active-employee list (and the per-position current
            // total basic salary) for each position. This drives the HOD-side
            // numbers in the table and the input the AI sees.
            foreach ($positions as $p) {
                $emps = DB::table('employees as e')
                    ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                    ->where('e.position_id', $p->id)
                    ->where('e.resort_id', $this->resort->resort_id)
                    ->whereNotIn('e.status', ['Terminated', 'Inactive', 'Offboarding'])
                    ->select('e.id as Empid', 'ra.first_name', 'ra.last_name', 'e.Admin_Parent_id',
                             'e.rank', 'e.Dept_id', 'e.nationality', 'e.basic_salary', 'e.incremented_date')
                    ->get();
                $p->employees = $emps;
                // Headcount: pmd-derived value if present, else fall back to
                // the count of active employees — gives a sensible HOD figure
                // even before manning is filled.
                $p->headcount = (int) ($p->headcount > 0 ? $p->headcount : $emps->count());

                // HOD budget = planned headcount × per-head salary. A position
                // with 2 planned heads but only 1 filled used to show just the
                // single filled salary (e.g. $550) next to a headcount of 2,
                // which under-read the HOD total and made the comparison
                // against the (headcount-based) AI budget inaccurate. Cost the
                // full planned headcount at the filled average so vacant
                // budgeted slots are priced in (e.g. 2 × $550 = $1,100).
                $filledCount = $emps->count();
                $filledSum   = (float) $emps->sum('basic_salary');
                $perHead     = $filledCount > 0 ? ($filledSum / $filledCount) : 0.0;
                $p->current_budget = (float) (max($p->headcount, $filledCount) * $perHead);

                $totalHodHeadcount += $p->headcount;
                $totalHodBudget    += $p->current_budget;
            }

            // Try to use the cached AI recommendation. If none — or the user
            // clicked Regenerate via ?regenerate=1 — call the FastAPI service.
            $regenerate = $request && $request->query('regenerate') === '1';
            if ($manningResponse && (empty($manningResponse->ai_suggestions) || $regenerate)) {
                $cached = $this->fetchAiBudgetRecommendations($manningResponse, $positions, $department);
                $aiSuggestions = $cached['recommendations'] ?? [];
                $aiStatus      = $cached['status'] ?? null;
                $aiGeneratedAt = $cached['generated_at'] ?? null;

                // When the user clicked Regenerate, redirect back to the
                // canonical URL so a refresh doesn't auto-fire another
                // (potentially expensive) AI call AND so the user sees a
                // flash toast confirming what happened. Without this the
                // page just renders with the old cached suggestions and
                // a small "AI failed" chip — easy to miss.
                if ($regenerate) {
                    $flashKind = $aiStatus === 'ready' ? 'success'
                              : ($aiStatus === 'timeout' ? 'warning' : 'error');
                    $flashMsg  = $aiStatus === 'ready'
                        ? 'AI workforce-planning analysis regenerated.'
                        : ($aiStatus === 'timeout'
                            ? 'The AI service did not respond in time. Showing the previously cached suggestions.'
                            : 'AI workforce-planning analysis failed — check that the AI service is reachable. Showing the previously cached suggestions.');
                    return redirect()
                        ->route('resort.budget.comparebudget', ['id' => $deptID, 'budgetid' => $budgetId])
                        ->with('ai_flash_kind', $flashKind)
                        ->with('ai_flash_msg', $flashMsg);
                }
            } elseif ($manningResponse) {
                $aiSuggestions = json_decode($manningResponse->ai_suggestions, true) ?: [];
                $aiStatus      = $manningResponse->ai_suggestions_status;
                $aiGeneratedAt = $manningResponse->ai_suggestions_at;
            }

            // Roll AI totals up for the table footer.
            foreach ($positions as $p) {
                $rec = $aiSuggestions[(string) $p->id] ?? null;
                if ($rec) {
                    $p->ai_headcount    = (int) ($rec['suggested_headcount'] ?? 0);
                    $p->ai_budget       = (float) ($rec['suggested_budget']  ?? 0);
                    $p->ai_justification = (string) ($rec['justification']    ?? '');
                    $totalAiHeadcount += $p->ai_headcount;
                    $totalAiBudget    += $p->ai_budget;
                } else {
                    $p->ai_headcount    = null;
                    $p->ai_budget       = null;
                    $p->ai_justification = '';
                }
            }
        }

        return view('resorts.budget.compare', compact(
            'page_title', 'available_rank',
            'positions', 'department', 'manningResponse',
            'aiSuggestions', 'aiStatus', 'aiGeneratedAt',
            'totalHodHeadcount', 'totalHodBudget',
            'totalAiHeadcount', 'totalAiBudget',
            'currencySymbol'
        ));
    }

    /**
     * AJAX-only Regenerate AI: fires the FastAPI call, persists the
     * new suggestions on manning_responses, and returns JSON the
     * compare-budget table can use to re-render in place.
     *
     * Previously the page used a hard-link to `?regenerate=1` which
     * caused a FULL page navigation (visible flash, scroll position
     * lost, slow on mobile). The new flow: button click → AJAX POST →
     * JS swaps the AI columns in the existing table.
     */
    public function CompareBudgetRegenerateAi($deptID, $budgetId, Request $request)
    {
        // Same auth/permission posture as CompareBudget.
        $manningResponse = ManningResponse::find($budgetId);
        if (!$manningResponse || (int) $manningResponse->resort_id !== (int) $this->resort->resort_id) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }
        $department = ResortDepartment::where('id', $deptID)
            ->where('resort_id', $this->resort->resort_id)
            ->first();
        if (!$department) {
            return response()->json(['success' => false, 'message' => 'Department not found'], 404);
        }

        // Replicate the position + employee fetch used by CompareBudget
        // so the AI sees the same context.
        $positions = ResortPosition::where('dept_id', $deptID)
            ->where('resort_id', $this->resort->resort_id)
            ->where('status', 'active')
            ->get();

        foreach ($positions as $p) {
            $emps = Employee::where('Position_id', $p->id)
                ->where('Dept_id', $deptID)
                ->whereIn('status', ['Active', 'Probationary'])
                ->get(['id', 'basic_salary']);
            $p->headcount      = $emps->count();
            $p->current_budget = (float) $emps->sum('basic_salary');
        }

        $cached = $this->fetchAiBudgetRecommendations($manningResponse, $positions, $department);
        $aiSuggestions = $cached['recommendations'] ?? [];
        $aiStatus      = $cached['status'] ?? null;
        $aiGeneratedAt = $cached['generated_at'] ?? null;

        // Flatten the per-position recommendations + roll totals so the
        // JS can swap the table in one pass.
        $rows = [];
        $totalAiHc = 0;
        $totalAiBudget = 0.0;
        foreach ($positions as $p) {
            $rec = $aiSuggestions[(string) $p->id] ?? null;
            $row = [
                'position_id'    => (int) $p->id,
                'position_title' => $p->position_title,
                'ai_headcount'   => null,
                'ai_budget'      => null,
                'ai_annual'      => null,
                'ai_justification' => '',
            ];
            if ($rec) {
                $row['ai_headcount']     = (int) ($rec['suggested_headcount'] ?? 0);
                $row['ai_budget']        = (float) ($rec['suggested_budget'] ?? 0);
                $row['ai_annual']        = $row['ai_budget'] * 12;
                $row['ai_justification'] = (string) ($rec['justification'] ?? '');
                $totalAiHc     += $row['ai_headcount'];
                $totalAiBudget += $row['ai_budget'];
            }
            $rows[] = $row;
        }

        return response()->json([
            'success'         => $aiStatus === 'ready',
            'status'          => $aiStatus,
            'generated_at'    => $aiGeneratedAt ? (string) $aiGeneratedAt : null,
            'message'         => $aiStatus === 'ready'
                ? 'AI workforce-planning analysis regenerated.'
                : ($aiStatus === 'timeout'
                    ? 'The AI service did not respond in time. Try again in a moment.'
                    : 'AI workforce-planning analysis failed — check that the AI service is reachable.'),
            'rows'            => $rows,
            'total_headcount' => $totalAiHc,
            'total_budget'    => $totalAiBudget,
            'total_annual'    => $totalAiBudget * 12,
        ]);
    }

    /**
     * Hit the FastAPI service for per-position headcount + budget
     * recommendations. Cached on the manning_responses row so subsequent
     * page loads return instantly. Failures are stored too (with a
     * `failed` status) so we can show a sensible UI without re-calling.
     */
    private function fetchAiBudgetRecommendations($manningResponse, $positions, $department): array
    {
        // Stamp 'pending' so a concurrent view doesn't re-call the AI.
        $manningResponse->ai_suggestions_status = 'pending';
        $manningResponse->save();

        $payloadPositions = [];
        foreach ($positions as $p) {
            $payloadPositions[] = [
                'position_id'                 => (int) $p->id,
                'position_title'              => (string) $p->position_title,
                'current_headcount'           => (int) $p->headcount,
                'current_total_basic_salary'  => (float) $p->current_budget,
            ];
        }
        $payload = [
            'department_name' => optional($department)->name ?: 'Department',
            'currency'        => 'USD',
            'positions'       => $payloadPositions,
        ];

        // Resolve the AI host. Prefer AI_BASE_URL (added for the new
        // budget / compliance endpoints) then fall back to the existing
        // AI_URL the rest of the codebase already uses — that's the var
        // the live .env actually defines, so without this fallback the
        // controller silently hit localhost:8001 on prod and Regenerate
        // AI looked like a no-op.
        $url = rtrim((string) (env('AI_BASE_URL') ?: env('AI_URL', 'http://localhost:8001')), '/') . '/budget_recommendations';

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
            // Same Hostinger-proxy guardrails as the visa AI calls — 90 s
            // upper bound (the page intentionally has a longer budget than
            // the OCR endpoints because this is a one-shot text-only LLM
            // call, not an image OCR pipeline).
            CURLOPT_TIMEOUT        => 90,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $response = curl_exec($curl);
        $errno    = curl_errno($curl);
        $err      = curl_error($curl);
        curl_close($curl);

        if ($errno !== 0 || !$response) {
            $manningResponse->ai_suggestions_status = $errno === CURLE_OPERATION_TIMEDOUT ? 'timeout' : 'failed';
            $manningResponse->ai_suggestions_at     = now();
            $manningResponse->save();
            \Log::warning('budget AI call failed: ' . ($err ?: 'no response'));
            return [
                'recommendations' => json_decode($manningResponse->ai_suggestions, true) ?: [],
                'status'          => $manningResponse->ai_suggestions_status,
                'generated_at'    => $manningResponse->ai_suggestions_at,
            ];
        }

        $decoded = json_decode($response, true);
        $recs    = is_array($decoded) ? ($decoded['recommendations'] ?? null) : null;
        if (!is_array($recs)) {
            $manningResponse->ai_suggestions_status = 'failed';
            $manningResponse->ai_suggestions_at     = now();
            $manningResponse->save();
            \Log::warning('budget AI returned unparseable body: ' . substr((string) $response, 0, 500));
            return [
                'recommendations' => json_decode($manningResponse->ai_suggestions, true) ?: [],
                'status'          => 'failed',
                'generated_at'    => $manningResponse->ai_suggestions_at,
            ];
        }

        $manningResponse->ai_suggestions        = json_encode($recs);
        $manningResponse->ai_suggestions_status = 'ready';
        $manningResponse->ai_suggestions_at     = now();
        $manningResponse->save();

        return [
            'recommendations' => $recs,
            'status'          => 'ready',
            'generated_at'    => $manningResponse->ai_suggestions_at,
        ];
    }

    public function ViewBudget(Request $request)
    {
        if(Common::checkRouteWisePermission('resort.budget.viewbudget',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }

        $page_title = 'View Budget';

        // Use requested year or fallback to current year
        $year = $request->input('year') ?? date('Y');

        $resortId = auth()->guard('resort-admin')->user()->resort_id;
        $Budget_id = $request->input('manning_response_id') ?? null;
        $Message_id = $request->input('Message_id') ?? null;

        $rank = config('settings.Position_Rank');

        $employeeRankPosition = Common::getEmployeeRankPosition( $this->resort->getEmployee);

        if($this->resort->is_master_admin == 0){
            if(($employeeRankPosition['position'] != "HR" && ($employeeRankPosition['rank'] != "HOD" || $employeeRankPosition['rank'] != "XCOM" )) && ($employeeRankPosition['position'] != "GM" && ($employeeRankPosition['rank'] != "HOD" || $employeeRankPosition['rank'] != "XCOM" )) && ($employeeRankPosition['position'] != "Finance" && ($employeeRankPosition['rank'] != "HOD" || $employeeRankPosition['rank'] != "XCOM" ))) {
                $rank_wise_departments = ResortDepartment::where('id', $this->resort->getEmployee->Dept_id)
                    ->where('resort_id', $resortId)
                    ->pluck('id')->toArray();
            }
            elseif($employeeRankPosition['position'] == "Finance" && ($employeeRankPosition['rank'] == "HOD" || $employeeRankPosition['rank'] == "XCOM" )){
                $employeeDeptId = $this->resort->getEmployee->Dept_id;
                // Get all Finance/GM approved dept ids
                $manningResponseDeptsId = ManningResponse::where('year', $year)
                    ->where('resort_id', $resortId)
                    ->whereIn('budget_process_status', ['Finance', 'GM'])
                    ->pluck('dept_id')
                    ->toArray();

                // If no finance/GM dept found → fallback to employee dept
                $deptIds = !empty($manningResponseDeptsId)
                    ? $manningResponseDeptsId
                    : [$employeeDeptId];

                // Final optimized department fetch
                $rank_wise_departments = ResortDepartment::where('resort_id', $resortId)
                    ->whereIn('id', $deptIds)
                    ->pluck('id')
                    ->toArray();

            }elseif($employeeRankPosition['position'] == "GM" && ($employeeRankPosition['rank'] == "HOD" || $employeeRankPosition['rank'] == "XCOM" )){
                $employeeDeptId = $this->resort->getEmployee->Dept_id;
                // Get all Finance/GM approved dept ids
                $manningResponseDeptsId = ManningResponse::where('year', $year)
                    ->where('resort_id', $resortId)
                    ->where('budget_process_status', 'GM')
                    ->pluck('dept_id')
                    ->toArray();

                // If no finance/GM dept found → fallback to employee dept
                $deptIds = !empty($manningResponseDeptsId)
                    ? $manningResponseDeptsId
                    : [$employeeDeptId];

                // Final optimized department fetch
                $rank_wise_departments = ResortDepartment::where('resort_id', $resortId)
                    ->whereIn('id', $deptIds)
                    ->pluck('id')
                    ->toArray();
            }
            else{
                $rank_wise_departments = ResortDepartment::where('resort_id', $resortId)
                    ->pluck('id')->toArray();
            }
        }else{
            $rank_wise_departments = ResortDepartment::where('resort_id', $resortId)
                    ->pluck('id')->toArray();
        }

        // Iterate ALL active departments (subject to role scope), then
        // look up the optional matching ManningResponse + non-terminal
        // BudgetStatus for metadata. Iterating manning_responses directly
        // (as the prior code did) silently DROPPED departments that
        // hadn't been put through a manning cycle yet — exactly the
        // Exec Office / L&D / Security gap on live.
        $manningByDept = ManningResponse::with('department')
            ->where('manning_responses.year', $year)
            ->where('manning_responses.resort_id', $resortId)
            ->whereIn('dept_id', $rank_wise_departments)
            ->leftJoin('budget_statuses as bs', function ($join) {
                $join->on('bs.Budget_id', '=', 'manning_responses.id');
            })
            ->whereIn('bs.id', function ($query) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('budget_statuses')
                    ->groupBy('Budget_id');
            })
            ->whereNotIn('bs.status', ['Rejected', 'Accepted', 'Approved'])
            ->get(['bs.Budget_id', 'bs.message_id as Message_id', 'manning_responses.*'])
            ->keyBy('dept_id');

        $catalogDepartments = ResortDepartment::where('resort_id', $resortId)
            ->whereIn('id', $rank_wise_departments)
            ->where('status', 'active')
            ->get();

        // Synthesize the "$departments" the rest of the method expects:
        // a collection of objects with `dept_id`, `Budget_id`, `Message_id`
        // and the manning-response columns. Depts without a manning get
        // null Budget_id / Message_id but still flow through the loop.
        $departments = $catalogDepartments->map(function ($dept) use ($manningByDept) {
            $manning = $manningByDept->get($dept->id);
            if ($manning) {
                $manning->dept_id = $dept->id;
                return $manning;
            }
            // Stub object with the minimum fields the downstream code reads.
            return (object) [
                'id'                   => null,
                'dept_id'              => $dept->id,
                'Budget_id'            => null,
                'Message_id'           => null,
                'department'           => $dept,
                'year'                 => $dept->year ?? null,
                'resort_id'            => $dept->resort_id ?? null,
                'total_headcount'      => 0,
                'total_filled_positions' => 0,
                'total_vacant_positions' => 0,
            ];
        });

        foreach ($departments as $department) {
            // Ensure we get vacant count from manning_responses properly filtered by position, dept_id, year, resort_id
            $department->departmentPositions = DB::table('resort_positions as p')
                ->leftJoin('employees as e', 'e.Position_id', '=', 'p.id')
                ->leftJoin('position_monthly_data as pmd', 'p.id', '=', 'pmd.position_id')
                ->leftJoin('manning_responses as mr', function($join) use ($year, $resortId, $department) {
                    $join->on('pmd.manning_response_id', '=', 'mr.id')
                         ->where('mr.year', '=', $year)
                         ->where('mr.resort_id', '=', $resortId)
                         ->where('mr.dept_id', '=', $department->dept_id);
                })
                ->where('p.resort_id', '=', $resortId)
                ->where('p.dept_id', '=', $department->dept_id)
                ->select(
                    'p.id',
                    'mr.id as Budget_id',
                    'p.position_title',
                    'p.id as Position_id',
                    'p.dept_id',
                    DB::raw('COALESCE(MAX(pmd.vacantcount), 0) as vacantcount'),
                    DB::raw('COALESCE(MAX(pmd.headcount), 0) as headcount')
                )
                ->groupBy('p.id', 'p.position_title', 'mr.id')
                ->get();

            if ($department->departmentPositions->isNotEmpty()) {
                foreach ($department->departmentPositions as $position) {
                    $employees = DB::table('employees as e')
                        ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                        ->where('position_id', $position->Position_id)
                        ->where('Dept_id', $position->dept_id)
                        ->where('e.status', 'Active')
                        ->get([
                            'e.resort_id',
                            'e.id as Empid',
                            'ra.first_name',
                            'ra.last_name',
                            'e.Position_id',
                            'e.Admin_Parent_id',
                            'e.rank',
                            'e.Dept_id',
                            'e.nationality',
                            'e.basic_salary',
                            'e.incremented_date',
                            DB::raw('0 as Proposed_Basic_salary')
                        ]);

                    $position->employees = $employees;

                    // Initialize vacant position properties
                    $position->proper_vacant_count = 0;
                    $position->is_in_manning_request = false;
                    $position->vacant_details = [];

                    // Get vacant count using resorts_child_notifications table (joined with manning_responses)
                    // resorts_child_notifications -> budget_statuses -> manning_responses -> position_monthly_data
                    $budgetStatus = BudgetStatus::where('Budget_id', $position->Budget_id)->first();

                    if ($budgetStatus) {
                        // Check if THIS specific position is in the manning request via resorts_child_notifications
                        $childNotification = ResortsChildNotifications::where('Parent_msg_id', $budgetStatus->message_id)
                            ->where('Position_id', $position->id)
                            ->where('Department_id', $position->dept_id)
                            ->first();

                        if ($childNotification) {
                            $position->is_in_manning_request = true;

                            // Real-time vacant = max(0, budgeted headcount − Active filled).
                            // pmd.vacantcount is a denormalized field that lags
                            // behind reality (it isn't updated when employees
                            // are assigned/removed), so reading it directly was
                            // showing "Vacant 1 / Vacant 2" even after a seat
                            // had been filled. Use the same headcount-minus-
                            // active-filled formula as Workforce + view-manning.
                            $positionMonthlyData = PositionMonthlyData::where('position_id', $position->id)
                                ->where('manning_response_id', $position->Budget_id)
                                ->get();

                            $maxHeadcount = 0;
                            foreach ($positionMonthlyData as $monthlyData) {
                                $maxHeadcount = max($maxHeadcount, (int) ($monthlyData->headcount ?? 0));
                            }

                            $today = \Carbon\Carbon::today()->toDateString();
                            $activeFilled = \App\Models\Employee::where('resort_id', $resortId)
                                ->where('Position_id', $position->id)
                                ->where('Dept_id', $position->dept_id)
                                ->where('status', 'Active')
                                ->where(function ($q) use ($today) {
                                    $q->whereNull('last_working_day')
                                      ->orWhereDate('last_working_day', '>', $today);
                                })
                                ->count();

                            $position->proper_vacant_count = max(0, $maxHeadcount - $activeFilled);

                            // If headcount is unknown (no manning row), fall back
                            // to resort_vacant_budget_costs as the legacy hint —
                            // but clamp to (budgeted - filled) wouldn't apply
                            // here since we don't know the budget. Keep the
                            // legacy count only when we genuinely have no signal.
                            if ($maxHeadcount === 0 && $position->proper_vacant_count == 0) {
                                $actualVacantCount = DB::table('resort_vacant_budget_costs')
                                    ->where('position_id', $position->id)
                                    ->where('department_id', $position->dept_id)
                                    ->where('resort_id', $resortId)
                                    ->where('year', $year)
                                    ->distinct('vacant_index')
                                    ->count('vacant_index');

                                if ($actualVacantCount > 0) {
                                    $position->proper_vacant_count = $actualVacantCount;
                                }
                            }

                            // Get vacant details from resort_vacant_budget_costs for each vacant index
                            if ($position->proper_vacant_count > 0) {
                                $vacantRecords = ResortVacantBudgetCost::where('position_id', $position->id)
                                    ->where('department_id', $position->dept_id)
                                    ->where('resort_id', $resortId)
                                    ->where('year', $year)
                                    ->orderBy('vacant_index')
                                    ->get();

                                foreach ($vacantRecords as $vacantBudgetCost) {
                                    $position->vacant_details[$vacantBudgetCost->vacant_index] = $vacantBudgetCost;
                                }
                            }
                        }
                    }

                    if ($employees->isNotEmpty() && $position->Budget_id != "") {
                        $smrp = StoreManningResponseParent::updateOrCreate(
                            [
                                "Resort_id" => $resortId,
                                "Department_id" => $position->dept_id,
                                "Budget_id" => $position->Budget_id
                            ],
                            [
                                "Resort_id" => $resortId,
                                "Department_id" => $position->dept_id,
                                "Budget_id" => $position->Budget_id
                            ]
                        );

                        foreach ($employees as $emp) {
                            $basic_salary = ((float)$emp->basic_salary > 0.0) ? $emp->basic_salary : 0.0;

                            $budgetData = StoreManningResponseChild::updateOrCreate(
                                [
                                    "Parent_SMRP_id" => $smrp->id,
                                    'Emp_id' => $emp->Empid
                                ],
                                [
                                    "Parent_SMRP_id" => $smrp->id,
                                    'Emp_id' => $emp->Empid,
                                    'Current_Basic_salary' => $basic_salary,
                                ]
                            );

                            $vacant_positions = DB::table('store_manning_response_parents as t1')
                                ->join("store_manning_response_children as t2", "t2.Parent_SMRP_id", "=", "t1.id")
                                ->join('employees as t3', 't3.id', "=", "t2.Emp_id")
                                ->join('resort_positions as t4', 't4.id', "=", "t3.Position_id")
                                ->leftJoin('position_monthly_data as pmd', 't4.id', '=', 'pmd.position_id')
                                ->leftJoin('manning_responses as mr', function ($join) use ($resortId, $year) {
                                    $join->on('pmd.manning_response_id', '=', 'mr.id')
                                        ->where('mr.year', '=', $year);
                                })
                                ->where('t1.resort_id', '=', $resortId)
                                ->where('t1.Department_id', '=', $position->dept_id)
                                ->where('t3.Position_id', '=', $emp->Position_id)
                                ->where('t2.Emp_id', '=', $emp->Empid)
                                ->where('t1.Budget_id', '=', $position->Budget_id)
                                ->select(
                                    't1.id as smrp_id',
                                    't2.id as smrp_child_id',
                                    't2.Current_Basic_salary as basic_salary',
                                    't4.id',
                                    'mr.id as Budget_id',
                                    't4.position_title',
                                    't4.dept_id',
                                    't2.Emp_id',
                                    't2.Proposed_Basic_salary',
                                    't2.Months',
                                    DB::raw('COALESCE(MAX(pmd.vacantcount), 0) as vacantcount'),
                                    DB::raw('COALESCE(MAX(pmd.headcount), 0) as headcount')
                                )
                                ->groupBy('t4.id', 't4.position_title')
                                ->first();

                            $emp->vacantData = $vacant_positions;
                        }
                    }
                }
            }
        }

        // Get divisions with departments for hierarchical view
        $divisions = ResortDivision::where('resort_id', $resortId)
            ->where('status', 'active')
            ->with(['departments' => function($query) use ($rank_wise_departments, $resortId, $year) {
                $query->where('resort_id', $resortId)
                    ->whereIn('id', $rank_wise_departments)
                    ->where('status', 'active')
                    ->with(['sections' => function($q) use ($resortId) {
                        $q->where('resort_id', $resortId)->where('status', 'active');
                    }]);
            }])
            ->get();

        // Use the $departments variable that already has vacant logic applied
        // (prepared in lines 492-669 with proper vacant_details and is_in_manning_request)
        $manningResponses = $departments;

        $available_rank = $employeeRankPosition['position'];

        // Build a Budget_id → is-locked map. Once GM has approved the budget
        // (status='Approved' OR budget_process_status='Approved'/'Completed')
        // the Revise Budget button must disable for that department, so HR
        // can't reopen something the GM already signed off on.
        $budgetIdsForYear = $departments->pluck('Budget_id')->filter()->unique()->all();
        $approvedBudgetIds = empty($budgetIdsForYear)
            ? collect()
            : DB::table('budget_statuses')
                ->whereIn('Budget_id', $budgetIdsForYear)
                ->where('resort_id', $resortId)
                ->whereIn('status', ['Approved', 'Completed'])
                ->pluck('Budget_id')
                ->unique();
        // Also treat manning_responses.budget_process_status as authoritative
        // (when Finance/GM marks it Approved, that flips this flag too).
        $approvedByProcess = empty($budgetIdsForYear)
            ? collect()
            : ManningResponse::whereIn('id', $budgetIdsForYear)
                ->whereIn('budget_process_status', ['Approved', 'Completed', 'GM_Approved'])
                ->pluck('id');
        $approvedBudgetIds = $approvedBudgetIds->merge($approvedByProcess)->unique()->values();
        $approvedBudgetIdsLookup = $approvedBudgetIds->flip(); // O(1) `isset` checks

        // Get resort budget costs for the modal
        $resortCosts = ResortBudgetCost::where('resort_id', $resortId)
            ->where('status', 'active')
            ->orderBy('id')
            ->get();

        return view('resorts.budget.view_budget_hierarchical')->with(compact(
            'page_title',
            'divisions',
            'resortId',
            'year',
            'employeeRankPosition',
            'available_rank',
            'manningResponses',
            'departments',
            'resortCosts',
            'approvedBudgetIdsLookup'
        ));
    }

    public function ConsolidateBudget(Request $request)
    {
        if(Common::checkRouteWisePermission('resort.budget.consolidatedbudget',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }

        $page_title = 'Consolidated Budget';
        try {
            $resortId = auth()->guard('resort-admin')->user()->resort_id;
            // Honour the year selector on the page. Was hardcoded to
            // now()->year, which is why every selection on the dropdown
            // produced the SAME numbers (whichever year you pick, the
            // controller still computed the current one).
            $requested = $request->input('year');
            $year = (is_numeric($requested) && (int) $requested >= 2000 && (int) $requested <= 2100)
                ? (int) $requested
                : (int) now()->year;

            // The page used to read from `store_consolidate_budget_parents` /
            // `store_consolidate_budget_children` — a frozen JSON snapshot
            // that only refreshed when someone explicitly clicked Save. So
            // any salary edit, cost-template change, employee add/remove
            // or per-employee cost override since the last save would make
            // the per-dept totals diverge from the Liability page and the
            // view-budget page. Live-compute now using the same source data
            // as Common::computeYearlyBudgetTotal — single source of truth.
            [$MainArray, $DepartmentTotal, $header] = $this->buildLiveConsolidatedArrays($resortId, $year);

            $employeeRankPosition = Common::getEmployeeRankPosition( $this->resort->getEmployee);

            return view('resorts.budget.consolidated')->with(compact('page_title','MainArray','header','DepartmentTotal','resortId','employeeRankPosition','year'));
        } catch( \Exception $e ) {
            \Log::emergency("File: ".$e->getFile ());
            \Log::emergency("Line: ".$e->getLine());
            \Log::emergency("Message: ".$e->getMessage());
        }
    }

    /**
     * Build the consolidated-budget arrays LIVE so the per-dept totals
     * match the Liability page and view-budget.
     *
     * Returns [$MainArray, $DepartmentTotal, $header] in the exact shape
     * resources/views/resorts/budget/consolidated.blade.php expects:
     *
     *   $MainArray = [
     *       'Dept Name' => [
     *           [position_title, employee_count, rank, nationality,
     *            current_basic_salary, jan_total, feb_total, ..., dec_total],
     *           ...
     *       ],
     *       ...
     *   ];
     *   $DepartmentTotal = ['Dept Name' => annual_total, ...];
     *   $header          = ['Jan 2026', 'Feb 2026', ..., 'Dec 2026'];
     *
     * Each monthly column for a position group = sum of (salary_m + sum
     * of cost_configs_m) across every employee in that position group.
     * Salary uses the same override-then-fallback chain
     * Common::computeYearlyBudgetTotal walks; cost configs use saved
     * resort_employee_budget_cost_configurations overrides ∪ live
     * fallback from resort_budget_costs templates (via
     * Common::computeBudgetCostMonthlyValue).
     */
    private function buildLiveConsolidatedArrays(int $resortId, int $year): array
    {
        $header = [];
        for ($m = 1; $m <= 12; $m++) {
            $header[] = Carbon::create($year, $m, 1)->format('M Y');
        }

        // Per-employee saved overrides (resort_employee_budget_cost_configurations)
        // are stored as USD per project convention — no conversion needed.
        //
        // Cost TEMPLATES (resort_budget_costs) however have an `amount_unit`
        // column that can be 'MVR', 'USD', or '%'. When the live fallback
        // formula (Common::computeBudgetCostMonthlyValue) returns the value
        // for an MVR template, that value is denominated in MVR — view-budget's
        // JS multiplies by mvrToUsdRate (1/15.42) before summing. Mirror
        // that here. e.g. "Ramadan Bonus = 3000 MVR/year" should add
        // $194.55/year per applicable Maldivian, not $3,000.
        $dollarToMvr = (float) (DB::table('resort_site_settings')
            ->where('resort_id', $resortId)
            ->value('DollertoMVR') ?: 15.42);
        if ($dollarToMvr <= 0) $dollarToMvr = 15.42;
        $mvrToUsdRate = 1.0 / $dollarToMvr;

        $departments = ResortDepartment::where('resort_id', $resortId)
            ->where('status', 'active')
            ->get(['id', 'name']);

        $resortCosts = DB::table('resort_budget_costs')
            ->where('resort_id', $resortId)
            ->where('status', 'active')
            ->get(['id', 'particulars', 'cost_title', 'amount', 'amount_unit', 'cost_type', 'frequency', 'details']);

        // Pre-fetch all per-month salary overrides once, keyed by employee.
        $empMonthlyOverrides = DB::table('resort_employee_monthly_salaries')
            ->where('resort_id', $resortId)
            ->where('year', $year)
            ->get(['employee_id', 'month', 'current_salary', 'proposed_salary'])
            ->groupBy('employee_id');

        // Pre-fetch all saved cost-config overrides once, keyed by
        // (employee_id, resort_budget_cost_id, month). All values are
        // stored in USD per project convention.
        $savedCostOverrides = DB::table('resort_employee_budget_cost_configurations')
            ->where('resort_id', $resortId)
            ->where('year', $year)
            ->get(['employee_id', 'resort_budget_cost_id', 'month', 'value']);
        $savedByKey = [];
        foreach ($savedCostOverrides as $row) {
            $savedByKey[$row->employee_id][$row->resort_budget_cost_id][$row->month] = (float) $row->value;
        }

        // Pre-fetch every employee's assigned allowances (the per-employee
        // assignments from the Employee Detail page → Allowances section,
        // backed by `employees_allowance`). These were missing from the
        // budget aggregator entirely — only the Payroll module read them.
        // That meant the consolidated/view-budget totals diverged from
        // payroll by exactly the sum of every employee's allowance lines.
        //
        // Convention:
        //   amount_unit 'MVR' → convert to USD via 1/DollertoMVR
        //   amount_unit 'USD' (or anything else) → keep as USD
        //   Allowance is paid monthly → divide annual by 12 at use, OR
        //     treat as a monthly figure depending on stored convention.
        // The Payroll calc treats `amount` as a MONTHLY figure (see
        // PayrollController::fetchTimeAttendance around line 2172), so
        // we mirror that here: each row contributes `amount` per month.
        $employeeAllowancesMonthlyUsd = DB::table('employees_allowance as ea')
            ->join('employees as e', 'e.id', '=', 'ea.employee_id')
            ->where('e.resort_id', $resortId)
            ->select(
                'ea.employee_id',
                DB::raw(
                    "SUM(CASE WHEN ea.amount_unit = 'MVR' "
                  . "THEN ea.amount * {$mvrToUsdRate} ELSE ea.amount END) as monthly_usd"
                )
            )
            ->groupBy('ea.employee_id')
            ->pluck('monthly_usd', 'ea.employee_id');

        $MainArray = [];
        $DepartmentTotal = [];

        foreach ($departments as $dept) {
            $employees = DB::table('employees')
                ->where('resort_id', $resortId)
                ->where('Dept_id', $dept->id)
                ->where('status', 'Active')
                ->get(['id', 'Position_id', 'nationality', 'religion', 'basic_salary', 'proposed_salary', 'benefit_grid_level']);

            if ($employees->isEmpty()) continue;

            $byPosition = $employees->groupBy('Position_id');
            $deptTotal  = 0.0;

            foreach ($byPosition as $positionId => $emps) {
                if (!$positionId) continue;

                $position = DB::table('resort_positions')
                    ->where('id', $positionId)
                    ->first(['position_title', 'Rank']);
                if (!$position) continue;

                // Aggregate monthly totals across every employee in this
                // position group.
                $monthly = array_fill(1, 12, 0.0);

                foreach ($emps as $emp) {
                    // CANONICAL aggregator — same call view-budget makes via
                    // getEmployeeMonthlyData below and the same call Liability's
                    // Common::computeYearlyBudgetTotal will be migrated to.
                    // Computes (per the helper's docblock): per-month salary
                    // override or fallback + cost-template legs (with MVR→USD)
                    // + per-employee allowance leg. Returns annual USD total.
                    //
                    // Replaces the inline loop above so the three pages
                    // (consolidated-budget, view-budget, Liability) are
                    // FORCED to produce the same number — no more drift
                    // from any single page adding a new leg the others miss.
                    $annualUsd = Common::annualBudgetForEmployee($resortId, $year, $emp);
                    // Distribute evenly across the 12 monthly cells for the
                    // per-position breakdown row. The cell-by-cell view
                    // still uses Common::annualCostForEmployee per cost +
                    // per month, but the row total is what drives the
                    // dept badge so this is what matters for matching.
                    $perMonth = $annualUsd / 12;
                    for ($m = 1; $m <= 12; $m++) {
                        $monthly[$m] += $perMonth;
                    }
                }

                // Display values for the row header columns. The first
                // employee in the group seeds nation + basic salary; the
                // count goes in column 1 (No. of position).
                $first = $emps->first();
                $rankLabel = $position->Rank ?? '—';
                $nation    = $first->nationality ?? '—';

                // Currency-bearing cells (basic salary + 12 monthly totals)
                // are pre-rendered through Common::formatCurrency so they
                // switch symbol + value when the resort display currency
                // toggles between Dollar and MVR. Was emitted as raw
                // number_format strings before — the badge total swapped
                // currency at render time but the inner table cells stayed
                // in raw USD numbers, which is what the user reported.
                $basicCell = Common::formatCurrency((float) $first->basic_salary, 'USD');

                $rowArr = [
                    $position->position_title,
                    $emps->count(),
                    $rankLabel,
                    $nation,
                    $basicCell,
                ];
                for ($m = 1; $m <= 12; $m++) {
                    $rowArr[] = Common::formatCurrency($monthly[$m], 'USD');
                }

                $MainArray[$dept->name][] = $rowArr;
                $deptTotal += array_sum($monthly);
            }

            // -- vacant salaries + vacant cost configs for this dept --
            // view-budget rolls vacant slots into the per-dept badge
            // total (each vacant_index counts as another budgeted
            // position). Mirror that here so the consolidated dept
            // total = view-budget's dept total.
            //
            // CRITICAL: resort_vacant_budget_costs can carry STALE rows
            // from before a slot was filled (e.g. HRM has a vacant row
            // worth $7,893.75 but headcount=1 and the slot is now
            // filled). view-budget's getPositionEmployees endpoint
            // recomputes the live vacancy count as
            //   max(0, maxHeadcount - activeFilled)
            // (see BudgetController:2287-2303) and only loads vacant
            // indexes <= that count. Apply the same filter here so
            // orphaned vacant rows don't inflate the dept total.
            $realVacantCountByPosition = []; // populated below if vacants exist
            $vacants = DB::table('resort_vacant_budget_costs')
                ->where('resort_id', $resortId)
                ->where('department_id', $dept->id)
                ->where('year', $year)
                ->get(['id', 'position_id', 'department_id', 'vacant_index', 'basic_salary', 'current_salary']);

            if ($vacants->isNotEmpty()) {
                // Real-vacancy count per position (max budgeted headcount
                // minus currently-Active filled). Keep only vacants whose
                // vacant_index falls inside that range.
                $vacantPositionIds = $vacants->pluck('position_id')->unique()->all();
                $maxHeadByPosition = DB::table('position_monthly_data')
                    ->whereIn('position_id', $vacantPositionIds)
                    ->selectRaw('position_id, MAX(COALESCE(headcount, 0)) as max_head')
                    ->groupBy('position_id')
                    ->pluck('max_head', 'position_id')
                    ->toArray();
                $today = Carbon::today()->toDateString();
                $filledByPosition = DB::table('employees')
                    ->where('resort_id', $resortId)
                    ->where('Dept_id', $dept->id)
                    ->whereIn('Position_id', $vacantPositionIds)
                    ->where('status', 'Active')
                    ->where(function ($q) use ($today) {
                        $q->whereNull('last_working_day')
                          ->orWhereDate('last_working_day', '>', $today);
                    })
                    ->selectRaw('Position_id, COUNT(*) as filled')
                    ->groupBy('Position_id')
                    ->pluck('filled', 'Position_id')
                    ->toArray();
                $realVacantCountByPosition = [];
                foreach ($vacantPositionIds as $pid) {
                    $maxHead = (int) ($maxHeadByPosition[$pid] ?? 0);
                    $filled  = (int) ($filledByPosition[$pid] ?? 0);
                    $realVacantCountByPosition[$pid] = max(0, $maxHead - $filled);
                }
                $vacants = $vacants->filter(function ($v) use ($realVacantCountByPosition) {
                    $real = $realVacantCountByPosition[$v->position_id] ?? 0;
                    return (int) $v->vacant_index <= $real;
                })->values();
            }

            // CANONICAL vacant aggregation — call Common::annualBudgetForVacantSlot
            // for each surviving vacant. Replaces the legacy salary+cost dual
            // loops below. By going through the same helper view-budget calls
            // via getVacantMonthlyData, the two pages can no longer disagree
            // on vacant slots.
            $vacantTotal = 0.0;
            foreach ($vacants as $v) {
                $vacantTotal += Common::annualBudgetForVacantSlot($resortId, $year, $v);
            }
            $deptTotal += $vacantTotal;

            if (!empty($MainArray[$dept->name])) {
                $DepartmentTotal[$dept->name] = $deptTotal;
            } elseif ($vacantTotal > 0) {
                // Dept has no filled employees but does have vacant
                // budget — keep it visible with the vacant-only total.
                $DepartmentTotal[$dept->name] = $deptTotal;
                $MainArray[$dept->name] = []; // no display rows yet
            }
        }

        return [$MainArray, $DepartmentTotal, $header];
    }

    public function viewConsolidatedBudget(Request $request, $resortId)
    {
        $selectedYear = $request->get('year', Carbon::now()->year);
        $employeeRankPosition = Common::getEmployeeRankPosition( $this->resort->getEmployee);

        // Retrieve manning responses by resort and year
        if(($employeeRankPosition['position'] != "HR" && ($employeeRankPosition['rank'] != "HOD" || $employeeRankPosition['rank'] != "XCOM" )) && ($employeeRankPosition['position'] != "GM" && ($employeeRankPosition['rank'] != "HOD" || $employeeRankPosition['rank'] != "XCOM" )) && ($employeeRankPosition['position'] != "Finance" && ($employeeRankPosition['rank'] != "HOD" || $employeeRankPosition['rank'] != "XCOM" ))) {
            $yearlyBudgets = ManningResponse::where('year', $selectedYear)
                                ->where('resort_id', $resortId)
                                ->where('dept_id', $this->resort->getEmployee->Dept_id)
                                ->with(['positionMonthlyData', 'GetBudgetStatus'])
                                ->get();
        }elseif($employeeRankPosition['position'] == "Finance" && ($employeeRankPosition['rank'] == "HOD" || $employeeRankPosition['rank'] == "XCOM" )){
            $yearlyBudgets = ManningResponse::where('year', $selectedYear)
                            ->where('resort_id', $resortId)
                            ->whereIn('budget_process_status', ['Finance', 'GM'])
                            ->with(['positionMonthlyData', 'GetBudgetStatus'])
                            ->get();
        }elseif($employeeRankPosition['position'] == "GM" && ($employeeRankPosition['rank'] == "HOD" || $employeeRankPosition['rank'] == "XCOM" )){
            $yearlyBudgets = ManningResponse::where('year', $selectedYear)
                            ->where('resort_id', $resortId)
                            ->where('budget_process_status', 'GM')
                            ->with(['positionMonthlyData', 'GetBudgetStatus'])
                            ->get();
        }
        else
        {
            $yearlyBudgets = ManningResponse::where('year', $selectedYear)
            ->where('resort_id', $resortId)
            ->with(['positionMonthlyData', 'GetBudgetStatus'])
            ->get();
        }

        // Department iteration source-of-truth: ALL active resort_departments
        // (subject to the role scope above), NOT just departments with a
        // matching manning response. The prior implementation iterated
        // `foreach ($yearlyBudgets as $response)` — so a department that
        // hadn't been put through manning yet (e.g. Executive Office,
        // Learning and Development, Security) silently vanished from the
        // consolidated view even though it had active employees and
        // vacant slots that DID exist in the canonical aggregator.
        //
        // Now: we fetch `$inScopeDeptIds` from the same role rules, then
        // iterate those departments. The manning response (if any) is
        // looked up by dept_id for metadata only.
        $manningByDept = $yearlyBudgets->keyBy('dept_id');

        if (($employeeRankPosition['position'] != "HR" && ($employeeRankPosition['rank'] != "HOD" || $employeeRankPosition['rank'] != "XCOM")) && ($employeeRankPosition['position'] != "GM" && ($employeeRankPosition['rank'] != "HOD" || $employeeRankPosition['rank'] != "XCOM")) && ($employeeRankPosition['position'] != "Finance" && ($employeeRankPosition['rank'] != "HOD" || $employeeRankPosition['rank'] != "XCOM"))) {
            // Non-HR/GM/Finance HOD: own department only.
            $inScopeDeptIds = [$this->resort->getEmployee->Dept_id];
        } elseif ($employeeRankPosition['position'] == "Finance" && ($employeeRankPosition['rank'] == "HOD" || $employeeRankPosition['rank'] == "XCOM")) {
            // Finance: depts whose manning has reached Finance / GM stage.
            $inScopeDeptIds = $yearlyBudgets->pluck('dept_id')->all();
        } elseif ($employeeRankPosition['position'] == "GM" && ($employeeRankPosition['rank'] == "HOD" || $employeeRankPosition['rank'] == "XCOM")) {
            // GM: depts whose manning has reached GM stage.
            $inScopeDeptIds = $yearlyBudgets->pluck('dept_id')->all();
        } else {
            // HR / master admin / catchall: ALL active departments of the
            // resort, even those without a manning response yet.
            $inScopeDeptIds = ResortDepartment::where('resort_id', $resortId)
                ->where('status', 'active')
                ->pluck('id')
                ->all();
        }

        $departmentsInScope = ResortDepartment::with('division', 'sections')
            ->whereIn('id', $inScopeDeptIds)
            ->where('resort_id', $resortId)
            ->get();

        if ($departmentsInScope->isNotEmpty())
        {
            // Initialize the consolidated budget array and retrieve unique headers
            $consolidatedBudget = [];
            $header = ResortBudgetCost::where('resort_id', $resortId)
                ->distinct()
                ->pluck('particulars')
                ->toArray();

            foreach ($departmentsInScope as $department) {
                // Optional metadata — null when this dept hasn't been put
                // through manning for the year yet.
                $response = $manningByDept->get($department->id);

                $divisionName = $department->division ? $department->division->name : 'No Division';
                $divisionId = $department->division ? $department->division->id : 0;
                $departmentName = $department->name;
                $departmentId = $department->id;

                // Initialize division if not exists
                if (!isset($consolidatedBudget[$divisionName])) {
                    $consolidatedBudget[$divisionName] = [
                        'division_id' => $divisionId,
                        'departments' => []
                    ];
                }

                // Initialize department if not exists. Manning-response
                // metadata is optional — depts without a manning row yet
                // show zero headcounts but still render their employees
                // and vacant slots from the canonical helpers.
                if (!isset($consolidatedBudget[$divisionName]['departments'][$departmentName])) {
                    $consolidatedBudget[$divisionName]['departments'][$departmentName] = [
                        'department_id' => $departmentId,
                        'manning_response_id' => $response ? $response->id : null,
                        'total_headcount'     => $response ? $response->total_headcount : 0,
                        'filled_positions'    => $response ? $response->total_filled_positions : 0,
                        'vacant_positions'    => $response ? $response->total_vacant_positions : 0,
                        'sections' => [],
                        'positions' => []
                    ];
                }

                // Position-set source-of-truth: align with ViewBudget. That
                // method iterates ALL resort_positions for the department
                // (LEFT-joined to position_monthly_data), so a position with
                // no PMD row still shows up. The previous implementation here
                // looped over $response->positionMonthlyData->unique() which
                // silently DROPPED any position not yet registered in PMD —
                // producing a smaller consolidated total than view-budget for
                // the same department (HR / A&G were each $24,114.52 short
                // because of this).
                //
                // We also union in any position that has an active employee in
                // this department even if it isn't catalogued under
                // resort_positions.dept_id (legacy data), again matching
                // view-budget's effective coverage.
                $catalogPositions = ResortPosition::where('resort_id', $resortId)
                    ->where('dept_id', $departmentId)
                    ->get();

                $employeePositionIds = DB::table('employees')
                    ->where('resort_id', $resortId)
                    ->where('Dept_id', $departmentId)
                    ->where('status', 'Active')
                    ->whereNotNull('Position_id')
                    ->pluck('Position_id')
                    ->unique()
                    ->all();

                $missingEmployeePositionIds = array_diff(
                    $employeePositionIds,
                    $catalogPositions->pluck('id')->all()
                );
                if (!empty($missingEmployeePositionIds)) {
                    $extra = ResortPosition::where('resort_id', $resortId)
                        ->whereIn('id', $missingEmployeePositionIds)
                        ->get();
                    $catalogPositions = $catalogPositions->concat($extra);
                }

                foreach ($catalogPositions as $position) {
                    $positionName = $position->position_title;
                    $positionRank = $position->Rank;
                    $positionId = $position->id;
                    $sectionId = $position->section_id ?? null;

                    // Get vacant count from resorts_child_notifications through manning_response
                    // resorts_child_notifications -> budget_statuses -> manning_responses -> position_monthly_data
                    $budgetStatus = $response ? BudgetStatus::where('Budget_id', $response->id)->first() : null;
                    $isPositionInManningRequest = false;

                    // Initialize max counts
                    $maxHeadcount = 0;
                    $maxFilledcount = 0;
                    $maxVacantFromMonthly = 0;
                    $maxVacantcount = 0;

                    // Get position monthly data for this specific position from the manning_response.
                    // Empty collection when this dept has no manning yet — the
                    // canonical helpers below still aggregate employees and
                    // vacants from their respective tables.
                    $positionMonthlyDataForPosition = $response
                        ? $response->positionMonthlyData->where('position_id', $positionId)
                        : collect();

                    // Calculate max counts across all months for this position
                    foreach ($positionMonthlyDataForPosition as $dataByMonth) {
                        $headcount = $dataByMonth->headcount ?? 0;
                        $filledcount = $dataByMonth->filledcount ?? 0;
                        $vacantcount = $dataByMonth->vacantcount ?? 0;

                        // Calculate max counts for the current position
                        $maxHeadcount = max($maxHeadcount, $headcount);
                        $maxFilledcount = max($maxFilledcount, $filledcount);
                        $maxVacantFromMonthly = max($maxVacantFromMonthly, $vacantcount);
                    }

                    if ($budgetStatus) {
                        // Check if THIS specific position is in the manning request via resorts_child_notifications
                        $childNotification = ResortsChildNotifications::where('Parent_msg_id', $budgetStatus->message_id)
                            ->where('Position_id', $positionId)
                            ->where('Department_id', $departmentId)
                            ->first();

                        if ($childNotification) {
                            $isPositionInManningRequest = true;

                            // Priority 1: Use vacant count from position_monthly_data (from manning_responses)
                            // This comes from manning_responses.total_vacant_positions properly filtered by position, dept_id, year
                            $maxVacantcount = $maxVacantFromMonthly;

                        } else {
                            // Position not in resorts_child_notifications, use calculated value from position_monthly_data
                            $maxVacantcount = $maxVacantFromMonthly > 0 ? $maxVacantFromMonthly : max(0, $maxHeadcount - $maxFilledcount);
                        }
                    } else {
                        // No budget status, calculate from position_monthly_data if available
                        $maxVacantcount = $maxVacantFromMonthly > 0 ? $maxVacantFromMonthly : max(0, $maxHeadcount - $maxFilledcount);
                    }

                    // Get employees for this position. Selecting `e.id` (no
                    // alias) in addition to `emp_id` so Common::annualBudgetForEmployee()
                    // can read $employee->id, and adding nationality+religion
                    // for that helper's Locals/Xpat/Muslim cost-template filter.
                    $employees = DB::table('employees as e')
                        ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                        ->where('e.Position_id', $positionId)
                        ->where('e.status', 'Active')
                        ->where('e.resort_id', $resortId)
                        ->select(
                            'e.id as emp_id',
                            'e.id',
                            'ra.first_name',
                            'ra.last_name',
                            'e.rank',
                            'e.nationality',
                            'e.religion',
                            'e.basic_salary',
                            'e.proposed_salary'
                        )
                        ->get();

                    // Load budget cost configurations for each employee - SUM FOR ENTIRE YEAR
                    foreach ($employees as $employee) {
                        // Get all configurations for the year (all months)
                        $employeeConfigs = ResortEmployeeBudgetCostConfiguration::where('employee_id', $employee->emp_id)
                            ->where('department_id', $departmentId)
                            ->where('position_id', $positionId)
                            ->where('resort_id', $resortId)
                            ->where('year', $selectedYear)
                            ->get();

                        // Calculate yearly totals for salaries
                        // For consolidated budget: Always use employees table values * 12 (same as budget view)
                        $employee->configured_basic_salary = ($employee->basic_salary ?? 0) * 12;
                        $employee->configured_current_salary = ($employee->proposed_salary ?? 0) * 12;

                        // Aggregate budget costs by resort_budget_cost_id (sum all months)
                        $aggregatedConfigs = [];
                        foreach ($employeeConfigs as $config) {
                            $costId = $config->resort_budget_cost_id;

                            if (!isset($aggregatedConfigs[$costId])) {
                                $aggregatedConfigs[$costId] = (object)[
                                    'resort_budget_cost_id' => $costId,
                                    'value' => 0,
                                    'currency' => $config->currency,
                                    'hours' => 0
                                ];
                            }

                            $aggregatedConfigs[$costId]->value += $config->value;
                            $aggregatedConfigs[$costId]->hours += $config->hours ?? 0;
                        }

                        // Convert aggregated array to collection for consistency
                        $employee->budget_configurations = collect(array_values($aggregatedConfigs));

                        // Yearly total: route through the canonical helper so
                        // the AJAX consolidated badges match view-budget
                        // (salary leg with per-month overrides + cost-template
                        // leg with live fallback + per-employee allowance leg).
                        // Common::calculateYearlyTotal is the legacy aggregator
                        // and is intentionally NOT used here anymore.
                        $employee->yearly_total = Common::annualBudgetForEmployee($resortId, (int) $selectedYear, $employee);
                    }

                    // Load vacant budget cost configurations - SUM FOR ENTIRE YEAR.
                    //
                    // Previously this iterated `for ($i = 1; $i <= $maxVacantcount;
                    // $i++)` where $maxVacantcount came from position_monthly_data
                    // (i.e. manning response). That silently DROPPED every
                    // vacant row in resort_vacant_budget_costs whose position
                    // had no PMD entry — exactly the F&B $6,628 gap we saw on
                    // live (and the $20,350 HR gap, and $76,144 Accounting gap).
                    //
                    // Now we iterate the persisted vacant rows directly so
                    // every vacant slot HR created for this (dept, position,
                    // year) shows up regardless of manning state.
                    $vacantConfigurations = [];
                    $vacantRowsForPosition = ResortVacantBudgetCost::where('position_id', $positionId)
                        ->where('department_id', $departmentId)
                        ->where('resort_id', $resortId)
                        ->where('year', $selectedYear)
                        ->orderBy('vacant_index')
                        ->get();
                    // Bump $maxVacantcount so calculatePositionTotal's loop
                    // (which still iterates by index) sees every slot.
                    $maxVacantcount = max($maxVacantcount, $vacantRowsForPosition->count());
                    foreach ($vacantRowsForPosition as $vacantBudgetCost) {
                        $i = (int) $vacantBudgetCost->vacant_index ?: 1;

                        // Get all monthly configurations for this vacant position
                        $vacantCostConfigs = ResortVacantBudgetCostConfiguration::where('vacant_budget_cost_id', $vacantBudgetCost->id)
                            ->get();

                        // For consolidated budget: Always use base values from resort_vacant_budget_costs * 12 (same as budget view)
                        $yearlyBasicSalary = ($vacantBudgetCost->basic_salary ?? 0) * 12;
                        $yearlyCurrentSalary = ($vacantBudgetCost->current_salary ?? 0) * 12;

                        // Update the vacant budget cost with yearly totals
                        $vacantBudgetCost->basic_salary = $yearlyBasicSalary;
                        $vacantBudgetCost->current_salary = $yearlyCurrentSalary;

                        // Aggregate budget costs by resort_budget_cost_id (sum all months)
                        $aggregatedVacantConfigs = [];
                        foreach ($vacantCostConfigs as $config) {
                            $costId = $config->resort_budget_cost_id;

                            if (!isset($aggregatedVacantConfigs[$costId])) {
                                $aggregatedVacantConfigs[$costId] = (object)[
                                    'resort_budget_cost_id' => $costId,
                                    'value' => 0,
                                    'currency' => $config->currency,
                                    'hours' => 0
                                ];
                            }

                            $aggregatedVacantConfigs[$costId]->value += $config->value;
                            $aggregatedVacantConfigs[$costId]->hours += $config->hours ?? 0;
                        }

                        $vacantConfigurations[$i] = [
                            'vacant_budget_cost' => $vacantBudgetCost,
                            'configurations' => collect(array_values($aggregatedVacantConfigs))
                        ];

                        // Yearly total: canonical helper. Note we pass the
                        // ORIGINAL DB row (with monthly basic_salary /
                        // current_salary), NOT the one mutated above where
                        // we set them to yearly×12. The helper computes its
                        // own salary leg from per-month overrides.
                        $vacantForHelper = (object) [
                            'id'             => $vacantBudgetCost->id,
                            'position_id'    => $vacantBudgetCost->position_id,
                            'department_id'  => $vacantBudgetCost->department_id,
                            'vacant_index'   => $vacantBudgetCost->vacant_index,
                            // Reverse the ×12 we did above so the helper
                            // sees monthly values as fallback.
                            'basic_salary'   => $yearlyBasicSalary / 12,
                            'current_salary' => $yearlyCurrentSalary / 12,
                        ];
                        $vacantConfigurations[$i]['yearly_total'] = Common::annualBudgetForVacantSlot($resortId, (int) $selectedYear, $vacantForHelper);
                    }

                    $positionData = [
                        'position_id' => $positionId,
                        'rank' => $positionRank,
                        'max_counts' => [
                            'max_headcount' => $maxHeadcount,
                            'max_vacantcount' => $maxVacantcount,
                            'max_filledcount' => $maxFilledcount,
                        ],
                        'employees' => $employees,
                        'vacant_count' => $maxVacantcount,
                        'vacant_configurations' => $vacantConfigurations
                    ];

                    // If position belongs to a section
                    if ($sectionId) {
                        $section = ResortSection::find($sectionId);
                        $sectionName = $section ? $section->name : 'Unknown Section';

                        if (!isset($consolidatedBudget[$divisionName]['departments'][$departmentName]['sections'][$sectionName])) {
                            $consolidatedBudget[$divisionName]['departments'][$departmentName]['sections'][$sectionName] = [
                                'section_id' => $sectionId,
                                'positions' => []
                            ];
                        }

                        $consolidatedBudget[$divisionName]['departments'][$departmentName]['sections'][$sectionName]['positions'][$positionName] = $positionData;
                    } else {
                        // Position directly under department
                        $consolidatedBudget[$divisionName]['departments'][$departmentName]['positions'][$positionName] = $positionData;
                    }
                }
            }

            // Get additional resort costs by particular cost title
            $resortCosts = ResortBudgetCost::where('resort_id', $resortId)
                ->select('id', 'particulars', 'amount', 'amount_unit')
                ->get();

            // Calculate and store totals for all levels (Position, Section, Department, Division)
            if (!empty($consolidatedBudget)) {
                foreach ($consolidatedBudget as $divisionName => &$divisionData) {
                    $divisionTotal = 0;

                    foreach ($divisionData['departments'] as $departmentName => &$departmentData) {
                        $departmentTotal = 0;

                        // Calculate totals for sections
                        if (!empty($departmentData['sections'])) {
                            foreach ($departmentData['sections'] as $sectionName => &$sectionData) {
                                $sectionTotal = 0;

                                // Calculate totals for positions in section
                                if (!empty($sectionData['positions'])) {
                                    foreach ($sectionData['positions'] as $positionName => &$positionData) {
                                        $positionTotal = Common::calculatePositionTotal($positionData, $resortCosts, $resortId);
                                        $positionData['calculated_total'] = $positionTotal;
                                        $sectionTotal += $positionTotal;
                                    }
                                }

                                $sectionData['calculated_total'] = $sectionTotal;
                                $departmentTotal += $sectionTotal;
                            }
                        }

                        // Calculate totals for direct positions (not in sections)
                        if (!empty($departmentData['positions'])) {
                            foreach ($departmentData['positions'] as $positionName => &$positionData) {
                                $positionTotal = Common::calculatePositionTotal($positionData, $resortCosts, $resortId);
                                $positionData['calculated_total'] = $positionTotal;
                                $departmentTotal += $positionTotal;
                            }
                        }

                        $departmentData['calculated_total'] = $departmentTotal;
                        $divisionTotal += $departmentTotal;
                    }

                    $divisionData['calculated_total'] = $divisionTotal;
                }
                unset($divisionData, $departmentData, $sectionData, $positionData); // Clean up references
            }

                $resortDepartmentsCount = ResortDepartment::where('resort_id', $resortId)->count();
                $resortManningResponseCount = ManningResponse::where('year', $selectedYear) ->where('resort_id', $resortId)->count();

                    if($resortDepartmentsCount == $resortManningResponseCount){
                        $isBudgetCompleted = true;
                    }else{
                        $isBudgetCompleted = false;
                    }
            // Return the partial view for AJAX requests

            // dd($consolidatedBudget);
            if ($request->ajax()) {
                $html = view('resorts.renderfiles.consolidated', compact(
                    'consolidatedBudget',
                    'header',
                    'resortCosts',
                    'selectedYear',
                    'employeeRankPosition'
                ))->render();

                $isBudgetCompleted = true; // ← your custom condition

                return response()->json([
                    'html' => $html,
                    'isBudgetCompleted' => $isBudgetCompleted
                ]);
            }
        }
        else{
            $resortId = auth()->guard('resort-admin')->user()->resort_id;
            $parent_Consolidate = StoreConsolidateBudgetParent::where('Resort_id',auth()->guard('resort-admin')->user()->resort_id)->where('year',$selectedYear)->latest()->first();
            $MainArray=array();
            $DepartmentTotal=array();
            $DepartmentArray=array();

            if(isset($parent_Consolidate))
            {
                $child_Consolidate = StoreConsolidateBudgetChild::where("Parent_SCB_id",$parent_Consolidate->id)->latest()->first();
                $header = json_decode($child_Consolidate->header);
                $data = json_decode($child_Consolidate->Data);
                $header = array_slice($header, 7);
                if(!empty($data))
                {
                    foreach($data as $k=>$p)
                    {
                        $internalArray=array();
                        $division = $p[0];
                        $Department = $p[1];
                        $Position = $p[2];
                        $NoOfPosition = $p[3];
                        $Rank = $p[4];
                        $Nation= $p[5];
                        $Salary = $p[6];
                        $Resortdepartment = ResortDepartment::where('resort_id', $this->resort->resort_id)->where('slug',$Department)->first();
                        $Resortposition = ResortPosition::where('resort_id', $this->resort->resort_id)
                                        ->where('slug', $Position)->first();
                        $remainingValues = array_slice($p, 6);

                        if(!in_array($Resortdepartment->id,$DepartmentArray) || array_key_exists($Resortdepartment->name, $MainArray))
                        {
                            $entry = [
                                $Resortposition->position_title,
                                $NoOfPosition,
                                $Rank,
                                $Nation,
                            ];
                            $MainArray[$Resortdepartment->name][] = array_merge($entry, $remainingValues);
                            $oldArray_value = array_key_exists($Resortdepartment->name, $DepartmentTotal)  ?  $DepartmentTotal[$Resortdepartment->name] : 0;
                            $DepartmentTotal[$Resortdepartment->name]= array_sum($remainingValues) + $oldArray_value ;
                            $DepartmentArray[] = $Resortdepartment->id;
                        }
                    }
                }
            }
            else
            {
                $header  = [];
                $data  = [];
                $Resortposition =     collect();
                $DepartmentTotal=[];
            }

            if ($request->ajax()) {
                return view('resorts.renderfiles.consolidatedold', compact('MainArray','header','DepartmentTotal','resortId'));
            }
        }

        // For non-AJAX requests, return the full view
        return view('budget.consolidated', compact(
            'consolidatedBudget',
            'header',
            'resortCosts',
            'selectedYear'
        ));
    }

    public function config()
    {
        try
        {
            if(Common::checkRouteWisePermission('resort.budget.config',config('settings.resort_permissions.view')) == false){
                return abort(403, 'Unauthorized access');
            }
            $page_title = 'Configuration';
            return view('resorts.budget.config')->with(compact('page_title'));
        } catch( \Exception $e ) {
            \Log::emergency("File: ".$e->getFile());
            \Log::emergency("Line: ".$e->getLine());
            \Log::emergency("Message: ".$e->getMessage());
        }
    }

    public function UploadconfigFiles(Request $request)
    {
        $consolidatdebudget_Year = $request->consolidatdebudget_Year;
        $hasFile = $request->hasFile('consolidatedbudget');

        // Validate xpat/local always
        $rules = [
            'xpat'  => 'required|numeric|min:0',
            'local' => 'required|numeric|min:0',
        ];
        $messages = [
            'xpat.required'  => 'The Xpat Value is required.',
            'local.required' => 'The Local Value is required.',
        ];

        // If a file is being uploaded, also validate it and require year
        if ($hasFile) {
            $rules['consolidatedbudget']       = 'required|file|mimes:xls,xlsx|max:2048';
            $rules['consolidatdebudget_Year']  = 'required';
            $messages['consolidatedbudget.mimes'] = 'The consolidated budget file must be an Excel file (.xls, .xlsx).';
            $messages['consolidatedbudget.max']   = 'The consolidated budget file may not be greater than 2MB.';
            $messages['consolidatdebudget_Year.required'] = 'Please select a year when uploading a file.';
        }

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'msg' => $validator->errors()->first()], 422);
        }
        // $path_path = config( 'settings.Resort_BudgetConfigFiles')."/".Auth::guard('resort-admin')->user()->resort->resort_id;

        try {
            //   $criteria = [];
            $attributes = [
                'xpat' => $request->xpat,
                'local' => $request->local,
                'consolidatdebudget' => null,
                'benifitgrid' => null,
            ];

            //   // Resort Id throw make folders
            //   if (isset($request->consolidatdebudget)) {
            //       $fileName = "Consolidation_Budget" . '.' . $request->consolidatdebudget->getClientOriginalExtension();
            //       Common::uploadFile($request->consolidatdebudget, $fileName, $path_path);
            //       $attributes['consolidatdebudget'] = $fileName;
            //   }

            // if (isset($request->benifitgrid))
            // {
            //     $fileName = "Benifit_Grid" . '.' . $request->benifitgrid->getClientOriginalExtension();
            //     Common::uploadFile($request->benifitgrid, $fileName, $path_path);
            //     $attributes['benifitgrid'] = $fileName;
            // }


            $configurationBudget = ManningandbudgetingConfigfiles::updateOrCreate(["resort_id"=> Auth::guard('resort-admin')->user()->resort_id], $attributes);
                //   $consolidatdebudget =   (isset($configurationBudget->consolidatdebudget))
                //                                 ? url($path_path.'/'.$configurationBudget->consolidatdebudget)
                //                                      :url(config('settings.default_picture'));
                // //   $benifitgrid = (isset($configurationBudget->benifitgrid))
                //                         ? url($path_path.'/'.$configurationBudget->benifitgrid)
                //                          :url(config('settings.default_picture'));

            if($hasFile)
            {
                $data = [
                    "Year"=>$request->consolidatdebudget_Year,
                    "Resort_id"=>$this->resort->resort_id,
                    "file"=>$request->file('consolidatedbudget')->getClientOriginalName()
                ];
                try {
                    $filePath = $request->file('consolidatedbudget')->store('imports');
                    Excel::import(new ConsolidateBudgetImport($data), $filePath);
                }
                catch (\Exception $e)
                {
                    $response['msg'] = $e->getMessage() ?: 'Something went wrong. Please check the Excel file format and ensure headers match the template.';
                    $response['success'] = false;
                    return response()->json($response, 422);
                }
            }

            $benifitgrid='';
            $consolidatdebudget='';
            $page_title = 'Configuration';
            $response['success'] = true;
            $response['data'] = [$consolidatdebudget,$benifitgrid];
            $response['msg'] ="Configuration saved successfully";
            return response()->json($response);
        }
        catch( \Exception $e ) {
          \Log::emergency("File: ".$e->getFile());
          \Log::emergency("Line: ".$e->getLine());
          \Log::emergency("Message: ".$e->getMessage());
          $response['success'] = false;
          $response['data'] = [];
          $response['msg'] ="Somthing Wrong";
          return response()->json($response);
        }
    }

    public function UpdateResortBudgetPositionWise(Request $request)
    {



        try
        {
            $ProposedBasicsalary= $request->ProposedBasicsalary;
            $basic_salary= $request->basic_salary;
            $monthdata= $request->month_data;
            $Total_Department_budget= $request->grand_total;
            $parent_id =0;

            foreach($basic_salary as $key=>$basic)
            {


                if(array_key_exists($key, $ProposedBasicsalary) && array_key_exists($basic['smrpChildId'], $monthdata))
                {
                    // echo  $ProposedBasicsalary[$key]['value'];
                    // echo "<pre>";
                        $StoreManningResponseChild = StoreManningResponseChild::where("id",$basic['smrpChildId'])->first();
                        $StoreManningResponseChild->Current_Basic_salary  =  $basic['value'];
                        $StoreManningResponseChild->Proposed_Basic_salary =  $ProposedBasicsalary[$key]['value'];
                        $StoreManningResponseChild->Months =  json_encode($monthdata[$basic['smrpChildId']]) ;// $monthdata[$basic['smrpChildId']];
                        $StoreManningResponseChild->save();

                        $parent_id = $StoreManningResponseChild->Parent_SMRP_id;
                    }
            }

            StoreManningResponseParent::where("id",$parent_id)->update(["Total_Department_budget"=>$Total_Department_budget]);
            return response()->json(['success' => true, 'message' => 'Budget updated successfully']);
        }
        catch   (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function approveBudget(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'budget_id' => 'required|integer',
                'department_id' => 'required|integer',
                'year' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
            }

            $budgetId = $request->input('budget_id');
            $departmentId = $request->input('department_id');
            $year = $request->input('year');

            $manningResponse = ManningResponse::where('id', $budgetId)
                                            ->where('dept_id', $departmentId)
                                            ->where('year', $year)
                                            ->first();

            if (!$manningResponse) {
                return response()->json(['success' => false, 'message' => 'Budget not found.'], 404);
            }

            // Was: budget_process_status save fired BEFORE the BudgetStatus
            // insert, and the insert crashed on five NOT NULL columns
            // (resort_id / Department_id / message_id / OtherComments /
            // created_by) that this method wasn't providing. End result was
            // a half-approved budget: manning_responses said Approved but no
            // BudgetStatus row existed. Wrap both writes in a transaction
            // and supply every required column so the approval is atomic
            // and the Revise-Budget lockout downstream actually triggers.
            DB::beginTransaction();
            try {
                $manningResponse->budget_process_status = 'Approved';
                $manningResponse->save();

                $userId = Auth::guard('resort-admin')->user()->id ?? null;
                BudgetStatus::create([
                    'resort_id'      => $manningResponse->resort_id,
                    'Department_id'  => $departmentId,
                    'message_id'     => $manningResponse->message_id ?? ('BUDGET_'.$budgetId),
                    'Budget_id'      => $budgetId,
                    'status'         => 'Approved',
                    'comments'       => 'Budget approved by GM.',
                    'OtherComments'  => 'Budget approved by GM.',
                    'created_by'     => $userId,
                    'modified_by'    => $userId,
                ]);
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }

            return response()->json(['success' => true, 'message' => 'Budget approved successfully!']);

        } catch (\Exception $e) {

            \Log::emergency("File: " . $e->getFile(). " | Line: " . $e->getLine() . " | Message: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'An error occurred while approving the budget.'], 500);
        }
    }

    /**
     * Save budget cost configuration via AJAX
     */
    public function saveBudgetCostAssignment(Request $request, $resortId)
    {
        try {
            // Validate the incoming request
            $validator = Validator::make($request->all(), [
                'department_id' => 'required|integer',
                'position_id' => 'required|integer',
                'table_type' => 'required|in:employee,vacant',
                'employee_id' => 'nullable|integer',
                'vacant_index' => 'nullable|integer',
                'basic_salary' => 'nullable|numeric|min:0',
                'current_salary' => 'nullable|numeric|min:0',
                'budget_costs' => 'required|array',
                'budget_costs.*.cost_id' => 'required|integer|exists:resort_budget_costs,id',
                'budget_costs.*.value' => 'required|numeric|min:0',
                'budget_costs.*.currency' => 'required|in:USD,MVR'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $departmentId = $request->input('department_id');
            $positionId = $request->input('position_id');
            $tableType = $request->input('table_type');
            $employeeId = $request->input('employee_id');
            $vacantIndex = $request->input('vacant_index', 1);
            $basicSalary = $request->input('basic_salary');
            $currentSalary = $request->input('current_salary');
            $budgetCosts = $request->input('budget_costs');
            $selectedYear = $request->input('year', Carbon::now()->year);

            // Get the actual department_id from manning_responses table
            $manningResponse = ManningResponse::find($departmentId);
            if (!$manningResponse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Department not found'
                ], 404);
            }

            $actualDepartmentId = $manningResponse->dept_id;

            // Get MVR to Dollar conversion rate
            $resortSettings = ResortSiteSettings::where('resort_id', $resortId)->first();
            $mvrToDollarRate = $resortSettings ? 1/$resortSettings->DollertoMVR : 15.42;

            DB::beginTransaction();

            if ($tableType === 'employee') {
                // Handle Employee Budget Cost Configuration
                if (!$employeeId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Employee ID is required for employee type'
                    ], 400);
                }

                // Delete existing configurations for this employee
                ResortEmployeeBudgetCostConfiguration::where('employee_id', $employeeId)
                    ->where('department_id', $actualDepartmentId)
                    ->where('position_id', $positionId)
                    ->where('resort_id', $resortId)
                    ->where('year', $selectedYear)
                    ->delete();

                // Check if "Overtime - Holiday" is selected
                $overtimeHolidayConfig = null;
                $overtimeHolidayCostId = null;
                foreach ($budgetCosts as $cost) {
                    $budgetCost = ResortBudgetCost::find($cost['cost_id']);
                    if ($budgetCost && $this->isOvertimeHoliday($budgetCost)) {
                        $overtimeHolidayConfig = $cost;
                        $overtimeHolidayCostId = $cost['cost_id'];
                        break;
                    }
                }

                // Insert new configurations
                foreach ($budgetCosts as $cost) {
                    $budgetCost = ResortBudgetCost::find($cost['cost_id']);
                    $isOvertimeHoliday = $budgetCost && $this->isOvertimeHoliday($budgetCost);

                    if ($isOvertimeHoliday) {
                        // For overtime holiday, create month-wise entries for all 12 months
                        for ($month = 1; $month <= 12; $month++) {
                            // Calculate holiday hours for this month
                            $holidayHours = $this->calculateHolidayHoursForMonth($selectedYear, $month);

                            // Calculate overtime holiday value based on basic salary and multiplier
                            // Formula: (Basic Salary ÷ Days in Month ÷ 8) × Multiplier × Hours
                            $daysInMonth = Carbon::create($selectedYear, $month, 1)->daysInMonth;
                            $dailySalary = $basicSalary / $daysInMonth;
                            $hourlyRate = $dailySalary / 8;
                            $multiplier = $budgetCost->amount ?? 1.5; // Default 1.5 for holiday OT
                            $overtimeHourlyRate = $hourlyRate * $multiplier;
                            $calculatedValue = $overtimeHourlyRate * $holidayHours;

                            ResortEmployeeBudgetCostConfiguration::create([
                                'employee_id' => $employeeId,
                                'resort_budget_cost_id' => $cost['cost_id'],
                                'value' => $calculatedValue,
                                'currency' => $cost['currency'],
                                'hours' => $holidayHours,
                                'department_id' => $actualDepartmentId,
                                'position_id' => $positionId,
                                'resort_id' => $resortId,
                                'year' => $selectedYear,
                                'month' => $month,
                                'basic_salary' => $basicSalary,
                                'current_salary' => $currentSalary
                            ]);
                        }
                    } else {
                        // For non-overtime-holiday items, create without month (legacy behavior)
                        ResortEmployeeBudgetCostConfiguration::create([
                            'employee_id' => $employeeId,
                            'resort_budget_cost_id' => $cost['cost_id'],
                            'value' => $cost['value'],
                            'currency' => $cost['currency'],
                            'department_id' => $actualDepartmentId,
                            'position_id' => $positionId,
                            'resort_id' => $resortId,
                            'year' => $selectedYear,
                            'basic_salary' => $basicSalary,
                            'current_salary' => $currentSalary
                        ]);
                    }
                }

                $savedConfigurations = ResortEmployeeBudgetCostConfiguration::where('employee_id', $employeeId)
                    ->where('resort_id', $resortId)
                    ->where('year', $selectedYear)
                    ->get();

            } else {
                // Handle Vacant Budget Cost Configuration

                // Get details from request
                $details = $request->input('details');

                // First, create or update the vacant budget cost record
                $vacantBudgetCost = ResortVacantBudgetCost::updateOrCreate(
                    [
                        'position_id' => $positionId,
                        'department_id' => $actualDepartmentId,
                        'resort_id' => $resortId,
                        'year' => $selectedYear,
                        'vacant_index' => $vacantIndex
                    ],
                    [
                        'basic_salary' => $basicSalary,
                        'current_salary' => $currentSalary,
                        'details' => $details
                    ]
                );

                // Delete existing configurations for this vacant position
                ResortVacantBudgetCostConfiguration::where('vacant_budget_cost_id', $vacantBudgetCost->id)
                    ->delete();

                // Check if "Overtime - Holiday" is selected
                $overtimeHolidayConfig = null;
                $overtimeHolidayCostId = null;
                foreach ($budgetCosts as $cost) {
                    $budgetCost = ResortBudgetCost::find($cost['cost_id']);
                    if ($budgetCost && $this->isOvertimeHoliday($budgetCost)) {
                        $overtimeHolidayConfig = $cost;
                        $overtimeHolidayCostId = $cost['cost_id'];
                        break;
                    }
                }

                // Insert new configurations
                foreach ($budgetCosts as $cost) {
                    $budgetCost = ResortBudgetCost::find($cost['cost_id']);
                    $isOvertimeHoliday = $budgetCost && $this->isOvertimeHoliday($budgetCost);

                    if ($isOvertimeHoliday) {
                        // For overtime holiday, create month-wise entries for all 12 months
                        for ($month = 1; $month <= 12; $month++) {
                            // Calculate holiday hours for this month
                            $holidayHours = $this->calculateHolidayHoursForMonth($selectedYear, $month);

                            // Calculate overtime holiday value based on basic salary and multiplier
                            // Formula: (Basic Salary ÷ Days in Month ÷ 8) × Multiplier × Hours
                            $daysInMonth = Carbon::create($selectedYear, $month, 1)->daysInMonth;
                            $dailySalary = $basicSalary / $daysInMonth;
                            $hourlyRate = $dailySalary / 8;
                            $multiplier = $budgetCost->amount ?? 1.5; // Default 1.5 for holiday OT
                            $overtimeHourlyRate = $hourlyRate * $multiplier;
                            $calculatedValue = $overtimeHourlyRate * $holidayHours;

                            ResortVacantBudgetCostConfiguration::create([
                                'vacant_budget_cost_id' => $vacantBudgetCost->id,
                                'resort_budget_cost_id' => $cost['cost_id'],
                                'value' => $calculatedValue,
                                'currency' => $cost['currency'],
                                'hours' => $holidayHours,
                                'department_id' => $actualDepartmentId,
                                'position_id' => $positionId,
                                'resort_id' => $resortId,
                                'year' => $selectedYear,
                                'month' => $month
                            ]);
                        }
                    } else {
                        // For non-overtime-holiday items, create without month (legacy behavior)
                        ResortVacantBudgetCostConfiguration::create([
                            'vacant_budget_cost_id' => $vacantBudgetCost->id,
                            'resort_budget_cost_id' => $cost['cost_id'],
                            'value' => $cost['value'],
                            'currency' => $cost['currency'],
                            'department_id' => $actualDepartmentId,
                            'position_id' => $positionId,
                            'resort_id' => $resortId,
                            'year' => $selectedYear
                        ]);
                    }
                }

                $savedConfigurations = ResortVacantBudgetCostConfiguration::where('vacant_budget_cost_id', $vacantBudgetCost->id)
                    ->get();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Budget cost configuration saved successfully!',
                'data' => [
                    'table_type' => $tableType,
                    'employee_id' => $employeeId,
                    'vacant_index' => $vacantIndex,
                    'position_id' => $positionId,
                    'basic_salary' => $basicSalary,
                    'current_salary' => $currentSalary,
                    'costs' => $savedConfigurations,
                    'mvr_to_dollar_rate' => $mvrToDollarRate
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile() . " | Line: " . $e->getLine() . " | Message: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while saving the budget cost configuration.'
            ], 500);
        }
    }

    /**
     * Get existing budget cost configuration
     */
    public function getConfiguration(Request $request, $resortId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'department_id' => 'required|integer',
                'position_id' => 'required|integer',
                'table_type' => 'required|in:employee,vacant',
                'employee_id' => 'nullable|integer',
                'vacant_index' => 'nullable|integer'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $departmentId = $request->input('department_id');
            $positionId = $request->input('position_id');
            $tableType = $request->input('table_type');
            $employeeId = $request->input('employee_id');
            $vacantIndex = $request->input('vacant_index', 1);
            $selectedYear = $request->input('year', Carbon::now()->year);

            // Get the actual department_id from manning_responses table
            $manningResponse = ManningResponse::find($departmentId);
            if (!$manningResponse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Department not found'
                ], 404);
            }

            $actualDepartmentId = $manningResponse->dept_id;

            $configuration = null;

            if ($tableType === 'employee' && $employeeId) {
                // Get employee configuration
                $configs = ResortEmployeeBudgetCostConfiguration::where('employee_id', $employeeId)
                    ->where('department_id', $actualDepartmentId)
                    ->where('position_id', $positionId)
                    ->where('resort_id', $resortId)
                    ->where('year', $selectedYear)
                    ->get();

                if ($configs->isNotEmpty()) {
                    $configuration = [
                        'basic_salary' => $configs->first()->basic_salary,
                        'current_salary' => $configs->first()->current_salary,
                        'costs' => $configs->map(function($config) {
                            return [
                                'resort_budget_cost_id' => $config->resort_budget_cost_id,
                                'value' => $config->value,
                                'currency' => $config->currency
                            ];
                        })
                    ];
                }
            } else {
                // Get vacant configuration
                $vacantBudgetCost = ResortVacantBudgetCost::where('position_id', $positionId)
                    ->where('department_id', $actualDepartmentId)
                    ->where('resort_id', $resortId)
                    ->where('year', $selectedYear)
                    ->where('vacant_index', $vacantIndex)
                    ->first();

                if ($vacantBudgetCost) {
                    $configs = ResortVacantBudgetCostConfiguration::where('vacant_budget_cost_id', $vacantBudgetCost->id)
                        ->get();

                    $configuration = [
                        'basic_salary' => $vacantBudgetCost->basic_salary,
                        'current_salary' => $vacantBudgetCost->current_salary,
                        'costs' => $configs->map(function($config) {
                            return [
                                'resort_budget_cost_id' => $config->resort_budget_cost_id,
                                'value' => $config->value,
                                'currency' => $config->currency
                            ];
                        })
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'configuration' => $configuration
            ]);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile() . " | Line: " . $e->getLine() . " | Message: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching the configuration.'
            ], 500);
        }
    }

    /**
     * Get positions and sections for a department
     */
    public function getDepartmentHierarchy(Request $request)
    {
        try {
            $departmentId = $request->input('department_id');
            $year = $request->input('year', date('Y'));
            $resortId = auth()->guard('resort-admin')->user()->resort_id;

            // Get manning response for this department — OPTIONAL. The
            // previous build returned `success: false` when the dept had
            // no manning row yet, which silently zero'd out the dept's
            // sub-tree (sections + positions + employee badges) in the
            // view-budget JS. The "No budget found for this department"
            // banner came from here. The dept catalog itself — sections,
            // positions, employees — exists independently of manning, so
            // we return the structure regardless. Manning is only needed
            // for the workflow-state metadata which we leave null.
            $manningResponse = ManningResponse::where('dept_id', $departmentId)
                ->where('year', $year)
                ->where('resort_id', $resortId)
                ->first();

            // Get sections
            $sections = ResortSection::where('dept_id', $departmentId)
                ->where('resort_id', $resortId)
                ->where('status', 'active')
                ->get();

            // Get positions without section
            $positionsWithoutSection = ResortPosition::where('dept_id', $departmentId)
                ->where('resort_id', $resortId)
                ->whereNull('section_id')
                ->where('status', 'active')
                ->get();

            // Get positions grouped by section
            $positionsBySection = [];
            foreach ($sections as $section) {
                $positions = ResortPosition::where('dept_id', $departmentId)
                    ->where('section_id', $section->id)
                    ->where('resort_id', $resortId)
                    ->where('status', 'active')
                    ->get();

                $positionsBySection[$section->id] = $positions;
            }

            return response()->json([
                'success' => true,
                'sections' => $sections,
                'positions_without_section' => $positionsWithoutSection,
                'positions_by_section' => $positionsBySection,
                'manning_response' => $manningResponse
            ]);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile() . " | Line: " . $e->getLine() . " | Message: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching department hierarchy.'
            ], 500);
        }
    }

    /**
     * Get employees and vacancies for a position
     */
    public function getPositionEmployees(Request $request)
    {
        try {
            $positionId = $request->input('position_id');
            $year = $request->input('year', date('Y'));
            $resortId = auth()->guard('resort-admin')->user()->resort_id;

            $position = ResortPosition::find($positionId);
            if (!$position) {
                return response()->json(['success' => false, 'message' => 'Position not found']);
            }

            // Get manning response — OPTIONAL. The previous build 403'd the
            // request when no manning row existed, which silently zero'd
            // out the view-budget badges for every dept that hadn't been
            // put through manning yet (Executive Office, L&D, Security
            // and POM on live — all rendering as $0.00). The canonical
            // helpers and employee tables don't depend on a manning row,
            // so we proceed even when it's missing and source the
            // metadata (vacant count, monthly data) from the persisted
            // budget tables instead.
            $manningResponse = ManningResponse::where('dept_id', $position->dept_id)
                ->where('year', $year)
                ->where('resort_id', $resortId)
                ->first();

            // Get employees
            $employees = DB::table('employees as e')
                ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                ->where('e.Position_id', $positionId)
                ->where('e.Dept_id', $position->dept_id)
                ->where('e.status', 'Active')
                ->get([
                    'e.resort_id',
                    'e.id as Empid',
                    'ra.first_name',
                    'ra.last_name',
                    'e.Position_id',
                    'e.Admin_Parent_id',
                    'e.rank',
                    'e.Dept_id',
                    'e.nationality',
                    'e.basic_salary',
                    'e.incremented_date'
                ]);

            // Get position monthly data — empty collection when there's no
            // manning response (downstream loops handle empty gracefully).
            $monthlyData = $manningResponse
                ? PositionMonthlyData::where('position_id', $positionId)
                    ->where('manning_response_id', $manningResponse->id)
                    ->get()
                : collect();

            // Get vacant position counts
            $vacantCounts = [];
            for ($i = 1; $i <= 12; $i++) {
                $monthData = $monthlyData->where('month', $i)->first();
                $vacantCounts[$i] = $monthData ? $monthData->vacantcount : 0;
            }

            // Process employee budget data — only meaningful when there IS
            // a manning response. For depts without one, the
            // StoreManningResponseParent / Child rows don't exist, and the
            // canonical helpers (called downstream in getEmployeeMonthlyData)
            // fall back to the employee's basic_salary anyway.
            if ($manningResponse) {
                foreach ($employees as $employee) {
                    $smrp = StoreManningResponseParent::where('Resort_id', $resortId)
                        ->where('Department_id', $position->dept_id)
                        ->where('Budget_id', $manningResponse->id)
                        ->first();

                    if ($smrp) {
                        $budgetChild = StoreManningResponseChild::where('Parent_SMRP_id', $smrp->id)
                            ->where('Emp_id', $employee->Empid)
                            ->first();

                        if ($budgetChild) {
                            $employee->smrp_child_id = $budgetChild->id;
                            $employee->proposed_basic_salary = $budgetChild->Proposed_Basic_salary ?? 0;
                            $employee->months_data = json_decode($budgetChild->Months, true) ?? [];
                        }
                    }
                }
            }

            // Real-time vacant = max(0, budgeted headcount − Active filled).
            // pmd.vacantcount is a stored field that lags behind reality (not
            // refreshed when employees are assigned/removed), which is why
            // Accounting Clerk was rendering Vacant 1 + Vacant 2 even after
            // a seat was filled. Same formula as Workforce + view-manning +
            // ViewBudget() page-load logic.
            $maxHeadcount = 0;
            foreach ($monthlyData as $monthlyDataItem) {
                $maxHeadcount = max($maxHeadcount, (int) ($monthlyDataItem->headcount ?? 0));
            }

            $today = \Carbon\Carbon::today()->toDateString();
            $activeFilled = \App\Models\Employee::where('resort_id', $resortId)
                ->where('Position_id', $positionId)
                ->where('Dept_id', $position->dept_id)
                ->where('status', 'Active')
                ->where(function ($q) use ($today) {
                    $q->whereNull('last_working_day')
                      ->orWhereDate('last_working_day', '>', $today);
                })
                ->count();

            $totalVacantPositions = max(0, $maxHeadcount - $activeFilled);

            // Fallback for depts without a manning response: count
            // persisted vacant rows in resort_vacant_budget_costs for
            // this (position, year). PMD-driven max(0, headcount-filled)
            // returns 0 when PMD is empty, so without this the view-budget
            // JS would render no Vacant rows for Executive Office / L&D /
            // Security even when HR has actual vacant slot data.
            if (!$manningResponse || $totalVacantPositions === 0) {
                $persistedVacantCount = ResortVacantBudgetCost::where('position_id', $positionId)
                    ->where('department_id', $position->dept_id)
                    ->where('resort_id', $resortId)
                    ->where('year', $year)
                    ->count();
                $totalVacantPositions = max($totalVacantPositions, $persistedVacantCount);
            }

            return response()->json([
                'success' => true,
                'employees' => $employees,
                'vacant_counts' => $vacantCounts,
                'total_vacant_positions' => $totalVacantPositions,
                'manning_response_id' => $manningResponse ? $manningResponse->id : null,
                'position' => $position
            ]);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile() . " | Line: " . $e->getLine() . " | Message: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching position employees.'
            ], 500);
        }
    }

    /**
     * Get employee monthly budget breakdown
     */
    public function getEmployeeMonthlyData(Request $request)
    {
        try {
            $employeeId = $request->input('employee_id');
            $positionId = $request->input('position_id');
            $year = $request->input('year', date('Y'));
            $resortId = auth()->guard('resort-admin')->user()->resort_id;

            // Get employee details
            $employee = DB::table('employees as e')
                ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                ->where('e.id', $employeeId)
                ->first([
                    'e.*',
                    'ra.first_name',
                    'ra.last_name'
                ]);

            if (!$employee) {
                return response()->json(['success' => false, 'message' => 'Employee not found']);
            }

            $position = ResortPosition::find($positionId);
            if (!$position) {
                return response()->json(['success' => false, 'message' => 'Position not found']);
            }

            // Get all resort budget costs
            $resortCosts = ResortBudgetCost::where('resort_id', $resortId)
                ->where('status', 'active')
                ->orderBy('id')
                ->get();

            // Get employee budget cost configurations for all months
            $employeeBudgetConfigs = ResortEmployeeBudgetCostConfiguration::where('employee_id', $employeeId)
                ->where('resort_id', $resortId)
                ->where('year', $year)
                ->get();

            // Shared salary defaults from the employees table — used as the
            // fallback when a particular month has no override.
            $currentBasicSalary = $employee->basic_salary ?? 0;
            $proposedBasicSalary = $employee->proposed_salary ?? 0;

            // Per-month salary overrides. Build a [month => {current, proposed}]
            // lookup so the table can render different salaries per month.
            $monthlySalaryOverrides = DB::table('resort_employee_monthly_salaries')
                ->where('employee_id', $employeeId)
                ->where('resort_id', $resortId)
                ->where('year', $year)
                ->get(['month', 'current_salary', 'proposed_salary'])
                ->keyBy('month');

            $monthlySalaries = [];
            for ($m = 1; $m <= 12; $m++) {
                $override = $monthlySalaryOverrides->get($m);
                $monthlySalaries[$m] = [
                    'current_salary'  => $override && $override->current_salary  !== null ? (float) $override->current_salary  : (float) $currentBasicSalary,
                    'proposed_salary' => $override && $override->proposed_salary !== null ? (float) $override->proposed_salary : (float) $proposedBasicSalary,
                ];
            }

            // Get DollertoMVR conversion rate for converting USD to MVR
            $resortSettings = ResortSiteSettings::where('resort_id', $resortId)->first();
            $dollarToMvrRate = $resortSettings ? ($resortSettings->DollertoMVR ?? 15.42) : 15.42;

            // Create month-wise and cost-wise lookup array [month][cost_id] = config
            // If no data exists, this will be an empty array and will show 0 values in the table
            $monthCostLookup = [];
            if ($employeeBudgetConfigs->isNotEmpty()) {
                foreach ($employeeBudgetConfigs as $config) {
                    if (!isset($monthCostLookup[$config->month])) {
                        $monthCostLookup[$config->month] = [];
                    }

                    // Data from config tables is stored in USD
                    // If currency is MVR, convert USD value to MVR
                    $value = $config->value ?? 0;
                    $currency = $config->currency ?? 'USD';

                    // If currency is MVR, convert USD to MVR
                    if ($currency === 'MVR' && $value > 0) {
                        $value = $value * $dollarToMvrRate;
                    }

                    $monthCostLookup[$config->month][$config->resort_budget_cost_id] = [
                        'value' => $value,
                        'currency' => $currency,
                        'hours' => $config->hours ?? 0
                    ];
                }
            }

            // --- Live fallback from the Budget → Cost definitions -------------
            // When the employee has no saved per-month override for a cost, the
            // table used to show 0. Instead, compute the value straight from the
            // ResortBudgetCost definition (amount + frequency + %/fixed +
            // Locals/Xpat/Muslim applicability) so the table reflects the
            // configured costs. HR's explicit overrides (already in
            // $monthCostLookup) always win — we only fill the gaps.
            $isLocal  = strtolower(trim((string) ($employee->nationality ?? ''))) === 'maldivian';
            $isMuslim = strtolower(trim((string) ($employee->religion ?? '')))   === 'muslim';
            $basicForPercent = (float) ($currentBasicSalary ?: 0);

            for ($m = 1; $m <= 12; $m++) {
                foreach ($resortCosts as $cost) {
                    if (isset($monthCostLookup[$m][$cost->id])) {
                        continue; // explicit override — leave it untouched
                    }
                    $monthCostLookup[$m][$cost->id] = [
                        'value'    => $this->computeBudgetCostMonthlyValue(
                                          $cost, $m, (int) $year, $isLocal, $isMuslim, $basicForPercent),
                        'currency' => $cost->amount_unit,
                        'hours'    => 0,
                        'computed' => true, // value derived from the cost config, not a saved override
                    ];
                }
            }

            // Per-employee allowances (employees_allowance) — same source
            // the consolidated aggregator and the payroll module read. The
            // value returned here is USD/month for ALL the employee's
            // assigned allowances combined, so the JS can simply add it
            // to each month's running total.
            $employeeAllowanceMonthlyUsd = (float) DB::table('employees_allowance')
                ->where('employee_id', $employeeId)
                ->selectRaw(
                    "COALESCE(SUM(CASE WHEN amount_unit = 'MVR' "
                  . "THEN amount * (1.0 / {$dollarToMvrRate}) ELSE amount END), 0) as total"
                )
                ->value('total');

            // CANONICAL annual total — same number consolidated-budget reads
            // for this employee. JS just uses this; doesn't re-sum month_cost_data.
            // Guarantees view-budget and consolidated produce identical
            // per-position totals because they're now calling the same helper.
            $annualBudgetUsd = Common::annualBudgetForEmployee($resortId, (int) $year, $employee);

            return response()->json([
                'success' => true,
                'employee' => $employee,
                'resort_costs' => $resortCosts,
                'month_cost_data' => $monthCostLookup,
                'employee_id' => $employeeId,
                'position_id' => $positionId,
                'department_id' => $position->dept_id,
                'year' => $year,
                'current_basic_salary' => $currentBasicSalary,
                'proposed_basic_salary' => $proposedBasicSalary,
                'monthly_salaries' => $monthlySalaries,
                // Monthly per-employee allowance total in USD — added to
                // every month by the JS aggregator so the page matches
                // the consolidated and payroll figures.
                'employee_allowance_monthly_usd' => $employeeAllowanceMonthlyUsd,
                // Canonical annual total — JS uses this directly.
                'annual_total_usd' => $annualBudgetUsd,
            ]);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile() . " | Line: " . $e->getLine() . " | Message: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching employee monthly data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Compute one ResortBudgetCost's value for a single month column.
     *
     * Used as the live fallback in getEmployeeMonthlyData() so the budget
     * table reflects the configured Budget → Cost definitions instead of 0
     * when an employee has no saved per-month override.
     *
     * Rules:
     *  - Applicability: a 'Locals Only' / 'Xpat Only' / 'Muslim Only' cost
     *    contributes 0 for an employee outside that group.
     *  - Amount unit '%'  → percentage of the employee's basic salary.
     *  - Frequency → per-month figure:
     *      Month        → full amount every month
     *      Year         → amount / 12
     *      Quarter      → amount / 3
     *      Daily        → amount × days in that calendar month
     *      One time …   → full amount in January only
     *  - The value is returned in the cost's own amount_unit (no FX
     *    conversion) — same number shown on the Budget → Cost screen.
     */
    private function computeBudgetCostMonthlyValue($cost, int $month, int $year, bool $isLocal, bool $isMuslim, float $basicSalary): float
    {
        $details = trim((string) ($cost->details ?? 'Both'));
        if ($details === 'Locals Only'  && !$isLocal)  return 0.0;
        if ($details === 'Xpat Only'    &&  $isLocal)  return 0.0;
        if ($details === 'Muslim Only'  && !$isMuslim) return 0.0;

        $amount = (float) ($cost->amount ?? 0);
        $unit   = strtoupper(trim((string) ($cost->amount_unit ?? 'USD')));
        $freq   = strtolower(trim((string) ($cost->frequency ?? 'Month')));

        // Percentage costs (e.g. Pension 7%) are a % of basic salary.
        $base = ($unit === '%') ? ($basicSalary * $amount / 100) : $amount;

        if (str_contains($freq, 'year')) {
            return round($base / 12, 2);
        }
        if (str_contains($freq, 'quarter')) {
            return round($base / 3, 2);
        }
        if (str_contains($freq, 'dai')) {
            $daysInMonth = (int) date('t', mktime(0, 0, 0, $month, 1, $year));
            return round($base * $daysInMonth, 2);
        }
        if (str_contains($freq, 'one time')) {
            return $month === 1 ? round($base, 2) : 0.0;
        }
        // Default: a monthly cost.
        return round($base, 2);
    }

    /**
     * Get vacant position monthly budget breakdown
     */
    public function getVacantMonthlyData(Request $request)
    {
        try {
            $vacantIndex = $request->input('vacant_index');
            $positionId = $request->input('position_id');
            $year = $request->input('year', date('Y'));
            $resortId = auth()->guard('resort-admin')->user()->resort_id;

            $position = ResortPosition::find($positionId);
            if (!$position) {
                return response()->json(['success' => false, 'message' => 'Position not found']);
            }

            // Get all resort budget costs
            $resortCosts = ResortBudgetCost::where('resort_id', $resortId)
                ->where('status', 'active')
                ->orderBy('id')
                ->get();

            // Get or create vacant budget cost record
            $vacantBudgetCost = ResortVacantBudgetCost::firstOrCreate(
                [
                    'position_id' => $positionId,
                    'department_id' => $position->dept_id,
                    'resort_id' => $resortId,
                    'year' => $year,
                    'vacant_index' => $vacantIndex
                ],
                [
                    'basic_salary' => 0,
                    'current_salary' => 0
                ]
            );

            // Get vacant budget cost configurations for all months
            $vacantBudgetConfigs = ResortVacantBudgetCostConfiguration::where('vacant_budget_cost_id', $vacantBudgetCost->id)
                ->where('resort_id', $resortId)
                ->where('year', $year)
                ->get();

            // Shared salary fallback from resort_vacant_budget_costs.
            $currentBasicSalary = $vacantBudgetCost->basic_salary ?? 0;
            $proposedBasicSalary = $vacantBudgetCost->current_salary ?? 0;

            // Per-month overrides from resort_vacant_monthly_salaries.
            $monthlySalaryOverrides = DB::table('resort_vacant_monthly_salaries')
                ->where('resort_id', $resortId)
                ->where('position_id', $positionId)
                ->where('department_id', $position->dept_id)
                ->where('year', $year)
                ->where('vacant_index', $vacantIndex)
                ->get(['month', 'current_salary', 'proposed_salary'])
                ->keyBy('month');

            $monthlySalaries = [];
            for ($m = 1; $m <= 12; $m++) {
                $override = $monthlySalaryOverrides->get($m);
                $monthlySalaries[$m] = [
                    'current_salary'  => $override && $override->current_salary  !== null ? (float) $override->current_salary  : (float) $currentBasicSalary,
                    'proposed_salary' => $override && $override->proposed_salary !== null ? (float) $override->proposed_salary : (float) $proposedBasicSalary,
                ];
            }

            // Get DollertoMVR conversion rate for converting USD to MVR
            $resortSettings = ResortSiteSettings::where('resort_id', $resortId)->first();
            $dollarToMvrRate = $resortSettings ? ($resortSettings->DollertoMVR ?? 15.42) : 15.42;

            // Create month-wise and cost-wise lookup array [month][cost_id] = config
            // If no data exists, this will be an empty array and will show 0 values in the table
            $monthCostLookup = [];
            if ($vacantBudgetConfigs->isNotEmpty()) {
                foreach ($vacantBudgetConfigs as $config) {
                    if (!isset($monthCostLookup[$config->month])) {
                        $monthCostLookup[$config->month] = [];
                    }

                    // Data from config tables is stored in USD
                    // If currency is MVR, convert USD value to MVR
                    $value = $config->value ?? 0;
                    $currency = $config->currency ?? 'USD';

                    // If currency is MVR, convert USD to MVR
                    if ($currency === 'MVR' && $value > 0) {
                        $value = $value * $dollarToMvrRate;
                    }

                    $monthCostLookup[$config->month][$config->resort_budget_cost_id] = [
                        'value' => $value,
                        'currency' => $currency,
                        'hours' => $config->hours ?? 0
                    ];
                }
            }

            // CANONICAL annual total for this vacant slot — same number
            // consolidated-budget computes, so view-budget JS just uses it.
            $vacantAnnualUsd = Common::annualBudgetForVacantSlot(
                $resortId, (int) $year, $vacantBudgetCost
            );

            return response()->json([
                'success' => true,
                'vacant_index' => $vacantIndex,
                'vacant_budget_cost_id' => $vacantBudgetCost->id,
                'resort_costs' => $resortCosts,
                'month_cost_data' => $monthCostLookup,
                'position_id' => $positionId,
                'department_id' => $position->dept_id,
                'year' => $year,
                'details' => $vacantBudgetCost->details ?? null,
                'current_basic_salary' => $currentBasicSalary,
                'proposed_basic_salary' => $proposedBasicSalary,
                'monthly_salaries' => $monthlySalaries,
                // Canonical annual total — JS uses this directly.
                'annual_total_usd' => $vacantAnnualUsd,
            ]);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile() . " | Line: " . $e->getLine() . " | Message: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching vacant position monthly data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update employee monthly budget configuration
     */
    public function updateEmployeeMonthlyBudget(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'employee_id' => 'required|integer',
                'position_id' => 'required|integer',
                'department_id' => 'required|integer',
                'year' => 'required|integer',
                'monthly_data' => 'required|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $employeeId = $request->employee_id;
            $positionId = $request->position_id;
            $departmentId = $request->department_id;
            $year = $request->year;
            $resortId = auth()->guard('resort-admin')->user()->resort_id;

            // Per-month salary override — write to resort_employee_monthly_salaries
            // for the specific month being saved. The employees.basic_salary /
            // proposed_salary record is left alone; the View Budget read path
            // falls back to it only when no per-month override exists.
            // (Previous behaviour overwrote the shared employee record and
            // propagated the edit to every other month on refresh.)
            if (!empty($request->monthly_data)) {
                foreach ($request->monthly_data as $monthData) {
                    if (!isset($monthData['month'])) continue;
                    $monthNum = (int) $monthData['month'];
                    $currentSalary  = $monthData['current_salary']  ?? null;
                    $proposedSalary = $monthData['proposed_salary'] ?? null;

                    if ($currentSalary === null && $proposedSalary === null) continue;

                    $updatePayload = [];
                    if ($currentSalary  !== null) $updatePayload['current_salary']  = $currentSalary;
                    if ($proposedSalary !== null) $updatePayload['proposed_salary'] = $proposedSalary;
                    if (empty($updatePayload)) continue;

                    DB::table('resort_employee_monthly_salaries')->updateOrInsert(
                        [
                            'employee_id' => $employeeId,
                            'resort_id'   => $resortId,
                            'year'        => $year,
                            'month'       => $monthNum,
                        ],
                        array_merge($updatePayload, ['updated_at' => now()])
                    );
                }
            }

            // Insert new month-wise cost configurations (without storing salary data).
            // cost_configurations may be omitted (e.g. salary-only update) —
            // skip cost handling in that case to avoid wiping existing rows.
            foreach ($request->monthly_data as $monthData) {
                $month = $monthData['month'];
                if (!array_key_exists('cost_configurations', $monthData)) {
                    continue;
                }
                $costConfigurations = $monthData['cost_configurations'] ?? [];

                // Delete existing configurations for this employee, year, and specific month
                ResortEmployeeBudgetCostConfiguration::where('employee_id', $employeeId)
                    ->where('resort_id', $resortId)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->delete();

                // Check if "Overtime - Normal" is selected
                $overtimeNormalConfig = null;
                $overtimeHolidayConfig = null;
                $overtimeHolidayCostId = null;
                foreach ($costConfigurations as $costConfig) {
                    $budgetCost = ResortBudgetCost::find($costConfig['resort_budget_cost_id']);
                    if ($budgetCost && $this->isOvertimeNormal($budgetCost)) {
                        $overtimeNormalConfig = $costConfig;
                    }
                    if ($budgetCost && $this->isOvertimeHoliday($budgetCost)) {
                        $overtimeHolidayConfig = $costConfig;
                        $overtimeHolidayCostId = $costConfig['resort_budget_cost_id'];
                    }
                }

                // Insert configurations for this month (without salary fields)
                foreach ($costConfigurations as $costConfig) {
                    $budgetCost = ResortBudgetCost::find($costConfig['resort_budget_cost_id']);
                    $isOvertimeHoliday = $budgetCost && $this->isOvertimeHoliday($budgetCost);

                    // Skip overtime holiday here - we'll handle it separately for all 12 months
                    if (!$isOvertimeHoliday) {
                        ResortEmployeeBudgetCostConfiguration::create([
                            'employee_id' => $employeeId,
                            'resort_budget_cost_id' => $costConfig['resort_budget_cost_id'],
                            'value' => $costConfig['value'],
                            'currency' => $costConfig['currency'] ?? 'USD',
                            'hours' => $costConfig['hours'] ?? 0,
                            'department_id' => $departmentId,
                            'position_id' => $positionId,
                            'resort_id' => $resortId,
                            'year' => $year,
                            'month' => $month
                        ]);
                    }
                }

                // If "Overtime - Holiday" is selected, automatically calculate and add it for all 12 months
                if ($overtimeHolidayConfig && $overtimeHolidayCostId) {
                    // Get employee basic salary for calculation
                    $employee = DB::table('employees')->where('id', $employeeId)->first();
                    $employeeBasicSalary = $employee->basic_salary ?? 0;
                    $budgetCost = ResortBudgetCost::find($overtimeHolidayCostId);
                    $multiplier = $budgetCost->amount ?? 1.5; // Default 1.5 for holiday OT

                    // Delete existing overtime holiday configurations for all months
                    ResortEmployeeBudgetCostConfiguration::where('employee_id', $employeeId)
                        ->where('resort_id', $resortId)
                        ->where('year', $year)
                        ->where('resort_budget_cost_id', $overtimeHolidayCostId)
                        ->delete();

                    // Create overtime holiday entries for all 12 months
                    for ($targetMonth = 1; $targetMonth <= 12; $targetMonth++) {
                        // Calculate holiday hours for this month
                        $holidayHours = $this->calculateHolidayHoursForMonth($year, $targetMonth);

                        // Calculate overtime holiday value
                        $daysInMonth = Carbon::create($year, $targetMonth, 1)->daysInMonth;
                        $dailySalary = $employeeBasicSalary / $daysInMonth;
                        $hourlyRate = $dailySalary / 8;
                        $overtimeHourlyRate = $hourlyRate * $multiplier;
                        $calculatedValue = $overtimeHourlyRate * $holidayHours;

                        ResortEmployeeBudgetCostConfiguration::create([
                            'employee_id' => $employeeId,
                            'resort_budget_cost_id' => $overtimeHolidayCostId,
                            'value' => $calculatedValue,
                            'currency' => $overtimeHolidayConfig['currency'] ?? 'USD',
                            'hours' => $holidayHours,
                            'department_id' => $departmentId,
                            'position_id' => $positionId,
                            'resort_id' => $resortId,
                            'year' => $year,
                            'month' => $targetMonth
                        ]);
                    }
                }

                // If "Overtime - Normal" is selected, automatically add it for the next 6 months
                // (Current month is already added above, so we add the next 5 months to make 6 total)
                if ($overtimeNormalConfig) {
                    $startMonth = $month;
                    $endMonth = min(12, $startMonth + 5); // Add for next 5 months (total 6 months including current)

                    for ($targetMonth = $startMonth + 1; $targetMonth <= $endMonth; $targetMonth++) {
                        // Check if overtime normal configuration already exists for this month
                        $existingOvertimeNormal = ResortEmployeeBudgetCostConfiguration::where('employee_id', $employeeId)
                            ->where('resort_id', $resortId)
                            ->where('year', $year)
                            ->where('month', $targetMonth)
                            ->where('resort_budget_cost_id', $overtimeNormalConfig['resort_budget_cost_id'])
                            ->first();

                        // Only create if it doesn't already exist (to avoid overwriting manual entries)
                        if (!$existingOvertimeNormal) {
                            ResortEmployeeBudgetCostConfiguration::create([
                                'employee_id' => $employeeId,
                                'resort_budget_cost_id' => $overtimeNormalConfig['resort_budget_cost_id'],
                                'value' => $overtimeNormalConfig['value'],
                                'currency' => $overtimeNormalConfig['currency'] ?? 'USD',
                                'hours' => $overtimeNormalConfig['hours'] ?? 0,
                                'department_id' => $departmentId,
                                'position_id' => $positionId,
                                'resort_id' => $resortId,
                                'year' => $year,
                                'month' => $targetMonth
                            ]);
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Employee monthly budget updated successfully'
            ]);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile() . " | Line: " . $e->getLine() . " | Message: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating employee budget: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update vacant position monthly budget configuration
     */
    public function updateVacantMonthlyBudget(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'vacant_budget_cost_id' => 'required|integer',
                'position_id' => 'required|integer',
                'department_id' => 'required|integer',
                'year' => 'required|integer',
                'monthly_data' => 'required|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $vacantBudgetCostId = $request->vacant_budget_cost_id;
            $positionId = $request->position_id;
            $departmentId = $request->department_id;
            $year = $request->year;
            $resortId = auth()->guard('resort-admin')->user()->resort_id;
            $details = $request->input('details');

            // Update vacant budget cost record with details if provided
            if ($details) {
                $vacantBudgetCost = ResortVacantBudgetCost::find($vacantBudgetCostId);
                if ($vacantBudgetCost) {
                    $vacantBudgetCost->details = $details;
                    $vacantBudgetCost->save();
                }
            }

            // Per-month vacant salary override — write to
            // resort_vacant_monthly_salaries for the specific month being saved.
            // resort_vacant_budget_costs.basic_salary / .current_salary is the
            // shared fallback used when no per-month override exists.
            $vacantBudgetCostRow = ResortVacantBudgetCost::find($vacantBudgetCostId);
            $vacantIndex = $vacantBudgetCostRow ? (int) $vacantBudgetCostRow->vacant_index : (int) $request->input('vacant_index', 0);

            if (!empty($request->monthly_data) && $vacantIndex > 0) {
                foreach ($request->monthly_data as $monthData) {
                    if (!isset($monthData['month'])) continue;
                    $monthNum = (int) $monthData['month'];
                    // saveVacantMonthBudget() now sends salaries straight (no
                    // historical column swap), so request[current_salary] is
                    // the Current Basic Salary and [proposed_salary] is the
                    // Proposed Basic Salary.
                    $currentSalary  = $monthData['current_salary']  ?? null;
                    $proposedSalary = $monthData['proposed_salary'] ?? null;
                    if ($currentSalary === null && $proposedSalary === null) continue;

                    $updatePayload = [];
                    if ($currentSalary  !== null) $updatePayload['current_salary']  = $currentSalary;
                    if ($proposedSalary !== null) $updatePayload['proposed_salary'] = $proposedSalary;
                    if (empty($updatePayload)) continue;

                    DB::table('resort_vacant_monthly_salaries')->updateOrInsert(
                        [
                            'resort_id'     => $resortId,
                            'position_id'   => $positionId,
                            'department_id' => $departmentId,
                            'year'          => $year,
                            'vacant_index'  => $vacantIndex,
                            'month'         => $monthNum,
                        ],
                        array_merge($updatePayload, ['updated_at' => now()])
                    );
                }
            }

            // (Legacy shared-record write removed — it propagated the first
            // month's salary to all 12 months on refresh. Per-month overrides
            // above are now the source of truth.)

            // Insert new month-wise cost configurations (without storing salary data).
            // cost_configurations may be omitted (e.g. when the request only
            // updates per-month salary) — skip cost handling in that case so we
            // don't wipe existing cost rows.
            foreach ($request->monthly_data as $monthData) {
                $month = $monthData['month'];
                if (!array_key_exists('cost_configurations', $monthData)) {
                    continue;
                }
                $costConfigurations = $monthData['cost_configurations'] ?? [];

                // Delete existing configurations for this vacant position, year, and specific month
                ResortVacantBudgetCostConfiguration::where('vacant_budget_cost_id', $vacantBudgetCostId)
                    ->where('resort_id', $resortId)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->delete();

                // Check if "Overtime - Normal" is selected
                $overtimeNormalConfig = null;
                $overtimeHolidayConfig = null;
                $overtimeHolidayCostId = null;
                foreach ($costConfigurations as $costConfig) {
                    $budgetCost = ResortBudgetCost::find($costConfig['resort_budget_cost_id']);
                    if ($budgetCost && $this->isOvertimeNormal($budgetCost)) {
                        $overtimeNormalConfig = $costConfig;
                    }
                    if ($budgetCost && $this->isOvertimeHoliday($budgetCost)) {
                        $overtimeHolidayConfig = $costConfig;
                        $overtimeHolidayCostId = $costConfig['resort_budget_cost_id'];
                    }
                }

                // Insert configurations for this month (without salary fields)
                foreach ($costConfigurations as $costConfig) {
                    $budgetCost = ResortBudgetCost::find($costConfig['resort_budget_cost_id']);
                    $isOvertimeHoliday = $budgetCost && $this->isOvertimeHoliday($budgetCost);

                    // Skip overtime holiday here - we'll handle it separately for all 12 months
                    if (!$isOvertimeHoliday) {
                        ResortVacantBudgetCostConfiguration::create([
                            'vacant_budget_cost_id' => $vacantBudgetCostId,
                            'resort_budget_cost_id' => $costConfig['resort_budget_cost_id'],
                            'value' => $costConfig['value'],
                            'currency' => $costConfig['currency'] ?? 'USD',
                            'hours' => $costConfig['hours'] ?? 0,
                            'department_id' => $departmentId,
                            'position_id' => $positionId,
                            'resort_id' => $resortId,
                            'year' => $year,
                            'month' => $month
                        ]);
                    }
                }

                // If "Overtime - Holiday" is selected, automatically calculate and add it for all 12 months
                if ($overtimeHolidayConfig && $overtimeHolidayCostId) {
                    // Get vacant budget cost basic salary for calculation
                    $vacantBudgetCost = ResortVacantBudgetCost::find($vacantBudgetCostId);
                    $vacantBasicSalary = $vacantBudgetCost->basic_salary ?? 0;
                    $budgetCost = ResortBudgetCost::find($overtimeHolidayCostId);
                    $multiplier = $budgetCost->amount ?? 1.5; // Default 1.5 for holiday OT

                    // Delete existing overtime holiday configurations for all months
                    ResortVacantBudgetCostConfiguration::where('vacant_budget_cost_id', $vacantBudgetCostId)
                        ->where('resort_id', $resortId)
                        ->where('year', $year)
                        ->where('resort_budget_cost_id', $overtimeHolidayCostId)
                        ->delete();

                    // Create overtime holiday entries for all 12 months
                    for ($targetMonth = 1; $targetMonth <= 12; $targetMonth++) {
                        // Calculate holiday hours for this month
                        $holidayHours = $this->calculateHolidayHoursForMonth($year, $targetMonth);

                        // Calculate overtime holiday value
                        $daysInMonth = Carbon::create($year, $targetMonth, 1)->daysInMonth;
                        $dailySalary = $vacantBasicSalary / $daysInMonth;
                        $hourlyRate = $dailySalary / 8;
                        $overtimeHourlyRate = $hourlyRate * $multiplier;
                        $calculatedValue = $overtimeHourlyRate * $holidayHours;

                        ResortVacantBudgetCostConfiguration::create([
                            'vacant_budget_cost_id' => $vacantBudgetCostId,
                            'resort_budget_cost_id' => $overtimeHolidayCostId,
                            'value' => $calculatedValue,
                            'currency' => $overtimeHolidayConfig['currency'] ?? 'USD',
                            'hours' => $holidayHours,
                            'department_id' => $departmentId,
                            'position_id' => $positionId,
                            'resort_id' => $resortId,
                            'year' => $year,
                            'month' => $targetMonth
                        ]);
                    }
                }

                // If "Overtime - Normal" is selected, automatically add it for the next 6 months
                // (Current month is already added above, so we add the next 5 months to make 6 total)
                if ($overtimeNormalConfig) {
                    $startMonth = $month;
                    $endMonth = min(12, $startMonth + 5); // Add for next 5 months (total 6 months including current)

                    for ($targetMonth = $startMonth + 1; $targetMonth <= $endMonth; $targetMonth++) {
                        // Check if overtime normal configuration already exists for this month
                        $existingOvertimeNormal = ResortVacantBudgetCostConfiguration::where('vacant_budget_cost_id', $vacantBudgetCostId)
                            ->where('resort_id', $resortId)
                            ->where('year', $year)
                            ->where('month', $targetMonth)
                            ->where('resort_budget_cost_id', $overtimeNormalConfig['resort_budget_cost_id'])
                            ->first();

                        // Only create if it doesn't already exist (to avoid overwriting manual entries)
                        if (!$existingOvertimeNormal) {
                            ResortVacantBudgetCostConfiguration::create([
                                'vacant_budget_cost_id' => $vacantBudgetCostId,
                                'resort_budget_cost_id' => $overtimeNormalConfig['resort_budget_cost_id'],
                                'value' => $overtimeNormalConfig['value'],
                                'currency' => $overtimeNormalConfig['currency'] ?? 'USD',
                                'hours' => $overtimeNormalConfig['hours'] ?? 0,
                                'department_id' => $departmentId,
                                'position_id' => $positionId,
                                'resort_id' => $resortId,
                                'year' => $year,
                                'month' => $targetMonth
                            ]);
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Vacant position monthly budget updated successfully'
            ]);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile() . " | Line: " . $e->getLine() . " | Message: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating vacant budget: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if a budget cost is "Overtime - Normal"
     * Uses the same detection logic as in the modal blade file
     */
    private function isOvertimeNormal($budgetCost)
    {
        if (!$budgetCost) {
            return false;
        }

        $particularsOriginal = $budgetCost->particulars ?? '';
        $costTitle = $budgetCost->cost_title ?? '';
        $particularsLower = strtolower(trim($particularsOriginal));
        $costTitleLower = strtolower(trim($costTitle));
        $particularsClean = strtolower(preg_replace('/[\s\-_]+/', '', $particularsOriginal));
        $costTitleClean = strtolower(preg_replace('/[\s\-_]+/', '', $costTitle));

        // Known overtime normal names (excluding holiday)
        $knownOvertimeNormalNames = [
            'overtime - normal',
            'overtime-normal',
            'ot - normal',
            'ot-normal',
            'overtime normal'
        ];

        // Check for exact matches
        if (in_array($particularsLower, $knownOvertimeNormalNames) || in_array($costTitleLower, $knownOvertimeNormalNames)) {
            return true;
        }

        // Check if it contains "overtime" or "ot" AND "normal" but NOT "holiday"
        if ((strpos($particularsLower, 'overtime') !== false || strpos($particularsClean, 'overtime') !== false ||
             strpos($costTitleLower, 'overtime') !== false || strpos($costTitleClean, 'overtime') !== false ||
             strpos($particularsLower, ' ot ') !== false || strpos($costTitleLower, ' ot ') !== false) &&
            (strpos($particularsLower, 'normal') !== false || strpos($costTitleLower, 'normal') !== false) &&
            strpos($particularsLower, 'holiday') === false && strpos($costTitleLower, 'holiday') === false) {
            return true;
        }

        // Pattern matching for OT normal variations
        if (preg_match('/\b(ot|overtime)[\s\-_]*normal\b/i', $particularsOriginal) ||
            preg_match('/\b(ot|overtime)[\s\-_]*normal\b/i', $costTitle)) {
            // Make sure it's not holiday
            if (stripos($particularsOriginal, 'holiday') === false && stripos($costTitle, 'holiday') === false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a budget cost is "Overtime - Holiday"
     * Uses the same detection logic as in the modal blade file
     */
    private function isOvertimeHoliday($budgetCost)
    {
        if (!$budgetCost) {
            return false;
        }

        $particularsOriginal = $budgetCost->particulars ?? '';
        $costTitle = $budgetCost->cost_title ?? '';
        $particularsLower = strtolower(trim($particularsOriginal));
        $costTitleLower = strtolower(trim($costTitle));
        $particularsClean = strtolower(preg_replace('/[\s\-_]+/', '', $particularsOriginal));
        $costTitleClean = strtolower(preg_replace('/[\s\-_]+/', '', $costTitle));

        // Known overtime holiday names
        $knownOvertimeHolidayNames = [
            'overtime - holiday',
            'overtime-holiday',
            'ot - holiday',
            'ot-holiday',
            'overtime holiday'
        ];

        // Check for exact matches
        if (in_array($particularsLower, $knownOvertimeHolidayNames) || in_array($costTitleLower, $knownOvertimeHolidayNames)) {
            return true;
        }

        // Check if it contains "overtime" or "ot" AND "holiday"
        if ((strpos($particularsLower, 'overtime') !== false || strpos($particularsClean, 'overtime') !== false ||
             strpos($costTitleLower, 'overtime') !== false || strpos($costTitleClean, 'overtime') !== false ||
             strpos($particularsLower, ' ot ') !== false || strpos($costTitleLower, ' ot ') !== false) &&
            (strpos($particularsLower, 'holiday') !== false || strpos($costTitleLower, 'holiday') !== false)) {
            return true;
        }

        // Pattern matching for OT holiday variations
        if (preg_match('/\b(ot|overtime)[\s\-_]*holiday\b/i', $particularsOriginal) ||
            preg_match('/\b(ot|overtime)[\s\-_]*holiday\b/i', $costTitle)) {
            return true;
        }

        return false;
    }

    /**
     * Calculate holiday hours for a specific month
     * Formula: (Fridays + Public Holidays - Fridays that are also Public Holidays) × 10 hours
     *
     * @param int $year
     * @param int $month (1-12)
     * @return int Total holiday hours for the month
     */
    private function calculateHolidayHoursForMonth($year, $month)
    {
        // Create Carbon instance for the first day of the month
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        // Count Fridays in the month
        $fridays = [];
        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            if ($currentDate->dayOfWeek === Carbon::FRIDAY) {
                // Format as 'dd-mm-yyyy' to match database format
                $fridays[] = $currentDate->format('d M Y');
            }
            $currentDate->addDay();
        }

        $fridayCount = count($fridays);

        // Get public holidays for this month from database
        // Public holidays are stored in format 'dd-mm-yyyy'
        $allPublicHolidays = PublicHoliday::where('status', 'active')->get();

        \Log::info("Holiday Calculation Debug - Looking for holidays in {$year}-{$month}");
        \Log::info("Total active holidays in database: " . $allPublicHolidays->count());

        $publicHolidays = $allPublicHolidays->filter(function($holiday) use ($year, $month) {
                // Parse the holiday_date (format: dd-mm-yyyy)
                $holidayDateRaw = trim($holiday->holiday_date);

                // Try to parse the date - handle multiple formats
                $dateParts = [];
                if (strpos($holidayDateRaw, '-') !== false) {
                    // Format: dd-mm-yyyy or d-m-yyyy
                    $dateParts = explode('-', $holidayDateRaw);
                } elseif (strpos($holidayDateRaw, '/') !== false) {
                    // Format: dd/mm/yyyy or d/m/yyyy
                    $dateParts = explode('/', $holidayDateRaw);
                } else {
                    \Log::warning("Unrecognized date format for holiday ID {$holiday->id}: '{$holidayDateRaw}'");
                    return false;
                }

                \Log::info("Processing holiday: ID={$holiday->id}, Name={$holiday->name}, Date={$holidayDateRaw}, Parts=" . json_encode($dateParts));

                if (count($dateParts) === 3) {
                    // Remove any whitespace and convert to integers
                    $holidayDay = (int)trim($dateParts[0]);
                    $holidayMonth = (int)trim($dateParts[1]);
                    $holidayYear = (int)trim($dateParts[2]);

                    \Log::info("Parsed: Day={$holidayDay}, Month={$holidayMonth}, Year={$holidayYear} | Looking for: Month={$month}, Year={$year}");
                    \Log::info("Type check - HolidayMonth type: " . gettype($holidayMonth) . ", value: " . var_export($holidayMonth, true));
                    \Log::info("Type check - Month type: " . gettype($month) . ", value: " . var_export($month, true));
                    \Log::info("Type check - HolidayYear type: " . gettype($holidayYear) . ", value: " . var_export($holidayYear, true));
                    \Log::info("Type check - Year type: " . gettype($year) . ", value: " . var_export($year, true));

                    // Validate parsed values
                    if ($holidayDay < 1 || $holidayDay > 31 || $holidayMonth < 1 || $holidayMonth > 12 || $holidayYear < 2000 || $holidayYear > 2100) {
                        \Log::warning("Invalid date values for holiday ID {$holiday->id}: Day={$holidayDay}, Month={$holidayMonth}, Year={$holidayYear}");
                        return false;
                    }

                    // Check if this holiday falls in the specified month and year
                    // Use loose comparison first to debug, then strict
                    $monthMatch = ($holidayMonth == $month);
                    $yearMatch = ($holidayYear == $year);
                    $monthStrictMatch = ($holidayMonth === $month);
                    $yearStrictMatch = ($holidayYear === $year);

                    \Log::info("Comparison results - Month loose: " . ($monthMatch ? 'true' : 'false') . ", strict: " . ($monthStrictMatch ? 'true' : 'false'));
                    \Log::info("Comparison results - Year loose: " . ($yearMatch ? 'true' : 'false') . ", strict: " . ($yearStrictMatch ? 'true' : 'false'));

                    $matches = ($holidayMonth == $month && $holidayYear == $year);

                    if ($matches) {
                        \Log::info("✓ Holiday '{$holiday->name}' ({$holidayDateRaw}) MATCHES {$year}-{$month}");
                    } else {
                        \Log::info("✗ Holiday '{$holiday->name}' ({$holidayDateRaw}) does NOT match {$year}-{$month}");
                        \Log::info("  Month comparison: {$holidayMonth} " . ($monthMatch ? '==' : '!=') . " {$month}");
                        \Log::info("  Year comparison: {$holidayYear} " . ($yearMatch ? '==' : '!=') . " {$year}");
                    }

                    return $matches;
                } else {
                    \Log::warning("Invalid date format for holiday ID {$holiday->id}: '{$holidayDateRaw}' (expected dd-mm-yyyy or dd/mm/yyyy, got " . count($dateParts) . " parts)");
                }
                return false;
            })
            ->map(function($holiday) {
                // Normalize the date format to 'dd-mm-yyyy' for comparison
                $holidayDateRaw = trim($holiday->holiday_date);
                $dateParts = [];

                // Handle both - and / separators
                if (strpos($holidayDateRaw, '-') !== false) {
                    $dateParts = explode('-', $holidayDateRaw);
                } elseif (strpos($holidayDateRaw, '/') !== false) {
                    $dateParts = explode('/', $holidayDateRaw);
                }

                if (count($dateParts) === 3) {
                    // Ensure consistent format: dd-mm-yyyy
                    $normalized = sprintf('%02d-%02d-%04d', (int)trim($dateParts[0]), (int)trim($dateParts[1]), (int)trim($dateParts[2]));
                    \Log::info("Normalized holiday date: '{$holidayDateRaw}' -> '{$normalized}'");
                    return $normalized;
                }
                \Log::warning("Could not normalize holiday date: '{$holidayDateRaw}'");
                return $holidayDateRaw;
            })
            ->toArray();

        $publicHolidayCount = count($publicHolidays);

        \Log::info("Filtered public holidays for {$year}-{$month}: " . json_encode($publicHolidays));

        // Normalize Friday dates for comparison
        $normalizedFridays = array_map(function($friday) {
            $dateParts = explode('-', $friday);
            if (count($dateParts) === 3) {
                return sprintf('%02d-%02d-%04d', (int)$dateParts[0], (int)$dateParts[1], (int)$dateParts[2]);
            }
            return $friday;
        }, $fridays);

        // Count how many Fridays are also public holidays (to avoid double counting)
        $fridaysThatArePublicHolidays = 0;
        foreach ($normalizedFridays as $friday) {
            if (in_array($friday, $publicHolidays)) {
                $fridaysThatArePublicHolidays++;
            }
        }

        // Calculate total holiday days
        // Total = Fridays + Public Holidays - Fridays that are also Public Holidays
        $totalHolidayDays = $fridayCount + $publicHolidayCount - $fridaysThatArePublicHolidays;

        // Calculate total holiday hours (10 hours per day)
        $totalHolidayHours = $totalHolidayDays * 10;

        \Log::info("Holiday Calculation Summary for {$year}-{$month}:");
        \Log::info("  - Fridays: {$fridayCount}");
        \Log::info("  - Public Holidays: {$publicHolidayCount} " . json_encode($publicHolidays));
        \Log::info("  - Fridays that are also Public Holidays (Overlap): {$fridaysThatArePublicHolidays}");
        \Log::info("  - Total Holiday Days: {$totalHolidayDays}");
        \Log::info("  - Total Holiday Hours: {$totalHolidayHours}");

        return $totalHolidayHours;
    }

    /**
     * Get holiday hours for a specific month
     * Used by frontend to auto-populate overtime holiday hours
     */
    public function getHolidayHoursForMonth(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'year' => 'required|integer|min:2000|max:2100',
                'month' => 'required|integer|min:1|max:12'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $year = $request->input('year');
            $month = $request->input('month');

            // Get all holidays for debugging
            $allHolidays = PublicHoliday::where('status', 'active')
                ->get()
                ->map(function($holiday) {
                    return [
                        'id' => $holiday->id,
                        'name' => $holiday->name,
                        'date' => $holiday->holiday_date,
                        'status' => $holiday->status
                    ];
                });

            $holidayHours = $this->calculateHolidayHoursForMonth($year, $month);

            return response()->json([
                'success' => true,
                'year' => $year,
                'month' => $month,
                'holiday_hours' => $holidayHours,
                'debug' => [
                    'all_active_holidays' => $allHolidays,
                    'check_logs' => 'Check Laravel logs for detailed calculation breakdown'
                ]
            ]);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile() . " | Line: " . $e->getLine() . " | Message: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while calculating holiday hours: ' . $e->getMessage()
            ], 500);
        }
    }
}
