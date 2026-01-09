<?php

namespace App\Http\Controllers\Resorts\GrievanceAndDisciplinery;
use DB;
use URL;
use Auth;
use Carbon\Carbon;
use App\Models\Resort;
use App\Helpers\Common;
use App\Models\Employee;
use App\Models\ActionStore;
use App\Models\OffensesModel;
use Illuminate\Http\Request;
use App\Models\GrievanceCategory;
use App\Models\GrievanceSubcategory;
use App\Models\GrivanceSubmissionModel;
use App\Models\GrivanceSubmissionWitness;
use Illuminate\Support\Facades\Validator;

use App\Models\GrievanceCommitteeMemberChild;
use App\Models\GrievanceCommitteeMemberParent;
use App\Models\GrivanceInvestigationChildModel;
use App\Models\GrivanceInvestigationModel;
use App\Models\GrivanceKeyPerson;
use App\Http\Controllers\Controller;
use App\Events\ResortNotificationEvent;
class GrivanceController extends Controller
{    public $resort;
    public $reporting_to;
    protected $underEmp_id=[];
    public function __construct()
    {
        $this->resort = $resortId = auth()->guard('resort-admin')->user();
        if($this->resort->is_master_admin == 0){
            $this->reporting_to = isset($this->resort->GetEmployee) ? $this->resort->GetEmployee->id:3;
            $this->underEmp_id = Common::getSubordinates( $this->reporting_to);
        }
    }
    public function GrivanceIndex(Request $request)
    {

        if(Common::checkRouteWisePermission('GrievanceAndDisciplinery.grivance.GrivanceIndex',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized action.');
        }
        $page_title = 'Grievance List';
        $assinged_id = isset($this->resort->GetEmployee) ? $this->resort->GetEmployee->id:0;
        $current_rank = isset($this->resort->GetEmployee->rank) ? $this->resort->GetEmployee->rank: 3 ;
        $Rank = config('settings.Position_Rank');
    
        if (isset($Rank[$current_rank])) 
        {
            $rankKey = $Rank[ $current_rank];
        }

        if($this->resort->is_master_admin != 0){
            $rankKey = "HR";
        }

        $flag_to_show="";

        if($rankKey =="HOD")
        {
            $GrivanceSubmissionModel =  GrivanceSubmissionModel::with(['category','GetEmployee'])->join('grivance_investigation_models as t1','t1.Grievance_s_id',"=","grivance_submission_models.id")
                                                ->join('grievance_committee_member_parents as t2',"t2.id","=","t1.Committee_id")
                                                ->join('grievance_committee_member_children as t3',"t3.Parent_id","=","t2.id")
                                                ->where('grivance_submission_models.resort_id',$this->resort->resort_id)
                                                ->where('t3.Committee_Member_Id',$assinged_id)
                                                ->whereNotIn('grivance_submission_models.status',['resolved','rejected'])
                                                ->groupby('grivance_submission_models.id')
                                                ->where('grivance_submission_models.Assigned',"Yes")
                                                ->get(['grivance_submission_models.*']);
        }
        elseif($rankKey =="EXCOM")
        {
            $GrivanceSubmissionModel =  GrivanceSubmissionModel::with(['category','GetEmployee'])->join('grivance_investigation_models as t1','t1.Grievance_s_id',"=","grivance_submission_models.id")
            ->join('grievance_committee_member_parents as t2',"t2.id","=","t1.Committee_id")
            ->join('grievance_committee_member_children as t3',"t3.Parent_id","=","t2.id")
            ->where('grivance_submission_models.resort_id',$this->resort->resort_id)
            ->where('t3.Committee_Member_Id',$assinged_id)
            ->whereNotIn('grivance_submission_models.status',['resolved','rejected'])
            ->groupby('grivance_submission_models.id')
            ->get(['grivance_submission_models.*']);
        }
        elseif($rankKey =="HR")
        {
            $GrivanceSubmissionModel= GrivanceSubmissionModel::with(['category','GetEmployee'])
            ->whereNotIn('status',['resolved','rejected'])
            ->where('resort_id',$this->resort->resort_id) //show all and history of all the committe members
            ->get();
        }
        elseif($rankKey =="GM" || $rankKey =="MGR")
        {
            $GrivanceSubmissionModel= GrivanceSubmissionModel::with(['category','GetEmployee'])
            ->whereNotIn('status',['resolved','rejected'])
            ->where('resort_id',$this->resort->resort_id)
            ->where('SentToGM',"Yes") // To sent Gm Dashboard
            ->get();

        }
        elseif($rankKey =="GM")
        {
            $GrivanceSubmissionModel= GrivanceSubmissionModel::with(['category','GetEmployee'])
            ->whereNotIn('status',['resolved','rejected'])
            ->where('resort_id',$this->resort->resort_id)
            ->where('SentToGM',"Yes") // To sent Gm Dashboard
            ->get();

        }


        if($request->ajax())
        {

            $edit_class   = '';
            $delete_class = '';

            if(Common::checkRouteWisePermission('GrievanceAndDisciplinery.grivance.GrivanceIndex', config('settings.resort_permissions.create')) || Common::checkRouteWisePermission('GrievanceAndDisciplinery.grivance.GrivanceIndex', config('settings.resort_permissions.edit'))) {
                $edit_class = '';
            }
            else
            {
                $edit_class = 'd-none';
            }

            if (!Common::checkRouteWisePermission('GrievanceAndDisciplinery.grivance.GrivanceIndex', config('settings.resort_permissions.delete'))) {
                $delete_class = 'd-none';
            }

            return datatables()->of($GrivanceSubmissionModel)
            ->addColumn('Action', function ($row) use ($rankKey ,$edit_class, $delete_class) {
                $id = base64_encode($row->id);

                    $string='';
                if($row->SentToGM =="Yes" &&  $rankKey =="GM")
                {
                    $string='<a target="_blank" href="'. route('GrievanceAndDisciplinery.config.Investigationinfo',$id) .'" class="btn btn-success btn-lg-icon  me-1 edit-row-btn '.$edit_class.'" data-cat-id="' . e($id) . '">
                    <i class="fas fa-info"></i>
                    </a>';
                }
                elseif($row->SentToGM == "No")
                {
                    $string='<a target="_blank" href="'. route('GrievanceAndDisciplinery.config.Investigationinfo',$id) .'" class="btn btn-success btn-lg-icon  me-1 edit-row-btn '.$edit_class.'" data-cat-id="' . e($id) . '">
                    <i class="fas fa-info"></i>
                    </a>
                    <a target="_blank" href="'. route('GrievanceAndDisciplinery.config.Investigation',$id) .'" class="btn btn-success btn-lg-icon  me-1 edit-row-btn '.$delete_class.'" data-cat-id="' . e($id) . '">
                    <i class="fas fa-balance-scale"></i>
                    </a>';          
                }
                else
                {
                    $string='<a target="_blank" href="javascript:Void(0)">Waiting for GM Response</a>';

                }
                if(isset($row->Gm_Decision))
                {
                    $string.='<a target="_blank" href="javascript:Void(0)">GM Response : '.$row->Gm_Decision.'</a>';
                }
               
                 // <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" data-cat-id="' . e($id) . '">
                                //     <img src="' . asset("resorts_assets/images/edit.svg") . '" alt="Edit" class="img-fluid">
                                // </a>
                        return '
                            <div  class="d-flex align-items-center">
                                '.$string.'

                                <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn '.$delete_class.'" data-cat-id="' . e($id) . '">
                                    <img src="' . asset("resorts_assets/images/trash-red.svg") . '" alt="Delete" class="img-fluid">
                                </a>
                            </div>';
            })
            ->addColumn('Grievance_Id', function ($row) {
                return $row->Grivance_id;
            })
            ->addColumn('Grivance_CategoryName', function ($row) 
            {
                return $row->category->Category_Name;
            })
            ->addColumn('Grivance_EmployeeName', function ($row) 
            {
                return  $row->GetEmployee->resortAdmin->first_name.' '. $row->GetEmployee->resortAdmin->last_name;
            })
            ->addColumn('Status', function ($row) {
                if($row->status == "pending")
                {
                    return ucfirst($row->status);
                }
                elseif($row->status == "in_review")
                {
                    return "In Review";
                }
                else
                {
                    return ucfirst($row->status);
                }
            })
            ->addColumn('Confidentiality', function ($row) {
                if($row->Grivance_Submission_Type =="Yes")
                {
                    $Grivance_Submission_Type =  "Yes";
                }
                else if($row->Anonymous =="No")
                {
                    $Grivance_Submission_Type =  "No";
                } 
                else
                {
                    $Grivance_Submission_Type =  "NotApplicable";
                }
                return $Grivance_Submission_Type;
            })
            ->rawColumns(['Grivance_id','Category_Name','Employee_Name','Confidentiality','Status','Action'])
            ->make(true);
        }
        return view('resorts.GrievanceAndDisciplinery.grivance.index',compact('page_title'));
    }

    public function CreateGrivance()
    {
        $page_title="Create Grivanec Submission";
        $GrievanceCategory = GrievanceCategory::where("resort_id",$this->resort->resort_id)->get();
        $GrievanceSubcategory =  GrievanceSubcategory::where('resort_id',$this->resort->resort_id)->get();
        $Employee =  Employee::with(['resortAdmin'])->where('resort_id',$this->resort->resort_id)->get();
        return view('resorts.GrievanceAndDisciplinery.grivance.create',compact('page_title','GrievanceCategory','GrievanceSubcategory','Employee'));
    }

    public function GetEmployeeDetails(Request $request)
    {
        $emp_id = base64_decode($request->emp);
    
        
        try
        { 
            $Employee =  Employee::with(['resortAdmin','department','position'])->where('id',$emp_id)->first();

            $Employee->DepartmentName = $Employee->department->name;
            $Employee->PositionName = $Employee->position->position_title;
            $Superviser = Employee::with(['resortAdmin'])
                            ->where('id',$Employee->reporting_to)->first();
            $Superviser->Main_Name = $Superviser->resortAdmin->first_name.' '. $Superviser->resortAdmin->last_name;
            $data=[
                'Employee'=>$Employee,
                'Superviser'=>$Superviser
            ];
            return response()->json([
                    'success' => true,
                    'data' =>$data,
                ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Find Employee Details'], 500);
        }
    }

    public function GrievanceSubmiteStore(Request $request)
    {


        $path = config('settings.GrivanceAttachments');
        if($request->Confidential =="option1")
        {
            $Grivance_Submission_Type =  "Yes";
        }
        else if($request->Anonymous =="option2")
        {
            $Grivance_Submission_Type =  "No";
        } 
        else
        {
            $Grivance_Submission_Type =  "NotApplicable";
        }

      
        $GrivanceSubmission = GrivanceSubmissionModel::create([
                                            "Grivance_id"=>Common::getGriveanceID(),
                                            'Grivance_Cat_id'=>$request->Grivance_Cat_id,
                                            'Grivance_Sub_cat'=>$request->Grivance_Sub_cat,
                                            'Employee_id'=>base64_decode($request->Employee_id),
                                            'status'=>'pending',
                                            'date'=> date('Y-m-d',strtotime($request->date)),
                                            'Grivance_description'=>$request->Grivance_description,
                                            'Grivance_date_time'=>isset($request->Grivance_date_time) ? Carbon::parse($request->Grivance_date_time)->format('Y-m-d H:i:s'):date('Y-m-d H:i:s') ,
                                            'location'=>$request->location,
                                            'Grivance_Eexplination_description'=>$request->Grivance_Eexplination_description,
                                            'Grivance_Submission_Type'=>$Grivance_Submission_Type,
                                            'resort_id'=>$this->resort->resort_id,
                                            
                                            ]);

        $Path = $path."/".$this->resort->resort->resort_id."/".$GrivanceSubmission->Grivance_id;

        if($request->hasFile('Attachments'))
        {
            $collection =array();
            foreach($request->Attachments as $file)
            {
                $newsimg = $file->getClientOriginalName();
                $file->move($Path, $newsimg);
                $collection[]= $newsimg;
            }
            GrivanceSubmissionModel::where('Grivance_id', $GrivanceSubmission->Grivance_id)
            ->update(['Attachements' => implode(",", $collection)]);
        }
        if(!empty($request->witness_id))
        {
            foreach($request->witness_id as $v)
            {
                GrivanceSubmissionWitness::create(["Witness_id" => base64_decode($v),"G_S_Parent_id" => $GrivanceSubmission->id,'Wintness_Status'=>'Active']);
            } 
        }
       

        return response()->json([
            'success' => true,
            'message' => 'Grievance Created Successfully',
        ], 200);
    }
    public function InvestigationReport($id)
    {        
        
        $id = base64_decode($id);
        $page_title="Grievance Investigation Report";

        $assinged_id = isset($this->resort->GetEmployee) ? $this->resort->GetEmployee->id:0;
        $current_rank = isset($this->resort->GetEmployee->rank) ? $this->resort->GetEmployee->rank: 3 ;
        $Rank = config('settings.Position_Rank');
        $flag="";
        if (isset($Rank[$current_rank])) 
        {
            $rankKey = $Rank[ $current_rank];
        }
        if($rankKey =="HOD")
        {
                          
        }
        elseif($rankKey =="EXCOM")
        {

        }
        elseif($rankKey =="HR")
        {
        
        }
        $Grivance_Parent = GrivanceSubmissionModel::join('employees as t1',"t1.id","=","grivance_submission_models.Employee_id")
                        ->join('resort_admins as t2',"t2.id","=","t1.Admin_Parent_id")
                        ->join('resort_departments as t3',"t3.id","=","t1.Dept_id")
                        ->join('resort_positions as t4',"t4.id","=","t1.Position_id")
                        ->join('grievance_subcategories as t6',"t6.id","=","grivance_submission_models.Grivance_Sub_cat")
                        ->join('grievance_categories as t7',"t7.id","=","grivance_submission_models.Grivance_Cat_id")

                        ->leftjoin('action_stores as t5',"t5.id","=","grivance_submission_models.action_taken")
                        ->where("t1.resort_id",$this->resort->resort_id)
                        ->where("grivance_submission_models.id",$id)
                        ->first(['t7.Category_Name as CatName','t6.Sub_Category_Name as SubCatName','t5.ActionName','t2.personal_phone','t2.id as Parentid','t2.first_name','t2.last_name','t2.profile_picture','grivance_submission_models.*','t3.name as DepartmentName','t4.position_title as PositiontName']);
            $flag="CommitteeMode";
            $GrivanceSubmissionHistory =[];
            $GrivanceSubmissionHistory =  GrivanceInvestigationModel::join('grivance_investigation_child_models as t1',"t1.investigation_p_id","=","grivance_investigation_models.id")
                                                                ->join('employees as t2',"t2.id","=","t1.Committee_member_id")   
                                                                ->join('resort_admins as t3',"t3.id","=","t2.Admin_Parent_id")   
                                                                ->where('grivance_investigation_models.Grievance_s_id',$id)
                                                                ->get(['t3.first_name','t3.last_name','grivance_investigation_models.*','t1.*']);
        $GrivanceInvestigationModel = GrivanceInvestigationModel::where('Grievance_s_id',$id)->first();
        $FilePath = config('settings.GrievanceSubmission').'/'.$this->resort->resort->resort_id;

        $GrievanceCommitteeMemberParent = GrievanceCommitteeMemberParent::where('resort_id',$this->resort->resort_id)->get();
        $ActionStore = ActionStore::where('resort_id',$this->resort->resort_id)->get();
        
        $EveidanceFilePath = config('settings.GrievanceSubmission').'/'.$this->resort->resort->resort_id;
        $path = config('settings.GrivanceAttachments').'/'.$this->resort->resort->resort_id; 
        $GrivanceKeys = GrivanceKeyPerson::where('resort_id',$this->resort->resort_id)->get()->pluck('emp_ids')->toArray();
        $auth_id  = $this->resort->id;
        return view('resorts.GrievanceAndDisciplinery.grivance.investigationreport',compact('auth_id','GrivanceKeys','EveidanceFilePath','GrivanceInvestigationModel','flag','GrivanceSubmissionHistory','ActionStore','FilePath','page_title','Grivance_Parent','GrievanceCommitteeMemberParent','path'));
    }

    public function InvestigationReportStore(Request $request)
    {

        if($request->flag =="AssignToComittee")
        {
            $committee_ids =  $request->assign_to;
            $id =  $request->Grievant_form_id;

            $validator = Validator::make($request->all(), 
            [
                'assign_to'   => ['required',  'min:1'],
                'assign_to.*' => ['required', 'integer'], 
            ], [
                'assign_to.required' => 'At least one Committee  is required.',
                'assign_to.*.required' => 'Each Committee  must be provided.',
                'assign_to.*.integer'  => 'Each Committee  must be a valid integer ID.',
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
                    GrivanceInvestigationModel::create(['resort_id'=>$this->resort->resort_id,'Grievance_s_id'=>$id,'Committee_id'=> $committee_ids]);
                    GrivanceSubmissionModel::where('id',$id)->update(['Assigned'=>"Yes",'Committee_id'=>$committee_ids,'status'=>"in_review",]);
                    DB::commit();
                    $grievant_committee = GrievanceCommitteeMemberChild::where('Parent_id',$committee_ids)->get();
                    foreach($grievant_committee as $g)
                    {
                        $msg = 'HR has assigned a grievance case to your committee.';
                        $title = ' Grievance Case';
                        $ModuleName = "Grievance And Disciplinery ";
                        event(new ResortNotificationEvent(Common::nofitication($this->resort->resort_id, 10,$title,$msg,0,$g->Committee_Member_Id,$ModuleName)));
                    }
                    return response()->json([
                        'success' => true,
                        'message' => 'Grievance Committee Successfully Assinged',
                        'route'=> route('GrievanceAndDisciplinery.grivance.GrivanceIndex')
                    ], 200);
            }
            catch (\Exception $e)
            {
                DB::rollBack();
                \Log::emergency("File: " . $e->getFile());
                \Log::emergency("Line: " . $e->getLine());
                \Log::emergency("Message: " . $e->getMessage());
                return response()->json(['error' => 'Failed to Assinged Grievance Committee'], 500);
            }
    
        }
        elseif($request->flag =="EditModeForCommittee")
        {

            DB::beginTransaction();
            try
            {   
            // if($request->outcome_type =="DeliverToHr")
            // {
            //         GrivanceSubmissionModel::where('id',$request->Grievant_form_id)->update(['Assigned'=>"DeliverToHr"]);
            // }
            // elseif($request->outcome_type =="Resolved")
            // {
            //         GrivanceSubmissionModel::where('id',$request->Grievant_form_id)->update(['status'=>$request->STATUS,'action_taken'=>base64_decode($request->action_taken),'outcome_type'=>$request->outcome_type,'status'=>"resolved",'outcome_type'=>$request->outcome_type]);
            // }
            // else
            // {  
            //     $SentToGm = ($request->approval_request == "on") ?"Yes":"No";
            //     GrivanceSubmissionModel::where('id',$request->Grievant_form_id)->update(['status'=>$request->STATUS,'action_taken'=>base64_decode($request->action_taken),'SentToGM'=>$SentToGm,'outcome_type'=>$request->outcome_type]);
            // }

            // $file = $request->investigation_file;
            // $assinged_id  = isset($this->resort->GetEmployee) ? $this->resort->GetEmployee->id:0;
            // $current_rank = isset($this->resort->GetEmployee->rank) ? $this->resort->GetEmployee->rank: 3 ;
            // $grievance_id =  $request->Grievant_form_id;
            // $committee_id = Common::PartOfCommitteeMember( $assinged_id,$this->resort->resort_id);

            //     $file =  $request->investigation_file;
            //     $Files=array();
            //     if(isset($file))
            //     {
            //         foreach($file as $f)
            //         {
            //             $FilePath = config('settings.GrievanceSubmission').'/'.$this->resort->resort->resort_id;
            //             $f->move($FilePath, $f->getClientOriginalName());
            //             $Files[] = $f->getClientOriginalName();
            //         }
            //     }
            //     $gr_investigation = GrivanceInvestigationModel::where("Grievance_s_id",$request->Grievant_form_id)->first();
            //     if(isset($gr_investigation->investigation_files))
            //     {
            //         foreach(explode(",",$gr_investigation->investigation_files) as $f)
            //         {
            //             if(!in_array($f,$Files))
            //             {
            //                 $Files[] = $f;
            //             }                       
            //         }
            //     }
            //     $Files = (!empty($Files)) ? implode(',', $Files) : null ;
                 

            //     $gr_investigation->investigation_files=  $Files;
            //     $gr_investigation->inves_start_date=$request->invesigation_date;
            //     $gr_investigation->resolution_date=$request->resolution_date;
            //     $gr_investigation->save();
            //     foreach($request->inves_find_recommendations as $key => $value)
            //     {
            //         GrivanceInvestigationChildModel::create([
            //             'investigation_p_id'=>$request->Grievant_form_id,
            //             'follow_up_action'=>$request->follow_up_action[$key],
            //             'follow_up_description'=>$request->follow_up_description[$key],
            //             'inves_find_recommendations'=>$value,
            //             'investigation_stage'=>$request->investigation_stage[$key],
            //             'Committee_member_id'=> $assinged_id ,
            //             'resolution_note'=>$request->resolution_note[$key],
            //         ]);
            //     }
            
            if($request->outcome_type == "DeliverToHr") {
                GrivanceSubmissionModel::where('id', $request->Grievant_form_id)->update(['Assigned' => "DeliverToHr"]);
            } elseif($request->outcome_type == "Resolved") {
                GrivanceSubmissionModel::where('id', $request->Grievant_form_id)->update([
                    'status' => "resolved",
                    'action_taken' => base64_decode($request->action_taken),
                    'outcome_type' => $request->outcome_type
                ]);
            } else {  
                $SentToGm = ($request->approval_request == "on") ? "Yes" : "No";
                GrivanceSubmissionModel::where('id', $request->Grievant_form_id)->update([
                    'status' => $request->STATUS,
                    'action_taken' => base64_decode($request->action_taken),
                    'SentToGM' => $SentToGm,
                    'outcome_type' => $request->outcome_type
                ]);
            }
            
            // Process investigation files
            $file = $request->investigation_file;
            $assinged_id = isset($this->resort->GetEmployee) ? $this->resort->GetEmployee->id : 0;
            $current_rank = isset($this->resort->GetEmployee->rank) ? $this->resort->GetEmployee->rank : 3;
            $grievance_id = $request->Grievant_form_id;
            $committee_id = Common::PartOfCommitteeMember($assinged_id, $this->resort->resort_id);
            
            $file = $request->investigation_file;
            $Files = array();
            if(isset($file)) {
                foreach($file as $f) {
                    $FilePath = config('settings.GrievanceSubmission').'/'.$this->resort->resort->resort_id;
                    $f->move($FilePath, $f->getClientOriginalName());
                    $Files[] = $f->getClientOriginalName();
                }
            }
            // Get the investigation record and update it
            $gr_investigation = GrivanceInvestigationModel::where("Grievance_s_id", $request->Grievant_form_id)->first();
            if(isset($gr_investigation->investigation_files)) {
                foreach(explode(",", $gr_investigation->investigation_files) as $f) {
                    if(!in_array($f, $Files)) {
                        $Files[] = $f;
                    }                       
                }
            }
            $Files = (!empty($Files)) ? implode(',', $Files) : null;
            
            $gr_investigation->investigation_files = $Files;
            $gr_investigation->inves_start_date = $request->invesigation_date;
            $gr_investigation->resolution_date = $request->resolution_date;
            $gr_investigation->save();
            
            // Here's the fixed part - using the correct investigation model ID
            $investigation_id = $gr_investigation->id; 
            
            // Create investigation child records
            foreach($request->inves_find_recommendations as $key => $value) {
                GrivanceInvestigationChildModel::create([
                    'investigation_p_id' => $investigation_id, 
                    'follow_up_action' => $request->follow_up_action[$key],
                    'follow_up_description' => $request->follow_up_description[$key],
                    'inves_find_recommendations' => $value,
                    'investigation_stage' => $request->investigation_stage[$key],
                    'Committee_member_id' => $assinged_id,
                    'resolution_note' => $request->resolution_note[$key],
                ]);
            }
                         
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Grievance  Committee  Feed Back Updated Successfully',
                    'route'=> route('GrievanceAndDisciplinery.grivance.GrivanceIndex')
                ], 200);
                  }
            catch (\Exception $e)
            {
                DB::rollBack();
                \Log::emergency("File: " . $e->getFile());
                \Log::emergency("Line: " . $e->getLine());
                \Log::emergency("Message: " . $e->getMessage());
                return response()->json(['error' => 'Failed to Add Feed Back '], 500);
            }
        }
        else
        {
            DB::beginTransaction();
            try
            {
                $GrivanceSubmissionModel = GrivanceSubmissionModel::where("id",$request->Grievant_form_id)->first();
                $GrivanceSubmissionModel->Gm_Decision = $request->Gm_Decision;
                $GrivanceSubmissionModel->Rejection_reason = $request->Rejection_reason;
                $GrivanceSubmissionModel->SentToGM ='No';
                $GrivanceSubmissionModel->Gm_Resoan=$request->Gm_Resoan;
                $GrivanceSubmissionModel->save();

                                
                    DB::commit();
                    return response()->json([
                    'success' => true,
                    'message' => 'Grievance  Committee  Feed Back Updated Successfully',
                    'route'=> route('GrievanceAndDisciplinery.grivance.GrivanceIndex')
                ], 200);

            }
            catch (\Exception $e)
            {
                DB::rollBack();
                \Log::emergency("File: " . $e->getFile());
                \Log::emergency("Line: " . $e->getLine());
                \Log::emergency("Message: " . $e->getMessage());
            
            }
        }
    }
    public function Investigationinfo($id)
    {

       
        $id = base64_decode($id);
        $page_title="Grievance Investigation Report";

        $assinged_id = isset($this->resort->GetEmployee) ? $this->resort->GetEmployee->id:0;
        $current_rank = isset($this->resort->GetEmployee->rank) ? $this->resort->GetEmployee->rank: 3 ;
        $Rank = config('settings.Position_Rank');
        $flag="";
        if (isset($Rank[$current_rank])) 
        {
            $rankKey = $Rank[ $current_rank];
        }
       
      
            // $Grivance_Parent = GrivanceSubmissionModel::join('employees as t1',"t1.id","=","grivance_submission_models.Employee_id")
            // ->join('resort_admins as t2',"t2.id","=","t1.Admin_Parent_id")
            // ->join('resort_departments as t3',"t3.id","=","t1.Dept_id")
            // ->join('resort_positions as t4',"t4.id","=","t1.Position_id")
            // ->leftjoin('action_stores as t5',"t5.id","=","grivance_submission_models.action_taken")
            // ->where("t1.resort_id",$this->resort->resort_id)
            // ->where("grivance_submission_models.id",$id)
            // ->first(['t5.ActionName','t2.personal_phone','t2.id as Parentid','t2.first_name','t2.last_name','t2.profile_picture','grivance_submission_models.*','t3.name as DepartmentName','t4.position_title as PositiontName']);
            $Grivance_Parent = GrivanceSubmissionModel::join('employees as t1',"t1.id","=","grivance_submission_models.Employee_id")
                                                        ->join('resort_admins as t2',"t2.id","=","t1.Admin_Parent_id")
                                                        ->join('resort_departments as t3',"t3.id","=","t1.Dept_id")
                                                        ->join('resort_positions as t4',"t4.id","=","t1.Position_id")
                                                        ->join('grievance_subcategories as t6',"t6.id","=","grivance_submission_models.Grivance_Sub_cat")
                                                        ->join('grievance_categories as t7',"t7.id","=","grivance_submission_models.Grivance_Cat_id")
                                                        ->leftjoin('action_stores as t5',"t5.id","=","grivance_submission_models.action_taken")
                                                        ->where("t1.resort_id",$this->resort->resort_id)
                                                        ->where("grivance_submission_models.id",$id)
                                                        ->first(['t7.Category_Name as CatName','t6.Sub_Category_Name as SubCatName','t5.ActionName','t2.personal_phone','t2.id as Parentid','t2.first_name','t2.last_name','t2.profile_picture','grivance_submission_models.*','t3.name as DepartmentName','t4.position_title as PositiontName']);
            $flag="CommitteeMode";
            $GrivanceSubmissionHistory =[];
            $GrivanceSubmissionHistory =  GrivanceInvestigationModel::join('grivance_investigation_child_models as t1',"t1.investigation_p_id","=","grivance_investigation_models.Grievance_s_id")
                                                                ->join('employees as t2',"t2.id","=","t1.Committee_member_id")   
                                                                ->join('resort_admins as t3',"t3.id","=","t2.Admin_Parent_id")   
                                                                ->where('grivance_investigation_models.Grievance_s_id',$id)
                                                                ->get(['t3.first_name','t3.last_name','grivance_investigation_models.*','t1.*']);
      
                                                                  $GrivanceInvestigationModel = GrivanceInvestigationModel::where('Grievance_s_id',$id)->first();
        // $offence_models = OffensesModel::where("id",$Grivance_Parent->Grivance_offence_id)->first();
        $FilePath = config('settings.GrievanceSubmission').'/'.$this->resort->resort->resort_id;

        $GrievanceCommitteeMemberParent = GrievanceCommitteeMemberParent::where('resort_id',$this->resort->resort_id)->get();
        $ActionStore = ActionStore::where('resort_id',$this->resort->resort_id)->get();
        
        $EveidanceFilePath = config('settings.GrievanceSubmission').'/'.$this->resort->resort->resort_id;
        $path = config('settings.GrivanceAttachments').'/'.$this->resort->resort->resort_id; 

        $GrivanceKeys = GrivanceKeyPerson::where('resort_id',$this->resort->resort_id)->get()->pluck('emp_ids')->toArray();
        $auth_id  = $this->resort->id;
        return view('resorts.GrievanceAndDisciplinery.grivance.Investigationinfo',compact('GrivanceKeys','auth_id','rankKey','path','EveidanceFilePath','GrivanceInvestigationModel','flag','GrivanceSubmissionHistory','ActionStore','FilePath','page_title','Grivance_Parent','GrievanceCommitteeMemberParent'));

    }
    public function RequestIdentity(Request $request)
    {
        $id= $request->id;
        GrivanceSubmissionModel::where('id',$id)->update(['Request_Identity_Disclosure'=>"Yes"]);
        return response()->json([
            'success' => true,
            'message' => 'Grievance Identity Disclosure Requested Successfully',
        
        ], 200);
    }

    
    public function RequestForStatement(Request $request)
    {
        $id = $request->id;
        $parent_id = GrivanceSubmissionModel::where('Grivance_id',$id)->first();
        $parent_id->RequestforStatment = 'Yes';
        $parent_id ->save();
        $witness = GrivanceSubmissionWitness::where("G_S_Parent_id",$parent_id->id)->update(['status'=>"Requested"]);
        $witness = GrivanceSubmissionWitness::where("G_S_Parent_id",$parent_id->id)->get();
        foreach($witness as $g)
        {
            $msg = 'Please Give Your Statement for a grievance case No.'.$id;
            $title = ' Rrequest To give your Statement For Grievance';
            $ModuleName = "Grievance And Disciplinery ";
            event(new ResortNotificationEvent(Common::nofitication($this->resort->resort_id, 10,$title,$msg,0,$g->Witness_id,$ModuleName)));
        }
        return response()->json([
            'success' => true,
            'message' => ' Requested For Statement  Successfully',
        
        ], 200);
    }

    public function GetGrivanceSubCat(Request $request)
    {

        $GrievanceSubcategory =  GrievanceSubcategory::where("Grievance_Cat_id",$request->id)->where('resort_id',$this->resort->resort_id)->get(['id','Sub_Category_Name']);
     
        return response()->json([
            'success' => true,
            'data'=>$GrievanceSubcategory,
        
        ], 200);
    }
    public function HistoryAndLogs(Request $request)
    {
        
        $emp = $request->emp;    
        $search = $request->search;     
       
        
        if($request->ajax())
        {
    
                                        
            $GrivanceSubmissionModel = GrivanceSubmissionModel::
            join('employees as t1', 't1.id', '=', 'grivance_submission_models.Employee_id')
            ->join('resort_admins as t2', 't2.id', '=', 't1.Admin_Parent_id')
            ->join('resort_departments as t3', 't3.id', '=', 't1.Dept_id')
            ->leftJoin('resort_sections as t4', 't4.id', '=', 't1.Section_id') // corrected join
            ->join('resort_positions as t5', 't5.id', '=', 't1.Position_id')
            ->join('grievance_categories as t6', 't6.id', '=', 'grivance_submission_models.Grivance_Cat_id')
            ->where('grivance_submission_models.status', 'resolved')
            ->where('grivance_submission_models.resort_id', $this->resort->resort_id)

            // Filter by employee ID if provided
            ->when(!empty($emp), function ($query) use ($emp) {
                $query->where('grivance_submission_models.Employee_id', $emp);
            })

            // Filter by general search term
            ->when(!empty($search), function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('t2.first_name', 'like', "%{$search}%")
                    ->orWhere('t2.last_name', 'like', "%{$search}%")
                    ->orWhere('grivance_submission_models.Grivance_id', 'like', "%{$search}%")
                    ->orWhere('t3.name', 'like', "%{$search}%")
                    ->orWhere('t4.name', 'like', "%{$search}%")
                    ->orWhere('t5.position_title', 'like', "%{$search}%")
                    ->orWhere('t6.Category_Name', 'like', "%{$search}%")
                    ->orWhere('grivance_submission_models.Grivance_Eexplination_description', 'like', "%{$search}%");
                });
            })

            ->get([
                't2.first_name',
                't2.last_name', // Avoid duplicate column names
                't2.id as Admin_id',
                't3.name as DepartmentName',
                't4.name as SectionName',
                't5.position_title as PositionName',
                'grivance_submission_models.*',
                
                't6.Category_Name'
            ])
            ->map(function($ak) {
                $ak->profileImg = Common::getResortUserPicture($ak->Admin_id);
                return $ak;
            });          
                                  
            return datatables()->of($GrivanceSubmissionModel)
            ->addColumn('ID', function ($row) {
                return $row->Grivance_id;
            })
            ->addColumn('Employee_Name', function ($row) {
                $string = '<div class="tableUser-block">
                                <div class="img-circle">
                                    <img src="' . asset($row->profileImg) . '" alt="user">
                                </div>
                                <span>' . $row->first_name . ' ' . $row->last_name . '</span>
                            </div>';
                return $string;
            })
            ->addColumn('Department', function ($row) 
            {
                return $row->DepartmentName;
            })
            ->addColumn('Section', function ($row) {
            
                return isset($row->SectionName) ? $row->SectionName : '-';
            })
            ->addColumn('Position', function ($row) {
            
                return $row->PositionName;
            })
            ->addColumn('GrivanceName', function ($row) 
            {
                return $row->Category_Name;
            })

            ->addColumn('Note', function ($row) {
                return $row->Grivance_Eexplination_description;
            })
            ->addColumn('Status', function ($row) {
                return ucfirst($row->status);
            })
            ->addColumn('Action', function ($row)
            {
                
                return $string='<a href="javascript:void(0)" class="btn btn-themeSuccess btn-xs">Resolved</a>';
            })
            ->rawColumns(['ID','GrivanceName','Employee_Name','Department','Section','Note','Status','Action'])
            ->make(true);
        }
        $page_title="History And Logs";
        $Employee =  Employee::with(['resortAdmin'])->where('resort_id',$this->resort->resort_id)->get();
        return view('resorts.GrievanceAndDisciplinery.HistoryAndLogs.index',compact('page_title','Employee'));
    }


    public function GrivnanceDestory($id)
    {

       
            $id = base64_decode($id);

            DB::beginTransaction();
            try
            {
               
            
                    $investigations = GrivanceInvestigationModel::where("Grievance_s_id", $id)->get();
                    foreach ($investigations as $investigation) 
                    {
                        GrivanceInvestigationChildModel::where("investigation_p_id", $investigation->id)->delete();
                    }
                    GrivanceInvestigationModel::where("Grievance_s_id", $id)->delete();
                    GrivanceSubmissionWitness::where("G_S_Parent_id", $id)->delete();
                    GrivanceSubmissionModel::find($id)->delete();
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Grievance Deleted Successfully',
                ], 200);
            }
            catch (\Exception $e)
            {
                DB::rollBack();
                \Log::emergency("File: " . $e->getFile());
                \Log::emergency("Line: " . $e->getLine());
                \Log::emergency("Message: " . $e->getMessage());
                return response()->json(['error' => 'Failed to Delete Grievance '], 500);
            }
    }
}
