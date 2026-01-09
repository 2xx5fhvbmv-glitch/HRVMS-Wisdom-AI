<?php

namespace App\Http\Controllers\Resorts\Accommodation;
use DB;
use Auth;
use Validator;
use Carbon\Carbon;
use App\Helpers\Common;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\BuildingModel;
use App\Models\ResortDepartment;
use App\Models\InventoryModule;
use App\Models\AssingAccommodation;
use App\Models\AccommodationType;
use App\Models\InventoryCategoryModel;
use App\Http\Controllers\Controller;
use App\Models\AvailableAccommodationModel;
use App\Models\AvailableAccommodationInvItem;
use App\Models\OccupancyLevelsHitACriticalThreshold;
use App\Models\TransferAccommodation;

class AccommodationMasterController extends Controller
{
    protected $resort;
    protected $underEmp_id=[];
    public function __construct()
    {
        $this->resort = $resortId = auth()->guard('resort-admin')->user();
        if($this->resort->is_master_admin == 0){
            $reporting_to = $this->globalUser->GetEmployee->id;
            $this->underEmp_id = Common::getSubordinates($reporting_to);
        }
    }

    public function index()
    {

        $page_title ="Accommodation Master";


        $BuildingModel = BuildingModel::join('bulidng_and_floor_and_rooms as t1', 't1.building_id', '=', 'building_models.id')
        ->where("building_models.resort_id", $this->resort->resort_id)
        ->groupBy('building_models.id','building_models.id')
        ->get(['building_models.*',DB::RAW('Count(t1.building_id) as TotalRoom')])
        ->map(function ($building) {



        $accommodations = AvailableAccommodationModel::join('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
                                ->join('employees as t2', 't2.id', '=', 't1.emp_id')
                                ->join('resort_admins as t3', 't3.id', '=', 't2.Admin_Parent_id')
                                ->where('available_accommodation_models.resort_id', $this->resort->resort_id)
                                ->where("BuildingName", $building->id)
                                ->with('availableAccommodationInvItem.inventoryModule', 'accommodationType')
                                ->get(['t1.emp_id','t3.id as Parentid', 't3.first_name', 't3.last_name', 'available_accommodation_models.*'])
                                ->map(function ($accommodation)
                                {
                                    $assignedAccommodations = AssingAccommodation::where("available_a_id", $accommodation->id)->get();
                                    $accommodation->EmployeeName = ucfirst($accommodation->first_name . ' ' . $accommodation->last_name);
                                    $accommodation->profileImg = Common::getResortUserPicture($accommodation->Parentid);
                                    $accommodation->AssingAccommodationCount = $assignedAccommodations->where("emp_id", 0)->count();
                                    $accommodation->bedAvailable = ($accommodation->Capacity == $accommodation->AssingAccommodationCount) ? $accommodation->Capacity : $accommodation->AssingAccommodationCount;
                                    return $accommodation;
                                });
                                $a = AvailableAccommodationModel::join('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
                                                                ->where('available_accommodation_models.resort_id', $this->resort->resort_id)
                                                                ->where("BuildingName", $building->id)
                                                                ->where("t1.emp_id", 0)
                                                                ->groupBy('t1.available_a_id')
                                                                ->first(['available_accommodation_models.id','available_accommodation_models.Capacity',DB::raw('COUNT(t1.id ) as AvailableRooms')]);
                                $AvailableRooms=0;
                                    if( isset($a->AvailableRooms))
                                    {
                                        if( $a->AvailableRooms < $a->Capacity)
                                        {
                                            $AvailableRooms = 1;
                                        }
                                        else {
                                            $AvailableRooms = $a->AvailableRooms;
                                        }
                                    }


                                $BedCapacity = AvailableAccommodationModel::where('resort_id', $this->resort->resort_id)
                                                                        ->where("BuildingName", $building->id)
                                                                        ->get([DB::RAW('SUM(available_accommodation_models.Capacity) as BedCapacity')]);

                                $AvailableBed = AvailableAccommodationModel::join('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
                                                                ->where('available_accommodation_models.resort_id', $this->resort->resort_id)
                                                                ->where("BuildingName", $building->id)
                                                                ->where("emp_id", 0)
                                                                // ->groupBy('t1.available_a_id')
                                                                ->first([DB::raw('COUNT(t1.id ) as BedCapacity')]);
                                $building->AvailableRooms =$AvailableRooms;
                                $building->BedCapacity = isset($BedCapacity[0]) ? $BedCapacity[0]->BedCapacity :0;
                                $building->AvailableBed = isset($AvailableBed) ? $AvailableBed->BedCapacity :0;

                                $OccupancyLevel = OccupancyLevelsHitACriticalThreshold::where("resort_id", $this->resort->resort_id)
                                ->where('building_id', $building->id)
                                ->first();
                                        $AvailableBed = ((int)$building->AvailableBed>0) ?  (int)$building->AvailableBed : 1;
                                $BedCapacity = ((int)$building->BedCapacity>0) ?  (int)$building->BedCapacity : 1;
                               
                                $BedPerc = ((int)$AvailableBed / (int)$BedCapacity) * 100;
                                if(isset($OccupancyLevel) && $OccupancyLevel->ThresSoldLevel  >= $BedPerc)
                                {
                                    $building->OccupancyLevel = "Alert";
                                }
                                else
                                {
                                    $building->OccupancyLevel = "No";
                                }

                           

        return $building;
    });


        return view('resorts.Accommodation.AccommodationMaster.index',compact('page_title','BuildingModel'));
    }

    public function GetBuildingWiseCollpasedData(Request $request)
    {
        $id = $request->id;


        if ($request->ajax())
        {
            $bedsType = $request->beds;

            $data = AvailableAccommodationModel::where("BuildingName", $id)
                ->join('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
                ->where('available_accommodation_models.resort_id', $this->resort->resort_id)
                ->with('availableAccommodationInvItem.inventoryModule', 'accommodationType')
                ->groupby('available_accommodation_models.RoomNo');
            if ($bedsType != "all")
            {
                $data->where('available_accommodation_models.blockFor', $bedsType);
            }
                $data = $data->get()->map(function ($accommodation)
                {
                    $itemData = [];
                    foreach ($accommodation->availableAccommodationInvItem as $item)
                    {
                        $inventoryItem = $item->inventoryModule ? ucfirst($item->inventoryModule->ItemName) : 'Unknown';
                        $itemData[] = ['inventoryItem' => $inventoryItem];
                    }
                    $accommodation->items = $itemData;
                    $accommodation->Color = $accommodation->accommodationType->Color ?? '#000000';
                    $accommodation->AccommodationName = $accommodation->accommodationType->AccommodationName ?? 'Not Available';
                    $AssingAccommodation = AssingAccommodation::where("available_a_id", $accommodation->id)
                                                            ->join('employees as t2', 't2.id', '=', 'assing_accommodations.emp_id')
                                                            ->join('resort_admins as t3', 't3.id', '=', 't2.Admin_Parent_id')
                                                            ->get(['t3.first_name', 't3.last_name', 't3.id as Parentid'])->map(function ($row) {
                                                                $row->EmployeeName = ucfirst($row->first_name . ' ' . $row->last_name);
                                                                $row->profileImg = Common::getResortUserPicture($row->Parentid);
                                                                return $row;
                                                            });
                    $accommodation->AssingAccommodation =$AssingAccommodation;
                    $accommodation->AssingAccommodationCount = $AssingAccommodation->where("assing_accommodations.emp_id", 0)->count();
                    return $accommodation;
                });



                return datatables()->of($data)
                ->editColumn('FloorNo', fn($row) => e($row->Floor))
                ->editColumn('RoomNo', fn($row) => e($row->RoomNo))
                ->editColumn('For', fn($row) => e($row->blockFor)) // Confirm if 'RoomNo' is the correct value for 'For'
                ->editColumn('EmployeeCategory', function ($row) {
                    $Rank = config('settings.eligibilty');
                    return e($Rank[$row->RoomType] ?? 'Unknown');
                })
                ->editColumn('RoomFacilities', function ($row) {
                    $itemNames = array_column($row->items, 'inventoryItem');
                    // $d = ($row->Capacity == $row->AssingAccommodationCount)
                    //     ? $row->Capacity
                    //     : $row->AssingAccommodationCount;

                    $facilities = e(implode(", ", $itemNames));
                    if ($row->Capacity!= 0 && $row->Capacity !=$row->AssingAccommodationCount) {
                        $capacity =  $row->Capacity  -  $row->AssingAccommodationCount;
                        if($capacity ==0)
                        {
                            $facilities .= ' <span class="badge badge-danger"> No Bed Available</span>';
                        }
                        else {
                            $facilities .= ' <span class="badge badge-green">' . $capacity . ' Bed Available</span>';

                        }
                    }

                    else
                    {
                        $facilities .= ' <span class="badge badge-danger"> No Bed Available</span>';
                    }

                    return $facilities;
                })
                ->editColumn('BedCapacity', fn($row) => e($row->Capacity ?? 0))
                ->editColumn('AssignTo', function ($row) {
                    $string='';
                    if($row->AssingAccommodation->isNotEmpty())
                    {
                        foreach($row->AssingAccommodation as $d)
                        {
                            $string.='<div class="tableUser-block">
                                    <div class="img-circle">
                                        <img src="' . e($d->profileImg) . '" alt="user">
                                    </div>
                                    <span class="userApplicants-btn">' . e($d->EmployeeName) . '</span>
                                </div>';
                        }
                    }
                    else {
                        $string='<span class="badge badge-danger">No Employee Assigned</span>';
                    }

                    return   $string;
                })
                ->editColumn('RoomStatus', function ($row) {
                      return '<span class="d-flex text-successTheme">
                                    <i class="fa-solid fa-circle-check"></i> Ready to be checked in
                                </span>';
                })
                ->rawColumns(['AssignTo', 'RoomFacilities', 'RoomStatus']) // Ensure proper rendering for these columns
                ->make(true);
        }
    }

    public function EmployeeAccommodation(Request $request)
    {
        
        $data = AvailableAccommodationModel::join('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
                    ->join('employees as t2', 't2.id', '=', 't1.emp_id')
                    ->join('resort_positions as t4', 't4.id', '=', 't2.Position_id')
                    ->join('resort_departments as t6', 't6.id', '=', 't2.Dept_id')
                    ->join('resort_admins as t3', 't3.id', '=', 't2.Admin_Parent_id')
                    ->join('building_models as t5', 't5.id', '=', 'available_accommodation_models.BuildingName')
                    ->join('bulidng_and_floor_and_rooms as t7', 't7.building_id', '=', 'available_accommodation_models.BuildingName')
                    ->join('accommodation_types as t8', 't8.id', '=', 'available_accommodation_models.Accommodation_type_id')
                    ->where('t6.resort_id', $this->resort->resort_id)
                    ->where('available_accommodation_models.resort_id', $this->resort->resort_id)
                    ->groupBy('t2.id')
                    ->paginate(10, [
                        'available_accommodation_models.id as AvailableAccommodation_ID',
                        't8.AccommodationName',
                        't7.Floor',
                        't7.Room',
                        't5.BuildingName as BName',
                        't6.name as DepartmentName',
                        't4.position_title',
                        't2.id as employee_id',
                        't2.Emp_id',
                        't3.id as Parentid',
                        't3.first_name',
                        't3.last_name',
                        'available_accommodation_models.*',
                        't1.effected_date',
                    ]);

                // Transform the collection
                $data->getCollection()->transform(function ($accommodation) {
                    // Fetch related data manually due to join query
                    $accommodationModel = AvailableAccommodationModel::with(['availableAccommodationInvItem.inventoryModule', 'accommodationType'])
                        ->find($accommodation->AvailableAccommodation_ID);

                    $itemData = [];
                    foreach ($accommodationModel->availableAccommodationInvItem as $item) {
                        $inventoryItem = $item->inventoryModule
                            ? ucfirst($item->inventoryModule->ItemName)
                            : 'Unknown';
                        $itemData[] = ['inventoryItem' => $inventoryItem];
                    }

                    $accommodation->items = $itemData;
                    $accommodation->Color = $accommodationModel->accommodationType->Color ?? '#000000';
                    $accommodation->AccommodationName = $accommodationModel->accommodationType->AccommodationName ?? 'Not Available';
                    $AssingAccommodation = AssingAccommodation::where("available_a_id", $accommodation->AvailableAccommodation_ID)->get();
                    $accommodation->EmployeeName = ucfirst($accommodation->first_name . ' ' . $accommodation->last_name);
                    $accommodation->profileImg = Common::getResortUserPicture($accommodation->Parentid);
                    $accommodation->AssingAccommodationCount = $AssingAccommodation->where("assing_accommodations.emp_id", 0)->count();
                    $accommodation->effected_date = Carbon::parse($accommodation->effected_date)->format('d F Y');

                    return $accommodation;
                });
        // Pass the paginated data to the view
            $ResortDepartment = ResortDepartment::where('resort_id', $this->resort->resort_id)->get();
        $page_title="Employee Accommodation";
        return view('resorts.Accommodation.AccommodationMaster.EmployeeAccommodation',compact('page_title','data','ResortDepartment'));
    }
    public function SearchEmpAccommodationgird(Request $request)
    {


        $Poitions = $request->input('Poitions');
        $search = $request->input('search');

        // Start building the query
        $dataQuery = AvailableAccommodationModel::join('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
            ->join('employees as t2', 't2.id', '=', 't1.emp_id')
            ->join('resort_positions as t4', 't4.id', '=', 't2.Position_id')
            ->join('resort_departments as t6', 't6.id', '=', 't2.Dept_id')
            ->join('resort_admins as t3', 't3.id', '=', 't2.Admin_Parent_id')
            ->join('building_models as t5', 't5.id', '=', 'available_accommodation_models.BuildingName')
            ->join('bulidng_and_floor_and_rooms as t7', 't7.building_id', '=', 'available_accommodation_models.BuildingName')
            ->join('accommodation_types as t8', 't8.id', '=', 'available_accommodation_models.Accommodation_type_id')
            ->where('t6.resort_id', $this->resort->resort_id)
            ->where('available_accommodation_models.resort_id', $this->resort->resort_id)
            ->with('availableAccommodationInvItem.inventoryModule', 'accommodationType');
        if ($Poitions)
        {
            $dataQuery->where('t4.id', $Poitions);
        }

        // Apply filter for search (e.g., Employee Name or Department Name)
        if ($search) {
            $dataQuery->where(function ($query) use ($search) {
                $query->where('t3.first_name', 'like', '%' . $search . '%')
                    ->orWhere('t3.last_name', 'like', '%' . $search . '%')
                    ->orWhere('t2.Emp_id', 'like', '%' . $search . '%')
                    ->orWhere('t6.name', 'like', '%' . $search . '%');
            });
        }

        // Execute the query and process results
        $data = $dataQuery->groupBy('t2.id')
        ->paginate(1, [
            'available_accommodation_models.id as AvailableAccommodation_ID',
            't8.AccommodationName',
            't7.Floor',
            't7.Room',
            't5.BuildingName as BName',
            't6.name as DepartmentName',
            't4.position_title',
            't2.id as employee_id',
            't2.Emp_id',
            't3.id as Parentid',
            't3.first_name',
            't3.last_name',
            'available_accommodation_models.*',
            't1.effected_date',
        ]);

        $data->getCollection()->transform(function ($accommodation) {
            // Fetch related data manually due to join query
            $accommodationModel = AvailableAccommodationModel::with(['availableAccommodationInvItem.inventoryModule', 'accommodationType'])
                ->find($accommodation->AvailableAccommodation_ID);

            $itemData = [];
            foreach ($accommodationModel->availableAccommodationInvItem as $item) {
                $inventoryItem = $item->inventoryModule
                    ? ucfirst($item->inventoryModule->ItemName)
                    : 'Unknown';
                $itemData[] = ['inventoryItem' => $inventoryItem];
            }

            $accommodation->items = $itemData;
            $accommodation->Color = $accommodationModel->accommodationType->Color ?? '#000000';
            $accommodation->AccommodationName = $accommodationModel->accommodationType->AccommodationName ?? 'Not Available';
            $AssingAccommodation = AssingAccommodation::where("available_a_id", $accommodation->AvailableAccommodation_ID)->get();
            $accommodation->EmployeeName = ucfirst($accommodation->first_name . ' ' . $accommodation->last_name);
            $accommodation->profileImg = Common::getResortUserPicture($accommodation->Parentid);
            $accommodation->AssingAccommodationCount = $AssingAccommodation->where("assing_accommodations.emp_id", 0)->count();
            $accommodation->effected_date = Carbon::parse($accommodation->effected_date)->format('d F Y');

            return $accommodation;
        });

            $view  = view('resorts.renderfiles.EmployeeAccommodationGird',compact('data'))->render();
            return response()->json(['success' =>true,'view'=>$view], 200);

    }

    public function EmpAccommodationList(Request $request)
    {

        if($request->ajax())
        {

            $Poitions = $request->input('Poitions');
            $search = $request->input('searchTerm');

          // Start building the query
          $dataQuery = AvailableAccommodationModel::join('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
            ->join('employees as t2', 't2.id', '=', 't1.emp_id')
            ->join('resort_positions as t4', 't4.id', '=', 't2.Position_id')
            ->join('resort_departments as t6', 't6.id', '=', 't2.Dept_id')
            ->join('resort_admins as t3', 't3.id', '=', 't2.Admin_Parent_id')
            ->join('building_models as t5', 't5.id', '=', 'available_accommodation_models.BuildingName')
            ->join('bulidng_and_floor_and_rooms as t7', 't7.building_id', '=', 'available_accommodation_models.BuildingName')
            ->join('accommodation_types as t8', 't8.id', '=', 'available_accommodation_models.Accommodation_type_id')
            ->where('t6.resort_id', $this->resort->resort_id)
            ->where('available_accommodation_models.resort_id', $this->resort->resort_id)

            ->with('availableAccommodationInvItem.inventoryModule', 'accommodationType');
        if ($Poitions)
        {
            $dataQuery->where('t4.id', $Poitions);
        }

        // Apply filter for search (e.g., Employee Name or Department Name)
        if ($search) {
            $dataQuery->where(function ($query) use ($search) {
                $query->where('t3.first_name', 'like', '%' . $search . '%')
                    ->orWhere('t3.last_name', 'like', '%' . $search . '%')
                    ->orWhere('t2.Emp_id', 'like', '%' . $search . '%')
                    ->orWhere('t6.name', 'like', '%' . $search . '%');
            });
        }

        // Execute the query and process results
        $data = $dataQuery->groupBy('t2.id')
            ->get([
                'available_accommodation_models.id as AvailableAccommodation_ID',
                't8.AccommodationName',
                't7.Floor',
                't7.Room',
                't5.BuildingName as BName',
                't6.name as DepartmentName',
                't4.position_title',
                't2.id as employee_id',
                't2.Emp_id',
                't2.joining_date',
                't3.id as Parentid',
                't3.first_name',
                't3.last_name',
                'available_accommodation_models.*',
                't1.effected_date',
            ])
            ->map(function ($accommodation) {
                $itemData = [];
                foreach ($accommodation->availableAccommodationInvItem as $item)
                {
                    $inventoryItem = $item->inventoryModule
                        ? ucfirst($item->inventoryModule->ItemName)
                        : 'Unknown';
                    $itemData[] = ['inventoryItem' => $inventoryItem];
                }
                $accommodation->items = $itemData;
                $accommodation->Color = $accommodation->accommodationType->Color ?? '#000000';
                $accommodation->AccommodationName = $accommodation->accommodationType->AccommodationName ?? 'Not Available';
                $AssingAccommodation = AssingAccommodation::where("available_a_id", $accommodation->id)->get();
                $accommodation->EmployeeName = ucfirst($accommodation->first_name . ' ' . $accommodation->last_name);
                $accommodation->profileImg = Common::getResortUserPicture($accommodation->Parentid);
                $accommodation->AssingAccommodationCount = $AssingAccommodation->where("assing_accommodations.emp_id", 0)->count();
                $accommodation->effected_date = Carbon::parse($accommodation->effected_date)->format('d F Y');
                $accommodation->joining_date = Carbon::parse($accommodation->joining_date)->format('d F Y');
                return $accommodation;
            });

            
            return datatables()->of($data)
            ->editColumn('Name', function($row){
                $row->EmployeeName;
                return '<div class="tableUser-block">
                            <div class="img-circle">
                                <img src="' . $row->profileImg . '" alt="user">
                            </div>
                            <span class="userApplicants-btn">' . htmlspecialchars($row->EmployeeName, ENT_QUOTES, 'UTF-8') . '</span>
                        </div>';
            })
            ->editColumn('Position', fn($row) => e($row->position_title))
            ->editColumn('Department', fn($row) => e($row->DepartmentName))
            ->editColumn('Since', fn($row) => e($row->effected_date))
            ->editColumn('Building', fn($row) => e($row->BName))
            ->editColumn('FloorNo', fn($row) => e($row->Floor))
            ->editColumn('RoomNo', fn($row) => e($row->RoomNo))
            ->editColumn('RoomType', fn($row) => e($row->AccommodationName))
            ->editColumn('Action', function ($row) {
                    $route =route('resort.accommodation.AccommodationEmployeeDetails',base64_encode($row->employee_id));
                return '<a target="_blank" href="'.$route.'"  class="btn btn-sm btn-themeSkyblue btn-sm">View Details</a>';
            })

                ->rawColumns(['Name','Action'])->make(true);
        }
    }
    public function AccommodationEmployeeDetails($id)
    {
        if(Common::checkRouteWisePermission('resort.accommodation.EmployeeAccommodation',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }
        
        $id = base64_decode($id);
        $employee = Employee::with(['resortAdmin','position'])->find($id);
        $BuildingModel = BuildingModel::join('bulidng_and_floor_and_rooms as t1', 't1.building_id', '=', 'building_models.id')
                                    ->where("building_models.resort_id", $this->resort->resort_id)
                                    ->groupBy('building_models.id','building_models.id')
                                    ->get();
        $data = AvailableAccommodationModel::join('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
                ->join('employees as t2', 't2.id', '=', 't1.emp_id')
                ->join('resort_positions as t4', 't4.id', '=', 't2.Position_id')
                ->join('resort_departments as t6', 't6.id', '=', 't2.Dept_id')
                ->join('resort_admins as t3', 't3.id', '=', 't2.Admin_Parent_id')
                ->join('building_models as t5', 't5.id', '=', 'available_accommodation_models.BuildingName')
                ->join('bulidng_and_floor_and_rooms as t7', 't7.building_id', '=', 'available_accommodation_models.BuildingName')
                ->join('accommodation_types as t8', 't8.id', '=', 'available_accommodation_models.Accommodation_type_id')
                ->leftjoin('transfer_accommodations as t9', 't9.NewAccommodation_id', '=', 't1.id')
                ->where('t2.id', $id)
                ->where('available_accommodation_models.resort_id', $this->resort->resort_id)
                ->with('availableAccommodationInvItem.inventoryModule', 'accommodationType')
                ->groupBy('t2.id')
                ->first(['t1.id as ChildBedId','t2.id as employee_id','available_accommodation_models.id as AvailableAccommodation_ID','available_accommodation_models.CleaningSchedule','t8.AccommodationName','t7.Floor','t7.Room','t5.BuildingName as BName','t6.name as DepartmentName','t4.position_title','t2.id as employee_id','t2.Emp_id','t3.id as Parentid', 't3.first_name', 't3.last_name', 'available_accommodation_models.*','t1.effected_date']);
        $history = collect();
        $AssingAccommodation = collect();

            if ($data) 
            {
                $history = TransferAccommodation::join('assing_accommodations as t1', 't1.id',"=",'transfer_accommodations.OldAccommodation_id')
                                    ->join('available_accommodation_models as t2', 't2.id',"=","t1.available_a_id")
                                    ->join('building_models as t3', 't3.id', '=', 't2.BuildingName')
                                    ->where("transfer_accommodations.Emp_id",$data->employee_id)
                                    ->get()
                                    ->map(function($i){
                                        $i->NewDate = $i->BuildingName.' Floor'.$i->Floor.',Room-'.$i->RoomNo. ' ('. Date('M Y',strtotime($i->OldDate)).  ' To '. Date('M Y',strtotime($i->NewdDate)) .')';

                                        return $i;
                                    });

                $AssingAccommodation = AssingAccommodation::where("available_a_id", $data->AvailableAccommodation_ID)
                ->where("t2.id","!=", $data->employee_id)
                ->join('employees as t2', 't2.id', '=', 'assing_accommodations.emp_id')
                ->join('resort_admins as t3', 't3.id', '=', 't2.Admin_Parent_id')
                ->get(['t3.first_name', 't3.last_name', 't3.id as Parentid'])->map(function ($row) {
                    $row->EmployeeName = ucfirst($row->first_name . ' ' . $row->last_name);
                    $row->profileImg = Common::getResortUserPicture($row->Parentid);
                    return $row;
                });

                $data->profileImg = Common::getResortUserPicture($data->Parentid);
                $data->EmployeeName = ucfirst($data->first_name.' '.$data->last_name);
                $data->effected_date =  Carbon::parse($data->effected_date)->format('d F Y');
                $now = Carbon::now();
                $effectedDate = Carbon::parse($data->effected_date);
                $years = $effectedDate->diffInYears($now);
                $months = $effectedDate->copy()->addYears($years)->diffInMonths($now);
                $data->effected_date_diff = "{$years} year(s), {$months} month(s)";

                $itemData = [];
                $item_id = [];

                foreach ($data->availableAccommodationInvItem as $item) {
                    $item_id[]= $item->Item_id;
                    $inventoryItem = $item->inventoryModule
                        ? ucfirst($item->inventoryModule->ItemName)
                        : 'Unknown';
                    $itemData[] =  $inventoryItem;
                }
                $data->itemData= $itemData;
                $data->item_id = $item_id;
            }   

        // ->map(function ($accommodation) {
        //

        //     $accommodation->AccommodationName = $accommodation->accommodationType->AccommodationName ?? 'Not Available';
        //     $AssingAccommodation = AssingAccommodation::where("available_a_id", $accommodation->id)->get();
        //     $accommodation->EmployeeName = ucfirst($accommodation->first_name . ' ' . $accommodation->last_name);
        //     $accommodation->profileImg = Common::getResortUserPicture($accommodation->Parentid);
        //     $accommodation->AssingAccommodationCount = $AssingAccommodation->where("assing_accommodations.emp_id", 0)->count();
        //     $accommodation->effected_date=Carbon::parse($accommodation->effected_date)->format('d F Y');
        //     return $accommodation;
        // });


        $InventoryModule = InventoryModule::where('resort_id', $this->resort->resort_id)->get();

        $page_title ="Accommodation Employee Details";
        return view('resorts.Accommodation.AccommodationMaster.EmployeeDetails',compact('history','employee','page_title','data','BuildingModel','InventoryModule','AssingAccommodation'));

    }
    public function AssignMoreAccommodationToEmp(Request $request)
    {
        $id = $request->AvailableAccommodation_ID;
        $items = $request->InventoryModule;

        // Start a database transaction
        DB::beginTransaction();

        try {
            // Iterate through each item and assign to the employee
            foreach ($items as $items_id) {
                AvailableAccommodationInvItem::updateOrCreate(
                    ["Available_Acc_id" => $id, "Item_id" => $items_id],
                    ["Available_Acc_id" => $id, "Item_id" => $items_id]
                );
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Ammenity assigned successfully',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());

            // Return an error response
            return response()->json([
                'success' => false,
                'error' => 'Failed to add ammenity',
                'message' => 'Failed to assign bed',
            ], 500);
        }
    }
    public function GetBuilidingWiseAvailableAccommodation(Request $request)
    {

        $id = $request->id;
        $page_title ="Accommodation Master";
        // $BuildingModel = BuildingModel::where("resort_id",$this->resort->resort_id)
        //                 ->get()
        //                 ->map(function($i)
        //                 {
        //                     $data = AvailableAccommodationModel::where("BuildingName", $i->id)
        //                         ->join('assing_accommodations as t1','t1.available_a_id',"=",'available_accommodation_models.id')
        //                         ->join('employees as t2',"t2.id","=","t1.emp_id")
        //                         ->join('resort_admins as t3',"t3.id","=","t2.Admin_Parent_id")
        //                         ->where('available_accommodation_models.resort_id', $this->resort->resort_id)
        //                         ->with('availableAccommodationInvItem.inventoryModule', 'accommodationType')


        //                         ->get(['t3.id as Parentid','t3.first_name','t3.last_name','available_accommodation_models.*'])
        //                         ->map(function ($accommodation) {

        //                             $AssingAccommodation = AssingAccommodation::where("available_a_id", $accommodation->id)
        //                                                                         ->get();

        //                             $accommodation->EmployeeName = ucfirst($accommodation->first_name.' '.$accommodation->last_name);
        //                             $accommodation->profileImg = Common::getResortUserPicture($accommodation->Parentid);
        //                             $accommodation->AssingAccommodationCount = $AssingAccommodation->where("assing_accommodations.emp_id", 0)->count();

        //                             $accommodation->bedAvailable = ($accommodation->Capacity == $accommodation->AssingAccommodationCount)? $accommodation->Capacity: $row->AssingAccommodationCount;


        //                             return $accommodation;

        //                         });

        //                         $TotalRoom = $data->sum(function ($accommodation) {
        //                             return is_numeric($accommodation->Capacity) ? $accommodation->Capacity : 0;
        //                         });
        //                         $i->TotalRoom = $TotalRoom;
        //                         return $i;
        //                 });

        $BuildingModel = BuildingModel::join('bulidng_and_floor_and_rooms as t1', 't1.building_id', '=', 'building_models.id')
        ->where("building_models.resort_id", $this->resort->resort_id)
        ->groupBy('building_models.id','building_models.id')
        ->get(['building_models.*',DB::RAW('Count(t1.building_id) as TotalRoom')])
        ->map(function ($building) {



                $accommodations = AvailableAccommodationModel::join('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
                                        ->join('employees as t2', 't2.id', '=', 't1.emp_id')
                                        ->join('resort_admins as t3', 't3.id', '=', 't2.Admin_Parent_id')
                                        ->where('available_accommodation_models.resort_id', $this->resort->resort_id)
                                        ->where("BuildingName", $building->id)
                                        ->with('availableAccommodationInvItem.inventoryModule', 'accommodationType')
                                        ->get(['t1.emp_id','t3.id as Parentid', 't3.first_name', 't3.last_name', 'available_accommodation_models.*'])
                                        ->map(function ($accommodation)
                                        {
                                            $assignedAccommodations = AssingAccommodation::where("available_a_id", $accommodation->id)->get();
                                            $accommodation->EmployeeName = ucfirst($accommodation->first_name . ' ' . $accommodation->last_name);
                                            $accommodation->profileImg =  Common::getResortUserPicture($accommodation->Parentid);
                                            $accommodation->AssingAccommodationCount = $assignedAccommodations->where("emp_id", 0)->count();
                                            $accommodation->bedAvailable = ($accommodation->Capacity == $accommodation->AssingAccommodationCount) ? $accommodation->Capacity : $accommodation->AssingAccommodationCount;

                                            return $accommodation;
                                        });
                                        $a = AvailableAccommodationModel::join('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
                                                                        ->where('available_accommodation_models.resort_id', $this->resort->resort_id)
                                                                        ->where("BuildingName", $building->id)
                                                                        ->where("t1.emp_id", 0)
                                                                        ->groupBy('t1.available_a_id')
                                                                        ->first(['available_accommodation_models.id','available_accommodation_models.Capacity',DB::raw('COUNT(t1.id ) as AvailableRooms')]);
                                        $AvailableRooms=0;
                                            if( isset($a->AvailableRooms))
                                            {
                                                if( $a->AvailableRooms < $a->Capacity)
                                                {
                                                    $AvailableRooms = 1;
                                                }
                                                else {
                                                    $AvailableRooms = $a->AvailableRooms;
                                                }
                                            }
                                        $BedCapacity = AvailableAccommodationModel::where('resort_id', $this->resort->resort_id)
                                                                                ->where("BuildingName", $building->id)
                                                                                ->get([DB::RAW('SUM(available_accommodation_models.Capacity) as BedCapacity')]);
                                        $AvailableBed = AvailableAccommodationModel::join('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
                                                                        ->where('available_accommodation_models.resort_id', $this->resort->resort_id)
                                                                        ->where("BuildingName", $building->id)
                                                                        ->where("emp_id", 0)
                                                                        // ->groupBy('t1.availabl  e_a_id')
                                                                        ->first([DB::raw('COUNT(t1.id ) as BedCapacity')]);
                                        $building->AvailableRooms =$AvailableRooms;
                                        $building->BedCapacity = isset($BedCapacity[0]) ? $BedCapacity[0]->BedCapacity :0;
                                        $building->AvailableBed = isset($AvailableBed) ? $AvailableBed->BedCapacity :0;


                return $building;
            });


        $page_title ='Available Accommodation';
        return view('resorts.Accommodation.AccommodationMaster.AvailableAccommodation',compact('page_title','BuildingModel'));

    }

    public function GetBuildingWiseAvailableCollpasedData(Request $request)
    {
        $id = $request->id;


        if ($request->ajax())
        {
            $bedsType = $request->beds;

            $data = AvailableAccommodationModel::where("BuildingName", $id)
                ->join('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
                ->where('available_accommodation_models.resort_id', $this->resort->resort_id)
                ->with('availableAccommodationInvItem.inventoryModule', 'accommodationType')
                ->groupby('available_accommodation_models.RoomNo');
                if ($bedsType != "all")
                {
                    $data->where('available_accommodation_models.blockFor', $bedsType);
                }

                $data = $data->get(['available_accommodation_models.id as available_a_id','available_accommodation_models.*'])->map(function ($accommodation)
                {
                    $accommodation->available_a_id= base64_encode($accommodation->available_a_id);

                    $accommodation->RoomType_id= base64_encode($accommodation->RoomType);

                    $itemData = [];
                    foreach ($accommodation->availableAccommodationInvItem as $item)
                    {
                        $inventoryItem = $item->inventoryModule ? ucfirst($item->inventoryModule->ItemName) : 'Unknown';
                        $itemData[] = ['inventoryItem' => $inventoryItem];
                    }
                    $accommodation->items = $itemData;
                    $accommodation->Color = $accommodation->accommodationType->Color ?? '#000000';
                    $accommodation->AccommodationName = $accommodation->accommodationType->AccommodationName ?? 'Not Available';

                    $AssingAccommodation = AssingAccommodation::where("available_a_id", $accommodation->id)
                                                            ->join('employees as t2', 't2.id', '=', 'assing_accommodations.emp_id')
                                                            ->join('resort_admins as t3', 't3.id', '=', 't2.Admin_Parent_id')
                                                            ->get(['t3.first_name', 't3.last_name', 't3.id as Parentid'])->map(function ($row) {
                                                                $row->EmployeeName = ucfirst($row->first_name . ' ' . $row->last_name);
                                                                $row->profileImg = Common::getResortUserPicture($row->Parentid);
                                                                return $row;
                                                            });
                    $accommodation->AssingAccommodation =$AssingAccommodation;
                    $accommodation->AssingAccommodationCount = $AssingAccommodation->where("assing_accommodations.emp_id", 0)->count();
                    return $accommodation;
                });


                $edit_class = '';
                if(Common::checkRouteWisePermission('resort.accommodation.AvailableAccommodation',config('settings.resort_permissions.edit')) == false){
                    $edit_class = 'd-none';
                }

                return datatables()->of($data)
                ->editColumn('FloorNo', fn($row) => e($row->Floor))
                ->editColumn('RoomNo', fn($row) => e($row->RoomNo))
                ->editColumn('For', fn($row) => e($row->blockFor)) // Confirm if 'RoomNo' is the correct value for 'For'
                ->editColumn('EmployeeCategory', function ($row) {
                    $Rank = config('settings.eligibilty');
                    return e($Rank[$row->RoomType] ?? 'Unknown');
                })
                ->editColumn('RoomFacilities', function ($row) {
                    $itemNames = array_column($row->items, 'inventoryItem');
                    // $d = ($row->Capacity == $row->AssingAccommodationCount)
                    //     ? $row->Capacity
                    //     : $row->AssingAccommodationCount;

                    $facilities = e(implode(", ", $itemNames));
                    if ($row->Capacity!= 0 && $row->Capacity !=$row->AssingAccommodationCount)
                    {
                        $capacity =  $row->Capacity  -  $row->AssingAccommodationCount;
                        if($capacity ==0)
                        {
                            $facilities .= ' <span class="badge badge-danger"> No Bed Available</span>';
                        }
                        else
                        {
                            $facilities .= ' <span class="badge badge-green">' . $capacity . ' Bed Available</span>';
                        }
                    }

                    else
                    {
                        $facilities .= ' <span class="badge badge-danger"> No Bed Available</span>';
                    }

                    return $facilities;
                })
                ->editColumn('BedCapacity', fn($row) => e($row->Capacity ?? 0))
                ->editColumn('Action', function ($row) use ($edit_class){
                    if (($row->Capacity!= 0 && $row->Capacity !=$row->AssingAccommodationCount) == true)
                    {
                     return'<a href="javascript:void(0)" class="btn btn-themeSkyblueLight AssingToRoom btn-small '.$edit_class.'" data-id="'.$row->available_a_id.'" data-RoomType="'.$row->RoomType_id.'">Assign</a>';
                    }
                    else {
                        return'';
                    }
                })
                ->editColumn('RoomStatus', function ($row) {
                      return '<span class="d-flex text-successTheme">
                                    <i class="fa-solid fa-circle-check"></i> Ready to be checked in
                                </span>';

                })

                ->rawColumns(['Action', 'RoomFacilities', 'RoomStatus']) // Ensure proper rendering for these columns
                ->make(true);

        }

    }



}
