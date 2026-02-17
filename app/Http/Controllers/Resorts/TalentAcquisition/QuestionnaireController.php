<?php

namespace App\Http\Controllers\Resorts\TalentAcquisition;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use App\Models\ResortDivision;
use App\Models\ResortDepartment;
use App\Models\ResortSection;
use App\Models\ResortPosition;
use App\Helpers\Common;
use App\Models\Questionnaire;
use App\Models\QuestionnaireChild;
use App\Models\VideoQuestion;
use Validator;
use DB;
use App\Models\ResortLanguages;
class QuestionnaireController extends Controller
{
    public $resort;

    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;


    }
    public function index()
    {
        try {
            if(Common::checkRouteWisePermission('resort.ta.Questionnaire',config('settings.resort_permissions.view')) == false){
                return abort(403, 'Unauthorized access');
            }
            $page_title = 'Questionnaire';

            $resort_divisions = ResortDivision::where('status', 'active')->where('resort_id',$this->resort->resort_id)->get();
            $resort_departments = ResortDepartment::where('status', 'active')->where('resort_id',$this->resort->resort_id)->get();
            $resort_sections = ResortSection::where('status', 'active')->where('resort_id',$this->resort->resort_id)->get();
            $resort_positions = ResortPosition::where('status', 'active')->where('resort_id',$this->resort->resort_id)->get();


                return view('resorts.talentacquisition.questionnaire.index',compact('page_title','resort_divisions','resort_departments','resort_sections','resort_positions'));

          } catch( \Exception $e ) {
            \Log::emergency("File: ".$e->getFile());
            \Log::emergency("Line: ".$e->getLine());
            \Log::emergency("Message: ".$e->getMessage());
          }
    }
    public function getResortWiseQuestion()
    {
        // try {

            $QuestionnersData = Questionnaire::select([
                't1.id as child_id',
                't2.name as Division',
                't3.name as Department',
                't4.position_title as Position',
                't1.Question',
                'questionnaires.id as ParentId',
                't1.questionType',
            ])->join('questionnaire_children as t1', 't1.Q_Parent_id', '=', 'questionnaires.id')
                ->join('resort_divisions as t2', 't2.id', '=', 'questionnaires.Division_id')
                ->join('resort_departments as t3', 't3.id', '=', 'questionnaires.Department_id')
                ->join('resort_positions as t4', 't4.id', '=', 'questionnaires.Position_id')
                ->where('questionnaires.Resort_id', $this->resort->resort_id)
                ->groupBy('questionnaires.id')
                ->orderBy('t1.id', 'DESC')
                ->get();

            $edit_class = '';
            $delete_class = '';
            if(Common::checkRouteWisePermission('resort.ta.Questionnaire',config('settings.resort_permissions.edit')) == false){
                $edit_class = 'd-none';
            }
            if(Common::checkRouteWisePermission('resort.ta.Questionnaire',config('settings.resort_permissions.delete')) == false){
                $delete_class = 'd-none';
            }

            return datatables()->of($QuestionnersData)
                ->addColumn('action', function ($row) use ($edit_class, $delete_class) {
                    $editUrl = asset('resorts_assets/images/edit.svg');
                    $deleteUrl = asset('resorts_assets/images/trash-red.svg');
                    $editroute= route('resort.ta.Questions.edit',$row->ParentId);

                    return '
                        <div class="d-flex align-items-center">
                            <a href="'.$editroute.'" class="btn-lg-icon icon-bg-green me-1 edit-row-btn '.$edit_class.'"
                            data-dept-id="' . htmlspecialchars($row->child_id, ENT_QUOTES, 'UTF-8') . '">
                                <img src="' . $editUrl . '" alt="Edit" class="img-fluid" />
                            </a>
                            <a href="#" class="btn-lg-icon icon-bg-red delete-row-btn '.$delete_class.'"
                           data-Parent-id="'. htmlspecialchars($row->ParentId, ENT_QUOTES, 'UTF-8') . '" data-dept-id="' . htmlspecialchars($row->child_id, ENT_QUOTES, 'UTF-8') . '">
                                <img src="' . $deleteUrl . '" alt="Delete" class="img-fluid" />
                            </a>
                        </div>';
                })

                // ->addColumn('questionType', function ($row) {
                //     if($row->questionType == "multiple") {
                //         return '<span class="badge bg-info">' . htmlspecialchars($row->questionType, ENT_QUOTES, 'UTF-8') . '</span>';
                //     } else{
                //         return '<span class="badge bg-success">' . htmlspecialchars($row->questionType, ENT_QUOTES, 'UTF-8') . '</span>';
                //     }
                //     //
                // })

                ->rawColumns(['Division', 'Department', 'Position', 'Question','action'])
                ->make(true);

        //    } catch (\Exception $e) {
        //     \Log::emergency("File: " . $e->getFile());
        //     \Log::emergency("Line: " . $e->getLine());
        //     \Log::emergency("Message: " . $e->getMessage());
        //     return response()->json(['error' => 'Failed to fetch data'], 500);
        // }

    }
    public function create()
    {
        if(Common::checkRouteWisePermission('resort.ta.Questionnaire',config('settings.resort_permissions.create')) == false){
            return abort(403, 'Unauthorized access');
        }

        $page_title = 'Questionnaire';
        $ResortDivision = ResortDivision::where("resort_id",$this->resort->resort_id)->get(["name","id"]);
        $ResortLanguages= ResortLanguages::orderBy("name","asc")->get();
        return view('resorts.talentacquisition.questionnaire.create',compact('ResortLanguages','ResortDivision','page_title'));
    }

    public function store(Request $request)
    {
        $Division = $request->ResortDivision;
        $Department = $request->Department;
        $Position = $request->Position;
        $AddQuestion = $request->AddQuestion;
        $que_type = $request->que_type;
        $CheckBoxOption = $request->CheckBoxOption;
        $RadioOption = $request->RadioOption;
        $VideoQuestion = $request->VideoQuestion;
        $Language = $request->language;
        $ans = $request->ans;
        $video_Avilable_question = (isset($request->video) && $request->video == 'on')  ? "Yes" : "No";


        $validator = Validator::make($request->all(), [
            'ResortDivision' => 'required',
            'Department' => 'required',
            'Position' => 'required',
            // 'AddQuestion.*' => 'required|array', // Each element of AddQuestion should also be an array
            // 'AddQuestion.*.*' => 'required|string|max:255', // Validate each question within the nested arrays
        ]);
        DB::beginTransaction();

        try {

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

                $p = Questionnaire::create([
                    "Resort_id"=> $this->resort->resort_id,
                    "Division_id"=> $Division,
                    "Department_id"=> $Department,
                    "Position_id"=> $Position,
                    "video"=>$video_Avilable_question

                ]);
            if(!empty($AddQuestion))
            {
                foreach($AddQuestion as $q=> $question)
                {

                    if(!empty($CheckBoxOption) && array_key_exists($q,$CheckBoxOption))
                    {
                        $type="multiple";
                        $ots = json_encode($CheckBoxOption[$q]);
                    }
                    elseif(!empty($RadioOption) && array_key_exists($q,$RadioOption))
                    {
                        $type="Radio";

                    }
                    else
                    {
                        $type="single";
                        $ots =null;
                    }

                    foreach($question as $ak=>$q1)
                    {
                        QuestionnaireChild ::create([
                            "Q_Parent_id"=>$p->id,
                            "Question"=>$q1,
                            "questionType"=>$type,
                            "options"=>$ots,
                            "ans"=> (!empty($ans) && array_key_exists($ak,$ans)) ?  $ans[$ak] : null,
                        ]);
                    }
                }
            }


            //  Video Question

            if($video_Avilable_question =="Yes" && !empty($Language))
            {

                foreach($Language as $l => $l1)
                {
                    VideoQuestion::create([
                        "Q_Parent_id"=>$p->id,
                        "lang_id"=>$l1,
                        "VideoQuestion"=>$VideoQuestion[$l],
                    ]);
                }

            }




            DB::commit();
            return response()->json(['success' => true, 'msg' => 'Questionnaire added successfully.']);


        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: ".$e->getFile());
            \Log::emergency("Line: ".$e->getLine());
            \Log::emergency("Message: ".$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to add Questionnaire.'], 500);
        }

    }

    public function edit($id)
    {
        if(Common::checkRouteWisePermission('resort.ta.Questionnaire',config('settings.resort_permissions.edit')) == false){
            return abort(403, 'Unauthorized access');
        }
        $page_title = 'Questionnaire';
        $ResortDivision = ResortDivision::where('status', 'active')->where('resort_id',$this->resort->resort_id)->get();
        $ResortDpartments = ResortDepartment::where('status', 'active')->where('resort_id',$this->resort->resort_id)->get();
        $ResortSections = ResortSection::where('status', 'active')->where('resort_id',$this->resort->resort_id)->get();
        $ResortPositions = ResortPosition::where('status', 'active')->where('resort_id',$this->resort->resort_id)->get();

        $Questionnaire = Questionnaire::where('resort_id',$this->resort->resort_id)->where("id",$id)->first();
        $ResortLanguages= ResortLanguages::orderBy("name","asc")->get();

        return view('resorts.talentacquisition.questionnaire.edit',compact('Questionnaire','ResortDivision','ResortDpartments','ResortSections','ResortLanguages','ResortPositions','page_title'));

    }

    public function update(Request $request)
    {

        $editQuestionnaire_id = $request->editQuestionnaire_id;

        $Division = $request->ResortDivision;
        $Department = $request->Department;
        $Position = $request->Position;
        $AddQuestion = $request->AddQuestion;
        $que_type = $request->que_type;
        $CheckBoxOption = $request->CheckBoxOption;
        $RadioOption = $request->RadioOption;
        $VideoQuestion = $request->VideoQuestion;
        $Language = $request->language;
        $ans = $request->ans;
        $video_Avilable_question = (isset($request->video) && $request->video == 'on')  ? "Yes" : "No";

        $validator = Validator::make($request->all(), [
            'ResortDivision' => 'required',
            'Department' => 'required',
            'Position' => 'required',
            // 'AddQuestion.*' => 'required|array', // Each element of AddQuestion should also be an array
            // 'AddQuestion.*.*' => 'required|string|max:255', // Validate each question within the nested arrays
        ]);


        DB::beginTransaction();

        try {

            if ($validator->fails())
            {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $p = Questionnaire::find($editQuestionnaire_id);

            if ($p) {
                $p->update([
                    "Resort_id" => $this->resort->resort_id,
                    "Division_id" => $Division,
                    "Department_id" => $Department,
                    "Position_id" => $Position,
                    "video" => $video_Avilable_question,
                ]);
            }

            if(!empty($AddQuestion))
            {

                        QuestionnaireChild::where('Q_Parent_id', $editQuestionnaire_id)->delete();

                foreach($AddQuestion as $q=> $question)
                {

                    if(!empty($CheckBoxOption) && array_key_exists($q,$CheckBoxOption))
                    {
                        $type="multiple";
                        $ots = json_encode($CheckBoxOption[$q]);
                    }
                    elseif(!empty($RadioOption) && array_key_exists($q,$RadioOption))
                    {
                        $type="Radio";

                    }
                    else
                    {
                        $type="single";
                        $ots =null;
                    }

                    foreach($question as $ak=>$q1)
                    {
                        QuestionnaireChild ::create([
                            "Q_Parent_id"=>$p->id,
                            "Question"=>$q1,
                            "questionType"=>$type,
                            "options"=>$ots,
                            "ans"=> (!empty($ans) && array_key_exists($ak,$ans)) ?  $ans[$ak] : null,
                        ]);
                    }
                }
            }


            //  Video Question

            if($video_Avilable_question =="Yes" && !empty($Language))
            {

                VideoQuestion::where('Q_Parent_id', $editQuestionnaire_id)->delete();

                foreach($Language as $l => $l1)
                {
                    VideoQuestion::create([
                        "Q_Parent_id"=>$p->id,
                        "lang_id"=>$l1,
                        "VideoQuestion"=>$VideoQuestion[$l],
                    ]);
                }

            }




            DB::commit();
            return response()->json(['success' => true, 'msg' => 'Questionnaire added successfully.']);
        } catch (\Exception $e) {
            // Log the error and return failure response
            \Log::error("Error deleting division: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete division.']);
        }

    }

    public function destroy(Request $request)
    {

        try {
            $QuestionnaireChild = QuestionnaireChild::where('id', $request->childId)->first();

            $QuestionnaireChild = QuestionnaireChild::where('Q_Parent_id', $request->ParentId)->first();
            if($QuestionnaireChild->count() == 1)
            {

                Questionnaire::where('id', $request->ParentId)->delete();
            }
            $QuestionnaireChild->delete();


            return response()->json(['success' => true, 'message' => 'Division deleted successfully.']);
        } catch (\Exception $e) {
            // Log the error and return failure response
            \Log::error("Error deleting division: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete division.']);
        }
    }

}

