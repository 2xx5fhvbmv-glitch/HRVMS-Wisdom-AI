<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Employee Repayment Details</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table th, .table td { border: 1px solid #ddd; padding: 6px; }
        .table th { background: #f4f4f4; }
        .card-title h3 { margin: 0 0 10px 0; }
        .bg-themeGrayLight { background: #f8f9fa; padding: 10px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <h2>Employee Repayment Details</h2>
    <div class="bg-themeGrayLight">
        <table class="table">
            <tbody>
                <tr><th>Employee Name:</th><td>{{ $payrollAdvance->employee->resortAdmin->full_name }}</td></tr>
                <tr><th>Employee ID:</th><td>{{ $payrollAdvance->employee->Emp_id }}</td></tr>
                <tr><th>Department:</th><td>{{ $payrollAdvance->employee->department->name }}</td></tr>
                <tr><th>Position:</th><td>{{ $payrollAdvance->employee->position->position_title }}</td></tr>
                <tr><th>Purpose:</th><td>{{ $payrollAdvance->pourpose }}</td></tr>
                <tr><th>Original Request Date:</th><td>{{ $payrollAdvance->request_date }}</td></tr>
                <tr><th>Requested Amount:</th><td>{{ $payrollAdvance->request_amount }}</td></tr>
          
                <tr><th>HR Status:</th><td>{{ $payrollAdvance->hr_status }}</td></tr>
                <tr><th>HR Approver:</th><td>{{ optional($payrollAdvance->hrApprover->resortAdmin ?? null)->full_name }}</td></tr>
                <tr><th>HR Approval Date:</th><td>{{ $payrollAdvance->hr_action_date ? \Carbon\Carbon::parse($payrollAdvance->hr_action_date)->format('d M Y') : '' }}</td></tr>
                <tr><th>Finance Status:</th><td>{{ $payrollAdvance->finance_status }}</td></tr>
                <tr><th>Finance Approver:</th><td>{{ optional($payrollAdvance->financeApprover->resortAdmin ?? null)->full_name }}</td></tr>
                <tr><th>Finance Approval Date:</th><td>{{ $payrollAdvance->finance_action_date ? \Carbon\Carbon::parse($payrollAdvance->finance_action_date)->format('d M Y') : '' }}</td></tr>
                <tr><th>GM Status:</th><td>{{ $payrollAdvance->gm_status }}</td></tr>
                <tr><th>GM Approver:</th><td>{{ optional($payrollAdvance->gmApprover->resortAdmin ?? null)->full_name }}</td></tr>
                <tr><th>GM Approval Date:</th><td>{{ $payrollAdvance->gm_action_date ? \Carbon\Carbon::parse($payrollAdvance->gm_action_date)->format('d M Y') : '' }}</td></tr>
            </tbody>
        </table>
    </div>
    <div class="bg-themeGrayLight">
        <h3>Repayment Schedule</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Month/Date</th>
                    <th>Schedule Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payrollAdvance->payrollRecoverySchedule as $schedule)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($schedule->repayment_date)->format('F Y') }}</td>
                    <td>{{ $schedule->amount }}</td>
                </tr>
                @endforeach
                <tr>
                    <th>Total</th>
                    <th>${{ $payrollAdvance->payrollRecoverySchedule->sum('amount') }}</th>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="bg-themeGrayLight">
        <h3>Deduction History</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Payroll Month</th>
                    <th>Deducted Amount</th>
                    <th>Payroll Cycle</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>April 2025</td>
                    <td>$1,250.00</td>
                    <td>April 2025</td>
                    <td>Completed</td>
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>