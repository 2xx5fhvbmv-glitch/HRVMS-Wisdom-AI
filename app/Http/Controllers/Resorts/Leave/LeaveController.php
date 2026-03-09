<?php

namespace App\Http\Controllers\Resorts\Leave;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use DB;
use Auth;
use Common;
use Config;
use Carbon\Carbon;
use App\Models\Resort;
use App\Models\Employee;
use App\Models\TicketAgent;
use App\Models\ResortAdmin;
use App\Models\EmployeeLeave;
use App\Models\LeaveCategory;
use App\Models\ResortDepartment;
use App\Models\ResortBenifitGrid;
use App\Models\ResortSiteSettings;
use App\Models\EmployeeTravelPass;
use App\Models\EmployeeLeaveStatus;
use App\Models\LeaveRecommendation;
use App\Models\ResortTransportation;
use App\Models\ResortBenifitGridChild;
use App\Models\EmployeeTravelPassStatus;
use App\Models\EmployeesLeaveTransportation;
use App\Notifications\AlternativeDateSuggestedNotification;
use Barryvdh\DomPDF\Facade\Pdf;

class LeaveController extends Controller
{
    public $resort;
    public $reporting_to;
    protected $underEmp_id=[];
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if (!$this->resort) {
            abort(401, 'Unauthenticated.');
        }
        if (($this->resort->is_master_admin ?? 1) == 0) {
            $employee = $this->resort->GetEmployee ?? $this->resort->getEmployee ?? null;
            if ($employee) {
                $this->reporting_to = $employee->id;
                $this->underEmp_id = Common::getSubordinates($this->reporting_to);
            }
        }
    }

    public function index()
    {
        $page_title = 'Leave Application';
        $getEmployee = $this->resort->GetEmployee ?? $this->resort->getEmployee ?? null;

        if (($this->resort->is_master_admin ?? 1) == 0 && !$getEmployee) {
            $target = url()->previous() === url()->current() ? route('leave.configration') : url()->previous();
            return redirect()->to($target)->with('error', 'Your account is not linked to an employee record. Please contact HR to use Leave Application.');
        }

        $resort_id = $this->resort->resort_id;
        $emp_id = $getEmployee->id ?? 0;
        $rank = $getEmployee->rank ?? 0;
        $emp_grade_for_eligibility = Common::getEmpGrade($rank);

        $targetRanks = [
            array_search('HOD', config('settings.Position_Rank')),
            array_search('MGR', config('settings.Position_Rank')),
            array_search('GM', config('settings.Position_Rank')),
            array_search('HR', config('settings.Position_Rank'))
        ];
        if (($this->resort->is_master_admin ?? 1) == 0) {
            $religion = $getEmployee->religion ?? null;
            if ($religion == "1") {
                $religion = "muslim";
            }
            $rank = $getEmployee->rank ?? 0;
            $emp_grade = Common::getEmpGrade(1);
            // dd($emp_grade );
            $excludedLeaveTypes = ['Absent', 'Present','DayOff'];

            $benefit_grid = Common::getBenefitGrid($emp_grade,$resort_id);
            
            // Check if benefit grid exists and provide fallback values
            $benefit_grid_emp_grade = $benefit_grid->emp_grade ?? $emp_grade;
            $benefit_grid_id = $benefit_grid->id ?? null;

            $leave_categories = ResortBenifitGridChild::select(
                'resort_benefit_grid_child.*',
                'lc.leave_type',
                'lc.color',
                'lc.leave_category',
                'lc.combine_with_other',
                'lc.eligibility',
                DB::raw("(SELECT COALESCE(SUM(el.total_days), 0)
                            FROM employees_leaves el
                            WHERE el.emp_id = " . (int) ($getEmployee->id ?? 0) . "
                            AND el.leave_category_id = lc.id
                            AND el.status = 'Approved'
                            AND el.from_date <= '" . Carbon::now()->endOfYear()->format('Y-m-d') . "'
                            AND el.to_date >= '" . Carbon::now()->startOfYear()->format('Y-m-d') . "'
                            ) as total_leave_days")
            )
            ->join('leave_categories as lc', 'lc.id', '=', 'resort_benefit_grid_child.leave_cat_id')
            ->where('resort_benefit_grid_child.rank', $benefit_grid_emp_grade)
            ->when($benefit_grid_id, function($query) use ($benefit_grid_id) {
                return $query->where('resort_benefit_grid_child.benefit_grid_id', $benefit_grid_id);
            })
            ->where(function ($query) use ($religion) {
                $query->where('resort_benefit_grid_child.eligible_emp_type', $this->resort->gender)
                        ->orWhere('resort_benefit_grid_child.eligible_emp_type', 'all');
                if ($religion == 'muslim') {
                    $query->orWhere('resort_benefit_grid_child.eligible_emp_type', $religion);
                }
            })
            ->where('lc.resort_id', $resort_id)
            ->whereNotIn('leave_type', $excludedLeaveTypes)
            ->get()
            ->map(function ($item) {
                $item->combine_with_other = $item->combine_with_other ?? 0;
                $item->leave_category = $item->leave_category ?? 0;
                $item->total_leave_days = $item->total_leave_days ?? 0; // Default if null
                return $item;
            });

            $Dept_id = $getEmployee->Dept_id ?? null;
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

            $currentYearStart = Carbon::now()->startOfYear()->format('Y-m-d');
            $currentYearEnd = Carbon::now()->endOfYear()->format('Y-m-d');
            $leave_categories = ResortBenifitGridChild::select(
                'resort_benefit_grid_child.*',
                'lc.leave_type',
                'lc.color',
                'lc.leave_category',
                'lc.combine_with_other',
                'lc.eligibility',
                DB::raw("(SELECT COALESCE(SUM(el2.total_days), 0)
                            FROM employees_leaves el2
                            WHERE el2.emp_id = " . (int) $emp_id . "
                            AND el2.leave_category_id = lc.id
                            AND el2.status = 'Approved'
                            AND (
                                (el2.from_date BETWEEN '" . $currentYearStart . "' AND '" . $currentYearEnd . "')
                                OR (el2.to_date BETWEEN '" . $currentYearStart . "' AND '" . $currentYearEnd . "')
                                OR (el2.from_date <= '" . $currentYearStart . "' AND el2.to_date >= '" . $currentYearEnd . "')
                            )
                            ) as total_leave_days")
            )
            ->join('leave_categories as lc', 'lc.id', '=', 'resort_benefit_grid_child.leave_cat_id')
            ->where('resort_benefit_grid_child.rank', $benefit_grid->emp_grade)
            ->where('resort_benefit_grid_child.benefit_grid_id', $benefit_grid->id)
            ->where(function ($query) {
                $query->where('resort_benefit_grid_child.eligible_emp_type', $this->resort->gender)
                        ->orWhere('resort_benefit_grid_child.eligible_emp_type', "all");
            })
            ->where('lc.resort_id', $resort_id)
            ->get()
            ->map(function ($item) {
                $item->combine_with_other = $item->combine_with_other ?? 0;
                $item->leave_category = $item->leave_category ?? 0;
                $item->total_leave_days = (int) ($item->total_leave_days ?? 0);
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

        // Include leave categories that exist for the resort but are not yet in the benefit grid
        // (e.g. newly added categories) so they appear on the apply page with 0 allocated days
        $excludedLeaveTypes = ['Absent', 'Present', 'DayOff'];
        $existingCatIds = $leave_categories->pluck('leave_cat_id')->unique()->filter()->values()->all();
        $missingCategories = LeaveCategory::where('resort_id', $resort_id)
            ->whereNotIn('leave_type', $excludedLeaveTypes)
            ->when(!empty($existingCatIds), function ($q) use ($existingCatIds) {
                return $q->whereNotIn('id', $existingCatIds);
            })
            ->get();
        foreach ($missingCategories as $lc) {
            $leave_categories->push((object) [
                'leave_cat_id' => $lc->id,
                'leave_type' => $lc->leave_type,
                'color' => $lc->color ?? '',
                'leave_category' => $lc->leave_category ?? 0,
                'combine_with_other' => $lc->combine_with_other ?? 0,
                'eligibility' => $lc->eligibility ?? '',
                'total_leave_days' => 0,
                'allocated_days' => 0,
                'available_days' => 0,
            ]);
        }

        // Filter by leave category eligibility: only show if employee's grade is in the category's eligibility list (or eligibility is empty = all)
        $leave_categories = $leave_categories->filter(function ($item) use ($emp_grade_for_eligibility) {
            $eligibilityStr = trim($item->eligibility ?? '');
            if ($eligibilityStr === '') {
                return true;
            }
            $allowed = array_map('trim', explode(',', $eligibilityStr));
            return in_array((string) $emp_grade_for_eligibility, $allowed, true);
        })->values();

        // Compute available_days (allocated - used + carry forward) so apply page allows using extended/carry forward leave
        $leaveCatIds = $leave_categories->pluck('leave_cat_id')->unique()->filter()->values()->all();
        $lcCarry = $leaveCatIds ? DB::table('leave_categories')->whereIn('id', $leaveCatIds)->get()->keyBy('id') : collect();
        $lastYearStart = Carbon::now()->subYear()->startOfYear()->format('Y-m-d');
        $lastYearEnd = Carbon::now()->subYear()->endOfYear()->format('Y-m-d');
        $lastYearUsedByCat = DB::table('employees_leaves')
            ->select('leave_category_id', DB::raw('SUM(total_days) as used_days'))
            ->where('emp_id', $emp_id)
            ->where('status', 'Approved')
            ->where(function ($q) use ($lastYearStart, $lastYearEnd) {
                $q->whereBetween('from_date', [$lastYearStart, $lastYearEnd])
                    ->orWhereBetween('to_date', [$lastYearStart, $lastYearEnd]);
            })
            ->groupBy('leave_category_id')
            ->get()
            ->keyBy('leave_category_id');
        $leave_categories = $leave_categories->map(function ($item) use ($lcCarry, $lastYearUsedByCat) {
            $allocated = (int) ($item->allocated_days ?? 0);
            $usedThisYear = (int) ($item->total_leave_days ?? 0);
            $available = max(0, $allocated - $usedThisYear);
            $lc = $lcCarry->get($item->leave_cat_id);
            $carryForwardEnabled = $lc && !empty($lc->carry_forward) && $lc->carry_forward != '0';
            if ($carryForwardEnabled) {
                $lastYearUsed = (int) ($lastYearUsedByCat->get($item->leave_cat_id)->used_days ?? 0);
                $unused = max($allocated - $lastYearUsed, 0);
                $carryMax = isset($lc->carry_max) && $lc->carry_max !== null && $lc->carry_max !== '' ? (int) $lc->carry_max : null;
                $carryForward = $carryMax !== null ? min($unused, $carryMax) : $unused;
                $available += $carryForward;
            }
            $item->available_days = max(0, $available);
            return $item;
        });

        $transportations = ResortTransportation::where('resort_id', $resort_id)
            ->pluck('transportation_option','id')
            ->toArray();
        $leaveFormValidation = config('settings.leave_form_validation', []);
        return view('resorts.leaves.leave.index', compact('page_title', 'emp_id','leave_categories', 'delegations', 'transportations', 'leaveFormValidation'));
    }

    /**
     * Leave approval: any employee's leave is approved by their Reporting To (from Employee Details page).
     * Approval chain on submit: applicant's reporting manager is always added first; for GM, HR/EXCOM/HOD are also added.
     * Who can approve: (1) the applicant's reporting manager, or (2) for GM leave, HR/EXCOM/HOD if they have a Pending row.
     */
    public function request()
    {
        try {
            $page_title = 'Leave Requests';
            $resort_id = $this->resort->resort_id;
            $resort_departments = ResortDepartment::where('resort_id', $this->resort->resort_id)->where('status', 'active')->get();

            // Retrieve the logged-in user's employee details (use GetEmployee to match details/handleLeaveAction)
            $loggedInEmployee = $this->resort->GetEmployee ?? $this->resort->getEmployee;
            $loggedInEmployeeId = $loggedInEmployee ? ($loggedInEmployee->id ?? null) : null;
            if (!$loggedInEmployee || !$loggedInEmployeeId) {
                abort(403, "Access Denied");
            }
            $rank = config('settings.Position_Rank');
            $current_rank = $loggedInEmployee->rank ?? null;
            $available_rank = $rank[$current_rank] ?? '';
            $employeeRankPosition = Common::getEmployeeRankPosition($loggedInEmployee);
            $isGM = ($employeeRankPosition['position'] ?? '') === 'GM' || ($employeeRankPosition['rank'] ?? '') === 'GM';
            $isHOD = ($available_rank === "HOD");
            $isHR = ($available_rank === "HR");
            $isHRExcomOrGM = ($available_rank === "EXCOM") || $isGM;
            $hodDeptId = $loggedInEmployee->Dept_id ?? null;

            // HR department, EXCOM and GM can view all departments; others see only their own department
            $hrDeptId = ResortDepartment::where('resort_id', $resort_id)->where('name', 'Human Resources')->value('id');
            $isFromHRDepartment = $hrDeptId && $hodDeptId && (int) $hodDeptId === (int) $hrDeptId;
            $isEXCOM = ($available_rank === 'EXCOM');
            $canViewWholeResort = $isFromHRDepartment || $isEXCOM || $isGM;
            $isHRDeptHOD = $isHOD && $isFromHRDepartment;
            if ($isHRDeptHOD) {
                $isHR = true;
                $isHOD = false;
            }

            // Restrict department list to only their department when user cannot view whole resort
            if (!$canViewWholeResort && $hodDeptId) {
                $resort_departments = $resort_departments->filter(function ($d) use ($hodDeptId) {
                    return (int) $d->id === (int) $hodDeptId;
                })->values();
            }

            // Subquery: latest status row per leave (so we get one row per leave when we join)
            $latestStatusDerived = '(SELECT ids.leave_request_id, ids.id AS sid, ids.status, ids.approver_id FROM employees_leaves_status ids INNER JOIN (SELECT leave_request_id, MAX(id) AS mid FROM employees_leaves_status GROUP BY leave_request_id) latest ON ids.leave_request_id = latest.leave_request_id AND ids.id = latest.mid)';

            $filterYear = (int) Carbon::now()->year;
            $yearStart = Carbon::createFromDate($filterYear, 1, 1)->format('Y-m-d');
            $yearEnd = Carbon::createFromDate($filterYear, 12, 31)->format('Y-m-d');

            $leave_requests_query = DB::table('employees_leaves as el')
                ->join('employees as e', 'e.id', '=', 'el.emp_id')
                ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
                ->join('resort_departments as rd', 'rd.id', '=', 'e.Dept_id')
                ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
                ->where('el.resort_id', $resort_id)
                ->whereNull('el.flag')
                ->where(function ($q) use ($yearStart, $yearEnd) {
                    $q->where('el.from_date', '<=', $yearEnd)->where('el.to_date', '>=', $yearStart);
                });

            if ($canViewWholeResort) {
                // HR department / EXCOM / GM: whole resort, all departments by default
                $leave_requests_query->leftJoin(DB::raw($latestStatusDerived . ' AS els'), 'els.leave_request_id', '=', 'el.id');
            } elseif ($isHOD) {
                // HOD (non-HR dept): only their department
                if ($hodDeptId) {
                    $leave_requests_query->where(function ($q) use ($hodDeptId, $loggedInEmployeeId) {
                        $q->where('e.Dept_id', $hodDeptId)->orWhere('el.emp_id', $loggedInEmployeeId);
                    });
                } else {
                    $leave_requests_query->where('e.reporting_to', $loggedInEmployeeId);
                }
                $leave_requests_query->leftJoin(DB::raw($latestStatusDerived . ' AS els'), 'els.leave_request_id', '=', 'el.id');
            } else {
                // Others (non-HR dept): only leaves where current user is approver and only same department
                $leave_requests_query->join('employees_leaves_status as els', 'els.leave_request_id', '=', 'el.id')
                    ->where('els.approver_id', $loggedInEmployeeId)
                    ->whereRaw('els.id = (SELECT MAX(ids.id) FROM employees_leaves_status ids WHERE ids.leave_request_id = el.id)');
                if ($hodDeptId) {
                    $leave_requests_query->where('e.Dept_id', $hodDeptId);
                }
            }

                $leaveRequests = $leave_requests_query->select(
                    'el.*',
                    'e.Emp_id as employee_id',
                    'e.rank',
                    'e.Admin_Parent_id',
                    'e.reporting_to as reporting_to',
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
                    'el.flag',
                    'els.status AS approval_status',
                    'els.approver_id AS approver_id',
                )->paginate(10);
                    // dd($leaveRequests);
                // Can approve: (1) applicant's reporting_to, or (2) GM leave and current user is HR/EXCOM/HOD, or (3) current user has a Pending row in the chain
                $currentUserRank = trim((string)($loggedInEmployee->rank ?? ''));
                $leaveIds = $leaveRequests->getCollection()->pluck('id')->toArray();
                $leaveIdsWithCurrentUserPending = $leaveIds ? EmployeeLeaveStatus::whereIn('leave_request_id', $leaveIds)->where('approver_id', $loggedInEmployeeId)->where('status', 'Pending')->pluck('leave_request_id')->toArray() : [];
                $leaveRequests->getCollection()->transform(function ($leaveRequest) use ($resort_id, $loggedInEmployeeId, $currentUserRank, $leaveIdsWithCurrentUserPending) {
                    $leaveRequest->profile_picture = Common::getResortUserPicture($leaveRequest->Admin_Parent_id);

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

                    // Can approve: (1) applicant's reporting_to (from employee profile; only they can approve), or (2) applicant is GM and current user is HR/EXCOM/HOD
                    $applicantReportingTo = $leaveRequest->reporting_to ?? $leaveRequest->applicant_reporting_to ?? null;
                    $isReportingManager = $applicantReportingTo !== null && $applicantReportingTo !== '' && (int)$applicantReportingTo === (int)$loggedInEmployeeId;
                    $applicantRankStr = trim((string)($leaveRequest->rank ?? ''));
                    $isGMLeaveApprover = ($applicantRankStr === '8') && in_array($currentUserRank, ['1', '2', '3'], true);
                    $statusVal = $leaveRequest->status ?? $leaveRequest->leave_status ?? '';
                    $leaveIsPending = strtolower(trim((string)$statusVal)) === 'pending';
                    $leaveRequest->can_approve = (bool)(($isReportingManager || $isGMLeaveApprover) && $leaveIsPending);

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

                // can_approve: applicant's reporting_to, or GM leave and user is HR/EXCOM/HOD, or user has a Pending row (same as transform above)
                $finalLeaveIds = $finalLeaveRequests->pluck('id')->toArray();
                $finalLeaveIdsWithPending = $finalLeaveIds ? EmployeeLeaveStatus::whereIn('leave_request_id', $finalLeaveIds)->where('approver_id', $loggedInEmployeeId)->where('status', 'Pending')->pluck('leave_request_id')->toArray() : [];
                $currentUserRank = trim((string)($loggedInEmployee->rank ?? ''));
                $finalLeaveRequests->each(function ($request) use ($loggedInEmployeeId, $currentUserRank, $finalLeaveIdsWithPending) {
                    $reportingToInt = (int)($request->reporting_to ?? $request->applicant_reporting_to ?? 0);
                    $isReportingManager = $reportingToInt > 0 && $reportingToInt === (int)$loggedInEmployeeId;
                    $applicantRankStr = trim((string)($request->rank ?? ''));
                    $isGMLeaveApprover = ($applicantRankStr === '8') && in_array($currentUserRank, ['1', '2', '3'], true);
                    $hasPendingRow = in_array($request->id, $finalLeaveIdsWithPending);
                    $statusVal = $request->status ?? $request->leave_status ?? '';
                    $leaveIsPending = strtolower(trim((string)$statusVal)) === 'pending';
                    $request->can_approve = (bool)(($isReportingManager || $isGMLeaveApprover || $hasPendingRow) && $leaveIsPending);
                });

                $show_department_filter = $canViewWholeResort;
                $filter_year = $filterYear;
                $filter_years = range(Carbon::now()->year - 2, Carbon::now()->year + 1);
                return view('resorts.leaves.leave.request', compact('finalLeaveRequests', 'page_title', 'resort_departments', 'hodDeptId', 'show_department_filter', 'filter_year', 'filter_years'));
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
            $hodDeptId = $loggedInEmployee->Dept_id ?? null;

            // HR department, EXCOM and GM see all departments; others see only their own
            $employeeRankPosition = Common::getEmployeeRankPosition($loggedInEmployee);
            $isGM = ($employeeRankPosition['position'] ?? '') === 'GM' || ($employeeRankPosition['rank'] ?? '') === 'GM';
            $hrDeptId = ResortDepartment::where('resort_id', $resort_id)->where('name', 'Human Resources')->value('id');
            $isFromHRDepartment = $hrDeptId && $hodDeptId && (int) $hodDeptId === (int) $hrDeptId;
            $canViewWholeResort = $isFromHRDepartment || ($available_rank === 'EXCOM') || $isGM;

            $loggedInEmployeeId = $loggedInEmployee->id ?? null;
            $filterYear = (int) ($request->get('year') ?: Carbon::now()->year);
            $yearStart = Carbon::createFromDate($filterYear, 1, 1)->format('Y-m-d');
            $yearEnd = Carbon::createFromDate($filterYear, 12, 31)->format('Y-m-d');

            $query = DB::table('employees_leaves as el')
                ->join('employees as e', 'e.id', '=', 'el.emp_id')
                ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
                ->join('resort_departments as rd', 'rd.id', '=', 'e.Dept_id')
                ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
                ->where('el.resort_id', $resort_id)
                ->whereNull('el.flag')
                ->where(function ($q) use ($yearStart, $yearEnd) {
                    $q->where('el.from_date', '<=', $yearEnd)->where('el.to_date', '>=', $yearStart);
                });

            if ($canViewWholeResort) {
                // HR / EXCOM / GM: whole resort
            } elseif ($hodDeptId) {
                // Other departments: only their department (and own leave)
                $query->where(function ($q) use ($hodDeptId, $loggedInEmployeeId) {
                    $q->where('e.Dept_id', $hodDeptId)->orWhere('el.emp_id', $loggedInEmployeeId);
                });
            } else {
                $query->where('e.reporting_to', $this->reporting_to);
            }

            if ($request->search) {
                $query->where(function ($q) use ($request) {
                    $q->where('ra.first_name', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('ra.last_name', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('e.Emp_id', 'LIKE', '%' . $request->search . '%');
                });
            }

            if (!empty($request->department) && ($canViewWholeResort || (int) $request->department === (int) $hodDeptId)) {
                $query->where('e.Dept_id', $request->department);
            }

            if ($request->position) {
                $query->where('e.Position_id', $request->position);
            }

            $query->select(
                'el.*',
                    'e.Emp_id as employee_id',
                    'e.Admin_Parent_id',
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
                $leaveRequest->profile_picture = Common::getResortUserPicture($leaveRequest->Admin_Parent_id);

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

        if(Common::checkRouteWisePermission('leave.request',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }
  
        $page_title = 'Leave Details';
        $resort_id = $this->resort->resort_id;
        $decodedId = base64_decode($leave_id);
        $employee = $this->resort->GetEmployee ?? $this->resort->getEmployee;
        
        if (!$employee) 
        {
            $page_title = 'Leave';
            $msg = 'Please login with employee details.';
            return view('resorts.error',compact('page_title','msg'));
        }
        $leave_categories = LeaveCategory::where('resort_id',$resort_id)->get();
        $rank = config('settings.Position_Rank');
        $current_rank = $employee->rank ?? null;
        $available_rank = $rank[$current_rank] ?? '';
        // Fetch the leave details for the specific leave ID
        // leftJoin status + task_delegation so we get a row even when status/delegation is missing
        $leave_details_query = DB::table('employees_leaves as el')
        ->leftJoin('employees_leaves_status as els', function ($join) {
            $join->on('els.leave_request_id', '=', 'el.id')
                 ->whereRaw('els.id = (SELECT MAX(id) FROM employees_leaves_status WHERE leave_request_id = el.id)');
        })
        ->join('employees as e', 'e.id', '=', 'el.emp_id')
        ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
        ->leftJoin('employees as delegated_emp', 'delegated_emp.id', '=', 'el.task_delegation')
        ->leftJoin('resort_admins as ra_td', 'ra_td.id', '=', 'delegated_emp.Admin_Parent_id')
        ->leftJoin('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
        ->leftJoin('resort_departments as rd', 'rd.id', '=', 'e.Dept_id')
        ->leftJoin('resort_transportations as rt', 'rt.id', '=', 'el.transportation')
        ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
        ->where('el.id', $decodedId)
        ->where('el.resort_id', $resort_id);
        $leaveDetail = $leave_details_query->select(
            'el.*',
            'e.Emp_id as employee_id',
            'e.rank',
            'els.status as leave_status',
            'els.approver_rank',
            'els.approver_id',
            // Main employee details
            'e.Admin_Parent_id',
            'ra.first_name as employee_first_name',
            'ra.last_name as employee_last_name',
            'ra.profile_picture as employee_profile_picture',
            'rp.position_title as position',
            'rd.name as department',
            'e.reporting_to as applicant_reporting_to',
            // Task delegation details
            'delegated_emp.Emp_id as task_delegation_emp_id',
            'ra_td.first_name as task_delegation_first_name',
            'ra_td.last_name as task_delegation_last_name',
            'ra_td.profile_picture as task_delegation_profile_picture',
            // Leave category details
            'lc.leave_type as leave_type',
            'lc.color',
            // Transportation label
            'rt.transportation_option as transportation_label'
        )->first();
        // dd($leaveDetail);

        if ($leaveDetail) {
            $combinedLeave = EmployeeLeave::where('flag',$leaveDetail->leave_category_id)
                ->join('leave_categories as lc','lc.id','=','employees_leaves.leave_category_id')
                ->first();
            // dd($combinedLeave);
            // Fetch total leave allocation for the employee (same rank as used in leave balance below)
            $emp_grade = Common::getEmpGrade($leaveDetail->rank);

            $benefit_grid = DB::table('resort_benifit_grid as rbg')
                ->join('resort_benefit_grid_child as rbgc', 'rbg.id', '=', 'rbgc.benefit_grid_id')
                ->where('rbg.emp_grade', $emp_grade)
                ->where('rbg.resort_id', $resort_id)
                ->where('rbgc.rank', $leaveDetail->rank)
                ->get();
            // Calculate total leaves taken by the employee for the current year
            $currentYearStart = Carbon::now()->startOfYear()->format('Y-m-d');
            $currentYearEnd = Carbon::now()->endOfYear()->format('Y-m-d');
            $leavesTaken = DB::table('employees_leaves')
                ->where('emp_id', $leaveDetail->emp_id)
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
            $leaveDetail->employee_profile_picture = Common::getResortUserPicture($leaveDetail->Admin_Parent_id);
        }

        if (!$leaveDetail) 
        {
            $page_title = 'Leave';
            $msg = 'Leave details not found';
            return view('resorts.error',compact('page_title','msg'));

        }
        // Fetch employee grade and benefit grid
        $emp_grade = Common::getEmpGrade($leaveDetail->rank);
        
        $benefit_grids = DB::table('resort_benifit_grid as rbg')
            ->join('resort_benefit_grid_child as rbgc', 'rbg.id', '=', 'rbgc.benefit_grid_id')
            ->join('leave_categories as lc', 'lc.id', '=', 'rbgc.leave_cat_id')
            ->where('rbg.emp_grade', $emp_grade)
            ->where('rbg.resort_id', $resort_id)
            ->where('rbgc.rank',$leaveDetail->rank)
            ->select(
                'lc.id as leave_category_id',
                'lc.leave_type',
                'lc.color',
                'lc.carry_forward',
                'lc.carry_max',
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

        $lastYearStart = Carbon::now()->subYear()->startOfYear()->format('Y-m-d');
        $lastYearEnd = Carbon::now()->subYear()->endOfYear()->format('Y-m-d');

        // Combine leave balances and usage (carry forward when leave is eligible and not used by employee)
        $leaveBalances = $benefit_grids->map(function ($grid) use ($leaveUsage, $leaveDetail, $lastYearStart, $lastYearEnd) {
            $usageRow = $leaveUsage->get($grid->leave_category_id);
            $usedDays = $usageRow ? (int) $usageRow->used_days : 0;
            $grid->used_days = $usedDays;
            $available = (int) $grid->allocated_days - $usedDays;

            $carryForwardEnabled = !empty($grid->carry_forward) && $grid->carry_forward != '0';
            if ($carryForwardEnabled) {
                $lastYearUsed = DB::table('employees_leaves')
                    ->select(DB::raw('SUM(total_days) as used_days'))
                    ->where('emp_id', $leaveDetail->emp_id)
                    ->where('leave_category_id', $grid->leave_category_id)
                    ->where('status', 'Approved')
                    ->where(function ($query) use ($lastYearStart, $lastYearEnd) {
                        $query->whereBetween('from_date', [$lastYearStart, $lastYearEnd])
                            ->orWhereBetween('to_date', [$lastYearStart, $lastYearEnd]);
                    })
                    ->value('used_days') ?? 0;
                $unused = max((int) $grid->allocated_days - $lastYearUsed, 0);
                $carryMax = isset($grid->carry_max) && $grid->carry_max !== null && $grid->carry_max !== '' ? (int) $grid->carry_max : null;
                $carryForward = $carryMax !== null ? min($unused, $carryMax) : $unused;
                $available += $carryForward;
            }

            $grid->available_days = max(0, $available);
            return $grid;
        });

        // Can approve: (1) applicant's reporting_to, or (2) applicant is GM and current user is HR/EXCOM/HOD, or (3) current user has a Pending status row (their turn in the chain)
        $reportingToInt = (int)($leaveDetail->applicant_reporting_to ?? $leaveDetail->reporting_to ?? 0);
        $currentUserRank = trim((string)($employee->rank ?? ''));
        $applicantRankStr = trim((string)($leaveDetail->rank ?? ''));
        $isReportingManager = $reportingToInt > 0 && $reportingToInt === (int)$employee->id;
        $isGMLeaveApprover = ($applicantRankStr === '8') && in_array($currentUserRank, ['1', '2', '3'], true);
        $leaveIsPending = strtolower(trim($leaveDetail->status ?? '')) === 'pending';
        $currentUserHasPendingStatus = EmployeeLeaveStatus::where('leave_request_id', $decodedId)
            ->where('status', 'Pending')
            ->where('approver_id', $employee->id)
            ->exists();
        $canApproveThisLeave = (bool)(($isReportingManager || $isGMLeaveApprover || $currentUserHasPendingStatus) && $leaveIsPending);

        // Fetch departure pass (travel pass) linked to this leave, if any
        $departurePass = EmployeeTravelPass::where('leave_request_id', $decodedId)
            ->leftJoin('resort_transportations as rt', 'rt.id', '=', 'employee_travel_passes.transportation')
            ->select(
                'employee_travel_passes.*',
                'rt.transportation_option as transportation_label'
            )
            ->first();

        return view('resorts.leaves.leave.details', compact('page_title', 'empID', 'available_rank', 'leaveDetail', 'leaveBalances', 'leaveUsage', 'leave_categories', 'employee', 'canApproveThisLeave', 'departurePass'));
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
            'els.approver_rank as approver_rank',
            'els.approver_id as approver_id'
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

        $rank = config('settings.Position_Rank') ?? [];
        $approverIds = $leaveUsage->pluck('approver_id')->filter()->unique()->values()->all();
        $approverIdToLabel = [];
        if (!empty($approverIds)) {
            $approverRanks = Employee::whereIn('id', $approverIds)->pluck('rank', 'id');
            foreach ($approverRanks as $empId => $r) {
                $k = $r !== null && $r !== '' ? (string) $r : null;
                $approverIdToLabel[(int) $empId] = ($k !== null && is_array($rank) && array_key_exists($k, $rank)) ? $rank[$k] : 'designated approver';
            }
        }
        $leaveUsage = $leaveUsage->map(function($usage) use ($rank, $approverIdToLabel) {
            $fromDate = $usage->from_date ?? null;
            $toDate = $usage->to_date ?? null;
            $usage->from_date = $fromDate ? Carbon::parse($fromDate)->format('d M') : '—';
            $usage->to_date = $toDate ? Carbon::parse($toDate)->format('d M') : '—';

            $combinedLeave = EmployeeLeave::where('flag', $usage->id)
                ->join('leave_categories as lc', 'lc.id', '=', 'employees_leaves.leave_category_id')
                ->first();

            // Resolve approver label from approver_id (Employee.rank) so "Approved by HOD" is correct
            $approverIdInt = (int) ($usage->approver_id ?? 0);
            if ($approverIdInt && isset($approverIdToLabel[$approverIdInt])) {
                $role = $approverIdToLabel[$approverIdInt];
            } else {
                $approverRankKey = $usage->approver_rank !== null && $usage->approver_rank !== '' ? (string) $usage->approver_rank : null;
                $role = ($approverRankKey !== null && array_key_exists($approverRankKey, $rank)) ? $rank[$approverRankKey] : 'designated approver';
            }
            if ($combinedLeave && $combinedLeave->to_date) {
                $usage->to_date = Carbon::parse($combinedLeave->to_date)->format('d M');
            }
            $usage->status_text = $usage->last_status ? "{$usage->last_status} by {$role}" : 'Pending';
            $usage->total_days = ($combinedLeave && isset($combinedLeave->total_days)) ? ((int) $combinedLeave->total_days + (int) $usage->total_days) : (int) $usage->total_days;

            // Return only plain data for JSON (no Eloquent models) to avoid DataTables serialization issues
            return (object) [
                'id' => $usage->id,
                'leave_category' => $usage->leave_category ?? '—',
                'reason' => $usage->reason ?? '—',
                'from_date' => $usage->from_date,
                'to_date' => $usage->to_date,
                'total_days' => $usage->total_days,
                'attachments' => $usage->attachments ?? null,
                'status' => $usage->status ?? null,
                'status_text' => $usage->status_text,
            ];
        });

        return response()->json([
            'draw' => (int) $request->get('draw', 1),
            'recordsTotal' => $total_records,
            'recordsFiltered' => $total_records,
            'data' => $leaveUsage->values()->all(),
        ]);
    }

    /**
     * Return single leave detail (for history table "View" modal). Ensures leave belongs to given empID.
     */
    public function getLeaveHistoryDetail(Request $request)
    {
        try {
            $leaveId = (int) $request->get('leave_id');
            $empID = (int) $request->get('empID');
            if (!$leaveId || !$empID) {
                return response()->json(['success' => false, 'message' => 'Invalid request.'], 400);
            }
            $resort_id = $this->resort->resort_id ?? 0;
            if (!$resort_id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
            }

            $leave = DB::table('employees_leaves as el')
                ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
                ->leftJoin('employees as e', 'e.id', '=', 'el.emp_id')
                ->leftJoin('employees as report_to_emp', 'report_to_emp.id', '=', 'e.reporting_to')
                ->leftJoin('resort_admins as ra_report', 'ra_report.id', '=', 'report_to_emp.Admin_Parent_id')
                ->where('el.id', $leaveId)
                ->where('el.emp_id', $empID)
                ->where('el.resort_id', $resort_id)
                ->select(
                    'el.id',
                    'el.reason',
                    'el.from_date',
                    'el.to_date',
                    'el.total_days',
                    'el.attachments',
                    'el.status',
                    'el.destination',
                    'lc.leave_type as leave_category',
                    'lc.color',
                    DB::raw("CONCAT(COALESCE(ra_report.first_name,''), ' ', COALESCE(ra_report.last_name,'')) as reporting_to_name")
                )
                ->first();

            if (!$leave) {
                return response()->json(['success' => false, 'message' => 'Leave not found.'], 404);
            }

            $leave->from_date_formatted = $leave->from_date ? Carbon::parse($leave->from_date)->format('d M, Y') : '—';
            $leave->to_date_formatted = $leave->to_date ? Carbon::parse($leave->to_date)->format('d M, Y') : '—';
            $leave->status_label = $leave->status ?? 'Pending';

            $departurePass = DB::table('employee_travel_passes as etp')
                ->leftJoin('resort_transportations as rt', 'rt.id', '=', 'etp.transportation')
                ->where('etp.leave_request_id', $leaveId)
                ->select(
                    'etp.departure_date',
                    'etp.departure_time',
                    'etp.arrival_date',
                    'etp.arrival_time',
                    'etp.departure_reason',
                    'etp.arrival_reason',
                    'etp.status as pass_status',
                    'rt.transportation_option as transportation_label'
                )
                ->first();

            if ($departurePass) {
                $departurePass->departure_date_formatted = $departurePass->departure_date ? Carbon::parse($departurePass->departure_date)->format('d M, Y') : '—';
                $departurePass->arrival_date_formatted = $departurePass->arrival_date ? Carbon::parse($departurePass->arrival_date)->format('d M, Y') : '—';
                $departurePass->reason_text = $departurePass->departure_reason ?? $departurePass->arrival_reason ?? '—';
            }

            return response()->json([
                'success' => true,
                'leave' => $leave,
                'departure_pass' => $departurePass,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Leave history detail error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Failed to load leave details.'], 500);
        }
    }

    public function store(Request $request)
    {
        $resort_id = $this->resort->resort_id;
        $emp_id = $this->resort->GetEmployee->id;
        $rank = $this->resort->GetEmployee->rank;

        // Resolve validation rules from first leave category (Mandatory/Optional/Hidden)
        $categoryIds = $request->input('leave_category_id');
        $categoryId = is_array($categoryIds) ? ($categoryIds[0] ?? null) : null;
        $leaveTypeName = null;
        if ($categoryId) {
            $firstCategory = LeaveCategory::find($categoryId);
            $leaveTypeName = $firstCategory ? trim($firstCategory->leave_type) : null;
        }
        $rules = $this->getLeaveFormValidationRules($leaveTypeName);

        $validatorRules = [
            'leave_category_id' => 'required|array',
            'leave_category_id.*' => 'required|exists:leave_categories,id',
            'from_date' => 'required|array',
            'from_date.*' => 'required|date_format:d/m/Y',
            'to_date' => 'required|array',
            'to_date.*' => 'required|date_format:d/m/Y',
        ];
        if ($rules['reason'] === 'mandatory') {
            $validatorRules['reason'] = 'required|string|max:2000';
        } else {
            $validatorRules['reason'] = 'nullable|string|max:2000';
        }
        if ($rules['task_delegation'] === 'mandatory') {
            $validatorRules['task_delegation'] = 'required|exists:employees,id';
        } else {
            $validatorRules['task_delegation'] = 'nullable|exists:employees,id';
        }
        if ($rules['destination'] !== 'hidden') {
            $validatorRules['destination'] = 'nullable|string|max:255';
        }
        $validatorRules['attachments'] = ($rules['attachment'] === 'mandatory') ? 'required|file|mimes:pdf,doc,docx,jpeg,jpg,png|max:5120' : 'nullable|file|mimes:pdf,doc,docx,jpeg,jpg,png|max:5120';

        $validator = Validator::make($request->all(), $validatorRules);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
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
                $leaveDetails = LeaveCategory::where('id', $categoryId)->first();
                $currentFlag = null;
                
                // Only set flag for combined leaves when multiple leave types are being submitted
                if ($leaveDetails->combine_with_other == 1 && count($request->leave_category_id) > 1) {
                    // If this is the first leave in a combined request, set flag to the current leave ID
                    // Otherwise, set flag to the first leave's ID
                    if ($key == 0) {
                        $currentFlag = null; // Will be updated to this leave's ID after creation
                    } else {
                        // For subsequent leaves in combined request, link to the first leave
                        $currentFlag = $request->leave_category_id[0];
                    }
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

                // Check if the leave category ID is valid
                $checkLeaveOverlap = EmployeeLeave::where('emp_id', $emp_id)
                    ->where('resort_id', $resort_id)
                    ->whereIn('status', ['Pending', 'Approved']) 
                    ->where(function ($query) use ($fromDate, $toDate) {
                        $query->where(function ($q) use ($fromDate, $toDate) {
                            $q->whereDate('from_date', '<=', $toDate)
                            ->whereDate('to_date', '>=', $fromDate);
                        });
                    })
                    ->first(); 

                if ($checkLeaveOverlap) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You have already applied leave on this date. Please select a different date',
                    ]);
                }

                // Get the leave category details for the current category ID
                $leaveCategory = DB::table('leave_categories')->where('id', $categoryId)->first();
                if (!$leaveCategory) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Leave category with ID $categoryId does not exist.",
                    ]);
                }

                // If category has a "number of times" limit, enforce it per period (frequency)
                $numberOfTimesLimit = $leaveCategory->number_of_times ?? null;
                if ($numberOfTimesLimit !== null && (int) $numberOfTimesLimit > 0) {
                    $frequency = $leaveCategory->frequency ?? 'Yearly';
                    $now = Carbon::now();
                    switch ($frequency) {
                        case 'Weekly':
                            $periodStart = $now->copy()->startOfWeek()->format('Y-m-d');
                            $periodEnd = $now->copy()->endOfWeek()->format('Y-m-d');
                            break;
                        case 'Monthly':
                            $periodStart = $now->copy()->startOfMonth()->format('Y-m-d');
                            $periodEnd = $now->copy()->endOfMonth()->format('Y-m-d');
                            break;
                        case 'Quarterly':
                            $periodStart = $now->copy()->startOfQuarter()->format('Y-m-d');
                            $periodEnd = $now->copy()->endOfQuarter()->format('Y-m-d');
                            break;
                        default:
                            $periodStart = $now->copy()->startOfYear()->format('Y-m-d');
                            $periodEnd = $now->copy()->endOfYear()->format('Y-m-d');
                    }
                    $applicationsInPeriod = DB::table('employees_leaves')
                        ->where('emp_id', $emp_id)
                        ->where('leave_category_id', $categoryId)
                        ->whereIn('status', ['Pending', 'Approved'])
                        ->where(function ($q) use ($periodStart, $periodEnd) {
                            $q->whereBetween('from_date', [$periodStart, $periodEnd])
                                ->orWhereBetween('to_date', [$periodStart, $periodEnd])
                                ->orWhere(function ($q2) use ($periodStart, $periodEnd) {
                                    $q2->where('from_date', '<=', $periodStart)->where('to_date', '>=', $periodEnd);
                                });
                        })
                        ->count();
                    if ($applicationsInPeriod >= (int) $numberOfTimesLimit) {
                        return response()->json([
                            'status' => 'error',
                            'message' => "You have reached the maximum number of applications ({$numberOfTimesLimit}) for {$leaveCategory->leave_type} per {$frequency}. You cannot apply again in this period.",
                        ], 422);
                    }
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

                // Add carry forward when leave is eligible and not used by employee (unused from last year)
                $carryForwardEnabled = !empty($leaveCategory->carry_forward) && $leaveCategory->carry_forward != '0';
                if ($carryForwardEnabled) {
                    $lastYearStart = Carbon::now()->subYear()->startOfYear()->format('Y-m-d');
                    $lastYearEnd = Carbon::now()->subYear()->endOfYear()->format('Y-m-d');
                    $lastYearUsed = DB::table('employees_leaves')
                        ->select(DB::raw('SUM(total_days) as used_days'))
                        ->where('emp_id', $emp_id)
                        ->where('leave_category_id', $categoryId)
                        ->where('status', 'Approved')
                        ->where(function ($query) use ($lastYearStart, $lastYearEnd) {
                            $query->whereBetween('from_date', [$lastYearStart, $lastYearEnd])
                                ->orWhereBetween('to_date', [$lastYearStart, $lastYearEnd]);
                        })
                        ->value('used_days') ?? 0;
                    $unused = max($allocatedDays - $lastYearUsed, 0);
                    $carryMax = isset($leaveCategory->carry_max) && $leaveCategory->carry_max !== null && $leaveCategory->carry_max !== '' ? (int) $leaveCategory->carry_max : null;
                    $carryForward = $carryMax !== null ? min($unused, $carryMax) : $unused;
                    $availableDays += $carryForward;
                }

                // Check if the requested leave exceeds the available days for the category
                if ($totalDays > $availableDays) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "You cannot apply for more days than your remaining balance in the {$leaveCategory->leave_type} category! Available: $availableDays days.",
                    ]);
                }

                

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
                    foreach ($request->transportation as $key => $transportMode) {
                        // Ensure all fields exist for the current index
                        if (
                            isset($request->arrival_date[$transportMode], $request->departure_date[$transportMode],
                                $request->arrival_time[$transportMode], $request->departure_time[$transportMode])
                        ) {
                           

                            // Generate the travel pass
                            $leaveTransport = EmployeesLeaveTransportation::create([
                                'leave_request_id'      => $leave->id,
                                'transportation'        => $transportMode,
                                'trans_arrival_date'    => $request->arrival_date[$transportMode] ? \Carbon\Carbon::createFromFormat('d/m/Y', $request->arrival_date[$transportMode]) : null,
                                'trans_departure_date'  => $request->departure_date[$transportMode] ? \Carbon\Carbon::createFromFormat('d/m/Y', $request->departure_date[$transportMode]) : null,
                               
                                'arrival_time' => $request->arrival_time[$transportMode] ?: null,
                                'departure_time' => $request->departure_time[$transportMode] ?: null,
                               
                            ]);
                        }
                    }
                }

                $passapprovalFlow                       =   collect();

                $directReportingManagerId = $this->resort->GetEmployee->reporting_to;
                $directReportingManager = Employee::select('id', 'rank','reporting_to')->where('resort_id',$this->resort->resort_id)->find($directReportingManagerId); // Fetch only id and rank
               
                if ($directReportingManager && $directReportingManager->rank < "8") {
                    $passapprovalFlow->push($directReportingManager); // First approver: Supervisor/Manager

                    // Step 2: Find the HOD for this Supervisor/Manager
                    $hod                                =   Employee::select('id', 'rank', 'reporting_to')->where('resort_id',$this->resort->resort_id)->find($directReportingManager->reporting_to);
                    if ($hod && $hod->rank < "8") {
                        $passapprovalFlow->push($hod); // Second approver: HOD
                    }
                }

                // Add HR and higher ranks to the approval flow
                $hrApprover                             =   Employee::select('id', 'rank')->where('resort_id',$this->resort->resort_id)->where('rank', 3)->first(); // HR
                if ($hrApprover) {
                    $passapprovalFlow->push($hrApprover); // Third approver: HR
                }

                // Add Security Officer to the approval flow
                $SOApprover                             =   Employee::select('id', 'rank')->where('resort_id',$this->resort->resort_id)->where('rank', 10)->first(); // Security Officer
                if ($SOApprover) {
                    $passapprovalFlow->push($SOApprover); // Fourth approver: Security Officer
                }

                if (isset($request->arr_date, $request->dept_date)) {
                    $boardingPassReason = $request->boarding_pass_reason ?? $request->reason;
                    $boardingPass                       =   EmployeeTravelPass::create([
                        'resort_id'                     =>  $resort_id,
                        'employee_id'                   =>  $emp_id,
                        'leave_request_id'              =>  $leave->id,
                        'transportation'                =>  $request->arrival_transportation ?? $request->dept_transportation,  // Set transportation based on arrival or departure
                        'arrival_date'                  =>  $request->arr_date ? \Carbon\Carbon::createFromFormat('d/m/Y', $request->arr_date) : null,
                        'arrival_time'                  =>  $request->arr_time,
                        'departure_date'                =>  $request->dept_date ? \Carbon\Carbon::createFromFormat('d/m/Y', $request->dept_date) : null,
                        'departure_time'                =>  $request->dept_time,
                        'departure_reason'              =>  $boardingPassReason,
                        'arrival_reason'                =>  $boardingPassReason,
                        'status'                        =>  'Pending',
                    ]);
                }

                if (!empty($boardingPass)) {
                    foreach ($passapprovalFlow as $approverFlw) {
                        EmployeeTravelPassStatus::create([
                            'travel_pass_id'            =>  $boardingPass->id,
                            'approver_id'               =>  $approverFlw->id,
                            'approver_rank'             =>  $approverFlw->rank,
                            'status'                    =>  'Pending',
                        ]);
                    }
                }

                // Leave approval: only the applicant's reporting_to can approve (reporting_to is set in employee profile and can be changed there).
                // Use applicant's reporting_to at submit time to create the approval chain.
                $applicantEmployee = Employee::select('id', 'rank', 'reporting_to')->where('resort_id', $this->resort->resort_id)->find($emp_id);
                $directReportingManagerId = $applicantEmployee ? $applicantEmployee->reporting_to : null;
                $directReportingManager = $directReportingManagerId
                    ? Employee::select('id', 'rank', 'reporting_to')->where('resort_id', $this->resort->resort_id)->find($directReportingManagerId)
                    : null;

                $approvalFlow = collect(); // Store the approval flow dynamically


                $findSickLeaveCategory                  =   LeaveCategory::where('leave_type', 'LIKE', '%Sick%')
                                                                ->where('resort_id',$resort_id)
                                                                ->first();

                $leaveCount                             =   EmployeeLeave::where('emp_id', $emp_id)
                                                                ->where('leave_category_id', $findSickLeaveCategory->id)
                                                                ->where('total_days', '1')
                                                                ->whereYear('from_date', Carbon::now()->year) 
                                                                ->count();

                // Get the clinic staff if the leave type is sick
                $getClinicStaff                         =   Common::findClinicStaff($resort_id);

                if (stripos($leaveDetails->leave_type, 'sick') !== false && $totalDays > 2) {
                    if ($getClinicStaff) {
                        $approvalFlow->push($getClinicStaff); // Clinic staff approves
                    }
                }

                if($leaveCount > 15) {
                    if ($getClinicStaff) {
                        $approvalFlow->push($getClinicStaff); // Clinic staff approves
                    }
                }

                // Leave approval: any employee's leave is approved by their Reporting To (from Employee Details page).
                // All ranks including GM: add the applicant's reporting manager first; for GM only, also add HR/EXCOM/HOD as optional additional approvers.
                if ($directReportingManager && $directReportingManager->rank) {
                    $approvalFlow->push($directReportingManager);
                }
                if ($rank === "8") {
                    // GM: after reporting manager, also add HR, EXCOM (or HOD if no EXCOM) so any of them can approve if needed
                    $approvalIds = $approvalFlow->pluck('id')->toArray();
                    $hrApprover = Employee::select('id', 'rank', 'reporting_to')->where('resort_id', $this->resort->resort_id)->where('rank', 3)->first();
                    if ($hrApprover && !in_array($hrApprover->id, $approvalIds)) {
                        $approvalFlow->push($hrApprover);
                        $approvalIds[] = $hrApprover->id;
                    }
                    $excomApprover = Employee::select('id', 'rank', 'reporting_to')->where('resort_id', $this->resort->resort_id)->where('rank', 1)->first();
                    if ($excomApprover && !in_array($excomApprover->id, $approvalIds)) {
                        $approvalFlow->push($excomApprover);
                        $approvalIds[] = $excomApprover->id;
                    } else {
                        $hodApprover = Employee::select('id', 'rank', 'reporting_to')->where('resort_id', $this->resort->resort_id)->where('rank', 2)->first();
                        if ($hodApprover && !in_array($hodApprover->id, $approvalIds)) {
                            $approvalFlow->push($hodApprover);
                        }
                    }
                }

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

        // Check for any relation: leave_category can be comma-separated IDs (e.g. "1,3,5"); at least one selected category must list another selected ID in its leave_category
        $categoryId = array_map('intval', (array) $categoryId);
        $hasRelation = $leaveCategories->contains(function ($category) use ($categoryId) {
            $allowed = array_filter(array_map('trim', explode(',', $category->leave_category ?? '')));
            $allowed = array_map('intval', $allowed);
            $others = array_diff($categoryId, [(int) $category->id]);
            return count(array_intersect($others, $allowed)) > 0;
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
        $employeeRankPosition = Common::getEmployeeRankPosition($this->resort->getEmployee);
        $isGM = ($employeeRankPosition['position'] ?? '') === 'GM' || ($employeeRankPosition['rank'] ?? '') === 'GM';
        $userDeptId = $this->resort->getEmployee->Dept_id ?? null;
        $hrDeptId = ResortDepartment::where('resort_id', $resort_id)->where('name', 'Human Resources')->value('id');
        $isFromHRDepartment = $hrDeptId && $userDeptId && (int) $userDeptId === (int) $hrDeptId;
        $canViewWholeResort = $isFromHRDepartment;

        $isMGR = ($available_rank === "MGR");

        $date = $filter === 'Tomorrow' ? Carbon::tomorrow() : Carbon::today();

        $employeesOnLeaveQuery = DB::table('employees_leaves as el')
            ->join('employees as e', 'e.id', '=', 'el.emp_id')
            ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
            ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
            ->join('resort_departments as rd', 'rd.id', '=', 'e.Dept_id')
            ->where('el.resort_id', $resort_id)
            ->where('el.status', "Approved")
            ->whereDate('el.from_date', '<=', $date)
            ->whereDate('el.to_date', '>=', $date);

        if (!$canViewWholeResort) {
            if ($userDeptId) {
                $employeesOnLeaveQuery->where('e.Dept_id', $userDeptId);
            } elseif ($isMGR) {
                $employeesOnLeaveQuery->where('e.reporting_to', $this->reporting_to);
            }
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
            'e.Admin_Parent_id',
            'rp.position_title as position',
            'rd.name as department'
        )->paginate(10);

        // Update profile pictures dynamically
        $employeesOnLeave->getCollection()->transform(function ($employee) {
            $employee->profile_picture = Common::getResortUserPicture($employee->Admin_Parent_id);
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
        $loggedInEmployee = $this->resort->getEmployee;
        $loggedInEmployeeId = $loggedInEmployee->id ?? null;
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
        $employeeRankPosition = Common::getEmployeeRankPosition($this->resort->getEmployee);
        $isGM = ($employeeRankPosition['position'] ?? '') === 'GM' || ($employeeRankPosition['rank'] ?? '') === 'GM';
        $userDeptId = $this->resort->getEmployee->Dept_id ?? null;
        $hrDeptId = ResortDepartment::where('resort_id', $resort_id)->where('name', 'Human Resources')->value('id');
        $isFromHRDepartment = $hrDeptId && $userDeptId && (int) $userDeptId === (int) $hrDeptId;
        $canViewWholeResort = $isFromHRDepartment;

        $isMGR = ($available_rank === "MGR");

        $employeesOnLeaveQuery = DB::table('employees_leaves as el')
            ->join('employees as e', 'e.id', '=', 'el.emp_id')
            ->join('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
            ->join('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
            ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->where('el.resort_id', $resort_id)
            ->where('el.status', "Approved")
            ->whereDate('el.from_date', '<=', $endDate)
            ->whereDate('el.to_date', '>=', $startDate);

        if (!$canViewWholeResort) {
            if ($userDeptId) {
                $employeesOnLeaveQuery->where('e.Dept_id', $userDeptId);
            } elseif ($isMGR) {
                $employeesOnLeaveQuery->where('e.reporting_to', $this->reporting_to);
            }
        }
        $employeesOnLeave = $employeesOnLeaveQuery->select(
                'ra.first_name',
                'ra.last_name',
                'rp.position_title as position',
                'lc.leave_type',
                'lc.color as color',
                'el.from_date',
                'el.to_date',
                'ra.id as adminID',
                DB::raw("DATEDIFF(el.to_date, el.from_date) + 1 as total_days")
            )
            ->paginate(10);



        // Format the response
        $data = $employeesOnLeave->map(function ($employee) {
            $employee->profile_picture = Common::getResortUserPicture($employee->adminID); // Modify as needed
            $employee->leave_dates = Carbon::parse($employee->from_date)->format('d M') . ' - ' . Carbon::parse($employee->to_date)->format('d M');
            return $employee;
        });

        return response()->json(['data' => $data]);
    }

    public function downloadPdf(Request $request, $empID)
    {
        @set_time_limit(120);

        $page_title = "Leave History";
        $decodedId = base64_decode($empID, true);
        $empIdInt = $decodedId !== false && is_numeric($decodedId) ? (int) $decodedId : 0;
        $resort_id = $this->resort->resort_id ?? 0;

        if (!$empIdInt || !$resort_id) {
            return redirect()->back()->with('error', 'Invalid request.');
        }

        // Ensure employee belongs to current resort
        $employee = DB::table('employees as e')
            ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->leftJoin('resort_positions as rp', 'rp.id', '=', 'e.Position_id')
            ->where('e.id', $empIdInt)
            ->where('e.resort_id', $resort_id)
            ->select(
                'e.id',
                'e.Emp_id as Emp_Code',
                'e.Admin_Parent_id',
                'e.rank',
                'e.resort_id',
                'ra.first_name',
                'ra.last_name',
                'ra.profile_picture',
                'rp.position_title'
            )
            ->first();

        if (!$employee) {
            return redirect()->back()->with('error', 'Employee not found.');
        }

        // Fetch leave data for this employee in this resort (main leaves only, same as history table)
        $leaveUsage = DB::table('employees_leaves as el')
            ->join('leave_categories', 'el.leave_category_id', '=', 'leave_categories.id')
            ->where('el.emp_id', $empIdInt)
            ->where('el.resort_id', $resort_id)
            ->whereNull('el.flag')
            ->select(
                'el.*',
                'leave_categories.leave_type as leave_category',
                DB::raw('DATE_FORMAT(el.from_date, "%Y-%m-%d") as from_date'),
                DB::raw('DATE_FORMAT(el.to_date, "%Y-%m-%d") as to_date')
            )
            ->orderBy('el.from_date', 'desc')
            ->get();

        // Employee header for PDF. Leave profile_picture empty to avoid DomPDF fetching remote/S3 URLs (prevents timeout).
        $employeeHeader = (object) [
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'Emp_Code' => $employee->Emp_Code,
            'position_title' => $employee->position_title ?? '—',
            'profile_picture' => '',
        ];
        if ($leaveUsage->isNotEmpty()) {
            $first = $leaveUsage->first();
            $first->first_name = $employeeHeader->first_name;
            $first->last_name = $employeeHeader->last_name;
            $first->Emp_Code = $employeeHeader->Emp_Code;
            $first->position_title = $employeeHeader->position_title;
            $first->profile_picture = $employeeHeader->profile_picture;
        }

        $emp_grade = Common::getEmpGrade($employee->rank);

        $benefit_grids = DB::table('resort_benifit_grid as rbg')
            ->join('resort_benefit_grid_child as rbgc', 'rbg.id', '=', 'rbgc.benefit_grid_id')
            ->join('leave_categories as lc', 'lc.id', '=', 'rbgc.leave_cat_id')
            ->where('rbg.emp_grade', $emp_grade)
            ->where('rbgc.rank', $employee->rank)
            ->where('rbg.resort_id', $resort_id)
            ->select(
                'lc.id as leave_category_id',
                'lc.leave_type',
                'lc.color',
                'lc.carry_forward',
                'lc.carry_max',
                'rbgc.allocated_days'
            )
            ->get();

        $lastYearStartPdf = Carbon::now()->subYear()->startOfYear()->format('Y-m-d');
        $lastYearEndPdf = Carbon::now()->subYear()->endOfYear()->format('Y-m-d');

        // Used days per leave category: only approved leaves deduct from balance
        $currentYearStart = Carbon::now()->startOfYear()->format('Y-m-d');
        $currentYearEnd = Carbon::now()->endOfYear()->format('Y-m-d');
        $usedPerCategory = DB::table('employees_leaves')
            ->where('emp_id', $empIdInt)
            ->where('resort_id', $resort_id)
            ->whereNull('flag')
            ->where('status', 'Approved')
            ->where(function ($q) use ($currentYearStart, $currentYearEnd) {
                $q->whereBetween('from_date', [$currentYearStart, $currentYearEnd])
                    ->orWhereBetween('to_date', [$currentYearStart, $currentYearEnd]);
            })
            ->selectRaw('leave_category_id, COALESCE(SUM(total_days), 0) as used_days')
            ->groupBy('leave_category_id')
            ->pluck('used_days', 'leave_category_id');

        $leaveBalances = $benefit_grids->map(function ($grid) use ($usedPerCategory, $empIdInt, $lastYearStartPdf, $lastYearEndPdf) {
            $usedDays = (int) ($usedPerCategory->get($grid->leave_category_id) ?? 0);
            $grid->used_days = $usedDays;
            $available = (int) $grid->allocated_days - $usedDays;
            $carryForwardEnabled = !empty($grid->carry_forward) && $grid->carry_forward != '0';
            if ($carryForwardEnabled) {
                $lastYearUsed = DB::table('employees_leaves')
                    ->select(DB::raw('SUM(total_days) as used_days'))
                    ->where('emp_id', $empIdInt)
                    ->where('leave_category_id', $grid->leave_category_id)
                    ->where('status', 'Approved')
                    ->where(function ($query) use ($lastYearStartPdf, $lastYearEndPdf) {
                        $query->whereBetween('from_date', [$lastYearStartPdf, $lastYearEndPdf])
                            ->orWhereBetween('to_date', [$lastYearStartPdf, $lastYearEndPdf]);
                    })
                    ->value('used_days') ?? 0;
                $unused = max((int) $grid->allocated_days - $lastYearUsed, 0);
                $carryMax = isset($grid->carry_max) && $grid->carry_max !== null && $grid->carry_max !== '' ? (int) $grid->carry_max : null;
                $carryForward = $carryMax !== null ? min($unused, $carryMax) : $unused;
                $available += $carryForward;
            }
            $grid->available_days = max(0, $available);
            return $grid;
        });

        $sitesettings = ResortSiteSettings::where('resort_id', $resort_id)->first(['resort_id', 'header_img', 'footer_img', 'Footer']);
        $ResortData = Resort::find($resort_id);

        $pdf = Pdf::loadView('resorts.leaves.leave.pdf', [
            'sitesettings' => $sitesettings,
            'resort_id' => $resort_id,
            'ResortData' => $ResortData,
            'employeeHeader' => $employeeHeader,
            'leaveUsage' => $leaveUsage,
            'leaveBalances' => $leaveBalances,
            'page_title' => $page_title,
        ]);
        $pdf->setPaper('a4', 'landscape');

        $fileName = 'leave-history-' . ($employee->Emp_Code ?? $empIdInt) . '.pdf';
        return $pdf->download($fileName);
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

        // Check if there is any Pending status for this leave
        $pendingStatuses = EmployeeLeaveStatus::where('leave_request_id', $leaveId)
            ->where('status', 'Pending')
            ->get();

        if ($pendingStatuses->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'This leave request is already ' . $leave->status . '. ',
            ], 200);
        }

        // Leave approval: only the applicant's reporting_to can approve. reporting_to is from employee profile (can be changed in profile settings).
        // We use the applicant's current reporting_to from the Employee model so if it was changed after leave was applied, the new reporting manager can approve.
        $rankConfig = config('settings.Position_Rank');
        $applicant = Employee::find($leave->emp_id);
        $applicantReportingToStr = trim((string)($applicant->reporting_to ?? ''));
        $currentApproverIdStr = trim((string)$currentApproverId);
        $applicantRankStr = trim((string)($applicant->rank ?? ''));
        $currentUserRankNum = trim((string)($this->resort->GetEmployee->rank ?? ''));
        $currentApproverIdInt = (int) $currentApproverId;

        $currentUserPendingRow = $pendingStatuses->first(function ($row) use ($currentApproverIdInt) {
            return (int) $row->approver_id === $currentApproverIdInt;
        });

        // Is current user the applicant's reporting_to? (from employee profile – same source as profile settings)
        $isReportingManager = $applicant && $applicantReportingToStr !== '' && (int) $applicantReportingToStr === $currentApproverIdInt;
        // GM leave approvers: rank 1 (EXCOM), 2 (HOD), 3 (HR) or role by position/department (e.g. HR dept)
        $currentUserEmployee = $this->resort->GetEmployee ?? $this->resort->getEmployee ?? null;
        $rankPosition = $currentUserEmployee ? Common::getEmployeeRankPosition($currentUserEmployee) : ['rank' => null, 'position' => null];
        $currentUserRankLabel = $rankPosition['rank'] ?? '';
        $currentUserPositionLabel = $rankPosition['position'] ?? '';
        $isHRExcomHodByRank = in_array($currentUserRankNum, ['1', '2', '3'], true);
        $isHRExcomHodByRole = in_array(strtoupper(trim($currentUserRankLabel)), ['EXCOM', 'HOD', 'HR'], true) || strtoupper(trim($currentUserPositionLabel ?? '')) === 'HR';
        $isGMLeaveApprover = ($applicantRankStr === '8') && ($isHRExcomHodByRank || $isHRExcomHodByRole);

        // GM flow: allow if (1) you are the applicant's reporting manager, OR (2) you have a Pending row AND you are HR/EXCOM/HOD
        if ($applicantRankStr === '8') {
            $canApproveGM = $isReportingManager || ($currentUserPendingRow && $isGMLeaveApprover);
            if (!$canApproveGM) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only the reporting manager or HR, EXCOM or HOD can approve or reject GM leave. You can view it only.',
                ], 403);
            }
        } else {
            // Non-GM flow (same idea as GM): allow if you have a Pending row (you're in the chain) OR you are the applicant's reporting_to (designated approver)
            if (!$currentUserPendingRow && !$isReportingManager) {
                $firstPending = $pendingStatuses->sortBy('id')->first();
                $approverRankKey = $firstPending && $firstPending->approver_rank !== null && $firstPending->approver_rank !== ''
                    ? (string) $firstPending->approver_rank : null;
                $lastApproverRank = ($approverRankKey !== null && array_key_exists($approverRankKey, $rankConfig))
                    ? $rankConfig[$approverRankKey] : 'designated approver';
                $actionname = ($action == "Rejected") ? "reject" : "approve";
                return response()->json([
                    'status' => 'error',
                    'message' => "You cannot $actionname this request. The request must first be approved by the $lastApproverRank.",
                ], 403);
            }
            if (!$applicant) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Leave applicant not found.',
                ], 403);
            }
        }

        // Update: same as GM – update the row where current user is the approver; if no row (reporting manager fallback) update first Pending row.
        // Always set approver_rank to current user's rank so "Approved by HOD/GM/HR" displays correctly.
        $currentUserRank = $this->resort->GetEmployee->rank ?? null;
        $updateData = [
            'status' => $action,
            'comments' => $comments,
            'approved_at' => now(),
            'approver_id' => $currentApproverId,
            'approver_rank' => $currentUserRank,
        ];
        if ($currentUserPendingRow) {
            EmployeeLeaveStatus::where('id', $currentUserPendingRow->id)->update($updateData);
        } else {
            // Reporting manager but no Pending row with their id (e.g. stale chain): update first Pending row and record who approved
            $firstPending = $pendingStatuses->sortBy('id')->first();
            if ($firstPending) {
                EmployeeLeaveStatus::where('id', $firstPending->id)->update($updateData);
            }
        }

        if ($action == 'Approved') {
            // GM leave: any one approval (HR/EXCOM/HOD) is enough. Others: approve when no Pending left.
            $isGMLeave = $applicant && trim((string)($applicant->rank ?? '')) === '8';
            $pendingCount = EmployeeLeaveStatus::where('leave_request_id', $leave->id)->where('status', 'Pending')->count();
            if ($isGMLeave || $pendingCount === 0) {
                $leave->status = 'Approved';
                $leave->save();
            }
            return response()->json([
                'status' => 'success',
                'message' => 'Leave approved successfully.',
            ], 200);
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

    /**
     * Get leave form field rules (mandatory, optional, hidden) for a leave type.
     * Uses config('settings.leave_form_validation') keyed by leave type name (lowercase).
     *
     * @param string|null $leaveTypeName
     * @return array
     */
    protected function getLeaveFormValidationRules($leaveTypeName)
    {
        $default = [
            'reason' => 'mandatory',
            'task_delegation' => 'optional',
            'destination' => 'optional',
            'transportation' => 'optional',
            'departure_pass' => 'optional',
            'attachment' => 'optional',
        ];
        if (empty($leaveTypeName)) {
            return $default;
        }
        $key = strtolower(trim($leaveTypeName));
        $config = config('settings.leave_form_validation', []);
        if (isset($config[$key])) {
            return array_merge($default, $config[$key]);
        }
        // Partial match (e.g. "Sick Leave" -> "sick leave")
        foreach ($config as $configKey => $rules) {
            if (stripos($key, $configKey) !== false || stripos($configKey, $key) !== false) {
                return array_merge($default, $rules);
            }
        }
        return $default;
    }

    public function recommendAlternativeDate(Request $request)
    {
        $leaveId = $request->input('leave_id');
        $altStartDate = \Carbon\Carbon::createFromFormat('m/d/Y', $request->input('alt_start_date'));
        $altEndDate = \Carbon\Carbon::createFromFormat('m/d/Y', $request->input('alt_end_date'));
        $comments = $request->input('comments');

        $leave = EmployeeLeave::find($leaveId);

        if (!$leave) {
            return response()->json([
                'status' => 'error',
                'message' => 'Leave request not found.',
            ], 404);
        }

        // Log the recommendation in a table (e.g., LeaveRecommendations)
        $leaveRecommend = LeaveRecommendation::create([
            'leave_id' => $leaveId,
            'recommended_by' => $this->resort->getEmployee->id, // Current approver
            'alt_start_date' => $altStartDate,
            'alt_end_date' => $altEndDate,
            'comments' => $comments,
        ]);
        // dd($this->resort);
        $from = $this->resort->first_name." ".$this->resort->last_name;
        $recipient = Employee::with('resortAdmin')->where('id',$leave->emp_id)->first();

        $leave->sendAlternateDateSuggessionNotification($leaveRecommend,$recipient,$leave,$from);

        return response()->json([
            'status' => 'success',
            'message' => 'Alternative dates suggested successfully.',
        ]);
    }

    public function sendEmailToTravelPartner(Request $request)
    {
        $travel_partners = TicketAgent::where('resort_id', $this->resort->resort_id)->get();

        if ($travel_partners->isEmpty()) {
            return redirect()->back()->with('error', 'No travel partners found for this resort.');
        }

        $leaveId = $request['leaveId'];
        $leave = EmployeeLeave::join('employees as e', 'e.id', '=', 'employees_leaves.emp_id')
            ->join('employees_leave_transportation as elt','elt.leave_request_id' ,'=','employees_leaves.id')
            ->join('resort_admins as ra','ra.id','=','e.Admin_Parent_id')
            ->join('resort_positions as rp','rp.id','=','e.Position_id')
            ->where('employees_leaves.id', $leaveId)
            ->select('employees_leaves.*', 'ra.first_name as admin_first_name', 'ra.last_name as admin_last_name', 'rp.position_title','e.Emp_id','elt.transportation as transportation_mode','elt.trans_arrival_date','elt.trans_departure_date')
            ->first();

        if (!$leave) {
            return redirect()->back()->with('error', 'Leave record not found.');
        }

        $sender = $this->resort->first_name . " " . $this->resort->last_name;

        foreach ($travel_partners as $partner) {
            $partner->sendEmailToTravelPartner($partner, $leave, $sender);
        }

        // return redirect()->back()->with('success', 'Emails sent to travel partners successfully.');
    }

}
