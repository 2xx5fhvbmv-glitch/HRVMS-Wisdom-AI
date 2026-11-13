<?php

/*
|--------------------------------------------------------------------------
| Wisdom AI data catalog — what WAI may query, and who may see what
|--------------------------------------------------------------------------
| WAI never writes SQL against these. It calls `query_data` with a dataset name, field names, filters, group-by and
| metrics; App\Services\Wisdom\CatalogQuery checks every name against this file and builds the query with Laravel's
| query builder (bound values, read-only connection). Adding a question type = adding a dataset or a field here.
|
| Security model (enforced in code, not by the AI):
| - Every table, the base and every join, is filtered by the user's resort_id ('resort' column). Always.
| - A scoped user (HOD/manager, when that tier exists) is limited to their department ids via 'scope.department'.
| - A field is offered only to tiers allowed by its 'sensitivity' (see 'sensitivity' below). Unlisted columns,
|   credentials, passport/ID numbers, emergency contacts, device tokens etc. are simply not in the catalog.
| - 'col' / 'expr' / join 'on' values are written by developers here; nothing from the AI is ever put into SQL text.
|
| Field keys: col (or expr), type (string|number|date|datetime), label, sensitivity (default 'general'),
| join (the join a field needs), values (known values, shown to the AI).
*/

