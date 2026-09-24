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

    @php
        // This one error view is shared by every portal (no admin/{{code}}
        // or shopkeeper/{{code}} override exists), but the original code
        // only ever handled resort-admin, and even then unsafely:
        // Auth::guard('resort-admin')->user() is null for an admin/
        // shopkeeper 403 (crashes on ->GetEmployee), and
        // is_master_admin lives on resort_admins itself, not on
        // ->GetEmployee (so that check silently never matched even for a
        // logged-in resort-admin master account, before the null crash).
        $resortUser = Auth::guard('resort-admin')->user();
    @endphp
    @if($resortUser && $resortUser->is_master_admin == 1)
        <a href="{{route('resort.master.admin_dashboard')}}" class="btn">Go to Dashboard</a>
    @elseif($resortUser)
        @php $rank = optional($resortUser->GetEmployee)->rank; @endphp
        @if($rank == 2)
            <a href="{{route('resort.master.hr_dashboard')}}" class="btn">Go to Dashboard</a>
        @elseif($rank == 8)
            <a href="{{route('resort.master.gm_dashboard')}}" class="btn">Go to Dashboard</a>
        @elseif($rank !== null)
            <a href="{{route('resort.master.hod_dashboard')}}" class="btn">Go to Dashboard</a>
        @else
            <a href="{{route('resort.loginindex')}}" class="btn">Go to Login</a>
        @endif
    @elseif(Auth::guard('admin')->check())
        <a href="{{route('admin.dashboard')}}" class="btn">Go to Dashboard</a>
    @elseif(Auth::guard('shopkeeper')->check())
        <a href="{{route('shopkeeper.dashboard')}}" class="btn">Go to Dashboard</a>
    @else
        <a href="{{route('resort.loginindex')}}" class="btn">Go to Login</a>
    @endif
   
</body>
</html>
