<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeePdpPlan extends Model
{
    protected $table = 'employee_pdp_plans';
    protected $fillable = [
        'resort_id', 'employee_id', 'position_id', 'template_id',
        'duration', 'factors', 'status', 'created_by'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function position()
    {
        return $this->belongsTo(ResortPosition::class, 'position_id');
    }

    public function template()
    {
        return $this->belongsTo(Professionalform::class, 'template_id');
    }
}
