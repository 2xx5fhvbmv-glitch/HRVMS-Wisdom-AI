<?php

namespace App\Http\Controllers\Resorts\Incident;

use App\Http\Controllers\Controller;
use App\Events\ResortNotificationEvent;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\ResortAdmin;
use App\Models\IncidentCategory;
use App\Models\IncidentCommittee;
use App\Models\IncidentSubCategory;
use App\Models\IncidentConfiguration;
use App\Models\IncidentActionTaken;
use App\Models\IncidentOutcomeType;
use App\Models\IncidentFollowupActions;
use App\Models\IncidentCommitteeMember;
use App\Models\IncidentResolutionTimeline;
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
        $this->reporting_to = isset($this->resort->GetEmployee) ? $this->resort->GetEmployee->id:0;
        $this->underEmp_id = Common::getSubordinates($this->reporting_to);
    }
    
    public function index()
    {
        $page_title ='Configuration';
        $resort_id = $this->resort->resort_id;
        $categories = IncidentCategory::where('resort_id',$resort_id)->get();
        $CommitteeMembers = Employee::join('resort_admins as t1',"t1.id","=","employees.Admin_Parent_id")
            ->where("t1.resort_id",$this->resort->resort_id)
            ->whereIn('employees.rank',[1,2,3,8])
            ->get(['t1.first_name','t1.last_name','t1.profile_picture','employees.*']);
        $resoltion_timeline = IncidentResolutionTimeline::where('resort_id',$resort_id)->pluck('timeline', 'priority');
        $meeting_reminder = IncidentConfiguration::where('resort_id', $resort_id)
        ->where('setting_key', 'meeting_reminder')
        ->first();
        $severity_levels = IncidentConfiguration::where('resort_id', $resort_id)
        ->where('setting_key', 'severity_levels')
        ->first();
        $status = IncidentConfiguration::where('resort_id', $resort_id)
        ->where('setting_key', 'status')
        ->first();
        // dd($meeting_reminder);
        return view('resorts.incident.config.index',compact('page_title','categories','CommitteeMembers','resoltion_timeline','meeting_reminder','severity_levels','status'));
    }

    public function viewCategories()
    {
        if(Common::checkRouteWisePermission('incident.index',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }
        $page_title ='Categories';
        $resort_id= $this->resort->resort_id;
        return view('resorts.incident.config.categories',compact('page_title'));
    }

    public function categoriesList(Request $request)
    {
        if($request->ajax())
        {
            $incident_categories= IncidentCategory::where('resort_id',$this->resort->resort_id)->get();
            // dd($incident_categories);
            $edit_class = '';
            $delete_class = '';
                if(Common::checkRouteWisePermission('incident.index',config('settings.resort_permissions.edit')) == false){
                    $edit_class = 'd-none';
                }
                if(Common::checkRouteWisePermission('incident.index',config('settings.resort_permissions.delete')) == false){
                    $delete_class = 'd-none';
                }
            return datatables()->of($incident_categories)
            ->addColumn('action', function ($row)  use ($edit_class, $delete_class){
                $id = base64_encode($row->id);
                return '
                    <div  class="d-flex align-items-center">
                        <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn ' . $edit_class . '" data-cat-id="' . e($id) . '">
                            <img src="' . asset("resorts_assets/images/edit.svg") . '" alt="Edit" class="img-fluid">
                        </a>
                        <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn ' . $delete_class . '" data-cat-id="' . e($id) . '">
                            <img src="' . asset("resorts_assets/images/trash-red.svg") . '" alt="Delete" class="img-fluid">
                        </a>
                    </div>';
            })
            ->rawColumns(['action'])
            ->make(true);
        }
    }

    public function storeCategories(Request $request)
    {
        $request->validate([
            'category_name.*' => 'required|string|max:255',
        ]);
        foreach ($request->category_name as $category) {
            $checkIndicentCategory = IncidentCategory::where('resort_id', $this->resort->resort_id)->where('category_name', $category)->first();
            
            if (!empty($category) && !$checkIndicentCategory) {
                IncidentCategory::create([
                    'resort_id' => $this->resort->resort_id, // Ensure the user has a resort_id
                    'category_name' => $category,
                ]);
            }else{
                return response()->json([
                    'success'=> false,
                    'message' => 'Category Name already exists for this resort. Please try again with a different name.',
                ]);
               ; 
            }
        }
        
        return response()->json([
            'success' =>true,
            'message' => 'Incident Categories Added Successfully',
            'redirect_url' => route('incident.categories.view')
        ]);

    }

    public function categoryInlineUpdate(Request $request,$id)
    {
        $Main_id = (int) base64_decode($request->Main_id);

        $resort_id = $this->resort->resort_id;
        $validator = Validator::make($request->all(), [
            'category_name' => [
                'required',
                'max:50',
                Rule::unique('incident_categories')->where(function ($query) use ($resort_id,$Main_id) {
                    return $query->where('resort_id', $resort_id);
                })->ignore( $Main_id),
            ],
        ], [
            'category_name.required' => 'The Category Name field is required. Please write something.',
            'category_name.unique' => 'The Category Name already exists for this resort.',
            'category_name.max' => 'The maximum allowed length for the Category Name is 50 characters.',
        ]);

        if($validator->fails())
        {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try
        {
            IncidentCategory::where('resort_id', $this->resort->resort_id)
            ->where('id', $Main_id)
            ->update([
                'category_name' => $request->category_name,
            ]);
            
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Category Updated Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Updated  Category'], 500);
        }
    }

    public function categoryDestory($id)
    {
        $id = base64_decode($id);
        DB::beginTransaction();
        try
        {
            IncidentSubCategory::where("category_id",$id)->delete();
            IncidentCategory::where("id",$id)->delete();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Category Delete Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Delete  Category'], 500);
        }
    }

    public function storeSubCategories(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:incident_categories,id',
            'subcategory_name' => 'required|array|min:1',
            'subcategory_name.*' => 'required|string|max:100|distinct',
            'priority' => 'required|array|min:1',
            'priority.*' => 'required|in:Low,Medium,High',
        ], [
            'category_id.required' => 'Please select a category.',
            'subcategory_name.required' => 'At least one subcategory is required.',
            'subcategory_name.*.distinct' => 'Duplicate subcategories are not allowed.',
            'priority.*.required' => 'Each subcategory must have a priority.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        // dd($request->all());
        try {
            foreach ($request->subcategory_name as $index => $subcategoryName) {
                IncidentSubCategory::create([
                    'resort_id' => $this->resort->resort_id, // Adjust if needed
                    'category_id' => $request->category_id,
                    'subcategory_name' => $subcategoryName,
                    'priority' => $request->priority[$index],
                ]);
            }

            DB::commit();
            return response()->json(
                [
                    'success' => true, 
                    'message' => 'Subcategories added successfully!',
                    'redirect_url'=> route('incident.subcategories.view')
                ]
            );
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error adding subcategories: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Something went wrong. Please try again.'], 500);
        }
    }

    public function viewSubCategories()
    {
        if(Common::checkRouteWisePermission('incident.index',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }
        $page_title ='Sub-Categories';
        $resort_id= $this->resort->resort_id;
        $categories = IncidentCategory::where('resort_id',$resort_id)->get();
        return view('resorts.incident.config.subcategories',compact('page_title','categories'));
    }

    public function subcategoriesList(Request $request) 
    {
        if ($request->ajax()) {
            
            $query = IncidentSubCategory::with('category') // Load category details
                ->where('resort_id', $this->resort->resort_id);
            
            if ($request->category) {
                $query->where('category_id', $request->category);
            }

            if ($request->priority) {
                $query->where('priority', $request->priority);
            }
                
            $incident_subcategories = $query->get();
            $edit_class = '';
            $delete_class = '';
                if(Common::checkRouteWisePermission('incident.index',config('settings.resort_permissions.edit')) == false){
                    $edit_class = 'd-none';
                }
                if(Common::checkRouteWisePermission('incident.index',config('settings.resort_permissions.delete')) == false){
                    $delete_class = 'd-none';
                }
            return datatables()->of($incident_subcategories)
                ->addColumn('category_name', function ($row) {
                    return $row->category ? $row->category->category_name : '-'; // Prevent null errors
                })
                ->addColumn('action', function ($row) use ($edit_class, $delete_class) {
                    $id = base64_encode($row->id);
                    return '
                        <div class="d-flex align-items-center">
                            <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn ' . $edit_class . '" data-cat-id="' . e($id) . '">
                                <img src="' . asset("resorts_assets/images/edit.svg") . '" alt="Edit" class="img-fluid">
                            </a>
                            <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn ' . $delete_class . '" data-cat-id="' . e($id) . '">
                                <img src="' . asset("resorts_assets/images/trash-red.svg") . '" alt="Delete" class="img-fluid">
                            </a>
                        </div>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }

    public function subcategoryinlineUpdate(Request $request, $id)
    {
        $Main_id = (int) base64_decode($request->Main_id);

        $resort_id = $this->resort->resort_id;
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|max:50', // ✅ Removed unique rule
            'subcategory_name' => 'required|string|max:100', // Add validation for subcategory name
            'priority' => 'required|string', // Ensure priority is optional but numeric
        ], [
            'category_id.required' => 'The Category field is required. Please select a category.',
            'category_id.max' => 'The maximum allowed length for the Category is 50 characters.',
            'subcategory_name.required' => 'The Subcategory Name field is required.',
            'subcategory_name.max' => 'The maximum allowed length for the Subcategory Name is 100 characters.',
            'priority' => 'The Priority field is required.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            IncidentSubCategory::where('resort_id', $this->resort->resort_id)
                ->where('id', $Main_id)
                ->update([
                    'category_id' => $request->category_id,
                    'subcategory_name' => $request->subcategory_name,
                    'priority' => $request->priority, // ✅ FIXED: Now updating priority
                ]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Category Updated Successfully',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error updating category: " . $e->getMessage());
            return response()->json(['error' => 'Failed to update category'], 500);
        }
    }

    public function subcategoryDestory($id)
    {
        $id = base64_decode($id);
        DB::beginTransaction();
        try
        {
            IncidentSubCategory::where("id",$id)->delete();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'SubCategory Delete Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Delete SubCategory'], 500);
        }
    }

    public function storeCommittees(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'CommitteeName'  => [
                'required',
                'array', // Ensures it is an array
            ],
            'CommitteeName.*' => [
                'required',
                'string',
                Rule::unique('incident_committee', 'commitee_name')->where(function ($query) {
                    return $query->where('resort_id', $this->resort->resort_id);
                })
            ],
            'members'   => ['required', 'array', 'min:1'], // Ensure members is an array
            'members.*' => ['required', 'array'], // Each index must contain an array
            'members.*.*' => ['required', 'integer', 'exists:employees,id'], // Each member ID must be valid
        ], [
            'CommitteeName.required' => 'Committee Name is required.',
            'CommitteeName.*.required' => 'Each Committee Name is required.',
            'CommitteeName.*.unique' => 'The Committee Name already exists for this resort.',
            'members.required' => 'At least one committee must have members.',
            'members.*.required' => 'Each committee must have at least one member.',
            'members.*.*.required' => 'Each member must be provided.',
            'members.*.*.integer' => 'Each member must be a valid ID.',
            'members.*.*.exists' => 'Invalid member selected.',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
    
        DB::beginTransaction();
        try {
            foreach ($request->CommitteeName as $index => $name) {
                $committee = IncidentCommittee::create([
                    'resort_id' => $this->resort->resort_id,
                    'commitee_name' => $name,
                    'date' => now(),
                ]);

                // ✅ Ensure members exist and are properly structured
                if (!empty($request->members[$index]) && is_array($request->members[$index])) {
                    foreach ($request->members[$index] as $memberId) {
                        IncidentCommitteeMember::create([
                            'commitee_id' => $committee->id,
                            'member_id' => $memberId,
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json(
                [
                    'success' => true, 
                    'message' => 'Committees saved successfully!',
                    'redirect_url'=> route('incident.committees.view')
                ]
            );
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error saving committees: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error saving committees.'], 500);
        }
    }

    public function viewCommittees()
    {
        $page_title ='Committee';
        $resort_id= $this->resort->resort_id;
        $CommitteeMembers = Employee::join('resort_admins as t1',"t1.id","=","employees.Admin_Parent_id")
        ->where("t1.resort_id",$this->resort->resort_id)
        ->whereIn('employees.rank',[1,2,3,8])
        ->get(['t1.first_name','t1.last_name','t1.profile_picture','employees.*']);
        return view('resorts.incident.config.committee',compact('page_title','CommitteeMembers'));
    }

    public function committeeList(Request $request)
    {
        if($request->ajax())
        {
            $committees = IncidentCommittee::with('members.employee.resortAdmin') // Load members
            ->where('resort_id', $this->resort->resort_id)
            ->select('id', 'commitee_name','created_at'); // Select only necessary fields

            return datatables()->of($committees)
                ->addColumn('member', function ($committee) {
                    // Display all member names in a comma-separated format
                    return $committee->members->map(function ($member) {
                        return $member->employee->resortAdmin->first_name . ' ' . $member->employee->resortAdmin->last_name;
                    })->implode(', ');
                })
                ->addColumn('action', function ($committee) {
                    $id = base64_encode($committee->id);
                    return '
                        <div class="d-flex align-items-center">
                            <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" data-cat-id="' . e($id) . '">
                                <img src="' . asset("resorts_assets/images/edit.svg") . '" alt="Edit" class="img-fluid">
                            </a>
                            <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-cat-id="' . e($id) . '">
                                <img src="' . asset("resorts_assets/images/trash-red.svg") . '" alt="Delete" class="img-fluid">
                            </a>
                        </div>';
                })
                ->rawColumns(['action']) // Ensure buttons are rendered as HTML
                ->make(true);
        }
    }

    public function committeeDestory($id)
    {
        $id = base64_decode($id);
        DB::beginTransaction();
        try
        {
            IncidentCommitteeMember::where("commitee_id",$id)->delete();
            IncidentCommittee::where("id",$id)->delete();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Committee Delete Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Delete Committee'], 500);
        }
    }

    public function committeeInlineUpdate(Request $request, $id) 
    {
        $id = base64_decode($id); // Decode the ID

        // Validate input
        $validator = Validator::make($request->all(), [
            'committee_name' => [
                'required',
                'string',
                'max:50',
                function ($attribute, $value, $fail) use ($id) {
                    $exists = IncidentCommittee::where('resort_id', auth()->user()->resort_id)
                        ->where('commitee_name', $value)
                        ->where('id', '!=', $id) // Exclude the current record
                        ->exists();

                    if ($exists) {
                        $fail("The committee name '$value' already exists.");
                    }
                }
            ],
            'committee_members' => 'nullable|array', // Members should be an array
            'committee_members.*' => 'exists:employees,id', // Validate each member exists in the employees table
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Update Committee Name
            $committee = IncidentCommittee::findOrFail($id);
            $committee->update(['commitee_name' => $request->committee_name]);

            // Update Committee Members
            if (!empty($request->committee_members)) {
                // Remove old members and insert new ones
                IncidentCommitteeMember::where('commitee_id', $id)->delete();

                foreach ($request->committee_members as $member_id) {
                    IncidentCommitteeMember::create([
                        'commitee_id' => $id,
                        'member_id' => $member_id
                    ]);
                }
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Incident Committee updated successfully!',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error updating Incident Committee: " . $e->getMessage());
            return response()->json(['error' => 'Failed to update committee'], 500);
        }
    }

    public function storeResolutionTimeline(Request $request) {
        $request->validate([
            'high_priority' => 'required|string|max:255',
            'medium_priority' => 'required|string|max:255',
            'low_priority' => 'required|string|max:255',
        ]);

        $resortId = $this->resort->resort_id; // Get logged-in user's resort_id

        $priorities = [
            'High' => $request->high_priority,
            'Medium' => $request->medium_priority,
            'Low' => $request->low_priority,
        ];

        foreach ($priorities as $priority => $timeline) {
            IncidentResolutionTimeline::updateOrCreate(
                ['resort_id' => $resortId, 'priority' => $priority],
                ['timeline' => $timeline]
            );
        }

        return redirect()->back()->with('success', 'Resolution timelines updated successfully.');
    }

    public function storeMeetingReminder(Request $request)
    {
        $request->validate([
            'reminder' => 'required|string',
        ]);

        IncidentConfiguration::saveSetting($this->resort->resort_id, 'meeting_reminder', ['reminder_days' => $request->reminder]);

        return response()->json(['success' => true, 'message' => 'Meeting Reminder saved successfully.']);
    }

    public function saveSeverityLevels(Request $request)
    {
        $request->validate([
            'severity' => 'required|array',
            'severity.*' => 'string'
        ]);

        $resort_id = $this->resort->resort_id; // Adjust based on your auth structure

        // Convert the array into a comma-separated string
        $severityLevels = implode(',', $request->severity);

        // Save or update the severity levels for the resort
        IncidentConfiguration::updateOrCreate(
            ['resort_id' => $resort_id, 'setting_key' => 'severity_levels'],
            ['setting_value' => $severityLevels]
        );

        return response()->json(['success' => true, 'message' => 'Severity levels updated successfully.']);
    }

    public function saveStatus(Request $request)
    {
        $request->validate([
            'status' => 'required|array',
            'status.*' => 'string'
        ]);

        $resort_id = $this->resort->resort_id; // Adjust based on your auth structure

        // Convert the array into a comma-separated string
        $status = implode(',', $request->status);

        // Save or update the severity levels for the resort
        IncidentConfiguration::updateOrCreate(
            ['resort_id' => $resort_id, 'setting_key' => 'status'],
            ['setting_value' => $status]
        );

        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }

    public function storeFolloupActions(Request $request)
    {
        $resort_id = $this->resort->resort_id ?? null;
    
        // Validate followup actions
        $validator = Validator::make($request->all(), [
            'followup_actions' => 'required|array|min:1',
            'followup_actions.*' => 'required|string|max:255',
        ], [
            'followup_actions.*.required' => 'Follow-up Action is required.',
            'followup_actions.*.max' => 'Max 255 characters allowed.',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }
    
        $actions = $request->input('followup_actions');
        $checkedIndex = $request->input('requires_employee_statement'); // This is a single value like "1", "2", etc.
    
        // Reset any existing "requires_employee_statement" flag
        if ($checkedIndex !== null) {
            IncidentFollowupActions::where('resort_id', $resort_id)
                ->where('requires_employee_statement', 1)
                ->update(['requires_employee_statement' => 0]);
        }
    
        // Save actions
        foreach ($actions as $index => $action) {
            $alreadyExists = IncidentFollowupActions::where('resort_id', $resort_id)
                ->where('followup_action', $action)
                ->exists();
    
            if (!$alreadyExists) {
                IncidentFollowupActions::create([
                    'followup_action' => $action,
                    'requires_employee_statement' => ((string)$index === (string)$checkedIndex) ? 1 : 0,
                    'resort_id' => $resort_id,
                ]);
            }
        }
    
        return response()->json([
            'message' => 'Follow-up actions saved successfully.',
            'redirect_url' => route('incident.followup-actions.view'),
        ]);
    }
    

    public function viewFolloupActions()
    {
        $page_title ='Followup Actions';
        $resort_id= $this->resort->resort_id;
        return view('resorts.incident.config.followupactions',compact('page_title'));
    }

    public function FolloupActionsList(Request $request) 
    {
        if ($request->ajax()) {
            $query = IncidentFollowupActions::where('resort_id', $this->resort->resort_id);
                            
            $incident_folloupactions = $query->get();
            // dd($incident_subcategories);
    
            return datatables()->of($incident_folloupactions)
                ->addColumn('action', function ($row) {
                    $id = base64_encode($row->id);
                    return '
                        <div class="d-flex align-items-center">
                            <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" data-cat-id="' . e($id) . '">
                                <img src="' . asset("resorts_assets/images/edit.svg") . '" alt="Edit" class="img-fluid">
                            </a>
                            <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-cat-id="' . e($id) . '">
                                <img src="' . asset("resorts_assets/images/trash-red.svg") . '" alt="Delete" class="img-fluid">
                            </a>
                        </div>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }

    public function folloupActionsinlineUpdate(Request $request, $id)
    {
        $Main_id = (int) base64_decode($request->Main_id);

        $resort_id = $this->resort->resort_id;
        $validator = Validator::make($request->all(), [
            'followup_action' => 'required|string|max:100', // Add validation for subcategory name
        ], [
            'followup_action.required' => 'The Followup actions Name field is required.',
            'followup_action.max' => 'The maximum allowed length for the Followup actions Name is 100 characters.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            IncidentFollowupActions::where('resort_id', $this->resort->resort_id)
                ->where('id', $Main_id)
                ->update([
                    'followup_action' => $request->followup_action,
                ]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Followup actions Updated Successfully',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error updating category: " . $e->getMessage());
            return response()->json(['error' => 'Failed to update Followup actions'], 500);
        }
    }

    public function folloupActionsDestory($id)
    {
        $id = base64_decode($id);
        DB::beginTransaction();
        try
        {
            IncidentFollowupActions::where("id",$id)->delete();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Follow-up action  Delete Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Delete Follow-up action'], 500);
        }
    }

    public function storeActionTaken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action_taken' => 'required|array|min:1',
            'action_taken.*' => 'required|string|max:100|distinct',
        ], [
            
            'action_taken.required' => 'At least one action taken is required.',
            'action_taken.*.distinct' => 'Duplicate action taken are not allowed.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        // dd($request->all());
        try {
            foreach ($request->action_taken as $index => $actionName) {
                IncidentActionTaken::create([
                    'resort_id' => $this->resort->resort_id, // Adjust if needed
                    'action_taken' => $actionName,
                ]);
            }

            DB::commit();
            return response()->json(
                [
                    'success' => true, 
                    'message' => 'Action taken added successfully!',
                    'redirect_url'=> route('incident.action-taken.view')
                ]
            );
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error adding Action taken: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Something went wrong. Please try again.'], 500);
        }
    }

    public function viewActionTaken()
    {
        $page_title ='Action Taken';
        $resort_id= $this->resort->resort_id;
        return view('resorts.incident.config.actiontaken',compact('page_title'));
    }

    public function ActionTakenList(Request $request) 
    {
        if ($request->ajax()) {
            $query = IncidentActionTaken::where('resort_id', $this->resort->resort_id);
                            
            $incident_actiontaken = $query->get();
            // dd($incident_subcategories);
    
            return datatables()->of($incident_actiontaken)
                ->addColumn('action', function ($row) {
                    $id = base64_encode($row->id);
                    return '
                        <div class="d-flex align-items-center">
                            <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" data-cat-id="' . e($id) . '">
                                <img src="' . asset("resorts_assets/images/edit.svg") . '" alt="Edit" class="img-fluid">
                            </a>
                            <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-cat-id="' . e($id) . '">
                                <img src="' . asset("resorts_assets/images/trash-red.svg") . '" alt="Delete" class="img-fluid">
                            </a>
                        </div>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }

    public function actionTakeninlineUpdate(Request $request, $id)
    {
        $Main_id = (int) base64_decode($request->Main_id);

        $resort_id = $this->resort->resort_id;
        $validator = Validator::make($request->all(), [
            'action_taken' => 'required|string|max:100', // Add validation for subcategory name
        ], [
            'action_taken.required' => 'The Action Taken field is required.',
            'action_taken.max' => 'The maximum allowed length for the Action Taken is 100 characters.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            IncidentActionTaken::where('resort_id', $this->resort->resort_id)
                ->where('id', $Main_id)
                ->update([
                    'action_taken' => $request->action_taken,
                ]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Action Taken Updated Successfully',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error updating category: " . $e->getMessage());
            return response()->json(['error' => 'Failed to update Action Taken'], 500);
        }
    }

    public function actionTakenDestory($id)
    {
        $id = base64_decode($id);
        DB::beginTransaction();
        try
        {
            IncidentActionTaken::where("id",$id)->delete();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Action Taken Delete Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Delete Action Taken'], 500);
        }
    }

    public function storeOutcomeType(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'outcome_type' => 'required|array|min:1',
            'outcome_type.*' => 'required|string|max:100|distinct',
        ], [
            
            'outcome_type.required' => 'At least one outcome type is required.',
            'outcome_type.*.distinct' => 'Duplicate outcome type are not allowed.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        // dd($request->all());
        try {
            foreach ($request->outcome_type as $index => $outcome_type) {
                IncidentOutcomeType::create([
                    'resort_id' => $this->resort->resort_id, // Adjust if needed
                    'outcome_type' => $outcome_type,
                ]);
            }

            DB::commit();
            return response()->json(
                [
                    'success' => true, 
                    'message' => 'Outcome Type added successfully!',
                    'redirect_url'=> route('incident.outcome-type.view')
                ]
            );
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error adding Outcome Type: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Something went wrong. Please try again.'], 500);
        }
    }

    public function viewOutcomeType()
    {
        $page_title ='Outcome Type';
        $resort_id= $this->resort->resort_id;
        return view('resorts.incident.config.outcometype',compact('page_title'));
    }

    public function outcomeTypeList(Request $request) 
    {
        if ($request->ajax()) {
            $query = IncidentOutcomeType::where('resort_id', $this->resort->resort_id);
                            
            $incident_outcometype = $query->get();
            // dd($incident_subcategories);
    
            return datatables()->of($incident_outcometype)
                ->addColumn('action', function ($row) {
                    $id = base64_encode($row->id);
                    return '
                        <div class="d-flex align-items-center">
                            <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" data-cat-id="' . e($id) . '">
                                <img src="' . asset("resorts_assets/images/edit.svg") . '" alt="Edit" class="img-fluid">
                            </a>
                            <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-cat-id="' . e($id) . '">
                                <img src="' . asset("resorts_assets/images/trash-red.svg") . '" alt="Delete" class="img-fluid">
                            </a>
                        </div>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }

    public function outcomeTypeinlineUpdate(Request $request, $id)
    {
        $Main_id = (int) base64_decode($request->Main_id);

        $resort_id = $this->resort->resort_id;
        $validator = Validator::make($request->all(), [
            'outcome_type' => 'required|string|max:100', // Add validation for subcategory name
        ], [
            'outcome_type.required' => 'The Outcome type field is required.',
            'outcome_type.max' => 'The maximum allowed length for the Outcome type is 100 characters.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            IncidentOutcomeType::where('resort_id', $this->resort->resort_id)
                ->where('id', $Main_id)
                ->update([
                    'outcome_type' => $request->outcome_type,
                ]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Outcome Type Updated Successfully',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error updating Outcome Type: " . $e->getMessage());
            return response()->json(['error' => 'Failed to update Outcome Type'], 500);
        }
    }

    public function outcomeTypeDestory($id)
    {
        $id = base64_decode($id);
        DB::beginTransaction();
        try
        {
            IncidentOutcomeType::where("id",$id)->delete();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Outcome Type Delete Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Delete Outcome Type'], 500);
        }
    }

}