<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClinicTreatmentAttachments extends Model
{
    use HasFactory;
    public $table='clinic_treatment_attachments';
    public $fillable=['clinic_treatment_id','attachment'];
}
