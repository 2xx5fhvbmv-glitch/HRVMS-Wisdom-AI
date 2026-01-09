<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClinicAppointmentAttachment extends Model
{
    use HasFactory;
    
        public $table='clinic_appointment_attechements';
        public $fillable=['resort_id','appointment_id','attachment'];
}
