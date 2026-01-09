<?php

namespace App\Http\Controllers\Resorts\People\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use Auth;
use Config;
use DB;
use App\Helpers\Common;
use App\Models\Employee;
use App\Models\ResortAdmin;
use App\Models\ResortPosition;
use App\Models\ResortDepartment;
use App\Models\EmployeeInfoUpdateRequest;
use Carbon\Carbon;
class InfoUpdateController extends Controller
{
    public $resort;
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
    }

    // Index 
    public function index(Request $request){
          $page_title = 'Info Update Requests';
          $resort = $this->resort;
          
          $positions = ResortPosition::where('resort_id',$resort->resort_id)->where('status','active')->get();
          $departments = ResortDepartment::where('resort_id',$resort->resort_id)->where('status','active')->get();

          return view('resorts.people.info_update.index',compact('page_title','positions','departments'));
     }

     // List Page
     public function list(Request $request){
          $resort = $this->resort;
          
          $positions = ResortPosition::where('resort_id',$resort->resort_id)->where('status','active')->get();
          $departments = ResortDepartment::where('resort_id',$resort->resort_id)->where('status','active')->get();

          $query = EmployeeInfoUpdateRequest::where('resort_id',$resort->resort_id)->with([
               'employee.resortAdmin',
               'department',
               'position'
          ]);

          if ($request->search != null) {
               $searchTerm = $request->search;
               $query->whereHas('employee', function ($q) use ($request,$searchTerm) {
                    $q->whereHas('resortAdmin',function($Qname) use ($searchTerm){
                         $Qname->where('id', 'LIKE', "%{$searchTerm}%")
                         ->orWhere('first_name', 'LIKE', "%{$searchTerm}%")
                                   ->orWhere('last_name', 'LIKE', "%{$searchTerm}%")
                                   ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "%{$searchTerm}%");
                    });
               });
          }
          if ($request->department) {
               $query->whereHas('employee', function ($q) use ($request) {
                    $q->where('Dept_id', $request->department);
               });
          }

          if ($request->position) {
               $query->whereHas('employee', function ($q) use ($request) {
                    $q->where('Position_id', $request->position);
               });
          }
          
          if($request->status != null){
               $query->where('status',$request->status);
          }else{
               $query->where('status','Pending');
          }

          if($request->date != null){
               $query->where('created_at',$request->date);
          }

           $employeeInfoUpdateRequest = $query->wherehas('employee.resortAdmin')->orderBy('created_at','desc')->paginate(10);
          if(Common::checkRouteWisePermission('people.info-update.index',config('settings.resort_permissions.edit')) == false){
               $edit_class = 'd-none';
          } else {
               $edit_class = '';
          }
         return response()->json([
               'status' => 'success',
               'html' => view('resorts.people.info_update.table_view', compact('employeeInfoUpdateRequest','edit_class'))->render()
          ]);
     }

     // View Details 
     public function show($id){
          if(!is_numeric($id)){
               $id = base64_decode($id);
          }
          $emp_info = EmployeeInfoUpdateRequest::where('id',$id)->where('resort_id',$this->resort->resort_id)->where('status','Pending')->with([
               'employee.resortAdmin',
               'department',
               'position'
           ])->wherehas('employee.resortAdmin')->first();

           $html = view('resorts.people.info_update.show_details', ['emp_info' => $emp_info])->render();

           

            return response()->json(['status' => 'success', 'message' => 'get.','html'=> $html]);
     }

   
     public function statusChange(Request $request){
          if($request->status == 'approve'){
               $employeeinfoUpdateRequest = EmployeeInfoUpdateRequest::where('id',$request->id)->first();
               $payload = $employeeinfoUpdateRequest->info_payload;

               foreach($payload as $key => $newValue){
                         $employees = Employee::where('id',$employeeinfoUpdateRequest->employee_id)->first();

                    if(in_array($key, ['first_name', 'middle_name', 'last_name', 'personal_phone'])){ //need Changes when App Integration is Complete only check if request data is correct or not 

                         $resort_admin = ResortAdmin::where('id',$employees->Admin_Parent_id)->first();
                         if($resort_admin){
                              $resort_admin->update([
                                   $key => $newValue,
                              ]);
                         }
                    }else{
                         if($employees){
                              $employees->update([
                                   $key => $newValue,
                              ]);
                         }
                    }
                    
               } 

               $employeeinfoUpdateRequest->update([
                    'status' => 'Approved',
                    'modified_by' => auth()->id(),
               ]);

          }
          return response()->json([
               'success' => 'true',
               'message' => 'Record Updated Successfully',
          ]);
          return redirect()->route('people.info-update.index')->with('success','Record Update Successfully');

     }


     // Reject Request
     public function rejectRequest(Request $request){
          $employeeinfoUpdateRequest = EmployeeInfoUpdateRequest::where('id',$request->id)->first();
          $employeeinfoUpdateRequest->update([
               'status' => 'Rejected',
               'reject_reason' => $request->reject_reason,
               'modified_by' => auth()->id(),
          ]);

          return redirect()->route('people.info-update.index')->with('success','Record Update Successfully');

     }

}