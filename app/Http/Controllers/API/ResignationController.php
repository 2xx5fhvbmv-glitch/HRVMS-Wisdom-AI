<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\EmployeeResignationReason;
use App\Models\EmployeeNoticePeriod;
use App\Models\EmployeeResignationWithdrawalConfig;
use App\Models\ExitClearanceFormAssignment;
use App\Models\ExitClearanceFormResponse;
use App\Models\ResignationMeetingSchedule;
use App\Models\Employee;
use App\Helpers\Common;
use Validator;
use DB;
use App\Models\EmployeeResignation;

class ResignationController extends Controller
{
    protected $user;
    protected $resort_id;
    protected $underEmp_id = [];

    public function __construct()
    {
        if (Auth::guard('api')->check()) {
            $this->user                                 =   Auth::guard('api')->user();
            $this->resort_id                            =   $this->user->resort_id;
        }
    }
    
    public function resignationDashboard()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $employeeResignationReasons                 =   EmployeeResignationReason::where('resort_id', $this->resort_id)
                                                                ->where('status', 'Active')
                                                                ->get();

            $employeeNoticePeriod                       =   EmployeeNoticePeriod::where('resort_id', $this->resort_id)
                                                                ->get();

            $resignations                               =   EmployeeResignation::where('resort_id', $this->resort_id)
                                                                ->where('status', '!=', 'Withdraw')
                                                                ->where('employee_id',  $this->user->GetEmployee->id)
                                                                ->first();
                                                     
            $employeeResignationWithdrawalConfig        =   EmployeeResignationWithdrawalConfig::where('resort_id', $this->resort_id)
                                                                ->first();

            if($resignations) {
            
                $employeeForm                           =   ExitClearanceFormAssignment::join('exit_clearance_form as exf', 'exf.id', '=', 'exit_clearance_form_assignments.form_id')
                                                                ->where('exit_clearance_form_assignments.resort_id', $this->resort_id)
                                                                ->where('exit_clearance_form_assignments.emp_resignation_id',  $resignations->id)
                                                                ->where('exit_clearance_form_assignments.assigned_to_type', 'employee')
                                                                ->get(['exit_clearance_form_assignments.*', 'exf.form_name', 'exf.form_structure','exf.type']);               

                $ResignationMeetingSchedule             =   ResignationMeetingSchedule::where('resignationId', $resignations->id)
                                                                ->get();
            }

            $relativePath                               =   config('settings.experienceLetters') . '/' . $this->user->resort->resort_id . '/' . $this->user->GetEmployee->Emp_id . '/' . $this->user->GetEmployee->Emp_id . '.pdf';
            $absolutePath                               =   public_path($relativePath);

            $employmentCertificate                      =   null;
            if (file_exists($absolutePath)) {
                // Generate a URL for the file
                $employmentCertificate = asset($relativePath);
            }

            return response()->json([
                'success'                               =>  true,
                'message'                               =>  'Resignation dashboard retrieved successfully.',
                'data'                                  =>  [
                    'resignation'                       =>  $resignations ?? null,
                    'withdrawal_config'                 =>  $employeeResignationWithdrawalConfig ?? null,
                    'exit_clearance_form'               =>  $employeeForm ?? null,
                    'resignation_meeting_schedule'      =>  $ResignationMeetingSchedule ?? null,
                    'resignation_reasons'               =>  $employeeResignationReasons ?? null,
                    'notice_periods'                    =>  $employeeNoticePeriod ?? null,
                    'employment_certificate'            =>  $employmentCertificate,
                ],
            ], 200);

