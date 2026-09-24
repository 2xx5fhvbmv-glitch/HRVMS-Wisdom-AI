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
        }

        // One chat_message_read row per (message, recipient): conversation_id
        // is the MESSAGE id, user_id the recipient. Receipts for my own sent
        // messages come from those rows — sent (no delivered_at) -> delivered
        // -> read. A group message is 'read' only once every recipient read it.
        $mineIds = $messages->where('sender_id', $resort->id)->pluck('id');
        $receipts = $mineIds->isEmpty()
            ? collect()
            : ChatMessageRead::whereIn('conversation_id', $mineIds)
                ->get(['conversation_id', 'status', 'delivered_at'])
                ->groupBy('conversation_id');

        // 'attachment' stores the raw disk path AWSEmployeeFileUpload() returned
        // (e.g. "26/public/EmployeesChatAttachments/.../file.jpg") — not a URL
        // the app can load directly, same as every other tenant-uploaded file;
        // must go through StorageHelper (per house convention), never raw.
        return $messages->map(function ($message) use ($resort, $receipts) {
            if (!empty($message->attachment)) {
                $message->attachment = \App\Helpers\StorageHelper::temporaryUrl($message->attachment);
            }
            if ((int) $message->sender_id === (int) $resort->id) {
                $rows = $receipts->get($message->id, collect());
                $total = $rows->count();
                $read = $rows->where('status', 'Read')->count();
                $delivered = $rows->filter(fn ($r) => $r->status === 'Read' || $r->delivered_at)->count();
                $message->read_status = $total && $read === $total ? 'read'
                    : ($total && $delivered === $total ? 'delivered' : 'sent');
                if ($message->type === 'group') {
                    $message->recipient_count = $total;
                    $message->delivered_count = $delivered;
                    $message->read_count = $read;
                }
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

        // One receipt row per recipient. Individual: the other person.
        // Group: every member except the sender (this used to store the GROUP
        // id as user_id, so group unread counts/ticks could never work).
        $receiptUserIds = $request->type === 'group'
            ? array_values(array_diff($group->groupMembers()->pluck('user_id')->all(), [$resort->id]))
            : [(int) $request->type_id];
        $now = Carbon::now();
        ChatMessageRead::insert(array_map(fn ($uid) => [
            'conversation_id' => $conversation->id,
            'user_id' => $uid,
            'status' => 'Unread',
            'created_at' => $now,
            'updated_at' => $now,
        ], $receiptUserIds));

      

        // Broadcast (Pusher, ShouldBroadcastNow) and the FCM push fan-out
        // below are both synchronous outbound HTTP calls the caller never
        // needs to wait on — the response body doesn't depend on either.
        // sendMobileNotification() alone costs a full uncached Google OAuth
        // round trip PLUS one FCM POST per registered device token, every
        // single message; combined with Pusher this was adding several
        // seconds to every chat/send response. Deferred to run right after
        // the HTTP response is flushed to the client (still same request,
        // no queue worker dependency) instead of before it.
        dispatch(function () use ($resort, $conversation) {
            broadcast(new \App\Events\MessageSent($conversation))->toOthers();

            if ($conversation->type == 'group') {
                $group = GroupChat::where('id', $conversation->type_id)
                    ->where('resort_id', $resort->resort_id)
                    ->first();

                $recipientAdminIds = $group
                    ? array_diff($group->groupMembers()->pluck('user_id')->toArray(), [$conversation->sender_id])
                    : [];
            } else {
                $recipientAdminIds = [$conversation->type_id];
            }

            if (!empty($recipientAdminIds)) {
                // sender_id/type_id/chat_group_member.user_id are all
                // resort_admins.id (the api-guard "current user" here is a
                // ResortAdmin) — sendMobileNotification/notifyEmployees expect
                // employees.id everywhere (device-token lookup,
                // resort_notifications.user_id FK). Passing resort_admins ids
                // straight through silently sent the push/row to whichever
                // unrelated employee happened to share that numeric id, or to
                // nobody at all.
                $recipientEmpIds = \App\Models\Employee::whereIn('Admin_Parent_id', $recipientAdminIds)
                    ->where('resort_id', $resort->resort_id)
                    ->pluck('id')
                    ->toArray();

                if (!empty($recipientEmpIds)) {
                    Common::notifyEmployees(
                        $resort->resort_id,
                        $recipientEmpIds,
                        $resort->full_name,
                        $conversation->message ?: 'Sent an attachment',
                        'Chat',
                        $conversation->id
                    );
                }
            }
        })->afterResponse();

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

    /**
     * 1-1 typing indicator only — group typing is a client-side whisper on
     * the group's presence channel (no server round trip needed, see
     * wisdom-chat.blade.php), since group.{id} members can publish to it
     * directly. chat.{id} is each user's own private inbox channel, not a
     * shared per-conversation channel, so the sender can't join the
     * recipient's channel to whisper the same way — this endpoint stands in.
     */
    public function typing(Request $request)
    {
        if (!$this->resort) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $resort = $this->resort;

        if ($request->type !== 'individual') {
            return response()->json(['success' => true]);
        }

        $recipientInResort = ResortAdmin::where('id', $request->type_id)
            ->where('resort_id', $resort->resort_id)
            ->exists();
        if (!$recipientInResort) {
            return response()->json(['success' => false, 'message' => 'Recipient not found.'], 404);
        }

        broadcast(new \App\Events\UserTyping($request->type_id, $resort->id, $resort->full_name))->toOthers();

        return response()->json(['success' => true]);
    }

    /**
     * Legacy single-message read (kept for existing clients). Prefer
     * markThreadRead().
     */
    public function markAsRead(Request $request)
    {
        $resort = $this->resort;

        $rows = ChatMessageRead::where('conversation_id', $request->conversation_id)
            ->where('user_id', $resort->id)
            ->where('status', 'Unread')
            ->whereHas('conversation', fn ($q) => $q->where('resort_id', $resort->resort_id))
            ->with('conversation:id,type,type_id,sender_id')
            ->get();

        $this->stamp($resort, $rows, true);

        return response()->json(['success' => true, 'message' => 'Conversation marked as read']);
    }

    /**
     * Bulk read: everything unread addressed to me in one thread, one call.
     * Broadcasts MessageReceipt(read) to each affected sender.
     * POST { type: individual|group, type_id }
     */
    public function markThreadRead(Request $request)
    {
        $v = Validator::make($request->all(), [
            'type' => 'required|in:individual,group',
            'type_id' => 'required|integer',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 400);
        }
        $resort = $this->resort;
        if (!$resort) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $rows = $this->pendingReceiptRows($resort, $request->type, $request->type_id)
            ->where('status', 'Unread')
            ->get();
        $ids = $this->stamp($resort, $rows, true);

        return response()->json(['success' => true, 'count' => count($ids), 'message_ids' => $ids]);
    }

    /**
     * Delivery ack: the client received the message(s) (socket event, push,
     * or app open). type/type_id optional — omit both to ack everything
     * undelivered addressed to me (call on app open / socket reconnect).
     * POST { type?, type_id? }
     */
    public function markDelivered(Request $request)
    {
        $resort = $this->resort;
        if (!$resort) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $rows = $this->pendingReceiptRows($resort, $request->type, $request->type_id)
            ->where('status', 'Unread')
            ->whereNull('delivered_at')
            ->get();
        $ids = $this->stamp($resort, $rows, false);

        return response()->json(['success' => true, 'count' => count($ids), 'message_ids' => $ids]);
    }

    /**
     * Per-recipient receipt detail for one message I sent ("seen by" list,
     * mainly for groups). GET chat/message-status/{message_id}
     */
    public function messageStatus($messageId)
    {
        $resort = $this->resort;
        if (!$resort) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $message = Conversation::where('id', $messageId)
            ->where('resort_id', $resort->resort_id)
            ->where('sender_id', $resort->id)
            ->first();
        if (!$message) {
            return response()->json(['success' => false, 'message' => 'Message not found.'], 404);
        }

        $rows = ChatMessageRead::where('conversation_id', $message->id)->get();
        $admins = ResortAdmin::whereIn('id', $rows->pluck('user_id'))->get()->keyBy('id');

        return response()->json([
            'success' => true,
            'message_id' => $message->id,
            'recipients' => $rows->map(fn ($r) => [
                'id' => $r->user_id,
                'name' => isset($admins[$r->user_id]) ? $admins[$r->user_id]->first_name . ' ' . $admins[$r->user_id]->last_name : 'Unknown',
                'status' => $r->status === 'Read' ? 'read' : ($r->delivered_at ? 'delivered' : 'sent'),
                'delivered_at' => $r->delivered_at ?: ($r->status === 'Read' ? $r->read_at : null),
                'read_at' => $r->read_at,
            ])->values(),
        ]);
    }

    /**
     * My receipt rows (user_id = me), tenant-scoped, optionally narrowed to
     * one thread. For an individual thread the other party is the sender;
     * for a group the conversation's type_id is the group id.
     */
    private function pendingReceiptRows($resort, $type, $typeId)
    {
        return ChatMessageRead::where('user_id', $resort->id)
            ->whereHas('conversation', function ($q) use ($resort, $type, $typeId) {
                $q->where('resort_id', $resort->resort_id);
                if ($type === 'individual') {
                    $q->where('type', 'individual')->where('sender_id', $typeId);
                } elseif ($type === 'group') {
                    $q->where('type', 'group')->where('type_id', $typeId);
                }
            })
            ->with('conversation:id,type,type_id,sender_id');
    }

    /**
     * Apply delivered/read to the given rows and tell each sender live.
     * Returns the affected message ids. A read also counts as delivered.
     */
    private function stamp($resort, $rows, bool $read): array
    {
        if ($rows->isEmpty()) {
            return [];
        }
        $now = Carbon::now();

        $update = $read
            ? ['status' => 'Read', 'read_at' => $now, 'delivered_at' => \DB::raw("COALESCE(delivered_at, '" . $now->toDateTimeString() . "')")]
            : ['delivered_at' => $now];
        ChatMessageRead::whereIn('id', $rows->pluck('id'))->update($update);

        // Receipt push must never fail the read/ack itself.
        try {
            foreach ($rows->groupBy(fn ($r) => $r->conversation->sender_id) as $senderId => $group) {
                $first = $group->first()->conversation;
                broadcast(new \App\Events\MessageReceipt(
                    $senderId,
                    $read ? 'read' : 'delivered',
                    $first->type,
                    $first->type === 'group' ? $first->type_id : $resort->id,
                    $resort->id,
                    $group->pluck('conversation_id')->map(fn ($i) => (int) $i)->all(),
                    $now
                ))->toOthers();
            }
        } catch (\Throwable $e) {
            \Log::warning('Chat receipt broadcast failed: ' . $e->getMessage());
        }

        return $rows->pluck('conversation_id')->map(fn ($i) => (int) $i)->all();
    }
}
