<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;
use App\Models\ResortAdmin;
use App\Models\ApplicantInterViewDetails;
use Carbon\Carbon;
use App\Helpers\Common;

class Applicant_form_data extends Model
{
    use HasFactory;
    protected $table = 'applicant_form_data';

    protected $fillable = [
        'Parent_v_id','resort_id','passport_no','passport_expiry_date','passport_img','Application_date','curriculum_vitae','passport_photo','full_length_photo',
        'first_name','last_name','gender','dob','mobile_number','email','marital_status','number_of_children','address_line_one','address_line_two','country','state','city','pin_code','Joining_availability','reference','select_level','terms_conditions','data_retention_month','data_retention_year','notes'
        ,'NotiesPeriod','SalaryExpectation','TimeZone','Scoring','AIRanking','Applicant_Source'
    ];
    public function GetVacancies()
    {
        return $this->belongsTo(Vacancies::class, 'Parent_v_id', 'id');
    }

    public function Application_wise_status()
    {
        return $this->hasMany(ApplicantWiseStatus::class, 'Applicant_id', 'id');
    }

    public function ApplicantInterviewDetail(){
        return $this->hasMany(ApplicantInterViewDetails::class, 'Applicant_id', 'id');
    }

}
