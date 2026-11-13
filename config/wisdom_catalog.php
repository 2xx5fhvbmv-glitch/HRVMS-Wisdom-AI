<?php

/*
|--------------------------------------------------------------------------
| Wisdom AI data catalog — what WAI may query, and who may see what
|--------------------------------------------------------------------------
| WAI never writes SQL against these. It calls `query_data` with a dataset name, field names, filters, group-by and
| metrics; App\Services\Wisdom\DataCatalog checks every name against this file and builds the query with Laravel's
| query builder (bound values, read-only connection). Adding a question type = adding a dataset or a field here.
|
| Security model (enforced in code, not by the AI):
| - Every table, the base and every join, is filtered by the user's resort_id ('resort' column). A table without its
|   own resort column is reached only through a 'required' INNER join that has one (DataCatalog refuses otherwise).
| - A scoped user (tier 'leader': HOD / EXCOM / manager outside HR) only gets datasets with 'scope.department', and
|   only rows of their own department(s) (Common::getScopedDepartmentIds).
| - 'tiers' on a dataset limits who may use it at all; 'sensitivity' on a field limits who sees that field.
| - Credentials, passport/ID numbers, contact details, addresses, bank details, emergency contacts, free-text
|   statements and confidential cases (grievance / disciplinary) are simply not in the catalog.
| - 'col' / 'expr' / join 'on' are written by developers here; nothing from the AI is ever put into SQL text.
|
| Field keys: col (or expr), type (string|number|date|datetime), label, sensitivity (default 'general'),
| join (the join a field needs), values (known values, shown to the AI).
| Join keys: table, alias, on [a, b], resort (that table's resort column), needs (a join that must come first),
| type ('inner' | default 'left').
*/

// Shared field definitions
$name = fn (string $a) => ['expr' => "TRIM(CONCAT(COALESCE({$a}.first_name,''),' ',COALESCE({$a}.last_name,'')))", 'type' => 'string', 'join' => 'person', 'sensitivity' => 'personal', 'label' => 'Full name'];
$employeeJoins = fn (string $fk) => [
    'employee' => ['table' => 'employees', 'alias' => 'e', 'on' => ['e.id', $fk], 'resort' => 'e.resort_id'],
    'person'   => ['table' => 'resort_admins', 'alias' => 'ra', 'on' => ['ra.id', 'e.Admin_Parent_id'], 'resort' => 'ra.resort_id', 'needs' => 'employee'],
    'dept'     => ['table' => 'resort_departments', 'alias' => 'd', 'on' => ['d.id', 'e.Dept_id'], 'resort' => 'd.resort_id', 'needs' => 'employee'],
];
$employeeFields = [
    'employee_id' => ['col' => 'e.Emp_id', 'type' => 'string', 'join' => 'employee', 'label' => 'Employee ID'],
    'name'        => $name('ra'),
    'department'  => ['col' => 'd.name', 'type' => 'string', 'join' => 'dept'],
];
$byEmployeeDept = ['department' => 'e.Dept_id', 'needs' => 'employee'];

