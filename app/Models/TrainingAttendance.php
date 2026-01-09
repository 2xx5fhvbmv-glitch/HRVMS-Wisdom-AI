<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Common;
use Carbon\Carbon;

class TrainingAttendance extends Model {
    use HasFactory;
    protected $table = 'training_attendance';

    protected $fillable = [
      'training_schedule_id',
      'employee_id',
      'attendance_date',
      'created_by',
      'status',
      'notes'
    ];

    public static function boot()
    {
      // parent::boot();
      // self::creating(function ($model) {
      //     $model->created_by = Auth::guard('resort-admin')->user()->id;
      // });


      parent::boot();

      self::saving(function ($model) {
      $user = Auth::guard('api')->user()->id ?? Auth::guard('resort-admin')->user()->id;

      // If a user is authenticated, set 'created_by' and 'modified_by'
      if ($user) {
          if (!$model->exists) {
              $model->created_by = $user;
          }
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

    public function schedule()
    {
      return $this->belongsTo(TrainingSchedule::class, 'training_schedule_id');
    }

    public function employee()
    {

      return $this->belongsTo(Employee::class, 'employee_id');
    }
}
