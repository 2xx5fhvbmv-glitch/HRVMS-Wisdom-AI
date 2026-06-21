<?php

namespace App\Services\Wisdom;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Helpers\Common;
use App\Models\Employee;
use App\Models\EmployeeLeave;
use App\Models\Payroll;
use App\Models\ParentAttendace;
use App\Models\Vacancies;
use App\Models\Applicant_form_data;
use App\Models\ResortDepartment;
use App\Models\SOSHistoryModel;
use App\Models\PerformanceCycle;
use App\Models\PerformaChildCycle;
use App\Models\EmployeePipPlan;
use App\Models\EmployeePdpPlan;
use App\Models\PerformanceKpiParent;
use App\Models\MonthlyCheckingModel;
use App\Models\PeformanceMeeting;
use App\Services\Wisdom\ReadQueryGuard;
use Carbon\Carbon;

/**
 * Wisdom AI — read-only data tools exposed to the LLM via function calling.
 *
 * Every tool:
 *   - is READ ONLY (no writes, ever),
 *   - is hard-scoped to the logged-in user's resort_id,
 *   - is gated by the caller's access tier (payroll tools are HR-only; the
 *     POLICY tier gets no tools at all).
 *
 * Tool results are returned as plain associative arrays and JSON-encoded by
 * the client before being handed back to the model.
 */
class WisdomTools
{
    /** Tools that expose salary / payroll / compensation data (HR / FULL tier only). */
    const PAYROLL_TOOLS = ['get_payroll_summary', 'get_employee_salary', 'get_workforce_budget', 'get_employee_cost'];

    /**
     * OpenAI-compatible tool schema list, filtered by the caller's capabilities.
     * POLICY tier (no DB) → empty list.
     */
    public static function definitions(array $ctx): array
    {
        if (empty($ctx['can_db'])) {
            return [];
        }

        $tools = [
            self::fn('get_headcount',
                'Get the number of active employees at the resort. Optionally filter by a department name.',
                [
                    'department' => ['type' => 'string', 'description' => 'Optional department name to filter by (e.g. "Front Office").'],
                ]),
            self::fn('get_department_breakdown',
                'Get the count of active employees grouped by department.', []),
            self::fn('count_employees_by_status',
                'Get counts of employees grouped by their employment status (Active, Resigned, etc.).', []),
            self::fn('get_employees_on_leave',
                'List employees who are on approved leave on a given date (defaults to today).',
                [
                    'date' => ['type' => 'string', 'description' => 'Date in YYYY-MM-DD format. Defaults to today.'],
                ]),
            self::fn('list_employees',
                'List active employees with their names, department, position and nationality. Optionally filter by department. Use this for "who works here", "list the employees" or "employee names" type questions.',
                [
                    'department' => ['type' => 'string', 'description' => 'Optional department name to filter by.'],
                    'limit'      => ['type' => 'integer', 'description' => 'Max number to return (default 50, max 200).'],
                ]),
            self::fn('get_nationality_breakdown',
                'Count active employees grouped by nationality, including a local (Maldivian) vs foreign split. Use this for "how many are local/foreign/expat" questions.', []),
            self::fn('find_employee',
                'Look up a specific employee by name or employee ID. Returns profile details (department, position, status, joining date). Does NOT return salary.',
                [
                    'name' => ['type' => 'string', 'description' => 'Full or partial name, or employee ID to search for.'],
                ], ['name']),
            self::fn('get_recruitment_pipeline',
                'Get a summary of the recruitment pipeline: vacancies grouped by status and applicants grouped by status.', []),
            self::fn('get_attendance_summary',
                'Get an attendance summary for a given date (defaults to today): how many employees checked in vs. total active.',
                [
                    'date' => ['type' => 'string', 'description' => 'Date in YYYY-MM-DD format. Defaults to today.'],
                ]),
            self::fn('get_employee_attendance',
                'Attendance for ONE specific employee. For a single day pass `date` (defaults today) → present/absent with check-in/out times. For a period (e.g. "this month", "last week") pass `from` and `to` → a per-day breakdown plus present/absent day counts. Use for "was X present", "X\'s attendance this month" questions.',
                [
                    'name' => ['type' => 'string', 'description' => 'Full or partial employee name, or employee ID.'],
                    'date' => ['type' => 'string', 'description' => 'Single date YYYY-MM-DD. Defaults to today. Ignored if from/to are given.'],
                    'from' => ['type' => 'string', 'description' => 'Range start YYYY-MM-DD (use with to). For "this month" pass the 1st of the month.'],
                    'to'   => ['type' => 'string', 'description' => 'Range end YYYY-MM-DD (use with from).'],
                ], ['name']),
            self::fn('get_upcoming_birthdays',
                'List active employees whose birthday falls in a given month (defaults to the current month). Use for "whose birthday is coming up", "birthdays this month" type questions.',
                [
                    'month' => ['type' => 'integer', 'description' => 'Month number 1-12. Defaults to the current month.'],
                ]),
            self::fn('get_active_sos',
                'List active / ongoing SOS emergency incidents at the resort (those not yet resolved, rejected or closed): emergency type, who raised it, location, date/time and status. Use for "any active SOS", "current emergencies" questions.', []),
            self::fn('get_accommodation_summary',
                'Staff accommodation capacity & occupancy: total rooms, total beds (capacity), occupied beds, available (free) beds and occupancy rate. Use for "how many beds are available", "accommodation occupancy", "free rooms" questions.', []),

            // --- Workforce Planning (non-monetary) ---------------------------
            self::fn('get_budgeted_headcount',
                'Workforce Planning: approved/budgeted headcount vs filled vs vacant seats for a year, from the manning plan. group_by "total" (default), "division", "department" or "position". Use for "approved headcount", "budgeted manpower", "how many positions are approved", "manpower by division/department".',
                [
                    'year'     => ['type' => 'integer', 'description' => 'Four-digit year. Defaults to the current year.'],
                    'group_by' => ['type' => 'string', 'description' => 'One of: total, division, department, position. Default total.'],
                ]),
            self::fn('get_vacancy_analysis',
                'Workforce Planning: vacant positions for a year with fill rate, grouped by "department" (default), "position" or "total". Set critical_only=true to list only groups that still have vacancies (most understaffed first). Use for "how many vacancies", "vacancy by department", "which positions are vacant", "most critical vacancies", "understaffed departments".',
                [
                    'year'          => ['type' => 'integer', 'description' => 'Four-digit year. Defaults to the current year.'],
                    'group_by'      => ['type' => 'string', 'description' => 'One of: total, department, position. Default department.'],
                    'critical_only' => ['type' => 'boolean', 'description' => 'If true, only groups with vacancies, sorted most-vacant first.'],
                ]),
            self::fn('get_gender_breakdown',
                'Count active employees by gender (male/female), optionally filtered by department, with the female ratio. Use for "how many males/females", "gender ratio", "female workforce by department".',
                [
                    'department' => ['type' => 'string', 'description' => 'Optional department name to filter by.'],
                ]),
            self::fn('get_manning_status',
                'Workforce Planning: status of departmental manning / budget requests — counts by status and the latest status per department (Genrated/sent, Approved, Rejected, Pending, Completed). Use for "which departments submitted their manning plan", "pending manning requests", "approved/rejected requests".', []),
            self::fn('get_occupancy',
                'Resort room occupancy: the latest recorded occupancy (or as of a given date) — occupancy percentage, total rooms and occupied rooms. Use for "current occupancy", "what is our occupancy".',
                [
                    'date' => ['type' => 'string', 'description' => 'Optional date YYYY-MM-DD; returns the latest record on/before it. Defaults to the most recent.'],
                ]),
            self::fn('get_workforce_compliance',
                'Workforce compliance check. type "localization" (default) = Maldivian vs expat split and localization %. type "minimum_wage" = count of current staff paid below the statutory minimum (HR only). Use for "localization percentage", "are we meeting local employment ratio", "employees under minimum wage".',
                [
                    'type' => ['type' => 'string', 'description' => 'One of: localization, minimum_wage. Default localization.'],
                ]),

            // ---- Performance Management ----
            self::fn('get_performance_summary',
                'Performance Management dashboard summary: pending appraisals, employees on PIP, employees on PDP, monthly check-in status, active performance cycles and total KPIs. Use for "give me a performance summary", "how many appraisals are pending", "how many employees are in PIP/PDP", "how many under review".', []),
            self::fn('get_pip_overview',
                'Employees on a Performance Improvement Plan (PIP): count and list (name, department, duration, status). Use for "how many employees are on PIP", "show employees on PIP", "which departments have PIP".',
                [
                    'status'     => ['type' => 'string', 'description' => 'Filter: active (default), completed, cancelled, or all.'],
                    'department' => ['type' => 'string', 'description' => 'Optional department name filter.'],
                ]),
            self::fn('get_pdp_overview',
                'Employees on a Professional Development Plan (PDP): count and list (name, department, duration, status). Use for "how many on PDP", "show PDP employees".',
                [
                    'status'     => ['type' => 'string', 'description' => 'Filter: active (default), completed, cancelled, or all.'],
                    'department' => ['type' => 'string', 'description' => 'Optional department name filter.'],
                ]),
            self::fn('get_appraisal_status',
                'Appraisal completion across active performance cycles: total appraisals, completed, manager-review pending, self-review pending, and completion rate %. Use for "how many appraisals are pending", "appraisal completion rate", "how many reviews still pending".', []),
            self::fn('get_performance_cycles',
                'List performance (appraisal) cycles with status (Active/Pending/Closed) and dates. Use for "what performance cycles are active", "which cycle is running", "show cycle status".', []),
            self::fn('get_kpi_overview',
                'KPI overview: total KPIs and counts grouped by workflow status (pending, responded, approved, rejected). Use for "show all KPIs", "how many KPIs exist", "which KPIs are pending/approved".', []),
            self::fn('get_monthly_checkins',
                'Monthly check-in status: totals grouped by check-in status (Pending/Conducted/Confirm/Rescheduled) and by approval status (pending/approved). Use for "how many check-ins are pending", "monthly check-in status".', []),
            self::fn('get_performance_meetings',
                'Upcoming performance meetings from a date (defaults to today): title, date, time, location, participant count. Use for "what performance meetings are scheduled", "upcoming review meetings".',
                [
                    'date' => ['type' => 'string', 'description' => 'Start date YYYY-MM-DD. Defaults to today.'],
                ]),
            self::fn('get_employee_performance',
                'Performance snapshot for one employee by name: their PIP, PDP, latest appraisal (self/manager review status) and latest monthly check-in. Use for "has Ahmed completed his appraisal", "why is Ahmed on PIP", "show Ahmed\'s performance".',
                [
                    'name' => ['type' => 'string', 'description' => 'Employee full or partial name, or employee ID.'],
                ], ['name']),
        ];

        // Payroll tools — HR / FULL tier only.
        if (!empty($ctx['can_payroll'])) {
            $tools[] = self::fn('get_payroll_summary',
                'Get the latest payroll summary for the resort: pay period, status, total employees and total payroll amount.', []);
            $tools[] = self::fn('get_employee_salary',
                'Get the basic salary / compensation for a specific employee by name. Payroll-restricted.',
                [
                    'name' => ['type' => 'string', 'description' => 'Full or partial employee name.'],
                ], ['name']);
            $tools[] = self::fn('get_workforce_budget',
                'Resort annual workforce budget for a year (USD), using the exact same engine as the official View Budget page (staff salaries + budgeted cost components + allowances + vacant-slot costs). Returns a total plus a breakdown grouped by group_by: "department" (default), "division", "section", "position", "nationality" (local vs expat budget) or "gender", or "total" for just the grand total. Use for "total HR budget", "<department>/<division> budget", "budget by position", "budget for local/expat employees", "budget this year". Budget is annual; for a single month divide by 12. To answer one group, read its value from the breakdown. Payroll-restricted.',
                [
                    'year'     => ['type' => 'integer', 'description' => 'Four-digit year. Defaults to the current year.'],
                    'group_by' => ['type' => 'string', 'description' => 'One of: total, division, department, section, position, nationality, gender. Default department.'],
                ]);
            $tools[] = self::fn('get_employee_cost',
                'Annual budgeted cost for ONE employee (USD) for a year, with the monthly average — salary + budgeted cost components + allowances, same engine as View Budget. Use for "what is <employee>\'s annual budget/cost", "monthly cost of this employee". Payroll-restricted.',
                [
                    'name' => ['type' => 'string', 'description' => 'Full or partial employee name, or employee ID.'],
                    'year' => ['type' => 'integer', 'description' => 'Four-digit year. Defaults to the current year.'],
                ], ['name']);

            // Escape hatch + schema discovery for questions the dedicated tools
            // don't cover. Full (HR) tier only — that tier may already see all
            // data, so the only invariant left to protect is resort isolation
            // (mandatory :resort_id bind) plus hiding credentials/system tables.
            $tools[] = self::fn('list_tables',
                'Discover which database tables exist before writing a custom query. Returns business table names, optionally filtered by a keyword (e.g. "accommodation", "budget", "overtime"). Use this first when unsure of the exact table name.',
                [
                    'keyword' => ['type' => 'string', 'description' => 'Optional substring to filter table names by.'],
                ]);
            $tools[] = self::fn('describe_table',
                'Show the column names of a specific table so you can write a correct query. Describe a table before querying it if unsure of its columns.',
                [
                    'table' => ['type' => 'string', 'description' => 'Exact table name (from list_tables).'],
                ], ['table']);
            $tools[] = self::fn('run_read_query',
                "Run a custom READ-ONLY MySQL SELECT for questions the dedicated tools don't cover (budget, accommodation, overtime, etc.). WORKFLOW: if unsure of names, call `list_tables` then `describe_table` FIRST, then write the query. STRICT RULES:\n"
                . "1) SELECT only — no INSERT/UPDATE/DELETE/DDL, single statement, no semicolons.\n"
                . "2) You MUST scope to the resort by adding `resort_id = :resort_id` somewhere (never write a literal number — `:resort_id` is bound automatically). Use the resort_id column of the main table, or join through a table that has one.\n"
                . "3) System/auth tables and credential columns (passwords, tokens) are blocked.\n"
                . "Schema notes: employee NAMES live on resort_admins — join `employees.Admin_Parent_id = resort_admins.id` (first_name,last_name). Department: `employees.Dept_id = resort_departments.id`(name). Position: `employees.Position_id = resort_positions.id`(position_title). Attendance: `parent_attendaces.Emp_id = employees.id` (date, CheckingTime, CheckingOutTime, Status, OverTime, OTStatus). Employee date-of-birth column is `employees.dob`. Filter active staff with `employees.status = 'Active'`. Results are capped at 200 rows. If a query errors, use describe_table to check the real column names, then retry once.",
                [
                    'sql' => ['type' => 'string', 'description' => 'A single MySQL SELECT statement, scoped with `resort_id = :resort_id`.'],
                ], ['sql']);
        }

        return $tools;
    }

