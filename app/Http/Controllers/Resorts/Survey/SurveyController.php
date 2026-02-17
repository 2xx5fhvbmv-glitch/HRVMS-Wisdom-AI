<?php

namespace App\Http\Controllers\Resorts\Survey;
use DB;
use Excel;
use Carbon\Carbon;
use App\Helpers\Common;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\ResortDepartment;
use App\Models\ParentSurvey;
use App\Models\SurveyQuestion;
use App\Models\SurveyEmployee;
use App\Events\ResortNotificationEvent;
use App\Exports\SurveyResultExport;
use App\Models\SurveyResult;
use Illuminate\Support\Facades\Http;
use App\Exports\SurveyDownloadQuestionAndAns;
class SurveyController extends Controller
{
    protected $resort;
    protected $underEmp_id=[];
    protected $newdates =[];

    public function __construct()
    {
        $this->resort = $resortId = auth()->guard('resort-admin')->user();
        if(!$this->resort) return;
        if($this->resort->GetEmployee)
        {
           $reporting_to = $this->resort->GetEmployee->id;
        }

        $this->newdates[]= Carbon::today()->format('Y-m-d');
        for($i=1; $i<=2; $i++)
        {
            $this->newdates[] =  Carbon::today()->addDays($i)->format('Y-m-d');
        }
    }
    public function index()
    {
        $emp = Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                            ->join('resort_positions as t2', "t2.id", "=", "employees.Position_id")
                            ->select(
                                't1.id as Parentid',
                                't1.first_name',
                                't1.last_name',
                                't1.profile_picture',
                                'employees.id as emp_id',
                                'employees.Emp_id as EmployeeId',
                                't2.position_title',
                            )
                            ->groupBy('employees.id')
                            ->where("t1.resort_id", $this->resort->resort_id)
                            
                            ->get()
                            ->map(function ($item) {
                                // dd(base64_encode($item->emp_id));
                                $item->Emp_id = base64_encode($item->emp_id);
                                $item->EmployeeName = ucfirst($item->first_name . ' ' . $item->last_name);
                                $item->Position = ucfirst($item->position_title);
                                $item->profileImg = Common::getResortUserPicture($item->Parentid);
                                return $item;
                            });
        $ResortDepartment = ResortDepartment::where('resort_id', $this->resort->resort_id)->get();

        $page_title = "Create Survey";
        return view('resorts.Survey.SurveyPages.create',compact('emp','page_title','ResortDepartment'));
    }


