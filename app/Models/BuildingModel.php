<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuildingModel extends Model
{
    use HasFactory;
    public  $table = 'building_models';
    public $fillable = ['resort_id','BuildingName'];

    public function floorsAndRooms()
    {
        return $this->hasMany(BulidngAndFloorAndRoom::class, 'building_id', 'id');
    }


    // public function floorsAndRooms()
    // {
    //     return $this->hasMany(BulidngAndFloorAndRoom::class, 'building_id', 'id');
    // }

}
