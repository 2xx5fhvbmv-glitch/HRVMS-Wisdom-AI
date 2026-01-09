<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\ResortAdmin;
use App\Models\Admin;
use App\Models\ResortDepartment;
use App\Models\Modules;
use Carbon\Carbon;
use App\Helpers\Common;
use App\Models\Employee;

use App\Models\ResortSiteSettings;
class Resort extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'resort_name','resort_prefix','resort_id','resort_email','resort_phone','resort_it_email','resort_it_phone',
        'address1','address2','city','state','country','zip','same_billing_address','Position_access',
        'billing_address1','billing_address2','billing_city','billing_state','billing_country','billing_pincode','headoffice_address1','headoffice_address2','headoffice_city','headoffice_state','headoffice_country','headoffice_pincode','tin','payment_method','invoice_email','logo','email_logo','status','payment_status','due_date','invoice_status','service_package','contract_start_date','contract_end_date','no_of_users','support_preference','Support_SLA'
    ];

    protected $dates = ['created_at','updated_at','deleted_at'];

    protected $appends  = ['site_admin_logo'];

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

    public function resortAdmins()
    {
        return $this->hasMany(ResortAdmin::class, 'resort_id');
    }

    public function rolePermissions()
    {
        return $this->hasMany(ResortRoleModulePermission::class);
    }
    public function notifications()
    {
        return $this->belongsToMany(Notification::class, 'notification_resort');
    }

    public function ResortDepartment()
    {
        return $this->hasMany(ResortDepartment::class,  'resort_id');
    }


    public function ResortEmployee()
    {
        return $this->belongsTo(Employee::class,  'resort_id');
    }

    public function emailTemplates()
    {
        return $this->hasMany(TAEmailTemplate::class, 'Resort_id');
    }

    public function businessHours()
    {
        return $this->hasMany(BusinessHour::class);
    }

}
