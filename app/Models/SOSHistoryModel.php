<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Auth;

class SOSHistoryModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table="sos_history";

    protected $fillable = [
        'resort_id',
        'emergency_id',
        'emp_initiated_by',
        'location',
        'latitude',
        'longitude',
        'status',
        'date',
        'time',
        'emergency_description',
        'sos_approved_by',
        'sos_approved_time',
        'sos_approved_date',
        'employee_message',
        'team_message',
        'rejected_message',
        'mass_instructions',

    ];

    protected $dates = ['created_at','updated_at','deleted_at'];

    public static function boot(){
        parent::boot();

        self::saving(function ($model) {
            // if (!$model->exists) {
            //     $model->created_by = Auth::guard('resort-admin')->user()->id;
            // }

            // if(Auth::guard('resort-admin')->check()) {
            //     $model->modified_by = Auth::guard('resort-admin')->user()->id;
            // }

            $user = Auth::guard('api')->user() ?? Auth::guard('resort-admin')->user();

            if ($user) {
                if (!$model->exists) {
                    $model->created_by = $user->id;
                }
                $model->modified_by = $user->id;
            }
        });
    }

    public function getSos()
    {
        return $this->belongsTo(SOSEmergencyTypesModel::class,'emergency_id','id');
    }

    public function employee(){
        return $this->belongsTo(Employee::class,'emp_initiated_by','id');
    }

}
