<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Departmen;
use App\Models\Position;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;
use Carbon\Carbon;
use App\Helpers\Common;

class Employee extends Model
{
    use HasFactory,Notifiable;
    use SoftDeletes;
    protected $table="employees";
    protected $fillable = [
        'passport_number',
        'title',
        'resort_id', //unique resort id which take form the resort
        'Resort_role_id',
        'Emp_id',
        'device_token',
        'Dept_id',
        'Section_id',
        'Position_id',
        'reporting_to',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'password',
        'remember_token',
        'is_employee',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'zip',
        'country',
        'rank',
        'profile_photo',
        'created_by',
        'modified_by',
        'Admin_Parent_id',
        'nationality',
        'dob',
        'marital_status',
        'blood_group',
        'joining_date',
        'employment_type',
        'nid',
        'present_address',
        'biometric_file',
        'tin',
        'contract_type',
        'termination_date',
        'payment_mode',
        'bank_name','bank_branch','account_type',
        'IFSC_BIC','account_holder_name','account_no',
        'currency','IBAN',
        'probation_end_date',
        'probation_status','probation_review_date','probation_confirmed_by','probation_remarks',
        'probation_letter_path','confirmation_date',
        'contract_end_date',
        'basic_salary','basic_salary_currency','proposed_salary',
        'proposed_salary_unit',
        'incremented_date',
        'last_increment_salary_amount',
        'last_salary_increment_type',
        'notes',
        'division_id',
        'emg_cont_first_name',
        'emg_cont_last_name',
        'emg_cont_no',
        'emg_cont_alt_no',
        'emg_cont_relationship',
        'emg_cont_email',
        'emg_cont_nationality',
        'emg_cont_dob',
        'emg_cont_age',
        'emg_cont_education',
        'emg_cont_passport_no',
        'emg_cont_passport_expiry_date',
        'emg_cont_current_address',
        'emg_cont_permanent_address',
        'work_location',
        'status',
        'religion','resign_effective_date','last_working_day',
        'leave_destination',
        'selfie_image',
        'latitude',
        'longitude',
        'main_rank',
    ];

    public static function boot(){
        parent::boot();

        self::saving(function ($model) {
            if (!$model->exists) {
                $model->created_by = Auth::guard('resort-admin')->user()->id;
            }

            if(Auth::guard('resort-admin')->check()) {
                $model->modified_by = Auth::guard('resort-admin')->user()->id;
            }
        });
    }

    public function getCreatedAtAttribute($value): ?string {
      if($value == '') {
        return '';
      } else {
        $dateFormat = Common::getDateFormateFromSettings();
        $timezone = config('app.timezone');
        $timeFormat = Common::getTimeFromSettings() == '12' ? 'h:i A' : 'H:i';
        $format = $dateFormat . ' ' . $timeFormat;
        return Carbon::parse($value)->setTimezone($timezone)->format($format);
      }
    }

    public function getUpdatedAtAttribute($value): ?string {
      if($value == '') {
        return '';
      } else {
        $dateFormat = Common::getDateFormateFromSettings();
        $timezone = config('app.timezone');
        $timeFormat = Common::getTimeFromSettings() == '12' ? 'h:i A' : 'H:i';
        $format = $dateFormat . ' ' . $timeFormat;
        return Carbon::parse($value)->setTimezone($timezone)->format($format);
      }
    }

    public function getCreatedByAttribute($value): ?string {
        $admin = Admin::select('first_name', 'last_name')->where('id', $this->attributes['created_by'])->first();

        $createdby = '';

        if($admin) {
            $createdby = ucwords($admin->first_name.' '.$admin->last_name);
        }

        return $createdby;
    }

    public function position()
    {
        return $this->belongsTo(ResortPosition::class, 'Position_id', 'id');
    }

    public function division()
    {
        return $this->belongsTo(ResortDivision::class, 'division_id', 'id');
    }

    public function section()
    {
        return $this->belongsTo(ResortSection::class, 'section_id', 'id');
    }

    public function resortAdmin()
    {
        return $this->belongsTo(ResortAdmin::class, 'Admin_Parent_id', 'id');
    }

    public function department()
    {
        return $this->belongsTo(ResortDepartment::class, 'Dept_id', 'id');
    }

    public function education()
    {
        return $this->hasMany(EmployeeEducation::class);
    }

    public function experiance()
    {
        return $this->hasMany(EmployeeExperiance::class);
    }

    public function allowance()
    {
        return $this->hasMany(EmployeeAllowance::class);
    }

