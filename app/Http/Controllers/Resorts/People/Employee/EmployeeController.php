<?php

namespace App\Http\Controllers\Resorts\People\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Exports\SelectedEmployeesExport;
use Illuminate\Support\Facades\Session;
use App\Models\Employee;
use App\Models\ResortAdmin;
use App\Models\ResortDivision;
use App\Models\ResortDepartment;
use App\Models\EmployeeLanguage;
use App\Models\ResortBenifitGrid;
use App\Models\ResortPosition;
use App\Models\ResortSection;
use App\Models\EmployeeEducation;
use App\Models\EmployeeExperiance;
use App\Models\EmployeesDocument;
use App\Models\SOSTeamManagementModel;
use App\Models\SOSRolesAndPermission;
use App\Models\SOSTeamMemeberModel;
use App\Models\ResortBudgetCost;
use App\Services\EmployeeAllowanceService;
use App\Models\EmployeeAllowance;
use App\Models\FilemangementSystem;
use App\Models\Compliance;
use App\Models\EmployeeBankDetails;
use App\Events\ResortNotificationEvent;
use App\Models\ManningandbudgetingConfigfiles;
use App\Models\EmployeeManningAndBudgeting;
use App\Models\EmployeeManningAndBudgetingConfig;
use App\Models\ResortTransportation;
use App\Models\EmployeeTravelQuota;
use App\Models\ProbationLetterTemplate;
use App\Models\PayrollAdvance;
use App\Models\Resort;
use App\Mail\ProbationLetterMail;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use Auth;
use App\Models\ResortSiteSettings;
use Hash;
use Config;
use Common;
use DB;
use Schema;
use Carbon\Carbon;
class EmployeeController extends Controller
{
    public $resort;
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;
    }

    public function index()
    {
        $page_title ='Employees';
        $resort_id = $this->resort->resort_id;
        $scopedDeptIds = Common::getScopedDepartmentIds();
        $teams = SOSTeamManagementModel::where('resort_id',$resort_id)->get();
        $roles = SOSRolesAndPermission::where('resort_id',$resort_id)->get();
        $departments = ResortDepartment::where('resort_id',$resort_id)
            ->where('status','active')
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('id', $scopedDeptIds))
            ->get();
        $positions = ResortPosition::where('resort_id',$resort_id)->where('status','active')->get();
        $resort_divisions = ResortDivision::where('resort_id',$resort_id)->where('status','active')->get();
        $employees = Employee::with(['resortAdmin','position','department'])
            ->where('resort_id',$resort_id)
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))
            ->latest()
            ->get();
        return view('resorts.people.employee.list',compact('page_title','resort_id','resort_divisions','employees','departments','positions','teams','roles'));
    }


    public function fetchEmployeesGrid(Request $request)
    {
        $scopedDeptIds = Common::getScopedDepartmentIds();
        $query = Employee::with(['resortAdmin', 'position', 'department','education','experiance'])
            ->where('resort_id',$this->resort->resort_id)
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds));
        if($request->status == null)
        {
            $query->where('status','!=','Inactive');
        }

        if ($request->searchTerm) {
            $searchTerm = $request->searchTerm;
            $query->where(function ($q) use ($searchTerm) {
                $q->Where('status', 'LIKE', "%{$searchTerm}%")
                ->orWhere('Emp_id','LIKE',"%{$searchTerm}%");

                // Search employee name (first or last)
                $q->orWhereHas('resortAdmin', function ($adminQ) use ($searchTerm) {
                    $adminQ->where(function ($nameQ) use ($searchTerm) {
                        $nameQ->where('first_name', 'LIKE', "%{$searchTerm}%")
                              ->orWhere('last_name', 'LIKE', "%{$searchTerm}%");
                    });
                });

                $q->orWhereHas('position', function ($positionQ) use ($searchTerm) {
                    $positionQ->where('position_title', 'LIKE', "%{$searchTerm}%");
                });

                $q->orWhereHas('department', function ($deptQ) use ($searchTerm) {
                    $deptQ->where('name', 'LIKE', "%{$searchTerm}%");
                });
            });
        }
        // Apply filters if present
        if ($request->filled('department_id')) {
            $query->where('Dept_id', $request->department_id);
        }

        if ($request->filled('position_id')) {
            $query->where('Position_id', $request->position_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('location')) {
            $query->where('employees.location', $request->location);
        }

        $pageSize = $request->input('pageSize', 10); // default to 10 if not sent
        $employees = $query->orderBy('created_by', 'desc')->paginate($pageSize);

        return response()->json([
            'html' => view('resorts.renderfiles.employee_grid', compact('employees'))->render(),
            'pagination' => (string) $employees->withQueryString()->links(),
        ]);
    }

    public function fetchEmployeesList(Request $request)
    {
        $scopedDeptIds = Common::getScopedDepartmentIds();
        $query = Employee::with(['resortAdmin', 'position', 'department','education','experiance'])
            ->where('resort_id',$this->resort->resort_id)
            ->where('status','!=','Inactive')
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds));

        if ($request->searchTerm) {
            $searchTerm = $request->searchTerm;
            $query->where(function ($q) use ($searchTerm) {
                $q->Where('status', 'LIKE', "%{$searchTerm}%")
                ->orWhere('Emp_id','LIKE',"%{$searchTerm}%");

                // Search employee name (first or last)
                $q->orWhereHas('resortAdmin', function ($adminQ) use ($searchTerm) {
                    $adminQ->where(function ($nameQ) use ($searchTerm) {
                        $nameQ->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$searchTerm}%"])
                              ->orWhere('first_name', 'LIKE', "%{$searchTerm}%")
                              ->orWhere('last_name', 'LIKE', "%{$searchTerm}%");
                    });
                });

                $q->orWhereHas('position', function ($positionQ) use ($searchTerm) {
                    $positionQ->where('position_title', 'LIKE', "%{$searchTerm}%");
                });

                $q->orWhereHas('department', function ($deptQ) use ($searchTerm) {
                    $deptQ->where('name', 'LIKE', "%{$searchTerm}%");
                });

            });
        }

        if ($request->department_id) {
            $query->where('Dept_id', $request->department_id);
        }

        if ($request->position_id) {
            $query->where('Position_id', $request->position_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->filled('location')) {
            $query->where('employees.location', $request->location);
        }

         // ✅ Sorting manually if needed (optional)
        if ($request->has('order')) {
            $columns = $request->input('columns');
            $order = $request->input('order')[0];
            $columnIndex = $order['column'];
            $columnName = $columns[$columnIndex]['data'] ?? 'created_at';
            $direction = $order['dir'] ?? 'desc';

            // Prevent sorting on custom columns like 'action' or 'checkbox'
            if (in_array($columnName, ['Emp_id','employment_type', 'status', 'created_at'])) {
                $query->orderBy($columnName, $direction);
            } else {
                // Fallback to default
                $query->orderBy('created_at', 'desc');
            }
        } else {
            $query->orderBy('created_at', 'desc'); // Default sort
        }

        return datatables()->of($query)
            ->addColumn('checkbox', fn($row) => '<input type="checkbox" class="employee-checkbox" value="'.$row->id.'" />')
            ->addColumn('applicant', fn($row) => '
                <div class="tableUser-block">
                    <div class="img-circle">
                        <img src="'.Common::getResortUserPicture($row->Admin_Parent_id ?? null).'" alt="user">
                    </div>
                    <span class="userApplicants-btn">'.$row->resortAdmin->full_name.'</span>
                </div>')
            ->addColumn('position', fn($row) => $row->position->position_title ?? '')
            ->addColumn('department', fn($row) => $row->department->name ?? '')
            ->addColumn('status', fn($row) => '<span class="badge badge-themeSuccess">'.$row->status.'</span>')
            ->addColumn('employment_type', fn($row) => $row->employment_type) // Optional dynamic
            ->addColumn('action', function ($row) {
                return '
                    <div class="dropdown table-dropdown">
                        <button class="btn btn-secondary dropdown-toggle dots-link" type="button" data-bs-toggle="dropdown">
                            <i class="fa-solid fa-ellipsis"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="' . route("people.employees.details", base64_encode($row->id)) . '">View Profile</a></li>
                            <li><a class="dropdown-item delete-employee" href="#" data-emp-id="'.$row->id.'">
                            Delete Employee</a></li>
                            <li><a class="dropdown-item add-to-team-btn" href="#" data-emp-id="'.$row->id.'">
                            Add to Team / Assign Role</a></li>
                            <li><a class="dropdown-item change-status" href="#"
                                data-id="'.$row->id.'"
                                data-status="'.$row->status.'">
                                Change Status
                            </a></li>
                        </ul>
                    </div>';
            })
            ->addColumn('created_at', fn($row) => $row->created_at) // Hidden column used for sorting
            ->rawColumns(['checkbox', 'applicant', 'status', 'action'])
            ->make(true);
    }

    public function getAllEmployeeIds(Request $request)
    {
        $scopedDeptIds = Common::getScopedDepartmentIds();
        // Start with a proper query
        $query = Employee::where('resort_id', $this->resort->resort_id)
                        ->where('status', '!=', 'Inactive')
                        ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds));

        if ($request->department_id) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->position_id) {
            $query->where('position_id', $request->position_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->filled('location')) {
            $query->where('location', $request->location);
        }

        if ($request->searchTerm) {
            $query->where(function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->searchTerm . '%')
                ->orWhere('last_name', 'like', '%' . $request->searchTerm . '%');
            });
        }

        $ids = $query->pluck('id')->toArray();

        return response()->json([
            'ids' => $ids,
            'total' => count($ids)
        ]);
    }

    public function exportSelected(Request $request)
    {
        $ids = $request->employee_ids;

        if (empty($ids)) {
            return response()->json(['error' => 'No employees selected'], 400);
        }

        return Excel::download(new SelectedEmployeesExport($ids), 'selected_employees.xlsx');
    }

    public function create()
    {
        $page_title ='Create Employee';
        $resort_id = $this->resort->resort_id;
        $scopedDeptIds = Common::getScopedDepartmentIds();
        // Next sequential employee id per resort (prefix-aware, off the highest
        // existing Emp_id — NOT the table primary key). User can still edit it.
        $employee_id = Common::nextEmployeeId($resort_id);
        $resort_divisions = ResortDivision::where('resort_id',$resort_id)->where('status','active')->get();
        $departments = ResortDepartment::where('resort_id',$resort_id)
            ->where('status','active')
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('id', $scopedDeptIds))
            ->get();
        $positions = ResortPosition::where('resort_id',$resort_id)->where('status','active')->get();
        $sections = ResortSection::where('resort_id',$resort_id)->where('status','active')->get();
        $payrollAllowance = ResortBudgetCost::where('resort_id', $resort_id)
            ->where('is_payroll_allowance', '1')
            ->get();
        $nationalitys = config('settings.nationalities');
        $countries = config('settings.countries');

        // ------------------------------------------------------------------
        // "Hire against a vacancy" picker (collapsible panel at the top of
        // Step 1). Uses the SAME filter as the offline-interview picker
        // (TalentAcquisition/OfflineInterviewController::create) so HR sees
        // a consistent shortlist on both pages: vacancies whose TA approval
        // is complete (Approved_By = TaFinalApproval, status =
        // ForwardedToNext) and which have an active application_links row.
        // Selecting one pre-fills Department/Position/Division in Step 2.
        // The panel is optional — direct hires skip it entirely.
        // ------------------------------------------------------------------
        $vacancies = collect();
        if (Schema::hasTable('vacancies')
            && Schema::hasTable('t_anotification_parents')
            && Schema::hasTable('t_anotification_children')
            && Schema::hasTable('application_links')) {
            // Subquery: how many employees have already been hired against
            // Filled-slot count per vacancy. Two sources combined:
            //   1. employees.vacancy_id — exact link, set by the new-
            //      hire form. Requires the 2026_06_01 migration; guarded.
            //   2. applicant_wise_statuses.status='Contract Accepted' on
            //      applicant_form_data.Parent_v_id — the TA flow's
            //      definition of "hired". Captures resorts that haven't
            //      run the migration yet AND old hires that pre-date
            //      the vacancy_id column.
            // Building both as subqueries + UNION ALL so the leftJoinSub
            // pattern below stays a single read. MAX() in the outer
            // grouped select picks the higher of the two so neither
            // path under-counts the slot.
            $hasVacancyIdCol = Schema::hasColumn('employees', 'vacancy_id');
            if ($hasVacancyIdCol) {
                $empFilled = DB::table('employees')
                    ->select('vacancy_id', DB::raw('COUNT(*) as filled'))
                    ->where('resort_id', $resort_id)
                    ->whereNotIn('status', ['Terminated', 'Inactive'])
                    ->whereNotNull('vacancy_id')
                    ->groupBy('vacancy_id');
                $taFilled = DB::table('applicant_wise_statuses as aws')
                    ->join('applicant_form_data as afd', 'afd.id', '=', 'aws.Applicant_id')
                    ->where('afd.resort_id', $resort_id)
                    ->where('aws.status', 'Contract Accepted')
                    ->groupBy('afd.Parent_v_id')
                    ->select('afd.Parent_v_id as vacancy_id', DB::raw('COUNT(DISTINCT aws.Applicant_id) as filled'));
                $filledByVacancy = $empFilled->unionAll($taFilled);
            } else {
                // No vacancy_id column → fall back to TA-flow only.
                $filledByVacancy = DB::table('applicant_wise_statuses as aws')
                    ->join('applicant_form_data as afd', 'afd.id', '=', 'aws.Applicant_id')
                    ->where('afd.resort_id', $resort_id)
                    ->where('aws.status', 'Contract Accepted')
                    ->groupBy('afd.Parent_v_id')
                    ->select('afd.Parent_v_id as vacancy_id', DB::raw('COUNT(DISTINCT aws.Applicant_id) as filled'));
            }

            $vacanciesQuery = DB::table('vacancies as v')
                ->join('resort_positions as p', 'p.id', '=', 'v.position')
                ->join('resort_departments as d', 'd.id', '=', 'v.department')
                // Division is reached through the department row
                // (resort_departments.division_id → resort_divisions.id).
                // resort_positions has no Division_id column — the earlier
                // join on p.Division_id caused a 1054 Unknown column error.
                ->leftJoin('resort_divisions as dv', 'dv.id', '=', 'd.division_id')
                ->join('t_anotification_parents as tap', 'tap.V_id', '=', 'v.id')
                ->join('t_anotification_children as tac', 'tac.Parent_ta_id', '=', 'tap.id')
                ->join('application_links as al', 'al.ta_child_id', '=', 'tac.id');

            // Always join the filled-count sub — even without the
            // employees.vacancy_id column, the TA-flow ("Contract
            // Accepted") fallback supplies values for this DB.
            $vacanciesQuery->leftJoinSub($filledByVacancy, 'fb', function ($j) {
                $j->on('fb.vacancy_id', '=', 'v.id');
            });

            $vacancies = $vacanciesQuery
                ->where('v.Resort_id', $resort_id)
                ->where('tac.Approved_By', Common::TaFinalApproval($resort_id))
                ->where('tac.status', 'ForwardedToNext')
                ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('d.id', $scopedDeptIds))
                ->groupBy(
                    'v.id', 'p.id', 'p.position_title', 'd.id', 'd.name',
                    'd.code', 'dv.id', 'dv.name', 'v.Total_position_required'
                )
                ->select(
                    'v.id as vacancy_id',
                    'p.id as position_id',
                    'p.position_title',
                    'd.id as department_id',
                    'd.name as department_name',
                    'd.code as department_code',
                    'dv.id as division_id',
                    'dv.name as division_name',
                    'v.Total_position_required as no_of_positions',
                    DB::raw('COALESCE(MAX(fb.filled), 0) as filled_count'),
                    DB::raw('MAX(al.link_Expiry_date) as link_expiry_date'),
                    // GM (final-approval) timestamp — the row that flipped
                    // the vacancy into the "ready to interview" state. Used
                    // on the create page to clamp the Joining Date minimum
                    // (an employee can't join before HR was even cleared to
                    // hire them).
                    DB::raw('MAX(tac.updated_at) as gm_approved_at')
                )
                ->orderByDesc('v.id')
                ->get()
                ->map(function ($v) {
                    $v->expiry_date_label = $v->link_expiry_date
                        ? \Carbon\Carbon::parse($v->link_expiry_date)->format('d M Y')
                        : '—';
                    $v->gm_approved_at_iso = $v->gm_approved_at
                        ? \Carbon\Carbon::parse($v->gm_approved_at)->format('Y-m-d')
                        : null;
                    $v->gm_approved_at_label = $v->gm_approved_at
                        ? \Carbon\Carbon::parse($v->gm_approved_at)->format('d M Y')
                        : null;
                    // Remaining slots = budgeted − already hired. We expose
                    // this on the picker UI so HR can see e.g. "1 of 2 left"
                    // and the row is auto-hidden once it reaches 0.
                    $total = (int) ($v->no_of_positions ?? 0);
                    $filled = (int) ($v->filled_count ?? 0);
                    $v->remaining_slots = max(0, $total - $filled);
                    return $v;
                })
                // A fully-filled vacancy is not vacant — strip it from the
                // picker. The store() guard re-checks server-side so a
                // stale tab can't sneak through.
                ->filter(fn($v) => $v->remaining_slots > 0)
                ->values();
        }

        return view('resorts.people.employee.create',compact('page_title','resort_id','resort_divisions','departments','employee_id','positions','sections','payrollAllowance','nationalitys','countries','vacancies'));
    }

    public function store(Request $request)
    {

        try{
            $check_admin = ResortAdmin::where('resort_id', $this->resort->resort_id)
                ->where('email', $request->email_address)
                ->where('status', 'active')
                ->first();

            if ($check_admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email address already exists for another employee. Please use a different email address.'

                ]);
            }

            // -------------------------------------------------------------
            // "Hire against a vacancy" is mandatory. Two guards:
            //   1. vacancy_id must be present on the request
            //   2. the vacancy must still have at least one remaining slot
            //      (Total_position_required − COUNT(employees.vacancy_id))
            // The picker on /create already hides filled vacancies, but a
            // stale tab or two HRs hiring at once could still post a now-
            // filled vacancy — this is the concurrency-safe check.
            // -------------------------------------------------------------
            $vacancyId = $request->input('vacancy_id');
            if (!$vacancyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must hire against an open vacancy. Please pick one from the "Hire against a vacancy" panel.'
                ]);
            }
            $vacancy = DB::table('vacancies')
                ->where('id', $vacancyId)
                ->where('Resort_id', $this->resort->resort_id)
                ->first();
            if (!$vacancy) {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected vacancy no longer exists for this resort.'
                ]);
            }
            $hasVacancyIdCol = \Schema::hasColumn('employees', 'vacancy_id');
            if ($hasVacancyIdCol) {
                $alreadyFilled = (int) Employee::where('vacancy_id', $vacancyId)
                    ->whereNotIn('status', ['Terminated', 'Inactive'])
                    ->count();
                $totalRequired = (int) ($vacancy->Total_position_required ?? 0);
                if ($totalRequired > 0 && $alreadyFilled >= $totalRequired) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This vacancy has already been fully filled ('
                            . $alreadyFilled . ' / ' . $totalRequired
                            . '). Please pick another open vacancy.'
                    ]);
                }
            }

            // License cap — resorts.no_of_users set on the super-admin
            // resort form. Blocks the hire once the cap is reached.
            $limitError = Common::employeeLimitError($this->resort->resort_id);
            if ($limitError) {
                return response()->json([
                    'success' => false,
                    'message' => $limitError
                ]);
            }

           DB::beginTransaction();
            $resortAdminData = [
                'first_name' => $request->employeeF_name,
                'middle_name' => $request->employeeM_name,
                'last_name' => $request->employeeL_name,
                'email' => $request->email_address,
                'personal_phone' => $request->mobile_num,
                'gender' => $request->gender,
                'address_line_1' => $request->permanent_addLine1,
                'address_line_2'=> $request->parmanent_addline2,
                'city' => $request->parmanent_city,
                'state' => $request->parmanent_state,
                'zip' => $request->parmanent_postal_code,
                'country' => $request->parmanent_country,
            ];

            $dob = Carbon::createFromFormat('d/m/Y', $request->date_birth)->format('Y-m-d');
            $joining_date = Carbon::createFromFormat('d/m/Y', $request->joining_date)->format('Y-m-d');

            // Probation window is policy-driven (joining_date + 3 months)
            // and the field on the form is disabled. We derive it server-
            // side regardless of what the client posts so a tampered or
            // empty submission still lands the correct date — but only for
            // employees whose employment_type is Probationary. For any
            // other employment type (Full-Time, Contract, etc.) we leave
            // probation_end_date NULL and probation_status='Confirmed' so
            // the Probation module ignores them.
            $isProbationary  = ($request->employment_status === 'Probationary');
            $probation_end_date = $isProbationary
                ? Carbon::createFromFormat('Y-m-d', $joining_date)->addMonths(3)->format('Y-m-d')
                : null;
            $probation_status = $isProbationary ? 'Active' : 'Confirmed';

            // Every newly-created employee lands in 'Onboarding'. That's
            // the lifecycle state expected by:
            //   • the Onboarding module (People → Onboarding) which
            //     surfaces them for orientation, document collection,
            //     facility tours, etc.
            //   • the Activate Employee button on the detail page, which
            //     specifically requires status='Onboarding' to fire
            //     (see EmployeeController::activate around line 1251).
            // The Probationary flow doesn't kick in here — it's the
            // ACTIVATE step that decides whether the employee becomes
            // 'Probationary' (when employment_type='Probationary') or
            // straight 'Active'. probation_status is still set to
            // 'Active' so the Probation module picks them up the
            // moment they're activated.
            $initial_status = 'Onboarding';

            // ----------------------------------------------------------------
            // Department / Position / Division resolution.
            //
            // The create page locks these dropdowns via prop('disabled', true)
            // when HR picks a vacancy. Browsers SKIP disabled inputs when
            // serializing forms, so the canonical `department`/`position`/
            // `division` fields can arrive empty even though the values are
            // visibly chosen. The view also writes hidden fallbacks
            // (`vacancy_department_id`, `vacancy_position_id`,
            // `vacancy_division_id`) — prefer the visible value if present,
            // otherwise fall back to the vacancy hidden field, otherwise
            // re-read straight off the vacancy row as a last line of
            // defence. This is the fix for "Column 'Dept_id' cannot be null"
            // on vacancy-tied hires.
            // ----------------------------------------------------------------
            $deptId = $request->filled('department')
                ? $request->department
                : ($request->filled('vacancy_department_id')
                    ? $request->vacancy_department_id
                    : ($vacancy->department ?? null));
            $positionId = $request->filled('position')
                ? $request->position
                : ($request->filled('vacancy_position_id')
                    ? $request->vacancy_position_id
                    : ($vacancy->position ?? null));
            $divisionId = $request->filled('division')
                ? $request->division
                : ($request->filled('vacancy_division_id')
                    ? $request->vacancy_division_id
                    : ($vacancy->division ?? null));

            $employeeData = [
                'resort_id' => $this->resort->resort_id,
                'title' => $request->gender =='male'? 'Mr.' : 'Ms.',
                'Dept_id'=> $deptId,
                'Section_id' => $request->section,
                'Position_id' => $positionId,
                // Link this employee back to the vacancy they were hired
                // against. Used by /resort/people/employees/create to count
                // "filled" slots and auto-hide fully-filled vacancies.
                'vacancy_id' => $hasVacancyIdCol ? $vacancyId : null,
                'division_id'=> $divisionId,
                'reporting_to'=> $request->reporting_person,
                'is_employee' => 1,
                'rank'=> $request->position_rank,
                'status' => $initial_status,
                'dob' =>$dob ,
                'marital_status' => $request->marital_status,
                'nationality'=> $request->nationality,
                'blood_group'=> $request->blood_group,
                'religion' => $request->religion,
                'joining_date' =>$joining_date ,
                'employment_type' => $request->employment_status,
                'passport_number' => $request->passport_numb,
                'nid' => $request->nid,
                // BUG FIX: the previous concatenation mixed Present + Permanent
                // address parts (used present_addLine1/2 then parmanent_city/state/
                // postal/country). Use the actual present_* fields, collapse empty
                // segments, and trim trailing commas so a sparsely-filled address
                // doesn't end with ", , , ,".
                'present_address' => collect([
                    $request->present_addLine1,
                    $request->present_addLine2,
                    $request->present_city,
                    $request->present_state,
                    $request->present_postal_code,
                    $request->present_country,
                ])->filter(fn($v) => !is_null($v) && trim((string) $v) !== '')->implode(', '),
                'tin' => $request->tin,
                'contract_type'=> $request->contract_type,
                'payment_mode' => $request->payment_mode,
                'probation_end_date' => $probation_end_date,
                'probation_status'   => $probation_status,
                'basic_salary' => $request->basic_salary,
                'basic_salary_currency'=> $request->basic_salary_currency,
                'emg_cont_first_name' => $request->emg_contact_fname,
                'emg_cont_last_name' => $request->emg_contact_lname,
                'emg_cont_email' => $request->emg_contact_email,
                'emg_cont_no' => $request->emg_contact_number,
                'emg_cont_relationship' => $request->emg_contact_relation,
                'emg_cont_nationality' => $request->emg_contact_nationalitys,
                'emg_cont_current_address' => $request->emg_contact_add_addLine1 . ', ' . $request->emg_add_line2 .',' . $request->emg_cont_city.','.$request->emg_cont_state.','.$request->emg_cont_postal_code.','.$request->emg_cont_country,
                'entitled_service_charge' => $request->entitle_service_charge ? 'yes' : 'no',
                'entitled_overtime' => $request->entitle_overtime ? 'yes' : 'no',
                'entitled_public_holiday' => $request->entitle_public_holiday ? 'yes' : 'no',
                'ewt_status' => $request->ewt_status ? 'yes' : 'no',
                'pension' => $request->pension ?? null,
                'benefit_grid_level' => $request->benefit_grid_level ?: null,
            ];

            $profile = Common::persistEmployeeProfile($resortAdminData, $employeeData, $this->resort->resort_id, null);
            $resortAdmin = $profile['resortAdmin'];
            $employee = $profile['employee'];
            $folder_name = $employee->Emp_id;

            if($request->hasFile('cv')){
                $cv = $request->file('cv');
                $aws_cv = Common::AWSEmployeeFileUpload($this->resort->resort_id,$cv,$folder_name);

                if($aws_cv['status'] == 'success'){
                    EmployeesDocument::create([
                        'employee_id' => $employee->id,
                        'resort_id' => $this->resort->resort_id,
                        'document_title' => 'CV',
                        'document_path' => $aws_cv['path'],
                        'document_category' => 'Employement',
                        'document_file_size' => $cv->getSize(),
                        'created_by' => Auth::guard('resort-admin')->user()->id,
                        'modified_by' => Auth::guard('resort-admin')->user()->id,
                    ]);
                }
            }
            // MIME-type allowlist for both image fields. Without this, HR
            // could pick a PDF in the file dialog and the upload would land
            // silently — the column stored a .pdf path and the <img> tag
            // resolved to the default avatar (reported on live for DR-485).
            // Same allowlist the OnBoarding API uses for selfie_image.
            $imageMimeAllow = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'heic', 'heif'];
            $isImageFile = function ($uploaded) use ($imageMimeAllow) {
                if (!$uploaded) return false;
                $ext = strtolower((string) $uploaded->getClientOriginalExtension());
                return in_array($ext, $imageMimeAllow, true);
            };

            if($request->hasFile('full_length_photo')){
                $file_full_length = $request->file('full_length_photo');
                if (!$isImageFile($file_full_length)) {
                    return back()->withErrors([
                        'full_length_photo' => 'Full-length photo must be an image (JPG, PNG, GIF, WebP, HEIC). Got: '
                            . strtoupper((string) $file_full_length->getClientOriginalExtension()),
                    ])->withInput();
                }
                $picture = Common::AWSEmployeeFileUpload($this->resort->resort_id,$file_full_length,$folder_name);

                if($picture['status'] == 'success'){
                    $employee->selfie_image = $picture['path'];
                    $employee->save();
                }
            }
            if($request->hasFile('profile_picture')){
                $file = $request->file('profile_picture');
                if (!$isImageFile($file)) {
                    return back()->withErrors([
                        'profile_picture' => 'Profile picture must be an image (JPG, PNG, GIF, WebP, HEIC). Got: '
                            . strtoupper((string) $file->getClientOriginalExtension()),
                    ])->withInput();
                }
                $profilePicture = Common::AWSEmployeeFileUpload($this->resort->resort_id,$file,$folder_name);

                if($profilePicture['status'] == 'success'){
                    $resortAdmin->profile_picture = $profilePicture['path'];
                    $resortAdmin->save();
                }
            }


            if(!empty($request->language)){
                foreach ($request->language as $lang) {
                    $language = EmployeeLanguage::create([
                        'employee_id' => $employee->id,
                        'language' => $lang[0],
                        'proficiency_level' => $lang[1],
                    ]);
                }
            }

            if(!empty($request->allowance)){
                foreach ($request->allowance as $allowance) {

                    $employeeAllowance = EmployeeAllowance::create([
                        'employee_id' => $employee->id,
                        'allowance_id' => $allowance['type'],
                        'amount' => $allowance['amount'],
                        'amount_unit' => $allowance['currency'],
                    ]);
                }
            }

            if(!empty($request->bank)){
                foreach ($request->bank as $bank) {

                    $EmployeeBankDetails = EmployeeBankDetails::create([
                        'employee_id' => $employee->id,
                        'bank_name' => $bank['bank_name'],
                        'bank_branch' => $bank['bank_branch'],
                        'account_type' => $bank['account_type'],
                        'IFSC_BIC' => $bank['ifsc'],
                        'account_holder_name' => $bank['account_name'],
                        'account_no' => $bank['account_number'],
                        'currency' => $bank['currency'],
                        'IBAN' => $bank['iban'],
                    ]);
                }
            }

            if(!empty($request->education)){
                foreach ($request->education as $edu) {

                    if($edu['document']){
                        $file = $edu['document'];
                        $edu_uploadedFile = Common::AWSEmployeeFileUpload($this->resort->resort_id,$file,$folder_name);
                    }
                    EmployeeEducation::create([
                        'employee_id' => $employee->id,
                        'education_level' => $edu['education_level'],
                        'institution_name' => $edu['institutio_name'],
                        'field_of_study' => $edu['field_study'],
                        'degree' => $edu['degree_earned'],
                        'attendance_period' => $edu['attendance_period'],
                        'location' => $edu['location'],
                        'certification' => $uploadedFile['path'] ?? null,
                    ]);
                }
            }


            if(!empty($request->experience)){
                foreach ($request->experience as $exp) {

                    if( isset($exp['document']) && $exp['document']){
                        $file = $exp['document'];
                        $uploadedFile = Common::AWSEmployeeFileUpload($this->resort->resort_id,$file,$folder_name);
                    }

                    EmployeeExperiance::create([
                        'employee_id' => $employee->id,
                        'company_name' => $exp['company_name'],
                        'job_title' => $exp['job_title'],
                        'employment_type' => $exp['employment_type'],
                        'duration' => $exp['duration'],
                        'location' => $exp['location'],
                        'reason_for_leaving' => $exp['reason_for_leaving'],
                        'reference_name' => $exp['reference_name'],
                        'reference_contact' => $exp['reference_contact'],
                        'document' => $uploadedFile['path'] ?? null,
                    ]);
                }
            }

            // Check salary  Compliance

            // Raw rank=3 (with a resort-wide, department-blind rank=2
            // fallback that could match any HOD, not necessarily HR)
            // excluded this resort's real HR employee. getResortHrEmployeeIds()
            // matches rank=3 anywhere or HR-department rank 1/2 correctly.
            $notify_person = Employee::whereIn('id', Common::getResortHrEmployeeIds($this->resort->resort_id))->first();

            $minWageMVR = 8021; // Minimum wage in MVR
            $minWageUSD = 520; // Minimum wage in USD
                if($employee->basic_salary < $minWageMVR && $employee->basic_salary_currency == 'MVR' || $employee->basic_salary < $minWageUSD && $employee->basic_salary_currency == 'USD') {

                    event(new ResortNotificationEvent(Common::nofitication(
                        $this->resort->resort_id,
                        10,
                        'Workforce Planning Minimum Wage Compliance Breached',
                        "Employee {$employee->resortAdmin->full_name} has a basic salary {$employee->basic_salary} below the minimum wage.",
                        0,
                        $notify_person->id,
                        'Workforce Planning (Minimum Wage)'
                    )));

                    Compliance::firstOrCreate([
                        'resort_id' => $this->resort->resort_id,
                        'employee_id' => $employee->id,
                        'module_name' => 'Workforce Planning',
                        'compliance_breached_name' => 'Minimum Wage',
                        'description' => "Employee {$employee->resortAdmin->full_name} has a basic salary {$employee->basic_salary} below the minimum wage.",
                        'reported_on' => Carbon::now(),
                        'status' => 'Breached'
                    ]);
                }

               $ManningandbudgetingConfigfiles = ManningandbudgetingConfigfiles::where('resort_id', $this->resort->resort_id)->first();

                $xpat = $ManningandbudgetingConfigfiles->xpat;
                $local = $ManningandbudgetingConfigfiles->local;

                // Get counts
                $totalEmployees = Employee::where('resort_id', $this->resort->resort_id)->count();
                $expatCount = Employee::where('resort_id', $this->resort->resort_id)
                        ->where('nationality', '!=', 'Maldivian')
                        ->count();

                $localCount = Employee::where('resort_id', $this->resort->resort_id)
                        ->where('nationality', 'Maldivian')
                        ->count();

                $compliance = null;
                    // Expat-Local Ratio compliance check
                    if ($totalEmployees > 0 && $xpat > 0 && $local > 0) {
                            // Calculate the expected counts based on configured ratio
                            $total_ratio = $xpat + $local;
                            $expected_expat = ceil($totalEmployees * ($xpat / $total_ratio));
                            $expected_local = ceil($totalEmployees * ($local / $total_ratio));

                            // Check if the actual counts violate the expected ratio
                            if ($expatCount > $expected_expat || $localCount < $expected_local) {
                                // Send notification to resort admin
                                event(new ResortNotificationEvent(Common::nofitication(
                                        $this->resort->resort_id,
                                        10,
                                        'Workforce Planning Expat-Local Ratio Compliance Breached',
                                        "Expat count ({$expatCount}) exceeds expected ({$expected_expat}) or Local count ({$localCount}) is below expected ({$expected_local}).",
                                        0,
                                        $notify_person->id,
                                        'Workforce Planning (Expat-Local Ratio)'
                                )));

                                $compliance = Compliance::firstOrCreate([
                                        'resort_id' => $this->resort->resort_id,
                                        'employee_id' => null,
                                        'module_name' => 'Workforce Planning',
                                        'compliance_breached_name' => 'Expat-Local Ratio',
                                        'description' => "Expat count ({$expatCount}) exceeds expected ({$expected_expat}) or Local count ({$localCount}) is below expected ({$expected_local})",
                                        'reported_on' => Carbon::now(),
                                        'status' => 'Breached'
                                ]);
                            }
                    }
                    // Expat-Local Ratio compliance End

                    if($employee->nationality != 'Maldivian'){
                        $ResortPosition = ResortPosition::where('resort_id', $this->resort->resort_id)
                            ->where('id', $employee->Position_id)
                            ->first();
                        if($ResortPosition->is_reserved == 'Yes'){
                            event(new ResortNotificationEvent(Common::nofitication(
                                    $this->resort->resort_id,
                                    10,
                                    'Workforce Planning Reserved Position Compliance Breached',
                                    "Position {$ResortPosition->position_title} is reserved for Local Candidate, but employee {$employee->resortAdmin->full_name} is not Maldivian.",
                                    0,
                                    $notify_person->id,
                                    'Workforce Planning (Reserved Position)'
                            )));

                            $compliance = Compliance::firstOrCreate([
                                    'resort_id' => $this->resort->resort_id,
                                    'employee_id' => null,
                                    'module_name' => 'Workforce Planning (Reserved Position)',
                                    'compliance_breached_name' => 'Reserved Position',
                                    'description' => "Position {$ResortPosition->position_title} is reserved for Local Candidate, but employee {$employee->resortAdmin->full_name} is not Maldivian.",
                                    'reported_on' => Carbon::now(),
                                    'status' => 'Breached'
                            ]);
                        }
                    }

                    $startDate = Carbon::createFromFormat('Y-m-d', $employee->joining_date);
                    $probationMonths = $startDate->diffInMonths($employee->probation_end_date);
                    // Check if probation period is more than 3 months
                    if ($probationMonths > 3 && $employee->employment_type =='Probationary') {
                        Compliance::create([
                            'resort_id' => $this->resort->resort_id,
                            'employee_id' => $employee->id,
                            'module_name' => 'Probation',
                            'compliance_breached_name' => 'Extended Probation Period',
                            'description' => "Probation period for " . $employee->resortAdmin->full_name . "(" . $employee->position->position_title . ') is set to ' . $probationMonths . ' months. Reduce to comply with the 3-month maximum',
                            'reported_on' => Carbon::now(),
                            'status' => 'Breached'
                        ]);

                        event(new ResortNotificationEvent(Common::nofitication(
                            $this->resort->resort_id,
                            10,
                            'Extended Probation Period',
                            "Probation period for " . $probation->resortAdmin->full_name . "(" . $probation->position->position_title . ") is set to " . $probationMonths . " months. Reduce to comply with the 3-month maximum",
                            0,
                            $notify_person->id,
                            'Probation'
                        )));

                    }


            DB::commit();
            Session::forget('employee_form');
            // JS reads `redirect_url` — the old `redirect` key was
            // undefined on the client, so window.location.href became
            // `undefined` and the browser fell back to a relative path
            // that landed on the super-admin route. Target the People
            // dashboard explicitly.
            return response()->json([
                'success'      => true,
                'status'       => 'success',
                'message'      => 'Employee created successfully!',
                'redirect_url' => route('people.hr.dashboard'),
            ]);
       }catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Error creating employee: ' . $e->getMessage(),
            ]);
        }
    }

    public function details($id)
    {
        $page_title ='Employee Details';
        $resort_id = $this->resort->resort_id;
        $scopedDeptIds = Common::getScopedDepartmentIds();
        $departments = ResortDepartment::where('resort_id',$resort_id)
            ->where('status','active')
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('id', $scopedDeptIds))
            ->get();
        $positions = ResortPosition::where('resort_id',$resort_id)->where('status','active')->get();
        $sections = ResortSection::where('resort_id',$resort_id)->where('status','active')->get();
        $resort_divisions = ResortDivision::where('resort_id',$resort_id)->where('status','active')->get();
        $resort_allowances = ResortBudgetCost::where('resort_id', $resort_id)->where('is_payroll_allowance',1)->get();
        $employee = Employee::with(['resortAdmin','position','department','division','section','education','experiance','allowance','language','sosTeams','document','bankDetails','reportingTo.resortAdmin'])
            ->where('id',base64_decode($id))
            ->where('resort_id', $resort_id)
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))
            ->first();
        if (!$employee) {
            return abort(403, 'You do not have access to this employee.');
        }
        $emp_benigit_grid = Common::getBenefitGrid($employee->position->Rank,$this->resort->resort_id);
        $benefitGrids = ResortBenifitGrid::where('resort_id',$this->resort->resort_id)->where('status','active')->get();

        // ------------------------------------------------------------------
        // Leaves Remaining (canonical recipe — mirrors LeaveController::apply
        // around lc-join + per-category balance). The previous implementation
        // summed `allocated_days` across every benefit-grid child row, which
        // included allowance/perk rows (R&R, language allowance, etc.) — that
        // inflated the badge to absurd numbers like 604. The leave module
        // counts only rows that join to `leave_categories` via `leave_cat_id`,
        // honors gender/religion eligibility, excludes Absent/Present/DayOff,
        // and uses per-category `max(0, allocated - used)` (so unused days in
        // one category can't be cancelled out by overuse in another).
        // ------------------------------------------------------------------
        $currentYearStart = Carbon::now()->startOfYear()->format('Y-m-d');
        $currentYearEnd = Carbon::now()->endOfYear()->format('Y-m-d');
        $empGender = strtolower((string) ($employee->resortAdmin->gender ?? ''));
        $empReligion = strtolower((string) ($employee->religion ?? ''));
        $empRank = $employee->rank ?? $employee->position->Rank ?? null;
        $empGrade = $emp_benigit_grid->emp_grade ?? $employee->position->Rank ?? null;
        $empGridId = $emp_benigit_grid->id ?? null;
        $excludedLeaveTypes = ['Absent', 'Present', 'DayOff'];

        if (!$empGridId) {
            $remianing_leaves = 0;
        } else {
            $leaveCategoryRows = DB::table('resort_benefit_grid_child as rbgc')
                ->join('leave_categories as lc', 'lc.id', '=', 'rbgc.leave_cat_id')
                ->where('rbgc.benefit_grid_id', $empGridId)
                ->where('rbgc.rank', $empGrade)
                ->where('rbgc.allocated_days', '>', 0)
                ->where('lc.resort_id', $this->resort->resort_id)
                ->whereNotIn('lc.leave_type', $excludedLeaveTypes)
                ->when($empRank !== null, function ($q) use ($empRank) {
                    $q->whereRaw('FIND_IN_SET(?, lc.eligibility)', [$empRank]);
                })
                ->where(function ($q) use ($empGender, $empReligion) {
                    $q->where('rbgc.eligible_emp_type', 'all');
                    if ($empGender !== '') {
                        $q->orWhere('rbgc.eligible_emp_type', $empGender);
                    }
                    if ($empReligion === 'muslim') {
                        $q->orWhere('rbgc.eligible_emp_type', 'muslim');
                    }
                })
                ->select('rbgc.allocated_days', 'rbgc.leave_cat_id')
                ->get();

            $usedByCat = DB::table('employees_leaves')
                ->select('leave_category_id', DB::raw('SUM(total_days) as used_days'))
                ->where('emp_id', $employee->id)
                ->where('status', 'Approved')
                ->where(function ($query) use ($currentYearStart, $currentYearEnd) {
                    $query->whereBetween('from_date', [$currentYearStart, $currentYearEnd])
                        ->orWhereBetween('to_date', [$currentYearStart, $currentYearEnd]);
                })
                ->groupBy('leave_category_id')
                ->pluck('used_days', 'leave_category_id');

            $remianing_leaves = (int) $leaveCategoryRows->sum(function ($row) use ($usedByCat) {
                $allocated = (int) ($row->allocated_days ?? 0);
                $used = (int) ($usedByCat[$row->leave_cat_id] ?? 0);
                return max(0, $allocated - $used);
            });
        }
        $teams = SOSTeamManagementModel::where('resort_id',$resort_id)->get();
        $roles = SOSRolesAndPermission::where('resort_id',$resort_id)->get();
        $nationality = config('settings.nationalities');
        if($employee->nationality != "Maldivian")
        {
            $costs = ResortBudgetCost::where('resort_id', $resort_id)->where('cost_title','Operational Cost')->whereIn('details',['Both','Xpat Only'])->get();
        }
        else
        {
            $costs = ResortBudgetCost::where('resort_id', $resort_id)->where('cost_title','Operational Cost')->whereIn('details',['Both','Locals Only'])->get();
        }

        $ResortSiteSettings = ResortSiteSettings::where('resort_id', $resort_id)->first();

        // Convert basic salary and allowances to MVR
        $conversionRate = $ResortSiteSettings ? ($ResortSiteSettings->DollertoMVR ?? 15.42) : 15.42;
        $basicMvr = $employee->basic_salary_currency === 'USD' ? $employee->basic_salary * $conversionRate : $employee->basic_salary;
        $totalAllowanceMvr = 0;
        foreach ($employee->allowance as $allowance)
        {
            $amt = $allowance->amount ?? 0;
            $unit = $allowance->amount_unit ?? 'USD';
            $totalAllowanceMvr += $unit === 'USD' ? ($amt * $conversionRate) : $amt;
        }
            $totalMonthlyEarningMvr = $basicMvr + $totalAllowanceMvr;
            $tin = $employee->tin ?? null;

            // Raw rank=3 (with a resort-wide, department-blind rank=2
            // fallback) excluded this resort's real HR employee.
            $notify_person = Employee::whereIn('id', Common::getResortHrEmployeeIds($resort_id))->first();
            if($totalMonthlyEarningMvr >= 30000 && !$tin)
            {
                // event(new ResortNotificationEvent(Common::nofitication(
                //     $this->resort->resort_id,
                //     10,
                //     'TIN Required for Employee',
                //     "{$employee->resortAdmin->full_name} ({$employee->Emp_id} - {$employee->position->position_title}) (RSWT: MVR {$totalMonthlyEarningMvr}/month) not registered. Submit MIRA 118 form.",
                //     0,
                //     $notify_person->id,
                //     'People Management (TIN Requirement)'
                // )));

                Compliance::firstOrCreate([
                    'resort_id' => $this->resort->resort_id,
                    'employee_id' => $employee->id,
                    'module_name' => 'People Management',
                    'compliance_breached_name' => 'TIN Requirement',
                    'description' => "{$employee->resortAdmin->full_name} ({$employee->Emp_id} - {$employee->position->position_title}) (RSWT: MVR {$totalMonthlyEarningMvr}/month) not registered. Submit MIRA 118 form.",
                    'reported_on' => Carbon::now(),
                    'status' => 'Breached'
                ]);
            }

            $minWageMVR = 8021; // Minimum wage in MVR
            $minWageUSD = 520; // Minimum wage in MVR
            // Check if the current employee's salary is below minimum wage
            $isBelowMinWage = false;

            if ($employee->basic_salary_currency == 'MVR' && $employee->basic_salary < $minWageMVR) {
                $isBelowMinWage = true;
            } elseif
                ($employee->basic_salary_currency == 'USD' && $employee->basic_salary < $minWageUSD) {
                $isBelowMinWage = true;
            }

            if ($isBelowMinWage) {
                // Create compliance record if employee's salary is below minimum wage
                Compliance::firstOrCreate([
                    'resort_id' => $this->resort->resort_id,
                    'employee_id' => $employee->id,
                    'module_name' => 'People Management',
                    'compliance_breached_name' => 'Minimum Wage',
                    'description' => "Employee {$employee->resortAdmin->full_name} has a basic salary below the minimum wage.",
                    'reported_on' => Carbon::now(),
                    'status' => 'Breached'
                ]);

                // Send notification
                // event(new ResortNotificationEvent(Common::nofitication(
                //     $this->resort->resort_id,
                //     10,
                //     'People Management Minimum Wage Compliance Breached',
                //     "Employee {$employee->resortAdmin->full_name} has a basic salary {$employee->basic_salary} {$employee->basic_salary_currency} below the minimum wage.",
                //     0,
                //     $notify_person->id,
                //     'People Management (Minimum Wage)'
                // )));
            }

        $airports = config('airports', ['national' => [], 'international' => []]);

        // ------------------------------------------------------------------
        // Recent Activities — the previous markup showed 3 hardcoded
        // "Lorem ipsum" cards on every employee's profile. Replace with the
        // employee's 3 most-recent real activities, drawn from the modules
        // that actually have application/approval flows: leaves,
        // promotions, salary increments. Each entry exposes a uniform
        // {title, subtitle, status, badge_class} shape so the blade can
        // iterate without per-type branches.
        // ------------------------------------------------------------------
        $badgeFor = function ($status) {
            $s = strtolower((string) $status);
            if (in_array($s, ['approved', 'completed', 'paid'])) return 'badge-themeSuccess';
            if (in_array($s, ['rejected', 'cancelled', 'canceled'])) return 'badge-themeDanger';
            if (in_array($s, ['hold', 'on hold', 'paused', 'in progress'])) return 'badge-themeSkyblue';
            return 'badge-themeWarning'; // Pending / unknown
        };

        $recentActivities = collect();

        // Last few leaves for this employee
        $leaveRows = DB::table('employees_leaves as el')
            ->leftJoin('leave_categories as lc', 'lc.id', '=', 'el.leave_category_id')
            ->where('el.emp_id', $employee->id)
            ->orderByDesc('el.id')
            ->limit(3)
            ->get(['el.id', 'el.from_date', 'el.to_date', 'el.status', 'el.created_at', 'lc.leave_type']);
        foreach ($leaveRows as $r) {
            $from = $r->from_date ? Carbon::parse($r->from_date)->format('d M Y') : '—';
            $to   = $r->to_date   ? Carbon::parse($r->to_date)->format('d M Y')   : '—';
            $recentActivities->push((object) [
                'title'       => ($r->leave_type ?: 'Leave') . ' Request',
                'subtitle'    => 'From ' . $from . ' to ' . $to,
                'status'      => $r->status ?: 'Pending',
                'badge_class' => $badgeFor($r->status),
                'when'        => $r->created_at,
            ]);
        }

        // Last promotion for this employee
        if (Schema::hasTable('employee_promotions')) {
            $promo = DB::table('employee_promotions')
                ->where('employee_id', $employee->id)
                ->orderByDesc('id')
                ->first(['id', 'status', 'effective_date', 'created_at']);
            if ($promo) {
                $eff = $promo->effective_date ? Carbon::parse($promo->effective_date)->format('d M Y') : null;
                $recentActivities->push((object) [
                    'title'       => 'Promotion',
                    'subtitle'    => $eff ? ('Effective ' . $eff) : 'Effective date pending',
                    'status'      => $promo->status ?: 'Pending',
                    'badge_class' => $badgeFor($promo->status),
                    'when'        => $promo->created_at,
                ]);
            }
        }

        // Last salary increment for this employee
        if (Schema::hasTable('people_salary_increment')) {
            $inc = DB::table('people_salary_increment')
                ->where('employee_id', $employee->id)
                ->orderByDesc('id')
                ->first(['id', 'status', 'effective_date', 'increment_amount', 'increment_type', 'created_at']);
            if ($inc) {
                $eff = $inc->effective_date ? Carbon::parse($inc->effective_date)->format('d M Y') : null;
                $recentActivities->push((object) [
                    'title'       => 'Salary Increment',
                    'subtitle'    => $eff ? ('Effective ' . $eff) : 'Awaiting effective date',
                    'status'      => $inc->status ?: 'Pending',
                    'badge_class' => $badgeFor($inc->status),
                    'when'        => $inc->created_at,
                ]);
            }
        }

        // Latest Employment-tab edit (from the audit log we now maintain)
        if (Schema::hasTable('employee_employment_audit_logs')) {
            $log = DB::table('employee_employment_audit_logs')
                ->where('employee_id', $employee->id)
                ->orderByDesc('id')
                ->first(['id', 'label', 'field', 'new_value', 'created_at']);
            if ($log) {
                $recentActivities->push((object) [
                    'title'       => 'Employment Update',
                    'subtitle'    => ($log->label ?: $log->field) . ($log->new_value ? ' → ' . $log->new_value : ''),
                    'status'      => 'Saved',
                    'badge_class' => 'badge-themeSuccess',
                    'when'        => $log->created_at,
                ]);
            }
        }

        // Latest resignation (Exit Clearance flow)
        if (Schema::hasTable('employee_resignation')) {
            $resig = DB::table('employee_resignation')
                ->where('employee_id', $employee->id)
                ->orderByDesc('id')
                ->first(['id', 'status', 'last_working_day', 'created_at']);
            if ($resig) {
                $lwd = $resig->last_working_day ? Carbon::parse($resig->last_working_day)->format('d M Y') : null;
                $recentActivities->push((object) [
                    'title'       => 'Resignation Submitted',
                    'subtitle'    => $lwd ? ('Last working day ' . $lwd) : 'Last working day pending',
                    'status'      => $resig->status ?: 'Pending',
                    'badge_class' => $badgeFor($resig->status),
                    'when'        => $resig->created_at,
                ]);
            }
        }

        // Latest expiry document edit / upload
        if (Schema::hasTable('employees_documents')) {
            $doc = DB::table('employees_documents')
                ->where('employee_id', $employee->id)
                ->orderByDesc('id')
                ->first(['id', 'document_title', 'expiry_date', 'updated_at', 'created_at']);
            if ($doc) {
                $exp = $doc->expiry_date ? Carbon::parse($doc->expiry_date)->format('d M Y') : null;
                $recentActivities->push((object) [
                    'title'       => 'Document: ' . ($doc->document_title ?: 'Untitled'),
                    'subtitle'    => $exp ? ('Expires ' . $exp) : 'Uploaded',
                    'status'      => $exp ? 'Tracked' : 'Uploaded',
                    'badge_class' => 'badge-themeSuccess',
                    'when'        => $doc->updated_at ?: $doc->created_at,
                ]);
            }
        }

        // Onboarding milestone — every employee has a created_at, so this
        // guarantees Recent Activities is never empty for any employee.
        // Label reflects the current lifecycle state so it doesn't
        // contradict the status badge: if HR hasn't clicked "Activate
        // Employee" yet, the employee is still in 'Onboarding' and the
        // activity must read as pending, not "Completed". Once activated
        // (status flips to Active / Probationary), the milestone reads
        // "Onboarded — Completed" as before.
        if (!empty($employee->created_at)) {
            $joinDate = $employee->joining_date
                ? Carbon::parse($employee->joining_date)->format('d M Y')
                : Carbon::parse($employee->getRawOriginal('created_at') ?: $employee->created_at)->format('d M Y');
            $isOnboarding = $employee->status === 'Onboarding';
            $recentActivities->push((object) [
                'title'       => $isOnboarding ? 'Onboarding' : 'Onboarded',
                'subtitle'    => ($isOnboarding ? 'Joining ' : 'Joined on ') . $joinDate,
                'status'      => $isOnboarding ? 'Pending Activation' : 'Completed',
                'badge_class' => $isOnboarding ? 'badge-themeWarning' : 'badge-themeSuccess',
                'when'        => $employee->getRawOriginal('created_at') ?: $employee->created_at,
            ]);
        }

        // Sort by most-recent created_at and keep top 3 across all sources.
        // `when` comes from 6 different tables' created_at/updated_at — a
        // historical row with a malformed value (seen in prod as literal
        // "14/08/2026 15:24", a d/m/Y string in a column Carbon expects
        // Y-m-d in) must not 500 the whole page; treat it as oldest instead.
        $recentActivities = $recentActivities
            ->sortByDesc(function ($a) {
                if (!$a->when) {
                    return 0;
                }
                try {
                    return Carbon::parse($a->when)->timestamp;
                } catch (\Throwable $e) {
                    return 0;
                }
            })
            ->take(3)
            ->values();

        // Visa / Work-Permit expiries synced from the Xpat module, so the
        // Expiry tab surfaces the same data the Visa module already holds
        // instead of only the manually-uploaded employees_documents rows.
        $xpatExpiries = $this->getXpatExpiries($employee->id, $resort_id);

        // Travel Quota tab: per-employee total_allowed per transportation
        // category the resort has enabled (resort_transportations), plus
        // this year's approved usage — same "Approved" + current-year-window
        // recipe leaveDashboard() uses for leave-balance usage, so the web
        // tab and the mobile API agree on what "used" means.
        $transportationOptions = ResortTransportation::where('resort_id', $resort_id)->get();
        $travelQuotas = EmployeeTravelQuota::where('employee_id', $employee->id)->get()->keyBy('transportation');
        $travelYearStart = Carbon::now()->startOfYear()->format('Y-m-d');
        $travelYearEnd = Carbon::now()->endOfYear()->format('Y-m-d');
        $travelUsage = DB::table('employee_travel_passes')
            ->select('transportation', DB::raw('COUNT(*) as used_count'))
            ->where('employee_id', $employee->id)
            ->where('status', 'Approved')
            ->where(function ($q) use ($travelYearStart, $travelYearEnd) {
                $q->whereBetween('arrival_date', [$travelYearStart, $travelYearEnd])
                  ->orWhereBetween('departure_date', [$travelYearStart, $travelYearEnd]);
            })
            ->groupBy('transportation')
            ->get()
            ->keyBy('transportation');

        // Surfaces the mobile-submitted request (if any) on the button below
        // so HR has context on what they're fulfilling — the button itself
        // works either way, HR can also issue this letter proactively.
        $pendingEmploymentVerificationRequest = PayrollAdvance::where('resort_id', $resort_id)
            ->where('employee_id', $employee->id)
            ->where('request_type', 'Employment Verification Letter')
            ->where('hr_status', 'Pending')
            ->latest('id')
            ->first();

        return view('resorts.people.employee.detail',compact('page_title','conversionRate','teams','roles','resort_id','resort_divisions','employee','departments','positions','remianing_leaves','nationality','benefitGrids','sections','costs','emp_benigit_grid','resort_allowances','airports','recentActivities','xpatExpiries','transportationOptions','travelQuotas','travelUsage','pendingEmploymentVerificationRequest'));
    }

    /**
     * HR-triggered "Generate & Send Employment Verification Letter" for an
     * ACTIVE employee, from their own details page. Deliberately NOT built on
     * top of ProbationController::sendProbationLetter() — that method has
     * probation-only side effects (flips employee->status to 'Offboarding',
     * creates an exit-clearance record) that would corrupt an active
     * employee's record if invoked with type=experience. Also not built on
     * ExitClearanceController::employementCertificate() — that requires an
     * EmployeeResignation row active employees don't have. Mirrors the
     * placeholder/PDF/email shape of both, adapted for an active employee
     * (duration of service = joining date -> today, not -> last working day).
     */
    public function sendEmploymentVerificationLetter(Request $request, $id)
    {
        $resort_id = $this->resort->resort_id;
        $employee = Employee::with(['resortAdmin','position','department'])
            ->where('resort_id', $resort_id)
            ->where('id', $id)
            ->first();
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found.'], 404);
        }

        $template = ProbationLetterTemplate::where('resort_id', $resort_id)
            ->where('type', 'experience')
            ->first();
        if (!$template) {
            return response()->json(['success' => false, 'message' => 'Experience/Employment Letter template not found for this resort. Please create one in People > Configuration.'], 404);
        }

        $resort = Resort::findOrFail($resort_id);

        $duration = '—';
        if ($employee->joining_date) {
            $jd = Carbon::parse($employee->joining_date);
            $now = Carbon::now();
            if ($now->greaterThanOrEqualTo($jd)) {
                $diff = $jd->diff($now);
                $parts = [];
                if ($diff->y > 0) $parts[] = $diff->y . ' year' . ($diff->y === 1 ? '' : 's');
                if ($diff->m > 0) $parts[] = $diff->m . ' month' . ($diff->m === 1 ? '' : 's');
                if (empty($parts)) $parts[] = max(1, $diff->d) . ' day' . ($diff->d === 1 ? '' : 's');
                $duration = implode(' ', $parts);
            }
        }

        $joiningDate = $employee->joining_date ? Carbon::parse($employee->joining_date)->format('d M Y') : '—';
        $issueDate = now()->format('d M Y');

        $placeholders = [
            // The seeded experience template literally uses {{date}} for BOTH
            // "Date of Joining:" and "Issued Date:" (same token, twice) — no
            // {{joining_date}} token appears in the content at all, so no
            // code-side mapping can make those two lines show different
            // values. Matches ExitClearanceController::employementCertificate's
            // own documented trade-off: {{date}} = joining date (the one that
            // actually matters for an employment-verification letter); HR can
            // fix "Issued Date" by editing the template in People >
            // Configuration to use {{issue_date}} there instead, which this
            // code already maps correctly.
            '{{date}}'                => $joiningDate,
            '{{issue_date}}'          => $issueDate,
            '{{resort_name}}'         => (string) $resort->resort_name,
            '{{employee_name}}'       => (string) optional($employee->resortAdmin)->full_name,
            '{{employee_code}}'       => (string) $employee->Emp_id,
            '{{Emp_id}}'              => (string) $employee->Emp_id,
            '{{position_title}}'      => (string) optional($employee->position)->position_title,
            '{{position}}'            => (string) optional($employee->position)->position_title,
            '{{Department_title}}'    => (string) optional($employee->department)->name,
            '{{department_name}}'     => (string) optional($employee->department)->name,
            '{{employment_type}}'     => (string) ($employee->employment_type ?? ''),
            '{{joining_date}}'        => $joiningDate,
            '{{last_working_day}}'    => '—',
            '{{date_of_separation}}'  => '—',
            '{{duration_of_service}}' => $duration,
        ];

        // Same stand-in text the seeded experience template ships with (no
        // {{token}} for it, just raw text) — see ExitClearanceController's
        // employementCertificate() for precedent.
        $letterContent = str_replace('[As per employment records]', $duration, $template->content);
        $letterContent = strtr($letterContent, $placeholders);

        $defaultEmailBody = '<p>Dear {{employee_name}},</p>'
            . '<p>Please find your ' . e($template->subject ?? 'Employment Verification Letter') . ' attached.</p>'
            . '<p>Regards,<br>{{resort_name}} HR</p>';
        $emailBodyTemplate = !empty($template->email_body) ? $template->email_body : $defaultEmailBody;
        $emailBody = strtr($emailBodyTemplate, $placeholders);

        $letterhead = Common::getLetterheadData($resort_id);
        $pdf = Pdf::loadView('resorts.people.probation.probation_letter_pdf', [
            'letterContent'  => $letterContent,
            'letterhead'     => $letterhead,
            'resort'         => $resort,
            'resortLogo'     => Common::GetResortLogo($resort_id),
            'signatureImage' => $letterhead['signatureImage'] ?? null,
            'signatoryName'  => ($letterhead['signatoryName'] ?? null) ?: 'Human Resources Department',
            'signatoryTitle' => ($letterhead['signatoryTitle'] ?? null)
                ?: 'For and on behalf of ' . ($resort->resort_name ?? 'the Management'),
        ])->setPaper('a4', 'portrait');
        $pdf->getDomPDF()->getOptions()->set('isRemoteEnabled', true);

        $fileName = 'employment-verification_' . $employee->id . '_' . time() . '.pdf';
        $pdfPath = storage_path('app/' . $fileName);
        $pdf->save($pdfPath);

        if (!file_exists($pdfPath)) {
            \Log::error("Employment verification letter PDF not found at $pdfPath");
            return response()->json(['success' => false, 'message' => 'Letter PDF could not be generated.'], 500);
        }

        Mail::to($employee->resortAdmin->email)->send(new ProbationLetterMail($employee, $pdfPath, 'experience', $resort, $fileName, $emailBody));

        $pendingRequest = PayrollAdvance::where('resort_id', $resort_id)
            ->where('employee_id', $employee->id)
            ->where('request_type', 'Employment Verification Letter')
            ->where('hr_status', 'Pending')
            ->latest('id')
            ->first();
        if ($pendingRequest) {
            $pendingRequest->hr_status = 'Approved';
            $pendingRequest->save();
        }

        Common::sendMobileNotification(
            $resort_id,
            2,
            null,
            null,
            'Employment Verification Letter',
            'Your Employment Verification Letter has been sent to your email.',
            'Request',
            [$employee->id],
            $pendingRequest->id ?? null,
            false,
            'employment-verification-ready'
        );

        return response()->json(['success' => true, 'message' => 'Employment Verification Letter sent to ' . $employee->resortAdmin->email . ' successfully.']);
    }

    /**
     * Collect the visa/work-permit expiry dates for an employee from the Xpat
     * module's tables (same sources as the Expiry Tracker / Xpat details page).
     * Returns a list of ['label','date','status'] — date is 'd M Y' or null.
     */
    private function getXpatExpiries($employeeId, $resortId): array
    {
        $fmt = function ($date) {
            if (empty($date)) {
                return [null, null];
            }
            try {
                $end = Carbon::parse($date);
            } catch (\Exception $e) {
                return [null, null];
            }
            $days = Carbon::today()->diffInDays($end, false);
            $status = $days < 0
                ? 'Expired ' . abs($days) . ' days ago'
                : 'Expires in ' . $days . ' days';
            return [$end->format('d M Y'), $status];
        };

        // OCR blob ("Other" doc) — work permit expiry, same field the Xpat
        // details page reads.
        $ocrWpExpiry = null;
        $ocrRow = \App\Models\VisaEmployeeExpiryData::where('resort_id', $resortId)
            ->where('employee_id', $employeeId)->where('DocumentName', 'Other')->latest('id')->first();
        if ($ocrRow) {
            $blob = is_array($ocrRow->Ai_extracted_data) ? $ocrRow->Ai_extracted_data : json_decode($ocrRow->Ai_extracted_data, true);
            $ocrWpExpiry = $blob['extracted_fields']['Work Permit Expiry Date (Expiry On)'] ?? null;
        }

        // Passport expiry from the OCR Passport_Copy doc.
        $passportExpiry = null;
        $passRow = \App\Models\VisaEmployeeExpiryData::where('resort_id', $resortId)
            ->where('employee_id', $employeeId)->where('DocumentName', 'Passport_Copy')->latest('id')->first();
        if ($passRow) {
            $blob = is_array($passRow->Ai_extracted_data) ? $passRow->Ai_extracted_data : json_decode($passRow->Ai_extracted_data, true);
            $passportExpiry = $blob['extracted_fields']['Date of Expiry'] ?? ($blob['extracted_fields']['Passport Expiry Date'] ?? null);
        }

        $visa      = \App\Models\VisaRenewal::where('resort_id', $resortId)->where('employee_id', $employeeId)->latest('id')->first();
        $insurance = \App\Models\EmployeeInsurance::where('resort_id', $resortId)->where('employee_id', $employeeId)->latest('id')->first();
        $slot      = \App\Models\QuotaSlotRenewal::where('resort_id', $resortId)->where('employee_id', $employeeId)->latest('id')->first();
        $medical   = \App\Models\WorkPermitMedicalRenewal::where('resort_id', $resortId)->where('employee_id', $employeeId)->latest('id')->first();

        $rows = [
            ['Passport', $passportExpiry],
            ['Visa', $visa->end_date ?? null],
            ['Work Permit', $ocrWpExpiry],
            ['Slot Payment', $slot->Due_Date ?? null],
            ['Insurance', $insurance->insurance_end_date ?? null],
            ['Work Permit Medical', $medical->end_date ?? null],
        ];

        $out = [];
        foreach ($rows as [$label, $date]) {
            [$formatted, $status] = $fmt($date);
            $out[] = ['label' => $label, 'date' => $formatted, 'status' => $status];
        }
        return $out;
    }

    public function assignToTeam(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'emp_id' => 'required|exists:employees,id',
            'team_id' => 'required|exists:sos_teams,id',
            'role_id' => 'required|exists:sos_role_management,id'
        ]);

        $resortId = Auth::guard('resort-admin')->user()->resort_id;
        $employee = Employee::with('resortAdmin')
                            ->where('id', $request->emp_id)
                            ->where('resort_id', $resortId)
                            ->first();

        $alreadyExists = SOSTeamMemeberModel::where('emp_id', $employee->Admin_Parent_id)
                            ->where('team_id', $request->team_id)
                            ->where('resort_id', $resortId)
                            ->exists();

        if ($alreadyExists) {
            return response()->json(['status' => 'error', 'message' => 'This employee is already a member of the selected SOS team.']);
        }

        SOSTeamMemeberModel::create([
            'resort_id' => $resortId,
            'emp_id' => $employee->Admin_Parent_id,
            'team_id' => $request->team_id,
            'role_id' => $request->role_id
        ]);

        return response()->json(['status' => 'success', 'message' => 'Employee assigned to SOS Team successfully!']);
    }

    public function changeStatus(Request $request)
    {
        $request->validate([
            'emp_id' => ['required', Rule::exists('employees', 'id')->where('resort_id', $this->resort->resort_id)],
            'status' => 'required|in:Active,Onboarding,Probationary,Inactive,Terminated,Resigned,On Leave,Suspended'
        ]);


        $employee = Employee::where('resort_id', $this->resort->resort_id)->find($request->emp_id);
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found.'], 404);
        }
        $oldStatus = $employee->status;
        ResortAdmin::where('id', $employee->Admin_Parent_id)
            ->update(['status' => $request->status]);
        $employee->status = $request->status;
        $employee->save();

        // Employment-status changes (activate/deactivate/terminate) had no
        // notification anywhere — HR and the employee's own HOD found out
        // only by noticing it in the list.
        if ($oldStatus !== $request->status) {
            try {
                $recipients = Common::getResortHrEmployeeIds($this->resort->resort_id);
                $hod = Common::FindResortHODDepartment($this->resort->resort_id, $employee->Dept_id);
                if ($hod) {
                    $recipients[] = $hod->id;
                }
                Common::notifyEmployees(
                    $this->resort->resort_id,
                    $recipients,
                    'Employee Status Changed',
                    "Status for {$employee->Emp_id} changed from {$oldStatus} to {$request->status}.",
                    'People Management',
                    $employee->id
                );
            } catch (\Exception $e) {
                \Log::warning('Employee status change notification failed: ' . $e->getMessage());
            }
        }

        return response()->json(['success' => true]);
    }

    public function sendCredentials(Request $request)
    {
        try {

            $employee = Employee::with('resortAdmin')->where('resort_id', $this->resort->resort_id)->find($request->employee_id);

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found.'
                ], 404);
            }

            if (!$employee->resortAdmin || !$employee->resortAdmin->email) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee does not have a valid email address.'
                ]);
            }

            $plainPassword = Common::generateUniquePassword(8);
            $hashedPassword = Hash::make($plainPassword);

            $resortAdmin = $employee->resortAdmin;
            $resortAdmin->password = $hashedPassword;
            $resortAdmin->save();
            // Hydrate GetEmployee so the notification template can read
            // Emp_id for the mobile-app login section of the email.
            $resortAdmin->setRelation('GetEmployee', $employee);
            $resortAdmin->sendResortemployee($this->resort->resort, $resortAdmin, $plainPassword);

            return response()->json([
                'success' => true,
                'message' => 'Credentials sent successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send credentials: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Inline update of employee location only (from the summary panel pencil icon).
     */
    public function updateLocation(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'location'    => 'nullable|in:Malé,Resorts',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }
        $employee = Employee::where('resort_id', $this->resort->resort_id)->findOrFail($request->employee_id);
        $employee->location = $request->location ?: null;
        $employee->save();
        return response()->json(['success' => true, 'message' => 'Location updated', 'location' => $employee->location]);
    }

    /**
     * Update an existing employee's profile picture (and optionally the
     * full-length / selfie image). Before this existed, the only write
     * path was the create flow — once an employee was created, the
     * profile photo was effectively immutable. Reported on live for
     * DR-485 where HR couldn't replace a wrongly-uploaded PDF without
     * re-creating the employee. MIME validation matches the create flow.
     */
    public function updateProfilePicture(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'employee_id'      => 'required|exists:employees,id',
            'profile_picture'  => 'nullable|file|mimes:jpg,jpeg,png,gif,svg,webp,heic,heif',
            'full_length_photo'=> 'nullable|file|mimes:jpg,jpeg,png,gif,svg,webp,heic,heif',
        ], [
            'profile_picture.mimes'   => 'Profile picture must be an image (JPG, PNG, GIF, WebP, HEIC).',
            'full_length_photo.mimes' => 'Full-length photo must be an image (JPG, PNG, GIF, WebP, HEIC).',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }
        if (!$request->hasFile('profile_picture') && !$request->hasFile('full_length_photo')) {
            return response()->json(['success' => false, 'message' => 'Pick at least one file to upload.'], 422);
        }

        $employee = Employee::with('resortAdmin')
            ->where('resort_id', $this->resort->resort_id)
            ->findOrFail($request->employee_id);

        // Use the employee's existing categorised folder so the new file
        // lands alongside their other documents (matches the create flow).
        $fileManagement = FilemangementSystem::where('resort_id', $this->resort->resort_id)
            ->where('Folder_Name', $employee->Emp_id)
            ->where('Folder_Type', 'categorized')
            ->first();
        if (!$fileManagement) {
            $fileManagement = Common::createFolderByName($this->resort->resort_id, $employee->Emp_id, 'categorized');
        }
        $folder_name = $fileManagement->Folder_Name;

        if ($request->hasFile('profile_picture') && $employee->resortAdmin) {
            $upload = Common::AWSEmployeeFileUpload($this->resort->resort_id, $request->file('profile_picture'), $folder_name);
            if (($upload['status'] ?? '') === 'success') {
                $employee->resortAdmin->profile_picture = $upload['path'];
                $employee->resortAdmin->save();
            } else {
                return response()->json(['success' => false, 'message' => 'Profile picture upload failed.'], 500);
            }
        }
        if ($request->hasFile('full_length_photo')) {
            $upload = Common::AWSEmployeeFileUpload($this->resort->resort_id, $request->file('full_length_photo'), $folder_name);
            if (($upload['status'] ?? '') === 'success') {
                $employee->selfie_image = $upload['path'];
                $employee->save();
            } else {
                return response()->json(['success' => false, 'message' => 'Full-length photo upload failed.'], 500);
            }
        }

        return response()->json([
            'success'         => true,
            'message'         => 'Profile picture updated.',
            'profile_picture' => Common::getResortUserPicture($employee->Admin_Parent_id),
        ]);
    }

    /**
     * Activate an employee who is in the pre-joining 'Onboarding' state.
     *
     * Called from the "Activate Employee" action on the profile once HR has
     * completed onboarding. Sets the joining date and flips the status to
     * 'Active' — only then does the employee surface in Payroll, Attendance
     * and headcount.
     */
    public function activate(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'emp_id'       => 'required|exists:employees,id',
            'joining_date' => ['required', 'date_format:d/m/Y'],
        ], [
            'joining_date.required'    => 'Joining date is required.',
            'joining_date.date_format' => 'Joining date must be in dd/mm/yyyy format.',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $employee = Employee::where('resort_id', $this->resort->resort_id)
            ->findOrFail($request->emp_id);

        // Only an Onboarding employee can be "activated" via this action.
        if ($employee->status !== 'Onboarding') {
            return response()->json([
                'success' => false,
                'message' => 'This employee is not in the Onboarding state.',
            ], 422);
        }

        $employee->joining_date = \Carbon\Carbon::createFromFormat('d/m/Y', $request->joining_date)->format('Y-m-d');
        // After activation, lifecycle status branches on employment type.
        //   • Probationary employees go straight to 'Probationary' so the
        //     Probation module list and the lifecycle badge both reflect
        //     the in-probation state. probation_status was already set to
        //     'Active' during create, so they appear in the Probation
        //     listing immediately.
        //   • Everyone else moves to 'Active' as before.
        $employee->status = ($employee->employment_type === 'Probationary')
            ? 'Probationary'
            : 'Active';
        $employee->save();

        try {
            $recipients = Common::getResortHrEmployeeIds($this->resort->resort_id);
            $hod = Common::FindResortHODDepartment($this->resort->resort_id, $employee->Dept_id);
            if ($hod) {
                $recipients[] = $hod->id;
            }
            Common::notifyEmployees(
                $this->resort->resort_id,
                $recipients,
                'Employee Activated',
                "{$employee->Emp_id} was activated and moved to {$employee->status} status.",
                'People Management',
                $employee->id
            );
        } catch (\Exception $e) {
            \Log::warning('Employee activation notification failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => $employee->status === 'Probationary'
                ? 'Employee activated. They are now in the Probation module and will show in Payroll / Attendance.'
                : 'Employee activated. They will now appear in Payroll and Attendance.',
        ]);
    }

    public function updatePersonal(Request $request)
    {
        $formattedDOB = \Carbon\Carbon::createFromFormat('d/m/Y', $request->dob)->format('Y-m-d');

        $employee = Employee::where('resort_id', $this->resort->resort_id)->findOrFail($request->employee_id);
        $employee->title = $request->title;
        $employee->dob = $formattedDOB;
        $employee->marital_status = $request->marital_status;
        $employee->nationality = $request->nationality;
        if ($request->has('location')) {
            $employee->location = $request->location ?: null;
        }
        $employee->religion = $request->religion;
        $employee->blood_group = $request->blood_group;
        $employee->passport_number = $request->passport_number;
        $employee->nid = $request->nid;
        $employee->save();

        // Update name and gender in resortAdmin
        $employee->resortAdmin->first_name = $request->first_name;
        $employee->resortAdmin->middle_name = $request->middle_name;
        $employee->resortAdmin->last_name = $request->last_name;
        $employee->resortAdmin->gender = $request->gender;
        $employee->resortAdmin->save();

        return response()->json(['success' => true ,'message' => "Personal Details Updated!"]);
    }

    public function updateContacts(Request $request)
    {

        $employee = Employee::where('resort_id', $this->resort->resort_id)->findOrFail($request->employee_id);

        // Snapshot the audit-loggable fields BEFORE save so we can diff
        // them after. Previously this endpoint silently updated
        // personal_phone / email with no audit row — so HR saw the
        // Mobile Number change land in the DB but get no entry in the
        // Employment Change Log. updateEmploymentData has the same
        // writer wired in, but the Personal Details tab calls THIS
        // endpoint, not that one.
        $oldSnapshot = [
            'email'          => optional($employee->resortAdmin)->email,
            'personal_phone' => optional($employee->resortAdmin)->personal_phone,
        ];

        $employee->present_address = $request->present_address;
        $employee->save();
        // Update name and gender in resortAdmin
        $employee->resortAdmin->personal_phone = $request->personal_phone;
        $employee->resortAdmin->email = $request->email;
        $employee->resortAdmin->address_line_1 = $request->address_line_1;
        $employee->resortAdmin->address_line_2 = $request->address_line_2;
        $employee->resortAdmin->city = $request->city;
        $employee->resortAdmin->state = $request->state;
        $employee->resortAdmin->country = $request->country;
        $employee->resortAdmin->zip = $request->zip;

        $employee->resortAdmin->save();

        $this->writeEmploymentAuditLog($employee, $oldSnapshot, [
            'email'          => $request->email,
            'personal_phone' => $request->personal_phone,
        ]);

        return response()->json(['success' => true ,'message' => "Contacts Details Updated!"]);
    }

    public function updateEmergencyContacts(Request $request)
    {

        $employee = Employee::where('resort_id', $this->resort->resort_id)->findOrFail($request->employee_id);
        $employee->emg_cont_first_name = $request->emg_cont_first_name;
        $employee->emg_cont_last_name = $request->emg_cont_last_name;
        $employee->emg_cont_no = $request->emg_cont_no;
        $employee->emg_cont_relationship = $request->emg_cont_relationship;
        $employee->emg_cont_email = $request->emg_cont_email;
        $employee->emg_cont_current_address = $request->emg_cont_current_address;
        $employee->save();

        return response()->json(['success' => true ,'message' => "Emegency Contacts Details Updated!"]);
    }

    public function updateAdditionalInfo(Request $request)
    {
        $request->validate([
            'leave_destination' => 'nullable|string|max:255',
            'biometric_file' => 'nullable|file|mimes:jpg,jpeg,png,gif,svg,webp,heic,heif,pdf',
            'languages.*.language' => 'required|string',
            'languages.*.proficiency_level' => 'required|string',
        ]);

        $employee = Employee::where('resort_id', $this->resort->resort_id)->findOrFail($request->employee_id);
        $employee->leave_destination = $request->leave_destination;

        $encodedEmployeeID = base64_encode($request->employee_id);
        // $baseUploadPath = config('settings.employee_biometrics');
        // $uploadPath = $baseUploadPath . '/' . $encodedEmployeeID;

        // if (!file_exists(public_path($uploadPath))) {
        //     mkdir(public_path($uploadPath), 0755, true);
        // }
        $folderName = FilemangementSystem::where('resort_id', $this->resort->resort_id)
            ->where('Folder_Name', $employee->Emp_id)
            ->where('Folder_Type', 'categorized')
            ->first();


        if ($request->hasFile('biometric_file')) {

            $file = $request->file('biometric_file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $aws = Common::AWSEmployeeFileUpload($this->resort->resort_id, $file, $folderName->Folder_Name);
            if($aws['status'] == true){
                $employee->biometric_file = $aws['path'];
            }
        }

        $employee->save();

        // Save languages
        if ($request->has('languages')) {
            // Delete old ones first (optional, depending on logic)
            $employee->language()->delete();

            foreach ($request->languages as $lang) {
                $employee->language()->create([
                    'language' => $lang['language'],
                    'proficiency_level' => $lang['proficiency_level'],
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Additional Information updated successfully.',
        ]);
    }

    public function updateEmploymentData(Request $request)
    {
        // TIN format validation. Maldives MIRA TINs are 10 digits, with a
        // 1-letter prefix (commonly "A" or "B") in some encodings. We
        // accept either 10 digits, or a single letter followed by 10
        // digits, and trim whitespace. Empty TIN is allowed (only required
        // when EWT Status is Enrolled — that gate is already enforced
        // client-side and on the request below). Server-side validation
        // exists so the rule still bites if a user submits via API or
        // bypasses the JS.
        $tin = trim((string) $request->input('tin', ''));
        if ($tin !== '' && !preg_match('/^[A-Za-z]?\d{10}$/', $tin)) {
            return response()->json([
                'success' => false,
                'message' => 'TIN must be 10 digits (Maldives MIRA format), optionally prefixed by a single letter.',
            ], 422);
        }
        // EWT enrolled → TIN required. Mirrors the client gate at
        // detail.blade.php:2700+, so a forged submit that strips the
        // disabled state is still rejected at the server.
        if ((string) $request->input('ewt_status') === '1' && $tin === '') {
            return response()->json([
                'success' => false,
                'message' => 'TIN is required when EWT Status is Enrolled.',
            ], 422);
        }

        $formattedJoinDate = $request->joining_date ? \Carbon\Carbon::createFromFormat('d/m/Y', $request->joining_date)->format('Y-m-d') : null;
        $formattedProbationEndDate = $request->probation_end_date ? \Carbon\Carbon::createFromFormat('d/m/Y', $request->probation_end_date)->format('Y-m-d') : null;
        $formattedTerminationDate = $request->termination_date ? \Carbon\Carbon::createFromFormat('d/m/Y', $request->termination_date)->format('Y-m-d') : null;


        $position = ResortPosition::where('resort_id', $this->resort->resort_id)->find($request->Position_id);
        $employee = Employee::where('resort_id', $this->resort->resort_id)->findOrFail($request->employee_id);

        if (!$position) {
            return response()->json([
                'success' => false,
                'message' => 'Position not found.',
            ], 422);
        }

        // Resolve the grade using the level HR is actively choosing on this
        // same form (falls back to the position's rank default when no
        // valid override is set) — previously resolved from rank alone,
        // before benefit_grid_level was even set from the request below, so
        // the level being picked here never actually affected the
        // entitlement flags computed from it.
        $grade = Common::resolveEmpGrade($this->resort->resort_id, $position->Rank, $request->benefit_grid_level);

        $benefitGrid = ResortBenifitGrid::where('resort_id', $this->resort->resort_id)
            ->where('emp_grade', $grade)
            ->where('status', 'active')
            ->first();

        // ---------- Capture old values for the audit log -----------------
        // Snapshot the existing field values BEFORE mutating the model so
        // the audit-log diff sees real before/after pairs. Resolves
        // FK ids to human-readable labels (Position / Department / etc.)
        // so the log table doesn't show raw integers.
        $oldSnapshot = [
            'status'             => $employee->status,
            'joining_date'       => $employee->joining_date,
            'benefit_grid_level' => $employee->benefit_grid_level,
            'tin'                => $employee->tin,
            'probation_end_date' => $employee->probation_end_date,
            'contract_type'      => $employee->contract_type,
            'termination_date'   => $employee->termination_date,
            'Position_id'        => optional($employee->position)->position_title,
            'Section_id'         => optional($employee->section)->section_title ?? optional($employee->section)->name,
            'Dept_id'            => optional($employee->department)->name,
            'division_id'        => optional($employee->division)->name,
            'reporting_to'       => optional(optional($employee->reportingTo)->resortAdmin)->full_name,
            'email'              => optional($employee->resortAdmin)->email,
            'personal_phone'     => optional($employee->resortAdmin)->personal_phone,
        ];

        $employee->status = $request->status;
        $employee->joining_date = $formattedJoinDate;
        $employee->benefit_grid_level = $request->benefit_grid_level;
        $employee->tin = $request->tin;
        $employee->probation_end_date = $formattedProbationEndDate;
        $employee->contract_type = $request->contract_type;
        $employee->termination_date = $formattedTerminationDate;
        $employee->Position_id = $request->Position_id;
        $employee->Section_id = $request->Section_id;
        $employee->Dept_id = $request->Dept_id;
        $employee->division_id = $request->division_id;
        $employee->reporting_to = $request->reporting_to ? $request->reporting_to : null;

        if( $benefitGrid)
        {
            $employee->entitled_service_charge = $benefitGrid->service_charge == 1 ? 'yes' : 'no';
            // employees.entitled_overtime is a strict enum('yes','no') — the
            // benefit grid's own overtime field is 'yes'/'n/a', so copying it
            // verbatim wrote an invalid value (silently coerced to '' by
            // MySQL) that never matched DutyRosterController's `== "no"`
            // check. That's why setting a grade's Overtime to "Not
            // Applicable" never blocked overtime entries on the roster.
            $employee->entitled_overtime = $benefitGrid->overtime == 'yes' ? 'yes' : 'no';
            $employee->entitled_public_holiday = $benefitGrid->paid_worked_public_holiday_and_friday == 1 ? 'yes' : 'no';
            $employee->entitled_annual_leave_ticket = $benefitGrid->annual_leave_ticket == 'yes' ? 'yes' : 'no';

            // basic_salary_currency has a DB default ('USD') rather than a
            // true null/unset state, so an employee who's never had their
            // salary configured yet still reads as 'USD'. Only sync the
            // grid's Salary Paid In while basic_salary is still empty —
            // once a real salary has been entered via the dedicated
            // salary/entitlements screen, that currency choice was
            // deliberate and a later grid reassignment must not silently
            // flip it under existing pay data.
            if (empty($employee->basic_salary) && in_array($benefitGrid->salary_paid_in, ['USD', 'MVR'])) {
                $employee->basic_salary_currency = $benefitGrid->salary_paid_in;
            }
        }
        $employee->save();

        // Update name and gender in resortAdmin
        $employee->resortAdmin->email = $request->email;
        $employee->resortAdmin->personal_phone = $request->personal_phone;
        $employee->resortAdmin->save();

        // ---------- Write audit log rows for every changed field --------
        $newPosition = ResortPosition::where('resort_id', $this->resort->resort_id)->find($request->Position_id);
        $newSection  = $request->Section_id ? ResortSection::where('resort_id', $this->resort->resort_id)->find($request->Section_id) : null;
        $newDept     = $request->Dept_id ? ResortDepartment::where('resort_id', $this->resort->resort_id)->find($request->Dept_id) : null;
        $newDivision = $request->division_id ? ResortDivision::where('resort_id', $this->resort->resort_id)->find($request->division_id) : null;
        $newReporter = $request->reporting_to
            ? Employee::with('resortAdmin')->where('resort_id', $this->resort->resort_id)->find($request->reporting_to)
            : null;

        $newSnapshot = [
            'status'             => $request->status,
            'joining_date'       => $formattedJoinDate,
            'benefit_grid_level' => $request->benefit_grid_level,
            'tin'                => $request->tin,
            'probation_end_date' => $formattedProbationEndDate,
            'contract_type'      => $request->contract_type,
            'termination_date'   => $formattedTerminationDate,
            'Position_id'        => $newPosition->position_title ?? null,
            'Section_id'         => $newSection->section_title ?? $newSection->name ?? null,
            'Dept_id'            => $newDept->name ?? null,
            'division_id'        => $newDivision->name ?? null,
            'reporting_to'       => optional(optional($newReporter)->resortAdmin)->full_name,
            'email'              => $request->email,
            'personal_phone'     => $request->personal_phone,
        ];

        $this->writeEmploymentAuditLog($employee, $oldSnapshot, $newSnapshot);

        return response()->json(['success' => true ,'message' => "Employment data Updated!"]);
    }

    /**
     * Per-field diff → one audit log row per changed field.
     *
     * The Employee-column diff (status, joining_date, Position_id, basic_salary,
     * etc.) is now handled by EmployeeEmploymentAuditObserver, which captures
     * the same fields from EVERY mutation path (Promotion approval, Salary
     * Increment apply, Activate, etc.) — not just this controller. This
     * method only needs to handle the columns the observer can't see:
     * email + personal_phone, which live on the related ResortAdmin row.
     */
    private function writeEmploymentAuditLog(Employee $employee, array $old, array $new): void
    {
        $labels = [
            'email'          => 'Email Address',
            'personal_phone' => 'Mobile Number',
        ];

        $admin = Auth::guard('resort-admin')->user();
        $changedById = $admin->id ?? null;

        foreach ($labels as $field => $label) {
            $oldVal = $old[$field] ?? null;
            $newVal = $new[$field] ?? null;

            $normalize = fn($v) => $v === null ? '' : trim((string) $v);
            if ($normalize($oldVal) === $normalize($newVal)) {
                continue;
            }

            \App\Models\EmployeeEmploymentAuditLog::create([
                'resort_id'   => $this->resort->resort_id,
                'employee_id' => $employee->id,
                'field'       => $field,
                'label'       => $label,
                'old_value'   => $oldVal !== null ? (string) $oldVal : null,
                'new_value'   => $newVal !== null ? (string) $newVal : null,
                'changed_by'  => $changedById,
            ]);
        }
    }

    /**
     * Paginated audit-log feed for the Employment tab. Renders a
     * partial HTML block (table rows + pagination) consumed via AJAX
     * from detail.blade.php so the page doesn't need to ship every
     * log row at first render.
     */
    public function employmentLogs(Request $request)
    {
        $employeeId = (int) $request->employee_id;
        if (!$employeeId) {
            return response()->json(['success' => false, 'html' => '']);
        }

        $logs = \App\Models\EmployeeEmploymentAuditLog::with('changedByAdmin')
            ->where('resort_id', $this->resort->resort_id)
            ->where('employee_id', $employeeId)
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'page', (int) ($request->page ?? 1));

        $html = view('resorts.people.employee._partials.employment-logs', compact('logs'))->render();

        return response()->json(['success' => true, 'html' => $html]);
    }

    public function updateSalary(Request $request)
    {
        DB::beginTransaction();

        try {
            $employeeId = $request->input('employee_id');
            $conversionRate = 15.42; // USD to MVR
            $basicSalary = floatval($request->input('basic_salary'));
            $basicSalaryCurrency = $request->input('basic_salary_currency', 'MVR');

            // Convert to MVR for pension & EWT check
            $basicSalaryInMVR = $basicSalaryCurrency === 'USD' ? $basicSalary * $conversionRate : $basicSalary;

            // Calculate total allowances in MVR
            $allowances = $request->input('allowances', []);
            $totalAllowanceMVR = 0;

            foreach ($allowances as $allowance) {
                $amount = floatval($allowance['amount']);
                $unit = $allowance['amount_unit'] ?? 'USD';
                $totalAllowanceMVR += $unit === 'USD' ? $amount * $conversionRate : $amount;
            }

            $totalEarningMVR = $basicSalaryInMVR + $totalAllowanceMVR;
            $ewtEligible = $totalEarningMVR >= 30000;

            // Calculate pension: 7% of basic salary (in MVR)
            $pensionFinal = round(($basicSalaryInMVR * 0.07), 2);

            // Update employee
            $employee = Employee::where('resort_id', $this->resort->resort_id)->findOrFail($employeeId);
            $oldBasicSalary = $employee->basic_salary;
            $oldBasicSalaryCurrency = $employee->basic_salary_currency;
            $employee->basic_salary = $basicSalary;
            $employee->basic_salary_currency = $basicSalaryCurrency;
            $employee->payment_mode = $request->input('payment_mode');
            $employee->pension = $pensionFinal;
            $employee->ewt = $request->input('ewt');
            $employee->entitled_service_charge = $request->input('entitle_service_charge') ? 'yes' : 'no';
            $employee->entitled_overtime = $request->input('entitle_overtime') ? 'yes' : 'no';
            $employee->entitled_public_holiday = $request->input('entitle_public_holiday') ? 'yes' : 'no';
            $employee->ewt_status = $request->input('ewt_status') ? 'yes' : 'no';
            $employee->save();

            // Save/update allowances
            foreach ($allowances as $allowance) {
                $allowanceId = $allowance['type'];
                $amount = $allowance['amount'];
                $amountUnit = $allowance['amount_unit'] ?? 'USD';

                $existing = EmployeeAllowance::where('employee_id', $employeeId)
                    ->where('allowance_id', $allowanceId)
                    ->first();

                if ($existing) {
                    $existing->update([
                        'amount' => $amount,
                        'amount_unit' => $amountUnit,
                    ]);
                } else {
                    EmployeeAllowance::create([
                        'employee_id' => $employeeId,
                        'allowance_id' => $allowanceId,
                        'amount' => $amount,
                        'amount_unit' => $amountUnit,
                    ]);
                }
            }

            DB::commit();

            // Direct salary edit bypasses the SalaryIncrement approval chain
            // entirely, so HR would otherwise have no record this happened.
            if ((float) $oldBasicSalary !== (float) $basicSalary || $oldBasicSalaryCurrency !== $basicSalaryCurrency) {
                try {
                    Common::notifyEmployees(
                        $this->resort->resort_id,
                        Common::getResortHrEmployeeIds($this->resort->resort_id),
                        'Employee Salary Updated',
                        "Basic salary for {$employee->Emp_id} changed from {$oldBasicSalaryCurrency} {$oldBasicSalary} to {$basicSalaryCurrency} {$basicSalary}.",
                        'People Management',
                        $employee->id
                    );
                } catch (\Exception $e) {
                    \Log::warning('Employee salary update notification failed: ' . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'ewt_eligible' => $ewtEligible,
                'ewt_enrolled' => !empty($employee->tin), // TIN number is proof of enrollment
                'total_earning_mvr' => $totalEarningMVR,
                'tin_no' => $employee->tin,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }


    public function updateBankDetails(Request $request, $id)
    {
        // Find the education record by ID
        $bank_details = EmployeeBankDetails::whereHas('employee', function ($q) {
                $q->where('resort_id', $this->resort->resort_id);
            })->find($id);

        if (!$bank_details) {
            return response()->json([
                'status' => false,
                'message' => 'Bank details not found.'
            ], 404);
        }

        // Update the education record
        $bank_details->bank_name = $request->input('bank_name');
        $bank_details->bank_branch = $request->input('bank_branch');
        $bank_details->account_type = $request->input('account_type');
        $bank_details->IFSC_BIC = $request->input('IFSC_BIC');
        $bank_details->account_holder_name = $request->input('account_holder_name');
        $bank_details->account_no = $request->input('account_no');
        $bank_details->currency = $request->input('currency');
        $bank_details->IBAN = $request->input('IBAN');

        $bank_details->save();

        // Fraud vector: bank detail changes had zero HR/Finance alert. Keep
        // the account number/IBAN/etc out of the message — never put
        // sensitive financial data in a notification row/push.
        try {
            $empIdentifier = optional($bank_details->employee)->Emp_id ?? $bank_details->employee_id;
            Common::notifyEmployees(
                $this->resort->resort_id,
                array_merge(
                    Common::getResortHrEmployeeIds($this->resort->resort_id),
                    Common::getResortFinanceEmployeeIds($this->resort->resort_id)
                ),
                'Employee Bank Details Updated',
                "Bank details were updated for employee {$empIdentifier}.",
                'People Management',
                $bank_details->employee_id
            );
        } catch (\Exception $e) {
            \Log::warning('Bank details update notification failed: ' . $e->getMessage());
        }

        // Return a success JSON response
        return response()->json([
            'status' => true,
            'message' => 'Bank details updated successfully.'
        ]);
    }

    public function addBankDetails(Request $request)
    {
        $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('resort_id', $this->resort->resort_id)],
            'bank_name' => 'required|string|max:255',
            'bank_branch' => 'required|string|max:255',
            'account_type' => 'nullable|string|max:255',
            'IFSC_BIC' => 'nullable|string|max:255',
            'account_holder_name' => 'nullable|string|max:255',
            'account_no' => 'nullable|string|max:255',
            'currency' => 'nullable|string|max:255',
            'IBAN' => 'nullable|string|max:255',

        ]);

        $bank_details = new EmployeeBankDetails();
        $bank_details->bank_name = $request->bank_name;
        $bank_details->employee_id = $request->employee_id;
        $bank_details->bank_branch = $request->bank_branch;
        $bank_details->account_type = $request->account_type;
        $bank_details->IFSC_BIC = $request->IFSC_BIC;
        $bank_details->account_holder_name = $request->account_holder_name;
        $bank_details->account_no = $request->account_no;
        $bank_details->currency = $request->currency;
        $bank_details->IBAN = $request->IBAN;

        $bank_details->save();

        try {
            $empIdentifier = optional($bank_details->employee)->Emp_id ?? $bank_details->employee_id;
            Common::notifyEmployees(
                $this->resort->resort_id,
                array_merge(
                    Common::getResortHrEmployeeIds($this->resort->resort_id),
                    Common::getResortFinanceEmployeeIds($this->resort->resort_id)
                ),
                'Employee Bank Details Added',
                "Bank details were added for employee {$empIdentifier}.",
                'People Management',
                $bank_details->employee_id
            );
        } catch (\Exception $e) {
            \Log::warning('Bank details add notification failed: ' . $e->getMessage());
        }

        return response()->json([
            'status' => true,
            'message' => 'Bank details added successfully.'
        ]);
    }


    public function updateEducationDetails(Request $request, $id)
    {
        // Find the education record by ID
        $education = EmployeeEducation::whereHas('employee', function ($q) {
                $q->where('resort_id', $this->resort->resort_id);
            })->find($id);

        if (!$education) {
            return response()->json([
                'status' => false,
                'message' => 'Education record not found.'
            ], 404);
        }

        // Update the education record
        $education->education_level = $request->input('education_level');
        $education->institution_name = $request->input('institution_name');
        $education->field_of_study = $request->input('field_of_study');
        $education->degree = $request->input('degree');
        $education->attendance_period = $request->input('attendance_period');
        $education->location = $request->input('location');

        $encodedEmployeeID = base64_encode($education->employee_id);
        // $baseUploadPath = config('settings.employee_certificates');
        // $uploadPath = $baseUploadPath . '/' . $encodedEmployeeID;

        // if (!file_exists(public_path($uploadPath))) {
        //     mkdir(public_path($uploadPath), 0755, true);
        // }

        // if ($request->hasFile('certification')) {
        //     $file = $request->file('certification'); // ✅ Fix: define $file
        //     $fileName = time() . '_' . $file->getClientOriginalName();
        //     $filePath = Common::uploadFile($file, $fileName, $uploadPath);
        //     $education->certification = $filePath;
        // }
        $folderName = FilemangementSystem::where('resort_id', $this->resort->resort_id)
            ->where('Folder_Name', $education->employee_id)
            ->where('Folder_Type', 'categorized')
            ->first();

        if ($request->hasFile('certification')) {
            $file = $request->file('certification'); // ✅ Fix: define $file
            $fileName = time() . '_' . $file->getClientOriginalName();
            $aws = Common::AWSEmployeeFileUpload($this->resort->resort_id, $file, $folderName->Folder_Name);
            if($aws['status'] == true){
                $education->certification = $filePath;
            }
        }

        $education->save();

        // Return a success JSON response
        return response()->json([
            'status' => true,
            'message' => 'Education details updated successfully.'
        ]);
    }

    public function addEducationDetails(Request $request)
    {
        $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('resort_id', $this->resort->resort_id)],
            'education_level' => 'required|string|max:255',
            'institution_name' => 'required|string|max:255',
            'field_of_study' => 'nullable|string|max:255',
            'degree' => 'nullable|string|max:255',
            'attendance_period' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png,gif,svg,webp,heic,heif|max:2048',
        ]);

        $education = new EmployeeEducation();
        $education->employee_id = $request->employee_id;
        $education->education_level = $request->education_level;
        $education->institution_name = $request->institution_name;
        $education->field_of_study = $request->field_of_study;
        $education->degree = $request->degree;
        $education->attendance_period = $request->attendance_period;
        $education->location = $request->location;

        // Handle certificate file upload
        // $encodedEmployeeID = base64_encode($request->employee_id);
        // $baseUploadPath = config('settings.employee_certificates');
        // $uploadPath = $baseUploadPath . '/' . $encodedEmployeeID;

        // if (!file_exists(public_path($uploadPath))) {
        //     mkdir(public_path($uploadPath), 0755, true);
        // }

        // if ($request->hasFile('certification')) {
        //     $file = $request->file('certification'); // ✅ Fix: define $file
        //     $fileName = time() . '_' . $file->getClientOriginalName();
        //     $filePath = Common::uploadFile($file, $fileName, $uploadPath);
        //     $education->certification = $filePath;
        // }

        $folderName = FilemangementSystem::where('resort_id', $this->resort->resort_id)
            ->where('Folder_Name', $education->employee_id)
            ->where('Folder_Type', 'categorized')
            ->first();

        if ($request->hasFile('certification')) {
            $file = $request->file('certification'); // ✅ Fix: define $file
            $fileName = time() . '_' . $file->getClientOriginalName();

            $aws = Common::AWSEmployeeFileUpload($this->resort->resort_id, $file, $folderName->Folder_Name);
            if($aws['status'] == true){
                $education->certification = $aws['path'];
            }
        }

        $education->save();

        return response()->json([
            'status' => true,
            'message' => 'Education details added successfully.'
        ]);
    }

    public function updateExperianceDetails(Request $request, $id)
    {
        // Find the education record by ID
        $exp = EmployeeExperiance::whereHas('employee', function ($q) {
                $q->where('resort_id', $this->resort->resort_id);
            })->find($id);

        if (!$exp) {
            return response()->json([
                'status' => false,
                'message' => 'Experiance record not found.'
            ], 404);
        }

        // Update the education record
        $exp->company_name = $request->input('company_name');
        $exp->job_title = $request->input('job_title');
        $exp->employment_type = $request->input('employment_type');
        $exp->duration = $request->input('duration');
        $exp->location = $request->input('location');
        $exp->reason_for_leaving = $request->input('reason_for_leaving');
        $exp->reference_name = $request->input('reference_name');
        $exp->reference_contact = $request->input('reference_contact');

        $exp->save();

        // Return a success JSON response
        return response()->json([
            'status' => true,
            'message' => 'Experiance details updated successfully.'
        ]);
    }

    public function addExperianceDetails(Request $request)
    {
        $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('resort_id', $this->resort->resort_id)],
            'company_name' => 'required|string|max:255',
            'job_title' => 'required|string|max:255',
            'employment_type' => 'nullable|string|max:255',
            'duration' => 'nullable|string|max:255',
            'reason_for_leaving' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'reference_name' => 'nullable|string|max:255',
            'reference_contact' => 'nullable|string|max:255',
        ]);

        $exp = new EmployeeExperiance();
        $exp->employee_id = $request->employee_id;
        $exp->company_name = $request->company_name;
        $exp->job_title = $request->job_title;
        $exp->employment_type = $request->employment_type;
        $exp->duration = $request->duration;
        $exp->reason_for_leaving = $request->reason_for_leaving;
        $exp->location = $request->location;
        $exp->reference_name = $request->reference_name;
        $exp->reference_contact = $request->reference_contact;
        $exp->save();

        return response()->json([
            'status' => true,
            'message' => 'Experiance details added successfully.'
        ]);
    }

    public function updateExpiryDocuments(Request $request)
    {
        $documentIds = $request->document_ids;
        if (empty($documentIds)) {
            return response()->json(['success' => false, 'message' => 'No documents provided.'], 501);
        }
        $documentTitles = $request->document_titles;
        $expiryDates = $request->expiry_dates;

        foreach ($documentIds as $index => $docId) {
            $document = EmployeesDocument::where('resort_id', $this->resort->resort_id)->find($docId);
            if ($document) {
                $document->document_title = $documentTitles[$index];
                $document->expiry_date = \Carbon\Carbon::createFromFormat('d/m/Y', $expiryDates[$index])->format('Y-m-d');
                $document->save();
            }
        }

        return response()->json(['success' => true, 'message' => 'Documents updated successfully.']);
    }

    public function updateTravelQuota(Request $request)
    {
        $employeeId = $request->employee_id;
        $transportationIds = $request->transportation_ids;
        if (empty($employeeId) || empty($transportationIds)) {
            return response()->json(['success' => false, 'message' => 'No travel quota provided.'], 501);
        }
        $totalAllowed = $request->total_allowed;
        $resortId = $this->resort->resort_id;

        $employee = Employee::where('id', $employeeId)->where('resort_id', $resortId)->first();
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found.'], 501);
        }

        foreach ($transportationIds as $index => $transportationId) {
            EmployeeTravelQuota::updateOrCreate(
                ['employee_id' => $employeeId, 'transportation' => $transportationId],
                ['resort_id' => $resortId, 'total_allowed' => (int) ($totalAllowed[$index] ?? 0)]
            );
        }

        return response()->json(['success' => true, 'message' => 'Travel quota updated successfully.']);
    }


    public function extractDetails(Request $request){

        $request->validate([
            'document' => 'required|mimes:pdf,jpg,jpeg,png,gif,svg,webp,heic,heif|max:20480',
        ]);

        $file = $request->file('document');
        if (!$file->isValid()) {
            return response()->json(['success' => false, 'message' => 'The document failed to upload.'], 400);
        }
        $flag = $request->doc_type;
        $url = config('services.ai_extract.base_url').'extract_education_exp_details?doc_type='.$flag;
        if($flag)
        {
            $curl = curl_init();
            $postFields = [
                'file' => new \CURLFile($file->getRealPath(), $file->getMimeType(), $file->getClientOriginalName()),
                'doc_type' => $flag,
            ];
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postFields,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                ],
            ]);
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if($err)
            {
                return response()->json(['success' => false, 'message' =>  $err]);
            }
            $AI_Data = json_decode($response, true);
        }
        else
        {
            $AI_Data = $response;
        }
        return response()->json(['success' => true, 'data' => $AI_Data ?? null]);
    }


    public function saveStep(Request $request)
    {
        try {

            $step = $request->step;
            if($step == 1){
                Session::forget('employee_form');
            }
            $sessionData = $request->except('step', '_token');

            $existingData = Session::get('employee_form', []);
            $existingData[$step] = $sessionData;

            Session::put('employee_form', $existingData);

            return response()->json(['success' => true, 'message' => 'Step data saved successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getDraft(Request $request){
        $step = $request->step;
        $sessionData = Session::get('employee_form', []);

        if (isset($sessionData[$step])) {
            return response()->json(['success' => true, 'data' => $sessionData[$step]]);
        }
        return response()->json(['success' => false, 'message' => 'No data found for this step.']);
    }

    public function getDepartmentByDivision(Request $request){
        $divisionId = $request->division_id;
        $departments = ResortDepartment::where('resort_id', $this->resort->resort_id)
            ->where('division_id', $divisionId)
            ->where('status', 'active')
            ->get();
        return response()->json(['success' => true, 'departments' => $departments]);
    }

    public function getSectionByDepartment(Request $request){

        $departmentId = $request->department_id;
        $sections = ResortSection::where('resort_id', $this->resort->resort_id)
            ->where('dept_id', $departmentId)
            ->where('status', 'active')
            ->get();
        return response()->json(['success' => true, 'sections' => $sections]);
    }

    public function getPositionBySection(Request $request){

        if(!$request->has('section_id') || empty($request->section_id)){
            $departmentId = $request->department_id;
            $positions = ResortPosition::where('resort_id', $this->resort->resort_id)
                ->where('dept_id', $departmentId)
                ->where('status', 'active')
                ->get();
            return response()->json(['success' => true, 'positions' => $positions]);
        }else{
            $sectionId = $request->section_id;
            $positions = ResortPosition::where('resort_id', $this->resort->resort_id)
                ->where('section_id', $sectionId)
                ->where('status', 'active')
                ->get();
            return response()->json(['success' => true, 'positions' => $positions]);
        }
    }

    public function getBenefitGridByPosition(Request $request){

        $positionId = $request->position_id;
        $position = ResortPosition::where('resort_id', $this->resort->resort_id)->find($positionId);

        if (!$position) {
            return response()->json(['success' => false, 'message' => 'Position not found.'], 404);
        }

        // A rank can now map to more than one grade (e.g. "HOD L1" and
        // "HOD L2" both rank=HOD) — return every active grid that matches,
        // not just one. orderBy('id') keeps the first/default entry stable
        // (oldest grade for this rank), matching Common::getEmpGrade()'s own
        // fallback order.
        $matchingGradeIds = \App\Models\ResortBenefitGradeLevelRank::where('resort_id', $this->resort->resort_id)
            ->where('rank', $position->Rank)
            ->orderBy('id')
            ->pluck('grade_level_id');

        $grids = ResortBenifitGrid::where('resort_id', $this->resort->resort_id)
            ->where('status', 'active')
            ->whereIn('emp_grade', $matchingGradeIds)
            ->get()
            ->sortBy(fn ($g) => array_search($g->emp_grade, $matchingGradeIds->all()))
            ->values();

        if ($grids->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No benefit grid found for this position.'], 404);
        }

        $first = $grids->first();
        return response()->json([
            'success' => true,
            // Back-compat single-grid fields, populated from the first
            // (default) match — unchanged shape/values for every rank that
            // only ever has one grid.
            'benfitGrid_emp_id' => $first->emp_grade,
            'position_rank' => $position->Rank,
            'emp_grade_name' => optional(\App\Models\ResortBenefitGradeLevel::find($first->emp_grade))->name,
            'service' => $first->service_charge == 1 ? 'yes' : 'no',
            'overtime' => $first->overtime,
            'holiday_overtime' => $first->paid_worked_public_holiday_and_friday == 1 ? 'yes' : 'no',
            // Full option list — the JS renders these as real <option>s and
            // only auto-selects when there's exactly one.
            'options' => $grids->map(fn ($g) => [
                'emp_grade' => $g->emp_grade,
                'name' => optional(\App\Models\ResortBenefitGradeLevel::find($g->emp_grade))->name,
                'service' => $g->service_charge == 1 ? 'yes' : 'no',
                'overtime' => $g->overtime,
                'holiday_overtime' => $g->paid_worked_public_holiday_and_friday == 1 ? 'yes' : 'no',
            ])->values(),
        ]);
    }

    /**
     * Look up entitlements directly by the Benefit Grid Level (emp_grade).
     * Used when HR overrides the auto-selected level on the create form —
     * the entitle-for-service-charge / OT / holiday-OT switches must follow
     * whatever level is currently picked, not the original position default.
     */
    public function getBenefitGridByLevel(Request $request){
        $grade = $request->benefit_grid_level;
        if (!$grade) {
            return response()->json(['success' => false, 'message' => 'Benefit grid level is required.'], 422);
        }
        $benefitGrid = ResortBenifitGrid::where('resort_id', $this->resort->resort_id)
            ->where('status', 'active')
            ->where('emp_grade', $grade)
            ->first();
        if (!$benefitGrid) {
            return response()->json(['success' => false, 'message' => 'No benefit grid configured for this level.'], 404);
        }
        return response()->json([
            'success'         => true,
            'emp_grade_name'  => optional(\App\Models\ResortBenefitGradeLevel::find($benefitGrid->emp_grade))->name,
            'service'         => $benefitGrid->service_charge == 1 ? 'yes' : 'no',
            'overtime'        => $benefitGrid->overtime,
            'holiday_overtime'=> $benefitGrid->paid_worked_public_holiday_and_friday == 1 ? 'yes' : 'no',
        ]);
    }


    public function getReportingPerson(Request $request){

        $reportingEmployees = Common::getValidReportingManagers(
            $this->resort->resort_id,
            $request->rank,
            $request->department_id,
            $request->employee_id
        );

        return response()->json(['success' => true, 'data' => $reportingEmployees]);

    }

    public function delete(Request $request)
    {
        $employee = Employee::where('resort_id', $this->resort->resort_id)->find($request->id);

        if (!$employee) {
            return response()->json(['message' => 'Employee not found.'], 404);
        }

        // Update status to inactive instead of deleting, and set deleted_at timestamp
        $employee->status = 'Inactive';
        $employee->deleted_at = now();
        $employee->save();

        return response()->json(['message' => 'Employee deleted successfully.']);
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return response()->json(['message' => 'No employees selected.'], 400);
        }

        DB::beginTransaction();
        try {
            $employees = Employee::whereIn('id', $ids)->where('resort_id', $this->resort->resort_id)->get();

            foreach ($employees as $employee) {
                $employee->status = 'Inactive';
                $employee->save();
            }

            DB::commit();
            return response()->json(['message' => 'Selected employees deleted successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete employees.'], 500);
        }
    }

}

