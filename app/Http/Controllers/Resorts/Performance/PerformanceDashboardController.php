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

        // Total Employee card shows resort-wide count (intentionally unscoped so dashboard acts as an overview)
        $Employee_count = Employee::where('resort_id', $resort_id)
                                    ->where('status', 'Active')
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

        // Department wise performance (active employee distribution by department)
        $department_data = DB::table('employees')
                            ->join('resort_departments', 'resort_departments.id', '=', 'employees.Dept_id')
                            ->where('employees.resort_id', $resort_id)
                            ->where('employees.status', 'Active')
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

        return view('resorts.Performance.dashboard.hrdashboard', compact(
            'page_title', 'Employee_count', 'appraisal_total', 'appraisal_pending',
            'department_data', 'performance_cycles', 'approved_checkins_count',
            'pip_count', 'pdp_count', 'availableYears', 'selectedYear'
        ));

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
                return '<a href="'.route('Performance.employees.details', base64_encode($row->id)).'" class="btn btn-theme btn-sm">View Details</a>';
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

        $history = DB::table('performa_child_cycles')
            ->leftJoin('performance_cycles', 'performance_cycles.id', '=', 'performa_child_cycles.Parent_cycle_id')
            ->where('performa_child_cycles.Emp_main_id', $empId)
            ->where('performance_cycles.resort_id', $resort_id)
            ->orderBy('performa_child_cycles.created_at', 'desc')
            ->select(
                'performa_child_cycles.*',
                'performance_cycles.Cycle_Name',
                'performance_cycles.Start_Date',
                'performance_cycles.End_Date'
            )
            ->get();

        $latestChildCycleId = $history->first()->id ?? null;

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
            'page_title', 'employee', 'status', 'history', 'latestChildCycleId', 'activePip', 'activePdp'
        ));
    }

    private function computeAppraisalStatus($empId, $activeCycleIds)
    {
        if ($activeCycleIds->isEmpty()) {
            return ['label' => 'Not Started', 'rating' => 0];
        }

        $child = PerformaChildCycle::whereIn('Parent_cycle_id', $activeCycleIds)
            ->where('Emp_main_id', $empId)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$child) {
            return ['label' => 'Not Started', 'rating' => 0];
        }

        if ($child->manager_review_status === 'completed') {
            $label = 'Done';
        } elseif ($child->self_review_status === 'completed' || $child->manager_review_status === 'pending') {
            $label = 'In Progress';
        } else {
            $label = 'Not Started';
        }

        $rating = 0;
        if (!empty($child->manager_review_data)) {
            $data = json_decode($child->manager_review_data, true);
            if (is_array($data) && isset($data['rating'])) {
                $rating = (float) $data['rating'];
            }
        }

        return ['label' => $label, 'rating' => $rating];
    }
}

