<?php

namespace App\Http\Controllers\Resorts\SOS;


use Excel;
use DB;
use URL;
use Auth;
use Carbon\Carbon;
use App\Helpers\Common;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;
use App\Models\Employee;
use App\Models\SOSHistoryModel; 
use App\Models\ChildSosHistory; 
use App\Models\SosTeamMemberActivity; 
use App\Models\SosHistoryEmployeeStatus; 
use App\Models\ResortDepartment;
use App\Models\SOSRolesAndPermission;
use App\Models\ResortGeoLocation;
use Google\Service\CloudControlsPartnerService\Console;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{
 
    public $resort;
    public $reporting_to;
    protected $underEmp_id=[];
    public function __construct()
    {
        $this->resort = $resortId = auth()->guard('resort-admin')->user();
        if(!$this->resort) return;
        // $reporting_to = $this->resort->GetEmployee->id;
        // $this->underEmp_id = Common::getSubordinates($reporting_to);
    }
    public function index(Request $request)
    {
        $page_title="Dashboard";

        $query = SOSHistoryModel::with(['getSos','employee','employee.resortAdmin'])->where('resort_id',$this->resort->resort_id);
        
        if ($request->searchTerm) {
            $searchTerm = $request->searchTerm;

            $query->where(function ($q) use ($searchTerm) {
                $q->Where('location', 'LIKE', "%{$searchTerm}%")
                ->orWhereHas('getSos', function ($sosQuery) use ($searchTerm) {
                    $sosQuery->where('name', 'LIKE', "%{$searchTerm}%");
                })
                ->orWhereHas('employee.resortAdmin', function ($nestedQuery) use ($searchTerm) {
                    $nestedQuery->where(function ($qq) use ($searchTerm) {
                        $qq->where('first_name', 'LIKE', "%{$searchTerm}%")
                            ->orWhere('last_name', 'LIKE', "%{$searchTerm}%")
                            ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$searchTerm}%"]);
                    });
                });
            });
        }
    
        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->date) {
            $query->where('date', $request->date);
        }

        // Show archived or active data
        if ($request->has('show_archived') && $request->show_archived == 'true') {
            $query->onlyTrashed(); // show only archived (soft deleted) records
        } else {
            $query->whereNull('deleted_at'); // default: active data
        }
        $query->orderBy('created_at', 'desc');
        $SOSHistory = $query->get();

        if($request->ajax())
        {
            $delete_class = '';
            if(Common::checkRouteWisePermission('sos.dashboard.index',config('settings.resort_permissions.delete')) == false){
                $delete_class = 'd-none';
            }
            return datatables()->of($SOSHistory)
            ->addColumn('sos_type', function ($row) {
                return $row->getSos->name;
            })
            ->addColumn('date', function ($row) {
                return date('d M Y',strtotime($row->date));
            })
            ->addColumn('time', function ($row) {
                return date('h:i A',strtotime($row->time));
            })
            ->addColumn('initiated_by', function ($row) {
                $employeesImage = '';
                    $empDetails = Employee::find($row->emp_initiated_by);
                    if ($empDetails) {
                        $image = Common::getResortUserPicture($empDetails->Admin_Parent_id ?? null);
                        if ($image) {
                            $employeesImage .= '
                                <div class="tableUser-block">
                                    <div class="img-circle">
                                        <img src="' . $image . '" alt="' . e($row->emp_initiated_by) . '">
                                    </div>
                                    <span class="userApplicants-btn">'. $row->employee->resortAdmin->first_name .' '.  $row->employee->resortAdmin->last_name .'</span>
                                </div>';
                        }
                    }
                return '<div class="user-ovImg">' . $employeesImage . '</div>';
            })
            ->addColumn('status', function ($row) {
                switch($row->status) {
                    case 'Completed':
                        return '<span class="badge badge-themeSuccess">Completed</span>';
                    case 'Drill-active':
                        return '<span class="badge badge-infoBorder">Drill-active</span>';
                    case 'Active':
                        return '<span class="badge badge-infoBorder">Active</span>';
                    case 'Rejected':
                        return '<span class="badge badge-themeDangerNew">Rejected</span>';
                    default:
                        return '<span class="badge badge-themeDanger">Pending</span>';
                }
            })
            ->addColumn('Action', function ($row) use ($delete_class) {
                $id = base64_encode($row->id);
                $delete_url = route('sos.emergency.destroy', $row->id);
                            return '
                            <div  class="d-flex align-items-center">
                                <a href="' . route('sos.emergency.view', e($id)) . '" title="View SOS Detail" class="btn-tableIcon  btnIcon-skyblue me-1">
                                    <i class="fa-regular fa-eye"></i>
                                </a>

                                <a href="' . route('sos.viewTeamActivityDetails', e($id)) . '" title="View Team Activity Details" class="btn-tableIcon  btnIcon-skyblue me-1">
                                    <i class="fa fa-users"></i>
                                </a>
                                
                                <a href="' . route('sos.viewEmployeeSafetyDetails', e($id)) . '" title="View Employee Safey Status" class="btn-tableIcon  btnIcon-skyblue me-1">
                                    <i class="fa-regular fa-user"></i>
                                </a>

                                <a href="' . route('sos.showMap', e($id)) . '" title="View Employees Live Location" class="btn-tableIcon  btnIcon-skyblue me-1">
                                    <i class="fas fa-location"></i>
                                </a>

                                <a data-toggle="tooltip" data-title="Archive" class="btn-lg-icon icon-bg-skyblue action-delete  mx-1 '.$delete_class.'" href="JavaScript:void(0);" data-url="'.$delete_url.'"><img src="' . asset("resorts_assets/images/archive.svg") . '" class="img-fluid"></a>
                            </div>';
            })
            ->rawColumns(['sos_type','date','time','initiated_by','status','Action'])
            ->make(true);
        }

        $hasPendingSOS = SOSHistoryModel::where('status', 'Pending')->exists();

        return view('resorts.SOS.dashboard.index', compact('page_title', 'SOSHistory','hasPendingSOS'));
        
    }

    public function destroy($id)
    {
      try {
          $data = SOSHistoryModel::whereId($id)->first();
          if(!$data){
            $data = SOSHistoryModel::withTrashed()->whereId($id)->first();

          }
          // Soft delete the emergency (sos)
          $data->delete();
          $response['success'] = true;
          $response['msg'] = __('messages.archiveSuccess', ['name' => 'SOS']);
      } catch (\Exception $e) {
          \Log::emergency("File: " . $e->getFile());
          \Log::emergency("Line: " . $e->getLine());
          \Log::emergency("Message: " . $e->getMessage());
          $response['success'] = false;
          $response['msg'] = $e->getMessage();
      }
      return response()->json($response);
    }

    public function updateStatus(Request $request)
    {
        $request->validate([
            'type' => 'required|in:Drilled,Real',
        ]);

        try {
            $sos = SOSHistoryModel::where('status', 'Pending')
            ->where('resort_id', auth()->user()->resort_id) // optional if needed
            ->latest()
            ->first();

            if (!$sos) {
                return response()->json([
                    'success' => false,
                    'message' => 'No pending SOS found.',
                ]);
            }

            $status = $request->type === 'Drilled' ? 'Drill-Active' : 'Real-Active';
            $sos->status = $status;
            $sos->save();

            return response()->json([
                'success' => true,
                'message' => 'SOS status updated to '. $request->type,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function view($id)
    {
        if(Common::checkRouteWisePermission('sos.dashboard.index',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized action.');
        }

        $page_title ='SOS Detail';
        $id = base64_decode($id);
        $resort_id = $this->resort->resort_id;

        $getSOSDetails = SOSHistoryModel::with(['getSos','employee','employee.resortAdmin'])->where('id',$id)->first();  
  
        return view('resorts.SOS.dashboard.view-sos-detail',compact('page_title','getSOSDetails'));
    }

    public function viewTeamActivityDetails(Request $request,$id)
    {
        if(Common::checkRouteWisePermission('sos.dashboard.index',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized action.');
        }
        $page_title ='Team Activity';
        $id = base64_decode($id);
        $resort_id = $this->resort->resort_id;

        $sosDetails = SOSHistoryModel::with(['getSos','employee','employee.resortAdmin'])->where('id',$id)->first();

        $teamMembersQuery = SosTeamMemberActivity::with(['team', 'resortAdmin', 'resortAdmin.GetEmployee', 'memberRole'])
        ->where('sos_history_id', $id);
        $teamMembers = $teamMembersQuery->paginate(10);
        // $teamMembers = $teamMembersQuery->get();

        $onlyAckSosCount = SosTeamMemberActivity::where('status','Acknowledged')->where('sos_history_id', $id)->count();
        $pendingSosCount = SosTeamMemberActivity::where('status','Unacknowledged')->where('sos_history_id', $id)->count();
        $totalMemebersCount = SosTeamMemberActivity::where('sos_history_id', $id)->count();

        $getAllTeams = ChildSosHistory::with('team')->where('sos_history_id', $id)->get();

        return view('resorts.SOS.dashboard.view-team-activity-detail',compact('page_title','sosDetails','teamMembers','totalMemebersCount','onlyAckSosCount','pendingSosCount','id','getAllTeams'));
        
    }

    public function filterTeamActivityDetails(Request $request, $id)
    {
        if(Common::checkRouteWisePermission('sos.dashboard.index',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized action.');
        }
        $teamId = $request->teamId;
        $showUnack = $request->show_status;

        $teamMembersQuery = SosTeamMemberActivity::with([
            'team', 
            'resortAdmin', 
            'resortAdmin.GetEmployee', 
            'memberRole'
        ])->where('sos_history_id', $id);

        if (!empty($teamId)) {
            $teamMembersQuery->where('team_id', $teamId);
        }

        if ($showUnack == "true") {
            $teamMembersQuery->where('status', 'Unacknowledged');
        }

        $teamMembers = $teamMembersQuery->paginate(10);

        $html= view( 'resorts.renderfiles.SosTeamMembersActivityList',compact( 'teamMembers'))->render();
        $pagination =  $teamMembers->links()->render();

        return response()->json(['success' => true, 'html' => $html, 'pagination'=>$pagination]);
    }

    public function viewEmployeeSafetyDetails(Request $request,$id)
    {
        if(Common::checkRouteWisePermission('sos.dashboard.index',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized action.');
        }
        $page_title ='Employee Safety Status';
        $id = base64_decode($id);
        $resort_id = $this->resort->resort_id;

        $sosDetails = SOSHistoryModel::with(['getSos'])->where('id',$id)->first();

        $employeeListQuery = SosHistoryEmployeeStatus::with(['employee', 'employee.resortAdmin', 'employee.department', 'employee.position'])
        ->where('sos_history_id', $id);
        $employeesStatusList = $employeeListQuery->paginate(10);

        $onlySafeEmpCount = SosHistoryEmployeeStatus::where('status','Safe')->where('sos_history_id', $id)->count();
        $onlyUnsafeEmpCount = SosHistoryEmployeeStatus::where('status','Unknown')->where('sos_history_id', $id)->count();
        $totalEmployeesCount = SosHistoryEmployeeStatus::where('sos_history_id', $id)->count();

        $getAllDepartments = ResortDepartment::where('resort_id',  $resort_id)->get();

        return view('resorts.SOS.dashboard.ViewEmployeeSafetyStatus',compact('page_title','sosDetails','employeesStatusList','totalEmployeesCount','onlySafeEmpCount','onlyUnsafeEmpCount','id','getAllDepartments'));
        
    }

    public function filterEmployeeSafetyDetails(Request $request, $id)
    {
        if(Common::checkRouteWisePermission('sos.dashboard.index',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized action.');
        }
        $departmentId = $request->departmentId;
        $showUnsafe = $request->show_status;

        $employeeListQuery = SosHistoryEmployeeStatus::with(['employee', 'employee.resortAdmin', 'employee.department','employee.position'])
        ->where('sos_history_id', $id);

        if (!empty($departmentId)) {
            $employeeListQuery->whereHas('employee', function ($query) use ($departmentId) {
                $query->where('Dept_id', $departmentId);
            });
        }

        if ($showUnsafe == "true") {
            $employeeListQuery->where('status', 'Unknown');
        }

        $employeesStatusList = $employeeListQuery->paginate(10);

        $html= view( 'resorts.renderfiles.SosEmployeesStatusList',compact( 'employeesStatusList'))->render();

        return response()->json(['success' => true, 'html' => $html]);
    }

    public function updateMassInstruction(Request $request)
    {
        $request->validate([
            'sos_history_id' => 'required|exists:sos_history,id',
            'mass_instruction' => 'required|string|max:255',
        ]);

        $update = SOSHistoryModel::where('id', $request->sos_history_id)
            ->update(['mass_instructions' => $request->mass_instruction]);

        if ($update) {
            $allEmpDeviceId                         =   Employee::where('resort_id',$this->resort->resort_id)
                                                            ->where('status', 'Active')                 
                                                            ->where('device_token', '!=', null)
                                                            ->where('device_token', '!=', '')
                                                            ->pluck('device_token');
            $title                                  =   "SOS Mass Instruction";
            $moduleName                             =   'SOS';
            $allEmpPushNotification             =   Common::sendPushNotifictionForMobile($allEmpDeviceId->toArray(), $title, $request->mass_instruction, $moduleName, NULL, NULL,NULL,'mass');
        }
        return response()->json(['success' => true, 'message' => 'Mass instruction updated successfully.']);
    }

    public function showMap($id)
    {
        if(Common::checkRouteWisePermission('sos.dashboard.index',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized action.');
        }
        $id = base64_decode($id);
        $employeesStatusList = SosHistoryEmployeeStatus::with(['employee', 'employee.resortAdmin', 'employee.department', 'employee.position'])->where('sos_history_id', $id)
        // ->whereHas('sosHistory', function ($query) {
        //     $query->where('status', 'active');
        // })
        ->get();
        $getAllTeams = ChildSosHistory::with('team')->where('sos_history_id', $id)->get();
        $Roles = SOSRolesAndPermission::where("resort_id",$this->resort->resort_id)->get();

        //get lat lng of resort
        // $geoLocation = ResortGeoLocation::where('resort_id', $this->resort->resort_id)->first();

        //get lat lng of sos event
        $geoLocation = SOSHistoryModel::where('id',$id)->first();


        $lat = $geoLocation && $geoLocation->latitude ? $geoLocation->latitude : '22.31514320';
        $lng = $geoLocation && $geoLocation->longitude ? $geoLocation->longitude : '73.14445130';
        $page_title = 'Live Location';
        return view('resorts.SOS.dashboard.employeeLiveLocation', compact('page_title','employeesStatusList','getAllTeams','Roles','id','lat', 'lng'));
    }

    public function filterMapEmployeeList(Request $request, $id)
    {
        if(Common::checkRouteWisePermission('sos.dashboard.index',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized action.');
        }
        $roleId = $request->roleId;
        $teamId = $request->teamId;
        $safetyStatus = $request->safety_status;

        $employeeListQuery = SosHistoryEmployeeStatus::with([
            'employee', 
            'employee.resortAdmin', 
            'employee.department',
            'employee.position'
        ])
        ->where('sos_history_id', $id);
        // ->whereHas('sosHistory', function ($query) {
        //     $query->where('status', 'active');
        // })
        // ->whereNotNull('latitude')
        // ->whereNotNull('longitude')
        
        if ($roleId) {
            /* $employeeListQuery->whereHas('employee', function ($query) use ($roleId) {
                $query->whereHas('resortAdmin.sosTeamMemberships', function ($q) use ($roleId) {
                    $q->where('sos_team_members.role_id', $roleId);
                });
            }); */
            $employeeListQuery->whereHas('employee', function ($query) use ($roleId) {
                $query->where('rank', $roleId);
            });
        }

        if ($teamId) {
            $employeeListQuery->whereHas('employee', function ($query) use ($teamId) {
                $query->whereHas('resortAdmin.sosTeamMemberships', function ($q) use ($teamId) {
                    $q->where('sos_team_members.team_id', $teamId);
                });
            });
        }

        if ($safetyStatus) {
            $employeeListQuery->where('status', $safetyStatus);
        }

        // \DB::enableQueryLog();
        // $employeesStatusList = $employeeListQuery->get();
        // dd(\DB::getQueryLog());

        $employeesStatusList = $employeeListQuery->get();
        $locations = $employeesStatusList->map(function ($status) {
            if (!is_numeric($status->latitude) || !is_numeric($status->longitude)) {
                return null;
            }
            $rankConfig = config('settings.Position_Rank');
            $availableRank = array_key_exists($status->employee->rank, $rankConfig) ? $rankConfig[$status->employee->rank] : '';

            return [
                'id' => $status->employee->id,
                'name' => $status->employee->resortAdmin->full_name ?? 'Unknown',
                'department' => $status->employee->department->short_name ?? 'Unknown',
                'lat' => floatval($status->latitude),
                'lng' => floatval($status->longitude),
                'status' => $status->status,
                'image' => Common::getResortUserPicture($status->employee->admin_parent_id ?? null),
                'role' => $availableRank,
            ];
        })->filter()->values();;

        $html = view('resorts.renderfiles.EmployeeListLiveLocationView', compact('employeesStatusList'))->render();

        return response()->json([
            'success' => true, 
            'html' => $html,
            'locations' => $locations
        ]);
    }
        
}