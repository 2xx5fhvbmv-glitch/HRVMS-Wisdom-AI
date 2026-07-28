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
        if(!$this->resort) return;
    }

    public function index()
    {
        $page_title ='Initiate Promotion';
        $resort_id = $this->resort->resort_id;
        $scopedDeptIds = Common::getScopedDepartmentIds();
        $employees = Employee::with(['resortAdmin','position','department'])
            ->where('resort_id',$resort_id)
            ->where('status','Active')
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))
            ->get();
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

        // Server-side vacancy guard — same check the UI surfaces under
        // NEW POSITION. Stops promotion submissions from bypassing JS.
        $newPosition = ResortPosition::where('id', $request->new_position)
            ->where('resort_id', $this->resort->resort_id)
            ->first();
        if ($newPosition) {
            $vacancy = $this->computePositionVacancy(
                (int) $this->resort->resort_id,
                (int) $request->new_position,
                $newPosition
            );
            if (!$vacancy['is_vacant']) {
                return back()
                    ->withInput()
                    ->withErrors(['new_position' => $vacancy['message']]);
            }
        }

        $formattedEffectiveDate = $request->effective_date ? Carbon::createFromFormat('d/m/Y', $request->effective_date)->format('Y-m-d') : null;
        // dd($formattedEffectiveDate);
        // Store promotion request
        
        $JobDescription =  JobDescription::where('Resort_id', $this->resort->resort_id)->where('Position_id', $request->new_position)->first();
        $jd_id = $JobDescription ? $JobDescription->id : null;
        // Snapshot the target-position budgeted salary at submit time so the
        // approval / detail views show the same "exceeds budget" warning HR
        // saw on the form, even if the underlying budget rows change later.
        // We re-compute server-side (don't trust the hidden form field).
        $budgetedSalaryAtSubmit = $newPosition
            ? $this->computeBudgetedSalaryForPosition((int) $this->resort->resort_id, (int) $request->new_position, $newPosition)
            : 0.0;

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
            'budgeted_salary' => $budgetedSalaryAtSubmit,
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
       
        // ── Approval flow — pool-based ────────────────────────────────────
        // Finance Approver Logic: anyone whose rank matches "Finance" OR whose
        // position is ranked Finance OR whose position_title is in the manager
        // titles list. We pick ONE as the nominal `approved_by` on the approval
        // row (DB needs a non-null value), but the action handler accepts ANY
        // member of the pool, and the notification fans out to all of them so
        // Finance HOD doesn't get silently skipped.
        $financeRank   = array_search('Finance', config('settings.Position_Rank')) ?: 7;
        $gmRank        = array_search('GM',      config('settings.Position_Rank')) ?: 8;
        $financeTitles = ['Director of Finance', 'Finance Manager'];

        // Finance dept IDs (Accounting / Finance / etc.) — used to include
        // the dept's HOD (rank=2) and EXCOM (rank=1) in the Finance pool
        // even when their position title isn't DOF / Finance Manager.
        $financeDeptIds = ResortDepartment::where('resort_id', $this->resort->resort_id)
            ->get()
            ->filter(fn($d) => Common::isFinanceDepartment($d->id))
            ->pluck('id');

        $financePool = Employee::with('resortAdmin')
            ->where('resort_id', $this->resort->resort_id)
            ->where(function ($q) use ($financeRank, $financeTitles, $financeDeptIds) {
                $q->where('rank', $financeRank)
                  ->orWhereHas('position', function ($pq) use ($financeRank, $financeTitles) {
                      $pq->where('Rank', $financeRank)
                         ->orWhereIn('position_title', $financeTitles);
                  });
                if ($financeDeptIds->isNotEmpty()) {
                    $q->orWhere(function ($qq) use ($financeDeptIds) {
                        $qq->whereIn('Dept_id', $financeDeptIds)
                           ->whereIn('rank', [1, 2]);
                    });
                }
            })
            ->get();

        $gmPool = Employee::with('resortAdmin')
            ->where('resort_id', $this->resort->resort_id)
            ->where(function ($q) use ($gmRank) {
                $q->where('rank', $gmRank)
                  ->orWhereHas('position', function ($pq) use ($gmRank) {
                      $pq->where('Rank', $gmRank);
                  });
            })
            ->get();

        // HOD Approver Logic — the EMPLOYEE'S current dept HOD/EXCOM is the
        // FIRST gate: they justify the promotion request (with comments)
        // before Finance / GM can act. Same pool-fan-out pattern so EXCOM or
        // HOD can act for each other, and everyone in the pool is notified.
        $hodRank   = array_search('HOD',   config('settings.Position_Rank')) ?: 2;
        $excomRank = array_search('EXCOM', config('settings.Position_Rank')) ?: 1;
        $employeeDeptId = (int) optional($promotion->employee)->Dept_id;
        $employeeBeingPromotedId = (int) $promotion->employee_id;
        $initiatorAdminId = (int) $promotion->created_by;

        $hodPool = collect();
        if ($employeeDeptId > 0) {
            $hodPool = Employee::with('resortAdmin')
                ->where('resort_id', $this->resort->resort_id)
                ->where('Dept_id', $employeeDeptId)
                ->where('status', 'Active')
                ->whereIn('rank', [$hodRank, $excomRank])
                ->get();
        }

        // Self-approval guard — always drop the employee being promoted.
        $hodPool     = $hodPool    ->reject(fn($e) => (int) $e->id === $employeeBeingPromotedId)->values();
        $financePool = $financePool->reject(fn($e) => (int) $e->id === $employeeBeingPromotedId)->values();
        $gmPool      = $gmPool     ->reject(fn($e) => (int) $e->id === $employeeBeingPromotedId)->values();

        // Drop the initiator from each pool — but only when at least one
        // OTHER candidate remains. When the initiator is the ONLY HOD of the
        // employee's dept (a small-dept edge case the user flagged), they
        // need to stay in the pool so the chain has someone to act on the
        // HOD slot. The $canAct gate in approval() also allows the initiator
        // to act on an HOD slot they're assigned to.
        $dropInitiator = function ($pool) use ($initiatorAdminId) {
            $withoutInitiator = $pool->reject(fn($e) => (int) ($e->Admin_Parent_id ?? 0) === $initiatorAdminId)->values();
            return $withoutInitiator->isNotEmpty() ? $withoutInitiator : $pool;
        };

        $hodPool     = $dropInitiator($hodPool);
        $financePool = $dropInitiator($financePool);
        $gmPool      = $dropInitiator($gmPool);

        $promotionApprovalFlow = collect();
        if ($hodPool->isNotEmpty()) {
            $promotionApprovalFlow->push(['approver' => $hodPool->first(), 'rank' => 'HOD', 'pool' => $hodPool]);
        }
        if ($financePool->isNotEmpty()) {
            $promotionApprovalFlow->push(['approver' => $financePool->first(), 'rank' => 'Finance', 'pool' => $financePool]);
        }
        if ($gmPool->isNotEmpty()) {
            $promotionApprovalFlow->push(['approver' => $gmPool->first(), 'rank' => 'GM', 'pool' => $gmPool]);
        }

        // Create approval entries + fan-out notifications.
        $promotionApprovalFlow->each(function ($approver) use ($promotion) {
            EmployeePromotionApproval::create([
                'promotion_id'   => $promotion->id,
                'approved_by'    => $approver['approver']->id,
                'status'         => 'Pending',
                'approval_rank'  => $approver['rank'],
            ]);

            $msg = "📢 New Promotion Request Submitted\n👤 Employee: " . $promotion->employee->resortAdmin->full_name .
            "\n🏢 From: " . optional($promotion->currentPosition)->position_title .
            "\n➡️ To: " . optional($promotion->newPosition)->position_title .
            "\n📅 Effective Date: " . Carbon::parse($promotion->effective_date)->format('d M Y') .
            "\n📝 Status: Pending Approval";

            // Notify every pool member so e.g. Finance HOD sees the request
            // even if first()-picked the DOF for the approval row.
            foreach ($approver['pool'] as $member) {
                event(new ResortNotificationEvent(Common::nofitication(
                    $this->resort->resort_id,
                    10,
                    'Promotion Request Notification',
                    $msg,
                    0,
                    $member->id,
                    'People'
                )));
            }
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
        // Guard against an admin account with no linked employee record —
        // $loggedInEmployee->id threw "property 'id' on null" and 500'd the
        // whole Promotion List page for such users.
        $loggedInUserId = $loggedInEmployee->id ?? null;
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

            // Role-based "my inbox" scoping.
            //
            //   - hasFullDataAccess()   (HR / GM / master-admin / L&D etc.)
            //         → see EVERYTHING. No filter applied.
            //   - Finance approver pool (rank=Finance, DOF/Finance Manager
            //         title, or rank 1/2 in Finance dept)
            //         → see promotions where the Finance approval row is
            //           currently Pending or On Hold (i.e. their action
            //           is needed). PLUS their own dept's HOD-stage inbox
            //           below if they're also a dept HOD/EXCOM.
            //   - Dept HOD / EXCOM (everyone else who reaches this branch)
            //         → see promotions for employees CURRENTLY in their
            //           dept where the HOD approval row is Pending or On
            //           Hold. Once HOD acts, the row drops out of their
            //           list — "my inbox" UX, confirmed by the user.
            //
            // Without this, F&B HOD kept seeing promotions they'd
            // already approved (which had moved on to Finance / GM),
            // and Finance HOD couldn't see cross-dept promotions waiting
            // at the Finance stage.
            $scopedDeptIds = Common::getScopedDepartmentIds();
            $userPromotionRole = $loggedInUserId
                ? $this->getUserPromotionRole($loggedInUserId, $resort_id)
                : null;
            $isFinancePoolMember = ($userPromotionRole === 'Finance');

            if (is_array($scopedDeptIds)) {
                $promotions->where(function ($q) use ($scopedDeptIds, $isFinancePoolMember) {
                    // Dept HOD view: every IN-FLIGHT promotion whose
                    // NEW POSITION belongs to my dept(s). Scoping by the
                    // destination dept (newPosition.dept_id) — not the
                    // employee's current dept — matches the user's mental
                    // model: a promotion belongs to the team it lands
                    // in, not the team the employee is leaving. Once
                    // the promotion is finalised (Approved / Rejected)
                    // it drops out so the list stays an active inbox.
                    $q->where(function ($deptQ) use ($scopedDeptIds) {
                        $deptQ->whereHas('newPosition', function ($pq) use ($scopedDeptIds) {
                                $pq->whereIn('dept_id', $scopedDeptIds ?: [0]);
                            })
                            ->whereIn('status', ['Pending', 'On Hold']);
                    });

                    // Finance pool inbox: cross-dept visibility for any
                    // promotion where the Finance stage is Pending /
                    // On Hold. Finance approver pool is a horizontal
                    // bench — they act regardless of which dept the
                    // employee belongs to.
                    if ($isFinancePoolMember) {
                        $q->orWhereHas('approvals', function ($aq) {
                            $aq->where('approval_rank', 'Finance')
                               ->whereIn('status', ['Pending', 'On Hold']);
                        });
                    }
                });
            }

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
                    // For a still-Pending promotion, show WHICH stage is
                    // currently waiting (e.g. "Pending — GM") so HR can tell
                    // at a glance whether HOD or Finance or GM is the
                    // bottleneck. approvals are persisted in chain order
                    // (smallest id = earliest stage).
                    if ($row->status === 'Pending') {
                        $nextStage = $row->approvals
                            ->where('status', 'Pending')
                            ->sortBy('id')
                            ->first();
                        $stageLabel = $nextStage->approval_rank ?? null;
                        $label = $stageLabel
                            ? "Pending — {$stageLabel} approval"
                            : 'Pending';
                        return '<span class="badge badge-themeWarning">' . e($label) . '</span>';
                    }

                    return match ($row->status) {
                        'Approved' => '<span class="badge badge-themeSuccess">Approved</span>',
                        'Rejected' => '<span class="badge badge-themeDanger">Rejected</span>',
                        'On Hold'  => '<span class="badge badge-themeSkyblue">On Hold</span>',
                        default    => '<span class="badge badge-themeWarning">' . e($row->status) . '</span>',
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
        $scopedDeptIds = Common::getScopedDepartmentIds();
        $employees = Employee::where('resort_id',$resort_id)
            ->where('status','Active')
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))
            ->get();
        if ($request->ajax()) {
            $promotions = EmployeePromotion::with([
                'employee.position',
                'employee.department',
                'employee.resortAdmin',
                'currentPosition',
                'newPosition',
                'approvals'
            ])->where('resort_id',$this->resort->resort_id)->where('status','Approved')->select('*');

            // Department scope — HOD / MGR / SUP only see their own dept's
            // promotions; GM / HR / admins get full visibility (returns null).
            if (is_array($scopedDeptIds)) {
                $promotions->whereHas('employee', function ($q) use ($scopedDeptIds) {
                    $q->whereIn('Dept_id', $scopedDeptIds);
                });
            }

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
        $position = ResortPosition::where('id', $request->position_id)
            ->where('resort_id', $resort_id)
            ->first();

        if (!$position) {
            return response()->json([
                "success" => false,
                "message" => "Position not found.",
            ], 404);
        }

        $emp_grade = Common::getEmpGrade($resort_id, $position->Rank);

        // Vacancy check — mirror TransferController::isTargetPositionVacant().
        // Primary source: manning_responses + position_monthly_data (Workforce
        // Planning headcount); compute filled in real time from Active
        // employees (exclude those past their last_working_day). Fall back to
        // resort_positions.no_of_positions when no manning row exists.
        $vacancyInfo = $this->computePositionVacancy($resort_id, (int) $request->position_id, $position);

        $budgetedSalary = $this->computeBudgetedSalaryForPosition($resort_id, (int) $request->position_id, $position);

        return response()->json([
            "success" => true,
            "data" => [
                "benefit_grid_level" => $emp_grade ?? "N/A",
                "job_desc_url" => $request->position_id ? route("job.description.by.position", ['posId' => $request->position_id]) : null,
                "position_title" => $position->position_title,
                "is_vacant" => $vacancyInfo['is_vacant'],
                "vacant_count" => $vacancyInfo['vacant_count'],
                "filled_count" => $vacancyInfo['filled_count'],
                "budgeted_headcount" => $vacancyInfo['budgeted_headcount'],
                "vacancy_message" => $vacancyInfo['message'],
                "budgeted_salary" => $budgetedSalary,
            ]
        ]);
    }

    /**
     * The highest budgeted monthly basic salary for a position in this resort.
     * Picks the max of (proposed-if-set, else current) across every salary
     * source for that position — active employees, vacant rows, and any
     * per-month overrides. Used by the promotion form to flag "salary
     * exceeds budget" before submit.
     */
    private function computeBudgetedSalaryForPosition(int $resortId, int $positionId, $position): float
    {
        // Delegates to the shared Common helper so Promotion + Salary
        // Increment (and any future module) compute budget the same way.
        return \App\Helpers\Common::computeBudgetedSalaryForPosition($resortId, $positionId, $position);
    }

    /**
     * Resolve the budgeted headcount, filled count, and vacancy for a position
     * using the same precedence the Transfer flow uses so promotion + transfer
     * agree on availability.
     */
    private function computePositionVacancy(int $resortId, int $positionId, $position): array
    {
        $today = \Carbon\Carbon::today()->toDateString();
        $activeFilter = function ($q) use ($today) {
            $q->whereNull('last_working_day')
              ->orWhereDate('last_working_day', '>', $today);
        };

        $manningYear = (int) (\DB::table('manning_responses')
            ->where('resort_id', $resortId)
            ->where('year', now()->year)
            ->exists()
            ? now()->year
            : \DB::table('manning_responses')->where('resort_id', $resortId)->max('year'));

        $budgeted = null;
        if ($manningYear) {
            $seat = \DB::table('position_monthly_data as pmd')
                ->join('manning_responses as mr', 'mr.id', '=', 'pmd.manning_response_id')
                ->where('mr.resort_id', $resortId)
                ->where('mr.year', $manningYear)
                ->where('pmd.position_id', $positionId)
                ->selectRaw('MAX(pmd.headcount) as budgeted')
                ->first();
            if ($seat && $seat->budgeted !== null) {
                $budgeted = (int) $seat->budgeted;
            }
        }
        if ($budgeted === null) {
            $budgeted = max(1, (int) ($position->no_of_positions ?? 0));
        }

        $filled = Employee::where('resort_id', $resortId)
            ->where('Position_id', $positionId)
            ->where('status', 'Active')
            ->where($activeFilter)
            ->count();

        $vacant = max(0, $budgeted - $filled);
        $isVacant = $vacant > 0;

        return [
            'is_vacant' => $isVacant,
            'vacant_count' => $vacant,
            'filled_count' => $filled,
            'budgeted_headcount' => $budgeted,
            'message' => $isVacant
                ? 'Position has '.$vacant.' vacant seat(s) ('.$filled.'/'.$budgeted.' filled).'
                : 'Position is fully occupied ('.$filled.'/'.$budgeted.' filled). No vacant seat available.',
        ];
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
            // Eager-load approver name + position so the Approval History
            // section can render "who acted" + "their role" without N+1.
            'approvals.approver.resortAdmin',
            'approvals.approver.position',
            'approvals.approver.department',
            'createdBy.GetEmployee',
            'modifiedBy'
        ])->where('id',$promotionId)->first();

        // Live fallback for the budget-exceed warning — promotions submitted
        // before the budgeted_salary column existed have NULL there, so the
        // warning never fires for them. Re-compute against today's budget so
        // approvers still see the warning. Only fall back when truly empty.
        if ($promotion
            && empty($promotion->budgeted_salary)
            && $promotion->newPosition
        ) {
            $promotion->budgeted_salary = $this->computeBudgetedSalaryForPosition(
                (int) $promotion->resort_id,
                (int) $promotion->new_position_id,
                $promotion->newPosition
            );
        }

        // Decide whether the logged-in user is allowed to actually
        // Approve / Reject / On-Hold this promotion. Hidden in the view when
        // $canAct is false. Rules:
        //   1. User must own an actionable approval slot (direct, delegate,
        //      Finance/GM pool, or HOD-of-same-dept pool).
        //   2. All EARLIER approvers in the chain must already be Approved.
        //   3. INITIATOR generally blocked from acting on their own request,
        //      EXCEPT when the only slot they can act on is an HOD slot
        //      assigned to them — small-dept edge case where the initiator
        //      is also the only HOD of the employee's dept.
        //   4. Finalised promotions (Approved/Rejected) never show buttons.
        $canAct = false;
        if ($promotion
            && !in_array($promotion->status, ['Approved', 'Rejected'], true)
            && Auth::guard('resort-admin')->check()
        ) {
            $currentAdminId  = (int) Auth::guard('resort-admin')->user()->id;
            $currentEmployee = optional($this->resort)->GetEmployee;
            $isInitiator     = ((int) $promotion->created_by === $currentAdminId);

            if ($currentEmployee) {
                $slot = $this->findActionableApproval($promotion, $currentEmployee);
                if ($slot && $this->earlierApprovalsAllApproved($promotion, $slot->id)) {
                    if (!$isInitiator) {
                        $canAct = true;
                    } else {
                        // Initiator allowed to act ONLY when they're assigned
                        // to an HOD slot (i.e. they're also the dept HOD and
                        // there's no other HOD to take the action).
                        if ($slot->approval_rank === 'HOD'
                            && (int) $slot->approved_by === (int) $currentEmployee->id) {
                            $canAct = true;
                        }
                    }
                }
            }
        }

        return view('resorts.people.promotion.approval', compact('page_title', 'promotion', 'canAct'));
    }

    public function handlePromotionApproval(Request $request, $id, $action)
    {
        // The approval-inbox row generates this URL with base64_encode($id)
        // (see ApprovalController@getApprovedRequests, ~:567); the
        // approval detail page sends the raw integer. Accept both:
        // try base64_decode and use it when the result is numeric,
        // otherwise fall back to the raw value. Was 404ing from the
        // inbox because findOrFail('Nw==') has no matching row.
        $resolvedId = $id;
        if (!ctype_digit((string) $id)) {
            $decoded = base64_decode((string) $id, true);
            if ($decoded !== false && ctype_digit($decoded)) {
                $resolvedId = $decoded;
            }
        }
        $promotion = EmployeePromotion::with(['approvals', 'employee'])->findOrFail($resolvedId);
        $comments = $request->input('comments', null);
        $currentEmployee = $this->resort->GetEmployee; // Assuming current logged-in employee
        // Eager-load relations the actor descriptor needs ($actorLabel below
        // reads resortAdmin->full_name, position->position_title, department->name).
        if ($currentEmployee) {
            $currentEmployee->loadMissing(['resortAdmin', 'position', 'department']);
        }
        $actionName = $action;
        $hr = Employee::where('resort_id',$this->resort->resort_id)->where('Admin_Parent_id',$promotion->created_by)->first();

        // Get current approval record (direct or via delegation)
        $currentApproval = $promotion->approvals()
            ->where('approved_by', $currentEmployee->id)
            ->whereIn('status', ['Pending', 'On Hold'])
            ->first();

        $delegateComment = '';
        // Fallback chain (delegate → Finance/GM pool → HOD-of-same-dept pool)
        // is the same one the approval page's $canAct uses, so the UI and the
        // server agree on who is allowed to act.
        if (!$currentApproval) {
            $slot = $this->findActionableApproval($promotion, $currentEmployee);
            if ($slot) {
                $currentApproval = $slot;
                // Mark as delegate-comment only when this user isn't the
                // nominal approved_by on the row (true for delegation AND
                // pool-fallback).
                if ((int) $slot->approved_by !== (int) $currentEmployee->id) {
                    $delegateComment = ' (Acted by delegate)';
                    // Re-assign so the audit trail names who actually acted.
                    $currentApproval->approved_by = $currentEmployee->id;
                }
            }
        }

        if (!$currentApproval) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized or have already finalized your action on this promotion.',
            ], 403);
        }

        // Ensure earlier approvers in the chain have approved first.
        // approval_rank is a STRING (e.g. 'HOD', 'Finance', 'GM') so
        // alphabetical `<` doesn't reflect chain order. We persist the
        // approvers in the order they were pushed at submit time, so the
        // primary-key `id` is the source of truth for chain order.
        $pendingBefore = $promotion->approvals()
            ->where('id', '<', $currentApproval->id)
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
            'remarks' => ($comments ?? '') . $delegateComment,
            'approved_at' => now(),
        ]);

        // Actor descriptor for HR notifications — "Ankit (HOD of Finance)",
        // "Ankit (Finance Manager)", etc. Falls back to just the name if the
        // employee record has no position / department row.
        $actorName = optional(optional($currentEmployee)->resortAdmin)->full_name ?? 'Approver';
        $actorRankLabel = config('settings.Position_Rank')[$currentEmployee->rank] ?? null;
        $actorDeptName  = optional($currentEmployee->department)->name;
        $actorPosTitle  = optional($currentEmployee->position)->position_title;
        if ($actorRankLabel && $actorDeptName) {
            $actorDescriptor = "{$actorRankLabel} of {$actorDeptName}";
        } elseif ($actorPosTitle) {
            $actorDescriptor = $actorPosTitle;
        } else {
            $actorDescriptor = '';
        }
        $actorLabel = $actorName . ($actorDescriptor ? " ({$actorDescriptor})" : '');
        $employeeName = $promotion->employee->resortAdmin->full_name ?? 'Employee';

        // Handle Approved
        if ($actionName === 'Approved') {
            $pendingCount = $promotion->approvals()->where('status', 'Pending')->count();

            if ($pendingCount === 0) {
                // Final approval – mark the promotion Approved.
                $promotion->status = 'Approved';
                $promotion->save();

                // If the effective date is already today (or past), apply the
                // promotion to the employee + fan out the day-of notifications
                // immediately. Otherwise the daily promotions:notify-effective
                // scheduler will pick it up on the effective date.
                $today = \Carbon\Carbon::today()->toDateString();
                if ($promotion->effective_date && \Carbon\Carbon::parse($promotion->effective_date)->toDateString() <= $today) {
                    try {
                        self::dispatchEffectiveDateNotifications($promotion);
                    } catch (\Throwable $e) {
                        \Log::error('Promotion apply-on-final-approval failed for #' . $promotion->id . ': ' . $e->getMessage());
                    }
                } else {
                    // Future effective date — at least notify the employee +
                    // dept HOD/EXCOM that the promotion has been finalised so
                    // they know to expect the change on the effective date.
                    self::dispatchFinalApprovedNotifications($promotion);
                }
            }

            if ($hr) {
                // "Finalized" wording only when ALL stages are done. While
                // intermediate stages are still pending we send a more
                // accurate "stage approved" notification — HR was getting
                // mis-led into thinking the promotion was complete after the
                // first approver clicked Approve.
                if ($pendingCount === 0) {
                    $title = 'Promotion Finalized';
                    $body  = "📢 Promotion for {$employeeName} has been fully approved. Final approval by {$actorLabel}.";
                } else {
                    $stageLabel = $currentApproval->approval_rank ?? 'Stage';
                    $title = 'Promotion Stage Approved';
                    $body  = "📢 Promotion for {$employeeName} — {$stageLabel} approved by {$actorLabel}. Awaiting next stage.";
                }
                event(new ResortNotificationEvent(Common::nofitication(
                    $this->resort->resort_id,
                    10,
                    $title,
                    $body,
                    0,
                    $hr->id,
                    'People'
                )));
            }

            // Notify the NEXT stage's approver pool so they know it's now
            // their turn — previously only HR was pinged when a stage
            // approved, so Finance HOD / GM had to discover via the list.
            // Find the first still-Pending approval row (smallest id =
            // earliest stage in the chain) and fan out to that role's pool.
            if ($pendingCount > 0) {
                $nextApproval = $promotion->approvals()
                    ->where('status', 'Pending')
                    ->orderBy('id')
                    ->first();
                if ($nextApproval) {
                    $nextStagePool = $this->buildPromotionApproverPool(
                        (string) $nextApproval->approval_rank,
                        (int) $this->resort->resort_id,
                        $promotion
                    );
                    $stageBody = "📢 Promotion for {$employeeName} is now awaiting your {$nextApproval->approval_rank} approval. " .
                                 "Previous stage approved by {$actorLabel}.";
                    foreach ($nextStagePool as $member) {
                        if (!$member || empty($member->id)) continue;
                        // Don't ping the employee being promoted, even if
                        // they happen to be in the pool.
                        if ((int) $member->id === (int) $promotion->employee_id) continue;
                        event(new ResortNotificationEvent(Common::nofitication(
                            $this->resort->resort_id,
                            10,
                            'Promotion Awaiting Your Approval',
                            $stageBody,
                            0,
                            $member->id,
                            'People'
                        )));
                    }
                }
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
                $reason = trim((string) $comments) !== '' ? " Reason: {$comments}" : '';
                event(new ResortNotificationEvent(Common::nofitication(
                    $this->resort->resort_id,
                    10,
                    'Promotion Rejected',
                    "📢 Promotion for {$employeeName} has been rejected by {$actorLabel}.{$reason}",
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
                $reason = trim((string) $comments) !== '' ? " Reason: {$comments}" : '';
                $tillDate = $promotion->follow_up_date
                    ? \Carbon\Carbon::parse($promotion->follow_up_date)->format('d M Y')
                    : '—';
                event(new ResortNotificationEvent(Common::nofitication(
                    $this->resort->resort_id,
                    10,
                    'Promotion On Hold',
                    "📢 Promotion for {$employeeName} has been put on hold by {$actorLabel} till {$tillDate}.{$reason}",
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
            'approvals.approver.resortAdmin',
            'approvals.approver.position',
            'approvals.approver.department',
            'createdBy.GetEmployee',
            'modifiedBy'
        ])->where('id',$promotionId)->first();

        // Live fallback so the budget-exceed warning fires even on promotions
        // submitted before the budgeted_salary column existed.
        if ($promotion
            && empty($promotion->budgeted_salary)
            && $promotion->newPosition
        ) {
            $promotion->budgeted_salary = $this->computeBudgetedSalaryForPosition(
                (int) $promotion->resort_id,
                (int) $promotion->new_position_id,
                $promotion->newPosition
            );
        }

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

    /**
     * Return the approver pool (Employee collection) for a given approval
     * stage on a promotion. Mirrors the pool-build logic in initiate()
     * (lines ~181-218) so the people notified on stage-approved are the
     * same set that was eligible at initiate time.
     *
     *  - 'HOD'     → employees in the SUBJECT's current dept with rank
     *                HOD (2) or EXCOM (1)
     *  - 'Finance' → Finance pool (rank=Finance, DOF/Finance Manager
     *                titles, or rank 1/2 in Finance dept)
     *  - 'GM'      → rank=GM (8) or position.Rank=GM
     */
    private function buildPromotionApproverPool(string $rank, int $resortId, ?EmployeePromotion $promotion = null)
    {
        $hodRank       = array_search('HOD',     config('settings.Position_Rank')) ?: 2;
        $excomRank     = array_search('EXCOM',   config('settings.Position_Rank')) ?: 1;
        $financeRank   = array_search('Finance', config('settings.Position_Rank')) ?: 7;
        $gmRank        = array_search('GM',      config('settings.Position_Rank')) ?: 8;
        $financeTitles = ['Director of Finance', 'Finance Manager'];

        if ($rank === 'Finance') {
            $financeDeptIds = ResortDepartment::where('resort_id', $resortId)
                ->get()
                ->filter(fn($d) => Common::isFinanceDepartment($d->id))
                ->pluck('id');

            return Employee::with('resortAdmin')
                ->where('resort_id', $resortId)
                ->where('status', 'Active')
                ->where(function ($q) use ($financeRank, $financeTitles, $financeDeptIds) {
                    $q->where('rank', $financeRank)
                      ->orWhereHas('position', function ($pq) use ($financeRank, $financeTitles) {
                          $pq->where('Rank', $financeRank)
                             ->orWhereIn('position_title', $financeTitles);
                      });
                    if ($financeDeptIds->isNotEmpty()) {
                        $q->orWhere(function ($qq) use ($financeDeptIds) {
                            $qq->whereIn('Dept_id', $financeDeptIds)
                               ->whereIn('rank', [1, 2]);
                        });
                    }
                })
                ->get();
        }

        if ($rank === 'GM') {
            return Employee::with('resortAdmin')
                ->where('resort_id', $resortId)
                ->where('status', 'Active')
                ->where(function ($q) use ($gmRank) {
                    $q->where('rank', $gmRank)
                      ->orWhereHas('position', function ($pq) use ($gmRank) {
                          $pq->where('Rank', $gmRank);
                      });
                })
                ->get();
        }

        if ($rank === 'HOD' && $promotion) {
            $deptId = (int) optional($promotion->employee)->Dept_id;
            if ($deptId > 0) {
                return Employee::with('resortAdmin')
                    ->where('resort_id', $resortId)
                    ->where('Dept_id', $deptId)
                    ->where('status', 'Active')
                    ->whereIn('rank', [$hodRank, $excomRank])
                    ->get();
            }
        }

        return collect();
    }

    /**
     * Return 'Finance', 'GM', or null for the given employee — used by
     * handlePromotionApproval so a Finance HOD (or any Finance pool member)
     * can act on a Finance-rank approval slot even when the row was assigned
     * to a different person at submit time.
     */
    private function getUserPromotionRole($empId, $resortId)
    {
        $financeRank   = array_search('Finance', config('settings.Position_Rank')) ?: 7;
        $gmRank        = array_search('GM',      config('settings.Position_Rank')) ?: 8;
        $financeTitles = ['Director of Finance', 'Finance Manager'];

        $emp = Employee::with('position')->where('resort_id', $resortId)->find($empId);
        if (!$emp) return null;

        // Title-based / rank-based match (DOF, Finance Manager, rank=Finance).
        $isFinance = ((int)$emp->rank === (int)$financeRank)
            || ($emp->position && ((int)$emp->position->Rank === (int)$financeRank
                || in_array($emp->position->position_title, $financeTitles, true)));
        // Department-based match — Finance dept's HOD (rank=2) and EXCOM
        // (rank=1) also belong to the Finance approver pool.
        if (!$isFinance
            && in_array((int)$emp->rank, [1, 2], true)
            && Common::isFinanceDepartment($emp->Dept_id ?? null)) {
            $isFinance = true;
        }
        if ($isFinance) return 'Finance';

        $isGm = ((int)$emp->rank === (int)$gmRank)
            || ($emp->position && (int)$emp->position->Rank === (int)$gmRank);
        if ($isGm) return 'GM';

        return null;
    }

    /**
     * Find the approval row this user is authorised to act on for a given
     * promotion, OR null if none. Checks in priority order:
     *   1) direct slot (approvals.approved_by = current employee id)
     *   2) delegation (Common::hasDelegationAuthority)
     *   3) Finance / GM role pool (via getUserPromotionRole)
     *   4) HOD pool of the SAME department as the slot's original approver
     *      (so EXCOM / other HODs can step in for the first()-picked HOD)
     *
     * Returns the EmployeePromotionApproval row when actionable.
     */
    private function findActionableApproval(EmployeePromotion $promotion, $currentEmployee)
    {
        if (!$currentEmployee) return null;

        $resortId = (int) $this->resort->resort_id;
        $approvals = $promotion->approvals()->whereIn('status', ['Pending', 'On Hold'])->get();
        if ($approvals->isEmpty()) return null;

        // 1) Direct slot
        $direct = $approvals->firstWhere('approved_by', $currentEmployee->id);
        if ($direct) return $direct;

        // 2) Delegation
        foreach ($approvals as $a) {
            if (Common::hasDelegationAuthority($currentEmployee->id, $a->approved_by, $resortId)) {
                return $a;
            }
        }

        // 3) Finance / GM role pool
        $userRole = $this->getUserPromotionRole($currentEmployee->id, $resortId);
        if ($userRole) {
            $slot = $approvals->firstWhere('approval_rank', $userRole);
            if ($slot) return $slot;
        }

        // 4) HOD pool fallback — same dept as the slot's nominal approver.
        $hodRank   = array_search('HOD',   config('settings.Position_Rank')) ?: 2;
        $excomRank = array_search('EXCOM', config('settings.Position_Rank')) ?: 1;
        if (in_array((int) $currentEmployee->rank, [$hodRank, $excomRank], true)) {
            foreach ($approvals->where('approval_rank', 'HOD') as $slot) {
                $slotEmp = Employee::find($slot->approved_by);
                if ($slotEmp && (int) $slotEmp->Dept_id === (int) $currentEmployee->Dept_id) {
                    return $slot;
                }
            }
        }

        return null;
    }

    /**
     * True when every approval row with id < $approvalId is already Approved.
     * Used to gate UI buttons + server actions so a later-stage approver
     * can't act before earlier stages have completed.
     */
    private function earlierApprovalsAllApproved(EmployeePromotion $promotion, $approvalId): bool
    {
        $earlier = $promotion->approvals()->where('id', '<', $approvalId)->get(['status']);
        foreach ($earlier as $a) {
            if ($a->status !== 'Approved') return false;
        }
        return true;
    }

    public function GetEmployeeWiseFilterData(Request $request)
    {
        try {
   
            $filters = $request->filters ?? [];
            $excludeIds = [];

            if (in_array('probation', $filters)) {
                // "On probation" = joined within the last 3 months (so they are
                // still inside the standard 3-month probation window). We do
                // NOT rely on employment_type='Probationary' because HR rarely
                // flips that field once probation ends.
                $threeMonthsAgo = now()->subMonths(3)->toDateString();
                $probationEmployees = Employee::where('resort_id', $this->resort->resort_id)
                    ->where(function ($q) use ($threeMonthsAgo) {
                        // Either probation_end_date is still in the future, OR
                        // (no end date set AND joining_date is within 3 months).
                        $q->where('probation_end_date', '>=', now()->toDateString())
                          ->orWhere(function ($q2) use ($threeMonthsAgo) {
                              $q2->whereNull('probation_end_date')
                                 ->where('joining_date', '>=', $threeMonthsAgo);
                          });
                    })
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
                // Only PROBATIONERS are subject to mandatory onboarding —
                // long-tenured employees who never went through these
                // programs shouldn't be excluded. Definition matches the
                // Probation list: probation_end_date still in the future,
                // OR no end date set AND joined within the last 3 months.
                //
                // Per-program completion check uses the same buckets as the
                // Probation details Progress Tracking section:
                //   no training_participants rows                → "Not Started" → EXCLUDE
                //   any 'Pending' in that program                → "In Progress" → EXCLUDE
                //   all sessions held, some 'Absent'             → "Absent"      → EXCLUDE
                //   at least one 'Present' / 'Late' in that prog → "Completed"   → OK
                $resortId = $this->resort->resort_id;
                $today = now()->toDateString();
                $threeMonthsAgo = now()->subMonths(3)->toDateString();

                $requiredProgramIds = \DB::table('probationary_learning_programs')
                    ->where('resort_id', $resortId)
                    ->pluck('program_id')->unique()->all();

                $probationerIds = Employee::where('resort_id', $resortId)
                    ->where('status', 'Active')
                    ->where(function ($q) use ($today, $threeMonthsAgo) {
                        $q->whereDate('probation_end_date', '>=', $today)
                          ->orWhere(function ($q2) use ($threeMonthsAgo) {
                              $q2->whereNull('probation_end_date')
                                 ->whereDate('joining_date', '>=', $threeMonthsAgo);
                          });
                    })
                    ->pluck('id')->all();

                if (!empty($requiredProgramIds) && !empty($probationerIds)) {
                    // (employee_id => [program_id, ...]) for programs they
                    // have at least one Present/Late attendance in. The L&D
                    // module writes attendance to training_attendance, not
                    // training_participants (which stays at 'Pending' on
                    // enrollment), so we LEFT JOIN and check the COALESCEd
                    // effective status — otherwise completed attendees would
                    // still look "not done."
                    $completedByEmp = \DB::table('training_participants as tp')
                        ->join('training_schedules as ts', 'ts.id', '=', 'tp.training_schedule_id')
                        ->leftJoin('training_attendance as ta', function ($j) {
                            $j->on('ta.training_schedule_id', '=', 'tp.training_schedule_id')
                              ->on('ta.employee_id', '=', 'tp.employee_id');
                        })
                        ->where('ts.resort_id', $resortId)
                        ->whereIn('ts.training_id', $requiredProgramIds)
                        ->whereRaw("COALESCE(ta.status, tp.status) IN ('Present','Late')")
                        ->whereIn('tp.employee_id', $probationerIds)
                        ->select('tp.employee_id', 'ts.training_id')
                        ->get()
                        ->groupBy('employee_id')
                        ->map(fn($rows) => $rows->pluck('training_id')->unique()->all())
                        ->toArray();

                    $trainingEmployees = [];
                    foreach ($probationerIds as $empId) {
                        $empCompleted = $completedByEmp[$empId] ?? [];
                        if (count($empCompleted) < count($requiredProgramIds)) {
                            // Missing at least one required program → exclude.
                            $trainingEmployees[] = $empId;
                        }
                    }
                    $excludeIds = array_merge($excludeIds, $trainingEmployees);
                }
            }


            $excludeIds = array_unique($excludeIds);

            return response()->json(['exclude_ids' => $excludeIds]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Apply an approved promotion to the employee's record. Mirrors
     * TransferController::applyTransferToEmployee — snapshots the pre-promotion
     * state once (idempotent), then updates Position_id, rank, basic_salary.
     * Manning sync is a no-op when the position is in the same dept (typical
     * promotion); cross-dept promotions adjust manning on both sides.
     */
    public static function applyPromotionToEmployee(EmployeePromotion $promotion): void
    {
        $promotion->loadMissing(['employee', 'newPosition']);
        $employee = $promotion->employee;
        if (!$employee) return;

        // One-time snapshot — re-applying must not overwrite original values.
        if (empty($promotion->pre_promotion_snapshot)) {
            $promotion->pre_promotion_snapshot = [
                'Position_id'  => $employee->Position_id,
                'Dept_id'      => $employee->Dept_id,
                'rank'         => $employee->rank,
                'basic_salary' => $employee->basic_salary,
            ];
            $promotion->save();
        }

        $sourceDeptId = $employee->Dept_id;
        $sourcePosId  = $employee->Position_id;

        // New position drives new rank + (optionally) new dept.
        $newPosition = $promotion->newPosition;
        if ($newPosition) {
            $employee->Position_id = $newPosition->id;
            if ($newPosition->Rank !== null) {
                $employee->rank = $newPosition->Rank;
            }
            // Cross-dept promotion — also move the employee's Dept_id.
            if ($newPosition->dept_id && (int) $newPosition->dept_id !== (int) $sourceDeptId) {
                $employee->Dept_id = $newPosition->dept_id;
            }
        }

        // new_salary on the promotion overrides; fall back to current_salary +
        // salary_increment_amount when new_salary wasn't pre-computed. Never
        // zero out the salary if the form didn't supply a usable value.
        $newSalary = null;
        if ($promotion->new_salary !== null && (float) $promotion->new_salary > 0) {
            $newSalary = (float) $promotion->new_salary;
        } elseif ($promotion->salary_increment_amount !== null && (float) $promotion->salary_increment_amount > 0) {
            $newSalary = (float) ($promotion->current_salary ?? $employee->basic_salary) + (float) $promotion->salary_increment_amount;
        } elseif ($promotion->salary_increment_percent !== null && (float) $promotion->salary_increment_percent > 0) {
            $base = (float) ($promotion->current_salary ?? $employee->basic_salary);
            $newSalary = $base + ($base * ((float) $promotion->salary_increment_percent / 100));
        }
        if ($newSalary !== null && $newSalary > 0) {
            $employee->basic_salary = $newSalary;
        }

        $employee->save();

        // Manning sync — only when the dept changed; same-dept position change
        // still needs a sync between the source position (-1) and the target
        // position (+1) for headcount tracking on the Workforce dashboard.
        if ((int) $sourcePosId !== (int) $employee->Position_id) {
            \App\Http\Controllers\Resorts\People\Transfer\TransferController::adjustManningCount(
                (int) $promotion->resort_id, (int) $sourceDeptId, (int) $sourcePosId, -1
            );
            \App\Http\Controllers\Resorts\People\Transfer\TransferController::adjustManningCount(
                (int) $promotion->resort_id, (int) $employee->Dept_id, (int) $employee->Position_id, +1
            );
        }
    }

    /**
     * Day-of: apply the promotion to the employee and notify the employee,
     * their HOD/EXCOM, GM, and HR. Marks effective_day_notified_at so the
     * scheduler never double-fires. Mirrors TransferController::dispatchEffectiveDateNotifications.
     */
    public static function dispatchEffectiveDateNotifications(EmployeePromotion $promotion): void
    {
        $promotion->loadMissing(['employee.resortAdmin', 'newPosition', 'currentPosition']);

        self::applyPromotionToEmployee($promotion);

        $employee = $promotion->employee()->first();
        if (!$employee) return;

        $employeeName = optional($employee->resortAdmin)->full_name ?? 'Employee';
        $oldPosTitle = optional($promotion->currentPosition)->position_title ?? 'previous position';
        $newPosTitle = optional($promotion->newPosition)->position_title ?? 'new position';
        $effectiveDate = $promotion->effective_date
            ? \Carbon\Carbon::parse($promotion->effective_date)->format('d M Y')
            : 'today';
        $resortId = $promotion->resort_id;

        $push = function ($recipientId, string $title, string $message) use ($resortId, $promotion) {
            if (empty($recipientId)) return;
            event(new ResortNotificationEvent(Common::nofitication(
                $resortId, 10, $title, $message, $promotion->id, $recipientId, 'People'
            )));
        };

        // (1) The employee
        $push(
            $employee->id,
            'Promotion Effective Today',
            "📌 Congratulations — your promotion from {$oldPosTitle} to {$newPosTitle} takes effect today ({$effectiveDate})."
        );

        // (2) Dept HOD / EXCOM (the new dept after promotion)
        $hodRank   = array_search('HOD',   config('settings.Position_Rank'));
        $excomRank = array_search('EXCOM', config('settings.Position_Rank'));
        $gmRank    = array_search('GM',    config('settings.Position_Rank'));
        $leadRanks = array_filter([$hodRank, $excomRank], fn($r) => $r !== false);

        $deptLeads = Employee::where('resort_id', $resortId)
            ->where('Dept_id', $employee->Dept_id)
            ->where('status', 'Active')
            ->whereIn('rank', $leadRanks)
            ->pluck('id');
        foreach ($deptLeads as $leadId) {
            if ((int) $leadId === (int) $employee->id) continue;
            $push(
                $leadId,
                'Employee Promotion Effective Today',
                "📌 {$employeeName} has been promoted from {$oldPosTitle} to {$newPosTitle} today ({$effectiveDate})."
            );
        }

        // (3) GM
        if ($gmRank !== false) {
            $gmIds = Employee::where('resort_id', $resortId)
                ->where('status', 'Active')
                ->where('rank', $gmRank)
                ->pluck('id');
            foreach ($gmIds as $gmId) {
                $push(
                    $gmId,
                    'Employee Promotion Effective Today',
                    "📌 {$employeeName} has been promoted to {$newPosTitle} today ({$effectiveDate})."
                );
            }
        }

        // (4) HR HOD / EXCOM
        $hrDept = \App\Models\ResortDepartment::where('resort_id', $resortId)->get()
            ->first(fn($d) => \App\Helpers\Common::isHRDepartment($d->id));
        if ($hrDept) {
            $hrLeads = Employee::where('resort_id', $resortId)
                ->where('Dept_id', $hrDept->id)
                ->where('status', 'Active')
                ->whereIn('rank', $leadRanks)
                ->pluck('id');
            foreach ($hrLeads as $leadId) {
                $push(
                    $leadId,
                    'Promotion Effective Today',
                    "📌 {$employeeName}'s promotion ({$oldPosTitle} → {$newPosTitle}) takes effect today ({$effectiveDate})."
                );
            }
        }

        // (5) Reporting manager — the employee's direct supervisor needs a
        // heads-up that the team change is now in effect (could be the new
        // dept's manager after a cross-dept promotion). Skip when the manager
        // is the employee themselves, the dept HOD pool, or the GM (already
        // notified above) to avoid duplicate pings.
        if (!empty($employee->reporting_to)) {
            $alreadyNotified = collect()
                ->concat($deptLeads ?? collect())
                ->push($employee->id)
                ->map(fn($v) => (int) $v)
                ->all();
            if (isset($gmIds)) {
                $alreadyNotified = array_merge($alreadyNotified, $gmIds->map(fn($v) => (int) $v)->all());
            }
            if (!in_array((int) $employee->reporting_to, $alreadyNotified, true)) {
                $push(
                    (int) $employee->reporting_to,
                    'Team Member Promotion Effective Today',
                    "📌 Your team member {$employeeName} has been promoted from {$oldPosTitle} to {$newPosTitle} today ({$effectiveDate})."
                );
            }
        }

        $promotion->effective_day_notified_at = now();
        $promotion->save();
    }

    /**
     * Sent the moment a promotion is fully Approved but the effective date is
     * still in the future. Notifies the employee + dept HOD/EXCOM + HR so they
     * know to expect the change on the effective date. Does NOT touch the
     * employee record — the scheduler handles that on the effective day.
     */
    public static function dispatchFinalApprovedNotifications(EmployeePromotion $promotion): void
    {
        $promotion->loadMissing(['employee.resortAdmin', 'newPosition', 'currentPosition']);
        $employee = $promotion->employee;
        if (!$employee) return;

        $employeeName = optional($employee->resortAdmin)->full_name ?? 'Employee';
        $newPosTitle = optional($promotion->newPosition)->position_title ?? 'new position';
        $effectiveDate = $promotion->effective_date
            ? \Carbon\Carbon::parse($promotion->effective_date)->format('d M Y')
            : 'a future date';
        $resortId = $promotion->resort_id;

        $push = function ($recipientId, string $title, string $message) use ($resortId, $promotion) {
            if (empty($recipientId)) return;
            event(new ResortNotificationEvent(Common::nofitication(
                $resortId, 10, $title, $message, $promotion->id, $recipientId, 'People'
            )));
        };

        $push(
            $employee->id,
            'Promotion Approved',
            "🎉 Your promotion to {$newPosTitle} has been approved. Effective date: {$effectiveDate}."
        );

        $hodRank   = array_search('HOD',   config('settings.Position_Rank'));
        $excomRank = array_search('EXCOM', config('settings.Position_Rank'));
        $leadRanks = array_filter([$hodRank, $excomRank], fn($r) => $r !== false);

        $deptLeads = Employee::where('resort_id', $resortId)
            ->where('Dept_id', $employee->Dept_id)
            ->where('status', 'Active')
            ->whereIn('rank', $leadRanks)
            ->pluck('id');
        foreach ($deptLeads as $leadId) {
            if ((int) $leadId === (int) $employee->id) continue;
            $push(
                $leadId,
                'Employee Promotion Approved',
                "📢 {$employeeName}'s promotion to {$newPosTitle} has been approved. Effective {$effectiveDate}."
            );
        }

        // Reporting manager — heads-up to the employee's direct supervisor
        // (unless they're already in the dept-leads set or are the employee
        // themselves).
        if (!empty($employee->reporting_to)) {
            $alreadyNotified = $deptLeads->push($employee->id)->map(fn($v) => (int) $v)->all();
            if (!in_array((int) $employee->reporting_to, $alreadyNotified, true)) {
                $push(
                    (int) $employee->reporting_to,
                    'Team Member Promotion Approved',
                    "📢 Your team member {$employeeName}'s promotion to {$newPosTitle} has been approved. Effective {$effectiveDate}."
                );
            }
        }
    }

}