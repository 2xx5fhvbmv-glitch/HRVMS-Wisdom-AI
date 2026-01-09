<?php
namespace App\Http\Controllers\resorts\Learning;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\EvaluationForm;
use App\Models\EvaluationFormResponse;
use App\Models\Resort;
use App\Models\ResortPosition;
use App\Models\ResortAdmin;
use App\Models\TrainingSchedule;

use Validator;
use DB;
use App\Helpers\Common;
use Carbon\Carbon;
use URL;

class EvaluationFormController extends Controller
{
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        $this->rank=  $this->resort->GetEmployee->rank ?? '';
    }
    public function index(){
        $page_title="Evaluation Form";
        $trainings = TrainingSchedule::where('status','Completed')->where('resort_id',$this->resort->resort_id)->get();

        return view('resorts.learning.evaluation.index',compact('page_title','trainings'));
    }

    public function list(Request $request)
    {
        $searchTerm = $request->get('searchTerm');
    
        $forms = EvaluationForm::where('resort_id', $this->resort->resort_id)
            ->orderBy('id', 'DESC');
    
        // Apply search filter
        if ($searchTerm) {
            $forms->where('form_name', 'like', "%$searchTerm%");
        }
    
        // Do NOT call get() here; pass the query builder to DataTables
        return datatables()->of($forms)
            ->addColumn('form_name', function ($row) {
                return $row->form_name;
            })
            ->addColumn('action', function ($row) {
                $edit_url = route('evaluation-form.edit', $row->id);
                $editimg = asset('resorts_assets/images/edit.svg');
                $deleteimg = asset('resorts_assets/images/trash-red.svg');
    
                return "<a href='$edit_url' class='edit-row-btn'><img src='$editimg' alt='Edit'></a>
                        <a href='#' class='delete-row-btn' data-id='$row->id'><img src='$deleteimg' alt='Delete'></a>";
            })
            ->rawColumns(['form_name', 'action'])
            ->make(true);
    }
    
    public function create(Request $request)
    {
        $resort_id = $this->resort->resort_id;
        $page_title = 'Create Evaluation Form';
        $trainings = TrainingSchedule::with(['learningProgram', 'participants.employee.resortAdmin'])->where('status','Completed')->orwhere('status','Ongoing')->where('resort_id',$this->resort->resort_id)->get();
        // dd($trainings);
        return view('resorts.learning.evaluation.create',compact('resort_id','trainings','page_title'));
    }
    public function store(Request $request)
    {
        // dd($request->input('position'));
        $resortId = $this->resort->resort_id;

        $form = EvaluationForm::create([
            'resort_id' => $resortId,
            'form_name' => $request->input('form_name'),
            // 'position' => $request->input('position'),
            'form_structure' => json_encode($request->input('form_structure')) // Save form as JSON
        ]);
        // dd($form);

        return response()->json(['success' => true, 'form' => $form]);
    }

    public function edit($id)
    {
        $page_title = 'Edit Evaluation Form';
        $resortId = $this->resort->resort_id;
        $form = EvaluationForm::findOrFail($id);
        $form->form_structure = json_decode($form->form_structure, true);
        return view('resorts.learning.evaluation.edit',compact('resortId','form','page_title'));
    }

    public function update(Request $request, $id)
    {
        $form = EvaluationForm::findOrFail($id);

        $validatedData = $request->validate([
            'form_name' => 'required|string|max:255',
            'form_structure' => 'required|string', // Ensure the form structure is valid JSON
        ]);

        $form->update([
            'form_name' => $validatedData['form_name'],
            'form_structure' => $validatedData['form_structure'], // Save updated structure
        ]);

        return redirect()->route('evaluation-form.index')->with('success', 'Form updated successfully.');
    }

    public function delete($id)
    {
        $form = EvaluationForm::findOrFail($id);
        $form->delete();

        return response()->json(['success' => 'Form deleted successfully.']);
    }

    public function show($training_id,$participant_id)
    {
        $page_title = 'View Evaluation Form';
        // dd($this->resort);
        $training_id = base64_decode($training_id);
        $participant_id = base64_decode($participant_id);

        $form = EvaluationForm::where('position',$position_id)->get();
        $interviewer_id = $this->resort->id;
        $interviewee_id = $applicant_id;

        return view('resorts.learning.evaluation.show',compact('form','interviewer_id','interviewee_id','page_title'));
    }

    public function saveResponse(Request $request, $formId)
    {
        // Validate the incoming request data
        $validated = $request->validate([
            'interviewee_id' => 'required|exists:applicant_form_data,id',
        ]);

        try {
            // Fetch the authenticated user (interviewer)
            $interviewer = $this->resort->id; // Get the logged-in user

            // Ensure the interviewer has a signature
            if (!$this->resort->signature_img) {
                return redirect()->back()->with('error', 'Authorized signature is missing. Please upload it first from your profile page.');
            }

            // Initialize an empty responses array
            $responses = [];
            foreach ($request->all() as $key => $value) {
                if (in_array($key, ['_token', 'interviewer_id', 'interviewee_id'])) {
                    continue; // Skip non-response fields
                }
                if ($value !== null) {
                    $responses[$key] = $value;
                }
            }

            // Check if responses are empty
            if (empty($responses)) {
                return redirect()->back()->with('error', 'No responses were submitted.');
            }

            // Save the response record
            $response = EvaluationFormResponse::create([
                'form_id' => $formId,
                'interviewer_id' => $interviewer, // Use authenticated user's ID
                'interviewee_id' => $validated['interviewee_id'],
                'interviewer_signature' => $this->resort->signature_img,
                'responses' => json_encode($responses),
            ]);

            // Redirect with a success message
            return redirect()->back()->with('success', 'Response saved successfully!');
        } catch (\Exception $e) {
            // Log the error and return a failure response
            \Log::error('Error saving interview response: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Failed to save the response. Please try again.');
        }
    }

    public function viewResponse($formId, $responseId)
    {
        try {
            $page_title = 'View Evaluation Response';
            $formId = base64_decode($formId);
            $responseId = base64_decode($responseId);
            // Fetch the response along with the form structure for rendering
            $response = EvaluationFormResponse::with(['interviewer', 'interviewee', 'form'])
                ->where('id', $responseId)
                ->where('form_id', $formId)
                ->firstOrFail();

            // dd($response);

            // Decode the stored JSON responses
            $responses = json_decode($response->responses, true);

            // Fetch the form structure
            $form = InterviewAssessmentForm::findOrFail($formId);
            $formStructure = json_decode($form->form_structure, true);

            return view('resorts.talentacquisition.interview-assessment.viewResponse', compact('response', 'responses', 'formStructure', 'page_title'));
        } catch (\Exception $e) {
            \Log::error('Error saving interview response: ' . $e->getMessage());

            // return redirect()->back()->withErrors(['error' => 'Failed to load the response. ' . $e->getMessage()]);
        }
    }


}
?>
