<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\Notifiable;
use Carbon\Carbon;
use App\Helpers\Common;
use App\Notifications\ConsentProductPurchaseNotification;

class Payment extends Model
{
    use HasFactory,Notifiable;
    protected $table = 'payments';
    protected $fillable = [
        'shopkeeper_id',
        'order_id','emp_id','purchased_date','product_id','quantity','price','status','cash_paid',
        'payroll_deducted','qr_code'
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

    public function sendConsentProductPurchaseNotification($payment,$shopkeeper)
    {
        $employee = Employee::with('resortAdmin')->where('id',$payment->emp_id)->first();

        $employee->resortAdmin->notify(new ConsentProductPurchaseNotification($payment, $shopkeeper));
    }

    public function getOutstandingAttribute()
    {
        return $this->price - $this->cash_paid - $this->payroll_deducted;
    }
    
    public function shopKeeper()
    {
        return $this->belongsTo(Shopkeeper::class, 'shopkeeper_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    
    
}
