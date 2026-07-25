<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;

class ClinicAppointmentCategories extends Model
{
    use HasFactory;
    public $table='clinic_appointment_categories';
    public $fillable=['resort_id','appointment_type','color','created_by','modified_by'];

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {

          $user = Auth::guard('api')->user() ?? Auth::guard('resort-admin')->user();

            if ($user) {
                if (!$model->exists) {
                    $model->created_by = $user->id;
                }
                // modified_by is NOT NULL with no default — was never set
                // here, silently stored as 0 locally (lenient sql_mode) but
                // would hard-fail with a real SQL error on any environment
                // running STRICT_TRANS_TABLES.
                $model->modified_by = $user->id;
            }
        });
    }
}
