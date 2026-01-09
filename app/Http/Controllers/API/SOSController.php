<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\SOSEmergencyTypesModel;
use App\Models\SOSHistoryModel;
use App\Models\SosHistoryEmployeeStatus;
use App\Models\SOSTeamManagementModel;
use App\Models\SOSTeamMemeberModel;
use App\Models\ChildSosHistory;
use App\Models\SosTeamMemberActivity;
use App\Models\Employee;
use App\Models\ChildSOSHistoryStatus;
use App\Models\SOSRolesAndPermission;
use App\Models\SOSChildEmergencyType;
use Illuminate\Support\Facades\Http;
use App\Helpers\Common;
use GuzzleHttp\Client;
use Carbon\Carbon;
use Validator;
use DB;

class SOSController extends Controller
{
    protected $user;
    protected $resort_id;
    protected $underEmp_id = [];

    public function __construct()
    {
        if (Auth::guard('api')->check()) {
            $this->user                                 =   Auth::guard('api')->user();
            $this->resort_id                            =   $this->user->resort_id;
            $this->reporting_to                         =   $this->user->GetEmployee->id;
            $this->underEmp_id                          =   Common::getSubordinates($this->reporting_to);
        }
    }

    public function getEmergencyTypes()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $SOSEmergencyTypesModel                      =   SOSEmergencyTypesModel::where('resort_id',$this->resort_id)
                                                                ->get();

            if (!$SOSEmergencyTypesModel) {
                return response()->json(['status' => false, 'message' => 'SOS Emergency Types not found'], 200);
            }

