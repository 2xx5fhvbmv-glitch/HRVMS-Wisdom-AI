<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeInsuranceChild extends Model
{
    use HasFactory;
    public $table = 'employee_insurance_children';
    public $fillable = ['employee_insurances_id','Premium','insurance_company','insurance_policy_number','insurance_coverage','insurance_start_date','insurance_end_date','insurance_file' ];


   
}


       