<?php

namespace App\Http\Controllers\Resorts\Incident;

use App\Http\Controllers\Controller;
use App\Events\ResortNotificationEvent;
use Illuminate\Support\Facades\Mail;
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
        if(!$this->resort) return;
        // dd( $this->resort);
        $this->reporting_to = isset($this->resort->GetEmployee) ? $this->resort->GetEmployee->id:0;
        $this->underEmp_id = Common::getSubordinates($this->reporting_to);
    }
    
    public function index()
    {
        // Page is open to every logged-in user. Data is scoped via
        // Common::scopeIncidentsForViewer() so non-HR/GM HOD/EXCOM only
        // see meetings tied to their own dept's incidents.
        $page_title ='Investigation Meeting';
        $incidents = Common::scopeIncidentsForViewer(Incidents::query())
            ->where('status', '!=', 'Resolved')
            ->orderByDesc('id')
            ->get(['id','incident_id','incident_name']);
        $canCreate = Common::checkRouteWisePermission('incident.meeting', config('settings.resort_permissions.create'));
        return view('resorts.incident.meeting.index', compact('page_title','incidents','canCreate'));
    }

    public function list(Request $request)
    {
        if ($request->ajax()) {
            // Participant-only meeting visibility — see Common::scopeMeetingsForViewer.
            // Previously listed meetings whose parent incident the user
            // had dept-level access to; now restricted to meetings where
            // the user is a participant or the incident reporter.
            $resortId = $this->resort->resort_id;
            $incident_meetings = IncidentsMeeting::with(['participant.employee','incidents'])
                ->whereHas('incidents', function ($q) use ($resortId) {
                    $q->where('resort_id', $resortId);
                });
            $incident_meetings = Common::scopeMeetingsForViewer($incident_meetings);

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
                            <a href="' . route('incident.meeting.detail', e($id)) . '" title="View Meeting Detail" class="btn-tableIcon btnIcon-blue me-1">
                                <i class="fa-regular fa-eye"></i>
                            </a>
                            <a href="javascript:void(0)" class="btn-tableIcon btnIcon-yellow me-1 edit-row-btn '.$edit_class.'" data-meeting-id="' . e($id) . '">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <a href="javascript:void(0)" class="btn-tableIcon eb-icon-critical delete-row-btn '.$delete_class.'" data-meeting-id="' . e($id) . '">
                                <i class="fa-regular fa-trash-can"></i>
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
        // Block direct-URL access for users who can't see this incident.
        $incident = Common::scopeIncidentsForViewer(Incidents::query())
            ->where('id', $incident_id)
            ->first();
        if (!$incident) {
            abort(403, 'You are not allowed to create meetings for this incident.');
        }
        $status = ['Active','OnLeave','Probationary','contractual'];
        $participants = Employee::with('resortAdmin')->where('resort_id',$resort_id)->wherein('status',$status)->get();

        // Past meetings on this incident — surfaced in the "Previous Notes /
        // Findings" section so the user has context before scheduling a new one.
        $previousMeetings = IncidentsMeeting::where('incident_id', $incident->id)
            ->orderByDesc('meeting_date')
            ->orderByDesc('meeting_time')
            ->get(['id','meeting_subject','meeting_date','meeting_time','meeting_agenda']);

        return view('resorts.incident.meeting.create', compact('page_title','participants','incident','previousMeetings'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'incidentId' => [
                'required', 'integer',
                Rule::exists('incidents', 'id')->where('resort_id', $this->resort->resort_id),
            ],
            'meeting_subject' => 'required|string|max:255',
            'meeting_date' => 'required|date_format:d/m/Y',
            'meeting_time' => 'required',
            'location' => 'nullable|string|max:255',
            'meeting_type' => 'required|in:Online,Physical',
            'participants' => 'nullable|array',
            'participants.*' => [
                Rule::exists('employees', 'id')->where('resort_id', $this->resort->resort_id),
            ],
            'roles' => 'nullable|array',
            'roles.*' => 'nullable|string|max:255',
            'ext_participants' => 'nullable|array',
            'ext_participants.*' => 'nullable|string|max:255',
            'meeting_agenda' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:51200', // 50 MB
        ]);

        try {
            $employee = $this->resort->getEmployee;
        
            if (!$employee) 
            {
                return response()->json(['success' => false, 'message' => 'Employee not found.'], 404);
            }

            $uploadedFiles = [];
            $uploadErrors  = [];

            if ($request->hasFile('attachments'))
            {
                foreach ($request->file('attachments') as $file)
                {
                    $status = Common::AWSEmployeeFileUpload($this->resort->resort_id, $file, $employee->Emp_id, null, true);

                    if (!empty($status['status']) && !empty($status['Chil_file_id']))
                    {
                        $uploadedFiles[] = [
                            'Filename' => $file->getClientOriginalName(),
                            'Child_id' => $status['Chil_file_id'],
                        ];
                    }
                    else
                    {
                        $uploadErrors[] = $file->getClientOriginalName() . ': ' . ($status['msg'] ?? 'upload failed');
                    }
                }
            }

            if (!empty($uploadErrors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some attachments failed to upload',
                    'errors'  => $uploadErrors,
                ], 422);
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

            // Save participants. Track which participant_ids have already
            // been notified within this request so a duplicate selection
            // (same person in the participants[] payload twice) only
            // triggers ONE notification — the create() can still happen
            // per-row if intentional (different roles) but the user
            // shouldn't see the bell ring twice.
            if ($request->participants) {
                $notifiedParticipantIds = [];
                foreach ($request->participants as $i => $participant_id) {
                    IncidentsMeetingParticipants::create([
                        'meeting_id' => $meeting->id,
                        'participant_id' => $participant_id,
                        'participant_role' => $request->roles[$i] ?? null,
                    ]);

                    if (in_array($participant_id, $notifiedParticipantIds, true)) {
                        continue;
                    }
                    $notifiedParticipantIds[] = $participant_id;

                    $msg = "Meeting Scheduled: {$request->meeting_subject}\n📅 " . Common::formatDate($request->meeting_date)
                        . "\n⏰ " . Common::formatDisplayTime($request->meeting_time) . "\n📍 {$request->location}";
                    event(new ResortNotificationEvent(Common::nofitication(
                        $this->resort->resort_id,
                        10,
                        'Meeting Scheduled',
                        $msg,
                        0,
                        $participant_id,
                        'Incident'
                    )));

                    // Email — same content as the bell notification.
                    try {
                        $partEmp   = \App\Models\Employee::where('resort_id', $this->resort->resort_id)->find($participant_id);
                        $partAdmin = $partEmp ? \App\Models\ResortAdmin::find($partEmp->Admin_Parent_id) : null;
                        if ($partAdmin && filter_var($partAdmin->email ?? '', FILTER_VALIDATE_EMAIL)) {
                            $name = trim(($partAdmin->first_name ?? '') . ' ' . ($partAdmin->last_name ?? '')) ?: 'there';
                            $bodyHtml = "You have been invited to a meeting in the Incident module.";
                            // Was Mail::send() — blocking SMTP round-trip per
                            // participant in a loop. Mailer::queue() only
                            // accepts Mailable instances, not the raw
                            // view+closure form, hence IncidentNotificationMail
                            // (same class IncidentController::notifyByEmail() uses).
                            Mail::to($partAdmin->email, $name)->queue(
                                new \App\Mail\IncidentNotificationMail(
                                    $name,
                                    'Meeting scheduled: ' . $request->meeting_subject,
                                    $bodyHtml,
                                    [
                                        'Meeting'  => $request->meeting_subject,
                                        'Date'     => $request->meeting_date,
                                        'Time'     => $request->meeting_time,
                                        'Location' => $request->location ?? '—',
                                    ],
                                    route('incident.meeting'),
                                    'View meetings'
                                )
                            );
                        }
                    } catch (\Throwable $e) {
                        \Log::warning('Incident meeting email failed for participant ' . $participant_id . ': ' . $e->getMessage());
                    }
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
        // Participant-only meeting visibility — direct-URL access to a
        // meeting the user isn't invited to (and didn't report the parent
        // incident) is blocked.
        $meeting = Common::scopeMeetingsForViewer(IncidentsMeeting::with(['participant.employee','externalParticipant','incidents']))
            ->where('incidents_investigation_meetings.id', $id)
            ->first();
        if (!$meeting) {
            abort(404, 'Meeting not found or not accessible.');
        }

        // Other meetings on the same incident — surfaced under "Previous
        // Notes / Findings" so the user has context for this one. Excludes
        // the current meeting itself.
        $previousMeetings = collect();
        if ($meeting) {
            $previousMeetings = IncidentsMeeting::where('incident_id', $meeting->incident_id)
                ->where('id', '!=', $meeting->id)
                ->orderByDesc('meeting_date')
                ->orderByDesc('meeting_time')
                ->get(['id','meeting_subject','meeting_date','meeting_time','meeting_agenda']);
        }

        return view('resorts.incident.meeting.detail',compact('page_title','meeting','previousMeetings'));
    }

    public function inlineUpdate(Request $request)
    {
        // Frontend sends the meeting id base64-encoded (matches the rest of the
        // module — list/details/delete all use base64 ids). Decode before
        // validating so the integer/exists rule passes.
        $request->merge(['id' => base64_decode($request->id, true) ?: $request->id]);

        $request->validate([
            'id' => 'required|integer|exists:incidents_investigation_meetings,id',
            'meeting_date' => 'nullable|string',
            'meeting_time' => 'nullable|string'
        ]);

        $meeting = IncidentsMeeting::with('participant')->find($request->id);

        if (!$meeting) {
            return response()->json(['message' => 'Meeting not found.'], 404);
        }
        // Participant-only gate — block inline updates for meetings the
        // viewer isn't a participant of (or didn't report the parent
        // incident, or isn't privileged).
        $canSee = Common::scopeMeetingsForViewer(IncidentsMeeting::query())
            ->where('id', $meeting->id)
            ->exists();
        if (!$canSee) {
            return response()->json(['message' => 'Not authorised.'], 403);
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
                $msg = "Meeting Rescheduled: {$meeting->meeting_subject}\n📅 " . Common::formatDate($meeting->meeting_date)
                    . "\n⏰ " . Common::formatDisplayTime($meeting->meeting_time) . "\n📍 {$meeting->location}";
    
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
        // Participant-only gate — only privileged users, participants,
        // or the incident reporter can delete the meeting.
        $canSee = Common::scopeMeetingsForViewer(IncidentsMeeting::query())
            ->where('id', $meeting->id)
            ->exists();
        if (!$canSee) {
            return response()->json([
                'success' => false,
                'message' => 'Not authorised.'
            ], 403);
        }

        // Grab participants + meeting details before the row (and its
        // participant rows) are deleted below, so there's still something
        // to notify with — same content shape as the "scheduled" message
        // in store(), just worded as cancelled.
        $participantIds = IncidentsMeetingParticipants::where('meeting_id', $meeting->id)
            ->pluck('participant_id')->filter()->unique()->values()->all();
        $cancelMsg = "Meeting Cancelled: {$meeting->meeting_subject}\n📅 " . Common::formatDate($meeting->meeting_date)
            . "\n⏰ " . Common::formatDisplayTime($meeting->meeting_time) . "\n📍 {$meeting->location}";
        $incidentId = $meeting->incident_id;

        $meeting->delete();

        IncidentsMeetingParticipants::where('meeting_id', $id)->delete();
        IncidentsMeetingExternalParticipants::where('meeting_id', $id)->delete();

        try {
            Common::notifyEmployees(
                $this->resort->resort_id,
                $participantIds,
                'Meeting Cancelled',
                $cancelMsg,
                'Incident',
                $incidentId
            );
        } catch (\Exception $e) {
            \Log::warning('Incident meeting cancel notification failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'Meeting deleted successfully.'
        ]);
    }
    
    
}
