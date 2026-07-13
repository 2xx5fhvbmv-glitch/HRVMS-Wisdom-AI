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

        $resortId  = $this->resort->resort_id;
        $extractor = new \App\Services\Visa\OpenRouterDocExtractor();

        // Records a failed job and returns the 202 the page polls (status -> failed).
        $failJob = function (string $message) use ($resortId) {
            $payload = ['success' => false, 'errors' => ['message' => $message]];
            $job = VisaSyncJob::create(['resort_id' => $resortId, 'status' => 'failed', 'result' => $payload]);
            return response()->json([
                'success' => true, 'processing' => true, 'job_id' => $job->id,
                'status_url' => route('resorts.visa.xpactsync.status', $job->id),
            ], 202);
        };

        // Extract straight from the PDFs via OpenRouter vision (no external OCR
        // service). Synchronous — a couple of vision calls fit under the web
        // timeout, so no queue worker is needed on this host.
        $xpat = $extractor->extract($Xpatfile, 'xpat_sync');
        if (($xpat['status'] ?? '') !== 'done') {
            return $failJob($xpat['message'] ?? 'Could not read the Xpat document.');
        }

        $quota = ['extracted_fields' => null];
        if ($quotaFile) {
            $quota = $extractor->extract($quotaFile, 'payment_schedule');
            if (($quota['status'] ?? '') !== 'done') {
                return $failJob($quota['message'] ?? 'Could not read the Quota Slot Fees document.');
            }
        }

        // Both extracted — run the (fast) DB saves and store the result on the
        // job, which the page's status poll then reads.
        $payload = $this->finalizeXpactSync($resortId, $xpat, $quota, $Xpatfile, $quotaFile);
        $job = VisaSyncJob::create([
            'resort_id' => $resortId,
            'status'    => !empty($payload['success']) ? 'done' : 'failed',
            'result'    => $payload,
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

        try {
            $payload = $this->finalizeXpactSync((int) $job->resort_id, $xpat, $quota);
        } catch (\Throwable $e) {
            \Log::error('Visa xpact-sync finalize crashed: ' . $e->getMessage(), ['job' => $job->id]);
            $payload = ['success' => false, 'errors' => ['message' => 'Could not save the extracted details. Please verify the document and try again.']];
        }
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
     * The OCR/LLM returns placeholder strings ("Unavailable", "N/A", …) for
     * fields it couldn't read. Treat those as "no value" so we never feed them
     * to Carbon::parse() (which throws) or store them as real data.
     */
    private function aiPlaceholder($v): bool
    {
        return in_array(strtolower(trim((string) $v)), ['', 'unavailable', 'n/a', 'na', 'none', 'null', 'not found', 'not available', '-'], true);
    }

    /** Parse an AI date field to a Carbon instance, or null for placeholders/garbage. */
    private function aiDate($v): ?\Carbon\Carbon
    {
        if ($this->aiPlaceholder($v)) return null;
        try {
            return Carbon::parse($v);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Persist the renewal records once both extractions are ready. The OCR
     * results now arrive via the async AI tasks (passed in) instead of inline
     * cURL — the rest mirrors the original synchronous save logic. Returns the
     * {success,msg} / {success:false,errors} payload.
     */
    private function finalizeXpactSync(int $resortId, array $xpat, array $quota, $xpatFile = null, $quotaFile = null): array
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

        // Match the deposit rate flexibly: employees store a demonym ("Indian")
        // while the rate list usually has the country name ("India"). Compare
        // case-insensitively, allowing either side to be a prefix of the other
        // so Indian<->India, Russian<->Russia, Syrian<->Syria all match.
        $natRaw = strtolower(trim((string) $employee->nationality));
        $VisaNationality = VisaNationality::where('resort_id', $resortId)
            ->where(function ($q) use ($natRaw) {
                $q->whereRaw('LOWER(TRIM(nationality)) = ?', [$natRaw])
                  ->orWhereRaw("? LIKE CONCAT(LOWER(TRIM(nationality)), '%')", [$natRaw])
                  ->orWhereRaw("LOWER(TRIM(nationality)) LIKE CONCAT(?, '%')", [$natRaw]);
            })
            ->first();
        if (!$VisaNationality) return $fail('Please add a Deposit Rate for ' . $employee->nationality);

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

        $visaIssued = $this->aiDate($fields['Visa Issued Date'] ?? null);
        if ($visaIssued) {
            // Many Expat System work-permit cards have no separate visa section —
            // just one Issued On/Expiry On for the whole permit. When the AI
            // couldn't find a distinct "Visa Expiry Date", fall back to the same
            // Work Permit expiry the card actually shows, rather than failing.
            $visaExpiry = $this->aiDate($fields['Visa Expiry Date'] ?? null)
                ?? $this->aiDate($fields['Work Permit Expiry Date (Expiry On)'] ?? null);
            if (!$visaExpiry) {
                return $fail('Could not read the Visa Expiry Date from the document. Please upload a clearer scan.');
            }
            $visaAmtCost = $ResortBudgetCost['VISA FEE'] ?? null;
            VisaRenewal::create([
                'resort_id'   => $resortId,
                'employee_id' => $employee->id,
                'WP_No'       => $fields['Work Permit Number'] ?? null,
                'start_date'  => $visaIssued->format('Y-m-d'),
                'end_date'    => $visaExpiry->format('Y-m-d'),
                'Amt'         => $visaAmtCost['amount'] ?? 0.00,
            ]);
            $visaAmt = $visaAmtCost['amount'] ?? 0.00;
        }

        $insuranceExpiry = $this->aiDate($fields['Insurance Expiry Date'] ?? null);
        if ($insuranceExpiry) {
            $medical_data = $ResortBudgetCost['MEDICAL INSURANCE - INTERNATIONAL'] ?? null;
            // 1-year validity: prefer the document's FROM date, else end - 1 year.
            $insuranceStart = $this->aiDate($fields['Insurance Start Date'] ?? null)
                ?? $insuranceExpiry->copy()->subYearNoOverflow()->addDay();
            // insurance_company, insurance_policy_number and insurance_coverage
            // are all NOT NULL columns. When the AI extraction doesn't find a
            // field in the document (common — e.g. no company name printed on
            // the page), this previously passed null straight through and
            // crashed the whole sync with a SQL integrity error instead of
            // saving the dates/premium we DID extract.
            EmployeeInsurance::create([
                'resort_id'               => $resortId,
                'employee_id'             => $employee->id,
                'insurance_company'       => $fields['Insurance Company Name'] ?? 'N/A',
                'insurance_policy_number' => $fields['Policy Number'] ?? 'N/A',
                'insurance_coverage'      => $fields['Insurance Coverage'] ?? 'N/A',
                'Premium'                 => $medical_data['amount'] ?? 0.00,
                'Currency'                => $medical_data['unit'] ?? null,
                'insurance_start_date'    => $insuranceStart->format('Y-m-d'),
                'insurance_end_date'      => $insuranceExpiry->format('Y-m-d'),
                'Status'                  => 'Pending',
            ]);
            $insuranceAmt = $medical_data['amount'] ?? 0.00;
        }

        $monthlyEntries = [];
        $wpExpiry = $this->aiDate($fields['Work Permit Expiry Date (Expiry On)'] ?? null);
        if ($wpExpiry) {
            $Work_permit_cost = $ResortBudgetCost['WORK PERMIT FEE'] ?? null;
            $monthlyCost = (float) ($Work_permit_cost['amount'] ?? 0.00);   // config monthly work-permit fee
            $currency    = $Work_permit_cost['unit'] ?? 'MVR';

            // Installments fall on the EXPIRY DAY, starting the month AFTER the
            // work-permit expiry, and run through December of that year (the next
            // year's set is generated on the next renewal). e.g. expiry 18 Jun
            // 2026 -> 18 Jul, 18 Aug … 18 Dec 2026, each at the full config fee.
            $cursor       = $wpExpiry->copy()->addMonthNoOverflow();
            $decemberEnd  = Carbon::create((int) $cursor->format('Y'), 12, 31)->endOfDay();
            $workPermitAmt = 0.00;
            while ($cursor->lte($decemberEnd)) {
                $monthlyEntries[] = [
                    'resort_id'    => $resortId,
                    'employee_id'  => $employee->id,
                    'Month'        => $cursor->format('m'),
                    'Payment_Date' => null,
                    'Due_Date'     => $cursor->format('Y-m-d'),
                    'Status'       => 'Unpaid',
                    'Amt'          => $monthlyCost,
                    'Currency'     => $currency,
                    'created_at'   => now(),
                ];
                $workPermitAmt += $monthlyCost;
                $cursor->addMonthNoOverflow();
            }
            if (!empty($monthlyEntries)) {
                WorkPermit::insert($monthlyEntries);
            }
        }

        $lastDueDateForDecember = collect($monthlyEntries)->filter(fn ($e) => $e['Month'] === '12')->last()['Due_Date'] ?? null;

        if ($insuranceExpiry && $lastDueDateForDecember) {
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

        // Quota Slot yearly amount from the budget config, split into monthly
        // installments: months 1..n-1 = floor(yearly/n), and the LAST month
        // absorbs the rounding remainder (e.g. 2000/12 -> 11 x 166 + 174 = 2000).
        $qotaslotAMt     = $ResortBudgetCost['QUOTA SLOT DEPOSIT'] ?? [];
        $qotaslotDeposit = (float) ($qotaslotAMt['amount'] ?? 0.00);
        $quotaCount      = max(1, count($quotaRows));
        $quotaBase       = floor($qotaslotDeposit / $quotaCount);
        $quotaLast       = $qotaslotDeposit - ($quotaBase * ($quotaCount - 1));

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

        // Persist the uploaded source PDFs so they appear under "Documents" on
        // the employee details page (the AI 'Other' blob is excluded from that
        // list). Best-effort — a storage hiccup must not fail the whole sync.
        $storeDoc = function ($file, string $docName) use ($resortId, $employee) {
            if (!$file) {
                return;
            }
            try {
                $aws = Common::AWSEmployeeFileUpload($resortId, $file, $employee->Emp_id);
                if (!empty($aws['status'])) {
                    VisaEmployeeExpiryData::where('resort_id', $resortId)
                        ->where('employee_id', $employee->id)->where('DocumentName', $docName)->delete();
                    VisaEmployeeExpiryData::create([
                        'resort_id'         => $resortId,
                        'employee_id'       => $employee->id,
                        'File_child_id'     => $aws['Chil_file_id'] ?? null,
                        'Ai_extracted_data' => json_encode([]),
                        'DocumentName'      => $docName,
                    ]);
                }
            } catch (\Throwable $e) {
                \Log::warning('xpact-sync document store failed: ' . $e->getMessage());
            }
        };
        $storeDoc($xpatFile, 'Xpat_Document');
        $storeDoc($quotaFile, 'Quota_Slot_Fees');

        DB::beginTransaction();
        try {
            foreach ($quotaRows as $key => $value) {
                $amt = ($key === ($quotaCount - 1)) ? $quotaLast : $quotaBase;
                $status = (($value['State'] ?? '') === 'FULLY PAID') ? 'Paid' : 'Unpaid';
                $dueDate = $this->aiDate($value['DatePaymentDueOn'] ?? null);
                QuotaSlotRenewal::create([
                    'resort_id'   => $resortId,
                    'Due_Date'    => $dueDate ? $dueDate->format('Y-m-d') : null,
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
