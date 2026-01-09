<!DOCTYPE html>
<html>
<head>
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        h1 {
            font-size: 18px;
            text-align: center;
            margin-bottom: 20px;
        }
        .report-info {
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .breached {
            color: red;
        }
        .resolved {
            color: green;
        }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="report-info">
        <p><strong>Generated on:</strong> {{ $date }}</p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Module Name</th>
                <th>Compliance Breached</th>
                <th>Employee ID</th>
                <th>Employee Name</th>
                <th>Description</th>
                <th>Reported On</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @if(count($compliances) > 0)
                @foreach($compliances as $compliance)
                <tr>
                    <td>{{ $compliance['no'] }}</td>
                    <td>{{ $compliance['module_name'] }}</td>
                    <td>{{ $compliance['compliance_breached_name'] }}</td>
                    <td>{{ $compliance['employee_id'] }}</td>
                    <td>{{ $compliance['employee_name'] }}</td>
                    <td>{{ $compliance['description'] }}</td>
                    <td>{{ $compliance['reported_on'] }}</td>
                    <td class="{{ strtolower($compliance['status']) }}">{{ $compliance['status'] }}</td>
                </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="8">No compliance records found</td>
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>