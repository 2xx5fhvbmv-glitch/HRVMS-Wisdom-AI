<?php

namespace App\Http\Controllers\Resorts\Survey;

use DB;
use Carbon\Carbon;
use App\Helpers\Common;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ParentSurvey;
use App\Models\SurveyEmployee;
use App\Models\ResortDepartment;

class DashboardController extends Controller
{
    protected $resort;
    protected $underEmp_id=[];
    protected $newdates =[];
    protected $olddates=[];
    public function __construct()
    {
        $this->resort = $resortId = auth()->guard('resort-admin')->user();
        if(!$this->resort) return;
        $reporting_to = isset($this->resort->GetEmployee) ? $this->resort->GetEmployee->id : 3;
        $this->newdates[]= Carbon::today()->format('Y-m-d');
        for($i=1; $i<=2; $i++)
        {
            $this->olddates[] =  Carbon::today()->subDays($i)->format('Y-m-d');
            $this->newdates[] =  Carbon::today()->addDays($i)->format('Y-m-d');
        }
    }

    /**
     * HR department (or Executive Office), HOD, and EXCOM see whole resort survey data.
     * Other users also see whole resort data; no department filter is applied on survey dashboard.
     */
    protected function canViewAllDepartments()
    {
        if (!isset($this->resort->GetEmployee)) {
            return true; // no employee link: show whole resort
        }

        $employee = $this->resort->GetEmployee;
        $rank     = $employee->rank ?? null;
        $deptId   = $employee->Dept_id ?? null;

        $department = $deptId
            ? ResortDepartment::where('id', $deptId)
                ->where('resort_id', $this->resort->resort_id)
                ->first(['name', 'code'])
            : null;

        $deptName = strtolower(trim($department->name ?? ''));
        $deptCode = $department->code ?? '';

        $isHRDept = in_array($deptName, ['human resources', 'hr']);
        $isEODept = ($deptCode === 'EO_1') || (strpos($deptName, 'executive office') !== false);

        $employeeRankPosition = Common::getEmployeeRankPosition($this->resort->GetEmployee);
        $positionName         = $employeeRankPosition['position'] ?? '';
        $rankName             = $employeeRankPosition['rank'] ?? '';

        $isGM    = ($positionName === 'GM') || ($rankName === 'GM');
        $isEXCOM = ($positionName === 'EXCOM') || ($rankName === 'EXCOM') || $rank == 1 || $rank === '1';
        $isHOD   = ($rank == 2 || $rank === '2');

        if (($isHRDept || $isEODept) && ($isGM || $isEXCOM || $isHOD)) {
            return true;
        }

        // Department HOD (rank 2 in non-HR dept) also sees whole resort data on survey dashboard
        if ($isHOD) {
            return true;
        }

        return false;
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

                    $a->Start_date = date('d M Y', strtotime($a->Start_date));
                    $a->End_date =  date('d M Y', strtotime($a->End_date));
                    
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
                       
            // Aggregate by DEPARTMENT only (was grouping by survey_title too,
            // which produced one row per dept-per-survey and made the doughnut
            // legend show duplicate department pills).
            $departmentWise = ParentSurvey::join('survey_employees as t1', 't1.Parent_survey_id', '=', 'parent_surveys.id')
                                            ->join('employees as t2', 't2.id', '=', 't1.Emp_id')
                                            ->join('resort_departments as t3', 't3.id', '=', 't2.Dept_id')
                                            ->whereIn('parent_surveys.Status', ['Publish', 'OnGoing'])
                                            ->where('parent_surveys.resort_id', $this->resort->resort_id)
                                            ->where('t1.emp_status', 'yes')
                                            ->select(
                                                't3.name as Department_name',
                                                DB::raw("COUNT(t1.id) as completed_count")
                                            )
                                            ->groupBy('t2.Dept_id', 't3.name')
                                            ->get()
                                            ->map(function ($department) use (&$i, $colors) {
                                                if ($i >= count($colors)) { $i = 0; }
                                                $department->color = $colors[$i];
                                                $i++;
                                                return $department;
                                            });
            // Reset color cursor so the next chart starts from #014653 again.
            $i = 0;

        $SurveyWiseParticipationRates = ParentSurvey::join('survey_employees as t1', 't1.Parent_survey_id', '=', 'parent_surveys.id')
                                                    ->whereIn('parent_surveys.Status', ['Publish', 'OnGoing'])
                                                    ->where('parent_surveys.resort_id', $this->resort->resort_id)
                                                    ->select(
                                                        'parent_surveys.id',
                                                        'parent_surveys.Surevey_title as title', // Survey title
                                                        DB::raw("SUM(CASE WHEN t1.emp_status = 'yes' THEN 1 ELSE 0 END) as completed_count"),
                                                        DB::raw("SUM(CASE WHEN t1.emp_status = 'no' THEN 1 ELSE 0 END) as pending_count"),
                                                        DB::raw("COUNT(t1.id) as total_count")
                                                    )
                                                    ->groupBy('parent_surveys.id', 'parent_surveys.Surevey_title')
                                                    ->get();

        $ParticipationRate = ParentSurvey::join('survey_employees as t1', 't1.Parent_survey_id', '=', 'parent_surveys.id')
                                            ->whereIn('parent_surveys.Status', ['Publish', 'OnGoing'])
                                            ->where('parent_surveys.resort_id', $this->resort->resort_id)
                                            ->select(
                                                'parent_surveys.resort_id',
                                                DB::raw("SUM(CASE WHEN t1.emp_status = 'yes' THEN 1 ELSE 0 END) as completed_count"),
                                                DB::raw("COUNT(t1.id) as total_count"),
                                                DB::raw("ROUND((SUM(CASE WHEN t1.emp_status = 'yes' THEN 1 ELSE 0 END) / NULLIF(COUNT(t1.id), 0)) * 100, 2) as participation_rate")
                                            )
                                            ->groupBy('parent_surveys.resort_id')
                                            ->get();

        $SurveyComparison = ParentSurvey::join('survey_employees as t1', 't1.Parent_survey_id', '=', 'parent_surveys.id')
                                            ->whereIn('parent_surveys.Status', ['Publish', 'OnGoing'])
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
                                            ->select(
                                                'parent_surveys.id',
                                                'parent_surveys.Status',
                                                'parent_surveys.Surevey_title as title',
                                                'parent_surveys.Start_date',
                                                'parent_surveys.End_date',
                                                DB::raw("SUM(CASE WHEN t1.emp_status = 'yes' THEN 1 ELSE 0 END) as completed_count"),
                                                DB::raw("SUM(CASE WHEN t1.emp_status = 'no' THEN 1 ELSE 0 END) as pending_count"),
                                                DB::raw("COUNT(t1.id) as total_count")
                                            )
                                            ->groupBy('parent_surveys.id', 'parent_surveys.Status', 'parent_surveys.Surevey_title', 'parent_surveys.Start_date', 'parent_surveys.End_date')
                                            ->orderBy('parent_surveys.End_date', 'desc')
                                            ->limit(5)
                                            ->get()
                                            ->map(function ($a) { 
                                                $a->Newid = base64_encode($a->id);
                                                // Avoid N+1 — the SELECT above already aggregates total_count.
                                                $a->count = (int) ($a->total_count ?? 0);
                                                $a->startDate = \Carbon\Carbon::parse($a->Start_date)->format('d M Y');
                                                $a->endDate = \Carbon\Carbon::parse($a->End_date)->format('d M Y');
                                                return $a;
                                            });

        return view('resorts.Survey.dashboard.admindashboard',compact('page_title','RecentSurveyResults','SurveyComparison','ParticipationRate','SurveyWiseParticipationRates','departmentWise','NearingDeadline','OngoingSurvey','CompleteSurvey_count','PublishSurvey_count','OngoingSurvey_count','total_Survey_count','SaveAsDraft'));
    }
    public function HR_Dashobard(Request $request)
    {
        $page_title ="Survey Dashboard";
        $SaveAsDraft = ParentSurvey::where('resort_id', $this->resort->resort_id)
                ->where('Status', 'SaveAsDraft')
                ->latest()
                ->limit(5)
                ->get()->map(function($a){ $a->Surevey_title = ucfirst($a->Surevey_title);
                    // Drafts go to the edit form (resume editing) — the read-only
                    // view doesn't make sense for unfinished work.
                    $a->route =  route('Survey.edit', base64_encode($a->id));

                    $a->Start_date = date('d M Y', strtotime($a->Start_date));
                    $a->End_date =  date('d M Y', strtotime($a->End_date));

                    return $a;

                });
            
        $total_Survey_count = ParentSurvey::where('resort_id', $this->resort->resort_id)->count();
        $OngoingSurvey_count = ParentSurvey::whereIn('Status', ['Publish', 'OnGoing'])->where('resort_id', $this->resort->resort_id)->count();
        $DraftSurvey_count = ParentSurvey::where('Status', 'SaveAsDraft')->where('resort_id', $this->resort->resort_id)->count();
        $CompleteSurvey_count = ParentSurvey::where('Status','Complete')->where('resort_id', $this->resort->resort_id)->count();
  
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
        // Feeds the "Survey Status" list on the dashboard (not just ongoing
        // ones despite the variable name) — was excluding 'Complete'
        // entirely, so an expired survey never appeared here even though
        // the summary count card right above it (CompleteSurvey_count)
        // correctly counted it. The blade's badge logic already handles a
        // 'Complete' status value, it just never received one.
        $OngoingSurvey = ParentSurvey::leftJoin('survey_employees as t1', 't1.Parent_survey_id', '=', 'parent_surveys.id')
                                    ->whereIn('parent_surveys.Status', ['Publish', 'OnGoing', 'Complete'])
                                    ->where('parent_surveys.resort_id', $this->resort->resort_id)
                                    ->select(
                                        'parent_surveys.id',
                                        'parent_surveys.Status',
                                        'parent_surveys.Surevey_title as title',
                                        'parent_surveys.Start_date',
                                        'parent_surveys.End_date',
                                        DB::raw("COALESCE(SUM(CASE WHEN t1.emp_status = 'yes' THEN 1 ELSE 0 END), 0) as completed_count"),
                                        DB::raw("COALESCE(SUM(CASE WHEN t1.emp_status = 'no' THEN 1 ELSE 0 END), 0) as pending_count"),
                                        DB::raw("COUNT(t1.id) as total_count")
                                    )
                                    ->groupBy('parent_surveys.id', 'parent_surveys.Status', 'parent_surveys.Surevey_title', 'parent_surveys.Start_date', 'parent_surveys.End_date')
                                    ->get();
    

                                    
              

        // Same fix as SurveyController@Getneartodeadlinesurvey — was
        // whereBetween(today, today+3), which excluded an already-overdue
        // survey (still Publish/OnGoing) entirely, even with pending
        // participants. Only the upper "nearing" bound is meaningful here.
        $deadlineEnd = Carbon::today()->addDays(3)->format('Y-m-d');
        $NearingDeadline = ParentSurvey::join('survey_employees as t1', 't1.Parent_survey_id', '=', 'parent_surveys.id')
                                    ->whereIn('parent_surveys.Status', ['Publish', 'OnGoing'])
                                    ->where('parent_surveys.resort_id', $this->resort->resort_id)
                                    ->where('parent_surveys.End_date', '<=', $deadlineEnd)
                                    ->select(
                                        'parent_surveys.id',
                                        'parent_surveys.Status',
                                        'parent_surveys.Surevey_title as title',
                                        'parent_surveys.Start_date',
                                        'parent_surveys.End_date',
                                        DB::raw("SUM(CASE WHEN t1.emp_status = 'yes' THEN 1 ELSE 0 END) as completed_count"),
                                        DB::raw("SUM(CASE WHEN t1.emp_status = 'no' THEN 1 ELSE 0 END) as pending_count"),
                                        DB::raw("COUNT(t1.id) as total_count")
                                    )
                                    ->groupBy('parent_surveys.id', 'parent_surveys.Status', 'parent_surveys.Surevey_title', 'parent_surveys.Start_date', 'parent_surveys.End_date')
                                    ->havingRaw("SUM(CASE WHEN t1.emp_status = 'no' THEN 1 ELSE 0 END) > 0")
                                    ->get()
                                    ->map(function($a){
                                        $a->Newid = base64_encode($a->id);
                                        $a->startDate = Carbon::parse($a->Start_date)->format('d M Y');
                                        $a->endDate = Carbon::parse($a->End_date)->format('d M Y');
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
                       
            // Aggregate by DEPARTMENT only (was grouping by survey_title too,
            // which produced one row per dept-per-survey and made the doughnut
            // legend show duplicate department pills).
            $departmentWise = ParentSurvey::join('survey_employees as t1', 't1.Parent_survey_id', '=', 'parent_surveys.id')
                                            ->join('employees as t2', 't2.id', '=', 't1.Emp_id')
                                            ->join('resort_departments as t3', 't3.id', '=', 't2.Dept_id')
                                            ->whereIn('parent_surveys.Status', ['Publish', 'OnGoing'])
                                            ->where('parent_surveys.resort_id', $this->resort->resort_id)
                                            ->where('t1.emp_status', 'yes')
                                            ->select(
                                                't3.name as Department_name',
                                                DB::raw("COUNT(t1.id) as completed_count")
                                            )
                                            ->groupBy('t2.Dept_id', 't3.name')
                                            ->get()
                                            ->map(function ($department) use (&$i, $colors) {
                                                if ($i >= count($colors)) { $i = 0; }
                                                $department->color = $colors[$i];
                                                $i++;
                                                return $department;
                                            });
            // Reset color cursor so the next chart starts from #014653 again.
            $i = 0;

        $SurveyWiseParticipationRates = ParentSurvey::join('survey_employees as t1', 't1.Parent_survey_id', '=', 'parent_surveys.id')
                                                    ->whereIn('parent_surveys.Status', ['Publish', 'OnGoing'])
                                                    ->where('parent_surveys.resort_id', $this->resort->resort_id)
                                                    ->select(
                                                        'parent_surveys.id',
                                                        'parent_surveys.Surevey_title as title', // Survey title
                                                        DB::raw("SUM(CASE WHEN t1.emp_status = 'yes' THEN 1 ELSE 0 END) as completed_count"),
                                                        DB::raw("SUM(CASE WHEN t1.emp_status = 'no' THEN 1 ELSE 0 END) as pending_count"),
                                                        DB::raw("COUNT(t1.id) as total_count")
                                                    )
                                                    ->groupBy('parent_surveys.id', 'parent_surveys.Surevey_title')
                                                    ->get();

        $ParticipationRate = ParentSurvey::join('survey_employees as t1', 't1.Parent_survey_id', '=', 'parent_surveys.id')
                                            ->whereIn('parent_surveys.Status', ['Publish', 'OnGoing'])
                                            ->where('parent_surveys.resort_id', $this->resort->resort_id)
                                            ->select(
                                                'parent_surveys.resort_id',
                                                DB::raw("SUM(CASE WHEN t1.emp_status = 'yes' THEN 1 ELSE 0 END) as completed_count"),
                                                DB::raw("COUNT(t1.id) as total_count"),
                                                DB::raw("ROUND((SUM(CASE WHEN t1.emp_status = 'yes' THEN 1 ELSE 0 END) / NULLIF(COUNT(t1.id), 0)) * 100, 2) as participation_rate")
                                            )
                                            ->groupBy('parent_surveys.resort_id')
                                            ->get();

        $SurveyComparison = ParentSurvey::join('survey_employees as t1', 't1.Parent_survey_id', '=', 'parent_surveys.id')
                                            ->whereIn('parent_surveys.Status', ['Publish', 'OnGoing'])
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
                                            ->select(
                                                'parent_surveys.id',
                                                'parent_surveys.Status',
                                                'parent_surveys.Surevey_title as title',
                                                'parent_surveys.Start_date',
                                                'parent_surveys.End_date',
                                                DB::raw("SUM(CASE WHEN t1.emp_status = 'yes' THEN 1 ELSE 0 END) as completed_count"),
                                                DB::raw("SUM(CASE WHEN t1.emp_status = 'no' THEN 1 ELSE 0 END) as pending_count"),
                                                DB::raw("COUNT(t1.id) as total_count")
                                            )
                                            ->groupBy('parent_surveys.id', 'parent_surveys.Status', 'parent_surveys.Surevey_title', 'parent_surveys.Start_date', 'parent_surveys.End_date')
                                            ->orderBy('parent_surveys.End_date', 'desc')
                                            ->limit(5)
                                            ->get()
                                            ->map(function ($a) { 
                                                $a->Newid = base64_encode($a->id);
                                                // Avoid N+1 — the SELECT above already aggregates total_count.
                                                $a->count = (int) ($a->total_count ?? 0);
                                                $a->startDate = \Carbon\Carbon::parse($a->Start_date)->format('d M Y');
                                                $a->endDate = \Carbon\Carbon::parse($a->End_date)->format('d M Y');
                                                return $a;
                                            });

        $surveyInsights = $this->getCachedSurveyInsights($this->resort->resort_id);

        return view('resorts.Survey.dashboard.hrdashboard',compact('page_title','RecentSurveyResults','SurveyComparison','ParticipationRate','SurveyWiseParticipationRates','departmentWise','NearingDeadline','OngoingSurvey','CompleteSurvey_count','DraftSurvey_count','OngoingSurvey_count','total_Survey_count','SaveAsDraft','surveyInsights'));
    }

    /**
     * Cached wrapper around buildSurveyInsights() with a manual 2-day refresh,
     * mirroring the other module dashboards. Cached per resort; "Regenerate"
     * loads ?regenerate_insights=1 and recomputes only once the 48h cooldown has
     * elapsed. Returns a '_meta' key for the card header.
     */
    private function getCachedSurveyInsights($resortId): array
    {
        $cooldownHours = 48;
        $cacheKey = 'survey_insights:' . $resortId;
        $now = \Carbon\Carbon::now();

        $cached = \Cache::get($cacheKey);
        $regenerate = request()->boolean('regenerate_insights');

        $stale = !is_array($cached) || empty($cached['generated_at']);
        if (!$stale) {
            $generatedAt = \Carbon\Carbon::parse($cached['generated_at']);
            if ($regenerate && $generatedAt->diffInHours($now) >= $cooldownHours) {
                $stale = true;
            }
        }

        if ($stale) {
            $cached = [
                'insights'     => $this->buildSurveyInsights($resortId),
                'generated_at' => $now->toIso8601String(),
            ];
            \Cache::put($cacheKey, $cached, $now->copy()->addDays(30));
        }

        $generatedAt = \Carbon\Carbon::parse($cached['generated_at']);
        $insights = $cached['insights'];
        $insights['_meta'] = [
            'generated_at'   => $generatedAt,
            'can_regenerate' => $generatedAt->diffInHours($now) >= $cooldownHours,
            'next_available' => $generatedAt->copy()->addHours($cooldownHours),
        ];

        return $insights;
    }

    /**
     * Compute the four Survey AI-insight cards for a resort:
     *   1. participation  Invited vs responded, overall response rate, lagging surveys
     *   2. activity       Survey status mix, active/closing-soon, recurring cadence
     *   3. sentiment      Avg rating + favourable/neutral/unfavourable split (Rating Qs)
     *   4. hotspots       Top choice answers + questions with most negative responses
     * Each card is wrapped so one failing query degrades just that card; the
     * deterministic numbers are then narrated by the FastAPI LLM (best-effort).
     */
    private function buildSurveyInsights($resortId): array
    {
        $insights = [
            'participation' => ['title' => 'Participation & Response Rate', 'body' => 'No surveys with recipients yet.'],
            'activity'      => ['title' => 'Survey Activity & Status',      'body' => 'No surveys created yet.'],
            'sentiment'     => ['title' => 'Sentiment / Score Pulse',       'body' => 'No rating responses yet to analyse.'],
            'hotspots'      => ['title' => 'Answer Hotspots',               'body' => 'No choice responses yet to analyse.'],
        ];
        $today = \Carbon\Carbon::now();

        // --- Card 1: Participation & response rate ------------------------------
        try {
            $surveys = \DB::table('parent_surveys')->where('resort_id', $resortId)->get(['id', 'Surevey_title']);
            if ($surveys->isNotEmpty()) {
                $invitedBy = \DB::table('survey_employees')->whereIn('Parent_survey_id', $surveys->pluck('id'))
                    ->select('Parent_survey_id', \DB::raw('COUNT(*) as c'))->groupBy('Parent_survey_id')->pluck('c', 'Parent_survey_id');
                $respondedBy = \DB::table('survey_employees')->whereIn('Parent_survey_id', $surveys->pluck('id'))
                    ->whereNotNull('Complete_time')
                    ->select('Parent_survey_id', \DB::raw('COUNT(*) as c'))->groupBy('Parent_survey_id')->pluck('c', 'Parent_survey_id');
                $totInvited = 0; $totResponded = 0; $rows = [];
                foreach ($surveys as $s) {
                    $inv = (int) ($invitedBy[$s->id] ?? 0);
                    if ($inv === 0) continue;
                    $resp = (int) ($respondedBy[$s->id] ?? 0);
                    $totInvited += $inv; $totResponded += $resp;
                    $rows[] = ['survey' => $s->Surevey_title ?: 'Untitled', 'invited' => $inv, 'responded' => $resp,
                               'rate' => round($resp / $inv * 100, 1)];
                }
                if ($totInvited > 0) {
                    $overall = round($totResponded / $totInvited * 100, 1);
                    usort($rows, fn ($a, $b) => $a['rate'] <=> $b['rate']);
                    $lowest = $rows[0];
                    $insights['participation']['body'] = $overall . '% response rate (' . $totResponded . ' of ' . $totInvited . ' across ' . count($rows) . ' surveys); lowest: "' . $lowest['survey'] . '" at ' . $lowest['rate'] . '%.';
                    $insights['participation']['details'] = ['overall' => $overall, 'invited' => $totInvited, 'responded' => $totResponded, 'rows' => array_slice($rows, 0, 15)];
                }
            }
        } catch (\Throwable $e) {}

        // --- Card 2: Survey activity & status ----------------------------------
        try {
            $byStatus = \DB::table('parent_surveys')->where('resort_id', $resortId)
                ->select('Status', \DB::raw('COUNT(*) as c'))->groupBy('Status')->pluck('c', 'Status');
            $total = (int) $byStatus->sum();
            if ($total > 0) {
                $active = \DB::table('parent_surveys')->where('resort_id', $resortId)
                    ->whereIn('Status', ['Publish', 'OnGoing'])
                    ->whereDate('End_date', '>=', $today->toDateString())->count();
                $closingSoon = \DB::table('parent_surveys')->where('resort_id', $resortId)
                    ->whereIn('Status', ['Publish', 'OnGoing'])
                    ->whereDate('End_date', '>=', $today->toDateString())
                    ->whereDate('End_date', '<=', $today->copy()->addDays(7)->toDateString())->count();
                $byCadence = \DB::table('parent_surveys')->where('resort_id', $resortId)
                    ->select('Recurring_survey', \DB::raw('COUNT(*) as c'))->groupBy('Recurring_survey')->pluck('c', 'Recurring_survey');
                $published = (int) ($byStatus['Publish'] ?? 0); $draft = (int) ($byStatus['SaveAsDraft'] ?? 0);
                $complete = (int) ($byStatus['Complete'] ?? 0);
                $insights['activity']['body'] = $total . ' surveys: ' . $published . ' published, ' . $draft . ' draft, ' . $complete . ' complete; ' . $active . ' active, ' . $closingSoon . ' closing within 7 days.';
                $insights['activity']['details'] = [
                    'total' => $total, 'by_status' => $byStatus->toArray(), 'active' => $active,
                    'closing_soon' => $closingSoon, 'cadence' => $byCadence->toArray(),
                ];
            }
        } catch (\Throwable $e) {}

        // --- Card 3: Sentiment / score pulse (Rating questions) ----------------
        try {
            $ratings = \DB::table('survey_results as r')
                ->join('survey_questions as q', 'q.id', '=', 'r.Question_id')
                ->join('parent_surveys as p', 'p.id', '=', 'r.Parent_survey_id')
                ->where('p.resort_id', $resortId)->where('q.Question_Type', 'Rating')
                ->whereRaw("r.Emp_Ans REGEXP '^[0-9]+(\\\\.[0-9]+)?$'")
                ->select('r.Emp_Ans', 'p.Surevey_title')->get();
            if ($ratings->isNotEmpty()) {
                $vals = []; $perSurvey = [];
                foreach ($ratings as $r) {
                    $v = (float) $r->Emp_Ans; $vals[] = $v;
                    $perSurvey[$r->Surevey_title ?: 'Untitled'][] = $v;
                }
                $avg = round(array_sum($vals) / count($vals), 2);
                $fav = count(array_filter($vals, fn ($v) => $v >= 4));
                $neu = count(array_filter($vals, fn ($v) => $v >= 3 && $v < 4));
                $unfav = count(array_filter($vals, fn ($v) => $v < 3));
                $favPct = round($fav / count($vals) * 100, 1);
                $surveyAvgs = [];
                foreach ($perSurvey as $title => $vs) $surveyAvgs[] = ['survey' => $title, 'avg' => round(array_sum($vs) / count($vs), 2), 'responses' => count($vs)];
                usort($surveyAvgs, fn ($a, $b) => $b['avg'] <=> $a['avg']);
                $best = $surveyAvgs[0] ?? null; $worst = end($surveyAvgs) ?: null;
                $bw = ($best && $worst && $best['survey'] !== $worst['survey']) ? ' Best: "' . $best['survey'] . '" (' . $best['avg'] . '); worst: "' . $worst['survey'] . '" (' . $worst['avg'] . ').' : '';
                $insights['sentiment']['body'] = 'Avg rating ' . $avg . '/5 across ' . count($vals) . ' responses; ' . $favPct . '% favourable.' . $bw;
                $insights['sentiment']['details'] = [
                    'avg' => $avg, 'favourable' => $fav, 'neutral' => $neu, 'unfavourable' => $unfav, 'total' => count($vals),
                    'surveys' => $surveyAvgs,
                ];
            }
        } catch (\Throwable $e) {}

        // --- Card 4: Answer hotspots (choice questions) ------------------------
        try {
            $choices = \DB::table('survey_results as r')
                ->join('survey_questions as q', 'q.id', '=', 'r.Question_id')
                ->join('parent_surveys as p', 'p.id', '=', 'r.Parent_survey_id')
                ->where('p.resort_id', $resortId)->whereIn('q.Question_Type', ['Single-Choice', 'Multi-Choice'])
                ->whereNotNull('r.Emp_Ans')->where('r.Emp_Ans', '!=', '')
                ->select('r.Emp_Ans', \DB::raw('COUNT(*) as c'))->groupBy('r.Emp_Ans')
                ->orderByDesc('c')->limit(15)->get();
            if ($choices->isNotEmpty()) {
                $rows = $choices->map(fn ($r) => ['answer' => $r->Emp_Ans, 'count' => (int) $r->c])->all();
                $top = $rows[0];
                $insights['hotspots']['body'] = 'Most common answer: "' . $top['answer'] . '" (' . $top['count'] . ') across ' . count($rows) . ' answer options.';
                $insights['hotspots']['details'] = ['rows' => $rows];
            }
        } catch (\Throwable $e) {}

        // Narrate the deterministic numbers via the FastAPI LLM (best-effort).
        $insights = \App\Helpers\Common::enrichDashboardInsights(
            $insights, 'employee survey & engagement', ['participation', 'activity', 'sentiment', 'hotspots']
        );

        return $insights;
    }

   

}
