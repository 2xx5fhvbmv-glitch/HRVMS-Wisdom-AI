<!DOCTYPE html>
{{--
    Interview Assessment Response PDF — §6.4 of the e-signature spec.
    Same letterhead + signature-block pattern as transfer_letter_pdf/
    hiring_approval_pdf: branded header when configured, else resort logo
    + name; the interviewer's frozen signature at the bottom (see
    InterviewAssessmentController::downloadResponsePdf()), never the live
    ResortAdmin.signature_img.

    $formStructure is the dynamic form-builder field list (each field has
    at least 'name' and 'label'); $responses is a flat name => value map.
    Rendered as a plain label/value list — the same data the form-render
    JS widget shows on screen, just without that library (not usable
    server-side for a PDF).
--}}
@php
    $lh = $letterhead ?? ['configured' => false];
    $hasLetterhead = !empty($lh['configured']) && !empty($lh['headerImage']);
@endphp
<html>
<head>
    <meta charset="utf-8">
    <title>Interview Assessment Response</title>
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
        <strong>Ref:</strong> IA/{{ $response->id }}/{{ \Carbon\Carbon::now()->format('Y') }}<br>
        <strong>Date Submitted:</strong> {{ $response->created_at->format('d M Y') }}
    </div>

    <div class="title">INTERVIEW ASSESSMENT RESPONSE</div>

    <table class="details">
        <tr><td class="label">Interviewer</td><td>{{ optional($response->interviewer)->first_name }} {{ optional($response->interviewer)->last_name }}</td></tr>
        <tr><td class="label">Interviewee</td><td>{{ optional($response->interviewee)->first_name }} {{ optional($response->interviewee)->last_name }}</td></tr>
        <tr><td class="label">Form</td><td>{{ optional($response->form)->form_name ?? '—' }}</td></tr>
    </table>

    <table class="details">
        @forelse($formStructure as $field)
            @php $val = $responses[$field['name'] ?? ''] ?? null; @endphp
            @if(!empty($field['label']))
                <tr>
                    <td class="label">{{ $field['label'] }}</td>
                    <td>{{ is_array($val) ? implode(', ', $val) : ($val ?? '—') }}</td>
                </tr>
            @endif
        @empty
            <tr><td colspan="2">No responses recorded.</td></tr>
        @endforelse
    </table>

    <div class="signature">
        @include('resorts.pdf_partials._signature_block', ['signatures' => $signatures ?? []])
    </div>

    <p class="footer-note">
        This is a system-generated interview assessment record. For queries, please contact the HR Department.
    </p>

    @if($hasLetterhead && !empty($lh['footerImage']))
        <img src="{{ $lh['footerImage'] }}" class="letterhead-footer-img" alt="letterhead footer">
    @endif
</body>
</html>
