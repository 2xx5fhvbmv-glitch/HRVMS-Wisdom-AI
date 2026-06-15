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
            if($this->resort->GetEmployee) {
                $reporting_to = $this->resort->GetEmployee->id;
                $this->underEmp_id = Common::getSubordinates($reporting_to);
            }
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

        // Validate uploads BEFORE hitting the AI extraction service. An empty
        // (0-byte) or non-PDF upload — e.g. a file whose body never finished
        // uploading — previously got shipped to the OCR service, which stalled
        // until the 50 s timeout and surfaced as an opaque "service did not
        // respond" error. Fail fast with a clear message instead.
        $validatePdf = function ($file, string $label) {
            if (!$file) return null; // absent is handled by the existing branches
            if (!$file->isValid() || !$file->getSize()) {
                return "The {$label} is empty or could not be read. Please re-select the file and try again.";
            }
            $ext  = strtolower((string) $file->getClientOriginalExtension());
            $mime = (string) $file->getMimeType();
            if ($ext !== 'pdf' && stripos($mime, 'pdf') === false) {
                return "The {$label} must be a PDF document.";
            }
            return null;
        };

        if ($validationError = $validatePdf($Xpatfile, 'Xpat document')) {
            return response()->json(['success' => false, 'errors' => ['message' => $validationError]], 422);
        }
        if ($validationError = $validatePdf($request->file('QuotaSlotFees'), 'Quota slot fees document')) {
            return response()->json(['success' => false, 'errors' => ['message' => $validationError]], 422);
        }

        /*
            Visa renewal cost
            Work permit medical renewal is a  Work Visa Medical test fee
            Insurance renewal is a medical insurance - international
        */

        $ResortBudgetCost = Common::VisaRenewalCost($this->resort->resort_id);
        $resortId = $this->resort->resort_id;

        // The heavy OCR/LLM extraction is wrapped in this closure so it can run
        // AFTER the HTTP response is sent (see app()->terminating below). The
        // browser receives an instant job id and polls for the result, so it
        // never waits on the slow extraction and can't hit the 50s proxy
        // timeout. Closures auto-bind $this, so $this->resort still works here.
        $work = function () use ($Xpatfile, $request, $ResortBudgetCost) : \Illuminate\Http\JsonResponse {
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
                // Explicit timeouts. Without these, cURL waits forever
                // and Hostinger's reverse proxy kills the request at
                // ~60 s, returning its own "Request Timeout" HTML page
                // that the user can't parse. 50 s is well under that
                // limit so we always fail inside PHP and can return a
                // clean JSON error. CONNECTTIMEOUT covers the case
                // where the AI host is unreachable.
                CURLOPT_TIMEOUT => 50,
                CURLOPT_CONNECTTIMEOUT => 10,
            ]);
            $response = curl_exec($curl);
            $err = curl_error($curl);
            $errno = curl_errno($curl);
            curl_close($curl);
            if ($err)
            {
                // Distinguish timeout from generic curl errors so the
                // user sees an actionable message ("try again" / "AI
                // service unreachable") instead of raw libcurl text.
                if ($errno === CURLE_OPERATION_TIMEDOUT) {
                    return response()->json([
                        'success' => false,
                        'errors'  => ['message' => 'The AI extraction service did not respond within 50 seconds. The document may be very large or the service may be busy — please try again in a moment.'],
                    ], 504);
                }
                if (in_array($errno, [CURLE_COULDNT_CONNECT, CURLE_COULDNT_RESOLVE_HOST], true)) {
                    return response()->json([
                        'success' => false,
                        'errors'  => ['message' => 'Could not reach the AI extraction service. Please contact support if this persists.'],
                    ], 502);
                }
                return response()->json([
                    'success' => false,
                    'errors' => ['message' => $err]
                ], 422);
            }
            $response1= $response;
            $AI_Data = json_decode($response, true);

            // Null-safe access — AI service can return invalid JSON
            // (timeout, partial response) which leaves $AI_Data as null,
            // or it can return the envelope without an extracted_fields
            // sub-array, or with the passport key missing. The previous
            // code accessed $AI_Data['extracted_fields'][...] directly
            // and threw "Trying to access array offset on value of type
            // null" (live 500 on POST /resort/visa/store).
            $passportRaw = $AI_Data['extracted_fields']["Employee's Passport Number"] ?? null;
            // Treat AI placeholders ("Unavailable", "N/A", ...) as "not read" so
            // the user gets the clear re-upload message instead of a failed
            // lookup against a junk passport value.
            $passportPlaceholder = in_array(strtolower(trim((string) $passportRaw)), ['unavailable', 'n/a', 'na', 'none', 'null', 'not found', '-'], true);
            if (empty($passportRaw) || $passportPlaceholder) {
                return response()->json([
                    'success' => false,
                    'errors' => ['message' => 'Could not read passport number from the document. Please upload a clearer scan.'],
                ], 422);
            }
            $passport_number = preg_replace('/[^A-Za-z0-9]/', '', (string) $passportRaw);

            // Look up the employee by the OCR-extracted passport number
            // (not a hardcoded value — the previous query passed the
            // literal 'z6979971' and matched the same person on every
            // submission, leaking data and completing visa renewals
            // against the wrong employee).
            $employee    = Employee::with(['resortAdmin'])
                ->where('resort_id', $this->resort->resort_id)
                ->where('passport_number', $passport_number)
                ->first();
            
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

                        // AI can return the envelope without an
                        // extracted_fields sub-array (or with it null) on
                        // empty/unreadable scans. Normalise to an array so the
                        // direct key lookups below never hit "Trying to access
                        // array offset on value of type null".
                        $fields = (isset($AI_Data['extracted_fields']) && is_array($AI_Data['extracted_fields']))
                            ? $AI_Data['extracted_fields']
                            : [];

                        if(!empty($fields['Visa Issued Date']))
                        {
                            $visaAmtCost =  $ResortBudgetCost['VISA FEE'] ?? null;

                            VisaRenewal::create([
                                'resort_id' => $this->resort->resort_id,
                                'employee_id' => $employee->id,
                                'WP_No'=> $fields['Work Permit Number'] ?? null,
                                'start_date' => Carbon::parse($fields['Visa Issued Date'])->format('Y-m-d'),
                                'end_date' => Carbon::parse($fields['Visa Expiry Date'] ?? null)->format('Y-m-d'),
                                'Amt' => $visaAmtCost['amount'] ?? 0.00,
                            ]);
                            $visaAmt = $visaAmtCost['amount'] ?? 0.00;
                        }
                        if(!empty($fields['Insurance Expiry Date']))
                        {
                            $medical_data =  $ResortBudgetCost['MEDICAL INSURANCE - INTERNATIONAL'] ?? null;

                            EmployeeInsurance::create([
                                'resort_id' => $this->resort->resort_id,
                                'employee_id' => $employee->id,
                                'Premium' => $medical_data['amount'] ?? 0.00,
                                "Currency"=> $medical_data['unit'] ?? null,
                                'insurance_start_date' => Carbon::parse($fields['Insurance Expiry Date'])->format('Y-m-d'),
                                'insurance_end_date' => Carbon::parse($fields['Insurance Expiry Date'])->format('Y-m-d'),
                            ]);

                             $insuranceAmt=$medical_data['amount'] ?? 0.00;
                        }

                        $monthlyEntries = [];
                        if (!empty($fields['Work Permit Expiry Date (Expiry On)']))
                        {
                             $Work_permit_cost =  $ResortBudgetCost['WORK PERMIT FEE'] ?? null;
                            $expiryDate = Carbon::parse($fields['Work Permit Expiry Date (Expiry On)'])->endOfMonth(); // e.g., 2025-05-31

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

                            WorkPermit::insert($monthlyEntries);
                        }

                        $lastDueDateForDecember = collect($monthlyEntries)
                                ->filter(fn($entry) => $entry['Month'] === '12')
                                ->last()['Due_Date'] ?? null;
                 
                        if(!empty($fields['Insurance Expiry Date']))
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
                        $qotaslotAMt =  $ResortBudgetCost['QUOTA SLOT DEPOSIT'] ?? [];
                        $qotaslotDeposit = $qotaslotAMt['amount'] ?? 0.00;
                        $Eleven_month_installment= ($qotaslotDeposit - 174) / 11;

                        $totalCost = $qotaslotDeposit;
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
                                    // Without timeouts, cURL waits forever and Hostinger's
                                    // reverse proxy kills the request at ~60 s with its own
                                    // HTML 500. 50 s sits well under that so we always
                                    // fail inside PHP and return a clean JSON error.
                                    CURLOPT_TIMEOUT => 50,
                                    CURLOPT_CONNECTTIMEOUT => 10,
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

        // Safety net so the closure always returns a JsonResponse.
        return response()->json(['success' => false, 'errors' => ['message' => 'Could not process the document.']], 422);
        };

        // Persist a job row, run the extraction AFTER the response is flushed,
        // and hand the browser an id to poll. No queue worker required.
        $job = \App\Models\VisaSyncJob::create(['resort_id' => $resortId, 'status' => 'processing']);

        app()->terminating(function () use ($work, $job) {
            @set_time_limit(0);
            try {
                $data = $work()->getData(true);
                $job->update([
                    'status' => !empty($data['success']) ? 'done' : 'failed',
                    'result' => $data,
                ]);
            } catch (\Throwable $e) {
                \Log::error('Visa xpact-sync job ' . $job->id . ' failed: ' . $e->getMessage());
                $job->update([
                    'status' => 'failed',
                    'result' => ['success' => false, 'errors' => ['message' => 'Extraction failed unexpectedly. Please try again.']],
                ]);
            }
        });

        return response()->json([
            'success'    => true,
            'processing' => true,
            'job_id'     => $job->id,
            'status_url' => route('resorts.visa.xpactsync.status', $job->id),
        ], 202);
    }

    /**
     * Polled by the Xpat-sync page to get the async extraction result.
     */
    public function syncStatus($id)
    {
        $job = \App\Models\VisaSyncJob::where('resort_id', $this->resort->resort_id)->find($id);
        if (!$job) {
            return response()->json(['success' => false, 'errors' => ['message' => 'Job not found']], 404);
        }
        return response()->json([
            'success' => true,
            'status'  => $job->status,   // processing | done | failed
            'data'    => $job->result,   // the original {success,msg,errors} payload when finished
        ]);
    }

    
}
