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
use App\Models\FilemangementSystem;
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

            // Extracted into shared private helpers — reused as-is by the
            // dedicated Employee-Accommodation and Employee-Key-Contacts
            // mobile screens (accommodationDetail()/keyContacts() below) so
            // that tab doesn't need to fetch this whole dashboard payload.
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

            $EmployeeItineraries->accommodation_details     =   $this->resolveAccommodationDetails($employeeId);

            $EmployeeItineraries->key_contacts              =   $this->resolveKeyContacts($employee);

            $EmployeeItineraries->cultural_insights         =   $CulturalInsights ? $CulturalInsights->cultural_insights : '';
            $EmployeeItineraries->itinerary_template        =   $ItineraryTemplate;
            $EmployeeItineraries->meeting_schedule          =   $meetingSchedule;
            // entry_pass_file/flight_ticket_file/domestic_flight_ticket
            // came through as raw child_file_management ids — never
            // resolved to an actual downloadable URL. The resolver
            // already exists and is wired into 3 sibling endpoints
            // (culturalInsights, AssignedStaffDashboard, itineraryTimeline)
            // but was never called from this, the main dashboard.
            $EmployeeItineraries->download_all               =   $this->resolveItineraryDownloads($EmployeeItineraries);
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

    /**
     * Shared by onBoardingDashboard() and the dedicated
     * on-boarding/accommodation-detail screen. $employeeId is always the
     * caller's OWN employee id (never taken from request input) — callers
     * resolve it via $this->user->GetEmployee->id first.
     */
    private function resolveAccommodationDetails($employeeId)
    {
        $accommodationDetails = AvailableAccommodationModel::join('assing_accommodations as t1', 't1.available_a_id', '=', 'available_accommodation_models.id')
            ->join('building_models as bm', 'bm.id', '=', 'available_accommodation_models.BuildingName')
            ->join('accommodation_types as at', 'at.id', '=', 'available_accommodation_models.Accommodation_type_id')
            ->where('available_accommodation_models.resort_id', $this->resort_id)
            ->where('t1.emp_id', $employeeId)
            ->with('availableAccommodationInvItem.inventoryModule')
            ->select('available_accommodation_models.*', 't1.id as assing_acc_id', 't1.BedNo', 't1.emp_id', 'bm.BuildingName', 'at.AccommodationName')
            ->first();

        $accommodationId = $accommodationDetails->id ?? null;
        $accommodationsharedPeople = [];
        $accommodationsharedPeopleCount = 0;

        if ($accommodationDetails && $accommodationDetails->Accommodation_type_id == 3) {
            $accommodationsharedPeople = AssingAccommodation::where("available_a_id", $accommodationId)
                ->join('employees as t2', 't2.id', '=', 'assing_accommodations.emp_id')
                ->join('resort_admins as t3', 't3.id', '=', 't2.Admin_Parent_id')
                ->get(['t3.first_name', 't3.last_name', 't3.id as Parentid'])
                ->map(function ($row) {
                    $row->profileImg = Common::getResortUserPicture($row->Parentid);
                    return $row;
                });
            $accommodationsharedPeopleCount = $accommodationsharedPeople->count();
        }

        $accommodationInvItems = [];
        if ($accommodationId) {
            $accommodationInvItems = AvailableAccommodationInvItem::join('inventory_modules as im', 'im.id', '=', 'available_accommodation_inv_items.Item_id')
                ->where('available_accommodation_inv_items.Available_Acc_id', $accommodationId)
                ->select('im.ItemName')
                ->get();
        }

        return [
            'building_name'                      => $accommodationDetails->BuildingName ?? '',
            'room_no'                            => $accommodationDetails->RoomNo ?? '',
            'bed_no'                             => $accommodationDetails->BedNo ?? '',
            'accommodation_type'                 => $accommodationDetails->AccommodationName ?? '',
            'accommodation_shared_people_count'  => $accommodationsharedPeopleCount,
            'accommodation_shared_people'        => $accommodationsharedPeople,
            'accommodation_inventory_items'      => $accommodationInvItems,
        ];
    }

    /**
     * Shared by onBoardingDashboard() and the dedicated
     * on-boarding/key-contacts screen. $employee is always the caller's own
     * Employee model (never resolved from request input).
     */
    private function resolveKeyContacts($employee)
    {
        $membersInYourDepartment = Employee::join('resort_admins as ra', 'ra.id', '=', 'employees.Admin_Parent_id')
            ->join('resort_positions as rp', 'rp.id', '=', 'employees.Position_id')
            ->join('resort_departments as rd', 'rd.id', '=', 'employees.Dept_id')
            ->where('employees.resort_id', $this->resort_id)
            ->where('employees.Dept_id', $employee->Dept_id)
            ->select('employees.id', 'ra.first_name', 'ra.last_name', 'ra.personal_phone', 'ra.profile_picture', 'employees.Admin_Parent_id', 'rp.position_title', 'rd.name as department_name')
            ->where('employees.status', 'Active')
            ->get()->map(function ($item) {
                $item->profile_picture = Common::getResortUserPicture($item->Admin_Parent_id);
                return $item;
            });

        $hodOfYourDivision = Employee::join('resort_admins as ra', 'ra.id', '=', 'employees.Admin_Parent_id')
            ->join('resort_positions as rp', 'rp.id', '=', 'employees.Position_id')
            ->join('resort_departments as rd', 'rd.id', '=', 'employees.Dept_id')
            ->where('employees.resort_id', $this->resort_id)
            ->where('employees.division_id', $employee->division_id)
            // HOD (2) and EXCOM (1) — a division headed by EXCOM
            // with no HOD showed no division head contact at all.
            ->whereIn('employees.rank', [1, 2])
            ->select('employees.id', 'ra.first_name', 'ra.last_name', 'ra.personal_phone', 'ra.profile_picture', 'employees.Admin_Parent_id', 'rp.position_title', 'rd.name as department_name')
            ->where('employees.status', 'Active')
            ->get()->map(function ($item) {
                $item->profile_picture = Common::getResortUserPicture($item->Admin_Parent_id);
                return $item;
            });

        return [
            'members_in_your_department' => $membersInYourDepartment,
            'hod_of_your_division'       => $hodOfYourDivision,
        ];
    }

    /**
     * Employee-Accommodation mobile screen. Same data as the
     * accommodation_details tab of on-boarding-dashboard, exposed standalone
     * so the mobile app doesn't have to pull the whole dashboard payload.
     * Employee-owned: always the caller's own employee id.
     */
    public function accommodationDetail()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $employee = $this->user->GetEmployee;
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found'], 404);
        }

        try {
            return response()->json([
                'success' => true,
                'message' => 'Accommodation detail retrieved successfully.',
                'data'    => $this->resolveAccommodationDetails($employee->id),
            ], 200);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * Employee-Key-Contacts mobile screen ("Members in Your Department" /
     * "HOD's of Your Division"). Same data as the key_contacts tab of
     * on-boarding-dashboard, exposed standalone. resort_id + the caller's
     * own department/division scope it — never another employee's.
     */
    public function keyContacts()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $employee = $this->user->GetEmployee;
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found'], 404);
        }

        try {
            return response()->json([
                'success' => true,
                'message' => 'Key contacts retrieved successfully.',
                'data'    => $this->resolveKeyContacts($employee),
            ], 200);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * Hotel-Details mobile screen. Same employee_itineraries row the
     * dashboard already returns, filtered down to just the hotel/medical
     * fields the Figma screen shows. Employee-owned: this employee's own
     * itinerary row only.
     */
    public function hotelDetails()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $employee = $this->user->GetEmployee;
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found'], 404);
        }

        try {
            $itinerary = EmployeeItineraries::where('resort_id', $this->resort_id)
                ->where('employee_id', $employee->id)
                ->select('hotel_name', 'hotel_contact_no', 'hotel_address', 'booking_reference',
                         'medical_center_name', 'medical_center_contact_no', 'medical_type')
                ->first();

            if (!$itinerary) {
                return response()->json(['success' => false, 'message' => 'Onboarding data not found'], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Hotel details retrieved successfully.',
                'data'    => $itinerary,
            ], 200);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * Cultural-Insights mobile screen. Resort-wide policy text (same table
     * the web config screen writes via storeOrUpdateCI) — no employee-
     * specific data here, so only resort_id scoping applies. Figma also
     * shows a "Download All Itinerary PDF" button on this screen even
     * though it's policy content, not itinerary data — most likely this
     * screen is really a tab inside the itinerary screen's tab set rather
     * than a standalone page (see itineraryTimeline() below, which already
     * exposes the same download_all block). Included here too so the
     * button works regardless of how the mobile client wires the tabs.
     */
    public function culturalInsights()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $employee = $this->user->GetEmployee;
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found'], 404);
        }

        try {
            $content = CulturalInsights::where('resort_id', $this->resort_id)->first();
            $itinerary = EmployeeItineraries::where('resort_id', $this->resort_id)
                ->where('employee_id', $employee->id)
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Cultural insights retrieved successfully.',
                'data'    => [
                    'cultural_insights' => $content->cultural_insights ?? '',
                    'download_all'      => $itinerary ? $this->resolveItineraryDownloads($itinerary) : [],
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
     * Every already-uploaded onboarding PDF on this itinerary (entry pass,
     * flight ticket, domestic flight ticket) resolved to a downloadable URL.
     * This is the "Download PDF"/"Download All Itinerary PDF" data — there
     * is no PDF-generation/merge step here, these are the real files HR
     * already uploaded via storeItinerary(). resort_id scoped through
     * Common::GetAWSFile.
     */
    private function resolveItineraryDownloads($itinerary)
    {
        $files = [
            'entry_pass_file'        => 'Entry Pass',
            'flight_ticket_file'     => 'Flight Ticket',
            'domestic_flight_ticket' => 'Domestic Flight Ticket',
        ];

        $downloads = [];
        foreach ($files as $column => $label) {
            $childId = $itinerary->{$column} ?? null;
            if (empty($childId)) {
                continue;
            }
            try {
                $aws = Common::GetAWSFile($childId, $this->resort_id);
                if (!empty($aws['success'])) {
                    $downloads[] = ['label' => $label, 'url' => $aws['NewURLshow'] ?? null];
                }
            } catch (\Throwable $e) {
                // Skip files that fail to resolve rather than failing the whole screen.
            }
        }

        return $downloads;
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

                // Pickup Task — was gated on arrival_date >= today, so a
                // still-Pending (never actioned) task whose date had simply
                // passed vanished from "Assigned Tasks" entirely instead of
                // showing as overdue. pickup_status/medical_escort_status
                // already track real completion state; the date only
                // decides whether a task ALSO counts as "upcoming" (this
                // week), not whether it's assigned at all.
                if ($itinerary->pickup_employee_id == $employee->id) {
                    $pickupTask                         =   [
                        'itinerary_id'                  =>  $itinerary->id,
                        'task_type'                     =>  'pickup',
                        'status'                        =>  $itinerary->pickup_status,
                        'type'                          =>  'Pick up at the Airport',
                        'name'                          =>  $name,
                        'profile_picture'               =>  $profile_picture,
                        'date'                          =>  $itinerary->arrival_date,
                        'time'                          =>  $itinerary->arrival_time,
                        'view_selfie'                   =>  $pickUpViewSelfieImage,
                        'download_pdf'                  =>  $this->resolveItineraryDownloads($itinerary),
                    ];
                    $tasks[]                            =   $pickupTask;

                    // Check if within upcoming week range
                    if ($itinerary->arrival_date >= $today && $itinerary->arrival_date <= $endOfWeek) {
                        $upcoming_tasks[]                =   $pickupTask;
                    }
                }

                // Medical Escort Task — same fix as pickup above.
                if ($itinerary->accompany_medical_employee_id == $employee->id) {
                    $medicalTask = [
                        'itinerary_id'                  =>  $itinerary->id,
                        'task_type'                     =>  'medical',
                        'status'                        =>  $itinerary->medical_escort_status,
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

    /**
     * Accept/Reject/Complete a pickup or medical-escort task from the
     * Assigned-Staff dashboard (and the same buttons on the Employee-
     * Onboarding itinerary screen's Upcoming/Assigned Tasks sections). Only
     * the staff member the task is actually assigned to may act on it —
     * scoped by resort_id AND by matching employee id against
     * pickup_employee_id/accompany_medical_employee_id on the itinerary,
     * never by itinerary_id alone.
     */
    public function taskAction(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'itinerary_id' => 'required|integer',
            'task_type'    => 'required|in:pickup,medical',
            'action'       => 'required|in:approve,reject,complete',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $employee = $this->user->GetEmployee;
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found'], 404);
        }

        try {
            $itinerary = EmployeeItineraries::where('resort_id', $this->resort_id)
                ->where('id', $request->itinerary_id)
                ->first();

            if (!$itinerary) {
                return response()->json(['success' => false, 'message' => 'Itinerary not found'], 404);
            }

            $ownerColumn = $request->task_type === 'pickup' ? 'pickup_employee_id' : 'accompany_medical_employee_id';
            $statusColumn = $request->task_type === 'pickup' ? 'pickup_status' : 'medical_escort_status';

            if ((int) $itinerary->{$ownerColumn} !== (int) $employee->id) {
                return response()->json(['success' => false, 'message' => 'This task is not assigned to you.'], 403);
            }

            $statusMap = ['approve' => 'Approved', 'reject' => 'Rejected', 'complete' => 'Completed'];
            $itinerary->{$statusColumn} = $statusMap[$request->action];
            $itinerary->save();

            return response()->json([
                'success' => true,
                'message' => 'Task updated successfully.',
                'data'    => [
                    'itinerary_id' => $itinerary->id,
                    'task_type'    => $request->task_type,
                    'status'       => $itinerary->{$statusColumn},
                ],
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
            'selfie_image'                              =>  'required|mimes:jpeg,png,jpg,gif,svg,webp,heic,heif',
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
                // Was $resortId — undefined variable (never set anywhere in
                // this method), so every selfie upload 500'd with an
                // "Undefined variable" error before ever reaching AWS.
                $status     =   Common::AWSEmployeeFileUpload($this->resort_id,$file, $employee->Emp_id,$SubFolder,true);

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

    /**
     * The 4 documents the mobile "Acknowledge" screen asks about. The
     * acknowledgement_type strings on the left must match exactly what
     * storeAcknowledgement() is called with; the value is the
     * EmployeesDocument.document_category HR tags the matching upload with
     * (via the existing generic /employee-document upload endpoint — no new
     * upload endpoint needed).
     */
    private const ACKNOWLEDGEMENT_TYPES = [
        'Contract Signed'                              => 'Contract',
        'Benefit Grid Received'                         => 'Benefit Grid',
        'Employee Handbook Received and Acknowledged'   => 'Employee Handbook',
        'Disciplinary Process'                          => 'Disciplinary Process',
    ];

    public function acknowledgementViewFiles()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $employee                                                   =   $this->user->GetEmployee;

        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found'], 404);
        }

        $employeeId                                                 =   $employee->id;
        $resortId                                                   =   $this->resort_id;

        try {
            // Was: Common::GetAWSFile("59","1") — hardcoded ids, no
            // resort_id/employee scoping at all, so every caller (any
            // resort, any employee) got back whichever file happened to be
            // ChildFileManagement id 59 under resort 1. Rebuilt to actually
            // read this employee's own acknowledgement status/date
            // (employee_onboarding_acknowledgements) plus their own tagged
            // document (employees_documents, resort_id + employee_id +
            // document_category scoped) for the "View File" link.
            $ackRows = \App\Models\EmployeeOnboardingAcknowledgements::where('resort_id', $resortId)
                ->where('employee_id', $employeeId)
                ->whereIn('acknowledgement_type', array_keys(self::ACKNOWLEDGEMENT_TYPES))
                ->orderByDesc('id')
                ->get()
                ->unique('acknowledgement_type')
                ->keyBy('acknowledgement_type');

            $docRows = \App\Models\EmployeesDocument::where('resort_id', $resortId)
                ->where('employee_id', $employeeId)
                ->whereIn('document_category', array_values(self::ACKNOWLEDGEMENT_TYPES))
                ->orderByDesc('id')
                ->get()
                ->unique('document_category')
                ->keyBy('document_category');

            $data = [];
            foreach (self::ACKNOWLEDGEMENT_TYPES as $type => $docCategory) {
                $ack = $ackRows->get($type);
                $doc = $docRows->get($docCategory);

                $fileUrl = null;
                if ($doc) {
                    $decoded = json_decode((string) $doc->document_path, true);
                    $childId = is_array($decoded) ? ($decoded['Child_id'] ?? null) : null;
                    if (!empty($childId)) {
                        try {
                            $aws = Common::GetAWSFile($childId, $resortId);
                            $fileUrl = !empty($aws['success']) ? ($aws['NewURLshow'] ?? null) : null;
                        } catch (\Throwable $e) {
                            $fileUrl = null;
                        }
                    }
                }

                $data[] = [
                    'acknowledgement_type' => $type,
                    'status'               => $ack->status ?? 'No',
                    'acknowledged_date'    => $ack->acknowledged_date ?? null,
                    'view_file_url'        => $fileUrl,
                ];
            }

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

    /**
     * Harassment Prevention policy content. Acknowledging it already works
     * via the existing generic on-boarding/store-acknowledgement endpoint
     * (acknowledgement_type is a free-text string) — this just supplies the
     * text to show before that acknowledgement.
     */
    public function harassmentPreventionContent()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $content                                     =   \App\Models\OnboardingContent::where('resort_id', $this->resort_id)
                                                                ->where('content_type', 'harassment_prevention')
                                                                ->first();

            return response()->json([
                'success'                               =>  true,
                'message'                               =>  'Harassment prevention content fetched successfully.',
                'data'                                  =>  [
                    'title'                              =>  $content->title ?? 'Harassment Prevention Policy',
                    'content'                            =>  $content->content ?? '',
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
     * The mobile "Access Details" screen is a key-value display (email,
     * passport number, access code) — NOT rich-text policy content. The
     * content/title shape here originally mirrored harassment-prevention's,
     * which never actually served the real Figma fields. resort_id +
     * employee-ownership scoped through $this->user->GetEmployee, same as
     * every other employee-facing endpoint in this controller.
     * access_code is resort-wide (a shared door/gate code, not a per-
     * employee secret), so it's read from the existing onboarding_contents
     * 'access_details' blob rather than adding a new employees column.
     */
    public function accessDetails()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $employee = $this->user->GetEmployee;
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found'], 404);
        }

        try {
            $content = \App\Models\OnboardingContent::where('resort_id', $this->resort_id)
                ->where('content_type', 'access_details')
                ->first();

            return response()->json([
                'success'                               =>  true,
                'message'                               =>  'Access details fetched successfully.',
                'data'                                  =>  [
                    'email'                              =>  $this->user->email ?? '',
                    'passport_number'                    =>  $employee->passport_number ?? '',
                    'access_code'                        =>  $content->content ?? '',
                    // Kept for backward-compat with any client already
                    // reading the old rich-text shape.
                    'title'                              =>  $content->title ?? 'Access Details',
                    'content'                            =>  $content->content ?? '',
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
     * The company-policy categories the "employee-handbook" mobile screen
     * lists. Everything except cultural_insights (which has its own
     * long-standing CulturalInsights table, written by the web config
     * screen) is a row in the generic onboarding_contents table — one
     * content_type per category instead of a dedicated column/table per
     * policy. has_content lets the mobile list grey out rows HR hasn't
     * written yet instead of opening to a blank screen.
     */
    private const HANDBOOK_CATEGORIES = [
        'non_discrimination'         => 'Non-Discrimination Policy',
        'harassment_prevention'      => 'Harassment Prevention',
        'job_roles_responsibilities' => 'Job Roles & Responsibilities',
        'safety_emergency_procedures' => 'Safety & Emergency Procedures',
        'access_details'             => 'Access Details',
        'cultural_insights'          => 'Cultural Insights',
    ];

    public function employeeHandbookList()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $existing = \App\Models\OnboardingContent::where('resort_id', $this->resort_id)
                ->whereIn('content_type', array_keys(self::HANDBOOK_CATEGORIES))
                ->pluck('content_type')
                ->all();

            $hasCulturalInsights = CulturalInsights::where('resort_id', $this->resort_id)
                ->whereNotNull('cultural_insights')
                ->exists();

            $list = [];
            foreach (self::HANDBOOK_CATEGORIES as $type => $title) {
                $list[] = [
                    'content_type' => $type,
                    'title'        => $title,
                    'has_content'  => $type === 'cultural_insights' ? $hasCulturalInsights : in_array($type, $existing, true),
                ];
            }

            // Employee Handbook now also surfaces real uploaded documents
            // instead of only free-text categories — HR manages this via
            // a resort-wide File Management folder named exactly "Employee
            // Handbook" (Folder_Type=uncategorized, same convention as the
            // existing facilityTourCategory folder), matched by name since
            // there's no dedicated "handbook folder" concept in the schema.
            $handbookFolder = FilemangementSystem::where('resort_id', $this->resort_id)
                ->where('Folder_Type', 'uncategorized')
                ->where('Folder_Name', 'Employee Handbook')
                ->first();

            $handbookFiles = $handbookFolder
                ? ChildFileManagement::where('Parent_File_ID', $handbookFolder->id)
                    ->where('resort_id', $this->resort_id)
                    ->orderByDesc('id')
                    ->get()
                    ->map(function ($file) {
                        $aws = Common::GetAWSFile($file->id, $this->resort_id);
                        return [
                            'id'   => $file->id,
                            'name' => $file->NewFileName ?: $file->File_Name,
                            'url'  => !empty($aws['success']) ? $aws['NewURLshow'] : null,
                        ];
                    })->values()
                : collect();

            return response()->json([
                'success' => true,
                'message' => 'Employee handbook categories retrieved successfully.',
                'data'    => $list,
                'handbook_files' => $handbookFiles,
            ], 200);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * Generic reader for any employee-handbook category (and the
     * already-named harassment-prevention-content/access-details routes
     * could call this too, but are left as-is for backward compatibility).
     * One method instead of 7 near-identical ones.
     */
    public function policyContent($type)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if (!array_key_exists($type, self::HANDBOOK_CATEGORIES)) {
            return response()->json(['success' => false, 'message' => 'Unknown content type.'], 404);
        }

        try {
            if ($type === 'cultural_insights') {
                $content = CulturalInsights::where('resort_id', $this->resort_id)->first();
                $body = $content->cultural_insights ?? '';
            } else {
                $content = \App\Models\OnboardingContent::where('resort_id', $this->resort_id)
                    ->where('content_type', $type)
                    ->first();
                $body = $content->content ?? '';
            }

            return response()->json([
                'success' => true,
                'message' => 'Policy content retrieved successfully.',
                'data'    => [
                    'content_type' => $type,
                    'title'        => self::HANDBOOK_CATEGORIES[$type],
                    'content'      => $body,
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
     * Employee-Onboarding itinerary mobile screen — day-grouped view over
     * the SAME employee_itineraries + employee_itineraries_meeting rows
     * already written by the web storeItinerary()/updateItinerary() flow.
     * No new tables: every item below is one of the existing date/time
     * columns or an existing meeting row, just reshaped into "Day 1, Day 2…"
     * groups. Employee-owned: this employee's own itinerary only.
     *
     * ponytail: "completed" for the non-actionable items (domestic flight,
     * speedboat, seaplane, hotel drop, meetings) is a naive date-passed
     * heuristic — there's no real completion tracking column for those.
     * pickup/medical DO have a real status (pickup_status/
     * medical_escort_status, settable only by the assigned staff via
     * taskAction()). Upgrade path: add per-item status columns if the
     * business ever needs someone to mark e.g. "hotel drop" done explicitly.
     */
    public function itineraryTimeline()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $employee = $this->user->GetEmployee;
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found'], 404);
        }

        try {
            $itinerary = EmployeeItineraries::where('resort_id', $this->resort_id)
                ->where('employee_id', $employee->id)
                ->first();

            if (!$itinerary) {
                return response()->json(['success' => false, 'message' => 'Onboarding data not found'], 404);
            }

            $today = Carbon::today()->toDateString();
            $items = [];

            $addItem = function ($date, $time, $title, $status, $fileColumn = null) use (&$items, $itinerary, $today) {
                if (empty($date)) {
                    return;
                }
                $dateStr = Carbon::parse($date)->toDateString();
                $downloadUrl = null;
                if ($fileColumn && !empty($itinerary->{$fileColumn})) {
                    try {
                        $aws = Common::GetAWSFile($itinerary->{$fileColumn}, $this->resort_id);
                        $downloadUrl = !empty($aws['success']) ? ($aws['NewURLshow'] ?? null) : null;
                    } catch (\Throwable $e) {
                        $downloadUrl = null;
                    }
                }
                $items[] = [
                    'date'          => $dateStr,
                    'time'          => $time,
                    'title'         => $title,
                    'status'        => $status ?? ($dateStr < $today ? 'Completed' : 'Pending'),
                    'download_pdf'  => $downloadUrl,
                ];
            };

            $addItem($itinerary->arrival_date, $itinerary->arrival_time, 'Airport Pickup', $itinerary->pickup_status, 'entry_pass_file');
            $addItem($itinerary->arrival_date, $itinerary->arrival_time, 'Hotel Drop (' . $itinerary->hotel_name . ')', null, 'flight_ticket_file');
            $addItem($itinerary->domestic_flight_date, $itinerary->domestic_departure_time, 'Domestic Flight', null, 'domestic_flight_ticket');
            $addItem($itinerary->speedboat_date, $itinerary->speedboat_departure_time, 'Resort Transportation (Speedboat)', null);
            $addItem($itinerary->seaplane_date, $itinerary->seaplane_departure_time, 'Resort Transportation (Seaplane)', null);
            $addItem($itinerary->medical_date, $itinerary->approx_time, 'Work Permit Medical (' . $itinerary->medical_type . ')', $itinerary->medical_escort_status);

            $meetings = EmployeeItinerariesMeeting::where('employee_itinerary_id', $itinerary->id)->get();
            foreach ($meetings as $meeting) {
                $addItem($meeting->meeting_date, $meeting->meeting_time, 'Meeting: ' . $meeting->meeting_title, null);
            }

            // Group by calendar date, sorted ascending, and label Day 1, Day 2…
            $byDate = collect($items)->groupBy('date')->sortKeys();
            $days = [];
            $dayNumber = 1;
            foreach ($byDate as $date => $dayItems) {
                $days[] = [
                    'day'   => 'Day ' . $dayNumber,
                    'date'  => $date,
                    'items' => $dayItems->values(),
                ];
                $dayNumber++;
            }

            $now = Carbon::now();
            $endOfWeek = Carbon::now()->endOfWeek();
            $upcomingTasks = collect($items)->filter(function ($item) use ($now, $endOfWeek) {
                return $item['date'] >= $now->toDateString() && $item['date'] <= $endOfWeek->toDateString() && $item['status'] !== 'Completed';
            })->values();

            $assignedTasks = [];
            if ($itinerary->pickup_employee_id) {
                $assignedTasks[] = [
                    'task_type'     => 'pickup',
                    'title'         => 'Pick up at the Airport',
                    'assigned_to'   => optional(Employee::find($itinerary->pickup_employee_id)->resortAdmin ?? null)->full_name,
                    'status'        => $itinerary->pickup_status,
                ];
            }
            if ($itinerary->accompany_medical_employee_id) {
                $assignedTasks[] = [
                    'task_type'     => 'medical',
                    'title'         => 'Escort to Medical Center',
                    'assigned_to'   => optional(Employee::find($itinerary->accompany_medical_employee_id)->resortAdmin ?? null)->full_name,
                    'status'        => $itinerary->medical_escort_status,
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Itinerary timeline retrieved successfully.',
                'data'    => [
                    'days'           => $days,
                    'upcoming_tasks' => $upcomingTasks,
                    'assigned_tasks' => $assignedTasks,
                    'download_all'   => $this->resolveItineraryDownloads($itinerary),
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
     * gm-meeting-schedule "Meetings" screen. Scoped to meetings the caller
     * actually has a stake in: their own onboarding itinerary's meetings, OR
     * any meeting (on any itinerary in this resort) where they're a listed
     * participant — never a resort-wide unfiltered list.
     */
    public function meetings()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $employee = $this->user->GetEmployee;
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found'], 404);
        }

        try {
            $meetings = EmployeeItinerariesMeeting::whereHas('itiernary', function ($q) use ($employee) {
                    $q->where('resort_id', $this->resort_id)
                      ->where(function ($qq) use ($employee) {
                          $qq->where('employee_id', $employee->id)
                             ->orWhere('pickup_employee_id', $employee->id)
                             ->orWhere('accompany_medical_employee_id', $employee->id);
                      });
                })
                ->orWhereRaw("FIND_IN_SET(?, meeting_participant_ids) AND employee_itinerary_id IN (SELECT id FROM employee_itineraries WHERE resort_id = ?)", [$employee->id, $this->resort_id])
                ->get()
                ->map(function ($meeting) {
                    $participantIds = array_filter(explode(',', (string) $meeting->meeting_participant_ids));
                    $participants = Employee::with('resortAdmin')
                        ->whereIn('id', $participantIds)
                        ->where('resort_id', $this->resort_id)
                        ->get()
                        ->map(fn($p) => [
                            'id'   => $p->id,
                            'name' => $p->resortAdmin->full_name ?? '',
                        ]);

                    return [
                        'id'           => $meeting->id,
                        'itinerary_id' => $meeting->employee_itinerary_id,
                        'title'        => $meeting->meeting_title,
                        'date'         => $meeting->meeting_date,
                        'time'         => $meeting->meeting_time,
                        'meeting_link' => $meeting->meeting_link,
                        'participants' => $participants,
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Meetings retrieved successfully.',
                'data'    => $meetings,
            ], 200);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * "Add Meeting Link" action on the Meetings screen. Anyone with a stake
     * in the meeting (itinerary owner, pickup/medical staff, or a listed
     * participant) may set the link — same ownership scope as meetings().
     */
    public function addMeetingLink(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'meeting_id'   => 'required|integer|exists:employee_itineraries_meeting,id',
            'meeting_link' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $employee = $this->user->GetEmployee;
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found'], 404);
        }

        try {
            $meeting = EmployeeItinerariesMeeting::with('itiernary')->find($request->meeting_id);
            $itinerary = $meeting->itiernary;

            if (!$itinerary || (int) $itinerary->resort_id !== (int) $this->resort_id) {
                return response()->json(['success' => false, 'message' => 'Meeting not found'], 404);
            }

            $participantIds = array_filter(explode(',', (string) $meeting->meeting_participant_ids));
            $hasStake = (int) $itinerary->employee_id === (int) $employee->id
                || (int) $itinerary->pickup_employee_id === (int) $employee->id
                || (int) $itinerary->accompany_medical_employee_id === (int) $employee->id
                || in_array((string) $employee->id, $participantIds, true);

            if (!$hasStake) {
                return response()->json(['success' => false, 'message' => 'You are not part of this meeting.'], 403);
            }

            $meeting->meeting_link = $request->meeting_link;
            $meeting->save();

            return response()->json([
                'success' => true,
                'message' => 'Meeting link updated successfully.',
                'data'    => ['id' => $meeting->id, 'meeting_link' => $meeting->meeting_link],
            ], 200);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * HR dashboard for the Onboarding module (mobile). Gate: any employee in
     * the HR department (any rank) — mirrors the isHRDepartment convention
     * TimeAndAttendanceController already uses for its own HR-wide mobile
     * dashboards (resort-wide visibility once you're an HR-department
     * member, not just HR rank/HOD/EXCOM).
     *
     * Onboarding is company-wide, not owned by the arriving employee's own
     * department, so this does NOT further restrict by
     * Common::getScopedDepartmentIds() — every HR-department member sees
     * every itinerary in the resort.
     */
    public function hrDashboard()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $employee = $this->user->GetEmployee ?? null;
        if (!$employee || !Common::isHRDepartment($employee->Dept_id ?? null)) {
            return response()->json(['success' => false, 'message' => 'Forbidden: HR department access only'], 403);
        }

        try {
            $resort_id = $this->resort_id;
            $today = Carbon::today()->format('Y-m-d');

            $itineraries = EmployeeItineraries::with([
                    'employee.resortAdmin',
                    'pickupemployee.resortAdmin',
                    'accompanymedicalemployee.resortAdmin',
                    'meetings',
                ])
                ->where('resort_id', $resort_id)
                ->get();

            $upcomingItineraries = $itineraries->filter(fn($i) => $i->arrival_date >= $today)->values();

            // Assigned Tasks — every pickup + medical-escort assignment across
            // all itineraries, split Completed/Pending by whether the task's
            // own date has passed.
            $completedTasks = 0;
            $pendingTasks = 0;
            $assignedTasks = [];
            foreach ($itineraries as $itinerary) {
                $employeeName = $this->employeeDisplayName($itinerary->employee);

                if ($itinerary->pickup_employee_id) {
                    $isCompleted = $itinerary->arrival_date < $today;
                    $isCompleted ? $completedTasks++ : $pendingTasks++;
                    $assignedTasks[] = [
                        'itinerary_id' => $itinerary->id,
                        'task'         => 'Pick up ' . $employeeName . ' at the Airport',
                        'date'         => $itinerary->arrival_date,
                        'time'         => $itinerary->arrival_time,
                        'assignee'     => $this->employeeDisplayName($itinerary->pickupemployee),
                        'assignee_contact' => optional($itinerary->pickupemployee)->resortAdmin->personal_phone ?? null,
                        'status'       => $isCompleted ? 'Completed' : 'Pending',
                    ];
                }

                if ($itinerary->accompany_medical_employee_id) {
                    $isCompleted = !empty($itinerary->medical_date) && $itinerary->medical_date < $today;
                    $isCompleted ? $completedTasks++ : $pendingTasks++;
                    $assignedTasks[] = [
                        'itinerary_id' => $itinerary->id,
                        'task'         => 'Accompany ' . $employeeName . ' to Medical Center',
                        'date'         => $itinerary->medical_date,
                        'time'         => $itinerary->approx_time,
                        'assignee'     => $this->employeeDisplayName($itinerary->accompanymedicalemployee),
                        'assignee_contact' => optional($itinerary->accompanymedicalemployee)->resortAdmin->personal_phone ?? null,
                        'status'       => $isCompleted ? 'Completed' : 'Pending',
                    ];
                }
            }

            // Upcoming Arrivals list
            $upcomingArrivals = $upcomingItineraries->map(function ($itinerary) {
                $employee = $itinerary->employee;
                $pickup = $itinerary->pickupemployee;
                $medical = $itinerary->accompanymedicalemployee;

                return [
                    'itinerary_id'    => $itinerary->id,
                    'employee_id'     => $employee->id ?? null,
                    'employee_name'   => $this->employeeDisplayName($employee),
                    'employee_photo'  => Common::getResortUserPicture($employee->Admin_Parent_id ?? null),
                    'arrival_date'    => $itinerary->arrival_date,
                    'arrival_time'    => $itinerary->arrival_time,
                    'representatives' => array_values(array_filter([
                        $pickup ? [
                            'role'    => 'Pickup',
                            'name'    => $this->employeeDisplayName($pickup),
                            'contact' => optional($pickup->resortAdmin)->personal_phone,
                        ] : null,
                        $medical ? [
                            'role'    => 'Medical Escort',
                            'name'    => $this->employeeDisplayName($medical),
                            'contact' => optional($medical->resortAdmin)->personal_phone,
                        ] : null,
                    ])),
                    'send_my_selfie_available' => empty($employee->selfie_image ?? null),
                    'flight_ticket_available'  => !empty($itinerary->flight_ticket_file),
                    'status'                   => 'Itinerary Created',
                ];
            })->values();

            // Onboarding Itinerary list — pending tasks count per itinerary
            // (own pickup task + own medical task + own meetings still ahead
            // of today).
            $itineraryList = $itineraries->map(function ($itinerary) use ($today) {
                $pending = 0;
                if ($itinerary->arrival_date >= $today) $pending++;
                if (!empty($itinerary->medical_date) && $itinerary->medical_date >= $today) $pending++;
                $pending += $itinerary->meetings->filter(fn($m) => $m->meeting_date >= $today)->count();

                return [
                    'itinerary_id'        => $itinerary->id,
                    'date'                => $itinerary->arrival_date,
                    'itinerary_title'     => 'Onboarding — ' . $this->employeeDisplayName($itinerary->employee),
                    'pending_tasks_count' => $pending,
                ];
            })->values();

            // Upcoming Meetings list
            $upcomingMeetings = EmployeeItinerariesMeeting::whereHas('itiernary', fn($q) => $q->where('resort_id', $resort_id))
                ->where('meeting_date', '>=', $today)
                ->orderBy('meeting_date')
                ->get()
                ->map(fn($m) => [
                    'meeting_id'   => $m->id,
                    'title'        => $m->meeting_title,
                    'date'         => $m->meeting_date,
                    'time'         => $m->meeting_time,
                    'meeting_link' => $m->meeting_link,
                ])->values();

            return response()->json([
                'success' => true,
                'message' => 'HR onboarding dashboard fetched successfully.',
                'stats' => [
                    'total_upcoming_arrivals' => $upcomingItineraries->count(),
                    'completed_tasks'         => $completedTasks,
                    'pending_tasks'           => $pendingTasks,
                    'average_time_days'       => Common::averageOnboardingLeadDays($resort_id),
                ],
                'upcoming_arrivals'      => $upcomingArrivals,
                'onboarding_itineraries' => $itineraryList,
                'assigned_tasks'         => $assignedTasks,
                'upcoming_meetings'      => $upcomingMeetings,
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * Null-safe "Employee -> resortAdmin -> full_name" lookup shared by the
     * HR dashboard's several list builders above.
     */
    private function employeeDisplayName($employee)
    {
        if (!$employee || !$employee->resortAdmin) {
            return 'Unknown';
        }
        return $employee->resortAdmin->full_name;
    }
}
