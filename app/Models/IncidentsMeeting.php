<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Helpers\Common;
class IncidentsMeeting extends Model
{
    use HasFactory;

    protected $table="incidents_investigation_meetings";

    protected $fillable = [
        'incident_id',
        'meeting_subject',
        'meeting_date',
        'meeting_time',
        'location',
        'meeting_type',
        'meeting_agenda',
        'attachments',
        'created_by',
        'modified_by'
    ];
    public static function boot(){
        parent::boot();

        self::saving(function ($model) {
          $user = Auth::guard('resort-admin')->user();
  
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

    public function incidents()
    {
        return $this->belongsTo(Incidents::class, 'incident_id', 'id');
    }

    public function participant()
    {
        return $this->hasMany(IncidentsMeetingParticipants::class, 'meeting_id', 'id');
    }
}