<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use App\Models\Admin;
use Carbon\Carbon;
use App\Helpers\Common;
use App\Notifications\ShopkeeperRegistartionEmail;
use App\Notifications\ResetPassword;
use App\Notifications\ResetPasswordSuccessNotification;

class Shopkeeper extends Authenticatable
{
    use HasFactory,Notifiable;
    protected $table = 'shopkeepers';
    protected $fillable = [
        'resort_id',
        'name','email','password','contact_no','profile_photo'
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

    public function sendShopkeeperRegistrationEmail($shopkeeper,$password)
    {
      $this->notify(new ShopkeeperRegistartionEmail($shopkeeper,$password));
    }

    public function sendPasswordResetNotification($token)
    {
      $this->notify(new ResetPassword($token,"shopkeeper"));
    }

    public function sendPasswordResetSuccessNotification($admin, $password)
    {
      $this->notify(new ResetPasswordSuccessNotification("shopkeeper", $admin, $password));
    }

    public function resort()
    {
        return $this->belongsTo(Resort::class, 'resort_id', 'id');
    }

}