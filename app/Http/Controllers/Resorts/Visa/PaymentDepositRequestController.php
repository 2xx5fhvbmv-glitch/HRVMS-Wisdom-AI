<?php

namespace App\Http\Controllers\Resorts\Visa;

use DB;
use Str;
use Auth;
use Validator;
use Excel;
use Carbon\Carbon;
use App\Helpers\Common;
use App\Models\Resorts;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VisaRenewal;
use App\Models\Employee;
use App\Models\EmployeeResignation;
use App\Models\VisaWallets;
use App\Models\TotalExpensessSinceJoing;
use App\Models\VisaTransectionHistory;
class PaymentDepositRequestController extends Controller
{
    protected $resort;
    protected $underEmp_id=[];
    public function __construct()
    {
        $this->resort = $resortId = auth()->guard('resort-admin')->user();
        if($this->resort->is_master_admin == 0){
            $reporting_to = $this->globalUser->GetEmployee->id;
            $this->underEmp_id = Common::getSubordinates($reporting_to);
        }
    }

    public function index(Request $request)
    {
     
        $EmployeeResignation = $this->GetIndex();
        $VisaWallets  = VisaWallets::orderBy("id","DESC")->where('resort_id', $this->resort->resort_id)->get();
        $page_title = 'Deposit Refund Request';
        return view('resorts.Visa.deposit.index', compact( 'page_title','EmployeeResignation','VisaWallets'));
    }

