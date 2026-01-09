<?php

namespace App\Http\Controllers\Resorts\Learning;

use App\Http\Controllers\Controller;
use App\Events\ResortNotificationEvent;
use Illuminate\Http\Request;
use App\Models\LearningCategory;
use App\Models\ResortAdmin;
use App\Models\Employee;
use App\Models\LearningRequest;
use App\Models\EmployeeLeaveStatus;
use App\Models\ResortDepartment;
use App\Models\TrainingSchedule;
use App\Models\TrainingAttendance;
use App\Models\AttendanceParameters;
use Auth;
use DB;
use Common;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public $resort;
    public $reporting_to;
    protected $underEmp_id=[];
  
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        $this->reporting_to = isset($this->resort->GetEmployee) ? $this->resort->GetEmployee->id:0;
        $this->underEmp_id = Common::getSubordinates($this->reporting_to);
    }
    
    public function HR_Dashobard()
    {
        $page_title ='Learning';
        $resort_id= $this->resort->resort_id;
        $ongoing_trainings_count = TrainingSchedule::where('status','Ongoing')->where('resort_id', $resort_id)->count();
        $completed_trainings_count = TrainingSchedule::where('status','Completed')->where('resort_id', $resort_id)->count();
        $scheduled_trainings_count = TrainingSchedule::where('status','Scheduled')->where('resort_id', $resort_id)->count();
        $pending_trainings_count = LearningRequest::where('status','Pending')->where('resort_id', $resort_id)->count();
        if($this->resort->is_master_admin == 0){
            $pending_learning_request = LearningRequest::with('learning')->where('status','Pending')->where('resort_id',$resort_id)->where('created_by',$this->resort->GetEmployee->Admin_Parent_id)->get();
        }else{
            $pending_learning_request = LearningRequest::with('learning')->where('status','Pending')->where('resort_id',$resort_id)->get();
        }
        // dd( $pending_trainings_count);
        $trainings = TrainingSchedule::with(['learningProgram', 'trainingAttendances','participants'])
        ->where('resort_id', $resort_id)
        ->orderBy('start_date', 'desc')
        ->limit(5)
        ->get();
        // dd($trainings[0]->participants);
        // Fetch all training schedules
        $trainingSchedules = TrainingSchedule::with('participants.employee', 'trainingAttendances')->get();

        // Define the threshold for completion (e.g., 70%)
        $AttendanceParameters = AttendanceParameters::where('resort_id',$resort_id)->first();
           
   
        $completionThreshold = $AttendanceParameters['threshold_percentage'] ?? 0;
       $completionData = [];

        // Iterate over each training schedule
        foreach ($trainingSchedules as $schedule) {
            $totalParticipants = $schedule->participants->count();
            $completedParticipants = 0;

            // Iterate over each participant
            foreach ($schedule->participants as $participant) {
                // Count total sessions and attended sessions
                $totalSessions = $schedule->trainingAttendances->count();
                $attendedSessions = $schedule->trainingAttendances->where('employee_id', $participant->employee_id)->count();

                // Calculate attendance percentage
                $attendancePercentage = ($totalSessions > 0) ? ($attendedSessions / $totalSessions) * 100 : 0;

                // Check if attendance meets or exceeds the threshold
                if ($attendancePercentage >= $completionThreshold) {
                    $completedParticipants++;
                }
            }

            // Calculate completion rate for the training
            $completionRate = ($totalParticipants > 0) ? ($completedParticipants / $totalParticipants) * 100 : 0;

            // Store the data
            $completionData[] = [
                'training_name' => $schedule->learningProgram->name ?? 'Unnamed Program',
                'completion_rate' => $completionRate,
                'total_participants' => $totalParticipants,
                'completed_participants' => $completedParticipants,
            ];
        }
        // dd($completionData);
        return view('resorts.learning.dashboard.hrdashboard',compact('page_title','scheduled_trainings_count','pending_trainings_count','completed_trainings_count','pending_learning_request','trainings','completionData'));
    }

    public function admin_dashboard()
    {
        $page_title ='Learning';
        $resort_id= $this->resort->resort_id;
        $ongoing_trainings_count = TrainingSchedule::where('status','Ongoing')->where('resort_id', $resort_id)->count();
        $completed_trainings_count = TrainingSchedule::where('status','Completed')->where('resort_id', $resort_id)->count();
        $scheduled_trainings_count = TrainingSchedule::where('status','Scheduled')->where('resort_id', $resort_id)->count();
        $pending_trainings_count = LearningRequest::where('status','Pending')->where('resort_id', $resort_id)->count();
        $pending_learning_request = LearningRequest::with('learning')->where('status','Pending')->where('resort_id',$resort_id)->get();
        $trainings = TrainingSchedule::with(['learningProgram', 'trainingAttendances'])
        ->where('resort_id', $resort_id)
        ->orderBy('start_date', 'desc')
        ->limit(5)
        ->get();
        // dd( $pending_trainings_count);
        return view('resorts.learning.dashboard.admin-dashboard',compact('page_title','scheduled_trainings_count','pending_trainings_count','completed_trainings_count','pending_learning_request','trainings'));
    }

    public function hod_dashboard()
    {
        $page_title ='Learning';
        $resort_id= $this->resort->resort_id;
        // dd($this->resort->GetEmployee->Admin_Parent_id);
        $ongoing_trainings_count = TrainingSchedule::where('status','Ongoing')->where('resort_id', $resort_id)->count();
        $completed_trainings_count = TrainingSchedule::where('status','Completed')->where('resort_id', $resort_id)->count();
        $scheduled_trainings_count = LearningRequest::where('status','Approved')->where('resort_id', $resort_id)->where('created_by',$this->resort->GetEmployee->Admin_Parent_id)->count();
        $pending_trainings_count = LearningRequest::where('status','Pending')->where('resort_id', $resort_id)->where('created_by',$this->resort->GetEmployee->Admin_Parent_id)->count();
        // dd( $pending_trainings_count);
        $pending_learning_request = LearningRequest::with('learning')->where('status','Pending')->where('resort_id',$resort_id)->where('created_by',$this->resort->GetEmployee->Admin_Parent_id)->get();
        
        $trainings = TrainingSchedule::with(['learningProgram', 'trainingAttendances'])
        ->where('resort_id', $resort_id)
        ->orderBy('start_date', 'desc')
        ->limit(5)
        ->get();
        
        return view('resorts.learning.dashboard.hoddashboard',compact('page_title','ongoing_trainings_count','completed_trainings_count','scheduled_trainings_count','pending_trainings_count','pending_learning_request','trainings'));
    }

    public function manager_dashboard()
    {
        $page_title ='Learning';
        $resort_id= $this->resort->resort_id;
        $ongoing_trainings_count = TrainingSchedule::where('status','Ongoing')->where('resort_id', $resort_id)->count();
        $completed_trainings_count = TrainingSchedule::where('status','Completed')->where('resort_id', $resort_id)->count();
        $scheduled_trainings_count = LearningRequest::where('status','Approved')->where('resort_id', $resort_id)->where('created_by',$this->resort->GetEmployee->Admin_Parent_id)->count();
        $pending_trainings_count = TrainingSchedule::where('status','Scheduled')->where('resort_id', $resort_id)->count();
        $pending_learning_request = LearningRequest::with('learning')->where('status','Pending')->where('resort_id',$resort_id)->where('learning_manager_id',$this->resort->GetEmployee->id)->get();
        // dd($pending_learning_request);
        $categories = LearningCategory::withCount('programs')->get();
        $today = now()->toDateString(); // Get current date
        $absentees = TrainingAttendance::where('status', 'Absent')
            ->whereDate('attendance_date', $today) // Fetch only today's absentees
            ->with('employee.resortAdmin', 'schedule.learningProgram')
            ->get();
        $trainings = TrainingSchedule::with(['learningProgram', 'trainingAttendances'])
        ->where('resort_id', $resort_id)
        ->orderBy('start_date', 'desc')
        ->limit(5)
        ->get();
        return view('resorts.learning.dashboard.manager-dashboard',compact('page_title','scheduled_trainings_count','pending_trainings_count','completed_trainings_count','pending_learning_request','trainings','categories','absentees'));
    }

    public function details(Request $request, $id)
    {
        if(Common::checkRouteWisePermission('learning.request.add',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }
        $page_title ='Details';
        $request_detail = LearningRequest::with('learning','employees.employee.resortAdmin')->where('id',$id)->first();
        $createdBy = ResortAdmin::where('id',$request_detail->created_by)->first();
        // dd($request_detail);
        return view('resorts.learning.request.detail',compact('page_title','request_detail','createdBy'));
    }

    public function getAbsentees(){
        $page_title = "Absentees List";
        return view('resorts.learning.attendance.absentees',compact('page_title'));
    }

    public function getAllAbsenteesData(Request $request) {
        $query = TrainingAttendance::where('status', 'Absent')
            ->with(['employee.resortAdmin', 'schedule.learningProgram'])
            ->orderBy('attendance_date', 'desc'); // Order by date (latest first)
    
        // Apply search filter
        if ($request->has('searchTerm') && !empty($request->searchTerm)) {
            $query->whereHas('employee.resortAdmin', function ($q) use ($request) {
                $q->where('first_name', 'LIKE', '%' . $request->searchTerm . '%')
                ->orwhere('last_name', 'LIKE', '%' . $request->searchTerm . '%');
            });
        }
    
        // Apply date filter
        if ($request->has('date') && !empty($request->date)) {
            $query->whereDate('attendance_date', $request->date);
        }
    
        $absentees = $query->get()->map(function ($absentee) {
            return [
                'employee_name' => $absentee->employee->resortAdmin->full_name,
                'learning_name' => $absentee->schedule->learningProgram->name,
                'attendance_date' => $absentee->attendance_date,
                'profile_picture' => Common::getResortUserPicture($absentee->employee->resortAdmin->id) // Get image URL
            ];
        });
        // dd($absentees);
    
        return response()->json(['data' => $absentees]);
    }
    
}