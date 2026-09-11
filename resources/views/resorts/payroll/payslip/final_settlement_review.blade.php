@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding">
                <div class="row  g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Payroll</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>

            <form id="final-review-form" method="POST">
                @csrf
                <input type="hidden" name="payment_mode" id="paymentModeInput" value="{{$finalSettlement->payment_mode}}">

                <div class="fsr-wrap">

                    {{-- ─────────── Settlement document (left) + Employee (right) ─────────── --}}
                    <div class="fsr-card fsr-combo fsr-span2">
                        <div class="fsr-cl">
                            <div class="fsr-sec-top">
                                <h2>Settlement document</h2>
                                <div class="fsr-doc-actions">
                                    <a href="#" class="fsr-ghostbtn" id="printFinalSettlement"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v8H6z"/></svg>Print</a>
                                    <a href="#" class="fsr-ghostbtn" id="downloadPdf"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>Download</a>
                                </div>
                            </div>
                            <div class="fsr-meta2">
                                <div class="fsr-m"><div class="fsr-l">Reference No.</div><div class="fsr-v">{{$finalSettlement->reference_no}}</div></div>
                                <div class="fsr-m"><div class="fsr-l">Doc Date</div><div class="fsr-v">{{ \Carbon\Carbon::parse($today)->format('d M Y') }}</div></div>
                                @php
                                    // final_settlements.last_working_date is nullable and
                                    // reaches the page as NULL when the F&F store didn't
                                    // receive a parseable value (Carbon::parse(null) then
                                    // renders as "30 Nov -0001"). Fall back to the
                                    // employee's resignation last_working_day, which is
                                    // always present at this point in the lifecycle.
                                    $lwd = $finalSettlement->last_working_date
                                        ?: optional($finalSettlement->employee->resignation)->last_working_day;
                                @endphp
                                {{-- Payroll Month was the month the payroll WINDOW starts
                                     (Apr for a 25-Apr → 24-May cycle). HR reads "Payroll
                                     Month" as the month of the last working day (when the
                                     employee actually left), which matches the payslip
                                     convention. Fall back to payroll_start if no LWD. --}}
                                <div class="fsr-m"><div class="fsr-l">Payroll Month</div><div class="fsr-v">{{ $lwd ? \Carbon\Carbon::parse($lwd)->format('F') : \Carbon\Carbon::parse($calculated['payroll_start'])->format('F') }}</div></div>
                                <div class="fsr-m"><div class="fsr-l">Pay Mode</div><div class="fsr-v">{{$finalSettlement->employee->payment_mode}}</div></div>
                                <div class="fsr-m"><div class="fsr-l">Hire Date</div><div class="fsr-v">{{ \Carbon\Carbon::parse($finalSettlement->employee->joining_date)->format('d M Y') }}</div></div>
                                <div class="fsr-m"><div class="fsr-l">Last Working Date</div><div class="fsr-v">{{ $lwd ? \Carbon\Carbon::parse($lwd)->format('d M Y') : '—' }}</div></div>
                                <div class="fsr-m"><div class="fsr-l">Reason</div><div class="fsr-v"><span class="fsr-rpill">{{$finalSettlement->employee->resignation->reason_title->reason}}</span></div></div>
                            </div>
                        </div>
                        <div class="fsr-cr fsr-pcard">
                            <div class="fsr-ct"><h2>Employee</h2></div>
                            @php
                                // Photo-first avatar with an initials fallback —
                                // Common::getResortUserPicture() always resolves to at
                                // least the app's generic silhouette default, so only
                                // treat it as a real photo when it differs from that
                                // default; otherwise fall back to initials rather than
                                // showing the generic placeholder.
                                $fsrFullName = $finalSettlement->employee->resortAdmin->full_name ?? '';
                                $fsrParts = preg_split('/\s+/', trim($fsrFullName));
                                $fsrInitials = strtoupper(($fsrParts[0][0] ?? '') . (isset($fsrParts[1]) ? $fsrParts[1][0] : '')) ?: '?';
                                $fsrPhoto = Common::getResortUserPicture($finalSettlement->employee->Admin_Parent_id);
                                if ($fsrPhoto === url(config('settings.default_picture'))) { $fsrPhoto = null; }
                            @endphp
                            <div class="fsr-pc-head">
                                <span class="fsr-pa">
                                    <span class="fsr-pa-fallback">{{ $fsrInitials }}</span>
                                    @if($fsrPhoto)<img src="{{ $fsrPhoto }}" alt="{{ $fsrFullName }}" onerror="this.remove()">@endif
                                </span>
                                <div class="fsr-pn">{{ $fsrFullName }} <span class="fsr-id-chip">{{$finalSettlement->employee->Emp_id}}</span></div>
                            </div>
                            <div class="fsr-prow"><span class="fsr-k">Position</span><span class="fsr-v">{{$finalSettlement->employee->position->position_title}}</span></div>
                            <div class="fsr-prow"><span class="fsr-k">Department</span><span class="fsr-v">{{$finalSettlement->employee->department->name}}</span></div>
                            <div class="fsr-prow"><span class="fsr-k">Division</span><span class="fsr-v">{{$finalSettlement->employee->division->name}}</span></div>
                            {{-- Basic Salary currency was hardcoded MVR even though
                                 employees on USD payroll have their basic stored in
                                 USD on the employee row. Show the employee's actual
                                 stored basic + their basic_salary_currency. --}}
                            <div class="fsr-prow"><span class="fsr-k">Basic Salary</span><span class="fsr-v">{{ number_format((float) ($finalSettlement->employee->basic_salary ?? 0), 2) }} {{ $finalSettlement->employee->basic_salary_currency ?? 'MVR' }}</span></div>
                            <div class="fsr-prow"><span class="fsr-k">Payroll Start Date</span><span class="fsr-v">{{$calculated['payroll_start']}}</span></div>
                            <div class="fsr-prow"><span class="fsr-k">Remarks</span><span class="fsr-v fsr-muted">—</span></div>
                        </div>
                    </div>

                    {{-- ════════════════════════════════════════════════════════════
                         Figma layout: two cards side by side.
                         LEFT  "Earnings"  → Payable Leaves table (per leave category
                                              + Air Ticket Reimbursement if recorded as
                                              a F&F earning → Total Earnings)
                         RIGHT "Deductions" → Settlement Details summary (Basic Salary
                                              For X Days → Service Charge → Leave Days
                                              Salary → Allowances → Gross Pay → EWHT →
                                              custom deductions → Total Deductions)

                         All amounts use the SAME currency the F&F page submitted in
                         (MVR for resorts that pay in MVR, USD where stored as USD).
                         Common::formatCurrency just prefixes the symbol — no
                         conversion happens here. ──────────────────────────────────── --}}
                    @php
                        // Display currency = the employee's basic_salary_currency.
                        // Previously hardcoded to MVR which broke USD-payroll
                        // employees (their figures show as raw MVR alongside a
                        // page header that says USD). The F&F service exposes
                        // values in MVR internally, so MVR rows are converted to
                        // display currency at render time via $toDisplay below.
                        $payCurrency = $finalSettlement->employee->basic_salary_currency ?? 'MVR';
                        $dollarToMvr = \App\Models\ResortSiteSettings::where('resort_id', $finalSettlement->employee->resort_id)->value('DollertoMVR') ?: 15.42;
                        // MVR-stored amounts → render currency.
                        $toDisplay = function ($mvr) use ($payCurrency, $dollarToMvr) {
                            $n = (float) $mvr;
                            if ($payCurrency === 'USD' && $dollarToMvr > 0) return $n / $dollarToMvr;
                            return $n;
                        };
                        // F&F-stored amounts (final_settlements columns) → render
                        // currency. The F&F page posts whatever was visible on
                        // screen at submit, which means values land in the
                        // EMPLOYEE'S basic_salary_currency. No conversion needed
                        // when the stored unit already matches payCurrency.
                        $storedToDisplay = function ($val) {
                            return (float) $val;
                        };
                        $isMaldivian = strtolower((string) $finalSettlement->employee->nationality) === 'maldivian';

                        // Build per-leave-category rows. Source priority:
                        //   1. final_settlements.leave_breakdown_json (frozen
                        //      snapshot HR signed off on at submit — locks in
                        //      manual edits like trimming Day Off 8→7 so it
                        //      doesn't re-accrue back to 8 on the review page).
                        //   2. Fresh getLeaveBalance() call as a fallback for
                        //      older settlements pre-dating the snapshot column.
                        //
                        // daily_salary from the service is MVR — convert when
                        // the employee is on USD payroll so the row amount
                        // column matches the page's pay currency.
                        $leaveRows = [];
                        $leaveDaysSalaryTotal = 0;
                        $dailySalaryDisplay = $toDisplay($calculated['daily_salary'] ?? 0);

                        $snapshot = !empty($finalSettlement->leave_breakdown_json)
                            ? json_decode($finalSettlement->leave_breakdown_json, true)
                            : null;
                        if (is_array($snapshot) && count($snapshot) > 0) {
                            foreach ($snapshot as $b) {
                                $days = (float) ($b['available_days'] ?? 0);
                                $amount = $days * $dailySalaryDisplay;
                                $leaveDaysSalaryTotal += $amount;
                                $leaveRows[] = [
                                    'leave_type' => $b['leave_type'] ?? 'Leave',
                                    'days'       => $days,
                                    'amount'     => $amount,
                                ];
                            }
                        } elseif (!empty($leaveBalances['details'])) {
                            foreach ($leaveBalances['details'] as $b) {
                                $amount = ($b['available_days'] ?? 0) * $dailySalaryDisplay;
                                $leaveDaysSalaryTotal += $amount;
                                $leaveRows[] = [
                                    'leave_type' => $b['leave_type'] ?? 'Leave',
                                    'days'       => $b['available_days'] ?? 0,
                                    'amount'     => $amount,
                                ];
                            }
                        }
                    @endphp

                    {{-- Build the row sets up front so the Earnings table on the
                         left and the Net Pay summary at the bottom both pull
                         from the same numbers — no drift between the two cards.

                         Sources of truth:
                           • $finalSettlement columns: what HR submitted on the
                             F&F page (basic_salary, service_charge, total_earnings,
                             tax, pension, loan_payment). These reach the page in
                             the employee's basic_salary_currency, so no conversion
                             is needed at render time.
                           • $calculated (from FinalSettlementService): MVR-internal
                             values used as fallbacks when the saved column is
                             empty (older settlements pre-dating the column).
                             These DO need MVR → payCurrency conversion via
                             $toDisplay.
                           • $leaveBalances: leave breakdown for the per-row
                             Payable Leaves table; the daily_salary multiplier
                             also runs through $toDisplay. --}}
                    @php
                        // Prefer the frozen value (set at store()/submit() time)
                        // over a live recompute — attendance data can change
                        // after a settlement is saved, and re-deriving the day
                        // count on every page load could show a different
                        // number than what the frozen dollar amount below was
                        // actually based on. Older rows predating this column
                        // fall back to the live figure.
                        $workedDays = $finalSettlement->worked_days !== null
                            ? (int) $finalSettlement->worked_days
                            : (int) ($calculated['worked_days'] ?? 0);

                        // Earned Salary (Basic Salary For N Days). Prefer the
                        // HR-submitted value (final_settlements.total_earnings —
                        // this is what HR called "Earned Salary" on the F&F
                        // page); fall back to the service's prorated MVR figure
                        // converted to display currency. Was missing entirely
                        // on the old layout (reported as "earned salary is
                        // missing").
                        $proratedBasic = isset($finalSettlement->total_earnings) && $finalSettlement->total_earnings > 0
                            ? (float) $finalSettlement->total_earnings
                            : $toDisplay($calculated['proratedBasic'] ?? 0);

                        // Service Charge — HR submitted via the Earnings card on
                        // the F&F page; was rendered on the wrong side of the
                        // review (under Deductions) and read 0 because the
                        // column lookup path was right but the layout was
                        // wrong. Moved here so it sits where HR submitted it.
                        $serviceCharge = $storedToDisplay($finalSettlement->service_charge ?? 0);

                        $totalAllowance = $toDisplay($calculated['total_allowances_mvr'] ?? 0);

                        $ewt          = $storedToDisplay($finalSettlement->tax ?? 0);
                        $pension      = $storedToDisplay($finalSettlement->pension ?? 0);
                        $loanRecovery = $storedToDisplay($finalSettlement->loan_payment ?? 0);
                        $noticeCharge = $toDisplay($calculated['notice_period_charge_mvr'] ?? 0);
                    @endphp

                    {{-- ─────────── Earnings (left) + Deductions (right) ─────────── --}}
                    <div class="fsr-card fsr-eq">
                        <div class="fsr-ct"><span class="fsr-ic fsr-up"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5M5 12l7-7 7 7"/></svg></span><h2>Earnings</h2></div>
                                    <table class="fsr-tbl">
                                            <thead>
                                                <tr>
                                                    <th>Particulars</th>
                                                    <th class="text-end">Days</th>
                                                    <th class="text-end">Amount ({{ $payCurrency }})</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php $totalEarnings = 0; @endphp

                                                {{-- Earned Salary (basic-for-N-days). --}}
                                                <tr>
                                                    <td>
                                                        Earned Salary
                                                        <small class="text-muted">(Basic Salary for {{ $workedDays }} day(s))</small>
                                                    </td>
                                                    <td class="text-end">{{ $workedDays }}</td>
                                                    <td class="text-end">{!! Common::formatCurrency($proratedBasic, $payCurrency) !!}</td>
                                                </tr>
                                                @php $totalEarnings += $proratedBasic; @endphp

                                                {{-- Service Charge. --}}
                                                <tr>
                                                    <td>Service Charge</td>
                                                    <td class="text-end">—</td>
                                                    <td class="text-end">{!! Common::formatCurrency($serviceCharge, $payCurrency) !!}</td>
                                                </tr>
                                                @php $totalEarnings += $serviceCharge; @endphp

                                                {{-- Allowances are shown one-per-row below via
                                                     $finalSettlement->earnings, so the aggregate
                                                     "Total Allowances" line that used to sit here
                                                     was double-counting the same amounts. Removed
                                                     per HR's review-page cleanup. --}}

                                                {{-- Per-leave-category breakdown (Annual / PH / Day Off). --}}
                                                @forelse($leaveRows as $row)
                                                    <tr>
                                                        <td>{{ $row['leave_type'] }}</td>
                                                        <td class="text-end">{{ number_format($row['days'], 2) }}</td>
                                                        <td class="text-end">{!! Common::formatCurrency($row['amount'], $payCurrency) !!}</td>
                                                    </tr>
                                                    @php $totalEarnings += $row['amount']; @endphp
                                                @empty
                                                    <tr>
                                                        <td colspan="3" class="text-center text-muted">No leave entitlements recorded.</td>
                                                    </tr>
                                                @endforelse

                                                {{-- Custom earnings (Air Ticket, joining bonus, etc.). --}}
                                                @if(!empty($finalSettlement->earnings))
                                                    @foreach($finalSettlement->earnings as $earnings)
                                                        @php $totalEarnings += $earnings->amount; @endphp
                                                        <tr>
                                                            <td>{{ optional(optional($earnings->earning)->allowanceName)->particulars ?? 'Other Earning' }}</td>
                                                            <td class="text-end">—</td>
                                                            <td class="text-end">{!! Common::formatCurrency($earnings->amount, $payCurrency) !!}</td>
                                                        </tr>
                                                    @endforeach
                                                @endif
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th colspan="2">Total Earnings</th>
                                                    <th class="text-end">{!! Common::formatCurrency($totalEarnings, $payCurrency) !!}</th>
                                                </tr>
                                            </tfoot>
                        </table>
                    </div>

                    {{-- ─────────── Deductions ───────────
                         Strictly the actual deductions HR posted (EWHT, MRPS,
                         Loan, Notice, custom). Basic / Service / Allowance
                         now live in the Earnings card where they belong. --}}
                    <div class="fsr-card fsr-eq">
                        <div class="fsr-ct"><span class="fsr-ic fsr-dn"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12l7 7 7-7"/></svg></span><h2>Deductions</h2></div>
                                    <table class="fsr-tbl">
                                            <thead>
                                                <tr>
                                                    <th>Particulars</th>
                                                    <th class="text-end">{{ $payCurrency }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php $totalDeductions = 0; @endphp

                                                <tr>
                                                    <td>EWHT - Total Taxable Income</td>
                                                    <td class="text-end">{!! Common::formatCurrency($ewt, $payCurrency) !!}</td>
                                                </tr>
                                                @php $totalDeductions += $ewt; @endphp

                                                {{-- MRPS / Pension applies only to Maldivian
                                                     employees (the F&F page hides the column
                                                     for foreigners; mirror that gate here so a
                                                     stray non-zero pension value can't show up
                                                     on a foreign employee's settlement). --}}
                                                @if($isMaldivian && $pension > 0)
                                                    <tr>
                                                        <td>MRPS Employee Mandatory Contribution</td>
                                                        <td class="text-end">{!! Common::formatCurrency($pension, $payCurrency) !!}</td>
                                                    </tr>
                                                    @php $totalDeductions += $pension; @endphp
                                                @endif

                                                @if($loanRecovery > 0)
                                                    <tr>
                                                        <td>Loan / Salary Advance Recovery</td>
                                                        <td class="text-end">{!! Common::formatCurrency($loanRecovery, $payCurrency) !!}</td>
                                                    </tr>
                                                    @php $totalDeductions += $loanRecovery; @endphp
                                                @endif

                                                @if($noticeCharge > 0)
                                                    <tr>
                                                        <td>Notice Period Charge</td>
                                                        <td class="text-end">{!! Common::formatCurrency($noticeCharge, $payCurrency) !!}</td>
                                                    </tr>
                                                    @php $totalDeductions += $noticeCharge; @endphp
                                                @endif

                                                @if(!empty($finalSettlement->deductions))
                                                    @foreach($finalSettlement->deductions as $deductions)
                                                        @php $totalDeductions += $deductions->amount; @endphp
                                                        <tr>
                                                            <td>{{ optional($deductions->deduction)->deduction_name ?? 'Other Deduction' }}</td>
                                                            <td class="text-end">{!! Common::formatCurrency($deductions->amount, $payCurrency) !!}</td>
                                                        </tr>
                                                    @endforeach
                                                @endif
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th>Total Deductions</th>
                                                    <th class="text-end" id="totalDeductions">{!! Common::formatCurrency($totalDeductions, $payCurrency) !!}</th>
                                                </tr>
                                            </tfoot>
                        </table>
                    </div>

                    @php
                        // Split sign + whole + fraction BEFORE flooring. Previously
                        // floor(-218.67) returned -219 (Carbon-of-the-flooring-world:
                        // toward negative infinity), so the converter spoke
                        // "Minus two hundred nineteen point thirty-three" instead
                        // of "Minus two hundred eighteen point sixty-seven".
                        // Work with absolute value, then re-attach the sign.
                        if (!function_exists('convertToWords')) {
                            // "39.60" → "Thirty-nine and sixty cents"
                            // (not the old "Thirty-nine point sixty" — HR
                            // wants the conventional cheque-style
                            // dollars-and-cents wording).
                            function convertToWords($number) {
                                $formatter = new NumberFormatter('en', NumberFormatter::SPELLOUT);
                                $sign = $number < 0 ? 'Minus ' : '';
                                $abs = abs((float) $number);
                                $whole = (int) floor($abs);
                                $fraction = (int) round(($abs - $whole) * 100);
                                $wholeWords = ucfirst($formatter->format($whole));
                                if ($fraction > 0) {
                                    $unit = $fraction === 1 ? ' cent' : ' cents';
                                    return $sign . $wholeWords . ' and ' . $formatter->format($fraction) . $unit;
                                }
                                return $sign . $wholeWords;
                            }
                        }
                    @endphp

                    {{-- ─────────── Net pay (breakdown + words, left) + hero/status (right) ─────────── --}}
                    <div class="fsr-card fsr-span2 fsr-netcard">
                        <div class="fsr-net-left">
                            <div class="fsr-ct"><h2>Net pay</h2></div>
                            <div class="fsr-nline">
                                <div class="fsr-nrow"><span class="fsr-lbl">Gross Earnings</span><span class="fsr-amt" id="grossEarnings">{!! Common::formatCurrency($totalEarnings, $payCurrency) !!}</span></div>
                                <div class="fsr-nrow"><span class="fsr-lbl">Total Deductions</span><span class="fsr-amt fsr-red" id="totalDeductions1">− {!! Common::formatCurrency($totalDeductions, $payCurrency) !!}</span></div>
                            </div>
                            <div class="fsr-words"><b>In words:</b> <span id="netPayWords">{{ $payCurrency }} {{ convertToWords($totalEarnings - $totalDeductions) }} Only</span></div>
                            {{-- Bank Name + Account Number come from the employee's
                                 bank_details record (most recent first). Was previously
                                 hardcoded to "Bank Of Maldives / 154210145545" so every
                                 settlement reviewer saw the same placeholder regardless
                                 of the actual payee. Hides when the employee has no
                                 bank record OR is paid in Cash. --}}
                            @if($finalSettlement->employee->payment_mode == 'Bank')
                                @php
                                    $bank = optional($finalSettlement->employee->bankDetails)->sortByDesc('id')->first();
                                @endphp
                                @if($bank)
                                    <div class="fsr-words fsr-bankbox">
                                        <div><b>Bank Name:</b> {{ $bank->bank_name ?? '—' }}</div>
                                        <div><b>Account Number:</b> {{ $bank->account_number ?? '—' }}</div>
                                    </div>
                                @else
                                    <div class="fsr-words fsr-bankbox fsr-warnbox">
                                        <i class="fa-solid fa-circle-info me-1"></i>
                                        Bank payment selected but no bank account is on file for this employee.
                                    </div>
                                @endif
                            @endif
                            <input type="hidden" name="total_earnings" id="totalEarningsInput" value="{{ number_format($totalEarnings, 2, '.', '') }}">
                            <input type="hidden" name="worked_days" id="workedDaysInput" value="{{ $workedDays }}">
                            <input type="hidden" name="total_deductions" id="totalDeductionsInput" value="{{ number_format($totalDeductions, 2, '.', '') }}">
                            <input type="hidden" name="net_pay" id="netPayInput" value="{{ number_format($totalEarnings - $totalDeductions, 2, '.', '') }}">
                            <input type="hidden" name="final_settlement_id" id="final_settlement_id" value="{{ $finalSettlement->id }}">
                        </div>
                        <div class="fsr-net-hero">
                            <div class="fsr-nh-lab">Total Net Payable</div>
                            <div class="fsr-nh-big" id="netPayable">{!! Common::formatCurrency($totalEarnings - $totalDeductions, $payCurrency) !!}</div>
                            {{-- Lock banner once the settlement is finalized — replaces
                                 the Submit button with a read-only status, preventing
                                 accidental re-finalize (the controller also rejects a
                                 second POST, but the UI guard avoids the round-trip). --}}
                            @if($finalSettlement->status === 'finalized')
                                <div class="fsr-nh-fin">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                    <span>Settlement finalized · locked from edits.
                                        @if(!empty($finalSettlement->finalized_at))
                                            Finalized on {{ \Carbon\Carbon::parse($finalSettlement->finalized_at)->format('d M Y H:i') }}.
                                        @endif
                                    </span>
                                </div>
                                <a href="{{ route('final.settlement.list') }}" class="fsr-nh-back">Back to list <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                            @else
                                <button type="submit" class="fsr-nh-submit">Submit <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></button>
                            @endif
                        </div>
                    </div>
                </div>
            </form>

        </div>
    </div>
@endsection

@section('import-css')
@include('resorts.payroll._payroll_buttons_v2_styles')
<style>
    @media print {
    body * {
        visibility: hidden;
    }

    #final-review-form, #final-review-form * {
        visibility: visible;
    }

    #final-review-form {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }

    .btn, .card-footer, .navbar, .sidebar,
    .fsr-ghostbtn, .fsr-nh-back, .fsr-nh-submit {
        display: none !important;
    }
}
</style>
<style>
/* ════════════════════════════════════════════════════════════════
   Review Final Settlement — frontend-only restyle. Scoped fsr-
   prefixed classes; nothing here touches the shared .paySlip-,
   .img-obj, .bg-themeGrayLight classes other pages still use. */
.fsr-wrap { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; align-items: start; }
.fsr-span2 { grid-column: 1 / -1; }
@media (max-width: 900px) { .fsr-wrap { grid-template-columns: 1fr; } }

.fsr-card { background: #fff; border-radius: 18px; box-shadow: 0 1px 2px rgba(1,70,83,.05), 0 16px 40px rgba(1,70,83,.10); padding: 24px 26px; }
.fsr-eq { height: 100%; }

/* header: settlement-document (left) + employee card (right) */
.fsr-card.fsr-combo { padding: 0; display: grid; grid-template-columns: 1.5fr 1fr; }
.fsr-combo .fsr-cl { padding: 24px 30px 24px 26px; min-width: 0; }
.fsr-combo .fsr-cr { padding: 24px 26px 24px 30px; border-left: 1px solid var(--line, #EEF2F2); min-width: 0; }
@media (max-width: 900px) { .fsr-card.fsr-combo { grid-template-columns: 1fr; } .fsr-combo .fsr-cr { border-left: none; border-top: 1px solid var(--line, #EEF2F2); } }

.fsr-sec-top { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 20px; }
.fsr-sec-top h2 { font-size: 18px; font-weight: 600; color: var(--ink); }
.fsr-doc-actions { display: flex; gap: 10px; flex: none; }
.fsr-ghostbtn { background: #fff; color: #3A4145; border: 1px solid var(--line, #EEF2F2); border-radius: 11px; padding: 9px 15px; font: inherit; font-size: 13.5px; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; transition: border-color .14s, color .14s; }
.fsr-ghostbtn:hover { border-color: #C7CDCF; color: var(--teal); }
.fsr-ghostbtn svg { width: 15px; height: 15px; }
@media (prefers-reduced-motion: reduce) { .fsr-ghostbtn, .fsr-nh-back, .fsr-nh-submit { transition: none; } }

.fsr-meta2 { display: grid; grid-template-columns: 1fr 1fr; gap: 22px 26px; }
@media (max-width: 560px) { .fsr-meta2 { grid-template-columns: 1fr; } }
.fsr-meta2 .fsr-m .fsr-l { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; color: #99A1A5; margin-bottom: 6px; }
.fsr-meta2 .fsr-m .fsr-v { font-size: 14px; font-weight: 500; color: var(--ink); line-height: 1.45; }
.fsr-rpill { display: inline-block; background: #fbeceb; color: #B4462F; font-size: 12.5px; font-weight: 600; padding: 3px 10px; border-radius: 8px; }

/* employee card — Incident "Reported by" style */
.fsr-pcard .fsr-ct { border-bottom: 1px solid var(--line, #EEF2F2); padding-bottom: 14px; margin-bottom: 16px; }
.fsr-pc-head { display: flex; align-items: center; gap: 14px; padding-bottom: 16px; border-bottom: 1px solid var(--line, #EEF2F2); }
.fsr-pa { position: relative; width: 54px; height: 54px; flex: none; border-radius: 50%; background: var(--teal-soft, #f1f7f7); overflow: hidden; }
.fsr-pa-fallback { position: absolute; inset: 0; display: grid; place-items: center; color: var(--teal); font-size: 17px; font-weight: 600; }
.fsr-pa img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.fsr-pn { font-size: 17px; font-weight: 600; color: var(--ink); display: flex; align-items: center; gap: 9px; flex-wrap: wrap; }
.fsr-id-chip { background: #F7F8F8; color: #3A4145; font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 7px; }
.fsr-prow { display: flex; justify-content: space-between; align-items: center; gap: 16px; padding: 12px 0; border-bottom: 1px solid #F3F6F6; font-size: 14px; }
.fsr-prow:last-child { border-bottom: none; }
.fsr-prow .fsr-k { color: #6B7378; }
.fsr-prow .fsr-v { font-weight: 600; text-align: right; color: var(--ink); }
.fsr-prow .fsr-v.fsr-muted { color: #99A1A5; font-weight: 400; }

/* section header (Earnings / Deductions / Net pay) */
.fsr-ct { display: flex; align-items: center; gap: 11px; margin-bottom: 16px; }
.fsr-ct .fsr-ic { width: 30px; height: 30px; flex: none; border-radius: 9px; display: grid; place-items: center; }
.fsr-ct .fsr-ic.fsr-up { background: var(--teal-soft, #f1f7f7); color: var(--teal); }
.fsr-ct .fsr-ic.fsr-dn { background: #fbeceb; color: #B4462F; }
.fsr-ct h2 { font-size: 18px; font-weight: 600; color: var(--ink); }

/* tables — .fsr-tbl doubled on th/td/tfoot rules: the app's own
   default.css ships ".table thead th{padding:0 10px 12px !important}"
   and ".table tbody td{padding:16px 10px !important}" plus a
   ":first-child{padding-left:0 !important}" reset (all !important,
   meant for other DataTables-style tables app-wide) — matching
   !important here is the only way to win against that, scoped to
   just these two tables so no other .table on the site is touched. */
.fsr-tbl { width: 100%; border-collapse: separate; border-spacing: 0; border: 1px solid var(--line, #EEF2F2); border-radius: 14px; overflow: hidden; margin: 0; }
.fsr-tbl.fsr-tbl th { background: var(--teal-soft, #f1f7f7); text-align: left; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; color: #6B7378; padding: 12px 15px !important; border-bottom: 1px solid var(--line, #EEF2F2); }
.fsr-tbl.fsr-tbl td { padding: 13px 15px !important; border-bottom: 1px solid #F3F6F6; font-size: 14px; color: #3A4145; vertical-align: middle; }
.fsr-tbl.fsr-tbl th:first-child,
.fsr-tbl.fsr-tbl td:first-child { padding-left: 15px !important; }
.fsr-tbl.fsr-tbl tr:last-child td { border-bottom: none; }
.fsr-tbl .text-end { text-align: right; font-variant-numeric: tabular-nums; }
.fsr-tbl.fsr-tbl tfoot tr td,
.fsr-tbl.fsr-tbl tfoot tr th { font-weight: 600; background: #fcfdfd; font-size: 14px; color: var(--ink); padding: 13px 15px !important; }
.fsr-tbl.fsr-tbl tfoot tr td:first-child,
.fsr-tbl.fsr-tbl tfoot tr th:first-child { padding-left: 15px !important; }

/* net pay + finalized status combined into one card */
.fsr-netcard { display: grid; grid-template-columns: 1.4fr 1fr; padding: 0; overflow: hidden; }
@media (max-width: 900px) { .fsr-netcard { grid-template-columns: 1fr; } }
.fsr-net-left { padding: 24px 26px; }
.fsr-nline { border: 1px solid var(--line, #EEF2F2); border-radius: 14px; overflow: hidden; }
.fsr-nrow { display: flex; justify-content: space-between; align-items: center; gap: 16px; padding: 15px 18px; border-bottom: 1px solid #F3F6F6; font-size: 14px; }
.fsr-nrow:last-child { border-bottom: none; }
.fsr-nrow .fsr-lbl { color: #3A4145; }
.fsr-nrow .fsr-amt { font-variant-numeric: tabular-nums; font-weight: 500; white-space: nowrap; }
.fsr-nrow .fsr-amt.fsr-red { color: #B4462F; }
.fsr-words { font-size: 12.5px; color: #3A4145; margin-top: 14px; line-height: 1.55; background: var(--teal-soft, #f1f7f7); border: 1px solid var(--line, #EEF2F2); border-radius: 12px; padding: 13px 16px; }
.fsr-words b { color: var(--ink); font-weight: 600; }
.fsr-bankbox { display: flex; flex-direction: column; gap: 4px; }
.fsr-bankbox.fsr-warnbox { color: #B4462F; background: #fbeceb; }
.fsr-net-hero { background: var(--teal-soft, #f1f7f7); padding: 28px; display: flex; flex-direction: column; justify-content: center; border-left: 1px solid var(--line, #EEF2F2); }
@media (max-width: 900px) { .fsr-net-hero { border-left: none; border-top: 1px solid var(--line, #EEF2F2); } }
.fsr-nh-lab { font-size: 11.5px; font-weight: 600; text-transform: uppercase; letter-spacing: .6px; color: #6B7378; }
.fsr-nh-big { font-size: 36px; font-weight: 600; line-height: 1; margin-top: 9px; color: var(--teal); font-variant-numeric: tabular-nums; }
.fsr-nh-fin { display: flex; align-items: center; gap: 9px; font-size: 13px; color: #1F7A54; font-weight: 500; margin-top: 24px; padding-top: 18px; border-top: 1px solid rgba(1,70,83,.1); }
.fsr-nh-fin svg { width: 15px; height: 15px; flex: none; }
.fsr-nh-back, .fsr-nh-submit { margin-top: 18px; align-self: flex-start; background: #fff; color: #3A4145; border: 1px solid var(--line, #EEF2F2); border-radius: 11px; padding: 11px 18px; font: inherit; font-size: 13.5px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; transition: border-color .14s, color .14s; }
.fsr-nh-back:hover, .fsr-nh-submit:hover { border-color: #C7CDCF; color: var(--teal); }
.fsr-nh-back svg, .fsr-nh-submit svg { width: 15px; height: 15px; }
</style>
@endsection

@section('import-scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>
   $('#printFinalSettlement').on('click', function (e) {
        e.preventDefault();
        window.print();
    });
    $('#final-review-form').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: "{{ route('final.settlement.submit') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                final_settlement_id: $("#final_settlement_id").val(),
                // Freeze what's actually on screen right now — this is
                // what previously got computed here but silently dropped,
                // leaving the list page showing a stale total_earnings/
                // net_pay from whenever the draft was first saved.
                total_earnings: $("#totalEarningsInput").val(),
                total_deductions: $("#totalDeductionsInput").val(),
                net_pay: $("#netPayInput").val(),
                worked_days: $("#workedDaysInput").val(),
            },
            success: function(response) {
                console.log(response);
                if (response.success) {
                    toastr.success(response.message, "Success", {
                        positionClass: 'toast-bottom-right'
                    });

                    // Redirect to review
                    setTimeout(() => {
                        window.location.href = "{{ route('final.settlement.list') }}";
                    }, 2000);
                   
                } else {
                    toastr.error('Error storing final settlement!', "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            },
            error: function() {
                toastr.error('An error occurred while saving the data.', "Error", {
                    positionClass: 'toast-bottom-right'
                });
            }
        });
    });
    $('#downloadPdf').on('click', function (e) {
    e.preventDefault();

    // Hide the buttons before capturing
    $('#printFinalSettlement').hide();
    $('#downloadPdf').hide();

    const formElement = document.getElementById("final-review-form");

    html2canvas(formElement, {
        scale: 2
    }).then(canvas => {
        const imgData = canvas.toDataURL('image/png');
        const pdf = new jspdf.jsPDF('p', 'mm', 'a4');

        const pageWidth = pdf.internal.pageSize.getWidth();
        const pageHeight = pdf.internal.pageSize.getHeight();
        const imgProps = pdf.getImageProperties(imgData);
        const pdfWidth = pageWidth;
        const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;

        pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
        pdf.save('Final-Settlement.pdf');

        // Show the buttons again
        $('#printFinalSettlement').show();
        $('#downloadPdf').show();
    });
});

</script>
@endsection