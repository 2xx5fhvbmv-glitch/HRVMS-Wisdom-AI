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
    
    public function form()
    {
        return $this->belongsTo(TrainingFeedbackForm::class, 'form_id');
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
