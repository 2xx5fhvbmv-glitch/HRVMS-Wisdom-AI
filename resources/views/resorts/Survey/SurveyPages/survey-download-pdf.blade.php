<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Survey - {{ $parent->Surevey_title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; margin: 0; padding: 20px; }
        .title { font-size: 18px; font-weight: bold; margin-bottom: 6px; }
        .badge { display: inline-block; padding: 2px 10px; border-radius: 4px; font-size: 10px; margin-bottom: 15px; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-info { background: #cce5ff; color: #004085; }
        .badge-primary { background: #b8daff; color: #004085; }
        .badge-secondary { background: #e2e3e5; color: #383d41; }
        .meta-strip { background: #f8f9fa; border: 1px solid #eee; border-radius: 6px; padding: 14px 18px; margin-bottom: 20px; }
        .meta-strip table { width: 100%; border-collapse: collapse; }
        .meta-strip td { padding: 6px 12px 6px 0; vertical-align: top; }
        .meta-label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.05em; color: #6c757d; margin-bottom: 2px; }
        .meta-value { font-weight: 600; color: #212529; }
        .section-heading { font-size: 13px; font-weight: bold; margin: 18px 0 10px; padding-bottom: 4px; border-bottom: 1px solid #dee2e6; }
        .question-block { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; padding: 12px 14px; margin-bottom: 10px; }
        .question-meta { margin-bottom: 6px; }
        .question-meta span { display: inline-block; padding: 2px 8px; margin-right: 6px; font-size: 9px; background: #fff; border: 1px solid #dee2e6; border-radius: 4px; }
        .question-text { font-weight: 600; margin: 6px 0; }
        .question-options { margin-top: 8px; }
        .question-options span { display: inline-block; padding: 4px 10px; margin: 2px 4px 2px 0; font-size: 10px; background: #fff; border: 1px solid #dee2e6; border-radius: 4px; }
        .participants-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .participants-table th { text-align: left; padding: 8px 10px; background: #f8f9fa; border: 1px solid #dee2e6; font-size: 10px; }
        .participants-table td { padding: 8px 10px; border: 1px solid #dee2e6; }
    </style>
</head>
<body>
    <div class="title">{{ $parent->Surevey_title }}</div>
    <span class="badge {{ $parent->Status == 'Complete' ? 'badge-success' : ($parent->Status == 'OnGoing' ? 'badge-info' : ($parent->Status == 'Publish' ? 'badge-primary' : 'badge-secondary')) }}">{{ $parent->Status }}</span>

    <div class="meta-strip">
        <table>
            <tr>
                <td style="width: 25%;">
                    <div class="meta-label">Created by</div>
                    <div class="meta-value">{{ $parent->EmployeeName }}</div>
                </td>
                <td style="width: 25%;">
                    <div class="meta-label">Date range</div>
                    <div class="meta-value">{{ date('d M Y', strtotime($parent->Start_date)) }} – {{ date('d M Y', strtotime($parent->End_date)) }}</div>
                </td>
                <td style="width: 25%;">
                    <div class="meta-label">Privacy</div>
                    <div class="meta-value">{{ !empty($parent->survey_privacy_type) ? ucfirst($parent->survey_privacy_type) : '—' }}</div>
                </td>
                <td style="width: 25%;">
                    <div class="meta-label">Participants</div>
                    <div class="meta-value">{{ $participantEmp->count() }} participant(s)</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section-heading">Participants</div>
    <table class="participants-table">
        <thead>
            <tr>
                <th style="width: 40px;">#</th>
                <th>Name</th>
            </tr>
        </thead>
        <tbody>
            @forelse($participantEmp as $idx => $e)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $e->EmployeeName }}</td>
                </tr>
            @empty
                <tr><td colspan="2">No participants assigned.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-heading">Questions ({{ $Question->count() }})</div>
    @if($Question->isNotEmpty())
        @foreach($Question as $q)
            <div class="question-block">
                <div class="question-meta">
                    <span>Q{{ $loop->iteration }}</span>
                    <span>{{ ucfirst($q->Question_Type ?? 'Text') }}</span>
                    @if(!empty($q->Question_Complusory) && strtolower($q->Question_Complusory) === 'yes')
                        <span>Required</span>
                    @endif
                </div>
                <div class="question-text">{{ ucfirst($q->Question_Text) }}</div>
                @if(!empty($q->Total_Option_Json))
                    @php
                        $options = is_string($q->Total_Option_Json) ? json_decode($q->Total_Option_Json, true) : $q->Total_Option_Json;
                    @endphp
                    @if(!empty($options) && (is_array($options) || is_object($options)))
                        <div class="question-options">
                            @foreach((array)$options as $opt)
                                <span>{{ is_string($opt) ? $opt : (is_array($opt) ? implode(' ', $opt) : $opt) }}</span>
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>
        @endforeach
    @else
        <p>No questions added yet.</p>
    @endif
</body>
</html>
