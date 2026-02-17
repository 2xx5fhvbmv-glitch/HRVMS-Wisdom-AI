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
use App\Models\EmployeeVisaExpiryDetails;
use App\Models\EmployeeQuotaSlotRenewal;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestChild; 
use App\Exports\PaymentRequestExport;
use App\Models\WorkPermitMedicalRenewal;
use App\Models\EmployeeInsurance;
use App\Models\QuotaSlotRenewal;
use App\Models\WorkPermit;

class PaymentRequestController extends Controller
{
  
    protected $resort;
    protected $underEmp_id=[];

    public function __construct()
    {
        $this->resort = $resortId = auth()->guard('resort-admin')->user();
        if(!$this->resort) return;
        if($this->resort->is_master_admin == 0){
            $reporting_to = $this->globalUser->GetEmployee->id;
            $this->underEmp_id = Common::getSubordinates($reporting_to);
        }
    }

    public function Create(Request $request)
    {

        // if ($request->ajax()) 
        // {
        //     $flags  = (array) $request->flag; // Support multiple flags
        //     $search = $request->search;
        //     $date   = $request->date;

        //     // Parse date range
        //     $filterStart = Carbon::now()->startOfMonth();
        //     $filterEnd   = Carbon::now()->endOfMonth();

        //     if ($date && strpos($date, '-') !== false) {
        //         try {
        //             $parts = explode(' - ', $date);
        //             $filterStart = Carbon::createFromFormat('d-m-Y', trim($parts[0]))->startOfDay();
        //             $filterEnd   = Carbon::createFromFormat('d-m-Y', trim($parts[1]))->endOfDay();
        //         } catch (\Exception $e) {
        //             // fallback remains current month
        //         }
        //     }

        //     $employees = Employee::with([
        //         'resortAdmin', 'position', 'department',
        //         'VisaRenewal', 'WorkPermitMedicalRenewal', 
        //         'WorkPermit', 'EmployeeInsurance', 'QuotaSlotRenewal'
        //     ])
        //     ->when($search, function ($query) use ($search) {
        //         $query->orWhereHas('resortAdmin', function ($q) use ($search) {
        //             $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
        //                 ->orWhere('first_name', 'LIKE', "%{$search}%")
        //                 ->orWhere('last_name', 'LIKE', "%{$search}%");
        //         });
        //     })
        //     ->where("nationality", '!=', "Maldivian")
        //     ->where('resort_id', $this->resort->resort_id)
        //     ->get()
        //     ->map(function ($employee) use ($flags, $filterStart, $filterEnd) {

        //         $employee->Emp_name = $employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name;
        //         $employee->Emp_id = $employee->Emp_id;
        //         $employee->Department_name = $employee->department->name ?? 'N/A';
        //         $employee->Position_name = $employee->position->position_title ?? 'N/A';
        //         $employee->ProfilePic = Common::getResortUserPicture($employee->resortAdmin->id);

        //         // Default values
        //         $employee->VisaExpiryExpiryDate = 'N/A';
        //         $employee->InsuranceExpiryDate = 'N/A';
        //         $employee->WorkPermitExpiryDate = 'N/A';
        //         $employee->WorkPermitMedicalPermitExpiryDate = 'N/A';
        //         $employee->QuotaSlotAmtForThisMonth = 'N/A';

        //         $hasAnyFlagData = false;

        //         // Work Permit
        //         if (in_array('work_permit', $flags)) {
        //             $wp = $employee->WorkPermit->filter(fn($item) =>
        //                 Carbon::parse($item->Due_Date)->between($filterStart, $filterEnd)
        //             )->sortByDesc('id')->first();

        //             if ($wp) {
        //                 $employee->WorkPermitExpiryDate = '<b>MVR ' . number_format($wp->Amt, 2) . '</b> ' . $this->getFormattedExpiryStatus($wp->Due_Date);
        //                 $hasAnyFlagData = true;
        //             }
        //         }

        //         // Visa
        //         if (in_array('visa', $flags)) {
        //             $visa = $employee->VisaRenewal;
        //             if ($visa && Carbon::parse($visa->end_date)->between($filterStart, $filterEnd)) {
        //                 $employee->VisaExpiryExpiryDate = '<b>MVR ' . $visa->Amt . '</b>' . $this->getFormattedExpiryStatus($visa->end_date);
        //                 $hasAnyFlagData = true;
        //             }
        //         }

        //         // Insurance
        //         if (in_array('insurance', $flags)) {
        //             $insurance = $employee->EmployeeInsurance()
        //                             ->where('employee_id', $employee->id)
        //                             ->where('resort_id', $this->resort->resort_id)
        //                             ->orderBy('id', 'desc')->first();
        //             if ($insurance && Carbon::parse($insurance->insurance_end_date)->between($filterStart, $filterEnd)) {
        //                 $employee->InsuranceExpiryDate = '<b>MVR ' . $insurance->Premium . '</b>' . $this->getFormattedExpiryStatus($insurance->insurance_end_date);
        //                 $hasAnyFlagData = true;
        //             }
        //         }

        //         // Medical
        //         if (in_array('MedicalReport', $flags)) {
        //             $med = $employee->WorkPermitMedicalRenewal;
        //             if ($med && Carbon::parse($med->end_date)->between($filterStart, $filterEnd)) {
        //                 $employee->WorkPermitMedicalPermitExpiryDate = '<b>MVR ' . $med->Amt . '</b>' . $this->getFormattedExpiryStatus($med->end_date);
        //                 $hasAnyFlagData = true;
        //             }
        //         }

        //         // Quota Slot
        //         if (in_array('slot_payment', $flags)) {
        //             $quota = $employee->QuotaSlotRenewal
        //                         ->filter(fn($item) => Carbon::parse($item->Expiry_Date)->between($filterStart, $filterEnd))
        //                         ->sortByDesc('id')->first();
        //             if ($quota) {
        //                 $employee->QuotaSlotAmtForThisMonth = '<b>MVR ' . number_format($quota->Amount, 2) . '</b>' . $this->getFormattedExpiryStatus($quota->Expiry_Date);
        //                 $hasAnyFlagData = true;
        //             }
        //         }

        //         return $hasAnyFlagData ? $employee : null;

        //     })->filter();


        //     return datatables()->of($employees)
        //         ->addIndexColumn()
        //         ->editColumn('EmployeeID', fn($row) => $row->Emp_id)
        //         ->addColumn('EmployeeName', fn($row) => $row->Emp_name)
        //         ->addColumn('Position', fn($row) => $row->Position_name)
        //         ->addColumn('Department', fn($row) => $row->Department_name)
        //         ->addColumn('VisaExpiry', fn($row) => $row->VisaExpiryExpiryDate ?? 'N/A')
        //         ->addColumn('WorkPermit', fn($row) => $row->WorkPermitExpiryDate ?? 'N/A')
        //         ->addColumn('Insurance', fn($row) => $row->InsuranceExpiryDate ?? 'N/A')
        //         ->addColumn('Medical', fn($row) => $row->WorkPermitMedicalPermitExpiryDate ?? 'N/A')
        //         ->addColumn('SlotFees', fn($row) => $row->QuotaSlotAmtForThisMonth ?? 'N/A')
        //         ->editColumn('CheckBox', fn($row) => '<input type="checkbox" class="form-check-input" name="employee_ids[]" value="' . $row->Emp_id . '">')
        //         ->rawColumns(['EmployeeID','CheckBox','SlotFees','VisaExpiry','Medical','Insurance','WorkPermit','Department','Position','EmployeeName'])
        //         ->make(true);
        // }

                
        if ($request->ajax()) 
        {
            $isChecked = $request->isChecked;
            $flags = (array) $request->flag;
            $search = $request->search;
            $date = $request->date;

            if (in_array('all', $flags)) {
                $flags = ['visa', 'insurance', 'work_permit', 'MedicalReport', 'slot_payment'];
            }

            $filterStart = Carbon::now()->startOfMonth();
            $filterEnd = Carbon::now()->endOfMonth();

            if ($date && strpos($date, '-') !== false) {
                try 
                {
                    $parts = explode(' - ', $date);
                    $filterStart = Carbon::createFromFormat('d-m-Y', trim($parts[0]))->startOfDay();
                    $filterEnd = Carbon::createFromFormat('d-m-Y', trim($parts[1]))->endOfDay();
                } catch (\Exception $e) 
                {
                    // fallback
                }
            }

            $totalVisa = $totalInsurance = $totalPermit = $totalMedical = $totalQuota = $totalChecked = 0;

            $employees = Employee::with(['resortAdmin', 'position', 'department', 'VisaRenewal.VisaChild', 'WorkPermitMedicalRenewal.WorkPermitMedicalRenewalChild', 'WorkPermit', 'EmployeeInsurance.InsuranceChild', 'QuotaSlotRenewal'])
                ->when($search, function ($query) use ($search) {
                    $query->orWhereHas('resortAdmin', function ($q) use ($search) {
                        $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                        ->orWhere('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%");
                    });
                })
                ->where('status','Active')
                ->where("nationality", '!=', "Maldivian")
                ->where('resort_id', $this->resort->resort_id)
                ->get()
                ->map(function ($employee) use (&$totalChecked, $isChecked, $flags, $filterStart, $filterEnd, &$totalVisa, &$totalInsurance, &$totalPermit, &$totalMedical, &$totalQuota) {
                    $employee->isChecked = $isChecked;
                    $employee->Emp_name = $employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name;
                    $employee->Emp_id = $employee->Emp_id;
                    $employee->Department_name = $employee->department->name ?? 'N/A';
                    $employee->Position_name = $employee->position->position_title ?? 'N/A';
                    $employee->ProfilePic = Common::getResortUserPicture($employee->resortAdmin->id);
                    $employee->VisaExpiryExpiryDate = $employee->InsuranceExpiryDate = $employee->WorkPermitExpiryDate = $employee->WorkPermitMedicalPermitExpiryDate = $employee->QuotaSlotAmtForThisMonth = 'N/A';

                    $employeeData = [];
                    $hasAnyFlagData = false;

                    if (in_array('visa', $flags)) {
                        $visa = $employee->VisaRenewal;

               
                        if ($visa && Carbon::parse($visa->end_date)->between($filterStart, $filterEnd)) {
                            $employee->VisaExpiryExpiryDate = '<b>MVR ' . number_format($visa->Amt, 2) . '</b>' . $this->getFormattedExpiryStatus($visa->end_date);
                            $totalVisa += $visa->Amt;
                            $hasAnyFlagData = true;
                            $employeeData[base64_encode($employee->id)]['VisaAmt'] = $visa->Amt;
                            $employeeData[base64_encode($employee->id)]['VisaExpiry'] = $visa->end_date;

                            $lastvisaExpiry  = $employee->VisaRenewal->VisaChild()->orderBy("id","desc")->first();
                            
                            $lastvisaExpiry = $lastvisaExpiry->end_date ?? 'N/A';
                            $employeeData[base64_encode($employee->id)]['LastVisaExpiry'] = $lastvisaExpiry;
                           
                        }
                    }

                    if (in_array('insurance', $flags)) {
                        $insurance = $employee->EmployeeInsurance()->where('employee_id', $employee->id)->where('resort_id', $this->resort->resort_id)->orderBy('id', 'desc')->first();
                        if ($insurance && Carbon::parse($insurance->insurance_end_date)->between($filterStart, $filterEnd)) {
                            $employee->InsuranceExpiryDate = '<b>MVR ' . number_format($insurance->Premium, 2) . '</b>' . $this->getFormattedExpiryStatus($insurance->insurance_end_date);
                            $totalInsurance += $insurance->Premium;
                            $hasAnyFlagData = true;
                            $employeeData[base64_encode($employee->id)]['InsuranceAmt'] = $insurance->Premium;
                            $employeeData[base64_encode($employee->id)]['InsuranceExpiry'] = $insurance->insurance_end_date;

                            $lastInsuranceExpiry  = $employee->EmployeeInsurance->InsuranceChild()->orderBy("id","desc")->first();
                            $lastInsuranceExpiry = $lastInsuranceExpiry->insurance_end_date ?? 'N/A';
                            $employeeData[base64_encode($employee->id)]['lastInsuranceExpiry'] = $lastInsuranceExpiry;
                        }
                    }

                    if (in_array('work_permit', $flags)) 
                    {
                        $wpEntries = $employee->WorkPermit->sortByDesc('id'); // all records sorted by id DESC
                        $currentWP = $wpEntries->filter(fn($item) => Carbon::parse($item->Due_Date)->between($filterStart, $filterEnd))->first();
                        $encodedId = base64_encode($employee->id);
                        if ($currentWP)
                        {
                            $employee->WorkPermitExpiryDate = '<b>MVR ' . number_format($currentWP->Amt, 2) . '</b>' . $this->getFormattedExpiryStatus($currentWP->Due_Date);
                            $totalPermit += $currentWP->Amt;
                            $hasAnyFlagData = true;
                            $employeeData[$encodedId]['WorkPermitAmt'] = $currentWP->Amt;
                            $employeeData[$encodedId]['WorkPermitExpiry'] = $currentWP->Due_Date;

                            $previousWP = $wpEntries->first(function ($item) use ($currentWP) {
                                return Carbon::parse($item->Due_Date)->lt(Carbon::parse($currentWP->Due_Date));
                            });

                            if ($previousWP) 
                            {
                                $employeeData[$encodedId]['LastWorkPermitExpiry'] = $previousWP->Due_Date;
                            } 
                            else
                            {
                                $employeeData[$encodedId]['LastWorkPermitExpiry'] = 'N/A';
                            }
                        }
                    }

                    if (in_array('MedicalReport', $flags)) 
                    {
                        $med = $employee->WorkPermitMedicalRenewal;
                        if ($med && Carbon::parse($med->end_date)->between($filterStart, $filterEnd)) 
                        {
                            $employee->WorkPermitMedicalPermitExpiryDate = '<b>MVR ' . number_format($med->Amt, 2) . '</b>' . $this->getFormattedExpiryStatus($med->end_date);
                            $totalMedical += $med->Amt;
                            $hasAnyFlagData = true;
                            $employeeData[base64_encode($employee->id)]['MedicalAmt'] = $med->Amt;
                            $employeeData[base64_encode($employee->id)]['MedicalExpiry'] = $med->end_date;

                   
                            $LastWorkPermitMedicalExpiry  = $employee->WorkPermitMedicalRenewal->WorkPermitMedicalRenewalChild()->orderBy("id","desc")->first();
                            $LastWorkPermitMedicalExpiry = $LastWorkPermitMedicalExpiry->end_date ?? 'N/A';
                            $employeeData[base64_encode($employee->id)]['LastWorkPermitMedicalExpiry'] = $LastWorkPermitMedicalExpiry;
                        }
                    }

                    if (in_array('slot_payment', $flags)) {
                        // $quota = $employee->QuotaSlotRenewal->filter(fn($item) => Carbon::parse($item->Expiry_Date)->between($filterStart, $filterEnd))->sortByDesc('id')->first();
                        // if ($quota) {
                        //     $employee->QuotaSlotAmtForThisMonth = '<b> MVR ' . number_format($quota->Amt, 2) . '</b>' . $this->getFormattedExpiryStatus($quota->Due_Date);
                        //     $totalQuota += $quota->Amt;
                        //     $hasAnyFlagData = true;
                        //     $employeeData[base64_encode($employee->id)]['QuotaAmt'] = $quota->Amt;
                        //     $employeeData[base64_encode($employee->id)]['QuotaExpiry'] = $quota->Due_Date;
                        // }

                        $quotaEntries = $employee->QuotaSlotRenewal->sortByDesc('id'); // All entries sorted descending by ID
                        $currentQuota = $quotaEntries
                            ->filter(fn($item) => Carbon::parse($item->Expiry_Date)->between($filterStart, $filterEnd))
                            ->first();
                        $encodedId = base64_encode($employee->id);
                        if ($currentQuota) 
                        {
                            $employee->QuotaSlotAmtForThisMonth = '<b> MVR ' . number_format($currentQuota->Amt, 2) . '</b>' . $this->getFormattedExpiryStatus($currentQuota->Due_Date);
                            $totalQuota += $currentQuota->Amt;
                            $hasAnyFlagData = true;

                            $employeeData[$encodedId]['QuotaAmt'] = $currentQuota->Amt;
                            $employeeData[$encodedId]['QuotaExpiry'] = $currentQuota->Due_Date;

                            // Get previous quota entry (before current one)
                            $previousQuota = $quotaEntries->first(function ($item) use ($currentQuota) {
                                return Carbon::parse($item->Due_Date)->lt(Carbon::parse($currentQuota->Due_Date));
                            });

                            if ($previousQuota) 
                            {
                                $employeeData[$encodedId]['LastQuotaExpiry'] = $previousQuota->Due_Date;
                            } 
                            else 
                            {
                                $employeeData[$encodedId]['LastQuotaExpiry'] = 'N/A';
                            }
                        }
                    }

                    $employee->extra= json_encode($employeeData);
                    if ($hasAnyFlagData && $employee->isChecked === "true") {
                        $totalChecked++;
                    }

                    return $hasAnyFlagData ? $employee : null;
                })->filter();

            $overallTotal = $totalVisa + $totalInsurance + $totalPermit + $totalMedical + $totalQuota;

            return datatables()->of($employees)
                ->addIndexColumn()
                ->editColumn('EmployeeID', fn($row) => $row->Emp_id)
                ->addColumn('EmployeeName', function($row) {
                
                    return '<div class="tableUser-block">
                                <div class="img-circle"><img src="'.$row->ProfilePic.'" alt="user"></div>
                                <span class="userApplicants-btn">'.$row->Emp_name.'</span>
                            </div>';
                })
                ->addColumn('Position', fn($row) => $row->Position_name)
                ->addColumn('Department', fn($row) => $row->Department_name)
                ->addColumn('VisaExpiry', fn($row) => $row->VisaExpiryExpiryDate ?? 'N/A')
                ->addColumn('WorkPermit', fn($row) => $row->WorkPermitExpiryDate ?? 'N/A')
                ->addColumn('Insurance', fn($row) => $row->InsuranceExpiryDate ?? 'N/A')
                ->addColumn('Medical', fn($row) => $row->WorkPermitMedicalPermitExpiryDate ?? 'N/A')
                ->addColumn('SlotFees', fn($row) => $row->QuotaSlotAmtForThisMonth ?? 'N/A')
                ->addColumn('CheckBox', function ($row) {
                    $isChecked = ($row->isChecked == "true") ? "checked" : "";
                    $id = base64_encode($row->id);
                    $fields = '';
                  
                    return "<div class=\"form-check no-label\">" .
                     
                        "<input class=\"form-check-input ChildCheck\" name=\"employee_ids[".$id."]\" type=\"checkbox\"  value=".$row->extra." ".$isChecked.">" .
                        "</div>";
                })
                ->rawColumns(['EmployeeID','CheckBox','SlotFees','VisaExpiry','Medical','Insurance','WorkPermit','Department','Position','EmployeeName'])
                ->with([
                    'totals' => [
                        'visa' => number_format($totalVisa, 2),
                        'insurance' => number_format($totalInsurance, 2),
                        'work_permit' => number_format($totalPermit, 2),
                        'medical' => number_format($totalMedical, 2),
                        'slot_payment' => number_format($totalQuota, 2),
                        'overall' => number_format($overallTotal, 2),
                        'totalChecked' => $totalChecked,
                    ]
                ])
                ->make(true);
        }


        $page_title = 'Create Payment Request';
        return view('resorts.Visa.PaymentRequest.create',compact('page_title'));
       
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
            return $end->format('d M Y')."  (Expires in " . ($daysDiff ) . " days)";
        }


    }


