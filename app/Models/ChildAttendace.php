<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChildAttendace extends Model
{

    use HasFactory;
    protected $table = 'child_attendaces';
    public $fillable = ['Parent_attd_id','InTime_out','OutTime_out','InTime_Location','OutTime_Location'];
}
