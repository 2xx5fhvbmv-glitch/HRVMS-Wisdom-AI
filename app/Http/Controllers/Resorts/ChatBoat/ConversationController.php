<?php

namespace App\Http\Controllers\Resorts\ChatBoat;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use Auth;
use Config;
use DB;
use PDF;
use App\Helpers\Common;
use App\Models\Compliance;
use App\Models\Employee;
use App\Models\ResortAdmin;
use App\Models\Conversation;
use App\Models\GroupChat;
use Carbon\Carbon;

class ConversationController extends Controller
{
     public $resort;

     public function __construct()
     {
          $this->resort = Auth::guard('resort-admin')->user();
     }

     public function chatView(Request $request, $id, $type)
     {
          $page_title = 'Chat View';
          $resort = $this->resort;
          $receiver_id = $id;

          if ($type == 'individual') {
               $employee = Employee::where('id', $receiver_id)->with('resortAdmin')->first();
               $data = [
                    'id' => $employee->id,
                    'name' => $employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name,
                    'profile' => Common::getResortUserPicture($employee->id),
               ];
          } else {
               $group = GroupChat::where('id', $receiver_id)->first();
               $data = [
                    'id' => $group->id,
                    'name' => $group->name,
                    'profile' => asset('resorts_assets/images/group-chat.png'),
               ];
          }

          return view('resorts.chat.view', compact('page_title', 'resort', 'receiver_id', 'type', 'data'));
     }

     public function sendMessage(Request $request)
     {
          $resort = $this->resort;

          $conversation = Conversation::create([
               'resort_id' => $resort->resort_id,
               'type' => $request->type,
               'type_id' => $request->type_id,
               'sender_id' => $resort->id,
               'message' => $request->message,
          ]);

          if ($request->hasFile('attachment')) {
               $attachmentPath = $request->file('attachment')->store('attachments', 'public');
               $conversation->attachment = $attachmentPath;
          }

          $conversation->save();

          return response()->json(['success' => true]);
     }
}
