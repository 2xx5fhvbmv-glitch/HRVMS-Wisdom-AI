<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InterviewAssessmentResponseForm extends Model
{
    use HasFactory;
    protected $table="interview_assessment_responses";
    public  $fillable = ['form_id','interviewer_id','interviewee_id', 'interviewer_signature','responses'];

    public function form()
    {
        return $this->belongsTo(InterviewAssessmentForm::class, 'form_id');
    }

    public function interviewer()
    {
        return $this->belongsTo(ResortAdmin::class, 'interviewer_id');
    }

    public function interviewee()
    {
        return $this->belongsTo(Applicant_form_data::class, 'interviewee_id');
    }

   
}
