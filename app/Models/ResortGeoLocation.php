<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ResortAdmin;
use Carbon\Carbon;
use Auth;
use App\Helpers\Common;
class ResortGeoLocation extends Model
{
    use HasFactory;
    public $table = 'resort_geo_locations';
    public  $fillable = [
        'resort_id','longitude','latitude'
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

}
