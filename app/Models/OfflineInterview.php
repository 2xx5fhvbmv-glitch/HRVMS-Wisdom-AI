<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfflineInterview extends Model
{
    protected $table = 'offline_interviews';

    protected $fillable = [
        'resort_id', 'applicant_form_data_id',
        'wizard_status', 'current_step',
        'budgeted_or_out_of_budget', 'division_id', 'department_id', 'section_id',
        'position_id', 'reporting_to', 'position_title', 'rank', 'required_starting_date',
        'employee_type', 'service_provider_name', 'salary', 'food', 'accommodation',
        'transportation', 'budget_salary', 'proposed_salary', 'allowances', 'medical',
        'insurance', 'pension', 'service_charge', 'uniform', 'benefit_accommodation',
        'recruitment_methods',
        'shortlisted_by_ai', 'hr_shortlisted', 'hr_round_status', 'hod_round_status',
        'gm_round_status', 'round_comments',
        'is_selected', 'offer_letter_path', 'created_employee_id',
        'created_by', 'modified_by',
    ];

    protected $casts = [
        'recruitment_methods' => 'array',
        'shortlisted_by_ai'   => 'boolean',
        'hr_shortlisted'      => 'boolean',
        'required_starting_date' => 'date',
    ];

    public function applicant()
    {
        return $this->belongsTo(Applicant_form_data::class, 'applicant_form_data_id');
    }

    public function documents()
    {
        return $this->hasMany(OfflineInterviewDocument::class);
    }

    public function department()
    {
        return $this->belongsTo(ResortDepartment::class, 'department_id');
    }

    public function position()
    {
        return $this->belongsTo(ResortPosition::class, 'position_id');
    }

    public function section()
    {
        return $this->belongsTo(ResortSection::class, 'section_id');
    }

    public function division()
    {
        return $this->belongsTo(ResortDivision::class, 'division_id');
    }

    public function reportingTo()
    {
        return $this->belongsTo(Employee::class, 'reporting_to');
    }

    public function createdEmployee()
    {
        return $this->belongsTo(Employee::class, 'created_employee_id');
    }
}
