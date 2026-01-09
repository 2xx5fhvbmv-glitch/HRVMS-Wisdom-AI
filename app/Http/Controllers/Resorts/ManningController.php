<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use App\Models\ResortAdmin;
use App\Models\ResortRole;
use App\Models\ResortModule;
use App\Models\ResortPermission;
use App\Models\ResortModulePermission;
use App\Models\ResortRoleModulePermission;
use App\Models\Division;
use App\Models\Department;
use App\Models\Section;
use App\Models\Position;
use App\Models\ResortDivision;
use App\Models\ResortDepartment;
use App\Models\ResortSection;
use App\Models\ResortPosition;
use App\Helpers\Common;
use App\Models\ResortInteralPagesPermission;
use App\Models\Employee;
use DB;
use Illuminate\Validation\Rule;

class ManningController extends Controller
{
    public $resort_id;
    function __construct() {
        $this->resort_id=  Auth::guard('resort-admin')->user()->resort_id;
    }

    public function index()
    {
        if(Common::checkRouteWisePermission('resort.budget.manning',config('settings.resort_permissions.view')) == false)
        {
           return abort(403, 'Unauthorized access');
        }
        $page_title = 'Resort Configuration';
        $resort_id = Auth::guard('resort-admin')->user()->resort_id;
        $divisions = Division::where('status', 'active')->get();
        $resort_divisions = ResortDivision::where('status', 'active')->where('resort_id',$resort_id)->get();
        $resort_departments = ResortDepartment::where('status', 'active')->where('resort_id',$resort_id)->get();
        $departments = Department::where('status', 'active')->get();
        $sections = Section::where('status', 'active')->get();
        $positions = Position::where('status', 'active')->get();
        $resort_sections = ResortSection::where('status', 'active')->where('resort_id',$resort_id)->get();
        $resort_positions = ResortPosition::where('status', 'active')->where('resort_id',$resort_id)->get();


        return view('resorts.manning.index')->with(
            compact(
            'page_title',
            'divisions','departments','sections','positions',
            'resort_divisions','resort_departments','resort_sections','resort_positions'
            )
        );
    }

    public function get_divisions()
    {
        $edit_class = '';
        $delete_class = '';

        if (!Common::checkRouteWisePermission('resort.budget.manning', config('settings.resort_permissions.edit'))) {
            $edit_class = 'd-none';
        }

        if (!Common::checkRouteWisePermission('resort.budget.manning', config('settings.resort_permissions.delete'))) {
            $delete_class = 'd-none';
        }

        $resort_id = Auth::guard('resort-admin')->user()->resort_id;

        // Eloquent query without fixed ordering
        $query = ResortDivision::where('resort_id', $resort_id);

        return datatables()->of($query)
            ->addColumn('action', function ($row) use ($edit_class, $delete_class) {
                return '
                    <div class="d-flex align-items-center">
                        <a href="#" class="btn-lg-icon icon-bg-green me-1 edit-row-btn ' . $edit_class . '"
                        data-division-id="' . htmlspecialchars($row->id, ENT_QUOTES, 'UTF-8') . '">
                            <img src="' . asset('resorts_assets/images/edit.svg') . '" alt="" class="img-fluid" />
                        </a>
                        <a href="#" class="btn-lg-icon icon-bg-red delete-row-btn ' . $delete_class . '"
                        data-division-id="' . htmlspecialchars($row->id, ENT_QUOTES, 'UTF-8') . '">
                            <img src="' . asset('resorts_assets/images/trash-red.svg') . '" alt="" class="img-fluid" />
                        </a>
                    </div>';
            })
            ->editColumn('status', function ($row) {
                $statusClass = $row->status === "active" ? 'text-success' : 'text-danger';
                $statusLabel = ucfirst($row->status);
                return '<span class="' . $statusClass . '">' . $statusLabel . '</span>';
            })
            ->rawColumns(['action', 'status']) // Allow HTML rendering
            ->make(true);
    }