    function PaymentRequestSubmit(Request $request)
    {
        $employee_ids = $request->employee_ids;

          $validator = Validator::make($request->all(), [
            'employee_ids' => 'required',
        ],
            [
            'employee_ids.required' => 'Please Select at least one employee.',
        ]);

        if ($validator->fails()) 
        {
            return response()->json($validator->errors(), 400);
        }

        if (empty($employee_ids)) 
        {
            return response()->json(['error' => 'No employees selected'], 400);
        }
        else
        {


                $parts = explode(' ', $this->resort->resort->resort_name);
                $initials = '';
            foreach ($parts as $part) 
            {
                $initials .= Str::substr($part, 0, 1);
            }
            $initials = 'PR-'. $initials.'-'.Common::PaymentRequest($this->resort->resort_id);

            DB::beginTransaction();
            try
            {
                $PaymentRequest = PaymentRequest::create(['resort_id' =>$this->resort->resort_id,'Requestd_id' => $initials,'Request_date' => Carbon::now(),'Status' => 'Pending']);
                foreach($employee_ids as $id => $data)
                {
                    $data = json_decode($data, true);
                
                    foreach($data as $key => $value) 
                    {
                        $visaAmt                       = isset($value['VisaAmt']) ? $value['VisaAmt'] : 0;
                        $VisaExpiry                    = isset($value['VisaExpiry']) ? $value['VisaExpiry'] : null;
                       

                        $LastVisaExpiry                = isset($value['LastVisaExpiry']) ? $value['LastVisaExpiry'] : null;
                        $LastInsuranceExpiry           = isset($value['lastInsuranceExpiry']) ? $value['lastInsuranceExpiry'] : null;
                        $LastWorkPermitExpiry          = isset($value['LastWorkPermitExpiry']) ? $value['LastWorkPermitExpiry'] : null;
                        $LastWorkPermitMedicalExpiry   = isset($value['LastWorkPermitMedicalExpiry']) ? $value['LastWorkPermitMedicalExpiry'] : null;
                        $LastQuotaExpiry               = isset($value['LastQuotaExpiry']) ? $value['LastQuotaExpiry'] : null;

                        $insuranceAmt                  = isset($value['InsuranceAmt']) ? $value['InsuranceAmt'] : 0;
                        $insuranceExpiry               = isset($value['InsuranceExpiry']) ? $value['InsuranceExpiry']:0 ;
                        $WorkpermitAmt                 = isset($value['WorkPermitAmt']) ? $value['WorkPermitAmt'] : 0;
                        $WorkpermitExpiry              = isset($value['WorkPermitExpiry']) ? $value['WorkPermitExpiry'] : null;
                        $medicalAmt                    = isset($value['MedicalAmt']) ? $value['MedicalAmt'] : 0;
                        $medicalExpiry                 = isset($value['MedicalExpiry']) ? $value['MedicalExpiry'] : null;
                        $quotaAmt                      = isset($value['QuotaAmt']) ? $value['QuotaAmt'] : 0;
                        $quotaExpiry                   = isset($value['QuotaExpiry']) ? $value['QuotaExpiry'] : null;
                        $employee_id                   = base64_decode($key);

                     
                        $countrequest = [ $WorkpermitAmt > 0 ? 'yes' : 'no',
                                          $quotaExpiry > 0 ? 'yes' : 'no',
                                          $insuranceExpiry > 0 ? 'yes' : 'no',
                                          $medicalAmt > 0 ? 'yes' : 'no',
                                          $visaAmt > 0 ? 'yes' : 'no'
                                        ];
                                        
                        // Count how many 'yes' values are in the array
                        $countrequest = count(array_filter($countrequest, function($value) {
                            return $value === 'yes';
                        }));
                        PaymentRequestChild::create([
                                                    'Requested_Id' => $PaymentRequest->id,
                                                    'Employee_id' => $employee_id,
                                                    'WorkPermitDate'=> $WorkpermitExpiry,
                                                    'WorkPermitAmt' => $WorkpermitAmt,
                                                    'QuotaslotDate' => $quotaExpiry,
                                                    'QuotaslotAmt' => $quotaAmt,
                                                    'InsuranceDate' => $insuranceExpiry,
                                                    'InsurancePrimume' => $insuranceAmt,
                                                    'MedicalReportDate' => $medicalExpiry, 
                                                    'MedicalReportFees' => $medicalAmt,
                                                    'VisaDate' => $VisaExpiry,
                                                    'VisaAmt' => $visaAmt, 
                                                    'LastVisaDate'=>  $LastVisaExpiry,
                                                    'LastMedicalReportDate'=> $LastWorkPermitMedicalExpiry,
                                                    'LastInsuranceDate'=> $LastInsuranceExpiry,
                                                    'LastQuotaslotDate'=> $LastQuotaExpiry,
                                                    'LastWorkPermitDate'=>  $LastWorkPermitExpiry,
                                                    'WorkPermitShow'=> $WorkpermitAmt > 0 ? 'yes' : 'no',
                                                    'QuotaslotShow'=> $quotaExpiry > 0 ? 'yes' : 'no',
                                                    'InsuranceShow'=> $insuranceExpiry > 0 ? 'yes' : 'no',
                                                    'MedicalReportShow'=> $medicalAmt > 0 ? 'yes' : 'no',
                                                    'VisaShow'=> $visaAmt > 0 ? 'yes' : 'no',
                                                    'OverallSteps'=> $countrequest,
                                                    'ChildStatus' => 'Pending',
                                                    'OngoingSteps'=> 0,
                                                ]);
                    
                    }

                }
  
                DB::commit();
                $route = route('resort.visa.PaymentRequestIndex');
                    return response()->json([
                                        'success' => true,
                                        'msg' => 'Payment request created successfully',
                                        'redirect' => $route,
                                    ], 200);
            }
            catch (\Exception $e) 
            {
                DB::rollBack();
                return response()->json([ 'success' => false,'error' => 'Failed to create payment request: ' . $e->getMessage()], 500);
            }
        }

    }

