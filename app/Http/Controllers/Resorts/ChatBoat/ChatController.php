<?php

namespace App\Http\Controllers\Resorts\ChatBoat;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use App\Helpers\Common;
use App\Models\Employee;
use App\Models\ResortAdmin;
use App\Models\Conversation;
use Carbon\Carbon;
use App\Models\GroupChat;


class ChatController extends Controller
{
     public $resort;

     public function __construct()
     {
          $this->resort = Auth::guard('resort-admin')->user();
          if(!$this->resort) return;
     }

     public function index(Request $request)
     {
          $page_title = 'Chat';
          $resort = $this->resort;

          $chatHaveEmpIds = Conversation::where('resort_id', $resort->resort_id)
               ->distinct()
               ->pluck('type_id')
               ->toArray();

          $chatWithEmp = Employee::where('resort_id', $resort->resort_id)
               ->with('resortAdmin')
               ->whereIn('id', $chatHaveEmpIds)
               ->whereIn('status', ['Active'])
               ->get()
               ->map(function ($employee) use ($resort) {
                    $lastMessage = Conversation::where('resort_id', $resort->resort_id)
                         ->where('type', 'individual')
                         ->where('type_id', $employee->id)
                         ->latest('created_at')
                         ->first();

                    return [
                         'id' => $employee->id,
                         'name' => $employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name,
                         'last_seen' => $employee->updated_at,
                         'profile' => Common::getResortUserPicture($employee->id),
                         'last_msg' => $lastMessage->message ?? null,
                         'type' => 'individual',
                    ];
               });

          $chatInGroups = GroupChat::where('resort_id', $resort->resort_id)
               ->join('chat_group_member', 'chat_group_member.chat_group_id', '=', 'chat_group.id')
               ->where('chat_group_member.user_id', $resort->id)
               ->select('chat_group.*')
               ->get()
               ->map(function ($group) use ($resort) {
                    $lastMessage = Conversation::where('resort_id', $resort->resort_id)
                         ->where('type', 'group')
                         ->where('type_id', $group->id)
                         ->latest('created_at')
                         ->first();

                    return [
                         'id' => $group->id,
                         'name' => $group->name,
                         'last_seen' => $group->updated_at,
                         'profile' => $group->profile_picture ?? null,
                         'last_msg' => $lastMessage->message ?? null,
                         'type' => 'group',
                    ];
               });

          $chats = $chatWithEmp->merge($chatInGroups);

          return view('resorts.chat.index', compact('page_title', 'resort', 'chats'));
     }

     public function newChat(Request $request)
     {
          $resort = $this->resort;
          $chatHaveEmpIds = Conversation::where('resort_id', $resort->resort_id)
               ->where('type', 'individual')
               ->distinct()
               ->pluck('type_id')
               ->toArray();

          $employees = Employee::where('resort_id', $resort->resort_id)
               ->with('resortAdmin')
               ->whereNotIn('id', $chatHaveEmpIds)
               ->whereIn('status', ['Active']);

          if ($request->has('search') && $request->search != '') {
               $searchTerm = $request->search;
               $employees->where(function ($query) use ($searchTerm) {
                    $query->where('id', 'LIKE', "%{$searchTerm}%")
                         ->orWhere('Emp_id', 'LIKE', "%{$searchTerm}%")
                         ->orWhereHas('resortAdmin', function ($adminQuery) use ($searchTerm) {
                              $adminQuery->where('first_name', 'LIKE', "%{$searchTerm}%")
                                   ->orWhere('last_name', 'LIKE', "%{$searchTerm}%")
                                   ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$searchTerm}%"])
                                   ->orWhere('email', 'LIKE', "%{$searchTerm}%");
                         });
               });
          }

          $employees = $employees->get();

          $html = view('resorts.chat.new_chat', compact('employees'))->render();

          return response()->json(['success' => true, 'html' => $html]);
     }
}
