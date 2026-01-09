<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payslip</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            font-size: 14px;
            margin: 40px;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .section {
            margin-bottom: 30px;
        }
        .flex-container {
            display: flex;
            justify-content: space-between;
            gap: 40px;
        }
        .user-details, .summary-table {
            flex: 1;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px 10px;
            text-align: left;
        }
        th {
            background-color: #f3f3f3;
        }
        .highlight {
            font-weight: bold;
        }
        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }
        .net-amount {
            font-size: 16px;
            font-weight: bold;
            text-align: right;
            margin-top: 10px;
        }
    </style>
</head>
<body>

    <h2>Employee Payslip</h2>

    <div class="section flex-container">
        <div class="user-details">
            <p><strong>Name:</strong> {{ $employee->resortAdmin->full_name }}</p>
            <p><strong>Emp ID:</strong> {{ $employee->Emp_id }}</p>
            <p><strong>Position:</strong> {{ $employee->position->position_title ?? 'N/A' }}</p>
            <p><strong>Department:</strong> {{ $employee->department->name ?? 'N/A' }}</p>
            <p><strong>Hire Date:</strong> {{ \Carbon\Carbon::parse($employee->joining_date)->format('d M Y') }}</p>
        </div>

        <div class="summary-table">
            <table>
                <tr><th>Days Worked</th><td>{{ $serviceCharge?->total_working_days ?? 0 }}</td></tr>
                <tr><th>Salary Period</th><td>{{ \Carbon\Carbon::parse($payroll->start_date)->format('d M Y') }} to {{ \Carbon\Carbon::parse($payroll->end_date)->format('d M Y') }}</td></tr>
                <tr><th>Service Charge Days</th><td>{{ $serviceCharge?->total_working_days ?? 0 }}</td></tr>
            </table>
        </div>
    </div>

    <div class="section flex-container">
        <div class="earnings">
            <h3>Earnings</h3>
            <table>
                <thead>
                    <tr><th>Description</th><th>USD</th></tr>
                </thead>
                <tbody>
                    <tr><td>Earned Salary</td><td>{{ number_format($earnedSalary, 2) }}</td></tr>

                    @foreach($review->allowances ?? [] as $allowance)
                        @php
                            $amount = $allowance->amount;
                            if ($allowance->amount_unit == "MVR") {
                                $amount = Common::RateConversion("MVRtoDoller", $amount, $payroll->resort_id);
                            }
                        @endphp
                        <tr><td>{{ $allowance->allowance_type }}</td><td>{{ number_format($amount, 2) }}</td></tr>
                    @endforeach

                    <tr><td>Fixed Allowance</td><td>{{ number_format($review?->earnings_allowance, 2) }}</td></tr>
                    <tr><td>Service Charge</td><td>{{ number_format($review?->service_charge, 2) }}</td></tr>
                    <tr><td>Total OT Amount</td><td>{{ number_format($review?->earnings_overtime, 2) }}</td></tr>
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td>Total Earnings</td>
                        <td>{{ number_format($review?->total_earnings, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="deductions">
            <h3>Deductions</h3>
            <table>
                <thead>
                    <tr><th>Description</th><th>USD</th></tr>
                </thead>
                <tbody>
                    <tr><td>Monthly Tax Deduction</td><td>{{ number_format($deductions->ewt, 2) }}</td></tr>
                    <tr><td>Staff Shop</td><td>{{ number_format($deductions->staff_shop, 2) }}</td></tr>
                    <tr><td>MRPS Employee Mandatory Contribution</td><td>{{ number_format($deductions->pension, 2) }}</td></tr>
                    <tr><td>Attendance Deduction</td><td>{{ number_format($deductions->attendance_deduction, 2) }}</td></tr>
                    <tr><td>City Ledger</td><td>{{ number_format($deductions->city_ledger, 2) }}</td></tr>
                    <tr><td>Advance Loan / Salary Repayment</td><td>{{ number_format($deductions->city_ledger, 2) }}</td></tr>
                    <tr><td>Other</td><td>{{ number_format($deductions->other, 2) }}</td></tr>
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td>Total Deductions</td>
                        <td>{{ number_format($deductions->total_deductions, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

  

    @if($employee?->payment_mode == 'Bank')
    <div class="section">
        <h3>Bank Details</h3>
        <table>
            <tr><th>Bank</th><td>{{ $employee?->bankDetails->first()?->bank_name ?? 'N/A' }}</td></tr>
            <tr><th>Account No.</th><td>{{ $employee?->bankDetails->first()?->account_no ?? 'N/A' }}</td></tr>
        </table>
    </div>
    @endif

</body>
</html>
