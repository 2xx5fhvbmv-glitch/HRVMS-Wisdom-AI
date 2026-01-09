<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use App\Models\Employee;
use App\Models\ParentSurvey;
use App\Models\SurveyEmployee;
use App\Models\SurveyQuestion;
use App\Models\SurveyResult;
use App\Helpers\Common;
use Carbon\Carbon;
use Validator;
use File;
use Auth;
use URL;
use DB;

class SurveyController extends Controller
{
    protected $user;
    protected $resort_id;
    protected $underEmp_id = [];

    public function __construct()
    {

        if (Auth::guard('api')->check()) {
            $this->user = Auth::guard('api')->user();
            $this->resort_id = $this->user->resort_id;
        }
    }

    public function employeeSurveyDashboard()
    {
    
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $employee_id                                =   $this->user->GetEmployee->id;

            // Optimized query using conditional aggregation
            $surveyCounts                               =   ParentSurvey::join('survey_employees as se', 'se.Parent_survey_id', '=', 'parent_surveys.id')
                                                                ->where('se.Emp_id', $employee_id)
                                                                ->where('resort_id', $this->resort_id)
                                                                ->whereIn('parent_surveys.Status', ['OnGoing', 'Complete'])
                                                                ->selectRaw("
                                                                    COUNT(*) AS total_count,
                                                                    COUNT(CASE WHEN se.emp_status = 'no' AND parent_surveys.Status = 'OnGoing' THEN 1 END) AS total_pending_count,
                                                                    COUNT(CASE WHEN se.emp_status = 'yes' AND parent_surveys.Status = 'OnGoing' THEN 1 END) AS total_complete_count
                                                                ")->first();
                                                                
            // Get all the surveys with their question count in a single query
            $surveyQuestionData                         =   ParentSurvey::join('survey_employees as se', 'se.Parent_survey_id', '=', 'parent_surveys.id')
                                                                ->leftJoin('survey_questions as sq', 'sq.Parent_survey_id', '=', 'parent_surveys.id')
                                                                ->where('se.Emp_id', $employee_id)
                                                                ->where('parent_surveys.resort_id', $this->resort_id)
                                                                ->whereIn('parent_surveys.Status', ['OnGoing', 'Complete'])
                                                                ->orderBy('parent_surveys.created_at', 'desc')
                                                                ->select(
                                                                    'parent_surveys.id',
                                                                    'parent_surveys.Surevey_title',
                                                                    'parent_surveys.Start_date',
                                                                    'parent_surveys.End_date',
                                                                    'parent_surveys.Status',
                                                                    'se.Complete_time',
                                                                    'se.emp_status',
                                                                    \DB::raw('COUNT(sq.id) as surveyQuetioncount')
                                                                )
                                                                ->groupBy('parent_surveys.id')
                                                                ->get();

             // Prepare the survey data
            $surveyData = [
                'total_count'                           =>  $surveyCounts->total_count,
                'total_pending_count'                   =>  $surveyCounts->total_pending_count,
                'total_complete_count'                  =>  $surveyCounts->total_complete_count,
                'servey_list_data'                           =>  $surveyQuestionData
            ];

            return response()->json(['success' => true, 'message' => 'Surveys Employee Dashboard', 'survey_data' => $surveyData], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function employeeSurveyQuestions($surveyId)
    {
    
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
           
            $employee_id                                =   $this->user->GetEmployee->id;
            $id                                         =   base64_decode($surveyId);

            $parent                                     =   ParentSurvey::join("resort_admins as t2","t2.id","=","parent_surveys.created_by")
                                                                ->join('employees as t1',"t1.Admin_Parent_id","=","t2.id")
                                                                ->join('resort_positions as rp', "rp.id", "=", "t1.Position_id")
                                                                ->where("parent_surveys.id",$id)
                                                                ->where("parent_surveys.resort_id",$this->resort_id)
                                                                ->first(['parent_surveys.*','t2.first_name','t2.last_name','t2.id as ParentId','rp.position_title'] );
            if (!$parent) {
                    return response()->json(['success' => false, 'message' => 'Survey not found'], 200);
            }

            $parent->EmployeeName                       =   ucfirst($parent->first_name . ' ' .  $parent->last_name);
            $parent->profileImg                         =   Common::getResortUserPicture($parent->Parentid);              
            $surveyQuestionAnsData                      =   ParentSurvey::join('survey_employees as se', 'se.Parent_survey_id', '=', 'parent_surveys.id')
                                                                ->leftJoin('survey_questions as sq', 'sq.Parent_survey_id', '=', 'parent_surveys.id')
                                                                ->where('se.Emp_id', $employee_id)
                                                                ->where('parent_surveys.resort_id', $this->resort_id)
                                                                ->where('parent_surveys.id',$id) 
                                                                ->select(
                                                                    'sq.id as survey_que_id',
                                                                    'sq.Question_Text',
                                                                    'sq.Question_Type',
                                                                    'sq.Total_Option_Json',
                                                                    'sq.Question_Complusory',
                                                                    'se.id as sur_emp_id'
                                                                )->get()
                                                                ->map(function ($question) {
                                                                    // Decode the Total_Option_Json to an array
                                                                    $question->Total_Option_Json = json_decode($question->Total_Option_Json);
                                                                    return $question;
                                                                });
            $totalQuestionCount                         =   $surveyQuestionAnsData->count();                                                
            $surveyData = [
                'parent_data'                           =>  $parent,
                'total_question_count'                  =>  $totalQuestionCount,
                'survey_question_data'                  =>  $surveyQuestionAnsData
            ];
            return response()->json(['success' => true, 'message' => 'Surveys Employee Dashboard', 'survey_data' => $surveyData], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function employeeQuestionsAnsStore(Request $request)
    {
    
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'parent_survey_id'                      => 'required|array',
            'parent_survey_id.*'                    => 'required|integer',
            'survey_emp_ta_id'                      => 'required|array',
            'survey_emp_ta_id.*'                    => 'required|integer',
            'question_id'                           => 'required|array',
            'question_id.*'                         => 'required|integer',
            'emp_ans'                               => 'required|array',
            'emp_ans.*'                             => 'required|string|max:255',
            'complete_time'                         => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }
        $employee_id                                =   $this->user->GetEmployee->id;

        if (
            count($request->parent_survey_id) !== count($request->survey_emp_ta_id) ||
            count($request->parent_survey_id) !== count($request->question_id) ||
            count($request->parent_survey_id) !== count($request->emp_ans)
        ) {
            return response()->json(['success' => false, 'message' => 'Array lengths do not match'], 200);
        }

        DB::beginTransaction();
        try {

            $surveyResults                              = [];
            foreach ($request->parent_survey_id as $index => $parentSurveyId) {
                $surveyResults[]                        = [
                    'Parent_survey_id'                  => $parentSurveyId,
                    'Survey_emp_ta_id'                  => $request->survey_emp_ta_id[$index],
                    'Question_id'                       => $request->question_id[$index],
                    'Emp_Ans'                           => $request->emp_ans[$index],
                    'created_at'                        => now(),
                    'updated_at'                        => now(),
                ];
            }
            SurveyResult::insert($surveyResults);

            // Check the response count
            $survEmp                                    =   SurveyResult::where('Parent_survey_id', $request->parent_survey_id[0])
                                                                ->where('Survey_emp_ta_id', $request->survey_emp_ta_id[0])
                                                                ->count();

            $existingTime                               =   SurveyEmployee::where('Parent_survey_id', $request->parent_survey_id[0])
                                                                ->where('Emp_id', $employee_id)
                                                                ->value('Complete_time');

            // Convert times to seconds
            $newTimeInSeconds                           =   Carbon::createFromFormat('H:i:s', $request->complete_time)->secondsSinceMidnight();
            $existingTimeInSeconds                      =   $existingTime ? Carbon::createFromFormat('H:i:s', $existingTime)->secondsSinceMidnight() : 0;

            // Add times together
            $totalTimeInSeconds                         =   $newTimeInSeconds + $existingTimeInSeconds;
            $finalTime                                  =   gmdate("H:i:s", $totalTimeInSeconds); // Convert back to HH:MM:SS

            // Update the survey result with the new complete_time
            $updateCompleteTime                         =   SurveyEmployee::where('Parent_survey_id', $request->parent_survey_id[0])
                                                                ->where('Emp_id', $employee_id)
                                                                ->update(['Complete_time' => $finalTime]);

            $surveyQuestionAnsData                      =   ParentSurvey::find($request->parent_survey_id[0]);

            if ($surveyQuestionAnsData && $surveyQuestionAnsData->Min_response <= $survEmp) {

                $updateEmpStatus                        =   SurveyEmployee::where('id', $request->survey_emp_ta_id[0])
                                                                ->where('Parent_survey_id', $request->parent_survey_id[0])
                                                                ->where('Emp_id', $employee_id)
                                                                ->update(['emp_status' => "yes"]);
            }

            $hrEmployee = Common::FindResortHR($this->user);
            if ($hrEmployee) {
                Common::sendMobileNotification(
                    $this->resort_id,
                    2,
                    null,
                    null,
                    'Survey Completed',
                    '📝 A survey has been completed by ' . $this->user->first_name . ' ' . $this->user->last_name . '.',
                    'Survey',
                    [$hrEmployee->id],
                    null,
                );
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Survey answers saved successfully']);
          
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

}
