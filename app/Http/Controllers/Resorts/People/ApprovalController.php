<?php

namespace App\Http\Controllers\Resorts\People;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\Common;
use Carbon\Carbon;
use App\Models\EmployeeInfoUpdateRequest;
use App\Models\EmployeePromotion;
use App\Models\ResortAdmin;
use App\Models\ResortPosition;
use App\Models\ResortDepartment;
use App\Models\EmployeeResignation;
use App\Models\Employee;
use App\Models\EmployeePromotionApproval;
use App\Models\EmployeeLeave;
use App\Models\PayrollAdvance;
use App\Models\EmployeeTransfer;
use App\Models\LearningRequest;
use App\Models\ManningResponse;
use App\Models\PaymentRequest;
use App\Models\PayrollApproval;
use App\Http\Controllers\Resorts\Payroll\PayrollController;
use Auth;
use Config;
use DB;

class ApprovalController extends Controller
{
    public $resort;

    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;
    }

    public function getApprovedRequests(Request $request)
    {
        $resort = $this->resort;
        $employee = $resort->GetEmployee;
        $rank = $employee->rank ?? '';
        $resort_departments = ResortDepartment::where('resort_id',$this->resort->resort_id)->where('status','active')->get();

        if($request->ajax()) {
            // Get pagination parameters
            $start = $request->get('start', 0);
            $length = $request->get('length', 10);
            $search = $request->get('search', '');
            $departmentId = $request->get('department_id', '');
            $positionId = $request->get('position_id', '');

            $mergedRequests = collect(); // Final merged collection

            // Get employee IDs that current user is delegated for (on-leave employees)
            $delegatedForIds = Common::getDelegatedEmployeeIds($employee->id, $resort->resort_id);

            // Batch fetch rank + department for all delegated employees (avoid N+1).
            $delegatedEmployees = !empty($delegatedForIds)
                ? Employee::whereIn('id', $delegatedForIds)->get(['id', 'rank', 'Dept_id'])
                : collect();
            // HR is identified by DEPARTMENT, not rank. `rank` stores a seniority
            // grade (EXCOM/HOD/MGR/SUP/LINE) — no HR position is graded rank 3,
            // so the old `rank == 3` check matched nobody and hid every
            // Info-Update approval from HR. Detect HR via the department.
            $isHR = Common::isHRDepartment($employee->Dept_id ?? null);
            $isDelegateForHR = $delegatedEmployees->contains(fn($e) => Common::isHRDepartment($e->Dept_id));
            if ($isHR || $isDelegateForHR) {
                $infoUpdateQuery = EmployeeInfoUpdateRequest::where('resort_id', $resort->resort_id)
                    ->where('status', 'Pending')
                    ->with(['employee.resortAdmin', 'employee.department', 'employee.position'])
                    ->whereHas('employee.resortAdmin');

                $this->filterByEmployee($infoUpdateQuery, $search, $departmentId, $positionId);

                $infoUpdateRequests = $infoUpdateQuery->orderBy('created_at', 'desc')
                    ->get()
                    ->map(function ($infoUpdateRequest) {
                        return [
                            'id' => $infoUpdateRequest->id,
                            'emp_id' => $infoUpdateRequest->employee->Emp_id,
                            'name' => $infoUpdateRequest->employee->resortAdmin->full_name,
                            'department' => $infoUpdateRequest->employee->department->name ?? null,
                            'position' => $infoUpdateRequest->employee->position->position_title ?? null,
                            'status' => $infoUpdateRequest->status,
                            'request_type' => 'Info Update Request',
                            // getRawOriginal(), not the plain attribute: at
                            // least EmployeeLeave and PayrollAdvance override
                            // getCreatedAtAttribute() to return an
                            // already-resort-formatted 'd/m/Y H:i' string,
                            // which Carbon::parse() can't read at all —
                            // crashed this whole merged inbox with an
                            // InvalidFormatException the moment either type
                            // had a pending row.
                            'created_at' => Carbon::parse($infoUpdateRequest->getRawOriginal('created_at'))->format('d M Y h:i A'),
                            'action' => 'info-update',
                        ];
                    });

            }

            // Employee Promotion Requests (include delegated approvals)
            $approverIds = array_merge([$employee->id], $delegatedForIds);
            $promotionQuery = EmployeePromotion::where('resort_id', $resort->resort_id)
                ->where('status', 'Pending')
                ->with(['approvals' => function ($query) use ($approverIds) {
                    $query->where('status', 'Pending')
                        ->whereIn('approved_by', $approverIds);
                }, 'employee.resortAdmin', 'employee.department', 'employee.position']);

            $this->filterByEmployee($promotionQuery, $search, $departmentId, $positionId);

            $employeePromotionList = $promotionQuery->get()
                ->filter(fn($promotion) => $promotion->approvals->isNotEmpty())
                ->map(function ($promotion) {
                    return [
                        'id' => $promotion->id,
                        'emp_id' => $promotion->employee->Emp_id,
                        'name' => $promotion->employee->resortAdmin->full_name,
                        'department' => $promotion->employee->department->name ?? null,
                        'position' => $promotion->employee->position->position_title ?? null,
                        'status' => $promotion->approvals->first()->status ?? 'Pending',
                        'request_type' => 'Promotion Request',
                        'created_at' => Carbon::parse($promotion->getRawOriginal('created_at'))->format('d M Y h:i A'),
                        'action' => 'promotion',
                    ];
                });


            // Payroll Advance Requests — one row per stage this user can act on, the same
            // three gates AdvanceSalaryController::updateStatus() enforces (HR → Finance → GM).
            // Was: rank 3 / 7 / 8 only (an HR Director at rank 1 saw nothing) and NO status
            // filter for anyone else, so every advance in the resort showed.
            $advanceStages = [];
            if (array_intersect($approverIds, Common::getResortHrEmployeeIds($resort->resort_id))) {
                $advanceStages['hr'] = fn ($q) => $q->where('hr_status', 'Pending');
            }
            // Same titles as the endpoint's whereIn (MySQL compares case-insensitively: "Director Of Finance").
            if (in_array(strtolower(trim((string) optional($employee->position)->position_title)), ['director of finance', 'finance manager'], true)) {
                $advanceStages['finance'] = fn ($q) => $q->where('hr_status', 'Approved')->where('finance_status', 'Pending');
            }
            if ((int) $rank === 8) {
                $advanceStages['gm'] = fn ($q) => $q->where('finance_status', 'Approved')->where('gm_status', 'Pending');
            }
            $advancePayrolls = collect();
            foreach ($advanceStages as $stage => $stageFilter) {
                $payroll_data_query = PayrollAdvance::where('resort_id', $resort->resort_id)
                    ->with(['employee.resortAdmin', 'employee.position', 'employee.department'])
                    ->whereHas('employee.resortAdmin')
                    ->where($stageFilter);
                $this->filterByEmployee($payroll_data_query, $search, $departmentId, $positionId);
                $advancePayrolls = $advancePayrolls->merge($payroll_data_query->orderBy('created_at', 'desc')->get()
                    ->map(fn ($payroll) => $this->employeeRow($payroll, 'Payroll Advance', 'advance_payroll', 'Pending', ['action_by' => $stage])));
            }


            // Employee Resignation Requests — HOD stage, then HR stage (only once HOD approved),
            // matched by the ids updateStatus() checks. HR is Common::isHR(): an HR Director is
            // rank 1, so the old rank==3 branch never reached them. 'On Hold' rows stay listed
            // so the approver can resume them here (updateStatus() accepts a decision on hold).
            $isHrApprover = Common::isHR($employee) || $isDelegateForHR;
            $empResignations = EmployeeResignation::with(['employee.resortAdmin', 'employee.department', 'employee.position'])
                ->where('resort_id', $resort->resort_id)
                ->whereIn('status', ['Pending', 'On Hold'])
                ->where(function ($q) use ($approverIds, $isHrApprover) {
                    $q->where(fn ($h) => $h->whereIn('hod_id', $approverIds)
                        ->where(fn ($s) => $s->where('hod_status', 'Pending')->orWhereNull('hod_status')));
                    if ($isHrApprover) {
                        $q->orWhere(fn ($h) => $h->whereIn('hr_id', $approverIds)->where('hod_status', 'Approved')
                            ->where(fn ($s) => $s->where('hr_status', 'Pending')->orWhereNull('hr_status')));
                    }
                });

            $this->filterByEmployee($empResignations, $search, $departmentId, $positionId);

            $employeeResignations = $empResignations->get()
                ->map(function ($resignation) {
                    return [
                        'id' => $resignation->id,
                        'emp_id' => $resignation->employee->Emp_id,
                        'name' => $resignation->employee->resortAdmin->full_name,
                        'department' => $resignation->employee->department->name ?? null,
                        'position' => $resignation->employee->position->position_title ?? null,
                        'status' => $resignation->status,
                        'request_type' => 'Resignation Request',
                        'created_at' => Carbon::parse($resignation->getRawOriginal('created_at'))->format('d M Y h:i A'),
                        'action' => 'resignation',
                    ];
                });


            // Employee Leave Requests
            $leavesQuery = EmployeeLeave::where('resort_id', $resort->resort_id)
                ->where('status', 'Pending')
                ->whereHas('leaveStatus', function ($query) use ($employee) {
                    $query->where('status', 'Pending')
                        ->where('approver_id', $employee->id);
                })
                ->with(['leaveStatus', 'employee.resortAdmin', 'employee.department', 'employee.position']);

            $this->filterByEmployee($leavesQuery, $search, $departmentId, $positionId);

            $employeeLeavesRequests = $leavesQuery->get()
                ->map(function ($leave) {
                    return [
                        'id' => $leave->id,
                        'emp_id' => $leave->employee->Emp_id,
                        'name' => $leave->employee->resortAdmin->full_name,
                        'department' => $leave->employee->department->name ?? null,
                        'position' => $leave->employee->position->position_title ?? null,
                        'status' => $leave->leaveStatus->status ?? 'Pending',
                        'request_type' => 'Leave Request',
                        'created_at' => Carbon::parse($leave->getRawOriginal('created_at'))->format('d M Y h:i A'),
                        'action' => 'leave',
                    ];
                });


            // Employee Transfer Requests — surface pending transfer approvals
            // assigned to this approver, a delegate, OR any team member in the
            // assigned role's pool (Finance / GM). Without the pool match a
            // Finance HOD other than the specific one store() picked would
            // never see the request in their inbox — which is exactly what
            // happened when only id=171 was the assigned approver and the
            // logged-in Finance HOD (id=182) saw nothing.
            $financeRank = array_search('Finance', config('settings.Position_Rank'));
            $gmRank      = array_search('GM',      config('settings.Position_Rank'));
            $financeTitles = ['Director of Finance', 'Finance Manager'];

            // Build a list of role-rank pool predicates: an Employee qualifies
            // for a role when their own rank OR position Rank matches, or (for
            // Finance) when the position title is in the manager-title list.
            // Finance dept (Accounting / Finance / etc.) IDs — used to widen
            // the pool so Finance HOD (rank=2) and EXCOM (rank=1) also see
            // pending Finance approvals in their inbox.
            $financeDeptIdsForInbox = ResortDepartment::where('resort_id', $resort->resort_id)
                ->get()
                ->filter(fn($d) => Common::isFinanceDepartment($d->id))
                ->pluck('id');

            $employeesForFinanceRole = Employee::where('resort_id', $resort->resort_id)
                ->where(function ($q) use ($financeRank, $financeTitles, $financeDeptIdsForInbox) {
                    if ($financeRank !== false) {
                        $q->where('rank', $financeRank)
                          ->orWhereHas('position', function ($pq) use ($financeRank) {
                              $pq->where('Rank', $financeRank);
                          });
                    }
                    $q->orWhereHas('position', function ($pq) use ($financeTitles) {
                        $pq->whereIn('position_title', $financeTitles);
                    });
                    // Finance dept's HOD / EXCOM also belong to the pool.
                    if ($financeDeptIdsForInbox->isNotEmpty()) {
                        $q->orWhere(function ($qq) use ($financeDeptIdsForInbox) {
                            $qq->whereIn('Dept_id', $financeDeptIdsForInbox)
                               ->whereIn('rank', [1, 2]);
                        });
                    }
                })
                ->pluck('id')->all();
            $employeesForGmRole = Employee::where('resort_id', $resort->resort_id)
                ->where(function ($q) use ($gmRank) {
                    if ($gmRank !== false) {
                        $q->where('rank', $gmRank)
                          ->orWhereHas('position', function ($pq) use ($gmRank) {
                              $pq->where('Rank', $gmRank);
                          });
                    }
                })
                ->pluck('id')->all();

            $isFinancePool = in_array($employee->id, $employeesForFinanceRole, true)
                             || !empty(array_intersect($delegatedForIds, $employeesForFinanceRole));
            $isGmPool      = in_array($employee->id, $employeesForGmRole, true)
                             || !empty(array_intersect($delegatedForIds, $employeesForGmRole));

            // Only surface a transfer when the user's stage is the
            // NEXT actionable one — their approval row is Pending AND
            // every earlier-id row for the same transfer is 'Approved'.
            // Without this guard, a GM saw transfers where Finance was
            // still Pending and got "You cannot act on this request
            // until previous approvers have approved it." from
            // handleApproval() at TransferController:887-893.
            //
            // Raw NOT EXISTS so the inner/outer references stay
            // unambiguous (using whereDoesntHave's nested closure caused
            // alias collisions between two rows of the same table).
            $noEarlierUnapprovedSql = "NOT EXISTS (
                SELECT 1
                FROM employee_transfers_approval earlier_ap
                WHERE earlier_ap.transfer_id = employee_transfers_approval.transfer_id
                  AND earlier_ap.id < employee_transfers_approval.id
                  AND earlier_ap.status <> 'Approved'
            )";
            $userActionablePredicate = function ($q) use ($noEarlierUnapprovedSql) {
                $q->where('status', 'Pending')->whereRaw($noEarlierUnapprovedSql);
            };

            $transferQuery = EmployeeTransfer::where('resort_id', $resort->resort_id)
                ->whereIn('status', ['Pending', 'On Hold'])
                ->where(function ($outer) use ($approverIds, $isFinancePool, $isGmPool, $userActionablePredicate) {
                    // Direct assignment / delegation — and only when
                    // there's no earlier-stage row still Pending.
                    $outer->whereHas('approvals', function ($q) use ($approverIds, $userActionablePredicate) {
                        $q->whereIn('approved_by', $approverIds);
                        $userActionablePredicate($q);
                    });
                    // OR any pending approval whose role this user
                    // qualifies for via the Finance / GM pool — same
                    // "all earlier rows Approved" guard applied.
                    if ($isFinancePool) {
                        $outer->orWhereHas('approvals', function ($q) use ($userActionablePredicate) {
                            $q->where('approval_rank', 'Finance');
                            $userActionablePredicate($q);
                        });
                    }
                    if ($isGmPool) {
                        $outer->orWhereHas('approvals', function ($q) use ($userActionablePredicate) {
                            $q->where('approval_rank', 'GM');
                            $userActionablePredicate($q);
                        });
                    }
                })
                ->with([
                    'approvals' => function ($q) {
                        $q->where('status', 'Pending');
                    },
                    'employee.resortAdmin',
                    'employee.department',
                    'employee.position',
                ]);

            $this->filterByEmployee($transferQuery, $search, $departmentId, $positionId);

            $employeeTransferList = $transferQuery->get()
                ->map(function ($transfer) {
                    return [
                        'id'           => $transfer->id,
                        'emp_id'       => optional($transfer->employee)->Emp_id,
                        'name'         => optional(optional($transfer->employee)->resortAdmin)->full_name,
                        'department'   => optional(optional($transfer->employee)->department)->name,
                        'position'     => optional(optional($transfer->employee)->position)->position_title,
                        'status'       => optional($transfer->approvals->first())->status ?? 'Pending',
                        'request_type' => 'Transfer Request',
                        'created_at'   => Carbon::parse($transfer->getRawOriginal('created_at'))->format('d M Y h:i A'),
                        'action'       => 'transfer',
                    ];
                });

            $collections = [
                $this->payrollApprovalRows($employee, $resort->resort_id, $search, $departmentId, $positionId),
                $this->budgetApprovalRows($employee, $resort->resort_id, $search, $departmentId, $positionId),
                $this->learningRequestRows($employee, $resort->resort_id, $search, $departmentId, $positionId),
                $this->visaPaymentRows($employee, $resort->resort_id, $approverIds, $search, $departmentId, $positionId),
                $this->resignationWithdrawalRows($resort->resort_id, $approverIds, $search, $departmentId, $positionId),
                $infoUpdateRequests ?? collect(),
                $employeePromotionList ?? collect(),
                $advancePayrolls ?? collect(),
                $employeeResignations ?? collect(),
                $employeeLeavesRequests ?? collect(),
                $employeeTransferList ?? collect(),
            ];
            
            $mergedRequests = collect($collections)->collapse();

            // Per-employee filter — used when the Employee Detail page
            // "Requests" tab forwards ?empId=<base64>. Each merged row
            // carries the Employee.Emp_id code (e.g. "DR-22"), not the
            // numeric id, so decode the URL param → look up the code →
            // keep only matching rows.
            $empIdFromUrl = $request->get('empId');
            if (!empty($empIdFromUrl)) {
                $decoded = base64_decode((string) $empIdFromUrl, true);
                if ($decoded !== false && ctype_digit((string) $decoded)) {
                    $scopedCode = Employee::where('id', (int) $decoded)
                        ->where('resort_id', $resort->resort_id)
                        ->value('Emp_id');
                    if ($scopedCode) {
                        $mergedRequests = $mergedRequests->filter(
                            fn($r) => ($r['emp_id'] ?? null) === $scopedCode
                        );
                    } else {
                        $mergedRequests = collect();
                    }
                }
            }

            // Sort all requests by created_at descending
            $sortedMergedRequests = $mergedRequests->sortByDesc(function ($request) {
                // Must match the display format used to build $request['created_at']
                // (each row above formats it as 'd M Y h:i A'). The old parser
                // expected the previous 'd-m-Y h:i A' format and Carbon threw
                // when the format normalization changed the strings.
                return Carbon::createFromFormat('d M Y h:i A', $request['created_at'])->timestamp;
            });

            // Get total count before pagination
            $totalRecords = $sortedMergedRequests->count();

            // Apply pagination
            $paginatedRequests = $sortedMergedRequests->slice($start, $length)->values();

            return response()->json([
                'draw' => intval($request->get('draw', 1)),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalRecords,
                'data' => $paginatedRequests->map(function($request) {
                    return [
                        'emp_id' => $request['emp_id'],
                        'name' => $request['name'],
                        'position' => $request['position'],
                        'department' => $request['department'],
                        'request_type' => $request['request_type'],
                        'created_at' => $request['created_at'],
                        'status' => '<span class="badge bg-themeWarning">' . $request['status'] . '</span>',
                        'action' => $this->generateActionButtons($request)
                    ];
                })
            ]);
        }

        return view('resorts.people.approval.list', [
            'page_title' => 'Approval',
            'resort_departments' => $resort_departments,
        ]);
    }

    /** The name / Emp ID / department / position filters every employee-based type applies. */
    private function filterByEmployee($query, $search, $departmentId, $positionId): void
    {
        if (!empty($search)) {
            $query->where(fn ($outer) => $outer->whereHas('employee.resortAdmin', function ($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%")
                    ->orWhere(DB::raw('CONCAT(first_name, " ", last_name)'), 'LIKE', "%{$search}%");
            })->orWhereHas('employee', fn ($q) => $q->where('Emp_id', 'LIKE', "%{$search}%")));
        }
        if (!empty($departmentId)) {
            $query->whereHas('employee', fn ($q) => $q->where('Dept_id', $departmentId));
        }
        if (!empty($positionId)) {
            $query->whereHas('employee', fn ($q) => $q->where('Position_id', $positionId));
        }
    }

    private function employeeRow($model, string $type, string $action, string $status, array $payload = []): array
    {
        return [
            'id' => $model->id,
            'emp_id' => optional($model->employee)->Emp_id,
            'name' => optional(optional($model->employee)->resortAdmin)->full_name,
            'department' => optional(optional($model->employee)->department)->name,
            'position' => optional(optional($model->employee)->position)->position_title,
            'status' => $status,
            'request_type' => $type,
            'created_at' => Carbon::parse($model->getRawOriginal('created_at'))->format('d M Y h:i A'),
            'action' => $action,
            'payload' => $payload,
        ];
    }

    /** Rows that aren't about one employee (a payroll run, a resort-wide budget): name/search filters only. */
    private function recordRow(int|string $id, string $label, string $department, string $type, string $action, string $status, $createdAt, array $payload = []): array
    {
        return ['id' => $id, 'emp_id' => '—', 'name' => $label, 'department' => $department, 'position' => '—', 'status' => $status,
            'request_type' => $type, 'created_at' => Carbon::parse($createdAt)->format('d M Y h:i A'), 'action' => $action, 'payload' => $payload];
    }

    private static function matchesSearch(string $label, $search): bool
    {
        return empty($search) || stripos($label, (string) $search) !== false;
    }

    /**
     * Payroll runs waiting on this user's step (Finance EXCOM → HR EXCOM → GM): the step
     * PayrollController::approvePayroll() itself resolves, pending, with every earlier step approved.
     */
    private function payrollApprovalRows($employee, int $resortId, $search, $departmentId, $positionId)
    {
        $steps = PayrollController::approvalStepsFor($employee, $resortId); // own step + steps of people they cover for
        if (!$steps || !empty($departmentId) || !empty($positionId)) {
            return collect();
        }
        return PayrollApproval::where('resort_id', $resortId)->whereIn('step_order', $steps)->where('status', 'pending')
            ->whereNotExists(fn ($q) => $q->from('payroll_approvals as earlier')
                ->whereColumn('earlier.payroll_id', 'payroll_approvals.payroll_id')
                ->whereColumn('earlier.step_order', '<', 'payroll_approvals.step_order')
                ->where('earlier.status', '!=', 'approved'))
            ->whereHas('payroll', fn ($q) => $q->where('resort_id', $resortId))
            ->with('payroll')->get()
            ->map(fn ($a) => $this->recordRow($a->payroll_id, 'Payroll ' . Common::formatDate($a->payroll->start_date) . ' – ' . Common::formatDate($a->payroll->end_date),
                'All departments', 'Payroll Approval', 'payroll', 'Pending', $a->getRawOriginal('created_at') ?? $a->payroll->getRawOriginal('updated_at')))
            ->filter(fn ($r) => self::matchesSearch($r['name'], $search))->values();
    }

    /**
     * Budgets at the Finance step (forwarded a whole year + category at once, as
     * ResortAllNotificationController::SendToFinance() does) for the Finance HOD/EXCOM,
     * and at the GM step (approved or sent back per department) for the GM.
     */
    private function budgetApprovalRows($employee, int $resortId, $search, $departmentId, $positionId)
    {
        // Own role or the role of anyone they are covering for today — what the budget endpoints accept.
        $acting = Common::actingEmployees($employee, $resortId);
        $isFinanceLead = $acting->contains(fn ($e) => Common::getEmployeeRankPosition($e)['position'] === 'Finance' && in_array((int) $e->rank, [1, 2], true));
        $isGm = $acting->contains(fn ($e) => Common::getEmployeeRankPosition($e)['rank'] === 'GM');
        $rows = collect();
        if (!empty($positionId)) {
            return $rows;
        }
        $budgets = fn ($stage) => ManningResponse::where('resort_id', $resortId)->where('status', 'submitted')->where('budget_process_status', $stage);

        if ($isFinanceLead && empty($departmentId)) {
            foreach ($budgets('Finance')->get(['id', 'year', 'employment_type', 'updated_at'])->groupBy(fn ($b) => $b->year . '|' . ($b->employment_type ?: 'Permanent')) as $key => $group) {
                [$year, $type] = explode('|', $key);
                $label = "{$type} budget {$year} — {$group->count()} department(s)";
                $rows->push($this->recordRow($key, $label, 'All departments', 'Budget Approval', 'budget-finance', 'Awaiting Finance',
                    $group->max(fn ($b) => $b->getRawOriginal('updated_at')), ['year' => $year, 'employment_type' => $type]));
            }
        }
        if ($isGm) {
            $gmBudgets = $budgets('GM')->with('department')->when(!empty($departmentId), fn ($q) => $q->where('dept_id', $departmentId))->get();
            foreach ($gmBudgets as $b) {
                $type = $b->employment_type ?: 'Permanent';
                $rows->push($this->recordRow($b->id, "{$type} budget {$b->year} — " . optional($b->department)->name, optional($b->department)->name ?? '—',
                    'Budget Approval', 'budget-gm', 'Awaiting GM', $b->getRawOriginal('updated_at'),
                    ['budget_id' => $b->id, 'department_id' => $b->dept_id, 'year' => $b->year]));
            }
        }
        return $rows->filter(fn ($r) => self::matchesSearch($r['name'], $search))->values();
    }

    /** Learning requests for the L&D Manager — the same gate LearningController::updateStatus() applies. */
    private function learningRequestRows($employee, int $resortId, $search, $departmentId, $positionId)
    {
        if (!Common::actingEmployees($employee, $resortId)->contains(fn ($e) => Common::isLdManager($e)) || !empty($departmentId) || !empty($positionId)) {
            return collect();
        }
        return LearningRequest::where('resort_id', $resortId)->whereIn('status', ['Pending', 'On Hold'])->with(['learning', 'employees'])->get()
            ->map(fn ($r) => $this->recordRow($r->id, (optional($r->learning)->name ?? 'Learning program') . ' — ' . $r->employees->count() . ' employee(s)',
                '—', 'Learning Request', 'learning', $r->status, $r->getRawOriginal('created_at')))
            ->filter(fn ($r) => self::matchesSearch($r['name'], $search))->values();
    }

    /**
     * Visa payment requests waiting on Finance (the people PaymentRequestController notifies).
     * Finance settles each fee on the request's own page; the one-click decision here is Reject.
     */
    private function visaPaymentRows($employee, int $resortId, array $approverIds, $search, $departmentId, $positionId)
    {
        if (!array_intersect($approverIds, Common::getResortFinanceEmployeeIds($resortId)) || !empty($departmentId) || !empty($positionId)) {
            return collect();
        }
        return PaymentRequest::where('resort_id', $resortId)->where('Status', 'Pending')->get()
            ->map(fn ($p) => $this->recordRow($p->id, "Visa payment request {$p->Requestd_id}", 'Visa', 'Visa Payment Request', 'visa-payment', 'Pending',
                $p->getRawOriginal('created_at')))
            ->filter(fn ($r) => self::matchesSearch($r['name'], $search))->values();
    }

    /**
     * Withdrawals requested while Offboarding: HOD step, then HR once the HOD approved —
     * read from the same withdraw_*_status columns EmployeeResignationController::withdrawDecision()
     * writes, for the assigned HOD/HR or their delegate (the actors withdrawalStage() accepts).
     */
    private function resignationWithdrawalRows(int $resortId, array $approverIds, $search, $departmentId, $positionId)
    {
        $query = EmployeeResignation::with(['employee.resortAdmin', 'employee.department', 'employee.position'])
            ->where('resort_id', $resortId)
            ->where(fn ($q) => $q
                ->where(fn ($h) => $h->where('withdraw_hod_status', 'Pending')->whereIn('hod_id', $approverIds))
                ->orWhere(fn ($h) => $h->where('withdraw_hod_status', 'Approved')->where('withdraw_hr_status', 'Pending')->whereIn('hr_id', $approverIds)));
        $this->filterByEmployee($query, $search, $departmentId, $positionId);
        return $query->get()->map(fn ($r) => $this->employeeRow($r, 'Resignation Withdrawal', 'resignation-withdrawal', 'Pending'));
    }

    private function generateActionButtons($request)
    {   
        $approve_url = [];
        $hold_url = [];
        $reject_url = [];
        $view_route = null;
        $view_modal = false;

        if($request['action'] == 'info-update') {

            // status-change is a GET route — POST here 405'd every Approve.
            $approve_url = [
                'route' => route('people.info-update.status-change'),
                'method' => 'GET',
                'action' => 'Approved',
                'status'=> 'approve',
                'id' => $request['id'],
                'key' => 'id',
            ];
            $reject_url =[
                'route' => route('people.info-update.request-rejected'),
                'method' => 'POST',
                'action' => 'Rejected',
                'status'=> 'Rejected',
                'id' => $request['id'],
                'key' => 'id',

            ];
            // Read-only before/after modal (show() returns {html}).
            $view_route = route('people.info-update.show', $request['id']);
            $view_modal = true;
        }
        elseif($request['action'] == 'promotion') {

            // route('promotion.review.action') is a POST route — the buttons
            // were emitting method=GET which made the AJAX call 405. Use POST
            // so handlePromotionApproval is actually reached.
            $approve_url = [
                'route' => route('promotion.review.action', ['id' => base64_encode($request['id']), 'action' => 'Approved']),
                'method' => 'POST',
                'action' => 'Approved',
                'status'=> 'Approved',
                'id' => $request['id'],
                'key' => 'id',
            ];
            $hold_url = [
                'route' => route('promotion.review.action', ['id' => base64_encode($request['id']), 'action' => 'On Hold']),
                'method' => 'POST',
                'action' => 'Hold',
                'status'=> 'On Hold',
                'id' => $request['id'],
                'key' => 'id',
            ];
            $reject_url = [
                'route' => route('promotion.review.action', ['id' => base64_encode($request['id']), 'action' => 'Rejected']),
                'method' => 'POST',
                'action' => 'Rejected',
                'status'=> 'Rejected',
                'id' => $request['id'],
                'key' => 'id',
            ];

            $view_route = route('promotion.details', ['id' => base64_encode($request['id'])]);

        }elseif ($request['action'] == 'resignation') {

            // updateStatus() does base64_decode($request->resignation_id),
            // so the value posted MUST be base64-encoded. Sending the raw id
            // resulted in "No query results for [EmployeeResignation]".
            $approve_url = [
                'route' => route('people.employee-resignation.status-update'),
                'method' => 'POST',
                'action' => 'Approved',
                'status'=> 'Approved',
                'id' => base64_encode($request['id']),
                'key' => 'resignation_id',
            ];

            // Same 'On Hold' action updateStatus() already handles; it stores the reason in hold_reason.
            $hold_url = [
                'route' => route('people.employee-resignation.status-update'),
                'method' => 'POST',
                'action' => 'On Hold',
                'status'=> 'On Hold',
                'id' => base64_encode($request['id']),
                'key' => 'resignation_id',
                'needs_reason' => true,
            ];
            $reject_url = [
                'route' => route('people.employee-resignation.status-update'),
                'method' => 'POST',
                'action' => 'Rejected',
                'status'=> 'Rejected',
                'id' => base64_encode($request['id']),
                'key' => 'resignation_id',
            ];
            $view_route = route('people.employee-resignation.show', ['id' => base64_encode($request['id'])]);

        } elseif ($request['action'] == 'advance_payroll') {

            $approve_url = [
                'route' => route('people.advance-salary.update-status'),
                'method' => 'POST',
                'action' => 'Approved',
                'status'=> 'Approved',
                'id' => $request['id'],
                'key' => 'advance_salary_id',
                'payload' => $request['payload'] ?? [],
            ];
          
            $reject_url = [
                'route' => route('people.advance-salary.update-status'),
                'method' => 'POST',
                'action' => 'Rejected',
                'status'=> 'Rejected',
                'id' => $request['id'],
                'key' => 'advance_salary_id',
                'payload' => $request['payload'] ?? [],
            ];
            $view_route = route('people.advance-salary.show', ['id' => base64_encode($request['id'])]);

        }elseif ($request['action'] == 'leave') {

            $approve_url = [
                'route' => route('leave.handleAction'),
                'method' => 'POST',
                'action' => 'Approved',
                'status'=> 'Approved',
                'id' => $request['id'],
                'key' => 'leave_id',
            ];
            
            $reject_url = [
                'route' => route('leave.handleAction'),
                'method' => 'POST',
                'action' => 'Rejected',
                'status'=> 'Rejected',
                'id' => $request['id'],
                'key' => 'leave_id',

            ];
            $view_route = route('leave.details', ['leave_id' => base64_encode($request['id'])]);
        } elseif ($request['action'] == 'resignation-withdrawal') {

            // withdrawDecision(): resignation_id (base64) + decision Approved | Rejected (+ reason, required on reject). No hold.
            $approve_url = ['route' => route('people.employee-resignation.withdraw-decision'), 'method' => 'POST', 'action' => 'Approved', 'status' => 'Approved',
                'id' => base64_encode($request['id']), 'key' => 'resignation_id', 'payload' => ['decision' => 'Approved']];
            $reject_url = ['route' => route('people.employee-resignation.withdraw-decision'), 'method' => 'POST', 'action' => 'Rejected', 'status' => 'Rejected',
                'id' => base64_encode($request['id']), 'key' => 'resignation_id', 'payload' => ['decision' => 'Rejected']];
            $view_route = route('people.employee-resignation.show', ['id' => base64_encode($request['id'])]);

        } elseif ($request['action'] == 'payroll') {

            // approvePayroll(): payroll_id + action approve|reject (+ remarks). 'Rejected' would approve.
            $approve_url = ['route' => route('payroll.approve'), 'method' => 'POST', 'action' => 'Approved', 'status' => 'Approved', 'id' => $request['id'], 'key' => 'payroll_id',
                'payload' => ['action' => 'approve']];
            $reject_url = ['route' => route('payroll.approve'), 'method' => 'POST', 'action' => 'Rejected', 'status' => 'Rejected', 'id' => $request['id'], 'key' => 'payroll_id',
                'payload' => ['action' => 'reject']];
            $view_route = route('payroll.view', ['payroll_id' => base64_encode($request['id'])]);

        } elseif ($request['action'] == 'budget-finance') {

            // Finance forwards the whole year + category to the GM in one action (SendToFinance()
            // refuses until every department has submitted); sending one department back is
            // done per department on the budget page.
            $approve_url = ['route' => route('resort.SendToFinance.manning.notification'), 'method' => 'POST', 'action' => 'Approved', 'status' => 'Approved',
                'id' => $request['id'], 'key' => '', 'payload' => $request['payload']];
            $view_route = route('resort.budget.viewbudget', ['year' => $request['payload']['year']]);

        } elseif ($request['action'] == 'budget-gm') {

            $approve_url = ['route' => route('resort.budget.approve'), 'method' => 'POST', 'action' => 'Approved', 'status' => 'Approved',
                'id' => $request['id'], 'key' => '', 'payload' => $request['payload']];
            // A budget "reject" is Revise Budget: back to the department as a draft, with the comment.
            $reject_url = ['route' => route('resort.ReviseBudget.manning.notification'), 'method' => 'POST', 'action' => 'Rejected', 'status' => 'Rejected',
                'id' => $request['id'], 'key' => '', 'payload' => ['budget_id' => $request['payload']['budget_id'], 'department_id' => $request['payload']['department_id']]];
            $view_route = route('resort.budget.viewbudget', ['year' => $request['payload']['year']]);

        } elseif ($request['action'] == 'learning') {

            // updateStatus(): request_id + status Approved | On Hold | Denied (+ reason).
            $approve_url = ['route' => route('learning.request.updateStatus'), 'method' => 'POST', 'action' => 'Approved', 'status' => 'Approved', 'id' => $request['id'], 'key' => 'request_id'];
            $hold_url = ['route' => route('learning.request.updateStatus'), 'method' => 'POST', 'action' => 'On Hold', 'status' => 'On Hold', 'id' => $request['id'], 'key' => 'request_id',
                'needs_reason' => true];
            $reject_url = ['route' => route('learning.request.updateStatus'), 'method' => 'POST', 'action' => 'Rejected', 'status' => 'Rejected', 'id' => $request['id'], 'key' => 'request_id',
                'payload' => ['status' => 'Denied']];
            $view_route = route('learning.request.details', ['id' => $request['id']]);

        } elseif ($request['action'] == 'visa-payment') {

            // Finance "approves" by settling each fee on the request's own page; Reject is the one-click decision.
            $reject_url = ['route' => route('resort.visa.PaymentRequestRejected'), 'method' => 'POST', 'action' => 'Rejected', 'status' => 'Rejected',
                'id' => base64_encode($request['id']), 'key' => 'Payment_id'];
            $view_route = route('resort.visa.PaymentRequestDetails', ['id' => base64_encode($request['id'])]);

        } elseif ($request['action'] == 'transfer') {

            // Transfer approvals — same handleApproval endpoint as the Transfer
            // list page. Approve / On Hold / Reject all hit GET routes that
            // the action-button JS opens directly.
            $approve_url = [
                'route'  => route('people.transfer.handle-approval', ['id' => $request['id'], 'action' => 'Approved']),
                'method' => 'POST',
                'action' => 'Approved',
                'status' => 'Approved',
                'id'     => $request['id'],
                'key'    => 'id',
            ];
            $hold_url = [
                'route'  => route('people.transfer.handle-approval', ['id' => $request['id'], 'action' => 'On Hold']),
                'method' => 'POST',
                'action' => 'On Hold',
                'status' => 'On Hold',
                'id'     => $request['id'],
                'key'    => 'id',
            ];
            $reject_url = [
                'route'  => route('people.transfer.handle-approval', ['id' => $request['id'], 'action' => 'Rejected']),
                'method' => 'POST',
                'action' => 'Rejected',
                'status' => 'Rejected',
                'id'     => $request['id'],
                'key'    => 'id',
            ];
            // The Transfer list page is the closest thing to a detail view.
            $view_route = route('people.transfer.list');
        }


        // data-payload carries the endpoint's own parameters (merged last, so it can override
        // status/action); data-needs_reason opens the reason modal for a hold too.
        $button = function (array $b, string $class, string $urlAttr, string $icon, string $flag = '') {
            if (!$b) {
                return '';
            }
            $attrs = ['class' => $class, 'data-req_id' => $b['id'], "data-{$urlAttr}" => $b['route'], 'data-method' => $b['method'], 'data-action' => $b['action'],
                'data-key' => $b['key'], 'data-status' => $b['status'], 'data-payload' => json_encode((object) ($b['payload'] ?? []))];
            if ($flag) {
                $attrs['data-flag'] = $flag;
            }
            if (!empty($b['needs_reason'])) {
                $attrs['data-needs_reason'] = '1';
            }
            $html = '';
            foreach ($attrs as $k => $v) {
                $html .= ' ' . $k . '="' . e($v) . '"';
            }
            return '<a href="javascript:void(0)"' . $html . '><i class="' . $icon . '"></i></a>';
        };

        return '<div class="d-flex align-items-center">'
            . $button($approve_url, 'action-btn btn-tableIcon btnIcon-skyblue me-2', 'approve_url', 'fa-solid fa-check')
            . ($view_route ? '<a href="' . e($view_route) . '" class="btn-tableIcon btnIcon-skyblue view-btn me-2' . ($view_modal ? ' view-modal-btn' : '') . '" data-req_id="' . e($request['id']) . '"><i class="fa-regular fa-eye"></i></a>' : '')
            . $button($hold_url, 'btn-tableIcon btnIcon-orangeDark action-btn me-2', 'hold_url', 'fa-regular fa-hand', 'On-Hold')
            . $button($reject_url, 'close-btn action-btn me-2', 'reject_url', 'fa-solid fa-xmark', 'Closed')
            . '</div>';
    }
}