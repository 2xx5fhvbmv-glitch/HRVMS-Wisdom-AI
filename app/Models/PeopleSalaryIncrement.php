<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Common;
use App\Models\PeopleSalaryIncrementStatus;
use Carbon\Carbon;

class PeopleSalaryIncrement extends Model
{
    
    use HasFactory;

    protected $table = 'people_salary_increment';
    protected $guarded = ['id'];

    protected $casts = [
        'effective_date' => 'date',
        'previous_salary' => 'decimal:2',
        'new_salary' => 'decimal:2',
        'increment_amount' => 'decimal:2',
        'value' => 'decimal:2',
    ];

    const PAY_INCREASE_TYPE_PERCENTAGE = 'Percentage';
    const PAY_INCREASE_TYPE_FIXED = 'Fixed';
    
    const PAY_INCREASE_TYPES = [
        self::PAY_INCREASE_TYPE_PERCENTAGE => 'Percentage',
        self::PAY_INCREASE_TYPE_FIXED => 'Fixed',
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
    
     public function peopleSalaryIncrementStatusFinance()
    {
        return $this->hasOne(PeopleSalaryIncrementStatus::class, 'people_salary_increment_id')
            ->where('approval_rank', 'Finance');
    }

    public function peopleSalaryIncrementStatusGM()
    {
        return $this->hasOne(PeopleSalaryIncrementStatus::class, 'people_salary_increment_id')
            ->where('approval_rank', 'GM');
    }
}