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
use App\Models\ResortGrade;
use Auth;
use DB;
use Common;
use Carbon\Carbon;

class LearningProgramController extends Controller
{
    public $resort;
    public $reporting_to;
    protected $underEmp_id=[];
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if($this->resort->is_master_admin == 0){
            $this->reporting_to = $this->resort->GetEmployee->id;
            $this->underEmp_id = Common::getSubordinates($this->reporting_to);
        }
    }
    public function index()
    {
        if(Common::checkRouteWisePermission('learning.programs.index',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }

        $resort_id = $this->resort->resort_id;
        $page_title ='Learning Program';
        $categories= LearningCategory::where('resort_id',$resort_id)->get();
        $positions = ResortPosition::where('status','active')->where('resort_id',$resort_id)->get();
        $departments = ResortDepartment::where('status','active')->where('resort_id',$resort_id)->get();
        $employees = Employee::with('resortAdmin')->where('resort_id',$resort_id)->whereIn('status', ['Active', 'Probationary'])->get();
        $grades = config('settings.Position_Rank');
        $trainers = Employee::with('resortAdmin')->where('resort_id',$resort_id)->whereIn('rank',['1','2','3','4','5','7','8','9'])->whereIn('status', ['Active', 'Probationary'])->get();
        return view('resorts.learning.program.index',compact('page_title','categories','positions','departments','employees','grades','trainers'));
    }

    public function list(Request $request)
    {
        try {
            $resort_id = $this->resort->resort_id;

            // Fetch programs with category relationship
            $query = LearningProgram::with('category')->where('resort_id', $resort_id);

            if ($request->searchTerm) {
                $searchTerm = $request->searchTerm;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('description', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('objectives', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('frequency', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('days', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('hours', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('delivery_mode', 'LIKE', "%{$searchTerm}%");
                });
            }
    
            // Apply category filter
            if ($request->category) {
                $query->where('learning_category_id', $request->category);
            }

            // Apply ordering if present
            if ($request->has('order')) {
                $columnIndex = $request->input('order.0.column'); // Index of the column
                $columnName = $request->input("columns.$columnIndex.data"); // Data field name
                $direction = $request->input('order.0.dir'); // asc or desc

                // Prevent SQL injection by only allowing certain fields
                $sortableColumns = ['name', 'description', 'objectives', 'frequency', 'days', 'hours', 'delivery_mode', 'created_at'];
                if (in_array($columnName, $sortableColumns)) {
                    $query->orderBy($columnName, $direction);
                }
            }

            $programs = $query->get();

            // dd($programs);

            return datatables()->of($programs)
                ->addColumn('category', function ($row) {
                    return optional($row->category)->category ?? 'N/A'; // Prevents null errors
                })
                ->addColumn('duration', function ($row) {
                    return "{$row->days} Days {$row->hours} hrs";
                })
                ->addColumn('target_audience', function ($row) {
                    if (!is_array($row->target_audience)) {
                        return 'N/A';
                    }
    
                    switch ($row->audience_type) {
                        case 'departments':
                            return ResortDepartment::whereIn('id', $row->target_audience)->pluck('name')->implode(', ');
    
                        case 'positions':
                            return ResortPosition::whereIn('id', $row->target_audience)->pluck('name')->implode(', ');
    
                        case 'grades':
                             return $grades = config('settings.Position_Rank');
                            
    
                        case 'employees':
                            return Employee::with('resortAdmin')
                            ->whereIn('id', $row->target_audience)
                            ->get()
                            ->map(function ($employee) {
                                return $employee->resortAdmin 
                                    ? $employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name 
                                    : 'N/A'; // If no related admin found
                            })
                            ->implode(', ');
                            
                        default:
                            return 'N/A';
                    }
                })
                ->rawColumns(['name', 'description', 'objectives', 'category', 'target_audience', 'duration', 'frequency', 'delivery_mode'])
                ->make(true);
        } catch (\Exception $e) {
            \Log::error("Error fetching Learning Programs: " . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch data'], 500);
        }
    }

    public function save(Request $request)
    {
        // dd($request->all());
        $resort_id = $this->resort->resort_id;
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'objectives' => 'required|string',
            'category' => 'required|exists:learning_categories,id',
            'audience_type' => 'required|in:departments,grades,positions,employees',
            'target_audiance' => 'required|array',
            'hours' => 'required|numeric|min:0.1',
            'days' => 'required|integer',
            'frequency' => 'required|string|in:one-time,recurring,quarterly,annually',
            'delivery_mode' => 'required|string|in:face-to-face,online,hybrid',
            'trainer' => 'required|exists:employees,id',
            'prior_qualification' => 'nullable|string',
            'learning_material.*' => 'nullable|mimes:pdf,ppt,pptx|max:2048', // Allow multiple files
        ]);

        // Store the learning program details
        $learningProgram = LearningProgram::create([
            'resort_id'=>$this->resort->resort_id,
            'name' => $request->name,
            'description' => $request->description,
            'objectives' => $request->objectives,
            'learning_category_id' => $request->category,
            'audience_type' => $request->audience_type,
            'target_audience' => $request->target_audiance, // Storing array
            'hours' => $request->hours,
            'days' => $request->days,
            'frequency' => $request->frequency,
            'delivery_mode' => $request->delivery_mode,
            'trainer' => $request->trainer,
            'prior_qualification' => $request->prior_qualification ?? null,
        ]);
        if( $learningProgram ){
            $encodedResortId = base64_encode($this->resort->resort_id);
            $storagePath = config('settings.learning_materials') . '/' . $encodedResortId;
            $filePaths = []; // Store file paths

            if ($request->hasFile('learning_material')) {
                foreach ($request->file('learning_material') as $file) {
                    // Generate unique file name
                    $fileName = time() . '_' . $file->getClientOriginalName();
                    $path = $file->storeAs($storagePath, $fileName); // Store file in dynamic path
                    $filePaths[] = $path; // Save the path in array

                    LearningMaterials::create([
                        'learning_program_id' => $learningProgram->id,
                        'file_path' => $path,
                    ]);
                }
            }
        }
        return response()->json(['success' => true, 'msg' => 'Learning Program saved successfully.']);
    }

    public function getProgramDetails(Request $request)
    {
        $program = LearningProgram::with('category')->find($request->program_id);

        if (!$program) {
            return response()->json(['success' => false, 'message' => 'Program not found']);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'category_id' => $program->learning_category_id,
                'trainer_id' => $program->trainer,
                'frequency' => $program->frequency,
            ]
        ]);
    }
}