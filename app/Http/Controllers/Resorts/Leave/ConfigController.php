<?php

namespace App\Http\Controllers\Resorts\Leave;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\EmployeeLeaveExport;
use App\Imports\ImportLeaves;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\LeaveCategory;
use App\Models\TicketAgent;
use App\Models\ResortDivision;
use App\Models\ResortTransportation;
use App\Models\ResortBenifitGridChild;
use App\Jobs\ImportLeavesJob;
use Auth;
use Config;
use DB;

class ConfigController extends Controller
{
    public $resort;
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;
    }

    public function index()
    {
        $page_title ='Leave Configuration';
        $resort_id = $this->resort->resort_id;
        $transportations = Config::get('settings.Transportations');
        $savedOptions = ResortTransportation::where('resort_id', $resort_id)
        ->pluck('transportation_option')
        ->toArray();
        $excludedLeaveTypes = ['Absent', 'Present'];
        $resort_divisions = ResortDivision::where('status', 'active')->where('resort_id',$resort_id)->get();
        $LeaveCategories = LeaveCategory::where('resort_id',$resort_id)
        ->whereNotIn('leave_type', $excludedLeaveTypes)->get();
        $eligibilty = config('settings.eligibilty');
        $TicketAgent = TicketAgent::where('resort_id', $this->resort->resort_id)->orderBy('id', 'desc')->pluck('agents_email');
        return view('resorts.leaves.config.index',compact('page_title','LeaveCategories','eligibilty','TicketAgent','transportations','savedOptions','resort_divisions'));
    }

    public function store_leaves_category(Request $request)
    {
        // Validate input fields with conditional rules
        $resort_id = $this->resort->resort_id;
        $validatedData = $request->validate([
            'leave_type' => 'required|string|max:255',
            'number_of_days' => 'required|integer|min:1',
            'carry_forward' => 'required|in:1,0',
            'carry_max' => 'required_if:carry_forward,1|nullable|integer|min:0',
            'earned_leave' => 'required|in:1,0',
            'earned_max' => 'required_if:earned_leave,1|nullable|integer|min:0',
            'eligibility' => 'required|array',
            'frequency' => 'required|in:Weekly,Monthly,Quarterly,Yearly',
            'number_of_times' => 'nullable|integer|min:1',
            'color' => 'nullable|string|max:7',
            'combine_with_other' => 'nullable|in:1,0',
            'leave_category' => 'required_if:combine_with_other,1|nullable|string|max:255',
        ]);
        // dd($request->all());
        try {
            $validatedData['resort_id'] = $resort_id;
            $validatedData['eligibility'] = implode(',', $request->eligibility); // Convert array to comma-separated string
            
            // Store data in the database
            LeaveCategory::create($validatedData);
        
            // Fetch updated leave categories for this resort
            $savedLeaveCategory = LeaveCategory::where('resort_id', $resort_id)->get();
        
            return response()->json([
                'success' => true,
                'message' => 'Leave category added successfully!',
                'leaveCategoriesHtml' => view('resorts.renderfiles.leave_categories', ['LeaveCategories' => $savedLeaveCategory])->render()
            ]);
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Leave Category Store Error: ' . $e->getMessage());
        
            return response()->json([
                'success' => false,
                'message' => 'Failed to save data. Please try again later.'
            ], 500);
        }
        
    }

    public function update_leaves_category(Request $request, $id)
    {
        // dd($request);
        $validatedData = $request->validate([
            'leave_type' => 'required|string|max:255',
            'number_of_days' => 'required|integer|min:1',
            'carry_forward' => 'required|in:1,0',
            'carry_max' => 'required_if:carry_forward,1|nullable|integer|min:0',
            'earned_leave' => 'required|in:1,0',
            'earned_max' => 'required_if:earned_leave,1|nullable|integer|min:0',
            'eligibility' => 'required|array',
            'frequency' => 'required|in:Weekly,Monthly,Quarterly,Yearly',
            'number_of_times' => 'nullable|integer|min:1',
            'color' => 'nullable|string|max:7',
            'combine_with_other' => 'required|in:1,0',
            'leave_category' => 'nullable|string|max:255',
        ]);
        $validatedData['eligibility'] = implode(',', $request->eligibility); // Convert array to comma-separated string

        $leaveCategory = LeaveCategory::findOrFail($id);
        $leaveCategory->update($validatedData);
        return response()->json(['success' => true,'message' => 'Leave category updated successfully!']);
    }

    public function delete_leaves_category($id)
    {
        DB::beginTransaction();
        try {
            $resort_id = $this->resort->resort_id;
    
            // Check if the leave category is in use
            if (ResortBenifitGridChild::where('leave_cat_id', $id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete leave category because it is in use.'
                ], 400);
            }
    
            // Check if the leave category exists
            $leaveCategory = LeaveCategory::find($id);
            if (!$leaveCategory) {
                return response()->json([
                    'success' => false,
                    'message' => 'Leave category not found.'
                ], 404);
            }
    
            // Delete the leave category
            $leaveCategory->delete();
            DB::commit();
            $savedLeaveCategory = LeaveCategory::where('resort_id', $resort_id)->get();

            return response()->json([
                'success' => true,
                'message' => 'Leave category deleted successfully!',
                'leaveCategoriesHtml' => view('resorts.renderfiles.leave_categories', ['LeaveCategories' => $savedLeaveCategory])->render()
            ]);
        }
        catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
    
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Leave Category: ' . $e->getMessage(),
            ], 500);
        }
    } 

    public function getEligibleLeaves(Request $request)
    {
        $empGrade = $request->input('emp_grade'); // Get the selected employee grade

        // Query the leave categories that include the given employee grade
        $leaves = LeaveCategory::whereRaw("FIND_IN_SET(?, eligibility)", [$empGrade])->where('resort_id',$this->resort->resort_id)->get();

        return response()->json([
            'success' => true,
            'data' => $leaves,
            'isViewMode' => false, // Add this if you need to check for view mode dynamically
        ]);
    }

    public function submitTransportationOptions(Request $request)
    {
        $resort_id = $this->resort->resort_id;
        $transportationOptions = $request->input('transportation_options');

        // Normalize to array (unchecked = not sent, so we get null; allow empty array to clear all)
        $selected = is_array($transportationOptions) ? array_values(array_filter($transportationOptions)) : [];

        // Remove any saved options for this resort that are no longer selected
        ResortTransportation::where('resort_id', $resort_id)->delete();

        // Save selected options
        foreach ($selected as $option) {
            $option = is_string($option) ? trim($option) : $option;
            if ($option !== '') {
                ResortTransportation::create([
                    'resort_id' => $resort_id,
                    'transportation_option' => $option,
                ]);
            }
        }

        $message = count($selected) > 0
            ? 'Transportation options updated successfully.'
            : 'Transportation options cleared.';
        return redirect()->back()->with('success', $message);
    }

    public function storeOccupancy(Request $request)
    {
        // Validate the incoming data
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'occupancy_percentage' => 'required|numeric|max:100',
        ]);

        $startDate = Carbon::createFromFormat('m/d/Y', $request->start_date);
        $endDate = Carbon::createFromFormat('m/d/Y', $request->end_date);
        $occupancyPercentage = $request->occupancy_percentage;

        // Loop through each day in the selected date range
        $currentDate = $startDate;
        while ($currentDate->lte($endDate)) {
            // Save the occupancy for each date
            Occupancy::create([
                'date' => $currentDate->format('Y-m-d'),
                'occupancy_percentage' => $occupancyPercentage,
                'resort_id' => auth()->user()->resort_id, // Assuming the resort_id is associated with the logged-in user
            ]);

            // Move to the next day
            $currentDate->addDay();
        }

        return response()->json(['success' => true, 'msg' => 'Occupancy percentages saved successfully.']);
    }

    public function exportLeave(Request $request)
    {
        $request->validate([
            'start_date' => 'required|string|regex:/^\d{2}-\d{2}-\d{4}$/',
            'end_date'   => 'required|string|regex:/^\d{2}-\d{2}-\d{4}$/',
        ], [
            'start_date.required' => 'Start date is required.',
            'end_date.required'   => 'End date is required.',
            'start_date.regex'   => 'Start date must be in dd-mm-yyyy format.',
            'end_date.regex'     => 'End date must be in dd-mm-yyyy format.',
        ]);

        $resort_id = $this->resort->resort_id;
        $start_date = \Carbon\Carbon::createFromFormat('d-m-Y', $request->start_date)->format('Y-m-d');
        $end_date   = \Carbon\Carbon::createFromFormat('d-m-Y', $request->end_date)->format('Y-m-d');

        if ($start_date > $end_date) {
            return back()->withErrors(['end_date' => 'End date must be on or after start date.']);
        }

        return Excel::download(
            new EmployeeLeaveExport($resort_id, null, null, null, null, $start_date, $end_date),
            'EmployeesLeaves.xlsx'
        );
    }

    public function downloadLeaveTemplate()
    {
        $resort_id = $this->resort->resort_id;
        return Excel::download(
            new EmployeeLeaveExport($resort_id, null, null, null, null, null, null),
            'Leave_Import_Template.xlsx'
        );
    }

    public function ImportLeave(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'UploadImportleave' => [
                'required',
                'file',
                'mimes:xlsx',
                'mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
        ], [
            'UploadImportleave.required' => 'Please select a file to upload.',
            'UploadImportleave.file'     => 'The uploaded file must be a valid file.',
            'UploadImportleave.mimes'    => 'Only Excel (.xlsx) files are allowed. Please upload an xlsx file.',
            'UploadImportleave.mimetypes'=> 'Only Excel (.xlsx) files are allowed. Please upload an xlsx file.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first('UploadImportleave')]);
        }

        $filePath = $request->file('UploadImportleave')->store('imports');

        ImportLeavesJob::dispatch($filePath, auth('resort-admin')->user()->resort_id);

        return response()->json([
            'success'  => true,
            'message'  => 'Leave import has been queued. Data will be saved shortly.',
        ]);
    }
}