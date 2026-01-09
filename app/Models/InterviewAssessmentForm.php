<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InterviewAssessmentForm extends Model
{
    use HasFactory;
    protected $fillable = ['resort_id', 'form_name', 'form_structure','position'];

    public function responses()
    {
        return $this->hasMany(InterviewAssessmentResponseForm::class, 'form_id');
    }
}
