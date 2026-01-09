<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ResortVacantBudgetCost extends Model
{
    use HasFactory;

    protected $table = 'resort_vacant_budget_costs';
    
    protected $fillable = [
        'position_id',
        'department_id',
        'resort_id',
        'year',
        'vacant_index',
        'details',
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
    public function configurations()
    {
        return $this->hasMany(ResortVacantBudgetCostConfiguration::class, 'vacant_budget_cost_id');
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

