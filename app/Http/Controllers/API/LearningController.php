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
use App\Helpers\Common;
use Carbon\Carbon;
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
            $sessions                               =   TrainingSchedule::with(['learningProgram', 'participants.employee.resortAdmin','learningProgram.category'])
                                                            ->where('training_schedules.resort_id', $resort_id)
                                                            ->where('training_schedules.training_id', $scheduleId)
                                                            ->first();
            $trainerData                            =   Employee::with('resortAdmin')->where('id',$sessions->learningProgram->trainer)
                                                            ->first();

            if ($trainerData) {
                $trainerData->profile = Common::getResortUserPicture($trainerData->Admin_Parent_id);
            }

            //Check if session exists
            if (!$sessions) {
                return response()->json(['success' => false, 'message' => 'Training session not found'], 200);
            }

            $data = [
                'training_id'                       =>  $sessions->learningProgram->id,
                'training_name'                     =>  $sessions->learningProgram->name,
                'training_start_date'               =>  $sessions->start_date,
                'training_end_date'                 =>  $sessions->end_date,
                'training_start_time'               =>  $sessions->start_time,
                'training_end_time'                 =>  $sessions->end_time,
                'category'                          =>  $sessions->learningProgram->category->category ?? 'N/A',
                'description'                       =>  $sessions->learningProgram->description ?? '',
                'trainer_first_name'                =>  $trainerData->resortAdmin->first_name,
                'trainer_last_name'                 =>  $trainerData->resortAdmin->last_name,
                'trainer_profile'                   =>  $trainerData->profile,
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
            }

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
            $startDate                              =   Carbon::now()->startOfMonth()->format('Y-m-d');
            $endDate                                =   Carbon::now()->endOfMonth()->format('Y-m-d');
            $employeeId                             =   $this->user->GetEmployee->id;
            $pendingCount                           =     TrainingSchedule::where('resort_id', $this->resort_id)
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
                                                                ->where('status', '!=', 'Completed') // or use 'Pending' if you have that
                                                                ->count();


            $completedHours                         =     TrainingSchedule::where('resort_id', $this->resort_id)
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
                                                                    ->whereHas('trainingAttendances', function ($query) use ($employeeId) {
                                                                        $query->where('status', 'Present')
                                                                                ->where('employee_id', $employeeId);
                                                                    })
                                                                    ->with([
                                                                        'learningProgram',
                                                                        ])
                                                                    ->where('status', '=', 'Completed') // or use 'Pending' if you have that
                                                                   ->get()->reduce(function ($carry, $session) {
                                                                    $start = \Carbon\Carbon::parse($session->start_time);
                                                                    $end = \Carbon\Carbon::parse($session->end_time);
                                                                    $hours = $end->diffInMinutes($start) / 60;
                                                                    return $carry + $hours;
                                                                }, 0);
                                                                    
                                    
            $dashboardArr['training_completed_hours']   =   $completedHours; 
            $dashboardArr['pending_training_count']     =   $pendingCount; 

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

            $assignedCount                          =   TrainingSchedule::where('resort_id', $this->resort_id)
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
                                                            ->count();
                                                        
             $completedCount                         =   TrainingSchedule::where('resort_id', $this->resort_id)
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
                                                            ->where('status', 'Completed')
                                                            ->count();

            $completedPercentage = $assignedCount > 0 ? round(($completedCount / $assignedCount) * 100, 2) : 0;
            $events                                 =   [];
            // Process Training Schedules
            foreach ($sessions as $session) {

                $sessionData =  [
                    'id'                            =>  $session['learningProgram']['id'],
                    'title'                         =>  $session->learningProgram->name,
                    'session_date'                  =>  $session->start_date,
                    'start_time'                    =>  date('h:i A', strtotime($session->start_time)),
                    'end_time'                      =>  date('h:i A', strtotime($session->end_time)),
                    'description'                   =>  $session->learningProgram->description,
                    'status'                        =>  $session->status,
                ];

                $events[]                           =   $sessionData;
            }
            
            $learningRequests                       =   LearningRequest::join("learning_requests_employees as lre", "learning_requests.id", "=", 'lre.learning_request_id')
                                                            ->whereBetween('learning_requests.start_date', [$startDate, $endDate])
                                                            ->where('lre.employee_id', $employeeId)
                                                            ->where('learning_requests.resort_id', $this->resort_id)
                                                            ->get();

            // Process Learning Requests
            foreach ($learningRequests as $request) {
                $requestData                        =   [
                    'id'                            =>  $request->learning->id,
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
}
