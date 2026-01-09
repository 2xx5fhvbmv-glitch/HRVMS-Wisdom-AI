<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Models\ResortAdmin;
use Carbon\Carbon;
use App\Helpers\Common;

class DutyRosterEntry extends Model
{
    use HasFactory;

    protected $table = 'duty_roster_entries';
    protected $fillable = [
       'resort_id',
       'roster_id',
       'Shift_id',
       'Emp_id',
       'OverTime',
       'CheckingTime',
       'DayWiseTotalHours',
       'CheckingOutTime',
       'date',
       'note',
       'Status',
       'CheckInCheckOut_Type',
       'OTStatus',
       'OTApproved_By',
       'created_by',
       'modified_by',
    ];

    public static function boot(){
        parent::boot();

        self::saving(function ($model) {
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

    public function resortAdmin() {
        return $this->belongsTo(ResortAdmin::class, 'created_by');
    }
    
    public function Getshift()
    {
        return $this->belongsTo(ShiftSettings::class, 'Shift_id');
    }
    
    public function Employee()
    {
        return $this->belongsTo(Employee::class, 'Emp_id');
    }
    
    public function dutyRoster()
    {
        return $this->belongsTo(DutyRoster::class, 'roster_id');
    }
}
