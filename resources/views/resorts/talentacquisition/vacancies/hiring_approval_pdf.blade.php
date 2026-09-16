<!DOCTYPE html>
{{--
    Hiring Request (Vacancy) Approval Letter — Item §6.3 of the e-signature
    spec. Same letterhead + signature-block pattern as
    transfer_letter_pdf.blade.php: branded header when the resort has
    configured Letterhead & E-signature, else resort logo + name; frozen
    per-approver signatures at the bottom (see
    VacancyController::buildVacancySignatures()), never the live
    ResortAdmin.signature_img.
--}}
@php
    $lh = $letterhead ?? ['configured' => false];
    $hasLetterhead = !empty($lh['configured']) && !empty($lh['headerImage']);
@endphp
<html>
<head>
    <meta charset="utf-8">
    <title>Hiring Request Approval</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        .letterhead { border-bottom: 2px solid #1f3c88; padding-bottom: 10px; margin-bottom: 25px; }
        .letterhead .logo { height: 60px; }
        .letterhead .resort-name { font-size: 20px; font-weight: bold; color: #1f3c88; margin: 0; }
        .letterhead-img { width: 100%; max-height: 130px; }
        .letterhead-address { font-size: 10px; color: #555; margin-top: 6px; }
        .meta { margin-bottom: 18px; }
        .title { text-align: center; font-size: 15px; font-weight: bold; text-decoration: underline; margin: 18px 0; }
        .body-text { line-height: 1.7; text-align: justify; }
        table.details { width: 100%; border-collapse: collapse; margin: 14px 0; }
        table.details td { border: 1px solid #ccc; padding: 6px 8px; }
        table.details td.label { background: #f3f5fb; font-weight: bold; width: 38%; }
        .signature { margin-top: 50px; }
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
        <strong>Ref:</strong> HIRE/{{ $vacancy->id }}/{{ \Carbon\Carbon::now()->format('Y') }}<br>
        <strong>Date:</strong> {{ \Carbon\Carbon::now()->format('d M Y') }}
    </div>

    <div class="title">HIRING REQUEST — APPROVAL RECORD</div>

    <p class="body-text">
        This document records the full approval of the following hiring request within
        {{ $resort->resort_name ?? 'the organization' }}.
    </p>

    <table class="details">
        <tr><td class="label">Position</td><td>{{ optional($vacancy->Getposition)->position_title ?? '—' }}</td></tr>
        <tr><td class="label">Department</td><td>{{ optional($vacancy->Getdepartment)->name ?? '—' }}</td></tr>
        <tr><td class="label">Requested Positions</td><td>{{ $vacancy->Total_position_required ?? '—' }}</td></tr>
        <tr><td class="label">Employment Type</td><td>{{ $vacancy->employee_type ?? '—' }}</td></tr>
        <tr><td class="label">Requested Starting Date</td><td>
            {{ $vacancy->required_starting_date ? \Carbon\Carbon::parse($vacancy->required_starting_date)->format('d M Y') : '—' }}
        </td></tr>
        <tr><td class="label">Budget Status</td><td>{{ $vacancy->budgeted ?? '—' }}</td></tr>
        @if(!empty($vacancy->justification))
            <tr><td class="label">Justification</td><td>{{ $vacancy->justification }}</td></tr>
        @endif
        <tr><td class="label">Requested By</td><td>{{ optional($vacancy->resortAdmin)->full_name ?? '—' }}</td></tr>
    </table>

    <p class="body-text">
        This hiring request has completed the resort's full approval chain (HR, Finance and GM as
        applicable). The approvers' signatures below confirm each stage's sign-off.
    </p>

    <div class="signature">
        @include('resorts.pdf_partials._signature_block', ['signatures' => $signatures ?? []])
    </div>

    <p class="footer-note">
        This is a system-generated hiring approval record. For queries, please contact the HR Department.
    </p>

    @if($hasLetterhead && !empty($lh['footerImage']))
        <img src="{{ $lh['footerImage'] }}" class="letterhead-footer-img" alt="letterhead footer">
    @endif
</body>
</html>
