<?php

namespace App\Http\Controllers\Resorts\GrievanceAndDisciplinery;
use DB;
use phpseclib3\Math\BigInteger\Engines\PHP\Base;
use URL;
use Auth;
use Carbon\Carbon;
use App\Models\Resort;
use App\Helpers\Common;
use App\Models\Employee;
use App\Models\OffensesModel;
use App\Models\ActionStore;
use App\Models\SeverityStore;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\GrivanceSubmissionModel;
use App\Models\DisciplinaryCategoriesModel;
use App\Models\DisciplineryCommitteeMembers;
use App\Models\DisciplineryAssignCommittee;
use App\Models\disciplinarySubmit;
use App\Models\DisciplinaryWitness;
use App\Events\ResortNotificationEvent;
use App\Models\DisciplinaryInvestigationChild;
use App\Models\DisciplinaryInvestigationParent;
use App\Models\DisciplinaryEmailmodel;

class DisciplinaryController extends Controller
{

    public $resort;
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
    
    public function DisciplinaryIndex(Request $request)
    {

        if(Common::checkRouteWisePermission('GrievanceAndDisciplinery.grivance.GrivanceIndex',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }
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
            $DisciplinarySubmissionModel =  disciplinarySubmit::with(['category','offence','GetEmployee'])
                                        ->join('disciplinery_assign_committees as t2',"t2.id","=","disciplinary_submits.Committee_id")
                                        ->join('disciplinery_committee_members as t3',"t3.Parent_committee_id","=","t2.id")
                                        ->where('disciplinary_submits.resort_id',$this->resort->resort_id)
                                        ->where('t3.MemberId',$assinged_id)
                                        ->whereNotIn('disciplinary_submits.status',['resolved','rejected'])
                                        ->groupby('disciplinary_submits.id')
                                        ->where('disciplinary_submits.Assigned',"Yes")
                                        ->where('disciplinary_submits.SendtoHr',"No")
                                        
                                        ->get(['disciplinary_submits.*']);


        }
        elseif($rankKey =="EXCOM")
        {
            // Committee members
            $DisciplinarySubmissionModel =  disciplinarySubmit::with(['category','offence','GetEmployee'])
            ->join('disciplinery_assign_committees as t2',"t2.id","=","disciplinary_submits.Committee_id")
            ->join('disciplinery_committee_members as t3',"t3.Parent_committee_id","=","t2.id")
            ->where('disciplinary_submits.resort_id',$this->resort->resort_id)
            ->where('t3.MemberId',$assinged_id)
            ->whereNotIn('disciplinary_submits.status',['resolved','rejected'])
            ->groupby('disciplinary_submits.id')
            ->where('disciplinary_submits.Assigned',"Yes")
            ->get(['disciplinary_submits.*']);
        }
        elseif($rankKey =="HR" || $rankKey =="GM")
        {
            $DisciplinarySubmissionModel= disciplinarySubmit::with(['category','offence','GetEmployee'])
            ->whereNotIn('status',['resolved','rejected'])
            ->where('resort_id',$this->resort->resort_id) //show all and history of all the committe members
            ->get();

        }
           


        if($request->ajax())
        {
            $edit_class = '';
            $delete_class = '';
            if(Common::checkRouteWisePermission('GrievanceAndDisciplinery.grivance.GrivanceIndex',config('settings.resort_permissions.edit')) == false){
                $edit_class = 'd-none';
            }
            if(Common::checkRouteWisePermission('GrievanceAndDisciplinery.grivance.GrivanceIndex',config('settings.resort_permissions.delete')) == false){
                $delete_class = 'd-none';
            }

            return datatables()->of($DisciplinarySubmissionModel)
            ->addColumn('Action', function ($row) use ($rankKey,$edit_class,$delete_class) 
            {
                $id = base64_encode($row->id);
                    $string='';
                
                    $string='<a target="_blank" href="'. route('GrievanceAndDisciplinery.Disciplinary.Investigation',$id) .'" class="btn btn-success btn-lg-icon  me-1 edit-row-btn '.$edit_class.'" data-cat-id="' . e($id) . '">
                    <i class="fas fa-balance-scale"></i>
                    </a>';
                        return '<div  class="d-flex align-items-center">
                                '.$string.'
                             
                                <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn '.$delete_class.'" data-cat-id="' . e($id) . '">
                                    <img src="' . asset("resorts_assets/images/trash-red.svg") . '" alt="Delete" class="img-fluid">
                                </a>
                            </div>';
            })
            ->addColumn('Disciplinary_Id', function ($row)
            {
                return $row->Disciplinary_id;
            })
            ->addColumn('Category_name', function ($row) 
            {
                return ucfirst($row->category->DisciplinaryCategoryName);
            })
           
            ->addColumn('Offence', function ($row) 
            {
                return ucfirst($row->Offence->OffensesName);
            })
            ->addColumn('EmployeeName', function ($row) 
            {
              
                // return $row->GetEmployee;
                return $row->GetEmployee->resortAdmin->first_name.' '.$row->GetEmployee->resortAdmin->last_name;
            })
            ->addColumn('Status', function ($row) 
            {
                return ucfirst(str_replace('_', ' ', $row->status));
            })
            ->rawColumns(['Disciplinary_Id','Category_name','Offence','EmployeeName','Status','Action'])
            ->make(true);
        }
        
        $page_title="Disciplinary Index";
        return view('resorts.GrievanceAndDisciplinery.diciplinary.index',compact('page_title'));
    }
    public function CreateDisciplinary()
    {
        $page_title="Disciplinary Dashboard";
        $Employee =  Employee::with(['resortAdmin'])->where('resort_id',$this->resort->resort_id)->get();
        $DisciplinaryCategories = DisciplinaryCategoriesModel::where('resort_id',$this->resort->resort_id)->get();

        $Offenses =  OffensesModel::where('resort_id',$this->resort->resort_id)->get();
        $ActionStore = ActionStore::where('resort_id',$this->resort->resort_id)->get();
        $SeverityStore = SeverityStore::where('resort_id',$this->resort->resort_id)->get();
        $committiee = DisciplineryAssignCommittee::where('resort_id',$this->resort->resort_id)->get();

        return view('resorts.GrievanceAndDisciplinery.diciplinary.create',compact('page_title','Offenses','ActionStore','SeverityStore','DisciplinaryCategories','Employee','committiee'));
    }




