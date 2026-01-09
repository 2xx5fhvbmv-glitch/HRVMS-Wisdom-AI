<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class SurveyEmployee extends Model
{
    use HasFactory;
    
    public $table='survey_employees';
    protected $fillable = [
        'Parent_survey_id','Emp_id','emp_status','Complete_time'
    ];
}
