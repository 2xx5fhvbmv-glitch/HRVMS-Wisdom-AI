<?php

namespace App\Http\Controllers\Resorts\People\Configuration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Models\EmployeeNoticePeriod;
use App\Models\ResortPosition;
use Auth;
use Config;
use DB;

class NoticePeriodController extends Controller
{
    public $resort;
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
    }

    Public function index(){
        $page_title ='Notice Period';
        $resort_id = $this->resort->resort_id;
       $emp_grade = config('settings.eligibilty');
        return view('resorts.people.config.notice-period.list',compact('page_title','emp_grade'));
    }
    
    public function list(Request $request){
        if($request->ajax())
        {
            $noticePeriod = EmployeeNoticePeriod::where('resort_id', $this->resort->resort_id)->get();

            return datatables()->of($noticePeriod)
                ->addColumn('immediate_release', function ($noticePeriod) {
                        $immidate_release = '';
                    if($noticePeriod->immediate_release == '1'){
                        $immidate_release = '<span class="badge badge-themeSuccess">Yes</span>';
                    }else{
                        $immidate_release = '<span class="badge badge-themeDanger">No</span>';
                    }
                    return $immidate_release;
                })
                ->addColumn('action', function ($noticePeriod) {
                    $id = base64_encode($noticePeriod->id);
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
                ->rawColumns(['immediate_release','action']) // Ensure buttons are rendered as HTML
                ->make(true);
        }
    }


    public function store(Request $request){
         $data = $request->notice_periods;

        foreach ($data as $key => $value) {
            $emp_notice_period = EmployeeNoticePeriod::updateOrCreate(
            [
                'resort_id' => $this->resort->resort_id,
                'title' => $value['title'],
            ],
            [
                'period' => @$value['days'],
                'immediate_release' => @$value['immediate_release'] ?? 0,
            ]
        );
        }
         return response()->json([
            'status' => 'success',
            'message' => 'Notice Period added successfully.',
        ]);
    }

    public function update(Request $request){
        $id = base64_decode($request->id);

        $emp_notice_period = EmployeeNoticePeriod::find($id);
        
        if($emp_notice_period){
            $emp_notice_period->title = $request->title;
            if($request->immediate_release == '1'){
                $emp_notice_period->period = null;
            }else{
                $emp_notice_period->period = $request->period;
            }
            $emp_notice_period->immediate_release = $request->immediate_release ?? 0;
            $emp_notice_period->save();
        }
         return response()->json([
            'success' => true,
            'message' => 'Notice Period updated successfully.',
        ]);
    }

    public function destroy(Request $request){
        $id = base64_decode($request->id);
        $emp_notice_period = EmployeeNoticePeriod::find($id);
        if($emp_notice_period){
            $emp_notice_period->delete();
        }
         return response()->json([
            'success' => true,
            'message' => 'Notice Period deleted successfully.',
        ]);
    }
}