    public function DepositeRefundStore(Request $request)
    {
        
        $wallet_option = $request->input('wallet_option', []);
       
        
        $validator = Validator::make($request->all(), [
            'wallet_option' => 'required|array',
            'wallet_option.*' => 'required|array',
            'wallet_option.*.*' => 'required|numeric|min:0',
        ]);

            if ($validator->fails()) 
            {

                return response()->json([
                    'success' => false,
                    'msg' => 'Validation failed',
                    'errors' => $validator->errors()->first()
                ], 422);
            }

            DB::beginTransaction();
            try
            {
           
                $insufficientWallets = [];
                $updates = [];

                foreach ($wallet_option as $walletId => $option) 
                {
                    $from_wallet_Amt = VisaWallets::where('resort_id', $this->resort->resort_id)
                        ->where('id', $walletId)
                        ->lockForUpdate()
                        ->first();

                    if (!$from_wallet_Amt) {
                        $insufficientWallets[] = "Wallet ID {$walletId} not found.";
                        continue;
                    }

                    $EmployeeId = key($option);
                    $walletAmt = $option[$EmployeeId];

                    $TotalExpensessSinceJoing = TotalExpensessSinceJoing::where('resort_id', $this->resort->resort_id)
                        ->where('employees_id', $EmployeeId)
                        ->first();

                    if(!$TotalExpensessSinceJoing) 
                    {
                        $insufficientWallets[] = "No expense record found for employee ID {$EmployeeId}.";
                        continue;
                    }

                    $Employee_Deposite = $TotalExpensessSinceJoing->Deposit_Amt;

                    if ($from_wallet_Amt->Amt < $Employee_Deposite) {

                        $employeeDetails= Employee::with(['resortAdmin'])->where('id', $EmployeeId)->first();
                        $insufficientWallets[] = "Insufficient balance in the " . $from_wallet_Amt->WalletName.'Wallet for employee  ' . $employeeDetails->resortAdmin->first_name .' '. $employeeDetails->resortAdmin->last_name. '. Available: ' . $from_wallet_Amt->Amt . ', Required: ' . $Employee_Deposite - $from_wallet_Amt->Amt;
                    } 
                    else 
                    {
                          
                        $updates[] = [
                            'wallet' => $from_wallet_Amt,
                            'employee_id' => $EmployeeId,
                            'employee_deposit' => $Employee_Deposite
                        ];
                    }
                }

                // If any validation errors, rollback and return
                if (!empty($insufficientWallets)) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'msg' => 'Error processing deposit refund',
                        'errors' => ['from_wallet' => $insufficientWallets]
                    ], 422);
                }

                // Process the valid updates
                foreach ($updates as $update) 
                {
                    $wallet = $update['wallet'];
                    $EmployeeId = $update['employee_id'];
                    $Employee_Deposite = $update['employee_deposit'];

                    $wallet->Amt -= $Employee_Deposite;
                    $wallet->save();

                    VisaTransectionHistory::create([
                        'resort_id' => $this->resort->resort_id,
                        'Amt' => $Employee_Deposite,
                        'from_wallet' => $wallet->id,
                        'to_wallet_realAmt' => 0.00,
                        'from_wallet_realAmt' => $wallet->Amt,
                        'Payment_Date' => Carbon::now(),
                        'comments' => "Deposit Refund",
                        'Employee_id' => $EmployeeId
                    ]);

                    EmployeeResignation::where('employee_id', $EmployeeId)->update([
                        'Deposit_withdraw' => 'Yes',
                        'Deposit_Amt' => $Employee_Deposite
                    ]);
                }


               
                DB::commit();
                $EmployeeResignation = $this->GetIndex();
                $VisaWallets  = VisaWallets::orderBy("id","DESC")->where('resort_id', $this->resort->resort_id)->get();

                $html = view('resorts.renderfiles.deposit_refund_table', compact('EmployeeResignation','VisaWallets'))->render();
                return response()->json(['success' => true,'html'=>$html, 'msg' => 'Deposit refund processed successfully.']);

            } catch (\Exception $e) {
                DB::rollBack();
                \Log::emergency("File: " . $e->getFile());
                \Log::emergency("Line: " . $e->getLine());
                \Log::emergency("Message: " . $e->getMessage());

                return response()->json([
                    'success' => false,
                    'msg' => 'Failed to process deposit refund',
                ], 500);
            }



         
    
        
    }
    public function GetIndex()
    {
        return $EmployeeResignation = EmployeeResignation::with(['employee.department','employee.position','employee.resortAdmin'])->where('hr_status','Approved')->where('resort_id', $this->resort->resort_id)
                            ->get()
                            ->filter(function($resignation) {
                                // Filter out resignations that have already been processed for deposit refund
                                return $resignation->Deposit_withdraw !== 'Yes';
                            })
                            ->map(function($resignation) {
                               
                                return [
                                    'Hod_status'      => $resignation->hod_status,
                                    'hr_status'       => $resignation->hr_status,
                                    'id'               => $resignation->id,
                                    'employee_id'      => $resignation->employee_id,
                                    'Emp_id'           => $resignation->employee->Emp_id,
                                    'employee_name'    => $resignation->employee->resortAdmin->first_name . ' ' . $resignation->employee->resortAdmin->last_name,
                                    'department'       => $resignation->employee->department->name ?? '',
                                    'position'         => $resignation->employee->position->position_title ?? '',
                                    'resignation_date' => $resignation->resignation_date,
                                    'profile_pic'      =>   Common::getResortUserPicture($resignation->employee->resortAdmin->id),

                                ];
                            }); 

    }

    public function DepositRequestSearch(Request $request)
    {
        $search = $request->input('search', '');
        $EmployeeResignation = EmployeeResignation::with(['employee.department','employee.position','employee.resortAdmin'])
            ->where('hr_status', 'Approved')
            ->where('resort_id', $this->resort->resort_id)
            ->where(function($query) use ($search) {
                $query->whereHas('employee.resortAdmin', function($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%");
                });
            })
            ->get()
            ->filter(function($resignation) {
                return $resignation->Deposit_withdraw !== 'Yes';
            })
            ->map(function($resignation) {
                return [
                    'id'               => $resignation->id,
                    'employee_id'      => $resignation->employee_id,
                    'Emp_id'           => $resignation->employee->Emp_id,
                    'employee_name'    => $resignation->employee->resortAdmin->first_name . ' ' . $resignation->employee->resortAdmin->last_name,
                    'department'       => $resignation->employee->department->name ?? '',
                    'position'         => $resignation->employee->position->position_title ?? '',
                    'resignation_date' => $resignation->resignation_date,
                    'profile_pic'      =>   Common::getResortUserPicture($resignation->employee->resortAdmin->id),
                ];
            });

              $VisaWallets  = VisaWallets::orderBy("id","DESC")->where('resort_id', $this->resort->resort_id)->get();
              $html = view('resorts.renderfiles.deposit_refund_table', compact('EmployeeResignation','VisaWallets'))->render(); 
            return response()->json(['html' => $html]);
    }


    public function DashboardDepositRequest(Request $request)
    {
        
  

            if ($request->ajax()) 
            {
                $position = $request->input('position', null);
                $Date = $request->input('date', null);
                $EmployeeResignation = EmployeeResignation::with(['employee.department','employee.position','employee.resortAdmin'])->where('hr_status','Approved')->where('resort_id', $this->resort->resort_id)
                            ->whereHas('employee', function($query) use ($position) 
                            {
                                if ($position) 
                                {
                                    $query->where('position_id', base64_decode($position));
                                }
                            })
                            ->where('Deposit_withdraw', '!=', 'Yes')
                            ->when($Date, function ($query, $Date) 
                            {
                                $query->whereDate('resignation_date', Carbon::parse($Date)->format('Y-m-d'));
                            })
                            ->get()
                           
                            ->map(function($resignation) 
                            {
                               
                                if($resignation->hod_status =="Pending" && $resignation->hr_status == "Pending")
                                {
                                    $resignation->RequestStatus = '<span class="badge badge-themeSkyblue">Not Requested</span>';
                                }
                                else
                                {
                                   $resignation->RequestStatus = '<span class="badge badge-themeBlue">Requested</span> ';
                                }
                              
                                    $DepositeAmount                = TotalExpensessSinceJoing::where('resort_id', $this->resort->resort_id)->where('employees_id', $resignation->employee_id)->first('Deposit_Amt');
                                    $resignation->DepositeAmount   = $DepositeAmount->Deposit_Amt ?? 'Please  Check Employee Nationality';
                                    $resignation->Hod_status       = $resignation->hod_status;
                                    $resignation->hr_status        = $resignation->hr_status;
                                    $resignation->id               = $resignation->id;
                                    $resignation->employee_id      = $resignation->employee_id;
                                    $resignation->Emp_id           = $resignation->employee->Emp_id;
                                    $resignation->employee_name    = $resignation->employee->resortAdmin->first_name . ' ' . $resignation->employee->resortAdmin->last_name;
                                    $resignation->department       = $resignation->employee->department->name ?? '';
                                    $resignation->position         = $resignation->employee->position->position_title ?? '';
                                    $resignation->resignation_date = $resignation->resignation_date;
                                    $resignation->Nationality      = $resignation->employee->nationality ?? '-';
                                    $resignation->profile_pic      =   Common::getResortUserPicture($resignation->employee->resortAdmin->id);

                                return $resignation;
                            }); 
                         
             return datatables()->of($EmployeeResignation)
                ->editColumn('ID', function ($row) 
                    {
                        return $row->Emp_id;
                    })
                    ->editColumn('Name', function ($row) 
                    {
                        return $row->employee_name;
                    })
                    ->editColumn('Nationality', function ($row) 
                    {
                        return $row->Nationality;
                    })      
                    ->editColumn('DepositAmount', function ($row) 
                    {
                        return $row->DepositeAmount;
                    }) 
                    ->editColumn('Status', function ($row) 
                    {
                        return $row->RequestStatus;
                    })      
                    ->rawColumns(['ID','Name','Nationality','DepositAmount','Status'])
                    ->make(true);
        }      
      

    }
}
