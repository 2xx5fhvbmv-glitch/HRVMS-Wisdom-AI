<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Common;
use Carbon\Carbon;

class LearningRequest extends Model
{
    use HasFactory;
    protected $table = 'learning_requests';

    protected $fillable = [
        'resort_id',
        'learning_id',
        'reason',
        'employee_id',
        'learning_manager_id',
        'start_date',
        'end_date',
        'status',
        'rejection_reason',
        'created_by',
    ];

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {

          $user = Auth::guard('api')->user() ?? Auth::guard('resort-admin')->user();

            if ($user) {
                if (!$model->exists) {
                    $model->created_by = $user->id;
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

    public function employees()
    {
        return $this->hasMany(LearningRequestEmployee::class);
    }

    public function learning()
    {
        return $this->belongsTo(LearningProgram::class, 'learning_id');
    }
}
