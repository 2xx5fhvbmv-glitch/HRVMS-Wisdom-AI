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
            $delegatedRanks = $delegatedEmployees->pluck('rank', 'id')->toArray();
            // HR is identified by DEPARTMENT, not rank. `rank` stores a seniority
            // grade (EXCOM/HOD/MGR/SUP/LINE) — no HR position is graded rank 3,
            // so the old `rank == 3` check matched nobody and hid every
            // Info-Update approval from HR. Detect HR via the department.
            $isHR = Common::isHRDepartment($employee->Dept_id ?? null);
            $isDelegateForHR = $delegatedEmployees->contains(fn($e) => Common::isHRDepartment($e->Dept_id));
            $isDelegateForFinance = in_array(7, $delegatedRanks);
            $isDelegateForGM = in_array(8, $delegatedRanks);
            if ($isHR || $isDelegateForHR) {
                $infoUpdateQuery = EmployeeInfoUpdateRequest::where('resort_id', $resort->resort_id)
                    ->where('status', 'Pending')
                    ->with(['employee.resortAdmin', 'employee.department', 'employee.position'])
                    ->whereHas('employee.resortAdmin');

                // Apply filters
                if (!empty($search)) {
                    $infoUpdateQuery->whereHas('employee.resortAdmin', function($q) use ($search) {
                         $q->where('first_name', 'LIKE', "%{$search}%")
                            ->orWhere('last_name', 'LIKE', "%{$search}%")
                            ->orWhere(DB::raw('CONCAT(first_name, " ", last_name)'), 'LIKE', "%{$search}%");
                    })->orWhereHas('employee', function($q) use ($search) {
                        $q->where('Emp_id', 'LIKE', "%{$search}%");
                    });
                }

                if (!empty($departmentId)) {
                    $infoUpdateQuery->whereHas('employee', function($q) use ($departmentId) {
                        $q->where('Dept_id', $departmentId);
                    });
                }

                if (!empty($positionId)) {
                    $infoUpdateQuery->whereHas('employee', function($q) use ($positionId) {
                        $q->where('position_id', $positionId);
                    });
                }

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
                            'created_at' => Carbon::parse($infoUpdateRequest->created_at)->format('d M Y h:i A'),
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

            // Apply filters for promotions
            if (!empty($search)) {
                $promotionQuery->whereHas('employee.resortAdmin', function($q) use ($search) {
                    $q->where('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%")
                        ->orWhere(DB::raw('CONCAT(first_name, " ", last_name)'), 'LIKE', "%{$search}%");
                    })->orWhereHas('employee', function($q) use ($search) {
                        $q->where('Emp_id', 'LIKE', "%{$search}%");
                    });
            }

            if (!empty($departmentId)) {
                $promotionQuery->whereHas('employee', function($q) use ($departmentId) {
                    $q->where('Dept_id', $departmentId);
                });
            }

            if (!empty($positionId)) {
                $promotionQuery->whereHas('employee', function($q) use ($positionId) {
                    $q->where('position_id', $positionId);
                });
            }

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
                        'created_at' => Carbon::parse($promotion->created_at)->format('d M Y h:i A'),
                        'action' => 'promotion',
                    ];
                });


            // Payroll Advance Requests
            $payroll_data_query = PayrollAdvance::where('resort_id', $resort->resort_id)
                ->with(['employee.resortAdmin', 'employee.position', 'employee.department'])
                ->whereHas('employee.resortAdmin');

            if ($rank == 3 || $isDelegateForHR) {
                $payroll_data_query->where('hr_status', 'Pending');
            } elseif ($rank == 7 || $isDelegateForFinance) {
                $payroll_data_query->where('hr_status', 'Approved');
            } elseif ($rank == 8 || $isDelegateForGM) {
                $payroll_data_query->where('finance_status', 'Approved');
            }

            // Apply filters for payroll
            if (!empty($search)) {
                $payroll_data_query->whereHas('employee.resortAdmin', function($q) use ($search) {
                    $q->where('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%")
                            ->orWhere(DB::raw('CONCAT(first_name, " ", last_name)'), 'LIKE', "%{$search}%"); 
                    })->orWhereHas('employee', function($q) use ($search) {
                        $q->where('Emp_id', 'LIKE', "%{$search}%");
                    });
            }

            if (!empty($departmentId)) {
                $payroll_data_query->whereHas('employee', function($q) use ($departmentId) {
                    $q->where('Dept_id', $departmentId);
                });
            }

            if (!empty($positionId)) {
                $payroll_data_query->whereHas('employee', function($q) use ($positionId) {
                    $q->where('position_id', $positionId);
                });
            }

            $advancePayrolls = $payroll_data_query->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($payroll) use ($rank) {
                    $status = $rank == 3 ? $payroll->hr_status : ($rank == 7 ? $payroll->finance_status : $payroll->status);
                    return [
                        'id' => $payroll->id,
                        'emp_id' => $payroll->employee->Emp_id,
                        'name' => $payroll->employee->resortAdmin->full_name,
                        'department' => $payroll->employee->department->name ?? null,
                        'position' => $payroll->employee->position->position_title ?? null,
                        'status' => $status ?? 'Pending',
                        'request_type' => 'Payroll Advance',
                        'created_at' => Carbon::parse($payroll->created_at)->format('d M Y h:i A'),
                        'action' => 'advance_payroll',
                    ];
                });


            // Employee Resignation Requests
            $empResignations = EmployeeResignation::with(['employee.resortAdmin', 'employee.department', 'employee.position'])
                ->where('resort_id', $resort->resort_id)
                ->where('status', 'Pending');

            // Resignation chain is HOD → HR — only those two roles can act
            // (see EmployeeResignationController@updateStatus). If the
            // logged-in user isn't HOD, HR, or a delegate for either, we
            // force an empty result. Without this guard the query
            // dropped through with no rank filter and surfaced every
            // Pending resignation to GM (rank 8) — who then got
            // "You are not authorized to update this resignation
            // status." when they clicked Approve.
            if ($rank == 2) {
                $empResignations->where(function($q) use ($employee, $delegatedForIds) {
                    $q->where('hod_id', $employee->id)
                      ->orWhereIn('hod_id', $delegatedForIds);
                })->where('hod_status', 'Pending');
            } elseif ($rank == 3 || $isDelegateForHR) {
                $empResignations->where(function($q) use ($employee, $delegatedForIds) {
                    $q->where('hr_id', $employee->id)
                      ->orWhereIn('hr_id', $delegatedForIds);
                })->where('hr_status', 'Pending');
            } else {
                // Not part of the resignation chain — show nothing so the
                // inbox never offers an action that the server will refuse.
                $empResignations->whereRaw('1 = 0');
            }

            // Apply filters for resignations
            if (!empty($search)) {
                $empResignations->whereHas('employee.resortAdmin', function($q) use ($search) {
                    $q->where('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%")
                            ->orWhere(DB::raw('CONCAT(first_name, " ", last_name)'), 'LIKE', "%{$search}%");
                    })->orWhereHas('employee', function($q) use ($search) {
                        $q->where('Emp_id', 'LIKE', "%{$search}%");
                    });
            }

            if (!empty($departmentId)) {
                $empResignations->whereHas('employee', function($q) use ($departmentId) {
                    $q->where('Dept_id', $departmentId);
                });
            }

            if (!empty($positionId)) {
                $empResignations->whereHas('employee', function($q) use ($positionId) {
                    $q->where('position_id', $positionId);
                });
            }

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
                        'created_at' => Carbon::parse($resignation->created_at)->format('d M Y h:i A'),
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

            // Apply filters for leaves
            if (!empty($search)) {
                $leavesQuery->whereHas('employee.resortAdmin', function($q) use ($search) {
                     $q->where('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%")
                            ->orWhere(DB::raw('CONCAT(first_name, " ", last_name)'), 'LIKE', "%{$search}%");
                    })->orWhereHas('employee', function($q) use ($search) {
                        $q->where('Emp_id', 'LIKE', "%{$search}%");
                    });
            }

            if (!empty($departmentId)) {
                $leavesQuery->whereHas('employee', function($q) use ($departmentId) {
                    $q->where('Dept_id', $departmentId);
                });
            }

            if (!empty($positionId)) {
                $leavesQuery->whereHas('employee', function($q) use ($positionId) {
                    $q->where('position_id', $positionId);
                });
            }

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
                        'created_at' => Carbon::parse($leave->created_at)->format('d M Y h:i A'),
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

            if (!empty($search)) {
                $transferQuery->whereHas('employee.resortAdmin', function ($q) use ($search) {
                    $q->where('first_name', 'LIKE', "%{$search}%")
                      ->orWhere('last_name', 'LIKE', "%{$search}%")
                      ->orWhere(DB::raw('CONCAT(first_name, " ", last_name)'), 'LIKE', "%{$search}%");
                })->orWhereHas('employee', function ($q) use ($search) {
                    $q->where('Emp_id', 'LIKE', "%{$search}%");
                });
            }
            if (!empty($departmentId)) {
                $transferQuery->whereHas('employee', function ($q) use ($departmentId) {
                    $q->where('Dept_id', $departmentId);
                });
            }
            if (!empty($positionId)) {
                $transferQuery->whereHas('employee', function ($q) use ($positionId) {
                    $q->where('Position_id', $positionId);
                });
            }

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
                        'created_at'   => Carbon::parse($transfer->created_at)->format('d M Y h:i A'),
                        'action'       => 'transfer',
                    ];
                });

            $collections = [
                $infoUpdateRequests ?? collect(),
                $employeePromotionList ?? collect(),
                $advancePayrolls ?? collect(),
                $employeeResignations ?? collect(),
                $employeeLeavesRequests ?? collect(),
                $employeeTransferList ?? collect(),
            ];
            
            $mergedRequests = collect($collections)->collapse();
            
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

    private function generateActionButtons($request)
    {   
        $approve_url = [];
        $hold_url = [];
        $reject_url = [];
        $view_route = null;

        if($request['action'] == 'info-update') {

            $approve_url = [
                'route' => route('people.info-update.status-change'),
                'method' => 'POST',
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

            ];
          
            $reject_url = [
                'route' => route('people.advance-salary.update-status'),
                'method' => 'POST',
                'action' => 'Rejected',
                'status'=> 'Rejected',
                'id' => $request['id'],
                'key' => 'advance_salary_id',

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


        return '
            <div class="d-flex align-items-center">
                ' . (!empty($approve_url) ? '<a href="'.($approve_url['method'] == 'GET' ? $approve_url['route'] : 'javascript:void(0)').'" class="action-btn btn-tableIcon btnIcon-skyblue me-2" id="approve-btn" data-req_id="'.$approve_url['id'].'" data-approve_url="'.$approve_url['route'].'" data-method="'.$approve_url['method'].'" data-action="'.$approve_url['action'].'" data-key="'.$approve_url['key'].'" data-status="'.$approve_url['status'].'"><i class="fa-solid fa-check"></i></a>' : ' ')  . ' 
                ' . ($view_route != null ? '<a href="'.$view_route.'" class="btn-tableIcon btnIcon-skyblue view-btn me-2" data-req_id="'.$request['id'].'"><i class="fa-regular fa-eye"></i></a>' : '') . '  
                ' . (!empty($hold_url) ? '<a href="'.($hold_url['method'] == 'GET' ? $hold_url['route'] : 'javascript:void(0)').'" class="btn-tableIcon btnIcon-orangeDark action-btn me-2" data-flag="On-Hold" data-req_id="'.$hold_url['id'].'" data-hold_url="'.$hold_url['route'].'" data-method="'.$hold_url['method'].'" data-action="'.$hold_url['action'].'" data-key="'.$hold_url['key'].'" data-status="'.$hold_url['status'].'"><i class="fa-regular fa-hand"></i></a>' : '') . '    
                ' . (!empty($reject_url) ? '<a href="'.($reject_url['method'] == 'GET' ? $reject_url['route'] : 'javascript:void(0)').'" class="close-btn action-btn me-2" data-flag="Closed" data-req_id="'.$reject_url['id'].'" data-reject_url="'.$reject_url['route'].'" data-method="'.$reject_url['method'].'" data-action="'.$reject_url['action'].'" data-key="'.$reject_url['key'].'" data-status="'.$reject_url['status'].'"><i class="fa-solid fa-xmark"></i></a>' : '') . '
            </div>
        ';
    }
}