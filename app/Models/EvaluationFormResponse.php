<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Common;
use Carbon\Carbon;

class EvaluationFormResponse extends Model
{
    use HasFactory;
    protected $table="evaluation_form_responses";
    public  $fillable = ['form_id','training_id','participant_id','responses','created_by'];
    protected $casts = ['responses' => 'array'];

    public static function boot()
    {
        parent::boot();
        // Was resort-admin-only — fataled on null when an employee submits
        // this via the mobile app (api guard), which is the actual/only
        // real caller of this model (nothing wrote to this table before).
        self::creating(function ($model) {
            $user = Auth::guard('api')->user() ?? Auth::guard('resort-admin')->user();
            if ($user) {
                $model->created_by = $user->id;
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
    
    public function form()
    {
        return $this->belongsTo(EvaluationForm::class, 'form_id');
    }

    public function training()
    {
        return $this->belongsTo(TrainingSchedule::class, 'training_id');
    }

    public function participant()
    {
        return $this->belongsTo(TrainingParticipant::class, 'participant_id');
    }

   
}
