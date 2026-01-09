<?php

namespace App\Http\Controllers\Resorts\Incident;

use App\Http\Controllers\Controller;
use App\Events\ResortNotificationEvent;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Incidents;
use App\Models\ResortAdmin;
use App\Models\IncidentCategory;
use App\Models\IncidentMeeting;
use App\Models\IncidentCommittee;
use App\Models\IncidentSubCategory;
use App\Models\IncidentActionTaken;
use App\Models\IncidentOutcomeType;
use App\Models\IncidentFollowupActions;
use App\Models\IncidentConfiguration;
use App\Models\IncidentCommitteeMember;
use App\Models\IncidentResolutionTimeline;
use App\Models\IncidentsInvestigation;
use App\Models\IncidentsWitness;
use App\Models\IncidentsEmployeeStatements;
use Auth;
use DB;
use App\Helpers\Common;
use Carbon\Carbon;

class IncidentController extends Controller
{
    public $resort;
    public $reporting_to;
    protected $underEmp_id=[];
  
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        $this->reporting_to = isset($this->resort->GetEmployee) ? $this->resort->GetEmployee->id:0;
        $this->underEmp_id = Common::getSubordinates($this->reporting_to);
    }
    
    public function index()
    {
        $page_title ='Incident List';
        $resort_id = $this->resort->resort_id;
        $categories = IncidentCategory::where('resort_id',$resort_id)->get();        
        return view('resorts.incident.incident.index',compact('page_title','categories'));
    }

    public function list(Request $request)
    {
        if ($request->ajax()) {
            $resort_id = $this->resort->resort_id;
            $loggedInEmployee = $this->resort->getEmployee;
            $loggedInUserId = $loggedInEmployee->id;
            $rank = config('settings.Position_Rank');
            $current_rank = $loggedInEmployee->rank ?? null;
            $available_rank = $rank[$current_rank] ?? '';
            $isHR = ($available_rank === "HR");
            $isGM = ($available_rank === "GM");

            $timelineRecords = DB::table('incident_resolution_timeline')->select('priority', 'timeline')->get();

            $timelines = [];
            foreach ($timelineRecords as $record) {
                preg_match('/(\d+)/', $record->timeline, $matches);
                if (isset($matches[1])) {
                    $timelines[$record->priority] = (int)$matches[1];
                }
            }

            $userCommittees = IncidentCommitteeMember::where('member_id', $loggedInUserId)
                ->pluck('commitee_id')
                ->toArray();

            $query = Incidents::where('resort_id', $resort_id)
                ->where('status', '!=', 'Resolved')
                ->with(['categoryName'])
                ->select('id', 'incident_id', 'incident_name', 'location', 'category', 'incident_date', 'incident_time', 'isWitness', 'involved_employees', 'status', 'priority','created_at');

            if ($isGM) {
                $query->where('approval', 1);
            } elseif (!$isHR) {
                $departmentId = $loggedInEmployee->Dept_id;

                $query->where(function ($q) use ($userCommittees, $departmentId) {
                    if (!empty($userCommittees)) {
                        $q->orWhere(function ($subQ) use ($userCommittees) {
                            foreach ($userCommittees as $committeeId) {
                                $subQ->orWhereRaw("JSON_CONTAINS(assigned_to, ?)", [json_encode((string)$committeeId)]);
                            }
                        });
                    }

                    if ($departmentId) {
                        $q->orWhereHas('reporter', function ($subQ) use ($departmentId) {
                            $subQ->where('Dept_id', $departmentId);
                        });
                    }
                });
            }

            if ($request->searchTerm) {
                $searchTerm = $request->searchTerm;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('incident_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('incident_id', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('location', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('incident_date', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('incident_time', 'LIKE', "%{$searchTerm}%");
                });
            }

            if ($request->category) {
                $query->where('category', $request->category);
            }

            if ($request->date) {
                $query->where('incident_date', $request->date);
            }

            $incidents = $query->get();

            $edit_class  = '';
            $delete_class  = '';
            if(Common::checkRouteWisePermission('incident.index',config('settings.resort_permissions.edit')) == false){
                $edit_class = 'd-none';
            }
            if(Common::checkRouteWisePermission('incident.index',config('settings.resort_permissions.delete')) == false){
                $delete_class = 'd-none';
            }

            return datatables()->of($incidents)
                ->addColumn('category', fn($row) => $row->categoryName->category_name ?? 'N/A')
                ->addColumn('date', fn($row) => date('d M Y', strtotime($row->incident_date)))
                ->addColumn('time', fn($row) => date('h:i A', strtotime($row->incident_time)))
                ->addColumn('involved_employees', function ($row) {
                    $employeesImages = '';
                    $involved_employees = explode(',', $row->involved_employees);
                    $count = count($involved_employees);
                    $displayLimit = 5;
                    foreach ($involved_employees as $index => $employee) {
                        $empDetails = Employee::find($employee);
                        if ($empDetails) {
                            $image = Common::getResortUserPicture($empDetails->Admin_Parent_id ?? null);
                            if ($index < $displayLimit) {
                                $employeesImages .= '
                                    <div class="img-circle">
                                        <img src="' . $image . '" alt="' . e($employee) . '">
                                    </div>';
                            }
                        }
                    }
                    if ($count > $displayLimit) {
                        $employeesImages .= '<div class="num">+' . ($count - $displayLimit) . '</div>';
                    }
                    return '<div class="user-ovImg">' . $employeesImages . '</div>';
                })
                ->addColumn('action', function ($row) use ($isHR, $isGM , $edit_class,$delete_class) {
                    $actions = '';
                    if ($isGM) {
                        $actions .= '<button class="correct-btn '.$edit_class.'" data-id="' . $row->id . '"><i class="fa-solid fa-check"></i></button>';
                        $actions .= '<button class="btn-tableIcon btnIcon-danger reject-btn '.$edit_class.'" data-id="' . $row->id . '"><i class="fa-solid fa-xmark"></i></button>';
                    }
                    if ($isHR) {
                        $actions .= '<a href="' . route('incident.view', base64_encode($row->id)) . '" class="btn-tableIcon btnIcon-skyblue" title="View"><i class="fa-regular fa-eye"></i></a>';
                    }
                    $actions .= '<a href="' . route('incident.investigation', base64_encode($row->id)) . '" class="btn-tableIcon btnIcon-green '.$edit_class.'" title="Investigation"><i class="fa-solid fa-user-secret"></i></a>';
                    $actions .= '<a href="' . route('incident.meeting.create', base64_encode($row->id)) . '" class="btn-tableIcon btnIcon-yellow '.$edit_class.'" title="Meeting"><i class="fa-solid fa-calendar-plus"></i></a>';
                    if ($isHR) {
                        $actions .= '<a href="#" class="btn-tableIcon btnIcon-danger delete-row-btn ' . $delete_class . '" title="Delete" data-id="' . e(base64_encode($row->id)) . '"><i class="fa-regular fa-trash-can"></i></a>';
                    }
                    return $actions;
                })
                ->addColumn('danger_flag', function ($row) use ($timelines) {
                    $investigation = IncidentsInvestigation::where('incident_id',$row->id)->first();
                    // $expected = $investigation->expected_resolution_date ?? null;
                    $start = $investigation->start_date ?? null;

                    if ($row->priority && isset($timelines[$row->priority]) && $start) {
                        $actualDate = Carbon::parse($start);
                        $deadline = $actualDate->copy()->addWeekdays($timelines[$row->priority]);
                        if (now()->gt($deadline)) {
                            return true;
                        }
                    }

                    return false;
                })
                ->rawColumns(['action', 'involved_employees', 'danger_flag'])
                ->make(true);
        }
    }

    public function resolvedList(Request $request)
    {
        if ($request->ajax()) {
            $resort_id = auth()->user()->resort_id; // Adjust based on your authentication setup
            
            $timelineRecords = DB::table('incident_resolution_timeline')->select('priority', 'timeline')->get();

            $timelines = [];
            foreach ($timelineRecords as $record) {
                preg_match('/(\d+)/', $record->timeline, $matches);
                if (isset($matches[1])) {
                    $timelines[$record->priority] = (int)$matches[1];
                }
            }
            // Start Query
            $query = Incidents::where('resort_id', $resort_id)
                ->where('status', 'Resolved')
                ->with('categoryName')
                ->select('id', 'incident_id', 'incident_name', 'location', 'category', 'incident_date', 'incident_time', 'isWitness', 'involved_employees','status','created_at');
    
            // ✅ Apply Search BEFORE fetching data
            if ($request->searchTerm) {
                $searchTerm = $request->searchTerm;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('incident_name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('incident_id', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('location', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('incident_date', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('incident_time', 'LIKE', "%{$searchTerm}%");
                });
            }

            if ($request->category) {
                $query->where('category', $request->category);
            }

            if ($request->date) {
                $query->where('incident_date', $request->date);
            }
    
            // ✅ Fetch data only after filtering
            $incidents = $query->get();
               
            $delete_class  = '';
            if(Common::checkRouteWisePermission('incident.index',config('settings.resort_permissions.delete')) == false){
                $delete_class = 'd-none';
            }

            return datatables()->of($incidents)
                ->addColumn('category', function ($row) {
                    return $row->category ? $row->categoryName->category_name : 'N/A';
                })
                ->addColumn('date', function ($row) {
                    return date('d M Y', strtotime($row->incident_date));
                })
                ->addColumn('time', function ($row) {
                    return date('h:i A', strtotime($row->incident_time));
                })
                ->addColumn('involved_employees', function ($row) {
                    $employeesImages = '';
                    $involved_employees = explode(',', $row->involved_employees);
                    $count = count($involved_employees);
                    $displayLimit = 5;
    
                    foreach ($involved_employees as $index => $employee) {
                        $empDetails = Employee::find($employee); // ✅ Use find() instead of findOrFail()
                        if ($empDetails) { // Ensure employee exists
                            $image = Common::getResortUserPicture($empDetails->Admin_Parent_id ?? null);
                            if ($index < $displayLimit) {
                                $employeesImages .= '
                                    <div class="img-circle">
                                        <img src="' . $image . '" alt="' . e($employee) . '">
                                    </div>
                                ';
                            }
                        }
                    }
    
                    if ($count > $displayLimit) {
                        $employeesImages .= '<div class="num">+' . ($count - $displayLimit) . '</div>';
                    }
    
                    return '<div class="user-ovImg">' . $employeesImages . '</div>';
                })
                ->addColumn('action', function ($row) use ($delete_class) {
                    return '<a href="' . route('incident.view', base64_encode($row->id)) . '" class="btn-tableIcon btnIcon-skyblue">
                                <i class="fa-regular fa-eye"></i>
                            </a>
                            <a href="#" class="btn-tableIcon btnIcon-danger delete-row-btn ' . $delete_class . '" data-cat-id="' . e($row->id) . '">
                                <i class="fa-regular fa-trash-can"></i>
                            </a>';
                })
                ->addColumn('danger_flag', function ($row) use ($timelines) {
                    $investigation = IncidentsInvestigation::where('incident_id',$row->id)->first();
                    // $expected = $investigation->expected_resolution_date ?? null;
                    $start = $investigation->start_date ?? null;

                    if ($row->priority && isset($timelines[$row->priority]) && $start) {
                        $actualDate = Carbon::parse($start);
                        $deadline = $actualDate->copy()->addWeekdays($timelines[$row->priority]);
                        if (now()->gt($deadline)) {
                            return true;
                        }
                    }

                    return false;
                })
                ->rawColumns(['action', 'involved_employees','danger_flag'])
                ->make(true);
        }
    }
    
    public function view($id)
    {
        if(Common::checkRouteWisePermission('incident.index',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }
        $page_title ='Incident Detail';
        $id = base64_decode($id);
        $resort_id = $this->resort->resort_id;
        $incident = Incidents::with(['reporter.resortAdmin','reporter.position','witness.employee'])->where('id',$id)->first();  
        // dd($incident);
        $incident_committee = IncidentCommittee::where('resort_id',$resort_id)->get();  
        $status = IncidentConfiguration::where('resort_id', $resort_id)
        ->where('setting_key', 'status')
        ->first(); 
        $statuses =  explode(",",$status->setting_value);
  
        return view('resorts.incident.incident.view',compact('page_title','incident','incident_committee','statuses'));
    }

    public function assign(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'incident_id' => 'required|exists:incidents,id',
            'priority' => 'nullable|in:Low,Medium,High',
            'assigned_commiteee' => 'nullable|array',
            'assigned_commiteee.*' => 'exists:incident_committee,id',
            'comments' => 'nullable|string|max:1000',
            'status' => 'required',
        ]);

        // Find the Incident Report
        $incident = Incidents::findOrFail($request->incident_id);

        // Update the Incident Report
        $incident->update([
            'priority' => $request->priority,
            'assigned_to' => json_encode($request->assigned_commiteee), // Store as JSON for multiple assignments
            'comments' => $request->comments,
            'status' => $request->status
        ]);

        // Send Notifications to Assigned Committee Members
        if (!empty($request->assigned_commiteee)) {
            $committeeMembers = IncidentCommitteeMember::whereIn('commitee_id', $request->assigned_commiteee)->get();
            
            foreach ($committeeMembers as $member) {
                $msg = 'HR has assigned a '.$incident->incident_name.' incident to your committee.';
                $title = 'Assign Incident';
                $ModuleName = "Incident";
                
                
                event(new ResortNotificationEvent(Common::nofitication(
                    $this->resort->resort_id, 
                    10, 
                    $title, 
                    $msg, 
                    0, 
                    $member->member_id, 
                    $ModuleName
                )));
            }
        }

        return response()->json([
            'message' => 'Incident assigned to committee successfully!',
            'incident' => $incident,
            'redirect_url' => route('incident.index'),
        ], 200);
    }

    public function investigation($id)
    {
         if(Common::checkRouteWisePermission('incident.index',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }
        $page_title ='Incident Investigation';
        $resort_id = $this->resort->resort_id;
        $id = base64_decode($id);
        $loggedInEmployee = $this->resort->getEmployee;
        $loggedInUserId = $loggedInEmployee->id;
        $rank = config('settings.Position_Rank');
        $current_rank = $loggedInEmployee->rank ?? null;
        $available_rank = $rank[$current_rank] ?? '';
        $isHR = ($available_rank === "HR");
        $incident = Incidents::with(['reporter.resortAdmin','reporter.position','witness.employee'])->where('id',$id)->first();  
        $investigations = IncidentsInvestigation::with(['addedBy.employee','followupAction'])->where('incident_id',$id)->orderBy('created_at','desc')->get();
        // dd($investigations);
        $incident_committee = IncidentCommittee::where('resort_id',$resort_id)->get();  
        $incident_witness_statements = IncidentsWitness::with('employee')->where('incident_id',$id)->get();
        // dd($incident_witness_statements);
        $incident_employee_statements = IncidentsEmployeeStatements::with('employee')->where('incident_id',$id)->get();
        
        // dd($incident_employee_statements);
        $followup_actions = IncidentFollowupActions::where('resort_id',$resort_id)->get(); 
        $action_takens = IncidentActionTaken::where('resort_id',$resort_id)->get(); 
        $outcome_types = IncidentOutcomeType::where('resort_id',$resort_id)->get(); 
        $status = IncidentConfiguration::where('resort_id', $resort_id)
        ->where('setting_key', 'status')
        ->first(); 
        $statuses =  explode(",",$status->setting_value);
        $severity_levels = IncidentConfiguration::where('resort_id', $resort_id)
        ->where('setting_key', 'severity_levels')
        ->first(); 
        $severities =  explode(",",$severity_levels->setting_value);
  
        return view('resorts.incident.incident.investigation',compact('page_title','incident','incident_committee','statuses','severities','followup_actions','action_takens','outcome_types','investigations','incident_witness_statements','incident_employee_statements','isHR'));
    }

    public function storeInvestigation(Request $request)
    {
        // Helper function to convert dd/mm/yyyy to yyyy-mm-dd
        $convertToYmd = function ($dateStr) {
            if (!$dateStr) return null;
            try {
                return Carbon::createFromFormat('d/m/Y', $dateStr)->format('Y-m-d');
            } catch (\Exception $e) {
                return $dateStr; // or log the error if needed
            }
        };

        $loggedInEmployee = $this->resort->getEmployee;
        if (!$loggedInEmployee) {
            abort(403, "Access Denied");
        }

        $rank = config('settings.Position_Rank');
        $current_rank = $this->resort->getEmployee->rank ?? null;
        $available_rank = $rank[$current_rank] ?? '';
        $isHOD = ($available_rank === "HOD");
        $isHR = ($available_rank === "HR");

        $request->validate([
            'priority' => 'nullable|string',
            'severity' => 'nullable|string',
            'police' => 'nullable|string',
            'police_date' => 'nullable',
            'police_time' => 'nullable',
            'mdf' => 'nullable|string',
            'mndf_date' => 'nullable',
            'mndf_time' => 'nullable',
            'firerescue' => 'nullable|string',
            'fire_date' => 'nullable',
            'fire_time' => 'nullable',
            'start_date' => 'nullable',
            'expResoDate' => 'nullable',
            'investFind' => 'nullable|string',
            'followUpActions' => 'nullable|integer',
            'resolutionNotes' => 'nullable|string',
            'outcomeType' => 'nullable|integer',
            'pre_mea' => 'nullable|string',
            'action_taken' => 'nullable|integer',
            'approval' => 'nullable|boolean',
            'status' => 'nullable|string',
        ]);

        $incident_details = Incidents::findOrFail($request->incident_id);
        $incident_details->priority = $request->priority;
        $incident_details->severity = $request->severity;
        $incident_details->status = $request->status;
        $incident_details->outcome_type =  $request->outcomeType;
        $incident_details->preventive_measures =  $request->pre_mea;
        $incident_details->action_taken =  $request->action_taken;
        $incident_details->approval = $request->approval;
        $incident_details->save();

        $incident = new IncidentsInvestigation();

        if ($request->police_date && $request->police_time) {
            $incident->police_notified = $request->police;
            $incident->police_date = $convertToYmd($request->police_date);
            $incident->police_time = $request->police_time;
        }
        
        if ($request->mndf_date && $request->mndf_time) {
            $incident->mdf_notified = $request->mdf;
            $incident->mndf_date = $convertToYmd($request->mndf_date);
            $incident->mndf_time = $request->mndf_time;
        }
        
        if ($request->fire_date && $request->fire_time) {
            $incident->fire_rescue_notified = $request->firerescue;
            $incident->fire_rescue_date = $convertToYmd($request->fire_date);
            $incident->fire_rescue_time = $request->fire_time;
        }
        if ($request->Ministry_notified_date && $request->Ministry_notified_date) 
        {
         
            $incident->Ministry_notified_date =  $convertToYmd($request->Ministry_notified_date);
            $incident->Ministry_time = $request->Ministry_time;
            $incident->Ministry_notified = $request->Ministry_notified;
        }

        if (!$isHR) {
            $employeeId = $loggedInEmployee->id;
        
            // Fetch the committee member row
            $committeeMember = IncidentCommitteeMember::where('member_id', $employeeId)->first();
        
            if ($committeeMember) {
                $incident->committee_id = $committeeMember->commitee_id;
                $incident->added_by_member_id = $committeeMember->id; // <-- Correct value here
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Committee membership not found for this user.'
                ], 403);
            }
        }
        
        $incident->incident_id =$request->incident_id;
        $incident->start_date = $convertToYmd($request->start_date);
        $incident->expected_resolution_date = $convertToYmd($request->expResoDate);
        $incident->investigation_findings = $request->investFind;
        $incident->folloup_action = $request->followUpActions;
        $incident->resolution_notes = $request->resolutionNotes;
    
        $incident->save();

       
        

        return response()->json([
            'success' => true,
            'message' => 'Investigation data saved successfully.'
        ]);
    }
    
    public function requestEmployeeStatements(Request $request)
    {
        $request->validate([
            'incident_id' => 'required|exists:incidents,id',
        ]);
    
        $incident = Incidents::with(['witness.employee', 'witness.employee.resortAdmin'])->findOrFail($request->incident_id);
    
        $userIds = collect();
        // dd($incident->involved_employees)
        // Involved employees (comma-separated IDs)
        if (!empty($incident->involved_employees)) {
            $involvedEmployeeIds = explode(',', $incident->involved_employees);
            // dd($involvedEmployeeIds);
            $userIds = $userIds->merge($involvedEmployeeIds);
        }
        
        // Witnesses (get employee_id from witness relation)
        if ($incident->witness) {
            foreach ($incident->witness as $witness) {
                // dd($witness);
                if (!empty($witness->witness_id)) {
                    $userIds->push($witness->witness_id);
                }
            }
        }
       
        $userIds = $userIds->unique()->filter(); // remove duplicates/nulls
              
        foreach ($userIds as $user) {
            // dd($user);
            $msg = 'You are requested to provide a statement regarding an incident.';
            $title = 'Employee Statement Required';
            $ModuleName = "Incident";
            
            event(new ResortNotificationEvent(Common::nofitication(
                $this->resort->resort_id, 
                10, 
                $title, 
                $msg, 
                0, 
                $user, 
                $ModuleName
            )));

            Common::sendMobileNotification(
                $this->resort->resort_id,
                '',
                '',
                $title,
                $msg,
                $ModuleName,
                [$user],
                null,
            );
        }    
        return response()->json(['message' => 'Statement request sent to involved employees and witnesses.']);
    }

    public function approve(Request $request)
    {
        $incident = Incidents::findOrFail($request->incident_id);

        if (!$incident) {
            return response()->json(['message' => 'Incident not found.'], 404);
        }

        $incident->approval = 1;
        $incident->status = "Approval Pending";
        $incident->save();

        // Notify GM
        $gm = Employee::with(['position','resortAdmin'])->where('Rank', 8)->first(); // Or adjust to your structure
        if ($gm) {
            $msg = 'An investigation report is awaiting your approval.';
            $title = 'Approval Required';
            $ModuleName = "Incident";
            
            event(new ResortNotificationEvent(Common::nofitication(
                $this->resort->resort_id, 
                10, 
                $title, 
                $msg, 
                0, 
                $gm->id, 
                $ModuleName
            )));       
        }

        return response()->json(['message' => 'Approval request sent to GM.']);
    }

    public function approveOrReject(Request $request)
    {
        $incident = Incidents::findOrFail($request->id);
        $incident->approved_by = $this->resort->getEmployee->id;
        $incident->approved_at = now();
        $incident->approval_remarks = $request->remarks;

        if ($request->status === 'approved') {
            $incident->status = 'Approved';
        } elseif ($request->status === 'rejected') {
            $incident->status = 'Rejected';
        }

        $incident->save();

        return response()->json(['success' => true]);
    }

    public function destroy(Request $request)
    {
        $id = base64_decode($request->id);

        try {
            DB::beginTransaction();

            $incident = Incidents::findOrFail($id);

            // Delete related child records
            $incident->witness()->delete();
            $incident->meeting()->each(function ($meeting) {
                // Optional: delete participants if needed
                $meeting->participant()->delete();
                $meeting->delete();
            });
            $incident->Investigation()->delete();
            $incident->employeeStatements()->delete();

            // Delete the main incident
            $incident->delete();

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Incident and related data deleted successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Incident Deletion failed.']);
        }
    }


}