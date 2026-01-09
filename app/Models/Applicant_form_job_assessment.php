<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;
use App\Models\ResortAdmin;
use Carbon\Carbon;
use App\Helpers\Common;
use App\Models\Applicant_form_data;

class Applicant_form_job_assessment extends Model
{
    use HasFactory;
    protected $table = 'applicant_form_job_assessment';

    protected $fillable = [
        'applicant_form_id',
        'question_id',
        'question_type',
        'response',
        'multiple_responses',
        'video_language_test',
        'video_path',
    ];
    // Accessor for multiple responses
    protected $casts = [
        'multiple_responses' => 'array',
    ];
    public function applicantForm()
    {
        return $this->belongsTo(Applicant_form_data::class);
    }

}
