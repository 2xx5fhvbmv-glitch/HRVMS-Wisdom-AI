<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Basic salary + service-provider commission for one Casual position,
 * configured once (not per employee, not per month) — see §35 in
 * docs/casual-intern-phase-2-scaling-and-payroll.md. Only ever created
 * for resort_positions rows with employee_category = 'Casual'.
 */
class CasualPositionPayConfig extends Model
{
    protected $table = 'casual_position_pay_configs';
    protected $fillable = [
        'resort_id', 'position_id', 'basic_salary', 'basic_salary_currency',
        'commission_amount', 'commission_currency',
    ];

    public function position()
    {
        return $this->belongsTo(ResortPosition::class, 'position_id', 'id');
    }
}
