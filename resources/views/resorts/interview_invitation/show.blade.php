<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interview Invitation</title>
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
        .btn-outline-decline { background: transparent; color: #dc3545; border: 2px solid #dc3545; padding: 12px 32px; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; }
        .btn-outline-decline:hover { background: #dc3545; color: #fff; }
        .status-badge { display: inline-block; padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; }
        .status-accepted { background: #d1e7dd; color: #0f5132; }
        .status-rejected { background: #f8d7da; color: #842029; }
        .status-pending { background: #fff3cd; color: #664d03; }
        .action-buttons { display: flex; gap: 12px; justify-content: center; margin-top: 24px; }
        .decline-form { display: none; margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; }
        .decline-form.show { display: block; }
    </style>
</head>
<body>

<div class="invitation-card">
    <div class="invitation-header">
        <h2>Interview Invitation</h2>
        @if($interview && isset($resort))
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
        @elseif($interview)

            @php
                $status = $interview->Status;
            @endphp

            {{-- Status Badge --}}
            <div class="text-center mb-4">
                @if($status === 'Slot Booked')
                    <span class="status-badge status-accepted">Accepted</span>
                @elseif($status === 'Invitation Rejected')
                    <span class="status-badge status-rejected">Declined</span>
                @elseif($status === 'Invitation Sent')
                    <span class="status-badge status-pending">Pending Response</span>
                @endif
            </div>

            {{-- Candidate Name --}}
            @if(isset($applicant))
            <div class="detail-row">
                <span class="detail-label">Candidate</span>
                <span class="detail-value">{{ ucfirst($applicant->first_name) }} {{ ucfirst($applicant->last_name) }}</span>
            </div>
            @endif

            {{-- Position & Department --}}
            @if(isset($vacancy))
            <div class="detail-row">
                <span class="detail-label">Position</span>
                <span class="detail-value">{{ $vacancy->position ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Department</span>
                <span class="detail-value">{{ $vacancy->department ?? '-' }}</span>
            </div>
            @endif

            {{-- Interview Details --}}
            <div class="detail-row">
                <span class="detail-label">Interview Date</span>
                <span class="detail-value">{{ \Carbon\Carbon::parse($interview->InterViewDate)->format('d M Y') }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Resort Time</span>
                <span class="detail-value">{{ $interview->ResortInterviewtime }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Your Local Time</span>
                <span class="detail-value">{{ $interview->ApplicantInterviewtime }}</span>
            </div>

            @if($interview->MeetingLink && $interview->MeetingLink != '0')
            <div class="detail-row">
                <span class="detail-label">Meeting Link</span>
                <span class="detail-value"><a href="{{ $interview->MeetingLink }}" target="_blank">Join Meeting</a></span>
            </div>
            @endif

            {{-- Action Buttons (only if Invitation Sent) --}}
            @if($status === 'Invitation Sent')
                <div class="action-buttons">
                    <form action="{{ route('resort.interview.invitation.accept', $token) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn-accept">Accept Interview</button>
                    </form>
                    <button type="button" class="btn-outline-decline" id="showDeclineForm">Decline Interview</button>
                </div>

                <div class="decline-form" id="declineForm">
                    <form action="{{ route('resort.interview.invitation.reject', $token) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Reason for declining (optional)</label>
                            <textarea class="form-control" name="rejection_reason" rows="3" placeholder="Please let us know why you are declining..."></textarea>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn-decline">Confirm Decline</button>
                        </div>
                    </form>
                </div>
            @endif

            {{-- Show rejection reason if rejected --}}
            @if($status === 'Invitation Rejected' && $interview->rejection_reason)
                <div class="mt-3 p-3" style="background: #f8d7da; border-radius: 8px;">
                    <strong>Reason:</strong> {{ $interview->rejection_reason }}
                </div>
            @endif

        @endif
    </div>
</div>

<script>
    document.getElementById('showDeclineForm')?.addEventListener('click', function() {
        document.getElementById('declineForm').classList.toggle('show');
    });
</script>
</body>
</html>
