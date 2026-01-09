<?php

namespace App\Http\Controllers\API\ChatBoat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Auth;
use App\Helpers\Common;
use App\Models\Conversation;
use App\Models\GroupChat;
use App\Models\ResortAdmin;
use App\Models\ChatMessageRead;
use Carbon\Carbon;

class ConversationController extends Controller
{
    protected $resort;

    public function __construct()
    {
        $this->resort = Auth::guard('api')->user();
    }

    // In this controller, we are using the ResortAdmin table's ID, not the Employee table's ID,
    // for sender_id and type_id (receiver_id) in the Conversation table.
    // Therefore, we are using the ResortAdmin table's ID to retrieve the employee details.
    public function chatView(Request $request, $type, $type_id)
    {
        $resort = $this->resort;
        $receiver_id = $type_id;

        if ($type == 'individual') {
            $resortAdmin = ResortAdmin::where('id', $receiver_id)->with('GetEmployee')->first();
            $data = [
                'id' => $resortAdmin->id,
                'name' => $resortAdmin->first_name . ' ' . $resortAdmin->last_name,
                'profile' => Common::getResortUserPicture($resortAdmin->id),
            ];
        } elseif ($type == 'group') {
            $group = GroupChat::where('id', $receiver_id)->where('resort_id', $resort->resort_id)->first();
            $data = [
                'id' => $group->id,
                'name' => $group->name,
                'profile' => asset('resorts_assets/images/group-chat.png'),
            ];
        }

        $send_message = Conversation::where('type', $type)
            ->where('type_id', $receiver_id)
            ->where('sender_id', $resort->id)
            ->orderBy('created_at', 'asc')
            ->get(['id','type', 'type_id', 'sender_id', 'message','attachment', 'created_at']);

        $get_message = Conversation::where('type', $type)
            ->where('type_id', $resort->id)
            ->where('sender_id', $receiver_id)
            ->orderBy('created_at', 'asc')
            
            ->get(['id','type', 'type_id', 'sender_id', 'message','attachment', 'created_at']);

        $chats = $send_message->merge($get_message)->sortBy('created_at')->values()->all();

        return response()->json([
            'success' => true,
            'message' => 'Chat view loaded successfully',
            'data' => $data,
            'receiver_id' => $receiver_id,
            'type' => $type,
            'messages' => $chats,
        ]);
    }

    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:individual,group',
            'type_id' => 'required|integer',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $resort = $this->resort;

          // Handle attachment BEFORE broadcasting for correct data

          $filename= '';
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');

            $SubFolder="EmployeesChatAttachments";
            $status =   Common::AWSEmployeeFileUpload($resort->resort_id,$file, $resort->GetEmployee->Emp_id,$SubFolder,false);

            if($status['status'] == true && isset($status['Chil_file_id']) && !empty($status['Chil_file_id']))
            {
                $filename = $file->getClientOriginalName();
                $imagePaths[] = ['Filename' => $filename, 'Child_id' => $status['Chil_file_id']];
            }
                    
        }
       

        $conversation = Conversation::create([
            'resort_id' => $resort->resort_id,
            'type' => $request->type,
            'type_id' => $request->type_id,
            'sender_id' => $resort->id,
            'message' => $request->message,
            'created_by' => $resort->id,
            'modified_by' => $resort->id,
            'attachment' => isset($status) ? $status['path'] : null,
        ]);

        ChatMessageRead::create([
            'conversation_id' => $conversation->id,
            'user_id' => $conversation->type_id,
            'status' => 'Unread',
        ]);

      

        $base_url = env('BASE_URL', 'https://app.thewisdom.ai:2053');

        if ($conversation->type == 'group') {
            $group = GroupChat::where('id', $conversation->type_id)
                ->where('resort_id', $resort->resort_id)
                ->first();

            if ($group) {
                $groupMembers = $group->groupMembers()->pluck('user_id')->toArray();

                foreach ($groupMembers as $memberId) {
                    if ($memberId == $conversation->sender_id) {
                        continue;
                    }

                    Http::post($base_url . '/sendChatMessage', [
                        'sender_id' => $conversation->sender_id,
                        'receiver_id' => $memberId,
                        'message' => $conversation->message ?? null,
                        'attachment' => $conversation->attachment ?? null,
                        'timestamp' => now(),
                    ]);
                }
            }
        } else {
            Http::post($base_url . '/sendChatMessage', [
                'sender_id' => $conversation->sender_id,
                'receiver_id' => $conversation->type_id,
                'message' => $conversation->message ?? null,
                'attachment' => $conversation->attachment ?? null,
                'timestamp' => now(),
            ]);
        }

        // Prepare chat history to return
        $send_message = Conversation::where('type', $request->type)
            ->where('type_id', $request->type_id)
            ->where('sender_id', $resort->id)
            ->orderBy('created_at', 'asc')
            ->get(['id','type', 'type_id', 'sender_id', 'message', 'attachment', 'created_at']);

        $get_message = Conversation::where('type', $request->type)
            ->where('type_id', $resort->id)
            ->where('sender_id', $request->type_id)
            ->orderBy('created_at', 'asc')
            ->get(['id','type', 'type_id', 'sender_id', 'message', 'attachment','created_at']);

        $chat_history = $send_message->merge($get_message)->sortBy('created_at')->values()->all();

        return response()->json([
            'message' => 'Message sent successfully',
            'message' => [
                'message_id' => $conversation->id,
                'message' => $conversation->message
            ],
            'chat_history' => $chat_history,
        ]);
    }

    public function markAsRead(Request $request)
    {
        $resort = $this->resort;
        $conversationId = $request->conversation_id;

        $chatMessageRead = ChatMessageRead::where('conversation_id', $conversationId)
            ->where('user_id', $resort->id)
            ->first();

        if ($chatMessageRead) {
            $chatMessageRead->status = 'Read';
            $chatMessageRead->read_at = Carbon::now();
            $chatMessageRead->save();
        }

        return response()->json(['success' => true, 'message' => 'Conversation marked as read']);
    }
}
