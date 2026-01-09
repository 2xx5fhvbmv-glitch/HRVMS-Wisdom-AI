<?php

namespace App\Models;
use App\Mail\SendTwoFactorVerificationCodeMail;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use App\Helpers\Common;
use Carbon\Carbon;
use App\Notifications\AlternativeDateSuggestedNotification;

class EmployeeLeave extends Model
{
    use HasFactory,Notifiable;
    protected $table = 'employees_leaves';
    protected $fillable = [
        'resort_id',
        'emp_id',
        'leave_category_id',
        'from_date',
        'to_date','total_days','flag',
        'attachments','reason','task_delegation',
        'destination','transportation','status','departure_date','arrival_date'
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

    public function statusHistory()
    {
        return $this->hasMany(EmployeeLeaveStatus::class, 'leave_request_id');
    }

    public function travelPasses()
    {
        return $this->hasOne(EmployeeTravelPass::class, 'leave_request_id');
    }

    public function sendAlternateDateSuggessionNotification($leaveRecommend,$recipient,$leave,$from)
    {
      $employee = Employee::with('resortAdmin')->where('id',$leave->emp_id)->first();

      $employee->resortAdmin->notify(new AlternativeDateSuggestedNotification($leaveRecommend,$recipient,$leave,$from));
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'emp_id', 'id');
    }

    public function leaveStatusApproved()
    {
      return $this->hasOne(EmployeeLeaveStatus::class, 'leave_request_id', 'id')
            ->where('status', 'Approved')->latest();
    }

    public function leaveStatus(){
      return $this->belongsTo(EmployeeLeaveStatus::class, 'id','leave_request_id');
    }

    public function LeaveCategory()
    {
        return $this->belongsTo(LeaveCategory::class, 'leave_category_id', 'id');
    }
    
}