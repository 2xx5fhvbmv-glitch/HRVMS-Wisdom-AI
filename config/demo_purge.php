<?php

/*
| How DemoPurger finds every row that belongs to the demo resort.
|
| Found automatically (no entry needed):
|   - tables with an integer resort_id / Resort_id column       → rows of the resort
|   - tables with an integer employee column (employee_id, emp_id, Emp_id,
|     Employee_id…) holding employees.id                         → rows of its employees
|   - tables with a declared foreign key to a purged table       → their child rows
|
| Everything else must be listed below. A table in none of these places stops
| the reset ("fail closed"), so a new table can never leak or be purged by
| guesswork — run `php artisan demo:purge-audit` after adding tables.
*/
return [

    // Integer columns that hold employees.id (checked against real data: 100% match).
    'employee_columns' => ['employee_id', 'emp_id', 'Emp_id', 'Employee_id'],

    // Child tables linked to a parent without a declared foreign key:
    // table => [[column, parent table, parent column], …]
    'children' => [
        'employees_leaves_status'                     => [['leave_request_id', 'employees_leaves', 'id']],
        'employees_leave_transportation'              => [['leave_request_id', 'employees_leaves', 'id']],
        'leave_recommendations'                       => [['leave_id', 'employees_leaves', 'id']],
        'employee_promotions_approval'                => [['promotion_id', 'employee_promotions', 'id']],
        'employee_transfers_approval'                 => [['transfer_id', 'employee_transfers', 'id']],
        'employee_travel_pass_status'                 => [['travel_pass_id', 'employee_travel_passes', 'id']],
        'employee_itineraries_meeting'                => [['employee_itinerary_id', 'employee_itineraries', 'id']],
        'exit_clearance_form_responses'               => [['assignment_id', 'exit_clearance_form_assignments', 'id']],
        'resignation_meeting_schedule'                => [['resignationId', 'employee_resignation', 'id']],
        'final_settlement_deductions'                 => [['final_settlement_id', 'final_settlements', 'id']],
        'final_settlement_earnings'                   => [['final_settlement_id', 'final_settlements', 'id']],
        'people_salary_increment_status'              => [['people_salary_increment_id', 'people_salary_increment', 'id']],
        'payroll_review_allowances'                   => [['payroll_review_id', 'payroll_reviews', 'id']],
        'payroll_advance_guarantor'                   => [['payroll_advance_id', 'payroll_advance', 'id']],
        'products'                                    => [['shopkeeper_id', 'shopkeepers', 'id']],
        'resort_benefit_grid_child'                   => [['benefit_grid_id', 'resort_benifit_grid', 'id']],
        'custom_benfits'                              => [['benefit_grid_id', 'resort_benifit_grid', 'id']],
        'custom_discounts'                            => [['benefit_grid_id', 'resort_benifit_grid', 'id']],
        'custom_leaves'                               => [['benefit_grid_id', 'resort_benifit_grid', 'id']],
        'resorts_child_notifications'                 => [['Parent_msg_id', 'resorts_parent_notifications', 'message_id']],
        'hr_reminder_request_mannings'                => [['message_id', 'resorts_parent_notifications', 'message_id']],
        'position_monthly_data'                       => [['manning_response_id', 'manning_responses', 'id']],
        'store_consolidate_budget_children'           => [['Parent_SCB_id', 'store_consolidate_budget_parents', 'id']],
        't_anotification_children'                    => [['Parent_ta_id', 't_anotification_parents', 'id']],
        'questionnaire_children'                      => [['Q_Parent_id', 'questionnaires', 'id']],
        'video_questions'                             => [['Q_Parent_id', 'questionnaires', 'id']],
        'interview_assessment_responses'              => [['form_id', 'interview_assessment_forms', 'id']],
        'applicant_form_job_assessment'               => [['applicant_form_id', 'applicant_form_data', 'id']],
        'education_applicant_form'                    => [['applicant_form_id', 'applicant_form_data', 'id']],
        'work_experience_applicant_form'              => [['applicant_form_id', 'applicant_form_data', 'id']],
        'performa_child_cycles'                       => [['Parent_cycle_id', 'performance_cycles', 'id']],
        'performance_kpi_children'                    => [['kpi_parents_id', 'performance_kpi_parents', 'id']],
        'learning_materials'                          => [['learning_program_id', 'learning_programs', 'id']],
        'evaluation_form_responses'                   => [['form_id', 'evaluation_form', 'id']],
        'training_feedback_responses'                 => [['form_id', 'training_feedback_form', 'id']],
        'facility_tour_images'                        => [['facility_tour_category_id', 'facility_tour_categories', 'id']],
        'incidents_investigation'                     => [['incident_id', 'incidents', 'id']],
        'incidents_investigation_meetings'            => [['incident_id', 'incidents', 'id']],
        'incidents_investigation_meetings_participants' => [['meeting_id', 'incidents_investigation_meetings', 'id']],
        'incidents_meetings_external_participants'    => [['meeting_id', 'incidents_investigation_meetings', 'id']],
        'incidents_witness'                           => [['incident_id', 'incidents', 'id']],
        'incident_committee_members'                  => [['commitee_id', 'incident_committee', 'id']],
        'grivance_investigation_child_models'         => [['investigation_p_id', 'grivance_investigation_models', 'id']],
        'grivance_submission_witnesses'               => [['G_S_Parent_id', 'grivance_submission_models', 'id']],
        'sos_child_emergency_types'                   => [['emergency_id', 'sos_emergency_types', 'id']],
        'support_chat_messages'                       => [['support_id', 'support', 'id']],
        'support_messages'                            => [['ticket_id', 'support', 'id']],
        'survey_questions'                            => [['Parent_survey_id', 'parent_surveys', 'id']],
        'survey_results'                              => [['Parent_survey_id', 'parent_surveys', 'id']],
        'visa_renewal_children'                       => [['visa_renewal_id', 'visa_renewals', 'id']],
        'work_permit_medical_renewal_children'        => [['permit_medical_id', 'work_permit_medical_renewals', 'id']],
        'manifest_visitors'                           => [['manifest_id', 'manifest', 'id']],
        'clinic_treatment_attachments'                => [['clinic_treatment_id', 'clinic_treatment', 'id']],
        'chat_message_read'                           => [['conversation_id', 'conversation', 'id']],
        'resort_data_import_records'                  => [['import_id', 'resort_data_imports', 'id']],
        'oauth_refresh_tokens'                        => [['access_token_id', 'oauth_access_tokens', 'id']],
    ],

    // Rows keyed by a demo login (resort_admins.id): table => [column, optional extra where].
    'admin_columns' => [
        'admin_notification_dismissals' => ['resort_admin_id'],
        'oauth_access_tokens'           => ['user_id'],
        'oauth_auth_codes'              => ['user_id'],
        'personal_access_tokens'        => ['tokenable_id', ['tokenable_type', 'App\\Models\\ResortAdmin']],
    ],

    // Resort column stored as text: table => column.
    'text_resort_columns' => [
        'store_consolidate_budget_parents' => 'Resort_id',
    ],

    // Shared by every resort (or super-admin only) — never purged.
    'global' => [
        'admins', 'admins_password_resets', 'admin_audit_logs', 'admin_modules', 'admin_module_permissions',
        'admin_passkeys', 'admin_role_module_permissions', 'airports', 'bulidng_and_foolr_and_rooms', 'cities',
        'countries', 'divisions', 'department', 'email_templates', 'ewt_tax_brackets', 'failed_jobs', 'jobs',
        'login_attempts', 'migrations', 'modules', 'module_pages', 'notifications', 'oauth_clients',
        'oauth_personal_access_clients', 'occupancy_thresholds', 'password_resets', 'permissions', 'positions',
        'public_holidays', 'resort_admin_password_resets', 'resort_data_import_mappings', 'resort_languages',
        'resort_modules', 'resort_module_permissions', 'resort_permissions', 'resort_position_module_permissions',
        'roles', 'sections', 'settings', 'shopkeeper_password_resets', 'states', 'support_categories', 'users',
        'websockets_statistics_entries',
        // The resort row itself is kept (name/logo survive resets).
        'resorts',
    ],
];
