<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\Auth;
use App\Models\Admin;
use Carbon\Carbon;
use App\Helpers\Common;
class BudgetStatus extends Model
{
    use HasFactory;

    protected $table = 'budget_statuses';

    public $fillable = ['resort_id','message_id','Budget_id','status','comments','Department_id','OtherComments'];




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



        public function getCreatedByAttribute($value): ?string {
            $admin = Admin::select('first_name', 'last_name')->where('id', $this->attributes['created_by'])->first();

            $createdby = '';

            if($admin) {
                $createdby = ucwords($admin->first_name.' '.$admin->last_name);
            }

            return $createdby;
        }

        public function getCreatedAtAttribute($value): ?string {
            $dateFormat = Common::getDateFormateFromSettings();
            $timezone = config('app.timezone');
            $timeFormat = Common::getTimeFromSettings() == '12' ? 'h:i A' : 'H:i';
            $format = $dateFormat . ' ' . $timeFormat;
            return Carbon::parse($value)->setTimezone($timezone)->format($format);
        }

        public function getUpdatedAtAttribute($value): ?string {
            $dateFormat = Common::getDateFormateFromSettings();
            $timezone = config('app.timezone');
            $timeFormat = Common::getTimeFromSettings() == '12' ? 'h:i A' : 'H:i';
            $format = $dateFormat . ' ' . $timeFormat;
            return Carbon::parse($value)->setTimezone($timezone)->format($format);
        }

}
