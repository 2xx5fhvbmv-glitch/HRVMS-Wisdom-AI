<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;

class ClinicAppointment extends Model
{
    use HasFactory;
    public $table='clinic_appointment';
    public $fillable=['resort_id','employee_id','doctor_id','appointment_category_id','date','time','description','status','created_by','modified_by'];

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {

          $user = Auth::guard('api')->user() ?? Auth::guard('resort-admin')->user();

            if ($user) {
                if (!$model->exists) {
                    $model->created_by = $user->id;
                }
            }
        });
    }

    public function appointmentCategory()
    {
        return $this->hasOne(ClinicAppointmentCategories::class, 'id', 'appointment_category_id');
    }

    public function employee()
    {
        return $this->hasOne(Employee::class, 'id', 'employee_id');
    }
}
