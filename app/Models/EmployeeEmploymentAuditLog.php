<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One row per field changed via the Employment tab on the Employee
 * Detail page. Written by EmployeeController::updateEmploymentDetails
 * after diffing old vs new values; surfaced by employeeEmploymentLogs.
 */
class EmployeeEmploymentAuditLog extends Model
{
    use HasFactory;

    protected $table = 'employee_employment_audit_logs';

    protected $fillable = [
        'resort_id',
        'employee_id',
        'field',
        'label',
        'old_value',
        'new_value',
        'changed_by',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function changedByAdmin()
    {
        return $this->belongsTo(ResortAdmin::class, 'changed_by');
    }
}
