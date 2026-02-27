<?php

namespace App\Http\Controllers\resorts\talentacquisition;

use DB;
use URL;
use Auth;
use Validator;
use Carbon\Carbon;
use App\Models\Resort;
use App\Helpers\Common;
use App\Models\Employee;
use App\Models\Vacancies;
use App\Models\ResortAdmin;
use Illuminate\Http\Request;
use App\Models\ResortSection;
use App\Models\ResortDivision;
use App\Models\ResortPosition;
use App\Models\ApplicantStatus;
use App\Models\ApplicationLink;
use App\Models\TaEmailTemplate;
use App\Models\ResortDepartment;
use App\Models\ApplicantLanguage;
use App\Models\Applicant_form_data;
use App\Models\ApplicantWiseStatus;
use App\Models\TAnotificationChild;
use App\Http\Controllers\Controller;
use App\Models\TAnotificationParent;
use App\Events\ResortNotificationEvent;
use App\Models\ApplicantInterViewDetails;
use App\Models\Applicant_form_job_assessment;
use Illuminate\Support\Str;
use App\Models\ApplicantOfferContract;
use App\Models\InterviewAssessmentResponseForm;
class ApplicantsController extends Controller
{
    //
    public $resort;
    protected $type;

    public function __construct()
    {
        $this->type = config('settings.Notifications');
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;
    }

    public function VacnacyWiseApplicants(Request $request, $id)
    {
        $page_title="Applicants";
        $EmailTamplete = TaEmailTemplate::where('Resort_id',$this->resort->resort_id)
        ->orderByDesc("id")
        ->get();

        return view("resorts.talentacquisition.Applicants.index",compact('EmailTamplete','page_title','id'));
    }

    public function GetVacnacyWiseApplicants(Request $request)
    {
        if($request->ajax())
        {

            $searchTerm = $request->get('searchTerm');

            $config = config('settings.Position_Rank');
            $resort_id =  $this->resort->resort_id;

            $id1  = base64_decode($request->vacanccyId);
            $resort_Location =  Auth::guard('resort-admin')->user()->resort->resort_id;

            $Applicant_form_data1 = Applicant_form_data::join('applicant_wise_statuses as t1', function ($join) {
                            $join->on('t1.Applicant_id', '=', 'applicant_form_data.id')
                                ->whereRaw('t1.id = (
                                    SELECT MAX(id)
                                    FROM applicant_wise_statuses
                                    WHERE Applicant_id = applicant_form_data.id
                                )');
                        })
                        ->leftJoin('work_experience_applicant_form as t7', function ($join) {
                            $join->on('t7.applicant_form_id', '=', 'applicant_form_data.id')
                                ->whereRaw('t7.id = (
                                    SELECT MAX(id)
                                    FROM work_experience_applicant_form
                                    WHERE applicant_form_id = applicant_form_data.id
                                )');
                        })
                        ->leftJoin('countries as t2', 't2.id', '=', 'applicant_form_data.country')
                        ->leftJoin('vacancies as t4', 't4.id', '=', 'applicant_form_data.Parent_v_id')
                        ->leftJoin('resort_positions as t5', 't5.id', '=', 't4.position')
                        ->leftJoin('applicant_inter_view_details as t8', function ($join) {
                            $join->on('t8.Applicant_id', '=', 'applicant_form_data.id')
                                ->whereRaw('t8.id = (
                                    SELECT MAX(id)
                                    FROM applicant_inter_view_details
                                    WHERE Applicant_id = applicant_form_data.id
                                )');
                        })
                        ->selectRaw('
                            applicant_form_data.id,
                            applicant_form_data.passport_no,
                            t2.name as countryName,
                            applicant_form_data.first_name,
                            applicant_form_data.last_name,
                            t7.work_start_date,
                            t7.work_end_date,
                            applicant_form_data.Total_Experiance as total_work_exp,
                            applicant_form_data.email,
                            applicant_form_data.Application_date,
                            applicant_form_data.mobile_number as contact,
                            applicant_form_data.employment_status,
                            t1.status,
                            t1.As_ApprovedBy,
                            applicant_form_data.passport_photo,
                            applicant_form_data.notes,
                            t1.status as ApplicantStatus,
                            t5.position_title,
                            t7.job_title,
                            t1.id as applicant_status_id,
                            t8.Status as InterviewStatus
                        ')
                        ->whereIn('t1.status', ['Sortlisted By Wisdom AI', 'Sortlisted', 'Round', 'Selected', 'Complete', 'Offer Letter Sent', 'Offer Letter Accepted', 'Offer Letter Rejected', 'Contract Sent', 'Contract Accepted', 'Contract Rejected'])
                        ->where('applicant_form_data.Parent_v_id', $id1)
                        ->where('applicant_form_data.resort_id', $resort_id)
                        ->orderBy('applicant_form_data.id', 'desc');

                    if (isset($searchTerm)) {
                        $Applicant_form_data1->where(function ($query) use ($searchTerm) {
                            $query->whereRaw("CONCAT(applicant_form_data.first_name, ' ', applicant_form_data.last_name) LIKE ?", ["%$searchTerm%"])
                                ->orWhere('applicant_form_data.email', 'like', "%$searchTerm%")
                                ->orWhere('t2.name', 'like', "%$searchTerm%")
                                ->orWhere('t5.position_title', 'like', "%$searchTerm%")
                                ->orWhere('t7.job_title', 'like', "%$searchTerm%")
                                ->orWhereRaw("CONCAT(applicant_form_data.Total_Experiance, ' ', 'year') LIKE ?", ["%$searchTerm%"])
                                ->orWhere('applicant_form_data.passport_no', 'like', "%$searchTerm%")
                                ->orWhere('applicant_form_data.mobile_number', 'like', "%$searchTerm%");

                            try {
                                $date = Carbon::createFromFormat('d-m-Y', $searchTerm)->format('Y-m-d');
                                $query->orWhere('applicant_form_data.Application_date', 'like', "%$date%");
                            } catch (\Exception $e) {
                                // Handle invalid date format gracefully
                            }
                        });
                    }

                    $Applicant_form_data = $Applicant_form_data1->get()
                        ->map(function ($applicant) use ($config, $resort_Location) {
                            // Map applicant fields
                            $applicant->name = ucfirst($applicant->first_name . ' ' . $applicant->last_name);
                            $applicant->Application_date = Carbon::parse($applicant->Application_date)->format('d-m-Y');
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
                            $applicant->Notes = base64_encode($applicant->notes);
                            $applicant->applicant_id = base64_encode($applicant->applicant_status_id);
                            return $applicant;
                        });
            return datatables()->of($Applicant_form_data)
            ->addColumn('action', function ($row) {
                $id = base64_encode($row->id);
                $actions = '<a href="javascript:void(0)" class="btn btn-sm btn-themeBlue me-1 userApplicants-btn" data-id="'.$row->applicant_id.'" data-bs-toggle="tooltip" data-bs-placement="top" title="View Applicant"><i class="fa-solid fa-eye"></i></a>
                        <a href="javascript:void(0)" class="btn btn-sm btn-theme ApplicantsNotes" data-notes="'.$row->Notes.'" data-id="'.$id.'" data-bs-toggle="tooltip" data-bs-placement="top" title="Notes"><i class="fa-solid fa-note-sticky"></i></a>';

                // Show Selected/Rejected buttons when GM round is complete
                if ($row->ApplicantStatus == 'Complete' && $row->As_ApprovedBy == 8) {
                    $actions .= ' <a href="javascript:void(0)" class="btn btn-sm btn-success selectCandidateBtn" data-id="'.$id.'" data-applicantstatusid="'.$row->applicant_id.'" data-bs-toggle="tooltip" title="Select"><i class="fa-solid fa-check"></i></a>';
                    $actions .= ' <a href="javascript:void(0)" class="btn btn-sm btn-danger rejectCandidateBtn" data-id="'.$id.'" data-applicantstatusid="'.$row->applicant_id.'" data-bs-toggle="tooltip" title="Reject"><i class="fa-solid fa-xmark"></i></a>';
                }

                // Show Send Offer Letter button when Selected
                if ($row->ApplicantStatus == 'Selected') {
                    $actions .= ' <a href="javascript:void(0)" class="btn btn-sm btn-warning sendOfferLetterBtn" data-id="'.$id.'" data-applicantstatusid="'.$row->applicant_id.'" data-bs-toggle="tooltip" title="Send Offer Letter"><i class="fa-solid fa-file-contract"></i></a>';
                }

                // Show Send Contract button when Offer Letter Accepted
                if ($row->ApplicantStatus == 'Offer Letter Accepted') {
                    $actions .= ' <a href="javascript:void(0)" class="btn btn-sm btn-info sendContractBtn" data-id="'.$id.'" data-applicantstatusid="'.$row->applicant_id.'" data-bs-toggle="tooltip" title="Send Contract"><i class="fa-solid fa-file-signature"></i></a>';
                }

                return $actions;
            })
            ->addColumn('details-control', function ($row) {
                return '<a class="a-link collapsed" data-bs-toggle="collapse" data-bs-target="#collapseRow'.$row->id.'" aria-expanded="false" aria-controls="collapseRow'.$row->id.'">Interview Details</a>';
            })
            ->addColumn('InvitationStatus', function ($row) {
                // Check applicant status first
                if ($row->ApplicantStatus === 'Rejected') {
                    return '<span class="badge bg-danger">Rejected</span>';
                } elseif ($row->ApplicantStatus === 'Selected') {
                    return '<span class="badge bg-success">Selected</span>';
                }

                $status = $row->InterviewStatus ?? null;
                if ($status === 'Invitation Sent') {
                    return '<span class="badge bg-info text-white">Invitation Sent</span>';
                } elseif ($status === 'Slot Booked') {
                    return '<span class="badge bg-success">Accepted</span>';
                } elseif ($status === 'Invitation Rejected') {
                    return '<span class="badge bg-danger">Declined</span>';
                } elseif ($status === 'Slot Not Booked' || $status === 'Active') {
                    return '<span class="badge bg-secondary">Pending</span>';
                }
                return '<span class="badge bg-light text-dark">-</span>';
            })
            ->addColumn('Stage', function ($row) {

                $string ='';
                if($row->As_ApprovedBy == 0)
                {
                    $string ='<span class="badge badge-themeSkyblue">' .$row->ApplicantStatus.'</span>';
                }
                elseif($row->As_ApprovedBy == 3 &&  $row->ApplicantStatus  == 'Sortlisted')
                {
                    $string ='<span class="badge badge-themeBlue">'. $row->rank_name .' '. $row->ApplicantStatus.'</span>';
                }
                elseif($row->As_ApprovedBy == 3 &&  $row->ApplicantStatus  == 'Round' || $row->ApplicantStatus  == 'Complete')
                {
                     $string ='<span class="badge badge-themeBlue">'. $row->rank_name .' '. $row->ApplicantStatus.'</span>';
                }
                elseif($row->As_ApprovedBy ==2 &&  $row->ApplicantStatus  == 'Round' || $row->ApplicantStatus  == 'Complete')
                {
                    $string ='<span class="badge badge-themePurple">'. $row->rank_name .' '. $row->ApplicantStatus.'</span>';
                }
                elseif($row->As_ApprovedBy == 8 &&  $row->ApplicantStatus  == 'Round' || $row->ApplicantStatus  == 'Complete')
                {
                     $string =' <span class="badge badge-themePink">'. $row->rank_name .' '. $row->ApplicantStatus.'</span>';
                }
                elseif($row->As_ApprovedBy == 8 &&  $row->ApplicantStatus  == 'Selected' || $row->ApplicantStatus  == 'Complete')
                {
                         $string ='<span class="badge badge-themeSuccess">'. $row->rank_name .' '. $row->ApplicantStatus.'</span>';
                }
                elseif( $row->ApplicantStatus  == 'Rejected')
                {
                    $string ='<span class="badge badge-themeDanger">'. $row->rank_name .' '. $row->ApplicantStatus.'</span>';
                }
                elseif($row->ApplicantStatus == 'Offer Letter Sent')
                {
                    $string ='<span class="badge bg-warning text-dark">Offer Letter Sent</span>';
                }
                elseif($row->ApplicantStatus == 'Offer Letter Accepted')
                {
                    $string ='<span class="badge bg-success">Offer Letter Accepted</span>';
                }
                elseif($row->ApplicantStatus == 'Offer Letter Rejected')
                {
                    $string ='<span class="badge bg-danger">Offer Letter Rejected</span>';
                }
                elseif($row->ApplicantStatus == 'Contract Sent')
                {
                    $string ='<span class="badge bg-info text-white">Contract Sent</span>';
                }
                elseif($row->ApplicantStatus == 'Contract Accepted')
                {
                    $string ='<span class="badge bg-success">Contract Accepted</span>';
                }
                elseif($row->ApplicantStatus == 'Contract Rejected')
                {
                    $string ='<span class="badge bg-danger">Contract Rejected</span>';
                }
                return $string;
            })
            ->rawColumns(['action', 'details-control', 'InvitationStatus', 'Stage'])
            ->make(true);
        }
    }

    public function ApplicantNote(Request $request)
    {
        $ApplicantNote = $request->ApplicantNote;
        $Applicant_id =  base64_decode($request->Applicant_id);

        $validator = Validator::make($request->all(), [
            'ApplicantNote' => 'required|max:250',
        ],
        [
            'ApplicantNote.required' => 'Please write something.',
            'ApplicantNote.max' => 'The note cannot exceed 250 characters.',
        ]);

        if($validator->fails())
        {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try
        {

            $Applicant_form_data = Applicant_form_data::find( $Applicant_id);
            $Applicant_form_data ->update(['notes'=> $ApplicantNote, 'notes_by' => $this->resort->id]);
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Note saved successfully!',
            ]);



       }
       catch (\Exception $e)
       {
           DB::rollBack();
           \Log::emergency("File: " . $e->getFile());
           \Log::emergency("Line: " . $e->getLine());
           \Log::emergency("Message: " . $e->getMessage());
           return response()->json(['error' => 'Failed to Upload  data'], 500);
       }
    }

