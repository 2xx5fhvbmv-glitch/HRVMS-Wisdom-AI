<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>403 - Unauthorized</title>
    <link rel="stylesheet" href="/css/app.css"> <!-- Optional -->
    <style>
        body { font-family: Arial, sans-serif; background-color: #f8f9fa; text-align: center; padding: 50px; }
        h1 { color: #dc3545; font-size: 72px; }
        p { font-size: 18px; color: #555; }
        a.btn { padding: 10px 20px; background: #007bff; color: #fff; text-decoration: none; border-radius: 5px; }
        a.btn:hover { background: #0056b3; }
    </style>
</head>
<body class="container">
    <h1>403</h1>
    <h2>Unauthorized Access</h2>
    <p>You do not have permission to access this page.</p>
    <p>Please contact your administrator if you believe this is an error.</p>

    @if(Auth::guard('resort-admin')->user()->GetEmployee->is_master_admin == 1)
        <a href="{{route('resort.master.admin_dashboard')}}" class="btn">Go to Dashboard</a>
    @else
        @if(Auth::guard('resort-admin')->user()->GetEmployee->rank == 2)
            <a href="{{route('resort.master.hr_dashboard')}}" class="btn">Go to Dashboard</a>
        @elseif(Auth::guard('resort-admin')->user()->GetEmployee->rank == 8)
            <a href="{{route('resort.master.gm_dashboard')}}" class="btn">Go to Dashboard</a>
        @else
            <a href="{{route('resort.master.hod_dashboard')}}" class="btn">Go to Dashboard</a>
        @endif
    @endif
   
</body>
</html>
