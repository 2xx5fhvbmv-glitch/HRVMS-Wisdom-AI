<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChildAttendace extends Model
{

    use HasFactory;
    protected $table = 'child_attendaces';
    // The geofence columns were missing, so create() silently dropped every check-in's coordinates and zone result.
    public $fillable = ['Parent_attd_id','InTime_out','OutTime_out','InTime_Location','OutTime_Location',
        'InTime_Latitude','InTime_Longitude','InTime_Accuracy','InTime_WithinGeofence','InTime_GeofenceName',
        'OutTime_Latitude','OutTime_Longitude','OutTime_Accuracy','OutTime_WithinGeofence','OutTime_GeofenceName'];
}
