<!DOCTYPE html>
<html>
<head>
    <title>Promotion History</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: left; }
    </style>
</head>
<body>
    <h2>Promotion History</h2>
    <table>
        <thead>
            <tr>
                <th>Employee ID</th>
                <th>Name</th>
                <th>Effective Date</th>
                <th>Old Position</th>
                <th>New Position</th>
                <th>Old Salary</th>
                <th>New Salary</th>
            </tr>
        </thead>
        <tbody>
            @foreach($promotions as $p)
                <tr>
                    <td>{{ $p->employee->Emp_id }}</td>
                    <td>{{ $p->employee->resortAdmin->full_name }}</td>
                    <td>{{ $p->effective_date }}</td>
                    <td>{{ $p->currentPosition->position_title ?? 'N/A' }}</td>
                    <td>{{ $p->newPosition->position_title ?? 'N/A' }}</td>
                    <td>{{ $p->current_salary }}</td>
                    <td>{{ $p->new_salary }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
