<?php

namespace App\Http\Controllers\Resorts\Accommodation;

use App\Models\AvailableAccommodationModel;
use DB;
use URL;
use Auth;
use Carbon\Carbon;
use App\Helpers\Common;
use App\Models\Employee;
use Carbon\CarbonInterval;
use Illuminate\Http\Request;
use App\Models\BuildingModel;
use App\Models\InventoryModule;
use App\Models\MaintanaceRequest;
use App\Models\AssingAccommodation;
use App\Models\InventoryCategoryModel;
use App\Models\ChildMaintananceRequest;
use App\Http\Controllers\Controller;
use App\Models\BulidngAndFloorAndRoom;
use App\Models\ResortDepartment;
use App\Models\EscalationDay;

use App\Models\ChildApprovedMaintanaceRequests;
class AccommodationDashboardController extends Controller
{

    public $globalUser='';
    protected $underEmp_id=[];

    public function __construct()
    {
        $this->globalUser = Auth::guard('resort-admin')->user();
        $this->resort = $resortId = auth()->guard('resort-admin')->user();
        if($this->resort->is_master_admin == 0){
            $this->reporting_to = isset($this->globalUser->GetEmployee) ? $this->globalUser->GetEmployee->id:3;
            $this->underEmp_id = Common::getSubordinates($this->reporting_to);
        }

        
    }
    public function Admin_dashboard(Request $request)
    {
        $page_title="Accommodation Dashboard";
        if($request->ajax())
        {


            $MaintanaceRequest = MaintanaceRequest::join("employees as t3","t3.id","maintanace_requests.Raised_By")
                                                ->join("resort_admins as t1","t1.id","t3.Admin_Parent_id")
                                                ->join("resort_departments as t4","t4.id","t3.Dept_id")
                                                ->whereNotIn('maintanace_requests.Status', ['Closed', 'On-Hold']);
                                                if( isset($request->ResortDepartment))
                                                {

                                                    $MaintanaceRequest ->where('t4.id',$request->ResortDepartment);
                                                }
                                                $MaintanaceRequest =  $MaintanaceRequest->leftjoin("resort_admins as t2","t2.id","maintanace_requests.Assigned_To")
                                                ->orderBy('maintanace_requests.id','desc')
                                                ->get(['t1.id as Parentid','t1.first_name','t1.last_name','t2.id as Assign_Parentid','t2.first_name as Assign_first_name','t2.last_name as Assign_last_name','maintanace_requests.*'])
                                                ->map(function ($row) 
                                                {
                                                    $row->RequestedBy=$row->first_name.' '.$row->last_name;
                                                    $row->AssgingedStaff=$row->Assigned_To;
                                                    $row->Location=$row->BuilidngData->BuildingName.',Room No - '.$row->RoomNo.',Floor No -'.$row->FloorNo;
                                                    $row->Priority = $row->priority;
                                                    $row->Date =$row->created_at->format('d M Y');
                                                    $row->profileImg = Common::getResortUserPicture($row->Parentid);
                                                    $InventoryModule= InventoryModule::where('resort_id',$this->globalUser->resort_id)
                                                                                    ->where("id",$row->item_id)
                                                                                    ->first('ItemName');
                                                    if(isset($row->Assigned_To))
                                                    {
                                                        $emp = Common::GetEmployeeDetails($row->Assigned_To);
                                                        $row->Assign_profileImg = Common::getResortUserPicture($emp->Parent_id);
                                                        $row->Assign_toName     = $emp->first_name.' '.$row->last_name;
                                                    }
                                                    $row->EffectedAmenity =isset($InventoryModule->ItemName) ? ucfirst($InventoryModule->ItemName):'';
                                                    return  $row;
                                                });
            return datatables()->of($MaintanaceRequest)
                            ->addColumn('action', function ($row) {
                    $id = base64_encode($row->id);
                     if($row->Status!='Open')
                     {
                        $string = '<a href="'.route('resort.accommodation.MainRequestDetails',$id ).'" class="btn-tableIcon btnIcon-skyblue mainRequetDetails" data-task_id="'.$id.'"><i class="fa-regular fa-eye"></i></a>
                         <a href="javascript:void(0)" class="btn-tableIcon btnIcon-blue ForwardToHOD" data-bs-toggle="tooltip"
                                        data-bs-placement="bottom" title="Forward to HOD" data-req_id="'.$id.'" data-Location="'.$row->Location.'"data-EffectedAmenity="'. $row->EffectedAmenity .'"><i class=" fa-solid fa-share"></i></a>';
                     }
                     else

                     {
                        $string= '<a href="'.route('resort.accommodation.MainRequestDetails',$id ).'" class="btn-tableIcon btnIcon-skyblue mainRequetDetails" data-task_id="'.$id.'"><i class="fa-regular fa-eye"></i></a>';
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
                    })
                    ->rawColumns(['RequestedBy','Priority','action','AssgingedStaff','Status'])
                    ->make(true);
        }

            $Employee =Employee::join('resort_admins','resort_admins.id',"=",'employees.Admin_Parent_id')
                            ->where('employees.resort_id', $this->globalUser->resort_id)
                            ->where("employees.rank",2)
                            ->get(['employees.*','resort_admins.first_name','resort_admins.last_name']);
            $buildings = BuildingModel::where("resort_id", $this->globalUser->resort_id)
                            ->get()
                            ->reduce(function ($result, $building) {
                                // Initialize the array for this building if not set
                                if (!isset($result[$building->BuildingName])) {
                                    $result[$building->BuildingName] = [];
                                }

                                // Fetch data for the current building
                                $data = AvailableAccommodationModel::join('assing_accommodations', 'assing_accommodations.available_a_id', '=', 'available_accommodation_models.id')
                                    ->where('available_accommodation_models.BuildingName', $building->id) // Filter by building ID
                                    ->where('available_accommodation_models.resort_id', $this->globalUser->resort_id)
                                    ->selectRaw("
                                        available_accommodation_models.RoomType,
                                        available_accommodation_models.BuildingName,
                                        available_accommodation_models.RoomNo,
                                        available_accommodation_models.RoomStatus,
                                        assing_accommodations.emp_id,

                                           SUM(CASE WHEN assing_accommodations.emp_id != 0 THEN 1 ELSE 0 END) as OccupiedRooms,
                                        SUM(CASE WHEN assing_accommodations.emp_id = 0 THEN 1 ELSE 0 END) as AvailableRooms,
                                        SUM(CASE WHEN assing_accommodations.emp_id != 0 THEN 1 ELSE 0 END) as OccupiedRooms,
                                        SUM(CASE WHEN available_accommodation_models.RoomStatus = 'Available' THEN 1 ELSE 0 END) as MainAvailableRooms

                                    ")
                                    ->groupBy(
                                        'available_accommodation_models.RoomType',
                                        'available_accommodation_models.BuildingName'
                                    )
                                    ->get()
                                    ->map(function ($accommodation) use ($building, &$result) {
                                        // Get additional data for floors and rooms
                                        // dd($accommodation);
                                        $buildingData = BulidngAndFloorAndRoom::where("building_id", $building->id)
                                            ->selectRaw('COUNT(distinct(Floor)) as TotalFloors, COUNT(Room) as TotalRooms')
                                            ->where('resort_id', $this->globalUser->resort_id)
                                            ->groupBy('building_id')
                                            ->first();
                                            $a = AvailableAccommodationModel::join('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
                                            ->where('available_accommodation_models.resort_id', $this->globalUser->resort_id)
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
                                                $AvailableFloor=0;
                                                if( isset($a->AvailableRooms))
                                                {
                                                    if( $a->AvailableRooms < $a->Capacity)
                                                    {
                                                        $AvailableFloor = 1;
                                                    }
                                                    else {
                                                        $AvailableFloor = $a->AvailableRooms;
                                                    }
                                                }
                                                $MaleBeds = AvailableAccommodationModel::join('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
                                                ->where('available_accommodation_models.resort_id', $this->globalUser->resort_id)
                                                ->where("BuildingName", $building->id)
                                                ->where("t1.emp_id", 0)
                                                ->where("available_accommodation_models.blockFor",'Male')
                                                // ->groupBy('t1.available_a_id')
                                                ->first(['available_accommodation_models.id','available_accommodation_models.Capacity',DB::raw('COUNT(t1.id ) as AvailableMaleBeds')]);

                                                $AvailableMaleBeds=0;
                                                if( isset($MaleBeds->AvailableBeds))
                                                {
                                                        $AvailableMaleBeds = $MaleBeds->AvailableMaleBeds;
                                                }
                                                $FemaleBeds = AvailableAccommodationModel::join('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
                                                ->where('available_accommodation_models.resort_id', $this->globalUser->resort_id)
                                                ->where("BuildingName", $building->id)
                                                ->where("t1.emp_id", 0)
                                                ->where("available_accommodation_models.blockFor",'Female')
                                                // ->groupBy('t1.available_a_id')
                                                ->first(['available_accommodation_models.id','available_accommodation_models.Capacity',DB::raw('COUNT(t1.id ) as AvailableFemaleBeds')]);

                                                $AvailableFemaleBeds=0;
                                                if( isset($FemaleBeds->AvailableFemaleBeds))
                                                {
                                                        $AvailableFemaleBeds = $FemaleBeds->AvailableFemaleBeds;
                                                }
                                    $Othercounts = AvailableAccommodationModel::where('BuildingName', $building->id)
                                            ->select(DB::RAW('SUM(CASE WHEN available_accommodation_models.blockFor = \'Female\' THEN available_accommodation_models.Capacity ELSE 0 END) as FemaleAvailableBeds'),DB::RAW('SUM(CASE WHEN available_accommodation_models.blockFor = \'Male\' THEN available_accommodation_models.Capacity ELSE 0 END) as MaleAvailableBeds'))
                                            ->where('resort_id', $this->globalUser->resort_id)
                                            ->first();


                                        if (empty($result[$building->BuildingName])) {
                                            $result[$building->BuildingName][] = [
                                                'Floor' => $AvailableFloor . '/' . ($buildingData->TotalFloors ?? 0), // Pending
                                                'Room' => $AvailableRooms . '/' . ($buildingData->TotalRooms ?? 0), // Done
                                                'Male Beds' =>  $AvailableMaleBeds. '/' . ($Othercounts->MaleAvailableBeds ?? 0),
                                                'Female Beds' => $AvailableFemaleBeds . '/' . ($Othercounts->FemaleAvailableBeds ?? 0),
                                            ];
                                        }

                                        // Update the existing array for this building
                                        $Rank = config('settings.eligibilty');
                                        if (isset($Rank[$accommodation->RoomType])) {
                                            $rankKey = $Rank[$accommodation->RoomType];

                                            $TotaData=AvailableAccommodationModel::where('available_accommodation_models.resort_id', $this->globalUser->resort_id)
                                                                            ->where("BuildingName", $building->id)
                                                                            ->where("RoomType", $accommodation->RoomType)
                                                                            ->groupBy('RoomType')
                                                                            ->first(['available_accommodation_models.id','available_accommodation_models.Capacity',DB::raw('SUM(available_accommodation_models.Capacity) as TotalCapacity')]);

                                            $a = AvailableAccommodationModel::join('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
                                            ->where('available_accommodation_models.resort_id', $this->globalUser->resort_id)
                                            ->where("BuildingName", $building->id)
                                            ->where("t1.emp_id", 0)
                                            ->where("available_accommodation_models.RoomType", $accommodation->RoomType)
                                            ->groupBy('t1.available_a_id')
                                            ->first(['available_accommodation_models.id','available_accommodation_models.Capacity',DB::raw('SUM(available_accommodation_models.Capacity) as assignedCapacity')]);

                                            $assignedCapacity = isset($a->assignedCapacity) ? $a->assignedCapacity : 0;
                                            $TotalCapacity  = isset($TotaData->TotalCapacity) ? $TotaData->TotalCapacity : 0;
                                            $result[$building->BuildingName][0][$rankKey] = $assignedCapacity . '/' . $TotalCapacity;
                                        }

                                        return $accommodation;
                                    });

                                return $result;
                            }, []);


            $OccupiedBed=  AssingAccommodation::where("resort_id",$this->globalUser->resort_id)
                            ->where('emp_id','!=',0)->count();

            $TotalBed = AssingAccommodation::where("resort_id",$this->globalUser->resort_id)->count();
            $EmployeesCount =$TotalBed - $OccupiedBed;

            $AvailableAccomodation =AssingAccommodation::where("resort_id",$this->globalUser->resort_id)->where('emp_id',0)->count();


            $Totalnumberofopenrequests= MaintanaceRequest::where("resort_id",$this->globalUser->resort_id)->where('Status','pending')->count();
            $TotalnumberofHighrequests= MaintanaceRequest::where("resort_id",$this->globalUser->resort_id)->where('priority','High')->count();
            $TotalnumberofInProgressrequests= MaintanaceRequest::where("resort_id",$this->globalUser->resort_id)->where('Status','In-Progress')->count();

            $Othercounts = AvailableAccommodationModel::select(DB::RAW('SUM(CASE WHEN available_accommodation_models.blockFor = \'Female\' THEN available_accommodation_models.Capacity ELSE 0 END) as FemaleAvailableBeds'),DB::RAW('SUM(CASE WHEN available_accommodation_models.blockFor = \'Male\' THEN available_accommodation_models.Capacity ELSE 0 END) as MaleAvailableBeds'))
                                            ->where('resort_id', $this->globalUser->resort_id)
                                            ->first();

            $BedStatistics = AvailableAccommodationModel::join('assing_accommodations', 'assing_accommodations.available_a_id', '=', 'available_accommodation_models.id')
                                            ->where('available_accommodation_models.resort_id', $this->globalUser->resort_id)
                                            ->select(
                                                'available_accommodation_models.BuildingName',
                                                DB::raw('
                                                    COUNT(CASE
                                                        WHEN available_accommodation_models.blockFor = "Male" AND assing_accommodations.emp_id != 0
                                                        THEN assing_accommodations.id
                                                        ELSE NULL
                                                    END) as MaleOccupiedBeds'),
                                                DB::raw('
                                                    COUNT(CASE
                                                        WHEN available_accommodation_models.blockFor = "Male" AND assing_accommodations.emp_id = 0
                                                        THEN available_accommodation_models.id
                                                        ELSE NULL
                                                    END) as MaleAvailableBeds'),
                                                    DB::raw('
                                                    COUNT(CASE
                                                        WHEN available_accommodation_models.blockFor = "Female" AND assing_accommodations.emp_id != 0
                                                        THEN available_accommodation_models.Capacity
                                                        ELSE NULL
                                                    END) as FemaleOccupiedBeds'),
                                                DB::raw('
                                                    COUNT(CASE
                                                        WHEN available_accommodation_models.blockFor = "Female" AND assing_accommodations.emp_id = 0
                                                        THEN available_accommodation_models.id
                                                        ELSE NULL
                                                    END) as FemaleAvailableBeds'),
                                            )
                                            ->groupBy('available_accommodation_models.resort_id')
                                            ->first();

            $ResortDepartment= ResortDepartment::where("resort_id",$this->globalUser->resort_id)->get();
            $InventoryCategory = InventoryCategoryModel::where("resort_id",$this->globalUser->resort_id)->get();
            return view('resorts.Accommodation.dashboard.admindashboard',compact('InventoryCategory','ResortDepartment','BedStatistics','TotalnumberofInProgressrequests','TotalnumberofHighrequests','Totalnumberofopenrequests','EmployeesCount','AvailableAccomodation','OccupiedBed','TotalBed','buildings','page_title','Employee'));


    }
    public function HR_Dashobard(Request $request)
    {



        $page_title="Accommodation Dashboard";
        if($request->ajax())
        {


            $MaintanaceRequest = MaintanaceRequest::join("employees as t3","t3.id","maintanace_requests.Raised_By")
                                                ->join("resort_admins as t1","t1.id","t3.Admin_Parent_id")
                                                ->join("resort_departments as t4","t4.id","t3.Dept_id")
                                                ->whereNotIn('maintanace_requests.Status', ['Closed', 'On-Hold']);
                                                if( isset($request->ResortDepartment))
                                                {

                                                    $MaintanaceRequest ->where('t4.id',$request->ResortDepartment);
                                                }
            $MaintanaceRequest =  $MaintanaceRequest->leftjoin("resort_admins as t2","t2.id","maintanace_requests.Assigned_To")
                                                ->orderBy('maintanace_requests.date','desc')
                                                ->whereIn('maintanace_requests.Status',['Open','pending','In-Progress'])
                                                ->get(['t1.id as Parentid','t1.first_name','t1.last_name','t2.id as Assign_Parentid','t2.first_name as Assign_first_name','t2.last_name as Assign_last_name','maintanace_requests.*'])
                                                ->map(function ($row) {
                                                    $row->RequestedBy=$row->first_name.' '.$row->last_name;
                                                    $row->AssgingedStaff=$row->Assigned_To;
                                                    $row->Location=$row->BuilidngData->BuildingName.',Room No - '.$row->RoomNo.',Floor No -'.$row->FloorNo;
                                                    $row->Priority = $row->priority;
                                                    $row->Date =$row->created_at->format('d M Y');
                                                    $row->profileImg = Common::getResortUserPicture($row->Parentid);
                                                    $InventoryModule= InventoryModule::where('resort_id',$this->globalUser->resort_id)
                                                                                    ->where("id",$row->item_id)
                                                                                    ->first('ItemName');


                                                    if(isset($row->Assigned_To) && $row->Assigned_To != 0)
                                                    {
                                                        $emp = Common::GetEmployeeDetails($row->Assigned_To);
                                                       
                                                        $row->Assign_profileImg = Common::getResortUserPicture($emp->Parent_id);
                                                        $row->Assign_toName     = $emp->first_name.' '.$emp->last_name;
                                                    
                                                    }
                                                    $row->EffectedAmenity = isset($InventoryModule) ? ucfirst($InventoryModule->ItemName) :'';
                                                    return  $row;
                                                });
            return datatables()->of($MaintanaceRequest)
                ->addColumn('action', function ($row) 
                {
                    $id = base64_encode($row->id);
                     if($row->Status!='Open')
                     {
                            $string = '<a href="'.route('resort.accommodation.MainRequestDetails',$id ).'" class="btn-tableIcon btnIcon-skyblue mainRequetDetails" data-task_id="'.$id.'"><i class="fa-regular fa-eye"></i></a>
                                        <a href="javascript:void(0)" class="btn-tableIcon btnIcon-blue ForwardToHOD" data-bs-toggle="tooltip"
                                        data-bs-placement="bottom" title="Forward to HOD" data-req_id="'.$id.'" data-Location="'.$row->Location.'"data-EffectedAmenity="'. $row->EffectedAmenity .'"><i class="fa-solid fa-check"></i></a>
                                        <a href="javascript:void(0)" class="btn-tableIcon btnIcon-danger RejectedRequest" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Reject Request" data-req_id="'.$id.'" data-Location="'.$row->Location.'"data-EffectedAmenity="'. $row->EffectedAmenity .'"><i class="fa-solid fa-xmark"></i></a>';
                     }
                     else
                     {
                            $string= '<a href="'.route('resort.accommodation.MainRequestDetails',$id ).'" class="btn-tableIcon btnIcon-skyblue mainRequetDetails" data-task_id="'.$id.'"><i class="fa-regular fa-eye"></i></a>';
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
                    ->editColumn('Priority', function ($row) 
                    {
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
                    ->editColumn('AssgingedStaff', function ($row) 
                    {
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
                    })
                    ->rawColumns(['RequestedBy','Priority','action','AssgingedStaff','Status'])
                    ->make(true);

        }

            $Employee =Employee::join('resort_admins','resort_admins.id',"=",'employees.Admin_Parent_id')
                            ->where('employees.resort_id', $this->globalUser->resort_id)
                            ->where("employees.rank",11)
                            ->get(['employees.*','resort_admins.first_name','resort_admins.last_name']);
                            
            $buildings = BuildingModel::where("resort_id", $this->globalUser->resort_id)
                            ->get()
                            ->reduce(function ($result, $building) {
                                // Initialize the array for this building if not set
                                if (!isset($result[$building->BuildingName])) {
                                    $result[$building->BuildingName] = [];
                                }

                                // Fetch data for the current building
                                $data = AvailableAccommodationModel::join('assing_accommodations', 'assing_accommodations.available_a_id', '=', 'available_accommodation_models.id')
                                    ->where('available_accommodation_models.BuildingName', $building->id) // Filter by building ID
                                    ->where('available_accommodation_models.resort_id', $this->globalUser->resort_id)
                                    ->selectRaw("
                                        available_accommodation_models.RoomType,
                                        available_accommodation_models.BuildingName,
                                        available_accommodation_models.RoomNo,
                                        available_accommodation_models.RoomStatus,
                                        assing_accommodations.emp_id,
                                        SUM(CASE WHEN assing_accommodations.emp_id != 0 THEN 1 ELSE 0 END) as OccupiedRooms,
                                        SUM(CASE WHEN assing_accommodations.emp_id = 0 THEN 1 ELSE 0 END) as AvailableRooms,
                                        SUM(CASE WHEN assing_accommodations.emp_id != 0 THEN 1 ELSE 0 END) as OccupiedRooms,
                                        SUM(CASE WHEN available_accommodation_models.RoomStatus = 'Available' THEN 1 ELSE 0 END) as MainAvailableRooms
                                    ")
                                    ->groupBy(
                                        'available_accommodation_models.RoomType',
                                        'available_accommodation_models.BuildingName'
                                    )
                                    ->get()
                                    ->map(function ($accommodation) use ($building, &$result) {
                                        // Get additional data for floors and rooms
                                        // dd($accommodation);
                                        $buildingData = BulidngAndFloorAndRoom::where("building_id", $building->id)
                                            ->selectRaw('COUNT(distinct(Floor)) as TotalFloors, COUNT(Room) as TotalRooms')
                                            ->where('resort_id', $this->globalUser->resort_id)
                                            ->groupBy('building_id')
                                            ->first();


                                            $a = AvailableAccommodationModel::join('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
                                            ->where('available_accommodation_models.resort_id', $this->globalUser->resort_id)
                                            ->where("BuildingName", $building->id)
                                            ->where("t1.emp_id", "!=",0)
                                            // ->groupBy('t1.available_a_id')
                                            ->first(['available_accommodation_models.id','available_accommodation_models.Capacity',DB::raw('COUNT(t1.id ) as OccupiedRooms')]);
                                            $AvailableRooms=0;

                                                if( isset($a->OccupiedRooms))
                                                {
                                                    if( $a->OccupiedRooms < $a->Capacity)
                                                    {
                                                        $AvailableRooms = 1;
                                                    }
                                                
                                                    else 
                                                    {
                                                        $AvailableRooms = $a->OccupiedRooms;
                                                    }
                                                }

                                                
                                                $AvailableFloor=0;
                                                if( isset($a->OccupiedRooms))
                                                {
                                                    if( $a->OccupiedRooms < $a->Capacity)
                                                    {
                                                        $AvailableFloor = 1;
                                                    }
                                                    else
                                                    {
                                                        $AvailableFloor = $a->OccupiedRooms;
                                                    }
                                                }

                                                $MaleBeds = AvailableAccommodationModel::join('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
                                                ->where('available_accommodation_models.resort_id', $this->globalUser->resort_id)
                                                ->where("BuildingName", $building->id)
                                                ->where("t1.emp_id", 0)
                                                ->where("available_accommodation_models.blockFor",'Male')
                                                // ->groupBy('t1.available_a_id')
                                                ->first(['available_accommodation_models.id','available_accommodation_models.Capacity',DB::raw('COUNT(t1.id ) as AvailableMaleBeds')]);

                                                $AvailableMaleBeds=0;
                                                if( isset($MaleBeds->AvailableBeds))
                                                {
                                                        $AvailableMaleBeds = $MaleBeds->AvailableMaleBeds;
                                                }
                                                $FemaleBeds = AvailableAccommodationModel::join('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
                                                ->where('available_accommodation_models.resort_id', $this->globalUser->resort_id)
                                                ->where("BuildingName", $building->id)
                                                ->where("t1.emp_id", 0)
                                                ->where("available_accommodation_models.blockFor",'Female')
                                                // ->groupBy('t1.available_a_id')
                                                ->first(['available_accommodation_models.id','available_accommodation_models.Capacity',DB::raw('COUNT(t1.id ) as AvailableFemaleBeds')]);
                                                $AvailableFemaleBeds=0;
                                                if( isset($FemaleBeds->AvailableFemaleBeds))
                                                {
                                                        $AvailableFemaleBeds = $FemaleBeds->AvailableFemaleBeds;
                                                }

                                              
                                                $OccupiedMaleBeds = AvailableAccommodationModel::join('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
                                                ->where('available_accommodation_models.resort_id', $this->globalUser->resort_id)
                                                ->where("BuildingName", $building->id)
                                                ->where("t1.emp_id","!=" ,0)
                                                ->where("available_accommodation_models.blockFor",'Male')
                                                // ->groupBy('t1.available_a_id')
                                                ->first([DB::raw('COUNT(t1.id ) as OccupiedMaleBeds')]);

                                                $OccupiedMaleBedsNew=0;
                                                if( isset($OccupiedMaleBeds->OccupiedMaleBeds))
                                                {
                                                        $OccupiedMaleBedsNew =$OccupiedMaleBeds->OccupiedMaleBeds;
                                                }

                                                $OccupiedFemaleBedsnew = AvailableAccommodationModel::join('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
                                                ->where('available_accommodation_models.resort_id', $this->globalUser->resort_id)
                                                ->where("BuildingName", $building->id)
                                                ->where("t1.emp_id","!=" ,0)
                                                ->where("available_accommodation_models.blockFor",'Female')
                                                // ->groupBy('t1.available_a_id')
                                                ->first([DB::raw('COUNT(t1.id ) as OccupiedFemaleBeds')]);
                                                $OccupiedFemaleBeds=0;

                                               
                                                    if( isset($OccupiedFemaleBedsnew->OccupiedFemaleBeds))
                                                    {
                                                             $OccupiedFemaleBeds = $OccupiedFemaleBedsnew->OccupiedFemaleBeds;
                                                    }
                                             
                                    $Othercounts = AvailableAccommodationModel::where('BuildingName', $building->id)
                                            ->select(DB::RAW('SUM(CASE WHEN available_accommodation_models.blockFor = \'Female\' THEN available_accommodation_models.Capacity ELSE 0 END) as FemaleAvailableBeds'),DB::RAW('SUM(CASE WHEN available_accommodation_models.blockFor = \'Male\' THEN available_accommodation_models.Capacity ELSE 0 END) as MaleAvailableBeds'))
                                            ->where('resort_id', $this->globalUser->resort_id)
                                            ->first();


                                        if (empty($result[$building->BuildingName])) {
                                            $result[$building->BuildingName][] = [
                                                'Floor' => $AvailableFloor . '/' . ($buildingData->TotalFloors ?? 0), // Pending
                                                'Room' => $AvailableRooms . '/' . ($buildingData->TotalRooms ?? 0), // Done
                                                'Male Beds' =>  $OccupiedMaleBedsNew. '/' . ($Othercounts->MaleAvailableBeds ?? 0),
                                                'Female Beds' => $OccupiedFemaleBeds . '/' . ($Othercounts->FemaleAvailableBeds ?? 0),
                                            ];
                                        }

                                        // Update the existing array for this building
                                        $Rank = config('settings.eligibilty');
                                        if (isset($Rank[$accommodation->RoomType])) {
                                            $rankKey = $Rank[$accommodation->RoomType];

                                            $TotaData=AvailableAccommodationModel::where('available_accommodation_models.resort_id', $this->globalUser->resort_id)
                                                                            ->where("BuildingName", $building->id)
                                                                            ->where("RoomType", $accommodation->RoomType)
                                                                            ->groupBy('RoomType')
                                                                            ->first(['available_accommodation_models.id','available_accommodation_models.Capacity',DB::raw('SUM(available_accommodation_models.Capacity) as TotalCapacity')]);

                                            $a = AvailableAccommodationModel::join('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
                                            ->where('available_accommodation_models.resort_id', $this->globalUser->resort_id)
                                            ->where("BuildingName", $building->id)
                                            ->where("t1.emp_id", "!=",0)
                                            ->where("available_accommodation_models.RoomType", $accommodation->RoomType)
                                            ->groupBy('t1.available_a_id')
                                            ->first(['available_accommodation_models.id','available_accommodation_models.Capacity',DB::raw('SUM(available_accommodation_models.Capacity) as assignedCapacity')]);
                                            // dd( $a );
                                            $assignedCapacity = isset($a->assignedCapacity) ? $a->assignedCapacity : 0;
                                            $TotalCapacity  = isset($TotaData->TotalCapacity) ? $TotaData->TotalCapacity : 0;
                                            $result[$building->BuildingName][0][$rankKey] = $assignedCapacity  .'/' . $TotalCapacity;
                                        }

                                        return $accommodation;
                                    });

                                return $result;
                            }, []);
            
            $OccupiedBed=  AssingAccommodation::where("resort_id",$this->globalUser->resort_id)
                            ->where('emp_id','!=',0)->count();

            $TotalBed = AssingAccommodation::where("resort_id",$this->globalUser->resort_id)->count();
            $AvailableAccomodation =AssingAccommodation::where("resort_id",$this->globalUser->resort_id)->where('emp_id',0)->count();

            $EmployeesCount =$TotalBed - $OccupiedBed;
            $Totalnumberofopenrequests= MaintanaceRequest::where("resort_id",$this->globalUser->resort_id)->where('Status','pending')->count();
            $TotalnumberofHighrequests= MaintanaceRequest::where("resort_id",$this->globalUser->resort_id)->where('priority','High')->count();
            $TotalnumberofInProgressrequests= MaintanaceRequest::where("resort_id",$this->globalUser->resort_id)->where('Status','In-Progress')->count();

            $Othercounts = AvailableAccommodationModel::select(DB::RAW('SUM(CASE WHEN available_accommodation_models.blockFor = \'Female\' THEN available_accommodation_models.Capacity ELSE 0 END) as FemaleAvailableBeds'),DB::RAW('SUM(CASE WHEN available_accommodation_models.blockFor = \'Male\' THEN available_accommodation_models.Capacity ELSE 0 END) as MaleAvailableBeds'))
                                            ->where('resort_id', $this->globalUser->resort_id)
                                            ->first();

            $BedStatistics = AvailableAccommodationModel::join('assing_accommodations', 'assing_accommodations.available_a_id', '=', 'available_accommodation_models.id')
                                            ->where('available_accommodation_models.resort_id', $this->globalUser->resort_id)
                                            ->select(
                                                'available_accommodation_models.BuildingName',
                                                DB::raw('
                                                    COUNT(CASE
                                                        WHEN available_accommodation_models.blockFor = "Male" AND assing_accommodations.emp_id != 0
                                                        THEN assing_accommodations.id
                                                        ELSE NULL
                                                    END) as MaleOccupiedBeds'),
                                                DB::raw('
                                                    COUNT(CASE
                                                        WHEN available_accommodation_models.blockFor = "Male" AND assing_accommodations.emp_id = 0
                                                        THEN available_accommodation_models.id
                                                        ELSE NULL
                                                    END) as MaleAvailableBeds'),
                                                    DB::raw('
                                                    COUNT(CASE
                                                        WHEN available_accommodation_models.blockFor = "Female" AND assing_accommodations.emp_id != 0
                                                        THEN available_accommodation_models.Capacity
                                                        ELSE NULL
                                                    END) as FemaleOccupiedBeds'),
                                                DB::raw('
                                                    COUNT(CASE
                                                        WHEN available_accommodation_models.blockFor = "Female" AND assing_accommodations.emp_id = 0
                                                        THEN available_accommodation_models.id
                                                        ELSE NULL
                                                    END) as FemaleAvailableBeds'),
                                            )
                                            ->groupBy('available_accommodation_models.resort_id')
                                            ->first();
                                 
            $ResortDepartment= ResortDepartment::where("resort_id",$this->globalUser->resort_id)->get();
            $InventoryCategory = InventoryCategoryModel::where("resort_id",$this->globalUser->resort_id)->get();
            return view('resorts.Accommodation.dashboard.hrdashboard',compact('InventoryCategory','ResortDepartment','BedStatistics','TotalnumberofInProgressrequests','TotalnumberofHighrequests','Totalnumberofopenrequests','EmployeesCount','AvailableAccomodation','OccupiedBed','TotalBed','buildings','page_title','Employee'));
    }

    public function Hod_dashboard(Request $request)
    {

        $page_title="Accommodation Dashboard";
      
        $ResortDepartment= ResortDepartment::where("resort_id",$this->globalUser->resort_id)->get();
        $currentHod = Auth::guard('resort-admin')->user()->GetEmployee->id;
        $MaintanaceRequest = MaintanaceRequest::join("employees as t3","t3.id","maintanace_requests.Raised_By")
                                                    ->join("resort_admins as t1","t1.id","t3.Admin_Parent_id")
                                                    ->join("resort_departments as t4","t4.id","t3.Dept_id")
                                                    ->whereNotIn('maintanace_requests.Status', ['Closed', 'On-Hold'])
                                                    ->where('maintanace_requests.Assigned_To',$currentHod);
                                                    if( isset($request->ResortDepartment))
                                                    {
                                                        $MaintanaceRequest->where('maintanace_requests.Raised_By',$request->ResortDepartment);
                                                    }
                            $MaintanaceRequest =  $MaintanaceRequest->leftjoin("resort_admins as t2","t2.id","maintanace_requests.Assigned_To")
                                                ->orderBy('maintanace_requests.id','desc')
                                                ->get(['t1.id as Parentid','t1.first_name','t1.last_name','maintanace_requests.*'])
                                                ->map(function ($row) {
                                                    $row->RequestedBy=$row->first_name.' '.$row->last_name;
                                                    $row->AssgingedStaff=$row->Assigned_To;
                                                    $row->Location=$row->BuilidngData->BuildingName.',Room No - '.$row->RoomNo.',Floor No -'.$row->FloorNo;
                                                    $row->Priority = $row->priority;
                                                    $row->Date =$row->created_at->format('d M Y');
                                                    $row->profileImg = Common::getResortUserPicture($row->Parentid);
                                                    $InventoryModule = InventoryModule::where('resort_id',$this->globalUser->resort_id)
                                                                                    ->where("id",$row->item_id)
                                                                                    ->first('ItemName');
                                                    if(isset($row->Assigned_To))
                                                    {
                                                        $emp = Common::GetEmployeeDetails($row->Assigned_To);

                                                        $row->Assign_profileImg = Common::getResortUserPicture($emp->Parent_id);
                                                        $row->Assign_toName     = $emp->first_name.' '.$row->last_name;
                                                    }
                                                    $row->EffectedAmenity = ucfirst($InventoryModule->ItemName);
                                                    return  $row;
                                                });
                                                if($request->ajax())
                                                {             return datatables()->of($MaintanaceRequest)
                            ->addColumn('action', function ($row) {
                    $id = base64_encode($row->id);
                   

                    return     $string ='<a href="javascript:void(0)" class="btn-tableIcon btnIcon-blue ForwardToHOD" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Assign to Employee" data-req_id="'.$id.'" data-Location="'.$row->Location.'"data-EffectedAmenity="'. $row->EffectedAmenity .'"><i class=" fa-solid fa-share"></i></a>
                            <a target="_blank" href="'.route('resort.accommodation.HODMainRequestDetails',$id ).'" class="btn-tableIcon btnIcon-skyblue mainRequetDetails" data-task_id="'.$id.'"><i class="fa-regular fa-eye"></i></a>';
                    
                  
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

        $Totalnumberofopenrequests= MaintanaceRequest::join("employees as t3","t3.id","maintanace_requests.Raised_By")
                                                ->join("resort_admins as t1","t1.id","t3.Admin_Parent_id")
                                                ->join("resort_departments as t4","t4.id","t3.Dept_id")
                                                ->whereIn('maintanace_requests.Status', ['pending','Open'])
                                                ->whereIn('t3.id',$this->underEmp_id)
                                                ->where("t3.resort_id",$this->globalUser->resort_id)
                                                ->count();
        $TotalnumberofHighrequests=MaintanaceRequest::join("employees as t3","t3.id","maintanace_requests.Raised_By")
                                                    ->join("resort_admins as t1","t1.id","t3.Admin_Parent_id")
                                                    ->join("resort_departments as t4","t4.id","t3.Dept_id")
                                                    ->whereIn('maintanace_requests.priority', ['High'])
                                                    ->whereIn('t3.id',$this->underEmp_id)
                                                    ->where("t3.resort_id",$this->globalUser->resort_id)
                                                    ->count();

        $TotalnumberofInProgressrequests= MaintanaceRequest::join("employees as t3","t3.id","maintanace_requests.Raised_By")
                                                    ->join("resort_admins as t1","t1.id","t3.Admin_Parent_id")
                                                    ->join("resort_departments as t4","t4.id","t3.Dept_id")
                                                    ->whereIn('maintanace_requests.Status', ['In-Progress'])
                                                    ->where("t3.resort_id",$this->globalUser->resort_id)
                                                    ->whereIn('t3.id',$this->underEmp_id)
                                                    ->count();

        $Employee =Employee::join('resort_admins','resort_admins.id',"=",'employees.Admin_Parent_id')
                                ->where('employees.resort_id', $this->globalUser->resort_id)
                                ->whereIn('employees.id',$this->underEmp_id)
                                ->get(['employees.*','resort_admins.first_name','resort_admins.last_name']);
        return view('resorts.Accommodation.dashboard.hoddashboard',compact('Employee','Totalnumberofopenrequests','TotalnumberofHighrequests','TotalnumberofInProgressrequests','page_title','ResortDepartment'));
    }


    public function AccomComplitionRequest(Request $request)
    {
    
                $EscalationDay                                  =   EscalationDay::where('resort_id', $this->globalUser->resort_id)->first();
                $inventoryItems                                 =   InventoryModule::where('resort_id', $this->globalUser->resort_id)->pluck('ItemName', 'id');
                $employee_id                                    =   $this->globalUser->GetEmployee->id;
                $MaintanaceRequest =  MaintanaceRequest::join("employees as t3", "t3.id", "=", "maintanace_requests.Raised_By")
                                                        ->join("resort_admins as t1", "t1.id", "=", "t3.Admin_Parent_id")
                                                        ->join("child_maintanance_requests as cmr", function ($join) use ($employee_id) {
                                                            $join->on("cmr.maintanance_request_id", "=", "maintanace_requests.id")
                                                                ->where("cmr.ApprovedBy", "=", $employee_id);
                                                        })
                                                        ->join("child_approved_maintanace_requests as camr", function ($join) use ($employee_id) 
                                                        {
                                                            $join->on("camr.maintanance_request_id", "=", "maintanace_requests.id")
                                                                ->where("camr.ApprovedBy", "=", $employee_id)
                                                                ->where("camr.Status", "=", "Assinged"); 
                                                        })
                                                        ->whereIn("maintanace_requests.Status",['ResolvedAwaiting'])
                                                        ->where("maintanace_requests.resort_id", $this->globalUser->resort_id)
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

                                                        ])
                                                        ->map(function ($row) use ($EscalationDay, $inventoryItems) {
                                                                return $this->formatMaintenanceRow($row, $EscalationDay, $inventoryItems);
                                                        });

                                    return datatables()->of($MaintanaceRequest)
                                    ->setRowClass(function ($row) {
                                        return isset($row->EscalationTimeOver) ? 'bg-escalated' : '';
                                    })
                                    ->addColumn('action', function ($row) 
                                    {
                                        $id = base64_encode($row->id);
                                        $string = '<a href="'.route('resort.accommodation.MainRequestDetails',$id ).'" class="btn-tableIcon btnIcon-skyblue mainRequetDetails" data-task_id="'.$id.'"><i class="fa-regular fa-eye"></i></a>
                                        <a href="javascript:void(0)" class="btn-tableIcon btnIcon-blue ForwardToEmployee" title="Forward to Employee" data-request_id="'.$id.'" data-child_appr_maint_req_id = "'.$row->child_appr_maint_req_id.'"><i class="fa-solid fa-check"></i></a>';
                                        return $string;
                                        })
                                                ->editColumn('RequestedBy', function ($row) {
                                                  return   '<div class="tableUser-block">
                                                                        <div class="img-circle"><img src="'.$row->Requested_Hod_profileImg.'" alt="user">
                                                                        </div>
                                                                        <span class="userApplicants-btn">'.$row->Requested_Hod_Name.'</span>
                                                                    </div>';
                            
                                                })
                                                ->editColumn('EmployeeName', function ($row) {
                                                    return   '<div class="tableUser-block">
                                                                <div class="img-circle"><img src="'.$row->profileImg.'" alt="user">
                                                                </div>
                                                                <span class="userApplicants-btn">'.$row->RequestedForMaintance.'</span>
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
                                               
                                                ->editColumn('Date', function ($row) {
                                                    return $row->Date;
                                                })
                                                ->editColumn('created_at', function ($row) {
                                                    return $row->created_at;
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
                                              ->rawColumns(['RequestedBy','EmployeeName','Priority','action','AssgingedStaff','Status','created_at'])
                                                ->make(true);
                                  
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
        $row->EscalationTimeOver                            =   ($daysSinceRequest > ($EscalationDay->EscalationDay ?? 0)) ? '#ffb4b4' :null;
        // **Set Profile Image**
        $row->profileImg                                    =   Common::getResortUserPicture($row->Parentid);
        $row->Image                                         =  URL::asset($row->Image); 
        $row->Completed_Image                               =  URL::asset($row->Completed_Image); 
        // **Get Inventory Item Name**
        $row->EffectedAmenity                               =   ucfirst($inventoryItems[$row->item_id] ?? 'N/A');

        // **Assigned Staff Details**
        if (!empty($row->Raised_By)) 
        { 
            $emp                                            =   Common::GetEmployeeDetails($row->Raised_By);
            $row->Assign_profileImg                         =   Common::getResortUserPicture($emp->Parent_id);
            $row->RequestedForMaintance                     =   $emp->first_name . ' ' . $emp->last_name;
        }

        $row->Priority                                      = $row->priority;

        $Request_id = ChildApprovedMaintanaceRequests::where("child_maintanance_request_id",$row->child_maint_req_id)
                                                        ->where("rank",11)->where('Status','Approved')
                                                        ->first('ApprovedBy');
  
        if(isset($Request_id->ApprovedBy))
        {

            $hod_request                                            =   Common::GetEmployeeDetails($Request_id->ApprovedBy);
            $row->Requested_Hod_profileImg                  =   Common::getResortUserPicture($hod_request->Parent_id);
            $row->Requested_Hod_Name                        =   $hod_request->first_name . ' ' . $hod_request->last_name;

        }


        return $row;
    }
    public function Aminities(Request $request)
    {
        if($request->ajax())
        {
            $InventoryCategory = $request->InventoryCategory;
            $InventoryModule = AvailableAccommodationModel::join('available_accommodation_inv_items as t1', 't1.Available_Acc_id', '=', 'available_accommodation_models.id')
                                                            ->join('inventory_modules as t2', 't2.id', '=', 't1.Item_id')
                                                            ->join('building_models as t3', 't3.id', '=', 'available_accommodation_models.BuildingName');
                                                            if(isset($InventoryCategory))
                                                            {
                                                                $InventoryModule->where('t2.Inv_Cat_id',$InventoryCategory);
                                                            }
            $InventoryModule = $InventoryModule->join('assing_accommodations as t4', 't4.available_a_id', '=', 'available_accommodation_models.id')
                                                ->leftJoin('employees as t5', 't5.id', '=', 't4.emp_id')
                                                ->leftJoin('resort_admins as t6', 't6.id', '=', 't5.Admin_Parent_id')
                                                ->where('available_accommodation_models.resort_id', $this->globalUser->resort_id)
                                                ->orderBy('t2.ItemName', 'asc')
                                                ->get([
                                                                't4.emp_id',
                                                                't6.id as Parent_id',
                                                                't6.first_name',
                                                                't6.last_name',
                                                                't2.ItemName',
                                                                't2.ItemCode',
                                                                't2.Inv_Cat_id as Category',
                                                                't2.Occupied',
                                                                't1.id',
                                                                't3.BuildingName as Bname',
                                                                'available_accommodation_models.RoomNo',
                                                                'available_accommodation_models.Floor as FloorNo',
                                                                'available_accommodation_models.id as available_a_id',
                                                                'available_accommodation_models.RoomType'
                                                ])
                                            ->map(function ($i)
                                            {
                                                $i->available_a_id= base64_encode($i->available_a_id);
                                                $i->RoomType_id= base64_encode($i->RoomType);
                                                $i->Occupied = isset($i->Occupied) ? $i->Occupied : 0;
                                                $i->Location = $i->Bname . ', R No - ' . $i->RoomNo . ', F No - ' . $i->FloorNo;
                                                $i->ItemName = $i->ItemName . '/' . $i->ItemCode;
                                                $i->EmployeeName = ($i->emp_id != 0) ? ucfirst($i->first_name . ' ' . $i->last_name) : '-';
                                                $i->profileImg = ($i->emp_id != 0) ? Common::getResortUserPicture($i->Parent_id) : 'No';
                                                return $i;
                                            });

            return datatables()->of($InventoryModule)
                        ->addColumn('action', function ($row) {
                $id = base64_encode($row->id);
                return '
                    <div class="d-flex align-items-center">
                        <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" data-cat-id="' . e($id) . '">
                            <img src="' . asset("resorts_assets/images/edit.svg") . '" alt="Edit" class="img-fluid">
                        </a>
                        <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-cat-id="' . e($id) . '">
                            <img src="' . asset("resorts_assets/images/trash-red.svg") . '" alt="Delete" class="img-fluid">
                        </a>
                    </div>';
            })
            ->editColumn('ItemName', function ($row) {
                return e($row->ItemName);
            })
            ->editColumn('Occupied', function ($row) {
                return isset($row->Occupied) ?  $row->Occupied: 0 ;
            })
            ->editColumn('Location', function ($row) {
                return e($row->Location);
            })
            ->editColumn('Employee', function ($row) {
                if($row->emp_id !=0)
                {
                    return  '<div class="tableUser-block">
                                <div class="img-circle"><img src="'.$row->profileImg.'" alt="user"></div>
                                <span class="userApplicants-btn">'.$row->EmployeeName.'</span>
                            </div>';
                }
                else
                {
                    return '<a href="javascript:void(0)" class="btn btn-themeSkyblueLight AssingToRoom btn-small" data-id="'.$row->available_a_id.'" data-roomtype="'.$row->RoomType_id.'">Assign</a>';
                }
            })
            ->rawColumns(['ItemName','Occupied','Location','Employee'])
            ->make(true);

        }


    }

    public function HODtableassignTask(Request $request)
    {
        if($request->ajax())
        {
                $MaintanaceRequest = MaintanaceRequest::join("employees as t3","t3.id","maintanace_requests.Raised_By")
                                                    ->join("resort_admins as t1","t1.id","t3.Admin_Parent_id")
                                                    ->join("resort_departments as t4","t4.id","t3.Dept_id")
                                                    ->whereNotIn('maintanace_requests.Status', ['Closed', 'On-Hold']);
                                                    if( isset($request->ResortDepartment))
                                                    {
                                                        $MaintanaceRequest ->where('t4.id',$request->ResortDepartment);
                                                    }
                                                    $MaintanaceRequest =  $MaintanaceRequest->leftjoin("resort_admins as t2","t2.id","maintanace_requests.Assigned_To")
                                                    ->orderBy('maintanace_requests.date','desc')
                                                    ->where('maintanace_requests.Assigned_To',"!=",null)
                                                    ->where('maintanace_requests.status','Assigned')
                                                    ->get(['t1.id as Parentid','t1.first_name','t1.last_name','t2.id as Assign_Parentid','t2.first_name as Assign_first_name','t2.last_name as Assign_last_name','maintanace_requests.*'])
                                                    ->map(function ($row) {
                                                        $row->RequestedBy=$row->first_name.' '.$row->last_name;
                                                        $row->AssgingedStaff=$row->Assigned_To;
                                                        $row->Location=$row->BuilidngData->BuildingName.',Room No - '.$row->RoomNo.',Floor No -'.$row->FloorNo;
                                                        $row->Priority = $row->priority;
                                                        $row->Date =$row->created_at->format('d M Y');
                                                        $row->profileImg = Common::getResortUserPicture($row->Parentid);
                                                        $InventoryModule= InventoryModule::where('resort_id',$this->globalUser->resort_id)
                                                                                        ->where("id",$row->item_id)
                                                                                        ->first('ItemName');
                                                        if(isset($row->Assigned_To))
                                                        {
                                                            $emp = Common::GetEmployeeDetails($row->Assigned_To);
                                                            if($emp)
                                                            {
                                                                $row->Assign_profileImg = Common::getResortUserPicture($emp->Parent_id);
                                                                $row->Assign_toName     = $emp->first_name.' '.$row->last_name;
                                                            }
                                                                
                                                        }
                                                        $row->EffectedAmenity = ucfirst($InventoryModule->ItemName);
                                                        return  $row;
                                                    });
            return datatables()->of($MaintanaceRequest)
                                ->addColumn('action', function ($row)
                        {
                        $id = base64_encode($row->id);

                            $string= '<a target="_blank" href="'.route('resort.accommodation.HODMainRequestDetails',$id ).'" class="btn-tableIcon btnIcon-skyblue mainRequetDetails" data-task_id="'.$id.'"><i class="fa-regular fa-eye"></i></a>';

                        return $string;
                        })
                        ->editColumn('DescriptionOfIssue', function ($row) {
                            return  $row->descriptionIssues;
                        })
                        ->editColumn('Location', function ($row) {
                            return e($row->Location);
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

                        ->rawColumns(['RequestedBy','Priority','action','AssgingedStaff','Status'])
                        ->make(true);

        }
        // show Maintanance Request Details
    }

    public function MainRequestForwordToEmp(Request $request)
    {
       

        DB::beginTransaction();  
        try {
            $employee_id                                    =   $this->globalUser->GetEmployee->id;
            $requestId                                      =   base64_decode($request->input('request_id'));
            $childApprMaintReqId                            =   $request->input('child_appr_maint_req_id');
            $maintanance                                    =   MaintanaceRequest::find($requestId);
           
            if (!$maintanance) {
                return response()->json(['success' => false, 'message' => 'Maintenance request not found'], 404);
            }

                $existingChildApprRequest                           =   ChildApprovedMaintanaceRequests::where('id', $childApprMaintReqId)
                                                                            ->where('maintanance_request_id', $requestId)
                                                                            ->where('Status', 'Assinged')
                                                                            ->where('resort_id', $this->globalUser->resort_id)
                                                                            ->first();
                if (!$existingChildApprRequest) {
                    
                    return response()->json(['success' => false, 'message' => 'This maintenance request is already Approved'], 400);
                }

                $updatedRows                                    =   ChildApprovedMaintanaceRequests::where('id', $childApprMaintReqId)
                                                                        ->where('maintanance_request_id', $maintanance->id)
                                                                        ->where('Status', 'Assinged')
                                                                        ->update([
                                                                            'Status'     => "Approved",
                                                                        ]);

                // If no records were updated, return an error
                if (!$updatedRows) {
                    return response()->json(['success' => false, 'message' => 'No pending maintenance request found to update'], 400);
                }
            
            DB::commit(); 

            return response()->json(['success' =>true,'message'=>'Request sent to Employee Successfully ' ], 200);
         } 
        catch (\Exception $e) 
        {
             DB::rollBack(); // Rollback if any error occurs
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

}
