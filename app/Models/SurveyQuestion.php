<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyQuestion extends Model
{
    use HasFactory;
    
    public $table='survey_questions';
    protected $fillable = [
        'Parent_survey_id','Question_Type','Total_Option_Json','type','Question_Text','Question_Complusory'
    ];
}
