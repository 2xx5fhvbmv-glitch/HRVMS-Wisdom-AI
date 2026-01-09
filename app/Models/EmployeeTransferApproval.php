<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeTransferApproval extends Model
{
    use HasFactory;
    protected $table = 'employee_transfers_approval';


    protected $fillable = [
        'transfer_id','status','approval_rank','approved_by','remarks',
    ];

    public function approver()
    {
        return $this->belongsTo(Employee::class, 'approved_by'); // or relevant approver model
    }

    public function transfer()
    {
        return $this->belongsTo(EmployeeTransfer::class, 'transfer_id'); // main transfer request
    }
}