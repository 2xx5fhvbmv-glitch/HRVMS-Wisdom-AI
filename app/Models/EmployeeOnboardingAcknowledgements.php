<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeOnboardingAcknowledgements extends Model
{
    use HasFactory;
    protected $table = 'employee_onboarding_acknowledgements';
    protected $fillable = [
        'resort_id',
        'employee_id',
        'acknowledgement_type',
        'acknowledged_date',
        'status',
        'created_at',
        'updated_at'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }
}
