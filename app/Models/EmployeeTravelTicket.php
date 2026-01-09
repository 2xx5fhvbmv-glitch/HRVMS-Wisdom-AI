<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Common;
use Carbon\Carbon;
class EmployeeTravelTicket extends Model
{
    use HasFactory;
    protected $table = 'travel_tickets';

    protected $fillable = [
        'resort_id',
        'leave_request_id',
        'employee_id',
        'ticket_file_path',
        'status','uploaded_by'
    ];

    public static function boot(){
        parent::boot();

        self::saving(function ($model) {
            if (!$model->exists) {
                $model->uploaded_by = Auth::guard('resort-admin')->user()->id;
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

    public function leaveRequest()
    {
        return $this->belongsTo(EmployeeLeave::class, 'leave_request_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}