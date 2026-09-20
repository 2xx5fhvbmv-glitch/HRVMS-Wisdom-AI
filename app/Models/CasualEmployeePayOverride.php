<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CasualEmployeePayOverride extends Model
{
    protected $table = 'casual_employee_pay_overrides';
    protected $fillable = ['resort_id', 'employee_id', 'basic_salary', 'basic_salary_currency'];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }
}
