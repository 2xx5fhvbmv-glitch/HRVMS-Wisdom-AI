<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\Common;
use Carbon\Carbon;
use Auth;

class Events extends Model
{
    use HasFactory;

    protected $table='events';

    protected $fillable=['resort_id','title','date','time','description','location','reminder_days','events_for','status','created_by','modified_by'];

    public static function boot(){
        parent::boot();

        self::saving(function ($model) {
            $user = Auth::guard('api')->user();

            if ($user) {
                if (!$model->exists) {
                    $model->created_by = $user->GetEmployee->id;
                }
                $model->modified_by = $user->GetEmployee->id;
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

    public function ChildEvents()
    {
        return $this->hasMany(ChildEvents::class, 'event_id');
    }

}
