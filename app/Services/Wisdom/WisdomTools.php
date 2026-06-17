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
use App\Models\ManningResponse;
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
    const PAYROLL_TOOLS = ['get_payroll_summary', 'get_employee_salary', 'get_budget_summary'];

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
            $tools[] = self::fn('get_budget_summary',
                'Get the resort HR salary-cost budget for a year (defaults to the current year): budgeted vs actual annual staff salary cost, the headcount and average salary it is based on. This mirrors the figure on the Workforce Planning HR dashboard. Use for "total HR budget", "salary budget this year" questions. Payroll-restricted.',
                [
                    'year' => ['type' => 'integer', 'description' => 'Four-digit year. Defaults to the current year.'],
                ]);

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
                case 'get_budget_summary':       return self::getBudgetSummary($rid, $args);
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

    /**
     * HR salary-cost budget for a year. Mirrors the Workforce Planning HR
     * dashboard: budgeted/actual annual staff cost = headcount × average basic
     * salary × 12. Budgeted headcount comes from the year's manning submission
     * (falls back to live active headcount when none exists). Money is in USD.
     */
    private static function getBudgetSummary(int $rid, array $args): array
    {
        $year = (int) ($args['year'] ?? 0);
        if ($year < 2000 || $year > 2100) {
            $year = (int) now()->format('Y');
        }

        $avgBasicSalary = (float) Employee::where('resort_id', $rid)
            ->where('status', 'Active')
            ->whereNotNull('basic_salary')
            ->avg('basic_salary');
        if ($avgBasicSalary <= 0) {
            $avgBasicSalary = 1500; // dashboard fallback
        }

        $liveActive = Employee::where('resort_id', $rid)->where('status', 'Active')->count();

        $manning = ManningResponse::where('resort_id', $rid)->where('year', $year)
            ->selectRaw('COALESCE(SUM(total_headcount),0) as budgeted, COALESCE(SUM(total_filled_positions),0) as filled')
            ->first();

        $budgetedHeadcount = (int) ($manning->budgeted ?? 0);
        $filledHeadcount   = (int) ($manning->filled ?? 0);
        $usedLiveFallback  = false;
        if ($budgetedHeadcount === 0 && $filledHeadcount === 0) {
            $budgetedHeadcount = $filledHeadcount = $liveActive;
            $usedLiveFallback  = true;
        }

        $budgetedAnnual = round($budgetedHeadcount * $avgBasicSalary * 12);
        $actualAnnual   = round($filledHeadcount * $avgBasicSalary * 12);

        return [
            'year'                   => $year,
            'budgeted_headcount'     => $budgetedHeadcount,
            'filled_headcount'       => $filledHeadcount,
            'average_basic_salary'   => Common::formatCurrency($avgBasicSalary, 'USD'),
            'budgeted_annual_cost'   => Common::formatCurrency((float) $budgetedAnnual, 'USD'),
            'actual_annual_cost'     => Common::formatCurrency((float) $actualAnnual, 'USD'),
            'basis'                  => 'Annual staff salary cost = headcount × average basic salary × 12 (same as the Workforce Planning HR dashboard).',
            'note'                   => $usedLiveFallback
                ? "No manning/budget submission found for {$year}; based on the current active headcount ({$liveActive})."
                : null,
        ];
    }

    private static function getPayrollSummary(int $rid): array
    {
        $p = Payroll::where('resort_id', $rid)->orderByDesc('id')->first();
        if (!$p) {
            return ['message' => 'No payroll records found for this resort yet.'];
        }

        $unit = $p->payroll_unit ?: 'USD';

        return [
            'period_start'    => $p->getRawOriginal('start_date'),
            'period_end'      => $p->getRawOriginal('end_date'),
            'status'          => $p->status,
            'payment_date'    => $p->getRawOriginal('payment_date'),
            'total_employees' => $p->total_employees,
            'total_payroll'   => Common::formatCurrency((float) $p->total_payroll, $unit),
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

        $list = $employees->map(fn ($e) => [
            'name'         => self::empName($e),
            'employee_id'  => $e->Emp_id,
            'department'   => $e->department->name ?? null,
            'position'     => $e->position->position_title ?? null,
            'basic_salary' => $e->basic_salary !== null
                ? Common::formatCurrency((float) $e->basic_salary, $e->basic_salary_currency ?: 'USD')
                : 'Not set',
        ])->values();

        return ['query' => $term, 'matches' => $list->count(), 'employees' => $list];
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