    public function document()
    {
        return $this->hasMany(EmployeesDocument::class);
    }

    public function language()
    {
        return $this->hasMany(EmployeeLanguage::class);
    }

    public function sosTeamMemberships()
    {
        return $this->hasMany(SOSTeamMemeberModel::class, 'emp_id', 'id');
    }
    
    public function sosTeams()
    {
        return $this->belongsToMany(
            SOSTeamManagementModel::class,
            'sos_team_members',
            'emp_id',     // Foreign key on pivot table referencing Employee
            'team_id',    // Foreign key on pivot table referencing Team
            'id',         // Local key on Employee table
            'id'          // Local key on Team table
        )->withPivot('role_id')->withTimestamps();
    }
    

    public function resort_divisions()
    {
        return $this->hasOne(ResortDivision::class, 'id', 'division_id');
    }

    public function resort_positions()
    {
      return $this->hasOne(ResortPosition::class, 'id', 'Position_id');
    }

    public function reportingTo()
    {
        return $this->belongsTo(Employee::class, 'reporting_to', 'id');
    }

    public function reportingToAdmin()
    {
        return $this->hasOneThrough(
            ResortAdmin::class,
            Employee::class,     // Intermediate model
            'id',                   // Foreign key on GetEmployee table (reporting_to)
            'id',                   // Foreign key on ResortAdmin table (Admin_Parent_id)
            'reporting_to',         // Local key on GetEmployee table
            'Admin_Parent_id'       // Local key on Employee table
        );
    }

    public function timeAndAttendances()
    {
        return $this->hasMany(PayrollTimeAndAttendance::class);
    }

    public function serviceCharges()
    {
        return $this->hasMany(PayrollServiceCharge::class);
    }

    public function deductions()
    {
        return $this->hasMany(PayrollDeduction::class);
    }

    public function committeeMemberships()
    {
        return $this->hasMany(IncidentCommitteeMember::class, 'member_id', 'id');
    }

    public function sosInitiatedBy()
    {
        return $this->hasMany(SOSHistoryModel::class, 'emp_initiated_by', 'id');
    }

    public function employeeLanguage()
    {
        return $this->hasMany(EmployeeLanguage::class, 'employee_id', 'id');
    }

    public function disciplinarySubmits()
    {
        return $this->hasMany(DisciplinarySubmit::class, 'Employee_id', 'id');
    }
    public function trainingParticipants()
    {
        return $this->hasMany(TrainingParticipant::class, 'employee_id', 'id');
    }
    public function promotions()
    {
        return $this->hasMany(EmployeePromotion::class, 'employee_id', 'id');
    }
    public function resignation()
    {
        return $this->hasOne(EmployeeResignation::class, 'employee_id', 'id')
                    ->where('status', 'Approved'); // Only show approved resignations
    }

    public function VisaExpiryDetails()
    {
        return $this->hasOne(VisaEmployeeExpiryData::class, 'employee_id', 'id');
    }
    public function WorkPermitMedicalRenewal()
    {
        return $this->hasOne(WorkPermitMedicalRenewal::class, 'employee_id', 'id');
    }
    public function QuotaSlotRenewal()
    {
        return $this->hasMany(QuotaSlotRenewal::class, 'employee_id', 'id');
    }

    public function VisaRenewal()
    {
        return $this->hasOne(VisaRenewal::class, 'employee_id', 'id');
    }

    public function WorkPermit()
    {
        return $this->hasMany(WorkPermit::class, 'employee_id', 'id');
    }

    public function bankDetails()
    {
        return $this->hasMany(EmployeeBankDetails::class, 'employee_id', 'id');
    }
    
    public function EmployeeInsurance()
    {
        return $this->hasOne(EmployeeInsurance::class, 'employee_id', 'id');
    }

    public function advancedPaymentRecovey(){
        return $this->hasMany(PayrollRecoverySchedule::class, 'employee_id', 'id');
    }
    public function EmployeeAttandance(){
        return $this->hasMany(ParentAttendace::class, 'Emp_id', 'id');
    }

    public function OnboardingAcknowledgements(){
        return $this->hasMany(EmployeeOnboardingAcknowledgements::class, 'employee_id', 'id');
    }

    public function EmployeeLeave()
    {
        return $this->hasMany(EmployeeLeave::class, 'emp_id', 'id');
    }

    public function EmployeeResignation()
    {
        return $this->hasMany(EmployeeResignation::class, 'employee_id', 'id');
    }

}
