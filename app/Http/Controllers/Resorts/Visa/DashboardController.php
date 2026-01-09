<?php

namespace App\Http\Controllers\Resorts\Visa;
use DB;
use URL;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Helpers\Common;
use App\Models\Resorts;
use App\Models\VisaWallets;
use App\Models\PaymentRequest;
use App\Models\ResortPosition;
use App\Models\Employee;
use App\Models\VisaXpactAmounts;
use App\Models\VisaWalletsTransactions;
use  App\Models\VisaNationality;
use App\Models\VisaRenewal;
use App\Models\QuotaSlotRenewal;
use App\Models\EmployeeInsurance;
use App\Models\WorkPermitMedicalRenewal;
use App\Models\VisaRenewalChild;
use  App\Models\ResortBudgetCost;
use App\Models\ResortDepartment;
use App\Models\WorkPermit;
use App\Models\VisaEmployeeExpiryData;

class DashboardController extends Controller
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
   

    public function Admin_Dashobard(Request $request)
    {

    }
    public function HR_Dashobard(Request $request)
    {
       
        $page_title ="Visa";
        $VisaWallets  = VisaWallets::orderBy("id","DESC")->where('resort_id', $this->resort->resort_id)->get();
        $VisaXpactAmounts = VisaXpactAmounts::orderBy("id","DESC")->where('resort_id', $this->resort->resort_id)->get();
        $reconiliation = $this->ReconiliationCheck();
        $DetermineSeverity = $this->DetermineSeverity();
        $Position = ResortPosition::where('resort_id', $this->resort->resort_id)->get();

        return view('resorts.Visa.dashboard.hrdashboard',compact('page_title','Position','VisaWallets','VisaXpactAmounts','reconiliation','DetermineSeverity'));
    }



    public function VisaXpactUpdateAmt(Request $request)
    {

        $id= base64_decode($request->id);
        $WalletAmt=$request->Xpact_WalletAmt;
        $VisaXpactAmounts = VisaXpactAmounts::find($id);
        DB::beginTransaction();
        try {
                if($VisaXpactAmounts->Xpact_Amt > 0)
                {
                    $VisaXpactAmounts->Xpact_Amt = $WalletAmt;
                    $VisaXpactAmounts->save();
                    DB::commit();

                    $VisaXpactAmounts  = VisaXpactAmounts::where('resort_id', $this->resort->resort_id)->get();
                    $html ='';
                    if($VisaXpactAmounts->isNotEmpty())
                    {
                        foreach($VisaXpactAmounts as $VisaWallet)
                        {
                            $html .= '<div class="col-xl-6 col-lg-12 col-6">
                                        <div class="reconciliation-block">
                                            <div>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0)" 
                                                    class="edit-visa-wallet me-2"
                                                    data-amt="' . base64_encode($VisaWallet->Xpact_Amt) . '" 
                                                    data-name="' . base64_encode($VisaWallet->Xpact_WalletName) . '" 
                                                    data-id="' . base64_encode($VisaWallet->id) . '">
                                                        <img src="' . URL::asset('resorts_assets/images/edit.svg') . '" alt="icon">
                                                    </a>
                                                </div>
                                                <h6>' . e($VisaWallet->Xpact_WalletName) . '</h6>
                                                <strong>MVR ' . number_format($VisaWallet->Xpact_Amt, 2) . '</strong>
                                            </div>
                                        </div>
                                    </div>';
                        }
                    }
                    else   
                    {   
                         $html ='<div class="col-12"><p class="text-center">No wallets available.</p> </div>';
                    }

                 
                    return response()->json([
                                    'success' => true,
                                    'msg' => 'Visa Xpact Amount updated successfully.',
                                    'html' => $html], 200);

                } 
                else 
                {

                     return response()->json([
                                                'success' => false,
                                                'msg' => 'Visa Expert amount must be greater than zero',
                                                'html' => $html
                                            ], 200);
                    return redirect()->back()->with('error', 'Visa Expert amount must be greater than zero.');
                }
            }
        catch (\Exception $e) 
        {
            DB::rollback();
            return redirect()->back()->with('error', 'Something went wrong. Please try again later.');
        }

    }
    public function ReconiliationCheck()
    {
       $results = [];

        $VisaWallets  = VisaWallets::orderBy("id","DESC")->where('resort_id', $this->resort->resort_id)->get();
        $VisaXpactAmounts = VisaXpactAmounts::orderBy("id","DESC")->where('resort_id', $this->resort->resort_id)->get();
            foreach ($VisaWallets as $wallet) {
                $matched = false;

                foreach ($VisaXpactAmounts as $xpact) {
                    if ($wallet->WalletName === $xpact->Xpact_WalletName) 
                    {
                        $matched = true;
                        $walletAmt = floatval($wallet->Amt);
                        $xpactAmt = floatval($xpact->Xpact_Amt);

                        if ($walletAmt != $xpactAmt) {
                            $difference = number_format(abs($walletAmt - $xpactAmt), 2);
                            
                            $results[] = 
                            [
                                'wallet_name' => $xpact->Xpact_WalletName,
                                'status' => "Not Reconciled - Difference: MVR ".$difference,
                            ];
                        }
                        break; 
                    }
                }

               
               
            }
        return $results;
    }

    public function DetermineSeverity()
    {
        $PaymentRequest = PaymentRequest::where('resort_id', $this->resort->resort_id)->get();
                       


            return  $finalCounts = [
                    'Pending' =>  $PaymentRequest->where('Status','Pending')->count(),
                    'Complete' =>$PaymentRequest->where('Status','Approved')->count()
                  
                ];

    }

    public function NatioanlityWiseEmployeeDepositAndCount(Request $request)
    {

        if($request->ajax())
        {
       
            $natioanlity = array();
            VisaNationality::where('resort_id', $this->resort->resort_id)
            ->get()
            ->map(function($ak) use (&$natioanlity){
                $natioanlityWiseEmp_count = Employee::where('resort_id', $this->resort->resort_id)
                                                    ->where('status', 'Active')
                                                    ->where('nationality', $ak->nationality)
                                                    ->get()->count();
                $natioanlity[$ak->nationality] = ['id'=>$ak->id,'DepositAmt'=>$ak->amt,'natioanlity'=>$ak->nationality,'Count'=>$natioanlityWiseEmp_count];
            return $ak;
            });


            // Convert array to collection for datatables
            $nationalityData = collect();
            foreach ($natioanlity as $key => $value) {
                $nationalityData->push((object)[
                    'nationality' => $value['natioanlity'],
                    'deposit_amount' => $value['DepositAmt'],
                    'employee_count' => $value['Count'],
                    'id' =>$value['id'] ?? 0
                ]);
            }
            return datatables()->of($nationalityData)
                ->editColumn('Nationality', function ($row) 
                {
                    return $row->nationality;
                })
                ->editColumn('DepositAmount', function ($row)
                {
                    return 'MVR ' . number_format($row->deposit_amount, 2);
                })
                ->editColumn('Employeee', function ($row) {
                    return $row->employee_count;
                })
                ->editColumn('Action', function ($row)
                {
                    $id = base64_encode($row->id);
                    return '<a href="javascript:void(0)" class="a-link OpenNatioanlityWiseEmployee" data-cat-id="' . e($id) . '">View Details</a>';
                })      
                ->rawColumns(['Nationality', 'DepositAmount', 'Employeee', 'Action'])
                ->make(true);
        }
       


    }
    public function NatioanlityWiseEmployeeDepositAndCountDetails(Request $request)
    {
        if ($request->ajax()) {
             $natioanlity = array();
            VisaNationality::where('resort_id', $this->resort->resort_id)
            ->get()
            ->map(function($ak) use (&$natioanlity){
                $natioanlityWiseEmp_count = Employee::where('resort_id', $this->resort->resort_id)
                                                    ->where('status', 'Active')
                                                    ->where('nationality', $ak->nationality)
                                                    ->get()->count();
                $natioanlity[$ak->nationality] = ['id'=>$ak->id,'DepositAmt'=>$ak->amt,'natioanlity'=>$ak->nationality,'Count'=>$natioanlityWiseEmp_count];
            return $ak;
            });

        
           
            $nationalityData = collect();
             foreach ($natioanlity as $key => $value) {
                $nationalityData->push((object)[
                    'nationality' => $value['natioanlity'],
                    'deposit_amount' => $value['DepositAmt'],
                    'employee_count' => $value['Count'],
                    'id' =>$value['id'] ?? 0
                ]);
            }
       
              return datatables()->of($nationalityData)
                ->editColumn('Nationality', function ($row) 
                {
                    return $row->nationality;
                })
                ->editColumn('DepositAmount', function ($row)
                {
                    return 'MVR ' . number_format($row->deposit_amount, 2);
                })
                ->editColumn('Employeee', function ($row) {
                    return $row->employee_count;
                })
                ->editColumn('Action', function ($row)
                {
                    $id = base64_encode($row->id);
                    return '<a href="javascript:void(0)" class="a-link OpenNatioanlityWiseEmployee" data-cat-id="' . e($id) . '">View Details</a>';
                })      
                ->rawColumns(['Nationality', 'DepositAmount', 'Employeee', 'Action'])
                ->make(true);
        }
        $page_title = 'Nationality Wise Employees';

        return view("resorts.Visa.employee.NatioanlityWiseEmployeeDepositAndCountlist",compact('page_title'));
    }

    public function NatioanlityWiseEmployeeList(Request $request)
    {
    
        $id = base64_decode($request->id);
        $VisaNationality = VisaNationality::where('resort_id', $this->resort->resort_id)->where('id', $id)->first();
        $natioanlityWiseEmp_count = Employee::with(['resortAdmin', 'position', 'department',])
                                                    ->where('resort_id', $this->resort->resort_id)
                                                    ->where('status', 'Active')
                                                    ->where('nationality', $VisaNationality->nationality)
                                                    ->get()
                                                    ->map(function($employee) {
                                                        $employee->Emp_name = $employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name;
                                                        $employee->Emp_id = $employee->Emp_id;
                                                        $employee->Department_name = $employee->department->name ?? 'N/A';
                                                        $employee->Position_name = $employee->position->position_title ?? 'N/A';
                                                        $employee->ProfilePic = Common::getResortUserPicture($employee->resortAdmin->id);
                                                        return $employee;
                                                    });
        $html = '';
        if($natioanlityWiseEmp_count->isNotEmpty())
        {
            foreach($natioanlityWiseEmp_count as $employee)
            {
                $html .= '<tr>
                            <td>' . e($employee->Emp_id) . '</td>
                            <td>
                             <div class=" d-flex align-items-center">
                                        <div class="img-circle"><img src="'.e($employee->ProfilePic) .'" alt="user"></div>
                                </div>
                            ' . e($employee->Emp_name) . '</td>
                            <td>'. e($employee->Position_name) .  '</td>
                            <td>' . e($employee->Department_name) .  '</td>
                          </tr>';
            }
        }
        else   
        {   
            $html = '<tr><td colspan="5" class="text-center">No employees found.</td></tr>';
        }
        return response()->json([
            'success' => true,
            'html' => $html
        ]);
                                                  
    }
 

    public function LiabilityBreakDown(Request $request)
    {
        if ($request->ajax()) {
            $resort_id = $this->resort->resort_id;
            $Year = $request->input('NatioanlityWiseBreakDownRang');
            
                $start = Carbon::create($Year, 1, 1)->startOfDay();

                // End = 31st Dec of the year
                $end = Carbon::create($Year, 12, 31)->endOfDay();

                $months = [];
                $period = $start->copy()->startOfMonth();

                while ($period->lte($end)) {
                    $months[$period->format('Y-m')] = $period->format('M Y'); 
                    $period->addMonth();
                }

           
            $chartData = [
                'labels' => array_values($months),
                'workpermit' => [],
                'slot_fee' => [],
                'insurance' => [],
                'medical' => [],
                'photo' => []
            ];

            foreach ($months as $monthKey => $monthLabel) 
            {
                $chartData['workpermit'][] = (float)WorkPermit::where('resort_id', $resort_id)
                    ->where('Status', 'Paid')
                    ->whereRaw("DATE_FORMAT(Due_Date, '%Y-%m') = ?", [$monthKey])
                    ->sum('Amt');

                $chartData['slot_fee'][] = (float)QuotaSlotRenewal::where('resort_id', $resort_id)
                    ->where('Status', 'Paid')
                    ->whereRaw("DATE_FORMAT(Due_Date, '%Y-%m') = ?", [$monthKey])
                    ->sum('Amt');

                $chartData['insurance'][] = (float)EmployeeInsurance::where('resort_id', $resort_id)
                    ->whereRaw("DATE_FORMAT(insurance_start_date, '%Y-%m') = ?", [$monthKey])
                    ->sum('Premium');

                $chartData['medical'][] =(float) WorkPermitMedicalRenewal::where('resort_id', $resort_id)
                    ->whereRaw("DATE_FORMAT(start_date, '%Y-%m') = ?", [$monthKey])
                    ->sum('Amt');

                $chartData['Visa'][] =(float) VisaRenewal::where('resort_id', $resort_id)
                    ->whereRaw("DATE_FORMAT(start_date, '%Y-%m') = ?", [$monthKey])
                    ->sum('Amt');
            }

            return response()->json([
                'success' => true,
                'data' => $chartData
            ]);
        }
    }

    public function NatioanlityWiseEmployeeBreakDownChart(Request $request)
    {
        $resort_id = $this->resort->resort_id;
            $natioanlity = array();
            $totalEmployees = 0;

            $totalActiveEmployees = Employee::where('resort_id', $resort_id)
                ->where('status', 'Active')
                ->where('nationality', '!=', 'maldivian')
                ->count();

             

            // First collect and compute totals
            VisaNationality::where('resort_id', $resort_id)
                ->get()
                ->map(function($ak) use (&$natioanlity, &$totalEmployees, $resort_id,&$totalActiveEmployees) {
                    $empCount = Employee::where('resort_id', $resort_id)
                        ->where('status', 'Active')
                        ->where('nationality', $ak->nationality)
                        ->count();
               
                    $natioanlity[$ak->nationality] = [
                        'id' => $ak->id,
                        'DepositAmt' => $ak->amt,
                        'natioanlity' => $ak->nationality,
                        'deposit_percent'  =>$empCount > 0 ? round( ( $empCount/ $totalActiveEmployees) * 100):0,
                        'Count' => $empCount , 
                    ];

                });

            $chartData = [
                'labels' => [],
                'data' => [],
                'deposit_percent' => [],
            ];
            foreach ($natioanlity as $value) {
                $chartData['labels'][] = $value['natioanlity'];
                $chartData['data'][] = $value['Count'];
                $chartData['deposit_percent'][] = $value['Count'];
              
            }
            return response()->json([
                'success' => true,
                'chartData' => $chartData
        ]);
       


    }

    public function DasbhoardFlagWiseGetData(Request $request)
    {
        $flag            = $request->triggerPoint;
        $checkYearStatus = $request->checkYearStatus;
        $formattedDate   = $request->formattedDate;
        if(isset($formattedDate))
        {
            $newdate = explode("-",$formattedDate);
            
            try 
            {
                $StartDate = Carbon::createFromFormat('d/m/Y', trim($newdate[0]));
                $EndDate = Carbon::createFromFormat('d/m/Y', trim($newdate[1]));
            } catch (\Exception $e) {
                // If specific format fails, try generic parsing with locale setting
                $StartDate = Carbon::parse(trim($newdate[0]))->locale('en_GB');
                $EndDate = Carbon::parse(trim($newdate[1]))->locale('en_GB');
            }
            $newDate  =  $formattedDate;
        }
        else
        {
            if($checkYearStatus == "Weekly")
            {
                $StartDate = Carbon::now()->startOfWeek();
                $EndDate   = Carbon::now()->endOfWeek();
            }
            elseif($checkYearStatus == "Monthly")
            {
                $StartDate = Carbon::now()->startOfMonth();
                $EndDate   = Carbon::now()->endOfMonth();
            }
            elseif($checkYearStatus == "Quarterly")
            {
                $StartDate = Carbon::now()->startOfQuarter();
                $EndDate   = Carbon::now()->endOfQuarter();
            }
            elseif($checkYearStatus == "Semiannual")
            {
                $StartDate = Carbon::now()->startOfYear()->addMonths(6);
                $EndDate   = Carbon::now()->endOfYear()->addMonths(6);
            }
            else
            {
                $StartDate = Carbon::now()->startOfYear();
                $EndDate   = Carbon::now()->endOfYear();
            }
            $newDate  =  $StartDate->format('d/m/Y') . ' - ' . $EndDate->format('d/m/Y');

        }

            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth   = Carbon::now()->endOfMonth();


        $employee=array();
        $resort_id         = $this->resort->resort_id;
        $ThisWeekStartDate = Carbon::now()->startOfWeek();
        $ThisWeekEndDate   = Carbon::now()->endOfWeek();
        $Today             = Carbon::now();
        if($flag == "WorkPermitFee")
        {
            
            $WorkPermit =    WorkPermit::where('resort_id', $resort_id)->whereBetween("Due_Date",[$StartDate,$EndDate])->get();
            $WorkPermit->map(function($w) use(&$employee)
            {

                 $today = Carbon::now();
                        $dueDate = Carbon::parse($w->Due_Date);
                        $overdueDays = $dueDate->diffInDays($today, false);
                        if ($overdueDays > 0) 
                        {
                            $due = " $overdueDays days overdue.";
                        } else {
                            $due = null; 
                        }
            
                        $w->overdue_status = $due;

                    $emp = Employee::with(['resortAdmin', 'position', 'department'])->where('id', $w->employee_id)->first();
                    $employee[]=[      
                                    "Emp_name"=>$emp->resortAdmin->first_name . ' ' . $emp->resortAdmin->last_name,
                                    "Emp_id" => $emp->Emp_id,
                                    "Department_name" => $emp->department->name ?? 'N/A',
                                    "Position_name"=> $emp->position->position_title ?? 'N/A',
                                    "ProfilePic"  => Common::getResortUserPicture($emp->resortAdmin->id),
                                    'overDue_status' =>  $due,
                                ];
                   

                    return $w;
            });
            
            $TotalPaidAmt         =    number_format($WorkPermit->where('Status', 'Paid')->sum('Amt'),2);
            $TotalUnpaidAmt       =    number_format($WorkPermit->where('Status', 'Unpaid')->sum('Amt'),2);
            $Totalemployees       =    $WorkPermit->groupBy('employee_id')->count('employee_id');
            $MonthlyduePayment    =    number_format(WorkPermit::where('resort_id', $resort_id)->whereBetween("Due_Date",[$startOfMonth,$endOfMonth])->where('Status', 'Unpaid')->sum('Amt'),2);
            $WeekduePayment       =    number_format(WorkPermit::where('resort_id', $resort_id)->whereBetween("Due_Date",[$ThisWeekStartDate,$ThisWeekEndDate])->where('Status', 'Unpaid')->sum('Amt'),2);
            $TodayduePayment      =    number_format(WorkPermit::where('resort_id', $resort_id)->whereDate("Due_Date",$Today->toDateString())->where('Status', 'Unpaid')->sum('Amt'),2);


        


        }
        elseif($flag == "QuotaSlot")
        {

            $QuotaSlotRenewal     =   QuotaSlotRenewal::where('resort_id', $resort_id)->whereBetween("Due_Date",[$StartDate,$EndDate])->get();
           
                $QuotaSlotRenewal->map(function($w) use(&$employee)
                {
                     $today = Carbon::now();
                        $dueDate = Carbon::parse($w->Due_Date);
                        $overdueDays = $dueDate->diffInDays($today, false);
                        if ($overdueDays > 0) 
                        {
                            $due = " $overdueDays days overdue.";
                        } else {
                            $due = null; 
                        }
            
                        $w->overdue_status = $due;

                    $emp = Employee::with(['resortAdmin', 'position', 'department'])->where('id', $w->employee_id)->first();
                    $employee[]=[      
                                    "Emp_name"=>$emp->resortAdmin->first_name . ' ' . $emp->resortAdmin->last_name,
                                    "Emp_id" => $emp->Emp_id,
                                    "Department_name" => $emp->department->name ?? 'N/A',
                                    "Position_name"=> $emp->position->position_title ?? 'N/A',
                                    "ProfilePic"  => Common::getResortUserPicture($emp->resortAdmin->id),
                                    'overDue_status' =>  $due,
                                ];
                   

                    return $w;
                });
            $TotalPaidAmt         =    number_format($QuotaSlotRenewal->where('Status', 'Paid')->sum('Amt'),2);

            $TotalUnpaidAmt       =    number_format($QuotaSlotRenewal->where('Status', 'Unpaid')->sum('Amt'),2);
            $Totalemployees       =    $QuotaSlotRenewal->groupBy('employee_id')->count('employee_id');
            $MonthlyduePayment    =    number_format(QuotaSlotRenewal::where('resort_id', $resort_id)->whereBetween("Due_Date",[$startOfMonth,$endOfMonth])->where('Status', 'Unpaid')->sum('Amt'),2);
            $WeekduePayment       =    number_format(QuotaSlotRenewal::where('resort_id', $resort_id)->whereBetween("Due_Date",[$ThisWeekStartDate,$ThisWeekEndDate])->where('Status', 'Unpaid')->sum('Amt'),2);
            $TodayduePayment      =    number_format(QuotaSlotRenewal::where('resort_id', $resort_id)->whereDate("Due_Date",$Today->toDateString())->where('Status', 'Unpaid')->sum('Amt'),2);

        }
        elseif($flag == "Insurance")
        {
            $EmployeeInsurance    =   EmployeeInsurance::where('resort_id', $resort_id)->whereBetween("insurance_end_date",[$StartDate,$EndDate])->get();
            
            $EmployeeInsurance->map(function($w) use(&$employee)
            {
                $today = Carbon::now();
                $dueDate = Carbon::parse($w->Due_Date);
                $overdueDays = $dueDate->diffInDays($today, false);
                if ($overdueDays > 0) 
                {
                    $due = " $overdueDays days overdue.";
                } else {
                    $due = null; 
                }
    
                $w->overdue_status = $due;

                $emp = Employee::with(['resortAdmin', 'position', 'department'])->where('id', $w->employee_id)->first();
                $employee[]=[      
                                "Emp_name"=>$emp->resortAdmin->first_name . ' ' . $emp->resortAdmin->last_name,
                                "Emp_id" => $emp->Emp_id,
                                "Department_name" => $emp->department->name ?? 'N/A',
                                "Position_name"=> $emp->position->position_title ?? 'N/A',
                                "ProfilePic"  => Common::getResortUserPicture($emp->resortAdmin->id),
                                'overDue_status' =>  $due,
                            ];
               
                return $w;
            });
            $TotalPaidAmt         =    number_format(EmployeeInsurance::where('resort_id', $resort_id)->whereBetween("insurance_start_date",[$startOfMonth,$endOfMonth])->sum('Premium'),2);
            $TotalUnpaidAmt       =    number_format($EmployeeInsurance->sum('Premium'),2);
            $Totalemployees       =    $EmployeeInsurance->groupBy('employee_id')->count('employee_id');
            $MonthlyduePayment    =    number_format(EmployeeInsurance::where('resort_id', $resort_id)->whereBetween("insurance_end_date",[$startOfMonth,$endOfMonth])->sum('Premium'),2);
            $WeekduePayment       =    number_format(EmployeeInsurance::where('resort_id', $resort_id)->whereBetween("insurance_end_date",[$ThisWeekStartDate,$ThisWeekEndDate])->sum('Premium'),2);
            $TodayduePayment      =    number_format(EmployeeInsurance::where('resort_id', $resort_id)->whereDate("insurance_end_date",$Today->toDateString())->sum('Premium'),2);
        }
        elseif($flag == "PermitMedicalFee")
        {
            $WorkPermitMedicalRenewal    =   WorkPermitMedicalRenewal::where('resort_id', $resort_id)->whereBetween("end_date",[$StartDate,$EndDate])->get();
            $WorkPermitMedicalRenewal->map(function($w) use(&$employee)
            {

                $today = Carbon::now();
                $dueDate = Carbon::parse($w->Due_Date);
                $overdueDays = $dueDate->diffInDays($today, false);
                if ($overdueDays > 0) 
                {
                    $due = " $overdueDays days overdue.";
                } else {
                    $due = null; 
                }
    
                $w->overdue_status = $due;

                $emp = Employee::with(['resortAdmin', 'position', 'department'])->where('id', $w->employee_id)->first();
                $employee[]=[      
                                "Emp_name"=>$emp->resortAdmin->first_name . ' ' . $emp->resortAdmin->last_name,
                                "Emp_id" => $emp->Emp_id,
                                "Department_name" => $emp->department->name ?? 'N/A',
                                "Position_name"=> $emp->position->position_title ?? 'N/A',
                                "ProfilePic"  => Common::getResortUserPicture($emp->resortAdmin->id),
                                'overDue_status' =>  $due,
                            ];
            });
            $TotalPaidAmt                =    number_format(WorkPermitMedicalRenewal::where('resort_id', $resort_id)->whereBetween("start_date",[$startOfMonth,$endOfMonth])->sum('Amt'),2);
            $TotalUnpaidAmt              =    number_format($WorkPermitMedicalRenewal->sum('Amt'),2);
            $Totalemployees              =    $WorkPermitMedicalRenewal->groupBy('employee_id')->count('employee_id');
            $MonthlyduePayment           =    number_format(WorkPermitMedicalRenewal::where('resort_id', $resort_id)->whereBetween("end_date",[$startOfMonth,$endOfMonth])->sum('Amt'),2);
            $WeekduePayment              =    number_format(WorkPermitMedicalRenewal::where('resort_id', $resort_id)->whereBetween("end_date",[$ThisWeekStartDate,$ThisWeekEndDate])->sum('Amt'),2);
            $TodayduePayment             =    number_format(WorkPermitMedicalRenewal::where('resort_id', $resort_id)->whereDate("end_date",$Today->toDateString())->sum('Amt'),2);
        }
        elseif($flag == "WorkVisa")
        {
            $VisaRenewal                 =   VisaRenewal::where('resort_id', $resort_id)->whereBetween("end_date",[$StartDate,$EndDate])->get();
            $VisaRenewal->map(function($w) use(&$employee)
            {

                $today = Carbon::now();
                $dueDate = Carbon::parse($w->Due_Date);
                $overdueDays = $dueDate->diffInDays($today, false);
                if ($overdueDays > 0) 
                {
                    $due = " $overdueDays days overdue.";
                } else {
                    $due = null; 
                }
    
                $w->overdue_status = $due;

                $emp = Employee::with(['resortAdmin', 'position', 'department'])->where('id', $w->employee_id)->first();
                $employee[]=[      
                                "Emp_name"=>$emp->resortAdmin->first_name . ' ' . $emp->resortAdmin->last_name,
                                "Emp_id" => $emp->Emp_id,
                                "Department_name" => $emp->department->name ?? 'N/A',
                                "Position_name"=> $emp->position->position_title ?? 'N/A',
                                "ProfilePic"  => Common::getResortUserPicture($emp->resortAdmin->id),
                                'overDue_status' =>  $due,
                            ];

                return $w;
            });
            $TotalPaidAmt                =    number_format(VisaRenewal::where('resort_id', $resort_id)->whereBetween("start_date",[$startOfMonth,$endOfMonth])->sum('Amt'),2);
            $TotalUnpaidAmt              =    number_format($VisaRenewal->sum('Amt'),2);
            $Totalemployees              =    $VisaRenewal->groupBy('employee_id')->count('employee_id');
            $MonthlyduePayment           =    number_format(VisaRenewal::where('resort_id', $resort_id)->whereBetween("end_date",[$startOfMonth,$endOfMonth])->sum('Amt'),2);
            $WeekduePayment              =    number_format(VisaRenewal::where('resort_id', $resort_id)->whereBetween("end_date",[$ThisWeekStartDate,$ThisWeekEndDate])->sum('Amt'),2);
            $TodayduePayment             =    number_format(VisaRenewal::where('resort_id', $resort_id)->whereDate("end_date",$Today->toDateString())->sum('Amt'),2);
        }

        $row1='';
        if(!empty($employee))
        {
            foreach($employee as $emp)
            {
                if($emp['overDue_status'] != null)
                {
                    $row1.='<div class="user-block block-danger  mb-1 d-flex align-items-center">
                        <div class="img-circle">
                            <img src='.$emp['ProfilePic'].' alt="image">
                        </div>
                        <div
                            class="w-100 d-xxl-flex d-xl-inline d-sm-flex align-items-center justify-content-between">
                            <div>
                                <h6>'.$emp['Emp_name'].'<span>'.$emp['Emp_id'].'</span></h6>
                                <p>'.$emp['Department_name'].' - '.$emp['Position_name'].'</p>
                            </div>
                            <div class="overdue-text text-end mt-xxl-0 mt-xl-1 mt-sm-0 mt-2">'.$emp['overDue_status'].' </div>
                        </div>
                    </div>';
                }
                else
                {
                     $row1 = '<div class="user-block block-danger  mb-1 d-flex align-items-center">
                    <h6 class="text-center">No Overdue found.</h6>
                    </div>';
                }
                 
            }
        }
        else
        {
            $row1 = '<div class="user-block block-danger  mb-1 d-flex align-items-center">
                    <h6 class="text-center">No Overdue found.</h6>
                    </div>';
        }
       

        


        $route = route('resort.visa.Expiry');
        $row='<div class="tab-pane fade show active" id="'.$flag.'" role="tabpanel" aria-labelledby="'.$flag.'">
                                <div class="row align-items-center mb-3">
                                    <div class="col">
                                               <div class="dateRangeAb"  id="datapicker">
                                                    <div>
                                                        <!-- Hidden input field to attach the calendar to -->
                                                        <input type="text" class="form-control" value="'.$newDate.'" name="hiddenInput" id="hiddenInput">
                                                    </div>
                                                    <p id="startDate" class="d-none">Start Date:</p>
                                                    <p id="endDate" class="d-none">End Date:</p>
                                                </div>
                                    </div>
                                    <div class="col-auto">
                                        <a href="'.$route.'" class="a-link">View All</a>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="total-incidents-box">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label>Total Xpats:</label>
                                                <Span> '.$Totalemployees.'</Span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label>Total Paid:</label>
                                                <Span>MVR '.$TotalPaidAmt.'</Span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label>Today:</label>
                                                <Span>MVR '.$TodayduePayment.'</Span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label>This Week:</label>
                                                <Span>MVR '.$WeekduePayment.'</Span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label>This Month:</label>
                                                <Span>MVR '.$MonthlyduePayment.'</Span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-7">
                                        <div class="bg-themeGrayLight overdue-alerts-box h-100">
                                            <h6 class="mb-3">Overdue Alerts</h6>
                                            '.$row1.'
                                        </div>
                                    </div>
                                </div>
                            </div>';
        return response()->json([
            'success' => true,
                    'StartDate'=>$StartDate,
                    'EndDate'=>$EndDate,
                    'html'=>$row
                ]);
        
       
    }

}
