<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class MonthlyCheckingModel extends Model
{
    use HasFactory;
    protected $table = 'monthly_checking_models';

    protected $fillable = [
        'resort_id','Checkin_id','tranining_id','emp_id','date_discussion','start_time','end_time','Meeting_Place','Area_of_Discussion','Area_of_Improvement','Time_Line','comment','employee_comment','status','created_by'
    ];

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
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'emp_id');
    }

}