    /**
     * Execute a tool call. Returns an associative array (the tool result).
     * Defensive: any failure returns an "error" key rather than throwing, so a
     * single bad query never crashes the conversation.
     */
    public static function execute(string $name, array $args, array $ctx): array
    {
        // Hard gate: never run a payroll tool for a non-payroll tier, even if the
        // model somehow asks for it.
        if (in_array($name, self::PAYROLL_TOOLS, true) && empty($ctx['can_payroll'])) {
            return ['error' => 'Access denied: payroll and compensation data is restricted for your role.'];
        }
        // The ad-hoc SQL + schema-discovery tools are full-access (HR) only.
        if (in_array($name, ['run_read_query', 'list_tables', 'describe_table'], true) && empty($ctx['can_payroll'])) {
            return ['error' => 'Access denied: custom queries are restricted for your role.'];
        }
        if (empty($ctx['can_db'])) {
            return ['error' => 'Access denied: your role does not have database access.'];
        }

        $rid = $ctx['resort_id'];

        try {
            switch ($name) {
                case 'get_headcount':            return self::getHeadcount($rid, $args);
                case 'get_department_breakdown': return self::getDepartmentBreakdown($rid);
                case 'count_employees_by_status':return self::countByStatus($rid);
                case 'get_employees_on_leave':   return self::getEmployeesOnLeave($rid, $args);
                case 'list_employees':           return self::listEmployees($rid, $args);
                case 'get_nationality_breakdown':return self::getNationalityBreakdown($rid);
                case 'find_employee':            return self::findEmployee($rid, $args);
                case 'get_recruitment_pipeline': return self::getRecruitmentPipeline($rid);
                case 'get_attendance_summary':   return self::getAttendanceSummary($rid, $args);
                case 'get_employee_attendance':  return self::getEmployeeAttendance($rid, $args);
                case 'get_upcoming_birthdays':   return self::getUpcomingBirthdays($rid, $args);
                case 'get_active_sos':           return self::getActiveSos($rid);
                case 'get_accommodation_summary':return self::getAccommodationSummary($rid);
                case 'get_budgeted_headcount':   return self::getBudgetedHeadcount($rid, $args);
                case 'get_vacancy_analysis':     return self::getVacancyAnalysis($rid, $args);
                case 'get_gender_breakdown':     return self::getGenderBreakdown($rid, $args);
                case 'get_manning_status':       return self::getManningStatus($rid, $args);
                case 'get_occupancy':            return self::getOccupancy($rid, $args);
                case 'get_workforce_compliance':
                    // Minimum-wage breakdown exposes compensation data → HR only.
                    if (strtolower(trim($args['type'] ?? '')) === 'minimum_wage' && empty($ctx['can_payroll'])) {
                        return ['error' => 'Access denied: minimum-wage / compensation data is restricted for your role.'];
                    }
                    return self::getWorkforceCompliance($rid, $args);
                case 'get_performance_summary':  return self::getPerformanceSummary($rid);
                case 'get_pip_overview':         return self::getPlanOverview($rid, $args, 'pip');
                case 'get_pdp_overview':         return self::getPlanOverview($rid, $args, 'pdp');
                case 'get_appraisal_status':     return self::getAppraisalStatus($rid);
                case 'get_performance_cycles':   return self::getPerformanceCycles($rid);
                case 'get_kpi_overview':         return self::getKpiOverview($rid);
                case 'get_monthly_checkins':     return self::getMonthlyCheckins($rid);
                case 'get_performance_meetings': return self::getPerformanceMeetings($rid, $args);
                case 'get_employee_performance': return self::getEmployeePerformance($rid, $args);
                case 'get_workforce_budget':     return self::getWorkforceBudget($rid, $args);
                case 'get_employee_cost':        return self::getEmployeeCost($rid, $args);
                case 'get_payroll_summary':      return self::getPayrollSummary($rid);
                case 'get_employee_salary':      return self::getEmployeeSalary($rid, $args);
                case 'list_tables':              return self::listTables($args);
                case 'describe_table':           return self::describeTable($args);
                case 'run_read_query':           return self::runReadQuery($rid, $args);
                default:                         return ['error' => "Unknown tool: {$name}"];
            }
        } catch (\Throwable $e) {
            Log::warning('Wisdom AI tool failed', ['tool' => $name, 'error' => $e->getMessage()]);
            return ['error' => 'Could not retrieve this data right now.'];
        }
    }

