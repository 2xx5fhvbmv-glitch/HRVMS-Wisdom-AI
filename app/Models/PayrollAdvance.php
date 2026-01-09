<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Common;
use App\Models\PeopleSalaryIncrementStatus;
use Carbon\Carbon;

class PayrollAdvance extends Model
{
    
    use HasFactory;

    protected $table = 'payroll_advance';
    protected $guarded = ['id'];

    public static function boot(){
        parent::boot();

        self::saving(function ($model) {
            // if (!$model->exists) {
            //     $model->created_by = Auth::guard('resort-admin')->user()->id;
            // }

            // if(Auth::guard('resort-admin')->check()) {
            //     $model->modified_by = Auth::guard('resort-admin')->user()->id;
            // }
             $user = Auth::guard('api')->user() ?? Auth::guard('resort-admin')->user();

            if ($user) {
                if (!$model->exists) {
                    $model->created_by = $user->id;
                }
                $model->modified_by = $user->id;
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
        $admin = ResortAdmin::select('first_name', 'last_name')->where('id', $this->attributes['created_by'])->first();

        $createdby = '';
        if($admin) {
            $createdby = ucwords($admin->first_name.' '.$admin->last_name);
        }
        return $createdby;
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class,'employee_id','id');
    }

    public function guarantors()
    {
        return $this->hasMany(PayrollAdvanceGuarantor::class,'payroll_advance_id','id');
    }

    public function PayrollAdvanceAttachment()
    {
        return $this->hasMany(PayrollAdvanceAttachments::class,'payroll_advance_id','id');
    }

    public function guarantor()
    {
      return $this->hasOne(PayrollAdvanceGuarantor::class, 'payroll_advance_id', 'id')->latest('updated_at');
    }

    public function payrollRecoverySchedule(){
      return $this->hasMany(PayrollRecoverySchedule::class, 'payroll_advance_id', 'id');     
    }

    public function hrApprover(){
      return $this->belongsTo(Employee::class, 'hr_approved_by', 'id');
    }

    public function financeApprover(){
      return $this->belongsTo(Employee::class, 'finance_approved_by', 'id');
    }

    public function gmApprover(){
      return $this->belongsTo(Employee::class, 'gm_approved_by', 'id');
    }
}
