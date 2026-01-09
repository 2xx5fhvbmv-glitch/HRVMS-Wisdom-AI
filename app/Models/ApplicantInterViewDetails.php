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
        'ApplicantInterviewtime','ResortInterviewtime','Approved_By','Status','MeetingLink','EmailTemplateId'
    ];
}



