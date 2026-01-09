<?php
namespace App\Http\Controllers\Resorts\People\Onboarding;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\Events\ResortNotificationEvent;
use App\Models\Resort;
use App\Models\Employee;
use App\Models\FacilityTourCategories;
use App\Models\FilemangementSystem;
use App\Models\FacilityTourImages;
use Auth;
use Config;
use Common;
use DB;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
class FacilityTourCategoryController extends Controller
{
    public $resort;
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
    }

    public function index(Request $request)
    {
        $resort = $this->resort;
        $page_title  = 'Facility Tour Categories';
        $facilityTourCategoriesQuery = FacilityTourCategories::where('resort_id', $resort->resort_id)
            ->orderBy('created_at', 'desc');

            if ($request->has('searchTerm') && $request->searchTerm != '') {
                $facilityTourCategoriesQuery->where('name', 'like', '%' . $request->searchTerm . '%');
            }
            
           $facilityTourCategories = $facilityTourCategoriesQuery->get();
            
        // yajra datatable
        if ($request->ajax()) { 
            return datatables()->of($facilityTourCategories)
                ->addColumn('action', function ($category) {

                    $edit_route = route('people.onboarding.facility-tour-categories.edit',base64_encode($category->id));
                    $view_route = route('people.onboarding.facility-tour-categories.show',base64_encode($category->id));
                    $viewBtn = '<a href="'.$view_route.'" data-id="'.$category->id.'" class="view-row-btn "><i class="fas fa-eye"></i></a>';
                    $editBtn = '<a href="javascript:void(0)" class="edit-row-btn ml-1" data-id="'.$category->id.'"><i class="fas fa-edit"></i></a>';
                    $deleteBtn = '<a href="javascript:void(0)" data-id="'.$category->id.'" class="delete-row-btn ml-1"><i class="fas fa-trash"></i></a>';
                    return $viewBtn.' '.$editBtn.' ' . $deleteBtn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('resorts.people.onboarding.facility_tour_category.list', compact('page_title'));
    }


    public function store(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'facilityTourName' => 'required|string|max:255',
            'thumbnail_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:10240',
            'facilityTourImgs.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10240',
        ], [
            'thumbnail_image.max' => 'The facility tour image must not be larger than 10MB.',
            'facilityTourImgs.*.max' => 'Each tour image must not be larger than 10MB.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $slug = \Str::slug($request->facilityTourName);
       
        $originalSlug = $slug;
        $count = 1;

        // Check if the slug already exists and append a number if it does
        while (FacilityTourCategories::where('slug', $slug)
            ->where('resort_id', $this->resort->resort_id)
            ->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        $fileManagement = FilemangementSystem::where('Folder_Name','facilityTourCategory')->first();
        if($fileManagement){
            $folderName = $fileManagement->Folder_Name;
        }
        $category_image = $request->file('thumbnail_image');
        $image_url = null;

        if ($category_image) {
            $thumbnail_image = Common::AWSEmployeeFacilityCategoryImageUpload($this->resort->resort_id, $category_image,$folderName);
        }

        if(isset($thumbnail_image['status']) && $thumbnail_image['status'] == true) {
            
            $facilityTourCategory = FacilityTourCategories::create([
                'resort_id' => $this->resort->resort_id,
                'name' => $request->facilityTourName,
                'slug' => $slug,
                'thumbnail_image' => $thumbnail_image['path'],
            ]);
        }else{
            return response()->json(['success' => false, 'message' => 'Thumbnail image upload failed.'], 500);
        }
        
        if ($request->hasFile('facilityTourImgs') && $facilityTourCategory) {
            foreach ($request->file('facilityTourImgs') as $image) {
                $aws = Common::AWSEmployeeFacilityCategoryImageUpload($this->resort->resort_id, $image,$folderName);
                if(isset($aws['status']) && $aws['status'] == true){
                    $facilityTourImage = FacilityTourImages::create([
                        'facility_tour_category_id' => $facilityTourCategory->id,
                        'image' => $aws['path'],
                    ]);
                }
            }
        }else{
            return response()->json(['success' => false, 'message' => 'Tour images upload failed.'], 500);
        }
        
        return response()->json(['success' => true, 'message' => 'Facility Tour Category created successfully.'], 200);

    }

    public function show($id)
    {
        $id =  base64_decode($id);
       
        $page_title = 'Facility Tour Category Details';
        $resort = $this->resort;
        $facilityTourCategory = FacilityTourCategories::findOrFail($id);
        $facilityTourImages = FacilityTourImages::where('facility_tour_category_id', $id)->get();
        return view('resorts.people.onboarding.facility_tour_category.view', compact('facilityTourCategory', 'facilityTourImages','resort', 'page_title'));
    }


    public function edit($id)
    {
        $facilityTourCategory = FacilityTourCategories::findOrFail($id);
        $facilityTourImages = FacilityTourImages::where('facility_tour_category_id', $id)->get();
        return view('resorts.people.onboarding.facility_tour_categories.edit', compact('facilityTourCategory', 'facilityTourImages'));
    }

    public function update(Request $request, $id)
    {
       
        $facilityTourCategory = FacilityTourCategories::findOrFail($id);

        if($facilityTourCategory) {
            
            $facilityTourCategory->update([
                'name' => $request->name,
                'status' => $request->status,
            ]);
        }
        
        return response()->json(['success' => true, 'message' => 'Facility Tour Category updated successfully.'], 200);
      
    }   

    public function destroy($id)
    {
        $facilityTourCategory = FacilityTourCategories::findOrFail($id);    
    
        if ($facilityTourCategory->image) {
            Common::deleteFile($facilityTourCategory->image);
        }

        $facilityTourImages = FacilityTourImages::where('facility_tour_category_id', $id)->get();
        foreach ($facilityTourImages as $image) {

            Common::deleteFile($image->image);
            $image->delete();
        }

        $facilityTourCategory->delete();
        return response()->json(['success' => true, 'message' => 'Facility Tour Category deleted successfully.']);

    } 

    public function imageUpdate(Request $request){

        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:4096',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()], 422);
        }

        $facilityTourCategory = FacilityTourCategories::findOrFail($request->id);

        $fileManagement = FilemangementSystem::where('Folder_Name','facilityTourCategory')->first();
        if($fileManagement){
            $folderName = $fileManagement->Folder_Name;
        }

        
        $image = $request->file('image');
        $aws_tour = Common::AWSEmployeeFacilityCategoryImageUpload($this->resort->resort_id, $image,$folderName);

        if (!isset($aws_tour['status']) || $aws_tour['status'] == false) {
            return response()->json(['success' => false, 'message' => 'Image upload failed.'], 500);
        }
        

        if($request->type == 'thumbnail'){
            $facilityTourCategory->update([
                'thumbnail_image' => $aws_tour['path'],
            ]);
        }elseif($request->type == 'tour_image'){

            $facilityTourImage = FacilityTourImages::findOrFail($request->image_id);

            $facilityTourImage->update([
                'image' => $aws_tour['path'],
            ]);

        } 
        if ($aws_tour['status'] == false) {
            return response()->json(['success' => false, 'message' => 'Image upload failed.'], 500);
        } else {
            return response()->json([
            'success' => true,
            'message' => 'Image updated successfully.',
            ], 200);
        }

    }
    
    public function imageDelete(Request $request)
    {
        if($request->type == 'thumbnail'){
            $facilityTourCategory = FacilityTourCategories::findOrFail($request->id);
            if($facilityTourCategory){
                $facilityTourCategory->update([
                    'thumbnail_image' => null,
                ]);
            }
            return response()->json(['success' => true, 'message' => 'Facility Tour Image deleted successfully.']);
        }else{
            $facilityTourImages = FacilityTourImages::findOrFail($request->id);
            if($facilityTourImages){
                $facilityTourImages->delete();
            }
            return response()->json(['success' => true, 'message' => 'Facility Tour Image deleted successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'Image not delete try again.']);
        
    }
}