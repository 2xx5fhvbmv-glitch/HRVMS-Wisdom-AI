<?php

namespace App\Http\Controllers\Resorts\Support;

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
    public $resort;
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
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
            'attachments.*' => 'nullable|file|max:51200' // 50MB max size
        ]);

        $uploadedFiles = []; 

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
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

        // Broadcast event with all required fields
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