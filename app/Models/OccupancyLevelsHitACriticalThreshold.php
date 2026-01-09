<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OccupancyLevelsHitACriticalThreshold extends Model
{
    use HasFactory;
    protected $table = 'occupancy_levels_hit_a_critical_thresholds';
     protected $fillable = ['resort_id','building_id','Floor','RoomNo','ThresSoldLevel'];

}
