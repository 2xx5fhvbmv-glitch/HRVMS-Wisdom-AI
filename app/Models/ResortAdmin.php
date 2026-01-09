<?php

namespace App\Models;

use App\Mail\SendTwoFactorVerificationCodeMail;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Common;
use App\Models\Resort;
use App\Models\Admin;

use App\Notifications\ResetPassword;
use App\Notifications\ResetPasswordSuccessNotification;
use App\Notifications\ResortRegistrationEmail;
use App\Models\Employee;
use App\Models\ResortPagewisePermission;
use Laravel\Passport\HasApiTokens;
use Laravel\Passport\Client as PassportClient;

class ResortAdmin extends Authenticatable
{
    use HasFactory,Notifiable,SoftDeletes;
    use HasApiTokens;

    protected $fillable = [
        'role_id',
        'resort_id',
        'first_name',
        'middle_name',
        'last_name',
        'gender',
        'email',
        'personal_phone',
        'password',
        'type',
        'is_master_admin',
        'is_employee',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'country',
        'zip',
        'status',
        'profile_picture',
        'signature_img',
        'menu_type',
        'Position_access'
    ];

    protected $appends  = ['full_name', 'emp_photo'];

    protected $hidden   = ['remember_token'];

    protected $dates = ['created_at','updated_at','deleted_at'];

    public static function boot(){
        parent::boot();

        self::saving(function ($model) {
          if (!$model->exists) {
            if(Auth::guard('resort-admin')->check()) {
              $model->created_by = Auth::guard('resort-admin')->user()->id;
            }
          }

          if(Auth::guard('resort-admin')->check()) {
            $model->modified_by = Auth::guard('resort-admin')->user()->id;
          }
        });

        // self::updating(function ($employee) {
        //   $fields_to_check = Common::getHistoryPermissionFields($employee->resort_id);
        //   $dirty = $employee->getDirty();

        //   foreach ($dirty as $field => $newValue) {
        //     if (in_array($field, $fields_to_check)) {
        //       $oldValue = $employee->getOriginal($field);
        //       $user_type = Common::getLoggedInResortUserType();
        //       $user_id = Auth::guard('resort-admin')->user()->id;

        //       FieldLogs::create([
        //         'resort_id' => $employee->resort_id,
        //         'employee_id' => $employee->id,
        //         'field_label' => $field,
        //         'previous_value' => $oldValue,
        //         'updated_value' => $newValue,
        //         'user_type' => $user_type,
        //         'user_id' => $user_id,
        //         'updated_at' => now(),
        //       ]);
        //     }
        //   }
        // });
    }

    public function getEmpPhotoAttribute()
    {
      if( $this->photo != NULL && $this->photo != "" ) {
      return url( config('settings.resort_admin_folder'))."/".$this->resort_id."/".$this->photo;
      } else {
      return '';
      }
    }

    public function getCreatedByAttribute($value): ?string {
      $admin = ResortAdmin::select('first_name', 'last_name')->where('id', $this->attributes['created_by'])->first();

      $createdby = '';

      if($admin) {
        $createdby = ucwords($admin->first_name.' '.$admin->last_name);
      }

      return $createdby;
    }

    public function getCreatedAtAttribute($value): ?string {
      $dateFormat = Common::getDateFormateFromSettings();
      $timezone = config('app.timezone');
      $timeFormat = Common::getTimeFromSettingsResort() == '12' ? 'h:i A' : 'H:i';
      $format = $dateFormat . ' ' . $timeFormat;
      return Carbon::parse($value)->setTimezone($timezone)->format($format);
    }

    public function getUpdatedAtAttribute($value): ?string {
      $dateFormat = Common::getDateFormateFromSettings();
      $timezone = config('app.timezone');
      $timeFormat = Common::getTimeFromSettingsResort() == '12' ? 'h:i A' : 'H:i';
      $format = $dateFormat . ' ' . $timeFormat;
      return Carbon::parse($value)->setTimezone($timezone)->format($format);
    }

    public function getFullNameAttribute($value): ?string {
      return ucwords($this->attributes['first_name'].' '.$this->attributes['last_name']);
    }

    public function sendPasswordResetNotification($token)
    {
      $this->notify(new ResetPassword($token,"resort"));
    }

    public function sendPasswordResetSuccessNotification($admin, $password)
    {
      $this->notify(new ResetPasswordSuccessNotification("resort", $admin, $password));
    }

    public function sendResortRegistrationEmail($resort,$admin,$password)
    {
      $this->notify(new ResortRegistrationEmail($resort,$admin,$password));
    }

    // Relationships
    public function resort()
    {
      return $this->belongsTo(Resort::class, 'resort_id', 'id');
    }

    public function getEmployee()
    {
      return $this->hasOne(Employee::class, 'Admin_Parent_id', 'id');
    }
    public function sendResortemployee($resort,$admin,$password)
    {
      $this->notify(new ResortRegistrationEmail($resort,$admin,$password));
    }

    public function sosTeamMemberships()
    {
        return $this->hasMany(SOSTeamMemeberModel::class, 'emp_id', 'id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }


}
