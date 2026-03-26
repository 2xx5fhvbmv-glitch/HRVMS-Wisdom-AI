<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payroll Review - {{ $payroll->start_date }} to {{ $payroll->end_date }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #333; }
        h2 { text-align: center; margin-bottom: 5px; font-size: 16px; }
        .period { text-align: center; color: #666; margin-bottom: 15px; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #0d6efd; color: #fff; padding: 6px 4px; text-align: center; font-size: 8px; white-space: nowrap; }
        td { padding: 5px 4px; border-bottom: 1px solid #e0e0e0; text-align: right; font-size: 8px; }
        td:nth-child(1), td:nth-child(2), td:nth-child(3), td:nth-child(4) { text-align: left; }
        tr:nth-child(even) { background: #f8f9fa; }
        .total-row { font-weight: bold; background: #e8f0fe !important; border-top: 2px solid #0d6efd; }
        .section-header { background: #f0f4ff; font-weight: bold; text-align: center; font-size: 8px; }
    </style>
</head>
<body>
    <h2>Payroll Review Report</h2>
    <div class="period">Period: {{ \Carbon\Carbon::parse($payroll->start_date)->format('d M Y') }} - {{ \Carbon\Carbon::parse($payroll->end_date)->format('d M Y') }}</div>

    <table>
        <thead>
            <tr>
                <th rowspan="2">ID</th>
                <th rowspan="2">Employee</th>
                <th rowspan="2">Department</th>
                <th rowspan="2">Position</th>
                <th colspan="2" class="section-header">Attendance</th>
                <th colspan="3" class="section-header">Overtime (Hours)</th>
                <th colspan="4" class="section-header">Earnings</th>
                <th colspan="6" class="section-header">Deductions</th>
                <th rowspan="2">Net Salary</th>
            </tr>
            <tr>
                <th>P</th>
                <th>A</th>
                <th>Reg</th>
                <th>Fri</th>
                <th>Hol</th>
                <th>SC</th>
                <th>Earned</th>
                <th>OT Pay</th>
                <th>Total</th>
                <th>Att.</th>
                <th>Staff</th>
                <th>Pension</th>
                <th>EWT</th>
                <th>Other</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totals = ['present'=>0,'absent'=>0,'regular_ot'=>0,'friday_ot'=>0,'holiday_ot'=>0,
                    'service_charge'=>0,'earned_salary'=>0,'overtime_pay'=>0,'total_earnings'=>0,
                    'attendance_ded'=>0,'staff_shop'=>0,'pension'=>0,'ewt'=>0,'other_ded'=>0,
                    'total_deductions'=>0,'net_salary'=>0];
            @endphp
            @foreach($rows as $row)
                @php
                    foreach($totals as $k => &$v) { if(isset($row[$k])) $v += $row[$k]; }
                @endphp
                <tr>
                    <td>{{ $row['emp_id'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['department'] }}</td>
                    <td>{{ $row['position'] }}</td>
                    <td style="text-align:center">{{ $row['present'] }}</td>
                    <td style="text-align:center">{{ $row['absent'] }}</td>
                    <td style="text-align:center">{{ $row['regular_ot'] }}</td>
                    <td style="text-align:center">{{ $row['friday_ot'] }}</td>
                    <td style="text-align:center">{{ $row['holiday_ot'] }}</td>
                    <td>{{ $symbol }}{{ number_format($row['service_charge'], 2) }}</td>
                    <td>{{ $symbol }}{{ number_format($row['earned_salary'], 2) }}</td>
                    <td>{{ $symbol }}{{ number_format($row['overtime_pay'], 2) }}</td>
                    <td>{{ $symbol }}{{ number_format($row['total_earnings'], 2) }}</td>
                    <td>{{ $symbol }}{{ number_format($row['attendance_ded'], 2) }}</td>
                    <td>{{ $symbol }}{{ number_format($row['staff_shop'], 2) }}</td>
                    <td>{{ $symbol }}{{ number_format($row['pension'], 2) }}</td>
                    <td>{{ $symbol }}{{ number_format($row['ewt'], 2) }}</td>
                    <td>{{ $symbol }}{{ number_format($row['other_ded'], 2) }}</td>
                    <td>{{ $symbol }}{{ number_format($row['total_deductions'], 2) }}</td>
                    <td><strong>{{ $symbol }}{{ number_format($row['net_salary'], 2) }}</strong></td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4">TOTAL</td>
                <td style="text-align:center">{{ $totals['present'] }}</td>
                <td style="text-align:center">{{ $totals['absent'] }}</td>
                <td style="text-align:center">{{ $totals['regular_ot'] }}</td>
                <td style="text-align:center">{{ $totals['friday_ot'] }}</td>
                <td style="text-align:center">{{ $totals['holiday_ot'] }}</td>
                <td>{{ $symbol }}{{ number_format($totals['service_charge'], 2) }}</td>
                <td>{{ $symbol }}{{ number_format($totals['earned_salary'], 2) }}</td>
                <td>{{ $symbol }}{{ number_format($totals['overtime_pay'], 2) }}</td>
                <td>{{ $symbol }}{{ number_format($totals['total_earnings'], 2) }}</td>
                <td>{{ $symbol }}{{ number_format($totals['attendance_ded'], 2) }}</td>
                <td>{{ $symbol }}{{ number_format($totals['staff_shop'], 2) }}</td>
                <td>{{ $symbol }}{{ number_format($totals['pension'], 2) }}</td>
                <td>{{ $symbol }}{{ number_format($totals['ewt'], 2) }}</td>
                <td>{{ $symbol }}{{ number_format($totals['other_ded'], 2) }}</td>
                <td>{{ $symbol }}{{ number_format($totals['total_deductions'], 2) }}</td>
                <td><strong>{{ $symbol }}{{ number_format($totals['net_salary'], 2) }}</strong></td>
            </tr>
        </tbody>
    </table>
</body>
</html>