return [

    // Which WAI tiers (App\Services\Wisdom\WisdomAccess) may read a field of each sensitivity.
    'sensitivity' => [
        'general'      => ['full', 'gm', 'leader', 'finance'],
        'personal'     => ['full', 'gm', 'leader'],   // names, gender
        'private'      => ['full', 'gm'],             // date of birth and similar
        'compensation' => ['full', 'finance'],        // pay (GM excluded: founder decision 2026-11-02)
    ],

    // Tiers limited to a fixed list of datasets (every other tier: any dataset its 'tiers' allows).
    'tier_datasets' => [
        'finance' => ['payroll_runs', 'payslips', 'salary_advances', 'employees'],   // employees: headcount / department for cost per head
    ],

    'limits' => [
        'rows'       => 200,
        'filters'    => 12,
        'group_by'   => 3,
        'metrics'    => 6,
        'in_values'  => 100,
        'timeout_ms' => 8000,
    ],

    'datasets' => [

        // ── People ────────────────────────────────────────────────────────────────────────────────────────────
        'employees' => [
            'module'      => 'People',
            'description' => 'One row per employee (all statuses; filter status = Active for current staff).',
            'table'       => 'employees', 'alias' => 'e',
            'resort'      => 'e.resort_id',
            'scope'       => ['department' => 'e.Dept_id'],
            'where_null'  => ['e.deleted_at'],
            'joins' => [
                'person'   => ['table' => 'resort_admins', 'alias' => 'ra', 'on' => ['ra.id', 'e.Admin_Parent_id'], 'resort' => 'ra.resort_id'],
                'dept'     => ['table' => 'resort_departments', 'alias' => 'd', 'on' => ['d.id', 'e.Dept_id'], 'resort' => 'd.resort_id'],
                'position' => ['table' => 'resort_positions', 'alias' => 'p', 'on' => ['p.id', 'e.Position_id'], 'resort' => 'p.resort_id'],
                'division' => ['table' => 'resort_divisions', 'alias' => 'dv', 'on' => ['dv.id', 'e.division_id'], 'resort' => 'dv.resort_id'],
            ],
            'fields' => [
                'employee_id'       => ['col' => 'e.Emp_id', 'type' => 'string', 'label' => 'Employee ID'],
                'name'              => $name('ra'),
                'department'        => ['col' => 'd.name', 'type' => 'string', 'join' => 'dept'],
                'position'          => ['col' => 'p.position_title', 'type' => 'string', 'join' => 'position'],
                'division'          => ['col' => 'dv.name', 'type' => 'string', 'join' => 'division'],
                'status'            => ['col' => 'e.status', 'type' => 'string', 'values' => ['Active', 'Onboarding', 'Offboarding', 'Inactive', 'Terminated']],
                'employment_type'   => ['col' => 'e.employment_type', 'type' => 'string', 'values' => ['Full-Time', 'Probationary', 'Casual', 'Internship']],
                'nationality'       => ['col' => 'e.nationality', 'type' => 'string'],
                'gender'            => ['col' => 'ra.gender', 'type' => 'string', 'join' => 'person', 'sensitivity' => 'personal'],
                'joining_date'      => ['col' => 'e.joining_date', 'type' => 'date'],
                'probation_status'  => ['col' => 'e.probation_status', 'type' => 'string'],
                'probation_end_date'=> ['col' => 'e.probation_end_date', 'type' => 'date'],
                'contract_end_date' => ['col' => 'e.contract_end_date', 'type' => 'date'],
                'last_working_day'  => ['col' => 'e.last_working_day', 'type' => 'date'],
                'date_of_birth'     => ['col' => 'e.dob', 'type' => 'date', 'sensitivity' => 'private'],
                'visa_expiry_date'  => ['col' => 'e.visa_expiry_date', 'type' => 'date'],
                'work_permit_expiry_date' => ['col' => 'e.work_permit_expiry_date', 'type' => 'date'],
                'basic_salary'      => ['col' => 'e.basic_salary', 'type' => 'number', 'sensitivity' => 'compensation', 'label' => 'Basic salary (in basic_salary_currency)'],
                'basic_salary_currency' => ['col' => 'e.basic_salary_currency', 'type' => 'string', 'sensitivity' => 'compensation'],
            ],
        ],

        'resignations' => [
            'module'      => 'People',
            'description' => 'One row per resignation. status is the overall state; hod_status / hr_status are the approval steps.',
            'table'       => 'employee_resignation', 'alias' => 'r',
            'resort'      => 'r.resort_id',
            'scope'       => $byEmployeeDept,
            'joins'       => $employeeJoins('r.employee_id') + [
                'reason' => ['table' => 'employee_resignation_reasons', 'alias' => 'rr', 'on' => ['rr.id', 'r.reason'], 'resort' => 'rr.resort_id'],
            ],
            'fields' => $employeeFields + [
                'reason'           => ['col' => 'rr.reason', 'type' => 'string', 'join' => 'reason'],
                'resignation_date' => ['col' => 'r.resignation_date', 'type' => 'date'],
                'last_working_day' => ['col' => 'r.last_working_day', 'type' => 'date'],
                'status'           => ['col' => 'r.status', 'type' => 'string'],
                'hod_status'       => ['col' => 'r.hod_status', 'type' => 'string'],
                'hr_status'        => ['col' => 'r.hr_status', 'type' => 'string'],
            ],
        ],

        'promotions' => [
            'module'      => 'People',
            'description' => 'One row per promotion request (current → new position).',
            'table'       => 'employee_promotions', 'alias' => 'pm',
            'resort'      => 'pm.resort_id',
            'scope'       => $byEmployeeDept,
            'joins'       => $employeeJoins('pm.employee_id') + [
                'from_position' => ['table' => 'resort_positions', 'alias' => 'cp', 'on' => ['cp.id', 'pm.current_position_id'], 'resort' => 'cp.resort_id'],
                'to_position'   => ['table' => 'resort_positions', 'alias' => 'np', 'on' => ['np.id', 'pm.new_position_id'], 'resort' => 'np.resort_id'],
            ],
            'fields' => $employeeFields + [
                'current_position'        => ['col' => 'cp.position_title', 'type' => 'string', 'join' => 'from_position'],
                'new_position'            => ['col' => 'np.position_title', 'type' => 'string', 'join' => 'to_position'],
                'effective_date'          => ['col' => 'pm.effective_date', 'type' => 'date'],
                'status'                  => ['col' => 'pm.status', 'type' => 'string'],
                'salary_increment_percent'=> ['col' => 'pm.salary_increment_percent', 'type' => 'number', 'sensitivity' => 'compensation'],
                'new_salary'              => ['col' => 'pm.new_salary', 'type' => 'number', 'sensitivity' => 'compensation'],
            ],
        ],

        'transfers' => [
            'module'      => 'People',
            'description' => 'One row per department / position transfer.',
            'table'       => 'employee_transfers', 'alias' => 't',
            'resort'      => 't.resort_id',
            'scope'       => $byEmployeeDept,
            'joins'       => $employeeJoins('t.employee_id') + [
                'from_dept' => ['table' => 'resort_departments', 'alias' => 'fd', 'on' => ['fd.id', 't.current_department_id'], 'resort' => 'fd.resort_id'],
                'to_dept'   => ['table' => 'resort_departments', 'alias' => 'td', 'on' => ['td.id', 't.target_department_id'], 'resort' => 'td.resort_id'],
            ],
            'fields' => $employeeFields + [
                'from_department' => ['col' => 'fd.name', 'type' => 'string', 'join' => 'from_dept'],
                'to_department'   => ['col' => 'td.name', 'type' => 'string', 'join' => 'to_dept'],
                'effective_date'  => ['col' => 't.effective_date', 'type' => 'date'],
                'status'          => ['col' => 't.status', 'type' => 'string'],
                'transfer_status' => ['col' => 't.transfer_status', 'type' => 'string', 'label' => 'Permanent / temporary'],
            ],
        ],

        // ── Leave ─────────────────────────────────────────────────────────────────────────────────────────────
        'leave_requests' => [
            'module'      => 'Leave',
            'description' => 'One row per leave request. total_days = days requested; status Pending / Approved / Rejected / Cancelled.',
            'table'       => 'employees_leaves', 'alias' => 'l',
            'resort'      => 'l.resort_id',
            'scope'       => $byEmployeeDept,
            'joins'       => $employeeJoins('l.emp_id') + [
                'category' => ['table' => 'leave_categories', 'alias' => 'c', 'on' => ['c.id', 'l.leave_category_id'], 'resort' => 'c.resort_id'],
            ],
            'fields' => $employeeFields + [
                'leave_type'  => ['col' => 'c.leave_type', 'type' => 'string', 'join' => 'category'],
                'is_paid_type'=> ['col' => 'c.is_paid', 'type' => 'number', 'join' => 'category', 'label' => '1 = paid leave type'],
                'from_date'   => ['col' => 'l.from_date', 'type' => 'date'],
                'to_date'     => ['col' => 'l.to_date', 'type' => 'date'],
                'total_days'  => ['col' => 'l.total_days', 'type' => 'number'],
                'status'      => ['col' => 'l.status', 'type' => 'string', 'values' => ['Pending', 'Approved', 'Rejected', 'Cancelled']],
                'requested_at'=> ['col' => 'l.created_at', 'type' => 'datetime'],
            ],
        ],

        'island_passes' => [
            'module'      => 'Leave',
            'description' => 'One row per island / boarding pass (departure and arrival).',
            'table'       => 'employee_travel_passes', 'alias' => 'tp',
            'resort'      => 'tp.resort_id',
            'scope'       => $byEmployeeDept,
            'joins'       => $employeeJoins('tp.employee_id'),
            'fields' => $employeeFields + [
                'departure_date' => ['col' => 'tp.departure_date', 'type' => 'date'],
                'arrival_date'   => ['col' => 'tp.arrival_date', 'type' => 'date'],
                'transportation' => ['col' => 'tp.transportation', 'type' => 'string'],
                'status'         => ['col' => 'tp.status', 'type' => 'string', 'values' => ['Pending', 'Approved', 'Rejected', 'Cancel']],
            ],
        ],

        // ── Time & Attendance ─────────────────────────────────────────────────────────────────────────────────
        'attendance' => [
            'module'      => 'Time and Attendance',
            'description' => 'One row per employee per day actually recorded (no row = absent). overtime_hours from the punch record.',
            'table'       => 'parent_attendaces', 'alias' => 'a',
            'resort'      => 'a.resort_id',
            'scope'       => $byEmployeeDept,
            'where'       => [['a.date', '>=', '2000-01-01']],   // old test rows carry 1970 dates
            'joins'       => $employeeJoins('a.Emp_id'),
            'fields' => $employeeFields + [
                'date'           => ['col' => 'a.date', 'type' => 'date'],
                'status'         => ['col' => 'a.Status', 'type' => 'string', 'values' => ['Present', 'Absent', 'DayOff', 'FullDayLeave', 'HalfDayLeave']],
                'check_in'       => ['col' => 'a.CheckingTime', 'type' => 'string'],
                'check_out'      => ['col' => 'a.CheckingOutTime', 'type' => 'string'],
                'overtime_hours' => ['expr' => "(CASE WHEN a.OverTime LIKE '%:%' THEN SUBSTRING_INDEX(a.OverTime,':',1) + SUBSTRING_INDEX(a.OverTime,':',-1) / 60 ELSE 0 END)", 'type' => 'number'],
                'overtime_status'=> ['col' => 'a.OTStatus', 'type' => 'string'],
            ],
        ],

        // ── Payroll (HR and Finance only) ─────────────────────────────────────────────────────────────────────
        'payroll_runs' => [
            'module'      => 'Payroll',
            'tiers'       => ['full', 'finance'],
            'description' => 'One row per payroll run (Permanent / Casual). status draft → approved → locked; total_payroll in the run currency.',
            'table'       => 'payroll', 'alias' => 'p',
            'resort'      => 'p.resort_id',
            'fields' => [
                'category'        => ['col' => 'p.payroll_category', 'type' => 'string', 'values' => ['Permanent', 'Casual']],
                'start_date'      => ['col' => 'p.start_date', 'type' => 'date'],
                'end_date'        => ['col' => 'p.end_date', 'type' => 'date'],
                'status'          => ['col' => 'p.status', 'type' => 'string', 'values' => ['draft', 'approved', 'locked']],
                'total_employees' => ['col' => 'p.total_employees', 'type' => 'number'],
                'currency'        => ['col' => 'p.payroll_unit', 'type' => 'string'],
                'total_payroll'   => ['col' => 'p.total_payroll', 'type' => 'number', 'sensitivity' => 'compensation'],
                'payment_date'    => ['col' => 'p.payment_date', 'type' => 'date'],
            ],
        ],

        'payslips' => [
            'module'      => 'Payroll',
            'tiers'       => ['full', 'finance'],
            'description' => 'One row per employee per payroll run: earnings, overtime pay, allowances, service charge, deductions, net pay. Join the run with run_start_date / payroll_category / run_status.',
            'table'       => 'payroll_reviews', 'alias' => 'pr',
            'resort'      => null,                 // no resort column: anchored by the required 'run' join
            'required'    => ['run'],
            'scope'       => $byEmployeeDept,
            'joins'       => ['run' => ['table' => 'payroll', 'alias' => 'p', 'on' => ['p.id', 'pr.payroll_id'], 'resort' => 'p.resort_id', 'type' => 'inner']]
                + $employeeJoins('pr.employee_id'),
            'fields' => $employeeFields + [
                'run_start_date'    => ['col' => 'p.start_date', 'type' => 'date', 'join' => 'run'],
                'run_end_date'      => ['col' => 'p.end_date', 'type' => 'date', 'join' => 'run'],
                'payroll_category'  => ['col' => 'p.payroll_category', 'type' => 'string', 'join' => 'run'],
                'run_status'        => ['col' => 'p.status', 'type' => 'string', 'join' => 'run'],
                'currency'          => ['col' => 'p.payroll_unit', 'type' => 'string', 'join' => 'run'],
                'earned_salary'     => ['col' => 'pr.earned_salary', 'type' => 'number', 'sensitivity' => 'compensation'],
                'overtime_pay'      => ['col' => 'pr.earnings_overtime', 'type' => 'number', 'sensitivity' => 'compensation'],
                'allowances'        => ['col' => 'pr.earnings_allowance', 'type' => 'number', 'sensitivity' => 'compensation'],
                'service_charge'    => ['col' => 'pr.service_charge', 'type' => 'number', 'sensitivity' => 'compensation'],
                'kpi_bonus'         => ['col' => 'pr.kpi_bonus', 'type' => 'number', 'sensitivity' => 'compensation'],
                'total_earnings'    => ['col' => 'pr.total_earnings', 'type' => 'number', 'sensitivity' => 'compensation'],
                'total_deductions'  => ['col' => 'pr.total_deductions', 'type' => 'number', 'sensitivity' => 'compensation'],
                'net_salary'        => ['col' => 'pr.net_salary', 'type' => 'number', 'sensitivity' => 'compensation'],
            ],
        ],

        'salary_advances' => [
            'module'      => 'Payroll',
            'tiers'       => ['full', 'finance'],
            'description' => 'One row per salary advance / loan request, with the HR → Finance → GM approval steps.',
            'table'       => 'payroll_advance', 'alias' => 'pa',
            'resort'      => 'pa.resort_id',
            'scope'       => $byEmployeeDept,
            'joins'       => $employeeJoins('pa.employee_id'),
            'fields' => $employeeFields + [
                'request_type'    => ['col' => 'pa.request_type', 'type' => 'string'],
                'request_date'    => ['col' => 'pa.request_date', 'type' => 'date'],
                'amount'          => ['col' => 'pa.request_amount', 'type' => 'number', 'sensitivity' => 'compensation'],
                'currency'        => ['col' => 'pa.currency', 'type' => 'string'],
                'status'          => ['col' => 'pa.status', 'type' => 'string', 'values' => ['Pending', 'In-Progress', 'Approved', 'Rejected']],
                'hr_status'       => ['col' => 'pa.hr_status', 'type' => 'string'],
                'finance_status'  => ['col' => 'pa.finance_status', 'type' => 'string'],
                'gm_status'       => ['col' => 'pa.gm_status', 'type' => 'string'],
                'recovery_status' => ['col' => 'pa.recovery_status', 'type' => 'string'],
            ],
        ],

        // ── Talent Acquisition ────────────────────────────────────────────────────────────────────────────────
        'vacancies' => [
            'module'      => 'Talent Acquisition',
            'description' => 'One row per vacancy (positions_required = seats).',
            'table'       => 'vacancies', 'alias' => 'v',
            'resort'      => 'v.Resort_id',
            'scope'       => ['department' => 'v.department'],
            'joins' => [
                'dept'     => ['table' => 'resort_departments', 'alias' => 'd', 'on' => ['d.id', 'v.department'], 'resort' => 'd.resort_id'],
                'position' => ['table' => 'resort_positions', 'alias' => 'p', 'on' => ['p.id', 'v.position'], 'resort' => 'p.resort_id'],
            ],
            'fields' => [
                'department'           => ['col' => 'd.name', 'type' => 'string', 'join' => 'dept'],
                'position'             => ['col' => 'p.position_title', 'type' => 'string', 'join' => 'position'],
                'status'               => ['col' => 'v.status', 'type' => 'string'],
                'positions_required'   => ['col' => 'v.Total_position_required', 'type' => 'number'],
                'required_starting_date' => ['col' => 'v.required_starting_date', 'type' => 'date'],
                'employee_type'        => ['col' => 'v.employee_type', 'type' => 'string'],
                'is_replacement'       => ['col' => 'v.is_replacement', 'type' => 'number', 'label' => '1 = replacing a leaver'],
                'is_required_local'    => ['col' => 'v.is_required_local', 'type' => 'number', 'label' => '1 = Maldivian only'],
                'out_of_budget_status' => ['col' => 'v.out_of_budget_status', 'type' => 'string'],
                'budgeted_salary'      => ['col' => 'v.budgeted_salary', 'type' => 'number', 'sensitivity' => 'compensation'],
                'created_at'           => ['col' => 'v.created_at', 'type' => 'datetime'],
            ],
        ],

        'applicants' => [
            'module'      => 'Talent Acquisition',
            'description' => 'One row per job application, with the vacancy it was for. ai_ranking / score from CV screening.',
            'table'       => 'applicant_form_data', 'alias' => 'ap',
            'resort'      => 'ap.resort_id',
            'scope'       => ['department' => 'v.department', 'needs' => 'vacancy'],
            'joins' => [
                'vacancy'  => ['table' => 'vacancies', 'alias' => 'v', 'on' => ['v.id', 'ap.Parent_v_id'], 'resort' => 'v.Resort_id'],
                'dept'     => ['table' => 'resort_departments', 'alias' => 'd', 'on' => ['d.id', 'v.department'], 'resort' => 'd.resort_id', 'needs' => 'vacancy'],
                'position' => ['table' => 'resort_positions', 'alias' => 'p', 'on' => ['p.id', 'v.position'], 'resort' => 'p.resort_id', 'needs' => 'vacancy'],
            ],
            'fields' => [
                'name'             => ['expr' => "TRIM(CONCAT(COALESCE(ap.first_name,''),' ',COALESCE(ap.last_name,'')))", 'type' => 'string', 'sensitivity' => 'personal'],
                'gender'           => ['col' => 'ap.gender', 'type' => 'string', 'sensitivity' => 'personal'],
                'position'         => ['col' => 'p.position_title', 'type' => 'string', 'join' => 'position'],
                'department'       => ['col' => 'd.name', 'type' => 'string', 'join' => 'dept'],
                'application_date' => ['col' => 'ap.Application_date', 'type' => 'date'],
                'source'           => ['col' => 'ap.Applicant_Source', 'type' => 'string'],
                'total_experience' => ['col' => 'ap.Total_Experiance', 'type' => 'string'],
                'notice_period'    => ['col' => 'ap.NotiesPeriod', 'type' => 'string'],
                'ai_ranking'       => ['col' => 'ap.AIRanking', 'type' => 'number'],
                'score'            => ['col' => 'ap.Scoring', 'type' => 'number'],
                'salary_expectation' => ['col' => 'ap.SalaryExpectation', 'type' => 'string', 'sensitivity' => 'compensation'],
            ],
        ],

        // ── Learning ──────────────────────────────────────────────────────────────────────────────────────────
        'training_participants' => [
            'module'      => 'Learning',
            'description' => 'One row per employee per scheduled training session.',
            'table'       => 'training_participants', 'alias' => 'tpp',
            'resort'      => null,                 // anchored by the required 'schedule' join
            'required'    => ['schedule'],
            'scope'       => $byEmployeeDept,
            'joins' => [
                'schedule' => ['table' => 'training_schedules', 'alias' => 'ts', 'on' => ['ts.id', 'tpp.training_schedule_id'], 'resort' => 'ts.resort_id', 'type' => 'inner'],
                'program'  => ['table' => 'learning_programs', 'alias' => 'lp', 'on' => ['lp.id', 'ts.training_id'], 'resort' => 'lp.resort_id', 'needs' => 'schedule'],
            ] + $employeeJoins('tpp.employee_id'),
            'fields' => $employeeFields + [
                'program'            => ['col' => 'lp.name', 'type' => 'string', 'join' => 'program'],
                'start_date'         => ['col' => 'ts.start_date', 'type' => 'date', 'join' => 'schedule'],
                'end_date'           => ['col' => 'ts.end_date', 'type' => 'date', 'join' => 'schedule'],
                'session_status'     => ['col' => 'ts.status', 'type' => 'string', 'join' => 'schedule'],
                'participant_status' => ['col' => 'tpp.status', 'type' => 'string'],
                'attendance_date'    => ['col' => 'tpp.attendance_date', 'type' => 'date'],
            ],
        ],

        // ── Accommodation (resort-wide facilities: not for department-scoped users) ───────────────────────────
        'accommodation_rooms' => [
            'module'      => 'Accommodation',
            'description' => 'One row per staff room: building, floor, beds, capacity, block_for (gender) and room_status.',
            'table'       => 'available_accommodation_models', 'alias' => 'am',
            'resort'      => 'am.resort_id',
            'joins' => [
                'building' => ['table' => 'building_models', 'alias' => 'b', 'on' => ['b.id', 'am.BuildingName'], 'resort' => 'b.resort_id'],
            ],
            'fields' => [
                'building'    => ['col' => 'b.BuildingName', 'type' => 'string', 'join' => 'building'],
                'floor'       => ['col' => 'am.Floor', 'type' => 'number'],
                'room'        => ['col' => 'am.RoomNo', 'type' => 'string'],
                'beds'        => ['col' => 'am.BedNo', 'type' => 'number'],
                'capacity'    => ['col' => 'am.Capacity', 'type' => 'number'],
                'block_for'   => ['col' => 'am.blockFor', 'type' => 'string', 'values' => ['Male', 'Female']],
                'room_status' => ['col' => 'am.RoomStatus', 'type' => 'string'],
            ],
        ],

        'accommodation_assignments' => [
            'module'      => 'Accommodation',
            'description' => 'One row per employee bed assignment.',
            'table'       => 'assing_accommodations', 'alias' => 'aa',
            'resort'      => 'aa.resort_id',
            'scope'       => $byEmployeeDept,
            'joins'       => $employeeJoins('aa.emp_id') + [
                'room'     => ['table' => 'available_accommodation_models', 'alias' => 'am', 'on' => ['am.id', 'aa.available_a_id'], 'resort' => 'am.resort_id'],
                'building' => ['table' => 'building_models', 'alias' => 'b', 'on' => ['b.id', 'am.BuildingName'], 'resort' => 'b.resort_id', 'needs' => 'room'],
            ],
            'fields' => $employeeFields + [
                'building'      => ['col' => 'b.BuildingName', 'type' => 'string', 'join' => 'building'],
                'room'          => ['col' => 'am.RoomNo', 'type' => 'string', 'join' => 'room'],
                'bed'           => ['col' => 'aa.BedNo', 'type' => 'string'],
                'assigned_from' => ['col' => 'aa.effected_date', 'type' => 'date'],
            ],
        ],

        'maintenance_requests' => [
            'module'      => 'Accommodation',
            'description' => 'One row per accommodation maintenance request.',
            'table'       => 'maintanace_requests', 'alias' => 'mr',
            'resort'      => 'mr.resort_id',
            'joins' => [
                'building' => ['table' => 'building_models', 'alias' => 'b', 'on' => ['b.id', 'mr.building_id'], 'resort' => 'b.resort_id'],
            ],
            'fields' => [
                'building'   => ['col' => 'b.BuildingName', 'type' => 'string', 'join' => 'building'],
                'floor'      => ['col' => 'mr.FloorNo', 'type' => 'string'],
                'room'       => ['col' => 'mr.RoomNo', 'type' => 'string'],
                'priority'   => ['col' => 'mr.priority', 'type' => 'string'],
                'status'     => ['col' => 'mr.Status', 'type' => 'string'],
                'date'       => ['col' => 'mr.date', 'type' => 'date'],
                'hold_until' => ['col' => 'mr.hold_until', 'type' => 'date'],
                'raised_at'  => ['col' => 'mr.created_at', 'type' => 'datetime'],
            ],
        ],

        // ── Incident (resort-wide) ────────────────────────────────────────────────────────────────────────────
        'incidents' => [
            'module'      => 'Incident',
            'description' => 'One row per reported incident (no people or statements, only the case facts).',
            'table'       => 'incidents', 'alias' => 'i',
            'resort'      => 'i.resort_id',
            'joins' => [
                'category' => ['table' => 'incident_categories', 'alias' => 'ic', 'on' => ['ic.id', 'i.category'], 'resort' => 'ic.resort_id'],
            ],
            'fields' => [
                'incident_id'   => ['col' => 'i.incident_id', 'type' => 'string'],
                'category'      => ['col' => 'ic.category_name', 'type' => 'string', 'join' => 'category'],
                'priority'      => ['col' => 'i.priority', 'type' => 'string'],
                'severity'      => ['col' => 'i.severity', 'type' => 'string'],
                'status'        => ['col' => 'i.status', 'type' => 'string'],
                'incident_date' => ['col' => 'i.incident_date', 'type' => 'date'],
                'location'      => ['col' => 'i.location', 'type' => 'string'],
                'outcome_type'  => ['col' => 'i.outcome_type', 'type' => 'string'],
                'resolved_at'   => ['col' => 'i.resolved_at', 'type' => 'datetime'],
            ],
        ],

        // Grievance & Disciplinary are deliberately not here: confidentiality (identity disclosure, committees)
        // is handled by their dedicated tools, which HR alone can use.
    ],
];
