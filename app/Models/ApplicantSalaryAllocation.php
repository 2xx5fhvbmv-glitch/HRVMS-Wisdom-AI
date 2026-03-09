<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ApplicantSalaryAllocation extends Model
{
    use HasFactory;

    protected $table = 'applicant_salary_allocations';

    protected $fillable = [
        'applicant_id',
        'resort_id',
        'position_id',
        'department_id',
        'basic_salary',
        'currency',
        'allowances',
        'remarks',
        'created_by',
        'modified_by'
    ];

    protected $casts = [
        'allowances' => 'array',
    ];

    public static function boot()
    {
        parent::boot();

        self::saving(function ($model) {
            if (!$model->exists) {
                if (Auth::guard('resort-admin')->check()) {
                    $model->created_by = Auth::guard('resort-admin')->user()->id;
                }
            }

            if (Auth::guard('resort-admin')->check()) {
                $model->modified_by = Auth::guard('resort-admin')->user()->id;
            }
        });
    }
}
