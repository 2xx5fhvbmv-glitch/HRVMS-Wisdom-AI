<?php

namespace App\Http\Controllers\Resorts\People;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Models\Announcement;
use App\Models\AnnouncementCategory;
use App\Models\ResortPosition;
use App\Models\EmployeeResignationWithdrawalConfig;

use Auth;
use Config;
use DB;

class ConfigController extends Controller
{
    public $resort;
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
    }

    public function index()
    {
        $page_title ='People Configuration';
        $resort_id = $this->resort->resort_id;
        $emp_resignation_withdrawal_config = EmployeeResignationWithdrawalConfig::where('resort_id', $resort_id)->first();
        $emp_grade = config('settings.eligibilty');
        return view('resorts.people.config.index',compact('page_title','emp_grade','emp_resignation_withdrawal_config'));
    }

    public function storeCategory(Request $request)
    {
        $request->validate([
            'category_name' => 'required|array',
            'category_name.*' => 'required|string|max:50'
        ]);
    
        $resort_id = $this->resort->resort_id;
        $errors = [];
    
        foreach ($request->category_name as $index => $name) {
            $exists = AnnouncementCategory::where('resort_id', $resort_id)
                ->where('name', $name)
                ->exists();
    
            if ($exists) {
                $errors["category_name.$index"] = ["The category '$name' already exists for this resort."];
            }
        }
    
        if (!empty($errors)) {
            return response()->json([
                'status' => 'error',
                'errors' => $errors
            ], 422);
        }
    
        foreach ($request->category_name as $name) {
            AnnouncementCategory::create([
                'resort_id' => $resort_id,
                'name' => $name
            ]);
        }
    
        return response()->json([
            'status' => 'success',
            'message' => 'Categories added successfully.',
            'redirect_url' => route('announcement.categories')
        ]);
    }
    

    public function announcementCategories(Request $request){
        if($request->ajax())
        {
            $categories = AnnouncementCategory::where('resort_id', $this->resort->resort_id)->get();

            return datatables()->of($categories)
                ->addColumn('action', function ($category) {
                    $id = base64_encode($category->id);
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
        $page_title = 'Announcement Categories';
        return view('resorts.people.config.announcement-category', compact('page_title'));

    }

    public function CategoryinlineUpdate(Request $request,$id)
    {
        $Main_id = (int) base64_decode($request->Main_id);

        $resort_id = $this->resort->resort_id;
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'max:50',
                Rule::unique('announcement_category')->where(function ($query) use ($resort_id,$Main_id) {
                    return $query->where('resort_id', $resort_id);
                })->ignore( $Main_id),
            ],
        ], [
            'name.required' => 'The Category Name field is required. Please write something.',
            'name.unique' => 'The Category Name already exists for this resort.',
            'name.max' => 'The maximum allowed length for the Category Name is 50 characters.',
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
            AnnouncementCategory::where('resort_id', $this->resort->resort_id)
            ->where('id', $Main_id)
            ->update([
                'name' => $request->name,
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
            AnnouncementCategory::where("id",$id)->delete();

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

    public function EmployeeResignationWithdrawalConfigStore(Request $request)
    {
        $emp_resignation_withdrawal = EmployeeResignationWithdrawalConfig::where('resort_id', $this->resort->resort_id)->first();
        if($emp_resignation_withdrawal){
            $emp_resignation_withdrawal->update([
                'enable_resignation_withdrawal'=> $request->enable_resignation_withdrawal,
                'required_resignation_withdrawal_reason'=> $request->required_resignation_withdrawal_reason,
            ]);
        }else{
            EmployeeResignationWithdrawalConfig::create([
                'resort_id' => $this->resort->resort_id,
                'enable_resignation_withdrawal'=> $request->enable_resignation_withdrawal,
                'required_resignation_withdrawal_reason'=> $request->required_resignation_withdrawal_reason,
            ]);
        }
        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'Configuration updated successfully.',
        ]);
    }
}