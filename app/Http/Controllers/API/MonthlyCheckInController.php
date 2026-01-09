<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use App\Models\Resort;
use App\Models\Employee;
use App\Models\MonthlyCheckingModel;
use App\Models\LearningProgram;
use App\Models\LearningRequest;
use App\Models\LearningRequestEmployee;
use App\Models\ResortPosition;
use Validator;
use Auth;
use DB;
use Common;
use Carbon\Carbon;
use App\Events\ResortNotificationEvent;

class MonthlyCheckInController extends Controller
{
    protected $user;
    protected $resort_id;
  
    public function __construct()
    {
        if (Auth::guard('api')->check()) {
            $this->user = Auth::guard('api')->user();
            $this->resort_id = $this->user->resort_id;
        }
    }
    
    public function managerDashboard()
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            // Base query with common joins
            $baseQuery                                      =   MonthlyCheckingModel::where('resort_id', $this->resort_id);

            // Completed Count
            $completedCount                                 =   (clone $baseQuery)
                                                                    ->where('status', 'Conducted')
                                                                    ->count();

            // Scheduled Count
            $scheduledCount                                 =   (clone $baseQuery)
                                                                    ->where('status', 'Confirm')
                                                                    ->count();

            // Pending Acceptances MCI
            $pendingAcceptancesMCI                          =   (clone $baseQuery)
                                                                   ->where('status', 'Pending')
                                                                    ->count();

            // Employees Acknowledge Meeting
            $employeesAcknowledgeMeeting                    =   (clone $baseQuery)
                                                                    ->where('status', 'Pending')
                                                                    ->count();

