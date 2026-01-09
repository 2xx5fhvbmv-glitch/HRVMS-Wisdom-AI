<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

use App\Models\SurveyQuestion;
use App\Models\SurveyEmployee;
use App\Models\ParentSurvey;
use App\Helpers\Common;

class SurveyDownloadQuestionAndAns implements FromView
{
    protected $survey_id;
    protected $resort;

    public function __construct($survey_id)
    {    $this->resort = $resortId = auth()->guard('resort-admin')->user();

        $this->survey_id = base64_decode($survey_id);
    }

    public function view(): View
    {
 

        $parent = ParentSurvey::join("resort_admins as t2","t2.id","=","parent_surveys.created_by")
                                ->join('employees as t1',"t1.Admin_Parent_id","=","t2.id")
                             
                                ->where("parent_surveys.id",$this->survey_id)
                                ->where("parent_surveys.resort_id",$this->resort->resort_id)
                                ->first(['parent_surveys.*','t2.first_name','t2.last_name','t2.id as ParentId'] );
                                 $parent->EmployeeName = ucfirst($parent->first_name . ' ' .  $parent->last_name);
                                 $parent->profileImg = Common::getResortUserPicture($parent->Parentid);

    
        $Question  =     SurveyQuestion::where("Parent_survey_id",$this->survey_id)->get();                             

        $participantEmp =  SurveyEmployee::join('employees as t1',"t1.id","=","survey_employees.Emp_id")
                                ->join("resort_admins as t2","t2.id","=","t1.Admin_Parent_id")
                                ->where("survey_employees.Parent_survey_id",$this->survey_id)    
                                ->get(['t2.first_name','t2.last_name','t2.id as ParentId'] )
                                ->map(function($i){
                                    $i->EmployeeName = ucfirst($i->first_name . ' ' .  $i->last_name);
                                    $i->profileImg = Common::getResortUserPicture($i->Parentid);
                                    return $i;
                                });
        return view('resorts.Survey.SurveyPages.exportsurveydetails', [
            'parent' => $parent,
            'Question' => $Question,
            'participantEmp' => $participantEmp,
        ]);
    }
}
