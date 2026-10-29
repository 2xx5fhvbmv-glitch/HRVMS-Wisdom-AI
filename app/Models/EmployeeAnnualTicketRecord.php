<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Whether the company provided an employee's round-trip ticket for one Annual Leave (anniversary) cycle. */
class EmployeeAnnualTicketRecord extends Model
{
    protected $fillable = ['resort_id', 'employee_id', 'cycle_start_date', 'cycle_end_date', 'company_provided', 'decided_by', 'decided_at'];

    protected $casts = ['cycle_start_date' => 'date', 'cycle_end_date' => 'date', 'company_provided' => 'boolean', 'decided_at' => 'datetime'];
}
