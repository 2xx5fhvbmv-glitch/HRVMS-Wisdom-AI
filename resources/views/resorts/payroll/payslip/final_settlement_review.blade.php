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
                                    <h4>{{$finalSettlement->employee->resortAdmin->full_name}} <span class="badge badge-themeLight">{{$finalSettlement->Emp_id}}</span></h4>
                                    <div class="table-responsive">
                                        <table class="paySlip-table">
                                            <tr><th>Position:</th><td>{{$finalSettlement->employee->position->position_title}}</td></tr>
                                            <tr><th>Department:</th><td>{{$finalSettlement->employee->department->name}}</td></tr>
                                            <tr><th>Division:</th><td>{{$finalSettlement->employee->division->name}}</td></tr>
                                            <tr><th>Basic Salary:</th><td>{{$finalSettlement->basic_salary}} MVR</td></tr>
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
                                        <tr><th>Last Working Date:</th><td>{{ \Carbon\Carbon::parse($finalSettlement->last_working_date)->format('d M Y') }}</td></tr>
                                        <tr><th>Payroll Month:</th><td>{{ \Carbon\Carbon::parse($calculated['payroll_start'])->format('F') }}</td></tr>
                                        <tr><th>Reason:</th><td>{{$finalSettlement->employee->resignation->reason_title->reason}}</td></tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-auto ms-auto">
                            <a href="#" class="btn btn-themeSkyblue btn-sm" id="printFinalSettlement">Print</a>
                        </div>
                        <div class="col-auto">
                            <a href="#" class="btn btn-themeBlue btn-sm" id="downloadPdf">Download</a>
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
                        // Display currency for this settlement. The F&F page stores
                        // values in MVR (the resort's payroll currency); preserved
                        // as-is on the review page so totals match.
                        $payCurrency = 'MVR';

                        // Build per-leave-category rows once so both totals on the
                        // left ("Total Earnings") and the right ("Leave Days Salary"
                        // line) are derived from the same source — no math drift.
                        $leaveRows = [];
                        $leaveDaysSalaryTotal = 0;
                        if (!empty($leaveBalances['details'])) {
                            foreach ($leaveBalances['details'] as $b) {
                                $amount = ($b['available_days'] ?? 0) * ($calculated['daily_salary'] ?? 0);
                                $leaveDaysSalaryTotal += $amount;
                                $leaveRows[] = [
                                    'leave_type' => $b['leave_type'] ?? 'Leave',
                                    'days'       => $b['available_days'] ?? 0,
                                    'amount'     => $amount,
                                ];
                            }
                        }
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
                                                    <th>Payable Leaves</th>
                                                    <th class="text-end">Days</th>
                                                    <th class="text-end">Amount ({{ $payCurrency }})</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php $totalEarnings = 0; @endphp
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

                                                {{-- Air Ticket Reimbursement + any other custom
                                                     earnings the F&F submission attached
                                                     (relocation ticket, joining bonus, etc.).
                                                     `$finalSettlement->earnings` is the
                                                     final_settlement_earnings rows keyed to
                                                     resort_budget_costs.particulars. --}}
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

                        {{-- ─────────── DEDUCTIONS / SETTLEMENT DETAILS card (right) ─────────── --}}
                        <div class="col-md-6">
                            <div class="paySlip-block p-0">
                                <div class="paySlip-header">Deductions</div>
                                <div class="paySlip-body">
                                    <div class="table-responsive">
                                        <table class="paySlipBorder-table">
                                            <thead>
                                                <tr>
                                                    <th>Settlement Details</th>
                                                    <th class="text-end">{{ $payCurrency }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {{-- Build-up of Gross Pay. Each row mirrors a
                                                     line on the F&F submit form so HR can
                                                     reconcile the review against what they
                                                     entered. --}}
                                                @php
                                                    $workedDays      = (int) ($calculated['worked_days'] ?? 0);
                                                    $proratedBasic   = (float) ($finalSettlement->proratedBasic ?? $calculated['proratedBasic'] ?? 0);
                                                    $serviceCharge   = (float) ($finalSettlement->service_charge ?? 0);
                                                    $totalAllowance  = (float) ($calculated['total_allowances_mvr'] ?? 0);
                                                    $grossPay        = $proratedBasic + $serviceCharge + $leaveDaysSalaryTotal + $totalAllowance;

                                                    $ewt             = (float) ($calculated['ewt'] ?? 0);
                                                    $pension         = (float) ($calculated['pension'] ?? 0);
                                                    $loanRecovery    = (float) ($calculated['loan_recovery'] ?? 0);
                                                    $noticeCharge    = (float) ($calculated['notice_period_charge_mvr'] ?? 0);
                                                @endphp
                                                <tr>
                                                    <td>
                                                        Basic Salary For
                                                        <span class="badge bg-light text-dark ms-1">{{ $workedDays }} Days</span>
                                                    </td>
                                                    <td class="text-end">{!! Common::formatCurrency($proratedBasic, $payCurrency) !!}</td>
                                                </tr>
                                                <tr>
                                                    <td>Service Charge Amount</td>
                                                    <td class="text-end">{!! Common::formatCurrency($serviceCharge, $payCurrency) !!}</td>
                                                </tr>
                                                <tr>
                                                    <td>Leave Days Salary</td>
                                                    <td class="text-end">{!! Common::formatCurrency($leaveDaysSalaryTotal, $payCurrency) !!}</td>
                                                </tr>
                                                <tr>
                                                    <td>Total Allowance Amount</td>
                                                    <td class="text-end">{!! Common::formatCurrency($totalAllowance, $payCurrency) !!}</td>
                                                </tr>
                                                <tr class="fw-600">
                                                    <td>Gross Pay</td>
                                                    <td class="text-end">{!! Common::formatCurrency($grossPay, $payCurrency) !!}</td>
                                                </tr>

                                                {{-- ── Actual deductions ──
                                                     EWHT, Pension, Loan/Advance recovery,
                                                     Notice Period charge, and any custom
                                                     deductions HR added during the F&F submit
                                                     (Notice Period Charge, Adjustments-Deduction,
                                                     City Ledger Deduction, etc.). --}}
                                                @php $totalDeductions = 0; @endphp
                                                <tr>
                                                    <td>EWHT - Total Taxable Income</td>
                                                    <td class="text-end">{!! Common::formatCurrency($ewt, $payCurrency) !!}</td>
                                                </tr>
                                                @php $totalDeductions += $ewt; @endphp

                                                @if($pension > 0)
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
                        function convertToWords($number) {
                            $formatter = new NumberFormatter('en', NumberFormatter::SPELLOUT);
                            $whole = floor($number);
                            $fraction = round(($number - $whole) * 100);
                            
                            $wholeWords = ucfirst($formatter->format($whole));
                            
                            if ($fraction > 0) {
                                return $wholeWords . ' point ' . $formatter->format($fraction);
                            } else {
                                return $wholeWords;
                            }
                        }
                    @endphp

                    <div class="bg-themeGrayLight mb-2">
                        <span class="fw-600">Total Net payable: </span>&nbsp;
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
                                <a href="{{ route('final.settlement.list') }}" class="btn btn-sm btn-outline-secondary">
                                    Back to list
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-themeBlue">Submit</button>
                        </div>
                    @endif
                </div>
            </form>

        </div>
    </div>
@endsection

@section('import-css')
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