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
use App\Models\ResortBudgetCost;
use App\Models\VisaEmployeeExpiryData;
use Carbon\Carbon;
use App\Models\WorkPermit;
use App\Models\VisaNationality;
use App\Models\TotalExpensessSinceJoing;
class FetchDataAiController extends Controller
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
    public function index()
    {
        $page_title = 'Xpat Sync';
        return view('resorts.Visa.XpactSync.index',compact('page_title'));  
    }

    public function store(Request $request)
    {
        $Xpatfile = $request->file('Xpatfile');

        /*
            Visa renewal cost 
            Work permit medical renewal is a  Work Visa Medical test fee
            Insurance renewal is a medical insurance - international
        */  

        $ResortBudgetCost = Common::VisaRenewalCost($this->resort->resort_id);

        if(isset($Xpatfile))
        {
            $xpat_sync = env('AI_extract_work_details_URL').'xpat_sync';   
            $curl = curl_init();

            $postFields = ['file' => new \CURLFile($Xpatfile->getRealPath(), $Xpatfile->getMimeType(), $Xpatfile->getClientOriginalName()),'doc_type' => "xpat_sync",];
            curl_setopt_array($curl, [
                CURLOPT_URL => $xpat_sync,
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
            if ($err) 
            {
                 return response()->json([
                    'success' => false,
                    'errors' => ['message' => $err]
                ], 422);
            } 
            $response1= $response;
            $AI_Data = json_decode($response, true); 
            
            $passport_number = preg_replace('/[^A-Za-z0-9]/', '',$AI_Data['extracted_fields']["Employee's Passport Number"]) ?? '';
            $employee    = Employee::with(['resortAdmin'])->where('resort_id',$this->resort->resort_id)->where("passport_number",'z6979971')->first();
            
            if (!$employee) 
            {
                return response()->json([
                    'success' => false,
                    'errors' => ['message' => 'Employee not found']
                ], 422);
            }
            else
            {
            
                $VisaNationality = VisaNationality::where('resort_id', $this->resort->resort_id)
                                                    ->where('nationality', $employee->nationality)
                                                    ->first();
                if (!$VisaNationality) 
                {
                    return response()->json([
                        'success' => false,
                        'errors' => ['message' => 'Please Add  Deposit Rate for the'.$employee->nationality]
                    ], 422);
                }
                else
                {
                    $QuotaSlotExit = QuotaSlotRenewal::where('employee_id', $employee->id)->first();
                    if($QuotaSlotExit) 
                    {
                        $name =  $employee->resortAdmin->first_name.' '.$employee->resortAdmin->last_name;
                        return response()->json([
                            'success' => false,
                            'errors' => ['message' => "Quota Slot Renewal already exists for {$name}. Please proceed with renewal."]
                        ], 422);
                    }
                    
                    $joiningDate = Carbon::parse($employee->joining_date)->startOfMonth(); // e.g., 2025-02-01
                    $endMonth = Carbon::create($joiningDate->year, 12, 1); // End at Dec of joining year
                    $QuotaSlotFees = $request->file('QuotaSlotFees');
                    if(isset($QuotaSlotFees))
                    { 
                        $insuranceAmt=0.00;
                        $workPermitAmt=0.00;
                        $workPermitMedicalAmt=0.00;
                        $visaAmt=0.00;
                        if($AI_Data['extracted_fields']['Visa Issued Date'])
                        {
                            $visaAmtCost =  $ResortBudgetCost['VISA FEE'] ?? null;

                            VisaRenewal::create([
                                'resort_id' => $this->resort->resort_id,
                                'employee_id' => $employee->id,
                                'WP_No'=> $AI_Data['extracted_fields']['Work Permit Number'] ?? null,
                                'start_date' => Carbon::parse($AI_Data['extracted_fields']['Visa Issued Date'])->format('Y-m-d'),
                                'end_date' => Carbon::parse($AI_Data['extracted_fields']['Visa Expiry Date'])->format('Y-m-d'),
                                'Amt' => $visaAmtCost['amount'] ?? 0.00,
                            ]);
                            $visaAmt = $visaAmtCost['amount'] ?? 0.00;
                        }
                        if($AI_Data['extracted_fields']['Insurance Expiry Date'])
                        {
                            $medical_data =  $ResortBudgetCost['MEDICAL INSURANCE - INTERNATIONAL'] ?? null;

                            EmployeeInsurance::create([
                                'resort_id' => $this->resort->resort_id,
                                'employee_id' => $employee->id,
                                'Premium' => $medical_data['amount'] ?? 0.00,
                                "Currency"=> $medical_data['unit'] ?? null,
                                'insurance_start_date' => Carbon::parse($AI_Data['extracted_fields']['Insurance Expiry Date'])->format('Y-m-d'),
                                'insurance_end_date' => Carbon::parse($AI_Data['extracted_fields']['Insurance Expiry Date'])->format('Y-m-d'),
                            ]);

                             $insuranceAmt=$medical_data['amount'];
                        }

                        $monthlyEntries = [];
                        if (!empty($AI_Data['extracted_fields']['Work Permit Expiry Date (Expiry On)'])) 
                        {
                             $Work_permit_cost =  $ResortBudgetCost['WORK PERMIT FEE'] ?? null;
dd($Work_permit_cost);
                            $expiryDate = Carbon::parse($AI_Data['extracted_fields']['Work Permit Expiry Date (Expiry On)'])->endOfMonth(); // e.g., 2025-05-31

                            $totalMonths = $joiningDate->diffInMonths($endMonth) + 1;
                            $totalCost = $Work_permit_cost['amount'] ?? 0.00;
                            $monthlyCost = round($totalCost / $totalMonths, 2);
                            $currency = $Work_permit_cost['unit'] ?? null;
                  
                            $workPermitAmt = $totalCost;
                            for ($i = 0; $i < $totalMonths; $i++) 
                            {
                                $monthStart = $joiningDate->copy()->addMonths($i);
                                $monthEnd = $monthStart->copy()->endOfMonth();
                                $nextMonthStart = $monthStart->copy()->addMonth()->startOfMonth();
                                $monthlyEntries[] = 
                                        [
                                            'resort_id'    => $this->resort->resort_id,
                                            'employee_id'  => $employee->id,
                                            'Month'        => $monthStart->format('m'),
                                            'Payment_Date' => $monthEnd->format('Y-m-d'),
                                            'Due_Date'     => $nextMonthStart->format('Y-m-d'),
                                            'status'       => $monthEnd->lte($expiryDate) ? 'Paid' : 'Unpaid',
                                            'Amt'          => $monthlyCost,
                                            'currency'     => $currency,
                                            'created_at'   => now(),    
                                        ];
                            }

                            dd( $monthlyEntries);
                            WorkPermit::insert($monthlyEntries);
                        }

                        $lastDueDateForDecember = collect($monthlyEntries)
                                ->filter(fn($entry) => $entry['Month'] === '12')
                                ->last()['Due_Date'] ?? null;
                 
                        if($AI_Data['extracted_fields']['Insurance Expiry Date'])
                        {

                            // Here Work permit 
                            $medical_data =  $ResortBudgetCost['WORK VISA MEDICAL TEST FEE'] ?? null;

                                
                                WorkPermitMedicalRenewal::create([
                                            'resort_id' => $this->resort->resort_id,
                                            'employee_id' => $employee->id,
                                            'Amt' => $medical_data['amount'] ?? 0.00,
                                            'Currency'=>$medical_data['unit']?? null,
                                            'start_date' => Carbon::parse($joiningDate)->format('Y-m-d'),
                                            'end_date'=>Carbon::parse($lastDueDateForDecember)->format('Y-m-d')
                                        ]);
                             $workPermitMedicalAmt =  $medical_data['amount'] ?? 0.00;
                        }

                        //$qotaslotAMt =  $ResortBudgetCost['QUOTA SLOT DEPOSIT'] ?? null;
                        $qotaslotAMt =  $ResortBudgetCost['QUOTA SLOT DEPOSIT'] ?? 0.00;
                        $Eleven_month_installment= ($qotaslotAMt['amount'] - 174) / 11 ?? 0.00;

                        $totalCost = $qotaslotAMt['amount'] ?? 0.00;
                        TotalExpensessSinceJoing::create(['resort_id'=> $this->resort->resort_id,
                                                        'employees_id' => $employee->id,
                                                        'Deposit_Amt' =>   $VisaNationality->amt?? 0.00,
                                                        'Total_work_permit' => $workPermitAmt ?? 0.00,
                                                        'Total_slot_Payment' => $totalCost ?? 0.00,
                                                        'Total_insurance_Payment' => $insuranceAmt ?? 0.00,
                                                        'Total_Work_Permit_Medical_Payment' => $workPermitMedicalAmt ?? 0.00,
                                                        "Total_Visa_Payment"=>$visaAmt ?? 0.00,
                                                        'Date' => Carbon::now()->format('Y-m-d'),
                                                        'Year'=> Carbon::now()->format('Y'),
                                                    ]);
                        VisaEmployeeExpiryData::where('resort_id', $this->resort->resort_id)
                                                        ->where('employee_id', $employee->id)
                                                        ->where('DocumentName', 'Other')
                                                        ->delete();
                        VisaEmployeeExpiryData::create(['resort_id' => $this->resort->resort_id,
                                                            'employee_id' => $employee->id,
                                                            'File_child_id' =>  null,
                                                            'Ai_extracted_data' => $response1,
                                                            'DocumentName' =>"Other" ?? null
                                                        ]);

                                                        
                            $payment_schedule = env('AI_extract_work_details_URL').'payment_schedule';   
                            $curl = curl_init();
                            $postFields = ['file' => new \CURLFile($QuotaSlotFees->getRealPath(),
                                            $QuotaSlotFees->getMimeType(), 
                                            $QuotaSlotFees->getClientOriginalName()),
                                            'doc_type' => "payment_schedule"
                                            ];
                                curl_setopt_array($curl, [
                                    CURLOPT_URL => $payment_schedule,
                                    CURLOPT_RETURNTRANSFER => true,
                                    CURLOPT_POST => true,
                                    CURLOPT_POSTFIELDS => $postFields,
                                    CURLOPT_HTTPHEADER => ['Accept: application/json',],
                                ]);
                                $responseQuotaSlot = curl_exec($curl);
                                $err = curl_error($curl);
                                curl_close($curl);
                            if($err) 
                            {
                                return response()->json([
                                    'success' => false,
                                    'errors' => ['message' => $err]
                                ], 422);
                            }   
                            $monthly_data =  json_decode($responseQuotaSlot, true);
                            if(!empty($monthly_data['extracted_fields']))
                            {
                                DB::beginTransaction();
                                foreach($monthly_data['extracted_fields'] as $key => $value)
                                {
                                    $amt = ( $key == 0 ) ?  174 : $Eleven_month_installment;
                                    if($value['State'] == 'FULLY PAID')
                                    {
                                    $status = "Paid";
                                    }
                                    else
                                    {
                                        $status = "Unpaid";
                                    }
                                    QuotaSlotRenewal::create([
                                                                'resort_id'=>$this->resort->resort_id,
                                                                'Due_Date'=>$value['DatePaymentDueOn'],
                                                                'employee_id'=> $employee->id,
                                                                'Month'=> $value['Month'],
                                                                "Currency"=>"MVR",
                                                                "Amt"=> $amt,
                                                                "Status"=> $status,
                                                            ]); 
                                }
                                DB::commit();
                                return response()->json(['success' => true,'msg' => 'Quota Slot renewal Created Successfully.',],200);
                            }
                            else
                            {
                                DB::rollBack();
                                return response()->json([
                                    'success' => false,
                                    'errors' => ['message' => 'Quota Slot Fees file is not valid']
                                ], 422);
                            }
                    }
                    else
                    {
                        return response()->json([
                            'success' => false,
                            'errors' => ['message' => 'Quota Slot Fees file is missing']
                        ], 422);
                    }
                }
              
            }
        }
        else
        {
            if(!isset($Xpatfile))
            {
                return response()->json([
                'success' => false,
                'errors' => ['message' => 'Xpact File is Missing ']
                ], 422);
            }
        }
    }

    
}
