<?php

namespace App\Models;

use App\Models\Admin;
use Carbon\Carbon;
use App\Helpers\Common;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PerformanceKpiParent extends Model
{
    use HasFactory;
    protected $table = 'performance_kpi_parents';
    protected $fillable =['property_goal','PropertyGoalbudget','budget_currency','PropertyGoalweightage','PropertyGoalscore','resort_id','status','responded_by','responded_at','individual_goal','response_budget','response_budget_currency','response_weightage','response_entries','gm_action','gm_action_at','gm_remarks','poor','fair','good','superb','poor_range','fair_range','good_range','superb_range'];

    protected $casts = [
        'response_entries' => 'array',
    ];

    public static function boot(){
        parent::boot();

        self::saving(function ($model) {
            if (!$model->exists) {
                $model->created_by = Auth::guard('resort-admin')->user()->id;
            }

            if(Auth::guard('resort-admin')->check()) {
                $model->modified_by = Auth::guard('resort-admin')->user()->id;
            }
        });
    }
    public function childrenKpi()
    {
        return $this->hasMany(PerformanceKpiChild::class, 'kpi_parents_id');
    }

    public function responder()
    {
        return $this->belongsTo(Employee::class, 'responded_by');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\ResortAdmin::class, 'created_by');
    }
}
