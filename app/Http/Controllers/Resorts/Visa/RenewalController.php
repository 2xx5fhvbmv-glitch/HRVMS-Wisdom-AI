<?php

namespace App\Http\Controllers\Resorts\Visa;
use URL;
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
use App\Models\TotalExpensessSinceJoing;
use App\Models\WorkPermit;
use App\Models\EmployeeInsuranceChild;
use App\Models\WorkPermitMedicalRenewalChild;
use App\Models\VisaEmployeeExpiryData;
use Validator;
use App\Models\PaymentRequestChild;
use App\Models\PaymentRequest;
class RenewalController extends Controller
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
   

    public function index()
    {
        $page_title = 'Visa Renewal';
        $Employee= Employee::with(['resortAdmin','position'])->where("nationality","!=","Maldivian")
            ->where('resort_id',$this->resort->resort_id)
            ->get()->map(function($i){
            $i->Name = $i->resortAdmin->first_name.' '.$i->resortAdmin->last_name;
            $i->profile = Common::getResortUserPicture($i->resortAdmin->id);
            return $i;
        });
        return view('resorts.Visa.Renewal.index',compact('page_title','Employee'));
    }
    public function GetEmployeeDetails(Request $request)
    {
        $emp_id = base64_decode($request->emp_id);
        $employee= Employee::with(['resortAdmin','position'])->where('id',$emp_id)->first();
        $start_date= carbon::now()->format('Y-m-d');
        $start = Carbon::parse($start_date);
        /*
            Visa renewal cost 
            Work permit medical renewal is a  Work Visa Medical test fee
            Insurance renewal is a 
        */  
        $ResortBudgetCost = Common::VisaRenewalCost($this->resort->resort_id);

        // Visa Renewal Details
            $VisaRenewal = VisaRenewal::where('employee_id',$emp_id)->where('resort_id',$this->resort->resort_id)->first(['employee_id','Visa_Number','WP_No','start_date','end_date','visa_file']);
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
            // Insurance Renewal Details
            $EmployeeInsurance = EmployeeInsurance::where('employee_id', $emp_id)
                                ->where('resort_id', $this->resort->resort_id)
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

                $EmployeeInsurance->cost =   $EmployeeInsurance->Premium.' '.$EmployeeInsurance->Currency ??  $medical_amt ['amount'].' '.$medical_amt['unit'];
                $EmployeeInsurance->employee_id =base64_encode($EmployeeInsurance->employee_id);
                $EmployeeInsurance->insurance_end_date = Carbon::parse($EmployeeInsurance->insurance_end_date)->format('d M Y');

            }
        
            $WorkPermitMedicalRenewal = WorkPermitMedicalRenewal::where('employee_id',$emp_id)->where('resort_id',$this->resort->resort_id)->first(['employee_id','Reference_Number','Amt','Currency','Medical_Center_name','start_date','end_date','medical_file']);
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
            // Quota Slot Renewal Details
            $firstDateOfMonth = Carbon::now()->startOfMonth(); 
            $nextmonthFirstDateOfMonth =  Carbon::now()->endOfMonth();
            $QuotaSlotRenewal = QuotaSlotRenewal::where('employee_id', $emp_id)
                                                ->where('resort_id', $this->resort->resort_id)
                                                ->orderByDesc('id')
                                                ->first(['employee_id', 'Month', 'Amt', 'Payment_Date', 'Due_Date', 'Currency', 'Reciept_file','PaymentType']);
            if($QuotaSlotRenewal)
            {
                if ($QuotaSlotRenewal->PaymentType == "Installment") 
                {
                    $QuotaSlotRenewal_end_date = Carbon::parse($QuotaSlotRenewal->Due_Date);
                } 
                else
                {
                    $QuotaSlotRenewal_end_date = Carbon::parse($QuotaSlotRenewal->Due_Date);
                }

                $QuotaSlotRenewal_months_diff = $start->diffInMonths($QuotaSlotRenewal_end_date);
                if ($QuotaSlotRenewal_months_diff < 1) 
                {
                    $days_diff = $start->diffInDays($QuotaSlotRenewal_end_date);
                    $QuotaSlotRenewal->QuotaSlotRenewalDate = "Expires in $days_diff days";
                } 
                else
                {
                    $QuotaSlotRenewal->QuotaSlotRenewalDate = "$QuotaSlotRenewal_months_diff month(s) remaining";
                } 
                $QuotaSlotRenewal->QuotaSlotRenewal_end_date = Carbon::parse($QuotaSlotRenewal_end_date)->format('Y-m-d');
                $QuotaSlotRenewal->employee_id =base64_encode($QuotaSlotRenewal->employee_id);
                if($QuotaSlotRenewal)
                {
                    $QuotaSlotRenewal->NewSlot =Carbon::parse($QuotaSlotRenewal->Payment_Date)->format('d M Y') .' Until 12 Months';
                }
                else
                {
                    $QuotaSlotRenewal->NewSlot =Carbon::now()->format('d M Y') .' Until 12 Months';
                }

            }
            $WorkPermitRenewal = WorkPermit::where('employee_id', $emp_id)
                ->where('resort_id', $this->resort->resort_id)
                ->orderByDesc('id')
                ->first(['id', 'employee_id', 'Month', 'Amt', 'Payment_Date', 'Due_Date', 'Currency', 'Reciept_file', 'PaymentType']);
     
            if($WorkPermitRenewal)
            {
                if ($WorkPermitRenewal->PaymentType == "Installment") 
                {
                    $WorkPermitRenewal_end_date = Carbon::parse($WorkPermitRenewal->Due_Date);
                } 
                else
                {
                    $WorkPermitRenewal_end_date = Carbon::parse($WorkPermitRenewal->Due_Date);
                }

                $WorkPermitRenewal_months_diff = $start->diffInMonths($WorkPermitRenewal_end_date);
                if ($WorkPermitRenewal_months_diff < 1) 

                {
                    $days_diff = $start->diffInDays($WorkPermitRenewal_end_date);
                    $WorkPermitRenewal->WorkPermitRenewalDate = "Expires in $days_diff days";
                } 
                else
                {
                    $WorkPermitRenewal->WorkPermitRenewalDate = "$WorkPermitRenewal_months_diff month(s) remaining";
                } 
                $WorkPermitRenewal->WorkPermitRenewal_end_date = Carbon::parse($WorkPermitRenewal_end_date)->format('Y-m-d');
                $WorkPermitRenewal->employee_id =base64_encode($WorkPermitRenewal->employee_id);
                if($WorkPermitRenewal)
                {
                    $WorkPermitRenewal->NewSlot =Carbon::parse($WorkPermitRenewal->Payment_Date)->format('d M Y') .' Until 12 Months';
                }
                else
                {
                    $WorkPermitRenewal->NewSlot =Carbon::now()->format('d M Y') .' Until 12 Months';
                }

            }

            
        // dd(["VisaRenewal"=>$VisaRenewal,"EmployeeInsurance"=>$EmployeeInsurance,"WorkPermitMedicalRenewal"=>$WorkPermitMedicalRenewal,"QuotaSlotRenewal"=>$QuotaSlotRenewal]);
        return response()->json(['success'=>true,'data' =>["WorkPermitRenewal"=>$WorkPermitRenewal,"VisaRenewal"=>$VisaRenewal,"EmployeeInsurance"=>$EmployeeInsurance,"WorkPermitMedicalRenewal"=>$WorkPermitMedicalRenewal,"QuotaSlotRenewal"=>$QuotaSlotRenewal]]);
    }

    public function UploadSeparetFileUsingAi(Request $request)
    {

 

        $validator = Validator::make($request->all(),
                    [
                        'emp_id' => 'required|string',
                        'flag'   => 'required|in:visa,insurance,work_permit_card_Test_Fee,slot_payment',
                        'file'   => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048', // 2MB max
                    ],
                    [
                        'emp_id.required' => 'Employee ID is required.',
                        'flag.required'   => 'Document type is required.',
                        'file.required'   => 'File is required.',
                        'file.mimes'      => 'File must be a PDF, JPG, JPEG, or PNG.',
                        'file.max'        => 'File size must not exceed 2MB.',
                    ]);

        if ($validator->fails()) 
        {
            return response()->json([
                'success' => false,
                'msg' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $emp_id =  base64_decode($request->emp_id);

        $child_id =  base64_decode($request->child_id);
        $employee = Employee::where('resort_id', $this->resort->resort_id)->where('id', $emp_id)->first("Emp_id");
        $TotalExpensessSinceJoing = TotalExpensessSinceJoing::where('resort_id', $this->resort->resort_id)->where('employees_id', $emp_id)->first();
        if (!$TotalExpensessSinceJoing) 
        {
            $TotalExpensessSinceJoing = new TotalExpensessSinceJoing();
        }
        $file = $request->file('file');
        $doc_type = $request->flag;
        $url = env('AI_extract_work_details_URL').$doc_type;


        if($doc_type=="insurance")
        {
            $doc_type = "insurance";
        }
        elseif($doc_type=="work_permit_card_Test_Fee")
        {
            $doc_type = "medical_report";
        }
        elseif($doc_type=="visa")
        {
            $doc_type = "visa";
        }

                       
            $url = env('AI_extract_work_details_URL').$doc_type; 

            $ResortBudgetCost = Common::VisaRenewalCost($this->resort->resort_id);
            $curl = curl_init();

                $curl = curl_init();
                $postFields = [
                                'file' => new \CURLFile($file->getRealPath(), $file->getMimeType(), $file->getClientOriginalName()),
                                'doc_type' => $doc_type,
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
                $ai_encode =$response;
                $AI_Data = json_decode($response, true);
       
        $main_folder = $this->resort->resort->resort_id;
        $employee    = Employee::where('resort_id',$this->resort->resort_id)->where("id",$emp_id)->first();

        if(!$employee)
        {
            return response()->json(['success'=>false,'message'=>'Employee not found','status'=>404]);
        }
        else
        {
            if($doc_type=="insurance")
            {

                $EmployeeInsurance = EmployeeInsurance::where('employee_id', $emp_id)
                                                        ->where('resort_id', $this->resort->resort_id)
                                                        ->first([
                                                            'id',
                                                            'resort_id',
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
            
                if($EmployeeInsurance)
                {
                    $aws =  Common::AWSEmployeeFileUpload($this->resort->resort_id,$file,$employee->Emp_id);
              
                    if($aws['status'] == false)
                    {
                        
                        return response()->json(['success'=>false,'message'=>$aws['msg'],'status'=>500]);
                    }
                  
                        DB::beginTransaction();
                        try
                        {
                            $Insurance_data =  $ResortBudgetCost['MEDICAL INSURANCE - INTERNATIONAL'] ?? null;
                            EmployeeInsuranceChild::create([
                                                        'employee_insurances_id' => $EmployeeInsurance->id,
                                                        'Premium' => $EmployeeInsurance->Premium,
                                                        'insurance_company' => $EmployeeInsurance->insurance_company,
                                                        'insurance_policy_number' => $EmployeeInsurance->insurance_policy_number,
                                                        'insurance_coverage' => $EmployeeInsurance->insurance_coverage,
                                                        'insurance_start_date' => $EmployeeInsurance->insurance_start_date,
                                                        'insurance_end_date' =>$EmployeeInsurance->insurance_end_date,
                                                       
                                                    ]);
                            EmployeeInsurance::where('resort_id', $this->resort->resort_id)
                                                       ->where('employee_id', $emp_id)
                                                       ->update(["resort_id"=>$this->resort->resort_id,'employee_id' => $emp_id],
                                                    [   
                                                        'insurance_file' =>$aws['Chil_file_id'] ? $aws['Chil_file_id'] : null,
                                                        'resort_id'  => $this->resort->resort_id,
                                                        'employee_id'=> $emp_id,
                                                        'Premium'    => $Insurance_data['amount'] ?? 0.00,
                                                        "Currency"   => $Insurance_data['unit'] ?? null,
                                                        'insurance_file'=> $aws['Chil_file_id'] ?? null,
                                                        'insurance_start_date' => Carbon::parse($AI_Data['extracted_fields']['Insurance Expiry Date'])->format('Y-m-d'),
                                                        'insurance_end_date' => Carbon::parse($AI_Data['extracted_fields']['Insurance Expiry Date'])->format('Y-m-d'),
                                                    ]);

                            $TotalExpensessSinceJoing->Total_insurance_Payment += $Insurance_data['amount'] ?? 0.00;
                            $TotalExpensessSinceJoing->save();
                            
                            VisaEmployeeExpiryData::where('resort_id', $this->resort->resort_id)
                            ->where('employee_id', $employee->id)
                            ->where('DocumentName', $doc_type)
                            ->delete();
                            VisaEmployeeExpiryData::create(['resort_id' => $this->resort->resort_id,
                                'employee_id' => $employee->id,
                                'File_child_id' =>  $aws['Chil_file_id']?? null,
                                'Ai_extracted_data' => $ai_encode ?? null,
                                'DocumentName' => $doc_type ?? null
                            ]);


                            $PaymentRequestChild = PaymentRequestChild::where('employee_id', $emp_id)->where('id', $child_id)->first();
                            $PaymentRequestChild->OngoingSteps = $PaymentRequestChild->OngoingSteps + 1;
                            if($PaymentRequestChild->OverallSteps == $PaymentRequestChild->OngoingSteps )
                            {
                                $PaymentRequestChild->ChildStatus = 'Complete';
                                PaymentRequest::where('id', $PaymentRequestChild->Requested_Id)->update(['Status' => 'Approved']);
                            }
                            $PaymentRequestChild->InsuranceShow = 'No';
                            $PaymentRequestChild->InsuranceStep = 'Yes';
                            $PaymentRequestChild->save();
                            DB::Commit();

                            return response()->json(['success'=>true,'message'=>'MedicaMedical Insurance - International Renewal Completed','status'=>200]);
                        }
                        catch(\Exception $e)
                        {
                            DB::rollBack();
                            return response()->json(['success'=>false,'message'=>'File Upload Failed','status'=>500]);
                        }
                }
                else
                {
                    return response()->json(['success'=>false,'message'=>'File Upload Failed','status'=>500]);
                }
            }
            if($doc_type=="medical_report")
            {
                $last_work_permit_insurance = WorkPermitMedicalRenewal::where('employee_id', $emp_id)->orderByDesc('id')->where('resort_id', $this->resort->resort_id)->first(); // Delete previous records if any
                    if($last_work_permit_insurance)
                    {
                        DB::beginTransaction();
                        try{
                                $aws =  Common::AWSEmployeeFileUpload($this->resort->resort_id,$file,$employee->Emp_id);

                                if($aws['status'] == false)
                                {
                                   
                                    return response()->json(['success'=>false,'message'=>$aws['msg'],'status'=>500]);
                                }
                                WorkPermitMedicalRenewalChild::create(['permit_medical_id'=>$last_work_permit_insurance->id,
                                                                'Reference_Number' => $last_work_permit_insurance->Reference_Number ?? $AI_Data['extracted_fields']['Reference Number(Generally starts with MOH)'],
                                                                'Cost' => $last_work_permit_insurance->Cost,
                                                                'Amt' => $last_work_permit_insurance->Amt,
                                                                'Medical_Center_name' => $last_work_permit_insurance->Medical_Center_name ?? $AI_Data['extracted_fields']['Medical Center Name'],
                                                                'start_date' => $last_work_permit_insurance->start_date,
                                                                'end_date' => $last_work_permit_insurance->end_date,
                                                                'medical_file' => $last_work_permit_insurance->medical_file
                                                            ]);
                                $medical_data =  $ResortBudgetCost['WORK VISA MEDICAL TEST FEE'] ?? null;
                                $start_date   =  Carbon::parse($AI_Data['extracted_fields']['Last Medical Test Date(Mentioned in Certification of Doctor)'])->format('Y-m-d');
                                $end_date     =   Carbon::parse($AI_Data['extracted_fields']['Last Medical Test Date(Mentioned in Certification of Doctor)'])->copy()->addYear();  
                                WorkPermitMedicalRenewal::where('resort_id', $this->resort->resort_id)
                                                       ->where('employee_id', $emp_id)
                                                       ->update([
                                                                    'Reference_Number' =>$AI_Data['extracted_fields']['Reference Number(Generally starts with MOH)'],
                                                                    'Medical_Center_name' => $AI_Data['extracted_fields']['Medical Center Name'],
                                                                    'Amt'          => $medical_data['amount'] ?? 0.00,
                                                                    'Currency'     => $medical_data['unit']?? null,
                                                                    'start_date'   => $start_date,
                                                                    'end_date'     =>$end_date->format('Y-m-d'),
                                                                    'medical_file' => $aws['Chil_file_id']
                                                                ]);
                                $workPermitMedicalAmt =  $medical_data['amount'] ?? 0.00;
                                $TotalExpensessSinceJoing->Total_Work_Permit_Medical_Payment += $workPermitMedicalAmt ?? 0.00;
                                $TotalExpensessSinceJoing->save();
                                 
                            $PaymentRequestChild = PaymentRequestChild::where('employee_id', $emp_id)->where('id', $child_id)->first();
                            $PaymentRequestChild->OngoingSteps = $PaymentRequestChild->OngoingSteps + 1;
                            if($PaymentRequestChild->OverallSteps == $PaymentRequestChild->OngoingSteps )
                            {
                                $PaymentRequestChild->ChildStatus = 'Complete';
                                PaymentRequest::where('id', $PaymentRequestChild->Requested_Id)->update(['Status' => 'Approved']);
                            }
                            $PaymentRequestChild->MedicalReportShow = 'No';
                            $PaymentRequestChild->MedicalReportStep = 'Yes';
                            $PaymentRequestChild->save();  
                        
                        
                        DB::Commit();
                                return response()->json(['success'=>true,'message'=>'Work Permit Medical Test Fee Renewal Successfully','status'=>200]);
                            }
                            catch(\Exception $e)
                            {
                                // If any error occurs, rollback the transaction
                                DB::rollBack();
                                return response()->json(['success'=>false,'message'=>'File Upload Failed','status'=>500]);
                            }
                    }
                    else    
                    {
                        return response()->json(['success'=>false,'message'=>'File Upload Failed','status'=>500]);
                    }
            }
            if($doc_type=="visa")
            {
                $VisaRenewal = VisaRenewal::where("resort_id",$this->resort->resort_id)->where("employee_id",$emp_id)->orderByDesc('id')->first();
                if($VisaRenewal)
                {  
                        $aws =  Common::AWSEmployeeFileUpload($this->resort->resort_id,$file,$employee->Emp_id);
                        
                        if($aws['status'] == false)
                        {
                            return response()->json(['success'=>false,'message'=>$aws['msg'],'status'=>500]);
                        }
                        DB::beginTransaction();
                        try{
                           $visa_amt =  $ResortBudgetCost['VISA FEE'];
                            VisaRenewalChild::create(["visa_renewal_id" => $VisaRenewal->id,
                                                    "VisaRenewal_Number" => $VisaRenewal->VisaRenewal_Number,
                                                    "WP_No"=> $VisaRenewal->WP_No,
                                                    "start_date" => $VisaRenewal->start_date,
                                                    "end_date" => $VisaRenewal->end_date,
                                                    "visa_file" => $VisaRenewal->visa_file,
                                                    "Amt" => $VisaRenewal->Amt
                                                ]);
                            $visa              =  VisaRenewal::find($VisaRenewal->id);
                            $visa->Visa_Number = $AI_Data['extracted_fields']['Passport Number(Starts with Alphabet followed by numbers)'];
                            $visa->start_date  = Carbon::createFromFormat('d/m/Y', $AI_Data['extracted_fields']['Visa Issued Date'])->format('Y-m-d');
                            $visa->end_date    = Carbon::createFromFormat('d/m/Y', $AI_Data['extracted_fields']['Visa Expiry Date'])->format('Y-m-d');
                            $visa->visa_file   = $aws['Chil_file_id'];
                            $visa->Amt         = $visa_amt['amount'];
                            $visa->save();
                            $TotalExpensessSinceJoing->Total_Visa_Payment +=  $visa_amt['amount'] ?? 0.00;
                            $TotalExpensessSinceJoing->save();
                            VisaEmployeeExpiryData::where('resort_id', $this->resort->resort_id)
                            ->where('employee_id', $employee->id)
                            ->where('DocumentName', $doc_type)
                            ->delete();
                            VisaEmployeeExpiryData::create(['resort_id' => $this->resort->resort_id,
                                'employee_id' => $employee->id,
                                'File_child_id' =>  $aws['Chil_file_id']?? null,
                                'Ai_extracted_data' => $ai_encode ?? null,
                                'DocumentName' => $doc_type ?? null
                            ]);
                        $PaymentRequestChild = PaymentRequestChild::where('employee_id', $emp_id)->where('id', $child_id)->first();
                        $PaymentRequestChild->OngoingSteps = $PaymentRequestChild->OngoingSteps + 1;
                        if($PaymentRequestChild->OverallSteps == $PaymentRequestChild->OngoingSteps )
                        {
                            $PaymentRequestChild->ChildStatus = 'Complete';
                            PaymentRequest::where('id', $PaymentRequestChild->Requested_Id)->update(['Status' => 'Approved']);
                        }
                        $PaymentRequestChild->VisaShow = 'No';
                        $PaymentRequestChild->VisaStep = 'Yes';
                        $PaymentRequestChild->save();  
                        DB::Commit();
                   
                        return response()->json(['success'=>true,'message'=>'Visa Renewal Successfully','status'=>200]);
                      
                     }
                    catch(\Exception $e)
                    {
                        // If any error occurs, rollback the transaction
                        DB::rollBack();
                        return response()->json(['success'=>false,'message'=>'File Upload Failed','status'=>500]);
                    }
                   
                }
                else
                {
                    return response()->json(['success'=>false,'message'=>'File Upload Failed','status'=>500]);
                }
            }
        } 
        

   
       
    }

    public function UploadQuotaSlot(Request $request)
    {
        $emp_id = base64_decode($request->emp_id);
        $flag = $request->flag;
    
        $payment_type = $request->payment_type;
        
        $ResortBudgetCost = Common::VisaRenewalCost($this->resort->resort_id);
   
        $start_date = Carbon::today();
       
        if($flag == "WorkPermit")
        {
            $WorkPermit_amt =  $ResortBudgetCost['WORK PERMIT FEE'] ?? null;

            if($payment_type =="Lumpsum")
            { 
                $next_year_due_date = $start_date->copy()->addYear();
                WorkPermit::create([    'resort_id'=>$this->resort->resort_id,
                                        'Due_Date'=> $next_year_due_date->format('Y-m-d'),
                                        'employee_id'=> $emp_id,
                                        'Month'=> 12,
                                        "Currency"=>"MVR",
                                        "Amt"=> $WorkPermit_amt['amount'],
                                    ]); 
 
                return response()->json(['success'=>true,'message'=>'WorkPermit Renewal  Successfully Using the Lumpsum Payment Type','status'=>200]);
            }
            elseif($payment_type =="Installment")
            {
                $start_date = Carbon::today();

               
                for ($i = 1; $i <=12; $i++) 
                {
                    $new_date = $start_date->copy()->addMonths($i);
                    WorkPermit::create([
                                                'resort_id'=>$this->resort->resort_id,
                                                'Due_Date'=> $new_date->format('Y-m-d'),
                                                'employee_id'=> $emp_id,
                                                'Month'=> $new_date->format('m'),
                                                "Currency"=>"MVR",
                                                "Amt"=> $WorkPermit_amt['amount']/12,
                                            ]);              
                }
            return  response()->json(['success'=>true,'message'=>' WorkPermit Renewal  successfully using the Installment Payment Type.','status'=>200]);
            }
            else
            {
                return response()->json(['success'=>false,'message'=>'Please  Add  Xpact Page','status'=>500]);
            }
        } 
        if($flag =="QuotaSlot")
        {
            
            $qotaslotAMt =  $ResortBudgetCost['QUOTA SLOT DEPOSIT'] ?? 0.00;
            if($payment_type =="Lumpsum")
            { 
        

               

                $next_year_due_date = $due_date->copy()->addYear();
        
                    QuotaSlotRenewal::create([
                                            'resort_id'=>$this->resort->resort_id,
                                            'Due_Date'=> $next_year_due_date->format('Y-m-d'),
                                            'employee_id'=> $emp_id,
                                            'Month'=> 12,
                                            "Currency"=>"MVR",
                                            "Amt"=> $qotaslotAMt['amount'],
                                            ]);     

             
                return response()->json(['success'=>true,'message'=>'Quota Slot Renewal  Successfully Using the Lumpsum Payment Type','status'=>200]);
            }
            elseif($payment_type =="Installment")
            {
                $start_date = Carbon::today();
                $Eleven_month_installment= ($qotaslotAMt['amount'] - 174) / 11 ?? 0.00;
                

                for ($i = 1; $i <=11; $i++) 
                {
                   
                    $new_date = $start_date->copy()->addMonths($i);
                    $amt = ($i==1) ? 174: number_format($Eleven_month_installment,2);
                    QuotaSlotRenewal::create([
                                          'resort_id'=>$this->resort->resort_id,
                                          'Due_Date'=> $new_date->format('Y-m-d'),
                                          'employee_id'=> $emp_id,
                                          'Month'=> $new_date->format('m'),
                                          "Currency"=>"MVR",
                                          "Amt"=> $amt,
                                        ]);              
                }
               
                 $TotalExpensessSinceJoing->Total_slot_Payment += $qotaslotAMt['amount'] ?? 0.00;  
                $TotalExpensessSinceJoing->save();
            
            return  response()->json(['success'=>true,'message'=>' Quota Slot Renewal  successfully using the Installment Payment Type.','status'=>200]);
            }
            else
            {
                return response()->json(['success'=>false,'message'=>'Please  Add  Xpact Page','status'=>500]);
            }
        }

        return response()->json(['success'=>false,'message'=>'Invalid Selection','status'=>500]);
    }

    public function VerifyDetails(Request $request)
    {

       if($request->ajax()) 
        {  
    
            $flags = ['all'];
            $search = $request->search;
            $date = $request->date;
            $isChecked = $request->isChecked;

            if (in_array('all', $flags)) {
                $flags = ['visa', 'insurance', 'work_permit', 'MedicalReport', 'slot_payment'];
            }

            $filterStart = Carbon::now()->startOfMonth();
            $filterEnd = Carbon::now()->endOfMonth();


            $Employee = Employee::with(['resortAdmin', 'position', 'department', 'VisaRenewal.VisaChild', 'WorkPermitMedicalRenewal.WorkPermitMedicalRenewalChild', 'WorkPermit', 'EmployeeInsurance.InsuranceChild', 'QuotaSlotRenewal'])
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
                ->map(function ($employee) use (  $filterStart, $filterEnd) {
                    $employee->Emp_name = $employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name;
                    $employee->Emp_id = $employee->Emp_id;
                    $employee->Department_name = $employee->department->name ?? 'N/A';
                    $employee->Position_name = $employee->position->position_title ?? 'N/A';
                    $employee->ProfilePic = Common::getResortUserPicture($employee->resortAdmin->id);
                    $employee->VisaExpiryExpiryDate = $employee->InsuranceExpiryDate = $employee->WorkPermitExpiryDate = $employee->WorkPermitMedicalPermitExpiryDate = $employee->QuotaSlotAmtForThisMonth = 'N/A';

                    $employeeData = [];
                    $hasAnyFlagData = false;

                    
                        $visa = $employee->VisaRenewal;

               
                        if ($visa && Carbon::parse($visa->end_date)->between($filterStart, $filterEnd)) {
                            $employee->VisaExpiryDate = $this->getFormattedExpiryStatus($visa->end_date);
                            $employee->VisaExpiryExpiryAmt = $visa->Amt;
                            $hasAnyFlagData = true;
                    
                           
                        }
          

           
                        $insurance = $employee->EmployeeInsurance()->where('employee_id', $employee->id)->where('resort_id', $this->resort->resort_id)->orderBy('id', 'desc')->first();
                        if ($insurance && Carbon::parse($insurance->insurance_end_date)->between($filterStart, $filterEnd)) {
                            $employee->InsuranceExpiryDate = $this->getFormattedExpiryStatus($insurance->insurance_end_date);
                            $employee->Premium = $insurance->Premium;
                            $hasAnyFlagData = true;
                   
                        }
                  
                        $wpEntries = $employee->WorkPermit->where('Status','Unpaid')->sortByDesc('id');
                        $currentWP = $wpEntries->filter(fn($item) => Carbon::parse($item->Due_Date)->between($filterStart, $filterEnd))->first();
                       
                        if ($currentWP)
                        {
                            $employee->WorkPermitExpiryDate =  $this->getFormattedExpiryStatus($currentWP->Due_Date);
                             $employee->WorkPermitAmt = number_format($currentWP->Amt,2);
                            $hasAnyFlagData = true;
                         
                           
                        }

                        $med = $employee->WorkPermitMedicalRenewal;
                        if ($med && Carbon::parse($med->end_date)->between($filterStart, $filterEnd)) 
                        {
                            $employee->WorkPermitMedicalPermitExpiryDate = $this->getFormattedExpiryStatus($med->end_date);
                            $employee->WorkPermitMedicalPermitAmt        =  number_format($med->Amt,2);
                            $hasAnyFlagData = true;
                           

                   
                          
                        }
                  
                      
                        $quotaEntries = $employee->QuotaSlotRenewal->sortByDesc('id')  ; // All entries sorted descending by ID

                          $currentQuota = $employee->QuotaSlotRenewal
                                        ->filter(fn($item) => Carbon::parse($item->Expiry_Date)->between($filterStart, $filterEnd))
                                        ->where('Status', 'Unpaid')
                                        ->first();
                        $currentQuota = $quotaEntries
                            ->filter(fn($item) => Carbon::parse($item->Expiry_Date)->between($filterStart, $filterEnd))
                            ->first();
                        $encodedId = base64_encode($employee->id);
                        if ($currentQuota) 
                        {
                            $employee->QuotaSlotAmtForThisMonth = $this->getFormattedExpiryStatus($currentQuota->Due_Date);
                            $employee->QuotaSlotAmtForThisMonthAmt =$currentQuota->Amt;
                            $hasAnyFlagData = true;

                           

                           
                        }
                    

                    $employee->extra= json_encode($employeeData);
                  

                    return $hasAnyFlagData ? $employee : null;
                })->filter();

            

                return datatables()->of($Employee)
                        ->addColumn('profile_view', function ($row) {
                            $expiryBoxes = '';
                      
                                $expiryBoxes .= '<div>
                                    <label>Work Permit: MVR  '.($row->WorkPermitAmt).'</label>
                                    <p>Expires: ' . ($row->WorkPermitExpiryDate ?? '-') . '</p>
                                </div>';
                           
                                $expiryBoxes .= '<div>
                                    <label>Slot Payment: MVR '.($row->QuotaSlotAmtForThisMonthAmt).'</label>
                                    <p>Expires: ' . ($row->QuotaSlotAmtForThisMonth ?? '-') . '</p>
                                </div>';
                               $expiryBoxes .= '<div>
                                    <label>Visa: MVR ' . ($row->VisaExpiryExpiryAmt ?? '-') . '</label>
                                    <p>Expires: ' . ($row->VisaExpiryDate ?? '-') . '</p>
                                </div>';
                               $expiryBoxes .= '<div>
                                    <label>Insurance: MVR ' . ($row->Premium ?? '-') . '</label>
                                    <p>Expires: ' . ($row->InsuranceExpiryDate ?? '-') . '</p>
                                </div>';
                           
                            return '<div class="exp-Date-userbox">
                                <div class="row align-items-lg-center">
                                    <div class="col-lg-3 col-md-4">
                                        <div class="user-profilebox d-flex">
                                            <div class="img-circle">
                                                <img src="' . ($row->ProfilePic ?? 'assets/images/user-2.svg') . '" alt="user">
                                            </div>
                                            <div>
                                                <h6>' . $row->Emp_name . '<span class="badge badge-themeNew">#' . $row->Emp_id . '</span></h6>
                                                <p>' . ($row->Department_name . ' - ' . $row->Position_name) . '</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-8 col-md-7">
                                        <div class="expires-date-box">' . $expiryBoxes . '</div>
                                    </div>
                                  
                                </div>
                            </div>';
                        })
                        ->rawColumns(['profile_view'])
                        ->make(true);
        }
        $page_title= 'Visa Verify';
        return view('resorts.Visa.expiry.verify', compact('page_title'));
    }
    public function getFormattedExpiryStatus($endDate)
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

    public function OrverviewDashbordExpiry(Request $request)
    {
      
        if($request->ajax()) 
        {  
    
           
            $search = $request->search;
            $date = $request->date;

          

            $filterStart = Carbon::now()->startOfMonth();
            $filterEnd = Carbon::now()->endOfMonth();

            if ($date && strpos($date, '-') !== false) 
            {
                try 
                {
                    $parts = explode(' - ', $date);
                    $filterStart = Carbon::createFromFormat('d-m-Y', trim($parts[0]))->startOfDay();
                    $filterEnd = Carbon::createFromFormat('d-m-Y', trim($parts[1]))->endOfDay();
                } 
                catch (\Exception $e) 
                {
                    // fallback
                }
            }
        $groupedData = [
            'Visa' => [],
            'Insurance' => [],
            'WorkPermit' => [],
            'Medical' => [],
            'QuotaSlot' => [],
        ];

        $employees = Employee::with([
                'resortAdmin',
                'position',
                'department',
                'VisaRenewal',
                'WorkPermitMedicalRenewal',
                'WorkPermit',
                'EmployeeInsurance',
                'QuotaSlotRenewal'
            ])
            ->where("nationality", '!=', "Maldivian")
            ->where('resort_id', $this->resort->resort_id)
            ->get();

        // Process employees and populate grouped data
        foreach ($employees as $employee) {
            $employeeId = $employee->id;
            $employee->Emp_name = $employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name;
            $employee->Emp_id = $employee->Emp_id;
            $employee->Department_name = $employee->department->name ?? 'N/A';
            $employee->Position_name = $employee->position->position_title ?? 'N/A';
            $employee->ProfilePic = Common::getResortUserPicture($employee->resortAdmin->id);
            
            // Visa
            $visa = $employee->VisaRenewal;
            if ($visa && Carbon::parse($visa->end_date)->between($filterStart, $filterEnd)) {
                $groupedData['Visa'][] = [
                    'ExpiryDate' => $this->getFormattedExpiryStatus($visa->end_date),
                    'Amount' => number_format($visa->Amt, 2),
                    'Emp_name' => $employee->Emp_name,
                    'Emp_id' => $employee->Emp_id,
                    'Department_name' => $employee->Department_name,
                    'Position_name' => $employee->Position_name,
                    'ProfilePic' => $employee->ProfilePic
                ];
            }

            // Insurance
            $insurance = $employee->EmployeeInsurance()->where('resort_id', $this->resort->resort_id)->latest()->first();
            if ($insurance && Carbon::parse($insurance->insurance_end_date)->between($filterStart, $filterEnd)) {
                $groupedData['Insurance'][] = [
                    'ExpiryDate' => $this->getFormattedExpiryStatus($insurance->insurance_end_date),
                    'Amount' => number_format($insurance->Premium, 2),
                    'Emp_name' => $employee->Emp_name,
                    'Emp_id' => $employee->Emp_id,
                    'Department_name' => $employee->Department_name,
                    'Position_name' => $employee->Position_name,
                    'ProfilePic' => $employee->ProfilePic
                ];
            }

            // Work Permit
            $currentWP = $employee->WorkPermit->where('Status', 'Unpaid')->sortByDesc('id')
                ->filter(fn($item) => Carbon::parse($item->Due_Date)->between($filterStart, $filterEnd))
                ->first();
            if ($currentWP) {
                $groupedData['WorkPermit'][] = [
                    'ExpiryDate' => $this->getFormattedExpiryStatus($currentWP->Due_Date),
                    'Amount' => number_format($currentWP->Amt, 2),
                    'Emp_name' => $employee->Emp_name,
                    'Emp_id' => $employee->Emp_id,
                    'Department_name' => $employee->Department_name,
                    'Position_name' => $employee->Position_name,
                    'ProfilePic' => $employee->ProfilePic
                ];
            }

            // Medical
            $med = $employee->WorkPermitMedicalRenewal;
            if ($med && Carbon::parse($med->end_date)->between($filterStart, $filterEnd)) {
                $groupedData['Medical'][] = [
                    'ExpiryDate' => $this->getFormattedExpiryStatus($med->end_date),
                    'Amount' => number_format($med->Amt, 2), 
                    'Emp_name' => $employee->Emp_name,
                    'Emp_id' => $employee->Emp_id,
                    'Department_name' => $employee->Department_name,
                    'Position_name' => $employee->Position_name,
                    'ProfilePic' => $employee->ProfilePic
                ];
            }

            // Quota Slot
            $currentQuota = $employee->QuotaSlotRenewal
                ->filter(fn($item) => Carbon::parse($item->Expiry_Date)->between($filterStart, $filterEnd))
                ->where('Status', 'Unpaid')
                ->first();
            if ($currentQuota) {
                $groupedData['QuotaSlot'][] = [
                    'ExpiryDate' => $this->getFormattedExpiryStatus($currentQuota->Due_Date),
                    'Amount' => number_format($currentQuota->Amt, 2),
                    'Emp_name' => $employee->Emp_name,
                    'Emp_id' => $employee->Emp_id,
                    'Department_name' => $employee->Department_name,
                    'Position_name' => $employee->Position_name,
                    'ProfilePic' => $employee->ProfilePic
                ];
            }
        }


        $consolidatedData = [];

        // Loop through grouped data (Visa, Insurance, etc.)
        foreach ($groupedData as $flag => $employees) {
            if (!empty($employees)) {
                $rowHtml = '  <h6 class="mb-2">' . $flag . '</h6>';

                foreach ($employees as $employee) {
                    $rowHtml .= '
                      
                        <div class="user-block d-flex align-items-center">
                            <div class="img-circle">
                                <img src="' . ($employee['ProfilePic'] ?? 'assets/images/user-2.svg') . '" alt="image">
                            </div>
                            <div class="w-100 d-flex align-items-center justify-content-between">
                                <div>
                                    <h6>' . $employee['Emp_name'] . ' <span>#' . $employee['Emp_id'] . '</span></h6>
                                    <p>' . $employee['Department_name'] . ' - ' . $employee['Position_name'] . '</p>
                                </div>
                                <div class="overdue-text">
                                    ' . $flag . ': MVR ' . $employee['Amount'] . '<br/>
                                    Expires: ' . $employee['ExpiryDate'] . '
                                </div>
                            </div>
                        </div>';
                }

                // Add this entire block as one DataTable row
                $consolidatedData[] = [
                    'profile_view' => '<div class="expiry-dates-overview-box">' . $rowHtml . '</div>'
                ];
            }
        }

        // Return to DataTables
        return datatables()->of($consolidatedData)
            ->rawColumns(['profile_view'])
            ->make(true);
        }
        $page_title= 'Visa Verify';             
    }
    public function PassportExpiry(Request $request)
    {
        // Your passport must be valid for the entire duration of your work visa or work permit.
        $flag=$request->flag;
        $file = $request->file('file');
        
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
            $expiryDateRaw = $AI_Data['extracted_fields']['Date of Expiry'] ?? "Not Exit";
            $issue_date = $AI_Data['extracted_fields']['Date of Issue'] ?? "Not Exit";
            if($expiryDateRaw !="Unavailable") 
            {
                $expiryDateRaw = Carbon::createFromFormat('d/m/Y', $expiryDateRaw)->format('Y-m-d');
                if ($expiryDateRaw) 
                {
                    try {
                        $passportno =  $AI_Data['extracted_fields']['passport no.'] ?? "Not Exit";
                        $expiryDate = Carbon::parse($expiryDateRaw)->endOfDay(); 
                        $today = Carbon::now();
                        $minValidDate = $today->copy()->addMonths(6);

                        if ($expiryDate->lt($minValidDate)) {
                            $status = "NOT VALID";  // Either expired or less than 6 months validity
                        } else {
                            $status = "VALID";
                        }

                    } 
                    catch (Exception $e) 
                    {
                        $passportno = "Not Exit";
                        $status = "NOT VALID"; // If parsing fails, treat as invalid
                    }
                } 
                else
                {
                    $passportno = "Not Exit";
                    $status = "NOT VALID"; // No expiry date means invalid
                }

                return response()->json([
                    'status' => true,
                    'message' => 'Passport Expiry Date',
                    'expiryDate' => $expiryDateRaw,
                    'issue_date'=> $issue_date,
                    'status' => $status,
                    'passportno'=>$passportno
                ]);
            }
            else
            {
                return response()->json(['status' => false, 'message' => 'Passport Expiry Date not found']);    
            }
       

    }

    public function CheckCv(Request $request)
    {   
        $flag = $request->flag;
        $file = $request->file('file');
        if($file)
        {
            $url = env('AI_URL').'extract_education_exp_details?doc_type=cv'; 
            $curl = curl_init();
            $postFields = [
                'file' => new \CURLFile($file->getRealPath(), $file->getMimeType(), $file->getClientOriginalName()),
                'doc_type' => 'cv',
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
                return response()->json(['status' => true,'data'=>'', 'message' =>  $err]);
            } 
            $AI_Data = json_decode($response, true); 
          
            if(array_key_exists("extracted_fields",$AI_Data))
            {
             return response()->json([
                    'status' => true,
                    'message' => 'fetch data',
                    'data'=>$AI_Data['extracted_fields']
                ]);
            }
            else
            {
                return response()->json(['status' => true, 'data'=>'','message' => 'Details Not Found']);    
            }
        }
        else
        {
            return response()->json(['status' => true,'data'=>'', 'message' => 'File not found']);
        }
    }

    public function Education(Request $request)
    {   
        $flag=$request->flag;
        $file = $request->file('file');
        if($file)
        {
            $url = env('AI_URL').'extract_education_exp_details?doc_type=education';
            $curl = curl_init();
            $postFields = [
                'file' => new \CURLFile($file->getRealPath(), $file->getMimeType(), $file->getClientOriginalName()),
                'doc_type' => 'education',
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
                return response()->json(['status' => true,'data'=>'', 'message' =>  $err]);
            } 
            
            $AI_Data = json_decode($response, true); 

            if(array_key_exists("extracted_fields",$AI_Data))
            {
             return response()->json([
                    'status' => true,
                    'message' => 'fatch data',
                    'file' => $file,
                    'data'=>$AI_Data['extracted_fields']
                ]);
            }
            else
            {
                return response()->json(['status' => true, 'data'=>'','message' => 'Details Not Found']);    
            }
        }
        else
        {
            return response()->json(['status' => true,'data'=>'', 'message' => 'File not found']);
        }
    }


    public function Experience(Request $request)
    {   
        $flag=$request->flag;
        $file = $request->file('file');

        if($file)
        {
            $url = env('AI_URL').'extract_education_exp_details?doc_type=experience'; 
            $curl = curl_init();
            $postFields = [
                'file' => new \CURLFile($file->getRealPath(), $file->getMimeType(), $file->getClientOriginalName()),
                'doc_type' => 'experience',
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
                return response()->json(['status' => true,'data'=>'', 'message' =>  $err]);
            } 
            $AI_Data = json_decode($response, true); 
            if(array_key_exists("extracted_fields",$AI_Data))
            {
             return response()->json([
                    'status' => true,
                    'message' => 'fatch data',
                    'file' => $file,
                    'data'=>$AI_Data['extracted_fields']
                ]);
            }
            else
            {
                return response()->json(['status' => true, 'data'=>'','message' => 'Details Not Found']);    
            }
        }
        else
        {
            return response()->json(['status' => true,'data'=>'', 'message' => 'File not found']);
        }
    }
}