    public function index(Request $request)
    {

        if($request->ajax())
        {
                $date = $request->date;
                $PaymentRequest = PaymentRequest::where('resort_id', $this->resort->resort_id)
                    ->where('Status', 'Pending')
                    ->when($date, function ($query) use ($date) 
                    {
                        $query->whereDate('Request_date', carbon::parse($date)->format('Y-m-d'));
                    })
                    ->orderBy('id', 'desc')
                    ->get(['id', 'Requestd_id', 'Request_date', 'Status']);

                    $edit_class = '';
                    if(Common::checkRouteWisePermission('resort.visa.PaymentRequestIndex',config('settings.resort_permissions.edit')) == false){
                        $edit_class = 'd-none';
                    }
                    return datatables()
                                ->of($PaymentRequest)
                                ->addIndexColumn()
                                ->editColumn('PaymentRequestID', function ($row) 
                                {
                                    return $row->Requestd_id ?? 'N/A';
                                })
                                ->editColumn('PaymentRequestedDate', function ($row) 
                                {
                                    return $row->Request_date ? Carbon::parse($row->Request_date)->format('d M Y') : 'N/A';
                                })
                                ->editColumn('Status', function ($row) 
                                {
                                    $badgeClass = 'badge-warning'; 
                                    return '<span class="badge ' . $badgeClass . '">' . htmlspecialchars($row->Status) . '</span>';
                                })
                                ->addColumn('Action', function ($row) use ($edit_class) 
                                {
                                    // <a target="_blank" href="' . $viewUrl1 . '" class="btn-tableIcon btnIcon-blue   btn-sm"><i class="fa-solid fa-check"></i></a>
                                    $encodedId = base64_encode($row->id);
                                    $viewUrl2 = route('resort.visa.PaymentRequestDetails', ['id' => $encodedId]);
                                                return '<a target="_blank" href="' . $viewUrl2 . '" class="btn-tableIcon btnIcon-blue   btn-sm"><i class="fa-regular fa-eye"></i></a>
                                                        <a  href="javascript:void(0)" class="btn-tableIcon btnIcon-danger PaymentRequestRejected   btn-sm '.$edit_class.'" data-id="'.$encodedId.'"><i class="fa-solid fa-xmark"></i></a>';
                                })
                                ->rawColumns(['Status', 'Action']) 
                                ->make(true);
        }
        $page_title = 'Payment Request List';
        return view('resorts.Visa.PaymentRequest.index', compact('page_title'));
    }

