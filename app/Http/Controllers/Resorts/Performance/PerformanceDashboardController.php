<?php

namespace App\Http\Controllers\Resorts\Performance;

use DB;
use URL;
use Auth;
use Carbon\Carbon;
use App\Helpers\Common;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\ResortDepartment;
use App\Models\ResortPosition;
use App\Models\PerformaChildCycle;
use App\Models\PerformanceCycle;
use App\Models\EmployeePipPlan;
use App\Models\EmployeePdpPlan;
use App\Http\Controllers\Controller;
class PerformanceDashboardController extends Controller
{
    public $globalUser='';
    protected $underEmp_id=[];

    public function __construct()
    {
        $this->globalUser = Auth::guard('resort-admin')->user();
        $this->resort = $resortId = auth()->guard('resort-admin')->user();
        if(!$this->resort) return;
        if($this->resort->is_master_admin == 0){
            $reporting_to = isset($this->globalUser->GetEmployee) ? $this->globalUser->GetEmployee->id : 3;
            $this->underEmp_id = Common::getSubordinates($reporting_to);
        }
    }
    public function Admin_dashboard()
    {

    }
    public function HR_Dashobard(Request $request)
    {
        $page_title="Performance Dashboard";
        $resort_id = $this->globalUser->resort_id;
        $scopedIds = Common::getPerformanceScopedEmpIds();

        // Year filter — dynamic from data + surrounding years
        $cycleYears = DB::table('performance_cycles')
                        ->where('resort_id', $resort_id)
                        ->selectRaw('YEAR(Start_Date) as y')
                        ->pluck('y')->filter()->unique();
        $currentYear = (int) date('Y');
        $availableYears = $cycleYears->merge([$currentYear - 1, $currentYear, $currentYear + 1])
                                     ->unique()
                                     ->filter()
                                     ->sortDesc()
                                     ->values();
        $selectedYear = (int) ($request->year ?: $currentYear);

        // Total Employee card — scoped to the user's department unless they have full access (HR/GM).
        $scopedDeptIds = Common::getScopedDepartmentIds();
        $Employee_count = Employee::where('resort_id', $resort_id)
                                    ->where('status', 'Active')
                                    ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))
                                    ->whereHas('resortAdmin', function($query) {
                                        $query->where('status', 'Active');
                                    })->count();

        // Appraisal pending: employees in active cycles for the selected year
        $activeCycleIds = DB::table('performance_cycles')
                            ->where('resort_id', $resort_id)
                            ->whereIn('status', ['OnGoing','Pending'])
                            ->where(function ($q) use ($selectedYear) {
                                $q->whereYear('Start_Date', $selectedYear)
                                  ->orWhereYear('End_Date', $selectedYear);
                            })
                            ->pluck('id');

        $appraisal_total = DB::table('performa_child_cycles')
                            ->whereIn('Parent_cycle_id', $activeCycleIds)
                            ->when(is_array($scopedIds), fn($q) => $q->whereIn('Emp_main_id', $scopedIds))
                            ->count();

        $appraisal_pending = DB::table('performa_child_cycles')
                            ->whereIn('Parent_cycle_id', $activeCycleIds)
                            ->when(is_array($scopedIds), fn($q) => $q->whereIn('Emp_main_id', $scopedIds))
                            ->where('manager_review_status', 'pending')
                            ->count();

        // Department wise performance (active employee distribution by department) — dept-scoped too.
        $department_data = DB::table('employees')
                            ->join('resort_departments', 'resort_departments.id', '=', 'employees.Dept_id')
                            ->where('employees.resort_id', $resort_id)
                            ->where('employees.status', 'Active')
                            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('employees.Dept_id', $scopedDeptIds))
                            ->groupBy('resort_departments.id', 'resort_departments.name')
                            ->select('resort_departments.name', DB::raw('COUNT(employees.id) as count'))
                            ->orderByDesc('count')
                            ->get();

        // Performance Cycles with review counts (filtered by selected year)
        $performance_cycles = DB::table('performance_cycles')
                            ->where('resort_id', $resort_id)
                            ->where(function ($q) use ($selectedYear) {
                                $q->whereYear('Start_Date', $selectedYear)
                                  ->orWhereYear('End_Date', $selectedYear);
                            })
                            ->orderByDesc('id')
                            ->limit(5)
                            ->get()
                            ->map(function ($cycle) {
                                $children = DB::table('performa_child_cycles')->where('Parent_cycle_id', $cycle->id)->get();
                                $cycle->total_employees = $children->count();
                                $cycle->self_completed = $children->where('self_review_status', 'completed')->count();
                                $cycle->manager_completed = $children->where('manager_review_status', 'completed')->count();
                                $cycle->self_pending = $children->where('self_review_status', 'pending')->count();
                                $cycle->manager_pending = $children->where('manager_review_status', 'pending')->count();
                                return $cycle;
                            });

        $approved_checkins_count = DB::table('monthly_checking_models')
            ->where('resort_id', $resort_id)
            ->where('approval_status', 'approved')
            ->whereYear('date_discussion', $selectedYear)
            ->when(is_array($scopedIds), fn($q) => $q->whereIn('emp_id', $scopedIds))
            ->count();

        $pip_count = DB::table('employee_pip_plans')
            ->where('resort_id', $resort_id)
            ->where('status', 'active')
            ->when(is_array($scopedIds), fn($q) => $q->whereIn('employee_id', $scopedIds))
            ->count();

        $pdp_count = DB::table('employee_pdp_plans')
            ->where('resort_id', $resort_id)
            ->where('status', 'active')
            ->when(is_array($scopedIds), fn($q) => $q->whereIn('employee_id', $scopedIds))
            ->count();

        // Appraisal Pending Departments panel — only show departments the user can see.
        $activeCycleIdsForPanel = DB::table('performance_cycles')
            ->where('resort_id', $resort_id)
            ->where('status', 'OnGoing')
            ->pluck('id');
        $appraisalDepartments = \App\Models\ResortDepartment::where('resort_id', $resort_id)
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('id', $scopedDeptIds))
            ->get()
            ->map(function ($dept) use ($resort_id, $activeCycleIdsForPanel) {
                $deptEmpIds = \App\Models\Employee::where('resort_id', $resort_id)
                    ->where('Dept_id', $dept->id)
                    ->where('status', 'Active')
                    ->pluck('id');
                $totalInCycle = DB::table('performa_child_cycles')
                    ->whereIn('Parent_cycle_id', $activeCycleIdsForPanel)
                    ->whereIn('Emp_main_id', $deptEmpIds)
                    ->count();
                $pendingCount = DB::table('performa_child_cycles')
                    ->whereIn('Parent_cycle_id', $activeCycleIdsForPanel)
                    ->whereIn('Emp_main_id', $deptEmpIds)
                    ->whereNull('Manager_review_date')
                    ->count();
                $dept->emp_count = $deptEmpIds->count();
                $dept->in_cycle_total = $totalInCycle;
                $dept->pending_count = $pendingCount;
                $dept->completed_count = $totalInCycle - $pendingCount;
                return $dept;
            });

        $performanceInsights = $this->getCachedPerformanceInsights($resort_id, $scopedIds, $scopedDeptIds);

        return view('resorts.Performance.dashboard.hrdashboard', compact(
            'page_title', 'Employee_count', 'appraisal_total', 'appraisal_pending',
            'department_data', 'performance_cycles', 'approved_checkins_count',
            'pip_count', 'pdp_count', 'availableYears', 'selectedYear',
            'appraisalDepartments', 'performanceInsights'
        ));

    }

    /**
     * Build the three AI-insight cards (appraisal completion, performance risk,
     * review throughput) for the performance dashboard from live data. Mirrors
     * the leave module's buildLeaveInsights(): deterministic figures first, then
     * the FastAPI LLM layers a richer body + recommendation on top. Pass scoped
     * employee/department ids (null = whole resort) to respect the viewer's
     * visibility. Wrapped so any data hiccup degrades to sensible defaults.
     */
    private function buildPerformanceInsights($resortId, $scopedIds = null, $scopedDeptIds = null): array
    {
        $insights = [
            'completion' => ['title' => 'Appraisal Completion Outlook', 'body' => 'No active appraisal cycle for this period yet.'],
            'risk'       => ['title' => 'Performance Risk & PIP Watch', 'body' => 'No employees are flagged as at-risk right now.'],
            'throughput' => ['title' => 'Self vs Manager Review Throughput', 'body' => 'Not enough review activity to analyse yet.'],
        ];

        try {
            $year = (int) date('Y');

            // Active cycles for the current year.
            $activeCycleIds = DB::table('performance_cycles')
                ->where('resort_id', $resortId)
                ->whereIn('status', ['OnGoing', 'Pending'])
                ->where(function ($q) use ($year) {
                    $q->whereYear('Start_Date', $year)->orWhereYear('End_Date', $year);
                })
                ->pluck('id');

            // Base scoped child-cycle query for the active cycles.
            $childQ = DB::table('performa_child_cycles')->whereIn('Parent_cycle_id', $activeCycleIds);
            if (is_array($scopedIds)) {
                $childQ->whereIn('Emp_main_id', $scopedIds);
            }
            $total       = (clone $childQ)->count();
            $managerDone = (clone $childQ)->where('manager_review_status', 'completed')->count();

            // Card 1 — Appraisal Completion Outlook (overall % + per-department).
            if ($total > 0) {
                $pct = (int) round(($managerDone / $total) * 100);

                $deptRows = [];
                $depts = ResortDepartment::where('resort_id', $resortId)
                    ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('id', $scopedDeptIds))
                    ->get();
                foreach ($depts as $dept) {
                    $deptEmpIds = Employee::where('resort_id', $resortId)
                        ->where('Dept_id', $dept->id)
                        ->where('status', 'Active')
                        ->pluck('id');
                    if ($deptEmpIds->isEmpty()) {
                        continue;
                    }
                    $dTotal = DB::table('performa_child_cycles')
                        ->whereIn('Parent_cycle_id', $activeCycleIds)
                        ->whereIn('Emp_main_id', $deptEmpIds)
                        ->count();
                    if ($dTotal == 0) {
                        continue;
                    }
                    $dDone = DB::table('performa_child_cycles')
                        ->whereIn('Parent_cycle_id', $activeCycleIds)
                        ->whereIn('Emp_main_id', $deptEmpIds)
                        ->where('manager_review_status', 'completed')
                        ->count();
                    $deptRows[] = [
                        'name'      => $dept->name,
                        'total'     => $dTotal,
                        'completed' => $dDone,
                        'pending'   => $dTotal - $dDone,
                        'pct'       => (int) round(($dDone / $dTotal) * 100),
                    ];
                }

                $lagging = null;
                foreach ($deptRows as $r) {
                    if ($r['pending'] > 0 && ($lagging === null || $r['pct'] < $lagging['pct'])) {
                        $lagging = $r;
                    }
                }
                $body = "{$pct}% of this year's appraisals are complete ({$managerDone} of {$total} manager reviews).";
                if ($lagging) {
                    $body .= " {$lagging['name']} is furthest behind at {$lagging['pct']}%.";
                }
                $insights['completion'] = ['title' => 'Appraisal Completion Outlook', 'body' => $body];
                $insights['completion']['details'] = [
                    'overall_pct' => $pct,
                    'total'       => $total,
                    'completed'   => $managerDone,
                    'departments' => $deptRows,
                ];
            }

            // Card 2 — Performance Risk & PIP Watch (active PIP/PDP employees).
            $pipQ = DB::table('employee_pip_plans')->where('resort_id', $resortId)->where('status', 'active');
            if (is_array($scopedIds)) {
                $pipQ->whereIn('employee_id', $scopedIds);
            }
            $pipIds = $pipQ->pluck('employee_id');
            $pdpQ = DB::table('employee_pdp_plans')->where('resort_id', $resortId)->where('status', 'active');
            if (is_array($scopedIds)) {
                $pdpQ->whereIn('employee_id', $scopedIds);
            }
            $pdpIds = $pdpQ->pluck('employee_id');

            $pipCount = $pipIds->count();
            $pdpCount = $pdpIds->count();
            if ($pipCount > 0 || $pdpCount > 0) {
                $allIds = $pipIds->merge($pdpIds)->unique()->values();
                $emps = DB::table('employees as e')
                    ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                    ->leftJoin('resort_departments as d', 'd.id', '=', 'e.Dept_id')
                    ->whereIn('e.id', $allIds)
                    ->select('e.id', DB::raw("TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) as name"), 'd.name as dept')
                    ->get()->keyBy('id');

                $riskEmployees = [];
                foreach ($allIds as $eid) {
                    $e = $emps->get($eid);
                    $types = [];
                    if ($pipIds->contains($eid)) {
                        $types[] = 'PIP';
                    }
                    if ($pdpIds->contains($eid)) {
                        $types[] = 'PDP';
                    }
                    $riskEmployees[] = [
                        'name' => ($e && trim((string) $e->name) !== '') ? $e->name : ('Employee #' . $eid),
                        'dept' => ($e && $e->dept) ? $e->dept : '—',
                        'type' => implode(' + ', $types),
                    ];
                }
                $body = "{$pipCount} employee" . ($pipCount == 1 ? '' : 's') . " on active PIPs and {$pdpCount} on PDPs need close follow-up.";
                $insights['risk'] = ['title' => 'Performance Risk & PIP Watch', 'body' => $body];
                $insights['risk']['details'] = ['pip' => $pipCount, 'pdp' => $pdpCount, 'employees' => $riskEmployees];
            }

            // Card 3 — Self vs Manager Review Throughput (where the bottleneck is).
            if ($total > 0) {
                $selfDone = (clone $childQ)->where('self_review_status', 'completed')->count();
                $selfPct  = (int) round(($selfDone / $total) * 100);
                $mgrPct   = (int) round(($managerDone / $total) * 100);
                $bottleneck = $selfPct < $mgrPct ? 'self-review' : 'manager-review';
                $insights['throughput'] = [
                    'title' => 'Self vs Manager Review Throughput',
                    'body'  => "Self-reviews are {$selfPct}% complete and manager reviews {$mgrPct}% — the {$bottleneck} stage is the current bottleneck.",
                ];
                $insights['throughput']['details'] = [
                    'self_pct'          => $selfPct,
                    'manager_pct'       => $mgrPct,
                    'total'             => $total,
                    'self_completed'    => $selfDone,
                    'manager_completed' => $managerDone,
                ];
            }
        } catch (\Throwable $e) {
            \Log::warning('Performance dashboard AI insights failed: ' . $e->getMessage());
        }

        // Layer the FastAPI LLM on top of the deterministic numbers (same bridge
        // the leave module uses); falls back to the computed text if AI is down.
        $insights = Common::enrichDashboardInsights(
            $insights, 'performance management', ['completion', 'risk', 'throughput']
        );

        return $insights;
    }

    /**
     * Cached wrapper around buildPerformanceInsights() with a 48h "Regenerate"
     * cooldown (mirrors the leave module). ?regenerate_insights=1 recomputes once
     * the cooldown elapses. Returns the insights plus a '_meta' block.
     */
    private function getCachedPerformanceInsights($resortId, $scopedIds = null, $scopedDeptIds = null): array
    {
        $cooldownHours = 48;
        $scopeSig = is_array($scopedIds) ? substr(md5(json_encode($scopedIds)), 0, 8) : 'all';
        $cacheKey = 'performance_insights:' . $resortId . ':' . $scopeSig;
        $now = Carbon::now();

        $cached = \Cache::get($cacheKey);
        $regenerate = request()->boolean('regenerate_insights');

        $stale = !is_array($cached) || empty($cached['generated_at']);
        if (!$stale) {
            $generatedAt = Carbon::parse($cached['generated_at']);
            if ($regenerate && $generatedAt->diffInHours($now) >= $cooldownHours) {
                $stale = true;
            }
        }

        if ($stale) {
            $cached = [
                'insights'     => $this->buildPerformanceInsights($resortId, $scopedIds, $scopedDeptIds),
                'generated_at' => $now->toIso8601String(),
            ];
            \Cache::put($cacheKey, $cached, $now->copy()->addDays(30));
        }

        $generatedAt = Carbon::parse($cached['generated_at']);
        $insights = $cached['insights'];
        $insights['_meta'] = [
            'generated_at'   => $generatedAt,
            'can_regenerate' => $generatedAt->diffInHours($now) >= $cooldownHours,
            'next_available' => $generatedAt->copy()->addHours($cooldownHours),
        ];

        return $insights;
    }

    public function Hod_dashboard(Request $request)
    {
        $dashboardLabel = request('dashboard_label', 'HOD');
        $page_header = '<span class="arca-font">'.$dashboardLabel.'</span> Dashboard';
    }

    public function excom_dashboard()
    {
        request()->merge(['dashboard_label' => 'XCOM']);
        return $this->Hod_dashboard(request());
    }

    public function employeesIndex()
    {
        $page_title = 'Employees';
        $resort_id  = $this->resort->resort_id;
        $departments = ResortDepartment::where('resort_id', $resort_id)->where('status', 'active')->get();
        $positions   = ResortPosition::where('resort_id', $resort_id)->where('status', 'active')->get();
        return view('resorts.Performance.employee.list', compact('page_title', 'departments', 'positions'));
    }

    public function employeesGrid(Request $request)
    {
        $resort_id = $this->resort->resort_id;

        $query = Employee::with(['resortAdmin', 'position', 'department'])
            ->where('resort_id', $resort_id)
            ->where('status', '!=', 'Inactive');

        $scopedIds = Common::getPerformanceScopedEmpIds();
        if (is_array($scopedIds)) {
            $query->whereIn('id', $scopedIds);
        }

        if ($request->searchTerm) {
            $searchTerm = $request->searchTerm;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('Emp_id', 'LIKE', "%{$searchTerm}%")
                  ->orWhereHas('resortAdmin', function ($a) use ($searchTerm) {
                      $a->where('first_name', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('last_name', 'LIKE', "%{$searchTerm}%");
                  })
                  ->orWhereHas('position', function ($p) use ($searchTerm) {
                      $p->where('position_title', 'LIKE', "%{$searchTerm}%");
                  });
            });
        }

        if ($request->filled('department_id')) {
            $query->where('Dept_id', $request->department_id);
        }
        if ($request->filled('position_id')) {
            $query->where('Position_id', $request->position_id);
        }

        $pageSize  = $request->input('pageSize', 10);
        $employees = $query->orderBy('created_at', 'desc')->paginate($pageSize);

        $activeCycleIds = PerformanceCycle::where('resort_id', $resort_id)
            ->whereIn('status', ['OnGoing', 'Pending'])
            ->pluck('id');

        $employeeStatus = [];
        foreach ($employees as $emp) {
            $employeeStatus[$emp->id] = $this->computeAppraisalStatus($emp->id, $activeCycleIds);
        }

        if ($request->appraisal_status) {
            $filtered = $employees->getCollection()->filter(function ($emp) use ($employeeStatus, $request) {
                return ($employeeStatus[$emp->id]['label'] ?? '') === $request->appraisal_status;
            })->values();
            $employees->setCollection($filtered);
        }

        return response()->json([
            'html'       => view('resorts.Performance.employee.grid', compact('employees', 'employeeStatus'))->render(),
            'pagination' => (string) $employees->withQueryString()->links(),
        ]);
    }

    public function employeesListData(Request $request)
    {
        $resort_id = $this->resort->resort_id;

        $query = Employee::with(['resortAdmin', 'position', 'department'])
            ->where('resort_id', $resort_id)
            ->where('status', '!=', 'Inactive');

        $scopedIds = Common::getPerformanceScopedEmpIds();
        if (is_array($scopedIds)) {
            $query->whereIn('id', $scopedIds);
        }

        if ($request->searchTerm) {
            $searchTerm = $request->searchTerm;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('Emp_id', 'LIKE', "%{$searchTerm}%")
                  ->orWhereHas('resortAdmin', function ($a) use ($searchTerm) {
                      $a->where('first_name', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('last_name', 'LIKE', "%{$searchTerm}%");
                  })
                  ->orWhereHas('position', function ($p) use ($searchTerm) {
                      $p->where('position_title', 'LIKE', "%{$searchTerm}%");
                  });
            });
        }
        if ($request->filled('department_id')) {
            $query->where('Dept_id', $request->department_id);
        }
        if ($request->filled('position_id')) {
            $query->where('Position_id', $request->position_id);
        }

        $activeCycleIds = PerformanceCycle::where('resort_id', $resort_id)
            ->whereIn('status', ['OnGoing', 'Pending'])
            ->pluck('id');

        return datatables()->of($query)
            ->addColumn('applicant', function ($row) {
                return '<div class="tableUser-block"><div class="img-circle"><img src="'.\App\Helpers\Common::getResortUserPicture($row->Admin_Parent_id ?? null).'" alt="user"></div><span class="userApplicants-btn">'.e($row->resortAdmin->full_name ?? '').'</span></div>';
            })
            ->addColumn('position', fn($row) => $row->position->position_title ?? '')
            ->addColumn('department', fn($row) => $row->department->name ?? '')
            ->addColumn('rating', function ($row) use ($activeCycleIds) {
                $st = $this->computeAppraisalStatus($row->id, $activeCycleIds);
                $r = (int) round($st['rating']);
                $html = '';
                for ($i = 1; $i <= 5; $i++) {
                    $html .= '<i class="fa-'.($i <= $r ? 'solid' : 'regular').' fa-star" style="color:#f5a623;"></i>';
                }
                return $html;
            })
            ->addColumn('appraisal_status', function ($row) use ($activeCycleIds) {
                $st = $this->computeAppraisalStatus($row->id, $activeCycleIds);
                $cls = match($st['label']) {
                    'Done'        => 'badge-themeSuccess',
                    'In Progress' => 'badge-themeWarning',
                    default       => 'badge-themeDanger',
                };
                return '<span class="badge '.$cls.'" data-label="'.$st['label'].'">'.$st['label'].'</span>';
            })
            ->addColumn('action', function ($row) {
                return '<a href="'.route('Performance.employees.details', base64_encode($row->id)).'" class="btn perf-btn-primary btn-sm">View Details</a>';
            })
            ->rawColumns(['applicant', 'rating', 'appraisal_status', 'action'])
            ->make(true);
    }

    public function employeeDetails($id)
    {
        $page_title = 'Employee Details';
        $resort_id  = $this->resort->resort_id;
        $empId      = base64_decode($id);

        $scopedIds = Common::getPerformanceScopedEmpIds();
        if (is_array($scopedIds) && !in_array((int) $empId, $scopedIds)) {
            abort(403, 'You do not have access to this employee.');
        }

        $employee = Employee::with(['resortAdmin', 'position', 'department'])
            ->where('resort_id', $resort_id)
            ->findOrFail($empId);

        $activeCycleIds = PerformanceCycle::where('resort_id', $resort_id)
            ->whereIn('status', ['OnGoing', 'Pending'])
            ->pluck('id');

        $status = $this->computeAppraisalStatus($empId, $activeCycleIds);

        // Emp_main_id may be stored as numeric id, base64, or the Emp_id string (legacy rows).
        $empMatchValues = [$empId, base64_encode($empId)];
        if ($employee->Emp_id) $empMatchValues[] = $employee->Emp_id;

        $history = DB::table('performa_child_cycles')
            ->leftJoin('performance_cycles', 'performance_cycles.id', '=', 'performa_child_cycles.Parent_cycle_id')
            ->whereIn('performa_child_cycles.Emp_main_id', $empMatchValues)
            ->where('performance_cycles.resort_id', $resort_id)
            ->orderBy('performa_child_cycles.created_at', 'desc')
            ->select(
                'performa_child_cycles.*',
                'performance_cycles.Cycle_Name',
                'performance_cycles.Start_Date',
                'performance_cycles.End_Date'
            )
            ->get();

        $latestChild = $history->first();
        $latestChildCycleId = $latestChild->id ?? null;
        // Pick the most informative view link: manager review if done, else self review if done, else null.
        $latestChildReviewRoute = null;
        if ($latestChild) {
            if ($latestChild->manager_review_status === 'completed') {
                $latestChildReviewRoute = 'Performance.Review.showManager';
            } elseif ($latestChild->self_review_status === 'completed') {
                $latestChildReviewRoute = 'Performance.Review.showSelf';
            }
        }

        $activePip = EmployeePipPlan::where('resort_id', $resort_id)
            ->where('employee_id', $empId)
            ->where('status', 'active')
            ->orderByDesc('id')
            ->first();

        $activePdp = EmployeePdpPlan::where('resort_id', $resort_id)
            ->where('employee_id', $empId)
            ->where('status', 'active')
            ->orderByDesc('id')
            ->first();

        return view('resorts.Performance.employee.details', compact(
            'page_title', 'employee', 'status', 'history', 'latestChildCycleId', 'latestChildReviewRoute', 'activePip', 'activePdp'
        ));
    }

    private function computeAppraisalStatus($empId, $activeCycleIds)
    {
        if ($activeCycleIds->isEmpty()) {
            return ['label' => 'Not Started', 'rating' => 0, 'has_rating' => false];
        }

        // Emp_main_id may be stored as numeric id, base64 of id, or the Emp_id string.
        $emp = Employee::find($empId);
        $matchValues = [$empId, base64_encode($empId)];
        if ($emp && $emp->Emp_id) $matchValues[] = $emp->Emp_id;

        $child = PerformaChildCycle::whereIn('Parent_cycle_id', $activeCycleIds)
            ->whereIn('Emp_main_id', $matchValues)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$child) {
            return ['label' => 'Not Started', 'rating' => 0, 'has_rating' => false];
        }

        // Three-state model that matches expectations:
        //   Done        — manager review completed (or self done for GM-review rows where manager is N/A)
        //   In Progress — a child cycle row exists for the employee (appraisal assigned), regardless of who has submitted yet
        //   Not Started — no child cycle row at all (handled above by the early return)
        if ($child->manager_review_status === 'completed'
            || ($child->manager_review_status === 'not_applicable' && $child->self_review_status === 'completed')) {
            $label = 'Done';
        } else {
            $label = 'In Progress';
        }

        $ratingInfo = $this->extractRatingFromChild($child);
        return ['label' => $label, 'rating' => $ratingInfo['rating'], 'has_rating' => $ratingInfo['has_rating']];
    }

    /**
     * Walk the cycle's template structure, find fields of type 'starRating',
     * read their values from manager_review_data, and return the average (out of 5).
     * Returns has_rating=false when nothing rateable was filled, so the UI can
     * show a "—" instead of five hollow stars.
     */
    private function extractRatingFromChild($child)
    {
        if (empty($child->manager_review_data)) {
            return ['rating' => 0, 'has_rating' => false];
        }
        $data = json_decode($child->manager_review_data, true);
        if (!is_array($data)) return ['rating' => 0, 'has_rating' => false];

        // Resolve the template that backed this child row (child level first, then parent fallback).
        $templateId = $child->template_id;
        if (empty($templateId)) {
            $cycle = \App\Models\PerformanceCycle::find($child->Parent_cycle_id);
            $templateId = $cycle->Manager_Review_Templete ?? $cycle->Self_Review_Templete ?? null;
        }
        $structure = $this->loadTemplateStructure($templateId);

        // Pull every starRating field's value out of the manager_review_data.
        $ratings = [];
        $maxRatings = [];
        foreach ($structure as $field) {
            if (($field['type'] ?? null) !== 'starRating') continue;
            $name = $field['name'] ?? null;
            if (!$name || !isset($data[$name])) continue;
            $val = (float) $data[$name];
            if ($val <= 0) continue;
            $ratings[]    = $val;
            $maxRatings[] = (float) ($field['maxRating'] ?? 5);
        }

        if (empty($ratings)) {
            // Legacy fallback: if a literal 'rating' key was used (older code path).
            if (isset($data['rating']) && (float) $data['rating'] > 0) {
                return ['rating' => min(5, (float) $data['rating']), 'has_rating' => true];
            }
            return ['rating' => 0, 'has_rating' => false];
        }

        // Normalize each star rating to a 0..5 scale, then average.
        $normalized = [];
        foreach ($ratings as $i => $r) {
            $max = $maxRatings[$i] ?: 5;
            $normalized[] = ($r / $max) * 5;
        }
        $avg = array_sum($normalized) / count($normalized);
        return ['rating' => $avg, 'has_rating' => true];
    }

    /**
     * Load and decode a performance template's form_structure given its prefixed id
     * (ninty_X / prof_X / numeric). Mirrors ReviewController::getTemplate so the
     * resolution is consistent across modules.
     */
    private function loadTemplateStructure($templateId)
    {
        if (empty($templateId) || $templateId === '0' || $templateId === 0) return [];

        $form = null;
        if (is_string($templateId) && strpos($templateId, 'ninty_') === 0) {
            $form = \App\Models\NintyDayPeformanceForm::find(substr($templateId, 6));
        } elseif (is_string($templateId) && strpos($templateId, 'prof_') === 0 && class_exists(\App\Models\Professionalform::class)) {
            $form = \App\Models\Professionalform::find(substr($templateId, 5));
        } elseif (is_numeric($templateId)) {
            // Try each form table (PerformanceTemplateForm → 90-day → professional).
            $form = \App\Models\PerformanceTemplateForm::find($templateId)
                ?: \App\Models\NintyDayPeformanceForm::find($templateId)
                ?: (class_exists(\App\Models\Professionalform::class) ? \App\Models\Professionalform::find($templateId) : null);
        }
        if (!$form || empty($form->form_structure)) return [];

        $decoded = json_decode($form->form_structure, true);
        // Some legacy rows are double-encoded.
        if (is_string($decoded)) $decoded = json_decode($decoded, true);
        return is_array($decoded) ? $decoded : [];
    }
}