        // Fetch resignation dashboard data
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function resignationStore(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        
        // Normalize last_working_day BEFORE validation. The mobile app
        // sends it in local d/m/Y or d/m/Y H:i format (seen in the live
        // log: "31/05/2026 23:15", "25/05/2026 15:09" — both rejected by
        // Laravel's `date` rule because strtotime can't disambiguate
        // d/m/Y when day > 12, and downstream Carbon::parse() throws
        // "Unexpected character"). Try the common formats in order, fall
        // back to whatever the client sent so validation still surfaces
        // a clear error for genuinely malformed input.
        $lwdRaw = $request->input('last_working_day');
        if (is_string($lwdRaw) && $lwdRaw !== '') {
            foreach (['d/m/Y H:i', 'd/m/Y H:i:s', 'd/m/Y', 'Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d'] as $fmt) {
                try {
                    $parsed = \Carbon\Carbon::createFromFormat($fmt, $lwdRaw);
                    if ($parsed && $parsed->format($fmt) === $lwdRaw) {
                        $request->merge(['last_working_day' => $parsed->format('Y-m-d')]);
                        break;
                    }
                } catch (\Throwable $e) {
                    // Try the next format.
                }
            }
        }

        // Normalize immediate_release to the canonical 'Yes' / 'No'
        // strings the DB column expects. The mobile app currently sends
        // booleans / lowercase strings / numerics — `in:Yes,No` rejected
        // all of them and surfaced "The selected immediate release is
        // invalid" on the resignation form (Deepika Iyer screenshot).
        // Accept the common truthy/falsy variants and coerce to a
        // canonical value; absent / null falls through to the 'No'
        // default applied at insert time.
        $irRaw = $request->input('immediate_release');
        if ($irRaw !== null && $irRaw !== '') {
            $truthy = ['Yes','yes','YES','true','True','TRUE','1', 1, true];
            $falsy  = ['No','no','NO','false','False','FALSE','0', 0, false];
            if (in_array($irRaw, $truthy, true)) {
                $request->merge(['immediate_release' => 'Yes']);
            } elseif (in_array($irRaw, $falsy, true)) {
                $request->merge(['immediate_release' => 'No']);
            }
            // Anything else still hits the `in:Yes,No` rule below and
            // returns a validation error — that's the desired behaviour
            // for genuinely unknown payloads.
        }

        $validator = Validator::make($request->all(), [
            'reason_type'                           => 'required',
            'resignation_letter'                    => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,heic,heif',
            'comments'                              => 'required',
            // After normalization above, last_working_day is in Y-m-d.
            // Keep the date rule as a final guard against truly bogus
            // input the format loop couldn't make sense of.
            'last_working_day'                      => 'nullable|date',
            'immediate_release'                     => 'nullable|in:Yes,No',
        ]);

        if ($validator->fails()) {
           return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }
        DB::beginTransaction();

        try {
            // Validate input
            $filePath                                   = null;
            // Handle file upload if present
            if($request->hasFile('resignation_letter')) {
                // Define leave attachment path
                $file       =   $request->file('resignation_letter');
                $SubFolder  =   "ResignationAttachments";
                $status     =   Common::AWSEmployeeFileUpload($this->resort_id,$file, $this->user->GetEmployee->Emp_id,$SubFolder,true);

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
           }

            $hodEmployee                                =   Common::FindResortHODDepartment($this->resort_id, $this->user->GetEmployee->Dept_id);
            $hrEmployee                                 =   Common::FindResortHR($this->user);

            // Guard against missing approver assignments. The previous code
            // dereferenced $hodEmployee->id / $hrEmployee->id directly, which
            // threw a generic "Server error" 500 when the employee's
            // department had no HOD configured or the resort had no HR
            // employee in the People → Configuration → HR list. Surface a
            // specific message so the mobile app can route the user back to
            // HR for setup instead of leaving them staring at a generic
            // failure toast.
            if (!$hodEmployee) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No HOD is configured for your department. Please ask HR to assign a Head of Department before resigning.',
                ], 422);
            }
            if (!$hrEmployee) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No HR contact is configured for this resort. Please contact HR directly to submit your resignation.',
                ], 422);
            }

            $resignation                                =   EmployeeResignation::create([
                'resort_id'                             =>  $this->resort_id,
                'employee_id'                           =>  $this->user->GetEmployee->id,
                'reason'                                =>  $request->input('reason_type'),
                'last_working_day'                      =>  $request->input('last_working_day')?? null,
                'resignation_date'                      =>  now(),
                'immediate_release'                     =>  $request->input('immediate_release')?? 'No',
                'resignation_letter'                    =>  $filePath ? json_encode($filePath) : null,
                'comments'                              =>  $request->input('comments'),
                // Capital 'Pending' matches the convention used by the
                // web-side EmployeeResignationController (and PHP's ===
                // comparisons throughout the People module). The old
                // lowercase 'pending' worked for SQL WHERE thanks to
                // case-insensitive collation but silently missed strict
                // PHP comparisons (dashboard counts, status badge match).
                'status'                                =>  'Pending',
                // Explicit defaults rather than relying on the MySQL
                // enum default — column-default behaviour shifts between
                // MySQL/MariaDB versions and migrations.
                'hod_status'                            =>  'Pending',
                'hr_status'                             =>  'Pending',
                'hod_id'                                =>  $hodEmployee->id,
                'hr_id'                                 =>  $hrEmployee->id,
                'departure_arrangements'                =>  [
                                                                "documentVerifed" => "0",
                                                                "passport_validity" => "0",
                                                                "international_flight" => "0",
                                                                "accommodation_arranged" => "0",
                                                                "transportation_arranged" => "0"
                                                            ],
            ]);
            DB::commit();

            // Two-stage approval flow → HOD approves first, then HR.
            // Notify HOD primarily (they're the gate); notify HR too
            // so they know it's coming (HR's actionable bell fires
            // again the moment HOD signs off — see
            // EmployeeResignationController::updateStatus). Prior code
            // only pinged HR, leaving HOD to discover the request via
            // the resignation list page.
            $empName = trim(($this->user->first_name ?? '') . ' ' . ($this->user->last_name ?? '')) ?: 'an employee';

            if ($hodEmployee) {
                Common::sendMobileNotification(
                    $this->resort_id,
                    2,
                    null,
                    null,
                    'Resignation',
                    "📝 {$empName} has submitted a resignation request. Please review and approve to forward it to HR.",
                    'Resignation',
                    [$hodEmployee->id],
                    $resignation->id,
                    false,
                    'resignation-request-hod',
                );
                // Bell notification for the web dashboard (matches the
                // pattern used by EmployeeResignationController when HOD
                // → HR handoff fires).
                try {
                    $notificationHtml = Common::nofitication(
                        $this->resort_id,
                        10,
                        'New Resignation Request',
                        "📝 {$empName} has submitted a resignation request. Please review.",
                        0,
                        $hodEmployee->id,
                        'People'
                    );
                    event(new \App\Events\ResortNotificationEvent($notificationHtml));
                } catch (\Throwable $e) {
                    \Log::warning('Resignation HOD bell notification failed: ' . $e->getMessage());
                }
            }

            // $hrEmployee was already resolved above and guarded — reuse it
            // instead of hitting FindResortHR a second time per submit.
            if ($hrEmployee) {
                Common::sendMobileNotification(
                    $this->resort_id,
                    2,
                    null,
                    null,
                    'Resignation',
                    "📝 {$empName} has submitted a resignation request. It will reach you for final approval once HOD signs off.",
                    'Resignation',
                    [$hrEmployee->id],
                    $resignation->id,
                    false,
                    'resignation-request-hr',
                );
            }

            // Commit the transaction

            return response()->json([
                'success'                               =>  true,
                'message'                               =>  'Resignation submitted successfully.',
                'data'                                  =>  $resignation,
            ], 201);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function resignationWithdraw(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        // Validate input
        $validator = Validator::make($request->all(), [
            'resignation_id'                            => 'required',
            'withdraw_reason'                           => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            $resignation                                =   EmployeeResignation::where('id', $request->input('resignation_id'))
                                                                ->where('employee_id', $this->user->GetEmployee->id)
                                                                ->first();
            if (!$resignation) {
                return response()->json(['success' => false, 'message' => 'Resignation not found or already processed'], 200);
            }
            // Update resignation status to Withdraw
            $resignation->status                        =   'Withdraw';
            $resignation->withdraw_reason               =   $request->input('withdraw_reason');
            $resignation->save();
            // Commit the transaction
            DB::commit();
            return response()->json([
                'success'                               =>  true,
                'message'                               =>  'Resignation withdrawn successfully.',
                'data'                                  =>  $resignation,
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function formSubmit(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        // Validate input
        $validator = Validator::make($request->all(), [
            'assignment_id'                           => 'required',
            'response_data'                           => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {

            $resignation                                =   ExitClearanceFormResponse::create([
                'assignment_id'                         =>  $request->input('assignment_id'),
                'response_data'                         =>  json_encode($request->input('response_data')),
                'submitted_by'                          =>  $this->user->GetEmployee->id,
                'submitted_date'                        =>  now(),
            ]);

            $ExitClearanceFormAssignment                =   ExitClearanceFormAssignment::find($request->input('assignment_id'));

            if (!$ExitClearanceFormAssignment) {
                return response()->json([
                    'success'                           =>  false,
                    'message'                           =>  'Form assignment not found'
                ],200);
            }

            $ExitClearanceFormAssignment->status        =   'Completed';
            // Tag the channel that closed the form so HR can tell at a
            // glance whether the employee submitted on mobile or HR/HOD
            // marked it in-browser. Guard with hasColumn so this still
            // works on environments where the 2026_06_01_150000 migration
            // hasn't been run yet.
            if (\Illuminate\Support\Facades\Schema::hasColumn('exit_clearance_form_assignments', 'completed_via')) {
                $ExitClearanceFormAssignment->completed_via = 'mobile';
            }
            $ExitClearanceFormAssignment->save();

            // Notify HR (and the resignation owner) that the employee has
            // submitted their exit interview form so HR can review and
            // progress the offboarding. Was previously silent — HR had to
            // refresh the page to discover the response had landed.
            try {
                $resignationRow = \App\Models\EmployeeResignation::with('employee.resortAdmin')
                    ->find($ExitClearanceFormAssignment->emp_resignation_id);
                if ($resignationRow) {
                    $hrId = $resignationRow->hr_id;
                    if (empty($hrId)) {
                        // Resignations created via paths that skipped
                        // FindResortHR end up with hr_id = NULL. Fall back
                        // to the resort HR so we don't drop the ping.
                        $resortAdmin = Auth::guard('api')->user();
                        $hrFallback = $resortAdmin ? Common::FindResortHR($resortAdmin) : null;
                        $hrId = optional($hrFallback)->id;
                    }
                    if ($hrId) {
                        $empName = optional(optional($resignationRow->employee)->resortAdmin)->full_name ?: 'employee';
                        $msg = "📋 {$empName} has submitted their exit interview form. Please review the responses.";
                        $notificationHtml = Common::nofitication(
                            $this->resort_id,
                            10,
                            'Exit Interview Form Submitted',
                            $msg,
                            0,
                            (int) $hrId,
                            'People'
                        );
                        event(new \App\Events\ResortNotificationEvent($notificationHtml));
                    }
                }
            } catch (\Throwable $e) {
                \Log::warning('Exit interview form submit notify failed: ' . $e->getMessage());
            }

            // Commit the transaction
            DB::commit();
            return response()->json([
                'success'                               =>  true,
                'message'                               =>  'Form submited successfully.',
                'data'                                  =>  $resignation,
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function empConfirmMeeting(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Validate input
        $validator = Validator::make($request->all(), [
            'resignation_id'                            => 'required',
            'status'                                    => 'required|in:Employee Schedule Confirm',
            'type'                                      => 'required|in:HR,HOD',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            $EmployeeResignation                =   EmployeeResignation::find($request->input('resignation_id'));
            if (!$EmployeeResignation) {
                return response()->json(['success' => false, 'message' => 'Resignation not found'], 200);
            }

            if($request->input('type') == 'HR') {
                $EmployeeResignation->hr_meeting_status  =   $request->input('status');
            }

            if($request->input('type') == 'HOD') {
                $EmployeeResignation->hod_meeting_status =   $request->input('status');
            }

            $EmployeeResignation->save();

            // Commit the transaction
            DB::commit();
            return response()->json([
                'success'                               =>  true,
                'message'                               =>  'Meeting scheduled Confirm successfully.',
                'data'                                  =>  $EmployeeResignation,
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * Dynamic exit-interview questionnaire for the employee's active
     * resignation. Reuses the existing ExitClearanceForm/Assignment
     * infra (form type='exit_interview') rather than a new questions
     * table — resignationDashboard() already returns ALL assigned
     * exit-clearance forms (exit_interview + exit_clearance + handover
     * mixed together); this narrows to just the exit_interview one and
     * includes any already-submitted response (so the mobile app can
     * resume a draft instead of resubmitting from scratch).
     */
    public function exitInterviewQuestions()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $resignation                                 =   EmployeeResignation::where('resort_id', $this->resort_id)
                                                                ->where('status', '!=', 'Withdraw')
                                                                ->where('employee_id', $this->user->GetEmployee->id)
                                                                ->first();

            if (!$resignation) {
                return response()->json(['success' => false, 'message' => 'No active resignation found.'], 200);
            }

            $assignment                                  =   ExitClearanceFormAssignment::join('exit_clearance_form as exf', 'exf.id', '=', 'exit_clearance_form_assignments.form_id')
                                                                ->where('exit_clearance_form_assignments.resort_id', $this->resort_id)
                                                                ->where('exit_clearance_form_assignments.emp_resignation_id', $resignation->id)
                                                                ->where('exit_clearance_form_assignments.assigned_to_type', 'employee')
                                                                ->where('exf.type', 'exit_interview')
                                                                ->select('exit_clearance_form_assignments.*', 'exf.form_name', 'exf.form_structure')
                                                                ->first();

            if (!$assignment) {
                return response()->json(['success' => false, 'message' => 'No exit interview form assigned.'], 200);
            }

            $existingResponse                            =   ExitClearanceFormResponse::where('assignment_id', $assignment->id)
                                                                ->orderBy('id', 'desc')
                                                                ->first();

            return response()->json([
                'success'                               =>  true,
                'message'                               =>  'Exit interview questions fetched successfully.',
                'data'                                  =>  [
                    'assignment_id'                      =>  $assignment->id,
                    'form_name'                           =>  $assignment->form_name,
                    'form_structure'                      =>  json_decode($assignment->form_structure, true),
                    'status'                              =>  $assignment->status,
                    'existing_response'                   =>  $existingResponse ? json_decode($existingResponse->response_data, true) : null,
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
     * Rejection (and hold) details for the employee's resignation —
     * rejected_reason/hold_reason already exist on employee_resignation
     * and are already in EmployeeResignation::$fillable, just never
     * exposed via a dedicated endpoint before now.
     */
    public function rejectionDetails()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $resignation                                 =   EmployeeResignation::where('resort_id', $this->resort_id)
                                                                ->where('employee_id', $this->user->GetEmployee->id)
                                                                ->orderBy('id', 'desc')
                                                                ->first();

            if (!$resignation) {
                return response()->json(['success' => false, 'message' => 'No resignation found.'], 200);
            }

            return response()->json([
                'success'                               =>  true,
                'message'                               =>  'Resignation status details fetched successfully.',
                'data'                                  =>  [
                    'status'                              =>  $resignation->status,
                    'hod_status'                          =>  $resignation->hod_status,
                    'hr_status'                           =>  $resignation->hr_status,
                    'rejected_reason'                     =>  $resignation->rejected_reason,
                    'hold_reason'                         =>  $resignation->hold_reason,
                ],
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }
}