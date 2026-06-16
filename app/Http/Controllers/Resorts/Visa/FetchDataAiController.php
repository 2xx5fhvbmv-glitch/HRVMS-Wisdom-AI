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
use App\Models\VisaSyncJob;

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

    /**
     * Kick off the extraction. The heavy OCR/LLM runs ASYNCHRONOUSLY on the AI
     * server (it returns a task id instantly), so this request finishes well
     * under the web timeout — no queue worker / cron needed on the web host.
     * The browser polls syncStatus(), which checks the AI tasks and, once both
     * are ready, performs the (fast) DB saves.
     */
    public function store(Request $request)
    {
        $Xpatfile  = $request->file('Xpatfile');
        $quotaFile = $request->file('QuotaSlotFees');

        $validatePdf = function ($file, string $label) {
            if (!$file) return null;
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
        if ($validationError = $validatePdf($quotaFile, 'Quota slot fees document')) {
            return response()->json(['success' => false, 'errors' => ['message' => $validationError]], 422);
        }
        if (!$Xpatfile) {
            return response()->json(['success' => false, 'errors' => ['message' => 'Xpact File is Missing']], 422);
        }

        $resortId = $this->resort->resort_id;
        $base = $this->aiBaseUrl();

        $xpatTask = $this->startAiExtraction($base, $Xpatfile, 'xpat_sync');
        if (isset($xpatTask['__error'])) {
            return response()->json(['success' => false, 'errors' => ['message' => $xpatTask['__error']]], 502);
        }

        $quotaTaskId = null;
        if ($quotaFile) {
            $quotaTask = $this->startAiExtraction($base, $quotaFile, 'payment_schedule');
            if (isset($quotaTask['__error'])) {
                return response()->json(['success' => false, 'errors' => ['message' => $quotaTask['__error']]], 502);
            }
            $quotaTaskId = $quotaTask['task_id'];
        }

        $job = VisaSyncJob::create([
            'resort_id'     => $resortId,
            'status'        => 'processing',
            'xpat_task_id'  => $xpatTask['task_id'],
            'quota_task_id' => $quotaTaskId,
        ]);

        return response()->json([
            'success'    => true,
            'processing' => true,
            'job_id'     => $job->id,
            'status_url' => route('resorts.visa.xpactsync.status', $job->id),
        ], 202);
    }

    /**
     * Polled by the Xpat-sync page (every ~30s). Checks the async AI tasks;
     * once both are ready it runs the DB saves exactly once and records the
     * final result on the job row.
     */
    public function syncStatus($id)
    {
        $job = VisaSyncJob::where('resort_id', $this->resort->resort_id)->find($id);
        if (!$job) {
            return response()->json(['success' => false, 'errors' => ['message' => 'Job not found']], 404);
        }
        if (in_array($job->status, ['done', 'failed'], true)) {
            return response()->json(['success' => true, 'status' => $job->status, 'data' => $job->result]);
        }

        $base  = $this->aiBaseUrl();
        $xpat  = $this->fetchAiResult($base, $job->xpat_task_id);
        $quota = $job->quota_task_id ? $this->fetchAiResult($base, $job->quota_task_id) : ['status' => 'done', 'extracted_fields' => null];

        // Can't reach the AI right now → transient, keep polling.
        if (($xpat['status'] ?? '') === '__unreachable' || ($quota['status'] ?? '') === '__unreachable') {
            return response()->json(['success' => true, 'status' => 'processing', 'data' => null]);
        }
        // Extraction errored on the AI side.
        if (($xpat['status'] ?? '') === 'error' || ($quota['status'] ?? '') === 'error') {
            $payload = ['success' => false, 'errors' => ['message' => $xpat['message'] ?? $quota['message'] ?? 'Extraction failed. Please try again.']];
            $job->update(['status' => 'failed', 'result' => $payload]);
            return response()->json(['success' => true, 'status' => 'failed', 'data' => $payload]);
        }
        // Still working.
        if (($xpat['status'] ?? '') !== 'done' || ($quota['status'] ?? '') !== 'done') {
            return response()->json(['success' => true, 'status' => 'processing', 'data' => null]);
        }

        // Both ready — atomically claim the job so two overlapping polls can't
        // both run the saves (which would duplicate records).
        $claimed = VisaSyncJob::where('id', $job->id)->where('status', 'processing')->update(['status' => 'finalizing']);
        if (!$claimed) {
            return response()->json(['success' => true, 'status' => 'processing', 'data' => null]);
        }

        $payload = $this->finalizeXpactSync((int) $job->resort_id, $xpat, $quota);
        $job->update(['status' => !empty($payload['success']) ? 'done' : 'failed', 'result' => $payload]);

        return response()->json(['success' => true, 'status' => $job->status, 'data' => $payload]);
    }

    /** Base URL of the AI service, reusing the proven host from
     *  AI_extract_work_details_URL (just stripping its path/query). */
    private function aiBaseUrl(): string
    {
        $u = (string) env('AI_extract_work_details_URL');
        $base = preg_replace('#extract_work_details.*$#', '', $u);
        if (!$base) {
            $base = rtrim((string) env('AI_URL', 'http://localhost:8001/'), '/') . '/';
        }
        return rtrim($base, '/') . '/';
    }

    /** Start a background extraction on the AI server; returns ['task_id'=>..]
     *  or ['__error'=>message]. This call returns near-instantly (just the
     *  upload), so it never approaches the web timeout. */
    private function startAiExtraction(string $base, $file, string $docType): array
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $base . 'extract_async',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                'file' => new \CURLFile($file->getRealPath(), $file->getMimeType(), $file->getClientOriginalName()),
                'doc_type' => $docType,
            ],
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $resp  = curl_exec($curl);
        $errno = curl_errno($curl);
        $err   = curl_error($curl);
        curl_close($curl);

        if ($err) {
            if (in_array($errno, [CURLE_COULDNT_CONNECT, CURLE_COULDNT_RESOLVE_HOST], true)) {
                return ['__error' => 'Could not reach the AI extraction service. Please contact support if this persists.'];
            }
            return ['__error' => $err];
        }
        $data = json_decode($resp, true);
        if (empty($data['task_id'])) {
            return ['__error' => 'The AI service did not start the extraction. Please try again.'];
        }
        return ['task_id' => $data['task_id']];
    }

    /** Poll one async extraction. Returns the AI payload, or status
     *  '__unreachable' on a transport blip so the caller keeps polling. */
    private function fetchAiResult(string $base, ?string $taskId): array
    {
        if (!$taskId) return ['status' => 'done', 'extracted_fields' => null];
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $base . 'extract_result/' . $taskId,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $resp = curl_exec($curl);
        $err  = curl_error($curl);
        curl_close($curl);
        if ($err) return ['status' => '__unreachable'];
        $data = json_decode($resp, true);
        return is_array($data) ? $data : ['status' => '__unreachable'];
    }

    /**
     * Persist the renewal records once both extractions are ready. The OCR
     * results now arrive via the async AI tasks (passed in) instead of inline
     * cURL — the rest mirrors the original synchronous save logic. Returns the
     * {success,msg} / {success:false,errors} payload.
     */
    private function finalizeXpactSync(int $resortId, array $xpat, array $quota): array
    {
        $fail = fn ($m) => ['success' => false, 'errors' => ['message' => $m]];

        $fields = (isset($xpat['extracted_fields']) && is_array($xpat['extracted_fields'])) ? $xpat['extracted_fields'] : [];
        $ResortBudgetCost = Common::VisaRenewalCost($resortId);

        $passportRaw = $fields["Employee's Passport Number"] ?? null;
        $placeholder = in_array(strtolower(trim((string) $passportRaw)), ['unavailable', 'n/a', 'na', 'none', 'null', 'not found', '-'], true);
        if (empty($passportRaw) || $placeholder) {
            return $fail('Could not read passport number from the document. Please upload a clearer scan.');
        }
        $passport_number = preg_replace('/[^A-Za-z0-9]/', '', (string) $passportRaw);

        $employee = Employee::with(['resortAdmin'])->where('resort_id', $resortId)->where('passport_number', $passport_number)->first();
        if (!$employee) return $fail('Employee not found');

        $VisaNationality = VisaNationality::where('resort_id', $resortId)->where('nationality', $employee->nationality)->first();
        if (!$VisaNationality) return $fail('Please Add  Deposit Rate for the' . $employee->nationality);

        if (QuotaSlotRenewal::where('employee_id', $employee->id)->first()) {
            $name = $employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name;
            return $fail("Quota Slot Renewal already exists for {$name}. Please proceed with renewal.");
        }

        $quotaRows = (isset($quota['extracted_fields']) && is_array($quota['extracted_fields'])) ? $quota['extracted_fields'] : [];
        if (empty($quotaRows)) {
            return $fail('Quota Slot Fees file is missing or could not be read.');
        }

        $joiningDate = Carbon::parse($employee->joining_date)->startOfMonth();
        $endMonth = Carbon::create($joiningDate->year, 12, 1);
        $insuranceAmt = 0.00; $workPermitAmt = 0.00; $workPermitMedicalAmt = 0.00; $visaAmt = 0.00;

        if (!empty($fields['Visa Issued Date'])) {
            $visaAmtCost = $ResortBudgetCost['VISA FEE'] ?? null;
            VisaRenewal::create([
                'resort_id'   => $resortId,
                'employee_id' => $employee->id,
                'WP_No'       => $fields['Work Permit Number'] ?? null,
                'start_date'  => Carbon::parse($fields['Visa Issued Date'])->format('Y-m-d'),
                'end_date'    => Carbon::parse($fields['Visa Expiry Date'] ?? null)->format('Y-m-d'),
                'Amt'         => $visaAmtCost['amount'] ?? 0.00,
            ]);
            $visaAmt = $visaAmtCost['amount'] ?? 0.00;
        }

        if (!empty($fields['Insurance Expiry Date'])) {
            $medical_data = $ResortBudgetCost['MEDICAL INSURANCE - INTERNATIONAL'] ?? null;
            EmployeeInsurance::create([
                'resort_id'            => $resortId,
                'employee_id'          => $employee->id,
                'Premium'              => $medical_data['amount'] ?? 0.00,
                'Currency'             => $medical_data['unit'] ?? null,
                'insurance_start_date' => Carbon::parse($fields['Insurance Expiry Date'])->format('Y-m-d'),
                'insurance_end_date'   => Carbon::parse($fields['Insurance Expiry Date'])->format('Y-m-d'),
            ]);
            $insuranceAmt = $medical_data['amount'] ?? 0.00;
        }

        $monthlyEntries = [];
        if (!empty($fields['Work Permit Expiry Date (Expiry On)'])) {
            $Work_permit_cost = $ResortBudgetCost['WORK PERMIT FEE'] ?? null;
            $expiryDate = Carbon::parse($fields['Work Permit Expiry Date (Expiry On)'])->endOfMonth();
            $totalMonths = $joiningDate->diffInMonths($endMonth) + 1;
            $totalCost = $Work_permit_cost['amount'] ?? 0.00;
            $monthlyCost = $totalMonths > 0 ? round($totalCost / $totalMonths, 2) : 0.00;
            $currency = $Work_permit_cost['unit'] ?? null;
            $workPermitAmt = $totalCost;
            for ($i = 0; $i < $totalMonths; $i++) {
                $monthStart = $joiningDate->copy()->addMonths($i);
                $monthEnd = $monthStart->copy()->endOfMonth();
                $nextMonthStart = $monthStart->copy()->addMonth()->startOfMonth();
                $monthlyEntries[] = [
                    'resort_id'    => $resortId,
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

        $lastDueDateForDecember = collect($monthlyEntries)->filter(fn ($e) => $e['Month'] === '12')->last()['Due_Date'] ?? null;

        if (!empty($fields['Insurance Expiry Date'])) {
            $medical_data = $ResortBudgetCost['WORK VISA MEDICAL TEST FEE'] ?? null;
            WorkPermitMedicalRenewal::create([
                'resort_id'   => $resortId,
                'employee_id' => $employee->id,
                'Amt'         => $medical_data['amount'] ?? 0.00,
                'Currency'    => $medical_data['unit'] ?? null,
                'start_date'  => Carbon::parse($joiningDate)->format('Y-m-d'),
                'end_date'    => Carbon::parse($lastDueDateForDecember)->format('Y-m-d'),
            ]);
            $workPermitMedicalAmt = $medical_data['amount'] ?? 0.00;
        }

        $qotaslotAMt = $ResortBudgetCost['QUOTA SLOT DEPOSIT'] ?? [];
        $qotaslotDeposit = $qotaslotAMt['amount'] ?? 0.00;
        $Eleven_month_installment = $qotaslotDeposit ? ($qotaslotDeposit - 174) / 11 : 0.00;

        TotalExpensessSinceJoing::create([
            'resort_id'                         => $resortId,
            'employees_id'                      => $employee->id,
            'Deposit_Amt'                       => $VisaNationality->amt ?? 0.00,
            'Total_work_permit'                 => $workPermitAmt ?? 0.00,
            'Total_slot_Payment'                => $qotaslotDeposit ?? 0.00,
            'Total_insurance_Payment'           => $insuranceAmt ?? 0.00,
            'Total_Work_Permit_Medical_Payment' => $workPermitMedicalAmt ?? 0.00,
            'Total_Visa_Payment'                => $visaAmt ?? 0.00,
            'Date'                              => Carbon::now()->format('Y-m-d'),
            'Year'                              => Carbon::now()->format('Y'),
        ]);

        VisaEmployeeExpiryData::where('resort_id', $resortId)
            ->where('employee_id', $employee->id)->where('DocumentName', 'Other')->delete();
        VisaEmployeeExpiryData::create([
            'resort_id'         => $resortId,
            'employee_id'       => $employee->id,
            'File_child_id'     => null,
            'Ai_extracted_data' => json_encode($xpat),
            'DocumentName'      => 'Other',
        ]);

        DB::beginTransaction();
        try {
            foreach ($quotaRows as $key => $value) {
                $amt = ($key == 0) ? 174 : $Eleven_month_installment;
                $status = (($value['State'] ?? '') === 'FULLY PAID') ? 'Paid' : 'Unpaid';
                QuotaSlotRenewal::create([
                    'resort_id'   => $resortId,
                    'Due_Date'    => $value['DatePaymentDueOn'] ?? null,
                    'employee_id' => $employee->id,
                    'Month'       => $value['Month'] ?? null,
                    'Currency'    => 'MVR',
                    'Amt'         => $amt,
                    'Status'      => $status,
                ]);
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Visa xpact-sync finalize failed: ' . $e->getMessage());
            return $fail('Could not save the renewal records. Please try again.');
        }

        // Return the employee name so the page can redirect to verify-details
        // pre-filtered to the staff member whose documents were just updated.
        $empName = trim(($employee->resortAdmin->first_name ?? '') . ' ' . ($employee->resortAdmin->last_name ?? ''));
        return [
            'success'  => true,
            'msg'      => 'Quota Slot renewal Created Successfully.',
            'employee' => $empName,
            'emp_id'   => $employee->Emp_id,
        ];
    }
}
