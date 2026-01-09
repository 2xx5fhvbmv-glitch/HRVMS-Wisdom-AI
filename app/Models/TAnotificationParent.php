<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use  App\Models\ResortAdmin;
use App\Models\ResortsChildNotifications;
use App\Models\TAnotificationChild;
use App\Helpers\Common;
use Carbon\Carbon;


use Illuminate\Support\Facades\Auth;
class TAnotificationParent extends Model
{
    use HasFactory;
    protected $table = 't_anotification_parents';
    protected $fillable = [
        'Resort_id',
        'V_id',
        'created_by',
        'modified_by',

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


    // public function TaNotificationChildren()
    // {
    //     return $this->hasMany(TAnotificationChild::class, 'parent_ta_id', 'id');
    // }


    // public function ResortVacancy()
    // {
    //     return $this->belongsTo(Vacancies::class, 'V_id', 'id');
    // }

    public function vacancy()
    {
        return $this->belongsTo(Vacancies::class, 'V_id', 'id');
    }

    // Relationship with TAnotificationChildren
    public function TAnotificationChildren()
    {
        return $this->hasMany(TAnotificationChild::class, 'Parent_ta_id', 'id');
    }
}
