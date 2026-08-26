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
                <div class="card card-fianlSettlement">
                    @csrf
                    <input type="hidden" name="payment_mode" id="paymentModeInput" value="{{$finalSettlement->payment_mode}}">

                    <div class="row g-2">
                        <div class="col-xxl-5 col-xl-5 col-lg-6">
                            <div class="paySlip-user">
                                <div class="img-obj cover">
                                    <img src="{{ Common::getResortUserPicture($finalSettlement->employee->Admin_Parent_id)}}" alt="image">
                                </div>
                                <div>
                                    {{-- Employee ID was reading $finalSettlement->Emp_id, which
                                         doesn't exist on the final_settlements table — the value
                                         lives on the related employee row. --}}
                                    <h4>{{$finalSettlement->employee->resortAdmin->full_name}} <span class="badge badge-themeLight">{{$finalSettlement->employee->Emp_id}}</span></h4>
                                    <div class="table-responsive">
                                        <table class="paySlip-table">
                                            <tr><th>Position:</th><td>{{$finalSettlement->employee->position->position_title}}</td></tr>
                                            <tr><th>Department:</th><td>{{$finalSettlement->employee->department->name}}</td></tr>
                                            <tr><th>Division:</th><td>{{$finalSettlement->employee->division->name}}</td></tr>
                                            {{-- Basic Salary currency was hardcoded MVR even though
                                                 employees on USD payroll have their basic stored in
                                                 USD on the employee row. Show the employee's actual
                                                 stored basic + their basic_salary_currency. --}}
                                            <tr><th>Basic Salary:</th><td>{{ number_format((float) ($finalSettlement->employee->basic_salary ?? 0), 2) }} {{ $finalSettlement->employee->basic_salary_currency ?? 'MVR' }}</td></tr>
                                            <tr><th>Payroll Start Date:</th><td>{{$calculated['payroll_start']}}</td></tr>
                                            <tr><th>Remarks:</th><td></td></tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xxl-4 col-xl-4 col-lg-6 col-sm">
                            <div class="bg-themeGrayLight h-100">
                                <div class="table-responsive">
                                    <table class="paySlip-table">
                                        <tr><th>Doc Date:</th><td>{{ \Carbon\Carbon::parse($today)->format('d M Y') }}</td></tr>
                                        <tr><th>Reference No.</th><td>{{$finalSettlement->reference_no}}</td></tr>
                                        <tr><th>Pay Mode:</th><td>{{$finalSettlement->employee->payment_mode}}</td></tr>
                                        <tr><th>Hire Date:</th><td>{{ \Carbon\Carbon::parse($finalSettlement->employee->joining_date)->format('d M Y') }}</td></tr>
                                        {{-- final_settlements.last_working_date is nullable and
                                             reaches the page as NULL when the F&F store didn't
                                             receive a parseable value (Carbon::parse(null) then
                                             renders as "30 Nov -0001"). Fall back to the
                                             employee's resignation last_working_day, which is
                                             always present at this point in the lifecycle. --}}
                                        @php
                                            $lwd = $finalSettlement->last_working_date
                                                ?: optional($finalSettlement->employee->resignation)->last_working_day;
                                        @endphp
                                        <tr><th>Last Working Date:</th><td>{{ $lwd ? \Carbon\Carbon::parse($lwd)->format('d M Y') : '—' }}</td></tr>
                                        {{-- Payroll Month was the month the payroll WINDOW starts
                                             (Apr for a 25-Apr → 24-May cycle). HR reads "Payroll
                                             Month" as the month of the last working day (when the
                                             employee actually left), which matches the payslip
                                             convention. Fall back to payroll_start if no LWD. --}}
                                        <tr><th>Payroll Month:</th><td>{{ $lwd ? \Carbon\Carbon::parse($lwd)->format('F') : \Carbon\Carbon::parse($calculated['payroll_start'])->format('F') }}</td></tr>
                                        <tr><th>Reason:</th><td>{{$finalSettlement->employee->resignation->reason_title->reason}}</td></tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-auto ms-auto">
                            <a href="#" class="btn payroll-btn-secondary btn-sm" id="printFinalSettlement">Print</a>
                        </div>
                        <div class="col-auto">
                            <a href="#" class="btn payroll-btn-secondary btn-sm" id="downloadPdf">Download</a>
                        </div>
                    </div>
                    <hr>

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

                    <div class="row g-md-4 g-3 mb-3">
                        {{-- ─────────── EARNINGS card (left) ─────────── --}}
                        <div class="col-md-6">
                            <div class="paySlip-block p-0">
                                <div class="paySlip-header">Earnings</div>
                                <div class="paySlip-body">
                                    <div class="table-responsive">
                                        <table class="paySlipBorder-table">
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
                                </div>
                            </div>
                        </div>

                        {{-- ─────────── DEDUCTIONS card (right) ───────────
                             Strictly the actual deductions HR posted (EWHT, MRPS,
                             Loan, Notice, custom). Basic / Service / Allowance
                             now live in the Earnings card where they belong. --}}
                        <div class="col-md-6">
                            <div class="paySlip-block p-0">
                                <div class="paySlip-header">Deductions</div>
                                <div class="paySlip-body">
                                    <div class="table-responsive">
                                        <table class="paySlipBorder-table">
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
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="paySlip-block h-auto mb-3">
                        <div class="table-responsive">
                            <table class="paySlipBorder-table">
                                <thead>
                                    <tr><th>Net Pay</th><th class="text-end">Amount</th></tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Gross Earnings</td>
                                        <td class="fw-600 text-end" id="grossEarnings">{!! Common::formatCurrency($totalEarnings, $payCurrency) !!}</td>
                                        <input type="hidden" name="total_earnings" id="totalEarningsInput" value="{{ number_format($totalEarnings, 2, '.', '') }}">
                                        <input type="hidden" name="worked_days" id="workedDaysInput" value="{{ $workedDays }}">
                                    </tr>
                                    <tr>
                                        <td>Total Deductions</td>
                                        <td class="fw-600 text-end" id="totalDeductions1">(-) {!! Common::formatCurrency($totalDeductions, $payCurrency) !!}</td>
                                        <input type="hidden" name="total_deductions" id="totalDeductionsInput" value="{{ number_format($totalDeductions, 2, '.', '') }}">
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th>Total Net Payable</th>
                                        <th class="text-end" id="netPayable">{!! Common::formatCurrency($totalEarnings - $totalDeductions, $payCurrency) !!}</th>
                                        <input type="hidden" name="net_pay" id="netPayInput" value="{{ number_format($totalEarnings - $totalDeductions, 2, '.', '') }}">
                                        <input type="hidden" name="final_settlement_id" id="final_settlement_id" value="{{ $finalSettlement->id }}">
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
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

                    <div class="bg-themeGrayLight mb-2">
                        <span class="fw-600">Total Net Payable: </span>&nbsp;
                        <span id="netPayWords">{{ $payCurrency }} {{ convertToWords($totalEarnings - $totalDeductions) }} Only</span>
                    </div>
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
                            <div class="bg-themeGrayLight d-flex mb-md-4 mb-3">
                                <div class="me-4"><span class="fw-600">Bank Name:</span> {{ $bank->bank_name ?? '—' }}</div>
                                <div><span class="fw-600">Account Number:</span> {{ $bank->account_number ?? '—' }}</div>
                            </div>
                        @else
                            <div class="bg-themeGrayLight d-flex mb-md-4 mb-3 text-warning">
                                <i class="fa-solid fa-circle-info me-2"></i>
                                Bank payment selected but no bank account is on file for this employee.
                            </div>
                        @endif
                    @endif

                    {{-- Lock banner once the settlement is finalized. Replaces the
                         Submit button with a read-only badge, prevents accidental
                         re-finalize (the controller also rejects POST in that case,
                         but the UI guard avoids the round-trip). --}}
                    @if($finalSettlement->status === 'finalized')
                        <div class="card-footer">
                            <div class="alert alert-success mb-0 py-2 px-3 d-flex align-items-center" role="alert" style="font-size:13px;">
                                <i class="fa-solid fa-lock me-2"></i>
                                <div class="flex-grow-1">
                                    <strong>Settlement Finalized.</strong>
                                    Locked from further edits.
                                    @if(!empty($finalSettlement->finalized_at))
                                        Finalized on
                                        <strong>{{ \Carbon\Carbon::parse($finalSettlement->finalized_at)->format('d M Y H:i') }}</strong>.
                                    @endif
                                </div>
                                <a href="{{ route('final.settlement.list') }}" class="btn btn-sm payroll-btn-secondary">
                                    Back to list
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="card-footer text-end">
                            <button type="submit" class="btn payroll-btn-critical">Submit</button>
                        </div>
                    @endif
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

    .btn, .card-footer, .navbar, .sidebar {
        display: none !important;
    }
}
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