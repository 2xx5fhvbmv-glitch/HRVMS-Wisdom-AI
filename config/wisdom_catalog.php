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

    // Older one-purpose WAI tools whose data a dataset above now covers with the same meaning. They are no longer
    // OFFERED to the model (WisdomTools::definitions), so it has one consistent path per question instead of
    // overlapping tools with different filters; execute() still runs them. Remove a name to offer it again.
    'replaces_tools' => [
        'get_headcount', 'get_department_breakdown', 'count_employees_by_status', 'list_employees',
        'get_nationality_breakdown', 'get_gender_breakdown', 'get_promotions', 'get_transfers', 'get_resignations',
        'get_employees_on_leave', 'get_vacancy_applicants', 'get_overtime', 'get_maintenance_requests', 'get_incidents',
        'get_boarding_pass_requests', 'get_visa_expiries', 'get_visa_liability', 'get_employee_documents',
        'list_tables', 'describe_table', 'run_read_query',
        'get_disciplinary_cases', 'get_grievance_cases', 'get_repeat_offenders', 'get_survey_participation', 'get_compliance_issues',
        'get_appraisal_status', 'get_pip_overview', 'get_incident_investigations', 'get_training_attendance', 'get_mandatory_training',
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
            'description' => 'One row per employee — THE real headcount (filter status = Active for current staff, the number to use for rates and staffing; budget / manning "filled" figures are planned seats, not people). Leavers: status Inactive / Terminated, exit date in last_working_day (resignation) or termination_date — when both are empty the exit date is unknown.',
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
                'probation_status'  => ['col' => 'e.probation_status', 'type' => 'string', 'values' => ['Active', 'Extended', 'Confirmed', 'Failed'],
                    'label' => 'Active or Extended = still on probation (even if probation_end_date has passed: that means the review is overdue)'],
                'probation_end_date'=> ['col' => 'e.probation_end_date', 'type' => 'date'],
                'contract_end_date' => ['col' => 'e.contract_end_date', 'type' => 'date'],
                'last_working_day'  => ['col' => 'e.last_working_day', 'type' => 'date'],
                'termination_date'  => ['col' => 'e.termination_date', 'type' => 'date'],
                'date_of_birth'     => ['col' => 'e.dob', 'type' => 'date', 'sensitivity' => 'private'],
                'visa_expiry_date'  => ['col' => 'e.visa_expiry_date', 'type' => 'date'],
                'work_permit_expiry_date' => ['col' => 'e.work_permit_expiry_date', 'type' => 'date'],
                'basic_salary'      => ['col' => 'e.basic_salary', 'type' => 'number', 'sensitivity' => 'compensation', 'label' => 'Basic salary (in basic_salary_currency)'],
                'basic_salary_currency' => ['col' => 'e.basic_salary_currency', 'type' => 'string', 'sensitivity' => 'compensation'],
            ],
        ],

        'resignations' => [
            'module'      => 'People',
            'description' => 'One row per resignation request. People who actually leave = status Approved; Withdraw = taken back and Pending = not decided, so never count those as leavers. Turnover rate for a period = approved resignations in the period (by resignation_date or last_working_day) ÷ current headcount (employees, status Active), per department or overall; compute the rate yourself from the two counts.',
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
                'status'           => ['col' => 'r.status', 'type' => 'string', 'values' => ['Pending', 'Approved', 'Withdraw', 'Rejected']],
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

        'leave_balances' => [
            'module'      => 'Leave',
            'description' => 'Computed: one row per active employee per leave type — allocated, earned so far, used this year, carried forward and balance (days), with liability_mvr = balance × daily rate (basic salary in MVR ÷ 30, as in final settlement). Use for unused leave, leave liability, and who could take leave soon. Unused day-offs: leave_type Day Off; more than 4 unused is "too many" (the T&A dashboard rule).',
            'basis'       => 'Leave balance engine (same as the Leave dashboard); daily rate = basic salary in MVR ÷ 30.',
            'source'      => [\App\Services\Wisdom\ComputedDatasets::class, 'leaveBalances'],
            'scope'       => ['department' => 'computed'],   // the source applies the department scope itself
            'fields' => [
                'employee_id'    => ['type' => 'string', 'label' => 'Employee ID'],
                'name'           => ['type' => 'string', 'sensitivity' => 'personal'],
                'department'     => ['type' => 'string'],
                'leave_type'     => ['type' => 'string', 'label' => 'e.g. Annual Leave, Sick Leave'],
                'allocated'      => ['type' => 'number', 'label' => 'Days allocated per year by the benefit grid'],
                'earned_so_far'  => ['type' => 'number', 'label' => 'Days earned to date (Annual Leave accrues monthly)'],
                'used'           => ['type' => 'number', 'label' => 'Approved days taken this year'],
                'carry_forward'  => ['type' => 'number'],
                'balance'        => ['type' => 'number', 'label' => 'Unused days available now'],
                'daily_rate_mvr' => ['type' => 'number', 'sensitivity' => 'compensation'],
                'liability_mvr'  => ['type' => 'number', 'sensitivity' => 'compensation', 'label' => 'Cost of paying out the balance, MVR'],
            ],
        ],

        'island_passes' => [
            'module'      => 'Leave',
            'description' => 'One row per island pass request (travel out = departure_date, return = arrival_date). Approval goes HOD → HR → SM (Security Manager); current_step = the approver it is waiting on while status is Pending.',
            'table'       => 'employee_travel_passes', 'alias' => 'tp',
            'resort'      => 'tp.resort_id',
            'scope'       => $byEmployeeDept,
            'joins'       => $employeeJoins('tp.employee_id'),
            'fields' => $employeeFields + [
                'departure_date' => ['col' => 'tp.departure_date', 'type' => 'date'],
                'arrival_date'   => ['col' => 'tp.arrival_date', 'type' => 'date'],
                'transportation' => ['col' => 'tp.transportation', 'type' => 'string'],
                'status'         => ['col' => 'tp.status', 'type' => 'string', 'values' => ['Pending', 'Approved', 'Rejected', 'Cancel']],
                // the first approver still Pending, in the fixed HOD → HR → SM order (resort-scoped through the pass)
                // older approval rows carry only the approver's rank (2 = HOD, 3 = HR); no rows yet = waiting for the first step
                'current_step'   => ['expr' => "(CASE WHEN tp.status <> 'Pending' THEN NULL ELSE COALESCE((SELECT COALESCE(s.approver_role, CASE s.approver_rank WHEN '2' THEN 'HOD' WHEN '3' THEN 'HR' END) FROM employee_travel_pass_status s WHERE s.travel_pass_id = tp.id AND s.status = 'Pending' ORDER BY FIELD(COALESCE(s.approver_role, CASE s.approver_rank WHEN '2' THEN 'HOD' WHEN '3' THEN 'HR' END), 'HOD', 'HR', 'SM') LIMIT 1), 'HOD') END)", 'type' => 'string', 'values' => ['HOD', 'HR', 'SM']],
                'requested_at'   => ['col' => 'tp.created_at', 'type' => 'datetime'],
                'days_pending'   => ['expr' => "(CASE WHEN tp.status = 'Pending' THEN DATEDIFF(CURDATE(), tp.created_at) END)", 'type' => 'number'],
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
            'description' => 'One row per vacancy. An open vacancy = is_open 1 (status Active or Open; Cancelled / Closed are not open). positions_required = seats; applicant_count = applications received; days_past_start > 0 = the required starting date has passed. No hire date is stored, so time-to-hire cannot be calculated.',
            'table'       => 'vacancies', 'alias' => 'v',
            'resort'      => 'v.Resort_id',
            'scope'       => ['department' => 'v.department'],
            'joins' => [
                'dept'     => ['table' => 'resort_departments', 'alias' => 'd', 'on' => ['d.id', 'v.department'], 'resort' => 'd.resort_id'],
                'position' => ['table' => 'resort_positions', 'alias' => 'p', 'on' => ['p.id', 'v.position'], 'resort' => 'p.resort_id'],
            ],
            'fields' => [
                'vacancy_id'           => ['col' => 'v.id', 'type' => 'number'],
                'department'           => ['col' => 'd.name', 'type' => 'string', 'join' => 'dept'],
                'position'             => ['col' => 'p.position_title', 'type' => 'string', 'join' => 'position'],
                'status'               => ['col' => 'v.status', 'type' => 'string', 'values' => ['Active', 'Open', 'Cancelled', 'Closed']],
                'is_open'              => ['expr' => "(CASE WHEN v.status IN ('Active','Open') THEN 1 ELSE 0 END)", 'type' => 'number', 'label' => '1 = open vacancy'],
                'days_past_start'      => ['expr' => 'DATEDIFF(CURDATE(), v.required_starting_date)', 'type' => 'number', 'label' => 'Days since the required starting date (negative = still ahead)'],
                // resort-scoped inside the subquery: applications of this vacancy in this resort only
                'applicant_count'      => ['expr' => '(SELECT COUNT(*) FROM applicant_form_data x WHERE x.Parent_v_id = v.id AND x.resort_id = v.Resort_id)', 'type' => 'number'],
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
                'vacancy_id'       => ['col' => 'ap.Parent_v_id', 'type' => 'number'],
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

        // ── Visa & Immigration costs (MVR) ────────────────────────────────────────────────────────────────────
        // "Spent in a period" = status Paid, by paid_or_due_date (many paid rows have no Payment_Date, only Due_Date).
        'work_permit_fees' => [
            'module'      => 'Visa',
            'description' => 'One row per monthly work-permit fee per employee. Money spent in a period: status = Paid and paid_or_due_date in the period; sum amount (MVR).',
            'table'       => 'work_permits', 'alias' => 'wp',
            'resort'      => 'wp.resort_id',
            'scope'       => $byEmployeeDept,
            'joins'       => $employeeJoins('wp.employee_id'),
            'fields' => $employeeFields + [
                'amount'           => ['col' => 'wp.Amt', 'type' => 'number', 'label' => 'Fee amount, in currency'],
                'currency'         => ['col' => 'wp.Currency', 'type' => 'string'],
                'status'           => ['col' => 'wp.Status', 'type' => 'string', 'values' => ['Paid', 'Unpaid']],
                'due_date'         => ['col' => 'wp.Due_Date', 'type' => 'date'],
                'payment_date'     => ['col' => 'wp.Payment_Date', 'type' => 'date'],
                'paid_or_due_date' => ['expr' => 'COALESCE(wp.Payment_Date, wp.Due_Date)', 'type' => 'date', 'label' => 'Payment date, or due date when no payment date was recorded'],
            ],
        ],

        'quota_slot_fees' => [
            'module'      => 'Visa',
            'description' => 'One row per quota slot fee per employee. Money spent in a period: status = Paid and paid_or_due_date in the period; sum amount (MVR).',
            'table'       => 'quota_slot_renewals', 'alias' => 'qs',
            'resort'      => 'qs.resort_id',
            'scope'       => $byEmployeeDept,
            'joins'       => $employeeJoins('qs.employee_id'),
            'fields' => $employeeFields + [
                'amount'           => ['col' => 'qs.Amt', 'type' => 'number', 'label' => 'Fee amount, in currency'],
                'currency'         => ['col' => 'qs.Currency', 'type' => 'string'],
                'status'           => ['col' => 'qs.Status', 'type' => 'string', 'values' => ['Paid', 'Unpaid']],
                'due_date'         => ['col' => 'qs.Due_Date', 'type' => 'date'],
                'payment_date'     => ['col' => 'qs.Payment_Date', 'type' => 'date'],
                'paid_or_due_date' => ['expr' => 'COALESCE(qs.Payment_Date, qs.Due_Date)', 'type' => 'date', 'label' => 'Payment date, or due date when no payment date was recorded'],
            ],
        ],

        'visa_renewals' => [
            'module'      => 'Visa',
            'description' => 'One row per visa renewal with its fee (MVR). Spent in a period: paid_date in the period.',
            'table'       => 'visa_renewals', 'alias' => 'vr',
            'resort'      => 'vr.resort_id',
            'scope'       => $byEmployeeDept,
            'joins'       => $employeeJoins('vr.employee_id'),
            'fields' => $employeeFields + [
                'amount'     => ['col' => 'vr.Amt', 'type' => 'number', 'label' => 'Fee (MVR)'],
                'status'     => ['col' => 'vr.Status', 'type' => 'string'],
                'start_date' => ['col' => 'vr.start_date', 'type' => 'date'],
                'end_date'   => ['col' => 'vr.end_date', 'type' => 'date', 'label' => 'Visa expiry'],
                'paid_date'  => ['col' => 'vr.paid_date', 'type' => 'date'],
            ],
        ],

        'insurance_premiums' => [
            'module'      => 'Visa',
            'description' => 'One row per employee insurance policy with its premium. Spent in a period: paid_date in the period.',
            'table'       => 'employee_insurances', 'alias' => 'ins',
            'resort'      => 'ins.resort_id',
            'scope'       => $byEmployeeDept,
            'joins'       => $employeeJoins('ins.employee_id'),
            'fields' => $employeeFields + [
                'insurance_company' => ['col' => 'ins.insurance_company', 'type' => 'string'],
                'premium'           => ['col' => 'ins.Premium', 'type' => 'number'],
                'currency'          => ['col' => 'ins.Currency', 'type' => 'string'],
                'status'            => ['col' => 'ins.Status', 'type' => 'string'],
                'start_date'        => ['col' => 'ins.insurance_start_date', 'type' => 'date'],
                'end_date'          => ['col' => 'ins.insurance_end_date', 'type' => 'date'],
                'paid_date'         => ['col' => 'ins.paid_date', 'type' => 'date'],
            ],
        ],

        'medical_renewals' => [
            'module'      => 'Visa',
            'description' => 'One row per work-permit medical renewal with its fee. Spent in a period: paid_date in the period.',
            'table'       => 'work_permit_medical_renewals', 'alias' => 'wm',
            'resort'      => 'wm.resort_id',
            'scope'       => $byEmployeeDept,
            'joins'       => $employeeJoins('wm.employee_id'),
            'fields' => $employeeFields + [
                'amount'         => ['col' => 'wm.Amt', 'type' => 'number'],
                'currency'       => ['col' => 'wm.Currency', 'type' => 'string'],
                'medical_center' => ['col' => 'wm.Medical_Center_name', 'type' => 'string'],
                'status'         => ['col' => 'wm.Status', 'type' => 'string'],
                'start_date'     => ['col' => 'wm.start_date', 'type' => 'date'],
                'end_date'       => ['col' => 'wm.end_date', 'type' => 'date'],
                'paid_date'      => ['col' => 'wm.paid_date', 'type' => 'date'],
            ],
        ],

        'visa_deposits' => [
            'module'      => 'Visa',
            'description' => 'One row per expatriate employee with the work-permit security deposit paid for them (MVR) and the date it was recorded. Deposits paid in a period: deposit_date in the period; sum deposit_amount.',
            'table'       => 'total_expensess_since_joings', 'alias' => 'te',
            'resort'      => 'te.resort_id',
            'scope'       => $byEmployeeDept,
            'joins'       => $employeeJoins('te.employees_id'),
            'fields' => $employeeFields + [
                'deposit_amount' => ['col' => 'te.Deposit_Amt', 'type' => 'number', 'label' => 'Deposit (MVR)'],
                'deposit_date'   => ['col' => 'te.Date', 'type' => 'date'],
            ],
        ],

        'expat_documents' => [
            'module'      => 'Visa',
            'description' => 'Computed: one row per active expatriate employee with the latest expiry of each immigration document (visa, work permit, insurance, medical — same dates as the Visa › Xpat employee screen), missing_documents / missing_count (no record at all), expired_documents, can_work_legally and visa_days_left (only an expired or missing VISA affects whether someone can work — insurance / medical are renewals, they do not stop anyone working) and next_renewal_* (any document). Use for "who has missing or incomplete documents", "whose visa is expiring" and workforce-availability risk.',
            'basis'       => 'Visa module tables (visa renewals, work-permit fees, insurance, medical renewals).',
            'source'      => [\App\Services\Wisdom\ComputedDatasets::class, 'expatDocuments'],
            'scope'       => ['department' => 'computed'],
            'fields' => [
                'employee_id'            => ['type' => 'string'],
                'name'                   => ['type' => 'string', 'sensitivity' => 'personal'],
                'department'             => ['type' => 'string'],
                'nationality'            => ['type' => 'string'],
                'visa_expiry'            => ['type' => 'date'],
                'work_permit_expiry'     => ['type' => 'date', 'label' => 'Next unpaid work-permit fee due date (as on the Xpat screen)'],
                'insurance_expiry'       => ['type' => 'date'],
                'medical_expiry'         => ['type' => 'date'],
                'missing_documents'      => ['type' => 'string', 'label' => 'Comma list of visa / work_permit / insurance / medical with no record'],
                'missing_count'          => ['type' => 'number'],
                'expired_documents'      => ['type' => 'string'],
                'can_work_legally'       => ['type' => 'string', 'values' => ['yes', 'no (visa expired)', 'unknown (no visa on record)']],
                'visa_days_left'         => ['type' => 'number', 'label' => 'Days until the VISA expires (negative = already expired). Use this — not next_renewal_date — for staff availability'],
                'next_renewal_document'  => ['type' => 'string', 'label' => 'Whichever document (visa, work permit, insurance, medical) comes up next — a renewal to schedule, NOT a visa expiry'],
                'next_renewal_date'      => ['type' => 'date'],
            ],
        ],

        'immigration_renewals' => [
            'module'      => 'Visa',
            'description' => 'Computed: every outstanding immigration item (unpaid work-permit and quota-slot fees, visa / insurance / medical renewals not yet paid) with its due or expiry date and amount in MVR — the cost of renewals in a period = sum amount_mvr with due_or_expiry_date in the period. blocks_work = yes only for Visa and Work Permit.',
            'basis'       => 'Same lookup as the Visa expiry and liability screens; amounts MVR.',
            'source'      => [\App\Services\Wisdom\ComputedDatasets::class, 'immigrationRenewals'],
            'scope'       => ['department' => 'computed'],
            'fields' => [
                'employee_id'        => ['type' => 'string'],
                'name'               => ['type' => 'string', 'sensitivity' => 'personal'],
                'department'         => ['type' => 'string'],
                'document'           => ['type' => 'string', 'values' => ['Visa', 'Work Permit', 'Slot Fee', 'Insurance', 'Medical']],
                'due_or_expiry_date' => ['type' => 'date'],
                'amount_mvr'         => ['type' => 'number'],
                'overdue'            => ['type' => 'string', 'values' => ['yes', 'no']],
                'blocks_work'        => ['type' => 'string', 'values' => ['yes', 'no']],
            ],
        ],

        'employee_documents' => [
            'module'      => 'File Management',
            'description' => 'One row per document uploaded to an employee\'s file (File Management: contracts, certificates, personal documents) with its category and expiry_date (often empty). Immigration documents (visa, permit, insurance, medical) are in expat_documents, not here.',
            'table'       => 'employees_documents', 'alias' => 'ed',
            'resort'      => 'ed.resort_id',
            'scope'       => $byEmployeeDept,
            'joins'       => $employeeJoins('ed.employee_id'),
            'fields' => $employeeFields + [
                'title'       => ['col' => 'ed.document_title', 'type' => 'string'],
                'category'    => ['col' => 'ed.document_category', 'type' => 'string'],
                'expiry_date' => ['col' => 'ed.expiry_date', 'type' => 'date'],
                'uploaded_at' => ['col' => 'ed.created_at', 'type' => 'datetime'],
            ],
        ],

        // ── Workforce planning ────────────────────────────────────────────────────────────────────────────────
        'staffing_forecast' => [
            'module'      => 'Workforce Planning',
            'description' => 'Computed: next 12 months — required_staff from the occupancy-based staffing model (the Workforce Planning dashboard forecast; future months use last year\'s occupancy when none is entered) vs approved_headcount in the manning plan; gap_vs_approved > 0 = more staff needed than approved. model_reason explains a missing forecast.',
            'basis'       => 'Workforce Planning dashboard staffing model + manning plan (position monthly headcount).',
            'source'      => [\App\Services\Wisdom\ComputedDatasets::class, 'staffingForecast'],
            'fields' => [
                'month'                => ['type' => 'string', 'label' => 'YYYY-MM'],
                'expected_occupancy_pct' => ['type' => 'number', 'label' => 'Occupancy the forecast is based on (entered, or last year\'s same month)'],
                'required_staff'       => ['type' => 'number'],
                'model_reason'         => ['type' => 'string'],
                'approved_headcount'   => ['type' => 'number'],
                'filled_in_plan'       => ['type' => 'number'],
                'active_employees_now' => ['type' => 'number'],
                'gap_vs_approved'      => ['type' => 'number'],
            ],
        ],

        // ── Time & Attendance (computed) ──────────────────────────────────────────────────────────────────────
        'weekly_hours' => [
            'module'      => 'Time and Attendance',
            'description' => 'Computed: normal hours per employee per week for the last 8 weeks (Mon–Sat, breaks and approved overtime excluded — the 48-hour rule) against the employee\'s weekly limit from the benefit grid (48 by default); over_limit = yes means more than the limit.',
            'basis'       => 'Same calculation as the T&A dashboard and the weekly-hours compliance check.',
            'source'      => [\App\Services\Wisdom\ComputedDatasets::class, 'weeklyHours'],
            'scope'       => ['department' => 'computed'],
            'fields' => [
                'employee_id'  => ['type' => 'string'],
                'name'         => ['type' => 'string', 'sensitivity' => 'personal'],
                'department'   => ['type' => 'string'],
                'week'         => ['type' => 'string'],
                'normal_hours' => ['type' => 'number'],
                'weekly_limit' => ['type' => 'number'],
                'over_limit'   => ['type' => 'string', 'values' => ['yes', 'no']],
            ],
        ],

        // ── Performance ───────────────────────────────────────────────────────────────────────────────────────
        'appraisals' => [
            'module'      => 'Performance',
            'description' => 'One row per employee per appraisal cycle with the self-review and manager-review status. Behind = either status pending; where the delay is = which of the two is pending (stage). Completion % = both completed ÷ all, per department.',
            'table'       => 'performa_child_cycles', 'alias' => 'pc',
            'resort'      => null,
            'required'    => ['cycle'],
            'scope'       => $byEmployeeDept,
            'joins'       => ['cycle' => ['table' => 'performance_cycles', 'alias' => 'pcy', 'on' => ['pcy.id', 'pc.Parent_cycle_id'], 'resort' => 'pcy.resort_id', 'type' => 'inner']]
                + $employeeJoins('pc.Emp_main_id'),
            'fields' => $employeeFields + [
                'cycle'                 => ['col' => 'pcy.Cycle_Name', 'type' => 'string', 'join' => 'cycle'],
                'cycle_start'           => ['col' => 'pcy.Start_Date', 'type' => 'date', 'join' => 'cycle'],
                'cycle_end'             => ['col' => 'pcy.End_Date', 'type' => 'date', 'join' => 'cycle'],
                'self_review_status'    => ['col' => 'pc.self_review_status', 'type' => 'string', 'values' => ['pending', 'completed']],
                'manager_review_status' => ['col' => 'pc.manager_review_status', 'type' => 'string', 'values' => ['pending', 'completed']],
                'stage'                 => ['expr' => "(CASE WHEN pc.self_review_status <> 'completed' THEN 'waiting for self-review' WHEN pc.manager_review_status <> 'completed' THEN 'waiting for manager review' ELSE 'completed' END)", 'type' => 'string', 'values' => ['waiting for self-review', 'waiting for manager review', 'completed']],
            ],
        ],

        'pip_plans' => [
            'module'      => 'Performance',
            'description' => 'One row per performance improvement plan (PIP). Currently on a PIP = status active.',
            'table'       => 'employee_pip_plans', 'alias' => 'pip',
            'resort'      => 'pip.resort_id',
            'scope'       => $byEmployeeDept,
            'joins'       => $employeeJoins('pip.employee_id'),
            'fields' => $employeeFields + [
                'status'       => ['col' => 'pip.status', 'type' => 'string', 'values' => ['active', 'completed', 'closed']],
                'duration'     => ['col' => 'pip.duration', 'type' => 'string'],
                'started_at'   => ['col' => 'pip.created_at', 'type' => 'datetime'],
            ],
        ],

        // ── Incident (investigations) ─────────────────────────────────────────────────────────────────────────
        'incident_investigations' => [
            'module'      => 'Incident',
            'description' => 'One row per incident investigation, with the incident\'s category, location and status. Overdue = is_overdue 1 (expected_resolution_date passed and the incident not resolved).',
            'table'       => 'incidents_investigation', 'alias' => 'ii',
            'resort'      => null,
            'required'    => ['incident'],
            'joins' => [
                'incident' => ['table' => 'incidents', 'alias' => 'i', 'on' => ['i.id', 'ii.incident_id'], 'resort' => 'i.resort_id', 'type' => 'inner'],
                'category' => ['table' => 'incident_categories', 'alias' => 'ic', 'on' => ['ic.id', 'i.category'], 'resort' => 'ic.resort_id', 'needs' => 'incident'],
            ],
            'fields' => [
                'incident_id'              => ['col' => 'i.incident_id', 'type' => 'string', 'join' => 'incident'],
                'category'                 => ['col' => 'ic.category_name', 'type' => 'string', 'join' => 'category'],
                'location'                 => ['col' => 'i.location', 'type' => 'string', 'join' => 'incident'],
                'incident_status'          => ['col' => 'i.status', 'type' => 'string', 'join' => 'incident'],
                'severity'                 => ['col' => 'i.severity', 'type' => 'string', 'join' => 'incident'],
                'investigation_start'      => ['col' => 'ii.start_date', 'type' => 'date'],
                'expected_resolution_date' => ['col' => 'ii.expected_resolution_date', 'type' => 'date'],
                'is_overdue'               => ['expr' => "(CASE WHEN ii.expected_resolution_date < CURDATE() AND i.resolved_at IS NULL THEN 1 ELSE 0 END)", 'type' => 'number', 'join' => 'incident'],
            ],
        ],

        // ── Learning (attendance and required training) ───────────────────────────────────────────────────────
        'training_attendance' => [
            'module'      => 'Learning',
            'description' => 'One row per employee per training session attended or missed (status Present / Absent). Attendance rate = Present ÷ all, per department or program.',
            'table'       => 'training_attendance', 'alias' => 'ta',
            'resort'      => null,
            'required'    => ['schedule'],
            'scope'       => $byEmployeeDept,
            'joins' => [
                'schedule' => ['table' => 'training_schedules', 'alias' => 'ts', 'on' => ['ts.id', 'ta.training_schedule_id'], 'resort' => 'ts.resort_id', 'type' => 'inner'],
                'program'  => ['table' => 'learning_programs', 'alias' => 'lp', 'on' => ['lp.id', 'ts.training_id'], 'resort' => 'lp.resort_id', 'needs' => 'schedule'],
            ] + $employeeJoins('ta.employee_id'),
            'fields' => $employeeFields + [
                'program'         => ['col' => 'lp.name', 'type' => 'string', 'join' => 'program'],
                'attendance_date' => ['col' => 'ta.attendance_date', 'type' => 'date'],
                'status'          => ['col' => 'ta.status', 'type' => 'string', 'values' => ['Present', 'Absent']],
            ],
        ],

        'training_compliance' => [
            'module'      => 'Learning',
            'description' => 'Computed: one row per active employee per REQUIRED program — mandatory programs (by department / position) and probation programs (due within the program\'s completion days of joining). completed = attended (Present) a session of that program; overdue = a probation program past its due date and not completed.',
            'basis'       => 'Mandatory and probationary learning program settings + training attendance.',
            'source'      => [\App\Services\Wisdom\ComputedDatasets::class, 'trainingCompliance'],
            'scope'       => ['department' => 'computed'],
            'fields' => [
                'employee_id'  => ['type' => 'string'],
                'name'         => ['type' => 'string', 'sensitivity' => 'personal'],
                'department'   => ['type' => 'string'],
                'program'      => ['type' => 'string'],
                'requirement'  => ['type' => 'string', 'values' => ['mandatory', 'probation']],
                'due_date'     => ['type' => 'date'],
                'completed'    => ['type' => 'string', 'values' => ['yes', 'no']],
                'completed_on' => ['type' => 'date'],
                'overdue'      => ['type' => 'string', 'values' => ['yes', 'no']],
            ],
        ],

        // ── Onboarding ────────────────────────────────────────────────────────────────────────────────────────
        'onboarding_readiness' => [
            'module'      => 'People',
            'description' => 'Computed: each joiner (status Onboarding, or a joining date from today on) with readiness — visa_ready (expats: immigration documents on file), accommodation_ready (bed assigned), itinerary_ready (arrival itinerary created), onboarding_ready (acknowledgements signed) and all_ready. A joiner with no joining_date has no start date set yet.',
            'basis'       => 'Visa documents, accommodation assignments, itineraries and onboarding acknowledgements.',
            'source'      => [\App\Services\Wisdom\ComputedDatasets::class, 'onboardingReadiness'],
            'scope'       => ['department' => 'computed'],
            'fields' => [
                'employee_id'         => ['type' => 'string'],
                'name'                => ['type' => 'string', 'sensitivity' => 'personal'],
                'department'          => ['type' => 'string'],
                'status'              => ['type' => 'string'],
                'joining_date'        => ['type' => 'date'],
                'arrival_date'        => ['type' => 'date'],
                'visa_ready'          => ['type' => 'string', 'values' => ['yes', 'no', 'not needed']],
                'accommodation_ready' => ['type' => 'string', 'values' => ['yes', 'no']],
                'itinerary_ready'     => ['type' => 'string', 'values' => ['yes', 'no']],
                'onboarding_ready'    => ['type' => 'string', 'values' => ['yes', 'no']],
                'all_ready'           => ['type' => 'string', 'values' => ['yes', 'no']],
            ],
        ],

        // ── Employee relations (HR only) ──────────────────────────────────────────────────────────────────────
        'disciplinary_cases' => [
            'module'      => 'Grievance and Disciplinary',
            'tiers'       => ['full'],
            'description' => 'One row per disciplinary case. The employee is the person the case is ABOUT (the subject). Repeat offenders = employees with more than one disciplinary case (group_by employee_id, count). warning_expiry = when the warning lapses. Open = status not Closed / Resolved.',
            'table'       => 'disciplinary_submits', 'alias' => 'ds',
            'resort'      => 'ds.resort_id',
            'scope'       => $byEmployeeDept,
            'joins'       => $employeeJoins('ds.Employee_id') + [
                'category' => ['table' => 'disciplinary_categories_models', 'alias' => 'dc', 'on' => ['dc.id', 'ds.Category_id'], 'resort' => 'dc.resort_id'],
                'offence'  => ['table' => 'offenses_models', 'alias' => 'om', 'on' => ['om.id', 'ds.Offence_id'], 'resort' => 'om.resort_id'],
            ],
            'fields' => $employeeFields + [
                'case_id'        => ['col' => 'ds.Disciplinary_id', 'type' => 'string'],
                'category'       => ['col' => 'dc.DisciplinaryCategoryName', 'type' => 'string', 'join' => 'category'],
                'offence'        => ['col' => 'om.OffensesName', 'type' => 'string', 'join' => 'offence'],
                'status'         => ['col' => 'ds.status', 'type' => 'string'],
                'priority'       => ['col' => 'ds.Priority', 'type' => 'string', 'values' => ['High', 'Medium', 'Low']],
                'warning_expiry' => ['col' => 'ds.Expiry_date', 'type' => 'date'],
                'raised_at'      => ['col' => 'ds.created_at', 'type' => 'datetime'],
                'age_days'       => ['expr' => 'DATEDIFF(CURDATE(), ds.created_at)', 'type' => 'number', 'label' => 'Days since the case was raised'],
            ],
        ],

        'grievance_cases' => [
            'module'      => 'Grievance and Disciplinary',
            'tiers'       => ['full'],
            'description' => 'One row per grievance. raised_by is the employee who RAISED the complaint — a complainant, never an offender (do not count grievances as offences). It is empty for confidential grievances (identity withheld). is_overdue = 1 when the grievance is still open past the resort\'s resolution time for its priority (High / Medium / Low days set in Grievance configuration).',
            'table'       => 'grivance_submission_models', 'alias' => 'gs',
            'resort'      => 'gs.resort_id',
            'scope'       => $byEmployeeDept,
            'where_null'  => ['gs.deleted_at'],
            'joins'       => $employeeJoins('gs.Employee_id') + [
                'category' => ['table' => 'grievance_categories', 'alias' => 'gc', 'on' => ['gc.id', 'gs.Grivance_Cat_id'], 'resort' => 'gc.resort_id'],
            ],
            'fields' => [
                'case_id'      => ['col' => 'gs.Grivance_id', 'type' => 'string'],
                'raised_by'    => ['expr' => "(CASE WHEN gs.Request_Identity_Disclosure = 'No' THEN NULL ELSE TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) END)", 'type' => 'string', 'join' => 'person', 'sensitivity' => 'personal', 'label' => 'Complainant (empty when confidential)'],
                'department'   => ['col' => 'd.name', 'type' => 'string', 'join' => 'dept', 'label' => 'Complainant\'s department'],
                'confidential' => ['expr' => "(CASE WHEN gs.Request_Identity_Disclosure = 'No' THEN 'yes' ELSE 'no' END)", 'type' => 'string', 'values' => ['yes', 'no']],
                'category'     => ['col' => 'gc.Category_Name', 'type' => 'string', 'join' => 'category'],
                'status'       => ['col' => 'gs.status', 'type' => 'string', 'values' => ['pending', 'in_review', 'resolved', 'closed']],
                'priority'     => ['col' => 'gs.Priority', 'type' => 'string', 'values' => ['High', 'Medium', 'Low']],
                'raised_at'    => ['col' => 'gs.created_at', 'type' => 'datetime'],
                'age_days'     => ['expr' => 'DATEDIFF(CURDATE(), gs.created_at)', 'type' => 'number'],
                // resolution time per priority from the resort's own grievance timeline (resort-scoped subquery)
                'is_overdue'   => ['expr' => "(CASE WHEN LOWER(COALESCE(gs.status,'')) IN ('resolved','closed','rejected') THEN 0 WHEN DATEDIFF(CURDATE(), gs.created_at) > (SELECT CASE gs.Priority WHEN 'High' THEN t.HighPriority WHEN 'Low' THEN t.LowPriority ELSE t.MediumPriority END FROM grivance_resoultion_time_line_models t WHERE t.resort_id = gs.resort_id ORDER BY t.id DESC LIMIT 1) THEN 1 ELSE 0 END)", 'type' => 'number', 'label' => '1 = open past the resolution time'],
            ],
        ],

        // ── Survey ────────────────────────────────────────────────────────────────────────────────────────────
        'survey_participation' => [
            'module'      => 'Survey',
            'description' => 'One row per employee invited to a survey, with whether they responded. Participation rate = responded yes ÷ invited, per survey and per department. Never shows answers; names only for Neutral (non-anonymous, non-confidential) surveys. The latest survey = the highest start_date.',
            'table'       => 'survey_employees', 'alias' => 'se',
            'resort'      => null,
            'required'    => ['survey'],
            'scope'       => $byEmployeeDept,
            'joins'       => ['survey' => ['table' => 'parent_surveys', 'alias' => 'ps', 'on' => ['ps.id', 'se.Parent_survey_id'], 'resort' => 'ps.resort_id', 'type' => 'inner']]
                + $employeeJoins('se.Emp_id'),
            'fields' => [
                'survey'         => ['col' => 'ps.Surevey_title', 'type' => 'string', 'join' => 'survey'],
                'survey_id'      => ['col' => 'ps.id', 'type' => 'number', 'join' => 'survey'],
                'survey_status'  => ['col' => 'ps.Status', 'type' => 'string', 'join' => 'survey', 'values' => ['OnGoing', 'Complete', 'SaveAsDraft']],
                'privacy'        => ['col' => 'ps.survey_privacy_type', 'type' => 'string', 'join' => 'survey', 'values' => ['Anonymous', 'Confidential', 'Neutral']],
                'start_date'     => ['col' => 'ps.Start_date', 'type' => 'date', 'join' => 'survey'],
                'end_date'       => ['col' => 'ps.End_date', 'type' => 'date', 'join' => 'survey'],
                'department'     => ['col' => 'd.name', 'type' => 'string', 'join' => 'dept'],
                'responded'      => ['expr' => "(CASE WHEN se.emp_status = 'yes' OR se.Complete_time IS NOT NULL THEN 'yes' ELSE 'no' END)", 'type' => 'string', 'values' => ['yes', 'no']],
                'name'           => ['expr' => "(CASE WHEN ps.survey_privacy_type = 'Neutral' THEN TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,''))) ELSE NULL END)", 'type' => 'string', 'join' => 'person', 'sensitivity' => 'personal', 'label' => 'Only for Neutral surveys'],
            ],
        ],

        // ── Compliance ────────────────────────────────────────────────────────────────────────────────────────
        'compliance_issues' => [
            'module'      => 'Compliance',
            'description' => 'One row per compliance flag. The same rule re-fires for the same person many times, so count people with count_distinct on employee_rule (or employee_id) — never raw rows. Open = status Breached. severity is the AI rating (Unrated when none); remediation = the suggested fix.',
            'table'       => 'compliances', 'alias' => 'cp',
            'resort'      => 'cp.resort_id',
            'scope'       => $byEmployeeDept,
            'where_null'  => ['cp.deleted_at'],
            'joins'       => $employeeJoins('cp.employee_id'),
            'fields' => $employeeFields + [
                'rule'          => ['col' => 'cp.compliance_breached_name', 'type' => 'string'],
                'module'        => ['col' => 'cp.module_name', 'type' => 'string'],
                'status'        => ['col' => 'cp.status', 'type' => 'string', 'values' => ['Breached', 'Resolved']],
                'severity'      => ['expr' => "COALESCE(NULLIF(cp.severity_ai,''), 'Unrated')", 'type' => 'string', 'values' => ['Critical', 'High', 'Medium', 'Low', 'Unrated']],
                'description'   => ['col' => 'cp.description', 'type' => 'string'],
                'remediation'   => ['col' => 'cp.remediation_ai', 'type' => 'string'],
                'reported_on'   => ['col' => 'cp.reported_on', 'type' => 'date'],
                'employee_rule' => ['expr' => "CONCAT(COALESCE(cp.employee_id,0),'|',cp.compliance_breached_name)", 'type' => 'string', 'label' => 'One person × one rule (for de-duplicated counts)'],
            ],
        ],

    ],
];
