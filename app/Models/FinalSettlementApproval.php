<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinalSettlementApproval extends Model
{
    use HasFactory;

    protected $table = 'final_settlement_approvals';

    protected $fillable = [
        'final_settlement_id',
        'resort_id',
        'step_order',
        'role_title',
        'approver_id',
        'approver_name',
        'status',
        'remarks',
        'approved_at',
        'signature_img',
        'signature_name',
        'signed_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'signed_at' => 'datetime',
    ];

    public function finalSettlement()
    {
        return $this->belongsTo(FinalSettlement::class, 'final_settlement_id');
    }
}
