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
        if(!$this->resort) return;
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

            // Honour the index-page filters (none of these were applied
            // before — controller dropped department / position / status
            // / search on the floor).
            $deptId     = $request->filled('department_id') ? (int) $request->department_id : null;
            $positionId = $request->filled('position_id')   ? (int) $request->position_id   : null;
            $statusVal  = $request->filled('status')        ? trim((string) $request->status) : null;
            $searchTerm = $request->filled('search_term')   ? trim((string) $request->search_term) : null;

            if ($statusVal) {
                // BUG FIX: the Pending dropdown option was matching only
                // rows with status='Pending' literally, but the badge
                // renderer below falls through `default => Pending` for
                // ANY unknown / null / legacy status value. Result was
                // "3 rows showing Pending badge, filter shows only 1".
                // For Pending, include the same set of values the badge
                // treats as Pending — i.e. anything that isn't one of
                // the explicitly-named buckets.
                if ($statusVal === 'Pending') {
                    $knownStatuses = ['Completed', 'Approved', 'Rejected', 'On Hold', 'In Progress'];
                    $empResignations->where(function ($q) use ($knownStatuses) {
                        $q->whereNull('status')
                          ->orWhere('status', '')
                          ->orWhereNotIn('status', $knownStatuses);
                    });
                } else {
                    $empResignations->where('status', $statusVal);
                }
            }
            if ($deptId) {
                $empResignations->whereHas('employee', fn($q) => $q->where('Dept_id', $deptId));
            }
            if ($positionId) {
                $empResignations->whereHas('employee', fn($q) => $q->where('Position_id', $positionId));
            }
            if ($searchTerm) {
                // Search across the canonical "Employee Name, ID or
                // Manager Name" hint shown in the input placeholder.
                // Manager Name = the employee's reporting supervisor.
                $empResignations->whereHas('employee', function ($q) use ($searchTerm) {
                    $q->where('Emp_id', 'like', "%{$searchTerm}%")
                      ->orWhereHas('resortAdmin', function ($raQ) use ($searchTerm) {
                          $raQ->where('first_name', 'like', "%{$searchTerm}%")
                              ->orWhere('last_name', 'like', "%{$searchTerm}%");
                      })
                      ->orWhereHas('reportingTo.resortAdmin', function ($mgrQ) use ($searchTerm) {
                          $mgrQ->where('first_name', 'like', "%{$searchTerm}%")
                               ->orWhere('last_name', 'like', "%{$searchTerm}%");
                      });
                });
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
        // Eager-load hod + hr (with their resortAdmin) so the show.blade
        // can resolve names without lazy-load round-trips. Without this
        // the relations get lazy-fetched and the chain still works, but
        // it's wasteful — and it doesn't help the rows where hod_id /
        // hr_id were never recorded at create time (see fallback below).
        $employeeResignation = EmployeeResignation::with([
                'employee.resortAdmin',
                'employee.department',
                'employee.position',
                'hod.resortAdmin',
                'hod.position',
                'hr.resortAdmin',
                'hr.position',
            ])
            ->findOrFail($resignationId);

        // Some resignations were created before hod_id / hr_id started
        // being captured (or via a code path that skipped FindResortHOD /
        // FindResortHR). Fall back to resolving them from the employee's
        // current department HOD and the resort's HR so the view shows
        // SOMEONE instead of a blank "HOD Name:" line. We attach the
        // resolved Employees to the resignation as dynamic properties so
        // the existing blade markup keeps working unchanged.
        if (empty($employeeResignation->hod_id) && $employeeResignation->employee) {
            $hodFallback = \App\Helpers\Common::FindResortHODDepartment(
                $this->resort->resort_id,
                $employeeResignation->employee->Dept_id
            );
            if ($hodFallback) {
                $hodFallback->load(['resortAdmin', 'position']);
                $employeeResignation->setRelation('hod', $hodFallback);
            }
        }
        if (empty($employeeResignation->hr_id)) {
            $hrFallback = \App\Helpers\Common::FindResortHR($this->resort);
            if ($hrFallback) {
                $hrFallback->load(['resortAdmin', 'position']);
                $employeeResignation->setRelation('hr', $hrFallback);
            }
        }

        $user = $this->resort->GetEmployee;

        // Authority resolution — mirror what updateStatus actually accepts
        // so the show page can't render Approve/Reject/On Hold buttons
        // that the server would then reject. Three accepted paths:
        //   1. User IS the assigned HOD (rank 2 + id matches hod_id)
        //   2. User IS the assigned HR  (rank 3 + id matches hr_id)
        //   3. User is GM/XCOM (rank 8/1) — senior override; can act as
        //      either HOD or HR. Common pattern across this codebase
        //      where senior leadership unblocks stuck approvals.
        //   4. Delegation authority — covered by updateStatus's
        //      Common::hasDelegationAuthority() check; mirrored here.
        $is_hod = false;
        $is_hr = false;

        if ($user) {
            $rank = (int) ($user->rank ?? 0);
            $userId = $user->id;
            $hodId  = (int) ($employeeResignation->hod_id ?? 0);
            $hrId   = (int) ($employeeResignation->hr_id ?? 0);

            if ($rank === 2 && $userId === $hodId) {
                $is_hod = true;
            }
            if ($rank === 3 && $userId === $hrId) {
                $is_hr = true;
            }
            // GM (rank 8) / EXCOM (rank 1) — senior override.
            if (in_array($rank, [1, 8], true)) {
                if ($employeeResignation->hod_status === 'Pending') {
                    $is_hod = true;
                }
                if ($employeeResignation->hr_status === 'Pending' && $employeeResignation->hod_status === 'Approved') {
                    $is_hr = true;
                }
            }
            // Delegation authority — same check updateStatus does.
            if (!$is_hod && $hodId && \App\Helpers\Common::hasDelegationAuthority($userId, $hodId, $this->resort->resort_id)) {
                $is_hod = true;
            }
            if (!$is_hr && $hrId && \App\Helpers\Common::hasDelegationAuthority($userId, $hrId, $this->resort->resort_id)) {
                $is_hr = true;
            }
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

        // GM (rank 8) / EXCOM (rank 1) senior override — mirrors the
        // show() controller. Lets leadership unblock a resignation when
        // the assigned HOD/HR is unavailable. Without this, the show
        // page renders the buttons (per its own override rule) but the
        // server rejects the click as 403.
        if (in_array((int) $user->rank, [1, 8], true)) {
            if ($employeeResignation->hod_status === 'Pending') {
                $is_hod = true;
            }
            if ($employeeResignation->hr_status === 'Pending' && $employeeResignation->hod_status === 'Approved') {
                $is_hr = true;
            }
        }

        // Delegation authority: if current user is not the HOD/HR but is their delegate
        if (!$is_hod && $employeeResignation->hod_id && \App\Helpers\Common::hasDelegationAuthority($user->id, $employeeResignation->hod_id, $this->resort->resort_id)) {
            $is_hod = true;
        }
        if (!$is_hr && $employeeResignation->hr_id && \App\Helpers\Common::hasDelegationAuthority($user->id, $employeeResignation->hr_id, $this->resort->resort_id)) {
            $is_hr = true;
        }

        // "On Hold" is a non-terminal pause. Either approver can flip
        // the overall row into On Hold while keeping their own
        // hod_status / hr_status as 'Pending' (so they can come back
        // and Approve or Reject later). The required reason lands in
        // the new `hold_reason` column and renders on the show page.
        $isOnHold = $status === 'On Hold';

        if($employeeResignation->hod_status === 'Pending' && $is_hod == true) {
            if ($isOnHold) {
                $employeeResignation->status = 'On Hold';
                $employeeResignation->hold_reason = $request->hold_reason ?? $request->reject_reason;
                // Persist the optional reviewer note alongside the reason
                // so the show page's "comments" trail doesn't lose context
                // when a reviewer types something in the textarea before
                // hitting On Hold.
                if ($request->filled('meeting_comment')) {
                    $employeeResignation->hod_comments = $request->meeting_comment;
                }
                // hod_status stays 'Pending' — HR isn't unblocked until
                // HOD actually approves. The pause is visible via the
                // overall status.
            } else {
                $employeeResignation->hod_status = $status;
                $employeeResignation->hod_meeting_status = 'Completed';
                $employeeResignation->hod_comments = $request->meeting_comment;
                // Clear any prior On Hold state on a fresh decision.
                // Approve unfreezes the row back to 'Pending' so HR can
                // take over; Reject is handled below by the terminal
                // status block and also clears the stale hold_reason.
                if ($employeeResignation->status === 'On Hold') {
                    if ($status === 'Approved') {
                        $employeeResignation->status = 'Pending';
                    }
                    $employeeResignation->hold_reason = null;
                }
            }
            $employeeResignation->save();

            // Notify HR — they're the next stage in the chain. Without
            // this they had to chase the bell list manually to learn
            // that HOD had signed off.
            if ($status === 'Approved' && $employeeResignation->hr_id) {
                $hodName = $user->resortAdmin ? $user->resortAdmin->full_name : 'HOD';
                $empName = $employeeResignation->employee && $employeeResignation->employee->resortAdmin
                    ? $employeeResignation->employee->resortAdmin->full_name
                    : 'employee';
                $msg = "📝 {$hodName} approved the resignation for {$empName}. Please review and approve.";
                $notificationHtml = Common::nofitication(
                    $this->resort->resort_id,
                    10,
                    'Resignation Awaiting HR Approval',
                    $msg,
                    0,
                    $employeeResignation->hr_id,
                    'People'
                );
                event(new \App\Events\ResortNotificationEvent($notificationHtml));
            }
        }elseif($employeeResignation->hr_status === 'Pending' && $is_hr == true) {
            if($employeeResignation->hod_status == 'Approved'){
                if ($isOnHold) {
                    $employeeResignation->status = 'On Hold';
                    $employeeResignation->hold_reason = $request->hold_reason ?? $request->reject_reason;
                    // Same pattern as the HOD branch — persist the
                    // reviewer's optional note alongside the reason.
                    if ($request->filled('meeting_comment')) {
                        $employeeResignation->hr_comments = $request->meeting_comment;
                    }
                } else {
                    $employeeResignation->hr_status = $status;
                    $employeeResignation->hr_meeting_status = 'Completed';
                    $employeeResignation->hr_comments = $request->meeting_comment;
                    $employeeResignation->status = $status;
                    // Approve or Reject is a terminal decision — clear
                    // any lingering On Hold reason so the row reads
                    // consistently if queried later.
                    $employeeResignation->hold_reason = null;
                }
                $employeeResignation->save();
            }else{
                return response()->json(['success' => false, 'message' => 'HOD approval is required before HR can approve.'], 403);
            }
        }else{
            return response()->json(['success' => false, 'message' => 'You are not authorized to update this resignation status.'], 403);
        }

        // On Hold short-circuits the downstream notification block (it
        // isn't a final disposition — the employee shouldn't get an
        // "approved/rejected" ping yet). Notify the OTHER approver
        // instead so they know the row is paused.
        if ($isOnHold) {
            $empName = optional(optional($employeeResignation->employee)->resortAdmin)->full_name ?: 'an employee';
            $actorName = $user->resortAdmin ? $user->resortAdmin->full_name : 'A reviewer';
            $notifyTarget = $is_hod ? $employeeResignation->hr_id : $employeeResignation->hod_id;
            if ($notifyTarget) {
                try {
                    $notificationHtml = Common::nofitication(
                        $this->resort->resort_id,
                        10,
                        'Resignation Put On Hold',
                        "⏸ {$actorName} put the resignation for {$empName} on hold. Reason: " . ($employeeResignation->hold_reason ?: '—'),
                        0,
                        $notifyTarget,
                        'People'
                    );
                    event(new \App\Events\ResortNotificationEvent($notificationHtml));
                } catch (\Throwable $e) { \Log::warning('On Hold notify failed: '.$e->getMessage()); }
            }
            return response()->json(['success' => true, 'message' => 'Resignation put on hold.']);
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
