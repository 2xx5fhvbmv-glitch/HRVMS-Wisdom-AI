<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


use Illuminate\Support\Facades\Auth;
use App\Models\Admin;
use Carbon\Carbon;
use App\Helpers\Common;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use HasFactory;
    protected $table = 'notifications';
    protected $fillable = [
        'name','content','start_date','end_date','notice_color','font_color','is_sticky','status'
    ];

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

    public function getCreatedByAttribute($value): ?string {
        return \App\Helpers\Common::personName(Admin::class, $this->attributes['created_by'] ?? null);
    }

    // Define the relationship with Resort
    public function resorts()
    {
        return $this->belongsToMany(Resort::class, 'notification_resort');
    }

}
