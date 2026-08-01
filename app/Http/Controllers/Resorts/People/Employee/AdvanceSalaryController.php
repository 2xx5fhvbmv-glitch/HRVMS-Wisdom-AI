<?php

namespace App\Http\Controllers\Resorts\People\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Employee;
use App\Models\ResortAdmin;
use App\Models\PayrollAdvance;
use App\Models\PayrollAdvanceGuarantor;
use App\Models\PayrollRecoverySchedule;
use App\Models\ResortPosition;
use App\Events\ResortNotificationEvent;
use Auth;
use Config;
use Common;
use DB;
use Carbon\Carbon;

class AdvanceSalaryController extends Controller
{
    public $resort;
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;

    }

    public function index()
    {   
      
        $page_title ='Salary Advance/Loan Request';
        $resort_id = $this->resort->resort_id;
        $employees = Employee::with(['resortAdmin','position','department'])->where('resort_id',$resort_id)->get();

        return view('resorts.people.employee.advance-salary.list',compact('page_title','resort_id'));
    }

    public function list(Request $request)
    {
        if($request->ajax())
        {
            $resort_id = $this->resort->resort_id;
            $rank = config('settings.Position_Rank');
            $current_rank = $this->resort->getEmployee->rank ?? null;
            $Dept_id = $this->resort->getEmployee->Dept_id ?? null;

            // HR/Finance-department HOD/EXCOM (raw rank 1/2) mapped to
            // "HOD"/"EXCOM" here instead of "HR"/"Finance", so real HR/
            // Finance staff (rarely literal rank 3/7) never got the
            // stage-scoped query below. Promote the same way other
            // modules derive an effective rank from department.
            if (!in_array($current_rank, [3, 7, 8], true)) {
                if (Common::isHRDepartment($Dept_id)) {
                    $current_rank = 3;
                } elseif (Common::isFinanceDepartment($Dept_id)) {
                    $current_rank = 7;
                }
            }
            $available_rank = $rank[$current_rank] ?? '';

            $isHR = ($available_rank === "HR");
            $isFinance = ($available_rank === "Finance");
            $isGM = ($available_rank === "GM");

            // dd($available_rank);
            // Base resort-scoped query — always defined so the variable can't be
            // undefined for roles outside HR/Finance/GM/master (e.g. a Finance
            // HOD/XCOM whose RANK maps to "HOD"/"EXCOM", not "Finance"), which
            // previously threw and surfaced as a DataTables "Ajax error".
            $payroll_data_query = PayrollAdvance::where('resort_id', $resort_id)
                ->with(['employee.resortAdmin', 'employee.position', 'employee.department'])
                ->whereHas('employee.resortAdmin')
                ->orderBy('created_at', 'DESC');

            // Stage filters: Finance sees HR-approved; GM sees Finance-approved.
            // HR / master / other authorised roles see the full resort list.
            if ($isFinance) {
                $payroll_data_query->where('hr_status', 'Approved');
            } elseif ($isGM) {
                $payroll_data_query->where('finance_status', 'Approved');
            }

            if($request->has('status') && $request->status != 'n/a'){
                $payroll_data_query = $payroll_data_query->where('status', $request->status);
            }
            
            if($request->searchTerm != null){
                $searchTerm = $request->searchTerm;
                $payroll_data_query = $payroll_data_query->whereHas('employee.resortAdmin', function ($query) use ($searchTerm) {   
                    $query->where('first_name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('last_name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('Emp_id', 'like', '%' . $searchTerm . '%')
                        ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', '%' . $searchTerm . '%');
                });
            }

            if($request->has('date') && $request->date != ''){
                $date = Carbon::createFromFormat('d/m/Y', $request->date)->format('Y-m-d');
                
                $payroll_data_query = $payroll_data_query->whereDate('request_date', $date);
            }

            $payroll_data = $payroll_data_query->get();

            return datatables()->of($payroll_data)
                ->addColumn('Emp_id', function ($payroll_data) {
                    return $payroll_data->employee->Emp_id;
                })
                ->addColumn('employee_name', function ($payroll_data) {
                    return $payroll_data->employee->resortAdmin->full_name ?? 'N/A';
                })
                ->addColumn('position', function ($payroll_data) {
                    return $payroll_data->employee->position->position_title ?? 'N/A';
                })
                ->editColumn('request_amount', function ($payroll_data) {
                    // The employee picks MVR or USD as the currency they
                    // want the advance IN (payroll_advance.currency) — this
                    // is not a "stored in USD, display in resort currency"
                    // value, so it must show its own literal currency, not
                    // run through formatCurrency()'s resort-display
                    // conversion (which always hardcoded '$' here).
                    return Common::formatRequestCurrency($payroll_data->request_amount, $payroll_data->currency ?: 'USD');
                })
                ->addColumn('department', function ($payroll_data) {
                    return $payroll_data->employee->department->name ?? 'N/A';
                })
                ->editColumn('request_date', function ($payroll_data) {
                    return $payroll_data->request_date ? Carbon::parse($payroll_data->request_date)->format('d M Y') : '';
                })
                ->editColumn('status', function ($payroll_data) {
                    $status = $payroll_data->status;
                    $badgeClass = match ($status) {
                        'Approved' => 'themeSuccess',
                        'Pending' => 'themeWarning',
                        'Rejected' => 'themeDanger',
                        'In-Progress' => 'themePrimary',
                        default => 'themeInfo',
                    };
                    return '<span class="badge badge-' . $badgeClass . '">' . $status . '</span>';
                })
                ->editColumn('rank_status', function ($payroll_data) {
                    $hr_status = $payroll_data->hr_status;
                    $finance_status = $payroll_data->finance_status;
                    $gm_status = $payroll_data->gm_status;

                    $hr_badge = '<span class="badge badge-' . ($hr_status == 'Approved' ? 'themeSuccess' : ($hr_status == 'Pending' ? 'themeWarning' : ($hr_status == 'Rejected' ? 'themeDanger' : 'themeInfo'))) . '">' . $hr_status . '</span>';
                    $finance_badge = '<span class="badge badge-' . ($finance_status == 'Approved' ? 'themeSuccess' : ($finance_status == 'Pending' ? 'themeWarning' : ($finance_status == 'Rejected' ? 'themeDanger' : 'themeInfo'))) . '">' . $finance_status . '</span>';
                    $gm_badge = '<span class="badge badge-' . ($gm_status == 'Approved' ? 'themeSuccess' : ($gm_status == 'Pending' ? 'themeWarning' : ($gm_status == 'Rejected' ? 'themeDanger' : 'themeInfo'))) . '">' . $gm_status . '</span>';

                    return 'HR: ' . $hr_badge . '<br><br> Finance: ' . $finance_badge . '<br><br> GM: ' . $gm_badge;
                })

                ->addColumn('action', function ($payroll_data) {
                    $id = base64_encode($payroll_data->id);
                    return '
                        <div class="d-flex align-items-center">
                            <a href="'.route('people.advance-salary.show',$id).'" class="a-link edit-row-btn">
                               View Details
                            </a>
                        </div>';
                })
                ->rawColumns(['status','rank_status','action']) 
                ->make(true);
        }
    }

    public function show($id){

        if(Common::checkRouteWisePermission('people.advance-salary.index',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }
        $id = base64_decode($id);
        $resort_id = $this->resort->resort_id;
        $page_title ='Salary Advance/Loan Request Approval';
        
        $rank = config('settings.Position_Rank');
        $current_rank = $this->resort->getEmployee->rank ?? null;
        $Dept_id = $this->resort->getEmployee->Dept_id ?? null;

        // Same effective-rank promotion as list() above — without it, no
        // real HR/Finance employee at this resort (raw rank rarely
        // literally 3/7) ever saw the Approve/Reject/Hold buttons below.
        if (!in_array($current_rank, [3, 7, 8], true)) {
            if (Common::isHRDepartment($Dept_id)) {
                $current_rank = 3;
            } elseif (Common::isFinanceDepartment($Dept_id)) {
                $current_rank = 7;
            }
        }
        $available_rank = $rank[$current_rank] ?? '';

        $isHR = ($available_rank === "HR");
        $isFinance = ($available_rank === "Finance");
        $isGM = ($available_rank === "GM");

        $advance_salary = PayrollAdvance::with(['employee.resortAdmin','employee.position','employee.department'])->wherehas('employee.resortAdmin')->where('id',$id)->first();
        $guarantors = PayrollAdvanceGuarantor::where('payroll_advance_id',$id)->get();
        $recovery_schedule = PayrollRecoverySchedule::where('payroll_advance_id',$id)->get();

        $request_attachment  =   config('settings.RequestAttachments');
        $attechment_path     =   $request_attachment . '/' . $advance_salary->employee->resort_id.'/'.$advance_salary->employee->Emp_id;

        $total_interest = 0;
        $actual_amount = 0; 
        $total_recovery = 0;
        if($recovery_schedule){
            $total_interest = $recovery_schedule->sum('interest_amount');
            $actual_amount = $advance_salary->request_amount;
            $total_recovery = $actual_amount + $total_interest;

        }
        return view('resorts.people.employee.advance-salary.show',compact('page_title','advance_salary','guarantors','recovery_schedule','total_interest','actual_amount','total_recovery','attechment_path','isHR','isFinance','isGM'));
    }
    
    public function paymentReschedule(Request $request){
       
        $current_month = date('m');
        $current_year = date('Y');
        $total_months = $request->months;

        $payroll_advance_data = PayrollAdvance::where('id', $request->advance_salary_id)->first();
        $employee = Employee::where('id', $payroll_advance_data->employee_id)->first();
        $amount = $payroll_advance_data->request_amount;

        $monthly_installment = round($amount / $total_months, 2);
        $remaining_balance = $amount;
        $month_year_array = [];

        if ($monthly_installment > $employee->basic_salary) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Monthly EMI cannot be greater than the employee\'s current salary.',
            ]);
        }

        $availableMonths = [];
        $currentMonth = Carbon::now();
        for ($i = 0; $i < 24; $i++) {
            $availableMonths[] = $currentMonth->copy()->addMonths($i)->format('F Y');
        }
       
        for ($i = 0; $i < $total_months; $i++) {
            $next_month = $current_month + $i + 1;
            $year = $current_year + floor(($next_month - 1) / 12);
            $month = ($next_month - 1) % 12 + 1;
            $month_name = Carbon::createFromDate($year, $month, 1)->format('F');

            $installment_amount = $monthly_installment;
            $remaining_balance -= $installment_amount;

            
            $month_year_array[] = [
                'month' => $month_name . ' ' . $year,
                'interest' => '',
                'installment_amount' => round($installment_amount,2),
                'remaining_balance' => round($remaining_balance)
            ];
        }

        $response = [
            'success' => true,
            'status' => 'success',
            'message' => 'Schedule generated successfully.',
            'data' => $month_year_array,
            'html' => view('resorts.people.employee.advance-salary.payment_schedule', compact('total_months', 'month_year_array','payroll_advance_data','availableMonths'))->render()
        ];
        return response()->json($response);

    }

    public function paymentRescheduleCalculate(Request $request){

        $payroll_advance_data = PayrollAdvance::where('id', $request->data[0]['payrollAdvanceId'])->first();
        
        $amount = $payroll_advance_data->request_amount;
        $total_months = count($request->data);
        $remaining_balance = $amount;
        $total_amount = 0;
        $total_interest = 0;
        $month_year_array = [];

         $availableMonths = [];
            $currentMonth = Carbon::now();
            for ($i = 0; $i < 24; $i++) {
                $availableMonths[] = $currentMonth->copy()->addMonths($i)->format('F Y');
            }

        foreach($request->data as $key => $value){
            $interest_rate = $value['interest'];
            $principal = $amount / $total_months;
            $interest_amount = ($remaining_balance * $interest_rate) / 100;
            $installment_amount = $principal + $interest_amount;
            $remaining_balance -= $principal;
            if ($key == $total_months - 1)
            {
                $remaining_balance = 0;
            } 

            $total_amount += $installment_amount;
            $total_interest += $interest_amount;

            $month_year_array[] = [
                'month' => $value['month'],
                'interest' => round($interest_rate, 2),
                'installment_amount' => round($installment_amount, 2),
                'remaining_balance' => round($remaining_balance, 2)
            ];
        }

        $response = [
            'status' => 'success',
            'message' => 'Schedule generated successfully.',
            'total_amount' => round($total_amount),
            'total_interest' => round($total_interest),
            'html' => view('resorts.people.employee.advance-salary.payment_schedule', compact('month_year_array','payroll_advance_data','availableMonths'))->render()
        ];
        return response()->json($response);
    }


    public function paymentRescheduleStore(Request $request){

        if(!isset($request->data) && empty($request->data)){
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Please generate schedule first.',
            ]);
        }
        $payrollAdvanceId = $request->data[0]['payrollAdvanceId'];
        $payrollAdvance = PayrollAdvance::where('id', $payrollAdvanceId)->first();

        // Check for duplicate months in repayment schedule
        foreach($request->data as $key => $value){
            

            $monthYear = $value['month'];
            $monthYearArray = explode(' ', $monthYear);
            $month = $monthYearArray[0]; 
            $year = $monthYearArray[1]; 
            
            $repayment_date = Carbon::parse("5 $month $year")->format('Y-m-d');

            $actual_amount_without_interest = $value['installment'] / (1 + ($value['interest'] / 100));

            $interest_amount = $value['installment'] - $actual_amount_without_interest;

            $recovery_schedule = PayrollRecoverySchedule::create([
                'payroll_advance_id' => $payrollAdvanceId,
                'employee_id' => $payrollAdvance->employee_id,
                'repayment_date' => $repayment_date,
                'amount' => $value['installment'],
                'interest' => $value['interest'],
                'interest_amount' => $interest_amount,
                'remaining_balance' => $value['remaining_balance'],
                'status' => 'Pending',
            ]);
        }

        // 🔔 Notify Employee 
        event(new ResortNotificationEvent(Common::nofitication(
            $this->resort->resort_id,
            10,
            $payrollAdvance->request_type . ' Repayment Schedule',
            "Your " . $payrollAdvance->request_type . " request for amount " . $payrollAdvance->request_amount . " has been accepted and the repayment schedule has been generated.",
            0,
            $payrollAdvance->employee_id,
            'People'
        )));

        $response = [
            'success' => true,
            'status' => 'success',
            'message' => 'Schedule generated successfully.',
            'redirect_url' => route('people.advance-salary.index'),
        ];
        return response()->json($response);
    }


    // HR is the resort's single point of contact for a loan request, so it
    // stays looped in on every stage's outcome (its own, Finance's, GM's) —
    // not only notified when it's the one acting.
    private function notifyHR($payrollAdvance, $title, $message)
    {
        $hrEmployeeIds = Common::getResortHrEmployeeIds($this->resort->resort_id);
        foreach ($hrEmployeeIds as $hrId) {
            event(new ResortNotificationEvent(Common::nofitication(
                $this->resort->resort_id,
                10,
                $title,
                $message,
                0,
                $hrId,
                'People'
            )));
        }
    }

    // In-app bell (ResortNotificationEvent above) doesn't reach the mobile
    // app — only Common::sendMobileNotification does the actual push.
    // skipDbInsert=true: the bell row is already written via the
    // nofitication() call alongside this one, so this only fires the push.
    private function notifyEmployeePush($payrollAdvance, $title, $message, $pageId)
    {
        Common::sendMobileNotification(
            $this->resort->resort_id,
            2,
            null,
            null,
            $title,
            $message,
            'People',
            [$payrollAdvance->employee_id],
            $payrollAdvance->id,
            true,
            $pageId
        );
    }

    // Update Status Handler
    public function updateStatus(Request $request){

        $payrollAdvance = PayrollAdvance::where('id', $request->advance_salary_id)->first();

        $rank = config('settings.Position_Rank');
        $current_rank = $this->resort->GetEmployee->rank ?? null;
        $available_rank = $rank[$current_rank] ?? '';
  
        $isHR = ($available_rank === "HR"); 

        if($payrollAdvance){
            $employeeName = $payrollAdvance->employee->resortAdmin->full_name ?? 'the employee';

            if($request->action_by == 'hr' && $payrollAdvance->hr_status == 'Pending'){

                if($payrollAdvance->guarantor->status == 'Pending'){
                    return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'message' => 'Guarantor approval is pending.',
                    ]);
                }
                // HR check — check direct or via delegation. Raw rank=3
                // excluded any resort whose real HR employee isn't literally
                // rank 3 (e.g. HR-department HOD/EXCOM), incorrectly denying
                // the actual HR approver access to their own approval step.
                $hrEmployeeIds = Common::getResortHrEmployeeIds($this->resort->resort_id);
                $hr = in_array($this->resort->GetEmployee->id, $hrEmployeeIds, true) ? $this->resort->GetEmployee : null;
                if (!$hr) {
                    // Check if current user is a delegate for any HR employee on leave
                    $hrEmployees = $hrEmployeeIds;
                    foreach ($hrEmployees as $hrEmpId) {
                        if (\App\Helpers\Common::hasDelegationAuthority($this->resort->GetEmployee->id, $hrEmpId, $this->resort->resort_id)) {
                            $hr = Employee::find($hrEmpId);
                            break;
                        }
                    }
                }

                if (!$hr) {
                    return response()->json([
                        'success' => false,
                        'status' => 'error',
                        'message' => 'You are not authorized to approve this request as HR.',
                    ], 403);
                }

                if($request->status == 'Approved' ){
                    if($payrollAdvance->guarantor->status != 'Approved'){
                            return response()->json([
                                'success' => false,
                                'status' => 'error',
                                'message' => 'Guarantor is not Approved.',
                            ]);
                    }
                    $payrollAdvance->update([
                    'hr_status' => 'Approved',
                    'hr_approved_by' => $hr->id,
                    'hr_action_date' => Carbon::now(),
                    'status' => 'In-Progress',
                    ]);

                    
                    event(new ResortNotificationEvent(Common::nofitication(
                    $this->resort->resort_id,
                    10,
                    $payrollAdvance->request_type .' Approved',
                    " Your ".$payrollAdvance->request_type." request  for amount " . $payrollAdvance->request_amount . " has been Approved.",
                    0,
                    $payrollAdvance->employee_id,
                    'People'
                    )));

                    $this->notifyEmployeePush($payrollAdvance, $payrollAdvance->request_type . ' Approved', "Your {$payrollAdvance->request_type} request for amount {$payrollAdvance->request_amount} has been approved by HR.", 'advance-salary-hr-approved');

                    $this->notifyHR($payrollAdvance, $payrollAdvance->request_type . ' Approved (HR)', "HR approved {$employeeName}'s {$payrollAdvance->request_type} request for amount {$payrollAdvance->request_amount}.");

                } elseif($request->status == 'Rejected'){
                    $payrollAdvance->update([
                    'hr_status' => 'Rejected',
                    'reject_reason' => $request->reject_reason,
                    'hr_approved_by' => $hr->id,
                    'status'=> 'Rejected',
                    'finance_action_date' => Carbon::now(),
                    ]);
                    //  // 🔔 Notify Employee
                    event(new ResortNotificationEvent(Common::nofitication(
                        $this->resort->resort_id,
                        10,
                        $payrollAdvance->request_type .' Rejected',
                        "❌ Your ".$payrollAdvance->request_type." request  for amount " . $payrollAdvance->request_amount . " has been rejected. for the reason: " . $request->reject_reason,
                        0,
                        $payrollAdvance->employee_id,
                        'People'
                    )));

                    $this->notifyHR($payrollAdvance, $payrollAdvance->request_type . ' Rejected (HR)', "HR rejected {$employeeName}'s {$payrollAdvance->request_type} request for amount {$payrollAdvance->request_amount}. Reason: {$request->reject_reason}");

                }
            }elseif($request->action_by == 'finance' && $payrollAdvance->finance_status == 'Pending'){

                $financeManagerTitles = ['Director of Finance', 'Finance Manager'];
                $positionIds = ResortPosition::where('resort_id', $this->resort->resort_id)
                    ->whereIn('position_title', $financeManagerTitles)
                    ->pluck('id');

                $financeApprover = Employee::with(['resortAdmin', 'position'])
                    ->whereIn('position_id', $positionIds)
                    ->where('resort_id', $this->resort->resort_id)
                    ->where('Admin_Parent_id',$this->resort->id)
                    ->select('id')
                    ->first();

                // If current user is not the finance approver, check delegation
                if ($financeApprover && $financeApprover->id != ($this->resort->GetEmployee->id ?? null)) {
                    if (!\App\Helpers\Common::hasDelegationAuthority($this->resort->GetEmployee->id, $financeApprover->id, $this->resort->resort_id)) {
                        $financeApprover = null;
                    }
                }
                if (!$financeApprover) {
                    return response()->json(['success' => false, 'status' => 'error', 'message' => 'You are not authorized to approve as Finance.'], 403);
                }

                if($request->status == 'Approved'){
                    $payrollAdvance->update([
                    'finance_status' => 'Approved',
                    'finance_approved_by' => $financeApprover->id,
                    'finance_action_date' => Carbon::now(),
                    'status' => 'In-Progress',
                    ]);

                    event(new ResortNotificationEvent(Common::nofitication(
                    $this->resort->resort_id,
                    10,
                    $payrollAdvance->request_type .' Approved',
                    " Your ".$payrollAdvance->request_type." request  for amount " . $payrollAdvance->request_amount . " has been Approved.",
                    0,
                    $payrollAdvance->employee_id,
                    'People'
                    )));

                    $this->notifyEmployeePush($payrollAdvance, $payrollAdvance->request_type . ' Approved', "Your {$payrollAdvance->request_type} request for amount {$payrollAdvance->request_amount} has been approved by Finance.", 'advance-salary-finance-approved');

                    $this->notifyHR($payrollAdvance, $payrollAdvance->request_type . ' Approved (Finance)', "Finance approved {$employeeName}'s {$payrollAdvance->request_type} request for amount {$payrollAdvance->request_amount}.");

                } elseif($request->status == 'Rejected'){
                    $payrollAdvance->update([
                        'finance_status' => 'Rejected',
                        'reject_reason' => $request->reject_reason,
                        'finance_approved_by' => $financeApprover->id,
                        'status'=> 'Rejected',
                        'finance_action_date' => Carbon::now(),
                    ]);
                    event(new ResortNotificationEvent(Common::nofitication(
                        $this->resort->resort_id,
                        10,
                        $payrollAdvance->request_type .' Rejected',
                        "❌ Your ".$payrollAdvance->request_type." request  for amount " . $payrollAdvance->request_amount . " has been rejected. for the reason: " . $request->reject_reason,
                        0,
                        $payrollAdvance->employee_id,
                        'People'
                    )));

                    $this->notifyHR($payrollAdvance, $payrollAdvance->request_type . ' Rejected (Finance)', "Finance rejected {$employeeName}'s {$payrollAdvance->request_type} request for amount {$payrollAdvance->request_amount}. Reason: {$request->reject_reason}");

                }
            }elseif($request->action_by == 'gm' && $payrollAdvance->gm_status == 'Pending'){
            
                $gmApprover = Employee::with('position')
                    ->where('rank', 8)
                    ->where('resort_id', $this->resort->resort_id)
                    ->where('Admin_Parent_id',$this->resort->id)
                    ->select('id')
                    ->first();

                // If current user is not the GM, check delegation
                if ($gmApprover && $gmApprover->id != ($this->resort->GetEmployee->id ?? null)) {
                    if (!\App\Helpers\Common::hasDelegationAuthority($this->resort->GetEmployee->id, $gmApprover->id, $this->resort->resort_id)) {
                        $gmApprover = null;
                    }
                }
                if (!$gmApprover) {
                    return response()->json(['success' => false, 'status' => 'error', 'message' => 'You are not authorized to approve as GM.'], 403);
                }

                $recovery_schedule = PayrollRecoverySchedule::where('payroll_advance_id', $payrollAdvance->id)
                    ->update(['status' => 'Pending']);

                if($request->status == 'Approved'){
                    $payrollAdvance->update([
                        'gm_status' => 'Approved',
                        'gm_approved_by' => $gmApprover->id,
                        'status' => 'Approved',
                        'gm_action_date' => Carbon::now(),
                    ]);

                    event(new ResortNotificationEvent(Common::nofitication(
                    $this->resort->resort_id,
                    10,
                    $payrollAdvance->request_type .' Approved',
                    " Your ".$payrollAdvance->request_type." request  for amount " . $payrollAdvance->request_amount . " has been Approved.",
                    0,
                    $payrollAdvance->employee_id,
                    'People'
                    )));

                    $this->notifyEmployeePush($payrollAdvance, $payrollAdvance->request_type . ' Fully Approved', "Your {$payrollAdvance->request_type} request for amount {$payrollAdvance->request_amount} has been fully approved.", 'advance-salary-fully-approved');

                    $this->notifyHR($payrollAdvance, $payrollAdvance->request_type . ' Approved (GM)', "GM approved {$employeeName}'s {$payrollAdvance->request_type} request for amount {$payrollAdvance->request_amount}.");

                } elseif($request->status == 'Rejected'){
                    $payrollAdvance->update([
                    'gm_status' => 'Rejected',
                    'reject_reason' => $request->reject_reason,
                    'gm_approved_by' => $gmApprover->id,
                    'gm_action_date' => Carbon::now(),
                    'status'=> 'Rejected',
                    'action_date' => Carbon::now(),
                    ]);


                    event(new ResortNotificationEvent(Common::nofitication(
                    $this->resort->resort_id,
                    10,
                    $payrollAdvance->request_type .' Rejected',
                    "❌ Your ".$payrollAdvance->request_type." request  for amount " . $payrollAdvance->request_amount . " has been rejected. for the reason: " . $request->reject_reason,
                    0,
                    $payrollAdvance->employee_id,
                    'People'
                    )));

                    $this->notifyHR($payrollAdvance, $payrollAdvance->request_type . ' Rejected (GM)', "GM rejected {$employeeName}'s {$payrollAdvance->request_type} request for amount {$payrollAdvance->request_amount}. Reason: {$request->reject_reason}");
                }
            }

        }

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'Status updated successfully.',
            'redirect_url' => route('people.advance-salary.index'),
        ]);

    }

}