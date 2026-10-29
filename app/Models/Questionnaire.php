<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Helpers\Common;
use Auth;
class Questionnaire extends Model
{

    use HasFactory;
    protected $table = 'questionnaires';
    protected $fillable = ['Resort_id','Division_id', 'Department_id', 'Position_id', 'video'];
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
        return \App\Helpers\Common::personName(ResortAdmin::class, $this->attributes['created_by'] ?? null);
    }
   public function questionnaireChildren()
    {
        return $this->hasMany(QuestionnaireChild::class, 'Q_Parent_id', 'id');
    }

    public function questionnaireVideo()
    {
        return $this->hasMany(VideoQuestion::class, 'Q_Parent_id', 'id');
    }


}
