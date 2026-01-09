<!DOCTYPE html>
<html>
<head>
    <title>Probation Details</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .header { font-size: 18px; font-weight: bold; margin-bottom: 10px; }
        .section { margin-bottom: 20px; }
        .badge { padding: 2px 6px; background-color: #007bff; color: white; border-radius: 4px; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">Probation Details for {{ $employee->resortAdmin->full_name }}</div>
    <p>Employee ID: #{{ $employee->Emp_id }}</p>
    <p>Department: {{ $employee->department->name }}</p>
    <p>Position: {{ $employee->position->position_title }}</p>
    <p>Joining Date: {{ \Carbon\Carbon::parse($employee->joining_date)->format('d M Y') }}</p>
    <p>Probation End Date: {{ \Carbon\Carbon::parse($employee->probation_end_date)->format('d M Y') }}</p>
    <p>Probation Status: {{ $employee->probation_status }}</p>
    <!-- Add any additional fields you want -->
</body>
</html>
