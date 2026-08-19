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
use App\Models\LetterheadSetting;
use App\Helpers\Common;

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
            // Was ->where("id",$id)->delete() with no resort filter — any
            // resort-admin could delete another resort's announcement
            // category by id. Scope like the sibling CategoryinlineUpdate().
            AnnouncementCategory::where('resort_id', $this->resort->resort_id)
                ->where("id",$id)->delete();

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

    /**
     * Letterhead & E-signature configuration screen.
     *
     * Per-resort branded letterhead (header/footer images), stored e-signature
     * image and signatory block used by document/letter PDFs (Transfer Letter
     * today; Probation / Promotion can adopt it via Common::getLetterheadData()).
     */
    public function letterheadIndex()
    {
        $page_title = 'Letterhead & Signature';
        $letterhead = LetterheadSetting::where('resort_id', $this->resort->resort_id)->first();

        return view('resorts.people.config.letterhead', compact('page_title', 'letterhead'));
    }

    /**
     * Store / update the resort's Letterhead & E-signature configuration.
     * Images accept heic/heif (the app is HEIC-friendly site-wide).
     */
    public function letterheadStore(Request $request)
    {
        // No `image` rule — it rejects SVG/HEIC; mimes covers the allowed set
        // and matches the site-wide HEIC-friendly upload convention.
        $imageRule = 'nullable|file|mimes:jpeg,jpg,png,gif,svg,webp,heic,heif|max:5120';

        $validator = Validator::make($request->all(), [
            'header_image'    => $imageRule,
            'footer_image'    => $imageRule,
            'signature_image' => $imageRule,
            'signatory_name'  => 'nullable|string|max:150',
            'signatory_title' => 'nullable|string|max:150',
            'address_line1'   => 'nullable|string|max:255',
            'address_line2'   => 'nullable|string|max:255',
            'contact_phone'   => 'nullable|string|max:100',
            'contact_email'   => 'nullable|email|max:150',
            'website'         => 'nullable|string|max:150',
        ], [
            'header_image.mimes'    => 'The header image must be of type: jpeg, jpg, png, gif, svg, webp, heic, heif.',
            'footer_image.mimes'    => 'The footer image must be of type: jpeg, jpg, png, gif, svg, webp, heic, heif.',
            'signature_image.mimes' => 'The signature image must be of type: jpeg, jpg, png, gif, svg, webp, heic, heif.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $resortId = $this->resort->resort_id;

        DB::beginTransaction();
        try {
            $setting = LetterheadSetting::firstOrNew(['resort_id' => $resortId]);

            // Relative-to-public directory, one per resort.
            $relativeDir = 'uploads/letterheads/' . $resortId;
            $absoluteDir = public_path($relativeDir);

            foreach (['header_image', 'footer_image', 'signature_image'] as $field) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    $fileName = $field . '_' . substr(md5(uniqid('', true)), 0, 10)
                        . '.' . strtolower($file->getClientOriginalExtension());

                    Common::uploadFile($file, $fileName, $absoluteDir);

                    // Remove the previous image so old files don't pile up.
                    $old = $setting->{$field};
                    if (!empty($old) && is_file(public_path($old))) {
                        @unlink(public_path($old));
                    }

                    $setting->{$field} = $relativeDir . '/' . $fileName;
                }
            }

            $setting->signatory_name  = $request->input('signatory_name');
            $setting->signatory_title = $request->input('signatory_title');
            $setting->address_line1   = $request->input('address_line1');
            $setting->address_line2   = $request->input('address_line2');
            $setting->contact_phone   = $request->input('contact_phone');
            $setting->contact_email   = $request->input('contact_email');
            $setting->website         = $request->input('website');
            $setting->save();

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Letterhead & signature settings saved successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File: ' . $e->getFile());
            \Log::emergency('Line: ' . $e->getLine());
            \Log::emergency('Message: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to save letterhead settings.',
            ], 500);
        }
    }
}