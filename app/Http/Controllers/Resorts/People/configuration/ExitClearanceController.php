<?php

namespace App\Http\Controllers\Resorts\People\Configuration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Models\EmployeeNoticePeriod;
use App\Models\ResortDepartment;
use App\Models\ExitClearanceForm;
use Auth;
use Config;
use DB;

class ExitClearanceController extends Controller
{
    public $resort;
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;
    }

    Public function index(){
        $page_title ='Exit Clearance Forms';
        $resort_id = $this->resort->resort_id;
        return view('resorts.people.config.exit-clearance.list',compact('page_title'));
    }
    
    public function list(Request $request){
        if($request->ajax())        
        {
            $query = ExitClearanceForm::where('resort_id', $this->resort->resort_id);

            if ($request->searchTerm != null) {
                $searchTerm = $request->searchTerm;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('form_name', 'LIKE', "%{$searchTerm}%")
                      ->orWhereHas('department', function ($q2) use ($searchTerm) {
                          $q2->where('name', 'LIKE', "%{$searchTerm}%");
                      });
                });
            }
            $exit_clearance = $query->get();

            return datatables()->of($exit_clearance)
                ->addColumn('department', function ($exit_clearance) {
                   
                    return $exit_clearance->department->name ?? '--';
                })
                ->addColumn('action', function ($row) {
                     $id = base64_encode($row->id);
                    $edit_url = route('people.exit-clearance.edit', $id);
                    $editimg = asset('resorts_assets/images/edit.svg');
                    $deleteimg = asset('resorts_assets/images/trash-red.svg');
        
                    return "<a href='$edit_url' class='edit-row-btn'><img src='$editimg' alt='Edit'></a>
                            <a href='#' class='delete-row-btn' data-id='$id'><img src='$deleteimg' alt='Delete'></a>";
                })
                ->rawColumns(['department','action']) // Ensure buttons are rendered as HTML
                ->make(true);
        }
    }
  
    public function create(){
       $page_title ='Exit Clearance Form';
        $resort_id = $this->resort->resort_id;
        $departments =  ResortDepartment::where('resort_id', $this->resort->resort_id)->get();
        $form_types = ExitClearanceForm::FORM_TYPES;
        return view('resorts.people.config.exit-clearance.create',compact('page_title','departments','form_types'));
    }

    public function store(Request $request){

        $exit_clearance  = ExitClearanceForm::create([
            'resort_id' => $this->resort->resort_id,
            'department_id' => $request->department,
            'form_name' => $request->form_name,
            'form_structure' => $request->form_structure,
            'form_type'=> $request->form_type,
            'type' => $request->employee_type,
        ]);        

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'Exit Clearance Form added successfully.',
            'redirect_url' => route('people.exit-clearance.index'),
        ]);
    }

    public function edit($id){
        $page_title ='Exit Clearance Form';
        $resort_id = $this->resort->resort_id;
        $departments =  ResortDepartment::where('resort_id', $this->resort->resort_id)->get();
        $exit_clearance = ExitClearanceForm::find(base64_decode($id));
        $form_types = ExitClearanceForm::FORM_TYPES;

        return view('resorts.people.config.exit-clearance.edit',compact('page_title','departments','exit_clearance','form_types'));
    }

    public function update(Request $request,$id){
        if(!is_numeric($id)){
            $id = base64_decode($id);
        }
        $exit_clearance = ExitClearanceForm::find($id);

        if($exit_clearance){
            $exit_clearance->department_id = $request->department;
            $exit_clearance->form_name = $request->form_name;
            $exit_clearance->form_structure = $request->form_structure;
            $exit_clearance->form_type = $request->form_type;
            $exit_clearance->type = $request->employee_type;
            $exit_clearance->save();
        }

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'Exit Clearance Form updated successfully.',
            'redirect_url' => route('people.exit-clearance.index'),
        ]);
    }
   
    public function destroy($id)
    {
          if(!is_numeric($id)){
            $id = base64_decode($id);
        }
            $exit_clearance = ExitClearanceForm::find($id);

        if ($exit_clearance) {
            $exit_clearance->delete();
            return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => 'Exit Clearance Form deleted successfully.',
            ]);
        }
        return response()->json([
            'success' => false,
            'status' => 'error',
            'message' => 'Exit Clearance Form not found.',
        ]);
    }

}