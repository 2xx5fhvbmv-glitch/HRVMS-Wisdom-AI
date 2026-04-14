<?php

namespace App\Http\Controllers\Resorts\Performance;

use DB;
use URL;
use Auth;
use Carbon\Carbon;
use App\Helpers\Common;
use Illuminate\Http\Request;
use App\Models\Employee;
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

        $Employee_count = Employee::where('resort_id', $resort_id)
                                    ->where('status', 'Active')
                                    ->whereHas('resortAdmin', function($query) {
                                        $query->where('status', 'Active');
                                    })->count();

        // Appraisal pending: employees in active cycles who haven't completed manager review
        $activeCycleIds = DB::table('performance_cycles')
                            ->where('resort_id', $resort_id)
                            ->whereIn('status', ['OnGoing','Pending'])
                            ->pluck('id');

        $appraisal_total = DB::table('performa_child_cycles')
                            ->whereIn('Parent_cycle_id', $activeCycleIds)
                            ->count();

        $appraisal_pending = DB::table('performa_child_cycles')
                            ->whereIn('Parent_cycle_id', $activeCycleIds)
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

        // Performance Cycles with review counts
        $performance_cycles = DB::table('performance_cycles')
                            ->where('resort_id', $resort_id)
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

        return view('resorts.Performance.dashboard.hrdashboard', compact(
            'page_title', 'Employee_count', 'appraisal_total', 'appraisal_pending',
            'department_data', 'performance_cycles'
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
}

