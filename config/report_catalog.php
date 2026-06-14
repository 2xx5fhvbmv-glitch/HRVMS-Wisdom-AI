<?php

/*
|--------------------------------------------------------------------------
| Report Builder Catalog
|--------------------------------------------------------------------------
|
| Friendly, module-based vocabulary for the HR report builder. HR never sees
| raw database table names — they pick a Module, then an Entity. Each entity
| maps to one real table. Columns are still auto-discovered + auto-labelled by
| ReportController::getTableColumns(), so this only maps the module/entity ->
| table relationships.
|
| This list is ALSO the security allow-list: ReportController validates that a
| requested table appears here before building or running a report, so the
| builder can never touch a table that isn't deliberately exposed.
|
| To expose a new entity: add 'Friendly Entity Name' => 'real_table_name'.
| Entities whose table does not exist in the DB are silently skipped at render.
|
*/

return [

    'People & Employees' => [
        'Employees'    => 'employees',
        'Transfers'    => 'employee_transfers',
        'Promotions'   => 'employee_promotions',
        'Resignations' => 'employee_resignation',
    ],

    'Leave' => [
        'Leave Requests' => 'employees_leaves',
    ],

    'Payroll' => [
        'Payroll Runs' => 'payroll',
        'Payslips'     => 'payroll_reviews',
    ],

    'Talent Acquisition' => [
        'Applicants'          => 'applicant_form_data',
        'Vacancies'           => 'vacancies',
        'Offers & Contracts'  => 'applicant_offer_contracts',
    ],

    'Learning & Development' => [
        'Training Schedules' => 'training_schedules',
        'Learning Requests'  => 'learning_requests',
        'Learning Programs'  => 'learning_programs',
    ],

    'Accommodation' => [
        'Maintenance Requests' => 'maintanace_requests',
        'Bed Assignments'      => 'assing_accommodations',
    ],

    'Incident' => [
        'Incidents' => 'incidents',
    ],

    'Survey' => [
        'Surveys' => 'parent_surveys',
    ],

    'Visa' => [
        'Visa Renewals' => 'visa_renewals',
        'Work Permits'  => 'work_permits',
        'Quota Slots'   => 'quota_slot_renewals',
    ],

    'Grievance & Disciplinary' => [
        'Grievances'        => 'grivance_submission_models',
        'Disciplinary Cases' => 'disciplinary_submits',
    ],

];
