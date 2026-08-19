<?php

namespace App\Http\Controllers\Resorts\Accommodation;

use DB;
use FontLib\TrueType\Collection;
use URL;
use Auth;
use DateTime;
use Validator;
use Carbon\Carbon;
use App\Helpers\Common;
use App\Helpers\StorageHelper;
use App\Models\Employee;
use App\Models\EscalationDay;
use App\Models\BuildingModel;
use App\Models\ResortDepartment;
use App\Models\AssingAccommodation;
use App\Models\AccommodationType;
use App\Models\InventoryCategoryModel;
use App\Models\InventoryModule;
use App\Models\AvailableAccommodationInvItem;
use App\Models\AvailableAccommodationModel;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ChildMaintananceRequest;
use App\Models\MaintanaceRequest;

class MaintananceContorller extends Controller
{
    protected $resort;
    protected $underEmp_id=[];

    private function createNotification($userId, $type, $message, $requestId = null)
    {
        \DB::table('resort_notifications')->insert([
            'resort_id' => $this->resort->resort_id,
            'user_id' => $userId,
            'module' => 'Accommodation',
            'type' => $type,
            'message' => $message,
            'status' => 'unread',
            'request_id' => $requestId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Was DB-only — every one of this helper's callers got an in-app
        // row but never a real push unless they separately (and only
        // sometimes) fired one themselves. Centralized here so HR, the
        // requester, and whoever it's forwarded/assigned to all get a real
        // push consistently, not just an in-app row some of the time.
        try {
            $employee = Employee::find($userId);
            $tokens = $employee ? Common::decodeDeviceTokens($employee->device_token) : [];
            if (!empty($tokens)) {
                Common::sendPushNotificationForMobile($tokens, $type, $message, 'Accommodation', null, null, null, null);
            }
        } catch (\Exception $e) {
            \Log::warning('Accommodation push notification failed: ' . $e->getMessage());
        }
    }

    public function __construct()
    {
        $this->resort =  auth()->guard('resort-admin')->user();
        $this->resort = $resortId = auth()->guard('resort-admin')->user();
        if(!$this->resort) return;
        if($this->resort->is_master_admin == 0){
            $reporting_to =(isset( $this->resort->GetEmployee)) ?  $this->resort->GetEmployee->id:3;
            $this->underEmp_id = Common::getSubordinates($reporting_to);
        }
        
    }

    public function CreateMaintenance()
    {
        if(Common::checkRouteWisePermission('resort.accommodation.CreateMaintenance',config('settings.resort_permissions.create')) == false){
            return redirect()->route('resort.accommodation.MaintanaceRequestlist');
        }
        $page_title = 'Create Maintenance Request';
        $InventoryItems= InventoryModule::where('resort_id',$this->resort->resort_id)->get();
        $Building= BuildingModel::where('resort_id',$this->resort->resort_id)->get();


        return view('resorts.Accommodation.Maintanance.CreateMaintenance',compact('page_title','Building','InventoryItems'));
    }

    public function getEmployeeAccommodation(Request $request)
    {
        $empId = $request->emp_id;
        $resortId = $this->resort->resort_id;

        $data = DB::table('assing_accommodations as aa')
            ->join('available_accommodation_models as a', 'a.id', '=', 'aa.available_a_id')
            ->join('building_models as b', 'b.id', '=', 'a.BuildingName')
            ->where('aa.emp_id', $empId)
            ->where('aa.resort_id', $resortId)
            ->select('b.id as building_id', 'b.BuildingName', 'a.Floor', 'a.RoomNo')
            ->first();

        if ($data) {
            return response()->json([
                'success' => true,
                'has_accommodation' => true,
                'building_id' => $data->building_id,
                'building_name' => $data->BuildingName,
                'floor' => $data->Floor,
                'room' => $data->RoomNo,
            ]);
        }

        return response()->json(['success' => true, 'has_accommodation' => false]);
    }

    public function CreateMaintenanceRequest(Request $request)
    { 
        $item_id = $request->item_id;
        $building_id = $request->building_id;
        $FloorNo = $request->FloorNo;
        $RoomNo = $request->RoomNo;
        $descriptionIssues = $request->descriptionIssues;
        $priority = isset($request->priority)? $request->priority:'Low';

        $dateString = '17/01/2025';
        $date = DateTime::createFromFormat('d/m/Y', $request->date);
        $date = isset($request->date) ? $date->format('Y-m-d') :  date('Y-m-d');
        $path_path = config('settings.MaintanceRequest') . '/' . Auth::guard('resort-admin')->user()->resort->resort_id;

   
        $rank= Common::FindResortHR( Auth::guard('resort-admin')->user());

        $collection = [
            'resort_id' => $this->resort->resort_id,
            'item_id' => $item_id ?? null,
            'building_id' => $building_id,
            'FloorNo' => $FloorNo ?: null,
            'RoomNo' => $RoomNo ?: null,
            'descriptionIssues' => $descriptionIssues,
            'priority' => $priority,
            'date'=>$date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'Raised_By' => $request->raised_by ?? Auth::guard('resort-admin')->user()->GetEmployee->id,
        ];

        $validator = Validator::make($request->all(), [
                'Video' => 'nullable|file|mimes:mp4,mov|max:7168',
                'Image' => 'nullable|file|mimes:jpg,jpeg,png,gif,svg,webp,heic,heif|max:1024',
                'item_id' => 'nullable',
                'building_id' => 'required',
                'descriptionIssues' => 'required',
                'FloorNo' => 'nullable',
                'RoomNo' => 'nullable',
                'start_time' => 'nullable',
                'end_time' => 'nullable',
            ], [
                'Video.max' => 'The video size must not exceed 7 MB.',
                'Video.mimes' => 'The video must be a file of type: mp4, mov.',
                'Image.max' => 'The image size must not exceed 1 MB.',
                'Image.mimes' => 'The image must be a file of type: jpg, jpeg, png, gif, svg, webp, heic, heif.',
                'descriptionIssues.required' => 'Please enter a description.',
                'building_id.required' => 'Please select a building.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

        DB::beginTransaction();
        try {
        // Handle image upload
        // Was a raw ->move() to a plain filesystem path (uploads/MaintanceRequest/...)
        // that never existed under public/ and never touched the configured
        // storage disk — files landed outside the web root entirely and were
        // never viewable, on local OR on Wasabi (STORAGE_DRIVER=wasabi in
        // prod). StorageHelper routes through the correct disk either way.
        if ($request->hasFile('Image')) {
            $imageFile = $request->file('Image');
            $imageName = time() . '_' . $imageFile->getClientOriginalName();

            StorageHelper::put($path_path . '/' . $imageName, file_get_contents($imageFile->getRealPath()));
            $collection['Image'] = $imageName;
        }

        // Handle video upload
        if ($request->hasFile('Video')) {
            $videoFile = $request->file('Video');
            $videoName = time() . '_' . $videoFile->getClientOriginalName();
            StorageHelper::put($path_path . '/' . $videoName, file_get_contents($videoFile->getRealPath()));
            $collection['Video'] = $videoName;
        }
     
            
            // Create the maintenance request
            $m_id = MaintanaceRequest::create($collection);

            if(isset($m_id->id))
            {

                $status = ['Open','Assinged','In-Progress','Resolvedawaiting','Closed'];

                    ChildMaintananceRequest::create([
                        'maintanance_request_id' => $m_id->id,
                        'resort_id' => $this->resort->resort_id,
                        'ApprovedBy' =>0,
                        'Status' =>'pending',
                        'rank'=> $rank,
                        'date'=>date('Y-m-d'),
                    ]);

                    // Notify HR about new maintenance request
                    $hrEmployees = Employee::where('resort_id', $this->resort->resort_id)->whereIn('rank', [3])->pluck('id');
                    foreach ($hrEmployees as $hrId) {
                        $this->createNotification($hrId, 'Maintenance Request', 'New maintenance request submitted: ' . $descriptionIssues, $m_id->id);
                    }

            }

            // Send push notification to employees in the building
            try {
                $buildingName = \App\Models\BuildingModel::find($building_id)->BuildingName ?? 'Building';
                $title = 'Maintenance Event - ' . $buildingName;
                $body = $descriptionIssues;
                $moduleName = 'Accommodation';

                // Get employees assigned to this building
                $query = \App\Models\AssingAccommodation::join('available_accommodation_models as a', 'a.id', '=', 'assing_accommodations.available_a_id')
                    ->join('employees as e', 'e.id', '=', 'assing_accommodations.emp_id')
                    ->where('a.BuildingName', $building_id)
                    ->where('assing_accommodations.resort_id', $this->resort->resort_id)
                    ->where('assing_accommodations.emp_id', '!=', 0);

                // If floor specified, filter by floor
                if (!empty($FloorNo)) {
                    $query->where('a.Floor', $FloorNo);
                }
                // If room specified, filter by room
                if (!empty($RoomNo)) {
                    $query->where('a.RoomNo', $RoomNo);
                }

                // Get employee IDs for in-app notifications
                $buildingEmployeeIds = (clone $query)->pluck('e.id')->unique()->toArray();

                // Create in-app notifications for building employees
                $buildingModel = \App\Models\BuildingModel::find($building_id);
                $locationText = $buildingModel ? $buildingModel->BuildingName : 'Building';
                foreach ($buildingEmployeeIds as $empId) {
                    $this->createNotification($empId, 'Maintenance Event', 'New event in ' . $locationText . ': ' . $descriptionIssues, $m_id->id);
                }

                // Notify HR employees
                $hrEmployees = Employee::where('resort_id', $this->resort->resort_id)->whereIn('rank', [3])->pluck('id');
                foreach ($hrEmployees as $hrId) {
                    if (!in_array($hrId, $buildingEmployeeIds)) {
                        $this->createNotification($hrId, 'Maintenance Event', 'New event in ' . $locationText . ': ' . $descriptionIssues, $m_id->id);
                    }
                }

                // Notify event creator if not already notified
                $creatorId = Auth::guard('resort-admin')->user()->GetEmployee->id ?? null;
                if ($creatorId && !in_array($creatorId, $buildingEmployeeIds) && !$hrEmployees->contains($creatorId)) {
                    $this->createNotification($creatorId, 'Maintenance Event', 'Event created in ' . $locationText . ': ' . $descriptionIssues, $m_id->id);
                }

                $deviceTokens = $query->whereNotNull('e.device_token')
                    ->where('e.device_token', '!=', '')
                    ->pluck('e.device_token')
                    ->unique()
                    ->toArray();

                if (!empty($deviceTokens)) {
                    Common::sendPushNotificationForMobile($deviceTokens, $title, $body, $moduleName, 'pending', null, null, null);
                }
            } catch (\Exception $e) {
                \Log::warning('Push notification failed for maintenance event: ' . $e->getMessage());
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Event created successfully'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Save', 'message' => 'Failed to create event: ' . $e->getMessage()], 500);
        }
    }

    public function HrForwardToHODManitenanceRequest(Request $request)
    {
        $HOD_id = $request->HOD_id;
        $task_id = base64_decode($request->task_id);

        // task_id/HOD_id were never checked against the caller's resort —
        // let a caller reassign/reopen another resort's maintenance
        // request to an arbitrary employee id.
        $mainRequest = MaintanaceRequest::where('id', $task_id)->where('resort_id', $this->resort->resort_id)->first();
        if (!$mainRequest) {
            return response()->json(['success' => false, 'message' => 'Maintenance request not found'], 404);
        }
        if (!Employee::where('id', $HOD_id)->where('resort_id', $this->resort->resort_id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Employee not found'], 404);
        }

        DB::beginTransaction();
        try{
            $mainRequest->update(['status'=>'Open','date'=>date('Y-m-d'),'Assigned_To'=>$HOD_id]);
            ChildMaintananceRequest::where("maintanance_request_id",$task_id)->where('resort_id', $this->resort->resort_id)->update(['ApprovedBy'=>$this->resort->GetEmployee->id,'Status'=>'Open']);
            ChildMaintananceRequest::create([
                'maintanance_request_id' => $task_id,
                'resort_id' => $this->resort->resort_id,
                'ApprovedBy' =>0,
                'Status' =>'pending',
                'date'=>date('Y-m-d'),

            ]);


            // Notify HOD (createNotification() now sends the push too)
            try {
                $this->createNotification($HOD_id, 'Maintenance Forwarded', 'A maintenance request has been forwarded to you: ' . ($mainRequest->descriptionIssues ?? ''), $task_id);
            } catch (\Exception $e) {
                \Log::warning('Notification failed: ' . $e->getMessage());
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Request Forwarded successfully.'], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Forward Request'], 500);
        }
    }

    public function MaintanaceRequestlist(Request $request)
    {
        if(Common::checkRouteWisePermission('resort.accommodation.MaintanaceRequestlist',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }
        
        $page_title = 'Maintenance Request List';
        $ResortDepartment = $request->ResortDepartment;
        $search = $request->Search;
        $EscalationDay = EscalationDay::where('resort_id',$this->resort->resort_id)->first();

        $EscalationDay =  isset($EscalationDay)?$EscalationDay->EscalationDay: config('settings.EscalationDay');
        
        if($request->flag =="true")
        {      

            if($request->ajax())
            {

              $currentEmployee2 = $this->resort->GetEmployee;
              $isHrOrAdmin2 = $this->resort->is_master_admin == 1;
              if ($currentEmployee2) {
                  $empDept2 = \App\Models\ResortDepartment::find($currentEmployee2->Dept_id);
                  if ($empDept2 && (
                      stripos($empDept2->name, 'human') !== false ||
                      stripos($empDept2->name, 'hr') !== false ||
                      $currentEmployee2->rank == 3
                  )) {
                      $isHrOrAdmin2 = true;
                  }
              }

              $MaintanaceRequest = MaintanaceRequest::join("employees as t3","t3.id","maintanace_requests.Raised_By")
                                        ->join("resort_admins as t1","t1.id","t3.Admin_Parent_id")
                                        ->join("resort_departments as t4","t4.id","t3.Dept_id")
                                        ->where('maintanace_requests.resort_id', $this->resort->resort_id)
                                        ->whereIn('maintanace_requests.Status', ['Closed']);

              if (!$isHrOrAdmin2 && $currentEmployee2) {
                  $MaintanaceRequest->where('maintanace_requests.Assigned_To', $currentEmployee2->id);
              }

                if(!empty($ResortDepartment))
                {
                    $MaintanaceRequest->where('t3.Dept_id', $ResortDepartment);
                }
                if (!empty($search))
                {
                    $MaintanaceRequest->where(function ($query) use ($search) {
                        $query->where('t1.first_name', 'LIKE', '%' . $search . '%')
                              ->orWhere('t1.last_name', 'LIKE', '%' . $search . '%')
                              ->orWhereRaw("CONCAT(t1.first_name, ' ', t1.last_name) LIKE ?", ['%' . $search . '%'])
                              ->orWhere('maintanace_requests.RoomNo', 'LIKE', '%' . $search . '%')
                              ->orWhere('maintanace_requests.FloorNo', 'LIKE', '%' . $search . '%')
                              ->orWhere('maintanace_requests.priority', 'LIKE', '%' . $search . '%');
                    });
                }
                $MaintanaceRequest = $MaintanaceRequest->orderBy('maintanace_requests.date', 'desc')
                        ->get(['t1.id as Parentid', 't1.first_name', 't1.last_name', 'maintanace_requests.*'])
                        ->map(function ($row) use($EscalationDay)
                        {
                            $row->RequestedBy = $row->first_name . ' ' . $row->last_name;
                            $row->AssgingedStaff = $row->Assigned_To;
                            $row->Location = $row->BuilidngData->BuildingName . (!empty($row->RoomNo) ? ', Room No - ' . $row->RoomNo : '') . (!empty($row->FloorNo) ? ', Floor No - ' . $row->FloorNo : '');
                            $row->Priority = $row->priority;
                            $row->NewStatus = $row->Status;

                            $row->Date =date('d M Y',strtotime($row->date));
                            $daysSinceRequest = now()->diffInDays(Carbon::parse($row->date));

                            $row->EscalationTimeOver = ($daysSinceRequest > $EscalationDay) ? 'Alert' : 'Regular';
                            $row->profileImg = Common::getResortUserPicture($row->Parentid);
                            $InventoryModule = InventoryModule::where('resort_id', $this->resort->resort_id)
                                ->where("id", $row->item_id)
                                ->first('ItemName');
                            $row->EffectedAmenity = $InventoryModule ? ucfirst($InventoryModule->ItemName) : 'N/A';
                            if(isset($row->Assigned_To) && $row->Assigned_To !=0)                            
                            {
                                $emp = Common::GetEmployeeDetails($row->Assigned_To, $this->resort->resort_id);
                                if ($emp) {
                                    $row->Assign_profileImg = Common::getResortUserPicture($emp->Parent_id);
                                    $row->Assign_toName     = $emp->first_name.' '.$emp->last_name;
                                }
                            }
                            return $row;
                        });


        
                return datatables()->of($MaintanaceRequest)
                ->addColumn('action', function ($row) {
                $id = base64_encode($row->id);
                    return '<a href="'.route('resort.accommodation.MainRequestDetails',$id ).'" class="btn-tableIcon btnIcon-skyblue mainRequetDetails" data-task_id="'.$id.'"><i class="fa-regular fa-eye"></i></a>';

                })
                ->editColumn('RequestedBy', function ($row) {
                return   '<div class="tableUser-block">
                                        <div class="img-circle"><img src="'.$row->profileImg.'" alt="user">
                                        </div>
                                        <span class="userApplicants-btn">'.$row->RequestedBy.'</span>
                                    </div>';

                })
                ->editColumn('EffectedAmenity', function ($row) {
                    return e($row->EffectedAmenity);
                })
                ->editColumn('Location', function ($row) {
                    return e($row->Location);
                })
                ->editColumn('Priority', function ($row) {
                    $string ='';
                    if($row->Priority == 'Low')
                    {
                        $string = '<span class="badge badge-blueNew border-0">Low</span>';
                    }
                    elseif($row->Priority == 'Medium')
                    {
                        $string = '<span class="badge badge-themeWarning border-0">Medium</span>';
                    }
                    elseif($row->Priority == 'High')
                    {
                        $string = '<span class="badge badge-danger">High</span>';
                    }
                    return $string;
                })
                ->editColumn('AssgingedStaff', function ($row) {

                    if(isset($row->AssgingedStaff))
                    {
                        return '<div class="tableUser-block">
                                    <div class="img-circle"><img src="'.$row->Assign_profileImg.'" alt="user">
                                    </div>
                                    <span class="userApplicants-btn">'.$row->Assign_toName.'</span>
                                </div>' ;
                    }
                    else
                    {
                        return '<span class="badge badge-themeWarning border-0">Not Assigned Yet</span>';
                    }
                })
                ->editColumn('Date', function ($row) {
                    return $row->Date;
                })
                ->editColumn('Status', function ($row)
                {
                        return '<span class="badge badge-orange">Close</span>';
                })

                ->rawColumns(['RequestedBy','Priority','action','AssgingedStaff','Status'])
                ->make(true);
            }
        }
        else
        {

           $currentEmployee = $this->resort->GetEmployee;
           $isHrOrAdmin = $this->resort->is_master_admin == 1;
           if ($currentEmployee) {
               $empDept = \App\Models\ResortDepartment::find($currentEmployee->Dept_id);
               if ($empDept && (
                   stripos($empDept->name, 'human') !== false ||
                   stripos($empDept->name, 'hr') !== false ||
                   $currentEmployee->rank == 3
               )) {
                   $isHrOrAdmin = true;
               }
           }

           $MaintanaceRequest =  MaintanaceRequest::join("employees as t3","t3.id","maintanace_requests.Raised_By")
                ->leftjoin("resort_admins as t1","t1.id","t3.Admin_Parent_id")
                ->leftjoin("resort_departments as t2","t2.id","t3.Dept_id")
                ->where('maintanace_requests.resort_id', $this->resort->resort_id)
                ->whereNotIn('maintanace_requests.Status', ['Closed', 'On-Hold'])
                ->leftjoin("resort_admins as t4","t4.id","maintanace_requests.Assigned_To");

           // Non-HR users only see requests assigned to them
           if (!$isHrOrAdmin && $currentEmployee) {
               $MaintanaceRequest->where('maintanace_requests.Assigned_To', $currentEmployee->id);
           }
            if(!empty($ResortDepartment))
            {
                $MaintanaceRequest->where('t3.Dept_id', $ResortDepartment);
            }
            if (!empty($search))
            {
                $MaintanaceRequest->where(function ($query) use ($search) {
                    $query->where('t1.first_name', 'LIKE', '%' . $search . '%')
                          ->orWhere('t1.last_name', 'LIKE', '%' . $search . '%')
                          ->orWhereRaw("CONCAT(t1.first_name, ' ', t1.last_name) LIKE ?", ['%' . $search . '%'])
                          ->orWhere('maintanace_requests.RoomNo', 'LIKE', '%' . $search . '%')
                          ->orWhere('maintanace_requests.FloorNo', 'LIKE', '%' . $search . '%')
                          ->orWhere('maintanace_requests.priority', 'LIKE', '%' . $search . '%');
                });
            }
            $MaintanaceRequest = $MaintanaceRequest->orderBy('maintanace_requests.date', 'desc')
            ->get(['t1.id as Parentid','t1.first_name','t1.last_name','t4.id as Assign_Parentid','t4.first_name as Assign_first_name','t4.last_name as Assign_last_name','maintanace_requests.*'])
            ->map(function ($row) use($EscalationDay)
                    {
                        $row->RequestedBy = $row->first_name . ' ' . $row->last_name;
                        $row->AssgingedStaff = $row->Assigned_To;
                        $row->Location = $row->BuilidngData->BuildingName . (!empty($row->RoomNo) ? ', Room No - ' . $row->RoomNo : '') . (!empty($row->FloorNo) ? ', Floor No - ' . $row->FloorNo : '');
                        $row->Priority = $row->priority;
                        $row->Date =date('d M Y',strtotime($row->date));
                        $daysSinceRequest = now()->diffInDays(Carbon::parse($row->date));
                        $row->NewStatus = $row->Status;

                        $row->EscalationTimeOver = ($daysSinceRequest > $EscalationDay) ? 'Alert' : 'Regular';
                        $row->profileImg = Common::getResortUserPicture($row->Parentid);
                        $InventoryModule = InventoryModule::where('resort_id', $this->resort->resort_id)
                            ->where("id", $row->item_id)
                            ->first('ItemName');
                        $row->EffectedAmenity = $InventoryModule ? ucfirst($InventoryModule->ItemName) : 'N/A';
                        if(isset($row->Assigned_To) && $row->Assigned_To !=0)                        {
                            $emp = Common::GetEmployeeDetails($row->Assigned_To, $this->resort->resort_id);
                            if ($emp) {
                                $row->Assign_profileImg = Common::getResortUserPicture($emp->Parent_id);
                                $row->Assign_toName     = $emp->first_name.' '.$emp->last_name;
                            }
                        }
                        return $row;
                    });

            if($request->ajax())
            {  
                    
                $edit_class = '';
                $delete_class = '';
                if(Common::checkRouteWisePermission('resort.accommodation.MaintanaceRequestlist',config('settings.resort_permissions.edit')) == false){
                    $edit_class = 'd-none';
                }
                if(Common::checkRouteWisePermission('resort.accommodation.MaintanaceRequestlist',config('settings.resort_permissions.edit')) == false){
                    $delete_class = 'd-none';
                }

                return datatables()->of($MaintanaceRequest)
                ->addColumn('action', function ($row) use ($edit_class) {
                $id = base64_encode($row->id);
                    $string='';
                    if($row->Status !="Open")
                    {
                        $string1 = '<a href="javascript:void(0)" class="correct-btn ForwardToHOD  '.$edit_class.'" data-req_id="'.$id.'" data-Location="'.$row->Location.'" data-EffectedAmenity="'. $row->EffectedAmenity .'" data-description="'.e($row->Description).'"><i class="fa-solid fa-check"></i></a>';

                    }
                    else
                    {
                        $string1='';
                    }
                     $string.='
                                <a href="'.route('resort.accommodation.MainRequestDetails',$id ).'" class="btn-tableIcon btnIcon-skyblue mainRequetDetails '.$edit_class.'" data-task_id="'.$id.'"><i class="fa-regular fa-eye"></i></a>
                                '. $string1.'<a href="javascript:void(0)" class="btn-tableIcon btnIcon-orangeDark OnHoldRequest '.$edit_class.'" data-flag="On-Hold" data-task_id="'.$id.'" ><i class="fa-regular fa-hand"></i></a>
                                <a href="javascript:void(0)" class="close-btn OnHoldRequest '.$edit_class.'" data-flag="Closed" data-task_id="'.$id.'"><i class="fa-solid fa-xmark"></i></a>
                            ';
                    return  $string;
                })
                ->editColumn('RequestedBy', function ($row) {
                return   '<div class="tableUser-block">
                                        <div class="img-circle"><img src="'.$row->profileImg.'" alt="user">
                                        </div>
                                        <span class="userApplicants-btn">'.$row->RequestedBy.'</span>
                                    </div>';

                })
                ->editColumn('EffectedAmenity', function ($row) {
                    return e($row->EffectedAmenity);
                })
                ->editColumn('Location', function ($row) {
                    return e($row->Location);
                })
                ->editColumn('Priority', function ($row) {
                    $string ='';
                    if($row->Priority == 'Low')
                    {
                        $string = '<span class="badge badge-blueNew border-0">Low</span>';
                    }
                    elseif($row->Priority == 'Medium')
                    {
                        $string = '<span class="badge badge-themeWarning border-0">Medium</span>';
                    }
                    elseif($row->Priority == 'High')
                    {
                        $string = '<span class="badge badge-danger">High</span>';
                    }
                    return $string;
                })
                ->editColumn('AssgingedStaff', function ($row) {

                    if(isset($row->AssgingedStaff))
                    {
                        return '<div class="tableUser-block">
                                    <div class="img-circle"><img src="'.$row->Assign_profileImg.'" alt="user">
                                    </div>
                                    <span class="userApplicants-btn">'.$row->Assign_toName.'</span>
                                </div>' ;
                    }
                    else
                    {
                        return '<span class="badge badge-themeWarning border-0">Not Assigned Yet</span>';
                    }
                })
                ->editColumn('Date', function ($row) {
                    return $row->Date;
                })
                ->editColumn('Status', function ($row)
                {

                    if($row->Status=='pending')
                    {
                        return '<span class="badge badge-themeSkyblue">Pending</span>';
                    }
                    elseif($row->Status=='In-Progress')
                    {
                        return '<span class="badge badge-themeBlue">In-Progress</span>';
                    }
                    elseif($row->Status=='Open')
                    {
                        return '<span class="badge badge-orange">Open</span>';
                    }
                    elseif($row->Status=='Assigned')
                    {
                        return '<span class="badge badge-themeWarning">Assigned</span>';
                    }
                    elseif($row->Status=='ResolvedAwaiting')
                    {
                        return '<span class="badge badge-info">Resolved Awaiting</span>';
                    }

                    elseif($row->Status=='Rejected')
                    {
                        return '<span class="badge badge-danger">Rejected</span>';
                    }
                })
                ->rawColumns(['RequestedBy','Priority','action','AssgingedStaff','Status'])
                ->make(true);
            }
        }

        $Employee = Employee::join('resort_admins','resort_admins.id',"=",'employees.Admin_Parent_id')
                            ->join('resort_departments as rd', 'rd.id', '=', 'employees.Dept_id')
                            ->where('employees.resort_id', $this->resort->resort_id)
                            ->where(function($q) {
                                $q->where('rd.name', 'like', '%engineer%')
                                  ->orWhere('rd.name', 'like', '%maintenance%')
                                  ->orWhere('rd.name', 'like', '%technical%');
                            })
                            ->whereIn('employees.rank', [1, 2])
                            ->where('employees.status', 'Active')
                            ->get(['employees.*','resort_admins.first_name','resort_admins.last_name']);
        $ResortDepartment = ResortDepartment::where('resort_id', $this->resort->resort_id)->get();

        return view('resorts.Accommodation.Maintanance.MaintanaceRequestlist',compact('page_title','MaintanaceRequest','Employee','ResortDepartment','EscalationDay'));
    }

    public function HrRejeactedRequest(Request $request)
    {

        $reason = $request->reason;
        $id = base64_decode($request->task_id);
       
       
        DB::beginTransaction();
        try
        {
            $mainRequest = MaintanaceRequest::where("resort_id",$this->resort->resort_id)->where("id",$id)->first();
            $mainRequest->update(['status'=>'Rejected',"RejactionReason"=>$reason]);

            // Notify the employee who raised the request
            try {
                $this->createNotification($mainRequest->Raised_By, 'Maintenance Rejected', 'Your maintenance request has been rejected. Reason: ' . $reason, $id);

                $raisedBy = Employee::find($mainRequest->Raised_By);
                if ($raisedBy && $raisedBy->device_token) {
                    Common::sendPushNotificationForMobile([$raisedBy->device_token], 'Maintenance Request Rejected', 'Your request has been rejected. Reason: ' . $reason, 'Accommodation', 'Rejected', null, null, null);
                }
            } catch (\Exception $e) {
                \Log::warning('Push notification failed: ' . $e->getMessage());
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => "Request Rejected Successfully"], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' =>$msgF], 500);
        }
    
    }
    public function MainRequestOnHold(Request $request)
    {
        $task_id = base64_decode($request->task_id);
        $flag = $request->flag;

        // task_id was never checked against the caller's resort — let a
        // caller hold/close any other resort's maintenance request.
        $mainRequest = MaintanaceRequest::where('id', $task_id)->where('resort_id', $this->resort->resort_id)->first();
        if (!$mainRequest) {
            return response()->json(['success' => false, 'message' => 'Maintenance request not found'], 404);
        }

        if($flag=='On-Hold')
        {
            $msgs ='The request has been successfully placed on hold.';
            $msgF='Failed to Place the request on hold';
        }
        else
        {
            $msgs ='The request has been successfully Closed.';
            $msgF = 'Failed to Closed request';
        }

        DB::beginTransaction();
        try
        {
            if($flag=='On-Hold')
            {
                $status ="On-Hold";
                $reason = $request->input('reason');
                $mainRequest->update(['status'=>$status,"ReasonOnHold"=>$reason]);
            }
            else
            {
                $status="Closed";
                $mainRequest->update(['status'=>$status]);

                ChildMaintananceRequest::where("maintanance_request_id",$task_id)->where('resort_id', $this->resort->resort_id)->update(['Status'=>'Closed']);

            }


            // Notify the employee who raised the request
            try {
                $notifType = $flag == 'On-Hold' ? 'Maintenance On Hold' : 'Maintenance Resolved';
                $notifMsg = $flag == 'On-Hold' ? 'Your maintenance request has been placed on hold.' : 'Your maintenance request has been resolved and closed.';
                $this->createNotification($mainRequest->Raised_By, $notifType, $notifMsg, $task_id);

                $raisedBy = Employee::find($mainRequest->Raised_By);
                if ($raisedBy && $raisedBy->device_token) {
                    $notifTitle = $flag == 'On-Hold' ? 'Maintenance Request On Hold' : 'Maintenance Request Closed';
                    $notifBody = $flag == 'On-Hold' ? 'Your request has been placed on hold.' : 'Your maintenance request has been resolved and closed.';
                    Common::sendPushNotificationForMobile([$raisedBy->device_token], $notifTitle, $notifBody, 'Accommodation', $status, null, null, null);
                }
            } catch (\Exception $e) {
                \Log::warning('Push notification failed: ' . $e->getMessage());
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => $msgs], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' =>$msgF], 500);
        }
    }

    public function MainRequestDetails($id)
    {

        
        if(Common::checkRouteWisePermission('resort.accommodation.CreateMaintenanceRequest',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }
        $id =base64_decode($id);
        $page_title="Maintenance Request Details";
        $MaintanaceRequest = MaintanaceRequest::join("employees as t3","t3.id","maintanace_requests.Raised_By")
                                            ->join("resort_admins as t1","t1.id","t3.Admin_Parent_id")
                                            ->join("resort_departments as t4","t4.id","t3.Dept_id")
                                            ->where('maintanace_requests.resort_id',$this->resort->resort_id)
                                            // ->where('maintanace_requests.Status',"!=",'Closed')
                                            ->where("maintanace_requests.id",$id)
                                            ->first(['t1.id as Parentid','t1.first_name','t1.last_name','maintanace_requests.*']);
        $MaintanaceRequestChild =MaintanaceRequest::join('child_maintanance_requests as t1',"t1.maintanance_request_id","=","maintanace_requests.id")
                                                    ->where('maintanace_requests.resort_id',$this->resort->resort_id)
                                                    ->where('maintanace_requests.id',$id)
                                                    ->where('t1.ApprovedBy','!=',0)
                                                    ->get(['t1.id','t1.Status']);


            $displayedStatuses = [];

                foreach($MaintanaceRequestChild as $m)
                {
                    if(!in_array($m->Status, $displayedStatuses))
                    {
                        $displayedStatuses[] = $m->Status;

                    }
                }
               
                $MaintanaceRequestChildDetails = MaintanaceRequest::join('child_maintanance_requests as t2', 't2.maintanance_request_id', '=', 'maintanace_requests.id')
                                                                    ->where('maintanace_requests.resort_id',$this->resort->resort_id)
                                                                    // ->where('maintanace_requests.Status',"!=",'Closed')
                                                                    ->where("maintanace_requests.id",$id)
                                                                    ->where('ApprovedBy','!=',0)
                                                                    ->get(['t2.Status','t2.date','maintanace_requests.Assigned_To','maintanace_requests.building_id','maintanace_requests.FloorNo','maintanace_requests.RoomNo','maintanace_requests.created_at as SubmitedDate'])->map(function ($row)
                                                                    {

                                                                        $row->submitedDate = date('d M Y',strtotime($row->SubmitedDate));

                                                                        $AssingedTask = Employee::join('resort_admins', 'resort_admins.id', '=', 'employees.Admin_Parent_id')
                                                                        ->where('employees.id', $row->Assigned_To)
                                                                        ->first(['resort_admins.first_name', 'resort_admins.last_name', 'resort_admins.id as Parentid']);
                                                                                $row->EmployeeName = isset($AssingedTask) ? ucfirst($AssingedTask->first_name . ' ' . $AssingedTask->last_name) : '-';
                                                                                $row->profileImg = isset($AssingedTask) ?  Common::getResortUserPicture($AssingedTask->Parentid): '-';
                                                                                $row->date = date('d M Y',strtotime($row->date));
                                                                        return $row;
                                                                    });

                    $displayedStatusesDetails = [];
                    foreach($MaintanaceRequestChildDetails as $k=>$m)
                    {
                        if($k==0)
                        {
                            $displayedStatusesDetails['SubmitedRequest'][] =  $m->submitedDate;
                        }

                        if(!in_array($m->Status, $displayedStatusesDetails))
                        {

                            $displayedStatusesDetails[$m->Status][] =  [$m->date,$m->profileImg,$m->EmployeeName];

                        }
                    }


        if(isset($MaintanaceRequest))
        {
            $AssingAccommodation = AssingAccommodation::join('available_accommodation_models as t1', 't1.id', '=', 'assing_accommodations.available_a_id')
            ->where("t1.BuildingName", $MaintanaceRequest->building_id)
            ->where("t1.Floor", $MaintanaceRequest->FloorNo)
            ->where("t1.RoomNo", $MaintanaceRequest->RoomNo)
            ->join('employees as t2', 't2.id', '=', 'assing_accommodations.emp_id')
            ->join('resort_admins as t3', 't3.id', '=', 't2.Admin_Parent_id')
            ->orderBy('t1.created_at', 'asc')
            ->get(['t3.first_name', 't3.last_name', 't3.id as Parentid'])->map(function ($row) {
                $row->EmployeeName = ucfirst($row->first_name . ' ' . $row->last_name);
                $row->profileImg = Common::getResortUserPicture($row->Parentid);
                return $row;
            });
        }
        else
        {
            $AssingAccommodation = (object)[];
        }

             $MaintanaceRequest->RequestedBy= $MaintanaceRequest->first_name.' '.$MaintanaceRequest->last_name;
             $MaintanaceRequest->AssgingedStaff= $MaintanaceRequest->Assigned_To;
             $MaintanaceRequest->Location= $MaintanaceRequest->BuilidngData->BuildingName . (!empty($MaintanaceRequest->RoomNo) ? ', Room No - '. $MaintanaceRequest->RoomNo : '') . (!empty($MaintanaceRequest->FloorNo) ? ', Floor No - '. $MaintanaceRequest->FloorNo : '');
             $MaintanaceRequest->Priority =  $MaintanaceRequest->priority;
             $MaintanaceRequest->Date =  date('d M Y',strtotime($MaintanaceRequest->date));

            $InventoryModule= InventoryModule::where('resort_id',$this->resort->resort_id)
                                            ->where("id", $MaintanaceRequest->item_id)
                                            ->first('ItemName');
             $MaintanaceRequest->EffectedAmenity = $InventoryModule ? ucfirst($InventoryModule->ItemName) : 'N/A';
             if($MaintanaceRequest->Priority == 'Low')
             {
                $MaintanaceRequest->Priority = '<span class="badge badge-blueNew border-0">Low</span>';
             }
             elseif($MaintanaceRequest->Priority == 'Medium')
             {
                $MaintanaceRequest->Priority = '<span class="badge badge-themeWarning border-0">Medium</span>';
             }
             elseif($MaintanaceRequest->Priority == 'High')
             {
                $MaintanaceRequest->Priority = '<span class="badge badge-danger">High</span>';
             }
             if(!isset($MaintanaceRequest->AssgingedStaff))
             {
                 $MaintanaceRequest->AssgingedStaff = '<span class="badge badge-themeWarning border-0">Not Assigned Yet</span>';
             }
             if($MaintanaceRequest->Status=='pending')
             {
                $MaintanaceRequest->Status='<span class="badge badge-themeSkyblue">Pending</span>';
             }
             elseif($MaintanaceRequest->Status=='In-Progress')
             {
                 $MaintanaceRequest->Status= '<span class="badge badge-themeBlue">In-Progress</span>';
             }
             elseif($MaintanaceRequest->Status=='Open')
             {
                 $MaintanaceRequest->Status= '<span class="badge badge-orange">Open</span>';
             }
             elseif($MaintanaceRequest->Status=='Assigned')
             {
                 $MaintanaceRequest->Status= '<span class="badge badge-themeWarning">Assigned</span>';
             }
             $MaintanaceRequest->RequestedBy ='<div class="tableUser-block">
                                                    <div class="img-circle"><img src="'.Common::getResortUserPicture($MaintanaceRequest->Parentid).'" alt="user">
                                                    </div>
                                                    <span class="userApplicants-btn">'.$MaintanaceRequest->RequestedBy.'</span>
                                                </div>';
            if(  $MaintanaceRequest->Image)
            {
                // Image can be either a plain filename (web upload) or a
                // json_encode(['Filename'=>..,'Child_id'=>..]) value (mobile
                // upload via AWSEmployeeFileUpload) — resolveMaintenanceAttachmentUrl()
                // handles both instead of blindly concatenating a path.
                $imageUrl = Common::resolveMaintenanceAttachmentUrl($MaintanaceRequest->Image, Auth::guard('resort-admin')->user()->resort->resort_id);
                $MaintanaceRequest->Image = $imageUrl ? '<img width="150px"; src="'.$imageUrl.'" alt="user">' : null;
            }
            if ($MaintanaceRequest->Video)
            {
                $path_path = config('settings.MaintanceRequest') . '/' . Auth::guard('resort-admin')->user()->resort->resort_id;
                $MaintanaceRequest->Video = '<iframe src="' . StorageHelper::temporaryUrl($path_path . '/' . $MaintanaceRequest->Video) . '"frameborder="0" allowfullscreen></iframe>';
            }
            return view('resorts.Accommodation.Maintanance.MaintanaceReqDetails',compact('displayedStatusesDetails','MaintanaceRequestChild','AssingAccommodation','MaintanaceRequest','page_title','displayedStatuses'));

    }
    public function HoldMaintanaceRequest(Request $request)
    {

        $search = $request->Search;

        $MaintanaceRequest = MaintanaceRequest::join("employees as t3","t3.id","maintanace_requests.Raised_By")
                ->join("resort_admins as t1","t1.id","t3.Admin_Parent_id")
                ->join("resort_departments as t4","t4.id","t3.Dept_id")
                ->join("building_models as t5","t5.id","maintanace_requests.building_id")
                ->where('maintanace_requests.resort_id', $this->resort->resort_id)
                ->whereIn('maintanace_requests.Status', [ 'On-Hold']);

            if(!empty($ResortDepartment))
            {
                $MaintanaceRequest->where('t2.Dept_id', $ResortDepartment);
            }
            if (!empty($search))
            {
                $MaintanaceRequest->where(function ($query) use ($search) {
                    $query->where('t1.first_name', 'LIKE', '%' . $search . '%')
                          ->orWhere('t1.last_name', 'LIKE', '%' . $search . '%')
                          ->orWhere('t5.BuildingName', 'LIKE', '%' . $search . '%')
                          ->orWhereRaw("CONCAT(t5.BuildingName, ' ', maintanace_requests.RoomNo,' ',maintanace_requests.FloorNo) LIKE ?", ['%' . $search . '%'])
                          ->orWhereRaw("CONCAT(t1.first_name, ' ', t1.last_name) LIKE ?", ['%' . $search . '%'])
                          ->orWhere('maintanace_requests.RoomNo', 'LIKE', '%' . $search . '%')
                          ->orWhere('maintanace_requests.FloorNo', 'LIKE', '%' . $search . '%')
                          ->orWhereRaw("DATE_FORMAT(maintanace_requests.date, '%d %b %Y') LIKE ?", ['%' . $search . '%']) // Date filter
                          ->orWhere('maintanace_requests.priority', 'LIKE', '%' . $search . '%');
                });
            }
            $MaintanaceRequest = $MaintanaceRequest->orderBy('maintanace_requests.id', 'desc')
                    ->get(['t1.id as Parentid', 't1.first_name', 't1.last_name', 'maintanace_requests.*'])
                    ->map(function ($row)
                    {
                        $row->RequestedBy = $row->first_name . ' ' . $row->last_name;
                        $row->AssgingedStaff = $row->Assigned_To;
                        $row->Location = $row->BuilidngData->BuildingName . (!empty($row->RoomNo) ? ', Room No - ' . $row->RoomNo : '') . (!empty($row->FloorNo) ? ', Floor No - ' . $row->FloorNo : '');
                        $row->Priority = $row->priority;
                        $row->Date =date('d M Y',strtotime($row->date));
                        $row->profileImg = Common::getResortUserPicture($row->Parentid);
                        $InventoryModule = InventoryModule::where('resort_id', $this->resort->resort_id)
                            ->where("id", $row->item_id)
                            ->first('ItemName');
                        $row->EffectedAmenity = $InventoryModule ? ucfirst($InventoryModule->ItemName) : 'N/A';
                        return $row;
                    });

            if($request->ajax())
            {

                return datatables()->of($MaintanaceRequest)
                ->addColumn('action', function ($row) {
                $id = base64_encode($row->id);
                    $string='';
                    if($row->Status !="Open")
                    {
                        $string1 = '<a href="javascript:void(0)" class="correct-btn ForwardToHOD" data-req_id="'.$id.'" data-Location="'.$row->Location.'"data-EffectedAmenity="'. $row->EffectedAmenity .'"><i class="fa-solid fa-check"></i></a>';
                    }
                    else
                    {
                        $string1='';
                    }
                     $string.='
                                <a href="'.route('resort.accommodation.MainRequestDetails',   $id ).'" class="btn-tableIcon btnIcon-skyblue mainRequetDetails" data-task_id="'.$id.'"><i class="fa-regular fa-eye"></i></a>
                                '.$string1.'
                                <a href="javascript:void(0)" class="close-btn OnHoldRequest" data-flag="Closed" data-task_id="'.$id.'"><i class="fa-solid fa-xmark"></i></a>
                            ';
                    return  $string;
                })
                ->editColumn('RequestedBy', function ($row) {
                return  $row->descriptionIssues;

                })
                ->editColumn('Location', function ($row) {
                    return e($row->Location);
                })
                ->editColumn('Date', function ($row) {
                    return $row->Date;
                })
                ->editColumn('Priority', function ($row) {
                    $string ='';
                    if($row->Priority == 'Low')
                    {
                        $string = '<span class="badge badge-blueNew border-0">Low</span>';
                    }
                    elseif($row->Priority == 'Medium')
                    {
                        $string = '<span class="badge badge-themeWarning border-0">Medium</span>';
                    }
                    elseif($row->Priority == 'High')
                    {
                        $string = '<span class="badge badge-danger">High</span>';
                    }
                    return $string;
                })


                ->rawColumns(['Location','RequestedBy','Priority','action','Status'])
                ->make(true);
            }
        $page_title="On Hold Requests";
        $holdAjaxRoute = route('resort.accommodation.HoldMaintanaceRequest');
        // HOD (2) and EXCOM (1) — this feeds an assignee dropdown, an
        // EXCOM-headed department previously had no one to assign to here.
        $Employee =Employee::join('resort_admins','resort_admins.id',"=",'employees.Admin_Parent_id')
                            ->where('employees.resort_id', $this->resort->resort_id)
                            ->whereIn("employees.rank",[1,2])
                            ->get(['employees.*','resort_admins.first_name','resort_admins.last_name']);
        return view('resorts.Accommodation.Maintanance.HoldMaintanaceRequest',compact('page_title','Employee','holdAjaxRoute'));
    }

    public function HodAssignToEmp(Request $request)
    {
     
        $emp_id = $request->emp_id;

        $task_id = base64_decode($request->task_id);
        try
        {
            $check = ChildMaintananceRequest::where('maintanance_request_id',$task_id)->where('resort_id',$this->resort->resort_id)->where('ApprovedBy',0)->first();
            // emp_id (the assignee) was never checked against the caller's
            // resort before being written as Assigned_To.
            if(isset($check) && Employee::where('id', $emp_id)->where('resort_id', $this->resort->resort_id)->exists())
            {
                $check->ApprovedBy= $this->resort->GetEmployee->id;
                $check->Status= 'Assinged';
                $check->save();
                MaintanaceRequest::where('id',$task_id)->update(['Assigned_To'=>$emp_id,'Status'=>'Assigned']);
                ChildMaintananceRequest::create([
                    'maintanance_request_id' => $task_id,
                    'resort_id' => $this->resort->resort_id,
                    'ApprovedBy' =>0,
                    'Status' =>'pending',
                    'date'=>date('Y-m-d'),
                ]);

                // Notify assigned employee + requester (createNotification() now sends the push too)
                try {
                    $mainRequest = MaintanaceRequest::find($task_id);
                    $this->createNotification($emp_id, 'Maintenance Assigned', 'You have been assigned a maintenance task: ' . ($mainRequest->descriptionIssues ?? ''), $task_id);
                    // Also notify the person who raised the request
                    $this->createNotification($mainRequest->Raised_By, 'Maintenance Update', 'Your maintenance request has been assigned to a technician.', $task_id);
                } catch (\Exception $e) {
                    \Log::warning('Notification failed: ' . $e->getMessage());
                }
            }
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Request Assigned successfully.'], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Assigned Request'], 500);
        }
    }
    public function HODMainRequestDetails($id)
    {
        $id =base64_decode($id);

        $page_title="Maintenance Request Details";

        $MaintanaceRequest = MaintanaceRequest::join("employees as t3","t3.id","maintanace_requests.Raised_By")
                                            ->join("resort_admins as t1","t1.id","t3.Admin_Parent_id")
                                            ->join("resort_departments as t4","t4.id","t3.Dept_id")
                                            ->where('maintanace_requests.resort_id',$this->resort->resort_id)
                                            // ->where('maintanace_requests.Status',"=",'Open')
                                            ->whereNotNull('maintanace_requests.Assigned_To')
                                            ->where("maintanace_requests.id",$id)
                                            ->first(['t1.id as Parentid','t1.first_name','t1.last_name','maintanace_requests.*']);
        $MaintanaceRequestChild =MaintanaceRequest::join('child_maintanance_requests as t1',"t1.maintanance_request_id","=","maintanace_requests.id")
                                            ->where('maintanace_requests.resort_id',$this->resort->resort_id)
                                            ->where('maintanace_requests.id',$id)
                                            ->where('t1.ApprovedBy','!=',0)

                                            ->get(['t1.id','t1.Status']);
            $displayedStatuses = [];
         
                foreach($MaintanaceRequestChild as $m)
                {
                    if(!in_array($m->Status, $displayedStatuses))
                    {
                        $displayedStatuses[] = $m->Status;
                    }
                }
                $MaintanaceRequestChildDetails = MaintanaceRequest::join('child_maintanance_requests as t2', 't2.maintanance_request_id', '=', 'maintanace_requests.id')
                                                                    ->where('maintanace_requests.resort_id',$this->resort->resort_id)
                                                                    ->where('maintanace_requests.Status',"!=",'Closed')
                                                                    ->where("maintanace_requests.id",$id)
                                                                    ->where('ApprovedBy','!=',0)
                                                                    ->get(['t2.Status','t2.date','maintanace_requests.Assigned_To','maintanace_requests.building_id','maintanace_requests.FloorNo','maintanace_requests.RoomNo','maintanace_requests.created_at as SubmitedDate'])->map(function ($row)
                                                                    {
                                                                        $row->submitedDate = date('d M Y',strtotime($row->SubmitedDate));
                                                                        $AssingedTask = Employee::join('resort_admins', 'resort_admins.id', '=', 'employees.Admin_Parent_id')
                                                                        ->where('employees.Admin_Parent_id', $row->Assigned_To)
                                                                        ->first(['resort_admins.first_name', 'resort_admins.last_name', 'resort_admins.id as Parentid']);
                                                                                $row->EmployeeName = isset($AssingedTask) ? ucfirst($AssingedTask->first_name . ' ' . $AssingedTask->last_name) : '-';
                                                                                $row->profileImg = isset($AssingedTask) ?  Common::getResortUserPicture($AssingedTask->Parentid): '-';
                                                                                $row->date = date('d M Y',strtotime($row->date));
                                                                        return $row;
                                                                    });
                    $displayedStatusesDetails = [];
                    foreach($MaintanaceRequestChildDetails as $k=>$m)
                    {
                        if($k==0)
                        {
                            $displayedStatusesDetails['SubmitedRequest'][] =  $m->submitedDate;
                        }
                        if(!in_array($m->Status, $displayedStatusesDetails))
                        {
                            $displayedStatusesDetails[$m->Status][] =  [$m->date,$m->profileImg,$m->EmployeeName];
                        }
                    }

            $AssingAccommodation = AssingAccommodation::join('available_accommodation_models as t1', 't1.id', '=', 'assing_accommodations.available_a_id')
                                                        ->where("t1.BuildingName", $MaintanaceRequest->building_id)
                                                        ->where("t1.Floor", $MaintanaceRequest->FloorNo)
                                                        ->where("t1.RoomNo", $MaintanaceRequest->RoomNo)
                                                        ->join('employees as t2', 't2.id', '=', 'assing_accommodations.emp_id')
                                                        ->join('resort_admins as t3', 't3.id', '=', 't2.Admin_Parent_id')
                                                        ->orderBy('t1.created_at', 'asc')
                                                        ->get(['t3.first_name', 't3.last_name', 't3.id as Parentid'])->map(function ($row) {
                                                            $row->EmployeeName = ucfirst($row->first_name . ' ' . $row->last_name);
                                                            $row->profileImg = Common::getResortUserPicture($row->Parentid);
                                                            return $row;
                                                        });
             $MaintanaceRequest->RequestedBy= $MaintanaceRequest->first_name.' '.$MaintanaceRequest->last_name;
             $MaintanaceRequest->AssgingedStaff= $MaintanaceRequest->Assigned_To;
             $MaintanaceRequest->Location= $MaintanaceRequest->BuilidngData->BuildingName . (!empty($MaintanaceRequest->RoomNo) ? ', Room No - '. $MaintanaceRequest->RoomNo : '') . (!empty($MaintanaceRequest->FloorNo) ? ', Floor No - '. $MaintanaceRequest->FloorNo : '');
             $MaintanaceRequest->Priority =  $MaintanaceRequest->priority;
             $MaintanaceRequest->Date =  date('d M Y',strtotime($MaintanaceRequest->date));

            $InventoryModule= InventoryModule::where('resort_id',$this->resort->resort_id)
                                            ->where("id", $MaintanaceRequest->item_id)
                                            ->first('ItemName');
             $MaintanaceRequest->EffectedAmenity = $InventoryModule ? ucfirst($InventoryModule->ItemName) : 'N/A';
             if($MaintanaceRequest->Priority == 'Low')
             {
                $MaintanaceRequest->Priority = '<span class="badge badge-blueNew border-0">Low</span>';
             }
             elseif($MaintanaceRequest->Priority == 'Medium')
             {
                $MaintanaceRequest->Priority = '<span class="badge badge-themeWarning border-0">Medium</span>';
             }
             elseif($MaintanaceRequest->Priority == 'High')
             {
                $MaintanaceRequest->Priority = '<span class="badge badge-danger">High</span>';
             }
             if(!isset($MaintanaceRequest->AssgingedStaff))
             {
                 $MaintanaceRequest->AssgingedStaff = '<span class="badge badge-themeWarning border-0">Not Assigned Yet</span>';
             }
             if($MaintanaceRequest->Status=='pending')
             {
                $MaintanaceRequest->Status='<span class="badge badge-themeSkyblue">Pending</span>';
             }
             elseif($MaintanaceRequest->Status=='In-Progress')
             {
                 $MaintanaceRequest->Status= '<span class="badge badge-themeBlue">In-Progress</span>';
             }
             elseif($MaintanaceRequest->Status=='Open')
             {
                 $MaintanaceRequest->Status= '<span class="badge badge-orange">Open</span>';
             }
             elseif($MaintanaceRequest->Status=='Assigned')
             {
                 $MaintanaceRequest->Status= '<span class="badge badge-themeWarning">Assigned</span>';
             }
             $MaintanaceRequest->RequestedBy ='<div class="tableUser-block">
                                                    <div class="img-circle"><img src="'.Common::getResortUserPicture($MaintanaceRequest->Parentid).'" alt="user">
                                                    </div>
                                                    <span class="userApplicants-btn">'.$MaintanaceRequest->RequestedBy.'</span>
                                                </div>';
            if(  $MaintanaceRequest->Image)
            {
                // Image can be either a plain filename (web upload) or a
                // json_encode(['Filename'=>..,'Child_id'=>..]) value (mobile
                // upload via AWSEmployeeFileUpload) — resolveMaintenanceAttachmentUrl()
                // handles both instead of blindly concatenating a path.
                $imageUrl = Common::resolveMaintenanceAttachmentUrl($MaintanaceRequest->Image, Auth::guard('resort-admin')->user()->resort->resort_id);
                $MaintanaceRequest->Image = $imageUrl ? '<img width="150px"; src="'.$imageUrl.'" alt="user">' : null;
            }
            if ($MaintanaceRequest->Video)
            {
                $path_path = config('settings.MaintanceRequest') . '/' . Auth::guard('resort-admin')->user()->resort->resort_id;
                $MaintanaceRequest->Video = '<iframe src="' . StorageHelper::temporaryUrl($path_path . '/' . $MaintanaceRequest->Video) . '"frameborder="0" allowfullscreen></iframe>';
            }
            return view('resorts.Accommodation.Maintanance.MaintanaceReqDetails',compact('displayedStatusesDetails','MaintanaceRequestChild','AssingAccommodation','MaintanaceRequest','page_title','displayedStatuses'));

    }


    public function HODHoldMaintanaceRequest(Request $request)
    {

        $currentHodId = Auth::guard('resort-admin')->user()->GetEmployee->id;
        $MaintanaceRequest = MaintanaceRequest::join("employees as t3","t3.id","maintanace_requests.Raised_By")
                ->join("resort_admins as t1","t1.id","t3.Admin_Parent_id")
                ->join("resort_departments as t4","t4.id","t3.Dept_id")
                ->join("building_models as t5","t5.id","maintanace_requests.building_id")
                ->where('maintanace_requests.resort_id', $this->resort->resort_id)
                ->whereIn('maintanace_requests.Status', [ 'On-Hold'])
                ->where(function($q) use ($currentHodId) {
                    $q->where('maintanace_requests.Assigned_To', $currentHodId)
                      ->orWhereIn('maintanace_requests.Assigned_To', $this->underEmp_id);
                });

            if(!empty($ResortDepartment))
            {
                $MaintanaceRequest->where('t2.Dept_id', $ResortDepartment);
            }
            if (!empty($search))
            {
                $MaintanaceRequest->where(function ($query) use ($search) {
                    $query->where('t1.first_name', 'LIKE', '%' . $search . '%')
                          ->orWhere('t1.last_name', 'LIKE', '%' . $search . '%')
                          ->orWhereRaw("CONCAT(t1.first_name, ' ', t1.last_name) LIKE ?", ['%' . $search . '%'])
                          ->orWhere('maintanace_requests.RoomNo', 'LIKE', '%' . $search . '%')
                          ->orWhere('maintanace_requests.FloorNo', 'LIKE', '%' . $search . '%')
                          ->orWhereRaw("DATE_FORMAT(maintanace_requests.date, '%d %b %Y') LIKE ?", ['%' . $search . '%']) // Date filter
                          ->orWhere('maintanace_requests.priority', 'LIKE', '%' . $search . '%');
                });
            }
            $MaintanaceRequest = $MaintanaceRequest->orderBy('maintanace_requests.id', 'desc')
                    ->get(['t1.id as Parentid', 't1.first_name', 't1.last_name', 'maintanace_requests.*'])
                    ->map(function ($row)
                    {
                        $row->RequestedBy = $row->first_name . ' ' . $row->last_name;
                        $row->AssgingedStaff = $row->Assigned_To;
                        $row->Location = $row->BuilidngData->BuildingName . (!empty($row->RoomNo) ? ', Room No - ' . $row->RoomNo : '') . (!empty($row->FloorNo) ? ', Floor No - ' . $row->FloorNo : '');
                        $row->Priority = $row->priority;
                        $row->Date =date('d M Y',strtotime($row->date));
                        $row->profileImg = Common::getResortUserPicture($row->Parentid);
                        $InventoryModule = InventoryModule::where('resort_id', $this->resort->resort_id)
                            ->where("id", $row->item_id)
                            ->first('ItemName');
                        $row->EffectedAmenity = $InventoryModule ? ucfirst($InventoryModule->ItemName) : 'N/A';
                        return $row;
                    });


            if($request->ajax())
            {

                return datatables()->of($MaintanaceRequest)
                ->addColumn('action', function ($row)
                {
                    $id = base64_encode($row->id);
                    $string='';
                    return  $string.='<a target="_blank" href="'.route('resort.accommodation.MainRequestDetails',$id ).'" class="btn-tableIcon btnIcon-skyblue mainRequetDetails" data-task_id="'.$id.'"><i class="fa-regular fa-eye"></i></a>';
                })
                ->editColumn('RequestedBy', function ($row)
                {
                    return  $row->descriptionIssues;
                })
                ->editColumn('Location', function ($row) {
                    return e($row->Location);
                })
                ->editColumn('Date', function ($row) {
                    return $row->Date;
                })
                ->editColumn('Priority', function ($row) {
                    $string ='';
                    if($row->Priority == 'Low')
                    {
                        $string = '<span class="badge badge-blueNew border-0">Low</span>';
                    }
                    elseif($row->Priority == 'Medium')
                    {
                        $string = '<span class="badge badge-themeWarning border-0">Medium</span>';
                    }
                    elseif($row->Priority == 'High')
                    {
                        $string = '<span class="badge badge-danger">High</span>';
                    }
                    return $string;
                })


                ->rawColumns(['Location','RequestedBy','Priority','action','Status'])
                ->make(true);
            }
        $page_title="On Hold Requests";
        $holdAjaxRoute = route('resort.accommodation.HODHoldMaintanaceRequest');
                    // HOD (2) and EXCOM (1) — this feeds an assignee dropdown, an
        // EXCOM-headed department previously had no one to assign to here.
        $Employee =Employee::join('resort_admins','resort_admins.id',"=",'employees.Admin_Parent_id')
                            ->where('employees.resort_id', $this->resort->resort_id)
                            ->whereIn("employees.rank",[1,2])
                            ->get(['employees.*','resort_admins.first_name','resort_admins.last_name']);
        return view('resorts.Accommodation.Maintanance.HoldMaintanaceRequest',compact('page_title','Employee','holdAjaxRoute'));
    }

    public function HODMaintanaceRequestlist(Request $request)
    {
        if($request->ajax())
        {
            //
            $EscalationDay = EscalationDay::where('resort_id',$this->resort->resort_id)->first();
            $search = $request->Search;
            $currentHodId = Auth::guard('resort-admin')->user()->GetEmployee->id;
            $MaintanaceRequest = MaintanaceRequest::join("employees as t3","t3.id","maintanace_requests.Raised_By")
                                        ->join("resort_admins as t1","t1.id","t3.Admin_Parent_id")
                                        ->join("resort_departments as t4","t4.id","t3.Dept_id")
                                        ->join('building_models as t5', 't5.id', 'maintanace_requests.building_id')
                                        ->where(function($q) use ($currentHodId) {
                                            $q->whereIn('t3.id', $this->underEmp_id)
                                              ->orWhere('maintanace_requests.Assigned_To', $currentHodId);
                                        })
                                        ->where('maintanace_requests.resort_id', $this->resort->resort_id)
                                        ->whereNotIn('maintanace_requests.Status', ['Closed','On-Hold']);

                                                        if(!empty($ResortDepartment))
                                                        {
                                                            $MaintanaceRequest->where('t3.Dept_id', $ResortDepartment);
                                                        }
                                                        if (!empty($search))
                                                        {
                                                            $MaintanaceRequest->where(function ($query) use ($search) {
                                                                $query->where('t1.first_name', 'LIKE', '%' . $search . '%')
                                                                      ->orWhere('t1.last_name', 'LIKE', '%' . $search . '%')
                                                                      ->orWhereRaw("DATE_FORMAT(maintanace_requests.date, '%d %b %Y') LIKE ?", ['%' . $search . '%']) // Date filter
                                                                      ->orWhereRaw("CONCAT(t1.first_name, ' ', t1.last_name) LIKE ?", ['%' . $search . '%'])
                                                                      ->orWhereRaw("CONCAT(t5.BuildingName, ' ', maintanace_requests.RoomNo,' ',maintanace_requests.FloorNo) LIKE ?", ['%' . $search . '%'])
                                                                      ->orWhere('maintanace_requests.RoomNo', 'LIKE', '%' . $search . '%')
                                                                      ->orWhere('maintanace_requests.FloorNo', 'LIKE', '%' . $search . '%')
                                                                      ->orWhere('maintanace_requests.priority', 'LIKE', '%' . $search . '%');
                                                            });
                                                        }
                                                        $MaintanaceRequest = $MaintanaceRequest->orderBy('maintanace_requests.id', 'desc')
                                                                ->get(['t1.id as Parentid', 't1.first_name', 't1.last_name', 'maintanace_requests.*'])
                                                                ->map(function ($row) use($EscalationDay)
                                                                {
                                                                    $row->RequestedBy = $row->first_name . ' ' . $row->last_name;
                                                                    $row->AssgingedStaff = $row->Assigned_To;
                                                                    $row->Location = $row->BuilidngData->BuildingName . (!empty($row->RoomNo) ? ', Room No - ' . $row->RoomNo : '') . (!empty($row->FloorNo) ? ', Floor No - ' . $row->FloorNo : '');
                                                                    $row->Priority = $row->priority;
                                                                    $row->NewStatus = $row->Status;

                                                                    $row->Date =date('d M Y',strtotime($row->date));
                                                                    $daysSinceRequest = now()->diffInDays(Carbon::parse($row->date));

                                                                    $row->EscalationTimeOver = ($daysSinceRequest > $EscalationDay->EscalationDay) ? 'Alert' : 'Regular';
                                                                    $row->profileImg = Common::getResortUserPicture($row->Parentid);
                                                                    $InventoryModule = InventoryModule::where('resort_id', $this->resort->resort_id)
                                                                        ->where("id", $row->item_id)
                                                                        ->first('ItemName');
                                                                    $row->EffectedAmenity = $InventoryModule ? ucfirst($InventoryModule->ItemName) : 'N/A';
                                                                    if(isset($row->Assigned_To) && $row->Assigned_To !=0)
                                                                    {
                                                                        $emp = Common::GetEmployeeDetails($row->Assigned_To, $this->resort->resort_id);
                                                                        if ($emp) {
                                                                        $row->Assign_profileImg = Common::getResortUserPicture($emp->Parent_id);
                                                                        $row->Assign_toName     = $emp->first_name.' '.$emp->last_name;
                                                                        }
                                                                    }
                                                                    return $row;
                                                                });
                    return datatables()->of($MaintanaceRequest)
                            ->addColumn('action', function ($row) {
                    $id = base64_encode($row->id);
                     if(!isset($row->Assigned_To))
                     {
                        $string ='<a href="javascript:void(0)" class="btn-tableIcon btnIcon-blue ForwardToHOD" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Assign to Employee" data-req_id="'.$id.'" data-Location="'.$row->Location.'"data-EffectedAmenity="'. $row->EffectedAmenity .'"><i class=" fa-solid fa-share"></i></a>';
                     }
                     else
                     {
                            $string= '<a target="_blank" href="'.route('resort.accommodation.HODMainRequestDetails',$id ).'" class="btn-tableIcon btnIcon-skyblue mainRequetDetails" data-task_id="'.$id.'"><i class="fa-regular fa-eye"></i></a>';
                     }
                     return $string;
                     })
                    ->editColumn('RequestedBy', function ($row) {
                      return   '<div class="tableUser-block">
                                            <div class="img-circle"><img src="'.$row->profileImg.'" alt="user">
                                            </div>
                                            <span class="userApplicants-btn">'.$row->RequestedBy.'</span>
                                        </div>';

                    })
                    ->editColumn('EffectedAmenity', function ($row) {
                        return e($row->EffectedAmenity);
                    })
                    ->editColumn('Location', function ($row) {
                        return e($row->Location);
                    })
                    ->editColumn('Priority', function ($row) {
                        $string ='';
                        if($row->Priority == 'Low')
                        {
                            $string = '<span class="badge badge-blueNew border-0">Low</span>';
                        }
                        elseif($row->Priority == 'Medium')
                        {
                            $string = '<span class="badge badge-themeWarning border-0">Medium</span>';
                        }
                        elseif($row->Priority == 'High')
                        {
                            $string = '<span class="badge badge-danger">High</span>';
                        }
                        return $string;
                    })
                    ->editColumn('AssgingedStaff', function ($row) {

                        if(isset($row->AssgingedStaff))
                        {
                            return '<div class="tableUser-block">
                                        <div class="img-circle"><img src="'.$row->Assign_profileImg.'" alt="user">
                                        </div>
                                        <span class="userApplicants-btn">'.$row->Assign_toName.'</span>
                                    </div>';
                        }
                        else
                        {
                            return '<span class="badge badge-themeWarning border-0">Not Assigned Yet</span>';
                        }
                    })
                    ->editColumn('Date', function ($row) {
                            return $row->Date;
                    })
                    ->editColumn('Status', function ($row)
                    {

                        if($row->Status=='pending')
                        {
                            return '<span class="badge badge-themeSkyblue">Pending</span>';
                        }
                        elseif($row->Status=='In-Progress')
                        {
                            return '<span class="badge badge-themeBlue">In-Progress</span>';
                        }
                        elseif($row->Status=='Open')
                        {
                            return '<span class="badge badge-orange">Open</span>';
                        }
                        elseif($row->Status=='Assigned')
                        {
                            return '<span class="badge badge-themeWarning">Assigned</span>';
                        }
                    })

                    ->rawColumns(['RequestedBy','Priority','action','AssgingedStaff','Status'])
                    ->make(true);

        }


        $page_title="Maintenance Requests";
        $Employee =Employee::join('resort_admins','resort_admins.id',"=",'employees.Admin_Parent_id')
                            ->where('employees.resort_id', $this->resort->resort_id)
                            ->whereIn('employees.id',$this->underEmp_id)
                            ->get(['employees.*','resort_admins.first_name','resort_admins.last_name']);
        return view('resorts.Accommodation.Maintanance.HODMaintanaceRequestlist',compact('page_title','Employee'));
    }

    public function HODAssignTaskList(Request $request)
    {
        if($request->ajax())
        {
            $search = $request->Search;
            $inventory = $request->inventory;
            $currentHodId = Auth::guard('resort-admin')->user()->GetEmployee->id;
            $MaintanaceRequest = MaintanaceRequest::join("employees as t3","t3.id","maintanace_requests.Raised_By")
                                        ->join("resort_admins as t1","t1.id","t3.Admin_Parent_id")
                                        ->join("resort_departments as t4","t4.id","t3.Dept_id")
                                        ->join('building_models as t5', 't5.id', 'maintanace_requests.building_id')
                                        ->join("employees as t6","t6.id","maintanace_requests.Assigned_To")
                                        ->join("resort_admins as t7","t7.id","t6.Admin_Parent_id")
                                        ->leftJoin("inventory_modules as t8","t8.id","maintanace_requests.Item_id")
                                        ->where(function($q) use ($currentHodId) {
                                            $q->whereIn('t3.id', $this->underEmp_id)
                                              ->orWhere('maintanace_requests.Assigned_To', $currentHodId)
                                              ->orWhereIn('maintanace_requests.Assigned_To', $this->underEmp_id);
                                        })
                                        ->where('maintanace_requests.resort_id', $this->resort->resort_id)
                                        ->where('maintanace_requests.Assigned_To',"!=",null)
                                        ->whereIn('maintanace_requests.Status', ['Assigned','In-Progress']);

                                                        if(!empty($inventory))
                                                        {
                                                            $MaintanaceRequest->where('t8.Inv_Cat_id', $inventory);
                                                        }
                                                        if (!empty($search))
                                                        {
                                                            $MaintanaceRequest->where(function ($query) use ($search) {
                                                                $query->where('t1.first_name', 'LIKE', '%' . $search . '%')
                                                                      ->orWhere('t1.last_name', 'LIKE', '%' . $search . '%')
                                                                      ->orWhere('t7.first_name', 'LIKE', '%' . $search . '%') // t7 filter
                                                                      ->orWhere('t7.last_name', 'LIKE', '%' . $search . '%')
                                                                      ->orWhereRaw("DATE_FORMAT(maintanace_requests.date, '%d %b %Y') LIKE ?", ['%' . $search . '%']) // Date filter
                                                                      ->orWhereRaw("CONCAT(t1.first_name, ' ', t1.last_name) LIKE ?", ['%' . $search . '%'])
                                                                      ->orWhereRaw("CONCAT(t7.first_name, ' ', t7.last_name) LIKE ?", ['%' . $search . '%'])
                                                                      ->orWhereRaw("CONCAT(t5.BuildingName, ' ', maintanace_requests.RoomNo,' ',maintanace_requests.FloorNo) LIKE ?", ['%' . $search . '%'])
                                                                      ->orWhere('maintanace_requests.RoomNo', 'LIKE', '%' . $search . '%')
                                                                      ->orWhere('maintanace_requests.FloorNo', 'LIKE', '%' . $search . '%')
                                                                      ->orWhere('maintanace_requests.Status', 'LIKE', '%' . $search . '%')
                                                                      ->orWhere('maintanace_requests.priority', 'LIKE', '%' . $search . '%');
                                                            });
                                                        }
                                                        $MaintanaceRequest = $MaintanaceRequest->orderBy('maintanace_requests.id', 'desc')
                                                                ->get(['t1.id as Parentid', 't1.first_name', 't1.last_name', 'maintanace_requests.*'])
                                                                ->map(function ($row)
                                                                {
                                                                    $row->RequestedBy = $row->first_name . ' ' . $row->last_name;
                                                                    $row->AssgingedStaff = $row->Assigned_To;
                                                                    $row->Location = $row->BuilidngData->BuildingName . (!empty($row->RoomNo) ? ', Room No - ' . $row->RoomNo : '') . (!empty($row->FloorNo) ? ', Floor No - ' . $row->FloorNo : '');
                                                                    $row->Priority = $row->priority;
                                                                    $row->NewStatus = $row->Status;

                                                                    $row->Date =date('d M Y',strtotime($row->date));
                                                                    $row->AssignedDays = now()->diffInDays(Carbon::parse($row->date));

                                                                    // $row->EscalationTimeOver = ($daysSinceRequest > $EscalationDay->EscalationDay) ? 'Alert' : 'Regular';
                                                                    $row->profileImg = Common::getResortUserPicture($row->Parentid);
                                                                    $InventoryModule = InventoryModule::where('resort_id', $this->resort->resort_id)
                                                                        ->where("id", $row->item_id)
                                                                        ->first('ItemName');
                                                                    $row->EffectedAmenity = $InventoryModule ? ucfirst($InventoryModule->ItemName) : 'N/A';
                                                                    if(isset($row->Assigned_To) && $row->Assigned_To !=0)                                                                    {
                                                                        $emp = Common::GetEmployeeDetails($row->Assigned_To, $this->resort->resort_id);
                                                                        if ($emp) {
                                                                        $row->Assign_profileImg = Common::getResortUserPicture($emp->Parent_id);
                                                                        $row->Assign_toName     = $emp->first_name.' '.$emp->last_name;
                                                                        }
                                                                    }
                                                                    return $row;
                                                                });

            return datatables()->of($MaintanaceRequest)

            ->addColumn('action', function ($row) {
                $id = base64_encode($row->id);

                    $string= '<a target="_blank" href="'.route('resort.accommodation.HODMainRequestDetails',$id ).'" class="btn-tableIcon btnIcon-skyblue mainRequetDetails" data-task_id="'.$id.'"><i class="fa-regular fa-eye"></i></a>';

                 return $string;
                 })
            ->editColumn('descriptionIssues', function ($row) {
                return  $row->descriptionIssues;
            })
            ->editColumn('EffectedAmenity', function ($row) {
                return e($row->EffectedAmenity);
            })
            ->editColumn('Location', function ($row) {
                return e($row->Location);
            })
            ->editColumn('AssignedDays', function ($row) {

                return $row->AssignedDays;
            })
            ->editColumn('AssignTo', function ($row)
            {
                if(isset($row->AssgingedStaff))
                {
                    return '<div class="tableUser-block">
                                <div class="img-circle"><img src="'.$row->Assign_profileImg.'" alt="user">
                                </div>
                                <span class="userApplicants-btn">'.$row->Assign_toName.'</span>
                            </div>';
                }
                else
                {
                    return '<span class="badge badge-themeWarning border-0">Not Assigned Yet</span>';
                }
            })
            ->editColumn('Date', function ($row) {
                return $row->Date;
            })
            ->editColumn('Status', function ($row)
            {
                if($row->Status=='In-Progress')
                {
                    return '<span class="badge badge-themeBlue">In-Progress</span>';
                }
                elseif($row->Status=='Open')
                {
                    return '<span class="badge badge-orange">Open</span>';
                }
                elseif($row->Status=='Assigned')
                {
                    return '<span class="badge badge-themeWarning">Assigned</span>';
                }
                elseif($row->Status=='Resolvedawaiting')
                {
                    return '<span class="badge badge-themeWarning">Resolved awaiting Confirmation</span>';
                }
            })
            ->rawColumns(['descriptionIssues','Location','Date','AssignedDays','AssignTo','Status','action'])
            ->make(true);

        }
        $page_title="Assigned Tasks";
        $inventory = InventoryCategoryModel::where('resort_id',$this->resort->resort_id)->get();

        return view('resorts.Accommodation.Maintanance.HODAssignTaskList',compact('page_title','inventory'));

    }
}
