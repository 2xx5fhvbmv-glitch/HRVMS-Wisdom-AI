<?php

namespace App\Http\Controllers\Resorts\People\Configuration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Models\EmployeeReminder;
use Auth;
use Config;
use DB;

class EmployeeReminderController extends Controller
{
    public $resort;
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;
    }

    public function index(){
        $page_title ='Employee Reminder';
        $resort_id = $this->resort->resort_id;
        return view('resorts.people.config.reminders.list',compact('page_title'));
    }

    public function list(Request $request){
        if($request->ajax())
        {
            $reminder = EmployeeReminder::where('resort_id', $this->resort->resort_id)
                ->orderBy('created_at', 'DESC')
                ->get();

            return datatables()->of($reminder)
                ->addColumn('action', function ($reminder) {
                    $id = base64_encode($reminder->id);
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
                ->rawColumns(['action']) 
                ->make(true);
        }
    }
  
    public function store(Request $request){
      $data = $request->reminders;
        foreach ($data as $key => $value) {

            $emp_resignation_reason = EmployeeReminder::updateOrCreate(
                [
                    'resort_id' => $this->resort->resort_id,
                    'task' => $value['task'], 
                ],
                [
                    'days' => $value['days'], 
                ]);
        }
         return response()->json([
            'status' => 'success',
            'message' => 'Reminder added successfully.',
        ]);
    }

    public function update(Request $request){
        $id = base64_decode($request->id);
        $emp_resignation_reason = EmployeeReminder::find($id);
        
        if($emp_resignation_reason){
            $emp_resignation_reason->task = $request->task;
            $emp_resignation_reason->days = $request->days;
            $emp_resignation_reason->save();
        }
         return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'Reminder updated successfully.',
        ]);
    }

    public function destroy(Request $request){
        $id = base64_decode($request->id);
        $emp_resignation_reason = EmployeeReminder::find($id);
        if($emp_resignation_reason){
            $emp_resignation_reason->delete();
        }
         return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'Reminder deleted successfully.',
        ]);
    }
}