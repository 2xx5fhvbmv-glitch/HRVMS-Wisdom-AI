<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee;
use App\Models\PayrollAdvance;
use App\Models\PayrollAdvanceGuarantor;
use App\Models\PayrollAdvanceAttachments;
use App\Helpers\Common;
use Carbon\Carbon;
use Validator;
use DB;

class RequestController extends Controller
{
    protected $user;
    protected $resort_id;
    protected $underEmp_id = [];

    public function __construct()
    {

        if (Auth::guard('api')->check()) {
            $this->user                                 =   Auth::guard('api')->user();
            $this->resort_id                            =   $this->user->resort_id;
        }
    }

    public function requestDashboard()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {

            $employee_id                                =   $this->user->GetEmployee->id;

            // Fetch all in one query for efficiency
            $requests                                   =   PayrollAdvance::join('employees', 'payroll_advance.employee_id', '=', 'employees.id')
                                                                ->join('payroll_advance_guarantor as pag', 'payroll_advance.id', '=', 'pag.payroll_advance_id')
                                                                ->where('payroll_advance.employee_id', $employee_id)
                                                                ->where('payroll_advance.resort_id', $this->resort_id)  
                                                                ->orderBy('payroll_advance.created_at', 'desc')
                                                                ->get(['payroll_advance.*','employees.Emp_id', 'pag.status as guarantor_status', 'pag.guarantor_id']);

            // Count statuses from collection instead of DB for performance
            $requestsApproved                           =   $requests->where('status', 'Approved')->count();
            $requestsPending                            =   $requests->where('status', 'Pending')->count();
            $requestsRejected                           =   $requests->where('status', 'Rejected')->count();
            $requestsInProgress                         =   $requests->where('status', 'In-Progress')->count();

        return response()->json([
            'success'                                   =>  true,
            'message'                                   =>  'Request List',
            'data'                                      =>  [
            'requests_approved'                         =>  $requestsApproved,
            'requests_pending'                          =>  $requestsPending,
            'requests_rejected'                         =>  $requestsRejected,
            'requests_inprogress'                       =>  $requestsInProgress,
            'requests'                                  =>  $requests,
            ],
        ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        } 
    }

    public function RequestStore(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        
        // Guarantors and an amount only apply to monetary requests (Payroll
        // Advance); letter-type requests such as Employment Verification
        // Letter carry neither.
        $validator = Validator::make($request->all(), [
            'request_type'                              =>  'required',
            'guarantor_id'                              =>  'required_if:request_type,Payroll Advance|array',
            'guarantor_id.*'                            =>  'integer|exists:employees,id',
            'request_amount'                            =>  'required_if:request_type,Payroll Advance',
            'currency'                                  =>  'nullable|in:MVR,USD',
            'priority'                                  =>  'required',
            'request_date'                              =>  'required',
            'purpose'                                   =>  'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        $employee_id                                    =  $this->user->GetEmployee->id;
        try {
            $PayrollAdvance                             =   PayrollAdvance::create([
                'resort_id'                             =>  $this->resort_id,
                'employee_id'                           =>  $employee_id,
                'request_type'                          =>  $request->request_type,
                'request_amount'                        =>  $request->filled('request_amount') ? $request->request_amount : null,
                'currency'                              =>  $request->filled('currency') ? $request->currency : null,
                'priority'                              =>  $request->priority,
                'request_date'                          =>  $request->request_date,
                'pourpose'                              =>  $request->purpose,
            ]);
            $guarantorIds = $request->guarantor_id ?? [];
            foreach($guarantorIds as $guaid) {
                PayrollAdvanceGuarantor::create([
                    'payroll_advance_id'                    =>  $PayrollAdvance->id,
                    'guarantor_id'                          =>  $guaid,
                    'status'                                =>  'Pending',
                ]);
            }
            // Guarantors were never told they'd been named on a request —
            // the only notification fired went to HR, so a guarantor found
            // out only if they happened to open the app and check the list.
            if (!empty($guarantorIds)) {
                Common::sendMobileNotification(
                    $this->resort_id,
                    2,
                    null,
                    null,
                    'Guarantor Request',
                    $this->user->first_name . ' ' . $this->user->last_name . ' has named you as a guarantor for a ' . $request->request_type . ' request.',
                    'Request',
                    $guarantorIds,
                    null,
                    false,
                    'guarantor-request-new',
                );
            }

            // Mobile app posts the files as "attachments"; older builds used
            // the misspelled "attechments" key, so accept either.
            $attachmentFiles = $request->file('attachments') ?? $request->file('attechments');
            if($attachmentFiles) {
                     $imagePaths = [];
                    foreach ($attachmentFiles as $file) {
                        $SubFolder="RequestAttachments";
                        $status =   Common::AWSEmployeeFileUpload($this->resort_id,$file, $this->user->GetEmployee->Emp_id,$SubFolder,true);

                        if ($status['status'] == false) {
                            break;
                        } else {
                            if($status['status'] == true && isset($status['Chil_file_id']) && !empty($status['Chil_file_id'])) {
                                $filename = $file->getClientOriginalName();
                                $imagePaths[] = ['Filename' => $filename, 'Child_id' => $status['Chil_file_id']];
                            }
                        }
                    }

                    if ($imagePaths) {
                        PayrollAdvanceAttachments::create([
                            'resort_id'             =>  $this->resort_id,
                            'payroll_advance_id'    =>  $PayrollAdvance->id,
                            'attachments'           =>  json_encode($imagePaths)
                        ]);
                    }
                }
                // Send mobile notification to every HR employee — FindResortHR()
                // only ever returns the first HR match, so a resort with more
                // than one real HR employee silently left the others with no
                // notification at all.
                $hrEmployeeIds = Common::getResortHrEmployeeIds($this->resort_id);
                if (!empty($hrEmployeeIds)) {
                    Common::sendMobileNotification(
                        $this->resort_id,
                        2,
                        null,
                        null,
                        'Request',
                        'A request has been sent by ' . $this->user->first_name . ' ' . $this->user->last_name . '.',
                        'Request',
                        $hrEmployeeIds,
                        null,
                        false,
                        'general-request-hr',
                    );
                }

            DB::commit();
            
            if (!$PayrollAdvance) {
                return response()->json(['status' => false, 'message' => 'SOS not found']);
            }
            return response()->json([
                'success'                               =>  true,
                'message'                               =>  "Request Send Successfully.",
                'data'                                  =>  $PayrollAdvance->load(['guarantors', 'PayrollAdvanceAttachment'])
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        } 
    }

    public function PeopleGuarantorRequestList(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        try {
            $query                                       =   PayrollAdvanceGuarantor::join('payroll_advance as pa', 'payroll_advance_guarantor.payroll_advance_id', '=', 'pa.id')
                                                                ->join('employees as e', 'payroll_advance_guarantor.guarantor_id', '=', 'e.id')
                                                                ->join('resort_admins as ra','e.Admin_Parent_id', '=', 'ra.id')
                                                                ->where('guarantor_id', $this->user->GetEmployee->id)
                                                                ->select('payroll_advance_guarantor.id','payroll_advance_guarantor.payroll_advance_id','payroll_advance_guarantor.guarantor_id','payroll_advance_guarantor.status', 'pa.request_type', 'pa.request_amount', 'pa.currency', 'pa.request_date', 'pa.status', 'ra.first_name', 'ra.last_name', 'ra.profile_picture', 'e.Admin_Parent_id','e.Emp_id')
                                                                ->where('pa.resort_id', $this->resort_id);

            // Default (no ?status=) stays Pending-only to avoid changing
            // behavior for whatever screen already treats this as an
            // action queue. ?status=all returns every status; any other
            // value filters to that specific status.
            $statusFilter = $request->query('status');
            if ($statusFilter === null) {
                $query->where('payroll_advance_guarantor.status', 'Pending');
            } elseif (strtolower($statusFilter) !== 'all') {
                $query->where('payroll_advance_guarantor.status', $statusFilter);
            }

            $guarantorRequests                          =   $query->orderBy('payroll_advance_guarantor.created_at', 'desc')
                                                                ->get()->map(function ($guarantorRequests) {
                                                                    $guarantorRequests->guarantor_profile_picture               =   Common::getResortUserPicture($guarantorRequests->Admin_Parent_id);
                                                                    $guarantorRequests->request_data                            =    PayrollAdvance::join('employees as e', 'payroll_advance.employee_id', '=', 'e.id')
                                                                        ->join('resort_admins as ra','e.Admin_Parent_id', '=', 'ra.id')
                                                                        ->join('resort_departments as rd', 'e.Dept_id', '=', 'rd.id')
                                                                        ->where('payroll_advance.resort_id', $this->resort_id)
                                                                        ->where('payroll_advance.id', $guarantorRequests->payroll_advance_id)
                                                                        ->select('payroll_advance.*','e.Emp_id','ra.first_name', 'ra.last_name', 'rd.name as department_name','e.Admin_Parent_id')
                                                                        ->first();

                                                                    // Was reusing the OUTER guarantor's Admin_Parent_id here —
                                                                    // every requester's photo came back as the guarantor's own
                                                                    // photo. Use request_data's own Admin_Parent_id (the
                                                                    // requester's), not the guarantor row's.
                                                                    $guarantorRequests->request_data->emp_profile_picture       =   Common::getResortUserPicture($guarantorRequests->request_data->Admin_Parent_id);
                                                                    return $guarantorRequests;
                                                                });

            if ($guarantorRequests->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'No requests found'], 200);
            }

            return response()->json([
                'success'                                   =>  true,
                'message'                                   =>  'Request List',
                'data'                                      =>  [
                'requests'                                  =>  $guarantorRequests,
                ],
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        } 
    }
    
    public function PeopleGuarantorRequestHandleAction(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

         $validator = Validator::make($request->all(), [
            'payroll_advance_id'                        =>  'required',
            'guarantor_request_id'                      =>  'required',
            'status'                                    =>  'required|in:Approved,Rejected',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        DB::beginTransaction();

        try {
              $PayrollAdvance                             =   PayrollAdvance::where('id', $request->payroll_advance_id)
                                                                ->where('resort_id', $this->resort_id)
                                                                ->first();

            $guarantorRequests                         =   PayrollAdvanceGuarantor::where('id',$request->guarantor_request_id)
                                                                ->where('payroll_advance_id', $request->payroll_advance_id)
                                                                ->where('guarantor_id', $this->user->GetEmployee->id)
                                                                ->first();
            if (!$guarantorRequests) {
                return response()->json(['success' => false, 'message' => 'No requests found'], 200);
            }

            $guarantorRequests->status                =   $request->status;
            $guarantorRequests->save();

            Common::sendMobileNotification($this->resort_id,2,null,null,"Guarantor Request {$request->status}","Your Guarantor Request {$request->status}","Request",[$PayrollAdvance->employee_id],null,false,'guarantor-request-status');

            // HR's own Approve action is gated on the guarantor status (see
            // AdvanceSalaryController::updateStatus's "Guarantor approval is
            // pending" check), but HR was never told when a guarantor
            // actually responded — the request just sat there with no
            // signal that it had become actionable.
            $hrEmployeeIds = Common::getResortHrEmployeeIds($this->resort_id);
            if (!empty($hrEmployeeIds)) {
                Common::sendMobileNotification(
                    $this->resort_id,
                    2,
                    null,
                    null,
                    'Guarantor Request ' . $request->status,
                    'Guarantor request for ' . ($PayrollAdvance->request_type ?? 'a request') . ' has been ' . strtolower($request->status) . '.',
                    'Request',
                    $hrEmployeeIds,
                    null,
                    false,
                    'guarantor-request-status-hr',
                );
            }

            DB::commit();
            return response()->json([
                'success'                                   =>  true,
                'message'                                   =>  $request->status == 'Approved' ? 'Request Approved Successfully.' : 'Request Rejected Successfully.',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        } 
    }


}
