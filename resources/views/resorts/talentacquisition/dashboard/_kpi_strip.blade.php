{{--
    Top KPI strip — Hiring Funnel / Open Vacancies / Avg. Time to Hire.
    Shared across admindashboard/hrdashboard/hoddashboard so the redesign
    stays visually identical on all three role variants. Reuses whatever
    $TotalApplicants/$Interviews/$Hired/$NewVacancies/$taInsights each
    controller action already computed — no new queries here.

    Data availability differs by dashboard (by design, not an oversight):
    - $NewVacancies (complete, unpaginated) is passed by admin_dashboard()
      and hr_dashboard() only. hod_dashboard() only has a limit(6) preview
      list, which would understate the true open-vacancy count, so Open
      Vacancies falls back to "—" there instead of showing a misleading number.
    - $taInsights (and its Time-to-Hire average) is only built/cached by
      hr_dashboard(). Admin/HOD show the "not enough data" fallback state.
--}}
@php
    $hfApplicants = $TotalApplicants ?? 0;
    $hfInterviews = $Interviews ?? 0;
    $hfHired = $Hired ?? 0;
    $hfInterviewPct = $hfApplicants > 0 ? round($hfInterviews / $hfApplicants * 100) : null;
    $hfHiredPct = $hfApplicants > 0 ? round($hfHired / $hfApplicants * 100) : null;

    $ovCount = null;
    $ovDepts = null;
    if (isset($NewVacancies)) {
        $ovCount = (int) $NewVacancies->sum('NoOfVacnacy');
        $ovDepts = $NewVacancies->pluck('Department')->filter()->unique()->count();
    }

    $tthAvg = $taInsights['tth']['details']['avg'] ?? null;
    $tthCount = isset($taInsights['tth']['details']['rows']) ? count($taInsights['tth']['details']['rows']) : null;
@endphp

<div class="col-lg-6">
    <div class="card card-hiring-funnel">
        <div class="card-title">
            <div class="row justify-content-between align-items-center g-3">
                <div class="col">
                    <h3>Hiring Funnel</h3>
                </div>
                <div class="col-auto">
                    <span class="hf-period">This period</span>
                </div>
            </div>
        </div>
        <div class="hf-segments">
            <div class="hf-segment hf-segment-applicants">
                <span class="hf-label">Applicants</span>
                <strong class="hf-count">{{ $hfApplicants }}</strong>
                <span class="hf-caption">Total received</span>
            </div>
            <div class="hf-segment hf-segment-interviews">
                <span class="hf-label">Interviews</span>
                <strong class="hf-count">{{ $hfInterviews }}</strong>
                <span class="hf-caption">{{ $hfInterviewPct !== null ? $hfInterviewPct.'% of applicants' : '—' }}</span>
            </div>
            <div class="hf-segment hf-segment-hired">
                <span class="hf-label">Hired</span>
                <strong class="hf-count">{{ $hfHired }}</strong>
                <span class="hf-caption">{{ $hfHiredPct !== null ? $hfHiredPct.'% conversion' : '—' }}</span>
            </div>
        </div>
    </div>
</div>

<div class="col-lg-3 col-md-6">
    <div class="card card-kpi-simple">
        <div class="card-title">
            <h3>Open Vacancies</h3>
        </div>
        <div class="kpi-body">
            @if($ovCount !== null)
                <strong class="kpi-count">{{ $ovCount }}</strong>
                @if($ovCount > 0)
                    <span class="kpi-caption">Across {{ $ovDepts }} department{{ $ovDepts == 1 ? '' : 's' }}</span>
                @else
                    <span class="kpi-caption">No open vacancies right now</span>
                @endif
            @else
                <strong class="kpi-count">—</strong>
                <span class="kpi-caption">Not available on this view</span>
            @endif
        </div>
    </div>
</div>

<div class="col-lg-3 col-md-6">
    <div class="card card-kpi-simple">
        <div class="card-title">
            <h3>Avg. Time to Hire</h3>
        </div>
        <div class="kpi-body">
            @if($tthAvg !== null)
                <strong class="kpi-count">{{ $tthAvg }} <span class="kpi-unit">days</span></strong>
                <span class="kpi-caption">Across {{ $tthCount }} hire{{ $tthCount == 1 ? '' : 's' }}</span>
            @else
                <strong class="kpi-count">—</strong>
                <span class="kpi-caption">Not enough closed hires yet</span>
            @endif
        </div>
    </div>
</div>

@once
<style>
    .card-hiring-funnel,
    .card-kpi-simple {
        border: 1px solid #E2EBEC;
    }
    .hf-period {
        font-size: 12px;
        color: #5D6F75;
        font-weight: 500;
    }
    .hf-segments {
        display: flex;
        border-radius: 16px;
        overflow: hidden;
        margin-top: 12px;
    }
    .hf-segment {
        flex: 1 1 0;
        padding: 18px 16px;
        color: #fff;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .hf-segment-applicants { background: #014653; }
    .hf-segment-interviews { background: #0E8A9E; }
    .hf-segment-hired { background: #1F9D6B; }
    .hf-label {
        font-size: 11px;
        font-weight: 600;
        letter-spacing: .04em;
        text-transform: uppercase;
        opacity: .85;
    }
    .hf-count {
        font-size: 26px;
        font-weight: 700;
        line-height: 1.25;
    }
    .hf-caption {
        font-size: 12px;
        opacity: .85;
    }
    .card-kpi-simple .kpi-body {
        display: flex;
        flex-direction: column;
        gap: 4px;
        margin-top: 12px;
    }
    .kpi-count {
        font-size: 30px;
        font-weight: 700;
        color: #14232A;
        line-height: 1.25;
    }
    .kpi-unit {
        font-size: 15px;
        font-weight: 600;
        color: #5D6F75;
    }
    .kpi-caption {
        font-size: 13px;
        color: #5D6F75;
    }
    @media (max-width: 576px) {
        .hf-segments {
            flex-direction: column;
        }
    }
</style>
@endonce
