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
                <div class="col-auto ms-auto">
                    <a href="#" class="btn btn-theme" id="printPayslip" onclick="printPaySlip()">Print</a>
                </div>
            </div>
        </div>

        <div class="card card-paySlip" id="payslipContent">
            <div class="row g-2">
                <div class="col-xxl-6 col-xl-6 col-lg-6">
                    @php
                        $empRecord = $payroll->employees->first();
                        $employee = $empRecord?->employee;
                    @endphp

                    @if($employee)
                        <div class="paySlip-user">
                            <div class="img-obj cover">
                                <img src="{{ Common::getResortUserPicture($employee->Admin_Parent_id) }}" alt="image">
                            </div>
                            <div>
                                <h4>{{ $employee->resortAdmin->full_name }}
                                    <span class="badge badge-themeLight">{{ $empRecord->Emp_id }}</span>
                                </h4>
                                <div class="table-responsive">
                                    <table class="paySlip-table">
                                        <tr>
                                            <th>Position:</th>
                                            <td>{{ $employee->position->position_title ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Department:</th>
                                            <td>{{ $employee->department->name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Hire Date:</th>
                                            <td>{{ \Carbon\Carbon::parse($employee->joining_date)->format('d M Y') }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                </div>
                @php
                    $serviceCharge = $payroll->serviceCharges->first();
                @endphp

                <div class="col-xxl-6 col-xl-6 col-lg-6 col-sm">
                    <div class="bg-themeGrayLight h-100">
                        <div class="table-responsive">
                            <table class="paySlip-table">
                                <tr>
                                    <th>Days Worked:</th>
                                    <td>{{ $serviceCharge?->total_working_days ?? 0 }}</td>
                                </tr>
                                <tr>
                                    <th>Salary period:</th>
                                    <td>{{ \Carbon\Carbon::parse($payroll->start_date)->format('d M Y') }} to {{ \Carbon\Carbon::parse($payroll->end_date)->format('d M Y') }}</td>
                                </tr>
                                <tr>
                                    <th>Service Charge Days:</th>
                                    <td>{{ $serviceCharge?->total_working_days ?? 0 }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
            <hr>
            <div class="row g-md-4 g-3 mb-2">
                <div class="col-md-6">
                    <div class="paySlip-block">
                        <div class="table-responsive">
                            <table class="paySlipBorder-table">
                                <thead>
                                    <tr>
                                        <th>Earnings</th>
                                        <th>US$</th>
                                    </tr>
                                </thead>
                                @php
                                    $review = $payroll->reviews->first();
                                @endphp

                                <tbody>
                                    <tr>
                                        <td>Earned Salary</td>
                                        @php
                                            $earnedSalary = $review?->earned_salary ?? 0;
                                            if ($payroll->payroll_unit == "MVR") {
                                                $amoearnedSalaryunt = Common::RateConversion("MVRtoDoller", $earnedSalary,$payroll->resort_id);
                                            }
                                            @endphp
                                        <td>{{ $earnedSalary ?? 0 }}</td>
                                    </tr>

                                    @if($review && !$review->allowances->isEmpty())
                                        @foreach($review->allowances as $allowance)
                                            @php 
                                                $amount = $allowance->amount;
                                                
                                                // Convert MVR to USD if needed
                                                if ($allowance->amount_unit == "MVR") {
                                                    $amount = Common::RateConversion("MVRtoDoller", $amount,$payroll->resort_id);
                                                   
                                                }
                                            @endphp
                                            <tr>
                                                <td>{{ $allowance->allowance_type }}</td>
                                                <td>{{ number_format($amount, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    @endif

                                    <tr>
                                        <td>Fixed Allowance</td>
                                        <td>{{ $review?->earnings_allowance ?? 0 }}</td>
                                    </tr>
                                    <tr>
                                        <td>Service charge</td>
                                        <td>{{ $review?->service_charge ?? 0 }}</td>
                                    </tr>
                                    <tr>
                                        <td>Total OT Amount</td>
                                        <td>{{ $review?->earnings_overtime ?? 0 }}</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td>Total Earnings</td>
                                        <td>{{ $review?->total_earnings ?? 0 }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="paySlip-block">
                        <div class="table-responsive">
                            <table class="paySlipBorder-table">
                                <thead>
                                    <tr>
                                        <th>Deductions</th>
                                        <th>US$</th>
                                    </tr>
                                </thead>
                                 @php
                                    $deductions = $payroll->deductions->first();
                                @endphp
                                <tbody>
                                    <tr>
                                        <td>Monthly Tax Deduction</td>
                                        <td>{{$deductions->ewt}}</td>
                                    </tr>
                                    <tr>
                                        <td>Staff Shop</td>
                                        <td>{{$deductions->staff_shop}}</td>
                                    </tr>
                                    <tr>
                                        <td>MRPS Employee Mandatory Contribution</td>
                                        <td>{{$deductions->pension}}</td>
                                    </tr>
                                    <tr>
                                        <td>Attendance Deduction</td>
                                        <td>{{$deductions->attendance_deduction}}</td>
                                    </tr>
                                    <tr>
                                        <td>City Ladger</td>
                                        <td>{{$deductions->city_ledger}}</td>
                                    </tr>
                                    <tr>
                                        <td>Advance Loan / Salary Reapayment</td>
                                        <td>{{$deductions->city_ledger}}</td>
                                    </tr>
                                    <tr>
                                        <td>Other</td>
                                        <td>{{$deductions->other}}</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td>Total Deductions</td>
                                        <td>{{$deductions->total_deductions}}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @php
                $review = $payroll->reviews->first();
                $net_salary = $review?->net_salary ?? 0;

                $employee = $payroll->employees->first()?->employee;

                function convertToWords($number) {
                    $formatter = new NumberFormatter('en', NumberFormatter::SPELLOUT);
                    return ucfirst($formatter->format($number));
                }
            @endphp

            @if($employee?->payment_mode == 'Bank')
                <div class="bankDetail-block mb-2">
                    <div class="table-responsive">
                        <table>
                            <tr>
                                <th>Bank:</th>
                                <td>{{ $employee?->bankDetails->first()?->bank_name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Account No.</th>
                                <td>{{ $employee?->bankDetails->first()?->account_no ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Total Amount:</th>
                                <td><b>{{ number_format($net_salary, 2) }}</b></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="bg-themeGrayLight">
                    USD {{ convertToWords($net_salary) }} Only
                </div>
            @endif

        </div>

    </div>
</div>
@endsection

@section('import-css')

@endsection

@section('import-scripts')
<script>
    document.getElementById("printPayslip").addEventListener("click", function (e) {
        $("#payslipContent").print();
    });
</script>
@endsection