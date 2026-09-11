<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EmployeeChat;
use App\Models\Employee;
use App\Models\ResortAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Common;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Validator;
use DB;


class EmployeeChatController extends Controller
{
    protected $user;
    protected $resort_id;

    public function __construct()
    {
        if (Auth::guard('api')->check()) {
            $this->user                                     =   Auth::guard('api')->user();
            $this->resort_id                                =   $this->user->resort_id;
        }
    }

    public function sendMessage(Request $request){

        // retusrn response()->json(['success' => true, 'message' => 'Stopped']);
        if (!Auth::guard('api')->check()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

         $validator = Validator::make($request->all(), [
            // exists:employees,id proves the row exists somewhere, never
            // that it belongs to this resort — a user on Resort A could
            // read/inject into a conversation between two Resort B
            // employees just by guessing their numeric ids.
            'receiver_id' => ['required', Rule::exists('employees', 'id')->where('resort_id', $this->resort_id)],
            'message' => 'nullable|string'
        ]);
            
         if ($validator->fails()) {
               return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        try{
            DB::beginTransaction();
            $user                   = Auth::guard('api')->user();
            $employee               = $user->GetEmployee;
            $sender_id              = $employee->id;
            $message                = $request->input('message'); 
            $receiver_id            = $request->receiver_id; 
            $restrictedRanks        = [1, 2, 4, 8];


            $sender                 = Employee::where('id', $sender_id)->where('resort_id', $this->resort_id)->first();
            $receiver               = Employee::where('id', $receiver_id)->where('resort_id', $this->resort_id)->first();
            $senderProfile          = ResortAdmin::where('id', $sender->Admin_Parent_id)->first();
            $receiverProfile        = ResortAdmin::where('id', $receiver->Admin_Parent_id)->first();
        
            if (in_array($sender->rank, $restrictedRanks) || in_array($receiver->rank, $restrictedRanks)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Conversation between these ranks is not allowed.'
                        ], 200);
                    }

            $conversationId         = min($sender_id, $receiver_id) . '_' . max($sender_id, $receiver_id);

            $chatHistory            =EmployeeChat::where('conversation_id', $conversationId)
                                                    ->where('resort_id', $this->resort_id)
                                                    ->select(['id','sender_id','message','created_at'])
                                                    ->orderBy('created_at', 'desc')
                                                    ->get()
                                                    ->map(function ($chat) use ($sender,$receiver,$senderProfile,$receiverProfile) {      
                                                            $isSender = $chat->sender_id === $sender->id;
         
                                                        return [
                                                            'message_id'    => $chat->id,
                                                            'message'       => $chat->message,
                                                            'created_at'    => $chat->created_at,
                                                            'user'          => [
                                                                'id'                => $isSender ? $sender->id : $receiver->id,
                                                                'name'              => $isSender ? $senderProfile->first_name : $receiverProfile->first_name,
                                                                'profile_picture'   =>  $isSender 
                                                                ? Common::getResortUserPicture($senderProfile->id) 
                                                                : Common::getResortUserPicture($receiverProfile->id)
                                                            ],
                                                        ];
                                                });
                if($message){
                    $Newmessage             = EmployeeChat::create([
                    'sender_id'             => $sender_id,
                    'resort_id'             => $this->resort_id,
                    'receiver_id'           => $request->receiver_id,
                    'conversation_id'       => $conversationId,
                    'message'               => $message,
                    ]);

                    DB::commit();

                    // Relay + push were previously sent synchronously here, same as
                    // ConversationController::sendMessage used to do — the FCM send
                    // costs a full uncached Google OAuth round trip plus one POST per
                    // device token, which delayed the response by several seconds.
                    // A slow response invites the mobile client to time out and retry
                    // the whole send-message request, re-creating the message row
                    // above and re-firing the notification (the duplicate "i am
                    // coming" pushes stacking on the lock screen). Deferred to run
                    // after the response is flushed, matching the fix already applied
                    // to ConversationController::sendMessage.
                    dispatch(function () use ($sender_id, $receiver_id, $conversationId, $message, $senderProfile, $Newmessage) {
                        $base_url = env('BASE_URL', 'http://localhost:2053');
                        // BASE_URL is commented out/unset in every env file, so this
                        // resolves to the localhost fallback — nothing listens there
                        // in prod. This relay is legacy/best-effort; it must never be
                        // able to block message persistence or the notification below.
                        try {
                            Http::post($base_url . '/sendChatMessage', [
                                'sender_id'         => $sender_id,
                                'receiver_id'       => $receiver_id,
                                'conversation_id'   => $conversationId,
                                'message'           => $message ?? null,
                                'timestamp'         => now(),
                            ]);
                        } catch (\Exception $e) {
                            \Log::warning('EmployeeChatController: legacy sendChatMessage relay failed: ' . $e->getMessage());
                        }

                        // receiver_id here is already employees.id (validated via
                        // Rule::exists('employees', 'id') above), so no id-domain
                        // translation is needed.
                        Common::notifyEmployees(
                            $this->resort_id,
                            [$receiver_id],
                            trim(($senderProfile->first_name ?? '') . ' ' . ($senderProfile->last_name ?? '')),
                            $message,
                            'Chat',
                            $Newmessage->id,
                            'employee-chat-message'
                        );
                    })->afterResponse();
                    return response()->json(['message' => 'Message sent successfully', 'message'=>['message_id'=>$Newmessage->id,'profile_picture'=>Common::getResortUserPicture($senderProfile->id),'message'=>$message],'chat history' => $chatHistory]);
                }else{
                    return response()->json(['chat history' => $chatHistory]);
                }
        }catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    
}
