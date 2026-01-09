<?php

namespace App\Http\Controllers\API\ChatBoat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use App\Helpers\Common;
use App\Models\Employee;
use App\Models\ResortAdmin;
use App\Models\Conversation;
use Carbon\Carbon;
use App\Models\GroupChat;
use App\Models\GroupChatMember;
use App\Models\ChatMessageRead;
use Validator;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
     protected $resort;
    public function __construct()
    {
        $this->resort = Auth::guard('api')->user();
    }

// In this controller, we are using the ResortAdmin table's ID, not the Employee table's ID,
// for sender_id and type_id (receiver_id) in the Conversation table.
// Therefore, we are using the ResortAdmin table's ID to retrieve the employee details.

     public function index(Request $request){
     
          $resort = $this->resort;

         $chatHaveReciver = Conversation::where('resort_id', $resort->resort_id)
               ->distinct()
               ->pluck('type_id')
               ->toArray();

         $chatHaveSender = Conversation::where('resort_id', $resort->resort_id)
               ->distinct()
               ->pluck('sender_id')
               ->toArray();

          $chatHaveEmpIds = array_merge($chatHaveReciver, $chatHaveSender);
              $chatWithEmp = ResortAdmin::where('resort_id', $resort->resort_id)
                         ->where('id', '!=', $resort->id)
                         ->whereIn('id', $chatHaveEmpIds)
                         ->with(['GetEmployee' => function ($query) {
                              $query->where('status', 'Active');
                         }])
                         ->get()
                         ->map(function ($ResortAdmin) use ($resort) {

                              // Correctly group the conditions for the last message query
                              $lastMessage = Conversation::where('resort_id', $resort->resort_id)
                                   ->where('type', 'individual')
                                   ->where(function ($q) use ($ResortAdmin) {
                                        $q->where('type_id', $ResortAdmin->id)
                                        ->orWhere('sender_id', $ResortAdmin->id);
                                   })
                                   ->latest('created_at')
                                   ->first();

                              // Correct unread count query
                              $unreadCount = ChatMessageRead::where('user_id', $resort->id)
                                   ->where('status', 'Unread')
                                   ->whereHas('conversation', function ($query) use ($resort, $ResortAdmin) {
                                        $query->where('resort_id', $resort->resort_id)
                                             ->where('type', 'individual')
                                             ->where('sender_id',$ResortAdmin->id)
                                             ->where('type_id', $resort->id);
                                   })->count();

                              return [
                                   'id' => $ResortAdmin->id,
                                   'name' => $ResortAdmin->first_name . ' ' . $ResortAdmin->last_name,
                                   'last_seen' => $ResortAdmin->updated_at,
                                   'profile' => Common::getResortUserPicture($ResortAdmin->id),
                                   'last_msg' => $lastMessage->message ?? null,
                                   'unread_count' => $unreadCount,
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

                                   $unreadCount = ChatMessageRead::where('user_id', $resort->id)
                                        ->where('status', 'Unread')
                                        ->whereHas('conversation', function ($query) use ($resort, $group) {
                                             $query->where('resort_id', $resort->resort_id)
                                                  ->where('type', 'group')
                                                  ->where('sender_id','!=',$resort->id)
                                                  ->where('type_id', $group->id);
                                        })->count();

                              return [
                                   'id' => $group->id,
                                   'name' => $group->name,
                                   'last_seen' => $group->updated_at,
                                   'profile' => $group->profile_picture ?? null,
                                   'last_msg' => $lastMessage->message ?? null,
                                   'unread_count' => $unreadCount,
                                   'type' => 'group',
                              ];
                      });

               $chats = $chatWithEmp->merge($chatInGroups);
          
               return response()->json([
                    'success' => true,
                    'chats' => $chats,
               ]);
     }

// Here we are geting Employee list for new chat but here also used id is resortAdmin table's id
     // not employee table's id
     public function newChat(Request $request){
          $resort = $this->resort;
         

          if($resort->GetEmployee->rank == 3) {
               $hr_access = true;
          }elseif($resort->GetEmployee->rank == 2){
               $hod_access = true;
          }

          $employees = Employee::where('resort_id', $resort->resort_id);

               if(isset($hod_access) && $hod_access) {
                    $employees->where('Dept_id', $resort->GetEmployee->Dept_id);
               }

               $employees->with('resortAdmin',function($query) use ($resort) {
                    $query->where('id','!=' ,$resort->id);
               });

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

          $datas = [];
          foreach ($employees as $employee) {
               if($employee->resortAdmin != null ){
                    $datas[] = [
                         'id' => $employee->resortAdmin->id,
                         'name' => $employee->resortAdmin->full_name,
                         'profile' => Common::getResortUserPicture($employee->resortAdmin->id),
                         'type' => 'individual',
                    ];
               }
          }
          return response()->json([
               'success' => true, 
               'data' => $datas
          ]);
     }

     public function createGroupChat(Request $request){
         
          $resort = $this->resort;
          $validator = Validator::make($request->all(), [
               'name' => 'required|string|max:255',
               'members' => 'required|array|min:1',
          ]);
          
          if ($validator->fails()) {
               return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
          }

          try {

               DB::beginTransaction();
                                  
               $group = GroupChat::create([
                    'name' => $request->name,
                    'resort_id' => $resort->resort_id,
                    'description' => $request->description ?? null,
                    'created_by' => $resort->id,
                    'modified_by' => $resort->id,
               ]);

               $members =  GroupChatMember::create([
                         'user_id' => $resort->id,
                         'chat_group_id' => $group->id,
                         'role' => 'admin',
                         'joined_at' => Carbon::now()
                    ]);
               foreach ($request->members as $memberId) {
                   $members =  GroupChatMember::create([
                         'user_id' => $memberId,
                         'chat_group_id' => $group->id,
                         'role' => 'member',
                         'joined_at' => Carbon::now()
                    ]);
               }
                           
               DB::commit();
               $group->members_count = $group->groupMembers()->count();
               $group->members = $group->groupMembers()->get();

               return response()->json(['success' => true, 'group' => $group], 201);
          } catch (\Exception $e) {
               DB::rollBack();
               \Log::error($e->getMessage());
               return response()->json(['success' => false, 'message' => 'Server error'], 500);
          }
     }

     public function deleteGroup(Request $request, $type_id){
          $resort = $this->resort;
          $group = GroupChat::where('id', $type_id)
               ->where('resort_id', $resort->resort_id)
               ->first();
          if (!$group) {
               return response()->json(['success' => false, 'message' => 'Group not found'], 404);
          }
          if ($group->created_by != $resort->id) {
               return response()->json(['success' => false, 'message' => 'You are not authorized to delete this group'], 403);
          }
          try {
               DB::beginTransaction();
               $conversation = Conversation::where('type', 'group')
                    ->where('type_id', $group->id)
                    ->delete();

               $group->groupMembers()->delete();
               $group->delete();
               DB::commit();
               return response()->json(['success' => true, 'message' => 'Group deleted successfully'], 200);
          } catch (\Exception $e) {
               DB::rollBack();
               \Log::error($e->getMessage());
               return response()->json(['success' => false, 'message' => 'Server error'], 500);

          }

     }

     public function newEmployeeList(Request $request,$type_id)
     {
          $resort = $this->resort;
          try {

               $group = GroupChat::where('id', $type_id)
                    ->where('resort_id', $resort->resort_id)
                    ->first();
               $members = $group->groupMembers()->pluck('user_id')->toArray();

               $newMemberList = Employee::where('resort_id', $resort->resort_id)
                    ->with('resortAdmin',function($query) use ($resort, $members) {
                         $query->whereNotIn('id', $members);
                    })->get();

               $datas = [];
               foreach ($newMemberList as $employee) {
                         if($employee->resortAdmin != null ){
                              $datas[] = [
                                   'id' => $employee->resortAdmin->id,
                                   'name' => $employee->resortAdmin->full_name,
                                   'profile' => Common::getResortUserPicture($employee->resortAdmin->id),
                              ];
                         }
                    }
               return response()->json(['success' => true, 'data' => $datas], 201);
          } catch (\Exception $e) {
               \Log::error($e->getMessage());
               return response()->json(['success' => false, 'message' => 'Server error'], 500);
          }
     }

     public function addMember(Request $request, $type_id)
     {
          $resort = $this->resort;
          $validator = Validator::make($request->all(), [
               'members' => 'required|array|min:1',
          ]);
          if ($validator->fails()) {
               return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
          }
          try {
               DB::beginTransaction();
               $group = GroupChat::where('id', $type_id)
                    ->where('resort_id', $resort->resort_id)
                    ->first();
                    
               if (!$group) {
                    return response()->json(['success' => false, 'message' => 'Group not found'], 404);
               }

               foreach ($request->members as $memberId) {
                    if($group->groupMembers()->where('user_id', $memberId)->exists()) {
                         continue; // Skip if member already exists
                    }
                    GroupChatMember::create([
                         'user_id' => $memberId,
                         'chat_group_id' => $group->id,
                         'role' => 'member',
                         'joined_at' => Carbon::now()
                    ]);
               }

               DB::commit();
               $group->members_count = $group->groupMembers()->count();
               $group->members = $group->groupMembers()->get();
               return response()->json(['success' => true, 'message' => 'Members added successfully', 'group' => $group], 201);
          } catch (\Exception $e) {
               DB::rollBack();
               \Log::error($e->getMessage());
               return response()->json(['success' => false, 'message' => 'Server error'], 500);
          }
     }

     public function removeMember(Request $request, $type_id)
     {
          $resort = $this->resort;
          $validator = Validator::make($request->all(), [
               'member_id' => 'required|exists:chat_group_member,user_id',
          ]);
          if ($validator->fails()) {
               return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
          }
          try {
               DB::beginTransaction();
               $group = GroupChat::where('id', $type_id)
                    ->where('resort_id', $resort->resort_id)
                    ->first();     
               if (!$group) {
                    return response()->json(['success' => false, 'message' => 'Group not found'], 404);

               }
               $member = GroupChatMember::where('chat_group_id', $group->id)
                    ->where('user_id', $request->member_id)
                    ->where('role', '!=', 'admin') // Ensure admin cannot be removed
                    ->first();
                    
               if (!$member) {
                    return response()->json(['success' => false, 'message' => 'Member not found in this group'], 404);
               }
               dd($member);
               $member->delete();
               DB::commit();
               $group->members_count = $group->groupMembers()->count();
               $group->members = $group->groupMembers()->get();
               return response()->json(['success' => true, 'message' => 'Member removed successfully', 'group' => $group], 200);
          } catch (\Exception $e) {
               DB::rollBack();
               \Log::error($e->getMessage());
               return response()->json(['success' => false, 'message' => 'Server error'], 500);
          }

     }

}
