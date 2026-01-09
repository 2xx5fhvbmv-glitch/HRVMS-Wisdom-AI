<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Helpers\Common;
use App\Notifications\ConsentProductPurchaseNotification;

class Payroll extends Model
{
    use HasFactory;
    protected $table = 'payroll';
    protected $fillable = ['resort_id','start_date', 'end_date', 'status' ,'total_payroll','total_employees','draft_date','payment_date','city_ledger_file','payroll_unit'];

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

    public function timeAndAttendances()
    {
        return $this->hasMany(PayrollTimeAndAttendance::class);
    }

    public function employees()
    {
        return $this->hasMany(PayrollEmployees::class);
    }

    public function serviceCharges()
    {
        return $this->hasMany(PayrollServiceCharge::class);
    }

    public function deductions()
    {
        return $this->hasMany(PayrollDeduction::class);
    }

    public function reviews()
    {
        return $this->hasMany(PayrollReview::class);
    }

    public function payrollSummary()
    {
        return $this->hasOne(PayrollSummary::class);
    }
}