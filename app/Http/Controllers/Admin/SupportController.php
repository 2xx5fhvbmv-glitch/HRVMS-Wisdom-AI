<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use App\Mail\SupportReplyEmail;
use App\Models\Admin;
use App\Models\Support;
use App\Models\SupportChatMessage;
use App\Models\SupportMessages;
use App\Models\Resort;
use App\Helpers\Common;
use File;
use DB;

class SupportController extends Controller
{

    public function index()
    {
        // return view('admin.manufecturers.index');
        $type = Auth::guard('admin')->user()->type;
        // dd($type);
        $data = new Support;
        $support_admins = Admin::where('status','active')->where('role_id',4)->get();
        // dd($admins);
        return view('admin.support.index',compact('data','support_admins'));
    }

    public function list()
    {
        $type = Auth::guard('admin')->user()->type;
        $loginAdmin = Auth::guard('admin')->user()->id;
    
        // Fetch support tickets with related categories and creators.
        // Eager-load the employee + position chain so the per-row Position
        // column doesn't trigger N+1 queries.
        $query = Support::with([
            'support_category',
            'createdBy.GetEmployee.position',
        ])->orderBy('created_at', 'DESC');
    
        // If the user is not a super admin, filter tickets assigned to them
        if ($type != "super") {
            $query->where('assigned_to', $loginAdmin);
        }
    
        $supports = $query->get();

        // Unread chat message counts per support_id, scoped to the logged-in
        // admin. Powers the badge on the Chat button so admins can see
        // tickets with new replies at a glance.
        $unreadByTicket = SupportChatMessage::whereIn('support_id', $supports->pluck('id'))
            ->where('receiver_id', $loginAdmin)
            ->where('receiver_type', 'admin')
            ->where('is_read', 0)
            ->select('support_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('support_id')
            ->pluck('cnt', 'support_id');

        // Unread EMAIL-REPLY counts per ticket — every resort-side reply
        // until an admin opens the ticket. Drives the badge on the
        // Email Reply button + the aggregate count on the sidebar nav.
        $unreadEmailByTicket = SupportMessages::whereIn('ticket_id', $supports->pluck('id'))
            ->where('sender', 'employee')
            ->where('is_read', 0)
            ->select('ticket_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('ticket_id')
            ->pluck('cnt', 'ticket_id');

        // Get unique resort IDs from the support tickets
        $resortIds = $supports->pluck('resort_id')->unique();
    
        // Fetch resorts and their business hours
        $resorts = Resort::with('businessHours')->whereIn('id', $resortIds)->get()->keyBy('id');
    
        // Get the current day of the week and time
        $currentDay = now()->format('l');
        $currentTime = now()->format('H:i:s');
    
        return datatables()->of($supports)
            ->addColumn('checkbox', function ($support) {
                return '<input type="checkbox" name="support_checkbox[]" class="support_checkbox" value="' . $support->id . '" />';
            })
            ->addColumn('ticketID', function ($support) {
                return $support->ticketID ?? 'N/A';
            })
            ->addColumn('employee_name', function ($support) {
                $image = Common::getResortUserPicture($support->createdBy);
                $name = optional($support->createdBy)->first_name ?
                    ucwords($support->createdBy->first_name . ' ' . $support->createdBy->last_name) : 'N/A';
    
                return '<div class="tableUser-block">
                            <div class="img-circle"><img src="' . $image . '" alt="user"></div>
                            <span class="userApplicants-btn">' . $name . '</span>
                        </div>';
            })
            ->addColumn('position', function ($support) {
                $employee = optional($support->createdBy)->GetEmployee;
                return optional(optional($employee)->position)->position_title ?? 'N/A';
            })
            ->addColumn('resort_name', function ($support) use ($resorts) {
                $resort = $resorts[$support->resort_id] ?? null;
                return $resort->resort_name ?? 'N/A';
            })
            ->addColumn('category', function ($support) {
                return $support->support_category->name ?? 'N/A';
            })
            ->addColumn('subject', function ($support) {
                return $support->subject;
            })
            ->addColumn('created_at', function ($support) {
                return \Carbon\Carbon::parse($support->created_at)->format('d M Y');
            })
            ->addColumn('status', function ($support) {
                $statusClasses = [
                    'New' => 'badge-success',
                    'On Hold' => 'badge-warning',
                    'In Progress' => 'badge-info',
                    'Close' => 'badge-danger'
                ];
                $class = $statusClasses[$support->status] ?? 'badge-secondary';
                return '<span class="badge ' . $class . '">' . $support->status . '</span>';
            })
            ->addColumn('action', function ($support) use ($loginAdmin, $type, $resorts, $currentDay, $currentTime, $unreadByTicket, $unreadEmailByTicket) {
                $resort = $resorts[$support->resort_id] ?? null;
                if (!$resort) return '-';
            
                // Fetch support preferences and SLA
                // $supportPreferences = explode(",", $resort->support_preference);
                $is24x7 = $resort->Support_SLA === '24/7 support';
                
            
                // Determine if current time is within business hours
                $isWithinBusinessHours = false;
                if ($is24x7) {
                    $isWithinBusinessHours = true;
                } else {
                    $businessHour = $resort->businessHours->firstWhere('day_of_week', $currentDay);
                    if ($businessHour) {
                        $isWithinBusinessHours = $currentTime >= $businessHour->start_time && $currentTime <= $businessHour->end_time;
                    }
                }
            
                // Restrict action if SLA is business hours only and current time is outside business hours
                if (!$isWithinBusinessHours) {
                    return '<span class="badge badge-warning">Support available during business hours</span>';
                }
            
                // Ticket URLs
                $assign_url = route('admin.supports.assign', base64_encode($support->id));
                $chat_url = route('admin.support.chat', base64_encode($support->id));
                $edit_url = route('admin.supports.edit', base64_encode($support->id));
                $view_url = route('admin.supports.view', base64_encode($support->id));
            
                // Ticket assignment status
                $isAssigned = !empty($support->assigned_to);
                $isAssignedToMe = $support->assigned_to == $loginAdmin;
            
                // Action buttons
                $assignButton = '';
                $chatButton = '';
                $emailReplyButton = ''; // Initialize before conditions
                $editButton = '';
                $viewButton = '<a href="' . $view_url . '" title="View Ticket" class="btn btn-info btn-sm mx-1">
                                <i class="fas fa-eye"></i> View
                            </a>';
            
                // If the ticket is not assigned, show the Assign button
                if (!$isAssigned) {
                    $assignButton = '<button title="Assign Ticket" class="btn btn-primary btn-sm mx-1 assign-ticket"
                            data-id="' . $support->id . '" data-url="' . $assign_url . '">
                            <i class="fas fa-user-tag"></i> Assign
                    </button>';
                }
            
                // If the logged-in admin is assigned to this ticket or is a super admin
                if ($isAssignedToMe || $type == "super") {
                    // Support preference UI was removed from the resort side, so
                    // new tickets have NULL preference. Show Chat + Email Reply
                    // unconditionally — they were previously hidden, which is
                    // why "Reply Ticket" went missing from the Actions column.
                    $unreadCount = (int) ($unreadByTicket[$support->id] ?? 0);
                    // Always render the badge with a stable selector
                    // (hidden when 0) so the polling JS can flip it live.
                    $hiddenCls   = $unreadCount > 0 ? '' : ' d-none';
                    $unreadBadge = ' <span class="badge badge-danger js-chat-unread-badge' . $hiddenCls . '" data-ticket-id="' . $support->id . '" style="background:#dc3545;color:#fff;border-radius:10px;padding:2px 7px;font-size:11px;margin-left:4px;">' . ($unreadCount > 99 ? '99+' : $unreadCount) . '</span>';
                    $chatButton = '<a href="' . $chat_url . '" title="Open Chat" class="btn btn-secondary btn-sm mx-1 position-relative">
                                        <i class="fas fa-comments"></i> Chat' . $unreadBadge . '
                                    </a>';

                    if (!empty($support->createdBy->email)) {
                        $unreadEmailCount = (int) ($unreadEmailByTicket[$support->id] ?? 0);
                        // Same idea as the Chat badge — always rendered,
                        // toggled via .d-none, picked up by the live poller.
                        $emailHiddenCls   = $unreadEmailCount > 0 ? '' : ' d-none';
                        $unreadEmailBadge = ' <span class="badge badge-danger js-email-unread-badge' . $emailHiddenCls . '" data-ticket-id="' . $support->id . '" style="background:#dc3545;color:#fff;border-radius:10px;padding:2px 7px;font-size:11px;margin-left:4px;">' . ($unreadEmailCount > 99 ? '99+' : $unreadEmailCount) . '</span>';
                        $emailReplyButton = '<button title="Reply via Email" class="btn btn-warning btn-sm mx-1 reply-email"
                            data-toggle="modal" data-target="#replyModal"
                            data-subject="' . htmlspecialchars($support->subject, ENT_QUOTES) . '"
                            data-reply-to="' . htmlspecialchars($support->createdBy->email, ENT_QUOTES) . '"
                            data-id="' . $support->id . '">
                            <i class="fas fa-envelope"></i> Email Reply' . $unreadEmailBadge . '
                        </button>';
                    }

                    $editButton = '<button title="Change Status" class="btn btn-success btn-sm mx-1 change-status"
                                data-id="' . $support->id . '" data-status="' . $support->status . '" >
                                <i class="fas fa-edit"></i> Change Status
                        </button>';
                }
            
                return $assignButton . $chatButton . $emailReplyButton . $editButton . $viewButton;
            })
            
            ->rawColumns(['checkbox', 'employee_name', 'status', 'action'])
            ->make(true);
    }
    
    public function isWithinBusinessHours($resort, $currentDay, $currentTime)
    {
        // Filter business hours for the current day
        $todaysHours = $resort->businessHours->firstWhere('day_of_week', $currentDay);
    
        if ($todaysHours) {
            return $currentTime >= $todaysHours->start_time && $currentTime <= $todaysHours->end_time;
        }
    
        return false;
    }

    public function assignTicket(Request $request, $id)
    {
        $request->validate([
            'assigned_to' => 'required|exists:admins,id'
        ]);

        $support = Support::findOrFail(base64_decode($id));
        $support->assigned_to = $request->assigned_to;
        $support->status = 'In Progress';
        $support->save();

        return response()->json(['success' => true, 'message' => 'Ticket assigned successfully!']);
    }

    public function updateStatus(Request $request)
    {

        $request->validate([
            'ticket_id' => 'required|integer|exists:support,id',
            'status' => 'required|in:New,In Progress,Close'
        ]);

        
        $ticket = Support::findOrFail($request->ticket_id);

        $ticket->status = $request->status;
        $ticket->save();

        return response()->json(['success' => true, 'message' => 'Status updated successfully!']);
    }

    /**
     * JSON list of email replies for a ticket. Powers the support-detail
     * page's polling loop — frontend calls this every few seconds to pull
     * any new admin/employee replies that arrived since the last poll.
     * Returns ids + raw timestamps so the client can dedupe.
     */
    public function fetchMessagesJson($id)
    {
        $ticketId = (int) base64_decode($id, true);
        if (!$ticketId) return response()->json(['success' => false], 400);

        $rows = SupportMessages::where('ticket_id', $ticketId)
            ->orderBy('id')
            ->get(['id', 'ticket_id', 'sender', 'sender_id', 'message', 'attachments', 'created_at']);

        $messages = $rows->map(function ($r) {
            // getRawOriginal because the model may have a formatting accessor.
            $raw = method_exists($r, 'getRawOriginal') ? $r->getRawOriginal('created_at') : $r->getOriginal('created_at');
            $when = $raw ? \Carbon\Carbon::parse($raw) : null;
            return [
                'id'           => $r->id,
                'sender'       => $r->sender,
                'message'      => $r->message,
                'attachments'  => $r->attachments ? json_decode($r->attachments, true) : [],
                'created_at'   => $when ? $when->toIso8601String() : null,
                'time_label'   => $when ? ($when->isToday() ? $when->format('h:i A') : $when->format('d M Y, h:i A')) : '',
            ];
        });

        // Also mark inbound (employee → admin) as read on every fetch so
        // the unread badge clears as the admin keeps the page open.
        SupportMessages::where('ticket_id', $ticketId)
            ->where('sender', 'employee')
            ->where('is_read', 0)
            ->update(['is_read' => 1]);

        return response()->json(['success' => true, 'messages' => $messages]);
    }

    /**
     * Aggregate unread counts for the admin Supports module — drives the
     * sidebar nav badge AND each ticket's Chat / Email Reply badges live.
     * The frontend polls this every ~10 seconds; cheap because both
     * underlying tables are indexed on (ticket/support_id, is_read).
     */
    public function counts()
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) return response()->json(['nav_total' => 0, 'by_ticket' => (object) []]);

        $isSuper = ($admin->type ?? null) === 'super';
        $adminId = $admin->id;

        $ticketIds = Support::when(!$isSuper, fn($q) => $q->where('assigned_to', $adminId))
            ->pluck('id');

        $chatByTicket = $ticketIds->isEmpty()
            ? collect()
            : SupportChatMessage::whereIn('support_id', $ticketIds)
                ->where('receiver_id', $adminId)
                ->where('receiver_type', 'admin')
                ->where('is_read', 0)
                ->select('support_id', DB::raw('COUNT(*) as cnt'))
                ->groupBy('support_id')
                ->pluck('cnt', 'support_id');

        $emailByTicket = $ticketIds->isEmpty()
            ? collect()
            : SupportMessages::whereIn('ticket_id', $ticketIds)
                ->where('sender', 'employee')
                ->where('is_read', 0)
                ->select('ticket_id', DB::raw('COUNT(*) as cnt'))
                ->groupBy('ticket_id')
                ->pluck('cnt', 'ticket_id');

        $byTicket = [];
        foreach ($ticketIds as $tid) {
            $byTicket[(string) $tid] = [
                'chat'  => (int) ($chatByTicket[$tid] ?? 0),
                'email' => (int) ($emailByTicket[$tid] ?? 0),
            ];
        }

        $navTotal = (int) $chatByTicket->sum() + (int) $emailByTicket->sum();

        return response()->json([
            'nav_total' => $navTotal,
            'by_ticket' => $byTicket,
        ]);
    }

