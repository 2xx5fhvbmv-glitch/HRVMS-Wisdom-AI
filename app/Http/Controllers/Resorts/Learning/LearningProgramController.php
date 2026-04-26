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
        if(!$this->resort) return;
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
        $scopedDeptIds = Common::getScopedDepartmentIds();
        $categories= LearningCategory::where('resort_id',$resort_id)->get();
        $positions = ResortPosition::where('status','active')->where('resort_id',$resort_id)->get();
        $departments = ResortDepartment::where('status','active')->where('resort_id',$resort_id)
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('id', $scopedDeptIds))
            ->get();
        $employees = Employee::with('resortAdmin')->where('resort_id',$resort_id)
            ->whereIn('status', ['Active', 'Probationary'])
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))
            ->get();
        $grades = config('settings.Position_Rank');
        $trainers = Employee::with('resortAdmin')->where('resort_id',$resort_id)
            ->whereIn('rank',['1','2','3','4','5','7','8','9'])
            ->whereIn('status', ['Active', 'Probationary'])
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds))
            ->get();
        return view('resorts.learning.program.index',compact('page_title','categories','positions','departments','employees','grades','trainers'));
    }

    public function show($id)
    {
        if(Common::checkRouteWisePermission('learning.programs.index',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }
        $program = LearningProgram::with('category')
            ->where('resort_id', $this->resort->resort_id)
            ->find(base64_decode($id));
        if (!$program) abort(404, 'Learning Program not found.');

        $materials = LearningMaterials::where('learning_program_id', $program->id)->get();

        // Resolve target audience labels for display
        $audienceLabels = [];
        if (is_array($program->target_audience) && !empty($program->target_audience)) {
            switch ($program->audience_type) {
                case 'departments':
                    $audienceLabels = ResortDepartment::whereIn('id', $program->target_audience)->pluck('name')->toArray();
                    break;
                case 'positions':
                    $audienceLabels = ResortPosition::whereIn('id', $program->target_audience)->pluck('position_title')->toArray();
                    break;
                case 'grades':
                    $rankMap = config('settings.Position_Rank', []);
                    $audienceLabels = array_values(array_filter(array_map(fn($g) => $rankMap[$g] ?? null, $program->target_audience)));
                    break;
                case 'employees':
                    $audienceLabels = Employee::with('resortAdmin')->whereIn('id', $program->target_audience)->get()
                        ->map(fn($e) => optional($e->resortAdmin)->full_name ?? '-')->toArray();
                    break;
            }
        }

        $trainer = $program->trainer ? Employee::with('resortAdmin')->find($program->trainer) : null;
        $page_title = 'Learning Program Details';
        return view('resorts.learning.program.show', compact('page_title', 'program', 'audienceLabels', 'trainer', 'materials'));
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

            return datatables()->of($programs)
                ->editColumn('description', fn($row) => $row->description ? e(\Illuminate\Support\Str::limit(strip_tags($row->description), 100)) : '-')
                ->editColumn('objectives', fn($row) => $row->objectives ? e(\Illuminate\Support\Str::limit(strip_tags($row->objectives), 100)) : '-')
                ->addColumn('category', function ($row) {
                    return optional($row->category)->category ?? 'N/A';
                })
                ->addColumn('duration', function ($row) {
                    // Hours / Days are now mutually optional — render only the parts present.
                    $parts = [];
                    if (!empty($row->days))  $parts[] = $row->days . ' Days';
                    if (!empty($row->hours)) $parts[] = $row->hours . ' hrs';
                    return $parts ? implode(' ', $parts) : '-';
                })
                ->addColumn('target_audience', function ($row) {
                    if (!is_array($row->target_audience)) {
                        return 'N/A';
                    }

                    switch ($row->audience_type) {
                        case 'departments':
                            return ResortDepartment::whereIn('id', $row->target_audience)->pluck('name')->implode(', ');

                        case 'positions':
                            return ResortPosition::whereIn('id', $row->target_audience)->pluck('position_title')->implode(', ');

                        case 'grades':
                            $rankMap = config('settings.Position_Rank', []);
                            return collect($row->target_audience)->map(fn($g) => $rankMap[$g] ?? $g)->implode(', ');

                        case 'employees':
                            // Scope: only return employee names in the logged-in user's dept-visibility scope.
                            $scopedEmpIds = Common::getPerformanceScopedEmpIds();
                            $ids = $row->target_audience;
                            if (is_array($scopedEmpIds)) {
                                $ids = array_values(array_intersect($ids, $scopedEmpIds));
                            }
                            return Employee::with('resortAdmin')
                                ->whereIn('id', $ids)
                                ->get()
                                ->map(function ($employee) {
                                    return $employee->resortAdmin
                                        ? $employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name
                                        : 'N/A';
                                })
                                ->implode(', ');

                        default:
                            return 'N/A';
                    }
                })
                ->addColumn('action', function ($row) {
                    $url = route('learning.programs.show', base64_encode($row->id));
                    return '<a href="'.$url.'" class="btn-tableIcon btnIcon-blue" title="View"><i class="fa-solid fa-eye"></i></a>';
                })
                ->rawColumns(['name', 'description', 'objectives', 'category', 'target_audience', 'duration', 'frequency', 'delivery_mode', 'action'])
                ->make(true);
        } catch (\Exception $e) {
            \Log::error("Error fetching Learning Programs: " . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch data'], 500);
        }
    }

    public function save(Request $request)
    {
        $resort_id = $this->resort->resort_id;
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            // Objectives can arrive as a single string (legacy) or as an array of strings
            // (new repeating "Add More" UI). Accept both.
            'objectives' => 'required',
            'objectives.*' => 'nullable|string|max:1000',
            'category' => 'required|exists:learning_categories,id',
            'audience_type' => 'required|in:departments,grades,positions,employees',
            'target_audiance' => 'required|array',
            // Hours / Days are mutually optional — at least one must be filled.
            'hours' => 'nullable|required_without:days|numeric|min:0.1',
            'days'  => 'nullable|required_without:hours|integer|min:1',
            // 'recurring' kept in the allow-list as a safety net for any in-flight client
            // posting the old value before cache clears; the migration rewrites stored data.
            'frequency' => 'required|string|in:one-time,monthly,recurring,quarterly,annually',
            'frequency_day' => 'nullable|integer|min:1|max:30',
            'delivery_mode' => 'required|string|in:face-to-face,online,hybrid',
            'trainer' => 'required|exists:employees,id',
            'external_training' => 'nullable|string|max:255',
            'external_trainer_company' => 'nullable|string|max:255',
            'trainer_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'prior_qualification' => 'nullable|string',
            'learning_material.*' => 'nullable|mimes:pdf,ppt,pptx|max:2048',
        ]);

        // Normalize objectives — array of bullet lines or a single string.
        $objectivesText = $this->normalizeObjectives($request->input('objectives'));
        if ($objectivesText === '') {
            return response()->json(['success' => false, 'msg' => 'Please enter at least one objective.'], 422);
        }

        // Trainer image — uploaded to storage/app/learning_trainer_images/{resort_id}/
        $trainerImagePath = null;
        if ($request->hasFile('trainer_image')) {
            $img = $request->file('trainer_image');
            $trainerImagePath = $img->storeAs(
                'learning_trainer_images/' . $this->resort->resort_id,
                time() . '_' . $img->getClientOriginalName()
            );
        }

        // Store the learning program details
        $learningProgram = LearningProgram::create([
            'resort_id'=>$this->resort->resort_id,
            'name' => $request->name,
            'description' => $request->description,
            'objectives' => $objectivesText,
            'learning_category_id' => $request->category,
            'audience_type' => $request->audience_type,
            'target_audience' => $request->target_audiance, // Storing array
            'hours' => $request->hours,
            'days' => $request->days,
            // Normalize the legacy "recurring" value at write time too.
            'frequency' => $request->frequency === 'recurring' ? 'monthly' : $request->frequency,
            // Day-of-month only applies to one-time / monthly / quarterly.
            'frequency_day' => in_array($request->frequency, ['one-time', 'monthly', 'recurring', 'quarterly'])
                ? $request->frequency_day
                : null,
            'delivery_mode' => $request->delivery_mode,
            'trainer' => $request->trainer,
            'external_training' => $request->external_training,
            'external_trainer_company' => $request->external_trainer_company,
            'trainer_image' => $trainerImagePath,
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

    /**
     * Normalize the `objectives` input into a single bullet-prefixed string.
     * Accepts either a string (legacy / pasted block) or an array of lines
     * (the new "Add More" UI). Empty entries are dropped.
     */
    private function normalizeObjectives($input)
    {
        if (is_array($input)) {
            $lines = collect($input)
                ->map(fn($v) => trim((string) $v))
                ->filter(fn($v) => $v !== '')
                ->map(function ($line) {
                    // Don't double-bullet existing lines that already start with one.
                    return preg_match('/^[•\-\*]/', $line) ? $line : '• ' . $line;
                })
                ->values()
                ->all();
            return implode("\n", $lines);
        }
        return trim((string) $input);
    }

    /**
     * Stream the trainer image for a Learning Program. Stored on the local disk
     * (private) so it needs a controller route rather than asset(...) URL.
     */
    public function trainerImage($id)
    {
        $program = LearningProgram::where('resort_id', $this->resort->resort_id)->find(base64_decode($id));
        if (!$program || !$program->trainer_image) abort(404, 'Trainer image not found.');

        if (!\Illuminate\Support\Facades\Storage::disk('local')->exists($program->trainer_image)) {
            abort(404, 'Image missing from storage.');
        }
        return \Illuminate\Support\Facades\Storage::disk('local')->response($program->trainer_image);
    }

    /**
     * Stream a Learning Program material file (stored on the local disk under storage/app/).
     * Access is gated to users who can view the parent program.
     */
    public function downloadMaterial($id)
    {
        $material = LearningMaterials::find(base64_decode($id));
        if (!$material) abort(404, 'Material not found.');

        $program = LearningProgram::where('resort_id', $this->resort->resort_id)->find($material->learning_program_id);
        if (!$program) abort(403, 'You do not have access to this material.');

        if (!\Illuminate\Support\Facades\Storage::disk('local')->exists($material->file_path)) {
            abort(404, 'File missing from storage.');
        }

        return \Illuminate\Support\Facades\Storage::disk('local')->download(
            $material->file_path,
            basename($material->file_path)
        );
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