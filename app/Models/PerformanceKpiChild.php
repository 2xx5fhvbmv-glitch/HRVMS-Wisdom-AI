<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceKpiChild extends Model
{
    use HasFactory;
    protected $table = 'performance_kpi_children';
    protected $fillable =['budget','weightage','score','kpi_parents_id','individual_goal','month','remarks','created_by'];

    public function parentKpi()
    {
        return $this->belongsTo(PerformanceKpiParent::class, 'kpi_parents_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\ResortAdmin::class, 'created_by');
    }
}
