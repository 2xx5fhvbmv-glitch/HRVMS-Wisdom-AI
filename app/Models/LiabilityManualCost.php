<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiabilityManualCost extends Model
{
    protected $table = 'liability_manual_costs';
    protected $guarded = ['id'];

    protected $casts = [
        'cost_date' => 'date',
        'amount' => 'float',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
