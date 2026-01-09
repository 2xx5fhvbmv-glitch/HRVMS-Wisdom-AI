<?php
namespace App\Http\Controllers\Resorts\People\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\Models\ExitClearanceFormResponse;
use App\Models\Resort;
use App\Models\Employee;
use App\Models\resortAdmin;
use App\Models\ResortDepartment;
use App\Models\ResortPosition;
use App\Models\ExitClearanceForm;
use App\Models\EmployeeResignation;
use App\Models\ExitClearanceFormAssignment;
use App\Models\ProbationLetterTemplate;
use App\Models\ResignationMeetingSchedule;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Events\ExitClearanceNotificationEvent;
use App\Mail\EmployementCertificateMail;
use Illuminate\Support\Facades\Mail;
use Auth;
use Config;
use Common;
use DB;
use Carbon\Carbon;

class EmployeeResignationController extends Controller 
{
    public $resort;
    
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
    }

    public function index(Request $request)
    {
        
        $page_title = 'Employee Resignation';
        $resort_id = $this->resort->resort_id;  
        $employee = $this->resort->GetEmployee;
        $departments = ResortDepartment::where('resort_id', $resort_id)->where('status','active')->get();
        $positions = ResortPosition::where('resort_id', $resort_id)->where('status','active')->get();
        $templates = ExitClearanceForm::where('resort_id', $resort_id)
            ->where('form_type', 'department')
            ->get();

        if ($request->ajax()) {
            $empResignations = EmployeeResignation::with(['employee.resortAdmin', 'employee.department', 'employee.position'])
                ->where('resort_id', $resort_id);


            if($this->resort->is_master_admin == 0){
                if ($employee->rank == 2) {
                    $empResignations = $empResignations->where('hod_id', $employee->id);
                } elseif ($employee->rank == 3) {
                    $empResignations = $empResignations->where('hr_id', $employee->id);
                }
            }

            $employeeResignations = $empResignations->get();
            
            
            return datatables()->of($employeeResignations)
                ->addColumn('Emp_id', function ($employeeResignation) {
                    return $employeeResignation->employee ? $employeeResignation->employee->Emp_id : 'N/A';
                })
                ->addColumn('employee_name', function ($employeeResignation) {
                    $image = Common::getResortUserPicture($employeeResignation->employee->Admin_Parent_id ?? null);
                    $name = optional(@$employeeResignation->employee->resortAdmin)->full_name;
                   
                    return '<div class="tableUser-block">
                                <div class="img-circle"><img src="' . $image . '" alt="user"></div>
                                <span class="userApplicants-btn">' . ($name ? ucwords($name) : 'N/A') . '</span>
                            </div>';
                })
                ->addColumn('position', function ($employeeResignation) {
                    return $employeeResignation->employee && $employeeResignation->employee->position
                        ? $employeeResignation->employee->position->position_title
                        : 'N/A';
                })
                ->addColumn('department', function ($employeeResignation) {
                    return $employeeResignation->employee && $employeeResignation->employee->department
                        ? $employeeResignation->employee->department->name
                        : 'N/A';
                })
                ->addColumn('resignation_date', function ($employeeResignation) {
                    return $employeeResignation->resignation_date
                        ? \Carbon\Carbon::parse($employeeResignation->resignation_date)->format('d M Y')
                        : 'N/A';
                })
                ->addColumn('last_working_day', function ($employeeResignation) {
                    return $employeeResignation->last_working_day
                        ? \Carbon\Carbon::parse($employeeResignation->last_working_day)->format('d M Y')
                        : 'N/A';
                })
                ->addColumn('status', function ($employeeResignation) {
                    return match ($employeeResignation->status) {
                        'Completed' => '<span class="badge badge-themeSuccess">Completed</span>',
                        'Approved' => '<span class="badge badge-themeSuccess">Approved</span>',
                        'Rejected' => '<span class="badge badge-themeDanger">Rejected</span>',
                        'On Hold'  => '<span class="badge badge-themeSkyblue">On Hold</span>',
                        'In Progress' => '<span class="badge badge-themePrimary">In Progress</span>',
                        default    => '<span class="badge badge-themeWarning">Pending</span>',
                    };
                })
                          
                ->addColumn('action', function ($employeeResignation) {
                    $action_url = route('people.employee-resignation.show', base64_encode($employeeResignation->id));
                    if ($employeeResignation->status === 'Pending') {
                        $user = $this->resort->GetEmployee;
                        $is_hod = false;
                        $is_hr = false;

                        if ($user->rank == 3) {
                            $is_hr = true;
                        }
                        if ($user->rank == 2) {
                            $is_hod = true;
                        }
                        $schedule_status = false;
                        if($is_hod == true && $employeeResignation->hod_meeting_status === 'Not Scheduled'){
                            $schedule_status = true;
                        }elseif($is_hr == true && $employeeResignation->hr_meeting_status === 'Not Scheduled'){
                            $schedule_status = true;
                        }


                        return '
                        <div class="d-flex align-items-center">

                            ' . ($schedule_status ? '<a href="javascript:void(0);" title="Schedule Meeting" class="btn-lg-icon btnIcon-success meeting-schedule" data-id="' . base64_encode($employeeResignation->id) . '">
                                <i class="fa-regular fa-calendar-days"></i>
                            </a>' : '') . '
                            <a href="' . $action_url . '" title="View Resignation Details" class="btn-lg-icon btnIcon-skyblue">
                                <i class="fa-regular fa-eye"></i>
                            </a>
                        </div>';
                    } else {
                        return '<div class="d-flex align-items-center">
                            <a href="' . $action_url . '" title="View Resignation Details" class="btn-lg-icon btnIcon-skyblue">
                                <i class="fa-regular fa-eye"></i>
                            </a>
                        </div>';
                    }
                })
                ->rawColumns(['employee_name', 'status', 'action'])
                ->make(true);
        }

        return view('resorts.people.employee-resignation.index', compact(
            'page_title', 
            'resort_id', 
            'departments',
            'positions',
            'templates'
        ));
    }

    public function show($id)
    {
        if(Common::checkRouteWisePermission('people.employee-resignation.index',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }
        $resignationId = base64_decode($id);
        $employeeResignation = EmployeeResignation::with(['employee.resortAdmin', 'employee.department', 'employee.position'])
            ->findOrFail($resignationId);

        $user = $this->resort->GetEmployee;
       
        $is_hod = false;
        $is_hr = false;

        if ($user->rank == 3) {
            $is_hr = true;
        }
        if ($user->rank == 2) {
            $is_hod = true;
        }

        $page_title = 'Employee Resignation Details';
        return view('resorts.people.employee-resignation.show', compact('page_title', 'employeeResignation','is_hr','is_hod'));
    }


    public function updateStatus(Request $request)
    {

        $resignationId = base64_decode($request->resignation_id);
        $status = $request->status;

        $user = $this->resort->GetEmployee;
        
        $employeeResignation = EmployeeResignation::findOrFail($resignationId);
        $is_hod = false;
        $is_hr = false;

        if ($user->rank == 3 && $employeeResignation->hr_id == $user->id) {
            $is_hr = true;
        }

        if ($user->rank == 2 && $employeeResignation->hod_id == $user->id) {
            $is_hod = true;
        }

        if($employeeResignation->hod_status === 'Pending' && $is_hod == true) {
            $employeeResignation->hod_status = $status;
            $employeeResignation->hod_meeting_status = 'Completed';
            $employeeResignation->hod_comments = $request->meeting_comment;
            $employeeResignation->save();
        }elseif($employeeResignation->hr_status === 'Pending' && $is_hr == true) {
            if($employeeResignation->hod_status == 'Approved'){
                $employeeResignation->hr_status = $status;
                $employeeResignation->hr_meeting_status = 'Completed';
                $employeeResignation->hr_comments = $request->meeting_comment;
                $employeeResignation->status = $status;
                $employeeResignation->save();
            }else{
                return response()->json(['success' => false, 'message' => 'HOD approval is required before HR can approve.'], 403);
            }
        }else{
            return response()->json(['success' => false, 'message' => 'You are not authorized to update this resignation status.'], 403);
        }

        $employee = $employeeResignation->employee;
        if ($employee && $employee->resortAdmin) {
            if ($status === 'Approved') {

                $employeeName = $employee->resortAdmin->full_name;
                $statusText = ucfirst(strtolower($status));
                $message = "Congratulations {$employeeName}, your resignation request has been {$statusText}. Please proceed with the exit clearance process as per HR instructions.";
                $notificationHtml = Common::nofitication(
                    $this->resort->resort_id,
                    10,
                    'Resignation Approved',
                    $message,
                    0,
                    $employee->id, 
                    'People'
                );
                event(new \App\Events\ResortNotificationEvent($notificationHtml));            
                return response()->json(['success' => true, 'message' => 'Employee resignation approved successfully.']);    
            }else{

                $employeeResignation->status = 'Rejected';
                $employeeResignation->rejected_reason = $request->reject_reason;
                $employeeResignation->save();

                $employeeName = $employee->resortAdmin->full_name;
                $statusText = ucfirst(strtolower($status));
                $message = "Your resignation request has been {$statusText}.";
                $notificationHtml = Common::nofitication(
                    $this->resort->resort_id,
                    10,
                    'Resignation Status Update',
                    $message,
                    0,
                    $employee->id, // Send to employee
                    'People'
                );
                event(new \App\Events\ResortNotificationEvent($notificationHtml));
                return response()->json(['success' => true, 'message' => 'Employee resignation rejected successfully.']);
            }
        }else{
            return response()->json(['success' => false, 'message' => 'Employee not found or invalid resignation ID.'], 404);
        }

        
    }

    public function scheduleMeeting(Request $request)
    {
        $resignationId = base64_decode($request->resignationId);
       
        $employeeResignation = EmployeeResignation::findOrFail($resignationId);

        $user = $this->resort->GetEmployee;
        $is_hod = false;
        $is_hr = false;
        $meeting_date = Carbon::createFromFormat('d/m/Y', $request->meetingDate)->format('Y-m-d');
        $meeting_time = Carbon::createFromFormat('H:i', $request->meetingTime)->format('H:i:s');

        if ($user->rank == 2) {
            $is_hod = true;
            $meeting_type = 'HOD';
        }
        if ($user->rank == 3) {
            $is_hr = true;
            $meeting_type = 'HR';
        }
       
        if($is_hod == false && $is_hr == false) {
            return response()->json(['success' => false, 'message' => 'You are not authorized to schedule a meeting.']);
        }

        if($is_hr == true && $employeeResignation->hod_meeting_status !== 'Completed') {
            return response()->json(['success' => false, 'message' => 'HOD meeting must be completed before scheduling HR meeting.']);
        }

        $scheduleMeeting = [
            'resignationId' => $employeeResignation->id,
            'title' => $request->meetingTitle,
            'meeting_date' => $meeting_date,   
            'meeting_time' => $meeting_time,
            'meeting_with' => $meeting_type,
            'status' => 'Pending',
            'created_by' => $user->id,  
        ];

        $scheduleMeeting = ResignationMeetingSchedule::create($scheduleMeeting);
        if ($scheduleMeeting) {
            if($meeting_type == 'HOD') {
                $employeeResignation->hod_meeting_status = 'Scheduled';
            } else {
                $employeeResignation->hr_meeting_status = 'Scheduled';
            }  
             $employeeResignation->save();
            $message = "Your meeting has been scheduled with " . $meeting_type . " successfully.";
            $notificationHtml = Common::nofitication(
                $this->resort->resort_id,
                10,
                'Meeting Scheduled',
                $message,
                0,
                $employeeResignation->employee->id, 
                'People'
            );
            event(new \App\Events\ResortNotificationEvent($notificationHtml));
            return response()->json(['success' =>true , 'message' => 'Meeting scheduled successfully.']);
        } else {
            return response()->json(['success' => false, 'message' => 'Failed to schedule meeting.']);
        }        
    }
}
