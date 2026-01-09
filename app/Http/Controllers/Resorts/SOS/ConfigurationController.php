<?php

namespace App\Http\Controllers\Resorts\SOS;


use Excel;
use DB;
use URL;
use Auth;
use Carbon\Carbon;
use App\Helpers\Common;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;
use App\Models\Employee;
use App\Models\ResortAdmin;
use App\Models\SOSRolesAndPermission;
use App\Models\SOSTeamManagementModel; 
use App\Models\SOSTeamMemeberModel;
use App\Models\SOSEmergencyTypesModel;
use App\Models\SOSChildEmergencyType;
use Google\Service\CloudControlsPartnerService\Console;
use Illuminate\Support\Facades\Validator;

class ConfigurationController extends Controller
{
 
    public $resort;
    public $reporting_to;
    protected $underEmp_id=[];
    public function __construct()
    {
        $this->resort = $resortId = auth()->guard('resort-admin')->user();
        // $reporting_to = $this->resort->GetEmployee->id;
        // $this->underEmp_id = Common::getSubordinates($reporting_to);
    }
    public function index()
    {
        $page_title = "Configuration";
        
        $getMembers = ResortAdmin::where("resort_id",$this->resort->resort_id)
                            ->where("is_employee",'1')
                            ->where("type","sub")
                            ->where("status","active")
                            ->get();
        $Roles = SOSRolesAndPermission::where("resort_id",$this->resort->resort_id)->get();
        $allTeams = SOSTeamManagementModel::where("resort_id",$this->resort->resort_id)->get();

        return view('resorts.SOS.configuration.index', compact('page_title', 'Roles', 'getMembers', 'allTeams'));
    }
    public function SOSRolesAndPermissionStore(Request $request)
    {
        $resort_id = $this->resort->resort_id;
        $validator = Validator::make($request->all(), [
            'sos' => 'required|array|min:1',
            'sos.*.role_name' => ['required', 'string', 'max:255'],
            'sos.*.role_name.*' => ['required', 'distinct'],

            'sos.*.assign_permission.*' => 'required|array|min:1',
            'sos.*.assign_permission.*' => 'string'

            // Custom Rule to ensure uniqueness for each role and permission
        ], [
            'sos.*.role_name.required' => 'Please Enter Role Name.',
            'sos.*.role_name.*.required' => 'Role Name is required.',
            'sos.*.role_name.*.distinct' => 'Duplicate Role Name are not allowed.',

            'sos.*.assign_permission.required' => 'Please select at least one Permission.',
            'sos.*.assign_permission.*.required' => 'Each Permission is required.'
        ]);

        $validator->after(function ($validator) use ($request, $resort_id) {

            foreach ($request->sos as $index => $value) {
        
                if ($value) {
                    $exists = DB::table('sos_role_management')
                        ->where('resort_id', $resort_id)
                        ->where('name', $value['role_name'])
                        ->exists();
        
                    if ($exists) {
                        $validator->errors()->add("role_name.$index", "This Role is already exists for this resort.");
                    }
                }
            }
        });
        
       
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        DB::beginTransaction();
        try
        {
            foreach($request->sos as $ak=>$val)
            {
                $permission = implode(',', $val['assign_permission']);
                SOSRolesAndPermission::create(
                    [
                        'resort_id'=>$resort_id,
                        'name'=>$val['role_name'],
                        'permission'=>$permission
                    ]
                );
            }
       
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'SOS Roles and Permission Create Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Create SOS Roles and Permission'], 500);
        }
    }

    public function SOSTeamStore(Request $request)
    {
        $resort_id = $this->resort->resort_id;
        
        $validator = Validator::make($request->all(), [
            'employee' => 'required|array|min:1',
            'team_name' => [
                'required',
                Rule::unique('sos_teams', 'name')->where(function ($query) use ($resort_id) {
                    return $query->where('resort_id', $resort_id);
                })
            ],
            'employee.*.team_member' => 'required|array|min:1', // expects array of IDs
            'employee.*.member_role' => 'required',
            'description' => ['required', 'string'],
        ], [
            'team_name.required' => 'The Team Name field is required.',
            'employee.*.team_member.required' => 'Please select at least one Employee.',
            'employee.*.team_member.*.required' => 'Each Employee is required.',
            'employee.*.member_role.required' => 'Please select at least one Role.',
            'employee.*.member_role.*.required' => 'Each Role is required.',
            'description.required' => 'The Description field is required.',
        ]);

        $validator->after(function ($validator) use ($request, $resort_id) {
            $pairs = [];
            foreach ($request->employee as $index => $employeeData) {
                $role = $employeeData['member_role'] ?? null;
                $members = $employeeData['team_member'] ?? [];
                if (!is_array($members)) {
                    $members = [$members];
                }
                foreach ($members as $member) {
                    if ($role) {
                        $pair = $member . '-' . $role;
                        if (in_array($pair, $pairs)) {
                            $validator->errors()->add('team_member.' . $index, 'Duplicate employee-role pair found: ' . $pair);
                        } else {
                            $pairs[] = $pair;
                        }
                    }
                    if ($role) {
                        $exists = DB::table('sos_teams as t1')->join('sos_team_members as t2', 't1.id', '=', 't2.team_id')
                            ->where('t1.resort_id', $resort_id)
                            ->where('t2.emp_id', $member)
                            ->where('t2.role_id', $role)
                            ->where('t1.name', $request->team_name)
                            ->exists();
                        if ($exists) {
                            $validator->errors()->add("team_member.$index", "This team member-role already exists for this resort.");
                        }
                    }
                }
            }
        });
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
    
        DB::beginTransaction();
        try
        {
            $teamExists = DB::table('sos_teams')
                        ->where('resort_id', $resort_id)
                        ->where('name', $request->team_name)
                        ->get();
            
            $insertedId = "";
            
            if (!$teamExists->isEmpty()) {
                $insertedId = $teamExists[0]->id;
            } else {
                $insert = SOSTeamManagementModel::create(
                    [
                        'resort_id'=>$resort_id,
                        'name'=>$request->team_name,
                        'description'=>strip_tags($request->description)
                        ]
                    );
                $insertedId = $insert->id;
            }
            
            if ($insertedId) {
                foreach($request->employee as $block) {
                    $roleId = $block['member_role'];
                    $members = $block['team_member'];
                    if (!is_array($members)) {
                        $members = [$members];
                    }
                    foreach ($members as $memberId) {
                        SOSTeamMemeberModel::create([
                            'resort_id' => $resort_id,
                            'team_id' => $insertedId,
                            'emp_id' => $memberId,
                            'role_id' => $roleId
                        ]);
                    }
                }
            }
   
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'SOS Team Create Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Create SOS Team'], 500);
        }
    }

    public function IndexSOSRolesAndPermission(Request $request)
    {
        $page_title = "SOS Roles And Permissions";
        $SOSRolesAndPermissions = SOSRolesAndPermission::where('resort_id', $this->resort->resort_id)->get();
        $sosAssignPermissions = config('settings.sosAssignPermissions');

        if ($request->ajax()) {
            return datatables()->of($SOSRolesAndPermissions)
                ->addColumn('assigned_permission', function ($row) use ($sosAssignPermissions) {
                    // Handle both array and comma-separated string
                    $ids = is_array($row->permission) ? $row->permission : explode(',', $row->permission);
                    $names = [];
                    foreach ($ids as $id) {
                        $id = trim($id);
                        if (isset($sosAssignPermissions[$id])) {
                            $names[] = $sosAssignPermissions[$id];
                        }
                    }
                    return implode(', ', $names);
                })
                ->addColumn('Action', function ($row) {
                    $id = base64_encode($row->id);
                    // Pass permission IDs as JSON for JS edit
                    $permissionIds = is_array($row->permission) ? $row->permission : explode(',', $row->permission);
                    return '
                        <div  class="d-flex align-items-center">
                            <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" 
                            data-role_name ="' . $row->name . '"  
                            data-id="' . e($id) . '"
                            data-assign_permission=\'' . json_encode($permissionIds) . '\'
                            >
                                <img src="' . asset("resorts_assets/images/edit.svg") . '" alt="Edit" class="img-fluid">
                            </a>
                            <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-id="' . e($id) . '">
                                <img src="' . asset("resorts_assets/images/trash-red.svg") . '" alt="Delete" class="img-fluid">
                            </a>
                        </div>';
                })
                ->rawColumns(['assigned_permission', 'Action'])
                ->make(true);
        }

        return view('resorts.SOS.configuration.IndexRoleAndPermission', compact('SOSRolesAndPermissions', 'page_title'));
    }

    public function SOSRolesAndPermissionDestory($id)
    {   
        $id = base64_decode($id);
        DB::beginTransaction();
        try
        {

            SOSRolesAndPermission::where("id",$id)->delete();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'SOS Roles And Permission Delete Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Delete SOS Role'], 500);
        }
    }

    public function SOSRoleAndPerminlineUpdate(Request $request)
    {
 
        $Main_id = (int) base64_decode($request->Main_id);
        $resort_id = $this->resort->resort_id;

        $validator = Validator::make($request->all(), [
            'role_name' => [
                'required',
                Rule::unique('sos_role_management', 'name')->where(function ($query) use ($resort_id, $Main_id,$request) {
                    return $query->where('resort_id', $resort_id);
                })->ignore($Main_id),  
            ],
            'assign_permission' => 'required'
        ], [
            'assign_permission.required' => 'Please Select Permission.',
            'role_name.required' => 'The Role name field is required.',
            'role_name.unique' => 'The Role already exists for this resort.',
        ]);

            if($validator->fails())
            {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

    
            DB::beginTransaction();
            try
            {
                $permission = implode(',', $request->assign_permission);
                
                SOSRolesAndPermission::where('resort_id', $this->resort->resort_id)
                ->where('id', $Main_id)
                ->update([
                    'name' => $request->role_name,
                    'permission' => $permission,
                ]);
                
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Role And Permission Updated Successfully',
                ], 200);
            }
            catch (\Exception $e)
            {
                DB::rollBack();
                \Log::emergency("File: " . $e->getFile());
                \Log::emergency("Line: " . $e->getLine());
                \Log::emergency("Message: " . $e->getMessage());
                return response()->json(['error' => 'Failed to Updated Role And Permission '], 500);
            }
    }

    
    public function IndexSOSTeamManagement(Request $request)
    {
        $page_title="Team Management";

        $query= SOSTeamManagementModel::with(['members', 'members.teamMember'])->where('resort_id',$this->resort->resort_id);
        if ($request->searchTerm) {
            $searchTerm = $request->searchTerm;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                ->orWhere('description', 'LIKE', "%{$searchTerm}%")
                ->orWhereHas('members.teamMember', function ($nestedQuery) use ($searchTerm) {
                    $nestedQuery->where(function ($qq) use ($searchTerm) {
                        $qq->where('first_name', 'LIKE', "%{$searchTerm}%")
                            ->orWhere('last_name', 'LIKE', "%{$searchTerm}%")
                            ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$searchTerm}%"]);
                    });
                });
            });
        }

        $SOSTeamManagement = $query->get();

        if($request->ajax())
        {
            return datatables()->of($SOSTeamManagement)
            ->addColumn('assigned_employees', function ($row) {
                $employeesImages = '';
                $teamMember = $row->members;
                $count = count($teamMember);
                $displayLimit = 5;
    
                foreach ($teamMember as $index => $p) {
                    $emp = $p->teamMember;
                    if ($emp) {
                        $image = Common::getResortUserPicture($emp->Admin_Parent_id ?? null);
                        if ($index < $displayLimit) {
                            $employeesImages .= '
                                <div class="img-circle" title="' . e($emp->first_name . ' ' . $emp->last_name) . '">
                                    <img src="' . $image . '" alt="' . e($emp->full_name) . '">
                                </div>
                            ';
                        }
                    }
                }
    
                if ($count > $displayLimit) {
                    $employeesImages .= '<div class="num">+' . ($count - $displayLimit) . '</div>';
                }
    
                return '<div class="user-ovImg d-flex">' . $employeesImages . '</div>';
            })
            ->addColumn('Action', function ($row) {
                $id = base64_encode($row->id);
                            return '
                            <div  class="d-flex align-items-center">
                                <a href="' . route('sos.team.detail', e($id)) . '" title="View Team Detail" class="btn-tableIcon btnIcon-yellow me-1">
                                    <i class="fa-regular fa-eye"></i>
                                </a>
                                <a href="#editTeam-modal" data-bs-toggle="modal" class="btn-lg-icon icon-bg-green me-1 edit-team-btn" 
                                data-name ="'.$row->name.'"  
                                data-id="' . e($id) . '"
                                data-description="'.$row->description.'"
                                data-teamMembers="'.$row->members.'"
                                >
                                    <img src="' . asset("resorts_assets/images/edit.svg") . '" alt="Edit" class="img-fluid">
                                </a>
                                <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-id="' . e($id) . '">
                                    <img src="' . asset("resorts_assets/images/trash-red.svg") . '" alt="Delete" class="img-fluid">
                                </a>
                            </div>';
            })
            ->rawColumns(['assigned_employees','Action'])
            ->make(true);
        }
        $getMembers = ResortAdmin::where("resort_id",$this->resort->resort_id)
                            ->where("is_employee",'1')
                            ->where("type","sub")
                            ->where("status","active")
                            ->get();
        $Roles = SOSRolesAndPermission::where("resort_id",$this->resort->resort_id)->get();

        return view('resorts.SOS.configuration.IndexSOSTeamManagement',compact('SOSTeamManagement','getMembers','Roles','page_title'));
    }
    
    public function SOSTeamManagementDestory($id)
    {   
        $id = base64_decode($id);
        DB::beginTransaction();
        try
        {

            $team = SOSTeamManagementModel::where("id",$id)->delete();

            if ($team) {
                SOSTeamMemeberModel::where("team_id",$id)->delete();
            }
            
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'SOS Team Delete Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Delete SOS Team'], 500);
        }
    }

    
    public function SOSTeamManagementinlineUpdate(Request $request)
    {
 
        $Main_id = (int) base64_decode($request->Main_id);
        $resort_id = $this->resort->resort_id;

        $validator = Validator::make($request->all(), [
            'team_name' => [
                'required',
                Rule::unique('sos_teams', 'name')->where(function ($query) use ($resort_id, $Main_id,$request) {
                    return $query->where('resort_id', $resort_id);
                })->ignore($Main_id),  
            ],
            'description' => 'required'
        ], [
            'description.required' => 'Please Enter Description.',
            'team_name.required' => 'The team name field is required.',
            'team_name.unique' => 'The team already exists for this resort.',
        ]);

            if($validator->fails())
            {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

    
            DB::beginTransaction();
            try
            {
                SOSTeamManagementModel::where('resort_id', $this->resort->resort_id)
                ->where('id', $Main_id)
                ->update([
                    'name' => $request->team_name,
                    'description' => strip_tags($request->description),
                ]);
                
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'SOS Team Updated Successfully',
                ], 200);
            }
            catch (\Exception $e)
            {
                DB::rollBack();
                \Log::emergency("File: " . $e->getFile());
                \Log::emergency("Line: " . $e->getLine());
                \Log::emergency("Message: " . $e->getMessage());
                return response()->json(['error' => 'Failed to Updated SOS Team '], 500);
            }
    }

    public function team_details($id, Request $request)
    {
        $page_title ='Team Detail';
        $id = base64_decode($id);
        $resort_id = $this->resort->resort_id;

        $team = SOSTeamManagementModel::with(['members', 'members.teamMember'])->where('id',$id)->first();
        $Roles = SOSRolesAndPermission::where("resort_id",$this->resort->resort_id)->get();
        $getMembers = ResortAdmin::where("resort_id",$this->resort->resort_id)
                            ->where("is_employee",'1')
                            ->where("type","sub")
                            ->where("status","active")
                            ->get();

        return view('resorts.SOS.configuration.SOSTeamDetail',compact('team','Roles','getMembers','page_title'));
    }

    public function get_team_details($id, Request $request)
    {
        $team_members = SOSTeamMemeberModel::with(['teamMember', 'memberRole'])->where('team_id',$id)->get();

        if($request->ajax())
        {
            return datatables()->of($team_members)
            ->addColumn('team_member_name', function ($member) {
                return $member->teamMember->first_name." ". $member->teamMember->last_name;
            })
            ->addColumn('member_role', function ($member) {
                return $member->memberRole->name;
            })
            ->addColumn('Action', function ($row) {
                $id = base64_encode($row->id);
                            return '
                            <div  class="d-flex align-items-center">
                                <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" 
                                data-member_id ="'.$row->emp_id.'"  
                                data-role_id ="'.$row->role_id.'"  
                                data-id="' . e($id) . '"
                                >
                                    <img src="' . asset("resorts_assets/images/edit.svg") . '" alt="Edit" class="img-fluid">
                                </a>
                                <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-id="' . e($id) . '">
                                    <img src="' . asset("resorts_assets/images/trash-red.svg") . '" alt="Delete" class="img-fluid">
                                </a>
                            </div>';
            })
            ->rawColumns(['team_member_name','member_role','Action'])
            ->make(true);
        }
    }

    
    public function SOSTeamMemberDestory($id)
    {   
        $id = base64_decode($id);
        DB::beginTransaction();
        try
        {
            $team = SOSTeamMemeberModel::where("id",$id)->delete();
            
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'SOS Team Member Delete Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Delete SOS Team Member'], 500);
        }
    }

    public function SOSTeamMemberinlineUpdate(Request $request)
    {
        $Main_id = (int) base64_decode($request->Main_id);

        $validator = Validator::make($request->all(), [
            'member_id' => [
                'required' 
            ],
            'role_id' => 'required'
        ], [
            'role_id.required' => 'Please select role.',
            'member_id.required' => 'The assigned member field is required.',
        ]);

        $validator->after(function ($validator) use ($request, $Main_id) {
            if ($request->role_id && $request->member_id) {

                $exists = SOSTeamMemeberModel::where('emp_id', $request->member_id) 
                        ->where('role_id', $request->role_id)
                        ->where('team_id', $request->team_id)
                        ->where('id', '!=', $Main_id)
                        ->exists();
    
                if ($exists) {
                    $validator->errors()->add("team_member", "This team already exists for this resort.");
                }
                
            }
        });

        if($validator->fails())
        {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }



        DB::beginTransaction();
        try
        {
            SOSTeamMemeberModel::where('id', $Main_id)
            ->update([
                'emp_id' => $request->member_id,
                'role_id' => $request->role_id,
            ]);
            
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'SOS Team Member Updated Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Updated SOS Team Member'], 500);
        }
    }
    
    public function getTeamData($id)
    {
        $id = base64_decode($id);

        $team = SOSTeamManagementModel::with(['members','members.teamMember'])->findOrFail($id);

        $allEmployees = ResortAdmin::where("resort_id",$this->resort->resort_id)
                            ->where("is_employee",'1')
                            ->where("type","sub")
                            ->where("status","active")
                            ->get();
        $allRoles = SOSRolesAndPermission::where("resort_id",$this->resort->resort_id)->get();

        return response()->json([
            'id' => $team->id,
            'name' => $team->name,
            'description' => $team->description,
            'members' => $team->members->map(function ($member) {
                return [
                    'team_member_id' => $member->emp_id,
                    'team_member_name' => $member->teamMember->first_name . ' ' . $member->teamMember->last_name,
                    'member_role' => $member->role_id,
                ];
            }),
            'all_employees' => $allEmployees->map(function ($emp) {
                return [
                    'id' => $emp->id,
                    'name' => $emp->first_name . ' ' . $emp->last_name
                ];
            }),
            'all_roles' => $allRoles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name
                ];
            }),
        ]);
    }

    public function update_team_details(Request $request, $id)
    {   
        $resort_id = $this->resort->resort_id;
        $validator = Validator::make($request->all(), [
            'employee' => 'required|array|min:1',
            'team_name' => [
                'required',
                Rule::unique('sos_teams', 'name')->where(function ($query) use ($resort_id, $id, $request) {
                    return $query->where('resort_id', $resort_id);
                })->ignore($id),
            ],
            'employee.*.team_member' => 'required|array|min:1', // expects array of IDs
            'employee.*.member_role' => 'required',
            'description' => 'required|string',
        ], [
            'team_name.required' => 'The Team Name field is required.',
            'employee.*.team_member.required' => 'Please select at least one Employee.',
            'employee.*.team_member.*.required' => 'Each Employee is required.',
            'employee.*.member_role.required' => 'Please select at least one Role.',
            'employee.*.member_role.*.required' => 'Each Role is required.',
            'description.required' => 'The Description field is required.',
        ]);

        $validator->after(function ($validator) use ($request, $resort_id, $id) {
            $pairs = [];
            foreach ($request->employee as $index => $employeeData) {
                $role = $employeeData['member_role'] ?? null;
                $members = $employeeData['team_member'] ?? [];
                if (!is_array($members)) {
                    $members = [$members];
                }
                foreach ($members as $member) {
                    if ($role) {
                        $pair = $member . '-' . $role;
                        if (in_array($pair, $pairs)) {
                            $validator->errors()->add('team_member.' . $index, 'Duplicate employee-role pair found: ' . $pair);
                        } else {
                            $pairs[] = $pair;
                        }
                    }
                    if ($role) {
                        $exists = DB::table('sos_teams as t1')->join('sos_team_members as t2', 't1.id', '=', 't2.team_id')
                            ->where('t1.resort_id', $resort_id)
                            ->where('t2.emp_id', $member)
                            ->where('t2.role_id', $role)
                            ->where('t1.name', $request->team_name)
                            ->where('t1.id', '!=', $id)
                            ->exists();
                        if ($exists) {
                            $validator->errors()->add("team_member.$index", "This team member-role already exists for this resort.");
                        }
                    }
                }
            }
        });

        if($validator->fails())
        {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try
        {
            $SOSTeamExists = SOSTeamManagementModel::findOrFail($id);
            if ($SOSTeamExists) {
                SOSTeamManagementModel::where('id', $id)
                    ->update([
                        'name' => $request->team_name,
                        'description' => strip_tags($request->description),
                    ]);

                SOSTeamMemeberModel::where("team_id", $id)->delete();

                foreach($request->employee as $block) {
                    $roleId = $block['member_role'];
                    $members = $block['team_member'];
                    if (!is_array($members)) {
                        $members = [$members];
                    }
                    foreach ($members as $memberId) {
                        SOSTeamMemeberModel::create([
                            'resort_id' => $resort_id,
                            'team_id' => $id,
                            'emp_id' => $memberId,
                            'role_id' => $roleId
                        ]);
                    }
                }
            }
            
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'SOS Team Updated Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json([
                'error' => 'Failed to update SOS Team',
                'message' => $e->getMessage()
            ], 500);
        }

    }
    public function SOSEmergencyTypeStore(Request $request)
    {
        $resort_id = $this->resort->resort_id;

        $validator = Validator::make($request->all(), [
            'emergency_name' => [
                'required',
                'max:50',
                Rule::unique('sos_emergency_types', 'name')->where(function ($query) use ($resort_id) {
                    return $query->where('resort_id', $resort_id);
                })
            ],
            'description' => ['required', 'string'],
            'assign_default_team' => 'required|array|min:1',
            'assign_default_team.*' => 'required|integer|exists:sos_teams,id',
        ], [
            'emergency_name.required' => 'The Emergency Type field is required.',
            'assign_default_team.required' => 'Please select at least one team.',
            'assign_default_team.*.required' => 'Please select at least one team.',
            'description.required' => 'The Description field is required.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
    
        DB::beginTransaction();
        try
        {
            $customFields = [];
            if ($request->has('custom_field_names') && $request->has('custom_field_values')) {
                foreach ($request->input('custom_field_names') as $index => $name) {
                    if (!empty($name)) {
                        $customFields[] = [
                            'name' => $name,
                            'value' => $request->input('custom_field_values')[$index],
                        ];
                    }
                }
            }

            // Store team IDs as JSON array
            $insert = SOSEmergencyTypesModel::create([
                'resort_id' => $resort_id,
                'name' => $request->emergency_name,
                'description' => strip_tags($request->description),
                'custom_fields' => json_encode($customFields)
            ]);

            // Insert into sos_child_emergency_types for each selected team
            if ($insert && !empty($request->assign_default_team)) {
                foreach ($request->assign_default_team as $teamId) {
                    SOSChildEmergencyType::create([
                        'emergency_id' => $insert->id,
                        'team_id' => $teamId,
                    ]);
                }
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'SOS Emergency Type Create Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Create SOS Emergency Type'], 500);
        }
    }

    public function IndexSOSEmergencyTypes(Request $request)
    {
        $page_title = "Emergency Types";

        $query = SOSEmergencyTypesModel::with(['assignedTeams.team'])->where('resort_id', $this->resort->resort_id);
        if ($request->searchTerm) {
            $searchTerm = $request->searchTerm;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('description', 'LIKE', "%{$searchTerm}%")
                    ->orWhereHas('assignedTeams.team', function ($teamQuery) use ($searchTerm) {
                        $teamQuery->where('name', 'LIKE', "%{$searchTerm}%");
                    });
            });
        }

        $SOSEmergencyTypes = $query->get();

        if ($request->ajax()) {
            return datatables()->of($SOSEmergencyTypes)
                ->addColumn('assigned_default_team', function ($row) {
                    // Get all assigned team names as comma separated
                    $teamNames = $row->assignedTeams->map(function ($assignedTeam) {
                        return $assignedTeam->team ? $assignedTeam->team->name : '';
                    })->filter()->toArray();
                    return implode(', ', $teamNames);
                })
                ->addColumn('Action', function ($row) {
                    $id = base64_encode($row->id);
                    $custom_fields = json_decode($row->custom_fields, true) ?? [];

                    return '
                    <div  class="d-flex align-items-center">
                        <a href="#editEmergencyType-modal" data-bs-toggle="modal" class="btn-lg-icon icon-bg-green me-1 edit-emergency-btn" 
                        data-name ="' . $row->name . '"  
                        data-id="' . e($id) . '"
                        data-description="' . $row->description . '"
                        data-assigned_default_team="' . htmlspecialchars(json_encode($row->assignedTeams->pluck('team_id')->toArray()), ENT_QUOTES, 'UTF-8') . '"
                        data-custom_fields="' . e(json_encode($custom_fields)) . '"
                        >
                            <img src="' . asset("resorts_assets/images/edit.svg") . '" alt="Edit" class="img-fluid">
                        </a>
                        <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-id="' . e($id) . '">
                            <img src="' . asset("resorts_assets/images/trash-red.svg") . '" alt="Delete" class="img-fluid">
                        </a>
                    </div>';
                })
                ->rawColumns(['Action'])
                ->make(true);
        }

        $allTeams = SOSTeamManagementModel::where("resort_id", $this->resort->resort_id)->get();

        return view('resorts.SOS.configuration.IndexEmergencyTypes', compact('SOSEmergencyTypes', 'allTeams', 'page_title'));
    }

    public function SOSEmergencyTypesDestory($id)
    {   
        $id = base64_decode($id);
        DB::beginTransaction();
        try
        {
            SOSEmergencyTypesModel::where("id",$id)->delete();
            
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'SOS Emergency Type Delete Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Delete SOS Emergency Type'], 500);
        }
    }

    public function updateEmergencyTypes(Request $request, $id)
    {   
        $id = base64_decode($id);
        $resort_id = $this->resort->resort_id;
        
        $validator = Validator::make($request->all(), [
            'emergency_name' => [
                'required',
                'max:50',
                Rule::unique('sos_emergency_types', 'name')->where(function ($query) use ($resort_id) {
                    return $query->where('resort_id', $resort_id);
                })->ignore($id),
            ],
            'description' => ['required', 'string'],
            'assign_default_team' => 'required|array|min:1',
            'assign_default_team.*' => 'required|integer|exists:sos_teams,id',
        ], [
            'emergency_name.required' => 'The Emergency Type field is required.',
            'assign_default_team.required' => 'Please select at least one team.',
            'assign_default_team.*.required' => 'Please select at least one team.',
            'description.required' => 'The Description field is required.',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try
        {
            $SOSEmergencyExists = SOSEmergencyTypesModel::findOrFail($id);
            if ($SOSEmergencyExists) {
                $customFields = [];
                if ($request->has('custom_field_names') && $request->has('custom_field_values')) {
                    foreach ($request->input('custom_field_names') as $index => $name) {
                        if (!empty($name)) {
                            $customFields[] = [
                                'name' => $name,
                                'value' => $request->input('custom_field_values')[$index],
                            ];
                        }
                    }
                }
                SOSEmergencyTypesModel::where('id', $id)
                ->update([
                    'name' => $request->emergency_name,
                    'description' => strip_tags($request->description),
                    'custom_fields' => json_encode($customFields)
                ]);

                // Remove old assigned teams
                SOSChildEmergencyType::where('emergency_id', $id)->delete();

                // Insert new assigned teams
                if (!empty($request->assign_default_team)) {
                    foreach ($request->assign_default_team as $teamId) {
                        SOSChildEmergencyType::create([
                            'emergency_id' => $id,
                            'team_id' => $teamId,
                        ]);
                    }
                }
            }
            
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'SOS Emergency Type Updated Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Update SOS Emergency Type'], 500);
        }
    }

}

