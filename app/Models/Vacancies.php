<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Common;
use Carbon\Carbon;
use App\Models\ResortDepartment;
use App\Models\ResortAdmin;
use App\Models\ResortPosition;
use App\Models\Applicant_form_data;
class Vacancies extends Model
{
    use HasFactory;

    protected  $table = 'vacancies';
    public  $fillable = [
      'Resort_id',
      'status',
      'budgeted',
      'Resort_id',
      'department',
      'required_starting_date',
      'position',
      'reporting_to',
      'rank',
      'division',
      'section',
      'employee_type',
      'duration',
      'service_provider_name',
      'amount_unit',
      'salary',
      'food',
      'accomodation',
      'transportation',
      'budgeted_salary',
      'propsed_salary',
      'budgeted_accomodation',
      'allowance',
      'service_charge',
      'uniform',
      'medical',
      'insurance',
      'pension',
      'recruitment',
      'is_required_local',
      'created_by',
      'modified_by',
      'Total_position_required'

    ];

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
        $admin = ResortAdmin::select('first_name', 'last_name')->where('id', $this->attributes['created_by'])->first();

        $createdby = '';
        if($admin) {
            $createdby = ucwords($admin->first_name.' '.$admin->last_name);
        }
        return $createdby;
    }

    public function departmentName()
    {
        return $this->belongsTo(ResortDepartment::class, 'department','id');
    }
    public function resortAdmin()
    {
        return $this->belongsTo(ResortAdmin::class, 'created_by', 'id');
    }

    // Define the relationship with ResortDepartments
    public function Getdepartment()
    {
        return $this->belongsTo(ResortDepartment::class, 'department', 'id');
    }

    // Define the relationship with ResortPositions
    public function Getposition()
    {
        return $this->belongsTo(ResortPosition::class, 'position', 'id');
    }


    public function GetApplications()
    {
        return $this->hasMany(Applicant_form_data::class, 'Parent_v_id', 'id');
    }

    public function children()
    {
        return $this->hasMany(TAnotificationChild::class, 'parent_ta_id', 'id');
    }
    // public function TAnotificationParent()
    // {
    //     return $this->hasOne(TAnotificationParent::class, 'V_id', 'id');
    // }
    public function TAnotificationParent()
    {
        return $this->hasMany(TAnotificationParent::class, 'V_id', 'id');
    }


}
