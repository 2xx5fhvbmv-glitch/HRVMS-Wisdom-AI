<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee;
use App\Models\ClinicAppointmentCategories;
use App\Models\ClinicAppointment;
use App\Models\ClinicAppointmentAttachment;
use App\Models\ClinicTreatment;
use App\Models\ClinicTreatmentAttachments;
use App\Models\ClinicMedicalCertificate;
use App\Models\EmployeeLeave;
use App\Models\LeaveCategory;
use App\Models\EmployeeLeaveStatus;
use App\Helpers\Common;
use Carbon\Carbon;
use Validator;
use DB;

class ClinicController extends Controller
{
    protected $user;
    protected $resort_id;
    protected $underEmp_id = [];

    public function __construct()
    {

        if (Auth::guard('api')->check()) {
            $this->user                             =   Auth::guard('api')->user();
            $this->resort_id                        =   $this->user->resort_id;
        }
    }

    public function appointmentCategoriesStore(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'appointment_type'                      => 'required',
            'color'                                 => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }
        DB::beginTransaction();
        try {
            $ClinicAppointmentCategories             =   ClinicAppointmentCategories::where('resort_id', $this->resort_id)
                                                            ->where('appointment_type', $request->appointment_type)
                                                            ->first();
            if($ClinicAppointmentCategories){
                return response()->json([
                    'success'                           => false,
                    'message'                           => 'Category already added. Please enter a new category'
                ], 200);
            }
            
            $categories                             =   ClinicAppointmentCategories::create([
                'resort_id'                         => $this->resort_id,
                'appointment_type'                  => $request->appointment_type,
                'color'                             => $request->color,
            ]);
           DB::commit();
            return response()->json([
                'success'                           => true,
                'message'                           => 'Appointment Categories Created Successfully',
                'appointment_category_data'         => $categories
                ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function appointmentCategories()
    {
    
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $categories                             =   ClinicAppointmentCategories::where('resort_id', $this->resort_id)
                                                            ->orderBy('created_at', 'desc')
                                                            ->get();
          
            return response()->json([
                'success'                           => true,
                'message'                           => 'Appointment Categories Fetched Successfully',
                'appointment_category_data'         => $categories
                ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function appointmentStore(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'date'                                  => 'required',
            'time'                                  => 'required',
            'appointment_category_id'               => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }
        $employee_id                                =   $this->user->GetEmployee->id;

        DB::beginTransaction();
        try {
            if ($request->has('appointment_id')) {
                // Update existing appointment
                $appointment                        =   ClinicAppointment::where('id', $request->appointment_id)
                                                                ->where('employee_id', $employee_id)
                                                                ->where('resort_id', $this->resort_id)
                                                                ->first();
                if (!$appointment) {
                    return response()->json([
                        'success'                   =>  false, 
                        'message'                   =>  'Appointment not found'
                    ], 200);
                }

                $appointment->update([
                    'appointment_category_id'       =>  $request->appointment_category_id,
                    'date'                          =>  $request->date,
                    'time'                          =>  $request->time,
                    'description'                   =>  $request->description,
                    'status'                        =>  'Reschedule', // Uncomment if you want to reset status
                ]);
                
                $message                            =   'Appointment updated successfully';

            } else {
                $fetchDoctorId                      =   Employee::where('rank', 12)->first();

                $AppointmentExist                   =   ClinicAppointment::where('employee_id', $employee_id)
                                                            ->where('doctor_id', $fetchDoctorId->id)
                                                            ->where('date', $request->date)
                                                            ->where('time', $request->time)
                                                            ->where('appointment_category_id', $request->appointment_category_id)
                                                            ->where('status', 'Pending')
                                                            ->first();
                if ($AppointmentExist) {
                    return response()->json([
                        'success'                   =>  false, 
                        'message'                   =>  'Appointment already exists for this date and time'
                    ], 200);
                }
                
                // Create new appointment
                $updateCompleteTime                 =   ClinicAppointment::create([
                    'resort_id'                     =>  $this->resort_id,
                    'employee_id'                   =>  $employee_id,
                    'doctor_id'                     =>  $fetchDoctorId->id,
                    'appointment_category_id'       =>  $request->appointment_category_id,
                    'date'                          =>  $request->date,
                    'time'                          =>  $request->time,
                    'description'                   =>  $request->description,
                    'status'                        =>  'Pending',
                ]);

                $message                            =   'Appointment created successfully';

                if($request->hasFile('attachments')) {
                    // Define leave attachment path
                    
                    foreach($request->attachments as $file)
                    {
                        $SubFolder      =   "IncidentAttatchements";
                        $status         =   Common::AWSEmployeeFileUpload($this->resort_id,$file, $this->user->GetEmployee->Emp_id,$SubFolder,true);

                        if ($status['status'] == false) {
                            break;
                        } else {
                            if($status['status'] == true && isset($status['Chil_file_id']) && !empty($status['Chil_file_id'])) {
                                $filename       =   $file->getClientOriginalName();
                                $imagePaths[]   =   ['Filename' => $filename, 'Child_id' => $status['Chil_file_id']];
                            }
                        }

                        
                        ClinicAppointmentAttachment::create([
                            'resort_id'             =>  $this->resort_id,
                            'appointment_id'        =>  $updateCompleteTime->id,
                            'attachment'            =>  $imagePaths? json_encode($imagePaths) : null,
                        ]);
                    }
                }
            }

            // Send In App Notification to each approver
            Common::sendMobileNotification(
                $this->user->resort_id,
                2,
                null,
                null,
                'Appointment Request',
                'An appointment request has been sent by ' . $this->user->first_name . ' ' . $this->user->last_name . '.',
                'Clinic',
                [$fetchDoctorId->id],
                null
            );

            DB::commit();
            return response()->json([
                'success'                           =>  true,
                'message'                           =>  $message
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function employeeClinicDashboard()
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $employee_id                                =   $this->user->GetEmployee->id;

        try {
            $upcomingAppointmentsCount              =   ClinicAppointment::where('employee_id', $employee_id)
                                                            ->whereDate('date', '>=', Carbon::today())
                                                            ->count();

            $nextAppointment                        =   ClinicAppointment::where('employee_id', $employee_id)
                                                            ->join('employees as e', 'e.id', '=', 'clinic_appointment.doctor_id')
                                                            ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                                                            ->whereDate('clinic_appointment.date', '>=', Carbon::today())
                                                            ->orderBy('clinic_appointment.date', 'asc')
                                                            ->orderBy('clinic_appointment.time', 'asc')
                                                            ->whereIn('clinic_appointment.status', ['Pending','Approved','Reschedule'])
                                                            ->get(['clinic_appointment.*', 'ra.first_name as doctor_first_name', 'ra.last_name as doctor_last_name']);

            $medicalIssueCount                      =   ClinicMedicalCertificate::where('employee_id', $employee_id)
                                                                ->where('resort_id', $this->resort_id)
                                                                ->count();

            $leaveRequest                           =   DB::table('employees_leaves as el')
                                                            ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
                                                            ->join('employees as e', 'e.id', '=', 'el.emp_id')
                                                            ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                                                            ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
                                                            ->join('employees_leaves_status as els', 'els.leave_request_id', '=', 'el.id')
                                                            ->where('els.status', 'Pending')
                                                            ->where('els.approver_rank', 12)
                                                            ->where('el.emp_id', $employee_id)
                                                            ->where('el.resort_id', $this->resort_id)
                                                            ->select(
                                                                'el.id',
                                                                'el.emp_id',
                                                                'el.from_date',
                                                                'el.to_date',
                                                                'el.status',
                                                                'el.reason',
                                                                'ra.first_name',
                                                                'ra.last_name',
                                                                'lc.leave_type as leave_category',
                                                                'rp.position_title as position',
                                                            )
                                                            ->orderBy('el.from_date', 'asc')
                                                            ->get();

            $appArray                                   =   [
                'upcoming_appointments_count'           =>  $upcomingAppointmentsCount,
                'next_appointment'                      =>  $nextAppointment,
                'pending_leave_request'                 =>  $leaveRequest,
                'medical_certificate_issue_count'       =>  $medicalIssueCount,
            ];

            return response()->json([                           
                'success'                           =>  true,
                'message'                           =>  'Employee Clinic Dashboard Fetched Successfully',
                'dashboard_data'                    =>  $appArray
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function appointmentDetails($appointment_id)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $appointment_id                             =   base64_decode($appointment_id);

        try {
            $appointment                            =   ClinicAppointment::with(['appointmentCategory',
                                                            'employee:id,Admin_Parent_id,Position_id,dob',
                                                            'employee.resortAdmin:id,first_name,last_name,profile_picture,gender',
                                                            'employee.position:id,position_title',
                                                            ])
                                                            ->where('resort_id', $this->resort_id)
                                                            ->where('id', $appointment_id)
                                                            ->first();
            $age                                    =   null;

            if ($appointment && $appointment->employee && $appointment->employee->dob) {
                $age                                =   Carbon::parse($appointment->employee->dob)->age;
            }

            $appointment->employee_age              =   $age;
            $appointment->employee->resortAdmin->profile_picture =   Common::getResortUserPicture( $appointment->employee->resortAdmin->id);

            return response()->json([
                'success'                           =>  true,
                'message'                           =>  'Appointment Details Fetched Successfully',
                'appointment_data'                  =>  $appointment
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function clinicStaffDashboard() 
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $upcomingAppointmentsCount                  =   ClinicAppointment::whereDate('date', '>=', Carbon::today())->count();

            $treatmentCount                             =   ClinicTreatment::where('resort_id', $this->resort_id)
                                                                ->distinct('employee_id')
                                                                ->count();
           
            $categories                                 =   ClinicAppointmentCategories::select(
                                                                'clinic_appointment_categories.id',
                                                                'clinic_appointment_categories.appointment_type',
                                                                'clinic_appointment_categories.color',
                                                                DB::raw('COUNT(DISTINCT ca.employee_id) as employee_count')
                                                                )->join('clinic_appointment as ca', 'ca.appointment_category_id', '=', 'clinic_appointment_categories.id')
                                                                ->where('clinic_appointment_categories.resort_id', $this->resort_id)
                                                                ->groupBy('clinic_appointment_categories.id', 'clinic_appointment_categories.appointment_type')
                                                                ->get();

            $totalCategoryEmployees                     =   $categories->sum('employee_count');

            $categories                                 =   $categories->map(function($cat) use ($totalCategoryEmployees) {
            $cat->percentage                            =   $totalCategoryEmployees > 0 ? round(($cat->employee_count / $totalCategoryEmployees) * 100) : 0;
                return $cat;
            });

            $appointmentData                            =   ClinicAppointment::join('employees as e', 'e.id', '=', 'clinic_appointment.employee_id')
                                                                ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                                                                 ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
                                                                ->orderBy('clinic_appointment.date', 'asc')
                                                                ->orderBy('clinic_appointment.time', 'asc')
                                                                ->whereNotIn('clinic_appointment.status', ['Cancel', 'Rejected','Medical Certificate'])
                                                                ->where('clinic_appointment.resort_id', $this->resort_id)
                                                                 ->limit(2)
                                                                ->get(['clinic_appointment.*', 'ra.first_name', 'ra.last_name', 'rp.position_title as position']);

            $today                                      =   Carbon::today();
            $startOfWeek                                =   Carbon::now()->startOfWeek();
            $endOfWeek                                  =   Carbon::now()->endOfWeek();
            $startOfMonth                               =   Carbon::now()->startOfMonth();
            $endOfMonth                                 =   Carbon::now()->endOfMonth();

            $medicalCertificateDailyCount               =   ClinicMedicalCertificate::where('resort_id', $this->resort_id)
                                                                ->whereDate('created_at', $today)
                                                                ->count();

            $medicalCertificateWeeklyCount              =   ClinicMedicalCertificate::where('resort_id', $this->resort_id)
                                                                ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
                                                                ->count();

            $medicalCertificateMonthlyCount             =   ClinicMedicalCertificate::where('resort_id', $this->resort_id)
                                                                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                                                                ->count();

            $leaveRequestPendingCount                  =   DB::table('employees_leaves as el')
                                                                ->join('employees_leaves_status as els', 'els.leave_request_id', '=', 'el.id')
                                                                ->where('els.status', 'Pending')
                                                                ->where('els.approver_id', $this->user->GetEmployee->id)
                                                                ->where('el.resort_id', $this->resort_id)
                                                                ->count();

            $leaveRequestApproveCount                   =   DB::table('employees_leaves as el')
                                                                ->join('employees_leaves_status as els', 'els.leave_request_id', '=', 'el.id')
                                                                ->where('els.status', 'Approved')
                                                                ->where('els.approver_id', $this->user->GetEmployee->id)
                                                                ->where('el.resort_id', $this->resort_id)
                                                                ->count();

            $appArray                                   =   [
                'upcoming_appointments_count'           =>  $upcomingAppointmentsCount,
                'medical_history_count'                 =>  $treatmentCount,
                'medical_certificate_daily'             =>  $medicalCertificateDailyCount,
                'medical_certificate_weekly'            =>  $medicalCertificateWeeklyCount,
                'medical_certificate_monthly'           =>  $medicalCertificateMonthlyCount,
                'categories'                            =>  $categories,
                'medical_leave_requests_pending'        =>  $leaveRequestPendingCount,
                'medical_leave_requests_approved'       =>  $leaveRequestApproveCount,
                'appointment_requests'                  =>  $appointmentData,
            ];

            return response()->json([                           
                'success'                               =>  true,
                'message'                               =>  'Employee Clinic Dashboard Fetched Successfully',
                'dashboard_data'                        =>  $appArray
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function appointmentListBasedonFilter(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            
            if ($request->filter == 'today') {
                $date   = [now()->startOfDay(), now()->endOfDay()];
            } elseif ($request->filter == 'weekly') {
                $date   = [now()->startOfWeek(), now()->endOfWeek()];
            } elseif ($request->filter == 'monthly') {
                $date   = [now()->startOfMonth(), now()->endOfMonth()];
            } else {
               $date    = [now()->startOfDay(), now()->endOfDay()];
            }

            $upcomingAppointments                   =   ClinicAppointment::join('employees as e', 'e.id', '=', 'clinic_appointment.employee_id')
                                                            ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                                                            ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
                                                            ->whereBetween('clinic_appointment.date', [$date[0], $date[1]])
                                                            ->orderBy('clinic_appointment.date', 'asc')
                                                            ->orderBy('clinic_appointment.time', 'asc')
                                                            ->whereIn('clinic_appointment.status', ['Pending', 'Reschedule'])
                                                            ->get(['clinic_appointment.*','e.Admin_Parent_id','ra.first_name', 'ra.last_name','rp.position_title','ra.profile_picture'])->map(function ($item) {
                                                                $item->profile_picture = Common::getResortUserPicture($item->Admin_Parent_id);
                                                                return $item;
                                                            });

            return response()->json([
            'success'                               =>  true,
            'message'                               =>  'Appointment List Fetched Successfully',
            'appointments'                          =>  $upcomingAppointments,
            ], 200);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function appointmentAndLeaveList()
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $employee                                       =   $this->user->GetEmployee;
        $emp_id                                         =   $employee->id;
        try {
            
            $treatmentSubquery                          =   ClinicTreatment::select('id')
                                                                ->whereColumn('clinic_treatment.appointment_id', 'clinic_appointment.id')
                                                                ->where('clinic_treatment.resort_id', $this->resort_id)
                                                                ->limit(1);

            $appointmentData                            =   ClinicAppointment::select([
                                                                'clinic_appointment.*',
                                                                'ra.first_name',
                                                                'ra.last_name',
                                                                'rp.position_title as position',
                                                                'cac.appointment_type',
                                                                'e.Admin_Parent_id',
                                                            ])
                                                            ->addSelect(['treatment_id' => $treatmentSubquery]) // Adds the treatment_id using the subquery
                                                            ->join('employees as e', 'e.id', '=', 'clinic_appointment.employee_id')
                                                            ->join('clinic_appointment_categories as cac', 'cac.id', '=', 'clinic_appointment.appointment_category_id')
                                                            ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                                                            ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
                                                            // ->orderBy('clinic_appointment.date', 'asc')
                                                            // ->orderBy('clinic_appointment.time', 'asc')
                                                            ->orderBy('clinic_appointment.created_at', 'desc')
                                                            ->whereNotIn('clinic_appointment.status', ['Cancel', 'Rejected', 'Medical Certificate'])
                                                            ->where('clinic_appointment.resort_id', $this->resort_id)
                                                            ->get()->map(function ($items) {
                                                                $items->profile_picture = Common::getResortUserPicture($items->Admin_Parent_id);
                                                                return $items;
                                                            });


            $leaveRequest                               =  DB::table('employees_leaves as el')
                                                            ->join('employees_leaves_status as els', 'els.leave_request_id', '=', 'el.id')
                                                            ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
                                                            ->join('employees as e', 'e.id', '=', 'el.emp_id')
                                                            ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                                                            ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
                                                            ->where('els.status', 'Pending')
                                                            ->where('el.resort_id',  $this->resort_id)
                                                            ->where('els.approver_id', $emp_id)
                                                            ->where('els.approver_rank', 12)
                                                            ->select(
                                                                'el.id',
                                                                'el.emp_id as employee_id',
                                                                'el.from_date',
                                                                'el.to_date',
                                                                'el.status',
                                                                'el.reason',
                                                                'e.Admin_Parent_id',
                                                                'ra.first_name',
                                                                'ra.last_name',
                                                                'lc.leave_type as leave_category',
                                                                'rp.position_title as position',
                                                                'els.id as emp_leave_status_id',
                                                                'els.approver_rank',
                                                                'els.approver_id',
                                                                'els.approved_at',
                                                                'els.status as approve_status',
                                                            )
                                                            ->orderBy('el.created_at', 'desc')
                                                            ->get()->map(function ($itemData) {
                                                                $itemData->profile_picture = Common::getResortUserPicture($itemData->Admin_Parent_id);
                                                                return $itemData;
                                                            });
            $appArray                                   =   [
                'appointment_requests'                  =>  $appointmentData,
                'leave_request'                         =>  $leaveRequest,
            ];

            return response()->json([                           
                'success'                               =>  true,
                'message'                               =>  'Employee Clinic Dashboard Fetched Successfully',
                'dashboard_data'                        =>  $appArray
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function appointmentStatusUpdate(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'appointment_id'                            => 'required',
            'status'                                    => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        try {
            $appointment                                =   ClinicAppointment::where('id', $request->appointment_id)
                                                                ->where('resort_id', $this->resort_id)
                                                                ->first();
            if (!$appointment) {
                return response()->json([
                    'success'                           =>  false, 
                    'message'                           =>  'Appointment not found'
                ], 200);
            }

            $allowedTransitions                         =   [
            'Pending'                                   =>  ['Approved', 'Cancel', 'Rejected'],
            'Reschedule'                                =>  ['Approved', 'Cancel', 'Rejected'],
            'Approved'                                  =>  ['Treatment'],
            'Treatment'                                 =>  ['Medical Certificate'],
            ];

        $currentStatus                                  =   $appointment->status;
        $newStatus                                      =   $request->status;

        if (!isset($allowedTransitions[$currentStatus]) || !in_array($newStatus, $allowedTransitions[$currentStatus])) {
            return response()->json([
                'success'                               =>  false,
                'message'                               =>  'Invalid status transition from "' . $currentStatus . '" to "' . $newStatus . '"',
            ], 200);
        }

        $appointment->status = $newStatus;
        $appointment->save();


        // Send In App Notification to each approver
        Common::sendMobileNotification(
            $this->user->resort_id,
            2,
            null,
            null,
            'Appointment ' . $newStatus,
            'Your appointment request is' . $newStatus,
            'Clinic',
            [$appointment->employee_id],
            null
        );

            return response()->json([
                'success'                               =>  true,
                'message'                               =>  'Appointment status updated successfully'
            ]);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function treatmentAdd(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'employee_id'                               => 'required',
            'appointment_category_id'                   => 'required',
            'date'                                      => 'required',
            'time'                                      => 'required',
            'treatment_provided'                        => 'required',
            'additional_notes'                          => 'required',
            'priority'                                  => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }
         $filePath                                   =   null;

        DB::beginTransaction();
        try {
            $employee_id                                =   $this->user->GetEmployee->id;

            $treatmentData                              =   ClinicTreatment::create([
                'resort_id'                             =>  $this->resort_id,
                'appointment_id'                        =>  $request->appointment_id ?? null,
                'employee_id'                           =>  $request->employee_id ,
                'appointment_category_id'               =>  $request->appointment_category_id,
                'date'                                  =>  now(),
                'time'                                  =>  now(),
                'treatment_provided'                    =>  $request->treatment_provided,
                'additional_notes'                      =>  $request->additional_notes,
                'external_consultation'                 =>  $request->external_consultation ?? null,
                'priority'                              =>  $request->priority,
            ]);

            if($request->hasFile('attachments')) {
                    $emp_id                             =   Employee::where('id',$request->employee_id)->first();

                    foreach($request->attachments as $file)
                    {
                       $file = $request->file('attachments');

                        $SubFolder                      =   "clinicTreatmentAttachment";
                        $status                         =   Common::AWSEmployeeFileUpload($this->resort_id,$file, $emp_id->Emp_id,$SubFolder,true);

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

                        ClinicTreatmentAttachments::create([
                            'resort_id'             =>  $this->resort_id,
                            'clinic_treatment_id'   =>  $treatmentData->id,
                            'attachment'            =>  $filePath ? json_encode($filePath) : null,
                        ]);
                    }
                }
                
            if($request->appointment_id) {
                $appointment                        =   ClinicAppointment::where('id', $request->appointment_id)
                                                            ->where('resort_id', $this->resort_id)
                                                            ->first();

                $appointment->status                =   'Treatment';
                $appointment->save();
            }
            DB::commit();
            return response()->json([
                'success'                           => true,
                'message'                           => 'Treatment Added Successfully',
                'treatment_data'                    => $treatmentData
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function medicalHistoryList()
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $treatmentData                              =   ClinicTreatment::join('employees as e', 'e.id', '=', 'clinic_treatment.employee_id')
                                                                ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                                                                ->where('clinic_treatment.resort_id', $this->resort_id)
                                                                ->groupBy('clinic_treatment.employee_id')
                                                                ->get(['clinic_treatment.*', 'ra.first_name', 'ra.last_name']);

            return response()->json([
                'success'                               =>  true,
                'message'                               =>  'Treatment Fetched Successfully',
                'treatment_data'                        =>  $treatmentData
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function medicalHistoryDetails($emp_id)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $employeeData                               =   Employee::join('resort_admins as ra', 'ra.id', '=', 'employees.Admin_Parent_id')
                                                                ->join('resort_positions as rp', 'rp.id', '=', 'employees.Position_id')
                                                                ->where('employees.id', $emp_id)
                                                                ->where('employees.resort_id', $this->resort_id)
                                                                ->first(['employees.id','employees.Admin_Parent_id','employees.rank','employees.dob','ra.gender','ra.personal_phone','ra.first_name', 'ra.last_name', 'rp.position_title as position']);

            if (!$employeeData) {
                return response()->json(['success' => false, 'message' => 'Employee not found'], 200);
            }

            $employeeData->profile_picture              =   Common::getResortUserPicture( $employeeData->Admin_Parent_id);
            $rankConfig                                 =   config('settings.Position_Rank');
            $availableRank                              =   $rankConfig[$employeeData->rank] ?? '';
            $employeeData->rank                         =   $availableRank;
            $age                                        =   Carbon::parse($employeeData->dob)->age;
            $employeeData->age                          =   $age;
            $appointmentsCount                          =   ClinicAppointment::where('resort_id', $this->resort_id)
                                                                ->where('employee_id', $emp_id)
                                                                ->count();

            $medicalCertificateCount                    =   ClinicMedicalCertificate::where('resort_id', $this->resort_id)
                                                                ->where('employee_id', $emp_id)
                                                                ->count();
            $treatmentData                              =   ClinicTreatment::join('employees as e', 'e.id', '=', 'clinic_treatment.employee_id')
                                                                ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                                                                ->where('clinic_treatment.resort_id', $this->resort_id)
                                                                ->where('clinic_treatment.employee_id', $emp_id)
                                                                ->get(['clinic_treatment.*', 'ra.first_name', 'ra.last_name']);
        
            $leaveCategory                              =   LeaveCategory::where('resort_id', $this->resort_id)->get()->toArray();
            $totalSickLeaveDays                         =   0;

            foreach ($leaveCategory as $key => $value) {
                
                if (stripos($value['leave_type'], 'sick') !== false) {

                        $leaveRequest                   =  EmployeeLeave::where('status', 'Approved')
                                                                ->where('resort_id', $this->resort_id)
                                                                ->where('emp_id', $emp_id)
                                                                ->where('leave_category_id', $value['id'])
                                                                ->get(['id','total_days','from_date','to_date','leave_category_id']);

                        $totalSickLeaveDays             +=  $leaveRequest->sum('total_days');
                }
            }

            $appointmentTreatmentData                   =   ClinicTreatment::join('employees as e', 'e.id', '=', 'clinic_treatment.employee_id')
                                                                ->where('clinic_treatment.resort_id', $this->resort_id)
                                                                ->where('clinic_treatment.employee_id', $emp_id)
                                                                ->select('clinic_treatment.*')
                                                                ->get()
                                                                ->map(function ($item) {
                                                                    return [
                                                                            'treatment_id'              =>  $item->id,
                                                                            'resort_id'                 =>  $item->resort_id,
                                                                            'appointment_id'            =>  $item->appointment_id,
                                                                            'employee_id'               =>  $item->employee_id,
                                                                            'appointment_category_id'   =>  $item->appointment_category_id,
                                                                            'date'                      =>  $item->date,
                                                                            'time'                      =>  $item->time,
                                                                            'treatment_provided'        =>  $item->treatment_provided,
                                                                            'additional_notes'          =>  $item->additional_notes,
                                                                            'type'                      =>  'appointment'
                                                                        ];
                                                                });

            $medicalCertificateData                     =   ClinicMedicalCertificate::join('employees as e', 'e.id', '=', 'clinic_medical_certificate.employee_id')
                                                                ->where('clinic_medical_certificate.resort_id', $this->resort_id)
                                                                ->where('clinic_medical_certificate.employee_id', $emp_id)
                                                                ->select('clinic_medical_certificate.*')
                                                                ->get()->map(function ($item) {
                                                                   return [
                                                                        'medical_certificate_id'        =>  $item->id,
                                                                        'resort_id'                     =>  $item->resort_id,
                                                                        'appointment_id'                =>  $item->appointment_id,
                                                                        'clinic_treatment_id'           =>  $item->clinic_treatment_id,
                                                                        'employee_id'                   =>  $item->employee_id,
                                                                        'appointment_category_id'       =>  $item->appointment_category_id,
                                                                        'date'                          =>  $item->date,
                                                                        'time'                          =>  $item->time,
                                                                        'description'                   =>  $item->description,
                                                                        'type'                          =>  'medical_certificate'
                                                                    ];
                                                                });
            $mergedData                                 =   $appointmentTreatmentData
            ->merge($medicalCertificateData)
            ->sortBy([
                ['date', 'asc'],
                ['time', 'asc']
            ])
            ->values(); 
          
            $medicalArr                                 =   [
                'employee_data'                         =>  $employeeData,
                'appointments_count'                    =>  $appointmentsCount,
                'medical_certificate'                   =>  $medicalCertificateCount,
                'sick_leave_count'                      =>  $totalSickLeaveDays,
                'history'                               =>  $mergedData,
            ];

            return response()->json([
                'success'                               =>  true,
                'message'                               =>  'Medical history fetched successfully.',
                'medical_history_details'               =>  $medicalArr
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function treatmentDetails($treatment_id)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $treatmentData                              =   ClinicTreatment::join('employees as e', 'e.id', '=', 'clinic_treatment.employee_id')
                                                                ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                                                                ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
                                                                ->join('clinic_appointment_categories as cac', 'cac.id', '=', 'clinic_treatment.appointment_category_id')
                                                                ->where('clinic_treatment.resort_id', $this->resort_id)
                                                                ->where('clinic_treatment.id', $treatment_id)
                                                                ->first(['clinic_treatment.*', 'e.Admin_Parent_id','e.rank','e.dob','ra.gender','ra.personal_phone','ra.first_name', 'ra.last_name', 'rp.position_title as position', 'cac.appointment_type']);

            $treatmentData->profile_picture             =   Common::getResortUserPicture($treatmentData->Admin_Parent_id);
            $rankConfig                                 =   config('settings.Position_Rank');
            $availableRank                              =   $rankConfig[$treatmentData->rank] ?? '';
            $treatmentData->rank                        =   $availableRank;
            $age                                        =   Carbon::parse($treatmentData->dob)->age;
            $treatmentData->age                         =   $age;
            $fetchDoctorName                            =   Employee::join('resort_admins as ra', 'ra.id', '=', 'employees.Admin_Parent_id')
                                                                ->where('employees.rank', 12)
                                                                ->first(['ra.first_name', 'ra.last_name']);

            $MedicalCertificate                         =   ClinicMedicalCertificate::where('resort_id', $this->resort_id)
                                                                ->where('clinic_treatment_id', $treatment_id)
                                                                ->first();
            if ($MedicalCertificate) {
                $treatmentData->medical_certificate_issue   = 'Yes';
            } else {
                $treatmentData->medical_certificate_issue   =   'No';
            }

            $treatmentData->doctor_name                 =   $fetchDoctorName->first_name . ' ' . $fetchDoctorName->last_name;

            $emp_id                                     =   Employee::where('id',$treatmentData->employee_id)->first();

            $clinic_treatement_attachment               =   url(config('settings.clinicTreatmentAttachments'));
            $dynamic_path                               =   $clinic_treatement_attachment . '/' . $this->user->resort->resort_id.'/'.$emp_id->Emp_id;

            $attachments                                =    ClinicTreatmentAttachments::where('clinic_treatment_id', $treatment_id)->select('attachment')
                                                                ->get()->map(function($attachments) use ($dynamic_path) {
                                                                    $attachments->attachment = $dynamic_path.'/'.$attachments->attachment;
                                                                    return $attachments->attachment;
                                                                });
            $treatmentData->attachments                 =   $attachments;
            return response()->json([
                'success'                               =>  true,
                'message'                               =>  'Treatment details fetched successfully',
                'medical_history_details'               =>  $treatmentData
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function treatmentAdditionalNoteUpdate(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $validator = Validator::make($request->all(), [
            'clinic_treatment_id'                       => 'required',
            'additional_notes'                          => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        try {
            $treatmentData                              =   ClinicTreatment::where('resort_id', $this->resort_id)
                                                                ->where('id', $request->clinic_treatment_id)
                                                                ->first();

            if (!$treatmentData) {
                return response()->json([
                'success'                               =>  false,
                'message'                               =>  'Appointment and Treatment not found.'
                ], 200);
            }

            $treatmentData->additional_notes             =   $request->additional_notes;
            $treatmentData->save();
            
            return response()->json([
                'success'                               =>  true,
                'message'                               => 'Additional notes updated successfully.',
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function medicalCertificateStore(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'employee_id'                               =>  'required',
            'start_date'                                =>  'required',
            'end_date'                                  =>  'required',
            'appointment_category_id'                   =>  'required',
            'description'                               =>  'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        if($request->clinic_treatment_id){
        // Check if clinic_treatment_id exists
        $treatmentExists                                =   ClinicTreatment::where('id', $request->clinic_treatment_id)
                                                                ->where('resort_id', $this->resort_id)
                                                                ->exists();

            if (!$treatmentExists) {
                return response()->json([
                    'success'                               =>  false,
                    'message'                               =>  'Invalid clinic_treatment_id. Treatment not found.',
                ], 200);
            }
        }

          $leave                                      =   EmployeeLeave::find($request->leave_request_id);

            if (!$leave) {
                return response()->json([
                    'status'                            =>  'error',
                    'message'                           =>  'Leave request not found.',
                ], 200);
            }
            DB::beginTransaction();
            try {
                $emp_id                                     =   Employee::where('id',$request->employee_id)->first();
                $filePath                                   =   null;

            if($request->hasFile('attachment')) {
                    // Define leave attachment path
                  
                    $file       =   $request->file('attachments');
                    $SubFolder  =   "clinicMedicalCertificateAttachments";
                    $status     =   Common::AWSEmployeeFileUpload($this->resort_id, $file, $emp_id->Emp_id, $SubFolder, true);

                    if ($status['status'] == false) {
                            return response()->json([
                            'success' => false, 
                            'message' => 'File upload failed: ' . ($status['msg'] ?? 'Unknown error')
                        ], 400);
                    } else {
                        if($status['status'] == true && isset($status['Chil_file_id']) && !empty($status['Chil_file_id'])) {
                            $filename = $file->getClientOriginalName();
                            $filePath = ['Filename' => $filename, 'Child_id' => $status['Chil_file_id']];
                        }
                    }
                }

            $treatmentData                              =   ClinicMedicalCertificate::create([
                'resort_id'                             =>  $this->resort_id,
                'appointment_id'                        =>  $request->appointment_id ?? null,
                'clinic_treatment_id'                   =>  $request->clinic_treatment_id?? null,
                'leave_request_id'                      =>  $request->leave_request_id?? null,
                'employee_id'                           =>  $request->employee_id,
                'appointment_category_id'               =>  $request->appointment_category_id,
                'start_date'                            =>  $request->start_date,
                'end_date'                              =>  $request->end_date,
                'description'                           =>  $request->description ?? null,
                'attachment'                            =>  $filePath ? json_encode($filePath) : null,
            ]);
                
            if (!$treatmentData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Appointment and Treatment not found.'
                ], 200);
            }

            if($request->appointment_id) {
                $appointment                                =   ClinicAppointment::where('id', $request->appointment_id)
                                                                    ->where('resort_id', $this->resort_id)
                                                                    ->first();
                                                                    
                $appointment->status                        =   'Medical Certificate';
                $appointment->save();
            }

            if($request->leave_request_id) {
                $leaveApprvEmpIds                   =   EmployeeLeaveStatus::where('leave_request_id', $request->leave_request_id)
                                                                ->where('approver_id','!=', $this->user->GetEmployee->id)
                                                                ->where('status','Pending')
                                                                ->pluck('approver_id')
                                                                ->toArray();
                $empIdFetch                         =   Employee::where('id', $leave->emp_id)->where('status', 'Active')->first();
                    Common::sendMobileNotification(
                        $this->user->resort_id,
                        2,
                        null,
                        null,
                        'Medical Certificate Issued',
                        $empIdFetch->Emp_id . ' Medical Certificate has been issued by ' . $this->user->first_name . ' ' . $this->user->last_name,
                        'Clinic',
                        $leaveApprvEmpIds,
                        null
                    );
            }


            DB::commit();
            return response()->json([
                'success'                               =>  true,
                'message'                               =>  'Medical certificate uploaded successfully.',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function medicalCertificateDetail($medicalCertiId)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $medicalCertificateData                    =   ClinicMedicalCertificate::join('employees as e', 'e.id', '=', 'clinic_medical_certificate.employee_id')
                                                                ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                                                                 ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
                                                                ->join('clinic_appointment_categories as cac', 'cac.id', '=', 'clinic_medical_certificate.appointment_category_id')
                                                                ->where('clinic_medical_certificate.resort_id', $this->resort_id)
                                                                ->where('clinic_medical_certificate.id', $medicalCertiId)
                                                                ->first(['clinic_medical_certificate.*','e.Admin_Parent_id','e.rank','e.dob','ra.gender','ra.first_name', 'ra.last_name', 'cac.appointment_type','rp.position_title as position']);

            if (!$medicalCertificateData) {
                return response()->json(['success' => false, 'message' => 'Medical certificate not found'], 200);
            }
            
            $medicalCertificateData->profile_picture    =   Common::getResortUserPicture($medicalCertificateData->Admin_Parent_id);
            $rankConfig                                 =   config('settings.Position_Rank');
            $availableRank                              =   $rankConfig[$medicalCertificateData->rank] ?? '';
            $medicalCertificateData->rank               =   $availableRank;
            $age                                        =   Carbon::parse($medicalCertificateData->dob)->age;
            $medicalCertificateData->age                =   $age;
            $emp_id                                     =   Employee::where('id',$medicalCertificateData->employee_id)->first();
            $clinic_medical_certi_attachment            =   url(config('settings.clinicMedicalCertificateAttachments'));
            $dynamic_path                               =   $clinic_medical_certi_attachment . '/' . $this->user->resort->resort_id.'/'.$emp_id->Emp_id;

            if($medicalCertificateData->attachment) {
                $medicalCertificateData->attachment    =   $dynamic_path.'/'.$medicalCertificateData->attachment;
            }
            
            return response()->json(['success' => true, 'data' => $medicalCertificateData], 200);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function pastMedicalHistory()
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $employee_id                                    =   $this->user->GetEmployee->id;

        try {
            $appointmentTreatmentData                   =   ClinicTreatment::join('employees as e', 'e.id', '=', 'clinic_treatment.employee_id')
                                                                ->where('clinic_treatment.resort_id', $this->resort_id)
                                                                ->where('clinic_treatment.employee_id', $employee_id)
                                                                ->select('clinic_treatment.*')
                                                                ->get()
                                                                ->map(function ($item) {
                                                                    return [
                                                                            'treatment_id'              =>  $item->id,
                                                                            'resort_id'                 =>  $item->resort_id,
                                                                            'appointment_id'            =>  $item->appointment_id,
                                                                            'employee_id'               =>  $item->employee_id,
                                                                            'appointment_category_id'   =>  $item->appointment_category_id,
                                                                            'date'                      =>  $item->date,
                                                                            'time'                      =>  $item->time,
                                                                            'treatment_provided'        =>  $item->treatment_provided,
                                                                            'additional_notes'          =>  $item->additional_notes,
                                                                            'type'                      =>  'appointment'
                                                                        ];
                                                                });
                                                                
            $medicalCertificateData                     =   ClinicMedicalCertificate::join('employees as e', 'e.id', '=', 'clinic_medical_certificate.employee_id')
                                                                ->where('clinic_medical_certificate.resort_id', $this->resort_id)
                                                                ->where('clinic_medical_certificate.employee_id', $employee_id)
                                                                ->get(['clinic_medical_certificate.*'])
                                                                ->map(function ($item) {
                                                                   return [
                                                                        'medical_certificate_id'        =>  $item->id,
                                                                        'resort_id'                     =>  $item->resort_id,
                                                                        'appointment_id'                =>  $item->appointment_id,
                                                                        'clinic_treatment_id'           =>  $item->clinic_treatment_id,
                                                                        'employee_id'                   =>  $item->employee_id,
                                                                        'appointment_category_id'       =>  $item->appointment_category_id,
                                                                        'date'                          =>  $item->date,
                                                                        'time'                          =>  $item->time,
                                                                        'description'                   =>  $item->description,
                                                                        'type'                          =>  'medical_certificate'
                                                                    ];
                                                                });

            $mergedData                                 =   $appointmentTreatmentData
                                                                ->merge($medicalCertificateData)
                                                                ->sortBy([
                                                                    ['date', 'asc'],
                                                                    ['time', 'asc']
                                                                ])
                                                                ->values(); 
            return response()->json([
                'success'                               =>  true,
                'message'                               =>  'Past Medical History Fetched Successfully',
                'past_medical_history'                  =>  $mergedData
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function clinicStaffLeaveAction(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'leave_id'                                  =>  'required',
            'status'                                    =>  'required|in:Approved,Rejected',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        try {

            $leaveId                                    =   $request->input('leave_id');
            $status                                     =   $request->input('status'); // Approve or Reject
            $leave                                      =   EmployeeLeave::find($leaveId);

            if (!$leave) {
                return response()->json([
                    'status'                            =>  'error',
                    'message'                           =>  'Leave request not found.',
                ], 200);
            }

            // Update the leave request status
             $updated                                   =   EmployeeLeaveStatus::where('leave_request_id', $leave->id)->where('approver_id', $this->user->GetEmployee->id)->where('status','Pending')->update([
                'approver_id'                           =>  $this->user->GetEmployee->id,
                'status'                                =>  $status,
                'approved_at'                           =>  now(),
            ]);

            if ($updated) {

                if($status == 'Rejected') {
                    // Send In App Notification to each approver
                    Common::sendMobileNotification(
                        $this->user->resort_id,
                        2,
                        null,
                        null,
                        'Leave Request',
                        'Your Leave request has been ' . $status . ' by ' . $this->user->first_name . ' ' . $this->user->last_name,
                        'Leave',
                        [$leave->emp_id],
                        null
                    );
                } else {

                    $leaveApprvEmpIds                   =   EmployeeLeaveStatus::where('leave_request_id', $leave->id)
                                                                ->where('approver_id','!=', $this->user->GetEmployee->id)
                                                                ->where('status','Pending')
                                                                ->pluck('approver_id')
                                                                ->toArray();

                    $empIdFetch                         =   Employee::where('id', $leave->emp_id)->where('status', 'Active')->first();
                    
                    // Send In App Notification to each approver
                    Common::sendMobileNotification(
                        $this->user->resort_id,
                        2,
                        null,
                        null,
                        'Leave Request',
                        $empIdFetch->Emp_id . ' Leave request has been ' . $status . ' by ' . $this->user->first_name . ' ' . $this->user->last_name,
                        'Leave',
                        $leaveApprvEmpIds,
                        null
                    );
                }
                
                return response()->json([
                    'success'                           =>  true,
                    'message'                           =>  "Leave has been {$status} successfully.",
                ], 200);

            } else {
                return response()->json([
                    'success'                           =>  false,
                    'message'                           =>  'Failed to update leave status. Maybe already processed.',
                ], 200);
            }

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

}
