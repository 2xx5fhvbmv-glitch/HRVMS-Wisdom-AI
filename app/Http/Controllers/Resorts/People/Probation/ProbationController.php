<?php

namespace App\Http\Controllers\Resorts\People\Probation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Employee;
use App\Models\Resort;
use App\Models\ResortAdmin;
use App\Models\ResortDivision;
use App\Models\ResortDepartment;
use App\Models\ResortPosition;
use App\Models\ResortSection;
use App\Models\MonthlyCheckingModel;
use App\Models\ProbationLetterTemplate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\ProbationLetterMail;
use App\Models\EmployeeResignation;
use App\Models\EmployeeResignationReason;
use Auth;
use Config;
use Common;
use DB;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
class ProbationController extends Controller
{
    public $resort;
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;
    }

    public function index(Request $request)
    {
        $page_title ='Probation';
        $scopedDeptIds = Common::getScopedDepartmentIds();
        if($request->ajax())
        {
            $resortId = $this->resort->resort_id;

            // List shows ONLY in-progress probations (Active / Extended) whose
            // probation window is still open today. Confirmed/Failed are
            // terminal states; rows whose probation_end_date has passed (or
            // whose joining_date + 3 months has passed when end_date is null)
            // are also excluded — those employees are no longer probationary
            // even if probation_status was never updated. Rows with neither
            // probation_end_date nor joining_date are dropped because we have
            // no way to confirm they are currently on probation.
            $today = Carbon::today()->toDateString();
            $query = Employee::with(['position', 'department','resortAdmin'])
                    ->where('resort_id', $this->resort->resort_id)
                    ->whereIn('probation_status', ['Active', 'Extended'])
                    ->where(function ($q) use ($today) {
                        $q->whereDate('probation_end_date', '>=', $today)
                          ->orWhere(function ($qq) use ($today) {
                              $qq->whereNull('probation_end_date')
                                 ->whereNotNull('joining_date')
                                 ->whereRaw('DATE_ADD(joining_date, INTERVAL 3 MONTH) >= ?', [$today]);
                          });
                    })
                    ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds));

            if ($request->filled('department_id')) {
                $query->where('Dept_id', $request->department_id);
            }

            if ($request->filled('position_id')) {
                $query->where('Position_id', $request->position_id);
            }

            if ($request->filled('status')) {
                $query->where('probation_status', $request->status);
            }

            if ($request->filled('searchTerm')) {
                $term = $request->searchTerm;
                // Emp_id lives on employees, names live on resort_admins —
                // querying Emp_id inside the whereHas would target a non-existent
                // resort_admins.Emp_id column.
                $query->where(function ($q) use ($term) {
                    $q->where('employees.Emp_id', 'like', '%'.$term.'%')
                      ->orWhereHas('resortAdmin', function ($qa) use ($term) {
                          $qa->where('first_name', 'like', '%'.$term.'%')
                             ->orWhere('last_name', 'like', '%'.$term.'%');
                      });
                });
            }

            // Date-range filter on probation end date (From / To).
            if($request->filled('date_from') && $request->filled('date_to')){
                $query->whereBetween('probation_end_date', [$request->date_from, $request->date_to]);
            } elseif($request->filled('date_from')){
                $query->whereDate('probation_end_date', '>=', $request->date_from);
            } elseif($request->filled('date_to')){
                $query->whereDate('probation_end_date', '<=', $request->date_to);
            }

            // Onboarding-training status filter — same buckets as the
            // onboarding_training column (Not Started / In Progress /
            // Completed / Absent), derived from training_participants.
            if ($request->filled('trainingStatus')) {
                $wanted = $request->trainingStatus;

                // Per-employee tallies: total bookings, attended, still pending.
                $perEmp = \DB::table('training_participants as tp')
                    ->join('training_schedules as ts', 'ts.id', '=', 'tp.training_schedule_id')
                    ->where('ts.resort_id', $resortId)
                    ->select(
                        'tp.employee_id',
                        \DB::raw('COUNT(*) as total'),
                        \DB::raw("SUM(CASE WHEN tp.status IN ('Present','Late') THEN 1 ELSE 0 END) as attended"),
                        \DB::raw("SUM(CASE WHEN tp.status = 'Pending' THEN 1 ELSE 0 END) as pending")
                    )
                    ->groupBy('tp.employee_id')
                    ->get();

                $bookedIds     = $perEmp->pluck('employee_id')->all();
                $inProgressIds = $perEmp->filter(fn($r) => $r->pending > 0)
                    ->pluck('employee_id')->all();
                $completedIds  = $perEmp->filter(fn($r) => $r->pending == 0 && $r->attended >= $r->total)
                    ->pluck('employee_id')->all();
                $absentIds     = $perEmp->filter(fn($r) => $r->pending == 0 && $r->attended < $r->total)
                    ->pluck('employee_id')->all();

                switch ($wanted) {
                    case 'Completed':
                        $query->whereIn('employees.id', $completedIds ?: [0]);
                        break;
                    case 'In Progress':
                        $query->whereIn('employees.id', $inProgressIds ?: [0]);
                        break;
                    case 'Absent':
                        $query->whereIn('employees.id', $absentIds ?: [0]);
                        break;
                    case 'Not Started':
                        // Employees with no training bookings at all.
                        if (!empty($bookedIds)) {
                            $query->whereNotIn('employees.id', $bookedIds);
                        }
                        break;
                }
            }

            $edit_class = '';
            if(Common::checkRouteWisePermission('people.probation',config('settings.resort_permissions.view')) == false){
                $edit_class = 'd-none';
            }

            return datatables()->of($query)
                ->addColumn('employee_id', fn($row) => '#'.$row->Emp_id)
                ->addColumn('employee_name', fn($row) => '
                    <div class="tableUser-block">
                        <div class="img-circle">
                            <img src="'.Common::getResortUserPicture($row->Admin_Parent_id ?? null).'" alt="user">
                        </div>
                        <span class="userApplicants-btn">'.$row->resortAdmin->full_name.'</span>
                    </div>')
                ->addColumn('position', fn($row) => optional($row->position)->position_title)
                ->addColumn('department', fn($row) => optional($row->department)->name)
                ->addColumn('joining_date', function ($row) {
                    // Carbon::parse(null) silently returns "now" — without this
                    // guard employees with no joining date wrongly showed
                    // today's date (and then no derived probation end date).
                    if (empty($row->joining_date)) {
                        return 'Not set';
                    }
                    return \Carbon\Carbon::parse($row->joining_date)->format('d M Y');
                })
                ->addColumn('probation_end_date', function ($row) {
                    // Use the explicit probation_end_date; when it isn't set,
                    // derive it as joining_date + 3 months (the standard
                    // probation window). Falls back to a placeholder only when
                    // there is no joining date either — Carbon::parse(null)
                    // would otherwise silently return "now".
                    $end = null;
                    if (!empty($row->probation_end_date)) {
                        $end = \Carbon\Carbon::parse($row->probation_end_date);
                    } elseif (!empty($row->joining_date)) {
                        $end = \Carbon\Carbon::parse($row->joining_date)->addMonths(3);
                    }
                    if (!$end) {
                        return 'Not set';
                    }
                    return $end->format('d M Y');
                })
                ->addColumn('onboarding_training', function ($row) use ($resortId) {
                    // Reflects whatever L&D training the employee is booked on,
                    // from training_participants (the real attendee table).
                    //   no bookings                          → Not Started
                    //   a session not held / not marked yet  → In Progress
                    //   all sessions held & all attended     → Completed
                    //   all sessions held, some not attended → Absent
                    $statuses = \DB::table('training_participants as tp')
                        ->join('training_schedules as ts', 'ts.id', '=', 'tp.training_schedule_id')
                        ->where('ts.resort_id', $resortId)
                        ->where('tp.employee_id', $row->id)
                        ->pluck('tp.status');

                    $t = $this->resolveOnboardingTraining($statuses);
                    return '<span class="badge '.$t['badge'].'">'.$t['label'].'</span>';
                })
                ->addColumn('monthly_checkin_status', function ($row) use ($request) {
                    // "All Months" sends an empty month — fall back to the
                    // current month for the per-month check-in status column
                    // (Carbon::parse('-01') would otherwise error).
                    $month = $request->filled('month') ? $request->get('month') : Carbon::now()->format('Y-m');
                    $monthStart   = Carbon::parse($month . '-01')->startOfMonth();
                    $monthEnd     = Carbon::parse($month . '-01')->endOfMonth();
                    $monthLabel   = $monthStart->format('M Y');

                    $checkin = MonthlyCheckingModel::where('emp_id', $row->id)
                        ->whereRaw("STR_TO_DATE(date_discussion, '%d/%m/%Y') BETWEEN ? AND ?", [
                            $monthStart->format('Y-m-d'),
                            $monthEnd->format('Y-m-d'),
                        ])
                        ->first();

                    if ($checkin) {
                        return '<span class="badge badge-themeSuccess">Up to date (' . $monthLabel . ')</span>';
                    }

                    // The month isn't over yet — no check-in can be "missed"
                    // until the window closes. Show Due for the current month
                    // (still in progress) and Upcoming for any future month a
                    // user might select via the filter.
                    $today = Carbon::now();
                    if ($monthEnd->isFuture() || $monthEnd->isSameDay($today->copy()->endOfMonth())) {
                        $label = $today->between($monthStart, $monthEnd) ? 'Due' : 'Upcoming';
                        return '<span class="badge badge-themeWarning">' . $label . ' (' . $monthLabel . ')</span>';
                    }

                    return '<span class="badge badge-themeDangerNew">Missed (' . $monthLabel . ')</span>';
                })              
                ->addColumn('review_status', function ($row) {
                    switch($row->probation_status) {
                        case 'Active':
                            return '<span class="badge badge-info">Active</span>';
                        case 'Extended':
                            return '<span class="badge badge-warning">Extended</span>';
                        case 'Confirmed':
                            return '<span class="badge badge-themeSuccess">Confirmed</span>';
                        case 'Failed':
                            return '<span class="badge badge-themeDangerNew">Failed</span>';
                        default:
                            return '<span class="badge badge-secondary">Pending</span>';
                    }
                })
                ->addColumn('actions', function($row) use ($edit_class) {
                    $viewUrl = route('people.probation.details', base64_encode($row->id));
                    return '
                        <div class="d-flex align-items-center">
                            <!-- Confirm / Fail Probation actions hidden here (per request) —
                                 HR uses the Confirm / Fail / letter actions on the
                                 probation details page instead.
                            <a class="btn-lg-icon btnIcon-success confirm-probation '.$edit_class.'" title="Confirm Probation Complete" data-id="'.$row->id.'">
                                <i class="fa-solid fa-check"></i>
                            </a>
                            <a class="btn-lg-icon btnIcon-danger fail-probation '.$edit_class.'" title="Failed Probation" data-id="'.$row->id.'">
                                <i class="fa-solid fa-xmark"></i>
                            </a>
                            <a class="btn-lg-icon btnIcon-yellow extend-probation '.$edit_class.'" title="Extend Probation" data-id="'.$row->id.'">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                            </a>
                            -->
                            <a href="' . $viewUrl . '" class="btn-lg-icon btnIcon-skyblue" title="View Detail">
                                <i class="fa-regular fa-eye"></i>
                            </a>
                        </div>';
                })
                ->rawColumns(['employee_name', 'onboarding_training', 'monthly_checkin_status','actions','review_status'])
                ->make(true);
        }
        $resort_id = $this->resort->resort_id;
        $departments = ResortDepartment::where('resort_id',$resort_id)->where('status','active')->get();
        $positions = ResortPosition::where('resort_id',$resort_id)->where('status','active')->get();
        $employees = Employee::with(['resortAdmin','position','department'])
            ->where('resort_id',$resort_id)
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))
            ->get();
        return view('resorts.people.probation.list',compact('page_title','resort_id','employees','departments','positions'));
    }

    /**
     * Resolve the "Onboarding Training" status from a collection of the
     * employee's training_participants.status values. Single source of truth
     * for the probation list column AND the details-page timeline.
     *
     *   no bookings                          → Not Started
     *   a session not held / not marked yet  → In Progress  (status 'Pending')
     *   all sessions held & all attended     → Completed     (Present / Late)
     *   all sessions held, some not attended → Absent        (an 'Absent' row)
     *
     * @param  \Illuminate\Support\Collection $statuses
     * @return array{label:string, badge:string}
     */
    private function resolveOnboardingTraining($statuses): array
    {
        if ($statuses->isEmpty()) {
            return ['label' => 'Not Started', 'badge' => 'badge-themeDangerNew'];
        }
        // Any session still Pending → attendance not marked yet.
        if ($statuses->contains('Pending')) {
            return ['label' => 'In Progress', 'badge' => 'badge-info'];
        }
        // Every held session attended → Completed; otherwise Absent.
        $allAttended = $statuses->every(fn($s) => in_array($s, ['Present', 'Late'], true));
        return $allAttended
            ? ['label' => 'Completed', 'badge' => 'badge-themeSuccess']
            : ['label' => 'Absent', 'badge' => 'badge-themeDangerNew'];
    }

    public function details(Request $request,$id)
    {
        if(Common::checkRouteWisePermission('people.probation',config('settings.resort_permissions.view')) == false)
        {
            abort(403, 'Unauthorized access');
        }
        $page_title ='Probation Details';
        $employeeId = base64_decode($id);
        $employee = Employee::with(['resortAdmin','position','department','section','reportingTo.position','reportingTo.department','reportingToAdmin'])->findOrFail($employeeId);
        // Probation is a 3-month window. The end date is probation_end_date
        // when set, otherwise joining_date + 3 months. Carbon::parse(null)
        // silently returns "now", so guard against a missing joining date —
        // without it everything collapsed to today and only one monthly
        // check-in was generated.
        $probationMonths = 3;
        $today = Carbon::today();
        $joiningDate  = $employee->joining_date ? Carbon::parse($employee->joining_date) : null;
        $probationEnd = $employee->probation_end_date
            ? Carbon::parse($employee->probation_end_date)
            : ($joiningDate ? $joiningDate->copy()->addMonths($probationMonths) : null);

        // Probation is "completed" only once its window has fully elapsed —
        // used to warn HR before sending a Probation Successful letter early.
        $probationCompleted = $probationEnd ? $probationEnd->isPast() : false;

        // Progress / days-remaining.
        $totalDays = ($joiningDate && $probationEnd) ? $joiningDate->diffInDays($probationEnd) : 0;
        $remainingDays = 0;
        $progress = 0;
        if ($joiningDate && $probationEnd) {
            $remainingDays = $today->lte($probationEnd)
                ? ($today->lt($joiningDate) ? $totalDays : $today->diffInDays($probationEnd))
                : 0;
            $daysPassed = $totalDays - $remainingDays;
            $progress = $totalDays > 0 ? min(100, round(($daysPassed / $totalDays) * 100)) : 0;
        }

        // Always emit 3 monthly check-ins (3-month probation) so the Progress
        // Tracking timeline is consistent. When the employee has a joining
        // date the real due date + status are computed; with no joining date
        // the check-in still shows as "Not set" / Pending rather than
        // vanishing from the timeline.
        //
        // Match window = the CALENDAR MONTH of the due date. If the 1st
        // check-in is due 01 May, any check-in dated within May counts —
        // joining-date-anchored windows (rolling 30-day buckets) would
        // misclassify a 15 May check-in for an Apr-1 joiner as "wrong month".
        $monthlyCheckins = [];
        for ($i = 0; $i < $probationMonths; $i++) {
            $label = 'Not set';
            $status = 'Pending';
            $badgeClass = 'badge-themeWarning';

            if ($joiningDate) {
                $dueDate     = $joiningDate->copy()->addMonths($i + 1);
                $windowStart = $dueDate->copy()->startOfMonth();
                $windowEnd   = $dueDate->copy()->endOfMonth();
                $label       = $dueDate->format('d M Y');

                $checkin = MonthlyCheckingModel::where('emp_id', $employee->id)
                    ->whereRaw("STR_TO_DATE(date_discussion, '%d/%m/%Y') BETWEEN ? AND ?", [
                        $windowStart->format('Y-m-d'),
                        $windowEnd->format('Y-m-d'),
                    ])
                    ->first();

                if ($checkin) {
                    $status = 'Completed';
                    $badgeClass = 'badge-themeSuccess';
                } elseif ($windowEnd->lt($today)) {
                    // Calendar month has fully ended without a check-in.
                    $status = 'Missed';
                    $badgeClass = 'badge-themeDangerNew';
                } elseif ($today->between($windowStart, $windowEnd)) {
                    // The window is the current calendar month — still time
                    // to record the check-in.
                    $status = 'Due';
                    $badgeClass = 'badge-themeWarning';
                }
                // else: future window — keep default Pending/yellow.
            }

            $monthlyCheckins[] = [
                'label' => $label,
                'status' => $status,
                'badge_class' => $badgeClass,
            ];
        }

        $joiningLabel      = $joiningDate ? $joiningDate->format('d M Y') : 'Not set';
        $probationEndLabel = $probationEnd ? $probationEnd->format('d M Y') : 'Not set';

        // --- Onboarding training: one timeline row per probationary program ---
        // The resort's probationary_learning_programs config defines which
        // L&D programs a probationer must complete. Each becomes its own
        // Progress Tracking row with its own status + due date
        // (joining_date + completion_days).
        $probationaryPrograms = \DB::table('probationary_learning_programs as plp')
            ->join('learning_programs as lp', 'lp.id', '=', 'plp.program_id')
            ->where('plp.resort_id', $employee->resort_id)
            ->get(['plp.program_id', 'plp.completion_days', 'lp.name']);

        $onboardingPrograms = [];
        foreach ($probationaryPrograms as $prog) {
            // This employee's attendance on this program's scheduled sessions.
            $statuses = \DB::table('training_participants as tp')
                ->join('training_schedules as ts', 'ts.id', '=', 'tp.training_schedule_id')
                ->where('ts.resort_id', $employee->resort_id)
                ->where('ts.training_id', $prog->program_id)
                ->where('tp.employee_id', $employee->id)
                ->pluck('tp.status');

            $resolved = $this->resolveOnboardingTraining($statuses);

            // Due = joining date + the program's allowed completion window.
            $due = 'Not set';
            if ($joiningDate) {
                $due = $prog->completion_days
                    ? $joiningDate->copy()->addDays((int) $prog->completion_days)->format('d M Y')
                    : $joiningDate->format('d M Y');
            }

            $onboardingPrograms[] = [
                'name'  => $prog->name,
                'due'   => $due,
                'label' => $resolved['label'],
                'badge' => $resolved['badge'],
            ];
        }

        // --- Final Probation Review status ---
        // Reflects probation_status — Confirmed/Failed are terminal,
        // Active/Extended are still pending the review decision.
        switch ($employee->probation_status) {
            case 'Confirmed':
                $finalReviewStatus = 'Completed';
                $finalReviewBadge  = 'badge-themeSuccess';
                break;
            case 'Failed':
                $finalReviewStatus = 'Failed';
                $finalReviewBadge  = 'badge-themeDangerNew';
                break;
            default:
                $finalReviewStatus = 'Pending';
                $finalReviewBadge  = 'badge-themeWarning';
        }

        return view('resorts.people.probation.detail', compact(
            'page_title', 'employee', 'monthlyCheckins',
            'joiningLabel', 'probationEndLabel', 'remainingDays', 'progress',
            'probationCompleted',
            'onboardingPrograms',
            'finalReviewStatus', 'finalReviewBadge'
        ));
    }

    public function confirmProbation(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);
        $employee->probation_status = 'Confirmed';
        $employee->employment_type = $request->employment_type ?? 'Full-time'; // default fallback
        $employee->status = 'Active';
        $employee->probation_review_date = now();
        $employee->probation_confirmed_by = $this->resort->GetEmployee->id;
        $employee->save();
        return response()->json(['message' => 'Employee probation confirmed and employment type updated.']);
    }

    public function failProbation(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);
        $employee->probation_status = 'Failed';
        $employee->employment_type = 'Probationary';
        $employee->status = 'Terminated';
        $employee->save();

        $this->ensureExitClearanceRecord($employee, $request->input('remarks'));

        return response()->json(['message' => 'Probation failed successfully.']);
    }

    /**
     * Failing probation terminates the employee, but the actual offboarding —
     * department clearance, HOD/HR sign-off, certificates, F&F settlement —
     * lives on the Exit Clearance page. That page is keyed off
     * employee_resignation rows, so we create one here with reason
     * "Probation Failure" (seeded per resort by migration
     * 2026_05_20_100000_seed_probation_failure_resignation_reason). Idempotent
     * via firstOrCreate so re-failing the same employee doesn't duplicate.
     */
    private function ensureExitClearanceRecord(Employee $employee, $remarks = null)
    {
        $reason = EmployeeResignationReason::where('resort_id', $employee->resort_id)
            ->where('reason', 'Probation Failure')
            ->where('status', 'Active')
            ->first();

        if (!$reason) {
            // Safety net: if the seed migration hasn't run, create the reason
            // on the fly rather than crashing on the NOT NULL FK.
            $reason = EmployeeResignationReason::create([
                'resort_id'   => $employee->resort_id,
                'reason'      => 'Probation Failure',
                'status'      => 'Active',
                'created_by'  => 0,
                'modified_by' => 0,
            ]);
        }

        $today = now()->toDateString();

        EmployeeResignation::firstOrCreate(
            [
                'resort_id'   => $employee->resort_id,
                'employee_id' => $employee->id,
                'reason'      => $reason->id,
            ],
            [
                'resignation_date'           => $today,
                'last_working_day'           => $today,
                'certificate_issue'          => 'no',
                'full_and_final_settlement'  => 'no',
                'immediate_release'          => 'Yes',
                'status'                     => 'Approved',
                'hod_status'                 => 'Approved',
                'hod_meeting_status'         => 'Completed',
                'hr_status'                  => 'Approved',
                'hr_meeting_status'          => 'Completed',
                'comments'                   => $remarks,
            ]
        );
    }

    public function extendProbation(Request $request, $id)
    {
        $formattedProbationEndDate = $request->extension_date ? \Carbon\Carbon::createFromFormat('d/m/Y', $request->extension_date)->format('Y-m-d') : null;
        $employee = Employee::findOrFail($id);
        $employee->probation_status = 'Extended';
        $employee->employment_type = "Probationary";
        $employee->status = 'Active';
        $employee->probation_review_date =
        $employee->probation_end_date = $formattedProbationEndDate;
        $employee->probation_remarks = $request->remarks;
        $employee->save();
        return response()->json(['status' => 'success', 'message' => 'Probation extended successfully.']);
    }

    public function letterTamplate(Request $request)
    {
        // dd($request->all());
        $MailTemplete  = $request->content;
        $MailSubject  = $request->subject;
        $type  = $request->type;
        $id  = $request->MailTemplete;
        $placeholders = ProbationLetterTemplate::extractPlaceholders($request->content) ?? [];
        $resort_id = $this->resort->resort_id;

        DB::beginTransaction();
        try
        {
            if($request->Mode != "edit")
            {
                $validator = Validator::make([
                    'type' => $type, // use decoded value
                    'subject' => $request->subject,
                    'content' => $request->content,
                ], [
                    'type' => [
                        'required',
                        Rule::unique('probation_letter_templates', 'type')
                            ->where(function ($query) use ($resort_id) {
                                return $query->where('resort_id', $resort_id);
                            }),
                    ],
                    'subject' => 'required',
                    'content' => 'required',
                ], [
                    'type.required' => 'The type field is required.',
                    'type.unique' => 'The type already exists for this resort.',
                    'subject.required' => 'The Subject is required.',
                    'content.required' => 'The Content is required.',
                ]);
                if($validator->fails())
                {
                    return response()->json([
                        'success' => false,
                        'errors' => $validator->errors()
                    ], 422);
                }
                ProbationLetterTemplate::create([
                    "resort_id"=>$this->resort->resort_id,
                    'type'=>$type,
                    'subject'=>$MailSubject,
                    'content'=>$MailTemplete,
                    'placeholers'=>$placeholders,
                ]);
                $msg = 'Probation Letter Template Created Successfully';
            }
            else
            {
                $validator = Validator::make([
                    'type' => $type, // use decoded value
                    'subject' => $request->subject,
                    'content' => $request->content,
                ], [
                    'type' => [
                        'required',
                        Rule::unique('probation_letter_templates', 'type')
                            ->where(function ($query) use ($resort_id) {
                                return $query->where('resort_id', $resort_id);
                            })
                            ->ignore($request->id),
                    ],
                    'subject' => 'required',
                    'content' => 'required',
                ], [
                    'type.required' => 'The Type field is required.',
                    'type.unique' => 'The type already exists for this resort.',
                    'subject.required' => 'The Subject is required.',
                    'content.required' => 'The Content is required.',
                ]);
                if($validator->fails())
                {
                    return response()->json([
                        'success' => false,
                        'errors' => $validator->errors()
                    ], 422);
                }
                ProbationLetterTemplate::where("resort_id",$this->resort->resort_id)
                    ->where("id",$request->id)
                    ->update([
                        "resort_id"=>$this->resort->resort_id,
                        'type'=>$type,
                        'subject'=>$MailSubject,
                        'content'=>$MailTemplete,
                        'placeholers'=>$placeholders,
                    ]);
                $msg = 'Probation Letter Template Updated Successfully';
            }
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => $msg,
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Add Probation Letter Template '], 500);
        }
    }

    public function ProbationEmailTamplateIndex(Request $request)
    {
        if($request->ajax())
        {
            $probation_letters = ProbationLetterTemplate::where('probation_letter_templates.resort_id',$this->resort->resort_id)->get();
        
            return datatables()->of($probation_letters)
            ->addColumn('subject', function ($row) 
            {
                return $row->subject;
            })
            ->addColumn('action', function ($row) 
            {
                $id = base64_encode($row->id);
                return '
                <div  class="d-flex align-items-center">
                    <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" data-cat-id="' . e($id) . '">
                        <img src="' . asset("resorts_assets/images/edit.svg") . '" alt="Edit" class="img-fluid">
                    </a>
                    <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-cat-id="' . e($id) . '">
                        <img src="' . asset("resorts_assets/images/trash-red.svg") . '" alt="Delete" class="img-fluid">
                    </a>
                </div>';
            })
            ->rawColumns(['type','action'])
            ->make(true);
        }
    }

    public function GetEmailTamplate(Request $request)
    {
        $id=  base64_decode($request->id);
        $probation_letter = ProbationLetterTemplate::where('resort_id',$this->resort->resort_id)
            ->where('id',$id)
            ->first();

        $data = [
            'type'=> $probation_letter->type,
            'id'=>$probation_letter->id,
            'flag'=>"edit",
            "subject"=>$probation_letter->subject,
            'content'=>$probation_letter->content
        ];
        
         return response()->json([
                'success' => true,
                'message' => 'Probation Email Template Created Successfully',
                'data'=>$data
            ], 200);
    }

    public function sendProbationLetter(Request $request)
    {
        $employee = Employee::with('position', 'resortAdmin', 'department')->findOrFail($request->employee_id);
        $type = $request->type;
        $resort = Resort::findOrFail($employee->resort_id);
        // dd($resort);
        // Generate content
        $template = ProbationLetterTemplate::where('resort_id', $employee->resort_id)
        ->where('type', $type)
        ->first();

        if (!$template) {
            return response()->json(['error' => 'Template not found for this resort and type.'], 404);
        }
        // Probation end: the explicit column, else joining_date + 3 months
        // (Carbon::parse(null) would otherwise put today's date in the letter).
        if ($employee->probation_end_date) {
            $probationEndDate = \Carbon\Carbon::parse($employee->probation_end_date)->format('d M Y');
        } elseif ($employee->joining_date) {
            $probationEndDate = \Carbon\Carbon::parse($employee->joining_date)->addMonths(3)->format('d M Y');
        } else {
            $probationEndDate = 'N/A';
        }

        // Every placeholder the probation letter templates can use must be
        // filled — any {{token}} not listed here renders literally in the
        // sent email. Templates use Department_title and employee_code, which
        // were previously missing.
        $placeholders = [
            '{{employee_name}}'       => (string) optional($employee->resortAdmin)->full_name,
            '{{employee_code}}'       => (string) $employee->Emp_id,
            '{{position}}'            => (string) optional($employee->position)->position_title,
            '{{position_title}}'      => (string) optional($employee->position)->position_title,
            '{{Department_title}}'    => (string) optional($employee->department)->name,
            '{{resort_name}}'         => (string) $resort->resort_name,
            '{{probation_end_date}}'  => $probationEndDate,
            '{{date}}'                => now()->format('d M Y'),
            '{{employment_type}}'     => (string) $employee->employment_type,
        ];

        $letterContent = strtr($template->content, $placeholders);

        // Render the PDF through the letterhead wrapper so the configured
        // Letterhead & E-signature (People > Configuration > Letterhead)
        // is applied — header/footer images, address, e-signature block.
        // Falls back to resort logo + typed signature when none is set.
        $letterhead = Common::getLetterheadData($employee->resort_id);
        $pdf = Pdf::loadView('resorts.people.probation.probation_letter_pdf', [
            'letterContent'  => $letterContent,
            'letterhead'     => $letterhead,
            'resort'         => $resort,
            'resortLogo'     => Common::GetResortLogo($employee->resort_id),
            'signatureImage' => $letterhead['signatureImage'],
            'signatoryName'  => $letterhead['signatoryName'] ?: 'Human Resources Department',
            'signatoryTitle' => $letterhead['signatoryTitle']
                ?: 'For and on behalf of ' . ($resort->resort_name ?? 'the Management'),
        ])->setPaper('a4', 'portrait');
        // Allow DomPDF to load the local letterhead image files.
        $pdf->getDomPDF()->getOptions()->set('isRemoteEnabled', true);

        $fileName = 'probation-' . $type . '_' . $employee->id . '.pdf';
        $pdfPath = storage_path('app/' . $fileName);
        $pdf->save($pdfPath);

        // $pdfPath = 'letters/probation_' . $type . '_' . $employee->id . '.pdf';
        // Storage::put($pdfPath, $pdf->output());

        // Update employee
        $employee->probation_status = $type === 'success' ? 'Confirmed' : 'Failed';
        $employee->probation_letter_path = $pdfPath;
        $employee->employment_type = $request->employment_type ?? 'Full-time'; // default fallback
        // Confirmed → keep Active; Failed → terminate (matches failProbation()).
        $employee->status = $type === 'success' ? 'Active' : 'Terminated';
        $employee->probation_review_date = now();
        $employee->probation_confirmed_by = $this->resort->GetEmployee->id;
        $employee->save();

        if ($type !== 'success') {
            // Mirror the failProbation() flow so the employee shows up on the
            // Exit Clearance page regardless of which UI marked them Failed.
            // Remarks captured by the Send-Unsuccessful-Letter Swal modal
            // become the resignation's comments field.
            $this->ensureExitClearanceRecord($employee, $request->input('remarks'));
        }

        // Send email
        if (file_exists($pdfPath)) {
            Mail::to($employee->resortAdmin->email)->send(new ProbationLetterMail($employee, $pdfPath, $type, $resort, $fileName, $letterContent));
            return response()->json(['success' => true, 'message' => 'Letter sent successfully.']);
        } else {
            // Log or return error
            \Log::error("Latter PDF not found at $pdfPath");
            return response()->json(['success' => false, 'message' => 'Letter PDF not found at'. $pdfPath]);
        }
    }

    public function exportProbationPdf($employeeId)
    {
        $employee = Employee::with([
            'resortAdmin', 'department', 'position'
        ])->findOrFail($employeeId);

        $pdf = Pdf::loadView('resorts.people.probation.probation_pdf', compact('employee'));
        return $pdf->download('Probation_Details_' . $employee->Emp_id . '.pdf');
    }


}