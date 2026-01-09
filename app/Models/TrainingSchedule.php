<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Common;
use Carbon\Carbon;

class TrainingSchedule extends Model {
    use HasFactory;
    protected $table = 'training_schedules';

    protected $fillable = [
        'resort_id',
        'training_id',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'venue',
        'description',
        'created_by',
        'status'
    ];

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->created_by = Auth::guard('resort-admin')->user()->id;
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

    public function participants() {
      return $this->hasMany(TrainingParticipant::class);
    }

    

    public function learningProgram()
    {
        return $this->belongsTo(LearningProgram::class, 'training_id');
    }

    public function trainingAttendances()
    {
        return $this->hasMany(TrainingAttendance::class, 'training_schedule_id');
    }

}
