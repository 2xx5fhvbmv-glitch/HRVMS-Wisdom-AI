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
use App\Models\PublicHoliday;
use App\Models\ParentAttendace;
use App\Models\ResortPosition;
use App\Models\LeaveRecommendation;
use App\Models\ResortTransportation;
use App\Models\ResortBenifitGridChild;
use App\Models\EmployeeTravelPassStatus;
use App\Models\EmployeesLeaveTransportation;
use App\Models\ResortNotification;
use App\Events\ResortNotificationEvent;
use App\Notifications\AlternativeDateSuggestedNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;

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
            $emp_grade = Common::getEmpGrade($rank);
            $excludedLeaveTypes = ['Absent', 'Present','DayOff'];

            $benefit_grid = Common::getBenefitGrid($emp_grade,$resort_id);

            // Check if benefit grid exists and provide fallback values
            $benefit_grid_emp_grade = $benefit_grid->emp_grade ?? $emp_grade;
            $benefit_grid_id = $benefit_grid->id ?? null;

            // Get the logged-in employee's gender
            $empGender = $this->resort->gender ?? '';

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
            ->whereRaw('FIND_IN_SET(?, lc.eligibility)', [$rank])
            ->where('resort_benefit_grid_child.allocated_days', '>', 0)
            ->where(function ($query) use ($religion, $empGender) {
                $query->where('resort_benefit_grid_child.eligible_emp_type', $empGender)
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
            ->where('employees.id', '!=', $emp_id) // Exclude self
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
            ->whereRaw('FIND_IN_SET(?, lc.eligibility)', [$rank])
            ->where('resort_benefit_grid_child.allocated_days', '>', 0)
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

            $delegations = DB::table('employees')
            ->join('resort_admins', 'employees.Admin_Parent_id', '=', 'resort_admins.id')
            ->where('employees.resort_id', $resort_id)
            ->where('employees.id', '!=', $emp_id) // Exclude self
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
        $airports = config('airports', ['national' => [], 'international' => []]);
        // Public holidays are excluded from the calendar-day count when an
        // employee picks a leave date range. Friday is the resort weekly off
        // (same convention as duty-roster + Common::getWeekCountInMonth).
        // Pass YYYY-MM-DD strings for current + next year to the view; the
        // JS uses them to subtract from calculateTotalDays().
        // public_holidays.holiday_date is a STRING column stored as d-m-Y
        // (e.g. "01-05-2026"), so whereYear() can't be used on it (the
        // earlier version returned zero rows because of that, which is
        // why only Fridays were excluded). Pull every active row and
        // normalise in PHP using createFromFormat with explicit fallbacks.
        $holidayDates = PublicHoliday::where('status', 'active')
            ->pluck('holiday_date')
            ->map(function ($d) {
                $d = trim((string) $d);
                if ($d === '') return null;
                foreach (['d-m-Y', 'd/m/Y', 'Y-m-d', 'Y/m/d', 'd-m-y', 'd/m/y'] as $fmt) {
                    try {
                        $c = \Carbon\Carbon::createFromFormat($fmt, $d);
                        if ($c) return $c->format('Y-m-d');
                    } catch (\Exception $e) {
                        // try next
                    }
                }
                // Last resort — Carbon::parse for ISO-ish strings.
                try { return \Carbon\Carbon::parse($d)->format('Y-m-d'); }
                catch (\Exception $e) { return null; }
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
        return view('resorts.leaves.leave.index', compact('page_title', 'emp_id','leave_categories', 'delegations', 'transportations', 'leaveFormValidation', 'airports', 'holidayDates'));
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

            // Per-employee scope — used when the Employee Detail page
            // "Leave" tab forwards ?empId=<base64>. Layered AFTER the
            // role-based scope so a restricted user can never see leaves
            // they wouldn't otherwise have access to (the filter only
            // narrows, never widens).
            if (request()->filled('empId')) {
                $decoded = base64_decode((string) request()->empId, true);
                if ($decoded !== false && ctype_digit((string) $decoded)) {
                    $leave_requests_query->where('el.emp_id', (int) $decoded);
                }
            }

                $leaveRequests = $leave_requests_query->select(
                    'el.*',
                    'e.Emp_id as employee_id',
                    'e.rank',
                    'e.Admin_Parent_id',
                    'e.reporting_to as reporting_to',
                    'e.joining_date',
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
                // Fetch all leave statuses for status_text computation
                $allLeaveIds = $leaveRequests->getCollection()->pluck('id')->toArray();
                $allStatuses = collect();
                if (!empty($allLeaveIds)) {
                    $allStatuses = DB::table('employees_leaves_status')
                        ->whereIn('leave_request_id', $allLeaveIds)
                        ->get()
                        ->groupBy('leave_request_id');
                }
                $rankConfig = config('settings.Position_Rank');

                $leaveRequests->getCollection()->transform(function ($leaveRequest) use ($resort_id, $loggedInEmployeeId, $currentUserRank, $leaveIdsWithCurrentUserPending, $allStatuses, $rankConfig) {
                    $leaveRequest->profile_picture = Common::getResortUserPicture($leaveRequest->Admin_Parent_id);

                    // Compute status_text and status_class
                    $statuses = $allStatuses[$leaveRequest->id] ?? collect();
                    if ($leaveRequest->leave_status === 'Approved') {
                        $leaveRequest->status_text = "Approved";
                        $leaveRequest->status_class = "badge-themeSuccess";
                    } elseif ($leaveRequest->leave_status === 'Rejected') {
                        $rejected = $statuses->firstWhere('status', 'Rejected');
                        if ($rejected) {
                            $rejectedBy = $rankConfig[$rejected->approver_rank] ?? $rejected->approver_rank;
                            $leaveRequest->status_text = "Rejected by {$rejectedBy}";
                        } else {
                            $leaveRequest->status_text = "Rejected";
                        }
                        $leaveRequest->status_class = "badge-themeDanger";
                    } else {
                        $lastApproved = $statuses->where('status', 'Approved')->last();
                        if ($lastApproved) {
                            $approvedBy = $rankConfig[$lastApproved->approver_rank] ?? $lastApproved->approver_rank;
                            $leaveRequest->status_text = "Pending - {$approvedBy} Approved";
                        } else {
                            $leaveRequest->status_text = "Pending";
                        }
                        $leaveRequest->status_class = "badge-themeWarning";
                    }

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
                    // Prorate allocation based on joining date
                    $leaveAllocation = Common::prorateLeaveByJoiningDate($leaveAllocation, $leaveRequest->joining_date ?? null);
                    $leaveRequest->available_balance = $leaveAllocation - $leavesTaken;

                    // Can approve: only if current user has a Pending row in the approval chain
                    $statusVal = $leaveRequest->status ?? $leaveRequest->leave_status ?? '';
                    $leaveIsPending = strtolower(trim((string)$statusVal)) === 'pending';
                    $hasPendingRow = \App\Models\EmployeeLeaveStatus::where('leave_request_id', $leaveRequest->id)
                        ->where('approver_id', $loggedInEmployeeId)
                        ->where('status', 'Pending')
                        ->exists();
                    $leaveRequest->can_approve = (bool)($hasPendingRow && $leaveIsPending);

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

                // can_approve: only if current user has a Pending row in the approval chain
                $finalLeaveIds = $finalLeaveRequests->pluck('id')->toArray();
                $finalLeaveIdsWithPending = $finalLeaveIds ? EmployeeLeaveStatus::whereIn('leave_request_id', $finalLeaveIds)->where('approver_id', $loggedInEmployeeId)->where('status', 'Pending')->pluck('leave_request_id')->toArray() : [];
                $finalLeaveRequests->each(function ($request) use ($loggedInEmployeeId, $finalLeaveIdsWithPending) {
                    $hasPendingRow = in_array($request->id, $finalLeaveIdsWithPending);
                    $statusVal = $request->status ?? $request->leave_status ?? '';
                    $leaveIsPending = strtolower(trim((string)$statusVal)) === 'pending';
                    $request->can_approve = (bool)($hasPendingRow && $leaveIsPending);
                });

                $show_department_filter = $canViewWholeResort;
                $filter_year = $filterYear;
                $filter_years = range(Carbon::now()->year - 2, Carbon::now()->year + 1);

                // Pre-load positions for filter
                if ($canViewWholeResort) {
                    $ResortPositions = ResortPosition::where('resort_id', $resort_id)->get();
                } else {
                    $ResortPositions = ResortPosition::where('resort_id', $resort_id)
                        ->where('dept_id', $hodDeptId)->get();
                }

                return view('resorts.leaves.leave.request', compact('finalLeaveRequests', 'page_title', 'resort_departments', 'hodDeptId', 'show_department_filter', 'filter_year', 'filter_years', 'ResortPositions'));
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
                    'e.joining_date',
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

            // Fetch all leave statuses for status_text computation
            $allLeaveIds = $leaveRequests->getCollection()->pluck('id')->toArray();
            $allStatuses = collect();
            if (!empty($allLeaveIds)) {
                $allStatuses = DB::table('employees_leaves_status')
                    ->whereIn('leave_request_id', $allLeaveIds)
                    ->get()
                    ->groupBy('leave_request_id');
            }
            $rankConfig = config('settings.Position_Rank');

            $leaveRequests->getCollection()->transform(function ($leaveRequest) use ($resort_id, $allStatuses, $rankConfig) {
                // dd($leaveRequest);
                $leaveRequest->profile_picture = Common::getResortUserPicture($leaveRequest->Admin_Parent_id);

                // Compute status_text and status_class
                $statuses = $allStatuses[$leaveRequest->id] ?? collect();
                if ($leaveRequest->leave_status === 'Approved') {
                    $leaveRequest->status_text = "Approved";
                    $leaveRequest->status_class = "badge-themeSuccess";
                } elseif ($leaveRequest->leave_status === 'Rejected') {
                    $rejected = $statuses->firstWhere('status', 'Rejected');
                    if ($rejected) {
                        $rejectedBy = $rankConfig[$rejected->approver_rank] ?? $rejected->approver_rank;
                        $leaveRequest->status_text = "Rejected by {$rejectedBy}";
                    } else {
                        $leaveRequest->status_text = "Rejected";
                    }
                    $leaveRequest->status_class = "badge-themeDanger";
                } else {
                    $lastApproved = $statuses->where('status', 'Approved')->last();
                    if ($lastApproved) {
                        $approvedBy = $rankConfig[$lastApproved->approver_rank] ?? $lastApproved->approver_rank;
                        $leaveRequest->status_text = "Pending - {$approvedBy} Approved";
                    } else {
                        $leaveRequest->status_text = "Pending";
                    }
                    $leaveRequest->status_class = "badge-themeWarning";
                }

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
                // Prorate allocation based on joining date
                $leaveAllocation = Common::prorateLeaveByJoiningDate($leaveAllocation, $leaveRequest->joining_date ?? null);
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

    /**
     * Per-employee Leave page entry point used by the Employee Detail
     * sidebar "Leave" tab. Routes the user to the leave-request list
     * (resorts.leaves.leave.request) scoped to the target employee via
     * ?empId=<base64> so HR sees ONLY that employee's leave requests —
     * not the global list. The request() method honors the same ?empId
     * filter, so an employee with no leaves yet still lands on a scoped
     * (empty) view instead of the global inbox.
     */
    public function employeeLeavePage($empID)
    {
        if(Common::checkRouteWisePermission('leave.request',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }
        $decoded = base64_decode((string) $empID, true);
        $empIdInt = ($decoded !== false && ctype_digit((string) $decoded)) ? (int) $decoded : 0;
        if (!$empIdInt) {
            return redirect()->back()->with('error', 'Invalid employee.');
        }

        // Per the docstring above: resolve this employee's MOST RECENT leave
        // record and land on leave.details for it so the user sees the rich
        // history view, not the global leave inbox.
        //
        // Previously this method redirected to leave.request?empId=<encoded>,
        // which dropped the user onto the global Leave Requests list filtered
        // (loosely) by employee — confusing UX, since "see this employee's
        // leave" should open THIS employee's leave page, not a filtered list.
        $latestLeave = EmployeeLeave::where('emp_id', $empIdInt)
            ->where('resort_id', $this->resort->resort_id)
            ->latest('id')
            ->select('id')
            ->first();

        if ($latestLeave) {
            return redirect()->route('leave.details', [
                'leave_id' => base64_encode($latestLeave->id),
            ]);
        }

        // No leaves yet — keep the previous fallback so the user still lands
        // somewhere sensible. Pass the empId so the list page can scope its
        // empty state to this employee instead of showing the global inbox.
        return redirect()->route('leave.request', ['empId' => $empID]);
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
            ->whereRaw('FIND_IN_SET(?, lc.eligibility)', [$leaveDetail->rank])
            ->where('rbgc.allocated_days', '>', 0)
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

        // Get employee joining date for proration
        $empJoiningDate = DB::table('employees')->where('id', $leaveDetail->emp_id)->value('joining_date');

        // Combine leave balances and usage (carry forward when leave is eligible and not used by employee)
        $leaveBalances = $benefit_grids->map(function ($grid) use ($leaveUsage, $leaveDetail, $lastYearStart, $lastYearEnd, $empJoiningDate) {
            $usageRow = $leaveUsage->get($grid->leave_category_id);
            $usedDays = $usageRow ? (int) $usageRow->used_days : 0;
            $grid->used_days = $usedDays;
            // Prorate allocation based on joining date
            $proratedDays = Common::prorateLeaveByJoiningDate((int) $grid->allocated_days, $empJoiningDate);
            $grid->allocated_days = $proratedDays;
            $available = $proratedDays - $usedDays;

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

        // Can approve: (1) applicant's reporting_to, (2) applicant is GM and current user is HR/EXCOM/HOD, (3) current user has a Pending status row, (4) HR/EXCOM can approve any pending leave
        $reportingToInt = (int)($leaveDetail->applicant_reporting_to ?? $leaveDetail->reporting_to ?? 0);
        $currentUserRank = trim((string)($employee->rank ?? ''));
        $applicantRankStr = trim((string)($leaveDetail->rank ?? ''));
        $isReportingManager = $reportingToInt > 0 && $reportingToInt === (int)$employee->id;
        $isGMLeaveApprover = ($applicantRankStr === '8') && in_array($currentUserRank, ['1', '2', '3'], true);
        $rankConfig = config('settings.Position_Rank');
        $currentUserRankLabel = $rankConfig[$currentUserRank] ?? '';
        $isHROrExcom = in_array($currentUserRankLabel, ['HR', 'EXCOM', 'GM']);
        $leaveIsPending = strtolower(trim($leaveDetail->status ?? '')) === 'pending';
        $currentUserHasPendingStatus = EmployeeLeaveStatus::where('leave_request_id', $decodedId)
            ->where('status', 'Pending')
            ->where('approver_id', $employee->id)
            ->exists();

        // Check if current user is a delegate for any pending approver in the chain
        $isDelegateForPendingApprover = false;
        if (!$currentUserHasPendingStatus && !$isHROrExcom) {
            $pendingApproverIds = EmployeeLeaveStatus::where('leave_request_id', $decodedId)
                ->where('status', 'Pending')
                ->pluck('approver_id')->toArray();
            foreach ($pendingApproverIds as $pId) {
                if (Common::hasDelegationAuthority($employee->id, $pId, $resort_id)) {
                    $isDelegateForPendingApprover = true;
                    break;
                }
            }
        }

        $canApproveThisLeave = (bool)(($isReportingManager || $isGMLeaveApprover || $currentUserHasPendingStatus || $isHROrExcom || $isDelegateForPendingApprover) && $leaveIsPending);

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
            // Compute status text: check actual leave status first, then chain details
            $leaveStatus = strtolower(trim($usage->status ?? ''));
            if ($leaveStatus === 'approved') {
                $usage->status_text = 'Approved';
            } elseif ($leaveStatus === 'rejected') {
                $usage->status_text = $usage->last_status ? "Rejected by {$role}" : 'Rejected';
            } elseif ($usage->last_status && strtolower($usage->last_status) === 'approved') {
                $usage->status_text = "Pending - {$role} Approved";
            } else {
                $usage->status_text = 'Pending';
            }
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

            $leave->from_date_formatted = $leave->from_date ? Carbon::parse($leave->from_date)->format('d M Y') : '—';
            $leave->to_date_formatted = $leave->to_date ? Carbon::parse($leave->to_date)->format('d M Y') : '—';
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
                $departurePass->departure_date_formatted = $departurePass->departure_date ? Carbon::parse($departurePass->departure_date)->format('d M Y') : '—';
                $departurePass->arrival_date_formatted = $departurePass->arrival_date ? Carbon::parse($departurePass->arrival_date)->format('d M Y') : '—';
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
        // Block non-employee admins (master admin, account-only users) from
        // submitting their own leave — there is no Employee row to attach it to.
        $applicantEmployeeRecord = $this->resort->GetEmployee ?? $this->resort->getEmployee ?? null;
        if (!$applicantEmployeeRecord) {
            return response()->json([
                'status' => 'error',
                'message' => 'Your account is not linked to an employee record. Please contact HR.',
            ], 422);
        }
        $emp_id = $applicantEmployeeRecord->id;
        $rank = $applicantEmployeeRecord->rank;

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
        $validatorRules['attachments'] = ($rules['attachment'] === 'mandatory') ? 'required|file|mimes:pdf,doc,docx,jpeg,jpg,png,gif,svg,webp,heic,heif|max:5120' : 'nullable|file|mimes:pdf,doc,docx,jpeg,jpg,png,gif,svg,webp,heic,heif|max:5120';

        $validator = Validator::make($request->all(), $validatorRules);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        // Prevent self-delegation
        if ($request->task_delegation && (int)$request->task_delegation === (int)$emp_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You cannot delegate tasks to yourself.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Define leave attachment path. The file is served from /public,
            // so the dir must live there — Storage::makeDirectory used to
            // write into storage/app/ instead, which was dead code.
            $leave_attachment = config('settings.leave_attachments');
            $dynamic_path = $leave_attachment . '/' . $emp_id;
            $absolutePath = public_path($dynamic_path);
            if (!is_dir($absolutePath)) {
                @mkdir($absolutePath, 0755, true);
            }

            // Handle file upload
            $filePath = null;
            if ($request->hasFile('attachments')) {
                $fileName = uniqid('attachment_', true) . '.' . $request->attachments->getClientOriginalExtension();
                $filePath = $dynamic_path . '/' . $fileName;
                $request->attachments->move($absolutePath, $fileName);
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

                // Calculate total days (inclusive), excluding Fridays
                // (resort weekly off) and public holidays — must match the
                // JS in resources/views/resorts/leaves/leave/index.blade.php
                // so the user's "30 Days" preview matches the persisted
                // total_days and balance debit.
                // public_holidays.holiday_date is a string column stored as
                // d-m-Y, so whereDate()/whereYear() return zero rows. Pull
                // every active row, normalise to Y-m-d in PHP, then filter
                // to the leave window.
                $rangeStart = $fromDate->format('Y-m-d');
                $rangeEnd   = $toDate->format('Y-m-d');
                $holidayLookup = PublicHoliday::where('status', 'active')
                    ->pluck('holiday_date')
                    ->map(function ($d) {
                        $d = trim((string) $d);
                        if ($d === '') return null;
                        foreach (['d-m-Y', 'd/m/Y', 'Y-m-d', 'Y/m/d', 'd-m-y', 'd/m/y'] as $fmt) {
                            try {
                                $c = \Carbon\Carbon::createFromFormat($fmt, $d);
                                if ($c) return $c->format('Y-m-d');
                            } catch (\Exception $e) { /* try next */ }
                        }
                        try { return \Carbon\Carbon::parse($d)->format('Y-m-d'); }
                        catch (\Exception $e) { return null; }
                    })
                    ->filter()
                    ->filter(fn($d) => $d >= $rangeStart && $d <= $rangeEnd)
                    ->flip();
                $totalDays = 0;
                $cursor = $fromDate->copy();
                while ($cursor->lte($toDate)) {
                    $isFriday = $cursor->isFriday();
                    $isHoliday = isset($holidayLookup[$cursor->format('Y-m-d')]);
                    if (!$isFriday && !$isHoliday) {
                        $totalDays++;
                    }
                    $cursor->addDay();
                }

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

                // Check if employee is already marked Present on any of the requested leave dates
                $presentOnDates = ParentAttendace::where('Emp_id', $emp_id)
                    ->where('resort_id', $resort_id)
                    ->whereIn('Status', ['Present', 'On-Time', 'Late', 'HalfDay', 'ShortLeave'])
                    ->whereDate('date', '>=', $fromDate->format('Y-m-d'))
                    ->whereDate('date', '<=', $toDate->format('Y-m-d'))
                    ->where(function ($q) {
                        $q->whereNotNull('CheckingTime')
                          ->where('CheckingTime', '!=', '')
                          ->where('CheckingTime', '!=', '00:00:00');
                    })
                    ->pluck('date')
                    ->map(fn($d) => Carbon::parse($d)->format('d/m/Y'))
                    ->implode(', ');

                if ($presentOnDates) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Cannot apply leave. Employee is already marked present on: {$presentOnDates}",
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

                // Check if the leave type is eligible for this employee's rank
                $leaveEligibility = DB::table('leave_categories')->where('id', $categoryId)->value('eligibility');
                if ($leaveEligibility && !in_array((string)$rank, explode(',', $leaveEligibility))) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "This leave type is not available for your position.",
                    ]);
                }

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

                // Prorate allocation based on employee's joining date
                $employeeRecord = DB::table('employees')->where('id', $emp_id)->first();
                $joiningDate = $employeeRecord->joining_date ?? null;
                $allocatedDays = Common::prorateLeaveByJoiningDate($allocatedDays, $joiningDate);

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

                // Only create a boarding pass when the user actually chose
                // "Yes" for departure pass AND filled in both dates. The old
                // `isset(...)` check was true even for empty strings, so a
                // bogus EmployeeTravelPass row was inserted on every submit.
                if ($request->input('departure') === 'Yes' && $request->filled('arr_date') && $request->filled('dept_date')) {
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
                $applicantEmployee = Employee::select('id', 'rank', 'reporting_to', 'Admin_Parent_id')->where('resort_id', $this->resort->resort_id)->find($emp_id);
                $directReportingManagerId = $applicantEmployee ? $applicantEmployee->reporting_to : null;
                $directReportingManager = $directReportingManagerId
                    ? Employee::select('id', 'rank', 'reporting_to')->where('resort_id', $this->resort->resort_id)->find($directReportingManagerId)
                    : null;

                $approvalFlow = collect(); // Store the approval flow dynamically


                $findSickLeaveCategory                  =   LeaveCategory::where('leave_type', 'LIKE', '%Sick%')
                                                                ->where('resort_id',$resort_id)
                                                                ->first();

                // If the resort has no Sick leave category, this whole block
                // is irrelevant — leaveCount stays 0 so the > 15 guard below
                // never trips. Previously this threw "Attempt to read property
                // 'id' on null" and broke every leave submission.
                $leaveCount = $findSickLeaveCategory
                    ? EmployeeLeave::where('emp_id', $emp_id)
                        ->where('leave_category_id', $findSickLeaveCategory->id)
                        ->where('total_days', '1')
                        ->whereYear('from_date', Carbon::now()->year)
                        ->count()
                    : 0;

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

                // Leave approval chain based on applicant's rank:
                // Line Worker (6) / Supervisor (5) → Reporting Manager only (1 approval)
                // Manager (4) → HOD or EXCOM → then GM (2 approvals)
                // HOD (2) → EXCOM → then GM (2 approvals)
                // EXCOM (1) → GM only (1 approval)
                // GM (8) → HR dept EXCOM, if not available then HOD of the department (1 approval)
                $rank = trim((string)($applicantEmployee->rank ?? ''));
                $approvalIds = $approvalFlow->pluck('id')->toArray();

                if ($rank === '8') {
                    // GM leave: HR Department's EXCOM approves (1 person)
                    $hrDeptId = \App\Models\ResortDepartment::where('resort_id', $this->resort->resort_id)
                        ->where('name', 'Human Resources')->value('id');
                    $gmApproverFound = false;
                    if ($hrDeptId) {
                        // Try HR dept EXCOM first
                        $hrExcom = Employee::select('id', 'rank', 'reporting_to')
                            ->where('resort_id', $this->resort->resort_id)
                            ->where('Dept_id', $hrDeptId)
                            ->where('rank', 1) // EXCOM
                            ->where('id', '!=', $emp_id)
                            ->first();
                        if ($hrExcom && !in_array($hrExcom->id, $approvalIds)) {
                            $approvalFlow->push($hrExcom);
                            $approvalIds[] = $hrExcom->id;
                            $gmApproverFound = true;
                        }
                    }
                    // Fallback: if no HR EXCOM, try HOD of the HR department
                    if (!$gmApproverFound && $hrDeptId) {
                        $hrHod = Employee::select('id', 'rank', 'reporting_to')
                            ->where('resort_id', $this->resort->resort_id)
                            ->where('Dept_id', $hrDeptId)
                            ->where('rank', 2) // HOD
                            ->where('id', '!=', $emp_id)
                            ->first();
                        if ($hrHod && !in_array($hrHod->id, $approvalIds)) {
                            $approvalFlow->push($hrHod);
                            $approvalIds[] = $hrHod->id;
                            $gmApproverFound = true;
                        }
                    }
                    // Last fallback: any EXCOM in the resort
                    if (!$gmApproverFound) {
                        $anyExcom = Employee::select('id', 'rank', 'reporting_to')
                            ->where('resort_id', $this->resort->resort_id)
                            ->where('rank', 1)
                            ->where('id', '!=', $emp_id)
                            ->first();
                        if ($anyExcom && !in_array($anyExcom->id, $approvalIds)) {
                            $approvalFlow->push($anyExcom);
                        }
                    }
                } elseif ($rank === '1') {
                    // EXCOM leave: GM only (1 approval)
                    $gmApprover = Employee::select('id', 'rank', 'reporting_to')
                        ->where('resort_id', $this->resort->resort_id)
                        ->where('rank', 8) // GM
                        ->where('id', '!=', $emp_id)
                        ->first();
                    if ($gmApprover && !in_array($gmApprover->id, $approvalIds)) {
                        $approvalFlow->push($gmApprover);
                        $approvalIds[] = $gmApprover->id;
                    }
                } elseif ($rank === '2') {
                    // HOD leave: EXCOM → then GM (2 approvals)
                    if ($directReportingManager && $directReportingManager->rank) {
                        $approvalFlow->push($directReportingManager);
                        $approvalIds[] = $directReportingManager->id;
                    } else {
                        // Fallback: find any EXCOM if reporting_to is not set
                        $excomApprover = Employee::select('id', 'rank', 'reporting_to')
                            ->where('resort_id', $this->resort->resort_id)
                            ->where('rank', 1) // EXCOM
                            ->where('id', '!=', $emp_id)
                            ->first();
                        if ($excomApprover && !in_array($excomApprover->id, $approvalIds)) {
                            $approvalFlow->push($excomApprover);
                            $approvalIds[] = $excomApprover->id;
                        }
                    }
                    $gmApprover = Employee::select('id', 'rank', 'reporting_to')
                        ->where('resort_id', $this->resort->resort_id)
                        ->where('rank', 8) // GM
                        ->where('id', '!=', $emp_id)
                        ->first();
                    if ($gmApprover && !in_array($gmApprover->id, $approvalIds)) {
                        $approvalFlow->push($gmApprover);
                        $approvalIds[] = $gmApprover->id;
                    }
                } elseif ($rank === '4') {
                    // Manager leave: HOD or EXCOM (reporting manager) → then GM (2 approvals)
                    if ($directReportingManager && $directReportingManager->rank) {
                        $approvalFlow->push($directReportingManager);
                        $approvalIds[] = $directReportingManager->id;
                    } else {
                        // Fallback: find HOD of same department
                        $applicantDeptId = $applicantEmployee->Dept_id ?? null;
                        if (!$applicantDeptId) {
                            $applicantDeptId = Employee::where('id', $emp_id)->value('Dept_id');
                        }
                        $hodApprover = Employee::select('id', 'rank', 'reporting_to')
                            ->where('resort_id', $this->resort->resort_id)
                            ->where('Dept_id', $applicantDeptId)
                            ->where('rank', 2) // HOD
                            ->where('id', '!=', $emp_id)
                            ->first();
                        if ($hodApprover && !in_array($hodApprover->id, $approvalIds)) {
                            $approvalFlow->push($hodApprover);
                            $approvalIds[] = $hodApprover->id;
                        }
                    }
                    $gmApprover = Employee::select('id', 'rank', 'reporting_to')
                        ->where('resort_id', $this->resort->resort_id)
                        ->where('rank', 8) // GM
                        ->where('id', '!=', $emp_id)
                        ->first();
                    if ($gmApprover && !in_array($gmApprover->id, $approvalIds)) {
                        $approvalFlow->push($gmApprover);
                        $approvalIds[] = $gmApprover->id;
                    }
                } else {
                    // Line Worker (6) / Supervisor (5) / others: Reporting Manager only (1 approval)
                    if ($directReportingManager && $directReportingManager->rank) {
                        $approvalFlow->push($directReportingManager);
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

            // ── Notifications after leave submission ──
            try {
                $applicantAdmin = ResortAdmin::find($applicantEmployee->Admin_Parent_id ?? 0);
                $applicantName = $applicantAdmin ? trim($applicantAdmin->first_name . ' ' . $applicantAdmin->last_name) : 'An employee';
                $leaveFromFormatted = Carbon::parse($fromDate)->format('d M Y');
                $leaveToFormatted = Carbon::parse($toDate)->format('d M Y');

                // Notify each approver in the chain
                foreach ($approvalFlow as $approver) {
                    $approverEmp = Employee::find($approver->id);
                    if ($approverEmp) {
                        event(new ResortNotificationEvent(Common::nofitication(
                            $resort_id, 10,
                            'Leave Approval Required',
                            $applicantName . ' has applied for leave from ' . $leaveFromFormatted . ' to ' . $leaveToFormatted . '. Please review.',
                            $leave->id,
                            $approverEmp->id,
                            'Leave'
                        )));
                    }
                }

                // Notify task delegation person
                if ($request->task_delegation) {
                    $delegatedEmp = Employee::find($request->task_delegation);
                    if ($delegatedEmp) {
                        event(new ResortNotificationEvent(Common::nofitication(
                            $resort_id, 10,
                            'Task Delegation',
                            $applicantName . ' has delegated tasks to you during leave (' . $leaveFromFormatted . ' - ' . $leaveToFormatted . '). Pending approval.',
                            $leave->id,
                            $delegatedEmp->id,
                            'Leave'
                        )));
                    }
                }

                // ── Confirmation email to the applicant ──
                // Was previously delivered via the external NOTIFICATION_URL
                // service; this gives a self-contained SMTP path so the
                // applicant always gets a record of their submission.
                if ($applicantAdmin && filter_var($applicantAdmin->email ?? '', FILTER_VALIDATE_EMAIL)) {
                    $leaveCategoryName = LeaveCategory::where('id', $leave->leave_category_id ?? 0)->value('leave_type') ?? 'Leave';
                    $totalDays = $leave->total_days ?? Carbon::parse($fromDate)->diffInDays(Carbon::parse($toDate)) + 1;

                    Mail::send('emails.leave-applied', [
                        'applicantName'   => $applicantName,
                        'leaveCategory'   => $leaveCategoryName,
                        'fromDate'        => $leaveFromFormatted,
                        'toDate'          => $leaveToFormatted,
                        'totalDays'       => $totalDays,
                        'reason'          => $request->reason ?? '',
                    ], function ($m) use ($applicantAdmin, $leaveFromFormatted, $leaveToFormatted) {
                        $m->to($applicantAdmin->email, $applicantAdmin->first_name . ' ' . $applicantAdmin->last_name)
                          ->subject('Leave Application Submitted (' . $leaveFromFormatted . ' – ' . $leaveToFormatted . ')');
                    });
                }
            } catch (\Exception $notifEx) {
                \Log::warning('Leave notification error: ' . $notifEx->getMessage());
            }

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
        // Drop empty / null entries up front — adding a fresh "Add another"
        // card sends an empty dropdown value, which previously made this
        // endpoint flag a relation error against the placeholder card.
        $categoryId = array_values(array_filter(
            (array) $request->input('category_id'),
            fn ($v) => $v !== null && $v !== '' && $v !== '0'
        ));
        $categoryId = array_values(array_unique(array_map('intval', $categoryId)));

        if (count($categoryId) < 2) {
            return response()->json([
                'status' => 'success',
                'message' => 'Valid selection.',
            ], 200);
        }

        // Find the leave categories by IDs
        $leaveCategories = LeaveCategory::whereIn('id', $categoryId)->get();

        // Check for any relation: leave_category can be comma-separated IDs (e.g. "1,3,5"); at least one selected category must list another selected ID in its leave_category
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
            ->whereRaw('FIND_IN_SET(?, lc.eligibility)', [$employee->rank])
            ->where('rbgc.allocated_days', '>', 0)
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

        // Get employee joining date for proration
        $pdfEmpJoiningDate = $employee->joining_date ?? null;

        $leaveBalances = $benefit_grids->map(function ($grid) use ($usedPerCategory, $empIdInt, $lastYearStartPdf, $lastYearEndPdf, $pdfEmpJoiningDate) {
            $usedDays = (int) ($usedPerCategory->get($grid->leave_category_id) ?? 0);
            $grid->used_days = $usedDays;
            // Prorate allocation based on joining date
            $proratedDays = Common::prorateLeaveByJoiningDate((int) $grid->allocated_days, $pdfEmpJoiningDate);
            $grid->allocated_days = $proratedDays;
            $available = $proratedDays - $usedDays;
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

        // If leave is already finalized, block action
        if (in_array($leave->status, ['Approved', 'Rejected'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'This leave request is already '.$leave->status.'.',
            ], 200);
        }

        // Check if current user has a pending approval row in the chain
        $currentUserPendingStatus = EmployeeLeaveStatus::where('leave_request_id', $leaveId)
            ->where('approver_id', $currentApproverId)
            ->where('status', 'Pending')
            ->exists();

        // Check if current user is a delegate for any pending approver in this chain
        $delegateForApproverId = null;
        if (!$currentUserPendingStatus) {
            $pendingApproverIds = EmployeeLeaveStatus::where('leave_request_id', $leaveId)
                ->where('status', 'Pending')
                ->pluck('approver_id')
                ->toArray();

            foreach ($pendingApproverIds as $pendingApproverId) {
                if (Common::hasDelegationAuthority($currentApproverId, $pendingApproverId, $this->resort->resort_id)) {
                    $delegateForApproverId = $pendingApproverId;
                    break;
                }
            }
        }

        $isDelegateApprover = ($delegateForApproverId !== null);
        $isHROrExcom = false; // Approval is now purely chain-based

        // If the current user doesn't have a pending row and is not a delegate — block
        if (!$currentUserPendingStatus && !$isDelegateApprover) {
            $actionname = ($action == "Rejected") ? "reject" : "approve";
            return response()->json([
                'status' => 'error',
                'message' => "You cannot $actionname this request. You are not in the approval chain or your approval is not pending.",
            ], 403);
        }

        // Approval is chain-based only: user must have a Pending row or be a delegate for one
        $applicant = Employee::find($leave->emp_id);
        $hasCurrentUserPendingRow = EmployeeLeaveStatus::where('leave_request_id', $leave->id)
            ->where('approver_id', $currentApproverId)
            ->where('status', 'Pending')
            ->exists();

        if (!$applicant || (!$hasCurrentUserPendingRow && !$isDelegateApprover)) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not in the approval chain for this leave request.',
            ], 403);
        }

        // Determine which approver_id to update in the chain
        $effectiveApproverId = $isDelegateApprover ? $delegateForApproverId : $currentApproverId;
        $delegateComment = $isDelegateApprover ? ' (Acted by delegate)' : '';

        // Update approval chain records — only the effective approver's row
        EmployeeLeaveStatus::where('leave_request_id', $leave->id)
            ->where('approver_id', $effectiveApproverId)
            ->where('status', 'Pending')
            ->update([
                'status'     => $action,
                'comments'   => ($comments ?? '') . $delegateComment,
                'approved_at'=> now(),
            ]);

        if ($action == 'Approved') {
            $pendingCount = EmployeeLeaveStatus::where('leave_request_id', $leave->id)->where('status', 'Pending')->count();
            if ($pendingCount === 0) {
                $leave->status = 'Approved';
                $leave->save();
            }

            // ── Notifications ──
            try {
                $applicantAdmin = ResortAdmin::find($applicant->Admin_Parent_id ?? 0);
                $applicantName = $applicantAdmin ? trim($applicantAdmin->first_name . ' ' . $applicantAdmin->last_name) : 'Employee';
                $leaveFrom = Carbon::parse($leave->from_date)->format('d M Y');
                $leaveTo = Carbon::parse($leave->to_date)->format('d M Y');

                if ($pendingCount === 0) {
                    // Fully approved — notify applicant
                    event(new ResortNotificationEvent(Common::nofitication(
                        $leave->resort_id, 10,
                        'Leave Approved',
                        'Your leave from ' . $leaveFrom . ' to ' . $leaveTo . ' has been fully approved.',
                        $leave->id,
                        $applicant->id,
                        'Leave'
                    )));

                    // Notify task delegation person that leave is confirmed
                    if ($leave->task_delegation) {
                        $delegatedEmp = Employee::find($leave->task_delegation);
                        if ($delegatedEmp) {
                            event(new ResortNotificationEvent(Common::nofitication(
                                $leave->resort_id, 10,
                                'Task Delegation Confirmed',
                                $applicantName . '\'s leave has been approved (' . $leaveFrom . ' - ' . $leaveTo . '). You are responsible for their tasks during this period.',
                                $leave->id,
                                $delegatedEmp->id,
                                'Leave'
                            )));
                        }
                    }
                } else {
                    // Partially approved — notify applicant
                    $approverAdmin = ResortAdmin::find($this->resort->GetEmployee->Admin_Parent_id ?? 0);
                    $approverName = $approverAdmin ? trim($approverAdmin->first_name . ' ' . $approverAdmin->last_name) : 'Approver';
                    event(new ResortNotificationEvent(Common::nofitication(
                        $leave->resort_id, 10,
                        'Leave Partially Approved',
                        'Your leave (' . $leaveFrom . ' - ' . $leaveTo . ') has been approved by ' . $approverName . '. Awaiting next approval.',
                        $leave->id,
                        $applicant->id,
                        'Leave'
                    )));
                }
            } catch (\Exception $notifEx) {
                \Log::warning('Leave approval notification error: ' . $notifEx->getMessage());
            }

            return response()->json([
                'status'  => 'success',
                'message' => $pendingCount === 0 ? 'Leave approved successfully.' : 'Leave approved at your level. Awaiting next approval.',
            ], 200);
        } elseif ($action === 'Rejected') {
            // Rejection at any level rejects the entire leave
            $leave->status = 'Rejected';
            $leave->save();
            // Also mark all remaining pending chain records as rejected
            EmployeeLeaveStatus::where('leave_request_id', $leave->id)
                ->where('status', 'Pending')
                ->update([
                    'status'     => 'Rejected',
                    'comments'   => $comments,
                    'approved_at'=> now(),
                ]);

            // ── Notify applicant of rejection ──
            try {
                $approverAdmin = ResortAdmin::find($this->resort->GetEmployee->Admin_Parent_id ?? 0);
                $approverName = $approverAdmin ? trim($approverAdmin->first_name . ' ' . $approverAdmin->last_name) : 'Approver';
                $leaveFrom = Carbon::parse($leave->from_date)->format('d M Y');
                $leaveTo = Carbon::parse($leave->to_date)->format('d M Y');

                event(new ResortNotificationEvent(Common::nofitication(
                    $leave->resort_id, 10,
                    'Leave Rejected',
                    'Your leave (' . $leaveFrom . ' - ' . $leaveTo . ') has been rejected by ' . $approverName . '.' . ($comments ? ' Reason: ' . $comments : ''),
                    $leave->id,
                    $applicant->id,
                    'Leave'
                )));

                // Notify task delegation person that leave is cancelled
                if ($leave->task_delegation) {
                    $delegatedEmp = Employee::find($leave->task_delegation);
                    if ($delegatedEmp) {
                        $applicantAdmin = ResortAdmin::find($applicant->Admin_Parent_id ?? 0);
                        $applicantName = $applicantAdmin ? trim($applicantAdmin->first_name . ' ' . $applicantAdmin->last_name) : 'Employee';
                        event(new ResortNotificationEvent(Common::nofitication(
                            $leave->resort_id, 10,
                            'Task Delegation Cancelled',
                            $applicantName . '\'s leave (' . $leaveFrom . ' - ' . $leaveTo . ') has been rejected. Task delegation is no longer active.',
                            $leave->id,
                            $delegatedEmp->id,
                            'Leave'
                        )));
                    }
                }
            } catch (\Exception $notifEx) {
                \Log::warning('Leave rejection notification error: ' . $notifEx->getMessage());
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Leave Rejected.',
            ], 200);
        } else {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid action.',
            ], 200);
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
