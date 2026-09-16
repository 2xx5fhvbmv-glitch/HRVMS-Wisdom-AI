<!DOCTYPE html>
{{--
    Disciplinary Case Report — §6.14 of the e-signature spec.
    One frozen signature per committee member who submitted an
    investigation entry (see DisciplinaryController::InvestigationReportStore()
    / downloadDisciplinaryReportPdf()), never re-derived from a live
    signature_img. Gated on disciplinarySubmit.status === 'resolved'.
--}}
@php
    $lh = $letterhead ?? ['configured' => false];
    $hasLetterhead = !empty($lh['configured']) && !empty($lh['headerImage']);
@endphp
<html>
<head>
    <meta charset="utf-8">
    <title>Disciplinary Case Report</title>
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
        .entry-block { border: 1px solid #ddd; padding: 8px 10px; margin-bottom: 8px; }
        .entry-block .hdr { font-weight: bold; }
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
        <strong>Ref:</strong> DISC/{{ $case->id }}/{{ \Carbon\Carbon::now()->format('Y') }}<br>
        <strong>Date:</strong> {{ \Carbon\Carbon::now()->format('d M Y') }}
    </div>

    <div class="title">DISCIPLINARY CASE REPORT</div>

    <table class="details">
        <tr><td class="label">Employee</td><td>{{ optional(optional($case->GetEmployee)->resortAdmin)->full_name ?? '—' }}</td></tr>
        <tr><td class="label">Category</td><td>{{ optional($case->category)->DisciplinaryCategoryName ?? '—' }}</td></tr>
        <tr><td class="label">Offence</td><td>{{ optional($case->offence)->OffensesName ?? '—' }}</td></tr>
        <tr><td class="label">Action Taken</td><td>{{ optional($case->action)->ActionName ?? '—' }}</td></tr>
        <tr><td class="label">Incident Description</td><td>{{ $case->Incident_description ?? '—' }}</td></tr>
        <tr><td class="label">Status</td><td>{{ ucfirst($case->status ?? '—') }}</td></tr>
    </table>

    @if($investigations->isNotEmpty())
        <div class="section-title">Investigation Entries</div>
        @foreach($investigations as $inv)
            <div class="entry-block">
                <div class="hdr">{{ $inv->signature_name ?? 'Committee Member' }} — {{ $inv->outcome_type ?? '—' }}</div>
                <div>Investigation Date: {{ $inv->invesigation_date ?? '—' }} &nbsp;|&nbsp; Resolution Date: {{ $inv->resolution_date ?? '—' }}</div>
                @foreach($children->where('Disciplinary_P_id', $inv->id) as $child)
                    <div style="margin-top:6px;">
                        <strong>Stage:</strong> {{ $child->investigation_stage ?? '—' }}<br>
                        <strong>Findings/Recommendations:</strong> {{ $child->inves_find_recommendations ?? '—' }}<br>
                        <strong>Follow-up:</strong> {{ $child->follow_up_action ?? '—' }} — {{ $child->follow_up_description ?? '—' }}<br>
                        <strong>Resolution Note:</strong> {{ $child->resolution_note ?? '—' }}
                    </div>
                @endforeach
            </div>
        @endforeach
    @endif

    <div class="signature">
        @include('resorts.pdf_partials._signature_block', ['signatures' => $signatures ?? []])
    </div>

    <p class="footer-note">
        This is a system-generated disciplinary case report. For queries, please contact the HR Department.
    </p>

    @if($hasLetterhead && !empty($lh['footerImage']))
        <img src="{{ $lh['footerImage'] }}" class="letterhead-footer-img" alt="letterhead footer">
    @endif
</body>
</html>
