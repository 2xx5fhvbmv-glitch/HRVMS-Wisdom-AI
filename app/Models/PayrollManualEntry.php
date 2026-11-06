<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** An amount HR typed or uploaded into a payroll run, with who / when / why (payroll G1, 2026-11-06). Removed entries keep their row. */
class PayrollManualEntry extends Model
{
    const KINDS = ['service_charge_pool', 'city_ledger', 'other_deduction', 'extra_payment'];

    protected $fillable = ['payroll_id', 'employee_id', 'kind', 'label', 'amount', 'currency', 'reason', 'created_by', 'deleted_by', 'deleted_at'];

    protected $casts = ['amount' => 'float', 'deleted_at' => 'datetime'];

    public function scopeLive($query)
    {
        return $query->whereNull('deleted_at');
    }
}
