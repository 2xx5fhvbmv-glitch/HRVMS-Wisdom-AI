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
                                   'profile' => $group->profile_picture ? \App\Helpers\StorageHelper::temporaryUrl($group->profile_picture) : null,
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

          // One-to-one chat is unrestricted for every user regardless of
          // department, position, rank, or reporting line (Chat Module spec
          // §2/§14) — department scoping only applies to group creation.
          $employees = Employee::where('resort_id', $resort->resort_id);

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

          $permission = $this->resolveChatGroupPermission($resort);
          if (!$permission['can_create']) {
               return response()->json(['success' => false, 'message' => 'You are not authorized to create a group.'], 403);
          }

          // Server-side enforcement — a dept HOD/XCOM's group is scoped to
          // their own department no matter what the client sends, so
          // manually entering another department's employee id can't bypass
          // the restriction (Chat Module spec rule #10).
          $outOfScope = $this->membersOutsideChatScope($request->members, $resort, $permission);
          if (!empty($outOfScope)) {
               return response()->json(['success' => false, 'message' => 'You can only add employees from your own department to this group.'], 403);
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
          if (!$this->isGroupAdmin($group, $resort)) {
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

               if (!$group) {
                    return response()->json(['success' => false, 'message' => 'Group not found'], 404);
               }
               if (!$this->isGroupAdmin($group, $resort)) {
                    return response()->json(['success' => false, 'message' => 'You are not authorized to manage this group.'], 403);
               }

               $members = $group->groupMembers()->pluck('user_id')->toArray();
               $permission = $this->resolveChatGroupPermission($resort);

               $newMemberList = Employee::where('resort_id', $resort->resort_id)
                    ->when(!$permission['unrestricted'], fn($q) => $q->where('Dept_id', $permission['dept_id']))
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
               if (!$this->isGroupAdmin($group, $resort)) {
                    return response()->json(['success' => false, 'message' => 'You are not authorized to manage this group.'], 403);
               }

               $permission = $this->resolveChatGroupPermission($resort);
               $outOfScope = $this->membersOutsideChatScope($request->members, $resort, $permission);
               if (!empty($outOfScope)) {
                    return response()->json(['success' => false, 'message' => 'You can only add employees from your own department to this group.'], 403);
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
               if (!$this->isGroupAdmin($group, $resort)) {
                    return response()->json(['success' => false, 'message' => 'You are not authorized to manage this group.'], 403);
               }
               $member = GroupChatMember::where('chat_group_id', $group->id)
                    ->where('user_id', $request->member_id)
                    ->where('role', '!=', 'admin') // Ensure admin cannot be removed
                    ->first();
                    
               if (!$member) {
                    return response()->json(['success' => false, 'message' => 'Member not found in this group'], 404);
               }
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

     /**
      * Group creation/member-selection permission for the logged-in user
      * (Chat Module spec §4-§7). One-to-one chat (newChat) is deliberately
      * NOT gated by this — only group creation and membership are.
      *
      * - GM, HR (rank 3), and HR-department EXCOM/HOD get unrestricted,
      *   cross-department group creation.
      * - Other EXCOM/HOD (rank 1/2) and the Finance approver role (rank 7)
      *   can create groups, but member selection is scoped to their own
      *   department.
      * - Everyone else (line workers / general employees) cannot create
      *   groups at all.
      */
     private function resolveChatGroupPermission($resort)
     {
          $employee = $resort->GetEmployee;
          $rank = (int) ($employee->rank ?? 0);
          $deptId = $employee->Dept_id ?? null;

          $unrestricted = $rank === 8
               || $rank === 3
               || (in_array($rank, [1, 2], true) && Common::isHRDepartment($deptId));

          if ($unrestricted) {
               return ['can_create' => true, 'unrestricted' => true, 'dept_id' => null];
          }

          $canCreate = in_array($rank, [1, 2, 7], true) && !empty($deptId);

          return ['can_create' => $canCreate, 'unrestricted' => false, 'dept_id' => $deptId];
     }

     /**
      * Member ids (resort_admin ids) outside the acting user's department —
      * always empty for an unrestricted permission. Checked server-side so
      * a restricted user can't bypass the UI by posting another
      * department's employee id directly (Chat Module spec rule #10).
      */
     private function membersOutsideChatScope(array $memberIds, $resort, array $permission)
     {
          if (empty($memberIds)) {
               return [];
          }

          $deptByAdminId = Employee::where('resort_id', $resort->resort_id)
               ->whereIn('Admin_Parent_id', $memberIds)
               ->pluck('Dept_id', 'Admin_Parent_id');

          return collect($memberIds)
               ->filter(function ($id) use ($deptByAdminId, $permission) {
                    // Not an employee of this resort at all — blocked for
                    // everyone, including HR/GM. This is tenant isolation,
                    // not the department rule below, so it isn't skipped by
                    // 'unrestricted'.
                    if (!$deptByAdminId->has($id)) {
                         return true;
                    }
                    if ($permission['unrestricted']) {
                         return false;
                    }
                    return $deptByAdminId[$id] != $permission['dept_id'];
               })
               ->values()->all();
     }

     /**
      * Group Admin = the creator, OR — HR administrative override — any HR
      * user (rank 3, or EXCOM/HOD inside the HR department) when the group
      * was itself created by an HR user. Both HR HOD and HR XCOM can manage
      * each other's HR-created groups regardless of who created it.
      */
     private function isGroupAdmin(GroupChat $group, $resort)
     {
          if ((int) $group->created_by === (int) $resort->id) {
               return true;
          }

          $actingPermission = $this->resolveChatGroupPermission($resort);
          $isActingHr = (int) ($resort->GetEmployee->rank ?? 0) === 3
               || (in_array((int) ($resort->GetEmployee->rank ?? 0), [1, 2], true) && Common::isHRDepartment($resort->GetEmployee->Dept_id ?? null));
          if (!$isActingHr) {
               return false;
          }

          $creator = ResortAdmin::find($group->created_by);
          $creatorEmployee = $creator->GetEmployee ?? null;
          $creatorRank = (int) ($creatorEmployee->rank ?? 0);
          $isCreatorHr = $creatorRank === 3
               || (in_array($creatorRank, [1, 2], true) && Common::isHRDepartment($creatorEmployee->Dept_id ?? null));

          return $isCreatorHr;
     }

     /**
      * Candidate list for a brand-new group (no group exists yet) — same
      * department scoping as newEmployeeList(), without an exclusion list.
      */
     public function groupMemberCandidates(Request $request)
     {
          $resort = $this->resort;
          $permission = $this->resolveChatGroupPermission($resort);

          if (!$permission['can_create']) {
               return response()->json(['success' => false, 'message' => 'You are not authorized to create a group.'], 403);
          }

          $employees = Employee::where('resort_id', $resort->resort_id)
               ->when(!$permission['unrestricted'], fn($q) => $q->where('Dept_id', $permission['dept_id']))
               ->with('resortAdmin', fn($query) => $query->where('id', '!=', $resort->id))
               ->get();

          $datas = [];
          foreach ($employees as $employee) {
               if ($employee->resortAdmin != null) {
                    $datas[] = [
                         'id' => $employee->resortAdmin->id,
                         'name' => $employee->resortAdmin->full_name,
                         'profile' => Common::getResortUserPicture($employee->resortAdmin->id),
                    ];
               }
          }

          return response()->json(['success' => true, 'data' => $datas]);
     }

     /**
      * Group Admin action — rename the group and/or replace its photo.
      */
     public function updateGroup(Request $request, $type_id)
     {
          $resort = $this->resort;
          $validator = Validator::make($request->all(), [
               'name' => 'nullable|string|max:255',
               'photo' => 'nullable|image|max:5120',
          ]);
          if ($validator->fails()) {
               return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
          }

          $group = GroupChat::where('id', $type_id)
               ->where('resort_id', $resort->resort_id)
               ->first();
          if (!$group) {
               return response()->json(['success' => false, 'message' => 'Group not found'], 404);
          }
          if (!$this->isGroupAdmin($group, $resort)) {
               return response()->json(['success' => false, 'message' => 'You are not authorized to manage this group.'], 403);
          }

          if ($request->filled('name')) {
               $group->name = $request->name;
          }

          if ($request->hasFile('photo')) {
               $status = Common::AWSEmployeeFileUpload($resort->resort_id, $request->file('photo'), $resort->GetEmployee->Emp_id, 'ChatGroupPhotos', false);
               if (($status['status'] ?? false) === true) {
                    $group->profile_picture = $status['path'];
               }
          }

          $group->modified_by = $resort->id;
          $group->save();

          return response()->json([
               'success' => true,
               'message' => 'Group updated successfully.',
               'group' => [
                    'id' => $group->id,
                    'name' => $group->name,
                    'profile_picture' => $group->profile_picture ? \App\Helpers\StorageHelper::temporaryUrl($group->profile_picture) : null,
               ],
          ]);
     }

     /**
      * FAQ content for the "Chat FAQ" placeholder screen — no bot service,
      * just a static per-resort list.
      */
     public function faqList()
     {
          if (!$this->resort) {
               return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
          }

          try {
               $faqs = \App\Models\Faq::where('resort_id', $this->resort->resort_id)
                    ->where('status', 'Active')
                    ->orderBy('sort_order', 'asc')
                    ->get(['id', 'question', 'answer']);

               return response()->json([
                    'success' => true,
                    'message' => 'FAQ list fetched successfully.',
                    'data'    => $faqs,
               ], 200);

          } catch (\Exception $e) {
               \Log::emergency("File: " . $e->getFile());
               \Log::emergency("Line: " . $e->getLine());
               \Log::error($e->getMessage());
               return response()->json(['success' => false, 'message' => 'Server error'], 500);
          }
     }

}
