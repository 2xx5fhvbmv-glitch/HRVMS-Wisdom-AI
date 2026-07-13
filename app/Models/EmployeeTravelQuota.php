<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeTravelQuota extends Model
{
    use HasFactory;

    protected $table = 'employee_travel_quotas';
    protected $fillable = [
        'resort_id',
        'employee_id',
        'transportation',
        'total_allowed',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function transportationOption()
    {
        return $this->belongsTo(ResortTransportation::class, 'transportation');
    }
}
