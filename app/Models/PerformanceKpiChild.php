<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceKpiChild extends Model
{
    use HasFactory;
    protected $table = 'performance_kpi_children';
    protected $fillable =['budget','weightage','score','kpi_parents_id'];
    public function parentKpi()
    {
        return $this->belongsTo(PerformanceKpiParent::class, 'parent_id');
    }
}