    public function TaUserApplicantsSideBar($id)
    {

        $resort_Location =  Auth::guard('resort-admin')->user()->resort->resort_id;
        $Applicant_form_data = Applicant_form_data::leftJoin('applicant_wise_statuses as t1', function ($join) {
                        $join->on('t1.Applicant_id', '=', 'applicant_form_data.id')
                            ->whereRaw('t1.id = (
                                SELECT MAX(id)
                                FROM applicant_wise_statuses
                                WHERE Applicant_id = applicant_form_data.id
                            )');
                    })

                    ->leftJoin('education_applicant_form as t2', 't2.applicant_form_id', '=', 'applicant_form_data.id')
                    ->leftJoin('countries as t4', 't4.id', '=', 'applicant_form_data.country')
                    ->leftJoin('vacancies as t6', 't6.id', '=', 'applicant_form_data.Parent_v_id')
                    ->leftJoin('resort_positions as t7', 't7.id', '=', 't6.position')
                    ->leftJoin('resort_departments as t8', 't8.id', '=', 't7.dept_id')
                    ->leftJoin('applicant_inter_view_details as t9', function ($join) use ($id) {
                        $join->on('t9.ApplicantStatus_id', '=', 't1.id');
                            // ->whereRaw('t9.id = (
                            //     SELECT MAX(id)
                            //     FROM applicant_inter_view_details
                            //     WHERE Applicant_id = t1.id
                            // )')->where('t9.ApplicantStatus_id', base64_decode($id));
                    })
                    ->selectRaw('
                        applicant_form_data.id,
                        applicant_form_data.Application_date,
                        applicant_form_data.first_name,
                        applicant_form_data.last_name,
                        applicant_form_data.gender,
                        applicant_form_data.dob,
                        applicant_form_data.email,
                        applicant_form_data.mobile_number,
                        applicant_form_data.country,
                        applicant_form_data.state,
                        applicant_form_data.city,
                        applicant_form_data.pin_code,
                        applicant_form_data.employment_status,
                        applicant_form_data.passport_no,
                        t2.educational_level as Education,
                        applicant_form_data.address_line_one,
                        applicant_form_data.address_line_two,
                        applicant_form_data.NotiesPeriod,
                        applicant_form_data.SalaryExpectation,
                        t4.name as CountryName,
                        t1.status as ApplicantStatus,
                        t7.position_title,t7.id as position_id,
                        t1.As_ApprovedBy,
                        t1.id as ApplicantStatusID,
                        applicant_form_data.passport_photo,
                        applicant_form_data.notes,
                        applicant_form_data.notes_by,
                        applicant_form_data.id as ApplicantID,
                        applicant_form_data.passport_img,
                        applicant_form_data.curriculum_vitae,
                        applicant_form_data.full_length_photo,
                         applicant_form_data.Total_Experiance,
                        t8.name as DepartmentName,
                        t9.InterViewDate,
                        t9.ApplicantInterviewtime,
                        t9.ResortInterviewtime,
                        t9.MeetingLink,
                        t1.Comments
                    ') // Removed the trailing comma here
                    ->where('t1.id', base64_decode($id))
                    ->first();

            $VideoQuestions = applicant_form_job_assessment::leftjoin("video_questions as t1","t1.id","=","applicant_form_job_assessment.question_id")
                                                            ->where("applicant_form_job_assessment.applicant_form_id",$Applicant_form_data->ApplicantID)
                                                            ->where("applicant_form_job_assessment.question_type","video")
                                                            ->get(['t1.VideoQuestion','video_path']);

            $SimpleQuestions = applicant_form_job_assessment::leftjoin("questionnaire_children as t1","t1.id","=","applicant_form_job_assessment.question_id")
                                                            ->where("applicant_form_job_assessment.applicant_form_id",$Applicant_form_data->ApplicantID)
                                                            ->where("applicant_form_job_assessment.question_type",null)
                                                            ->get(['t1.Question','applicant_form_job_assessment.response','applicant_form_job_assessment.multiple_responses']);



            $config = config('settings.Position_Rank');

            $InterViewRound = config('settings.InterViewRound');
            if($Applicant_form_data->passport_photo)
            {
                $getFileapplicant = Common::GetApplicantAWSFile($Applicant_form_data->passport_photo);
                $getFileapplicant =  $getFileapplicant['NewURLshow'];
            }
            else
            {
                $getFileapplicant = null;
            }
            $Applicant_form_data->profileImg = $getFileapplicant;
            $Applicant_form_data->rank_name = $config[$Applicant_form_data->As_ApprovedBy] ?? 'Unknown Rank';
            $Applicant_form_data->InterViewDate =   Carbon::parse($Applicant_form_data->InterViewDate)->format('d-m-Y');

            $ApplicantWiseStatusFinal = ApplicantWiseStatus::where("Applicant_id", $Applicant_form_data->ApplicantID)
                                                        ->orderBy('status', 'asc') // Sort by "status" in ascending order
                                                        ->whereIn('status',['Complete','Round','Sortlisted'])
                                                        ->get(["Applicant_id", "As_ApprovedBy", "status"]);
            $HrSortlisted = ApplicantWiseStatus::where("Applicant_id", $Applicant_form_data->ApplicantID)
                                                        ->orderBy('status', 'asc')
                                                        ->where('status', 'Sortlisted')
                                                        ->where('As_ApprovedBy', '!=', 0)
                                                        ->first(["Applicant_id", "As_ApprovedBy", "status"]);

            $CompliteWiseStatusFinal = ApplicantWiseStatus::where("Applicant_id", $Applicant_form_data->ApplicantID)
                                                            ->orderBy('status', 'asc') // Sort by "status" in ascending order
                                                            ->whereIn('status',['Complete','Selected'])
                                                            ->get(["Applicant_id", "As_ApprovedBy", "status"])
                                                            ->map(function($s) use($config){
                                                                $config = config('settings.Position_Rank');
                                                                $s->rank_name = $config[$s->As_ApprovedBy] ?? 'Unknown Rank';
                                                                return $s;
                                                            });
            $completeRound=array();
            $approved_keys=[2,3,8];
            foreach($CompliteWiseStatusFinal as $c)
            {

                $completeRound[$c->As_ApprovedBy] = $c->rank_name;
            }
            

            $InterviewComments = ApplicantWiseStatus::leftjoin('applicant_inter_view_details as t1','t1.ApplicantStatus_id',"=","applicant_wise_statuses.id")
                                                            ->where("applicant_wise_statuses.Applicant_id", $Applicant_form_data->ApplicantID)
                                                            ->orderBy('status', 'asc') // Sort by "status" in ascending order
                                                            ->whereIn('applicant_wise_statuses.status',['Complete','Selected','Round'])
                                                            ->get([
                                                                            "applicant_wise_statuses.Applicant_id",
                                                                            "t1.InterViewDate",'t1.ApplicantInterviewtime',
                                                                            "t1.ResortInterviewtime","applicant_wise_statuses.As_ApprovedBy",
                                                                            "applicant_wise_statuses.status",
                                                                            "applicant_wise_statuses.Comments",
                                                                            "applicant_wise_statuses.As_ApprovedBy",
                                                                            "t1.MeetingLink"
                                                                            ])
                                                            ->Map(function($s) use($config){
                                                                $config = config('settings.Position_Rank');
                                                                $s->rank_name = $config[$s->As_ApprovedBy] ?? 'Unknown Rank';
                                                                $s->InterViewDate =Carbon::parse($s->InterViewDate)->format('d-m-Y');
                                                                return $s;
                                                            });

                                                            // dd(  $InterviewComments,$Applicant_form_data->ApplicantID);

            $CurrentRank= $this->resort->GetEmployee->rank;
            $interview_assesment_response = InterviewAssessmentResponseForm::join('employees as t1','t1.Admin_Parent_id','=','interview_assessment_responses.interviewer_id')
            ->where('interviewee_id',$Applicant_form_data->ApplicantID)->get(['interview_assessment_responses.id','interview_assessment_responses.form_id','interview_assessment_responses.interviewer_id','interview_assessment_responses.interviewee_id','interview_assessment_responses.interviewer_signature','interview_assessment_responses.responses','t1.rank']);

            // dd($interview_assesment_response);

            $ApplicantLanguage = ApplicantLanguage::where('applicant_form_id', $Applicant_form_data->ApplicantID)->get(['language','level']);
            $CurrentRankOFUser = $this->resort->GetEmployee->rank;

            if($Applicant_form_data->passport_photo)
            {
               $getFileapplicant = Common::GetApplicantAWSFile($Applicant_form_data->passport_photo);

               $getFileapplicant =  $getFileapplicant['NewURLshow'];
            }
            else
            {
                $getFileapplicant = null;
            }


        $currentUserId = $this->resort->id;
        $view =  view('resorts.renderfiles.TaUserApplicantsSideBar',compact('currentUserId','getFileapplicant','CurrentRankOFUser','ApplicantLanguage','InterviewComments','CurrentRank','HrSortlisted','completeRound','ApplicantWiseStatusFinal','Applicant_form_data','InterViewRound','SimpleQuestions','VideoQuestions','interview_assesment_response'))->render();
        return response()->json([
            'success' => true,
            'view'=>$view,
            'message' => 'Note saved successfully!',
        ]);
    }
    public function getApplicantWiseGridWise(Request $request)
    {

        $config = config('settings.Position_Rank');
        $resort_id =  $this->resort->resort_id;
        $id1  = base64_decode($request->id);
        $resort_Location =  Auth::guard('resort-admin')->user()->resort->resort_id;
        $searchTerm = $request->searchTerm;
        $Applicant_form_data1 = Applicant_form_data::join('applicant_wise_statuses as t1', function ($join) {
            $join->on('t1.Applicant_id', '=', 'applicant_form_data.id')
                ->whereRaw('t1.id = (
                    SELECT MAX(id)
                    FROM applicant_wise_statuses
                    WHERE Applicant_id = applicant_form_data.id
                )');
        })
         ->join('work_experience_applicant_form as t7', function ($join) {
            $join->on('t7.applicant_form_id', '=', 'applicant_form_data.id')
                ->whereRaw('t1.id = (
                    SELECT MAX(id)
                    FROM applicant_wise_statuses
                    WHERE Applicant_id = applicant_form_data.id
                )');
        })
        ->leftJoin('work_experience_applicant_form as t3', 't3.id', '=', 'applicant_form_data.id')
        ->leftJoin('countries as t2', 't2.id', '=', 'applicant_form_data.country')
        ->leftJoin('vacancies as t4', 't4.id', '=', 'applicant_form_data.Parent_v_id')
        ->leftJoin('resort_positions as t5', 't5.id', '=', 't4.position')
        ->selectRaw('
            applicant_form_data.id,
            applicant_form_data.passport_no,
            t2.name as countryName,
            applicant_form_data.first_name,
            applicant_form_data.last_name,
            t3.work_start_date,
            t3.work_end_date,
            applicant_form_data.Total_Experiance as total_work_exp,
            applicant_form_data.email,
            applicant_form_data.Application_date,
            applicant_form_data.mobile_number as contact,
            applicant_form_data.employment_status,
            t1.status,
            t1.As_ApprovedBy,
            applicant_form_data.passport_photo,
            applicant_form_data.notes,
            t1.status as ApplicantStatus,
            t5.position_title,
            t7.job_title,
            t1.id as applicant_status_id

        ')
        ->whereIn('t1.status', ['Sortlisted By Wisdom AI', 'Sortlisted', 'Round', 'Selected','Complete'])
        ->where('applicant_form_data.Parent_v_id', $id1)
        ->where('applicant_form_data.resort_id', $resort_id)
        ->orderBy('applicant_form_data.id', 'desc');

        // Search functionality
        if (isset($searchTerm)) {
            $Applicant_form_data1->where(function ($query) use ($searchTerm) {
                $query->whereRaw("CONCAT(applicant_form_data.first_name, ' ', applicant_form_data.last_name) LIKE ?", ["%$searchTerm%"])
                    ->orWhere('applicant_form_data.email', 'like', "%$searchTerm%")
                    ->orWhere('t2.name', 'like', "%$searchTerm%")
                    ->orWhere('t5.position_title', 'like', "%$searchTerm%")
                    ->orWhere('t7.job_title', 'like', "%$searchTerm%")
                    ->orWhere('applicant_form_data.passport_no', 'like', "%$searchTerm%")
                    ->orWhere('applicant_form_data.mobile_number', 'like', "%$searchTerm%");
            });
        }

        $Applicant_form_data = $Applicant_form_data1->paginate(10);

        $Applicant_form_data->getCollection()->transform(function ($applicant) use ($config, $resort_Location) {
            $config = config('settings.Position_Rank');
            $applicant->rank = $applicant->As_ApprovedBy; // Approved By field
            $applicant->name = ucfirst($applicant->first_name . ' ' . $applicant->last_name);
            $applicant->Application_date = Carbon::parse($applicant->Application_date)->format('d-m-Y');
            $applicant->rank_name = $config[$applicant->As_ApprovedBy] ?? 'Wisdom AI'; // Map rank_name from config
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
            $applicant->Notes = base64_encode($applicant->notes);
            // $applicant->applicant_id = base64_encode($applicant->id);
            $applicant->applicant_id=  base64_encode($applicant->applicant_status_id);

            return $applicant;
        });

        // Pagination links
        $pagination = $Applicant_form_data->links()->render();


            $view = view("resorts.talentacquisition.Applicants.gridviwe",compact('pagination','Applicant_form_data'))->render();
            return response()->json([
                'success' => true,
                'view'=>$view,


            ]);
    }

    public function getApplicantWiseNotes($id)
    {

        try{
            $Applicant_form_data =  Applicant_form_data::find(base64_decode($id));
            return response()->json(['success' => true, 'notes' => $Applicant_form_data->notes], 200);
        }
        catch( \Exception $e )
        {
            \Log::emergency("File: ".$e->getFile());
            \Log::emergency("Line: ".$e->getLine());
            \Log::emergency("Message: ".$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to add Get the data.'], 500);

        }
    }

    public function ApplicantWiseStatus(Request $request)
    {


            $applicant_id = base64_decode($request->Applicant_id);
            $RowId=  $request->RowId;
            $EmailTamplete = TaEmailTemplate::where('Resort_id',$this->resort->resort_id)
                                        ->orderByDesc("id")
                                        ->get();
            $rank = $this->resort->GetEmployee->rank;

            $resort_id = $this->resort->resort_id;
            $config = config('settings.Position_Rank');



                $StatusApplicantStatus = Vacancies::join("applicant_form_data as t1", "t1.Parent_v_id", "=", "vacancies.id")
                    ->join('applicant_wise_statuses as t4', function ($join) {
                        $join->on('t4.Applicant_id', '=', 't1.id')
                            ->whereIn('t4.status',  ['Sortlisted','Round','Selected'])
                            // ->where('t4.As_ApprovedBy', '=', 3)
                            ->whereRaw('t4.id = (
                                SELECT MAX(id)
                                FROM applicant_wise_statuses
                                WHERE Applicant_id = t1.id
                            )');
                    })
                    ->leftjoin('applicant_inter_view_details as t3', function ($join) use($applicant_id) {
                        $join->on('t3.Applicant_id', '=', 't1.id')
                            ->whereRaw('t3.id = (
                                SELECT MAX(id)
                                FROM applicant_inter_view_details
                                WHERE Applicant_id = t1.id
                            )')->where('t3.ApplicantStatus_id',$applicant_id);
                    })
                    ->leftjoin("resort_positions as t5", "t5.id", "=", "vacancies.position")
                    ->where('vacancies.Resort_id', $resort_id)
                    ->where('t4.id', $applicant_id)
                    ->selectRaw('
                        t1.id as Applicant_id,
                        t1.Application_date,
                        t1.passport_photo,
                        t1.first_name,
                        t1.last_name,
                        t1.gender,
                        t1.mobile_number as Contact,
                        t1.email as Email,
                        t3.InterViewDate,
                        t3.ApplicantInterviewtime,
                        t3.ResortInterviewtime,
                        t3.Status AS InterviewStatus,
                        t3.MeetingLink,
                        t4.status as ApplicantStatus,
                        t4.As_ApprovedBy,
                        t5.position_title as Position,
                        t4.id as ApplicantStatus_id,
                        vacancies.Resort_id,
                        t3.id as InterView_id
                    ')
                    ->first();
                if ($StatusApplicantStatus) {


                    if ($StatusApplicantStatus)
                    {
                       
                        $StatusApplicantStatus->rank_name = $config[$StatusApplicantStatus->As_ApprovedBy] ?? 'Unknown Rank';
                        $StatusApplicantStatus->AppliedDate = Carbon::parse($StatusApplicantStatus->Application_date)->format('d-m-Y');

                        $StatusApplicantStatus->MalidivanTime = (isset($StatusApplicantStatus->ResortInterviewtime)) ?$StatusApplicantStatus->ResortInterviewtime: '-';
                        $StatusApplicantStatus->ApplicantTime = (isset($StatusApplicantStatus->ApplicantInterviewtime)) ?$StatusApplicantStatus->ApplicantInterviewtime :   '-';
                        $StatusApplicantStatus->InterviewStatus = $StatusApplicantStatus->InterviewStatus ?? 'Slot Not Booked';
                        $StatusApplicantStatus->Date = (isset($StatusApplicantStatus->InterViewDate) && $StatusApplicantStatus->InterViewDate !="0000-00-00") ?  Carbon::parse($StatusApplicantStatus->InterViewDate)->format('d-m-Y') : '-';
                        // Badge logic

                        $badgeThemes = [
                            'Sortlisted' => 'badge-themeBlue',
                            'Round' => [
                                2 => 'badge-themePurple',
                                3 => 'badge-themeBlue',
                                8 => 'badge-themePink'
                            ],
                            'Selected' => 'badge-themeSuccess',
                            'Rejected' => 'badge-themeDanger',
                        ];

                        if (isset($badgeThemes[$StatusApplicantStatus->ApplicantStatus])) {
                            if (is_array($badgeThemes[$StatusApplicantStatus->ApplicantStatus]))
                            {
                                $theme = $badgeThemes[$StatusApplicantStatus->ApplicantStatus][$StatusApplicantStatus->As_ApprovedBy] ?? 'badge-themeDefault';
                            }
                            else
                            {
                                $theme = $badgeThemes[$StatusApplicantStatus->ApplicantStatus];
                            }
                            $StatusApplicantStatus->ApplicantStatus = "<span class='badge $theme'>{$StatusApplicantStatus->rank_name} {$StatusApplicantStatus->ApplicantStatus}</span>";
                        } else {
                            $StatusApplicantStatus->ApplicantStatus = "<span class='badge badge-themeDefault'>{$StatusApplicantStatus->ApplicantStatus}</span>";
                        }



                        if( $StatusApplicantStatus->rank_name =="HR")
                        {
                            $StatusApplicantStatus->round  = "Introductory Round";
                            $StatusApplicantStatus->Interviewer ="HR Manager";
                        }
                        else if( $StatusApplicantStatus->rank_name =="GM")
                        {
                            $StatusApplicantStatus->round  = "Final Round";
                            $StatusApplicantStatus->Interviewer ="General Manager";
                        }
                        else if( $StatusApplicantStatus->rank_name =="HOD")
                        {
                            $StatusApplicantStatus->round  = "Techincal Round";
                            $StatusApplicantStatus->Interviewer ="Head Of Department";
                        }
                        else
                        {
                            $StatusApplicantStatus->round  = "Final Round";
                            $StatusApplicantStatus->Interviewer = $StatusApplicantStatus->rank_name;
                        }
                        $StatusApplicantStatus->ApplicantID = base64_encode($StatusApplicantStatus->Applicant_id);

                        $StatusApplicantStatus->ApplicantStatus_id = base64_encode($StatusApplicantStatus->ApplicantStatus_id);
                        $StatusApplicantStatus->Interview_id = base64_encode($StatusApplicantStatus->InterView_id);

                    }
                }
                return response()->json([
                    'success' => true,
                    'data' => [
                        'rank_name' => $StatusApplicantStatus->rank_name,
                        'round' => $StatusApplicantStatus->round,
                        'Interviewer' => $StatusApplicantStatus->Interviewer,
                        'Date' => $StatusApplicantStatus->Date,
                        'MalidivanTime' => $StatusApplicantStatus->MalidivanTime,
                        'ApplicantTime' => $StatusApplicantStatus->ApplicantTime,
                        'InterviewStatus' => $StatusApplicantStatus->InterviewStatus,
                        'Resort_id' => $StatusApplicantStatus->Resort_id,
                        'ApplicantID' => $StatusApplicantStatus->ApplicantID,
                        'ApplicantStatus_id' => $StatusApplicantStatus->ApplicantStatus_id,
                        'Interview_id'=>$StatusApplicantStatus->Interview_id,
                        'MeetingLink'=>$StatusApplicantStatus->MeetingLink,
                    ],
                ]);


    }

    // public function SortlistedApplicants($id)
    // {
    //   $id = base64_decode($id);
    // }

    // public function ApplicantTimeZoneget(Request $request)
    // {

    //     $ApplicantID=base64_decode($request->ApplicantID);
    //     $Applicant_form_data = Applicant_form_data::leftJoin('countries as t1',"t1.id","=","applicant_form_data.country")
    //     ->where('applicant_form_data.id',$ApplicantID)->first(['applicant_form_data.*','t1.flag_url']);
    //     $Round = $request->Round;
    //     $InterviewType = $request->InterviewType;
    //     $view = view("resorts.renderfiles.TimezoneModel",compact('Applicant_form_data','Round','InterviewType'))->render();

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Slots Fetched Successfully!',
    //         'view'=>$view,
    //     ]);
    // }

    public function ApplicantTimeZoneget(Request $request)
    {
        // dd($request);
        $InterviewDate = $request->InterviewDate;
        $ApplicantID = base64_decode($request->ApplicantID);
        $Applicant_form_data = Applicant_form_data::leftJoin('countries as t1', "t1.id", "=", "applicant_form_data.country")
            ->where('applicant_form_data.id', $ApplicantID)->first(['applicant_form_data.*', 't1.flag_url']);

        $Round = $request->Round;
        $InterviewType = $request->InterviewType;

        // Get the booked interview slots for the current interviewer on this date
        $interviewerId = $this->resort->id;
        $resortId = $this->resort->resort_id;
        $bookedSlots = ApplicantInterViewDetails::where('InterViewDate', $InterviewDate)
            ->whereIn('Status', ['Invitation Sent', 'Slot Booked'])
            ->where(function ($query) use ($interviewerId, $resortId) {
                $query->where('interviewer_id', $interviewerId)
                      ->orWhere(function ($q) use ($resortId) {
                          $q->whereNull('interviewer_id')
                            ->where('resort_id', $resortId);
                      });
            })
            ->get();
        // dd($bookedSlots);

        // Prepare the booked slots in a suitable structure (array of booked time slots)
        $bookedTimes = $bookedSlots->map(function ($slot) {
            return [
                'ResortInterviewtime' => $slot->ResortInterviewtime,
                'ApplicantInterviewtime' => $slot->ApplicantInterviewtime
            ];
        })->toArray();

        $view = view("resorts.renderfiles.TimezoneModel", compact('Applicant_form_data', 'Round', 'InterviewType', 'bookedTimes'))->render();

        return response()->json([
            'success' => true,
            'message' => 'Slots Fetched Successfully!',
            'view' => $view,
        ]);
    }


    public function InterviewRequest(Request $request)
    {
        // dd($request->all());
       
            $Round =  $request->Round??"HR";
            $InterviewType = $request->InterviewType??'Introductory';
            $TimeSlotsFormdate = $request->TimeSlotsFormdate;
            $ApplicantID = base64_decode($request->ApplicantID);
            $ApplicantStatus_id = base64_decode($request->ApplicantStatus_id);
            $Resort_id = $request->Resort_id;

            // Fetch resort details
            $resort_details = Resort::find($Resort_id); // Use find() instead of where()->get() for a single result.
            // dd($resort_details);
            if (!$resort_details) {
                return response()->json(['error' => 'Resort not found'], 404);
            }

            $interviewerId = $this->resort->id;
            $resortTime = $request->ResortInterviewtime ?? $request->MalidivanManualTime1;
            $applicantTime = $request->ApplicantInterviewtime ?? $request->ApplicantManualTime1;
            $interviewDate = Carbon::createFromFormat('Y-m-d', $request->TimeSlotsFormdate)->format('Y-m-d');

            // Validate manual time against interviewer's existing bookings
            if ($request->MalidivanManualTime1) {
                $existingBookings = ApplicantInterViewDetails::where('InterViewDate', $interviewDate)
                    ->whereIn('Status', ['Invitation Sent', 'Slot Booked'])
                    ->where(function ($query) use ($interviewerId, $Resort_id) {
                        $query->where('interviewer_id', $interviewerId)
                              ->orWhere(function ($q) use ($Resort_id) {
                                  $q->whereNull('interviewer_id')
                                    ->where('resort_id', $Resort_id);
                              });
                    })
                    ->get();

                foreach ($existingBookings as $booking) {
                    $bookedTimes = array_map('trim', explode(',', $booking->ResortInterviewtime));
                    if (in_array($request->MalidivanManualTime1, $bookedTimes)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'This time slot is already booked for another candidate.',
                        ]);
                    }
                }
            }

            // Update or create Applicant Interview Details
            $ApplicantInterViewDetails = ApplicantInterViewDetails::updateOrCreate(
                [
                    'resort_id' => $Resort_id,
                    'Applicant_id' => $ApplicantID,
                    'ApplicantStatus_id' => $ApplicantStatus_id
                ],
                [
                    'Status' => 'Invitation Sent',
                    'ResortInterviewtime' => $resortTime,
                    'ApplicantInterviewtime' => $applicantTime,
                    'InterViewDate' => $interviewDate,
                    'EmailTemplateId' => $request->EmailTemplate,
                    'MeetingLink' => $request->MeetingLink,
                    'interviewer_id' => $interviewerId,
                    'invitation_token' => Str::uuid(),
                ]
            );

            // Fetch applicant form data
            $Applicant_form_data = Applicant_form_data::find($ApplicantID);
            if (!$Applicant_form_data) {
                return response()->json(['error' => 'Applicant data not found'], 404);
            }

            DB::commit();

            // Get rank and Todo data
            $rank = $this->resort->GetEmployee->rank;
            $TodoData = Common::GmApprovedVacancy($Resort_id, $rank);
            $TodoDataview = view('resorts.renderfiles.TaTodoList', compact('TodoData'))->render();

            // fianl Response

            $Final_response_data = Vacancies::join("applicant_form_data as t1", "t1.Parent_v_id", "=", "vacancies.id")
                    ->join("countries as t2", "t2.id", "=", "t1.country")
                    ->join('applicant_wise_statuses as t4', function ($join) {
                        $join->on('t4.Applicant_id', '=', 't1.id');
                    })

                    ->Join('applicant_inter_view_details as t3', function ($join) {
                        $join->on('t3.Applicant_id', '=', 't1.id')
                            ->whereRaw('t3.id = (
                                SELECT MAX(id)
                                FROM applicant_inter_view_details
                                WHERE Applicant_id = t1.id
                            )');
                    })
                    ->join("resort_positions as t5", "t5.id", "=", "vacancies.position")
                    ->join("resort_departments as t6", "t6.id", "=", "t5.dept_id")
                    ->where('t1.id',            $ApplicantID)
                    ->where('t4.id',            $ApplicantStatus_id)
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
                        t6.name as Department
                    ')
                    ->first();
                    $InterViewDate = Carbon::parse($Final_response_data->InterViewDate)->format('d-m-Y');
                    $FianlResponse ='';
                    // dd($Final_response_data);
                    if($Final_response_data)
                    {
                        $FianlResponse ='<tr>
                            <th>Name:</th>
                            <td>'.ucfirst($Final_response_data->first_name.' '.$Final_response_data->last_name).'</td>
                        </tr>
                        <tr>
                            <th>Position:</th>
                            <td>'.$Final_response_data->Position.'</td>
                        </tr>
                        <tr>
                            <th>Department:</th>
                            <td>'.$Final_response_data->Department.'</td>
                        </tr>
                        <tr>
                            <th>Interview Date:</th>
                            <td>'.$InterViewDate.'</td>
                        </tr>
                        <tr>
                            <th>Malidivan Time:</th>
                            <td>'.$Final_response_data->ResortInterviewtime.'</td>
                        </tr>
                        <tr>
                            <th>Applicant Time:</th>
                            <td>'.$Final_response_data->ApplicantInterviewtime.'</td>
                        </tr>';
                        if($Final_response_data->MeetingLink) {
                            $FianlResponse .= '<tr>
                                <th>Meeting Link:</th>
                                <td><a href="'.$Final_response_data->MeetingLink.'" target="_blank">'.$Final_response_data->MeetingLink.'</a></td>
                            </tr>';
                        }
                    }

                    $invitationLink = route('resort.interview.invitation.show', $ApplicantInterViewDetails->invitation_token);

                    $dynamic_data = [
                        'candidate_name' => $Final_response_data->first_name . ' ' . $Final_response_data->last_name,
                        'position_title' => $Final_response_data->Position,
                        'resort_name' => $resort_details->resort_name,
                        'interview_date' => Carbon::parse($InterViewDate)->format('d-m-Y'),
                        'interview_time' => $Final_response_data->ApplicantInterviewtime,
                        'interview_link' => $invitationLink,
                        'meeting_link' => $Final_response_data->MeetingLink ?? '',
                        'interview_type' =>  $InterviewType,
                        'interview_round'=> $Round,
                        'accept_link' => $invitationLink,
                        'reject_link' => $invitationLink,
                        'department' => $Final_response_data->Department ?? '',
                    ];

                    // Send email using the BeyondTestApprovalEmail class
                    $recipientEmail = $Applicant_form_data->email;
                    $templateId = $request->EmailTemplate;
                    $emailWarning = '';
                    try {
                        $result = Common::sendTemplateEmail("TalentAcquisition", $templateId, $recipientEmail, $dynamic_data);
                        if ($result !== true) {
                            $emailWarning = ' (Email could not be sent)';
                            \Log::warning("Interview email failed: " . $result);
                        }
                    } catch (\Exception $e) {
                        $emailWarning = ' (Email could not be sent)';
                        \Log::warning("Interview email exception: " . $e->getMessage());
                    }

                    return response()->json([
                        'success' => true,
                        'message' => 'Interview Invitation Sent!' . $emailWarning,
                        'InterViewDate' => $InterViewDate,
                        'TodoDataview' => $TodoDataview,
                        'Final_response_data' => $FianlResponse
                    ]);

       DB::beginTransaction();
        try {  } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to upload data'], 500);
        }
    }

    public function ApprovedOrSortApplicantWiseStatus(Request $request)
    {

        $Resort_id =  $this->resort->resort_id;

        $resort_details = Resort::find($Resort_id); // Use find() instead of where()->get() for a single


        if (!$resort_details) {
            return response()->json(['error' => 'Resort not found'], 404);
        }
        $ApplicantID  =   base64_decode($request->ApplicantID);
        $applicantstatusid = $request->applicantstatusid;
        $Rank =  $request->Rank;


        $Progress_Rank = $request->Progress_Rank;
        $interviewRound = $request->interviewRound;

        if($interviewRound =="HOD Complete" || $interviewRound =="HOD Round")
        {
            $Approved_By =2;
        }
        elseif($interviewRound =="GM Complete" || $interviewRound =="GM Round" || $interviewRound =="select")
        {
            $Approved_By =8;
        }
        else
        {
            $Approved_By = $this->resort->GetEmployee->rank;
        }
        $ApplicantWiseStatus = ApplicantWiseStatus::find($applicantstatusid);

        if( $ApplicantWiseStatus->As_ApprovedBy==0)
        {
            $applicantstatusid=0;
        }
        if($ApplicantWiseStatus->As_ApprovedBy == 3 && $ApplicantWiseStatus->status == "Round" || $ApplicantWiseStatus->status == "Sortlisted" )
        {
            $applicantstatusid;
        }
        elseif($ApplicantWiseStatus->As_ApprovedBy == 3 && $ApplicantWiseStatus->status == "Complete")
        {
            $applicantstatusid=0;
        }
        elseif($ApplicantWiseStatus->As_ApprovedBy == 2 && $ApplicantWiseStatus->status == "Round" )
        {
            $applicantstatusid;
        }
        elseif($ApplicantWiseStatus->As_ApprovedBy == 2 &&  $ApplicantWiseStatus->status == "Complete")
        {
            $applicantstatusid=0;
        }
        elseif($ApplicantWiseStatus->As_ApprovedBy == 8 && $ApplicantWiseStatus->status == "Round" )
        {
            $applicantstatusid;
        }
        elseif($ApplicantWiseStatus->As_ApprovedBy == 8 &&  $ApplicantWiseStatus->status == "Complete")
        {
            $applicantstatusid;
        }

        DB::beginTransaction();
        try
        {
            ApplicantWiseStatus::updateOrCreate(['id'=>$applicantstatusid],[
                "Applicant_id"=>$ApplicantID,
                "As_ApprovedBy"=>$Approved_By,
                "status"=>$Rank,
            ]);
            DB::Commit();

            if($Rank =="Complete" || $Rank =="Rejected" || $Rank == "Selected")
            {
                $Final_response_data = Vacancies::join("applicant_form_data as t1", "t1.Parent_v_id", "=", "vacancies.id")
                ->join("countries as t2", "t2.id", "=", "t1.country")
                ->join('applicant_wise_statuses as t4', function ($join) {
                    $join->on('t4.Applicant_id', '=', 't1.id');
                })
                ->leftJoin('applicant_inter_view_details as t3', function ($join) {
                    $join->on('t3.Applicant_id', '=', 't1.id')
                        ->whereRaw('t3.id = (
                            SELECT MAX(id)
                            FROM applicant_inter_view_details
                            WHERE Applicant_id = t1.id
                        )');
                })
                ->join("resort_positions as t5", "t5.id", "=", "vacancies.position")
                ->join("resort_departments as t6", "t6.id", "=", "t5.dept_id")
                ->leftJoin("employees as emp", "emp.id", "=", "vacancies.reporting_to")
                ->leftJoin("resort_admins as ra", "ra.id", "=", "emp.Admin_Parent_id")
                ->where('t1.id',$ApplicantID)
                ->where('t4.id',$applicantstatusid)
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
                    t6.name as Department,
                    vacancies.required_starting_date,
                    vacancies.reporting_to,
                    ra.first_name as AdminFirstName,
                    ra.last_name as AdminLastName
                ')
                ->first();
                
                // dd($Final_response_data);
                
                $InterViewDate = $Final_response_data->InterViewDate
                    ? Carbon::parse($Final_response_data->InterViewDate)->format('d-m-Y')
                    : '-';
                $dynamic_data = [
                    'candidate_name' => $Final_response_data->first_name . ' ' . $Final_response_data->last_name,
                    'position_title' => $Final_response_data->Position,
                    'resort_name' => $resort_details->resort_name,
                    'interview_round' =>   $interviewRound,
                    'completion_date' => Carbon::now()->format('d-m-Y'),
                    'interview_date' => $InterViewDate,
                    'department' => $Final_response_data->Department ?? '',
                    'required_starting_date' => $Final_response_data->required_starting_date ?? '',
                    'reporting_to' => trim(($Final_response_data->AdminFirstName ?? '') . ' ' . ($Final_response_data->AdminLastName ?? '')),
                ];
                // dd($dynamic_data);

                // Send email using the BeyondTestApprovalEmail class
                $recipientEmail = $Final_response_data->Email;
                $templateId = $request->emailTemplateID;
                try {
                    $result = Common::sendTemplateEmail("TalentAcquisition",$templateId, $recipientEmail, $dynamic_data);
                    if ($result !== true) {
                        \Log::warning("Status update email failed: " . $result);
                    }
                } catch (\Exception $emailEx) {
                    \Log::warning("Status update email exception: " . $emailEx->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Request Updated!',
            ]);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Upload  data'], 500);
        }

    }
    public function RoundWiseForm(Request $request)
    {
        $ApplicantID  =   base64_decode($request->Applicant_id);

        $Comment         =  $request->Comment;
        DB::beginTransaction();
        try
        {
            $ApplicantWiseStatus = ApplicantWiseStatus::find($ApplicantID);
            $ApplicantWiseStatus->Comments  = $Comment;
            $ApplicantWiseStatus->save();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Comments Saved Succesfully!',

            ]);



        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Upload  data'], 500);
        }
    }

