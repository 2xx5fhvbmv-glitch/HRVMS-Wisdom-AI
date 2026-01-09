<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Common;
use Carbon\Carbon;
class EmployeeTravelPass extends Model
{
    use HasFactory;
    protected $table = 'employee_travel_passes';

    protected $fillable = [
        'resort_id',
        'employee_id',
        'leave_request_id',
        'transportation',
        'arrival_date',
        'arrival_time',
        'arrival_mode',
        'arrival_reason',
        'departure_date',
        'departure_time',
        'departure_mode',
        'departure_reason',
        'status'
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

    public function leaveRequest()
    {
        return $this->belongsTo(EmployeeLeave::class, 'leave_request_id');
    }

    public function employeeTravelPassStatusData()
    {
        return $this->hasMany(EmployeeTravelPassStatus::class, 'travel_pass_id');
    }
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
    public function DepartureResortTransportation()
    {
        return $this->belongsTo(ResortTransportation::class,'departure_mode');
    }
    public function ArrivalResortTransportation()
    {
        return $this->belongsTo(ResortTransportation::class,'arrival_mode');
    }
}