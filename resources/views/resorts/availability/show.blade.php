<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Availability</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f4f8; font-family: 'Segoe UI', sans-serif; }
        .invitation-card { max-width: 600px; margin: 60px auto; background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); overflow: hidden; }
        .invitation-header { background: #004552; color: #fff; padding: 30px; text-align: center; }
        .invitation-header h2 { margin: 0; font-size: 22px; font-weight: 600; }
        .invitation-header p { margin: 8px 0 0; opacity: 0.85; font-size: 14px; }
        .invitation-body { padding: 30px; }
        .detail-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #eee; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #666; font-size: 14px; font-weight: 500; }
        .detail-value { color: #333; font-size: 14px; font-weight: 600; text-align: right; }
        .btn-accept { background: #198754; color: #fff; border: none; padding: 12px 32px; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; }
        .btn-accept:hover { background: #157347; color: #fff; }
        .btn-decline { background: #dc3545; color: #fff; border: none; padding: 12px 32px; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; }
        .btn-decline:hover { background: #bb2d3b; color: #fff; }
        .status-badge { display: inline-block; padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; }
        .status-available { background: #d1e7dd; color: #0f5132; }
        .status-unavailable { background: #f8d7da; color: #842029; }
        .status-pending { background: #fff3cd; color: #664d03; }
        .action-buttons { display: flex; gap: 12px; justify-content: center; margin-top: 24px; }
        .availability-info { background: #f8f9fa; border-radius: 12px; padding: 20px; margin: 16px 0; }
        .availability-info p { margin: 0; color: #555; font-size: 14px; line-height: 1.6; }
    </style>
</head>
<body>

<div class="invitation-card">
    <div class="invitation-header">
        <h2>Availability Check</h2>
        @if($applicant && isset($resort))
            <p>{{ $resort->resort_name ?? '' }}</p>
        @endif
    </div>

    <div class="invitation-body">

        @if(session('success'))
            <div class="alert alert-success text-center">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger text-center">{{ session('error') }}</div>
        @endif
        @if(session('info'))
            <div class="alert alert-info text-center">{{ session('info') }}</div>
        @endif

        @if(!empty($error))
            <div class="text-center py-4">
                <h4 class="text-danger">{{ $error }}</h4>
                <p class="text-muted">Please contact the HR department for assistance.</p>
            </div>
        @elseif($applicant)

            @php $status = $applicant->availability_status; @endphp

            <div class="text-center mb-4">
                @if($status === 'available')
                    <span class="status-badge status-available">Available</span>
                @elseif($status === 'unavailable')
                    <span class="status-badge status-unavailable">Not Available</span>
                @elseif($status === 'pending')
                    <span class="status-badge status-pending">Pending Your Response</span>
                @endif
            </div>

            <div class="detail-row">
                <span class="detail-label">Name</span>
                <span class="detail-value">{{ ucfirst($applicant->first_name) }} {{ ucfirst($applicant->last_name) }}</span>
            </div>

            <div class="availability-info">
                <p>We have an opportunity that may match your profile. Please let us know if you are currently available and interested in exploring new positions with us.</p>
            </div>

            @if($status === 'pending')
                <div class="action-buttons">
                    <form action="{{ route('resort.availability.available', $token) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn-accept">I'm Available</button>
                    </form>
                    <form action="{{ route('resort.availability.unavailable', $token) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn-decline">Not Available</button>
                    </form>
                </div>
            @endif

        @endif
    </div>
</div>

</body>
</html>