    public function GetCategoryWiseOffence(Request $request)
    {
        $id = Base64_decode($request->id);
       
        $OffensesModel = OffensesModel::where("disciplinary_cat_id",$id)
                                        ->where('resort_id',$this->resort->resort_id)
                                        ->get(['id','OffensesName'])
                                        ->map(function ($item) {
                                            
                                            $item->newid = base64_encode($item->id);

                                            return $item;
                                        });

        return response()->json([
            'success' => true,
            'data' =>$OffensesModel,
        ], 200);   
    }


    public function StoreDisciplinary(Request $request)
    {
        $Employee_id = base64_decode($request->Employee_id);
        $Category_id = base64_decode($request->Category_id);
        $Offence_id =  base64_decode($request->Offence_id);
        $Action_id =  base64_decode($request->Action_id);
        $Severity_id =  base64_decode($request->Severity_id);
        $Expiry_date = $request->Expiry_date;
        $priority_level = $request->priority_level;
        $Incident_description = $request->incident_description;
        $committiee_id = $request->assign_to;
        $Request_For_Statement = ($request->Request_For_Statement == "on")? 'Yes':'No';
        $Attachment  = $request->attachment;
        $upload_signed_document  = $request->upload_signed_document;
        $witnessisapplicable =  count($request->select_witness) >  0 ?  "Yes":"No";
        $new_upload_signed_document ='';
        $emp = Employee::join("resort_admins as t1","t1.id","=","employees.Admin_Parent_id")
                        ->join("resort_departments as t2","t2.id","=","employees.Dept_id")
                        ->join("resort_positions as t3","t3.id","=","employees.Position_id")
                        ->where('employees.resort_id',$this->resort->resort_id)
                        ->where("employees.id",$Employee_id)
                        ->first(['t1.email','t1.first_name','t1.last_name','t2.name as DepartmentName','t3.position_title as PositionName']);
      
        $disciplinary_email =  DisciplinaryEmailmodel::where('resort_id',$this->resort->resort_id)->where( 'Action_id',$Action_id)->first();
       if(!isset(  $disciplinary_email ))
       {
         return response()->json(['error' => 'We are not found any email template for this action'], 500);
       } 
        
        $currentOffence = OffensesModel::where('resort_id',$this->resort->resort_id)->where("id",$Offence_id)->first('OffensesName');
        $Category = DisciplinaryCategoriesModel::where('resort_id',$this->resort->resort_id)->where("id",$Category_id)->first('DisciplinaryCategoryName');
             $disciplinarySubmit = disciplinarySubmit::create([
                                                            'resort_id'=>$this->resort->resort_id,
                                                            'Disciplinary_id'=>Common::getDisciplinaryID(),
                                                            'Employee_id'=>$Employee_id,
                                                            'Committee_id'=> $committiee_id,
                                                            'Category_id'=>$Category_id,
                                                            'Offence_id'=>$Offence_id ,
                                                            'Action_id'=>$Action_id ,
                                                            'Severity_id'=>$Severity_id,
                                                            'Expiry_date'=>  $Expiry_date,
                                                            'Incident_description'=> $Incident_description ,
                                                            'select_witness'=>$witnessisapplicable,
                                                            'Request_For_Statement'=> $Request_For_Statement,
                                                            'status'=>'In_Review',
                                                            'Priority'=> $priority_level,
                                                            'Assigned'=>'Yes',
                                                        ]);


          $path = config('settings.DisciplinaryAttachments');
          $Path = $path."/".$this->resort->resort->resort_id."/".$disciplinarySubmit->Disciplinary_id;
      
          if ($request->hasFile('attachment')) {
            $collection = [];
            foreach ($request->file('attachment') as $file) {
                // Keep original filename
                $filename = $file->getClientOriginalName();
                
                // Move the file to the destination path
                $file->move($Path, $filename);
                $collection[] = $filename;
            }
            
            disciplinarySubmit::where('Disciplinary_id', $disciplinarySubmit->Disciplinary_id)
                ->update(['Attachements' => implode(",", $collection)]);
        }
        
        if ($request->hasFile('upload_signed_document')) {
            $upload_signed_document = $request->file('upload_signed_document');
            $filename = $upload_signed_document->getClientOriginalName();
            
            $upload_signed_document->move($Path, $filename);
            
            disciplinarySubmit::where('Disciplinary_id', $disciplinarySubmit->Disciplinary_id)
                ->update(['upload_signed_document' => $filename]);
        }

        
        $members_ids  = DisciplineryCommitteeMembers::where("Parent_committee_id",$committiee_id)->get();

        foreach($members_ids as $g)
        {
            $msg = 'HR has assigned a grievance case to your committee.';
            $title = ' Disciplinary Case';
            $ModuleName = "Grievance And Disciplinery ";
            event(new ResortNotificationEvent(Common::nofitication($this->resort->resort_id, 10,$title,$msg,0,$g->MemberId,$ModuleName)));
        }
        if($witnessisapplicable =="Yes")
        {
            foreach($request->select_witness   as $id)
            {   

   
                $Wintness_Status = ($Request_For_Statement =="Yes") ?'Requested' : ''; 
                DisciplinaryWitness::create([
                                                "resort_id"=>$this->resort->resort_id,
                                                'Disciplinary_id'=>$disciplinarySubmit->Disciplinary_id,
                                                'Employee_id'=>base64_decode($id),
                                            ]);
            }
        }


        
        $dynamic_data = [
            "Case_ID"=> $disciplinarySubmit->Disciplinary_id,
            'candidate_name' => $emp->first_name . ' ' . $emp->last_name,
            'position_title' => $emp->PositionName,
            'resort_name' => $this->resort->resort->resort_name,
            'Department_title' =>  $emp->DepartmentName,
            'Category_name' =>   $Category->DisciplinaryCategoryName,
            'Offense' => $currentOffence->OffensesName,
            'Priority_Level'=> $priority_level,
            'Date_Submitted' => date('d-m-Y'),
            'Case_Description' => $Incident_description,
        ];

        // Send email using the BeyondTestApprovalEmail class
        $recipientEmail = $emp->email;
        $templateId = $disciplinary_email->id;


        $result = Common::sendTemplateEmail("Disciplinary",$templateId, $recipientEmail, $dynamic_data);
        
        return response()->json([
                                    'success' => true,
                                    'message' => 'Disciplinary Created Successfully',
                                ],200);
    
    }


