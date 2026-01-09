<?php

namespace App\Http\Controllers\Resorts\Learning;

use App\Http\Controllers\Controller;
use App\Events\ResortNotificationEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\LearningCategory;
use App\Models\LearningProgram;
use App\Models\LearningMaterials;
use App\Models\ResortPosition;
use App\Models\ResortDepartment;
use App\Models\Employee;
use App\Models\MandatoryLearningProgram;
use App\Models\ProbationaryLearningProgram;
use App\Models\AttendanceParameters;
use Auth;
use DB;
use Common;
use Carbon\Carbon;

class ConfigurationController extends Controller
{
    public $resort;
    public $reporting_to;
    protected $underEmp_id=[];
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if($this->resort->is_master_admin == 0){
            $this->reporting_to = $this->resort->GetEmployee->id ?? '';
            $this->underEmp_id = Common::getSubordinates($this->reporting_to);
        }
    }
    public function index()
    {
        if(Common::checkRouteWisePermission('learning.configration',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }
        $resort_id = $this->resort->resort_id;
        $page_title ='Learning Configuration';
        $categories= LearningCategory::where('resort_id',$resort_id)->get();
        $programs= LearningProgram::where('resort_id',$resort_id)->get();

        $positions = ResortPosition::where('status','active')->where('resort_id',$resort_id)->get();
        $departments = ResortDepartment::where('status','active')->where('resort_id',$resort_id)->get();
        $employees = Employee::with('resortAdmin')->where('resort_id',$resort_id)->whereIn('status', ['Active', 'Probationary'])->get();
        $grades = config('settings.Position_Rank');
        $trainers = Employee::with('resortAdmin')->where('resort_id',$resort_id)->whereIn('rank',['1','2','3','4','5','7','8','9'])->whereIn('status', ['Active', 'Probationary'])->get();
        $attenndanceParameters = AttendanceParameters::where('resort_id', $resort_id)->first();
        // dd($attenndanceParameters);
        return view('resorts.learning.config.index',compact('page_title','attenndanceParameters','categories','programs','positions','departments','employees','grades','trainers'));
    }

    public function saveCategories(Request $request)
    {
        $resort_id = $this->resort->resort_id;
        // Validate the incoming data
        $validator = Validator::make($request->all(), [
            'category' => [
                'required',
                'max:255',
                Rule::unique('learning_categories')->where(function ($query) use ($resort_id) {
                    return $query->where('resort_id', $resort_id);
                })
            ],
        ], [
            'category.required' => 'The Category field is required.',
            'category.unique' => 'The Category already exists for this resort.',
            'category.max' => 'The maximum allowed length for the Category Name is 255 characters.',
        ]);

        if($validator->fails())
        {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        LearningCategory::create([
            'resort_id' => $this->resort->resort_id,
            'category' => $request->category,
            'color' => $request->color
        ]);

        return response()->json(['success' => true, 'msg' => 'Category created successfully.']);
    }

    public function listCategories()
    {
       
        $categories = LearningCategory::where('resort_id', $this->resort->resort_id)->orderBy('id', 'desc')->get();

            return datatables()->of($categories)
                ->addColumn('action', function ($row) {
                    $editUrl = asset('resorts_assets/images/edit.svg');
                    $deleteUrl = asset('resorts_assets/images/trash-red.svg');

                    return '
                        <div class="d-flex align-items-center">
                            <a href="#" class="btn-lg-icon icon-bg-red edit-row-btn"
                           data-category-id="'. htmlspecialchars($row->id, ENT_QUOTES, 'UTF-8') . '">
                                <img src="' . $editUrl . '" alt="Edit" class="img-fluid" />
                            </a>
                            <a href="#" class="btn-lg-icon icon-bg-red delete-row-btn"
                           data-category-id="'. htmlspecialchars($row->id, ENT_QUOTES, 'UTF-8') . '">
                                <img src="' . $deleteUrl . '" alt="Delete" class="img-fluid" />
                            </a>
                        </div>';
                })
                ->rawColumns(['category', 'color','action'])
                ->make(true);

            try {  } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch data'], 500);
        }

    }

    public function inlineCategoryUpdate(Request $request, $id){
        $categoryId = $id;

        $resort_id = $this->resort->resort_id;
        $validator = Validator::make($request->all(), [
            'category' => [
                'required',
                'max:255',
                Rule::unique('learning_categories')->where(function ($query) use ($resort_id,$categoryId) {
                    return $query->where('resort_id', $resort_id);
                })->ignore( $categoryId),
            ],
        ], [
            'category.required' => 'The Category field is required.',
            'category.unique' => 'The Category already exists for this resort.',
            'category.max' => 'The maximum allowed length for the Category Name is 255 characters.',
        ]);

        if($validator->fails())
        {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        // Find the division by ID
        $category = LearningCategory::find($id);
        // dd($request);

        if (!$category) {
            return response()->json(['success' => false, 'message' => 'Category not found.']);
        }
        try {
            // Update the division's attributes
            $category->category = $request->input('category');
            $category->color = $request->input('color');
                        
            // agent the changes
            $category->save();
            
            // Return a JSON response
            return response()->json(['success' => true, 'message' => 'Category updated successfully.']);
        } catch( \Exception $e ) {
            \Log::emergency( "File: ".$e->getFile() );
            \Log::emergency( "Line: ".$e->getLine() );
            \Log::emergency( "Message: ".$e->getMessage() );

            return response()->json(['success' => false, 'message' => 'Failed to update category.']);
        }
    }

    public function destroyCategory($id)
    {
        DB::beginTransaction();
        try{

            LearningCategory::find($id)->delete();
            DB::commit();
            return response()->json(['success' => true, 'msg' => 'category Deleted successfully.'], 200);


        }
        catch( \Exception $e )
        {

            DB::rollBack();
            \Log::emergency("File: ".$e->getFile());
            \Log::emergency("Line: ".$e->getLine());
            \Log::emergency("Message: ".$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete category.'], 500);

        }

    }

    public function getMandatoryPositions(Request $request)
    {
        $departmentId = $request->input('department_id');

        if (!$departmentId) {
            return response()->json(['success' => false, 'message' => 'Invalid department ID']);
        }

        $positions = ResortPosition::where('dept_id', $departmentId)->get(['id', 'position_title']);

        if ($positions->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No positions found']);
        }

        return response()->json(['success' => true, 'positions' => $positions]);
    }

    public function save_mandatory_program(Request $request){
        $validatedData = $request->validate([
            'programs' => 'required|array|min:1',
            'programs.*.mandatory_program' => 'required|exists:learning_programs,id',
            'programs.*.mandatory_department' => 'required|exists:resort_departments,id',
            'programs.*.mandatory_position' => 'required|exists:resort_positions,id',
            'programs.*.notify_before_days' => 'required|integer|min:1|max:7',
        ]);
    
        try {
            foreach ($validatedData['programs'] as $program) {
                MandatoryLearningProgram::create([
                    'resort_id' => $this->resort->resort_id,
                    'program_id' => $program['mandatory_program'],
                    'department_id' => $program['mandatory_department'],
                    'position_id' => $program['mandatory_position'],
                    'notify_before_days' => $program['notify_before_days'],
                ]);
            }
    
            return response()->json([
                'success' => true,
                'message' => 'Mandatory Learning Programs saved successfully.'
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save mandatory programs.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function get_mandatory_program(){
        $resort_id = $this->resort->resort_id;
        $page_title ='Mandatory Learning';
        $categories= LearningCategory::where('resort_id',$resort_id)->get();
        $programs= LearningProgram::where('resort_id',$resort_id)->get();
        $positions = ResortPosition::where('status','active')->where('resort_id',$resort_id)->get();
        $departments = ResortDepartment::where('status','active')->where('resort_id',$resort_id)->get();
        return view('resorts.learning.program.mandatory',compact('page_title','categories','programs','positions','departments'));
    }

    public function list_mandatory_program(Request $request){
        try {
            $resort_id = $this->resort->resort_id;
    
            // Fetch programs with department, position, and category relationships
            $query = MandatoryLearningProgram::with(['department', 'position'])
                ->where('resort_id', $resort_id);
    
            // Apply search filter
            
            if ($request->searchTerm) {
                $query->where(function ($q) use ($request) {
                    $q->whereHas('program', function ($q2) use ($request) {
                        $q2->where('name', 'LIKE', "%{$request->searchTerm}%");
                    })->orWhereHas('department', function ($q2) use ($request) {
                        $q2->where('name', 'LIKE', "%{$request->searchTerm}%");
                    })->orWhereHas('position', function ($q2) use ($request) {
                        $q2->where('position_title', 'LIKE', "%{$request->searchTerm}%");
                    });
                });
            }
    
            // Apply department filter
            if ($request->department) {
                $query->where('department_id', $request->department);
            }
    
            // Apply position filter
            if ($request->position) {
                $query->where('position_id', $request->position);
            }
    
            $programs = $query->get();
    
            return datatables()->of($programs)
                ->addColumn('program', function ($row) {
                    return optional($row->program)->name ?? 'N/A'; // ✅ Returns 'N/A' if null
                })
                ->addColumn('department', function ($row) {
                    return optional($row->department)->name ?? 'N/A';
                })
                ->addColumn('position', function ($row) {
                    return optional($row->position)->position_title ?? 'N/A';
                })
                ->addColumn('notify_before_days', function ($row) {
                    return $row->notify_before_days ?? 'N/A'; // Fixed Notify Before Days column
                })
                ->rawColumns(['program', 'department', 'position', 'notify_before_days'])
                ->make(true);
        } catch (\Exception $e) {
            \Log::error("Error fetching Learning Programs: " . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch data'], 500);
        }
    }

    public function get_probationary_program(){
        $resort_id = $this->resort->resort_id;
        $page_title ='Probationary Learning';
        $programs= LearningProgram::where('resort_id',$resort_id)->get();
        return view('resorts.learning.program.probationary',compact('page_title','programs'));
    }

    public function list_probationary_program(Request $request){
        try {
            $resort_id = $this->resort->resort_id;
    
            // Fetch programs with department, position, and category relationships
            $query = ProbationaryLearningProgram::with(['program'])
                ->where('resort_id', $resort_id);
    
            // Apply search filter
            
            if ($request->searchTerm) {
                $query->where(function ($q) use ($request) {
                    $q->whereHas('program', function ($q2) use ($request) {
                        $q2->where('name', 'LIKE', "%{$request->searchTerm}%");
                    });
                });
            }
        
            $programs = $query->get();
    
            return datatables()->of($programs)
                ->addColumn('program', function ($row) {
                    return optional($row->program)->name ?? 'N/A'; // ✅ Returns 'N/A' if null
                })
                ->addColumn('notify_before_days', function ($row) {
                    return $row->notify_before_days ?? 'N/A'; // Fixed Notify Before Days column
                })
                ->rawColumns(['program', 'department', 'position', 'completion_days'])
                ->make(true);
        } catch (\Exception $e) {
            \Log::error("Error fetching Learning Programs: " . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch data'], 500);
        }
    }

    public function save_probationary_program(Request $request){    
        try {
            ProbationaryLearningProgram::create([
                'resort_id' => $this->resort->resort_id,
                'program_id' => $request->probationary_programs,
                'completion_days' => $request->completion_days,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Probationary Learning Programs saved successfully.',
                'redirect_url'=>route('probationary.learning.get')
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save mandatory programs.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function saveAttendanceParameters(Request $request)
    {
        $request->validate([
            'threshold_percentage' => 'nullable|integer|min:0|max:100',
            'auto_notifications' => 'nullable',
        ]);

        AttendanceParameters::updateOrCreate(
            ['resort_id' => auth()->user()->resort_id],
            [
                'threshold_percentage' => $request->threshold_percentage,
                'auto_notifications' => $request->has('auto_notifications'),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Attendance Parameters saved successfully.',
        ]);
    }

    public function saveEvaluationReminder(Request $request)
    {
        $request->validate([
            'evaluation_reminder' => 'nullable|string|max:255',
        ]);

        AttendanceParameters::updateOrCreate(
            ['resort_id' => auth()->user()->resort_id],
            [
                'evaluation_reminder' => $request->evaluation_reminder,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Reminder saved successfully.',
        ]);
    }
}