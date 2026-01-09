<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


use Illuminate\Support\Facades\Auth;
use App\Models\ResortAdmin;
use Carbon\Carbon;
use App\Helpers\Common;

class ResortBenifitGrid extends Model
{
    use HasFactory;
    protected $table = 'resort_benifit_grid';
    protected $fillable = [
        'resort_id','emp_grade','rank','contract_status','effective_date','salary_period','service_charge','ramadan_bonus','uniform','health_care_insurance','day_off_per_week','working_hrs_per_week','emergency_leave','birthday_leave','public_holiday_per_year','paid_seak_leave_per_year','paid_companssionate_leave_per_year','paid_maternity_leave_per_year','paid_paternity_leave_per_year','paid_worked_public_holiday_and_friday','relocation_ticket','max_excess_luggage_relocation_expense','ticket_upon_termination','meals_per_day','accommodation_status','furniture_and_fixtures','housekeeping','linen','laundry','internet_access','telephone','annual_leave','annual_leave_ticket','rest_and_relaxation_leave_per_year','no_of_r_and_r_leave','total_rest_and_relaxation_leave_per_year','rest_and_relaxation_allowance','paid_circumcision_leave_per_year','overtime','salary_paid_in','loan_and_salary_advanced','sports_and_entertainment_facilities','free_return_flight_to_male_per_year','food_and_beverages_discount','alchoholic_beverages_discount','spa_discount','dive_center_discount','water_sports_discount','friends_with_benefit_discount','standard_staff_rate_for_single','standard_staff_rate_for_double','staff_rate_for_seaplane_male','male_subsistence_allowance','custom_fields','status'
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

    public function customLeaves() {
        return $this->hasMany(CustomLeave::class,"benefit_grid_id");
    }

    public function customBenefits() {
        return $this->hasMany(CustomBenefit::class,"benefit_grid_id");
    }

    public function customDiscounts() {
      return $this->hasMany(CustomDiscount::class,"benefit_grid_id");
    }

    public function benefitGridChild() {
      return $this->hasMany(ResortBenifitGridChild::class,"benefit_grid_id");
    }

}