    // ---------------------------------------------------------------------
    // Tool implementations
    // ---------------------------------------------------------------------

    private static function getHeadcount(int $rid, array $args): array
    {
        $q = Employee::where('resort_id', $rid)->where('status', 'Active');

        $dept = trim($args['department'] ?? '');
        if ($dept !== '') {
            $q->whereHas('department', fn ($d) => $d->where('name', 'like', "%{$dept}%"));
        }

        return [
            'department'      => $dept !== '' ? $dept : 'All departments',
            'active_headcount' => $q->count(),
        ];
    }

    private static function getDepartmentBreakdown(int $rid): array
    {
        $rows = Employee::where('resort_id', $rid)
            ->where('status', 'Active')
            ->select('Dept_id', DB::raw('COUNT(*) as c'))
            ->groupBy('Dept_id')
            ->get();

        $deptNames = ResortDepartment::where('resort_id', $rid)->pluck('name', 'id');

        $breakdown = [];
        foreach ($rows as $row) {
            $label = $deptNames[$row->Dept_id] ?? 'Unassigned';
            $breakdown[$label] = ($breakdown[$label] ?? 0) + (int) $row->c;
        }
        arsort($breakdown);

        return [
            'total_active'  => array_sum($breakdown),
            'by_department' => $breakdown,
        ];
    }

    private static function countByStatus(int $rid): array
    {
        $rows = Employee::where('resort_id', $rid)
            ->select('status', DB::raw('COUNT(*) as c'))
            ->groupBy('status')
            ->pluck('c', 'status');

        return ['by_status' => $rows->toArray()];
    }

    private static function getEmployeesOnLeave(int $rid, array $args): array
    {
        $date = self::cleanDate($args['date'] ?? null);

        $leaves = EmployeeLeave::where('resort_id', $rid)
            ->where('status', 'Approved')
            ->whereDate('from_date', '<=', $date)
            ->whereDate('to_date', '>=', $date)
            ->with(['employee:id,Emp_id,Admin_Parent_id,Dept_id', 'employee.resortAdmin:id,first_name,last_name', 'employee.department:id,name', 'LeaveCategory:id,name'])
            ->limit(100)
            ->get();

        $list = $leaves->map(function ($l) {
            $emp = $l->employee;
            return [
                'name'       => $emp ? self::empName($emp) : 'Unknown',
                'department' => $emp && $emp->department ? $emp->department->name : null,
                'leave_type' => $l->LeaveCategory->name ?? null,
                'from'       => $l->getRawOriginal('from_date'),
                'to'         => $l->getRawOriginal('to_date'),
            ];
        })->values();

        return [
            'date'  => $date,
            'count' => $list->count(),
            'employees' => $list,
        ];
    }

    private static function findEmployee(int $rid, array $args): array
    {
        $term = trim($args['name'] ?? '');
        if ($term === '') {
            return ['error' => 'Please provide a name or employee ID to search for.'];
        }

        $employees = Employee::where('resort_id', $rid)
            ->where(function ($q) use ($term) {
                $q->whereHas('resortAdmin', function ($r) use ($term) {
                        $r->where('first_name', 'like', "%{$term}%")
                          ->orWhere('last_name', 'like', "%{$term}%")
                          ->orWhereRaw("CONCAT(first_name,' ',last_name) LIKE ?", ["%{$term}%"]);
                    })
                  ->orWhere('Emp_id', 'like', "%{$term}%");
            })
            ->with(['resortAdmin:id,first_name,last_name', 'department:id,name', 'position:id,position_title'])
            ->limit(10)
            ->get();

        $list = $employees->map(fn ($e) => [
            'name'        => self::empName($e),
            'employee_id' => $e->Emp_id,
            'department'  => $e->department->name ?? null,
            'position'    => $e->position->position_title ?? null,
            'status'      => $e->status,
            'employment_type' => $e->employment_type,
            'joining_date' => $e->getRawOriginal('joining_date'),
        ])->values();

        return [
            'query'   => $term,
            'matches' => $list->count(),
            'employees' => $list,
        ];
    }

    private static function listEmployees(int $rid, array $args): array
    {
        $limit = (int) ($args['limit'] ?? 50);
        if ($limit < 1)   $limit = 50;
        if ($limit > 200) $limit = 200;

        $q = Employee::where('resort_id', $rid)
            ->where('status', 'Active')
            ->with(['resortAdmin:id,first_name,last_name', 'department:id,name', 'position:id,position_title']);

        $dept = trim($args['department'] ?? '');
        if ($dept !== '') {
            $q->whereHas('department', fn ($d) => $d->where('name', 'like', "%{$dept}%"));
        }

        $total = (clone $q)->count();
        $rows  = $q->limit($limit)->get();

        $list = $rows->map(fn ($e) => [
            'name'        => self::empName($e),
            'department'  => $e->department->name ?? null,
            'position'    => $e->position->position_title ?? null,
            'nationality' => $e->nationality,
        ])->sortBy('name')->values();

        return [
            'total_active' => $total,
            'returned'     => $list->count(),
            'truncated'    => $total > $list->count(),
            'employees'    => $list,
        ];
    }

    private static function getNationalityBreakdown(int $rid): array
    {
        $rows = Employee::where('resort_id', $rid)
            ->where('status', 'Active')
            ->select('nationality', DB::raw('COUNT(*) as c'))
            ->groupBy('nationality')
            ->get();

        $byNat = [];
        $local = 0;
        $foreign = 0;
        foreach ($rows as $r) {
            $nat = trim((string) $r->nationality);
            $label = $nat !== '' ? $nat : 'Unspecified';
            $byNat[$label] = ($byNat[$label] ?? 0) + (int) $r->c;

            if (stripos($nat, 'maldiv') !== false) {
                $local += (int) $r->c;
            } elseif ($nat !== '') {
                $foreign += (int) $r->c;
            }
        }
        arsort($byNat);

        return [
            'by_nationality'  => $byNat,
            'local_maldivian' => $local,
            'foreign'         => $foreign,
            'note'            => 'Local = Maldivian nationality; foreign = all other specified nationalities.',
        ];
    }

    private static function getRecruitmentPipeline(int $rid): array
    {
        $result = [];

        try {
            $result['vacancies_by_status'] = Vacancies::where('Resort_id', $rid)
                ->select('status', DB::raw('COUNT(*) as c'))
                ->groupBy('status')->pluck('c', 'status')->toArray();
        } catch (\Throwable $e) {
            $result['vacancies_by_status'] = [];
        }

        try {
            $result['applicants_by_status'] = Applicant_form_data::where('resort_id', $rid)
                ->select('status', DB::raw('COUNT(*) as c'))
                ->groupBy('status')->pluck('c', 'status')->toArray();
            $result['total_applicants'] = array_sum($result['applicants_by_status']);
        } catch (\Throwable $e) {
            $result['applicants_by_status'] = [];
        }

        return $result;
    }

