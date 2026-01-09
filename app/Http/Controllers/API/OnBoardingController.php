<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\AvailableAccommodationInvItem;
use App\Models\AvailableAccommodationModel;
use App\Models\EmployeeItinerariesMeeting;
use App\Models\EmployeeItineraries;
use App\Models\ItineraryTemplate;
use App\Models\AssingAccommodation;
use App\Models\EmployeeOnboardingAcknowledgements;
use App\Models\CulturalInsights;
use App\Models\Employee;
use App\Models\ChildFileManagement;
use App\Models\FacilityTourCategories;
use App\Models\FacilityTourImages;
use App\Models\JobDescription;
use App\Models\ResortBenifitGrid;
use App\Models\ResortBenifitGridChild;
use App\Models\Resort;
use App\Helpers\Common;
use Carbon\Carbon;
use Validator;
use DB;

class OnBoardingController extends Controller
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

    public function onBoardingDashboard()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
        
            $employee                                       =   $this->user->GetEmployee;
            $employeeId                                     =   $this->user->GetEmployee->id;

            $EmployeeItineraries                            =   EmployeeItineraries::where('resort_id', $this->resort_id)
                                                                    ->where('employee_id', $employeeId)
                                                                    ->first();
            if (!$EmployeeItineraries) {
                return response()->json([
                    'status'                                =>  false,
                    'message'                               =>  'Onboarding data not found'
                ]);
            }

            // Helper to get employee details
           
            $getEmployeeDetails                             =   function ($employeeId) {
                                                                return Employee::join('resort_admins as ra', 'ra.id', '=', 'employees.Admin_Parent_id')
                                                                    ->where('employees.id', $employeeId)
                                                                    ->where('employees.status', 'Active')
                                                                    ->select('employees.id', 'ra.first_name', 'ra.last_name', 'ra.personal_phone', 'ra.profile_picture', 'employees.Admin_Parent_id')
                                                                    ->first();
            };

            $pickupEmployee                                 =   $getEmployeeDetails($EmployeeItineraries->pickup_employee_id);
            $medicalEmployee                                =   $getEmployeeDetails($EmployeeItineraries->accompany_medical_employee_id);

            $EmployeeItineraries->pickup_employee_name      =   $pickupEmployee ? "{$pickupEmployee->first_name} {$pickupEmployee->last_name}" : '';
            $EmployeeItineraries->pickup_employee_phone     =   $pickupEmployee->personal_phone ?? '';
            $EmployeeItineraries->pickup_employee_profile   =   $pickupEmployee ? Common::getResortUserPicture($pickupEmployee->Admin_Parent_id) : '';
            $pickupSelfiImage                               =   Employee::where('id', $EmployeeItineraries->pickup_employee_id)->select('selfie_image','Emp_id')->first();
            $employeeSelfiePath                             =   config('settings.employeeSelfie');
            $dynamic_path                                   =   $employeeSelfiePath . '/' . $this->user->resort->resort_id.'/'.$pickupSelfiImage->Emp_id;
            $EmployeeItineraries->pick_up_view_selfie_image =   asset('/' . $dynamic_path . '/' . $pickupSelfiImage->selfie_image)  ?? '';
            $EmployeeItineraries->medical_employee_name     =   $medicalEmployee ? "{$medicalEmployee->first_name} {$medicalEmployee->last_name}" : '';
            $EmployeeItineraries->medical_employee_phone    =   $medicalEmployee->personal_phone ?? '';
            $EmployeeItineraries->medical_employee_profile  =   $medicalEmployee ? Common::getResortUserPicture($medicalEmployee->Admin_Parent_id) : '';

            $medicalEmpSelfiImage                           =   Employee::where('id', $EmployeeItineraries->accompany_medical_employee_id)->select('selfie_image','Emp_id')->first();
            $employeeSelfiePath                             =   config('settings.employeeSelfie');
            $dynamic_path                                   =   $employeeSelfiePath . '/' . $this->user->resort->resort_id.'/'.$medicalEmpSelfiImage->Emp_id;
            $EmployeeItineraries->medical_view_selfie_image =   asset('/' . $dynamic_path . '/' . $medicalEmpSelfiImage->selfie_image)  ?? '';

            $accommodationDetails                           =   AvailableAccommodationModel::join('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
                                                                    ->join('building_models as bm', 'bm.id', '=', 'available_accommodation_models.BuildingName')
                                                                    ->join('accommodation_types as at', 'at.id', '=', 'available_accommodation_models.Accommodation_type_id')
                                                                    ->where('available_accommodation_models.resort_id', $this->resort_id)
                                                                    ->where('t1.emp_id', $employeeId)
                                                                    ->with('availableAccommodationInvItem.inventoryModule')
                                                                    ->select('available_accommodation_models.*', 't1.id as assing_acc_id', 't1.BedNo', 't1.emp_id', 'bm.BuildingName', 'at.AccommodationName')
                                                                    ->first();

            $accommodationId                                =   $accommodationDetails->id ?? null;
            $accommodationsharedPeople                      =   [];
            $accommodationsharedPeopleCount                 =   0;

            if ($accommodationDetails && $accommodationDetails->Accommodation_type_id == 3) {
                $accommodationsharedPeople                  =   AssingAccommodation::where("available_a_id", $accommodationId)
                                                                    ->join('employees as t2', 't2.id', '=', 'assing_accommodations.emp_id')
                                                                    ->join('resort_admins as t3', 't3.id', '=', 't2.Admin_Parent_id')
                                                                    ->get(['t3.first_name', 't3.last_name', 't3.id as Parentid'])
                                                                    ->map(function ($row) {
                                                                        $row->profileImg = Common::getResortUserPicture($row->Parentid);
                                                                        return $row;
                                                                    });
                $accommodationsharedPeopleCount             =   $accommodationsharedPeople->count();
            }

            $accommodationInvItems                          =   [];
            if ($accommodationId) {
                $accommodationInvItems                      =   AvailableAccommodationInvItem::join('inventory_modules as im', 'im.id', '=', 'available_accommodation_inv_items.Item_id')
                                                                    ->where('available_accommodation_inv_items.Available_Acc_id', $accommodationId)
                                                                    ->select('im.ItemName')
                                                                    ->get();
            }
            $membersInYourDepartment                        =   Employee::join('resort_admins as ra', 'ra.id', '=', 'employees.Admin_Parent_id')
                                                                    ->join('resort_positions as rp', 'rp.id', '=', 'employees.Position_id')
                                                                    ->join('resort_departments as rd', 'rd.id', '=', 'employees.Dept_id')
                                                                    ->where('employees.resort_id', $this->resort_id)
                                                                    ->where('employees.Dept_id', $employee->Dept_id)
                                                                    ->select('employees.id', 'ra.first_name', 'ra.last_name', 'ra.personal_phone', 'ra.profile_picture', 'employees.Admin_Parent_id','rp.position_title','rd.name as department_name')                                                                                                               
                                                                    ->where('employees.status', 'Active')
                                                                    ->get()->map(function ($item) {
                                                                        $item->profile_picture = Common::getResortUserPicture($item->Admin_Parent_id);
                                                                        return $item;
                                                                    });

            $hodOfYourDivision                              =   Employee::join('resort_admins as ra', 'ra.id', '=', 'employees.Admin_Parent_id')
                                                                    ->join('resort_positions as rp', 'rp.id', '=', 'employees.Position_id')
                                                                    ->join('resort_departments as rd', 'rd.id', '=', 'employees.Dept_id')
                                                                    ->where('employees.resort_id', $this->resort_id)
                                                                    ->where('employees.division_id', $employee->division_id)
                                                                    ->where('employees.rank', 2)
                                                                    ->select('employees.id', 'ra.first_name', 'ra.last_name', 'ra.personal_phone', 'ra.profile_picture', 'employees.Admin_Parent_id','rp.position_title','rd.name as department_name')                                                                                                               
                                                                    ->where('employees.status', 'Active')
                                                                    ->get()->map(function ($item) {
                                                                        $item->profile_picture = Common::getResortUserPicture($item->Admin_Parent_id);
                                                                        return $item;
                                                                    });

            $CulturalInsights                               =   CulturalInsights::where('resort_id', $this->resort_id)  
                                                                    ->select('cultural_insights')
                                                                    ->first();

            $ItineraryTemplate                              =   ItineraryTemplate::where('id',$EmployeeItineraries->template_id)
                                                                    ->where('resort_id', $this->resort_id)
                                                                    ->select('id', 'resort_id', 'name', 'description', 'template_type', 'fields')
                                                                    ->first();

            $meetingSchedule                                =   EmployeeItinerariesMeeting::where('employee_itinerary_id',$EmployeeItineraries->id)
                                                                    ->select('id', 'employee_itinerary_id', 'meeting_date', 'meeting_time', 'meeting_link', 'meeting_participant_ids')
                                                                    ->get();

            $resort_id                                      =   $this->resort_id;

            $EmployeeItineraries->accommodation_details     =   [
                'building_name'                             =>  $accommodationDetails->BuildingName ?? '',
                'room_no'                                   =>  $accommodationDetails->RoomNo ?? '',
                'bed_no'                                    =>  $accommodationDetails->BedNo ?? '',
                // 'capacity'                                  =>  $accommodationDetails->Capacity ?? 0,
                'accommodation_type'                        =>  $accommodationDetails->AccommodationName ?? '',
                'accommodation_shared_people_count'         =>  $accommodationsharedPeopleCount,
                'accommodation_shared_people'               =>  $accommodationsharedPeople,
                'accommodation_inventory_items'             =>  $accommodationInvItems,
            ];

            $EmployeeItineraries->key_contacts              =   [
                'members_in_your_department'                =>  $membersInYourDepartment,
                'hod_of_your_division'                      =>  $hodOfYourDivision,
            ];

            $EmployeeItineraries->cultural_insights         =   $CulturalInsights ? $CulturalInsights->cultural_insights : '';  
            $EmployeeItineraries->itinerary_template        =   $ItineraryTemplate;  
            $EmployeeItineraries->meeting_schedule          =   $meetingSchedule;  
            // $EmployeeItineraries->facility_tour_categories_image  =   $FacilityTourCategories;  

            return response()->json([
                'success'                                   => true,
                'message'                                   => "Onboarding retrieved successfully.",
                'on_boarding_data'                          => $EmployeeItineraries,
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function AssignedStaffDashboard()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $employee                                   =   $this->user->GetEmployee;

            // Dates for upcoming tasks
            $today                                      =   \Carbon\Carbon::today();
            $endOfWeek                                  =   \Carbon\Carbon::now()->endOfWeek();

            // All tasks (pickup or medical) assigned to the staff
            $itineraries                                =   EmployeeItineraries::where('resort_id', $this->resort_id)
                                                                ->where(function ($q) use ($employee,$today) {
                                                                    $q->where('pickup_employee_id', $employee->id)
                                                                    ->orWhere('accompany_medical_employee_id', $employee->id);
                                                                })
                                                                ->get();

            $tasks                                      =   [];
            $upcoming_tasks                             =   [];

            foreach ($itineraries as $itinerary) {
                // Common user details fetch
                $user                                   =   Employee::join('resort_admins as ra', 'ra.id', '=', 'employees.Admin_Parent_id')
                                                                ->where('employees.id', $itinerary->employee_id)
                                                                ->select('ra.first_name', 'ra.last_name', 'ra.profile_picture', 'employees.Admin_Parent_id','employees.selfie_image','employees.Emp_id')
                                                                ->first();

                $name                                   =   $user ? $user->first_name . ' ' . $user->last_name : '';
                $profile_picture                        =   $user ? Common::getResortUserPicture($user->Admin_Parent_id) : '';
                $employeeSelfiePath                     =   config('settings.employeeSelfie');
                $dynamic_path                           =   $employeeSelfiePath . '/' . $this->user->resort->resort_id.'/'.$user->Emp_id;
                $pickUpViewSelfieImage                  =   '';
                
                if ($user->selfie_image != null || $user->selfie_image != '') {
                    $pickUpViewSelfieImage              =   asset('/' . $dynamic_path . '/' . $user->selfie_image);
                }

                if ($itinerary->arrival_date >= $today) {
                    // Pickup Task

                    if ($itinerary->pickup_employee_id == $employee->id) {
                        $pickupTask                         =   [
                            'type'                          =>  'Pick up at the Airport',
                            'name'                          =>  $name,
                            'profile_picture'               =>  $profile_picture,
                            'date'                          =>  $itinerary->arrival_date,
                            'time'                          =>  $itinerary->arrival_time,
                            'view_selfie'                   =>  $pickUpViewSelfieImage,
                        ];
                        $tasks[]                            =   $pickupTask;

                        // Check if within upcoming week range
                        if ($itinerary->arrival_date >= $today && $itinerary->arrival_date <= $endOfWeek) {
                            $upcoming_tasks[]                =   $pickupTask;
                        }
                    }
                }

                // Upcoming Medical Escort Task
                if ($itinerary->medical_date >= $today)
                {
                    // Medical Escort Task
                    if ($itinerary->accompany_medical_employee_id == $employee->id) {
                        $medicalTask = [
                            'type'                          =>  $itinerary->medical_center_name.' To Medical Center',
                            'name'                          =>  $name,
                            'profile_picture'               =>  $profile_picture,
                            'date'                          =>  $itinerary->medical_date,
                            'time'                          =>  $itinerary->approx_time,
                            'location'                      =>  $itinerary->medical_center_name,
                        ];
                        $tasks[]                            =   $medicalTask;
                        
                        if ($itinerary->medical_date >= $today && $itinerary->medical_date <= $endOfWeek) {
                            $upcoming_tasks[]                =   $medicalTask;
                        }
                    }
                }
            }

            return response()->json([
                'success'                               =>  true,
                'message'                               =>  'Assigned staff dashboard retrieved successfully.',
                'tasks'                                 =>  $tasks,
                'upcoming_tasks'                        =>  $upcoming_tasks,
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function scheduleTaskCalender(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
         $validator = Validator::make($request->all(), [
            'year'                                      =>  'date_format:Y', 
            'month'                                     =>  'date_format:m',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $employee                                       =   $this->user->GetEmployee;
        $year                                           =   $request->year ?? now()->year;
        $month                                          =   $request->month ?? now()->month;

        try {
        
        $records                                        =   EmployeeItineraries::join('employees as e', 'e.id', '=', 'employee_itineraries.employee_id')
                                                                ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                                                                ->select(
                                                                    'employee_itineraries.arrival_date',
                                                                    'employee_itineraries.arrival_time',
                                                                    'employee_itineraries.medical_date',
                                                                    'employee_itineraries.medical_time',
                                                                    'employee_itineraries.pickup_employee_id',
                                                                    'employee_itineraries.accompany_medical_employee_id',
                                                                    'ra.first_name',
                                                                    'ra.last_name',
                                                                    'ra.profile_picture',
                                                                    'e.Admin_Parent_id'
                                                                )
                                                                ->where('employee_itineraries.resort_id', $this->resort_id)
                                                                ->where(function ($q) use ($employee) {
                                                                    $q->where('employee_itineraries.pickup_employee_id', $employee->id)
                                                                    ->orWhere('employee_itineraries.accompany_medical_employee_id', $employee->id);
                                                                })
                                                                ->where(function ($query) use ($year, $month) {
                                                                    $query->where(function ($q) use ($year, $month) {
                                                                        $q->whereYear('employee_itineraries.arrival_date', $year)
                                                                        ->whereMonth('employee_itineraries.arrival_date', $month);
                                                                    })->orWhere(function ($q) use ($year, $month) {
                                                                        $q->whereYear('employee_itineraries.medical_date', $year)
                                                                        ->whereMonth('employee_itineraries.medical_date', $month);
                                                                    });
                                                                })
                                                                ->get();
        $result                                         =   [];

        foreach ($records as $item) {
            // Pick up task
            if ($item->pickup_employee_id == $employee->id && !empty($item->arrival_date)) {
                $result[]                               =   [
                    'type'                              =>  'Pick up',
                    'name'                              =>  trim($item->first_name . ' ' . $item->last_name),
                    'time'                              =>  $item->arrival_time,
                    'date'                              =>  $item->arrival_date,
                    'profile_picture'                   =>  Common::getResortUserPicture($item->Admin_Parent_id),
                ];
            }

            // Medical task
             if ($item->accompany_medical_employee_id == $employee->id && !empty($item->medical_date)) {
                $result[]                               =   [
                    'type'                              =>  'Medical',
                    'name'                              =>  trim($item->first_name . ' ' . $item->last_name),
                    'time'                              =>  $item->medical_time ?? '', // Assuming medical_time is available, otherwise set to empty
                    'date'                              =>  $item->medical_date,
                    'profile_picture'                   =>  Common::getResortUserPicture($item->Admin_Parent_id),
                ];
            }
        }
        
        return response()->json([
            'success'                                   =>  true,
            'message'                                   => 'Task calendar fetched successfully.',
            'schedule_task_data'                        =>  $result,
        ], 200);

       } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
        
    }

    public function sendSelfiImage(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator                                      =   Validator::make($request->all(), [
            'selfie_image'                              =>  'required|image|mimes:jpeg,png,jpg',
        ]);

        
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        try {
            $employee                                   =   $this->user->GetEmployee;
            $storeSelfie                                =   Employee::where('id', $employee->id)->first();

           if ($request->hasFile('selfie_image')) {
                $file       =   $request->file('selfie_image');
                $SubFolder  =   "employeeSelfie";
                $status     =   Common::AWSEmployeeFileUpload($resortId,$file, $employee->Emp_id,$SubFolder,true);

                if ($status['status'] == false) {
                    return response()->json([
                        'success'   =>  false, 
                        'message'   =>  'File upload failed: ' . ($status['msg'] ?? 'Unknown error')
                    ], 400);
                } else {
                    if($status['status'] == true && isset($status['Chil_file_id']) && !empty($status['Chil_file_id'])) {
                        $filename   =   $file->getClientOriginalName();
                        $filePath   =   ['Filename' => $filename, 'Child_id' => $status['Chil_file_id']];
                    }
                }
                
                $storeSelfie->selfie_image = $filePath;
                $storeSelfie->save();
            }

            return response()->json([
                'success'                               => true,
                'message'                               => 'Selfie image uploaded successfully.',
                'selfie_data'                           => [
                    // 'selfie_image'                      => asset('/' . $dynamic_path . '/' . $newsimg),
                    'selfie_image'                      => '',
                ],
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function storeAcknowledgement(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        // Validate the request
        $validator                                      =   Validator::make($request->all(), [
            'acknowledgements'                          =>  'required|array|min:1',
            'acknowledgements.*.acknowledgement_type'   =>  'required|string',
            'acknowledgements.*.acknowledged_date'      =>  'required|date',
            'acknowledgements.*.status'                 =>  'required|in:Yes,No',
        ]);

        // Validate the request
        if($validator->fails()) {
            return response()->json(['success' => false,'errors' => $validator->errors()], 422);
        }

        try {
            $employee                                   =   $this->user->GetEmployee;
            $saved                                      =   [];
            $duplicates                                 =   [];

            foreach ($request->acknowledgements as $ack) {
                $exists                                 =   EmployeeOnboardingAcknowledgements::where('employee_id', $employee->id)
                                                                ->where('acknowledgement_type', $ack['acknowledgement_type'])
                                                                ->whereDate('acknowledged_date', $ack['acknowledged_date'])
                                                                ->exists();

                if ($exists) {
                    $duplicates[]                       =   $ack['acknowledgement_type'];
                    continue;
                }

                $saved[]                                =   EmployeeOnboardingAcknowledgements::create([
                    'resort_id'                         =>  $this->resort_id,
                    'employee_id'                       =>  $employee->id,
                    'acknowledgement_type'              =>  $ack['acknowledgement_type'],
                    'acknowledged_date'                 =>  Carbon::parse($ack['acknowledged_date']),
                    'status'                            =>  $ack['status'],
                ]);
            }

            $message                                    =   'Acknowledgements stored successfully.';
            if (!empty($duplicates)) {
                $message                                .=  ' Already stored: ' . implode(', ', $duplicates);
            }

            return response()->json([
                'success'                               =>  true,
                'message'                               =>  $message,
                'data'                                  =>  $saved,
                'duplicates'                            =>  $duplicates,
            ], 201);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function acknowledgementViewFiles()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $employee                                                   =   $this->user->GetEmployee;
        $employeeId                                                 =   $this->user->GetEmployee->id;

        try {

            // $findEmpJD                                              =   JobDescription::where("Resort_id",$this->resort_id)
            //                                                             ->where('Division_id', $this->user->GetEmployee->division_id)
            //                                                             ->where('Department_id', $this->user->GetEmployee->Dept_id)
            //                                                             ->where('Position_id', $this->user->GetEmployee->Position_id)
            //                                                             ->where('Section_id', $this->user->GetEmployee->Section_id)
            //                                                             ->where("compliance", "Approved")
            //                                                             ->get();


            // $benefit_grid                                           =   ResortBenifitGrid::where('resort_id', $this->resort_id)
            //                                                                 ->where('emp_grade', $this->user->GetEmployee->rank)
            //                                                                 ->first();

            // $benefit_grids['leaveOfBenefitGrid']                    =   $benefit_grid->emp_grade;
            // $benefit_grids['salaryPeriod']                          =   $benefit_grid->salary_period;
            // $benefit_grids['salaryPaidIn']                          =   $benefit_grid->salary_paid_in;
            // $benefit_grids['contractStatus']                        =   $benefit_grid->contract_status;
            // $benefit_grids['effectiveDate']                         =   $benefit_grid->effective_date;
            // $benefit_grids['overtime']                              =   $benefit_grid->overtime;
            // $benefit_grids['paid_worked_public_holiday_and_friday'] =   $benefit_grid->paid_worked_public_holiday_and_friday;
            // $benefit_grids['service_charge']                        =   $benefit_grid->service_charge;
            // $benefit_grids['accommodation_status']                  =   $benefit_grid->accommodation_status;
            // $benefit_grids['furniture_and_fixtures']                =   $benefit_grid->furniture_and_fixtures;
            // $benefit_grids['housekeeping']                          =   $benefit_grid->housekeeping;
            // $benefit_grids['internet_access']                       =   $benefit_grid->internet_access;
            // $benefit_grids['linen']                                 =   $benefit_grid->linen;
            // $benefit_grids['laundry']                               =   $benefit_grid->laundry;
            // $benefit_grids['telephone']                             =   $benefit_grid->telephone;
            // $benefit_grids['loan_and_salary_advanced']              =   $benefit_grid->loan_and_salary_advanced;
            // $benefit_grids['uniform']                               =   $benefit_grid->uniform;
            // $benefit_grids['health_care_insurance']                 =   $benefit_grid->health_care_insurance;
            // $benefit_grids['ramadan_bonus']                         =   $benefit_grid->ramadan_bonus;
            // $benefit_grids['ramadan_bonus_eligibility']             =   $benefit_grid->ramadan_bonus_eligibility;
            // $benefit_grids['max_excess_luggage_relocation_expense'] =   $benefit_grid->max_excess_luggage_relocation_expense;
            // $benefit_grids['meals_per_day']                         =   $benefit_grid->meals_per_day;
            // $benefit_grids['food_and_beverages_discount']           =   $benefit_grid->food_and_beverages_discount;
            // $benefit_grids['alchoholic_beverages_discount']         =   $benefit_grid->alchoholic_beverages_discount;
            // $benefit_grids['spa_discount']                          =   $benefit_grid->spa_discount;
            // $benefit_grids['dive_center_discount']                  =   $benefit_grid->dive_center_discount;
            // $benefit_grids['water_sports_discount']                 =   $benefit_grid->water_sports_discount;
            // $benefit_grids['annual_leave_ticket']                   =   $benefit_grid->annual_leave_ticket;
            // $benefit_grids['sports_and_entertainment_facilities']   =   $benefit_grid->sports_and_entertainment_facilities;
            // $benefit_grids['standard_staff_rate_for_single']        =   $benefit_grid->standard_staff_rate_for_single;
            // $benefit_grids['standard_staff_rate_for_double']        =   $benefit_grid->standard_staff_rate_for_double;
            // $benefit_grids['friends_with_benefit_discount']         =   $benefit_grid->friends_with_benefit_discount;
            // $benefit_grids['staff_rate_for_seaplane_male']          =   $benefit_grid->staff_rate_for_seaplane_male;
            // $benefit_grids['ticket_upon_termination']               =   $benefit_grid->ticket_upon_termination;
            // $benefit_grids['male_subsistence_allowance']            =   $benefit_grid->male_subsistence_allowance;
            // $benefit_grids['free_return_flight_to_male_per_year']   =   $benefit_grid->free_return_flight_to_male_per_year;
            // $benefit_grids['status']                                =   $benefit_grid->status;
            // $benefit_grids['relocation_ticket']                     =   $benefit_grid->relocation_ticket;

            // $ResortData                                             =   Resort::where('id',$benefit_grid->resort_id)
            //                                                                 ->select('resort_name','resort_email','resort_phone','address1','address2','city','state','country','zip')
            //                                                                 ->first();

            // $benefit_grids['leave_data']                            =   ResortBenifitGridChild::join('leave_categories as lc','lc.id','=','resort_benefit_grid_child.leave_cat_id')
            //                                                                 ->where('resort_benefit_grid_child.benefit_grid_id', $benefit_grid->id)
            //                                                                 ->where('rank',$benefit_grid->emp_grade)
            //                                                                 ->where('lc.resort_id', $this->resort_id)
            //                                                                 ->select('leave_type','allocated_days')
            //                                                                 ->get()
            //                                                                 ->map(function($item) {
            //                                                                     $item->leave_type = $item->leave_type . ' (In Days)';
            //                                                                     return $item;
            //                                                                 });

            // $data['resort_data']                                    =   $ResortData;
            // $data ['Contract Signed?']                              =   '';
            // $data ['Benefit Grid Received?']                        =   $benefit_grids;
            // $data ['Employee Handbook Received and Acknowledged?']  =   '';
            // $data ['Job Description Received?']                     =   $findEmpJD;
            // $data ['Work Permit Received?']                         =   '';
            // $data ['Flight Ticket Received?']                       =   '';

                $contract_data = Common::GetAWSFile("59","1");
                $benefit_data = Common::GetAWSFile("59","1");
                $handbook_data = Common::GetAWSFile("59","1");
                $jd_data = Common::GetAWSFile("59","1");
                $wp_data = Common::GetAWSFile("59","1");
                $ticket_data = Common::GetAWSFile("59","1");

                $contract_data = $contract_data ? $contract_data : '';
                $benefit_data = $benefit_data ? $benefit_data : '';
                $handbook_data = $handbook_data ? $handbook_data : '';
                $jd_data = $jd_data ? $jd_data : '';
                $wp_data = $wp_data ? $wp_data : '';
                $ticket_data = $ticket_data ? $ticket_data : '';

                $data = [
                    'Contract'                                 =>  $contract_data,
                    'Benefit'                                  =>  $benefit_data,
                    'Handbook'                                 =>  $handbook_data,
                    'Job Description'                          =>  $jd_data,
                    'Work Permit'                              =>  $wp_data,
                    'Flight Ticket'                            =>  $ticket_data,
                ];
                
            return response()->json([
                'success'                                           =>  true,
                'message'                                           =>  "Acknowledgement files retrieved successfully.",
                'acknowledgement_files'                             =>  $data,
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    // public function getOnboardingVirtualFacility()
    // {
    //     if (!Auth::guard('api')->check()) {
    //         return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    //     }
        
    //     $resort_id                                      =   $this->resort_id;
    //     try {
    //         $FacilityTourCategories                         =   FacilityTourCategories::where('resort_id', $this->resort_id)
    //                                                                 ->select('id', 'resort_id','name','status', 'thumbnail_image')
    //                                                                 ->get()->map(function ($item) use ($resort_id) {
    //                                                                     // Fetch the file management record
    //                                                                     $file = ChildFileManagement::where('file_path', $item->thumbnail_image)->first();
    //                                                                     $item->thumbnail_image_path = $file ? Common::GetAWSFile($file->id,$resort_id): '';

    //                                                                     $item->facility_tour_images = $FacilityTourImages = FacilityTourImages::where('facility_tour_category_id', $item->id)
    //                                                                         ->select('id', 'facility_tour_category_id', 'image')
    //                                                                         ->get()->map(function ($image) use ($resort_id) {
    //                                                                             $file = ChildFileManagement::where('file_path', $image->image)->first();
    //                                                                             $image->imagePath = $file ? Common::GetAWSFile($file->id,$resort_id): '';
    //                                                                             return $image;
    //                                                                         });
    //                                                                     return $item;
    //                                                                 });
    //         return response()->json([
    //             'success'                                           =>  true,
    //             'message'                                           =>  "Virtual facility retrieved successfully.",
    //             'facility_tour_categories_image'                    =>  $FacilityTourCategories,
    //         ], 200);

    //     } catch (\Exception $e) {
    //         \Log::emergency("File: " . $e->getFile());
    //         \Log::emergency("Line: " . $e->getLine());
    //         \Log::error($e->getMessage());
    //         return response()->json(['success' => false, 'message' => 'Server error'], 500);
    //     }
    // }

    public function getOnboardingVirtualFacility()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        
        $resort_id                                      =   $this->resort_id;
        try {
           $categories = FacilityTourCategories::where('resort_id', $resort_id)
            ->select('id', 'resort_id', 'name', 'status', 'thumbnail_image')
            ->get();

            $categoryIds = $categories->pluck('id')->toArray();

            $images = FacilityTourImages::whereIn('facility_tour_category_id', $categoryIds)
                ->select('id', 'facility_tour_category_id', 'image')
                ->get()
                ->groupBy('facility_tour_category_id');

            $allPaths = collect($categories)->pluck('thumbnail_image')
                ->merge($images->flatten()->pluck('image'))
                ->unique()
                ->filter()
                ->toArray();

            $fileMap = ChildFileManagement::whereIn('File_Path', $allPaths)
                ->get()
                ->keyBy('File_Path');

            $awsFileMap = [];
            foreach ($fileMap as $file) {

                $awsFileMap[$file->File_Path] = Common::GetAWSFile($file->id, $resort_id);

            }

            $final = $categories->map(function ($item) use ($images, $awsFileMap) {
                $item->thumbnail_image_path = $awsFileMap[$item->thumbnail_image] ?? '';

                $item->facility_tour_images = ($images[$item->id] ?? collect())->map(function ($img) use ($awsFileMap) {
                    $img->imagePath = $awsFileMap[$img->image] ?? '';
                    return $img;
                });

                return $item;
            });

            return response()->json([
                'success' => true,
                'message' => "Virtual facility retrieved successfully.",
                'facility_tour_categories_image' => $final,
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }
}
