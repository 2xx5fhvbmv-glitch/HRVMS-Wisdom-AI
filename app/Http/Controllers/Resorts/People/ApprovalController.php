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

            // Info Update Requests (only for rank 3)
            if ($rank == 3) {
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
                            'created_at' => Carbon::parse($infoUpdateRequest->created_at)->format('d-m-Y h:i A'),
                            'action' => 'info-update',
                        ];
                    });

            }

            // Employee Promotion Requests
            $promotionQuery = EmployeePromotion::where('resort_id', $resort->resort_id)
                ->where('status', 'Pending')
                ->with(['approvals' => function ($query) use ($employee) {
                    $query->where('status', 'Pending')
                        ->where('approved_by', $employee->id);
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
                        'created_at' => Carbon::parse($promotion->created_at)->format('d-m-Y h:i A'),
                        'action' => 'promotion',
                    ];
                });


            // Payroll Advance Requests
            $payroll_data_query = PayrollAdvance::where('resort_id', $resort->resort_id)
                ->with(['employee.resortAdmin', 'employee.position', 'employee.department'])
                ->whereHas('employee.resortAdmin');

            if ($rank == 3) {
                $payroll_data_query->where('hr_status', 'Pending');
            } elseif ($rank == 7) {
                $payroll_data_query->where('hr_status', 'Approved');
            } elseif ($rank == 8) {
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
                        'created_at' => Carbon::parse($payroll->created_at)->format('d-m-Y h:i A'),
                        'action' => 'advance_payroll',
                    ];
                });


            // Employee Resignation Requests
            $empResignations = EmployeeResignation::with(['employee.resortAdmin', 'employee.department', 'employee.position'])
                ->where('resort_id', $resort->resort_id)
                ->where('status', 'Pending');

            if ($rank == 2) {
                $empResignations->where('hod_id', $employee->id)->where('hod_status', 'Pending');
            } elseif ($rank == 3) {
                $empResignations->where('hr_id', $employee->id)->where('hr_status', 'Pending');
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
                        'created_at' => Carbon::parse($resignation->created_at)->format('d-m-Y h:i A'),
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
                        'created_at' => Carbon::parse($leave->created_at)->format('d-m-Y h:i A'),
                        'action' => 'leave',
                    ];
                });


            $collections = [
                $infoUpdateRequests ?? collect(),
                $employeePromotionList ?? collect(),
                $advancePayrolls ?? collect(),
                $employeeResignations ?? collect(),
                $employeeLeavesRequests ?? collect(),
            ];
            
            $mergedRequests = collect($collections)->collapse();
            
            // Sort all requests by created_at descending
            $sortedMergedRequests = $mergedRequests->sortByDesc(function ($request) {
                return Carbon::createFromFormat('d-m-Y h:i A', $request['created_at'])->timestamp;
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

            $approve_url = [
                'route' => route('promotion.review.action', ['id' => base64_encode($request['id']), 'action' => 'Approved']),
                'method' => 'GET',
                'action' => 'Approved',
                'status'=> 'Approved',
                'id' => $request['id'],
                'key' => 'id',
            ];
            $hold_url = [
                'route' => route('promotion.review.action', ['id' => base64_encode($request['id']), 'action' => 'On Hold']),
                'method' => 'GET',
                'action' => 'Hold',
                'status'=> 'On Hold',
                'id' => $request['id'],
                'key' => 'id',
            ];
            $reject_url = [
                'route' => route('promotion.review.action', ['id' => base64_encode($request['id']), 'action' => 'Rejected']),
                'method' => 'GET',
                'action' => 'Rejected',
                'status'=> 'Rejected',
                'id' => $request['id'],
                'key' => 'id',
            ];
            
            $view_route = route('promotion.details', ['id' => base64_encode($request['id'])]);

        }elseif ($request['action'] == 'resignation') {

            $approve_url = [
                'route' => route('people.employee-resignation.status-update'),
                'method' => 'POST',
                'action' => 'Approved',
                'status'=> 'Approved',
                'id' => $request['id'],
                'key' => 'resignation_id',
            ];
            
            $reject_url = [
                'route' => route('people.employee-resignation.status-update'),
                'method' => 'POST',
                'action' => 'Rejected',
                'status'=> 'Rejected',
                'id' => $request['id'],
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