<?php
namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Notifications\ResetPassword;
use App\Notifications\ResetPasswordSuccessNotification;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin;
use App\Models\Role;
use Carbon\Carbon;
use App\Helpers\Common;
use App\Notifications\AdminRegistrationEmail;

class Admin extends Authenticatable
{
  use Notifiable;
  use SoftDeletes;
  protected $guard = 'admin';
  protected $table = 'admins';

  protected $fillable = [
    'first_name', 'last_name', 'middle_name', 'email', 'password', 'role_id', 'profile_picture', 'home_phone', 'cell_phone', 'start_date', 'address', 'sms', 'allow_login', 'notes', 'status', 'type', 'added_by', 'created_by', 'modified_by', ''
  ];

  protected $hidden = [
    'password', 'remember_token',
  ];

  protected $dates = ['created_at','updated_at','deleted_at'];

  protected $appends = ['admin_profile'];

  public static function boot(){
    parent::boot();

    self::saving(function ($model) {
      if (!$model->exists) {
        $model->created_by = Auth::guard('admin')->user()->id;
      }

      if(Auth::guard('admin')->check()) {
        $model->modified_by = Auth::guard('admin')->user()->id;
      }
    });
  }

  public function getCreatedByAttribute($value): ?string {
    $admin = Admin::select('first_name', 'last_name')->where('id', $this->attributes['created_by'])->first();

    $createdby = '';

    if($admin) {
      $createdby = ucwords($admin->first_name.' '.$admin->last_name);
    }

    return $createdby;
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

  public function getAdminProfileAttribute()
  {
    if( $this->profile_picture != NULL && $this->profile_picture != "" ) {
      return url( config('settings.admin_folder'))."/".$this->profile_picture;
    } else {
      return asset('admin_assets/files/default-pic.jpg');
    }
  }

  public function sendPasswordResetNotification($token)
  {
    $this->notify(new ResetPassword($token,"admin"));
  }

  public function sendPasswordResetSuccessNotification($admin, $password)
  {
    $this->notify(new ResetPasswordSuccessNotification("admin", $admin, $password));
  }

  public function sendAdminRegistrationEmail($admin,$password)
  {
    $this->notify(new AdminRegistrationEmail($admin,$password));
  }

}
