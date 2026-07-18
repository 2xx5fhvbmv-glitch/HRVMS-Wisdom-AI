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
        if(!$this->resort) return;
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

               // Was dereferencing info_payload on a possibly-null result
               // with no guard — an unmatched/already-processed id fatals
               // with "Attempt to read property on null" instead of a
               // clean error response.
               if (!$employeeinfoUpdateRequest) {
                    return response()->json([
                         'success' => false,
                         'message' => 'Request not found.',
                    ], 404);
               }

               $payload = $employeeinfoUpdateRequest->info_payload;
               $employees = Employee::where('id',$employeeinfoUpdateRequest->employee_id)->first();

               // This whole approve action previously ran with no
               // transaction and no exception handling — every other
               // mutating action in this app wraps in DB::beginTransaction/
               // commit/rollBack. If any single field in the payload failed
               // to save (bad value, DB constraint), the loop would fatal
               // partway through with some fields already written and
               // others not, the status update to 'Approved' never
               // running, and the HR user seeing a generic AJAX error with
               // no record of what actually happened.
               // Permanent Address (address_line_1/2) lives on ResortAdmin,
               // not Employee — those columns were dropped from the
               // employees table entirely, so routing them to $employees
               // below either silently no-ops or throws an "Unknown column"
               // SQL error that poisons the whole approval transaction.
               $resortAdminFields = ['first_name', 'middle_name', 'last_name', 'personal_phone', 'address_line_1', 'address_line_2'];

               DB::beginTransaction();
               try {
                    foreach($payload as $key => $newValue){
                         // dob is stored on Employee as Y-m-d, but mobile can
                         // submit other formats (e.g. "16-Sep-1992") — write
                         // the same canonical format updatePersonal() uses,
                         // otherwise every other feature reading Employee.dob
                         // (age calc, exports, reports) breaks silently.
                         if ($key === 'dob' && !empty($newValue)) {
                              try {
                                   $newValue = \Carbon\Carbon::parse($newValue)->format('Y-m-d');
                              } catch (\Exception $e) {
                                   // Leave as-is; validation should have caught a truly invalid date.
                              }
                         }

                         if(in_array($key, $resortAdminFields, true)){ //need Changes when App Integration is Complete only check if request data is correct or not

                              $resort_admin = $employees ? ResortAdmin::where('id',$employees->Admin_Parent_id)->first() : null;
                              if($resort_admin && in_array($key, $resort_admin->getFillable(), true)){
                                   $resort_admin->update([
                                        $key => $newValue,
                                   ]);
                              }
                         }else{
                              if($employees && in_array($key, $employees->getFillable(), true)){
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

                    DB::commit();

                    // Neither approve nor reject notified the employee at
                    // all — they had no way to know their request had been
                    // actioned without manually checking back.
                    try {
                         Common::sendMobileNotification(
                              $this->resort->resort_id,
                              2,
                              null,
                              null,
                              'Profile Update Approved',
                              'Your profile update request has been approved.',
                              'People',
                              [$employeeinfoUpdateRequest->employee_id],
                              null,
                              false,
                              'info-update-approved'
                         );
                    } catch (\Exception $ne) {
                         \Log::warning('Info update approval notification failed: ' . $ne->getMessage());
                    }
               } catch (\Exception $e) {
                    DB::rollBack();
                    \Log::emergency("File: ".$e->getFile());
                    \Log::emergency("Line: ".$e->getLine());
                    \Log::emergency("Message: ".$e->getMessage());
                    return response()->json([
                         'success' => false,
                         'message' => 'Failed to update employee record.',
                    ], 500);
               }
          }
          return response()->json([
               'success' => 'true',
               'message' => 'Record Updated Successfully',
          ]);

     }


     // Reject Request
     public function rejectRequest(Request $request){
          $employeeinfoUpdateRequest = EmployeeInfoUpdateRequest::where('id',$request->id)->first();
          if (!$employeeinfoUpdateRequest) {
               return redirect()->route('people.info-update.index')->with('error','Request not found.');
          }

          $employeeinfoUpdateRequest->update([
               'status' => 'Rejected',
               'reject_reason' => $request->reject_reason,
               'modified_by' => auth()->id(),
          ]);

          // Same gap as approve — the employee never found out their
          // request was rejected (or why) without manually checking back.
          try {
               Common::sendMobileNotification(
                    $this->resort->resort_id,
                    2,
                    null,
                    null,
                    'Profile Update Rejected',
                    'Your profile update request was rejected.' . ($request->reject_reason ? ' Reason: ' . $request->reject_reason : ''),
                    'People',
                    [$employeeinfoUpdateRequest->employee_id],
                    null,
                    false,
                    'info-update-rejected'
               );
          } catch (\Exception $ne) {
               \Log::warning('Info update rejection notification failed: ' . $ne->getMessage());
          }

          return redirect()->route('people.info-update.index')->with('success','Record Update Successfully');

     }

}