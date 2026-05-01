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
        if(!$this->resort) return;
        if($this->resort->is_master_admin == 0){
            $this->reporting_to = $this->resort->GetEmployee->id;
            $this->underEmp_id = Common::getSubordinates($this->reporting_to);
        }
    }

    public function index() {
        // The schedule list is read-only and the underlying query is already
        // department-scoped via getPerformanceScopedEmpIds(). Permission gate is
        // softened so HOD / XCOM / MGR / SUP can drill in from their dashboard tiles
        // without hitting a 403. Create / edit actions still require full permission.
        $page_title = "Learning Schedule";
        $trainings = TrainingSchedule::with('participants')->get();
        return view('resorts.learning.schedule.list', compact('trainings', 'page_title'));
    }

    public function schedule() {
        // Only HR / GM / L&D Manager (or super-admin / master-admin) — matches the
        // button-gate on the calendar page. Plus the existing route-permission check.
        if (!Common::hasFullDataAccess()) {
            return abort(403, 'Only HR, GM and L&D Managers can create a learning schedule.');
        }
        if(Common::checkRouteWisePermission('learning.schedule',config('settings.resort_permissions.create')) == false){
            return abort(403, 'Unauthorized access');
        }
        $page_title = "Learning Schedule";
        $scopedDeptIds = Common::getScopedDepartmentIds();
        $employees = Employee::with(['resortAdmin','department','position'])
            ->where('resort_id',$this->resort->resort_id)
            ->whereIn('status', ['Active', 'Probationary'])
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))
            ->get();
        $programs = LearningProgram::where('resort_id',$this->resort->resort_id)->get();
        $departments = ResortDepartment::where('resort_id',$this->resort->resort_id)->where('status','active')
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('id', $scopedDeptIds))
            ->get();
        return view('resorts.learning.schedule.index',compact('page_title','employees','programs','departments'));
    }

    public function list(Request $request)
    {
        try {
            $resort_id = $this->resort->resort_id;
            // Department-visibility scope — HR (and HR-dept HOD/EXCOM) only see schedules
            // with at least one in-scope participant. GM / L&D Manager / admin = null = all.
            $scopedEmpIds = Common::getPerformanceScopedEmpIds();

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
            // Participants JOIN — also filtered by the user's scope, so the attendees
            // column never reveals names from other departments. Schedules where the
            // user has no in-scope participants are excluded by the whereExists below.
            ->leftJoin('training_participants', function ($join) use ($scopedEmpIds) {
                $join->on('training_schedules.id', '=', 'training_participants.training_schedule_id');
                if (is_array($scopedEmpIds)) {
                    $join->whereIn('training_participants.employee_id', $scopedEmpIds);
                }
            })
            ->leftJoin('employees', 'training_participants.employee_id', '=', 'employees.id')
            ->leftJoin('resort_admins', 'resort_admins.id', '=', 'employees.Admin_Parent_id') // Fetch attendees' names from resort_admins
            ->where('training_schedules.resort_id', $resort_id)
            ->when(is_array($scopedEmpIds), function ($q) use ($scopedEmpIds) {
                // Surface only schedules where one of the user's in-scope employees participates.
                $q->whereExists(function ($sub) use ($scopedEmpIds) {
                    $sub->selectRaw(1)
                        ->from('training_participants as tp_scope')
                        ->whereColumn('tp_scope.training_schedule_id', 'training_schedules.id')
                        ->whereIn('tp_scope.employee_id', $scopedEmpIds);
                });
            });

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
            // Filter by status when a dashboard tile passed one. Pure date-derived
            // (raw `status` column is unreliable — it defaults to 'Scheduled' for
            // every row, so using it would make every tile click return the same
            // entries). Matches the dashboard tile counts exactly.
            if ($request->filled('status')) {
                $today = \Carbon\Carbon::now()->toDateString();
                $statusParam = $request->input('status');
                if ($statusParam === 'Ongoing') {
                    $query->where('training_schedules.start_date', '<=', $today)
                          ->where('training_schedules.end_date', '>=', $today);
                } elseif ($statusParam === 'Completed') {
                    $query->where('training_schedules.end_date', '<', $today);
                } elseif ($statusParam === 'Scheduled' || $statusParam === 'Pending') {
                    $query->where('training_schedules.start_date', '>', $today);
                } else {
                    $query->where('training_schedules.status', $statusParam);
                }
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
            ->addColumn('status', function ($row) {
                // Date-derived status — same rule as the dashboard tile counts and
                // the training-history page so all three views agree.
                $today = \Carbon\Carbon::now()->toDateString();
                if ($row->status === 'Completed' || $row->end_date < $today) {
                    $effective = 'Completed';
                } elseif ($row->status === 'Ongoing' || ($row->start_date <= $today && $row->end_date >= $today)) {
                    $effective = 'Ongoing';
                } elseif ($row->start_date > $today) {
                    $effective = 'Scheduled';
                } else {
                    $effective = $row->status ?: 'Scheduled';
                }
                $badge = ['Completed' => 'success', 'Ongoing' => 'info', 'Scheduled' => 'warning', 'Pending' => 'secondary'][$effective] ?? 'secondary';
                return '<span class="badge badge-' . $badge . '">' . $effective . '</span>';
            })
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
            ->rawColumns(['trainer', 'attendees', 'action', 'status'])
            ->make(true);
        
        } catch (\Exception $e) {
            \Log::error("Error fetching Learning Programs: " . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch data'], 500);
        }
    }

    public function store(Request $request) {
        // Server-side gate to mirror the schedule() page check — block anyone but
        // HR / GM / L&D Manager from creating a schedule even via direct POST.
        if (!Common::hasFullDataAccess()) {
            return response()->json(['success' => false, 'message' => 'Only HR, GM and L&D Managers can create a learning schedule.'], 403);
        }

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
    
    /**
     * Returns the metadata for one learning program so the schedule form can
     * pre-fill description / trainer / suggested hours+days when the user
     * picks a title from the dropdown.
     */
    public function getProgramDetail($id)
    {
        $resort_id = $this->resort->resort_id;

        $program = LearningProgram::with('category')
            ->where('resort_id', $resort_id)
            ->find($id);

        if (!$program) {
            return response()->json(['success' => false, 'message' => 'Program not found.'], 404);
        }

        $trainerName = null;
        $trainerPosition = null;
        $trainerDepartment = null;
        if ($program->trainer) {
            $trainerEmp = Employee::with(['resortAdmin', 'position', 'department'])->find($program->trainer);
            if ($trainerEmp) {
                $trainerName = $trainerEmp->resortAdmin
                    ? trim($trainerEmp->resortAdmin->first_name . ' ' . $trainerEmp->resortAdmin->last_name)
                    : null;
                $trainerPosition = optional($trainerEmp->position)->position_title;
                $trainerDepartment = optional($trainerEmp->department)->name;
            }
        }

        // target_audience is cast to array on the model, but tolerate raw JSON
        // strings too in case older rows were saved differently.
        $rawAudience = $program->target_audience;
        $targetAudienceIds = [];
        if (is_array($rawAudience)) {
            $targetAudienceIds = $rawAudience;
        } elseif (is_string($rawAudience) && $rawAudience !== '') {
            $decoded = json_decode($rawAudience, true);
            if (is_array($decoded)) $targetAudienceIds = $decoded;
        }
        // Coerce to ints so whereIn matches numeric PKs cleanly.
        $targetAudienceIds = array_values(array_filter(array_map('intval', $targetAudienceIds)));

        $audienceLabels = [];
        if (!empty($targetAudienceIds)) {
            switch ($program->audience_type) {
                case 'departments':
                    $audienceLabels = ResortDepartment::whereIn('id', $targetAudienceIds)
                        ->pluck('name')->all();
                    break;
                case 'grades':
                    $audienceLabels = ResortPosition::whereIn('id', $targetAudienceIds)
                        ->pluck('position_title')->all();
                    break;
                case 'employees':
                    $audienceLabels = Employee::with('resortAdmin')
                        ->whereIn('id', $targetAudienceIds)
                        ->get()
                        ->map(fn($e) => optional($e->resortAdmin)->full_name
                            ?? trim((optional($e->resortAdmin)->first_name ?? '') . ' ' . (optional($e->resortAdmin)->last_name ?? '')))
                        ->filter()
                        ->values()
                        ->all();
                    break;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id'                 => $program->id,
                'name'               => $program->name,
                'description'        => $program->description,
                'objectives'         => $program->objectives,
                'category'           => optional($program->category)->category,
                'hours'              => $program->hours,
                'days'               => $program->days,
                'frequency'          => $program->frequency,
                'delivery_mode'      => $program->delivery_mode,
                'trainer_name'       => $trainerName,
                'trainer_position'   => $trainerPosition,
                'trainer_department' => $trainerDepartment,
                'audience_type'      => $program->audience_type,
                'audience_ids'       => $targetAudienceIds,
                'audience_labels'    => $audienceLabels,
            ],
        ]);
    }

    public function getEmployeesDeptwise(Request $request)
    {
        // deptID is optional — empty / "all" means "every department the user can see".
        $request->validate([
            'deptID' => 'nullable|exists:resort_departments,id'
        ]);

        $scopedDeptIds = Common::getScopedDepartmentIds();
        $deptID = $request->filled('deptID') ? (int) $request->deptID : null;

        if ($deptID !== null && is_array($scopedDeptIds) && !in_array($deptID, $scopedDeptIds)) {
            return response()->json(['success' => false, 'message' => 'You do not have access to this department.'], 403);
        }

        $query = Employee::where('resort_id', $this->resort->resort_id)
            ->with(['resortAdmin', 'position']);

        if ($deptID !== null) {
            $query->where('Dept_id', $deptID);
        } elseif (is_array($scopedDeptIds)) {
            // No dept selected → still respect the user's department visibility scope.
            $query->whereIn('Dept_id', $scopedDeptIds);
        }

        $employees = $query->get()->map(function ($employee) {
            return [
                'id' => $employee->id,
                'full_name' => optional($employee->resortAdmin)->full_name,
                'position_title' => optional($employee->position)->position_title,
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
            // Validate the request data — accept HH:mm or HH:mm:ss for the times.
            $validated = $request->validate([
                'id' => 'required|integer|exists:training_schedules,id',
                'start_date' => 'sometimes|nullable|date_format:d/m/Y',
                'end_date' => 'sometimes|nullable|date_format:d/m/Y',
                'start_time' => 'sometimes|nullable|regex:/^\d{2}:\d{2}(:\d{2})?$/',
                'end_time'   => 'sometimes|nullable|regex:/^\d{2}:\d{2}(:\d{2})?$/',
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

            // Times — normalize to HH:mm:ss for the DB.
            $normalizeTime = fn($t) => strlen($t) === 5 ? $t . ':00' : $t;
            if ($request->filled('start_time')) {
                $schedule->start_time = $normalizeTime($request->start_time);
            }
            if ($request->filled('end_time')) {
                $schedule->end_time = $normalizeTime($request->end_time);
            }

            // Validate that the end date is after or equal to the start date
            if ($schedule->start_date && $schedule->end_date && strtotime($schedule->end_date) < strtotime($schedule->start_date)) {
                return response()->json([
                    'success' => false,
                    'message' => 'End date must be after or equal to start date'
                ], 422);
            }

            // If start and end fall on the same day, end_time must be after start_time.
            if ($schedule->start_date && $schedule->end_date
                && $schedule->start_date === $schedule->end_date
                && $schedule->start_time && $schedule->end_time
                && strtotime($schedule->end_time) <= strtotime($schedule->start_time)) {
                return response()->json([
                    'success' => false,
                    'message' => 'End time must be after start time on the same day.'
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
        // Read-only list; data is scoped via getPerformanceScopedEmpIds() so the
        // permission gate isn't necessary to keep cross-dept data hidden.
        $page_title = 'Training History';
        $resortId = $this->resort->resort_id;

        // HR (and HR-dept HOD/EXCOM) is now department-scoped — restrict to schedules
        // that have at least one in-scope participant. GM / L&D Manager / admin keep
        // resort-wide visibility (helper returns null for them).
        $scopedEmpIds = Common::getPerformanceScopedEmpIds();

        $query = TrainingSchedule::with(['learningProgram', 'trainingAttendances', 'participants'])
            ->where('resort_id', $resortId)
            ->when(is_array($scopedEmpIds), fn($q) => $q->whereHas('participants', fn($sq) => $sq->whereIn('employee_id', $scopedEmpIds)))
            ->orderBy('start_date', 'desc');

        // Apply date filter
        if ($request->has('date') && $request->date !== '') {
            $date = \Carbon\Carbon::parse($request->date);
            $query->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date);
        }

        // Status filter — pure date-derived (raw `status` column is unreliable since
        // every row defaults to 'Scheduled'). Matches the dashboard tile counts.
        if ($request->filled('status')) {
            $today = \Carbon\Carbon::now()->toDateString();
            $statusParam = $request->input('status');
            if ($statusParam === 'Ongoing') {
                $query->where('start_date', '<=', $today)->where('end_date', '>=', $today);
            } elseif ($statusParam === 'Completed') {
                $query->where('end_date', '<', $today);
            } elseif ($statusParam === 'Scheduled' || $statusParam === 'Pending') {
                $query->where('start_date', '>', $today);
            } else {
                $query->where('status', $statusParam);
            }
        }

        $trainings = $query->get();

        if ($request->ajax()) {
            return datatables()->of($trainings)
                ->addColumn('title', fn($row) => $row->learningProgram->name ?? 'N/A')
                ->addColumn('dates', fn($row) => date('d M Y', strtotime($row->start_date)) . ' - ' . date('d M Y', strtotime($row->end_date)))
                ->addColumn('time', fn($row) => date('h:i A', strtotime($row->start_time)) . ' - ' . date('h:i A', strtotime($row->end_time)))
                ->addColumn('venue', fn($row) => $row->venue ?? 'N/A')
                ->addColumn('status', function ($row) {
                    // Derive effective status from dates so this column agrees with
                    // the dashboard tile counts (which also derive from dates).
                    $today = \Carbon\Carbon::now()->toDateString();
                    if ($row->status === 'Completed' || $row->end_date < $today) {
                        $effective = 'Completed';
                    } elseif ($row->status === 'Ongoing' || ($row->start_date <= $today && $row->end_date >= $today)) {
                        $effective = 'Ongoing';
                    } elseif ($row->start_date > $today) {
                        $effective = 'Scheduled';
                    } else {
                        $effective = $row->status ?: 'Scheduled';
                    }
                    $badge = ['Completed' => 'success', 'Ongoing' => 'info', 'Scheduled' => 'warning', 'Pending' => 'secondary'][$effective] ?? 'secondary';
                    return '<span class="badge badge-' . $badge . '">' . $effective . '</span>';
                })
                ->addColumn('participants', fn($row) => $row->participants->count())
                ->addColumn('attendance', function ($row) {
                    $totalDays = \Carbon\Carbon::parse($row->start_date)->diffInDays(\Carbon\Carbon::parse($row->end_date)) + 1;
                    $totalExpected = $totalDays * $row->participants->count();
                    $actualPresent = $row->trainingAttendances->where('status', 'Present')->count();
                    return $totalExpected > 0 ? round(($actualPresent / $totalExpected) * 100, 2) . '%' : '0%';
                })
                ->addColumn('action', function ($row) {
                    return '<button type="button" class="btn btn-themeBlue btn-sm" onclick="viewTrainingDetail(' . $row->id . ')">View</button>';
                })
                ->rawColumns(['title', 'dates', 'time', 'venue', 'status', 'participants', 'attendance', 'action'])
                ->make(true);
        }

        return view('resorts.learning.schedule.history',compact('trainings','page_title'));
    }

    /**
     * Returns the full detail of a single training schedule for the View modal
     * on the Training History page (program info, dates, trainer, venue,
     * status, participants list and per-participant attendance %).
     */
    public function historyDetail($id)
    {
        $resort_id = $this->resort->resort_id;

        $training = TrainingSchedule::with([
            'learningProgram.category',
            'participants.employee.resortAdmin',
            'trainingAttendances',
        ])->where('resort_id', $resort_id)->find($id);

        if (!$training) {
            return response()->json(['success' => false, 'message' => 'Training not found.'], 404);
        }

        $totalDays = Carbon::parse($training->start_date)->diffInDays(Carbon::parse($training->end_date)) + 1;
        $participantsCount = $training->participants->count();
        $totalExpected = $totalDays * $participantsCount;
        $actualPresent = $training->trainingAttendances->where('status', 'Present')->count();
        $attendancePercent = $totalExpected > 0 ? round(($actualPresent / $totalExpected) * 100, 2) : 0;

        $trainerEmp = null;
        $trainerId = optional($training->learningProgram)->trainer;
        if ($trainerId) {
            $trainerEmp = Employee::with('resortAdmin')->find($trainerId);
        }

        $participants = $training->participants->map(function ($p) use ($training) {
            $present = $training->trainingAttendances
                ->where('employee_id', $p->employee_id)
                ->where('status', 'Present')
                ->count();
            $total = $training->trainingAttendances
                ->where('employee_id', $p->employee_id)
                ->count();
            $name = optional(optional($p->employee)->resortAdmin)->full_name
                ?? trim((optional(optional($p->employee)->resortAdmin)->first_name ?? '') . ' ' . (optional(optional($p->employee)->resortAdmin)->last_name ?? ''));
            return [
                'name'             => $name ?: 'Unknown',
                'attended'         => $present,
                'total_marked'     => $total,
                'percentage'       => $total > 0 ? round(($present / $total) * 100, 2) : 0,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'title'              => optional($training->learningProgram)->name,
                'description'        => optional($training->learningProgram)->description,
                'category'           => optional(optional($training->learningProgram)->category)->category,
                'start_date'         => date('d M Y', strtotime($training->start_date)),
                'end_date'           => date('d M Y', strtotime($training->end_date)),
                'start_time'         => date('h:i A', strtotime($training->start_time)),
                'end_time'           => date('h:i A', strtotime($training->end_time)),
                'venue'              => $training->venue ?? 'N/A',
                'status'             => $training->status,
                'trainer'            => $trainerEmp && $trainerEmp->resortAdmin
                    ? trim($trainerEmp->resortAdmin->first_name . ' ' . $trainerEmp->resortAdmin->last_name)
                    : 'N/A',
                'participants_count' => $participantsCount,
                'attendance_percent' => $attendancePercent,
                'participants'       => $participants,
            ],
        ]);
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