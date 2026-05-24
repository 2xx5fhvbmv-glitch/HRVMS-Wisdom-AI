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
        if(!$this->resort) return;
    }

    public function index(Request $request)
    {
        $page_title ='Initiate Transfer';
        $resort_id = $this->resort->resort_id;
        $scopedDeptIds = \App\Helpers\Common::getScopedDepartmentIds();
        $employees = Employee::with(['resortAdmin','position','department'])
            ->where('status','Active')
            ->where('resort_id',$resort_id)
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))
            ->get();
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
            'current_section_id' => 'nullable|exists:resort_sections,id',
            'target_section_id'  => 'nullable|exists:resort_sections,id',
            'current_pos_id' => 'nullable',
            'target_pos' => 'required|exists:resort_positions,id',
            'reason_transfer' => 'nullable|string|max:255',
            'effective_date' => ['required', 'date_format:d/m/Y'],
            'transfer_status' => 'required|in:Permanent,Temporary',
            'additional_notes' => 'nullable|string|max:255',
            'reporting_manager' => 'required|exists:employees,id',
            // Item 2 — proposed salary for the transferred employee.
            // Forbidden on Temporary transfers (the employee returns to the
            // original role + salary at temporary_to, so changing pay would
            // be reverted anyway and just confuses Payroll mid-window).
            'proposed_salary' => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value !== null && $value !== '' && $request->input('transfer_status') === 'Temporary') {
                        $fail('Proposed Salary is not allowed on a Temporary transfer.');
                    }
                },
            ],
            // Item 3 — temporary period; only required when type = Temporary.
            'temporary_from' => 'required_if:transfer_status,Temporary|nullable|date_format:d/m/Y',
            'temporary_to'   => 'required_if:transfer_status,Temporary|nullable|date_format:d/m/Y|after_or_equal:temporary_from',
        ]);

        // Block duplicate in-flight transfers — a second submission while a
        // Pending/On-Hold transfer exists for the same employee would race
        // both approval flows and risk applying the older transfer's profile
        // update over the newer one. HR can cancel/withdraw the existing one
        // first if a re-submission is needed.
        $hasOpenTransfer = EmployeeTransfer::where('resort_id', $this->resort->resort_id)
            ->where('employee_id', $validated['employee_name'])
            ->whereIn('status', ['Pending', 'On Hold'])
            ->exists();
        if ($hasOpenTransfer) {
            return response()->json([
                'success' => false,
                'message' => 'This employee already has a pending transfer request. Resolve it before submitting another.',
                'errors'  => ['employee_name' => ['This employee already has a pending transfer request.']],
            ], 422);
        }

        // ── Item 1 — Vacant Position Validation ──────────────────────────
        // Don't allow transferring an employee into a position that is
        // already actively filled beyond its budgeted headcount.
        $vacancyCheck = $this->isTargetPositionVacant(
            (int) $validated['target_pos'],
            (int) $validated['employee_name']
        );
        if (!$vacancyCheck['vacant']) {
            return response()->json([
                'success' => false,
                'message' => $vacancyCheck['message'],
                'errors'  => ['target_pos' => [$vacancyCheck['message']]],
            ], 422);
        }

        // Save transfer
        $formattedEffectiveDate = $validated['effective_date']
            ? Carbon::createFromFormat('d/m/Y', $validated['effective_date'])->format('Y-m-d')
            : null;

        $temporaryFrom = ($validated['transfer_status'] === 'Temporary' && !empty($validated['temporary_from']))
            ? Carbon::createFromFormat('d/m/Y', $validated['temporary_from'])->format('Y-m-d') : null;
        $temporaryTo = ($validated['transfer_status'] === 'Temporary' && !empty($validated['temporary_to']))
            ? Carbon::createFromFormat('d/m/Y', $validated['temporary_to'])->format('Y-m-d') : null;

        // For a Temporary transfer the Effective Date IS the day the temp
        // period starts — enforce server-side too in case the form was
        // bypassed. Otherwise the apply scheduler and the temp window get
        // out of sync (apply fires on effective_date, revert fires after
        // temporary_to).
        if ($validated['transfer_status'] === 'Temporary' && $temporaryFrom) {
            $formattedEffectiveDate = $temporaryFrom;
        }

        $transfer = EmployeeTransfer::create([
            'resort_id' => $this->resort->resort_id,
            'employee_id' => $validated['employee_name'],
            'current_department_id' => $validated['current_dep_id'],
            'target_department_id' => $validated['target_dep'],
            'current_section_id' => $validated['current_section_id'] ?? null,
            'target_section_id'  => $validated['target_section_id'] ?? null,
            'current_position_id' => $validated['current_pos_id'],
            'target_position_id' => $validated['target_pos'],
            'reason_for_transfer' => $validated['reason_transfer'],
            'effective_date' => $formattedEffectiveDate,
            'transfer_status' => $validated['transfer_status'],
            'additional_notes' => $validated['additional_notes'],
            'reporting_manager'=>$validated['reporting_manager'],
            // Item 2 — budgeted salary snapshot + proposed salary.
            'budgeted_salary' => $this->getBudgetedSalary((int) $validated['target_dep'], (int) $validated['target_pos']),
            'proposed_salary' => $validated['proposed_salary'] ?? null,
            // Item 3 — temporary period.
            'temporary_from' => $temporaryFrom,
            'temporary_to'   => $temporaryTo,
            'status' => 'Pending',
        ]);

        $transferApprovalFlow = collect();

        // Finance Approver Logic — match by Finance position titles OR by
        // Finance rank (7) so resorts that don't use the exact title strings
        // still resolve a Finance approver. (Item 5 fix.)
        $financeManagerTitles = ['Director of Finance', 'Finance Manager'];
        $financeRank = array_search('Finance', config('settings.Position_Rank')); // 7
        $financePositionIds = ResortPosition::where('resort_id', $this->resort->resort_id)
            ->where(function ($q) use ($financeManagerTitles, $financeRank) {
                $q->whereIn('position_title', $financeManagerTitles);
                if ($financeRank !== false) {
                    $q->orWhere('Rank', $financeRank);
                }
            })
            ->pluck('id');

        // Resolve Finance dept IDs once so we can also include the dept's
        // HOD (rank=2) / EXCOM (rank=1) as Finance approver candidates.
        $financeDeptIds = ResortDepartment::where('resort_id', $this->resort->resort_id)
            ->get()
            ->filter(fn($d) => \App\Helpers\Common::isFinanceDepartment($d->id))
            ->pluck('id');

        $financeApprover = Employee::with(['resortAdmin', 'position'])
            ->where('resort_id', $this->resort->resort_id)
            ->where(function ($q) use ($financePositionIds, $financeRank, $financeDeptIds) {
                $q->whereIn('Position_id', $financePositionIds);
                if ($financeRank !== false) {
                    $q->orWhere('rank', $financeRank);
                }
                if ($financeDeptIds->isNotEmpty()) {
                    $q->orWhere(function ($qq) use ($financeDeptIds) {
                        $qq->whereIn('Dept_id', $financeDeptIds)
                           ->whereIn('rank', [1, 2]);
                    });
                }
            })
            ->first();

        // Build the full Finance pool so we can fan out the on-submit
        // notification to every eligible Finance HOD/EXCOM, not only the one
        // picked for `approved_by`.
        $financePool = Employee::with('resortAdmin')
            ->where('resort_id', $this->resort->resort_id)
            ->where(function ($q) use ($financePositionIds, $financeRank, $financeDeptIds) {
                $q->whereIn('Position_id', $financePositionIds);
                if ($financeRank !== false) {
                    $q->orWhere('rank', $financeRank);
                }
                if ($financeDeptIds->isNotEmpty()) {
                    $q->orWhere(function ($qq) use ($financeDeptIds) {
                        $qq->whereIn('Dept_id', $financeDeptIds)
                           ->whereIn('rank', [1, 2]);
                    });
                }
            })
            ->get();

        if ($financeApprover) {
            $transferApprovalFlow->push([
                'approver' => $financeApprover,
                'rank' => 'Finance',
                'pool' => $financePool,
            ]);
        }

        // GM Approver Logic — GM rank (8). Match against the employee's own
        // `rank` column OR the linked position's Rank, so it works whether or
        // not the position relation is populated. (Item 5 fix.)
        $gmRank = array_search('GM', config('settings.Position_Rank')); // 8
        $gmApprover = Employee::with('position')
            ->where('resort_id', $this->resort->resort_id)
            ->where(function ($q) use ($gmRank) {
                $q->where('rank', $gmRank)
                  ->orWhereHas('position', fn($query) => $query->where('Rank', $gmRank));
            })
            ->first();

        $gmPool = Employee::with('resortAdmin')
            ->where('resort_id', $this->resort->resort_id)
            ->where(function ($q) use ($gmRank) {
                $q->where('rank', $gmRank)
                  ->orWhereHas('position', fn($query) => $query->where('Rank', $gmRank));
            })
            ->get();

        if ($gmApprover) {
            $transferApprovalFlow->push([
                'approver' => $gmApprover,
                'rank' => 'GM',
                'pool' => $gmPool,
            ]);
        }

        // Create approval entries + fan out notifications to the WHOLE pool
        // for each role, so e.g. Finance HOD + DOF both see the request in
        // their notification feed.
        $transferApprovalFlow->each(function ($approver) use ($transfer) {
            EmployeeTransferApproval::create([
                'transfer_id' => $transfer->id,
                'approved_by' => $approver['approver']->id,
                'status' => 'Pending',
                'approval_rank'  => $approver['rank'],
            ]);

            $msg = "📢 New Transfer Request Submitted\n👤 Employee: " . optional($transfer->employee->resortAdmin)->full_name .
            "\n🏢 From: " . optional($transfer->currentDepartment)->name .
            "\n➡️ To: " . optional($transfer->targetDepartment)->name .
            "\n📅 Effective Date: " . Carbon::parse($transfer->effective_date)->format('d M Y') .
            "\n📝 Status: Pending Approval";

            $pool = $approver['pool'] ?? collect([$approver['approver']]);
            foreach ($pool as $member) {
                event(new ResortNotificationEvent(Common::nofitication(
                    $this->resort->resort_id,
                    10,
                    'Transfer Request Notification',
                    $msg,
                    $transfer->id,
                    $member->id,
                    'People'
                )));
            }
        });

        // Awareness notifications — Finance/GM get the *approval* request
        // above; the affected department leads need a heads-up that a
        // transfer is in flight (so the receiving HOD/EXCOM aren't blindsided
        // when the employee shows up later). Errors here are non-fatal —
        // the transfer is already saved.
        try {
            $this->dispatchOnSubmitNotifications($transfer);
        } catch (\Throwable $e) {
            \Log::warning('Transfer on-submit dept notifications failed for #' . $transfer->id . ': ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Transfer request submitted.',
            'redirect_url' => route('people.transfer.list')
        ]);
    }

    /**
     * Item 1 — Determine whether the target position has an open seat.
     *
     * A position is considered "vacant" (transfer-allowed) when the number of
     * employees actively occupying it is below its budgeted headcount.
     * Budgeted headcount is taken from ResortPosition.no_of_positions and, if
     * available, cross-checked against the current month's manning data.
     * The transferred employee themselves is excluded from the filled count
     * (re-transferring within the same position would otherwise self-block).
     *
     * @return array{vacant: bool, message: string}
     */
    private function isTargetPositionVacant(int $targetPositionId, int $employeeId): array
    {
        $resortId = $this->resort->resort_id;

        $position = ResortPosition::where('resort_id', $resortId)
            ->find($targetPositionId);

        if (!$position) {
            return ['vacant' => false, 'message' => 'Target position not found.'];
        }

        // --- Primary source: Workforce Planning (manning_responses + position_monthly_data).
        // This is what the Workforce Planning module reports as the authoritative
        // vacant count. Use the most recent manning year that has a row for this
        // position; if there is genuine manning data we let it override the
        // simpler resort_positions.no_of_positions fallback.
        $manningYear = (int) (DB::table('manning_responses')
            ->where('resort_id', $resortId)
            ->where('year', now()->year)
            ->exists()
            ? now()->year
            : DB::table('manning_responses')->where('resort_id', $resortId)->max('year'));

        if ($manningYear) {
            $seat = DB::table('position_monthly_data as pmd')
                ->join('manning_responses as mr', 'mr.id', '=', 'pmd.manning_response_id')
                ->where('mr.resort_id', $resortId)
                ->where('mr.year', $manningYear)
                ->where('pmd.position_id', $targetPositionId)
                ->selectRaw('MAX(pmd.headcount) as budgeted, MAX(pmd.filledcount) as filled, MAX(pmd.vacantcount) as vacant')
                ->first();

            if ($seat && $seat->budgeted !== null) {
                $budgeted = (int) $seat->budgeted;
                $filled   = (int) $seat->filled;
                $vacant   = (int) $seat->vacant;

                // If the employee being moved is *already* in this position,
                // they shouldn't count against the filled tally.
                $alreadyHere = Employee::where('resort_id', $resortId)
                    ->where('Position_id', $targetPositionId)
                    ->where('id', $employeeId)
                    ->where('status', 'Active')
                    ->exists();
                if ($alreadyHere) {
                    $filled = max(0, $filled - 1);
                    $vacant = max(0, $vacant + 1);
                }

                if ($vacant <= 0 || $filled >= $budgeted) {
                    return [
                        'vacant'  => false,
                        'message' => 'No vacant seat for this position in Workforce Planning ('
                            . $filled . '/' . $budgeted . ' filled, ' . $vacant . ' vacant for ' . $manningYear . ').',
                    ];
                }

                return ['vacant' => true, 'message' => 'Target position has '.$vacant.' vacant seat(s).'];
            }
        }

        // --- Fallback: no manning row for this position — rely on the simple
        // resort_positions.no_of_positions cap. When unset, default to 1 seat
        // so the gate is never silently bypassed.
        $budgetedHeadcount = (int) ($position->no_of_positions ?? 0);
        if ($budgetedHeadcount <= 0) {
            $budgetedHeadcount = 1;
        }

        $filledCount = Employee::where('resort_id', $resortId)
            ->where('Position_id', $targetPositionId)
            ->where('status', 'Active')
            ->where('id', '!=', $employeeId)
            ->count();

        if ($filledCount >= $budgetedHeadcount) {
            return [
                'vacant'  => false,
                'message' => 'The selected target position is already fully occupied ('
                    . $filledCount . '/' . $budgetedHeadcount
                    . ' filled). Transfer into an actively filled position is not allowed.',
            ];
        }

        return ['vacant' => true, 'message' => 'Target position has an available seat.'];
    }

    /**
     * Item 2 — Resolve the budgeted basic salary for a target position.
     *
     * Source: `resort_vacant_budget_costs` (per position / department / year)
     * which stores `basic_salary` for budgeted vacant positions. Uses the
     * current year, falling back to the most recent year on record.
     * Returns null when no budget row exists for the position.
     */
    private function getBudgetedSalary(int $departmentId, int $positionId): ?float
    {
        $row = DB::table('resort_vacant_budget_costs')
            ->where('resort_id', $this->resort->resort_id)
            ->where('position_id', $positionId)
            ->where('department_id', $departmentId)
            ->orderByRaw('CASE WHEN year = ? THEN 0 ELSE 1 END', [now()->year])
            ->orderByDesc('year')
            ->first();

        if ($row) {
            $salary = (float) ($row->current_salary ?: $row->basic_salary);
            if ($salary > 0) {
                return $salary;
            }
        }

        // Fallback — most resorts have very few rows in resort_vacant_budget_costs
        // so the lookup above misses most positions. Use the average basic
        // salary of currently-active employees in that position as a sensible
        // proxy. Better than showing "No budgeted salary on record" for HR.
        $avg = (float) Employee::where('resort_id', $this->resort->resort_id)
            ->where('Position_id', $positionId)
            ->where('status', 'Active')
            ->whereNotNull('basic_salary')
            ->avg('basic_salary');

        return $avg > 0 ? round($avg, 2) : null;
    }

    /**
     * Item 2 — AJAX endpoint: returns the budgeted salary for a target
     * department + position so the initiate form can display it live.
     */
    public function getPositionBudgetInfo(Request $request)
    {
        $departmentId = (int) $request->input('target_dep');
        $positionId   = (int) $request->input('target_pos');
        $employeeId   = (int) $request->input('employee_id');

        if (!$departmentId || !$positionId) {
            return response()->json(['success' => false, 'budgeted_salary' => null]);
        }

        $budgetedSalary = $this->getBudgetedSalary($departmentId, $positionId);

        // Live vacancy preview — runs the same gate as store() so HR sees the
        // verdict the moment they pick the target position rather than at
        // submit time. Employee id is needed so re-transferring within the
        // same position isn't reported as "fully filled" by the gate.
        $vacancy = $employeeId
            ? $this->isTargetPositionVacant($positionId, $employeeId)
            : null;

        return response()->json([
            'success'         => true,
            'budgeted_salary' => $budgetedSalary,
            'vacancy'         => $vacancy,
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
            ])->where('resort_id', $this->resort->resort_id)
              ->whereIn('status',['Pending','On Hold'])->select('*');

            // Item 4 — department scoping. HR / GM (full data access) see every
            // resort transfer. Other HOD/XCOM users see only transfers that
            // involve their own department (as source OR target), plus any
            // transfer they are personally an approver on, so Finance/GM
            // approvers never lose visibility of their approval queue.
            $scopedDeptIds = \App\Helpers\Common::getScopedDepartmentIds();
            if (is_array($scopedDeptIds)) {
                $loggedInEmployeeId = $this->resort->GetEmployee->id ?? 0;
                $transfers->where(function ($q) use ($scopedDeptIds, $loggedInEmployeeId) {
                    $q->whereIn('current_department_id', $scopedDeptIds)
                      ->orWhereIn('target_department_id', $scopedDeptIds)
                      ->orWhereHas('approvals', function ($aq) use ($loggedInEmployeeId) {
                          $aq->where('approved_by', $loggedInEmployeeId);
                      });
                });
            }

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
               ->addColumn('actions', function ($row) {
                    // Role-pool aware: any qualifying Finance / GM employee
                    // (not only the one specifically picked by store()) can act
                    // on the matching pending approval.
                    //
                    // Note: $edit_class is NOT applied here. That class gates
                    // who can *initiate* a transfer (the Transfer Initiate page
                    // permission). Acting on an approval the user is authorised
                    // for is a separate authority — approvalForUser() is the
                    // only gate. Otherwise an approver had to be given an
                    // unrelated page permission just to click their own
                    // pending approval.
                    $loggedInEmployee = $this->resort->GetEmployee ?? null;
                    $myApproval = $loggedInEmployee
                        ? $this->approvalForUser($row, $loggedInEmployee)
                        : null;

                    $viewUrl = route('people.transfer.show', base64_encode($row->id));
                    $viewBtn = '<a href="' . $viewUrl . '" class="btn-tableIcon btnIcon-skyblue" title="View Details"><i class="fa-regular fa-eye"></i></a>';

                    if (!$myApproval) {
                        // No approval authority — still allow viewing details.
                        return '<div class="d-flex align-items-center gap-2">' . $viewBtn . '</div>';
                    }

                    return '
                        <div class="d-flex align-items-center gap-2">
                            <a href="#" class="correct-btn transfer-action" data-id="' . $row->id . '" data-approvedBy="' . $myApproval->approved_by . '" data-action="Approved" title="Approve"><i class="fa-solid fa-check"></i></a>
                            <a href="#" class="btn-tableIcon btnIcon-orangeDark transfer-action" data-id="' . $row->id . '" data-approvedBy="' . $myApproval->approved_by . '" data-action="On Hold" title="Put on Hold"><i class="fa-regular fa-hand"></i></a>
                            <a href="#" class="close-btn transfer-action" data-id="' . $row->id . '" data-action="Rejected" data-approvedBy="' . $myApproval->approved_by . '" title="Reject"><i class="fa-solid fa-xmark"></i></a>'
                            . $viewBtn .
                        '</div>';
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
            ])->where('resort_id', $this->resort->resort_id)->select('*');

            // Item 4 — same department scoping as the list screen.
            $scopedDeptIds = \App\Helpers\Common::getScopedDepartmentIds();
            if (is_array($scopedDeptIds)) {
                $loggedInEmployeeId = $this->resort->GetEmployee->id ?? 0;
                $transfers->where(function ($q) use ($scopedDeptIds, $loggedInEmployeeId) {
                    $q->whereIn('current_department_id', $scopedDeptIds)
                      ->orWhereIn('target_department_id', $scopedDeptIds)
                      ->orWhereHas('approvals', function ($aq) use ($loggedInEmployeeId) {
                          $aq->where('approved_by', $loggedInEmployeeId);
                      });
                });
            }

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
                ->addColumn('actions', function ($row) {
                    // History is read-only — just View Details (which shows
                    // rejection reason, approvals timeline, salary,
                    // allowances/benefit grid, letter download).
                    $viewUrl = route('people.transfer.show', base64_encode($row->id));
                    return '<a href="' . $viewUrl . '" class="btn-tableIcon btnIcon-skyblue" title="View Details"><i class="fa-regular fa-eye"></i></a>';
                })
                ->rawColumns(['employee_name', 'effective_date', 'status', 'reason_for_transfer', 'actions'])
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
        // Whitelist the action so a malformed URL can't write a junk value
        // into the approval enum (and bypass the dispatch branches below).
        $actionName = $action;
        if (!in_array($actionName, ['Approved', 'Rejected', 'On Hold'], true)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid action.',
            ], 422);
        }

        $transfer = EmployeeTransfer::with(['approvals', 'employee', 'targetPosition', 'targetDepartment'])->findOrFail($id);
        $comments = $request->input('reason', null);
        $currentEmployee = $this->resort->GetEmployee;

        $currentApproval = $transfer->approvals()
            ->where('approved_by', $currentEmployee->id)
            ->whereIn('status', ['Pending', 'On Hold'])
            ->first();

        $delegateComment = '';
        if (!$currentApproval) {
            // Eager-load the user's position once — both delegation and the
            // role-pool fallback below reference it.
            $currentEmployee->loadMissing('position');

            // Role-pool fallback FIRST — any Finance / GM team member can act
            // on an approval assigned to their role, not only the one specific
            // employee picked by store()->first() at creation time. Iterate in
            // id order so the earliest open approval wins.
            $pendingApprovals = $transfer->approvals()
                ->whereIn('status', ['Pending', 'On Hold'])
                ->orderBy('id')
                ->get();
            foreach ($pendingApprovals as $pa) {
                if ($pa->approval_rank === 'Finance' && $this->isFinanceApprover($currentEmployee)) {
                    $currentApproval = $pa;
                    $delegateComment = ' (Acted by Finance role: ' . optional($currentEmployee->resortAdmin)->full_name . ')';
                    break;
                }
                if ($pa->approval_rank === 'GM' && $this->isGmApprover($currentEmployee)) {
                    $currentApproval = $pa;
                    $delegateComment = ' (Acted by GM role: ' . optional($currentEmployee->resortAdmin)->full_name . ')';
                    break;
                }
            }

            // Then fall back to on-leave delegation authority.
            if (!$currentApproval) {
                foreach ($pendingApprovals as $pa) {
                    if (\App\Helpers\Common::hasDelegationAuthority($currentEmployee->id, $pa->approved_by, $this->resort->resort_id)) {
                        $currentApproval = $pa;
                        $delegateComment = ' (Acted by delegate)';
                        break;
                    }
                }
            }
        }

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
            'remarks' => ($comments ?? '') . $delegateComment,
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

            // 🔔 Per-stage HR notification.
            //
            // Previously HR only heard about a transfer at submit time and
            // again at FINAL approval (via dispatchPostApprovalNotifications).
            // That meant when Finance approved first, HR was silent until GM
            // also approved — so Finance's milestone was invisible to HR.
            // Fan out an intermediate notification to every HR dept lead so
            // HR sees each approval as it happens.
            if ($pending > 0 && $onHold === 0 && $rejected === 0) {
                try {
                    $hrDept = ResortDepartment::where('resort_id', $this->resort->resort_id)
                        ->get()
                        ->first(fn($d) => \App\Helpers\Common::isHRDepartment($d->id));
                    if ($hrDept) {
                        $stageName = $currentApproval->approval_rank ?? 'Approver';
                        $actorName = optional($currentEmployee->resortAdmin)->full_name
                            ?: trim((optional($currentEmployee->resortAdmin)->first_name ?? '') . ' ' . (optional($currentEmployee->resortAdmin)->last_name ?? ''));
                        $empName   = optional(optional($transfer->employee)->resortAdmin)->full_name ?? 'Employee';
                        $fromDept  = optional($transfer->currentDepartment)->name ?? '—';
                        $toDept    = optional($transfer->targetDepartment)->name ?? '—';

                        $remainingRanks = $transfer->approvals()
                            ->where('status', 'Pending')
                            ->pluck('approval_rank')
                            ->filter()
                            ->unique()
                            ->implode(', ');

                        $msg = "✅ {$stageName} approval received from {$actorName} for the transfer of {$empName} ({$fromDept} → {$toDept})."
                             . ($remainingRanks !== '' ? "\n⏳ Still pending: {$remainingRanks}." : '');

                        foreach ($this->getDepartmentLeadEmployeeIds((int) $hrDept->id) as $leadId) {
                            event(new ResortNotificationEvent(Common::nofitication(
                                $this->resort->resort_id,
                                10,
                                'Transfer Stage Approved',
                                $msg,
                                $transfer->id,
                                $leadId,
                                'People'
                            )));
                        }
                    }
                } catch (\Throwable $e) {
                    \Log::warning('HR stage-approval notification failed for transfer #' . $transfer->id . ': ' . $e->getMessage());
                }
            }

            /**
             * ✅  Main record becomes Approved **only if**:
             *     – nobody is Pending
             *     – nobody Rejected
             *     – nobody On-Hold
             */
            if ($pending === 0 && $onHold === 0 && $rejected === 0) {

                // Capture the current department BEFORE the profile is mutated,
                // so post-approval notifications reach the *old* department HOD.
                $currentDepartmentId = $transfer->current_department_id ?? $transfer->employee->Dept_id;

                $transfer->status = 'Approved';
                $transfer->save();

                // ✍️ Profile move is now date-gated: only apply now if the
                // effective date has already arrived (today/past). Otherwise
                // the daily scheduler (transfers:notify-effective) applies
                // the move when the effective date rolls around so the
                // employee doesn't appear in the new department early.
                $effectiveDate = $transfer->effective_date
                    ? Carbon::parse($transfer->effective_date)
                    : null;
                if ($effectiveDate && !$effectiveDate->isFuture()) {
                    self::applyTransferToEmployee($transfer);
                    $transfer->effective_day_notified_at = now();
                    $transfer->save();
                }
                // Reload the employee record so subsequent reads in this
                // request reflect whatever we did (or didn't) just write.
                $employee = $transfer->employee()->first() ?: $employee;

                // 🔔 Item 6 — Post-approval notifications.
                $this->dispatchPostApprovalNotifications($transfer, $employee, $currentDepartmentId, $hr);

                // 📄 Item 7 — Generate & email the Transfer Letter.
                try {
                    $this->generateAndSendTransferLetter($transfer->fresh(['employee.resortAdmin', 'targetDepartment', 'targetPosition', 'currentDepartment', 'currentPosition']));
                } catch (\Throwable $e) {
                    \Log::error('Transfer letter generation failed for transfer #' . $transfer->id . ': ' . $e->getMessage());
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
                "⏸️ Your transfer request to " . optional($transfer->targetDepartment)->name . " has been put on hold.",
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

    /* =================================================================
     *  Item 6 — Post-approval notification helpers
     * ================================================================= */

    /**
     * Returns the employee ids of the HOD (rank 2) and EXCOM/XCOM (rank 1)
     * of a given department within the current resort. These two ranks are
     * the department leadership per config('settings.Position_Rank').
     *
     * @return int[]
     */
    /**
     * Pool match — does this employee qualify as a Finance approver for this
     * resort? Uses the same logic store() uses to *pick* the Finance approver
     * (rank = Finance, or position title matches "Director of Finance" /
     * "Finance Manager", or the position's Rank itself is Finance).
     *
     * Used by the Transfer list action column and handleApproval so any
     * Finance team member can act on a Finance approval — not just the one
     * specific employee picked by store()->first() at creation time.
     */
    private function isFinanceApprover($employee): bool
    {
        if (!$employee) return false;
        $financeRank = array_search('Finance', config('settings.Position_Rank'));
        $financeTitles = ['Director of Finance', 'Finance Manager'];

        // Title-based / rank-based match (DOF, Finance Manager, rank=Finance).
        if ($financeRank !== false && (int) $employee->rank === (int) $financeRank) {
            return true;
        }
        $pos = $employee->position ?? null;
        if ($pos) {
            if (in_array($pos->position_title, $financeTitles, true)) return true;
            if ($financeRank !== false && (int) $pos->Rank === (int) $financeRank) return true;
        }

        // Department-based match — Finance dept's HOD (rank=2) and EXCOM
        // (rank=1) are part of the Finance approver pool even if their
        // position title isn't DOF/Finance Manager.
        if (in_array((int) $employee->rank, [1, 2], true)
            && \App\Helpers\Common::isFinanceDepartment($employee->Dept_id ?? null)) {
            return true;
        }
        return false;
    }

    /**
     * Pool match for GM — rank or position Rank = GM (8).
     */
    private function isGmApprover($employee): bool
    {
        if (!$employee) return false;
        $gmRank = array_search('GM', config('settings.Position_Rank'));
        if ($gmRank === false) return false;

        if ((int) $employee->rank === (int) $gmRank) return true;
        $pos = $employee->position ?? null;
        if ($pos && (int) $pos->Rank === (int) $gmRank) return true;
        return false;
    }

    /**
     * Resolve the approval row the logged-in employee may act on for this
     * transfer. Direct assignment wins; otherwise a role-pool match
     * (Finance / GM) so any qualifying team member can step in.
     *
     * Returns null when the user has no actionable approval (delegation is
     * checked separately by handleApproval).
     */
    private function approvalForUser(EmployeeTransfer $transfer, $employee)
    {
        if (!$employee) return null;

        // 1) Direct assignment — the original picked approver.
        $direct = $transfer->approvals->firstWhere('approved_by', $employee->id);
        if ($direct) return $direct;

        // 2) Role-pool match on an open approval row.
        foreach ($transfer->approvals as $a) {
            if (!in_array($a->status, ['Pending', 'On Hold'], true)) continue;
            if ($a->approval_rank === 'Finance' && $this->isFinanceApprover($employee)) return $a;
            if ($a->approval_rank === 'GM'      && $this->isGmApprover($employee))      return $a;
        }
        return null;
    }

    private function getDepartmentLeadEmployeeIds(?int $departmentId): array
    {
        if (!$departmentId) {
            return [];
        }

        $hodRank   = array_search('HOD', config('settings.Position_Rank'));   // 2
        $excomRank = array_search('EXCOM', config('settings.Position_Rank')); // 1

        return Employee::where('resort_id', $this->resort->resort_id)
            ->where('Dept_id', $departmentId)
            ->where('status', 'Active')
            ->whereIn('rank', array_filter([$hodRank, $excomRank], fn($r) => $r !== false))
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();
    }

    /**
     * Sends a single in-app transfer notification to one employee id,
     * following the app's existing event(new ResortNotificationEvent(...))
     * convention. Skips silently when $employeeId is empty.
     */
    private function notifyEmployee($employeeId, string $title, string $message, $requestId = 0): void
    {
        if (empty($employeeId)) {
            return;
        }

        event(new ResortNotificationEvent(Common::nofitication(
            $this->resort->resort_id,
            10,
            $title,
            $message,
            $requestId,
            $employeeId,
            'People'
        )));
    }

    /**
     * On submit, fan out an "awareness" notification to the dept leads of
     * the source AND target departments (and HR), so the receiving
     * HOD/EXCOM hear about the transfer the moment it's initiated — not
     * only after Finance + GM both finalize. Approvers themselves are
     * notified separately in store().
     */
    private function dispatchOnSubmitNotifications(EmployeeTransfer $transfer): void
    {
        $employee = $transfer->employee;
        if (!$employee) {
            return;
        }
        $employeeName = optional($employee->resortAdmin)->full_name ?? 'Employee';
        $fromDept = optional($transfer->currentDepartment)->name ?? 'previous department';
        $toDept   = optional($transfer->targetDepartment)->name ?? 'new department';
        $effective = $transfer->effective_date
            ? Carbon::parse($transfer->effective_date)->format('d M Y')
            : 'TBD';

        // Skip notifying the employee themselves if they happen to be a
        // dept lead in either dept.
        $employeeId = (int) $employee->id;

        // Current dept leads — heads-up that one of their employees is being
        // transferred OUT.
        foreach ($this->getDepartmentLeadEmployeeIds((int) $transfer->current_department_id) as $leadId) {
            if ($leadId === $employeeId) { continue; }
            $this->notifyEmployee(
                $leadId,
                'Employee Transfer Initiated',
                "📤 Transfer initiated: {$employeeName} ({$fromDept} → {$toDept}, effective {$effective}). Pending Finance + GM approval.",
                $transfer->id
            );
        }

        // Target dept leads — the receiving HOD/EXCOM need to know an
        // incoming employee is in the pipeline.
        foreach ($this->getDepartmentLeadEmployeeIds((int) $transfer->target_department_id) as $leadId) {
            if ($leadId === $employeeId) { continue; }
            $this->notifyEmployee(
                $leadId,
                'Incoming Employee Transfer (Pending)',
                "📥 Incoming transfer: {$employeeName} (from {$fromDept}, effective {$effective}). Pending Finance + GM approval.",
                $transfer->id
            );
        }

        // HR dept leads — full visibility of every transfer in flight.
        $hrDepartment = ResortDepartment::where('resort_id', $this->resort->resort_id)
            ->get()
            ->first(fn($d) => \App\Helpers\Common::isHRDepartment($d->id));
        if ($hrDepartment) {
            foreach ($this->getDepartmentLeadEmployeeIds((int) $hrDepartment->id) as $leadId) {
                if ($leadId === $employeeId) { continue; }
                $this->notifyEmployee(
                    $leadId,
                    'Transfer Request Submitted',
                    "📢 New transfer request: {$employeeName} ({$fromDept} → {$toDept}, effective {$effective}).",
                    $transfer->id
                );
            }
        }
    }

    /**
     * Item 6 — On final approval, notify:
     *   (a) Current Department HOD/EXCOM
     *   (b) New Department HOD/EXCOM
     *   (c) HR Department HOD/EXCOM
     *   (d) the transferred employee
     * Plus the initiating HR user (existing behaviour, kept).
     */
    private function dispatchPostApprovalNotifications(EmployeeTransfer $transfer, $employee, $currentDepartmentId, $hr): void
    {
        $employeeName = optional($employee->resortAdmin)->full_name ?? 'Employee';
        $fromDept = optional($transfer->currentDepartment)->name ?? 'previous department';
        $toDept   = optional($transfer->targetDepartment)->name ?? 'new department';
        // Transfer effective date — shown to every recipient so the receiving
        // HOD, current HOD, HR and the employee all know when the move
        // actually takes effect. Falls back to a placeholder when unset.
        $effectiveDate = $transfer->effective_date
            ? Carbon::parse($transfer->effective_date)->format('d M Y')
            : 'TBD';
        $temporaryNote = '';
        if ($transfer->transfer_status === 'Temporary' && $transfer->temporary_from && $transfer->temporary_to) {
            $temporaryNote = ' (Temporary: '
                . Carbon::parse($transfer->temporary_from)->format('d M Y')
                . ' → ' . Carbon::parse($transfer->temporary_to)->format('d M Y') . ')';
        }

        // (d) Transferred employee
        $this->notifyEmployee(
            $employee->id,
            'Transfer Approved',
            "🎉 Your transfer from {$fromDept} to {$toDept} has been fully approved. Effective date: {$effectiveDate}.{$temporaryNote}",
            $transfer->id
        );

        // (a) Current Department HOD/EXCOM
        foreach ($this->getDepartmentLeadEmployeeIds((int) $currentDepartmentId) as $leadId) {
            if ($leadId === (int) $employee->id) { continue; }
            $this->notifyEmployee(
                $leadId,
                'Employee Transfer Out',
                "📢 {$employeeName} has been transferred out of {$fromDept} to {$toDept}. Effective date: {$effectiveDate}.{$temporaryNote}",
                $transfer->id
            );
        }

        // (b) New Department HOD/EXCOM
        foreach ($this->getDepartmentLeadEmployeeIds((int) $transfer->target_department_id) as $leadId) {
            if ($leadId === (int) $employee->id) { continue; }
            $this->notifyEmployee(
                $leadId,
                'Incoming Employee Transfer',
                "📢 {$employeeName} has been transferred into {$toDept} from {$fromDept}. Effective date: {$effectiveDate}.{$temporaryNote}",
                $transfer->id
            );
        }

        // (c) HR Department HOD/EXCOM — resolve the HR department, then its leads.
        $hrDepartment = ResortDepartment::where('resort_id', $this->resort->resort_id)
            ->get()
            ->first(fn($d) => \App\Helpers\Common::isHRDepartment($d->id));

        if ($hrDepartment) {
            foreach ($this->getDepartmentLeadEmployeeIds((int) $hrDepartment->id) as $leadId) {
                $this->notifyEmployee(
                    $leadId,
                    'Employee Transfer Finalized',
                    "📢 Transfer for {$employeeName} ({$fromDept} → {$toDept}) has been approved and the profile updated. Effective date: {$effectiveDate}.{$temporaryNote}",
                    $transfer->id
                );
            }
        }

        // Initiating HR user (existing behaviour retained).
        if ($hr) {
            $this->notifyEmployee(
                $hr->id,
                'Employee Transfer Finalized',
                "📢 Transfer for {$employeeName} has been approved and profile updated. Effective date: {$effectiveDate}.{$temporaryNote}",
                $transfer->id
            );
        }
    }

    /**
     * Apply the transfer to the Employee record — Dept / Section / Position /
     * Division / Rank / Reporting To. Pulled out of handleApproval() so the
     * profile move can be deferred to the effective date instead of happening
     * the moment Finance + GM both approve. Called either by handleApproval()
     * (when the effective date is already today/past) or by the daily
     * scheduler when the effective date arrives.
     */
    public static function applyTransferToEmployee(EmployeeTransfer $transfer): void
    {
        $transfer->loadMissing(['employee', 'targetDepartment', 'targetPosition']);

        $employee = $transfer->employee;
        if (!$employee) return;

        // Snapshot the employee's pre-move state so a Temporary transfer can
        // revert exactly (Dept / Section / Position / Division / Rank /
        // Reporting To / Basic Salary). Only written once per transfer — re-
        // applying the same row (idempotency) must not clobber the original
        // snapshot with the now-target values.
        if (empty($transfer->pre_transfer_snapshot)) {
            $transfer->pre_transfer_snapshot = [
                'Dept_id'      => $employee->Dept_id,
                'Section_id'   => $employee->Section_id,
                'Position_id'  => $employee->Position_id,
                'division_id'  => $employee->division_id,
                'reporting_to' => $employee->reporting_to,
                'rank'         => $employee->rank,
                'basic_salary' => $employee->basic_salary,
            ];
            // Persist the snapshot before the move so a mid-write failure
            // doesn't leave an unrevertable temp transfer.
            $transfer->save();
        }

        // Capture the source dept/position BEFORE overwriting — the manning
        // sync uses these to decrement the source's filledcount.
        $sourceDeptId = $employee->Dept_id;
        $sourcePosId  = $employee->Position_id;

        $employee->Dept_id      = $transfer->target_department_id;
        $employee->Position_id  = $transfer->target_position_id;
        // Clear stale Section_id when the target dept has no section picked.
        $employee->Section_id   = $transfer->target_section_id ?: null;
        $employee->division_id  = optional($transfer->targetDepartment)->division_id ?? $employee->division_id;
        $employee->reporting_to = $transfer->reporting_manager ?? $employee->reporting_to;
        if ($transfer->targetPosition && $transfer->targetPosition->Rank !== null) {
            $employee->rank = $transfer->targetPosition->Rank;
        }
        // Apply the new salary set during initiation. Skip when the form left
        // it blank so we never accidentally zero an employee's salary.
        if ($transfer->proposed_salary !== null && $transfer->proposed_salary !== '') {
            $employee->basic_salary = $transfer->proposed_salary;
        }
        $employee->save();

        // Workforce Planning / Manning sync — keep filledcount / vacantcount
        // honest on both ends of the move (per-position-month rows AND the
        // dept totals on manning_responses).
        self::adjustManningCount((int) $transfer->resort_id, (int) $sourceDeptId, (int) $sourcePosId, -1);
        self::adjustManningCount((int) $transfer->resort_id, (int) $transfer->target_department_id, (int) $transfer->target_position_id, +1);
    }

    /**
     * Push a +1 / -1 delta through the manning numbers for one dept+position
     * in the resort's current-year manning (falls back to the latest year
     * if no current-year row exists).
     *
     *   filledcount += $filledDelta   (clamped to [0, headcount])
     *   vacantcount mirrors so filled + vacant = headcount
     *
     * The same delta is applied to manning_responses.total_filled_positions /
     * total_vacant_positions for the dept so the Workforce Planning cards
     * stay in sync.
     */
    private static function adjustManningCount(int $resortId, int $deptId, int $positionId, int $filledDelta): void
    {
        if ($deptId <= 0 || $positionId <= 0 || $filledDelta === 0) return;

        $year = (int) date('Y');
        $manning = \DB::table('manning_responses')
            ->where('resort_id', $resortId)
            ->where('dept_id', $deptId)
            ->where('year', $year)
            ->first(['id', 'total_filled_positions', 'total_vacant_positions']);

        if (!$manning) {
            $manning = \DB::table('manning_responses')
                ->where('resort_id', $resortId)
                ->where('dept_id', $deptId)
                ->orderByDesc('year')
                ->first(['id', 'total_filled_positions', 'total_vacant_positions']);
        }
        if (!$manning) return; // no manning configured for this dept — nothing to update

        // Per-month rows for this position.
        $rows = \DB::table('position_monthly_data')
            ->where('manning_response_id', $manning->id)
            ->where('position_id', $positionId)
            ->get(['id', 'headcount', 'vacantcount', 'filledcount']);

        foreach ($rows as $row) {
            $head     = (int) ($row->headcount ?? 0);
            $newFilled = max(0, (int) $row->filledcount + $filledDelta);
            if ($head > 0) {
                $newFilled = min($newFilled, $head);
            }
            $newVacant = $head > 0
                ? max(0, $head - $newFilled)
                : max(0, (int) $row->vacantcount - $filledDelta);

            \DB::table('position_monthly_data')
                ->where('id', $row->id)
                ->update([
                    'filledcount' => $newFilled,
                    'vacantcount' => $newVacant,
                    'updated_at'  => now(),
                ]);
        }

        // Dept-level totals on the manning_responses card.
        \DB::table('manning_responses')->where('id', $manning->id)->update([
            'total_filled_positions' => max(0, (int) $manning->total_filled_positions + $filledDelta),
            'total_vacant_positions' => max(0, (int) $manning->total_vacant_positions - $filledDelta),
            'updated_at'             => now(),
        ]);
    }

    /**
     * Revert a Temporary transfer — restores the employee's Dept / Section /
     * Position / Division / Rank / Reporting To from the snapshot captured
     * when applyTransferToEmployee() first ran. Idempotent via reverted_at:
     * the daily scheduler only picks up rows where it is null.
     */
    public static function revertTemporaryTransfer(EmployeeTransfer $transfer): void
    {
        $transfer->loadMissing(['employee.resortAdmin', 'currentDepartment', 'targetDepartment']);

        $employee = $transfer->employee;
        if (!$employee) return;

        $snapshot = is_array($transfer->pre_transfer_snapshot)
            ? $transfer->pre_transfer_snapshot
            : [];

        if (empty($snapshot)) {
            // Without a snapshot we can't revert safely — record that the
            // window passed and skip silently. (Snapshots are captured for
            // every apply going forward; this only matters for legacy temp
            // transfers that pre-date this column.)
            $transfer->reverted_at = now();
            $transfer->save();
            return;
        }

        // Restore each field that was snapshot. Use array_key_exists so a
        // legitimately-null original value (e.g. no section) is restored.
        // basic_salary is included so temp transfers with a proposed_salary
        // also revert to the pre-transfer pay on the temporary_to date.
        foreach (['Dept_id','Section_id','Position_id','division_id','reporting_to','rank','basic_salary'] as $col) {
            if (array_key_exists($col, $snapshot)) {
                $employee->{$col} = $snapshot[$col];
            }
        }
        $employee->save();

        // Manning sync — mirror image of the apply: take 1 off the temp
        // dept/position they were in, give it back to the dept/position
        // from the snapshot.
        $resortId      = (int) $transfer->resort_id;
        $snapshotDept  = (int) ($snapshot['Dept_id']     ?? 0);
        $snapshotPos   = (int) ($snapshot['Position_id'] ?? 0);
        if ($snapshotDept > 0 && $snapshotPos > 0) {
            self::adjustManningCount($resortId, (int) $transfer->target_department_id, (int) $transfer->target_position_id, -1);
            self::adjustManningCount($resortId, $snapshotDept, $snapshotPos, +1);
        }

        $transfer->reverted_at = now();
        $transfer->save();

        // Fan-out revert notifications to the same 4 audiences. We message
        // them in terms of the *current dept* (which is now the original
        // dept again) and the dept they were temporarily in (the target).
        $employeeName = optional($employee->resortAdmin)->full_name ?? 'Employee';
        $originalDept = optional($transfer->currentDepartment)->name ?? 'their original department';
        $tempDept     = optional($transfer->targetDepartment)->name ?? 'the temporary department';
        $tempToDate   = $transfer->temporary_to
            ? \Carbon\Carbon::parse($transfer->temporary_to)->format('d M Y')
            : 'today';

        $resortId = $transfer->resort_id;
        $push = function ($recipientId, string $title, string $message) use ($resortId, $transfer) {
            if (empty($recipientId)) return;
            event(new ResortNotificationEvent(\App\Helpers\Common::nofitication(
                $resortId, 10, $title, $message, $transfer->id, $recipientId, 'People'
            )));
        };

        $hodRank   = array_search('HOD',   config('settings.Position_Rank'));
        $excomRank = array_search('EXCOM', config('settings.Position_Rank'));
        $leadRanks = array_filter([$hodRank, $excomRank], fn($r) => $r !== false);

        // (1) Employee
        $push(
            $employee->id,
            'Temporary Transfer Ended',
            "↩️ Your temporary transfer to {$tempDept} ended on {$tempToDate}. You have returned to {$originalDept}."
        );

        // (2) The dept they're returning TO — current leads
        $returnLeads = Employee::where('resort_id', $resortId)
            ->where('Dept_id', $employee->Dept_id) // post-revert dept
            ->where('status', 'Active')
            ->whereIn('rank', $leadRanks)
            ->pluck('id');
        foreach ($returnLeads as $leadId) {
            if ((int) $leadId === (int) $employee->id) continue;
            $push(
                $leadId,
                'Employee Returning From Temporary Transfer',
                "↩️ {$employeeName} has returned to {$originalDept} after their temporary assignment to {$tempDept} ended ({$tempToDate})."
            );
        }

        // (3) The dept they're leaving — target leads
        $tempDeptLeads = Employee::where('resort_id', $resortId)
            ->where('Dept_id', $transfer->target_department_id)
            ->where('status', 'Active')
            ->whereIn('rank', $leadRanks)
            ->pluck('id');
        foreach ($tempDeptLeads as $leadId) {
            if ((int) $leadId === (int) $employee->id) continue;
            $push(
                $leadId,
                'Temporary Transfer Ended',
                "↩️ {$employeeName}'s temporary assignment to {$tempDept} ended ({$tempToDate}). They have returned to {$originalDept}."
            );
        }

        // (4) HR dept leads
        $hrDept = ResortDepartment::where('resort_id', $resortId)->get()
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
                    'Temporary Transfer Ended',
                    "↩️ {$employeeName}'s temporary transfer (to {$tempDept}) ended on {$tempToDate}. Profile restored to {$originalDept}."
                );
            }
        }
    }

    /**
     * Day-of notifications — fired by the daily scheduler when a transfer's
     * effective_date is today. Tells the employee + current/new/HR dept
     * leads that the move is happening today.
     *
     * Idempotent via $transfer->effective_day_notified_at — the command
     * only picks up transfers where it is still null.
     *
     * Static + accepts a resort_id so the scheduler can drive it without
     * authenticating a resort-admin (it loops every resort and bypasses
     * $this->resort, which is the request-scoped logged-in user).
     */
    public static function dispatchEffectiveDateNotifications(EmployeeTransfer $transfer): void
    {
        $transfer->loadMissing([
            'employee.resortAdmin',
            'currentDepartment',
            'targetDepartment',
            'targetPosition',
        ]);

        // ★ Apply the profile move FIRST, then notify. handleApproval gates
        // this by date so on the effective day the scheduler is the surface
        // that actually moves the employee — they don't appear in the new
        // department before that date.
        self::applyTransferToEmployee($transfer);

        $employee = $transfer->employee()->first(); // reload after the move
        if (!$employee) return;

        $employeeName = optional($employee->resortAdmin)->full_name ?? 'Employee';
        $fromDept = optional($transfer->currentDepartment)->name ?? 'previous department';
        $toDept   = optional($transfer->targetDepartment)->name ?? 'new department';
        $effectiveDate = $transfer->effective_date
            ? Carbon::parse($transfer->effective_date)->format('d M Y')
            : 'today';

        $temporaryNote = '';
        if ($transfer->transfer_status === 'Temporary' && $transfer->temporary_from && $transfer->temporary_to) {
            $temporaryNote = ' (Temporary: '
                . Carbon::parse($transfer->temporary_from)->format('d M Y')
                . ' → ' . Carbon::parse($transfer->temporary_to)->format('d M Y') . ')';
        }

        $resortId = $transfer->resort_id;

        // Helper — same payload shape as notifyEmployee() but static-safe
        // (no $this binding to the resort-admin guard).
        $push = function ($recipientId, string $title, string $message) use ($resortId, $transfer) {
            if (empty($recipientId)) return;
            event(new ResortNotificationEvent(Common::nofitication(
                $resortId,
                10,
                $title,
                $message,
                $transfer->id,
                $recipientId,
                'People'
            )));
        };

        // (1) The employee
        $push(
            $employee->id,
            'Transfer Effective Today',
            "📌 Reminder — your transfer to {$toDept} takes effect today ({$effectiveDate}).{$temporaryNote}"
        );

        // (2) Current dept HOD/EXCOM
        $hodRank   = array_search('HOD',   config('settings.Position_Rank'));
        $excomRank = array_search('EXCOM', config('settings.Position_Rank'));
        $leadRanks = array_filter([$hodRank, $excomRank], fn($r) => $r !== false);

        $currentLeads = Employee::where('resort_id', $resortId)
            ->where('Dept_id', $transfer->current_department_id)
            ->where('status', 'Active')
            ->whereIn('rank', $leadRanks)
            ->pluck('id');
        foreach ($currentLeads as $leadId) {
            if ((int) $leadId === (int) $employee->id) continue;
            $push(
                $leadId,
                'Employee Transfer Effective Today',
                "📌 {$employeeName} is transferred out of {$fromDept} to {$toDept} today ({$effectiveDate}).{$temporaryNote}"
            );
        }

        // (3) Target dept HOD/EXCOM
        $targetLeads = Employee::where('resort_id', $resortId)
            ->where('Dept_id', $transfer->target_department_id)
            ->where('status', 'Active')
            ->whereIn('rank', $leadRanks)
            ->pluck('id');
        foreach ($targetLeads as $leadId) {
            if ((int) $leadId === (int) $employee->id) continue;
            $push(
                $leadId,
                'Incoming Employee Today',
                "📌 {$employeeName} joins {$toDept} today ({$effectiveDate}) from {$fromDept}.{$temporaryNote}"
            );
        }

        // (4) HR dept HOD/EXCOM
        $hrDept = ResortDepartment::where('resort_id', $resortId)->get()
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
                    'Transfer Effective Today',
                    "📌 {$employeeName}'s transfer ({$fromDept} → {$toDept}) takes effect today ({$effectiveDate}).{$temporaryNote}"
                );
            }
        }

        $transfer->effective_day_notified_at = now();
        $transfer->save();
    }

    /* =================================================================
     *  Item 7 — Transfer Letter generation
     * ================================================================= */

    /**
     * Generates the Transfer Letter PDF, emails it to the transferred
     * employee, and saves a copy into the File Management module (the
     * employee's categorized folder) titled "Transfer Letter".
     *
     * NOTE: The app has no letterhead-management or e-signature system.
     * The PDF uses the resort logo + name as an approximated letterhead and
     * a typed signatory block. See transfer_letter_pdf.blade.php.
     */
    private function generateAndSendTransferLetter(EmployeeTransfer $transfer): void
    {
        $employee = $transfer->employee;
        if (!$employee) {
            return;
        }

        $resort = Resort::find($transfer->resort_id);
        $employeeName = optional($employee->resortAdmin)->full_name ?? 'Employee';

        // ── 1. Render the PDF ────────────────────────────────────────────
        // Pull the resort's configured Letterhead & E-signature settings.
        // When none is configured, $letterhead['configured'] is false and the
        // template falls back to the resort logo + typed-signature layout.
        $letterhead = Common::getLetterheadData($transfer->resort_id);

        $pdf = Pdf::loadView('resorts.people.transfer.transfer_letter_pdf', [
            'transfer'       => $transfer,
            'resort'         => $resort,
            'resortLogo'     => Common::GetResortLogo($transfer->resort_id),
            'employeeName'   => $employeeName,
            'letterhead'     => $letterhead,
            // Signatory block — prefer configured values, fall back to defaults.
            'signatureImage' => $letterhead['signatureImage'],
            'signatoryName'  => $letterhead['signatoryName'] ?: 'Human Resources Department',
            'signatoryTitle' => $letterhead['signatoryTitle']
                ?: 'For and on behalf of ' . ($resort->resort_name ?? 'the Management'),
        ])->setPaper('a4', 'portrait');

        // Allow DomPDF to load local letterhead image files.
        $pdf->getDomPDF()->getOptions()->set('isRemoteEnabled', true);

        $pdfBinary = $pdf->output();

        // Local copy for emailing + record-keeping.
        $localFileName = 'transfer-letter-' . $transfer->id . '.pdf';
        $localPath = storage_path('app/' . $localFileName);
        file_put_contents($localPath, $pdfBinary);

        // ── 2. Save into the File Management module ──────────────────────
        $savedPath = $this->saveTransferLetterToFileManagement($transfer, $employee, $pdfBinary);

        // ── 3. Persist letter status on the transfer record ──────────────
        $transfer->letter_dispatched    = 'Yes';
        $transfer->transfer_letter_path = $savedPath;
        $transfer->save();

        // ── 4. Email the letter to the transferred employee ─────────────
        $email = optional($employee->resortAdmin)->email;
        if ($email && file_exists($localPath)) {
            $bodyHtml = 'Dear ' . e($employeeName) . ',<br><br>'
                . 'Please find attached your official Transfer Letter. '
                . 'All details of your transfer have been approved and updated in our records.<br><br>'
                . 'Regards,<br>Human Resources Department';

            try {
                \Mail::to($email)->send(new \App\Mail\TransferLetterMail($transfer, $localPath, $bodyHtml, $employeeName));
            } catch (\Throwable $e) {
                \Log::error('Transfer letter email failed for transfer #' . $transfer->id . ': ' . $e->getMessage());
            }
        }
    }

    /**
     * Item 7 — Stores the Transfer Letter PDF into the File Management
     * module under the transferred employee's categorized root folder
     * (named after their Emp_id), mirroring FileManageController's encrypted
     * upload pattern. Returns the storage path, or null on failure.
     */
    private function saveTransferLetterToFileManagement(EmployeeTransfer $transfer, $employee, string $pdfBinary): ?string
    {
        try {
            // The employee's categorized root folder (created by the
            // Employee model `created` hook — named after Emp_id, UnderON 0).
            $folder = \App\Models\FilemangementSystem::where('resort_id', $transfer->resort_id)
                ->where('Folder_Name', $employee->Emp_id)
                ->where('Folder_Type', 'categorized')
                ->where('UnderON', 0)
                ->first();

            if (!$folder) {
                \Log::warning('Transfer letter: no File Management folder for employee ' . $employee->Emp_id);
                return null;
            }

            $resortFolder = optional(optional($this->resort)->resort)->resort_id
                ?? optional(Resort::find($transfer->resort_id))->resort_id;

            $uniqueString = substr(md5(uniqid('transfer-letter', true)), 0, 10);
            $newFileName  = $uniqueString . '.pdf.enc';
            $path = $resortFolder . '/public/categorized/' . $folder->Folder_unique_id . '/' . $newFileName;

            // AES-256-CBC encryption — identical scheme to FileManageController.
            $key = hash('sha256', env('ENCRYPTION_KEY'), true);
            $iv  = random_bytes(16);
            $encrypted = $iv . openssl_encrypt($pdfBinary, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

            if ($encrypted === false) {
                throw new \Exception('Encryption failed: ' . openssl_error_string());
            }

            \App\Helpers\StorageHelper::disk()->put($path, $encrypted, [
                'ContentType'        => 'application/octet-stream',
                'ContentDisposition' => 'attachment; filename="Transfer Letter.pdf"',
            ]);

            \App\Models\ChildFileManagement::create([
                'resort_id'      => $transfer->resort_id,
                'unique_id'      => $uniqueString,
                'Parent_File_ID' => $folder->id,
                'File_Name'      => 'Transfer Letter',
                'NewFileName'    => 'Transfer Letter',
                'File_Type'      => 'pdf',
                'File_Size'      => round(strlen($pdfBinary) / 1024, 2),
                'File_Path'      => $path,
                'File_Extension' => 'pdf',
            ]);

            return $path;
        } catch (\Throwable $e) {
            \Log::error('Transfer letter File Management save failed for transfer #' . $transfer->id . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Item 7 — Re-download a previously generated Transfer Letter PDF.
     * Regenerates from the current transfer data so it works even if the
     * stored encrypted copy is unavailable.
     */
    public function show($id)
    {
        $page_title = 'Transfer Details';
        $transferId = base64_decode($id);
        if (!is_numeric($transferId)) {
            abort(404);
        }

        $transfer = EmployeeTransfer::with([
                'employee.resortAdmin',
                'employee.position',
                'employee.department',
                'currentDepartment',
                'currentPosition',
                'targetDepartment',
                'targetPosition',
                'reporting.resortAdmin',
                'reporting.position',
                'approvals.approver.resortAdmin',
                'approvals.approver.position',
            ])
            ->where('resort_id', $this->resort->resort_id)
            ->findOrFail($transferId);

        // Department-scope guard — same rule as the list page so non-HR/GM
        // HOD/XCOM can only open transfers involving their own department,
        // or transfers they (or a delegate) are an approver on.
        $scopedDeptIds = Common::getScopedDepartmentIds();
        if (is_array($scopedDeptIds)) {
            $myId = $this->resort->GetEmployee->id ?? 0;
            $deptOk = in_array((int) $transfer->current_department_id, $scopedDeptIds, true)
                   || in_array((int) $transfer->target_department_id, $scopedDeptIds, true);
            $approverOk = $transfer->approvals->contains('approved_by', $myId);
            if (!$deptOk && !$approverOk) {
                abort(403, 'You do not have access to this transfer.');
            }
        }

        // Latest Rejected approval row supplies the rejection reason. handleApproval
        // stores the reason on the approval row's `remarks` column, so a rejected
        // transfer surfaces it via the most-recent Rejected approval.
        $rejectionReason = optional(
            $transfer->approvals
                ->where('status', 'Rejected')
                ->sortByDesc('approved_at')
                ->first()
        )->remarks;

        // On-Hold reason — same idea, latest On Hold remarks.
        $onHoldReason = optional(
            $transfer->approvals
                ->where('status', 'On Hold')
                ->sortByDesc('approved_at')
                ->first()
        )->remarks;

        return view('resorts.people.transfer.detail', compact(
            'page_title', 'transfer', 'rejectionReason', 'onHoldReason'
        ));
    }

    public function downloadTransferLetter($id)
    {
        $transfer = EmployeeTransfer::with([
            'employee.resortAdmin', 'targetDepartment', 'targetPosition', 'currentDepartment', 'currentPosition'
        ])->where('resort_id', $this->resort->resort_id)->findOrFail($id);

        $resort = Resort::find($transfer->resort_id);

        $pdf = Pdf::loadView('resorts.people.transfer.transfer_letter_pdf', [
            'transfer'       => $transfer,
            'resort'         => $resort,
            'resortLogo'     => Common::GetResortLogo($transfer->resort_id),
            'employeeName'   => optional($transfer->employee->resortAdmin)->full_name ?? 'Employee',
            'signatureImage' => null,
            'signatoryName'  => 'Human Resources Department',
            'signatoryTitle' => 'For and on behalf of ' . ($resort->resort_name ?? 'the Management'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('Transfer_Letter_' . ($transfer->employee->Emp_id ?? $transfer->id) . '.pdf');
    }

    /**
     * Inline preview of the Transfer Letter — same PDF as the download
     * endpoint, but streamed so the browser displays it in a new tab
     * instead of forcing a download. Uses the resort's configured
     * letterhead + e-signature so HR sees exactly what the employee
     * received via email.
     */
    public function previewTransferLetter($id)
    {
        $transfer = EmployeeTransfer::with([
            'employee.resortAdmin', 'targetDepartment', 'targetPosition', 'currentDepartment', 'currentPosition'
        ])->where('resort_id', $this->resort->resort_id)->findOrFail($id);

        $resort = Resort::find($transfer->resort_id);
        $letterhead = Common::getLetterheadData($transfer->resort_id);

        $pdf = Pdf::loadView('resorts.people.transfer.transfer_letter_pdf', [
            'transfer'       => $transfer,
            'resort'         => $resort,
            'resortLogo'     => Common::GetResortLogo($transfer->resort_id),
            'employeeName'   => optional($transfer->employee->resortAdmin)->full_name ?? 'Employee',
            'letterhead'     => $letterhead,
            'signatureImage' => $letterhead['signatureImage'],
            'signatoryName'  => $letterhead['signatoryName'] ?: 'Human Resources Department',
            'signatoryTitle' => $letterhead['signatoryTitle']
                ?: 'For and on behalf of ' . ($resort->resort_name ?? 'the Management'),
        ])->setPaper('a4', 'portrait');
        $pdf->getDomPDF()->getOptions()->set('isRemoteEnabled', true);

        $filename = 'Transfer_Letter_' . ($transfer->employee->Emp_id ?? $transfer->id) . '.pdf';
        return $pdf->stream($filename);
    }

    /**
     * Manually (re-)send the Transfer Letter to the transferred employee.
     * Reuses the same generator the final-approval flow uses — so the file
     * lands in File Management, the transfer record is marked dispatched,
     * and the employee receives the email. Safe to call multiple times.
     */
    public function sendTransferLetter($id)
    {
        $transfer = EmployeeTransfer::with([
            'employee.resortAdmin', 'targetDepartment', 'targetPosition', 'currentDepartment', 'currentPosition'
        ])->where('resort_id', $this->resort->resort_id)->findOrFail($id);

        if ($transfer->status !== 'Approved') {
            return response()->json([
                'success' => false,
                'message' => 'Letter can only be sent once the transfer is fully approved.',
            ], 422);
        }

        $email = optional(optional($transfer->employee)->resortAdmin)->email;
        if (empty($email)) {
            return response()->json([
                'success' => false,
                'message' => "Cannot send — the employee doesn't have an email on file.",
            ], 422);
        }

        try {
            $this->generateAndSendTransferLetter($transfer);
        } catch (\Throwable $e) {
            \Log::error('Manual transfer letter send failed for #' . $transfer->id . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Could not send the letter: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Transfer Letter emailed to ' . $email . '.',
        ]);
    }

}