    private static function getAttendanceSummary(int $rid, array $args): array
    {
        $date = self::cleanDate($args['date'] ?? null);

        $checkedIn = ParentAttendace::where('resort_id', $rid)
            ->whereDate('date', $date)
            ->whereNotNull('CheckingTime')
            ->where('CheckingTime', '!=', '')
            ->distinct('Emp_id')
            ->count('Emp_id');

        $activeTotal = Employee::where('resort_id', $rid)->where('status', 'Active')->count();

        return [
            'date'           => $date,
            'checked_in'     => $checkedIn,
            'active_total'   => $activeTotal,
            'not_checked_in' => max(0, $activeTotal - $checkedIn),
        ];
    }

    private static function getEmployeeAttendance(int $rid, array $args): array
    {
        $term = trim($args['name'] ?? '');
        if ($term === '') {
            return ['error' => 'Please provide an employee name.'];
        }

        $employee = Employee::where('resort_id', $rid)
            ->where(function ($q) use ($term) {
                $q->whereHas('resortAdmin', function ($r) use ($term) {
                        $r->where('first_name', 'like', "%{$term}%")
                          ->orWhere('last_name', 'like', "%{$term}%")
                          ->orWhereRaw("CONCAT(first_name,' ',last_name) LIKE ?", ["%{$term}%"]);
                    })
                  ->orWhere('Emp_id', 'like', "%{$term}%");
            })
            ->with(['resortAdmin:id,first_name,last_name', 'department:id,name'])
            ->first();

        if (!$employee) {
            return ['query' => $term, 'found' => false, 'message' => 'No matching employee found.'];
        }

        $from = $args['from'] ?? null;
        $to   = $args['to'] ?? null;

        // Range mode: per-day breakdown + present/absent counts.
        if ($from && $to) {
            $from = self::cleanDate($from);
            $to   = self::cleanDate($to);

            // parent_attendaces.Emp_id is the employees.id foreign key.
            $rows = ParentAttendace::where('resort_id', $rid)
                ->where('Emp_id', $employee->id)
                ->whereBetween('date', [$from, $to])
                ->orderBy('date')
                ->get();

            $days = $rows->map(fn ($a) => [
                'date'      => $a->getRawOriginal('date'),
                'present'   => !empty($a->CheckingTime),
                'status'    => $a->Status ?: (!empty($a->CheckingTime) ? 'Present' : 'Absent'),
                'check_in'  => $a->CheckingTime ?: null,
                'check_out' => $a->CheckingOutTime ?: null,
            ])->values();

            $presentDays = $days->where('present', true)->count();

            return [
                'employee'      => self::empName($employee),
                'department'    => $employee->department->name ?? null,
                'from'          => $from,
                'to'            => $to,
                'days_with_records' => $days->count(),
                'present_days'  => $presentDays,
                'absent_or_no_record_days' => max(0, $days->count() - $presentDays),
                'daily'         => $days,
            ];
        }

        // Single-day mode.
        $date = self::cleanDate($args['date'] ?? null);
        $att = ParentAttendace::where('resort_id', $rid)
            ->where('Emp_id', $employee->id)
            ->whereDate('date', $date)
            ->first();

        $present = $att && !empty($att->CheckingTime);

        return [
            'employee'   => self::empName($employee),
            'department' => $employee->department->name ?? null,
            'date'       => $date,
            'present'    => $present,
            'status'     => $att?->Status ?? ($present ? 'Present' : 'No attendance record for this date'),
            'check_in'   => $att?->CheckingTime ?: null,
            'check_out'  => $att?->CheckingOutTime ?: null,
        ];
    }

    private static function getUpcomingBirthdays(int $rid, array $args): array
    {
        $month = (int) ($args['month'] ?? 0);
        if ($month < 1 || $month > 12) {
            $month = (int) now()->format('n');
        }

        $rows = Employee::where('resort_id', $rid)
            ->where('status', 'Active')
            ->whereNotNull('dob')
            ->whereMonth('dob', $month)
            ->with(['resortAdmin:id,first_name,last_name', 'department:id,name'])
            ->get();

        $list = $rows->map(function ($e) {
            $raw = $e->getRawOriginal('dob');
            $day = null;
            try {
                $day = $raw ? (int) Carbon::parse($raw)->format('j') : null;
            } catch (\Throwable $ex) {
                $day = null;
            }
            return [
                'name'          => self::empName($e),
                'department'    => $e->department->name ?? null,
                'date_of_birth' => $raw,
                'day'           => $day,
            ];
        })->filter(fn ($r) => $r['day'] !== null)->sortBy('day')->values();

        return [
            'month'     => Carbon::create(null, $month, 1)->format('F'),
            'count'     => $list->count(),
            'birthdays' => $list,
        ];
    }

    private static function getActiveSos(int $rid): array
    {
        // "Active" = anything not in a terminal state. Status values seen in the
        // SOS module: Pending, Active, Real-Active, Drill-Active (active) vs
        // Resolved / Rejected / Closed / Inactive (terminal).
        $terminal = ['resolved', 'rejected', 'closed', 'inactive'];

        $rows = SOSHistoryModel::where('resort_id', $rid)
            ->whereNull('deleted_at')
            ->whereNotIn(DB::raw('LOWER(status)'), $terminal)
            ->with(['getSos:id,name', 'employee:id,Admin_Parent_id', 'employee.resortAdmin:id,first_name,last_name'])
            ->orderByDesc('date')->orderByDesc('time')
            ->limit(50)
            ->get();

        $list = $rows->map(function ($s) {
            $emp = $s->employee;
            return [
                'type'        => $s->getSos->name ?? 'Unknown',
                'raised_by'   => $emp ? self::empName($emp) : 'Unknown',
                'location'    => $s->location,
                'date'        => $s->getRawOriginal('date'),
                'time'        => $s->time,
                'status'      => $s->status,
                'description' => $s->emergency_description,
            ];
        })->values();

        return [
            'active_count' => $list->count(),
            'incidents'    => $list,
        ];
    }

    /**
     * Staff accommodation capacity & occupancy. A room's bed count is its
     * `Capacity`; a bed is occupied when there is a matching row in
     * assing_accommodations (see AssignAccommodationController occupancy logic).
     */
    private static function getAccommodationSummary(int $rid): array
    {
        $totalRooms = DB::table('available_accommodation_models')->where('resort_id', $rid)->count();
        $totalBeds  = (int) DB::table('available_accommodation_models')->where('resort_id', $rid)->sum('Capacity');
        $occupied   = DB::table('assing_accommodations')->where('resort_id', $rid)->count();
        $available  = max(0, $totalBeds - $occupied);

        return [
            'total_rooms'     => $totalRooms,
            'total_beds'      => $totalBeds,
            'occupied_beds'   => $occupied,
            'available_beds'  => $available,
            'occupancy_rate'  => $totalBeds > 0 ? round($occupied / $totalBeds * 100, 1) . '%' : 'n/a',
        ];
    }

    // =====================================================================
    // Workforce Planning module
    // Budget + cost use the SAME canonical engine as the View Budget page
    // (Common::annualBudgetForEmployee / annualBudgetForVacantSlot, USD).
    // Headcount / vacancy mirror the Workforce Planning dashboard's
    // position_monthly_data MAX-per-month aggregation. All resort-scoped.
    // =====================================================================

    /** Resolve a 4-digit year argument, defaulting to the current year. */
    private static function wpYear(array $args): int
    {
        $year = (int) ($args['year'] ?? 0);
        return ($year < 2000 || $year > 2100) ? (int) now()->format('Y') : $year;
    }

