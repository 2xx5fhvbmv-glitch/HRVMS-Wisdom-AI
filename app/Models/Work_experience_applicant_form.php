<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


use Illuminate\Support\Facades\Auth;
use App\Models\ResortAdmin;
use Carbon\Carbon;
use App\Helpers\Common;

class Work_experience_applicant_form extends Model
{
    use HasFactory;
    protected $table = 'work_experience_applicant_form';

    protected $fillable = [
        'applicant_form_id','job_title','employer_name','total_work_exp',
        'work_country_name','work_city','work_start_date','work_end_date','job_description_work','currently_working'
    ];

}
