<!DOCTYPE html>
{{--
    Performance Review Report — §6.12 of the e-signature spec.
    Frozen signatures of the employee (self review) and their manager
    (manager review), each captured at the moment that stage was
    submitted (see ReviewController::submitSelfReview() /
    submitManagerReview() / downloadCycleReviewPdf()), never re-derived
    from a live signature_img. Gated on manager_review_status === 'completed'.
--}}
@php
    $lh = $letterhead ?? ['configured' => false];
    $hasLetterhead = !empty($lh['configured']) && !empty($lh['headerImage']);

    $renderFields = function ($structure, $data) {
        $out = '';
        foreach ((array) $structure as $idx => $field) {
            $ftype = $field['type'] ?? null;
            $fname = $field['name'] ?? ('field_' . $idx);
            $flabel = $field['label'] ?? '';
            if (in_array($ftype, ['header', 'paragraph'])) {
                $out .= '<div class="section-title">' . e($flabel) . '</div>';
                continue;
            }
            $raw = $data[$fname] ?? null;
            $display = $raw;
            if (in_array($ftype, ['select', 'radio-group']) && !empty($field['values'])) {
                foreach ($field['values'] as $opt) {
                    if (($opt['value'] ?? null) == $raw) { $display = $opt['label'] ?? $raw; break; }
                }
            } elseif ($ftype === 'file') {
                $display = $raw ? basename($raw) : null;
            } elseif (is_array($raw)) {
                $display = implode(', ', $raw);
            }
            $out .= '<div class="field-row"><div class="flabel">' . e($flabel) . '</div><div class="fval">' . e($display !== null && $display !== '' ? $display : '—') . '</div></div>';
        }
        return $out;
    };
@endphp
<html>
<head>
    <meta charset="utf-8">
    <title>Performance Review Report</title>
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
        .section-title { font-size: 13px; font-weight: bold; margin: 18px 0 6px; }
        .field-row { margin-bottom: 8px; }
        .field-row .flabel { font-weight: bold; }
        .field-row .fval { margin-top: 2px; }
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
        <strong>Ref:</strong> PERF/{{ $childCycle->id }}/{{ \Carbon\Carbon::now()->format('Y') }}<br>
        <strong>Date:</strong> {{ \Carbon\Carbon::now()->format('d M Y') }}
    </div>

    <div class="title">PERFORMANCE REVIEW REPORT</div>

    <table class="details">
        <tr><td class="label">Employee</td><td>{{ optional(optional($employee)->resortAdmin)->full_name ?? '—' }}</td></tr>
        <tr><td class="label">Position</td><td>{{ optional(optional($employee)->position)->position_title ?? '—' }}</td></tr>
        <tr><td class="label">Manager</td><td>{{ optional(optional($manager)->resortAdmin)->full_name ?? '—' }}</td></tr>
        <tr><td class="label">Cycle</td><td>{{ $childCycle->Cycle_Name ?? '—' }}</td></tr>
        <tr><td class="label">Self Review Date</td><td>{{ $childCycle->Self_review_date ?? '—' }}</td></tr>
        <tr><td class="label">Manager Review Date</td><td>{{ $childCycle->Manager_review_date ?? '—' }}</td></tr>
    </table>

    @if(!empty($selfStructure))
        <div class="section-title">Self Review</div>
        {!! $renderFields($selfStructure, $selfData) !!}
    @endif

    @if(!empty($managerStructure))
        <div class="section-title">Manager Review</div>
        {!! $renderFields($managerStructure, $managerData) !!}
    @endif

    <div class="signature">
        @include('resorts.pdf_partials._signature_block', ['signatures' => $signatures ?? []])
    </div>

    <p class="footer-note">
        This is a system-generated performance review report. For queries, please contact the HR Department.
    </p>

    @if($hasLetterhead && !empty($lh['footerImage']))
        <img src="{{ $lh['footerImage'] }}" class="letterhead-footer-img" alt="letterhead footer">
    @endif
</body>
</html>
