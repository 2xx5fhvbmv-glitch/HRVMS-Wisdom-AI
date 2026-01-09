<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisaFeeAmount extends Model
{
    use HasFactory;

    protected  $table = 'visa_fee_amounts';
    public  $fillable = ["resort_id" ,"nationality", "AmountbeforExp",'AmountafterExp'];

}
