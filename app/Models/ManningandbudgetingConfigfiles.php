<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use App\Helpers\Common;
class ManningandbudgetingConfigfiles extends Model
{
    use HasFactory;
    use SoftDeletes;
        protected $table = 'manningandbudgeting_configfiles';
        protected $fillable = [
            'consolidatdebudget','benifitgrid','xpat','resort_id','local'
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
}

