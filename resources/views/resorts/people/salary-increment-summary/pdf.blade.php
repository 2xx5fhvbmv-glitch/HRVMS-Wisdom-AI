<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salary Increment Summary</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            table-layout: fixed;
        }
        .table th, .table td {
            border: 1px solid #ddd;
            padding: 3px 2px;
            text-align: left;
            vertical-align: top;
            word-break: break-word;
        }
        .table th {
            background-color: #f4f4f4;
            font-weight: bold;
        }
        .col-empid { width: 5%; }
        .col-name { width: 12%; }
        .col-position { width: 10%; }
        .col-dept { width: 10%; }
        .col-curr-sal, .col-new-sal, .col-increment { width: 7%; }
        .col-increment-type { width: 7%; }
        .col-effective { width: 8%; }
        .col-remarks { width: 12%; }
        .col-fin-status, .col-gm-status { width: 5%; }
        .col-fin-remark, .col-gm-remark { width: 12%; }
        .summary-section {
            margin-bottom: 10px;
        }
        .summary-section h6 {
            margin: 0 0 2px 0;
            font-size: 10px;
        }
        .summary-section strong {
            font-size: 10px;
        }
    </style>
</head>
<body>
    @php
        $dateFormat = Common::getDateFormateFromSettings();
    @endphp
    <div class="summary-section">
        <h6>Current Basic Salary (Monthly): <strong>${{ number_format($currentBasicSalary, 2) }}</strong></h6>
        <h6>New Basic Salary (Monthly): <strong>${{ number_format($newBasicSalary, 2) }}</strong></h6>
        <h6>Monthly Payroll Increase: <strong>${{ number_format($monthlyPayrollIncrease, 2) }}</strong></h6>
        <h6>Annual Payroll Increase: <strong>${{ number_format($annualPayrollIncrease, 2) }}</strong></h6>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th class="col-empid">Emp ID</th>
                <th class="col-name">Employee Name</th>
                <th class="col-position">Position</th>
                <th class="col-dept">Department</th>
                <th class="col-curr-sal">Current Salary</th>
                <th class="col-new-sal">New Salary</th>
                <th class="col-increment">Increment</th>
                <th class="col-increment-type">Increment Type</th>
                <th class="col-effective">Effective Date</th>
                <th class="col-remarks">Remarks</th>
                <th class="col-fin-status">Finance Status</th>
                <th class="col-fin-remark">Finance Remark</th>
                <th class="col-gm-status">GM Status</th>
                <th class="col-gm-remark">GM Remark</th>
                <th class="col-gm-remark">Last Increment Amount</th>
                <th class="col-gm-remark">Last Increment Type</th>
                <th class="col-gm-remark">Last Increment Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $row)
                <tr>
                    <td>{{ $row->employee->Emp_id }}</td>
                    <td>{{ $row->employee->resortAdmin->full_name }}</td>
                    <td>{{ $row->employee->position->position_title }}</td>
                    <td>{{ $row->employee->department->name }}</td>
                    <td>${{ number_format($row->previous_salary, 2) }}</td>
                    <td>${{ number_format($row->new_salary, 2) }}</td>
                    <td>${{ number_format($row->increment_amount, 2) }}</td>
                    <td>{{ $row->increment_type }}</td>
                    <td>{{ \Carbon\Carbon::parse($row->effective_date)->format($dateFormat) }}</td>
                    <td>{{ $row->remarks ?? " "}}</td>
                    <td>{{ @$row->peopleSalaryIncrementStatusFinance->status}}</td>
                    <td>{{ @$row->peopleSalaryIncrementStatusFinance->remarks}}</td>
                    <td>{{ @$row->peopleSalaryIncrementStatusGM->status}}</td>
                    <td>{{ @$row->peopleSalaryIncrementStatusGM->remarks}}</td>
                    <td>{{ @$row->employee->last_increment_salary_amount}}</td>
                    <td>{{ @$row->employee->last_salary_increment_type}}</td>
                    <td>{{ @$row->employee->incremented_date}}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>