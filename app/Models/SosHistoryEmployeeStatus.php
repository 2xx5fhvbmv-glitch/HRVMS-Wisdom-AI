<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;

class SosHistoryEmployeeStatus extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sos_history_employee_status';

    protected $fillable = [
        'sos_history_id',
        'emp_id',
        'mass_instruction',
        'address',
        'latitude',
        'longitude',
        'status',
        'created_by',
        'modified_by',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public static function boot(){
        parent::boot();

        self::saving(function ($model) {
             $user = Auth::guard('api')->user() ?? Auth::guard('resort-admin')->user();

            if ($user) {
                if (!$model->exists) {
                    $model->created_by = $user->id;
                }

                $model->modified_by = $user->id;
            }
        });
    }

    public function sosHistory()
    {
        return $this->belongsTo(SOSHistoryModel::class,'sos_history_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'emp_id');
    }
}