    /**
     * Annual workforce budget (USD) for a year, grouped by the requested
     * dimension. Same engine as the official View Budget page: each figure =
     * per-employee (salary + budgeted cost components + allowances) over active
     * employees + per-vacant-slot costs over budgeted vacancies.
     */
    private static function getWorkforceBudget(int $rid, array $args): array
    {
        $year    = self::wpYear($args);
        $groupBy = strtolower(trim($args['group_by'] ?? 'department'));
        if (!in_array($groupBy, ['total', 'division', 'department', 'section', 'position', 'nationality', 'gender'], true)) {
            $groupBy = 'department';
        }

        $depts     = DB::table('resort_departments')->where('resort_id', $rid)->get(['id', 'name', 'division_id'])->keyBy('id');
        $divs      = DB::table('resort_divisions')->where('resort_id', $rid)->get(['id', 'name'])->keyBy('id');
        $positions = DB::table('resort_positions')->where('resort_id', $rid)->get(['id', 'position_title', 'section_id'])->keyBy('id');
        $sections  = DB::table('resort_sections')->where('resort_id', $rid)->get(['id', 'name'])->keyBy('id');
        $genders   = DB::table('employees as e')->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->where('e.resort_id', $rid)->pluck('ra.gender', 'e.id');

        $buckets = [];
        $add = function ($key, float $amt) use (&$buckets) {
            if ($amt <= 0) return;
            $key = ($key === null || $key === '') ? 'Unspecified' : $key;
            $buckets[$key] = ($buckets[$key] ?? 0) + $amt;
        };

        foreach (Employee::where('resort_id', $rid)->where('status', 'Active')->get() as $emp) {
            $amt = Common::annualBudgetForEmployee($rid, $year, $emp);
            if ($amt <= 0) continue;
            switch ($groupBy) {
                case 'total':       $key = 'Total'; break;
                case 'division':    $key = optional($divs->get(optional($depts->get($emp->Dept_id))->division_id))->name ?? 'No division'; break;
                case 'department':  $key = optional($depts->get($emp->Dept_id))->name ?? 'Unassigned'; break;
                case 'section':     $key = optional($sections->get($emp->Section_id))->name ?? 'No section'; break;
                case 'position':    $key = optional($positions->get($emp->Position_id))->position_title ?? 'No position'; break;
                case 'nationality': $key = $emp->nationality === 'Maldivian' ? 'Local (Maldivian)' : ($emp->nationality ? 'Expatriate' : 'Unspecified'); break;
                case 'gender':      $g = $genders->get($emp->id); $key = $g ? ucfirst(strtolower($g)) : 'Unspecified'; break;
                default:            $key = 'Total';
            }
            $add($key, $amt);
        }

        $vacants = DB::table('resort_vacant_budget_costs')->where('resort_id', $rid)->where('year', $year)
            ->get(['id', 'position_id', 'department_id', 'vacant_index', 'basic_salary', 'current_salary']);
        foreach ($vacants as $v) {
            $amt = Common::annualBudgetForVacantSlot($rid, $year, $v);
            if ($amt <= 0) continue;
            switch ($groupBy) {
                case 'total':       $key = 'Total'; break;
                case 'division':    $key = optional($divs->get(optional($depts->get($v->department_id))->division_id))->name ?? 'No division'; break;
                case 'department':  $key = optional($depts->get($v->department_id))->name ?? 'Unassigned'; break;
                case 'section':     $key = optional($sections->get(optional($positions->get($v->position_id))->section_id))->name ?? 'No section'; break;
                case 'position':    $key = optional($positions->get($v->position_id))->position_title ?? 'No position'; break;
                default:            $key = 'Vacant (unassigned)'; // nationality / gender unknown for empty slots
            }
            $add($key, $amt);
        }

        $rate = self::dollarToMvr($rid);
        $grand = array_sum($buckets);
        arsort($buckets);
        $formatted = [];
        foreach ($buckets as $k => $v) {
            $formatted[$k] = self::dualMoney((float) $v, $rate);
        }

        return [
            'year'            => $year,
            'group_by'        => $groupBy,
            'conversion_rate' => '1 USD = ' . number_format($rate, 4) . ' MVR',
            'total_budget'    => self::dualMoney($grand, $rate),
            'breakdown'       => $formatted,
            'basis'           => 'Annual workforce budget = per-employee salary + budgeted cost components + allowances, plus vacant-slot costs (same engine as the View Budget page). Each figure is given in BOTH USD and MVR (MVR = USD × the resort USD→MVR rate). Budget is planned annually; for one month divide by 12 (even split).',
            'note'            => empty($formatted) ? "No budget data found for {$year}." : null,
        ];
    }

    /** Annual budgeted cost for one employee (USD), with monthly average. */
    private static function getEmployeeCost(int $rid, array $args): array
    {
        $term = trim($args['name'] ?? '');
        if ($term === '') return ['error' => 'Please provide an employee name.'];
        $year = self::wpYear($args);

        $emp = Employee::where('resort_id', $rid)
            ->where(function ($q) use ($term) {
                $q->whereHas('resortAdmin', function ($r) use ($term) {
                        $r->whereRaw("CONCAT(first_name,' ',last_name) LIKE ?", ["%{$term}%"])
                          ->orWhere('first_name', 'like', "%{$term}%")
                          ->orWhere('last_name', 'like', "%{$term}%");
                    })
                  ->orWhere('Emp_id', 'like', "%{$term}%");
            })
            ->with(['resortAdmin:id,first_name,last_name', 'department:id,name', 'position:id,position_title'])
            ->first();
        if (!$emp) return ['query' => $term, 'matches' => 0];

        $total = Common::annualBudgetForEmployee($rid, $year, $emp);
        $rate  = self::dollarToMvr($rid);
        return [
            'employee'        => self::empName($emp),
            'employee_id'     => $emp->Emp_id,
            'department'      => $emp->department->name ?? null,
            'position'        => $emp->position->position_title ?? null,
            'year'            => $year,
            'conversion_rate' => '1 USD = ' . number_format($rate, 4) . ' MVR',
            'annual_budget'   => self::dualMoney($total, $rate),
            'monthly_average' => self::dualMoney($total / 12, $rate),
            'basis'           => 'Annual budgeted cost (salary + budgeted cost components + allowances), same engine as View Budget. Given in BOTH USD and MVR.',
        ];
    }

    /**
     * Per-(position, manning_response) seat totals for a year: MAX headcount /
     * filled / vacant across the 12 monthly rows, exactly like the Workforce
     * Planning dashboard. Each manning_response is one department.
     */
    private static function wpSeatRows(int $rid, int $year)
    {
        return DB::table('position_monthly_data as pmd')
            ->join('manning_responses as mr', 'mr.id', '=', 'pmd.manning_response_id')
            ->where('mr.resort_id', $rid)
            ->where('mr.year', $year)
            ->groupBy('pmd.position_id', 'pmd.manning_response_id', 'mr.dept_id')
            ->get([
                'pmd.position_id',
                'mr.dept_id',
                DB::raw('MAX(pmd.headcount)   as budget'),
                DB::raw('MAX(pmd.filledcount) as filled'),
                DB::raw('MAX(pmd.vacantcount) as vacant'),
            ]);
    }

    /** Budgeted vs filled vs vacant headcount for a year, grouped. */
    private static function getBudgetedHeadcount(int $rid, array $args): array
    {
        $year    = self::wpYear($args);
        $groupBy = strtolower(trim($args['group_by'] ?? 'total'));
        if (!in_array($groupBy, ['total', 'division', 'department', 'position'], true)) {
            $groupBy = 'total';
        }

        $rows = self::wpSeatRows($rid, $year);
        if ($rows->isEmpty()) {
            $active = Employee::where('resort_id', $rid)->where('status', 'Active')->count();
            return [
                'year' => $year, 'group_by' => $groupBy,
                'total_budgeted' => $active, 'total_filled' => $active, 'total_vacant' => 0,
                'note' => "No manning plan submitted for {$year}; showing live active headcount as both budgeted and filled.",
            ];
        }

        $depts     = DB::table('resort_departments')->where('resort_id', $rid)->get(['id', 'name', 'division_id'])->keyBy('id');
        $divs      = DB::table('resort_divisions')->where('resort_id', $rid)->get(['id', 'name'])->keyBy('id');
        $positions = DB::table('resort_positions')->where('resort_id', $rid)->get(['id', 'position_title'])->keyBy('id');

        $tot = ['budgeted' => 0, 'filled' => 0, 'vacant' => 0];
        $groups = [];
        foreach ($rows as $r) {
            $tot['budgeted'] += (int) $r->budget;
            $tot['filled']   += (int) $r->filled;
            $tot['vacant']   += (int) $r->vacant;
            if ($groupBy === 'total') continue;
            switch ($groupBy) {
                case 'division':   $key = optional($divs->get(optional($depts->get($r->dept_id))->division_id))->name ?? 'No division'; break;
                case 'department': $key = optional($depts->get($r->dept_id))->name ?? 'Unassigned'; break;
                default:           $key = optional($positions->get($r->position_id))->position_title ?? ('Position #' . $r->position_id);
            }
            $groups[$key] = $groups[$key] ?? ['budgeted' => 0, 'filled' => 0, 'vacant' => 0];
            $groups[$key]['budgeted'] += (int) $r->budget;
            $groups[$key]['filled']   += (int) $r->filled;
            $groups[$key]['vacant']   += (int) $r->vacant;
        }

        $out = [
            'year' => $year, 'group_by' => $groupBy,
            'total_budgeted' => $tot['budgeted'], 'total_filled' => $tot['filled'], 'total_vacant' => $tot['vacant'],
        ];
        if ($groupBy !== 'total') {
            uasort($groups, fn ($a, $b) => $b['budgeted'] <=> $a['budgeted']);
            $out['breakdown'] = $groups;
        }
        return $out;
    }

