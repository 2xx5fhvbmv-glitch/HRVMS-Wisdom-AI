<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;
use Carbon\Carbon;
use App\Helpers\Common;
class IncidentsMeetingParticipants extends Model
{
    use HasFactory;

    protected $table="incidents_investigation_meetings_participants";

    protected $fillable = [
        'meeting_id',
        'participant_id',
        'participant_role'
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

    public function meeting()
    {
        return $this->belongsTo(IncidentsMeeting::class, 'meeting_id', 'id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'participant_id', 'id');
    }
}