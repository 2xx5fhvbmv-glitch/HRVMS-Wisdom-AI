<?php

namespace App\Http\Controllers\Resorts\Support;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use App\Events\NewChatMessage;
use App\Models\Admin;
use App\Models\Support;
use App\Models\SupportChatMessage;
use App\Helpers\Common;
use File;
use DB;

class SupportChatController extends Controller
{
    public $resort;
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;
    }
    public function index($support_id)
    {
        $page_title = 'Support Chat';
        // return view('admin.manufecturers.index');
        $supportId = base64_decode($support_id);
        $support = Support::with(['support_category','createdBy','assignedAdmin'])->where('id',base64_decode($support_id))->first();
        // dd($support->assignedAdmin->first_name);
        $messages = SupportChatMessage::where('support_id', base64_decode($support_id))->orderBy('created_at', 'asc')->get();
        return view('resorts.support.chat',compact('messages','support','supportId','page_title'));
    }

    public function fetchMessages($support_id)
    {
        $messages = SupportChatMessage::where('support_id', base64_decode($support_id))->orderBy('created_at', 'asc')->get();
        
        return response()->json($messages);
    }

    public function sendMessage(Request $request)
    {
        $employee = $this->resort->getEmployee;
        
        if (!$employee) 
        {
            return response()->json(['success' => false, 'message' => 'Employee not found.'], 404);
        }
        // Receiver fields are nullable: tickets that haven't been assigned to
        // an admin yet have no concrete recipient. We still record the
        // employee's outbound message and notify the support pool generically.
        $validatedData = $request->validate([
            'support_id'     => 'required|exists:support,id',
            'senderId'       => 'required',
            'senderType'     => 'required|string',
            'receiverId'     => 'nullable',
            'receiverType'   => 'nullable|string',
            'receiver_name'  => 'nullable|string',
            'receiver_image' => 'nullable|string',
            'senderName'     => 'required|string',
            'senderImage'    => 'nullable|string',
            'message'        => 'nullable|string',
            'attachments.*'  => 'nullable|file|max:51200' // 50MB max size
        ]);

        // Resolve the actual receiver from the support ticket. We don't trust
        // the receiverId the form posted — that field is rendered from
        // $support->assigned_to, which can be stale (admin deleted, ticket
        // reassigned in another tab) and silently routes the broadcast to
        // a non-existent channel. Look it up from the DB instead.
        $support       = Support::find($validatedData['support_id']);
        $assignedAdmin = $support ? $support->assignedAdmin : null;
        if (!$assignedAdmin) {
            // Ticket has no live assigned admin (assigned_to is stale or
            // null). Fall back to the first admin so the message still
            // gets through; log a warning so the bad assignment can be
            // cleaned up.
            $assignedAdmin = Admin::orderBy('id')->first();
            Log::warning('Support chat: stale or missing assigned_to', [
                'support_id'     => $validatedData['support_id'],
                'assigned_to_db' => $support->assigned_to ?? null,
                'fallback_to'    => optional($assignedAdmin)->id,
            ]);
        }
        $resolvedName = $assignedAdmin
            ? trim($assignedAdmin->first_name . ' ' . $assignedAdmin->last_name)
            : '';
        $validatedData['receiverId']     = optional($assignedAdmin)->id ?: 0;
        $validatedData['receiverType']   = $validatedData['receiverType'] ?: 'admin';
        $validatedData['receiver_name']  = $resolvedName !== '' ? $resolvedName : 'Support Team';
        $validatedData['receiver_image'] = $validatedData['receiver_image'] ?: '';

        $uploadedFiles = []; 

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $status =   Common::AWSEmployeeFileUpload($this->resort->resort_id, $file, $employee->Emp_id, null, true);

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

        $message = SupportChatMessage::create([
            'support_id' => $validatedData['support_id'],
            'sender_id' => $validatedData['senderId'],
            'sender_type' => $validatedData['senderType'],
            'receiver_id' => $validatedData['receiverId'],
            'receiver_type' => $validatedData['receiverType'],
            'message' => $validatedData['message'],
        ]);

        if(!empty($uploadedFiles))
        {
            // Persist on the same instance so $message->attachment is
            // populated for the JSON response below; otherwise the sender's
            // own UI would render the bubble with empty attachments and
            // require a page refresh to pick them up.
            $message->attachment = json_encode($uploadedFiles);
            $message->save();
        }

        // Broadcast event with all required fields
        broadcast(new NewChatMessage(
            $validatedData['message'],
            $validatedData['senderId'],
            $validatedData['receiverId'],
            $validatedData['senderName'],
            $validatedData['senderImage'],
            $validatedData['receiver_name'],
            $validatedData['receiver_image'],
            $uploadedFiles
        ))->toOthers();

        return response()->json([
            'success' => true,
            'message' => [
                'message' => $message->message,
                'sender_id' => $message->sender_id,
                'receiver_id' => $message->receiver_id,
                'sender_type' => $message->sender_type,
                'receiver_type' => $message->receiver_type,
                'attachments' => $message->attachment ? json_decode($message->attachment, true) : [] // Ensure it's an array
            ]
        ]);
    }

}