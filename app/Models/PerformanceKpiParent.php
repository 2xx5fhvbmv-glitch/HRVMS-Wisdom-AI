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
    protected $fillable =['property_goal','PropertyGoalbudget','PropertyGoalweightage','PropertyGoalscore','resort_id'];

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
}
