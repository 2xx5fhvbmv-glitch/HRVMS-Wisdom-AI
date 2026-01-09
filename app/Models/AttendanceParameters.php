<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceParameters extends Model
{
    use HasFactory;
    protected $table='attendance_parameters';
    public $fillable = ['resort_id','threshold_percentage','auto_notifications','evaluation_reminder'];

    protected static function boot()
    {
        parent::boot();
    }
}
