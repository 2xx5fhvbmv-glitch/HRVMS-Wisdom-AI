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
use App\Models\IncidentsMeeting;
use App\Models\IncidentCategory;
use App\Models\IncidentCommittee;
use App\Models\IncidentSubCategory;
use App\Models\IncidentConfiguration;
use App\Models\IncidentCommitteeMember;
use App\Models\IncidentResolutionTimeline;
use App\Models\IncidentsMeetingParticipants;
use App\Models\IncidentsMeetingExternalParticipants;
use Auth;
use DB;
use Common;
use Carbon\Carbon;

class IncidentMeetingController extends Controller
{
    public $resort;
    public $reporting_to;
    protected $underEmp_id=[];
  
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        // dd( $this->resort);
        $this->reporting_to = isset($this->resort->GetEmployee) ? $this->resort->GetEmployee->id:0;
        $this->underEmp_id = Common::getSubordinates($this->reporting_to);
    }
    
    public function index()
    {
        $page_title ='Investigation Meeting';
        $resort_id = $this->resort->resort_id;
        // $categories = IncidentMeeting::where('resort_id',$resort_id)->get();        
        return view('resorts.incident.meeting.index',compact('page_title'));
    }

    public function list(Request $request)
    {
        if ($request->ajax()) {
            $incident_meetings = IncidentsMeeting::with(['participant.employee','incidents']);

            // Apply filters
            if ($request->has('searchTerm') && $request->searchTerm) {
                $search = $request->searchTerm;
                $incident_meetings = $incident_meetings->where(function ($q) use ($search) {
                    $q->where('meeting_subject', 'like', '%' . $search . '%')
                      ->orWhere('location', 'like', '%' . $search . '%')
                      ->orWhereHas('incidents', function ($q2) use ($search) {
                          $q2->where('incident_id', 'like', '%' . $search . '%');
                      });
                });
            }
    
            if ($request->has('date') && $request->date) {
                $incident_meetings = $incident_meetings->whereDate('meeting_date', $request->date);
            }
    
            $incident_meetings = $incident_meetings->get();

            // Check permissions
            $edit_class = '';
            $delete_class = '';
            if(Common::checkRouteWisePermission('incident.meeting',config('settings.resort_permissions.edit')) == false){
                $edit_class = 'd-none';
            }
            if(Common::checkRouteWisePermission('incident.meeting',config('settings.resort_permissions.delete')) == false){
                $delete_class = 'd-none';
            }

            return datatables()->of($incident_meetings)
                ->addColumn('incidentID', function ($row) {
                    return $row->incidents->incident_id;
                })
                ->addColumn('date', function ($row) {
                    return date('d M Y', strtotime($row->meeting_date));
                })
                ->addColumn('time', function ($row) {
                    return date('h:i A', strtotime($row->meeting_time));
                })
                ->addColumn('participants', function ($row) {
                    $employeesImages = '';
                    $participants = $row->participant;
                    $count = count($participants);
                    $displayLimit = 5;
        
                    foreach ($participants as $index => $p) {
                        $emp = $p->employee;
                        if ($emp) {
                            $image = Common::getResortUserPicture($emp->Admin_Parent_id ?? null);
                            if ($index < $displayLimit) {
                                $employeesImages .= '
                                    <div class="img-circle" title="' . e($emp->resortAdmin->first_name . ' ' . $emp->resortAdmin->last_name) . '">
                                        <img src="' . $image . '" alt="' . e($emp->resortAdmin->full_name) . '">
                                    </div>
                                ';
                            }
                        }
                    }
        
                    if ($count > $displayLimit) {
                        $employeesImages .= '<div class="num">+' . ($count - $displayLimit) . '</div>';
                    }
        
                    return '<div class="user-ovImg d-flex">' . $employeesImages . '</div>';
                })
               ->addColumn('attachments', function ($row) {
                    $html = '';

                    if ($row->attachments) {
                        foreach (json_decode($row->attachments, true) as $attachment) {
                            if (isset($attachment['Filename']) && isset($attachment['Child_id'])) {
                                $encodedId = base64_encode($attachment['Child_id']);
                                $filename = htmlspecialchars($attachment['Filename']); // Safe for output
                                $html .= '<a href="javascript:void(0)" target="_blank" class="download-link" data-id="' . $encodedId . '">' . $filename . '</a><br>';
                            }
                        }
                    } else {
                        $html = '<span class="text-muted">No attachments</span>';
                    }

                    return $html;
                })         
                ->addColumn('action', function ($row) use ($edit_class, $delete_class) {
                    $id = base64_encode($row->id);
                    return '
                        <div class="d-flex align-items-center">
                            <a href="' . route('incident.meeting.detail', e($id)) . '" title="View Meeting Detail" class="btn-tableIcon btnIcon-yellow me-1">
                                <i class="fa-regular fa-eye"></i>
                            </a>
                            <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn '.$edit_class.'" data-meeting-id="' . e($id) . '">
                                <img src="' . asset("resorts_assets/images/edit.svg") . '" alt="Edit" class="img-fluid">
                            </a>
                            <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn '.$delete_class.'" data-meeting-id="' . e($id) . '">
                                <img src="' . asset("resorts_assets/images/trash-red.svg") . '" alt="Delete" class="img-fluid">
                            </a>
                        </div>';
                })
                ->rawColumns(['participants','attachments' ,'action']) 
                ->make(true);
        }
        
    }

    public function create($incident_id)
    {
        if(Common::checkRouteWisePermission('incident.meeting',config('settings.resort_permissions.create')) == false){
            return abort(403, 'Unauthorized access');
        }
        $page_title ='Incident Meeting Create';
        $resort_id = $this->resort->resort_id;
        $incident_id = base64_decode($incident_id);
        $incident = Incidents::findOrFail($incident_id);
        $status = ['Active','OnLeave','Probationary','contractual'];
        $participants = Employee::with('resortAdmin')->where('resort_id',$resort_id)->wherein('status',$status)->get();
        // dd($participants);
        return view('resorts.incident.meeting.create',compact('page_title','participants','incident'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'incidentId' => 'required|integer|exists:incidents,id',
            'meeting_subject' => 'required|string|max:255',
            'meeting_date' => 'required|date_format:d/m/Y',
            'meeting_time' => 'required',
            'location' => 'nullable|string|max:255',
            'meeting_type' => 'required|in:Online,Physical',
            'participants' => 'nullable|array',
            'participants.*' => 'exists:employees,id',
            'roles' => 'nullable|array',
            'roles.*' => 'nullable|string|max:255',
            'ext_participants' => 'nullable|array',
            'ext_participants.*' => 'nullable|string|max:255',
            'meeting_agenda' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:2048',
        ]);

        try {
            $employee = $this->resort->getEmployee;
        
            if (!$employee) 
            {
                return response()->json(['success' => false, 'message' => 'Employee not found.'], 404);
            }

            $uploadedFiles = [];
           
            if ($request->hasFile('attachments')) 
            {
                foreach ($request->file('attachments') as $file) 
                {
                    $status =   Common::AWSEmployeeFileUpload($this->resort->resort_id, $file, $employee->Emp_id,true );
                  
                    if ($status['status'] == false) 
                    {
                        break;
                    }
                    else
                    {
                        if($status['status'] == true && isset($status['Chil_file_id']) && !empty($status['Chil_file_id']))
                        {

                            $filename = $file->getClientOriginalName();
                            $uploadedFiles[] = ['Filename' => $filename, 'Child_id' => $status['Chil_file_id']];
                        }
                       

                    }
                }

            }
      
            $meeting = new IncidentsMeeting();
            $meeting->incident_id = $request->incidentId;
            $meeting->meeting_subject = $request->meeting_subject;
            $meeting->meeting_date = Carbon::createFromFormat('d/m/Y', $request->meeting_date)->format('Y-m-d');
            $meeting->meeting_time = $request->meeting_time;
            $meeting->location = $request->location;
            $meeting->meeting_type = $request->meeting_type;
            $meeting->meeting_agenda = $request->meeting_agenda;
            $meeting->attachments = $uploadedFiles ? json_encode($uploadedFiles) : null;
            $meeting->save();

            // Save participants
            if ($request->participants) {
                foreach ($request->participants as $i => $participant_id) {
                    IncidentsMeetingParticipants::create([
                        'meeting_id' => $meeting->id,
                        'participant_id' => $participant_id,
                        'participant_role' => $request->roles[$i] ?? null,
                    ]);

                    $msg = "Meeting Scheduled: {$request->meeting_subject}\n📅 {$request->meeting_date}\n⏰ {$request->meeting_time}\n📍 {$request->location}";
                    event(new ResortNotificationEvent(Common::nofitication(
                        $this->resort->resort_id,
                        10,
                        'Meeting Scheduled',
                        $msg,
                        0,
                        $participant_id,
                        'Incident'
                    )));
                }
            }

            // External participants
            if ($request->ext_participants) {
                foreach ($request->ext_participants as $extName) {
                    if ($extName) {
                        IncidentsMeetingExternalParticipants::create([
                            'meeting_id' => $meeting->id,
                            'participant_name' => $extName,
                        ]);
                    }
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Investigation meeting created successfully!',
                'meeting_id' => $meeting->id,
                'redirect_url' => route('incident.meeting')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function details($id)
    {
        if(Common::checkRouteWisePermission('incident.meeting',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }
        $page_title ='Meeting Detail';
        $id = base64_decode($id);
        $resort_id = $this->resort->resort_id;
        $meeting = IncidentsMeeting::with(['participant.employee','incidents'])->where('id',$id)->first();  
  
        return view('resorts.incident.meeting.detail',compact('page_title','meeting'));
    }

    public function inlineUpdate(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:incidents_investigation_meetings,id',
            'meeting_date' => 'nullable|string',
            'meeting_time' => 'nullable|string'
        ]);
    
        $meeting = IncidentsMeeting::with('participant')->find($request->id);
    
        if (!$meeting) {
            return response()->json(['message' => 'Meeting not found.'], 404);
        }
    
        $updated = false;
    
        if ($request->meeting_date) {
            try {
                $formattedDate = \Carbon\Carbon::createFromFormat('d/m/Y', $request->meeting_date)->format('Y-m-d');
                $meeting->meeting_date = $formattedDate;
                $updated = true;
            } catch (\Exception $e) {
                return response()->json(['message' => 'Invalid date format.'], 400);
            }
        }
    
        if ($request->meeting_time) {
            $meeting->meeting_time = $request->meeting_time;
            $updated = true;
        }
    
        if ($updated) {
            $meeting->save();
            
            // dd($meeting->participant);
            foreach ($meeting->participant as $participant) {
                // dd($participant->participant_id);
                $msg = "Meeting Rescheduled: {$meeting->meeting_subject}\n📅 {$meeting->meeting_date}\n⏰ {$meeting->meeting_time}\n📍 {$meeting->location}";
    
                event(new ResortNotificationEvent(Common::nofitication(
                    $this->resort->resort_id,
                    10,
                    'Meeting Rescheduled',
                    $msg,
                    0,
                    $participant->participant_id,
                    'Incident'
                )));
            }
        }
    
        return response()->json(['message' => 'Meeting updated successfully.']);
    }

    public function delete(Request $request,$id){
        
        $id = base64_decode($id);
        $meeting = IncidentsMeeting::find($id);
        if (!$meeting) {
            return response()->json([
                'success' => false,
                'message' => 'Meeting not found.'
            ], 404);
        }
        
        $meeting->delete();

        IncidentsMeetingParticipants::where('meeting_id', $id)->delete();
        IncidentsMeetingExternalParticipants::where('meeting_id', $id)->delete();

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'Meeting deleted successfully.'
        ]);
    }
    
    
}
