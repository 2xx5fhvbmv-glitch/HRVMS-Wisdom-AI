<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Employee;
use App\Models\Resort;
use App\Models\ResortAdmin;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use App\Models\EmployeeLeave;
use App\Models\EmployeeLeaveStatus;
use App\Models\LeaveCategory;
use App\Models\ResortDepartment;
use App\Models\ResortBenifitGrid;
use App\Models\ResortBenifitGridChild;
use App\Models\ResortTransportation;
use App\Models\EmployeeTravelPass;
use App\Models\EmployeeTravelPassStatus;
use App\Models\EmployeesLeaveTransportation;
use App\Models\EmployeeResignation;
use App\Models\ResortHoliday;
use App\Models\Compliance;
use App\Models\ResortPosition;
use App\Helpers\Common;
use Carbon\Carbon;
use Validator;
use File;
use Auth;
use DB;

class LeaveController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function leaveAdd(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Validate request data
        $validator = Validator::make($request->all(), [
            'leave_category_id'         => 'required|array',
            'leave_category_id.*'       => 'required|integer',
            'from_date'                 => 'required|array',
            'from_date.*'               => 'required|date_format:Y-m-d',
            'to_date'                   => 'required|array',
            'to_date.*'                 => 'required|date_format:Y-m-d',
            'reason'                    => 'required|string',
            'task_delegation'           => 'required|integer',

            // Conditional validation for transportation
            'transportation'            => 'nullable|array',
            'transportation.*'          => 'nullable|integer',

            // Conditional fields for transportation
            'trans_arrival_date'        => 'required_with:transportation|array',
            'trans_arrival_date.*'      => 'required_with:transportation.*|date_format:Y-m-d',
            'trans_departure_date'      => 'required_with:transportation|array',
            'trans_departure_date.*'    => 'required_with:transportation.*|date_format:Y-m-d',

            // Additional fields for transportation and destination
            'dept_date'                 => 'required_with_all:destination,transportation|date_format:Y-m-d',
            'dept_time'                 => 'required_with_all:destination,transportation|date_format:H:i',
            'dept_transportation'       => 'required_with_all:destination,transportation|integer',
            'arrival_date'              => 'required_with_all:destination,transportation|date_format:Y-m-d',
            'arrival_time'              => 'required_with_all:destination,transportation|date_format:H:i',
            'arrival_transportation'    => 'required_with_all:destination,transportation|integer',
            'dept_reason'               => 'required_with_all:destination,transportation',
            'arrival_reason'            => 'required_with_all:destination,transportation',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        $user                                           =   Auth::guard('api')->user();
        $employee                                       =   $user->GetEmployee;
        $emp_id                                         =   $employee->id;
        $rank                                           =   $employee->rank;
        $resortId                                       =   $user->resort_id;

        try {
            // Start a database transaction
            DB::beginTransaction();

            // Validate transportation ID
            $transportationId                           =   $request->transportation;

            if (isset($transportationId)) {
                if (!ResortTransportation::where('id', $transportationId)->exists()) {
                    return response()->json([
                        'success'                       =>  false,
                        'message'                       =>  'Invalid transportation ID provided.',
                    ], 200);
                }
            }

            // Get the leave category IDs from the request
            $leaveCategoryIds                           =   $request->leave_category_id;

            // Handle the case for single-category leave applications
            if (count($leaveCategoryIds) == 1) {
                // Fetch the leave category from the database
                $categories                             =   LeaveCategory::whereIn('id', $leaveCategoryIds)->where('resort_id',$user->resort_id)->get();
                if ($categories->isEmpty()) {
                    return response()->json([
                        'success'                       =>  false,
                        'message'                       =>  'Invalid leave category selected.'
                    ], 200);
                }
            } else {
                // Fetch the leave categories for the selected IDs
                $categories                             =   LeaveCategory::whereIn('id', $leaveCategoryIds)->where('resort_id',$user->resort_id)->get();

                // Check if there are exactly 2 categories and if they can be combined
                if ($categories->count() != 2) {
                    return response()->json([
                        'success'                       => false,
                        'message'                       => 'The selected leave categories are not combined with each other.'
                    ], 200);
                }

                $firstCategory                          = $categories->first();
                $secondCategory                         = $categories->last();

                // Validate that the categories can be combined
                if (!(
                    ($firstCategory->combine_with_other  == 1 && $firstCategory->leave_category  == $secondCategory->id) ||
                    ($secondCategory->combine_with_other == 1 && $secondCategory->leave_category == $firstCategory->id)
                )) {
                    return response()->json([
                        'success'                       => false,
                        'message'                       => 'The selected leave categories are not combined with each other.'
                    ], 200);
                }
            }

            // Define leave attachment path
            $leave_attachment                           =   config('settings.leave_attachments');
            $dynamic_path                               =   $leave_attachment . '/' . $emp_id;

            // Create the directory if it doesn't exist
            if (!Storage::exists($dynamic_path)) {
                Storage::makeDirectory($dynamic_path);
            }

            // Handle file upload if any attachments are provided
            $filePath                                   =   null;
            if ($request->hasFile('attachments')) {
                $file = $request->file('attachments');

                // Check storage driver configuration
                $storageDriver = config('filesystems.default');

                if ($storageDriver === 's3') {
                    // AWS S3 Storage - Ensure folder structure exists in database
                    $employeeFolderName                     =   $employee->Emp_id;
                    $SubFolder                              =   "LeaveAttachments";

                    // Check if the employee's main folder exists
                    $employeeFolder                         =   DB::table('filemangement_systems')
                                                                    ->where('resort_id', $resortId)
                                                                    ->where('Folder_Name', $employeeFolderName)
                                                                    ->where('Folder_Type', 'categorized')
                                                                    ->first();

                    // If the employee folder doesn't exist, create it
                    if (!$employeeFolder) {
                        $employeeFolderId                   =   DB::table('filemangement_systems')->insertGetId([
                            'resort_id'                     =>  $resortId,
                            'Folder_unique_id'              =>  \Illuminate\Support\Str::random(10),
                            'UnderON'                       =>  0,
                            'Folder_Name'                   =>  $employeeFolderName,
                            'Folder_Type'                   =>  'categorized',
                            'created_by'                    =>  null,
                            'modified_by'                   =>  null,
                            'created_at'                    =>  now(),
                            'updated_at'                    =>  now(),
                        ]);

                        // Retrieve the created folder
                        $employeeFolder                     =   DB::table('filemangement_systems')
                                                                    ->where('id', $employeeFolderId)
                                                                    ->first();
                    }

                    // Check if the LeaveAttachments subfolder exists
                    $leaveAttachmentsFolder                 =   DB::table('filemangement_systems')
                                                                    ->where('resort_id', $resortId)
                                                                    ->where('UnderON', $employeeFolder->id)
                                                                    ->where('Folder_Name', $SubFolder)
                                                                    ->first();

                    // If the LeaveAttachments subfolder doesn't exist, create it
                    if (!$leaveAttachmentsFolder) {
                        DB::table('filemangement_systems')->insert([
                            'resort_id'                     =>  $resortId,
                            'Folder_unique_id'              =>  \Illuminate\Support\Str::random(10),
                            'UnderON'                       =>  $employeeFolder->id,
                            'Folder_Name'                   =>  $SubFolder,
                            'Folder_Type'                   =>  'categorized',
                            'created_by'                    =>  null,
                            'modified_by'                   =>  null,
                            'created_at'                    =>  now(),
                            'updated_at'                    =>  now(),
                        ]);
                    }

                    $status =   Common::AWSEmployeeFileUpload($resortId,$file, $employee->Emp_id,$SubFolder,true);

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
                } else {
                    // Local Storage
                    $leave_attachment                       =   config('settings.leave_attachments');
                    $dynamic_path                           =   $leave_attachment . '/' . $emp_id;

                    // Create the directory if it doesn't exist
                    if (!Storage::exists($dynamic_path)) {
                        Storage::makeDirectory($dynamic_path);
                    }

                    // Store the file locally
                    $filename                               =   time() . '_' . $file->getClientOriginalName();
                    $file->storeAs($dynamic_path, $filename);
                    $filePath                               =   $dynamic_path . '/' . $filename;
                }
            }

            // Process each leave category and create leave records
            foreach ($request->leave_category_id as $key => $categoryId) {
                $leaveDetails                           =   LeaveCategory::where('id', $categoryId)->where('resort_id',$user->resort_id)->first();

                $currentFlag                            =   null;
                if ($leaveDetails->combine_with_other == 1) {
                    $currentFlag                        =   $leaveDetails->leave_category;
                }

                $fromDate                               =   Carbon::parse($request->from_date[$key]);
                $toDate                                 =   Carbon::parse($request->to_date[$key]);


                // Validate the date range
                if ($toDate->lt($fromDate)) {
                    return response()->json([
                        'success'                       =>  false,
                        'message'                       =>  'To date must be the same or after from date for leave category ID ' . $categoryId,
                    ], 200);
                }

                // Calculate the total days for the leave
                $totalDays                              =   $fromDate->diffInDays($toDate) + 1;

                $leaveCategory                          =   DB::table('leave_categories')->where('id', $categoryId)->first();
                if (!$leaveCategory) {
                    return response()->json([
                        'status'                        =>  'error',
                        'message'                       =>  "Leave category with ID $categoryId does not exist.",
                    ]);
                }

                // Get the employee grade and leave balances
                $emp_grade                              =   Common::getEmpGrade($rank);

                // Fetch the benefit grid (allocated days) for the employee's grade and rank
                $benefit_grid                           =   DB::table('resort_benifit_grid as rbg')
                                                            ->join('resort_benefit_grid_child as rbgc', 'rbg.id', '=', 'rbgc.benefit_grid_id')
                                                            ->where('rbg.emp_grade', $emp_grade)
                                                            ->where('rbgc.rank', $rank)
                                                            ->where('rbgc.leave_cat_id', $categoryId)
                                                            ->where('rbg.resort_id', $resortId)
                                                            ->select('rbgc.allocated_days')
                                                            ->first();

                if (!$benefit_grid) {
                    return response()->json([
                        'status'                        =>  'error',
                        'message'                       =>  "No benefit grid found for this employee's rank and leave category.",
                    ]);
                }

                $allocatedDays                          =   $benefit_grid->allocated_days;

                // Get the total used days for the current leave category within the current year
                $currentYearStart                       =   Carbon::now()->startOfYear()->format('Y-m-d');
                $currentYearEnd                         =   Carbon::now()->endOfYear()->format('Y-m-d');

               $approvedLeaves                         =    DB::table('employees_leaves')
                                                                ->where('emp_id', $emp_id)
                                                                ->where('resort_id', $user->resort_id)
                                                                ->where('status', 'Approved')
                                                                ->where('leave_category_id', $categoryId)
                                                                ->where(function ($query) use ($currentYearStart, $currentYearEnd) {
                                                                    $query->whereBetween('from_date', [$currentYearStart, $currentYearEnd])
                                                                        ->orWhereBetween('to_date', [$currentYearStart, $currentYearEnd]);
                                                                })
                                                                ->sum('total_days');

                // Get pending leave requests for this category to prevent double booking
                $pendingLeaves                          =   DB::table('employees_leaves')
                                                                ->where('emp_id', $emp_id)
                                                                ->where('resort_id', $user->resort_id)
                                                                ->where('status', 'Pending')
                                                                ->where('leave_category_id', $categoryId)
                                                                ->where('id', '!=', $request->leave_id ?? 0) // Exclude current leave if it's an update
                                                                ->where(function ($query) use ($currentYearStart, $currentYearEnd) {
                                                                    $query->whereBetween('from_date', [$currentYearStart, $currentYearEnd])
                                                                        ->orWhereBetween('to_date', [$currentYearStart, $currentYearEnd]);
                                                                })
                                                                ->sum('total_days');

                $usedDays                               =   $approvedLeaves;
                $pendingDays                            =   $pendingLeaves;
                $availableDays                          =   $allocatedDays - $usedDays - $pendingDays;

                // Check if the requested leave exceeds the available days
                if ($totalDays > $availableDays) {
                    return response()->json([
                        'status'                        =>  false,
                        'message'                       =>  "You can only apply for {$availableDays} days in the {$leaveCategory->leave_type} category. You have already used or applied for " . ($usedDays + $pendingDays) . " days out of your {$allocatedDays} allocated days.",
                    ]);
                }

                // Process optional departure and arrival dates
                $departure_date                         =   $request->departure_date ? $request->departure_date : null;
                $arrival_date                           =   $request->arrival_date ? $request->arrival_date : null;
                $existingLeave                          =   EmployeeLeave::where('emp_id', $emp_id)
                                                                ->whereDate('from_date', $fromDate)
                                                                ->whereDate('to_date', $toDate)
                                                                ->first();

                if (stripos($leaveDetails->leave_type, 'birthday') !== false) {
                    // Check if a birthday leave already exists for the employee
                    $birthdayLeaveExists                =   EmployeeLeave::where('emp_id', $emp_id)
                                                                ->whereHas('leaveCategory', function ($query) {
                                                                    $query->where('leave_type', 'like', '%birthday%');
                                                                })
                                                                ->exists();
                    if ($birthdayLeaveExists) {
                        return response()->json([
                            'success'                   =>  false,
                            'message'                   =>  'Birthday leave can only be applied once.',
                        ], 200);
                    }
                }

                if ($existingLeave) {
                    if ($existingLeave->leave_category_id != $categoryId) {
                        return response()->json([
                            'success'                   =>  false,
                            'message'                   =>  'Leave already exists with a different category for this date range.',
                        ], 200);
                    }

                    return response()->json([
                        'success'                       =>  false,
                        'message'                       =>  'Leave already exists for this date range.',
                    ], 200);
                }

                $leaveExistDate                         =   EmployeeLeave::where('emp_id', $emp_id)
                                                                ->where(function ($query) use ($fromDate, $toDate) {
                                                                    $query->where(function ($q) use ($fromDate, $toDate) {
                                                                        $q->where('from_date', '<=', $toDate)
                                                                        ->where('to_date', '>=', $fromDate);
                                                                    });
                                                                })
                                                                ->exists();
                if ($leaveExistDate) {
                    return response()->json([
                        'success'                       => false,
                        'message'                       => 'Leave already exists with overlapping date range.',
                    ], 200);
                }

                // Create the leave record in the database
                $leave                                  =   EmployeeLeave::create([
                    'resort_id'                         =>  $user->resort_id,
                    'emp_id'                            =>  $emp_id,
                    'leave_category_id'                 =>  $categoryId,
                    'from_date'                         =>  $fromDate,
                    'to_date'                           =>  $toDate,
                    'flag'                              =>  $currentFlag,
                    'total_days'                        =>  $totalDays,
                    'reason'                            =>  $request->reason,
                    'task_delegation'                   =>  $request->task_delegation,
                    'destination'                       =>  $request->destination,
                    'attachments'                       =>  $filePath ? json_encode($filePath) : null,
                    'status'                            =>  "Pending",
                ]);

                // Check for holidays within the leave period
                $LeaveOverlapHoliday = $this->processLeaveWithHolidayCheck($user->resort_id,$emp_id,$fromDate,$toDate,'LeaveOverlapHoliday');
                $CheckEmployeeNoticePeriod = $this->processLeaveWithHolidayCheck($user->resort_id,$emp_id,$fromDate,$toDate,'CheckEmployeeNoticePeriod');

                if ($transportationId) {

                    foreach ($transportationId as $key  => $transportMode) {


                        // Ensure all fields exist for the current index
                        if (isset($request->trans_arrival_date[$key], $request->trans_departure_date[$key])) {
                            // Transportation Entry
                            $entryPass                  = EmployeesLeaveTransportation::create([
                                'leave_request_id'      => $leave->id,
                                'transportation'        => $transportMode,
                                'trans_arrival
                                _date'    => \Carbon\Carbon::createFromFormat('Y-m-d', $request->trans_arrival_date[$key]),
                                'trans_departure_date'  => \Carbon\Carbon::createFromFormat('Y-m-d', $request->trans_departure_date[$key]),
                            ]);
                        }
                    }
                }

                $passApprovalFlow                       =   collect();


                // Add Security Manager (SM) to the approval flow (rank 4)
                $securityManagerTitles                  =   ['Security Manager', 'SM'];

                // Get position IDs that match the titles in the current resort
                $positionIds                            =   ResortPosition::where('resort_id', $user->resort_id)
                                                                ->whereIn('position_title', $securityManagerTitles)
                                                                ->pluck('id'); // Get the position IDs

                // Get employees who hold these positions in the current resort
                $SMApprover                             =   Employee::with(['resortAdmin','position'])->whereIn('Position_id', $positionIds)
                                                                ->where('resort_id', $user->resort_id)->select('id', 'rank')
                                                                ->first();

                if ($SMApprover) {
                    $passApprovalFlow->push($SMApprover); // Fourth approver: Security Officer
                }

                // Add HR to the approval flow (rank 3)
                $hrApprover                             =   Employee::select('id', 'rank')->where('resort_id',$user->resort_id)->where('rank', 3)->first();
                if ($hrApprover) {
                    $passApprovalFlow->push($hrApprover); // Third approver: HR
                }

                // Add HOD to the approval flow (rank 2)
                $hodApprover                             =   Employee::select('id', 'rank')->where('rank', 2)->where('resort_id',$user->resort_id)->where('Dept_id', $employee->Dept_id)->first();
                if ($hodApprover ) {
                    $passApprovalFlow->push($hodApprover); // Second approver: HOD
                }

                if (isset($request->arrival_date, $request->dept_date)) {
                    $boardingPass                       =   EmployeeTravelPass::create([
                        'resort_id'                     =>  $user->resort_id,
                        'employee_id'                   =>  $employee->id,
                        'leave_request_id'              =>  $leave->id,
                        'arrival_mode'                  =>  $request->arrival_transportation ?? null,  // Set transportation based on arrival or departure
                        'arrival_date'                  =>  $request->arrival_date ? \Carbon\Carbon::createFromFormat('Y-m-d', $request->arrival_date) : null,
                        'arrival_time'                  =>  $request->arrival_time,
                        'arrival_reason'                =>  $request->arrival_reason,
                        'departure_date'                =>  $request->dept_date ? \Carbon\Carbon::createFromFormat('Y-m-d', $request->dept_date) : null,
                        'departure_time'                =>  $request->dept_time,
                        'departure_mode'                =>  $request->dept_transportation ?? null, // Set transportation based on arrival or departure
                        'departure_reason'              =>  $request->dept_reason,
                        'status'                        =>  'Pending',
                    ]);
                }

                if (!empty($boardingPass)) {
                    foreach ($passApprovalFlow as $approverFlw) {
                        EmployeeTravelPassStatus::create([
                            'travel_pass_id'            =>  $boardingPass->id,
                            'approver_id'               =>  $approverFlw->id,
                            'approver_rank'             =>  $approverFlw->rank,
                            'status'                    =>  'Pending',
                        ]);
                    }
                }
                $approvalFlow                           =   collect(); // Store the approval flow dynamically


                $findSickLeaveCategory                  =   LeaveCategory::where('leave_type', 'LIKE', '%Sick%')
                                                                ->where('resort_id',$user->resort_id)
                                                                ->first();

                $leaveCount                             =   EmployeeLeave::where('emp_id', $emp_id)
                                                                ->where('leave_category_id', $findSickLeaveCategory->id)
                                                                ->where('total_days', '1')
                                                                ->whereYear('from_date', Carbon::now()->year)
                                                                ->count();

                // Get the clinic staff if the leave type is sick
                $getClinicStaff                         =   Common::findClinicStaff($employee->resort_id);

                if (stripos($leaveDetails->leave_type, 'sick') !== false && $totalDays >= 2) {
                    if ($getClinicStaff) {
                        $approvalFlow->push($getClinicStaff); // Clinic staff approves
                    }
                }

                if (stripos($leaveDetails->leave_type, 'Maternity') !== false && $totalDays >= 30) {
                    if ($getClinicStaff) {
                        $approvalFlow->push($getClinicStaff); // Clinic staff approves
                    }
                }

                if($leaveCount > 15) {
                    if($fromDate == $toDate) {
                        if ($getClinicStaff) {
                            $approvalFlow->push($getClinicStaff); // Clinic staff approves
                        }
                    }
                }

                $directReportingManagerId               =   $employee->reporting_to;

                if($rank != "1" || $rank != "3" || $rank != "8") { // If not EXCOM, HR, or GM
                    $directReporting                        =   Employee::where('id', $directReportingManagerId)
                                                                    ->where('resort_id',$user->resort_id)
                                                                    ->where('Dept_id', $employee->Dept_id)
                                                                    ->first();
                    if ($directReporting) {
                        $approvalFlow->push($directReporting); // First approver: Supervisor/Manager
                    }
                }

                // Helper to get EXCOM, HOD, HR
                $getApprover                            =   function($deptId, $rank,$resortId) {
                                                                return Employee::select('id', 'rank')
                                                                    ->where('resort_id',$resortId)
                                                                    ->where('Dept_id', $deptId)
                                                                    ->where('rank', $rank)
                                                                    ->first();
                                                            };

                $getHR                                  =   function($resortId) {
                                                                return Employee::select('id', 'rank')->where('rank', 3)->where('resort_id',$resortId)->first();
                                                            };

                $getGM                                  =   function($resortId) {
                                                                return Employee::select('id', 'rank')->where('rank', 8)->where('resort_id',$resortId)->first();
                                                            };


                // Line worked and supervisor applied leave
                if($rank === "6" || $rank === "5") {

                    $findEXCOM                          =   $getApprover($employee->Dept_id, 1,$resortId);
                    if ($findEXCOM) {
                        $approvalFlow->push($findEXCOM); // Only EXCOM approves
                    } else {
                        $findHOD                    =   $getApprover($employee->Dept_id, 2,$resortId);
                        if ($findHOD) {
                            $approvalFlow->push($findHOD); // Only HOD approves if EXCOM not found
                        }
                    }

                    if ($findHR = $getHR($resortId)) {
                        $approvalFlow->push($findHR); // Only HR approves
                    }
                }

                // MGR applied leave
                if ($rank === "4") {

                    $findEXCOM                          =   $getApprover($employee->Dept_id, 1,$resortId);
                    if ($findEXCOM) {
                        $approvalFlow->push($findEXCOM); // Only EXCOM approves
                    }

                    if ($findHR = $getHR($resortId)) {
                        $approvalFlow->push($findHR); // Only HR approves
                    }
                }

                // HOD and GM applied leave
                if ($rank === "2" || $rank === "8") {
                    if ($findHR = $getHR($resortId)) {
                        $approvalFlow->push($findHR); // Only HR approves
                    }
                }

                // EXCOM & HR applied leave
                if ($rank === "1" || $rank === "3") {
                    if ($findGM = $getGM($resortId)) {
                        $approvalFlow->push($findGM); // Only GM approves
                    }
                }

                // Log the approvers for the leave request
                foreach ($approvalFlow as $approver) {
                    $EmployeeLeaveStatusData = EmployeeLeaveStatus::create([
                        'leave_request_id'              =>  $leave->id,
                        'approver_rank'                 =>  $approver->rank,
                        'approver_id'                   =>  $approver->id,
                        'status'                        =>  'Pending',
                    ]);

                    // Send In App Notification to each approver
                    Common::sendMobileNotification(
                       $user->resort_id,
                       2,
                        null,
                        null,
                        'Leave Request',
                        'A request has been sent by ' . $user->first_name . ' ' . $user->last_name . '.',
                        'Leave',
                        [$approver->id],
                        $leave->id
                    );
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Leave application submitted successfully!'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error("Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred. Please try again later.',
            ], 500);
        }
    }

    public function leaveCategory()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $user                                       =   Auth::guard('api')->user();
            $employee                                   =   $user->GetEmployee;
            $emp_id                                     =   $employee->id;
            $resort_id                                  =   $user->resort_id;
            $gender                                     =   $user->gender;

            $targetRanks                                =   [
                array_search('HOD', config('settings.Position_Rank')),
                array_search('MGR', config('settings.Position_Rank')),
                array_search('GM', config('settings.Position_Rank')),
                array_search('HR', config('settings.Position_Rank'))
            ];

            if ($user->is_master_admin == 0) {

                $religion                               =   $employee->religion;
                if ($religion == "1") {
                    $religion = "muslim";
                }

                $rank                                   =   $employee->rank;
                $emp_grade                              =   Common::getEmpGrade($rank);
                $benefit_grid                           =   Common::getBenefitGrid($emp_grade, $resort_id);

                if (!$benefit_grid) {
                    return response()->json([
                        'success'                       =>  false,
                        'message'                       =>  'Leave Category is not found.',
                        'leave_category'                =>  [],
                    ], 200);
                }

                $leave_categories                       =   ResortBenifitGridChild::select(
                                                                'resort_benefit_grid_child.*',
                                                                'lc.leave_type',
                                                                'lc.color',
                                                                'lc.leave_category',
                                                                'lc.combine_with_other',
                                                                DB::raw("(SELECT SUM(el.total_days)
                                                                                FROM employees_leaves el
                                                                                WHERE el.emp_id = {$emp_id}
                                                                                AND el.leave_category_id = lc.id
                                                                                AND el.status = 'Approved'
                                                                                AND (
                                                                                    (el.from_date BETWEEN '" . Carbon::now()->startOfYear()->startOfMonth()->format('Y-m-d') . "' AND '" . Carbon::now()->endOfYear()->endOfMonth()->format('Y-m-d') . "')
                                                                                    OR
                                                                                    (el.to_date BETWEEN '" . Carbon::now()->startOfYear()->startOfMonth()->format('Y-m-d') . "' AND '" . Carbon::now()->endOfYear()->endOfMonth()->format('Y-m-d') . "')
                                                                                )
                                                                                ) as total_leave_days")
                                                            )
                                                            ->join('leave_categories as lc', 'lc.id', '=', 'resort_benefit_grid_child.leave_cat_id')
                                                            ->where('resort_benefit_grid_child.rank', $benefit_grid->emp_grade)
                                                            ->where('resort_benefit_grid_child.benefit_grid_id', $benefit_grid->id)
                                                            ->where('lc.resort_id', $resort_id)
                                                            ->where(function ($query) use ($religion, $gender) {
                                                                $query->where('resort_benefit_grid_child.eligible_emp_type', $gender)
                                                                    ->orWhere('resort_benefit_grid_child.eligible_emp_type', 'all');
                                                                if ($religion == 'muslim') {
                                                                    $query->orWhere('resort_benefit_grid_child.eligible_emp_type', $religion);
                                                                }
                                                            })
                                                            ->get()
                                                            ->map(function ($item) {
                                                                $item->combine_with_other = $item->combine_with_other ?? 0;
                                                                $item->leave_category = $item->leave_category ?? 0;
                                                                $item->total_leave_days =  (int) ($item->total_leave_days ?? 0); // Default if null
                                                                return $item;
                                                            });
            } else {

                $benefit_grid                           =   ResortBenifitGrid::where('emp_grade', 1)
                                                                ->where('resort_id', $resort_id)
                                                                ->where('status', 'active') // Ensure to check for active status
                                                                ->first();

                $leave_categories                       =   ResortBenifitGridChild::select(
                                                                'resort_benefit_grid_child.*',
                                                                'lc.leave_type',
                                                                'lc.color',
                                                                'lc.leave_category',
                                                                'lc.combine_with_other',
                                                                DB::raw('SUM(el.total_days) as total_days') // Use DB::raw for aggregation
                                                            )
                                                            ->join('leave_categories as lc', 'lc.id', '=', 'resort_benefit_grid_child.leave_cat_id')
                                                            ->leftJoin('employees_leaves as el', 'el.leave_category_id', '=', 'lc.id')
                                                            ->where('resort_benefit_grid_child.rank', $benefit_grid->emp_grade)
                                                            ->where(function ($query) use ($gender) { // Pass $gender into the closure
                                                                $query->where('resort_benefit_grid_child.eligible_emp_type', $gender)
                                                                    ->orWhere('resort_benefit_grid_child.eligible_emp_type', 'all');
                                                            })
                                                            ->where('lc.resort_id', $resort_id)
                                                            ->groupBy('el.leave_category_id') // Correct the method name to groupBy
                                                            ->get()
                                                            ->map(function ($item) {
                                                                $item->combine_with_other = $item->combine_with_other ?? 0;
                                                                $item->leave_category = $item->leave_category ?? 0;
                                                                return $item;
                                                            });
            }

            //format leave type
            $leave_categories                           =   $leave_categories->map(function ($item) {
                if (preg_match('/leaves$/i', $item->leave_type)) {
                    $item->leave_type                   =   preg_replace('/leaves$/i', 'Leave', $item->leave_type);
                }
                return $item;
            });

            if ($leave_categories->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'Leave Category is not found.'], 200);
            }

            return response()->json(['success' => true, 'message' => 'Leave Category Listing.', 'leave_category' => $leave_categories], 200);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function taskDelegation()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $user                                       =   Auth::guard('api')->user();
            $employee                                   =   $user->GetEmployee;
            $emp_id                                     =   $employee->id;
            $resort_id                                  =   $user->resort_id;
            $gender                                     =   $user->gender;

            $targetRanks                                =   [
                array_search('HOD', config('settings.Position_Rank')),
                array_search('MGR', config('settings.Position_Rank')),
                array_search('GM',  config('settings.Position_Rank')),
                array_search('HR',  config('settings.Position_Rank'))
            ];

            if ($user->is_master_admin == 0) {

                $reporting_to                           =   $employee->id;
                $underEmp_id                            =   Common::getSubordinates($reporting_to);

                $Dept_id                                =   $employee->Dept_id;
                $delegations                            =   DB::table('employees')
                                                                ->join('resort_admins', 'employees.Admin_Parent_id', '=', 'resort_admins.id')
                                                                ->where('employees.resort_id', $resort_id)
                                                                ->whereIn('employees.rank', $targetRanks)
                                                                // ->whereIn('employees.id', $underEmp_id)
                                                                ->where(function ($query) use ($Dept_id) {
                                                                    $query->where('employees.rank', array_search('HOD', config('settings.Position_Rank')))
                                                                        ->where('employees.Dept_id', $Dept_id)
                                                                        ->orWhere('employees.rank', '<>', array_search('HOD', config('settings.Position_Rank')));
                                                                })
                                                                ->select(
                                                                    'employees.*',
                                                                    'resort_admins.first_name as first_name',
                                                                    'resort_admins.last_name as last_name',
                                                                    'resort_admins.email as admin_email'
                                                                )
                                                                ->get();
            } else {

                $delegations                            =   DB::table('employees')
                                                                ->join('resort_admins', 'employees.Admin_Parent_id', '=', 'resort_admins.id')
                                                                ->where('employees.resort_id', $resort_id)
                                                                ->whereIn('employees.rank', $targetRanks)
                                                                ->select(
                                                                    'employees.*',
                                                                    'resort_admins.first_name as first_name',
                                                                    'resort_admins.last_name as last_name',
                                                                    'resort_admins.email as admin_email'
                                                                )->get();
            }

            if ($delegations->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'No delegations found.'], 200);
            }

            return response()->json(['success' => true, 'message' => 'Delegations Listing.', 'delegations' => $delegations], 200);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function transportations()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $user                                       =   Auth::guard('api')->user();
            $resort_id                                  =   $user->resort_id;
            $transportations                            =   ResortTransportation::where('resort_id', $resort_id)->get()->toArray();

            return response()->json([
                'success'                               =>  true,
                'message'                               =>  'Transportations Listing.',
                'transportations'                       =>  $transportations
            ], 200);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function leaveDashboard()
    {
        $user                                           =   Auth::guard('api')->user();
        $employee                                       =   $user->GetEmployee;
        $emp_id                                         =   $employee->id;
        $resort_id                                      =   $user->resort_id;

        if ($user->is_master_admin == 0) {
            $reporting_to                               =   $employee->id;
            $underEmp_id                                =   Common::getSubordinates($reporting_to);
        }

        try {
            $leave_details_query                        =   DB::table('employees_leaves as el')
                                                            ->join('employees_leaves_status as els', 'els.leave_request_id', '=', 'el.id')
                                                            ->join('employees as e', 'e.id', '=', 'el.emp_id') // Main employee
                                                            ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id') // Main employee admin details
                                                            ->join('employees as delegated_emp', 'delegated_emp.id', '=', 'el.task_delegation') // Delegated employee
                                                            ->join('resort_admins as ra_td', 'ra_td.id', '=', 'delegated_emp.Admin_Parent_id') // Task delegation admin details
                                                            ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
                                                            ->join('resort_departments as rd', 'rd.id', '=', 'e.Dept_id')
                                                            ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
                                                            ->join('resorts as rs', 'rs.id', '=', 'lc.resort_id')
                                                            ->where('el.emp_id', $emp_id);

            $leaveDetails                               =   $leave_details_query->select(
                                                                'el.*',
                                                                'e.Emp_id as employee_id',
                                                                'e.rank',
                                                                'els.status as leave_status',
                                                                'els.approver_rank',
                                                                'els.approver_id',
                                                                // Main employee details
                                                                'ra.first_name as employee_first_name',
                                                                'ra.last_name as employee_last_name',
                                                                'ra.profile_picture as employee_profile_picture',
                                                                'rp.position_title as position',
                                                                'rd.name as department',
                                                                // Task delegation details
                                                                'delegated_emp.Emp_id as task_delegation_emp_id',
                                                                'ra_td.first_name as task_delegation_first_name',
                                                                'ra_td.last_name as task_delegation_last_name',
                                                                'ra_td.profile_picture as task_delegation_profile_picture',
                                                                // Leave category details
                                                                'lc.leave_type as leave_type',
                                                                'lc.color',
                                                            )->get();

            $leaveData                                  =   [];
            $leaveData['leave_balances']                =   null;
            $leaveData['total_allocated_days']          =   null;
            $leaveData['total_available_days']          =   null;

            $emp_grade                                  =   Common::getEmpGrade($user->GetEmployee->rank);

            $benefit_grids                              =   DB::table('resort_benifit_grid as rbg')
                                                                ->join('resort_benefit_grid_child as rbgc', 'rbg.id', '=', 'rbgc.benefit_grid_id')
                                                                ->join('leave_categories as lc', 'lc.id', '=', 'rbgc.leave_cat_id')
                                                                ->where('rbg.emp_grade', $emp_grade)
                                                                ->where('rbgc.rank',$user->GetEmployee->rank)
                                                                ->where('rbg.resort_id', $resort_id)
                                                                ->where('lc.resort_id', $resort_id)
                                                                ->select(
                                                                    'lc.id as leave_category_id',
                                                                    'lc.leave_type',
                                                                    'lc.color',
                                                                    'rbgc.allocated_days'
                                                                )
                                                                ->get();

                    // Calculate total leaves taken for each category for the current year
                    $currentYearStart                   =   Carbon::now()->startOfYear()->format('Y-m-d');
                    $currentYearEnd                     =   Carbon::now()->endOfYear()->format('Y-m-d');
                    $leaveUsage                         =   DB::table('employees_leaves')
                                                                ->select('leave_category_id', DB::raw('SUM(total_days) as used_days'))
                                                                ->where('emp_id', $emp_id)
                                                                ->where('status', 'Approved')
                                                                ->where(function ($query) use ($currentYearStart, $currentYearEnd) {
                                                                    $query->whereBetween('from_date', [$currentYearStart, $currentYearEnd])
                                                                        ->orWhereBetween('to_date', [$currentYearStart, $currentYearEnd]);
                                                                })
                                                                ->groupBy('leave_category_id')
                                                                ->get()
                                                                ->keyBy('leave_category_id');


                    if($leaveUsage) {

                        // Combine leave balances and usage
                        $leaveBalances                  =   $benefit_grids->map(function ($grid) use ($leaveUsage) {
                            $usedDays                   =   (int) ($leaveUsage->get($grid->leave_category_id)->used_days ?? 0);
                            $grid->used_days            =   $usedDays;
                            $grid->available_days       =   $grid->allocated_days - $usedDays;
                            return $grid;
                        });


                        $leaveData['leave_balances']    =   $leaveBalances;
                        $total_leaves_allocated         =   0;
                        $total_taken_laves              =   0;
                        foreach($leaveBalances as $leaves) {
                            $total_leaves_allocated     =   $total_leaves_allocated +  $leaves->allocated_days;
                            $total_taken_laves          =   $total_taken_laves +  $leaves->available_days;
                        }
                        $leaveData['total_allocated_days']  =   $total_leaves_allocated;
                        $leaveData['total_available_days']  =   $total_taken_laves;
                    }

            $loggedInEmployee                           =   $employee;

            if (!$loggedInEmployee) {
                return response()->json(['success' => false, 'message' => 'Access Denied.'], 200);
            }

            $rank                                       =   config('settings.Position_Rank');
            $current_rank                               =   $employee->rank ?? null;
            $available_rank                             =   $rank[$current_rank] ?? '';
            $isHOD                                      =   ($available_rank === "HOD");
            $isHR                                       =   ($available_rank === "HR");

            $islandPassQuery                            =   DB::table('employee_travel_passes as etp')
                                                                ->join('employees as e', 'e.id', '=', 'etp.employee_id') // Main employee
                                                                ->join('employee_travel_pass_status as etps', 'etps.travel_pass_id', '=', 'etp.id')
                                                                ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id') // Main employee admin details
                                                                ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
                                                                ->join('resort_departments as rd', 'rd.id', '=', 'e.Dept_id')
                                                                ->select(
                                                                    'etp.*',
                                                                    'e.Admin_Parent_id as Admin_Parent_id',
                                                                    'ra.first_name',
                                                                    'ra.last_name',
                                                                    'ra.profile_picture',
                                                                    'rp.position_title as position',
                                                                    'etps.approver_rank',
                                                                    'etps.approver_id',
                                                                    'etps.approved_at',
                                                                )
                                                                ->where('etp.resort_id', $resort_id) // Order by ID descending
                                                                ->orderBy('etp.id', 'desc') // Order by ID descending
                                                                ->first();

            if ($islandPassQuery) {
                $approveData                            =   DB::table('employee_travel_pass_status as etps')
                                                                ->where('etps.travel_pass_id', $islandPassQuery->id)
                                                                ->select('etps.approver_rank', 'etps.approver_id')
                                                                ->get();

                // Map the approver_rank to rank type using the config
                $rankConfig                             =   config('settings.Position_Rank');
                $approveData                            =   $approveData->map(function ($item) use ($rankConfig) {
                    $item->rank_type                    =   $rankConfig[$item->approver_rank] ?? 'Unknown';
                    return $item;
                });

                // Attach approve_data to the main island pass object
                $islandPassQuery->approve_data          =   $approveData;
            }

            $leaveData['island_pass']                   =   $islandPassQuery;
            $myLeaveRequest                             =   null;

            // if ($employee->rank == 6 || $employee->rank == 5 || $employee->rank == 4) {
                // Retrieve only the upcoming leave request with role-specific logic
                $leave_requests_query                   =   DB::table('employees_leaves as el')
                                                                ->join('employees as e', 'e.id', '=', 'el.emp_id')
                                                                ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id') // Main employee admin details
                                                                ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
                                                                ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
                                                                // ->where('el.status', 'Pending')
                                                                ->whereDate('el.from_date', '>=', Carbon::now()->format('Y-m-d')) // Only future leaves
                                                                ->where('el.flag', null);

                $leave_requests_query->where('el.emp_id', $emp_id);
                $leave_requests_query->where('el.resort_id', $resort_id);

                $myLeaveRequest                         =    $leave_requests_query
                                                                ->select(
                                                                    'el.id',
                                                                    'el.emp_id',
                                                                    'el.from_date',
                                                                    'el.to_date',
                                                                    'el.status',
                                                                    'el.reason',
                                                                    'el.created_at',
                                                                    'lc.leave_type as leave_category',
                                                                    'ra.first_name',
                                                                    'ra.last_name',
                                                                    'ra.profile_picture',
                                                                    'rp.position_title as position',
                                                                )
                                                                ->orderBy('el.created_at', 'DESC')
                                                                ->first();
            // }

            // Get the  leave request
            $leaveRequest                               =   DB::table('employees_leaves as el')
                                                                ->join('employees as e', 'e.id', '=', 'el.emp_id')
                                                                ->join('employees_leaves_status as els', 'els.leave_request_id', '=', 'el.id')
                                                                ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id') // Main employee admin details
                                                                ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
                                                                ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
                                                                ->where('el.status', 'Pending')
                                                                ->where('el.flag', null)
                                                                ->where('els.status', 'Pending')
                                                                ->where('els.approver_id', $emp_id)
                                                                ->select(
                                                                    'el.id',
                                                                    'el.emp_id',
                                                                    'el.from_date',
                                                                    'el.to_date',
                                                                    'el.status',
                                                                    'el.reason',
                                                                    'el.created_at',
                                                                    'lc.leave_type as leave_category',
                                                                    'ra.first_name',
                                                                    'ra.last_name',
                                                                    'ra.profile_picture',
                                                                    'rp.position_title as position',
                                                                )
                                                            ->orderBy('el.created_at', 'DESC')
                                                            ->first();

            if($leaveRequest) {

                $leaveRequest->approve_data             =   EmployeeLeaveStatus::where('leave_request_id', $leaveRequest->id)
                                                                ->get()->map(function ($empAppr) {
                                                                    $role = ucfirst(strtolower($empAppr->approver_rank ?? ''));
                                                                    $rank = config('settings.Position_Rank');
                                                                    $role = $rank[$role] ?? '';

                                                                    return [
                                                                        'approver_rank'     => $empAppr->approver_rank,
                                                                        'approver_id'       => $empAppr->approver_id,
                                                                        'status'            => $empAppr->status,
                                                                        'rank_type'         => $role,
                                                                    ];
                                                                })->values();

            }

            if ($myLeaveRequest) {

                $approveData                            =   DB::table('employees_leaves_status as els')
                                                                ->where('els.leave_request_id', $myLeaveRequest->id)
                                                                ->select('els.approver_rank', 'els.approver_id', 'els.status')
                                                                ->get();

                // Map the approver_rank to rank type using the config
                $rankConfig                             =   config('settings.Position_Rank');
                $approveData                            =   $approveData->map(function ($item) use ($rankConfig) {
                    $item->rank_type                    =   $rankConfig[$item->approver_rank] ?? 'Unknown';
                    return $item;
                });

                // Attach leave approve_data to the
                $myLeaveRequest->approve_data           =   $approveData;
                $myLeaveRequest->from_date              =   Carbon::parse($myLeaveRequest->from_date)->format('Y-m-d');
                $myLeaveRequest->to_date                =   Carbon::parse($myLeaveRequest->to_date)->format('Y-m-d');
            }

            //Leave Request data end here
            $leaveHistory                               =   DB::table('employees_leaves')
                                                                ->join('leave_categories', 'employees_leaves.leave_category_id', '=', 'leave_categories.id')
                                                                ->join('employees_leaves_status as els', 'els.leave_request_id', '=', 'employees_leaves.id')
                                                                ->select(
                                                                    'employees_leaves.id',
                                                                    'employees_leaves.emp_id',
                                                                    'leave_categories.leave_type as leave_category',
                                                                    'employees_leaves.reason',
                                                                    'employees_leaves.from_date',
                                                                    'employees_leaves.to_date',
                                                                    'employees_leaves.total_days',
                                                                    'employees_leaves.attachments',
                                                                    'employees_leaves.status',
                                                                    'employees_leaves.created_at',
                                                                )
                                                                ->whereDate('employees_leaves.from_date', '<', Carbon::now()->format('Y-m-d')) // Past dates only
                                                                ->whereDate('employees_leaves.to_date', '<=', Carbon::now()->format('Y-m-d')) // Past dates only
                                                                ->where('employees_leaves.emp_id', $emp_id) // Filter by the given employee ID
                                                                ->where('employees_leaves.resort_id', $resort_id) // Filter by the given resort ID
                                                                ->whereIn('employees_leaves.status', ['Approved', 'Rejected']) // Filter by the Status
                                                                ->orderBy('employees_leaves.from_date', 'desc') // Optional: Order by most recent past leave first
                                                                ->take(2)
                                                                ->get()->map(function ($item) {
                                                                    $item->approve_data         =   EmployeeLeaveStatus::where('leave_request_id', $item->id)->get()->map(function ($empAppr) {
                                                                        $role                   =   ucfirst(strtolower($empAppr->approver_rank ?? ''));
                                                                        $rank                   =   config('settings.Position_Rank');
                                                                        $role                   =   $rank[$role] ?? '';

                                                                        return [
                                                                            'approver_rank'     => $empAppr->approver_rank,
                                                                            'approver_id'       => $empAppr->approver_id,
                                                                            'status'            => $empAppr->status,
                                                                            'rank_type'         => $role,
                                                                        ];
                                                                    })->values();

                                                                return $item;
                                                            });

                $leaveData['my_leave_request']          =   (object)[];
                $leaveData['leave_request']             =   $leaveRequest;
            if ($myLeaveRequest) {
                $leaveData['my_leave_request']          =   $myLeaveRequest;
            }

            $leaveData['leave_history']                 =   $leaveHistory;
            $response['status']                         =   true;
            $response['message']                        =   'Leave Details';
            $response['leave_detail']                   =   $leaveData;
            return response()->json($response);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function viewLeaveRequest($leave_id)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $user                                       =   Auth::guard('api')->user();
            $resortId                                   =   $user->resort_id;
            $decodedId                                  =   base64_decode($leave_id);

            if (!$leave_id || !base64_decode($leave_id, true)) {
                return response()->json(['success' => false, 'message' => 'Invalid or missing leave ID.'], 200);
            }

            if (!$resortId) {
                return response()->json(['success' => false, 'message' => 'Invalid resort ID.'], 200);
            }

            if ($resortId) {
                // Fetch the leave details for the specific leave ID
                $leave_details_query                    =   DB::table('employees_leaves as el')
                                                                ->join('employees_leaves_status as els', 'els.leave_request_id', '=', 'el.id')
                                                                ->join('employees as e', 'e.id', '=', 'el.emp_id') // Main employee
                                                                ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id') // Main employee admin details
                                                                ->join('employees as delegated_emp', 'delegated_emp.id', '=', 'el.task_delegation') // Delegated employee
                                                                ->join('resort_admins as ra_td', 'ra_td.id', '=', 'delegated_emp.Admin_Parent_id') // Task delegation admin details
                                                                ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
                                                                ->join('resort_departments as rd', 'rd.id', '=', 'e.Dept_id')
                                                                ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
                                                                ->leftJoin('employee_travel_passes as etp', 'etp.leave_request_id', '=', 'el.id') // Join for travel passes
                                                                ->leftJoin('resort_transportations as rt', 'rt.id', '=', 'etp.transportation') // Join for transportation options
                                                                ->where('el.id', $decodedId)
                                                                ->where('el.resort_id', $resortId);

                $leaveDetail                            =   $leave_details_query->select(
                                                                'el.*',
                                                                'e.Emp_id as employee_id',
                                                                'e.rank',
                                                                'els.status as leave_status',
                                                                'els.approver_rank',
                                                                'els.approver_id',
                                                                // Main employee details
                                                                'ra.first_name as employee_first_name',
                                                                'ra.last_name as employee_last_name',
                                                                'ra.profile_picture as employee_profile_picture',
                                                                'rp.position_title as position',
                                                                'rd.name as department',
                                                                // Task delegation details
                                                                'delegated_emp.Emp_id as task_delegation_emp_id',
                                                                'ra_td.first_name as task_delegation_first_name',
                                                                'ra_td.last_name as task_delegation_last_name',
                                                                'ra_td.profile_picture as task_delegation_profile_picture',
                                                                // Leave category details
                                                                'lc.leave_type as leave_type',
                                                                'lc.color',
                                                                // Transportation details
                                                                DB::raw('JSON_ARRAYAGG(JSON_OBJECT(
                                                                    "transportation_name", rt.transportation_option,
                                                                    "transportation_id", rt.id,
                                                                    "id", etp.id,
                                                                    "arrival_date", etp.arrival_date,
                                                                    "arrival_time", etp.arrival_time,
                                                                    "departure_date", etp.departure_date,
                                                                    "departure_time", etp.departure_time
                                                                )) as transportation_details'))->groupBy('el.id')->first();

                if ($leaveDetail) {
                    // Fetch total leave allocation for the employee
                    $emp_grade                          =   Common::getEmpGrade($leaveDetail->rank);
                    $benefit_grid                       =   DB::table('resort_benifit_grid as rbg')
                                                                    ->join('resort_benefit_grid_child as rbgc', 'rbg.id', '=', 'rbgc.benefit_grid_id')
                                                                    ->where('rbg.emp_grade', $emp_grade)
                                                                    ->get();

                    // Calculate total leaves taken by the employee for the current year
                    $currentYearStart                   =   Carbon::now()->startOfYear()->format('Y-m-d');
                    $currentYearEnd                     =   Carbon::now()->endOfYear()->format('Y-m-d');

                    $leavesTaken                        =   DB::table('employees_leaves')
                                                                ->where('emp_id', $leaveDetail->employee_id)
                                                                ->where('status', 'Approved')
                                                                ->where(function ($query) use ($currentYearStart, $currentYearEnd) {
                                                                    $query->whereBetween('from_date', [$currentYearStart, $currentYearEnd])
                                                                        ->orWhereBetween('to_date', [$currentYearStart, $currentYearEnd]);
                                                                })
                                                                ->sum('total_days');

                    // Total allocation (sum of all allocated days across leave categories)
                    $totalAllocation                   =    $benefit_grid->sum('allocated_days');

                    // Attach leave balance information
                    $leaveDetail->total_leave_allocation    =   $totalAllocation;
                    $leaveDetail->leaves_taken              =   $leavesTaken;

                    // Update profile picture dynamically
                    $leaveDetail->employee_profile_picture  =   Common::getResortUserPicture($leaveDetail->employee_id);
                    $leaveDetail->transportation_details    =   json_decode($leaveDetail->transportation_details, true);

                    $baseUrl = url('/');
                    $leaveDetail->attachments               =   $leaveDetail->attachments ? $baseUrl . '/' . $leaveDetail->attachments : '';
                }

                if (!$leaveDetail) {
                    return response()->json(['success' => false, 'message' => 'Leave details not found.'], 200);
                }

                $response['status']                     =   true;
                $response['message']                    =   'Leave Details';
                $response['leave_request']              =   $leaveDetail;

                return response()->json($response);
            }
        } catch (QueryException $qe) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error("Database Query Error: " . $qe->getMessage());
            return response()->json(['success' => false, 'message' => 'A database error occurred. Please contact support.'], 500);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error("Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }

    public function leaveRequestList(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {

            $user                                       =   Auth::guard('api')->user();
            $employee                                   =   $user->GetEmployee;
            $resortId                                   =   $user->resort_id;

            if ($user->is_master_admin == 0) {
                $reporting_to                           =   $employee->id;
                $underEmp_id                            =   Common::getSubordinates($reporting_to);
            }

            if (!$resortId) {
                return response()->json(['success' => false, 'message' => 'Invalid resort ID.'], 200);
            }

            $resort_departments                         =   ResortDepartment::where('resort_id', $resortId)->where('status', 'active')->get();

            if (!$employee) {
                return response()->json(['success' => false, 'message' => 'Access Denied.'], 200);
            }

            $rank                                       =   config('settings.Position_Rank');
            $current_rank                               =   $employee->rank ?? null;
            $available_rank                             =   $rank[$current_rank] ?? '';
            $isHOD                                      =   ($available_rank === "HOD");
            $isHR                                       =   ($available_rank === "HR");

            // Get month and year from the request
            if ($request->has('year') && $request->has('month')) {
                // Use the provided year and month
                $year                                   =   $request->year;
                $month                                  =   $request->month;
            } else {
                // Default to the current year and month
                $year                                   =   Carbon::now()->year;
                $month                                  =   Carbon::now()->month;
            }

            // Calculate the start and end dates for the specified or default month
            $startDate                                  =   Carbon::create($year, $month, 1)->startOfMonth()->format('Y-m-d');
            $endDate                                    =   Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d');

            $leave_requests_query                       =   DB::table('employees_leaves as el')
                                                            ->join('employees_leaves_status as els', 'els.leave_request_id', '=', 'el.id')
                                                            ->join('employees as e', 'e.id', '=', 'el.emp_id')
                                                            ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                                                            ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id');

            // if ($employee->rank == 6 || $employee->rank == 5 || $employee->rank == 4) {
                $leave_requests_query->where('el.emp_id', $employee->id);
            // }

            $leave_requests_query->where('el.resort_id', $resortId) // Filter by resort Id
                ->whereBetween('el.from_date', [$startDate, $endDate]); // Filter by date range

            $leaveRequests                              =   $leave_requests_query->select(
                                                                'el.*',
                                                                'e.Emp_id as employee_id',
                                                                'e.rank',
                                                                'el.status as leave_status',
                                                                'ra.first_name as first_name',
                                                                'ra.last_name as last_name',
                                                                'ra.profile_picture',
                                                                'lc.leave_type as leave_type',
                                                                'lc.color',
                                                                'els.status as leave_status',
                                                                'els.approver_rank',
                                                                'els.approver_id',
                                                            )->get();


            // Group data by 'id'
            $leaveRequests                              =   $leaveRequests->groupBy('id')->map(function ($group) {
                // Use the first record as the base
                $base                                   =   $group->first();

                // Collect approver details for the grouped records
                $approveData                            =   $group->map(function ($item) {

                    $role                               =   ucfirst(strtolower($item->approver_rank ?? ''));
                    $rank                               =   config('settings.Position_Rank');
                    $role                               =   $rank[$role] ?? '';

                    return [
                        'approver_rank'                 =>  $item->approver_rank,
                        'approver_id'                   =>  $item->approver_id,
                        'status'                        =>  $item->leave_status,
                        'rank_type'                     =>  $role,
                    ];
                })->unique()->values();

                // Add approve_data to the base record
                $base->approve_data                     =   $approveData;

                return $base;
            })->values();

            if ($leaveRequests->isEmpty()) {
                $response['status']                     =   false;
                $response['message']                    =   'No leave requests found';
                $response['leave_request_list']         =   [];
            } else {
                $response['status']                     =   true;
                $response['message']                    =   'Leave Details';
                $response['leave_request_list']         =   $leaveRequests;
            }

            return response()->json($response);
        } catch (QueryException $qe) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error("Database Query Error: " . $qe->getMessage());
            return response()->json(['success' => false, 'message' => 'A database error occurred. Please contact support.'], 500);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error("Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }

    public function leaveHistoryList(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $user           = Auth::guard('api')->user();
            $resortId       = $user->resort_id;
            $employee       = $user->GetEmployee;
            $leaveCatId     = $request->leave_cat_id;

            if (!$resortId) {
                return response()->json(['success' => false, 'message' => 'Invalid resort ID.'], 200);
            }

            if ($request->filter        === 'weekly' || !$request->has('filter')) {
                // Default to weekly if filter is 'weekly' or no filter is provided
                $startDate      =   Carbon::now()->startOfWeek(Carbon::SUNDAY)->format('Y-m-d');
                $endDate        =   Carbon::now()->endOfWeek(Carbon::SATURDAY)->format('Y-m-d');
            } elseif ($request->filter  === 'monthly') {
                // For monthly filter
                $startDate      =   Carbon::now()->startOfMonth()->format('Y-m-d');
                $endDate        =   Carbon::now()->endOfMonth()->format('Y-m-d');
            } elseif ($request->filter  === 'yearly') {
                // For yearly filter
                $startDate      =   Carbon::now()->startOfYear()->format('Y-m-d');
                $endDate        =   Carbon::now()->endOfYear()->format('Y-m-d');
            }

            if ($request->has('year') && $request->has('month')) {
                // Use the provided year and month
                $year           =   $request->year;
                $month          =   $request->month;

                // Calculate the start and end dates for the specified or default month
                $startDate      =   Carbon::create($year, $month, 1)->startOfMonth()->format('Y-m-d');
                $endDate        =   Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d');
            }

            if ($resortId) {
                $empId                  =   $employee->id;

                // Build the initial query to fetch leave details
                $combineLeaveDetails    =   DB::table('employees_leaves as el')
                                                // ->join('employees_leaves_status as els', 'els.leave_request_id', '=', 'el.id')
                                                ->join('employees_leaves_status as els', function ($join) {
                                                    $join->on('els.leave_request_id', '=', 'el.id')
                                                        ->whereRaw('els.id = (SELECT MAX(id) FROM employees_leaves_status WHERE leave_request_id = el.id)');
                                                })
                                                ->join('employees as e', 'e.id', '=', 'el.emp_id') // Main employee
                                                ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id') // Main employee admin details
                                                ->join('employees as delegated_emp', 'delegated_emp.id', '=', 'el.task_delegation') // Delegated employee
                                                ->join('resort_admins as ra_td', 'ra_td.id', '=', 'delegated_emp.Admin_Parent_id') // Task delegation admin details
                                                ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
                                                ->join('resort_departments as rd', 'rd.id', '=', 'e.Dept_id')
                                                ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
                                                ->where('el.resort_id', $resortId)
                                                ->whereBetween('el.from_date', [$startDate, $endDate]) // Filter by date range
                                                // ->where('el.status', 'Approved')
                                                // ->where('els.status', 'Approved')
                                                ->where('el.emp_id', $empId)
                                                ->where(function ($query) {
                                                    $query->whereNull('el.flag') // Include rows where flag is NULL
                                                        ->orWhereExists(function ($subQuery) {
                                                            $subQuery->select(DB::raw(1))
                                                                ->from('employees_leaves as el2')
                                                                ->whereColumn('el2.leave_category_id', '=', 'el.flag') // Match flag with leave_category_id
                                                                ->whereColumn('el2.emp_id', '=', 'el.emp_id') // Ensure same employee
                                                                ->whereColumn('el2.resort_id', '=', 'el.resort_id'); // Ensure same resort
                                                        });
                                                })
                                                ->distinct();

                // Add leave_category_id condition only if it is provided
                if ($request->filled('leave_cat_id')) {
                    $combineLeaveDetails->where('el.leave_category_id', $leaveCatId);
                }

                // Select the necessary fields
                $combineLeaveDetails    =   $combineLeaveDetails->select(
                                                'el.*',
                                                'e.Emp_id as employee_id',
                                                'e.rank',
                                                'els.status as leave_status',
                                                'els.approver_rank',
                                                'els.approver_id',
                                                // Main employee details
                                                'ra.first_name as employee_first_name',
                                                'ra.last_name as employee_last_name',
                                                'ra.profile_picture as employee_profile_picture',
                                                'rp.position_title as position',
                                                'rd.name as department',
                                                // Task delegation details
                                                'delegated_emp.Emp_id as task_delegation_emp_id',
                                                'ra_td.first_name as task_delegation_first_name',
                                                'ra_td.last_name as task_delegation_last_name',
                                                'ra_td.profile_picture as task_delegation_profile_picture',
                                                // Leave category details
                                                'lc.leave_type as leave_type',
                                                'lc.color'
                                            )->get();

                $combinedData       = $combineLeaveDetails->map(function ($leave) use ($combineLeaveDetails) {
                    // Initialize leave_data if not already set
                    if (!isset($leave->leave_data)) {
                        $leave->leave_data = [];
                    }

                    // If the leave has a flag, find the matching leave and add it to the leave_data array
                    if ($leave->flag) {
                        $parentLeave    = $combineLeaveDetails->firstWhere('leave_category_id', $leave->flag);

                        // If a matching parent leave exists, add the current leave to its leave_data
                        if ($parentLeave) {
                            $parentLeave->leave_data[] = $leave;
                        }
                    }

                    $leave->approve_data         =   EmployeeLeaveStatus::where('leave_request_id', $leave->id)->get()->map(function ($empAppr) {
                                                                        $role                   =   ucfirst(strtolower($empAppr->approver_rank ?? ''));
                                                                        $rank                   =   config('settings.Position_Rank');
                                                                        $role                   =   $rank[$role] ?? '';

                                                                        return [
                                                                            'approver_rank'     => $empAppr->approver_rank,
                                                                            'approver_id'       => $empAppr->approver_id,
                                                                            'status'            => $empAppr->status,
                                                                            'rank_type'         => $role,
                                                                        ];
                                                                    })->values();

                    return $leave;
                });

                // Collect all `leave_id`s that are part of `leave_data`
                $leaveIdsInData     = $combinedData->flatMap(function ($leave) {
                    return collect($leave->leave_data)->pluck('id');
                })->unique();

                // Filter out the items whose `leave_id` exists in `leaveIdsInData`
                $filteredData       = $combinedData->reject(function ($leave) use ($leaveIdsInData) {
                    return $leaveIdsInData->contains($leave->id);
                });

                // Re-index the collection
                $finalData = $filteredData->values();
                $baseUrl = url('/');

                if ($finalData) {
                    foreach ($finalData as $leaveDetail) {

                        if ($leaveDetail->leave_data) {
                            foreach ($leaveDetail->leave_data as $leaveData) {
                                $leaveData->employee_profile_picture    = Common::getResortUserPicture($leaveData->employee_id);
                                $leaveData->attachments                 = $leaveData->attachments ? $baseUrl . '/' . $leaveData->attachments : '';
                            }
                        }

                        // Update profile picture dynamically
                        $leaveDetail->employee_profile_picture  = Common::getResortUserPicture($leaveDetail->employee_id);
                        $leaveDetail->attachments               = $leaveDetail->attachments ? $baseUrl . '/' . $leaveDetail->attachments : '';
                    }
                }

                // Check if finalData is empty
                if($finalData->isEmpty()){

                    return response()->json([
                        'success'           => false,
                        'message'           => 'No leave history found for the specified criteria.',
                        'leave_request'     => []
                    ], 200);
                }

                $response['status']                 = true;
                $response['message']                = 'Fetched leave history successfully';
                $response['leave_request']          = $finalData;

                return response()->json($response);
            }
        } catch (QueryException $qe) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error("Database Query Error: " . $qe->getMessage());
            return response()->json(['success' => false, 'message' => 'A database error occurred. Please contact support.'], 500);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error("Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }

    public function viewLeaveHistory($leave_id)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $user       = Auth::guard('api')->user();
            $resortId   = $user->resort_id;
            $decodedId  = base64_decode($leave_id);

            if (!$leave_id || !base64_decode($leave_id, true)) {
                return response()->json(['success' => false, 'message' => 'Invalid or missing leave ID.'], 200);
            }

            if (!$resortId) {
                return response()->json(['success' => false, 'message' => 'Invalid resort ID.'], 200);
            }

            if ($resortId) {
                $leaveDetails = DB::table('employees_leaves')
                    ->where('id', $decodedId)
                    ->whereNotIn('status', ['Pending'])
                    ->first();

                if (!$leaveDetails) {
                    return response()->json(['success' => false, 'message' => 'Leave ID not found.',], 200);
                }

                $leaveCategoryId = $leaveDetails->leave_category_id;
                $empId = $leaveDetails->emp_id;

                // Fetch the leave details for the specific leave ID
                $combineLeaveDetails = DB::table('employees_leaves as el')
                    ->join('employees_leaves_status as els', 'els.leave_request_id', '=', 'el.id')
                    ->join('employees as e', 'e.id', '=', 'el.emp_id') // Main employee
                    ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id') // Main employee admin details
                    ->join('employees as delegated_emp', 'delegated_emp.id', '=', 'el.task_delegation') // Delegated employee
                    ->join('resort_admins as ra_td', 'ra_td.id', '=', 'delegated_emp.Admin_Parent_id') // Task delegation admin details
                    ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
                    ->join('resort_departments as rd', 'rd.id', '=', 'e.Dept_id')
                    ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
                    ->where('el.resort_id', $resortId)
                    ->where(function ($query) use ($leaveCategoryId, $decodedId) {
                        $query->where('el.flag', $leaveCategoryId) // Matches flag with leave_category_id
                            ->orWhere('el.id', $decodedId); // Include the original record
                    })
                    ->whereNotIn('el.status', ['Pending'])
                    ->whereNotIn('els.status', ['Pending'])
                    ->where('el.emp_id', $empId);

                $combineLeaveDetails = $combineLeaveDetails->select(
                    'el.*',
                    'e.Emp_id as employee_id',
                    'e.rank',
                    'els.status as leave_status',
                    'els.approver_rank',
                    'els.approver_id',
                    // Main employee details
                    'ra.first_name as employee_first_name',
                    'ra.last_name as employee_last_name',
                    'ra.profile_picture as employee_profile_picture',
                    'rp.position_title as position',
                    'rd.name as department',
                    // Task delegation details
                    'delegated_emp.Emp_id as task_delegation_emp_id',
                    'ra_td.first_name as task_delegation_first_name',
                    'ra_td.last_name as task_delegation_last_name',
                    'ra_td.profile_picture as task_delegation_profile_picture',
                    // Leave category details
                    'lc.leave_type as leave_type',
                    'lc.color'
                )->get();

                if ($combineLeaveDetails) {
                    foreach ($combineLeaveDetails as $leaveDetail) {

                        // Fetch total leave allocation for the employee
                        $emp_grade          = Common::getEmpGrade($leaveDetail->rank);
                        $benefit_grid       = DB::table('resort_benifit_grid as rbg')
                            ->join('resort_benefit_grid_child as rbgc', 'rbg.id', '=', 'rbgc.benefit_grid_id')
                            ->where('rbg.emp_grade', $emp_grade)
                            ->get();

                        // Calculate total leaves taken by the employee for the current year
                        $currentYearStart   = Carbon::now()->startOfYear()->format('Y-m-d');
                        $currentYearEnd     = Carbon::now()->endOfYear()->format('Y-m-d');

                        $leavesTaken        = DB::table('employees_leaves')
                            ->where('emp_id', $leaveDetail->emp_id)
                            ->whereNotIn('status', ['Pending'])

                            ->where(function ($query) use ($currentYearStart, $currentYearEnd) {
                                $query->whereBetween('from_date', [$currentYearStart, $currentYearEnd])
                                    ->orWhereBetween('to_date', [$currentYearStart, $currentYearEnd]);
                            })
                            ->sum('total_days');

                        // Total allocation (sum of all allocated days across leave categories)
                        $totalAllocation                        = $benefit_grid->sum('allocated_days');

                        // Attach leave balance information
                        $leaveDetail->total_leave_allocation    = $totalAllocation;
                        $leaveDetail->leaves_taken              = $leavesTaken;

                        // Update profile picture dynamically
                        $leaveDetail->employee_profile_picture  = Common::getResortUserPicture($leaveDetail->employee_id);
                    }
                }
                $totalLeave                         = $combineLeaveDetails->sum('total_days');

                $combineLeaveDetails = $combineLeaveDetails->map(function ($item) {
                    if (preg_match('/leaves$/i', $item->leave_type)) {
                        $item->leave_type = preg_replace('/leaves$/i', 'Leave', $item->leave_type);
                    }
                    return $item;
                });

                if (!$combineLeaveDetails) {
                    return response()->json(['success' => false, 'message' => 'Leave details not found.'], 200);
                }
                $baseUrl = url('/');

                $response['status']                 = true;
                $response['message']                = 'Leave Details';
                $response['leave_request']          = $combineLeaveDetails;
                $response['total_leave']            = $totalLeave;
                $response['attachments']            = $combineLeaveDetails[0]->attachments ? $baseUrl . '/' . $combineLeaveDetails[0]->attachments : '';

                return response()->json($response);
            }
        } catch (QueryException $qe) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error("Database Query Error: " . $qe->getMessage());
            return response()->json(['success' => false, 'message' => 'A database error occurred. Please contact support.'], 500);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error("Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }

    public function leaveUpdate(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $leave_id                                       =   $request->leave_id;

        // Validate request data
        $validator                                      =   Validator::make($request->all(), [
            'leave_id'                                  =>  'required|integer',
            'leave_category_id'                         =>  'required|array',
            'leave_category_id.*'                       =>  'required|integer',
            'from_date'                                 =>  'required|array',
            'from_date.*'                               =>  'required|date_format:Y-m-d',
            'to_date'                                   =>  'required|array',
            'to_date.*'                                 =>  'required|date_format:Y-m-d',
            'reason'                                    =>  'required|string',

            'transportation'                            =>  'nullable|array',
            'transportation.*'                          =>  'nullable|integer',

            // Conditional fields for transportation
            'trans_arrival_date'                        => 'required_with:transportation|array',
            'trans_arrival_date.*'                      => 'required_with:transportation.*|date_format:Y-m-d',
            'trans_departure_date'                      => 'required_with:transportation|array',
            'trans_departure_date.*'                    => 'required_with:transportation.*|date_format:Y-m-d',

            // Additional fields for transportation and destination
            'dept_date'                                 => 'required_with_all:destination,transportation|date_format:Y-m-d',
            'dept_time'                                 => 'required_with_all:destination,transportation|date_format:H:i',
            'dept_transportation'                       => 'required_with_all:destination,transportation|integer',
            'arrival_date'                              => 'required_with_all:destination,transportation|date_format:Y-m-d',
            'arrival_time'                              => 'required_with_all:destination,transportation|date_format:H:i',
            'arrival_transportation'                    => 'required_with_all:destination,transportation|integer',
            'dept_reason'                               => 'required_with_all:destination,transportation',

        ]);

        // $validator->sometimes(['departure_date', 'arrival_date'], 'required|date_format:Y-m-d', function ($input) use ($leave_id) {
        //     $leaveFind      = EmployeeLeave::find($leave_id);

        //     // Combine the two conditions:
        //     return $leaveFind && $leaveFind->status !== 'Approved' && isset($input->transportation) && $input->transportation == 2;
        // });

        // $validator->sometimes('arrival_date', 'required|date_format:Y-m-d', function ($input) use ($leave_id) {
        //     $leaveFind      = EmployeeLeave::find($leave_id);
        //     // return $leaveFind && $leaveFind->status == 'Approved' && isset($input->transportation) && $input->transportation == 2;
        //     return $leaveFind && $leaveFind->status == 'Approved' && isset($input->transportation);
        // });

        $validator->sometimes('destination', 'required|string', function ($input) use ($request) {
            // Fetch the leave record
            $leaveFind                                  =   EmployeeLeave::find($request->leave_id);

            // Make "destination" required only if leave status is not "Approved"
            return $leaveFind && $leaveFind->status !== 'Approved';
        });

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        $leaveFind                                      =   EmployeeLeave::find($request->leave_id);

        if (!$leaveFind) {
            return response()->json([
                'success'                               =>  false,
                'message'                               =>  'Invalid Leave ID .',
            ], 200);
        }

        $user                                           =   Auth::guard('api')->user();
        $employee                                       =   $user->GetEmployee;
        $emp_id                                         =   $employee->id;
        $rank                                           =   $employee->rank;

        try {
            DB::beginTransaction();

            // Validate transportation ID
            $transportationId                           =   $request->transportation;

            if (!ResortTransportation::where('id', $transportationId)->exists()) {
                return response()->json([
                    'success'                           =>  false,
                    'message'                           =>  'Invalid transportation ID provided.',
                ], 200);
            }

            // Get the leave category IDs from the request
            $leaveCategoryIds                           =   $request->leave_category_id;

            // Handle the case for single-category leave applications
            if (count($leaveCategoryIds) == 1) {
                // Fetch the leave category from the database
                $categories                             =   LeaveCategory::whereIn('id', $leaveCategoryIds)->get();
                if ($categories->isEmpty()) {
                    return response()->json(['success' => false, 'message' => 'Invalid leave category selected.'], 200);
                }
            } else {
                // Fetch the leave categories for the selected IDs
                $categories                             =   LeaveCategory::whereIn('id', $leaveCategoryIds)->get();

                // Check if there are exactly 2 categories and if they can be combined
                if ($categories->count() != 2) {
                    return response()->json(['success' => false, 'message' => 'The selected leave categories are not combined with each other.'], 200);
                }

                $firstCategory                          =   $categories->first();
                $secondCategory                         =   $categories->last();

                // Validate that the categories can be combined
                if (!(($firstCategory->combine_with_other == 1 && $firstCategory->leave_category == $secondCategory->id) ||
                    ($secondCategory->combine_with_other == 1 && $secondCategory->leave_category == $firstCategory->id))) {
                    return response()->json(['success' => false, 'message' => 'The selected leave categories are not combined with each other.'], 200);
                }
            }

            // Define leave attachment path
            $leave_attachment                           =   config('settings.leave_attachments');
            $dynamic_path                               =   $leave_attachment . '/' . $emp_id;

            // Create the directory if it doesn't exist
            if (!Storage::exists($dynamic_path)) {
                Storage::makeDirectory($dynamic_path);
            }

            // Handle file upload if any attachments are provided
            $filePath                                   =   null;

            if ($request->hasFile('attachments')) {
                $fileName                               =   uniqid('attachment_', true) . '.' . $request->attachments->getClientOriginalExtension();
                $filePath                               =   $dynamic_path . '/' . $fileName;
                $request->attachments->move(public_path($dynamic_path), $fileName);
            }

            // Process each leave category and create leave records
            foreach ($request->leave_category_id as $key => $categoryId) {

                $fromDate                               =   Carbon::parse($request->from_date[$key]);
                $toDate                                 =   Carbon::parse($request->to_date[$key]);

                // Validate the date range
                if ($toDate->lt($fromDate)) {
                    return response()->json([
                        'success'                       =>  false,
                        'message'                       =>  'To date must be the same or after from date for leave category ID ' . $categoryId,
                    ], 200);
                }

                // Calculate the total days for the leave
                $totalDays                              =   $fromDate->diffInDays($toDate) + 1;

                $leaveCategory                          =   DB::table('leave_categories')->where('id', $categoryId)->first();
                if (!$leaveCategory) {
                    return response()->json([
                        'status'                        =>  false,
                        'message'                       =>  "Leave category with ID $categoryId does not exist.",
                    ]);
                }

                // Get the employee grade and leave balances
                $emp_grade                              =   Common::getEmpGrade($rank);

                // Fetch the benefit grid (allocated days) for the employee's grade and rank
                $benefit_grid                           =   DB::table('resort_benifit_grid as rbg')
                                                                ->join('resort_benefit_grid_child as rbgc', 'rbg.id', '=', 'rbgc.benefit_grid_id')
                                                                ->where('rbg.emp_grade', $emp_grade)
                                                                ->where('rbgc.rank', $rank)
                                                                ->where('rbgc.leave_cat_id', $categoryId)
                                                                ->select('rbgc.allocated_days')
                                                                ->first();

                if (!$benefit_grid) {
                    return response()->json([
                        'status'                        =>  false,
                        'message'                       => "No benefit grid found for this employee's rank and leave category.",
                    ]);
                }

                $allocatedDays                          =   $benefit_grid->allocated_days;

                // Get the total used days for the current leave category within the current year
                $currentYearStart                       =   Carbon::now()->startOfYear()->format('Y-m-d');
                $currentYearEnd                         =   Carbon::now()->endOfYear()->format('Y-m-d');

                $leaveUsage                             =   DB::table('employees_leaves')
                                                            ->select(DB::raw('SUM(total_days) as used_days'))
                                                            ->where('emp_id', $emp_id)
                                                            ->where('status', 'Approved')
                                                            ->where('leave_category_id', $categoryId)
                                                            ->where(function ($query) use ($currentYearStart, $currentYearEnd) {
                                                                $query->whereBetween('from_date', [$currentYearStart, $currentYearEnd])
                                                                    ->orWhereBetween('to_date', [$currentYearStart, $currentYearEnd]);
                                                            })
                                                            ->groupBy('leave_category_id')
                                                            ->first();

                $usedDays                               =   $leaveUsage->used_days ?? 0;
                $availableDays                          =   $allocatedDays - $usedDays;

                // Check if the requested leave exceeds the available days for the category
                if ($totalDays > $availableDays) {
                    return response()->json([
                        'status'                        =>  false,
                        'message'                       =>  "You cannot apply for more days than your remaining balance in the {$leaveCategory->leave_type} category! Available: $availableDays days.",
                    ]);
                }

                // Process optional departure and arrival dates
                $departure_date                         =   $request->departure_date ? $request->departure_date : null;
                $arrival_date                           =   $request->arrival_date ? $request->arrival_date : null;

                // Update the leave record in the database

                $leaveUpdate                            =   EmployeeLeave::find($request->leave_id);

                $leaveUpdate->leave_category_id         =   $categoryId;
                $leaveUpdate->from_date                 =   $fromDate;
                $leaveUpdate->to_date                   =   $toDate;
                $leaveUpdate->total_days                =   $totalDays;
                $leaveUpdate->reason                    =   $request->reason;
                $leaveUpdate->task_delegation           =   $request->task_delegation;
                $leaveUpdate->attachments               =   $filePath;

                // Only update 'destination' if status is not 'Approved'
                if ($leaveUpdate->status !== "Approved") {
                    $leaveUpdate->destination           =   $request->destination;
                }

                $leaveUpdate->arrival_date              =   $arrival_date;
                $leaveUpdate->status                    =   "Pending";
                $leaveUpdate->save();

                if ($transportationId) {

                    if ($leaveUpdate->status == 'Pending') {

                        $travelPassIds                  =   DB::table('employee_travel_passes')
                                                                ->where('leave_request_id', $request->leave_id)
                                                                ->pluck('id')
                                                                ->toArray();

                        if (!empty($travelPassIds)) {
                            // Delete related travel pass statuses first
                            DB::table('employee_travel_pass_status')
                                ->whereIn('travel_pass_id', $travelPassIds)
                                ->delete();

                            // Delete travel passes
                            DB::table('employee_travel_passes')
                                ->whereIn('id', $travelPassIds)
                                ->delete();
                        }
                    }

                        foreach ($transportationId as $key  => $transportMode) {
                            // Ensure all fields exist for the current index
                            if (isset($request->trans_arrival_date[$key], $request->trans_departure_date[$key])) {
                                // Transportation Entry
                                $entryPass                  = EmployeesLeaveTransportation::create([
                                    'leave_request_id'      => $leaveUpdate->id,
                                    'transportation'        => $transportMode,
                                    'trans_arrival_date'    => \Carbon\Carbon::createFromFormat('Y-m-d', $request->trans_arrival_date[$key]),
                                    'trans_departure_date'  => \Carbon\Carbon::createFromFormat('Y-m-d', $request->trans_departure_date[$key]),
                                ]);
                            }
                        }

                        $passApprovalFlow                       =   collect();

                         // Add Security Manager (SM) to the approval flow (rank 4)
                        $securityManagerTitles                  =   ['Security Manager', 'SM'];

                        // Get position IDs that match the titles in the current resort
                        $positionIds                            =   ResortPosition::where('resort_id', $user->resort_id)
                                                                        ->whereIn('position_title', $securityManagerTitles)
                                                                        ->pluck('id'); // Get the position IDs

                        // Get employees who hold these positions in the current resort
                        $SMApprover                             =   Employee::with(['resortAdmin','position'])->whereIn('Position_id', $positionIds)
                                                                        ->where('resort_id', $user->resort_id)->select('id', 'rank')
                                                                        ->first();
                        if ($SMApprover) {
                            $passApprovalFlow->push($SMApprover); // Fourth approver: Security Officer
                        }

                        // Add HR to the approval flow (rank 3)
                        $hrApprover                             =   Employee::select('id', 'rank')->where('resort_id',$user->resort_id)->where('rank', 3)->first();
                        if ($hrApprover) {
                            $passApprovalFlow->push($hrApprover); // Third approver: HR
                        }

                        // Add HOD to the approval flow (rank 2)
                        $hodApprover                             =   Employee::select('id', 'rank')->where('rank', 2)->where('resort_id',$user->resort_id)->where('Dept_id', $employee->Dept_id)->first();
                        if ($hodApprover ) {
                            $passApprovalFlow->push($hodApprover); // Second approver: HOD
                        }

                        if (isset($request->arrival_date, $request->dept_date)) {
                            if ($leaveUpdate->status == 'Pending') {
                                // Generate Entry Pass
                               $boardingPass                       =   EmployeeTravelPass::create([
                                'resort_id'                     =>  $user->resort_id,
                                'employee_id'                   =>  $emp_id,
                                'leave_request_id'              =>  $request->leave_id,
                                'arrival_mode'                  =>  $request->arrival_transportation ?? null,  // Set transportation based on arrival or departure
                                'arrival_date'                  =>  $request->arrival_date ? \Carbon\Carbon::createFromFormat('Y-m-d', $request->arrival_date) : null,
                                'arrival_time'                  =>  $request->arrival_time??null,
                                'arrival_reason'                =>  $request->arrival_reason,
                                'departure_date'                =>  $request->dept_date ? \Carbon\Carbon::createFromFormat('Y-m-d', $request->dept_date) : null,
                                'departure_time'                =>  $request->dept_time??null,
                                'departure_mode'                =>  $request->dept_transportation ?? null, // Set transportation based on arrival or departure
                                'departure_reason'              =>  $request->dept_reason,
                                'status'                        =>  'Pending',
                            ]);

                            if (!empty($boardingPass)) {
                                // Add the same approval flow for Exit Pass as well
                               foreach ($passApprovalFlow as $approverFlw) {

                                    // Create approval status for Entry Pass
                                    EmployeeTravelPassStatus::create([
                                        'travel_pass_id'    =>  $entryPass->id,
                                        'approver_id'       =>  $approverFlw->id,
                                        'approver_rank'     =>  $approverFlw->rank,
                                        'status'            =>  'Pending',
                                    ]);
                                }
                            }
                        }
                    }
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Leave application updated successfully!'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error("Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred. Please try again later.',
            ], 500);
        }
    }

    public function leaveDashboardHR()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $user                               =   Auth::guard('api')->user();
        $employee                           =   $user->GetEmployee;
        $resort_id                          =   $user->resort_id;
        $reporting_to                       =   $employee->reporting_to;
        $emp_id                             =   $employee->id;

        try {
            $rank                           =   config('settings.Position_Rank');
            $current_rank                   =   $employee->rank ?? null;
            $available_rank                 =   $rank[$current_rank] ?? '';
            $isHR                           =   ($available_rank === "HR");
            $isGM                           =   ($available_rank === "GM");
            $isHOD                          =   ($available_rank === "HOD");

            $leaveCounts                    =   $this->getLeaveCounts($resort_id, $isHR, $isGM, $reporting_to);
            $upcomingEmployeeLeave          =   $this->getUpcomingEmployeeLeave($resort_id, $isHOD, $employee->Dept_id);
            $leaveRequest                   =   $this->getUpcomingLeaveRequest($resort_id, $emp_id, $available_rank,$employee->rank);
            $upcomingHolidays               =   $this->getUpcomingHolidays($resort_id);
            $employeesOnLeaveToday          =   $this->getEmployeesOnLeaveToday($resort_id, $isHOD, $employee->Dept_id);
            $islandPassHR                   =   $this->islandPassHR();
            $upcomingBirthdays              =   $this->upcomingBirthdays($resort_id);

            $leaveData = [
                'total_applied_leaves'      =>  $leaveCounts['totalApplied'],
                'total_approved_leaves'     =>  $leaveCounts['totalApproved'],
                'total_rejected_leaves'     =>  $leaveCounts['totalRejected'],
                'total_pending_leaves'      =>  $leaveCounts['totalPending'],
                'employees_on_leave_today'  =>  $employeesOnLeaveToday,
                'upcoming_employee_leave'   =>  $upcomingEmployeeLeave,
                'leave_request'             =>  $leaveRequest,
                'island_pass'               =>  $islandPassHR,
                'upcoming_birthdays'        =>  $upcomingBirthdays,
                'upcoming_holidays'         =>  $upcomingHolidays,
            ];

            return response()->json([
                'status'                    =>  true,
                'message'                   =>  $available_rank . ' Leave Deshboard Details',
                'leave_detail'              =>  $leaveData
            ]);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    private function getLeaveCounts($resort_id, $isHR, $isGM, $reporting_to)
    {
        $query                                          =   DB::table('employees_leaves as el')
                                                            ->where('el.resort_id', $resort_id)
                                                            ->whereNull('flag');

        if (!$isHR && !$isGM) {
            $query->join('employees as e', 'e.id', '=', 'el.emp_id')->where('e.reporting_to', $reporting_to);
        }

        return [
            'totalApplied'                              =>  $query->count(),
            'totalApproved'                             =>  (clone $query)->where('el.status', 'Approved')->count(),
            'totalPending'                              =>  (clone $query)->where('el.status', 'Pending')->count(),
            'totalRejected'                             =>  (clone $query)->where('el.status', 'Rejected')->count()
        ];
    }

    private function getUpcomingEmployeeLeave($resort_id, $isHOD, $dept_id)
    {
        $startDate                                      =   Carbon::now()->startOfWeek();
        $endDate                                        =   Carbon::now()->endOfWeek();

        $query                                          =   DB::table('employees_leaves as el')
                                                            ->join('employees as e', 'e.id', '=', 'el.emp_id')
                                                            ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
                                                            ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
                                                            ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                                                            ->where('el.resort_id', $resort_id)
                                                            ->where('el.status', 'Approved')
                                                            ->whereDate('el.from_date', '<=', $endDate)
                                                            ->whereDate('el.to_date', '>=', $startDate);

        if ($isHOD) {
            $query->where('e.Dept_id', $dept_id);
        }

        $data                                           =   $query->select(
                                                                'ra.id',
                                                                'ra.first_name',
                                                                'ra.last_name',
                                                                'ra.profile_picture',
                                                                'rp.position_title as position',
                                                                'lc.leave_type',
                                                                'lc.color',
                                                                'el.from_date',
                                                                'el.to_date',
                                                                DB::raw("DATEDIFF(el.to_date, el.from_date) + 1 as total_days")
                                                            )->get();


        $upcomingEmployeeLeave                          =   $data->map(function ($employee) {
            $employee->profile_picture                  =   Common::getResortUserPicture($employee->id);
            return $employee;
        });

        return $upcomingEmployeeLeave;
    }

    private function getUpcomingLeaveRequest($resort_id, $emp_id, $available_rank,$emp_rank)
    {
        $isHOD                                          = ($available_rank === "HOD");

        if($emp_rank == 1 || $emp_rank == 3 || $emp_rank == 8) {

            $leaveRequest                               =   DB::table('employees_leaves as el')
                                                                ->join('employees_leaves_status as els', 'els.leave_request_id', '=', 'el.id')
                                                                ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
                                                                ->join('employees as e', 'e.id', '=', 'el.emp_id')
                                                                ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                                                                ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
                                                                ->where('el.status', 'Pending')
                                                                ->where('el.resort_id', $resort_id)
                                                                ->where('els.approver_rank', $emp_rank)
                                                                ->where('els.status', 'Pending')
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
                                                                    'els.id as emp_l_s_id',
                                                                    'els.approver_rank',
                                                                    'els.approver_id',
                                                                    'els.approved_at',
                                                                    'els.status as approve_status',
                                                                )
                                                                ->orderBy('el.created_at', 'DESC')
                                                                ->take(2)
                                                                ->get();
        }

        if ($isHOD) {

            $leaveRequest                               =   DB::table('employees_leaves as el')
                                                            ->join('employees_leaves_status as els', 'els.leave_request_id', '=', 'el.id')
                                                            ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
                                                            ->join('employees as e', 'e.id', '=', 'el.emp_id')
                                                            ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                                                            ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
                                                            ->where('el.status', 'Pending')
                                                            ->where('el.resort_id', $resort_id)
                                                            ->where('e.reporting_to', $emp_id)
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
                                                                'els.id as emp_l_s_id',
                                                                'els.approver_rank',
                                                                'els.approver_id',
                                                                'els.approved_at',
                                                                'els.status as approve_status',
                                                            )
                                                            ->orderBy('el.created_at', 'DESC')
                                                            ->take(2)
                                                            ->get();
        }

        // Group data by 'id'
        $finalData                                      =   $leaveRequest->groupBy('id')->map(function ($group) {
            // Use the first record as the base
            $base                                       =   $group->first();

            // Collect approver details for the grouped records
            $approveData                                =   EmployeeLeaveStatus::where('leave_request_id', $base->id)
                                                                ->get()
                                                                ->map(function ($empAppr) {
                                                                    $role = ucfirst(strtolower($empAppr->approver_rank ?? ''));
                                                                    $rank = config('settings.Position_Rank');
                                                                    $role = $rank[$role] ?? '';

                                                                    return [
                                                                        'approver_rank'     => $empAppr->approver_rank,
                                                                        'approver_id'       => $empAppr->approver_id,
                                                                        'status'            => $empAppr->status,
                                                                        'rank_type'         => $role,
                                                                    ];
                                                                })->values();


            // Add approve_data to the base record
            $base->approve_data                         =   $approveData;

            // Clear duplicate fields in the base record
            unset($base->approver_rank, $base->approver_id);

            return $base;
        })->values();

        return $finalData;
    }

    private function getUpcomingHolidays($resort_id)
    {
        return ResortHoliday::where('resort_id', $resort_id)
            ->where('PublicHolidaydate', '>=', Carbon::now()->toDateString())
            ->orderBy('PublicHolidaydate', 'asc')
            ->get();
    }

    private function getEmployeesOnLeaveToday($resort_id, $isHOD, $dept_id)
    {
        $date = Carbon::today();

        $query = DB::table('employees_leaves as el')
            ->join('employees as e', 'e.id', '=', 'el.emp_id')
            ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
            ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
            ->join('resort_departments as rd', 'rd.id', '=', 'e.Dept_id')
            ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->where('el.resort_id', $resort_id)
            ->where('el.status', 'Approved')
            ->whereDate('el.from_date', '<=', $date)
            ->whereDate('el.to_date', '>=', $date);

        if ($isHOD) {
            $query->where('e.Dept_id', $dept_id);
        }

        $data = $query->select(
            'el.id as leave_id',
            'ra.id',
            'ra.first_name',
            'ra.last_name',
            'ra.profile_picture',
            'lc.leave_type',
            'lc.color',
            'e.Emp_id as employee_id',
            'rp.position_title as position',
            'rd.name as department'
        )->get();

        $getEmployeesOnLeaveToday  = $data->map(function ($employee) {
            $employee->profile_picture  = Common::getResortUserPicture($employee->id);
            return $employee;
        });

        return $getEmployeesOnLeaveToday;
    }

    private function upcomingBirthdays($resort_id)
    {
        $today              = Carbon::today()->format('d-m'); // Current day and month (d-m)
        $tomorrow           = Carbon::tomorrow()->format('d-m'); // Tomorrow's day and month (d-m)
        $dayAfterTomorrow   = Carbon::today()->addDays(2)->format('d-m'); // Day after tomorrow's day and month (d-m)

        $upcomingBirthdays  = Employee::with(['resortAdmin', 'position']) // Eager load relationships
            ->whereRaw('SUBSTRING(dob, 1, 5) IN (?, ?, ?)', [$today, $tomorrow, $dayAfterTomorrow]) // Compare day and month
            ->orderByRaw("SUBSTRING(dob, 1, 5)")
            ->get();

        $upcomingBirthdays = DB::table('employees as e')
            ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id') // Join with resort_admins
            ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id') // Join with positions
            ->whereRaw('SUBSTRING(e.dob, 1, 5) IN (?, ?, ?)', [$today, $tomorrow, $dayAfterTomorrow]) // Compare day and month
            ->where('ra.resort_id', $resort_id)
            ->select(
                'e.id as employee_id',
                'e.Admin_Parent_id as Admin_Parent_id',
                'e.dob',
                'ra.first_name',
                'ra.last_name',
                'rp.position_title',
                'e.dob',
            )
            ->orderByRaw("SUBSTRING(e.dob, 1, 5)")
            ->get();

        // Map the profile picture for each employee
        $upcomingBirthdays              = $upcomingBirthdays->map(function ($employee) {
            $employee->profile_picture  = Common::getResortUserPicture($employee->Admin_Parent_id);
            return $employee;
        });

        return $upcomingBirthdays;
    }

    private function islandPassHR()
    {
        $islandPassQuery    = DB::table('employee_travel_passes as etp')
            ->join('employees as e', 'e.id', '=', 'etp.employee_id') // Main employee
            ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id') // Main employee admin details
            ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
            ->join('resort_departments as rd', 'rd.id', '=', 'e.Dept_id')
            ->select(
                'etp.*',
                'e.Admin_Parent_id as Admin_Parent_id',
                'ra.first_name',
                'ra.last_name',
                'ra.profile_picture',
                'rp.position_title as position',
            )
            ->orderBy('etp.id', 'desc') // Order by ID descending
            ->first();

        if ($islandPassQuery) {
            $approveData = DB::table('employee_travel_pass_status as etps')
                ->where('etps.travel_pass_id', $islandPassQuery->id)
                ->select('etps.approver_rank', 'etps.approver_id','etps.status')
                ->get();

            // Map the approver_rank to rank type using the config
            $rankConfig = config('settings.Position_Rank');
            $approveData = $approveData->map(function ($item) use ($rankConfig) {
                $item->rank_type = $rankConfig[$item->approver_rank] ?? 'Unknown';
                return $item;
            });

            // Attach approve_data to the main island pass object
            $islandPassQuery->approve_data = $approveData;
            $islandPassQuery->profile_picture    = Common::getResortUserPicture($islandPassQuery->employee_id);
        }

        return $islandPassQuery;
    }

    public function whoIsOnLeave()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {

            $user           = Auth::guard('api')->user();
            $resort_id      = $user->resort_id;
            $employee       = $user->GetEmployee;

            if (!$employee) {
                return response()->json(['success' => false, 'message' => 'Employee record not found'], 200);
            }

            $date           = Carbon::today();

            $query = DB::table('employees_leaves as el')
                ->join('employees as e', 'e.id', '=', 'el.emp_id')
                ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
                ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
                ->join('resort_departments as rd', 'rd.id', '=', 'e.Dept_id')
                ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                ->where('el.resort_id', $resort_id)
                ->where('el.status', 'Approved')
                ->whereDate('el.from_date', '<=', $date)
                ->whereDate('el.to_date', '>=', $date);

            $data = $query->select(
                'el.id as leave_id',
                'ra.id',
                'ra.first_name',
                'ra.last_name',
                'ra.profile_picture',
                'lc.leave_type',
                'lc.color',
                'e.Emp_id as employee_id',
                'rp.position_title as position',
                'rd.name as department'
            )->get();

            $getEmployeesOnLeaveToday       = $data->map(function ($employee) {
                $employee->profile_picture  = Common::getResortUserPicture($employee->id);
                return $employee;
            });

            return response()->json([
                'status'        => true,
                'message'       => 'Who is on leave details in HR.',
                'leave_detail'  => $getEmployeesOnLeaveToday
            ]);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function hodWhoIsOnLeave()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {

            $user           = Auth::guard('api')->user();
            $resort_id      = $user->resort_id;
            $employee       = $user->GetEmployee;

            if (!$employee) {
                return response()->json(['success' => false, 'message' => 'Employee record not found'], 200);
            }

            $date           = Carbon::today();
            $reporting_to   = $employee->id;
            $underEmp_id    = Common::getSubordinates($reporting_to);

            if (empty($underEmp_id)) {
                return response()->json(['status' => true, 'message' => 'No employees on leave today.', 'leave_detail' => []]);
            }

            $query = DB::table('employees_leaves as el')
                ->join('employees as e', 'e.id', '=', 'el.emp_id')
                ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
                ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
                ->join('resort_departments as rd', 'rd.id', '=', 'e.Dept_id')
                ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                ->where('el.resort_id', $resort_id)
                ->whereIn('el.emp_id', $underEmp_id)
                ->where('el.status', 'Approved')
                ->whereDate('el.from_date', '<=', $date)
                ->whereDate('el.to_date', '>=', $date);

            $data = $query->select(
                'el.id as leave_id',
                'ra.id',
                'ra.first_name',
                'ra.last_name',
                'ra.profile_picture',
                'lc.leave_type',
                'lc.color',
                'e.Emp_id as employee_id',
                'rp.position_title as position',
                'rd.name as department'
            )->get();

            $getEmployeesOnLeaveToday       = $data->map(function ($employee) {
                $employee->profile_picture  = Common::getResortUserPicture($employee->id);
                return $employee;
            });

            return response()->json([
                'status'        => true,
                'message'       => 'Who is on leave details in HOD.',
                'leave_detail'  => $getEmployeesOnLeaveToday
            ]);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function hrUpcomingEmployeeLeaveList(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $user           = Auth::guard('api')->user();
            $resort_id      =   $user->resort_id;
            $rank               = config('settings.Position_Rank');

            if ($request->filter        === 'weekly' || !$request->has('filter')) {
                // Default to weekly if filter is 'weekly' or no filter is provided
                $startDate      = Carbon::now()->startOfWeek(Carbon::SUNDAY)->format('Y-m-d');
                $endDate        = Carbon::now()->endOfWeek(Carbon::SATURDAY)->format('Y-m-d');
            } elseif ($request->filter  === 'monthly') {
                // For monthly filter
                $startDate      = Carbon::now()->startOfMonth()->format('Y-m-d');
                $endDate        = Carbon::now()->endOfMonth()->format('Y-m-d');
            } elseif ($request->filter  === 'yearly') {
                // For yearly filter
                $startDate      = Carbon::now()->startOfYear()->format('Y-m-d');
                $endDate        = Carbon::now()->endOfYear()->format('Y-m-d');
            }

            $query  = DB::table('employees_leaves as el')
                ->join('employees as e', 'e.id', '=', 'el.emp_id')
                ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
                ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
                ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                ->where('el.resort_id', $resort_id)
                ->where('el.status', 'Approved')
                ->whereDate('el.from_date', '<=', $endDate)
                ->whereDate('el.to_date', '>=', $startDate);

            $data =  $query->select(
                'ra.id as emp_id',
                'ra.first_name',
                'ra.last_name',
                'ra.profile_picture',
                'rp.position_title as position',
                'lc.leave_type',
                'lc.color',
                'lc.color',
                'e.Emp_id',
                'el.id as leave_id',
                'el.from_date',
                'el.to_date',
                DB::raw("DATEDIFF(el.to_date, el.from_date) + 1 as total_days")
            )->get();


            $upcomingEmployeeLeave = $data->map(function ($employee) {
                $employee->profile_picture      = Common::getResortUserPicture($employee->emp_id);
                return $employee;
            });


            if ($upcomingEmployeeLeave->isEmpty()) {
                $response['status']         = false;
                $response['message']        = 'No Upcoming Employee Leave Found';
                $response['upcoming_employee_leave']  = [];
            } else {
                $response['status']         = true;
                $response['message']        = 'Upcoming Employee Leave';
                $response['upcoming_employee_leave']  = $upcomingEmployeeLeave->toArray();
            }

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function hodUpcomingEmployeeLeaveList(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $user               = Auth::guard('api')->user();
            $resort_id          =   $user->resort_id;
            $employee   = $user->GetEmployee;
            // $rank               = config('settings.Position_Rank');
            // $current_rank       = $employee->rank ?? null;
            // $available_rank     = $rank[$current_rank] ?? '';
            // $isHR               = ($available_rank === "HR");
            // $isHOD              = ($available_rank === "HOD");

            $reporting_to   = $employee->id;
            $underEmp_id    = Common::getSubordinates($reporting_to);

            if ($request->filter        === 'weekly' || !$request->has('filter')) {
                // Default to weekly if filter is 'weekly' or no filter is provided
                $startDate      = Carbon::now()->startOfWeek(Carbon::SUNDAY)->format('Y-m-d');
                $endDate        = Carbon::now()->endOfWeek(Carbon::SATURDAY)->format('Y-m-d');
            } elseif ($request->filter  === 'monthly') {
                // For monthly filter
                $startDate      = Carbon::now()->startOfMonth()->format('Y-m-d');
                $endDate        = Carbon::now()->endOfMonth()->format('Y-m-d');
            } elseif ($request->filter  === 'yearly') {
                // For yearly filter
                $startDate      = Carbon::now()->startOfYear()->format('Y-m-d');
                $endDate        = Carbon::now()->endOfYear()->format('Y-m-d');
            }

            $query  = DB::table('employees_leaves as el')
                ->join('employees as e', 'e.id', '=', 'el.emp_id')
                ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
                ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
                ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                ->where('el.resort_id', $resort_id)
                ->whereIn('el.emp_id', $underEmp_id)
                ->where('el.status', 'Approved')
                ->whereDate('el.from_date', '<=', $endDate)
                ->whereDate('el.to_date', '>=', $startDate);

            // if ($isHOD) {
            //     $query->where('e.Dept_id', $dept_id);
            // }

            $data =  $query->select(
                'ra.id as emp_id',
                'ra.first_name',
                'ra.last_name',
                'ra.profile_picture',
                'rp.position_title as position',
                'lc.leave_type',
                'lc.color',
                'lc.color',
                'e.Emp_id',
                'el.id as leave_id',
                'el.from_date',
                'el.to_date',
                DB::raw("DATEDIFF(el.to_date, el.from_date) + 1 as total_days")
            )->get();


            $upcomingEmployeeLeave = $data->map(function ($employee) {
                $employee->profile_picture      = Common::getResortUserPicture($employee->emp_id);
                return $employee;
            });


            if ($upcomingEmployeeLeave->isEmpty()) {
                $response['status']         = false;
                $response['message']        = 'No Upcoming Employee Leave Found';
                $response['upcoming_employee_leave']  = [];
            } else {
                $response['status']         = true;
                $response['message']        = 'Upcoming Employee Leave';
                $response['upcoming_employee_leave']  = $upcomingEmployeeLeave->toArray();
            }

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function employeeLeaveRequestListHRHOD(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $user           = Auth::guard('api')->user();
            $resortId       = $user->resort_id;
            $employee       = $user->GetEmployee;
            $rank           = config('settings.Position_Rank');
            $current_rank   = $employee->rank ?? null;
            $available_rank = $rank[$current_rank] ?? '';
            $isHR           = ($available_rank === "HR");
            $isHOD          = ($available_rank === "HOD");

            if (!$resortId) {
                return response()->json(['success' => false, 'message' => 'Invalid resort ID.'], 403);
            }

            if ($request->filter        === 'weekly' || !$request->has('filter')) {
                // Default to weekly if filter is 'weekly' or no filter is provided
                $startDate      = Carbon::now()->startOfWeek(Carbon::SUNDAY)->format('Y-m-d');
                $endDate        = Carbon::now()->endOfWeek(Carbon::SATURDAY)->format('Y-m-d');
            } elseif ($request->filter  === 'monthly') {
                // For monthly filter
                $startDate      = Carbon::now()->startOfMonth()->format('Y-m-d');
                $endDate        = Carbon::now()->endOfMonth()->format('Y-m-d');
            } elseif ($request->filter  === 'yearly') {
                // For yearly filter
                $startDate      = Carbon::now()->startOfYear()->format('Y-m-d');
                $endDate        = Carbon::now()->endOfYear()->format('Y-m-d');
            }

            if ($request->has('year') && $request->has('month')) {
                // Use the provided year and month
                $year           = $request->year;
                $month          = $request->month;

                // Calculate the start and end dates for the specified or default month
                $startDate      = Carbon::create($year, $month, 1)->startOfMonth()->format('Y-m-d');
                $endDate        = Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d');
            }

            if ($resortId) {
                $empId                  =   $employee->id;

                $leaveDetails           =   DB::table('employees_leaves as el')
                                            ->join('employees_leaves_status as els', 'els.leave_request_id', '=', 'el.id')
                                            ->join('employees as e', 'e.id', '=', 'el.emp_id') // Main employee
                                            ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id') // Main employee admin details
                                            ->join('employees as delegated_emp', 'delegated_emp.id', '=', 'el.task_delegation') // Delegated employee
                                            ->join('resort_admins as ra_td', 'ra_td.id', '=', 'delegated_emp.Admin_Parent_id') // Task delegation admin details
                                            ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
                                            ->join('resort_departments as rd', 'rd.id', '=', 'e.Dept_id')
                                            ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
                                            ->where('el.resort_id', $resortId)
                                            ->whereBetween('el.from_date', [$startDate, $endDate]) // Filter by date range
                                            ->where('el.status', 'Pending')
                                            // ->where('els.status', 'Pending')
                                            ->where('els.approver_id', $empId);

                if ($isHOD) {
                    if ($request->dept_id) {
                        $leaveDetails->where('e.Dept_id', $request->dept_id);
                    }
                }

                // // Select the necessary fields
                $leaveDetails           =   $leaveDetails->select(
                                                'el.*',
                                                'e.Emp_id as employee_id',
                                                'e.rank',
                                                'els.approver_rank',
                                                'els.approver_id',
                                                // Main employee details
                                                'ra.first_name as employee_first_name',
                                                'ra.last_name as employee_last_name',
                                                'ra.profile_picture as employee_profile_picture',
                                                'rp.position_title as position',
                                                'rd.id as department_id',
                                                'rd.name as department',
                                                // Task delegation details
                                                'delegated_emp.Emp_id as task_delegation_emp_id',
                                                'ra_td.first_name as task_delegation_first_name',
                                                'ra_td.last_name as task_delegation_last_name',
                                                'ra_td.profile_picture as task_delegation_profile_picture',
                                                // Leave category details
                                                'lc.leave_type as leave_type',
                                                'lc.color'
                                            )->get();

                $baseUrl                =   url('/');

                // Group data by 'id'
                $leaveDetails       = $leaveDetails->groupBy('id')->map(function ($group) {


                    // Use the first record as the base
                    $base           = $group->first();
                    $baseUrl        = url('/');

                    // Collect approver details for the grouped records
                    $approveData    = EmployeeLeaveStatus::where('leave_request_id', $base->id)
                                        ->get()
                                        ->map(function ($empAppr) {
                                            $role = ucfirst(strtolower($empAppr->approver_rank ?? ''));
                                            $rank = config('settings.Position_Rank');
                                            $role = $rank[$role] ?? '';

                                            return [
                                                'approver_rank'     => $empAppr->approver_rank,
                                                'approver_id'       => $empAppr->approver_id,
                                                'status'            => $empAppr->status,
                                                'rank_type'         => $role,
                                            ];
                                        })->values();

                    // Add approve_data to the base record
                    $base->approve_data                 = $approveData;
                    $base->employee_profile_picture     = Common::getResortUserPicture($base->employee_id);
                    $base->attachments                  = $base->attachments ? $baseUrl . '/' . $base->attachments : '';
                    // Clear duplicate fields in the base record
                    unset($base->approver_rank, $base->approver_id);

                    return $base;
                })->values(); // Re-index the collection

                // Group the leave records by 'created_at' date (Access 'created_at' correctly as an object property)
                $groupedByDate = $leaveDetails->groupBy(function ($item) {
                    return \Carbon\Carbon::parse($item->created_at)->format('Y-m-d');
                });

                // Format the results with 'applied_on' as the key
                $formattedLeaveDetails          = [];
                foreach ($groupedByDate as $createdAt => $leaves) {
                    $formattedLeaveDetails[]    = [
                        'applied_on'            => $createdAt, // This will be the key
                        'leaves'                => $leaves,
                    ];
                }

                if (!$formattedLeaveDetails) {
                    return response()->json(['success' => false, 'message' => 'Leave details not found.'], 200);
                }
                $response['status'] = true;
                $response['message'] = 'Leave Details';
                $response['leave_request'] = $formattedLeaveDetails;

                return response()->json($response);
            }
        } catch (QueryException $qe) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error("Database Query Error: " . $qe->getMessage());
            return response()->json(['success' => false, 'message' => 'A database error occurred. Please contact support.'], 500);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error("Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }

    public function leaveRequestViewHRHOD($leave_id)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $user           = Auth::guard('api')->user();
            $employee       = $user->GetEmployee;
            $emp_id         = $employee->id;
            $resortId       = $user->resort_id;
            $decodedId      = base64_decode($leave_id);

            $rankConfig      = config('settings.Position_Rank');
            $currentRank     = $employee->rank ?? null;
            $availableRank   = $rankConfig[$currentRank] ?? '';
            $isHOD           = ($availableRank === "HOD");

            if (!$leave_id || !base64_decode($leave_id, true)) {
                return response()->json(['success' => false, 'message' => 'Invalid or missing leave ID.'], 200);
            }

            if (!$resortId) {
                return response()->json(['success' => false, 'message' => 'Invalid resort ID.'], 200);
            }

            if ($resortId) {
                // Fetch the leave details for the specific leave ID
                $leave_details_query = DB::table('employees_leaves as el')
                    // ->join('employees_leaves_status as els', 'els.leave_request_id', '=', 'el.id')
                    ->join('employees as e', 'e.id', '=', 'el.emp_id') // Main employee
                    ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id') // Main employee admin details
                    ->join('employees as delegated_emp', 'delegated_emp.id', '=', 'el.task_delegation') // Delegated employee
                    ->join('resort_admins as ra_td', 'ra_td.id', '=', 'delegated_emp.Admin_Parent_id') // Task delegation admin details
                    ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
                    ->join('resort_departments as rd', 'rd.id', '=', 'e.Dept_id')
                    ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
                    ->leftJoin('employee_travel_passes as etp', 'etp.leave_request_id', '=', 'el.id') // Join for travel passes
                    ->leftJoin('resort_transportations as rt', 'rt.id', '=', 'etp.transportation') // Join for transportation options
                    ->where('el.id', $decodedId)
                    ->where('el.resort_id', $resortId);

                $leaveDetail = $leave_details_query->select(
                    'el.*',
                    'e.Emp_id as employee_id',
                    'e.rank',
                    // Main employee details
                    'ra.first_name as employee_first_name',
                    'ra.last_name as employee_last_name',
                    'ra.profile_picture as employee_profile_picture',
                    'rp.position_title as position',
                    'rd.name as department',
                    // Task delegation details
                    'delegated_emp.Emp_id as task_delegation_emp_id',
                    'ra_td.first_name as task_delegation_first_name',
                    'ra_td.last_name as task_delegation_last_name',
                    'ra_td.profile_picture as task_delegation_profile_picture',
                    // Leave category details
                    'lc.leave_type as leave_type',
                    'lc.color',
                    'etp.arrival_date as island_arrival_date',
                    'etp.arrival_time as island_arrival_time',
                    'etp.departure_date as island_departure_date',
                    'etp.departure_time as island_departure_time',
                    'etp.arrival_reason',
                    'etp.departure_reason',
                    DB::raw('JSON_ARRAYAGG(JSON_OBJECT(
                        "transportation_name", rt.transportation_option,
                        "transportation_id", rt.id,
                        "id", etp.id,
                        "arrival_date", etp.arrival_date,
                        "arrival_time", etp.arrival_time,
                        "departure_date", etp.departure_date,
                        "departure_time", etp.departure_time
                    )) as transportation_details')
                )->groupBy('el.id')->first();

                if ($isHOD) {
                    $from_date  = $leaveDetail->from_date;
                    $end_date   = $leaveDetail->to_date;

                    $alreadyEmpLeaveQuery = DB::table('employees_leaves as el')
                        ->join('employees as e', 'e.id', '=', 'el.emp_id') // Main employee
                        ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id') // Main employee admin details
                        ->where(function ($query) use ($from_date, $end_date) {
                            // Check if the leave period overlaps with the given date range
                            $query->whereBetween('el.from_date', [$from_date, $end_date])  // 'from_date' is within the range
                                ->orWhereBetween('el.to_date', [$from_date, $end_date])    // 'to_date' is within the range
                                ->orWhere(function ($subQuery) use ($from_date, $end_date) {
                                    // Handle the case where the leave period completely encompasses the given range
                                    $subQuery->where('el.from_date', '<=', $end_date)
                                        ->where('el.to_date', '>=', $from_date);
                                });
                        })
                        ->where('el.id', '!=', $leaveDetail->id)
                        ->select(
                            'e.*',
                            'e.Emp_id as employee_id',
                            'e.rank',
                            'el.id as emp_leave_id',
                            'el.from_date',
                            'el.to_date',
                            'ra.first_name as employee_first_name',
                            'ra.last_name as employee_last_name'
                        )
                        ->get();
                }

                if ($leaveDetail) {
                    // Fetch total leave allocation for the employee
                    $emp_grade      = Common::getEmpGrade($leaveDetail->rank);
                    $benefit_grid   = DB::table('resort_benifit_grid as rbg')
                        ->join('resort_benefit_grid_child as rbgc', 'rbg.id', '=', 'rbgc.benefit_grid_id')
                        ->where('rbg.emp_grade', $emp_grade)
                        ->get();

                    // Calculate total leaves taken by the employee for the current year
                    $currentYearStart   = Carbon::now()->startOfYear()->format('Y-m-d');
                    $currentYearEnd     = Carbon::now()->endOfYear()->format('Y-m-d');

                    $leavesTaken    = DB::table('employees_leaves')
                        ->where('emp_id', $leaveDetail->employee_id)
                        ->where('status', 'Approved')
                        ->where(function ($query) use ($currentYearStart, $currentYearEnd) {
                            $query->whereBetween('from_date', [$currentYearStart, $currentYearEnd])
                                ->orWhereBetween('to_date', [$currentYearStart, $currentYearEnd]);
                        })
                        ->sum('total_days');

                    // Total allocation (sum of all allocated days across leave categories)
                    $totalAllocation                        = $benefit_grid->sum('allocated_days');

                    // Attach leave balance information
                    $leaveDetail->total_leave_allocation    = $totalAllocation;
                    $leaveDetail->leaves_taken              = $leavesTaken;

                    // Update profile picture dynamically
                    $leaveDetail->employee_profile_picture  = Common::getResortUserPicture($leaveDetail->employee_id);
                    $leaveDetail->transportation_details    = json_decode($leaveDetail->transportation_details, true);
                    // $leaveDetail->island_pass               = json_decode($leaveDetail->island_pass, true);
                    $baseUrl                                = url('/');
                    $leaveDetail->attachments               = $leaveDetail->attachments ? $baseUrl . '/' . $leaveDetail->attachments : '';
                    // $role                                   = ucfirst(strtolower($leaveDetail->approver_rank ?? ''));
                    // $rank                                   = config('settings.Position_Rank');
                    // $role                                   = $rank[$role] ?? '';
                    // $leaveDetail->rank_type                 = $role;

                    $approveData    = DB::table('employees_leaves_status as els')
                        ->where('els.leave_request_id', $leaveDetail->id)
                        ->select('els.approver_rank', 'els.approver_id', 'approved_at', 'status')
                        ->get();


                    // Map the approver_rank to rank type using the config
                    $rankConfig             =   config('settings.Position_Rank');
                    $approveData            =   $approveData->map(function ($item) use ($rankConfig) {
                        $item->rank_type    =   $rankConfig[$item->approver_rank] ?? 'Unknown';
                        return $item;
                    });


                    // Filter only if approver_id matches emp_id
                    $filteredApproveData    =   $approveData->where('approver_id', $emp_id);

                    // Attach approve_data to the main island pass object
                    // $leaveDetail->approve_data      = $approveData;

                    // If emp_id is found, assign filtered data, otherwise assign an empty array
                    $leaveDetail->approve_data  =   $filteredApproveData->isNotEmpty() ? $filteredApproveData->values() : [];
                    if ($isHOD) {
                        $leaveDetail->already_emp_leave = $alreadyEmpLeaveQuery;
                    }
                }

                if (!$leaveDetail) {
                    return response()->json(['success' => false, 'message' => 'Leave details not found.'], 200);
                }

                $response['status']                  = true;
                $response['message']                 = 'Leave Details';
                $response['leave_request']           = $leaveDetail;

                return response()->json($response);
            }
        } catch (QueryException $qe) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error("Database Query Error: " . $qe->getMessage());
            return response()->json(['success' => false, 'message' => 'A database error occurred. Please contact support.'], 500);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error("Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }

    public function getUpcomingBirthdaysList(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $user       = Auth::guard('api')->user();
            $resortId   = $user->resort_id;

            // Define date ranges
            $today = Carbon::today()->format('d-m'); // Current day and month (d-m)
            $tomorrow = Carbon::tomorrow()->format('d-m'); // Tomorrow's day and month (d-m)
            $startMonthDay = Carbon::today()->format('m-d');
            $endMonthDay = Carbon::today()->addMonths(11)->format('m-d');

            $upcomingBirthdays = Employee::with(['resortAdmin', 'position']) // Eager load both relationships
                ->whereRaw('SUBSTRING(dob, 1, 5) = ?', [$today]) // Compare day and month from the string
                ->orWhereRaw('SUBSTRING(dob, 1, 5) = ?', [$tomorrow])
                ->orWhereRaw('SUBSTRING(dob, 1, 5) >= ?', [$startMonthDay])
                ->orWhereRaw('SUBSTRING(dob, 1, 5) <= ?', [$endMonthDay])
                ->orderByRaw('SUBSTRING(dob, 4, 2) ASC')
                ->get();

            $upcomingBirthdays = $upcomingBirthdays->filter(function ($employee) {
                // Ensure `dob` is not empty and matches `d-m-Y` format
                return !empty($employee->dob) && preg_match('/^\d{2}-\d{2}-\d{4}$/', $employee->dob);
            })->map(function ($employee) {
                $employee->profile_picture = Common::getResortUserPicture($employee->Admin_Parent_id);
                try {
                    // Convert `d-m-Y` to Carbon instance, then extract month and day
                    $dob = Carbon::createFromFormat('d-m-Y', $employee->dob);
                    $dobMonthDay = $dob->format('m-d'); // Extract MM-DD
                    $employee->formatted_dob = $dob->format('l M, d'); // Format as `Day Month, Date`
                } catch (\Carbon\Exceptions\InvalidFormatException $e) {
                    $employee->formatted_dob = 'Invalid Date';
                }
                return $employee;
            });

            // Separate today's and tomorrow's birthdays
            $todayBirthdays = $upcomingBirthdays->filter(function ($employee) use ($today) {
                return substr($employee->dob, 0, 5) == $today;
            });

            $tomorrowBirthdays = $upcomingBirthdays->filter(function ($employee) use ($tomorrow) {
                return substr($employee->dob, 0, 5) == $tomorrow;
            });

            // Filter out remaining upcoming birthdays
            $remainingBirthdays = $upcomingBirthdays->filter(function ($employee) use ($today, $tomorrow) {
                $dobMonthDay = substr($employee->dob, 0, 5); // Extract 'mm-dd'

                return $dobMonthDay !== $today && $dobMonthDay !== $tomorrow;
            });


            $bithdayArray = [];
            $bithdayArray['todayBirthdays']     = $todayBirthdays->values(); // Reset keys
            $bithdayArray['tomorrowBirthdays']  = $tomorrowBirthdays->values(); // Reset keys
            $bithdayArray['remainingBirthdays'] = $remainingBirthdays->values(); // Reset keys


            $response['status']                     = true;
            $response['message']                    = 'Leave Details';
            $response['upcoming_birthday_list']     = $bithdayArray;

            return response()->json($response);
        } catch (QueryException $qe) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error("Database Query Error: " . $qe->getMessage());
            return response()->json(['success' => false, 'message' => 'A database error occurred. Please contact support.'], 500);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error("Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'An unexpected error occurred. Please try again later.'], 500);
        }
        $page_title = "Upcoming Birthdays";
        return view('resorts.leaves.dashboard.upcoming_birthdays', compact(
            'page_title',
            'resort_id',
            'todayBirthdays',
            'tomorrowBirthdays',
            'remainingBirthdays',
            'today',
            'tomorrow'
        ));
    }

    public function leaveDashboardHOD()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $user           = Auth::guard('api')->user();
        $employee       = $user->GetEmployee;
        $resort_id      = $user->resort_id;
        $reporting_to   = $employee->reporting_to;
        $emp_id         = $employee->id;

        try {
            $rank               = config('settings.Position_Rank');
            $current_rank       = $employee->rank ?? null;
            $available_rank     = $rank[$current_rank] ?? '';
            $isHR               = ($available_rank === "HR");
            $isGM               = ($available_rank === "GM");
            $isHOD              = ($available_rank === "HOD");

            $leaveCounts            = $this->getLeaveCounts($resort_id, $isHR, $isGM, $reporting_to);
            $upcomingEmployeeLeave  = $this->getUpcomingEmployeeLeave($resort_id, $isHOD, $employee->Dept_id);
            $leaveRequest           = $this->getUpcomingLeaveRequest($resort_id, $emp_id, $available_rank,$employee->rank);
            $upcomingHolidays       = $this->getUpcomingHolidays($resort_id);
            $islandPassHOD          = $this->islandPassHOD($resort_id, $employee);
            $employeesOnLeaveToday  = $this->getEmployeesOnLeaveToday($resort_id, $isHOD, $employee->Dept_id);
            $leaveData = [
                'total_applied_leaves'      => $leaveCounts['totalApplied'],
                'total_approved_leaves'     => $leaveCounts['totalApproved'],
                'total_rejected_leaves'     => $leaveCounts['totalRejected'],
                'total_pending_leaves'      => $leaveCounts['totalPending'],
                'employees_on_leave_today'  => $employeesOnLeaveToday,
                'upcoming_employee_leave'   => $upcomingEmployeeLeave,
                'leave_request'             => $leaveRequest,
                'island_pass'               => $islandPassHOD,
                'upcoming_holidays'         => $upcomingHolidays,
            ];

            return response()->json([
                'status' => true,
                'message' => 'HOD Leave Deshboard Details',
                'leave_detail' => $leaveData
            ]);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    private function islandPassHOD($resort_id, $employee)
    {
        $islandPassQuery    = DB::table('employee_travel_passes as etp')
            ->join('employees as e', 'e.id', '=', 'etp.employee_id') // Main employee
            ->join('employee_travel_pass_status as etps', 'etps.travel_pass_id', '=', 'etp.id')
            ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id') // Main employee admin details
            ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
            ->join('resort_departments as rd', 'rd.id', '=', 'e.Dept_id')
            ->select(
                'etp.*',
                'e.Admin_Parent_id as Admin_Parent_id',
                'ra.first_name',
                'ra.last_name',
                'ra.profile_picture',
                'rp.position_title as position',
                'etps.approver_rank',
                'etps.approver_id',
                'etps.approved_at',
                'etps.status',
            )
            ->where('etp.resort_id', $resort_id) // Order by ID descending
            ->where('e.reporting_to', $employee->id)
            ->orderBy('etp.id', 'desc') // Order by ID descending
            ->first();

        if ($islandPassQuery) {
            $approveData = DB::table('employee_travel_pass_status as etps')
                ->where('etps.travel_pass_id', $islandPassQuery->id)
                ->select('etps.approver_rank', 'etps.approver_id', 'etps.status')
                ->get();

            // Map the approver_rank to rank type using the config
            $rankConfig = config('settings.Position_Rank');
            $approveData = $approveData->map(function ($item) use ($rankConfig) {
                $item->rank_type = $rankConfig[$item->approver_rank] ?? 'Unknown';
                return $item;
            });

            // Attach approve_data to the main island pass object
            $islandPassQuery->approve_data = $approveData;
            $islandPassQuery->profile_picture    = Common::getResortUserPicture($islandPassQuery->employee_id);
        }

        return $islandPassQuery;
    }

    public function islandPassViewHR(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $user           = Auth::guard('api')->user();
        $employee       = $user->GetEmployee;
        $resort_id      = $user->resort_id;
        $reporting_to   = $employee->reporting_to;
        $emp_id         = $employee->id;

        try {

            $islandPassQuery    = DB::table('employee_travel_passes as etp')
                ->join('employees as e', 'e.id', '=', 'etp.employee_id') // Main employee
                ->join('employee_travel_pass_status as etps', 'etps.travel_pass_id', '=', 'etp.id')
                ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id') // Main employee admin details
                ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
                ->join('resort_departments as rd', 'rd.id', '=', 'e.Dept_id')
                ->select(
                    'etp.*',
                    'e.Admin_Parent_id as Admin_Parent_id',
                    'ra.first_name',
                    'ra.last_name',
                    'ra.profile_picture',
                    'rp.position_title as position',
                    'etps.approver_rank',
                    'etps.approver_id',
                    'etps.approved_at',
                )
                ->orderBy('etp.id', 'desc') // Order by ID descending
                ->get();


            // Group data by 'id'
            $islandPassQuery  = $islandPassQuery->groupBy('id')->map(function ($group) {
                // Use the first record as the base
                $base           = $group->first();
                // Collect approver details for the grouped records
                $approveData    = $group->map(function ($item) {

                    $role           = ucfirst(strtolower($item->approver_rank ?? ''));
                    $rank           = config('settings.Position_Rank');
                    $role           = $rank[$role] ?? '';

                    return [
                        'approver_rank' => $item->approver_rank,
                        'approver_id'   => $item->approver_id,
                        'rank_type'     => $role,
                        'status'        => $item->status,
                    ];
                })->unique()->values();

                // Add approve_data to the base record
                $base->approve_data     = $approveData;
                // Clear duplicate fields in the base record
                unset($base->approver_rank, $base->approver_id);
                $base->profile_picture  = Common::getResortUserPicture($base->Admin_Parent_id);

                return $base;
            })->values();

            return response()->json([
                'status'            => true,
                'message'           => 'Island Pass view',
                'island_view_hr'    => $islandPassQuery
            ]);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function islandPassViewHOD()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $user           = Auth::guard('api')->user();
        $employee       = $user->GetEmployee;
        $resort_id      = $user->resort_id;
        $reporting_to   = $employee->reporting_to;
        $emp_id         = $employee->id;

        try {

            $islandPassQuery    = DB::table('employee_travel_passes as etp')
                ->join('employees as e', 'e.id', '=', 'etp.employee_id') // Main employee
                ->join('employee_travel_pass_status as etps', 'etps.travel_pass_id', '=', 'etp.id')
                ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id') // Main employee admin details
                ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
                ->join('resort_departments as rd', 'rd.id', '=', 'e.Dept_id')
                ->select(
                    'etp.*',
                    'e.Admin_Parent_id as Admin_Parent_id',
                    'ra.first_name',
                    'ra.last_name',
                    'ra.profile_picture',
                    'rp.position_title as position',
                    'etps.approver_rank',
                    'etps.approver_id',
                    'etps.approved_at',
                )
                ->where('etp.resort_id', $resort_id)
                ->where('e.reporting_to', $employee->id)
                ->orderBy('etp.id', 'desc') // Order by ID descending
                ->get();

            // Group data by 'id'
            $islandPassQuery  = $islandPassQuery->groupBy('id')->map(function ($group) {
                // Use the first record as the base
                $base           = $group->first();
                // Collect approver details for the grouped records
                $approveData    = $group->map(function ($item) {

                    $role           = ucfirst(strtolower($item->approver_rank ?? ''));
                    $rank           = config('settings.Position_Rank');
                    $role           = $rank[$role] ?? '';

                    return [
                        'approver_rank' => $item->approver_rank,
                        'approver_id'   => $item->approver_id,
                        'rank_type'     => $role,
                        'status'        => $item->status,
                    ];
                })->unique()->values();

                // Add approve_data to the base record
                $base->approve_data     = $approveData;
                // Clear duplicate fields in the base record
                unset($base->approver_rank, $base->approver_id);
                $base->profile_picture  = Common::getResortUserPicture($base->Admin_Parent_id);

                return $base;
            })->values();

            return response()->json([
                'status'            => true,
                'message'           => 'Island Pass view',
                'island_view_hod'   => $islandPassQuery
            ]);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function islandPassRequestViewHODAndHR($pass_id)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $user           = Auth::guard('api')->user();
        $employee       = $user->GetEmployee;
        $resort_id      = $user->resort_id;
        $reporting_to   = $employee->reporting_to;
        $emp_id         = $employee->id;
        $pass_id        = base64_decode($pass_id);

        try {

            $islandPassRequestView    = DB::table('employee_travel_passes as etp')
                ->join('employees as e', 'e.id', '=', 'etp.employee_id') // Main employee
                ->join('employee_travel_pass_status as etps', 'etps.travel_pass_id', '=', 'etp.id')
                ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id') // Main employee admin details
                ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
                ->join('resort_departments as rd', 'rd.id', '=', 'e.Dept_id')
                ->select(
                    'etp.*',
                    'e.Admin_Parent_id as Admin_Parent_id',
                    'ra.first_name',
                    'ra.last_name',
                    'ra.profile_picture',
                    'rp.position_title as position',
                    'etps.approver_rank',
                    'etps.approver_id',
                    'etps.approved_at',
                )
                ->where('etp.resort_id', $resort_id)
                ->where('e.reporting_to', $employee->id)
                ->where('etp.id', $pass_id) // Order by ID descending
                ->orderBy('etp.id', 'desc') // Order by ID descending
                ->first();

            if ($islandPassRequestView) {
                $approveData    = DB::table('employee_travel_pass_status as etps')
                    ->where('etps.travel_pass_id', $islandPassRequestView->id)
                    ->select('etps.approver_rank', 'etps.approver_id')
                    ->get();

                // Map the approver_rank to rank type using the config
                $rankConfig     = config('settings.Position_Rank');
                $approveData    = $approveData->map(function ($item) use ($rankConfig) {
                    $item->rank_type = $rankConfig[$item->approver_rank] ?? 'Unknown';
                    return $item;
                });

                // Attach approve_data to the main island pass object
                $islandPassRequestView->approve_data = $approveData;
            }

            return response()->json([
                'status'                        => true,
                'message'                       => 'Island Pass Request view',
                'island_pass_request_view'      => $islandPassRequestView
            ]);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function handleLeaveAction(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'leave_id'                                  => 'required',
            'action'                                    => 'required',
            'reason'                                    => 'required_if:action,Rejected',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        try {
            $leaveId                                    =   $request->input('leave_id');
            $action                                     =   $request->input('action'); // Approve or Reject
            $comments                                   =   $request->input('reason', null); // Optional comments
            $user                                       =   Auth::guard('api')->user();
            $employee                                   =   $user->GetEmployee;
            $currentApproverId                          =   $employee->id; // Assuming the logged-in user is the approver
            $leave                                      =   EmployeeLeave::find($leaveId);

            if (!$leave) {
                return response()->json([
                    'status'                            => false,
                    'message'                           =>  'Leave request not found.',
                ], 200);
            }

            // Check if the current approver is authorized to take action
            $lastStatus                                 =   EmployeeLeaveStatus::where('leave_request_id', $leaveId)
                                                                ->where('status', 'Pending')
                                                                ->first();

            $rankConfig                                 =   config('settings.Position_Rank');
            $currentApproverRank                        =   array_key_exists($employee->rank, $rankConfig) ? $rankConfig[$employee->rank] : '';
            $lastApproverRank                           =   array_key_exists($lastStatus->approver_rank, $rankConfig) ? $rankConfig[$lastStatus->approver_rank] : '';
            $actionname                                 =   ($action == "Rejected") ? "reject" : "approve";

            if ($lastStatus && $lastStatus->approver_id != $currentApproverId) {
                return response()->json([
                    'status'                            =>  false,
                    'message'                           =>  "You cannot $actionname this request. The request must first be approved by the $lastApproverRank.",
                ], 200);
            }

            EmployeeLeaveStatus::where('leave_request_id', $leave->id)->where('approver_id', $currentApproverId)->update([
                'leave_request_id'                      =>  $leave->id,
                'approver_id'                           =>  $currentApproverId,
                'status'                                =>  $action,
                'comments'                              =>  $comments, // Save comments if provided
                'approved_at'                           =>  now(),
            ]);

                $empName            =   Employee::join('resort_admins as ra','ra.id','=','employees.Admin_Parent_id')
                                            ->where('employees.id', $leave->emp_id)
                                            ->select('ra.first_name', 'ra.last_name')
                                            ->first();

                $role               =   ucfirst(strtolower($user->GetEmployee->rank ?? ''));
                $rank               =   config('settings.Position_Rank');
                $role               =   $rank[$role] ?? '';

                    // Send In App Notification to each approver
                    Common::sendMobileNotification(
                        $user->resort_id,
                        2,
                        null,
                        null,
                        'Leave Request',
                        $empName->first_name . ' ' . $empName->last_name . ' Your leave request from '.$action . ' by '.$role,
                        'Leave',
                        [$leave->emp_id],
                        NULL,
                    );


            // $employeeLeaveStatus    = EmployeeLeaveStatus::where('leave_request_id' , $leave->id)->where('status','Approved')->get();

            $allApproved                                =   EmployeeLeaveStatus::where('leave_request_id', $leave->id)
                                                                ->where('status', '!=', 'Approved')
                                                                ->doesntExist();
            if ($allApproved) {
                EmployeeLeave::where('id', $leave->id)->update(['status' => $action]);
            }

            if ($action == 'Approved') {

                if ($lastStatus->approver_rank == 3 || $lastStatus->approver_rank == 8) {
                    $leave->status                      =   "Approved";
                    $leave->save();

                    // Send In App Notification to each approver
                    Common::sendMobileNotification(
                        $user->resort_id,
                        2,
                        null,
                        null,
                        'Leave Request',
                        $empName->first_name . ' ' . $empName->last_name . 'Your leave request from ' . $leave->from_date . ' to ' . $leave->to_date . ' has been ' . $action . '.',
                        'Leave',
                        [$leave->emp_id],
                        NULL,
                    );
                }

                return response()->json([
                    'status'                            =>  true,
                    'message'                           =>  'Leave approved successfully.',
                ]);

            } else if ($action === 'Rejected') {

                if($employee->rank != 12)
                {
                    $leave->status                          =   "Rejected";
                    $leave->save();
                }

                // Send In App Notification to each approver
                Common::sendMobileNotification(
                    $user->resort_id,
                    2,
                    null,
                    null,
                    'Leave Request',
                    $empName->first_name . ' ' . $empName->last_name . 'Your leave request from ' . $leave->from_date . ' to ' . $leave->to_date . ' has been ' . $action . '.',
                    'Leave',
                    [$leave->emp_id],
                    NULL,
                );

                return response()->json([
                    'status'                            =>  true,
                    'message'                           =>  'Leave Rejected.',
                ], 200);
            } else {
                return response()->json([
                    'status'                            =>  false,
                    'message'                           =>  'Invalid action.',
                ], 200);
            }
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    private function processLeaveWithHolidayCheck($resortId, $employeeId, $fromDate, $toDate,$type)
    {
        // Convert string dates to Carbon if needed
        $startDate                                      =   $fromDate instanceof Carbon ? $fromDate : Carbon::parse($fromDate);
        $endDate                                        =   $toDate instanceof Carbon ? $toDate : Carbon::parse($toDate);

        try {
            if($type == 'LeaveOverlapHoliday')
            {
                // Get all holidays within the date range
                $holidays                                       =   ResortHoliday::select([
                                                                    'resortholidays.id',
                                                                    'resortholidays.PublicHolidaydate as date',
                                                                    'resortholidays.PublicHolidayName as title',
                                                                ])
                                                                ->where('resort_id', $resortId)
                                                                ->whereRaw("DATE(PublicHolidaydate) BETWEEN ? AND ?", [
                                                                    $startDate->format('Y-m-d'),
                                                                    $endDate->format('Y-m-d')
                                                                ])
                                                                ->get();

                // If holidays exist within the leave period, record each as a compliance breach
                if ($holidays->isNotEmpty()) {
                    $employee                                   =   Employee::find($employeeId);

                    foreach ($holidays as $holiday) {
                        // Create compliance record for each holiday day that overlaps with leave
                        Compliance::create([
                            'resort_id'                         =>  $resortId,
                            'employee_id'                       =>  $employeeId,
                            'module_name'                       =>  'Leave',
                            'compliance_breached_name'          =>  'Leave taken on holiday',
                            'description'                       =>  'In leave request ' .  $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d') . ', the ' . $holiday->title . ' on ' . $holiday->date . ' has been included in the leave.',
                            'reported_on'                       =>  Carbon::now(),
                            'status'                            =>  'Breached',
                        ]);
                    }
                }

                return $holidays;
            }

            if($type == 'CheckEmployeeNoticePeriod')
            {
                // Get all holidays within the date range
                $employeeResignation                        =   EmployeeResignation::select('id','employee_id')
                                                                    ->where('resort_id', $resortId)
                                                                    ->where('employee_id', $employeeId)
                                                                    ->where('status', 'Approved')
                                                                    ->first();
                if($employeeResignation) {
                    Compliance::create([
                        'resort_id'                         =>  $resortId,
                        'employee_id'                       =>  $employeeId,
                        'module_name'                       =>  'Resignation',
                        'compliance_breached_name'          =>  'Leave taken on notice period',
                        'description'                       =>  'In leave request ' .  $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d') . ', the employee has taken leave during their notice period.',
                        'reported_on'                       =>  Carbon::now(),
                        'status'                            =>  'Breached',
                    ]);

                    return $employeeResignation;
                }
            }
            return null;
        } catch (\Exception $e) {
            \Log::error("Error in processLeaveWithHolidayCheck: " . $e->getMessage());
            return null;
        }
    }
}
