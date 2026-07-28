<?php

namespace App\Http\Controllers\Resorts\People\Compliances;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use Auth;
use Config;
use DB;
use PDF;
use App\Helpers\Common;
use App\Models\Compliance;
use App\Models\Employee;
use App\Models\ResortAdmin;
use App\Models\ResortPosition;
use App\Models\ResortDepartment;
use App\Models\EmployeeInfoUpdateRequest;
use App\Models\ManningandbudgetingConfigfiles;
use App\Models\ParentAttendace;
use App\Models\BreakAttendaces;
use App\Models\ChildAttendace;
use App\Models\Vacancies;
use App\Models\EmployeeLeave;
use App\Models\LeaveCategory;
use App\Models\PayrollServiceCharge;
use App\Models\Payroll;
use App\Models\Incidents;
use App\Models\ResortBenifitGrid;
use App\Models\PayrollDeduction;
use App\Events\ResortNotificationEvent;
use App\Models\ResortNotification;
use Carbon\Carbon;
use App\Models\EmployeeOnboardingAcknowledgements;
use App\Models\EmployeePromotion;
use App\Models\JobDescription;
use App\Models\ResortSiteSettings;
use App\Models\ResortHoliday;

class ComplianceController extends Controller
{
    public $resort;
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;
    }

    /**
     * Call the AI service to enrich a freshly-created compliance row with
     * a severity assessment, plain-language explanation, and concrete
     * remediation. Writes the three columns back onto the row and stamps
     * ai_status / ai_generated_at.
     *
     * Defensive: any failure (timeout, parse error, unreachable host)
     * marks the row ai_status='failed' but leaves the original
     * description intact, so the view always has SOMETHING to show.
     * The 25 s timeout is the controller-level cap; the FastAPI client
     * has its own 90 s LLM cap (BedrockAsync) but the resort-side rules
     * engine runs through ~15 categories so we keep the per-call ceiling
     * tight to avoid letting a single hung call stall the whole run.
     */
    /**
     * Bulk re-enrich the last N compliance rows for this resort with AI.
     * Triggered from the "Regenerate AI" button on /people/compliance.
     *
     * Defaults to the 50 most-recent breached rows so users get a quick
     * "everything I'm staring at gets refreshed" without a full table
     * walk. The body cap (max 200) keeps the response size sane and
     * stops a hung AI call from holding the worker for too long.
     */
    public function regenerateAi(Request $request)
    {
        if (!$this->resort) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        if (Common::checkRouteWisePermission('people.compliance.index', config('settings.resort_permissions.edit')) == false) {
            return response()->json(['success' => false, 'message' => 'Not permitted to regenerate AI insights'], 403);
        }

        // ONE batch of 15 per click. Previously a Regenerate click
        // processed up to 50 rows in one HTTP request and routinely blew
        // Hostinger's ~60 s reverse-proxy timeout. Then I auto-paged in
        // groups of 10, which worked but meant every click ran ALL rows
        // — HR wanted explicit, per-click batches instead.
        //
        // Selection order — natural rotation, no client-side cursor:
        //   1. Rows never AI-enriched (ai_generated_at IS NULL) come
        //      FIRST so brand-new breaches always surface fastest.
        //   2. Then by oldest ai_generated_at, so the same row never gets
        //      re-processed until everything else has had its turn.
        // This means consecutive clicks naturally process different rows
        // without the front-end having to track offsets.
        $limit = (int) ($request->input('limit', 15) ?: 15);
        $limit = max(1, min(20, $limit));

        // CRITICAL: this filter chain MUST match the one in list() below.
        // The list view does NOT require status='Breached' — older
        // compliance rules (e.g. Time and Attendance / Over Time Not
        // Eligible) created rows with status='' (empty string). Those
        // rows DO surface on the dashboard but used to be skipped by
        // Regenerate, leaving HR with rows they could see but couldn't
        // enrich — the "NEW AI" badge count would mysteriously cap.
        //
        // Match list()'s filter exactly:
        //   - Dismissal_status = 'Pending'   (not dismissed)
        //   - whereHas employee + valid resortAdmin  (visible employee)
        // No status filter — any visible row is eligible.
        $resortId = $this->resort->resort_id;
        $baseQuery = Compliance::where('resort_id', $resortId)
            ->where('Dismissal_status', 'Pending')
            ->whereHas('employee', function ($q) use ($resortId) {
                $q->where('resort_id', $resortId)->whereHas('resortAdmin');
            });
        $totalEligible = (clone $baseQuery)->count();

        $rows = (clone $baseQuery)
            // NULLs first (never-enriched), then oldest ai_generated_at.
            // MySQL: NULLs already sort first under ASC, so a single
            // orderBy gives the right rotation behaviour.
            ->orderByRaw('ai_generated_at IS NULL DESC')
            ->orderBy('ai_generated_at', 'asc')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $okCount   = 0;
        $failCount = 0;
        // Track distinct failure reasons so the user-facing message is
        // actionable. Without this they see "AI generation failed" with no
        // hint whether the AI host was unreachable, the LLM timed out, or
        // the response body was malformed.
        $failReasons = [];
        // IDs of rows that completed successfully this batch — sent back
        // to the JS so it can flash them green for ~10 s after the table
        // reloads. Lets HR see at a glance which 15 rows were just done.
        $processedIds = [];
        foreach ($rows as $r) {
            // Reconstruct a minimal but useful context. Per-row context
            // (the rich employee snapshot the rules engine passes) only
            // exists at create-time; for the bulk case we hydrate what
            // we can from the row + its employee relation. The model
            // still gets the original `description` text in the prompt,
            // so the explanation stays anchored even when context is thin.
            $emp = $r->employee_id ? Employee::with('resortAdmin', 'position', 'department')->find($r->employee_id) : null;
            $ctx = [];
            if ($emp) {
                // No employee_name / employee_code / employee_id keys —
                // the latest prompt forbids naming the employee in the
                // prose, and the LLM was leaking these field names
                // directly into output ("(employee_code: the affected
                // employee)"). The compliance row already shows the
                // employee in dedicated columns, so the AI only needs
                // the structural context (position, department, rank,
                // nationality, salary) to reason about the breach.
                $ctx = [
                    'position'      => optional($emp->position)->position_title,
                    'department'    => optional($emp->department)->name,
                    'rank'          => $emp->rank,
                    'nationality'   => $emp->nationality,
                    'basic_salary'  => $emp->basic_salary,
                ];
            }
            $this->enrichComplianceWithAi($r, $ctx);
            $r->refresh();
            if ($r->ai_status === 'ready') {
                $okCount++;
                $processedIds[] = (int) $r->id;
            } else {
                $failCount++;
                // 'timeout' / 'failed' are the only two statuses the
                // helper writes on a non-success path.
                $reason = $r->ai_status ?: 'failed';
                $failReasons[$reason] = ($failReasons[$reason] ?? 0) + 1;
            }
        }

        // Build a single human sentence: "5 timed out, 1 failed". Easier
        // for HR than a JSON breakdown.
        $reasonText = '';
        if (!empty($failReasons)) {
            $parts = [];
            foreach ($failReasons as $reason => $n) {
                $label = $reason === 'timeout' ? 'timed out' : 'failed';
                $parts[] = "{$n} {$label}";
            }
            $reasonText = ' (' . implode(', ', $parts) . ' — see logs)';
        }

        $processed = $rows->count();

        // How many breached rows still have NO AI insight at all
        // (ai_generated_at IS NULL), counted AFTER this batch saved.
        //
        // This MUST be a fresh count, not `totalEligible - processed`.
        // The old subtraction was wrong once total > one batch: it
        // subtracted only the current batch from the grand total every
        // click, so for 27 rows it oscillated 12 → 15 → 12 forever and
        // never reached 0 — the user saw "12 more eligible" indefinitely
        // and the same rows got re-processed instead of completing.
        //
        // Because selection is never-enriched-first AND every processed
        // row (success, timeout, or fail) stamps ai_generated_at, this
        // NULL count drops by exactly `processed` each click and counts
        // down cleanly to 0. The JS treats 0 as "complete". Re-running a
        // full fresh sweep later (e.g. after a prompt change) would show
        // 0 immediately since every row is already stamped — clicking
        // still rotates the oldest rows, it just won't show a countdown.
        $remainingEligible = (clone $baseQuery)->whereNull('ai_generated_at')->count();

        return response()->json([
            'success'            => true,
            'message'            => "Regenerated AI for {$okCount} rows" . $reasonText,
            'enriched'           => $okCount,
            'failed'             => $failCount,
            'fail_reasons'       => $failReasons,
            'processed'          => $processed,
            'processed_ids'      => $processedIds,  // for the highlight-after-reload flash
            'total_breached'     => $totalEligible,
            'remaining_eligible' => $remainingEligible,
            // Legacy alias for any caller that already reads `.total`.
            'total'              => $processed,
        ]);
    }

    /**
     * Layer-2 AI anomaly scan — sends a resort-level snapshot to the
     * FastAPI /compliance_scan endpoint and files each returned anomaly
     * as a new Compliance row with module_name = 'AI Anomaly Detection'.
     *
     * Distinct from the rules engine (checkCompliance) which applies
     * hard thresholds. This one catches outliers/patterns the engine
     * can't encode statically. Capped at 20 new rows per call so a
     * runaway LLM can't flood the compliance list.
     */
    public function runAnomalyScan(Request $request)
    {
        if (!$this->resort) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        if (Common::checkRouteWisePermission('people.compliance.index', config('settings.resort_permissions.create')) == false) {
            return response()->json(['success' => false, 'message' => 'Not permitted to run anomaly scan'], 403);
        }

        $resort_id = $this->resort->resort_id;
        $resort = \App\Models\Resort::find($resort_id);

        // Build the snapshot. Keep it dense — the LLM has a context cap.
        // - Per-position salary distribution (median/min/max + count).
        // - Per-department employee mix (count + expat ratio + avg salary).
        // - Active probationary cohort.
        $positionStats = Employee::with('position')
            ->where('resort_id', $resort_id)
            ->where('status', 'Active')
            ->whereNotNull('Position_id')
            ->get()
            ->groupBy('Position_id')
            ->map(function ($emps, $positionId) {
                $sals = $emps->pluck('basic_salary')->filter()->sort()->values();
                $count = $sals->count();
                if ($count === 0) return null;
                $median = $count % 2 === 1
                    ? $sals[intdiv($count, 2)]
                    : (($sals[$count / 2 - 1] + $sals[$count / 2]) / 2);
                return [
                    'position_id'    => (int) $positionId,
                    'position_title' => optional($emps->first()->position)->position_title,
                    'headcount'      => $count,
                    'salary_median'  => (float) $median,
                    'salary_min'     => (float) $sals->first(),
                    'salary_max'     => (float) $sals->last(),
                    // Top earners surface salary outliers vs the median.
                    // No employee_code / name keys here either — the AI is
                    // forbidden from referencing the employee by ID, code,
                    // or name in description/remediation prose (the row
                    // displays the employee in dedicated columns). Salary
                    // and rank are what the model needs to reason about
                    // the anomaly.
                    'top_earners'    => $emps->sortByDesc('basic_salary')->take(3)->map(fn($e) => [
                        'salary'      => (float) $e->basic_salary,
                        'rank'        => $e->rank,
                    ])->values()->all(),
                ];
            })
            ->filter()
            ->values()
            ->all();

        $departmentStats = Employee::with('department', 'resortAdmin')
            ->where('resort_id', $resort_id)
            ->where('status', 'Active')
            ->whereNotNull('Dept_id')
            ->get()
            ->groupBy('Dept_id')
            ->map(function ($emps, $deptId) {
                $count    = $emps->count();
                $expats   = $emps->where('nationality', '!=', 'Maldivian')->count();
                $avgSal   = $emps->pluck('basic_salary')->filter()->avg() ?: 0;
                return [
                    'dept_id'        => (int) $deptId,
                    'dept_name'      => optional($emps->first()->department)->name,
                    'headcount'      => $count,
                    'expat_count'    => $expats,
                    'expat_ratio'    => $count > 0 ? round(($expats / $count) * 100, 1) : 0,
                    'avg_salary'     => round($avgSal, 2),
                ];
            })
            ->values()
            ->all();

        $probationStats = [
            'active_count'  => Employee::where('resort_id', $resort_id)->where('status', 'Active')->whereIn('probation_status', ['Active', 'Extended'])->count(),
            'extended_count'=> Employee::where('resort_id', $resort_id)->where('status', 'Active')->where('probation_status', 'Extended')->count(),
        ];

        $payload = [
            'resort_id'      => $resort_id,
            'resort_name'    => $resort ? $resort->resort_name : null,
            'resort_country' => 'Maldives',
            'currency'       => 'USD',
            'max_anomalies'  => 20,
            'snapshot'       => [
                'positions'   => $positionStats,
                'departments' => $departmentStats,
                'probation'   => $probationStats,
                'totals'      => [
                    'active_employees' => Employee::where('resort_id', $resort_id)->where('status', 'Active')->count(),
                    'departments'      => count($departmentStats),
                    'positions'        => count($positionStats),
                ],
            ],
        ];

        // Resolve the AI host — prefer AI_BASE_URL but fall back to
        // the existing AI_URL the live .env defines.
        $url = rtrim((string) (env('AI_BASE_URL') ?: env('AI_URL', 'http://localhost:8001')), '/') . '/compliance_scan';

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
            // Longer than the per-row enrichment because this is a single
            // big-context LLM call. Still under Hostinger's ~60 s wall by
            // having the FE button confirm with the user first.
            CURLOPT_TIMEOUT        => 80,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $response = curl_exec($curl);
        $errno    = curl_errno($curl);
        $err      = curl_error($curl);
        curl_close($curl);

        if ($errno !== 0 || !$response) {
            $msg = $errno === CURLE_OPERATION_TIMEDOUT
                ? 'AI anomaly scan timed out. Try again in a moment.'
                : 'AI anomaly scan failed. Check that the AI service is reachable.';
            \Log::warning("compliance anomaly scan failed: " . ($err ?: 'no response'));
            return response()->json(['success' => false, 'message' => $msg], $errno === CURLE_OPERATION_TIMEDOUT ? 504 : 502);
        }

        $decoded = json_decode($response, true);
        $anomalies = is_array($decoded) ? ($decoded['anomalies'] ?? []) : [];
        if (!is_array($anomalies)) {
            return response()->json(['success' => false, 'message' => 'AI returned an unexpected response shape.'], 502);
        }

        $created       = 0;
        $droppedNoEmp  = 0;   // resort-level findings or invalid employee refs
        $droppedEmpty  = 0;   // empty description
        $createdIds    = [];  // for the highlight-after-reload flash (same as regenerate)

        foreach ($anomalies as $a) {
            $type = trim((string) ($a['anomaly_type'] ?? 'Unknown Anomaly'));
            $sev  = trim((string) ($a['severity'] ?? 'Medium'));
            $desc = trim((string) ($a['description'] ?? ''));
            $rem  = trim((string) ($a['remediation'] ?? ''));
            if ($desc === '') { $droppedEmpty++; continue; }

            // For the row to be visible on the dashboard list, the
            // employee_id must reference an employee in THIS resort that
            // has an attached resortAdmin (same filter as list() and
            // regenerateAi). Resort-level anomalies (employee_id NULL)
            // or references to deleted/foreign employees won't surface
            // on the dashboard — filing them is wasted work and confuses
            // HR who expects 20 anomalies and sees 14.
            $empId = $a['employee_id'] ?? null;
            $emp   = null;
            if ($empId !== null) {
                $emp = Employee::where('id', $empId)
                    ->where('resort_id', $resort_id)
                    ->whereHas('resortAdmin')
                    ->first();
            }
            if (!$emp) {
                // Either the AI returned a resort-wide finding (no
                // specific employee) or the employee was terminated /
                // belongs to another resort. Log for HR's awareness but
                // don't create a row that would never be seen.
                $droppedNoEmp++;
                \Log::info('anomaly_scan: dropping unvisible anomaly', [
                    'resort_id'    => $resort_id,
                    'anomaly_type' => $type,
                    'employee_id'  => $empId,
                ]);
                continue;
            }

            $row = Compliance::create([
                'resort_id'                => $resort_id,
                'employee_id'              => $emp->id,
                'module_name'              => 'AI Anomaly Detection',
                'compliance_breached_name' => $type,
                // Use the AI text as the canonical description AND
                // populate the AI columns so the list view styles it
                // with the severity badge.
                'description'              => $desc,
                'description_ai'           => $desc,
                'remediation_ai'           => $rem,
                'severity_ai'              => in_array($sev, ['Critical','High','Medium','Low','Info'], true) ? $sev : 'Medium',
                'ai_status'                => 'ready',
                'ai_generated_at'          => now(),
                'reported_on'              => Carbon::now(),
                'status'                   => 'Breached',
                // Dismissal_status defaults to 'Pending' (see migration
                // 2025_07_25_124643_add_fieldsincompliances) so the row
                // surfaces on the dashboard without an explicit set.
            ]);
            $created++;
            $createdIds[] = (int) $row->id;
        }

        $msg = "AI scan complete — {$created} new anomal" . ($created === 1 ? 'y' : 'ies') . ' filed';
        if ($droppedNoEmp > 0) {
            $msg .= " ({$droppedNoEmp} resort-level/orphan finding" . ($droppedNoEmp === 1 ? '' : 's') . ' dropped — see logs)';
        }

        return response()->json([
            'success'        => true,
            'message'        => $msg,
            'created'        => $created,
            'created_ids'    => $createdIds,   // matches regenerate.processed_ids shape
            'processed_ids'  => $createdIds,   // alias so JS can reuse the same highlight helper
            'dropped_no_employee' => $droppedNoEmp,
            'dropped_empty'  => $droppedEmpty,
            'returned'       => count($anomalies),
        ]);
    }

    private function enrichComplianceWithAi($compliance, array $context = []): void
    {
        if (!$compliance) return;

        // Resolve the AI host — prefer AI_BASE_URL but fall back to
        // the existing AI_URL the live .env defines.
        $url = rtrim((string) (env('AI_BASE_URL') ?: env('AI_URL', 'http://localhost:8001')), '/') . '/compliance_explain';
        $payload = [
            'rule_name'             => (string) $compliance->compliance_breached_name,
            'rule_module'           => (string) $compliance->module_name,
            'hardcoded_description' => (string) $compliance->description,
            'resort_country'        => 'Maldives',
            'currency'              => 'USD',
            // Empty PHP array json-encodes to [] which the FastAPI
            // Pydantic Dict[str, Any] rejects. Cast to object so it
            // serialises as {} when the caller didn't supply context.
            'context'               => empty($context) ? (object) [] : $context,
        ];

        // Two attempts: a flaky DeepSeek node or a transient TLS handshake
        // would previously fail the row on first try and leave HR clicking
        // Regenerate repeatedly. One retry catches that without ballooning
        // wall-clock — the per-row budget is still inside the 60 s wall.
        $maxAttempts = 2;
        $response    = false;
        $errno       = 0;
        $err         = '';
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($payload),
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
                // 45 s leaves enough room for one retry inside Hostinger's
                // ~60 s reverse-proxy wall when this helper is called from
                // a non-batched code path. The batched (regenerateAi) caller
                // pages in groups of 10 so the wall isn't a concern there.
                CURLOPT_TIMEOUT        => 45,
                CURLOPT_CONNECTTIMEOUT => 5,
            ]);
            $response = curl_exec($curl);
            $errno    = curl_errno($curl);
            $err      = curl_error($curl);
            curl_close($curl);
            if ($errno === 0 && $response) {
                break; // success → exit retry loop
            }
            // Don't retry on timeouts — they'd just blow the wall budget.
            if ($errno === CURLE_OPERATION_TIMEDOUT) {
                break;
            }
        }

        if ($errno !== 0 || !$response) {
            $compliance->ai_status        = $errno === CURLE_OPERATION_TIMEDOUT ? 'timeout' : 'failed';
            $compliance->ai_generated_at  = now();
            $compliance->save();
            \Log::warning("compliance AI enrichment failed (compliance #{$compliance->id}, url={$url}): " . ($err ?: 'no response'));
            return;
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded) || empty($decoded['description'])) {
            $compliance->ai_status        = 'failed';
            $compliance->ai_generated_at  = now();
            $compliance->save();
            \Log::warning("compliance AI returned unparseable body (compliance #{$compliance->id}): " . substr((string) $response, 0, 300));
            return;
        }

        // Validate severity against the badge set the view knows how to
        // render. An unexpected value (e.g. the LLM emitting "Severe" or
        // sentence-case noise) used to leak straight into the column and
        // showed up as a malformed badge.
        $rawSev = (string) ($decoded['severity'] ?? 'Medium');
        $sev    = in_array($rawSev, ['Critical', 'High', 'Medium', 'Low', 'Info'], true) ? $rawSev : 'Medium';

        $compliance->severity_ai     = $sev;
        $compliance->description_ai  = (string) ($decoded['description'] ?? '');
        $compliance->remediation_ai  = (string) ($decoded['remediation'] ?? '');
        $compliance->ai_status       = 'ready';
        $compliance->ai_generated_at = now();
        $compliance->save();
    }

     public function index(Request $request){
          $page_title = 'Compliance';
        $resort = $this->resort;
            $positions = ResortPosition::where('resort_id', $resort->resort_id)->where('status', 'active')->get();
               $departments = ResortDepartment::where('resort_id', $resort->resort_id)->where('status', 'active')->get();
            return view('resorts.people.compliance.list', compact('positions', 'departments','page_title'));

     }

     // List Page
     public function list(Request $request){
          $resort = $this->resort;
          $resortId = $resort->resort_id;
          $positions = ResortPosition::where('resort_id', $resortId)->where('status', 'active')->get();
          $departments = ResortDepartment::where('resort_id', $resortId)->where('status', 'active')->get();
          // Constrain the eager-loaded employee to the SAME resort so that
          // even if a compliance row was created with an employee_id from
          // another resort (data bug), the relationship returns null and
          // the row gets filtered out by the whereHas() guard below.
          // Without this, the employee_name / profile picture / Emp_id
          // shown for the compliance row could come from a DIFFERENT
          // resort's employee — the reported "other resort data" leak.
          $query = Compliance::where('resort_id', $resortId)->with([
               'employee' => function ($q) use ($resortId) {
                    $q->where('resort_id', $resortId)->with('resortAdmin');
               },
          ]);
          if ($request->searchTerm != null) {
               $searchTerm = $request->searchTerm;

               $query->whereHas('employee', function ($q) use ($request, $searchTerm) {   
                    $q->whereHas('resortAdmin', function ($Qname) use ($searchTerm) {
                         $Qname->where('id', 'LIKE', "%{$searchTerm}%")
                              ->orWhere('first_name', 'LIKE', "%{$searchTerm}%")     
                              ->orWhere('last_name', 'LIKE', "%{$searchTerm}%")
                              ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "%{$searchTerm}%");
                    });
               });
          }
          if ($request->status != null) {
               $query->where('status', $request->status);
          }

          if ($request->module_name != null) {
               $query->where('module_name', $request->module_name);
          }

          if ($request->compliance_breached_name != null) {
               $query->where('compliance_breached_name', $request->compliance_breached_name);
          }
          // whereHas restricts the result to rows whose employee belongs to
          // THIS resort. Belt-and-braces with the eager-load constraint
          // above — together they ensure rows with cross-resort
          // employee_id don't appear AND no cross-resort employee data is
          // hydrated.
          $compliances = $query
               ->whereHas('employee', function ($q) use ($resortId) {
                    $q->where('resort_id', $resortId)->whereHas('resortAdmin');
               })
               ->where("Dismissal_status", "Pending")
               // Order so the most recently AI-regenerated rows surface
               // at the top. HR clicks "Regenerate AI" → the 15 rows
               // just processed now sit at positions 1-15 in the list,
               // making the batch trivially visible. Rows never enriched
               // (ai_generated_at NULL) drop to the bottom — they don't
               // outrank a freshly regenerated row in the list.
               ->orderByRaw('ai_generated_at IS NULL ASC')
               ->orderByDesc('ai_generated_at')
               ->orderByDesc('id')
               ->get();

          if ($request->ajax()) 
          {
               $edit_class = '';
               if (Common::checkRouteWisePermission('people.compliances.edit', config('settings.resort_permissions.edit')) == false) 
               {
                    $edit_class = 'd-none';
               }
               return datatables()->of($compliances)
                    ->addIndexColumn()  // This adds the DT_RowIndex column
                    ->addColumn('employee_id', function ($compliance) {
                         return $compliance->employee ? $compliance->employee->Emp_id : '-';
                    })
                    ->addColumn('employee_name', function ($compliance) {
                         if (!$compliance->employee) {
                              return '<span class="text-danger">-</span>';
                         }
                         $name = $compliance->employee->resortAdmin->full_name ?? 'N/A';
                         $profileImage = Common::getResortUserPicture($compliance->employee->Admin_Parent_id);
                         return '
                         <div class="tableUser-block">
                              <div class="img-circle">
                                   <img src="'.e($profileImage).'" alt="user">
                              </div>
                              <span class="userApplicants-btn">'.e($name).'</span>
                         </div>';
                    })
                    ->addColumn('reported_on', function ($compliance) {
                         // Human-readable: "09 Jun 2026 14:32". Drops seconds
                         // (no operational value on this screen) and uses a
                         // 3-letter month so the column never wraps.
                         return $compliance->reported_on ? Carbon::parse($compliance->reported_on)->format('d M Y H:i') : 'N/A';
                    })
                    // Render the description column with the AI enrichment
                    // when present: severity badge + AI explanation + the
                    // suggested remediation. Falls back to the original
                    // hardcoded `description` whenever the AI field is
                    // empty (rule fired but enrichment failed, or the row
                    // pre-dates the migration). Same column, richer
                    // payload — the existing DataTables binding doesn't
                    // need to change.
                    ->editColumn('description', function ($compliance) {
                         $aiDesc = trim((string) ($compliance->description_ai ?? ''));
                         $hard   = (string) $compliance->description;
                         if ($aiDesc === '') {
                              return e($hard);
                         }

                         // Severity → badge class. Unknown values fall to "secondary".
                         $sev   = trim((string) ($compliance->severity_ai ?? 'Medium'));
                         $color = [
                              'Critical' => 'danger',
                              'High'     => 'warning',
                              'Medium'   => 'info',
                              'Low'      => 'secondary',
                              'Info'     => 'secondary',
                         ][$sev] ?? 'secondary';

                         $html  = '<div class="compliance-ai-cell">';
                         $html .= '<div class="mb-1"><span class="badge badge-themeGrayLight me-1">AI</span><span class="badge badge-' . $color . '">' . e($sev) . '</span></div>';
                         $html .= '<div class="mb-1">' . e($aiDesc) . '</div>';
                         $rem   = trim((string) ($compliance->remediation_ai ?? ''));
                         if ($rem !== '') {
                              $html .= '<div class="text-muted small"><strong>Suggested fix:</strong> ' . e($rem) . '</div>';
                         }
                         return $html;
                    })
                    ->addColumn('status', function ($compliance) {
                         if($compliance->status =="")
                         {
                              $status = 'Breached';
                              $color ="warning";

                         }
                         elseif($compliance->status =="Breached")
                         {
                              $status = 'Breached';
                              $color ="warning";

                         }
                         else 
                         {
                              $status = 'Resolved';
                              $color ="success";

                         }
                        
                         return '<span class="badge badge-' . ($color) . '">' . $status . '</span>';
                    
                    })
                    ->addColumn('action', function ($compliance) use ($edit_class) {  // Changed from 'actions' to 'action'
                         $actions = '<div>';
                         if($edit_class != '') {
                              $actions .= '<a href="javascript:void(0)" class="a-link">-</a>';
                         }else{
                              $actions .= '<a href="javascript::void(0)" data-id="'.base64_encode($compliance->id).'" class="a-link dismmisal '.$edit_class.'">Dismiss</a>';
                         }
                         $actions .= '</div>';
                         return $actions;
                    })
                    // Allow the AI-enriched description HTML (badge + remediation
                    // block) through unescaped. The data we inject is already
                    // run through e() inside editColumn('description'), so the
                    // HTML the DataTable sees is safe.
                    ->rawColumns(['employee_name','status', 'action', 'description'])
                    // Stamp the compliance row id onto the <tr> as
                    // data-compliance-id so the post-regenerate JS can
                    // flash newly-processed rows green.
                    ->setRowAttr([
                        'data-compliance-id' => fn ($c) => (int) $c->id,
                    ])
                    // SERVER-SIDE freshness flag: any row whose AI was
                    // (re)generated in the last 30 minutes gets the
                    // ai-fresh-row class. This keeps the "NEW" pill +
                    // green left border visible across pagination, page
                    // refreshes, and pure DataTables redraws — the
                    // JS-only flash dies on the first redraw, this
                    // doesn't. Window is 30 min because HR who clicks
                    // Regenerate and steps away for coffee should still
                    // see what they processed when they come back.
                    ->setRowClass(function ($c) {
                        if (!$c->ai_generated_at) return '';
                        try {
                            $age = Carbon::parse($c->ai_generated_at);
                            return $age->gte(now()->subMinutes(30)) ? 'ai-fresh-row' : '';
                        } catch (\Throwable $e) {
                            return '';
                        }
                    })
                    ->make(true);
          }    
          
     }
     

     public function checkCompliance(Request $request){
          $resort = $this->resort;

          // Reset the per-run active-breach tracker. Every breach re-detected
          // below records its key; resolveStaleBreaches() at the end closes any
          // auto-resolvable breach NOT re-detected (i.e. now fixed).
          $this->activeBreachKeys = [];

          $today = Carbon::today()->format('Y-m-d');

          $minWageMVR = 8021; // Minimum wage in MVR
          $minWageUSD = 520; // Minimum wage in MVR
          $notify_person = Employee::where('resort_id', $resort->resort_id)->where('rank','3')->first();
          if (!$notify_person)
          {
               // Fall back to the HR department's HOD/EXCOM — NOT just any
               // rank-2 HOD in the resort, which was leaking Compliance
               // notifications to unrelated departments (e.g. Food & Beverage).
               $notify_person = Common::FindResortHR($resort->resort_id);
          }

          
               // Payroll Service Charges Start
               // Get last month's date range
               $lastMonth = Carbon::now()->subMonth();
               $startOfLastMonth = $lastMonth->copy()->startOfMonth()->format('Y-m-d');
               $endOfLastMonth = $lastMonth->copy()->endOfMonth()->format('Y-m-d');
                  
               // Fetch payroll data for last month
               $payrollData = Payroll::where('resort_id', $resort->resort_id)
                    ->whereBetween('start_date', [$startOfLastMonth, $endOfLastMonth])
                    ->orWhereBetween('end_date', [$startOfLastMonth, $endOfLastMonth])
                    ->first();

               if($payrollData) 
               {
                    $payroll_service_charges = PayrollServiceCharge::where('payroll_id', $payrollData->id)->get();
                    foreach ($payroll_service_charges as $payroll) 
                    {
                         $employee           =  Employee::where('id', $payroll->employee_id)->where('resort_id', $resort->resort_id)->first();
                         $grade              =  Common::getEmpGrade($resort->resort_id, $employee->rank);
                         $resortBenifitsGrid =  ResortBenifitGrid::where('resort_id', $resort->resort_id)->where('emp_grade', $grade)->where('service_charge', '1')->where('status','Active')->first();
                         
                         if($resortBenifitsGrid && $payroll->service_charge_amount > 0) 
                         {
                             
                                   $row = $this->createComplianceOncePerDay([
                                        'resort_id' => $resort->resort_id,
                                        'employee_id' => $employee->id,
                                        'module_name' => 'Payroll Compliance',
                                        'compliance_breached_name' => 'Service Charge Compliance',
                                        'description' => "Employee " . $employee->resortAdmin->full_name . " is eligible for service charge but receiving less than the required amount. Expected: " . $resortBenifitsGrid->service_charge_amount . ", Received: " . $payroll->service_charge_amount,
                                        'reported_on' => Carbon::now(),
                                        'status' => 'Breached'
                                   ]);
                                   if ($row) {
                                   $this->enrichComplianceWithAi($row, [
                                        'grade'           => $grade,
                                        'expected_amount' => (float) $resortBenifitsGrid->service_charge_amount,
                                        'received_amount' => (float) $payroll->service_charge_amount,
                                        'payroll_period'  => $startOfLastMonth . ' to ' . $endOfLastMonth,
                                   ]);
                                   event(new ResortNotificationEvent(Common::nofitication(
                                        $this->resort->resort_id,
                                        10,
                                        'Service Charge Compliance',
                                        "Employee " . $employee->resortAdmin->full_name . " is eligible for service charge but receiving less than the required amount. Expected: " . $resortBenifitsGrid->service_charge_amount . ", Received: " . $payroll->service_charge_amount,
                                        0,
                                        $notify_person->id,
                                        'Service Charge Compliance'
                                   )));
                                   }
                         }
                    }
               }
               // Payroll Service Charges Start





               // Incident Compliance Start
               $incidentData = Incidents::where('resort_id', $resort->resort_id)->where('status','Reported')->get();
               foreach ($incidentData as $incident) 
               {
                    
               }
               

               // Probation Compliance start
               $probationData = Employee::where('resort_id', $resort->resort_id)->where('status', 'Active')->whereIn('probation_status', ['Active','Extended'])->get();
               if($probationData->isEmpty()) 
               {
                    foreach ($probationData as $probation) 
                    {
                         $probationEndDate = Carbon::parse($probation->probation_end_date);
                         $probationStartDate = Carbon::parse($probation->joining_date);

                         // Calculate the difference in months
                         $probationMonths = $probationStartDate->diffInMonths($probationEndDate);

                         // Check if probation period is more than 3 months
                         if ($probationMonths > 3 && $probation->probation_status == 'Active') {
                              $row = $this->createComplianceOncePerDay([
                                   'resort_id' => $resort->resort_id,
                                   'employee_id' => $probation->id,
                                   'module_name' => 'Probation',
                                   'compliance_breached_name' => 'Extended Probation Period',
                                   'description' => "Probation period for " . $probation->resortAdmin->full_name . "(" . $probation->position->position_title . ') is set to ' . $probationMonths . ' months. Reduce to comply with the 3-month maximum',
                                   'reported_on' => Carbon::now(),
                                   'status' => 'Breached'
                              ]);
                              if ($row) {
                              $this->enrichComplianceWithAi($row, [
                                   'position'           => optional($probation->position)->position_title,
                                   'joining_date'       => optional($probation->joining_date)->format('Y-m-d') ?? $probation->joining_date,
                                   'probation_end_date' => optional($probation->probation_end_date)->format('Y-m-d') ?? $probation->probation_end_date,
                                   'probation_months'   => (int) $probationMonths,
                                   'probation_status'   => $probation->probation_status,
                              ]);

                              event(new ResortNotificationEvent(Common::nofitication(
                                   $this->resort->resort_id,
                                   10,
                                   'Extended Probation Period',
                                   "Probation period for " . $probation->resortAdmin->full_name . "(" . $probation->position->position_title . ") is set to " . $probationMonths . " months. Reduce to comply with the 3-month maximum",
                                   0,
                                   $notify_person->id,
                                   'Probation'
                              )));
                              }

                         }
                    }
               }
               // Probation Compliance End

                    // OverTime Agreement Compliance Start
              
               $overtimeData = Employee::where('resort_id', $resort->resort_id)
                         ->where('status', 'Active')
                         ->get();
               if($overtimeData->isNotEmpty())
               {
                    foreach ($overtimeData as $overtime) 
                    {
                         $grade = Common::getEmpGrade($resort->resort_id, $overtime->rank);
                         $resortBenifitsGrid = ResortBenifitGrid::where('resort_id', $resort->resort_id)->where('emp_grade', $grade)->where('status','Active')
                              ->where('overtime', '!=','yes')
                              ->first();

                         $attendance = ParentAttendace::where('resort_id',$resort->resort_id)->where('Emp_id', $overtime->id)
                              ->whereDate('date', $today)
                              ->where('CheckingTime', '!=', null)
                              ->where('status', 'Present')
                              ->where('OverTime', '!=', null)
                              ->first();

                         if ($resortBenifitsGrid && $attendance) {
                              $row = $this->createComplianceOncePerDay([
                                   'resort_id' => $resort->resort_id,
                                   'employee_id' => $overtime->id,
                                   'module_name' => ' Overtime  Agreement',
                                   'compliance_breached_name' => 'Overtime Agreement Lacked',
                                   'description' => "Overtime logged for " . $overtime->resortAdmin->full_name . "(" . $overtime->position->position_title . ') but employment agreement lacks overtime terms. Update contract or adjust hours',
                                   'reported_on' => Carbon::now(),
                                   'status' => 'Breached'
                              ]);
                              if ($row) {
                              $this->enrichComplianceWithAi($row, [
                                   'position'           => optional($overtime->position)->position_title,
                                   'grade'              => $grade,
                                   'overtime_logged_at' => $today,
                              ]);

                              event(new ResortNotificationEvent(Common::nofitication(
                                   $this->resort->resort_id,
                                   10,
                                   'Overtime Agreement Lacked',
                                   "Overtime logged for " . $overtime->resortAdmin->full_name . "(" . $overtime->position->position_title . ") but employment agreement lacks overtime terms. Update contract or adjust hours",
                                   0,
                                   $notify_person->id,
                                   'Overtime Agreement Lacked'
                              )));
                              }
                         }

                    }
               }
                    // OverTime Compliance End



                    // Senior HR and Management start
                    $total_employees = Employee::where('resort_id', $resort->resort_id)->where('status', 'Active')->get();
                    $total_employees_count = $total_employees->count();
                    if($total_employees_count > 50)
                    {
                         // Raw rank=3 excluded HR employees whose actual rank
                         // isn't literally 3 (e.g. HR-department HOD/EXCOM).
                         $seniorHR = Employee::whereIn('id', Common::getResortHrEmployeeIds($resort->resort_id))->first();
                        
                         if ($seniorHR && $seniorHR->nationality != 'Maldivian') {
                              $row = $this->createComplianceOncePerDay([
                                   'resort_id' => $resort->resort_id,
                                   'employee_id' => $seniorHR->id,
                                   'module_name' => 'Senior HR and Management ',
                                   'compliance_breached_name' => 'Senior HR Non-Maldivian',
                                   'description' => "Senior HR and Management position at " . $resort->resort_name . " should be held by a Maldivian. Current  holder is " . $seniorHR->resortAdmin->full_name . "(" . $seniorHR->position->position_title . ')',
                                   'reported_on' => Carbon::now(),
                                   'status' => 'Breached'
                              ]);
                              if ($row) {
                              $this->enrichComplianceWithAi($row, [
                                   'position'       => optional($seniorHR->position)->position_title,
                                   'nationality'    => $seniorHR->nationality,
                                   'rank'           => $seniorHR->rank,
                                   'resort_name'    => $resort->resort_name,
                                   'total_employees'=> $total_employees_count,
                              ]);

                              event(new ResortNotificationEvent(Common::nofitication(
                                   $this->resort->resort_id,
                                   10,
                                   'Senior HR Non-Maldivian',
                                  "Senior HR and Management position at " . $resort->resort_name . " should be held by a Maldivian. Current  holder is " . $seniorHR->resortAdmin->full_name . "(" . $seniorHR->position->position_title . ")",
                                   0,
                                   $notify_person->id,
                                   'Senior HR and Management'
                              )));
                              }
                         }

                         // Management positions should be at least N% Maldivian.
                         // Threshold sourced from Common::getResortLocalRatioTarget,
                         // which prefers the PER-RESORT value from
                         // manningandbudgeting_configfiles.local (set on
                         // /resort/budget/config) and falls back to the
                         // system-wide config. Same helper as the WP
                         // dashboard chip — guaranteed to agree.
                         $localRatioMin = (float) Common::getResortLocalRatioTarget($resort->resort_id);
                         $localRatioFraction = $localRatioMin / 100;
                         $localRatioLabel    = rtrim(rtrim(number_format($localRatioMin, 1), '0'), '.');

                         $managementEmployees = Employee::where('resort_id', $resort->resort_id)
                              ->where('rank',1)
                              ->where('status', 'Active')
                              ->get();

                         $managementCount = $managementEmployees->count();
                         $maldivianCount = $managementEmployees->where('nationality', 'Maldivian')->count();
                         $NonMaldivian = $managementEmployees->where('nationality', '!=', 'Maldivian')->whereIn('rank', ['3','1'])->get();

                         if ($maldivianCount < ($managementCount * $localRatioFraction)) {
                              $breachDesc = "Management positions at " . $resort->resort_name . " should have at least {$localRatioLabel}% Maldivian representation. Current ratio is " . $maldivianCount . "/" . $managementCount;
                              $row = $this->createComplianceOncePerDay([
                                   'resort_id' => $resort->resort_id,
                                   'module_name' => 'Senior HR and Management',
                                   'compliance_breached_name' => 'Management Non-Maldivian',
                                   'description' => $breachDesc,
                                   'reported_on' => Carbon::now(),
                                   'status' => 'Breached'
                              ]);
                              if ($row) {
                              $this->enrichComplianceWithAi($row, [
                                   'resort_name'        => $resort->resort_name,
                                   'management_count'   => $managementCount,
                                   'maldivian_count'    => $maldivianCount,
                                   'required_ratio_pct' => $localRatioMin,
                                   'current_ratio_pct'  => $managementCount > 0 ? round(($maldivianCount / $managementCount) * 100, 1) : 0,
                              ]);

                              event(new ResortNotificationEvent(Common::nofitication(
                                   $this->resort->resort_id,
                                   10,
                                   'Management Non-Maldivian',
                                   $breachDesc,
                                   0,
                                   $notify_person->id,
                                   'Senior HR and Management'
                              )));
                              }
                         }

                         foreach ($NonMaldivian as $nonMaldivian) {
                              $row = $this->createComplianceOncePerDay([
                                   'resort_id' => $resort->resort_id,
                                   'employee_id' => $nonMaldivian->id,
                                   'module_name' => 'Senior HR and Management',
                                   'compliance_breached_name' => 'Management Non-Maldivian',
                                   'description' => "Management position at " . $resort->resort_name .
                                   " should be held by a Maldivian. Current holder is " . $nonMaldivian->resortAdmin->full_name . "(" . $nonMaldivian->position->position_title . ')',
                                   'reported_on' => Carbon::now(),
                                   'status' => 'Breached'
                              ]);
                              if ($row) {
                              $this->enrichComplianceWithAi($row, [
                                   'position'      => optional($nonMaldivian->position)->position_title,
                                   'nationality'   => $nonMaldivian->nationality,
                                   'rank'          => $nonMaldivian->rank,
                                   'resort_name'   => $resort->resort_name,
                              ]);

                              event(new ResortNotificationEvent(Common::nofitication(
                                   $this->resort->resort_id,
                                   10,
                                   'Management Non-Maldivian',
                                   "Management position at " . $resort->resort_name .
                                   " should be held by a Maldivian. Current holder is " . $nonMaldivian->resortAdmin->full_name . "(" . $nonMaldivian->position->position_title . ")",
                                   0,
                                   $notify_person->id,
                                   'Senior HR and Management'
                              )));
                              }
                         }
                    }
                    // Senior HR and Management end
                    

                    // Pension Compliance start
               $payrollData = Payroll::where('resort_id', $resort->resort_id)->whereBetween('start_date', [$startOfLastMonth, $endOfLastMonth])->orWhereBetween('end_date', [$startOfLastMonth, $endOfLastMonth])->first();
               if($payrollData)
               {
                    $payrollDeductions = PayrollDeduction::where('payroll_id', $payrollData->id)->get();
                    foreach(  $payrollDeductions as $deduction) 
                    {
                         $employee = Employee::where('id', $deduction->employee_id)->where('resort_id', $resort->resort_id)->first();
                         if ($employee) {
                              $grade = Common::getEmpGrade($resort->resort_id, $employee->rank);
                              // Calculate age from date of birth
                              if ($employee->dob) {
                                         try {
                                            $age = Carbon::createFromFormat('d/m/Y', $employee->dob)->age;
                                        } catch (\Exception $e) {
                                            // If parsing fails, try another common format
                                            try {
                                                $age = Carbon::parse($employee->dob)->age;
                                            } catch (\Exception $e) {
                                                $age = 0; // Default value if parsing fails
                                            }
                                        }
                                        
                                        // Check if employee is eligible for pension (between 16 and 65 years)
                                        if ($age >= 16 && $age <= 65) {
                                             
                                             $basicSalary = $employee->basic_salary;
                                             $sevenPercentOfSalary = $basicSalary * 0.07;
                                             
                                             if ($deduction->pension < $sevenPercentOfSalary) {
                                                  $row = $this->createComplianceOncePerDay([
                                                       'resort_id' => $resort->resort_id,
                                                       'employee_id' => $employee->id,
                                                       'module_name' => 'Pension Compliance',
                                                       'compliance_breached_name' => 'Pension Deduction Below 7%',
                                                       'description' => "Employee " . $employee->resortAdmin->full_name . " is eligible for pension (age " . $age . " years). Pension deduction is below the required 7% of basic salary (" . $basicSalary . ").",
                                                       'reported_on' => Carbon::now(),
                                                       'status' => 'Breached'
                                                  ]);
                                                  if ($row) {
                                                  $this->enrichComplianceWithAi($row, [
                                                       'age'                => $age,
                                                       'basic_salary'       => (float) $basicSalary,
                                                       'required_pension'   => (float) round($sevenPercentOfSalary, 2),
                                                       'actual_pension'     => (float) $deduction->pension,
                                                       'payroll_period'     => $startOfLastMonth . ' to ' . $endOfLastMonth,
                                                  ]);

                                                  event(new ResortNotificationEvent(Common::nofitication(
                                                       $this->resort->resort_id,
                                                       10,
                                                       'Pension Compliance',
                                                       "Employee " . $employee->resortAdmin->full_name . " is eligible for pension (age " . $age . " years). Pension deduction is below the required 7% of basic salary (" . $basicSalary . ").",
                                                       0,
                                                       $notify_person->id,
                                                       'Pension Deduction Below 7%'
                                                  )));
                                                  }
                                             }
                                        }
                              }
                         }
                    }
               }
                    // Pension Compliance end


               $ManningandbudgetingConfigfiles = ManningandbudgetingConfigfiles::where('resort_id', $resort->resort_id)->first();
               if (!$ManningandbudgetingConfigfiles) {
                    return response()->json([
                         'status' => 'error',
                         'message' => 'Workforce Planning is not configured for this resort.'
                    ]);
               }

               $xpat = $ManningandbudgetingConfigfiles->xpat;
               $local = $ManningandbudgetingConfigfiles->local;

               // Get counts
               $totalEmployees = Employee::where('resort_id', $resort->resort_id)->count();
               $expatCount = Employee::where('resort_id', $resort->resort_id)
                    ->where('nationality', '!=', 'Maldivian')
                    ->count();

               $localCount = Employee::where('resort_id', $resort->resort_id)
                    ->where('nationality', 'Maldivian')
                    ->count();

               $compliance = null;
                  // Expat-Local Ratio compliance check
                  if ($totalEmployees > 0 && $xpat > 0 && $local > 0) 
                  {
                         $total_ratio = $xpat + $local;
                         $expected_expat = ceil($totalEmployees * ($xpat / $total_ratio));
                         $expected_local = ceil($totalEmployees * ($local / $total_ratio));
                         
                         // Check if the actual counts violate the expected ratio
                         if ($expatCount > $expected_expat || $localCount < $expected_local) {
                               // Send notification to resort admin
                               event(new ResortNotificationEvent(Common::nofitication(
                                     $this->resort->resort_id,
                                     10,
                                     'Workforce Planning Expat-Local Ratio Compliance Breached',
                                     "Expat count ({$expatCount}) exceeds expected ({$expected_expat}) or Local count ({$localCount}) is below expected ({$expected_local}).",
                                     0,
                                     $notify_person->id,
                                     'Workforce Planning'
                               )));

                               $compliance = Compliance::firstOrCreate(
                                     [
                                          'resort_id' => $resort->resort_id,
                                          'employee_id' => null,
                                          'module_name' => 'Workforce Planning',
                                          'compliance_breached_name' => 'Expat-Local Ratio',
                                     ],
                                     [
                                          'description' => "Expat count ({$expatCount}) exceeds expected ({$expected_expat}) or Local count ({$localCount}) is below expected ({$expected_local})",
                                          'reported_on' => Carbon::now(),
                                          'status' => 'Breached',
                                     ]
                               );
                         }
                  }
               // Expat-Local Ratio compliance End
               // Minumum wage compliance check
               $minWageMVR = 8021; // Minimum wage in MVR
               $minWageUSD = 520; // Minimum wage in MVR

               $employeesBelowMinWageInMVR = Employee::where('resort_id', $resort->resort_id)
                    ->where('basic_salary', '<', $minWageMVR)
                    ->where('basic_salary_currency', 'MVR')
                    ->get();

               $employeesBelowMinWageInUSD = Employee::where('resort_id', $resort->resort_id)
                    ->where('basic_salary', '<', $minWageUSD) // Assuming you have a conversion rate set in your config
                    ->where('basic_salary_currency', 'USD')
                    ->get();
               $employeesBelowMinWage = $employeesBelowMinWageInMVR->merge($employeesBelowMinWageInUSD);
               if ($employeesBelowMinWage->isNotEmpty()) {
                    foreach ($employeesBelowMinWage as $employee)
                    {
                         $this->markBreachActive([
                              'resort_id' => $resort->resort_id,
                              'employee_id' => $employee->id,
                              'module_name' => 'Workforce Planning',
                              'compliance_breached_name' => 'Minimum Wage',
                         ]);
                         $compliance = Compliance::firstOrCreate(
                              [
                                   'resort_id' => $resort->resort_id,
                                   'employee_id' => $employee->id,
                                   'module_name' => 'Workforce Planning',
                                   'compliance_breached_name' => 'Minimum Wage',
                              ],
                              [
                                   'description' => "Employee {$employee->resortAdmin->full_name} has a basic salary below the minimum wage.",
                                   'reported_on' => Carbon::now(),
                                   'status' => 'Breached',
                              ]
                         );
                    }
               }
               // Minumum Wage Compliance End


               // . Time & Attendance
               // Time & Attendance Compliance Start
                
               $employeeIds = Employee::where('resort_id', $resort->resort_id)->where('status', 'active')->pluck('id')->toArray();


               // Check for employees working without breaks
               $attendacedata = ParentAttendace::where('resort_id',$resort->resort_id)->whereIn('Emp_id', $employeeIds)
                    ->whereDate('date', $today)
                    ->where('CheckingTime', '!=', null)
                    ->where('status', 'Present')
                    ->get();

               // List of employees who didn't take a break in 5+ hours
               $employeesWithoutBreak = [];

               foreach ($attendacedata as $attendance) 
               {
                    // Get employee check in time
                    $checkInTime = Carbon::parse($attendance->CheckingTime);
                    
                    $breaks = BreakAttendaces::where('Parent_attd_id', $attendance->id)
                              ->get();
                    
                    if ($breaks->isEmpty()) {
                              $hoursWorked = $checkInTime->diffInHours(Carbon::now());
                              if ($hoursWorked >= 5) {
                                   $employee = Employee::find($attendance->Emp_id);
                                   $employeesWithoutBreak[] = [
                                        'resort_id' => $resort->resort_id,
                                        'employee_id' => $employee->id,
                                        'module_name' => 'Time & Attendance',
                                        'compliance_breached_name' => 'Without Mandatory Break',
                                        'description' => "Employee {$employee->resortAdmin->full_name} ({$employee->position->position_title}) worked {$hoursWorked} hours without a break. Review schedule to ensure adherence to labor laws.",
                                        'reported_on' => Carbon::now(),
                                        'status' => 'Breached'
                                   ];
                              }
                    } else {
                              $lastBreakTime = $checkInTime;
                              $hadLongPeriod = false;
                              
                              foreach ($breaks as $break) {
                                   $breakStartTime = Carbon::parse($break->start_time);
                                   $timeDifference = $lastBreakTime->diffInHours($breakStartTime);
                                   
                                   if ($timeDifference >= 5) {
                                        $hadLongPeriod = true;
                                        $employee = Employee::find($attendance->Emp_id);
                                        $employeesWithoutBreak[] = [
                                             'resort_id' => $resort->resort_id,
                                             'employee_id' => $employee->id,
                                             'module_name' => 'Time & Attendance',
                                             'compliance_breached_name' => 'Without Mandatory Break',
                                             'description' => "Employee {$employee->resortAdmin->full_name} ({$employee->position->position_title}) worked {$timeDifference} hours without a break. Review schedule to ensure adherence to labor laws.",
                                             'reported_on' => Carbon::now(),
                                             'status' => 'Breached'
                                        ];
                                             
                                   }
                                   
                                   $lastBreakTime = Carbon::parse($break->end_time);
                              }

                              // Check if there's been 5+ hours since last break
                              $timeSinceLastBreak = $lastBreakTime->diffInHours(Carbon::now());
                              if ($timeSinceLastBreak >= 5) {
                                   $employee = Employee::find($attendance->Emp_id);
                                   
                                   $employeesWithoutBreak[] = [
                                        'resort_id' => $resort->resort_id,
                                        'employee_id' => $employee->id,
                                        'module_name' => 'Time & Attendance',
                                        'compliance_breached_name' => 'Without Mandatory Break',
                                        'description' => "Employee {$employee->resortAdmin->full_name} ({$employee->position->position_title}) worked {$timeSinceLastBreak} hours without a break. Review schedule to ensure adherence to labor laws.",
                                        'reported_on' => Carbon::now(),
                                        'status' => 'Breached'
                                   ];
                                   
                              }
                    }
               }

               // Insert all compliance breaches in one go
               if (!empty($employeesWithoutBreak)) 
               {
                    $this->insertComplianceOncePerDay($employeesWithoutBreak);
               }

               // Time & Attendance Compliance End

               // Weekly Working Hours Compliance Start

               // Check for employees scheduled to work more than 48 hours in a week (Monday to Saturday)
               $startOfWeek = Carbon::now()->subWeek()->startOfWeek();
               $endOfWeek =  Carbon::now()->copy()->endOfWeek();
               
               $overworkedEmployees = [];
               // Get only the employee at index 3 (4th element) if it exists
               foreach($employeeIds as $empId)
               {
                    $weeklyHours = ParentAttendace::where('resort_id', $resort->resort_id)->where('Emp_id', $empId)
                         ->where('CheckingTime', '!=', null)
                              ->whereBetween('date', [$startOfWeek->format('Y-m-d'), $endOfWeek->format('Y-m-d')])
                              ->where('Status', 'Present')
                              ->get();
                              
                    $totalHoursWorked = 0;
                    foreach ($weeklyHours as $day) {
                         if (!empty($day->CheckingTime) && !empty($day->CheckingOutTime)) {
                              $checkIn = Carbon::parse($day->CheckingTime);
                              $checkOut = Carbon::parse($day->CheckingOutTime);
                              $hoursWorked = $checkIn->diffInHours($checkOut);
                              $totalHoursWorked += $hoursWorked;
                         }
                    }
                    
                    if ($totalHoursWorked > 48) {
                         $employee = Employee::find($empId);
                         $overworkedEmployees[] = [
                              'resort_id' => $resort->resort_id,
                              'employee_id' => $employee->id,
                              'module_name' => 'Time & Attendance',
                              'compliance_breached_name' => 'Weekly Working Hours Limit',
                              'description' => "Employee {$employee->resortAdmin->full_name} ({$employee->position->position_title}) is scheduled for {$totalHoursWorked} hours this week. Adjust schedule to comply with the 48-hour weekly limit.",
                              'reported_on' => Carbon::now(),
                              'status' => 'Breached'
                         ];
                    }
               }
               
               if (!empty($overworkedEmployees)) {
                    $this->insertComplianceOncePerDay($overworkedEmployees);
               }

               // Weekly Working Hours Compliance End


               // vacancy compliance start
               $vacancies = Vacancies::where('resort_id', $resort->resort_id)->where('status', 'Active')->get();
               foreach ($vacancies as $vacancy) 
               {
                    $base_salary = $vacancy->budgeted_salary;    
                    $propsed_salary = $vacancy->propsed_salary;
                    // Usd
                    if($propsed_salary > $base_salary){
                         // Per-vacancy breach: there is no vacancy_id column, so the description
                         // (which embeds the position title) is what distinguishes one vacancy
                         // from another. Keep it in the match key; only reported_on must be excluded.
                         $compliance = Compliance::firstOrCreate(
                              [
                                   'resort_id' => $resort->resort_id,
                                   'employee_id' => null,
                                   'module_name' => 'Talent Acquisition',
                                   'compliance_breached_name' => 'Salary Compliance',
                                   'description' => "Vacancy {$vacancy->Getposition->position_title} has a proposed salary ({$propsed_salary}) exceeding the budgeted salary ({$base_salary}).",
                              ],
                              [
                                   'reported_on' => Carbon::now(),
                                   'status' => 'Breached',
                              ]
                         );
                    }
                    if($base_salary > $minWageUSD){
                         // Per-vacancy breach (no vacancy_id column) — keep description in the match key.
                         $compliance = Compliance::firstOrCreate(
                              [
                                   'resort_id' => $resort->resort_id,
                                   'employee_id' => null,
                                   'module_name' => 'Talent Acquisition',
                                   'compliance_breached_name' => 'Minimum Wage Compliance',
                                   'description' => "Vacancy {$vacancy->Getposition->position_title} has a budgeted salary ({$base_salary}) below the minimum wage.",
                              ],
                              [
                                   'reported_on' => Carbon::now(),
                                   'status' => 'Breached',
                              ]
                         );
                    }
               }
               // Vacancy compliance end




          // Visa Module   
               $flag = 'all';
               $filterStart = Carbon::now()->startOfMonth();
               $filterEnd = Carbon::now()->endOfMonth();
               $overworkedEmployeesVisa = [];
               $Employee = Employee::with(['resortAdmin','position', 'department','VisaRenewal.VisaChild','WorkPermitMedicalRenewal.WorkPermitMedicalRenewalChild','WorkPermit','EmployeeInsurance.InsuranceChild','QuotaSlotRenewal'])->where("nationality", '!=', "Maldivian")->where('resort_id', $this->resort->resort_id)->get()
               ->map(function ($employee) use (&$overworkedEmployeesVisa) 
               {

                    $employee->Emp_name        = $employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name;
                    $employee->Emp_id          = $employee->Emp_id;
                    $employee->Department_name = $employee->department->name;
                    $employee->Position_name   = $employee->position->position_title;
                    $employee->ProfilePic      = Common::getResortUserPicture($employee->resortAdmin->id);

                    $newVisaMArray = [];
                    $ExpiryDocument = [];
                    // Visa
                    $visa = $employee->VisaRenewal;
                    if ($visa && $this->isExpired($visa->end_date)) {
                         $employee->VisaExpiryDate = $this->getFormattedExpiryStatus($visa->end_date);
                         $employee->VisaExpiryExpiryAmt = $visa->Amt;
                         $newVisaMArray[] = [
                              'type' => 'Visa',
                              'ExpiryDate' => $this->getFormattedExpiryStatus($visa->end_date),
                              'amount' => $visa->Amt
                         ];
                    }

                    // Insurance
                    $insurance = $employee->EmployeeInsurance()
                         ->where('employee_id', $employee->id)
                         ->where('resort_id', $this->resort->resort_id)
                         ->orderBy('id', 'desc')
                         ->first();

                    if ($insurance && $this->isExpired($insurance->insurance_end_date)) {
                         $employee->InsuranceExpiryDate = $this->getFormattedExpiryStatus($insurance->insurance_end_date);
                         $employee->Premium = $insurance->Premium;
                         $newVisaMArray[] = [
                              'type' => 'Medical International Insurance',
                              'ExpiryDate' => $this->getFormattedExpiryStatus($insurance->insurance_end_date),
                              'amount' => $insurance->Premium
                         ];
                    }

                    // Work Permit
                    $currentWP = $employee->WorkPermit->where('Status', 'Unpaid')->filter(fn($item) => $this->isExpired($item->Due_Date))->sortByDesc('id')->first();
                    if($currentWP) 
                    {
                         $employee->WorkPermitExpiryDate = $this->getFormattedExpiryStatus($currentWP->Due_Date);
                         $employee->WorkPermitAmt = number_format($currentWP->Amt, 2);
                         $newVisaMArray[] = [ 'type' => 'Work Permit','ExpiryDate' => $this->getFormattedExpiryStatus($currentWP->Due_Date),'amount' => number_format($currentWP->Amt, 2)];
                    }
                    // Medical Test
                    $med = $employee->WorkPermitMedicalRenewal;
                    if ($med && $this->isExpired($med->end_date)) 
                    {
                         $employee->WorkPermitMedicalPermitExpiryDate = $this->getFormattedExpiryStatus($med->end_date);
                         $employee->WorkPermitMedicalPermitAmt = number_format($med->Amt, 2);
                         $newVisaMArray[] =  [
                                                  'type' => 'Work Permit Medical Test Fee',
                                                  'ExpiryDate' => $this->getFormattedExpiryStatus($med->end_date),
                                                  'amount' => number_format($med->Amt,2)
                                             ];
                    }
                    // Quota Slot
                    $currentQuota = $employee->QuotaSlotRenewal->filter(fn($item) => $this->isExpired($item->Due_Date))->where('Status', 'Unpaid')->first();
                         if ($currentQuota) 
                         {
                              $employee->QuotaSlotAmtForThisMonth = $this->getFormattedExpiryStatus($currentQuota->Due_Date);
                              $employee->QuotaSlotAmtForThisMonthAmt = $currentQuota->Amt;
                              $newVisaMArray[] = [
                                   'type' => 'Quota Slot',
                                   'ExpiryDate' => $this->getFormattedExpiryStatus($currentQuota->Due_Date),
                                   'amount' => number_format($currentQuota->Amt, 2)
                              ];
                         }
                         if (!empty($newVisaMArray)) 
                         {
                              $ExpiryDocument[$employee->Emp_name] = $newVisaMArray;
                              if (!empty($ExpiryDocument))
                              {    
                                   $overworkedEmployeesVisa[] = [
                                        'resort_id' => $this->resort->resort_id,
                                        'employee_id' => $employee->id,
                                        'module_name' => 'Visa',
                                        'compliance_breached_name' => 'Expired Visa Documents',
                                        'description' => $this->stringifyExpiryDocument($ExpiryDocument),
                                        'reported_on' => Carbon::now(),
                                        'status' => 'Breached'
                                   ];
                              }
                         }
                    return !empty($newVisaMArray) ? $employee : null;
               })->filter();
               if(!empty($overworkedEmployeesVisa))
               {
                    $this->insertComplianceOncePerDay($overworkedEmployeesVisa);
               }
          // VisaEnd

          // Incident Compliance Start
               $fourtieenEightHoursAgo = Carbon::now()->subHours(48);
               $Incidentemployees= array();
               $incidents = incidents::with(['Investigation','victim.resortAdmin','victim.position','victim.department'])->where('priority', 'high')
                                   ->where('severity', 'Severe')
                                   ->where('created_at', '<=', $fourtieenEightHoursAgo)
                                   ->whereHas( 'Investigation', function ($query) use ($fourtieenEightHoursAgo) {
                                        $query->where('Ministry_notified','No');
                                   })
                                   ->get()->map(function ($incident) use(&$Incidentemployees) 
                                   {
                                        $incident->Fullname = $incident->victim->resortAdmin->first_name . ' ' . $incident->victim->resortAdmin->last_name;
                                        $incident->poistion = $incident->victim->position->position_title;
                                        $incident->department = $incident->victim->department->name;
                                             $Incidentemployees[] = [
                                                            'resort_id' =>$incident->resort_id,
                                                            'employee_id' => $incident->victims,
                                                            'module_name' => 'Incident Compliance',
                                                            'compliance_breached_name' => 'Ministry is not Notified yet',
                                                            'description' => "High severity incident for {$incident->Fullname} was created more than 48 hours ago and ministry has not been notified yet.",
                                                            'reported_on' => Carbon::now(),
                                                            'status' => 'Breached'
                                                       ];
                                        return $incident;
                                   });

                    if(!empty($Incidentemployees))
                    {
                         $this->insertComplianceOncePerDay($Incidentemployees);
                    }

          // End Incident Compliance Start
                    
          // On Bording
                    

          // Probation Compliance
               $EmployeeOnBording = array();
               $gracePeriods = [30, 90]; // Different grace periods to check

               foreach ($gracePeriods as $gracePeriod) 
               {
                    // Calculate cutoff date for current grace period
                    $cutoffDate = Carbon::now()->subDays($gracePeriod)->format('Y-m-d');
                    
                    // Build base query
                    $query = Employee::with(['resortAdmin', 'OnboardingAcknowledgements', 'position', 'department'])
                         ->where('resort_id', $this->resort->resort_id)
                         ->where('status', 'Active')
                         ->whereHas('OnboardingAcknowledgements', function ($query) {
                              $query->where('acknowledgement_type', 'Job Description Received?')
                                   ->where('status', 'no');
                         })
                         ->whereDate('joining_date', '<=', $cutoffDate);
                    
                    // Apply probation status condition based on grace period
                    if ($gracePeriod == 30) {
                         // For 30 days: check employees with active probation
                         $query->where('probation_status', 'Active');
                    } elseif ($gracePeriod == 90) {
                         // For 90 days: check employees with inactive probation
                         $query->where('probation_status', '!=', 'Active');
                    }
                    
                    // Execute query and process results
                    $employees = $query->get()->map(function ($employee) use (&$EmployeeOnBording, $gracePeriod) {
                         $employee->Emp_name = $employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name;
                         $employee->Emp_id = $employee->Emp_id;
                         $employee->Department_name = $employee->department->name;
                         $employee->Position_name = $employee->position->position_title;
                         $joiningDate = Carbon::parse($employee->joining_date)->format('d M Y');
                         $daysSinceJoining = Carbon::parse($employee->joining_date)->diffInDays(Carbon::now());
                         
                         // Create compliance record with grace period context
                         $probationStatus = $employee->probation_status == 'Active' ? 'Active Probation' : 'Inactive Probation';
                         $complianceDescription = "Employee {$employee->Emp_name} ({$employee->Position_name}) joined on {$joiningDate} ({$daysSinceJoining} days ago) and has not received the job description yet. Grace period: {$gracePeriod} days ({$probationStatus}).";
                         
                         $EmployeeOnBording[] = [
                              'resort_id' => $this->resort->resort_id,
                              'employee_id' => $employee->id,
                              'module_name' => 'On Boarding',
                              'compliance_breached_name' => 'Job Description Not Received',
                              'description' => $complianceDescription,
                              'reported_on' => Carbon::now(),
                              'status' => 'Breached',
                         ];
                         
                         return $employee;
                    });
               }
           


               // Insert all compliance records at once
               if (!empty($EmployeeOnBording)) 
               {                                                                           
                    $this->insertComplianceOncePerDay($EmployeeOnBording);
               }

          //End of On Bording
               
          // Start EmployeePromotion
               $EmployeePromotion = [];
               $query = Employee::with(['resortAdmin', 'promotions.newPosition', 'position', 'department'])
                         ->where('resort_id', $this->resort->resort_id)
                         ->where('status', 'Active')
                         ->whereHas('promotions', function ($query) {
                              $query->where('Jd_id',"=", null); // Check if Job Description is not received
                         })
                         ->get()
                         ->map(function ($employee) use (&$EmployeePromotion) {
                              $employee->Emp_name = $employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name;
                              $employee->Emp_id = $employee->Emp_id;
                              $employee->Department_name = $employee->department->name;
                              $employee->Position_name = $employee->position->position_title;

                              // Check if the employee has a promotion with a Job Description
                              if ($employee->promotions && $employee->promotions->count() > 0) 
                              {
                                   $employee->promotions->each(function ($promotion) use (&$EmployeePromotion, $employee) {
                                        if (!is_null($promotion->Jd_id)) {
                                             return; // already linked to a JD template
                                        }

                                        // Re-sync: Jd_id is written once at promotion creation
                                        // (PromotionController) and never updated, so a JD template
                                        // created for the position AFTER the promotion leaves this
                                        // NULL forever. If a template now exists for the new
                                        // position, back-fill it — the breach is then not raised
                                        // below and the auto-resolve sweep closes any open row.
                                        $jdTemplate = JobDescription::where('Resort_id', $this->resort->resort_id)
                                             ->where('Position_id', $promotion->new_position_id)
                                             ->first();
                                        if ($jdTemplate) {
                                             $promotion->Jd_id = $jdTemplate->id;
                                             $promotion->save();
                                             return; // JD template exists → not a breach
                                        }

                                        // No JD template exists for the new position → genuine breach.
                                        $employee->PromotionDate = Carbon::parse($promotion->created_at)->format('d M Y');
                                        $employee->PromotionPosition = $promotion->newPosition->position_title;
                                        $EmployeePromotion[] =
                                        [
                                             'resort_id' => $this->resort->resort_id,
                                             'employee_id' => $employee->id,
                                             'module_name' => 'Employee Promotion',
                                             'compliance_breached_name' => 'Job Description Not Received',
                                             'description' => "Employee {$employee->Emp_name} ({$employee->Position_name}) was promoted to {$employee->PromotionPosition} on {$employee->PromotionDate} but has not received the job description.",
                                             'reported_on' => Carbon::now(),
                                             'status' => 'Breached',
                                        ];
                                   });
                                 
                                 
                                   
                              }
                              return $employee;
                         });
    
                          if (!empty($EmployeePromotion)) 
                         {                                                                           
                              $this->insertComplianceOncePerDay($EmployeePromotion);
                         }



          
          
          // People Module Start
          
          $ResortSiteSettings = ResortSiteSettings::where('resort_id', $this->resort->resort_id)->first();
          // Missing resort scope — every other Employee::with(...) block in
          // this file has ->where('resort_id', $this->resort->resort_id),
          // this one didn't, so every resort's compliance run pulled EVERY
          // employee in the whole database and sent its own HR TIN/salary
          // notifications naming employees who belong to OTHER resorts.
          $employee = Employee::with(['resortAdmin','position','department','division','section','education','experiance','allowance','language','sosTeams','document','bankDetails'])
               ->where('resort_id', $this->resort->resort_id)
               ->get()
               ->map(function ($employee) use ($ResortSiteSettings) 
               {
                    $employee->Emp_name = $employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name;
                    $employee->Emp_id = $employee->Emp_id;
                    $employee->Department_name = $employee->department->name;
                    $employee->Position_name = $employee->position->position_title;
                    

                     $conversionRate = $ResortSiteSettings->DollertoMVR;
                         $basicMvr = $employee->basic_salary_currency === 'USD' ? $employee->basic_salary * $conversionRate : $employee->basic_salary;
                         $totalAllowanceMvr = 0;
                         foreach ($employee->allowance as $allowance) 
                         {
                              $amt = $allowance->amount ?? 0;
                              $unit = $allowance->amount_unit ?? 'USD';
                              $totalAllowanceMvr += $unit === 'USD' ? ($amt * $conversionRate) : $amt;
                         }
                    $totalMonthlyEarningMvr = $basicMvr + $totalAllowanceMvr;
                    $tin = $employee->tin ?? null;

                    $notify_person = Employee::where('resort_id', $this->resort->resort_id)->where('rank','3')->first();
                    if (!$notify_person)
                    {
                         // Fall back to the HR department's HOD/EXCOM, not any rank-2 HOD resort-wide.
                         $notify_person = Common::FindResortHR($this->resort->resort_id);
                    }
                    if($totalMonthlyEarningMvr >= 30000 && !$tin)
                    {
                         event(new ResortNotificationEvent(Common::nofitication(
                              $this->resort->resort_id,
                              10,
                              'TIN Required for Employee',
                              "{$employee->Emp_name} ({$employee->Emp_id} - {$employee->Position_name}) (RSWT: MVR {$totalMonthlyEarningMvr}/month) not registered. Submit MIRA 118 form.",
                              0,
                              $notify_person->id,
                              'People Management (TIN Requirement)'
                         )));
                         $this->markBreachActive([
                              'resort_id' => $this->resort->resort_id,
                              'employee_id' => $employee->id,
                              'module_name' => 'People Management',
                              'compliance_breached_name' => 'TIN Requirement',
                         ]);
                         Compliance::firstOrCreate(
                              [
                                   'resort_id' => $this->resort->resort_id,
                                   'employee_id' => $employee->id,
                                   'module_name' => 'People Management',
                                   'compliance_breached_name' => 'TIN Requirement',
                              ],
                              [
                                   'description' => "{$employee->Emp_name} ({$employee->Emp_id} - {$employee->Position_name}) (RSWT: MVR {$totalMonthlyEarningMvr}/month) not registered. Submit MIRA 118 form.",
                                   'reported_on' => Carbon::now(),
                                   'status' => 'Breached',
                              ]
                         );
                    }
                    return $employee;
               });

            
          // End Of People Module


          // Over Time check Employee eligible to do over time or not
          
               $employee = Employee::with(['resortAdmin','position','department','EmployeeAttandance'])->where('resort_id', $this->resort->resort_id)
               ->where('status', 'active')    
               ->whereHas('resortAdmin', function ($query) {
                    $query->where('status', 'Active');
               }) 
               ->get()
               ->map(function ($employee) 
               {
                    $employee->Emp_name = $employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name;
                    $employee->Emp_id = $employee->Emp_id;
                    $employee->Department_name = $employee->department->name;
                    $employee->Position_name = $employee->position->position_title;

                    // Overtime eligibility is an employee-level fact, so record at most one
                    // breach per employee regardless of how many approved-OT attendance rows exist.
                    $hasApprovedOvertime = $employee->EmployeeAttandance
                         ->contains(fn ($attendance) => $attendance->OTStatus == "Approved");

                    if ($hasApprovedOvertime && $employee->entitled_overtime == "no") {
                         $this->markBreachActive([
                              'resort_id' => $this->resort->resort_id,
                              'employee_id' => $employee->id,
                              'module_name' => 'Time and Attendance',
                              'compliance_breached_name' => 'Over Time Not Eligibile',
                         ]);
                         Compliance::firstOrCreate(
                              [
                                   'resort_id' => $this->resort->resort_id,
                                   'employee_id' => $employee->id,
                                   'module_name' => 'Time and Attendance',
                                   'compliance_breached_name' => 'Over Time Not Eligibile',
                              ],
                              [
                                   'description' => "{$employee->Emp_name} ({$employee->Emp_id} - {$employee->Position_name}) is not eligible for overtime.",
                                   'reported_on' => Carbon::now(),
                                   'status' => 'Compliant',
                              ]
                         );
                    }
                    // Check if the employee is eligible for overtime
                  
                    return $employee;
               });

               // Extra leave day if a public holiday falls during annual leave
               $employees     =    Employee::with(['EmployeeLeave'])
                                        ->where('resort_id', $this->resort->resort_id)
                                        ->where('status', 'Active')
                                        ->get()->map(function ($employee) {
                                             $complianceRecords       =    [];
                                             
                                             // Process each leave for this employee
                                             foreach ($employee->EmployeeLeave as $leave) {
                                                  $startDate          =    Carbon::parse($leave->from_date);
                                                  $endDate            =    Carbon::parse($leave->to_date);
                                                  
                                                  // Get holidays within this leave period
                                                  $holidays           =    ResortHoliday::select([
                                                                                'resortholidays.id',
                                                                                'resortholidays.PublicHolidaydate as date',
                                                                                'resortholidays.PublicHolidayName as title',
                                                                           ])
                                                                           ->where('resort_id', $this->resort->resort_id)
                                                                           ->whereRaw("DATE(PublicHolidaydate) BETWEEN ? AND ?", [
                                                                                $startDate->format('Y-m-d'),
                                                                                $endDate->format('Y-m-d')
                                                                           ])
                                                                           ->get();
                                                  
                                                  // Create compliance records for overlapping holidays
                                                  foreach ($holidays as $holiday)    {
                                                       $complianceRecords[]               =    [
                                                            'resort_id'                   =>   $this->resort->resort_id,
                                                            'employee_id'                 =>   $employee->id,
                                                            'module_name'                 =>   'Leave',
                                                            'compliance_breached_name'    =>   'Leave taken on holiday',
                                                            'description'                 =>   'In leave request ' . $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d') . ', the ' . $holiday->title . ' on ' . $holiday->date . ' has been included in the leave.',
                                                            'reported_on'                 =>   Carbon::now(),
                                                            'status'                      =>   'Breached',
                                                       ];
                                                  }
                                             }
                                             
                                             // Bulk insert compliance records if any
                                             if (!empty($complianceRecords)) {
                                                  $this->insertComplianceOncePerDay($complianceRecords);
                                             }
                                             
                                             return $employee;
                                        });

               // Check for overlap with leave or notice periods.
                $employees     =    Employee::with(['EmployeeResignation','EmployeeLeave'])
                                        ->where('resort_id', $this->resort->resort_id)
                                        ->where('status', 'Active')
                                       ->whereHas('EmployeeResignation', function($query) {
                                             $query->where('status', 'Approved');
                                        })->get()->map(function ($employee) {
                                             $complianceRecords                      =    [];
                                             
                                             // Process each resignation for this employee
                                             foreach ($employee->EmployeeResignation as $resignation) {

                                                  $resignationDate    =    Carbon::parse($resignation->resignation_date);
                                                  $lastWorkingDay     =    Carbon::parse($resignation->last_working_day);

                                                  foreach ($employee->EmployeeLeave as $leave) {
                                                       $leaveStart    =    Carbon::parse($leave->from_date);
                                                       $leaveEnd      =    Carbon::parse($leave->to_date);
                                                       if (($leaveStart->between($resignationDate, $lastWorkingDay) || $leaveEnd->between($resignationDate, $lastWorkingDay) || ($leaveStart->lte($resignationDate) && $leaveEnd->gte($lastWorkingDay)))) {
                                                            $complianceRecords[]               =    [
                                                                 'resort_id'                   =>   $this->resort->resort_id,
                                                                 'employee_id'                 =>   $employee->id,
                                                                 'module_name'                 =>   'Resignation',
                                                                 'compliance_breached_name'    =>   'Leave taken on notice period',
                                                                 'description'                 =>   'In leave request ' . $leaveStart->format('Y-m-d') . ' to ' . $leaveEnd->format('Y-m-d') . ', the employee has taken leave during their notice period (resignation date: ' . $resignationDate->format('Y-m-d') . ', last working day: ' . $lastWorkingDay->format('Y-m-d') . ').',
                                                                 'reported_on'                 =>   Carbon::now(),
                                                                 'status'                      =>   'Breached',
                                                            ];
                                                       }
                                                  }
                                             }
                                           
                                           // Bulk insert compliance records if any
                                            if (!empty($complianceRecords)) {
                                                  $this->insertComplianceOncePerDay($complianceRecords);
                                             }
                                             
                                             return $employee;
                                        });

          // Auto-close breaches that were NOT re-detected this run (now fixed).
          $this->resolveStaleBreaches($resort->resort_id);

          return redirect()->route('people.compliance.index')->with('success', 'Compliance checks completed successfully.');
     }

     // Keys (resort|employee|module|breach) of every breach re-detected during
     // the current checkCompliance() run. Used by resolveStaleBreaches() to
     // auto-close breaches that no longer fire. Reset at the start of each run.
     private $activeBreachKeys = [];

     // Breach types that are safe to auto-resolve: evaluated on EVERY compliance
     // run and driven by employee attributes, so non-detection reliably means
     // the underlying issue was fixed. Payroll-gated breaches (Service Charge,
     // Pension) are intentionally excluded — they only run when last month's
     // payroll row exists, so their absence does NOT prove the breach is fixed.
     // NOTE: only breaches generated INSIDE checkCompliance() may be listed
     // here. 'Medical certificates Required' is deliberately omitted — it is
     // produced by the separate test() method, not the production run, so the
     // sweep would never mark it active and would wrongly resolve it.
     private const AUTO_RESOLVABLE_BREACHES = [
          'TIN Requirement',
          'Minimum Wage',
          'Extended Probation Period',
          'Over Time Not Eligibile',
          'Overtime Agreement Lacked',
          'Job Description Not Received',
          'Without Mandatory Break',
          'Weekly Working Hours Limit',
     ];

     /**
      * Build the canonical active-breach key for an employee-keyed breach.
      * Null-employee breaches are skipped — they never surface on the compliance
      * list (list() requires whereHas('employee')) so they are out of scope for
      * auto-resolve. Accepts either an attributes array or explicit parts.
      */
     private function markBreachActive(array $row): void
     {
          $employeeId = $row['employee_id'] ?? null;
          if ($employeeId === null) {
               return;
          }
          $this->activeBreachKeys[implode('|', [
               $row['resort_id'] ?? '',
               $employeeId,
               trim((string) ($row['module_name'] ?? '')),
               trim((string) ($row['compliance_breached_name'] ?? '')),
          ])] = true;
     }

     /**
      * Auto-close employee-keyed breaches that were NOT re-detected during this
      * run — i.e. the underlying issue was fixed (e.g. a TIN was entered). Only
      * breach types in AUTO_RESOLVABLE_BREACHES are touched, and only rows still
      * Pending (manually dismissed rows are left as-is). Resolved rows get
      * Dismissal_status='Resolved' (distinct from manual 'Rejected') so they
      * drop off the list while staying auditable.
      */
     private function resolveStaleBreaches($resortId): void
     {
          $open = Compliance::where('resort_id', $resortId)
               ->whereNotNull('employee_id')
               ->where('Dismissal_status', 'Pending')
               ->whereIn('compliance_breached_name', self::AUTO_RESOLVABLE_BREACHES)
               ->get();

          foreach ($open as $row) {
               $key = implode('|', [
                    $row->resort_id,
                    $row->employee_id,
                    trim((string) $row->module_name),
                    trim((string) $row->compliance_breached_name),
               ]);
               if (!isset($this->activeBreachKeys[$key])) {
                    $row->status           = 'Resolved';
                    $row->Dismissal_status = 'Resolved';
                    $row->save();
               }
          }
     }

     /**
      * Dedup guard for breaches generated by checkCompliance().
      *
      * checkCompliance() appends breaches on every run and never clears prior
      * rows, so without a guard each run re-creates the same breaches. This
      * returns true when an identical breach (same resort, employee, module,
      * breach name and description) has already been reported today — allowing a
      * genuinely new breach to still be recorded on a later day.
      */
     private function complianceReportedToday(array $row)
     {
          $query = Compliance::query()
               ->where('resort_id', $row['resort_id'])
               ->where('module_name', $row['module_name'])
               ->where('compliance_breached_name', $row['compliance_breached_name'])
               ->whereDate('reported_on', Carbon::today());

          if (array_key_exists('employee_id', $row) && $row['employee_id'] !== null) {
               $query->where('employee_id', $row['employee_id']);
          } else {
               $query->whereNull('employee_id');
          }

          // Some breaches (leave-on-holiday, promotions) legitimately produce
          // several rows per employee that differ only by description, so the
          // description is part of the identity when present.
          if (array_key_exists('description', $row) && $row['description'] !== null) {
               $query->where('description', $row['description']);
          }

          return $query->exists();
     }

     /**
      * Create a breach only if an identical one has not already been reported
      * today. Returns the new row, or null if it was skipped as a duplicate (so
      * the caller can skip AI enrichment and notifications too).
      */
     private function createComplianceOncePerDay(array $attributes)
     {
          // Record that this breach is still active this run (drives auto-resolve).
          $this->markBreachActive($attributes);

          if ($this->complianceReportedToday($attributes)) {
               return null;
          }

          return Compliance::create($attributes);
     }

     /**
      * Bulk-insert a batch of breaches, dropping any that were already reported
      * today so re-running the compliance check does not duplicate them.
      */
     private function insertComplianceOncePerDay(array $rows)
     {
          $fresh = [];
          $seen = [];
          foreach ($rows as $row) {
               // Record that this breach is still active this run (drives auto-resolve).
               $this->markBreachActive($row);

               // Skip rows already queued in this same batch (the DB check below
               // can't catch those, as they are not persisted yet).
               $key = implode('|', [
                    $row['resort_id'] ?? '',
                    $row['employee_id'] ?? '',
                    $row['module_name'] ?? '',
                    $row['compliance_breached_name'] ?? '',
                    $row['description'] ?? '',
               ]);
               if (isset($seen[$key])) {
                    continue;
               }
               $seen[$key] = true;

               if (!$this->complianceReportedToday($row)) {
                    $fresh[] = $row;
               }
          }

          if (!empty($fresh)) {
               Compliance::insert($fresh);
          }
     }

     public function isExpired($date)
     {
          return  Carbon::parse($date)->lt(Carbon::today());
     }
     function stringifyExpiryDocument(array $expiryDocument, bool $asArray = true)
     {
         $out = [];
          foreach ($expiryDocument as $employeeName => $docs) 
          {

               // Build the part after "EmployeeName: "
               $docParts = array_map(function ($doc) {
                    return sprintf(
                         '%s — %s, MVR %s',
                         $doc['type'],
                         $doc['ExpiryDate'],
                         $doc['amount']
                    );
               }, $docs);

               $out = $employeeName . ': ' . implode('; ', $docParts);
          }

          // Return as‑is (separate strings) or one single string
          return $asArray ? $out : implode("\n", $out);
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
     public function download(Request $request)
     {
          $resort   = $this->resort;
          $resortId = $resort->resort_id;

          // Same scoping as list(): constrain the eager-loaded employee
          // to the current resort so a cross-resort employee_id can't
          // leak another resort's name/picture into the downloaded file.
          $query = Compliance::where('resort_id', $resortId)->with([
               'employee' => function ($q) use ($resortId) {
                    $q->where('resort_id', $resortId)->with('resortAdmin');
               },
          ]);
          
          if ($request->searchTerm != null) {
               $searchTerm = $request->searchTerm;
               $query->whereHas('employee', function ($q) use ($searchTerm) {   
                    $q->whereHas('resortAdmin', function ($Qname) use ($searchTerm) {
                         $Qname->where('id', 'LIKE', "%{$searchTerm}%")
                              ->orWhere('first_name', 'LIKE', "%{$searchTerm}%")     
                              ->orWhere('last_name', 'LIKE', "%{$searchTerm}%")
                              ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "%{$searchTerm}%");
                    });
               });
          }
          
          if ($request->status != null) {
               $query->where('status', $request->status);
          }
          
          if ($request->module_name != null) {
               $query->where('module_name', $request->module_name);
          }
          
          if ($request->compliance_breached_name != null) {
               $query->where('compliance_breached_name', $request->compliance_breached_name);
          }
          
          // Filter out rows whose employee isn't in this resort (cross-resort
          // data corruption guard — see list() for the rationale).
          $compliances = $query
               ->whereHas('employee', function ($q) use ($resortId) {
                    $q->where('resort_id', $resortId)->whereHas('resortAdmin');
               })
               ->get();
          
          // Transform data similar to how the DataTable displays it
          $formattedData = [];
          foreach ($compliances as $index => $compliance) {
               $formattedData[] = [
                    'no' => $index + 1,
                    'module_name' => $compliance->module_name,
                    'compliance_breached_name' => $compliance->compliance_breached_name,
                    'employee_id' => $compliance->employee ? $compliance->employee->Emp_id : 'N/A',
                    'employee_name' => $compliance->employee ? $compliance->employee->resortAdmin->full_name : 'N/A',
                    'description' => $compliance->description,
                    'reported_on' => $compliance->reported_on ? Carbon::parse($compliance->reported_on)->format('Y-m-d H:i:s') : 'N/A',
                    'status' => $compliance->status
               ];
          }
          
          // Generate PDF
          $data = [
               'compliances' => $formattedData,
               'title' => 'Compliance Report',
               'date' => Carbon::now()->format('Y-m-d')
          ];
          
          $pdf = PDF::loadView('resorts.people.compliance.pdf', $data);
          return $pdf->download('compliance-report-' . Carbon::now()->format('Y-m-d') . '.pdf');
     }


     public function test(){
          
          $resort = $this->resort;

          $employees = Employee::where('resort_id',$resort->resort_id)->where('status','Active')->get();

          foreach($employees as $employee) {
               $emp_grade = Common::getEmpGrade($resort->resort_id, $employee->rank);
               $leaveCategory = LeaveCategory::where('resort_id', $resort->resort_id)
                    ->where(function($query) use ($emp_grade) {
                         $query->where('eligibility', $emp_grade)
                              ->orWhereRaw("FIND_IN_SET(?, eligibility)", [$emp_grade]);
                    })->get();

               if($leaveCategory) {

                    $sickLeaveCategory = $leaveCategory->where('leave_type', 'Sick Leave')->first();
                    $sickLeaveEmployees = EmployeeLeave::where('resort_id', $resort->resort_id)->where('leave_category_id',$sickLeaveCategory->id)->where('emp_id',$employee->id)->get();
                    if($sickLeaveEmployees->count() > 0) {
                         foreach ($sickLeaveEmployees as $employeeLeave) {

                              if($employeeLeave->total_days <= 2){
                                   $leaveDate = Carbon::parse($employeeLeave->from_date);
                                   $leaveMonth = $leaveDate->month;
                                   $leaveYear = $leaveDate->year;

                                   $checkCurrentMonthLeaves = EmployeeLeave::where('resort_id', $resort->resort_id)
                                        ->where('emp_id', $employeeLeave->emp_id)
                                        ->where('leave_category_id', $employeeLeave->leave_category_id)
                                        ->whereMonth('from_date', $leaveMonth)
                                        ->whereYear('from_date', $leaveYear)
                                        ->where('total_days','<=',2)
                                        ->get();

                                   $sickLeaveDaysInCurrentMonth = $checkCurrentMonthLeaves->sum('total_days');

                                   if ($sickLeaveDaysInCurrentMonth >= 15) {
                                        $compliance = Compliance::firstOrCreate(
                                        [
                                             'resort_id' => $resort->resort_id,
                                             'employee_id' => $employeeLeave->emp_id,
                                             'module_name' => 'Sick Leave',
                                             'compliance_breached_name' => 'Medical certificates Required',
                                        ],
                                        [
                                             'description' => "Employee {$employee->resortAdmin->full_name} has taken more than {$sickLeaveDaysInCurrentMonth} days of Sick Leave without a medical certificate.",
                                             'reported_on' => Carbon::now(),
                                             'status' => 'Breached',
                                        ]);
                                   }
                              }

                              if($employeeLeave->total_days > 2){
                              
                                   $compliance = Compliance::firstOrCreate(
                                        [
                                             'resort_id' => $resort->resort_id,
                                             'employee_id' => $employeeLeave->emp_id,
                                             'module_name' => 'Sick Leave',
                                             'compliance_breached_name' => 'Medical certificates Required',
                                        ],
                                        [
                                             'description' => "Employee {$employee->resortAdmin->full_name} has taken more than {$sickLeaveDaysInCurrentMonth} days of Sick Leave without a medical certificate.",
                                             'reported_on' => Carbon::now(),
                                             'status' => 'Breached',
                                        ]);
                              }


                         }
                    }

                    // Check if employee has completed at least one year in the company
                    if($employee->joining_date && Carbon::parse($employee->joining_date)->diffInYears(Carbon::now()) >= 1) {
                         $annualLeaveCategory = $leaveCategory->where('leave_type', 'Annual Leave')->first();
                         $annualLeaveEmployees = EmployeeLeave::where('resort_id', $resort->resort_id)
                              ->where('leave_category_id', $annualLeaveCategory->id)
                              ->where('emp_id', $employee->id)
                              ->whereYear('from_date', Carbon::now()->year)
                              ->get();

                         if($annualLeaveEmployees->count() > 0) {     
                              $totalLeavs = $annualLeaveEmployees->sum('total_days');
                              if($totalLeavs == $annualLeaveCategory->number_of_days){

                              }elseif($totalLeavs < $annualLeaveCategory->number_of_days){

                              }
                         }
                    }  


               }

          }

          $LeaveCategory = LeaveCategory::where('resort_id', $resort->resort_id)->where('leave_type','Annual Leave')->first();
          $sickLeaveEmployees = EmployeeLeave::where('resort_id', $resort->resort_id)->where('leave_category_id',$LeaveCategory->id)->get();


          return 'sada';
          // Sick Leave Compliance Start
               $sickLeaveCategory = LeaveCategory::where('resort_id', $resort->resort_id)->where('leave_type','Sick Leave')->first();
               $sickLeaveEmployees = EmployeeLeave::where('resort_id', $resort->resort_id)->where('leave_category_id',$sickLeaveCategory->id)
               ->get();
               

               foreach ($sickLeaveEmployees as $employeeLeave) {

                    if($employeeLeave->total_days <= 2){
                          $leaveDate = Carbon::parse($employeeLeave->from_date);
                          $leaveMonth = $leaveDate->month;
                          $leaveYear = $leaveDate->year;

                         $checkCurrentMonthLeaves = EmployeeLeave::where('resort_id', $resort->resort_id)
                              ->where('emp_id', $employeeLeave->emp_id)
                              ->where('leave_category_id', $employeeLeave->leave_category_id)
                              ->whereMonth('from_date', $leaveMonth)
                              ->whereYear('from_date', $leaveYear)
                              ->where('total_days','<=',2)
                              ->get();

                         $sickLeaveDaysInCurrentMonth = $checkCurrentMonthLeaves->sum('total_days');

                         // Check if employee has taken more than 2 sick leaves in current month
                         if ($sickLeaveDaysInCurrentMonth >= 15) {
                              $compliance = Compliance::firstOrCreate([
                              'resort_id' => $resort->resort_id,
                              'employee_id' => $employeeLeave->employee_id,
                              'module_name' => 'Sick Leave',
                              'compliance_breached_name' => 'Medical Certificate',
                              'description' => "Employee {$employeeLeave->employee->resortAdmin->full_name} has taken more than 2 Sick Leaves without a medical certificate.",
                              'reported_on' => Carbon::now(),
                              'status' => 'Breached'
                         ]);
                         }
                    }
                    // $check_medical_cetificate = ;

                    if($check_medical_cetificate == false) {
                         $compliance = Compliance::firstOrCreate([
                              'resort_id' => $resort->resort_id,
                              'employee_id' => $employeeLeave->employee_id,
                              'module_name' => 'Sick Leave',
                              'compliance_breached_name' => 'Medical Certificate',
                              'description' => "Employee {$employeeLeave->employee->resortAdmin->full_name} has taken more than 2 Sick Leaves without a medical certificate.",
                              'reported_on' => Carbon::now(),
                              'status' => 'Breached'
                         ]);
                    }
               }
          // Sick Leave Compliance End
     }


     public function Calendar()
     {
          $resort = $this->resort;
         
          $compliances = Compliance::where('resort_id', $resort->resort_id)
               ->with(['employee.resortAdmin'])
               ->get()
               ->map(function ($compliance) {
                    return [
                         'title' => $compliance->compliance_breached_name,
                         'start' => Carbon::parse($compliance->reported_on)->format('Y-m-d'),
                         'end' => Carbon::parse($compliance->reported_on)->format('Y-m-d'),
                         'description' => $compliance->description,
                         'employee_name' => $compliance->employee ? $compliance->employee->resortAdmin->full_name : 'N/A',
                         'status' => $compliance->status
                    ];
               });

          return view('resorts.people.compliance.calendar', compact('compliances'));
     }

     public function DismissCompliance($id)
     {
          // Scope by resort_id. Without it any logged-in user could
          // dismiss a compliance row belonging to ANOTHER resort just by
          // knowing the base64-encoded id (which is enumerable). The list
          // page only shows the current resort's rows, but the dismiss
          // endpoint is callable directly.
          $decoded = base64_decode($id);
          $compliance = Compliance::where('id', $decoded)
              ->where('resort_id', $this->resort->resort_id)
              ->first();
          if ($compliance) {
              $compliance->Dismissal_status = 'Rejected';
              $compliance->save();

               return response()->json([
                   'success' => true,
                   'message' => 'Compliance dismissed successfully.'
               ],200);
          }
          else
          {
               return response()->json([
                   'success' => false,
                   'errors' => $validator->errors()
               ], 422);
          }

         
     }
}