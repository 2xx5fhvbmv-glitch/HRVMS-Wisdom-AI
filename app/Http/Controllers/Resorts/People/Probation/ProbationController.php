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
            $query = Employee::with(['position', 'department','resortAdmin'])
                    ->where('resort_id', $this->resort->resort_id)
                    ->whereIn('probation_status', ['Active', 'Extended', 'Confirmed', 'Failed'])
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
                $query->whereHas('resortAdmin', function ($q) use ($request) {
                    $q->where('first_name', 'like', '%'.$request->searchTerm.'%')
                      ->orWhere('last_name', 'like', '%'.$request->searchTerm.'%')
                      ->orWhere('Emp_id', 'like', '%'.$request->searchTerm.'%');
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
            $edit_class = '';
            if(Common::checkRouteWisePermission('people.probation',config('settings.resort_permissions.view')) == false){
                $edit_class = 'd-none';
            }

            // --- Onboarding training (L&D) pre-resolution -----------------------------
            // Resolve the resort's probationary learning programs ONCE here, outside the
            // per-row DataTables closure. The probationary programs (and the training
            // schedules backing them) are the same for every row, so doing this once
            // keeps the per-row work to a single COUNT query.
            $resortId = $this->resort->resort_id;
            $probationaryProgramIds = \App\Models\ProbationaryLearningProgram::where('resort_id', $resortId)
                ->pluck('program_id');
            $probationaryProgramCount = $probationaryProgramIds->count();

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
                ->addColumn('onboarding_training', function ($row) use ($resortId, $probationaryProgramIds, $probationaryProgramCount) {
                    // Reflects the employee's progress on the resort's probationary
                    // (onboarding) learning programs. A program counts as completed when
                    // the employee has a training_attendance record with status='Present'
                    // on a TrainingSchedule backed by that program — the same definition
                    // the Learning dashboard uses (DashboardController@computeCompulsoryCompletionPercent).
                    if ($probationaryProgramCount === 0) {
                        // No probationary programs configured for this resort.
                        return '<span class="badge badge-themeDangerNew">Not Started</span>';
                    }

                    // Distinct probationary programs this employee has completed.
                    $completedPrograms = \DB::table('training_attendance as ta')
                        ->join('training_schedules as ts', 'ts.id', '=', 'ta.training_schedule_id')
                        ->where('ts.resort_id', $resortId)
                        ->whereIn('ts.training_id', $probationaryProgramIds)
                        ->where('ta.employee_id', $row->id)
                        ->where('ta.status', 'Present')
                        ->distinct()
                        ->count('ts.training_id');

                    if ($completedPrograms >= $probationaryProgramCount) {
                        return '<span class="badge badge-themeSuccess">Completed</span>';
                    }
                    if ($completedPrograms > 0) {
                        return '<span class="badge badge-info">In Progress</span>';
                    }
                    return '<span class="badge badge-themeDangerNew">Not Started</span>';
                })
                ->addColumn('monthly_checkin_status', function ($row) use ($request) {
                    // "All Months" sends an empty month — fall back to the
                    // current month for the per-month check-in status column
                    // (Carbon::parse('-01') would otherwise error).
                    $month = $request->filled('month') ? $request->get('month') : Carbon::now()->format('Y-m');
                    $startOfMonth = Carbon::parse($month . '-01')->startOfMonth()->format('Y-m-d');
                    $endOfMonth = Carbon::parse($month . '-01')->endOfMonth()->format('Y-m-d');

                    $checkin = MonthlyCheckingModel::where('emp_id', $row->id)
                        ->whereRaw("STR_TO_DATE(date_discussion, '%d/%m/%Y') BETWEEN ? AND ?", [$startOfMonth, $endOfMonth])
                        ->first();
                
                    return $checkin
                        ? '<span class="badge badge-themeSuccess">Up to date ('.Carbon::parse($month . '-01')->startOfMonth()->format('M Y') .')</span>'
                        : '<span class="badge badge-themeDangerNew">Missed ('.Carbon::parse($month . '-01')->startOfMonth()->format('M Y') .')</span>';
                    
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
                            <a class="btn-lg-icon btnIcon-success confirm-probation '.$edit_class.'" title="Confirm Probation Complete" data-id="'.$row->id.'">
                                <i class="fa-solid fa-check"></i>
                            </a>
                            <a class="btn-lg-icon btnIcon-danger fail-probation '.$edit_class.'" title="Failed Probation" data-id="'.$row->id.'">
                                <i class="fa-solid fa-xmark"></i> 
                            </a>
                            <!-- Extend Probation action hidden for now (per request).
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
        $monthlyCheckins = [];
        for ($i = 0; $i < $probationMonths; $i++) {
            $label = 'Not set';
            $status = 'Pending';
            $badgeClass = 'badge-themeWarning';

            if ($joiningDate) {
                $monthStart = $joiningDate->copy()->addMonths($i);
                $monthEnd   = $joiningDate->copy()->addMonths($i + 1);
                $label = $monthEnd->format('d M Y');

                $checkin = MonthlyCheckingModel::where('emp_id', $employee->id)
                    ->whereRaw("STR_TO_DATE(date_discussion, '%d/%m/%Y') BETWEEN ? AND ?", [
                        $monthStart->format('Y-m-d'),
                        $monthEnd->format('Y-m-d'),
                    ])
                    ->first();

                if ($monthEnd->lt($today)) {
                    $status = $checkin ? 'Completed' : 'Missed';
                    $badgeClass = $checkin ? 'badge-themeSuccess' : 'badge-themeDangerNew';
                } elseif ($checkin) {
                    $status = 'Completed';
                    $badgeClass = 'badge-themeSuccess';
                }
            }

            $monthlyCheckins[] = [
                'label' => $label,
                'status' => $status,
                'badge_class' => $badgeClass,
            ];
        }

        $joiningLabel      = $joiningDate ? $joiningDate->format('d M Y') : 'Not set';
        $probationEndLabel = $probationEnd ? $probationEnd->format('d M Y') : 'Not set';

        return view('resorts.people.probation.detail', compact(
            'page_title', 'employee', 'monthlyCheckins',
            'joiningLabel', 'probationEndLabel', 'remainingDays', 'progress',
            'probationCompleted'
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

    public function failProbation($id)
    {
        $employee = Employee::findOrFail($id);
        $employee->probation_status = 'Failed';
        $employee->employment_type = 'Probationary';
        $employee->status = 'Terminated';
        $employee->save();
        return response()->json(['message' => 'Probation failed successfully.']);
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

        // Optionally, generate PDF
        $pdf = Pdf::loadHTML($letterContent);

        $fileName = 'probation-' . $type . '_' . $employee->id . '.pdf';
        $pdfPath = storage_path('app/' . $fileName);
        $pdf->save($pdfPath);

        // $pdfPath = 'letters/probation_' . $type . '_' . $employee->id . '.pdf';
        // Storage::put($pdfPath, $pdf->output());

        // Update employee
        $employee->probation_status = $type === 'success' ? 'Confirmed' : 'Failed';
        $employee->probation_letter_path = $pdfPath;
        $employee->employment_type = $request->employment_type ?? 'Full-time'; // default fallback
        $employee->status = 'Active';
        $employee->probation_review_date = now();
        $employee->probation_confirmed_by = $this->resort->GetEmployee->id;
        $employee->save();

        // Send email
        if (file_exists($pdfPath)) {
            Mail::to($employee->resortAdmin->email)->send(new ProbationLetterMail($employee,$pdfPath, $type, $resort,$fileName));
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