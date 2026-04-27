<?php

namespace App\Http\Controllers\Resorts\Performance;

use DB;
use URL;
use Auth;
use Carbon\Carbon;
use Validator;
use App\Helpers\Common;
use App\Models\Employee;
use App\Models\PeformanceMeeting;
use Illuminate\Http\Request;
use App\Mail\PerformanceMeetMail;
use Illuminate\Support\Facades\Mail;

use App\Http\Controllers\Controller;
use App\Models\PerformanceMeetingContent;
use App\Models\PerformanceMeetingParticipant;
use App\Models\Resort;
use Illuminate\Support\Str;
class PerformanceMeetingController extends Controller
{


    public $resort = '';
    public $globalUser = '';
    protected $underEmp_id = [];

    public function __construct()
    {
        $this->resort = $this->globalUser = Auth::guard('resort-admin')->user();
        if (!$this->resort) return;
        if ($this->resort->is_master_admin == 0 && isset($this->globalUser->GetEmployee) && $this->globalUser->GetEmployee) {
            $reporting_to = $this->globalUser->GetEmployee->id;
            $this->underEmp_id = Common::getSubordinates($reporting_to);
        }
    }
    //
    public function index()
    {

        

        // Department-based visibility: HR/GM see everyone, other HOD/XCOM only their own dept.
        $scopedDeptIds = Common::getScopedDepartmentIds();

        $employees = Employee::with(['resortAdmin', 'position'])
        ->where('resort_id', $this->resort->resort_id)
        ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))
        ->get()
        ->map(function ($e)
        {
            $e->Emp_id = base64_encode($e->id);
            $e->positionName = $e->position->position_title;
            $e->profileImg = Common::getResortUserPicture(optional($e->resortAdmin)->id);
            $e->EmployeeName = optional($e->resortAdmin)->first_name . ' ' . optional($e->resortAdmin)->last_name;
            return $e;
        });

        $page_title="Performance Meeting";
        return view('resorts.Performance.Meeting.index',compact('page_title','employees'));
    }

    public function scheduledMeetings()
    {
        $page_title = "Scheduled Meetings";
        return view('resorts.Performance.Meeting.scheduled', compact('page_title'));
    }

    public function calendarData(Request $request)
    {
        $scopedIds = Common::getPerformanceScopedEmpIds();
        // Only show meetings that have at least one accepted participant
        $meetings = PeformanceMeeting::where('resort_id', $this->resort->resort_id)
            ->whereHas('participants', function($q) {
                $q->where('status', 'accepted');
            })
            ->when(is_array($scopedIds), function ($q) use ($scopedIds) {
                $q->whereIn('id', function ($sub) use ($scopedIds) {
                    $sub->from('performance_meeting_participants')
                        ->select('meeting_id')
                        ->whereIn('employee_id', $scopedIds);
                });
            })
            ->get()
            ->map(function ($meeting) {
                $parts = $meeting->participants()->with('employee.resortAdmin', 'employee.position')->get();

                $formatList = function ($collection) {
                    return $collection->map(function ($p) {
                        return [
                            'name'     => trim(optional(optional($p->employee)->resortAdmin)->first_name.' '.optional(optional($p->employee)->resortAdmin)->last_name) ?: 'Unknown',
                            'position' => optional(optional($p->employee)->position)->position_title ?? '',
                            'photo'    => Common::getResortUserPicture(optional($p->employee)->Admin_Parent_id ?? null),
                            'reason'   => $p->reason,
                        ];
                    })->values();
                };

                $accepted_list = $formatList($parts->where('status', 'accepted'));
                $pending_list  = $formatList($parts->where('status', 'pending'));
                $declined_list = $formatList($parts->where('status', 'declined'));

                $accepted = $accepted_list->count();
                $pending  = $pending_list->count();
                $declined = $declined_list->count();
                $total    = $accepted + $declined + $pending;

                $color = '#28a745';
                if ($pending > 0) $color = '#EFB408';

                return [
                    'id' => $meeting->id,
                    'title' => $meeting->title,
                    'start' => $meeting->date . 'T' . $meeting->start_time,
                    'end' => $meeting->date . 'T' . $meeting->end_time,
                    'color' => $color,
                    'textColor' => '#fff',
                    'description' => $meeting->description,
                    'location' => $meeting->location,
                    'conference_link' => $meeting->conference_links,
                    'accepted' => $accepted,
                    'declined' => $declined,
                    'pending' => $pending,
                    'total' => $total,
                    'accepted_list' => $accepted_list,
                    'pending_list'  => $pending_list,
                    'declined_list' => $declined_list,
                ];
            });

        return response()->json($meetings);
    }

    public function meetingSidebar(Request $request)
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $scopedIds = Common::getPerformanceScopedEmpIds();
        $meetings = PeformanceMeeting::where('resort_id', $this->resort->resort_id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->when(is_array($scopedIds), function ($q) use ($scopedIds) {
                $q->whereIn('id', function ($sub) use ($scopedIds) {
                    $sub->from('performance_meeting_participants')
                        ->select('meeting_id')
                        ->whereIn('employee_id', $scopedIds);
                });
            })
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($meeting) {
                $participants = PerformanceMeetingParticipant::where('meeting_id', $meeting->id)
                    ->with('employee.resortAdmin')
                    ->get();
                $meeting->accepted = $participants->where('status', 'accepted')->count();
                $meeting->declined = $participants->where('status', 'declined')->count();
                $meeting->pending = $participants->where('status', 'pending')->count();
                $meeting->total = $participants->count();
                $meeting->formattedDate = Carbon::parse($meeting->date)->format('D d M Y');
                $meeting->participantList = $participants->map(function ($p) {
                    return [
                        'name' => optional($p->employee->resortAdmin)->first_name . ' ' . optional($p->employee->resortAdmin)->last_name,
                        'profileImg' => Common::getResortUserPicture(optional($p->employee)->Admin_Parent_id),
                        'status' => $p->status,
                    ];
                });
                return $meeting;
            });

        $html = '';
        foreach ($meetings as $meeting) {
            $dateParts = explode(' ', $meeting->formattedDate);
            // $dateParts[0]=Wed, [1]=15, [2]=Apr, [3]=2026

            $statusBadge = '';
            if ($meeting->total > 0) {
                $statusBadge  = '<span class="mtg-badge mtg-badge-green">'.$meeting->accepted.' Accepted</span>';
                if ($meeting->pending > 0) $statusBadge  .= '<span class="mtg-badge mtg-badge-warn">'.$meeting->pending.' Pending</span>';
                if ($meeting->declined > 0) $statusBadge .= '<span class="mtg-badge mtg-badge-danger">'.$meeting->declined.' Declined</span>';
            }

            $participantHtml = '';
            foreach ($meeting->participantList as $p) {
                $statusPill = '';
                if ($p['status'] === 'accepted') {
                    $statusPill = '<span class="mtg-status mtg-status-accepted" title="Accepted"><i class="fa-solid fa-check"></i></span>';
                } elseif ($p['status'] === 'declined') {
                    $statusPill = '<span class="mtg-status mtg-status-declined" title="Declined"><i class="fa-solid fa-xmark"></i></span>';
                } else {
                    $statusPill = '<span class="mtg-status mtg-status-pending" title="Pending"><i class="fa-regular fa-clock"></i></span>';
                }
                $participantHtml .= '<div class="mtg-participant">
                    <img src="'.e($p['profileImg']).'" alt="user" class="mtg-avatar"/>
                    <span class="mtg-pname">'.e($p['name']).'</span>
                    '.$statusPill.'
                </div>';
            }

            $html .= '<div class="mtg-card">
                <div class="mtg-date">
                    <div class="mtg-day">'.($dateParts[1] ?? '').'</div>
                    <div class="mtg-month">'.($dateParts[2] ?? '').'</div>
                    <div class="mtg-weekday">'.($dateParts[0] ?? '').'</div>
                </div>
                <div class="mtg-body">
                    <h6 class="mtg-title">'.e($meeting->title).'</h6>
                    <div class="mtg-meta"><i class="fa-regular fa-clock"></i> '.e($meeting->start_time).' - '.e($meeting->end_time).'</div>'
                    .(!empty($meeting->location) ? '<div class="mtg-meta"><i class="fa-solid fa-location-dot"></i> '.e($meeting->location).'</div>' : '').
                    '<div class="mtg-badges">'.$statusBadge.'</div>
                    <div class="mtg-participants">'.$participantHtml.'</div>
                </div>
            </div>';
        }

        if (empty($html)) {
            $html = '<div class="text-center py-4 text-muted"><i class="fa-regular fa-calendar-xmark" style="font-size:40px;"></i><p class="mt-2">No meetings this month</p></div>';
        }

        return response()->json(['success' => true, 'data' => $html]);
    }

    public function meetingsList()
    {
        $page_title = "All Meetings";
        return view('resorts.Performance.Meeting.list', compact('page_title'));
    }

    public function meetingDetailPage($id)
    {
        $meeting = PeformanceMeeting::where('resort_id', $this->resort->resort_id)
            ->where('id', $id)
            ->first();

        if (!$meeting) {
            abort(404, 'Meeting not found');
        }

        $participants = $meeting->participants()->with('employee.resortAdmin', 'employee.position')->get()->map(function ($p) {
            return (object)[
                'name' => optional($p->employee->resortAdmin)->first_name . ' ' . optional($p->employee->resortAdmin)->last_name,
                'email' => optional($p->employee->resortAdmin)->email,
                'position' => optional($p->employee->position)->position_title ?? '',
                'profileImg' => Common::getResortUserPicture(optional($p->employee)->Admin_Parent_id),
                'status' => $p->status,
                'reason' => $p->reason,
                'responded_at' => $p->responded_at ? Carbon::parse($p->responded_at)->format('d M Y, h:i A') : null,
            ];
        });

        $accepted = $participants->where('status', 'accepted');
        $declined = $participants->where('status', 'declined');
        $pending = $participants->where('status', 'pending');
        $page_title = 'Meeting Details';

        return view('resorts.Performance.Meeting.view', compact('page_title', 'meeting', 'participants', 'accepted', 'declined', 'pending'));
    }

    public function meetingsListData(Request $request)
    {
        $scopedIds = Common::getPerformanceScopedEmpIds();
        $meetings = PeformanceMeeting::where('resort_id', $this->resort->resort_id)
            ->when(is_array($scopedIds), function ($q) use ($scopedIds) {
                $q->whereIn('id', function ($sub) use ($scopedIds) {
                    $sub->from('performance_meeting_participants')
                        ->select('meeting_id')
                        ->whereIn('employee_id', $scopedIds);
                });
            })
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($meeting) {
                $participants = $meeting->participants()->with('employee.resortAdmin')->get();
                $meeting->accepted_count = $participants->where('status', 'accepted')->count();
                $meeting->declined_count = $participants->where('status', 'declined')->count();
                $meeting->pending_count = $participants->where('status', 'pending')->count();
                $meeting->total_count = $participants->count();
                $meeting->declined_details = $participants->where('status', 'declined')->map(function ($p) {
                    return [
                        'name' => optional($p->employee->resortAdmin)->first_name . ' ' . optional($p->employee->resortAdmin)->last_name,
                        'reason' => $p->reason ?? 'No reason provided',
                    ];
                })->values();
                $meeting->accepted_names = $participants->where('status', 'accepted')->map(function ($p) {
                    return optional($p->employee->resortAdmin)->first_name . ' ' . optional($p->employee->resortAdmin)->last_name;
                })->values();
                $meeting->pending_names = $participants->where('status', 'pending')->map(function ($p) {
                    return optional($p->employee->resortAdmin)->first_name . ' ' . optional($p->employee->resortAdmin)->last_name;
                })->values();
                return $meeting;
            });

        return datatables()->of($meetings)
            ->editColumn('title', function ($row) {
                return e($row->title);
            })
            ->editColumn('date', function ($row) {
                return Carbon::parse($row->date)->format('d M Y');
            })
            ->addColumn('time', function ($row) {
                return $row->start_time . ' - ' . $row->end_time;
            })
            ->addColumn('location_link', function ($row) {
                $loc = '';
                if ($row->location) $loc .= e($row->location);
                if ($row->location && $row->conference_links) $loc .= '<br>';
                if ($row->conference_links) $loc .= '<small class="text-muted">' . e($row->conference_links) . '</small>';
                return $loc ?: 'N/A';
            })
            ->addColumn('accepted', function ($row) {
                $tooltip = $row->accepted_names->implode(', ');
                return '<span class="badge badge-green" data-bs-toggle="tooltip" title="' . e($tooltip) . '">' . $row->accepted_count . ' Accepted</span>';
            })
            ->addColumn('declined', function ($row) {
                if ($row->declined_count == 0) return '<span class="badge badge-themeLight">0</span>';
                $details = '';
                foreach ($row->declined_details as $d) {
                    $details .= e($d['name']) . ': ' . e($d['reason']) . '&#10;';
                }
                return '<span class="badge badge-danger" data-bs-toggle="tooltip" data-bs-html="true" title="' . $details . '">' . $row->declined_count . ' Declined</span>';
            })
            ->addColumn('pending', function ($row) {
                if ($row->pending_count == 0) return '<span class="badge badge-themeLight">0</span>';
                $tooltip = $row->pending_names->implode(', ');
                return '<span class="badge badge-themeWarning" data-bs-toggle="tooltip" title="' . e($tooltip) . '">' . $row->pending_count . ' Pending</span>';
            })
            ->addColumn('total', function ($row) {
                return $row->total_count;
            })
            ->addColumn('action', function ($row) {
                return '<a href="' . route('Performance.Meeting.view', $row->id) . '" class="btn-lg-icon icon-bg-skyblue"><i class="fa-regular fa-eye"></i></a>';
            })
            ->rawColumns(['location_link', 'accepted', 'declined', 'pending', 'action'])
            ->make(true);
    }

   public function SendMeetingLink(Request $request)
{
    $validator = Validator::make($request->all(), [
        'title' => 'required|string|max:255',
        'date' => 'required|date_format:m/d/Y',
        'start_time' => 'required|date_format:H:i',
        'end_time' => 'required|date_format:H:i|after:start_time',
        'location' => 'nullable|string|max:255|required_without:conference_link',
        'conference_link' => 'nullable|string|max:255|required_without:location',
        'description' => 'nullable|string|max:500',
        'Emp_id' => 'required|array|min:1',
    ], [
        'title.required' => 'Please provide a meeting title.',
        'date.required' => 'Please provide a meeting date.',
        'start_time.required' => 'Please provide a start time.',
        'end_time.required' => 'Please provide an end time.',
        'end_time.after' => 'End time must be after start time.',
        'location.required_without' => 'Please provide either a Location or Meeting Link.',
        'conference_link.required_without' => 'Please provide either a Meeting Link or Location.',
        'Emp_id.required' => 'Please select at least one employee.',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    try {
        DB::beginTransaction();

        $meeting = PeformanceMeeting::create([
            'resort_id' => $this->resort->resort_id,
            'title' => $request->title,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'date' => Carbon::parse($request->date)->format('Y-m-d'),
            'location' => $request->location,
            'conference_links' => $request->conference_link,
            'description' => $request->description,
            // Track the organizer so accept/decline can notify them.
            'created_by' => $this->resort->id,
        ]);

        $content = PerformanceMeetingContent::where('resort_id', $this->resort->resort_id)->first();
        $emailTemplate = $content->content ?? '';

        // Use default template if none configured
        if (empty(trim(strip_tags($emailTemplate)))) {
            $emailTemplate = '
                <div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;">
                    <div style="background:#014653;color:#fff;padding:20px;border-radius:8px 8px 0 0;text-align:center;">
                        <h2 style="margin:0;">Performance Meeting Invitation</h2>
                    </div>
                    <div style="padding:24px;background:#fff;border:1px solid #e0e0e0;">
                        <p>Dear <strong>{Employee_name}</strong>,</p>
                        <p>You are invited to attend a performance meeting. Please find the details below:</p>
                        <table style="width:100%;border-collapse:collapse;margin:16px 0;">
                            <tr><td style="padding:8px 12px;border:1px solid #e0e0e0;background:#f8f9fa;font-weight:600;width:140px;">Title</td><td style="padding:8px 12px;border:1px solid #e0e0e0;">{Title}</td></tr>
                            <tr><td style="padding:8px 12px;border:1px solid #e0e0e0;background:#f8f9fa;font-weight:600;">Date</td><td style="padding:8px 12px;border:1px solid #e0e0e0;">{Meeting_Date}</td></tr>
                            <tr><td style="padding:8px 12px;border:1px solid #e0e0e0;background:#f8f9fa;font-weight:600;">Time</td><td style="padding:8px 12px;border:1px solid #e0e0e0;">{Meeting_Time}</td></tr>
                            <tr><td style="padding:8px 12px;border:1px solid #e0e0e0;background:#f8f9fa;font-weight:600;">Location</td><td style="padding:8px 12px;border:1px solid #e0e0e0;">{Meeting_Location}</td></tr>
                            <tr><td style="padding:8px 12px;border:1px solid #e0e0e0;background:#f8f9fa;font-weight:600;">Meeting Link</td><td style="padding:8px 12px;border:1px solid #e0e0e0;">{Meeting_Link}</td></tr>
                            <tr><td style="padding:8px 12px;border:1px solid #e0e0e0;background:#f8f9fa;font-weight:600;">Description</td><td style="padding:8px 12px;border:1px solid #e0e0e0;">{Description}</td></tr>
                        </table>
                        {Accept_Decline_Buttons}
                    </div>
                    <div style="padding:16px 24px;background:#f8f9fa;border:1px solid #e0e0e0;border-top:none;border-radius:0 0 8px 8px;">
                        <p style="margin:0;color:#666;">Best regards,<br><strong>{Your_Name}</strong><br>{Your_Designation}<br>{Resort_Name}</p>
                    </div>
                </div>
            ';
        } else {
            // Append accept/decline buttons to custom template if not already included
            if (strpos($emailTemplate, '{Accept_Decline_Buttons}') === false) {
                $emailTemplate .= '{Accept_Decline_Buttons}';
            }
        }

        $Your_Name = $this->resort->first_name . ' ' . $this->resort->last_name;
        $Designation = $this->resort->is_employee == 1
            ? optional($this->resort->GetEmployee->position)->position_title
            : 'GM';

        $resort_details = Resort::find($this->resort->resort_id);

        foreach ($request->Emp_id as $encodedId) {
            $employee = Employee::with(['resortAdmin', 'position'])->find(base64_decode($encodedId));

            if (!$employee || !$employee->resortAdmin) continue;

            // Create participant record with unique token
            $token = Str::random(48);
            PerformanceMeetingParticipant::create([
                'meeting_id' => $meeting->id,
                'employee_id' => $employee->id,
                'resort_id' => $this->resort->resort_id,
                'status' => 'pending',
                'token' => $token,
            ]);

            $EmployeeName = $employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name;
            $respondUrl = route('meeting.respond', ['token' => $token]);

            $placeholders = [
                "{Employee_name}",
                "{Title}",
                "{Meeting_Date}",
                "{Meeting_Time}",
                "{Meeting_Location}",
                "{Description}",
                "{Your_Name}",
                "{Your_Designation}",
                "{Resort_Name}",
                "{Meeting_Link}",
                "{Accept_Decline_Buttons}"
            ];

            $acceptDeclineHtml = '
                <div style="margin-top:20px;padding:15px;background:#f8f9fa;border-radius:8px;text-align:center;">
                    <p style="margin-bottom:12px;font-weight:600;">Please respond to this meeting invitation:</p>
                    <a href="'.$respondUrl.'?action=accept" style="display:inline-block;padding:10px 30px;background:#014653;color:#fff;text-decoration:none;border-radius:6px;margin-right:10px;font-weight:500;">Accept</a>
                    <a href="'.$respondUrl.'?action=decline" style="display:inline-block;padding:10px 30px;background:#dc3545;color:#fff;text-decoration:none;border-radius:6px;font-weight:500;">Decline</a>
                </div>';

            $replacements = [
                $EmployeeName,
                $request->title,
                Carbon::parse($request->date)->format('d M Y'),
                $request->start_time . ' to ' . $request->end_time,
                $request->location ?? 'N/A',
                $request->description ?? '',
                $Your_Name,
                $Designation,
                $resort_details->resort_name ?? 'Resort',
                $request->conference_link ?? 'N/A',
                $acceptDeclineHtml
            ];

            $finalBody = str_replace($placeholders, $replacements, $emailTemplate);
            Mail::to($employee->resortAdmin->email)->queue(
                new PerformanceMeetMail($request->title, $finalBody)
            );
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Meeting request has been sent to employee(s).',
        ]);
    } catch (\Exception $e) {
        DB::rollBack();

        \Log::emergency("File: " . $e->getFile());
        \Log::emergency("Line: " . $e->getLine());
        \Log::emergency("Message: " . $e->getMessage());

        return response()->json(['error' => 'Failed to send meeting link.'], 500);
    }
}

    // public function SendMeetingLink(Request $request)
    // {

    //     $title = $request->title;
    //     $date  = $request->date;
    //     $start_time = $request->start_time;
    //     $end_time = $request->end_time;
    //     $date = $request->date;
    //     $location = $request->location;
    //     $conference_link = $request->conference_link;
    //     $description = $request->description;
    //     $Emp_id = $request->Emp_id;
    //     $validator = Validator::make($request->all(), [
    //         'title' => 'required|string|max:255',
    //         'date' => 'required',
    //         'start_time' => 'required|date_format:H:i',
    //         'end_time' => 'required|date_format:H:i', // Removed after:start_time
    //         'location' => 'required|string|max:255',
    //         'conference_link' => 'required|string|max:255', // Changed from url to string
    //         'description' => 'required|string|max:500',
    //         'Emp_id' => 'required|array|min:1',
    //     ], [
    //         'title.required' => 'Please provide a meeting title.',
    //         'date.required' => 'Please provide a meeting date.',
    //         'start_time.required' => 'Please provide a start time.',
    //         'end_time.required' => 'Please provide an end time.',
    //         'location.required' => 'Please provide a location.',
    //         'conference_link.required' => 'Please provide a conference link.',
    //         'description.required' => 'Please provide a description.',
    //         'Emp_id.required' => 'Please select at least one employee.',
    //     ]);

    //     // Check if validation fails

    //     if ($validator->fails()) {
    //         return response()->json(['errors' => $validator->errors()], 422);
    //     }
    //     PeformanceMeeting::create([
    //         'resort_id'=>$this->resort->resort_id,
    //         'title'=>$title,
    //         'start_time'=>$start_time,
    //         'end_time'=>$end_time,
    //         'date'=>Carbon::parse($date)->format('Y-m-d'),
    //         'location'=>$location,
    //         'conference_links'=>$conference_link,
    //         'description'=>$description,
    //     ]);
    //     $content = PerformanceMeetingContent::where('resort_id',$this->resort->resort_id)->first();

    //     $body = $content->content;



    //     $Your_Name = $this->resort->first_name. '  '. $this->resort->last_name;
    //     $Designation='';
    //     if($this->resort->is_employee ==1)
    //     {
    //         $Designation =$this->resort->GetEmployee->position->position_title;
    //     }
    //     else
    //     {
    //         $Designation ='GM';
    //     }
    //     $resort_details =  Resort::where('id',  $this->resort->resort_id)->first();
       

    //         if(count($Emp_id)> 0)
    //         {
    //             foreach ($Emp_id as $key => $id)
    //             {
    //                 $employees = Employee::with(['resortAdmin', 'position'])->where('id',base64_decode($id))->first();
    //                 $EmployeeName = optional($employees->resortAdmin)->first_name . ' ' . optional($employees->resortAdmin)->last_name;
    //                 $data= [];

    //             $healthy = [
    //                 "{Employee_name}",
    //                 "{Title}",
    //                 "{Meeting_Date}",
    //                 "{Meeting_Time}",

    //                 "{Description}",
    //                 "{Your_Name}",
    //                 "{Your_Designation}",
    //                 "{Resort_Name}",
    //             ];

    //             $yummy = [
    //                 $EmployeeName,
    //                 $title,
    //                 $start_time .' to '. $end_time ,
    //                 $date,

    //                 $description,
    //                 $Your_Name,
    //                 $Designation,
    //                 $resort_details->resort_name
    //             ];
    //                 $emailTemplate =  $body;

    //                 $newbody = str_replace($healthy, $yummy, $emailTemplate);

    //                 Mail::to($employees->resortAdmin->email)->queue(new PerformanceMeetMail($title,$newbody));


    //             }

    //         }
    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Meeting Link to employee Successfully',
    //         ], 200);
    //      try
    //     {}
    //     catch (\Exception $e)
    //     {
    //         DB::rollBack();
    //         \Log::emergency("File: " . $e->getFile());
    //         \Log::emergency("Line: " . $e->getLine());
    //         \Log::emergency("Message: " . $e->getMessage());
    //         return response()->json(['error' => 'Failed sent  Meeting Link'], 500);
    //     }
    // }

    public function GetPerformanceEmp(Request $request)
    {
        $searchValue = trim((string) ($request->input('searchValue', '')));
        $department = $request->input('department');
        $position = $request->input('position');
        $employment_grade = $request->input('employment_grade');
        $gender = $request->input('gender');

        // Restrict to the logged-in user's department unless they have full access (HR/GM).
        $scopedDeptIds = Common::getScopedDepartmentIds();

        $employees = Employee::with(['resortAdmin', 'position'])
            ->where('resort_id', $this->resort->resort_id)
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds));

        if ($searchValue !== '') {
            $employees->where(function ($query) use ($searchValue) {
                $query->whereHas('resortAdmin', function ($q) use ($searchValue) {
                    $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$searchValue}%"]);
                })
                ->orWhereHas('position', function ($q) use ($searchValue) {
                    $q->where('position_title', 'LIKE', "%{$searchValue}%");
                });
            });
        }

        $departments = $this->normalizeFilterArray($department, true);
        if (!empty($departments)) {
            $employees->whereIn('Dept_id', $departments);
        }
        $positions = $this->normalizeFilterArray($position, true);
        if (!empty($positions)) {
            $employees->whereIn('Position_id', $positions);
        }
        $grades = $this->normalizeFilterArray($employment_grade, true);
        if (!empty($grades)) {
            $employees->whereIn('rank', $grades);
        }
        $genders = $this->normalizeFilterArray($gender, false);
        if (!empty($genders)) {
            $employees->whereHas('resortAdmin', function ($q) use ($genders) {
                $q->whereIn('gender', $genders);
            });
        }

        $employees = $employees->distinct()
                                    ->get()
                                    ->map(function ($e) {
                                        $e->Emp_id = base64_encode($e->id);
                                        $e->positionName = optional($e->position)->position_title;
                                        $e->profileImg = Common::getResortUserPicture(optional($e->resortAdmin)->id);
                                        $e->EmployeeName = optional($e->resortAdmin)->first_name . ' ' . optional($e->resortAdmin)->last_name;
                                        return $e;
                                    });
            return response()->json([
                'success' => true,
                'data' => $employees->values()->all(),
            ], 200);
    }

    /**
     * Normalize filter input to array of scalar values (for department, position, grade, gender).
     * @param bool $castInt For Dept_id, Position_id, rank use true so values are int for whereIn.
     */
    private function normalizeFilterArray($value, bool $castInt = false): array
    {
        if ($value === null || $value === '') {
            return [];
        }
        $raw = [];
        if (is_array($value)) {
            foreach ($value as $v) {
                $v = is_scalar($v) ? trim((string) $v) : '';
                if ($v !== '' && $v !== 'Department') {
                    $raw[] = $castInt ? (int) $v : $v;
                }
            }
        } else {
            $v = trim((string) $value);
            if ($v !== '' && $v !== 'Department') {
                $raw[] = $castInt ? (int) $v : $v;
            }
        }
        return array_values($raw);
    }

    public function showMeetingResponse($token)
    {
        $participant = PerformanceMeetingParticipant::where('token', $token)->first();

        if (!$participant) {
            return view('resorts.Performance.Meeting.response', [
                'error' => 'Invalid or expired meeting link.',
                'meeting' => null,
                'participant' => null,
                'action' => null
            ]);
        }

        $meeting = $participant->meeting;
        $action = request('action', null);

        // Auto-accept if action=accept and not yet responded
        if ($action === 'accept' && $participant->status === 'pending') {
            $participant->update([
                'status' => 'accepted',
                'responded_at' => now(),
            ]);
            $this->notifyMeetingResponse($participant, $meeting, 'accepted');
            return view('resorts.Performance.Meeting.response', [
                'error' => null,
                'meeting' => $meeting,
                'participant' => $participant,
                'action' => 'accepted',
                'message' => 'You have accepted the meeting invitation.'
            ]);
        }

        return view('resorts.Performance.Meeting.response', [
            'error' => null,
            'meeting' => $meeting,
            'participant' => $participant,
            'action' => $action
        ]);
    }

    public function submitMeetingResponse(Request $request, $token)
    {
        $participant = PerformanceMeetingParticipant::where('token', $token)->first();

        if (!$participant) {
            return back()->with('error', 'Invalid or expired meeting link.');
        }

        $status = $request->input('status');
        $reason = $request->input('reason', '');

        if ($status === 'declined' && empty(trim($reason))) {
            return back()->with('error', 'Please provide a reason for declining.')->withInput();
        }

        $participant->update([
            'status' => $status,
            'reason' => $reason,
            'responded_at' => now(),
        ]);

        $this->notifyMeetingResponse($participant, $participant->meeting, $status, $reason);

        $message = $status === 'accepted'
            ? 'You have accepted the meeting invitation.'
            : 'You have declined the meeting invitation.';

        return view('resorts.Performance.Meeting.response', [
            'error' => null,
            'meeting' => $participant->meeting,
            'participant' => $participant,
            'action' => $status,
            'message' => $message
        ]);
    }

    /**
     * Tell the meeting organizer (and any GM, as a fallback for legacy meetings without
     * created_by) that an invitee accepted or declined.
     */
    private function notifyMeetingResponse($participant, $meeting, $status, $reason = '')
    {
        if (!$meeting) return;

        // Resolve the organizer's employee id from the resort_admin id stored on the meeting.
        $recipients = [];
        if (!empty($meeting->created_by)) {
            $organizerEmp = Employee::where('Admin_Parent_id', $meeting->created_by)->first(['id']);
            if ($organizerEmp) $recipients[] = (int) $organizerEmp->id;
        }
        // Fallback for legacy meetings (created_by is NULL) — notify GM(s).
        if (empty($recipients)) {
            $recipients = Employee::where('resort_id', $meeting->resort_id)
                ->where('rank', 8)->pluck('id')->all();
        }

        // Resolve the responder's display name.
        $responder = $participant->employee()->with('resortAdmin')->first();
        $name = $responder && $responder->resortAdmin
            ? trim(($responder->resortAdmin->first_name ?? '') . ' ' . ($responder->resortAdmin->last_name ?? ''))
            : 'A participant';
        if ($name === '') $name = 'A participant';

        $title = $status === 'accepted' ? 'Meeting Accepted' : 'Meeting Declined';
        $msg = $status === 'accepted'
            ? $name . ' accepted "' . ($meeting->title ?? 'the meeting') . '".'
            : $name . ' declined "' . ($meeting->title ?? 'the meeting') . '".'
                . ($reason ? ' Reason: ' . $reason : '');

        try {
            Common::notifyEmployees(
                $meeting->resort_id,
                $recipients,
                $title,
                $msg,
                'Performance',
                $meeting->id
            );
        } catch (\Exception $e) {
            \Log::warning('Meeting response notification failed: ' . $e->getMessage());
        }
    }
}
