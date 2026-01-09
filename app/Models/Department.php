<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin;
use Carbon\Carbon;
use App\Helpers\Common;

class Department extends Model
{
    use HasFactory;
    protected $table = 'department';
    protected $fillable = [
        'division_id',
        'name','code','short_name','status'
    ];

    public static function boot(){
        parent::boot();

        self::saving(function ($model) {
             // Skip if running in seeder or artisan command
             if (App::runningInConsole()) {
                return;
            }

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
        $admin = Admin::select('first_name', 'last_name')->where('id', $this->attributes['created_by'])->first();

        $createdby = '';

        if($admin) {
            $createdby = ucwords($admin->first_name.' '.$admin->last_name);
        }

        return $createdby;
    }

    public function divisions()
    {
        return $this->belongsTo(Division::class);
    }

    public function positions()
    {
        return $this->hasMany(Position::class, 'dept_id');
    }

    public function sections()
    {
        return $this->hasMany(Section::class);
    }
}
