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
        
        $validator = Validator::make($request->all(), [
            'reason_type'                           => 'required',
            'resignation_letter'                    => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png',
            'comments'                              => 'required',
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

            
            $resignation                                =   EmployeeResignation::create([
                'resort_id'                             =>  $this->resort_id,
                'employee_id'                           =>  $this->user->GetEmployee->id,
                'reason'                                =>  $request->input('reason_type'),
                'last_working_day'                      =>  $request->input('last_working_day')?? null,
                'resignation_date'                      =>  now(),
                'immediate_release'                     =>  $request->input('immediate_release')?? 'No',
                'resignation_letter'                    =>  $filePath ? json_encode($filePath) : null,
                'comments'                              =>  $request->input('comments'),
                'status'                                =>  'pending',
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
            
            $hrEmployee                                 =   Common::FindResortHR($this->user);

            if ($hrEmployee) {
                Common::sendMobileNotification(
                    $this->resort_id,
                    2,
                    null,
                    null,
                    'Resignation',
                    'A resignation request has been submitted by ' . $this->user->first_name . ' ' . $this->user->last_name . '.',
                    'Resignation',
                    [$hrEmployee->id],
                    null,
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
            $ExitClearanceFormAssignment->save();

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
}