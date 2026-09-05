<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Benefit Grid — Statement</title>
{{-- Real Poppins @font-face + the app's shared design tokens (same source
     the on-screen view uses) — this page is opened as its own browser tab
     (not a headless PDF engine), so normal relative asset URLs work. --}}
<link href="{{ URL::asset('resorts_assets/css/default.css') }}?v={{ @filemtime(public_path('resorts_assets/css/default.css')) }}" rel="stylesheet">
@include('resorts.layouts._design_tokens')
@include('resorts.benifitgrid._statement_styles')
<style>
    body{background:#fff;margin:0;padding:24px 16px}
</style>
</head>
<body>
<div class="bgst-page">
    {{-- Same resort branding the previous PDF showed (logo, name, address) —
         carried forward from $ResortData, just restyled to match the statement. --}}
    <div class="bgst-letterhead">
        <img src="{{ Common::GetResortLogo($ResortData->id) }}" alt="{{ $ResortData->resort_name }} logo">
        <div class="addr">
            <b>{{ $ResortData->resort_name }}</b>
            {{ $ResortData->address1 }}{{ $ResortData->address2 ? ', ' . $ResortData->address2 : '' }}<br>
            {{ $ResortData->state }} &mdash; {{ $ResortData->city }}, {{ $ResortData->zip }}<br>
            {{ $ResortData->country }}
        </div>
    </div>
    <div class="bgst-sheet">
        @include('resorts.benifitgrid._statement', [
            'benefit_grid' => $benefit_grid,
            'benefitGridChildren' => $benefitGridChildren,
            'selected_linen_array' => explode(',', $benefit_grid->linen ?? ''),
            'selected_laundry' => explode(',', $benefit_grid->laundry ?? ''),
            'selected_sports' => explode(',', $benefit_grid->sports_and_entertainment_facilities ?? ''),
        ])
    </div>
    @if(!empty($sitesettings->Footer))
        <div class="bgst-footer">{!! $sitesettings->Footer !!}</div>
    @endif
</div>
<script>
    window.onload = function () {
        window.print();
    }
</script>
</body>
</html>
