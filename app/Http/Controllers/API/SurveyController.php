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
            // Get employee linked to this ResortAdmin (same as login: login finds Employee by Emp_id, then ResortAdmin by employee.Admin_Parent_id; we have the token's ResortAdmin, so get Employee by Admin_Parent_id)
            $employee = $this->user->getEmployee ?? $this->user->GetEmployee ?? null;
            if (!$employee || !isset($employee->id)) {
                $employee = Employee::where('Admin_Parent_id', $this->user->id)->first();
            }
            if (!$employee || !isset($employee->id)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Surveys Employee Dashboard',
                    'survey_data' => [
                        'total_count' => 0,
                        'total_pending_count' => 0,
                        'total_complete_count' => 0,
                        'servey_list_data' => [],
                    ],
                ], 200);
            }

            $employee_id = (int) $employee->id;

            // Only surveys for this resort (parent_surveys.resort_id must match logged-in user's resort_id, e.g. 26)
            $surveyCounts = ParentSurvey::join('survey_employees as se', 'se.Parent_survey_id', '=', 'parent_surveys.id')
                ->where('se.Emp_id', $employee_id)
                ->where('parent_surveys.resort_id', $this->resort_id)
                ->whereIn('parent_surveys.Status', ['Publish', 'OnGoing', 'Complete'])
                ->selectRaw("
                    COUNT(*) AS total_count,
                    COUNT(CASE WHEN se.emp_status = 'no' AND parent_surveys.Status IN ('Publish', 'OnGoing') THEN 1 END) AS total_pending_count,
                    COUNT(CASE WHEN se.emp_status = 'yes' AND parent_surveys.Status IN ('Publish', 'OnGoing') THEN 1 END) AS total_complete_count
                ")->first();

            // Get all the surveys with their question count (one row per survey for this employee); match parent_surveys.resort_id to user's resort
            // Creator name/position weren't included at all on this listing
            // endpoint — only the per-survey detail endpoint
            // (employeeSurveyQuestions) had them. Same join pattern as that
            // endpoint (t2=resort_admins, t1=employees, rp=resort_positions
            // via parent_surveys.created_by), left-joined so a survey never
            // disappears from the list if its creator's admin/employee/
            // position record is ever missing.
            $surveyQuestionData = ParentSurvey::join('survey_employees as se', 'se.Parent_survey_id', '=', 'parent_surveys.id')
                ->leftJoin('resort_admins as t2', 't2.id', '=', 'parent_surveys.created_by')
                ->leftJoin('employees as t1', 't1.Admin_Parent_id', '=', 't2.id')
                ->leftJoin('resort_positions as rp', 'rp.id', '=', 't1.Position_id')
                ->where('se.Emp_id', $employee_id)
                ->where('parent_surveys.resort_id', $this->resort_id)
                ->whereIn('parent_surveys.Status', ['Publish', 'OnGoing', 'Complete'])
                ->orderBy('parent_surveys.created_at', 'desc')
                ->select(
                    'parent_surveys.id',
                    'parent_surveys.Surevey_title',
                    'parent_surveys.Start_date',
                    'parent_surveys.End_date',
                    'parent_surveys.Status',
                    'parent_surveys.created_at',
                    'se.id as sur_emp_id',
                    'se.Complete_time',
                    'se.emp_status',
                    't2.id as creator_admin_id',
                    't2.first_name as creator_first_name',
                    't2.last_name as creator_last_name',
                    'rp.position_title as creator_position',
                    \DB::raw('(SELECT COUNT(*) FROM survey_questions WHERE Parent_survey_id = parent_surveys.id) as surveyQuetioncount')
                )
                ->get()
                ->map(function ($row) {
                    // Mobile-friendly: ensure counts are int, add base64 survey_id for questions API
                    $row->surveyQuetioncount = (int) $row->surveyQuetioncount;
                    $row->survey_id = (int) $row->id;
                    $row->survey_id_encoded = base64_encode($row->id);
                    $row->sur_emp_id = (int) ($row->sur_emp_id ?? 0);
                    $row->created_by_name = trim(($row->creator_first_name ?? '') . ' ' . ($row->creator_last_name ?? '')) ?: null;
                    $row->created_by_role = $row->creator_position;
                    $row->created_by_photo = $row->creator_admin_id ? Common::getResortUserPicture($row->creator_admin_id) : null;
                    return $row;
                });

            // When no surveys, first() returns null
            $surveyData = [
                'total_count' => $surveyCounts ? (int) $surveyCounts->total_count : 0,
                'total_pending_count' => $surveyCounts ? (int) $surveyCounts->total_pending_count : 0,
                'total_complete_count' => $surveyCounts ? (int) $surveyCounts->total_complete_count : 0,
                'servey_list_data' => $surveyQuestionData,
                'survey_list_data' => $surveyQuestionData, // alias for mobile (correct spelling)
            ];

            $response = ['success' => true, 'message' => 'Surveys Employee Dashboard', 'survey_data' => $surveyData];
            // Optional debug: add ?debug=1 to request to see which employee/resort the API is using (for troubleshooting "survey not showing")
            if (request()->has('debug') && request()->input('debug') == '1') {
                $response['_debug'] = [
                    'employee_id' => $employee_id,
                    'resort_id' => $this->resort_id,
                    'employee_emp_id' => $employee->Emp_id ?? null,
                ];
            }
            return response()->json($response, 200);

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
            $employee = $this->user->getEmployee ?? $this->user->GetEmployee ?? null;
            if (!$employee || !isset($employee->id)) {
                $employee = Employee::where('Admin_Parent_id', $this->user->id)->first();
            }
            if (!$employee || !isset($employee->id)) {
                return response()->json(['success' => false, 'message' => 'Employee not found'], 200);
            }
            $employee_id = (int) $employee->id;
            $id = (int) base64_decode($surveyId);
            if ($id <= 0) {
                return response()->json(['success' => false, 'message' => 'Invalid survey'], 200);
            }

            // Ensure this employee is a participant in this survey (mobile: only show questions if assigned)
            $participantCheck = SurveyEmployee::where('Parent_survey_id', $id)
                ->where('Emp_id', $employee_id)
                ->exists();
            if (!$participantCheck) {
                return response()->json(['success' => false, 'message' => 'Survey not found or you are not a participant'], 200);
            }

            $parent = ParentSurvey::join("resort_admins as t2", "t2.id", "=", "parent_surveys.created_by")
                ->join('employees as t1', "t1.Admin_Parent_id", "=", "t2.id")
                ->join('resort_positions as rp', "rp.id", "=", "t1.Position_id")
                ->where("parent_surveys.id", $id)
                ->where("parent_surveys.resort_id", $this->resort_id)
                ->first(['parent_surveys.*', 't2.first_name', 't2.last_name', 't2.id as ParentId', 'rp.position_title']);
            if (!$parent) {
                return response()->json(['success' => false, 'message' => 'Survey not found'], 200);
            }

            $parent->EmployeeName                       =   ucfirst($parent->first_name . ' ' .  $parent->last_name);
            $parent->profileImg                         =   Common::getResortUserPicture($parent->ParentId);
            // Privacy hints for the mobile client so it can render the right
            // banner before the participant starts answering.
            $parent->privacy_type                       =   $parent->survey_privacy_type ?? 'Neutral';
            $privacyNotes = [
                'Anonymous'    => 'This is an Anonymous survey. Your responses will not be linked to your identity — even admins cannot see who answered.',
                'Confidential' => 'This is a Confidential survey. Your identity is recorded but only authorised admins (HR / L&D / GM) can see who responded.',
                'Neutral'      => 'This survey is Neutral. Your identity will be visible alongside your answers.',
            ];
            $parent->privacy_note                       =   $privacyNotes[$parent->privacy_type] ?? $privacyNotes['Neutral'];
            $surveyQuestionAnsData = ParentSurvey::join('survey_employees as se', 'se.Parent_survey_id', '=', 'parent_surveys.id')
                ->leftJoin('survey_questions as sq', 'sq.Parent_survey_id', '=', 'parent_surveys.id')
                ->where('se.Emp_id', $employee_id)
                ->where('parent_surveys.resort_id', $this->resort_id)
                ->where('parent_surveys.id', $id)
                ->select(
                    'sq.id as survey_que_id',
                    'sq.Question_Text',
                    'sq.Question_Type',
                    'sq.Total_Option_Json',
                    'sq.Question_Complusory',
                    'se.id as sur_emp_id'
                )
                // Previously nothing here reflected whether THIS employee had
                // actually answered a question — sur_emp_id is just the
                // assignment row id, identical before and after answering,
                // which made "Questions Answered" read as fully answered
                // (e.g. "1 of 1") the moment the survey was opened. A
                // correlated subquery (not a join) avoids row fan-out if a
                // question was ever answered more than once, and also lets
                // the client resume/prefill a previously saved answer.
                ->selectRaw('(SELECT sr.Emp_Ans FROM survey_results sr WHERE sr.Question_id = sq.id AND sr.Survey_emp_ta_id = se.id ORDER BY sr.id DESC LIMIT 1) as previous_answer')
                ->get()
                ->filter(function ($q) {
                    return $q->survey_que_id !== null; // exclude nulls from leftJoin when no questions
                })
                ->map(function ($question) {
                    $question->survey_que_id = (int) $question->survey_que_id;
                    $question->sur_emp_id = (int) $question->sur_emp_id;
                    $question->Total_Option_Json = $question->Total_Option_Json ? json_decode($question->Total_Option_Json) : null;
                    $question->is_answered = $question->previous_answer !== null;
                    return $question;
                })
                ->values();
            $totalQuestionCount = $surveyQuestionAnsData->count();
            $answeredQuestionCount = $surveyQuestionAnsData->where('is_answered', true)->count();
            $surveyData = [
                'parent_data' => $parent,
                'total_question_count' => (int) $totalQuestionCount,
                'answered_question_count' => (int) $answeredQuestionCount,
                'survey_question_data' => $surveyQuestionAnsData,
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
        $employee = $this->user->getEmployee ?? $this->user->GetEmployee ?? null;
        if (!$employee || !isset($employee->id)) {
            $employee = Employee::where('Admin_Parent_id', $this->user->id)->first();
        }
        if (!$employee || !isset($employee->id)) {
            return response()->json(['success' => false, 'message' => 'Employee not found'], 200);
        }
        $employee_id = (int) $employee->id;

        if (
            count($request->parent_survey_id) !== count($request->survey_emp_ta_id) ||
            count($request->parent_survey_id) !== count($request->question_id) ||
            count($request->parent_survey_id) !== count($request->emp_ans)
        ) {
            return response()->json(['success' => false, 'message' => 'Array lengths do not match'], 200);
        }

        DB::beginTransaction();
        try {

            // Was a plain bulk insert — resubmitting the same question (e.g.
            // resuming a survey after leaving mid-way) created a second
            // duplicate row instead of updating the first, and there was no
            // way to prefill a previously saved answer on reopen. Upserting
            // per question fixes both: same question answered twice just
            // updates in place, and employeeSurveyQuestions() can now read
            // it back to resume where the employee left off.
            foreach ($request->parent_survey_id as $index => $parentSurveyId) {
                SurveyResult::updateOrCreate(
                    [
                        'Parent_survey_id' => (int) $parentSurveyId,
                        'Survey_emp_ta_id' => (int) $request->survey_emp_ta_id[$index],
                        'Question_id' => (int) $request->question_id[$index],
                    ],
                    [
                        'Emp_Ans' => $request->emp_ans[$index],
                    ]
                );
            }

            $survEmp = SurveyResult::where('Parent_survey_id', $request->parent_survey_id[0])
                ->where('Survey_emp_ta_id', $request->survey_emp_ta_id[0])
                ->count();

            $existingTime = SurveyEmployee::where('Parent_survey_id', $request->parent_survey_id[0])
                ->where('Emp_id', $employee_id)
                ->value('Complete_time');

            // Parse complete_time (mobile may send H:i:s or HH:MM:SS or similar)
            $timeStr = preg_replace('/\s+/', '', (string) $request->complete_time);
            try {
                $newTimeInSeconds = Carbon::createFromFormat('H:i:s', $timeStr)->secondsSinceMidnight();
            } catch (\Exception $e) {
                try {
                    $newTimeInSeconds = (int) Carbon::parse($timeStr)->secondsSinceMidnight();
                } catch (\Exception $e2) {
                    $newTimeInSeconds = 0;
                }
            }
            $existingTimeInSeconds = 0;
            if ($existingTime) {
                try {
                    $existingTimeInSeconds = Carbon::createFromFormat('H:i:s', $existingTime)->secondsSinceMidnight();
                } catch (\Exception $e) {
                    $existingTimeInSeconds = (int) Carbon::parse($existingTime)->secondsSinceMidnight();
                }
            }

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
                    $request->parent_survey_id[0],
                    false,
                    'survey-completed',
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

    /**
     * Completed surveys previously couldn't be reopened at all — only a
     * duration value was shown, with no way to see the questions, the
     * employee's own submitted answers, or when it was completed. Modeled
     * on the admin-side GetSurveyResults() per-question join pattern
     * (Resorts/Survey/SurveyController.php), scoped to this employee only.
     */
    public function employeeSurveyResponses($surveyId)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $employee = $this->user->getEmployee ?? $this->user->GetEmployee ?? null;
            if (!$employee || !isset($employee->id)) {
                $employee = Employee::where('Admin_Parent_id', $this->user->id)->first();
            }
            if (!$employee || !isset($employee->id)) {
                return response()->json(['success' => false, 'message' => 'Employee not found'], 200);
            }
            $employee_id = (int) $employee->id;
            $id = (int) base64_decode($surveyId);
            if ($id <= 0) {
                return response()->json(['success' => false, 'message' => 'Invalid survey'], 200);
            }

            $surveyEmployee = SurveyEmployee::where('Parent_survey_id', $id)
                ->where('Emp_id', $employee_id)
                ->first();
            if (!$surveyEmployee) {
                return response()->json(['success' => false, 'message' => 'Survey not found or you are not a participant'], 200);
            }
            if ($surveyEmployee->emp_status !== 'yes') {
                return response()->json(['success' => false, 'message' => 'This survey has not been completed yet'], 200);
            }

            $parent = ParentSurvey::where('id', $id)
                ->where('resort_id', $this->resort_id)
                ->first(['Surevey_title', 'Start_date', 'End_date']);
            if (!$parent) {
                return response()->json(['success' => false, 'message' => 'Survey not found'], 200);
            }

            $questions = SurveyQuestion::where('Parent_survey_id', $id)->orderBy('id')->get(['id', 'Question_Text', 'Question_Type']);

            $responses = $questions->map(function ($q) use ($id, $surveyEmployee) {
                $result = SurveyResult::where('Parent_survey_id', $id)
                    ->where('Survey_emp_ta_id', $surveyEmployee->id)
                    ->where('Question_id', $q->id)
                    ->first();
                return [
                    'question_id'   => (int) $q->id,
                    'question_text' => $q->Question_Text,
                    'question_type' => $q->Question_Type,
                    'answer'        => $result->Emp_Ans ?? null,
                ];
            })->values();

            return response()->json([
                'success' => true,
                'message' => 'Survey responses fetched successfully',
                'data'    => [
                    'survey_title'         => $parent->Surevey_title,
                    'start_date'           => $parent->Start_date,
                    'end_date'             => $parent->End_date,
                    'completion_date'      => $surveyEmployee->updated_at ? $surveyEmployee->updated_at->format('Y-m-d') : null,
                    'completion_time'      => $surveyEmployee->updated_at ? $surveyEmployee->updated_at->format('h:i A') : null,
                    'completion_duration'  => $surveyEmployee->Complete_time,
                    'responses'            => $responses,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

}
