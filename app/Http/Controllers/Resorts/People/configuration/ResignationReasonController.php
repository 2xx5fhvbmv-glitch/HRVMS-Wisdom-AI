<?php

namespace App\Http\Controllers\Resorts\People\configuration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Models\EmployeeResignationReason;
use Auth;
use Config;
use DB;
class ResignationReasonController extends Controller
{
    public $resort;
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
    }

    public function index(){
        $page_title ='Resignation Reasons';
        $resort_id = $this->resort->resort_id;
       
        return view('resorts.people.config.resignation-reason.list',compact('page_title'));
    }

    public function list(Request $request){
        if($request->ajax())
        {
            $resignationReason = EmployeeResignationReason::where('resort_id', $this->resort->resort_id)->orderBy('created_at','desc')->get();

            return datatables()->of($resignationReason)
                ->addColumn('status', function ($resignationReason) {
                        $status = '';
                    if($resignationReason->status == 'Active'){

                        $status = '<span class="badge badge-themeSuccess">'.$resignationReason->status.'</span>';
                    }else{
                        $status = '<span class="badge badge-themeDanger">'.$resignationReason->status.'</span>';
                    }
                return $status;

                })
                ->addColumn('action', function ($resignationReason) {
                    $id = base64_encode($resignationReason->id);
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
        return view('resorts.people.config.resignation-reason.list');
    }
  
    
    public function store(Request $request)
    {
        $data = $request->reasons;

        foreach ($data as $key => $value) {
            $reason = trim($value['reason']);
            $status = (isset($value['status']) && $value['status'] == 1) ? 'Active' : 'Inactive';

            if (empty($reason)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Reason name is required.',
                ]);
            }

            $check = EmployeeResignationReason::where('resort_id', $this->resort->resort_id)
                ->where('reason', $reason)
                ->first();

            if ($check) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Reason name already exists. Please try a different name.',
                ]);
            }

            EmployeeResignationReason::updateOrCreate(
                [
                    'resort_id' => $this->resort->resort_id,
                    'reason' => $reason,
                ],
                [
                    'status' => $status,
                ]
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Resignation Reasons added successfully.',
        ]);
    }


    public function update(Request $request){
         $id = base64_decode($request->Main_id);
        
        $employeeResignationReason = EmployeeResignationReason::where('id', $id)->update([
                'reason' => $request->reason, 
                'status' => $request->status, 
            ]);
        
         return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'Resignation Reasons updated successfully.',
        ]);
    }

    public function destroy(Request $request){
        $id = base64_decode($request->id);

        EmployeeResignationReason::where('id', $id)->delete();

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'Resignation Reason deleted successfully.',
        ]);
    }

}