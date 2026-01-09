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
        if($this->resort->is_master_admin == 0){
            $reporting_to = $this->globalUser->GetEmployee->id;
            $this->underEmp_id = Common::getSubordinates($reporting_to);
        }
    }
    public function Admin_dashboard()
    {

    }
    public function HR_Dashobard(Request $request)
    {
        $page_title="Performance Dashboard";
        $Employee_count = Employee::with(['resortAdmin'])->where('resort_id',$this->globalUser->resort_id)
                                        ->where('status','Active')
                                    ->whereHas('resortAdmin', function($query) {
                                        $query->where('status', 'Active');
                                    })->get()->count();

        return view('resorts.Performance.dashboard.hrdashboard',compact('page_title','Employee_count'));

    }

    public function Hod_dashboard(Request $request)
    {

    }
}