    /** Vacancy analysis for a year: vacant seats + fill rate, grouped. */
    private static function getVacancyAnalysis(int $rid, array $args): array
    {
        $year    = self::wpYear($args);
        $groupBy = strtolower(trim($args['group_by'] ?? 'department'));
        if (!in_array($groupBy, ['total', 'department', 'position'], true)) {
            $groupBy = 'department';
        }
        $criticalOnly = !empty($args['critical_only']);

        $rows = self::wpSeatRows($rid, $year);
        if ($rows->isEmpty()) {
            return ['year' => $year, 'total_vacant' => 0, 'note' => "No manning plan submitted for {$year}; vacancy analysis is unavailable."];
        }

        $depts     = DB::table('resort_departments')->where('resort_id', $rid)->get(['id', 'name'])->keyBy('id');
        $positions = DB::table('resort_positions')->where('resort_id', $rid)->get(['id', 'position_title'])->keyBy('id');

        $totalVac = 0;
        $groups = [];
        foreach ($rows as $r) {
            $totalVac += (int) $r->vacant;
            switch ($groupBy) {
                case 'department': $key = optional($depts->get($r->dept_id))->name ?? 'Unassigned'; break;
                case 'position':   $key = optional($positions->get($r->position_id))->position_title ?? ('Position #' . $r->position_id); break;
                default:           $key = 'Total';
            }
            $groups[$key] = $groups[$key] ?? ['budgeted' => 0, 'filled' => 0, 'vacant' => 0];
            $groups[$key]['budgeted'] += (int) $r->budget;
            $groups[$key]['filled']   += (int) $r->filled;
            $groups[$key]['vacant']   += (int) $r->vacant;
        }

        if ($groupBy === 'total') {
            return [
                'year' => $year, 'total_vacant' => $totalVac,
                'total_budgeted' => array_sum(array_column($groups, 'budgeted')),
                'total_filled'   => array_sum(array_column($groups, 'filled')),
            ];
        }

        foreach ($groups as &$g) {
            $g['fill_rate'] = $g['budgeted'] > 0 ? round($g['filled'] / $g['budgeted'] * 100) . '%' : '—';
        }
        unset($g);
        if ($criticalOnly) {
            $groups = array_filter($groups, fn ($g) => $g['vacant'] > 0);
        }
        uasort($groups, fn ($a, $b) => $b['vacant'] <=> $a['vacant']);

        return ['year' => $year, 'group_by' => $groupBy, 'total_vacant' => $totalVac, 'critical_only' => $criticalOnly, 'breakdown' => $groups];
    }

    /** Active-staff gender split, optionally filtered by department. */
    private static function getGenderBreakdown(int $rid, array $args): array
    {
        $dept = trim($args['department'] ?? '');
        $q = Employee::where('employees.resort_id', $rid)->where('employees.status', 'Active')
            ->join('resort_admins as ra', 'ra.id', '=', 'employees.Admin_Parent_id');
        if ($dept !== '') {
            $q->whereHas('department', fn ($d) => $d->where('name', 'like', "%{$dept}%"));
        }
        $rows = $q->selectRaw('LOWER(COALESCE(NULLIF(ra.gender, ""), "unspecified")) g, COUNT(*) c')
            ->groupBy('g')->pluck('c', 'g');

        $male = (int) $rows->get('male', 0);
        $female = (int) $rows->get('female', 0);
        $other = 0;
        foreach ($rows as $k => $v) {
            if (!in_array($k, ['male', 'female'], true)) $other += (int) $v;
        }
        $total = $male + $female + $other;
        return [
            'scope'        => $dept !== '' ? $dept : 'All departments',
            'total'        => $total,
            'male'         => $male,
            'female'       => $female,
            'unspecified'  => $other,
            'female_ratio' => $total > 0 ? round($female / $total * 100) . '%' : '—',
        ];
    }

    /** Manning / budget request approval status, by status and by department. */
    private static function getManningStatus(int $rid, array $args): array
    {
        $depts = DB::table('resort_departments')->where('resort_id', $rid)->get(['id', 'name'])->keyBy('id');
        $statuses = DB::table('budget_statuses')->where('resort_id', $rid)
            ->orderBy('created_at')->get(['Department_id', 'status']);
        if ($statuses->isEmpty()) {
            return ['note' => 'No manning / budget request records found for this resort.'];
        }
        $byStatus = [];
        $byDept = [];
        foreach ($statuses as $s) {
            $st = $s->status ?: 'Unknown';
            $byStatus[$st] = ($byStatus[$st] ?? 0) + 1;
            $dn = optional($depts->get($s->Department_id))->name ?? ('Dept #' . $s->Department_id);
            $byDept[$dn] = $st; // ordered by created_at → latest status per dept wins
        }
        return [
            'by_status'     => $byStatus,
            'by_department' => $byDept,
            'note'          => 'Status meanings: Genrated = request generated/sent to HODs; Approved; Rejected; Pending; Completed.',
        ];
    }

