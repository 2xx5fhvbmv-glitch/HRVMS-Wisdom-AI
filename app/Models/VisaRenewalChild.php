<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisaRenewalChild extends Model
{
    use HasFactory;
    public $table = 'visa_renewal_children';
    public $fillable = ['visa_renewal_id','Visa_Number','WP_No','start_date','Amt','end_date','visa_file'];

}
