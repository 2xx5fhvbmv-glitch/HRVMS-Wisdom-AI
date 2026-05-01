<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Common;
use Carbon\Carbon;

class SupportCategory extends Model {
    use HasFactory;
    protected $table = 'support_categories';

    protected $fillable = [
        'name',
        'status',
        'created_by',
        'modified_by'
    ];

    public static function boot(){
        parent::boot();

        self::saving(function ($model) {
            // Support categories are managed from BOTH the admin panel
            // (auth:admin guard) and the resort-admin panel (resort-admin guard).
            // Pick whichever guard is currently authenticated — hard-coding
            // resort-admin throws "property id on null" when an admin saves.
            $userId = null;
            if (Auth::guard('admin')->check()) {
                $userId = Auth::guard('admin')->user()->id;
            } elseif (Auth::guard('resort-admin')->check()) {
                $userId = Auth::guard('resort-admin')->user()->id;
            }

            if (!$model->exists && $userId) {
                $model->created_by = $userId;
            }
            if ($userId) {
                $model->modified_by = $userId;
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
        $admin = Admin::select('first_name', 'last_name')->where('id', $this->attributes['created_by'])->first();

        $createdby = '';

        if($admin) {
            $createdby = ucwords($admin->first_name.' '.$admin->last_name);
        }

        return $createdby;
    }


    public function supprots() {
      return $this->hasMany(Support::class);
    }

   

}
