<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeInsurance extends Model
{
    use HasFactory;
    public $table = 'employee_insurances';
    public $fillable = [
        'resort_id',
        'employee_id',
        'Currency',
        
        'Premium',
        'insurance_company',
        'insurance_policy_number',
        'insurance_coverage',
        'insurance_start_date',
        'insurance_end_date',
        'insurance_file' 
        
    ];

    public function InsuranceChild()
    {
        return $this->hasMany(EmployeeInsuranceChild::class, 'employee_insurances_id', 'id');
    }
}
