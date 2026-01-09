<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;

class ClinicMedicalCertificate extends Model
{
    use HasFactory;
    public $table='clinic_medical_certificate';
    public $fillable=['resort_id','appointment_id','clinic_treatment_id','leave_request_id','employee_id','appointment_category_id','start_date','end_date','description','attachment','created_by','modified_by'];

    
    public static function boot(){
        parent::boot();

        self::saving(function ($model) {
            $user = Auth::guard('api')->user() ?? Auth::guard('resort-admin')->user();

            if ($user) {
                if (!$model->exists) {
                    $model->created_by = $user->id;
                }
            }
            $user = Auth::guard('api')->user() ?? Auth::guard('resort-admin')->user();

            if ($user) {
                if (!$model->exists) {
                    $model->modified_by = $user->id;
                }
            }
            
        });
    }
}
