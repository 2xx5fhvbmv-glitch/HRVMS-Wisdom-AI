<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use App\Models\Employee;
use App\Models\AssingAccommodation;
use App\Models\AvailableAccommodationModel;
use App\Models\BuildingModel;
use App\Models\MaintanaceRequest;
use App\Models\InventoryModule;
use App\Models\BulidngAndFloorAndRoom;
use App\Models\EscalationDay;
use App\Models\AccommodationType;
use App\Models\HousekeepingSchedules;
use App\Models\ChildHouseKeepingSchedules;
use App\Models\HousekeepingSchedulesImg;
use App\Helpers\Common;
use App\Models\ChildMaintananceRequest;
use Carbon\Carbon;
use DateTime;
use Validator;
use File;
use Auth;
use URL;
use DB;

class StaffAccommodationController extends Controller
{
    protected $user;
    protected $resort_id;
    protected $underEmp_id = [];

    public function __construct()
    {
        if (Auth::guard('api')->check()) {
            $this->user                                     =   Auth::guard('api')->user();
            $this->resort_id                                =   $this->user->resort_id;
            $this->reporting_to                             =   $this->user->GetEmployee->id;
            $this->underEmp_id                              =   Common::getSubordinates($this->reporting_to);
        }
    }

    public function staffAccommodationDashboard()
    {

        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $employee                                       =   $this->user->GetEmployee;
            $EscalationDay                                  =   EscalationDay::where('resort_id', $this->resort_id)->first();

            $inventoryItems                                 =   InventoryModule::where('resort_id', $this->resort_id)->pluck('ItemName', 'id');

            $accommodationDetails                           =   AvailableAccommodationModel::join('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
                                                                    ->join('building_models as bm', 'bm.id', '=', 'available_accommodation_models.BuildingName')
                                                                    ->where('available_accommodation_models.resort_id', $this->resort_id)
                                                                    ->where("t1.emp_id", $employee->id)
                                                                    ->select('available_accommodation_models.*','t1.id as assing_acc_id','t1.BedNo','t1.emp_id','bm.BuildingName')
                                                                    ->first();

            // Check if accommodation details exist to avoid errors
            $accommodationId = $accommodationDetails->id ?? null;

            $accommodationsharedPeople = 0;
            if ($accommodationId) {
                $accommodationsharedPeople                  =   AvailableAccommodationModel::join('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
                                                                    ->where('available_accommodation_models.resort_id', $this->resort_id)
                                                                    ->where('t1.available_a_id', $accommodationId)
                                                                    ->where('available_accommodation_models.Accommodation_type_id', 2)
                                                                    ->count();
            }

            $maintanaceRequest                              =   MaintanaceRequest::join("employees as t3", "t3.id", "=", "maintanace_requests.Raised_By")
                                                                    ->where('maintanace_requests.resort_id', $this->resort_id)
                                                                    ->where('maintanace_requests.Raised_By', $employee->id)
                                                                    ->orderBy('maintanace_requests.created_at', 'desc')
                                                                    ->whereNotIn("maintanace_requests.Status", ['ResolvedAwaiting'])
                                                                    ->take(2)
                                                                    ->get(['maintanace_requests.*']);
            
            $completeMaintananceReqQuery                    =   MaintanaceRequest::join("employees as t3", "t3.id", "=", "maintanace_requests.Raised_By")
                                                                    ->join("resort_admins as t1", "t1.id", "=", "t3.Admin_Parent_id")
                                                                    ->join("child_maintanance_requests as cmr", function ($join) {
                                                                        $join->on("cmr.maintanance_request_id", "=", "maintanace_requests.id")
                                                                            ->where("cmr.rank", "=", 3)
                                                                            ->where("cmr.Status", "=", "Open");
                                                                    })

                                                                    ->join("child_approved_maintanace_requests as camr", function ($join)  {
                                                                        $join->on("camr.maintanance_request_id", "=", "maintanace_requests.id")
                                                                            ->where("camr.rank", "=", 3)
                                                                            ->where("camr.Status", "=", "Approved"); // Note: Check spelling "Assigned" vs "Assinged"
                                                                    })
                                                                    ->where("maintanace_requests.resort_id", $this->resort_id)
                                                                    ->whereNotIn("maintanace_requests.Status", ['Closed'])
                                                                    ->get([
                                                                        'maintanace_requests.*',
                                                                        'cmr.id as child_maint_req_id',
                                                                        'cmr.Status as child_maint_req_status',
                                                                        'cmr.ApprovedBy as child_maint_req_ApprovedBy',
                                                                        'camr.id as child_appr_maint_req_id',
                                                                        'camr.Status as child_appr_maint_req_status',
                                                                        't1.id as Parentid',
                                                                        't1.first_name',
                                                                        't1.last_name',
                                                                    ])->map(function ($row) use ($EscalationDay, $inventoryItems) {
                                                                            return $this->formatMaintenanceRow($row, $EscalationDay, $inventoryItems);
                                                                    });
            // Prepare response data
            $accomData = [
                'accomodation_details'                      =>  $accommodationDetails,
                'accomodation_shared_people'                =>  $accommodationsharedPeople,
                'maintanace_request'                        =>  $maintanaceRequest,
                'complete_task'                             =>  $completeMaintananceReqQuery,
            ];

            $response['status']                             =   true;
            $response['message']                            =   'Accommodation Dashboard Data Retrieved Successfully';
            $response['accomodation_data']                  =   $accomData;

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }

    }

    public function staffAccommodationDetails($accommodationId)
    {

        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $accommodationId                                =   base64_decode($accommodationId, true);
            if (!$accommodationId) {
                return response()->json(['success' => false, 'message' => 'Invalid accommodation ID'], 200);
            }
            $employee                                       =   $this->user->GetEmployee;
            $accommodationDetailsQuery                      =   AvailableAccommodationModel::join('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
                                                                    ->join('accommodation_types as at', 'at.id', '=', 'available_accommodation_models.Accommodation_type_id')
                                                                    ->join('employees as e', 'e.id', '=', 't1.emp_id')
                                                                    ->join('resort_admins as ra', 'ra.id', "=", 'e.Admin_Parent_id')
                                                                    ->join("resort_departments as rd", "rd.id", "e.Dept_id")
                                                                    ->join('building_models as bm', 'bm.id', '=', 'available_accommodation_models.BuildingName')
                                                                    ->where('available_accommodation_models.resort_id', $this->resort_id)
                                                                    ->where("available_accommodation_models.id", $accommodationId)
                                                                    ->where("t1.emp_id", $employee->id)
                                                                    ->with('availableAccommodationInvItem.inventoryModule')
                                                                    ->select('available_accommodation_models.*','at.AccommodationName','t1.id as assing_acc_id','t1.BedNo','t1.emp_id as employee_id','bm.BuildingName','e.Emp_id','ra.first_name','ra.last_name','rd.name as department_name','ra.id as Parentid');
                                                                    
            $accommodationDetails                           =   $accommodationDetailsQuery->first();

            if (!$accommodationDetails) {
                return response()->json(['success' => false, 'message' => 'No accommodation details found'], 200);
            }

            $accommodationDetails->profileImg               =   Common::getResortUserPicture($accommodationDetails->Parentid);                                               

           
            // Fetch assigned accommodation employees
            $AssingAccommodation                            =   AssingAccommodation::where("available_a_id", $accommodationId)
                                                                    ->join('employees as t2', 't2.id', '=', 'assing_accommodations.emp_id')
                                                                    ->join('resort_admins as t3', 't3.id', '=', 't2.Admin_Parent_id')
                                                                    ->get(['t3.first_name', 't3.last_name', 't3.id as Parentid'])
                                                                    ->map(function ($row) {
                                                                        $row->EmployeeName = ucfirst($row->first_name . ' ' . $row->last_name);
                                                                        $row->profileImg = Common::getResortUserPicture($row->Parentid);
                                                                        return $row;
                                                                    });
                                                                    
            $accommodationDetails->shared_accommodation_users  =    $AssingAccommodation;
          
            $response['status']                             =   true;
            $response['message']                            =   'Accommodation Details Fetched Successfully for the Employee';
            $response['accomodation_data']                  =   $accommodationDetails;

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }

    }

    public function inventoryItems()
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $InventoryItems                             =   InventoryModule::where('resort_id',$this->resort_id)->get();

            $response['status']                         =   true;
            $response['message']                        =   'InventoryItems Fetched successfully';
            $response['data']                           =   $InventoryItems;
            return response()->json($response);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function createMaintenanceRequests(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'item_id'                                   =>  'required',
            'building_id'                               =>  'required',
            'FloorNo'                                   =>  'required',
            'RoomNo'                                    =>  'required',
            'descriptionIssues'                         =>  'required',
            'priority'                                  =>  'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        try {
            DB::beginTransaction();
            $date                                       =   DateTime::createFromFormat('d/m/Y', $request->date);
            $date                                       =   isset($request->date) ? $date->format('Y-m-d') :  date('Y-m-d');
            $path_path                                  =   config('settings.MaintanceRequest') . '/' . Auth::guard('api')->user()->resort->resort_id;
            
            $collection                                 =   [
                'resort_id'                             =>  $this->resort_id,
                'item_id'                               =>  $request->item_id,
                'building_id'                           =>  $request->building_id,
                'FloorNo'                               =>  $request->FloorNo,
                'RoomNo'                                =>  $request->RoomNo,
                'descriptionIssues'                     =>  $request->descriptionIssues,
                'priority'                              =>  isset($request->priority)? $request->priority:'Low',
                'date'                                  =>  $date,
                'Raised_By'                             =>  Auth::guard('api')->user()->GetEmployee->id,
            ];

            $filePath                                   =   null;
            if ($request->hasFile('Image')) {
                $file       =   $request->file('Image');
                $SubFolder  =   "MaintanceRequest";
                $status     =   Common::AWSEmployeeFileUpload($this->resort_id, $file, $this->user->GetEmployee->Emp_id, $SubFolder, true);

                if ($status['status'] == false) {
                    return response()->json([
                        'success'           =>  false, 
                        'message'           =>  'File upload failed: ' . ($status['msg'] ?? 'Unknown error')
                    ], 400);
                } else {
                    if($status['status'] == true && isset($status['Chil_file_id']) && !empty($status['Chil_file_id'])) {
                        $filename                       =   $file->getClientOriginalName();
                       $filePath                        =   ['Filename' => $filename, 'Child_id' => $status['Chil_file_id']];
                    }
                }
            }
            $collection['Image']                        =   json_encode($filePath);

            $m_id                                       =   MaintanaceRequest::create($collection);

            if(isset($m_id->id)) {
                $status                                 =   ['Open','Assinged','In-Progress','Resolvedawaiting','Closed'];

                    ChildMaintananceRequest::create([
                        'maintanance_request_id'        =>  $m_id->id,
                        'resort_id'                     =>  $this->resort_id,
                        'ApprovedBy'                    =>  0,
                        'Status'                        =>  'pending',
                        'date'                          =>  date('Y-m-d'),
                    ]);

                // Send mobile notification to HR employee
                $hrEmployee                             =   Common::FindResortHR($this->user);
                $emp_id                                 =   Auth::guard('api')->user()->GetEmployee->Emp_id;
                
                if ($hrEmployee) {
                    Common::sendMobileNotification(
                        $this->resort_id,
                        2,
                        null,
                        null,
                        'Maintenance Request Created',
                        "The Maintenance Request for #$emp_id has been Created.".$request->descriptionIssues,
                        'Maintenance',
                        [$hrEmployee->id],
                        null,
                    );
                }
            }
            
            DB::commit();
            $response['status']                         =   true;
            $response['message']                        =   'Maintanance Request Created Successfully';
           
            return response()->json($response);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function staffMaintenanceReqList()
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $employee                                   =   $this->user->GetEmployee;

            $maintenanceRequests                        =   MaintanaceRequest::where('resort_id', $this->resort_id)
                                                                ->where('Raised_By', $employee->id)
                                                                ->orderBy('created_at', 'desc')
                                                                ->get();

            if ($maintenanceRequests->isEmpty()) {
                return response()->json([
                    'status'                            =>  true,
                    'message'                           =>  'No maintenance requests found',
                    'data'                              =>  []
                ]);
            }

            $response['status']                         =   true;
            $response['message']                        =   'Maintenance Requests Retrieved Successfully';
            $response['data']                           =   $maintenanceRequests;
                                                                   
            return response()->json($response);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function viewMaintenanceRequest($maintanaceId)
    {

        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $maintanaceId                                   =   base64_decode($maintanaceId, true);
            
            $maintanaceRequest                              =   MaintanaceRequest::join("employees as t3", "t3.id", "=", "maintanace_requests.Raised_By")
                                                                    ->join('inventory_modules as t2', 't2.id', '=', 'maintanace_requests.item_id')
                                                                    ->where('maintanace_requests.resort_id', $this->resort_id)
                                                                    ->where('maintanace_requests.id', $maintanaceId)
                                                                    ->first(['maintanace_requests.*','t2.ItemName']);
            
            $MaintanaceRequestChild                         =   MaintanaceRequest::join('child_maintanance_requests as t1',"t1.maintanance_request_id","=","maintanace_requests.id")
                                                                    ->where('maintanace_requests.resort_id',$this->resort_id)
                                                                    ->where('maintanace_requests.id',$maintanaceId)
                                                                    ->where('t1.ApprovedBy','!=',0)
                                                                    ->orderBy("t1.id", "ASC")
                                                                    ->get(['t1.id','t1.Status','t1.date']);
            if (!empty($maintanaceRequest->Image)) {
                $path_path                              =   config('settings.MaintanceRequest') . '/' . Auth::guard('api')->user()->resort->resort_id;
                if (!file_exists($path_path)) {
                        mkdir($path_path, 0777, true);
                    }
                $maintanaceRequest->Image               =   URL::asset($path_path . '/' . $maintanaceRequest->Image);
            }

            if (!empty($maintanaceRequest->Completed_Image )) {
                $path                              =   config('settings.MaintanceRequest') . '/' . Auth::guard('api')->user()->resort->resort_id;
                $maintanaceRequest->Completed_Image     =   URL::asset($path . '/' . $maintanaceRequest->Completed_Image);
            }
                                                                                                            
            $displayedStatuses = ['data' => []];

                foreach($MaintanaceRequestChild as $m)
                {
                    if(!in_array($m->Status, $displayedStatuses))
                    {
                        $displayedStatuses['data'][] = [
                            'status' => $m->Status,
                            'date' => $m->date
                        ];
                    }
                }
            
                $assignMaintReqStaffDetails                 =   ChildMaintananceRequest::join("employees as t3", "t3.id", "=", "child_maintanance_requests.ApprovedBy")
                                                                    ->join("resort_admins as t1", "t1.id", "=", "t3.Admin_Parent_id")
                                                                    ->where('child_maintanance_requests.Status','In-Progress')
                                                                    ->where('child_maintanance_requests.maintanance_request_id',$maintanaceId)
                                                                    ->select('t3.id','t1.first_name', 't1.last_name','t1.id as Parentid','t1.personal_phone')
                                                                    ->first();
                if($assignMaintReqStaffDetails) {
                    $assignMaintReqStaffDetails->profileImg     =   Common::getResortUserPicture($assignMaintReqStaffDetails->Parentid);
                }                                                    
                
            $maintanaceArr = [
                'maintanace_request'                        =>  $maintanaceRequest,
                'status'                                    =>  $displayedStatuses,
                'assign_staff'                              =>  $assignMaintReqStaffDetails ,
            ];
            $response['status']                             =   true;
            $response['message']                            =   'Accommodation Details Fetched Successfully for the Employee';
            $response['accomodation_data']                  =   $maintanaceArr;

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }

    }

    public function completeTaskStatus(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'request_id'                                    =>  'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        try {

            
            DB::beginTransaction();
            $employee_id                                    =   $this->user->GetEmployee->id;
            $employee                                       =   $this->user->GetEmployee;
            $requestId                                      =   $request->input('request_id');
           
            $maintanaceRequest = MaintanaceRequest::where('id',$requestId)->where('Status','Resolvedawaiting')->first();
            
                if (!$maintanaceRequest) {
                    return response()->json(['success' => false, 'message' => 'Already complete the task'], 200);
                }

            if($maintanaceRequest) {
                $maintanaceRequest                          =   MaintanaceRequest::where('id',$requestId)->where('Status','Resolvedawaiting')->update([
                                                                    'Status'     => "Closed",
                                                                ]);
                ChildMaintananceRequest::create([
                    'maintanance_request_id'                    =>  $requestId,
                    'resort_id'                                 =>  $this->resort_id,
                    'ApprovedBy'                                =>  $employee_id ,
                    'Status'                                    =>  'Closed',
                    'rank'                                      =>  $employee->rank,
                    'date'                                      =>  date('Y-m-d'),
                ]);
            }
            
            DB::commit(); // Commit Transaction

            $response['status']                             =   true;
            $response['message']                            =   'Complete the task successfully.';
            return response()->json($response);

            
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback if any error occurs
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function editMaintenanceRequests(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'request_id'                                =>  'required',
            'item_id'                                   =>  'required',
            'building_id'                               =>  'required',
            'FloorNo'                                   =>  'required',
            'RoomNo'                                    =>  'required',
            'descriptionIssues'                         =>  'required',
            'priority'                                  =>  'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        try {
            DB::beginTransaction();
            $date                                       =   DateTime::createFromFormat('d/m/Y', $request->date);
            $date                                       =   isset($request->date) ? $date->format('Y-m-d') :  date('Y-m-d');
            $path_path                                  =   config('settings.MaintanceRequest') . '/' . Auth::guard('api')->user()->resort->resort_id;
            
            $maintanaceRequestEdit                      =   MaintanaceRequest::find($request->request_id);
            
            if (!$maintanaceRequestEdit) {
                return response()->json(['success' => false, 'message' => 'Maintenance Request not found'], 200);
            }

            $maintanaceRequestEdit->resort_id           =  $this->resort_id;
            $maintanaceRequestEdit->item_id             =  $request->item_id;
            $maintanaceRequestEdit->building_id         =  $request->building_id;
            $maintanaceRequestEdit->FloorNo             =  $request->FloorNo;
            $maintanaceRequestEdit->RoomNo              =  $request->RoomNo;
            $maintanaceRequestEdit->descriptionIssues   =  $request->descriptionIssues;
            $maintanaceRequestEdit->priority            =  isset($request->priority)? $request->priority:'Low';
             
            if ($request->hasFile('Image')) {
                $imageFile                              =   $request->file('Image');
                $imageName                              =   time() . '_' . $imageFile->getClientOriginalName();
                $imageFile->move($path_path, $imageName);
                $maintanaceRequestEdit->Image           =   $imageName;
            }

            $maintanaceRequestEdit->save();
            
            DB::commit();
            $response['status']                         =   true;
            $response['message']                        =   'Maintanance Request Updated Successfully';
           
            return response()->json($response);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }
    
    private function formatMaintenanceRow($row, $EscalationDay, $inventoryItems)
    {
        $row->RequestedBy                                   =   $row->first_name . ' ' . $row->last_name;
        $row->AssgingedStaff                                =   $row->Assigned_To;
        $row->Location                                      =   optional($row->BuilidngData)->BuildingName . ', Room No - ' . $row->RoomNo . ', Floor No -' . $row->FloorNo;
        // $row->Priority                                      =   $row->priority;
        $row->NewStatus                                     =   $row->Status;
        $row->Date                                          =   date('d M Y', strtotime($row->date));

        // **Calculate Escalation Time**
        $daysSinceRequest                                   =   now()->diffInDays(Carbon::parse($row->date));
        $row->EscalationTimeOver                            =   ($daysSinceRequest > ($EscalationDay->EscalationDay ?? 0)) ? 'Alert' : 'Regular';

        // **Set Profile Image**
        $row->profileImg                                    =   Common::getResortUserPicture($row->Parentid);

        // **Get Inventory Item Name**
        $row->EffectedAmenity                               =   ucfirst($inventoryItems[$row->item_id] ?? 'N/A');

        // **Assigned Staff Details**
        if (!empty($row->Assigned_To)) { 
            $emp                                            =   Common::GetEmployeeDetails($row->Assigned_To);
            $row->Assign_profileImg                         =   Common::getResortUserPicture($emp->Parent_id);
            $row->Assign_toName                             =   $emp->first_name . ' ' . $emp->last_name;
        }

        return $row;
    }
}