return [

    // Which WAI tiers (App\Services\Wisdom\WisdomAccess) may read a field of each sensitivity.
    'sensitivity' => [
        'general'      => ['full', 'gm'],
        'personal'     => ['full', 'gm'],
        'compensation' => ['full'],          // pay: HR only (founder decision 2026-11-02, GM excluded)
    ],

    'limits' => [
        'rows'      => 200,
        'filters'   => 12,
        'group_by'  => 3,
        'metrics'   => 6,
        'in_values' => 100,
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
                'name'              => ['expr' => "TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,'')))", 'type' => 'string', 'join' => 'person', 'label' => 'Full name', 'sensitivity' => 'personal'],
                'department'        => ['col' => 'd.name', 'type' => 'string', 'join' => 'dept'],
                'position'          => ['col' => 'p.position_title', 'type' => 'string', 'join' => 'position'],
                'division'          => ['col' => 'dv.name', 'type' => 'string', 'join' => 'division'],
                'status'            => ['col' => 'e.status', 'type' => 'string', 'values' => ['Active', 'Onboarding', 'Offboarding', 'Inactive', 'Terminated']],
                'employment_type'   => ['col' => 'e.employment_type', 'type' => 'string', 'values' => ['Full-Time', 'Probationary', 'Casual', 'Internship']],
                'nationality'       => ['col' => 'e.nationality', 'type' => 'string'],
                'gender'            => ['col' => 'ra.gender', 'type' => 'string', 'join' => 'person'],
                'joining_date'      => ['col' => 'e.joining_date', 'type' => 'date'],
                'probation_status'  => ['col' => 'e.probation_status', 'type' => 'string'],
                'probation_end_date'=> ['col' => 'e.probation_end_date', 'type' => 'date'],
                'contract_end_date' => ['col' => 'e.contract_end_date', 'type' => 'date'],
                'last_working_day'  => ['col' => 'e.last_working_day', 'type' => 'date'],
                'date_of_birth'     => ['col' => 'e.dob', 'type' => 'date', 'sensitivity' => 'personal'],
                'visa_expiry_date'  => ['col' => 'e.visa_expiry_date', 'type' => 'date'],
                'work_permit_expiry_date' => ['col' => 'e.work_permit_expiry_date', 'type' => 'date'],
                'basic_salary'      => ['col' => 'e.basic_salary', 'type' => 'number', 'sensitivity' => 'compensation', 'label' => 'Basic salary (in basic_salary_currency)'],
                'basic_salary_currency' => ['col' => 'e.basic_salary_currency', 'type' => 'string', 'sensitivity' => 'compensation'],
            ],
        ],

        // ── Leave ─────────────────────────────────────────────────────────────────────────────────────────────
        'leave_requests' => [
            'module'      => 'Leave',
            'description' => 'One row per leave request. total_days = days requested; status Pending / Approved / Rejected / Cancelled.',
            'table'       => 'employees_leaves', 'alias' => 'l',
            'resort'      => 'l.resort_id',
            'scope'       => ['department' => 'e.Dept_id', 'needs' => 'employee'],
            'joins' => [
                'employee' => ['table' => 'employees', 'alias' => 'e', 'on' => ['e.id', 'l.emp_id'], 'resort' => 'e.resort_id'],
                'person'   => ['table' => 'resort_admins', 'alias' => 'ra', 'on' => ['ra.id', 'e.Admin_Parent_id'], 'resort' => 'ra.resort_id', 'needs' => 'employee'],
                'dept'     => ['table' => 'resort_departments', 'alias' => 'd', 'on' => ['d.id', 'e.Dept_id'], 'resort' => 'd.resort_id', 'needs' => 'employee'],
                'category' => ['table' => 'leave_categories', 'alias' => 'c', 'on' => ['c.id', 'l.leave_category_id'], 'resort' => 'c.resort_id'],
            ],
            'fields' => [
                'employee_id' => ['col' => 'e.Emp_id', 'type' => 'string', 'join' => 'employee'],
                'name'        => ['expr' => "TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,'')))", 'type' => 'string', 'join' => 'person', 'sensitivity' => 'personal'],
                'department'  => ['col' => 'd.name', 'type' => 'string', 'join' => 'dept'],
                'leave_type'  => ['col' => 'c.leave_type', 'type' => 'string', 'join' => 'category'],
                'is_paid_type'=> ['col' => 'c.is_paid', 'type' => 'number', 'join' => 'category', 'label' => '1 = paid leave type'],
                'from_date'   => ['col' => 'l.from_date', 'type' => 'date'],
                'to_date'     => ['col' => 'l.to_date', 'type' => 'date'],
                'total_days'  => ['col' => 'l.total_days', 'type' => 'number'],
                'status'      => ['col' => 'l.status', 'type' => 'string', 'values' => ['Pending', 'Approved', 'Rejected', 'Cancelled']],
                'requested_at'=> ['col' => 'l.created_at', 'type' => 'datetime'],
            ],
        ],

        // ── Time & Attendance ─────────────────────────────────────────────────────────────────────────────────
        'attendance' => [
            'module'      => 'Time and Attendance',
            'description' => 'One row per employee per day actually recorded (no row = absent). overtime_hours from the punch record.',
            'table'       => 'parent_attendaces', 'alias' => 'a',
            'resort'      => 'a.resort_id',
            'scope'       => ['department' => 'e.Dept_id', 'needs' => 'employee'],
            'where'       => [['a.date', '>=', '2000-01-01']],   // old test rows carry 1970 dates
            'joins' => [
                'employee' => ['table' => 'employees', 'alias' => 'e', 'on' => ['e.id', 'a.Emp_id'], 'resort' => 'e.resort_id'],
                'person'   => ['table' => 'resort_admins', 'alias' => 'ra', 'on' => ['ra.id', 'e.Admin_Parent_id'], 'resort' => 'ra.resort_id', 'needs' => 'employee'],
                'dept'     => ['table' => 'resort_departments', 'alias' => 'd', 'on' => ['d.id', 'e.Dept_id'], 'resort' => 'd.resort_id', 'needs' => 'employee'],
            ],
            'fields' => [
                'employee_id'    => ['col' => 'e.Emp_id', 'type' => 'string', 'join' => 'employee'],
                'name'           => ['expr' => "TRIM(CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,'')))", 'type' => 'string', 'join' => 'person', 'sensitivity' => 'personal'],
                'department'     => ['col' => 'd.name', 'type' => 'string', 'join' => 'dept'],
                'date'           => ['col' => 'a.date', 'type' => 'date'],
                'status'         => ['col' => 'a.Status', 'type' => 'string', 'values' => ['Present', 'Absent', 'DayOff', 'FullDayLeave', 'HalfDayLeave']],
                'check_in'       => ['col' => 'a.CheckingTime', 'type' => 'string'],
                'check_out'      => ['col' => 'a.CheckingOutTime', 'type' => 'string'],
                'overtime_hours' => ['expr' => "(CASE WHEN a.OverTime LIKE '%:%' THEN SUBSTRING_INDEX(a.OverTime,':',1) + SUBSTRING_INDEX(a.OverTime,':',-1) / 60 ELSE 0 END)", 'type' => 'number'],
                'overtime_status'=> ['col' => 'a.OTStatus', 'type' => 'string'],
            ],
        ],
    ],
];
