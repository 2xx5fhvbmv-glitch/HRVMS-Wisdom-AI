<?php

namespace App\Http\Controllers\Resorts\Visa;

use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Helpers\Common;
use App\Models\VisaRenewal;
use App\Models\QuotaSlotRenewal;
use App\Models\EmployeeInsurance;
use App\Models\WorkPermitMedicalRenewal;
use Carbon\Carbon;
use App\Models\VisaRenewalChild;
use  App\Models\ResortBudgetCost;
use App\Models\ResortDepartment;
use App\Models\WorkPermit;
use App\Models\VisaEmployeeExpiryData;
use App\Models\ChildFileManagement;
use Storage;
use App\Models\TotalExpensessSinceJoing;
use App\Models\PaymentRequestChild;

use App\Models\PaymentRequest;
// Jobs 
use App\Jobs\UploadEmployeeFileToAws;

class XpactEmployeeController extends Controller
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

    public function XpactEmpIndex(Request $request)
    {
        $page_title = 'Xpact Employees ';
           $start = Carbon::today();

        if($request->ajax())
        {
            $searchTerm = $request->get('searchTerm');
            $status = $request->get('status');
            $departmentFilter = $request->get('departmentFilter');
            $date = $request->get('date');
           $Employee = Employee::with(['resortAdmin', 'position', 'department'])
                                ->whereNotIn('nationality', ['Maldivian'])
                                ->whereHas('resortAdmin',function ($query) {
                                    $query->where('status','Active');
                                })
                                ->where('resort_id', $this->resort->resort_id)
                                ->where('status','Active')
                                ->when(!empty($status) && $status != "All", function ($query) use ($status)
                                {
                                    $query->where('status', $status);
                                })
                                ->when(!empty($departmentFilter), function ($query) use ($departmentFilter)
                                {
                                    $query->whereHas('department', function ($q) use ($departmentFilter) {
                                        $q->where('id', $departmentFilter);
                                    });
                                })
                                ->when(!empty($searchTerm), function ($query) use ($searchTerm) {
                                    $query->where(function ($q) use ($searchTerm) {
                                        $q->orWhere('id', $searchTerm) // if numeric ID
                                        ->orWhere('Emp_id', 'LIKE', "%$searchTerm%") // fixed Emp_id match
                                        ->orWhereHas('resortAdmin', function ($q1) use ($searchTerm) {
                                            $q1->where('first_name', 'LIKE', "%$searchTerm%")
                                                ->orWhere('last_name', 'LIKE', "%$searchTerm%")
                                                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%$searchTerm%"]);
                                        })
                                        ->orWhereHas('position', function ($q2) use ($searchTerm) {
                                            $q2->where('position_title', 'LIKE', "%$searchTerm%");
                                        })
                                        ->orWhereHas('department', function ($q3) use ($searchTerm) {
                                            $q3->where('name', 'LIKE', "%$searchTerm%");
                                        });
                                    });
                                })
                                ->when(!empty($date), function ($query) use ($date) {
                                 
                                    $query->where('joining_date', Carbon::parse($date)->format('Y-m-d'));
                                })
                                ->get()
                            ->map(function($i) use ($start) 
                            {
                                $i->profile = Common::getResortUserPicture($i->resortAdmin->id);
                                $WorkPermitMedicalRenewal = WorkPermitMedicalRenewal::where('employee_id', $i->id)->where('resort_id', $this->resort->resort_id)->first(['employee_id','Reference_Number','Cost','Currency','Medical_Center_name','start_date','end_date','medical_file']);
                                if($WorkPermitMedicalRenewal) 
                                {
                                    $medicalStatus = $this->getFormattedExpiryStatus($WorkPermitMedicalRenewal->end_date);
                                    $i->WorkPermit = Carbon::parse($WorkPermitMedicalRenewal->end_date)->format('d M Y') . ' ' . $medicalStatus;
                                }
   
                                $QuotaSlotRenewal = QuotaSlotRenewal::where('employee_id', $i->id)
                                        ->where('resort_id', $this->resort->resort_id)
                                        ->orderBy('id', 'DESC')
                                        ->where('Month',12)
                                        ->first(['employee_id', 'Month', 'Amt', 'Payment_Date', 'Due_Date', 'Currency', 'Reciept_file', 'PaymentType']);

                                if ($QuotaSlotRenewal) 
                                {
                                    $i->QuotaSlotRenewalDate =$this->getFormattedExpiryStatus($QuotaSlotRenewal->Due_Date);
                                }
                                $VisaEmployeeExpiryData1 = $this->GetemployeeDocument($i->id);
                                if ($VisaEmployeeExpiryData1) 
                                {
                                    $VisaEmployeeExpiryData = $VisaEmployeeExpiryData1[0];
                                    $statisctic_emp_header = $VisaEmployeeExpiryData1[1] ?? null;

                                    if ($statisctic_emp_header) {
                                        $insuranceExpiryRaw = $statisctic_emp_header['Ai_extracted_data']['extracted_fields']['Insurance Expiry Date'] ?? null;

                                        if ($insuranceExpiryRaw) 
                                        {
                                            
                                            $i->InsuranceRenewalDate = $this->getFormattedExpiryStatus($insuranceExpiryRaw);
                                        
                                        }
                                    } else {
                                        $i->InsuranceRenewalDate = null;
                                    }
                                }
                                else
                                {
                                    $i->InsuranceRenewalDate = null;
                                }
                                $statisctic_emp_headerWorkPermit   =  $VisaEmployeeExpiryData1[2] ?? null;
                                if (!empty($statisctic_emp_headerWorkPermit['Ai_extracted_data']['extracted_fields'])) 
                                {
                                    $dateRaw = $statisctic_emp_headerWorkPermit['Ai_extracted_data']['extracted_fields']['Last Entry Allowed'] ?? null;

                                    if ($dateRaw) 
                                    {
                                       $i->WorkPermitPassRemingDays = $this->getFormattedExpiryStatus($dateRaw);
                                    }
                                }
                                else
                                {
                                    $i->WorkPermitPassRemingDays = null;
                                }
                                return $i;
                            });
                $edit_class = '';
                if(Common::checkRouteWisePermission('resort.visa.xpactEmployee',config('settings.resort_permissions.edit')) == false){
                    $edit_class = 'd-none';
                }
             return datatables()->of($Employee)
                ->addColumn('EmployeeId', function ($row) {
                   return $row->Emp_id;
                })
                ->editColumn('EmployeeName', function ($row) {
                  return $row->resortAdmin->first_name . ' ' . $row->resortAdmin->last_name;
                })
                ->editColumn('position', function ($row) {
                     return $row->position->position_title ;
                })
                ->editColumn('department', function ($row) 
                {
                    return $row->department->name;
                })
                ->editColumn('JoiningDate', function ($row) 
                {
                    return date("d-m-Y",strtotime($row->joining_date))??'N/A';
                })
                
                ->editColumn('status', function ($row) {
                    if ($row->status == 'Active') 
                    {
                        $statusClass = 'badge badge-success';
                        $activeStatus = 'Active';
                    }
                    elseif($row->status == 'InActive')
                    {
                        $activeStatus = 'In Active';
                        $statusClass = 'badge badge-infoBorder';
                    }
                    else
                    {
                        $activeStatus = $row->status;
                        $statusClass = 'badge badge-secondary';
                    }
                    return '<span class="' . $statusClass . '">' . $activeStatus . '</span>';
                })
                ->editColumn('Insurance', function ($row) 
                {
                    return $row->InsuranceRenewalDate ?? 'N/A';
                })
                 ->editColumn('WorkPermitDue', function ($row) 
                {
                    return 'Pending which date we need to show';
                })
                ->editColumn('SlotPaymentDue', function ($row) 
                {
                    return $row->QuotaSlotRenewalDate ?? 'N/A';
                })
                ->editColumn('action', function ($row) use ($edit_class) 
                {
                    return '<a target="_blank" href="' . route('resort.visa.XpactEmpDetails', base64_encode($row->id)) . '" class="btn btn-themeSkyblue btn-sm ' . $edit_class . '">Edit</a>';
                })
                ->rawColumns(['EmployeeId','position','department', 'JoiningDate','Insurance','WorkPermitDue', 'SlotPaymentDue', 'status', 'action'])
                ->make(true);
        }


            
        $departments = ResortDepartment::where('resort_id', $this->resort->resort_id)->get();
              
        return view('resorts.Visa.employee.xpactemp', compact('page_title','departments'));
    }

    public function XpactEmpDetails($id)
    { 
        if(Common::checkRouteWisePermission('resort.visa.xpactEmployee',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized action.');
        }
        $id = base64_decode($id);
        $page_title = "Xpact Employee Details";
        $Employee = Employee::with(['resortAdmin', 'position', 'department'])->where("id",$id)->where("nationality",'!=',"Maldivian")->where('resort_id', $this->resort->resort_id)->first();
        // $Employee->passport_number = $Employee->passport_number; //uncomment if you want to show passport number
        
        $Employee->profilePic = Common::getResortUserPicture($Employee->resortAdmin->id);

        $start = Carbon::now();
        $QuotaSlotRenewal = QuotaSlotRenewal::where('employee_id', $id)->where('resort_id', $this->resort->resort_id)->orderBy('id', 'DESC')->first(['employee_id', 'Month', 'Amt', 'Payment_Date', 'Due_Date', 'Currency', 'Reciept_file','PaymentType']);

        $QuotaSlotDeposit = ResortBudgetCost::whereIn('particulars',['QUOTA SLOT DEPOSIT','Quota Slot Deposit','quota slot deposit'])->where("details","Xpat Only")->where('status','active')->where('resort_id',$this->resort->resort_id)->orderBy('updated_at', 'DESC')->first(['particulars','amount','amount_unit']);
        $WorkPermitFee = ResortBudgetCost::whereIn('particulars',['Work Permit fee','work permit fee','WORK PERMIT FEE','QUOTA SLOT DEPOSIT','Quota Slot Deposit','quota slot deposit'])->where("details","Xpat Only")->where('status','active')->where('resort_id',$this->resort->resort_id)->orderBy('updated_at', 'DESC')->first(['particulars','amount','amount_unit']);
        $DepositPayment = ResortBudgetCost::whereIn('particulars',['Deposit Payment','DEPOSIT PAYMENT','deposit payment'])->where("details","Xpat Only")->where('status','active')->where('resort_id',$this->resort->resort_id)->orderBy('updated_at', 'DESC')->first(['particulars','amount','amount_unit']);
        $TotalInsuranePremium = ResortBudgetCost::whereIn('particulars',['Deposit Payment','DEPOSIT PAYMENT','deposit payment'])->where("details","Xpat Only")->where('status','active')->where('resort_id',$this->resort->resort_id)->orderBy('updated_at', 'DESC')->first(['particulars','amount','amount_unit']);
  
        $TotalInsuranePremium    =  ResortBudgetCost::whereIn('particulars',['Deposit Payment','DEPOSIT PAYMENT','deposit payment'])->where("details","Xpat Only")->where('status','active')->where('resort_id',$this->resort->resort_id)->orderBy('updated_at', 'DESC')->first(['particulars','amount','amount_unit']);
        $VisaEmployeeExpiryData1 =  $this->GetemployeeDocument($id);

        $VisaEmployeeExpiryData  =  $VisaEmployeeExpiryData1[0];
        $statisctic_emp_header   =  $VisaEmployeeExpiryData1[1] ?? null;
        $statisctic_emp_headerWorkPermit   =  $VisaEmployeeExpiryData1[2] ?? null;
        if($statisctic_emp_header)
        {
            $Ai_extracted_data = $statisctic_emp_header->Ai_extracted_data;
            // Visa Expiry Date -> 
            if(isset($Ai_extracted_data) && $Ai_extracted_data['extracted_fields']['Visa Issued Date'])
            {
                $statisctic_emp_header->Name ="Visa Expiry";
                $statisctic_emp_header->VisaExpiryDate = Carbon::parse($Ai_extracted_data['extracted_fields']['Visa Issued Date'])->format('d M Y');
                $statisctic_emp_header->VisaRemingDays = $this->getFormattedExpiryStatus($Ai_extracted_data['extracted_fields']['Visa Expiry Date']);
            }
             // Insurance Expiry Date ->
            if(isset($Ai_extracted_data) && $Ai_extracted_data['extracted_fields']['Insurance Expiry Date'])
            {
                $statisctic_emp_header->Name ="Insurance Expiry";
                $statisctic_emp_header->InsuranceExpiryDate = Carbon::parse($Ai_extracted_data['extracted_fields']['Insurance Expiry Date'])->format('d M Y');
                $statisctic_emp_header->InsuranceRemingDays = $this->getFormattedExpiryStatus($Ai_extracted_data['extracted_fields']['Insurance Expiry Date']);
                $statisctic_emp_header->QuotaSlotNumber = $Ai_extracted_data['extracted_fields']['Quota Slot Number'];
            }
            // Work permit Expiry 
            if(isset($Ai_extracted_data) && $Ai_extracted_data['extracted_fields']['Work Permit Expiry Date (Expiry On)'])
            {
                $statisctic_emp_header->Name ="Work Permit Expiry";
                $statisctic_emp_header->WorkPermitExpiryDate = Carbon::parse($Ai_extracted_data['extracted_fields']['Work Permit Expiry Date (Expiry On)'])->format('d M Y');
                $statisctic_emp_header->WorkPermitRemingDays = $this->getFormattedExpiryStatus($Ai_extracted_data['extracted_fields']['Work Permit Expiry Date (Expiry On)']);
            }
        }
        // Work Permit Card Expiry
        if(isset($Ai_extracted_data) && isset($statisctic_emp_headerWorkPermit) && isset($statisctic_emp_headerWorkPermit['Ai_extracted_data']) && isset($statisctic_emp_headerWorkPermit['Ai_extracted_data']['extracted_fields']))
        {
            $date = $statisctic_emp_headerWorkPermit['Ai_extracted_data']['extracted_fields']['Last Entry Allowed'];
            $statisctic_emp_header->Name = "Work Permit Entry Pass";
            $statisctic_emp_header->LastEntryDate = Carbon::parse($date)->format('d M Y');
            $statisctic_emp_header->WorkPermitPassRemingDays = $this->getFormattedExpiryStatus($date);
        }
        if($QuotaSlotRenewal)
        {
            $QuotaSlotRenewal->QuotaslotExpiryDate = Carbon::parse($QuotaSlotRenewal->Due_Date)->format('d M Y');
            $QuotaSlotRenewal->QuotaslotRemingDays = $this->getFormattedExpiryStatus($QuotaSlotRenewal->Due_Date);
        }

        $TotalExpensessSinceJoing = $this->TotalExpensessSinceJoing($id);
        
        return view('resorts.Visa.employee.XpatEmployeeDetails', compact('page_title','Employee','statisctic_emp_header','QuotaSlotRenewal','VisaEmployeeExpiryData','TotalExpensessSinceJoing'));
    }

    public function XpactEmpBudgetCost(Request $request)
    {

        $ResortBudgetCost = ResortBudgetCost::whereIn('particulars',['QUOTA SLOT DEPOSIT','Quota Slot Deposit','quota slot deposit'])->where("details","Xpat Only")->where('status','active')->where('resort_id',$this->resort->resort_id)->orderBy('updated_at', 'DESC')->first(['particulars','amount','amount_unit']);          
        $PayableAmt =  Common::RateConversion("DollerToMVR",$ResortBudgetCost->amount,$this->resort->resort_id);


        if($request->ajax())
        {
             $employee_id = base64_decode($request->employee_id);
          

            if($request->flag =="Quota_Slot_Fee")
            {
                $QuotaSlotRenewalPaid =  QuotaSlotRenewal::where('employee_id', $employee_id)->where('resort_id', $this->resort->resort_id)->orderBy('Month', 'ASC')
                                                        ->get();
                $PaidAmt = $QuotaSlotRenewalPaid->where("Status","Paid")->sum('Amt');
                $UnPaidAmt = $QuotaSlotRenewalPaid->where("Status","Unpaid")->sum('Amt');

                $CommonVariable = QuotaSlotRenewal::where('employee_id', $employee_id)->where("Status","Unpaid")->where('resort_id', $this->resort->resort_id)->orderBy('Month', 'ASC')
                                ->get()
                                ->map(function($i)
                                {
                                    $i->type="QuotaSlot";
                                    return $i;
                                });
                $PayableAmt = $PaidAmt + $UnPaidAmt;
                

            }
            else
            {
                $WorkPermit =  WorkPermit::where('employee_id', $employee_id)->where('resort_id', $this->resort->resort_id)->orderBy('Month', 'ASC')
                    ->get();

                    $PaidAmt = $WorkPermit->where("Status","Paid")->sum('Amt');
                    $UnPaidAmt = $WorkPermit->where("Status","Unpaid")->sum('Amt');

                    $CommonVariable = WorkPermit::where('employee_id', $employee_id)->where("Status","Unpaid")->where('resort_id', $this->resort->resort_id)->orderBy('Month', 'ASC')
                                    ->get()
                                     ->map(function($i)
                                    {
                                        $i->type="WorkPermit";
                                        return $i;
                                    });;
                    $PayableAmt = $PaidAmt + $UnPaidAmt;

            }
          
            return datatables()->of($CommonVariable) 
                ->addColumn('Month', function ($row) {
                   return Carbon::parse($row->Due_Date)->format('F Y');
                })
                ->editColumn('Amount', function ($row) {
                  return $row->Amt. ' ' . $row->Currency;
                })
                ->editColumn('DueDate', function ($row) {
                     return Carbon::parse($row->Due_Date)->format('d-M-Y');
                })
                ->editColumn('Status', function ($row) 
                {
                    if($row->Status =="Paid")
                    {
                        return '<span class="badge badge-themeSuccess">Paid</span>';
                    }
                    else
                    {
                        return '<span class="badge badge-themeDanger">Pending</span>';
                    }
                  
                })
                ->editColumn('PaymentDate', function ($row) 
                {
                    if(isset($row->PaymentDate))
                    {
                         return Carbon::parse($row->PaymentDate)->format('d M Y');
                    }
                    else
                    {
                        return '-';
                    }
                })
                ->editColumn('Action', function ($row) 
                {
                        if($row->Status !="Paid")
                        {
                            return '<a target="_blank" data-id="'.base64_encode($row->id).'" data-type="'.$row->type.'"  class="a-link markasPaid">Mark as Paid</a>';
                        }
                })
                 ->editColumn('created_at', function ($row) 
                {
                        
                            return $row->created_at;
                        
                })
                 ->with([
                    'footerData' => 
                    [
                        'PayableAmount' => 'MVR ' . number_format($PayableAmt, 2),
                        'AmountPaid' => 'MVR ' . number_format($PaidAmt, 2),
                        'BalanceAmount' => 'MVR ' . number_format($UnPaidAmt, 2),
                    ]
                ])
                ->rawColumns(['Month','Amount','DueDate', 'Status','PaymentDate', 'Action'])
                ->make(true);


        }
    }
    public function QuotaSlotMakrasPaid(Request $request)
    {
        $child_id = base64_decode($request->child_id);
        if($request->TypeofModel =="WorkPermit")
        {
            $WorkPermit = WorkPermit::find(base64_decode($request->Mark_id));

            $TotalExpensessSinceJoing =  TotalExpensessSinceJoing::where('resort_id', $this->resort->resort_id)->where('employees_id', $WorkPermit->employee_id)->first();
            if($WorkPermit)
            {
                if($TotalExpensessSinceJoing)
                {
                    $TotalExpensessSinceJoing->Total_work_permit = $TotalExpensessSinceJoing->Total_work_permit + $WorkPermit->Amt;
                    $TotalExpensessSinceJoing->save();
                }
                $PaymentRequestChild = PaymentRequestChild::where('employee_id', $WorkPermit->employee_id)->where('id', $child_id)->first();
                $WorkPermit->Status = "Paid";
                $WorkPermit->ReceiptNumber = $request->Receipt_number;
                $WorkPermit->Payment_Date = Carbon::now()->format('Y-m-d');

                $WorkPermit->save();
                if( $child_id)
                {
                    $PaymentRequestChild = PaymentRequestChild::where('employee_id', $WorkPermit->employee_id)->where('id', $child_id)->first();
                    $PaymentRequestChild->OngoingSteps = $PaymentRequestChild->OngoingSteps + 1;
                    
                    if($PaymentRequestChild->OverallSteps == $PaymentRequestChild->OngoingSteps )
                    {
                        $PaymentRequestChild->ChildStatus = 'Complete';
                        PaymentRequest::where('id', $PaymentRequestChild->Requested_Id)->update(['Status' => 'Approved']);
                    }
                     $PaymentRequestChild->WorkPermitShow = 'No';
                    $PaymentRequestChild->WorkPermitStep = 'Yes';
                    $PaymentRequestChild->save();
    

                }
                return response()->json(['status' =>true, 'message' => 'Marked as Paid successfully']);
            }
            else
            {
                return response()->json(['status' => false, 'message' => 'Work Permit Slot Renewal not found']);
            }
        }
        else
        {
            $QuotaSlotRenewal = QuotaSlotRenewal::find(base64_decode($request->Mark_id));
            $TotalExpensessSinceJoing =  TotalExpensessSinceJoing::where('resort_id', $this->resort->resort_id)
                                                ->where('employees_id', $QuotaSlotRenewal->employee_id)
                                                ->first();

            if($TotalExpensessSinceJoing)
            {
                $TotalExpensessSinceJoing->Total_slot_Payment = $TotalExpensessSinceJoing->Total_slot_Payment + $QuotaSlotRenewal->Amt;
                $TotalExpensessSinceJoing->save();
            }
           
            if($QuotaSlotRenewal)
            {
                $QuotaSlotRenewal->Status = "Paid";
                $QuotaSlotRenewal->ReceiptNumber = $request->Receipt_number;
                $QuotaSlotRenewal->Payment_Date = Carbon::now()->format('Y-m-d');
                $QuotaSlotRenewal->save();
                if($child_id)
                {
                    $PaymentRequestChild = PaymentRequestChild::where('employee_id', $QuotaSlotRenewal->employee_id)->where('id', $child_id)->first();
                    $PaymentRequestChild->OngoingSteps = $PaymentRequestChild->OngoingSteps + 1;
                    if($PaymentRequestChild->OverallSteps == $PaymentRequestChild->OngoingSteps )
                    {
                        $PaymentRequestChild->ChildStatus = 'Complete';
                        PaymentRequest::where('id', $PaymentRequestChild->Requested_Id)->update(['Status' => 'Approved']);
                    }
                     $PaymentRequestChild->QuotaslotShow = 'No';
                    $PaymentRequestChild->QuotaslotStep = 'Yes';
                    $PaymentRequestChild->save();
    
                }
                return response()->json(['status' =>true, 'message' => 'Marked as Paid successfully']);
            }
            else
            {
                return response()->json(['status' => false, 'message' => 'Quota Slot Renewal not found']);
            }
        }
        
    }
    public function PastTransectionHistory(Request $request)
    {
        if($request->ajax())
        {
            $employee_id = base64_decode($request->employee_id);      
            $SelectYear = $request->SelectYear;
            $quotaSlotData = QuotaSlotRenewal::where('employee_id', $employee_id)
                                ->where('resort_id', $this->resort->resort_id);
                            
            if ($SelectYear != "ALL" && isset($SelectYear) && !empty($SelectYear)) {
                $quotaSlotData->whereYear('Due_Date', $SelectYear);
            }
            $quotaSlotData = $quotaSlotData->where('Status', 'Paid')
                            ->orderBy('id', 'DESC')
                            ->get([
                                'id', 'employee_id', 'Month', 'Amt', 'Payment_Date', 
                                'Due_Date', 'Currency', 'Reciept_file', 'PaymentType',
                                'created_at'
                            ])
                            ->map(function($item) {
                                $item->transaction_type = 'Quota Slot';
                                return $item;
                            });
            // Get WorkPermit data
            $workPermitData = WorkPermit::where('employee_id', $employee_id)->where('resort_id', $this->resort->resort_id);

            if ($SelectYear != "ALL" && isset($SelectYear) && !empty($SelectYear)) 
            {
                $workPermitData->whereYear('Due_Date', $SelectYear);
            }
            
            $workPermitData = $workPermitData->where('Status', 'Paid')
                            ->orderBy('id', 'DESC')
                            ->get([
                                'id', 'employee_id', 'Month', 'Amt', 'Payment_Date', 
                                'Due_Date', 'Currency', 'Reciept_file', 'PaymentType','created_at'
                            ])
                            ->map(function($item) {
                                $item->transaction_type = 'Work Permit';
                                return $item;
                            });
                            
            // Combine both collections and sort by Due_Date
            $MixDatas = $quotaSlotData->concat($workPermitData)
                                        ->sortByDesc('Due_Date')
                                        ->values();
            return datatables()->of($MixDatas)
                ->editColumn('Year', function ($row) 
                {
                  return  Carbon::parse($row->Due_Date)->format('Y');
                })
                ->editColumn('TransactionType', function ($row) {
                  return '<b>'.$row->transaction_type.'</b> ('.$row->PaymentType.')';
                })
                ->editColumn('Amount', function ($row) {
                  return $row->Amt. ' ' . $row->Currency;
                })
                  ->addColumn('Date', function ($row) {
                   return Carbon::parse($row->Due_Date)->format('F Y');
                })
                ->editColumn('ReceiptNo', function ($row) {
                     return "ReceiptNo";
                })
                ->editColumn('Status', function ($row) 
                {
                    return '<span class="badge badge-themeSuccess">Paid</span>';
                })
              
               
                ->rawColumns(['Year','TransactionType','Amount', 'Date','ReceiptNo', 'Status'])
                ->make(true);
        }
    }
    public function EmployeeWiseVisaDocumentUpload(Request $request)
    {
        $DocumentType = $request->DocumentType;
        $employee_id  = base64_decode($request->emp_id);
        $flag ='';
        if($DocumentType == "Insurance")
        {
            $flag="insurance";
        }
        elseif($DocumentType == "Work_Permit_Entry_Pass")
        {
            $flag="work_permit_entry_pass";
        }
        elseif($DocumentType == "Work_Permit_Card")
        {
            $flag="work_permit_card";
        }
        elseif($DocumentType == "Visa")
        {
            $flag="visa";
        }
        elseif($DocumentType == "Medical_Report")
        {
            $flag="medical_report";
        }
        elseif($DocumentType == "Passport_Copy") 
        {
            $flag="passport";
        }

        $main_folder = $this->resort->resort->resort_id;
        $file         = $request->file('DocumentFile');

        $employee    = Employee::where('resort_id',$this->resort->resort_id)->where("id",$employee_id)->first();
       
        $aws =  Common::AWSEmployeeFileUpload($this->resort->resort_id,$file,$employee->Emp_id); 
        
        if($aws['status'] == true)
        {
            $file_child_id  = $aws['Chil_file_id'];
            $url = env('AI_extract_work_details_URL').$flag; 
                $curl = curl_init();
                $postFields = [
                    'file' => new \CURLFile($file->getRealPath(), $file->getMimeType(), $file->getClientOriginalName()),
                    'doc_type' => $flag,
                ];
                curl_setopt_array($curl, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $postFields,
                    CURLOPT_HTTPHEADER => [
                        'Accept: application/json',
                    ],
                ]);
                $response = curl_exec($curl);
                $err = curl_error($curl);
                curl_close($curl);
                if($err) 
                {
                    return response()->json(['status' => false, 'message' =>  $err]);
                } 
                $AI_Data = json_decode($response, true); 
            
                DB::beginTransaction();
                try
                {
                    if($DocumentType == "Visa")
                    {
                        $VisaRenewal =  VisaRenewal::where('resort_id', $this->resort->resort_id)
                                    ->where('employee_id', $employee->id)
                                    ->update([  
                                                'visa_file'=>$file_child_id,
                                                'resort_id' => $this->resort->resort_id,
                                                'employee_id' => $employee->id,
                                                'Visa_Number'=> $AI_Data['extracted_fields']['Visa No.'] ?? null,
                                                'WP_No'=> $AI_Data['extracted_fields']['Work Permit Number (Starts with WP)'] ?? null,
                                                'start_date' =>  Carbon::createFromFormat('d/m/Y', $AI_Data['extracted_fields']['Visa Issued Date']),
                                                'end_date' =>Carbon::createFromFormat('d/m/Y', $AI_Data['extracted_fields']['Visa Expiry Date']),
                                            ]);
                    }
                    elseif($DocumentType == "Work_Permit_Card")
                    {

                            WorkPermit::where('resort_id', $this->resort->resort_id)
                            ->where('employee_id', $employee->id)
                            ->update([
                                'Reciept_file'=> $file_child_id,
                                'Work_Permit_Number' => $AI_Data['extracted_fields']['Work Permit Number'] ?? null,
                            ]);
                    }
                        VisaEmployeeExpiryData::where('resort_id', $this->resort->resort_id)
                        ->where('employee_id', $employee->id)
                        ->where('DocumentName', $DocumentType)
                        ->delete();
                        VisaEmployeeExpiryData::create(['resort_id' => $this->resort->resort_id,
                            'employee_id' => $employee->id,
                            'File_child_id' => $file_child_id ?? null,
                            'Ai_extracted_data' => $response ?? null,
                            'DocumentName' => $DocumentType ?? null
                        ]);
                    DB::commit();
                    $GetemployeeDocument = $this->GetemployeeDocument($employee->id);
                    $GetemployeeDocument = $GetemployeeDocument[0];
                    return response()->json(['status' => true, 'data'=>$GetemployeeDocument,'message' => 'File uploaded successfully.']);
                }
                catch(\Exception $e)
                {
                    DB::rollBack();
                    return response()->json(['status' => false, 'message' => 'Error occurred while processing the file.']);
                }
      
        }
        else
        {
            if($aws['msg'])
            {

                return response()->json(['status' => false, 'message' => $aws['msg']]);
            }
            else
            {
                
                return response()->json(['status' => false, 'message' => 'File upload failed. Please try again.']);
            }
         
        }


    }

    private function GetemployeeDocument($id)
    {

        $VisaEmployeeExpiryData = VisaEmployeeExpiryData::where('employee_id', $id)
                                                        ->where('resort_id', $this->resort->resort_id)
                                                        ->get(['created_at','DocumentName', 'Ai_extracted_data', 'File_child_id'])
                                                        ->map(function ($data)
                                                        {
                                                            $data->DocName = str_replace('_', ' ', $data->DocumentName);
                                                            $data->lastUploadedFile= $data->created_at->format('d M Y');
                                                            $data->child_id = base64_encode($data->File_child_id); 
                                                            $data->Ai_extracted_data = json_decode($data->Ai_extracted_data, true);
                                                            
                                                            return $data;
                                                        });
                                                        
        return [
                $VisaEmployeeExpiryData,
                $VisaEmployeeExpiryData->where('DocumentName','=','Other')->first(),
                $VisaEmployeeExpiryData->where('DocumentName','=','Work_Permit_Entry_Pass')->first()
            ];
    }
    public function XpactEmpFileDownload($id)
    {
     
        $ChildFiles = ChildFileManagement::where("id",base64_decode($id))->where("resort_id"   ,$this->resort->resort_id)->first();
        if (isset($ChildFiles) && Storage::disk('s3')->exists($ChildFiles->File_Path)) 
        {   

            $key = hash('sha256', env('ENCRYPTION_KEY'), true);
            $encryptedData = Storage::disk('s3')->get($ChildFiles->File_Path);
            if (empty($encryptedData) || strlen($encryptedData) < 16) 
            {
                throw new \Exception('Invalid or corrupted encrypted data');
            }
                $iv = substr($encryptedData, 0, 16);
                $cipherText = substr($encryptedData, 16);
                $decryptedData = openssl_decrypt(
                    $cipherText,
                    'aes-256-cbc',
                    $key,
                    OPENSSL_RAW_DATA,  // Critical for handling binary data properly
                    $iv
                );
                
                if ($decryptedData === false) {
                    $error = openssl_error_string();
                    throw new \Exception("Decryption failed: {$error}");
                }
                
                $decryptedFileName = str_replace('.enc', '', basename($ChildFiles->File_Path));
                $tempFilePath = "temp/decrypted_" . time() . "_{$decryptedFileName}";
                $extension = strtolower(pathinfo($decryptedFileName, PATHINFO_EXTENSION));
                $mimeTypes = [
                    'pdf' => 'application/pdf',
                    'doc' => 'application/msword',
                    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'xls' => 'application/vnd.ms-excel',
                    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'ppt' => 'application/vnd.ms-powerpoint',
                    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                    'txt' => 'text/plain',
                    'csv' => 'text/csv',
                    
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'bmp' => 'image/bmp',
                    'svg' => 'image/svg+xml',
                    'webp' => 'image/webp',
                    
                    'mp3' => 'audio/mpeg',
                    'wav' => 'audio/wav',
                    'ogg' => 'audio/ogg',
                    'flac' => 'audio/flac',
                    'aac' => 'audio/aac',
                    
                    'mp4' => 'video/mp4',
                    'mov' => 'video/quicktime',
                    'avi' => 'video/x-msvideo',
                    'mkv' => 'video/x-matroska',
                    'webm' => 'video/webm',
                    'wmv' => 'video/x-ms-wmv',
                    'flv' => 'video/x-flv',
                    
                    'zip' => 'application/zip',
                    'rar' => 'application/x-rar-compressed',
                    'tar' => 'application/x-tar',
                    'gz' => 'application/gzip',
                    '7z' => 'application/x-7z-compressed',
                    'html' => 'text/html',
                    'css' => 'text/css',
                    'js' => 'application/javascript',
                    'json' => 'application/json',
                    'xml' => 'application/xml'
                ];
                
                // Set MIME type based on extension or detect if not in our map
                if (isset($mimeTypes[$extension])) {
                    $mimeType = $mimeTypes[$extension];
        
                } else {
                    // Fallback to file detection - may not be accurate for all file types
                    // but better than nothing for unknown extensions
                    if (function_exists('mime_content_type')) {
                        // Create a temporary file to use mime_content_type
                        $tempFile = tempnam(sys_get_temp_dir(), 'file');
                        file_put_contents($tempFile, $decryptedData);
                        $mimeType = mime_content_type($tempFile);
                        unlink($tempFile); // Clean up
                    } else if (class_exists('finfo')) {
                        $finfo = new \finfo(FILEINFO_MIME_TYPE);
                        $mimeType = $finfo->buffer($decryptedData);
                    } else {
                        // If all detection methods fail, use binary as default
                        $mimeType = 'application/octet-stream';
                    }
                }
                
                // Store the decrypted file with proper content type
                Storage::disk('s3')->put($tempFilePath, $decryptedData, [
                    'ContentType' => $mimeType
                ]);
                
                // Generate a temporary URL with sufficient time window
                $fileExtension = pathinfo($ChildFiles->File_Path, PATHINFO_EXTENSION);
                // Get MIME type dynamically

                $mimeType = match (strtolower($extension)) {
                    'mp4'  => 'video/mp4',
                    'mov'  => 'video/quicktime',
                    'avi'  => 'video/x-msvideo',
                    'pdf'  => 'application/pdf',
                    'txt'  => 'text/plain',
                    'jpg'  => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png'  => 'image/png',
                    'gif'  => 'image/gif',
                    'doc', 'docx' => 'application/msword',
                    'xls', 'xlsx' => 'application/vnd.ms-excel',
                    'zip'  => 'application/zip',
                    default => 'application/octet-stream' // Fallback for unknown types
                };
                $newUrl = Storage::disk('s3')->temporaryUrl($tempFilePath, now()->addMinutes(30));
            } else {
                $mimeType='';
            $newUrl = "No";
        }
      
        return ['success' => true,  'NewURLshow' => $newUrl,'mimeType' => $mimeType];     

        if ($AwsResponse['status'] == true) 
        {
            return response()->json(['status' => true, 'AwsResponse' => $AwsResponse, 'message' => 'File found successfully'], 200);
        } 
        else
        {
            return response()->json(['status' => false, 'AwsResponse' => '', 'message' => 'File not found or cannot be accessed'], 200);
        }
    }
    
    function getFormattedExpiryStatus($endDate)
    {
        $start = Carbon::today();
        $end = Carbon::parse($endDate);
        $daysDiff = $start->diffInDays($end, false);
        if ($daysDiff < 0) 
        {
            return $end->format('d M Y')."  (Expired " . abs($daysDiff) . " days ago)";
        }
        else
        {
            return $end->format('d M Y')."  (Expires in " . ($daysDiff + 1) . " days)";
        }
    }

   private function  TotalExpensessSinceJoing($id)
   {
        $data = array();
        $TotalExpensessSinceJoing = TotalExpensessSinceJoing::where('resort_id',$this->resort->resort_id)
                                    ->where('employees_id',$id)
                                    ->get();
                                
        
        $data['totalDepositAmount']               =  $TotalExpensessSinceJoing->sum('Deposit_Amt') ?? 0.00;
        $data['totalWorkPermitAmount']            =  $TotalExpensessSinceJoing->sum('Total_work_permit')?? 0.00;
        $data['totalQuotaSlotPayment']            =  $TotalExpensessSinceJoing->sum('Total_slot_Payment')?? 0.00;
        $data['totalInsurancePayment']            =  $TotalExpensessSinceJoing->sum('Total_insurance_Payment')?? 0.00;
        $data['totalWorkPermitMedicalFeePayment'] =  $TotalExpensessSinceJoing->sum('Total_Work_Permit_Medical_Payment')?? 0.00;
        $data['totalVisa']                        =  $TotalExpensessSinceJoing->sum('Total_Visa_Payment')?? 0.00;

        return $data;
   }
}
 