           // Query to get counts for each status in a single query
            $static                                         =   MonthlyCheckingModel::where('resort_id', $this->resort_id)
                                                                    ->selectRaw('
                                                                        COUNT(CASE WHEN status = "Conducted" THEN 1 END) as completed_count,
                                                                        COUNT(CASE WHEN status = "Confirm" THEN 1 END) as schedule_count,
                                                                        COUNT(CASE WHEN status = "Pending" THEN 1 END) as pending_count,
                                                                        COUNT(*) as total_count
                                                                    ')
                                                                    ->first();
                                                        

            // Get the counts from the result
            $totalCount                                     =   $static->total_count;
            $completedCount                                 =   $static->completed_count;
            $scheduleCount                                  =   $static->schedule_count;
            $pendingCount                                   =   $static->pending_count;

            // Calculate percentages
            $completedPercentage                            =   ($totalCount > 0) ? ($completedCount / $totalCount) * 100 : 0;
            $schedulePercentage                             =   ($totalCount > 0) ? ($scheduleCount / $totalCount) * 100 : 0;
            $pendingPercentage                              =   ($totalCount > 0) ? ($pendingCount / $totalCount) * 100 : 0;

            $upComingMeeting                                =   MonthlyCheckingModel::join('employees as t1', 't1.id', '=', 'monthly_checking_models.emp_id')
                                                                    ->join('resort_admins as t2', 't2.id', '=', 't1.Admin_Parent_id')
                                                                    ->where('date_discussion', '>', Carbon::now()->toDateString())
                                                                    ->select([
                                                                        'monthly_checking_models.id',
                                                                        'monthly_checking_models.start_time',
                                                                        'monthly_checking_models.end_time',
                                                                        'monthly_checking_models.date_discussion',
                                                                        'monthly_checking_models.Area_of_Discussion',
                                                                        't1.Admin_Parent_id', 
                                                                        't2.first_name', 
                                                                        't2.last_name', 
                                                                        't2.profile_picture', 
                                                                    ])
                                                                    ->where('monthly_checking_models.status', 'Confirm')
                                                                    ->get()->map(function($item){
                                                                        $item->profile_picture =  Common::getResortUserPicture($item->Admin_Parent_id);
                                                                        return $item;
                                                                    });
            $monthlyCheckInArr = [
                'completed_count'                           =>  $completedCount,
                'scheduled_count'                           =>  $scheduledCount,
                'pending_acceptances_monthly_checkin'       =>  $pendingAcceptancesMCI,
                'employees_acknowledge_meeting'             =>  $employeesAcknowledgeMeeting,
                'static_percentage'                         =>  [
                    'completed_percentage'                  =>  $completedPercentage,
                    'schedule_percentage'                   =>  $schedulePercentage,
                    'pending_percentage'                    =>  $pendingPercentage,
                ],
                'upcoming_meeting'                          =>  $upComingMeeting,
            ];

            $response['status']                             =   true;
            $response['message']                            =   'Monthly Check-In Dashboard data fetched successfully';
            $response['monthly_checkIn_array']              =   $monthlyCheckInArr;

            return response()->json($response);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function learningProgram()
    {    
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        try
        {

            $learningProgram                                =   LearningProgram::where('resort_id', $this->resort_id)->orderBy("id","desc")->get();

            if ($learningProgram->isEmpty()) {
                return response()->json([
                    'status' => true,
                    'message' => 'No learning programs found',
                    'learning_program_list' => []
                ]);
            }

            
            $response['status']                             =   true;
            $response['message']                            =   'Learning Program data fetched successfully';
            $response['learning_program_list']              =   $learningProgram;
            return response()->json($response);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function learingManager()
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $trainingManagerTitles                      =   ['Training Director', 'L&D Manager', 'Learning & Development Head'];

            // Get position IDs that match the titles in the current resort
            $positionIds                                =   ResortPosition::where('resort_id',  $this->resort_id)
                                                                ->whereIn('position_title', $trainingManagerTitles)
                                                                ->pluck('id'); // Get the position IDs

            $learningManagers                           =   Employee::select('id','Admin_Parent_id', 'Position_id', 'resort_id', 'Emp_id')
                                                                ->with(['resortAdmin:id,first_name,last_name','position:id,position_title'])
                                                                ->whereIn('Position_id', $positionIds)
                                                                ->where('resort_id',$this->resort_id)
                                                                ->get();

            if ($learningManagers->isEmpty()) {
                return response()->json([
                    'status' => true,
                    'message' => 'No learning manager found',
                    'learning_plearning_manager_listrogram_list' => []
                ]);
            }

            $response['status']                         =   true;
            $response['message']                        =   'Learning Manager data fetched successfullyy';
            $response['learning_manager_list']          =   $learningManagers;
            return response()->json($response);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }         
    }

    public function monthlyCheckInStore(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'date_discussion'                               =>  'required',
            'start_time'                                    =>  'required',
            'end_time'                                      =>  'required',
            'Meeting_Place'                                 =>  'required',
            'Area_of_Discussion'                            =>  'required',
            'Area_of_Improvement'                           =>  'required',
            'Time_Line'                                     =>  'required',
            'emp_id'                                        =>  'required',
            'comment'                                       =>  'required',
           'learning_manager_id'                            =>  'required_with:tranining_id',
            ], [
            'Meeting_Place.unique'                          =>  'The Category Name ":input" already exists for this resort.',
            'Area_of_Improvement'                           =>  'Please Enter Area of Imporvement.',
            'Time_Line.required'                            =>  'Please Enter Time Line',
            'learning_manager_id.required_with'               =>  'Learning Manager is required when a training is selected.',
        ]);
        if($validator->fails())
        {
            return response()->json(['success' => false,'errors' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try
        {
            MonthlyCheckingModel::create([
                "Checkin_id"                                =>  Common::getMonthlyCheckIn(),
                "resort_id"                                 =>  $this->resort_id,
                "date_discussion"                           =>  $request->date_discussion, 
                "start_time"                                =>  $request->start_time, 
                "end_time"                                  =>  $request->end_time, 
                "Meeting_Place"                             =>  $request->Meeting_Place, 
                "Area_of_Discussion"                        =>  $request->Area_of_Discussion, 
                "Area_of_Improvement"                       =>  $request->Area_of_Improvement,
                "Time_Line"                                 =>  $request->Time_Line, 
                "comment"                                   =>  $request->comment,
                'status'                                    =>  'Pending',
                "tranining_id"                              =>  $request->tranining_id??'', 
                "emp_id"                                    =>  $request->emp_id, 
            ]);

            if(isset($request->tranining_id))
            {
                $l = LearningRequest::create([
                    "resort_id"                             =>  $this->resort_id,
                    "learning_id"                           =>  $request->tranining_id,
                    'status'                                =>  'Pending',
                    "reason"                                =>  $request->Area_of_Improvement,
                    "learning_manager_id"                   =>  $request->learning_manager_id, 
                ]);

                LearningRequestEmployee::create([
                    "employee_id"                           =>  $request->emp_id,
                    "learning_request_id"                   =>  $l->id,
                ]);
            }

            $msg                                =   'Meeting scheduled by HR for Monthly Check-In. Subject: ' . ($request->Area_of_Improvement ?? $request->Area_of_Discussion);
            $title                              =   'Monthly check-in Meeting Scheduled';
            $ModuleName                         =   'Performance';
            $sendMobileNotification             =   Common::sendMobileNotification($this->resort_id,2,null,null,$title,$msg,$ModuleName,[$request->emp_id],null);
            // event(new ResortNotificationEvent(Common::nofitication($this->resort_id, 10,$title,$msg,0,$request->emp_id,$ModuleName)));
            DB::commit();
            $response['status']                             =   true;
            $response['message']                            =   'Monthly Check in Stored successfully';
            return response()->json($response);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }                 
    }

    public function monthlyCheckInRescheduleMeeting(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'meeting_id'                                    =>  'required',
            'date_discussion'                               =>  'required',
            'venue'                                         =>  'required',
            'start_time'                                    =>  'required', 
            'end_time'                                      =>  'required', 
        ]);
        if($validator->fails())
        {
            return response()->json(['success' => false,'errors' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try
        {
            $meeting                                        =   MonthlyCheckingModel::where('id', $request->meeting_id)->where('status', 'Confirm')->first();

            if (!$meeting) {
                DB::rollBack();
                return response()->json([
                    'success'                               =>  false,
                    'message'                               =>  'Meeting not found or status is not Confirm.'
                ], 200);
            }
           
            $update                                         =   MonthlyCheckingModel::where('id',$request->meeting_id)->where('status','Confirm')->update([
                "date_discussion"                           =>  $request->date_discussion, 
                "start_time"                                =>  $request->start_time,
                "end_time"                                  =>  $request->end_time,
                "Meeting_Place"                             =>  $request->venue,
                "status"                                    =>  'Rescheduled',
            ]);

            $msg                                =   'Meeting Rescheduled by HR for Monthly Check-In Date '.$request->date_discussion;
            $title                              =   'Monthly check-in Meeting Rescheduled';
            $ModuleName                         =   'Performance';
            $sendMobileNotification             =   Common::sendMobileNotification($this->resort_id,null,null,$title,$msg,$ModuleName,[$meeting->emp_id],null);

            DB::commit();
            $response['status']                             =   true;
            $response['message']                            =   'Monthly Check in reschedule meeting successfully';
            return response()->json($response);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }   
    }

    public function employeeMonthlyCheckinDashboard()
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $employee_id                                        =  $this->user->GetEmployee->id;
  
        try {
            // Base query with common joins
            $baseQuery                                      =   MonthlyCheckingModel::join('employees as t1', 't1.id', '=', 'monthly_checking_models.emp_id')
                                                                    ->join('resort_admins as t2', 't2.id', '=', 't1.Admin_Parent_id')
                                                                    ->join("resort_positions as rp", "rp.id", "=", "t1.Position_id")
                                                                    ->leftjoin("training_schedules as t5", "t5.training_id", "=", "monthly_checking_models.tranining_id")
                                                                    ->where('monthly_checking_models.emp_id',$employee_id)
                                                                    ->select([
                                                                        'monthly_checking_models.id',
                                                                        'monthly_checking_models.start_time', 
                                                                        'monthly_checking_models.end_time',
                                                                        'monthly_checking_models.date_discussion',
                                                                        'monthly_checking_models.Meeting_Place',
                                                                        'monthly_checking_models.created_by',
                                                                        'monthly_checking_models.status',
                                                                        't2.first_name', 
                                                                        't2.last_name',
                                                                        'rp.position_title',
                                                                        't5.created_by as manager_id' 
                                                                    ]);
            $pendingAcknowledgements                        =   (clone $baseQuery)
                                                                    ->whereIn('monthly_checking_models.status',['Pending','Rescheduled'])
                                                                    ->get()->map(function($item){
                                                                        $createdByName          =   Employee::join('resort_admins as t2', 't2.id', '=', 'employees.Admin_Parent_id')
                                                                                                    ->where('t2.id',$item->created_by)->first();
                                                                    
                                                                        $item->meeting_with     =   $createdByName->first_name.' '.$createdByName->last_name;

                                                                        $managerCreatedByName   =   Employee::join('resort_admins as ra', 'ra.id', '=', 'employees.Admin_Parent_id')
                                                                                                    ->join("resort_positions as rp", "rp.id", "=", "employees.Position_id")
                                                                                                    ->where('ra.id',$item->manager_id)
                                                                                                    ->first();
                                                                        $item->manager_name     =   $managerCreatedByName ? $managerCreatedByName->first_name . ' ' . $managerCreatedByName->last_name : '';
                                                                        $item->position         =   $managerCreatedByName ? $managerCreatedByName->position_title : '';
                                                                        $item->manager_profile  =   Common::getResortUserPicture($item->manager_id);
                                                                        return $item;
                                                                    });

            $checkInHistory                                 =   (clone $baseQuery)
                                                                    ->where('monthly_checking_models.status', 'Conducted')
                                                                    ->get();

            $monthlyCheckInArr = [
                'pending_acknowledgements'                  =>  $pendingAcknowledgements,
                'check_in_history'                          =>  $checkInHistory,
            ];

            $response['status']                             =   true;
            $response['message']                            =   'Monthly Check-In Dashboard data fetched successfully';
            $response['monthly_check_in_employee']          =   $monthlyCheckInArr;

            return response()->json($response);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function MonthlyCheckinMeetingDetails($meetingId)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        
  
        try {
            $id = base64_decode($meetingId);
            $monthly                                        =   MonthlyCheckingModel::join("employees as t1", "t1.id", "=", "monthly_checking_models.emp_id")
                                                                    ->join("resort_admins as t2", "t2.id", "=", "t1.Admin_Parent_id")
                                                                    ->join("resort_positions as t3", "t3.id", "=", "t1.Position_id")
                                                                    ->leftjoin("learning_programs as t4", "t4.id", "=", "monthly_checking_models.tranining_id")
                                                                    ->leftjoin("training_schedules as t5", "t5.training_id", "=", "monthly_checking_models.tranining_id")
                                                                    ->where("t1.resort_id", $this->resort_id)
                                                                    ->where("monthly_checking_models.id", $id)
                                                                    ->orderBy("id","desc")
                                                                    ->first(['monthly_checking_models.id as Parent_m_id','t4.name as traniningname','t2.first_name','t2.last_name','t2.id as ParentId','t1.Emp_id as OrignalEmp_id','t3.position_title as PositionName','monthly_checking_models.*','t5.created_by as manager_id']);
            $monthly->profileImg                            =   Common::getResortUserPicture($monthly->ParentId);

            
            $createdByName                                  =   Employee::join('resort_admins as ra', 'ra.id', '=', 'employees.Admin_Parent_id')
                                                                    ->join("resort_positions as rp", "rp.id", "=", "employees.Position_id")
                                                                    ->where('ra.id',$monthly->manager_id)
                                                                    ->first();
            $monthly->manager_name                          =   $createdByName ? $createdByName->first_name . ' ' . $createdByName->last_name : '';
            $monthly->position                              =   $createdByName ? $createdByName->position_title : '';

            $response['status']                             =   true;
            $response['message']                            =   'Monthly Check-In Details fetched successfully';
            $response['monthly_check_detail']               =   $monthly;

            return response()->json($response);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function employeeConfirmMeeting(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $validator = Validator::make($request->all(), [
            'meeting_id'                                    =>  'required',
            'status'                                        =>  'required',
        ]);

        if($validator->fails())
        {
            return response()->json(['success' => false,'errors' => $validator->errors()], 400);
        }

        $employee_id                                        =  $this->user->GetEmployee->id;
  
        try {
            $monthly                                        =   MonthlyCheckingModel::join("employees as t1", "t1.id", "=", "monthly_checking_models.emp_id")
                                                                    ->join("resort_admins as t2", "t2.id", "=", "t1.Admin_Parent_id")
                                                                    ->where('monthly_checking_models.id', $request->meeting_id)
                                                                    // ->where('monthly_checking_models.status','Pending')
                                                                    ->where('monthly_checking_models.emp_id',$employee_id)
                                                                    ->select('monthly_checking_models.*','t1.Admin_Parent_id','t2.first_name','t2.last_name')
                                                                    ->first();

            if (!$monthly) {
                return response()->json(['success' => false, 'message' => 'Meeting not found or already confirmed'], 200);
            }
            
            $createdByEmployeeId                            =   Employee::where('Admin_Parent_id',$monthly->created_by)->first();

            // dd([$createdByEmployeeId->id]);
            $monthly->status                                =   'Confirm';                   
            $monthly->save();
    
            $msg                                            =   'Meeting Confirm by '.$monthly->first_name.' for Monthly Check-In';
            $title                                          =   'Monthly check-in Meeting Confirm';
            $ModuleName                                     =   'Performance';
            $sendMobileNotification                         =   Common::sendMobileNotification(
                                                                    $this->resort_id,
                                                                    2,
                                                                    null,
                                                                    null,
                                                                    $title,
                                                                    $msg,
                                                                    $ModuleName,
                                                                    [$createdByEmployeeId->id],
                                                                    null,
                                                                );
                                                                
            event(new ResortNotificationEvent(Common::nofitication($this->resort_id, 10,$title,$msg,0,$createdByEmployeeId->id,$ModuleName)));

            $response['status']                             =   true;
            $response['message']                            =   'Monthly Check-In meeting confirmed successfully';

            return response()->json($response);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function postMeetingEmployeeComment(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $validator = Validator::make($request->all(), [
            'meeting_id'                                    =>  'required',
            'employee_comment'                              =>  'required',
        ]);

        if($validator->fails())
        {
            return response()->json(['success' => false,'errors' => $validator->errors()], 400);
        }

        $employee_id                                        =  $this->user->GetEmployee->id;
  
        try {
            $monthly                                        =   MonthlyCheckingModel::where('id',$request->meeting_id)
                                                                    ->where('status','Conducted')
                                                                    ->where('emp_id',$employee_id)
                                                                    ->first();
            if (!$monthly) {
                return response()->json(['success' => false, 'message' => 'Meeting not found or not in Conducted status'], 200);
            }
            
            $monthly->employee_comment                      =   $request->employee_comment;                   
            $monthly->save();

            $response['status']                             =   true;
            $response['message']                            =   'Employee comment submitted successfully';

            return response()->json($response);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function monthlyCheckInHistory()
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $employeeMonthlyCheckings                       =   MonthlyCheckingModel::select('id','emp_id','Checkin_id','date_discussion','start_time','end_time','Meeting_Place','Area_of_Discussion','Area_of_Improvement','Time_Line','comment','employee_comment','status')
                                                                ->with(['employee:id,Admin_Parent_id,Position_id','employee.resortAdmin:id,first_name,last_name,profile_picture','employee.position:id,position_title'])
                                                                ->orderBy('emp_id')
                                                                ->whereIn('status',['Conducted','Rescheduled'])
                                                                ->orderBy('date_discussion', 'desc')
                                                                ->get()
                                                                ->groupBy('emp_id') ->map(function ($items) {
                                                                    $employee = $items->first()->employee;
                                                            
                                                                    return [
                                                                        'emp_id'                        => $employee->id,
                                                                        'first_name'                    => $employee->resortAdmin->first_name,
                                                                        'last_name'                     =>  $employee->resortAdmin->last_name,
                                                                        'postion'                       =>  $employee->position->position_title,
                                                                        'profile_picture'               =>  Common::getResortUserPicture( $employee->resortAdmin->id),
                                                                        'checkins' => $items->map(function ($checkin) {
                                                                            return [
                                                                                'id'                    => $checkin->id,
                                                                                'date_discussion'       => $checkin->date_discussion,
                                                                                'start_time'            => $checkin->start_time,
                                                                                'end_time'              => $checkin->end_time,
                                                                                'Area_of_Discussion'    => $checkin->Area_of_Discussion,
                                                                                'status'                => $checkin->status,
                                                                            ];
                                                                        })->values()
                                                                    ];
                                                                })->values(); // To get a clean array instead of a collection with keys

            $response['status']                             =   true;
            $response['message']                            =   'History Fetched Successfully';
            $response['history_data']                       =   $employeeMonthlyCheckings;

            return response()->json($response);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }

    }

}
