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
        if(!$this->resort) return;
        if($this->resort->is_master_admin == 0){
            $this->reporting_to = isset($this->resort->GetEmployee) ? $this->resort->GetEmployee->id:3;
            $this->underEmp_id = Common::getSubordinates( $this->reporting_to);
        }
    }
    
    public function DisciplinaryIndex(Request $request)
    {

        // Was checking the grievance route name — meant a user who was given
        // explicit permission for "Disciplinary Index" still got 403 because
        // the lookup was against the wrong route. Use the disciplinary route.
        if(Common::checkRouteWisePermission('GrievanceAndDisciplinery.Disciplinary.DisciplinaryIndex',config('settings.resort_permissions.view')) == false){
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
        // Default empty collection so $DisciplinarySubmissionModel is always
        // defined even for ranks not handled below.
        $DisciplinarySubmissionModel = collect();
        $rankKey = $rankKey ?? null;

        if($rankKey =="HOD" || $rankKey =="EXCOM")
        {
            // Committee members see cases assigned to a committee they
            // belong to (status not resolved/rejected). They also see
            // cases they created themselves so a creator never loses
            // visibility of their own submission, even when it's
            // unassigned (Committee_id IS NULL).
            // Cases delivered to HR (SendtoHr=Yes) stay visible — the
            // committee member who handled the case still needs a record
            // of it on their dashboard.
            $loginAdminId = $this->resort->id;

            $committeeCaseIds = disciplinarySubmit::query()
                ->join('disciplinery_assign_committees as t2', 't2.id', '=', 'disciplinary_submits.Committee_id')
                ->join('disciplinery_committee_members as t3', 't3.Parent_committee_id', '=', 't2.id')
                ->where('disciplinary_submits.resort_id', $this->resort->resort_id)
                ->where('t3.MemberId', $assinged_id)
                ->whereNotIn('disciplinary_submits.status', ['resolved', 'rejected'])
                ->where('disciplinary_submits.Assigned', 'Yes')
                ->pluck('disciplinary_submits.id');

            $DisciplinarySubmissionModel = disciplinarySubmit::with(['category', 'offence', 'GetEmployee'])
                ->where('disciplinary_submits.resort_id', $this->resort->resort_id)
                ->whereNotIn('disciplinary_submits.status', ['resolved', 'rejected'])
                ->where(function ($q) use ($committeeCaseIds, $loginAdminId) {
                    $q->whereIn('disciplinary_submits.id', $committeeCaseIds)
                      ->orWhere('disciplinary_submits.created_by', $loginAdminId);
                })
                ->get(['disciplinary_submits.*']);
        }
        else
        {
            // HR / GM and any other rank with view permission see the
            // resort-wide list (cases not yet resolved/rejected). The page
            // itself is gated by the permission check above, so falling
            // through here is safe.
            $DisciplinarySubmissionModel= disciplinarySubmit::with(['category','offence','GetEmployee'])
            ->whereNotIn('status',['resolved','rejected'])
            ->where('resort_id',$this->resort->resort_id)
            ->get();
        }
           


        if($request->ajax())
        {
            $edit_class = '';
            $delete_class = '';
            if(Common::checkRouteWisePermission('GrievanceAndDisciplinery.Disciplinary.DisciplinaryIndex',config('settings.resort_permissions.edit')) == false){
                $edit_class = 'd-none';
            }
            if(Common::checkRouteWisePermission('GrievanceAndDisciplinery.Disciplinary.DisciplinaryIndex',config('settings.resort_permissions.delete')) == false){
                $delete_class = 'd-none';
            }

            return datatables()->of($DisciplinarySubmissionModel)
            ->addColumn('Action', function ($row) use ($rankKey,$edit_class,$delete_class) 
            {
                $id = base64_encode($row->id);
                    $string='';
                
                    $string='<a target="_blank" href="'. route('GrievanceAndDisciplinery.Disciplinary.Investigation',$id) .'" class="btn-tableIcon btnIcon-blue me-1 edit-row-btn '.$edit_class.'" data-cat-id="' . e($id) . '">
                    <i class="fas fa-balance-scale"></i>
                    </a>';
                        return '<div  class="d-flex align-items-center">
                                '.$string.'
                             
                                <a href="javascript:void(0)" class="btn-tableIcon eb-icon-critical delete-row-btn '.$delete_class.'" data-cat-id="' . e($id) . '">
                                    <i class="fa-regular fa-trash-can"></i>
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
            ->addColumn('CreatedAt', function ($row)
            {
                return $row->created_at ? $row->created_at->format('d M Y') : '—';
            })
            ->addColumn('ValidUntil', function ($row)
            {
                // Expiry_date is the form's "Action Valid Until" field —
                // surface it so HR can see at a glance how long an action
                // remains in force. Stored as Y-m-d / d/m/Y depending on
                // form path; Carbon::parse handles both.
                if (empty($row->Expiry_date) || $row->Expiry_date === '0000-00-00') {
                    return '—';
                }
                try {
                    return \Carbon\Carbon::parse($row->Expiry_date)->format('d M Y');
                } catch (\Exception $e) {
                    return $row->Expiry_date;
                }
            })
            ->rawColumns(['Disciplinary_Id','Category_name','Offence','EmployeeName','Status','CreatedAt','ValidUntil','Action'])
            ->make(true);
        }
        
        $page_title="Disciplinary";
        return view('resorts.GrievanceAndDisciplinery.diciplinary.index',compact('page_title'));
    }
    public function CreateDisciplinary()
    {
        $page_title="Create Disciplinary";
        $Employee =  Employee::with(['resortAdmin'])->where('resort_id',$this->resort->resort_id)->get();
        $DisciplinaryCategories = DisciplinaryCategoriesModel::where('resort_id',$this->resort->resort_id)->get();

        $Offenses =  OffensesModel::where('resort_id',$this->resort->resort_id)->get();
        $ActionStore = ActionStore::where('resort_id',$this->resort->resort_id)->get();
        $SeverityStore = SeverityStore::where('resort_id',$this->resort->resort_id)->get();
        $committiee = DisciplineryAssignCommittee::where('resort_id',$this->resort->resort_id)->get();

        return view('resorts.GrievanceAndDisciplinery.diciplinary.create',compact('page_title','Offenses','ActionStore','SeverityStore','DisciplinaryCategories','Employee','committiee'));
    }




    /**
     * Resolve the most recent Severity + Action used on prior disciplinary
     * submissions for this offence at this resort. Powers the auto-fill
     * shortcut on the create form so admins don't re-pick the same defaults
     * over and over. Returns base64-encoded ids so the frontend can match
     * the option values directly.
     */
    public function GetOffenceDefaults(Request $request)
    {
        $offenceId = (int) base64_decode($request->offence_id);
        $payload = ['Severity_id' => null, 'Action_id' => null, 'description' => null];

        if (!$offenceId) {
            return response()->json(['success' => true, 'data' => $payload]);
        }

        $offence = OffensesModel::where('id', $offenceId)
            ->where('resort_id', $this->resort->resort_id)
            ->first(['offensesdescription', 'default_severity_id', 'default_action_id']);
        if ($offence) {
            $payload['description'] = $offence->offensesdescription;
            if ($offence->default_severity_id) $payload['Severity_id'] = base64_encode($offence->default_severity_id);
            if ($offence->default_action_id)   $payload['Action_id']   = base64_encode($offence->default_action_id);
        }

        // Fallback to most-recent submission's severity/action if the offence
        // doesn't have admin-set defaults yet.
        if (!$payload['Severity_id'] || !$payload['Action_id']) {
            $latest = disciplinarySubmit::where('Offence_id', $offenceId)
                ->where('resort_id', $this->resort->resort_id)
                ->orderByDesc('id')
                ->first(['Severity_id', 'Action_id']);
            if ($latest) {
                if (!$payload['Severity_id'] && $latest->Severity_id) $payload['Severity_id'] = base64_encode($latest->Severity_id);
                if (!$payload['Action_id']   && $latest->Action_id)   $payload['Action_id']   = base64_encode($latest->Action_id);
            }
        }

        // Last-resort fallback so the dropdowns NEVER come back empty —
        // pick the lowest-id severity / action for the resort. The form
        // is editable, so the user can adjust if the guess is wrong.
        // Without this, brand-new offences with no defaults + no history
        // (e.g. "Chronic Absenteeism") would leave both dropdowns blank.
        if (!$payload['Severity_id']) {
            $sev = \App\Models\SeverityStore::where('resort_id', $this->resort->resort_id)
                ->orderBy('id')->first(['id']);
            if ($sev) $payload['Severity_id'] = base64_encode($sev->id);
        }
        if (!$payload['Action_id']) {
            $act = \App\Models\ActionStore::where('resort_id', $this->resort->resort_id)
                ->orderBy('id')->first(['id']);
            if ($act) $payload['Action_id'] = base64_encode($act->id);
        }

        return response()->json(['success' => true, 'data' => $payload]);
    }

    public function GetCategoryWiseOffence(Request $request)
    {
        $id = Base64_decode($request->id);
       
        $OffensesModel = OffensesModel::where("disciplinary_cat_id",$id)
                                        ->where('resort_id',$this->resort->resort_id)
                                        ->get(['id','OffensesName','disciplinary_cat_id'])
                                        ->map(function ($item) {
                                            $item->newid = base64_encode($item->id);
                                            $item->cat   = base64_encode($item->disciplinary_cat_id);
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
        // Was written into disciplinarySubmit below before the (only)
        // resort-scoped lookup of this same id even ran, and that lookup's
        // failure only broke notification code, never blocked the write —
        // an id from another resort saved successfully either way.
        if (!Employee::where('id', $Employee_id)->where('resort_id', $this->resort->resort_id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Invalid employee.'], 422);
        }
        $witnessIds = [];
        if (!empty($request->select_witness)) {
            foreach ((array) $request->select_witness as $wid) {
                $witnessIds[] = base64_decode($wid);
            }
            $validWitnessCount = Employee::whereIn('id', $witnessIds)->where('resort_id', $this->resort->resort_id)->count();
            if ($validWitnessCount !== count(array_unique($witnessIds))) {
                return response()->json(['success' => false, 'message' => 'One or more witnesses are invalid.'], 422);
            }
        }
        $Category_id = base64_decode($request->Category_id);
        $Offence_id =  base64_decode($request->Offence_id);
        $Action_id =  base64_decode($request->Action_id);
        $Severity_id =  base64_decode($request->Severity_id);
        // Form datepicker emits d/m/Y but the column is a DATE — passing
        // "10/05/2026" straight in made MySQL store 0000-00-00. Parse to
        // Y-m-d here. Tolerate already-Y-m-d values (e.g. API callers).
        $Expiry_date = null;
        if ($request->filled('Expiry_date')) {
            $raw = trim((string) $request->Expiry_date);
            foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'Y/m/d'] as $fmt) {
                try {
                    $c = \Carbon\Carbon::createFromFormat($fmt, $raw);
                    if ($c) { $Expiry_date = $c->format('Y-m-d'); break; }
                } catch (\Exception $e) { /* try next */ }
            }
            if (!$Expiry_date) {
                try { $Expiry_date = \Carbon\Carbon::parse($raw)->format('Y-m-d'); }
                catch (\Exception $e) { $Expiry_date = null; }
            }
        }
        $priority_level = $request->priority_level;
        $Incident_description = $request->incident_description;
        $committiee_id = $request->filled('assign_to') ? $request->assign_to : null;
        $Request_For_Statement = ($request->Request_For_Statement == "on")? 'Yes':'No';
        $Attachment  = $request->attachment;
        $upload_signed_document  = $request->upload_signed_document;
        $witnessisapplicable = !empty($request->select_witness) && count((array) $request->select_witness) > 0 ? "Yes" : "No";
        $new_upload_signed_document ='';
        $emp = Employee::join("resort_admins as t1","t1.id","=","employees.Admin_Parent_id")
                        ->join("resort_departments as t2","t2.id","=","employees.Dept_id")
                        ->join("resort_positions as t3","t3.id","=","employees.Position_id")
                        ->where('employees.resort_id',$this->resort->resort_id)
                        ->where("employees.id",$Employee_id)
                        ->first(['t1.email','t1.first_name','t1.last_name','t2.name as DepartmentName','t3.position_title as PositionName']);
      
        // Email template lookup is best-effort — the disciplinary record
        // should save even when no template has been configured for this
        // action yet. We just skip the notification and surface a warning
        // in the success response so the user knows.
        $disciplinary_email = DisciplinaryEmailmodel::where('resort_id', $this->resort->resort_id)
            ->where('Action_id', $Action_id)
            ->first();
        
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

        // Notify each committee member assigned to this DISCIPLINARY case.
        // Was using grievance copy ("a grievance case to your committee")
        // which confused users on the receiving end.
        $caseId = $disciplinarySubmit->Disciplinary_id ?? '';
        $msg = 'HR has assigned a disciplinary case' . ($caseId ? ' (' . $caseId . ')' : '') . ' to your committee.';
        $title = 'Disciplinary Case';
        $ModuleName = 'Disciplinary';
        foreach ($members_ids as $g) {
            event(new ResortNotificationEvent(Common::nofitication(
                $this->resort->resort_id, 10, $title, $msg, 0, $g->MemberId, $ModuleName
            )));
        }
        if($witnessisapplicable =="Yes")
        {
            foreach($witnessIds as $witnessId)
            {
                $Wintness_Status = ($Request_For_Statement =="Yes") ?'Requested' : '';
                DisciplinaryWitness::create([
                                                "resort_id"=>$this->resort->resort_id,
                                                'Disciplinary_id'=>$disciplinarySubmit->Disciplinary_id,
                                                'Employee_id'=>$witnessId,
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
            'Date_Submitted' => date('d M Y'),
            'Case_Description' => $Incident_description,
        ];

        // Send email only when a template is configured for this action AND
        // the employee actually has an email on record. Capture the result
        // (the helper returns true on success, an error string on failure)
        // so we can surface the reason to the user instead of silently
        // dropping the mail.
        $emailWarning = null;
        if (!$disciplinary_email) {
            $emailWarning = 'Saved, but no email template is configured for this action. The employee was not notified by email.';
            \Log::warning('Disciplinary saved without email notification', [
                'resort_id' => $this->resort->resort_id,
                'action_id' => $Action_id,
                'case_id'   => $disciplinarySubmit->Disciplinary_id ?? null,
            ]);
        } elseif (empty($emp->email)) {
            $emailWarning = 'Saved, but the employee has no email on record. The notification was not sent.';
            \Log::warning('Disciplinary saved without recipient email', [
                'resort_id' => $this->resort->resort_id,
                'employee_id' => $Employee_id,
                'case_id' => $disciplinarySubmit->Disciplinary_id ?? null,
            ]);
        } else {
            try {
                $sendResult = Common::sendTemplateEmail('Disciplinary', $disciplinary_email->id, $emp->email, $dynamic_data);
                // sendTemplateEmail returns true on success, a string on failure.
                if ($sendResult !== true) {
                    $emailWarning = 'Saved, but email delivery failed: ' . (is_string($sendResult) ? $sendResult : 'unknown error');
                    \Log::warning('Disciplinary email helper returned non-true', [
                        'case_id' => $disciplinarySubmit->Disciplinary_id ?? null,
                        'result'  => $sendResult,
                    ]);
                } else {
                    \Log::info('Disciplinary email dispatched', [
                        'case_id' => $disciplinarySubmit->Disciplinary_id ?? null,
                        'to'      => $emp->email,
                    ]);
                }
            } catch (\Throwable $mailEx) {
                $emailWarning = 'Saved, but email delivery threw: ' . $mailEx->getMessage();
                \Log::error('Disciplinary email threw exception', [
                    'case_id' => $disciplinarySubmit->Disciplinary_id ?? null,
                    'error'   => $mailEx->getMessage(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Disciplinary Created Successfully',
            'warning' => $emailWarning,
        ], 200);

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
                // "Active Offences" panel shows ACTION VALID UNTIL — the
                // expiry of the disciplinary action against the employee.
                // Falls back to "—" if Expiry_date is empty / 0000-00-00.
                if (empty($row->Expiry_date) || $row->Expiry_date === '0000-00-00') {
                    return '—';
                }
                try {
                    return \Carbon\Carbon::parse($row->Expiry_date)->format('d M Y');
                } catch (\Exception $e) {
                    return $row->Expiry_date;
                }
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
                                                    ->leftJoin('disciplinery_assign_committees as t9',"t9.id","=","disciplinary_submits.Committee_id")
                                                    ->where("t1.resort_id",$this->resort->resort_id)
                                                    ->where("disciplinary_submits.id",$id)
                                                    ->where('disciplinary_submits.status','In_Review')
                                                    ->first(['t8.ActionName','t7.DisciplinaryCategoryName as  CatName','t6.OffensesName','t2.personal_phone','t2.email as employee_email','t2.id as Parentid','t2.first_name','t2.last_name','t2.profile_picture','t1.Emp_id as employee_code','t9.CommitteeName','disciplinary_submits.*','t3.name as DepartmentName','t4.position_title as PositiontName']);
       
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

        $FollowUpActions = \App\Models\DisciplinaryFollowUpAction::where('resort_id', $this->resort->resort_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('resorts.GrievanceAndDisciplinery.diciplinary.investigationreport',compact('parent','child','page_title','Disciplinary_parent','Path','committee_member_id','FollowUpActions'));
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
                // Use the Disciplinary upload path + the case's Disciplinary_id
                // subfolder so the URL the view builds in DisciplineryInvestigation()
                // — uploads/DisciplinaryAttachments/{resort_id}/{Disciplinary_id}/file —
                // actually resolves to a real file on disk. Was writing to
                // public/uploads/GrievanceSubmission (wrong module) without the
                // case subdir, so every attachment 404'd from the view page.
                $FilePath = config('settings.DisciplinaryAttachments')
                    .'/'.$this->resort->resort->resort_id
                    .'/'.$request->Disciplinary_form_id;
                foreach($file as $f) {
                    $f->move(public_path($FilePath), $f->getClientOriginalName());
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

                try {
                    $accusedEmpId = disciplinarySubmit::where('resort_id', $this->resort->resort_id)
                        ->where('Disciplinary_id', $id)
                        ->value('Employee_id');
                    if ($accusedEmpId) {
                        Common::notifyEmployees(
                            $this->resort->resort_id,
                            [$accusedEmpId],
                            'Disciplinary Case Resolved',
                            'Your disciplinary case has been resolved.',
                            'Disciplinary',
                            $id
                        );
                    }
                } catch (\Exception $e) {
                    \Log::warning('Disciplinary resolved notification failed: ' . $e->getMessage());
                }
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
                           

            // Fan out a bell notification to every OTHER member of the
            // committee assigned to this case, so they know an update was
            // posted. Skip the member who just submitted (themselves).
            try {
                $caseRow = disciplinarySubmit::where('resort_id', $this->resort->resort_id)
                    ->where('Disciplinary_id', $id)
                    ->first(['Committee_id', 'Disciplinary_id']);
                if ($caseRow && $caseRow->Committee_id) {
                    $otherMemberIds = DisciplineryCommitteeMembers::where('Parent_committee_id', $caseRow->Committee_id)
                        ->where('MemberId', '!=', (int) $committee_member_id)
                        ->pluck('MemberId')
                        ->filter()
                        ->unique()
                        ->values()
                        ->all();
                    if (!empty($otherMemberIds)) {
                        $title = 'Disciplinary Investigation Update';
                        $msg = 'A committee member has updated the investigation for ' . $caseRow->Disciplinary_id . '.';
                        foreach ($otherMemberIds as $mid) {
                            event(new ResortNotificationEvent(Common::nofitication(
                                $this->resort->resort_id,
                                10,
                                $title,
                                $msg,
                                0,
                                $mid,
                                'Disciplinary'
                            )));
                        }
                    }
                }
            } catch (\Exception $notifyEx) {
                \Log::warning('Disciplinary investigation notify failed: ' . $notifyEx->getMessage());
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

