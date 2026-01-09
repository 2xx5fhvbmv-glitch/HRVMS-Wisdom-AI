<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Common;
use Carbon\Carbon;

class LearningProgram extends Model
{
    use HasFactory;
    protected $table = 'learning_programs';

    protected $fillable = [
        'resort_id',
        'name',
        'description',
        'objectives',
        'learning_category_id',
        'audience_type',
        'target_audience',
        'hours',
        'days',
        'frequency',
        'delivery_mode',
        'trainer',
        'prior_qualification',
    ];
    protected $casts = [
      'target_audience' => 'array', // Ensure array handling for multiple selections
    ];

    public static function boot(){
        parent::boot();
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
      return $this->belongsTo(LearningCategory::class, 'learning_category_id'); 
    }

    public function trainer()
    {
        return $this->belongsTo(Employee::class, 'trainer_id');
    }

    public function departments()
    {
        return $this->hasMany(ResortDepartment::class, 'id', 'target_audience');
    }

    public function positions()
    {
        return $this->hasMany(ResortPosition::class, 'id', 'target_audience');
    }

    public function employees()
    {
        return $this->hasMany(Employee::class, 'id', 'target_audience');
    }

    public function mandatoryprogram()
    {
        return $this->hasMany(MandatoryLearningProgram::class, 'id', 'program_id');
    }
}