    public function PaymentRequestRejected(Request $request)
    {
       $reason = $request->reason;
       $id = base64_decode($request->Payment_id);


         $PaymentRequest = PaymentRequest::find($id);
        if (!$PaymentRequest) 
        {
            return response()->json(['success' => false,'msg' => 'Payment Request not found'], 404); 
        }   
        else{
            $PaymentRequest->Status = 'Reject';
            $PaymentRequest->Reason = $reason;
            $PaymentRequest->save();

            return response()->json(['success' => true, 'msg' => 'Payment Request Rejected Successfully'], 200);
        }
    }

    public function PaymentRequestDetails($id)
    {
      
        if(Common::checkRouteWisePermission('resort.visa.PaymentRequestIndex',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized action.');
        }
        
        $PaymentRequest = PaymentRequest::find(base64_decode($id));
        if (!$PaymentRequest) 
        {
            return redirect()->route('resort.Visa.PaymentRequestIndex')->with('error', 'Payment Request not found');
        }
        else
        {
        $GrandTotal = 0;
        $PaymentRequestChildren = PaymentRequestChild::where('Requested_Id', $PaymentRequest->id)
                            ->with(['RequestedEmployees.resortAdmin', 'RequestedEmployees.position', 'RequestedEmployees.department'])
                            ->get()->map(function ($employee) use(&$GrandTotal) 
                                {
                                    $sum= $employee->WorkPermitAmt + $employee->QuotaslotAmt + $employee->InsurancePrimume + $employee->MedicalReportFees + $employee->VisaAmt;
                                    $employee->TotalAmount = number_format($sum, 2);
                                    $employee->Emp_name = $employee->RequestedEmployees->resortAdmin->first_name .'  '. $employee->RequestedEmployees->resortAdmin->last_name;
                                    $employee->Emp_id = $employee->RequestedEmployees->Emp_id;
                                    $employee->Department_name = $employee->RequestedEmployees->department->name ?? 'N/A';
                                    $employee->Position_name = $employee->RequestedEmployees->position->position_title ?? 'N/A';
                                    $employee->ProfilePic = Common::getResortUserPicture( $employee->RequestedEmployees->resortAdmin->id);
                                    $GrandTotal += $sum;
                                    
                                return $employee;
                            });
                         
            if ($PaymentRequestChildren->isEmpty()) 
            {
                return redirect()->route('resort.Visa.PaymentRequestIndex')->with('error', 'No payment request details found');
            }
            
            $PaymentRequest->children = $PaymentRequestChildren;
        }

        $page_title = 'Payment Request Details';
        return view('resorts.Visa.PaymentRequest.details', compact('page_title','id','GrandTotal','PaymentRequestChildren'));
    }

    public function DownloadPymentRequest($id)
    {

        $PaymentRequest = PaymentRequest::find(base64_decode($id));
        if (!$PaymentRequest) 
        {
            return redirect()->back()->with('error', 'Payment Request not found');
        }  
        else
        {
            $PaymentRequestChildren = PaymentRequestChild::where('Requested_Id', $PaymentRequest->id)
                ->with(['RequestedEmployees.resortAdmin', 'RequestedEmployees.position', 'RequestedEmployees.department'])
                ->get()->map(function ($employee) 
                {
                    $employee->Emp_name = $employee->RequestedEmployees->resortAdmin->first_name .'  '. $employee->RequestedEmployees->resortAdmin->last_name;
                    $employee->Emp_id = $employee->RequestedEmployees->Emp_id;
                    $employee->Department_name = $employee->RequestedEmployees->department->name ?? 'N/A';
                    $employee->Position_name = $employee->RequestedEmployees->position->position_title ?? 'N/A';
                    $employee->ProfilePic = Common::getResortUserPicture( $employee->RequestedEmployees->resortAdmin->id);
                    return $employee;
                });
           
            $PaymentRequest->children = $PaymentRequestChildren;
            return Excel::download(new PaymentRequestExport($PaymentRequest, $PaymentRequestChildren), $PaymentRequest->Requestd_id.'.xlsx');

        }
    }
    public function PaymentRequestThrowRenewal($id, $childId)
    {
        $page_title = 'Renewal Payment Request';
        
        $ResortBudgetCost = Common::VisaRenewalCost($this->resort->resort_id);
        $PaymentRequest = PaymentRequest::find(base64_decode($id));
        $child = PaymentRequestChild::where("id", base64_decode($childId))->first();
        $start_date= carbon::now()->format('Y-m-d');
        $start = Carbon::parse($start_date);
        if($child)
        {
            $Employees = Employee::with(['resortAdmin', 'position', 'department'])
                                    ->where('id', $child->Employee_id)
                                    ->where('resort_id', $this->resort->resort_id)
                                    ->first();
            
            if($Employees) 
            {
                $Employees->Emp_name = $Employees->resortAdmin->first_name . ' ' . $Employees->resortAdmin->last_name;
                $Employees->Emp_id = $Employees->Emp_id;
                $Employees->Department_name = $Employees->department->name ?? 'N/A';
                $Employees->Position_name = $Employees->position->position_title ?? 'N/A';
                $Employees->ProfilePic = Common::getResortUserPicture($Employees->resortAdmin->id);
            }
            $PaymentRequest_id = $PaymentRequest ? $PaymentRequest->Requestd_id : '';



            $WorkPermitMedicalRenewal = WorkPermitMedicalRenewal::where('employee_id',$child->Employee_id)->where('resort_id',$this->resort->resort_id)->first(['employee_id','Reference_Number','Amt','Currency','Medical_Center_name','start_date','end_date','medical_file']);
            
            if($WorkPermitMedicalRenewal) 
            {
                $work_permit_amt =  $ResortBudgetCost['WORK VISA MEDICAL TEST FEE'];

                $medical_end_date = Carbon::parse($WorkPermitMedicalRenewal->end_date);
                $medical_months_diff = $start->diffInMonths($medical_end_date);
                if ($medical_months_diff < 1) 
                {
                    $days_diff = $start->diffInDays($medical_end_date);
                    $WorkPermitMedicalRenewal->MedicalRenewalTime = "Expires in $days_diff days";
                } 
                else
                {
                    $WorkPermitMedicalRenewal->MedicalRenewalTime = "$medical_months_diff month(s) remaining";
                } 
                $WorkPermitMedicalRenewal->workpermitcost =  $WorkPermitMedicalRenewal->Amt.' MVR' ?? $work_permit_amt['amount'].' '.$work_permit_amt['unit'];
                $WorkPermitMedicalRenewal->employee_id =base64_encode($WorkPermitMedicalRenewal->employee_id);
                $WorkPermitMedicalRenewal->medical_end_date = Carbon::parse($WorkPermitMedicalRenewal->medical_end_date)->format('d M Y');
            }
              $EmployeeInsurance = EmployeeInsurance::where('employee_id', $child->Employee_id)->where('resort_id', $this->resort->resort_id)
                                ->first([
                                        'insurance_company',
                                        'insurance_policy_number',
                                        'insurance_coverage',
                                        'insurance_start_date',
                                        'insurance_end_date',
                                        'insurance_file',
                                        'Currency',
                                        'Premium',
                                        'employee_id',
                                    ]);
                $start = Carbon::now();
                if ($EmployeeInsurance) 
                {
                
                    $insurance_end_date = Carbon::parse($EmployeeInsurance->insurance_end_date);
                    $insurance_months_diff = $start->diffInMonths($insurance_end_date);

                    if ($insurance_months_diff < 1) 
                    {
                        $days_diff = $start->diffInDays($insurance_end_date);
                        $EmployeeInsurance->InsuranceRenewalTime = "Expires in $days_diff days";
                    } 
                    else 
                    {
                        $EmployeeInsurance->InsuranceRenewalTime = "$insurance_months_diff month(s) remaining";
                    }
                    $medical_amt =  $ResortBudgetCost['MEDICAL INSURANCE - INTERNATIONAL'];
                    $EmployeeInsurance->insurance_policy_number =isset( $EmployeeInsurance->insurance_policy_number) ? $EmployeeInsurance->insurance_policy_number :  'N/A';
                    $EmployeeInsurance->cost =   $EmployeeInsurance->Premium.' '.$EmployeeInsurance->Currency ??  $medical_amt ['amount'].' '.$medical_amt['unit'];
                    $EmployeeInsurance->employee_id =base64_encode($EmployeeInsurance->employee_id);
                    $EmployeeInsurance->insurance_end_date = Carbon::parse($EmployeeInsurance->insurance_end_date)->format('d M Y');

                }


                if($child->QuotaslotShow == 'yes')
                {
                

                    $QuotaSlotRenewalPaid =  QuotaSlotRenewal::where('employee_id', $child->Employee_id)
                                                            ->where('resort_id', $this->resort->resort_id)
                                                            ->orderBy('Month', 'ASC')
                                                            ->get();
                    $QuotaSlotPaidAmt = $QuotaSlotRenewalPaid->where("Status","Paid")->sum('Amt');
                    $QuotaSlotUnPaidAmt = $QuotaSlotRenewalPaid->where("Status","Unpaid")->sum('Amt');
                    $QuotaSlotVariable = QuotaSlotRenewal::where('Status','Unpaid')->whereDate('Due_date',$child->QuotaslotDate)->where('employee_id', $child->Employee_id)->where("Status","Unpaid")->where('resort_id', $this->resort->resort_id)->orderBy('Month', 'ASC')->first();
            
                    $QuotaSlotPayableAmt = $QuotaSlotPaidAmt + $QuotaSlotUnPaidAmt;
                }
                else
                {
                    $QuotaSlotVariable        = '';
                    $QuotaSlotPaidAmt         = 0.00;
                    $QuotaSlotUnPaidAmt       = 0.00;
                    $QuotaSlotPayableAmt      = 0.00;
                }

            
            if($child->WorkPermitShow == 'yes')
            {
               

                $WorkPermit =  WorkPermit::where('employee_id', $child->Employee_id)->where('resort_id', $this->resort->resort_id)->orderBy('Month', 'ASC')
                                                        ->get();

                    $WorkPermitPaidAmt = $WorkPermit->where("Status","Paid")->sum('Amt');
                    $WorkPermitUnPaidAmt = $WorkPermit->where("Status","Unpaid")->sum('Amt');

                    $WorkPermitCommonVariable = WorkPermit::where('employee_id', $child->Employee_id)->whereDate('Due_date',$child->WorkPermitDate)->where("Status","Unpaid")->where('resort_id', $this->resort->resort_id)->orderBy('Month', 'ASC')->first();
               
                                     
                    $WorkPermitPayableAmt = $WorkPermitPaidAmt + $WorkPermitUnPaidAmt;
            }
            else
            {
                $WorkPermitCommonVariable = '';
                $WorkPermitPayableAmt    = 0.00;
                $WorkPermitPaidAmt       = 0.00;
                $WorkPermitUnPaidAmt     = 0.00;
            }
             
            $VisaRenewal = VisaRenewal::where('employee_id',$child->Employee_id)->where('resort_id',$this->resort->resort_id)->first(['employee_id','Visa_Number','WP_No','start_date','end_date','visa_file']);
            if($VisaRenewal) 
            {
                $Visaend = Carbon::parse($VisaRenewal->end_date);
                $months_diff = $start->diffInMonths($Visaend);
                if ($months_diff < 1) 
                {
                    $days_diff = $start->diffInDays($Visaend);
                    $VisaRenewal->VisaRenewalTime = "Expires in $days_diff days";
                } 
                else
                {
                    $VisaRenewal->VisaRenewalTime = "$months_diff month(s) remaining";
                }
                
                $visa_amt =  $ResortBudgetCost['VISA FEE'];
                
                $VisaRenewal->Amt = number_format($ResortBudgetCost['VISA FEE']['amount'],2).' '.$ResortBudgetCost['VISA FEE']['unit'] ?? $visa_amt['amount'].' '.$visa_amt['unit'];
                $VisaRenewal->end_date = Carbon::parse($VisaRenewal->end_date)->format('d M Y');
                $VisaRenewal->employee_id =base64_encode($VisaRenewal->employee_id);
                $VisaRenewal->Validitydate = "Form  ".Carbon::parse($VisaRenewal->start_date)->format('d M Y') .' To '.Carbon::parse($VisaRenewal->end_date)->format('d M Y');
            }
            else
            {
                $VisaRenewal = null;
            }
              
        }
        else
        {
            $PaymentRequest_id        = '';
            $Employees                = null;
            $child                    = null;
            $WorkPermitMedicalRenewal = null;
            $EmployeeInsurance        = null;
            $QuotaSlotPaidAmt         = 0.00;
            $QuotaSlotUnPaidAmt       = 0.00;
            $QuotaSlotPayableAmt      = 0.00;
            $WorkPermitPayableAmt     = 0.00;
            $WorkPermitPaidAmt        = 0.00;
            $WorkPermitUnPaidAmt      = 0.00;
        }
        
        

           
        return view('resorts.Visa.PaymentRequest.renewal', compact('page_title','VisaRenewal','WorkPermitCommonVariable','WorkPermitPayableAmt','WorkPermitPaidAmt','WorkPermitUnPaidAmt','QuotaSlotVariable','QuotaSlotPayableAmt','QuotaSlotPaidAmt','QuotaSlotUnPaidAmt','WorkPermitMedicalRenewal','EmployeeInsurance','child', 'Employees', 'PaymentRequest_id'));
    }
 



   
}
