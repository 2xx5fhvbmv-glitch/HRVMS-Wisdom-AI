<!DOCTYPE html>
{{--
    Transfer Letter PDF (Item 7).

    Letterhead & E-signature:
      When the resort has configured a Letterhead & Signature record
      (People > Configuration > Letterhead & Signature), this template renders
      the branded header image (and optional footer image) plus the stored
      e-signature image and signatory block.

      When no letterhead is configured ($letterhead['configured'] === false),
      it falls back to the legacy layout: resort logo + resort name header and
      a typed signature block — so letter generation never breaks.

    Probation / Promotion letters can reuse this exact pattern: pass a
    `$letterhead` array from Common::getLetterheadData($resortId) and copy the
    header / footer / signature sections below.
--}}
@php
    $lh = $letterhead ?? ['configured' => false];
    $hasLetterhead = !empty($lh['configured']) && !empty($lh['headerImage']);
@endphp
<html>
<head>
    <meta charset="utf-8">
    <title>Transfer Letter</title>
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
        .sig-line { border-top: 1px solid #000; width: 220px; padding-top: 4px; }
        .footer-note { margin-top: 40px; font-size: 10px; color: #888; }
        .letterhead-footer-img { width: 100%; max-height: 90px; margin-top: 30px; }
    </style>
</head>
<body>
    {{-- ── Letterhead ────────────────────────────────────────────────────
         Branded header image when configured, else logo + resort name. --}}
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
        <strong>Ref:</strong> TRF/{{ $transfer->id }}/{{ \Carbon\Carbon::now()->format('Y') }}<br>
        <strong>Date:</strong> {{ \Carbon\Carbon::now()->format('d M Y') }}
    </div>

    <div class="title">EMPLOYEE TRANSFER LETTER</div>

    <p>Dear {{ $employeeName }},</p>

    <p class="body-text">
        We are pleased to inform you that, following internal review and approval, you are being
        transferred within {{ $resort->resort_name ?? 'the organization' }}. The details of your
        {{ strtolower($transfer->transfer_status) }} transfer are set out below.
    </p>

    <table class="details">
        <tr><td class="label">Employee Name</td><td>{{ $employeeName }}</td></tr>
        <tr><td class="label">Employee ID</td><td>{{ $transfer->employee->Emp_id ?? '—' }}</td></tr>
        <tr><td class="label">Current Department</td><td>{{ optional($transfer->currentDepartment)->name ?? '—' }}</td></tr>
        <tr><td class="label">New Department</td><td>{{ optional($transfer->targetDepartment)->name ?? '—' }}</td></tr>
        <tr><td class="label">Current Position</td><td>{{ optional($transfer->currentPosition)->position_title ?? '—' }}</td></tr>
        <tr><td class="label">New Position</td><td>{{ optional($transfer->targetPosition)->position_title ?? '—' }}</td></tr>
        <tr><td class="label">Transfer Type</td><td>{{ $transfer->transfer_status }}</td></tr>
        <tr><td class="label">Effective Date</td><td>{{ \Carbon\Carbon::parse($transfer->effective_date)->format('d M Y') }}</td></tr>
        @if($transfer->transfer_status === 'Temporary' && $transfer->temporary_from)
            <tr><td class="label">Temporary Period</td><td>
                {{ \Carbon\Carbon::parse($transfer->temporary_from)->format('d M Y') }}
                to {{ \Carbon\Carbon::parse($transfer->temporary_to)->format('d M Y') }}
            </td></tr>
        @endif
        @if($transfer->proposed_salary)
            <tr><td class="label">Proposed Salary</td><td>{{ number_format($transfer->proposed_salary, 2) }}</td></tr>
        @endif
    </table>

    <p class="body-text">
        All other terms and conditions of your employment remain unchanged. You are requested to
        report to your new department on the effective date mentioned above. We wish you continued
        success in your new role.
    </p>

    {{-- ── Signature block ───────────────────────────────────────────────
         Uses the configured e-signature image when available; otherwise
         the signatory name/title are rendered as a typed block. --}}
    <div class="signature">
        @if(!empty($signatureImage))
            <img src="{{ $signatureImage }}" style="height:55px;" alt="signature"><br>
        @endif
        <div class="sig-line">
            <strong>{{ $signatoryName ?? 'Human Resources Department' }}</strong><br>
            {{ $signatoryTitle ?? 'For and on behalf of ' . ($resort->resort_name ?? 'the Management') }}
        </div>
    </div>

    <p class="footer-note">
        This is a system-generated transfer letter. For queries, please contact the HR Department.
    </p>

    {{-- ── Optional branded footer image ───────────────────────────────── --}}
    @if($hasLetterhead && !empty($lh['footerImage']))
        <img src="{{ $lh['footerImage'] }}" class="letterhead-footer-img" alt="letterhead footer">
    @endif
</body>
</html>