    public function DisciplineryOpenOffence(Request $request)
    {
        $DisciplinarySubmissionModel= disciplinarySubmit::with(['category','offence','GetEmployee'])
        ->leftjoin('action_stores as t1', 't1.id', '=', 'disciplinary_submits.Action_id')
        ->where('disciplinary_submits.status','In_Review')
        ->where('disciplinary_submits.Employee_id',base64_decode($request->Employee_id))
        ->where('disciplinary_submits.resort_id',$this->resort->resort_id) //show all and history of all the committe members
        ->get(['t1.ActionName','disciplinary_submits.*']);
        if($request->ajax())
        {
            return datatables()->of($DisciplinarySubmissionModel)
            ->addColumn('Action', function ($row) 
            {
                return $row->ActionName;
            })
          
            ->addColumn('Category', function ($row) 
            {
                return ucfirst($row->category->DisciplinaryCategoryName);
            })
           
            ->addColumn('Offense', function ($row) 
            {
                return ucfirst($row->Offence->OffensesName);
            })
            ->addColumn('Date', function ($row) 
            {
               return $row->Expiry_date;
            })
           
            ->rawColumns(['Category','Offense','Date','Action'])
            ->make(true);
        }
    }
    public function DisciplineryInvestigation($id)
    {
        $id = base64_decode($id);

        $Disciplinary_parent= disciplinarySubmit::leftjoin('action_stores as t8', 't8.id', '=', 'disciplinary_submits.Action_id')
                                                    ->join('employees as t1',"t1.id","=","disciplinary_submits.Employee_id")
                                                    ->join('resort_admins as t2',"t2.id","=","t1.Admin_Parent_id")
                                                    ->join('resort_departments as t3',"t3.id","=","t1.Dept_id")
                                                    ->join('resort_positions as t4',"t4.id","=","t1.Position_id")
                                                    ->join('offenses_models as t6',"t6.id","=","disciplinary_submits.Offence_id")
                                                    ->join('disciplinary_categories_models as t7',"t7.id","=","disciplinary_submits.Category_id")
                                                    ->where("t1.resort_id",$this->resort->resort_id)
                                                    ->where("disciplinary_submits.id",$id)
                                                    ->where('disciplinary_submits.status','In_Review')
                                                    ->first(['t8.ActionName','t7.DisciplinaryCategoryName as  CatName','t6.OffensesName','t2.personal_phone','t2.id as Parentid','t2.first_name','t2.last_name','t2.profile_picture','disciplinary_submits.*','t3.name as DepartmentName','t4.position_title as PositiontName']);
       
        $page_title ="Disciplinary Investigation";
        $path = config('settings.DisciplinaryAttachments');
        $Path = $path."/".$this->resort->resort->resort_id."/".$Disciplinary_parent->Disciplinary_id;
        $committee_member_id =  Auth::guard('resort-admin')->user()->GetEmployee->id;
        $Rank = config('settings.Position_Rank');
        $parent = DisciplinaryInvestigationParent::join("employees as t1","t1.id","=","disciplinary_investigation_parents.Committee_member_id")
                                                    ->join("resort_admins as t2","t2.id","=","t1.Admin_Parent_id")
                                                    ->where("t1.resort_id",$this->resort->resort_id)
                                                    ->where("disciplinary_investigation_parents.Disciplinary_id",$Disciplinary_parent->Disciplinary_id)
                                                    ->get(['disciplinary_investigation_parents.*','t1.rank','t2.first_name','t2.last_name'])
                                                    ->map(function($ak) use($Rank){
                                                        if (isset($Rank[$ak->rank])) 
                                                        {
                                                            $ak->rank = $Rank[ $ak->rank];
                                                        }
                                                        return $ak;

                                                    });
        $id = $parent->pluck("id")->toArray();
        $child = DisciplinaryInvestigationChild::whereIn("Disciplinary_P_id",$id)->get(); 

        return view('resorts.GrievanceAndDisciplinery.diciplinary.investigationreport',compact('parent','child','page_title','Disciplinary_parent','Path','committee_member_id'));
    }
    public function RequestForStatement(Request $request)
    {
        $id = $request->id;
        $parent_id = disciplinarySubmit::where('Disciplinary_id',$id)->first();
        $parent_id->Request_For_Statement = 'Yes';
        $parent_id ->save();
        $witness = DisciplinaryWitness::where("Disciplinary_id",$parent_id->Disciplinary_id)->update(['Request_For_Statement'=>'Yes','Wintness_Status'=>"Requested"]);
        $witness = DisciplinaryWitness::where("Disciplinary_id",$parent_id->Disciplinary_id)->get();
        foreach($witness as $g)
        {
            $msg = 'Please Give Your Statement for a disciplinary case No.'.$id;
            $title = ' Rrequest To give your Statement For disciplinary';
            $ModuleName = "Grievance And Disciplinery ";
            event(new ResortNotificationEvent(Common::nofitication($this->resort->resort_id, 10,$title,$msg,0,$g->Employee_id,$ModuleName)));
        }
        return response()->json([
            'success' => true,
            'message' => ' Requested For Statement  Successfully',
        
        ], 200);
    }

