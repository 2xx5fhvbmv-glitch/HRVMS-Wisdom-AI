<?php

namespace App\Http\Controllers\Resorts\Learning;

use App\Http\Controllers\Controller;
use App\Events\ResortNotificationEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\LearningCategory;
use App\Models\LearningProgram;
use App\Models\TrainingParticipant;
use App\Models\ResortPosition;
use App\Models\ResortDepartment;
use App\Models\Employee;
use App\Models\TrainingSchedule;
use App\Models\TrainingAttendance;
use Auth;
use DB;
use Common;
use Carbon\Carbon;

class AttendanceController extends Controller
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

    public function index(Request $request)
    {
        $page_title = 'Attendance List';
        // Schedule the page was opened for (base64 in the query string).
        $scheduleId = base64_decode($request->query('schedule_id'));

        // Dropdown of this resort's schedules that can still be marked
        // (Scheduled or Ongoing) — the old query was status='Ongoing' only and
        // had no resort filter, so the schedule just clicked (often still
        // 'Scheduled') never appeared and other resorts' schedules leaked in.
        $trainings = TrainingSchedule::with('learningProgram')
            ->where('resort_id', $this->resort->resort_id)
            ->whereIn('status', ['Scheduled', 'Ongoing', 'Completed'])
            ->orderByDesc('start_date')
            ->get();

        return view('resorts.learning.attendance.index', compact('scheduleId','trainings','page_title'));
    }

    public function list(Request $request)
    {
        $resort_id = $this->resort->resort_id;

        // Department visibility: HR/GM see everything, non-HR HOD/XCOM only trainings
        // that include at least one employee from their department.
        $scopedEmpIds = Common::getPerformanceScopedEmpIds();

        $query = TrainingSchedule::with(['learningProgram', 'participants.employee.resortAdmin'])
            ->where('training_schedules.resort_id', $resort_id)
            ->when(is_array($scopedEmpIds), fn($q) => $q->whereHas('participants', fn($sq) => $sq->whereIn('employee_id', $scopedEmpIds)));

        // Search Filter
        if ($request->searchTerm) {
            $searchTerm = $request->searchTerm;
        
            $query->whereHas('learningProgram', function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('delivery_mode', 'LIKE', "%{$searchTerm}%");
            })
            ->orWhere('status', 'LIKE', "%{$searchTerm}%")
            ->orWhere('start_date', 'LIKE', "%{$searchTerm}%")
            ->orWhere('end_date', 'LIKE', "%{$searchTerm}%")
            ->orWhere('start_time', 'LIKE', "%{$searchTerm}%")
            ->orWhere('end_time', 'LIKE', "%{$searchTerm}%");
        
            // Separate whereHas for employee search
            $query->orWhereHas('participants.employee.resortAdmin', function ($q) use ($searchTerm) {
                $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$searchTerm}%"])
                  ->orWhere('first_name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('last_name', 'LIKE', "%{$searchTerm}%");
            });
        }
        

        // Schedule filter — the page is opened to mark attendance for ONE
        // schedule, so scope to it. The #trainingFilter dropdown carries
        // training_schedules.id values; previously the controller filtered
        // by training_id (the program) and ignored schedule_id entirely, so
        // every schedule's participants showed up regardless.
        $scheduleId = $request->input('schedule_id') ?: $request->input('training');
        if ($scheduleId) {
            $query->where('training_schedules.id', $scheduleId);
        }

        $sessions = $query->get();
        $data = [];

        foreach ($sessions as $session) {
            foreach ($session->participants as $participant) {
                $employee = $participant->employee;
                if ($employee) {
                    $position = ResortPosition::where('id', $employee->Position_id)->first();

                    // Fetch attendance count for this employee in this training session
                    $total_present_days = TrainingAttendance::where('training_schedule_id', $session->id)
                        ->where('employee_id', $employee->id)
                        ->count();

                    // Calculate total training days
                    $total_training_days = \Carbon\Carbon::parse($session->start_date)
                        ->diffInDays(\Carbon\Carbon::parse($session->end_date)) + 1; // +1 to include start date

                    // Format attendance as Present/Total
                    $attendance_status = "{$total_present_days}/{$total_training_days}";

                    // Fix: Add training_schedule_id to the history URL
                    $historyUrl = route('attendance.history.page', [
                        'employee_id' => base64_encode($employee->id),
                        'training_schedule_id' => base64_encode($session->id)
                    ]);

                    // $historyUrl = route('attendance.history.page', [
                    //     'employee_id' => $employee->id,
                    //     'training_schedule_id' => $session->id
                    // ]);
                    
                    $data[] = [
                        'id' => $employee->id,
                        'Emp_ID' => $employee->Emp_id,
                        'checkbox' => '<input type="checkbox" class="attendance-checkbox" data-employee-id="'.$employee->id.'" data-training-id="'.$session->id.'">',
                        'employee_name' => '<div class="tableUser-block">
                            <div class="img-circle"><img src="'.Common::getResortUserPicture($employee->Admin_Parent_id).'" alt="user"></div>
                            <span class="userReviewTasks-btn">'.$employee->resortAdmin->first_name.' '.$employee->resortAdmin->last_name.'</span>
                        </div>',
                        'position' => $position->position_title ?? 'N/A',
                        'training_name' => $session->learningProgram->name,
                        'training_type' => $session->learningProgram->delivery_mode,
                        'start_date' => \Carbon\Carbon::parse($session->start_date)->format('d M Y'),
                        'end_date' => \Carbon\Carbon::parse($session->end_date)->format('d M Y'),
                        'start_time' => $session->start_time,
                        'end_time' => $session->end_time,
                        'attendance' => $attendance_status,
                        'created_at' => $session->created_at,
                        'action' => '<a href="'.$historyUrl.'" class="btn-tableIcon btnIcon-orange">
                            <i class="fa-regular fa-eye"></i>
                        </a>',
                    ];
                }
            }
        }

        return response()->json(['data' => $data]);
    }

    public function markAttendanceBulk(Request $request)
    {
        $request->validate([
            'training_schedule_id' => ['required', Rule::exists('training_schedules', 'id')->where('resort_id', $this->resort->resort_id)],
            'employees' => 'required|array',
            'employees.*.employee_id' => ['required', Rule::exists('employees', 'id')->where('resort_id', $this->resort->resort_id)],
            'employees.*.status' => 'required|in:Present,Absent',
        ]);

        $trainingSchedule = TrainingSchedule::where('resort_id', $this->resort->resort_id)->find($request->training_schedule_id);
        if (!$trainingSchedule) {
            return response()->json(['success' => false, 'message' => 'Training schedule not found.'], 404);
        }
        // Backfill is allowed: no longer reject submissions whose current date sits
        // outside [start_date, end_date]. Persist against the schedule's start_date
        // so the record falls inside the training window for downstream reports.
        $attendanceDate = $trainingSchedule->start_date;

        foreach ($request->employees as $employeeData) {
            TrainingAttendance::updateOrCreate(
                [
                    'training_schedule_id' => $request->training_schedule_id,
                    'employee_id' => $employeeData['employee_id'],
                    'attendance_date' => $attendanceDate,
                ],
                [
                    'status' => $employeeData['status'],
                ]
            );
        }

        return response()->json(['success' => true, 'message' => 'Attendance updated successfully']);
    }

    public function attendanceHistoryPage($employee_id)
    {
        $page_title = 'Attendance History';
        $employee = Employee::with('resortAdmin')
            ->where('resort_id', $this->resort->resort_id)
            ->find(base64_decode($employee_id));
        if (!$employee) {
            abort(404, 'Employee not found.');
        }
        return view('resorts.learning.attendance.history', compact('employee','page_title'));
    }

    public function getAttendanceHistoryData(Request $request, $employee_id)
    {
        // dd(base64_decode($employee_id));
        $query = TrainingAttendance::with(['schedule.learningProgram'])
            ->where('employee_id', base64_decode($employee_id))
            // Scope through the owning schedule so another resort's attendance
            // records can't be read even if employee_id collides across tenants.
            ->whereHas('schedule', fn($sq) => $sq->where('resort_id', $this->resort->resort_id));

        // Apply search filter (training name)
        if ($request->has('searchTerm') && !empty($request->searchTerm)) {
            $search = $request->searchTerm;
            $query->whereHas('schedule.learningProgram', function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%");
            });
        }

        // Apply training type filter
        if ($request->has('type') && !empty($request->type)) {
            $query->whereHas('schedule.learningProgram', function ($q) use ($request) {
                $q->where('delivery_mode', $request->type);
            });
        }

        if ($request->date) {
            $query->Where('attendance_date', $request->date)
            ->orWhere('attendance_date', $request->date);
        }

        // Apply status filter
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        $history = $query->orderBy('updated_at', 'desc')->get();

        return response()->json(['data' => $history]);
    }

    public function saveAttendance(Request $request)
    {
        $request->validate([
            'training_schedule_id' => ['required', Rule::exists('training_schedules', 'id')->where('resort_id', $this->resort->resort_id)],
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('resort_id', $this->resort->resort_id)],
            'attendance_date' => 'required|date',
            'status' => 'required|in:Present,Absent,Late',
            'notes' => 'nullable|string|max:500',
        ]);

        $attendance = TrainingAttendance::updateOrCreate(
            [
                'training_schedule_id' => $request->training_schedule_id,
                'employee_id' => $request->employee_id,
                'attendance_date' => Carbon::parse($request->attendance_date)->format('Y-m-d'),
            ],
            [
                'status' => $request->status,
                'notes' => $request->notes
            ]
        );

        return response()->json(['message' => 'Attendance saved successfully']);
    }

    public function getAttendanceChartData()
    {
        $resort_id = $this->resort->resort_id;

        // Fetch all training schedules that belong to this resort
        $trainings = TrainingSchedule::with(['trainingAttendances'])
            ->where('resort_id', $resort_id)
            ->get();

        $data = [
            'labels' => [],
            'values' => [],
            'late_percentage' => 0, // Initialize late percentage
            'colors' => ['#014653', '#53CAFF', '#EFB408', '#50B9BF', '#333333', '#8DC9C9']
        ];

        $totalEmployeesOverall = 0;
        $lateCountOverall = 0;

        foreach ($trainings as $training) {
            $totalEmployees = $training->trainingAttendances->count(); // Total employees scheduled
            $presentCount = $training->trainingAttendances->where('status', 'Present')->count(); // Employees marked Present
            $lateCount = $training->trainingAttendances->where('status', 'Late')->count(); // Employees marked Late
            
            // Accumulate for overall late percentage calculation
            $totalEmployeesOverall += $totalEmployees;
            $lateCountOverall += $lateCount;

            // Calculate attendance percentage
            $attendancePercentage = $totalEmployees > 0 ? ($presentCount / $totalEmployees) * 100 : 0;

            $data['labels'][] = $training->learningProgram->name ?? 'Unknown Training';
            $data['values'][] = round($attendancePercentage, 2);
        }

        // Calculate overall late attendance percentage
        $data['late_percentage'] = $totalEmployeesOverall > 0 ? round(($lateCountOverall / $totalEmployeesOverall) * 100, 2) : 0;

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function getTrainingParticipationData()
    {
        $resort_id = $this->resort->resort_id;
        $scopedEmpIds = Common::getPerformanceScopedEmpIds();

        // Fetch trainings that involve at least one employee in scope.
        $trainings = TrainingSchedule::with(['trainingAttendances.employee.department'])
            ->where('resort_id', $resort_id)
            ->when(is_array($scopedEmpIds), fn($q) => $q->whereHas('trainingAttendances', fn($sq) => $sq->whereIn('employee_id', $scopedEmpIds)))
            ->get();

        $data = [
            'labels' => [],
            'datasets' => [],
            'colors' => ['#014653', '#53CAFF', '#EFB408', '#50B9BF', '#333333', '#7AD45A', '#FF4B4B', '#F5738D', '#53CAFF']
        ];

        $departmentWiseParticipation = [];

        foreach ($trainings as $training) {
            $trainingName = $training->learningProgram->name ?? 'Unknown Training';

            // Count participants
            $totalParticipants = $training->trainingAttendances->count();

            // Store label
            $data['labels'][] = $trainingName;

            // Department-wise participation
            foreach ($training->trainingAttendances as $attendance) {
                $department = $attendance->employee->department->name ?? 'Unknown';

                if (!isset($departmentWiseParticipation[$department])) {
                    $departmentWiseParticipation[$department] = [];
                }

                // Store department participation count for this training
                $departmentWiseParticipation[$department][$trainingName] = isset($departmentWiseParticipation[$department][$trainingName]) 
                    ? $departmentWiseParticipation[$department][$trainingName] + 1 
                    : 1;
            }
        }

        // Convert department participation data into datasets
        $colorIndex = 0;
        foreach ($departmentWiseParticipation as $department => $participationData) {
            $data['datasets'][] = [
                'label' => $department,
                'data' => array_map(fn($training) => $participationData[$training] ?? 0, $data['labels']),
                'backgroundColor' => $data['colors'][$colorIndex % count($data['colors'])],
                'borderColor' => '#fff',
                'borderWidth' => 2,
                'borderRadius' => 10,
            ];
            $colorIndex++;
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

   


}