<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class PerformanceCycle extends Model
{
    use HasFactory;
    public $table='performance_cycles';
    public $fillable=['resort_id','Cycle_Name','Start_Date','End_Date','CycleSummary','Self_Review','Self_Review_Templete','Manager_Review','Manager_Review_Templete'	,'Self_Activity_Start_Date','Self_Activity_End_Date','Manager_Activity_Start_Date','Manager_Activity_End_Date','CycleReminders','status'];
}
