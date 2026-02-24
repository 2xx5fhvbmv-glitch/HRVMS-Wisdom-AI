<?php

namespace App\Http\Controllers\Resorts;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\ResortDivision;
use App\Models\ResortDepartment;
use App\Models\ResortSection;
use App\Models\ResortPosition;
use App\Helpers\Common;
use Carbon\Carbon;
use DB;
use App\Models\ResortLanguages;
use App\Models\ResortAdmin;
use App\Models\Vacancies;
use App\Models\TAnotificationChild;
use App\Models\TAnotificationParent;
use App\Models\ApplicationLink;
use App\Models\Applicant_form_data;
use App\Models\ApplicantInterViewDetails;
use App\Models\HiringSource;
use App\Models\TaEmailTemplate;
use App\Models\ManningResponse;
use App\Models\PositionMonthlyData;
use URL;
class TalentAcquisitionDashboardController extends Controller
{
    public $globalUser='';
    public function __construct()
    {
        $this->globalUser = Auth::guard('resort-admin')->user();
        if(!$this->globalUser) return;
    }
    public function admin_dashboard()
    {

            $page_title ='Talent Acquisition Dashboard';
            $page_header = '<span class="arca-font">My</span> Dashboard';
            $currentYear = date('Y');
            $nextYear = $currentYear + 1;
            $resort_id = $this->globalUser->resort_id;
            $resort_divisions = ResortDivision::where('status','active')->where('resort_id',$resort_id)->get();
            $resort_departments = ResortDepartment::where('status','active')->where('resort_id',$resort_id)->get();
            $resort_positions = ResortPosition::where('status','active')->where('resort_id',$resort_id)->get();
            $config = config('settings.Position_Rank');
            // $rank = $this->globalUser->GetEmployee->rank;
            $rank = 3;
            $Vacancies = Common::GetTheFreshVacancies($resort_id,"Active",$rank);

            $resort_id_decode =  base64_encode($resort_id);
            $applicant_link = route('resort.applicantForm',$resort_id_decode);
            $applicationUrlshow = substr($applicant_link, 0, 30).'...';
            $TodoData = Common::GmApprovedVacancy($resort_id,$rank);
        
            $NewVacancies = Vacancies::join("resort_departments as t1", "t1.id", "=", "vacancies.department")
                            ->join("resort_positions as t2", "t2.id", "=", "vacancies.position")
                            ->join("t_anotification_parents as t3", "t3.V_id", "=", "vacancies.id")
                            ->join("t_anotification_children as t4", "t4.Parent_ta_id", "=", "t3.id")
                            ->join("application_links as t5", "t5.ta_child_id", "=", "t4.id")
                            ->leftJoin("applicant_form_data as t6", "t6.Parent_v_id", "=", "vacancies.id")
                            ->join('job_advertisements as t7', 't7.Resort_id', '=', 'vacancies.Resort_id')
                            ->where("vacancies.resort_id", $resort_id)
                            ->where("t4.status", "ForwardedToNext")
                            ->where("t4.Approved_By", Common::TaFinalApproval($resort_id))
                            ->selectRaw("
                                vacancies.id AS vacancy_id,
                                vacancies.position,
                                t2.position_title AS positionTitle,
                                t2.id AS PositionID,
                                t2.code AS PositionCode,
                                t1.name AS Department,
                                t1.code AS DepartmentCode,
                                COUNT(t6.id) AS NoOfApplication, -- Total applications per vacancy
                                MAX(t5.link_Expiry_date) AS LinkExpiryDate, -- Latest link expiry date
                                MAX(t6.Application_date) AS LatestApplicationDate, -- Latest application date
                                vacancies.Total_position_required as NoOfVacnacy,
                                t7.Jobadvimg
                            ")
                            ->groupBy(
                                "vacancies.id",
                                "vacancies.position",
                                "t2.position_title",
                                "t2.id",
                                "t2.code",
                                "t1.name",
                                "t1.code",
                                "vacancies.Total_position_required",
                                "t7.Jobadvimg"
                            )
                            ->get();

                            foreach($NewVacancies  as $v)
                            {

                                $v->positionTitle;
                                $v->PositonCode;
                                $v->Department;
                                $v->DepartmentCode;
                                $v->NoOfVacnacy = $v->NoOfVacnacy;
                                $v->NoOfApplication =  (isset($v->NoOfApplication))  ? $v->NoOfApplication: 0;
                                $v->ApplicationDate =  Carbon::parse($v->Application_date)->format('d-m-Y');
                                $v->ExpiryDate = Carbon::parse($v->link_Expiry_date)->format('d-m-Y');
                                $v->ApplicationId= $v->application_id;
                            }
            $talentPool  = Applicant_form_data::join('applicant_wise_statuses as t1', 't1.Applicant_id', '=', 'applicant_form_data.id')
                            ->whereIn("t1.status", ["Rejected","Rejected By Wisdom AI"])
                            ->latest('t1.created_at')
                            ->take('10')
                            ->get(['applicant_form_data.id','email','passport_photo','first_name','last_name','Comments']);

            $currentMonthStart = Carbon::now()->startOfMonth();
            $currentMonthEnd = Carbon::now()->endOfMonth();
            $TotalApplicants = Applicant_form_data::join('applicant_wise_statuses as t1', 't1.Applicant_id', '=', 'applicant_form_data.id')
                        ->where('t1.status', "!=",'Selected')
                        ->where('applicant_form_data.resort_id', $resort_id)
                        ->select(DB::raw('COUNT(DISTINCT t1.Applicant_id) as total_applicants') )
                        ->groupBy('t1.Applicant_id')
                        ->distinct()
                        ->get();
            $TotalApplicants = isset($TotalApplicants[0]) ? $TotalApplicants[0]->total_applicants : 0;
            $Interviews = ApplicantInterViewDetails::where('resort_id', $resort_id)->count();
            $Hired = DB::table('applicant_wise_statuses as t1')
                    ->join('applicant_form_data as t2', 't2.id', '=', 't1.Applicant_id')
                    ->where('t2.resort_id', $resort_id) // Reference resort_id from applicant_form_data
                    ->where("t1.As_ApprovedBy",8)
                    ->where("t1.status","Selected")
                    ->groupBy('t1.Applicant_id')
                    ->get()
                    ->count();
            // $positionId = $request->input('position_id'); // Fetch position filter from request
            $Resort_Position =  ResortPosition::where('Resort_id', $resort_id)->get();
            $HiringSource = HiringSource::where('Resort_id', $resort_id)
                        ->orderBy('id', 'desc')->get();
            $EmailTamplete = TaEmailTemplate::where('Resort_id',$resort_id)
                        ->orderByDesc("id")->get();


            return view('resorts.talentacquisition.dashboard.admindashboard',
                compact(
                    'applicationUrlshow',
                    'applicant_link',
                    'Hired',
                    'Interviews',
                    'resort_id_decode',
                    'TotalApplicants',
                    'resort_id',
                    'resort_divisions',
                    'resort_departments',
                    'resort_positions',
                    'Vacancies',
                    'TodoData',
                    'NewVacancies',
                    'talentPool',
                    'Resort_Position',
                    'HiringSource',
                    'EmailTamplete'
                )
            );
    //     } catch( \Exception $e ) {
    //         \Log::emergency("File: ".$e->getFile());
    //         \Log::emergency("Line: ".$e->getLine());
    //         \Log::emergency("Message: ".$e->getMessage());
    //         return view('resorts.talentacquisition.dashboard.admindashboard',compact('resort_id'));
    //     }
    }
    public function hr_dashboard()
    {
        // try {
            $page_title ='Talent Acquisition Dashboard';
            $page_header = '<span class="arca-font">My</span> Dashboard';
            $currentYear = date('Y');
            $nextYear = $currentYear + 1;
            $resort_id = $this->globalUser->resort_id;
            $resort_divisions = ResortDivision::where('status','active')->where('resort_id',$resort_id)->get();
            $resort_departments = ResortDepartment::where('status','active')->where('resort_id',$resort_id)->get();
            $resort_positions = ResortPosition::where('status','active')->where('resort_id',$resort_id)->get();
            $config = config('settings.Position_Rank');
            $rank = $this->globalUser->GetEmployee->rank ?? '';

            // Detect effective rank for users whose actual rank doesn't match their role
            // e.g. EXCOM Director of Finance → rank 7, EXCOM Director of HR → rank 3
            $effectiveRank = $rank;
            if (!in_array($rank, [3, 7, 8])) {
                $userDeptName = ResortDepartment::where('id', $this->globalUser->GetEmployee->Dept_id)->value('name');
                $userPositionTitle = $this->globalUser->GetEmployee->position->position_title ?? '';
                if (stripos($userDeptName, 'Accounting') !== false || stripos($userDeptName, 'Finance') !== false
                    || stripos($userPositionTitle, 'Finance') !== false) {
                    $effectiveRank = 7;
                } elseif (stripos($userDeptName, 'Human Resources') !== false || stripos($userPositionTitle, 'Human Resources') !== false) {
                    $effectiveRank = 3;
                }
            }
            $Vacancies = Common::GetTheFreshVacancies($resort_id,"Active",$effectiveRank);
            $resort_id_decode =  base64_encode($resort_id);
            $applicant_link = route('resort.applicantForm',$resort_id_decode);
            $applicationUrlshow = substr($applicant_link, 0, 30).'...';
            $TodoData = Common::GmApprovedVacancy($resort_id,$effectiveRank);
            
            $NewVacancies = Vacancies::join("resort_departments as t1", "t1.id", "=", "vacancies.department")
                            ->join("resort_positions as t2", "t2.id", "=", "vacancies.position")
                            ->join("t_anotification_parents as t3", "t3.V_id", "=", "vacancies.id")
                            ->join("t_anotification_children as t4", "t4.Parent_ta_id", "=", "t3.id")
                            ->join("application_links as t5", "t5.ta_child_id", "=", "t4.id")
                            ->leftJoin("applicant_form_data as t6", "t6.Parent_v_id", "=", "vacancies.id")
                            ->join('job_advertisements as t7', 't7.Resort_id', '=', 'vacancies.Resort_id')
                            ->where("vacancies.resort_id", $resort_id)
                            ->where("t4.status", "ForwardedToNext")
                            ->where("t4.Approved_By", Common::TaFinalApproval($resort_id))
                            ->selectRaw("
                                vacancies.id AS vacancy_id,
                                vacancies.position,
                                t2.position_title AS positionTitle,
                                t2.id AS PositionID,
                                t2.code AS PositionCode,
                                t1.name AS Department,
                                t1.code AS DepartmentCode,
                                COUNT(t6.id) AS NoOfApplication, -- Total applications per vacancy
                                MAX(t5.link_Expiry_date) AS LinkExpiryDate, -- Latest link expiry date
                                MAX(t6.Application_date) AS LatestApplicationDate, -- Latest application date
                                vacancies.Total_position_required as NoOfVacnacy,
                                t7.Jobadvimg
                            ")
                            ->groupBy(
                                "vacancies.id",
                                "vacancies.position",
                                "t2.position_title",
                                "t2.id",
                                "t2.code",
                                "t1.name",
                                "t1.code",
                                "vacancies.Total_position_required",
                                "t7.Jobadvimg"
                            )
                            ->get();
                            foreach($NewVacancies  as $v)
                            {
                                $v->positionTitle;
                                $v->PositonCode;
                                $v->Department;
                                $v->DepartmentCode;
                                $v->NoOfVacnacy = $v->NoOfVacnacy;
                                $v->NoOfApplication =  (isset($v->NoOfApplication))  ? $v->NoOfApplication: 0;
                                $v->ApplicationDate =  Carbon::parse($v->Application_date)->format('d-m-Y');
                                $v->ExpiryDate = Carbon::parse($v->link_Expiry_date)->format('d-m-Y');
                                $v->ApplicationId= $v->application_id;
                            }
            $talentPool  = Applicant_form_data::join('applicant_wise_statuses as t1', 't1.Applicant_id', '=', 'applicant_form_data.id')
                            ->whereIn("t1.status", ["Rejected","Rejected By Wisdom AI"])
                            ->latest('t1.created_at')
                            ->take('10')
                            ->get(['applicant_form_data.id','email','passport_photo','first_name','last_name','Comments']);
            $currentMonthStart = Carbon::now()->startOfMonth();
            $currentMonthEnd = Carbon::now()->endOfMonth();
            $TotalApplicants = Applicant_form_data::join('applicant_wise_statuses as t1', 't1.Applicant_id', '=', 'applicant_form_data.id')
                        ->where('t1.status', "!=",'Selected')
                        ->where('applicant_form_data.resort_id', $resort_id)
                        ->select(DB::raw('COUNT(DISTINCT t1.Applicant_id) as total_applicants') )
                        ->groupBy('t1.Applicant_id')
                        ->distinct()
                        ->get();
            if(isset($TotalApplicants[0]) && !empty($TotalApplicants[0]->total_applicants))
            {
                $TotalApplicants = $TotalApplicants[0]->total_applicants;
            } else {
                $TotalApplicants = 0;
            }
            $Interviews = ApplicantInterViewDetails::where('resort_id', $resort_id)->count();
            $Hired = DB::table('applicant_wise_statuses as t1')
                    ->join('applicant_form_data as t2', 't2.id', '=', 't1.Applicant_id')
                    ->where('t2.resort_id', $resort_id) // Reference resort_id from applicant_form_data
                    ->where("t1.As_ApprovedBy",8)
                    ->where("t1.status","Selected")
                    ->groupBy('t1.Applicant_id')
                    ->get()
                    ->count();

            $Resort_Position =  ResortPosition::where('Resort_id', $resort_id)->get();

            // Top Countries - applicants grouped by country
            $topCountries = DB::table('applicant_form_data')
                ->join('countries', 'countries.id', '=', 'applicant_form_data.country')
                ->join('vacancies', 'vacancies.id', '=', 'applicant_form_data.Parent_v_id')
                ->where('vacancies.Resort_id', $resort_id)
                ->selectRaw('countries.name as country, countries.flag_url, COUNT(applicant_form_data.id) as total_count')
                ->groupBy('countries.name', 'countries.flag_url')
                ->orderByDesc('total_count')
                ->limit(10)
                ->get();

            $HiringSource = HiringSource::where('Resort_id', $resort_id)
                        ->orderBy('id', 'desc')->get();

            $EmailTamplete = TaEmailTemplate::where('Resort_id',$resort_id)
                        ->orderByDesc("id")->get();

            // Approval History - recent approval/reject/hold actions
            $approvalHistory = DB::table('t_anotification_children as tc')
                ->join('t_anotification_parents as tp', 'tp.id', '=', 'tc.Parent_ta_id')
                ->join('vacancies as v', 'v.id', '=', 'tp.V_id')
                ->join('resort_positions as rp', 'rp.id', '=', 'v.position')
                ->join('resort_departments as rd', 'rd.id', '=', 'v.department')
                ->leftJoin('resort_admins as ra', 'ra.id', '=', 'tc.modified_by')
                ->where('tp.Resort_id', $resort_id)
                ->whereIn('tc.status', ['ForwardedToNext', 'Approved', 'Rejected', 'Hold'])
                ->select(
                    'tc.id as child_id',
                    'tc.status as action_status',
                    'tc.Approved_By',
                    'tc.reason',
                    DB::raw("CONCAT(ra.first_name, ' ', ra.last_name) as action_by"),
                    DB::raw('tc.updated_at as action_date'),
                    'rp.position_title',
                    'rd.name as department_name',
                    'v.Total_position_required'
                )
                ->orderByDesc('tc.updated_at')
                ->limit(10)
                ->get()
                ->map(function ($item) use ($config) {
                    $item->rank_name = $config[$item->Approved_By] ?? 'Unknown';
                    // Friendly action label
                    if ($item->action_status === 'ForwardedToNext') {
                        $item->action_label = 'Approved';
                        $item->badge_class = 'bg-success';
                    } elseif ($item->action_status === 'Approved') {
                        $item->action_label = 'Approved';
                        $item->badge_class = 'bg-success';
                    } elseif ($item->action_status === 'Rejected') {
                        $item->action_label = 'Rejected';
                        $item->badge_class = 'bg-danger';
                    } elseif ($item->action_status === 'Hold') {
                        $item->action_label = 'On Hold';
                        $item->badge_class = 'bg-warning';
                    } else {
                        $item->action_label = $item->action_status;
                        $item->badge_class = 'bg-secondary';
                    }
                    return $item;
                });

            return view('resorts.talentacquisition.dashboard.hrdashboard',
                compact(
                    'applicationUrlshow',
                    'applicant_link',
                    'Hired',
                    'Interviews',
                    'resort_id_decode',
                    'TotalApplicants',
                    'resort_id',
                    'resort_divisions',
                    'resort_departments',
                    'resort_positions',
                    'Vacancies',
                    'TodoData',
                    'NewVacancies',
                    'talentPool',
                    'Resort_Position',
                    'topCountries',
                    'HiringSource',
                    'EmailTamplete',
                    'approvalHistory'
                )
            );
        // } catch( \Exception $e ) {
        //     \Log::emergency("File: ".$e->getFile());
        //     \Log::emergency("Line: ".$e->getLine());
        //     \Log::emergency("Message: ".$e->getMessage());
        //     return view('resorts.talentacquisition.dashboard.hrdashboard',compact('resort_id'));
        // }
    }
    public function hod_dashboard()
    {
        try {
            $page_title = 'Talent Acquisition Dashboard';
            $page_header = '<span class="arca-font">My</span> Dashboard';
            $currentYear = date('Y');
            $nextYear = $currentYear + 1;
            $resort_id = $this->globalUser->resort_id;
            $Dept_id = $this->globalUser->GetEmployee->Dept_id;
            $userRank = $this->globalUser->GetEmployee->rank;
            $currentMonthStart = Carbon::now()->startOfMonth();
            $currentMonthEnd = Carbon::now()->endOfMonth();
            $config = config('settings.Position_Rank');

            // EXCOM (rank 1), GM (rank 8), and Finance/Accounting users see all departments
            $showAllDepts = in_array($userRank, [1, 8]);
            if (!$showAllDepts) {
                $userDeptName = ResortDepartment::where('id', $Dept_id)->value('name');
                $userPositionTitle = $this->globalUser->GetEmployee->position->position_title ?? '';
                if (stripos($userDeptName, 'Accounting') !== false || stripos($userDeptName, 'Finance') !== false
                    || stripos($userPositionTitle, 'Finance') !== false) {
                    $showAllDepts = true;
                }
            }

            // Fetch draft vacancies for the Drafts section
            $drafts = Vacancies::with(['Getdepartment', 'Getposition'])
                ->where('Resort_id', $resort_id)
                ->where('status', 'Draft')
                ->when(!$showAllDepts, function ($query) use ($Dept_id) {
                    $query->where('department', $Dept_id);
                })
                ->orderBy('id', 'DESC')
                ->limit(6)
                ->get();

            // Fetch active divisions, departments, and positions for the resort
            $resort_divisions = ResortDivision::where('status','active')->where('resort_id',$resort_id)->get();
            $resort_departments = ResortDepartment::where('status','active')->where('resort_id',$resort_id)->get();
            $resort_positions = ResortPosition::where('status','active')->where('resort_id',$resort_id)->get();


            // Fetch hiring requests with related notifications
            $hiring_request = Vacancies::with(['Getdepartment','Getposition',
                'TAnotificationParent.TAnotificationChildren' => function ($query) {
                    $query->whereNotNull('status')->where('status', '<>', '');
                }
            ])
            ->whereHas('TAnotificationParent.TAnotificationChildren', function ($query) {
                $query->whereNotNull('status')->where('status', '<>', '');
            })
            ->when(!$showAllDepts, function ($query) use ($Dept_id) {
                $query->where('department', $Dept_id);
            })
            ->where('resort_id', $resort_id)
            ->orderBy('vacancies.id', 'desc') // Order by ID in descending order
            ->limit(6)
            ->get();

            $vacancies = Vacancies::with([
                'Getdepartment',
                'Getposition',
                'TAnotificationParent.TAnotificationChildren' => function ($query) {
                    $query->where('status', 'Approved')  // Only include notifications that are approved
                          ->where('Approved_By', 8);     // Ensure the approval is from GM (Approved_By = 8)
                }
            ])
            ->where('resort_id', $resort_id)
            ->when(!$showAllDepts, function ($query) use ($Dept_id) {
                $query->where('department', $Dept_id);
            })
            ->whereHas('TAnotificationParent.TAnotificationChildren', function ($query) {
                $query->where('status', 'ForwardedToNext')  // Ensure only those with "Approved" status are included
                      ->where('Approved_By', 8);     // Ensure the approval is by GM
            })
            ->orderByDesc('id')
            ->limit(6)
            ->get();

            // dd($vacancies);

            $TotalApplicantsQuery = Applicant_form_data::join('applicant_wise_statuses as t1', 't1.Applicant_id', '=', 'applicant_form_data.id')
                ->join('vacancies as t2', 't2.id', '=', 'applicant_form_data.Parent_v_id')
                ->where('t1.status', '!=', 'Selected')
                ->where('applicant_form_data.resort_id', $resort_id);
            if (!$showAllDepts) {
                $TotalApplicantsQuery->where('t2.department', $Dept_id);
            }
            $TotalApplicants = $TotalApplicantsQuery->select(DB::raw('COUNT(DISTINCT t1.Applicant_id) as total_applicants'))
                ->first();

            $TotalApplicants = $TotalApplicants->total_applicants ?? 0;

            $InterviewsQuery = DB::table('applicant_inter_view_details as t1')
                ->join('applicant_form_data as t2', 't2.id', '=', 't1.Applicant_id') // Linking to applicant_form_data
                ->join('vacancies as t3', 't3.id', '=', 't2.Parent_v_id') // Linking to vacancies for department
                ->where('t2.resort_id', $resort_id);
            if (!$showAllDepts) {
                $InterviewsQuery->where('t3.department', $Dept_id);
            }
            $Interviews = $InterviewsQuery->count();

            $HiredQuery = DB::table('applicant_wise_statuses as t1')
                ->join('applicant_form_data as t2', 't2.id', '=', 't1.Applicant_id')
                ->join('vacancies as t3', 't3.id', '=', 't2.Parent_v_id') // Correct alias for vacancies
                ->where('t2.resort_id', $resort_id)
                ->where('t1.As_ApprovedBy', 8)
                ->where('t1.status', 'Selected');
            if (!$showAllDepts) {
                $HiredQuery->where('t3.department', $Dept_id);
            }
            $Hired = $HiredQuery->distinct()
                ->count('t1.Applicant_id'); // Count distinct Applicant_id

                $UpcomingApplicantsQuery = Vacancies::join("applicant_form_data as t1", "t1.Parent_v_id", "=", "vacancies.id")
                ->join("countries as t2", "t2.id", "=", "t1.country")
                ->join('applicant_wise_statuses as t4', function ($join) {
                    $join->on('t4.Applicant_id', '=', 't1.id');
                })
                ->whereIn('t4.status', ['Round']) // Adjust 'Round' to actual status values in your database
                ->join('applicant_inter_view_details as t3', function ($join) {
                    $join->on('t3.Applicant_id', '=', 't1.id');
                })
                ->join("resort_positions as t5", "t5.id", "=", "vacancies.position")
                ->join("resort_departments as t6", "t6.id", "=", "t5.dept_id")
                ->whereBetween('t3.InterViewDate', [$currentMonthStart, $currentMonthEnd])
                ->where('vacancies.Resort_id', $resort_id);
                if (!$showAllDepts) {
                    $UpcomingApplicantsQuery->where('vacancies.department', $Dept_id);
                }
                $UpcomingApplicants = $UpcomingApplicantsQuery
                ->selectRaw('
                    t1.id as Applicant_id,
                    t1.Application_date,
                    t1.passport_photo,
                    t1.first_name,
                    t1.last_name,
                    t1.gender,
                    t1.mobile_number as Contact,
                    t1.email as Email,
                    t2.name AS Nationality,
                    t3.InterViewDate,
                    t3.ApplicantInterviewtime,
                    t3.ResortInterviewtime,
                    t3.Status AS InterviewStatus,
                    t3.MeetingLink,
                    t4.As_ApprovedBy AS ApprovedBy,
                    t4.status AS ApplicationStatus,
                    t5.position_title as Position,
                    t4.id as ApplicantStatus_id,
                    t3.id as Interview_id,
                    t6.name as Department,
                    t1.notes, -- Ensure this field exists
                    t3.ResortInterviewtime'
                )
                ->get()
                ->map(function ($applicant) use ($config) {
                    $applicant->name = ucfirst($applicant->first_name . ' ' . $applicant->last_name);
                    $applicant->InterViewDate = Carbon::parse($applicant->InterViewDate)->format('d M');
                    $applicant->rank_name = $config[$applicant->As_ApprovedBy] ?? 'Wisdom AI';
                    if($applicant->passport_photo)
                    {
                    $getFileapplicant = Common::GetApplicantAWSFile($applicant->passport_photo);

                    $getFileapplicant =  $getFileapplicant['NewURLshow'];
                    }
                    else
                    {
                        $getFileapplicant = null;
                    }
                    $applicant->profileImg = $getFileapplicant;             
                    $applicant->Notes = isset($applicant->notes) ? base64_encode($applicant->notes) : '';
                    $applicant->applicant_id = base64_encode($applicant->ApplicantStatus_id);
                    return $applicant;
                });
            // dd($UpcomingApplicants);


            return view('resorts.talentacquisition.dashboard.hoddashboard', compact(
                'resort_id',
                'resort_divisions',
                'resort_departments',
                'resort_positions',
                'hiring_request',
                'vacancies','TotalApplicants','Interviews','Hired','UpcomingApplicants','drafts'

            ));
        } catch (\Exception $e) {
            \Log::error("Error in HOD Dashboard: " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            // Default empty collections in case of failure
            $resort_id = optional($this->globalUser)->resort_id;
            $resort_divisions = $resort_departments = $resort_positions = collect();
            $hiring_request = $vacancies = $UpcomingApplicants = $drafts = collect();
            $TotalApplicants = 0;
            $Interviews = 0;
            $Hired = 0;

            return view('resorts.talentacquisition.dashboard.hoddashboard', compact(
                'resort_id',
                'resort_divisions',
                'resort_departments',
                'resort_positions',
                'hiring_request',
                'vacancies',
                'TotalApplicants',
                'Interviews',
                'Hired',
                'UpcomingApplicants',
                'drafts'
            ));
        }
    }

    public function getTopCountriesPositionData(Request $request)
    {
        $resortId = auth()->user()->resort_id;
        $PositionId = $request->PositionId;
        $currentVacancyData = DB::table('applicant_form_data')
            ->selectRaw('countries.name as country, countries.flag_url, COUNT(applicant_form_data.id) as latest_count')
            ->join('countries', 'countries.id', '=', 'applicant_form_data.country')
            ->join('vacancies', 'vacancies.id', '=', 'applicant_form_data.Parent_v_id')
            ->where('vacancies.Resort_id', $resortId)
            ->where('vacancies.Position', $PositionId)
            ->whereDate('applicant_form_data.created_at', '>=', now()->startOfMonth())
            ->groupBy('countries.name', 'countries.flag_url')
            ->get();

        $previousVacancyData = DB::table('applicant_form_data')
            ->selectRaw('countries.name as country, COUNT(applicant_form_data.id) as previous_count')
            ->join('countries', 'countries.id', '=', 'applicant_form_data.country')
            ->join('vacancies', 'vacancies.id', '=', 'applicant_form_data.Parent_v_id')
            ->where('vacancies.Resort_id', $resortId)
            ->where('vacancies.Position', $PositionId)
            ->whereDate('applicant_form_data.created_at', '<', now()->startOfMonth())
            ->groupBy('countries.name')
            ->pluck('previous_count', 'country');
        $applicantTrends = [];
        $string1='';
        foreach ($currentVacancyData as $current)
        {
            $previousCount = $previousVacancyData[$current->country] ?? 0;
            $trend = $current->latest_count > $previousCount
                ? URL::asset('resorts_assets/images/up-chart.svg')
                : ($current->latest_count < $previousCount ? URL::asset('resorts_assets/images/down-chart.svg') : '');

            $applicantTrends[] =
            [
                'country' => $current->country,
                'flag_url' => $current->flag_url,
                'latest_count' => $current->latest_count,
                'previous_count' => $previousCount,
                'trend' => $trend,
            ];

        }
        return response()->json([
            'success' => true,
            'message'=>"Revert Request Sent Successfully!",
            'applicantTrends'=>$applicantTrends,
        ]);
    }
    public function topHiringSources(Request $request)
    {
        $currentYear =$request->YearWiseTopSource;

        // Get all months for the current year in "M Y" format
        $months = collect(range(1, 12))->map(function ($month) use ($currentYear) {
            return date('M Y', mktime(0, 0, 0, $month, 1));
        });

        // Fetch applicant data with month and year
        $applicantData = DB::table('applicant_form_data')
            ->join('hiring_sources', 'applicant_form_data.Applicant_Source', '=', 'hiring_sources.id')
            ->selectRaw('
                DATE_FORMAT(applicant_form_data.created_at, "%b %Y") as month_year,
                hiring_sources.source_name as source_name,
                COUNT(applicant_form_data.id) as count,
                hiring_sources.colour as color
            ')
            ->whereYear('applicant_form_data.created_at', $currentYear)
            ->groupBy('month_year', 'source_name', 'colour')
            ->orderByRaw('DATE_FORMAT(applicant_form_data.created_at, "%Y-%m")')
            ->get();
            // Extract unique sources
        $sources = $applicantData->pluck('source_name')->unique();
        $datasets = $sources->map(function ($source_name) use ($months, $applicantData) {
        $color = $applicantData->where('source_name', $source_name)->pluck('color')->first();
        $data = $months->map(function ($month) use ($source_name, $applicantData) {
                $record = $applicantData->where('source_name', $source_name)->where('month_year', $month)->first();
                return $record->count ?? 0; // Default to 0 if no record exists
            });

            return [
                'label' => $source_name,
                'data' => $data->values(),
                'backgroundColor' => $color,
                'borderColor' => '#fff',
                'borderWidth' => 2,
                'borderRadius' => 10,
            ];
        });

        // Convert to array if needed
        $datasets = $datasets->values();

        // Pass $months and $datasets to your chart
        return response()->json([
            'labels' => $months->toArray(),
            'datasets' => $datasets,
        ]);

    }
}
