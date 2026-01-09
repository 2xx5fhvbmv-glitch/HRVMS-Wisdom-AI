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
        // return view('admin.manufecturers.index');
        $support = Support::with(['support_category','createdBy.GetEmployee','assignedAdmin'])->where('id',base64_decode($support_id))->first();

        // dd($support);
        $messages = SupportChatMessage::where('support_id', base64_decode($support_id))->orderBy('created_at', 'asc')->get();
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
            'receiverId' => 'required',
            'receiverType' => 'required|string',
            'receiver_name' => 'required|string',
            'receiver_image' => 'nullable|string',
            'senderName' => 'required|string',
            'senderImage' => 'nullable|string',
            'message' => 'nullable|string',
            'attachments.*' => 'nullable|file|max:51200' 
        ]);
        $uploadedFiles = []; 

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $status =   Common::AWSEmployeeFileUpload($support->resort_id, $file, $employee->Emp_id,true );

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
            $msg = SupportChatMessage::findOrFail($message->id);
            $msg->attachment = json_encode($uploadedFiles);
            $msg->save();
        }

        // Send WebSocket event
        broadcast(new NewChatMessage(
            $validatedData['message'], 
            $validatedData['senderId'], 
            $validatedData['receiverId'], 
            $validatedData['senderName'], 
            $validatedData['senderImage'], 
            $validatedData['receiver_name'], 
            $validatedData['receiver_image']
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