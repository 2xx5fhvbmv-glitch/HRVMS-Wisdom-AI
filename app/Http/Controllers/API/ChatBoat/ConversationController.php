<?php

namespace App\Http\Controllers\API\ChatBoat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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
        // Same dual-guard resolution as ChatController — mobile (api/
        // Passport) and web (resort-admin session) both authenticate
        // against the resort_admins table, so this is one chat identity
        // reached from two platforms (Chat Module spec §1/§8).
        $this->resort = Auth::guard('api')->user() ?: Auth::guard('resort-admin')->user();
    }

    // In this controller, we are using the ResortAdmin table's ID, not the Employee table's ID,
    // for sender_id and type_id (receiver_id) in the Conversation table.
    // Therefore, we are using the ResortAdmin table's ID to retrieve the employee details.
    public function chatView(Request $request, $type, $type_id)
    {
        $resort = $this->resort;
        $receiver_id = $type_id;

        // Tenant isolation — same rule as sendMessage(): never resolve a
        // chat partner/group outside the caller's own resort, and a group
        // conversation is only viewable by its actual members.
        if ($type == 'individual') {
            $resortAdmin = ResortAdmin::where('id', $receiver_id)
                ->where('resort_id', $resort->resort_id)
                ->with('GetEmployee')->first();
            if (!$resortAdmin) {
                return response()->json(['success' => false, 'message' => 'Recipient not found.'], 404);
            }
            $data = [
                'id' => $resortAdmin->id,
                'name' => $resortAdmin->first_name . ' ' . $resortAdmin->last_name,
                'profile' => Common::getResortUserPicture($resortAdmin->id),
            ];
        } elseif ($type == 'group') {
            $group = GroupChat::where('id', $receiver_id)->where('resort_id', $resort->resort_id)->first();
            if (!$group) {
                return response()->json(['success' => false, 'message' => 'Group not found.'], 404);
            }
            // Actual members can always view; the HR administrative override
            // ("manage groups created by the HR department") is meaningless
            // if HR can add/remove/rename a group without ever seeing what's
            // in it, so it grants viewing too.
            $isMember = $group->groupMembers()->where('user_id', $resort->id)->exists();
            if (!$isMember && !Common::isChatGroupAdmin($group, $resort)) {
                return response()->json(['success' => false, 'message' => 'You are not a member of this group.'], 403);
            }

            $members = $group->groupMembers()->get(['user_id', 'role'])->map(function ($member) {
                $admin = ResortAdmin::find($member->user_id);
                return [
                    'id' => $member->user_id,
                    'name' => $admin ? $admin->first_name . ' ' . $admin->last_name : 'Unknown',
                    'profile' => Common::getResortUserPicture($member->user_id),
                    'role' => $member->role,
                ];
            })->values();

            $data = [
                'id' => $group->id,
                'name' => $group->name,
                'profile' => $group->profile_picture ? \App\Helpers\StorageHelper::temporaryUrl($group->profile_picture) : asset('resorts_assets/images/group-chat.png'),
                'members' => $members,
                'is_admin' => Common::isChatGroupAdmin($group, $resort),
            ];
        } else {
            return response()->json(['success' => false, 'message' => 'Invalid chat type.'], 400);
        }

        $chats = $this->messageThread($resort, $type, $receiver_id);

        return response()->json([
            'success' => true,
            'message' => 'Chat view loaded successfully',
            'data' => $data,
            'receiver_id' => $receiver_id,
            'type' => $type,
            'messages' => $chats,
        ]);
    }

    /**
     * Full ordered message thread for a chat, tenant-scoped, with
     * attachment paths resolved to real URLs.
     *
     * Individual chats are directional (type_id/sender_id form a pair, so
     * "my sent" and "their sent" are two different rows) and need the
     * sender/receiver union below. Group chats are NOT directional — every
     * message in the group already has type_id = the group id regardless
     * of who sent it, so a second "type_id = me" query (as this used to
     * run for both types) matches nothing and silently hid every message
     * from every other group member.
     */
    private function messageThread($resort, $type, $otherPartyId)
    {
        $readAt = null;
        if ($type === 'group') {
            $messages = Conversation::where('resort_id', $resort->resort_id)
                ->where('type', 'group')
                ->where('type_id', $otherPartyId)
                ->orderBy('created_at', 'asc')
                ->get(['id','type', 'type_id', 'sender_id', 'message','attachment', 'created_at']);
        } else {
            $sent = Conversation::where('resort_id', $resort->resort_id)
                ->where('type', 'individual')
                ->where('type_id', $otherPartyId)
                ->where('sender_id', $resort->id)
                ->get(['id','type', 'type_id', 'sender_id', 'message','attachment', 'created_at']);

            $received = Conversation::where('resort_id', $resort->resort_id)
                ->where('type', 'individual')
                ->where('type_id', $resort->id)
                ->where('sender_id', $otherPartyId)
                ->get(['id','type', 'type_id', 'sender_id', 'message','attachment', 'created_at']);

            $messages = $sent->merge($received)->sortBy('created_at')->values();

            // markAsRead() is thread-level, not per-message — it flips one
            // chat_message_read row (conversation_id = the thread partner's
            // id) to Read with a read_at timestamp. That was never surfaced
            // back in the message list at all, so a sender's message stayed
            // "sent" forever even after the recipient opened and read it —
            // this is what actually determines the tick shown. Any message
            // I (the caller) sent with created_at <= the other party's
            // read_at (on THEIR record of reading MY thread, i.e.
            // conversation_id = my own id) counts as read by them.
            $theirReadRecord = ChatMessageRead::where('conversation_id', $resort->id)
                ->where('user_id', $otherPartyId)
                ->where('status', 'Read')
                ->first();
            $readAt = $theirReadRecord ? $theirReadRecord->read_at : null;
        }

        // 'attachment' stores the raw disk path AWSEmployeeFileUpload() returned
        // (e.g. "26/public/EmployeesChatAttachments/.../file.jpg") — not a URL
        // the app can load directly, same as every other tenant-uploaded file;
        // must go through StorageHelper (per house convention), never raw.
        return $messages->map(function ($message) use ($resort, $readAt) {
            if (!empty($message->attachment)) {
                $message->attachment = \App\Helpers\StorageHelper::temporaryUrl($message->attachment);
            }
            if ((int) $message->sender_id === (int) $resort->id) {
                $message->read_status = ($readAt && $message->created_at <= $readAt) ? 'read' : 'sent';
            }
            return $message;
        })->values()->all();
    }

    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:individual,group',
            'type_id' => 'required|integer',
            // An attachment-only message (a photo with no caption) has no
            // 'message' at all — this used to hard-require it, so every
            // caption-less image send failed validation before the upload
            // ever ran.
            'message' => 'nullable|string|required_without:attachment',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        if (!$this->resort) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $resort = $this->resort;

        // Tenant isolation — type_id is a bare id with no other check, so
        // without this an employee could message (or post into a group
        // belonging to) a completely different resort just by knowing/
        // guessing another resort's id. Group messages additionally require
        // the sender to actually be a member of that group.
        if ($request->type === 'individual') {
            $recipientInResort = ResortAdmin::where('id', $request->type_id)
                ->where('resort_id', $resort->resort_id)
                ->exists();
            if (!$recipientInResort || (int) $request->type_id === (int) $resort->id) {
                return response()->json(['success' => false, 'message' => 'Recipient not found.'], 404);
            }
        } else {
            $group = GroupChat::where('id', $request->type_id)
                ->where('resort_id', $resort->resort_id)
                ->first();
            if (!$group) {
                return response()->json(['success' => false, 'message' => 'Group not found.'], 404);
            }
            $isMember = $group->groupMembers()->where('user_id', $resort->id)->exists();
            if (!$isMember && !Common::isChatGroupAdmin($group, $resort)) {
                return response()->json(['success' => false, 'message' => 'You are not a member of this group.'], 403);
            }
        }

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

      

        broadcast(new \App\Events\MessageSent($conversation))->toOthers();

        if ($conversation->type == 'group') {
            $group = GroupChat::where('id', $conversation->type_id)
                ->where('resort_id', $resort->resort_id)
                ->first();

            $recipientIds = $group
                ? array_diff($group->groupMembers()->pluck('user_id')->toArray(), [$conversation->sender_id])
                : [];
        } else {
            $recipientIds = [$conversation->type_id];
        }

        if (!empty($recipientIds)) {
            Common::sendMobileNotification(
                $resort->resort_id, 2, null, null,
                $resort->full_name, $conversation->message ?: 'Sent an attachment',
                'Chat', $recipientIds
            );
        }

        $chat_history = $this->messageThread($resort, $request->type, $request->type_id);

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully',
            'data' => [
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
