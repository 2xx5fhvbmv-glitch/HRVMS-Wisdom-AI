<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkPermitMedicalRenewalChild extends Model
{
    use HasFactory;
    public $table = 'work_permit_medical_renewal_children';
    public $fillable = ['permit_medical_id','Reference_Number','Cost','Amt','Medical_Center_name','start_date','end_date','medical_file'];

}
