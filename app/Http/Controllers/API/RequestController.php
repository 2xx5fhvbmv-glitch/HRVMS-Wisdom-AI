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

            // The upload endpoint's response includes payroll_advance_attachment,
            // but this dashboard/listing never surfaced it at all — a file
            // uploaded on submission was invisible ever after.
            $attachmentsByAdvanceId                     =   PayrollAdvanceAttachments::whereIn('payroll_advance_id', $requests->pluck('id'))
                                                                ->get()
                                                                ->groupBy('payroll_advance_id');

            $requests                                   =   $requests->map(function ($req) use ($attachmentsByAdvanceId) {
                $rows                                   =   $attachmentsByAdvanceId->get($req->id, collect());
                $req->attachments                       =   $this->resolveAttachments($rows);
                return $req;
            });

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

    private function resolveAttachments($rows)
    {
        return $rows->flatMap(function ($row) {
            $decoded                                    =   json_decode((string) $row->attachments, true);
            return is_array($decoded) ? $decoded : [];
        })->map(function ($file) {
            $childId                                    =   $file['Child_id'] ?? null;
            $url                                         =   null;
            if ($childId) {
                try {
                    $aws                                 =   Common::GetAWSFile($childId, $this->resort_id);
                    if (!empty($aws['success'])) $url = $aws['NewURLshow'];
                } catch (\Throwable $e) {
                    // leave url null for attachments that fail to resolve
                }
            }
            return ['filename' => $file['Filename'] ?? null, 'url' => $url];
        })->values();
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
                    $PayrollAdvance->id,
                    false,
                    'guarantor-request-new',
                );
            }

            // Mobile app posts the files as "attachments"; older builds used
            // the misspelled "attechments" key, so accept either.
            $attachmentFiles = $request->file('attachments') ?? $request->file('attechments');
            // A failed upload (S3/storage error, bad file, etc.) was silently
            // swallowed — the request still saved and returned "Request Send
            // Successfully" with an empty attachments array, giving the app
            // (and the employee) no indication their file never made it in.
            // Surface it instead so the app can tell the user and let them
            // retry, rather than only finding out later that the attachment
            // is missing.
            $attachmentUploadFailed = false;
            $attachmentUploadError = null;
            if($attachmentFiles) {
                     $imagePaths = [];
                    foreach ($attachmentFiles as $file) {
                        $SubFolder="RequestAttachments";
                        $status =   Common::AWSEmployeeFileUpload($this->resort_id,$file, $this->user->GetEmployee->Emp_id,$SubFolder,true);

                        if ($status['status'] == false) {
                            $attachmentUploadFailed = true;
                            $attachmentUploadError  = $status['msg'] ?? 'Attachment upload failed.';
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
                        $PayrollAdvance->id,
                        false,
                        'general-request-hr',
                    );
                }

                // Advance Salary (loan) requests also need Finance and XCOM
                // in the loop up front — approval later routes through them
                // anyway, so they should see the request the moment it lands,
                // not only at their own approval step.
                if ($request->request_type === 'Payroll Advance') {
                    $financeEmployeeIds = Common::getResortFinanceEmployeeIds($this->resort_id);
                    if (!empty($financeEmployeeIds)) {
                        Common::sendMobileNotification(
                            $this->resort_id,
                            2,
                            null,
                            null,
                            'Request',
                            'An advance salary request has been sent by ' . $this->user->first_name . ' ' . $this->user->last_name . '.',
                            'Request',
                            $financeEmployeeIds,
                            $PayrollAdvance->id,
                            false,
                            'advance-salary-request-finance',
                        );
                    }

                    // Advance Salary approval chain is HR -> Finance -> GM
                    // (see AdvanceSalaryController::updateStatus / the
                    // rank_status column on the resort admin list) — GM is
                    // rank=8, not "XCOM".
                    $gmEmployeeIds = \App\Models\Employee::where('resort_id', $this->resort_id)
                        ->where('rank', 8)
                        ->where(function ($q) {
                            $q->whereNull('status')->orWhere('status', 'Active')->orWhere('status', 'Probationary');
                        })
                        ->pluck('id')
                        ->map(fn($v) => (int) $v)
                        ->all();
                    if (!empty($gmEmployeeIds)) {
                        Common::sendMobileNotification(
                            $this->resort_id,
                            2,
                            null,
                            null,
                            'Request',
                            'An advance salary request has been sent by ' . $this->user->first_name . ' ' . $this->user->last_name . '.',
                            'Request',
                            $gmEmployeeIds,
                            $PayrollAdvance->id,
                            false,
                            'advance-salary-request-gm',
                        );
                    }
                }

            DB::commit();

            if (!$PayrollAdvance) {
                return response()->json(['status' => false, 'message' => 'SOS not found']);
            }
            $PayrollAdvance->load(['guarantors', 'PayrollAdvanceAttachment']);
            // PayrollAdvanceAttachment (the raw relation, loaded above for
            // compatibility) only carries the un-resolved Child_id — no
            // usable URL, so the app can't display/download the file it
            // just uploaded. requestDashboard()/salaryAdvanceDetails() both
            // already resolve this properly; do the same here.
            $PayrollAdvance->attachments = $this->resolveAttachments($PayrollAdvance->PayrollAdvanceAttachment);
            return response()->json([
                'success'                               =>  true,
                'message'                               =>  $attachmentUploadFailed
                                                                ? "Request Send Successfully, but your attachment could not be uploaded. Please try attaching it again."
                                                                : "Request Send Successfully.",
                'attachment_upload_failed'              =>  $attachmentUploadFailed,
                'attachment_upload_error'               =>  $attachmentUploadFailed ? $attachmentUploadError : null,
                'data'                                  =>  $PayrollAdvance
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        } 
    }

    // Nothing in the app previously listed employees for the "Select
    // Guarantor Employee" picker — mobile had no endpoint to search against,
    // hence no search box on that field.
    public function GuarantorEmployeeList(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        try {
            $selfEmployeeId                              =   $this->user->GetEmployee->id;
            $search                                       =   trim((string) $request->query('search', ''));

            $query                                        =   Employee::join('resort_admins as ra', 'ra.id', '=', 'employees.Admin_Parent_id')
                                                                ->join('resort_positions as rp', 'rp.id', '=', 'employees.Position_id')
                                                                ->where('employees.resort_id', $this->resort_id)
                                                                ->where('employees.status', 'Active')
                                                                ->where('employees.id', '!=', $selfEmployeeId)
                                                                ->select(
                                                                    'employees.id',
                                                                    'employees.Emp_id',
                                                                    'employees.Admin_Parent_id',
                                                                    'ra.first_name',
                                                                    'ra.last_name',
                                                                    'rp.position_title'
                                                                );

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('ra.first_name', 'like', "%$search%")
                        ->orWhere('ra.last_name', 'like', "%$search%")
                        ->orWhere('employees.Emp_id', 'like', "%$search%")
                        ->orWhereRaw("CONCAT(ra.first_name, ' ', ra.last_name) like ?", ["%$search%"]);
                });
            }

            $employees                                    =   $query->orderBy('ra.first_name')
                                                                ->get()
                                                                ->map(function ($e) {
                                                                    $e->employee_name    = trim($e->first_name . ' ' . $e->last_name);
                                                                    $e->profile_picture  = Common::getResortUserPicture($e->Admin_Parent_id);
                                                                    return $e;
                                                                })
                                                                ->values();

            return response()->json([
                'success'                                 =>  true,
                'message'                                 =>  'Employee list fetched successfully',
                'data'                                    =>  $employees,
            ], 200);

        } catch (\Exception $e) {
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
                                                                // 'pa.status' was selected alongside
                                                                // 'payroll_advance_guarantor.status' with both bare-named
                                                                // "status" — PDO's associative fetch keeps whichever
                                                                // column comes LAST for a repeated key, so the overall
                                                                // request's status (pa.status, e.g. still "Pending" until
                                                                // HR/Finance/GM act) silently overwrote the guarantor's
                                                                // own Approved/Rejected decision on every response. The
                                                                // overall request status is already available separately
                                                                // via request_data.status below, so pa.status is dropped
                                                                // here rather than aliased — it was never actually used
                                                                // as a distinct field.
                                                                ->select('payroll_advance_guarantor.id','payroll_advance_guarantor.payroll_advance_id','payroll_advance_guarantor.guarantor_id','payroll_advance_guarantor.status', 'pa.request_type', 'pa.request_amount', 'pa.currency', 'pa.request_date', 'ra.first_name', 'ra.last_name', 'ra.profile_picture', 'e.Admin_Parent_id','e.Emp_id')
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

            Common::sendMobileNotification($this->resort_id,2,null,null,"Guarantor Request {$request->status}","Your Guarantor Request {$request->status}","Request",[$PayrollAdvance->employee_id],$PayrollAdvance->id,false,'guarantor-request-status');

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
                    $PayrollAdvance->id,
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

    /**
     * GET request/salary-advance-details/{id}
     * Single request's full detail — request info, guarantor, repayment
     * schedule and deduction history. requestDashboard() only ever
     * returned the list/summary shape; nothing exposed the recovery
     * schedule or guarantor approval info to mobile at all.
     */
    public function salaryAdvanceDetails($id)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $employeeId = $this->user->GetEmployee->id;

            $advance = PayrollAdvance::with([
                    // request_amount/currency accepts an ARRAY of guarantor_id
                    // on submit (RequestStore), but this endpoint only ever
                    // loaded the singular latest-updated `guarantor` relation —
                    // a request with 2+ guarantors silently dropped all but one
                    // from the detail view.
                    'guarantors.employee.resortAdmin:id,first_name,last_name',
                    'payrollRecoverySchedule',
                    'PayrollAdvanceAttachment',
                ])
                ->where('resort_id', $this->resort_id)
                ->where('employee_id', $employeeId)
                ->find($id);

            if (!$advance) {
                return response()->json(['success' => false, 'message' => 'Request not found.'], 200);
            }

            $guarantorsInformation = $advance->guarantors->map(function ($g) {
                $guarantorAdmin = optional($g->employee)->resortAdmin;
                return [
                    'guarantor_id'              => $g->guarantor_id,
                    'guarantor_name'            => $guarantorAdmin ? trim($guarantorAdmin->first_name . ' ' . $guarantorAdmin->last_name) : null,
                    'guarantor_approval_status' => $g->status,
                    // payroll_advance_guarantor has no dedicated approval-date
                    // column — updated_at only changes when status is actually
                    // set (create/save both touch it, but a still-Pending row
                    // means no approval has happened yet), so it's null until
                    // the guarantor has actually responded.
                    'guarantor_approval_date'   => $g->status !== 'Pending' ? $g->updated_at : null,
                ];
            })->values();

            $attachments = $this->resolveAttachments($advance->PayrollAdvanceAttachment);

            // Web portal's "Deduction History" section (AdvanceSalaryRepaymentTrackerController)
            // re-labels this SAME payroll_recovery_schedule collection —
            // there is no separate deduction ledger tied to a salary advance.
            $repaymentSchedule = $advance->payrollRecoverySchedule->map(function ($s) {
                return [
                    'month'  => $s->repayment_date ? Carbon::parse($s->repayment_date)->format('F Y') : null,
                    'amount' => $s->amount,
                    'remark' => $s->remark,
                ];
            })->values();

            $deductionHistory = $advance->payrollRecoverySchedule->map(function ($s) {
                return [
                    'payroll_month'   => $s->repayment_date ? Carbon::parse($s->repayment_date)->format('F Y') : null,
                    'deducted_amount' => $s->amount,
                    'status'          => $s->status === 'Paid' ? 'Completed' : 'Pending',
                ];
            })->values();

            return response()->json([
                'success' => true,
                'message' => 'Salary advance details fetched successfully',
                'data'    => [
                    'request_information' => [
                        'requested_date'  => $advance->request_date,
                        'status'          => $advance->status,
                        'priority'        => $advance->priority,
                        'amount'          => $advance->request_amount,
                        'currency'        => $advance->currency,
                        'purpose'         => $advance->pourpose,
                        'recovery_status' => $advance->recovery_status,
                    ],
                    'guarantors_information' => $guarantorsInformation,
                    'attachments'           => $attachments,
                    'repayment_schedule'    => $repaymentSchedule,
                    'deduction_history'     => $deductionHistory,
                ],
            ], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

}
