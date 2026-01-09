<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Common;
use Carbon\Carbon;
class EmployeesLeaveTransportation extends Model
{
    use HasFactory;
    protected $table = 'employees_leave_transportation';

    protected $fillable = [
        'leave_request_id',
        'transportation',
        'trans_arrival_date',
        'trans_departure_date',
        'reason',
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
}