    public function SaveSurvey(Request $request)
    {
        
        DB::beginTransaction();
        try
        {
                $selectParticipants = $request->selectParticipants;
                $survey_privacy_type = $request->survey_privacy_type;
                $startDate  = Carbon::createFromFormat('d/m/Y', $request->startDate)->format('Y-m-d');
                $endDate  = Carbon::createFromFormat('d/m/Y', $request->endDate)->format('Y-m-d');

                $recurring_survey  = $request->recurring_survey;
                $reminderNotification = $request->reminderNotification;
                $minimum_responses = $request->minimum_responses;
                $surevey_editable = isset($request->surevey_editable)  ?  'yes' : 'No';
                $CheckBoxOption = $request->CheckBoxOption;
                $RadioOption = $request->RadioOption;
                $Emp_id = $request->Emp_id;
                $survey =ParentSurvey::create(["resort_id"=>$this->resort->resort_id,
                                            "Surevey_title"=>$request->survey_title,
                                            "Start_date" =>$startDate,
                                            "End_date" =>$endDate, 
                                            "Recurring_survey"=> $recurring_survey,        
                                            "Reminder_notification"=>$reminderNotification,
                                            "Min_response"=>  $minimum_responses,
                                            "Allow_edit"=>  $surevey_editable,
                                            "Status"=>  $request->Status,
                                            'survey_privacy_type'=>$survey_privacy_type
                                    ]);

                if (!empty($request->AddQuestion)) 
                {
                    foreach ($request->AddQuestion as $type => $questions) 
                    { 
                        foreach ($questions as $qIndex => $questionArray) 
                        {
                            foreach ($questionArray as $questionText) 
                            {
                
                                // Determine if the question is required
                                $question_is_required = 'no';
                                if (!empty($request->AddquestionReq) && array_key_exists($qIndex, $request->AddquestionReq)) 
                                {
                                    $question_is_required = $request->AddquestionReq[$qIndex][0] == 'on' ? 'yes' : 'no';
                                }
                                $ots = null;
                                if ($type == "multiple" && !empty($request->CheckBoxOption) && array_key_exists($qIndex, $request->CheckBoxOption)) 
                                {
                                    $ots = json_encode($request->CheckBoxOption[$qIndex]);
                                    $questionType = "Multi-Choice";
                                }
                                elseif ($type == "radio" && !empty($request->RadioOption) && array_key_exists($qIndex, $request->RadioOption)) 
                                {
                                    $ots = json_encode($request->RadioOption[$qIndex]);
                                    $questionType = "Single-Choice";
                                } 
                                elseif ($type == "Rating") 
                                {
                                    $questionType = "Rating";
                                }
                                else
                                {
                                    $questionType = "Text";
                                }
                
                                // Store the question in the database
                                SurveyQuestion::create([
                                    "Parent_survey_id" => $survey->id,
                                    "Question_Text" => $questionText,
                                    "Question_Type" => $questionType,
                                    "type" => $questionType,
                                    "Total_Option_Json" => $ots,
                                    "Question_Complusory" => $question_is_required,
                                ]);
                            }
                        }
                    }
                }
                

                $notificationTitle = ' Survey Request';
                $notificationMessage = "Survey  request for **'{$survey->Surevey_title}'** has been submitted for feedback.  
                                        **Dates:** {$startDate} to {$endDate}  
                                        **Please  participants.";
        
                $moduleName = "Survey";
                foreach($Emp_id as $e)
                {
                    $employeeId = (int)base64_decode($e);
                 
                    SurveyEmployee::create(["Emp_id"=>$employeeId,"Parent_survey_id"=> (int) $survey->id]);
                   
                    
                     event(new ResortNotificationEvent(Common::nofitication($this->resort->resort_id, 
                                                                            10,
                                                                            $notificationTitle,
                                                                            $notificationMessage,
                                                                            0,
                                                                            $employeeId,
                                                                            'Survey'
                                                                        )));
                }
            DB::commit();
            $route = route('Survey.view',base64_encode($survey->id));
            return response()->json(['success' => true, 'msg' => 'Ticket Agent added successfully.',"route"=>$route], 200);
        }
        catch( \Exception $e )
        {

            DB::rollBack();
            \Log::emergency("File: ".$e->getFile());
            \Log::emergency("Line: ".$e->getLine());
            \Log::emergency("Message: ".$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to add Agent Email.'], 500);

        }

     
    }

    public function SurveyView($id)
    {
         if(Common::checkRouteWisePermission('Survey.Surveylist',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }

        $id = base64_decode($id);

        $parent = ParentSurvey::join("resort_admins as t2","t2.id","=","parent_surveys.created_by")
                                ->join('employees as t1',"t1.Admin_Parent_id","=","t2.id")
                             
                                ->where("parent_surveys.id",$id)
                                ->where("parent_surveys.resort_id",$this->resort->resort_id)
                                ->first(['parent_surveys.*','t2.first_name','t2.last_name','t2.id as ParentId'] );
                                 $parent->EmployeeName = ucfirst($parent->first_name . ' ' .  $parent->last_name);
                                 $parent->profileImg = Common::getResortUserPicture($parent->Parentid);


        $Question  =     SurveyQuestion::where("Parent_survey_id",$id)->get();                             

        $participantEmp =  SurveyEmployee::join('employees as t1',"t1.id","=","survey_employees.Emp_id")
                                ->join("resort_admins as t2","t2.id","=","t1.Admin_Parent_id")
                                ->where("survey_employees.Parent_survey_id",$id)    
                                ->get(['t2.first_name','t2.last_name','t2.id as ParentId'] )
                                ->map(function($i){
                                    $i->EmployeeName = ucfirst($i->first_name . ' ' .  $i->last_name);
                                    $i->profileImg = Common::getResortUserPicture($i->Parentid);
                                    return $i;
                                });

        $page_title = "View  Survey";
        return view('resorts.Survey.SurveyPages.view',compact('page_title','parent','Question','participantEmp'));

    }

    public function Surveylist(Request $request)
    {
    
        if($request->ajax())
        {

            $search = $request->search;
            
            $searchDate = null;
            if (preg_match('/\d{2}-\d{2}-\d{4}/', $search)) {
                try {
                    $searchDate = Carbon::createFromFormat('d-m-Y', $search)->format('Y-m-d');
                } catch (\Exception $e) {
                    $searchDate = null;
                }
            }
            
            $ParentSurvey = ParentSurvey::whereIn('Status', ['Publish','OnGoing'])
                ->where('resort_id', $this->resort->resort_id)
                ->where(function ($query) use ($search, $searchDate) {
                    if (!empty($search)) 
                    {
                        $query->where('Surevey_title', 'LIKE', "%$search%")
                        ->orwhere('survey_privacy_type', 'LIKE', "%$search%");
                        
                        if ($searchDate) {
                            $query->orWhereRaw("STR_TO_DATE(Start_date, '%d/%m/%Y') LIKE ?", ["%$searchDate%"])
                                  ->orWhereRaw("STR_TO_DATE(End_date, '%d/%m/%Y') LIKE ?", ["%$searchDate%"]);
                        } else {
                            $query->orWhere('Start_date', 'LIKE', "%$search%")
                                  ->orWhere('End_date', 'LIKE', "%$search%");
                        }
                    }
                })
                ->get();
            
            $delete_class = '';
            if(Common::checkRouteWisePermission('Survey.Surveylist',config('settings.resort_permissions.delete')) == false){
                $delete_class = 'd-none';
            }

            return datatables()->of($ParentSurvey)
            ->addColumn('SurveyName', function ($row) {
                return $row->Surevey_title;
            })
            ->addColumn('NoOfApplicant', function ($row) {
                $count = SurveyEmployee::where("Parent_survey_id",$row->id)->count();
                $id = base64_encode($row->id); 
                return  '<a href="javascipt:void(0)" class="a-link showTotalapplicant" data-id="'.$id.'">'.$count.'</a>';
            })
            ->addColumn('Privacy', function ($row) {
                return $row->survey_privacy_type;
            })
           
            ->addColumn('StartDate', function ($row) {
                return  date('d-m-Y', strtotime($row->Start_date));
            })
            
            ->addColumn('EndDate', function ($row) {
                return  date('d-m-Y', strtotime($row->End_date));
            })
            ->addColumn('Action', function ($row) use ($delete_class) {
                $id = base64_encode($row->id);

                $view = route('Survey.view',$id);
                            return '
                            <div  class="d-flex align-items-center">
                                <a target="_blank" href="'.$view.'" class="btn-lg-icon icon-bg-skyblue"><img src="' . asset("resorts_assets/images/eye.svg") . '" alt="icon"></a>
                                <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn '.$delete_class.'" data-id="' . e($id) . '">
                                    <img src="' . asset("resorts_assets/images/trash-red.svg") . '" alt="Delete" class="img-fluid">
                                </a>
                            </div>';
                                                            // <a href="#" class="btn-lg-icon icon-bg-blue"><img src="' . asset("resorts_assets/images/copy.svg") . '" alt="icon"></a>


            })

            ->rawColumns(['SurveyName','NoOfApplicant','Privacy','StartDate','EndDate','Action'])
            ->make(true);
        }

        $page_title = "Survey List";
        return   view('resorts.Survey.SurveyPages.index',compact('page_title'));
    }

    public function SurveyDestory($id)
    {
        $id = base64_decode($id);
         DB::beginTransaction();
        try
        {
            ParentSurvey::find($id)->delete();
            SurveyQuestion::where("Parent_survey_id",$id)->delete();          
            SurveyEmployee::where("Parent_survey_id",$id)->delete();    
            
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Survey Delete Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Delete Survey'], 500);
        }
    }

    public function changeStatus(Request $request)
    {

        $id = base64_decode($request->id);
        $status = $request->status;
        DB::beginTransaction();
        try
        {
               ParentSurvey::where('id', $id)->update(['status' => $status]);
               DB::commit();
               return response()->json([
                   'success' => true,
                   'message' => 'Survey Status Updated Successfully',
               ], 200);
           }
           catch (\Exception $e)
           {
               DB::rollBack();
               \Log::emergency("File: " . $e->getFile());
               \Log::emergency("Line: " . $e->getLine());
               \Log::emergency("Message: " . $e->getMessage());
               return response()->json(['error' => 'Failed to  Status Updated  Survey'], 500);
           }

    }

    public function TotalApplicant($id)
    {

        $parent = ParentSurvey::join('survey_employees as t1',"t1.Parent_survey_id","=","parent_surveys.id")
                    ->join('employees as t2',"t2.id","=","t1.Emp_id")
                    ->join('resort_admins as t3',"t3.id","=","t2.Admin_Parent_id")
                    ->where("parent_surveys.id",base64_decode($id))
                    ->where('parent_surveys.resort_id',$this->resort->resort_id)

                    ->get(['t3.id as Parentid','t3.first_name','t3.last_name','t1.Emp_id'])
                    ->map(function($ak){

                        $ak->EmployeeName = ucfirst($ak->first_name . ' ' . $ak->last_name);
                        $ak->profileImg = Common::getResortUserPicture($ak->Parentid);
                        return $ak;
                    });

        $row='';    
        if($parent->isNotEmpty())
        {
            foreach($parent as $p)
            {

                
                    $row .='<div class="col-sm-6">
                                <div class="d-flex align-items-center employee-name-box">
                                    <div class="img-box">
                                        <img src="'.$p-> profileImg.'" alt="" class="img-fluid">
                                    </div>
                                    <a href="javascript:void(0)">'.$p-> EmployeeName.'</a>
                                </div>
                            </div>';
            }
        }
        else
        {
            $row .='<div class="col-sm-6">
                        <div class="d-flex align-items-center employee-name-box">
                            No Record Found..
                        </div>
                    </div>';
        }

        return $row;
    }


    public function CompleteSurvey(Request $request)
    {
        if(Common::checkRouteWisePermission('Survey.Surveylist',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }

        if($request->ajax())
        {

            $search = $request->search;
            
            // Attempt to convert search input to Y-m-d format if it's a valid date
            $searchDate = null;
            if (preg_match('/\d{2}-\d{2}-\d{4}/', $search)) {
                try {
                    $searchDate = Carbon::createFromFormat('d-m-Y', $search)->format('Y-m-d');
                } catch (\Exception $e) {
                    $searchDate = null;
                }
            }
            
            $ParentSurvey = ParentSurvey::whereIn('Status', ['Complete'])
                ->where('resort_id', $this->resort->resort_id)
                ->where(function ($query) use ($search, $searchDate) {
                    if (!empty($search)) {  
                        
                        $query->where('Surevey_title', 'LIKE', "%$search%")
                                ->orwhere('survey_privacy_type', 'LIKE', "%$search%"); 
                        if ($searchDate) {
                            $query->orWhereRaw("STR_TO_DATE(Start_date, '%d/%m/%Y') LIKE ?", ["%$searchDate%"])
                                  ->orWhereRaw("STR_TO_DATE(End_date, '%d/%m/%Y') LIKE ?", ["%$searchDate%"]);
                        } else {
                            $query->orWhere('Start_date', 'LIKE', "%$search%")
                                  ->orWhere('End_date', 'LIKE', "%$search%");
                        }
                    }
                })
                ->get();
            
            
            


            return datatables()->of($ParentSurvey)
            ->addColumn('SurveyName', function ($row) {
                return $row->Surevey_title;
            })
            ->addColumn('NoOfApplicant', function ($row) {
                $count = SurveyEmployee::where("Parent_survey_id",$row->id)->count();
                $id = base64_encode($row->id); 
                return  '<a href="javascipt:void(0)" class="a-link showTotalapplicant" data-id="'.$id.'">'.$count.'</a>';
            })
            ->addColumn('Privacy', function ($row) {
                return $row->survey_privacy_type;
            })
           
            ->addColumn('StartDate', function ($row) {
                return  date('d-m-Y', strtotime($row->Start_date));
            })
            
            ->addColumn('EndDate', function ($row) {
                return  date('d-m-Y', strtotime($row->End_date));
            })
            ->addColumn('Action', function ($row) {
                $id = base64_encode($row->id);

                $view = route('Survey.view',$id);
                            return '
                            <div  class="d-flex align-items-center">
                                <a target="_blank" href="'.$view.'" class="btn-lg-icon icon-bg-skyblue"><img src="' . asset("resorts_assets/images/eye.svg") . '" alt="icon"></a>
                            </div>';
                                                            // <a href="#" class="btn-lg-icon icon-bg-blue"><img src="' . asset("resorts_assets/images/copy.svg") . '" alt="icon"></a>


            })

            ->rawColumns(['SurveyName','NoOfApplicant','Privacy','StartDate','EndDate','Action'])
            ->make(true);
        }

        $page_title = "Complete Survey List";
        return   view('resorts.Survey.SurveyPages.CompleteSurvey',compact('page_title'));
    }


    public function DarftSurvey(Request $request)
    {
        if(Common::checkRouteWisePermission('Survey.Surveylist',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }

    
        if($request->ajax())
        {

            $search = $request->search;
            
            // Attempt to convert search input to Y-m-d format if it's a valid date
            $searchDate = null;
            if (preg_match('/\d{2}-\d{2}-\d{4}/', $search)) {
                try {
                    $searchDate = Carbon::createFromFormat('d-m-Y', $search)->format('Y-m-d');
                } catch (\Exception $e) {
                    $searchDate = null;
                }
            }
            
            $ParentSurvey = ParentSurvey::whereIn('Status', ['SaveAsDraft'])
                ->where('resort_id', $this->resort->resort_id)
                ->where(function ($query) use ($search, $searchDate) {
                    if (!empty($search)) {
                        $query->where('Surevey_title', 'LIKE', "%$search%")
                                ->orwhere('survey_privacy_type', 'LIKE', "%$search%");
                        if ($searchDate) {
                            $query->orWhereRaw("STR_TO_DATE(Start_date, '%d/%m/%Y') LIKE ?", ["%$searchDate%"])
                                  ->orWhereRaw("STR_TO_DATE(End_date, '%d/%m/%Y') LIKE ?", ["%$searchDate%"]);
                        } else {
                            $query->orWhere('Start_date', 'LIKE', "%$search%")
                                  ->orWhere('End_date', 'LIKE', "%$search%");
                        }
                    }
                })
                ->get();
            
            $delete_class = '';
           if(Common::checkRouteWisePermission('Survey.Surveylist',config('settings.resort_permissions.delete')) == false){
                $delete_class = 'd-none';
            }
            return datatables()->of($ParentSurvey)
            ->addColumn('SurveyName', function ($row) {
                return $row->Surevey_title;
            })
            ->addColumn('NoOfApplicant', function ($row) {
                $count = SurveyEmployee::where("Parent_survey_id",$row->id)->count();
                $id = base64_encode($row->id); 
                return  '<a href="javascipt:void(0)" class="a-link showTotalapplicant" data-id="'.$id.'">'.$count.'</a>';
            })
            ->addColumn('Privacy', function ($row) {
                return $row->survey_privacy_type;
            })
           
            ->addColumn('StartDate', function ($row) {
                return  date('d-m-Y', strtotime($row->Start_date));
            })
            
            ->addColumn('EndDate', function ($row) {
                return  date('d-m-Y', strtotime($row->End_date));
            })
            ->addColumn('Action', function ($row) use ($delete_class) {
                $id = base64_encode($row->id);

                $view = route('Survey.view',$id);
                            return '
                            <div  class="d-flex align-items-center">
                                <a target="_blank" href="'.$view.'" class="btn-lg-icon icon-bg-skyblue"><img src="' . asset("resorts_assets/images/eye.svg") . '" alt="icon"></a>
                                <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn '.$delete_class.'" data-id="' . e($id) . '">
                                    <img src="' . asset("resorts_assets/images/trash-red.svg") . '" alt="Delete" class="img-fluid">
                                </a>
                            </div>';
                                                            // <a href="#" class="btn-lg-icon icon-bg-blue"><img src="' . asset("resorts_assets/images/copy.svg") . '" alt="icon"></a>


            })

            ->rawColumns(['SurveyName','NoOfApplicant','Privacy','StartDate','EndDate','Action'])
            ->make(true);
        }

        $page_title = "Darft Survey";
        return   view('resorts.Survey.SurveyPages.DarftSurvey',compact('page_title'));
    }

    public function NotifyToParticipants(Request $request)
    {
        $id = base64_decode($request->id);
      
            $ParentSurvey = ParentSurvey::where('id', $id)->first();
            $notificationTitle = ' Survey Request';
            $notificationMessage = "Survey request for **'{$ParentSurvey->Surevey_title}'** has been submitted for feedback.  
                                    **Dates:** {$ParentSurvey->Start_date} to {$ParentSurvey->End_date}  
                                    **Please participate in the survey. If you have already participated, kindly ignore this message.**";
            
            $moduleName = "Survey";
            $SurveyEmployee = SurveyEmployee::where("Parent_survey_id",$ParentSurvey->id)->get();
            foreach($SurveyEmployee as $e)
            {
                event(new ResortNotificationEvent(Common::nofitication(
                    $this->resort->resort_id, 
                    10, 
                    $notificationTitle, 
                    $notificationMessage, 
                    'Survey', 
                    $e->Emp_id, 
                    $moduleName
                )));
                 // mobile Notification pending 

            }
         return response()->json([
                'success' => true,
                'message' => 'Survey Nofication Successfully Sent',
            ], 200);
            try{     }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Delete Survey'], 500);
        }
    }
    public function GetPendingParticipants(Request $request)
    {
        $id= base64_decode($request->id);
        $parent = ParentSurvey::join('survey_employees as t1',"t1.Parent_survey_id","=","parent_surveys.id")
        ->join('employees as t2',"t2.id","=","t1.Emp_id")
        ->join('resort_admins as t3',"t3.id","=","t2.Admin_Parent_id")
        ->where("parent_surveys.id",$id)
        ->where('parent_surveys.resort_id',$this->resort->resort_id)
        ->where('t1.emp_status','no')
        ->get(['t3.id as Parentid','t3.first_name','t3.last_name','t1.Emp_id'])
        ->map(function($ak){

            $ak->EmployeeName = ucfirst($ak->first_name . ' ' . $ak->last_name);
            $ak->profileImg = Common::getResortUserPicture($ak->Parentid);
            return $ak;
        });
         $row='';    
        if($parent->isNotEmpty())
        {
            foreach($parent as $p)
            {

                
                    $row .='<div class="col-sm-6">
                                <div class="d-flex align-items-center employee-name-box">
                                    <div class="img-box">
                                        <img src="'.$p-> profileImg.'" alt="" class="img-fluid">
                                    </div>
                                    <a href="javascript:void(0)">'.$p-> EmployeeName.'</a>
                                </div>
                            </div>';
            }
        }
        else
        {
            $row .='<div class="col-sm-6">
                        <div class="d-flex align-items-center employee-name-box">
                            No Record Found..
                        </div>
                    </div>';
        }

        return $row;
   
    }
    public function Getneartodeadlinesurvey(Request $request)
    {
        if(Common::checkRouteWisePermission('Survey.Surveylist',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }

        $search = $request->search;
        $searchDate = null;

        // Check if the search input is a valid date (format: dd-mm-yyyy)
        if (preg_match('/\d{2}-\d{2}-\d{4}/', $search)) {
            try {
                $searchDate = Carbon::createFromFormat('d-m-Y', $search)->format('Y-m-d');
            } catch (\Exception $e) {
                $searchDate = null;
            }
        }

        $ParentSurvey = ParentSurvey::join('survey_employees as t1', 't1.Parent_survey_id', '=', 'parent_surveys.id')
            ->join('employees as t2', 't2.id', '=', 't1.Emp_id')
            ->join('resort_admins as t3', 't3.id', '=', 't2.Admin_Parent_id')
            ->where('parent_surveys.resort_id', $this->resort->resort_id)
            ->where('t1.emp_status', 'no')
            ->where('parent_surveys.Status', 'OnGoing')
            ->whereIn('parent_surveys.End_date', $this->newdates) // 2 days before deadline
            ->select(
                'parent_surveys.id',
                'parent_surveys.Status',
                'parent_surveys.survey_privacy_type',
                'parent_surveys.Surevey_title as title',
                'parent_surveys.Start_date',
                'parent_surveys.End_date',
                DB::raw("SUM(CASE WHEN t1.emp_status = 'yes' THEN 1 ELSE 0 END) as completed_count"),
                DB::raw("SUM(CASE WHEN t1.emp_status = 'no' THEN 1 ELSE 0 END) as pending_count"),
                DB::raw("COUNT(t1.id) as total_count")
            )
            ->groupBy('parent_surveys.id') // Ensure correct grouping for aggregates
            ->where(function ($query) use ($search, $searchDate) {
                if (!empty($search)) {
                    $query->where('parent_surveys.Surevey_title', 'LIKE', "%$search%")
                        ->orWhere('parent_surveys.survey_privacy_type', 'LIKE', "%$search%");
                        
                    // Handle date search if a valid date is found
                    if ($searchDate) {
                        $query->orWhereDate('parent_surveys.Start_date', '=', $searchDate)
                            ->orWhereDate('parent_surveys.End_date', '=', $searchDate);
                    } else {
                        // If the user searches using an incorrect date format, fallback to LIKE search
                        $query->orWhere('parent_surveys.Start_date', 'LIKE', "%$search%")
                            ->orWhere('parent_surveys.End_date', 'LIKE', "%$search%");
                    }
                }
            })
            ->get();

        // dd( $ParentSurvey);
        if($request->ajax())
        {

            
            // Attempt to convert search input to Y-m-d format if it's a valid date
            $searchDate = null;
            if (preg_match('/\d{2}-\d{2}-\d{4}/', $search)) {
                try {
                    $searchDate = Carbon::createFromFormat('d-m-Y', $search)->format('Y-m-d');
                } catch (\Exception $e) {
                    $searchDate = null;
                }
            }
        
            return datatables()->of($ParentSurvey)
            ->addColumn('SurveyName', function ($row) {
                return $row->title;
            })
            ->addColumn('NoOfApplicant', function ($row) {
                $id = base64_encode($row->id); 
                return  '<a href="javascript:void(0)" id="PendingParticipants" class="a-link " data-id="'. $id.'">View Pending Participants</a>';
            })
            ->addColumn('Privacy', function ($row) {
                return $row->survey_privacy_type;
            })
           
            ->addColumn('StartDate', function ($row) {
                return  date('d-m-Y', strtotime($row->Start_date));
            })
            ->addColumn('EndDate', function ($row) {
                return  date('d-m-Y', strtotime($row->End_date));
            })
        
            ->rawColumns(['SurveyName','NoOfApplicant','Privacy','StartDate','EndDate'])
            ->make(true);
        }
        $page_title = "Surveys Nearing Deadline";
        return  view('resorts.Survey.SurveyPages.Getneartodeadlinesurvey',compact('page_title'));
    }
    
    public function GetSurveyResults($id)
    {
        if(Common::checkRouteWisePermission('Survey.Surveylist',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }
        $id = base64_decode($id);
        $ParentSurvey = ParentSurvey::join("resort_admins as t2","t2.id","=","parent_surveys.created_by")
                                ->join('employees as t1',"t1.Admin_Parent_id","=","t2.id")
                             
                                ->where("parent_surveys.id",$id)
                                ->where("parent_surveys.resort_id",$this->resort->resort_id)
                                ->first(['parent_surveys.*','t2.first_name','t2.last_name','t2.id as ParentId'] );
                                 $ParentSurvey->profileImg = Common::getResortUserPicture($ParentSurvey->Parentid);
                                 $ParentSurvey->EmployeeName = ucfirst($ParentSurvey->first_name . ' ' .  $ParentSurvey->last_name);
                                 $ParentSurvey->startDate = \Carbon\Carbon::parse($ParentSurvey->Start_date)->format('d M Y');
                                 $ParentSurvey->endDate = \Carbon\Carbon::parse($ParentSurvey->End_date)->format('d M Y');
    
    
   
        $Responed = SurveyEmployee::where("Parent_survey_id",$id)->get();
        $TotalResponed = $Responed->where('emp_status','yes')->count();
        $Min_responsed= $Responed->count();

        $totalHours = 0; // Initialize as numeric
        foreach ($Responed as $t) {
            if (empty($t->Complete_time)) continue; // Skip empty values

            try {
                // Detect format dynamically (try 24-hour format first)
                $time = Carbon::createFromFormat('H:i:s', $t->Complete_time);
            } catch (\Exception $e) {
                // If it fails, try 12-hour format (AM/PM)
                try {
                    $time = Carbon::createFromFormat('h:i:s A', $t->Complete_time);
                } catch (\Exception $e) {
                    // Skip invalid formats
                    continue;
                }
            }
        
            $totalHours += $time->hour + ($time->minute / 60) + ($time->second / 3600);
        }
        $hours = floor($totalHours);
        $minutes = round(($totalHours - $hours) * 60);
        $formattedTime = sprintf('%02d hours %02d mins', $hours, $minutes);

        $responseRate = ($TotalResponed  > 0) ? ($TotalResponed  / $Min_responsed) * 100 : 0;
   
        $ResponedEmp =  SurveyEmployee::join('employees as t1',"t1.id","=","survey_employees.Emp_id")
                                        ->join("resort_admins as t2","t2.id","=","t1.Admin_Parent_id")
                                        ->where("survey_employees.Parent_survey_id",$id)    
                                        ->where('survey_employees.emp_status','yes') 
                                        ->get(['t1.id as emp_id','t2.first_name','t2.last_name','t2.id as ParentId'] )
                                        ->map(function($i){
                                            $i->emp_id  = base64_encode($i->emp_id);
                                            $i->EmployeeName = ucfirst($i->first_name . ' ' .  $i->last_name);
                                            $i->profileImg = Common::getResortUserPicture($i->Parentid);
                                            return $i;
                                        });

        $page_title = "Survey Results";
        $id = base64_encode($id);
        return  view('resorts.Survey.SurveyPages.Result',compact('id','page_title','ResponedEmp','responseRate','formattedTime','TotalResponed','ParentSurvey'));

    }

    public function SurveyReultExport(Request $request)
    {
        $survey_id  = base64_decode($request->id);
        $respondent_id  = base64_decode($request->respondent);

        $ParentSurvey = ParentSurvey::join("resort_admins as t2","t2.id","=","parent_surveys.created_by")
                                    ->join('employees as t1',"t1.Admin_Parent_id","=","t2.id")
                                
                                    ->where("parent_surveys.id",$survey_id)
                                    ->where("parent_surveys.resort_id",$this->resort->resort_id)
                                    ->first(['parent_surveys.*','t2.first_name','t2.last_name','t2.id as ParentId'] );

            $Responed = SurveyEmployee::where("Parent_survey_id",$survey_id)->get();
            
            $TotalResponed = $Responed->where('emp_status','yes')->count();
            $Emp_id  = $Responed->where('emp_status','yes')->pluck('Emp_id')->toArray();

            $Min_responsed= $Responed->count();
    
            $totalHours = 0; // Initialize as numeric
            foreach ($Responed as $t) {
                if (empty($t->Complete_time)) continue; // Skip empty values
    
                try {
                    // Detect format dynamically (try 24-hour format first)
                    $time = Carbon::createFromFormat('H:i:s', $t->Complete_time);
                } catch (\Exception $e) {
                    // If it fails, try 12-hour format (AM/PM)
                    try {
                        $time = Carbon::createFromFormat('h:i:s A', $t->Complete_time);
                    } catch (\Exception $e) {
                        // Skip invalid formats
                        continue;
                    }
                }
            
                $totalHours += $time->hour + ($time->minute / 60) + ($time->second / 3600);
            }
            $hours = floor($totalHours);
            $minutes = round(($totalHours - $hours) * 60);
  
            $responseRate = ($TotalResponed  > 0) ? ($TotalResponed  / $Min_responsed) * 100 : 0;
            $surveyName =   ucfirst($ParentSurvey->Surevey_title)             ;
            $totalRespondents =  $TotalResponed ;
            $responseRate = $responseRate;
            $avgCompletionTime = sprintf('%02d hours %02d mins', $hours, $minutes);
        

            // $fecthQuesiton = ParentSurvey::join('survey_questions as t1',"t1.Parent_survey_id",'=',"parent_surveys.id")
            //                             ->join('survey_employees as t2',"t2.Parent_survey_id","=","parent_surveys.id")    
            //                             ->join('employees as t3',"t3.id","=","t2.Emp_id")
            //                             ->join("resort_admins as t4","t4.id","=","t3.Admin_Parent_id")
            //                             ->where('parent_surveys.id',$survey_id)
            //                             ->where('t2.emp_status','yes')
            //                             ->groupBy("t2.id")    
            //                             ->get(['t1.*','t2.id as Emp_id','t4.first_name','t4.last_name']);
                                            

            //                                 $i=1;
            //                             $array=[];
            // foreach($fecthQuesiton as $q)
            // {
            //     $surveyresult = SurveyResult::where("Parent_survey_id",$q->Parent_survey_id)->where("Survey_emp_ta_id",$q->Emp_id)->where("Question_id",$q->id)->first();
            //     $ans =  isset($surveyresult->Emp_Ans) ? $surveyresult->Emp_Ans:''; 
            //     $array[]=["id"=>$i,"ParticipantName"=>$q->first_name.' '.$q->last_name,"Question"=>$q->Question_Text,"Ans"=>$ans ];
            //     $i++;
            // }

            $fetchQuestions = ParentSurvey::join('survey_questions as t1', 't1.Parent_survey_id', '=', 'parent_surveys.id')
                            ->join('survey_employees as t2', 't2.Parent_survey_id', '=', 'parent_surveys.id')
                            ->join('employees as t3', 't3.id', '=', 't2.Emp_id')
                            ->join('resort_admins as t4', 't4.id', '=', 't3.Admin_Parent_id')
                            ->where('parent_surveys.id', $survey_id);
                            if($respondent_id !="All")
                            {
                                $fetchQuestions ->where("t3.id",$respondent_id);
                            }
                            $fetchQuestions =  $fetchQuestions->where('t2.emp_status', 'yes')
                            ->select('t1.id as question_id', 't1.Question_Text', 't2.id as Emp_id', 't4.first_name', 't4.last_name')
                            ->orderBy('t2.id') // Order by participant for clarity
                            ->get();

        $array = [];
        $i = 1;

        foreach ($fetchQuestions as $q) {
            // Check if the employee has answered the question
            $surveyResult = SurveyResult::where("Parent_survey_id", $survey_id)
                ->where("Survey_emp_ta_id", $q->Emp_id)
                ->where("Question_id", $q->question_id)
                ->first();

            $ans = isset($surveyResult->Emp_Ans) ? $surveyResult->Emp_Ans : '';

            $array[] = [
                "id" => $i,
                "ParticipantName" => $q->first_name . ' ' . $q->last_name,
                "Question" => $q->Question_Text,
                "Ans" => $ans
            ];
            
            $i++;
        }

            // dd($array);
        $data = $array;
    
        return Excel::download(new SurveyResultExport($surveyName, $totalRespondents, $responseRate, $avgCompletionTime, $data), 'SurveyResultExport.xlsx');


    }
    public function DownloadQuestionAndAns($id)
    {
        return Excel::download(new SurveyDownloadQuestionAndAns($id), 'survey data.xlsx');

    }
}
