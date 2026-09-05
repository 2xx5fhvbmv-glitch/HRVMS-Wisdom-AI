<?php
namespace App\Http\Controllers\Resorts\Learning;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\ResortAdmin;
use App\Models\ResortPosition;
use App\Models\LearningProgram;
use Illuminate\Validation\Rule;
use App\Models\LearningCategory;
use App\Models\ResortDepartment;
use App\Models\LearningMaterials;
use App\Models\TrainingSchedule;
use App\Models\TrainingParticipant;
use App\Models\LearningRequest;
use App\Models\LearningRequestEmployee;
use App\Events\ResortNotificationEvent;
use Illuminate\Support\Facades\Validator;
use DB;
use Auth;
use Common;
use DateTime;
use Carbon\Carbon;

class LearningCalendarController extends Controller
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

    public function index()
    {
        if(Common::checkRouteWisePermission('learning.calendar.index',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }
        $resort_id = $this->resort->resort_id;
        $page_title ='Learning Session Calendar';
        $categories= LearningCategory::where('resort_id',$resort_id)->get();
        $positions = ResortPosition::where('status','active')->where('resort_id',$resort_id)->get();
        $departments = ResortDepartment::where('status','active')->where('resort_id',$resort_id)->get();
        $employees = Employee::with('resortAdmin')->where('resort_id',$resort_id)->whereIn('status', ['Active', 'Probationary'])->get();
        $grades = config('settings.Position_Rank');
        $trainers = Employee::with('resortAdmin')->where('resort_id',$resort_id)->whereIn('rank',['1','2','3','4','5','7','8','9'])->whereIn('status', ['Active', 'Probationary'])->get();
        return view('resorts.learning.program.calendar',compact('page_title','categories','positions','departments','employees','grades','trainers'));
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $resort_id = $this->resort->resort_id;
        $request->validate([
            'title' => 'required|exists:learning_programs,id',
            'session_date' => 'required|date',
            'session_time' => 'nullable',
            'venue' => 'nullable|string|max:255',
            'session_frequency_hidden' => 'required|in:one-time,recurring,quarterly,annually',
        ]);
        $session_date = trim($request->session_date);

        $formatted_date = Carbon::parse($request->session_date)->format('Y-m-d');
        // dd($formatted_date);

        $session = LearningCalendarSession::create([
            'resort_id'=>$resort_id,
            'learning_program_id' => $request->title,
            'session_date' => $formatted_date,
            'session_time' => $request->session_time,
            'venue' => $request->venue,
            'frequency' => $request->session_frequency_hidden,
        ]);

        // Sessions carry no participant list of their own — participants are
        // the employees whose learning request for this same program was
        // Approved (LearningController@updateStatus notifies them of the
        // assignment the same way).
        try {
            $participantIds = LearningRequestEmployee::join('learning_requests', 'learning_requests.id', '=', 'learning_requests_employees.learning_request_id')
                ->where('learning_requests.resort_id', $resort_id)
                ->where('learning_requests.learning_id', $request->title)
                ->where('learning_requests.status', 'Approved')
                ->pluck('learning_requests_employees.employee_id')
                ->unique()
                ->all();

            if (!empty($participantIds)) {
                $learningProgram = LearningProgram::find($request->title);
                $programName = $learningProgram ? $learningProgram->name : 'Learning Program';
                Common::notifyEmployees(
                    $resort_id,
                    $participantIds,
                    'New Learning Session Scheduled',
                    "A session for '{$programName}' has been scheduled on " . Common::formatDate($formatted_date)
                        . ($request->session_time ? " at " . Common::formatDisplayTime($request->session_time) : "")
                        . ($request->venue ? ". Venue: {$request->venue}." : "."),
                    'Learning',
                    $session->id
                );
            }
        } catch (\Exception $ne) {
            \Log::warning('Learning calendar session notification failed: ' . $ne->getMessage());
        }

        return response()->json(
            [
                'success' => true, 
                'message' => 'Session added successfully!',
                'redirect_url'=>route('learning.calendar.index')
            ]
        );
    }

    public function getSessions(Request $request)
    {
        $resort_id = $this->resort->resort_id;
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Department-visibility scope for calendar sessions.
        $scopedEmpIds = \App\Helpers\Common::getPerformanceScopedEmpIds();

        // Fetch Training Schedules with Participants — range overlap so a multi-day
        // event (e.g. April 26 → April 27) shows on every day it runs, not only
        // the start day.
        $sessions = TrainingSchedule::where('resort_id', $resort_id)
        ->where(function ($query) use ($startDate, $endDate) {
            $query->whereBetween('start_date', [$startDate, $endDate])
                ->orWhereBetween('end_date', [$startDate, $endDate])
                ->orWhere(function ($subQuery) use ($startDate, $endDate) {
                    $subQuery->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                });
        })
        ->when(is_array($scopedEmpIds), fn($q) => $q->whereHas('participants', fn($sq) => $sq->whereIn('employee_id', $scopedEmpIds)))
        ->with(['learningProgram', 'participants.employee.resortAdmin','participants.employee.position'])
        // Chronological (start_date asc) — the calendar event list shows
        // upcoming sessions in the order they'll happen, not creation order.
        ->orderBy('start_date', 'asc')
        ->orderBy('start_time', 'asc')
        ->get();

        $events = [];
        // dd($sessions);
        // Process Training Schedules
        foreach ($sessions as $session) {
            $sessionData = $this->formatSessionData($session);

            // Fetch Attendees from Employees
            $attendees = [];
            foreach ($session->participants as $participant) {
                // dd($participant);
                $employee = Employee::with('resortAdmin')->find($participant->employee_id);
                if ($employee) {
                    $attendees[] = [
                        'name' => $employee->resortAdmin ? $employee->resortAdmin->full_name : $employee->first_name . ' ' . $employee->last_name,
                        'image' => $employee->resortAdmin
                            ? Common::getResortUserPicture($employee->resortAdmin->id)
                            : ($employee->profile_picture ?? asset('default-profile.png')),
                        'position' => $employee->position->position_title ?? null,
                    ];
                }
            }

            $sessionData['participants'] = $attendees;
            $events[] = $sessionData;
        }

        // Note: previously this method also surfaced approved LearningRequest rows
        // here. Dropped — they aren't real training_schedules, so they appeared on
        // the calendar but never in dashboard counts / history / schedule list,
        // which was confusing. Calendar now only shows real schedules. Approved
        // requests are visible through the Learning Requests page instead.

        return response()->json(['data' => $events]);
    }
    /**
     * Returns the auth user's compulsory / probationary learning programs as
     * FullCalendar events. start = joining_date, end = joining_date + completion_days,
     * coloured by status (overdue / completed / pending).
     */
    public function myCompulsoryEvents()
    {
        $resort_id = $this->resort->resort_id;
        $emp = $this->resort->GetEmployee ?? null;
        if (!$emp) {
            return response()->json(['data' => []]);
        }

        // Probationary programs apply to anyone on probation; mandatory programs
        // apply by department / position. Both use the auth user's joining_date as
        // the deadline anchor.
        $probationary = \App\Models\ProbationaryLearningProgram::with('program')
            ->where('resort_id', $resort_id)
            ->get();

        $mandatory = \DB::table('mandatory_learning_programs as mlp')
            ->join('learning_programs as lp', 'lp.id', '=', 'mlp.program_id')
            ->where('mlp.resort_id', $resort_id)
            ->where(function ($q) use ($emp) {
                $q->whereNull('mlp.department_id')->orWhere('mlp.department_id', $emp->Dept_id);
            })
            ->where(function ($q) use ($emp) {
                $q->whereNull('mlp.position_id')->orWhere('mlp.position_id', $emp->Position_id);
            })
            ->select('lp.id as program_id', 'lp.name as program_name', 'mlp.notify_before_days')
            ->get();

        // Programs the user has already completed (Present in any session of that program).
        $completed = \DB::table('training_attendance as ta')
            ->join('training_schedules as ts', 'ts.id', '=', 'ta.training_schedule_id')
            ->where('ts.resort_id', $resort_id)
            ->where('ta.employee_id', $emp->id)
            ->where('ta.status', 'Present')
            ->pluck('ts.training_id')
            ->unique()
            ->all();

        $joining = $emp->joining_date ? Carbon::parse($emp->joining_date) : Carbon::now();
        $events = [];

        foreach ($probationary as $r) {
            $programId = $r->program_id;
            $name = optional($r->program)->name ?? '—';
            $days = (int) ($r->completion_days ?? 0);
            $due  = $days > 0 ? (clone $joining)->addDays($days) : (clone $joining);
            $isDone = in_array($programId, $completed, true);
            $isOverdue = !$isDone && $due->isPast();
            $color = $isDone ? '#28a745' : ($isOverdue ? '#dc3545' : '#fd7e14');
            $events[] = [
                'title'        => 'My Program: ' . $name,
                'session_date' => $joining->toDateString(),
                'start_date'   => $joining->toDateString(),
                'end_date'     => $due->toDateString(),
                'start_time'   => '',
                'end_time'     => '',
                'description'  => $isDone ? 'Completed' : ($isOverdue ? 'Overdue — please complete ASAP.' : 'Compulsory program. Due by ' . $due->format('d M Y') . '.'),
                'color'        => $color,
                'participants' => [],
            ];
        }

        foreach ($mandatory as $r) {
            $days = (int) ($r->notify_before_days ?? 0);
            $due  = $days > 0 ? (clone $joining)->addDays($days) : (clone $joining)->addDays(30);
            $isDone = in_array($r->program_id, $completed, true);
            $isOverdue = !$isDone && $due->isPast();
            $color = $isDone ? '#28a745' : ($isOverdue ? '#dc3545' : '#fd7e14');
            $events[] = [
                'title'        => 'My Program: ' . $r->program_name,
                'session_date' => $joining->toDateString(),
                'start_date'   => $joining->toDateString(),
                'end_date'     => $due->toDateString(),
                'start_time'   => '',
                'end_time'     => '',
                'description'  => $isDone ? 'Completed' : ($isOverdue ? 'Overdue — please complete ASAP.' : 'Mandatory program. Due by ' . $due->format('d M Y') . '.'),
                'color'        => $color,
                'participants' => [],
            ];
        }

        return response()->json(['data' => $events]);
    }

    // Format Training Session Data
    private function formatSessionData($session)
    {
        return [
            'title' => $session->learningProgram->name,
            'session_date' => $session->start_date,
            // Calendar uses [start_date..end_date] to drop a dot on every day a
            // multi-day program runs — not only the start day.
            'start_date' => $session->start_date,
            'end_date' => $session->end_date,
            'start_time' => date('h:i A', strtotime($session->start_time)),
            'end_time' => date('h:i A', strtotime($session->end_time)),
            'description' => $session->learningProgram->description,
            'color' => $session->color_class ?? '#28a745',
            'participants' => $session->participants->map(function ($user) {
                return [
                    'name' => $user->name,
                    'image' => asset('storage/' . $user->profile_image)
                ];
            }),
        ];
    }

    // Format Learning Request Data
    private function formatLearningRequestData($request)
    {
        // Fetch the creator's ResortAdmin details
        $creator = ResortAdmin::find($request->created_by);

        return [
            'title' => "Learning Request: " . $request->learning->name,
            'session_date' => $request->start_date,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'start_time' => '09:00 AM', // Adjust if necessary
            'end_time' => '05:00 PM', // Adjust if necessary
            'description' => "Learning request from " . ($creator ? $creator->full_name : 'Unknown') . ". " . $request->learning->description,
            'color' => '#ff9800', // Orange for differentiation
            'participants' => $request->employees->map(function ($emp) {
                return [
                    'name' => $emp->employee->first_name . ' ' . $emp->employee->last_name,
                    'image' => $emp->employee->profile_picture
                        ? asset('storage/' . $emp->employee->profile_picture)
                        : asset('default-profile.png'),
                ];
            }),
        ];
    }
    
}