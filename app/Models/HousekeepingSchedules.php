<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\Common;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class HousekeepingSchedules extends Model
{
    use HasFactory;

    protected $table = 'housekeeping_schedules';
    protected $fillable = ['resort_id','available_a_id','BuildingName','Floor','RoomNo', 'Assigned_To', 'date','time','special_instructions','clean_type','status','created_by','modified_by'];

    public static function boot(){
        parent::boot();

        self::saving(function ($model) {
        $user = Auth::guard('api')->user() ?? Auth::guard('resort-admin')->user();

        // If a user is authenticated, set 'created_by' and 'modified_by'
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
            $timezone   = config('app.timezone');
            $timeFormat = Common::getTimeFromSettings() == '12' ? 'h:i A' : 'H:i';
            $format     = $dateFormat . ' ' . $timeFormat;
            return Carbon::parse($value)->setTimezone($timezone)->format($format);
        }
    }

    public function getUpdatedAtAttribute($value): ?string {
        if($value == '') {
            return '';
        } else {
            $dateFormat = Common::getDateFormateFromSettings();
            $timezone   = config('app.timezone');
            $timeFormat = Common::getTimeFromSettings() == '12' ? 'h:i A' : 'H:i';
            $format     = $dateFormat . ' ' . $timeFormat;
            return Carbon::parse($value)->setTimezone($timezone)->format($format);
        }
    }
}
