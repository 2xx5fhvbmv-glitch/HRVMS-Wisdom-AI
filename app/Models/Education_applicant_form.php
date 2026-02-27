<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;
use App\Models\ResortAdmin;
use Carbon\Carbon;
use App\Helpers\Common;

class Education_applicant_form extends Model
{
    use HasFactory;
    protected $table = 'education_applicant_form';

    protected $fillable = [
        'applicant_form_id','institute_name','educational_level',
        'country_educational','city_educational','pass_out_year'
    ];

}
