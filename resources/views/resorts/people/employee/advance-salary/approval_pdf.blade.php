<!DOCTYPE html>
{{--
    Salary Advance / Loan Approval Record — §6.9 of the e-signature spec.
    Same letterhead + signature-block pattern as the other letters:
    branded header when configured, else resort logo + name; each stage's
    frozen signature at the bottom (see
    AdvanceSalaryController::downloadApprovalPdf()).
--}}
@php
    $lh = $letterhead ?? ['configured' => false];
    $hasLetterhead = !empty($lh['configured']) && !empty($lh['headerImage']);
@endphp
<html>
<head>
    <meta charset="utf-8">
    <title>Salary Advance Approval</title>
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
        <strong>Ref:</strong> ADV/{{ $payrollAdvance->id }}/{{ \Carbon\Carbon::parse($payrollAdvance->request_date)->format('Y') }}<br>
        <strong>Date:</strong> {{ \Carbon\Carbon::now()->format('d M Y') }}
    </div>

    <div class="title">{{ strtoupper($payrollAdvance->request_type ?? 'SALARY ADVANCE') }} — APPROVAL RECORD</div>

    <table class="details">
        <tr><td class="label">Employee</td><td>{{ optional(optional($payrollAdvance->employee)->resortAdmin)->full_name ?? '—' }}</td></tr>
        <tr><td class="label">Employee ID</td><td>{{ optional($payrollAdvance->employee)->Emp_id ?? '—' }}</td></tr>
        <tr><td class="label">Department</td><td>{{ optional(optional($payrollAdvance->employee)->department)->name ?? '—' }}</td></tr>
        <tr><td class="label">Request Type</td><td>{{ $payrollAdvance->request_type ?? '—' }}</td></tr>
        @if(!empty($payrollAdvance->request_amount))
            <tr><td class="label">Amount</td><td>{{ number_format($payrollAdvance->request_amount, 2) }} {{ $payrollAdvance->currency ?? '' }}</td></tr>
        @endif
        <tr><td class="label">Purpose</td><td>{{ $payrollAdvance->pourpose ?? '—' }}</td></tr>
        <tr><td class="label">Request Date</td><td>{{ $payrollAdvance->request_date ? \Carbon\Carbon::parse($payrollAdvance->request_date)->format('d M Y') : '—' }}</td></tr>
        <tr><td class="label">Status</td><td>{{ $payrollAdvance->status ?? '—' }}</td></tr>
    </table>

    <div class="signature">
        @include('resorts.pdf_partials._signature_block', ['signatures' => $signatures ?? []])
    </div>

    <p class="footer-note">
        This is a system-generated approval record. For queries, please contact the HR Department.
    </p>

    @if($hasLetterhead && !empty($lh['footerImage']))
        <img src="{{ $lh['footerImage'] }}" class="letterhead-footer-img" alt="letterhead footer">
    @endif
</body>
</html>
