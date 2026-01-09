<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NintyDayPeformanceForm extends Model
{
    use HasFactory;


    protected $table = 'ninty_day_peformance_forms';
    protected $fillable = [
        'resort_id','FormName','form_structure'
    ];
}
