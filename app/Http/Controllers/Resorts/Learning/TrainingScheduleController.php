<?php
namespace App\Http\Controllers\Resorts\Learning;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\ResortPosition;
use App\Models\LearningProgram;
use Illuminate\Validation\Rule;
use App\Models\LearningCategory;
use App\Models\ResortDepartment;
use App\Models\LearningMaterials;
use App\Models\LearningRequest;
use App\Models\LearningRequestEmployee;
use App\Models\LearningCalendarSession;
use App\Models\TrainingParticipant;
use App\Models\TrainingSchedule;
use App\Events\ResortNotificationEvent;
use Illuminate\Support\Facades\Validator;
use DB;
use Auth;
use Common;
use DateTime;
use Carbon\Carbon;

class TrainingScheduleController extends Controller
{
    public $resort;
    public $reporting_to;
    protected $underEmp_id=[];
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if($this->resort->is_master_admin == 0){
            $this->reporting_to = $this->resort->GetEmployee->id;
            $this->underEmp_id = Common::getSubordinates($this->reporting_to);
        }
    }

    public function index() {
       
        if(Common::checkRouteWisePermission('learning.schedule',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }
        $page_title = "Learning Schedule";
        $trainings = TrainingSchedule::with('participants')->get();
        return view('resorts.learning.schedule.list', compact('trainings', 'page_title'));
    }

    public function schedule() {
        if(Common::checkRouteWisePermission('learning.schedule',config('settings.resort_permissions.create')) == false){
            return abort(403, 'Unauthorized access');
        }
        $page_title = "Learning Schedule";
        $employees = Employee::with(['resortAdmin','department','position'])->where('resort_id',$this->resort->resort_id)->whereIn('status', ['Active', 'Probationary'])->get();
        $programs= LearningProgram::where('resort_id',$this->resort->resort_id)->get();
        $departments = ResortDepartment::where('resort_id',$this->resort->resort_id)->where('status','active')->get();
        return view('resorts.learning.schedule.index',compact('page_title','employees','programs','departments'));
    }

    public function list(Request $request)
    {
        try {
            $resort_id = $this->resort->resort_id;

            $query = TrainingSchedule::select(
                'training_schedules.id',
                'training_schedules.training_id',
                'training_schedules.start_date',
                'training_schedules.end_date',
                'training_schedules.start_time',
                'training_schedules.end_time',
                'training_schedules.status',
                'training_schedules.created_at',
                'learning_programs.name as learning_name',
                'learning_programs.delivery_mode as learning_type',
                DB::raw("CONCAT(trainer_admin.first_name, ' ', trainer_admin.last_name) as trainer"), // Trainer from resort_admins
                DB::raw("GROUP_CONCAT(CONCAT(resort_admins.first_name, ' ', resort_admins.last_name) SEPARATOR ', ') as employee_names") // Attendees
            )
            ->leftJoin('learning_programs', 'training_schedules.training_id', '=', 'learning_programs.id')
            ->leftJoin('employees as trainer', 'learning_programs.trainer', '=', 'trainer.id') // Join trainer from employees table
            ->leftJoin('resort_admins as trainer_admin', 'trainer.Admin_Parent_id', '=', 'trainer_admin.id') // Fetch trainer's name from resort_admins
            ->leftJoin('training_participants', 'training_schedules.id', '=', 'training_participants.training_schedule_id')
            ->leftJoin('employees', 'training_participants.employee_id', '=', 'employees.id')
            ->leftJoin('resort_admins', 'resort_admins.id', '=', 'employees.Admin_Parent_id') // Fetch attendees' names from resort_admins
            ->where('training_schedules.resort_id', $resort_id);
            
            // Apply search
            if ($request->searchTerm) {
                $searchTerm = $request->searchTerm;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('learning_programs.name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('learning_programs.delivery_mode', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('training_schedules.status', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('training_schedules.start_date', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('training_schedules.end_date', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('training_schedules.start_time', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('training_schedules.end_time', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('resort_admins.first_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('resort_admins.last_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('trainer_admin.first_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('trainer_admin.last_name', 'LIKE', "%{$searchTerm}%");
                });
            }
            // Apply filter
            if ($request->type) {
                $query->Where('learning_programs.delivery_mode', $request->type);
            }

            if ($request->date) {
                $query->Where('training_schedules.start_date', $request->date)
                ->orWhere('training_schedules.end_date', $request->date);
            }
            
            // Group by to avoid duplicate rows
            $trainings = $query->groupBy('training_schedules.id')->get();

            $edit_class = '';
            if(Common::checkRouteWisePermission('learning.schedule',config('settings.resort_permissions.edit')) == false){
               $edit_class = 'd-none';
            }

            // dd($trainings);
            return datatables()->of($trainings)
            ->addColumn('trainer', function ($row) {
                $trainerImage = Common::getResortUserPicture($row->trainer_id); // Get trainer's profile picture
                return '
                    <div class="tableUser-block">
                        <div class="img-circle"><img src="' . $trainerImage . '" alt="user"></div>
                        <span>' . e($row->trainer) . '</span>
                    </div>
                ';
            })
            ->addColumn('attendees', function ($row) {
                $attendeeImages = '';
                $attendees = explode(', ', $row->employee_names); // Split names
                $employeeIds = explode(',', $row->employee_ids); // Split IDs
                $count = count($attendees);
                $displayLimit = 5; // Show 5 images max, rest as "+ count"
        
                foreach ($attendees as $index => $attendee) {
                    $image = Common::getResortUserPicture($employeeIds[$index] ?? null);
                    if ($index < $displayLimit) {
                        $attendeeImages .= '
                            <div class="img-circle">
                                <img src="' . $image . '" alt="' . e($attendee) . '">
                            </div>
                        ';
                    }
                }
        
                if ($count > $displayLimit) {
                    $attendeeImages .= '<div class="num">+' . ($count - $displayLimit) . '</div>';
                }
        
                return '<div class="user-ovImg">' . $attendeeImages . '</div>';
            })
            ->addColumn('action', function ($row) use ($edit_class) {
                $editUrl = 'javascript:void(0)';
                $editIcon = asset("resorts_assets/images/edit.svg");
                $attendanceUrl = route('learning.schedule.attendance', ['schedule_id' => base64_encode($row->id)]);
            
                return '<a href="' . $editUrl . '" title="Edit" class="btn-lg-icon icon-bg-green me-1 edit-row-btn '.$edit_class.'" data-schedule-id="' . e($row->id) . '">
                            <img src="' . $editIcon . '" alt="Edit" class="img-fluid">
                        </a>
                        <a href="' . $attendanceUrl . '" title="Mark Attendance" class="btn-sm-icon">
                            <i class="fas fa-calendar-check" aria-hidden="true"></i>
                        </a>';
            })
            ->rawColumns(['trainer', 'attendees', 'action'])
            ->make(true);
        
        } catch (\Exception $e) {
            \Log::error("Error fetching Learning Programs: " . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch data'], 500);
        }
    }

    public function store(Request $request) {
        // Debug the incoming request
        // dd($request->all());
    
        $request->validate([
            'learning_title' => 'required|exists:learning_programs,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'venue' => 'required|string|max:255',
            'description' => 'nullable|string',
            'employee_ids' => 'required|json'
        ]);
        
        $employeeIds = json_decode($request->employee_ids, true);

        // Check if the training ID exists in learning_programs
        $learningProgram = LearningProgram::where('id', $request->learning_title)->first();
        if (!$learningProgram) {
            return response()->json([
                'success' => false,
                'msg' => 'Selected learning program does not exist!'
            ], 400);
        }

        // Create the training schedule
        $training = TrainingSchedule::create([
            'resort_id' => $this->resort->resort_id,
            'training_id' => $request->learning_title,  // Ensure this matches the correct ID
            'start_date' => Carbon::parse($request->start_date)->format('Y-m-d'),
            'end_date' => Carbon::parse($request->end_date)->format('Y-m-d'),
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'venue' => $request->venue,
            'description' => $request->description,
            'status' => 'Scheduled',
        ]);
    
        // Insert participants
        foreach ($employeeIds as $employee_id) {
            TrainingParticipant::create([
                'training_schedule_id' => $training->id,
                'employee_id' => $employee_id,
                'status' => 'Pending'
            ]);

           
            $notificationTitle = 'Training Sceduled';
            $notificationMessage = "Training '{$learningProgram->name}' has been scheduled from {$request->start_date} to {$request->end_date}, between {$request->start_time} - {$request->end_time}.";
            $moduleName = "Learning";

            event(new ResortNotificationEvent(Common::nofitication(
                $this->resort->resort_id, 
                10, 
                $notificationTitle, 
                $notificationMessage, 
                'Learning', 
                $employee_id, 
                $moduleName
            )));
        }
    
        return response()->json([
            'success' => true, 
            'msg' => 'Learning scheduled successfully.',
            'redirect_url' => url('learning.schedule.index')
        ]);
    }
    
    public function getEmployeesDeptwise(Request $request)
    {
        $request->validate([
            'deptID' => 'required|exists:resort_departments,id'
        ]);

        $employees = Employee::where('Dept_id', $request->deptID)
            ->with(['resortAdmin', 'position'])
            ->get()
            ->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'full_name' => $employee->resortAdmin->full_name,
                    'position_title' => $employee->position->position_title,
                    'image' => Common::getResortUserPicture($employee->Admin_Parent_id)
                ];
            });

        return response()->json([
            'success' => true,
            'employees' => $employees
        ]);
    }

    public function inlineUpdate(Request $request)
    {
        try {
            // Validate the request data
            $validated = $request->validate([
                'id' => 'required|integer|exists:training_schedules,id',
                'start_date' => 'sometimes|nullable|date_format:d/m/Y',
                'end_date' => 'sometimes|nullable|date_format:d/m/Y',
            ]);

            // Find the schedule record
            $schedule = TrainingSchedule::findOrFail($request->id);

            // Track changes for notifications
            $oldStartDate = $schedule->start_date;
            $oldEndDate = $schedule->end_date;

            // Only update fields that were provided
            if ($request->has('start_date') && $request->start_date) {
                $startDateParts = explode('/', $request->start_date);
                $schedule->start_date = $startDateParts[2] . '-' . $startDateParts[1] . '-' . $startDateParts[0];
            }

            if ($request->has('end_date') && $request->end_date) {
                $endDateParts = explode('/', $request->end_date);
                $schedule->end_date = $endDateParts[2] . '-' . $endDateParts[1] . '-' . $endDateParts[0];
            }

            // Validate that the end date is after or equal to the start date
            if ($schedule->start_date && $schedule->end_date && strtotime($schedule->end_date) < strtotime($schedule->start_date)) {
                return response()->json([
                    'success' => false,
                    'message' => 'End date must be after or equal to start date'
                ], 422);
            }

            $schedule->save();

            // Get all attendees
            $attendees = TrainingParticipant::where('training_schedule_id', $schedule->id)->pluck('employee_id');

            if ($attendees->isNotEmpty()) {
                // Fetch training program name
                $learningProgram = LearningProgram::find($schedule->training_id);
                $trainingName = $learningProgram ? $learningProgram->name : "Training Program";

                // Construct the notification message
                $notificationTitle = 'Training Schedule Updated';
                $notificationMessage = "The schedule for '{$trainingName}' has been updated. New dates: From {$schedule->start_date} to {$schedule->end_date}.";

                $moduleName = "Learning";

                // Send notification to all attendees
                foreach ($attendees as $employee_id) {
                    event(new ResortNotificationEvent(Common::nofitication(
                        $this->resort->resort_id,
                        10,
                        $notificationTitle,
                        $notificationMessage,
                        'Learning',
                        $employee_id,
                        $moduleName
                    )));
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Schedule dates updated successfully, and all attendees have been notified.'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating schedule: ' . $e->getMessage()
            ], 500);
        }
    }

    public function history(Request $request)
    {
        if(Common::checkRouteWisePermission('learning.schedule',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }
        $page_title = 'Training History';
        $resortId = $this->resort->resort_id;

        $query = TrainingSchedule::with(['learningProgram', 'trainingAttendances', 'participants'])
            ->where('resort_id', $resortId)
            ->orderBy('start_date', 'desc');
        $trainings = $query->get();
        

        // Apply date filter
        if ($request->has('date') && $request->date !== '') {
            $date = \Carbon\Carbon::parse($request->date);
            $query->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date);
        }

        if ($request->ajax()) {
            return datatables()->of($trainings)
                ->addColumn('title', fn($row) => $row->learningProgram->name ?? 'N/A')
                ->addColumn('dates', fn($row) => date('d M Y', strtotime($row->start_date)) . ' - ' . date('d M Y', strtotime($row->end_date)))
                ->addColumn('participants', fn($row) => $row->participants->count())
                ->addColumn('attendance', function ($row) {
                    $totalDays = \Carbon\Carbon::parse($row->start_date)->diffInDays(\Carbon\Carbon::parse($row->end_date)) + 1;
                    $totalExpected = $totalDays * $row->participants->count();
                    $actualPresent = $row->trainingAttendances->where('status', 'Present')->count();
                    return $totalExpected > 0 ? round(($actualPresent / $totalExpected) * 100, 2) . '%' : '0%';
                })
                ->rawColumns(['title', 'dates', 'participants', 'attendance'])
                ->make(true);
        }

        return view('resorts.learning.schedule.history',compact('trainings','page_title'));
    }




    public function feedbackformAssignParticipant(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'training_schedule_id'      => 'required',
            'feedback_form_id'          => 'required',
           
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        try {
               
                $feedbackFormId                         =   $request->feedback_form_id;
                $scheduleId                             =   $request->training_schedule_id;
                $resort_id                              =   $this->resort_id;
                
                $trainingSchedule                    =   TrainingSchedule::with(['learningProgram', 'participants'])
                                                                ->where('training_schedules.resort_id', $resort_id)
                                                                ->where('training_schedules.training_id', $scheduleId)
                                                                ->first();
                $result  = '';
                foreach ($trainingSchedule->participants as $key => $value) {

                    $participant                        =   TrainingParticipant::where('training_schedule_id', $value->training_schedule_id)
                                                                ->where('employee_id', $value->employee_id)
                                                                ->first();

                    // Check if feedback already assigned
                    if ($participant && $participant->train_feedback_form_id === null) {

                        $participant->train_feedback_form_id = $feedbackFormId;
                        $participant->save();
                  
                        $notificationTitle                  =   'Learing Feedback Form';
                        $notificationMessage                =   'Feedback Form';
                        $moduleName                         =   "Learning";

                        $formTitle                          =   TrainingFeedbackForm::where('id',$feedbackFormId)->first();
                        $title                              =   $formTitle->form_name;
                        $message                            =   'Recive feedbackform notification';
                        $module                             =   'Learning';
                        
                        //Send mobile notification
                        $sendMobileNotification             =   Common::sendMobileNotification(
                                                                    $resort_id,
                                                                    $feedbackFormId,
                                                                    $value->training_schedule_id,
                                                                    $notificationTitle,
                                                                    $notificationMessage,
                                                                    $moduleName,
                                                                    [$value->employee_id],
                                                                    null,
                                                                );
                    }
                }
                
            return response()->json(['success' => true, 'message' => 'Feedback form send successfully'], 200);

        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }
    

}