<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Models\ResortAdmin;
use Carbon\Carbon;
use App\Helpers\Common;

class EmployeeResignation extends Model
{
    use HasFactory;
    protected $table = 'employee_resignation';

    protected $fillable = [
        'resort_id',
        'employee_id',
        'reason',
        'resignation_date',
        'certificate_issue',
        'full_and_final_settlement',
        'departure_arrangements',
        'last_working_day',
        'immediate_release',
        'comments',
        'resignation_letter',
        'status',
        'hod_status',
        'hod_meeting_status',
        'hod_comments',
        'hod_id',
        'hr_status',
        'hr_meeting_status',
        'hr_comments',
        'hr_id',
        'rejected_reason',
        'withdraw_reason',
        'created_at',
        'updated_at',
        'Deposit_withdraw',
        'Deposit_Amt'
    ];

    
    protected $casts = ['departure_arrangements' => 'array'];

    public static function boot(){
        parent::boot();
    }



    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function reason_title(){
        return $this->belongsTo(EmployeeResignationReason::class, 'reason');
    }

    public function assignedForms()
    {
        return $this->hasMany(ExitClearanceFormAssignment::class, 'emp_resignation_id');
    }
    public function assignedForm()
    {
        return $this->hasOne(ExitClearanceFormAssignment::class, 'emp_resignation_id');
    }

    public function meetingSchedule()
    {
        return $this->hasMany(ResignationMeetingSchedule::class, 'resignationId');
    }
    public function createdBy()
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }
    public function hod()
    {
        return $this->belongsTo(Employee::class, 'hod_id');
    }
    public function hr()
    {
        return $this->belongsTo(Employee::class, 'hr_id');
    }
}