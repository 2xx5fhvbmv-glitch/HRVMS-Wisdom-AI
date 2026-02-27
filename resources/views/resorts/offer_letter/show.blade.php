<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offer Letter</title>
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
        .download-link { display: inline-flex; align-items: center; gap: 8px; padding: 10px 24px; background: #004552; color: #fff; text-decoration: none; border-radius: 8px; font-weight: 600; margin-top: 16px; }
        .download-link:hover { background: #006570; color: #fff; }
    </style>
</head>
<body>

<div class="invitation-card">
    <div class="invitation-header">
        <h2>Offer Letter</h2>
        @if($offer && isset($resort))
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
        @elseif($offer)

            @php $status = $offer->status; @endphp

            <div class="text-center mb-4">
                @if($status === 'Accepted')
                    <span class="status-badge status-accepted">Accepted</span>
                @elseif($status === 'Rejected')
                    <span class="status-badge status-rejected">Declined</span>
                @elseif($status === 'Sent')
                    <span class="status-badge status-pending">Pending Response</span>
                @endif
            </div>

            @if(isset($applicant))
            <div class="detail-row">
                <span class="detail-label">Candidate</span>
                <span class="detail-value">{{ ucfirst($applicant->first_name) }} {{ ucfirst($applicant->last_name) }}</span>
            </div>
            @endif

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

            @if($offer->file_path)
            <div class="text-center mt-3">
                <a href="{{ asset('storage/' . $offer->file_path) }}" target="_blank" class="download-link">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/></svg>
                    Download Offer Letter
                </a>
            </div>
            @endif

            @if($status === 'Sent')
                <div class="action-buttons">
                    <form action="{{ route('resort.offer.letter.accept', $token) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn-accept">Accept Offer</button>
                    </form>
                    <button type="button" class="btn-outline-decline" id="showDeclineForm">Decline Offer</button>
                </div>

                <div class="decline-form" id="declineForm">
                    <form action="{{ route('resort.offer.letter.reject', $token) }}" method="POST">
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

            @if($status === 'Rejected' && $offer->rejection_reason)
                <div class="mt-3 p-3" style="background: #f8d7da; border-radius: 8px;">
                    <strong>Reason:</strong> {{ $offer->rejection_reason }}
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
