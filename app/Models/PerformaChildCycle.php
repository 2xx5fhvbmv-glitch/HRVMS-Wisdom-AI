<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformaChildCycle extends Model
{
    use HasFactory;
    public $table = 'performa_child_cycles';
    protected $fillable = [
        'Parent_cycle_id', 'Emp_main_id', 'Manager_id', 'template_id', 'is_gm_review',
        'self_review_status', 'manager_review_status',
        'self_review_data', 'manager_review_data',
        'Self_review_date', 'Manager_review_date',
    ];

}
