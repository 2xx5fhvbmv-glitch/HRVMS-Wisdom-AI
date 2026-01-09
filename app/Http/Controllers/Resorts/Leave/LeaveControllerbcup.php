<?php

namespace App\Http\Controllers\Resorts\Leave;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use App\Models\Resort;
use App\Models\Employee;
use App\Models\ResortAdmin;
use App\Models\EmployeeLeave;
use App\Models\LeaveCategory;
use App\Models\ResortDepartment;
use App\Models\ResortBenifitGrid;
use App\Models\EmployeeLeaveStatus;
use App\Models\EmployeeTravelPass;
use App\Models\EmployeeTravelPassStatus;
use App\Models\LeaveRecommendation;
use App\Models\ResortTransportation;
use App\Models\ResortBenifitGridChild;
use App\Models\ResortSiteSettings;
use App\Notifications\AlternativeDateSuggestedNotification;

use Auth;
use DB;
use Common;
use Config;
use Carbon\Carbon;

class LeaveController extends Controller
{
    public $resort;
    protected $underEmp_id=[];
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if($this->resort->is_master_admin == 0){
            $reporting_to = $this->resort->GetEmployee->id;
            $this->underEmp_id = Common::getSubordinates($reporting_to);
        }
    }

    public function index()
    {
        $page_title = 'Leave Application';
        $resort_id = $this->resort->resort_id;
        $emp_id =  $this->resort->GetEmployee->id;
        $rank =  $this->resort->GetEmployee->rank;
        // dd($this->resort->GetEmployee->reporting_to);

        $targetRanks = [
            array_search('HOD', config('settings.Position_Rank')),
            array_search('MGR', config('settings.Position_Rank')),
            array_search('GM', config('settings.Position_Rank')),
            array_search('HR', config('settings.Position_Rank'))
        ];
        if($this->resort->is_master_admin == 0){
            $religion = $this->resort->GetEmployee->religion;
            if($religion == "1"){
                $religion = "muslim";
            }
            $rank = $this->resort->GetEmployee->rank;
            $emp_grade = Common::getEmpGrade(1);
            // dd($emp_grade );

            $benefit_grid = Common::getBenefitGrid($emp_grade,$resort_id);

            $leave_categories = ResortBenifitGridChild::select(
                'resort_benefit_grid_child.*',
                'lc.leave_type',
                'lc.color',
                'lc.leave_category',
                'lc.combine_with_other',
                DB::raw("(SELECT SUM(el.total_days)
                            FROM employees_leaves el
                            WHERE el.emp_id = {$this->resort->GetEmployee->id}
                            AND el.leave_category_id = lc.id
                            AND el.status = 'Approved'
                            AND (
                                (el.from_date BETWEEN '" . Carbon::now()->startOfYear()->startOfMonth()->format('Y-m-d') . "' AND '" . Carbon::now()->endOfYear()->endOfMonth()->format('Y-m-d') . "')
                                OR
                                (el.to_date BETWEEN '" . Carbon::now()->startOfYear()->startOfMonth()->format('Y-m-d') . "' AND '" . Carbon::now()->endOfYear()->endOfMonth()->format('Y-m-d') . "')
                            )
                            ) as total_leave_days")
            )
            ->join('leave_categories as lc', 'lc.id', '=', 'resort_benefit_grid_child.leave_cat_id')
            ->where('resort_benefit_grid_child.rank', $benefit_grid->emp_grade)
            ->where(function ($query) use ($religion) {
                $query->where('resort_benefit_grid_child.eligible_emp_type', $this->resort->gender)
                        ->orWhere('resort_benefit_grid_child.eligible_emp_type', 'all');
                if ($religion == 'muslim') {
                    $query->orWhere('resort_benefit_grid_child.eligible_emp_type', $religion);
                }
            })
            ->where('lc.resort_id', $resort_id)
            ->get()
            ->map(function ($item) {
                $item->combine_with_other = $item->combine_with_other ?? 0;
                $item->leave_category = $item->leave_category ?? 0;
                $item->total_leave_days = $item->total_leave_days ?? 0; // Default if null
                return $item;
            });

            $Dept_id = $this->resort->GetEmployee->Dept_id;
            $delegations = DB::table('employees')
            ->join('resort_admins', 'employees.Admin_Parent_id', '=', 'resort_admins.id')
            ->where('employees.resort_id', $resort_id)
            ->whereIn('employees.rank', $targetRanks)
            // ->whereIn('employees.id', $this->underEmp_id)
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
        }
        else{
            $emp_grade = Common::getEmpGrade($rank);
            $benefit_grid = Common::getBenefitGrid($emp_grade,$resort_id);

            $leave_categories = ResortBenifitGridChild::select(
                'resort_benefit_grid_child.*',
                'lc.leave_type',
                'lc.color',
                'lc.leave_category',
                'lc.combine_with_other',
                DB::raw('SUM(el.total_days) as total_days') // Use DB::raw for aggregation
            )
            ->join('leave_categories as lc', 'lc.id', '=', 'resort_benefit_grid_child.leave_cat_id')
            ->leftJoin('employees_leaves as el', 'el.leave_category_id', '=', 'lc.id')
            ->where('resort_benefit_grid_child.rank', $benefit_grid->emp_grade)
            ->where(function ($query) {
                $query->where('resort_benefit_grid_child.eligible_emp_type', $this->resort->gender)
                        ->orWhere('resort_benefit_grid_child.eligible_emp_type', "all");
            })
            ->where('lc.resort_id', $resort_id)
            ->group_by('el.leave_category_id')
            ->get()
            ->map(function ($item) {
                $item->combine_with_other = $item->combine_with_other ?? 0;
                $item->leave_category = $item->leave_category ?? 0;
                return $item;
            });


            // dd($leave_categories);


            $delegations = DB::table('employees')
            ->join('resort_admins', 'employees.Admin_Parent_id', '=', 'resort_admins.id')
            ->where('employees.resort_id', $resort_id)
            ->whereIn('employees.rank', $targetRanks)
            ->select(
                'employees.*',
                'resort_admins.first_name as first_name',
                'resort_admins.last_name as last_name',
                'resort_admins.email as admin_email'
            )
            ->get();
        }
        $transportations = ResortTransportation::where('resort_id', $resort_id)
            ->pluck('transportation_option','id')
            ->toArray();
        return view('resorts.leaves.leave.index', compact('page_title', 'emp_id','leave_categories', 'delegations', 'transportations'));
    }

    public function request()
    {
        try {
            $page_title = 'Leave Requests';
            $resort_id = $this->resort->resort_id;
            $resort_departments = ResortDepartment::where('resort_id',$this->resort->resort_id)->where('status','active')->get();

            // Retrieve the logged-in user's employee details
            $loggedInEmployee = $this->resort->getEmployee;
            if (!$loggedInEmployee) {
                abort(403, "Access Denied");
            }
            $rank = config('settings.Position_Rank');
            $current_rank = $this->resort->getEmployee->rank ?? null;
            $available_rank = $rank[$current_rank] ?? '';
            $isHOD = ($available_rank === "HOD");
            $isHR = ($available_rank === "HR");

            $leave_requests_query = DB::table('employees_leaves as el')
                // ->join('employees_leaves_status as els', 'els.leave_request_id', '=', 'el.id')
                ->join('employees as e', 'e.id', '=', 'el.emp_id')
                ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
                ->join('resort_departments as rd', 'rd.id', '=', 'e.Dept_id')
                ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
                ->where('el.status', 'Pending');

                if ($isHR) {
                    $leave_requests_query->where('e.id', '!=', $this->resort->getEmployee->id); // Exclude HR's own requests
                        // ->whereNotIn('rp.rank', ['GM']); // Exclude higher hierarchy roles (e.g., GM, HOD)
                }

                // Additional conditions for HOD
                if ($isHOD) {
                    // $leave_requests_query->whereIn('e.id', $this->underEmp_id); // Only employees under HOD
                    $leave_requests_query->where('e.reporting_to', $reporting_to ); // Only employees under HOD

                }

                $leaveRequests = $leave_requests_query->select(
                    'el.*',
                    'e.Emp_id as employee_id',
                    'e.rank',
                    'el.status as leave_status',
                    'ra.first_name as first_name',
                    'ra.last_name as last_name',
                    'ra.profile_picture',
                    'rp.position_title as position',
                    'rd.name as department',
                    'lc.leave_type as leave_type',
                    'lc.color',
                    'lc.combine_with_other',
                    'lc.leave_category',
                    'lc.id as leave_category_main_id',
                    'el.flag'
                )->paginate(10);

                $leaveRequests->getCollection()->transform(function ($leaveRequest) use ($resort_id) {
                    // dd($leaveRequest);
                    $leaveRequest->profile_picture = Common::getResortUserPicture($leaveRequest->employee_id);

                    // Fetch employee grade and benefit grid
                    $emp_grade = Common::getEmpGrade($leaveRequest->rank);
                    $benefit_grid = DB::table('resort_benifit_grid as rbg')
                        ->join('resort_benefit_grid_child as rbgc', 'rbg.id', '=', 'rbgc.benefit_grid_id')
                        ->where('rbg.emp_grade', $emp_grade)
                        ->where('rbgc.leave_cat_id', $leaveRequest->leave_category_id)
                        ->first();

                    // Calculate total leaves taken by the employee for the current year
                    $currentYearStart = Carbon::now()->startOfYear()->format('Y-m-d');
                    $currentYearEnd = Carbon::now()->endOfYear()->format('Y-m-d');

                    $leavesTaken = DB::table('employees_leaves')
                        ->where('emp_id', $leaveRequest->emp_id)
                        ->where('leave_category_id', $leaveRequest->leave_category_id)
                        ->where('status', 'Approved')
                        ->where(function ($query) use ($currentYearStart, $currentYearEnd) {
                            $query->whereBetween('from_date', [$currentYearStart, $currentYearEnd])
                                  ->orWhereBetween('to_date', [$currentYearStart, $currentYearEnd]);
                        })
                        ->sum('total_days');
                    $leaveAllocation = $benefit_grid->allocated_days ?? 0;
                    $leaveRequest->available_balance = $leaveAllocation - $leavesTaken;

                    return $leaveRequest;
                });

                $mergecollection=array();
                $currentYearStart = Carbon::now()->startOfYear()->format('Y-m-d');
                $currentYearEnd = Carbon::now()->endOfYear()->format('Y-m-d');

                foreach($leaveRequests as $k => $leaveRequest)
                {
                    if (isset($leaveRequest->flag))
                    {
                        $matchLeaveCheck = DB::table('employees_leaves')->join('leave_categories as t1','t1.id',"=",'employees_leaves.leave_category_id')
                            ->where('employees_leaves.emp_id', $leaveRequest->emp_id)
                            ->where('employees_leaves.leave_category_id', $leaveRequest->leave_category_id)
                            ->where('t1.leave_category', $leaveRequest->leave_category)
                            ->where('employees_leaves.status', 'Pending')
                            ->where('employees_leaves.id',$leaveRequest->id)
                            ->where(function ($query) use ($currentYearStart, $currentYearEnd) {
                                $query->whereBetween('employees_leaves.from_date', [$currentYearStart, $currentYearEnd])
                                        ->orWhereBetween('employees_leaves.to_date', [$currentYearStart, $currentYearEnd]);
                            })
                        ->get(['t1.leave_type','t1.leave_category','total_days','from_date','to_date','t1.color'])->toArray();
                        $mergecollection[$leaveRequest->flag][] = $matchLeaveCheck;
                    }
                }
                foreach ($leaveRequests as $k => $leaveRequest)
                {
                    if (array_key_exists($leaveRequest->id, $mergecollection)) {
                        $leaveRequest->CombineLeave = $mergecollection[$leaveRequest->id];
                    }
                }
                $combinedLeaveRequests = $leaveRequests->filter(function ($leaveRequest) {
                    return $leaveRequest->combine_with_other == 1;
                });
                $separateLeaveRequests = $leaveRequests->filter(function ($leaveRequest) {
                    return $leaveRequest->combine_with_other == 0;
                });

                $finalLeaveRequests = collect();

                // Step 1: Loop through each item in the combined leave requests
                foreach ($combinedLeaveRequests as $combinedLeave)
                {
                    $flag = $combinedLeave->flag;
                    if( $flag == null)
                    {
                        $existsInSeparate = $separateLeaveRequests->first(function ($separateLeave) use ($flag) {
                            return $separateLeave->id == $flag;
                        });

                        if (!$existsInSeparate) {
                            $finalLeaveRequests->push($combinedLeave);
                        }
                    }
                }
                $finalLeaveRequests = $finalLeaveRequests->merge($separateLeaveRequests);
                // dd($finalLeaveRequests);

                return view('resorts.leaves.leave.request', compact('finalLeaveRequests', 'page_title','resort_departments'));
        } catch (\Exception $e) {
            \Log::error('Leave Application Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            // Optionally, return an error view or message
            return response()->view('errors.500', ['message' => 'An unexpected error occurred.'], 500);
        }
    }

    public function filterLeaveGridRequests(Request $request)
    {
        try {
            $resort_id = $this->resort->resort_id;
            $loggedInEmployee = $this->resort->getEmployee;

            if (!$loggedInEmployee) {
                abort(403, "Access Denied");
            }

            $rank = config('settings.Position_Rank');
            $current_rank = $loggedInEmployee->rank ?? null;
            $available_rank = $rank[$current_rank] ?? '';
            $isHOD = ($available_rank === "HOD");
            $isHR = ($available_rank === "HR");

            $query = DB::table('employees_leaves as el')
                ->join('employees as e', 'e.id', '=', 'el.emp_id')
                ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
                ->join('resort_departments as rd', 'rd.id', '=', 'e.Dept_id')
                ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
                ->where('el.status', 'Pending');

            if ($isHR) {
                $query->where('e.id', '!=', $loggedInEmployee->id);
                    // ->whereNotIn('rp.rank', ['GM']);
            }

            if ($isHOD && isset($this->underEmp_id)) {
                // $query->whereIn('e.id', $this->underEmp_id);
                $query->where('e.reporting_to', $reporting_to ); // Only employees under HOD

            }

            if ($request->search) {
                $query->where(function ($q) use ($request) {
                    $q->where('ra.first_name', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('ra.last_name', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('e.Emp_id', 'LIKE', '%' . $request->search . '%');
                });
            }

            if ($request->department) {
                $query->where('e.Dept_id', $request->department);
            }

            if ($request->position) {
                $query->where('e.Position_id', $request->position);
            }

            $query->select(
                'el.*',
                    'e.Emp_id as employee_id',
                    'e.rank',
                    'el.status as leave_status',
                    'ra.first_name as first_name',
                    'ra.last_name as last_name',
                    'ra.profile_picture',
                    'rp.position_title as position',
                    'rd.name as department',
                    'lc.leave_type as leave_type',
                    'lc.color',
                    'lc.combine_with_other',
                    'lc.leave_category',
                    'lc.id as leave_category_main_id',
                    'el.flag'
            );

            $leaveRequests = $query->paginate(10);

            // $leaveRequests->transform(function ($leaveRequest) {
            //     $leaveRequest->profile_picture = Common::getResortUserPicture($leaveRequest->employee_id);

            //     $emp_grade = Common::getEmpGrade($leaveRequest->rank);
            //     $benefit_grid = DB::table('resort_benifit_grid as rbg')
            //         ->join('resort_benefit_grid_child as rbgc', 'rbg.id', '=', 'rbgc.benefit_grid_id')
            //         ->where('rbg.emp_grade', $emp_grade)
            //         ->where('rbgc.leave_cat_id', $leaveRequest->leave_category_id)
            //         ->first();

            //     $currentYearStart = Carbon::now()->startOfYear()->format('Y-m-d');
            //     $currentYearEnd = Carbon::now()->endOfYear()->format('Y-m-d');
            //     $leavesTaken = DB::table('employees_leaves')
            //         ->where('emp_id', $leaveRequest->emp_id)
            //         ->where('leave_category_id', $leaveRequest->leave_category_id)
            //         ->where('status', 'Approved')
            //         ->whereBetween('from_date', [$currentYearStart, $currentYearEnd])
            //         ->sum('total_days');

            //     $leaveAllocation = $benefit_grid->allocated_days ?? 0;
            //     $leaveRequest->available_balance = $leaveAllocation - $leavesTaken;
            //     // dd($leaveRequest);

            //     return $leaveRequest;
            // });

            $leaveRequests->getCollection()->transform(function ($leaveRequest) use ($resort_id) {
                // dd($leaveRequest);
                $leaveRequest->profile_picture = Common::getResortUserPicture($leaveRequest->employee_id);

                // Fetch employee grade and benefit grid
                $emp_grade = Common::getEmpGrade($leaveRequest->rank);
                $benefit_grid = DB::table('resort_benifit_grid as rbg')
                    ->join('resort_benefit_grid_child as rbgc', 'rbg.id', '=', 'rbgc.benefit_grid_id')
                    ->where('rbg.emp_grade', $emp_grade)
                    ->where('rbgc.leave_cat_id', $leaveRequest->leave_category_id)
                    ->first();

                // Calculate total leaves taken by the employee for the current year
                $currentYearStart = Carbon::now()->startOfYear()->format('Y-m-d');
                $currentYearEnd = Carbon::now()->endOfYear()->format('Y-m-d');

                $leavesTaken = DB::table('employees_leaves')
                    ->where('emp_id', $leaveRequest->emp_id)
                    ->where('leave_category_id', $leaveRequest->leave_category_id)
                    ->where('status', 'Approved')
                    ->where(function ($query) use ($currentYearStart, $currentYearEnd) {
                        $query->whereBetween('from_date', [$currentYearStart, $currentYearEnd])
                              ->orWhereBetween('to_date', [$currentYearStart, $currentYearEnd]);
                    })
                    ->sum('total_days');
                $leaveAllocation = $benefit_grid->allocated_days ?? 0;
                $leaveRequest->available_balance = $leaveAllocation - $leavesTaken;

                return $leaveRequest;
            });

            $mergecollection=array();
            $currentYearStart = Carbon::now()->startOfYear()->format('Y-m-d');
            $currentYearEnd = Carbon::now()->endOfYear()->format('Y-m-d');

            foreach($leaveRequests as $k => $leaveRequest)
            {
                if (isset($leaveRequest->flag))
                {
                    $matchLeaveCheck = DB::table('employees_leaves')->join('leave_categories as t1','t1.id',"=",'employees_leaves.leave_category_id')
                        ->where('employees_leaves.emp_id', $leaveRequest->emp_id)
                        ->where('employees_leaves.leave_category_id', $leaveRequest->leave_category_id)
                        ->where('t1.leave_category', $leaveRequest->leave_category)
                        ->where('employees_leaves.status', 'Pending')
                        ->where('employees_leaves.id',$leaveRequest->id)
                        ->where(function ($query) use ($currentYearStart, $currentYearEnd) {
                            $query->whereBetween('employees_leaves.from_date', [$currentYearStart, $currentYearEnd])
                                    ->orWhereBetween('employees_leaves.to_date', [$currentYearStart, $currentYearEnd]);
                        })
                    ->get(['t1.leave_type','t1.leave_category','total_days','from_date','to_date','t1.color'])->toArray();
                    $mergecollection[$leaveRequest->flag][] = $matchLeaveCheck;
                }
            }
            foreach ($leaveRequests as $k => $leaveRequest)
            {
                if (array_key_exists($leaveRequest->id, $mergecollection)) {
                    $leaveRequest->CombineLeave = $mergecollection[$leaveRequest->id];
                }
            }
            $combinedLeaveRequests = $leaveRequests->filter(function ($leaveRequest) {
                return $leaveRequest->combine_with_other == 1;
            });
            $separateLeaveRequests = $leaveRequests->filter(function ($leaveRequest) {
                return $leaveRequest->combine_with_other == 0;
            });

            $finalLeaveRequests = collect();

            // Step 1: Loop through each item in the combined leave requests
            foreach ($combinedLeaveRequests as $combinedLeave)
            {
                $flag = $combinedLeave->flag;
                if( $flag == null)
                {
                    $existsInSeparate = $separateLeaveRequests->first(function ($separateLeave) use ($flag) {
                        return $separateLeave->id == $flag;
                    });

                    if (!$existsInSeparate) {
                        $finalLeaveRequests->push($combinedLeave);
                    }
                }
            }
            $finalLeaveRequests = $finalLeaveRequests->merge($separateLeaveRequests);

             // Render the view with the filtered data
            $html = view('resorts.renderfiles.leave-requests-grid', ['finalLeaveRequests' => $finalLeaveRequests])->render();

            return response()->json(['html' => $html]);
        } catch (\Exception $e) {
            \Log::error('Filter Leave Requests Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json(['error' => 'An unexpected error occurred.'], 500);
        }
    }

    public function details($leave_id)
    {
        $page_title = 'Leave Details';
        $resort_id = $this->resort->resort_id;
        $decodedId = base64_decode($leave_id);
        $leave_categories = LeaveCategory::where('resort_id',$resort_id)->get();
        // Fetch the leave details for the specific leave ID
        $leave_details_query = DB::table('employees_leaves as el')
        ->join('employees_leaves_status as els', 'els.leave_request_id', '=', 'el.id')
        ->join('employees as e', 'e.id', '=', 'el.emp_id') // Main employee
        ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id') // Main employee admin details
        ->join('employees as delegated_emp', 'delegated_emp.id', '=', 'el.task_delegation') // Delegated employee
        ->join('resort_admins as ra_td', 'ra_td.id', '=', 'delegated_emp.Admin_Parent_id') // Task delegation admin details
        ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
        ->join('resort_departments as rd', 'rd.id', '=', 'e.Dept_id')
        ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
        ->where('el.id', $decodedId);
        $leaveDetail = $leave_details_query->select(
            'el.*',
            'e.Emp_id as employee_id',
            'e.rank',
            'els.status as leave_status',
            'els.approver_rank',
            'els.approver_id',
            // Main employee details
            'ra.first_name as employee_first_name',
            'ra.last_name as employee_last_name',
            'ra.profile_picture as employee_profile_picture',
            'rp.position_title as position',
            'rd.name as department',
            // Task delegation details
            'delegated_emp.Emp_id as task_delegation_emp_id',
            'ra_td.first_name as task_delegation_first_name',
            'ra_td.last_name as task_delegation_last_name',
            'ra_td.profile_picture as task_delegation_profile_picture',
            // Leave category details
            'lc.leave_type as leave_type',
            'lc.color'
        )->first();
        // dd($leaveDetail);

        if ($leaveDetail) {
            $combinedLeave = EmployeeLeave::where('flag',$leaveDetail->leave_category_id)
                ->join('leave_categories as lc','lc.id','=','employees_leaves.leave_category_id')
                ->first();
            // dd($combinedLeave);
            // Fetch total leave allocation for the employee
            $emp_grade = Common::getEmpGrade($leaveDetail->rank);

            $benefit_grid = DB::table('resort_benifit_grid as rbg')
                ->join('resort_benefit_grid_child as rbgc', 'rbg.id', '=', 'rbgc.benefit_grid_id')
                ->where('rbg.emp_grade', $emp_grade)
                ->get();
            // Calculate total leaves taken by the employee for the current year
            $currentYearStart = Carbon::now()->startOfYear()->format('Y-m-d');
            $currentYearEnd = Carbon::now()->endOfYear()->format('Y-m-d');
            $leavesTaken = DB::table('employees_leaves')
                ->where('emp_id', $leaveDetail->employee_id)
                ->where('status', 'Approved')
                ->where(function ($query) use ($currentYearStart, $currentYearEnd) {
                    $query->whereBetween('from_date', [$currentYearStart, $currentYearEnd])
                        ->orWhereBetween('to_date', [$currentYearStart, $currentYearEnd]);
                })
                ->sum('total_days');

            // Total allocation (sum of all allocated days across leave categories)
            $totalAllocation = $benefit_grid->sum('allocated_days');
            // Attach leave balance information
            $leaveDetail->total_leave_allocation = $totalAllocation;
            $leaveDetail->leaves_taken = $leavesTaken;
            $leaveDetail->combinedLeave = $combinedLeave;

            // Update profile picture dynamically
            $leaveDetail->employee_profile_picture = Common::getResortUserPicture($leaveDetail->employee_id);
        }
        if (!$leaveDetail) {
            abort(404, "Leave details not found.");
        }
        // Fetch employee grade and benefit grid
        $emp_grade = Common::getEmpGrade($leaveDetail->rank);
        $benefit_grids = DB::table('resort_benifit_grid as rbg')
            ->join('resort_benefit_grid_child as rbgc', 'rbg.id', '=', 'rbgc.benefit_grid_id')
            ->join('leave_categories as lc', 'lc.id', '=', 'rbgc.leave_cat_id')
            ->where('rbg.emp_grade', $emp_grade)
            ->where('rbgc.rank',$leaveDetail->rank)
            ->select(
                'lc.id as leave_category_id',
                'lc.leave_type',
                'lc.color',
                'rbgc.allocated_days'
            )
            ->get();
        $empID = $leaveDetail->emp_id;
        // Calculate total leaves taken for each category for the current year
        $currentYearStart = Carbon::now()->startOfYear()->format('Y-m-d');
        $currentYearEnd = Carbon::now()->endOfYear()->format('Y-m-d');
        $leaveUsage = DB::table('employees_leaves')
            ->select('leave_category_id', DB::raw('SUM(total_days) as used_days'))
            ->where('emp_id', $leaveDetail->emp_id)
            ->where('status', 'Approved')
            ->where(function ($query) use ($currentYearStart, $currentYearEnd) {
                $query->whereBetween('from_date', [$currentYearStart, $currentYearEnd])
                    ->orWhereBetween('to_date', [$currentYearStart, $currentYearEnd]);
            })
            ->groupBy('leave_category_id')
            ->get()
            ->keyBy('leave_category_id');
        // dd($leaveUsage);
        // Combine leave balances and usage
        $leaveBalances = $benefit_grids->map(function ($grid) use ($leaveUsage) {
            $usedDays = $leaveUsage->get($grid->leave_category_id)->used_days ?? 0;
            $grid->used_days = $usedDays;
            $grid->available_days = $grid->allocated_days - $usedDays;
            return $grid;
        });
        dd($leaveDetail);
        return view('resorts.leaves.leave.details', compact('page_title', 'empID','leaveDetail', 'leaveBalances','leaveUsage','leave_categories'));
    }

    public function getLeaveHistory(Request $request)
    {
        // Validate the request
        $request->validate([
            'empID' => 'required|integer',
        ]);
        // dd($request->all());
        $empID = $request->empID;
        $leave_catId = $request->leave_catId;

        // Define the query
        $leaveUsageQuery = DB::table('employees_leaves')
            ->join('leave_categories', 'employees_leaves.leave_category_id', '=', 'leave_categories.id')
            ->leftJoin('employees_leaves_status as els', function($join) {
                $join->on('els.leave_request_id', '=', 'employees_leaves.id')
                     ->whereRaw('els.id = (SELECT MAX(id) FROM employees_leaves_status WHERE leave_request_id = employees_leaves.id AND status != "Pending")');
            })
            ->where('employees_leaves.flag', null)
            ->where('employees_leaves.emp_id', $empID);

        // Apply category filter if provided
        if (!empty($leave_catId)) {
            $leaveUsageQuery->where('employees_leaves.leave_category_id', $leave_catId);
        }

        // Select required columns
        $leaveUsageQuery->select(
            'employees_leaves.id',
            'leave_categories.leave_type as leave_category',
            'employees_leaves.reason',
            DB::raw('DATE_FORMAT(employees_leaves.from_date, "%Y-%m-%d") as from_date'),
            DB::raw('DATE_FORMAT(employees_leaves.to_date, "%Y-%m-%d") as to_date'),
            'employees_leaves.total_days',
            'employees_leaves.attachments',
            'employees_leaves.status',
            'els.status as last_status',
            'els.approver_rank as approver_rank'
        );

        // Get total records before applying pagination
        $total_records = $leaveUsageQuery->count();

        // Apply pagination
        $start = $request->get('start', 0);
        $length = $request->get('length', 10);
        $leaveUsage = $leaveUsageQuery
            ->offset($start)
            ->limit($length)
            ->get();

        $leaveUsage = $leaveUsage->map(function($usage) {
            $usage->from_date = Carbon::parse($usage->from_date)->format('d M');
            $usage->to_date = Carbon::parse($usage->to_date)->format('d M');

            $usage->combinedLeave = EmployeeLeave::where('flag',$usage->id)
            ->join('leave_categories as lc','lc.id','=','employees_leaves.leave_category_id')
            ->first();
    
            // Set readable status
            $role = ucfirst(strtolower($usage->approver_rank ?? ''));

            $rank = config('settings.Position_Rank');
            $role = $rank[$role] ?? '';
            $usage->to_date =  (isset($usage->combinedLeave) ? $usage->combinedLeave->to_date :$usage->to_date);
            $usage->to_date = Carbon::parse($usage->to_date)->format('d M');
            $usage->status_text = $usage->last_status ? "{$usage->last_status} by {$role}" : 'Pending';
            $usage->total_days = (isset($usage->combinedLeave) ? ($usage->combinedLeave->total_days + $usage->total_days) :$leaveRequest->total_days);

            return $usage;
        });

        // dd( $leaveUsage);

        // Return response
        return response()->json([
            'draw' => $request->get('draw', 1),
            'recordsTotal' => $total_records,
            'recordsFiltered' => $total_records, // Adjust if filters are applied
            'data' => $leaveUsage
        ]);
    }

    public function store(Request $request)
    {
        $resort_id = $this->resort->resort_id;
        $emp_id = $this->resort->GetEmployee->id;
        $rank = $this->resort->GetEmployee->rank;

        DB::beginTransaction();
        try {
            // dd($request->all());
            // Define leave attachment path
            $leave_attachment = config('settings.leave_attachments');
            $dynamic_path = $leave_attachment . '/' . $emp_id;

            // Create directory if it doesn't exist
            if (!Storage::exists($dynamic_path)) {
                Storage::makeDirectory($dynamic_path);
            }

            // Handle file upload
            $filePath = null;
            if ($request->hasFile('attachments')) {
                $fileName = uniqid('attachment_', true) . '.' . $request->attachments->getClientOriginalExtension();
                $filePath = $dynamic_path . '/' . $fileName;
                $request->attachments->move(public_path($dynamic_path), $fileName);
            }

            foreach ($request->leave_category_id as $key => $categoryId) {
                $leaveDetails = LeaveCategory::where('id',$categoryId)->first();
                $currentFlag = null;
                if ($leaveDetails->combine_with_other == 1) {
                    $currentFlag = $leaveDetails->leave_category;
                }
                           
                $fromDate = \Carbon\Carbon::createFromFormat('d/m/Y', $request->from_date[$key]);
                $toDate = \Carbon\Carbon::createFromFormat('d/m/Y', $request->to_date[$key]);

                // Validate that to_date >= from_date
                if ($toDate->lt($fromDate)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "To date must be the same or after from date for leave category ID $categoryId!",
                    ]);
                }

                // Calculate total days (inclusive)
                $totalDays = $fromDate->diffInDays($toDate) + 1;

                // Get the leave category details for the current category ID
                $leaveCategory = DB::table('leave_categories')->where('id', $categoryId)->first();
                if (!$leaveCategory) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Leave category with ID $categoryId does not exist.",
                    ]);
                }

                // Get the employee grade and leave balances
                $emp_grade = Common::getEmpGrade($rank);

                // Fetch the benefit grid (allocated days) for the employee's grade and rank
                $benefit_grid = DB::table('resort_benifit_grid as rbg')
                    ->join('resort_benefit_grid_child as rbgc', 'rbg.id', '=', 'rbgc.benefit_grid_id')
                    ->where('rbg.emp_grade', $emp_grade)
                    ->where('rbgc.rank', $rank)
                    ->where('rbgc.leave_cat_id', $categoryId)
                    ->select('rbgc.allocated_days')
                    ->first();

                if (!$benefit_grid) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "No benefit grid found for this employee's rank and leave category.",
                    ]);
                }

                $allocatedDays = $benefit_grid->allocated_days;

                // Get the total used days for the current leave category within the current year
                $currentYearStart = Carbon::now()->startOfYear()->format('Y-m-d');
                $currentYearEnd = Carbon::now()->endOfYear()->format('Y-m-d');

                $leaveUsage = DB::table('employees_leaves')
                    ->select(DB::raw('SUM(total_days) as used_days'))
                    ->where('emp_id', $emp_id)
                    ->where('status', 'Approved')
                    ->where('leave_category_id', $categoryId)
                    ->where(function ($query) use ($currentYearStart, $currentYearEnd) {
                        $query->whereBetween('from_date', [$currentYearStart, $currentYearEnd])
                            ->orWhereBetween('to_date', [$currentYearStart, $currentYearEnd]);
                    })
                    ->groupBy('leave_category_id')
                    ->first();

                $usedDays = $leaveUsage->used_days ?? 0;
                $availableDays = $allocatedDays - $usedDays;

                // Check if the requested leave exceeds the available days for the category
                if ($totalDays > $availableDays) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "You cannot apply for more days than your remaining balance in the {$leaveCategory->leave_type} category! Available: $availableDays days.",
                    ]);
                }

                // Create leave record
                $leave = EmployeeLeave::create([
                    'resort_id' => $resort_id,
                    'emp_id' => $emp_id,
                    'leave_category_id' => $categoryId,
                    'from_date' => $fromDate,
                    'to_date' => $toDate,
                    'total_days' => $totalDays,
                    'reason' => $request->reason,
                    'flag' =>  $currentFlag,
                    'task_delegation' => $request->task_delegation,
                    'destination' => $request->destination,
                    'attachments' => $filePath,
                    'status' => "Pending",
                ]);
                
                // Save transportation and dates if provided
                if ($request->has('transportation')) {
                    // dd($request->transportation);
                    foreach ($request->transportation as $key => $transportMode) {
                        // dd($request->arrival_date[$transportMode]);
                        // Ensure all fields exist for the current index
                        if (
                            isset($request->arrival_date[$transportMode], $request->departure_date[$transportMode],
                                    $request->arrival_time[$transportMode], $request->departure_time[$transportMode])
                        ) 
                        {
                             // Check conditions for Entry, Exit, or Boarding pass
                            if ($request->arrival_date[$transportMode] && $request->departure_date[$transportMode]) {
                                $passType = 'Boarding';  // Both arrival and departure details provided
                            } elseif ($request->arrival_date[$transportMode] && !$request->departure_date[$transportMode]) {
                                $passType = 'Entry';  // Only arrival details provided
                            } elseif (!$request->arrival_date[$transportMode] && $request->departure_date[$transportMode]) {
                                $passType = 'Exit';  // Only departure details provided
                            }
                            // Generate Entry Pass
                            $entryPass = EmployeeTravelPass::create([
                                'resort_id' => $resort_id,
                                'employee_id' => $emp_id,
                                'leave_request_id' => $leave->id,
                                'pass_type' => $passType,  // Entry pass
                                'transportation' => $transportMode,
                                'arrival_date' => $request->arrival_date[$transportMode] ? \Carbon\Carbon::createFromFormat('Y-m-d', $request->arrival_date[$transportMode]) : null,
                                'arrival_time' => $request->arrival_time[$transportMode] ?: null,
                                'departure_date' => $request->departure_date[$transportMode] ? \Carbon\Carbon::createFromFormat('Y-m-d', $request->departure_date[$transportMode]) : null,
                                'departure_time' => $request->departure_time[$transportMode] ?: null,
                                'reason' => $request->reason,
                                'status' => 'Pending',
                            ]);
                                            
                            // Determine approval flow for both Entry and Exit passes
                            $passapprovalFlow = collect();
                            
                            // For Entry Pass: Use the same approval flow logic as for Leave request (you can reuse the same logic for the approval process)
                            $directReportingManagerId = $this->resort->GetEmployee->reporting_to;
                            $directReportingManager = Employee::select('id', 'rank','reporting_to')->find($directReportingManagerId); // Fetch only id and rank
                            
                            if ($directReportingManager && $directReportingManager->rank < "8") {
                                $passapprovalFlow->push($directReportingManager); // First approver: Supervisor/Manager
                        
                                // Step 2: Find the HOD for this Supervisor/Manager
                                $hod = Employee::select('id', 'rank','reporting_to')->find($directReportingManager->reporting_to);
                                if ($hod && $hod->rank < "8") {
                                    $passapprovalFlow->push($hod); // Second approver: HOD
                                }
                            }
                        
                            // Add HR and higher ranks to the approval flow
                            $hrApprover = Employee::select('id', 'rank')->where('rank', 3)->first(); // HR
                            if ($hrApprover) {
                                $passapprovalFlow->push($hrApprover); // Third approver: HR
                            }

                            // Add HR and higher ranks to the approval flow
                            $SOApprover = Employee::select('id', 'rank')->where('rank', 10)->first(); // Security Officer
                            if ($SOApprover) {
                                $passapprovalFlow->push($SOApprover); // Third approver: Security Officer
                            }
                        
                           // Create approval status for the travel pass
                            $passapprovalFlow->each(function($approver) use ($travelPass) {
                                EmployeeTravelPassStatus::create([
                                    'travel_pass_id' => $travelPass->id,
                                    'approver_id' => $approver->id,
                                    'approver_rank' => $approver->rank,
                                    'status' => 'Pending',
                                ]);
                            });
                        }
                
                    }
                }
            
                // Determine the direct reporting manager

                $directReportingManagerId = $this->resort->GetEmployee->reporting_to;
                $directReportingManager = Employee::select('id', 'rank','reporting_to')->find($directReportingManagerId); // Fetch only id and rank

                $approvalFlow = collect(); // Store the approval flow dynamically

                // Special case: If GM applies for leave, only MD approves
                if ($rank === "8") {
                    $hrApprover = Employee::select('id', 'rank', 'reporting_to')->where('rank', 3)->first();
                    if ($hrApprover) {
                        $approvalFlow = collect([$hrApprover]); // Only HR approves
                    }
                }
                else{
                    if ($directReportingManager && $directReportingManager->rank < "8") {
                        $approvalFlow->push($directReportingManager);

                        $hod = Employee::select('id', 'rank','reporting_to')->find($directReportingManager->reporting_to); // Fetch only id and rank
                        // dd("OHOD");
                        if ($hod) {
                            if ($hod && $hod->rank < "8") {
                                $approvalFlow->push($hod); // Second approver: HOD
                            }
                        }
                    }
                    // Step 3: Add HR to the approval flow (rank 3)
                    if ($rank !== "3") {
                        // dd("HR:");
                        $hrApprover = Employee::select('id', 'rank', 'reporting_to')->where('rank', 3)->first();
                        if ($hrApprover) {
                            $approvalFlow->push($hrApprover); // Third approver: HR
                        }
                    }     
                }
                // dd($approvalFlow);

                // Log the approvers for the leave request
                foreach ($approvalFlow as $approver) {
                    EmployeeLeaveStatus::create([
                        'leave_request_id' => $leave->id,
                        'approver_rank' => $approver->rank,
                        'approver_id' => $approver->id,
                        'status' => 'Pending',
                    ]);
                }
            }
            

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Leave application submitted successfully!',
                'redirect_url' => route('leave.request')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Leave Application Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to submit application. Please try again.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function getCombineInfo(Request $request)
    {
        // Get the category_id from the request
        $categoryId = $request->input('category_id'); // Assuming the key is 'category_ids' based on the AJAX data
        if (count($categoryId) == 1) {
            return response()->json([
                'status' => 'success',
                'message' => 'Valid selection.',
            ], 200);
        }

        // Find the leave categories by IDs
        $leaveCategories = LeaveCategory::whereIn('id', $categoryId)->get();

        // Check for any relation between `id` and `leave_category`
        $hasRelation = $leaveCategories->contains(function ($category) use ($categoryId) {
            // Check if the leave_category field matches one of the selected IDs in the categoryId array
            return in_array($category->leave_category, $categoryId);
        });

        // If no relation is found, return error
        if (!$hasRelation) {
            return response()->json([
                'status' => 'error',
                'message' => 'No relation exists between the selected leave categories. Please revise your selection.',
            ], 200);
        }

        // If relation exists, process valid selection
        return response()->json([
            'status' => 'success',
            'message' => 'Valid selection.',
        ], 200);
    }

    public function getEmployeesOnLeave(Request $request)
    {
        $filter = $request->input('filter', 'Today'); // Default filter: Today
        $resort_id = $this->resort->resort_id;
        $rank = config('settings.Position_Rank');
        $current_rank = $this->resort->getEmployee->rank ?? null;
        $available_rank = $rank[$current_rank] ?? '';

        // Determine if the user is an HOD
        $isHOD = ($available_rank === "HOD");

        // Determine the target date based on the filter
        $date = $filter === 'Tomorrow' ? Carbon::tomorrow() : Carbon::today();

        // Build the query to fetch employees on leave
        $employeesOnLeaveQuery = DB::table('employees_leaves as el')
            ->join('employees_leaves_status as els', 'els.leave_request_id', '=', 'el.id')
            ->join('employees as e', 'e.id', '=', 'el.emp_id')
            ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
            ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
            ->join('resort_departments as rd', 'rd.id', '=', 'e.Dept_id')
            ->where('el.resort_id', $resort_id)
            ->where('el.status', "Approved")
            ->whereDate('el.from_date', '<=', $date)
            ->whereDate('el.to_date', '>=', $date);

        if ($isHOD) {
            $employeesOnLeaveQuery->where('e.Dept_id', $this->resort->getEmployee->Dept_id);
        }

        // Fetch the results with pagination
        $employeesOnLeave = $employeesOnLeaveQuery->select(
            'el.id as leave_id',
            'ra.first_name',
            'ra.last_name',
            'ra.profile_picture',
            'lc.leave_type',
            'lc.color',
            'e.Emp_id as employee_id',
            'rp.position_title as position',
            'rd.name as department'
        )->paginate(10);

        // Update profile pictures dynamically
        $employeesOnLeave->getCollection()->transform(function ($employee) {
            $employee->profile_picture = Common::getResortUserPicture($employee->employee_id);
            return $employee;
        });
        // dd($employeesOnLeave);

        // Return the paginated results as JSON
        return response()->json($employeesOnLeave);
    }

    public function getUpcomingLeaves(Request $request)
    {
        $filter = $request->input('filter', 'week');
        $resort_id = $this->resort->resort_id;

        // Set the date range based on the filter
        $startDate = Carbon::now()->startOfWeek();
        $endDate = Carbon::now()->endOfWeek();
        if ($filter === 'month') {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();
        }

        $rank = config('settings.Position_Rank');
        $current_rank = $this->resort->getEmployee->rank ?? null;
        $available_rank = $rank[$current_rank] ?? '';

        // Determine if the user is an HOD
        $isHOD = ($available_rank === "HOD");

        // Fetch employees on leave within the date range
        $employeesOnLeaveQuery = DB::table('employees_leaves as el')
            ->join('employees as e', 'e.id', '=', 'el.emp_id')
            ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
            ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
            ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->where('el.resort_id', $resort_id)
            ->where('el.status', "Approved")
            ->whereDate('el.from_date', '<=', $endDate)
            ->whereDate('el.to_date', '>=', $startDate);

            if ($isHOD) {
                $employeesOnLeaveQuery->where('e.Dept_id', $this->resort->getEmployee->Dept_id);
            }
            $employeesOnLeave = $employeesOnLeaveQuery->select(
                'ra.first_name',
                'ra.last_name',
                'rp.position_title as position',
                'lc.leave_type',
                'lc.color as color',
                'el.from_date',
                'el.to_date',
                DB::raw("DATEDIFF(el.to_date, el.from_date) + 1 as total_days")
            )
            ->paginate(10);



        // Format the response
        $data = $employeesOnLeave->map(function ($employee) {
            $employee->profile_picture = Common::getResortUserPicture($employee->first_name); // Modify as needed
            $employee->leave_dates = Carbon::parse($employee->from_date)->format('d M') . ' - ' . Carbon::parse($employee->to_date)->format('d M');
            return $employee;
        });

        return response()->json(['data' => $data]);
    }

    public function downloadPdf(Request $request,$empID)
    {
        $page_title = "Leave History";
        $decodedId = base64_decode($empID);

        // Fetch leave data
        $leaveUsageQuery = DB::table('employees_leaves')
            ->join('leave_categories', 'employees_leaves.leave_category_id', '=', 'leave_categories.id')
            ->join('employees','employees.id','=','employees_leaves.emp_id')
            ->join('resort_admins','resort_admins.id','=','employees.Admin_Parent_id')
            ->join('resort_positions as rp', 'rp.id', '=', 'employees.Position_id')
            ->where('employees_leaves.emp_id', $decodedId);

        $leaveUsage = $leaveUsageQuery->select(
            'employees_leaves.*',
            'leave_categories.leave_type as leave_category',
            'employees_leaves.reason',
            DB::raw('DATE_FORMAT(employees_leaves.from_date, "%Y-%m-%d") as from_date'),
            DB::raw('DATE_FORMAT(employees_leaves.to_date, "%Y-%m-%d") as to_date'),
            'employees_leaves.total_days',
            'employees_leaves.attachments',
            'employees.Emp_id as employee_id',
            'employees_leaves.status',
            'employees_leaves.resort_id',
            'employees.rank',
            'employees.emp_id as Emp_Code',
            'resort_admins.profile_picture',
            'resort_admins.first_name',
            'resort_admins.last_name',
            'rp.position_title'
        )->get();

        // dd($leaveUsage);

        $emp_grade = Common::getEmpGrade($leaveUsage[0]->rank);

        $benefit_grids = DB::table('resort_benifit_grid as rbg')
            ->join('resort_benefit_grid_child as rbgc', 'rbg.id', '=', 'rbgc.benefit_grid_id')
            ->join('leave_categories as lc', 'lc.id', '=', 'rbgc.leave_cat_id')
            ->where('rbg.emp_grade', $emp_grade)
            ->where('rbgc.rank',$leaveUsage[0]->rank)
            ->select(
                'lc.id as leave_category_id',
                'lc.leave_type',
                'lc.color',
                'rbgc.allocated_days'
            )
            ->get();

            $leaveUsage[0]->profile_picture = Common::getResortUserPicture($leaveUsage[0]->employee_id);

            $leaveBalances = $benefit_grids->map(function ($grid) use ($leaveUsage) {
                $usedDays = $leaveUsage->get($grid->leave_category_id)->used_days ?? 0;
                $grid->used_days = $usedDays;
                $grid->available_days = $grid->allocated_days - $usedDays;
                return $grid;
            });

            $sitesettings = ResortSiteSettings::where('resort_id', $leaveUsage[0]->resort_id)->first(['resort_id','header_img','footer_img','Footer']);
            $ResortData = Resort::find($leaveUsage[0]->resort_id);
            $resort_id = $leaveUsage[0]->resort_id;

            $html = view('resorts.leaves.leave.pdf',
            [
                'sitesettings'=>$sitesettings,'resort_id'=>$resort_id,'ResortData'=>$ResortData,'leaveUsage'=>$leaveUsage,'leaveBalances'=>$leaveBalances,'page_title'=>$page_title
            ])->render();

            return $html;
    }

    public function handleLeaveAction(Request $request)
    {
        $leaveId = $request->input('leave_id');
        $action = $request->input('action'); // Approve or Reject
        $comments = $request->input('reason', null); // Optional comments
        $currentApproverId = $this->resort->GetEmployee->id;// Assuming the logged-in user is the approver
        
        $leave = EmployeeLeave::find($leaveId);

        if (!$leave) {
            return response()->json([
                'status' => 'error',
                'message' => 'Leave request not found.',
            ],200);
        }

        // Check if the current approver is authorized to take action
        $lastStatus = EmployeeLeaveStatus::where('leave_request_id', $leaveId)
            ->where('status','Pending')
            ->orderBy('created_at', 'desc')
            ->first();

        // dd($this->resort->GetEmployee->rank,$lastStatus->approver_rank);

        $rankConfig = config('settings.Position_Rank');
        $currentApproverRank = array_key_exists($this->resort->GetEmployee->rank, $rankConfig) ? $rankConfig[$this->resort->GetEmployee->rank] : '';
        $lastApproverRank = array_key_exists($lastStatus->approver_rank, $rankConfig) ? $rankConfig[$lastStatus->approver_rank] : '';
        $actionname = ($action == "Rejected") ? "reject" : "approve";
        if ($lastStatus && $lastStatus->approver_id != $currentApproverId) {
            return response()->json([
                'status' => 'error',
                'message' => "You cannot $actionname this request. The request must first be approved by the $lastApproverRank.",
            ],403);
        }

        EmployeeLeaveStatus::where('leave_request_id' , $leave->id)->where('approver_id', $currentApproverId)->update([
            'leave_request_id' => $leave->id,
            'approver_id' => $currentApproverId,
            'status' => $action,
            'comments' => $comments, // Save comments if provided
            'approved_at'=>now(),
        ]);

        if ($action == 'Approved') {

            if( $lastStatus->approver_rank == 8)
            {
                $leave->status="Approved";
                $leave->save();
            }
            return response()->json([
                'status' => 'success',
                'message' => 'Leave approved successfully.',
            ],200);
        }
        elseif ($action === 'Rejected')
        {
            $leave->status="Rejected";
            $leave->save();
            return response()->json([
                'status' => 'success',
                'message' => 'Leave Rejected.',
            ],200);
        }
        else{
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid action.',
            ],200);
        }
    }

    public function recommendAlternativeDate(Request $request)
    {
        $leaveId = $request->input('leave_id');
        $altStartDate = $request->input('alt_start_date');
        $altEndDate = $request->input('alt_end_date');
        $comments = $request->input('comments');

        $leave = EmployeeLeave::find($leaveId);

        if (!$leave) {
            return response()->json([
                'status' => 'error',
                'message' => 'Leave request not found.',
            ], 404);
        }

        // Log the recommendation in a table (e.g., LeaveRecommendations)
        LeaveRecommendation::create([
            'leave_id' => $leaveId,
            'recommended_by' => $this->resort->getEmployee->id, // Current approver
            'alt_start_date' => $altStartDate,
            'alt_end_date' => $altEndDate,
            'comments' => $comments,
        ]);

        // Notify the employee
        $this->notify(new AlternativeDateSuggestedNotification($this->resort->getEmployee->id, $altStartDate, $altEndDate, $comments));
        // Notification::send($leave->employee, new AlternativeDateSuggestedNotification($this->resort->getEmployee->id, $altStartDate, $altEndDate, $comments));

        return response()->json([
            'status' => 'success',
            'message' => 'Alternative dates suggested successfully.',
        ]);
    }

}
