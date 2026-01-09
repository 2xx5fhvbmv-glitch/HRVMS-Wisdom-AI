<?php
// app/Models/ItineraryTemplate.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Employee;

use App\Helpers\Common;
use Carbon\Carbon;

class EmployeeItineraries extends Model
{
  protected $table = 'employee_itineraries';
    protected $fillable = [
        'resort_id', 'employee_id', 'template_id', 'greeting_message','arrival_date','arrival_time',
        'entry_pass_file','flight_ticket_file','pickup_employee_id','accompany_medical_employee_id',
        'domestic_flight_date','domestic_departure_time','domestic_arrival_time','resort_transportation_id','domestic_flight_ticket',
        'speedboat_name','speedboat_date','speedboat_departure_time','speedboat_arrival_time','captain_number','location',
        'seaplane_name','seaplane_date','seaplane_departure_time','seaplane_arrival_time','hotel_id','hotel_name','hotel_contact_no','booking_reference',
        'hotel_address','medical_center_name','medical_center_contact_no','medical_type','medical_date','medical_time','approx_time'
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

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    public function pickupemployee()
    {
        return $this->belongsTo(Employee::class, 'pickup_employee_id', 'id');
    }

    public function accompanymedicalemployee()
    {
        return $this->belongsTo(Employee::class, 'accompany_medical_employee_id', 'id');
    }

    public function template()
    {
        return $this->belongsTo(ItineraryTemplate::class, 'template_id', 'id');
    }

    public function meetings()
    {
        return $this->hasMany(EmployeeItinerariesMeeting::class, 'employee_itinerary_id', 'id');
    }
}
