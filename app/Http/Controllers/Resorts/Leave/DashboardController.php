<?php

namespace App\Http\Controllers\Resorts\Leave;

use App\Http\Controllers\Controller;
use App\Events\ResortNotificationEvent;
use Illuminate\Http\Request;
use App\Models\ResortHoliday;
use App\Models\ResortAdmin;
use App\Models\Employee;
use App\Models\EmployeeLeave;
use App\Models\EmployeeLeaveStatus;
use App\Models\ResortDepartment;
use App\Models\LeaveCategory;
use Auth;
use DB;
use Common;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public $resort;
    public $reporting_to;
    protected $underEmp_id=[];
    public function __construct()
    {
        // $this->resort = Auth::guard('resort-admin')->user();
        // $this->reporting_to = $this->resort->GetEmployee->id;
        // // dd($reporting_to);
        // $this->underEmp_id = Common::getSubordinates($this->reporting_to);
        $this->resort = $resortId = auth()->guard('resort-admin')->user();
        if(isset($this->resort->GetEmployee))
        {
            $reporting_to = $this->resort->GetEmployee->id;
        }
        else
        {
            $reporting_to = $this->resort->id;
        }
        $this->underEmp_id = Common::getSubordinates($reporting_to);

    }

    public function admin_dashboard()
    {
        $page_title ='Leave';
        $resort_departments = ResortDepartment::where('resort_id',$this->resort->resort_id)->where('status','active')->get();
        $upcomingHolidays = ResortHoliday::where('resort_id', $this->resort->resort_id)
        ->where('PublicHolidaydate', '>=', Carbon::now()->toDateString())
        ->orderBy('PublicHolidaydate', 'asc')
        ->get();

        $rank = config('settings.Position_Rank');
        $current_rank = $this->resort->getEmployee->rank ?? null;
        $available_rank = $rank[$current_rank] ?? '';
        $isHOD = ($available_rank === "HOD");
        $isHR = ($available_rank === "HR");
        if( $isHR ){
            $total_applied_leaves = DB::table('employees_leaves as el')
            ->where('el.resort_id', $this->resort->resort_id)->where('flag',null)->count();
            $total_approved_leaves = DB::table('employees_leaves as el')
            ->where('el.resort_id', $this->resort->resort_id)->where('flag',null)->where('status','Approved')->count();
            $total_pending_leaves = DB::table('employees_leaves as el')
            ->where('el.resort_id', $this->resort->resort_id)->where('flag',null)->where('status','Pending')->count();
            $total_rejected_leaves = DB::table('employees_leaves as el')
            ->where('el.resort_id', $this->resort->resort_id)->where('flag',null)->where('status','Rejected')->count();
        }
        else{
            $total_applied_leaves = DB::table('employees_leaves as el')
            ->join('employees as e', 'e.id', '=', 'el.emp_id')->where('flag',null)
            // ->whereIn('e.id', $this->underEmp_id)
            ->where('e.reporting_to', $this->reporting_to )// Only employees under HOD
            ->where('el.resort_id', $this->resort->resort_id)->count();

            $total_approved_leaves = DB::table('employees_leaves as el')
            ->join('employees as e', 'e.id', '=', 'el.emp_id')
            // ->whereIn('e.id', $this->underEmp_id)
            ->where('e.reporting_to', $this->reporting_to ) // Only employees under reporting to
            ->where('flag',null)
            ->where('el.resort_id', $this->resort->resort_id)->where('el.status','Approved')->count();

            $total_pending_leaves = DB::table('employees_leaves as el')
            ->join('employees as e', 'e.id', '=', 'el.emp_id')
            // ->whereIn('e.id', $this->underEmp_id)
            ->where('e.reporting_to', $this->reporting_to )
            ->where('flag',null)
            ->where('el.resort_id', $this->resort->resort_id)->where('el.status','Pending')->count();

            $total_rejected_leaves = DB::table('employees_leaves as el')
            ->join('employees as e', 'e.id', '=', 'el.emp_id')
            // ->whereIn('e.id', $this->underEmp_id)
            ->where('e.reporting_to',$this->reporting_to )
            ->where('flag',null)
            ->where('el.resort_id', $this->resort->resort_id)->where('el.status','Rejected')->count();
        }
        

        // Get today's day and month, and tomorrow's day and month
        $today = Carbon::today()->format('d-m'); // Current day and month (d-m)
        $tomorrow = Carbon::tomorrow()->format('d-m'); // Tomorrow's day and month (d-m)
       
        $upcomingBirthdays = Employee::with(['resortAdmin', 'position']) // Eager load both relationships
        ->whereRaw('SUBSTRING(dob, 1, 5) = ?', [$today]) // Compare day and month from the string
        ->orWhereRaw('SUBSTRING(dob, 1, 5) = ?', [$tomorrow]) // Compare tomorrow's day and month
        ->get();

        $upcomingBirthdays = $upcomingBirthdays->map(function($employee) {
            $employee->profile_picture = Common::getResortUserPicture($employee->Admin_Parent_id);
            return $employee;
        });
       
        // Separate today's and tomorrow's birthdays
        $todayBirthdays = $upcomingBirthdays->filter(function ($employee) use ($today) {
            return substr($employee->dob, 0, 5) == $today;
        });

        $tomorrowBirthdays = $upcomingBirthdays->filter(function ($employee) use ($tomorrow) {
            return substr($employee->dob, 0, 5) == $tomorrow;
        });

        // dd($todayBirthdays);

        $tomorrowBirthdays = $upcomingBirthdays->filter(function ($employee) use ($tomorrow) {
            return Carbon::parse($employee->dob)->format('d-m') == $tomorrow;
        });

        return view('resorts.leaves.dashboard.admin-dashboard',compact('page_title','upcomingHolidays','todayBirthdays','tomorrowBirthdays','total_applied_leaves','resort_departments','total_approved_leaves','total_pending_leaves','total_rejected_leaves'));
    }

    public function HR_Dashobard()
    {
        $page_title ='Leave';
        $resort_departments = ResortDepartment::where('resort_id',$this->resort->resort_id)->where('status','active')->get();
        $upcomingHolidays = ResortHoliday::where('resort_id', $this->resort->resort_id)
        ->where('PublicHolidaydate', '>=', Carbon::now()->toDateString())
        ->orderBy('PublicHolidaydate', 'asc')
        ->get();
        $loggedInEmployee = $this->resort->getEmployee;
        $loggedInEmployeeId = $loggedInEmployee->id ?? null;

        $rank = config('settings.Position_Rank');
        $current_rank = $this->resort->getEmployee->rank ?? null;
        $available_rank = $rank[$current_rank] ?? '';
        $isHOD = ($available_rank === "HOD");
        $isHR = ($available_rank === "HR");
        if( $isHR ){
            $total_applied_leaves = DB::table('employees_leaves as el')
            ->where('el.resort_id', $this->resort->resort_id)->where('flag',null)->count();
            $total_approved_leaves = DB::table('employees_leaves as el')
            ->where('el.resort_id', $this->resort->resort_id)->where('flag',null)->where('status','Approved')->count();
            $total_pending_leaves = DB::table('employees_leaves as el')
            ->where('el.resort_id', $this->resort->resort_id)->where('flag',null)->where('status','Pending')->count();
            $total_rejected_leaves = DB::table('employees_leaves as el')
            ->where('el.resort_id', $this->resort->resort_id)->where('flag',null)->where('status','Rejected')->count();
        }
        else{
            $total_applied_leaves = DB::table('employees_leaves as el')
            ->join('employees as e', 'e.id', '=', 'el.emp_id')->where('flag',null)
            ->join('employees_leaves_status as els', 'els.leave_request_id', '=', 'el.id')

            // ->whereIn('e.id', $this->underEmp_id)
            ->where('els.approver_id',$loggedInEmployeeId)// Only employees under HOD
            ->where('el.resort_id', $this->resort->resort_id)->count();

            $total_approved_leaves = DB::table('employees_leaves as el')
            ->join('employees as e', 'e.id', '=', 'el.emp_id')
            ->join('employees_leaves_status as els', 'els.leave_request_id', '=', 'el.id')

            // ->whereIn('e.id', $this->underEmp_id)
            ->where('els.approver_id',$loggedInEmployeeId)// Only employees under HOD
            ->where('flag',null)
            ->where('el.resort_id', $this->resort->resort_id)->where('el.status','Approved')->count();

            $total_pending_leaves = DB::table('employees_leaves as el')
            ->join('employees as e', 'e.id', '=', 'el.emp_id')
            ->join('employees_leaves_status as els', 'els.leave_request_id', '=', 'el.id')

            // ->whereIn('e.id', $this->underEmp_id)
            ->where('els.approver_id',$loggedInEmployeeId)// Only employees under HOD
            ->where('flag',null)
            ->where('el.resort_id', $this->resort->resort_id)->where('el.status','Pending')->count();

            $total_rejected_leaves = DB::table('employees_leaves as el')
            ->join('employees as e', 'e.id', '=', 'el.emp_id')
            // ->whereIn('e.id', $this->underEmp_id)
            ->where('e.reporting_to',$this->reporting_to )
            ->where('flag',null)
            ->where('el.resort_id', $this->resort->resort_id)->where('el.status','Rejected')->count();
        }
        

        // Get today's day and month, and tomorrow's day and month
        $today = Carbon::today()->format('d-m'); // Current day and month (d-m)
        $tomorrow = Carbon::tomorrow()->format('d-m'); // Tomorrow's day and month (d-m)
       
        $upcomingBirthdays = Employee::with(['resortAdmin', 'position']) // Eager load both relationships
        ->whereRaw('SUBSTRING(dob, 1, 5) = ?', [$today]) // Compare day and month from the string
        ->orWhereRaw('SUBSTRING(dob, 1, 5) = ?', [$tomorrow]) // Compare tomorrow's day and month
        ->get();

        $upcomingBirthdays = $upcomingBirthdays->map(function($employee) {
            $employee->profile_picture = Common::getResortUserPicture($employee->Admin_Parent_id);
            return $employee;
        });
       
        // Separate today's and tomorrow's birthdays
        $todayBirthdays = $upcomingBirthdays->filter(function ($employee) use ($today) {
            return substr($employee->dob, 0, 5) == $today;
        });

        $tomorrowBirthdays = $upcomingBirthdays->filter(function ($employee) use ($tomorrow) {
            return substr($employee->dob, 0, 5) == $tomorrow;
        });

        // dd($todayBirthdays);

        $tomorrowBirthdays = $upcomingBirthdays->filter(function ($employee) use ($tomorrow) {
            return Carbon::parse($employee->dob)->format('d-m') == $tomorrow;
        });

        return view('resorts.leaves.dashboard.hrdashboard',compact('page_title','upcomingHolidays','todayBirthdays','tomorrowBirthdays','total_applied_leaves','resort_departments','total_approved_leaves','total_pending_leaves','total_rejected_leaves'));
    }

    public function hod_dashboard()
    {
        $page_title ='Leave';
        $loggedInEmployee = $this->resort->getEmployee;
        $loggedInEmployeeId = $loggedInEmployee->id ?? null;
        $resort_departments = ResortDepartment::where('resort_id',$this->resort->resort_id)->where('status','active')->get();

        $upcomingHolidays = ResortHoliday::where('resort_id', $this->resort->resort_id)
        ->where('PublicHolidaydate', '>=', Carbon::now()->toDateString())
        ->orderBy('PublicHolidaydate', 'asc')
        ->get();

        // dd($this->underEmp_id);
        // Get counts for each leave status
        $total_applied_leaves = DB::table('employees_leaves as el')
        ->join('employees as e', 'e.id', '=', 'el.emp_id')->where('flag',null)
        // ->whereIn('e.id', $this->underEmp_id)
        ->join('employees_leaves_status as els', 'els.leave_request_id', '=', 'el.id')
        ->where('els.approver_id',$loggedInEmployeeId)// Only employees under HOD
        ->where('el.resort_id', $this->resort->resort_id)->count();

        $total_approved_leaves = DB::table('employees_leaves as el')
        ->join('employees as e', 'e.id', '=', 'el.emp_id')
        ->join('employees_leaves_status as els', 'els.leave_request_id', '=', 'el.id')
        // ->whereIn('e.id', $this->underEmp_id)
        ->where('flag',null)
        ->where('els.approver_id',$loggedInEmployeeId)
        ->where('el.resort_id', $this->resort->resort_id)->where('els.status','Approved')->count();

        $total_pending_leaves = DB::table('employees_leaves as el')
        ->join('employees as e', 'e.id', '=', 'el.emp_id')
        ->join('employees_leaves_status as els', 'els.leave_request_id', '=', 'el.id')
        // ->whereIn('e.id', $this->underEmp_id)
        // ->where('e.reporting_to', $this->reporting_to )
        ->where('flag',null)
        ->where('els.approver_id',$loggedInEmployeeId)
        ->where('el.resort_id', $this->resort->resort_id)->where('els.status','Pending')->count();

        $total_rejected_leaves = DB::table('employees_leaves as el')
        ->join('employees as e', 'e.id', '=', 'el.emp_id')
        ->join('employees_leaves_status as els', 'els.leave_request_id', '=', 'el.id')
        // ->whereIn('e.id', $this->underEmp_id)
        // ->where('e.reporting_to', $this->reporting_to )
        ->where('flag',null)
        ->where('els.approver_id',$loggedInEmployeeId)
        ->where('el.resort_id', $this->resort->resort_id)->where('el.status','Rejected')->count();

        return view('resorts.leaves.dashboard.hoddashboard',compact('page_title','upcomingHolidays','resort_departments','total_applied_leaves','total_approved_leaves','total_pending_leaves','total_rejected_leaves'));
    }

    public function get_upcomimg_holidays(Request $request){
        $resort_id = $this->resort->resort_id;
        $upcomingHolidays = ResortHoliday::where('resort_id', $resort_id)
        ->where('PublicHolidaydate', '>=', Carbon::now()->toDateString())
        ->orderBy('PublicHolidaydate', 'ASC')
        ->get();

        if ($request->ajax()) {
            $upcomingHolidays = ResortHoliday::where('resort_id', $resort_id)
                ->where('PublicHolidaydate', '>=', Carbon::now()->toDateString())
                ->orderBy('PublicHolidaydate', 'asc')
                ->get();
        
            return datatables()->of($upcomingHolidays)
                ->editColumn('PublicHolidaydate', function ($row) {
                    return $row->PublicHolidaydate ? Carbon::parse($row->PublicHolidaydate)->format('d-m-Y') : '--';
                })
                ->addColumn('day', function ($row) {
                    return $row->PublicHolidaydate ? Carbon::parse($row->PublicHolidaydate)->format('l') : '--';
                })
                ->make(true);
        }

       $page_title = "Holidays";
       
       return view('resorts.leaves.dashboard.upcoming_holidays', compact('page_title','resort_id'));
    }

    public function getUpcomingBirthdaysList(Request $request)
    {
        $resort_id = $this->resort->resort_id;
        // dd($resort_id);
        // Define date ranges
        $today = Carbon::today()->format('d-m'); // Current day and month (d-m)
        $tomorrow = Carbon::tomorrow()->format('d-m'); // Tomorrow's day and month (d-m)
        $startMonthDay = Carbon::today()->format('m-d');
        $endMonthDay = Carbon::today()->addMonths(11)->format('m-d');
        // dd($startMonthDay,$endMonthDay);

        $upcomingBirthdays = Employee::with(['resortAdmin', 'position'])
            ->where('resort_id', $resort_id) // Filter by resort_id
            ->where(function($query) use ($today, $tomorrow, $startMonthDay, $endMonthDay) {
                $query->whereRaw('SUBSTRING(dob, 1, 5) = ?', [$today])
                      ->orWhereRaw('SUBSTRING(dob, 1, 5) = ?', [$tomorrow])
                      ->orWhereRaw('SUBSTRING(dob, 1, 5) >= ?', [$startMonthDay])
                      ->orWhereRaw('SUBSTRING(dob, 1, 5) <= ?', [$endMonthDay]);
            })
            ->orderByRaw('SUBSTRING(dob, 4, 2) ASC')
            ->get();

        $upcomingBirthdays = $upcomingBirthdays->filter(function ($employee) {
            // Ensure `dob` is not empty and matches `d-m-Y` format
            return !empty($employee->dob) && preg_match('/^\d{2}-\d{2}-\d{4}$/', $employee->dob);
        })->map(function ($employee) {
            $employee->profile_picture = Common::getResortUserPicture($employee->Admin_Parent_id);
            try {
                // Convert `d-m-Y` to Carbon instance, then extract month and day
                $dob = Carbon::createFromFormat('d-m-Y', $employee->dob);
                $dobMonthDay = $dob->format('m-d'); // Extract MM-DD
                $employee->formatted_dob = $dob->format('l M, d'); // Format as `Day Month, Date`
            } catch (\Carbon\Exceptions\InvalidFormatException $e) {
                $employee->formatted_dob = 'Invalid Date';
            }
            return $employee;
        });
        
        //dd($upcomingBirthdays);
        // Separate today's and tomorrow's birthdays
        $todayBirthdays = $upcomingBirthdays->filter(function ($employee) use ($today) {
            return substr($employee->dob, 0, 5) == $today;
        });

        $tomorrowBirthdays = $upcomingBirthdays->filter(function ($employee) use ($tomorrow) {
            return substr($employee->dob, 0, 5) == $tomorrow;
        });

        // Filter out remaining upcoming birthdays
        $remainingBirthdays = $upcomingBirthdays->filter(function ($employee) use ($today, $tomorrow) {
            $dobMonthDay = substr($employee->dob, 0, 5); // Extract 'mm-dd'
            // dd($dobMonthDay);
            return $dobMonthDay !== $today && $dobMonthDay !== $tomorrow;
        });

        // dd($todayBirthdays,$tomorrowBirthdays,$remainingBirthdays);
    
        $page_title = "Upcoming Birthdays";
        return view('resorts.leaves.dashboard.upcoming_birthdays', compact(
            'page_title',
            'resort_id',
            'todayBirthdays',
            'tomorrowBirthdays',
            'remainingBirthdays',
            'today',
            'tomorrow'
        ));
    }

    // public function getLeaveRequests(Request $request) {
    //     // Initialize variables
    //     $resort_id = $request->user()->resort_id;
    //     $loggedInEmployee = $this->resort->getEmployee;
    //     $loggedInEmployeeId = $loggedInEmployee->id ?? null;
    //     // dd($loggedInEmployeeId);
    //     $department_id = $request->get('department_id', '');
    //     $position_id = $request->get('position_id', '');
    //     $search = $request->get('search', '');
    //     $rank = config('settings.Position_Rank');
    //     $current_rank = $this->resort->getEmployee->rank ?? null;
    //     $available_rank = $rank[$current_rank] ?? '';

    //     $isHOD = ($available_rank === "HOD");
    //     $isHR = ($available_rank === "HR");

    //     $permission = '';
    //     if(Common::checkRouteWisePermission('leave.request',config('settings.resort_permissions.edit')) == false){
    //         $permission ='d-none';
    //     }
    //     // Base query for leave requests
    //     $leave_requests_query = DB::table('employees_leaves as el')
    //         ->join('employees as e', 'e.id', '=', 'el.emp_id')
    //         ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
    //         ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
    //         ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
    //         ->join('resort_departments as rd', 'rd.id', '=', 'e.Dept_id')
    //         // ->leftJoin('employees_leaves_status as els', function($join) {
    //         //     $join->on('els.leave_request_id', '=', 'el.id')
    //         //          ->whereRaw('els.id = (SELECT MAX(id) FROM employees_leaves_status WHERE leave_request_id = el.id AND status != "Pending")');
    //         // })
    //          ->join('employees_leaves_status as els', 'els.leave_request_id', '=', 'el.id')
    //         ->where('el.resort_id', $resort_id)
    //         ->where('el.flag', null)
    //         ->where('el.status', 'Pending');
        
    //     $leave_requests_query->where(function ($query) use ($loggedInEmployeeId) {
    //         $query->where('els.approver_id', $loggedInEmployeeId)
    //                 ->where('els.status', 'Pending');
    //                 // ->orWhere('el.emp_id', $loggedInEmployeeId); // Include logged-in user's own leave applications
    //     });

    //       // Apply filters
    //     if (isset($department_id) && !empty($department_id)) {
    //         $leave_requests_query->where('e.Dept_id', $department_id);
    //     }
    //     if ($position_id) {
    //         $leave_requests_query->where('e.Position_id', $position_id);
    //     }
    //     if ($search) {
    //         $leave_requests_query->where(function ($q) use ($search) {
    //             $q->where('ra.first_name', 'LIKE', '%' . $search . '%')
    //               ->orWhere('ra.last_name', 'LIKE', '%' . $search . '%')
    //               ->orWhere('e.Emp_id', 'LIKE', '%' . $search . '%');
    //         });
    //     }
    
    //     // Get total records
    //     $total_records = $leave_requests_query->count();
          
    //     // Apply pagination
    //     $leave_requests = $leave_requests_query
    //         ->groupBy('el.id')
    //         ->orderBy('el.created_at','desc')
    //         ->select(
    //             'el.*',
    //             'el.Emp_id as employee_id',
    //             'e.Admin_Parent_id',
    //             'ra.first_name',
    //             'ra.last_name',
    //             'lc.leave_type',
    //             'lc.color',
    //             'rp.position_title as position',
    //             'rd.code as code',
    //             'rd.name as department',
    //             'els.status as last_status',
    //             'els.approver_rank as approver_rank',
    //             'el.from_date',
    //             'el.to_date',
    //             'el.total_days',
    //             'el.attachments',
    //             'el.created_at',
    //             'els.status as approval_status',
    //             'els.approver_id as approver_id',
    //         )
    //         ->skip($request->get('start', 0))
    //         ->take($request->get('length', 10))
    //         ->get();
    //     // Format results
    //     $leave_requests = $leave_requests->map(function($leaveRequest) {
    //         $leaveRequest->from_date = Carbon::parse($leaveRequest->from_date)->format('d M');
    //         $leaveRequest->to_date = Carbon::parse($leaveRequest->to_date)->format('d M');
    //         $leaveRequest->profile_picture = Common::getResortUserPicture($leaveRequest->Admin_Parent_id);
    //         $leaveRequest->combinedLeave = EmployeeLeave::where('flag',$leaveRequest->id)
    //         ->join('leave_categories as lc','lc.id','=','employees_leaves.leave_category_id')
    //         ->first();
    
    //         // Set readable status
    //         $role = ucfirst(strtolower($leaveRequest->approver_rank ?? ''));

    //         $rank = config('settings.Position_Rank');
    //         $role = $rank[$role] ?? '';
    //         $leaveRequest->to_date =  (isset($leaveRequest->combinedLeave) ? $leaveRequest->combinedLeave->to_date :$leaveRequest->to_date);
    //         $leaveRequest->to_date = Carbon::parse($leaveRequest->to_date)->format('d M');
    //         $leaveRequest->status_text = $leaveRequest->last_status ? "{$leaveRequest->last_status} by {$role}" : 'Pending';
    //         $leaveRequest->total_days = (isset($leaveRequest->combinedLeave) ? ($leaveRequest->combinedLeave->total_days + $leaveRequest->total_days) :$leaveRequest->total_days);
    //         $leaveRequest->routes = route('leave.details',base64_encode($leaveRequest->id));
    //         return $leaveRequest;
    //     });
    
    //     // Return response in DataTables format
    //     return response()->json([
    //         'draw' => $request->get('draw', 1),
    //         'recordsTotal' => $total_records,
    //         'recordsFiltered' => $total_records,
    //         'data' => $leave_requests,
    //         'permission' => $permission,

    //     ]);
    // }

    public function getLeaveRequests(Request $request)
    {
        $resort_id = $request->user()->resort_id;
        $loggedInEmployee = $this->resort->getEmployee;
        $loggedInEmployeeId = $loggedInEmployee->id ?? null;

        $department_id = $request->get('department_id', '');
        $position_id = $request->get('position_id', '');
        $search = $request->get('search', '');
        $rank = config('settings.Position_Rank');
        $current_rank = $this->resort->getEmployee->rank ?? null;
        $available_rank = $rank[$current_rank] ?? '';

        $permission = Common::checkRouteWisePermission('leave.request', config('settings.resort_permissions.edit')) ? '' : 'd-none';

        $leave_requests_query = DB::table('employees_leaves as el')
            ->join('employees as e', 'e.id', '=', 'el.emp_id')
            ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
            ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
            ->join('resort_departments as rd', 'rd.id', '=', 'e.Dept_id')
            ->join('employees_leaves_status as els', 'els.leave_request_id', '=', 'el.id')
            ->where('el.resort_id', $resort_id)
            ->where('el.flag', null)
            // ->where('el.status', 'Pending')
            ->where(function ($query) use ($loggedInEmployeeId) {
                $query->where('els.approver_id', $loggedInEmployeeId)
                    ->where('els.status', 'Pending');
            });

        if (!empty($department_id)) {
            $leave_requests_query->where('e.Dept_id', $department_id);
        }
        if ($position_id) {
            $leave_requests_query->where('e.Position_id', $position_id);
        }
        if ($search) {
            $leave_requests_query->where(function ($q) use ($search) {
                $q->where('ra.first_name', 'LIKE', '%' . $search . '%')
                    ->orWhere('ra.last_name', 'LIKE', '%' . $search . '%')
                    ->orWhere('e.Emp_id', 'LIKE', '%' . $search . '%');
            });
        }

        $total_records = $leave_requests_query->count();

        $leave_requests = $leave_requests_query
            ->groupBy('el.id')
            ->orderBy('el.created_at', 'desc')
            ->select(
                'el.*',
                'el.Emp_id as employee_id',
                'e.Admin_Parent_id',
                'ra.first_name',
                'ra.last_name',
                'lc.leave_type',
                'lc.color',
                'rp.position_title as position',
                'rd.code as code',
                'rd.name as department',
                'els.status as last_status',
                'els.approver_rank as approver_rank',
                'el.from_date',
                'el.to_date',
                'el.total_days',
                'el.attachments',
                'el.created_at',
                'els.status as approval_status',
                'els.approver_id as approver_id',
            )
            ->skip($request->get('start', 0))
            ->take($request->get('length', 10))
            ->get();

        // Fetch all statuses once for performance
        $statusesGrouped = DB::table('employees_leaves_status')
            ->whereIn('leave_request_id', $leave_requests->pluck('id'))
            ->orderBy('id', 'asc')
            ->get()
            ->groupBy('leave_request_id');

        $leave_requests = $leave_requests->map(function ($leaveRequest) use ($statusesGrouped, $rank) {
            $leaveRequest->from_date = Carbon::parse($leaveRequest->from_date)->format('d M');
            $leaveRequest->to_date = Carbon::parse($leaveRequest->to_date)->format('d M');
            $leaveRequest->profile_picture = Common::getResortUserPicture($leaveRequest->Admin_Parent_id);

            $leaveRequest->combinedLeave = EmployeeLeave::where('flag', $leaveRequest->id)
                ->join('leave_categories as lc', 'lc.id', '=', 'employees_leaves.leave_category_id')
                ->first();

            // Status Logic
            $statuses = $statusesGrouped[$leaveRequest->id] ?? collect();

            // Check rejection
            $rejected = $statuses->firstWhere('status', 'Rejected');
            if ($rejected) {
                $rejectedBy = $rank[$rejected->approver_rank] ?? $rejected->approver_rank;
                $leaveRequest->status_text = "Rejected by {$rejectedBy}";
            } else {
                // Check last approved
                $lastApproved = $statuses->where('status', 'Approved')->last();
                $approvedRanks = $statuses->where('status', 'Approved')->pluck('approver_rank')->map(function ($r) {
                    return strtoupper($r);
                })->unique()->toArray();

                $required = ['HOD', 'GM', 'HR'];
                if (!array_diff($required, $approvedRanks)) {
                    $leaveRequest->status_text = "Approved";
                } elseif ($lastApproved) {
                    $approvedBy = $rank[$lastApproved->approver_rank] ?? $lastApproved->approver_rank;
                    $leaveRequest->status_text = "Approved by {$approvedBy}";
                } else {
                    $leaveRequest->status_text = "Pending";
                }
            }

            // Handle combined leaves
            if ($leaveRequest->combinedLeave) {
                $leaveRequest->to_date = Carbon::parse($leaveRequest->combinedLeave->to_date)->format('d M');
                $leaveRequest->total_days += $leaveRequest->combinedLeave->total_days;
            }

            $leaveRequest->routes = route('leave.details', base64_encode($leaveRequest->id));
            return $leaveRequest;
        });

        return response()->json([
            'draw' => $request->get('draw', 1),
            'recordsTotal' => $total_records,
            'recordsFiltered' => $total_records,
            'data' => $leave_requests,
            'permission' => $permission,
        ]);
    }

    
    public function getLeaveChartData(Request $request)
    {
        $currentYear = $request->YearWiseLeaveHistory;
        // dd($currentYear);
         $loggedInEmployee = $this->resort->getEmployee;
            $loggedInEmployeeId = $loggedInEmployee->id ?? null;
        // Get all months for the selected year in "M Y" format
        $months = collect(range(1, 12))->map(function ($month) use ($currentYear) {
            return date('M Y', mktime(0, 0, 0, $month, 1, $currentYear));
        });

        // dd($currentYear,$months);

        // Calculate the date range for the selected year
        $startDate = $currentYear . '-01-01';
        $endDate = $currentYear . '-12-31';

        $rank = config('settings.Position_Rank');
        $current_rank = $this->resort->getEmployee->rank ?? null;
        $available_rank = $rank[$current_rank] ?? '';

        // Determine if the user is an HOD
        $isHOD = ($available_rank === "HOD");
        $isHR = ($available_rank === "HR");

        // Fetch leave data with aggregated counts
        $leavesDataQuery = DB::table('employees_leaves as el')
            ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
            ->join('employees as e', 'e.id', '=', 'el.emp_id')
            ->join('employees_leaves_status as els', 'els.leave_request_id', '=', 'el.id')
            ->where('el.status', "Approved")
            ->where('el.resort_id', $this->resort->resort_id)
            ->whereBetween('el.from_date', [$startDate, $endDate])
            ->groupBy('month_year', 'lc.leave_type', 'lc.color')
            ->orderByRaw('DATE_FORMAT(el.from_date, "%Y-%m")');
     
        if (!$isHR) {
            // $leavesDataQuery->whereIn('e.id', $this->underEmp_id);
            // $leavesDataQuery->where('e.reporting_to', $this->reporting_to );
            $leavesDataQuery->where('els.approver_id',$loggedInEmployeeId); // Only requests where the logged-in employee is the approver);

        }
        $leavesData = $leavesDataQuery->select(
            DB::raw("DATE_FORMAT(el.from_date, '%b %Y') as month_year"),
            'lc.leave_type',
            DB::raw('SUM(el.total_days) as total_days'),
            'lc.color as color'
        )->get();
        // dd($leavesData);

        // Extract unique leave types
        $leaveTypes = $leavesData->pluck('leave_type')->unique();

        // Create datasets for each leave type
        $datasets = $leaveTypes->map(function ($leaveType) use ($months, $leavesData) {
            $color = $leavesData->where('leave_type', $leaveType)->pluck('color')->first();

            $data = $months->map(function ($month) use ($leaveType, $leavesData) {
                $record = $leavesData->where('leave_type', $leaveType)->where('month_year', $month)->first();
                return $record ? $record->total_days : 0; // Default to 0 if no data exists
            });

            return [
                'label' => $leaveType,
                'data' => $data->toArray(),
                'backgroundColor' => $color,
                'borderColor' => '#fff',
                'borderWidth' => 2,
                'borderRadius' => 10,
            ];
        });

        return response()->json([
            'labels' => $months->toArray(),
            'datasets' => $datasets->values(),
        ]);
    }

    public function sendBirthdayNotification(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id'
        ]);

        $employee = Employee::with(['resortAdmin', 'position'])
            ->findOrFail($request->employee_id);

        if (!$employee->resortAdmin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee admin information not found'
            ], 404);
        }

        $resortId = $this->resort->resort_id;
        $senderId = $this->resort->getEmployee->id;
        $birthdayPersonName = $employee->resortAdmin->first_name." ".$employee->resortAdmin->last_name;
        $birthdayPersonID = $employee->id;
        
        $notificationMessage = "🎉 Today is the birthday of: $birthdayPersonName! 🎂 Wish them a great day!";
        $notificationTitle = "Birthday Notification";
        
        // Get all employee IDs except the birthday person
        $receivers = Employee::where('resort_id', $resortId)
            ->where('id', '!=', $employee->id)
            ->pluck('id')
            ->toArray();

        // Send notifications
        foreach ($receivers as $receiverId) {
            event(new ResortNotificationEvent(Common::nofitication(
                    $resortId, 
                    10, // Use config with fallback
                    $notificationTitle, 
                    $notificationMessage,
                    $birthdayPersonID, // Assuming this is priority
                    $receiverId,
                    'Birthday', // More specific type
                )
            ));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Birthday notifications sent to all employees!',
            'receivers_count' => count($receivers)
        ]);
    }
}