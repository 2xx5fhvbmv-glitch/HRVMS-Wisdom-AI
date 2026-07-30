<?php

namespace App\Http\Controllers\Resorts\Accommodation;

use App\Models\AvailableAccommodationModel;
use DB;
use URL;
use Auth;
use Carbon\Carbon;
use App\Helpers\Common;
use App\Helpers\StorageHelper;
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
        if(!$this->resort) return;
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
                                                if( $request->filled('ResortDepartment'))
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
                                                    $row->Location=optional($row->BuilidngData)->BuildingName . (!empty($row->RoomNo) ? ', Room No - '.$row->RoomNo : '') . (!empty($row->FloorNo) ? ', Floor No - '.$row->FloorNo : '');
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
                                                        $row->Assign_toName     = $emp->first_name.' '.$emp->last_name;
                                                    }
                                                    $row->EffectedAmenity = $InventoryModule ? ucfirst($InventoryModule->ItemName) : 'N/A';
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
            $EmployeesCount = Employee::where('resort_id', $this->globalUser->resort_id)->where('status', 'Active')->count();

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
                                                DB::raw('COUNT(CASE WHEN available_accommodation_models.blockFor = "Male" AND assing_accommodations.emp_id != 0 THEN 1 END) as MaleOccupiedBeds'),
                                                DB::raw('COUNT(CASE WHEN available_accommodation_models.blockFor = "Male" AND assing_accommodations.emp_id = 0 THEN 1 END) as MaleAvailableBeds'),
                                                DB::raw('COUNT(CASE WHEN available_accommodation_models.blockFor = "Female" AND assing_accommodations.emp_id != 0 THEN 1 END) as FemaleOccupiedBeds'),
                                                DB::raw('COUNT(CASE WHEN available_accommodation_models.blockFor = "Female" AND assing_accommodations.emp_id = 0 THEN 1 END) as FemaleAvailableBeds')
                                            )
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
                                                ->where('maintanace_requests.resort_id', $this->globalUser->resort_id)
                                                ->whereNotIn('maintanace_requests.Status', ['Closed', 'On-Hold']);
                                                if( $request->filled('ResortDepartment'))
                                                {
                                                    $MaintanaceRequest ->where('t4.id',$request->ResortDepartment);
                                                }
            $MaintanaceRequest =  $MaintanaceRequest->leftjoin("resort_admins as t2","t2.id","maintanace_requests.Assigned_To")
                                                ->orderBy('maintanace_requests.date','desc')
                                                ->whereIn('maintanace_requests.Status',['Open','pending','Pending','In-Progress'])
                                                ->get(['t1.id as Parentid','t1.first_name','t1.last_name','t2.id as Assign_Parentid','t2.first_name as Assign_first_name','t2.last_name as Assign_last_name','maintanace_requests.*'])
                                                ->map(function ($row) {
                                                    $row->RequestedBy=$row->first_name.' '.$row->last_name;
                                                    $row->AssgingedStaff=$row->Assigned_To;
                                                    $row->Location=optional($row->BuilidngData)->BuildingName . (!empty($row->RoomNo) ? ', Room No - '.$row->RoomNo : '') . (!empty($row->FloorNo) ? ', Floor No - '.$row->FloorNo : '');
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
                                        data-bs-placement="bottom" title="Approve & Forward" data-req_id="'.$id.'" data-Location="'.e($row->Location).'" data-EffectedAmenity="'.e($row->EffectedAmenity).'" data-description="'.e($row->descriptionIssues).'"><i class="fa-solid fa-check"></i></a>
                                        <a href="javascript:void(0)" class="btn-tableIcon btnIcon-danger RejectedRequest" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Reject Request" data-req_id="'.$id.'" data-Location="'.e($row->Location).'" data-EffectedAmenity="'.e($row->EffectedAmenity).'" data-description="'.e($row->descriptionIssues).'"><i class="fa-solid fa-xmark"></i></a>';
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

            $Employee = Employee::join('resort_admins','resort_admins.id',"=",'employees.Admin_Parent_id')
                            ->join('resort_departments as rd', 'rd.id', '=', 'employees.Dept_id')
                            ->where('employees.resort_id', $this->globalUser->resort_id)
                            ->where(function($q) {
                                $q->where('rd.name', 'like', '%engineer%')
                                  ->orWhere('rd.name', 'like', '%maintenance%')
                                  ->orWhere('rd.name', 'like', '%technical%');
                            })
                            ->whereIn('employees.rank', [1, 2]) // EXCOM or HOD
                            ->where('employees.status', 'Active')
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

            $EmployeesCount = Employee::where('resort_id', $this->globalUser->resort_id)->where('status', 'Active')->count();
            $Totalnumberofopenrequests= MaintanaceRequest::where("resort_id",$this->globalUser->resort_id)->where('Status','pending')->count();
            $TotalnumberofHighrequests= MaintanaceRequest::where("resort_id",$this->globalUser->resort_id)->where('priority','High')->count();
            $TotalnumberofInProgressrequests= MaintanaceRequest::where("resort_id",$this->globalUser->resort_id)->where('Status','In-Progress')->count();

            $Othercounts = AvailableAccommodationModel::select(DB::RAW('SUM(CASE WHEN available_accommodation_models.blockFor = \'Female\' THEN available_accommodation_models.Capacity ELSE 0 END) as FemaleAvailableBeds'),DB::RAW('SUM(CASE WHEN available_accommodation_models.blockFor = \'Male\' THEN available_accommodation_models.Capacity ELSE 0 END) as MaleAvailableBeds'))
                                            ->where('resort_id', $this->globalUser->resort_id)
                                            ->first();

            $BedStatistics = AvailableAccommodationModel::join('assing_accommodations', 'assing_accommodations.available_a_id', '=', 'available_accommodation_models.id')
                                            ->where('available_accommodation_models.resort_id', $this->globalUser->resort_id)
                                            ->select(
                                                DB::raw('COUNT(CASE WHEN available_accommodation_models.blockFor = "Male" AND assing_accommodations.emp_id != 0 THEN 1 END) as MaleOccupiedBeds'),
                                                DB::raw('COUNT(CASE WHEN available_accommodation_models.blockFor = "Male" AND assing_accommodations.emp_id = 0 THEN 1 END) as MaleAvailableBeds'),
                                                DB::raw('COUNT(CASE WHEN available_accommodation_models.blockFor = "Female" AND assing_accommodations.emp_id != 0 THEN 1 END) as FemaleOccupiedBeds'),
                                                DB::raw('COUNT(CASE WHEN available_accommodation_models.blockFor = "Female" AND assing_accommodations.emp_id = 0 THEN 1 END) as FemaleAvailableBeds')
                                            )
                                            ->first();
                                 
            $ResortDepartment= ResortDepartment::where("resort_id",$this->globalUser->resort_id)->get();
            $InventoryCategory = InventoryCategoryModel::where("resort_id",$this->globalUser->resort_id)->get();
            $accommodationInsights = $this->getCachedAccommodationInsights($this->globalUser->resort_id);
            return view('resorts.Accommodation.dashboard.hrdashboard',compact('InventoryCategory','ResortDepartment','BedStatistics','TotalnumberofInProgressrequests','TotalnumberofHighrequests','Totalnumberofopenrequests','EmployeesCount','AvailableAccomodation','OccupiedBed','TotalBed','buildings','page_title','Employee','accommodationInsights'));
    }

    /**
     * Cached wrapper around buildAccommodationInsights() with a manual 2-day
     * refresh, mirroring the TA/Leave/Payroll/Learning dashboards. Cached per
     * resort; "Regenerate" loads ?regenerate_insights=1 and recomputes only once
     * the 48h cooldown has elapsed. Returns a '_meta' key for the card header.
     */
    private function getCachedAccommodationInsights($resortId): array
    {
        $cooldownHours = 48;
        $cacheKey = 'accommodation_insights:' . $resortId;
        $now = \Carbon\Carbon::now();

        $cached = \Cache::get($cacheKey);
        $regenerate = request()->boolean('regenerate_insights');

        $stale = !is_array($cached) || empty($cached['generated_at']);
        if (!$stale) {
            $generatedAt = \Carbon\Carbon::parse($cached['generated_at']);
            if ($regenerate && $generatedAt->diffInHours($now) >= $cooldownHours) {
                $stale = true;
            }
        }

        if ($stale) {
            $cached = [
                'insights'     => $this->buildAccommodationInsights($resortId),
                'generated_at' => $now->toIso8601String(),
            ];
            \Cache::put($cacheKey, $cached, $now->copy()->addDays(30));
        }

        $generatedAt = \Carbon\Carbon::parse($cached['generated_at']);
        $insights = $cached['insights'];
        $insights['_meta'] = [
            'generated_at'   => $generatedAt,
            'can_regenerate' => $generatedAt->diffInHours($now) >= $cooldownHours,
            'next_available' => $generatedAt->copy()->addHours($cooldownHours),
        ];

        return $insights;
    }

    /**
     * Compute the four Accommodation AI-insight cards for a resort:
     *   1. maintenance  Maintenance SLA & backlog (open/closed, priority, resolution, aging)
     *   2. occupancy    Bed & room occupancy (% occupied, male/female split, top building)
     *   3. hotspots     Maintenance hotspots (buildings / inventory items by request volume)
     *   4. demand       Vacancy vs demand (vacant beds by gender vs incoming new hires)
     * Each card is wrapped so one failing query degrades just that card; the
     * deterministic numbers are then narrated by the FastAPI LLM (best-effort).
     */
    private function buildAccommodationInsights($resortId): array
    {
        $insights = [
            'maintenance' => ['title' => 'Maintenance SLA & Backlog', 'body' => 'No maintenance requests logged yet.'],
            'occupancy'   => ['title' => 'Bed & Room Occupancy',      'body' => 'No beds configured yet.'],
            'hotspots'    => ['title' => 'Maintenance Hotspots',      'body' => 'No maintenance requests logged yet.'],
            'demand'      => ['title' => 'Vacancy vs Demand',         'body' => 'No bed or hiring data yet to compare.'],
        ];

        $openStatuses = ['Open', 'pending', 'On-Hold', 'In-Progress', 'Assigned'];

        // --- Card 1: Maintenance SLA & backlog ---------------------------------
        try {
            $byStatus = MaintanaceRequest::where('resort_id', $resortId)
                ->select('Status', DB::raw('COUNT(*) as c'))->groupBy('Status')->pluck('c', 'Status');
            $total = (int) $byStatus->sum();
            if ($total > 0) {
                $closed = (int) ($byStatus['Closed'] ?? 0);
                $open = $total - $closed;
                $byPriority = MaintanaceRequest::where('resort_id', $resortId)
                    ->select('priority', DB::raw('COUNT(*) as c'))->groupBy('priority')->pluck('c', 'priority');
                $high = (int) ($byPriority['High'] ?? 0);
                $med  = (int) ($byPriority['Medium'] ?? 0);
                $low  = (int) ($byPriority['Low'] ?? 0);
                $avgDays = MaintanaceRequest::where('resort_id', $resortId)->where('Status', 'Closed')
                    ->whereNotNull('date')->value(DB::raw('AVG(DATEDIFF(updated_at, `date`))'));
                $avgDays = $avgDays !== null ? round((float) $avgDays, 1) : null;
                $aging = MaintanaceRequest::where('resort_id', $resortId)
                    ->whereIn('Status', $openStatuses)
                    ->whereDate('date', '<', \Carbon\Carbon::now()->subDays(7)->toDateString())->count();
                $resTxt = $avgDays === null ? 'no closed requests yet' : 'avg resolution ' . $avgDays . ' days';
                $insights['maintenance']['body'] = $open . ' open / ' . $closed . ' closed of ' . $total . ' requests; ' . $high . ' high-priority; ' . $resTxt . '; ' . $aging . ' aging (>7 days).';
                $insights['maintenance']['details'] = [
                    'total' => $total, 'open' => $open, 'closed' => $closed,
                    'by_status' => $byStatus->toArray(),
                    'priority' => ['High' => $high, 'Medium' => $med, 'Low' => $low],
                    'avg_resolution_days' => $avgDays, 'aging' => (int) $aging,
                ];
            }
        } catch (\Throwable $e) {}

        // --- Card 2: Bed & room occupancy --------------------------------------
        try {
            $bs = AvailableAccommodationModel::join('assing_accommodations as a', 'a.available_a_id', '=', 'available_accommodation_models.id')
                ->where('available_accommodation_models.resort_id', $resortId)
                ->select(
                    DB::raw('COUNT(CASE WHEN available_accommodation_models.blockFor = "Male"   AND a.emp_id != 0 THEN 1 END) as MaleOccupied'),
                    DB::raw('COUNT(CASE WHEN available_accommodation_models.blockFor = "Male"   AND a.emp_id = 0 THEN 1 END) as MaleVacant'),
                    DB::raw('COUNT(CASE WHEN available_accommodation_models.blockFor = "Female" AND a.emp_id != 0 THEN 1 END) as FemaleOccupied'),
                    DB::raw('COUNT(CASE WHEN available_accommodation_models.blockFor = "Female" AND a.emp_id = 0 THEN 1 END) as FemaleVacant')
                )->first();
            $mo = (int) ($bs->MaleOccupied ?? 0); $mv = (int) ($bs->MaleVacant ?? 0);
            $fo = (int) ($bs->FemaleOccupied ?? 0); $fv = (int) ($bs->FemaleVacant ?? 0);
            $occupied = $mo + $fo; $totalBeds = $mo + $mv + $fo + $fv;
            if ($totalBeds > 0) {
                $occPct = round($occupied / $totalBeds * 100, 1);
                $malePct = ($mo + $mv) > 0 ? round($mo / ($mo + $mv) * 100, 1) : 0;
                $femalePct = ($fo + $fv) > 0 ? round($fo / ($fo + $fv) * 100, 1) : 0;
                // Per-building occupancy (BuildingName stores the building id).
                $byBuilding = AvailableAccommodationModel::join('assing_accommodations as a', 'a.available_a_id', '=', 'available_accommodation_models.id')
                    ->leftJoin('building_models as b', 'b.id', '=', 'available_accommodation_models.BuildingName')
                    ->where('available_accommodation_models.resort_id', $resortId)
                    ->groupBy('available_accommodation_models.BuildingName', 'b.BuildingName')
                    ->select(DB::raw("COALESCE(b.BuildingName,'Unnamed') as bname"),
                             DB::raw('COUNT(*) as total'),
                             DB::raw('COUNT(CASE WHEN a.emp_id != 0 THEN 1 END) as occupied'))
                    ->get()
                    ->map(fn ($r) => ['building' => $r->bname, 'total' => (int) $r->total, 'occupied' => (int) $r->occupied,
                                      'pct' => (int) $r->total > 0 ? round((int) $r->occupied / (int) $r->total * 100, 1) : 0])
                    ->sortByDesc('pct')->values()->all();
                $top = $byBuilding[0] ?? null;
                $topTxt = $top ? ' ' . $top['building'] . ' is nearest capacity at ' . $top['pct'] . '%.' : '';
                $insights['occupancy']['body'] = $occPct . '% bed occupancy (' . $occupied . ' of ' . $totalBeds . '); male ' . $malePct . '%, female ' . $femalePct . '%.' . $topTxt;
                $insights['occupancy']['details'] = [
                    'occupancy_pct' => $occPct, 'occupied' => $occupied, 'total' => $totalBeds,
                    'male' => ['occupied' => $mo, 'vacant' => $mv, 'pct' => $malePct],
                    'female' => ['occupied' => $fo, 'vacant' => $fv, 'pct' => $femalePct],
                    'buildings' => array_slice($byBuilding, 0, 15),
                ];
            }
        } catch (\Throwable $e) {}

        // --- Card 3: Maintenance hotspots --------------------------------------
        try {
            $byBuilding = MaintanaceRequest::leftJoin('building_models as b', 'b.id', '=', 'maintanace_requests.building_id')
                ->where('maintanace_requests.resort_id', $resortId)
                ->groupBy('b.BuildingName')
                ->select(DB::raw("COALESCE(b.BuildingName,'Unknown') as name"), DB::raw('COUNT(*) as c'))
                ->orderByDesc('c')->limit(10)->get()
                ->map(fn ($r) => ['building' => $r->name, 'count' => (int) $r->c])->all();
            $byItem = MaintanaceRequest::leftJoin('inventory_modules as i', 'i.id', '=', 'maintanace_requests.item_id')
                ->where('maintanace_requests.resort_id', $resortId)
                ->groupBy('i.ItemName')
                ->select(DB::raw("COALESCE(i.ItemName,'Unknown') as name"), DB::raw('COUNT(*) as c'))
                ->orderByDesc('c')->limit(10)->get()
                ->map(fn ($r) => ['item' => $r->name, 'count' => (int) $r->c])->all();
            if (!empty($byBuilding) || !empty($byItem)) {
                $tb = $byBuilding[0] ?? null; $ti = $byItem[0] ?? null;
                $parts = [];
                if ($tb) $parts[] = $tb['building'] . ' leads with ' . $tb['count'] . ' requests';
                if ($ti) $parts[] = $ti['item'] . ' is the most-reported item (' . $ti['count'] . ')';
                $insights['hotspots']['body'] = ucfirst(implode('; ', $parts)) . '.';
                $insights['hotspots']['details'] = ['buildings' => $byBuilding, 'items' => $byItem];
            }
        } catch (\Throwable $e) {}

        // --- Card 4: Vacancy vs demand -----------------------------------------
        try {
            // Vacant beds by gender (re-uses the occupancy figures if present).
            $bs = AvailableAccommodationModel::join('assing_accommodations as a', 'a.available_a_id', '=', 'available_accommodation_models.id')
                ->where('available_accommodation_models.resort_id', $resortId)
                ->select(
                    DB::raw('COUNT(CASE WHEN available_accommodation_models.blockFor = "Male"   AND a.emp_id = 0 THEN 1 END) as MaleVacant'),
                    DB::raw('COUNT(CASE WHEN available_accommodation_models.blockFor = "Female" AND a.emp_id = 0 THEN 1 END) as FemaleVacant')
                )->first();
            $mv = (int) ($bs->MaleVacant ?? 0); $fv = (int) ($bs->FemaleVacant ?? 0);
            // Incoming new hires by gender (accepted contracts, gender from applicant form).
            $incoming = DB::table('applicant_offer_contracts as oc')
                ->join('applicant_form_data as a', 'a.id', '=', 'oc.applicant_id')
                ->where('oc.resort_id', $resortId)->where('oc.type', 'contract')->where('oc.status', 'Accepted')
                ->select(DB::raw("LOWER(a.gender) as g"), DB::raw('COUNT(DISTINCT a.id) as c'))
                ->groupBy('g')->pluck('c', 'g');
            $im = (int) ($incoming['male'] ?? 0); $if = (int) ($incoming['female'] ?? 0);
            // Current unhoused active staff (no bed assignment), overall.
            $housedIds = AssingAccommodation::where('resort_id', $resortId)->where('emp_id', '!=', 0)->pluck('emp_id')->all();
            $unhoused = Employee::where('resort_id', $resortId)->where('status', 'Active')
                ->when(!empty($housedIds), fn ($q) => $q->whereNotIn('id', $housedIds))->count();
            if ($mv + $fv + $im + $if > 0) {
                $maleShort = max(0, $im - $mv); $femaleShort = max(0, $if - $fv);
                $shortTxt = ($maleShort + $femaleShort) > 0
                    ? ' Projected shortfall: ' . $maleShort . ' male, ' . $femaleShort . ' female beds.'
                    : ' Vacant beds cover the incoming cohort.';
                $insights['demand']['body'] = 'Vacant beds: ' . $mv . ' male, ' . $fv . ' female. Incoming hires: ' . $im . ' male, ' . $if . ' female; ' . $unhoused . ' active staff currently unhoused.' . $shortTxt;
                $insights['demand']['details'] = [
                    'vacant' => ['male' => $mv, 'female' => $fv],
                    'incoming' => ['male' => $im, 'female' => $if],
                    'shortfall' => ['male' => $maleShort, 'female' => $femaleShort],
                    'unhoused' => (int) $unhoused,
                ];
            }
        } catch (\Throwable $e) {}

        // Narrate the deterministic numbers via the FastAPI LLM (best-effort).
        $insights = Common::enrichDashboardInsights(
            $insights, 'staff accommodation & facilities', ['maintenance', 'occupancy', 'hotspots', 'demand']
        );

        return $insights;
    }

    public function Hod_dashboard(Request $request)
    {

        $page_title="Accommodation Dashboard";
        $dashboardLabel = request('dashboard_label', 'HOD');
        $page_header = '<span class="arca-font">'.$dashboardLabel.'</span> Dashboard';

        $ResortDepartment= ResortDepartment::where("resort_id",$this->globalUser->resort_id)->get();
        // Unguarded ->GetEmployee->id crashed this whole page (and its
        // DataTables ajax refresh) for any XCOM/HOD admin account with no
        // linked employee row — same null-check the constructor already
        // uses for $this->reporting_to.
        $currentHod = optional(Auth::guard('resort-admin')->user()->GetEmployee)->id;
        $MaintanaceRequest = MaintanaceRequest::join("employees as t3","t3.id","maintanace_requests.Raised_By")
                                                    ->join("resort_admins as t1","t1.id","t3.Admin_Parent_id")
                                                    ->join("resort_departments as t4","t4.id","t3.Dept_id")
                                                    ->whereNotIn('maintanace_requests.Status', ['Closed', 'On-Hold'])
                                                    ->where('maintanace_requests.Assigned_To',$currentHod)
                                                    ->where('maintanace_requests.resort_id', $this->globalUser->resort_id);
                                                    if($request->filled('ResortDepartment'))
                                                    {
                                                        $MaintanaceRequest->where('t3.Dept_id',$request->ResortDepartment);
                                                    }
                            $MaintanaceRequest =  $MaintanaceRequest->leftjoin("resort_admins as t2","t2.id","maintanace_requests.Assigned_To")
                                                ->orderBy('maintanace_requests.id','desc')
                                                ->get(['t1.id as Parentid','t1.first_name','t1.last_name','maintanace_requests.*'])
                                                ->map(function ($row) {
                                                    $row->RequestedBy=$row->first_name.' '.$row->last_name;
                                                    $row->AssgingedStaff=$row->Assigned_To;
                                                    $row->Location=optional($row->BuilidngData)->BuildingName . (!empty($row->RoomNo) ? ', Room No - '.$row->RoomNo : '') . (!empty($row->FloorNo) ? ', Floor No - '.$row->FloorNo : '');
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
                                                        $row->Assign_toName     = $emp->first_name.' '.$emp->last_name;
                                                    }
                                                    $row->EffectedAmenity = $InventoryModule ? ucfirst($InventoryModule->ItemName) : 'N/A';
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

        $currentHodId = optional(Auth::guard('resort-admin')->user()->GetEmployee)->id;

        $Totalnumberofopenrequests= MaintanaceRequest::where('maintanace_requests.resort_id', $this->globalUser->resort_id)
                                                ->where('maintanace_requests.Assigned_To', $currentHodId)
                                                ->whereIn('maintanace_requests.Status', ['pending','Open'])
                                                ->count();
        $TotalnumberofHighrequests= MaintanaceRequest::where('maintanace_requests.resort_id', $this->globalUser->resort_id)
                                                    ->where('maintanace_requests.Assigned_To', $currentHodId)
                                                    ->where('maintanace_requests.priority', 'High')
                                                    ->count();

        $TotalnumberofInProgressrequests= MaintanaceRequest::where('maintanace_requests.resort_id', $this->globalUser->resort_id)
                                                    ->where('maintanace_requests.Assigned_To', $currentHodId)
                                                    ->whereIn('maintanace_requests.Status', ['In-Progress'])
                                                    ->count();

        $Employee =Employee::join('resort_admins','resort_admins.id',"=",'employees.Admin_Parent_id')
                                ->where('employees.resort_id', $this->globalUser->resort_id)
                                ->whereIn('employees.id',$this->underEmp_id)
                                ->get(['employees.*','resort_admins.first_name','resort_admins.last_name']);
        return view('resorts.Accommodation.dashboard.hoddashboard',compact('page_header','Employee','Totalnumberofopenrequests','TotalnumberofHighrequests','TotalnumberofInProgressrequests','page_title','ResortDepartment'));
    }

    public function excom_dashboard()
    {
        request()->merge(['dashboard_label' => 'XCOM']);
        return $this->Hod_dashboard(request());
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
        $row->Location                                      =   optional($row->BuilidngData)->BuildingName . (!empty($row->RoomNo) ? ', Room No - ' . $row->RoomNo : '') . (!empty($row->FloorNo) ? ', Floor No - ' . $row->FloorNo : '');
        // $row->Priority                                      =   $row->priority;
        $row->NewStatus                                     =   $row->Status;
        $row->Date                                          =   date('d M Y', strtotime($row->date));

        // **Calculate Escalation Time**
        $daysSinceRequest                                   =   now()->diffInDays(Carbon::parse($row->date));
        $row->EscalationTimeOver                            =   ($daysSinceRequest > ($EscalationDay->EscalationDay ?? 0)) ? '#ffb4b4' :null;
        // **Set Profile Image**
        $row->profileImg                                    =   Common::getResortUserPicture($row->Parentid);
        // Was URL::asset($row->Image) — missing the resort-scoped directory
        // the file actually lives under (see MaintananceContorller.php),
        // AND URL::asset only ever produces a working URL for the local
        // disk, not the configured storage driver (Wasabi in prod). Same
        // root cause as the maintenance-request details page attachments.
        $path_path = config('settings.MaintanceRequest') . '/' . $this->resort->resort_id;
        $row->Image           = $row->Image ? StorageHelper::temporaryUrl($path_path . '/' . $row->Image) : null;
        $row->Completed_Image = $row->Completed_Image ? StorageHelper::temporaryUrl($path_path . '/' . $row->Completed_Image) : null;
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
                                                        $row->Location=optional($row->BuilidngData)->BuildingName . (!empty($row->RoomNo) ? ', Room No - '.$row->RoomNo : '') . (!empty($row->FloorNo) ? ', Floor No - '.$row->FloorNo : '');
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
                                                                $row->Assign_toName     = $emp->first_name.' '.$emp->last_name;
                                                            }
                                                                
                                                        }
                                                        $row->EffectedAmenity = $InventoryModule ? ucfirst($InventoryModule->ItemName) : 'N/A';
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
