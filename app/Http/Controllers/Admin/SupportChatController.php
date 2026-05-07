<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;

use App\Events\NewChatMessage;

use App\Models\Admin;
use App\Models\Support;
use App\Models\SupportChatMessage;
use App\Helpers\Common;
use File;
use DB;

class SupportChatController extends Controller
{
    public function index($support_id)
    {
        $decodedId = base64_decode($support_id);
        $support = Support::with(['support_category','createdBy.GetEmployee','assignedAdmin'])->where('id',$decodedId)->first();

        // Mark all admin-bound messages on this ticket as read for the
        // current admin so the unread badge on the support list clears.
        $loginAdminId = Auth::guard('admin')->id();
        if ($loginAdminId) {
            SupportChatMessage::where('support_id', $decodedId)
                ->where('receiver_id', $loginAdminId)
                ->where('receiver_type', 'admin')
                ->where('is_read', 0)
                ->update(['is_read' => 1]);
        }

        $messages = SupportChatMessage::where('support_id', $decodedId)->orderBy('created_at', 'asc')->get();
        return view('admin.support.chat',compact('messages','support'));
    }

    public function fetchMessages($support_id)
    {
        $support = Support::with(['support_category','createdBy.GetEmployee','assignedAdmin'])->where('id',base64_decode($support_id))->first();

        $messages = SupportChatMessage::where('support_id', base64_decode($support_id))->orderBy('created_at', 'asc')->get();
        
        return response()->json($messages,$support);
    }

    public function sendMessage(Request $request)
    {
        $support = Support::with(['support_category','createdBy.GetEmployee','assignedAdmin'])->where('id',$request->support_id)->first();

         $employee = $support->createdBy->getEmployee;
        
        if (!$employee) 
        {
            return response()->json(['success' => false, 'message' => 'Employee not found.'], 404);
        }

        $validatedData = $request->validate([
            'support_id' => 'required|exists:support,id',
            'senderId' => 'required',
            'senderType' => 'required|string',
            'receiverId' => 'nullable',
            'receiverType' => 'nullable|string',
            'receiver_name' => 'nullable|string',
            'receiver_image' => 'nullable|string',
            'senderName' => 'required|string',
            'senderImage' => 'nullable|string',
            'message' => 'nullable|string',
            'attachments.*' => 'nullable|file|max:51200'
        ]);

        // Resolve the actual receiver from the support ticket. We don't trust
        // the form's receiverId — the customer's GetEmployee->id rendered in
        // the page may be stale (employee renamed/deleted, ticket reassigned).
        // Use the live DB value so the broadcast always lands on the right
        // channel (chat.{employee_id}).
        $resolvedReceiverId = optional($employee)->id;
        if (!$resolvedReceiverId) {
            \Illuminate\Support\Facades\Log::warning('Support chat: no employee for support', [
                'support_id'  => $validatedData['support_id'],
                'createdBy'   => optional($support->createdBy)->id,
            ]);
        }
        $validatedData['receiverId']     = $resolvedReceiverId ?: 0;
        $validatedData['receiverType']   = $validatedData['receiverType'] ?: 'employee';
        $validatedData['receiver_name']  = $validatedData['receiver_name']
            ?: trim(optional($support->createdBy)->first_name . ' ' . optional($support->createdBy)->last_name)
            ?: 'Customer';
        $validatedData['receiver_image'] = $validatedData['receiver_image'] ?: '';
        $uploadedFiles = []; 

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $status =   Common::AWSEmployeeFileUpload($support->resort_id, $file, $employee->Emp_id, null, true);

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
        // Save message to database
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
            // Save on the same instance so $message->attachment is set in
            // the JSON response; the sender-side appendMessage() reads
            // response.message.attachments to render the bubble.
            $message->attachment = json_encode($uploadedFiles);
            $message->save();
        }

        // Send WebSocket event
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