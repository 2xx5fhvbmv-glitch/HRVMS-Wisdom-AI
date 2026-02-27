<?php
namespace App\Http\Controllers\resorts\talentacquisition;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\InterviewAssessmentForm;
use App\Models\InterviewAssessmentResponseForm;
use App\Models\Resort;
use App\Models\ResortPosition;
use App\Models\ResortAdmin;
use Validator;
use DB;
use App\Helpers\Common;
use Carbon\Carbon;
use URL;

class InterviewAssessmentController extends Controller
{
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;
        $this->rank=  $this->resort->GetEmployee->rank ?? '';
    }
    public function index(){
        if(Common::checkRouteWisePermission('interview-assessment.index',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }
        $page_title="Interview Assessment";
        $positionsQuery = ResortPosition::where('status','active')->where('resort_id',$this->resort->resort_id);
        if($this->rank == 2) {
            $positionsQuery->where('dept_id', $this->resort->GetEmployee->Dept_id);
        }
        $positions = $positionsQuery->get();

        return view('resorts.talentacquisition.interview-assessment.index',compact('page_title','positions'));
    }
    public function list(Request $request)
    {
        $position = $request->get('position');
        $searchTerm = $request->get('searchTerm');

        $forms = InterviewAssessmentForm::select([
            'interview_assessment_forms.id',
            'interview_assessment_forms.form_name',
            't4.position_title as Position',
            'interview_assessment_forms.resort_id',
        ])
        ->join('resort_positions as t4', 't4.id', '=', 'interview_assessment_forms.position')
        ->where('interview_assessment_forms.resort_id', $this->resort->resort_id);

        if($this->rank == 2) {
            $forms->where('t4.dept_id', $this->resort->GetEmployee->Dept_id);
        }

        $forms->orderBy('interview_assessment_forms.id', 'DESC');

        if ($searchTerm) {
            $forms->where(function ($query) use ($searchTerm) {
                $query->where('interview_assessment_forms.form_name', 'like', "%$searchTerm%")
                    ->orWhere('t4.position_title', 'like', "%$searchTerm%");
            });
        }
        if($position){
            $forms->where('interview_assessment_forms.position',$position);
        }

        $forms ->get();

        $edit_class = '';
        $delete_class = '';
                if(Common::checkRouteWisePermission('interview-assessment.index',config('settings.resort_permissions.edit')) == false){
                    $edit_class = 'd-none';
                }
                if(Common::checkRouteWisePermission('interview-assessment.index',config('settings.resort_permissions.delete')) == false){
                    $delete_class = 'd-none';
                }
        // Do NOT call get() here; pass the query builder to DataTables.
        return datatables()->of($forms)
            ->addColumn('action', function ($row) use ($edit_class, $delete_class) {

                $edit_url = route('interview-assessment.edit', $row->id);
                // $delete_url = route('interview-assessment.delete', $row->id);
                $editimg = asset('resorts_assets/images/edit.svg');
                $deleteimg = asset('resorts_assets/images/trash-red.svg');
                return "<a href='$edit_url' class='edit-row-btn $edit_class'><img src='$editimg' alt='Edit'></a>
                        <a href='#' class='delete-row-btn $delete_class' data-id='$row->id'><img src='$deleteimg' alt='Delete'></a>";
            })
            ->addColumn('Position', function ($row) {
                return $row->Position;
            })
            ->addColumn('form_name', function ($row) {
                return $row->form_name;
            })
            ->rawColumns(['Position', 'form_name', 'action'])
            ->make(true);
    }

    public function create(Request $request)
    {
        if(Common::checkRouteWisePermission('interview-assessment.index',config('settings.resort_permissions.create')) == false){
            return abort(403, 'Unauthorized access');
        }
        $page_title = "Create Interview Assessment";
        $resort_id = $this->resort->resort_id;
        $positions = ResortPosition::where('status','active')->where('resort_id',$this->resort->resort_id)->get();
        return view('resorts.talentacquisition.interview-assessment.create',compact('resort_id','positions','page_title'));
    }
    public function store(Request $request)
    {
        $resortId = $this->resort->resort_id;


        $validator =  Validator::make($request->all(), [
            'form_name' => 'required',
            'position' => 'required',
        ], [
            'form_name.required' => 'Please Enter Form Name.',
            'position.required' => 'Please select Position',
        ]);
        if($validator->fails())
        {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $form = InterviewAssessmentForm::create([
            'resort_id' => $resortId,
            'form_name' => $request->input('form_name'),
            'position' => $request->input('position'),
            'form_structure' => json_encode($request->input('form_structure')) // Save form as JSON
        ]);
        // dd($form);

        return response()->json(['success' => true, 'form' => $form]);
    }

    public function edit($id)
    {

        if(Common::checkRouteWisePermission('interview-assessment.index',config('settings.resort_permissions.edit')) == false){
            return abort(403, 'Unauthorized access');
        }
        $page_title = "Edit Interview Assessment";
        $resortId = $this->resort->resort_id;
        $form = InterviewAssessmentForm::findOrFail($id);
        $form->form_structure = json_decode($form->form_structure, true);


        $positions = ResortPosition::where('status','active')->where('resort_id',$this->resort->resort_id)->get();
        return view('resorts.talentacquisition.interview-assessment.edit',compact('resortId','positions','form','page_title'));
    }

    public function update(Request $request, $id)
    {
        $form = InterviewAssessmentForm::findOrFail($id);

      
        $validator =  Validator::make($request->all(), [
            'form_name' => 'required',
            'position' => 'required',
        ], [
            'form_name.required' => 'Please Enter Form Name.',
            'position.required' => 'Please select Position',
        ]);
        if($validator->fails())
        {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $form->update([
            'form_name' => $validatedData['form_name'],
            'position' => $validatedData['position'],
            'form_structure' => $validatedData['form_structure'], // Save updated structure
        ]);

        return redirect()->route('interview-assessment.index')->with('success', 'Form updated successfully.');
    }

    public function delete($id)
    {
        $form = InterviewAssessmentForm::findOrFail($id);
        $form->delete();

        return response()->json(['success' => 'Form deleted successfully.']);
    }

    public function show($position_id,$applicant_id)
    {
        $page_title = "View Interview Assessment";
        $position_id = base64_decode($position_id);
        $applicant_id = base64_decode($applicant_id);

        $form = InterviewAssessmentForm::where('position',$position_id)->get();
        $interviewer_id = $this->resort->id;
        $interviewee_id = $applicant_id;

        // Get current user's rank name to filter form sections
        $rankConfig = config('settings.Position_Rank');
        $userRank = $this->rank;
        $userRankName = $rankConfig[$userRank] ?? '';

        // If user is in HR department, map their section to "HR" regardless of actual rank
        $employee = $this->resort->GetEmployee ?? null;
        if ($employee) {
            $userDeptName = \App\Models\ResortDepartment::where('id', $employee->Dept_id)->value('name') ?? '';
            if (stripos($userDeptName, 'Human Resources') !== false) {
                $userRankName = 'HR';
            }
        }

        // Filter form structure to only show sections for this user's rank
        $filteredStructure = [];
        $otherSectionsData = [];

        if ($form->isNotEmpty()) {
            $fullStructure = json_decode(json_decode($form[0]->form_structure), true);

            // Parse sections: h1 headers act as section dividers with rank names
            $sections = [];
            $currentSection = null;
            foreach ($fullStructure as $field) {
                if (($field['type'] ?? '') === 'header' && ($field['subtype'] ?? '') === 'h1') {
                    $currentSection = trim(strip_tags($field['label'] ?? ''));
                    $sections[$currentSection] = [];
                }
                if ($currentSection !== null) {
                    $sections[$currentSection][] = $field;
                }
            }

            // Build filtered structure for current user's rank
            foreach ($sections as $sectionName => $sectionFields) {
                if (strcasecmp($sectionName, $userRankName) === 0) {
                    $filteredStructure = array_merge($filteredStructure, $sectionFields);
                }
            }

            // Get existing responses from OTHER ranks for read-only display
            $otherResponses = DB::table('interview_assessment_responses as iar')
                ->join('resort_admins as ra', 'ra.id', '=', 'iar.interviewer_id')
                ->leftJoin('employees as emp', 'emp.Admin_Parent_id', '=', 'ra.id')
                ->leftJoin('resort_departments as rd', 'rd.id', '=', 'emp.Dept_id')
                ->where('iar.form_id', $form[0]->id)
                ->where('iar.interviewee_id', $applicant_id)
                ->where('iar.interviewer_id', '!=', $interviewer_id)
                ->select('iar.*', 'emp.rank as interviewer_rank', 'ra.first_name', 'ra.last_name', 'rd.name as dept_name')
                ->get();

            foreach ($otherResponses as $resp) {
                $respRankName = isset($resp->interviewer_rank) ? ($rankConfig[$resp->interviewer_rank] ?? 'Unknown') : 'Unknown';
                // If interviewer is in HR department, map to "HR" section
                if (isset($resp->dept_name) && stripos($resp->dept_name, 'Human Resources') !== false) {
                    $respRankName = 'HR';
                }
                $respSectionFields = $sections[$respRankName] ?? [];
                if (!empty($respSectionFields)) {
                    $otherSectionsData[] = [
                        'rankName' => $respRankName,
                        'interviewer_name' => trim(($resp->first_name ?? '') . ' ' . ($resp->last_name ?? '')),
                        'fields' => $respSectionFields,
                        'responses' => json_decode($resp->responses, true),
                        'submitted_at' => $resp->created_at,
                    ];
                }
            }

            // Check if current user already submitted a response
            $existingResponse = InterviewAssessmentResponseForm::where('form_id', $form[0]->id)
                ->where('interviewee_id', $applicant_id)
                ->where('interviewer_id', $interviewer_id)
                ->first();
        } else {
            $existingResponse = null;
        }

        $existingResponseData = $existingResponse ? json_decode($existingResponse->responses, true) : null;

        return view('resorts.talentacquisition.interview-assessment.show',compact('form','interviewer_id','interviewee_id','page_title','filteredStructure','existingResponseData','otherSectionsData','userRankName'));
    }

    public function saveResponse(Request $request, $formId)
    {
        // Validate the incoming request data
        $validated = $request->validate([
            'interviewee_id' => 'required|exists:applicant_form_data,id',
        ]);

        try {
            // Fetch the authenticated user (interviewer)
            $interviewer = $this->resort->id;

            // Ensure the interviewer has a signature
            if (!$this->resort->signature_img) {
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Authorized signature is missing. Please upload it first from your profile page.'], 422);
                }
                return redirect()->back()->with('error', 'Authorized signature is missing. Please upload it first from your profile page.');
            }
            $signature = $this->resort->signature_img;

            // Initialize an empty responses array
            $responses = [];
            foreach ($request->all() as $key => $value) {
                if (in_array($key, ['_token', 'interviewer_id', 'interviewee_id'])) {
                    continue;
                }
                if ($value !== null) {
                    $responses[$key] = $value;
                }
            }

            // Check if responses are empty
            if (empty($responses)) {
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'No responses were submitted.'], 422);
                }
                return redirect()->back()->with('error', 'No responses were submitted.');
            }

            // Check if this interviewer already submitted — update instead of creating duplicate
            $existing = InterviewAssessmentResponseForm::where('form_id', $formId)
                ->where('interviewer_id', $interviewer)
                ->where('interviewee_id', $validated['interviewee_id'])
                ->first();

            if ($existing) {
                $existing->update([
                    'interviewer_signature' => $signature,
                    'responses' => json_encode($responses),
                ]);
                $message = 'Response updated successfully!';
            } else {
                InterviewAssessmentResponseForm::create([
                    'form_id' => $formId,
                    'interviewer_id' => $interviewer,
                    'interviewee_id' => $validated['interviewee_id'],
                    'interviewer_signature' => $signature,
                    'responses' => json_encode($responses),
                ]);
                $message = 'Response saved successfully!';
            }

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => $message]);
            }
            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            \Log::error('Error saving interview response: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Failed to save the response. Please try again.'], 500);
            }
            return redirect()->back()->with('error', 'Failed to save the response. Please try again.');
        }
    }

    public function viewResponse($formId, $responseId)
    {
        try {
            $page_title = "View Interview Assessment Response";
            $formId = base64_decode($formId);
            $responseId = base64_decode($responseId);
            // Fetch the response along with the form structure for rendering
            $response = InterviewAssessmentResponseForm::with(['interviewer', 'interviewee', 'form'])
                ->where('id', $responseId)
                ->where('form_id', $formId)
                ->firstOrFail();

            // dd($response);

            // Decode the stored JSON responses
            $responses = json_decode($response->responses, true);

            // Fetch the form structure
            $form = InterviewAssessmentForm::findOrFail($formId);
            $formStructure = json_decode($form->form_structure, true);

            return view('resorts.talentacquisition.interview-assessment.viewResponse', compact('response', 'responses', 'formStructure','page_title'));
        } catch (\Exception $e) {
            \Log::error('Error saving interview response: ' . $e->getMessage());

            // return redirect()->back()->withErrors(['error' => 'Failed to load the response. ' . $e->getMessage()]);
        }
    }

}
?>
