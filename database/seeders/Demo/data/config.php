<?php

// Demo ENV resort configuration, captured once from the Demo Resort (id 26) on 2026-10-08.
// Table => rows (no id/resort/audit columns). '_ref' is the source row id, used to remap
// leave_categories.leave_category and the benefit grid's grade-level ids.
// Test leftovers (dev shifts, office geofences, duplicate HOD grades) removed.

return array (
  'resort_site_settings' => 
  array (
    0 => 
    array (
      'currency' => 'Dollar',
      'casual_payment_model' => 'lump_sum',
      'MVRtoDoller' => 0.06485084306096,
      'DollertoMVR' => 15.42,
      'MVR_img' => 'maldives-currency-icon-new.svg',
      'Doller_img' => 'doller-currency-icon.svg',
      'Footer' => 'Copyright © 2026. All Rights Reserved.',
      'FinalApproval' => '8',
      'header_img' => NULL,
      'footer_img' => NULL,
      'emergency_police_number' => NULL,
      'emergency_fire_number' => NULL,
      'emergency_mndf_number' => NULL,
    ),
  ),
  'leave_categories' => 
  array (
    0 => 
    array (
      '_ref' => 57,
      'leave_type' => 'Annual Leave',
      'number_of_days' => 30,
      'carry_forward' => 0,
      'carry_max' => NULL,
      'earned_leave' => 0,
      'earned_max' => NULL,
      'eligibility' => '8,1,2,4,5,6',
      'frequency' => 'Yearly',
      'number_of_times' => '1',
      'color' => '#a264f7',
      'leave_category' => '58,61',
      'combine_with_other' => 1,
      'is_paid' => 'paid',
    ),
    1 => 
    array (
      '_ref' => 58,
      'leave_type' => 'Emergency Leave',
      'number_of_days' => 10,
      'carry_forward' => 0,
      'carry_max' => NULL,
      'earned_leave' => 0,
      'earned_max' => NULL,
      'eligibility' => '8,1,2,4,5,6',
      'frequency' => 'Yearly',
      'number_of_times' => '1',
      'color' => '#ff2600',
      'leave_category' => '',
      'combine_with_other' => 0,
      'is_paid' => 'paid',
    ),
    2 => 
    array (
      '_ref' => 59,
      'leave_type' => 'Maternity Leave',
      'number_of_days' => 60,
      'carry_forward' => 0,
      'carry_max' => NULL,
      'earned_leave' => 0,
      'earned_max' => NULL,
      'eligibility' => '8,1,2,4,5,6',
      'frequency' => 'Yearly',
      'number_of_times' => '1',
      'color' => '#0056d6',
      'leave_category' => '',
      'combine_with_other' => 0,
      'is_paid' => 'paid',
    ),
    3 => 
    array (
      '_ref' => 60,
      'leave_type' => 'Paternity Leave',
      'number_of_days' => 3,
      'carry_forward' => 0,
      'carry_max' => NULL,
      'earned_leave' => 0,
      'earned_max' => NULL,
      'eligibility' => '8,1,2,4,5,6',
      'frequency' => 'Yearly',
      'number_of_times' => '1',
      'color' => '#00a3d7',
      'leave_category' => '57',
      'combine_with_other' => 1,
      'is_paid' => 'paid',
    ),
    4 => 
    array (
      '_ref' => 61,
      'leave_type' => 'Birthday Leave',
      'number_of_days' => 1,
      'carry_forward' => 0,
      'carry_max' => NULL,
      'earned_leave' => 0,
      'earned_max' => NULL,
      'eligibility' => '8,1,2,4,5,6',
      'frequency' => 'Yearly',
      'number_of_times' => '1',
      'color' => '#7a7a7a',
      'leave_category' => '',
      'combine_with_other' => 0,
      'is_paid' => 'paid',
    ),
    5 => 
    array (
      '_ref' => 62,
      'leave_type' => 'Sick Leave',
      'number_of_days' => 30,
      'carry_forward' => 0,
      'carry_max' => NULL,
      'earned_leave' => 0,
      'earned_max' => NULL,
      'eligibility' => '8,1,2,4,5,6',
      'frequency' => 'Yearly',
      'number_of_times' => '30',
      'color' => '#ffc4ab',
      'leave_category' => '',
      'combine_with_other' => 0,
      'is_paid' => 'paid',
    ),
    6 => 
    array (
      '_ref' => 63,
      'leave_type' => 'Rest Relaxation Leave',
      'number_of_days' => 12,
      'carry_forward' => 0,
      'carry_max' => NULL,
      'earned_leave' => 0,
      'earned_max' => NULL,
      'eligibility' => '8,1,2',
      'frequency' => 'Yearly',
      'number_of_times' => '2',
      'color' => '#371a94',
      'leave_category' => '',
      'combine_with_other' => 0,
      'is_paid' => 'paid',
    ),
    7 => 
    array (
      '_ref' => 64,
      'leave_type' => 'Circumcision Leave',
      'number_of_days' => 5,
      'carry_forward' => 0,
      'carry_max' => NULL,
      'earned_leave' => 0,
      'earned_max' => NULL,
      'eligibility' => '8,1,2,4,5,6',
      'frequency' => 'Yearly',
      'number_of_times' => '1',
      'color' => '#002e7a',
      'leave_category' => '',
      'combine_with_other' => 0,
      'is_paid' => 'unpaid',
    ),
    8 => 
    array (
      '_ref' => 76,
      'leave_type' => 'Unpaid Leave',
      'number_of_days' => 30,
      'carry_forward' => 1,
      'carry_max' => NULL,
      'earned_leave' => 0,
      'earned_max' => NULL,
      'eligibility' => '8,1,2,4,5,6',
      'frequency' => 'Yearly',
      'number_of_times' => '30',
      'color' => '#00364a',
      'leave_category' => '',
      'combine_with_other' => 0,
      'is_paid' => 'unpaid',
    ),
    9 => 
    array (
      '_ref' => 77,
      'leave_type' => 'Public Holiday',
      'number_of_days' => 0,
      'carry_forward' => 0,
      'carry_max' => NULL,
      'earned_leave' => 0,
      'earned_max' => NULL,
      'eligibility' => '8,1,2,3,4,5,6,7',
      'frequency' => 'Yearly',
      'number_of_times' => '1',
      'color' => '#1abc9c',
      'leave_category' => '',
      'combine_with_other' => 0,
      'is_paid' => 'paid',
    ),
    10 => 
    array (
      '_ref' => 78,
      'leave_type' => 'Day Off',
      'number_of_days' => 52,
      'carry_forward' => 1,
      'carry_max' => NULL,
      'earned_leave' => 0,
      'earned_max' => NULL,
      'eligibility' => '8,1,2,3,4,5,6,7',
      'frequency' => 'Weekly',
      'number_of_times' => '1',
      'color' => '#f1c40f',
      'leave_category' => '',
      'combine_with_other' => 0,
      'is_paid' => 'paid',
    ),
  ),
  'shift_settings' => 
  array (
    0 => 
    array (
      'ShiftName' => 'Morning Shift',
      'StartTime' => '04:00',
      'EndTime' => '12:00',
      'TotalHours' => '8:0',
    ),
    1 => 
    array (
      'ShiftName' => 'Afternoon Shift',
      'StartTime' => '12:00',
      'EndTime' => '20:00',
      'TotalHours' => '8:0',
    ),
    2 => 
    array (
      'ShiftName' => 'Evening Shift',
      'StartTime' => '20:00',
      'EndTime' => '04:00',
      'TotalHours' => '8:0',
    ),
    3 => 
    array (
      'ShiftName' => 'Office Shift',
      'StartTime' => '08:00',
      'EndTime' => '17:00',
      'TotalHours' => '9:0',
    ),
  ),
  'payroll_config' => 
  array (
    0 => 
    array (
      'cutoff_day' => 25,
    ),
  ),
  'attendance_parameters' => 
  array (
    0 => 
    array (
      'threshold_percentage' => 35,
      'auto_notifications' => 1,
      'evaluation_reminder' => 'after_3_days',
    ),
  ),
  'manningandbudgeting_configfiles' => 
  array (
    0 => 
    array (
      'consolidatdebudget' => NULL,
      'benifitgrid' => NULL,
      'xpat' => 55,
      'local' => 45,
    ),
  ),
  'resort_benefit_grade_levels' => 
  array (
    0 => 
    array (
      '_ref' => 1,
      'name' => 'LINE WORKERS',
      'status' => 'active',
    ),
    1 => 
    array (
      '_ref' => 2,
      'name' => 'SUP',
      'status' => 'active',
    ),
    2 => 
    array (
      '_ref' => 3,
      'name' => 'MGR',
      'status' => 'active',
    ),
    3 => 
    array (
      '_ref' => 4,
      'name' => 'HOD',
      'status' => 'active',
    ),
    4 => 
    array (
      '_ref' => 5,
      'name' => 'EXCOM',
      'status' => 'active',
    ),
  ),
  'resort_benefit_grade_level_ranks' => 
  array (
    0 => 
    array (
      'grade_level_id' => 2,
      'rank' => 5,
    ),
    1 => 
    array (
      'grade_level_id' => 3,
      'rank' => 4,
    ),
    2 => 
    array (
      'grade_level_id' => 5,
      'rank' => 1,
    ),
    3 => 
    array (
      'grade_level_id' => 5,
      'rank' => 3,
    ),
    4 => 
    array (
      'grade_level_id' => 5,
      'rank' => 7,
    ),
    5 => 
    array (
      'grade_level_id' => 5,
      'rank' => 8,
    ),
    6 => 
    array (
      'grade_level_id' => 4,
      'rank' => 2,
    ),
    7 => 
    array (
      'grade_level_id' => 1,
      'rank' => 6,
    ),
  ),
  'resort_benifit_grid' => 
  array (
    0 => 
    array (
      'emp_grade' => '1',
      'rank' => '6',
      'contract_status' => 'single',
      'effective_date' => '08/31/2026',
      'salary_period' => 'monthly',
      'service_charge' => '1.00',
      'ramadan_bonus' => '0.00',
      'ramadan_bonus_eligibility' => 'all',
      'uniform' => 'yes',
      'health_care_insurance' => 'yes',
      'day_off_per_week' => 1,
      'working_hrs_per_week' => 6,
      'emergency_leave' => NULL,
      'birthday_leave' => NULL,
      'public_holiday_per_year' => NULL,
      'paid_seak_leave_per_year' => NULL,
      'paid_companssionate_leave_per_year' => NULL,
      'paid_maternity_leave_per_year' => NULL,
      'paid_paternity_leave_per_year' => NULL,
      'paid_worked_public_holiday_and_friday' => NULL,
      'relocation_ticket' => 'no',
      'max_excess_luggage_relocation_expense' => '0.00',
      'ticket_upon_termination' => 'yes',
      'meals_per_day' => 6,
      'accommodation_status' => 'Four Share',
      'furniture_and_fixtures' => 'yes',
      'housekeeping' => 'once a week',
      'linen' => 'Bed sheet & pillow cover,Bath towel,Bath mat,Bedsheet,Blanket',
      'laundry' => 'once a week',
      'internet_access' => 'yes',
      'telephone' => 'no',
      'annual_leave' => NULL,
      'annual_leave_ticket' => 'yes',
      'rest_and_relaxation_leave_per_year' => NULL,
      'no_of_r_and_r_leave' => NULL,
      'total_rest_and_relaxation_leave_per_year' => NULL,
      'rest_and_relaxation_allowance' => NULL,
      'paid_circumcision_leave_per_year' => NULL,
      'overtime' => 'yes',
      'salary_paid_in' => 'USD',
      'loan_and_salary_advanced' => 'yes',
      'sports_and_entertainment_facilities' => 'Billiard,Football,Volleyball,Fishing trips,Table tennis,Beach access,Karaoke,Staff gym,Outdoor cinema,Fifa & PubG tournaments',
      'free_return_flight_to_male_per_year' => '2.00',
      'food_and_beverages_discount' => '50.00',
      'alchoholic_beverages_discount' => '50.00',
      'spa_discount' => '25.00',
      'dive_center_discount' => '25.00',
      'water_sports_discount' => '25.00',
      'friends_with_benefit_discount' => '20.00',
      'standard_staff_rate_for_single' => '100.00',
      'standard_staff_rate_for_double' => '200.00',
      'staff_rate_for_seaplane_male' => '40.00',
      'male_subsistence_allowance' => NULL,
      'custom_fields' => '[]',
      'status' => 'active',
    ),
    1 => 
    array (
      'emp_grade' => '2',
      'rank' => '5',
      'contract_status' => 'single',
      'effective_date' => '11/13/2025',
      'salary_period' => 'monthly',
      'service_charge' => '1.00',
      'ramadan_bonus' => '3000.00',
      'ramadan_bonus_eligibility' => NULL,
      'uniform' => 'yes',
      'health_care_insurance' => 'yes',
      'day_off_per_week' => 1,
      'working_hrs_per_week' => 6,
      'emergency_leave' => NULL,
      'birthday_leave' => NULL,
      'public_holiday_per_year' => 11,
      'paid_seak_leave_per_year' => NULL,
      'paid_companssionate_leave_per_year' => NULL,
      'paid_maternity_leave_per_year' => NULL,
      'paid_paternity_leave_per_year' => NULL,
      'paid_worked_public_holiday_and_friday' => NULL,
      'relocation_ticket' => 'no',
      'max_excess_luggage_relocation_expense' => NULL,
      'ticket_upon_termination' => 'yes',
      'meals_per_day' => 6,
      'accommodation_status' => 'Four Share',
      'furniture_and_fixtures' => 'yes',
      'housekeeping' => 'twice a week',
      'linen' => 'Bed sheet & pillow cover,Bath towel,Bath mat,Bedsheet,Blanket',
      'laundry' => 'twice a week',
      'internet_access' => 'yes',
      'telephone' => 'no',
      'annual_leave' => NULL,
      'annual_leave_ticket' => 'yes',
      'rest_and_relaxation_leave_per_year' => NULL,
      'no_of_r_and_r_leave' => NULL,
      'total_rest_and_relaxation_leave_per_year' => NULL,
      'rest_and_relaxation_allowance' => NULL,
      'paid_circumcision_leave_per_year' => NULL,
      'overtime' => 'yes',
      'salary_paid_in' => 'USD',
      'loan_and_salary_advanced' => 'yes',
      'sports_and_entertainment_facilities' => 'Billiard,Football,Volleyball,Fishing trips,Table tennis,Beach access,Karaoke,Staff gym,Outdoor cinema,Fifa & PubG tournaments',
      'free_return_flight_to_male_per_year' => '2.00',
      'food_and_beverages_discount' => '50.00',
      'alchoholic_beverages_discount' => '50.00',
      'spa_discount' => '25.00',
      'dive_center_discount' => '25.00',
      'water_sports_discount' => '25.00',
      'friends_with_benefit_discount' => '20.00',
      'standard_staff_rate_for_single' => '100.00',
      'standard_staff_rate_for_double' => '200.00',
      'staff_rate_for_seaplane_male' => '40.00',
      'male_subsistence_allowance' => NULL,
      'custom_fields' => '[]',
      'status' => 'active',
    ),
    2 => 
    array (
      'emp_grade' => '3',
      'rank' => '4',
      'contract_status' => 'single',
      'effective_date' => '11/13/2025',
      'salary_period' => 'monthly',
      'service_charge' => '1.00',
      'ramadan_bonus' => '3000.00',
      'ramadan_bonus_eligibility' => NULL,
      'uniform' => 'yes',
      'health_care_insurance' => 'yes',
      'day_off_per_week' => 1,
      'working_hrs_per_week' => 6,
      'emergency_leave' => NULL,
      'birthday_leave' => NULL,
      'public_holiday_per_year' => 11,
      'paid_seak_leave_per_year' => NULL,
      'paid_companssionate_leave_per_year' => NULL,
      'paid_maternity_leave_per_year' => NULL,
      'paid_paternity_leave_per_year' => NULL,
      'paid_worked_public_holiday_and_friday' => NULL,
      'relocation_ticket' => 'no',
      'max_excess_luggage_relocation_expense' => '0.00',
      'ticket_upon_termination' => 'yes',
      'meals_per_day' => 6,
      'accommodation_status' => 'Double Share',
      'furniture_and_fixtures' => 'yes',
      'housekeeping' => 'twice a week',
      'linen' => 'Bed sheet & pillow cover,Bath towel,Bath mat,Bedsheet,Blanket',
      'laundry' => 'twice a week',
      'internet_access' => 'yes',
      'telephone' => 'yes',
      'annual_leave' => NULL,
      'annual_leave_ticket' => 'yes',
      'rest_and_relaxation_leave_per_year' => NULL,
      'no_of_r_and_r_leave' => NULL,
      'total_rest_and_relaxation_leave_per_year' => NULL,
      'rest_and_relaxation_allowance' => NULL,
      'paid_circumcision_leave_per_year' => NULL,
      'overtime' => 'n/a',
      'salary_paid_in' => 'USD',
      'loan_and_salary_advanced' => 'yes',
      'sports_and_entertainment_facilities' => 'Billiard,Football,Volleyball,Fishing trips,Table tennis,Beach access,Karaoke,Staff gym,Outdoor cinema,Fifa & PubG tournaments',
      'free_return_flight_to_male_per_year' => '2.00',
      'food_and_beverages_discount' => '50.00',
      'alchoholic_beverages_discount' => '50.00',
      'spa_discount' => '25.00',
      'dive_center_discount' => '25.00',
      'water_sports_discount' => '25.00',
      'friends_with_benefit_discount' => '20.00',
      'standard_staff_rate_for_single' => '100.00',
      'standard_staff_rate_for_double' => '200.00',
      'staff_rate_for_seaplane_male' => '40.00',
      'male_subsistence_allowance' => NULL,
      'custom_fields' => '[]',
      'status' => 'active',
    ),
    3 => 
    array (
      'emp_grade' => '4',
      'rank' => '2',
      'contract_status' => 'married',
      'effective_date' => '11/13/2025',
      'salary_period' => 'monthly',
      'service_charge' => '1.00',
      'ramadan_bonus' => '3000.00',
      'ramadan_bonus_eligibility' => NULL,
      'uniform' => 'yes',
      'health_care_insurance' => 'yes',
      'day_off_per_week' => 1,
      'working_hrs_per_week' => 6,
      'emergency_leave' => NULL,
      'birthday_leave' => NULL,
      'public_holiday_per_year' => 11,
      'paid_seak_leave_per_year' => NULL,
      'paid_companssionate_leave_per_year' => NULL,
      'paid_maternity_leave_per_year' => NULL,
      'paid_paternity_leave_per_year' => NULL,
      'paid_worked_public_holiday_and_friday' => NULL,
      'relocation_ticket' => 'yes',
      'max_excess_luggage_relocation_expense' => '200.00',
      'ticket_upon_termination' => 'yes',
      'meals_per_day' => 6,
      'accommodation_status' => 'Single Share',
      'furniture_and_fixtures' => 'yes',
      'housekeeping' => '3 a week',
      'linen' => 'Bed sheet & pillow cover,Bath towel,Bath mat,Bedsheet,Blanket',
      'laundry' => '3 a week',
      'internet_access' => 'yes',
      'telephone' => 'yes',
      'annual_leave' => NULL,
      'annual_leave_ticket' => 'yes',
      'rest_and_relaxation_leave_per_year' => NULL,
      'no_of_r_and_r_leave' => NULL,
      'total_rest_and_relaxation_leave_per_year' => NULL,
      'rest_and_relaxation_allowance' => NULL,
      'paid_circumcision_leave_per_year' => NULL,
      'overtime' => 'n/a',
      'salary_paid_in' => 'USD',
      'loan_and_salary_advanced' => '',
      'sports_and_entertainment_facilities' => 'Billiard,Football,Volleyball,Fishing trips,Table tennis,Beach access,Karaoke,Staff gym,Outdoor cinema,Fifa & PubG tournaments',
      'free_return_flight_to_male_per_year' => '4.00',
      'food_and_beverages_discount' => '50.00',
      'alchoholic_beverages_discount' => '50.00',
      'spa_discount' => '25.00',
      'dive_center_discount' => '25.00',
      'water_sports_discount' => '25.00',
      'friends_with_benefit_discount' => '20.00',
      'standard_staff_rate_for_single' => '100.00',
      'standard_staff_rate_for_double' => '300.00',
      'staff_rate_for_seaplane_male' => '40.00',
      'male_subsistence_allowance' => NULL,
      'custom_fields' => '[]',
      'status' => 'active',
    ),
    4 => 
    array (
      'emp_grade' => '5',
      'rank' => '1,3,7,8',
      'contract_status' => 'married',
      'effective_date' => '11/13/2025',
      'salary_period' => 'monthly',
      'service_charge' => '1.00',
      'ramadan_bonus' => '3000.00',
      'ramadan_bonus_eligibility' => NULL,
      'uniform' => 'yes',
      'health_care_insurance' => 'yes',
      'day_off_per_week' => 1,
      'working_hrs_per_week' => 6,
      'emergency_leave' => NULL,
      'birthday_leave' => NULL,
      'public_holiday_per_year' => 11,
      'paid_seak_leave_per_year' => NULL,
      'paid_companssionate_leave_per_year' => NULL,
      'paid_maternity_leave_per_year' => NULL,
      'paid_paternity_leave_per_year' => NULL,
      'paid_worked_public_holiday_and_friday' => NULL,
      'relocation_ticket' => 'yes',
      'max_excess_luggage_relocation_expense' => '300.00',
      'ticket_upon_termination' => 'yes',
      'meals_per_day' => 6,
      'accommodation_status' => 'Single Share',
      'furniture_and_fixtures' => 'yes',
      'housekeeping' => '3 a week',
      'linen' => 'Bed sheet & pillow cover,Bath towel,Bath mat,Bedsheet,Blanket',
      'laundry' => '3 a week',
      'internet_access' => 'yes',
      'telephone' => 'yes',
      'annual_leave' => NULL,
      'annual_leave_ticket' => 'yes',
      'rest_and_relaxation_leave_per_year' => NULL,
      'no_of_r_and_r_leave' => NULL,
      'total_rest_and_relaxation_leave_per_year' => NULL,
      'rest_and_relaxation_allowance' => NULL,
      'paid_circumcision_leave_per_year' => NULL,
      'overtime' => 'n/a',
      'salary_paid_in' => 'USD',
      'loan_and_salary_advanced' => '',
      'sports_and_entertainment_facilities' => 'Billiard,Football,Volleyball,Fishing trips,Table tennis,Beach access,Karaoke,Staff gym,Outdoor cinema,Fifa & PubG tournaments',
      'free_return_flight_to_male_per_year' => '2.00',
      'food_and_beverages_discount' => '50.00',
      'alchoholic_beverages_discount' => '50.00',
      'spa_discount' => '25.00',
      'dive_center_discount' => '25.00',
      'water_sports_discount' => '25.00',
      'friends_with_benefit_discount' => '20.00',
      'standard_staff_rate_for_single' => '100.00',
      'standard_staff_rate_for_double' => '200.00',
      'staff_rate_for_seaplane_male' => '40.00',
      'male_subsistence_allowance' => NULL,
      'custom_fields' => '[]',
      'status' => 'active',
    ),
  ),
  'visa_document_types' => 
  array (
    0 => 
    array (
      'documentname' => 'Passport',
    ),
    1 => 
    array (
      'documentname' => 'Qualification',
    ),
    2 => 
    array (
      'documentname' => 'Curriculum Vitae',
    ),
    3 => 
    array (
      'documentname' => 'Photo',
    ),
  ),
  'visa_xpact_amounts' => 
  array (
    0 => 
    array (
      'Xpact_WalletName' => 'Withdrawn',
      'Xpact_Amt' => '2.00',
      'Xpact_Payment_Date' => NULL,
    ),
    1 => 
    array (
      'Xpact_WalletName' => 'Deposited',
      'Xpact_Amt' => '0.00',
      'Xpact_Payment_Date' => NULL,
    ),
    2 => 
    array (
      'Xpact_WalletName' => 'Reserved',
      'Xpact_Amt' => '1.00',
      'Xpact_Payment_Date' => NULL,
    ),
    3 => 
    array (
      'Xpact_WalletName' => 'Available',
      'Xpact_Amt' => '1.00',
      'Xpact_Payment_Date' => NULL,
    ),
  ),
  'visa_nationalities' => 
  array (
    0 => 
    array (
      'nationality' => 'Afghanistan',
      'amt' => '18300',
    ),
    1 => 
    array (
      'nationality' => 'Albanian',
      'amt' => '20900',
    ),
    2 => 
    array (
      'nationality' => 'Algerian',
      'amt' => '31100',
    ),
    3 => 
    array (
      'nationality' => 'Andorran',
      'amt' => '11900',
    ),
    4 => 
    array (
      'nationality' => 'Angolan',
      'amt' => '33000',
    ),
    5 => 
    array (
      'nationality' => 'Antiguan and Barbudan',
      'amt' => '42405',
    ),
    6 => 
    array (
      'nationality' => 'Argentine',
      'amt' => '51850',
    ),
    7 => 
    array (
      'nationality' => 'Armenian',
      'amt' => '15500',
    ),
    8 => 
    array (
      'nationality' => 'Australian',
      'amt' => '12200',
    ),
    9 => 
    array (
      'nationality' => 'Austrian',
      'amt' => '13850',
    ),
    10 => 
    array (
      'nationality' => 'Azerbaijani',
      'amt' => '12000',
    ),
    11 => 
    array (
      'nationality' => 'Bahamian',
      'amt' => '35420',
    ),
    12 => 
    array (
      'nationality' => 'Bahraini',
      'amt' => '6450',
    ),
    13 => 
    array (
      'nationality' => 'Bangladeshi',
      'amt' => '8000',
    ),
    14 => 
    array (
      'nationality' => 'Barbadian',
      'amt' => '50500',
    ),
    15 => 
    array (
      'nationality' => 'Belarusian',
      'amt' => '25850',
    ),
    16 => 
    array (
      'nationality' => 'Belgian',
      'amt' => '20795',
    ),
    17 => 
    array (
      'nationality' => 'Belizean',
      'amt' => '23130',
    ),
    18 => 
    array (
      'nationality' => 'Beninese',
      'amt' => '16300',
    ),
    19 => 
    array (
      'nationality' => 'Bhutanese',
      'amt' => '9000',
    ),
    20 => 
    array (
      'nationality' => 'Bolivian',
      'amt' => '31010',
    ),
    21 => 
    array (
      'nationality' => 'Bosnian and Herzegovinian',
      'amt' => '15750',
    ),
    22 => 
    array (
      'nationality' => 'Botswanan',
      'amt' => '43900',
    ),
    23 => 
    array (
      'nationality' => 'Brazilian',
      'amt' => '27020',
    ),
    24 => 
    array (
      'nationality' => 'Bruneian',
      'amt' => '13900',
    ),
    25 => 
    array (
      'nationality' => 'Bulgarian',
      'amt' => '12800',
    ),
    26 => 
    array (
      'nationality' => 'Cambodian',
      'amt' => '6900',
    ),
    27 => 
    array (
      'nationality' => 'Cameroonian',
      'amt' => '10800',
    ),
    28 => 
    array (
      'nationality' => 'Canadian',
      'amt' => '17650',
    ),
    29 => 
    array (
      'nationality' => 'Cape Verdean',
      'amt' => '25750',
    ),
    30 => 
    array (
      'nationality' => 'Chadian',
      'amt' => '14200',
    ),
    31 => 
    array (
      'nationality' => 'Chilean',
      'amt' => '31010',
    ),
    32 => 
    array (
      'nationality' => 'Chinese',
      'amt' => '8650',
    ),
    33 => 
    array (
      'nationality' => 'Colombian',
      'amt' => '20800',
    ),
    34 => 
    array (
      'nationality' => 'Congolese (Congo-Brazzaville)',
      'amt' => '21300',
    ),
    35 => 
    array (
      'nationality' => 'Congolese (Congo-Kinshasa)',
      'amt' => '22200',
    ),
    36 => 
    array (
      'nationality' => 'Costa Rican',
      'amt' => '18500',
    ),
    37 => 
    array (
      'nationality' => 'Ivorian',
      'amt' => '10300',
    ),
    38 => 
    array (
      'nationality' => 'Croatian',
      'amt' => '21700',
    ),
    39 => 
    array (
      'nationality' => 'Cuban',
      'amt' => '25500',
    ),
    40 => 
    array (
      'nationality' => 'Cypriot',
      'amt' => '9000',
    ),
    41 => 
    array (
      'nationality' => 'Czech',
      'amt' => '20800',
    ),
    42 => 
    array (
      'nationality' => 'Danish',
      'amt' => '12100',
    ),
    43 => 
    array (
      'nationality' => 'Djiboutian',
      'amt' => '14000',
    ),
    44 => 
    array (
      'nationality' => 'Dominican',
      'amt' => '23100',
    ),
    45 => 
    array (
      'nationality' => 'Ecuadorean',
      'amt' => '49000',
    ),
    46 => 
    array (
      'nationality' => 'Egyptian',
      'amt' => '7700',
    ),
    47 => 
    array (
      'nationality' => 'El Salvador',
      'amt' => '27000',
    ),
    48 => 
    array (
      'nationality' => 'Estonian',
      'amt' => '33200',
    ),
    49 => 
    array (
      'nationality' => 'Ethiopian',
      'amt' => '11800',
    ),
    50 => 
    array (
      'nationality' => 'Fijian',
      'amt' => '23200',
    ),
    51 => 
    array (
      'nationality' => 'Finnish',
      'amt' => '23400',
    ),
    52 => 
    array (
      'nationality' => 'French',
      'amt' => '12500',
    ),
    53 => 
    array (
      'nationality' => 'Gambian',
      'amt' => '37600',
    ),
    54 => 
    array (
      'nationality' => 'Georgian',
      'amt' => '5800',
    ),
    55 => 
    array (
      'nationality' => 'German',
      'amt' => '11900',
    ),
    56 => 
    array (
      'nationality' => 'Ghanaian',
      'amt' => '37400',
    ),
    57 => 
    array (
      'nationality' => 'Greek',
      'amt' => '10400',
    ),
    58 => 
    array (
      'nationality' => 'Guatemalan',
      'amt' => '12900',
    ),
    59 => 
    array (
      'nationality' => 'Guinean',
      'amt' => '40000',
    ),
    60 => 
    array (
      'nationality' => 'Guyanese',
      'amt' => '3800',
    ),
    61 => 
    array (
      'nationality' => 'Haitian',
      'amt' => '25000',
    ),
    62 => 
    array (
      'nationality' => 'Honduran',
      'amt' => '12200',
    ),
    63 => 
    array (
      'nationality' => 'Hungarian',
      'amt' => '19900',
    ),
    64 => 
    array (
      'nationality' => 'Icelander',
      'amt' => '27800',
    ),
    65 => 
    array (
      'nationality' => 'Indian',
      'amt' => '3500',
    ),
    66 => 
    array (
      'nationality' => 'Indonesian',
      'amt' => '5000',
    ),
    67 => 
    array (
      'nationality' => 'Iranian',
      'amt' => '5400',
    ),
    68 => 
    array (
      'nationality' => 'Iraqi',
      'amt' => '8500',
    ),
    69 => 
    array (
      'nationality' => 'Irish',
      'amt' => '15700',
    ),
    70 => 
    array (
      'nationality' => 'Israeli',
      'amt' => '10700',
    ),
    71 => 
    array (
      'nationality' => 'Italian',
      'amt' => '11400',
    ),
    72 => 
    array (
      'nationality' => 'Jamaican',
      'amt' => '27800',
    ),
    73 => 
    array (
      'nationality' => 'Japanese',
      'amt' => '8900',
    ),
    74 => 
    array (
      'nationality' => 'Jordanian',
      'amt' => '7700',
    ),
    75 => 
    array (
      'nationality' => 'Kazakhstani',
      'amt' => '11300',
    ),
    76 => 
    array (
      'nationality' => 'Kenyan',
      'amt' => '19500',
    ),
    77 => 
    array (
      'nationality' => 'Kiribati',
      'amt' => '51886',
    ),
    78 => 
    array (
      'nationality' => 'North Korean',
      'amt' => '10100',
    ),
    79 => 
    array (
      'nationality' => 'South Korean',
      'amt' => '10050',
    ),
    80 => 
    array (
      'nationality' => 'Kosovo',
      'amt' => '19160',
    ),
    81 => 
    array (
      'nationality' => 'Kuwaiti',
      'amt' => '6150',
    ),
    82 => 
    array (
      'nationality' => 'Kyrgyzstani',
      'amt' => '11500',
    ),
    83 => 
    array (
      'nationality' => 'Lao',
      'amt' => '6000',
    ),
    84 => 
    array (
      'nationality' => 'Latvian',
      'amt' => '15500',
    ),
    85 => 
    array (
      'nationality' => 'Lebanese',
      'amt' => '7400',
    ),
    86 => 
    array (
      'nationality' => 'Liberian',
      'amt' => '11600',
    ),
    87 => 
    array (
      'nationality' => 'Libyan',
      'amt' => '6900',
    ),
    88 => 
    array (
      'nationality' => 'Liechtensteiner',
      'amt' => '12800',
    ),
    89 => 
    array (
      'nationality' => 'Lithuanian',
      'amt' => '12750',
    ),
    90 => 
    array (
      'nationality' => 'Luxembourger',
      'amt' => '41200',
    ),
    91 => 
    array (
      'nationality' => 'Macau',
      'amt' => '9000',
    ),
    92 => 
    array (
      'nationality' => 'North Macedonian',
      'amt' => '14800',
    ),
    93 => 
    array (
      'nationality' => 'Malagasy',
      'amt' => '8250',
    ),
    94 => 
    array (
      'nationality' => 'Malawian',
      'amt' => '41400',
    ),
    95 => 
    array (
      'nationality' => 'Malaysian',
      'amt' => '3800',
    ),
    96 => 
    array (
      'nationality' => 'Malian',
      'amt' => '38500',
    ),
    97 => 
    array (
      'nationality' => 'Maltese',
      'amt' => '17200',
    ),
    98 => 
    array (
      'nationality' => 'Mauritanian',
      'amt' => '33500',
    ),
    99 => 
    array (
      'nationality' => 'Mauritian',
      'amt' => '10800',
    ),
    100 => 
    array (
      'nationality' => 'Mexican',
      'amt' => '20300',
    ),
    101 => 
    array (
      'nationality' => 'Moldovan',
      'amt' => '5800',
    ),
    102 => 
    array (
      'nationality' => 'Mongolian',
      'amt' => '15000',
    ),
    103 => 
    array (
      'nationality' => 'Montenegrin',
      'amt' => '18900',
    ),
    104 => 
    array (
      'nationality' => 'Moroccan',
      'amt' => '15600',
    ),
    105 => 
    array (
      'nationality' => 'Mozambican',
      'amt' => '32100',
    ),
    106 => 
    array (
      'nationality' => 'Burmese',
      'amt' => '6750',
    ),
    107 => 
    array (
      'nationality' => 'Namibian',
      'amt' => '29700',
    ),
    108 => 
    array (
      'nationality' => 'Nepalese',
      'amt' => '8000',
    ),
    109 => 
    array (
      'nationality' => 'Dutch',
      'amt' => '12500',
    ),
    110 => 
    array (
      'nationality' => 'New Zealander',
      'amt' => '14500',
    ),
    111 => 
    array (
      'nationality' => 'Nicaraguan',
      'amt' => '64000',
    ),
    112 => 
    array (
      'nationality' => 'Nigerien',
      'amt' => '11000',
    ),
    113 => 
    array (
      'nationality' => 'Nigerian',
      'amt' => '11000',
    ),
    114 => 
    array (
      'nationality' => 'Norwegian',
      'amt' => '23800',
    ),
    115 => 
    array (
      'nationality' => 'Omani',
      'amt' => '7700',
    ),
    116 => 
    array (
      'nationality' => 'Pakistani',
      'amt' => '3800',
    ),
    117 => 
    array (
      'nationality' => 'Palauan',
      'amt' => '25000',
    ),
    118 => 
    array (
      'nationality' => 'Palestinian',
      'amt' => '7100',
    ),
    119 => 
    array (
      'nationality' => 'Panamanian',
      'amt' => '11550',
    ),
    120 => 
    array (
      'nationality' => 'Papua New Guinean',
      'amt' => '25000',
    ),
    121 => 
    array (
      'nationality' => 'Paraguayan',
      'amt' => '89550',
    ),
    122 => 
    array (
      'nationality' => 'Peruvian',
      'amt' => '73200',
    ),
    123 => 
    array (
      'nationality' => 'Filipino',
      'amt' => '5500',
    ),
    124 => 
    array (
      'nationality' => 'Polish',
      'amt' => '41450',
    ),
    125 => 
    array (
      'nationality' => 'Portuguese',
      'amt' => '21800',
    ),
    126 => 
    array (
      'nationality' => 'Qatari',
      'amt' => '6150',
    ),
    127 => 
    array (
      'nationality' => 'Romanian',
      'amt' => '19000',
    ),
    128 => 
    array (
      'nationality' => 'Russian',
      'amt' => '16200',
    ),
    129 => 
    array (
      'nationality' => 'Rwandan',
      'amt' => '24000',
    ),
    130 => 
    array (
      'nationality' => 'Saint Kitts and Nevisian',
      'amt' => '50700',
    ),
    131 => 
    array (
      'nationality' => 'Saint Lucian',
      'amt' => '35000',
    ),
    132 => 
    array (
      'nationality' => 'Saint Vincentian',
      'amt' => '16700',
    ),
    133 => 
    array (
      'nationality' => 'Samoan',
      'amt' => '31700',
    ),
    134 => 
    array (
      'nationality' => 'San Marinese',
      'amt' => '10800',
    ),
    135 => 
    array (
      'nationality' => 'Saudi Arabian',
      'amt' => '6300',
    ),
    136 => 
    array (
      'nationality' => 'Senegalese',
      'amt' => '11200',
    ),
    137 => 
    array (
      'nationality' => 'Serbian',
      'amt' => '18900',
    ),
    138 => 
    array (
      'nationality' => 'Seychellois',
      'amt' => '11200',
    ),
    139 => 
    array (
      'nationality' => 'Sierra Leonean',
      'amt' => '35900',
    ),
    140 => 
    array (
      'nationality' => 'Singaporean',
      'amt' => '5400',
    ),
    141 => 
    array (
      'nationality' => 'Slovak',
      'amt' => '13200',
    ),
    142 => 
    array (
      'nationality' => 'Slovenian',
      'amt' => '19600',
    ),
    143 => 
    array (
      'nationality' => 'Somali',
      'amt' => '10900',
    ),
    144 => 
    array (
      'nationality' => 'South African',
      'amt' => '14200',
    ),
    145 => 
    array (
      'nationality' => 'Spanish',
      'amt' => '11900',
    ),
    146 => 
    array (
      'nationality' => 'Sri Lankan',
      'amt' => '4000',
    ),
    147 => 
    array (
      'nationality' => 'Sudanese',
      'amt' => '9800',
    ),
    148 => 
    array (
      'nationality' => 'Surinamese',
      'amt' => '28600',
    ),
    149 => 
    array (
      'nationality' => 'Eswatini',
      'amt' => '12400',
    ),
    150 => 
    array (
      'nationality' => 'Swedish',
      'amt' => '9000',
    ),
    151 => 
    array (
      'nationality' => 'Swiss',
      'amt' => '12400',
    ),
    152 => 
    array (
      'nationality' => 'Syrian',
      'amt' => '25600',
    ),
    153 => 
    array (
      'nationality' => 'Taiwan',
      'amt' => '7800',
    ),
    154 => 
    array (
      'nationality' => 'Tajikistani',
      'amt' => '13300',
    ),
    155 => 
    array (
      'nationality' => 'Tanzanian',
      'amt' => '13300',
    ),
    156 => 
    array (
      'nationality' => 'Thai',
      'amt' => '3900',
    ),
    157 => 
    array (
      'nationality' => 'Togolese',
      'amt' => '29800',
    ),
    158 => 
    array (
      'nationality' => 'Trinidadian and Tobagonian',
      'amt' => '25900',
    ),
    159 => 
    array (
      'nationality' => 'Tunisian',
      'amt' => '12850',
    ),
    160 => 
    array (
      'nationality' => 'Turkish',
      'amt' => '21200',
    ),
    161 => 
    array (
      'nationality' => 'Turkmen',
      'amt' => '5400',
    ),
    162 => 
    array (
      'nationality' => 'Ugandan',
      'amt' => '19600',
    ),
    163 => 
    array (
      'nationality' => 'Ukrainian',
      'amt' => '9400',
    ),
    164 => 
    array (
      'nationality' => 'Emirati',
      'amt' => '7050',
    ),
    165 => 
    array (
      'nationality' => 'British',
      'amt' => '13300',
    ),
    166 => 
    array (
      'nationality' => 'American',
      'amt' => '18750',
    ),
    167 => 
    array (
      'nationality' => 'Uruguayan',
      'amt' => '32900',
    ),
    168 => 
    array (
      'nationality' => 'Uzbekistani',
      'amt' => '8600',
    ),
    169 => 
    array (
      'nationality' => 'Vanuatuan',
      'amt' => '39195',
    ),
    170 => 
    array (
      'nationality' => 'Venezuelan',
      'amt' => '13200',
    ),
    171 => 
    array (
      'nationality' => 'Vietnamese',
      'amt' => '5700',
    ),
    172 => 
    array (
      'nationality' => 'Yemeni',
      'amt' => '17500',
    ),
    173 => 
    array (
      'nationality' => 'Zambian',
      'amt' => '18700',
    ),
    174 => 
    array (
      'nationality' => 'Zimbabwean',
      'amt' => '24800',
    ),
  ),
  'visa_config_reminders' => 
  array (
    0 => 
    array (
      'Work_Permit_Fee_reminder' => 'Active',
      'Work_Permit_Fee' => '2',
      'Slot_Fee_reminder' => 'Active',
      'Slot_Fee' => '2',
      'Insurance' => '2',
      'Insurance_reminder' => 'Active',
      'Medical_reminder' => 'Active',
      'Medical' => '2',
      'Visa_reminder' => 'Active',
      'Visa' => '1',
      'Passport' => '2',
      'Passport_reminder' => 'Active',
    ),
  ),
  'resort_geofences' => 
  array (
    0 => 
    array (
      'name' => 'Staff Accommodation',
      'color' => '#FF4444',
      'shape_type' => 'circle',
      'coordinates' => '{"center":{"lat":4.17349454,"lng":73.51209355},"radius":23}',
      'grace_period' => 5,
      'status' => 'active',
    ),
    1 => 
    array (
      'name' => 'Resort Island',
      'color' => '#2196F3',
      'shape_type' => 'polygon',
      'coordinates' => '[{"lat":4.1753023,"lng":73.50748458},{"lat":4.17440347,"lng":73.50643315},{"lat":4.17395405,"lng":73.5078279},{"lat":4.17438207,"lng":73.50845018}]',
      'grace_period' => 10,
      'status' => 'active',
    ),
  ),
  'resort_geo_locations' => 
  array (
    0 => 
    array (
      'latitude' => '4.17298387',
      'longitude' => '73.51214681',
      'polygon_coords' => '[{"lat":4.17325225,"lng":73.51041754},{"lat":4.1718276,"lng":73.51023515},{"lat":4.1719988,"lng":73.51134022},{"lat":4.17290834,"lng":73.51071795}]',
    ),
  ),
  'increment_types' => 
  array (
    0 => 
    array (
      'name' => 'Annual Performance Increment',
      'status' => 'Active',
    ),
    1 => 
    array (
      'name' => 'Service Recognition Increment',
      'status' => 'Active',
    ),
    2 => 
    array (
      'name' => 'Skill Enhancement Increment',
      'status' => 'Active',
    ),
  ),
  'business_hours' => 
  array (
    0 => 
    array (
      'day_of_week' => 'Monday',
      'start_time' => '09:00:00',
      'end_time' => '18:00:00',
    ),
    1 => 
    array (
      'day_of_week' => 'Tuesday',
      'start_time' => '09:00:00',
      'end_time' => '18:00:00',
    ),
    2 => 
    array (
      'day_of_week' => 'Wednesday',
      'start_time' => '09:00:00',
      'end_time' => '18:00:00',
    ),
    3 => 
    array (
      'day_of_week' => 'Thursday',
      'start_time' => '09:00:00',
      'end_time' => '18:00:00',
    ),
    4 => 
    array (
      'day_of_week' => 'Friday',
      'start_time' => '09:00:00',
      'end_time' => '18:00:00',
    ),
    5 => 
    array (
      'day_of_week' => 'Saturday',
      'start_time' => '09:00:00',
      'end_time' => '18:00:00',
    ),
    6 => 
    array (
      'day_of_week' => 'Sunday',
      'start_time' => '09:00:00',
      'end_time' => '18:00:00',
    ),
  ),
  'escalation_days' => 
  array (
    0 => 
    array (
      'EscalationDay' => 1,
    ),
  ),
  'employee_resignation_reasons' => 
  array (
    0 => 
    array (
      'reason' => 'Better Opportunity',
      'status' => 'Active',
    ),
    1 => 
    array (
      'reason' => 'Career growth opportunity',
      'status' => 'Active',
    ),
    2 => 
    array (
      'reason' => 'Relocation',
      'status' => 'Active',
    ),
    3 => 
    array (
      'reason' => 'Probation Failure',
      'status' => 'Active',
    ),
  ),
  'employee_resignation_withdrawal_configuration' => 
  array (
    0 => 
    array (
      'enable_resignation_withdrawal' => 1,
      'required_resignation_withdrawal_reason' => 0,
    ),
  ),
  'resort_deductions' => 
  array (
    0 => 
    array (
      'deduction_name' => 'Uniform Damage',
      'deduction_type' => 'Fixed',
      'currency' => 'USD',
      'maximum_limit' => '50',
      'maximum_limit_type' => 'fixed',
    ),
  ),
  'color_themes' => 
  array (
    0 => 
    array (
      'name' => 'A',
      'color' => '#ff2600',
    ),
    1 => 
    array (
      'name' => 'P',
      'color' => '#00f900',
    ),
    2 => 
    array (
      'name' => 'Day Off',
      'color' => '#000000',
    ),
  ),
);
