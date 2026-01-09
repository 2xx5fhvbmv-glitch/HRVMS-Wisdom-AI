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
use App\Models\ChildApprovedMaintanaceRequests;
use Carbon\Carbon;
use Validator;
use File;
use Auth;
use URL;
use DB;

class AccommodationController extends Controller
{
    protected $user;
    protected $resort_id;
    protected $underEmp_id = [];

    public function __construct()
    {

        if (Auth::guard('api')->check()) {
            $this->user = Auth::guard('api')->user();
            $this->resort_id = $this->user->resort_id;
            $this->reporting_to = $this->user->GetEmployee->id;
            $this->underEmp_id = Common::getSubordinates($this->reporting_to);
        }
    }

    public function HR_Dashobard()
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {

            $employee                                       = $this->user->GetEmployee;
            $rank                                           = config('settings.Position_Rank');
            $current_rank                                   = $employee->rank ?? null;
            $available_rank                                 = $rank[$current_rank] ?? '';

            $accomodationData                               =   [];
            $EmployeesCount                                 =   Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                                                                    ->where("t1.resort_id", $this->resort_id)
                                                                    ->count();

            $OccupiedBed                                    =   AssingAccommodation::where("resort_id", $this->resort_id)
                                                                    ->where('emp_id', '!=', 0)->count();

            $AvailableAccomodation                          =   AssingAccommodation::where("resort_id", $this->resort_id)->where('emp_id', 0)->count();

            $bedStatic                                      =   [];
            $totalOccupiedMaleBeds                          =   0;
            $totalMaleBeds                                  =   0;
            $totalOccupiedFemaleBeds                        =   0;
            $totalFemaleBeds                                =   0;

            $buildings                                      =   BuildingModel::where("resort_id", $this->resort_id)->get()->map(function ($building) use (&$totalOccupiedMaleBeds, &$totalMaleBeds, &$totalOccupiedFemaleBeds, &$totalFemaleBeds) {
                $OccupiedMaleBeds                           =   AvailableAccommodationModel::join('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
                                                                    ->where('available_accommodation_models.resort_id', $this->resort_id)
                                                                    ->where("BuildingName", $building->id)
                                                                    ->where("t1.emp_id", "!=", 0)
                                                                    ->where("available_accommodation_models.blockFor", 'Male')
                                                                    ->first([DB::raw('COUNT(t1.id) as OccupiedMaleBeds')]);

                $OccupiedMaleBedsNew                        =   $OccupiedMaleBeds->OccupiedMaleBeds ?? 0;
                $OccupiedFemaleBeds                         =   AvailableAccommodationModel::join('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
                                                                    ->where('available_accommodation_models.resort_id', $this->resort_id)
                                                                    ->where("BuildingName", $building->id)
                                                                    ->where("t1.emp_id", "!=", 0)
                                                                    ->where("available_accommodation_models.blockFor", 'Female')
                                                                    ->first([DB::raw('COUNT(t1.id) as OccupiedFemaleBeds')]);

                $OccupiedFemaleBedsNew                      =   $OccupiedFemaleBeds->OccupiedFemaleBeds ?? 0;
                $Othercounts                                =   AvailableAccommodationModel::where('BuildingName', $building->id)
                                                                    ->where('resort_id', $this->resort_id)
                                                                    ->select(
                                                                        DB::RAW('SUM(CASE WHEN available_accommodation_models.blockFor = \'Female\' THEN available_accommodation_models.Capacity ELSE 0 END) as FemaleAvailableBeds'),
                                                                        DB::RAW('SUM(CASE WHEN available_accommodation_models.blockFor = \'Male\' THEN available_accommodation_models.Capacity ELSE 0 END) as MaleAvailableBeds')
                                                                    )
                                                                    ->first();

                $totalOccupiedMaleBeds                      +=  $OccupiedMaleBedsNew;
                $totalMaleBeds                              +=  $Othercounts->MaleAvailableBeds ?? 0;
                $totalOccupiedFemaleBeds                    +=  $OccupiedFemaleBedsNew;
                $totalFemaleBeds                            +=  $Othercounts->FemaleAvailableBeds ?? 0;
            });

            // Format output as occupied/total
            $bedStatic                                         = [
                'male_beds_total'                           => $totalMaleBeds,
                'male_beds_occupied'                        => $totalOccupiedMaleBeds,
                'female_beds_total'                         => $totalFemaleBeds,
                'female_beds_occupied'                      => $totalOccupiedFemaleBeds,
            ];

            $BuildingModel                                  =   BuildingModel::join('bulidng_and_floor_and_rooms as t1', 't1.building_id', '=', 'building_models.id')
                                                                    ->where("building_models.resort_id", $this->resort_id)
                                                                    ->groupBy('building_models.id', 'building_models.id')
                                                                    ->get(['building_models.*', DB::RAW('Count(t1.building_id) as TotalRoom')])
                                                                    ->map(function ($building) {

                    $accommodations                         =   AvailableAccommodationModel::join('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
                                                                    ->join('employees as t2', 't2.id', '=', 't1.emp_id')
                                                                    ->join('resort_admins as t3', 't3.id', '=', 't2.Admin_Parent_id')
                                                                    ->where('available_accommodation_models.resort_id', $this->resort_id)
                                                                    ->where("BuildingName", $building->id)
                                                                    ->with('availableAccommodationInvItem.inventoryModule', 'accommodationType')
                                                                    ->get(['t1.emp_id', 't3.id as Parentid', 't3.first_name', 't3.last_name', 'available_accommodation_models.*'])
                                                                    ->map(function ($accommodation) {
                                                                        $assignedAccommodations                         =   AssingAccommodation::where("available_a_id", $accommodation->id)->get();
                                                                        $accommodation->EmployeeName                    =   ucfirst($accommodation->first_name . ' ' . $accommodation->last_name);
                                                                        $accommodation->profileImg                      =   Common::getResortUserPicture($accommodation->Parentid);
                                                                        $accommodation->AssingAccommodationCount        =   $assignedAccommodations->where("emp_id", 0)->count();
                                                                        $accommodation->bedAvailable                    =   ($accommodation->Capacity == $accommodation->AssingAccommodationCount) ? $accommodation->Capacity : $accommodation->AssingAccommodationCount;

                                                                        return $accommodation;
                                                                    });

                    $a                                      =   AvailableAccommodationModel::join('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
                                                                    ->where('available_accommodation_models.resort_id', $this->resort_id)
                                                                    ->where("BuildingName", $building->id)
                                                                    ->where("t1.emp_id", 0)
                                                                    ->groupBy('t1.available_a_id')
                                                                    ->first(['available_accommodation_models.id', 'available_accommodation_models.Capacity', DB::raw('COUNT(t1.id ) as AvailableRooms')]);
                    $AvailableRooms                             =   0;
                    if (isset($a->AvailableRooms)) {
                        if ($a->AvailableRooms < $a->Capacity) {
                            $AvailableRooms                     =   1;
                        } else {
                            $AvailableRooms                     =   $a->AvailableRooms;
                        }
                    }

                    $BedCapacity                                =   AvailableAccommodationModel::where('resort_id', $this->resort_id)
                                                                        ->where("BuildingName", $building->id)
                                                                        ->get([DB::RAW('SUM(available_accommodation_models.Capacity) as BedCapacity')]);

                    $AvailableBed                               =   AvailableAccommodationModel::join('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
                                                                        ->where('available_accommodation_models.resort_id', $this->resort_id)
                                                                        ->where("BuildingName", $building->id)
                                                                        ->where("emp_id", 0)
                                                                        // ->groupBy('t1.availabl  e_a_id')
                                                                        ->first([DB::raw('COUNT(t1.id ) as BedCapacity')]);

                    $building->AvailableRooms                   =   $AvailableRooms;
                    $building->BedCapacity                      =   isset($BedCapacity[0]) ? (int)$BedCapacity[0]->BedCapacity : 0;
                    $building->AvailableBed                     =   isset($AvailableBed) ? $AvailableBed->BedCapacity : 0;
                    return $building;
                });
            $BuildingCountData                              =   BuildingModel::where('resort_id', $this->resort_id)
                                                                    ->orderBy("id", "DESC")
                                                                    ->count();

            $BulidingRoomCountData                          =   BulidngAndFloorAndRoom::where('resort_id', $this->resort_id)->orderBy("id", "DESC")
                                                                    ->count();

            $MaintanaceRequest                              =   MaintanaceRequest::join("employees as t3", "t3.id", "maintanace_requests.Raised_By")
                                                                    ->join("resort_admins as t1", "t1.id", "t3.Admin_Parent_id")
                                                                    ->join("resort_departments as t4", "t4.id", "t3.Dept_id")
                                                                    ->orderBy('maintanace_requests.created_at', 'desc') // Order by latest
                                                                    ->take(2) // Get only the last 2 records
                                                                    ->whereNotIn('maintanace_requests.Status', ['Closed', 'On-Hold']);

            $MaintanaceRequest                              =   $MaintanaceRequest->leftjoin("resort_admins as t2", "t2.id", "maintanace_requests.Assigned_To")
                                                                    ->get(['maintanace_requests.*','t1.id as Parentid', 't1.first_name', 't1.last_name', 't3.Emp_id', 't2.id as Assign_Parentid', 't2.first_name as Assign_first_name', 't2.last_name as Assign_last_name'])
                                                                    ->map(function ($row) {
                                                                        $row->RequestedBy = $row->first_name . ' ' . $row->last_name;
                                                                        $row->AssgingedStaff = $row->Assigned_To;
                                                                        $row->Location = $row->BuilidngData->BuildingName . ',Room No - ' . $row->RoomNo . ',Floor No -' . $row->FloorNo;
                                                                        // $row->Date =$row->created_at->format('d M Y');
                                                                        $row->profileImg = Common::getResortUserPicture($row->Parentid);
                                                                        $InventoryModule = InventoryModule::where('resort_id', $this->resort_id)
                                                                            ->where("id", $row->item_id)
                                                                            ->first('ItemName');

                                                                        if (isset($row->Assigned_To)) {
                                                                            $emp = Common::GetEmployeeDetails($row->Assigned_To);

                                                                            $row->Assign_profileImg = Common::getResortUserPicture($emp->Parent_id);
                                                                            $row->Assign_toName     = $emp->first_name . ' ' . $row->last_name;
                                                                        }
                                                                        $row->EffectedAmenity = isset($InventoryModule) ? ucfirst($InventoryModule->ItemName) : '';
                                                                        return  $row;
                                                                    });

            $currentDate                                    =   Carbon::now()->format('Y-m-d');
            $housekeepingSchedules                          =   HousekeepingSchedules::join('building_models as bm', 'bm.id', '=', 'housekeeping_schedules.BuildingName')
                                                                    ->where('housekeeping_schedules.resort_id', $this->resort_id)
                                                                    ->where('housekeeping_schedules.date',  $currentDate)
                                                                    ->select('housekeeping_schedules.*', 'bm.BuildingName as BName')
                                                                    ->get();

            $accomodationData['total_employee']             =   $EmployeesCount;
            $accomodationData['occupied_bed']               =   $OccupiedBed;
            $accomodationData['available_bed']              =   $AvailableAccomodation;
            $accomodationData['bed_static']                 =   $bedStatic;
            $accomodationData['building_static']            =   $BuildingModel;
            $accomodationData['accommodation_building']     =   $BuildingCountData;
            $accomodationData['accommodation_room']         =   $BulidingRoomCountData;
            $accomodationData['MaintanaceRequest']          =   $MaintanaceRequest->toArray();
            $accomodationData['housekeeping_schedules']     =   $housekeepingSchedules;

            $response['status']                             =   true;
            $response['message']                            =   'Accomodation ' . $available_rank . ' Dashboard';
            $response['accomodation_data']                  =   $accomodationData;

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function getBuilding()
    {

        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {

            $BuildingData                                   =   BuildingModel::where('resort_id', $this->resort_id)
                                                                    ->orderBy("id", "DESC")
                                                                    ->get();

            $response['status']                             =   true;
            $response['message']                            =   'Building data retrieved successfully';
            $response['building_data']                      =   $BuildingData;

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function getBuildingWiseFloor(Request $request)
    {

        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'building_id'                                 =>  'required'
        ]);


        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        try {
            $building_id                                    =   $request->building_id;
            $BulidngAndFloorAndRoom                         =   BulidngAndFloorAndRoom::where('resort_id', $this->resort_id)
                ->where("building_id", $building_id)
                ->groupBy('Floor')
                ->pluck('Floor');

            $response['status']                             =   true;
            $response['message']                            =   'Floor data retrieved successfully';
            $response['floor_data']                         =   $BulidngAndFloorAndRoom;

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function getFloorWiseRooms(Request $request)
    {

        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'building_id'                                   =>    'required',
            'floor'                                         =>    'required'
        ]);


        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        try {
            $floor                                          =   $request->floor;
            $building_id                                    =   $request->building_id;

            $BulidngAndFloorAndRoom                         =   BulidngAndFloorAndRoom::where('resort_id', $this->resort_id)
                ->where("building_id", $building_id)
                ->where("Floor", $floor)
                ->pluck('Room');

            $response['status']                             =   true;
            $response['message']                            =  'Rooms data retrieved successfully';
            $response['room_data']                          =   $BulidngAndFloorAndRoom;

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function getAccommodationType()
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {

            $AccommodationType                              =   AccommodationType::where('resort_id', $this->resort_id)->get();

            if ($AccommodationType->isEmpty()) {
                $response['status']                         =   false;
                $response['message']                        =   'No accommodation types found';
                $response['accommodation_type']             =   [];
                return response()->json($response);
            }

            $response['status']                             =   true;
            $response['message']                            =   'Accommodation Type retrieved successfully';
            $response['accommodation_type']                 =   $AccommodationType;
            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function getBuildingWiseEmployee(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'building_id'                                   =>    'required',
            'floor'                                         =>    'required',
            'room'                                          =>    'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }
        try {

            $building_id                                    =   $request->building_id;
            $floor                                          =   $request->floor;
            $room                                           =   $request->room;

            $accommodations                                 =   AvailableAccommodationModel::join('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
                                                                    ->join('employees as t2', 't2.id', '=', 't1.emp_id')
                                                                    ->join('resort_positions as rp', 'rp.id', '=', 't2.Position_id')
                                                                    ->join('resort_admins as t3', 't3.id', '=', 't2.Admin_Parent_id')
                                                                    ->where('available_accommodation_models.resort_id', $this->resort_id)
                                                                    ->where('available_accommodation_models.RoomNo', $room)
                                                                    ->where('available_accommodation_models.Floor', $floor)
                                                                    ->where('available_accommodation_models.BuildingName', $building_id)
                                                                    ->get([
                                                                        't1.emp_id',
                                                                        't3.id as Parentid',
                                                                        't3.first_name',
                                                                        't3.last_name',
                                                                        'rp.position_title',
                                                                        'available_accommodation_models.*'
                                                                    ]);

            // Fetch all assigned accommodations
            $assignedAccommodations                             =   AssingAccommodation::join('employees as t2', 't2.id', '=', 'assing_accommodations.emp_id')
                                                                        ->join('resort_admins as t3', 't3.id', '=', 't2.Admin_Parent_id')
                                                                        ->whereIn("assing_accommodations.available_a_id", $accommodations->pluck('id')) // Get all IDs in one query
                                                                        ->get(['assing_accommodations.available_a_id', 't3.first_name', 't3.last_name', 't3.id as Parentid'])
                                                                        ->groupBy('available_a_id');
            // Group accommodations
            $groupedData                                        =   [];

            foreach ($accommodations as $key => $acc) {
                if (!isset($groupedData[$key])) {
                    $groupedData[$key]                          =   [
                        'emp_id'                                =>  $acc->emp_id,
                        'Parentid'                              =>  $acc->Parentid,
                        'first_name'                            =>  $acc->first_name,
                        'last_name'                             =>  $acc->last_name,
                        'position_title'                        =>  $acc->position_title,
                        'id'                                    =>  $acc->id,
                        'resort_id'                             =>  $acc->resort_id,
                        'BuildingName'                          =>  $acc->BuildingName,
                        'Floor'                                 =>  $acc->Floor,
                        'RoomNo'                                =>  $acc->RoomNo,
                        'Accommodation_type_id'                 =>  $acc->Accommodation_type_id,
                        'RoomType'                              =>  $acc->RoomType,
                        'BedNo'                                 =>  $acc->BedNo,
                        'blockFor'                              =>  $acc->blockFor,
                        'Capacity'                              =>  $acc->Capacity,
                        'CleaningSchedule'                      =>  $acc->CleaningSchedule,
                        'RoomStatus'                            =>  $acc->RoomStatus,
                        'Occupancytheresold'                    =>  $acc->Occupancytheresold,
                        'created_by'                            =>  $acc->created_by,
                        'modified_by'                           =>  $acc->modified_by,
                        'Colour'                                =>  $acc->Colour,
                        'created_at'                            =>  $acc->created_at,
                        'updated_at'                            =>  $acc->updated_at,
                        'AssingAccommodation'                   =>  []
                    ];
                }

                // Assign accommodation data if exists
                $assingAccomData                                =   $assignedAccommodations->get($acc->id, collect())->map(function ($row) {
                    return [
                        'first_name'                            =>  $row->first_name,
                        'last_name'                             =>  $row->last_name,
                        'Parentid'                              =>  $row->Parentid,
                        'EmployeeName'                          =>  ucfirst($row->first_name . ' ' . $row->last_name),
                        'profileImg'                            =>  Common::getResortUserPicture($row->Parentid)
                    ];
                });

                $groupedData[$key]['AssingAccommodation']       =   $assingAccomData;
                $groupedData[$key]['AssingAccommodationCount']  =   $assingAccomData->count();
                $groupedData[$key]['AvailableBedCount']         =   $acc->Capacity - $assingAccomData->count();
            }

            $response['status']                             =   true;
            $response['message']                            =   'Building-wise employee data retrieved successfully';
            $response['building_emp_data']                  =   $groupedData;

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function accommodationEmployeeDetails($id)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {

            $id                                             =   base64_decode($id);
            $employeData                                    =   AvailableAccommodationModel::join('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
                                                                    ->join('employees as t2', 't2.id', '=', 't1.emp_id')
                                                                    ->join('resort_positions as t4', 't4.id', '=', 't2.Position_id')
                                                                    ->join('resort_departments as t6', 't6.id', '=', 't2.Dept_id')
                                                                    ->join('resort_admins as t3', 't3.id', '=', 't2.Admin_Parent_id')
                                                                    ->join('building_models as t5', 't5.id', '=', 'available_accommodation_models.BuildingName')
                                                                    ->join('bulidng_and_floor_and_rooms as t7', 't7.building_id', '=', 'available_accommodation_models.BuildingName')
                                                                    ->join('accommodation_types as t8', 't8.id', '=', 'available_accommodation_models.Accommodation_type_id')
                                                                    ->where('t2.id', $id)
                                                                    ->where('available_accommodation_models.resort_id', $this->resort_id)
                                                                    ->groupBy('t2.id')
                                                                    ->first(['available_accommodation_models.id as AvailableAccommodation_ID', 'available_accommodation_models.CleaningSchedule', 't8.AccommodationName', 't7.Floor', 't7.Room', 't5.BuildingName as BName', 't6.name as DepartmentName', 't4.position_title', 't2.id as employee_id', 't2.Emp_id', 't3.id as Parentid', 't3.first_name', 't3.last_name', 'available_accommodation_models.*', 't1.effected_date']);

            $AssingAccommodation                            =   AssingAccommodation::where("available_a_id", $employeData->AvailableAccommodation_ID)
                                                                ->where("t2.id", "!=", $employeData->employee_id)
                                                                ->join('employees as t2', 't2.id', '=', 'assing_accommodations.emp_id')
                                                                ->join('resort_admins as t3', 't3.id', '=', 't2.Admin_Parent_id')
                                                                ->get(['t3.first_name', 't3.last_name', 't3.id as Parentid'])->map(function ($row) {
                                                                    $row->EmployeeName = ucfirst($row->first_name . ' ' . $row->last_name);
                                                                    $row->profileImg = Common::getResortUserPicture($row->Parentid);
                                                                    return $row;
                                                                });

            $employeData->profileImg                        =   Common::getResortUserPicture($employeData->Parentid);
            $employeData->EmployeeName                      =   ucfirst($employeData->first_name . ' ' . $employeData->last_name);
            $employeData->effected_date                     =   Carbon::parse($employeData->effected_date)->format('d F Y');
            $now                                            =   Carbon::now();
            $effectedDate                                   =   Carbon::parse($employeData->effected_date);
            $years                                          =   $effectedDate->diffInYears($now);
            $months                                         =   $effectedDate->copy()->addYears($years)->diffInMonths($now);
            $employeData->effected_date_diff                =   "{$years} year(s), {$months} month(s)";
            $InventoryModule                                =   InventoryModule::where('resort_id', $this->resort_id)->get();
            $empArr                                         =   [];
            $empArr['emp_data']                             =   $employeData;
            $empArr['Inventory']                            =   $InventoryModule;
            $empArr['assign_wtih']                          =   $AssingAccommodation;

            $response['status']                             =   true;
            $response['message']                            =   'Building-wise employee data retrieved successfully';
            $response['building_emp_data']                  =   $empArr;

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function hrMaintenanceRequestDashboard()
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {

            $employee                                       =   $this->user->GetEmployee;
            $employee_id                                    =   $this->user->GetEmployee->id;

            $baseQuery                                      =   MaintanaceRequest::join("employees as t3", "t3.id", "=", "maintanace_requests.Raised_By")
                                                                    ->join("resort_admins as t1", "t1.id", "=", "t3.Admin_Parent_id")
                                                                    ->join("resort_departments as t4", "t4.id", "=", "t3.Dept_id")
                                                                    ->where('maintanace_requests.resort_id', $this->resort_id);

            $MaintanaceReqOpenCount                         =   (clone $baseQuery)->where('maintanace_requests.Status', 'Open')->count();
            $MaintanaceReqHighCount                         =   (clone $baseQuery)->where('maintanace_requests.priority', 'High')->count();
            $MaintanaceReqCompleteCount                     =   (clone $baseQuery)->where('maintanace_requests.Status', 'Closed')->count();
            $MaintanaceReqNearingCompletionCount            =   (clone $baseQuery)->where('maintanace_requests.Status', 'In-Progress')->count();

            $EscalationDay                                  =   EscalationDay::where('resort_id', $this->resort_id)->first();

            $inventoryItems                                 =   InventoryModule::where('resort_id', $this->resort_id)->pluck('ItemName', 'id');

            $MaintanaceRequest                              =   (clone $baseQuery)
                                                                    ->whereNotIn('maintanace_requests.Status', ['Closed','ResolvedAwaiting'])
                                                                    ->orderBy('maintanace_requests.created_at', 'desc')
                                                                    ->get(['maintanace_requests.*','t1.id as Parentid', 't1.first_name', 't1.last_name'])
                                                                    ->map(function ($row) use ($EscalationDay, $inventoryItems) {
                                                                        return $this->formatMaintenanceRow($row, $EscalationDay, $inventoryItems);
                                                                    });

            $completeMaintananceReqQuery                    =   MaintanaceRequest::join("employees as t3", "t3.id", "=", "maintanace_requests.Raised_By")
                                                                    ->join("resort_admins as t1", "t1.id", "=", "t3.Admin_Parent_id")
                                                                    ->join("child_maintanance_requests as cmr", function ($join) use ($employee_id) {
                                                                        $join->on("cmr.maintanance_request_id", "=", "maintanace_requests.id")
                                                                            ->where("cmr.ApprovedBy", "=", $employee_id);
                                                                    })

                                                                    ->join("child_approved_maintanace_requests as camr", function ($join) use ($employee_id) {
                                                                        $join->on("camr.maintanance_request_id", "=", "maintanace_requests.id")
                                                                            ->where("camr.ApprovedBy", "=", $employee_id)
                                                                            ->where("camr.Status", "=", "Assinged"); // Note: Check spelling "Assigned" vs "Assinged"
                                                                    })
                                                                    ->where("maintanace_requests.resort_id", $this->resort_id)
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

            $data                                           =   [
                'open_req_count'                            =>  $MaintanaceReqOpenCount,
                'high_req_count'                            =>  $MaintanaceReqHighCount,
                'nearing_comp_req_count'                    =>  $MaintanaceReqNearingCompletionCount,
                'complete_req_count'                        =>  $MaintanaceReqCompleteCount,
                'maintanace_request'                        =>  $MaintanaceRequest,
                'maintanace_comp_task'                      =>  $completeMaintananceReqQuery,
            ];

            $response['status']                             =   true;
            $response['message']                            =   'Maintances Request retrieved successfully';
            $response['maintanace_request_data']                 =   $data;
            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * **Helper Function: Format Maintenance Request Row**
     */
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

    public function mainRequestDetails($id)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        try {
            $id                                             =   base64_decode($id);
            $MaintanaceRequest                              =   MaintanaceRequest::join("employees as t3", "t3.id", "=", "maintanace_requests.Raised_By")
                                                                    ->join("resort_admins as t1", "t1.id", "=", "t3.Admin_Parent_id")
                                                                    ->join('resort_positions as rp', 'rp.id', '=', 't3.Position_id')
                                                                    ->where('maintanace_requests.resort_id', $this->resort_id)
                                                                    ->where("maintanace_requests.id", $id)
                                                                    ->first(['maintanace_requests.*','t1.id as Parentid', 't1.first_name', 't1.last_name', 'rp.position_title', 't3.Emp_id']);
            

            $childApprMaintReq                              =   ChildMaintananceRequest::join('child_approved_maintanace_requests as camr','camr.child_maintanance_request_id', '=','child_maintanance_requests.id')
                                                                    ->where('camr.maintanance_request_id',$id)
                                                                    ->get(['camr.id as child_appr_maint_req_id','camr.Status as child_appr_maint_req_status']);
            
            $childMaintReq                                  =   ChildMaintananceRequest::where('maintanance_request_id',$id)->where('rank',6)->where('Status','In-Progress')
                                                                    ->first();
            
           
            $MaintanaceRequest->child_approved_data         =   $childApprMaintReq;
            $MaintanaceRequest->child_accept_status_data    =   $childMaintReq;

            if ($MaintanaceRequest) { // Ensure request exists before processing
                $MaintanaceRequest->profileImg               =   Common::getResortUserPicture($MaintanaceRequest->Parentid);
                // **Check & Assign Image Path**
                if (!empty($MaintanaceRequest->Image)) {
                    $path_path                              =   config('settings.MaintanceRequest') . '/' . $this->resort_id;
                    $MaintanaceRequest->Image               =   URL::asset($path_path . '/' . $MaintanaceRequest->Image);
                }

                // **Check & Assign Video Path**
                if (!empty($s->Video)) {
                    $path_path                              =   config('settings.MaintanceRequest') . '/' . $this->resort_id;
                    $MaintanaceRequest->Video               =   URL::asset($path_path . '/' . $MaintanaceRequest->Video);
                }

                // **Check & Assign Image Path**
                if (!empty($MaintanaceRequest->Completed_Image )) {
                    $path_path                              =   config('settings.MaintanceRequest') . '/' . Auth::guard('api')->user()->resort->resort_id;
                    $MaintanaceRequest->Completed_Image     =   URL::asset($path_path . '/' . $MaintanaceRequest->Completed_Image);
                }

                $MaintanaceRequest->BuilidngData;

                $InventoryModule                            =   InventoryModule::where('resort_id', $this->resort_id)
                                                                    ->where("id", $MaintanaceRequest->item_id)
                                                                    ->first('ItemName');
                                                                    
                $MaintanaceRequest->EffectedAmenity         =   ucfirst($InventoryModule->ItemName);
            }

            $childMaintCompleteComment                       =   ChildMaintananceRequest::where('maintanance_request_id',$id)->where('rank',6)->where('Status','Resolvedawaiting')
                                                                    ->first();
                                                                   
            if ($childMaintCompleteComment) {
                $MaintanaceRequest->complete_comment        = $childMaintCompleteComment->comments;
            } else {
                $MaintanaceRequest->complete_comment        = '';
            }

            $response['status']                             =   true;
            $response['message']                            =   'Maintances request details retrieved successfully';
            $response['maintanace_request']                 =   $MaintanaceRequest;
            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function employeeList()
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        try {
            $Employeelist                                   =   Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                                                                    ->join('resort_positions as t2', "t2.id", "=", "employees.Position_id")
                                                                    ->select(
                                                                        'employees.id',
                                                                        't1.id as Parentid',
                                                                        't1.first_name',
                                                                        't1.last_name',
                                                                        't1.profile_picture',
                                                                        'employees.Emp_id as EmployeeId',
                                                                        't2.position_title',
                                                                        'employees.rank',
                                                                    )
                                                                    ->groupBy('employees.id')
                                                                    ->where("t1.resort_id", $this->resort_id)
                                                                    ->get()
                                                                    ->map(function ($item) {
                                                                        $item->EmployeeName = ucfirst($item->first_name . ' ' . $item->last_name);
                                                                        $item->Position = ucfirst($item->position_title);
                                                                        $item->profileImg = Common::getResortUserPicture($item->Parentid);
                                                                        $item->Emp_id = $item->EmployeeId;
                                                                        return $item;
                });
            $response['status']                             =   true;
            $response['message']                            =   'Maintances request details retrieved successfully';
            $response['employee_list']                      =   $Employeelist;
            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function hrBedAssign($empId)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $employeeRank                                   =   Employee::find($empId);
            $availableAccommodation                         =   AvailableAccommodationModel::join('accommodation_types as at', 'at.id', '=', 'available_accommodation_models.Accommodation_type_id')
                                                                    ->join('building_models as bm', 'bm.id', '=', 'available_accommodation_models.BuildingName')
                                                                    ->leftJoin('bulidng_and_floor_and_rooms as bf', function ($join) {
                                                                        $join->on('bf.id', '=', 'available_accommodation_models.Floor')
                                                                            ->on('bf.building_id', '=', 'available_accommodation_models.BuildingName');
                                                                    })
                                                                    ->leftJoin('bulidng_and_floor_and_rooms as bfr', function ($join) {
                                                                        $join->on('bfr.Room', '=', 'available_accommodation_models.RoomNo')
                                                                            ->on('bfr.building_id', '=', 'available_accommodation_models.BuildingName')
                                                                            ->on('bfr.Floor', '=', 'available_accommodation_models.Floor');
                                                                    })
                                                                    // ->leftJoin('assing_accommodations as ac', function ($join) {
                                                                    //     $join->on('ac.available_a_id', '=', 'available_accommodation_models.BedNo')->where('ac.emp_id',0); 
                                                                    // })
                                                                    ->where('available_accommodation_models.resort_id', $this->resort_id)
                                                                    ->where('available_accommodation_models.RoomType', $employeeRank->rank)
                                                                    ->select(
                                                                        'available_accommodation_models.*',
                                                                        'at.id as acc_type_id',
                                                                        'at.AccommodationName',
                                                                        'bm.BuildingName as BName',
                                                                        'bm.id as building_id',
                                                                        'bfr.id as room_id',
                                                                        'bfr.Room as room_num',
                                                                        // 'ac.id as Bed_id',   
                                                                        // 'ac.BedNo as bed_no'
                                                                    )
                                                                    ->groupBy(
                                                                        'available_accommodation_models.id',
                                                                        'bm.id',
                                                                        'bf.id',
                                                                        'bfr.id',
                                                                        // 'ac.id' 
                                                                    )
                                                                    ->get()
                                                                    ->groupBy('building_id') // Group accommodations by building
                                                                    ->map(function ($buildingGroup) {
                                                                    return $buildingGroup->map(function ($item) use ($buildingGroup) {
                                                                        $itemArray  =   $item->toArray();
                                                                        $bed_data   =   AssingAccommodation::where('resort_id', $this->resort_id)
                                                                                            ->where('available_a_id', $item->id)
                                                                                            ->where('emp_id', 0)
                                                                                            ->get(['BedNo', 'id'])
                                                                                            ->map(function ($bed) {
                                                                                                return [
                                                                                                    'asssign_id'    => $bed->id,
                                                                                                    'Bed_no'        => $bed->BedNo
                                                                                                ];
                                                                                            });

                                                                        // Collect all floors under the same building
                                                                        $floors = $buildingGroup->groupBy('Floor')->map(function ($floorGroup) use ($bed_data) {
                                                                            return [
                                                                                'Floor'                     => $floorGroup->first()->Floor,
                                                                                'roomData'                  => $floorGroup->groupBy('room_id')->map(function ($roomGroup) use ($bed_data) {
                                                                                    return [
                                                                                        'room_id'           => $roomGroup->first()->room_id,
                                                                                        'room_num'          => $roomGroup->first()->room_num,
                                                                                        'bed_data'          => $bed_data,
                                                                                    ];
                                                                                })->values()
                                                                            ];
                                                                        })->values(); // Ensure unique floors

                                                                        // Add nested structure
                                                                        $itemArray['BuildingData']              = [
                                                                            [
                                                                                'building_id'                   => $itemArray['building_id'],
                                                                                'BuildingName'                  => $itemArray['BName'],
                                                                                'flootData'                     => $floors
                                                                            ]
                                                                        ];

                                                                        return $itemArray;
                                                                    });
                                                                })
                                                                ->collapse() // Flatten back to a single array
                                                                ->toArray();

            $response['status']                             =   true;
            $response['message']                            =   'Bed assignments data retrieved successfully';
            $response['bed_assign']                 =   $availableAccommodation;
            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function assignAccommodationToEmp(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'emp_id'                                        =>    'required',
            'assign_id'                                     =>    'required',
            'accommodation_type_id'                         =>    'required',
            'building_id'                                   =>    'required',
            'floor'                                         =>    'required',
            'room'                                          =>    'required',
            'bed'                                           =>    'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        $assignId                                           =   $request->assign_id;
        $emp_id                                             =   $request->emp_id;

        try {
            DB::beginTransaction();

            $anyAssigned                                    =   AssingAccommodation::where('emp_id', $emp_id)->first();
            if (!$anyAssigned) {
                AssingAccommodation::where("id", $assignId)->update(['emp_id' => $emp_id, "effected_date" => date('Y-m-d')]);

                $Employeelist                           =   Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
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
                if ($Employeelist) {
                    $availableAccommodation             =   AvailableAccommodationModel::where("id", $Employeelist->available_a_id)
                        ->where('resort_id', $this->resort_id)
                        ->with('availableAccommodationInvItem.inventoryModule', 'accommodationType') // Eager load relationships
                        ->first();
                    if ($availableAccommodation) {
                        $itemData                       =   [];
                        $item_id                        =   [];
                        // Process inventory items
                        foreach ($availableAccommodation->availableAccommodationInvItem as $item) {
                            $inventoryItem              =   $item->inventoryModule ? ucfirst($item->inventoryModule->ItemName) : 'Unknown';
                            $item_id[]                  =   $item->inventoryModule->id;
                            $itemData[]                 =   $inventoryItem;
                        }
                        $data = [
                            'employee'                  =>  [
                                'name'                  =>  ucfirst($Employeelist->first_name . ' ' . $Employeelist->last_name),
                                'position'              =>  ucfirst($Employeelist->position_title),
                                'profile_picture'       =>  Common::getResortUserPicture($Employeelist->Parentid),
                                'emp_id'                =>  $Employeelist->EmployeeId,
                            ],
                            'accommodation'             =>  [
                                'building_name'         =>  $availableAccommodation->BuildingName ?? 'Not Available',
                                'floor'                 =>  $availableAccommodation->Floor ?? 'Not Available',
                                'room_no'               =>  $availableAccommodation->RoomNo ?? 'Not Available',
                                'facilities'            =>  $itemData,
                                'RoomStatus'            =>  $availableAccommodation->RoomStatus ?? 'Not Available',
                                'color'                 =>  $availableAccommodation->accommodationType->Color ?? 'DefaultColor',
                                'accommodation_name'    =>  $availableAccommodation->accommodationType->AccommodationName ?? 'Not Available',
                            ],
                        ];
                    }
                    $InventoryModule                    =  InventoryModule::whereIn('id', $item_id)->get();

                    foreach ($InventoryModule as $module) {
                        $module->Occupied               =   $module->Occupied + 1;
                        $module->save();
                    }
                } else {
                    $data                               =   [];
                }

                DB::commit();

                $response['status']                             =   true;
                $response['message']                            =   'Bed assigned successfully.';
                $response['bed_assign_data']                    =   $data;
            } else {
                $response['status']                             =   false;
                $response['message']                            =   'Bed Already assigned.';
            }

            return response()->json($response);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to bed Assigned', 'message' => 'Failed to Assign Bed  '], 500);
        }
    }

    public function hrRoomInfo(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $accomodationData                               =   [];
            $EmployeesCount                                 =   Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")->where("t1.resort_id", $this->resort_id)->count();
            $OccupiedBed                                    =   AssingAccommodation::where("resort_id", $this->resort_id)->where('emp_id', '!=', 0)->count();
            $AvailableAccomodation                          =   AssingAccommodation::where("resort_id", $this->resort_id)->where('emp_id', 0)->count();


            $data                                           =   AvailableAccommodationModel::leftJoin('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
                                                                    ->leftJoin('employees as e', 'e.id', '=', 't1.emp_id')
                                                                    ->leftJoin('resort_admins as rd', "rd.id", "=", "e.Admin_Parent_id")
                                                                    ->leftJoin('resort_positions as rp', "rp.id", "=", "e.Position_id")
                                                                    ->where('available_accommodation_models.resort_id', $this->resort_id)
                                                                    ->when($request->accommodation_type_id, function ($query) use ($request) {
                                                                        return $query->where('available_accommodation_models.Accommodation_type_id', $request->accommodation_type_id);
                                                                    })
                                                                    ->when($request->building_id, function ($query) use ($request) {
                                                                        return $query->where('available_accommodation_models.BuildingName', $request->building_id);
                                                                    })
                                                                    ->when($request->floor, function ($query) use ($request) {
                                                                        return $query->where('available_accommodation_models.Floor', $request->floor);
                                                                    })
                                                                    ->when($request->room_no, function ($query) use ($request) {
                                                                        return $query->where('available_accommodation_models.RoomNo', $request->room_no);
                                                                    })
                                                                    // ->groupBy('available_accommodation_models.id')
                                                                    ->get(['available_accommodation_models.id as available_a_id', 'available_accommodation_models.*', 't1.*', 'rd.first_name', 'rd.last_name', 'rp.position_title'])
                                                                    ->map(function ($accommodation) {

                                                                        $accommodation->AccommodationName           =   $accommodation->accommodationType->AccommodationName ?? 'Not Available';

                                                                        // Fetch Assigned Accommodation Count where emp_id = 0
                                                                        $AssingAccommodationCount                   =   AssingAccommodation::where("available_a_id", $accommodation->available_a_id)
                                                                            ->where("emp_id", 0) // Filter employees with ID 0
                                                                            ->count();
                                                                        // Calculate Available Beds
                                                                        $accommodation->AssingAccommodationCount    =  $AssingAccommodationCount;

                                                                        return $accommodation;
                                                                    });

            $accomodationData['total_employee']             =   $EmployeesCount;
            $accomodationData['occupied_bed']               =   $OccupiedBed;
            $accomodationData['available_bed']              =   $AvailableAccomodation;
            $accomodationData['data']                       =   $data;

            $response['status']                             =   true;
            $response['message']                            =   'Room Information retrive successfully.';
            $response['accomodation_data']                  =   $accomodationData;

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function empListWithAvailableBed(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'room_type'                                         =>    'required',
            'available_a_id'                                    =>    'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        try {
            $RoomType                                           =   $request->room_type;
            $available_a_id                                     =   $request->available_a_id;


            if ($RoomType == 1) {
                $emp_grade                                      =   [1, 3, 7, 8];
            } else if ($RoomType == 4) {
                $emp_grade                                      =   [4];
            } else if ($RoomType == 2) {
                $emp_grade                                      =   [2];
            } else if ($RoomType == 5) {
                $emp_grade                                      =   [5];
            } else {
                $emp_grade                                      =   [6];
            }

            $AvailableAccommodationModel                        =   AvailableAccommodationModel::where("id", $available_a_id)
                ->pluck('blockFor')
                ->map(function ($value) {
                    return strtolower($value);
                });

            $Employees                                          =   Employee::leftJoin('assing_accommodations as t3', function ($join) {
                $join->on('t3.emp_id', '=', 'employees.id')
                    ->where('t3.resort_id', '=', $this->resort_id);
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

            $AssingAccommodation                                =  AssingAccommodation::where("emp_id", 0)->where("available_a_id", $available_a_id)->get();

            $accomodationData['employee_data']                  =   $Employees;
            $accomodationData['bed_data']                       =   $AssingAccommodation;

            $response['status']                                 =   true;
            $response['message']                                =   'Employee and bed data retrive successfully.';
            $response['accomodation_data']                      =   $accomodationData;

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function houseKeepingAddSchedules(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'building_id'                                       =>    'required',
            'floor'                                             =>    'required',
            'room'                                              =>    'required',
            'date'                                              =>    'required',
            'time'                                              =>    'required',
            'special_instructions'                              =>    'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        try {
            DB::beginTransaction();

            $AvailableAccommodationModel                    =   AvailableAccommodationModel::join('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
                                                                    ->where('available_accommodation_models.resort_id', $this->resort_id)
                                                                    ->where("BuildingName", $request->building_id)
                                                                    ->where("Floor", $request->floor)
                                                                    ->where("RoomNo", $request->room)
                                                                    ->first();

            $housekeepingSchCheck                           =   HousekeepingSchedules::where('BuildingName', $request->building_id)
                                                                    ->where('Floor', $request->floor)
                                                                    ->where('RoomNo', $request->room)
                                                                    ->where('date',  $request->date)
                                                                    ->where('status', 'Pending')
                                                                    ->first();

            if ($housekeepingSchCheck) {// Add HOD to the approval flow (rank 2)
                        $hodApprover                             =   Employee::select('id', 'rank')->where('rank', 2)->where('resort_id',$user->resort_id)->where('Dept_id', $employee->Dept_id)->first();
                        if ($hodApprover ) {
                            $passApprovalFlow->push($hodApprover); // Second approver: HOD
                        }

                $response['status']                         =   true;
                $response['message']                        =   'Room no-' . $request->room . ' Already Schedule.';
                // $response['data']                           =   $housekeepingSchCheck;
                return response()->json($response);
            }
            // Initialize housekeeping schedule variable
            $houseKeepingSchAdd                             =   null;
            if (!empty($AvailableAccommodationModel)) {
                $roomType                                   =   $AvailableAccommodationModel->RoomType;
                $cleanType                                  =   in_array($roomType, [1, 2, 5]) ? 'Standard' : 'Deep Cleaning';

                $houseKeepingSchAdd                         =   HousekeepingSchedules::create([
                    'resort_id'                             =>  $this->resort_id,
                    'available_a_id'                        =>  $AvailableAccommodationModel->id,
                    'BuildingName'                          =>  $request->building_id,
                    'Floor'                                 =>  $request->floor,
                    'RoomNo'                                =>  $request->room,
                    'date'                                  =>  $request->date,
                    'time'                                  =>  $request->time,
                    'special_instructions'                  =>  $request->special_instructions,
                    'clean_type'                            =>  $cleanType,
                    'status'                                =>  'Pending',
                ]);

                $childHouseKeepingSchedules                 =   ChildHouseKeepingSchedules::create([
                    'resort_id'                             =>  $this->resort_id,
                    'housekeeping_id'                       =>  $houseKeepingSchAdd->id,
                    'ApprovedBy'                            =>  0,
                    'date'                                  =>  date('Y-m-d'),
                    'status'                                =>  'Pending',
                ]);
            }

            DB::commit();
            $response['status']                             =   true;
            $response['message']                            =   'Housekeeping schedule created successfully.';
            $response['accomodation_data']                  =   $houseKeepingSchAdd;
            return response()->json($response);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function hrHouseKeepingDashboard(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $currentDate                                    =   Carbon::now()->format('Y-m-d');

        try {

            $housekeepingSchedules                      =   HousekeepingSchedules::join('building_models as bm', 'bm.id', '=', 'housekeeping_schedules.BuildingName')
                                                                ->where('housekeeping_schedules.resort_id', $this->resort_id);

            if ($request->date) {
                $housekeepingSchedules->where('housekeeping_schedules.date', $request->date);
            } else {
                $housekeepingSchedules->where('housekeeping_schedules.date',  $currentDate);
            }

            $housekeepingSchedules                      =   $housekeepingSchedules->select('housekeeping_schedules.*', 'bm.BuildingName as BName')->get();

            if ($housekeepingSchedules->isEmpty()) {
                $response['status']                     =   true;
                $response['message']                    =   'No schedules found.';
                return response()->json($response);
            }

            $response['status']                         =   true;
            $response['message']                        =   'Housekeeping Schedule Dashboard.';
            $response['accomodation_data']              =   $housekeepingSchedules;
            return response()->json($response);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function houseKeepingScheView($scheduleId)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            // Decode Schedule ID
            $scheduleId                                     =   base64_decode($scheduleId, true);

            // Check if decoding was successful
            if (!$scheduleId || !is_numeric($scheduleId)) {
                return response()->json(['success' => false, 'message' => 'Invalid Schedule ID'], 200);
            }

            $housekeepingScheView                           =   HousekeepingSchedules::join('building_models as bm', 'bm.id', '=', 'housekeeping_schedules.BuildingName')
                                                                    ->where('housekeeping_schedules.resort_id', $this->resort_id)
                                                                    ->select('housekeeping_schedules.*', 'bm.BuildingName as BName')
                                                                    ->where('housekeeping_schedules.id', $scheduleId)
                                                                    ->first();

            $hodData                                        =   Employee::join('resort_admins', 'resort_admins.id', "=", 'employees.Admin_Parent_id')
                                                                    ->where('employees.resort_id', $this->resort_id)
                                                                    ->where("employees.rank", 2)
                                                                    ->get(['employees.id', 'employees.Admin_Parent_id', 'employees.resort_id', 'employees.Emp_id', 'resort_admins.first_name', 'resort_admins.last_name']);
            // Prepare response data
            $scheduleRes                                    =   [
                'housekeeping_data'                         =>  $housekeepingScheView,
                'hod_data'                                  =>  $hodData
            ];

            // Check if the record exists
            if (!$housekeepingScheView) {
                $response['status']                         =   false;
                $response['message']                        =   'No housekeeping schedule found for the given ID.';
                return response()->json($response);
            }

            $response['status']                             =   true;
            $response['message']                            =   'Housekeeping Schedule retrive data successfully.';
            $response['accomodation_data']                  =   $scheduleRes;
            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function houseKeepingAssingHRtoHOD(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'hod_id'                                            =>    'required',
            'housekeeping_id'                                   =>    'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        try {

            DB::beginTransaction();
            $assignToHODExists                              =   ChildHouseKeepingSchedules::where("housekeeping_id", $request->housekeeping_id)->where('ApprovedBy', $request->hod_id)->exists();

            if ($assignToHODExists) {
                return response()->json([
                    'status'                                =>  false,
                    'message'                               =>  'This housekeeping schedule is already assigned to the specified HOD.',
                ], 200);
            }

            $housekeepingSchedules                      =   HousekeepingSchedules::where("id", $request->housekeeping_id)->update(['Assigned_To' =>  $request->hod_id, 'Status' => 'Open']);
            $assignToHOD                                =   ChildHouseKeepingSchedules::where("housekeeping_id", $request->housekeeping_id)->update(['ApprovedBy' => $this->user->GetEmployee->id, 'Status' => 'Open']);

            if (!$assignToHOD) {
                DB::rollBack();
                return response()->json([
                    'status'                            =>  false,
                    'message'                           =>  'Failed to update housekeeping schedule. Please try again.',
                ], 500);
            }

            $childHouseKeepingSchedules                 =   ChildHouseKeepingSchedules::create([
                'resort_id'                             =>  $this->resort_id,
                'housekeeping_id'                       =>  $request->housekeeping_id,
                'ApprovedBy'                            =>  0,
                'date'                                  =>  date('Y-m-d'),
                'status'                                =>  'Pending',
            ]);

            // Send In App Notification to each approver
            Common::sendMobileNotification(
                $this->resort_id,
                2,
                null,
                null,
                'Housekeeping Request',
                'Housekeeping request has been assigned to you by ' . $this->user->first_name . ' ' . $this->user->last_name,
                'Accommodation',
                [$request->hod_id],
                null
            );

            DB::commit();

            $response['status']                             =   true;
            $response['message']                            =   'Housekeeping schedule successfully assigned to HOD.';
            $response['hr_to_assing_data']                  =   $childHouseKeepingSchedules;
            return response()->json($response);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function hodHouseKeepingDashboard(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $currentDate                                    =   Carbon::now()->format('Y-m-d');
            $filterDate                                     =   $request->input('date', $currentDate); // Use provided date or default to today

            $housekeepingSchedules                          =   HousekeepingSchedules::join('building_models as bm', 'bm.id', '=', 'housekeeping_schedules.BuildingName')
                                                                    ->where('housekeeping_schedules.resort_id', $this->resort_id)
                                                                    ->where('housekeeping_schedules.Assigned_To', $this->user->GetEmployee->id)
                                                                    ->whereDate('housekeeping_schedules.date', $filterDate);

            $housekeepingSchedules                          =   $housekeepingSchedules->select('housekeeping_schedules.*', 'bm.BuildingName as BName')->get();

            $assignedHousekeeping                           =   HousekeepingSchedules::join('child_housekeeping_schedules as ch', 'ch.housekeeping_id', '=', 'housekeeping_schedules.id')
                                                                    ->join('employees as e', 'e.id', '=', 'ch.ApprovedBy')
                                                                    ->join('resort_admins', 'e.Admin_Parent_id', '=', 'resort_admins.id')
                                                                    ->where('ch.ApprovedBy', $this->user->GetEmployee->id)
                                                                    ->where('ch.status', '=', 'Assigned')
                                                                    ->whereDate('housekeeping_schedules.date', $filterDate)
                                                                    ->select('ch.id', 'housekeeping_schedules.RoomNo', 'housekeeping_schedules.special_instructions', 'housekeeping_schedules.status', 'housekeeping_schedules.clean_type', 'resort_admins.first_name', 'resort_admins.last_name')
                                                                    ->orderBy('housekeeping_schedules.created_at', 'desc')
                                                                    ->distinct()
                                                                    ->get();

            $data                                           =   [
                'housekeepingSchedules'                     =>  $housekeepingSchedules,
                'assignedHousekeeping'                      =>  $assignedHousekeeping
            ];

            if ($housekeepingSchedules->isEmpty() && $assignedHousekeeping->isEmpty()) {
                $response['status']                         =   true;
                $response['message']                        =   'No schedules found.';
                return response()->json($response);
            }

            $response['status']                             =   true;
            $response['message']                            =   'Housekeeping Schedule Dashboard.';
            $response['accomodation_data']                  =   $data;
            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function housekeepingEmployee(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $currentDate                                        =   Carbon::now()->format('Y-m-d');
            $dateToUse = $request->date ?? $currentDate;

            $user                                               =   Auth::guard('api')->user();
            $employee                                           =   $user->GetEmployee;
            $emp_id                                             =   $employee->id;

            $employeeHousekeeping                               =   HousekeepingSchedules::
                                                                        // join("resort_admins as t1", "t1.id", "=", "employees.Admin_Parent_id")
                                                                        join("employees as e", "housekeeping_schedules.assigned_to", "=", 'e.id')
                                                                        ->join("child_housekeeping_schedules as ch", 'ch.housekeeping_id', '=', 'housekeeping_schedules.id')
                                                                        ->where('housekeeping_schedules.date', $dateToUse)
                                                                        ->where(function ($query) use ($emp_id) {
                                                                            $query->where('housekeeping_schedules.Assigned_To', $emp_id)
                                                                                ->orWhere('ch.ApprovedBy', '=', $emp_id)
                                                                                ->orWhere('housekeeping_schedules.created_by', '=', $emp_id);
                                                                        })
                                                                        ->where('ch.ApprovedBy', '!=', '0')
                                                                        ->where('ch.date', $dateToUse)
                                                                        ->select('housekeeping_schedules.id', 'housekeeping_schedules.RoomNo', 'housekeeping_schedules.clean_type', 'housekeeping_schedules.special_instructions', 'housekeeping_schedules.status', 'housekeeping_schedules.time')
                                                                        //             ->distinct())
                                                                        ->distinct()
                                                                        ->get();

            if ($employeeHousekeeping->isempty()) {
                $response['status']                             =   true;
                $response['message']                            =   'No Housekeeping Employee data found on this request.';
                return response()->json($response);
            }

            $response['status']                             =   true;
            $response['message']                            =   'Housekeeping Employee.';
            $response['housekeeping_data']                  =   $employeeHousekeeping;
            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function availableStaffUnderHOD(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {

            // Ensure underEmp_id is an array and not empty
            if (empty($this->underEmp_id) || !is_array($this->underEmp_id)) {
                return response()->json(['success' => false, 'message' => 'No employees found under HOD'], 200);
            }

            $filterAssigned = $request->input('filter_assigned', false); // Default to false if not provided

            $underHODEmployee                                   =   Employee::join('resort_admins', 'resort_admins.id', "=", 'employees.Admin_Parent_id')
                                                                        ->leftJoin('housekeeping_schedules as hs', 'hs.Assigned_To', '=', 'employees.id')
                                                                        ->where('employees.resort_id', $this->resort_id)
                                                                        ->whereIn('employees.id', $this->underEmp_id);

            if ($filterAssigned) {
                $underHODEmployee->where('hs.status', 'Assigned');
            }

            $underHODEmployee = $underHODEmployee->select([
                'employees.id',
                'resort_admins.first_name',
                'resort_admins.last_name'
            ])
                ->groupBy('employees.id', 'resort_admins.first_name', 'resort_admins.last_name') // Ensuring unique employees
                ->get()
                ->map(function ($row) {
                    $row->profileImg = Common::getResortUserPicture($row->id);
                    return $row;
                });

            $response['status']                                 =   true;
            $response['message']                                =   'Housekeeping Schedule Dashboard.';
            $response['available_staff_list']                   =   $underHODEmployee;
            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function hodAssignDeadline(Request $request, $emp_id)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'time'                                              =>    'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        try {
            $emp_id                                     =   base64_decode($emp_id, true);

            if (!$emp_id || !is_numeric($emp_id)) {
                return response()->json(['success' => false, 'message' => 'Invalid Room ID'], 200);
            }

            $underHODEmployee = Employee::join('resort_admins', 'resort_admins.id', "=", 'employees.Admin_Parent_id')
                ->where('employees.resort_id', $this->resort_id)
                ->whereIn('employees.id', $this->underEmp_id)
                ->get(['employees.id', 'resort_admins.first_name', 'resort_admins.last_name'])
                ->map(function ($row) {
                    $row->profileImg = Common::getResortUserPicture($row->id);
                    return $row;
                });

            // Extract employee IDs from the list
            $allowedEmpIds = $underHODEmployee->pluck('id')->toArray();

            // Check if the given emp_id is in the list
            if (!in_array($emp_id, $allowedEmpIds)) {
                return response()->json(['success' => false, 'message' => 'Employee not under HOD'], 200);
            }

            $updated = HousekeepingSchedules::where('Assigned_to', $emp_id)
                ->update(['time' => $request->time]);

            $updatedRecord = HousekeepingSchedules::where('Assigned_to', $emp_id)->select('id', 'RoomNo', 'time')->get();

            if ($updated) {
                return response()->json([
                    $response['success']                                    =   true,
                    $response['message']                                    =   'Housekeeping available staff under HOD.',
                    $response['deadline_assigned_to']                       =   $updatedRecord,

                ]);
            } else {
                return response()->json([
                    $response['success']                                 =   false,
                    $response['message']   = 'No record found for the given employee.',
                ], 200);
            }

            $response['message']                                =   'Housekeeping available staff under HOD.';
            // $response['available_staff_list']                   =   $underHODEmployee;
            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function houseKeepingAssingHODtoEmp(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'emp_id'                                            =>    'required',
            'housekeeping_id'                                   =>    'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        try {

            DB::beginTransaction();
            $assignToHODExists                              =   ChildHouseKeepingSchedules::where("housekeeping_id", $request->housekeeping_id)->where('ApprovedBy', $request->emp_id)->exists();

            if ($assignToHODExists) {
                return response()->json([
                    'status'                                =>  false,
                    'message'                               =>  'This housekeeping schedule is already assigned to the specified HOD.',
                ], 200);
            }

            $housekeepingSchedules                      =   HousekeepingSchedules::where("id", $request->housekeeping_id)->update(['Assigned_To' =>  $request->emp_id, 'Status' => 'Assigned']);
            $assignToHOD                                =   ChildHouseKeepingSchedules::where("housekeeping_id", $request->housekeeping_id)->where('Status', '=', 'Pending')->update(['ApprovedBy' => $this->user->GetEmployee->id, 'Status' => 'Assigned']);

            if (!$assignToHOD) {
                DB::rollBack();
                return response()->json([
                    'status'                            =>  false,
                    'message'                           =>  'Failed to update housekeeping schedule. Please try again.',
                ], 500);
            }

            $childHouseKeepingSchedules                 =   ChildHouseKeepingSchedules::create([
                'resort_id'                             =>  $this->resort_id,
                'housekeeping_id'                       =>  $request->housekeeping_id,
                'ApprovedBy'                            =>  0,
                'date'                                  =>  date('Y-m-d'),
                'status'                                =>  'Pending',
            ]);

            DB::commit();

            $response['status']                             =   true;
            $response['message']                            =   'Housekeeping schedule successfully assigned to HOD.';
            $response['hr_to_assing_data']                  =   $childHouseKeepingSchedules;
            return response()->json($response);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function empAcceptHousekeeping($room_Id)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $roomId                                     =   base64_decode($room_Id, true);

            if (!$roomId || !is_numeric($roomId)) {
                return response()->json(['success' => false, 'message' => 'Invalid Room ID'], 200);
            }

            $user                                               =   Auth::guard('api')->user();
            $employee                                           =   $user->GetEmployee;
            $emp_id                                             =   $employee->id;

            $acceptHousekeeping = HousekeepingSchedules::join('child_housekeeping_schedules as ch', 'ch.housekeeping_id', '=', 'housekeeping_schedules.id')
                ->join('employees as e', 'e.id', '=', 'housekeeping_schedules.Assigned_To')
                ->where('housekeeping_schedules.resort_id', $this->resort_id)
                ->where('housekeeping_schedules.id', $roomId)
                ->where('ch.ApprovedBy', '!=', '0')
                ->where('housekeeping_schedules.status', 'Assigned')
                ->where(function ($query) use ($emp_id) {
                    $query->where('housekeeping_schedules.Assigned_To', $emp_id)
                        ->orWhere('ch.ApprovedBy', '=', $emp_id)
                        ->orWhere('housekeeping_schedules.created_by', '=', $emp_id);
                })
                ->select('ch.id', 'housekeeping_schedules.id as hs_id', 'ch.status')
                ->distinct()
                ->get();

            if (!$acceptHousekeeping->isEmpty()) {
                $housekeepingIds = $acceptHousekeeping->pluck('hs_id')->toArray();
                DB::table('housekeeping_schedules')
                    ->whereIn('id', $housekeepingIds)
                    ->update(['status' => 'In-Progress']);

                $updatedHousekeeping = DB::table('housekeeping_schedules as hs')
                    ->whereIn('id', $housekeepingIds)
                    ->select('hs.id', 'hs.RoomNo', 'hs.status')
                    ->get();

                $response['status']                             =   true;
                $response['message']                            =   'Housekeeping Schedule status changed successfully.';
                $response['accomodation_data']                  =   $updatedHousekeeping;
                return response()->json($response);
            }

            $response['status']                             =   true;
            $response['message']                            =   'Housekeeping Schedule status is not Assigned.';
            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function empAddTaskHousekeeping(Request $request, $room_Id)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $roomId                                     =   base64_decode($room_Id, true);

            if (!$roomId || !is_numeric($roomId)) {
                return response()->json(['success' => false, 'message' => 'Invalid Room ID'], 200);
            }

            $validator = Validator::make(
                $request->all(),
                [
                    'document_path' => 'file|mimes:jpg,jpeg,png|max:2048',
                    'status' => 'required|in:Complete,On-Hold',
                ],
                [
                    'document_path.mimes' => 'The image must be a type of:jpg,jpeg,png',
                ]
            );

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $user                                               =   Auth::guard('api')->user();
            $employee                                           =   $user->GetEmployee;
            $emp_id                                             =   $employee->id;

            $updateHousekeeping                                 =   HousekeepingSchedules::join('child_housekeeping_schedules as ch', 'ch.housekeeping_id', '=', 'housekeeping_schedules.id')
                                                                        ->join('employees as e', 'e.id', '=', 'housekeeping_schedules.Assigned_To')
                                                                        ->where('housekeeping_schedules.resort_id', $this->resort_id)
                                                                        ->where('housekeeping_schedules.id', $roomId)
                                                                        ->where('ch.ApprovedBy', '!=', '0')
                                                                        ->where('housekeeping_schedules.status', 'In-Progress')
                                                                        ->where(function ($query) use ($emp_id) {
                                                                            $query->where('housekeeping_schedules.Assigned_To', $emp_id)
                                                                                ->orWhere('ch.ApprovedBy', '=', $emp_id)
                                                                                ->orWhere('housekeeping_schedules.created_by', '=', $emp_id);
                                                                        })
                                                                        ->select('housekeeping_schedules.id as housekeeping_id', 'housekeeping_schedules.Assigned_To as emp_id')
                                                                        ->distinct()
                                                                        ->first();

            if (!$updateHousekeeping) {
                return response()->json(['status' => 'false', 'message' => 'Unauthorized to update status for this room or no data found for given room'], 200);
            }

            $housekeeping_id = $updateHousekeeping->housekeeping_id;
            $emp_id = $updateHousekeeping->emp_id;

            $housekeeping = HousekeepingSchedules::find($roomId);
            $housekeeping->status = $request->status;
            $housekeeping->save();

            // Fetch Employee ID for folder naming
            $getEmp = Employee::find($emp_id);
            $Emp_id = $getEmp->Emp_id;

            // $housekeeping_doc_path = config('settings.Housekeeping_Images');
            // $dynamic_path = $housekeeping_doc_path . '/' . $Emp_id; // Employee-specific folder

            // Create directory if it doesn’t exist
            // $absolute_path = public_path($dynamic_path);
            // if (!File::exists($absolute_path)) {
            //     File::makeDirectory($absolute_path, 0755, true);
            // }

            $imagePaths = [];
            if ($request->hasFile('document_path')) {
                foreach ($request->file('document_path') as $uploadedFile) {

                   $SubFolder="HousekeepingImages";
                    $status =   Common::AWSEmployeeFileUpload($this->resort_id,$uploadedFile, $Emp_id,$SubFolder,true);

                    if ($status['status'] == false) {
                        break;
                    } else {
                        if($status['status'] == true && isset($status['Chil_file_id']) && !empty($status['Chil_file_id'])) {
                            $filename = $uploadedFile->getClientOriginalName();
                            $imagePaths[] = ['Filename' => $filename, 'Child_id' => $status['Chil_file_id']];
                        }
                    }
                    
                    $image = new HousekeepingSchedulesImg();
                    $image->resort_id = $this->resort_id;
                    $image->housekeeping_id = $housekeeping_id;
                    $image->emp_id = $emp_id;
                    $image->document_path =  $imagePaths? json_encode($imagePaths) : null;
                    $image->save();

                    $imagePaths[] = '';
                }
            }
            $response['status']                             =   true;
            $response['message']                            =   'Housekeeping status change successfully.';
            $response['updated_emp']                  =   ['id' => $housekeeping->id, 'status' => $housekeeping->status, 'img_path' => $imagePaths];

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function handleMaintananceAction(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'request_id'                                    =>  'required',
            'action'                                        =>  'required|in:Approved,Rejected,On-Hold',
            'reason'                                        =>  'required_if:action,On-Hold',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        try {

            $engineeringDepHOD                              =   Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                                                                    ->where("t1.resort_id", $this->resort_id)
                                                                    ->where("employees.rank", 11)
                                                                    ->select('employees.id','t1.first_name','t1.last_name')
                                                                    ->first();

            $requestId                                      =   $request->input('request_id');
            $action                                         =   $request->input('action'); // Approve or Reject
            $reason                                         =   $request->input('reason', null); // Optional comments
            $user                                           =   Auth::guard('api')->user();
            $employee                                       =   $user->GetEmployee;
            $currentApproverId                              =   $employee->id;
            $maintanance                                    =   MaintanaceRequest::find($requestId);

            if (!$maintanance) {
                return response()->json([
                    'status'                                =>  'error',
                    'message'                               =>  'Maintanance request not found.',
                ], 200);
            }

            if ($maintanance->Status !== 'pending') {
                return response()->json([
                    'status'                                =>  false,
                    'message'                               =>  'Action can only be performed on Pending requests.',
                ], 200);
            }

            if ($action == 'Approved') {
                $maintanance->status                        =   "Open";
                $maintanance->Assigned_To                   =   $engineeringDepHOD->id;
            } elseif ($action === 'Rejected') {
                $maintanance->status                        =   "Rejected";
            } elseif ($action === 'On-Hold') {
                $maintanance->status                        =   "On-Hold";
                $maintanance->ReasonOnHold                  =   $reason;
            }

            $maintanance->save();
            if ($action == 'Approved') {
                ChildMaintananceRequest::where('maintanance_request_id', $maintanance->id) ->where('resort_id', $this->resort_id)
                    ->update([
                        'Status'                                =>  'Open',
                        'ApprovedBy'                            =>  $action === 'Approved' ? $currentApproverId : DB::raw('ApprovedBy'),
                        'rank'                                  =>  $employee->rank,
                    ]);

                ChildMaintananceRequest::create([
                    'maintanance_request_id'                =>  $requestId,
                    'resort_id'                             =>  $this->resort_id,
                    'ApprovedBy'                            =>  0,
                    'Status'                                =>  'pending',
                    'date'                                  =>  date('Y-m-d'),
                ]);
                
                $hrEmployee                             =   Common::FindResortHR($this->user);

                // Send Mobile Notification to Engineering Department HOD
                Common::sendMobileNotification(
                    $this->resort_id,
                    2,
                    null,
                    null,
                    'Maintenance Request Assigned',
                    "The Maintenance Request for #{$hrEmployee->Emp_id} has been Approved and assigned to you.{$maintanance->descriptionIssues}",
                    'Maintenance',
                    [$engineeringDepHOD->id],
                    null,
                );

                return response()->json([
                    'status' => true,
                    'message' => "The maintenance request has been successfully {$action} and assigned to the Head of the Engineering Department.",
                ], 200);
            }

            return response()->json([
                'status' => true,
                'message' => "The maintenance request has been successfully {$action}",
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function engDepartmentHODMaintenanceReqDashboard()
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $employee_id                                    =   $this->user->GetEmployee->id;
            $employee                                       =   $this->user->GetEmployee;
            $baseQuery                                      =   MaintanaceRequest::join("employees as t3", "t3.id", "=", "maintanace_requests.Raised_By")
                                                                    ->leftJoin('child_maintanance_requests as cmr', function($join) {
                                                                        $join->on('cmr.maintanance_request_id', '=', 'maintanace_requests.id')
                                                                             ->whereRaw('cmr.id = (SELECT MAX(id) FROM child_maintanance_requests WHERE maintanance_request_id = maintanace_requests.id AND Status != "Pending")');
                                                                        
                                                                    })
                                                                    ->leftJoin("employees as approver", "approver.id", "=", "cmr.ApprovedBy") 
                                                                    ->join("resort_admins as t1", "t1.id", "=", "t3.Admin_Parent_id")
                                                                    ->join("resort_departments as t4", "t4.id", "=", "t3.Dept_id")
                                                                    ->where('maintanace_requests.resort_id', $this->resort_id);

            $EscalationDay                                  =   EscalationDay::where('resort_id', $this->resort_id)->first();

            $inventoryItems                                 =   InventoryModule::where('resort_id', $this->resort_id)->pluck('ItemName', 'id');

            $MaintanaceRequestData                          =   (clone $baseQuery)
                                                                    ->where('maintanace_requests.Assigned_To', $employee_id)
                                                                    ->whereNotIn('maintanace_requests.Status', ['Closed','On-Hold'])
                                                                    ->orderBy('maintanace_requests.created_at', 'desc')
                                                                    ->take(2)
                                                                    ->get(['maintanace_requests.*','t1.id as Parentid', 't1.first_name', 't1.last_name','cmr.ApprovedBy','approver.rank as ApprovedByRank'])
                                                                    ->map(function ($row) use ($EscalationDay, $inventoryItems) {
                                                                        return $this->formatMaintenanceRow($row, $EscalationDay, $inventoryItems);
                                                                    });

            $MaintanaceRequest                              = $MaintanaceRequestData->map(function($MaintanaceRequests) {
                $role                                       = ucfirst(strtolower($MaintanaceRequests->ApprovedByRank ?? ''));
                $rank                                       = config('settings.Position_Rank');
                $role                                       = $rank[$role] ?? '';
                $MaintanaceRequests->status_text            = 'Approved by '.$role ;
                return $MaintanaceRequests;
            });

            $MaintanaceAssignTask                           =   (clone $baseQuery)
                                                                    ->where('cmr.ApprovedBy',$employee_id)
                                                                    ->where('maintanace_requests.Status', 'Assigned')
                                                                    ->orderBy('maintanace_requests.created_at', 'desc')
                                                                    ->take(2)
                                                                    ->groupBy('maintanace_requests.id')
                                                                    ->get(['maintanace_requests.*','t1.id as Parentid', 't1.first_name', 't1.last_name'])
                                                                    ->map(function ($row) use ($EscalationDay, $inventoryItems) {
                                                                        return $this->formatMaintenanceRow($row, $EscalationDay, $inventoryItems);
                                                                    });

            $completeMaintananceReqQuery                    =   MaintanaceRequest::join("employees as t3", "t3.id", "=", "maintanace_requests.Raised_By")
                                                                    ->join("resort_admins as t1", "t1.id", "=", "t3.Admin_Parent_id")
                                                                    ->join("child_maintanance_requests as cmr", function ($join) use ($employee_id) {
                                                                        $join->on("cmr.maintanance_request_id", "=", "maintanace_requests.id")
                                                                            ->where("cmr.ApprovedBy", "=", $employee_id)
                                                                            ->where("cmr.Status", "=", "Assinged"); // Note: Check spelling "Assigned" vs "Assinged"
                                                                    })
                                                                   
                                                                    ->join("child_approved_maintanace_requests as camr", function ($join) use ($employee_id) {
                                                                        $join->on("camr.maintanance_request_id", "=", "maintanace_requests.id")
                                                                            ->where("camr.ApprovedBy", "=", $employee_id)
                                                                            ->where("camr.Status", "=", "Assinged"); // Note: Check spelling "Assigned" vs "Assinged"
                                                                    })

                                                                    ->where("maintanace_requests.resort_id", $this->resort_id)
                                                                    ->groupBy('maintanace_requests.id')
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
            $data                                           =   [
                'maintanace_request'                        =>  $MaintanaceRequest,
                'maintanace_assign'                         =>  $MaintanaceAssignTask,
                'maintanace_comp_task'                      =>  $completeMaintananceReqQuery,
            ];

            $response['status']                             =   true;
            $response['message']                            =   'Maintances Request retrieved successfully';
            $response['maintanace_request_data']            =   $data;
            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function engDepartmentHODMaintenanceReqList()
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $employee_id                                    =   $this->user->GetEmployee->id;
            $baseQuery                                      =   MaintanaceRequest::join("employees as t3", "t3.id", "=", "maintanace_requests.Raised_By")
                                                                    ->leftJoin('child_maintanance_requests as cmr', function($join) {
                                                                        $join->on('cmr.maintanance_request_id', '=', 'maintanace_requests.id')
                                                                             ->whereRaw('cmr.id = (SELECT MAX(id) FROM child_maintanance_requests WHERE maintanance_request_id = maintanace_requests.id AND Status != "Pending")');
                                                                        
                                                                    })
                                                                    ->leftJoin("employees as approver", "approver.id", "=", "cmr.ApprovedBy") 
                                                                    ->join("resort_admins as t1", "t1.id", "=", "t3.Admin_Parent_id")
                                                                    ->join("resort_departments as t4", "t4.id", "=", "t3.Dept_id")
                                                                    ->where('maintanace_requests.resort_id', $this->resort_id)
                                                                    ->where('maintanace_requests.Assigned_To', $employee_id);


            $EscalationDay                                  =   EscalationDay::where('resort_id', $this->resort_id)->first();

            $inventoryItems                                 =   InventoryModule::where('resort_id', $this->resort_id)->pluck('ItemName', 'id');

            $MaintanaceRequestData                           =   (clone $baseQuery)
                                                                    ->whereNotIn('maintanace_requests.Status', ['Closed','On-Hold'])
                                                                    ->orderBy('maintanace_requests.created_at', 'desc')
                                                                    ->get(['maintanace_requests.*','t1.id as Parentid', 't1.first_name', 't1.last_name','cmr.ApprovedBy','approver.rank as ApprovedByRank'])
                                                                    ->map(function ($row) use ($EscalationDay, $inventoryItems) {
                                                                        return $this->formatMaintenanceRow($row, $EscalationDay, $inventoryItems);
                                                                    });

            $MaintanaceRequest                              = $MaintanaceRequestData->map(function($MaintanaceRequests) {
                $role                                       = ucfirst(strtolower($MaintanaceRequests->ApprovedByRank ?? ''));
                $rank                                       = config('settings.Position_Rank');
                $role                                       = $rank[$role] ?? '';
                $MaintanaceRequests->status_text            = 'Approved by '.$role ;
                return $MaintanaceRequests;
            });
            
            $response['status']                             =   true;
            $response['message']                            =   'Maintances Request retrieved successfully';
            $response['maintanace_request_data']            =   $MaintanaceRequest;
            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function getEmployeesUnderEngHOD()
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $employee_id                                    =   $this->user->GetEmployee->id;

            $underEDHODEmployee                             =   Employee::join('resort_admins', 'resort_admins.id', "=", 'employees.Admin_Parent_id')
                                                                        ->where('employees.resort_id', $this->resort_id)
                                                                        ->where('employees.reporting_to', $employee_id)
                                                                        ->select([
                                                                            'employees.id',
                                                                            'resort_admins.first_name',
                                                                            'resort_admins.last_name'
                                                                        ])->get();

            if ($underEDHODEmployee->isEmpty()) {
                return response()->json([
                'status'                                    => false,
                'message'                                   => 'No employees found under this HOD',
                'employees'                                 => []
                ], 200);
            }
            
            $response['status']                             =   true;
            $response['message']                            =   'Employees retrieved successfully';
            $response['employee_list']                      =   $underEDHODEmployee;
            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function engHODAssignEmployees(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'request_id'                                    =>  'required',
            'assing_employee_id'                            =>  'required',
            'comments'                                      =>  'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        try {
            DB::beginTransaction();
            $employee_id                                    =   $this->user->GetEmployee->id;
            $employee                                       =   $this->user->GetEmployee;
            $requestId                                      =   $request->input('request_id');
            $assingEmployeeId                               =   $request->input('assing_employee_id');
            $maintanance                                    =   MaintanaceRequest::find($requestId);

            if (!$maintanance) {
                return response()->json(['success' => false, 'message' => 'Maintenance request not found'], 200);
            }
    
            if ($maintanance->Status === "Assigned") {
                return response()->json([
                    'success' => false,
                    'message' => 'This request is already assigned'
                ], 200);
            }

            $maintanance->Status                            =   "Assigned";
            $maintanance->Assigned_To                       =   $assingEmployeeId;
            $maintanance->save();
            
            ChildMaintananceRequest::where('maintanance_request_id', $maintanance->id)->where('Status','pending')
                ->update([
                    'Status'                                =>  "Assinged",
                    'ApprovedBy'                            =>  $employee_id,
                    'rank'                                  =>  $employee->rank,
                ]);

            ChildMaintananceRequest::create([
                'maintanance_request_id'                    =>  $requestId,
                'resort_id'                                 =>  $this->resort_id,
                'ApprovedBy'                                =>  0,
                'Status'                                    =>  'pending',
                'date'                                      =>  date('Y-m-d'),
                ]);
            

            Common::sendMobileNotification(
                $this->resort_id,
                2,
                null,
                null,
                'Maintenance Request Assigned',
                "The Maintenance Request for #{$this->user->GetEmployee->Emp_id} has been Approved and assigned to you.{$maintanance->descriptionIssues}",
                'Maintenance',
                [$assingEmployeeId],
                null,
            );

            DB::commit(); // Commit Transaction

            $response['status']                             =   true;
            $response['message']                            =   'Task assigned successfully';
            $response['maintanance_request']                =   $maintanance;
            return response()->json($response);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback if any error occurs
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function engDepartmentHODMaintenanceReqAssignList()
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $employee_id                                    =   $this->user->GetEmployee->id;
            $baseQuery                                      =   MaintanaceRequest::join("employees as t3", "t3.id", "=", "maintanace_requests.Raised_By")
                                                                    ->leftJoin('child_maintanance_requests as cmr', function($join) {
                                                                        $join->on('cmr.maintanance_request_id', '=', 'maintanace_requests.id')
                                                                             ->whereRaw('cmr.id = (SELECT MAX(id) FROM child_maintanance_requests WHERE maintanance_request_id = maintanace_requests.id AND Status != "Pending")');
                                                                        
                                                                    })
                                                                    ->leftJoin("employees as approver", "approver.id", "=", "cmr.ApprovedBy") 
                                                                    ->join("resort_admins as t1", "t1.id", "=", "t3.Admin_Parent_id")
                                                                    ->join("resort_departments as t4", "t4.id", "=", "t3.Dept_id")
                                                                    ->where('maintanace_requests.resort_id', $this->resort_id)
                                                                    ->where('maintanace_requests.Status', 'Assigned');
             // Ensure the employee is assigned to the request (if cmr is present)
            $baseQuery                                      =   $baseQuery->where(function ($query) use ($employee_id) {
                                                                $query->where('cmr.ApprovedBy', $employee_id)
                                                                    ->orWhereNull('cmr.ApprovedBy'); // Ensure requests without cmr records are included
                                                                });

            $EscalationDay                                  =   EscalationDay::where('resort_id', $this->resort_id)->first();

            $inventoryItems                                 =   InventoryModule::where('resort_id', $this->resort_id)->pluck('ItemName', 'id');

            $assignRequestData                              =   (clone $baseQuery)
                                                                    ->whereNotIn('maintanace_requests.Status', ['Closed','On-Hold'])
                                                                    ->orderBy('maintanace_requests.created_at', 'desc')
                                                                    ->get(['maintanace_requests.*','t1.id as Parentid', 't1.first_name', 't1.last_name','cmr.ApprovedBy','approver.rank as ApprovedByRank'])
                                                                    ->map(function ($row) use ($EscalationDay, $inventoryItems) {
                                                                        return $this->formatMaintenanceRow($row, $EscalationDay, $inventoryItems);
                                                                    });
            
            $response['status']                             =   true;
            $response['message']                            =   'Assigned Maintenance Requests retrieved successfully';
            $response['assign_maintanace_request_list']            =   $assignRequestData;
            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function engDepartmentStaffMaintenanceReqDashboard()
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try
        {
            $employee_id                                    =   $this->user->GetEmployee->id;
            $baseQuery                                      =   MaintanaceRequest::join("employees as t3", "t3.id", "=", "maintanace_requests.Raised_By")
                                                                    ->leftJoin('child_maintanance_requests as cmr', function($join) {
                                                                        $join->on('cmr.maintanance_request_id', '=', 'maintanace_requests.id')
                                                                             ->whereRaw('cmr.id = (SELECT MAX(id) FROM child_maintanance_requests WHERE maintanance_request_id = maintanace_requests.id AND Status != "Pending")');
                                                                        
                                                                    })
                                                                    ->leftJoin("employees as approver", "approver.id", "=", "cmr.ApprovedBy") 
                                                                    ->join("resort_admins as t1", "t1.id", "=", "t3.Admin_Parent_id")
                                                                    ->join("resort_departments as t4", "t4.id", "=", "t3.Dept_id")
                                                                    ->where('maintanace_requests.resort_id', $this->resort_id);

            $EscalationDay                                  =   EscalationDay::where('resort_id', $this->resort_id)->first();

            $inventoryItems                                 =   InventoryModule::where('resort_id', $this->resort_id)->pluck('ItemName', 'id');

            $MaintanaceRequestData                          =   (clone $baseQuery)
                                                                    ->where('maintanace_requests.Assigned_To', $employee_id)
                                                                    // ->where('maintanace_requests.Status','Assigned')
                                                                    ->orderBy('maintanace_requests.created_at', 'desc')
                                                                    ->get(['maintanace_requests.*','t1.id as Parentid', 't1.first_name', 't1.last_name','cmr.ApprovedBy','approver.rank as ApprovedByRank'])
                                                                    ->map(function ($row) use ($EscalationDay, $inventoryItems) {
                                                                        return $this->formatMaintenanceRow($row, $EscalationDay, $inventoryItems);
                                                                    });

            $response['status']                             =   true;
            $response['message']                            =   'Maintances Request retrieved successfully';
            $response['maintanace_request_list']            =   $MaintanaceRequestData;
            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function engDepartmentStaffMaintenanceReqAccept(Request $request)
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
            // $maintanance                                    =   MaintanaceRequest::find($requestId);
            
            $maintanance                                    =   MaintanaceRequest::where('id', $requestId)
                                                                    ->where('Status', 'Assigned')
                                                                    ->where('resort_id', $this->resort_id)
                                                                    ->first();
            
            if (!$maintanance) {
                return response()->json(['success' => false, 'message' => 'Maintenance request not found'], 200);
            }
    
            // Check if Maintenance Request is already in progress
            $existingChildRequest                           =   ChildMaintananceRequest::where('maintanance_request_id', $maintanance->id)
                                                                    ->where('Status', 'In-Progress')
                                                                    ->where('resort_id', $this->resort_id)
                                                                    ->first();

            if ($existingChildRequest) {
                return response()->json(['success' => false, 'message' => 'This maintenance request is already in progress'], 200);
            }


            $updatedRows                                    =   ChildMaintananceRequest::where('maintanance_request_id', $maintanance->id)
                                                                    ->where('Status', 'pending')
                                                                    ->where('resort_id', $this->resort_id)
                                                                    ->update([
                                                                        'Status'    => "In-Progress",
                                                                        'ApprovedBy'=> $employee_id,
                                                                        'rank'      => $employee->rank,
                                                                    ]);
            $maintananceUpdateStatus                        =   MaintanaceRequest::where('id', $maintanance->id)
                                                                    ->where('Status', 'Assigned')
                                                                    ->where('resort_id', $this->resort_id)
                                                                    ->update([
                                                                        'Status'    => "In-Progress",
                                                                    ]);
            
            // If no records were updated, return an error
            if (!$updatedRows) {
                return response()->json(['success' => false, 'message' => 'No pending maintenance request found to update'], 200);
            }

            ChildMaintananceRequest::create([
                'maintanance_request_id'                =>  $requestId,
                'resort_id'                             =>  $this->resort_id,
                'ApprovedBy'                            =>  $employee_id,
                'Status'                                =>  'pending',
                'date'                                  =>  date('Y-m-d'),
            ]);

            $maintananceReq                                   =   MaintanaceRequest::find($requestId);
            DB::commit(); // Commit Transaction

            $response['status']                             =   true;
            $response['message']                            =   'Approved the task successfully';
            $response['maintanance_request']                =   $maintananceReq;
            return response()->json($response);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback if any error occurs
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }
    
    public function engDepartmentStaffMaintenanceReqComplete(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'request_id'                                    =>  'required',
            'status'                                        =>  'required',
            'Image'                                         =>  'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        try {

            DB::beginTransaction();
            $employee_id                                    =   $this->user->GetEmployee->id;
            $employee                                       =   $this->user->GetEmployee;
            $requestId                                      =   $request->input('request_id');
            $maintanance                                    =   MaintanaceRequest::find($requestId);

            if (!$maintanance) {
                return response()->json(['success' => false, 'message' => 'Maintenance request not found'], 200);
            }

            if ($request->status == 'Completed') {

                if ($request->hasFile('Image')) {
                    $path                                   =   config('settings.MaintanceRequest') . '/' . Auth::guard('api')->user()->resort->resort_id;
                    
                    $imageFile                                  =   $request->file('Image');
                    $imageName                                  =   time() . '_' . $imageFile->getClientOriginalName();
                    $imageFile->move($path, $imageName);
                    $maintanance->Completed_Image               =   $imageName;
                }
            
                $maintanance->save();

                // Check if Maintenance Request is already in progress
                $existingChildRequest                           =   ChildMaintananceRequest::where('maintanance_request_id', $maintanance->id)
                                                                    ->where('Status', 'Resolvedawaiting')
                                                                    ->where('resort_id', $this->resort_id)
                                                                    ->first();
                if ($existingChildRequest) {
                    return response()->json(['success' => false, 'message' => 'This maintenance request is already in progress'], 200);
                }
                $updatedRows                                    =   ChildMaintananceRequest::where('maintanance_request_id', $maintanance->id)
                                                                        ->where('ApprovedBy',$employee_id)
                                                                        ->where('Status', 'pending')
                                                                        ->update([
                                                                            'Status'    => "Resolvedawaiting",
                                                                            'rank'      => $employee->rank,
                                                                            'comments'  => $request->input('comments', null),
                                                                        ]);
                // If no records were updated, return an error
                if (!$updatedRows) {
                    return response()->json(['success' => false, 'message' => 'No pending maintenance request found to update'], 200);
                }

                $findEDHODChildRequest                           =   ChildMaintananceRequest::where('maintanance_request_id', $maintanance->id)
                                                                        ->where('rank', '11')
                                                                        ->where('resort_id', $this->resort_id)
                                                                        ->first();
                if ($findEDHODChildRequest) {
                    ChildApprovedMaintanaceRequests::create([
                        'resort_id'                     => $this->resort_id,
                        'child_maintanance_request_id'  => $findEDHODChildRequest->id,
                        'maintanance_request_id'        => $maintanance->id,
                        'ApprovedBy'                    => $findEDHODChildRequest->ApprovedBy,
                        'Status'                        => 'Assinged',
                        'date'                          => date('Y-m-d'),
                        'rank'                          => $findEDHODChildRequest->rank,
                    ]);
                    Common::sendMobileNotification(
                        $this->resort_id,
                        2,
                        null,
                        null,
                        'Maintenance Request Completed',
                        "The Maintenance Request for #{$this->user->GetEmployee->Emp_id} has been Completed and assigned to you.{$request->input('comments', null)}",
                        'Maintenance',
                        [$findEDHODChildRequest->ApprovedBy],
                       null,
                        );
                }
                
                
            }
            DB::commit(); // Commit Transaction

            $response['status']                             =   true;
            $response['message']                            =   'Comelete the task successfully and assigned to the Head of the Engineering Department for the approval.';
            return response()->json($response);

        } catch (\Exception $e) {
            DB::rollBack(); // Rollback if any error occurs
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function engHODCompleteSendToHR(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'request_id'                                    =>  'required',
            'child_appr_maint_req_id'                       =>  'required', 
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        try {

            DB::beginTransaction();
            $employee_id                                    =   $this->user->GetEmployee->id;
            $requestId                                      =   $request->input('request_id');
            $childApprMaintReqId                            =   $request->input('child_appr_maint_req_id');
            $maintanance                                    =   MaintanaceRequest::find($requestId);

            if (!$maintanance) {
                return response()->json(['success' => false, 'message' => 'Maintenance request not found'], 200);
            }

                // Check if Maintenance Request is already in Approved
                $existingChildApprRequest                           =   ChildApprovedMaintanaceRequests::where('id', $childApprMaintReqId)
                                                                            ->where('maintanance_request_id', $requestId)
                                                                            ->where('Status', 'Assinged')
                                                                            ->where('resort_id', $this->resort_id)
                                                                            ->first();
                if (!$existingChildApprRequest) {
                    return response()->json(['success' => false, 'message' => 'This maintenance request is already Approved'], 200);
                }

                                            
            
                $updatedRows                                    =   ChildApprovedMaintanaceRequests::where('id', $childApprMaintReqId)
                                                                        ->where('maintanance_request_id', $maintanance->id)
                                                                        ->where('Status', 'Assinged')
                                                                        ->update([
                                                                            'Status'     => "Approved",
                                                                        ]);
                $maintanance->Status                            = 'Resolvedawaiting';                                   
                $maintanance->save();

                $findHRChildRequest                           =   ChildMaintananceRequest::where('maintanance_request_id', $maintanance->id)
                                                                        ->where('rank', '3')
                                                                        ->where('resort_id', $this->resort_id)
                                                                        ->first();
                if ($findHRChildRequest) {
                    ChildApprovedMaintanaceRequests::create([
                        'resort_id'                     => $this->resort_id,
                        'child_maintanance_request_id'  => $findHRChildRequest->id,
                        'maintanance_request_id'        => $maintanance->id,
                        'ApprovedBy'                    => $findHRChildRequest->ApprovedBy,
                        'Status'                        => 'Assinged',
                        'date'                          => date('Y-m-d'),
                        'rank'                          => $findHRChildRequest->rank,
                    ]);

                    Common::sendMobileNotification(
                        $this->resort_id,
                        2,
                        null,
                        null,
                        'Maintenance Request Completed',
                        "The Maintenance Request for #{$this->user->GetEmployee->Emp_id} has been Completed and assigned to you.{$maintanance->descriptionIssues}",
                        'Maintenance',
                        [$findHRChildRequest->ApprovedBy],
                        null,
                    );
                }

                // If no records were updated, return an error
                if (!$updatedRows) {
                    return response()->json(['success' => false, 'message' => 'No pending maintenance request found to update'], 200);
                }
            
            DB::commit(); // Commit Transaction

            $response['status']                             =   true;
            $response['message']                            =   'Comelete the task successfully and assigned to the HR for the approval.';
            return response()->json($response);

            
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback if any error occurs
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function completeTaskHRSendToStaffAccEmp(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'request_id'                                    =>  'required',
            'child_appr_maint_req_id'                       =>  'required', 
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        try {

            DB::beginTransaction();
            $employee_id                                    =   $this->user->GetEmployee->id;
            $requestId                                      =   $request->input('request_id');
            $childApprMaintReqId                            =   $request->input('child_appr_maint_req_id');
            $maintanance                                    =   MaintanaceRequest::find($requestId);
           
            if (!$maintanance) {
                return response()->json(['success' => false, 'message' => 'Maintenance request not found'], 200);
            }

                // Check if Maintenance Request is already in Approved
                $existingChildApprRequest                   =   ChildApprovedMaintanaceRequests::where('id', $childApprMaintReqId)
                                                                    ->where('maintanance_request_id', $requestId)
                                                                    ->where('Status', 'Assinged')
                                                                    ->where('resort_id', $this->resort_id)
                                                                    ->first();
                if (!$existingChildApprRequest) {
                    return response()->json(['success' => false, 'message' => 'This maintenance request is already Approved'], 200);
                }

                $updatedRows                                =   ChildApprovedMaintanaceRequests::where('id', $childApprMaintReqId)
                                                                    ->where('maintanance_request_id', $maintanance->id)
                                                                    ->where('Status', 'Assinged')
                                                                    ->update([
                                                                        'Status'     => "Approved",
                                                                    ]);


                $employee                                   =   Employee::find($maintanance->Raised_By);
                if (!$employee) {
                    return response()->json(['success' => false, 'message' => 'Employee not found'], 200);
                }   
                // Send Mobile Notification to Staff/Acc/Emp
                Common::sendMobileNotification(
                    $this->resort_id,
                    4,
                    null,
                    null,
                    'Maintenance Request Completed',
                    "The Maintenance Request for #{$employee->Emp_id} has been Completed.".$maintanance->descriptionIssues,
                    'Maintenance',
                    [$maintanance->Raised_By],
                    $maintanance->id,
                );

                // If no records were updated, return an error
                if (!$updatedRows) {
                    return response()->json(['success' => false, 'message' => 'No pending maintenance request found to update'], 200);
                }
            
            DB::commit(); // Commit Transaction

            $response['status']                             =   true;
            $response['message']                            =   'Successfully request sent to Employee';
            return response()->json($response);

            
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback if any error occurs
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }
}
