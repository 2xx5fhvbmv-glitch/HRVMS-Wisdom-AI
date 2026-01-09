<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyResult extends Model
{
    use HasFactory;
    public $table='survey_results';
    protected $fillable = ['Parent_survey_id','Survey_emp_ta_id','Question_id','Emp_Ans'];
}
