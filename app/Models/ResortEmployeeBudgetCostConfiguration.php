<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ResortEmployeeBudgetCostConfiguration extends Model
{
    use HasFactory;

    protected $table = 'resort_employee_budget_cost_configurations';

    protected $fillable = [
        'employee_id',
        'resort_budget_cost_id',
        'value',
        'currency',
        'hours',
        'department_id',
        'position_id',
        'resort_id',
        'year',
        'month',
        'basic_salary',
        'current_salary',
        'created_by',
        'modified_by'
    ];

    public static function boot()
    {
        parent::boot();

        self::saving(function ($model) {
            if (!$model->exists) {
                if(Auth::guard('resort-admin')->check()) {
                    $model->created_by = Auth::guard('resort-admin')->user()->id;
                }
            }

            if(Auth::guard('resort-admin')->check()) {
                $model->modified_by = Auth::guard('resort-admin')->user()->id;
            }
        });
    }

    // Relationships
    public function resortBudgetCost()
    {
        return $this->belongsTo(ResortBudgetCost::class, 'resort_budget_cost_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function department()
    {
        return $this->belongsTo(ResortDepartment::class, 'department_id');
    }

    public function position()
    {
        return $this->belongsTo(ResortPosition::class, 'position_id');
    }

    public function resort()
    {
        return $this->belongsTo(Resort::class, 'resort_id');
    }
}

