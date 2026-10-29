<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


use Illuminate\Support\Facades\Auth;
use App\Models\ResortAdmin;
use Carbon\Carbon;
use App\Helpers\Common;

class ResortBudgetCost extends Model
{
    use HasFactory;
    protected $table = 'resort_budget_costs';
    protected $fillable = [
        'resort_id','cost_title','particulars','amount','cost_type','frequency','details','status','is_payroll_allowance'
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
        return \App\Helpers\Common::personName(ResortAdmin::class, $this->attributes['created_by'] ?? null);
    }

}