    public function store_divisions(Request $request)
    {
        if ($request->has('division_id')) {
            $request->merge(['name' => $request->division_id]);
        }

        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('resort_divisions')->where(fn($query) => $query->where('resort_id', $this->resort_id)),
            ],
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('resort_divisions')->where(fn($query) => $query->where('resort_id', $this->resort_id)),
            ],
            'short_name' => 'required|string|max:50',
            'status' => 'required|in:active,inactive',
        ], [
            'name.unique' => 'This division name already exists for the selected resort.',
            'code.unique' => 'This division code already exists for the selected resort.',
        ]);
        if ($validator->fails())
        {
            return response()->json(['success' => false,'errors' => $validator->errors()], 422);
        }

        try {

            ResortDivision::create([
                'resort_id' => $this->resort_id,
                'name' => $request->name,
                'code' => $request->code,
                'short_name' => $request->short_name,
                'status' => $request->status,
            ]);



            return response()->json(['success' => true, 'message' => 'Division added successfully.']);
        } catch (\Exception $e) {
            \Log::emergency("File: ".$e->getFile());
            \Log::emergency("Line: ".$e->getLine());
            \Log::emergency("Message: ".$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to add division.'], 500);
        }
    }

    public function inlineDivisionUpdate(Request $request, $id)
    {

        // Find the division by ID
        $division = ResortDivision::find($id);

        if (!$division) {
            return response()->json(['success' => false, 'message' => 'Division not found.']);
        }

        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('resort_divisions')
                    ->where(function ($query)  {
                        return $query->where('resort_id', $this->resort_id);
                    })
                    ->ignore($id), // Ignore the current record by ID
            ],
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('resort_divisions')
                    ->where(function ($query) {
                        return $query->where('resort_id', $this->resort_id);
                    })
                    ->ignore($id), // Ignore the current record by ID
            ],
            'short_name' => 'required|string|max:50',
            'status' => 'required|in:active,inactive',
        ], [
            'name.unique' => 'This division name already exists for the selected resort.',
            'code.unique' => 'This division code already exists for the selected resort.',
        ]);


        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        try {
            // Update the division's attributes
            $division->name = $request->input('name');
            $division->code = $request->input('code');
            $division->short_name = $request->input('short_name');
            $division->status = $request->input('status');

            // Save the changes
            $division->save();

            // Return a JSON response
            return response()->json(['success' => true, 'message' => 'Division updated successfully.']);
        } catch( \Exception $e ) {
            \Log::emergency( "File: ".$e->getFile() );
            \Log::emergency( "Line: ".$e->getLine() );
            \Log::emergency( "Message: ".$e->getMessage() );

            return response()->json(['success' => false, 'message' => 'Failed to update division.']);
        }
    }

    public function destroy_division($id)
    {
        try {
            // Find the division
            $division = ResortDivision::findOrFail($id);

            // Check if there are departments associated with this division
            $departments = ResortDepartment::where('division_id', $division->id)->get();

            // If there are any departments, return an error message
            if ($departments->count() > 0) {
                return response()->json(['success' => false, 'message' => 'Cannot delete division, departments are associated with it.']);
            }

            // If no departments are associated, delete the division
            $division->delete();  // Use forceDelete() if soft deletes are not used

            return response()->json(['success' => true, 'message' => 'Division deleted successfully.']);
        } catch (\Exception $e) {
            // Log the error and return failure response
            \Log::error("Error deleting division: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete division.']);
        }
    }

    public function get_departments(Request $request)
    {
        $resort_id = Auth::guard('resort-admin')->user()->resort_id;

        if ($request->ajax()) {
              $edit_class = '';
                $delete_class = '';
                if(Common::checkRouteWisePermission('resort.budget.manning',config('settings.resort_permissions.edit')) == false){
                    $edit_class = 'd-none';
                }
                if(Common::checkRouteWisePermission('resort.budget.manning',config('settings.resort_permissions.delete')) == false){
                    $delete_class = 'd-none';
                }

            $departments = ResortDepartment::select([
                'resort_departments.id',
                'resort_divisions.name as division',
                'resort_departments.code',
                'resort_departments.name',
                'resort_departments.short_name',
                'resort_departments.status',
                'resort_departments.created_by',
                'resort_departments.created_at',
                'resort_departments.updated_at'
            ])
            ->join('resort_divisions', 'resort_departments.division_id', '=', 'resort_divisions.id')
            ->where('resort_departments.resort_id',$resort_id)
            ->orderBy('resort_departments.created_at', 'DESC');

            return datatables()->of($departments)
                ->addColumn('action', function ($row) use ($edit_class, $delete_class) {

                    return '
                        <div class="d-flex align-items-center">
                            <a href="#" class="btn-lg-icon icon-bg-green me-1 edit-row-btn ' . $edit_class . '"
                            data-dept-id="' . htmlspecialchars($row->id, ENT_QUOTES, 'UTF-8') . '">
                                <img src="' . asset('resorts_assets/images/edit.svg') . '" alt="" class="img-fluid" />
                            </a>
                            <a href="#" class="btn-lg-icon icon-bg-red delete-row-btn ' . $delete_class . '"
                            data-dept-id="' . htmlspecialchars($row->id, ENT_QUOTES, 'UTF-8') . '">
                                <img src="' . asset('resorts_assets/images/trash-red.svg') . '" alt="" class="img-fluid" />
                            </a>
                        </div>';
                })
                ->editColumn('status', function ($row) {
                    $statusClass = $row->status === "active" ? 'text-success' : 'text-danger';
                    $statusLabel = ucfirst($row->status);
                    return '<span class="' . $statusClass . '">' . $statusLabel . '</span>';
                })
                ->rawColumns(['name', 'division','code', 'short_name', 'status', 'action'])
                ->make(true);
        }

        return view('resorts.manning.index');
    }

   

    public function store_departments(Request $request)
    {
        if ($request->has('dept_id')) {
            $request->merge(['name' => $request->dept_id]);
        }


        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('resort_departments')
                    ->where(function ($query) use ($request) {
                        return $query->where('resort_id', $this->resort_id)
                                     ->where('division_id', $request->division_id);
                    })
                    ->ignore($request->id), // Ignore current record ID if updating
            ],
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('resort_departments')
                    ->where(function ($query) use ($request) {
                        return $query->where('resort_id', $this->resort_id)
                                     ->where('division_id', $request->division_id);
                    })
                    ->ignore($request->id), // Ignore current record ID if updating
            ],
            'short_name' => 'required|string|max:50',
            'status' => 'required|in:active,inactive',
        ], [
            'name.unique' => 'This Department name already exists for the selected Division and resort.',
            'code.unique' => 'This Department code already exists for the selected Division and resort.',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        try {
                $department = new ResortDepartment();
                $department->resort_id = $this->resort_id;
                $department->division_id = $request->division_id;
                $department->name = $request->name;
                $department->code = $request->code;
                $department->short_name = $request->short_name;
                $department->status = $request->status;
                $department->save();


            return response()->json(['success' => true, 'message' => 'Department added successfully.']);

        } catch( \Exception $e ) {
            \Log::emergency( "File: ".$e->getFile() );
            \Log::emergency( "Line: ".$e->getLine() );
            \Log::emergency( "Message: ".$e->getMessage() );

            return response()->json(['success' => false, 'message' => 'Failed to add department.']);
        }
    }

    public function inlineDepartmentUpdate(Request $request, $id)
    {
        // Find the division by ID
        $dept = ResortDepartment::find($id);

        if (!$dept) {
            return response()->json(['success' => false, 'message' => 'Department not found.']);
        }
        if ($request->has('dept_id')) {
            $request->merge(['name' => $request->dept_id]);
        }

            $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('resort_departments')
                    ->ignore($id)
                    ->where(function ($query) use ($request) {
                        return $query->where('resort_id', $this->resort_id)
                                ->where('division_id', $request->division);
                    })
            ],
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('resort_departments')
                    ->ignore($id)
                    ->where(function ($query) use ($request) {
                        return $query->where('resort_id', $this->resort_id)
                                ->where('division_id', $request->division);
                    })
            ],
            'short_name' => 'required|string|max:50',
            'status' => 'required|in:active,inactive',
        ], [
            'name.unique' => 'This Department name already exists for the selected Division and resort.',
            'code.unique' => 'This Department code already exists for the selected Division and resort.',
        ]);
        // Usage example:
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Update the division's attributes
            $dept->division_id = $request->input('division');
            $dept->name = $request->input('name');
            $dept->code = $request->input('code');
            $dept->short_name = $request->input('short_name');
            $dept->status = $request->input('status');

            // Save the changes
            $dept->save();

            // Get the updated division name
            $divisionName = ResortDivision::find($request->input('division'))->name;

            // Return success response with division name
            return response()->json([
                'success' => true,
                'message' => 'Department updated successfully.',
                'divisionName' => $divisionName // Return division name
            ]);
        } catch( \Exception $e ) {
            \Log::emergency( "File: ".$e->getFile() );
            \Log::emergency( "Line: ".$e->getLine() );
            \Log::emergency( "Message: ".$e->getMessage() );

            return response()->json(['success' => false, 'message' => 'Failed to update department.']);
        }
    }

    public function destroy_department($id)
    {
        try {
            $dept = ResortDepartment::findOrFail($id);

            $hasPositions = ResortPosition::where('dept_id', $dept->id)->exists();

            if ($hasPositions) {
                return response()->json(['success' => false, 'message' => 'Cannot delete department, positions are associated with it.']);
            }

            $hasSections = ResortSection::where('dept_id', $dept->id)->exists();

            if ($hasSections) {
                return response()->json(['success' => false, 'message' => 'Cannot delete department, sections are associated with it.']);
            }

            $dept->delete(); 

            return response()->json(['success' => true, 'message' => 'Department deleted successfully.']);
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error("Error deleting department: " . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to delete department.']);
        }
    }


    public function get_sections(Request $request)
    {
        $resort_id = Auth::guard('resort-admin')->user()->resort_id;

        if ($request->ajax()) {

            $sections = ResortSection::select([
                'resort_sections.id',
                'resort_sections.dept_id',
                'resort_departments.name as department',
                'resort_sections.name',
                'resort_sections.code',
                'resort_sections.short_name',
                'resort_sections.status',
                'resort_sections.created_by',
                'resort_divisions.name as division',
                'resort_sections.created_at',
                'resort_sections.updated_at'
            ])
            ->join('resort_departments', 'resort_sections.dept_id', '=', 'resort_departments.id')
            ->join('resort_divisions', 'resort_divisions.id', '=', 'resort_departments.division_id')
            ->where('resort_sections.resort_id',$resort_id)
            ->orderBy('resort_sections.id', 'DESC');
            // ->get();

            $edit_class = '';
                    $delete_class = '';
                    if(Common::checkRouteWisePermission('resort.budget.manning',config('settings.resort_permissions.edit')) == false){
                        $edit_class = 'd-none';
                    }
                    if(Common::checkRouteWisePermission('resort.budget.manning',config('settings.resort_permissions.delete')) == false){
                        $delete_class = 'd-none';
                    }

            return datatables()->of($sections)
                ->addColumn('action', function ($row) use ($edit_class,$delete_class) {
                    
                    return '
                        <div class="d-flex align-items-center">
                            <a href="#" class="btn-lg-icon icon-bg-green me-1 edit-row-btn ' . $edit_class . '"
                            data-section-id="' . htmlspecialchars($row->id, ENT_QUOTES, 'UTF-8') . '">
                                <img src="' . asset('resorts_assets/images/edit.svg') . '" alt="" class="img-fluid" />
                            </a>
                            <a href="#" class="btn-lg-icon icon-bg-red delete-row-btn ' . $delete_class . '"
                            data-section-id="' . htmlspecialchars($row->id, ENT_QUOTES, 'UTF-8') . '">
                                <img src="' . asset('resorts_assets/images/trash-red.svg') . '" alt="" class="img-fluid" />
                            </a>
                        </div>';
                })
                ->editColumn('status', function ($row) {
                    $statusClass = $row->status === "active" ? 'text-success' : 'text-danger';
                    $statusLabel = ucfirst($row->status);
                    return '<span class="' . $statusClass . '">' . $statusLabel . '</span>';
                })
                ->rawColumns(['name', 'division','department','code', 'short_name', 'status', 'action'])
                ->make(true);
        }

        return view('resorts.manning.index');
    }

    public function store_sections(Request $request)
    {

        if ($request->has('section_id'))
        {
            $request->merge(['name' => $request->section_id]);
        }

        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('resort_sections')
                    ->where(function ($query) use ($request) {
                        return $query->where('resort_id', $this->resort_id)
                                       ->where('dept_id', $request->dept_id);
                    })
            ],
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('resort_sections')

                    ->where(function ($query) use ($request) {
                        return $query->where('resort_id', $this->resort_id)
                                ->where('dept_id', $request->dept_id);
                    })
            ],
            'short_name' => 'required|string|max:50',
            'status' => 'required|in:active,inactive',
        ], [
            'name.unique' => 'This Section  name already exists for the selected Division and resort.',
            'code.unique' => 'This Section  code already exists for the selected Division and resort.',
        ]);
        // Usage example:
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
        try {
            $resort_id = Auth::guard('resort-admin')->user()->resort_id;
            // No division selected, so create a new division
            $section = new ResortSection();
            $section->resort_id = $resort_id;
            $section->dept_id = $request->dept_id;
            $section->name = $request->name;
            $section->code = $request->code;
            $section->short_name = $request->short_name;
            $section->status = $request->status;
            $section->save();
            return response()->json(['success' => true, 'message' => 'Section added successfully.']);
        } catch( \Exception $e ) {
            \Log::emergency( "File: ".$e->getFile() );
            \Log::emergency( "Line: ".$e->getLine() );
            \Log::emergency( "Message: ".$e->getMessage() );
            return response()->json(['success' => false, 'message' => 'Failed to add section.']);
        }
    }

    public function inlinesectionsUpdate(Request $request)
    {


         $section = ResortSection::find($request->id);

        if (!$section) {
            return response()->json(['success' => false, 'message' => 'Section not found.']);
        }
        else
        {
                $existingDepartment = DB::table('resort_sections')
                    ->where('name', $request->name)
                    ->where('dept_id', $request->department)
                    ->where('resort_id', $this->resort_id)
                    ->where('id', '!=', $request->id)
                    ->first();
                if ($existingDepartment) {
                    return response()->json([
                        'errors' => [
                            'name' => ['This Section name already exists for the selected Department and resort.']
                        ]
                    ], 422);
                }

                $validator = Validator::make($request->all(), [
                    'name' => [
                        'required',
                        'string',
                        'max:255',
                        Rule::unique('resort_sections')
                            ->where(function ($query) use ($request) {
                                return $query->where('resort_id', $this->resort_id)
                                             ->where('dept_id', $request->department);
                            })
                            ->ignore($request->id), // Ignore the current record ID when updating
                    ],
                    'code' => [
                        'required',
                        'string',
                        'max:10',
                        Rule::unique('resort_sections')
                            ->where(function ($query) use ($request) {
                                return $query->where('resort_id', $this->resort_id)
                                             ->where('dept_id', $request->department);
                            })
                            ->ignore($request->id),
                    ],
                    'short_name' => 'required|string|max:50',
                    'status' => 'required|in:active,inactive',
                ], [
                    'name.unique' => 'This Section name already exists for the selected Department and Resort.',
                    'code.unique' => 'This Section code already exists for the selected Department and Resort.',
                ]);
                if ($validator->fails()) {
                    return response()->json([
                        'errors' => $validator->errors()
                    ], 422);
                }
                $section->dept_id = $request->input('department');
                $section->name = $request->input('name');
                $section->code = $request->input('code');
                $section->short_name = $request->input('short_name');
                $section->status = $request->input('status');

                // Save the changes
                $section->save();

                // Get the updated division name
                $divisionName = ResortDivision::find($request->input('division'))->name;
                $deptName = ResortDepartment::find($request->input('department'))->name;
                return response()->json([
                    'success' => true,
                    'message' => 'Section updated successfully.',
                    'divisionName' => $divisionName, // Return division name
                    'deptName' => $deptName // Return division name
                ]);
        }
    }

    public function destroy_sections($id)
    {
        try {
            $section = ResortSection::findOrFail($id);
            $section->delete();  // Soft delete if you're using soft deletes, otherwise use forceDelete()

            return response()->json(['success' => true, 'message' => 'Section deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete section.']);
        }
    }

    public function get_positions(Request $request)
    {
        $resort_id = Auth::guard('resort-admin')->user()->resort_id;

        if ($request->ajax()) {
            $positions = ResortPosition::select([
                    'resort_positions.id',
                    'resort_positions.dept_id',
                    'resort_departments.name as department',
                    'resort_sections.name as section',
                    'resort_positions.position_title',
                    DB::raw('COUNT(employees.id) as no_of_positions'), // Count employees for each position
                    'resort_positions.code',
                    'resort_positions.short_title',
                    'resort_positions.status',
                    'resort_positions.created_by',
                    'resort_positions.Rank',
                    'resort_divisions.name as division',
                    'resort_positions.created_at',
                    'resort_positions.updated_at'
                ])
                ->leftJoin('resort_departments', 'resort_positions.dept_id', '=', 'resort_departments.id')
                ->leftJoin('resort_sections', 'resort_positions.section_id', '=', 'resort_sections.id')
                ->leftJoin('resort_divisions', 'resort_divisions.id', '=', 'resort_departments.division_id')
                ->leftJoin('employees', function ($join) {
                    $join->on('employees.position_id', '=', 'resort_positions.id')
                        ->where('employees.resort_id', '=', Auth::guard('resort-admin')->user()->resort_id); // Ensure you're counting only employees from the same resort
                })
                ->where('resort_positions.resort_id', $resort_id)
                ->groupBy('resort_positions.id', 'resort_positions.dept_id', 'resort_positions.position_title', 'resort_positions.code', 'resort_positions.short_title', 'resort_positions.status', 'resort_positions.created_by', 'resort_divisions.name', 'resort_positions.created_at', 'resort_positions.updated_at')
                ->orderBy('resort_positions.id', 'DESC');
                // ->get();
                $edit_class = '';
                    $delete_class = '';
                    if(Common::checkRouteWisePermission('resort.budget.manning',config('settings.resort_permissions.edit')) == false){
                        $edit_class = 'd-none';
                    }
                    if(Common::checkRouteWisePermission('resort.budget.manning',config('settings.resort_permissions.delete')) == false){
                        $delete_class = 'd-none';
                    }

            return datatables()->of($positions)
                ->addColumn('action', function ($row) use ($edit_class,$delete_class) {
                    
                    return '
                        <div class="d-flex align-items-center">
                            <a href="#" class="btn-lg-icon icon-bg-green me-1 edit-row-btn ' . $edit_class . '"
                            data-position-id="' . htmlspecialchars($row->id, ENT_QUOTES, 'UTF-8') . '">
                                <img src="' . asset('resorts_assets/images/edit.svg') . '" alt="" class="img-fluid" />
                            </a>
                            <a href="#" class="btn-lg-icon icon-bg-red delete-row-btn ' . $delete_class . '"
                            data-position-id="' . htmlspecialchars($row->id, ENT_QUOTES, 'UTF-8') . '">
                                <img src="' . asset('resorts_assets/images/trash-red.svg') . '" alt="" class="img-fluid" />
                            </a>
                        </div>';
                })
                ->editColumn('Rank', function ($row) {

                    $ranks = config('settings.Position_Rank');
                    $rankName = isset($ranks[$row->Rank]) ? $ranks[$row->Rank] : 'Unknown Rank';
                    $rankId = $row->Rank;
                    return '<span class="primary"><input type="hidden" name="rankId" value="' . $rankId . '" >'. $rankName . '</span>';
                })
                ->editColumn('status', function ($row) {
                    $statusClass = $row->status === "active" ? 'text-success' : 'text-danger';
                    $statusLabel = ucfirst($row->status);
                    return '<span class="' . $statusClass . '">' . $statusLabel . '</span>';
                })
                ->rawColumns(['position_title', 'no_of_positions','division','department','section','code', 'short_title', 'Rank','status', 'action'])
                ->make(true);
        }

        return view('resorts.manning.index');
    }

    public function store_positions(Request $request)
    {
        if ($request->has('position_id'))
        {
            $request->merge(['position_title' => $request->position_id]);
        }

        $validator = Validator::make($request->all(), [
            'position_title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('resort_positions')
                    ->where(function ($query) use ($request) {
                        return $query->where('resort_id', $this->resort_id)
                                       ->where('dept_id', $request->dept_id);
                    })
            ],
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('resort_positions')

                    ->where(function ($query) use ($request) {
                        return $query->where('resort_id', $this->resort_id)
                                ->where('dept_id', $request->dept_id);
                    })
            ],
            'short_title' => 'required|string|max:50',
            'status' => 'required|in:active,inactive',
        ], [
            'name.unique' => 'This Position  name already exists for the selected Department and resort.',
            'code.unique' => 'This Position  code already exists for the selected Department and resort.',
        ]);
        // Usage example:
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        try
        {
            $position = new ResortPosition();
            $position->resort_id = $this->resort_id;
            $position->dept_id = $request->dept_id;
            $position->section_id = $request->section_id;
            $position->position_title = $request->position_title;
            $position->no_of_positions = $request->no_of_positions;
            $position->code = $request->code;
            $position->short_title = $request->short_title;
            $position->status = $request->status;
            $position->Rank = $request->Rank;
            $position->save();
            // Return success message
            return response()->json(['success' => true, 'message' => 'Position added successfully.']);
        } catch (\Exception $e) {
            // Log the detailed error message
            \Log::emergency("File: ".$e->getFile());
            \Log::emergency("Line: ".$e->getLine());
            \Log::emergency("Message: ".$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to add position.']);
        }
    }

    public function inlinePositionUpdate(Request $request, $id)
    {
        if ($request->has('short_name') || $request->has('position_title'))
        {
            $request->merge(['short_title' => $request->short_name]);
            $request->merge(['position_title' => $request->name]);
        }
        $position = ResortPosition::find($id);
        if (!$position) {
            return response()->json(['success' => false, 'message' => 'Position not found.']);
        }
        else
        {
            $existingDepartment = DB::table('resort_positions')
            ->where('position_title', $request->position_title)
            ->where('dept_id', $request->department)
            ->where('resort_id', $this->resort_id)
            ->where('id', '!=', $id)
            ->first();

            if ($existingDepartment) {
                return response()->json([
                    'errors' => [
                        'name' => ['This Positions name already exists for the selected Department and resort.']
                    ]
                ], 422);
            }

            $validator = Validator::make($request->all(), [
                'position_title' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('resort_positions')
                        ->where(function ($query) use ($request) {
                            return $query->where('resort_id', $this->resort_id)
                                           ->where('dept_id', $request->dept_id);
                        })
                ],
                'code' => [
                    'required',
                    'string',
                    'max:10',
                    Rule::unique('resort_positions')

                        ->where(function ($query) use ($request) {
                            return $query->where('resort_id', $this->resort_id)
                                    ->where('dept_id', $request->dept_id);
                        })
                ],
                'short_title' => 'required|string|max:50',
                'status' => 'required|in:active,inactive',
            ], [
                'name.unique' => 'This Position  name already exists for the selected Department and resort.',
                'code.unique' => 'This Position  code already exists for the selected Department and resort.',
            ]);
            // Usage example:
            if ($validator->fails()) {
                return response()->json([
                    'errors' => $validator->errors()
                ], 422);
            }

            try {
                // dd($request);
                // Update the division's attributes
                $position->dept_id = $request->input('department');
                $position->section_id = ($request->input('section') && $request->input('section') != '') ? $request->input('section') : null;
                $position->position_title = $request->input('name');
                $position->no_of_positions = $request->input('no_of_positions');
                $position->code = $request->input('code');
                $position->short_title = $request->input('short_name');
                $position->status = $request->input('status');
                $position->Rank = $request->input('Rank');
                $update = $position->save();
                // Get the updated division name
                $divisionName = ResortDivision::find($request->input('division'))->name;
                $deptName = ResortDepartment::find($request->input('department'))->name;
                $sectionName = $request->input('section') ? ResortSection::find($request->input('section'))->name : '';
                $Rank = config('settings.Position_Rank');

                // Return success response with division name
                return response()->json([
                    'success' => true,
                    'message' => 'Position updated successfully.',
                    'divisionName' => $divisionName, // Return division name
                    'deptName' => $deptName ,// Return division name
                    'sectionName' => $sectionName ,// Return sectin name
                    'Rank'=>array_key_exists($request->input('Rank'), $Rank) ? $Rank[$request->input('Rank')] : $request->input('Rank'),
                ]);
            } catch( \Exception $e ) {
                \Log::emergency( "File: ".$e->getFile() );
                \Log::emergency( "Line: ".$e->getLine() );
                \Log::emergency( "Message: ".$e->getMessage() );

                return response()->json(['success' => false, 'message' => 'Failed to update position.']);
            }

        }
        // Validate incoming request

    }

    public function destroy_position($id)
    {
        try {
            $position = ResortPosition::findOrFail($id);

            $associatedPermissions = ResortInteralPagesPermission::where('position_id', $position->id)->count();

            $associatedEmployees = Employee::where('position_id', $position->id)->count();

            if ($associatedPermissions > 0) {
                return response()->json(['success' => false, 'message' => 'Cannot delete position, permissions or modules are associated with it.']);
            }

            if ($associatedEmployees > 0) {
                return response()->json(['success' => false, 'message' => 'Cannot delete position, employees are associated with it.']);
            }

            $position->delete();  

            return response()->json(['success' => true, 'message' => 'Position deleted successfully.']);
        } catch (\Exception $e) {
            \Log::error("Failed to delete position with ID {$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete position.']);
        }
    }
}
