<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Common;
use Carbon\Carbon;
class HrReminderRequestManning extends Model
{
    use HasFactory;

    protected $table = 'hr_reminder_request_mannings';

    public $fillable = ['message_id','reminder_message_subject'];




        public static function boot(){
            parent::boot();

            self::saving(function ($model) {
                if (!$model->exists) {
                    $model->created_by = Auth::guard('resort-admin')->user()->id;
                }

                if(Auth::guard('resort-admin')->check()) {
                    $model->modified_by = Auth::guard('resort-admin')->user()->id;
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
            $dateFormat = Common::getDateFormateFromSettings();
            $timezone = config('app.timezone');
            $timeFormat = Common::getTimeFromSettings() == '12' ? 'h:i A' : 'H:i';
            $format = $dateFormat . ' ' . $timeFormat;
            return Carbon::parse($value)->setTimezone($timezone)->format($format);
        }

        public function getUpdatedAtAttribute($value): ?string {
            $dateFormat = Common::getDateFormateFromSettings();
            $timezone = config('app.timezone');
            $timeFormat = Common::getTimeFromSettings() == '12' ? 'h:i A' : 'H:i';
            $format = $dateFormat . ' ' . $timeFormat;
            return Carbon::parse($value)->setTimezone($timezone)->format($format);
        }

        public function Modules()
        {
            return $this->belongsTo(Modules::class, 'Module_Id', 'id');
        }

}
