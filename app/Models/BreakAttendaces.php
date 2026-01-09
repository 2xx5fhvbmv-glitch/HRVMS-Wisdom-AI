<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakAttendaces extends Model
{

    use HasFactory;
    protected $table = 'break_attendaces';
    public $fillable = ['Parent_attd_id','Break_InTime','Break_OutTime','Total_Break_Time','InTime_Location','OutTime_Location'];
}