    public function view($id)
    {
        $ticketId = base64_decode($id);
        $support = Support::with(['support_category', 'createdBy','assignedAdmin'])->findOrFail($ticketId);
        // Admin opened the ticket → mark every employee-sent reply as read
        // so the unread badge on the action button (and sidebar nav) clears.
        SupportMessages::where('ticket_id', $ticketId)
            ->where('sender', 'employee')
            ->where('is_read', 0)
            ->update(['is_read' => 1]);
        $supportEmails = SupportMessages::where('ticket_id', $ticketId)->get();

        return view('admin.support.view', compact('support','supportEmails'));
    }

    public function replyStore(Request $request)
    {
        $request->validate([
            'ticket_id' => 'required|exists:support,id',
            'body' => 'required|string',
            'subject' => 'nullable|string|max:255',
            'attachment.*' => 'nullable|file|mimes:jpg,jpeg,png,heic,heif,pdf,doc,docx|max:32768', // Allow multiple files
        ]);

        $ticket = Support::with(['createdBy.GetEmployee','assignedAdmin','support_category'])->findOrFail($request->ticket_id);
        // dd($ticket);

        $resort = Resort::findorFail($ticket->resort_id);
        // dd($resort);

        $employee = $ticket->createdBy->GetEmployee;
        
        if (!$employee) 
        {
            return response()->json(['success' => false, 'message' => 'Employee not found.'], 404);
        }
        $uploadedFiles = [];

        if ($request->hasFile('attachment')) {
            foreach ($request->file('attachment') as $file) {
                $status =   Common::AWSEmployeeFileUpload($ticket->resort_id, $file, $employee->Emp_id, null, true);

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
        
        // Store reply in database
        $message = SupportMessages::create([
            'ticket_id' => $ticket->id,
            'message' => e($request->body) ,// Escapes but keeps basic formatting
            'attachments' => count($uploadedFiles) > 0 ? json_encode($uploadedFiles) : null,
            'sender' => 'admin',
            'sender_id' => Auth::guard('admin')->user()->id,
            // Stays unread until the resort user opens the email-reply page.
            'is_read' => 0,
        ]);

        // Determine recipient (To field)
        $recipientEmail = $ticket->createdBy->email;
        $replyBy = Auth::guard('admin')->user()->first_name." ".Auth::guard('admin')->user()->last_name;
        Mail::to($recipientEmail)->send(new SupportReplyEmail(
            $ticket,                 // Ticket Information
            $resort,         // Resort Information
            e($request->body),          // Reply Message
            $replyBy         // Admin (Reply Sender)
        ));
        
        return response()->json([
            'success' => true,
            'message' => 'Reply sent successfully!',
            'data' => $message
        ]);
    }
}