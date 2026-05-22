<!DOCTYPE html>
{{--
    Probation Letter PDF.

    Wraps the HR-authored probation letter template content ($letterContent,
    already placeholder-substituted) with the resort's configured Letterhead &
    E-signature (People > Configuration > Letterhead & Signature).

    When no letterhead is configured ($letterhead['configured'] === false) it
    falls back to a resort logo + name header and a typed signature block, so
    letter generation never breaks. Same pattern as the Transfer Letter PDF.
--}}
@php
    $lh = $letterhead ?? ['configured' => false];
    $hasLetterhead = !empty($lh['configured']) && !empty($lh['headerImage']);
@endphp
<html>
<head>
    <meta charset="utf-8">
    <title>Probation Letter</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        .letterhead { border-bottom: 2px solid #1f3c88; padding-bottom: 10px; margin-bottom: 25px; }
        .letterhead .logo { height: 60px; }
        .letterhead .resort-name { font-size: 20px; font-weight: bold; color: #1f3c88; margin: 0; }
        .letterhead-img { width: 100%; max-height: 130px; }
        .letterhead-address { font-size: 10px; color: #555; margin-top: 6px; }
        .letter-body { line-height: 1.7; text-align: justify; }
        .signature { margin-top: 50px; }
        .sig-line { border-top: 1px solid #000; width: 220px; padding-top: 4px; }
        .footer-note { margin-top: 40px; font-size: 10px; color: #888; }
        .letterhead-footer-img { width: 100%; max-height: 90px; margin-top: 30px; }
    </style>
</head>
<body>
    {{-- ── Letterhead — branded header image when configured, else logo. --}}
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

    {{-- ── Letter body — the HR-authored template content. --}}
    <div class="letter-body">
        {!! $letterContent !!}
    </div>

    {{-- ── Signature block — configured e-signature image + signatory. --}}
    <div class="signature">
        @if(!empty($signatureImage))
            <img src="{{ $signatureImage }}" style="height:55px;" alt="signature"><br>
        @endif
        <div class="sig-line">
            <strong>{{ $signatoryName ?? 'Human Resources Department' }}</strong><br>
            {{ $signatoryTitle ?? 'For and on behalf of ' . ($resort->resort_name ?? 'the Management') }}
        </div>
    </div>

    {{-- ── Optional branded footer image. --}}
    @if($hasLetterhead && !empty($lh['footerImage']))
        <img src="{{ $lh['footerImage'] }}" class="letterhead-footer-img" alt="letterhead footer">
    @endif
</body>
</html>
