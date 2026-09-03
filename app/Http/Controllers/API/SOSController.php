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
use App\Models\ResortSiteSettings;
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
    
    public function getEmergencyContacts()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $siteSettings = ResortSiteSettings::where('resort_id', $this->resort_id)->first();

            return response()->json([
                'success' => true,
                'message' => "Emergency contact numbers retrieved successfully.",
                'data' => [
                    'police' => $siteSettings->emergency_police_number ?? null,
                    'fire' => $siteSettings->emergency_fire_number ?? null,
                    'mndf' => $siteSettings->emergency_mndf_number ?? null,
                ],
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
            // A hard requirement on a typed location string blocked every
            // SOS trigger that had real GPS coordinates but an empty
            // location field — exactly backwards for a panic button, where
            // speed matters and lat/long are the authoritative source of
            // location anyway. Only require the text fallback when
            // coordinates genuinely aren't available.
            'location'                                  =>  'nullable|required_without_all:latitude,longitude',
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

            // rank==4 (MGR) used to be required here too, but no Security
            // Manager record in the DB actually carries rank 4 — the real
            // seeded example is rank 2/HOD — so that condition never
            // matched and every SOS trigger silently skipped notifying
            // anyone. Title alone is the real signal (matches
            // EnsureSOSSecurityManagerAccess, which gates the
            // approve/dispatch endpoints this employee is routed to).
            $smEmployeeModel                            =   Employee::join('resort_positions as rp', 'employees.Position_id', '=', 'rp.id')
                                                                ->where('employees.resort_id', $this->resort_id)
                                                                ->where('employees.status', 'Active')
                                                                ->where('rp.position_title', 'Security Manager')
                                                                ->select('employees.id','employees.Admin_Parent_id','employees.Emp_id','employees.Position_id','employees.device_token')
                                                                ->first();
            $smEmployee                                 =   $smEmployeeModel ? $smEmployeeModel->toArray() : null;

            // No active Security Manager configured for this resort — the SOS
            // record itself is still saved; just skip the push/notification
            // step instead of crashing the whole request.
            if ($smEmployee) {
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
                $sosPushNotification                        =   Common::sendPushNotificationForMobile([$smEmployee['device_token']], $title, $body, $moduleName,'Pending',$sound,$custom_sound_channel,NULL);

                $sosNotification                            =   Common::sendMobileNotification($this->resort_id,2,null,null,$title,$body,$moduleName,[$smEmployee['id']],$SOSHistoryAdd->id,false,'sos-alert-security-manager');
            } else {
                \Log::warning("SOSStore: no active Security Manager found for resort_id {$this->resort_id} — SOS #{$SOSHistoryAdd->id} saved without push notification.");
            }

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
                Common::sendPushNotificationForMobile([$empInitiatedDeviceToken['device_token']], $title, $body, $moduleName, $sosStatus, null, null,NULL);
                Common::sendMobileNotification($this->resort_id,2,null, null, $title, $body, $moduleName, [$empInitiatedDeviceToken['id']], $sosHistory->id,false,'sos-status-update');
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
                        // ::insert() is a raw bulk query, not ::create() — it
                        // never auto-populates Eloquent timestamps, unlike
                        // $teamHistoryInsertData above which sets them
                        // explicitly. Every row inserted here previously got
                        // a permanent NULL updated_at, crashing any view
                        // calling ->diffForHumans() on it.
                        'created_at'                    =>  now(),
                        'updated_at'                    =>  now(),
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
            Common::sendPushNotificationForMobile($deviceTokens, $title, $request->team_message, $moduleName,$sosStatus,$sound,$custom_sound_channel,NULL);
            Common::sendMobileNotification($this->resort_id,2,null,null, $title,$request->team_message,$moduleName,$empIds,$sosHistory->id,false,'sos-team-alert');

            //Send in app and push notification to the who initiated the SOS
            Common::sendPushNotificationForMobile([$empInitiatedDeviceToken['device_token']], $title, $body, $moduleName,'Active',$sound,$custom_sound_channel,NULL);
            Common::sendMobileNotification($this->resort_id,2,null,null,$title, $body,$moduleName,[$empInitiatedDeviceToken['id']],$sosHistory->id,false,'sos-status-update');

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

            Common::sendPushNotificationForMobile($allEmpDeviceId->toArray(), $title, $request->employee_message ?? 'Please help us, SOS Alert has been raised.', $moduleName,'Active',$sound,$custom_sound_channel,NULL);
            Common::sendMobileNotification($this->resort_id,2,null,null,$title, $request->employee_message ?? 'Please help us, SOS Alert has been raised.',$moduleName,$allEmpId,$sosHistory->id,false,'sos-team-alert');

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

    /**
     * Continuous live-location ping during an active SOS. The triggering
     * employee's (and any responding team member's) app calls this on an
     * interval while the event is open; it overwrites the "last known
     * location" already read by employeeAndTeamLocation/showMap/
     * filterMapEmployeeList — no new trail table, those endpoints already
     * render off sos_history_employee_status / sos_team_member_activity's
     * current lat/lng, so updating the same rows in place is all a live
     * map needs. sos_history_id is resort-and-status gated the same way
     * as employeeAndTeamLocation/getTeamAcknowledged (this session's fix
     * pattern) so a ping can never be written into another resort's, or a
     * closed-out, SOS event.
     */
    public function SOSLocationUpdate(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator                                      =   Validator::make($request->all(), [
            'sos_history_id'                            =>  'required',
            'latitude'                                  =>  'required',
            'longitude'                                 =>  'required',
            'address'                                   =>  'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        try {
            $sosHistory                                 =   SOSHistoryModel::where('id', $request->sos_history_id)
                                                                ->where('resort_id', $this->resort_id)
                                                                ->whereNotIn('status', ['Completed', 'Rejected', 'Drill-Completed', 'Drill-Rejected'])
                                                                ->first();

            if (!$sosHistory) {
                return response()->json(['success' => false, 'message' => 'SOS event not found or no longer active.'], 404);
            }

            $employeeStatus                             =   SosHistoryEmployeeStatus::updateOrCreate(
                                                                [
                                                                    'sos_history_id'     =>  $request->sos_history_id,
                                                                    'emp_id'             =>  $this->user->GetEmployee->id,
                                                                ],
                                                                [
                                                                    'latitude'           =>  $request->latitude,
                                                                    'longitude'          =>  $request->longitude,
                                                                    'address'            =>  $request->address,
                                                                ]
                                                            );

            // Also refresh the caller's team-activity pin if they're a
            // responding team member on this event — employeeAndTeamLocation
            // merges both sources for the live map.
            SosTeamMemberActivity::where('sos_history_id', $request->sos_history_id)
                                                                ->where('emp_id', $this->user->id)
                                                                ->update([
                                                                    'latitude'           =>  $request->latitude,
                                                                    'longitude'          =>  $request->longitude,
                                                                    'address'            =>  $request->address,
                                                                ]);

            return response()->json([
                'success'                               =>  true,
                'message'                               =>  "Location updated successfully.",
                'data'                                  =>  $employeeStatus,
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
            // No resort ownership check at all — any authenticated user,
            // any resort, could pull live GPS + name/photo for every
            // employee/SOS-team member tied to another resort's active
            // emergency just by enumerating sos_id.
            $SOSHistoryModel                            =   SOSHistoryModel::where('id', $sosId)
                                                                ->where('resort_id', $this->resort_id)
                                                                // 'Real-Active' (a genuine, non-drill emergency — see
                                                                // drillRealSOS()) was missing from this list, so live
                                                                // location tracking silently refused to show anyone's
                                                                // position for exactly the emergencies where it matters
                                                                // most, returning a misleading "No employee location
                                                                // found" instead.
                                                                ->whereIn('sos_history.status',['Active','Drill-Active','Real-Active','In-Progress'])
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
                                                                ->where('sos_history.resort_id', $this->resort_id)
                                                                ->where('stma.emp_id', $this->user->id)
                                                                // 'Real-Active' (a genuine, non-drill emergency — see
                                                                // drillRealSOS()) was missing from this list, so live
                                                                // location tracking silently refused to show anyone's
                                                                // position for exactly the emergencies where it matters
                                                                // most, returning a misleading "No employee location
                                                                // found" instead.
                                                                ->whereIn('sos_history.status',['Active','Drill-Active','Real-Active','In-Progress'])
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

            // date/time/emergency_description already come through via
            // sos_history.* above — but nothing here ever resolved WHO
            // raised it, so the Security Manager's detail screen had no
            // name or photo to show for the initiating employee.
            $initiator                                  =   Employee::join('resort_admins as ra', 'ra.id', '=', 'employees.Admin_Parent_id')
                                                                ->where('employees.id', $sosData->emp_initiated_by)
                                                                ->select('ra.id as admin_id', 'ra.first_name', 'ra.last_name', 'employees.Emp_id')
                                                                ->first();
            $sosData->initiator_name                    =   $initiator ? trim($initiator->first_name . ' ' . $initiator->last_name) : null;
            $sosData->initiator_emp_id                  =   $initiator->Emp_id ?? null;
            $sosData->initiator_photo                   =   $initiator ? Common::getResortUserPicture($initiator->admin_id) : null;

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

            // sos_approved_by is null until someone actually approves/
            // acknowledges the SOS — every alert not yet acted on crashed
            // this endpoint with "assign property on null".
            $sosHistoryData->sos_approved_by_name       =   $sosHistoryData->sos_approved_by
                                                                ? Employee::join('resort_admins as ra', 'employees.Admin_Parent_id', '=', 'ra.id')
                                                                    ->where('employees.id', $sosHistoryData->sos_approved_by)
                                                                    ->select('employees.id','employees.Admin_Parent_id', 'ra.first_name', 'ra.last_name', 'ra.profile_picture')
                                                                    ->first()
                                                                : null;

            if ($sosHistoryData->sos_approved_by_name) {
                $sosHistoryData->sos_approved_by_name->profile_picture =   Common::getResortUserPicture( $sosHistoryData->sos_approved_by_name->Admin_Parent_id);
            }
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

        // Same gap as employeeAndTeamLocation() — no resort ownership
        // check at all, so this leaked acknowledged/unacknowledged SOS
        // team member names/photos/division/rank for any resort's event.
        if (!SOSHistoryModel::where('id', $sosId)->where('resort_id', $this->resort_id)->exists()) {
            return response()->json(['success' => false, 'message' => 'SOS event not found.'], 404);
        }

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
                                                                // 'Real-Active' (a real, non-drill SOS) was missing here —
                                                                // same class of bug fixed for employee-team-location/
                                                                // SOSDetails: a live SOS could never be marked Completed
                                                                // by the security manager, only a drill could.
                                                                ->whereIn('status',['Active', 'Drill-Active', 'Real-Active', 'In-Progress'])
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
            $allEmpPushNotification                 =   Common::sendPushNotificationForMobile($allEmpDeviceId->toArray(), $title, $body, $moduleName,'Completed',NULL,NULL,NULL);

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

    /**
     * All SOS response teams for the resort, each with its members
     * (name/photo/role) — the mobile "Fire Team Members" screen.
     * SOSTeamListing() only returns team name/description, not members.
     */
    public function fireTeamMembers()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $teams                                       =   SOSTeamManagementModel::where('resort_id', $this->resort_id)
                                                                ->get()
                                                                ->map(function ($team) {
                                                                    $team->members = SOSTeamMemeberModel::join('resort_admins as ra', 'sos_team_members.emp_id', '=', 'ra.id')
                                                                        ->leftJoin('sos_role_management as role', 'sos_team_members.role_id', '=', 'role.id')
                                                                        ->where('sos_team_members.team_id', $team->id)
                                                                        ->select(
                                                                            'sos_team_members.id',
                                                                            'ra.id as resort_admin_id',
                                                                            'ra.first_name',
                                                                            'ra.last_name',
                                                                            'role.name as role_name'
                                                                        )
                                                                        ->get()
                                                                        ->map(function ($member) {
                                                                            $member->profile_picture = Common::getResortUserPicture($member->resort_admin_id);
                                                                            return $member;
                                                                        });
                                                                    return $team;
                                                                });

            return response()->json([
                'success'                               =>  true,
                'message'                               =>  "Fire team members fetched successfully.",
                'data'                                  =>  $teams,
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * Open/active SOS incidents for Security-staffed roles (position_title
     * containing "Security" — there's no dedicated rank/role for this,
     * confirmed against config('settings.Position_Rank')).
     */
    public function securityStaffDashboard()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $activeSos                                  =   SOSHistoryModel::join('sos_emergency_types as set', 'sos_history.emergency_id', '=', 'set.id')
                                                                ->join('employees as e', 'sos_history.emp_initiated_by', '=', 'e.id')
                                                                ->join('resort_admins as ra', 'e.Admin_Parent_id', '=', 'ra.id')
                                                                ->where('sos_history.resort_id', $this->resort_id)
                                                                ->whereIn('sos_history.status', ['Pending', 'Active', 'Real-Active', 'In-Progress'])
                                                                ->orderBy('sos_history.created_at', 'desc')
                                                                ->select(
                                                                    'sos_history.*',
                                                                    'set.name as emergency_name',
                                                                    'ra.first_name',
                                                                    'ra.last_name',
                                                                    'e.Admin_Parent_id'
                                                                )
                                                                ->get()->map(function ($item) {
                                                                    $item->profile_picture = Common::getResortUserPicture($item->Admin_Parent_id);
                                                                    return $item;
                                                                });

            return response()->json([
                'success'                               =>  true,
                'message'                               =>  "Security staff dashboard fetched successfully.",
                'data'                                  =>  [
                    'active_sos_count'                  =>  $activeSos->count(),
                    'active_sos'                         =>  $activeSos,
                ],
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * Live dashboard for the Security Manager — pending SOS awaiting
     * approve/reject/team-assignment (see SOSStore's rank=4 +
     * position_title='Security Manager' routing) plus a count of
     * currently-active incidents.
     */
    public function managerDashboard()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $pendingSos                                  =   SOSHistoryModel::join('sos_emergency_types as set', 'sos_history.emergency_id', '=', 'set.id')
                                                                ->join('employees as e', 'sos_history.emp_initiated_by', '=', 'e.id')
                                                                ->join('resort_admins as ra', 'e.Admin_Parent_id', '=', 'ra.id')
                                                                ->where('sos_history.resort_id', $this->resort_id)
                                                                ->where('sos_history.status', 'Pending')
                                                                ->orderBy('sos_history.created_at', 'desc')
                                                                ->select(
                                                                    'sos_history.*',
                                                                    'set.name as emergency_name',
                                                                    'ra.first_name',
                                                                    'ra.last_name',
                                                                    'e.Admin_Parent_id'
                                                                )
                                                                ->get()->map(function ($item) {
                                                                    $item->profile_picture = Common::getResortUserPicture($item->Admin_Parent_id);
                                                                    return $item;
                                                                });

            $activeSosCount                              =   SOSHistoryModel::where('resort_id', $this->resort_id)
                                                                ->whereIn('status', ['Active', 'Real-Active', 'In-Progress'])
                                                                ->count();

            return response()->json([
                'success'                               =>  true,
                'message'                               =>  "Manager dashboard fetched successfully.",
                'data'                                  =>  [
                    'pending_approval_count'             =>  $pendingSos->count(),
                    'pending_approval'                   =>  $pendingSos,
                    'active_sos_count'                   =>  $activeSosCount,
                ],
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * Chat log for a single SOS incident. New dedicated table (see
     * migration comment) — the shared `conversation` table's type enum is
     * hard-limited to group/individual, so this stays isolated instead of
     * widening shared chat infra.
     */
    public function sosChatLogs($sosId)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $sosId                                          =   base64_decode($sosId);
        try {
            $messages                                   =   \App\Models\SosChatMessage::join('resort_admins as ra', 'sos_chat_messages.sender_id', '=', 'ra.id')
                                                                ->where('sos_chat_messages.resort_id', $this->resort_id)
                                                                ->where('sos_chat_messages.sos_history_id', $sosId)
                                                                ->orderBy('sos_chat_messages.created_at', 'asc')
                                                                ->select(
                                                                    'sos_chat_messages.*',
                                                                    'ra.first_name',
                                                                    'ra.last_name'
                                                                )
                                                                ->get()->map(function ($item) {
                                                                    $item->profile_picture = Common::getResortUserPicture($item->sender_id);
                                                                    return $item;
                                                                });

            return response()->json([
                'success'                               =>  true,
                'message'                               =>  "SOS chat log fetched successfully.",
                'data'                                  =>  $messages,
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * Post a chat message during an active SOS. Not explicitly in the
     * mobile spec (which only listed the GET log), but a log screen with
     * no way to populate it isn't testable — thin, isolated addition.
     */
    public function sosSendChatMessage(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator                                      =   Validator::make($request->all(), [
            'sos_history_id'                            =>  'required',
            'message'                                   =>  'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        // sos_history_id was never checked against the resort before
        // insert — a message could be attached to another resort's SOS
        // event log.
        $sosHistory                                      =   SOSHistoryModel::where('id', $request->sos_history_id)->where('resort_id', $this->resort_id)->first();
        if (!$sosHistory) {
            return response()->json(['success' => false, 'message' => 'SOS event not found.'], 404);
        }

        try {
            $chatMessage                                 =   \App\Models\SosChatMessage::create([
                'resort_id'                              =>  $this->resort_id,
                'sos_history_id'                          =>  $request->sos_history_id,
                'sender_id'                               =>  $this->user->id,
                'message'                                 =>  $request->message,
            ]);

            // The chat log only ever updated if the employee's app happened
            // to poll it — nothing told them a new instruction arrived, so
            // it never showed "in real time". Push it immediately, same as
            // every other SOS status change.
            $recipient                                   =   Employee::where('resort_id', $this->resort_id)
                                                                ->where('id', $sosHistory->emp_initiated_by)
                                                                ->first(['id', 'device_token']);
            if ($recipient && $recipient->id != ($this->user->GetEmployee->id ?? null)) {
                Common::sendPushNotificationForMobile([$recipient->device_token], 'Security Instructions', $request->message, 'SOS', 'Active', 'siren_sound', 'custom_sound_channel', NULL);
                Common::sendMobileNotification($this->resort_id, 2, null, null, 'Security Instructions', $request->message, 'SOS', [$recipient->id], $sosHistory->id, false, 'sos-chat-message');
            }

            return response()->json([
                'success'                               =>  true,
                'message'                               =>  "Message sent successfully.",
                'data'                                  =>  $chatMessage,
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

}
