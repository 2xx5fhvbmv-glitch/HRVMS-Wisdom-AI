<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use App\Models\Employee;
use App\Models\LearningCategory;
use App\Models\ResortPosition;
use App\Models\ResortDepartment;
use App\Models\LearningRequest;
use App\Models\TrainingSchedule;
use App\Models\ResortAdmin;
use App\Models\TrainingAttendance;
use App\Models\TrainingFeedbackForm;
use App\Models\TrainingParticipant;
use App\Models\TrainingFeedbackResponse;
use App\Models\EvaluationForm;
use App\Models\EvaluationFormResponse;
use App\Models\EmployeeItineraries;
use App\Models\LearningMaterials;
use App\Helpers\Common;
use App\Helpers\StorageHelper;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Validator;
use Auth;
use DB;
use Illuminate\Support\Facades\Http;

class LearningController extends Controller
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

    public function managerTrainingCalendar(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        try {
            
            if($request->start_date && $request->end_date) {
                $startDate                          =   $request->start_date ;
                $endDate                            =   $request->end_date;
            } else {                
                $startDate                          =   Carbon::now()->startOfMonth()->format('Y-m-d');
                $endDate                            =   Carbon::now()->endOfMonth()->format('Y-m-d');
            }

            // Fetch Training Schedules with Participants
            $sessions                               =   TrainingSchedule::where('resort_id', $this->resort_id)
                                                            ->where(function ($query) use ($startDate, $endDate) {
                                                            $query->whereBetween('start_date', [$startDate, $endDate])
                                                                ->orWhereBetween('end_date', [$startDate, $endDate])
                                                                ->orWhere(function ($subQuery) use ($startDate, $endDate) {
                                                                    $subQuery->where('start_date', '<=', $startDate)
                                                                            ->where('end_date', '>=', $endDate);
                                                                });
                                                        })
                                                        ->with(['learningProgram', 'participants.employee.resortAdmin','participants.employee.position'])
                                                        ->get();

            $events                                     =   [];
            // Process Training Schedules
            foreach ($sessions as $session) {
                $sessionData                            =   $this->formatSessionData($session);

                // Fetch Attendees from Employees
                $attendees                              =   [];
                foreach ($session->participants as $participant) {
                    $employee                           =   Employee::with('resortAdmin')->find($participant->employee_id);
                    if ($employee) {
                        $attendees[]                    =   [
                            'name'                      =>  $employee->resortAdmin ? $employee->resortAdmin->full_name : $employee->first_name . ' ' . $employee->last_name,
                            'image'                     =>  $employee->resortAdmin ? Common::getResortUserPicture($employee->resortAdmin->id) : ($employee->profile_picture ?? asset('default-profile.png')),
                            'position'                  =>  $employee->position->position_title ?? null,
                        ];
                    }
                }

                $sessionData['participants']            =   $attendees;
                $events[]                               =   $sessionData;
            }

            // Fetch Approved Learning Requests
            $learningRequests                           =   LearningRequest::where('status', 'Approved')
                                                            ->where('resort_id', $this->resort_id)
                                                            ->whereBetween('start_date', [$startDate, $endDate])
                                                            ->with(['learning', 'employees.employee.resortAdmin','employees.employee.position']) // Load employee and resortAdmin
                                                            ->get();

            // Process Learning Requests
            foreach ($learningRequests as $request) {
                $requestData                            =   $this->formatLearningRequestData($request);

                // Fetch Employees Attending
                $attendees                              =   [];
                foreach ($request->employees as $learningRequestEmployee) {
                    $employee                           =   $learningRequestEmployee->employee;
                    if ($employee) {
                        $attendees[]                    =   [
                            'name'                      =>  $employee->resortAdmin ? $employee->resortAdmin->full_name : $employee->first_name . ' ' . $employee->last_name,
                            'image'                     =>  $employee->resortAdmin ? Common::getResortUserPicture($employee->resortAdmin->id) : ($employee->profile_picture ?? asset('default-profile.png')),
                            'position'                  =>  $employee->position ? $employee->position->position_title : "",
                        ];
                    }
                }

                $requestData['participants']            =   $attendees;
                $events[]                               =   $requestData;
            }

            return response()->json(['success' => true, 'message' => 'Calender Traning data fetched Successfully', 'calender_learning_data' => $events], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    // Format Training Session Data
    private function formatSessionData($session)
    {
       
        return [
            'id'                                    => $session['learningProgram']['id'],
            'title'                                 => $session->learningProgram->name,
            'session_date'                          => $session->start_date,
            'start_time'                            => date('h:i A', strtotime($session->start_time)),
            'end_time'                              => date('h:i A', strtotime($session->end_time)),
            'description'                           => $session->learningProgram->description,
            'color'                                 => $session->color_class ?? '#28a745',
            'participants'                          => $session->participants->map(function ($user) {
                return [
                    'name'                          => $user->name,
                    'image'                         => asset('storage/' . $user->profile_image)
                ];
            }),
        ];
    }

    // Format Learning Request Data
    private function formatLearningRequestData($request)
    {
        // Fetch the creator's ResortAdmin details
        $creator                                    =   ResortAdmin::find($request->created_by);
        
        return [
            'id'                                    =>  $request->learning->id,
            'title'                                 =>  "Learning Request: " . $request->learning->name,
            'session_date'                          =>  $request->start_date,
            'start_time'                            =>  '09:00 AM', // Adjust if necessary
            'end_time'                              =>  '05:00 PM', // Adjust if necessary
            'description'                           =>  "Learning request from " . ($creator ? $creator->full_name : 'Unknown') . ". " . $request->learning->description,
            'color'                                 =>  '#ff9800', // Orange for differentiation
            'participants'                          =>  $request->employees->map(function ($emp) {
                return [
                    'name'                          =>  $emp->employee->first_name . ' ' . $emp->employee->last_name,
                    'image'                         =>  $emp->employee->profile_picture ? asset('storage/' . $emp->employee->profile_picture) : asset('default-profile.png'),
                ];
            }),
        ];
    }

    public function trainingDetails($scheduleId)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        try {

            $scheduleId                             =   base64_decode($scheduleId);
            $resort_id                              =   $this->resort_id;
            // Route param is named "schedule_id" but callers across the app
            // disagree on what they send: some pass the schedule's own row
            // id (training_schedules.id), others pass training_id (the
            // learning_programs FK, shared across every session of the same
            // program). Match either — resolving by the schedule's own id
            // also fixes a program with 2+ sessions always returning
            // whichever one sorted first under a training_id-only match.
            $sessions                               =   TrainingSchedule::with(['learningProgram', 'participants.employee.resortAdmin','learningProgram.category'])
                                                            ->where('training_schedules.resort_id', $resort_id)
                                                            ->where(function ($q) use ($scheduleId) {
                                                                $q->where('training_schedules.id', $scheduleId)
                                                                  ->orWhere('training_schedules.training_id', $scheduleId);
                                                            })
                                                            ->first();

            // Was dereferencing $sessions->learningProgram->trainer (and
            // several other $sessions->learningProgram-> fields below)
            // BEFORE this check ever ran — a scheduleId that doesn't
            // resolve to a real session, or a session whose learningProgram
            // relation is missing, crashed with a 500 ("Learning request
            // doesn't have the session details" on the client) instead of
            // a clean "not found".
            if (!$sessions || !$sessions->learningProgram) {
                return response()->json(['success' => false, 'message' => 'Training session not found'], 200);
            }

            $trainerData                            =   Employee::with('resortAdmin')->where('id',$sessions->learningProgram->trainer)
                                                            ->first();

            if ($trainerData) {
                $trainerData->profile = Common::getResortUserPicture($trainerData->Admin_Parent_id);
            }

            $data = [
                'training_id'                       =>  $sessions->learningProgram->id,
                'training_name'                     =>  $sessions->learningProgram->name,
                'training_start_date'               =>  $sessions->start_date,
                'training_end_date'                 =>  $sessions->end_date,
                'training_start_time'               =>  $sessions->start_time,
                'training_end_time'                 =>  $sessions->end_time,
                'location'                           =>  $sessions->venue,
                'category'                          =>  $sessions->learningProgram->category->category ?? 'N/A',
                'description'                       =>  $sessions->learningProgram->description ?? '',
                'trainer_first_name'                =>  optional(optional($trainerData)->resortAdmin)->first_name,
                'trainer_last_name'                 =>  optional(optional($trainerData)->resortAdmin)->last_name,
                'trainer_profile'                   =>  optional($trainerData)->profile,
            ];

            // Current employee's own feedback/evaluation form state for this
            // session — drives the app's button (Feedback Form/Evaluation
            // Form when pending vs. View Feedback Form/View Evaluation Form
            // once submitted). training_participants.train_*_form_id is set
            // when the L&D Manager assigns a form; the response tables key
            // off participant_id = employees.id (not training_participants.id
            // despite the relation name), matching feedbackStore()/
            // evaluationStore()'s own lookup.
            $myEmployeeId = optional($this->user->GetEmployee)->id;
            $myParticipant = $sessions->participants->firstWhere('employee_id', $myEmployeeId);

            $feedbackResponse = null;
            $evaluationResponse = null;
            if ($myEmployeeId) {
                $feedbackResponse = TrainingFeedbackResponse::where('training_id', $sessions->id)
                    ->where('participant_id', $myEmployeeId)
                    ->first();
                $evaluationResponse = EvaluationFormResponse::where('training_id', $sessions->id)
                    ->where('participant_id', $myEmployeeId)
                    ->first();
            }

            $data['feedback_form'] = [
                'assigned'      => (bool) optional($myParticipant)->train_feedback_form_id,
                'form_id'       => optional($myParticipant)->train_feedback_form_id,
                'submitted'     => (bool) $feedbackResponse,
                'form_res_id'   => optional($feedbackResponse)->id,
            ];
            $data['evaluation_form'] = [
                'assigned'      => (bool) optional($myParticipant)->train_evaluation_form_id,
                'form_id'       => optional($myParticipant)->train_evaluation_form_id,
                'submitted'     => (bool) $evaluationResponse,
                'form_res_id'   => optional($evaluationResponse)->id,
            ];

            $data['participants'] = [];
            foreach ($sessions->participants as $participant) {

                $employee                           =   $participant->employee;
                if ($employee) {
                    $position                       =   ResortPosition::where('id', $employee->Position_id)->first();

                    // Fetch attendance count for this employee in this training session
                    $total_present_days             =   TrainingAttendance::where('training_schedule_id', $sessions->id)
                                                        ->where('employee_id', $employee->id)
                                                        ->count();

                    // Calculate total training days
                    $total_training_days            =   \Carbon\Carbon::parse($sessions->start_date)->diffInDays(\Carbon\Carbon::parse($sessions->end_date)) + 1; // +1 to include start date

                    // Format attendance as Present/Total
                    $attendance_status              =   "{$total_present_days}/{$total_training_days}";


                    $employeeRank                   =   $employee->rank ?? null;
                    $rankConfig                     =   config('settings.Position_Rank');
                    $rankName                       =   $rankConfig[$employeeRank] ?? '';

                    $data['participants'][]         =   [
                        'id'                        =>  $employee->id,
                        'Emp_ID'                    =>  $employee->Emp_id,
                        'profile'                   =>  Common::getResortUserPicture($employee->Admin_Parent_id),
                        'employee_name'             =>  $employee->resortAdmin->first_name.' '.$employee->resortAdmin->last_name,
                        'position'                  =>  $position->position_title ?? 'N/A',
                        'attendance'                =>  $attendance_status,
                        'rank'                      =>  $rankName,
                    ];
                }
            }

            return response()->json([
                'success'                           =>  true,
                'message'                           =>  'Training details data fetched successfully',
                'training_data'                     =>  $data
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function trainingList()
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        try {

            $trainings                                  =   TrainingSchedule::with('learningProgram')
                                                                ->where('status', 'Ongoing')
                                                                ->where('resort_id', $this->resort_id)
                                                                ->get()
                                                                ->map(function ($training) {
                                                                    return [
                                                                        'id'                    =>  $training->learningProgram->id ?? null,
                                                                        'training_schedule_id'  =>  $training->id ?? null,
                                                                        'name'                  =>  $training->learningProgram->name ?? null
                                                                    ];
                                                                });
            return response()->json([
                'success'                           =>  true,
                'message'                           =>  'Training  list fetched successfully',
                'training_list'                     =>  $trainings
            ], 200);
                                                    
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function trainingBasedParticipant($scheduleId)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $scheduleId                             =   base64_decode($scheduleId);
            $resort_id                              =   $this->resort_id;

            $sessions                               =   TrainingSchedule::with(['learningProgram', 'participants.employee.resortAdmin'])
                                                            ->where('training_schedules.resort_id', $resort_id)
                                                            ->where('training_schedules.training_id', $scheduleId)
                                                            ->first();

            $data                                   =   [];

            if ($sessions) {
                foreach ($sessions->participants as $participant) {
                    $employee                       =   $participant->employee;
                    if ($employee && $employee->resortAdmin) {

                        $attendance                 =   TrainingAttendance::where('training_schedule_id', $sessions->id)
                                                            ->where('employee_id', $employee->id)
                                                            ->where('status','Present')->first();

                                                           
                        $status                     =   $attendance && $attendance->status === 'Present' ? 'Present' : '';
                        $data[]                     =   [
                            'id'                    =>  $employee->id,
                            'first_name'            =>  $employee->resortAdmin->first_name,
                            'last_name'             =>  $employee->resortAdmin->last_name,
                            'attendance'            =>  $status, // 'present' or 'pending'
                        ];
                    }
                }
            }

            return response()->json([
                'success'                           =>  true,
                'message'                           =>  'Participant list fetched successfully',
                'participant_list'                  =>  $data
            ], 200);
                                                    
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function markAttendance(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'training_schedule_id'      => 'required',
            'employees'                 => 'required|array|min:1',
            'employees.*.employee_id'   => 'required|exists:employees,id',
            'employees.*.status'        => 'required|in:Present,Absent,Late',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        try {
            $resort_id                              =   $this->resort_id;
            
            $trainingSchedule = TrainingSchedule::find($request->training_schedule_id);
           
            // Check if the training schedule exists
            if (!$trainingSchedule) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Invalid training schedule ID'
                ],200);
            }

            $currentDate = now()->toDateString();
            // Ensure training is within the valid date range
            if ($currentDate < $trainingSchedule->start_date || $currentDate > $trainingSchedule->end_date) {
                 return response()->json([
                    'success' => false, 
                    'message' => 'Attendance can only be marked during the training period'
                ], 200);

            }
         
            $absentEmployeeIds = [];
            foreach ($request->employees as $employeeData) {
                TrainingAttendance::updateOrCreate(
                    [
                        'training_schedule_id'      =>  $trainingSchedule->id,
                        'employee_id'               =>  $employeeData['employee_id'],
                        'attendance_date'           =>  $currentDate,
                    ],
                    [
                        'status'                    =>  $employeeData['status'],
                    ]
                );
                if ($employeeData['status'] === 'Absent') {
                    $absentEmployeeIds[] = $employeeData['employee_id'];
                }
            }

            $this->notifyTrainingAbsence($resort_id, $trainingSchedule->id, $absentEmployeeIds);

            return response()->json([
                'success'                           =>  true,
                'message'                           =>  'Attendance updated successfully',
            ], 200);
            
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error('Error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function employeeTrainingCalendar(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        try {

            if($request->start_date && $request->end_date){
                
                $startDate                          =   $request->start_date ;
                $endDate                            =   $request->end_date;

            } else {
                
                $startDate                          =   Carbon::now()->startOfMonth()->format('Y-m-d');
                $endDate                            =   Carbon::now()->endOfMonth()->format('Y-m-d');
            }
            $employeeId                            =   $this->user->GetEmployee->id;

            // Fetch Training Schedules with Participants
            $sessions                               =   TrainingSchedule::where('resort_id', $this->resort_id)
                                                            ->where(function ($query) use ($startDate, $endDate) {
                                                            $query->whereBetween('start_date', [$startDate, $endDate])
                                                                ->orWhereBetween('end_date', [$startDate, $endDate])
                                                                ->orWhere(function ($subQuery) use ($startDate, $endDate) {
                                                                    $subQuery->where('start_date', '<=', $startDate)
                                                                            ->where('end_date', '>=', $endDate);
                                                                });
                                                        })
                                                        ->whereHas('participants', function ($query) use ($employeeId) {
                                                            $query->where('employee_id', $employeeId);
                                                        })
                                                        ->with([
                                                            'learningProgram',
                                                            'participants' => function ($q) use ($employeeId) {
                                                                $q->where('employee_id', $employeeId)
                                                                  ->with(['employee.resortAdmin', 'employee.position']);
                                                            }
                                                        ])->get();

            $events                                     =   [];
            // Process Training Schedules
            foreach ($sessions as $session) {

                $sessionData =  [
                    'id'                                    =>  $session['learningProgram']['id'],
                    'title'                                 =>  $session->learningProgram->name,
                    'session_date'                          =>  $session->start_date,
                    'start_time'                            =>  date('h:i A', strtotime($session->start_time)),
                    'end_time'                              =>  date('h:i A', strtotime($session->end_time)),
                    'description'                           =>  $session->learningProgram->description,
                    'color'                                 =>  $session->color_class ?? '#28a745',
                ];

                $events[]                                   =   $sessionData;
            }

            return response()->json(['success' => true, 'message' => 'Employee Calender Traning data fetched Successfully', 'emp_calender_learning_data' => $events], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function employeeLearningDashbaord(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $employeeId                             =   $this->user->GetEmployee->id;
            $today                                  =   \Carbon\Carbon::now()->toDateString();

            // Was hard-restricted to the current calendar month on every
            // query below (fixed separately) AND, before that, filtered by
            // the raw `status` column — which is unreliable across this
            // whole table (confirmed: every single training_schedules row
            // in the system is still 'Scheduled', regardless of its actual
            // dates — nothing ever advances it). Web's HOD dashboard
            // (Learning\DashboardController::hod_dashboard()) already hit
            // this and switched to deriving Ongoing/Completed/Pending
            // purely from start_date/end_date vs today — mirroring that
            // same date-derived logic here instead of trusting `status`,
            // scoped to this employee's own participant records.
            $baseQuery                              =   function () use ($employeeId) {
                return TrainingSchedule::where('resort_id', $this->resort_id)
                    ->whereHas('participants', function ($query) use ($employeeId) {
                        $query->where('employee_id', $employeeId);
                    });
            };

            $ongoingCount                           =   $baseQuery()->where('start_date', '<=', $today)->where('end_date', '>=', $today)->count();
            $completedCount                         =   $baseQuery()->where('end_date', '<', $today)->count();
            $scheduledCount                         =   $baseQuery()->where('start_date', '>', $today)->count();
            // Matches hod_dashboard()'s own semantic: "pending" = not yet started.
            $pendingCount                           =   $scheduledCount;
            $assignedCount                          =   $ongoingCount + $completedCount + $scheduledCount;

            $completedHours                         =     TrainingSchedule::where('resort_id', $this->resort_id)
                                                                    ->whereHas('participants', function ($query) use ($employeeId) {
                                                                        $query->where('employee_id', $employeeId);
                                                                    })
                                                                    ->whereHas('trainingAttendances', function ($query) use ($employeeId) {
                                                                        $query->where('status', 'Present')
                                                                                ->where('employee_id', $employeeId);
                                                                    })
                                                                    ->with([
                                                                        'learningProgram',
                                                                        ])
                                                                    ->where('end_date', '<', $today)
                                                                   ->get()->reduce(function ($carry, $session) {
                                                                    $start = \Carbon\Carbon::parse($session->start_time);
                                                                    $end = \Carbon\Carbon::parse($session->end_time);
                                                                    $hours = $end->diffInMinutes($start) / 60;
                                                                    return $carry + $hours;
                                                                }, 0);


            $dashboardArr['training_completed_hours']   =   $completedHours;
            $dashboardArr['pending_training_count']     =   $pendingCount;
            $dashboardArr['ongoing_training_count']     =   $ongoingCount;
            $dashboardArr['completed_training_count']   =   $completedCount;

            $sessions                               =   TrainingSchedule::where('resort_id', $this->resort_id)
                                                            ->whereHas('participants', function ($query) use ($employeeId) {
                                                                $query->where('employee_id', $employeeId);
                                                            })
                                                            ->with([
                                                                'learningProgram',
                                                                'participants' => function ($q) use ($employeeId) {
                                                                    $q->where('employee_id', $employeeId)
                                                                    ->with(['employee.resortAdmin', 'employee.position']);
                                                                }
                                                            ])->get();

            $completedPercentage = $assignedCount > 0 ? round(($completedCount / $assignedCount) * 100, 2) : 0;
            $events                                 =   [];
            // Process Training Schedules
            foreach ($sessions as $session) {

                $sessionData =  [
                    'id'                            =>  $session['learningProgram']['id'],
                    'training_schedule_id'          =>  $session->id,
                    'title'                         =>  $session->learningProgram->name,
                    'session_date'                  =>  $session->start_date,
                    'start_time'                    =>  date('h:i A', strtotime($session->start_time)),
                    'end_time'                      =>  date('h:i A', strtotime($session->end_time)),
                    'description'                   =>  $session->learningProgram->description,
                    'status'                        =>  $session->status,
                ];

                $events[]                           =   $sessionData;
            }

            // Programs this employee already has an actual scheduled session
            // for (rendered above with real venue/trainer/dates via $sessions).
            // A learning request has no persisted link to the schedule it
            // eventually becomes — matched only by training_id == learning_id
            // — so once scheduled it must be skipped below, otherwise it
            // renders twice: once correctly, once as a stale stub with no
            // training_schedule_id, hardcoded 9-5 times, and the request's
            // own approval status instead of "Scheduled".
            $scheduledProgramIds                    =   $sessions->pluck('training_id')->filter()->unique();

            $learningRequests                       =   LearningRequest::join("learning_requests_employees as lre", "learning_requests.id", "=", 'lre.learning_request_id')
                                                            ->where('lre.employee_id', $employeeId)
                                                            ->where('learning_requests.resort_id', $this->resort_id)
                                                            ->get();

            // Process Learning Requests
            foreach ($learningRequests as $request) {
                if ($scheduledProgramIds->contains($request->learning_id)) {
                    continue;
                }

                $requestData                        =   [
                    'id'                            =>  $request->learning->id,
                    'training_schedule_id'          =>  null,
                    'title'                         =>  "Learning Request: " . $request->learning->name,
                    'session_date'                  =>  $request->start_date,
                    'start_time'                    =>  '09:00 AM', // Adjust if necessary
                    'end_time'                      =>  '05:00 PM', // Adjust if necessary
                    'description'                   =>  $request->learning->description,
                    'status'                        =>  $request->status,
                ];

                $events[]                           =   $requestData;
            }

            $dashboardArr['assign_trainig_prog_comp_percen']    =   $completedPercentage;
            $dashboardArr['assign_trainig_programs']            =   $events;

            // completed_training_count above was already correct — the
            // employee L&D screens ("Past Training Programs",
            // "Pending Feedback & Evaluation") need the actual items, not
            // just a count. Neither array existed at all before this.
            $completedSessions                      =   $sessions->filter(function ($session) use ($today) {
                                                            return $session->end_date < $today;
                                                        })->values();

            $dashboardArr['completed_trainings']    =   $completedSessions->map(function ($session) {
                                                            return [
                                                                'id'            =>  $session->id,
                                                                'title'         =>  $session->learningProgram->name ?? null,
                                                                'session_date'  =>  $session->start_date,
                                                                'end_date'      =>  $session->end_date,
                                                                'start_time'    =>  date('h:i A', strtotime($session->start_time)),
                                                                'end_time'      =>  date('h:i A', strtotime($session->end_time)),
                                                                'description'   =>  $session->learningProgram->description ?? null,
                                                                'status'        =>  $session->status,
                                                            ];
                                                        })->values();

            // Feedback forms are resort-wide (training_feedback_form has no
            // per-training link — see feedbackformListing()), a response is
            // keyed to (form_id, training_id, participant_id). "Pending" =
            // a completed training this employee attended that has no
            // response row yet, using whichever form the resort has
            // configured to submit against.
            $feedbackFormId                         =   TrainingFeedbackForm::where('resort_id', $this->resort_id)->value('id');
            $pendingFeedbackForms                   =   collect();
            if ($feedbackFormId && $completedSessions->isNotEmpty()) {
                $respondedTrainingIds               =   TrainingFeedbackResponse::where('participant_id', $employeeId)
                                                            ->whereIn('training_id', $completedSessions->pluck('id'))
                                                            ->pluck('training_id')->unique();
                $pendingFeedbackForms               =   $completedSessions->reject(function ($session) use ($respondedTrainingIds) {
                                                            return $respondedTrainingIds->contains($session->id);
                                                        })->map(function ($session) use ($feedbackFormId) {
                                                            return [
                                                                'training_schedule_id' =>  $session->id,
                                                                'feedback_form_id'     =>  $feedbackFormId,
                                                                'title'                =>  $session->learningProgram->name ?? null,
                                                                'session_date'         =>  $session->start_date,
                                                            ];
                                                        })->values();
            }
            $dashboardArr['pending_feedback_forms'] =   $pendingFeedbackForms;

            return response()->json(['success' => true, 'message' => 'Employee dashboard data fetched Successfully', 'emp_dashboard_data' => $dashboardArr], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function feedbackformListing()
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
                
            $form = TrainingFeedbackForm::where('resort_id', $this->resort_id)->get();
            
            if (!$form) {
                return response()->json(['success' => false, 'message' => 'Form not found'], 200);
            }

            return response()->json(['success' => true, 'message' => 'Feedback form data fetched Successfully', 'feedback_form_listing' => $form], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function feedbackStore(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'feedback_form_id'                          => 'required',
            'training_schedule_id'                      => 'required',
            'responses'                                 => 'required',
           
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }
      
        DB::beginTransaction();
        try {
               
                $feedbackFormId                         =   $request->feedback_form_id;
                $trainingScheduleId                     =   $request->training_schedule_id;
                $resort_id                              =   $this->resort_id;
                $participant_id                         =   $this->user->GetEmployee->id;
                $responses                              =   $request->responses;

                 // Check if feedback already exists
                $existing                               =   TrainingFeedbackResponse::where('form_id', $feedbackFormId)
                                                                ->where('training_id', $trainingScheduleId)
                                                                ->where('participant_id', $participant_id)
                                                                ->first();
                if ($existing) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Feedback has already been submitted for this participant.'
                    ], 200);
                }
                
                TrainingFeedbackResponse::create([
                    'form_id'                           => $feedbackFormId,
                    'training_id'                       => $trainingScheduleId,
                    'participant_id'                    => $participant_id,
                    'responses'                         => $responses,
                ]);

                $this->notifyFormSubmitted($resort_id, $participant_id, $trainingScheduleId, $feedbackFormId, 'feedback');

                DB::commit();
            return response()->json(['success' => true, 'message' => 'Feedback data stored successfully'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function participantFeedbackFromList(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'training_schedule_id'      => 'required',
           
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        try {
                $trainingScheduleId                     =   $request->training_schedule_id;

                // Retrieve participant feedback list with conditional where
                $trainingFeedbackResponse               =   TrainingFeedbackResponse::join('employees as e','e.id','training_feedback_responses.participant_id')
                                                                ->join('resort_admins as t1', "t1.id", "=", "e.Admin_Parent_id")
                                                                ->join('resort_positions as t2', "t2.id", "=", "e.Position_id")
                                                                ->when($trainingScheduleId, function ($query, $trainingScheduleId) {
                                                                    return $query->where('training_feedback_responses.training_id', $trainingScheduleId);
                                                                })
                                                                ->select(
                                                                   'training_feedback_responses.*',
                                                                    't1.id as Parentid',
                                                                    't1.first_name',
                                                                    't1.last_name',
                                                                    't1.profile_picture',
                                                                    'e.id as emp_id',
                                                                    't2.position_title',
                                                                )->get()
                                                                ->map(function ($item) {
                                                                    $item->profile_picture = Common::getResortUserPicture($item->Parentid);
                                                                    return $item;
                                                                });

                if ($trainingFeedbackResponse->isEmpty()) {
                    return response()->json([
                        'success'                       => true,
                        'message'                       => 'No feedback records found',
                        'feedback_listing'              => []
                    ]);
                }

                return response()->json([
                    'success'                           =>  true,
                    'message'                           =>  'Feedback data retrieved successfully',
                    'feedback_listing'                  =>  $trainingFeedbackResponse
                ], 200);
        

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function feedbackFormResView($formResId)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {   
                                                            
            $trainingFeedbackResponse                   =   TrainingFeedbackResponse::join('training_feedback_form as tff','tff.id','training_feedback_responses.form_id')
                                                                ->when($formResId, function ($query, $formResId) {
                                                                    return $query->where('training_feedback_responses.id', $formResId);
                                                                })
                                                                ->select(
                                                                    'training_feedback_responses.*',
                                                                   'tff.form_name', 
                                                                   'tff.form_structure',
                                                                )->first();

            return response()->json([
                'success'                           =>  true,
                'message'                           =>  'Feedback data retrieved successfully',
                'feedback_form_res_view'            =>  $trainingFeedbackResponse
            ], 200);


        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * Structural mirror of feedbackformListing()/feedbackStore()/
     * participantFeedbackFromList()/feedbackFormResView() for evaluation
     * forms — same shapes, same conventions, see those methods' comments
     * for the reasoning behind them.
     */
    public function evaluationformListing()
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $form = EvaluationForm::where('resort_id', $this->resort_id)->get();

            return response()->json(['success' => true, 'message' => 'Evaluation form data fetched Successfully', 'evaluation_form_listing' => $form], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function evaluationStore(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'evaluation_form_id'                        => 'required',
            'training_schedule_id'                      => 'required',
            'responses'                                  => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
                $evaluationFormId                       =   $request->evaluation_form_id;
                $trainingScheduleId                     =   $request->training_schedule_id;
                $participant_id                         =   $this->user->GetEmployee->id;
                $responses                              =   $request->responses;

                $existing                               =   EvaluationFormResponse::where('form_id', $evaluationFormId)
                                                                ->where('training_id', $trainingScheduleId)
                                                                ->where('participant_id', $participant_id)
                                                                ->first();
                if ($existing) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Evaluation has already been submitted for this participant.'
                    ], 200);
                }

                EvaluationFormResponse::create([
                    'form_id'                           => $evaluationFormId,
                    'training_id'                       => $trainingScheduleId,
                    'participant_id'                    => $participant_id,
                    'responses'                         => $responses,
                ]);

                $this->notifyFormSubmitted($this->resort_id, $participant_id, $trainingScheduleId, $evaluationFormId, 'evaluation');

                DB::commit();
            return response()->json(['success' => true, 'message' => 'Evaluation data stored successfully'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function participantEvaluationFromList(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'training_schedule_id'      => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        try {
                $trainingScheduleId                     =   $request->training_schedule_id;

                $evaluationResponse                     =   EvaluationFormResponse::join('employees as e','e.id','evaluation_form_responses.participant_id')
                                                                ->join('resort_admins as t1', "t1.id", "=", "e.Admin_Parent_id")
                                                                ->join('resort_positions as t2', "t2.id", "=", "e.Position_id")
                                                                ->when($trainingScheduleId, function ($query, $trainingScheduleId) {
                                                                    return $query->where('evaluation_form_responses.training_id', $trainingScheduleId);
                                                                })
                                                                ->select(
                                                                   'evaluation_form_responses.*',
                                                                    't1.id as Parentid',
                                                                    't1.first_name',
                                                                    't1.last_name',
                                                                    't1.profile_picture',
                                                                    'e.id as emp_id',
                                                                    't2.position_title',
                                                                )->get()
                                                                ->map(function ($item) {
                                                                    $item->profile_picture = Common::getResortUserPicture($item->Parentid);
                                                                    return $item;
                                                                });

                if ($evaluationResponse->isEmpty()) {
                    return response()->json([
                        'success'                       => true,
                        'message'                       => 'No evaluation records found',
                        'evaluation_listing'            => []
                    ]);
                }

                return response()->json([
                    'success'                           =>  true,
                    'message'                           =>  'Evaluation data retrieved successfully',
                    'evaluation_listing'                =>  $evaluationResponse
                ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function evaluationFormResView($formResId)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $evaluationResponse                         =   EvaluationFormResponse::join('evaluation_form as ef','ef.id','evaluation_form_responses.form_id')
                                                                ->when($formResId, function ($query, $formResId) {
                                                                    return $query->where('evaluation_form_responses.id', $formResId);
                                                                })
                                                                ->select(
                                                                    'evaluation_form_responses.*',
                                                                   'ef.form_name',
                                                                   'ef.form_structure',
                                                                )->first();

            return response()->json([
                'success'                           =>  true,
                'message'                           =>  'Evaluation data retrieved successfully',
                'evaluation_form_res_view'          =>  $evaluationResponse
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * Alerts HR (Common::getResortHrEmployeeIds) and L&D Manager
     * (Common::getResortLdManagerEmployeeIds) whenever an employee submits a
     * feedback or evaluation form, so review isn't missed. Excludes the
     * submitter themself in case they happen to also be HR/L&D.
     */
    private function notifyFormSubmitted($resortId, $submitterEmployeeId, $trainingScheduleId, $formId, string $formType)
    {
        $notifyIds = array_values(array_diff(
            array_unique(array_merge(
                Common::getResortHrEmployeeIds($resortId),
                Common::getResortLdManagerEmployeeIds($resortId)
            )),
            [(int) $submitterEmployeeId]
        ));

        if (empty($notifyIds)) {
            return;
        }

        $submitterName = optional($this->user)->full_name ?? 'An employee';
        $trainingName  = optional(optional(TrainingSchedule::with('learningProgram')->find($trainingScheduleId))->learningProgram)->name;
        $label         = $formType === 'evaluation' ? 'Evaluation' : 'Feedback';

        Common::sendMobileNotification(
            $resortId,
            2,
            $formId,
            $trainingScheduleId,
            "{$label} Form Submitted",
            "{$submitterName} submitted {$label}" . ($trainingName ? " for {$trainingName}" : '') . '.',
            'Learning',
            $notifyIds,
            null,
            false,
            "training-{$formType}-form-submitted"
        );
    }

    /**
     * Alerts HR (Common::getResortHrEmployeeIds) and L&D Manager
     * (Common::getResortLdManagerEmployeeIds) whenever an employee is marked
     * Absent from a training session — same recipient-resolution as
     * notifyFormSubmitted() above. Shared by both markAttendance()
     * (trainer-facing) and ldManagerMarkAttendanceStore() (L&D-manager-facing).
     */
    private function notifyTrainingAbsence($resortId, $trainingScheduleId, array $absentEmployeeIds)
    {
        if (empty($absentEmployeeIds)) {
            return;
        }

        $notifyIds = array_values(array_unique(array_merge(
            Common::getResortHrEmployeeIds($resortId),
            Common::getResortLdManagerEmployeeIds($resortId)
        )));

        if (empty($notifyIds)) {
            return;
        }

        $trainingName = optional(optional(TrainingSchedule::with('learningProgram')->where('resort_id', $resortId)->find($trainingScheduleId))->learningProgram)->name;

        $absentNames = Employee::with('resortAdmin')->whereIn('id', $absentEmployeeIds)->where('resort_id', $resortId)->get()
            ->map(fn($e) => optional($e->resortAdmin) ? trim($e->resortAdmin->first_name . ' ' . $e->resortAdmin->last_name) : null)
            ->filter()->implode(', ');

        Common::sendMobileNotification(
            $resortId,
            2,
            null,
            $trainingScheduleId,
            'Training Absence',
            ($absentNames ?: 'An employee') . ' marked Absent' . ($trainingName ? " for {$trainingName}" : '') . '.',
            'Learning',
            $notifyIds,
            null,
            false,
            'training-attendance-absence'
        );
    }

    /**
     * L&D Manager identity check — mirrors the exact pattern established in
     * Resorts\Learning\LearningController (web) and Common::hasFullDataAccess's
     * L&D branch: position title match OR L&D department membership.
     * Deliberately NOT rank-based (no HOD/EXCOM/HR/GM shortcut) — the mobile
     * L&D Manager screens are scoped to the actual L&D Manager only. Routes
     * are already gated by the 'ld.manager' middleware; this is a second,
     * defence-in-depth check at the controller level, matching how the web
     * LearningController re-checks the same thing inline in every action.
     */
    private function isLdManager($employee)
    {
        if (!$employee) return false;
        $ldManagerTitles = ['Training Director', 'L&D Manager', 'Learning & Development Head'];
        $positionTitle = optional($employee->position)->position_title;
        return in_array($positionTitle, $ldManagerTitles, true)
            || Common::isLDDepartment($employee->Dept_id ?? null);
    }

    /**
     * L&D Manager dashboard (mobile). "New arrivals" = employees who have an
     * onboarding itinerary (employee_itineraries row) — the same cohort the
     * HR onboarding dashboard tracks. "Onboarding trainings ... for new
     * arrivals" = training_schedules with at least one participant from that
     * cohort (there is no separate "onboarding training" flag on
     * learning_programs/training_schedules, so schedule-to-arrival overlap
     * is the closest real signal in the existing schema).
     */
    public function ldManagerDashboard()
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $employee = $this->user->GetEmployee ?? null;
        if (!$this->isLdManager($employee)) {
            return response()->json(['success' => false, 'message' => 'Forbidden: L&D Manager access only'], 403);
        }

        try {
            $resort_id = $this->resort_id;
            $today = Carbon::today()->format('Y-m-d');

            $itineraries = EmployeeItineraries::with(['employee.resortAdmin', 'pickupemployee.resortAdmin', 'accompanymedicalemployee.resortAdmin'])
                ->where('resort_id', $resort_id)
                ->get();

            $upcomingItineraries = $itineraries->filter(fn($i) => $i->arrival_date >= $today)->values();
            $arrivalEmployeeIds = $itineraries->pluck('employee_id')->filter()->unique()->values()->all();

            $onboardingSchedulesQuery = TrainingSchedule::where('resort_id', $resort_id)
                ->whereHas('participants', fn($q) => $q->whereIn('employee_id', $arrivalEmployeeIds));

            $onboardingTrainingsScheduled = empty($arrivalEmployeeIds) ? 0 : (clone $onboardingSchedulesQuery)->count();
            $pendingOnboardingTrainings   = empty($arrivalEmployeeIds) ? 0 : (clone $onboardingSchedulesQuery)->where('start_date', '>', $today)->count();

            // Which arrivals already have at least one training scheduled —
            // drives the "Status: Training Scheduled" label on the list below.
            $scheduledParticipantEmpIds = empty($arrivalEmployeeIds) ? [] : TrainingParticipant::whereIn('employee_id', $arrivalEmployeeIds)
                ->whereHas('schedule', fn($q) => $q->where('resort_id', $resort_id))
                ->pluck('employee_id')->unique()->all();

            $upcomingArrivals = $upcomingItineraries->map(function ($itinerary) use ($scheduledParticipantEmpIds) {
                $employee = $itinerary->employee;
                $pickup = $itinerary->pickupemployee;
                $medical = $itinerary->accompanymedicalemployee;

                return [
                    'itinerary_id'    => $itinerary->id,
                    'employee_id'     => $employee->id ?? null,
                    'employee_name'   => ($employee && $employee->resortAdmin) ? $employee->resortAdmin->full_name : 'Unknown',
                    'employee_photo'  => Common::getResortUserPicture($employee->Admin_Parent_id ?? null),
                    'arrival_date'    => $itinerary->arrival_date,
                    'arrival_time'    => $itinerary->arrival_time,
                    'representatives' => array_values(array_filter([
                        $pickup ? ['role' => 'Pickup', 'name' => optional($pickup->resortAdmin)->full_name ?? 'Unknown', 'contact' => optional($pickup->resortAdmin)->personal_phone] : null,
                        $medical ? ['role' => 'Medical Escort', 'name' => optional($medical->resortAdmin)->full_name ?? 'Unknown', 'contact' => optional($medical->resortAdmin)->personal_phone] : null,
                    ])),
                    'flight_ticket_available' => !empty($itinerary->flight_ticket_file),
                    'status'          => 'Itinerary Created',
                    'training_status' => in_array($employee->id ?? null, $scheduledParticipantEmpIds, true) ? 'Training Scheduled' : 'Not Scheduled',
                ];
            })->values();

            return response()->json([
                'success' => true,
                'message' => 'L&D Manager dashboard fetched successfully.',
                'stats' => [
                    'total_upcoming_arrivals'                      => $upcomingItineraries->count(),
                    'average_time_days'                            => Common::averageOnboardingLeadDays($resort_id),
                    'onboarding_trainings_scheduled_count'         => $onboardingTrainingsScheduled,
                    'pending_onboarding_trainings_scheduled_count' => $pendingOnboardingTrainings,
                    'total_itineraries_created_count'              => $itineraries->count(),
                ],
                'upcoming_arrivals' => $upcomingArrivals,
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * L&D Manager Training Calendar (mobile). Month-grid by default; `view`
     * = day|weekly|monthly picks the window around `date` (defaults to
     * today). `category_id` filters to one learning_categories row ("All
     * Category" = omit the param). Also returns a per-arrival "upcoming
     * training" summary card for the L&D Manager's new-arrival cohort.
     */
    public function ldManagerTrainingCalendar(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $employee = $this->user->GetEmployee ?? null;
        if (!$this->isLdManager($employee)) {
            return response()->json(['success' => false, 'message' => 'Forbidden: L&D Manager access only'], 403);
        }

        try {
            $resort_id = $this->resort_id;
            $today = Carbon::today()->format('Y-m-d');
            $view = in_array($request->query('view'), ['day', 'weekly', 'monthly'], true) ? $request->query('view') : 'monthly';
            $refDate = $request->query('date') ? Carbon::parse($request->query('date')) : Carbon::now();
            $categoryId = $request->query('category_id');

            if ($view === 'day') {
                $startDate = $refDate->copy()->startOfDay()->format('Y-m-d');
                $endDate   = $refDate->copy()->endOfDay()->format('Y-m-d');
            } elseif ($view === 'weekly') {
                $startDate = $refDate->copy()->startOfWeek()->format('Y-m-d');
                $endDate   = $refDate->copy()->endOfWeek()->format('Y-m-d');
            } else {
                $startDate = $refDate->copy()->startOfMonth()->format('Y-m-d');
                $endDate   = $refDate->copy()->endOfMonth()->format('Y-m-d');
            }

            $sessions = TrainingSchedule::where('resort_id', $resort_id)
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate])
                        ->orWhere(function ($sub) use ($startDate, $endDate) {
                            $sub->where('start_date', '<=', $startDate)->where('end_date', '>=', $endDate);
                        });
                })
                ->when($categoryId, function ($q) use ($categoryId) {
                    $q->whereHas('learningProgram', fn($lp) => $lp->where('learning_category_id', $categoryId));
                })
                ->with(['learningProgram.category', 'participants'])
                ->get();

            $events = $sessions->map(function ($session) {
                return [
                    'training_schedule_id' => $session->id,
                    'training_id'          => $session->learningProgram->id ?? null,
                    'title'                => $session->learningProgram->name ?? null,
                    'category'             => optional(optional($session->learningProgram)->category)->category,
                    'session_date'         => $session->start_date,
                    'end_date'             => $session->end_date,
                    'start_time'           => $session->start_time ? date('h:i A', strtotime($session->start_time)) : null,
                    'end_time'             => $session->end_time ? date('h:i A', strtotime($session->end_time)) : null,
                    'participants_count'   => $session->participants->count(),
                ];
            })->values();

            $categoriesList = LearningCategory::where('resort_id', $resort_id)
                ->get(['id', 'category'])
                ->map(fn($c) => ['id' => $c->id, 'category' => $c->category])
                ->values()->all();
            array_unshift($categoriesList, ['id' => null, 'category' => 'All Category']);

            // Per-arrival upcoming-training card.
            $arrivalEmployeeIds = EmployeeItineraries::where('resort_id', $resort_id)
                ->pluck('employee_id')->filter()->unique()->values()->all();

            $employeeCards = collect();
            if (!empty($arrivalEmployeeIds)) {
                $employeeCards = Employee::with('resortAdmin')
                    ->where('resort_id', $resort_id)
                    ->whereIn('id', $arrivalEmployeeIds)
                    ->get()
                    ->map(function ($emp) use ($resort_id, $today) {
                        $participantSchedules = TrainingSchedule::where('resort_id', $resort_id)
                            ->whereHas('participants', fn($q) => $q->where('employee_id', $emp->id))
                            ->with('learningProgram')
                            ->get();

                        $upcoming = $participantSchedules->filter(fn($s) => $s->start_date >= $today)->sortBy('start_date')->first();

                        return [
                            'employee_id'    => $emp->id,
                            'employee_name'  => optional($emp->resortAdmin)->full_name ?? 'Unknown',
                            'employee_photo' => Common::getResortUserPicture($emp->Admin_Parent_id ?? null),
                            'upcoming_training' => $upcoming ? [
                                'title' => optional($upcoming->learningProgram)->name,
                                'date'  => $upcoming->start_date,
                                'time'  => $upcoming->start_time ? date('h:i A', strtotime($upcoming->start_time)) : null,
                            ] : null,
                            'on_boarding_trainings_count' => $participantSchedules->count(),
                            'completed_count' => $participantSchedules->filter(fn($s) => $s->end_date < $today)->count(),
                            'pending_count'   => $participantSchedules->filter(fn($s) => $s->start_date >= $today)->count(),
                        ];
                    })->values();
            }

            return response()->json([
                'success' => true,
                'message' => 'Training calendar fetched successfully.',
                'view' => $view,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'categories' => $categoriesList,
                'events' => $events,
                'employee_cards' => $employeeCards,
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * L&D Manager Mark Attendance — Step 1: "Select Training" dropdown.
     * Deliberately NOT filtering on the `status` column like the legacy
     * trainingList() does: every training_schedules row in this app is
     * still 'Scheduled' regardless of its actual dates — nothing ever
     * advances it (same finding already documented in
     * employeeLearningDashbaord() above). Mirroring that fix here: "ongoing"
     * is derived from start_date/end_date vs today, not the status column.
     */
    public function ldManagerMarkAttendanceTrainings()
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $employee = $this->user->GetEmployee ?? null;
        if (!$this->isLdManager($employee)) {
            return response()->json(['success' => false, 'message' => 'Forbidden: L&D Manager access only'], 403);
        }

        try {
            $today = Carbon::today()->format('Y-m-d');
            $trainings = TrainingSchedule::with('learningProgram')
                ->where('resort_id', $this->resort_id)
                ->where('start_date', '<=', $today)
                ->where('end_date', '>=', $today)
                ->get()
                ->map(fn($t) => [
                    'training_schedule_id' => $t->id,
                    'name'                 => optional($t->learningProgram)->name,
                    'start_date'           => $t->start_date,
                    'end_date'             => $t->end_date,
                ])->values();

            return response()->json([
                'success' => true,
                'message' => 'Trainings fetched successfully.',
                'trainings' => $trainings,
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * L&D Manager Mark Attendance — Step 2: the employee checklist for a
     * chosen training. Each row's checkbox is pre-checked ("is_present")
     * only when a TrainingAttendance row already exists for TODAY with
     * status Present — giving the Figma's "some already checked" mixed
     * default for free, rather than assuming everyone defaults to Present.
     */
    public function ldManagerMarkAttendanceParticipants($training_schedule_id)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $employee = $this->user->GetEmployee ?? null;
        if (!$this->isLdManager($employee)) {
            return response()->json(['success' => false, 'message' => 'Forbidden: L&D Manager access only'], 403);
        }

        try {
            $resort_id = $this->resort_id;

            $schedule = TrainingSchedule::with('learningProgram')
                ->where('resort_id', $resort_id)
                ->find($training_schedule_id);

            if (!$schedule) {
                return response()->json(['success' => false, 'message' => 'Training schedule not found'], 404);
            }

            $today = Carbon::today()->format('Y-m-d');
            $todaysAttendance = TrainingAttendance::where('training_schedule_id', $schedule->id)
                ->where('attendance_date', $today)
                ->get()
                ->keyBy('employee_id');

            $participants = TrainingParticipant::with(['employee.resortAdmin', 'employee.position', 'employee.department'])
                ->where('training_schedule_id', $schedule->id)
                ->get()
                ->filter(fn($p) => $p->employee)
                ->map(function ($p) use ($todaysAttendance) {
                    $emp = $p->employee;
                    $existing = $todaysAttendance->get($emp->id);
                    return [
                        'employee_id' => $emp->id,
                        'name'        => optional($emp->resortAdmin)->full_name ?? 'Unknown',
                        'photo'       => Common::getResortUserPicture($emp->Admin_Parent_id ?? null),
                        'department'  => optional($emp->department)->name,
                        'position'    => optional($emp->position)->position_title,
                        'is_present'  => $existing ? ($existing->status === 'Present') : false,
                    ];
                })->values();

            return response()->json([
                'success' => true,
                'message' => 'Participants fetched successfully.',
                'training' => [
                    'training_schedule_id' => $schedule->id,
                    'name'       => optional($schedule->learningProgram)->name,
                    'start_date' => $schedule->start_date,
                    'end_date'   => $schedule->end_date,
                ],
                'participants' => $participants,
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * L&D Manager Mark Attendance — Step 3: submit. Unlike the legacy
     * markAttendance(), every id is resort_id-scoped in the validator itself
     * (Rule::exists()->where('resort_id', ...)) — no cross-tenant gap.
     */
    public function ldManagerMarkAttendanceStore(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $employee = $this->user->GetEmployee ?? null;
        if (!$this->isLdManager($employee)) {
            return response()->json(['success' => false, 'message' => 'Forbidden: L&D Manager access only'], 403);
        }

        $resort_id = $this->resort_id;

        $validator = Validator::make($request->all(), [
            'training_schedule_id'     => ['required', Rule::exists('training_schedules', 'id')->where('resort_id', $resort_id)],
            'employees'                => 'required|array|min:1',
            'employees.*.employee_id'  => ['required', Rule::exists('employees', 'id')->where('resort_id', $resort_id)],
            'employees.*.status'       => 'required|in:Present,Absent',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $today = Carbon::today()->format('Y-m-d');
            $absentEmployeeIds = [];
            foreach ($request->employees as $row) {
                TrainingAttendance::updateOrCreate(
                    [
                        'training_schedule_id' => $request->training_schedule_id,
                        'employee_id'          => $row['employee_id'],
                        'attendance_date'      => $today,
                    ],
                    [
                        'status' => $row['status'],
                    ]
                );
                if ($row['status'] === 'Absent') {
                    $absentEmployeeIds[] = $row['employee_id'];
                }
            }

            $this->notifyTrainingAbsence($resort_id, $request->training_schedule_id, $absentEmployeeIds);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Attendance marked successfully.',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * L&D Manager's incoming Training Request list — read-only. Gated by the
     * 'ld.manager' route middleware (L&D position/department, not rank), so
     * only the true L&D Manager reaches this method; HOD/EXCOM/HR/GM 403 at
     * the middleware before this code runs.
     *
     * Mirrors Resorts\Learning\LearningController::list()'s data shape for the
     * L&D-manager branch, but unlike the web screen this is view-only: no
     * approve/reject/schedule action exists here (that stays web-only per spec),
     * so requests are scoped to Pending only and no action metadata is returned.
     */
    public function managerRequestList(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $requests = LearningRequest::where('learning_requests.resort_id', $this->resort_id)
                ->where('learning_requests.status', 'Pending')
                ->with(['learning', 'employees.employee.resortAdmin'])
                ->orderByDesc('learning_requests.id')
                ->get();

            $data = $requests->map(function ($req) {
                $creator = ResortAdmin::find($req->created_by);

                $suggestedEmployees = $req->employees->map(function ($lre) {
                    $employee = $lre->employee;
                    if (!$employee) return null;
                    $name = $employee->resortAdmin
                        ? trim($employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name)
                        : trim($employee->first_name . ' ' . $employee->last_name);
                    return [
                        'employee_id' => $employee->id,
                        'name'        => $name,
                    ];
                })->filter()->values();

                $attachments = LearningMaterials::where('learning_program_id', $req->learning_id)
                    ->get()
                    ->map(function ($material) {
                        return [
                            'id'  => $material->id,
                            'url' => StorageHelper::url($material->file_path),
                        ];
                    })->values();

                return [
                    'id'                  => $req->id,
                    'learning_name'       => $req->learning->name ?? 'N/A',
                    'suggested_employees' => $suggestedEmployees,
                    'requested_by'        => $creator ? trim($creator->first_name . ' ' . $creator->last_name) : 'N/A',
                    'reason'              => $req->reason,
                    'start_date'          => $req->start_date,
                    'end_date'            => $req->end_date,
                    // Read-only screen — approve/reject/schedule stays web-only.
                    'action_note'         => 'To approve, reject, or schedule this training, please use the web portal.',
                    'attachments'         => $attachments,
                ];
            })->values();

            return response()->json([
                'success'              => true,
                'message'              => 'Learning request list fetched successfully',
                'learning_request_list' => $data,
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }
}