    public function InvestigationReportStore(Request $request)
    {
       
            $id  = $request->Disciplinary_form_id;
            $committee_member_id  = $request->committee_member_id;
            $invesigation_date = $request->invesigation_date;
            $resolution_date = $request->resolution_date;
            $outcome_type = $request->outcome_type;
            $investigation_file =  $request->investigation_file;
            $resolution_note = $request->resolution_note;
            $investigation_stage = $request->investigation_stage;
            $follow_up_description = $request->follow_up_description;
            $follow_up_action  = $request->follow_up_action;
            $inves_find_recommendations =  $request->inves_find_recommendations;

            $file = $request->investigation_file;
            $Files = array();
            if(isset($file)) {
                foreach($file as $f) {
                    $FilePath = config('settings.GrievanceSubmission').'/'.$this->resort->resort->resort_id;
                    $f->move($FilePath, $f->getClientOriginalName());
                    $Files[] = $f->getClientOriginalName();
                }
            }
            
            // Check if gr_investigation is defined before using it
            if(isset($gr_investigation) && isset($gr_investigation->investigation_files)) {
                foreach(explode(",", $gr_investigation->investigation_files) as $f) {
                    if(!in_array($f, $Files)) {
                        $Files[] = $f;
                    }                       
                }
            }
            
            $Files = (!empty($Files)) ? implode(',', $Files) : null;
            
            $disciplinary_investigation = new DisciplinaryInvestigationParent();
            $disciplinary_investigation->resort_id = $this->resort->resort_id;
            $disciplinary_investigation->Disciplinary_id = $request->Disciplinary_form_id;
            $disciplinary_investigation->Committee_member_id = $committee_member_id;
            $disciplinary_investigation->invesigation_date = $request->invesigation_date;
            $disciplinary_investigation->resolution_date = $request->resolution_date;
            $disciplinary_investigation->investigation_file = $Files;
            $disciplinary_investigation->outcome_type = $request->outcome_type;
            $disciplinary_investigation->save();
            
            if($request->outcome_type == "DeliverToHr") {
                disciplinarySubmit::where("resort_id", $this->resort->resort_id)
                    ->where("Disciplinary_id", $id)
                    ->update(["SendtoHr" => "Yes"]);
            }

            if($request->STATUS == "resolved") {
                disciplinarySubmit::where("resort_id", $this->resort->resort_id)
                    ->where("Disciplinary_id", $id)
                    ->update(["status" => "resolved"]);
            }
            
            // Make sure the parent ID exists before creating child records
            if($disciplinary_investigation->id) 
            {

             foreach($request->inves_find_recommendations as $key => $value) 
             {

                        DisciplinaryInvestigationChild::create([
                            'Disciplinary_P_id' => $disciplinary_investigation->id,
                            'inves_find_recommendations' => $value,
                            'follow_up_action' => $request->follow_up_action[$key],
                            'follow_up_description' => $request->follow_up_description[$key],
                            'investigation_stage' => $request->investigation_stage[$key],
                            'resolution_note' => $request->resolution_note[$key],
                        ]);
                }
            }
                           

             DB::beginTransaction();
        try {DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Disciplinary Committee Feed Back Updated Successfully',
                'route' => route('GrievanceAndDisciplinery.Disciplinary.DisciplinaryIndex')
            ], 200);
        }
        catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Add Feed Back: ' . $e->getMessage()], 500);
        }
    }


  


}

