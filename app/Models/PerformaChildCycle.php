<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformaChildCycle extends Model
{
    use HasFactory;
    public $table = 'performa_child_cycles';
    protected $fillable =['Parent_cycle_id','Emp_main_id','Self_review_date','Manager_review_date','Manager_id'];

}
