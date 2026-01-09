<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeePromotionApproval extends Model
{
    use HasFactory;
    protected $table = 'employee_promotions_approval';


    protected $fillable = [
        'promotion_id','status','approval_rank','approved_by','remarks',
    ];

    public function approver()
    {
        return $this->belongsTo(Employee::class, 'approved_by'); // or relevant approver model
    }

    public function promotion()
    {
        return $this->belongsTo(EmployeePromotion::class, 'promotion_id'); // main transfer request
    }
}