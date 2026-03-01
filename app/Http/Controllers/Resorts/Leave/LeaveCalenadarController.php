<?php

namespace App\Http\Controllers\Resorts\Leave;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use DB;
use Common;
use Config;
use Carbon\Carbon;
use App\Models\Resort;
use App\Models\Employee;
use App\Models\ResortAdmin;
use App\Models\EmployeeLeave;
use App\Models\LeaveCategory;
use App\Models\EmployeeLeaveStatus;
use App\Models\ResortDepartment;


class LeaveCalenadarController extends Controller
{
    public $resort;
    public $reporting_to;
    protected $underEmp_id=[];
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if (!$this->resort) return;
        $reporting_to = $this->resort->getEmployee?->id ?? $this->resort->id;
        $this->reporting_to = $reporting_to;
        $this->underEmp_id = Common::getSubordinates($reporting_to);
    }
    public function index()
    {
        $page_title = 'Leave Calendar';
        $loggedInEmployee = $this->resort->getEmployee;

        if (!$loggedInEmployee) {
            abort(403, "Access Denied");
        }

        $rank = config('settings.Position_Rank');
        $current_rank = $loggedInEmployee->rank ?? null;
        $available_rank = $rank[$current_rank] ?? '';
        $isHOD = ($available_rank === "HOD");
        $isHR = ($available_rank === "HR");

        // Treat Human Resources HOD like HR (whole-resort visibility)
        $hrDeptId = ResortDepartment::where('resort_id', $this->resort->resort_id)
            ->where('name', 'like', '%Human Resources%')
            ->value('id');
        $loggedInEmployeeId = $loggedInEmployee->id ?? null;
        $isHRDeptHOD = $isHOD && $loggedInEmployee && $hrDeptId && (int)$loggedInEmployee->Dept_id === (int)$hrDeptId;
        if ($isHRDeptHOD) {
            $isHR = true;
            $isHOD = false;
        }

        $leave_requests_query = DB::table('employees_leaves as el')
            ->join('employees as e', 'e.id', '=', 'el.emp_id')
            ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
            ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
            ->join('resort_departments as rd', 'rd.id', '=', 'e.Dept_id')
            ->where('el.resort_id', $this->resort->resort_id)
            ->whereNull('el.flag');
            

        // if ($isHR) {
        //     $leave_requests_query->where('e.id', '!=', $loggedInEmployee->id);// Exclude HR's own requests
        //         // ->whereNotIn('e.rank', ['GM']); // Exclude higher hierarchy roles
        // }

        if (!$isHR) {
            if ($isHOD) {
                // Non-HR HOD: show only own department leaves (incl. self)
                $leave_requests_query->where('e.Dept_id', $loggedInEmployee->Dept_id);
            } else {
                // Other ranks: show own leaves + subordinates' leaves
                $leave_requests_query->where(function ($q) use ($loggedInEmployeeId) {
                    $q->whereIn('e.id', $this->underEmp_id)
                        ->orWhere('el.emp_id', $loggedInEmployeeId);
                });
            }
        }
        

        $leaveRequests = $leave_requests_query->select(
            'el.*',
            'el.from_date as start',
            'el.to_date as end',
            'lc.leave_type as title',
            'lc.color as backgroundColor',
            'e.Emp_id as employee_id',
            'e.rank',
            'e.Admin_Parent_id',
            'el.status as leave_status',
            'ra.first_name as first_name',
            'ra.last_name as last_name',
            'ra.profile_picture',
            'rp.position_title as position',
            'rd.name as department',
            'lc.color as backgroundColor',
        )->orderBy('el.id', 'desc')->get();

        // Transform data for FullCalendar
        $calendarData = $leaveRequests->map(function ($leave) {
            return [
                'id' => $leave->id,
                'title' => "{$leave->title}",
                'employee_name' => "{$leave->first_name} {$leave->last_name}",
                'start' => $leave->start,
                'end' => $leave->end, // Add 1 day to include the end date
                'backgroundColor' => $leave->backgroundColor . '14',
                'textColor' => $leave->backgroundColor,// Default text color
                'position' => $leave->position,
                'status' => $leave->status,
                'total_days' =>$leave->total_days,
                'profile_picture' => Common::getResortUserPicture($leave->Admin_Parent_id)
            ];
        });
        return view('resorts.leaves.calendar.index', compact('page_title','calendarData'));
    }

    public function getLeaves(Request $request)
    {
        $loggedInEmployee = $this->resort->getEmployee;
        if (!$loggedInEmployee) 
        {
            abort(403, "Access Denied");
        }

        $rank = config('settings.Position_Rank');
        $current_rank = $loggedInEmployee->rank ?? null;
        $available_rank = $rank[$current_rank] ?? '';
        $isHOD = ($available_rank === "HOD");
        $isHR = ($available_rank === "HR");

        // Treat Human Resources HOD like HR (whole-resort visibility)
        $hrDeptId = ResortDepartment::where('resort_id', $this->resort->resort_id)
            ->where('name', 'like', '%Human Resources%')
            ->value('id');
        $loggedInEmployeeId = $loggedInEmployee->id ?? null;
        $isHRDeptHOD = $isHOD && $loggedInEmployee && $hrDeptId && (int)$loggedInEmployee->Dept_id === (int)$hrDeptId;
        if ($isHRDeptHOD) {
            $isHR = true;
            $isHOD = false;
        }

        $leave_requests_query = DB::table('employees_leaves as el')
            ->join('employees as e', 'e.id', '=', 'el.emp_id')
            ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
            ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
            ->join('resort_departments as rd', 'rd.id', '=', 'e.Dept_id')
            ->where('el.resort_id', $this->resort->resort_id)
            ->whereNull('el.flag');
            

        // if ($isHR) {
        //     $leave_requests_query->where('e.id', '!=', $loggedInEmployee->id); // Exclude HR's own requests
        //         // ->whereNotIn('e.rank', ['GM']); // Exclude higher hierarchy roles
        // }

        if (!$isHR) {
            if ($isHOD) {
                // Non-HR HOD: show only own department leaves (incl. self)
                $leave_requests_query->where('e.Dept_id', $loggedInEmployee->Dept_id);
            } else {
                // Other ranks: show own leaves + subordinates' leaves
                $leave_requests_query->where(function ($q) use ($loggedInEmployeeId) {
                    $q->whereIn('e.id', $this->underEmp_id)
                        ->orWhere('el.emp_id', $loggedInEmployeeId);
                });
            }
        }
        // else{
        //     $leave_requests_query->where('e.reporting_to', $this->reporting_to );
        // }

        $leaveRequests = $leave_requests_query->select(
            'el.*',
            'el.from_date as start',
            'el.to_date as end',
            'lc.leave_type as title',
            'lc.color as backgroundColor',
            'e.Emp_id as employee_id',
            'e.rank',
            'el.status as leave_status',
            'ra.first_name as first_name',
            'ra.last_name as last_name',
            'ra.profile_picture',
            'rp.position_title as position',
            'rd.name as department',
            'lc.color as backgroundColor',
            )->orderBy('el.id', 'desc')->get();

        // Transform data for FullCalendar
        $calendarData = $leaveRequests->map(function ($leave) {
            return [
                'id' => $leave->id,
                'title' => "{$leave->title}",
                'employee_name' => "{$leave->first_name} {$leave->last_name}",
                'start' => $leave->start,
                'end' => Carbon::parse($leave->end)->addDay()->format('Y-m-d'), // Add 1 day to include the end date
                'backgroundColor' => $leave->backgroundColor . '14',
                'textColor' => $leave->backgroundColor,// Default text color
            ];
        });
        // dd($calendarData);
        return response()->json($calendarData);
    }
}