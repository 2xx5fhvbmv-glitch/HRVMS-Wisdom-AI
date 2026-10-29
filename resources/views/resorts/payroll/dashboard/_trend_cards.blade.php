{{--
    OT Trend, Pension, Tax and Budget Comparison — plain HTML/SVG cards (no Chart.js).
    Styles: public/resorts_assets/css/payroll-trend-cards.css · behaviour: public/resorts_assets/js/payroll-trend-cards.js
    Data comes from the same GET endpoints the old charts used (see window.PTC in dashboard.blade.php); nothing is queried here.
--}}
@php
    $ptcYears = fn (int $back) => range((int) now()->year, (int) now()->year - $back);
    $ptcCard = function (string $title, array $years, string $label = 'Year') {
        return ['title' => $title, 'years' => $years, 'label' => $label];
    };
    $ptcHero = '<div class="hero">
            <div class="hm"><div class="cap" data-cap></div><div class="hrow"><span class="big"><span class="cur" data-cur></span><span data-n>—</span><span class="u" data-u></span></span><span class="chip" data-chip hidden></span></div></div>
            <div class="side" data-side hidden><div class="k" data-sk></div><div class="v" data-sv></div></div>
            <div class="empty-hero" data-empty hidden></div>
        </div>';
@endphp

<div class="col-xl-3 col-md-6">
    <section class="card ptc" id="ptcOt">
        <div class="pc-h"><h3 class="t">OT Trend</h3>
            <select class="rs-sel" aria-label="Year">@foreach ($ptcYears(5) as $y)<option value="{{ $y }}">{{ $y }}</option>@endforeach</select>
        </div>
        {!! $ptcHero !!}
        <div class="viz ot" id="ptcOtViz"></div>
        <div class="leg"><span><i></i><span id="ptcOtL1"></span></span><span id="ptcOtL2w"><i class="d"></i><span id="ptcOtL2"></span></span></div>
    </section>
</div>

<div class="col-xl-3 col-md-6 @if(App\Helpers\Common::checkRouteWisePermission('payroll.pension.index', config('settings.resort_permissions.view')) == false) d-none @endif">
    <section class="card ptc" id="ptcPn">
        <div class="pc-h"><h3 class="t">Pension</h3>
            <select class="rs-sel" aria-label="Year">@foreach ($ptcYears(2) as $y)<option value="{{ $y }}">{{ $y }}</option>@endforeach</select>
        </div>
        {!! $ptcHero !!}
        <div class="viz focus" id="ptcPnViz"><div class="pills" id="ptcPnPills"></div><div class="mx" id="ptcPnX"></div></div>
    </section>
</div>

<div class="col-xl-3 col-md-6">
    <section class="card ptc" id="ptcTx">
        <div class="pc-h"><h3 class="t">Tax</h3>
            <select class="rs-sel" aria-label="Year">@foreach ($ptcYears(5) as $y)<option value="{{ $y }}">{{ $y }}</option>@endforeach</select>
        </div>
        {!! $ptcHero !!}
        <div class="viz focus" id="ptcTxViz"><div class="stairs" id="ptcTxStairs"></div><div class="st-x" id="ptcTxX"></div></div>
    </section>
</div>

<div class="col-xl-3 col-md-6">
    <section class="card ptc" id="ptcBg">
        <div class="pc-h"><h3 class="t">Budget Comparison</h3>
            <select class="rs-sel" aria-label="Year">@foreach ($ptcYears(5) as $y)<option value="{{ $y }}">{{ $y }}</option>@endforeach</select>
        </div>
        {!! $ptcHero !!}
        <div class="viz focus" id="ptcBgViz">
            <div class="pace" id="ptcBgPace"></div>
            <div class="var" id="ptcBgVar"><span class="zl">On budget</span></div>
            <div class="mx" id="ptcBgX"></div>
        </div>
    </section>
</div>
