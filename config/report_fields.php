<?php

/*
|--------------------------------------------------------------------------
| Report Builder — Business Semantic Layer (curated fields)
|--------------------------------------------------------------------------
|
| This is the predefined, business-friendly vocabulary for the HR report
| builder. It REPLACES the old "pick a raw database column" UI. Users never
| see table names, column names, foreign keys or related tables — they pick a
| Module, then an Entity, then a curated set of business Fields defined here.
|
| Shape:
|   Module => Entity Label => [
|       'table'       => 'real_base_table',     // base table the report runs on
|       'employee_fk' => 'employee_id'|null,    // base column referencing employees.id
|                                                // (null when the base table IS employees)
|       'fields'      => [ 'Business Label' => <definition> ],
|   ]
|
| Field definition (exactly one source key):
|   ['col' => 'x']                  Direct column on the base table.
|   ['employee_col' => 'x']         Column read from the employee record (via employee_fk).
|   ['employee_name' => true]       Employee full name (resort_admins via Admin_Parent_id).
|   ['lookup'=>'t','fk'=>'c','name'=>'n']           belongsTo: base.c -> t.id, show t.n
|   ['employee_lookup'=>'t','fk'=>'c','name'=>'n']  belongsTo off the employee record.
|
| Optional cast on any column-based field:
|   'cast' => 'date'         -> formatted d/m/Y
|   'cast' => 'eligibility'  -> mapped through config('settings.eligibilty') (rank/grade)
|   'cast' => 'money'        -> number_format(2) + currency from 'currency_col'
|                               (salaries are held per-employee in USD or MVR)
|
| This list is ALSO the security allow-list: ReportController only ever runs a
| query against a table/field that appears here, so the builder can never reach
| a table or column that isn't deliberately exposed.
|
*/

