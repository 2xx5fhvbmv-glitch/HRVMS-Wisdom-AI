@extends('resorts.layouts.app')
@section('page_tab_title' ,"Dashboard")

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
<style>
    /* WAI Insights: fixed card height (matching the Talent Acquisition
       module's WAI Insights card, the reference for this pattern) instead
       of the previous JS-driven sync to Payroll Comparison's height — the
       insights list scrolls internally instead of stretching the card.
       Payroll Comparison (below) is pinned to the same height so the two
       stay visually paired without runtime JS. */
    #card-wiINsightPayroll {
        height: 512px !important;
        max-height: 512px !important;
        display: flex;
        flex-direction: column;
    }
    #card-wiINsightPayroll .leaveUser-main { overflow-y: auto; flex: 1 1 auto; min-height: 0; }

    /* ---- Payroll Overview card (redesigned Payroll Expenses chart) ----
       border-radius matches the site-wide .card default (25px) — same
       curvature as the Service Charges and Payroll overview boxes next
       to it, so the row reads as one unified set instead of the 16px
       from the original spec standing out against its neighbors. */
    .payroll-overview-card {
        background: #FFFFFF;
        border: 1px solid #E2EBEC;
        border-radius: 25px;
        box-shadow: 0 4px 16px rgba(20,35,42,0.06);
        padding: 20px 22px;
    }
    .payroll-overview-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }
    .payroll-overview-header h3 { margin: 0; font-weight: 700; color: #14232A; }
    .payroll-overview-subtitle { margin: 4px 0 0; font-size: 13px; color: #5D6F75; }
    /* Same filter-dropdown size/spacing as the Service Charges card's
       filter (matched to its actual rendered computed style — this
       header isn't wrapped in .card-title itself, since that class also
       adds a bottom divider line the approved design doesn't have here). */
    .payroll-overview-header select.form-select {
        font-size: 14px;
        padding: 4px 30px 4px 16px;
    }
    .payroll-overview-legend {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 20px;
        margin: 14px 0 0;
    }
    .po-legend-item {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: none;
        background: none;
        padding: 0;
        font-size: 13px;
        font-weight: 600;
        color: #14232A;
        cursor: pointer;
        transition: color .15s ease, opacity .15s ease;
    }
    .po-legend-item.po-dimmed { color: #93A4A9; opacity: .6; }
    .po-legend-swatch {
        width: 18px;
        height: 3px;
        border-radius: 2px;
        display: inline-block;
        flex-shrink: 0;
    }
    .payroll-overview-chart-wrap {
        position: relative;
        height: clamp(260px, 42vh, 360px);
    }
    .po-tooltip {
        position: absolute;
        top: 0;
        left: 0;
        pointer-events: none;
        opacity: 0;
        transform: translate(-9999px, -9999px);
        transition: opacity .1s ease;
        background: #FFFFFF;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(20,35,42,0.16);
        padding: 12px 16px;
        font-size: 13px;
        color: #14232A;
        z-index: 10;
        min-width: 175px;
    }
    .po-tooltip-title { font-weight: 700; margin-bottom: 6px; }
    .po-tooltip-row { display: flex; align-items: center; gap: 8px; margin-top: 4px; white-space: nowrap; }
    .po-tooltip-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
    .po-tooltip-value { margin-left: auto; font-weight: 600; padding-left: 14px; }

    .payroll-tabs-card { border-radius: 25px; }
    .payroll-tabs-header { display: flex; align-items: center; justify-content: space-between; }
    .payroll-view-all { flex-shrink: 0; white-space: nowrap; }
    .payroll-status-tabs .nav-tabs .nav-link { display: inline-flex; align-items: center; gap: 8px; }
    .payroll-tab-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 20px;
        height: 20px;
        padding: 0 6px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 600;
        background: #E7E7E7;
        color: #666666;
    }
    .payroll-status-tabs .nav-tabs .nav-link.active .payroll-tab-badge {
        background: #014653;
        color: #FFFFFF;
    }
    .payroll-tab-table thead th {
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: .02em;
        color: #667085;
        background: rgba(1,70,83,0.06);
        border-bottom: 1px solid rgba(1,70,83,0.12);
        padding: 12px 10px !important;
    }
    .payroll-tab-table tbody tr:hover { background: rgba(1,70,83,0.04); }
    .payroll-tab-table td.text-end,
    .payroll-tab-table th.text-end { font-variant-numeric: tabular-nums; }

    /* Shared, explicit min-height so the two paired cards line up exactly
       regardless of small natural-content differences, now that the
       .card-heigth ancestor's auto-stretch is turned off for this row
       (see .dept-comparison-row below). */
    .dept-distribution-card,
    .payroll-distributions-card { min-height: 472px; }
    .dept-distribution-card {
        border: 1px solid #E2EBEC;
        border-radius: 20px;
        box-shadow: 0 6px 24px rgba(20,35,42,0.06);
    }
    /* Exact 60/40 split (Bootstrap's col-xl-* only offers 12ths, e.g. 7/5 =
       58.3%/41.6%). Below this breakpoint both fall back to col-lg-12 —
       full width, stacked, equal size either way. */
    @media (min-width: 1200px) {
        .dept-comparison-col-dist { flex: 0 0 60%; max-width: 60%; }
        .dept-comparison-col-pdist { flex: 0 0 40%; max-width: 40%; }
    }
    /* This row sits inside a much larger ancestor row carrying the site's
       .card-heigth class, whose ".card-heigth .card { height:100% }" rule
       (default.css) applies to ANY nested .card via descendant selector —
       including these two, despite this row not having that class itself.
       Combined with Bootstrap's default row align-items:stretch, that made
       both cards balloon to match whichever was tallest, leaving a visible
       empty gap in the shorter one. align-items:flex-start keeps each card
       at its own natural height instead. */
    .dept-comparison-row { align-items: flex-start; }
    .dept-card-header,
    .dept-modal-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
    }
    /* Matches the site's standard .card-title spacing (default.css) so this
       card's title lines up with OT Trend / Pension / etc — the card's own
       20px padding already accounts for the top gap, so no extra top
       padding is added here (that had been double-counting before). */
    .dept-card-header { padding-bottom: 8px; border-bottom: 1px solid #E7E7E7; margin-bottom: 12px; }
    .dept-modal-header { padding: 20px 24px; border-bottom: 1px solid #E2EBEC; }
    .dept-header-left { display: flex; align-items: flex-start; gap: 12px; min-width: 0; }
    .dept-header-title { margin: 0; font-size: 18px; font-weight: 700; color: #14232A; }
    .dept-total-line { font-size: 14px; color: #5D6F75; margin-top: 2px; }
    .dept-total-line-modal { margin-top: 0; font-size: 15px; }
    .dept-total-value { font-weight: 700; color: #14232A; }
    .dept-modal-subtitle { font-size: 13px; color: #5D6F75; margin-top: 2px; }
    .dept-modal-header-right { display: flex; align-items: center; gap: 16px; flex-shrink: 0; }
    .dept-expand-btn {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #014653;
        color: #fff;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        cursor: pointer;
        flex-shrink: 0;
        transition: background .15s ease;
    }
    .dept-expand-btn:hover { background: #026778; }

    .dept-tiles-wrap {
        position: relative;
        width: 100%;
        height: 352px;
        padding: 12px 20px 20px;
        box-sizing: border-box;
    }
    .dept-tiles-wrap-modal {
        flex: 1;
        height: auto;
        padding: 16px 24px 24px;
    }
    .dept-tiles-empty { width: 100%; padding: 40px 20px; }
    .dept-tile {
        position: absolute;
        box-sizing: border-box;
        padding: 3px;
    }
    .dept-tile-inner {
        width: 100%;
        height: 100%;
        border-radius: 14px;
        padding: 12px 14px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        overflow: hidden;
    }
    .dept-tile-name {
        font-size: 13px;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .dept-tile-pct {
        font-size: 24px;
        font-weight: 700;
        line-height: 1.1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .dept-tile-amount {
        font-size: 12px;
        opacity: .85;
        margin-top: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .dept-tile.tile-md .dept-tile-amount { display: none; }
    .dept-tile.tile-md .dept-tile-pct { font-size: 18px; }
    .dept-tile.tile-sm .dept-tile-amount { display: none; }
    .dept-tile.tile-sm .dept-tile-pct { font-size: 14px; }
    .dept-tile.tile-sm .dept-tile-name { font-size: 11px; }
    .dept-tile.tile-sm .dept-tile-inner { padding: 8px 10px; justify-content: center; gap: 2px; }

    /* Payroll Distributions — paired beside Distribution by Department,
       same white/border/radius/shadow language. */
    .payroll-distributions-card {
        border: 1px solid #E2EBEC;
        border-radius: 20px;
        box-shadow: 0 6px 24px rgba(20,35,42,0.06);
        display: flex;
        flex-direction: column;
    }
    /* Matches the site's standard .card-title spacing (default.css) so this
       card's title lines up with Distribution by Department, OT Trend,
       Pension, etc. */
    .pdist-header { padding-bottom: 8px; border-bottom: 1px solid #E7E7E7; margin-bottom: 12px; }
    .pdist-header-title { margin: 0; font-size: 18px; font-weight: 700; color: #14232A; }
    .pdist-header-subtitle { margin: 4px 0 0; font-size: 13px; color: #5D6F75; }
    .pdist-gauge-wrap { position: relative; width: 100%; height: 190px; margin-bottom: 28px; }
    .pdist-legend-row { margin: 0 0 16px; }
    .pdist-tile {
        border: 1px solid #E2EBEC;
        border-radius: 14px;
        padding: 12px 14px;
        height: 100%;
    }
    .pdist-tile-top { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
    .pdist-tile-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
    .pdist-tile-name { font-size: 13px; color: #5D6F75; }
    .pdist-tile-amount {
        font-size: 16px;
        font-weight: 500;
        color: #14232A;
        font-variant-numeric: tabular-nums;
        margin-bottom: 4px;
    }
    .pdist-tile-pct { font-size: 13px; font-weight: 600; }
    .pdist-total { text-align: center; margin: auto 0 0; font-size: 13px; color: #5D6F75; }
    .pdist-total .pdist-total-value { font-weight: 700; color: #14232A; }

    .dept-modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(20,35,42,0.45);
        backdrop-filter: blur(4px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1055;
        padding: 20px;
    }
    .dept-modal {
        background: #fff;
        width: 90vw;
        height: 82vh;
        border-radius: 20px;
        border: 1px solid #E2EBEC;
        box-shadow: 0 20px 60px rgba(20,35,42,0.25);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    /* Payroll Comparison — matches the app's standard 25px card radius,
       paired visually with WAI Insights beside it. Fixed height (512px)
       matches its own natural content size (2 donut gauges + legends +
       totals, which shouldn't scroll/clip) and is pinned here so WAI
       Insights can match it via a plain CSS value instead of runtime JS. */
    .payroll-comparison-card {
        border-radius: 25px;
        height: 512px !important;
        padding-bottom: 8px;
    }
    .pc-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 20px 20px 4px;
    }
    .pc-header-title { margin: 0; font-size: 18px; font-weight: 700; color: #14232A; }
    .pc-header .form-select { font-size: 14px; padding: 4px 24px 4px 10px; }
    .pc-columns { padding: 8px 20px 4px; margin: 0; }
    .pc-col {
        min-width: 0;
        text-align: center;
        min-height: 390px;
        display: flex;
        flex-direction: column;
    }
    .pc-period-pill { margin-bottom: 14px; align-self: center; }
    .pc-donut-wrap { width: 100%; height: 190px; position: relative; margin: 0 auto 14px; flex-shrink: 0; }
    .pc-legend { text-align: left; display: flex; flex-direction: column; gap: 10px; }
    .pc-legend-row { display: flex; align-items: center; gap: 8px; }
    .pc-legend-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
    .pc-legend-label {
        font-size: 13px;
        color: #5D6F75;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .pc-legend-amount {
        font-size: 13px;
        font-weight: 600;
        color: #14232A;
        margin-left: auto;
        padding-left: 8px;
        white-space: nowrap;
    }
    .pc-compare-line { margin: 16px 0 4px; align-self: center; }
    .pc-compare-pill i { font-size: 10px; margin-right: 4px; }
    .pc-compare-text { font-size: 13px; color: #5D6F75; margin-left: 6px; }
    .pc-empty {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 20px 10px;
        color: #93A4A9;
    }
    .pc-empty p { margin-bottom: 12px; font-size: 13px; }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Payroll</span>
                        <h1>Dashboard</h1>
                    </div>
                </div>
                <div class="col-auto ms-auto"><a href="{{route('payroll.payslip.index')}}" class="btn btn-sm btn-theme @if(App\Helpers\Common::checkRouteWisePermission('payroll.run',config('settings.resort_permissions.view')) == false) d-none @endif">Share Payslips</a></div>
                @php
                    $currentEmployee = Auth::guard('resort-admin')->user()->GetEmployee ?? null;
                    $rankPos = $currentEmployee ? App\Helpers\Common::getEmployeeRankPosition($currentEmployee) : ['rank' => null];
                    $currentRank = $rankPos['rank'] ?? '';

                    // Original gate: Supervisor only.
                    $isSupervisor = $currentRank === 'SUP';

                    // Additional gate: Finance department HOD / XCOM.
                    // Finance leadership owns payroll execution alongside HR,
                    // so the Run Payroll button now appears for them too.
                    // Uses the canonical Common::isFinanceDepartment helper
                    // (matches "Finance", "Accounting", "Accounts", common
                    // short codes, and loose-contains variants like
                    // "Finance & Accounting") rather than hard-coding a
                    // single department name.
                    $isFinanceLead = $currentEmployee
                        && in_array($currentRank, ['HOD', 'EXCOM'], true)
                        && App\Helpers\Common::isFinanceDepartment($currentEmployee->Dept_id ?? null);

                    $canRunPayroll = $isSupervisor || $isFinanceLead;
                @endphp
                @if($canRunPayroll)
                <div class="col-auto"><a href="{{route('payroll.run')}}" class="btn btn-theme" onclick="localStorage.removeItem('currentStep');localStorage.removeItem('payroll_id');localStorage.removeItem('selectedEmployees');localStorage.removeItem('selectedEmployeesIds');localStorage.removeItem('deductions');">Run Payroll</a></div>
                @endif
            </div>
        </div>

        <div class="row g-3 g-xxl-4 card-heigth">
            <div class="col-lg-3 col-sm-6">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0  fw-500">Total Employees</p>
                            <strong>{{$total_employees}}</strong>
                        </div>
                        <a href="#">
                            <img src="assets/images/arrow-right-circle.svg" alt="" class="img-fluid">
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0  fw-500">Paid Employees</p>
                            <strong>{{$total_paid_employees}} <small>/{{$total_employees}}</small></strong>
                        </div>
                        <a href="#">
                            <img src="assets/images/arrow-right-circle.svg" alt="" class="img-fluid">
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6 @if(App\Helpers\Common::checkRouteWisePermission('payroll.run',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0 fw-500">Last Payroll</p>
                            <strong>
                                {!! Common::formatCurrency($lastPayroll->total_payroll ?? 0, 'USD') !!}
                            </strong>
                        </div>
                        <div class="text-end">
                            <span>{{ $lastPayroll ? \Carbon\Carbon::flexible($lastPayroll->updated_at)->format('d M Y') : '-' }}</span><br>
                            <span class="badge badge-themeSuccess">
                                {{ ucfirst($lastPayroll->status ?? 'N/A') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-sm-6 @if(App\Helpers\Common::checkRouteWisePermission('payroll.run',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0 fw-500">Upcoming Payroll</p>
                            <strong>
                                @if(($upcomingPayroll->total_payroll ?? 0) > 0)
                                    {!! Common::formatCurrency($upcomingPayroll->total_payroll, 'USD') !!}
                                @else
                                    {!! Common::formatCurrency($upcomingEstimated ?? 0, 'USD') !!}
                                    @if($isEstimated ?? false)
                                        <small class="text-muted d-block" style="font-size: 11px;">(Estimated)</small>
                                    @endif
                                @endif
                            </strong>
                        </div>
                        <div class="text-end">
                            <span>{{ $upcomingCutoffDate->format('d M Y') }}</span><br>
                            <span class="badge badge-themeWarning">
                                {{ ucfirst($upcomingPayroll->status ?? 'Pending') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7 @if(App\Helpers\Common::checkRouteWisePermission('payroll.run',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card payroll-overview-card">
                    <div class="payroll-overview-header">
                        <div>
                            <h3>Payroll Overview</h3>
                            <p class="payroll-overview-subtitle">Monthly salary, overtime &amp; service charge</p>
                        </div>
                        <div class="form-group">
                            <select class="form-select YearWisePayrollExpense" id="yearFilter" aria-label="Default select example">
                                <?php
                                $currentYear = date('Y');
                                for ($i = 0; $i < 3; $i++) {
                                    $startYear = $currentYear - $i;
                                    $endYear = $startYear + 1;
                                    echo "<option value=\"$startYear\"";
                                    if ($i == 0)
                                    {
                                        echo " selected";
                                    }
                                    echo ">$startYear</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="payroll-overview-chart-wrap">
                        <canvas id="myStackedBarChart"></canvas>
                        <div class="po-tooltip" id="payrollOverviewTooltip"></div>
                    </div>
                    <div class="payroll-overview-legend" id="payrollOverviewLegend">
                        <button type="button" class="po-legend-item" data-index="0">
                            <span class="po-legend-swatch" style="background:#0E8A9E"></span>Salary
                        </button>
                        <button type="button" class="po-legend-item" data-index="1">
                            <span class="po-legend-swatch" style="background:#14603F"></span>Overtime
                        </button>
                        <button type="button" class="po-legend-item" data-index="2">
                            <span class="po-legend-swatch" style="background:#6F74E0"></span>Service Charge
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 @if(App\Helpers\Common::checkRouteWisePermission('payroll.run',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card card-serviceCharges">
                    <div class=" card-title">
                        <div class="row justify-content-between align-items-center g-md-3 g-1">
                            <div class="col">
                                <h3 class="text-nowrap">Service Charges</h3>
                            </div>
                            <div class="col-auto">
                                <div class="form-group">
                                    <select class="form-select YearWiseServichCharges" aria-label="Default select example">
                                        <?php
                                        $currentYear = date('Y');
                                        for ($i = 0; $i < 3; $i++) {
                                            $startYear = $currentYear - $i;
                                            $endYear = $startYear + 1;
                                            echo "<option value=\"$startYear\"";
                                            if ($i == 0)
                                            {
                                                echo " selected";
                                            }
                                            echo ">$startYear</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 align-items-center">
                        <div class="col-12">
                            <canvas id="myDoughnutChart"></canvas>
                        </div>
                        <div class="col-12">
                            <div class="row g-2"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 @if(App\Helpers\Common::checkRouteWisePermission('payroll.run',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card">
                    <div class="card-title">
                        <h3>Payroll overview</h3>
                    </div>
                    <div class="mb-xl-4 mb-3">
                        <label for="month" class="form-label">MONTH</label>
                        <select class="form-select select2t-none" id="month" aria-label="Default select example">
                            @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}" {{ now()->month == $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-xl-4 mb-3 pb-1 pb-xxl-3">
                        <label for="year" class="form-label">YEAR</label>
                        <select class="form-select select2t-none" id="year" aria-label="Default select example">
                            @foreach(range(now()->year - 5, now()->year + 1) as $y)
                                <option value="{{ $y }}" {{ now()->year == $y ? 'selected' : '' }}>
                                    {{ $y }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <a href="#" class="btn btn-themeBlue @if(App\Helpers\Common::checkRouteWisePermission('payroll.run',config('settings.resort_permissions.view')) == false) d-none @endif" id="viewPayroll">View Payroll</a>
                </div>
            </div>
            <div class="col-xl-6 col-md-6 @if(App\Helpers\Common::checkRouteWisePermission('payroll.run',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card card-wiINsightPayroll" id="card-wiINsightPayroll" >
                    @php $meta = $payrollInsights['_meta'] ?? null; @endphp
                    <div class=" card-title">
                        <div class="row justify-content-between align-items-center g-md-3 g-1">
                            <div class="col">
                                <h3 class="text-nowrap">WAI Insights</h3>
                            </div>
                            <div class="col-auto text-end" style="font-size:12px;line-height:1.3;">
                                @if($meta)
                                    <div class="text-muted">Updated {{ $meta['generated_at']->diffForHumans() }}</div>
                                    @if($meta['can_regenerate'])
                                        <a href="?regenerate_insights=1" class="a-linkTheme">Regenerate</a>
                                    @else
                                        <span class="text-muted" title="{{ $meta['next_available']->format('d M Y, H:i') }}">Regenerate {{ $meta['next_available']->diffForHumans() }}</span>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="leaveUser-main">
                        @foreach([['key'=>'trend','modal'=>'payrollInsightTrendModal'],['key'=>'overtime','modal'=>'payrollInsightOvertimeModal'],['key'=>'expat','modal'=>'payrollInsightExpatModal'],['key'=>'allowance','modal'=>'payrollInsightAllowanceModal']] as $pc)
                        <div class="leaveUser-block">
                            <div class="img">
                                <img src="{{ URL::asset('resorts_assets/images/wisdom-ai-small.svg') }}" alt="image">
                            </div>
                            <div>
                                <h6>{{ $payrollInsights[$pc['key']]['title'] ?? '' }}</h6>
                                <p>{{ $payrollInsights[$pc['key']]['body'] ?? '' }}</p>
                                @if(!empty($payrollInsights[$pc['key']]['recommendation']))
                                    <p class="mb-2" style="color:#2EACB3;"><strong>Recommendation:</strong> {{ $payrollInsights[$pc['key']]['recommendation'] }}</p>
                                @endif
                                <div>
                                    <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#{{ $pc['modal'] }}" class="a-linkTheme">View Details</a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

              {{-- Payroll Comparison — relocated here beside WAI Insights so its
                   height is no longer tied to Distribution by Department, which
                   now pairs with Payroll Distributions instead. --}}
              <div class="col-xl-6 col-lg-6 @if(App\Helpers\Common::checkRouteWisePermission('payroll.run',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="comparison-wrapper">
                    @include('resorts.renderfiles.payroll_comparison_card', ['payrollData' => $payrollData])
                </div>
            </div>

            {{-- Payrolls (Draft / In Approval / Completed) — merged into one
                 tabbed card. Each tab's table below is exactly the markup
                 that used to be its own separate card: same data variables
                 ($draftPayrolls/$approvalPayrolls/$lockedPayrolls), same
                 @foreach/@forelse loops, same columns, same status-badge
                 logic, same View/Edit&Resubmit links — only the outer
                 presentation changed. Per-tab visibility mirrors exactly
                 what each section had before merging: Draft and Completed
                 were gated by the payroll.run permission, Approval was not
                 — preserved as-is rather than applying one gate to the
                 whole card, which would have changed who can see what. --}}
            @php
                $canSeeDraftCompletedTabs = App\Helpers\Common::checkRouteWisePermission('payroll.run', config('settings.resort_permissions.view'));
                // Prefer In Approval when it has pending payrolls; otherwise
                // Draft — unless Draft/Completed are permission-hidden, in
                // which case Approval (always visible) is the only safe
                // default so the active tab is never one with no visible button.
                $defaultPayrollTab = ($approvalPayrolls->isNotEmpty() || !$canSeeDraftCompletedTabs) ? 'approval' : 'draft';
            @endphp
            <div class="col-12">
                <div class="card payroll-tabs-card">
                    <div class="card-title">
                        <h3>Payrolls</h3>
                    </div>
                    <div class="tab-theme payroll-status-tabs">
                        <div class="payroll-tabs-header">
                            <ul class="nav nav-tabs" id="payrollStatusTab" role="tablist">
                                @if($canSeeDraftCompletedTabs)
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link @if($defaultPayrollTab === 'draft') active @endif" id="payrollDraftTab" data-bs-toggle="tab" data-bs-target="#payrollDraftPane" type="button" role="tab" aria-controls="payrollDraftPane" aria-selected="@if($defaultPayrollTab === 'draft') true @else false @endif">
                                        <i class="fa-solid fa-pencil"></i> Draft
                                        <span class="payroll-tab-badge">{{ $draftPayrolls->count() }}</span>
                                    </button>
                                </li>
                                @endif
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link @if($defaultPayrollTab === 'approval') active @endif" id="payrollApprovalTab" data-bs-toggle="tab" data-bs-target="#payrollApprovalPane" type="button" role="tab" aria-controls="payrollApprovalPane" aria-selected="@if($defaultPayrollTab === 'approval') true @else false @endif">
                                        <i class="fa-solid fa-clipboard-check"></i> In Approval
                                        <span class="payroll-tab-badge">{{ $approvalPayrolls->count() }}</span>
                                    </button>
                                </li>
                                @if($canSeeDraftCompletedTabs)
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="payrollCompletedTab" data-bs-toggle="tab" data-bs-target="#payrollCompletedPane" type="button" role="tab" aria-controls="payrollCompletedPane" aria-selected="false">
                                        <i class="fa-solid fa-lock"></i> Completed
                                        <span class="payroll-tab-badge">{{ $lockedPayrolls->count() }}</span>
                                    </button>
                                </li>
                                @endif
                            </ul>
                            @if($canSeeDraftCompletedTabs)
                            <a href="{{ route('payroll.drafts.list') }}" id="payrollDraftViewAll" class="a-link payroll-view-all @if($defaultPayrollTab !== 'draft') d-none @endif">View All</a>
                            @endif
                        </div>
                        <div class="tab-content" id="payrollStatusTabContent">
                            @if($canSeeDraftCompletedTabs)
                            <div class="tab-pane fade @if($defaultPayrollTab === 'draft') show active @endif" id="payrollDraftPane" role="tabpanel" aria-labelledby="payrollDraftTab" tabindex="0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0 payroll-tab-table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Period</th>
                                                <th>Employees</th>
                                                <th class="text-end">Total Amount</th>
                                                <th>Created</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($draftPayrolls as $index => $draft)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ \Carbon\Carbon::parse($draft->start_date)->format('d M Y') }} - {{ \Carbon\Carbon::parse($draft->end_date)->format('d M Y') }}</td>
                                                    <td>{{ $draft->employee_count }}</td>
                                                    <td class="text-end">{!! Common::formatCurrency($draft->total_payroll ?? 0, 'USD') !!}</td>
                                                    <td>{{ \Carbon\Carbon::flexible($draft->created_at)->format('d M Y') }}</td>
                                                    <td><span class="badge badge-themeGray">{{ ucfirst($draft->status) }}</span></td>
                                                    <td>
                                                        <a href="{{ route('payroll.run') }}?resume={{ $draft->id }}" class="btn btn-sm btn-themeBlue" onclick="localStorage.setItem('payroll_id','{{ $draft->id }}');localStorage.setItem('currentStep','7');">
                                                            <i class="fa-solid fa-eye"></i> View
                                                        </a>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="7" class="text-center text-muted py-3">No draft payrolls yet.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            @endif
                            <div class="tab-pane fade @if($defaultPayrollTab === 'approval') show active @endif" id="payrollApprovalPane" role="tabpanel" aria-labelledby="payrollApprovalTab" tabindex="0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0 payroll-tab-table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Period</th>
                                                <th>Employees</th>
                                                <th class="text-end">Total Amount</th>
                                                <th>Finance EXCOM</th>
                                                <th>HR EXCOM</th>
                                                <th>GM</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($approvalPayrolls as $index => $ap)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ \Carbon\Carbon::parse($ap->start_date)->format('d M Y') }} - {{ \Carbon\Carbon::parse($ap->end_date)->format('d M Y') }}</td>
                                                    <td>{{ $ap->employee_count }}</td>
                                                    <td class="text-end">{!! Common::formatCurrency($ap->total_payroll ?? 0, 'USD') !!}</td>
                                                    @foreach($ap->approvals as $approval)
                                                        <td>
                                                            @if($approval->status === 'approved')
                                                                <span class="badge badge-themeSuccess"><i class="fa-solid fa-check"></i> {{ $approval->approver_name }}</span>
                                                            @elseif($approval->status === 'rejected')
                                                                <span class="badge badge-themeDanger" title="{{ $approval->remarks }}"><i class="fa-solid fa-times"></i> Rejected</span>
                                                                @if($approval->remarks)
                                                                    <small class="d-block text-danger" style="font-size:10px;">{{ Str::limit($approval->remarks, 30) }}</small>
                                                                @endif
                                                            @else
                                                                <span class="badge badge-themeWarning">Pending</span>
                                                            @endif
                                                        </td>
                                                    @endforeach
                                                    <td>
                                                        @if($ap->has_rejection)
                                                            <span class="badge badge-themeDanger">Rejected</span>
                                                        @elseif($ap->status === 'approved')
                                                            <span class="badge badge-themeSuccess">Approved</span>
                                                        @elseif($ap->status === 'pending_approval')
                                                            <span class="badge badge-themeWarning">Pending Approval</span>
                                                        @else
                                                            <span class="badge badge-themeGray">Draft</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($ap->has_rejection && $ap->status === 'draft')
                                                            <a href="{{ route('payroll.run') }}?resume={{ $ap->id }}" class="btn btn-sm btn-themeBlue"
                                                               onclick="localStorage.setItem('payroll_id','{{ $ap->id }}');localStorage.setItem('currentStep','1');">
                                                                <i class="fa-solid fa-pen-to-square"></i> Edit & Resubmit
                                                            </a>
                                                        @else
                                                            <a href="{{ route('payroll.run') }}?resume={{ $ap->id }}&viewonly=1" class="btn btn-sm btn-themeBlue"
                                                               onclick="localStorage.setItem('payroll_id','{{ $ap->id }}');localStorage.setItem('currentStep','7');">
                                                                <i class="fa-solid fa-eye"></i> View
                                                            </a>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="9" class="text-center text-muted py-3">No payrolls in approval.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            @if($canSeeDraftCompletedTabs)
                            <div class="tab-pane fade" id="payrollCompletedPane" role="tabpanel" aria-labelledby="payrollCompletedTab" tabindex="0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0 payroll-tab-table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Period</th>
                                                <th>Employees</th>
                                                <th class="text-end">Total Payroll</th>
                                                <th>Locked On</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($lockedPayrolls as $index => $lp)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ \Carbon\Carbon::parse($lp->start_date)->format('d M Y') }} - {{ \Carbon\Carbon::parse($lp->end_date)->format('d M Y') }}</td>
                                                    <td>{{ $lp->total_employees }}</td>
                                                    <td class="text-end">{!! Common::formatCurrency($lp->total_payroll ?? 0, 'USD') !!}</td>
                                                    <td>{{ \Carbon\Carbon::flexible($lp->updated_at)->format('d M Y, h:i A') }}</td>
                                                    <td>
                                                        <a href="{{ route('payroll.view', ['payroll_id' => base64_encode($lp->id)]) }}" class="btn btn-sm btn-themeBlue">
                                                            <i class="fa-solid fa-eye"></i> View
                                                        </a>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="6" class="text-center text-muted py-3">No completed payrolls yet.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- <div class="col-xl-3 col-md-6">
                <div class="card  card-activityLog" id="card-activityLog">
                    <div class=" card-title">
                        <div class="row justify-content-between align-items-center g-md-3 g-1">
                            <div class="col">
                                <h3 class="text-nowrap">Activity Log</h3>
                            </div>
                            <div class="col-auto">
                                <a href="#" class="a-link">View All</a>
                            </div>
                        </div>
                    </div>
                    <div class="leaveUser-main">
                        <div class="leaveUser-block">
                            <div class="date-block bg">DEC <h5>01</h5> Mon</div>
                            <p>Lorem ipsum is simply dummy text of the typesetting industry Lorem typesetting
                                industry ipsum.</p>
                        </div>
                        <div class="leaveUser-block">
                            <div class="date-block bg">DEC <h5>01</h5> Mon</div>
                            <p>Lorem ipsum is simply dummy text of the typesetting industry Lorem typesetting
                                industry ipsum.</p>
                        </div>
                        <div class="leaveUser-block">
                            <div class="date-block bg">DEC <h5>01</h5> Mon</div>
                            <p>Lorem ipsum is simply dummy text of the typesetting industry Lorem typesetting
                                industry ipsum.</p>
                        </div>
                        <div class="leaveUser-block">
                            <div class="date-block bg">DEC <h5>01</h5> Mon</div>
                            <p>Lorem ipsum is simply dummy text of the typesetting industry Lorem typesetting
                                industry ipsum.</p>
                        </div>
                    </div>
                </div>
            </div> -->


            

          

            <div class="row g-3 g-xxl-4 dept-comparison-row">
                <div class="dept-comparison-col-dist col-lg-12">
                    <div class="card dept-distribution-card">
                        <div class="dept-card-header">
                            <div class="dept-header-left">
                                <div>
                                    <h3 class="dept-header-title">Distribution by Department</h3>
                                    <div class="dept-total-line">Total <span class="dept-total-value" id="deptDistributionTotal"></span></div>
                                </div>
                            </div>
                            <button type="button" class="dept-expand-btn" id="deptExpandBtn" title="Expand distribution by department" aria-label="Expand distribution by department">
                                <i class="fa-solid fa-up-right-and-down-left-from-center"></i>
                            </button>
                        </div>
                        <div class="dept-tiles-wrap" id="departmentTiles">
                            <div class="dept-tiles-empty text-center text-muted py-4 d-none" id="departmentTilesEmpty">No department data available yet.</div>
                        </div>
                    </div>
                </div>

                {{-- Payroll Distributions — paired with Distribution by Department:
                     one slices spend by department, the other shows how that same
                     spend was physically paid out. Same route/AJAX/data as before
                     (route('payroll.distribution') -> {cashPayments, bankTransfers}),
                     only the rendering changed (half-gauge instead of a full ring). --}}
                <div class="dept-comparison-col-pdist col-lg-12 @if(App\Helpers\Common::checkRouteWisePermission('payroll.run',config('settings.resort_permissions.view')) == false) d-none @endif">
                    <div class="card payroll-distributions-card">
                        <div class="pdist-header">
                            <h3 class="pdist-header-title">Payroll Distributions</h3>
                            <p class="pdist-header-subtitle">How this period's payroll was paid out</p>
                        </div>
                        <div class="pdist-gauge-wrap">
                            <canvas id="myDoughnutChartPayroll"></canvas>
                        </div>
                        <div class="row g-2 pdist-legend-row" id="payroll-distribution-container">
                            <div class="col-6">
                                <div class="pdist-tile">
                                    <div class="pdist-tile-top">
                                        <span class="pdist-tile-dot" style="background:#0E8A9E;"></span>
                                        <span class="pdist-tile-name">Bank Transfers</span>
                                    </div>
                                    <div class="pdist-tile-amount">{{ Common::GetResortCurrencySymbol() }} 0.00</div>
                                    <div class="pdist-tile-pct" style="color:#0E8A9E;">0% of payout</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="pdist-tile">
                                    <div class="pdist-tile-top">
                                        <span class="pdist-tile-dot" style="background:#014653;"></span>
                                        <span class="pdist-tile-name">Cash Payments</span>
                                    </div>
                                    <div class="pdist-tile-amount">{{ Common::GetResortCurrencySymbol() }} 0.00</div>
                                    <div class="pdist-tile-pct" style="color:#014653;">0% of payout</div>
                                </div>
                            </div>
                        </div>
                        <p class="pdist-total">Total paid out this period: <span class="pdist-total-value">{{ Common::GetResortCurrencySymbol() }} 0.00</span></p>
                    </div>
                </div>
            </div>

            <div class="dept-modal-backdrop d-none" id="deptModalBackdrop">
                <div class="dept-modal" role="dialog" aria-modal="true" aria-labelledby="deptModalTitle">
                    <div class="dept-modal-header">
                        <div class="dept-header-left">
                            <div>
                                <h3 class="dept-header-title" id="deptModalTitle">Distribution by Department</h3>
                                <div class="dept-modal-subtitle" id="deptModalSubtitle"></div>
                            </div>
                        </div>
                        <div class="dept-modal-header-right">
                            <div class="dept-total-line dept-total-line-modal">Total <span class="dept-total-value" id="deptModalTotal"></span></div>
                            <button type="button" class="dept-expand-btn" id="deptCollapseBtn" title="Collapse distribution by department" aria-label="Collapse distribution by department">
                                <i class="fa-solid fa-down-left-and-up-right-to-center"></i>
                            </button>
                        </div>
                    </div>
                    <div class="dept-tiles-wrap dept-tiles-wrap-modal" id="departmentTilesModal"></div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class=" card-title">
                        <div class="row justify-content-between align-items-center g-md-2 g-1">
                            <div class="col">
                                <h3 class="text-nowrap">OT Trend</h3>
                            </div>
                            <div class="col-auto">
                                <div class="form-group">
                                   <select id="yearSelect" class="form-select" style="width: auto; display: inline-block;">
                                        @for ($y = now()->year; $y >= now()->year - 5; $y--)
                                            <option value="{{ $y }}">{{ $y }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <canvas id="myLineChart" width="365" height="326"></canvas>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 @if(App\Helpers\Common::checkRouteWisePermission('payroll.pension.index', config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card">
                    <div class="card-title ">
                        <div class="row justify-content-between align-items-center g-md-3 g-1">
                            <div class="col">
                                <h3 class="text-nowrap">Pension</h3>
                            </div>
                            <div class="col-auto">
                                <div class="form-group">
                                    <select class="form-select YearWisePensionData" aria-label="Default select example">
                                        <?php
                                        $currentYear = date('Y');
                                        for ($i = 0; $i < 3; $i++) {
                                            $startYear = $currentYear - $i;
                                            echo "<option value=\"$startYear\"";
                                            if ($i == 0)
                                            {
                                                echo " selected";
                                            }
                                            echo ">$startYear</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <canvas id="pension" width="365" height="293" class="mb-2"></canvas>
                    <div class="row g-2 justify-content-center">
                        <div class="col-auto">
                            <div class="doughnut-label">
                                <span class="bg-theme"></span>Employee
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="doughnut-label">
                                <span class="bg-themeLightBlue"></span>Employer
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-title">
                        <h3>Tax</h3>
                    </div>
                    <div class="taxChart-block">
                        <canvas id="taxChart" width="328" height="328"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-title">
                        <div class="row justify-content-between align-items-center g-md-3 g-1">
                            <div class="col">
                                <h3 class="text-nowrap">Budget Comparison</h3>
                            </div>
                            <div class="col-auto">
                                <select class="form-select YearWiseBudgetComparison" aria-label="Default select example" style="width: auto; display: inline-block;">
                                    @for ($i = date('Y'); $i >= date('Y') - 5; $i--)
                                        <option value="{{ $i }}" {{ $i == date('Y') ? 'selected' : '' }}>{{ $i }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                    </div>
                    <canvas id="budgetComp" width="365" height="293" class="mb-2"></canvas>
                    <div class="row g-2 justify-content-center">
                        <div class="col-auto">
                            <div class="doughnut-label">
                                <span class="bg-theme"></span>Budgeted Amount
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="doughnut-label">
                                <span class="bg-themeLightBlue"></span>Actual Amount
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal -->
<div class="modal fade" id="assign-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small modal-assign">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Payroll Components</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="basic_salary" class="form-label">Total Basic Salary</label>
                    <input type="text" id="basic_salary" class="form-control" value="54,415.20">
                </div>
                <div class="mb-3">
                    <label for="service_charge" class="form-label">Service Charge Values</label>
                    <input type="text" id="service_charge" class="form-control" value="145.00">
                </div>
                <div class="mb-3">
                    <label for="normal_ot" class="form-label">Normal OT</label>
                    <input type="text" id="normal_ot" class="form-control" value="{{ Common::GetResortCurrencySymbol() }} 1,110.00 (120 Hrs)">
                </div>
                <div>
                    <label for="holiday_ot" class="form-label">Holiday OT</label>
                    <input type="text" id="holiday_ot" class="form-control" value="{{ Common::GetResortCurrencySymbol() }} 142.00 (70Hrs)">
                </div>

            </div>
            <div class="modal-footer">
                <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                <a href="#" class="btn btn-themeBlue">Submit</a>
            </div>
        </div>
    </div>
</div>

@include('resorts.payroll.dashboard._insight_modals')
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
    document.getElementById('viewPayroll').addEventListener('click', function () {
        let selectedMonth = document.getElementById('month').value;
        let selectedYear = document.getElementById('year').value;

        let viewPayrollUrl = "{{ route('payroll.view.all') }}";

        // Construct the first and last date of the selected month
        let startDate = moment(`${selectedYear}-${selectedMonth}-01`);
        let endDate = moment(startDate).endOf('month');

        // Store selected date range in hidden input field
        $("#hiddenInput").daterangepicker({
            autoApply: true,
            startDate: startDate,
            endDate: endDate,
            opens: 'right',
            parentEl: '#datapicker',
            alwaysShowCalendars: true,
            linkedCalendars: false,
            locale: {
                format: "DD-MM-YYYY", // Ensure consistent format
            }
        });

        // Redirect to payroll view with selected month and year
        window.location.href = `${viewPayrollUrl}?month=${selectedMonth}&year=${selectedYear}`;
    });
</script>
<script type="text/javascript">
    // tooltip 
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
      
    document.addEventListener('DOMContentLoaded', function() {
        // Show/hide chart labels
        document.querySelectorAll('.chartImg-block').forEach(block => {
            block.addEventListener('click', function() {
                const chartLabelBlock = this.closest('.row').querySelector('.chartLabel-block');
                chartLabelBlock.classList.remove('d-none');
            });
        });
    });

</script>
<script type="module">
    // Payroll Comparison donuts: builds the current/previous period charts
    // from window.pcComparisonData, which payroll_comparison_card.blade.php
    // re-declares every time its markup is rendered (initial page load, and
    // again after the month-selector AJAX swap replaces the card's HTML).
    window.initializePayrollComparisonDonuts = function() {
        var data = window.pcComparisonData;
        if (!data) return;

        if (window.pcDonutCurrent && typeof window.pcDonutCurrent.destroy === 'function') {
            window.pcDonutCurrent.destroy();
            window.pcDonutCurrent = null;
        }
        if (window.pcDonutPrevious && typeof window.pcDonutPrevious.destroy === 'function') {
            window.pcDonutPrevious.destroy();
            window.pcDonutPrevious = null;
        }

        function buildDonut(canvasId, period) {
            var canvasEl = document.getElementById(canvasId);
            if (!canvasEl || !period) return null;

            // The container's real width isn't always settled yet at chart
            // creation time (e.g. right after an AJAX HTML swap or while a
            // sibling's flex layout is still resolving), which left Chart.js
            // falling back to the canvas's default 300x150 size instead of
            // the actual column width — an explicit resize() after creation
            // forces it to re-measure once layout has settled.

            var centerTextPlugin = {
                id: canvasId + 'CenterText',
                afterDraw: function (chart) {
                    var ctx = chart.ctx;
                    var width = chart.width, height = chart.height;
                    ctx.save();
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.font = '500 12px Poppins';
                    ctx.fillStyle = '#93A4A9';
                    ctx.fillText('TOTAL', width / 2, height / 2 - 14);
                    ctx.font = '600 21px Poppins';
                    ctx.fillStyle = '#14232A';
                    ctx.fillText(currencySymbol + ' ' + Math.round(convertAmount(period.total, 'USD')).toLocaleString(), width / 2, height / 2 + 12);
                    ctx.restore();
                }
            };

            var chart = new Chart(canvasEl.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: period.items.map(function (it) { return it.label; }),
                    datasets: [{
                        data: period.items.map(function (it) { return it.amount; }),
                        backgroundColor: period.items.map(function (it) { return it.color; }),
                        borderColor: '#FFFFFF',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return context.label + ': ' + formatAmount(context.raw, 'USD');
                                }
                            }
                        }
                    }
                },
                plugins: [centerTextPlugin]
            });
            requestAnimationFrame(function () { chart.resize(); });
            return chart;
        }

        window.pcDonutCurrent = buildDonut('pcDonutCurrent', data.current);
        window.pcDonutPrevious = buildDonut('pcDonutPrevious', data.previous);
    }
    // Global variable to store the chart instance
    let myDoughnutChart = null;
    let serviceChargeAvg = 0;
    //Service Charges Chart
    const ctz = document.getElementById('myDoughnutChart').getContext('2d');
    const doughnutLabelsInsideN = {
        id: 'doughnutLabelsInsideN',
        afterDraw: function (chart) {
            var ctx = chart.ctx;
            chart.data.datasets.forEach(function (dataset, i) {
                var meta = chart.getDatasetMeta(i);
                if (!meta.hidden) {
                    meta.data.forEach(function (element, index) {
                        var dataValue = dataset.data[index];
                        var total = dataset.data.reduce(function (acc, val) {
                            return acc + val;
                        }, 0);
                        var percentage = ((dataValue / total) * 100).toFixed(0) + '%';
                        var position = element.tooltipPosition();
                        ctx.fillStyle = '#fff';
                        ctx.font = 'normal 18px Poppins';
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        ctx.fillText(percentage, position.x, position.y);
                    });
                }
            });
        }
    };

    const centerText = {
        id: 'centerText',
        afterDraw: function (chart) {
            const width = chart.width;
            const height = chart.height;
            const ctx = chart.ctx;

            ctx.restore();

            // Use the real average service charge (not sum of percentages)
            const formattedTotal = formatAmount(serviceChargeAvg, 'USD');

            // Text configuration
            ctx.textBaseline = 'middle';
            ctx.textAlign = 'center';

            // Total number
            ctx.font = '500 22px Poppins';
            ctx.fillStyle = '#222222';
            // ctx.fillText('$' + total, width / 2, height / 2 - 15);
            ctx.fillText(formattedTotal, width / 2, height / 2 - 15);

            // "Total" label
            ctx.font = '500 13px Poppins';
            ctx.fillStyle = '#222222';
            ctx.fillText('Avg', width / 2, height / 2 + 15);

            ctx.save();
        }
    };

    // Function to fetch and update the chart
    function GetServiceChargeChart() {
        $.ajax({
            url: "{{ route('chart.service-charges') }}", // Replace with your actual route
            type: "POST",
            data: {
                "_token": "{{ csrf_token() }}",
                "YearWiseServichCharges": $(".YearWiseServichCharges").val()
            },
            success: function (response) {
                console.log(response);
                const data = response.data;
                const total = response.total;
                const labels = data.map(item => item.label);
                const serviceCharges = data.map(item => item.service_charge);
                const serviceChargespercentage = data.map(item => item.percentage);
                // Set average for center text (total / number of months)
                serviceChargeAvg = data.length > 0 ? parseFloat(total.replace(/,/g, '')) / data.length : 0;
                const colors = ['#014653', '#53CAFF', '#EFB408', '#50B9BF', '#333333', '#8DC9C9'];

                 // Check if the chart exists and destroy it
                if (myDoughnutChart !== null && typeof myDoughnutChart.destroy === 'function') {
                    myDoughnutChart.destroy();
                }
                // Update the chart
                myDoughnutChart = new Chart(document.getElementById('myDoughnutChart'), {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: serviceChargespercentage,
                            backgroundColor: colors,
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        layout: {
                            padding: {
                                top: 10,
                                bottom: 10,
                                left: 0,
                                right: 0
                            }
                        }
                    },
                    plugins: [doughnutLabelsInsideN, centerText] // Attach the plugin to this chart only
                });
                // Update the side labels
                const labelContainer = document.querySelector('.row.g-2');
                let labelsHTML = '';
                data.forEach((item, index) => {
                    labelsHTML += `
                        <div class="col-6">
                            <div class="doughnut-label">
                                <span style="background-color: ${colors[index]}"></span>${item.label} <br>${formatAmount(item.service_charge, 'USD')}
                            </div>
                        </div>
                    `;
                });
                // Add total row
                labelsHTML += `
                    <div class="fw-500">Total: ${formatAmount(parseFloat(String(total).replace(/,/g, '')), 'USD')}</div>
                `;

                // Insert into the DOM
                labelContainer.innerHTML = labelsHTML;
            },
            error: function (xhr) {
                console.error("Failed to fetch chart data", xhr);
            }
        });
    }

    // Trigger data load on dropdown change
    $(document).on("change", ".YearWiseServichCharges", function () {
        GetServiceChargeChart();
    });

    // Trigger data load on dropdown change
    $(document).on("change", ".YearWisePensionData", function () {
        fetchPensionChartData();
    });

    // Initial chart load
    GetServiceChargeChart();

    fetchPayrollData();

    fetchDepartmentDistribution();

    fetchPensionChartData();

    renderEwtTaxChart();

        // Initialize progress bars for the initial server-rendered comparison card
        window.initializePayrollComparisonDonuts();


    const defaultYear = $('#yearSelect').val();
    fetchOtTrendChart(defaultYear);

    // On year change
    $('#yearSelect').on('change', function () {
        const selectedYear = $(this).val();
        fetchOtTrendChart(selectedYear);
    });    // Rendering the chart

    function fetchPayrollData() {
        $.ajax({
            url: "{{ route('payroll.distribution') }}", // Replace with your actual route
            type: "GET",
            success: function (response) {
                // Extracting cash & bank payment data — unchanged: same
                // endpoint, same response shape ({cashPayments, bankTransfers}).
                const cashPayments = Number(response.cashPayments) || 0;
                const bankTransfers = Number(response.bankTransfers) || 0;
                const totalPayroll = cashPayments + bankTransfers;

                const cashPercentage = totalPayroll > 0 ? ((cashPayments / totalPayroll) * 100) : 0;
                const bankPercentage = totalPayroll > 0 ? ((bankTransfers / totalPayroll) * 100) : 0;

                updateDoughnutChartPayroll(cashPayments, bankTransfers, cashPercentage, bankPercentage);

                const totalText = formatAmount(totalPayroll, 'USD');
                const container = document.getElementById('payroll-distribution-container');
                container.innerHTML = `
                    <div class="col-6">
                        <div class="pdist-tile">
                            <div class="pdist-tile-top">
                                <span class="pdist-tile-dot" style="background:#0E8A9E;"></span>
                                <span class="pdist-tile-name">Bank Transfers</span>
                            </div>
                            <div class="pdist-tile-amount">${formatAmount(bankTransfers, 'USD')}</div>
                            <div class="pdist-tile-pct" style="color:#0E8A9E;">${bankPercentage.toFixed(1)}% of payout</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="pdist-tile">
                            <div class="pdist-tile-top">
                                <span class="pdist-tile-dot" style="background:#014653;"></span>
                                <span class="pdist-tile-name">Cash Payments</span>
                            </div>
                            <div class="pdist-tile-amount">${formatAmount(cashPayments, 'USD')}</div>
                            <div class="pdist-tile-pct" style="color:#014653;">${cashPercentage.toFixed(1)}% of payout</div>
                        </div>
                    </div>
                `;
                document.querySelector('.pdist-total-value').textContent = totalText;
            },
            error: function (xhr) {
                console.error("Failed to fetch payroll chart data", xhr);
            }
        });
    }

    // Store chart instance globally
    var myDoughnutChartPayroll;

    // Payroll Distribution Chart (Cash Payments vs. Bank Transfers) — a
    // half-gauge doughnut (circumference:180, rotation:-90) with the real
    // Bank Transfer percentage centered underneath the arc.
    function updateDoughnutChartPayroll(cashAmount, bankAmount, cashPercentage, bankPercentage) {
        const canvasEl = document.getElementById('myDoughnutChartPayroll');
        const ctxPayroll = canvasEl.getContext('2d');

        if (myDoughnutChartPayroll) {
            myDoughnutChartPayroll.destroy();
        }

        const gaugeCenterText = {
            id: 'payrollDistCenterText',
            // afterDatasetsDraw runs one phase before the core Tooltip
            // plugin's own afterDraw — drawing here (instead of afterDraw)
            // keeps the tooltip on top on hover instead of hidden behind
            // this text. Same fix as doughnutLabelsInside/doughnutLabelsInsideN
            // elsewhere on this page.
            afterDatasetsDraw: function (chart) {
                var meta = chart.getDatasetMeta(0);
                var arc = meta.data[0];
                if (!arc) return;
                var ctx = chart.ctx;
                var x = arc.x, y = arc.y;
                // For a semicircle gauge, arc.y sits at the flat bottom edge
                // of the dome (the "equator" of the underlying full circle),
                // not its visual middle — both lines need to sit ABOVE it,
                // inside the hole, or they spill past the canvas/card edge
                // and collide with whatever sits right below (here, the
                // legend tiles).
                ctx.save();
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.font = '600 26px Poppins';
                ctx.fillStyle = '#0E8A9E';
                ctx.fillText(bankPercentage.toFixed(0) + '%', x, y - 45);
                ctx.font = '500 12px Poppins';
                ctx.fillStyle = '#5D6F75';
                ctx.fillText('via Bank Transfer', x, y - 20);
                ctx.restore();
            }
        };

        myDoughnutChartPayroll = new Chart(ctxPayroll, {
            type: 'doughnut',
            data: {
                labels: ['Bank Transfers', 'Cash Payments'],
                datasets: [{
                    data: [bankAmount, cashAmount],
                    backgroundColor: ['#0E8A9E', '#014653'],
                    borderColor: '#FFFFFF',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                circumference: 180,
                rotation: -90,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                var pct = context.dataIndex === 0 ? bankPercentage : cashPercentage;
                                return context.label + ': ' + formatAmount(context.raw, 'USD') + ' (' + pct.toFixed(1) + '%)';
                            }
                        }
                    }
                }
            },
            plugins: [gaugeCenterText]
        });

        // See the equivalent note in initializePayrollComparisonDonuts —
        // the container's width isn't always settled yet at chart-creation
        // time, so force a re-measure once layout has caught up.
        requestAnimationFrame(function () { myDoughnutChartPayroll.resize(); });
    }

    // Distribution by Department — proportional bento tile layout, with an
    // expand button that opens the same tiles (recomputed for the modal's
    // own size) across all departments in a modal. Same AJAX endpoint/
    // response shape as before ({what, value, color} per department); only
    // how it's rendered changed (tiles instead of a treemap canvas + legend).
    var DEPT_TILE_PALETTE = ['#014653', '#0e8a9e', '#6fb7c2', '#2f6bff', '#8a5cf6', '#0ea5e9', '#14603f', '#1f9d6b', '#d98a00', '#c26b3e', '#5d6f75', '#3a7d8c', '#4a5f8a', '#7a8a99', '#9fb0b5'];
    var DEPT_TILE_LIME = '#E0FF02';
    var DEPT_TILE_INK = '#14232A';
    var deptCurrentData = null;

    function deptTileTextColor(hex) {
        var r = parseInt(hex.slice(1, 3), 16);
        var g = parseInt(hex.slice(3, 5), 16);
        var b = parseInt(hex.slice(5, 7), 16);
        var luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
        return luminance > 0.6 ? DEPT_TILE_INK : '#FFFFFF';
    }

    // Squarified treemap: lays `items` (each with .area, summing to w*h) out
    // as rectangles that stay close to square, so tile area stays exactly
    // proportional to budget share regardless of department count.
    function squarifyDepartments(items, w0, h0) {
        var rects = [];

        function worst(row, sideLen) {
            var s = row.reduce(function (sum, it) { return sum + it.area; }, 0);
            var areas = row.map(function (it) { return it.area; });
            var rmax = Math.max.apply(null, areas);
            var rmin = Math.min.apply(null, areas);
            return Math.max((sideLen * sideLen * rmax) / (s * s), (s * s) / (sideLen * sideLen * rmin));
        }

        function layoutRow(remaining, x, y, w, h) {
            if (!remaining.length) return;
            if (remaining.length === 1) {
                rects.push($.extend({ x: x, y: y, w: w, h: h }, remaining[0]));
                return;
            }
            var shortSide = Math.min(w, h);
            var row = [remaining[0]];
            var i = 1;
            while (i < remaining.length) {
                var candidate = row.concat(remaining[i]);
                if (worst(candidate, shortSide) <= worst(row, shortSide)) {
                    row = candidate;
                    i++;
                } else {
                    break;
                }
            }
            var rest = remaining.slice(row.length);
            var rowArea = row.reduce(function (sum, it) { return sum + it.area; }, 0);
            if (w >= h) {
                var rowWidth = rowArea / h;
                var cy = y;
                row.forEach(function (it) {
                    var rh = it.area / rowWidth;
                    rects.push($.extend({ x: x, y: cy, w: rowWidth, h: rh }, it));
                    cy += rh;
                });
                layoutRow(rest, x + rowWidth, y, w - rowWidth, h);
            } else {
                var rowHeight = rowArea / w;
                var cx = x;
                row.forEach(function (it) {
                    var rw = it.area / rowHeight;
                    rects.push($.extend({ x: cx, y: y, w: rw, h: rowHeight }, it));
                    cx += rw;
                });
                layoutRow(rest, x, y + rowHeight, w, h - rowHeight);
            }
        }

        layoutRow(items, 0, 0, w0, h0);
        return rects;
    }

    // Renders `departmentData` as tiles inside `containerEl`, sized to
    // whatever pixel dimensions that container actually has right now — the
    // card container (~300px tall) and the modal container (~82vh tall)
    // have very different aspect ratios, so each gets its own layout pass
    // rather than reusing one precomputed set of proportions.
    function renderDeptTilesInto(containerEl, emptyEl, departmentData, total) {
        Array.prototype.forEach.call(containerEl.querySelectorAll('.dept-tile'), function (el) { el.remove(); });

        var sorted = departmentData
            .map(function (d) { return { what: d.what, value: parseFloat(d.value) || 0 }; })
            .filter(function (d) { return d.value > 0; })
            .sort(function (a, b) { return b.value - a.value; });

        if (!sorted.length || total <= 0) {
            if (emptyEl) emptyEl.classList.remove('d-none');
            return;
        }
        if (emptyEl) emptyEl.classList.add('d-none');

        var w0 = containerEl.clientWidth;
        var h0 = containerEl.clientHeight;
        if (!w0 || !h0) return;

        var scale = (w0 * h0) / total;
        var withArea = sorted.map(function (d) { return $.extend({ area: d.value * scale }, d); });
        var rects = squarifyDepartments(withArea, w0, h0);

        rects.forEach(function (r, index) {
            var pct = (r.value / total) * 100;
            var isLargest = index === 0;
            var bg = isLargest ? DEPT_TILE_LIME : DEPT_TILE_PALETTE[(index - 1) % DEPT_TILE_PALETTE.length];
            var textColor = isLargest ? DEPT_TILE_INK : deptTileTextColor(bg);
            var amountText = formatAmount(r.value, 'USD');

            var tile = document.createElement('div');
            tile.className = 'dept-tile';
            if (r.w < 96 || r.h < 62) {
                tile.classList.add('tile-sm');
            } else if (r.w < 150 || r.h < 96) {
                tile.classList.add('tile-md');
            }
            tile.style.left = r.x + 'px';
            tile.style.top = r.y + 'px';
            tile.style.width = r.w + 'px';
            tile.style.height = r.h + 'px';
            tile.title = r.what + ' · ' + amountText + ' (' + pct.toFixed(1) + '%)';

            var inner = document.createElement('div');
            inner.className = 'dept-tile-inner';
            inner.style.background = bg;
            inner.style.color = textColor;

            var nameEl = document.createElement('div');
            nameEl.className = 'dept-tile-name';
            nameEl.textContent = r.what;

            var bottomEl = document.createElement('div');

            var pctEl = document.createElement('div');
            pctEl.className = 'dept-tile-pct';
            pctEl.textContent = pct.toFixed(1) + '%';

            var amountEl = document.createElement('div');
            amountEl.className = 'dept-tile-amount';
            amountEl.textContent = amountText;

            bottomEl.appendChild(pctEl);
            bottomEl.appendChild(amountEl);
            inner.appendChild(nameEl);
            inner.appendChild(bottomEl);
            tile.appendChild(inner);
            containerEl.appendChild(tile);
        });
    }

    function renderDepartmentTiles(departmentData) {
        deptCurrentData = departmentData;

        var total = departmentData.reduce(function (sum, d) { return sum + (parseFloat(d.value) || 0); }, 0);
        var totalText = currencySymbol + ' ' + Math.round(convertAmount(total, 'USD')).toLocaleString();
        document.getElementById('deptDistributionTotal').textContent = totalText;
        document.getElementById('deptModalTotal').textContent = totalText;
        document.getElementById('deptModalSubtitle').textContent =
            'All ' + departmentData.length + ' department' + (departmentData.length === 1 ? '' : 's') + ' · sized by share of budget';

        renderDeptTilesInto(document.getElementById('departmentTiles'), document.getElementById('departmentTilesEmpty'), departmentData, total);

        if (!document.getElementById('deptModalBackdrop').classList.contains('d-none')) {
            renderDeptTilesInto(document.getElementById('departmentTilesModal'), null, departmentData, total);
        }
    }

    function openDeptModal() {
        document.getElementById('deptModalBackdrop').classList.remove('d-none');
        document.body.style.overflow = 'hidden';
        if (deptCurrentData) {
            var total = deptCurrentData.reduce(function (sum, d) { return sum + (parseFloat(d.value) || 0); }, 0);
            renderDeptTilesInto(document.getElementById('departmentTilesModal'), null, deptCurrentData, total);
        }
    }

    function closeDeptModal() {
        document.getElementById('deptModalBackdrop').classList.add('d-none');
        document.body.style.overflow = '';
    }

    $(document).on('click', '#deptExpandBtn', openDeptModal);
    $(document).on('click', '#deptCollapseBtn', closeDeptModal);
    $(document).on('click', '#deptModalBackdrop', function (e) {
        if (e.target === this) closeDeptModal();
    });
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && !document.getElementById('deptModalBackdrop').classList.contains('d-none')) {
            closeDeptModal();
        }
    });

    var deptTilesResizeTimer;
    $(window).on('resize', function () {
        clearTimeout(deptTilesResizeTimer);
        deptTilesResizeTimer = setTimeout(function () {
            if (!deptCurrentData) return;
            var total = deptCurrentData.reduce(function (sum, d) { return sum + (parseFloat(d.value) || 0); }, 0);
            renderDeptTilesInto(document.getElementById('departmentTiles'), document.getElementById('departmentTilesEmpty'), deptCurrentData, total);
            if (!document.getElementById('deptModalBackdrop').classList.contains('d-none')) {
                renderDeptTilesInto(document.getElementById('departmentTilesModal'), null, deptCurrentData, total);
            }
        }, 150);
    });

    function fetchDepartmentDistribution() {
        $.ajax({
            url: "{{ route('payroll.departmentDistribution') }}", // Replace with actual route
            type: "GET",
            success: function (response) {
                renderDepartmentTiles(response.data || []);
            },
            error: function (xhr) {
                console.error("Failed to fetch department distribution data", xhr);
            }
        });
    }

    var cty = document.getElementById('myStackedBarChart').getContext('2d');

    // Function to fetch chart data dynamically — unchanged: same endpoint,
    // same request param, same response shape (payrollCost/otCost/serviceCharge).
    function fetchChartData(year) {
        $.ajax({
            url: "{{ route('payroll.getExpenses') }}", // Adjust route accordingly
            method: "GET",
            data: { year: year },
            success: function (response) {
                if (response.success) {
                    updateChart(response.labels, response.data);
                }
            },
            error: function (xhr, error, code) {
                console.error("Error fetching chart data:", error);
            }
        });
    }

    // Function to update the chart dynamically — same data keys as before
    // (payrollCost/otCost/serviceCharge); only the on-screen series labels
    // changed (Salary/Overtime/Service Charge), set once at chart init below.
    function updateChart(labels, datasetValues) {
        myStackedBarChart.data.labels = labels;
        myStackedBarChart.data.datasets[0].data = datasetValues.payrollCost;
        myStackedBarChart.data.datasets[1].data = datasetValues.otCost;
        myStackedBarChart.data.datasets[2].data = datasetValues.serviceCharge;
        myStackedBarChart.update(); // Update the chart
    }

    // Subtle pale-teal gradient fill under the Salary line only.
    var payrollOverviewSalaryGradient = cty.createLinearGradient(0, 0, 0, 320);
    payrollOverviewSalaryGradient.addColorStop(0, 'rgba(14,138,158,0.22)');
    payrollOverviewSalaryGradient.addColorStop(1, 'rgba(14,138,158,0)');

    // Dashed vertical crosshair at the hovered month. Runs in
    // afterDatasetsDraw (not afterDraw) — same lesson as the Payroll
    // Distributions doughnut fix: this chart uses a custom `external`
    // tooltip (a plain HTML element, not canvas-drawn), so there's no
    // built-in canvas tooltip to fight over draw order with, but keeping
    // it in afterDatasetsDraw is still the correct phase for a plot-area
    // background element like a crosshair.
    var payrollOverviewCrosshair = {
        id: 'payrollOverviewCrosshair',
        afterDatasetsDraw: function (chart) {
            var active = chart.getActiveElements();
            if (!active || !active.length) return;
            var ctx = chart.ctx;
            var x = active[0].element.x;
            ctx.save();
            ctx.beginPath();
            ctx.setLineDash([4, 4]);
            ctx.moveTo(x, chart.chartArea.top);
            ctx.lineTo(x, chart.chartArea.bottom);
            ctx.lineWidth = 1;
            ctx.strokeStyle = 'rgba(1,70,83,0.3)';
            ctx.stroke();
            ctx.restore();
        }
    };

    // Custom white/rounded/shadowed tooltip (plain HTML positioned over the
    // canvas) — Chart.js's own "external" tooltip pattern, no extra library.
    function payrollOverviewTooltipHandler(context) {
        var tooltipEl = document.getElementById('payrollOverviewTooltip');
        var tooltipModel = context.tooltip;

        if (!tooltipModel || tooltipModel.opacity === 0 || !tooltipModel.dataPoints || !tooltipModel.dataPoints.length) {
            tooltipEl.style.opacity = 0;
            tooltipEl.style.transform = 'translate(-9999px, -9999px)';
            return;
        }

        var html = '<div class="po-tooltip-title">' + (tooltipModel.title && tooltipModel.title[0] || '') + '</div>';
        tooltipModel.dataPoints.forEach(function (dp) {
            html += '<div class="po-tooltip-row">'
                + '<span class="po-tooltip-dot" style="background:' + dp.dataset.borderColor + '"></span>'
                + dp.dataset.label
                + '<span class="po-tooltip-value">' + formatAmount(dp.raw, 'USD') + '</span>'
                + '</div>';
        });
        tooltipEl.innerHTML = html;

        tooltipEl.style.opacity = 1;
        var left = tooltipModel.caretX + 14;
        var top = tooltipModel.caretY - tooltipEl.offsetHeight / 2;
        // Keep the tooltip from spilling past the right edge of the chart.
        if (left + tooltipEl.offsetWidth > context.chart.width) {
            left = tooltipModel.caretX - tooltipEl.offsetWidth - 14;
        }
        tooltipEl.style.transform = 'translate(' + left + 'px, ' + top + 'px)';
    }

    // Initialize the chart
    var myStackedBarChart = new Chart(cty, {
        type: 'line',
        data: {
            labels: [], // Initially empty, will be updated via AJAX
            datasets: [
                {
                    label: 'Salary',
                    data: [],
                    borderColor: '#0E8A9E',
                    backgroundColor: payrollOverviewSalaryGradient,
                    fill: true,
                    borderWidth: 2.5,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: '#0E8A9E',
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 2,
                },
                {
                    label: 'Overtime',
                    data: [],
                    borderColor: '#14603F',
                    backgroundColor: 'transparent',
                    fill: false,
                    borderWidth: 2.5,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: '#14603F',
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 2,
                },
                {
                    label: 'Service Charge',
                    data: [],
                    borderColor: '#6F74E0',
                    backgroundColor: 'transparent',
                    fill: false,
                    borderWidth: 2.5,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: '#6F74E0',
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 2,
                },
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    enabled: false,
                    external: payrollOverviewTooltipHandler
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { autoSkip: false, maxRotation: 0, minRotation: 0, color: '#93A4A9' }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.06)', drawTicks: false },
                    border: { display: false },
                    ticks: { callback: function (v) { return formatAmount(v, 'USD'); }, color: '#93A4A9' }
                }
            }
        },
        plugins: [payrollOverviewCrosshair]
    });

    // Clickable legend: toggle a series' visibility and dim its label.
    $(document).on('click', '#payrollOverviewLegend .po-legend-item', function () {
        var index = parseInt($(this).data('index'), 10);
        var meta = myStackedBarChart.getDatasetMeta(index);
        var nowHidden = meta.hidden === null ? !myStackedBarChart.data.datasets[index].hidden : !meta.hidden;
        meta.hidden = nowHidden;
        $(this).toggleClass('po-dimmed', nowHidden);
        myStackedBarChart.update();
    });

    // Payrolls card: "View All" only applies to the Draft tab's list, so
    // only show it while Draft is the active tab — keeps the tab bar's
    // height identical across tabs instead of only Draft growing taller.
    $(document).on('shown.bs.tab', '#payrollStatusTab button[data-bs-toggle="tab"]', function (e) {
        $('#payrollDraftViewAll').toggleClass('d-none', e.target.id !== 'payrollDraftTab');
    });

    // Fetch initial chart data
    let initialYear = $("#yearFilter").val();
    fetchChartData(initialYear);

    // Change event for the year filter
    $("#yearFilter").change(function () {
        let selectedYear = $(this).val();
        fetchChartData(selectedYear);
    });

    $(document).on('change', '#monthSelector', function () {
        const selectedMonth = $(this).val();

        $.ajax({
            url: '{{ route("payroll.getPayrollComparison") }}',
            method: 'GET',
            data: { month: selectedMonth },
            beforeSend: function() {
                // Keep the dropdown visible, only replace the chart content
                $('.comparison-wrapper .pc-columns').css('opacity', '0.5');
            },
            success: function (response) {
                $('.comparison-wrapper').html(response.html);
                window.initializePayrollComparisonDonuts();
                $('[data-bs-toggle="tooltip"]').tooltip();
            },
            error: function () {
                $('.comparison-wrapper').html('<p class="text-danger">Error loading data.</p>');
            }
        });
    });



  
    function fetchOtTrendChart(selectedYear) {
        $.ajax({
            url: "{{ route('payroll.otTrendData') }}",
            type: "GET",
            data: { year: selectedYear },
            success: function (response) {
                const labels = response.labels;
                const data = response.data;

                const ctx = document.getElementById('myLineChart').getContext('2d');

                if (window.myLineChart && typeof window.myLineChart.destroy === 'function') {
                    window.myLineChart.destroy();
                }

                window.myLineChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'OT Hours',
                            data: data,
                            borderColor: '#2EACB3',
                            backgroundColor: 'rgba(46, 172, 179, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 5,
                            pointBackgroundColor: '#2EACB3'
                        }]
                    },
                    options: {
                        plugins: {
                            legend: { display: false }
                        },
                        layout: { padding: 0 },
                        scales: {
                            x: { grid: { display: false } },
                            y: {
                                beginAtZero: true,
                                grid: { display: false },
                                ticks: { callback: function(v) { return v + ' hrs'; } }
                            }
                        }
                    }
                });
            },
            error: function (xhr) {
                console.error("Failed to load OT trend data", xhr);
            }
        });
    }

    function renderLineChart(labels, dataPoints) {
        const ctx = document.getElementById('myLineChart').getContext('2d');

        if (window.myLineChart) {
            window.myLineChart.destroy(); // Destroy previous instance if needed
        }

        window.myLineChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Total OT Hours',
                    data: dataPoints,
                    borderColor: '#2EACB3',
                    backgroundColor: '#2EACB3',
                    borderWidth: 1,
                    fill: false,
                    tension: 0.4,
                    pointRadius: 0
                }]
            },
            options: {
                plugins: {
                    legend: { display: false }
                },
                layout: {
                    padding: { top: 0, bottom: 0, left: 0, right: 0 }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { display: true }
                    },
                    y: {
                        grid: { display: false },
                        beginAtZero: true,
                        ticks: { callback: function(v) { return formatAmount(v, 'USD'); } }
                    }
                }
            }
        });
    }

    function fetchPensionChartData() {
        $.ajax({
            url: "{{route('payroll.getMonthlyPensionData')}}", // API endpoint
            method: 'GET',
            data: {
                "_token": "{{ csrf_token() }}",
                "YearWisePensionData": $(".YearWisePensionData").val()
            },
            success: function (response) {
                // console.log(response);

                const labels = response.map(item => item.month);
                const employeeData = response.map(item => item.employee);
                const employerData = response.map(item => item.employer);

                updatePensionChart(labels, employeeData, employerData);
            }
        });
    }

    function updatePensionChart(labels, employeeData, employerData) {
        const ctx = document.getElementById('pension').getContext('2d');

        if (window.pensionChart) {
            window.pensionChart.destroy(); // Destroy previous chart instance
        }

        window.pensionChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Employee',
                        data: employeeData,
                        backgroundColor: '#014653',
                        borderColor: '#014653',
                        borderWidth: 1,
                        borderRadius: 3,
                        barThickness: 14
                    },
                    {
                        label: 'Employer',
                        data: employerData,
                        backgroundColor: '#2EACB3',
                        borderColor: '#2EACB3',
                        borderWidth: 1,
                        borderRadius: 3,
                        barThickness: 14
                    }
                ]
            },
            options: {
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: true,
                        callbacks: {
                            label: function (tooltipItem) {
                                return formatAmount(tooltipItem.raw, 'USD');
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { display: true }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) { return formatAmount(value, 'USD'); }
                        },
                        grid: { display: false },
                        border: { display: true }
                    }
                }
            }
        });
    }

    var budgetCompChart = null;

    function fetchBudgetComparison(year) {
        $.ajax({
            url: "{{ route('payroll.budgetComparison') }}",
            type: 'GET',
            data: { year: year },
            success: function (response) {
                var ctd = document.getElementById('budgetComp').getContext('2d');

                if (budgetCompChart) {
                    budgetCompChart.destroy();
                }

                // The year filter already shows which year is selected, so
                // the trailing year on each month label ("Jan 26") is
                // redundant on the axis itself — strip it, keep just the
                // month. Presentation only; response.labels is untouched.
                var monthOnlyLabels = response.labels.map(function (label) {
                    return label.replace(/\s+\d{2,4}$/, '');
                });

                budgetCompChart = new Chart(ctd, {
                    type: 'line',
                    data: {
                        labels: monthOnlyLabels,
                        datasets: [
                            {
                                label: 'Budgeted Amount',
                                data: response.budgeted,
                                borderColor: '#014653',
                                backgroundColor: '#014653',
                                borderWidth: 1,
                                fill: false,
                                tension: 0.4,
                                pointRadius: 0
                            },
                            {
                                label: 'Actual Amount',
                                data: response.actual,
                                borderColor: '#2EACB3',
                                backgroundColor: '#2EACB3',
                                borderWidth: 1,
                                fill: false,
                                tension: 0.4,
                                pointRadius: 0
                            },
                        ]
                    },
                    options: {
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        layout: {
                            padding: { top: 0, bottom: 0, left: 0, right: 0 }
                        },
                        scales: {
                            x: {
                                // No explicit tick rotation — same as OT
                                // Trend's x-axis, letting Chart.js pick its
                                // own natural angle so both charts share the
                                // same label style.
                                beginAtZero: true,
                                grid: { display: false },
                                border: { display: true }
                            },
                            y: {
                                grid: { display: false },
                                beginAtZero: true,
                                ticks: {
                                    callback: function (value) { return formatAmount(value, 'USD'); }
                                }
                            }
                        }
                    }
                });
            },
            error: function (xhr) {
                console.error("Failed to fetch budget comparison data", xhr);
            }
        });
    }

    fetchBudgetComparison($('.YearWiseBudgetComparison').val());

    $(document).on("change", ".YearWiseBudgetComparison", function () {
        fetchBudgetComparison($(this).val());
    });

    function renderEwtTaxChart(year = new Date().getFullYear()) {
        $.ajax({
            url: "{{ route('payroll.ewtBracketChart') }}",
            type: 'GET',
            data: { year: year },
            success: function (res) {
                const ctx = document.getElementById('taxChart').getContext('2d');

                if (window.taxChartInstance) window.taxChartInstance.destroy();

                // Convert data to display currency
                var chartData = res.data.map(function(v) { return convertAmount(v, 'USD'); });

                // Build labels with amounts for legend
                var legendLabels = res.labels.map(function(label, i) {
                    return label + ' (' + currencySymbol + chartData[i].toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ')';
                });

                window.taxChartInstance = new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: legendLabels,
                        datasets: [{
                            data: chartData,
                            backgroundColor: ['#014653', '#2EACB3', '#EFB408', '#50B9BF', '#333333'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'bottom'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(tooltipItem) {
                                        return tooltipItem.label;
                                    }
                                }
                            }
                        }
                    },
                    plugins: [{
                        id: 'taxLabelsInside',
                        // afterDatasetsDraw runs one phase before the core
                        // Tooltip plugin's own afterDraw — drawing here
                        // (instead of afterDraw) keeps the tooltip on top on
                        // hover instead of hidden behind this label. Same fix
                        // as doughnutLabelsInside/doughnutLabelsInsideN
                        // elsewhere on this page.
                        afterDatasetsDraw(chart) {
                            var ctx2 = chart.ctx;
                            var dataset = chart.data.datasets[0];
                            var meta = chart.getDatasetMeta(0);
                            meta.data.forEach(function(element, i) {
                                var value = dataset.data[i];
                                var pos = element.tooltipPosition();
                                ctx2.fillStyle = '#fff';
                                ctx2.font = 'bold 12px Arial';
                                ctx2.textAlign = 'center';
                                ctx2.textBaseline = 'middle';
                                ctx2.fillText(currencySymbol + value.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}), pos.x, pos.y);
                            });
                        }
                    }]
                });
            }
        });
    }

    function getRandomColor() {
        const hue = Math.floor(Math.random() * 360);
        return `hsl(${hue}, 70%, 60%)`;
    }

    const pieLabelsInside = {
        id: 'pieLabelsInside',
        afterDraw(chart) {
            const ctx = chart.ctx;
            const dataset = chart.data.datasets[0];
            const meta = chart.getDatasetMeta(0);
            const total = dataset.data.reduce((a, b) => a + b, 0);

            meta.data.forEach((element, i) => {
                const value = dataset.data[i];
                const percent = ((value / total) * 100).toFixed(1) + '%';
                const { x, y } = element.tooltipPosition();

                ctx.fillStyle = '#fff';
                ctx.font = 'bold 14px Arial';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(percent, x, y);
            });
        }
    };

    // var cte = document.getElementById('taxChart').getContext('2d');

    // // Custom plugin to display percentage labels inside the pie chart
    // const pieLabelsInside = {
    //     id: 'pieLabelsInside',
    //     afterDraw: function (chart) {
    //         var ctx = chart.ctx;
    //         chart.data.datasets.forEach(function (dataset, i) {
    //             var meta = chart.getDatasetMeta(i);
    //             if (!meta.hidden) {
    //                 meta.data.forEach(function (element, index) {
    //                     var dataValue = dataset.data[index];
    //                     var total = dataset.data.reduce(function (acc, val) {
    //                         return acc + val;
    //                     }, 0);
    //                     var percentage = ((dataValue / total) * 100).toFixed(0) + '%';

    //                     var position = element.tooltipPosition(); // Position for the label

    //                     ctx.fillStyle = '#fff'; // Label color
    //                     ctx.font = 'bold 18px Arial'; // Font style
    //                     ctx.textAlign = 'center';
    //                     ctx.textBaseline = 'middle';

    //                     // Draw the percentage label at the center of each slice
    //                     ctx.fillText(percentage, position.x, position.y);
    //                 });
    //             }
    //         });
    //     }
    // };

    // // Create the pie chart
    // var taxChart = new Chart(cte, {
    //     type: 'pie', // Change to 'pie' for pie chart
    //     data: {
    //         // labels: ['January 2024', 'February 2024', 'March 2024', 'April 2024', 'May 2024', 'June 2024'],
    //         datasets: [{
    //             data: [35, 45, 20],
    //             backgroundColor: ['#4C88BB', '#2EACB3', '#014653'],
    //             borderWidth: 0
    //         }]
    //     },
    //     options: {
    //         responsive: true,
    //         plugins: {
    //             pieLabelsInside: true, // Enable the custom plugin
    //             legend: {
    //                 display: false
    //             }
    //         },
    //         layout: {
    //             padding: {
    //                 top: 10,
    //                 bottom: 10,
    //                 left: 0,
    //                 right: 0
    //             }
    //         }
    //     },
    //     plugins: [pieLabelsInside] // Attach the plugin to this chart only
    // });

</script>
@endsection