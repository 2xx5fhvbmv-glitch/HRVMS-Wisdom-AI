<?php

namespace App\Http\Controllers\Resorts\Leave;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeAnnualTicketRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Flight-ticket tracking (founder decision 2026-10-29): HR records, per employee per Annual Leave cycle, whether the
 * company has provided the round-trip ticket. Answered on the Annual Leave request page and on this report; editable
 * any time within the cycle, asked again when the employee's anniversary starts a new cycle. HR only.
 */
class AnnualTicketController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Common::isHR(), 403);
        $resortId = Auth::guard('resort-admin')->user()->resort_id;
        $scopedDepts = Common::getScopedDepartmentIds();
        $show = $request->query('show') === 'all' ? 'all' : 'unanswered';

        $employees = Employee::with(['resortAdmin:id,first_name,last_name', 'department:id,name'])
            ->where('resort_id', $resortId)->where('status', 'Active')->whereNotNull('joining_date')
            ->when(is_array($scopedDepts), fn ($q) => $q->whereIn('Dept_id', $scopedDepts))
            ->get(['id', 'Emp_id', 'Admin_Parent_id', 'Dept_id', 'joining_date', 'resort_id']);
        $records = EmployeeAnnualTicketRecord::whereIn('employee_id', $employees->pluck('id'))->get()
            ->keyBy(fn ($r) => $r->employee_id . '|' . $r->cycle_start_date->toDateString());

        $rows = $employees->map(function ($e) use ($records) {
            $cycle = Common::annualLeaveCycle($e->joining_date);
            if (!$cycle) {
                return null;
            }
            $record = $records->get($e->id . '|' . $cycle['start']->toDateString());
            return (object) ['id' => $e->id, 'code' => $e->Emp_id, 'name' => trim(($e->resortAdmin->first_name ?? '') . ' ' . ($e->resortAdmin->last_name ?? '')),
                'department' => $e->department->name ?? '', 'cycle_start' => $cycle['start'], 'cycle_end' => $cycle['end'], 'company_provided' => $record?->company_provided];
        })->filter()->when($show === 'unanswered', fn ($c) => $c->whereNull('company_provided'))->sortBy('name')->values();

        $page_title = 'Annual leave tickets';
        return view('resorts.leaves.annual-tickets', compact('page_title', 'rows', 'show'));
    }

    public function store(Request $request)
    {
        abort_unless(Common::isHR(), 403);
        $resortId = Auth::guard('resort-admin')->user()->resort_id;
        $request->validate(['employee_id' => ['required', 'integer'], 'company_provided' => ['required', 'boolean']]);
        $employee = Employee::where('id', $request->employee_id)->where('resort_id', $resortId)->first();
        $scopedDepts = Common::getScopedDepartmentIds();
        abort_if(!$employee || (is_array($scopedDepts) && !in_array($employee->Dept_id, $scopedDepts)), 404);

        $state = Common::saveAnnualTicket($employee, $request->boolean('company_provided'), Auth::guard('resort-admin')->id());
        return $state
            ? response()->json(['success' => true, 'message' => 'Ticket answer saved.', 'ticket' => $state])
            : response()->json(['success' => false, 'message' => 'This employee has no joining date, so there is no leave cycle yet.'], 422);
    }
}
