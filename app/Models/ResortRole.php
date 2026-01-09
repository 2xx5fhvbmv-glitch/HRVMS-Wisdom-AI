<?php
namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\ResortAdmin;
use Carbon\Carbon;
use App\Helpers\Common;

class ResortRole extends Authenticatable
{
  use SoftDeletes;
  protected $table = 'resort_roles';

  protected $guard = 'resort-admin';

  protected $fillable = ['resort_id', 'name', 'status', 'created_by', 'modified_by', 'updated_at'];
  
  protected $dates = ['created_at','updated_at'];

  public static function boot(){
    parent::boot();

    self::saving(function ($model) {
      if (!$model->exists && Auth::guard('resort-admin')->check()) {
        $model->created_by = Auth::guard('resort-admin')->user()->id;
      }

      if(Auth::guard('resort-admin')->check()) {
        $model->modified_by = Auth::guard('resort-admin')->user()->id;
      }
    });
  }

  public function getCreatedByAttribute($value): ?string {
    $admin = ResortAdmin::select('first_name', 'last_name')->where('id', $this->attributes['created_by'])->first();

    $createdby = '';

    if($admin) {
      $createdby = ucwords($admin->first_name.' '.$admin->last_name);
    }
    return $createdby;
  }

  public function getUpdatedAtAttribute($value): ?string {
    $dateFormat = Common::getDateFormateFromSettings();
    $timezone = config('app.timezone');
    $timeFormat = Common::getTimeFromSettings() == '12' ? 'h:i A' : 'H:i';
    $format = $dateFormat . ' ' . $timeFormat;
    return Carbon::parse($value)->setTimezone($timezone)->format($format);
  }
}
