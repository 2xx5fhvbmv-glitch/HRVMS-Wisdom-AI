<!DOCTYPE html>
{{--
    Incident Report — §6.13 of the e-signature spec.
    Frozen signatures from every employee statement, every witness
    statement, and the GM's final approval — all captured at the moment
    each person acted (see IncidentController::provideStatement() /
    approveOrReject()), never re-derived from a live signature_img.
    Gated on status === 'Approved' (see downloadIncidentReportPdf()).
--}}
@php
    $lh = $letterhead ?? ['configured' => false];
    $hasLetterhead = !empty($lh['configured']) && !empty($lh['headerImage']);
@endphp
<html>
<head>
    <meta charset="utf-8">
    <title>Incident Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        .letterhead { border-bottom: 2px solid #1f3c88; padding-bottom: 10px; margin-bottom: 25px; }
        .letterhead .logo { height: 60px; }
        .letterhead .resort-name { font-size: 20px; font-weight: bold; color: #1f3c88; margin: 0; }
        .letterhead-img { width: 100%; max-height: 130px; }
        .letterhead-address { font-size: 10px; color: #555; margin-top: 6px; }
        .meta { margin-bottom: 18px; }
        .title { text-align: center; font-size: 15px; font-weight: bold; text-decoration: underline; margin: 18px 0; }
        table.details { width: 100%; border-collapse: collapse; margin: 14px 0; }
        table.details td { border: 1px solid #ccc; padding: 6px 8px; }
        table.details td.label { background: #f3f5fb; font-weight: bold; width: 32%; }
        .section-title { font-size: 13px; font-weight: bold; margin: 20px 0 8px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
        .statement-block { border: 1px solid #ddd; padding: 8px 10px; margin-bottom: 8px; }
        .statement-block .who { font-weight: bold; }
        .statement-block .text { margin-top: 4px; }
        .signature { margin-top: 40px; }
        .footer-note { margin-top: 40px; font-size: 10px; color: #888; }
        .letterhead-footer-img { width: 100%; max-height: 90px; margin-top: 30px; }
    </style>
</head>
<body>
    @if($hasLetterhead)
        <div class="letterhead">
            <img src="{{ $lh['headerImage'] }}" class="letterhead-img" alt="letterhead">
            @if(!empty($lh['addressLine1']) || !empty($lh['addressLine2']) || !empty($lh['contactPhone']) || !empty($lh['contactEmail']) || !empty($lh['website']))
                <div class="letterhead-address">
                    @if(!empty($lh['addressLine1'])) {{ $lh['addressLine1'] }}@endif
                    @if(!empty($lh['addressLine2'])), {{ $lh['addressLine2'] }}@endif
                    @if(!empty($lh['contactPhone'])) &nbsp;|&nbsp; Tel: {{ $lh['contactPhone'] }}@endif
                    @if(!empty($lh['contactEmail'])) &nbsp;|&nbsp; {{ $lh['contactEmail'] }}@endif
                    @if(!empty($lh['website'])) &nbsp;|&nbsp; {{ $lh['website'] }}@endif
                </div>
            @endif
        </div>
    @else
        <table class="letterhead" style="width:100%; border:none;">
            <tr>
                <td style="border:none; width:80px;">
                    @if(!empty($resortLogo))
                        <img src="{{ $resortLogo }}" class="logo" alt="logo">
                    @endif
                </td>
                <td style="border:none; vertical-align:middle;">
                    <p class="resort-name">{{ $resort->resort_name ?? 'Resort' }}</p>
                </td>
            </tr>
        </table>
    @endif

    <div class="meta">
        <strong>Ref:</strong> INC/{{ $incident->id }}/{{ \Carbon\Carbon::now()->format('Y') }}<br>
        <strong>Date:</strong> {{ \Carbon\Carbon::now()->format('d M Y') }}
    </div>

    <div class="title">INCIDENT REPORT</div>

    <table class="details">
        <tr><td class="label">Incident</td><td>{{ $incident->incident_name ?? '—' }}</td></tr>
        <tr><td class="label">Reported By</td><td>{{ optional(optional($incident->reporter)->resortAdmin)->full_name ?? '—' }}</td></tr>
        <tr><td class="label">Incident Date</td><td>{{ $incident->incident_date ? \Carbon\Carbon::parse($incident->incident_date)->format('d M Y') : '—' }}</td></tr>
        <tr><td class="label">Location</td><td>{{ $incident->location ?? '—' }}</td></tr>
        <tr><td class="label">Description</td><td>{{ $incident->description ?? '—' }}</td></tr>
        <tr><td class="label">Severity</td><td>{{ $incident->severity ?? '—' }}</td></tr>
        <tr><td class="label">Preventive Measures</td><td>{{ $incident->preventive_measures ?? '—' }}</td></tr>
        <tr><td class="label">Status</td><td>{{ $incident->status ?? '—' }}</td></tr>
        <tr><td class="label">Approval Remarks</td><td>{{ $incident->approval_remarks ?? '—' }}</td></tr>
    </table>

    @if($employeeStatements->isNotEmpty())
        <div class="section-title">Employee Statements</div>
        @foreach($employeeStatements as $statement)
            <div class="statement-block">
                <div class="who">{{ optional(optional($statement->employee)->resortAdmin)->full_name ?? 'Employee' }}</div>
                <div class="text">{{ $statement->statement ?? '—' }}</div>
            </div>
        @endforeach
    @endif

    @if($witnessStatements->isNotEmpty())
        <div class="section-title">Witness Statements</div>
        @foreach($witnessStatements as $witness)
            <div class="statement-block">
                <div class="who">{{ optional(optional($witness->employee)->resortAdmin)->full_name ?? 'Witness' }}</div>
                <div class="text">{{ $witness->witness_statements ?? '—' }}</div>
            </div>
        @endforeach
    @endif

    <div class="signature">
        @include('resorts.pdf_partials._signature_block', ['signatures' => $signatures ?? []])
    </div>

    <p class="footer-note">
        This is a system-generated incident report. For queries, please contact the HR Department.
    </p>

    @if($hasLetterhead && !empty($lh['footerImage']))
        <img src="{{ $lh['footerImage'] }}" class="letterhead-footer-img" alt="letterhead footer">
    @endif
</body>
</html>
