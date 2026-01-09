<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BulidngAndFloorAndRoom extends Model
{
    use HasFactory;
    public  $table = 'bulidng_and_floor_and_rooms';
    public $fillable = ['resort_id','building_id','Floor','Room'];
    public function building()
    {
        return $this->belongsTo(BuildingModel::class, 'building_id', 'id');
    }

}
