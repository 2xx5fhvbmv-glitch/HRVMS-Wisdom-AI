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
            if(!$this->resort) return;
            if($this->resort->is_master_admin == 0){
                if($this->resort->GetEmployee) {
                    $reporting_to = $this->resort->GetEmployee->id;
                    $this->underEmp_id = Common::getSubordinates($reporting_to);
                }
            }
        }


    /**
     * Resolve an insurance policy's [start, end] (both Y-m-d) given the FROM/TO
     * dates read from the document. Expatriate medical insurance is always a
     * 1-year term, so:
     *   - both present  -> use as-is
     *   - only TO/expiry -> start = end - 1 year
     *   - only FROM      -> end   = start + 1 year
     *   - neither        -> [null, null]
     * Uses safeAiDate so OCR sentinels ("Unavailable", "", odd formats) never throw.
     */
    private function resolveInsurancePeriod($fromRaw, $toRaw): array
    {
        $from = \App\Helpers\Common::safeAiDate($fromRaw);
        $to   = \App\Helpers\Common::safeAiDate($toRaw);

        if ($from && !$to) {
            $to = $from->copy()->addYearNoOverflow()->subDay();
        } elseif ($to && !$from) {
            $from = $to->copy()->subYearNoOverflow()->addDay();
        }

        return [
            $from ? $from->format('Y-m-d') : null,
            $to   ? $to->format('Y-m-d')   : null,
        ];
    }

    public function index()
    {
        $page_title = 'Renewals';
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
            
                // Unified — latest UNPAID insurance policy (falls back to latest).
                $insDue = \App\Helpers\Common::visaNextDue(EmployeeInsurance::class, $this->resort->resort_id, $emp_id, 'insurance_end_date', 'desc') ?: $EmployeeInsurance->insurance_end_date;
                $insurance_end_date = Carbon::parse($insDue);

                // diffInMonths is UNSIGNED — an already-expired policy was reading
                // "7 month(s) remaining" instead of expired. Branch on past/future.
                if ($insurance_end_date->isPast())
                {
                    $monthsAgo = $insurance_end_date->diffInMonths($start);
                    $daysAgo   = $insurance_end_date->diffInDays($start);
                    $EmployeeInsurance->InsuranceRenewalTime = $monthsAgo >= 1
                        ? "Expired $monthsAgo month(s) ago"
                        : "Expired $daysAgo day(s) ago";
                }
                else
                {
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
                }
                $medical_amt =  $ResortBudgetCost['MEDICAL INSURANCE - INTERNATIONAL'];

                $EmployeeInsurance->cost =   $EmployeeInsurance->Premium.' '.$EmployeeInsurance->Currency ??  $medical_amt ['amount'].' '.$medical_amt['unit'];
                $EmployeeInsurance->employee_id =base64_encode($EmployeeInsurance->employee_id);
                $EmployeeInsurance->insurance_end_date = $insurance_end_date->format('d M Y');

            }
        
            $WorkPermitMedicalRenewal = WorkPermitMedicalRenewal::where('employee_id',$emp_id)->where('resort_id',$this->resort->resort_id)->first(['employee_id','Reference_Number','Amt','Currency','Medical_Center_name','start_date','end_date','medical_file']);
            if($WorkPermitMedicalRenewal) 
            {
                $work_permit_amt =  $ResortBudgetCost['WORK VISA MEDICAL TEST FEE'];

                // Unified — latest UNPAID medical record (falls back to latest).
                $medDue = \App\Helpers\Common::visaNextDue(WorkPermitMedicalRenewal::class, $this->resort->resort_id, $emp_id, 'end_date', 'desc') ?: $WorkPermitMedicalRenewal->end_date;
                $medical_end_date = Carbon::parse($medDue);
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
                $WorkPermitMedicalRenewal->workpermitcost =  Common::formatMvr($WorkPermitMedicalRenewal->Amt);
                $WorkPermitMedicalRenewal->employee_id =base64_encode($WorkPermitMedicalRenewal->employee_id);
                $WorkPermitMedicalRenewal->medical_end_date = $medical_end_date->format('d M Y');
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
                // "Last Slot month" = the LAST month of the current schedule (latest
                // Due_Date), so the card matches the schedule's final row — not the
                // next-due date used on the Payment Request / Xpat pages.
                $slotDue = QuotaSlotRenewal::where('resort_id', $this->resort->resort_id)->where('employee_id', $emp_id)
                    ->whereNotNull('Due_Date')->max('Due_Date') ?: $QuotaSlotRenewal->Due_Date;
                $QuotaSlotRenewal_end_date = Carbon::parse($slotDue);

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
                $QuotaSlotRenewal->QuotaSlotRenewal_end_date = Carbon::parse($QuotaSlotRenewal_end_date)->format('d M Y');
                $QuotaSlotRenewal->employee_id =base64_encode($QuotaSlotRenewal->employee_id);
                // "New slot" = the current 12-month cycle's START (its last month − 11),
                // so it matches the schedule. Was showing today's date because it read
                // Payment_Date (null for an unpaid installment cycle).
                $QuotaSlotRenewal->NewSlot = $QuotaSlotRenewal_end_date->copy()->subMonthsNoOverflow(11)->format('d M Y') .' Until 12 Months';

            }
            $WorkPermitRenewal = WorkPermit::where('employee_id', $emp_id)
                ->where('resort_id', $this->resort->resort_id)
                ->orderByDesc('id')
                ->first(['id', 'employee_id', 'Month', 'Amt', 'Payment_Date', 'Due_Date', 'Currency', 'Reciept_file', 'PaymentType']);
     
            if($WorkPermitRenewal)
            {
                // "Last Work Permit month" = the LAST month of the current schedule
                // (latest Due_Date), so the card matches the schedule's final row.
                $wpDue = WorkPermit::where('resort_id', $this->resort->resort_id)->where('employee_id', $emp_id)
                    ->whereNotNull('Due_Date')->max('Due_Date') ?: $WorkPermitRenewal->Due_Date;
                $WorkPermitRenewal_end_date = Carbon::parse($wpDue);

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
                $WorkPermitRenewal->WorkPermitRenewal_end_date = Carbon::parse($WorkPermitRenewal_end_date)->format('d M Y');
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
                        'file'   => 'required|file|mimes:pdf,jpg,jpeg,png,gif,svg,webp,heic,heif|max:2048', // 2MB max
                    ],
                    [
                        'emp_id.required' => 'Employee ID is required.',
                        'flag.required'   => 'Document type is required.',
                        'file.required'   => 'File is required.',
                        'file.mimes'      => 'File must be a PDF, JPG, JPEG, PNG, GIF, SVG, WEBP, HEIC, or HEIF.',
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

        // child_id is only present when the upload is launched from the
        // payment-request workflow. On the standalone Renewal page it is absent,
        // so keep it null and skip the payment-request bookkeeping below.
        $child_id =  $request->filled('child_id') ? base64_decode($request->child_id) : null;
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
                    // Hostinger reverse proxy kills requests at ~60 s with
                    // its own HTML "Request Timeout" 500. 50 s here keeps
                    // failures inside PHP so the response is structured.
                    CURLOPT_TIMEOUT => 50,
                    CURLOPT_CONNECTTIMEOUT => 10,
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
                            // Insurance validity is exactly one year. Prefer the
                            // explicit FROM/TO from the document; if only one date is
                            // read, derive the other so the span is 1 year.
                            [$insStartYmd, $insEndYmd] = $this->resolveInsurancePeriod(
                                $AI_Data['extracted_fields']['Insurance Start Date']  ?? null,
                                $AI_Data['extracted_fields']['Insurance Expiry Date'] ?? null
                            );

                            // NOTE (bug fix): the previous code passed TWO arrays to
                            // ->update(), but Eloquent's update() takes only ONE — so
                            // the dates/company/policy were silently dropped and the
                            // card showed "Policy Number: N/A". This writes a single
                            // array with every field, including the company + policy.
                            EmployeeInsurance::where('resort_id', $this->resort->resort_id)
                                                       ->where('employee_id', $emp_id)
                                                       ->update([
                                                        'resort_id'               => $this->resort->resort_id,
                                                        'employee_id'             => $emp_id,
                                                        'insurance_company'       => $AI_Data['extracted_fields']['Insurance Company Name'] ?? $EmployeeInsurance->insurance_company,
                                                        'insurance_policy_number' => $AI_Data['extracted_fields']['Policy Number'] ?? $EmployeeInsurance->insurance_policy_number,
                                                        'Premium'                 => $Insurance_data['amount'] ?? 0.00,
                                                        'Currency'                => $Insurance_data['unit'] ?? null,
                                                        'insurance_file'          => $aws['Chil_file_id'] ?? null,
                                                        'insurance_start_date'    => $insStartYmd,
                                                        'insurance_end_date'      => $insEndYmd,
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


                            // Payment-request bookkeeping only applies when this upload came
                            // from the payment-request workflow (child_id present).
                            $PaymentRequestChild = $child_id ? PaymentRequestChild::where('employee_id', $emp_id)->where('id', $child_id)->first() : null;
                            if ($PaymentRequestChild) {
                                $PaymentRequestChild->OngoingSteps = $PaymentRequestChild->OngoingSteps + 1;
                                if($PaymentRequestChild->OverallSteps == $PaymentRequestChild->OngoingSteps )
                                {
                                    $PaymentRequestChild->ChildStatus = 'Complete';
                                    PaymentRequest::where('id', $PaymentRequestChild->Requested_Id)->update(['Status' => 'Approved']);
                                }
                                $PaymentRequestChild->InsuranceShow = 'No';
                                $PaymentRequestChild->InsuranceStep = 'Yes';
                                $PaymentRequestChild->save();
                            }
                            DB::Commit();

                            return response()->json(['success'=>true,'message'=>'Medical Insurance - International Renewal Completed','status'=>200]);
                        }
                        catch(\Throwable $e)
                        {
                            DB::rollBack();
                            return response()->json(['success'=>false,'message'=>'File Upload Failed','status'=>500]);
                        }
                }
                else
                {
                    // No existing insurance record — create the first one from the OCR data.
                    $aws = Common::AWSEmployeeFileUpload($this->resort->resort_id, $file, $employee->Emp_id);
                    if ($aws['status'] == false) {
                        return response()->json(['success'=>false,'message'=>$aws['msg'],'status'=>500]);
                    }
                    DB::beginTransaction();
                    try {
                        $Insurance_data = $ResortBudgetCost['MEDICAL INSURANCE - INTERNATIONAL'] ?? null;
                        // 1-year validity: prefer FROM/TO from the doc, else derive.
                        [$insStartYmd, $insEndYmd] = $this->resolveInsurancePeriod(
                            $AI_Data['extracted_fields']['Insurance Start Date']  ?? null,
                            $AI_Data['extracted_fields']['Insurance Expiry Date'] ?? null
                        );
                        EmployeeInsurance::create([
                            'resort_id'               => $this->resort->resort_id,
                            'employee_id'             => $emp_id,
                            'insurance_company'       => $AI_Data['extracted_fields']['Insurance Company Name'] ?? null,
                            'insurance_policy_number' => $AI_Data['extracted_fields']['Policy Number'] ?? null,
                            'Premium'                 => $Insurance_data['amount'] ?? 0.00,
                            'Currency'                => $Insurance_data['unit'] ?? null,
                            'insurance_start_date'    => $insStartYmd,
                            'insurance_end_date'      => $insEndYmd,
                            'insurance_file'          => $aws['Chil_file_id'] ?? null,
                        ]);

                        $TotalExpensessSinceJoing->Total_insurance_Payment += $Insurance_data['amount'] ?? 0.00;
                        $TotalExpensessSinceJoing->save();

                        VisaEmployeeExpiryData::where('resort_id', $this->resort->resort_id)
                            ->where('employee_id', $employee->id)
                            ->where('DocumentName', $doc_type)
                            ->delete();
                        VisaEmployeeExpiryData::create([
                            'resort_id'         => $this->resort->resort_id,
                            'employee_id'       => $employee->id,
                            'File_child_id'     => $aws['Chil_file_id'] ?? null,
                            'Ai_extracted_data' => $ai_encode ?? null,
                            'DocumentName'      => $doc_type ?? null,
                        ]);

                        DB::Commit();
                        return response()->json(['success'=>true,'message'=>'Medical Insurance - International Renewal Completed','status'=>200]);
                    } catch (\Throwable $e) {
                        DB::rollBack();
                        return response()->json(['success'=>false,'message'=>'File Upload Failed','status'=>500]);
                    }
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
                                // safeAiDate tolerates the OCR sentinels & format variants the
                                // AI service returns. When the date is unreadable both
                                // start/end_date fall back to today rather than throwing.
                                $medParsed = \App\Helpers\Common::safeAiDate($AI_Data['extracted_fields']['Last Medical Test Date(Mentioned in Certification of Doctor)'] ?? null);
                                $start_date = ($medParsed ?: \Carbon\Carbon::now())->format('Y-m-d');
                                $end_date   = ($medParsed ? $medParsed->copy() : \Carbon\Carbon::now())->addYear();
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
                                 
                            // Payment-request bookkeeping only when launched from that workflow.
                            $PaymentRequestChild = $child_id ? PaymentRequestChild::where('employee_id', $emp_id)->where('id', $child_id)->first() : null;
                            if ($PaymentRequestChild) {
                                $PaymentRequestChild->OngoingSteps = $PaymentRequestChild->OngoingSteps + 1;
                                if($PaymentRequestChild->OverallSteps == $PaymentRequestChild->OngoingSteps )
                                {
                                    $PaymentRequestChild->ChildStatus = 'Complete';
                                    PaymentRequest::where('id', $PaymentRequestChild->Requested_Id)->update(['Status' => 'Approved']);
                                }
                                $PaymentRequestChild->MedicalReportShow = 'No';
                                $PaymentRequestChild->MedicalReportStep = 'Yes';
                                $PaymentRequestChild->save();
                            }


                        DB::Commit();
                                return response()->json(['success'=>true,'message'=>'Work Permit Medical Test Fee Renewal Successfully','status'=>200]);
                            }
                            catch(\Throwable $e)
                            {
                                // If any error occurs, rollback the transaction
                                DB::rollBack();
                                return response()->json(['success'=>false,'message'=>'File Upload Failed','status'=>500]);
                            }
                    }
                    else
                    {
                        // No existing medical record — create the first one from the OCR data.
                        $aws = Common::AWSEmployeeFileUpload($this->resort->resort_id, $file, $employee->Emp_id);
                        if ($aws['status'] == false) {
                            return response()->json(['success'=>false,'message'=>$aws['msg'],'status'=>500]);
                        }
                        DB::beginTransaction();
                        try {
                            $medical_data = $ResortBudgetCost['WORK VISA MEDICAL TEST FEE'] ?? null;
                            $medParsed = \App\Helpers\Common::safeAiDate($AI_Data['extracted_fields']['Last Medical Test Date(Mentioned in Certification of Doctor)'] ?? null);
                            $start_date = ($medParsed ?: \Carbon\Carbon::now())->format('Y-m-d');
                            $end_date   = ($medParsed ? $medParsed->copy() : \Carbon\Carbon::now())->addYear();
                            WorkPermitMedicalRenewal::create([
                                'resort_id'           => $this->resort->resort_id,
                                'employee_id'         => $emp_id,
                                'Reference_Number'    => $AI_Data['extracted_fields']['Reference Number(Generally starts with MOH)'] ?? null,
                                'Medical_Center_name' => $AI_Data['extracted_fields']['Medical Center Name'] ?? null,
                                'Amt'                 => $medical_data['amount'] ?? 0.00,
                                'Currency'            => $medical_data['unit'] ?? null,
                                'start_date'          => $start_date,
                                'end_date'            => $end_date->format('Y-m-d'),
                                'medical_file'        => $aws['Chil_file_id'] ?? null,
                            ]);

                            $TotalExpensessSinceJoing->Total_Work_Permit_Medical_Payment += $medical_data['amount'] ?? 0.00;
                            $TotalExpensessSinceJoing->save();

                            DB::Commit();
                            return response()->json(['success'=>true,'message'=>'Work Permit Medical Test Fee Renewal Successfully','status'=>200]);
                        } catch (\Throwable $e) {
                            DB::rollBack();
                            return response()->json(['success'=>false,'message'=>'File Upload Failed','status'=>500]);
                        }
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
                        // Payment-request bookkeeping only when launched from that workflow.
                        $PaymentRequestChild = $child_id ? PaymentRequestChild::where('employee_id', $emp_id)->where('id', $child_id)->first() : null;
                        if ($PaymentRequestChild) {
                            $PaymentRequestChild->OngoingSteps = $PaymentRequestChild->OngoingSteps + 1;
                            if($PaymentRequestChild->OverallSteps == $PaymentRequestChild->OngoingSteps )
                            {
                                $PaymentRequestChild->ChildStatus = 'Complete';
                                PaymentRequest::where('id', $PaymentRequestChild->Requested_Id)->update(['Status' => 'Approved']);
                            }
                            $PaymentRequestChild->VisaShow = 'No';
                            $PaymentRequestChild->VisaStep = 'Yes';
                            $PaymentRequestChild->save();
                        }
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
            // Guard against duplicate schedules. Renewing again while a schedule
            // is still unpaid (or double-clicking, or renewing once as Lumpsum and
            // once as Installment) appended a second set of rows — double-charging
            // the employee. Block while any unpaid Work Permit row exists.
            if (WorkPermit::where('resort_id', $this->resort->resort_id)->where('employee_id', $emp_id)
                    ->whereRaw("LOWER(COALESCE(Status,'')) <> 'paid'")->exists()) {
                return response()->json(['success'=>false,'message'=>'A pending Work Permit schedule already exists for this employee. Settle (or remove) it before renewing again.','status'=>409]);
            }

            $WorkPermit_amt =  $ResortBudgetCost['WORK PERMIT FEE'] ?? null;

            if($payment_type =="Lumpsum")
            { 
                $next_year_due_date = $start_date->copy()->addYear();
                WorkPermit::create([    'resort_id'=>$this->resort->resort_id,
                                        'Due_Date'=> $next_year_due_date->format('Y-m-d'),
                                        'employee_id'=> $emp_id,
                                        'Month'=> 12,
                                        "Currency"=> $WorkPermit_amt['unit'] ?? 'MVR',
                                        "Amt"=> $WorkPermit_amt['amount'],
                                    ]);
 
                return response()->json(['success'=>true,'message'=>'WorkPermit Renewal  Successfully Using the Lumpsum Payment Type','status'=>200]);
            }
            elseif($payment_type =="Installment")
            {
                // Calendar-year aligned renewal. Continue from the month AFTER the
                // latest existing row, through DECEMBER of that year — NOT a rolling
                // 12-month window from today. This stops the schedule spilling into
                // the next year and stops re-creating already-paid months as new
                // "Pending" rows. Once the year's months are paid, the next renewal
                // covers Jan–Dec of the following year.
                $last = WorkPermit::where('resort_id', $this->resort->resort_id)
                    ->where('employee_id', $emp_id)->whereNotNull('Due_Date')
                    ->orderBy('Due_Date', 'desc')->first();

                if ($last) {
                    $anchor = Carbon::parse($last->Due_Date)->addMonthNoOverflow(); // first month to create
                    $dueDay = Carbon::parse($last->Due_Date)->day;                  // keep the same day-of-month
                } else {
                    $anchor = Carbon::today();
                    $dueDay = Carbon::today()->day;
                }

                $year    = $anchor->year;
                $created = 0;
                for ($m = $anchor->month; $m <= 12; $m++) {
                    // Defensive: never duplicate a month that already has a row.
                    $exists = WorkPermit::where('resort_id', $this->resort->resort_id)
                        ->where('employee_id', $emp_id)
                        ->whereYear('Due_Date', $year)->whereMonth('Due_Date', $m)->exists();
                    if ($exists) { continue; }

                    $monthStart = Carbon::create($year, $m, 1);
                    $day = min($dueDay, $monthStart->copy()->endOfMonth()->day);
                    $due = $monthStart->copy()->day($day);

                    WorkPermit::create([
                        'resort_id'   => $this->resort->resort_id,
                        'Due_Date'    => $due->format('Y-m-d'),
                        'employee_id' => $emp_id,
                        'Month'       => $due->format('m'),
                        'Currency'    => $WorkPermit_amt['unit'] ?? 'MVR',
                        'Amt'         => $WorkPermit_amt['amount'],
                    ]);
                    $created++;
                }

                return  response()->json(['success'=>true,'message'=>"Work Permit renewed for {$created} month(s) through December {$year}.",'status'=>200]);
            }
            else
            {
                return response()->json(['success'=>false,'message'=>'Please  Add  Xpact Page','status'=>500]);
            }
        } 
        if($flag =="QuotaSlot")
        {
            // Block renewal only while the current cycle still has UNPAID months —
            // settle those first. Once fully paid, HR may renew the next cycle even if
            // coverage already runs years ahead: paying cycles in advance is the user's
            // choice, and each new cycle simply continues after the last month
            // (May 2028 -> Jun 2028-May 2029 -> Jun 2029-... and so on).
            if (QuotaSlotRenewal::where('resort_id', $this->resort->resort_id)->where('employee_id', $emp_id)
                    ->whereRaw("LOWER(COALESCE(Status,'')) <> 'paid'")->exists()) {
                return response()->json(['success'=>false,'message'=>"This employee's current Quota Slot cycle still has unpaid months — settle them before renewing the next cycle.",'status'=>409]);
            }

            $qotaslotAMt =  $ResortBudgetCost['QUOTA SLOT DEPOSIT'] ?? 0.00;

            if (!in_array($payment_type, ['Lumpsum', 'Installment'], true)) {
                return response()->json(['success'=>false,'message'=>'Please select a payment type.','status'=>500]);
            }

            // Both Lumpsum and Installment create the SAME 12-month schedule — the
            // yearly Quota Slot deposit split across 12 months (first month 174, the
            // remaining 11 share the balance and the last absorbs any rounding so the
            // rows total the deposit exactly). The Lumpsum option used to create a
            // SINGLE row, so it never appeared as a 12-month schedule. payment_type is
            // recorded on each row; how it's settled (all at once vs monthly) is a
            // payment-time concern.
            $deposit  = (float) ($qotaslotAMt['amount'] ?? 0);
            $unit     = $qotaslotAMt['unit'] ?? 'MVR';
            $firstAmt = 174;
            $perMonth = $deposit > $firstAmt ? round(($deposit - $firstAmt) / 11, 2) : 0;

            // Continue from the month after the latest existing row so cycles don't
            // overlap (mirrors the Work Permit renewal).
            $last = QuotaSlotRenewal::where('resort_id', $this->resort->resort_id)
                ->where('employee_id', $emp_id)->whereNotNull('Due_Date')
                ->orderBy('Due_Date', 'desc')->first();
            if ($last) {
                $anchor = Carbon::parse($last->Due_Date)->addMonthNoOverflow();
                $dueDay = Carbon::parse($last->Due_Date)->day;
            } else {
                $anchor = Carbon::today();
                $dueDay = Carbon::today()->day;
            }

            // Lumpsum = one upfront payment, so every month is created already PAID
            // with today's payment date. Installment = pay monthly, so rows start
            // Pending (Unpaid).
            $isLumpsum    = ($payment_type === 'Lumpsum');
            $rowStatus    = $isLumpsum ? 'Paid' : 'Unpaid';
            $rowPaymentDt = $isLumpsum ? Carbon::today()->format('Y-m-d') : null;

            $allocated = 0;
            for ($i = 0; $i < 12; $i++) {
                $monthDate = $anchor->copy()->addMonths($i);
                $day = min($dueDay, $monthDate->copy()->endOfMonth()->day);
                $due = $monthDate->copy()->day($day);

                if ($i === 0)        { $amt = $firstAmt; }
                elseif ($i === 11)   { $amt = round($deposit - $allocated, 2); }
                else                 { $amt = $perMonth; }
                $allocated += $amt;

                QuotaSlotRenewal::create([
                    'resort_id'    => $this->resort->resort_id,
                    'Due_Date'     => $due->format('Y-m-d'),
                    'employee_id'  => $emp_id,
                    'Month'        => $due->format('m'),
                    'Currency'     => $unit,
                    'Amt'          => $amt,
                    'PaymentType'  => $payment_type,
                    'Status'       => $rowStatus,
                    'Payment_Date' => $rowPaymentDt,
                ]);
            }

            // Track the lifetime slot total (guard against a missing snapshot row).
            $TotalExpensessSinceJoing = TotalExpensessSinceJoing::where('resort_id', $this->resort->resort_id)->where('employees_id', $emp_id)->first();
            if ($TotalExpensessSinceJoing) {
                $TotalExpensessSinceJoing->Total_slot_Payment += $deposit;
                $TotalExpensessSinceJoing->save();
            }

            return response()->json(['success'=>true,'message'=>"Quota Slot renewed for 12 months ({$payment_type}).",'status'=>200]);
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
                ->whereNotIn('id', function ($q) {
                    // Employees already verified & submitted drop off this list —
                    // their data now lives on the employee-details page.
                    $q->select('employee_id')->from('visa_verification_statuses')
                      ->where('resort_id', $this->resort->resort_id)
                      ->where('status', 'verified');
                })
                ->get()
                ->map(function ($employee) use (  $filterStart, $filterEnd) {
                    $employee->Emp_name = $employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name;
                    $employee->Emp_id = $employee->Emp_id;
                    $employee->Department_name = $employee->department->name ?? 'N/A';
                    $employee->Position_name = $employee->position->position_title ?? 'N/A';
                    $employee->ProfilePic = Common::getResortUserPicture($employee->resortAdmin->id);
                    $employee->VisaExpiryExpiryDate = $employee->InsuranceExpiryDate = $employee->WorkPermitExpiryDate = $employee->WorkPermitMedicalPermitExpiryDate = $employee->QuotaSlotAmtForThisMonth = 'N/A';
                    // Raw values + record ids for the per-column Edit (null = no
                    // record this month, so no edit icon is shown for that box).
                    $employee->VisaRecordId = $employee->VisaAmtRaw = $employee->VisaDateRaw = $employee->VisaStatusRaw = null;
                    $employee->InsuranceRecordId = $employee->InsuranceAmtRaw = $employee->InsuranceDateRaw = null;
                    $employee->WorkPermitRecordId = $employee->WorkPermitAmtRaw = $employee->WorkPermitDateRaw = $employee->WorkPermitStatusRaw = null;
                    $employee->SlotRecordId = $employee->SlotAmtRaw = $employee->SlotDateRaw = $employee->SlotStatusRaw = null;

                    $employeeData = [];
                    $hasAnyFlagData = false;

                    
                        // Latest visa by expiry date (not the bare hasOne default) —
                        // same "always show regardless of month" fix already applied
                        // to Insurance below. Gating this on the current-month window
                        // hid already-EXPIRED visas (like an end_date a month or more
                        // in the past) from a page whose job is to catch exactly that.
                        $visa = $employee->VisaRenewal()->where('employee_id', $employee->id)->where('resort_id', $this->resort->resort_id)->orderBy('end_date', 'desc')->orderBy('id', 'desc')->first();

                        if ($visa) {
                            $employee->VisaExpiryDate = $this->getFormattedExpiryStatus($visa->end_date);
                            $employee->VisaExpiryExpiryAmt = $visa->Amt;
                            $employee->VisaRecordId  = $visa->id;
                            $employee->VisaAmtRaw    = $visa->Amt;
                            $employee->VisaDateRaw   = Carbon::parse($visa->end_date)->format('Y-m-d');
                            $employee->VisaStatusRaw = $visa->Status ?? null;
                            if (Carbon::parse($visa->end_date)->between($filterStart, $filterEnd)) {
                                $hasAnyFlagData = true;
                            }
                        }
          

           
                        // Latest insurance by expiry date (not id — a higher id can hold an
                        // older end_date). Insurance always displays its latest record so the
                        // card + edit pencil show regardless of month; the current-month gate
                        // below only governs whether insurance alone pulls the row into the list.
                        $insurance = $employee->EmployeeInsurance()->where('employee_id', $employee->id)->where('resort_id', $this->resort->resort_id)->orderBy('insurance_end_date', 'desc')->orderBy('id', 'desc')->first();
                        if ($insurance) {
                            $employee->InsuranceExpiryDate = $this->getFormattedExpiryStatus($insurance->insurance_end_date);
                            $employee->Premium = $insurance->Premium;
                            $employee->InsuranceRecordId = $insurance->id;
                            $employee->InsuranceAmtRaw   = $insurance->Premium;
                            $employee->InsuranceDateRaw  = Carbon::parse($insurance->insurance_end_date)->format('Y-m-d');
                            if (Carbon::parse($insurance->insurance_end_date)->between($filterStart, $filterEnd)) {
                                $hasAnyFlagData = true;
                            }
                        }
                  
                        // Work permit EXPIRY shown here is the document's "Expiry On" from the
                        // OCR 'Other' blob (the same source the employee-details page uses) — NOT
                        // the monthly fee-schedule due date, which would otherwise show the last
                        // scheduled fee month instead of the permit's real expiry. The fee row is
                        // only used to attach the inline Edit (or Add +) control and its amount.
                        $ocrWp = \App\Models\VisaEmployeeExpiryData::where('employee_id', $employee->id)
                            ->where('resort_id', $this->resort->resort_id)
                            ->where('DocumentName', 'Other')
                            ->orderBy('id', 'desc')
                            ->first();
                        $wpOcrExpiry = null;
                        if ($ocrWp) {
                            $fields = json_decode($ocrWp->Ai_extracted_data, true)['extracted_fields'] ?? [];
                            $wpOcrExpiry = \App\Helpers\Common::safeAiDate($fields['Work Permit Expiry Date (Expiry On)'] ?? null);
                        }

                        $currentWP = $employee->WorkPermit->where('Status','Unpaid')->sortByDesc('Due_Date')->first();
                        if ($currentWP)
                        {
                            $employee->WorkPermitAmt       = number_format($currentWP->Amt,2);
                            $employee->WorkPermitRecordId  = $currentWP->id;
                            $employee->WorkPermitAmtRaw    = $currentWP->Amt;
                            $employee->WorkPermitDateRaw   = Carbon::parse($currentWP->Due_Date)->format('Y-m-d');
                            $employee->WorkPermitStatusRaw = $currentWP->Status ?? null;
                            if (Carbon::parse($currentWP->Due_Date)->between($filterStart, $filterEnd)) {
                                $hasAnyFlagData = true;
                            }
                        }

                        // Displayed expiry: OCR "Expiry On" first, fee due date only as fallback.
                        if ($wpOcrExpiry) {
                            $employee->WorkPermitExpiryDate = $this->getFormattedExpiryStatus($wpOcrExpiry->format('Y-m-d'));
                            if (!$employee->WorkPermitDateRaw) {
                                $employee->WorkPermitDateRaw = $wpOcrExpiry->format('Y-m-d');
                            }
                        } elseif ($currentWP) {
                            $employee->WorkPermitExpiryDate = $this->getFormattedExpiryStatus($currentWP->Due_Date);
                        }

                        $med = $employee->WorkPermitMedicalRenewal;
                        if ($med && Carbon::parse($med->end_date)->between($filterStart, $filterEnd)) 
                        {
                            $employee->WorkPermitMedicalPermitExpiryDate = $this->getFormattedExpiryStatus($med->end_date);
                            $employee->WorkPermitMedicalPermitAmt        =  number_format($med->Amt,2);
                            $hasAnyFlagData = true;
                           

                   
                          
                        }
                  
                      
                        // QuotaSlotRenewal rows never populate Expiry_Date (only
                        // Due_Date) — Carbon::parse(null) silently returns "now",
                        // so filtering on Expiry_Date->between(thisMonth) matched
                        // every row and ->first() just returned whichever row
                        // was inserted last (the earliest, already-paid month).
                        // Mirror the Work Permit pattern above: the relevant
                        // "current" row is the latest-due UNPAID one.
                        $currentQuota = $employee->QuotaSlotRenewal
                            ->where('Status', 'Unpaid')
                            ->sortByDesc('Due_Date')
                            ->first();
                        $encodedId = base64_encode($employee->id);
                        if ($currentQuota)
                        {
                            $employee->QuotaSlotAmtForThisMonth = $this->getFormattedExpiryStatus($currentQuota->Due_Date);
                            $employee->QuotaSlotAmtForThisMonthAmt =$currentQuota->Amt;
                            $employee->SlotRecordId  = $currentQuota->id;
                            $employee->SlotAmtRaw    = $currentQuota->Amt;
                            $employee->SlotDateRaw   = Carbon::parse($currentQuota->Due_Date)->format('Y-m-d');
                            $employee->SlotStatusRaw = $currentQuota->Status ?? null;
                            $hasAnyFlagData = true;

                           

                           
                        }
                    

                    $employee->extra= json_encode($employeeData);
                  

                    return $hasAnyFlagData ? $employee : null;
                })->filter();

            

                return datatables()->of($Employee)
                        ->addColumn('profile_view', function ($row) {
                            $expiryBoxes = '';

                            // Renders a small Edit pencil for a column, carrying the
                            // record id + current values so the modal can pre-fill.
                            // Empty when there's no record for that column this month.
                            $editIcon = function ($type, $id, $amount, $date, $status, $empId = null) {
                                // No record yet: only Work Permit can be added manually from here
                                // (HR enters the data), shown as a + icon carrying the employee id.
                                // Other columns stay edit-only. A pre-filled date (e.g. OCR expiry)
                                // is carried into the Add modal as a suggestion.
                                if (!$id) {
                                    if ($type !== 'work_permit' || !$empId) return '';
                                    return ' <a href="javascript:void(0)" class="EditExpiry" title="Add Work Permit"'
                                        . ' data-type="work_permit" data-id="" data-emp="' . $empId . '"'
                                        . ' data-amount="" data-date="' . htmlspecialchars((string) $date, ENT_QUOTES) . '" data-status="">'
                                        . '<i class="fa-regular fa-circle-plus"></i></a>';
                                }
                                // Records are stored in MVR and the edit modal is MVR-only, so
                                // pre-fill the raw stored amount (no conversion).
                                $displayAmount = ($amount === null || $amount === '')
                                    ? ''
                                    : $amount;
                                return ' <a href="javascript:void(0)" class="EditExpiry" title="Edit"'
                                    . ' data-type="' . $type . '"'
                                    . ' data-id="' . $id . '"'
                                    . ' data-emp="' . ($empId ?: '') . '"'
                                    . ' data-amount="' . htmlspecialchars((string) $displayAmount, ENT_QUOTES) . '"'
                                    . ' data-date="' . htmlspecialchars((string) $date, ENT_QUOTES) . '"'
                                    . ' data-status="' . htmlspecialchars((string) $status, ENT_QUOTES) . '">'
                                    . '<i class="fa-regular fa-pen-to-square"></i></a>';
                            };

                                $expiryBoxes .= '<div>
                                    <label>Work Permit: '.Common::formatMvr($row->WorkPermitAmt).$editIcon('work_permit', $row->WorkPermitRecordId, $row->WorkPermitAmtRaw, $row->WorkPermitDateRaw, $row->WorkPermitStatusRaw, $row->id).'</label>
                                    <p>Expires: ' . ($row->WorkPermitExpiryDate ?? '-') . '</p>
                                </div>';

                                $expiryBoxes .= '<div>
                                    <label>Slot Payment: '.Common::formatMvr($row->QuotaSlotAmtForThisMonthAmt).$editIcon('slot', $row->SlotRecordId, $row->SlotAmtRaw, $row->SlotDateRaw, $row->SlotStatusRaw).'</label>
                                    <p>Expires: ' . ($row->QuotaSlotAmtForThisMonth ?? '-') . '</p>
                                </div>';
                               $expiryBoxes .= '<div>
                                    <label>Visa: ' . ($row->VisaExpiryExpiryAmt !== null ? Common::formatMvr($row->VisaExpiryExpiryAmt) : '-') . $editIcon('visa', $row->VisaRecordId, $row->VisaAmtRaw, $row->VisaDateRaw, $row->VisaStatusRaw) . '</label>
                                    <p>Expires: ' . ($row->VisaExpiryDate ?? '-') . '</p>
                                </div>';
                               $expiryBoxes .= '<div>
                                    <label>Insurance: ' . ($row->Premium !== null ? Common::formatMvr($row->Premium) : '-') . $editIcon('insurance', $row->InsuranceRecordId, $row->InsuranceAmtRaw, $row->InsuranceDateRaw, '') . '</label>
                                    <p>Expires: ' . ($row->InsuranceExpiryDate ?? '-') . '</p>
                                </div>';
                           
                            return '<div class="exp-Date-userbox">
                                <div class="row align-items-lg-center">
                                    <div class="col-auto">
                                        <div class="form-check no-label">
                                            <input class="form-check-input VerifyCheck" type="checkbox" value="' . $row->id . '">
                                        </div>
                                    </div>
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

    /**
     * Verify-details "Submit": for the selected employees, push their verified
     * renewal values into the OCR 'Other' blob the employee-details page reads
     * (Visa / Insurance / Work-Permit expiry), then mark them verified so they
     * drop off the verify list. Resort-scoped. Quota Slot already reflects on the
     * details page via its own record, so it needs no blob write.
     */
    public function SubmitVerifiedDetails(Request $request)
    {
        $request->validate([
            'employee_ids'   => 'required|array|min:1',
            'employee_ids.*' => 'integer',
        ]);

        $rid  = $this->resort->resort_id;
        $uid  = optional(auth()->guard('resort-admin')->user())->id;
        $done = 0;

        foreach (array_unique($request->employee_ids) as $empId) {
            $employee = \App\Models\Employee::where('id', $empId)->where('resort_id', $rid)->first();
            if (!$employee) {
                continue;
            }

            \DB::transaction(function () use ($employee, $rid, $uid, &$done) {
                // Latest verified records (same selection rules as the verify list).
                $visa = \App\Models\VisaRenewal::where('employee_id', $employee->id)->where('resort_id', $rid)
                    ->orderBy('id', 'desc')->first();
                $insurance = \App\Models\EmployeeInsurance::where('employee_id', $employee->id)->where('resort_id', $rid)
                    ->orderBy('insurance_end_date', 'desc')->orderBy('id', 'desc')->first();
                $workPermit = \App\Models\WorkPermit::where('employee_id', $employee->id)->where('resort_id', $rid)
                    ->where('Status', 'Unpaid')->orderBy('Due_Date', 'desc')->first();

                // Merge verified expiries into the OCR 'Other' blob, preserving every
                // other extracted field (passport, WP number, quota slot number…).
                $ocr = \App\Models\VisaEmployeeExpiryData::where('employee_id', $employee->id)->where('resort_id', $rid)
                    ->where('DocumentName', 'Other')->orderBy('id', 'desc')->first();
                $payload = $ocr ? (json_decode($ocr->Ai_extracted_data, true) ?: []) : [];
                $fields  = $payload['extracted_fields'] ?? [];

                if ($visa && $visa->end_date) {
                    $fields['Visa Expiry Date'] = Carbon::parse($visa->end_date)->format('Y-m-d');
                }
                if ($insurance && $insurance->insurance_end_date) {
                    $fields['Insurance Expiry Date'] = Carbon::parse($insurance->insurance_end_date)->format('Y-m-d');
                }
                if ($workPermit && $workPermit->Due_Date) {
                    $fields['Work Permit Expiry Date (Expiry On)'] = Carbon::parse($workPermit->Due_Date)->format('Y-m-d');
                }
                $payload['extracted_fields'] = $fields;

                if ($ocr) {
                    $ocr->Ai_extracted_data = json_encode($payload);
                    $ocr->save();
                } else {
                    \App\Models\VisaEmployeeExpiryData::create([
                        'resort_id'         => $rid,
                        'employee_id'       => $employee->id,
                        'File_child_id'     => null,
                        'Ai_extracted_data' => json_encode($payload),
                        'DocumentName'      => 'Other',
                    ]);
                }

                // Mark verified → employee drops off the verify list.
                \App\Models\VisaVerificationStatus::updateOrCreate(
                    ['resort_id' => $rid, 'employee_id' => $employee->id],
                    ['status' => 'verified', 'verified_at' => now(), 'verified_by' => $uid]
                );

                $done++;
            });
        }

        if ($done === 0) {
            return response()->json(['success' => false, 'errors' => ['message' => 'No valid employees to submit.']], 422);
        }

        return response()->json([
            'success' => true,
            'msg'     => $done . ' employee(s) submitted — their data now shows on the employee details page.',
            'count'   => $done,
        ]);
    }

    /**
     * Inline edit for the verify-details columns. Updates the amount, expiry/due
     * date and (where applicable) status of a single Visa / Work Permit / Slot /
     * Insurance record — scoped to the current resort so one resort can't edit
     * another's data.
     */
    public function UpdateExpiryRecord(Request $request)
    {
        $request->validate([
            'type'        => 'required|in:visa,work_permit,slot,insurance',
            'id'          => 'nullable|integer',
            'emp_id'      => 'nullable|integer',
            'amount'      => 'nullable|numeric',
            'expiry_date' => 'nullable|date',
            'status'      => 'nullable|string',
        ]);

        // type => [model, amountColumn, dateColumn, statusColumn|null, allowedStatuses]
        $map = [
            'visa'        => [\App\Models\VisaRenewal::class,      'Amt',     'end_date',           'Status', ['Pending', 'Paid']],
            'work_permit' => [\App\Models\WorkPermit::class,       'Amt',     'Due_Date',           'Status', ['Paid', 'Unpaid']],
            'slot'        => [\App\Models\QuotaSlotRenewal::class,  'Amt',     'Due_Date',           'Status', ['Paid', 'Unpaid']],
            'insurance'   => [\App\Models\EmployeeInsurance::class, 'Premium', 'insurance_end_date',  null,     []],
        ];
        [$modelClass, $amountCol, $dateCol, $statusCol, $allowedStatuses] = $map[$request->type];

        if ($request->filled('id')) {
            $record = $modelClass::where('id', $request->id)
                ->where('resort_id', $this->resort->resort_id)
                ->first();
            if (!$record) {
                return response()->json(['success' => false, 'errors' => ['message' => 'Record not found.']], 404);
            }
        } else {
            // Manual Add — HR creates a record where none existed. Only Work Permit
            // supports this from the verify screen; a due date is required.
            if ($request->type !== 'work_permit') {
                return response()->json(['success' => false, 'errors' => ['message' => 'Adding a new record is not supported for this item.']], 422);
            }
            if (!$request->filled('expiry_date')) {
                return response()->json(['success' => false, 'errors' => ['message' => 'Expiry / Due Date is required.']], 422);
            }
            $employee = \App\Models\Employee::where('id', $request->emp_id)
                ->where('resort_id', $this->resort->resort_id)
                ->first();
            if (!$employee) {
                return response()->json(['success' => false, 'errors' => ['message' => 'Employee not found.']], 404);
            }
            $record = new $modelClass();
            $record->resort_id   = $this->resort->resort_id;
            $record->employee_id = $employee->id;
            $record->Currency    = 'MVR';
            $record->Status      = 'Unpaid';
        }

        if ($request->filled('amount')) {
            // The visa module is MVR end-to-end — the edit modal accepts MVR and
            // these records are stored in MVR, so save the raw value as typed.
            $record->{$amountCol} = (float) $request->amount;
        }
        if ($request->filled('expiry_date')) {
            $record->{$dateCol} = Carbon::parse($request->expiry_date)->format('Y-m-d');
        }
        if ($statusCol && $request->filled('status') && in_array($request->status, $allowedStatuses, true)) {
            $record->{$statusCol} = $request->status;
        }
        $wasAdd = !$request->filled('id');
        $record->save();

        return response()->json(['success' => true, 'msg' => $wasAdd ? 'Added successfully.' : 'Updated successfully.']);
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
            ->whereRaw('LOWER(TRIM(nationality)) != ?', ['maldivian'])
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

            // Insurance — reuse the eager-loaded hasOne relation rather than firing
            // a fresh query per employee.
            $insurance = $employee->EmployeeInsurance;
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

            // Quota Slot — filter and display by Due_Date so the filter window
            // matches the value shown in the row (was filtering by Expiry_Date).
            $currentQuota = $employee->QuotaSlotRenewal
                ->where('Status', 'Unpaid')
                ->filter(fn($item) => Carbon::parse($item->Due_Date)->between($filterStart, $filterEnd))
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
                                    ' . $flag . ': ' . Common::formatMvr($employee['Amount']) . '<br/>
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
                // Hostinger proxy kills the request at ~60 s — explicit
                // 50 s timeout means PHP returns a clean JSON error first.
                CURLOPT_TIMEOUT => 50,
                CURLOPT_CONNECTTIMEOUT => 10,
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

            // Treat every sentinel + null + empty as "no date extracted"
            // so we don't feed Carbon a non-date string. The previous
            // guard only matched "Unavailable", which let the default
            // "Not Exit" fall through and throw
            //   "A two digit day could not be found"
            // on every OCR result that missed the expiry field
            // (live 500 on POST /resort/visa/passport-expiry).
            $missingDateSentinels = ['Not Exit', 'Unavailable', 'N/A', 'NA', 'null', null, ''];
            $hasUsableExpiry = $expiryDateRaw !== null
                && !in_array(trim((string) $expiryDateRaw), $missingDateSentinels, true);

            if ($hasUsableExpiry)
            {
                // Wrap the format parse — even with the sentinel guard,
                // the OCR may return a date in a different layout
                // (e.g. Y-m-d, d-m-Y). createFromFormat throws on any
                // mismatch, so try the most common ones in order and
                // fall back to "could not parse" if none stick.
                $parsedExpiry = null;
                foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'd M Y', 'd-M-Y'] as $fmt) {
                    try {
                        $candidate = Carbon::createFromFormat($fmt, (string) $expiryDateRaw);
                        if ($candidate && $candidate->format($fmt) === (string) $expiryDateRaw) {
                            $parsedExpiry = $candidate;
                            break;
                        }
                    } catch (\Throwable $e) { /* try next */ }
                }

                if ($parsedExpiry)
                {
                    try {
                        $expiryDateRaw = $parsedExpiry->format('Y-m-d');
                        $passportno =  $AI_Data['extracted_fields']['passport no.'] ?? "Not Exit";
                        $expiryDate = $parsedExpiry->copy()->endOfDay();
                        $today = Carbon::now();
                        $minValidDate = $today->copy()->addMonths(6);

                        if ($expiryDate->lt($minValidDate)) {
                            $status = "NOT VALID";  // Either expired or less than 6 months validity
                        } else {
                            $status = "VALID";
                        }

                    }
                    catch (\Throwable $e)
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
                // Hostinger proxy kills the request at ~60 s — explicit
                // 50 s timeout means PHP returns a clean JSON error first.
                CURLOPT_TIMEOUT => 50,
                CURLOPT_CONNECTTIMEOUT => 10,
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
                // Hostinger proxy kills the request at ~60 s — explicit
                // 50 s timeout means PHP returns a clean JSON error first.
                CURLOPT_TIMEOUT => 50,
                CURLOPT_CONNECTTIMEOUT => 10,
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
                // Hostinger proxy kills the request at ~60 s — explicit
                // 50 s timeout means PHP returns a clean JSON error first.
                CURLOPT_TIMEOUT => 50,
                CURLOPT_CONNECTTIMEOUT => 10,
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
