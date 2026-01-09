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
use App\Models\ResortDepartment;
use App\Models\ResortPosition;
use Barryvdh\DomPDF\Facade\Pdf;
use Auth;
use Config;
use Common;
use DB;
use Carbon\Carbon;

class AdvanceSalaryRepaymentTrackerController extends Controller
{
    public $resort;
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        
    }

    public function index()
    {   
       
        $page_title ='Loan & Salary Advance Repayment Tracker';
        $resort_id = $this->resort->resort_id;
        $resort = $this->resort;
          
        $positions = ResortPosition::where('resort_id',$resort->resort_id)->where('status','active')->get();
        $departments = ResortDepartment::where('resort_id',$resort->resort_id)->where('status','active')->get();

        return view('resorts.people.employee.advance-salary-repayment-tracker.list',compact('page_title','resort_id','positions','departments'));
    }

    public function list(Request $request)
    {
        if($request->ajax())
        {
            $resort_id = $this->resort->resort_id;
            

            $query = PayrollAdvance::where('resort_id',$resort_id)->where('status','Approved')->with(['employee.resortAdmin','employee.position','employee.department','payrollRecoverySchedule'])
                            ->whereHas('employee.resortAdmin');
                            
                      
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
                $query->where('recovery_status',$request->status);
            }
                            
            $payroll_data = $query->get();
        

            return datatables()->of($payroll_data)
                ->addColumn('Emp_id', function ($payroll_data) {
                    return $payroll_data->employee->Emp_id;
                })
                ->addColumn('employee_name', function ($payroll_data) {
                    return $payroll_data->employee->resortAdmin->full_name;
                })
                ->addColumn('position', function ($payroll_data) {
                    return $payroll_data->employee->position->position_title;
                })
                ->addColumn('department', function ($payroll_data) {
                    return $payroll_data->employee->department->name;
                })
                ->addColumn('last_payment', function ($payroll_data) {
                    $last_payment = $payroll_data->payrollRecoverySchedule->where('status','Paid')->last();
                    if ($last_payment) {
                        return Carbon::parse($last_payment->repayment_date)->format('F');
                    }else {
                        return '-';
                    }   
                })

                ->editColumn('recovery_status', function ($payroll_data) {
                    $status = $payroll_data->recovery_status;
                    return '<span class="badge badge-' . ($status == 'Completed' ? 'themeSuccess' : ($status == 'Pending' || $status == 'In Progress' ? 'themeWarning' : ($status == 'Rejected' ? 'themeDanger' : ($status == 'Scheduled' ? 'themeSkyblue' : 'themeInfo')))) . '">' . $status . '</span>';
                })
            
                ->addColumn('action', function ($payroll_data) {
                    $id = base64_encode($payroll_data->id);
                    return '
                        <div class="d-flex align-items-center">
                            <a href="'.route('people.advance-salary-repayment-tracker.show',$id).'" class="a-link edit-row-btn">
                               View Details
                            </a>
                        </div>';
                })
                ->rawColumns(['last_payment','recovery_status','action']) 
                ->make(true);
        }
    }

    public function show($id){
        if(Common::checkRouteWisePermission('people.advance-salary-repayment-tracker.index',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }
        $id = base64_decode($id);
        $resort_id = $this->resort->resort_id;
        $page_title ='Salary Advance/Loan Request Approval';

        $availableMonths = [];
        $currentMonth = Carbon::now();
        for ($i = 0; $i < 36; $i++) {
            $availableMonths[] = $currentMonth->copy()->addMonths($i)->format('F Y');
        }
        $payrollAdvance =  PayrollAdvance::where('resort_id',$resort_id)->where('id',$id)->with(['employee.resortAdmin','employee.position','employee.department','payrollRecoverySchedule','hrApprover','financeApprover','gmApprover'])
                            ->whereHas('employee.resortAdmin')->first();
                            
        return view('resorts.people.employee.advance-salary-repayment-tracker.show',compact('page_title','payrollAdvance','availableMonths'));
    }
    

    // Update recovery payment Data
    public function update(Request $request){

        $recovery_schedule = PayrollRecoverySchedule::find($request->schedule_id);

        if ($recovery_schedule) {
            $check_recovery_schedule = PayrollRecoverySchedule::where('payroll_advance_id', $recovery_schedule->payroll_advance_id)
                ->whereYear('repayment_date', Carbon::createFromFormat('F Y', $request->repayment_date)->year)
                ->whereMonth('repayment_date', Carbon::createFromFormat('F Y', $request->repayment_date)->month)
                ->where('id', '!=', $request->schedule_id)
                ->first();

            $month_date = Carbon::createFromFormat('F Y', $request->repayment_date)->startOfMonth()->format('Y-m-d');
            
            if (!$check_recovery_schedule) {
                $recovery_schedule->repayment_date = $month_date;
                $recovery_schedule->amount = $request->amount;
                $recovery_schedule->save();
            }else{

                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'message' => 'A recovery schedule for this month already exists. Please select a different month.',
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'Recovery payment updated successfully.',
        ]);

    }

    public function addNote(Request $request){
        $recovery_schedule = PayrollRecoverySchedule::find($request->schedule_id);
        if ($recovery_schedule) {
            $recovery_schedule->remark = $request->remark;
            $recovery_schedule->save();
        }else{
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Recovery schedule not found.',
            ]);
        }
        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'Remark added successfully.',
        ]);
    }

    public function markAsComplete(Request $request,$id){
        $id = base64_decode($id);
        $payrollAdvance = PayrollAdvance::find($id);
        if ($payrollAdvance) {

            $payrollAdvance->recovery_status = 'Completed';
            $payrollAdvance->action_date = Carbon::now();
            $payrollAdvance->save();

            return redirect()->route('people.advance-salary-repayment-tracker.show', base64_encode($payrollAdvance->id))
                ->with('success', 'Salary advance marked as completed successfully.');
        
            
        }
        return redirect()->back()
            ->with('error', 'Salary advance not found.');
    }

     public function downloadPdf($id)
    {
        $id = base64_decode($id);
        $payrollAdvance = PayrollAdvance::with([
            'employee.resortAdmin',
            'employee.position',
            'employee.department',
            'payrollRecoverySchedule'
        ])->findOrFail($id);


        $pdf = Pdf::loadView('resorts.people.employee.advance-salary-repayment-tracker.pdf-report', [
            'payrollAdvance' => $payrollAdvance,
        ]);

        return $pdf->download('repayment-details-'.$payrollAdvance->id.'.pdf');
    }
}