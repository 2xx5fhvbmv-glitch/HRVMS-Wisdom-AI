<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meeting Response</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', Arial, sans-serif; }
        .response-card { max-width: 550px; margin: 60px auto; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .response-header { background: #014653; color: #fff; padding: 24px; text-align: center; }
        .response-body { background: #fff; padding: 30px; }
        .meeting-detail { display: flex; padding: 10px 0; border-bottom: 1px solid #f0f0f0; }
        .meeting-detail:last-child { border-bottom: none; }
        .meeting-detail .label { font-weight: 600; color: #555; width: 120px; flex-shrink: 0; }
        .meeting-detail .value { color: #333; }
        .btn-accept { background: #014653; border: none; padding: 10px 30px; font-weight: 500; }
        .btn-accept:hover { background: #026b7a; }
        .btn-decline { background: #dc3545; border: none; padding: 10px 30px; font-weight: 500; }
        .btn-decline:hover { background: #c82333; }
        .success-icon { font-size: 48px; color: #28a745; }
        .declined-icon { font-size: 48px; color: #dc3545; }
    </style>
</head>
<body>
    <div class="response-card">
        <div class="response-header">
            <h4 style="margin:0;">Performance Meeting</h4>
        </div>
        <div class="response-body">
            @if($error)
                <div class="text-center py-4">
                    <div style="font-size:48px;color:#dc3545;">&#10060;</div>
                    <h5 class="mt-3">{{ $error }}</h5>
                </div>
            @elseif(isset($message))
                <div class="text-center py-3">
                    @if($action === 'accepted')
                        <div class="success-icon">&#10004;</div>
                        <h5 class="mt-3 text-success">{{ $message }}</h5>
                    @else
                        <div class="declined-icon">&#10006;</div>
                        <h5 class="mt-3 text-danger">{{ $message }}</h5>
                    @endif

                    @if($meeting)
                        <hr>
                        <div class="text-start">
                            <div class="meeting-detail"><span class="label">Title</span><span class="value">{{ $meeting->title }}</span></div>
                            <div class="meeting-detail"><span class="label">Date</span><span class="value">{{ \Carbon\Carbon::parse($meeting->date)->format('d M Y') }}</span></div>
                            <div class="meeting-detail"><span class="label">Time</span><span class="value">{{ $meeting->start_time }} - {{ $meeting->end_time }}</span></div>
                            @if($meeting->location)
                            <div class="meeting-detail"><span class="label">Location</span><span class="value">{{ $meeting->location }}</span></div>
                            @endif
                            @if($meeting->conference_links)
                            <div class="meeting-detail"><span class="label">Meeting Link</span><span class="value">{{ $meeting->conference_links }}</span></div>
                            @endif
                        </div>
                    @endif
                </div>
            @elseif($participant && $participant->status !== 'pending')
                <div class="text-center py-3">
                    @if($participant->status === 'accepted')
                        <div class="success-icon">&#10004;</div>
                        <h5 class="mt-3 text-success">You have already accepted this meeting.</h5>
                    @else
                        <div class="declined-icon">&#10006;</div>
                        <h5 class="mt-3 text-muted">You have already declined this meeting.</h5>
                        @if($participant->reason)
                            <p class="text-muted">Reason: {{ $participant->reason }}</p>
                        @endif
                    @endif
                </div>
            @elseif($action === 'decline')
                <h5 class="mb-3">Decline Meeting</h5>
                <div class="text-start mb-3">
                    <div class="meeting-detail"><span class="label">Title</span><span class="value">{{ $meeting->title }}</span></div>
                    <div class="meeting-detail"><span class="label">Date</span><span class="value">{{ \Carbon\Carbon::parse($meeting->date)->format('d M Y') }}</span></div>
                    <div class="meeting-detail"><span class="label">Time</span><span class="value">{{ $meeting->start_time }} - {{ $meeting->end_time }}</span></div>
                </div>

                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <form method="POST" action="{{ route('meeting.respond.submit', $participant->token) }}">
                    @csrf
                    <input type="hidden" name="status" value="declined">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Reason for declining <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="4" placeholder="Please provide a reason for declining this meeting..." required>{{ old('reason') }}</textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('meeting.respond', ['token' => $participant->token]) }}" class="btn btn-secondary flex-fill">Back</a>
                        <button type="submit" class="btn btn-decline text-white flex-fill">Submit Decline</button>
                    </div>
                </form>
            @else
                <h5 class="mb-3">Meeting Invitation</h5>
                <div class="text-start mb-4">
                    <div class="meeting-detail"><span class="label">Title</span><span class="value">{{ $meeting->title }}</span></div>
                    <div class="meeting-detail"><span class="label">Date</span><span class="value">{{ \Carbon\Carbon::parse($meeting->date)->format('d M Y') }}</span></div>
                    <div class="meeting-detail"><span class="label">Time</span><span class="value">{{ $meeting->start_time }} - {{ $meeting->end_time }}</span></div>
                    @if($meeting->location)
                    <div class="meeting-detail"><span class="label">Location</span><span class="value">{{ $meeting->location }}</span></div>
                    @endif
                    @if($meeting->conference_links)
                    <div class="meeting-detail"><span class="label">Meeting Link</span><span class="value">{{ $meeting->conference_links }}</span></div>
                    @endif
                    @if($meeting->description)
                    <div class="meeting-detail"><span class="label">Description</span><span class="value">{{ $meeting->description }}</span></div>
                    @endif
                </div>

                <div class="d-flex gap-2">
                    <form method="POST" action="{{ route('meeting.respond.submit', $participant->token) }}" class="flex-fill">
                        @csrf
                        <input type="hidden" name="status" value="accepted">
                        <button type="submit" class="btn btn-accept text-white w-100">Accept</button>
                    </form>
                    <a href="{{ route('meeting.respond', ['token' => $participant->token, 'action' => 'decline']) }}" class="btn btn-decline text-white flex-fill text-center">Decline</a>
                </div>
            @endif
        </div>
    </div>
</body>
</html>
