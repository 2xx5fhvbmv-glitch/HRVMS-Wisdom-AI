<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;

class ClinicTreatment extends Model
{
    use HasFactory;
    public $table='clinic_treatment';
    public $fillable=['resort_id','appointment_id','employee_id','appointment_category_id','date','time','treatment_provided','additional_notes','external_consultation','priority','created_by','modified_by'];

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
}
