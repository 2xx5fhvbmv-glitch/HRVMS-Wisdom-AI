<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollApproval extends Model
{
    protected $table = 'payroll_approvals';

    protected $fillable = [
        'payroll_id', 'resort_id', 'step_order', 'role_title',
        'approver_id', 'approver_name', 'status', 'remarks', 'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }
}