    public function destoryApplicant(Request $request)
    {
        $id = base64_decode($request->base64_id);

        DB::beginTransaction();
        try
        {
            $Applicant_form_data = Applicant_form_data::find($id);
            $Applicant_form_data->delete();
                DB::table('applicant_form_job_assessment')::where('applicant_form_id',$Applicant_form_data->id)->delete();
                DB::table('applicant_inter_view_details')::where('Applicant_id',$Applicant_form_data->id)->delete();
                DB::table('applicant_languages')::where('applicant_form_id',$Applicant_form_data->id)->delete();
                DB::table('education_applicant_form')::where('Applicant_id',$Applicant_form_data->id)->delete();
                DB::table('work_experience_applicant_form')::where('applicant_form_id',$Applicant_form_data->id)->delete();

                DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Applicant successfully removed from the Talent Pool!..',

            ]);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Upload  data'], 500);
        }



    }

    public function GetDateclickWiseUpcomingInterview(Request $request)
    {
        $date = $request->date ? Carbon::parse($request->date) : null;
        $resort_id = $request->Resort_id;
        $currentMonthStart = Carbon::now()->startOfMonth();
        $currentMonthEnd = Carbon::now()->endOfMonth();

        $config = config('settings.Position_Rank');

        $UplcomingApplicants1 = Vacancies::join("applicant_form_data as t1", "t1.Parent_v_id", "=", "vacancies.id")
            ->join("countries as t2", "t2.id", "=", "t1.country")
            ->join('applicant_wise_statuses as t4', function ($join) {
                $join->on('t4.Applicant_id', '=', 't1.id');
                // Uncomment and modify if needed
                // $join->whereRaw('t4.id = (
                //     SELECT MAX(id)
                //     FROM applicant_wise_statuses
                //     WHERE Applicant_id = t1.id
                // )');
            })
            ->whereIn('t4.status', ['Round'])
            ->join('applicant_inter_view_details as t3', function ($join) {
                $join->on('t3.Applicant_id', '=', 't1.id');
                // Uncomment and modify if needed
                // $join->whereRaw('t3.id = (
                //     SELECT MAX(id)
                //     FROM applicant_inter_view_details
                //     WHERE Applicant_id = t1.id
                // )');
            })
            ->join("resort_positions as t5", "t5.id", "=", "vacancies.position")
            ->join("resort_departments as t6", "t6.id", "=", "t5.dept_id")
            ->whereNotNull('t3.MeetingLink')
            ->where('t3.Status', 'Slot Booked');

        if ($date) {
            $UplcomingApplicants1->where('t3.InterViewDate', $date);
        } else {
            $UplcomingApplicants1->whereBetween('t3.InterViewDate', [$currentMonthStart, $currentMonthEnd])
            ->orderBy("InterViewDate","asc");
        }

        $UplcomingApplicants = $UplcomingApplicants1
            ->where('vacancies.Resort_id', $resort_id)
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
                t3.ResortInterviewtime
            ')
            ->get()

        ->map(function ($item) use($config) {
            $item->AppliedDate = Carbon::parse($item->Application_date)->format('d-m-Y');

            $item->InterViewDate = $item->InterViewDate ? Carbon::parse($item->InterViewDate)->format('d-m-Y') : '-';

            $item->MalidivanTime = $item->ResortInterviewtime ?? '-';

            $item->ApplicantTime = $item->ApplicantInterviewtime ?? '-';

            $item->InterviewStatus = $item->InterviewStatus ?? 'Slot Not Booked';
            $item->rank_name = $config[$item->ApprovedBy] ?? 'Unknown Rank';
            return $item;
            })->map(function ($applicant) use ($config) {
            // Map applicant fields
            $applicant->name = ucfirst($applicant->first_name . ' ' . $applicant->last_name);
            $applicant->RealInterViewDate =Carbon::parse($applicant->InterViewDate)->format('Y-m-d');
            $applicant->InterViewDate = Carbon::parse($applicant->InterViewDate)->format('d M');

            $applicant->rank_name = $config[$applicant->As_ApprovedBy] ?? 'Wisdom AI';
            $applicant->Notes = base64_encode($applicant->notes);
            $applicant->applicant_id = base64_encode($applicant->applicant_status_id);
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

            return $applicant;
        });

        $string ='';
        $dates =[];
        if($UplcomingApplicants->isNotEmpty())
        {
            foreach( $UplcomingApplicants as $u)
            {
                  if($u->passport_photo)
                    {
                    $getFileapplicant = Common::GetApplicantAWSFile($u->passport_photo);

                    $getFileapplicant =  $getFileapplicant['NewURLshow'];
                    }
                    else
                    {
                        $getFileapplicant = null;
                    }
                    $string .=  '<div class="upInterviews-block">
                                <div class="img-circle">
                                    <img src="'.$getFileapplicant.'" alt="image">
                                </div>
                                <div>
                                    <h6>'.$u->name.'</h6>
                                    <p>'.$u->Position.'</p>
                                    <span class="badge badge-theme">'.$u->Department.'</span>
                                </div>
                                <div>
                                    <div class="date">'.$u->InterViewDate.'</div>
                                    <div class="time">'.$u->ResortInterviewtime.'</div>
                                </div>
                            </div>';

                            array_push($dates, $u->RealInterViewDate);
            }
        }
        else
        {

            $string .='<div class="upInterviews-block">
                                <div style="text-align: left;" >
                                    No Record Found
                                </div>
                            </div>';
        }
        return response()->json([
            'success' => true,
            'message' => 'Applicant successfully removed from the Talent Pool!..',
            'view'=>$string,
            'dates'=>$dates,

        ]);
            // dd( $string);
    }
    public function TalentPool(Request $request)
    {
        if(Common::checkRouteWisePermission('resort.ta.TalentPool',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized action.');
        }
        $page_title="Talent Pool";
        $config = config('settings.Position_Rank');
        $resort_id =  $this->resort->resort_id;
        if($request->ajax())
        {
            $searchTerm = $request->get('searchTerm');
            $ResortDepartment = $request->get('ResortDepartment');
            $Positions = $request->get('Positions');

            $id1  = base64_decode($request->vacanccyId);
            $resort_Location =  Auth::guard('resort-admin')->user()->resort->resort_id;

            $Applicant_form_data1 = Applicant_form_data::join('applicant_wise_statuses as t1', function ($join) {
                $join->on('t1.Applicant_id', '=', 'applicant_form_data.id')
                    ->whereRaw('t1.id = (
                        SELECT MAX(id)
                        FROM applicant_wise_statuses
                        WHERE Applicant_id = applicant_form_data.id
                    )');
            })
            ->join('work_experience_applicant_form as t7', 't7.applicant_form_id', '=', 'applicant_form_data.id')
            ->leftJoin('countries as t2', 't2.id', '=', 'applicant_form_data.country')
            ->leftJoin('vacancies as t4', 't4.id', '=', 'applicant_form_data.Parent_v_id')
            ->leftJoin('resort_positions as t5', 't5.id', '=', 't4.position')
            ->leftJoin('resort_departments as t6', 't6.id', '=', 't5.dept_id')
            ->selectRaw('
                applicant_form_data.id,
                applicant_form_data.passport_no,
                t2.name as countryName,
                applicant_form_data.first_name,
                applicant_form_data.last_name,
                applicant_form_data.Total_Experiance as total_work_exp,
                applicant_form_data.email,
                applicant_form_data.Application_date,
                applicant_form_data.mobile_number as contact,
                applicant_form_data.employment_status,
                t1.status,
                t1.As_ApprovedBy,
                applicant_form_data.passport_photo,
                applicant_form_data.notes,
                t1.status as ApplicantStatus,
                t5.position_title as Position,
                t7.job_title,
                t1.id as applicant_status_id,
                t6.name as Department,
                applicant_form_data.data_retention_month,
                applicant_form_data.data_retention_year,
                applicant_form_data.AIRanking,
                applicant_form_data.Scoring,
                applicant_form_data.passport_img,
                applicant_form_data.curriculum_vitae,
                applicant_form_data.full_length_photo,
                t1.Comments
            ')
            ->whereIn('t1.status', ['Rejected', 'Rejected By Wisdom AI'])
            ->where('applicant_form_data.resort_id', $resort_id)
            ->orderBy('applicant_form_data.id', 'desc');

        // Apply filters
        if (!empty($searchTerm)) {
            $Applicant_form_data1->where(function ($query) use ($searchTerm) {
                $query->whereRaw("CONCAT(applicant_form_data.first_name, ' ', applicant_form_data.last_name) LIKE ?", ["%$searchTerm%"])
                    ->orWhere('applicant_form_data.email', 'like', "%$searchTerm%")
                    ->orWhere('t2.name', 'like', "%$searchTerm%")
                    ->orWhere('t5.position_title', 'like', "%$searchTerm%")
                    ->orWhere('t7.job_title', 'like', "%$searchTerm%")
                    ->orWhereRaw("CONCAT(applicant_form_data.Total_Experiance, ' ', 'year') LIKE ?", ["%$searchTerm%"])
                    ->orWhere('applicant_form_data.passport_no', 'like', "%$searchTerm%")
                    ->orWhere('applicant_form_data.mobile_number', 'like', "%$searchTerm%")
                    ->orWhere('t6.name', 'like', "%$searchTerm%");

                try {
                    $date = Carbon::createFromFormat('d-m-Y', $searchTerm)->format('Y-m-d');
                    $query->orWhere('applicant_form_data.Application_date', 'like', "%$date%");
                } catch (\Exception $e) {
                    // Ignore invalid date formats
                }
            });
        }

        // Apply department filter
        if (!empty($ResortDepartment)) {
            $Applicant_form_data1->where('t5.dept_id', $ResortDepartment);
        }

        // Apply position filter
        if (!empty($Positions)) {
            $Applicant_form_data1->where('t5.id', $Positions);
        }

        // Fetch data
        $Applicant_form_data = $Applicant_form_data1->get()->map(function ($applicant) use ($config, $resort_Location) {
            // Map fields for output
            $applicant->name = ucfirst($applicant->first_name . ' ' . $applicant->last_name);
            $applicant->Application_date = Carbon::parse($applicant->Application_date)->format('d-m-Y');
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
            $applicant->Notes = base64_encode($applicant->notes);
            $applicant->applicant_id = base64_encode($applicant->applicant_status_id);
            $applicant->ConsentExpiryDate = $applicant->data_retention_month . 'M/' . $applicant->data_retention_year . 'Y';

            return $applicant;
        });

                $reject_class = '';
                $delete_class = '';
                if(Common::checkRouteWisePermission('resort.ta.TalentPool',config('settings.resort_permissions.edit')) == false){
                    $reject_class = 'd-none';
                }
                if(Common::checkRouteWisePermission('resort.ta.TalentPool',config('settings.resort_permissions.delete')) == false){
                    $delete_class = 'd-none';
                }

            return datatables()->of($Applicant_form_data)
            ->addColumn('action', function ($row) use ($reject_class, $delete_class) {
                $id = base64_encode($row->id);

                return '
                <div class="dropdown table-dropdown">
                    <button class="btn btn-secondary dropdown-toggle dots-link" type="button" id="dropdownMenuButton'.$row->id.'" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-ellipsis"></i>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton'.$row->id.'">
                        <li><a class="dropdown-item userApplicants-btn" href="javascript:void(0)" data-id="'.$row->applicant_id.'"><i class="fa-solid fa-eye me-2"></i>View Profile</a></li>
                        <li><a class="dropdown-item RejactionReason '.$reject_class.'" data-Rank="'.$row->As_ApprovedBy.'" data-applicant_status_id="'.$row->applicant_status_id.'" data-Comments="'.$row->Comments.'" href="javascript:void(0)" data-id="'.$row->applicant_id.'"><i class="fa-solid fa-circle-info me-2"></i>Rejection Reason</a></li>
                        <li><a class="dropdown-item sendConsentRequestBtn" data-id="'.$id.'" href="javascript:void(0)"><i class="fa-solid fa-file-shield me-2"></i>Send Consent Request</a></li>
                        <li><a class="dropdown-item checkAvailabilityBtn" data-id="'.$id.'" href="javascript:void(0)"><i class="fa-solid fa-calendar-check me-2"></i>Check Availability</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger destoryApplicant '.$delete_class.'" data-location="'.$row->id.'" data-id="'.$row->id.'" href="javascript:void(0)"><i class="fa-solid fa-trash me-2"></i>Delete</a></li>
                    </ul>
                </div>';
            })
            ->addColumn('details-control', function ($row) {
                return '<a class="a-link collapsed" data-bs-toggle="collapse" data-bs-target="#collapseRow'.$row->id.'" aria-expanded="false" aria-controls="collapseRow'.$row->id.'">Interview Details</a>';
            })
            ->addColumn('Stage', function ($row) {

                $string ='';
                if($row->As_ApprovedBy == 0)
                {
                    $string ='<span class="badge badge-themeSkyblue">' .$row->ApplicantStatus.'</span>';
                }
                elseif($row->As_ApprovedBy == 3 &&  $row->ApplicantStatus  == 'Sortlisted')
                {
                    $string ='<span class="badge badge-themeBlue">'. $row->rank_name .' '. $row->ApplicantStatus.'</span>';
                }
                elseif($row->As_ApprovedBy == 3 &&  $row->ApplicantStatus  == 'Round' || $row->ApplicantStatus  == 'Complete')
                {
                     $string ='<span class="badge badge-themeBlue">'. $row->rank_name .' '. $row->ApplicantStatus.'</span>';
                }
                elseif($row->As_ApprovedBy ==2 &&  $row->ApplicantStatus  == 'Round' || $row->ApplicantStatus  == 'Complete')
                {
                    $string ='<span class="badge badge-themePurple">'. $row->rank_name .' '. $row->ApplicantStatus.'</span>';
                }
                elseif($row->As_ApprovedBy == 8 &&  $row->ApplicantStatus  == 'Round' || $row->ApplicantStatus  == 'Complete')
                {
                     $string =' <span class="badge badge-themePink">'. $row->rank_name .' '. $row->ApplicantStatus.'</span>';
                }
                elseif($row->As_ApprovedBy == 8 &&  $row->ApplicantStatus  == 'Selected' || $row->ApplicantStatus  == 'Complete')
                {
                         $string ='<span class="badge badge-themeSuccess">'. $row->rank_name .' '. $row->ApplicantStatus.'</span>';
                }
                elseif( $row->ApplicantStatus  == 'Rejected')
                {
                    $string ='<span class="badge badge-themeDanger">'. $row->rank_name .' '. $row->ApplicantStatus.'</span>';
                }
                return $string;
            })
            ->rawColumns(['action', 'details-control','Stage'])
            ->make(true);
        }
        $rank = $this->resort->GetEmployee->rank ?? null;
        $employeeDeptId = $this->resort->GetEmployee->Dept_id ?? null;
        $ResortDepartment = ResortDepartment::where("resort_id", $resort_id)->get();
        $EmailTamplete = TaEmailTemplate::where('Resort_id', $resort_id)->orderByDesc("id")->get();

        return view("resorts.talentacquisition.Applicants.talentpool",compact('page_title','ResortDepartment','rank','employeeDeptId','EmailTamplete'));

    }
    public function getTalentPoolGridApplicant(Request $request)
    {
        $config = config('settings.Position_Rank');
        $resort_id =  $this->resort->resort_id;
        $searchTerm = $request->get('searchTerm');
        $ResortDepartment = $request->get('ResortDepartment');
        $Positions = $request->get('Positions');

        $id1  = base64_decode($request->id);
        $resort_Location =  Auth::guard('resort-admin')->user()->resort->resort_id;
        $searchTerm = $request->searchTerm;
        $Applicant_form_data1 = Applicant_form_data::leftjoin('applicant_wise_statuses as t1', function ($join) {
            $join->on('t1.Applicant_id', '=', 'applicant_form_data.id')
                ->whereRaw('t1.id = (
                    SELECT MAX(id)
                    FROM applicant_wise_statuses
                    WHERE Applicant_id = applicant_form_data.id
                )');
        })
         ->leftjoin('work_experience_applicant_form as t7', function ($join) {
            $join->on('t7.applicant_form_id', '=', 'applicant_form_data.id')
                ->whereRaw('t1.id = (
                    SELECT MAX(id)
                    FROM applicant_wise_statuses
                    WHERE Applicant_id = applicant_form_data.id
                )');
        })
        ->leftJoin('work_experience_applicant_form as t3', 't3.id', '=', 'applicant_form_data.id')
        ->leftJoin('countries as t2', 't2.id', '=', 'applicant_form_data.country')
        ->leftJoin('vacancies as t4', 't4.id', '=', 'applicant_form_data.Parent_v_id')
        ->leftJoin('resort_positions as t5', 't5.id', '=', 't4.position')
        ->leftJoin('resort_departments as t6', 't6.id', '=', 't5.dept_id')
        ->selectRaw('
            applicant_form_data.id,
            applicant_form_data.passport_no,
            t2.name as countryName,
            applicant_form_data.first_name,
            applicant_form_data.last_name,
            t3.work_start_date,
            t3.work_end_date,
            applicant_form_data.Total_Experiance as total_work_exp,
            applicant_form_data.email,
            applicant_form_data.Application_date,
            applicant_form_data.mobile_number as contact,
            applicant_form_data.employment_status,
            t1.status,
            t1.As_ApprovedBy,
            applicant_form_data.passport_photo,
            applicant_form_data.notes,
            t1.status as ApplicantStatus,
            t5.position_title,
            t7.job_title,
            t1.id as applicant_status_id,
            t6.name as Department,
            applicant_form_data.data_retention_month,
            applicant_form_data.data_retention_year,
            applicant_form_data.AIRanking,
            applicant_form_data.Scoring,
            applicant_form_data.passport_img,
            applicant_form_data.curriculum_vitae,
            applicant_form_data.full_length_photo,
            t1.Comments
        ')
        ->whereIn('t1.status', ['Rejected','Rejected By Wisdom AI'])
        ->where('applicant_form_data.resort_id', $resort_id)
        ->orderBy('applicant_form_data.id', 'desc');

        if (!empty($searchTerm)) {
            $Applicant_form_data1->where(function ($query) use ($searchTerm) {
                $query->whereRaw("CONCAT(applicant_form_data.first_name, ' ', applicant_form_data.last_name) LIKE ?", ["%$searchTerm%"])
                    ->orWhere('applicant_form_data.email', 'like', "%$searchTerm%")
                    ->orWhere('t2.name', 'like', "%$searchTerm%")
                    ->orWhere('t5.position_title', 'like', "%$searchTerm%")
                    ->orWhere('t7.job_title', 'like', "%$searchTerm%")
                    ->orWhereRaw("CONCAT(applicant_form_data.Total_Experiance, ' ', 'year') LIKE ?", ["%$searchTerm%"])
                    ->orWhere('applicant_form_data.passport_no', 'like', "%$searchTerm%")
                    ->orWhere('applicant_form_data.mobile_number', 'like', "%$searchTerm%")
                    ->orWhere('t6.name', 'like', "%$searchTerm%");

                try {
                    $date = Carbon::createFromFormat('d-m-Y', $searchTerm)->format('Y-m-d');

                    $query->orWhere('applicant_form_data.Application_date', 'like', "%$date%");
                } catch (\Exception $e) {
                }
            });
        }

        if (!empty($ResortDepartment)) {
            $Applicant_form_data1->where('t5.dept_id', $ResortDepartment);
        }

        if (!empty($Positions)) {
            $Applicant_form_data1->where('t5.id', $Positions);
        }

        $Applicant_form_data = $Applicant_form_data1->paginate(10);

        $Applicant_form_data->getCollection()->transform(function ($applicant) use ($config, $resort_Location) {
            $config = config('settings.Position_Rank');
            $applicant->rank = $applicant->As_ApprovedBy; // Approved By field
            $applicant->name = ucfirst($applicant->first_name . ' ' . $applicant->last_name);
            $applicant->Application_date = Carbon::parse($applicant->Application_date)->format('d-m-Y');
            $applicant->rank_name = $config[$applicant->As_ApprovedBy] ?? 'Wisdom AI'; // Map rank_name from config
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
                $applicant->Notes = base64_encode($applicant->notes);
            // $applicant->applicant_id = base64_encode($applicant->id);
            $applicant->applicant_id=  base64_encode($applicant->applicant_status_id);

            return $applicant;
        });
            $pagination = $Applicant_form_data->links()->render();

            $view = view("resorts.talentacquisition.Applicants.talentpoolgird",compact('pagination','Applicant_form_data'))->render();
            return response()->json([
                'success' => true,
                'view'=>$view,


            ]);
    }
    public function RevertBack(Request $request)
    {
        DB::beginTransaction();
        try
        {
            $applicant_status_id = $request->applicant_status_id;
            $Applicant_wise_statuses = ApplicantWiseStatus::find($applicant_status_id)->update(['status'=>"Sortlisted By Wisdom AI"]);
            DB::commit();


            return response()->json([
                'success' => true,
                'message'=>"Revert Request Sent Successfully!",

            ]);

        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Upload  data'], 500);
        }
    }

    public function GetAwsFiles(Request $request)
    {
       
       
        $ApplicantID = base64_decode($request->id);
        $flag        = $request->flag;
        
        $wherefind = "";
        if($flag == "curriculum_vitae")
        {
            $wherefind = "curriculum_vitae";  
        }
        elseif($flag == "passport_img")
        {
            $wherefind = "passport_img";
        }
        elseif($flag == "passport_photo")
        {
            $wherefind = "passport_photo";
        }
        else
        {
            $wherefind = "full_length_photo";
        }

        $applicant = Applicant_form_data::where('id', $ApplicantID)->select($wherefind)->first();
        $getFileapplicant = Common::GetApplicantAWSFile($applicant->$wherefind);
        if($getFileapplicant['success'] == true)
        {
            return response()->json([
                'success' => true,
                'message' => 'File Retrieved Successfully!',
                'NewURLshow' => $getFileapplicant['NewURLshow'],
                'mimeType' => $getFileapplicant['mimeType'],
            ]);
        }
        else
        {
            return response()->json([
                'success' => false,
                'message' => 'File Not Found!',
            ]);
        }

    }

    public function GetAllAwsFiles(Request $request)
    {
        $ApplicantID = base64_decode($request->id);
        $fields = ['curriculum_vitae', 'passport_img', 'passport_photo', 'full_length_photo'];
        $applicant = Applicant_form_data::where('id', $ApplicantID)->select($fields)->first();

        if (!$applicant) {
            return response()->json(['success' => false, 'message' => 'Applicant Not Found!']);
        }

        $files = [];
        foreach ($fields as $field) {
            if (!empty($applicant->$field)) {
                $result = Common::GetApplicantAWSFile($applicant->$field);
                if ($result['success']) {
                    $files[] = [
                        'name' => $field,
                        'url' => $result['NewURLshow'],
                    ];
                }
            }
        }

        if (empty($files)) {
            return response()->json(['success' => false, 'message' => 'No files found!']);
        }

        return response()->json(['success' => true, 'files' => $files]);
    }

    public function RejectedApplicants(Request $request)
    {
        $page_title = "Rejected Applications";
        $EmailTamplete = TaEmailTemplate::where('Resort_id', $this->resort->resort_id)
            ->orderByDesc("id")
            ->get();
        return view("resorts.talentacquisition.Applicants.rejected", compact('EmailTamplete', 'page_title'));
    }

    public function GetRejectedApplicants(Request $request)
    {
        if ($request->ajax()) {
            $searchTerm = $request->get('searchTerm');
            $config = config('settings.Position_Rank');
            $resort_id = $this->resort->resort_id;

            $query = Applicant_form_data::join('applicant_wise_statuses as t1', function ($join) {
                    $join->on('t1.Applicant_id', '=', 'applicant_form_data.id')
                        ->whereRaw('t1.id = (SELECT MAX(id) FROM applicant_wise_statuses WHERE Applicant_id = applicant_form_data.id)');
                })
                ->leftJoin('countries as t2', 't2.id', '=', 'applicant_form_data.country')
                ->leftJoin('vacancies as t4', 't4.id', '=', 'applicant_form_data.Parent_v_id')
                ->leftJoin('resort_positions as t5', 't5.id', '=', 't4.position')
                ->leftJoin('resort_departments as t6', 't6.id', '=', 't5.dept_id')
                ->selectRaw('
                    applicant_form_data.id,
                    applicant_form_data.first_name,
                    applicant_form_data.last_name,
                    applicant_form_data.email,
                    applicant_form_data.passport_photo,
                    t2.name as countryName,
                    t5.position_title,
                    t6.name as department,
                    t1.status as ApplicantStatus,
                    t1.Comments as rejection_reason,
                    t1.updated_at as rejection_date,
                    t1.id as applicant_status_id
                ')
                ->whereIn('t1.status', ['Rejected', 'Rejected By Wisdom AI'])
                ->where('applicant_form_data.resort_id', $resort_id)
                ->orderBy('t1.updated_at', 'desc');

            if (!empty($searchTerm)) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->whereRaw("CONCAT(applicant_form_data.first_name, ' ', applicant_form_data.last_name) LIKE ?", ["%$searchTerm%"])
                        ->orWhere('applicant_form_data.email', 'like', "%$searchTerm%")
                        ->orWhere('t2.name', 'like', "%$searchTerm%")
                        ->orWhere('t5.position_title', 'like', "%$searchTerm%")
                        ->orWhere('t6.name', 'like', "%$searchTerm%");
                });
            }

            $data = $query->get()->map(function ($item) {
                $item->name = ucfirst($item->first_name . ' ' . $item->last_name);
                $item->rejection_date = $item->rejection_date ? Carbon::parse($item->rejection_date)->format('d-m-Y') : '-';
                $item->applicant_id = base64_encode($item->applicant_status_id);
                if ($item->passport_photo) {
                    $getFile = Common::GetApplicantAWSFile($item->passport_photo);
                    $item->profileImg = $getFile['NewURLshow'];
                } else {
                    $item->profileImg = null;
                }
                return $item;
            });

            return datatables()->of($data)
                ->addColumn('action', function ($row) {
                    return '<a href="javascript:void(0)" class="btn btn-sm btn-themeBlue me-1 userApplicants-btn" data-id="' . $row->applicant_id . '" data-bs-toggle="tooltip" title="View"><i class="fa-solid fa-eye"></i></a>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }

    public function ReviewReminders(Request $request)
    {
        $page_title = "Review Reminders";
        return view("resorts.talentacquisition.Applicants.reviewreminders", compact('page_title'));
    }

    public function GetReviewReminders(Request $request)
    {
        if ($request->ajax()) {
            $resort_id = $this->resort->resort_id;

            $query = ApplicantInterViewDetails::join('applicant_form_data as t1', 't1.id', '=', 'applicant_inter_view_details.Applicant_id')
                ->leftJoin('vacancies as t4', 't4.id', '=', 't1.Parent_v_id')
                ->leftJoin('resort_positions as t5', 't5.id', '=', 't4.position')
                ->leftJoin('resort_departments as t6', 't6.id', '=', 't5.dept_id')
                ->where('applicant_inter_view_details.Status', 'Slot Booked')
                ->where('applicant_inter_view_details.InterViewDate', '>=', Carbon::today())
                ->where('applicant_inter_view_details.InterViewDate', '<=', Carbon::today()->addDays(7))
                ->where('applicant_inter_view_details.resort_id', $resort_id)
                ->selectRaw('
                    t1.id as applicant_id,
                    t1.first_name,
                    t1.last_name,
                    t1.passport_photo,
                    t5.position_title,
                    t6.name as department,
                    applicant_inter_view_details.InterViewDate,
                    applicant_inter_view_details.ResortInterviewtime,
                    applicant_inter_view_details.ApplicantInterviewtime,
                    applicant_inter_view_details.MeetingLink,
                    applicant_inter_view_details.id as interview_id
                ')
                ->orderBy('applicant_inter_view_details.InterViewDate', 'asc')
                ->get()
                ->map(function ($item) {
                    $item->name = ucfirst($item->first_name . ' ' . $item->last_name);
                    $item->interview_date = Carbon::parse($item->InterViewDate)->format('d-m-Y');
                    $item->days_until = Carbon::today()->diffInDays(Carbon::parse($item->InterViewDate));
                    if ($item->passport_photo) {
                        $getFile = Common::GetApplicantAWSFile($item->passport_photo);
                        $item->profileImg = $getFile['NewURLshow'];
                    } else {
                        $item->profileImg = null;
                    }
                    return $item;
                });

            return datatables()->of($query)
                ->addColumn('meeting', function ($row) {
                    if ($row->MeetingLink && $row->MeetingLink != '0') {
                        return '<a href="' . $row->MeetingLink . '" target="_blank" class="btn btn-sm btn-themeBlue"><i class="fa-solid fa-video me-1"></i>Join</a>';
                    }
                    return '<span class="badge bg-secondary">No Link</span>';
                })
                ->addColumn('days', function ($row) {
                    if ($row->days_until == 0) return '<span class="badge bg-danger">Today</span>';
                    if ($row->days_until == 1) return '<span class="badge bg-warning text-dark">Tomorrow</span>';
                    return '<span class="badge bg-info text-white">' . $row->days_until . ' days</span>';
                })
                ->rawColumns(['meeting', 'days'])
                ->make(true);
        }
    }

    public function sendOfferLetter(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'applicant_id' => 'required',
            'email_template_id' => 'required',
            'offer_letter' => 'required|file|mimes:pdf|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $resort_id = $this->resort->resort_id;
        $applicant_id = base64_decode($request->applicant_id);
        $applicant_status_id = base64_decode($request->applicant_status_id);

        $applicant = Applicant_form_data::find($applicant_id);
        if (!$applicant) {
            return response()->json(['success' => false, 'message' => 'Applicant not found.'], 404);
        }

        DB::beginTransaction();
        try {
            // Upload file
            $file = $request->file('offer_letter');
            $filePath = $file->store('offer_letters/' . $resort_id, 'public');

            // Create offer contract record
            $token = Str::uuid()->toString();
            $offerContract = ApplicantOfferContract::create([
                'applicant_id' => $applicant_id,
                'applicant_status_id' => $applicant_status_id,
                'resort_id' => $resort_id,
                'type' => 'offer_letter',
                'file_path' => $filePath,
                'status' => 'Sent',
                'token' => $token,
                'email_template_id' => $request->email_template_id,
                'sent_by' => $this->resort->id,
            ]);

            // Update applicant status
            ApplicantWiseStatus::updateOrCreate(
                ['id' => $applicant_status_id],
                ['status' => 'Offer Letter Sent', 'As_ApprovedBy' => 8]
            );

            // Send email
            $resort = \App\Models\Resort::find($resort_id);
            $vacancy = Vacancies::join('resort_positions as t5', 't5.id', '=', 'vacancies.position')
                ->leftJoin('resort_departments as t6', 't6.id', '=', 't5.dept_id')
                ->where('vacancies.id', $applicant->Parent_v_id)
                ->selectRaw('t5.position_title, t6.name as department')
                ->first();

            $acceptUrl = route('resort.offer.letter.show', $token);
            $dynamic_data = [
                'candidate_name' => $applicant->first_name . ' ' . $applicant->last_name,
                'position_title' => $vacancy->position_title ?? '',
                'department' => $vacancy->department ?? '',
                'resort_name' => $resort->resort_name ?? '',
                'accept_link' => $acceptUrl,
                'reject_link' => $acceptUrl,
                'offer_letter_link' => $acceptUrl,
            ];

            $result = Common::sendTemplateEmail("TalentAcquisition", $request->email_template_id, $applicant->email, $dynamic_data);
            if ($result !== true) {
                \Log::warning("Offer letter email failed: " . $result);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Offer letter sent successfully!']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("sendOfferLetter error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to send offer letter.'], 500);
        }
    }

    public function sendContract(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'applicant_id' => 'required',
            'email_template_id' => 'required',
            'contract_file' => 'required|file|mimes:pdf|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $resort_id = $this->resort->resort_id;
        $applicant_id = base64_decode($request->applicant_id);
        $applicant_status_id = base64_decode($request->applicant_status_id);

        $applicant = Applicant_form_data::find($applicant_id);
        if (!$applicant) {
            return response()->json(['success' => false, 'message' => 'Applicant not found.'], 404);
        }

        DB::beginTransaction();
        try {
            $file = $request->file('contract_file');
            $filePath = $file->store('contracts/' . $resort_id, 'public');

            $token = Str::uuid()->toString();
            $contract = ApplicantOfferContract::create([
                'applicant_id' => $applicant_id,
                'applicant_status_id' => $applicant_status_id,
                'resort_id' => $resort_id,
                'type' => 'contract',
                'file_path' => $filePath,
                'status' => 'Sent',
                'token' => $token,
                'email_template_id' => $request->email_template_id,
                'sent_by' => $this->resort->id,
            ]);

            ApplicantWiseStatus::updateOrCreate(
                ['id' => $applicant_status_id],
                ['status' => 'Contract Sent', 'As_ApprovedBy' => 8]
            );

            $resort = \App\Models\Resort::find($resort_id);
            $vacancy = Vacancies::join('resort_positions as t5', 't5.id', '=', 'vacancies.position')
                ->leftJoin('resort_departments as t6', 't6.id', '=', 't5.dept_id')
                ->where('vacancies.id', $applicant->Parent_v_id)
                ->selectRaw('t5.position_title, t6.name as department')
                ->first();

            $contractUrl = route('resort.contract.show', $token);
            $dynamic_data = [
                'candidate_name' => $applicant->first_name . ' ' . $applicant->last_name,
                'position_title' => $vacancy->position_title ?? '',
                'department' => $vacancy->department ?? '',
                'resort_name' => $resort->resort_name ?? '',
                'accept_link' => $contractUrl,
                'reject_link' => $contractUrl,
                'contract_link' => $contractUrl,
            ];

            $result = Common::sendTemplateEmail("TalentAcquisition", $request->email_template_id, $applicant->email, $dynamic_data);
            if ($result !== true) {
                \Log::warning("Contract email failed: " . $result);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Contract sent successfully!']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("sendContract error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to send contract.'], 500);
        }
    }

    public function sendConsentRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'applicant_id' => 'required',
            'consent_expiry_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $applicant_id = base64_decode($request->applicant_id);
        $applicant = Applicant_form_data::find($applicant_id);
        if (!$applicant) {
            return response()->json(['success' => false, 'message' => 'Applicant not found.'], 404);
        }

        $token = Str::uuid()->toString();
        $applicant->update([
            'consent_status' => 'pending',
            'consent_expiry_date' => $request->consent_expiry_date,
            'consent_token' => $token,
        ]);

        // Send consent email
        $resort = \App\Models\Resort::find($this->resort->resort_id);
        $consentUrl = route('resort.consent.show', $token);

        $subject = "Data Retention Consent Request - " . ($resort->resort_name ?? '');
        $body = "
            <p>Dear " . ucfirst($applicant->first_name) . " " . ucfirst($applicant->last_name) . ",</p>
            <p>We would like to retain your profile data in our talent pool until <strong>" . Carbon::parse($request->consent_expiry_date)->format('d M Y') . "</strong>.</p>
            <p>Please review and submit your consent by clicking the link below:</p>
            <p><a href='{$consentUrl}' style='display:inline-block;padding:12px 32px;background:#004552;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;'>Review Consent Request</a></p>
            <p>Regards,<br>HR Team<br>" . ($resort->resort_name ?? '') . "</p>
        ";

        try {
            \App\Jobs\TaEmailSent::dispatch($applicant->email, $subject, ['mainbody' => $body]);
        } catch (\Exception $e) {
            \Log::warning("Consent email failed: " . $e->getMessage());
        }

        return response()->json(['success' => true, 'message' => 'Consent request sent successfully!']);
    }

    public function checkAvailability(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'applicant_id' => 'required',
            'email_template_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $applicant_id = base64_decode($request->applicant_id);
        $applicant = Applicant_form_data::find($applicant_id);
        if (!$applicant) {
            return response()->json(['success' => false, 'message' => 'Applicant not found.'], 404);
        }

        $resort = \App\Models\Resort::find($this->resort->resort_id);
        $dynamic_data = [
            'candidate_name' => $applicant->first_name . ' ' . $applicant->last_name,
            'resort_name' => $resort->resort_name ?? '',
            'meeting_link' => $request->meeting_link ?? '',
            'job_link' => $request->job_link ?? '',
        ];

        try {
            $result = Common::sendTemplateEmail("TalentAcquisition", $request->email_template_id, $applicant->email, $dynamic_data);
            if ($result !== true) {
                \Log::warning("Check availability email failed: " . $result);
            }
        } catch (\Exception $e) {
            \Log::warning("Check availability email exception: " . $e->getMessage());
        }

        return response()->json(['success' => true, 'message' => 'Availability check email sent!']);
    }

    public function deleteTalentPoolApplicant(Request $request)
    {
        $applicant_id = base64_decode($request->applicant_id);
        $applicant = Applicant_form_data::find($applicant_id);
        if (!$applicant) {
            return response()->json(['success' => false, 'message' => 'Applicant not found.'], 404);
        }

        if ($applicant->resort_id != $this->resort->resort_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        DB::beginTransaction();
        try {
            ApplicantWiseStatus::where('Applicant_id', $applicant_id)->delete();
            ApplicantInterViewDetails::where('Applicant_id', $applicant_id)->delete();
            ApplicantOfferContract::where('applicant_id', $applicant_id)->delete();
            $applicant->delete();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Applicant deleted successfully!']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("Delete talent pool applicant error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete applicant.'], 500);
        }
    }
}
