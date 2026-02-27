<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicantInterViewDetails extends Model
{
    use HasFactory;

    protected $table = 'applicant_inter_view_details';

    protected $fillable = [
        'resort_id','Applicant_id','ApplicantStatus_id','InterViewDate',
        'ApplicantInterviewtime','ResortInterviewtime','Approved_By','Status','MeetingLink','EmailTemplateId',
        'invitation_token','interviewer_id','rejection_reason'
    ];

    public function interviewer()
    {
        return $this->belongsTo(\App\Models\ResortAdmin::class, 'interviewer_id');
    }

    public function applicant()
    {
        return $this->belongsTo(\App\Models\Applicant_form_data::class, 'Applicant_id');
    }
}



