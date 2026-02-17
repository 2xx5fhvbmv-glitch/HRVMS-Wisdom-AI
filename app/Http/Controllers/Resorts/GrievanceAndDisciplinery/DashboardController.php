<?php

namespace App\Http\Controllers\Resorts\GrievanceAndDisciplinery;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use DB;
use URL;
use Auth;
use Carbon\Carbon;
use App\Helpers\Common;
use App\Models\GrievanceCategory;
use App\Models\GrivanceSubmissionModel;
class DashboardController extends Controller
{
    protected $resort;
    protected $underEmp_id=[];
    public function __construct()
    {
        $this->resort = $resortId = auth()->guard('resort-admin')->user();
        if(!$this->resort) return;
        if($this->resort->is_master_admin == 0){
            $reporting_to = $this->globalUser->GetEmployee->id;
            $this->underEmp_id = Common::getSubordinates($reporting_to);
        }
    }

    public function Admin_dashboard(Request $request)
    {

    }
    public function HR_Dashobard(Request $request)
    {


        $markasResolved = GrivanceSubmissionModel::where('resort_id', $this->resort->resort_id)->get();
        $totalcase =  $markasResolved->count(); 
        $resolvedCase =  $markasResolved->where('status', 'resolved')->count();
        $DelegatedCases =  $markasResolved->where('Assigned', 'Yes')->count();
        $PendingApprovals =  $markasResolved->where('SentToGM', 'Yes')->where("Gm_Decision",'Pending')->count();


        $grivanceCategoryWiseCount=[];
        $GrievanceCategory =  GrievanceCategory::with(['GrievancesSubmisstion'])->where('resort_id', $this->resort->resort_id)->get()->map(function ($category) use(&$grivanceCategoryWiseCount) 
        {


        });


        $totalPercengate= $totalcase > 0 ? round(($resolvedCase / $totalcase) * 100, 2) : 0;
        $page_title ="People Relation";


        return view('resorts.GrievanceAndDisciplinery.dashboard.hrdashboard',compact('page_title','grivanceCategoryWiseCount','totalPercengate','DelegatedCases','PendingApprovals'));
    }

    
    public function Hod_dashboard(Request $request)
    {

    }

}
