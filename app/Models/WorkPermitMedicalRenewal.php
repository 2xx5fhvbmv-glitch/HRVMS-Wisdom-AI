<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkPermitMedicalRenewal extends Model
{
    use HasFactory;

    public $table = 'work_permit_medical_renewals';
    public $fillable = ['resort_id','employee_id','Reference_Number','Amt','Cost','Currency','Medical_Center_name','start_date','end_date','medical_file'];

    public function WorkPermitMedicalRenewalChild()
    {
        return $this->hasMany(WorkPermitMedicalRenewalChild::class, 'permit_medical_id', 'id');
    }
}

