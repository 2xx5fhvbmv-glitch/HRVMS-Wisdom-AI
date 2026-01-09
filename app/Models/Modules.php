<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ResortModulePermission;
use App\Models\Admin;
use Illuminate\Support\Facades\App;
use App\Models\ModulePages;
use Carbon\Carbon;
use App\Helpers\Common;
use Auth;
class Modules extends Model
{
    use HasFactory;

    protected $table = 'modules';

    protected $fillable = [
        'module_name','status'
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

    public function getSiteAdminLogoAttribute()
    {
        if( $this->logo != NULL && $this->logo != "" ) {
            return url( config('settings.brand_logo_folder'))."/".$this->logo;
        } else {
            return '';
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

    public function permissions()
    {
        return $this->hasMany(ResortPagewisePermission::class, 'page_permission_id', 'id');
    }
    public function get_modulepage()
    {
        return $this->hasMany(ModulePages::class, 'module_id', 'id');
    }
   

}
