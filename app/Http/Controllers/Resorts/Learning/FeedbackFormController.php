<?php
namespace App\Http\Controllers\resorts\Learning;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\TrainingFeedbackForm;
use App\Models\TrainingFeedbackResponse;
use App\Models\Resort;
use App\Models\ResortPosition;
use App\Models\ResortAdmin;
use App\Models\TrainingSchedule;

use Validator;
use DB;
use App\Helpers\Common;
use Carbon\Carbon;
use URL;

class FeedbackFormController extends Controller
{
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;
        $this->rank=  $this->resort->GetEmployee->rank ?? '';
    }
    public function index(){
        // LR-06: forms management is HR / L&D Manager (full) or GM (view-only).
        if (!Common::hasFullDataAccess()) {
            return abort(403, 'Unauthorized access');
        }
        $page_title="Feedback Form";
        $trainings = TrainingSchedule::where('status','Completed')->where('resort_id',$this->resort->resort_id)->get();

        return view('resorts.learning.feedbackform.index',compact('page_title','trainings'));
    }

    public function list(Request $request)
    {
        if (!Common::hasFullDataAccess()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $searchTerm = $request->get('searchTerm');
    
        $forms = TrainingFeedbackForm::where('resort_id', $this->resort->resort_id)
            ->orderBy('id', 'DESC');
    
        // Apply search filter
        if ($searchTerm) {
            $forms->where('form_name', 'like', "%$searchTerm%");
        }
    
        // Do NOT call get() here; pass the query builder to DataTables
        return datatables()->of($forms)
            ->addColumn('form_name', function ($row) {
                return e($row->form_name);
            })
            ->addColumn('action', function ($row) {
                $view_url = route('feedback-form.preview', $row->id);
                $edit_url = route('feedback-form.edit', $row->id);

                return "<a href='$view_url' class='btn-tableIcon btnIcon-blue view-row-btn me-1' title='View'><i class='fa-solid fa-eye'></i></a>
                        <a href='$edit_url' class='btn-tableIcon btnIcon-yellow edit-row-btn me-1' title='Edit'><i class='fa-solid fa-pen-to-square'></i></a>
                        <a href='#' class='btn-tableIcon lnd-icon-critical delete-row-btn' title='Delete' data-id='$row->id'><i class='fa-regular fa-trash-can'></i></a>";
            })
            ->rawColumns(['form_name', 'action'])
            ->make(true);
    }
    
    public function create(Request $request)
    {
        // LR-06: only HR / L&D Manager may create/edit/delete feedback forms.
        if (!Common::canManageLearning()) {
            return abort(403, 'Unauthorized access');
        }
        $page_title = "Create Feedback Form";
        $resort_id = $this->resort->resort_id;
        $trainings = TrainingSchedule::with(['learningProgram', 'participants.employee.resortAdmin'])
            ->where('resort_id', $this->resort->resort_id)
            ->where(function ($q) {
                $q->where('status', 'Completed')->orWhere('status', 'Ongoing');
            })
            ->get();
        // dd($trainings);
        return view('resorts.learning.feedbackform.create',compact('resort_id','trainings','page_title'));
    }
    public function store(Request $request)
    {
        if (!Common::canManageLearning()) {
            return response()->json(['success' => false, 'message' => 'Only HR and L&D Managers can manage feedback forms.'], 403);
        }
        // dd($request->input('position'));
        $resortId = $this->resort->resort_id;

        $form = TrainingFeedbackForm::create([
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
        if (!Common::canManageLearning()) {
            return abort(403, 'Unauthorized access');
        }
        $page_title = "Edit Feedback Form";
        $resortId = $this->resort->resort_id;
        $form = TrainingFeedbackForm::where('resort_id', $resortId)->find($id);
        if (!$form) {
            abort(404, 'Feedback form not found.');
        }
        $form->form_structure = json_decode($form->form_structure, true);
        return view('resorts.learning.feedbackform.edit',compact('resortId','form','page_title'));
    }

    public function preview($id)
    {
        if (!Common::hasFullDataAccess()) {
            return abort(403, 'Unauthorized access');
        }
        $page_title = "Preview Feedback Form";
        $form = TrainingFeedbackForm::where('resort_id', $this->resort->resort_id)->findOrFail($id);
        $structure = json_decode($form->form_structure, true);
        if (is_string($structure)) $structure = json_decode($structure, true);
        $structure = is_array($structure) ? $structure : [];
        return view('resorts.learning.feedbackform.preview', compact('page_title', 'form', 'structure'));
    }

    public function update(Request $request, $id)
    {
        if (!Common::canManageLearning()) {
            return abort(403, 'Unauthorized access');
        }
        $form = TrainingFeedbackForm::where('resort_id', $this->resort->resort_id)->find($id);
        if (!$form) {
            abort(404, 'Feedback form not found.');
        }

        $validatedData = $request->validate([
            'form_name' => 'required|string|max:255',
            'form_structure' => 'required|string', // Ensure the form structure is valid JSON
        ]);

        $form->update([
            'form_name' => $validatedData['form_name'],
            'form_structure' => $validatedData['form_structure'], // Save updated structure
        ]);

        return redirect()->route('feedback-form.index')->with('success', 'Form updated successfully.');
    }

    public function delete($id)
    {
        if (!Common::canManageLearning()) {
            return response()->json(['success' => false, 'message' => 'Only HR and L&D Managers can manage feedback forms.'], 403);
        }
        $form = TrainingFeedbackForm::where('resort_id', $this->resort->resort_id)->find($id);
        if (!$form) {
            return response()->json(['success' => false, 'message' => 'Form not found.'], 404);
        }
        $form->delete();

        return response()->json(['success' => 'Form deleted successfully.']);
    }

    public function show($training_id,$participant_id)
    {
        $page_title = "View Feedback Form";
        // dd($this->resort);
        $training_id = base64_decode($training_id);
        $participant_id = base64_decode($participant_id);

        $form = TrainingFeedbackForm::where('position',$position_id)->get();
        $interviewer_id = $this->resort->id;
        $interviewee_id = $applicant_id;

        return view('resorts.talentacquisition.interview-assessment.show',compact('form','interviewer_id','interviewee_id','page_title'));
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
            $response = InterviewAssessmentResponseForm::create([
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
            $page_title = "View Feedback Response";
            $formId = base64_decode($formId);
            $responseId = base64_decode($responseId);
            // LR-01: this referenced InterviewAssessmentResponseForm/
            // InterviewAssessmentForm — TalentAcquisition models, not even
            // imported in this file (namespace App\Http\Controllers\
            // resorts\Learning has no such class), so every call fatals
            // with a class-not-found error. Never functional. The correct
            // model for this feature was already imported and unused —
            // TrainingFeedbackResponse/TrainingFeedbackForm — and, unlike
            // the TA models, resort-scoped via the form relation below.
            $response = TrainingFeedbackResponse::with(['training', 'participant', 'form'])
                ->where('id', $responseId)
                ->where('form_id', $formId)
                ->whereHas('form', function ($q) {
                    $q->where('resort_id', $this->resort->resort_id);
                })
                ->firstOrFail();

            // Decode the stored JSON responses
            $responses = $response->responses;

            $form = $response->form;
            $formStructure = json_decode($form->form_structure, true);

            // No blade view was ever built for this (the method fataled on
            // every call before, so nothing could have rendered one
            // either) — return the data rather than inventing a new view
            // template, which is feature work beyond this security fix.
            return response()->json(compact('response', 'responses', 'formStructure', 'page_title'));
        } catch (\Exception $e) {
            \Log::error('Error loading feedback response: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Failed to load the response.']);
        }
    }


}
?>
