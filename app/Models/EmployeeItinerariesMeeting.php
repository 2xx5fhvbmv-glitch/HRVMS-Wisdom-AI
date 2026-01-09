<?php
// app/Models/ItineraryTemplate.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\Common;
use Carbon\Carbon;

class EmployeeItinerariesMeeting extends Model
{
    protected $table = 'employee_itineraries_meeting';
    protected $fillable = [
        'employee_itinerary_id', 'meeting_title', 'meeting_date', 'meeting_time','meeting_link','meeting_participant_ids'
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

    public function itiernary()
    {
        return $this->belongsTo(EmployeeItineraries::class, 'employee_itinerary_id', 'id');
    }
    
}
