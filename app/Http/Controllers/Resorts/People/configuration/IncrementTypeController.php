<?php

namespace App\Http\Controllers\Resorts\People\Configuration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Models\IncrementType;
use Auth;
use Config;
use DB;

class IncrementTypeController extends Controller
{
    public $resort;
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;
    }

    public function indeX(){
        $page_title ='Resignation Reasons';
        $resort_id = $this->resort->resort_id;
       
        return view('resorts.people.config.increment-type.list',compact('page_title'));
    }

    public function list(Request $request){
        if($request->ajax())
        {
            $incrementType = IncrementType::where('resort_id', $this->resort->resort_id)->get();

            return datatables()->of($incrementType)
                ->addColumn('status', function ($incrementType) {
                        $status = '';
                    if($incrementType->status == 'Active'){
                        $status = '<span class="badge badge-themeSuccess">'.$incrementType->status.'</span>';
                    }else{
                        $status = '<span class="badge badge-themeDanger">'.$incrementType->status.'</span>';
                    }
                    return $status;
                })
                ->addColumn('action', function ($incrementType) {
                    $id = base64_encode($incrementType->id);
                    return '
                        <div class="d-flex align-items-center">
                            <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" data-id="' . e($id) . '">
                                <img src="' . asset("resorts_assets/images/edit.svg") . '" alt="Edit" class="img-fluid">
                            </a>
                            <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-id="' . e($id) . '">
                                <img src="' . asset("resorts_assets/images/trash-red.svg") . '" alt="Delete" class="img-fluid">
                            </a>
                        </div>';
                })
                ->rawColumns(['status','action']) 
                ->make(true);
        }
        return view('resorts.people.config.increment-type.list');
    }
  
    
    public function store(Request $request){
        
        $data = $request->types;

        foreach ($data as $key => $value) {
    
            if(isset($value['status']) && $value['status'] == 1){
                $status = 'Active';
            }else{
                $status = 'Inactive';
            }

            $emp_resignation_reason = IncrementType::updateOrCreate(
                [
                    'resort_id' => $this->resort->resort_id,
                    'name' => $value['title'], 
                ],
                [
                    'status' => $status,
                ]);
        }
         return response()->json([
            'status' => 'success',
            'message' => 'Increment Type added successfully.',
        ]);
    }

    public function update(Request $request){
         $id = base64_decode($request->Main_id);
        
        $incrementType = IncrementType::where('id', $id)->update([
                'name' => $request->title, 
                'status' => $request->status, 
            ]);
        
         return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'Increment Type updated successfully.',
        ]);
    }

    public function destroy(Request $request){
        $id = base64_decode($request->id);

        IncrementType::where('id', $id)->delete();

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'Increment Type deleted successfully.',
        ]);
    }

}