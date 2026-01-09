<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisaRenewal extends Model
{
    use HasFactory;
       public $table = 'visa_renewals';
    public $fillable = ['resort_id','employee_id','Visa_Number','WP_No','start_date','end_date','visa_file'];


    public function VisaChild()
    {
        return $this->hasMany(VisaRenewalChild::class, 'visa_renewal_id', 'id');
    }
}