            return response()->json([
                'success'                               =>  true,
                'message'                               =>  "SOS types retrieved successfully.",
                'data'                                  =>  $SOSEmergencyTypesModel,
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }
    
    public function SOSStore(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        
        $validator = Validator::make($request->all(), [
            'emergency_id'                              =>  'required',
            'location'                                  =>  'required',
            'latitude'                                  =>  'required',
            'longitude'                                 =>  'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }
        
        // DB::beginTransaction();
        try {
            $SOSHistoryAdd                              =   SOSHistoryModel::create([
                'resort_id'                             =>  $this->resort_id,
                'emergency_id'                          =>  $request->emergency_id,
                'emp_initiated_by'                      =>  $this->user->GetEmployee->id,
                'location'                              =>  $request->location,
                'latitude'                              =>  $request->latitude,
                'longitude'                             =>  $request->longitude,
                'status'                                =>  'Pending',
                'date'                                  =>  Carbon::now()->format('Y-m-d'),
                'time'                                  =>  Carbon::now()->format('H:i:s'),
                'emergency_description'                 =>  $request->emergency_description,
            ]);

            $smEmployee                                 =   Employee::join('resort_positions as rp', 'employees.Position_id', '=', 'rp.id')
                                                                ->where('employees.resort_id', $this->resort_id)
                                                                ->where('employees.rank',4)
                                                                ->where('employees.status', 'Active')
                                                                ->where('rp.position_title', 'Security Manager')
                                                                ->select('employees.id','employees.Admin_Parent_id','employees.Emp_id','employees.Position_id','employees.device_token')
                                                                ->first()
                                                                ->toArray();

            $deviceToken                                =   $smEmployee['device_token'];
            $title                                      =   "SOS Alert";
            $body                                       =   "SOS Alert!\n"
                                                            . "Name: " . $this->user->first_name . ' ' . $this->user->last_name . "\n"
                                                            . "Date: " . Carbon::now()->format('d M Y') . "\n"
                                                            . "Time: " . Carbon::now()->format('h:i A') . "\n"
                                                            . "Location: " . $request->location . "\n"
                                                            . "Please respond immediately!";

            $moduleName                                 =   'SOS';
            $sound                                      =   'siren_sound';
            $custom_sound_channel                       =   'custom_sound_channel';
            $sosPushNotification                        =   Common::sendPushNotifictionForMobile([$smEmployee['device_token']], $title, $body, $moduleName,'Pending',$sound,$custom_sound_channel,NULL);

            $sosNotification                            =   Common::sendMobileNotification($this->resort_id,2,null,null,$title,$body,$moduleName,[$smEmployee['id']],null);

            // DB::commit();
            if (!$SOSHistoryAdd) {
                return response()->json(['status' => false, 'message' => 'SOS not added'], 200);
            }
            return response()->json([
                'success'                               =>  true,
                'message'                               =>  "SOS Add Successfully.",
                'data'                                  =>  $SOSHistoryAdd,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        } 
    }

    public function handleSOSActionWithTeam(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'action'                                    => 'required|in:Drill-Active,Active,Rejected,Drill-Rejected',
            'team_id'                                   => 'required_if:action,Active,Drill-Active|array|nullable', // team_id is required and must be an array if action is Active
            'sos_id'                                    => 'required',
            'team_message'                              => 'required_if:action,Active,Drill-Active|nullable|string', // team_message is required only if action is Active
            'rejected_message'                          => 'required_if:action,Rejected,Drill-Rejected|nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }
        $title                                          =   "SOS Alert";
        $moduleName                                     =   'SOS';
        $sound                                          =   'siren_sound';
        $custom_sound_channel                           =   'custom_sound_channel';
        $employee_id                                    =   $this->user->GetEmployee->id;
    
        // DB::beginTransaction();
        try {
            $sosExist                                   =   SOSHistoryModel::where('id',$request->sos_id)
                                                                ->where('resort_id', $this->resort_id)
                                                                ->first();
            // Check if SOS exists
            if (!$sosExist) {
                return response()->json(['success' => false, 'message' => 'SOS Not Exist'], 200);
            }

            $sosHistory                                 =   SOSHistoryModel::where('id',$request->sos_id)
                                                                ->where('resort_id', $this->resort_id)
                                                                ->whereIn('status', ['Pending','Drill-Active','Real-Active'])
                                                                ->where('sos_approved_by', null)
                                                                ->first();                          
            if (!$sosHistory) {
                return response()->json(['success' => false, 'message' => 'SOS Already '.$request->action], 200);
            }

            // Status update
            $statusMap = [
                'Active'                                =>  'Active',
                'Drill-Active'                          =>  'Drill-Active',
                'Rejected'                              =>  'Rejected',
                'Drill-Rejected'                        =>  'Drill-Rejected'
            ];

            $sosHistory->status                         =   $statusMap[$request->action];
            $sosHistory->sos_approved_by                =   $employee_id;
            $sosHistory->sos_approved_time              =   Carbon::now();
            $sosHistory->sos_approved_date              =   Carbon::now()->format('Y-m-d');
            $sosHistory->employee_message               =   $request->employee_message ?? null;
            $sosHistory->team_message                   =   $request->team_message ?? null;
            $sosHistory->rejected_message               =   $request->rejected_message ?? null;
            $sosHistory->save();
            
            // Get initiator details
            $empInitiatedDeviceToken                    =   Employee::where('resort_id', $this->resort_id)
                                                                ->where('status', 'Active')
                                                                ->where('id', $sosHistory->emp_initiated_by)
                                                                ->first();

            $empName                                    =   Employee::join('resort_admins as ra', 'ra.id', '=', 'employees.Admin_Parent_id')
                                                                ->where('employees.resort_id', $this->resort_id)
                                                                ->where('employees.id', $employee_id)
                                                                ->select('ra.first_name', 'ra.last_name')
                                                                ->first();

            // Handle Rejected or Drill-Rejected
            if (in_array($request->action, ['Rejected', 'Drill-Rejected'])) {
                $sosStatus                              = 'Rejected';
                $body                                   = "{$empName->first_name} {$empName->last_name} SOS {$sosStatus}";
                Common::sendPushNotifictionForMobile([$empInitiatedDeviceToken['device_token']], $title, $body, $moduleName, $sosStatus, null, null,NULL);
                Common::sendMobileNotification($this->resort_id,2,null, null, $title, $body, $moduleName, [$empInitiatedDeviceToken['id']], null);
                return response()->json([
                    'success'                           =>  true, 
                    'message'                           =>  "SOS {$request->action} successfully.", 
                    'data'                              =>  $sosHistory
                ], 200);
            }

            // Handle Active / Drill-Active
            $sosStatus                                  =   'Active';
            $body                                       =   "SOS {$sosStatus} : Fire. Please proceed to the nearest assembly point";

            ChildSOSHistoryStatus::insert([
                ['sos_history_id' => $sosHistory->id, 'sos_status' => 'sos_activation'],
                ['sos_history_id' => $sosHistory->id, 'sos_status' => 'manager_acknowledgement']
            ]);

            // Insert SosHistoryEmployeeStatus in bulk
            $employees                                  =   Employee::where('resort_id', $this->resort_id)
                                                                ->where('status', 'Active')
                                                                ->select('id', 'latitude', 'longitude', 'device_token')
                                                                ->get();

            $now                                        =   now();
            $statusInsertData                           =   $employees->map(function ($emp) use ($sosHistory, $now) {
                return [
                    'latitude'                          =>  $emp->latitude,
                    'longitude'                         =>  $emp->longitude,
                    'sos_history_id'                    =>  $sosHistory->id,
                    'emp_id'                            =>  $emp->id,
                    'status'                            =>  'Unknown',
                    'created_at'                        =>  $now,
                    'updated_at'                        =>  $now,
                ];
            })->toArray();

            DB::table('sos_history_employee_status')->insert($statusInsertData);


            $getTeamMemeber                             =   SOSTeamMemeberModel::join('resort_admins as ra', 'ra.id', '=', 'sos_team_members.emp_id')
                                                                ->join('employees as e', 'e.Admin_Parent_id', '=', 'ra.id')
                                                                ->whereIn('sos_team_members.team_id', $request->team_id)
                                                                ->where('sos_team_members.resort_id', $this->resort_id)
                                                                ->select('e.device_token', 'e.id','e.Emp_id','sos_team_members.team_id', 'ra.id as admin_id')
                                                                ->get();

            $teamHistoryInsertData                      =   [];
            $memberActivityData                         =   [];

            foreach ($request->team_id as $teamId) {
                // child sos history
                $teamHistoryInsertData[]                =   [
                    'sos_history_id'                    =>  $request->sos_id,
                    'team_id'                           =>  $teamId,
                    'created_at'                        =>  now(),
                    'updated_at'                        =>  now(),
                ];
                
                // log each team member activity
                $members                                =   $getTeamMemeber->where('team_id', $teamId);

                foreach ($members as $member) {

                    $memberActivityData[]               =   [
                        'sos_history_id'                =>  $request->sos_id,
                        'team_id'                       =>  $teamId,
                        'emp_id'                        =>  $member->admin_id,
                        'status'                        =>  'Unacknowledged',
                    ];
                }
            }

            // Bulk insert
            ChildSosHistory::insert($teamHistoryInsertData);
            SosTeamMemberActivity::insert($memberActivityData);

            // extract device tokens & employee IDs
            $deviceTokens                               =   $getTeamMemeber->pluck('device_token')->unique()->filter()->values()->toArray();
            $empIds                                     =   $getTeamMemeber->pluck('id')->unique()->values()->toArray();

            //Send in app and push notification to the team member
            Common::sendPushNotifictionForMobile($deviceTokens, $title, $request->team_message, $moduleName,$sosStatus,$sound,$custom_sound_channel,NULL);
            Common::sendMobileNotification($this->resort_id,2,null,null, $title,$request->team_message,$moduleName,$empIds,null);

            //Send in app and push notification to the who initiated the SOS
            Common::sendPushNotifictionForMobile([$empInitiatedDeviceToken['device_token']], $title, $body, $moduleName,'Active',$sound,$custom_sound_channel,NULL);
            Common::sendMobileNotification($this->resort_id,2,null,null,$title, $body,$moduleName,[$empInitiatedDeviceToken['id']],null);

            //Send push notification to the employee same resort
            $allEmpDeviceId                             =   Employee::where('resort_id',$this->resort_id)->where('status','Active')
                                                                ->where('device_token','!=',null)
                                                                ->where('device_token','!=','')
                                                                ->where('id','!=',$employee_id)
                                                                ->pluck('device_token');

            $allEmpId                                   =   Employee::where('resort_id',$this->resort_id)
                                                                ->where('status','Active')
                                                                ->where('id','!=',$employee_id)
                                                                ->pluck('id');

            Common::sendPushNotifictionForMobile($allEmpDeviceId->toArray(), $title, $request->employee_message ?? 'Please help us, SOS Alert has been raised.', $moduleName,'Active',$sound,$custom_sound_channel,NULL);
            Common::sendMobileNotification($this->resort_id,2,null,null,$title, $request->employee_message ?? 'Please help us, SOS Alert has been raised.',$moduleName,$allEmpId,null);

            ChildSOSHistoryStatus::create([
                'sos_history_id'                        =>  $sosHistory->id,
                'sos_status'                            =>  'team_notifications_sent',
            ]);

            // DB::commit();
            return response()->json([
                'success'                               =>  true,
                'message'                               =>  "SOS {$request->action} successfully.",
                'data'                                  =>  $sosHistory,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function SOSTeamListing()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $employee_id                                    =  $this->user->GetEmployee->id;
        try {
            $sosTeamData                                =   SOSTeamManagementModel::where('resort_id', $this->resort_id)->get();

            if ($sosTeamData->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'SOS team not found'], 200);
            }
            return response()->json([
                'success'                               =>  true,
                'message'                               =>  "SOS team listing fetched successfully.",
                'data'                                  =>  $sosTeamData,
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function SOSSafeStatus(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator                                      =   Validator::make($request->all(), [
            'sos_history_id'                            =>  'required',
            'status'                                    =>  'required|in:Safe,Unsafe',
            'address'                                   =>  'required',
            'latitude'                                  =>  'required',
            'longitude'                                 =>  'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }
        try {
            $sosHistoryEmployeeStatus                   =   SosHistoryEmployeeStatus::where('sos_history_id', $request->sos_history_id)
                                                                ->where('emp_id', $this->user->GetEmployee->id)
                                                                ->first();

            if (!$sosHistoryEmployeeStatus) {
                return response()->json(['success' => false, 'message' => 'SOS not found'], 200);
            }

            $sosHistoryEmployeeStatus->status           =   $request->status;
            $sosHistoryEmployeeStatus->address          =   $request->address;
            $sosHistoryEmployeeStatus->latitude         =   $request->latitude;
            $sosHistoryEmployeeStatus->longitude        =   $request->longitude;
            $sosHistoryEmployeeStatus->save();

            return response()->json([
                'success'                               =>  true,
                'message'                               =>  "You are marked as {$request->status}. SOS alert successfully updated",
                'data'                                  =>  $sosHistoryEmployeeStatus,
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function employeeAndTeamLocation($sosId)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $sosId                                          =   base64_decode($sosId);
        $employee                                       =   $this->user->GetEmployee;
        $rank                                           =   config('settings.Position_Rank');
        $current_rank                                   =   $employee->rank ?? null;
        $available_rank                                 =   $rank[$current_rank] ?? '';
        $isHOD                                          =   ($available_rank === "HOD");

        try {
            $SOSHistoryModel                            =   SOSHistoryModel::where('id', $sosId)
                                                                ->whereIn('sos_history.status',['Active','Drill-Active','In-Progress'])
                                                                ->first();


            if (!$SOSHistoryModel) {
                return response()->json([
                    'success'                           =>  false,
                    'message'                           =>  'No employee location found'
                ], 200);
            }

            $sosHistoryEmployeeStatus                   =   SosHistoryEmployeeStatus::join('employees as e', 'sos_history_employee_status.emp_id', '=', 'e.id')
                                                                ->join('resort_admins as ra', 'e.Admin_Parent_id', '=', 'ra.id')
                                                                ->where('sos_history_employee_status.sos_history_id', $sosId)
                                                                // ->where('sos_history_employee_status.status', '!=', 'Unknown')
                                                                ->select(
                                                                    'sos_history_employee_status.id',
                                                                    'sos_history_employee_status.sos_history_id',
                                                                    'sos_history_employee_status.emp_id',
                                                                    'sos_history_employee_status.status',
                                                                    'sos_history_employee_status.address',
                                                                    'sos_history_employee_status.latitude',
                                                                    'sos_history_employee_status.longitude',
                                                                    'ra.first_name',
                                                                    'ra.last_name',
                                                                    'ra.profile_picture',
                                                                    'e.Admin_Parent_id',
                                                                );
                                                                if($isHOD) {
                                                                    $sosHistoryEmployeeStatus->whereIn('e.id', $this->underEmp_id);
                                                                }

            $sosHistoryEmployeeStatus                   =   $sosHistoryEmployeeStatus->get()->map(function ($item) {
                                                                    $item->profile_picture = Common::getResortUserPicture($item->Admin_Parent_id);
                                                                     $item->type = 'employee'; 
                                                                    return $item;
                                                                });

            $sosTeamMemberActivity                      =   SosTeamMemberActivity::join('resort_admins as ra', 'sos_team_member_activity.emp_id', '=', 'ra.id')
                                                                ->join('sos_teams as st', 'sos_team_member_activity.team_id', '=', 'st.id')
                                                                ->where('sos_team_member_activity.sos_history_id', $sosId)
                                                                ->where('sos_team_member_activity.status','!=', 'Unacknowledged')
                                                                ->select(
                                                                    // 'sos_team_member_activity.*',
                                                                    'sos_team_member_activity.id as team_member_id',
                                                                    'sos_team_member_activity.emp_id', 
                                                                    'sos_team_member_activity.status',
                                                                    'sos_team_member_activity.address',
                                                                    'sos_team_member_activity.latitude',
                                                                    'sos_team_member_activity.longitude',
                                                                    'sos_team_member_activity.team_id',
                                                                    'sos_team_member_activity.sos_history_id',
                                                                    'ra.id as resort_admin_id',
                                                                    'ra.first_name',
                                                                    'ra.last_name',
                                                                    'ra.profile_picture',
                                                                    'st.name as team_name',
                                                                )
                                                                ->get()->map(function ($item) {
                                                                    $item->profile_picture = Common::getResortUserPicture($item->resort_admin_id);
                                                                    $item->type = 'team_member'; 
                                                                    return $item;
                                                                });

            // Merge both collections into one
            $merged = $sosHistoryEmployeeStatus->merge($sosTeamMemberActivity)->values();

            return response()->json([
                'success'                               =>  true,
                'message'                               =>  'SOS Security Staff Fetched successfully.',
                // 'data'                                  =>  $SOSHistoryModel,
                'data'                                  =>  $merged,
            ], 200);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function SOSDetails($sosId)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $sosId                                          =   base64_decode($sosId);
        try {
            $sosData                                    =   SOSHistoryModel::join('sos_team_member_activity as stma', 'sos_history.id', '=', 'stma.sos_history_id')
                                                                ->join('sos_emergency_types as set', 'sos_history.emergency_id', '=', 'set.id')
                                                                ->where('sos_history.id', $sosId)
                                                                ->where('stma.emp_id', $this->user->id)
                                                                ->whereIn('sos_history.status',['Active','Drill-Active','In-Progress'])
                                                                ->select('sos_history.*','stma.status as team_member_status', 'stma.address as team_member_address', 'stma.latitude as team_member_latitude', 'stma.longitude as team_member_longitude','stma.id as team_member_id','stma.emp_id as team_member_emp_id','set.name as emergency_name')
                                                                ->first();
            if (!$sosData) {
                return response()->json([
                    'success'                           =>  false,
                    'message'                           =>  'SOS Details not found.',
                     'data'                             =>  (object)[],
                ], 200);
            }

            $sosHistoryEmployees                        =   SosHistoryEmployeeStatus::join('employees as e', 'sos_history_employee_status.emp_id', '=', 'e.id')
                                                                ->join('resort_admins as ra', 'e.Admin_Parent_id', '=', 'ra.id')
                                                                ->where('sos_history_employee_status.sos_history_id', $sosId)
                                                                ->select(
                                                                    'sos_history_employee_status.id',
                                                                    'sos_history_employee_status.sos_history_id',
                                                                    'sos_history_employee_status.emp_id',
                                                                    'sos_history_employee_status.status',
                                                                    'sos_history_employee_status.latitude',
                                                                    'sos_history_employee_status.longitude',
                                                                )
                                                                ->get();

            
            if($sosHistoryEmployees){
                $sosData->sos_history_employee          =   $sosHistoryEmployees;
            }

            $teamMemberStats                            =   SosTeamMemberActivity::where('sos_history_id', $sosId)
                                                                ->selectRaw("
                                                                    COUNT(*) as total,
                                                                    SUM(CASE WHEN status = 'Acknowledged' THEN 1 ELSE 0 END) as acknowledged
                                                                ")->first();

            $sosData->sos_team_member_activity          =   SosTeamMemberActivity::join('resort_admins as ra', 'sos_team_member_activity.emp_id', '=', 'ra.id')
                                                                ->where('sos_team_member_activity.sos_history_id', $sosId)
                                                                ->join('sos_teams as st', 'sos_team_member_activity.team_id', '=', 'st.id')
                                                                ->select(
                                                                    'sos_team_member_activity.*',
                                                                    'ra.first_name',
                                                                    'ra.last_name',
                                                                    'ra.profile_picture',
                                                                    'ra.id as resort_admin_id',
                                                                    'st.name as team_name',
                                                                )
                                                                ->get()->map(function ($item) {
                                                                    $item->profile_picture = Common::getResortUserPicture($item->resort_admin_id);
                                                                    return $item;
                                                                });

            $sosData->sos_team_member_total             =   (int) ($teamMemberStats->total ?? 0);
            $sosData->sos_team_member_acknowledged      =   (int) ($teamMemberStats->acknowledged ?? 0);

            return response()->json([
                'success'                               =>  true,
                'message'                               =>  'SOS Details fetched successfully.',
                'data'                                  =>  $sosData,
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function SOSAcknowledge(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator                                      =   Validator::make($request->all(), [
            'sos_history_id'                            =>  'required',
            'team_member_id'                            =>  'required',
            'status'                                    =>  'required',
            'address'                                   =>  'required',
            'latitude'                                  =>  'required',
            'longitude'                                 =>  'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }
        try {
            $sosAcknowledged                            =   SosTeamMemberActivity::where('sos_history_id', $request->sos_history_id)
                                                            ->where('emp_id', $this->user->id)
                                                            ->where('id', $request->team_member_id)
                                                            ->where('status', 'Unacknowledged')
                                                            ->first();
            if (!$sosAcknowledged) {
                return response()->json(['success' => false, 'message' => 'SOS Already Acknowledged'], 200);
            }

            $sosAcknowledged->status                    =   'Acknowledged';
            $sosAcknowledged->address                   =   $request->address;
            $sosAcknowledged->latitude                  =   $request->latitude;
            $sosAcknowledged->longitude                 =   $request->longitude;
            $sosAcknowledged->save();

            ChildSOSHistoryStatus::create([
                'sos_history_id'                        =>  $request->sos_history_id,
                'sos_status'                            =>  'acknowledgements_received_from_team_members',
            ]);

            SOSHistoryModel::where('id', $request->sos_history_id)->update(['status' => 'In-Progress']);

            return response()->json([
                'success'                               =>  true,
                'message'                               =>  "SOS acknowledged successfully.",
                'data'                                  =>  $sosAcknowledged,
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function SOSHistoryListing()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $sosHistoryData                             =   SOSHistoryModel::join('sos_emergency_types as set', 'sos_history.emergency_id', '=', 'set.id')
                                                                ->join('employees as e', 'sos_history.emp_initiated_by', '=', 'e.id')
                                                                ->join('resort_admins as ra', 'e.Admin_Parent_id', '=', 'ra.id')
                                                                ->where('sos_history.resort_id', $this->resort_id)
                                                                ->whereIn('sos_history.status', ['Completed', 'Rejected', 'Drill-Completed', 'Drill-Rejected'])
                                                                ->orderBy('sos_history.created_at', 'desc')
                                                                ->select(
                                                                    'sos_history.*',
                                                                    'set.name as emergency_name',
                                                                    'ra.first_name',
                                                                    'ra.last_name',
                                                                    'ra.profile_picture'
                                                                )
                                                                ->get()->map(function ($item) {
                                                                    $item->profile_picture = Common::getResortUserPicture($item->Admin_Parent_id);
                                                                    return $item;
                                                                });

            if ($sosHistoryData->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'SOS history not found'], 200);
            }

            return response()->json([
                'success'                               =>  true,
                'message'                               =>  "SOS history fetched successfully.",
                'data'                                  =>  $sosHistoryData,
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function SOSHistoryDetails($sosId)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $sosId                                          =   base64_decode($sosId);
        try {
            $sosHistoryData                             =   SOSHistoryModel::join('sos_emergency_types as set', 'sos_history.emergency_id', '=', 'set.id')
                                                                ->join('employees as e', 'sos_history.emp_initiated_by', '=', 'e.id')
                                                                ->join('resort_admins as ra', 'e.Admin_Parent_id', '=', 'ra.id')
                                                                ->where('sos_history.resort_id', $this->resort_id)
                                                                ->select(
                                                                    'sos_history.*',
                                                                    'set.name as emergency_name',
                                                                    'ra.first_name',
                                                                    'ra.last_name',
                                                                    'ra.profile_picture',
                                                                    'e.Admin_Parent_id',
                                                                )
                                                                ->where('sos_history.id', $sosId)
                                                                ->first();
            if (!$sosHistoryData) {
                return response()->json(['success' => false, 'message' => 'SOS history not found'], 200);
            }

            $sosHistoryData->sos_approved_by_name       =   Employee::join('resort_admins as ra', 'employees.Admin_Parent_id', '=', 'ra.id')
                                                                ->where('employees.id', $sosHistoryData->sos_approved_by)
                                                                ->select('employees.id','employees.Admin_Parent_id', 'ra.first_name', 'ra.last_name', 'ra.profile_picture')
                                                                ->first();

            $sosHistoryData->sos_approved_by_name->profile_picture =   Common::getResortUserPicture( $sosHistoryData->sos_approved_by_name->Admin_Parent_id);
            $sosHistoryData->profile_picture            =   Common::getResortUserPicture($sosHistoryData->Admin_Parent_id);

            // Fetch team member activity stats
            $teamMemberStats                            =   SosTeamMemberActivity::where('sos_history_id', $sosId)
                                                                ->selectRaw("
                                                                    COUNT(*) as total,
                                                                    SUM(CASE WHEN status = 'Acknowledged' THEN 1 ELSE 0 END) as acknowledged
                                                                ")->first();

            $sosHistoryData->sos_team_member_activity   =   SosTeamMemberActivity::join('resort_admins as ra', 'sos_team_member_activity.emp_id', '=', 'ra.id')
                                                                ->where('sos_team_member_activity.sos_history_id', $sosId)
                                                                ->join('sos_teams as st', 'sos_team_member_activity.team_id', '=', 'st.id')
                                                                ->select(
                                                                    'sos_team_member_activity.*',
                                                                    'ra.first_name',
                                                                    'ra.last_name',
                                                                    'ra.profile_picture',
                                                                    'ra.id as resort_admin_id',
                                                                    'st.name as team_name',
                                                                )
                                                                ->get()->map(function ($item) {
                                                                    $item->team_member_division    =   Employee::join('resort_divisions as rd', 'rd.id', '=', 'employees.division_id')
                                                                                                            ->where('Admin_Parent_id', $item->resort_admin_id)
                                                                                                            ->select('rd.name as division_name', 'employees.rank')
                                                                                                            ->first();

                                                                    if ($item->team_member_division) {
                                                                        $empRank        =   $item->team_member_division->rank ?? null;
                                                                        $rankConfig     =   config('settings.Position_Rank');
                                                                        $rankType       =   array_key_exists($empRank, $rankConfig) ? $rankConfig[$empRank] : null;
                                                                        $item->team_member_division->rank_type  =   $rankType;
                                                                    } else {
                                                                        $item->team_member_division = null;
                                                                    }

                                                                    $item->profile_picture = Common::getResortUserPicture($item->resort_admin_id);
                                                                    return $item;
                                                                });

            $sosHistoryData->sos_team_total_count       =   (int) ($teamMemberStats->total ?? 0);
            $sosHistoryData->sos_team_acknowledged_count =   (int) ($teamMemberStats->acknowledged ?? 0);

            $sosHistory = ChildSOSHistoryStatus::where('sos_history_id',$sosId)->get();

            if (!$sosHistory) {
                return response()->json(['success' => false, 'message' => 'SOS history not found'], 200);
            }


            $displayedStatuses                          = ['data' => []];

            foreach($sosHistory as $m)
            {
                $dateTime                               =   Carbon::parse($m->created_at);
                $date                                   =   $dateTime->format('Y-m-d');
                $time                                   =   $dateTime->format('H:i:s');

                if(!in_array($m->sos_status, $displayedStatuses))
                {
                    $displayedStatuses['data'][]        =   [
                        'sos_status'                    =>  $m->sos_status,
                        'date'                          =>  $date,
                        'time'                          =>  $time
                    ];
                }
            }

            $sosHistoryDataArr                          = [
                'sos_history'                           => $sosHistoryData,
                'timeline'                              => $displayedStatuses,
            ];
            
            if (empty($sosHistoryDataArr)) {
                return response()->json(['success' => false, 'message' => 'SOS history not found'], 200);
            }

            return response()->json([
                'success'                               =>  true,
                'message'                               =>  "SOS history fetched successfully.",
                'data'                                  =>  $sosHistoryDataArr,
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function getAnySOSEmergency()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $currentDate                                =   Carbon::today()->format('Y-m-d');
            $SOSEmergencyTypesModel                     =   SOSHistoryModel::join('employees as e', 'sos_history.emp_initiated_by', '=', 'e.id')
                                                                ->join('resort_admins as ra', 'e.Admin_Parent_id', '=', 'ra.id')
                                                                ->join('resort_positions as rp', 'e.position_id', '=', 'rp.id')
                                                                ->join('sos_emergency_types as set', 'sos_history.emergency_id', '=', 'set.id')
                                                                ->where('sos_history.resort_id', $this->resort_id)
                                                                ->whereDate('sos_history.date', $currentDate)
                                                                ->whereNotIn('sos_history.status', ['Completed','Rejected','Drill-Rejected','Drill-Completed'])
                                                                ->select('sos_history.*', 'set.name as emergency_name', 'ra.first_name', 'ra.last_name', 'ra.profile_picture','rp.position_title', 'e.Admin_Parent_id')
                                                                ->orderBy('created_at', 'ASC')->first();
            if (!$SOSEmergencyTypesModel) {
                return response()->json(['success' => false, 'message' => 'SOS not found'], 200);
            }

            $SOSEmployee                                =   SOSTeamMemeberModel::join('sos_team_member_activity as stma', 'sos_team_members.team_id', '=', 'stma.team_id')
                                                                ->where('sos_team_members.resort_id', $this->resort_id)
                                                                ->where('sos_team_members.emp_id',$this->user->id)
                                                                ->where('stma.emp_id',$this->user->id)
                                                                ->where('stma.sos_history_id', $SOSEmergencyTypesModel->id)
                                                                ->first();
                                                                
            if($SOSEmployee){

                $SOSEmployee->role_assigned             =   SOSRolesAndPermission::where('sos_role_management.resort_id', $this->resort_id)
                                                                ->where('id', $SOSEmployee->role_id)
                                                                ->first();
                if ($SOSEmployee->role_assigned) {
                    // Convert permissions to array
                    $SOSEmployee->role_assigned->permission         = explode(',', $SOSEmployee->role_assigned->permission);

                    // Map permission names
                    $SOSEmployee->role_assigned->permission_names   = collect($SOSEmployee->role_assigned->permission)
                        ->filter(function ($id) {
                            return !empty($id) && isset(config('settings.sosAssignPermissions')[$id]);
                        })
                        ->map(function ($id) {
                            return config('settings.sosAssignPermissions')[$id];
                        })
                        ->values()
                        ->toArray();
                } 

            }

            $SOSEmergencyTypesModel->profile_picture    =   Common::getResortUserPicture($SOSEmergencyTypesModel->Admin_Parent_id);
            $SOSEmergencyTypesModel->employee_status    =   SosHistoryEmployeeStatus::where('emp_id', $this->user->GetEmployee->id)->where('sos_history_id', $SOSEmergencyTypesModel->id)->first();
            $SOSEmergencyTypesModel->sos_employee       =   $SOSEmployee ?? null;
            $SOSEmergencyTypesModel->sos_team_id        =   SOSChildEmergencyType::where('emergency_id',$SOSEmergencyTypesModel->emergency_id)->get();

            return response()->json([
                'success'                               =>  true,
                'message'                               =>  "SOS details fetched successfully.",
                'data'                                  =>  $SOSEmergencyTypesModel
            ]);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function getTeamAcknowledged($sosId)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        
        $sosId                                          =   base64_decode($sosId);

        try{
            $sosTeamMemberAcknowledged                  =   SosTeamMemberActivity::join('resort_admins as ra', 'sos_team_member_activity.emp_id', '=', 'ra.id')
                                                                    ->where('sos_team_member_activity.sos_history_id', $sosId)
                                                                    ->join('sos_teams as st', 'sos_team_member_activity.team_id', '=', 'st.id')
                                                                    ->where('sos_team_member_activity.status', 'Acknowledged')
                                                                    ->select(
                                                                        'sos_team_member_activity.*',
                                                                        'ra.first_name',
                                                                        'ra.last_name',
                                                                        'ra.profile_picture',
                                                                        'ra.id as resort_admin_id',
                                                                        'st.name as team_name',
                                                                    )
                                                                    ->get()->map(function ($item) {
                                                                         $item->team_member_division    =   Employee::join('resort_divisions as rd', 'rd.id', '=', 'employees.division_id')
                                                                                                            ->where('Admin_Parent_id', $item->resort_admin_id)
                                                                                                            ->select('rd.name as division_name', 'employees.rank')
                                                                                                            ->first();

                                                                    if ($item->team_member_division) {
                                                                        $empRank        =   $item->team_member_division->rank ?? null;
                                                                        $rankConfig     =   config('settings.Position_Rank');
                                                                        $rankType       =   array_key_exists($empRank, $rankConfig) ? $rankConfig[$empRank] : null;
                                                                        $item->team_member_division->rank_type  =   $rankType;
                                                                    } else {
                                                                        $item->team_member_division = null;
                                                                    }
                                                                        $item->profile_picture = Common::getResortUserPicture($item->resort_admin_id);
                                                                        return $item;
                                                                    });

            $sosTeamMemberUnacknowledged                =   SosTeamMemberActivity::join('resort_admins as ra', 'sos_team_member_activity.emp_id', '=', 'ra.id')
                                                                    ->where('sos_team_member_activity.sos_history_id', $sosId)
                                                                    ->join('sos_teams as st', 'sos_team_member_activity.team_id', '=', 'st.id')
                                                                    ->where('sos_team_member_activity.status', 'Unacknowledged')
                                                                    ->select(
                                                                        'sos_team_member_activity.*',
                                                                        'ra.first_name',
                                                                        'ra.last_name',
                                                                        'ra.profile_picture',
                                                                        'ra.id as resort_admin_id',
                                                                        'st.name as team_name',
                                                                    )
                                                                    ->get()->map(function ($item) {
                                                                         $item->team_member_division    =   Employee::join('resort_divisions as rd', 'rd.id', '=', 'employees.division_id')
                                                                                                            ->where('Admin_Parent_id', $item->resort_admin_id)
                                                                                                            ->select('rd.name as division_name', 'employees.rank')
                                                                                                            ->first();

                                                                    if ($item->team_member_division) {
                                                                        $empRank        =   $item->team_member_division->rank ?? null;
                                                                        $rankConfig     =   config('settings.Position_Rank');
                                                                        $rankType       =   array_key_exists($empRank, $rankConfig) ? $rankConfig[$empRank] : null;
                                                                        $item->team_member_division->rank_type  =   $rankType;
                                                                    } else {
                                                                        $item->team_member_division = null;
                                                                    }
                                                                        $item->profile_picture = Common::getResortUserPicture($item->resort_admin_id);
                                                                        return $item;
                                                                    });

            

            return response()->json([
                'success'                               => true,
                'message'                               => "SOS history fetched successfully.",
                'data'                                  => [
                    'acknowledged'                      => $sosTeamMemberAcknowledged,
                    'unacknowledged'                    => $sosTeamMemberUnacknowledged,
                ],], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function drillRealSOS(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'sos_id'                                    => 'required',
            'action'                                    => 'required|in:Real-Active,Drill-Active',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        $employee_id                                    =  $this->user->GetEmployee->id;
        try {
            $sosHistory                                 =   SOSHistoryModel::where('id',$request->sos_id)
                                                                ->where('resort_id', $this->resort_id)
                                                                ->where('status', 'Pending')
                                                                ->first();

            if (!$sosHistory) {
                return response()->json(['success' => false, 'message' => 'SOS Not Found'], 200);
            }

            $sosHistory->status                         =   $request->action;
            $sosHistory->save();

            return response()->json([
                'success'                               =>  true,
                'message'                               =>  "SOS {$request->action} successfully.",
                'data'                                  =>  $sosHistory,
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function completeSOSUpdateStatus(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'sos_id'                                    => 'required',
            'status'                                    => 'required|in:Completed',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }
        // DB::beginTransaction();
        try {
            $sosHistory                                 =   SOSHistoryModel::where('id', $request->sos_id)
                                                                ->where('resort_id', $this->resort_id)
                                                                ->whereIn('status',['Active', 'Drill-Active', 'In-Progress'])
                                                                ->first();
            if (!$sosHistory) {
                return response()->json(['success' => false, 'message' => 'SOS Not Found'], 200);
            }

            $sosHistory->status                         =   $request->status;
            $sosHistory->save();

            ChildSOSHistoryStatus::create([
                'sos_history_id'                        =>  $sosHistory->id,
                'sos_status'                            =>  'situation_was_marked_as_under_control',
            ]);

            ChildSOSHistoryStatus::create([
                'sos_history_id'                        =>  $sosHistory->id,
                'sos_status'                            =>  'sos_completed',
             ]);

            $title                                  =   "SOS Under Controlled";
            $body                                   =   "SOS Alert: Incident was reported and is now under control. For your safety, please remain calm and proceed to the nearest designated assembly point.";
            $moduleName                             =   'SOS';
            
            //Send push notification to the employee same resort
            $allEmpDeviceId                         =   Employee::where('resort_id',$this->resort_id)->where('status','Active')->where('id','!=',$this->user->GetEmployee->id)->pluck('device_token');
            $allEmpPushNotification                 =   Common::sendPushNotifictionForMobile($allEmpDeviceId->toArray(), $title, $body, $moduleName,'Completed',NULL,NULL,NULL);

            // DB::commit();
            return response()->json([
                'success'                               =>  true,
                'message'                               =>  "SOS status updated successfully.",
                'data'                                  =>  $sosHistory,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

}
