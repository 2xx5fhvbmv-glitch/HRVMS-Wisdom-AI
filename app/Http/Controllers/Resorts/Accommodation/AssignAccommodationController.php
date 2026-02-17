<?php

namespace App\Http\Controllers\Resorts\Accommodation;


use DB;
use Auth;
use Validator;
use Carbon\Carbon;
use App\Helpers\Common;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Models\InventoryModule;
use App\Models\AssingAccommodation;
use App\Http\Controllers\Controller;
use App\Models\TransferAccommodation;
use App\Models\AvailableAccommodationModel;
class AssignAccommodationController extends Controller
{
    protected $resort;
    protected $underEmp_id=[];
    public function __construct()
    {
        $this->resort = $resortId = auth()->guard('resort-admin')->user();
        if(!$this->resort) return;
        if($this->resort->is_master_admin == 0){
            $reporting_to = $this->globalUser->GetEmployee->id;
            $this->underEmp_id = Common::getSubordinates($reporting_to);
        }
    }

    public function AssignAccommation()
    {
        if(Common::checkRouteWisePermission('resort.accommodation.AssignAccommation',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }
        $Employeelist = Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                            ->join('resort_positions as t2', "t2.id", "=", "employees.Position_id")
                            ->select(
                                't1.id as Parentid',
                                't1.first_name',
                                't1.last_name',
                                't1.profile_picture',
                                'employees.Emp_id as EmployeeId',
                                't2.position_title',
                                'employees.id as new_emp_id',
                            )
                            ->groupBy('employees.id')
                            ->where("t1.resort_id", $this->resort->resort_id)
                            ->get()
                            ->map(function ($item) {
                                    $item->EmployeeName = ucfirst($item->first_name . ' ' . $item->last_name);
                                    $item->Position = ucfirst($item->position_title);
                                    $item->profileImg = Common::getResortUserPicture($item->Parentid);
                                    $item->Emp_id = $item->EmployeeId;
                                return $item;
                            });

        $AvailableAccommodationModel  = AvailableAccommodationModel::join('building_models as t1',"t1.id","=","available_accommodation_models.BuildingName")
                                                                    ->where("t1.resort_id", $this->resort->resort_id)
                                                                    ->groupBy("available_accommodation_models.BuildingName")
                                                                    ->get(['t1.BuildingName as BName','available_accommodation_models.*']);
        $page_title="Assign Accommodation";
       return view('resorts.Accommodation.AssignAccommodation.index',compact('page_title','Employeelist','AvailableAccommodationModel'));
    }

    public function BuildingwiseAccommodation(Request $request)
    {
        $Employeeid = $request->Employeeid;

        $emp =Employee::with('resortAdmin')->find($Employeeid);

        $rank = Common::getEmpGrade($emp->rank);

        $gender = ucfirst($emp->resortAdmin->gender);



        $select_build = $request->select_build;
        if($request->ajax())
        {
            $data = AvailableAccommodationModel::where("BuildingName", $select_build)
                    ->where('resort_id', $this->resort->resort_id)
                    ->where('RoomType', $rank)
                    ->where('blockFor', $gender)
                    ->with('availableAccommodationInvItem.inventoryModule', 'accommodationType') // Eager load relationships
                    ->get()
                    ->map(function ($accommodation) {
                        $itemData = [];

                        foreach ($accommodation->availableAccommodationInvItem as $item) {
                            $inventoryItem = $item->inventoryModule
                                ? ucfirst($item->inventoryModule->ItemName)
                                : 'Unknown';

                            $itemData[] = [
                                'inventoryItem' => $inventoryItem,
                            ];
                        }
                        $accommodation->items = $itemData;
                        $accommodation->Color = $accommodation->accommodationType->Color;
                        $accommodation->AccommodationName = $accommodation->accommodationType ? ucfirst($accommodation->accommodationType->AccommodationName) : 'Not Available';
                        $AssingAccommodation = AssingAccommodation::where("available_a_id",$accommodation->id)->get();
                        $accommodation->AssingAccommodationCount=$AssingAccommodation->where("emp_id","=",0)->count();
                        return $accommodation;
                    });

                    $edit_class = '';
                    if(Common::checkRouteWisePermission('resort.accommodation.AssignAccommation',config('settings.resort_permissions.view')) == false){
                        $edit_class = 'd-none';
                    }
                    return datatables()->of($data)
                    ->addColumn('Action', function ($row) use ($edit_class) 
                    {
                        $id = base64_encode($row->id);
                        return '<a href="#" id="Bedshow" class="btn btn-themeSkyblueLight btn-small '.$edit_class.'" data-id="'.$id.'">Select Bed</a>';
                    })
                    ->editColumn('FloorNo', function ($row)
                    {
                        return e($row->Floor);
                    })
                    ->editColumn('RoomNo', function ($row) {
                        return e($row->RoomNo);
                    })
                    ->editColumn('EmployeeCategory', function ($row)
                    {
                        $Rank = config('settings.eligibilty');
                        return e($Rank[$row->RoomType]);
                    })
                    ->editColumn('RoomFacilities', function ($row) {
                        // Extract the inventory items as strings
                        $itemNames = array_map(function ($item) {
                            return $item['inventoryItem'];
                        }, $row->items);

                        // Join the names into a comma-separated string
                        return e(implode(", ", $itemNames));
                    })
                    ->editColumn('RoomStatus', function ($row) {
                        return e($row->CleaningSchedule);
                    })
                    ->editColumn('BedCapacity', function ($row) {
                        return isset($row->Capacity) ? e($row->Capacity) : 0;
                    })
                    ->editColumn('BedAvailability', function ($row) {
                        $d=0;
                        if($row->Capacity == $row->AssingAccommodationCount)
                        {
                            $d = $row->Capacity;
                        }
                        else
                        {
                            $d= $row->AssingAccommodationCount;
                        }
                        return $d;
                    })
                    ->editColumn('Status', function ($row) {
                        // Replace with the appropriate status logic
                        return '<span class="badge" style="background-color: ' . e($row->Color) . ';">' . e($row->AccommodationName) . '</span>';
                    })
                    ->rawColumns(['Status', 'Action']) // Mark 'Action' column as raw HTML
    ->make(true);

        }

    }
    public function GetAssignedBed(Request $request)
    {
        $id = base64_decode($request->id);
        $data = AvailableAccommodationModel::join('assing_accommodations as t1','t1.available_a_id',"=","available_accommodation_models.id")
                                            ->leftJoin('employees as t2',"t2.id","=","t1.emp_id")
                                            ->leftJoin('resort_admins as t3',"t3.id","=","t2.Admin_Parent_id")
                                            ->where('available_accommodation_models.id',$id)
                                            ->get([
                                                            'available_accommodation_models.BuildingName',
                                                            'available_accommodation_models.Floor',
                                                            'available_accommodation_models.RoomNo',
                                                            't1.emp_id',
                                                            't3.first_name',
                                                            't3.last_name',
                                                            't3.id as Parentid',
                                                            't1.id as assingid'
                                                        ])->map(function($i)
                                                        {
                                                            $i->EmployeeName = ucfirst($i->first_name . ' ' . $i->last_name);
                                                            $i->profileImg = Common::getResortUserPicture($i->Parentid);
                                                            return $i;
                                                        });
        return response()->json(['success' =>true,'data'=>$data], 200);


    }
    public function AssignAccommodationToEmp(Request $request)
    {


        $assignId= $request->assignId;
        $emp_id = $request->emp_id;
        $validator = Validator::make($request->all(), [
            'assignId' =>'required',
            'emp_id' => 'required'
        ],
    [
            'assignId.required' => 'Please select Bed',
            'emp_id.required' => 'Please select Employee'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }



        DB::beginTransaction();
        try
        {
            $anyAssigned =AssingAccommodation::where('emp_id',$emp_id)->first();
            
            if(!$anyAssigned)
            {

            
                    AssingAccommodation::where("id",$assignId)->update(['emp_id'=>$emp_id,"effected_date"=>date('Y-m-d')]);

                    $Employeelist = Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                                    ->join('resort_positions as t2', "t2.id", "=", "employees.Position_id")
                                    ->join('assing_accommodations as t3', "t3.emp_id", "=", "employees.id")
                                    ->select(
                                        't1.id as Parentid',
                                        't1.first_name',
                                        't1.last_name',
                                        't1.profile_picture',
                                        'employees.Emp_id as EmployeeId',
                                        't2.position_title',
                                        't3.available_a_id',
                                        'employees.id as new_emp_id'
                                    )
                                    ->where("t3.id", $assignId)
                                    ->first();
                            if ($Employeelist)
                            {
                                $availableAccommodation = AvailableAccommodationModel::where("id", $Employeelist->available_a_id)
                                                                                    ->where('resort_id', $this->resort->resort_id)
                                                                                    ->with('availableAccommodationInvItem.inventoryModule', 'accommodationType') // Eager load relationships
                                                                                    ->first();
                                if ($availableAccommodation)
                                {
                                    $itemData = [];
                                    $item_id=[];
                                    // Process inventory items
                                    foreach ($availableAccommodation->availableAccommodationInvItem as $item)
                                    {
                                        $inventoryItem = $item->inventoryModule ? ucfirst($item->inventoryModule->ItemName) : 'Unknown';
                                        $item_id[] = $item->inventoryModule->id;
                                        $itemData[] = $inventoryItem;
                                    }
                                    $data = [
                                        'employee' => [
                                            'name' => ucfirst($Employeelist->first_name . ' ' . $Employeelist->last_name),
                                            'position' => ucfirst($Employeelist->position_title),
                                            'profile_picture' => Common::getResortUserPicture($Employeelist->Parentid),
                                            'emp_id' => $Employeelist->EmployeeId,
                                        ],
                                        'accommodation' => [
                                            'building_name' => $availableAccommodation->BuildingName ?? 'Not Available',
                                            'floor' => $availableAccommodation->Floor ?? 'Not Available',
                                            'room_no' => $availableAccommodation->RoomNo ?? 'Not Available',
                                            'facilities' => $itemData,
                                            'RoomStatus'=>$availableAccommodation->RoomStatus ?? 'Not Available',
                                            'color' => $availableAccommodation->accommodationType->Color ?? 'DefaultColor',
                                            'accommodation_name' => $availableAccommodation->accommodationType->AccommodationName ?? 'Not Available',
                                        ],
                                    ];
                                }
                                $InventoryModule =InventoryModule::whereIn('id', $item_id)->get();

                                foreach ($InventoryModule as $module)
                                {
                                    $module->Occupied = $module->Occupied + 1;
                                    $module->save();
                                }
                            }
                            else
                            {
                                $data=[];
                            }
                        

                    DB::commit();

                return response()->json(['success' =>true,'message'=>'Bed assigned successfully','data' =>$data], 200);
            }
            else
            {
                return response()->json(['success' =>false,'message'=>'Bed Already assigned '], 500); 
            }
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['success' =>false,'error' => 'Failed to bed Assigned','message'=>'Failed to Assign Bed  '], 500);
        }

    }
    public function GetAccmmodationwiseEmployee(Request $request)
    {
        $RoomType = base64_decode($request->RoomType);
        $available_a_id =  base64_decode($request->available_a_id);


        if($RoomType == 1 ){
            $emp_grade = [1, 3 , 7 , 8];
        }
        else if($RoomType == 4){
            $emp_grade = [4];
        }
        else if($RoomType == 2){
            $emp_grade = [ 2];
        }
        else if($RoomType == 5){
            $emp_grade = [ 5];
        }
        else{
            $emp_grade =[6];
        }

        $AvailableAccommodationModel = AvailableAccommodationModel::where("id", $available_a_id)
                                                                    ->pluck('blockFor')
                                                                    ->map(function ($value) {
                                                                        return strtolower($value);
                                                                    });

        $Employees = Employee::leftJoin('assing_accommodations as t3', function ($join) {
            $join->on('t3.emp_id', '=', 'employees.id')
                 ->where('t3.resort_id', '=', $this->resort->resort_id);
        })
        ->join('resort_admins as t1', 't1.id', '=', 'employees.Admin_Parent_id')
        ->join('resort_positions as t2', 't2.id', '=', 'employees.Position_id')
        ->select(
               't1.id as Parentid',
                        't1.first_name',
                        't1.last_name',
                        't1.profile_picture',
                        'employees.Emp_id',
                        'employees.id as EmployeeId',
                        't2.position_title',
                        't3.available_a_id'
        )
        ->whereIn('t1.gender', $AvailableAccommodationModel)
        ->whereIn('employees.rank',  $emp_grade) // Normal where for comparison with $RoomType

        ->whereNull('t3.available_a_id')
        ->get();


        $AssingAccommodation = AssingAccommodation::where("emp_id",0)->where("available_a_id", $available_a_id)->get();


        return response()->json(['success' =>true,'Employees'=>$Employees,'AssingAccommodation'=>$AssingAccommodation], 200);
    }

    public function MoveToNext(Request $request)
    {
        $Reason        = $request->Reason;
        $assignId      = $request->assignId;
        $emp_id        = $request->emp_id;
        $OldAssingedId = $request->OldAssingedId;
        $ChildBedId    = $request->ChildBedId;
            $validator = Validator::make($request->all(), [
                'assignId' =>'required',
                'emp_id' => 'required',
                'OldAssingedId'=>'required'
            ],
            [
                'assignId.required' => 'Please select Bed',
                'OldAssingedId.required'=>"Please Provide Last Accommodation Details",
                'emp_id.required' => 'Please select Employee'
            ]);

            if ($validator->fails()) 
            {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            DB::beginTransaction();
            try{

            $Employeelist = Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                        ->join('resort_positions as t2', "t2.id", "=", "employees.Position_id")
                        ->join('assing_accommodations as t3', "t3.emp_id", "=", "employees.id")
                        ->select(
                            't1.id as Parentid',
                            't1.first_name',
                            't1.last_name',
                            't1.profile_picture',
                            'employees.Emp_id as EmployeeId',
                            't2.position_title',
                            't3.available_a_id',
                            'employees.id as new_emp_id'
                        )
                        ->where("t3.id", $ChildBedId)
                        ->first();
                $data=[];
                if ($Employeelist)
                {
                    $availableAccommodation = AvailableAccommodationModel::where("id", $Employeelist->available_a_id)
                                                                        ->where('resort_id', $this->resort->resort_id)
                                                                        ->with('availableAccommodationInvItem.inventoryModule', 'accommodationType') // Eager load relationships
                                                                        ->first();
                    if ($availableAccommodation)
                    {
                        $itemData = [];
                        $item_id=[];
                        // Process inventory items
                        foreach ($availableAccommodation->availableAccommodationInvItem as $item)
                        {
                            $inventoryItem = $item->inventoryModule ? ucfirst($item->inventoryModule->ItemName) : 'Unknown';
                            $item_id[] = $item->inventoryModule->id;
                            $itemData[] = $inventoryItem;
                        }
                        $data = [
                            'employee' => [
                                'name' => ucfirst($Employeelist->first_name . ' ' . $Employeelist->last_name),
                                'position' => ucfirst($Employeelist->position_title),
                                'profile_picture' => Common::getResortUserPicture($Employeelist->Parentid),
                                'emp_id' => $Employeelist->EmployeeId,
                            ],
                            'accommodation' => [
                                'building_name' => $availableAccommodation->BuildingName ?? 'Not Available',
                                'floor' => $availableAccommodation->Floor ?? 'Not Available',
                                'room_no' => $availableAccommodation->RoomNo ?? 'Not Available',
                                'facilities' => $itemData,
                                'RoomStatus'=>$availableAccommodation->RoomStatus ?? 'Not Available',
                                'color' => $availableAccommodation->accommodationType->Color ?? 'DefaultColor',
                                'accommodation_name' => $availableAccommodation->accommodationType->AccommodationName ?? 'Not Available',
                            ],
                        ];
                    }
                    $InventoryModule =InventoryModule::whereIn('id', $item_id)->get();

                    foreach ($InventoryModule as $module)
                    {
                        $module->Occupied = $module->Occupied - 1;
                        $module->save();
                    }
                    // AssingAccommodation::where("id",$OldAssingedId)->where(['emp_id'=>$emp_id])
                    // ->update(["effected_date"=>null]);
                }
                $AssingData = AssingAccommodation::where("id",$ChildBedId)->first();

                TransferAccommodation::create(['Emp_id'=>$emp_id,'resort_id'=>$this->resort->resort_id,'OldDate'=> $AssingData->effected_date, 'NewdDate'=>Date('Y-m-d'),'NewAccommodation_id'=>$assignId,"OldAccommodation_id"=>$ChildBedId,"Reason"=>$Reason]);
                $AssingData->emp_id=0;
                $AssingData->effected_date=null;
                $AssingData->save();
                AssingAccommodation::where("id",$assignId)->update(['emp_id'=>$emp_id,"effected_date"=>date('Y-m-d')]);

                DB::commit();
                return response()->json(['success' =>true,'message'=>'Bed assigned successfully','data' =>$data], 200);
    

            }
            catch (\Exception $e)
            {
                DB::rollBack();
                \Log::emergency("File: " . $e->getFile());
                \Log::emergency("Line: " . $e->getLine());
                \Log::emergency("Message: " . $e->getMessage());
                return response()->json(['success' =>false,'error' => 'Failed to bed Assigned','message'=>'Failed to Assign Bed  '], 500);
            }

    }
}
