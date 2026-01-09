<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use  App\Models\ResortAdmin;
use App\Models\ResortsChildNotifications;
use App\Helpers\Common;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
class TAnotificationChild extends Model
{
    use HasFactory;
    protected $table = 't_anotification_children';
    protected $fillable = [
        'Parent_ta_id',
        'status',
        'holding_date',
        'created_by',
        'modified_by',
        'reason',
        'Approved_By',

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

    public function TaNotificationParent()
    {
        return $this->belongsTo(TAnotificationParent::class, 'parent_ta_id', 'id');
    }

    public function ApplicationLinkRelation()
    {
        return $this->belongsTo(ApplicationLink::class, 'ta_child_id', 'id');
    }


    //  public function ApplicationLink()
    // {
    //     return $this->hasOne(ApplicationLink::class, 'ta_child_id', 'id');
    // }



    public function parent()
    {
        return $this->belongsTo(TAnotificationParent::class, 'parent_ta_id', 'id');
    }

    // Relationship with ApplicationLinkCollection
    public function ApplicationLinkCollection()
    {
        return $this->hasMany(ApplicationLink::class, 'ta_child_id', 'id');
    }
}
