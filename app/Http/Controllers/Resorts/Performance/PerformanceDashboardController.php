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
                            ->where('status', 'OnGoing')
                            ->pluck('id');

        $appraisal_total = DB::table('performa_child_cycles')
                            ->whereIn('Parent_cycle_id', $activeCycleIds)
                            ->count();

        $appraisal_pending = DB::table('performa_child_cycles')
                            ->whereIn('Parent_cycle_id', $activeCycleIds)
                            ->whereNull('Manager_review_date')
                            ->count();

        return view('resorts.Performance.dashboard.hrdashboard', compact(
            'page_title', 'Employee_count', 'appraisal_total', 'appraisal_pending'
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