    /** Resort occupancy (latest, or as of a given date). */
    private static function getOccupancy(int $rid, array $args): array
    {
        $date = (isset($args['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', trim((string) $args['date'])))
            ? trim((string) $args['date']) : null;
        $q = DB::table('occuplanies')->where('resort_id', $rid)->whereNull('deleted_at');
        if ($date) $q->where('occupancydate', '<=', $date);
        $row = $q->orderByDesc('occupancydate')->first();
        if (!$row) return ['note' => 'No occupancy data recorded for this resort.'];
        return [
            'date'              => $row->occupancydate,
            'occupancy_percent' => $row->occupancyinPer,
            'total_rooms'       => $row->occupancytotalRooms,
            'occupied_rooms'    => $row->occupancyOccupiedRooms,
        ];
    }

    /** Workforce compliance: localization ratio, or minimum-wage breaches. */
    private static function getWorkforceCompliance(int $rid, array $args): array
    {
        $type = strtolower(trim($args['type'] ?? 'localization'));
        $current = ['Active', 'Probationary'];

        if ($type === 'minimum_wage') {
            $base = fn () => Employee::where('resort_id', $rid)->whereIn('status', $current);
            $usd = $base()->where('basic_salary_currency', 'USD')
                ->where(fn ($q) => $q->where('basic_salary', '<', 520)->orWhereNull('basic_salary'))->count();
            $mvr = $base()->where('basic_salary_currency', 'MVR')
                ->where(fn ($q) => $q->where('basic_salary', '<', 8021)->orWhereNull('basic_salary'))->count();
            $unconfigured = $base()->whereNull('basic_salary_currency')->count();
            return [
                'type'                => 'minimum_wage',
                'thresholds'          => ['USD' => 520, 'MVR' => 8021],
                'under_min_wage_usd'  => $usd,
                'under_min_wage_mvr'  => $mvr,
                'salary_unconfigured' => $unconfigured,
                'total_flagged'       => $usd + $mvr + $unconfigured,
                'note'                => 'Current (Active/Probationary) staff paid below the statutory minimum wage, plus those with no salary configured.',
            ];
        }

        $local = Employee::where('resort_id', $rid)->whereIn('status', $current)->where('nationality', 'Maldivian')->count();
        $expat = Employee::where('resort_id', $rid)->whereIn('status', $current)->where('nationality', '!=', 'Maldivian')->count();
        $total = $local + $expat;
        return [
            'type'                 => 'localization',
            'local_maldivian'      => $local,
            'expatriate'           => $expat,
            'total'                => $total,
            'localization_percent' => $total > 0 ? round($local / $total * 100, 1) . '%' : '—',
            'note'                 => 'Localization = Maldivian share of the current (Active/Probationary) workforce.',
        ];
    }

    private static function getPayrollSummary(int $rid): array
    {
        $p = Payroll::where('resort_id', $rid)->orderByDesc('id')->first();
        if (!$p) {
            return ['message' => 'No payroll records found for this resort yet.'];
        }

        $unit = $p->payroll_unit ?: 'USD';
        $rate = self::dollarToMvr($rid);

        return [
            'period_start'    => $p->getRawOriginal('start_date'),
            'period_end'      => $p->getRawOriginal('end_date'),
            'status'          => $p->status,
            'payment_date'    => $p->getRawOriginal('payment_date'),
            'total_employees' => $p->total_employees,
            'conversion_rate' => '1 USD = ' . number_format($rate, 4) . ' MVR',
            'total_payroll'   => self::dualMoney((float) $p->total_payroll, $rate, $unit),
        ];
    }

    private static function getEmployeeSalary(int $rid, array $args): array
    {
        $term = trim($args['name'] ?? '');
        if ($term === '') {
            return ['error' => 'Please provide an employee name.'];
        }

        $employees = Employee::where('resort_id', $rid)
            ->where(function ($q) use ($term) {
                $q->whereHas('resortAdmin', function ($r) use ($term) {
                        $r->where('first_name', 'like', "%{$term}%")
                          ->orWhere('last_name', 'like', "%{$term}%")
                          ->orWhereRaw("CONCAT(first_name,' ',last_name) LIKE ?", ["%{$term}%"]);
                    })
                  ->orWhere('Emp_id', 'like', "%{$term}%");
            })
            ->with(['resortAdmin:id,first_name,last_name', 'department:id,name', 'position:id,position_title'])
            ->limit(10)
            ->get();

        if ($employees->isEmpty()) {
            return ['query' => $term, 'matches' => 0, 'employees' => []];
        }

        $rate = self::dollarToMvr($rid);
        $list = $employees->map(fn ($e) => [
            'name'         => self::empName($e),
            'employee_id'  => $e->Emp_id,
            'department'   => $e->department->name ?? null,
            'position'     => $e->position->position_title ?? null,
            'basic_salary' => $e->basic_salary !== null
                ? self::dualMoney((float) $e->basic_salary, $rate, $e->basic_salary_currency ?: 'USD')
                : 'Not set',
        ])->values();

        return [
            'query'           => $term,
            'matches'         => $list->count(),
            'conversion_rate' => '1 USD = ' . number_format($rate, 4) . ' MVR',
            'employees'       => $list,
        ];
    }

    /**
     * List business tables (for schema discovery), optionally filtered by a
     * keyword. Auth/system tables are hidden.
     */
    private static function listTables(array $args): array
    {
        $keyword = strtolower(trim($args['keyword'] ?? ''));

        try {
            $raw = DB::connection('mysql_readonly')->select('SHOW TABLES');
        } catch (\Throwable $e) {
            Log::warning('Wisdom AI list_tables failed', ['error' => $e->getMessage()]);
            return ['error' => 'Could not list tables right now.'];
        }

        $tables = [];
        foreach ($raw as $row) {
            $name = array_values((array) $row)[0] ?? null;
            if (!$name || ReadQueryGuard::isDeniedTable($name)) {
                continue;
            }
            if ($keyword !== '' && strpos(strtolower($name), $keyword) === false) {
                continue;
            }
            $tables[] = $name;
        }
        sort($tables);

        // Bound the payload — there are hundreds of tables.
        $shown = array_slice($tables, 0, 80);

        return [
            'keyword'     => $keyword !== '' ? $keyword : null,
            'match_count' => count($tables),
            'tables'      => $shown,
            'truncated'   => count($tables) > count($shown),
            'note'        => count($tables) > count($shown) ? 'Too many matches — refine with a more specific keyword.' : null,
        ];
    }

    /**
     * Describe a table's columns (for schema discovery). Credential columns and
     * denied tables are not exposed.
     */
    private static function describeTable(array $args): array
    {
        $table = trim($args['table'] ?? '');
        if ($table === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return ['error' => 'Provide a valid table name (letters, numbers, underscores).'];
        }
        if (ReadQueryGuard::isDeniedTable($table)) {
            return ['error' => "Table \"{$table}\" is restricted."];
        }

        try {
            if (!Schema::connection('mysql_readonly')->hasTable($table)) {
                return ['error' => "Table \"{$table}\" does not exist. Use list_tables to find the right name."];
            }
            $columns = Schema::connection('mysql_readonly')->getColumnListing($table);
        } catch (\Throwable $e) {
            Log::warning('Wisdom AI describe_table failed', ['error' => $e->getMessage()]);
            return ['error' => 'Could not describe this table right now.'];
        }

        $columns = array_values(array_filter($columns, fn ($c) => !ReadQueryGuard::isSensitiveColumn($c)));
        $hasResortId = in_array('resort_id', array_map('strtolower', $columns), true);

        return [
            'table'           => $table,
            'columns'         => $columns,
            'has_resort_id'   => $hasResortId,
            'scoping_hint'    => $hasResortId
                ? "Scope with `{$table}.resort_id = :resort_id`."
                : 'No resort_id column — join through a table that has one (e.g. employees) and scope on that.',
        ];
    }

    /**
     * Ad-hoc read-only query. Every safety check lives in ReadQueryGuard; here
     * we bind the resort id, run it on the read-only connection, and strip any
     * credential columns from the rows as a final safety net.
     */
    private static function runReadQuery(int $rid, array $args): array
    {
        $check = ReadQueryGuard::validate((string) ($args['sql'] ?? ''));
        if (empty($check['ok'])) {
            return ['error' => $check['error'], 'hint' => 'Fix the SQL to satisfy this rule (use list_tables / describe_table if unsure), or fall back to a dedicated tool.'];
        }

        try {
            $rows = DB::connection('mysql_readonly')
                ->select($check['sql'], ['resort_id' => $rid]);
        } catch (\Throwable $e) {
            Log::warning('Wisdom AI run_read_query failed', ['error' => $e->getMessage()]);
            // Surface the DB's own message (column/table not found etc.) so the
            // model can correct itself — it's not sensitive and aids recovery.
            return [
                'error' => 'Query failed: ' . self::dbErrorHint($e->getMessage()),
                'hint'  => 'Use describe_table to confirm the real column names, then retry once.',
            ];
        }

        $rows = array_map(function ($r) {
            $arr = (array) $r;
            foreach (array_keys($arr) as $k) {
                if (ReadQueryGuard::isSensitiveColumn($k)) {
                    unset($arr[$k]);
                }
            }
            return $arr;
        }, $rows);

        return [
            'row_count' => count($rows),
            'truncated' => count($rows) >= ReadQueryGuard::MAX_ROWS,
            'rows'      => $rows,
        ];
    }

    /** Extract the meaningful part of a MySQL error for the model. */
    private static function dbErrorHint(string $msg): string
    {
        if (preg_match("/Unknown column '([^']+)'/", $msg, $m)) {
            return "unknown column '{$m[1]}'.";
        }
        if (preg_match("/Table '[^']*\.([^']+)' doesn't exist/", $msg, $m)) {
            return "table '{$m[1]}' doesn't exist.";
        }
        if (stripos($msg, 'syntax') !== false) {
            return 'SQL syntax error.';
        }
        return 'check the table and column names.';
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    /**
     * Employee display name. The `employees` table has no name columns — the
     * name lives on the linked resort_admins row (resortAdmin relation).
     */
    // ---------------------------------------------------------------------
    // Performance Management
    // ---------------------------------------------------------------------

    private static function getPerformanceSummary(int $rid): array
    {
        $activeCycleIds = PerformanceCycle::where('resort_id', $rid)
            ->whereIn('status', ['OnGoing', 'Pending'])->pluck('id');

        return [
            'appraisals_pending' => PerformaChildCycle::whereIn('Parent_cycle_id', $activeCycleIds)
                ->where('manager_review_status', 'pending')->count(),
            'employees_on_pip'  => EmployeePipPlan::where('resort_id', $rid)->where('status', 'active')->count(),
            'employees_on_pdp'  => EmployeePdpPlan::where('resort_id', $rid)->where('status', 'active')->count(),
            'active_cycles'     => PerformanceCycle::where('resort_id', $rid)->where('status', 'OnGoing')->count(),
            'pending_cycles'    => PerformanceCycle::where('resort_id', $rid)->where('status', 'Pending')->count(),
            'total_kpis'        => PerformanceKpiParent::where('resort_id', $rid)->count(),
            'checkins_pending'  => MonthlyCheckingModel::where('resort_id', $rid)->where('approval_status', 'pending')->count(),
            'checkins_approved' => MonthlyCheckingModel::where('resort_id', $rid)->where('approval_status', 'approved')->count(),
        ];
    }

    private static function getPlanOverview(int $rid, array $args, string $type): array
    {
        /** @var class-string $model */
        $model = $type === 'pip' ? EmployeePipPlan::class : EmployeePdpPlan::class;
        $status = strtolower(trim($args['status'] ?? 'active'));

        $q = $model::where('resort_id', $rid)
            ->with([
                'employee:id,Emp_id,Admin_Parent_id,Dept_id',
                'employee.resortAdmin:id,first_name,last_name',
                'employee.department:id,name',
            ]);
        if ($status !== 'all' && $status !== '') {
            $q->where('status', $status);
        }
        $dept = trim($args['department'] ?? '');
        if ($dept !== '') {
            $q->whereHas('employee.department', fn ($d) => $d->where('name', 'like', "%{$dept}%"));
        }

        $rows = $q->orderByDesc('id')->limit(100)->get();
        $list = $rows->map(fn ($p) => [
            'employee'   => $p->employee ? self::empName($p->employee) : 'Unknown',
            'department' => optional(optional($p->employee)->department)->name,
            'duration'   => $p->duration,
            'status'     => $p->status,
        ])->values();

        $byDept = $list->groupBy(fn ($r) => $r['department'] ?: 'Unassigned')->map->count();

        return [
            'plan'          => strtoupper($type),
            'status_filter' => $status,
            'count'         => $list->count(),
            'by_department' => $byDept,
            'employees'     => $list,
        ];
    }

    private static function getAppraisalStatus(int $rid): array
    {
        $activeCycleIds = PerformanceCycle::where('resort_id', $rid)
            ->whereIn('status', ['OnGoing', 'Pending'])->pluck('id');

        if ($activeCycleIds->isEmpty()) {
            return ['message' => 'No active or pending performance cycles right now.', 'total_appraisals' => 0];
        }

        $total = PerformaChildCycle::whereIn('Parent_cycle_id', $activeCycleIds)->count();
        $done = PerformaChildCycle::whereIn('Parent_cycle_id', $activeCycleIds)
            ->where(function ($q) {
                $q->where('manager_review_status', 'completed')
                  ->orWhere(function ($q2) {
                      $q2->where('manager_review_status', 'not_applicable')->where('self_review_status', 'completed');
                  });
            })->count();
        $managerPending = PerformaChildCycle::whereIn('Parent_cycle_id', $activeCycleIds)
            ->where('manager_review_status', 'pending')->count();
        $selfPending = PerformaChildCycle::whereIn('Parent_cycle_id', $activeCycleIds)
            ->where('self_review_status', 'pending')->count();

        return [
            'total_appraisals'       => $total,
            'completed'              => $done,
            'manager_review_pending' => $managerPending,
            'self_review_pending'    => $selfPending,
            'completion_rate_pct'    => $total > 0 ? round($done / $total * 100, 1) : 0,
        ];
    }

    private static function getPerformanceCycles(int $rid): array
    {
        $cycles = PerformanceCycle::where('resort_id', $rid)->orderByDesc('id')->limit(50)
            ->get(['id', 'Cycle_Name', 'Start_Date', 'End_Date', 'status']);
        $label = ['OnGoing' => 'Active', 'Pending' => 'Pending', 'Close' => 'Closed'];

        $list = $cycles->map(fn ($c) => [
            'name'   => $c->Cycle_Name,
            'status' => $label[$c->status] ?? $c->status,
            'start'  => $c->getRawOriginal('Start_Date'),
            'end'    => $c->getRawOriginal('End_Date'),
        ])->values();
        $byStatus = $cycles->groupBy(fn ($c) => $label[$c->status] ?? $c->status)->map->count();

        return ['total' => $cycles->count(), 'by_status' => $byStatus, 'cycles' => $list];
    }

    private static function getKpiOverview(int $rid): array
    {
        $byStatus = PerformanceKpiParent::where('resort_id', $rid)
            ->select('status', DB::raw('COUNT(*) as c'))->groupBy('status')->pluck('c', 'status');
        return [
            'total_kpis' => array_sum($byStatus->toArray()),
            'by_status'  => $byStatus->toArray(),
        ];
    }

    private static function getMonthlyCheckins(int $rid): array
    {
        $byStatus = MonthlyCheckingModel::where('resort_id', $rid)
            ->select('status', DB::raw('COUNT(*) as c'))->groupBy('status')->pluck('c', 'status');
        $byApproval = MonthlyCheckingModel::where('resort_id', $rid)
            ->select('approval_status', DB::raw('COUNT(*) as c'))->groupBy('approval_status')->pluck('c', 'approval_status');
        return [
            'total'       => array_sum($byStatus->toArray()),
            'by_status'   => $byStatus->toArray(),
            'by_approval' => $byApproval->toArray(),
        ];
    }

    private static function getPerformanceMeetings(int $rid, array $args): array
    {
        $date = self::cleanDate($args['date'] ?? null);
        $meetings = PeformanceMeeting::where('resort_id', $rid)
            ->whereNotNull('date')->whereDate('date', '>=', $date)
            ->withCount('participants')
            ->orderBy('date')->limit(50)
            ->get(['id', 'title', 'date', 'start_time', 'end_time', 'location']);

        $list = $meetings->map(fn ($m) => [
            'title'        => $m->title,
            'date'         => $m->getRawOriginal('date'),
            'start_time'   => $m->start_time,
            'location'     => $m->location,
            'participants' => $m->participants_count,
        ])->values();

        return ['from_date' => $date, 'count' => $list->count(), 'meetings' => $list];
    }

    private static function getEmployeePerformance(int $rid, array $args): array
    {
        $term = trim($args['name'] ?? '');
        if ($term === '') {
            return ['error' => 'Please provide an employee name.'];
        }

        $emp = Employee::where('resort_id', $rid)
            ->where(function ($q) use ($term) {
                $q->whereHas('resortAdmin', function ($r) use ($term) {
                      $r->where('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%")
                        ->orWhereRaw("CONCAT(first_name,' ',last_name) LIKE ?", ["%{$term}%"]);
                  })->orWhere('Emp_id', 'like', "%{$term}%");
            })
            ->with('resortAdmin:id,first_name,last_name')
            ->first();

        if (!$emp) {
            return ['query' => $term, 'found' => false, 'message' => 'No matching employee found.'];
        }

        $pip = EmployeePipPlan::where('resort_id', $rid)->where('employee_id', $emp->id)->latest('id')->first();
        $pdp = EmployeePdpPlan::where('resort_id', $rid)->where('employee_id', $emp->id)->latest('id')->first();
        $child = PerformaChildCycle::where(function ($q) use ($emp) {
            $q->where('Emp_main_id', (string) $emp->id)->orWhere('Emp_main_id', $emp->Emp_id);
        })->latest('id')->first();
        $checkin = MonthlyCheckingModel::where('resort_id', $rid)
            ->where(function ($q) use ($emp) {
                $q->where('emp_id', (string) $emp->id)->orWhere('emp_id', $emp->Emp_id);
            })->latest('id')->first();

        return [
            'employee'         => self::empName($emp),
            'pip'              => $pip ? ['status' => $pip->status, 'duration' => $pip->duration] : 'No PIP record',
            'pdp'              => $pdp ? ['status' => $pdp->status, 'duration' => $pdp->duration] : 'No PDP record',
            'latest_appraisal' => $child ? ['self_review' => $child->self_review_status, 'manager_review' => $child->manager_review_status] : 'No appraisal record',
            'latest_checkin'   => $checkin ? ['status' => $checkin->status, 'approval' => $checkin->approval_status, 'date' => $checkin->date_discussion] : 'No check-in record',
        ];
    }

    private static function empName(Employee $e): string
    {
        $ra = $e->resortAdmin;
        $name = $ra ? trim($ra->first_name . ' ' . $ra->last_name) : '';
        return $name !== '' ? $name : ('Employee ' . ($e->Emp_id ?: '#' . $e->id));
    }

    private static function cleanDate($value): string
    {
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($value))) {
            return trim($value);
        }
        return now()->toDateString();
    }

    /** Resort USD→MVR rate (DollertoMVR), defaulting to 15.42. */
    private static function dollarToMvr(int $rid): float
    {
        $r = (float) (DB::table('resort_site_settings')->where('resort_id', $rid)->value('DollertoMVR') ?: 15.42);
        return $r > 0 ? $r : 15.42;
    }

    /**
     * Format a money amount in BOTH USD and MVR so the assistant can answer in
     * either currency or show a conversion. $src is the amount's stored
     * currency; the other side is derived from the resort USD↔MVR rate —
     * USD→MVR multiply, MVR→USD divide (never the stored inverse rate).
     */
    private static function dualMoney(float $amount, float $rate, string $src = 'USD'): array
    {
        $src = strtoupper(trim($src)) ?: 'USD';
        if ($src === 'MVR') {
            $mvr = $amount;
            $usd = $rate > 0 ? $amount / $rate : 0.0;
        } else {
            $usd = $amount;
            $mvr = $amount * $rate;
        }
        return [
            'usd' => 'USD ' . number_format($usd, 2),
            'mvr' => 'MVR ' . number_format($mvr, 2),
        ];
    }

    /**
     * Build a single OpenAI-compatible function tool definition.
     */
    private static function fn(string $name, string $description, array $properties, array $required = []): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name'        => $name,
                'description' => $description,
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => (object) $properties,
                    'required'   => $required,
                ],
            ],
        ];
    }
}
