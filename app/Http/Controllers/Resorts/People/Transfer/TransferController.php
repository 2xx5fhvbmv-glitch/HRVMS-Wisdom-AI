<?php

namespace App\Http\Controllers\Resorts\People\Transfer;

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
use App\Models\EmployeeTransfer;
use App\Models\EmployeeTransferApproval;
use App\Models\ManningResponse;
use App\Models\PositionMonthlyData;
use App\Events\ResortNotificationEvent;
use Auth;
use Config;
use Common;
use DB;
use Carbon\Carbon;
class TransferController extends Controller
{
    public $resort;
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
    }

    public function index(Request $request)
    {
        $page_title ='Initiate Transfer';
        $resort_id = $this->resort->resort_id;
        $employees = Employee::with(['resortAdmin','position','department'])->where('status','Active')->where('resort_id',$resort_id)->get();
        $departments = ResortDepartment::where('resort_id',$resort_id)->where('status','active')->get();
        return view('resorts.people.transfer.index',compact('page_title','employees','departments'));
    }

    public function store(Request $request)
    {
        // Validate input
        $validated = $request->validate([
            'employee_name' => 'required|exists:employees,id',
            'current_dep_id' => 'nullable',
            'target_dep' => 'required|exists:resort_departments,id',
            'current_pos_id' => 'nullable',
            'target_pos' => 'required|exists:resort_positions,id',
            'reason_transfer' => 'nullable|string|max:255',
            'effective_date' => ['required', 'date_format:d/m/Y'],
            'transfer_status' => 'required|in:Permanent,Temporary',
            'additional_notes' => 'nullable|string|max:255',
            'reporting_manager' => 'required|exists:employees,id'
        ]);

        // Save transfer
        $formattedEffectiveDate = $validated['effective_date'] ? Carbon::createFromFormat('d/m/Y', $validated['effective_date'])->format('Y-m-d') : null;

        $transfer = EmployeeTransfer::create([
            'resort_id' => $this->resort->resort_id,
            'employee_id' => $validated['employee_name'],
            'current_department_id' => $validated['current_dep_id'],
            'target_department_id' => $validated['target_dep'],
            'current_position_id' => $validated['current_pos_id'],
            'target_position_id' => $validated['target_pos'],
            'reason_for_transfer' => $validated['reason_transfer'],
            'effective_date' => $formattedEffectiveDate,
            'transfer_status' => $validated['transfer_status'],
            'additional_notes' => $validated['additional_notes'],
            'reporting_manager'=>$validated['reporting_manager'],
            'status' => 'Pending',
        ]);

        $transferApprovalFlow = collect();

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
            $transferApprovalFlow->push([
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

        if ($gmApprover) {
            // $promotionApprovalFlow->push($gmApprover);
            $transferApprovalFlow->push([
                'approver' => $gmApprover,
                'rank' => 'GM'
            ]);
        }

        // Create approval entries
        $transferApprovalFlow->each(function ($approver) use ($transfer) {
            EmployeeTransferApproval::create([
                'transfer_id' => $transfer->id,
                'approved_by' => $approver['approver']->id,
                'status' => 'Pending',
                'approval_rank'  => $approver['rank'],
            ]);

            $msg = "📢 New Transfer Request Submitted\n👤 Employee: " . $transfer->employee->full_name .
            "\n🏢 From: " . optional($transfer->currentDepartment)->department_name .
            "\n➡️ To: " . optional($transfer->targetDepartment)->department_name .
            "\n📅 Effective Date: " . Carbon::parse($transfer->effective_date)->format('d M Y') .
            "\n📝 Status: Pending Approval";

            event(new ResortNotificationEvent(Common::nofitication(
                $this->resort->resort_id, // Make sure `resort_id` exists on the `meetings` table
                10,
                'Transfer Request Notification',
                $msg,
                0,
                $approver['approver']->id,
                'People'
            )));
        });

        return response()->json([
            'success' => true,
            'message' => 'Transfer request submitted.',
            'redirect_url' => route('people.transfer.list')
        ]);
    }

    public function list(Request $request)
    {
        $page_title = "Transfer List";
        $edit_class = ''; // Define variable at method level to avoid undefined variable error

        if ($request->ajax()) {
            $transfers = EmployeeTransfer::with([
                'employee.position',
                'employee.department',
                'employee.resortAdmin','currentDepartment', 'targetDepartment', 'currentPosition', 'targetPosition','approvals'
            ])->whereIn('status',['Pending','On Hold'])->select('*');

            if ($request->filled('department_id')) {
                $transfers->whereHas('employee', function ($q) use ($request) {
                    $q->where('Dept_id', $request->department_id);
                });
            }

            if ($request->filled('position_id')) {
                $transfers->whereHas('employee', function ($q) use ($request) {
                    $q->where('Position_id', $request->position_id);
                });
            }

            if ($request->filled('status')) {
                $transfers->where('status', $request->status);
            }

            if ($request->filled('searchTerm')) {
                $transfers->whereHas('employee.resortAdmin', function ($q) use ($request) {
                    $q->where('first_name', 'like', '%' . $request->searchTerm . '%')
                        ->orWhere('last_name', 'like', '%' . $request->searchTerm . '%')
                        ->orWhere('Emp_id', 'like', '%' . $request->searchTerm . '%');
                });
            }

            if ($request->filled('date')) {
                $transfers->whereDate('effective_date', $request->date);
            }

            if(Common::checkRouteWisePermission('people.transfer.initiate',config('settings.resort_permissions.edit')) == false){
                $edit_class = 'd-none';
            }
            
            // dd($transfers->get());
            return datatables()->of($transfers)
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
                ->addColumn('current_position', fn($row) => optional($row->currentPosition)->position_title ?? '—')
                ->addColumn('target_position', fn($row) => optional($row->targetPosition)->position_title ?? '—')
                ->addColumn('current_department', function ($row) {
                    return optional($row->currentDepartment)->name ?? '—';
                })
                ->addColumn('target_department', function ($row) {
                    return optional($row->targetDepartment)->name ?? '—';
                })
                ->addColumn('effective_date', function ($row) {
                    $date = \Carbon\Carbon::parse($row->effective_date);
                    return $date->format('d M Y');
                })
                ->addColumn('reason_for_transfer', function ($row) {
                    return '<span title="' . e($row->reason_for_transfer) . '">' . \Str::limit($row->reason_for_transfer, 30) . '</span>';
                })
                ->addColumn('status', function ($row) {
                    if (in_array($row->status, ['Approved', 'Rejected'])) {
                        return '<span class="badge badge-theme' . ($row->status === 'Approved' ? 'Success' : 'Danger') . '">' . $row->status . '</span>';
                    }

                    $statuses = $row->approvals->map(function ($approval)  {
                        $role = $approval->approval_rank ?? 'Approver';
                        $status = $approval->status;
                        $badgeClass = match ($status) {
                            'Approved' => 'badge-themeSuccess',
                            'Rejected' => 'badge-themeDanger',
                            'On Hold'  => 'badge-themeSkyblue',
                            default    => 'badge-themeWarning',
                        };
                        return '<div><span class="badge ' . $badgeClass . '">' . $role . ': ' . $status . '</span></div>';
                    })->implode('');

                    return $statuses ?: '<span class="badge badge-themeWarning">Pending</span>';
                })
               ->addColumn('actions', function ($row) use ($edit_class) {
                    $loggedInEmployeeId = $this->resort->GetEmployee->id ?? null;
                    $myApproval = $row->approvals->firstWhere('approved_by', $loggedInEmployeeId);

                    if (!$myApproval) return '';

                    return '
                        <div class="d-flex align-items-center gap-2">
                            <a href="#" class="correct-btn transfer-action ' . $edit_class . '" data-id="' . $row->id . '" data-approvedBy="' . $myApproval->approved_by . '" data-action="Approved" title="Approve"><i class="fa-solid fa-check"></i></a>
                            <a href="#" class="btn-tableIcon btnIcon-orangeDark transfer-action ' . $edit_class . '" data-id="' . $row->id . '" data-approvedBy="' . $myApproval->approved_by . '" data-action="On Hold" title="Put on Hold"><i class="fa-regular fa-hand"></i></a>
                            <a href="#" class="close-btn transfer-action ' . $edit_class . '" data-id="' . $row->id . '" data-action="Rejected" data-approvedBy="' . $myApproval->approved_by . '" title="Reject"><i class="fa-solid fa-xmark"></i></a>
                        </div>';
                })
                ->rawColumns(['employee_name', 'effective_date', 'status', 'actions', 'reason_for_transfer'])
                ->make(true);
        }

        $resort_id = $this->resort->resort_id;
        $departments = ResortDepartment::where('resort_id', $resort_id)->where('status', 'active')->get();
        $positions = ResortPosition::where('resort_id', $resort_id)->where('status', 'active')->get();

        return view('resorts.people.transfer.list', compact('page_title', 'departments', 'positions'));
    }

    public function history(Request $request)
    {
        $page_title = "Transfer History";

        if ($request->ajax()) {
            $transfers = EmployeeTransfer::with([
                'employee.position',
                'employee.department',
                'employee.resortAdmin','currentDepartment', 'targetDepartment', 'currentPosition', 'targetPosition','approvals'
            ])->select('*');

            if ($request->filled('department_id')) {
                $transfers->whereHas('employee', function ($q) use ($request) {
                    $q->where('Dept_id', $request->department_id);
                });
            }

            if ($request->filled('position_id')) {
                $transfers->whereHas('employee', function ($q) use ($request) {
                    $q->where('position_id', $request->position_id);
                });
            }

            if ($request->filled('status')) {
                $transfers->where('status', $request->status);
            }

            if ($request->filled('searchTerm')) {
                $transfers->whereHas('employee.resortAdmin', function ($q) use ($request) {
                    $q->where('first_name', 'like', '%' . $request->searchTerm . '%')
                        ->orWhere('last_name', 'like', '%' . $request->searchTerm . '%')
                        ->orWhere('Emp_id', 'like', '%' . $request->searchTerm . '%');
                });
            }

            if ($request->filled('date')) {
                $transfers->whereDate('effective_date', $request->date);
            }

            // dd($transfers->get());
            return datatables()->of($transfers)
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
                ->addColumn('current_position', fn($row) => optional($row->currentPosition)->position_title ?? '—')
                ->addColumn('target_position', fn($row) => optional($row->targetPosition)->position_title ?? '—')
                ->addColumn('current_department', function ($row) {
                    return optional($row->currentDepartment)->name ?? '—';
                })
                ->addColumn('target_department', function ($row) {
                    return optional($row->targetDepartment)->name ?? '—';
                })
                ->addColumn('effective_date', function ($row) {
                    $date = \Carbon\Carbon::parse($row->effective_date);
                    return $date->format('d M Y');
                })
                ->addColumn('reason_for_transfer', function ($row) {
                    return '<span title="' . e($row->reason_for_transfer) . '">' . \Str::limit($row->reason_for_transfer, 30) . '</span>';
                })
                ->addColumn('status', function ($row) {
                    return match ($row->status) {
                        'Approved' => '<span class="badge badge-themeSuccess">Approved</span>',
                        'Rejected' => '<span class="badge badge-themeDanger">Rejected</span>',
                        'On Hold'  => '<span class="badge badge-themeSkyblue">On Hold</span>',
                        default    => '<span class="badge badge-themeWarning">Pending</span>',
                    };
                })
                ->rawColumns(['employee_name', 'effective_date', 'status', 'reason_for_transfer'])
                ->make(true);
        }

        $resort_id = $this->resort->resort_id;
        $departments = ResortDepartment::where('resort_id', $resort_id)->where('status', 'active')->get();
        $positions = ResortPosition::where('resort_id', $resort_id)->where('status', 'active')->get();

        return view('resorts.people.transfer.history', compact('page_title', 'departments', 'positions'));
    }

    public function getEmployeeTransferHistory(Request $request)
    {
        $employeeId = $request->employee_id;

        $transfers = EmployeeTransfer::with(['currentDepartment', 'targetDepartment'])
            ->where('employee_id', $employeeId)
            ->orderBy('effective_date', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'effective_date' => \Carbon\Carbon::parse($item->effective_date)->format('d M Y'),
                    'current_department' => optional($item->currentDepartment)->name ?? '-',
                    'target_department' => optional($item->targetDepartment)->name ?? '-',
                    'transfer_type' => $item->transfer_status,
                    'status' => $item->status,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $transfers,
        ]);
    }

    public function getTransferStats(Request $request)
    {
        $employeeId = $request->employee_id;
    
        $baseQuery = EmployeeTransfer::where('resort_id', $this->resort->resort_id);
    
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', 'Pending')->count(),
            'approved' => (clone $baseQuery)->where('status', 'Approved')->count(),
            'rejected_on_hold' => (clone $baseQuery)->whereIn('status', ['Rejected', 'On-Hold'])->count(),
            'pending_letter' => (clone $baseQuery)
                ->where('status', 'Approved')
                ->where('letter_dispatched', 'No')
                ->count(),
        ];
    
        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
    

    public function getTransferTypeStats(Request $request)
    {

        $temporaryCount = EmployeeTransfer::where('resort_id', $this->resort->resort_id)
                            ->where('transfer_status', 'Temporary')->count();

        $permanentCount = EmployeeTransfer::where('resort_id', $this->resort->resort_id)
                            ->where('transfer_status', 'Permanent')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'temporary' => $temporaryCount,
                'permanent' => $permanentCount,
            ]
        ]);
    }

    public function handleApproval(Request $request, $id, $action)
    {
        $transfer = EmployeeTransfer::with(['approvals', 'employee', 'targetPosition', 'targetDepartment'])->findOrFail($id);
        $comments = $request->input('reason', null);
        $currentEmployee = $this->resort->GetEmployee;
        $actionName = $action;

        $currentApproval = $transfer->approvals()
            ->where('approved_by', $currentEmployee->id)
            ->whereIn('status', ['Pending', 'On Hold'])
            ->first();

        if (!$currentApproval) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized or have already finalized your action on this request.',
            ], 403);
        }

        $pendingBefore = $transfer->approvals()
            ->where('id', '<', $currentApproval->id)
            ->get();

        foreach ($pendingBefore as $previousApproval) {
            if ($previousApproval->status !== 'Approved') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You cannot act on this request until previous approvers have approved it.',
                ], 403);
            }
        }

        // Update current approval
        $currentApproval->update([
            'status' => $actionName,
            'remarks' => $comments,
            'approved_at' => now(),
        ]);

        $hr = Employee::where('resort_id',$this->resort->resort_id)->where('Admin_Parent_id',$transfer->created_by)->first();
        $employee = Employee::findOrFail($transfer->employee_id);

        // Handle Approved
        if ($actionName === 'Approved') {

            // 1.  How many approvers are still PENDING?
            $pending  = $transfer->approvals()->where('status', 'Pending')->count();
            // 2.  How many have put it ON-HOLD or REJECTED?
            $onHold   = $transfer->approvals()->where('status', 'On Hold')->count();
            $rejected = $transfer->approvals()->where('status', 'Rejected')->count();

            /**
             * ✅  Main record becomes Approved **only if**:
             *     – nobody is Pending
             *     – nobody Rejected
             *     – nobody On-Hold
             */
            if ($pending === 0 && $onHold === 0 && $rejected === 0) {

                $transfer->status = 'Approved';
                $transfer->save();

                // ✍️ propagate changes to Employee profile
                $employee                 = $transfer->employee;
                $employee->Dept_id        = $transfer->target_department_id;
                $employee->Position_id    = $transfer->target_position_id;
                $employee->division_id    = $transfer->targetDepartment->division_id;
                $employee->reporting_to   = $transfer->reporting_manager ?? $employee->reporting_to;
                $employee->rank           = $transfer->targetPosition->Rank;
                $employee->save();

                // 🔔 notifications (unchanged)
                event(new ResortNotificationEvent(Common::nofitication(
                    $this->resort->resort_id,
                    10,
                    'Transfer Approved',
                    "🎉 Your transfer request to " . $transfer->targetDepartment->name . " has been approved!",
                    0,
                    $employee->id,
                    'People'
                )));

                if ($hr) {
                    event(new ResortNotificationEvent(Common::nofitication(
                        $this->resort->resort_id,
                        10,
                        'Employee Transfer Finalized',
                        "📢 Transfer for " . $employee->resortAdmin->full_name . " has been approved and profile updated.",
                        0,
                        $hr->id,
                        'People'
                    )));
                }
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Your approval has been recorded.',
            ]);
        }

        // Handle Rejected
        if ($actionName === 'Rejected') {
            $transfer->status = 'Rejected';
            $transfer->save();

            // 🔔 Notify Employee
            event(new ResortNotificationEvent(Common::nofitication(
                $this->resort->resort_id,
                10,
                'Transfer Rejected',
                "❌ Your transfer request to " . $transfer->targetDepartment->name . " has been rejected.",
                0,
                $transfer->employee_id,
                'People'
            )));

            
            if ($hr) {
                event(new ResortNotificationEvent(Common::nofitication(
                    $this->resort->resort_id,
                    10,
                    'Employee Transfer Rejected',
                    "📢 Transfer for " . $employee->resortAdmin->full_name . " has been rejected.",
                    0,
                    $hr->id,
                    'People'
                )));
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Transfer Request Rejected.',
            ]);
        }

        // Handle On Hold
        if ($actionName === 'On Hold') {
            $transfer->status = 'On Hold';
            $transfer->save();

            // 🔔 Notify Employee
            event(new ResortNotificationEvent(Common::nofitication(
                $transfer->resort_id,
                10,
                'Transfer On Hold',
                "⏸️ Your transfer request to " . $transfer->targetDepartment->department_name . " has been put on hold.",
                0,
                $transfer->employee_id,
                'People'
            )));


            if ($hr) {
                event(new ResortNotificationEvent(Common::nofitication(
                    $this->resort->resort_id,
                    10,
                    'Employee Transfer On Hold',
                    "📢 Transfer for " . $employee->resortAdmin->full_name . " has been put on hold.",
                    0,
                    $hr->id,
                    'People'
                )));
            }
            return response()->json([
                'status' => 'success',
                'message' => 'Transfer Request Put On Hold.',
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Invalid action.',
        ]);
    }


    public function checkBudget(Request $request)
    {
        $deptId = $request->input('target_dep');
        $positionId = $request->input('target_pos');

        $month = now()->format('m');
        $year = now()->format('Y');

        $manning = ManningResponse::where('dept_id', $deptId)
            ->where('year', $year)
            ->first();

        if (!$manning) {
            return response()->json(['success' => false, 'message' => 'No manning data found for this department.']);
        }

        $positionData = PositionMonthlyData::where('manning_response_id', $manning->id)
            ->where('month', $month)
            ->where('position_id', $positionId)
            ->first();

        if (!$positionData || $positionData->vacantcount <= 0) {
            return response()->json(['success' => false, 'message' => 'No vacant budgeted headcount available.']);
        }

        return response()->json(['success' => true, 'message' => 'Budget check passed.']);
    }

    public function getReportingManagers(Request $request){
        $Dept_id = $request->departmentId;
        $targetRanks = [
            array_search('HOD', config('settings.Position_Rank')),
            array_search('MGR', config('settings.Position_Rank')),
            array_search('GM', config('settings.Position_Rank')),
            array_search('SUP', config('settings.Position_Rank')),
            array_search('EXCOM', config('settings.Position_Rank'))
        ];
        // Query for employees in the same resort, with rank HOD/MGR/GM, and HODs from the same department
        $reportingEmployees = DB::table('employees')
            ->join('resort_admins', 'employees.Admin_Parent_id', '=', 'resort_admins.id')
            ->where('employees.resort_id', $this->resort->resort_id)
            ->whereIn('employees.rank', $targetRanks)
            ->where(function ($query) use ($Dept_id) {
                $query->where('employees.rank', array_search('HOD', config('settings.Position_Rank')))
                      ->where('employees.Dept_id', $Dept_id)
                      ->orWhere('employees.rank', '<>', array_search('HOD', config('settings.Position_Rank')));
            })
            ->select(
                'employees.*',
                'resort_admins.first_name as first_name',
                'resort_admins.last_name as last_name',
                'resort_admins.email as admin_email'
            )
            ->get();
        // dd($reportingEmployees);
        
        return response()->json(['success' => true, 'data' => $reportingEmployees]);

    }

}