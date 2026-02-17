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

        LearningCalendarSession::create([
            'resort_id'=>$resort_id,
            'learning_program_id' => $request->title,
            'session_date' => $formatted_date,
            'session_time' => $request->session_time,
            'venue' => $request->venue,
            'frequency' => $request->session_frequency_hidden,
        ]);

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

        // Fetch Training Schedules with Participants
        $sessions = TrainingSchedule::where('resort_id', $resort_id)
        ->where(function ($query) use ($startDate, $endDate) {
            $query->whereBetween('start_date', [$startDate, $endDate])
                ->orWhereBetween('end_date', [$startDate, $endDate])
                ->orWhere(function ($subQuery) use ($startDate, $endDate) {
                    $subQuery->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                });
        })
        ->with(['learningProgram', 'participants.employee.resortAdmin','participants.employee.position'])
        ->orderBy('created_at', 'desc')
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

        // Fetch Approved Learning Requests
        $learningRequests = LearningRequest::where('resort_id', $resort_id)
            ->where('status', 'Approved')
            ->whereBetween('start_date', [$startDate, $endDate])
            ->with(['learning', 'employees.employee.resortAdmin','employees.employee.position']) // Load employee and resortAdmin
            ->get();

        // Process Learning Requests
        foreach ($learningRequests as $request) {
            $requestData = $this->formatLearningRequestData($request);

            // Fetch Employees Attending
            $attendees = [];
            foreach ($request->employees as $learningRequestEmployee) {
                $employee = $learningRequestEmployee->employee;
                if ($employee) {
                    $attendees[] = [
                        'name' => $employee->resortAdmin ? $employee->resortAdmin->full_name : $employee->first_name . ' ' . $employee->last_name,
                        'image' => $employee->resortAdmin
                            ? Common::getResortUserPicture($employee->resortAdmin->id)
                            : ($employee->profile_picture ?? asset('default-profile.png')),
                        'position' => $employee->position ? $employee->position->position_title : "",
                    ];
                }
            }

            $requestData['participants'] = $attendees;
            $events[] = $requestData;
        }

        return response()->json(['data' => $events]);
    }
    // Format Training Session Data
    private function formatSessionData($session)
    {
        return [
            'title' => $session->learningProgram->name,
            'session_date' => $session->start_date,
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