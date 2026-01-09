<?php

namespace App\Http\Controllers\Resorts\Survey;

use DB;
use Carbon\Carbon;
use App\Helpers\Common;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ParentSurvey;
use App\Models\SurveyEmployee;
class DashboardController extends Controller
{
    protected $resort;
    protected $underEmp_id=[];
    protected $newdates =[];
    protected $olddates=[];
    public function __construct()
    {
        $this->resort = $resortId = auth()->guard('resort-admin')->user();
        $reporting_to = isset($this->resort->GetEmployee) ? $this->resort->GetEmployee->id : 3;
        $this->newdates[]= Carbon::today()->format('Y-m-d');
        for($i=1; $i<=2; $i++)
        {
            $this->olddates[] =  Carbon::today()->subDays($i)->format('Y-m-d');
            $this->newdates[] =  Carbon::today()->addDays($i)->format('Y-m-d');
        }
    }
    public function Admin_Dashobard(Request $request)
    {
        $page_title ="Surevy Dashboard";
        $SaveAsDraft = ParentSurvey::where('resort_id', $this->resort->resort_id)
                ->where('Status', 'SaveAsDraft')
                ->latest()
                ->limit(5)
                ->get()->map(function($a){ $a->Surevey_title = ucfirst($a->Surevey_title); 
                    $a->route =  route('Survey.view', base64_encode($a->id));

                    $a->Start_date = date('d-m-Y', strtotime($a->Start_date));
                    $a->End_date =  date('d-m-Y', strtotime($a->End_date));
                    
                    return $a;
                
                });
            
        $total_Survey_count = ParentSurvey::where('resort_id', $this->resort->resort_id)->count();
        $OngoingSurvey_count = ParentSurvey::where('Status','OnGoing')->where('resort_id', $this->resort->resort_id)->count();
        $PublishSurvey_count =ParentSurvey::whereIn('Status',['Publish','SaveAsDraft'])->where('resort_id', $this->resort->resort_id)->count();
        $CompleteSurvey_count =ParentSurvey::where('Status','Complete')->where('resort_id', $this->resort->resort_id)->count();
  
        // $OngoingSurvey = ParentSurvey::join('survey_employees as t1', 't1.Parent_survey_id', '=', 'parent_surveys.id')
        //                 ->where('parent_surveys.Status', 'OnGoing')
        //                 ->where('parent_surveys.resort_id', $this->resort->resort_id)
        //                 ->where('t1.emp_status','yes')
        //                 ->select(
        //                     'parent_surveys.id',
        //                     DB::raw('count(t1.id) as completed_count'),
        //                     DB::raw('(SELECT COUNT(*) FROM survey_employees WHERE Parent_survey_id = parent_surveys.id) as total_count')
        //                 )
        //                 ->groupBy('parent_surveys.id')
        //                 ->get();
        $OngoingSurvey = ParentSurvey::join('survey_employees as t1', 't1.Parent_survey_id', '=', 'parent_surveys.id')
                                    ->where('parent_surveys.Status', 'OnGoing')
                                    ->where('parent_surveys.resort_id', $this->resort->resort_id)
                                    ->select(
                                        'parent_surveys.id',
                                        'parent_surveys.Status',
                                        'parent_surveys.Surevey_title as title', // Survey title
                                        'parent_surveys.Start_date', // Creation date
                                        'parent_surveys.End_date', // Closing date
                                        DB::raw("SUM(CASE WHEN t1.emp_status = 'yes' THEN 1 ELSE 0 END) as completed_count"),
                                        DB::raw("SUM(CASE WHEN t1.emp_status = 'no' THEN 1 ELSE 0 END) as pending_count"),
                                        DB::raw("COUNT(t1.id) as total_count")
                                    )
                                    ->groupBy('parent_surveys.id')
                                    ->get();
    

                                    
              

        $NearingDeadline = ParentSurvey::join('survey_employees as t1', 't1.Parent_survey_id', '=', 'parent_surveys.id')
                                    ->where('parent_surveys.Status', 'OnGoing')
                                    ->where('parent_surveys.resort_id', $this->resort->resort_id)
                                    ->select(
                                        'parent_surveys.id',
                                        'parent_surveys.Status',
                                        'parent_surveys.Surevey_title as title', // Survey title
                                        'parent_surveys.Start_date', // Creation date
                                        'parent_surveys.End_date', // Closing date
                                        DB::raw("SUM(CASE WHEN t1.emp_status = 'yes' THEN 1 ELSE 0 END) as completed_count"),
                                        DB::raw("SUM(CASE WHEN t1.emp_status = 'no' THEN 1 ELSE 0 END) as pending_count"),
                                        DB::raw("COUNT(t1.id) as total_count")
                                    )
                                    ->where('t1.emp_status','no')

                                    ->groupBy('parent_surveys.id')
                                    ->whereIn('End_date',   $this->newdates) // 2 days before deadline
                                    ->get()
                                    
                                    ->map(function($a){ 
                                        $a->Newid=base64_encode($a->id);
                                        $a->startDate = carbon::parse($a->Start_date)->format('d M Y');
                                        $a->endDate = carbon::parse($a->End_date)->format('d M Y');
                                        return $a;
                                    });
                                  

                                    $i=0;
                            $colors = [                   
                                        '#014653',
                                        '#53CAFF',
                                        '#EFB408',
                                        '#2EACB3',
                                        '#333333',
                                        '#8DC9C9',
                                        '#7AD45A',
                                        '#FF4B4B',
                                        '#F5738D',  
                                        '#0E8509',
                                    ];
                       
            $departmentWise = ParentSurvey::join('survey_employees as t1', 't1.Parent_survey_id', '=', 'parent_surveys.id')
                                            ->join('employees as t2', 't2.id', '=', 't1.Emp_id')
                                            ->join('resort_departments as t3', 't3.id', '=', 't2.Dept_id')
                                            ->where('parent_surveys.Status', 'OnGoing')
                                            ->where('parent_surveys.resort_id', $this->resort->resort_id)
                                            ->where('t1.emp_status', 'yes') // Only count completed surveys
                                            ->select(
                                                'parent_surveys.Surevey_title',
                                                't3.name as Department_name',
                                                DB::raw("COUNT(t1.id) as completed_count") 
                                            )
                                            ->groupBy('t2.Dept_id') // Group by department
                                            ->get()
                                            ->map(function ($department) use (&$i, $colors) { // Pass $i by reference
                                                if ($i < count($colors)) {
                                                    $department->color = $colors[$i]; // Assign color if available
                                                }
                                                else 
                                                {
                                                    $i=0; 
                                                    $department->color = $colors[$i]; 
                                                }
                                                $i++; 
                                                return $department;
                                            });

        $SurveyWiseParticipationRates = ParentSurvey::join('survey_employees as t1', 't1.Parent_survey_id', '=', 'parent_surveys.id')
                                                    ->where('parent_surveys.Status', 'OnGoing')
                                                    ->where('parent_surveys.resort_id', $this->resort->resort_id)
                                                    ->select(
                                                        'parent_surveys.id',
                                                        'parent_surveys.Surevey_title as title', // Survey title
                                                        DB::raw("SUM(CASE WHEN t1.emp_status = 'yes' THEN 1 ELSE 0 END) as completed_count"),
                                                        DB::raw("SUM(CASE WHEN t1.emp_status = 'no' THEN 1 ELSE 0 END) as pending_count"),
                                                        DB::raw("COUNT(t1.id) as total_count")
                                                    )
                                                    ->groupBy('parent_surveys.id')
                                                    ->get();

        $ParticipationRate = ParentSurvey::join('survey_employees as t1', 't1.Parent_survey_id', '=', 'parent_surveys.id')
                                            ->where('parent_surveys.Status', 'OnGoing')
                                            ->where('parent_surveys.resort_id', $this->resort->resort_id)
                                            ->select(
                                                'parent_surveys.id',
                                                'parent_surveys.Surevey_title as title', // Survey title
                                                DB::raw("SUM(CASE WHEN t1.emp_status = 'yes' THEN 1 ELSE 0 END) as completed_count"),
                                                DB::raw("COUNT(t1.id) as total_count"),
                                                DB::raw("ROUND((SUM(CASE WHEN t1.emp_status = 'yes' THEN 1 ELSE 0 END) / COUNT(t1.id)) * 100, 2) as participation_rate") // Calculate %
                                            )
                                            ->groupBy('parent_surveys.resort_id')
                                            ->get();

        $SurveyComparison = ParentSurvey::join('survey_employees as t1', 't1.Parent_survey_id', '=', 'parent_surveys.id')
                                            ->where('parent_surveys.Status', 'OnGoing')
                                            ->where('parent_surveys.resort_id', $this->resort->resort_id)
                                            ->whereBetween('parent_surveys.Start_date', [
                                                now()->subMonths(2)->startOfMonth(),
                                                now()->endOfMonth()
                                            ]) // Fetch data for the last 2 months + current month
                                            ->select(
                                                'parent_surveys.id', // Add survey ID
                                                DB::raw("DATE_FORMAT(parent_surveys.Start_date, '%b %Y') as survey_month"), // Format month
                                                'parent_surveys.Surevey_title as title', // Survey title
                                                'parent_surveys.Surevey_title as survey_type', // Survey type (Engagement, Feedback, etc.)
                                                DB::raw("SUM(CASE WHEN t1.emp_status = 'yes' THEN 1 ELSE 0 END) as completed_count"), // Completed responses
                                                DB::raw("SUM(CASE WHEN t1.emp_status = 'no' THEN 1 ELSE 0 END) as pending_count"), // Pending responses
                                                DB::raw("COUNT(t1.id) as total_count") // Total survey participants
                                            )
                                            ->groupBy('survey_month', 'parent_surveys.Surevey_title', 'parent_surveys.id') // Group by survey type for stacked comparison
                                            ->get()
                                            ->map(function ($survey) use (&$i, $colors) { 
                                                if ($i < count($colors)) {
                                                    $survey->color = $colors[$i]; // Assign color if available
                                                } else {
                                                    $i = 0;  
                                                    $survey->color = $colors[$i]; 
                                                }
                                                $i++; 
                                                return $survey;
                                            });

         $RecentSurveyResults = ParentSurvey::join('survey_employees as t1', 't1.Parent_survey_id', '=', 'parent_surveys.id')
                                            ->where('parent_surveys.Status', 'Complete')
                                            ->where('parent_surveys.resort_id', $this->resort->resort_id)
                                            ->whereIn('parent_surveys.End_date', $this->olddates) // Ensure old dates array is valid
                                            ->select(
                                                'parent_surveys.id',
                                                'parent_surveys.Status',
                                                'parent_surveys.Surevey_title as title', // Survey title
                                                'parent_surveys.Start_date', // Creation date
                                                'parent_surveys.End_date', // Closing date
                                                DB::raw("SUM(CASE WHEN t1.emp_status = 'yes' THEN 1 ELSE 0 END) as completed_count"),
                                                DB::raw("SUM(CASE WHEN t1.emp_status = 'no' THEN 1 ELSE 0 END) as pending_count"),
                                                DB::raw("COUNT(t1.id) as total_count")
                                            )
                                            ->groupBy('parent_surveys.id', 'parent_surveys.Status', 'parent_surveys.Surevey_title', 'parent_surveys.Start_date', 'parent_surveys.End_date') // Include all selected columns in groupBy
                                            ->orderBy('parent_surveys.End_date', 'asc') // Sort to get the minimum surveys
                                            ->limit(5) // Get only the minimum 5 surveys
                                            ->get()
                                            ->map(function ($a) { 
                                                $a->Newid = base64_encode($a->id);
                                                $a->count = SurveyEmployee::where("Parent_survey_id",$a->id)->count();
                                                $a->startDate = \Carbon\Carbon::parse($a->Start_date)->format('d M Y');
                                                $a->endDate = \Carbon\Carbon::parse($a->End_date)->format('d M Y');
                                                return $a;
                                            });

        return view('resorts.Survey.dashboard.admindashboard',compact('page_title','RecentSurveyResults','SurveyComparison','ParticipationRate','SurveyWiseParticipationRates','departmentWise','NearingDeadline','OngoingSurvey','CompleteSurvey_count','PublishSurvey_count','OngoingSurvey_count','total_Survey_count','SaveAsDraft'));
    }
    public function HR_Dashobard(Request $request)
    {
        $page_title ="Surevy Dashboard";
        $SaveAsDraft = ParentSurvey::where('resort_id', $this->resort->resort_id)
                ->where('Status', 'SaveAsDraft')
                ->latest()
                ->limit(5)
                ->get()->map(function($a){ $a->Surevey_title = ucfirst($a->Surevey_title); 
                    $a->route =  route('Survey.view', base64_encode($a->id));

                    $a->Start_date = date('d-m-Y', strtotime($a->Start_date));
                    $a->End_date =  date('d-m-Y', strtotime($a->End_date));
                    
                    return $a;
                
                });
            
        $total_Survey_count = ParentSurvey::where('resort_id', $this->resort->resort_id)->count();
        $OngoingSurvey_count = ParentSurvey::where('Status','OnGoing')->where('resort_id', $this->resort->resort_id)->count();
        $PublishSurvey_count =ParentSurvey::whereIn('Status',['Publish','SaveAsDraft'])->where('resort_id', $this->resort->resort_id)->count();
        $CompleteSurvey_count =ParentSurvey::where('Status','Complete')->where('resort_id', $this->resort->resort_id)->count();
  
        // $OngoingSurvey = ParentSurvey::join('survey_employees as t1', 't1.Parent_survey_id', '=', 'parent_surveys.id')
        //                 ->where('parent_surveys.Status', 'OnGoing')
        //                 ->where('parent_surveys.resort_id', $this->resort->resort_id)
        //                 ->where('t1.emp_status','yes')
        //                 ->select(
        //                     'parent_surveys.id',
        //                     DB::raw('count(t1.id) as completed_count'),
        //                     DB::raw('(SELECT COUNT(*) FROM survey_employees WHERE Parent_survey_id = parent_surveys.id) as total_count')
        //                 )
        //                 ->groupBy('parent_surveys.id')
        //                 ->get();
        $OngoingSurvey = ParentSurvey::join('survey_employees as t1', 't1.Parent_survey_id', '=', 'parent_surveys.id')
                                    ->where('parent_surveys.Status', 'OnGoing')
                                    ->where('parent_surveys.resort_id', $this->resort->resort_id)
                                    ->select(
                                        'parent_surveys.id',
                                        'parent_surveys.Status',
                                        'parent_surveys.Surevey_title as title', // Survey title
                                        'parent_surveys.Start_date', // Creation date
                                        'parent_surveys.End_date', // Closing date
                                        DB::raw("SUM(CASE WHEN t1.emp_status = 'yes' THEN 1 ELSE 0 END) as completed_count"),
                                        DB::raw("SUM(CASE WHEN t1.emp_status = 'no' THEN 1 ELSE 0 END) as pending_count"),
                                        DB::raw("COUNT(t1.id) as total_count")
                                    )
                                    ->groupBy('parent_surveys.id')
                                    ->get();
    

                                    
              

        $NearingDeadline = ParentSurvey::join('survey_employees as t1', 't1.Parent_survey_id', '=', 'parent_surveys.id')
                                    ->where('parent_surveys.Status', 'OnGoing')
                                    ->where('parent_surveys.resort_id', $this->resort->resort_id)
                                    ->select(
                                        'parent_surveys.id',
                                        'parent_surveys.Status',
                                        'parent_surveys.Surevey_title as title', // Survey title
                                        'parent_surveys.Start_date', // Creation date
                                        'parent_surveys.End_date', // Closing date
                                        DB::raw("SUM(CASE WHEN t1.emp_status = 'yes' THEN 1 ELSE 0 END) as completed_count"),
                                        DB::raw("SUM(CASE WHEN t1.emp_status = 'no' THEN 1 ELSE 0 END) as pending_count"),
                                        DB::raw("COUNT(t1.id) as total_count")
                                    )
                                    ->where('t1.emp_status','no')

                                    ->groupBy('parent_surveys.id')
                                    ->whereIn('End_date',   $this->newdates) // 2 days before deadline
                                    ->get()
                                    
                                    ->map(function($a){ 
                                        $a->Newid=base64_encode($a->id);
                                        $a->startDate = carbon::parse($a->Start_date)->format('d M Y');
                                        $a->endDate = carbon::parse($a->End_date)->format('d M Y');
                                        return $a;
                                    });
                                  

                                    $i=0;
                            $colors = [                   
                                        '#014653',
                                        '#53CAFF',
                                        '#EFB408',
                                        '#2EACB3',
                                        '#333333',
                                        '#8DC9C9',
                                        '#7AD45A',
                                        '#FF4B4B',
                                        '#F5738D',  
                                        '#0E8509',
                                    ];
                       
            $departmentWise = ParentSurvey::join('survey_employees as t1', 't1.Parent_survey_id', '=', 'parent_surveys.id')
                                            ->join('employees as t2', 't2.id', '=', 't1.Emp_id')
                                            ->join('resort_departments as t3', 't3.id', '=', 't2.Dept_id')
                                            ->where('parent_surveys.Status', 'OnGoing')
                                            ->where('parent_surveys.resort_id', $this->resort->resort_id)
                                            ->where('t1.emp_status', 'yes') // Only count completed surveys
                                            ->select(
                                                'parent_surveys.Surevey_title',
                                                't3.name as Department_name',
                                                DB::raw("COUNT(t1.id) as completed_count") 
                                            )
                                            ->groupBy('t2.Dept_id') // Group by department
                                            ->get()
                                            ->map(function ($department) use (&$i, $colors) { // Pass $i by reference
                                                if ($i < count($colors)) {
                                                    $department->color = $colors[$i]; // Assign color if available
                                                }
                                                else 
                                                {
                                                    $i=0; 
                                                    $department->color = $colors[$i]; 
                                                }
                                                $i++; 
                                                return $department;
                                            });

        $SurveyWiseParticipationRates = ParentSurvey::join('survey_employees as t1', 't1.Parent_survey_id', '=', 'parent_surveys.id')
                                                    ->where('parent_surveys.Status', 'OnGoing')
                                                    ->where('parent_surveys.resort_id', $this->resort->resort_id)
                                                    ->select(
                                                        'parent_surveys.id',
                                                        'parent_surveys.Surevey_title as title', // Survey title
                                                        DB::raw("SUM(CASE WHEN t1.emp_status = 'yes' THEN 1 ELSE 0 END) as completed_count"),
                                                        DB::raw("SUM(CASE WHEN t1.emp_status = 'no' THEN 1 ELSE 0 END) as pending_count"),
                                                        DB::raw("COUNT(t1.id) as total_count")
                                                    )
                                                    ->groupBy('parent_surveys.id')
                                                    ->get();

        $ParticipationRate = ParentSurvey::join('survey_employees as t1', 't1.Parent_survey_id', '=', 'parent_surveys.id')
                                            ->where('parent_surveys.Status', 'OnGoing')
                                            ->where('parent_surveys.resort_id', $this->resort->resort_id)
                                            ->select(
                                                'parent_surveys.id',
                                                'parent_surveys.Surevey_title as title', // Survey title
                                                DB::raw("SUM(CASE WHEN t1.emp_status = 'yes' THEN 1 ELSE 0 END) as completed_count"),
                                                DB::raw("COUNT(t1.id) as total_count"),
                                                DB::raw("ROUND((SUM(CASE WHEN t1.emp_status = 'yes' THEN 1 ELSE 0 END) / COUNT(t1.id)) * 100, 2) as participation_rate") // Calculate %
                                            )
                                            ->groupBy('parent_surveys.resort_id')
                                            ->get();

        $SurveyComparison = ParentSurvey::join('survey_employees as t1', 't1.Parent_survey_id', '=', 'parent_surveys.id')
                                            ->where('parent_surveys.Status', 'OnGoing')
                                            ->where('parent_surveys.resort_id', $this->resort->resort_id)
                                            ->whereBetween('parent_surveys.Start_date', [
                                                now()->subMonths(2)->startOfMonth(),
                                                now()->endOfMonth()
                                            ]) // Fetch data for the last 2 months + current month
                                            ->select(
                                                'parent_surveys.id', // Add survey ID
                                                DB::raw("DATE_FORMAT(parent_surveys.Start_date, '%b %Y') as survey_month"), // Format month
                                                'parent_surveys.Surevey_title as title', // Survey title
                                                'parent_surveys.Surevey_title as survey_type', // Survey type (Engagement, Feedback, etc.)
                                                DB::raw("SUM(CASE WHEN t1.emp_status = 'yes' THEN 1 ELSE 0 END) as completed_count"), // Completed responses
                                                DB::raw("SUM(CASE WHEN t1.emp_status = 'no' THEN 1 ELSE 0 END) as pending_count"), // Pending responses
                                                DB::raw("COUNT(t1.id) as total_count") // Total survey participants
                                            )
                                            ->groupBy('survey_month', 'parent_surveys.Surevey_title', 'parent_surveys.id') // Group by survey type for stacked comparison
                                            ->get()
                                            ->map(function ($survey) use (&$i, $colors) { 
                                                if ($i < count($colors)) {
                                                    $survey->color = $colors[$i]; // Assign color if available
                                                } else {
                                                    $i = 0;  
                                                    $survey->color = $colors[$i]; 
                                                }
                                                $i++; 
                                                return $survey;
                                            });

         $RecentSurveyResults = ParentSurvey::join('survey_employees as t1', 't1.Parent_survey_id', '=', 'parent_surveys.id')
                                            ->where('parent_surveys.Status', 'Complete')
                                            ->where('parent_surveys.resort_id', $this->resort->resort_id)
                                            ->whereIn('parent_surveys.End_date', $this->olddates) // Ensure old dates array is valid
                                            ->select(
                                                'parent_surveys.id',
                                                'parent_surveys.Status',
                                                'parent_surveys.Surevey_title as title', // Survey title
                                                'parent_surveys.Start_date', // Creation date
                                                'parent_surveys.End_date', // Closing date
                                                DB::raw("SUM(CASE WHEN t1.emp_status = 'yes' THEN 1 ELSE 0 END) as completed_count"),
                                                DB::raw("SUM(CASE WHEN t1.emp_status = 'no' THEN 1 ELSE 0 END) as pending_count"),
                                                DB::raw("COUNT(t1.id) as total_count")
                                            )
                                            ->groupBy('parent_surveys.id', 'parent_surveys.Status', 'parent_surveys.Surevey_title', 'parent_surveys.Start_date', 'parent_surveys.End_date') // Include all selected columns in groupBy
                                            ->orderBy('parent_surveys.End_date', 'asc') // Sort to get the minimum surveys
                                            ->limit(5) // Get only the minimum 5 surveys
                                            ->get()
                                            ->map(function ($a) { 
                                                $a->Newid = base64_encode($a->id);
                                                $a->count = SurveyEmployee::where("Parent_survey_id",$a->id)->count();
                                                $a->startDate = \Carbon\Carbon::parse($a->Start_date)->format('d M Y');
                                                $a->endDate = \Carbon\Carbon::parse($a->End_date)->format('d M Y');
                                                return $a;
                                            });

        return view('resorts.Survey.dashboard.hrdashboard',compact('page_title','RecentSurveyResults','SurveyComparison','ParticipationRate','SurveyWiseParticipationRates','departmentWise','NearingDeadline','OngoingSurvey','CompleteSurvey_count','PublishSurvey_count','OngoingSurvey_count','total_Survey_count','SaveAsDraft'));
    }

   

}