return [

    'People & Employees' => [

        'Employees' => [
            'table'       => 'employees',
            'employee_fk' => null, // base table IS the employee
            'fields' => [
                'Employee ID'        => ['employee_col' => 'Emp_id'],
                'Employee Name'      => ['employee_name' => true],
                'Department'         => ['lookup' => 'resort_departments', 'fk' => 'Dept_id',     'name' => 'name'],
                'Section'            => ['lookup' => 'resort_sections',    'fk' => 'Section_id',  'name' => 'name'],
                'Division'           => ['lookup' => 'resort_divisions',   'fk' => 'division_id', 'name' => 'name'],
                'Position'           => ['lookup' => 'resort_positions',   'fk' => 'Position_id', 'name' => 'position_title'],
                'Nationality'        => ['col' => 'nationality'],
                'Rank'               => ['col' => 'rank', 'cast' => 'eligibility'],
                'Benefit Grid Level' => ['col' => 'benefit_grid_level', 'cast' => 'eligibility'],
                'Employment Type'    => ['col' => 'employment_type'],
                'Contract Type'      => ['col' => 'contract_type'],
                'Status'             => ['col' => 'status'],
                'Joining Date'       => ['col' => 'joining_date', 'cast' => 'date'],
                'Date of Birth'      => ['col' => 'dob', 'cast' => 'date'],
                'Marital Status'     => ['col' => 'marital_status'],
                'Probation Status'   => ['col' => 'probation_status'],
                'Probation End Date' => ['col' => 'probation_end_date', 'cast' => 'date'],
                'Confirmation Date'  => ['col' => 'confirmation_date', 'cast' => 'date'],
                'Basic Salary'       => ['col' => 'basic_salary', 'cast' => 'money', 'currency_col' => 'basic_salary_currency'],
                'Passport Number'    => ['col' => 'passport_number'],
                'Work Location'      => ['col' => 'work_location'],
            ],
        ],

        'Transfers' => [
            'table'       => 'employee_transfers',
            'employee_fk' => 'employee_id',
            'fields' => [
                'Employee ID'        => ['employee_col' => 'Emp_id'],
                'Employee Name'      => ['employee_name' => true],
                'Current Department' => ['lookup' => 'resort_departments', 'fk' => 'current_department_id', 'name' => 'name'],
                'Target Department'  => ['lookup' => 'resort_departments', 'fk' => 'target_department_id',  'name' => 'name'],
                'Current Position'   => ['lookup' => 'resort_positions',   'fk' => 'current_position_id',   'name' => 'position_title'],
                'Target Position'    => ['lookup' => 'resort_positions',   'fk' => 'target_position_id',    'name' => 'position_title'],
                'Reason'             => ['col' => 'reason_for_transfer'],
                'Effective Date'     => ['col' => 'effective_date', 'cast' => 'date'],
                'Transfer Status'    => ['col' => 'transfer_status'],
                'Proposed Salary'    => ['col' => 'proposed_salary'],
                'Status'             => ['col' => 'status'],
            ],
        ],

        'Promotions' => [
            'table'       => 'employee_promotions',
            'employee_fk' => 'employee_id',
            'fields' => [
                'Employee ID'       => ['employee_col' => 'Emp_id'],
                'Employee Name'     => ['employee_name' => true],
                'Current Position'  => ['lookup' => 'resort_positions', 'fk' => 'current_position_id', 'name' => 'position_title'],
                'New Position'      => ['lookup' => 'resort_positions', 'fk' => 'new_position_id',     'name' => 'position_title'],
                'Effective Date'    => ['col' => 'effective_date', 'cast' => 'date'],
                'Current Salary'    => ['col' => 'current_salary'],
                'New Salary'        => ['col' => 'new_salary'],
                'Increment Amount'  => ['col' => 'salary_increment_amount'],
                'Increment Percent' => ['col' => 'salary_increment_percent'],
                'Status'            => ['col' => 'status'],
            ],
        ],

        'Resignations' => [
            'table'       => 'employee_resignation',
            'employee_fk' => 'employee_id',
            'fields' => [
                'Employee ID'      => ['employee_col' => 'Emp_id'],
                'Employee Name'    => ['employee_name' => true],
                'Reason'           => ['col' => 'reason'],
                'Resignation Date' => ['col' => 'resignation_date', 'cast' => 'date'],
                'Last Working Day' => ['col' => 'last_working_day', 'cast' => 'date'],
                'HR Status'        => ['col' => 'hr_status'],
                'HOD Status'       => ['col' => 'hod_status'],
                'Status'           => ['col' => 'status'],
            ],
        ],

    ],

    'Leave' => [

        'Leave Requests' => [
            'table'       => 'employees_leaves',
            'employee_fk' => 'emp_id',
            'fields' => [
                'Employee ID'   => ['employee_col' => 'Emp_id'],
                'Employee Name' => ['employee_name' => true],
                'Leave Type'    => ['lookup' => 'leave_categories', 'fk' => 'leave_category_id', 'name' => 'leave_type'],
                'From Date'     => ['col' => 'from_date', 'cast' => 'date'],
                'To Date'       => ['col' => 'to_date', 'cast' => 'date'],
                'Total Days'    => ['col' => 'total_days'],
                'Reason'        => ['col' => 'reason'],
                'Destination'   => ['col' => 'destination'],
                'Status'        => ['col' => 'status'],
            ],
        ],

    ],

    'Payroll' => [

        'Payroll Runs' => [
            'table'       => 'payroll',
            'employee_fk' => null,
            'fields' => [
                'Start Date'      => ['col' => 'start_date', 'cast' => 'date'],
                'End Date'        => ['col' => 'end_date', 'cast' => 'date'],
                'Total Payroll'   => ['col' => 'total_payroll'],
                'Total Employees' => ['col' => 'total_employees'],
                'Payment Date'    => ['col' => 'payment_date', 'cast' => 'date'],
                'Status'          => ['col' => 'status'],
            ],
        ],

        'Payslips' => [
            'table'       => 'payroll_reviews',
            'employee_fk' => 'employee_id',
            'fields' => [
                'Employee ID'      => ['employee_col' => 'Emp_id'],
                'Employee Name'    => ['employee_name' => true],
                'Basic Earnings'   => ['col' => 'earnings_basic'],
                'Earned Salary'    => ['col' => 'earned_salary'],
                'Overtime Earnings'=> ['col' => 'earnings_overtime'],
                'Allowance'        => ['col' => 'earnings_allowance'],
                'Service Charge'   => ['col' => 'service_charge'],
                'KPI Bonus'        => ['col' => 'kpi_bonus'],
                'Total Earnings'   => ['col' => 'total_earnings'],
                'Total Deductions' => ['col' => 'total_deductions'],
                'Net Salary'       => ['col' => 'net_salary'],
            ],
        ],

    ],

    'Visa' => [

        'Visa Renewals' => [
            'table'       => 'visa_renewals',
            'employee_fk' => 'employee_id',
            'fields' => [
                'Employee ID'    => ['employee_col' => 'Emp_id'],
                'Employee Name'  => ['employee_name' => true],
                'Visa Number'    => ['col' => 'Visa_Number'],
                'Work Permit No' => ['col' => 'WP_No'],
                'Start Date'     => ['col' => 'start_date', 'cast' => 'date'],
                'End Date'       => ['col' => 'end_date', 'cast' => 'date'],
                'Amount'         => ['col' => 'Amt'],
                'Status'         => ['col' => 'Status'],
                'Paid Date'      => ['col' => 'paid_date', 'cast' => 'date'],
            ],
        ],

        'Work Permits' => [
            'table'       => 'work_permits',
            'employee_fk' => 'employee_id',
            'fields' => [
                'Employee ID'        => ['employee_col' => 'Emp_id'],
                'Employee Name'      => ['employee_name' => true],
                'Work Permit Number' => ['col' => 'Work_Permit_Number'],
                'Month'              => ['col' => 'Month'],
                'Currency'           => ['col' => 'Currency'],
                'Amount'             => ['col' => 'Amt'],
                'Payment Date'       => ['col' => 'Payment_Date', 'cast' => 'date'],
                'Due Date'           => ['col' => 'Due_Date', 'cast' => 'date'],
                'Status'             => ['col' => 'Status'],
                'Payment Type'       => ['col' => 'PaymentType'],
            ],
        ],

        'Quota Slots' => [
            'table'       => 'quota_slot_renewals',
            'employee_fk' => 'employee_id',
            'fields' => [
                'Employee ID'  => ['employee_col' => 'Emp_id'],
                'Employee Name'=> ['employee_name' => true],
                'Month'        => ['col' => 'Month'],
                'Currency'     => ['col' => 'Currency'],
                'Amount'       => ['col' => 'Amt'],
                'Payment Date' => ['col' => 'Payment_Date', 'cast' => 'date'],
                'Due Date'     => ['col' => 'Due_Date', 'cast' => 'date'],
                'Status'       => ['col' => 'Status'],
                'Payment Type' => ['col' => 'PaymentType'],
            ],
        ],

    ],

    'Talent Acquisition' => [

        'Applicants' => [
            'table'       => 'applicant_form_data',
            'employee_fk' => null,
            'fields' => [
                'First Name'         => ['col' => 'first_name'],
                'Last Name'          => ['col' => 'last_name'],
                'Gender'             => ['col' => 'gender'],
                'Date of Birth'      => ['col' => 'dob', 'cast' => 'date'],
                'Email'              => ['col' => 'email'],
                'Mobile Number'      => ['col' => 'mobile_number'],
                'Country'            => ['col' => 'country'],
                'Marital Status'     => ['col' => 'marital_status'],
                'Application Date'   => ['col' => 'Application_date', 'cast' => 'date'],
                'Employment Status'  => ['col' => 'employment_status'],
                'Applicant Source'   => ['col' => 'Applicant_Source'],
                'Salary Expectation' => ['col' => 'SalaryExpectation'],
                'Total Experience'   => ['col' => 'Total_Experiance'],
            ],
        ],

        'Vacancies' => [
            'table'       => 'vacancies',
            'employee_fk' => null,
            'fields' => [
                'Position'           => ['lookup' => 'resort_positions',   'fk' => 'position',   'name' => 'position_title'],
                'Department'         => ['lookup' => 'resort_departments', 'fk' => 'department', 'name' => 'name'],
                'Division'           => ['lookup' => 'resort_divisions',   'fk' => 'division',   'name' => 'name'],
                'Section'            => ['lookup' => 'resort_sections',     'fk' => 'section',    'name' => 'name'],
                'Rank'               => ['col' => 'rank', 'cast' => 'eligibility'],
                'Employee Type'      => ['col' => 'employee_type'],
                'Total Positions'    => ['col' => 'Total_position_required'],
                'Salary'             => ['col' => 'salary'],
                'Required Start Date'=> ['col' => 'required_starting_date', 'cast' => 'date'],
                'Status'             => ['col' => 'status'],
            ],
        ],

        'Offers & Contracts' => [
            'table'       => 'applicant_offer_contracts',
            'employee_fk' => null,
            'fields' => [
                'Applicant'        => ['lookup' => 'applicant_form_data', 'fk' => 'applicant_id', 'name' => 'first_name'],
                'Type'             => ['col' => 'type'],
                'Status'           => ['col' => 'status'],
                'Responded At'     => ['col' => 'responded_at', 'cast' => 'date'],
                'Rejection Reason' => ['col' => 'rejection_reason'],
            ],
        ],

    ],

    'Learning & Development' => [

        'Learning Programs' => [
            'table'       => 'learning_programs',
            'employee_fk' => null,
            'fields' => [
                'Name'                    => ['col' => 'name'],
                'Category'                => ['lookup' => 'learning_categories', 'fk' => 'learning_category_id', 'name' => 'category'],
                'Delivery Mode'           => ['col' => 'delivery_mode'],
                'Audience Type'           => ['col' => 'audience_type'],
                'Hours'                   => ['col' => 'hours'],
                'Days'                    => ['col' => 'days'],
                'Frequency'               => ['col' => 'frequency'],
                'External Trainer Company'=> ['col' => 'external_trainer_company'],
            ],
        ],

        'Learning Requests' => [
            'table'       => 'learning_requests',
            'employee_fk' => null,
            'fields' => [
                'Learning Program' => ['lookup' => 'learning_programs', 'fk' => 'learning_id', 'name' => 'name'],
                'Reason'           => ['col' => 'reason'],
                'Start Date'       => ['col' => 'start_date', 'cast' => 'date'],
                'End Date'         => ['col' => 'end_date', 'cast' => 'date'],
                'Status'           => ['col' => 'status'],
            ],
        ],

        'Training Schedules' => [
            'table'       => 'training_schedules',
            'employee_fk' => null,
            'fields' => [
                'Venue'       => ['col' => 'venue'],
                'Start Date'  => ['col' => 'start_date', 'cast' => 'date'],
                'End Date'    => ['col' => 'end_date', 'cast' => 'date'],
                'Start Time'  => ['col' => 'start_time'],
                'End Time'    => ['col' => 'end_time'],
                'Description' => ['col' => 'description'],
                'Status'      => ['col' => 'status'],
            ],
        ],

    ],

    'Time & Attendance' => [

        'Attendance' => [
            'table'       => 'parent_attendaces',
            'employee_fk' => 'Emp_id',
            'fields' => [
                'Employee ID'   => ['employee_col' => 'Emp_id'],
                'Employee Name' => ['employee_name' => true],
                'Date'          => ['col' => 'date', 'cast' => 'date'],
                'Status'        => ['col' => 'Status'],
                'Check In'      => ['col' => 'CheckingTime'],
                'Check Out'     => ['col' => 'CheckingOutTime'],
                'Total Hours'   => ['col' => 'DayWiseTotalHours'],
                'Overtime'      => ['col' => 'OverTime'],
                'OT Status'     => ['col' => 'OTStatus'],
                'Note'          => ['col' => 'note'],
            ],
        ],

    ],

    'Budget' => [

        'Budget Costs' => [
            'table'       => 'resort_budget_costs',
            'employee_fk' => null,
            'fields' => [
                'Cost Title'  => ['col' => 'cost_title'],
                'Particulars' => ['col' => 'particulars'],
                'Amount'      => ['col' => 'amount'],
                'Amount Unit' => ['col' => 'amount_unit'],
                'Cost Type'   => ['col' => 'cost_type'],
                'Frequency'   => ['col' => 'frequency'],
                'Status'      => ['col' => 'status'],
            ],
        ],

        'Employee Budget' => [
            'table'       => 'resort_employee_budget_cost_configurations',
            'employee_fk' => 'employee_id',
            'fields' => [
                'Employee ID'    => ['employee_col' => 'Emp_id'],
                'Employee Name'  => ['employee_name' => true],
                'Department'     => ['lookup' => 'resort_departments', 'fk' => 'department_id', 'name' => 'name'],
                'Position'       => ['lookup' => 'resort_positions',   'fk' => 'position_id',   'name' => 'position_title'],
                'Value'          => ['col' => 'value'],
                'Currency'       => ['col' => 'currency'],
                'Basic Salary'   => ['col' => 'basic_salary'],
                'Current Salary' => ['col' => 'current_salary'],
                'Year'           => ['col' => 'year'],
                'Month'          => ['col' => 'month'],
            ],
        ],

    ],

    'Accommodation' => [

        'Bed Assignments' => [
            'table'       => 'assing_accommodations',
            'employee_fk' => 'emp_id',
            'fields' => [
                'Employee ID'   => ['employee_col' => 'Emp_id'],
                'Employee Name' => ['employee_name' => true],
                'Room'          => ['lookup' => 'available_accommodation_models', 'fk' => 'available_a_id', 'name' => 'RoomNo'],
                'Bed No'        => ['col' => 'BedNo'],
                'Effected Date' => ['col' => 'effected_date', 'cast' => 'date'],
            ],
        ],

        'Maintenance Requests' => [
            'table'       => 'maintanace_requests',
            'employee_fk' => 'Raised_By',
            'fields' => [
                'Raised By' => ['employee_name' => true],
                'Building'  => ['lookup' => 'building_models', 'fk' => 'building_id', 'name' => 'BuildingName'],
                'Floor No'  => ['col' => 'FloorNo'],
                'Room No'   => ['col' => 'RoomNo'],
                'Issue'     => ['col' => 'descriptionIssues'],
                'Priority'  => ['col' => 'priority'],
                'Status'    => ['col' => 'Status'],
                'Date'      => ['col' => 'date', 'cast' => 'date'],
            ],
        ],

    ],

    'Incident' => [

        'Incidents' => [
            'table'       => 'incidents',
            'employee_fk' => 'reporter_id',
            'fields' => [
                'Incident ID'   => ['col' => 'incident_id'],
                'Incident Name' => ['col' => 'incident_name'],
                'Reporter'      => ['employee_name' => true],
                'Description'   => ['col' => 'description'],
                'Incident Date' => ['col' => 'incident_date', 'cast' => 'date'],
                'Location'      => ['col' => 'location'],
                'Priority'      => ['col' => 'priority'],
                'Severity'      => ['col' => 'severity'],
                'Status'        => ['col' => 'status'],
                'Outcome Type'  => ['col' => 'outcome_type'],
                'Action Taken'  => ['col' => 'action_taken'],
            ],
        ],

    ],

    'Grievance & Disciplinary' => [

        'Grievances' => [
            'table'       => 'grivance_submission_models',
            'employee_fk' => 'Employee_id',
            'fields' => [
                'Grievance ID'  => ['col' => 'Grivance_id'],
                'Employee Name' => ['employee_name' => true],
                'Date'          => ['col' => 'date', 'cast' => 'date'],
                'Location'      => ['col' => 'location'],
                'Description'   => ['col' => 'Grivance_description'],
                'Status'        => ['col' => 'status'],
                'Priority'      => ['col' => 'Priority'],
                'Outcome Type'  => ['col' => 'outcome_type'],
                'Action Taken'  => ['col' => 'action_taken'],
            ],
        ],

        'Disciplinary Cases' => [
            'table'       => 'disciplinary_submits',
            'employee_fk' => 'Employee_id',
            'fields' => [
                'Disciplinary ID' => ['col' => 'Disciplinary_id'],
                'Employee Name'   => ['employee_name' => true],
                'Description'     => ['col' => 'Incident_description'],
                'Status'          => ['col' => 'status'],
                'Priority'        => ['col' => 'Priority'],
                'Expiry Date'     => ['col' => 'Expiry_date', 'cast' => 'date'],
            ],
        ],

    ],

    'Survey' => [

        'Surveys' => [
            'table'       => 'parent_surveys',
            'employee_fk' => null,
            'fields' => [
                'Survey Title' => ['col' => 'Surevey_title'],
                'Start Date'   => ['col' => 'Start_date', 'cast' => 'date'],
                'End Date'     => ['col' => 'End_date', 'cast' => 'date'],
                'Status'       => ['col' => 'Status'],
                'Recurring'    => ['col' => 'Recurring_survey'],
                'Min Response' => ['col' => 'Min_response'],
                'Privacy Type' => ['col' => 'survey_privacy_type'],
            ],
        ],

    ],

];
