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

                    <div class="row g-md-4 g-3 mb-3">
                        <div class="col-md-6">
                            <div class="paySlip-block p-0">
                                <div class="table-responsive">
                                    <div class="paySlip-header">Earning</div>
                                    <div class="paySlip-body">
                                        <table class="paySlipBorder-table">
                                            <thead>
                                                <tr>
                                                    <th>Payable Leaves</th>
                                                    <th>Days</th>
                                                    <th>Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            @php
                                                $totalLeaveSalary = 0;
                                                $totalEarnings = 0;
                                            @endphp

                                            @if($leaveBalances['details'] && count($leaveBalances['details']) > 0)
                                                @foreach($leaveBalances['details'] as $balance)
                                                    @php
                                                        $leaveSalary = $balance['available_days'] * $calculated['daily_salary'];
                                                        $totalLeaveSalary += $leaveSalary;
                                                    @endphp
                                                    <tr>
                                                        <td>{{ $balance['leave_type'] }}</td>
                                                        <td>{{ $balance['available_days'] }}</td>
                                                        <td>MVR {{ number_format($leaveSalary, 2) }}</td>
                                                    </tr>
                                                @endforeach
                                            @endif

                                            <tr>
                                                <td>Leave Encashment</td>
                                                <td>{{ $leaveBalances['total_days'] ?? 0 }} days</td>
                                                <td>MVR {{ number_format($calculated['leave_encashment'], 2) }}</td>
                                            </tr>

                                            <tr>
                                                <td>Basic Salary</td>
                                                <td>{{ $finalSettlement->total_days }} days</td>
                                                <td>MVR {{ number_format($finalSettlement->proratedBasic, 2) }}</td>
                                            </tr>

                                            @php
    
                                                $totalEarnings += $calculated['leave_encashment'];
                                                $totalEarnings += $finalSettlement->proratedBasic;
                                            @endphp

                                            @if($finalSettlement->earnings)
                                                @foreach($finalSettlement->earnings as $earnings)
                                                    @php
                                                        $totalEarnings += $earnings->amount;
                                                    @endphp
                                                    <tr>
                                                        <td>{{ $earnings->earning->allowanceName->particulars }}</td>
                                                        <td>-</td>
                                                        <td>MVR {{ number_format($earnings->amount, 2) }}</td>
                                                    </tr>
                                                @endforeach
                                            @endif

                                            <tr>
                                                    <td>Regular OT</td>
                                                    <td>{{ $calculated['regular_ot_hours'] }} hrs</td>
                                                    <td>MVR {{ number_format($calculated['regular_ot_amount'], 2) }}</td>
                                                </tr>
                                                @php $totalEarnings += $calculated['regular_ot_amount']; @endphp

                                                <tr>
                                                    <td>Holiday OT</td>
                                                    <td>{{ $calculated['holiday_ot_hours'] }} hrs</td>
                                                    <td>MVR {{ number_format($calculated['holiday_ot_amount'], 2) }}</td>
                                                </tr>
                                                @php $totalEarnings += $calculated['holiday_ot_amount']; @endphp

                                                <tr>
                                                    <td>Total OT</td>
                                                    <td>{{ $calculated['holiday_ot_hours'] + $calculated['regular_ot_hours'] }} hrs</td>
                                                    <td>MVR {{ number_format($calculated['total_ot_amount'], 2) }}</td>
                                                </tr>

                                                <tr>
                                                    <td>Ramadan Bonus</td>
                                                    <td>-</td>
                                                    <td>MVR {{ number_format($calculated['ramadan_bonus'], 2) }}</td>
                                                </tr>
                                                @php $totalEarnings += $calculated['ramadan_bonus']; @endphp

                                                <tr>
                                                    <td>Service Charge</td>
                                                    <td>-</td>
                                                    <td>MVR {{ number_format($finalSettlement->service_charge, 2) }}</td>
                                                </tr>
                                                @php $totalEarnings += $finalSettlement->service_charge; @endphp
                                            </tbody>

                                            <tfoot>
                                                <tr>
                                                    <td colspan="2">Total Gross Pay</td>
                                                    <td>MVR {{ number_format($totalEarnings, 2) }}</td>
                                                </tr>
                                            </tfoot>

                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="paySlip-block p-0">
                                <div class="paySlip-header">Deductions</div>
                                <div class="paySlip-body">
                                    <div class="table-responsive">
                                        <table class="paySlipBorder-table">
                                            <thead>
                                                <tr>
                                                    <th>Settlement Details</th>
                                                    <th>MVR</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $totalDeductions = 0;
                                                @endphp
                                                
                                                @if(isset($calculated['ewt']) && $calculated['ewt'] > 0)
                                                    @php $totalDeductions += $calculated['ewt']; @endphp
                                                    <tr>
                                                        <td>Employee Withholding Tax (EWT)</td>
                                                        <td>{{ number_format($calculated['ewt'], 2) }}</td>
                                                    </tr>
                                                @endif
                                                
                                                @if(isset($calculated['pension']) && $calculated['pension'] > 0)
                                                    @php $totalDeductions += $calculated['pension']; @endphp
                                                    <tr>
                                                        <td>Pension Contribution</td>
                                                        <td>{{ number_format($calculated['pension'], 2) }}</td>
                                                    </tr>
                                                @endif
                                                
                                                @if(isset($calculated['loan_recovery']) && $calculated['loan_recovery'] > 0)
                                                    @php $totalDeductions += $calculated['loan_recovery']; @endphp
                                                    <tr>
                                                        <td>Loan Recovery</td>
                                                        <td>{{ number_format($calculated['loan_recovery'], 2) }}</td>
                                                    </tr>
                                                @endif
                                                
                                                @if($finalSettlement->deductions)
                                                    @foreach($finalSettlement->deductions as $deductions)
                                                        @php $totalDeductions += $deductions->amount; @endphp
                                                        <tr>
                                                            <td>{{ $deductions->deduction->deduction_name }}</td>
                                                            <td>{{ number_format($deductions->amount, 2) }}</td>
                                                        </tr>
                                                    @endforeach
                                                @endif
                                                
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <td>Total Deductions</td>
                                                    <td id="totalDeductions">MVR{{ number_format($totalDeductions, 2) }}</td>  
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
                                    <tr><th>Net Pay</th><th>Amount</th></tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Gross Earning</td>
                                        <td class="fw-600" id="grossEarnings">MVR {{ number_format($totalEarnings, 2) }}</td>
                                        <input type="hidden" name="total_earnings" id="totalEarningsInput" value="{{ number_format($totalEarnings, 2) }}">
                                    </tr>
                                    <tr>
                                        <td>Total Deductions</td>
                                        <td class="fw-600" id="totalDeductions1">(-) MVR {{ number_format($totalDeductions, 2) }}</td>
                                        <input type="hidden" name="total_deductions" id="totalDeductionsInput" value="{{ number_format($totalDeductions, 2) }}">
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td>Total Net Payable</td>
                                        <td id="netPayable">MVR {{ number_format($totalEarnings - $totalDeductions, 2) }}</td>
                                        <input type="hidden" name="net_pay" id="netPayInput" value="{{ number_format($totalEarnings - $totalDeductions, 2) }}">
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
                        <span id="netPayWords"> MVR {{ convertToWords($totalEarnings - $totalDeductions) }} Only </span>
                    </div>
                    @if($finalSettlement->employee->payment_mode == 'Bank')
                        <div class="bg-themeGrayLight d-flex mb-md-4 mb-3">
                            <div><span class="fw-600">Bank Name:</span> Bank Of Maldives</div>
                            <div><span class="fw-600">Account Number:</span> 154210145545</div>
                        </div>
                    @endif

                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-themeBlue">Submit</button>
                    </div>
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