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
    }

    public function index(Request $request)
    {
        $page_title ='Probation';
        if($request->ajax())
        {
            $query = Employee::with(['position', 'department','resortAdmin'])
                    ->where('employment_type', 'Probationary');

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

            if($request->filled('date')){
                $query->where('probation_end_date', $request->date);
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
                    $date = \Carbon\Carbon::parse($row->joining_date);
                    return $date->format('d M Y') . ' (' . $date->diffForHumans(null, true) . ')';
                })
                ->addColumn('probation_end_date', function ($row) {
                    $end = \Carbon\Carbon::parse($row->probation_end_date);
                    return $end->format('d M Y') . ' (' . $end->diffForHumans(null, true) . ')';
                })
                ->addColumn('onboarding_training', function ($row) {
                    // Use training_id from MonthlyCheckingModel
                    $latest = MonthlyCheckingModel::where('emp_id', $row->id)->latest()->first();
                    return $latest?->tranining_id
                        ? '<span class="badge badge-themeSuccess">Completed</span>'
                        : '<span class="badge badge-themeDangerNew">Not Started</span>';
                })
                ->addColumn('monthly_checkin_status', function ($row) use ($request) {
                    $month = $request->get('month') ?? Carbon::now()->format('Y-m');
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
                            <a class="btn-lg-icon btnIcon-yellow extend-probation '.$edit_class.'" title="Extend Probation" data-id="'.$row->id.'">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                            </a>
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
        $employees = Employee::with(['resortAdmin','position','department'])->where('resort_id',$resort_id)->get();
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
        $joiningDate = Carbon::parse($employee->joining_date)->startOfMonth();
        $probationEnd = Carbon::parse($employee->probation_end_date)->endOfMonth();
        // dd($probationEnd);
        
        $monthlyCheckins = [];
        $today = Carbon::today();
        
        $current = $joiningDate->copy();
        while ($current->lessThanOrEqualTo($probationEnd)) {
            $startOfMonth = $current->copy()->startOfMonth()->format('Y-m-d');
            $endOfMonth = $current->copy()->endOfMonth()->format('Y-m-d');
            // dd($startOfMonth, $endOfMonth);
            $checkin = MonthlyCheckingModel::where('emp_id', $employee->id)
                ->whereRaw("STR_TO_DATE(date_discussion, '%d/%m/%Y') BETWEEN ? AND ?", [$startOfMonth, $endOfMonth])
                ->first();
            // dd($checkin)
            $status = 'Pending';
            $badgeClass = 'badge-themeWarning';
        
            if ($current->lt($today)) {
                $status = $checkin ? 'Completed' : 'Missed';
                $badgeClass = $checkin ? 'badge-themeSuccess' : 'badge-themeDangerNew';
            } elseif ($current->isSameMonth($today)) {
                $status = $checkin ? 'Completed' : 'Pending';
                $badgeClass = $checkin ? 'badge-themeSuccess' : 'badge-themeWarning';
            }
        
            $monthlyCheckins[] = [
                'label' => $current->format('F Y'),
                'status' => $status,
                'badge_class' => $badgeClass,
            ];
        
            $current->addMonth();
        }
        
        return view('resorts.people.probation.detail',compact('page_title','employee','monthlyCheckins'));
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
        $probationEndDate = \Carbon\Carbon::parse($employee->probation_end_date)->format('d M Y');

        $placeholders = [
            '{{employee_name}}'       => (string) optional($employee->resortAdmin)->full_name,
            '{{position}}'            => (string) optional($employee->position)->position_title,
            '{{resort_name}}'         => (string) $resort->resort_name,
            '{{probation_end_date}}'  => $probationEndDate,
            '{{date}}'                => now()->format('d M Y'),
            '{{employment_type}}'     => (string) $employee->employment_type,
            '{{position_title}}'      => (string) optional($employee->position)->position_title,
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