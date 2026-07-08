<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


use Illuminate\Support\Facades\Auth;
use App\Models\Admin;
use Carbon\Carbon;
use App\Helpers\Common;

class Announcement extends Model
{
    use HasFactory;
    protected $table = 'announcement';
    protected $fillable = [
        'resort_id','title','employee_id','message','published_date','status','created_by','modified_by'
    ];

    public static function boot(){
        parent::boot();

        self::saving(function ($model) {
            // Mobile API requests authenticate via the 'api' guard, not
            // 'resort-admin' — calling ->user()->id here unguarded threw
            // "Attempt to read property 'id' on null" for any save from
            // an API context (same class of bug fixed in FilemangementSystem/
            // ChildFileManagement/AuditLogs).
            $user = Auth::guard('resort-admin')->user() ?? Auth::guard('api')->user();

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

    public function category()
    {
      return $this->belongsTo(AnnouncementCategory::class, 'title', 'id');
    } 

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    } 
}