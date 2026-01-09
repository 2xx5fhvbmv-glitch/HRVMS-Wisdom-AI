<?php

namespace App\Http\Controllers\Resorts\People\Promotion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\Models\Employee;
use App\Models\Resort;
use App\Models\ProbationLetterTemplate;
use App\Mail\PromotionLetterMail;
use Illuminate\Support\Facades\Mail;
use App\Models\ResortPosition;
use App\Models\ResortDepartment;
use App\Models\ResortBenifitGrid;
use App\Models\EmployeePromotion;
use App\Models\EmployeePromotionApproval;
use App\Events\ResortNotificationEvent;
use App\Models\TrainingSchedule;
use App\Models\Compliance;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\PromotionHistoryExport;
use Auth;
use Config;
use DB;
use Common;
use Carbon\Carbon;
use App\Models\JobDescription;
class PromotionController extends Controller
{
    public $resort;
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
    }

    public function index()
    {
        $page_title ='Initiate Promotion';
        $resort_id = $this->resort->resort_id;
        $employees = Employee::with(['resortAdmin','position','department'])->where('resort_id',$resort_id)->where('status','Active')->get();
        $positions = ResortPosition::where('resort_id',$resort_id)->where('status','active')->get();
        $emp_grade = config('settings.eligibilty'); // Assuming this maps IDs to names
        $benefitGrids = ResortBenifitGrid::where('resort_id',$this->resort->resort_id)->get();
        return view('resorts.people.promotion.initiate-promotion',compact('page_title','employees','positions','emp_grade','benefitGrids'));
    }

    public function submitPromotion(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'select_employee' => 'required|exists:employees,id',
            'new_position' => 'required|exists:resort_positions,id',
            'level' => 'nullable',
            'salary_inc' => 'nullable|numeric',
            'salary_amt' => 'nullable|numeric',
            'effective_date' => 'required',
            'job_des' => 'nullable|string',
            'benefit_grid' => 'nullable|string',
            'comments' => 'nullable|string',
        ]);
        $formattedEffectiveDate = $request->effective_date ? Carbon::createFromFormat('d/m/Y', $request->effective_date)->format('Y-m-d') : null;
        // dd($formattedEffectiveDate);
        // Store promotion request
        
        $JobDescription =  JobDescription::where('Resort_id', $this->resort->resort_id)->where('Position_id', $request->new_position)->first();
        $jd_id = $JobDescription ? $JobDescription->id : null;
        $promotion = EmployeePromotion::create([
            'resort_id' => $this->resort->resort_id,
            'Jd_id' => $jd_id,
            'employee_id' => $request->select_employee,
            'current_position_id'=> $request->old_position_id,
            'new_position_id' => $request->new_position,
            'new_level' => $request->level,
            'current_salary' => $request->hdn_old_basic_salary,
            'salary_increment_percent' => $request->salary_inc,
            'salary_increment_amount' => $request->salary_amt,
            'new_salary'=>$request->hdn_new_basic_salary,
            'effective_date' => $formattedEffectiveDate,
            'updated_benefit_grid' => $request->benefit_grid,
            'comments' => $request->comments,
            'status' => 'Pending',
            'letter_dispatched' => 'No'
        ]);

        if($promotion)
        {
            $EmployeePromotion = [];
                $query = Employee::with(['resortAdmin', 'promotions.newPosition', 'position', 'department'])
                ->where('resort_id', $this->resort->resort_id)
                ->where('status', 'Active')
                ->whereHas('promotions', function ($query) use ($promotion)
                {
                    $query->where('Jd_id',"=", null)
                    ->where('id',$promotion->id); 
                })
                ->get()
                ->map(function ($employee) use (&$EmployeePromotion) {
                    $employee->Emp_name = $employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name;
                    $employee->Emp_id = $employee->Emp_id;
                    $employee->Department_name = $employee->department->name;
                    $employee->Position_name = $employee->position->position_title;

                    // Check if the employee has a promotion with a Job Description
                    if ($employee->promotions && $employee->promotions->count() > 0) 
                    {
                        $employee->promotions->each(function ($promotions) use (&$EmployeePromotion, $employee) {
                            if (is_null($promotions->Jd_id)) 
                            {
                                    $employee->PromotionDate = Carbon::parse($promotions->created_at)->format('d M Y');
                                    $employee->PromotionPosition = $promotions->newPosition->position_title;
                                        $EmployeePromotion[] = 
                                        [
                                        'resort_id' => $this->resort->resort_id,
                                        'employee_id' => $employee->id,
                                        'module_name' => 'Employee Promotion',
                                        'compliance_breached_name' => 'Job Description Not Received',
                                        'description' => "Employee {$employee->Emp_name} ({$employee->Position_name}) was promoted to {$employee->PromotionPosition} on {$employee->PromotionDate} but has not received the job description.",
                                        'reported_on' => Carbon::now(),
                                        'status' => 'Breached',
                                    ];

                            }
                        });



                    }
                    return $employee;
                });

            if (!empty($EmployeePromotion)) 
            {                                                                           
                Compliance::insert($EmployeePromotion);
            }
        }
       
        $promotionApprovalFlow = collect();

        // Finance Approver Logic
        $financeManagerTitles = ['Director of Finance', 'Finance Manager'];
        $positionIds = ResortPosition::where('resort_id', $this->resort->resort_id)
            ->whereIn('position_title', $financeManagerTitles)
            ->pluck('id');

        $financeApprover = Employee::with(['resortAdmin', 'position'])
            ->whereIn('position_id', $positionIds)
            ->where('resort_id', $this->resort->resort_id)
            ->select('id')
            ->first();

        if ($financeApprover) {
            // $promotionApprovalFlow->push($financeApprover);
            $promotionApprovalFlow->push([
                'approver' => $financeApprover,
                'rank' => 'Finance'
            ]);
        }

        // GM Approver Logic (Rank 8 in position table)
        $gmApprover = Employee::with('position')
            ->whereHas('position', fn($query) => $query->where('rank', 8))
            ->where('resort_id', $this->resort->resort_id)
            ->select('id')
            ->first();

        if ($gmApprover) 
        {
            // $promotionApprovalFlow->push($gmApprover);
            $promotionApprovalFlow->push([
                'approver' => $gmApprover,
                'rank' => 'GM'
            ]);
        }
        // dd($promotionApprovalFlow);

        // Create approval entries
        $promotionApprovalFlow->each(function ($approver) use ($promotion) {
            EmployeePromotionApproval::create([
                'promotion_id' => $promotion->id,
                'approved_by'    => $approver['approver']->id,
                'status' => 'Pending',
                'approval_rank'  => $approver['rank'], // Add this to your DB schema if not already

            ]);

            $msg = "📢 New Promotion Request Submitted\n👤 Employee: " . $promotion->employee->resortAdmin->full_name .
            "\n🏢 From: " . optional($promotion->currentPosition)->position_title .
            "\n➡️ To: " . optional($promotion->newPosition)->position_title .
            "\n📅 Effective Date: " . Carbon::parse($promotion->effective_date)->format('d M Y') .
            "\n📝 Status: Pending Approval";

            event(new ResortNotificationEvent(Common::nofitication(
                $this->resort->resort_id, // Make sure `resort_id` exists on the `meetings` table
                10,
                'Promotion Request Notification',
                $msg,
                0,
                $approver['approver']->id,
                'People'
            )));
        });

        return response()->json([
            'success' => true,
            'message' => 'Promotion request submitted.',
            'redirect_url' => route('people.promotion.list')
        ]);
    }

    public function list(Request $request)
    {        
        $page_title = "Promotion List";
        $resort_id = $this->resort->resort_id;
        $positions = ResortPosition::where('resort_id',$resort_id)->where('status','active')->get();
        $departments = ResortDepartment::where('resort_id',$resort_id)->where('status','active')->get();
        $loggedInEmployee = $this->resort->getEmployee;
        $loggedInUserId = $loggedInEmployee->id;
        $rank = config('settings.Position_Rank');
        $current_rank = $loggedInEmployee->rank ?? null;
        $available_rank = $rank[$current_rank] ?? '';
        $isHR = ($available_rank === "HR");
        $isGM = ($available_rank === "GM");

        if ($request->ajax()) {
            $promotions = EmployeePromotion::with([
                'employee.position',
                'employee.department',
                'employee.resortAdmin',
                'currentPosition',
                'newPosition',
                'approvals'
            ])->where('resort_id',$this->resort->resort_id)->select('*');

            // HR sees all statuses; others only limited
            // if (!$isHR) {
            //     $promotions->whereIn('status', ['Pending', 'On Hold']);
            // }

            // Filters
            if ($request->filled('department_id')) {
                $promotions->whereHas('employee', function ($q) use ($request) {
                    $q->where('Dept_id', $request->department_id);
                });
            }

            if ($request->filled('position_id')) {
                $promotions->whereHas('employee', function ($q) use ($request) {
                    $q->where('Position_id', $request->position_id);
                });
            }

            if ($request->filled('searchTerm')) {
                $promotions->whereHas('employee.resortAdmin', function ($q) use ($request) {
                    $q->where('first_name', 'like', '%' . $request->searchTerm . '%')
                    ->orWhere('last_name', 'like', '%' . $request->searchTerm . '%')
                    ->orWhere('Emp_id', 'like', '%' . $request->searchTerm . '%');
                });
            }

            $loggedInEmployeeId = $this->resort->GetEmployee->id ?? null;
            
            return datatables()->of($promotions)
                ->addColumn('employee_id', fn($row) => '#' . ($row->employee->Emp_id ?? 'N/A'))
                ->addColumn('employee_name', function ($row) {
                    $photo = Common::getResortUserPicture($row->employee->Admin_Parent_id ?? null);
                    $name = $row->employee->resortAdmin->full_name ?? 'N/A';
                    return '
                        <div class="tableUser-block">
                            <div class="img-circle">
                                <img src="' . $photo . '" alt="user">
                            </div>
                            <span class="userApplicants-btn">' . $name . '</span>
                        </div>';
                })
                ->addColumn('current_department', fn($row) => optional($row->currentPosition->department)->name ?? '—')
                ->addColumn('new_department', fn($row) => optional($row->newPosition->department)->name ?? '—')
                ->addColumn('current_position', fn($row) => optional($row->currentPosition)->position_title ?? '—')
                ->addColumn('new_position', fn($row) => optional($row->newPosition)->position_title ?? '—')
                ->addColumn('current_salary', fn($row) => $row->current_salary ?? '—')
                ->addColumn('new_salary', fn($row) => $row->new_salary ?? '—')
                ->addColumn('effective_date', function ($row) {
                    return \Carbon\Carbon::parse($row->effective_date)->format('d M Y');
                })
              ->addColumn('status', function ($row) {
                    // $approval = $row->approvals->first(); // gets the first approval

                    // if (!$approval) {
                    //     return '<span class="badge badge-secondary">No Status</span>';
                    // }

                    return match ($row->status) {
                        'Approved' => '<span class="badge badge-themeSuccess">Approved</span>',
                        'Rejected' => '<span class="badge badge-themeDanger">Rejected</span>',
                        'On Hold'  => '<span class="badge badge-themeSkyblue">On Hold</span>',
                        default    => '<span class="badge badge-themeWarning">Pending</span>',
                    };
                })

                ->addColumn('actions', function ($row) use ($isHR, $loggedInEmployeeId) {
                    if ($isHR) {
                        $detailUrl = route('promotion.details', ['id' => base64_encode($row->id)]);
                        return '<a href="' . $detailUrl . '" class="a-link">View Details</a>';
                    }else{
                        $approvalUrl = route('promotion.approval', ['id' => base64_encode($row->id)]);
                        return '<a href="' . $approvalUrl . '" class="a-link">View Details</a>';
                    }

                    // // For non-HR approvers
                    // if (in_array($row->status, ['Pending', 'On Hold'])) {
                    //     $myApproval = $row->approvals->firstWhere('approved_by', $loggedInEmployeeId);
                    //     if ($myApproval) {
                    //         $approvalUrl = route('promotion.approval', ['id' => base64_encode($row->id)]);
                    //         return '<a href="' . $approvalUrl . '" class="a-link">View Details</a>';
                    //     }
                    // }

                    return ''; // No access or action
                })
                ->rawColumns(['employee_name', 'status', 'effective_date', 'actions'])
                ->make(true);
        }

        return view('resorts.people.promotion.list',compact('page_title','departments','positions'));
    }

    public function getHistory(Request $request,$id = null) 
    {        
        $page_title = "Promotion History";
        $resort_id = $this->resort->resort_id;
        // Accept either from query param or route param
        $employeeId = $request->employee_id ?? ($id ? base64_decode($id) : null);
        $decodedId = $employeeId;
        $employees = Employee::where('resort_id',$resort_id)->where('status','Active')->get();
        if ($request->ajax()) {
            $promotions = EmployeePromotion::with([
                'employee.position',
                'employee.department',
                'employee.resortAdmin',
                'currentPosition',
                'newPosition',
                'approvals'
            ])->where('resort_id',$this->resort->resort_id)->where('status','Approved')->select('*');

            // Filters
            if ($decodedId) {
                $promotions->whereHas('employee', function ($q) use ($decodedId) {
                    $q->where('id', $decodedId);
                });
            }

            return datatables()->of($promotions)
                ->addColumn('employee_id', fn($row) => '#' . ($row->employee->Emp_id ?? 'N/A'))
                ->addColumn('employee_name', function ($row) {
                    $photo = Common::getResortUserPicture($row->employee->Admin_Parent_id ?? null);
                    $name = $row->employee->resortAdmin->full_name ?? 'N/A';
                    return '
                        <div class="tableUser-block">
                            <div class="img-circle">
                                <img src="' . $photo . '" alt="user">
                            </div>
                            <span class="userApplicants-btn">' . $name . '</span>
                        </div>';
                })
                ->addColumn('effective_date', function ($row) {
                    return \Carbon\Carbon::parse($row->effective_date)->format('d M Y');
                })
                ->addColumn('old_position', fn($row) => optional($row->currentPosition)->position_title ?? '—')
                ->addColumn('new_position', fn($row) => optional($row->newPosition)->position_title ?? '—')
                ->addColumn('old_salary', fn($row) => $row->current_salary ?? '—')
                ->addColumn('new_salary', fn($row) => $row->new_salary ?? '—')
                ->addColumn('old_jd', function ($row) {
                    $employee = $row->employee;
                    $oldPositionId = optional($row->currentPosition)->id;
                    $url = $oldPositionId ? route('job.description.by.position', ['posId' => $oldPositionId]) : null;
                    return $url ? '<a href="' . $url . '" class="a-link" target="_blank">View Job Description</a>' : '—';
                })
                ->addColumn('new_jd', function ($row) {
                    $newPositionId = optional($row->newPosition)->id;
                    $url = $newPositionId ? route('job.description.by.position', ['posId' => $newPositionId]) : null;
                    return $url ? '<a href="' . $url . '" class="a-link" target="_blank">View Job Description</a>' : '—';
                })
                ->addColumn('old_benifit_grid', function ($row) {
                    $employee = $row->employee;
                    $url = $employee->benefit_grid_level ? route('benefit.grid.view', ['level' => $employee->benefit_grid_level]) : null;
                    return $url ? '<a href="' . $url . '" class="a-link" target="_blank">View Benefit Grid</a>' : '—';
                })
                ->addColumn('new_benifit_grid', function ($row) {
                    $url = $row->updated_benefit_grid ? route('benefit.grid.view', ['level' => $row->updated_benefit_grid]) : null;
                    return $url ? '<a href="' . $url . '" class="a-link" target="_blank">View Benefit Grid</a>' : '—';
                })
                ->rawColumns(['employee_name', 'status', 'effective_date', 'old_jd', 'new_jd', 'old_benifit_grid', 'new_benifit_grid'])
                ->make(true);
        }

        return view('resorts.people.promotion.history',compact('page_title','employees','decodedId'));
    }

    public function getPosDetails(Request $request){
        $resort_id = $this->resort->resort_id;
        $position = ResortPosition::where('id', $request->position_id)->first();
        $emp_grade = Common::getEmpGrade($position->Rank);
        // dd($emp_grade);
        return response()->json([
            "success" => true,
            "data" => [
                "benefit_grid_level" => $emp_grade ?? "N/A",
                "job_desc_url" => $request->position_id ? route("job.description.by.position", ['posId' => $request->position_id]) : null ,         
            ]
        ]);
    }

    public function approval($id){
        $page_title = "Promotion Review";
        $promotionId = base64_decode($id);
        $promotion = EmployeePromotion::with([
            'employee.position',
            'employee.department',
            'employee.resortAdmin',
            'currentPosition', 
            'newPosition',
            'approvals',
            'createdBy.GetEmployee', 
            'modifiedBy'
        ])->where('id',$promotionId)->first();   
        // dd($promotion->createdBy->GetEmployee->position->position_title);  
        return view('resorts.people.promotion.approval',compact('page_title','promotion'));


    }

    public function handlePromotionApproval(Request $request, $id, $action)
    {
        $promotion = EmployeePromotion::with(['approvals', 'employee'])->findOrFail($id);
        $comments = $request->input('comments', null);
        $currentEmployee = $this->resort->GetEmployee; // Assuming current logged-in employee
        $actionName = $action;
        $hr = Employee::where('resort_id',$this->resort->resort_id)->where('Admin_Parent_id',$promotion->created_by)->first();

        // Get current approval record
        $currentApproval = $promotion->approvals()
            ->where('approved_by', $currentEmployee->id)
            ->whereIn('status', ['Pending', 'On Hold'])
            ->first();

        if (!$currentApproval) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized or have already finalized your action on this promotion.',
            ], 403);
        }

        // Ensure previous approvers (lower rank) have approved
        $pendingBefore = $promotion->approvals()
            ->where('approval_rank', '<', $currentApproval->approval_rank)
            ->get();

        foreach ($pendingBefore as $previousApproval) {
            if ($previousApproval->status !== 'Approved') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You cannot act until previous approvers have approved this promotion.',
                ], 403);
            }
        }

        // Update current approval
        $currentApproval->update([
            'status' => $actionName,
            'remarks' => $comments,
            'approved_at' => now(),
        ]);

        // Handle Approved
        if ($actionName === 'Approved') {
            $pendingCount = $promotion->approvals()->where('status', 'Pending')->count();

            if ($pendingCount === 0) {
                // Final approval – update main promotion table
                $promotion->status = 'Approved';
                $promotion->save();
            }

            if ($hr) {
                event(new ResortNotificationEvent(Common::nofitication(
                    $this->resort->resort_id,
                    10,
                    'Employee Promotion Finalized',
                    "📢 Promotion for " . $promotion->employee->resortAdmin->full_name . " has been approved",
                    0,
                    $hr->id,
                    'People'
                )));
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Promotion Approved Successfully.',
                'redirect_url' => route('people.promotion.list')
            ]);
        }

        // Handle Rejected
        if ($actionName === 'Rejected') {
            $promotion->status = 'Rejected';
            $promotion->save();

            if ($hr) {
                event(new ResortNotificationEvent(Common::nofitication(
                    $this->resort->resort_id,
                    10,
                    'Employee Promotion Rejected',
                    "📢 Promotion for " . $promotion->employee->resortAdmin->full_name . " has been rejected.",
                    0,
                    $hr->id,
                    'People'
                )));
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Promotion Rejected.',
                'redirect_url' => route('people.promotion.list')
            ]);
        }

        // Handle On Hold
        if ($actionName === 'On Hold') {
            $promotion->status = 'On Hold';
            // Save follow-up date
            if ($request->has('followup_date')) {
                $formattedFollowupDate = $request->followup_date ? Carbon::createFromFormat('d/m/Y', $request->followup_date)->format('Y-m-d') : null;
                $promotion->follow_up_date = $formattedFollowupDate;
            }
            $promotion->save();
            if ($hr) {
                event(new ResortNotificationEvent(Common::nofitication(
                    $this->resort->resort_id,
                    10,
                    'Employee Promotion put On Hold',
                    "📢 Promotion for " . $promotion->employee->resortAdmin->full_name . " has been put on hold till date ".$promotion->follow_up_date,
                    0,
                    $hr->id,
                    'People'
                )));
            }
            return response()->json([
                'status' => 'success',
                'message' => 'Promotion put on hold.',
                'redirect_url' => route('people.promotion.list')
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Invalid action.',
        ]);
    }

    public function detail($id){
        $page_title = "Promotion Details";
        $promotionId = base64_decode($id);
        $promotion = EmployeePromotion::with([
            'employee.position',
            'employee.department',
            'employee.resortAdmin',
            'currentPosition', 
            'newPosition',
            'approvals',
            'createdBy.GetEmployee', 
            'modifiedBy'
        ])->where('id',$promotionId)->first();   

        // dd($promotion->createdBy->GetEmployee->position->position_title);  
        return view('resorts.people.promotion.detail',compact('page_title','promotion'));
    }
    
    public function sendPromotionLetter(Request $request)
    {
        $promotion = EmployeePromotion::with([
            'employee.position',
            'employee.department',
            'employee.resortAdmin',
            'currentPosition', 
            'newPosition',
            'approvals',
            'createdBy.GetEmployee', 
            'modifiedBy'
        ])->where('id',$request->promotionId)->first();   
            
        $type = $request->type;
        $resort = Resort::findOrFail($promotion->resort_id);

        // Generate content
        $template = ProbationLetterTemplate::where('resort_id', $promotion->resort_id)
        ->where('type', $type)
        ->first();

        if (!$template) {
            return response()->json(['error' => 'Template not found for this resort and type.'], 404);
        }
        $placeholders = [
            '{{employee_name}}'       => (string) optional($promotion->employee->resortAdmin)->full_name,
            '{{employee_code}}'       => (string) $promotion->employee->Emp_id,
            '{{position_title}}'      => (string) optional($promotion->currentPosition)->position_title,
            '{{Department_title}}'   => (string) optional($promotion->currentPosition->department)->name,
            '{{resort_name}}'         => (string) $resort->resort_name,
            '{{date}}'                => now()->format('d M Y'),
            '{{employment_type}}'     => (string) $promotion->employee->employment_type,
            '{{new_position}}'         => (string) optional($promotion->newPosition)->position_title,
            '{{current_department}}' => (string) optional($promotion->currentPosition->department)->name,
            '{{new_department}}' => (string) optional($promotion->newPosition->department)->name,
            '{{new_level}}' => (string) $promotion->new_level,
            '{{effective_date}}' => (string) Carbon::parse($promotion->effective_date)->format('d M Y'),
        ];

        $letterContent = strtr($template->content, $placeholders);

        // Optionally, generate PDF
        $pdf = Pdf::loadHTML($letterContent);
        $pdfPath = 'letters/promotion_' . $type . '_' . $promotion->employee->id . '.pdf';
        Storage::put($pdfPath, $pdf->output());


        // Update employee
        $promotion->letter_dispatched = 'Yes';
      
        $promotion->save();

        // Send email
        Mail::to($promotion->employee->resortAdmin->email)->send(new PromotionLetterMail($promotion, $pdfPath, $type, $resort));

        return response()->json(['message' => 'Letter sent successfully.']);
    }

    public function confirmPromotion(Request $request)
    {
        $promotion = EmployeePromotion::with(['employee.position',
            'employee.department',
            'employee.resortAdmin',
            'currentPosition', 
            'newPosition',
            'approvals',
            'createdBy.GetEmployee', 
            'modifiedBy'])->findOrFail($request->promotionId);
        // dd( $promotion->newPosition->department->division->id);
        $employee = Employee::findOrFail($promotion->employee_id);
        $employee->Dept_id = $promotion->newPosition->department->id;
        $employee->Position_id = $promotion->newPosition->id;
        $employee->division_id = $promotion->newPosition->department->division_id;
        $employee->rank = $promotion->new_level;
        $employee->basic_salary = $promotion->new_salary;
        $employee->incremented_date = $promotion->effective_date;
        $employee->benefit_grid_level = $promotion->updated_benefit_grid;
        $employee->save();


        // Send notification to employee
        event(new ResortNotificationEvent(Common::nofitication(
            $this->resort->resort_id,
            10,
            'Promotion Confirmation',
            'Your promotion has been confirmed.',
            0,
            $promotion->employee_id,
            'People'
        )));

        return response()->json(['message' => 'Promotion confirmed successfully.']);
    }

    public function exportPDF()
    {
        $promotions = EmployeePromotion::with(['employee.position',
            'employee.department',
            'employee.resortAdmin',
            'currentPosition', 
            'newPosition',
            'approvals',
            'createdBy.GetEmployee', 
            'modifiedBy'])->where('resort_id',$this->resort->resort_id)->where('status','Approved')->get();

        $pdf = Pdf::loadView('resorts.people.promotion.promotion_pdf', compact('promotions'));
        return $pdf->download('promotion-history.pdf');
    }

    public function exportExcel()
    {
        return Excel::download(new PromotionHistoryExport, 'promotion-history.xlsx');
    }

    public function inlineUpdate(Request $request)
    {
        try {
            $promotion = EmployeePromotion::findOrFail($request->id);
            $oldSalary = $promotion->current_salary;
            $newSalary = $request->new_salary;

            // Calculate increment amount and percent
            $salaryIncrementAmount = $newSalary - $oldSalary;
            $salaryIncrementPercent = $oldSalary > 0 ? ($salaryIncrementAmount / $oldSalary) * 100 : 0;

            $promotion->new_position_id = $request->position_id;
            $promotion->salary_increment_amount = $salaryIncrementAmount;
            $promotion->salary_increment_percent = round($salaryIncrementPercent, 2);
            $promotion->new_salary = $newSalary;

            if ($request->effective_date) {
                try {
                    $date = \Carbon\Carbon::createFromFormat('d/m/Y', $request->effective_date);
                } catch (\Exception $e1) {
                    try {
                        $date = \Carbon\Carbon::parse($request->effective_date);
                    } catch (\Exception $e2) {
                        return response()->json(['success' => false, 'message' => 'Invalid effective date format']);
                    }
                }

                $promotion->effective_date = $date->format('Y-m-d');
            }

            $promotion->save();

            return response()->json([
                'success' => true,
                'message' => 'Promotion updated successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function GetEmployeeWiseFilterData(Request $request)
    {
        try {
   
            $filters = $request->filters ?? [];
            $excludeIds = [];

            if (in_array('probation', $filters)) {
                $probationEmployees = Employee::where('employment_type', 'Probationary')
                    ->where('resort_id', $this->resort->resort_id)
                    ->pluck('id')->toArray();
                $excludeIds = array_merge($excludeIds, $probationEmployees);
            }

            if (in_array('disciplinary', $filters)) {
                $disciplinaryEmployees = Employee::whereHas('disciplinarySubmits', function ($q) {
                    $q->where('status', 'In_Review')                
                    ->where('resort_id', $this->resort->resort_id); // example condition
                })->pluck('id')->toArray();
                $excludeIds = array_merge($excludeIds, $disciplinaryEmployees);
            }

            if(in_array('promoted', $filters)){
                $promotionEmployees = EmployeePromotion::where('status', 'Approved')                  
                ->where('effective_date', '>=', now()->subMonth(6))
                ->where('resort_id', $this->resort->resort_id)
                ->pluck('employee_id')->toArray();
                $excludeIds = array_merge($excludeIds, $promotionEmployees);
            }

            if (in_array('training', $filters)) {
                $trainingScheduleIds = TrainingSchedule::where('resort_id', $this->resort->resort_id)
                    ->where('status', 'ongoing')
                    ->where('end_date', '>=', now())
                    ->pluck('id');

                $trainingEmployees = Employee::whereHas('trainingParticipants', function ($q) use ($trainingScheduleIds) {
                    $q->whereIn('training_schedule_id', $trainingScheduleIds);
                })->pluck('id')->toArray();

                $excludeIds = array_merge($excludeIds, $trainingEmployees);
            }


            $excludeIds = array_unique($excludeIds);

            return response()->json(['exclude_ids' => $excludeIds]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

}