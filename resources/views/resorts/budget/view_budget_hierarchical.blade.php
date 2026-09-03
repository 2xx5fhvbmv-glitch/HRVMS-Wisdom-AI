@extends('resorts.layouts.app')
@section('page_tab_title', 'View Budget')

@if ($message = Session::get('success'))
<div class="alert alert-success">
    <p>{{ $message }}</p>
</div>
@endif

@section('content')

<style>
    /* Same requested push as the other module dashboards/pages — extra
       breathing room between the hero and the content below it, scoped to
       this page (.page-hedding's own margin-bottom is shared by every
       page's hero). padding-bottom, not margin: adjacent sibling margins
       collapse to the larger of the two rather than summing. Below
       Bootstrap's sm breakpoint the extra padding pushes content into the
       teal hero curve's rounded bottom-left corner (body::before,
       border-radius 0 0 50px 50px) — same collision found on Payroll —
       neutralized below 576px. */
    #view-budget-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #view-budget-hero { padding-bottom: 0; }
    }

    /* Enhanced Budget Table Styling */
    .budget-monthly-table tbody tr:hover {
        background-color: #f8f9fa !important;
        transform: translateX(2px);
    }

    /* No overflow/border-radius here — the table's own overflow:hidden
       previously broke position:sticky on every th/td (an ancestor with
       overflow != visible becomes the sticky containing block, and the
       table itself never scrolls, so cells never actually stuck).
       Rounded corners are already provided by .wb-table-scroll below. */
    .budget-monthly-table {
        box-shadow: 0 2px 4px rgba(0,0,0,0.08);
    }

    .budget-monthly-table th {
        font-size: 0.813rem;
        padding: 12px 8px;
        vertical-align: middle;
        white-space: nowrap;
    }

    .budget-monthly-table td {
        padding: 10px 8px;
        vertical-align: middle;
    }

    .btn-edit-month-budget {
        transition: all 0.2s ease;
    }

    .btn-edit-month-budget:hover {
        transform: scale(1.1);
    }

    /* Month column styling */
    .budget-monthly-table tbody tr td:first-child {
        background-color: #f8f9fa;
        font-weight: 500;
    }

    /* Total row emphasis */
    .budget-monthly-table tbody tr:last-child {
        font-size: 0.875rem;
        box-shadow: 0 -2px 4px rgba(0,0,0,0.05);
    }

    /* ==================================================================
       Drill-Down Redesign — brand palette + nav/detail-card/table restyle.
       View-layer only: every existing accordion element/id/class/
       data-attribute, AJAX endpoint, and calculation stays untouched —
       these rules only repaint what's already there, plus style the new
       nav-card/detail-card/toggle markup added alongside it. No DOM
       nodes are moved: "focus mode" (division→department→section→
       position→employee/vacant showing one path at a time) is achieved
       by hiding everything off the current path and collapsing on-path
       wrapper boxes with `display: contents`, which removes their own
       box (margin/padding/indentation) while leaving their children — and
       every .closest()/.find() relationship the total/badge functions
       rely on — completely intact. See wobbly-munching-lake.md for why.
       ================================================================== */
    /* Neutral/geometry tokens (--teal/--teal-2/--teal-3/--teal-soft/--lime/
       --ink/--muted/--faint/--line/--line-2/--card) now come from the
       shared :root palette (resorts/layouts/_design_tokens.blade.php) —
       this block previously redefined them on :root itself (not scoped
       to a wrapper class), which would have silently overridden the
       shared palette page-wide; removed rather than renamed. --wb-vacant/
       --wb-increase are exact-hex matches for the shared --warning/
       --positive, now pointing there too. --wb-bg/--wb-vacant-bg/
       --wb-increase-bg have no shared equivalent (their -bg values don't
       match --warning-bg/--positive-bg) and stay local. */
    :root {
        --wb-bg: #F2F6F6;
        --wb-vacant: var(--warning);
        --wb-vacant-bg: #FFF6E5;
        --wb-increase: var(--positive);
        --wb-increase-bg: #EAF7F0;
    }

    /* ---- Nav card (Phase 1) ---- */
    .wb-nav-card {
        background: var(--card);
        border-radius: 14px;
        box-shadow: 0 1px 3px rgba(1,70,83,0.08);
        padding: 16px 18px;
        margin-bottom: 16px;
    }
    .wb-nav-top {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 12px;
    }
    .wb-detail-header-card {
        background: var(--card);
        border-radius: 14px;
        box-shadow: 0 1px 3px rgba(1,70,83,0.08);
        padding: 14px 18px;
        margin-bottom: 16px;
    }
    .wb-back-btn {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: 1px solid var(--line);
        background: #fff;
        color: var(--muted);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        flex-shrink: 0;
        transition: background .12s, color .12s;
    }
    .wb-back-btn:hover:not(:disabled) { background: var(--teal-soft); color: var(--ink); }
    .wb-back-btn:disabled { opacity: .35; cursor: not-allowed; }
    .wb-revise-budget-btn {
        flex-shrink: 0;
        border: none;
        border-radius: 8px;
        padding: 7px 14px;
        font-size: 12px;
        font-weight: 700;
        color: #fff;
        background: var(--teal);
        cursor: pointer;
    }
    .wb-revise-budget-btn:hover { background: var(--teal-2); }
    .wb-revise-budget-btn:disabled { background: var(--muted); opacity: .5; cursor: not-allowed; }
    .wb-breadcrumb {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
        font-size: 12.5px;
        font-weight: 600;
        color: var(--muted);
        flex: 1;
        min-width: 160px;
    }
    .wb-breadcrumb button {
        border: none;
        background: none;
        padding: 2px 4px;
        color: var(--muted);
        font-weight: 600;
        font-size: 12.5px;
        cursor: pointer;
        border-radius: 6px;
    }
    .wb-breadcrumb button:hover { background: var(--teal-3); color: var(--teal); }
    .wb-breadcrumb button.wb-crumb-current { color: var(--teal); cursor: default; }
    .wb-breadcrumb button.wb-crumb-current:hover { background: none; }
    .wb-breadcrumb .wb-crumb-sep { color: var(--faint); }
    /* Search input itself uses the shared .input-group / .form-control.search
       / .card-header classes (same as e.g. Talent Pool) — no page-specific
       input styling here. Only the results dropdown below is this page's
       own UI. */
    .wb-search-results {
        position: absolute;
        top: calc(100% + 4px);
        left: 0; right: 0;
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 10px;
        box-shadow: 0 8px 24px rgba(1,70,83,0.14);
        max-height: 320px;
        overflow-y: auto;
        z-index: 20;
    }
    .wb-search-result-row {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        cursor: pointer;
        border-bottom: 1px solid var(--line-2);
        font-size: 12.5px;
    }
    .wb-search-result-row:last-child { border-bottom: none; }
    .wb-search-result-row:hover { background: var(--teal-soft); }
    .wb-search-result-type {
        font-size: 9.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: var(--faint);
        flex-shrink: 0;
        width: 46px;
    }
    .wb-search-result-name { color: var(--ink); font-weight: 600; }
    .wb-search-result-sub { color: var(--faint); font-size: 11px; }

    /* ---- Drill-down list rows (Phase 1) ---- */
    .wb-level-list { display: flex; flex-direction: column; gap: 8px; }
    .wb-group-row {
        display: flex;
        align-items: center;
        gap: 12px;
        border: 1px solid var(--line);
        border-radius: 10px;
        padding: 10px 14px;
        cursor: pointer;
        transition: background .12s, border-color .12s;
    }
    .wb-group-row:hover { background: var(--teal-soft); border-color: var(--teal-3); }
    .wb-group-row-main { flex: 1; min-width: 0; }
    .wb-group-row-name { font-size: 13.5px; font-weight: 600; color: var(--ink); }
    .wb-level-tag {
        display: inline-block;
        font-size: 9.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: var(--teal);
        background: var(--teal-3);
        border-radius: 6px;
        padding: 2px 7px;
        margin-right: 8px;
        vertical-align: middle;
    }
    .wb-group-row-meta { font-size: 11px; color: var(--muted); margin-top: 3px; }
    .wb-group-row-budget { font-size: 13px; font-weight: 700; color: var(--teal); flex-shrink: 0; }
    .wb-group-row-chevron { color: var(--faint); flex-shrink: 0; }

    /* ---- Leaf rows: employee / vacant (Phase 1) ---- */
    .wb-leaf-row { display: flex; align-items: center; gap: 12px; }
    .wb-leaf-avatar {
        width: 38px; height: 38px; border-radius: 50%; flex-shrink: 0;
        object-fit: cover; background: var(--teal); color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: 13px; font-weight: 700;
    }
    .wb-leaf-avatar-vacant {
        border: 2px dashed var(--wb-vacant);
        background: var(--wb-vacant-bg);
        color: var(--wb-vacant);
    }
    .wb-vacant-pill {
        display: inline-block;
        font-size: 9.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: var(--wb-vacant);
        background: var(--wb-vacant-bg);
        border: 1px solid #ffe2ad;
        border-radius: 10px;
        padding: 2px 8px;
    }

    /* ---- Detail card (Phase 2) ---- */
    .wb-detail-card-inner { background: var(--card); }
    .wb-annual-summary {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin: 4px 0 14px;
    }
    .wb-annual-stat {
        flex: 1;
        min-width: 130px;
        background: var(--teal-soft);
        border-radius: 10px;
        padding: 10px 12px;
    }
    .wb-annual-stat-label {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: var(--faint);
        display: block;
        margin-bottom: 3px;
    }
    .wb-annual-stat-value { font-size: 15px; font-weight: 800; color: var(--teal); }
    .wb-annual-stat-delta.wb-delta-up { color: var(--wb-increase); }
    .wb-annual-stat-delta.wb-delta-down { color: var(--error); }

    /* ---- Group toggle chips (Phase 5) ---- */
    .wb-group-toggles { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; }
    .wb-group-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: 1px solid var(--line);
        border-radius: 20px;
        padding: 5px 12px;
        font-size: 11.5px;
        font-weight: 600;
        color: var(--muted);
        background: #fff;
        cursor: pointer;
        user-select: none;
    }
    .wb-group-chip .wb-group-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .wb-group-chip.wb-chip-active {
        background: var(--teal-3);
        border-color: var(--teal-3);
        color: var(--teal);
    }

    /* ---- Table framing / sticky (Phase 3) ---- */
    .wb-table-scroll { max-height: 65vh; overflow: auto; border-radius: 10px; border: 1px solid var(--line-2); }
    /* Fixed layout so every column's rendered width exactly matches its
       declared inline width — required for the .wb-col-* left offsets
       below (0/80/210/340/440) to line up the 5 sticky columns edge to
       edge. Auto layout would size columns from content across all rows,
       which the sticky offsets can't know in advance. */
    .budget-monthly-table { table-layout: fixed; }
    /* Flat, light header — a finance/spreadsheet-style table reads numbers
       for a living, so the header's job is to label columns clearly
       without competing for attention; a saturated gradient reads more
       "marketing card" than "financial report". Long cost-template names
       (e.g. "Language Allowance", "R And R Allowance") also need to wrap
       to a second line instead of overflowing into the next column —
       white-space:nowrap was inherited from a global table style. */
    .budget-monthly-table thead th {
        position: sticky;
        top: 0;
        z-index: 3;
        background: var(--teal-3) !important;
        color: var(--teal) !important;
        border-bottom: 2px solid var(--teal) !important;
        white-space: normal !important;
        overflow-wrap: break-word;
        font-size: 12px !important;
        line-height: 1.3 !important;
        padding: 8px 10px !important;
        vertical-align: middle;
    }
    .budget-monthly-table .wb-sticky-col {
        position: sticky;
        background: #fff;
        z-index: 2;
    }
    .budget-monthly-table thead th.wb-sticky-col { z-index: 4; }
    .budget-monthly-table tbody tr:hover .wb-sticky-col { background: #f8f9fa !important; }
    .budget-monthly-table tr.table-total-row .wb-sticky-col { background: #f8f9fa !important; }
    .wb-col-month { left: 0; }
    .wb-col-current { left: 80px; }
    .wb-col-proposed { left: 210px; }
    .wb-col-action { left: 340px; box-shadow: 2px 0 4px rgba(0,0,0,0.05); }
    .wb-zero-dash { color: var(--faint); font-weight: 400; }

    /* Column show/hide by cost group (Phase 5) — driven purely by a
       data-hidden-groups token list on the scroll wrapper; never touches
       data-cost-id or the totals-recalculation logic. */
    .wb-table-scroll[data-hidden-groups~="allowances"] [data-cost-group="allowances"],
    .wb-table-scroll[data-hidden-groups~="overtime"] [data-cost-group="overtime"],
    .wb-table-scroll[data-hidden-groups~="travel"] [data-cost-group="travel"],
    .wb-table-scroll[data-hidden-groups~="insurance"] [data-cost-group="insurance"],
    .wb-table-scroll[data-hidden-groups~="other"] [data-cost-group="other"] {
        display: none;
    }
    @keyframes wbChipShake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-3px); }
        75% { transform: translateX(3px); }
    }
    .wb-group-chip.wb-chip-shake { animation: wbChipShake 0.3s; }

    /* Icon-button restyle for edit-pencil / copy-down — visual only,
       .btn-edit-month-budget / .btn-copy-down and every data-* attribute
       stay exactly as the existing delegated handlers expect. */
    .budget-monthly-table .btn-edit-month-budget,
    .budget-monthly-table .btn-copy-down {
        width: 26px;
        height: 26px;
        padding: 0 !important;
        border-radius: 7px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--line);
        background: #fff;
        color: var(--muted);
    }
    .budget-monthly-table .btn-edit-month-budget:hover { background: var(--teal-3); color: var(--teal); border-color: var(--teal-3); transform: none; }
    .budget-monthly-table .btn-copy-down:hover { background: var(--teal-3); color: var(--teal); border-color: var(--teal-3); }

    /* ---- Revise Budget button (Phase 6, visual only) ---- */
    .revisebudgetmodal, a.revisebudgetmodal {
        background: var(--teal) !important;
        border-color: var(--teal) !important;
        color: #fff !important;
    }

    /* ---- Focus-mode flattening (core drill-down mechanism) ----
       Unconditional — NOT gated by a .wb-focus-active ancestor class.
       Bootstrap's own collapse state (.collapse.show) persists in the DOM
       independently of wbPath (e.g. after drilling deep then coming back
       to the root, those inner collapses are still "shown"). Gating this
       on .wb-focus-active meant that once wbPath emptied out and that
       class was removed, every rule below stopped applying and whatever
       had previously been expanded reappeared in full, unflattened — the
       exact "old accordion still showing underneath" bug. Keeping these
       rules always-on means the original accordion chrome never becomes
       visible again regardless of prior navigation. */
    #accordionViewBudget .division-accordion:not(.wb-on-path),
    #accordionViewBudget .department-accordion:not(.wb-on-path),
    #accordionViewBudget .section-accordion:not(.wb-on-path),
    #accordionViewBudget .position-accordion:not(.wb-on-path),
    #accordionViewBudget .employee-accordion:not(.wb-on-path),
    #accordionViewBudget .vacant-accordion:not(.wb-on-path) {
        display: none !important;
    }
    #accordionViewBudget .wb-on-path.wb-flatten,
    /* Division: .division-accordion IS the .accordion-item (same element) —
       its collapse/body are direct children, not nested under a child
       .accordion-item like every other level. */
    #accordionViewBudget .wb-on-path.wb-flatten.division-accordion > .collapse,
    #accordionViewBudget .wb-on-path.wb-flatten.division-accordion > .collapse.show,
    #accordionViewBudget .wb-on-path.wb-flatten.division-accordion > .collapse > .accordion-body,
    /* Department/Section/Position/Employee/Vacant: wrap a child .accordion-item. */
    #accordionViewBudget .wb-on-path.wb-flatten > .accordion-item,
    #accordionViewBudget .wb-on-path.wb-flatten > .accordion-item > .collapse,
    #accordionViewBudget .wb-on-path.wb-flatten > .accordion-item > .collapse.show,
    #accordionViewBudget .wb-on-path.wb-flatten > .accordion-item > .collapse > .accordion-body,
    #accordionViewBudget .wb-on-path.wb-flatten > .accordion-item > .row,
    #accordionViewBudget .wb-on-path.wb-flatten > .accordion-item > .row > div {
        display: contents !important;
    }
    #accordionViewBudget .wb-on-path.wb-flatten.division-accordion > h2.accordion-header,
    #accordionViewBudget .wb-on-path.wb-flatten > .accordion-item > h2.accordion-header,
    #accordionViewBudget .wb-on-path.wb-flatten > .accordion-item > .row h2.accordion-header,
    #accordionViewBudget .wb-on-path.wb-flatten > .accordion-item > .row .col-md-3 {
        display: none !important;
    }
    #accordionViewBudget { padding: 0; }
</style>

<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="view-budget-hero">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>WORKFORCE PLANNING</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="row g-md-3 g-2 align-items-center justify-content-between">
                    <div class="col-xl-2 col-md-4 col-sm-4 col-6">
                        <form method="GET" action="{{ route('resort.budget.viewbudget') }}" id="yearFilterForm">
                            <select class="form-select" name="year" id="yearFilter" onchange="document.getElementById('yearFilterForm').submit();">
                                @php
                                    $currentYear = date('Y');
                                    $startYear = $currentYear - 10;
                                    $endYear = $currentYear + 1;
                                    $selectedYear = request()->get('year', $currentYear);
                                @endphp
                                @for ($loopyear = $startYear; $loopyear <= $endYear; $loopyear++)
                                    <option value="{{ $loopyear }}" @if ($loopyear == $selectedYear) selected @endif>{{ $loopyear }}</option>
                                @endfor
                            </select>
                        </form>
                    </div>
                    <div class="col-xl-4 col-md-5 col-sm-6 col-12">
                        {{-- Same input-group / form-control.search / .card-header
                             pattern used site-wide (e.g. Talent Pool's search bar)
                             — reusing the shared CSS rather than a page-specific
                             look, so this page's search box isn't visually its
                             own thing. --}}
                        <div class="input-group" id="wbSearchGroup">
                            <input type="search" class="form-control search" id="wbSearchInput" placeholder="Search employee or position" autocomplete="off">
                            <i class="fa-solid fa-search"></i>
                            <div class="wb-search-results d-none" id="wbSearchResults"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Drill-down nav card — purely additive presentation layer.
                 Reads from window.budgetTotals (already populated by the
                 existing eager loadAllBudgetTotalsOnPageLoad() chain) and
                 the rendered accordion DOM; drilling into a row calls
                 bootstrap.Collapse.show() on the matching EXISTING collapse
                 element so the real shown.bs.collapse listeners fire and
                 load data exactly as they already do today. Nothing here
                 fetches data on its own. --}}
            <div class="wb-nav-card" id="wbNavCard">
                <div class="wb-nav-top">
                    <button type="button" class="wb-back-btn" id="wbBackBtn" title="Back" disabled>
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
                    </button>
                    <div class="wb-breadcrumb" id="wbBreadcrumb">
                        <button type="button" class="wb-crumb-current" data-wb-level="root">All Divisions</button>
                    </div>
                    {{-- Only ever shown/wired when the drilled-into department
                         actually has a live trigger in the (now-hidden)
                         original accordion — see wbUpdateReviseBudgetButton().
                         Clicking it just clicks that original trigger, so
                         the existing modal/save logic is entirely reused. --}}
                    <button type="button" class="wb-revise-budget-btn d-none" id="wbReviseBudgetBtn">Revise Budget</button>
                </div>
                <div class="wb-level-list" id="wbLevelList"></div>
            </div>

            {{-- Detail-card title bar for the selected employee/vacant —
                 built from the exact same avatar/name/meta markup as the
                 nav card's leaf rows (see wbLeafRowInnerHtml()), so this
                 never looks different from what the user just clicked.
                 Replaces the original accordion header's generic person
                 icon + default Bootstrap badges, which is now fully
                 hidden like every other level (see wbApplyFocus()). --}}
            <div class="wb-detail-header-card d-none" id="wbDetailHeader"></div>

            <div class="viewBudget-accordion" id="accordionViewBudget">
                @if($divisions->isNotEmpty())
                    @php $divisionIteration = 1; @endphp
                    @foreach($divisions as $division)
                        {{-- Level 1: Division --}}
                        <div class="accordion-item mb-2 division-accordion">
                            <h2 class="accordion-header" id="headingDiv{{ $divisionIteration }}">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapseDiv{{ $divisionIteration }}" aria-expanded="false"
                                        aria-controls="collapseDiv{{ $divisionIteration }}">
                                    <i class="fas fa-building me-2"></i>
                                    <h3>{{ $division->name }}</h3>
                                    <span class="badge badge-dark ms-2 small divisionGrandTotal">Budget: {{ Common::GetResortCurrencySymbol() }} 0.00</span>
                                </button>
                            </h2>
                            <div id="collapseDiv{{ $divisionIteration }}" class="collapse"
                                 aria-labelledby="headingDiv{{ $divisionIteration }}" data-bs-parent="#accordionViewBudget">
                                <div class="accordion-body p-2">
                                    @php $deptIteration = 1; @endphp
                                    @foreach($division->departments as $department)
                                        @php
                                            $manningResponse = $manningResponses->where('dept_id', $department->id)->first();
                                        @endphp
                                        @if($manningResponse)
                                        {{-- Level 2: Department --}}
                                        <div class="accordion mb-2 ms-3 department-accordion" id="accordionDept{{ $divisionIteration }}_{{ $deptIteration }}">
                                            <div class="accordion-item">
                                                <div class="row g-0 align-items-center">
                                                    <div class="col-md-9">
                                                        <h2 class="accordion-header" id="headingDept{{ $divisionIteration }}_{{ $deptIteration }}">
                                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                                    data-bs-target="#collapseDept{{ $divisionIteration }}_{{ $deptIteration }}"
                                                                    aria-expanded="false" aria-controls="collapseDept{{ $divisionIteration }}_{{ $deptIteration }}">
                                                                <i class="fas fa-sitemap me-2"></i>
                                                                <span>{{ $department->name }}</span>
                                                                <span class="badge badge-dark ms-2 small departmentGrandTotal">Budget: {{ Common::GetResortCurrencySymbol() }} 0.00</span>
                                                            </button>
                                                        </h2>
                                                    </div>
                                                    <div class="col-md-3 text-end pe-2">
                                                        @php
                                                            $isBudgetLocked = isset($approvedBudgetIdsLookup) && isset($approvedBudgetIdsLookup[$manningResponse->id]);
                                                        @endphp
                                                        @if($available_rank == 'HR')
                                                            @if($isBudgetLocked)
                                                                {{-- GM has approved this budget — Revise is locked. --}}
                                                                <button type="button"
                                                                    class="btn btn-xs wfp-btn-secondary ms-2"
                                                                    disabled
                                                                    title="GM has approved this budget — revisions are locked.">
                                                                    Revise Budget
                                                                </button>
                                                            @else
                                                                <a href="#revise-budgetmodal"
                                                                   data-dept_id="{{ $department->id }}"
                                                                   data-Budget_id="{{ $manningResponse->id }}"
                                                                   class="btn btn-xs wfp-btn-primary ms-2 revisebudgetmodal"
                                                                   data-bs-toggle="modal">
                                                                    Revise Budget
                                                                </a>
                                                            @endif
                                                        @endif
                                                    </div>
                                                </div>

                                                <div id="collapseDept{{ $divisionIteration }}_{{ $deptIteration }}"
                                                     class="collapse"
                                                     aria-labelledby="headingDept{{ $divisionIteration }}_{{ $deptIteration }}"
                                                     data-bs-parent="#accordionDept{{ $divisionIteration }}_{{ $deptIteration }}">
                                                    <div class="accordion-body p-2"
                                                         data-department-id="{{ $department->id }}"
                                                         data-division-iteration="{{ $divisionIteration }}"
                                                         data-dept-iteration="{{ $deptIteration }}"
                                                         data-year="{{ $year }}">
                                                        <!-- Content will be loaded via AJAX when expanded -->
                                                        <div class="text-center py-3">
                                                            <div class="spinner-border spinner-border-sm" role="status">
                                                                <span class="visually-hidden">Loading...</span>
                                                            </div>
                                                            <span class="ms-2">Loading...</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @php $deptIteration++; @endphp
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @php $divisionIteration++; @endphp
                    @endforeach
                @else
                    <div class="alert alert-info">
                        <p>No divisions found for this resort.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Revise Budget Modal --}}
<div class="modal fade" id="revise-budgetmodal" tabindex="-1" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Revise Budget</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="ReviseBudget">
                @csrf
                <div class="modal-body">
                    <div class="form-group mb-20">
                        <input type="hidden" class="Revise_Budget_id" name="budget_id" >
                        <input type="hidden" class="Revise_Department_id" name="department_id" >
                        @php
                            $manning_request =  config('settings.manning_request');
                            $manning_request = array_key_exists('msg3', $manning_request) ? $manning_request['msg3'] : '' ;
                        @endphp
                        <textarea class="form-control" name="ReviseBudgetComment" rows="7" placeholder="Add Comment Regarding Revision">{{ $manning_request }}</textarea>
                    </div>
                </div>
                <div class="modal-footer justify-content-end">
                    <button type="button" class="btn btn-sm wfp-btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm wfp-btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('import-css')
@include('resorts.workforce_planning._wfp_buttons_v2_styles')
@endsection

@section('import-scripts')
<script>
    // Store MVR to Dollar rate globally
    @php
        $resortSettings = \App\Models\ResortSiteSettings::where('resort_id', $resortId)->first();
        // DollertoMVR field stores: 1 USD = X MVR (e.g., 15.42 means 1 USD = 15.42 MVR)
        $mvrToDollarRate = $resortSettings->MVRtoDoller ?? 0.065;
    @endphp
    window.mvrToDollarRate = {{ $mvrToDollarRate }};
    window.wbDefaultPictureUrl = "{{ url(config('settings.default_picture')) }}";

// Format a budget number with thousands separator + 2 decimal places.
// Used for every Budget badge so 18720.00 → "18,720.00" (matches the
// payroll module's formatAmount() helper). Tolerates strings/null.
window.formatBudget = function (n) {
    var num = parseFloat(n);
    if (!isFinite(num)) num = 0;
    return num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

$(document).ready(function() {
    const resortId = {{ $resortId }};
    const year = {{ $year }};
    const csrfToken = '{{ csrf_token() }}';

    // Track loaded departments
    const loadedDepartments = {};

    // Function to get current year from page
    function getCurrentYear() {
        // Try to get year from year filter select
        const yearSelect = $('#yearFilter');
        if (yearSelect.length && yearSelect.val()) {
            return parseInt(yearSelect.val());
        }
        // Fallback to current year
        return new Date().getFullYear();
    }

    // Function to get days in a month for a given year and month
    function getDaysInMonth(year, month) {
        // month is 1-12, but Date constructor expects 0-11 for month
        // So we use month (1-12) and day 0 to get the last day of the previous month
        // which gives us the last day of the current month
        return new Date(year, month, 0).getDate();
    }

    // Store budget totals for badge calculations
    window.budgetTotals = {
        positions: {},
        sections: {},
        departments: {},
        divisions: {}
    };

    // Load all budget data automatically on page load for badge calculations
    // Single batched call replacing the old 3-level waterfall (one AJAX
    // request per department, then per position, then per employee/vacant
    // — hundreds of requests for a real resort, ~4+ seconds even on a tiny
    // test dataset). BudgetController::getAllBudgetTotals() computes every
    // position's total server-side in a fixed number of queries using the
    // same canonical Common::annualBudgetForEmployee() /
    // annualBudgetForVacantSlot() arithmetic, and returns exactly the
    // shape window.budgetTotals.positions already needs — no other client
    // code changes required.
    function loadAllBudgetTotalsOnPageLoad() {
        console.log('Loading all budget totals on page load...');

        $.ajax({
            url: "{{ route('resort.budget.hierarchy.all-totals') }}",
            method: 'GET',
            data: {
                year: year,
                _token: csrfToken
            },
            success: function(response) {
                if (response.success) {
                    window.budgetTotals.positions = response.positions;
                    updateAllBadgesFromTotals();
                } else {
                    console.error('Error loading budget totals:', response.message);
                }
            },
            error: function(xhr) {
                console.error('Error loading budget totals:', xhr);
            }
        });
    }

    // Update all badges from calculated totals
    function updateAllBadgesFromTotals() {
        console.log('Updating all badges from calculated totals...');

        // Clear existing section and department totals
        window.budgetTotals.sections = {};
        window.budgetTotals.departments = {};

        // Step 1: Calculate section totals first (from positions in sections)
        Object.keys(window.budgetTotals.positions).forEach(positionId => {
            const positionData = window.budgetTotals.positions[positionId];
            const total = positionData.total || 0;
            const sectionId = positionData.sectionId;

            // Update position badges if in DOM
            $(`.position-accordion[data-position-id="${positionId}"]`).find('.positionGrandTotal').text('Budget: ' + formatAmount(total, 'USD'));
            $(`.accordion-body[data-position-id="${positionId}"]`).closest('.position-accordion').find('.positionGrandTotal').text('Budget: ' + formatAmount(total, 'USD'));

            // Add to section total if position is in a section
            if (sectionId) {
                if (!window.budgetTotals.sections[sectionId]) {
                    window.budgetTotals.sections[sectionId] = 0;
                }
                window.budgetTotals.sections[sectionId] += total;
            }
        });

        // Step 2: Calculate department totals (from sections + direct positions)
        const departmentSectionMap = {}; // Track which sections belong to which departments
        const departmentDirectPositions = {}; // Track direct positions (not in sections) by department

        Object.keys(window.budgetTotals.positions).forEach(positionId => {
            const positionData = window.budgetTotals.positions[positionId];
            const total = positionData.total || 0;
            const deptId = positionData.departmentId;
            const sectionId = positionData.sectionId;

            if (!deptId) return;

            if (sectionId) {
                // Position is in a section
                if (!departmentSectionMap[deptId]) {
                    departmentSectionMap[deptId] = [];
                }
                if (departmentSectionMap[deptId].indexOf(sectionId) === -1) {
                    departmentSectionMap[deptId].push(sectionId);
                }
            } else {
                // Direct position (not in section)
                if (!departmentDirectPositions[deptId]) {
                    departmentDirectPositions[deptId] = 0;
                }
                departmentDirectPositions[deptId] += total;
            }
        });

        // Calculate department totals
        Object.keys(departmentSectionMap).forEach(deptId => {
            let deptTotal = 0;

            // Add section totals
            departmentSectionMap[deptId].forEach(sectionId => {
                if (window.budgetTotals.sections[sectionId]) {
                    deptTotal += window.budgetTotals.sections[sectionId];
                }
            });

            // Add direct positions
            if (departmentDirectPositions[deptId]) {
                deptTotal += departmentDirectPositions[deptId];
            }

            window.budgetTotals.departments[deptId] = deptTotal;
        });

        // Add departments that only have direct positions (no sections)
        Object.keys(departmentDirectPositions).forEach(deptId => {
            if (!window.budgetTotals.departments[deptId]) {
                window.budgetTotals.departments[deptId] = departmentDirectPositions[deptId];
            }
        });

        // Step 3: Update section badges from stored totals (if sections are in DOM)
        Object.keys(window.budgetTotals.sections).forEach(sectionId => {
            const sectionTotal = window.budgetTotals.sections[sectionId];
            // Note: Section badges will be updated when sections are rendered via updateSectionDepartmentDivisionBadges
            // But we can also try to find them by ID pattern
            $(`.section-accordion`).each(function() {
                // Check if this section contains positions that match our sectionId
                // Since we don't have direct section ID mapping, we'll rely on DOM-based calculation
            });
        });

        // Step 4: Update department badges from stored totals
        Object.keys(window.budgetTotals.departments).forEach(deptId => {
            const deptTotal = window.budgetTotals.departments[deptId];
            $(`[data-department-id="${deptId}"]`).closest('.department-accordion').find('.departmentGrandTotal').text('Budget: ' + formatAmount(deptTotal, 'USD'));
        });

        // Step 5: Calculate and update division totals
        $('.division-accordion').each(function() {
            const $division = $(this);
            let divisionTotal = 0;

            $division.find('[data-department-id]').each(function() {
                const deptId = $(this).data('department-id');
                if (deptId && window.budgetTotals.departments[deptId]) {
                    divisionTotal += window.budgetTotals.departments[deptId];
                }
            });

            $division.find('.divisionGrandTotal').text('Budget: ' + formatAmount(divisionTotal, 'USD'));
        });

        // Update section, department, and division badges by calculating from DOM (for when they're rendered)
        updateSectionDepartmentDivisionBadges();

        console.log('All badges updated.');
    }

    // Update department + division badges using stored positions data
    // (window.budgetTotals.positions[*].departmentId / divisionIndex).
    // Does NOT walk the DOM, so it works for unexpanded departments where
    // .section-accordion / .position-accordion don't yet exist.
    function updateDepartmentDivisionBadgesFromStored() {
        const deptTotals = {};
        Object.keys(window.budgetTotals.positions).forEach(positionId => {
            const p = window.budgetTotals.positions[positionId];
            if (!p || !p.departmentId) return;
            const t = parseFloat(p.total || 0);
            deptTotals[p.departmentId] = (deptTotals[p.departmentId] || 0) + t;
        });

        // Update each department badge
        Object.keys(deptTotals).forEach(deptId => {
            const total = deptTotals[deptId];
            window.budgetTotals.departments[deptId] = total;
            $(`.accordion-body[data-department-id="${deptId}"]`)
                .closest('.department-accordion')
                .find('.departmentGrandTotal')
                .first()
                .text('Budget: ' + formatAmount(total, 'USD'));
        });

        // Update each division badge by summing its child departments
        $('.division-accordion').each(function() {
            const $division = $(this);
            let divisionTotal = 0;
            $division.find('[data-department-id]').each(function() {
                const deptId = $(this).data('department-id');
                if (deptId && deptTotals[deptId]) {
                    divisionTotal += deptTotals[deptId];
                }
            });
            $division.find('.divisionGrandTotal').first()
                .text('Budget: ' + formatAmount(divisionTotal, 'USD'));
        });
    }

    // Update section, department, and division badges from position totals
    function updateSectionDepartmentDivisionBadges() {
        // Calculate section totals
        $('.section-accordion').each(function() {
            const $section = $(this);
            let sectionTotal = 0;

            $section.find('.position-accordion').each(function() {
                const positionId = $(this).data('position-id') || $(this).find('[data-position-id]').first().data('position-id');
                if (positionId && window.budgetTotals.positions[positionId]) {
                    sectionTotal += (window.budgetTotals.positions[positionId].total || 0);
                }
            });

            $section.find('.sectionGrandTotal').text('Budget: ' + formatAmount(sectionTotal, 'USD'));
            window.budgetTotals.sections[$section.attr('id')] = sectionTotal;
        });

        // Calculate department totals
        $('.department-accordion').each(function() {
            const $dept = $(this);
            let deptTotal = 0;

            // Sum sections
            $dept.find('.section-accordion').each(function() {
                const sectionId = $(this).attr('id');
                if (window.budgetTotals.sections[sectionId]) {
                    deptTotal += window.budgetTotals.sections[sectionId];
                }
            });

            // Sum direct positions (not in sections)
            $dept.find('.position-accordion').each(function() {
                if ($(this).closest('.section-accordion').length === 0) {
                    const positionId = $(this).data('position-id') || $(this).find('[data-position-id]').first().data('position-id');
                    if (positionId && window.budgetTotals.positions[positionId]) {
                        deptTotal += (window.budgetTotals.positions[positionId].total || 0);
                    }
                }
            });

            // Fallback: if DOM walk yielded 0 (department not yet expanded
            // → no .section-accordion / .position-accordion in DOM), use the
            // stored department total computed from positions data so we
            // don't overwrite a previously-correct badge with $0.00.
            const deptId = $dept.find('[data-department-id]').first().data('department-id')
                || $dept.closest('[data-department-id]').data('department-id');
            if (deptTotal === 0 && deptId && window.budgetTotals.departments[deptId]) {
                deptTotal = window.budgetTotals.departments[deptId];
            }

            $dept.find('.departmentGrandTotal').first().text('Budget: ' + formatAmount(deptTotal, 'USD'));

            if (deptId) {
                window.budgetTotals.departments[deptId] = deptTotal;
            }
        });

        // Calculate division totals
        $('.division-accordion').each(function() {
            const $division = $(this);
            let divisionTotal = 0;

            $division.find('.department-accordion').each(function() {
                const $dept = $(this);
                const deptId = $dept.find('[data-department-id]').first().data('department-id')
                    || $dept.closest('[data-department-id]').data('department-id');
                if (deptId && window.budgetTotals.departments[deptId]) {
                    divisionTotal += window.budgetTotals.departments[deptId];
                } else {
                    // Fallback: calculate directly from positions
                    let deptTotal = 0;
                    $dept.find('.position-accordion').each(function() {
                        const positionId = $(this).data('position-id') || $(this).find('[data-position-id]').first().data('position-id');
                        if (positionId && window.budgetTotals.positions[positionId]) {
                            deptTotal += (window.budgetTotals.positions[positionId].total || 0);
                        }
                    });
                    divisionTotal += deptTotal;
                }
            });

            $division.find('.divisionGrandTotal').first().text('Budget: ' + formatAmount(divisionTotal, 'USD'));
        });
    }

    // Global function to recalculate all totals from rendered tables (similar to consolidated page)
    window.recalculateAllTotals = function() {
        console.log('Recalculating all totals from rendered tables...');

        // Recalculate position totals using the same function as updateBadgesHierarchy.
        // NOTE: calculatePositionTotal/Section/Department/Division read from
        // RENDERED cell text (already converted to the display currency),
        // so we pass `displayCurrency` as sourceCurrency to formatAmount —
        // convertAmount then returns the value as-is (no double conversion).
        $('.position-accordion').each(function() {
            const $position = $(this);
            const positionId = $position.data('position-id');
            if (positionId) {
                const positionTotal = calculatePositionTotal($position);
                if (positionTotal > 0 || positionTotal === 0) {
                    $position.find('.positionGrandTotal').text('Budget: ' + formatAmount(positionTotal, displayCurrency));
                }
            }
        });

        // Recalculate section totals
        $('.section-accordion').each(function() {
            const sectionTotal = calculateSectionTotal($(this));
            $(this).find('.sectionGrandTotal').text('Budget: ' + formatAmount(sectionTotal, displayCurrency));
        });

        // Recalculate department totals
        $('.department-accordion').each(function() {
            const deptTotal = calculateDepartmentTotal($(this));
            $(this).find('.departmentGrandTotal').text('Budget: ' + formatAmount(deptTotal, displayCurrency));
        });

        // Recalculate division totals
        $('.division-accordion').each(function() {
            const divisionTotal = calculateDivisionTotal($(this));
            $(this).find('.divisionGrandTotal').text('Budget: ' + formatAmount(divisionTotal, displayCurrency));
        });

        console.log('All totals recalculated.');
    };

    // Calculate position total from rendered tables
    function calculatePositionTotalFromTable($positionElement) {
        let total = 0;

        // Find all budget monthly tables within this position
        $positionElement.find('.budget-monthly-table').each(function() {
            const $table = $(this);
            const $totalRow = $table.find('.table-total-row');

            if ($totalRow.length) {
                // Get current salary total
                const currentSalaryText = $totalRow.find('.total-current-salary').text();
                const currentSalary = parseFloat(currentSalaryText.replace(currencySymbol, '').replace(/,/g, '').trim() || 0);

                // Get proposed salary total
                const proposedSalaryText = $totalRow.find('.total-proposed-salary').text();
                const proposedSalary = parseFloat(proposedSalaryText.replace(currencySymbol, '').replace(/,/g, '').trim() || 0);

                // Sum all cost configuration totals
                let costTotal = 0;
                $totalRow.find('td[data-cost-id]').each(function() {
                    const costText = $(this).text();
                    const costValue = parseFloat(costText.replace(currencySymbol, '').replace(/,/g, '').trim() || 0);
                    if (!isNaN(costValue)) {
                        costTotal += costValue;
                    }
                });

                // Add to position total: Proposed wins when entered (> 0); otherwise Current
                total += (proposedSalary > 0 ? proposedSalary : currentSalary) + costTotal;
            }
        });

        return total;
    }

    // Calculate section total from rendered tables
    function calculateSectionTotalFromTable($sectionElement) {
        let total = 0;
        $sectionElement.find('.position-accordion').each(function() {
            total += calculatePositionTotalFromTable($(this));
        });
        return total;
    }

    // Calculate department total from rendered tables
    function calculateDepartmentTotalFromTable($deptElement) {
        let total = 0;

        // Sum sections
        $deptElement.find('.section-accordion').each(function() {
            total += calculateSectionTotalFromTable($(this));
        });

        // Sum direct positions (not in sections)
        $deptElement.find('.position-accordion').each(function() {
            if ($(this).closest('.section-accordion').length === 0) {
                total += calculatePositionTotalFromTable($(this));
            }
        });

        return total;
    }

    // Calculate division total from rendered tables
    function calculateDivisionTotalFromTable($divisionElement) {
        let total = 0;
        $divisionElement.find('.department-accordion').each(function() {
            total += calculateDepartmentTotalFromTable($(this));
        });
        return total;
    }

    // Start loading all budget totals on page load. The 1s artificial
    // delay this used to have was working around loadAllBudgetTotalsOnPageLoad()
    // needing to read [data-department-id] elements from the DOM before it
    // could know what to fetch — the single-request version below doesn't
    // touch the DOM to decide what to request, so there's nothing left to
    // wait for.
    loadAllBudgetTotalsOnPageLoad();

    // Load department content when accordion is shown
    $(document).on('shown.bs.collapse', '[id^="collapseDept"]', function() {
        const $accordionBody = $(this).find('.accordion-body');
        const departmentId = $accordionBody.data('department-id');
        const divisionIteration = $accordionBody.data('division-iteration');
        const deptIteration = $accordionBody.data('dept-iteration');
        const year = $accordionBody.data('year');

        // Only load once
        if (loadedDepartments[departmentId]) {
            return;
        }

        loadDepartmentHierarchy(departmentId, divisionIteration, deptIteration, year, $accordionBody);
        loadedDepartments[departmentId] = true;
    });

    function loadDepartmentHierarchy(departmentId, divisionIteration, deptIteration, year, $container) {
        $.ajax({
            url: "{{ route('resort.budget.hierarchy.department') }}",
            method: 'GET',
            data: {
                department_id: departmentId,
                year: year,
                _token: csrfToken
            },
            success: function(response) {
                if (response.success) {
                    let html = '';

                    // Add sections with positions
                    if (response.sections && response.sections.length > 0) {
                        let sectionIteration = 1;
                        response.sections.forEach(section => {
                            html += `
                                <div class="accordion mb-2 ms-3 section-accordion" id="accordionSec${divisionIteration}_${deptIteration}_${sectionIteration}">
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingSec${divisionIteration}_${deptIteration}_${sectionIteration}">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                    data-bs-target="#collapseSec${divisionIteration}_${deptIteration}_${sectionIteration}"
                                                    aria-expanded="false" aria-controls="collapseSec${divisionIteration}_${deptIteration}_${sectionIteration}">
                                                <i class="fas fa-layer-group me-2"></i>
                                                <span>${section.name}</span>
                                                <span class="badge badge-dark ms-2 small sectionGrandTotal">Budget: {{ Common::GetResortCurrencySymbol() }} 0.00</span>
                                            </button>
                                        </h2>

                                        <div id="collapseSec${divisionIteration}_${deptIteration}_${sectionIteration}"
                                             class="collapse"
                                             aria-labelledby="headingSec${divisionIteration}_${deptIteration}_${sectionIteration}"
                                             data-bs-parent="#accordionSec${divisionIteration}_${deptIteration}_${sectionIteration}">
                                            <div class="accordion-body p-2">`;

                            if (response.positions_by_section[section.id]) {
                                let posSecIteration = 1;
                                response.positions_by_section[section.id].forEach(position => {
                                    html += createPositionHtml(position, divisionIteration, deptIteration, sectionIteration, posSecIteration);
                                    posSecIteration++;
                                });
                            }

                            html += `</div></div></div></div>`;
                            sectionIteration++;
                        });
                    }

                    // Add positions without section
                    if (response.positions_without_section && response.positions_without_section.length > 0) {
                        let positionIteration = 1;
                        response.positions_without_section.forEach(position => {
                            html += createPositionHtml(position, divisionIteration, deptIteration, 0, positionIteration);
                            positionIteration++;
                        });
                    }

                    $container.html(html || '<p class="text-muted">No positions found.</p>');

                    // Update badges from stored totals if available
                    setTimeout(() => {
                        updateBadgesFromStoredTotals($container);
                        // Bubble up to department + division badges using
                        // already-loaded window.budgetTotals.positions data
                        updateSectionDepartmentDivisionBadges();
                    }, 100);
                } else {
                    $container.html('<p class="text-danger">' + response.message + '</p>');
                }
            },
            error: function(xhr) {
                $container.html('<p class="text-danger">Error loading department hierarchy.</p>');
                console.error(xhr);
            }
        });
    }

    // Update badges from stored totals in a container
    function updateBadgesFromStoredTotals($container) {
        // Update position badges
        $container.find('.position-accordion').each(function() {
            const positionId = $(this).data('position-id');
            if (positionId && window.budgetTotals && window.budgetTotals.positions && window.budgetTotals.positions[positionId] && window.budgetTotals.positions[positionId].total) {
                const total = window.budgetTotals.positions[positionId].total;
                $(this).find('.positionGrandTotal').text('Budget: ' + formatAmount(total, 'USD'));
            }
        });

        // Update section badges
        $container.find('.section-accordion').each(function() {
            const $section = $(this);
            let sectionTotal = 0;
            $section.find('.position-accordion').each(function() {
                const positionId = $(this).data('position-id');
                if (positionId && window.budgetTotals && window.budgetTotals.positions && window.budgetTotals.positions[positionId] && window.budgetTotals.positions[positionId].total) {
                    sectionTotal += parseFloat(window.budgetTotals.positions[positionId].total);
                }
            });
            $section.find('.sectionGrandTotal').text('Budget: ' + formatAmount(sectionTotal, 'USD'));
            window.budgetTotals.sections[$section.attr('id')] = sectionTotal;
        });

        // Update the parent department badge for this container
        const $dept = $container.closest('.department-accordion');
        if ($dept.length) {
            let deptTotal = 0;
            $dept.find('.section-accordion').each(function() {
                const sectionId = $(this).attr('id');
                if (window.budgetTotals.sections[sectionId]) {
                    deptTotal += window.budgetTotals.sections[sectionId];
                }
            });
            $dept.find('.position-accordion').each(function() {
                if ($(this).closest('.section-accordion').length === 0) {
                    const positionId = $(this).data('position-id');
                    if (positionId && window.budgetTotals.positions[positionId]) {
                        deptTotal += parseFloat(window.budgetTotals.positions[positionId].total || 0);
                    }
                }
            });
            $dept.find('.departmentGrandTotal').first().text('Budget: ' + formatAmount(deptTotal, 'USD'));
            const deptId = $dept.closest('[data-department-id]').data('department-id') || $container.data('department-id');
            if (deptId) {
                window.budgetTotals.departments[deptId] = deptTotal;
            }
        }
    }

    function createPositionHtml(position, divisionIteration, deptIteration, sectionIteration, positionIteration) {
        const accordionId = `pos${divisionIteration}_${deptIteration}_${sectionIteration}_${positionIteration}`;
        // Check if we have stored total for this position
        let badgeText = 'Budget: {{ Common::GetResortCurrencySymbol() }} 0.00';
        if (window.budgetTotals && window.budgetTotals.positions && window.budgetTotals.positions[position.id] && window.budgetTotals.positions[position.id].total) {
            badgeText = 'Budget: ' + formatAmount(window.budgetTotals.positions[position.id].total, 'USD');
        }
        return `
            <div class="accordion mb-2 ms-3 position-accordion" id="accordion${accordionId}" data-position-id="${position.id}">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading${accordionId}">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapse${accordionId}"
                                aria-expanded="false" aria-controls="collapse${accordionId}">
                            <i class="fas fa-user-tie me-2"></i>
                            <span>${position.position_title}</span>
                            <span class="badge badge-info ms-2">${position.code || ''}</span>
                            <span class="badge badge-dark ms-2 small positionGrandTotal">${badgeText}</span>
                        </button>
                    </h2>

                    <div id="collapse${accordionId}"
                         class="collapse"
                         aria-labelledby="heading${accordionId}"
                         data-bs-parent="#accordion${accordionId}">
                        <div class="accordion-body p-2" data-position-id="${position.id}">
                            <div class="text-center py-3">
                                <div class="spinner-border spinner-border-sm" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <span class="ms-2">Loading employees...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    // Load position employees when position accordion is shown
    $(document).on('shown.bs.collapse', '[id^="collapse"][id*="pos"]', function() {
        const $accordionBody = $(this).find('.accordion-body');
        const positionId = $accordionBody.data('position-id');

        // Only load once
        if ($accordionBody.data('loaded')) {
            return;
        }

        loadPositionEmployees(positionId, $accordionBody);
        $accordionBody.data('loaded', true);
    });

    function loadPositionEmployees(positionId, $container) {
        $.ajax({
            url: "{{ route('resort.budget.hierarchy.position.employees') }}",
            method: 'GET',
            data: {
                position_id: positionId,
                year: year,
                _token: csrfToken
            },
            success: function(response) {
                if (response.success) {
                    let html = '';

                    if (response.employees && response.employees.length > 0) {
                        let empIteration = 1;
                        response.employees.forEach(employee => {
                            const rankConfig = @json(config('settings.Position_Rank'));
                            const rankName = rankConfig[employee.rank] || employee.rank;
                            const employeeAccordionId = `empAccordion_${positionId}_${empIteration}`;

                            html += `
                                <div class="accordion mb-2 ms-3 employee-accordion" id="${employeeAccordionId}" data-employee-rank="${employee.rank}" data-employee-rank-name="${rankName}" data-employee-picture="${employee.picture || ''}">
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading${employeeAccordionId}">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                    data-bs-target="#collapse${employeeAccordionId}"
                                                    aria-expanded="false" aria-controls="collapse${employeeAccordionId}">
                                                <i class="fas fa-user me-2"></i>
                                                <span>${employee.first_name} ${employee.last_name}</span>
                                                <span class="badge bg-secondary ms-2">${rankName}</span>
                                                <span class="badge bg-info ms-2">${employee.nationality}</span>
                                            </button>
                                        </h2>

                                        <div id="collapse${employeeAccordionId}"
                                             class="collapse"
                                             aria-labelledby="heading${employeeAccordionId}"
                                             data-bs-parent="#${employeeAccordionId}">
                                            <div class="accordion-body p-2"
                                                 data-employee-id="${employee.Empid}"
                                                 data-position-id="${positionId}"
                                                 data-type="employee">
                                                <div class="text-center py-3">
                                                    <div class="spinner-border spinner-border-sm" role="status">
                                                        <span class="visually-hidden">Loading...</span>
                                                    </div>
                                                    <span class="ms-2">Loading monthly budget...</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                            empIteration++;
                        });
                    }

                    // Add individual vacant positions as separate accordions
                    if (response.total_vacant_positions && response.total_vacant_positions > 0) {
                        for (let v = 1; v <= response.total_vacant_positions; v++) {
                            const vacantAccordionId = `vacantAccordion_${positionId}_${v}`;
                            html += `
                                <div class="accordion mb-2 ms-3 vacant-accordion" id="${vacantAccordionId}">
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading${vacantAccordionId}">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                    data-bs-target="#collapse${vacantAccordionId}"
                                                    aria-expanded="false" aria-controls="collapse${vacantAccordionId}">
                                                <i class="fas fa-user-slash me-2 text-warning"></i>
                                                <span>Vacant ${v}</span>
                                                <span class="badge bg-warning text-dark ms-2">Vacant Position</span>
                                            </button>
                                        </h2>

                                        <div id="collapse${vacantAccordionId}"
                                             class="collapse"
                                             aria-labelledby="heading${vacantAccordionId}"
                                             data-bs-parent="#${vacantAccordionId}">
                                            <div class="accordion-body p-2"
                                                 data-vacant-index="${v}"
                                                 data-position-id="${positionId}"
                                                 data-type="vacant">
                                                <div class="text-center py-3">
                                                    <div class="spinner-border spinner-border-sm" role="status">
                                                        <span class="visually-hidden">Loading...</span>
                                                    </div>
                                                    <span class="ms-2">Loading vacant position data...</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }
                    }

                    $container.html(html || '<p class="text-muted">No employees or vacant positions found.</p>');

                    // Auto-load all employee and vacant data immediately for badge calculation
                    let loadPromises = [];

                    // Load all employee data
                    if (response.employees && response.employees.length > 0) {
                        response.employees.forEach((employee, index) => {
                            const $empBody = $container.find(`[data-employee-id="${employee.Empid}"]`);
                            if ($empBody.length && !$empBody.data('loaded')) {
                                const promise = new Promise((resolve) => {
                                    loadEmployeeMonthlyData(employee.Empid, positionId, $empBody);
                                    $empBody.data('loaded', true);
                                    // Wait a bit for data to load
                                    setTimeout(resolve, 200);
                                });
                                loadPromises.push(promise);
                            }
                        });
                    }

                    // Load all vacant data
                    if (response.total_vacant_positions && response.total_vacant_positions > 0) {
                        for (let v = 1; v <= response.total_vacant_positions; v++) {
                            const $vacantBody = $container.find(`[data-vacant-index="${v}"]`);
                            if ($vacantBody.length && !$vacantBody.data('loaded')) {
                                const promise = new Promise((resolve) => {
                                    loadVacantMonthlyData(v, positionId, $vacantBody);
                                    $vacantBody.data('loaded', true);
                                    // Wait a bit for data to load
                                    setTimeout(resolve, 200);
                                });
                                loadPromises.push(promise);
                            }
                        }
                    }

                    // Badges are authoritative from the server (canonical
                    // per-position calculated_total — includes vacant slots +
                    // per-employee allowances). Do NOT recompute from the
                    // freshly-loaded table here: only THIS position's table is
                    // loaded, so a global recompute reads every OTHER position's
                    // table as 0 and wipes the correct department/division totals.
                    // (Reported: expanding one position zeroed all other budgets.)
                    Promise.all(loadPromises).then(() => {});

                } else {
                    $container.html('<p class="text-danger">' + response.message + '</p>');
                }
            },
            error: function(xhr) {
                $container.html('<p class="text-danger">Error loading employees.</p>');
                console.error(xhr);
            }
        });
    }

    // Load employee/vacant monthly data when accordion is shown
    $(document).on('shown.bs.collapse', '[id^="collapseempAccordion"], [id^="collapsevacantAccordion"]', function() {
        const $accordionBody = $(this).find('.accordion-body');
        const type = $accordionBody.data('type');
        const positionId = $accordionBody.data('position-id');

        // Only load once
        if ($accordionBody.data('loaded')) {
            return;
        }

        if (type === 'employee') {
            const employeeId = $accordionBody.data('employee-id');
            loadEmployeeMonthlyData(employeeId, positionId, $accordionBody);
        } else if (type === 'vacant') {
            const vacantIndex = $accordionBody.data('vacant-index');
            loadVacantMonthlyData(vacantIndex, positionId, $accordionBody);
        }

        $accordionBody.data('loaded', true);
    });

    function loadEmployeeMonthlyData(employeeId, positionId, $container) {
        $container.html('<div class="text-center"><div class="spinner-border spinner-border-sm"></div> Loading...</div>');

        $.ajax({
            url: "{{ route('resort.budget.hierarchy.employee.monthly') }}",
            method: 'GET',
            data: {
                employee_id: employeeId,
                position_id: positionId,
                year: year,
                _token: csrfToken
            },
            success: function(response) {
                if (response.success) {
                    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                    const resortCosts = response.resort_costs;
                    const monthCostData = response.month_cost_data;

                    // Fallback salaries from the employees table. Per-month
                    // overrides live in response.monthly_salaries[m] when set.
                    const currentBasicSalary = parseFloat(response.current_basic_salary || 0);
                    const proposedBasicSalary = parseFloat(response.proposed_basic_salary || 0);
                    const monthlySalaries = response.monthly_salaries || {};
                    const salaryForMonth = function (m) {
                        const row = monthlySalaries[m] || monthlySalaries[String(m)];
                        return {
                            current:  row && row.current_salary  !== undefined ? parseFloat(row.current_salary)  : currentBasicSalary,
                            proposed: row && row.proposed_salary !== undefined ? parseFloat(row.proposed_salary) : proposedBasicSalary
                        };
                    };

                    // Calculate totals from per-month effective salaries.
                    let totalCurrentSalary = 0;
                    let totalProposedSalary = 0;
                    for (let mm = 1; mm <= 12; mm++) {
                        const s = salaryForMonth(mm);
                        totalCurrentSalary  += s.current;
                        totalProposedSalary += s.proposed;
                    }
                    const totals = {
                        currentSalary: totalCurrentSalary,
                        proposedSalary: totalProposedSalary,
                        costs: {}
                    };

                    // Build table header. Annual summary + group chips are
                    // prepended once totals are final (below) — they need
                    // totals.costs fully accumulated first.
                    let html = `
                        <div class="wb-table-scroll" data-hidden-groups=" overtime travel insurance other ">
                            <table class="table table-bordered table-hover align-middle budget-monthly-table" style="font-size: 0.875rem;">
                                <thead style="background: var(--teal); color: white;">
                                    <tr>
                                        <th class="text-center wb-sticky-col wb-col-month" style="width: 80px; font-weight: 600;">Month</th>
                                        <th class="text-center wb-sticky-col wb-col-current" style="width: 130px; font-weight: 600;">Current Basic<br>Salary</th>
                                        <th class="text-center wb-sticky-col wb-col-proposed" style="width: 130px; font-weight: 600;">Proposed Basic<br>Salary</th>
                                        <th class="text-center wb-sticky-col wb-col-action" style="width: 80px; font-weight: 600;">Action</th>`;

                    // Add all cost configuration columns
                    resortCosts.forEach(cost => {
                        const costGroup = window.wbClassifyCost(cost);
                        html += `<th class="text-center" data-cost-group="${costGroup}" style="width: 140px; font-weight: 600;">${cost.particulars || cost.cost_title || 'N/A'}</th>`;
                        totals.costs[cost.id] = 0;
                    });

                    html += `</tr></thead><tbody>`;

                    // Add rows for each month (1-12)
                    for (let m = 1; m <= 12; m++) {
                        const monthData = monthCostData[m] || {};

                        // Use same salary for all months (from employees table)

                        const rowSalary = salaryForMonth(m);
                        html += `
                            <tr style="transition: all 0.2s;">
                                <td class="text-center wb-sticky-col wb-col-month" style="font-weight: 500; font-size: 0.813rem;">${months[m-1]}</td>
                                <td class="text-end wb-sticky-col wb-col-current" style="font-size: 0.813rem;">${formatAmountOrDash(rowSalary.current, 'USD')}</td>
                                <td class="text-end wb-sticky-col wb-col-proposed" style="font-size: 0.813rem;">${formatAmountOrDash(rowSalary.proposed, 'USD')}</td>
                                <td class="text-center wb-sticky-col wb-col-action">
                                    <button class="btn btn-sm eb-btn-secondary btn-edit-month-budget"
                                            data-month="${m}"
                                            data-month-name="${months[m-1]}"
                                            data-employee-id="${employeeId}"
                                            data-position-id="${positionId}"
                                            data-department-id="${response.department_id}"
                                            data-type="employee"
                                            title="Edit ${months[m-1]} Budget"
                                            style="padding: 0.25rem 0.5rem;">
                                        <i class="fas fa-edit" style="font-size: 0.75rem;"></i>
                                    </button>
                                    ${m < 12 ? `
                                    <button class="btn btn-sm eb-btn-positive btn-copy-down"
                                            data-month="${m}"
                                            data-month-name="${months[m-1]}"
                                            data-employee-id="${employeeId}"
                                            data-position-id="${positionId}"
                                            data-department-id="${response.department_id}"
                                            data-type="employee"
                                            title="Copy ${months[m-1]} values to ${months[m]}"
                                            style="padding: 0.25rem 0.5rem; margin-left: 2px;">
                                        <i class="fas fa-arrow-down" style="font-size: 0.75rem;"></i>
                                    </button>` : ''}
                                </td>`;

                        // Display cost configuration values (read-only)
                        resortCosts.forEach(cost => {
                            const costData = monthData && monthData[cost.id] ? monthData[cost.id] : null;
                            let costValue = costData && costData.value ? parseFloat(costData.value) : 0;
                            const currency = costData && costData.currency ? costData.currency : 'USD';
                            const originalValue = costValue;

                            // Convert MVR to USD if needed
                            // MVRtoDoller field stores the rate: 1 MVR = X USD
                            // Example: If MVRtoDoller = 1/15.42, then 1 MVR = 1/15.42 USD
                            // So: USD = MVR × MVRtoDoller
                            if (currency === 'MVR' && costValue > 0) {
                                try {
                                    const mvrToUsdRate = window.mvrToDollarRate || 1/15.42;
                                    costValue = costValue * mvrToUsdRate;
                                    console.log(`Cost ${cost.particulars}: ${originalValue} MVR × ${mvrToUsdRate} = ${costValue.toFixed(2)} USD`);
                                } catch (e) {
                                    console.error('MVR conversion error:', e);
                                }
                            }

                            if (!isNaN(costValue)) {
                                totals.costs[cost.id] += parseFloat(costValue);
                            }

                            html += `
                                <td class="text-end"
                                    data-month="${m}"
                                    data-cost-id="${cost.id}"
                                    data-cost-group="${window.wbClassifyCost(cost)}"
                                    data-employee-id="${employeeId}"
                                    data-currency="${currency}"
                                    data-original-value="${originalValue}"
                                    data-usd-value="${costValue.toFixed(2)}"
                                    style="font-size: 0.813rem;">
                                    ${formatAmountOrDash(parseFloat(costValue), 'USD')}
                                </td>`;
                        });

                        html += `</tr>`;
                    }

                    // Add Total row
                    html += `
                        <tr class="table-total-row" style="background-color: #f8f9fa; font-weight: 600; border-top: 2px solid #dee2e6;">
                            <td class="text-center wb-sticky-col wb-col-month" style="font-weight: 700;">TOTAL</td>
                            <td class="text-end text-primary total-current-salary wb-sticky-col wb-col-current" style="font-weight: 700;">${formatAmountOrDash(totals.currentSalary, 'USD')}</td>
                            <td class="text-end text-success total-proposed-salary wb-sticky-col wb-col-proposed" style="font-weight: 700;">${formatAmountOrDash(totals.proposedSalary, 'USD')}</td>
                            <td class="wb-sticky-col wb-col-action"></td>`;

                    resortCosts.forEach(cost => {
                        html += `<td class="text-end text-dark total-cost-${cost.id}" data-cost-id="${cost.id}" data-cost-group="${window.wbClassifyCost(cost)}" style="font-weight: 700;">${formatAmountOrDash(totals.costs[cost.id], 'USD')}</td>`;
                    });

                    html += `</tr></tbody></table></div>`;

                    // Totals are final now — assemble the detail card in
                    // visual order: annual summary, then group chips, then
                    // the table built above.
                    const finalHtml = '<div class="wb-detail-card-inner mt-3">'
                        + wbAnnualSummaryHtml(response, totals, resortCosts)
                        + wbGroupChipsHtml(resortCosts)
                        + html
                        + '</div>';

                    $container.html(finalHtml);

                    // Server-rendered badges are authoritative — do not recompute
                    // from this freshly-loaded table (it would read sibling
                    // positions' unloaded tables as 0 and wipe their totals).
                } else {
                    $container.html('<p class="text-danger">' + response.message + '</p>');
                }
            },
            error: function(xhr) {
                $container.html('<p class="text-danger">Error loading monthly data.</p>');
                console.error(xhr);
            }
        });
    }

    function calculateEmployeeTotals(employeeId, resortCosts) {
        let totalCurrentSalary = 0;
        let totalProposedSalary = 0;
        const costTotals = {};

        for (let m = 1; m <= 12; m++) {
            const currentSalary = parseFloat($(`.current-salary-${m}[data-employee-id="${employeeId}"]`).val() || 0);
            const proposedSalary = parseFloat($(`.proposed-salary-${m}[data-employee-id="${employeeId}"]`).val() || 0);

            totalCurrentSalary += currentSalary;
            totalProposedSalary += proposedSalary;

            resortCosts.forEach(cost => {
                const costValue = parseFloat($(`.cost-${m}-${cost.id}[data-employee-id="${employeeId}"]`).val() || 0);
                if (!costTotals[cost.id]) {
                    costTotals[cost.id] = 0;
                }
                costTotals[cost.id] += costValue;
            });
        }

        $('#total-current-salary').text(formatAmount(totalCurrentSalary, 'USD'));
        $('#total-proposed-salary').text(formatAmount(totalProposedSalary, 'USD'));

        resortCosts.forEach(cost => {
            $(`#total-cost-${cost.id}`).text(formatAmount(costTotals[cost.id] || 0, 'USD'));
        });
    }

    function loadVacantMonthlyData(vacantIndex, positionId, $container) {
        $container.html('<div class="text-center"><div class="spinner-border spinner-border-sm"></div> Loading...</div>');

        $.ajax({
            url: "{{ route('resort.budget.hierarchy.vacant.monthly') }}",
            method: 'GET',
            data: {
                vacant_index: vacantIndex,
                position_id: positionId,
                year: year,
                _token: csrfToken
            },
            success: function(response) {
                if (response.success) {
                    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                    const resortCosts = response.resort_costs;
                    const monthCostData = response.month_cost_data || {};

                    // Fallback salaries from resort_vacant_budget_costs; per-month
                    // overrides live in response.monthly_salaries[m] when set.
                    const currentBasicSalary = parseFloat(response.current_basic_salary || 0);
                    const proposedBasicSalary = parseFloat(response.proposed_basic_salary || 0);
                    const monthlySalaries = response.monthly_salaries || {};
                    const salaryForMonth = function (m) {
                        const row = monthlySalaries[m] || monthlySalaries[String(m)];
                        return {
                            current:  row && row.current_salary  !== undefined ? parseFloat(row.current_salary)  : currentBasicSalary,
                            proposed: row && row.proposed_salary !== undefined ? parseFloat(row.proposed_salary) : proposedBasicSalary
                        };
                    };

                    let totalCurrentSalary = 0;
                    let totalProposedSalary = 0;
                    for (let mm = 1; mm <= 12; mm++) {
                        const s = salaryForMonth(mm);
                        totalCurrentSalary  += s.current;
                        totalProposedSalary += s.proposed;
                    }
                    const totals = {
                        currentSalary: totalCurrentSalary,
                        proposedSalary: totalProposedSalary,
                        costs: {}
                    };

                    // Build table header. Annual summary + group chips are
                    // prepended once totals are final (below).
                    let html = `
                        <div class="wb-table-scroll" data-hidden-groups=" overtime travel insurance other ">
                            <table class="table table-bordered table-hover align-middle budget-monthly-table" style="font-size: 0.875rem;">
                                <thead style="background: var(--teal); color: white;">
                                    <tr>
                                        <th class="text-center wb-sticky-col wb-col-month" style="width: 80px; font-weight: 600;">Month</th>
                                        <th class="text-center wb-sticky-col wb-col-current" style="width: 130px; font-weight: 600;">Current Basic<br>Salary</th>
                                        <th class="text-center wb-sticky-col wb-col-proposed" style="width: 130px; font-weight: 600;">Proposed Basic<br>Salary</th>
                                        <th class="text-center wb-sticky-col wb-col-action" style="width: 80px; font-weight: 600;">Action</th>`;

                    // Add all cost configuration columns
                    resortCosts.forEach(cost => {
                        const costGroup = window.wbClassifyCost(cost);
                        html += `<th class="text-center" data-cost-group="${costGroup}" style="width: 140px; font-weight: 600;">${cost.particulars || cost.cost_title || 'N/A'}</th>`;
                        totals.costs[cost.id] = 0;
                    });

                    html += `</tr></thead><tbody>`;

                    // Add rows for each month (1-12)
                    for (let m = 1; m <= 12; m++) {
                        const monthData = monthCostData[m] || {};

                        // Use same salary for all months (from resort_vacant_budget_costs table)

                        const rowSalary = salaryForMonth(m);
                        html += `
                            <tr style="transition: all 0.2s;">
                                <td class="text-center wb-sticky-col wb-col-month" style="font-weight: 500; font-size: 0.813rem;">${months[m-1]}</td>
                                <td class="text-end wb-sticky-col wb-col-current" style="font-size: 0.813rem;">${formatAmountOrDash(rowSalary.current, 'USD')}</td>
                                <td class="text-end wb-sticky-col wb-col-proposed" style="font-size: 0.813rem;">${formatAmountOrDash(rowSalary.proposed, 'USD')}</td>
                                <td class="text-center wb-sticky-col wb-col-action">
                                    <button class="btn btn-sm eb-btn-secondary btn-edit-month-budget"
                                            data-month="${m}"
                                            data-month-name="${months[m-1]}"
                                            data-vacant-index="${vacantIndex}"
                                            data-vacant-budget-cost-id="${response.vacant_budget_cost_id}"
                                            data-position-id="${positionId}"
                                            data-department-id="${response.department_id}"
                                            data-type="vacant"
                                            title="Edit ${months[m-1]} Budget"
                                            style="padding: 0.25rem 0.5rem;">
                                        <i class="fas fa-edit" style="font-size: 0.75rem;"></i>
                                    </button>
                                    ${m < 12 ? `
                                    <button class="btn btn-sm eb-btn-positive btn-copy-down"
                                            data-month="${m}"
                                            data-month-name="${months[m-1]}"
                                            data-vacant-index="${vacantIndex}"
                                            data-vacant-budget-cost-id="${response.vacant_budget_cost_id}"
                                            data-position-id="${positionId}"
                                            data-department-id="${response.department_id}"
                                            data-type="vacant"
                                            title="Copy ${months[m-1]} values to ${months[m]}"
                                            style="padding: 0.25rem 0.5rem; margin-left: 2px;">
                                        <i class="fas fa-arrow-down" style="font-size: 0.75rem;"></i>
                                    </button>` : ''}
                                </td>`;

                        // Display cost configuration values (read-only)
                        resortCosts.forEach(cost => {
                            const costData = monthData && monthData[cost.id] ? monthData[cost.id] : null;
                            let costValue = costData && costData.value ? parseFloat(costData.value) : 0;
                            const currency = costData && costData.currency ? costData.currency : 'USD';
                            const originalValue = costValue;

                            // Convert MVR to USD if needed
                            // MVRtoDoller field stores the rate: 1 MVR = X USD
                            // Example: If MVRtoDoller = 1/15.42, then 1 MVR = 1/15.42 USD
                            // So: USD = MVR × MVRtoDoller
                            if (currency === 'MVR' && costValue > 0) {
                                try {
                                    const mvrToUsdRate = window.mvrToDollarRate || 1/15.42;
                                    costValue = costValue * mvrToUsdRate;
                                    console.log(`Vacant Cost ${cost.particulars}: ${originalValue} MVR × ${mvrToUsdRate} = ${costValue.toFixed(2)} USD`);
                                } catch (e) {
                                    console.error('MVR conversion error:', e);
                                }
                            }

                            if (!isNaN(costValue)) {
                                totals.costs[cost.id] += parseFloat(costValue);
                            }

                            html += `
                                <td class="text-end"
                                    data-month="${m}"
                                    data-cost-id="${cost.id}"
                                    data-cost-group="${window.wbClassifyCost(cost)}"
                                    data-vacant-index="${vacantIndex}"
                                    data-currency="${currency}"
                                    data-original-value="${originalValue}"
                                    data-usd-value="${costValue.toFixed(2)}"
                                    style="font-size: 0.813rem;">
                                    ${formatAmountOrDash(parseFloat(costValue), 'USD')}
                                </td>`;
                        });

                        html += `</tr>`;
                    }

                    // Add Total row
                    html += `
                        <tr class="table-total-row" style="background-color: #f8f9fa; font-weight: 600; border-top: 2px solid #dee2e6;">
                            <td class="text-center wb-sticky-col wb-col-month" style="font-weight: 700;">TOTAL</td>
                            <td class="text-end text-primary total-current-salary wb-sticky-col wb-col-current" style="font-weight: 700;">${formatAmountOrDash(totals.currentSalary, 'USD')}</td>
                            <td class="text-end text-success total-proposed-salary wb-sticky-col wb-col-proposed" style="font-weight: 700;">${formatAmountOrDash(totals.proposedSalary, 'USD')}</td>
                            <td class="wb-sticky-col wb-col-action"></td>`;

                    resortCosts.forEach(cost => {
                        html += `<td class="text-end text-dark total-cost-${cost.id}" data-cost-id="${cost.id}" data-cost-group="${window.wbClassifyCost(cost)}" style="font-weight: 700;">${formatAmountOrDash(totals.costs[cost.id], 'USD')}</td>`;
                    });

                    html += `</tr></tbody></table></div>`;

                    const finalHtml = '<div class="wb-detail-card-inner mt-3">'
                        + wbAnnualSummaryHtml(response, totals, resortCosts)
                        + wbGroupChipsHtml(resortCosts)
                        + html
                        + '</div>';

                    $container.html(finalHtml);

                    // Server-rendered badges are authoritative — do not recompute
                    // from this freshly-loaded table (it would read sibling
                    // positions' unloaded tables as 0 and wipe their totals).
                } else {
                    $container.html('<p class="text-danger">' + response.message + '</p>');
                }
            },
            error: function(xhr) {
                $container.html('<p class="text-danger">Error loading vacant position data.</p>');
                console.error(xhr);
            }
        });
    }

    // Revise budget modal
    $(document).on('click', '.revisebudgetmodal', function() {
        $(".Revise_Budget_id").val($(this).data("budget_id"));
        $(".Revise_Department_id").val($(this).data("dept_id"));
    });

    // Revise Budget form submission handler
    $('#ReviseBudget').validate({
        rules: {
            ReviseBudgetComment: {
                required: true,
            }
        },
        messages: {
            ReviseBudgetComment: {
                required: "Please Add Revise Budget Comment.",
            }
        },
        submitHandler: function(form, event) {
            if (event) {
                event.preventDefault();
            }

            $.ajax({
                url: "{{ route('resort.ReviseBudget.manning.notification') }}",
                type: "POST",
                data: $(form).serialize(),
                success: function(response) {
                    if (response.success) {
                        $('#revise-budgetmodal').modal('hide');
                        $(".revisebudgetmodal").prop('disabled', true);
                        toastr.success(response.msg, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        // Reload the page to reflect changes
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    }
                },
                error: function(response) {
                    let errors = response.responseJSON;
                    let errs = '';

                    if (errors && errors.errors) {
                        $.each(errors.errors, function(key, error) {
                            errs += error + '<br>';
                        });
                    } else {
                        errs = 'An unexpected error occurred. Please try again.';
                    }

                    toastr.error(errs, { positionClass: 'toast-bottom-right' });
                }
            });
        }
    });

    // Copy-down — take this row's salary + cost values and save them to every
    // subsequent month for the same employee / vacant. Uses the same backend
    // save endpoint as the per-month modal, so any server-side validation
    // continues to apply.
    $(document).on('click', '.btn-copy-down', function () {
        const $btn = $(this);
        const sourceMonth = parseInt($btn.data('month'), 10);
        const monthName = $btn.data('month-name');
        const type = $btn.data('type');
        const positionId = $btn.data('position-id');
        const departmentId = $btn.data('department-id');

        if (!sourceMonth || sourceMonth >= 12) return;

        // Source row — anchor by data-month + employee/vacant key.
        const keyAttr = type === 'employee' ? 'data-employee-id' : 'data-vacant-index';
        const keyVal  = type === 'employee' ? $btn.data('employee-id') : $btn.data('vacant-index');
        const vacantBudgetCostId = type === 'vacant' ? $btn.data('vacant-budget-cost-id') : null;

        const $sourceRow = $(`td[data-month="${sourceMonth}"][${keyAttr}="${keyVal}"]`).first().closest('tr');
        if (!$sourceRow.length) {
            toastr.error('Could not locate source row to copy from.', 'Copy Down', { positionClass: 'toast-bottom-right' });
            return;
        }

        // Salary — read from the rendered cell (column 1 = Current, column 2 = Proposed).
        // Use the SYSTEM currency symbol (was hardcoded '$' which left "MVR"
        // strings intact on MVR resorts, breaking parseFloat).
        const currencySymbol = '{{ Common::GetResortCurrencySymbol() }}';
        // parseFloat("—") (the zero-as-dash placeholder) is NaN, not 0 — the
        // `|| 0` fallback only catches an empty string, so NaN must be
        // caught explicitly or a genuinely-zero source month would copy
        // forward as NaN instead of 0.
        const basicSalaryRaw    = parseFloat($sourceRow.find('td').eq(1).text().replace(currencySymbol, '').replace(/,/g, '').trim());
        const proposedSalaryRaw = parseFloat($sourceRow.find('td').eq(2).text().replace(currencySymbol, '').replace(/,/g, '').trim());
        const basicSalary    = isNaN(basicSalaryRaw) ? 0 : basicSalaryRaw;
        const proposedSalary = isNaN(proposedSalaryRaw) ? 0 : proposedSalaryRaw;

        // Cost configurations — preserve original currency + value so the
        // server can re-store the MVR value (data-original-value) the same
        // way the per-month modal does. The USD-converted display value is
        // passed too so the row updates instantly client-side.
        const costConfigurations = [];
        $sourceRow.find('td[data-cost-id]').each(function () {
            const $c = $(this);
            costConfigurations.push({
                resort_budget_cost_id: $c.data('cost-id'),
                currency: $c.data('currency') || 'USD',
                value: parseFloat($c.data('usd-value') || 0),                       // USD-converted value (what gets stored / displayed)
                original_value: parseFloat($c.data('original-value') || 0),         // raw MVR (or USD) value
                hours: 0
            });
        });

        // Copy only to the immediately-next month. HR can chain clicks down
        // the table when they want to fill more rows — this avoids the
        // earlier "Jan → all of Feb–Dec" behaviour that overwrote months
        // they had already entered different values for.
        const nextMonth = sourceMonth + 1;
        if (nextMonth > 12) return;
        const monthsLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        const nextMonthName = monthsLabels[nextMonth - 1];
        const targetMonths = [nextMonth];

        wisdomConfirm({
            role: 'confirm',
            title: 'Copy values to next month?',
            confirmText: 'Yes, copy',
            extra: {
                html: `Copy <strong>${monthName}</strong>'s salary and cost values to <strong>${nextMonthName}</strong>? Any existing entry in ${nextMonthName} will be overwritten.`
            }
        }).then(function (result) {
            if (!result.isConfirmed) return;
            performCopyDown();
        });

        function performCopyDown() {
        $btn.prop('disabled', true);
        const original = $btn.html();
        $btn.html('<i class="fas fa-spinner fa-spin" style="font-size: 0.75rem;"></i>');

        let completed = 0;
        let failed = 0;
        const totalCalls = targetMonths.length;

        targetMonths.forEach(function (targetMonth) {
            // Salary is now per-month (resort_employee_monthly_salaries /
            // resort_vacant_monthly_salaries), so copy-down can safely
            // propagate both salary AND cost configurations to the target
            // month only — without touching any other month.
            const payload = {
                position_id:    positionId,
                department_id:  departmentId,
                year:           year,
                monthly_data:   [{
                    month: targetMonth,
                    current_salary:  basicSalary,
                    proposed_salary: proposedSalary,
                    cost_configurations: costConfigurations
                }],
                _token: csrfToken
            };

            const url = (type === 'employee')
                ? "{{ route('resort.budget.hierarchy.employee.update') }}"
                : "{{ route('resort.budget.hierarchy.vacant.update') }}";

            if (type === 'employee') {
                payload.employee_id = keyVal;
            } else {
                payload.vacant_index = keyVal;
                payload.vacant_budget_cost_id = vacantBudgetCostId;
            }

            $.ajax({
                url: url,
                method: 'POST',
                data: payload
            }).done(function (res) {
                if (res && res.success) {
                    if (type === 'employee') {
                        updateEmployeeMonthRow(keyVal, targetMonth, basicSalary, proposedSalary, costConfigurations);
                    } else if (typeof updateVacantMonthRow === 'function') {
                        updateVacantMonthRow(keyVal, targetMonth, basicSalary, proposedSalary, costConfigurations);
                    }
                } else {
                    failed++;
                }
            }).fail(function () {
                failed++;
            }).always(function () {
                completed++;
                if (completed === totalCalls) {
                    $btn.prop('disabled', false).html(original);
                    if (failed === 0) {
                        toastr.success(`Copied ${monthName} values to ${nextMonthName}.`, 'Copy Down', { positionClass: 'toast-bottom-right' });
                    } else {
                        toastr.error(`Failed to copy ${monthName} → ${nextMonthName}.`, 'Copy Down', { positionClass: 'toast-bottom-right' });
                    }

                    if (type === 'employee' && typeof recalculateEmployeeTableTotals === 'function') {
                        recalculateEmployeeTableTotals(keyVal);
                    }
                    if (typeof updateBadgesHierarchy === 'function') {
                        updateBadgesHierarchy(positionId);
                    }
                    if (typeof window.recalculateAllTotals === 'function') {
                        window.recalculateAllTotals();
                    }

                    // Refresh the annual summary strip with the canonical
                    // server figure — same staleness issue as a modal save
                    // (see saveEmployeeMonthBudget/saveVacantMonthBudget):
                    // it doesn't update itself from the row/badge patches
                    // above, so it stays frozen at whatever it showed
                    // before this copy-down.
                    if (failed === 0) {
                        if (type === 'employee') {
                            const $employeeContainer = $(`.accordion-body[data-employee-id="${keyVal}"]`).first();
                            if ($employeeContainer.length) loadEmployeeMonthlyData(keyVal, positionId, $employeeContainer);
                        } else {
                            const $vacantContainer = $(`.accordion-body[data-vacant-index="${keyVal}"]`).first();
                            if ($vacantContainer.length) loadVacantMonthlyData(keyVal, positionId, $vacantContainer);
                        }
                    }
                }
            });
        });
        }
    });

    // Edit month budget - Open modal
    $(document).on('click', '.btn-edit-month-budget', function() {
        const $btn = $(this);
        const month = $btn.data('month');
        const monthName = $btn.data('month-name');
        const type = $btn.data('type');
        const positionId = $btn.data('position-id');
        const departmentId = $btn.data('department-id');

        // Set modal title and context
        $('#budgetCostModalLabel').html(`<i class="fas fa-wallet me-2"></i>Budget Cost Assignment - ${monthName}`);
        $('#modalTableType').text(type === 'employee' ? 'Employee' : 'Vacant Position');

        // Store month and type in modal for later use
        $('#budgetCostModal').data('edit-month', month);
        $('#budgetCostModal').data('edit-type', type);
        $('#budgetCostModal').data('position-id', positionId);
        $('#budgetCostModal').data('department-id', departmentId);

        if (type === 'employee') {
            const employeeId = $btn.data('employee-id');
            $('#budgetCostModal').data('employee-id', employeeId);

            // Load employee data for this specific month
            loadEmployeeDataForMonth(employeeId, positionId, departmentId, month);
        } else {
            const vacantIndex = $btn.data('vacant-index');
            const vacantBudgetCostId = $btn.data('vacant-budget-cost-id');
            $('#budgetCostModal').data('vacant-index', vacantIndex);
            $('#budgetCostModal').data('vacant-budget-cost-id', vacantBudgetCostId);

            // Load vacant data for this specific month
            loadVacantDataForMonth(vacantIndex, vacantBudgetCostId, positionId, departmentId, month);
        }

        // Toggle Details select visibility based on type
        if (typeof window.toggleDetailsSelect === 'function') {
            setTimeout(function() {
                window.toggleDetailsSelect();
            }, 100);
        }

        // Show the modal
        $('#budgetCostModal').modal('show');
    });

    // Load employee data for a specific month into modal
    function loadEmployeeDataForMonth(employeeId, positionId, departmentId, month) {
        // Get employee data from the accordion
        const $employeeAccordion = $(`[data-employee-id="${employeeId}"]`).closest('.employee-accordion');
        const employeeName = $employeeAccordion.find('.accordion-button span').first().text().trim();

        // Get rank from data attribute (more reliable) or fallback to badge text
        let employeeRank = $employeeAccordion.data('employee-rank') || '';
        const employeeRankName = $employeeAccordion.data('employee-rank-name') || '';
        const rankBadge = $employeeAccordion.find('.badge.bg-secondary').text().trim();

        // Use the most reliable source for rank - prioritize raw rank value
        const finalRank = employeeRank || employeeRankName || rankBadge || '';

        console.log('Loading employee data:', {
            employeeId: employeeId,
            rawRank: employeeRank,
            rankName: employeeRankName,
            rankBadge: rankBadge,
            finalRank: finalRank
        });

        // Get current values from table. The cell may render the zero-as-dash
        // placeholder ("—") for a genuinely-zero month — that isn't a valid
        // number for the modal's input fields, so normalize through parseFloat
        // rather than trusting the raw text (which the old `|| '0.00'` fallback
        // wouldn't catch, since a non-empty "—" string is truthy).
        const currentSalaryNum = parseFloat($(`td[data-month="${month}"][data-employee-id="${employeeId}"]`).closest('tr').find('td:eq(1)').text().replace(currencySymbol, '').replace(/,/g, '').trim());
        const proposedSalaryNum = parseFloat($(`td[data-month="${month}"][data-employee-id="${employeeId}"]`).closest('tr').find('td:eq(2)').text().replace(currencySymbol, '').replace(/,/g, '').trim());
        const currentSalary = isNaN(currentSalaryNum) ? '0.00' : currentSalaryNum.toFixed(2);
        const proposedSalary = isNaN(proposedSalaryNum) ? '0.00' : proposedSalaryNum.toFixed(2);

        // Set salary fields
        $('#formBasicSalary').val(currentSalary || '0.00');
        $('#formCurrentSalary').val(proposedSalary || '0.00');

        // Set hidden fields
        $('#formDepartmentId').val(departmentId);
        $('#formPositionId').val(positionId);
        $('#formTableType').val('employee');
        $('#formEmployeeId').val(employeeId);

        // Load cost configurations with existing values FIRST (before auto-calculations)
        $('.budget-cost-checkbox').each(function() {
            const costId = $(this).data('cost-id');
            const $card = $(this).closest('.budget-cost-card');
            const isPercentageBased = $card.data('is-percentage') == '1';
            const frequency = ($card.data('frequency') || '').toLowerCase();
            const $amountInput = $(`.budget-cost-amount[data-cost-id="${costId}"]`);
            const $hoursInput = $(`.budget-cost-hours[data-cost-id="${costId}"]`);
            const $currencySelect = $(`.budget-cost-currency[data-cost-id="${costId}"]`);

            // Try to get existing value from table
            const $tableCell = $(`td[data-month="${month}"][data-employee-id="${employeeId}"][data-cost-id="${costId}"]`);
            let value = 0;
            let currency = 'USD';

            if ($tableCell.length) {
                // Get currency from data attribute
                currency = $tableCell.data('currency') || 'USD';

                // If currency is MVR, use original MVR value from data attribute
                // Otherwise, use the displayed USD value
                if (currency === 'MVR') {
                    // Get original MVR value from data attribute (stored before conversion to USD)
                    value = parseFloat($tableCell.data('original-value') || 0);
                } else {
                    // For USD, get the displayed value
                    value = parseFloat($tableCell.text().replace(currencySymbol, '').replace(/,/g, '').trim() || 0);
                }
            }

            // If value exists, check and populate
            if (value > 0) {
                $(this).prop('checked', true);

                // Set currency select to match the currency from table
                if ($currencySelect.length && currency) {
                    $currencySelect.val(currency);
                }

                // For daily frequency items, the value in table is already the monthly total
                // So we store it as is, but we need to calculate the daily rate for future calculations
                if (!isPercentageBased && frequency === 'daily') {
                    // Get year and calculate days in month
                    const year = getCurrentYear();
                    const daysInMonth = new Date(year, month, 0).getDate();
                    if (daysInMonth > 0) {
                        // Calculate daily rate from monthly total
                        const dailyRate = value / daysInMonth;
                        $amountInput.data('original-amount', dailyRate.toFixed(2));
                        $amountInput.val(value.toFixed(2)); // Keep monthly total displayed
                    } else {
                        $amountInput.val(value.toFixed(2));
                    }
                } else {
                    $amountInput.val(value.toFixed(2));
                }

                // For percentage-based items, set hours if available from table
                if (isPercentageBased && $hoursInput.length) {
                    // Try to get hours from data attribute or default to saved value
                    const savedHours = $hoursInput.data('default-hours') || 0;
                    $hoursInput.val(savedHours);
                }
            } else {
                // No existing value - don't set anything yet, let auto-calculation handle it
                $(this).prop('checked', false);
                // Don't set amount yet - auto-calculation will set it
            }
        });

        // NOW set employee rank and salary for overtime and pension auto-calculations
        // This must happen AFTER loading existing values so auto-calc only applies to new items
        if (typeof window.setBudgetModalEmployeeData === 'function') {
            window.setBudgetModalEmployeeData(finalRank, currentSalary, proposedSalary, month);
        }

        // Apply daily frequency multiplier for items without existing values
        // This should happen after loading existing values
        setTimeout(function() {
            if (typeof window.applyDailyFrequencyMultiplier === 'function') {
                window.applyDailyFrequencyMultiplier();
            }
        }, 200);

        // Calculate initial total
        if (typeof window.updateModalTotal === 'function') {
            window.updateModalTotal();
        }
    }

    // Load vacant data for a specific month into modal
    function loadVacantDataForMonth(vacantIndex, vacantBudgetCostId, positionId, departmentId, month) {
        // Get current values from table (see loadEmployeeDataForMonth for why
        // this normalizes through parseFloat rather than trusting raw text —
        // a zero-as-dash "—" cell is a non-empty, truthy string).
        const currentSalaryNum = parseFloat($(`td[data-month="${month}"][data-vacant-index="${vacantIndex}"]`).closest('tr').find('td:eq(1)').text().replace(currencySymbol, '').replace(/,/g, '').trim());
        const proposedSalaryNum = parseFloat($(`td[data-month="${month}"][data-vacant-index="${vacantIndex}"]`).closest('tr').find('td:eq(2)').text().replace(currencySymbol, '').replace(/,/g, '').trim());
        const currentSalary = isNaN(currentSalaryNum) ? '0.00' : currentSalaryNum.toFixed(2);
        const proposedSalary = isNaN(proposedSalaryNum) ? '0.00' : proposedSalaryNum.toFixed(2);

        // Set salary fields
        $('#formBasicSalary').val(currentSalary || '0.00');
        $('#formCurrentSalary').val(proposedSalary || '0.00');

        // Set hidden fields
        $('#formDepartmentId').val(departmentId);
        $('#formPositionId').val(positionId);
        $('#formTableType').val('vacant');
        $('#formVacantIndex').val(vacantIndex);

        // Load details value from backend
        $.ajax({
            url: "{{ route('resort.budget.hierarchy.vacant.monthly') }}",
            method: 'GET',
            data: {
                vacant_index: vacantIndex,
                position_id: positionId,
                year: year,
                _token: csrfToken
            },
            success: function(response) {
                if (response.success && response.details) {
                    $('#vacantDetailsSelect').val(response.details);
                }
            },
            error: function(xhr) {
                console.error('Error loading details:', xhr);
            }
        });

        // Load cost configurations with existing values FIRST (before auto-calculations)
        $('.budget-cost-checkbox').each(function() {
            const costId = $(this).data('cost-id');
            const $card = $(this).closest('.budget-cost-card');
            const isPercentageBased = $card.data('is-percentage') == '1';
            const frequency = ($card.data('frequency') || '').toLowerCase();
            const $amountInput = $(`.budget-cost-amount[data-cost-id="${costId}"]`);
            const $hoursInput = $(`.budget-cost-hours[data-cost-id="${costId}"]`);
            const $currencySelect = $(`.budget-cost-currency[data-cost-id="${costId}"]`);

            // Try to get existing value from table
            const $tableCell = $(`td[data-month="${month}"][data-vacant-index="${vacantIndex}"][data-cost-id="${costId}"]`);
            let value = 0;
            let currency = 'USD';

            if ($tableCell.length) {
                // Get currency from data attribute
                currency = $tableCell.data('currency') || 'USD';

                // If currency is MVR, use original MVR value from data attribute
                // Otherwise, use the displayed USD value
                if (currency === 'MVR') {
                    // Get original MVR value from data attribute (stored before conversion to USD)
                    value = parseFloat($tableCell.data('original-value') || 0);
                } else {
                    // For USD, get the displayed value
                    value = parseFloat($tableCell.text().replace(currencySymbol, '').replace(/,/g, '').trim() || 0);
                }
            }

            // If value exists, check and populate
            if (value > 0) {
                $(this).prop('checked', true);

                // Set currency select to match the currency from table
                if ($currencySelect.length && currency) {
                    $currencySelect.val(currency);
                }

                // For daily frequency items, the value in table is already the monthly total
                // So we store it as is, but we need to calculate the daily rate for future calculations
                if (!isPercentageBased && frequency === 'daily') {
                    // Get year and calculate days in month
                    const year = getCurrentYear();
                    const daysInMonth = new Date(year, month, 0).getDate();
                    if (daysInMonth > 0) {
                        // Calculate daily rate from monthly total
                        const dailyRate = value / daysInMonth;
                        $amountInput.data('original-amount', dailyRate.toFixed(2));
                        $amountInput.val(value.toFixed(2)); // Keep monthly total displayed
                    } else {
                        $amountInput.val(value.toFixed(2));
                    }
                } else {
                    $amountInput.val(value.toFixed(2));
                }

                // For percentage-based items, set hours if available from table
                if (isPercentageBased && $hoursInput.length) {
                    const savedHours = $hoursInput.data('default-hours') || 0;
                    $hoursInput.val(savedHours);
                }
            } else {
                // No existing value - don't set anything yet, let auto-calculation handle it
                $(this).prop('checked', false);
                // Don't set amount yet - auto-calculation will set it
            }
        });

        // NOW set employee rank and salary for overtime and pension auto-calculations
        // For vacant positions, assume "Line Worker" rank for overtime eligibility
        if (typeof window.setBudgetModalEmployeeData === 'function') {
            window.setBudgetModalEmployeeData('Line Worker', currentSalary, proposedSalary, month);
        }

        // Apply daily frequency multiplier for items without existing values
        // This should happen after loading existing values
        setTimeout(function() {
            if (typeof window.applyDailyFrequencyMultiplier === 'function') {
                window.applyDailyFrequencyMultiplier();
            }
        }, 200);

        // Calculate initial total
        if (typeof window.updateModalTotal === 'function') {
            window.updateModalTotal();
        }
    }

    // Update modal total when cost items change
    function updateModalTotal() {
        let total = 0;
        const mvrToUsdRate = parseFloat($('#mvrToDollarRate').val() || 1/15.42);

        $('.budget-cost-checkbox:checked').each(function() {
            const costId = $(this).data('cost-id');
            const amount = parseFloat($(`.budget-cost-amount[data-cost-id="${costId}"]`).val() || 0);
            const currency = $(`.budget-cost-currency[data-cost-id="${costId}"]`).val() || 'USD';

            // Convert MVR to USD if needed
            // MVRtoDoller field stores: 1 MVR = X USD, so multiply
            if (currency === 'MVR') {
                total += amount * mvrToUsdRate;
            } else {
                total += amount;
            }
        });

        $('#totalSelectedAmount').text(total.toFixed(2));
    }

    // Submit budget cost assignment from modal
    $(document).on('click', '#submitBudgetCostAssignment', function() {
        const $modal = $('#budgetCostModal');
        const month = $modal.data('edit-month');
        const type = $modal.data('edit-type');
        const positionId = $modal.data('position-id');
        const departmentId = $modal.data('department-id');
        const basicSalary = $('#formBasicSalary').val();
        const currentSalary = $('#formCurrentSalary').val();

        // Collect cost configurations. We iterate EVERY cost card (not just
        // checked boxes) so that unchecking + saving persists as an explicit
        // $0 override. Without this, the read endpoint's live fallback would
        // re-fill the cell from the default cost definition on next render,
        // making it look like the un-check "didn't save".
        const costConfigurations = [];
        const mvrToUsdRate = parseFloat($('#mvrToDollarRate').val() || 1/15.42);
        const dollarToMvrRate = mvrToUsdRate > 0 ? (1 / mvrToUsdRate) : 15.42; // Inverse for USD to MVR conversion

        $('.budget-cost-checkbox').each(function () {
            const $checkbox = $(this);
            const isChecked = $checkbox.is(':checked');
            const costId    = $checkbox.data('cost-id');
            const currency  = $(`.budget-cost-currency[data-cost-id="${costId}"]`).val() || 'USD';
            const hours     = $(`.budget-cost-hours[data-cost-id="${costId}"]`).val() || 0;

            let value = 0;
            if (isChecked) {
                value = parseFloat($(`.budget-cost-amount[data-cost-id="${costId}"]`).val() || 0);
                // Store in USD; convert MVR if needed.
                if (currency === 'MVR' && value > 0) {
                    value = value * mvrToUsdRate;
                }
            }

            costConfigurations.push({
                resort_budget_cost_id: costId,
                value: value,
                currency: currency,
                hours: isChecked ? hours : 0
            });
        });

        if (type === 'employee') {
            const employeeId = $modal.data('employee-id');
            saveEmployeeMonthBudget(employeeId, positionId, departmentId, month, basicSalary, currentSalary, costConfigurations);
        } else {
            const vacantIndex = $modal.data('vacant-index');
            const vacantBudgetCostId = $modal.data('vacant-budget-cost-id');
            saveVacantMonthBudget(vacantIndex, vacantBudgetCostId, positionId, departmentId, month, basicSalary, currentSalary, costConfigurations);
        }
    });

    // Save employee month budget via AJAX
    function saveEmployeeMonthBudget(employeeId, positionId, departmentId, month, basicSalary, currentSalary, costConfigurations) {
        $.ajax({
            url: "{{ route('resort.budget.hierarchy.employee.update') }}",
            method: 'POST',
            data: {
                employee_id: employeeId,
                position_id: positionId,
                department_id: departmentId,
                year: year,
                monthly_data: [{
                    month: month,
                    current_salary: basicSalary,
                    proposed_salary: currentSalary,
                    cost_configurations: costConfigurations
                }],
                _token: csrfToken
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Success');
                    $('#budgetCostModal').modal('hide');

                    // Update the specific month row immediately
                    updateEmployeeMonthRow(employeeId, month, basicSalary, currentSalary, costConfigurations);

                    // Recalculate totals for the employee table
                    recalculateEmployeeTableTotals(employeeId);

                    // Update badges hierarchically (position -> section -> department -> division)
                    updateBadgesHierarchy(positionId);

                    // The row/total-row patches above are instant client-side
                    // math, but the annual summary strip's Proposed Annual
                    // must stay the canonical server figure (never re-derived
                    // client-side) — re-fetching also refreshes Current
                    // Annual / Δ / Allowances-per-year, which otherwise stay
                    // frozen at whatever they were on first load (reported
                    // as "still showing zero after saving new costs").
                    const $employeeContainer = $(`.accordion-body[data-employee-id="${employeeId}"]`).first();
                    if ($employeeContainer.length) {
                        loadEmployeeMonthlyData(employeeId, positionId, $employeeContainer);
                    }
                } else {
                    toastr.error(response.message, 'Error');
                }
            },
            error: function(xhr) {
                toastr.error('Error saving employee budget.', 'Error');
                console.error(xhr);
            }
        });
    }

    // Update specific month row in employee table
    function updateEmployeeMonthRow(employeeId, month, basicSalary, currentSalary, costConfigurations) {
        const $row = $(`td[data-month="${month}"][data-employee-id="${employeeId}"]`).first().closest('tr');
        const mvrToUsdRate = parseFloat($('#mvrToDollarRate').val() || 1/15.42);

        // Update salary columns
        $row.find('td:eq(1)').html(formatAmountOrDash(parseFloat(basicSalary), 'USD'));
        $row.find('td:eq(2)').html(formatAmountOrDash(parseFloat(currentSalary), 'USD'));

        // Update cost configuration columns
        costConfigurations.forEach(config => {
            const $cell = $row.find(`td[data-cost-id="${config.resort_budget_cost_id}"]`);
            if ($cell.length) {
                // config.value is already in USD (converted before sending to backend)
                let valueInUSD = parseFloat(config.value);
                let originalMvrValue = valueInUSD;

                // If currency is MVR, convert USD back to MVR for data-original-value
                // This is needed so when modal opens again, it shows the MVR value
                // MVRtoDoller stores: 1 MVR = X USD, so MVR = USD / MVRtoDoller
                if (config.currency === 'MVR' && valueInUSD > 0 && mvrToUsdRate > 0) {
                    originalMvrValue = valueInUSD / mvrToUsdRate;
                }

                // Store both USD value and original MVR value as data attributes
                $cell.html(formatAmountOrDash(valueInUSD, 'USD'));
                $cell.attr('data-currency', config.currency);
                $cell.attr('data-original-value', originalMvrValue.toFixed(2));
                $cell.attr('data-usd-value', valueInUSD.toFixed(2));
            }
        });
    }

    // Recalculate employee table totals (all values should already be in USD).
    // Uses /,/g (not single replace) so totals ≥ $1,000,000 don't drop digits,
    // and reads salary cells by class anchor (.row-current-salary /
    // .row-proposed-salary) to be index-independent. Cost cells are summed
    // from data-usd-value when present (set on save), falling back to text.
    function recalculateEmployeeTableTotals(employeeId) {
        const $table = $(`td[data-employee-id="${employeeId}"]`).first().closest('table');
        if (!$table.length) return 0;
        const $totalRow = $table.find('.table-total-row');

        const numberFromCell = function ($cell) {
            // Prefer a data-usd-value attribute when present — it's the canonical
            // numeric value set on save, so we don't have to re-parse formatted text.
            const usd = $cell.attr('data-usd-value');
            if (usd !== undefined && usd !== null && usd !== '') {
                const n = parseFloat(usd);
                return isNaN(n) ? 0 : n;
            }
            const txt = ($cell.text() || '').replace(currencySymbol, '').replace(/,/g, '').trim();
            const n = parseFloat(txt);
            return isNaN(n) ? 0 : n;
        };

        let totalCurrent = 0;
        let totalProposed = 0;
        const costTotals = {};

        $table.find('tbody tr').not('.table-total-row').each(function () {
            const $row = $(this);
            totalCurrent  += numberFromCell($row.find('td').eq(1));
            totalProposed += numberFromCell($row.find('td').eq(2));

            $row.find('td[data-cost-id]').each(function () {
                const costId = $(this).data('cost-id');
                costTotals[costId] = (costTotals[costId] || 0) + numberFromCell($(this));
            });
        });

        $totalRow.find('.total-current-salary').html(formatAmountOrDash(totalCurrent, 'USD'));
        $totalRow.find('.total-proposed-salary').html(formatAmountOrDash(totalProposed, 'USD'));

        Object.keys(costTotals).forEach(function (costId) {
            const $costCell = $totalRow.find(`td[data-cost-id="${costId}"]`);
            if ($costCell.length) {
                $costCell.html(formatAmountOrDash(costTotals[costId], 'USD'));
            }
        });

        console.log('Employee table totals recalculated:', { totalCurrent, totalProposed, costTotals });

        const costTotalsSum = Object.values(costTotals).reduce((sum, val) => sum + val, 0);
        return totalCurrent + totalProposed + costTotalsSum;
    }

    // Save vacant month budget via AJAX
    // NOTE: argument names are historical — `basicSalary` is the value typed
    // into #formBasicSalary (the modal field labeled "Current Basic Salary")
    // and `currentSalary` is the value typed into #formCurrentSalary (labeled
    // "Proposed Basic Salary"). Mirror saveEmployeeMonthBudget so the request
    // shape matches: current_salary <- basicSalary, proposed_salary <- currentSalary.
    function saveVacantMonthBudget(vacantIndex, vacantBudgetCostId, positionId, departmentId, month, basicSalary, currentSalary, costConfigurations) {
        const details = $('#vacantDetailsSelect').val() || '';

        $.ajax({
            url: "{{ route('resort.budget.hierarchy.vacant.update') }}",
            method: 'POST',
            data: {
                vacant_index: vacantIndex,
                vacant_budget_cost_id: vacantBudgetCostId,
                position_id: positionId,
                department_id: departmentId,
                year: year,
                details: details,
                monthly_data: [{
                    month: month,
                    current_salary: basicSalary,
                    proposed_salary: currentSalary,
                    cost_configurations: costConfigurations
                }],
                _token: csrfToken
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Success');
                    $('#budgetCostModal').modal('hide');

                    // Update the specific month row immediately
                    updateVacantMonthRow(vacantIndex, month, basicSalary, currentSalary, costConfigurations);

                    // Recalculate totals for the vacant table
                    recalculateVacantTableTotals(vacantIndex);

                    // Update badges hierarchically (position -> section -> department -> division)
                    updateBadgesHierarchy(positionId);

                    // Refresh the annual summary strip with the canonical
                    // server figure — see saveEmployeeMonthBudget for why
                    // (it doesn't update itself from the row/total-row
                    // patches above and was staying frozen at $0 after a
                    // save on a previously-empty vacant slot).
                    const $vacantContainer = $(`.accordion-body[data-vacant-index="${vacantIndex}"]`).first();
                    if ($vacantContainer.length) {
                        loadVacantMonthlyData(vacantIndex, positionId, $vacantContainer);
                    }
                } else {
                    toastr.error(response.message, 'Error');
                }
            },
            error: function(xhr) {
                toastr.error('Error saving vacant budget.', 'Error');
                console.error(xhr);
            }
        });
    }

    // Update specific month row in vacant table
    function updateVacantMonthRow(vacantIndex, month, basicSalary, currentSalary, costConfigurations) {
        const $row = $(`td[data-month="${month}"][data-vacant-index="${vacantIndex}"]`).first().closest('tr');
        const mvrToUsdRate = parseFloat($('#mvrToDollarRate').val() || 1/15.42);

        // Update salary columns
        $row.find('td:eq(1)').html(formatAmountOrDash(parseFloat(basicSalary), 'USD'));
        $row.find('td:eq(2)').html(formatAmountOrDash(parseFloat(currentSalary), 'USD'));

        // Update cost configuration columns
        costConfigurations.forEach(config => {
            const $cell = $row.find(`td[data-cost-id="${config.resort_budget_cost_id}"]`);
            if ($cell.length) {
                // config.value is already in USD (converted before sending to backend)
                let valueInUSD = parseFloat(config.value);
                let originalMvrValue = valueInUSD;

                // If currency is MVR, convert USD back to MVR for data-original-value
                // This is needed so when modal opens again, it shows the MVR value
                // MVRtoDoller stores: 1 MVR = X USD, so MVR = USD / MVRtoDoller
                if (config.currency === 'MVR' && valueInUSD > 0 && mvrToUsdRate > 0) {
                    originalMvrValue = valueInUSD / mvrToUsdRate;
                }

                // Store both USD value and original MVR value as data attributes
                $cell.html(formatAmountOrDash(valueInUSD, 'USD'));
                $cell.attr('data-currency', config.currency);
                $cell.attr('data-original-value', originalMvrValue.toFixed(2));
                $cell.attr('data-usd-value', valueInUSD.toFixed(2));
            }
        });
    }

    // Recalculate vacant table totals
    function recalculateVacantTableTotals(vacantIndex) {
        const $table = $(`td[data-vacant-index="${vacantIndex}"]`).first().closest('table');
        if (!$table.length) return 0;
        const $totalRow = $table.find('.table-total-row');

        const numberFromCell = function ($cell) {
            const usd = $cell.attr('data-usd-value');
            if (usd !== undefined && usd !== null && usd !== '') {
                const n = parseFloat(usd);
                return isNaN(n) ? 0 : n;
            }
            const txt = ($cell.text() || '').replace(currencySymbol, '').replace(/,/g, '').trim();
            const n = parseFloat(txt);
            return isNaN(n) ? 0 : n;
        };

        let totalCurrent = 0;
        let totalProposed = 0;
        const costTotals = {};

        $table.find('tbody tr').not('.table-total-row').each(function () {
            const $row = $(this);
            totalCurrent  += numberFromCell($row.find('td').eq(1));
            totalProposed += numberFromCell($row.find('td').eq(2));

            $row.find('td[data-cost-id]').each(function () {
                const costId = $(this).data('cost-id');
                costTotals[costId] = (costTotals[costId] || 0) + numberFromCell($(this));
            });
        });

        $totalRow.find('.total-current-salary').html(formatAmountOrDash(totalCurrent, 'USD'));
        $totalRow.find('.total-proposed-salary').html(formatAmountOrDash(totalProposed, 'USD'));

        Object.keys(costTotals).forEach(function (costId) {
            const $costCell = $totalRow.find(`td[data-cost-id="${costId}"]`);
            if ($costCell.length) {
                $costCell.html(formatAmountOrDash(costTotals[costId], 'USD'));
            }
        });

        console.log('Vacant table totals recalculated:', { totalCurrent, totalProposed, costTotals });

        const costTotalsSum = Object.values(costTotals).reduce((sum, val) => sum + val, 0);
        return totalCurrent + totalProposed + costTotalsSum;
    }

    // Update badges hierarchically from position up to division
    function updateBadgesHierarchy(positionId) {
        console.log('Updating badges for position:', positionId);

        // Find the position accordion - try multiple methods
        let $positionAccordion = $(`.position-accordion[data-position-id="${positionId}"]`);

        // If not found, try finding by accordion-body
        if (!$positionAccordion.length) {
            $positionAccordion = $(`.accordion-body[data-position-id="${positionId}"]`).closest('.position-accordion');
        }

        // If still not found, try finding through employee/vacant accordions
        if (!$positionAccordion.length) {
            $positionAccordion = $(`.accordion-body[data-position-id="${positionId}"][data-type]`).closest('.position-accordion');
        }

        if ($positionAccordion.length) {
            console.log('Found position accordion');

            // Calculate position total from all tables
            const positionTotal = calculatePositionTotal($positionAccordion);
            console.log('Position total:', positionTotal);

            // Update position badge
            const $positionBadge = $positionAccordion.find('.positionGrandTotal');
            if ($positionBadge.length) {
                $positionBadge.text('Budget: ' + formatAmount(positionTotal, 'USD'));
                console.log('Updated position badge to:', positionTotal);
            }

            // Find and update parent section (if exists)
            const $sectionAccordion = $positionAccordion.closest('.section-accordion');
            if ($sectionAccordion.length) {
                console.log('Found section accordion');
                const sectionTotal = calculateSectionTotal($sectionAccordion);
                console.log('Section total:', sectionTotal);

                const $sectionBadge = $sectionAccordion.find('.sectionGrandTotal');
                if ($sectionBadge.length) {
                    $sectionBadge.text('Budget: ' + formatAmount(sectionTotal, 'USD'));
                    console.log('Updated section badge to:', sectionTotal);
                }
            }

            // Find and update parent department
            const $deptAccordion = $positionAccordion.closest('.department-accordion');
            if ($deptAccordion.length) {
                console.log('Found department accordion');
                const deptTotal = calculateDepartmentTotal($deptAccordion);
                console.log('Department total:', deptTotal);

                const $deptBadge = $deptAccordion.find('.departmentGrandTotal');
                if ($deptBadge.length) {
                    $deptBadge.text('Budget: ' + formatAmount(deptTotal, 'USD'));
                    console.log('Updated department badge to:', deptTotal);
                }
            }

            // Find and update parent division
            const $divisionAccordion = $positionAccordion.closest('.division-accordion');
            if ($divisionAccordion.length) {
                console.log('Found division accordion');
                const divisionTotal = calculateDivisionTotal($divisionAccordion);
                console.log('Division total:', divisionTotal);

                const $divisionBadge = $divisionAccordion.find('.divisionGrandTotal');
                if ($divisionBadge.length) {
                    $divisionBadge.text('Budget: ' + formatAmount(divisionTotal, 'USD'));
                    console.log('Updated division badge to:', divisionTotal);
                }
            }
        } else {
            console.error('Position accordion not found for position ID:', positionId);
        }
    }

    // Calculate position total from all employee and vacant tables
    function calculatePositionTotal($positionElement) {
        let total = 0;

        // If this position's data hasn't been loaded yet (no rendered total row),
        // fall back to the server-rendered badge value. Without this, a parent
        // (section/department/division) recompute would read every unexpanded
        // position as 0 and collapse the correct totals — the root of the bug
        // where expanding/editing one position zeroed the others.
        const $loadedTotalRows = $positionElement.find('.accordion-body[data-position-id] .budget-monthly-table .table-total-row');
        if (!$loadedTotalRows.length) {
            const badgeTxt = $positionElement.find('.positionGrandTotal').first().text().replace(/[^0-9.\-]/g, '');
            const badgeVal = parseFloat(badgeTxt);
            return isNaN(badgeVal) ? 0 : badgeVal;
        }

        // Find the accordion body that contains the tables
        const $positionBody = $positionElement.find('.accordion-body[data-position-id]').first();

        if ($positionBody.length) {
            // Get all budget monthly tables (both employee and vacant)
            $positionBody.find('.budget-monthly-table').each(function() {
                const $table = $(this);
                const $totalRow = $table.find('.table-total-row');

                if ($totalRow.length) {
                    // Get the current basic salary total (column 2, index 1)
                    const currentSalaryTotal = parseFloat($totalRow.find('.total-current-salary').text().replace(currencySymbol, '').replace(/,/g, '').trim() || 0);

                    // Get the proposed basic salary total (column 3, index 2)
                    const proposedSalaryTotal = parseFloat($totalRow.find('.total-proposed-salary').text().replace(currencySymbol, '').replace(/,/g, '').trim() || 0);

                    // Sum all cost configuration totals using data-cost-id attribute
                    let costConfigTotal = 0;
                    $totalRow.find('td[data-cost-id]').each(function() {
                        const costValue = parseFloat($(this).text().replace(currencySymbol, '').replace(/,/g, '').trim() || 0);
                        if (!isNaN(costValue)) {
                            costConfigTotal += costValue;
                        }
                    });

                    // Salary basis: Proposed wins when entered (> 0); otherwise Current
                    const effectiveSalaryTotal = proposedSalaryTotal > 0 ? proposedSalaryTotal : currentSalaryTotal;
                    const tableTotal = effectiveSalaryTotal + costConfigTotal;
                    total += tableTotal;

                    console.log('Table total:', {
                        currentSalary: currentSalaryTotal,
                        proposedSalary: proposedSalaryTotal,
                        effectiveSalary: effectiveSalaryTotal,
                        costConfigs: costConfigTotal,
                        tableTotal: tableTotal
                    });
                }
            });
        }

        console.log('Position total calculated (including current & proposed salary):', total);
        return total;
    }

    // Calculate section total from all positions
    function calculateSectionTotal($sectionElement) {
        let total = 0;

        // Find all positions within this section
        $sectionElement.find('.position-accordion').each(function() {
            total += calculatePositionTotal($(this));
        });

        return total;
    }

    // Calculate department total from all sections and direct positions
    function calculateDepartmentTotal($deptElement) {
        let total = 0;

        // Sum all sections
        $deptElement.find('.section-accordion').each(function() {
            total += calculateSectionTotal($(this));
        });

        // Sum direct positions (not in sections)
        const $deptBody = $deptElement.find('> .accordion-item > [id^="collapseDept"]').first();
        if ($deptBody.length) {
            $deptBody.find('> .accordion-body > .position-accordion, > .accordion-body > .ms-3 > .position-accordion').each(function() {
                // Make sure this position is not inside a section
                if ($(this).closest('.section-accordion').length === 0) {
                    total += calculatePositionTotal($(this));
                }
            });
        }

        return total;
    }

    // Calculate division total from all departments
    function calculateDivisionTotal($divisionElement) {
        let total = 0;

        $divisionElement.find('.department-accordion').each(function() {
            total += calculateDepartmentTotal($(this));
        });

        return total;
    }

    // Event listeners for modal cost configuration changes
    $(document).on('change', '.budget-cost-checkbox, .budget-cost-amount, .budget-cost-currency', function() {
        if (typeof window.updateModalTotal === 'function') {
            window.updateModalTotal();
        }
    });

    // Salary-based recalculation (Pension % AND Overtime) is owned by
    // recalcBudgetModalSalaryBased() in budget_cost_modal.blade.php, which is
    // bound to both salary inputs and uses the effective salary (Proposed
    // when entered, else Current). The old handler here recalculated pension
    // only — and never overtime — so overtime stayed on the current salary.

    // ==================================================================
    // Drill-Down Navigation (Phase 1/2) — purely additive on top of the
    // existing accordion. Every click here resolves to an EXISTING
    // collapse element and calls Bootstrap's own Collapse.show() on it,
    // so the real shown.bs.collapse listeners above (unmodified) fire and
    // load data exactly as they already do. This layer never fetches
    // data itself — it only marks which accordion is "on path" (for the
    // CSS flattening rules) and renders a prettier read-out of whatever
    // the existing accordion just rendered into the DOM.
    // ==================================================================
    var wbPath = []; // [{level, id, label, code, iter, domId, positionId}]
    var wbAwaitingRender = false;

    function wbEsc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }

    function wbRenderBreadcrumb() {
        var $bc = $('#wbBreadcrumb');
        $bc.empty();
        var $root = $('<button type="button" data-wb-idx="-1">All Divisions</button>');
        if (wbPath.length === 0) $root.addClass('wb-crumb-current');
        $bc.append($root);
        wbPath.forEach(function (p, idx) {
            $bc.append('<span class="wb-crumb-sep">/</span>');
            var $btn = $('<button type="button" data-wb-idx="' + idx + '">' + wbEsc(p.label) + '</button>');
            if (idx === wbPath.length - 1) $btn.addClass('wb-crumb-current');
            $bc.append($btn);
        });
        $('#wbBackBtn').prop('disabled', wbPath.length === 0);
    }

    function wbFindLevelElement(p) {
        if (p.level === 'division')   return $('#collapseDiv' + p.iter).closest('.division-accordion');
        if (p.level === 'department') return $('[data-department-id="' + p.id + '"]').first().closest('.department-accordion');
        if (p.level === 'section')    return $('#' + p.domId).closest('.section-accordion');
        if (p.level === 'position')   return $('.position-accordion[data-position-id="' + p.id + '"]').first();
        if (p.level === 'employee')   return $('[data-employee-id="' + p.id + '"]').first().closest('.employee-accordion');
        if (p.level === 'vacant')     return $('[data-vacant-index="' + p.id + '"][data-position-id="' + p.positionId + '"]').first().closest('.vacant-accordion');
        return $();
    }

    function wbApplyFocus() {
        $('#accordionViewBudget .wb-on-path').removeClass('wb-on-path wb-flatten');
        var deepest = wbPath.length ? wbPath[wbPath.length - 1] : null;
        var isLeaf = deepest && (deepest.level === 'employee' || deepest.level === 'vacant');
        if (!isLeaf) wbHideDetailHeader();

        if (wbPath.length === 0) {
            $('#accordionViewBudget').removeClass('wb-focus-active');
            wbUpdateReviseBudgetButton();
            return;
        }
        $('#accordionViewBudget').addClass('wb-focus-active');
        wbPath.forEach(function (p) {
            var $el = wbFindLevelElement(p);
            if (!$el || !$el.length) return;
            $el.addClass('wb-on-path');
            // Every level's own accordion chrome (header, budget badge,
            // Revise Budget button) is always hidden — the nav card's
            // breadcrumb/row list and, for the deepest employee/vacant
            // level, the dedicated #wbDetailHeader title bar (see
            // wbShowEmployeeDetailHeader/wbShowVacantDetailHeader) already
            // show that same information in the current design's own
            // style, so leaving the original header visible read as a
            // leftover of the old always-expanded accordion underneath.
            $el.addClass('wb-flatten');
        });
        wbUpdateReviseBudgetButton();
    }

    // Revise Budget lives inside the department's own accordion header,
    // which is now always hidden (wb-flatten) once the nav card takes
    // over. Rather than reimplement the trigger, surface a button in the
    // nav card that — when a department is in the current path — just
    // clicks the real, existing trigger for that department (still
    // present in the DOM, only visually hidden). This reuses the exact
    // same modal population / locked-state / save logic untouched.
    function wbUpdateReviseBudgetButton() {
        var $btn = $('#wbReviseBudgetBtn');
        var deptEntry = wbPath.filter(function (p) { return p.level === 'department'; })[0];
        if (!deptEntry) { $btn.addClass('d-none'); return; }

        var $deptEl = wbFindLevelElement(deptEntry);
        var $realTrigger = $deptEl.find('.revisebudgetmodal').first();
        if ($realTrigger.length) {
            $btn.removeClass('d-none').prop('disabled', false).attr('title', '')
                .off('click.wbRevise').on('click.wbRevise', function () {
                    $realTrigger.get(0).click();
                });
            return;
        }

        // No live anchor — either GM-locked (disabled button present) or
        // this rank doesn't get a trigger at all (not rendered server-side).
        var $lockedBtn = $deptEl.find('button:contains("Revise Budget")').first();
        if ($lockedBtn.length) {
            $btn.removeClass('d-none').prop('disabled', true).attr('title', $lockedBtn.attr('title') || '');
            return;
        }

        $btn.addClass('d-none');
    }

    // Show an existing Bootstrap collapse element via its own API — this
    // is what fires the existing shown.bs.collapse listeners that load
    // AJAX data. No duplicate fetch logic.
    function wbShowCollapse($collapseEl) {
        if (!$collapseEl || !$collapseEl.length) return;
        var el = $collapseEl.get(0);
        if (window.bootstrap && bootstrap.Collapse) {
            bootstrap.Collapse.getOrCreateInstance(el, { toggle: false }).show();
        } else {
            $collapseEl.collapse('show');
        }
    }

    // Poll briefly for the loading spinner inside a container to be
    // replaced by real content, then run the callback. Existing AJAX
    // success handlers replace $container.html(...) synchronously once
    // their request resolves — this just waits for that to happen rather
    // than assuming a fixed delay. Checks only a DIRECT child ".text-center"
    // (the exact loading-placeholder wrapper both the department and
    // position AJAX containers start with) rather than searching the whole
    // subtree — a deep search would also match a *deeper*, not-yet-expanded
    // level's own still-unresolved placeholder and wait for the wrong thing.
    function wbWaitForContent($container, callback, attempts) {
        attempts = attempts || 0;
        if (!$container || !$container.length) { callback(); return; }
        var stillLoading = $container.children('.text-center').find('.spinner-border').length > 0;
        if (!stillLoading || attempts > 40) { callback(); return; }
        setTimeout(function () { wbWaitForContent($container, callback, attempts + 1); }, 100);
    }

    function wbBudgetFor(id, kind) {
        // Reads the already-computed value from window.budgetTotals
        // (populated by the existing eager loadAllBudgetTotalsOnPageLoad
        // chain) rather than recomputing anything.
        if (kind === 'position') {
            var p = window.budgetTotals.positions[id];
            return p && typeof p.total !== 'undefined' ? p.total : null;
        }
        if (kind === 'department') return window.budgetTotals.departments[id] || null;
        return null;
    }

    // ---- Level renderers: build the pretty row list from whatever the
    // existing accordion already rendered into the DOM for the current
    // level. Falls back gracefully if a section/count field isn't
    // available yet (badge totals load asynchronously).
    function wbRenderRoot() {
        var rows = [];
        $('.division-accordion').each(function () {
            var $div = $(this);
            var iter = $div.find('.accordion-header[id^="headingDiv"]').attr('id').replace('headingDiv', '');
            var name = $div.find('> h2 h3').first().text().trim();
            var deptCount = $div.find('.department-accordion').length;
            var budgetTxt = $div.find('.divisionGrandTotal').first().text().trim();
            rows.push({
                level: 'division', iter: iter, label: name,
                meta: deptCount + (deptCount === 1 ? ' department' : ' departments'),
                budget: budgetTxt
            });
        });
        wbRenderLevelList(rows, 'division');
    }

    function wbRenderDepartments($divisionEl) {
        var rows = [];
        // Iterate the department wrapper elements directly (one per real
        // department) rather than every [data-department-id] match — that
        // attribute also appears on nested Action-button data-* attributes
        // once a department's employees/costs are loaded, which would
        // otherwise require a de-dupe pass.
        $divisionEl.find('.department-accordion').each(function () {
            var $deptAccordion = $(this);
            var deptId = $deptAccordion.find('[data-department-id]').first().data('department-id');
            var name = $deptAccordion.find('.accordion-header[id^="headingDept"] span').first().text().trim();
            var budgetTxt = $deptAccordion.find('.departmentGrandTotal').first().text().trim();
            rows.push({ level: 'department', id: deptId, label: name, budget: budgetTxt, meta: '' });
        });
        wbRenderLevelList(rows, 'department');
    }

    function wbRenderSectionsAndPositions($deptBodyEl) {
        var rows = [];
        $deptBodyEl.children('.section-accordion').each(function () {
            var $sec = $(this);
            var posCount = $sec.find('.position-accordion').length;
            if (posCount === 0) return; // auto-skip empty sections, per spec
            var domId = $sec.attr('id');
            var name = $sec.find('.accordion-header span').first().text().trim();
            var budgetTxt = $sec.find('.sectionGrandTotal').first().text().trim();
            rows.push({
                level: 'section', domId: domId, label: name,
                meta: posCount + (posCount === 1 ? ' position' : ' positions'),
                budget: budgetTxt
            });
        });
        $deptBodyEl.children('.position-accordion').each(function () {
            wbPushPositionRow($(this), rows);
        });
        wbRenderLevelList(rows, 'section-or-position');
    }

    function wbRenderPositionsInSection($sectionEl) {
        var rows = [];
        $sectionEl.find('.position-accordion').each(function () {
            wbPushPositionRow($(this), rows);
        });
        wbRenderLevelList(rows, 'position');
    }

    function wbPushPositionRow($posEl, rows) {
        var posId = $posEl.data('position-id');
        var title = $posEl.find('.accordion-header span').first().text().trim();
        var budgetTxt = $posEl.find('.positionGrandTotal').first().text().trim();
        rows.push({ level: 'position', id: posId, label: title, budget: budgetTxt, meta: '' });
    }

    function wbRenderEmployeesAndVacant($positionBodyEl, positionId) {
        var rows = [];
        $positionBodyEl.find('.employee-accordion').each(function () {
            var $emp = $(this);
            var body = $emp.find('.accordion-body[data-employee-id]').first();
            var empId = body.data('employee-id');
            var name = $emp.find('.accordion-header span').first().text().trim();
            var rankName = $emp.find('.badge.bg-secondary').first().text().trim();
            var nationality = $emp.find('.badge.bg-info').first().text().trim();
            var picture = $emp.attr('data-employee-picture') || '';
            rows.push({
                level: 'employee', id: empId, label: name,
                role: rankName, nationality: nationality, picture: picture
            });
        });
        $positionBodyEl.find('.vacant-accordion').each(function () {
            var $vac = $(this);
            var body = $vac.find('.accordion-body[data-vacant-index]').first();
            var vIdx = body.data('vacant-index');
            rows.push({ level: 'vacant', id: vIdx, positionId: positionId, label: 'Vacant ' + vIdx });
        });
        wbRenderLevelList(rows, 'leaf');
    }

    function wbRenderLevelList(rows, kind) {
        var $list = $('#wbLevelList');
        $list.empty();
        if (!rows.length) {
            $list.append('<p class="text-muted small mb-0">Nothing here yet.</p>');
            return;
        }
        rows.forEach(function (row) {
            if (row.level === 'employee' || row.level === 'vacant') {
                $list.append(wbLeafRowHtml(row));
            } else {
                $list.append(wbGroupRowHtml(row));
            }
        });
    }

    function wbGroupRowHtml(row) {
        var levelTagText = { division: 'Division', department: 'Department', section: 'Section', position: 'Position' }[row.level] || row.level;
        var $row = $('<div class="wb-group-row"></div>');
        $row.attr('data-wb-level', row.level);
        if (row.id !== undefined) $row.attr('data-wb-id', row.id);
        if (row.iter !== undefined) $row.attr('data-wb-iter', row.iter);
        if (row.domId !== undefined) $row.attr('data-wb-domid', row.domId);
        $row.html(
            '<div class="wb-group-row-main">' +
                '<span class="wb-level-tag">' + wbEsc(levelTagText) + '</span>' +
                '<span class="wb-group-row-name">' + wbEsc(row.label) + '</span>' +
                (row.meta ? '<div class="wb-group-row-meta">' + wbEsc(row.meta) + '</div>' : '') +
            '</div>' +
            '<div class="wb-group-row-budget">' + wbEsc(row.budget || '') + '</div>' +
            '<div class="wb-group-row-chevron"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg></div>'
        );
        return $row;
    }

    // Shared by the nav card's leaf rows AND the detail-header title bar
    // (see wbShowEmployeeDetailHeader/wbShowVacantDetailHeader below) — one
    // implementation so the two never visually drift apart.
    function wbLeafRowInnerHtml(row) {
        if (row.level === 'vacant') {
            return '<div class="wb-leaf-row wb-group-row-main">' +
                    '<span class="wb-leaf-avatar wb-leaf-avatar-vacant"><i class="fa fa-user-slash"></i></span>' +
                    '<div><span class="wb-group-row-name">' + wbEsc(row.label) + '</span>' +
                    '<div class="wb-group-row-meta"><span class="wb-vacant-pill">Vacant</span> Unfilled — no cost</div></div>' +
                '</div>';
        }
        return '<div class="wb-leaf-row wb-group-row-main">' +
                wbAvatarHtml(row.label, row.picture) +
                '<div><span class="wb-group-row-name">' + wbEsc(row.label) + '</span>' +
                '<div class="wb-group-row-meta">' + wbEsc(row.role || '') + (row.nationality ? ' · ' + wbEsc(row.nationality) : '') + '</div></div>' +
            '</div>';
    }

    function wbLeafRowHtml(row) {
        var $row = $('<div class="wb-group-row wb-leaf-row-wrap"></div>');
        $row.attr('data-wb-level', row.level);
        $row.attr('data-wb-id', row.id);
        if (row.positionId !== undefined) $row.attr('data-wb-position-id', row.positionId);
        $row.html(wbLeafRowInnerHtml(row) + '<div class="wb-group-row-chevron"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg></div>');
        return $row;
    }

    // Detail-header title bar — shown full width above the selected
    // employee/vacant's table, replacing the original accordion header
    // (now fully hidden, see wbApplyFocus()). Reads straight from the
    // employee/vacant-accordion element's own data-* attributes rather
    // than requiring "a row the user just clicked" to exist, so it works
    // identically whether reached via the nav card list or a search
    // result.
    function wbShowEmployeeDetailHeader(employeeId) {
        var $emp = $('[data-employee-id="' + employeeId + '"]').first().closest('.employee-accordion');
        if (!$emp.length) { wbHideDetailHeader(); return; }
        var row = {
            level: 'employee',
            label: $emp.find('.accordion-header span').first().text().trim(),
            role: $emp.attr('data-employee-rank-name') || $emp.find('.badge.bg-secondary').first().text().trim(),
            nationality: $emp.find('.badge.bg-info').first().text().trim(),
            picture: $emp.attr('data-employee-picture') || ''
        };
        $('#wbDetailHeader').html(wbLeafRowInnerHtml(row)).removeClass('d-none');
    }

    function wbShowVacantDetailHeader(vacantIndex) {
        $('#wbDetailHeader').html(wbLeafRowInnerHtml({ level: 'vacant', label: 'Vacant ' + vacantIndex })).removeClass('d-none');
    }

    function wbHideDetailHeader() {
        $('#wbDetailHeader').addClass('d-none').empty();
    }

    // Round photo, falling back to round initials — same pattern already
    // used on the Workforce Planning dashboard's Compliance Tracking card
    // this session (compare against the resort's configured default
    // placeholder picture to detect "no real photo").
    window.wbAvatarHtml = function (name, pictureUrl) {
        name = (name || '').trim();
        var isDefault = !pictureUrl || pictureUrl === window.wbDefaultPictureUrl;
        if (isDefault) {
            var parts = name.split(' ').filter(Boolean);
            var initials = parts.slice(0, 2).map(function (p) { return p.charAt(0).toUpperCase(); }).join('');
            return '<span class="wb-leaf-avatar">' + (initials || '?') + '</span>';
        }
        return '<img class="wb-leaf-avatar" src="' + wbEsc(pictureUrl) + '" alt="">';
    };
    function wbAvatarHtml(name, pictureUrl) { return window.wbAvatarHtml(name, pictureUrl); }

    // ---- Cost-group classifier (Phase 4) ----
    // resortCosts is NOT a fixed column list — it's fully per-resort
    // configurable (confirmed by direct DB query: 23 active items for one
    // resort, a completely different 17 for another). Bucketing is
    // therefore keyword-substring matching against the cost's own label,
    // never a hardcoded name whitelist. Order matters: narrower/more
    // specific groups are checked before the broader "Allowances" catch-all
    // so e.g. "Relocation / Luggage Allowance" lands in Travel & Tickets
    // (it matches "relocation") rather than Allowances (it also contains
    // the word "allowance").
    var WB_COST_GROUP_ORDER = ['overtime', 'insurance', 'travel', 'allowances'];
    var WB_COST_GROUP_LABELS = {
        overtime: 'Overtime',
        insurance: 'Insurance & Permits',
        travel: 'Travel & Tickets',
        allowances: 'Salary & Allowances',
        other: 'Other'
    };
    var WB_COST_GROUP_COLORS = {
        allowances: 'var(--teal)', // brand teal per explicit user decision (spec's own #2F6BFF conflicted with its "no blue" rule)
        overtime: '#7D4B17', // muted copper — darkened from #B8722E for CVD lightness separation from travel
        travel: '#C1666B', // dusty terracotta
        insurance: '#7C5C9C', // muted plum — was #1F9D6B, collided with --positive/--wb-increase
        other: 'var(--muted)'
    };
    var WB_COST_GROUP_KEYWORDS = {
        overtime: ['overtime', 'ovetime'],
        insurance: ['insurance', 'medical', 'permit', 'visa'],
        travel: ['ticket', 'relocation', 'luggage', 'seaplane', 'boat', 'arrival', 'accomodation', 'accommodation', 'transfer', 'overnight'],
        allowances: ['allowance', 'bonus', 'ramadan', 'fire brigade', 'food cost', 'r and r', 'rnr', 'male', 'airport']
    };
    // Returns a CSS-safe slug ('allowances', 'overtime', 'travel',
    // 'insurance', or the 'other' fallback) — group labels like
    // "Travel & Tickets" aren't valid class/attribute-value characters.
    window.wbClassifyCost = function (cost) {
        var label = ((cost && (cost.particulars || cost.cost_title)) || '').toLowerCase();
        for (var i = 0; i < WB_COST_GROUP_ORDER.length; i++) {
            var slug = WB_COST_GROUP_ORDER[i];
            var keywords = WB_COST_GROUP_KEYWORDS[slug];
            for (var j = 0; j < keywords.length; j++) {
                if (label.indexOf(keywords[j]) !== -1) return slug;
            }
        }
        return 'other';
    };

    // ---- Zero-as-dash (Phase 6) ----
    // Wraps the existing, unmodified global formatAmount() — only adds a
    // muted-dash special case for 0/NaN. Must be applied at every
    // money-rendering call site (initial build, row update, totals
    // recompute) or values revert to "$0.00" after any edit/copy-down.
    window.formatAmountOrDash = function (amount, currency) {
        var num = parseFloat(amount);
        if (!isFinite(num) || num === 0) {
            return '<span class="wb-zero-dash">—</span>';
        }
        return formatAmount(num, currency);
    };

    // ---- Annual summary strip (Phase 2) ----
    // Proposed Annual reuses response.annual_total_usd verbatim — the
    // canonical figure Common::annualBudgetForEmployee/VacantSlot compute,
    // deliberately kept in sync with the Consolidated Budget page. Never
    // re-derive it. Current Annual / Allowances-per-year are built only
    // from totals this same table already computed for its own TOTAL row —
    // a display-only regrouping, not a new calculation.
    function wbAnnualSummaryHtml(response, totals, resortCosts) {
        var proposedAnnual = parseFloat(response.annual_total_usd);
        if (!isFinite(proposedAnnual)) proposedAnnual = 0;
        var costsSum = Object.keys(totals.costs).reduce(function (s, id) { return s + (totals.costs[id] || 0); }, 0);
        var currentAnnual = totals.currentSalary + costsSum;
        // Round to cents before comparing/displaying — float subtraction of
        // two summed totals can land on a tiny non-zero remainder (e.g.
        // -0.0000001), which a truly-equal pair would otherwise render as
        // the confusing "$-0.00" instead of "$0.00".
        var delta = Math.round((proposedAnnual - currentAnnual) * 100) / 100;
        var deltaClass = delta > 0 ? 'wb-delta-up' : (delta < 0 ? 'wb-delta-down' : '');
        var deltaSign = delta > 0 ? '+' : '';
        var allowancesAnnual = 0;
        resortCosts.forEach(function (cost) {
            if (window.wbClassifyCost(cost) === 'allowances') {
                allowancesAnnual += (totals.costs[cost.id] || 0);
            }
        });
        return '' +
            '<div class="wb-annual-summary">' +
                '<div class="wb-annual-stat"><span class="wb-annual-stat-label">Current Annual</span>' +
                    '<span class="wb-annual-stat-value">' + formatAmount(currentAnnual, 'USD') + '</span></div>' +
                '<div class="wb-annual-stat"><span class="wb-annual-stat-label">Proposed Annual</span>' +
                    '<span class="wb-annual-stat-value">' + formatAmount(proposedAnnual, 'USD') + '</span></div>' +
                '<div class="wb-annual-stat"><span class="wb-annual-stat-label">Δ Annual</span>' +
                    '<span class="wb-annual-stat-value wb-annual-stat-delta ' + deltaClass + '">' + deltaSign + formatAmount(delta, 'USD') + '</span></div>' +
                '<div class="wb-annual-stat"><span class="wb-annual-stat-label">Allowances / Year</span>' +
                    '<span class="wb-annual-stat-value">' + formatAmount(allowancesAnnual, 'USD') + '</span></div>' +
            '</div>';
    }

    // ---- Group toggle chips (Phase 5) ----
    // Default: only Allowances visible. "At least one group visible" is
    // enforced in the click handler below (clicking the last active chip
    // is a no-op with a brief shake, not a silently-blocked click).
    function wbGroupChipsHtml(resortCosts) {
        var present = {};
        resortCosts.forEach(function (cost) { present[window.wbClassifyCost(cost)] = true; });
        var order = ['allowances', 'overtime', 'travel', 'insurance', 'other'];
        var html = '<div class="wb-group-toggles">';
        order.forEach(function (slug) {
            if (!present[slug]) return;
            var active = slug === 'allowances';
            html += '<button type="button" class="wb-group-chip' + (active ? ' wb-chip-active' : '') + '" data-cost-group-toggle="' + slug + '">' +
                '<span class="wb-group-dot" style="background:' + WB_COST_GROUP_COLORS[slug] + '"></span>' +
                WB_COST_GROUP_LABELS[slug] +
                '</button>';
        });
        html += '</div>';
        return html;
    }

    // Toggling reads/writes column visibility purely via a data attribute
    // on the table wrapper + a CSS rule (added below) — the underlying
    // <td>/<th> data-cost-id and totals-recalculation logic never sees this.
    $(document).on('click', '.wb-group-chip', function () {
        var $chip = $(this);
        var $toggles = $chip.closest('.wb-group-toggles');
        var activeCount = $toggles.find('.wb-chip-active').length;
        var isActive = $chip.hasClass('wb-chip-active');
        if (isActive && activeCount <= 1) {
            $chip.addClass('wb-chip-shake');
            setTimeout(function () { $chip.removeClass('wb-chip-shake'); }, 300);
            return; // at least one group must always stay visible
        }
        $chip.toggleClass('wb-chip-active');
        var $scroll = $toggles.closest('.wb-detail-card-inner').find('.wb-table-scroll');
        var hidden = [];
        $toggles.find('.wb-group-chip').each(function () {
            if (!$(this).hasClass('wb-chip-active')) hidden.push($(this).data('cost-group-toggle'));
        });
        $scroll.attr('data-hidden-groups', ' ' + hidden.join(' ') + ' ');
    });

    // ---- Drill-in on row click ----
    $(document).on('click', '.wb-group-row[data-wb-level="division"]', function () {
        var iter = $(this).data('wb-iter');
        var label = $(this).find('.wb-group-row-name').text();
        wbPath = [{ level: 'division', iter: iter, label: label }];
        var $collapse = $('#collapseDiv' + iter);
        wbShowCollapse($collapse);
        wbApplyFocus(); wbRenderBreadcrumb();
        // Departments are server-rendered inside the division body already
        // (only Section/Position/Employee/Vacant are AJAX-loaded) — no wait.
        wbRenderDepartments($collapse.closest('.division-accordion'));
    });

    $(document).on('click', '.wb-group-row[data-wb-level="department"]', function () {
        var deptId = $(this).data('wb-id');
        var label = $(this).find('.wb-group-row-name').text();
        wbPath.push({ level: 'department', id: deptId, label: label });
        var $body = $('[data-department-id="' + deptId + '"]').first();
        var $collapse = $body.closest('.collapse');
        wbShowCollapse($collapse);
        wbApplyFocus(); wbRenderBreadcrumb();
        wbWaitForContent($body, function () {
            wbRenderSectionsAndPositions($body);
        });
    });

    $(document).on('click', '.wb-group-row[data-wb-level="section"]', function () {
        var domId = $(this).data('wb-domid');
        var label = $(this).find('.wb-group-row-name').text();
        wbPath.push({ level: 'section', domId: domId, label: label });
        var $collapse = $('#collapse' + domId);
        wbShowCollapse($collapse);
        wbApplyFocus(); wbRenderBreadcrumb();
        // Positions within a section are already rendered client-side as
        // part of the parent department's single AJAX response — no wait.
        wbRenderPositionsInSection($('#' + domId).closest('.section-accordion'));
    });

    $(document).on('click', '.wb-group-row[data-wb-level="position"]', function () {
        var posId = $(this).data('wb-id');
        var label = $(this).find('.wb-group-row-name').text();
        wbPath.push({ level: 'position', id: posId, label: label });
        var $posEl = $('.position-accordion[data-position-id="' + posId + '"]').first();
        var $collapse = $posEl.find('> .accordion-item > .collapse');
        wbShowCollapse($collapse);
        wbApplyFocus(); wbRenderBreadcrumb();
        wbWaitForContent($collapse.find('.accordion-body').first(), function () {
            wbRenderEmployeesAndVacant($collapse.find('.accordion-body').first(), posId);
        });
    });

    $(document).on('click', '.wb-leaf-row-wrap[data-wb-level="employee"]', function () {
        var empId = $(this).data('wb-id');
        var label = $(this).find('.wb-group-row-name').text();
        wbPath.push({ level: 'employee', id: empId, label: label });
        var $collapse = $('[data-employee-id="' + empId + '"]').first().closest('.collapse');
        wbShowCollapse($collapse);
        wbApplyFocus(); wbRenderBreadcrumb();
        wbShowEmployeeDetailHeader(empId);
        $('#wbLevelList').empty();
    });

    $(document).on('click', '.wb-leaf-row-wrap[data-wb-level="vacant"]', function () {
        var vIdx = $(this).data('wb-id');
        var posId = $(this).data('wb-position-id');
        var label = $(this).find('.wb-group-row-name').text();
        wbPath.push({ level: 'vacant', id: vIdx, positionId: posId, label: label });
        var $collapse = $('[data-vacant-index="' + vIdx + '"][data-position-id="' + posId + '"]').first().closest('.collapse');
        wbShowCollapse($collapse);
        wbApplyFocus(); wbRenderBreadcrumb();
        wbShowVacantDetailHeader(vIdx);
        $('#wbLevelList').empty();
    });

    // ---- Breadcrumb / back navigation (jump to an already-drilled level —
    // no re-fetch needed, the content is already rendered in the DOM) ----
    function wbJumpTo(idx) {
        wbPath = wbPath.slice(0, idx + 1);
        wbApplyFocus(); wbRenderBreadcrumb();
        if (wbPath.length === 0) { wbRenderRoot(); return; }
        var last = wbPath[wbPath.length - 1];
        if (last.level === 'division') {
            wbRenderDepartments($('#collapseDiv' + last.iter).closest('.division-accordion'));
        } else if (last.level === 'department') {
            wbRenderSectionsAndPositions($('[data-department-id="' + last.id + '"]').first());
        } else if (last.level === 'section') {
            wbRenderPositionsInSection($('#' + last.domId).closest('.section-accordion'));
        } else if (last.level === 'position') {
            var $posEl = $('.position-accordion[data-position-id="' + last.id + '"]').first();
            wbRenderEmployeesAndVacant($posEl.find('.accordion-body[data-position-id]').first(), last.id);
        } else {
            $('#wbLevelList').empty();
        }
    }

    $(document).on('click', '#wbBreadcrumb button', function () {
        wbJumpTo(parseInt($(this).data('wb-idx'), 10));
    });
    $(document).on('click', '#wbBackBtn', function () {
        if (wbPath.length === 0) return;
        wbJumpTo(wbPath.length - 2);
    });

    // ---- Search (Phase 1) — over already-loaded DOM only, per the
    // explicit "don't bypass existing server-side loading" requirement.
    // No search endpoint exists for this hierarchy today. ----
    $(document).on('input', '#wbSearchInput', function () {
        var q = $(this).val().trim().toLowerCase();
        var $results = $('#wbSearchResults');
        if (q.length < 2) { $results.addClass('d-none').empty(); return; }

        var matches = [];
        $('.employee-accordion').each(function () {
            var $emp = $(this);
            var body = $emp.find('.accordion-body[data-employee-id]').first();
            var name = $emp.find('.accordion-header span').first().text().trim();
            var role = $emp.find('.badge.bg-secondary').first().text().trim();
            if ((name + ' ' + role).toLowerCase().indexOf(q) === -1) return;
            matches.push({
                type: 'Employee', label: name, sub: role,
                empId: body.data('employee-id'), positionId: body.data('position-id')
            });
        });
        $('.position-accordion').each(function () {
            var $pos = $(this);
            var title = $pos.find('.accordion-header span').first().text().trim();
            if (title.toLowerCase().indexOf(q) === -1) return;
            matches.push({ type: 'Position', label: title, positionId: $pos.data('position-id') });
        });

        if (!matches.length) {
            $results.removeClass('d-none').html('<div class="wb-search-result-row text-muted">No matches in departments you\'ve opened this session.</div>');
            return;
        }
        var html = '';
        matches.slice(0, 20).forEach(function (m, i) {
            html += '<div class="wb-search-result-row" data-wb-idx="' + i + '">' +
                '<span class="wb-search-result-type">' + wbEsc(m.type) + '</span>' +
                '<span><span class="wb-search-result-name">' + wbEsc(m.label) + '</span>' +
                (m.sub ? ' <span class="wb-search-result-sub">' + wbEsc(m.sub) + '</span>' : '') + '</span>' +
                '</div>';
        });
        $results.removeClass('d-none').html(html).data('wb-matches', matches);
    });

    $(document).on('click', '.wb-search-result-row[data-wb-idx]', function () {
        var matches = $('#wbSearchResults').data('wb-matches') || [];
        var m = matches[parseInt($(this).data('wb-idx'), 10)];
        if (!m) return;
        $('#wbSearchResults').addClass('d-none').empty();
        $('#wbSearchInput').val('');
        if (m.type === 'Employee') {
            var $collapse = $('[data-employee-id="' + m.empId + '"]').first().closest('.collapse');
            wbPath.push({ level: 'employee', id: m.empId, label: m.label });
            wbShowCollapse($collapse);
            wbApplyFocus(); wbRenderBreadcrumb();
            wbShowEmployeeDetailHeader(m.empId);
        } else {
            var $posEl = $('.position-accordion[data-position-id="' + m.positionId + '"]').first();
            var $posCollapse = $posEl.find('> .accordion-item > .collapse');
            wbPath.push({ level: 'position', id: m.positionId, label: m.label });
            wbShowCollapse($posCollapse);
            wbApplyFocus(); wbRenderBreadcrumb();
            wbWaitForContent($posCollapse.find('.accordion-body').first(), function () {
                wbRenderEmployeesAndVacant($posCollapse.find('.accordion-body').first(), m.positionId);
            });
        }
    });
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#wbSearchGroup').length) {
            $('#wbSearchResults').addClass('d-none');
        }
    });

    // Initial render of the top level once divisions exist in the DOM
    // (they're server-rendered, so this runs immediately — no AJAX wait,
    // though badge totals are still placeholders at this point).
    wbRenderRoot();

    // The division/department budget badges start as server-rendered
    // "$0.00" placeholders and only get their real totals once
    // updateAllBadgesFromTotals() runs (after the eager background load
    // chain resolves, ~1.5s after page load). Wrap it — purely additive,
    // the original function body is untouched and still runs exactly as
    // before — so the nav card list re-renders with real numbers once
    // they're available, if the user is still viewing the root level.
    if (typeof updateAllBadgesFromTotals === 'function') {
        var wbOrigUpdateAllBadgesFromTotals = updateAllBadgesFromTotals;
        updateAllBadgesFromTotals = function () {
            wbOrigUpdateAllBadgesFromTotals.apply(this, arguments);
            if (wbPath.length === 0) wbRenderRoot();
        };
    }
});
</script>

@include('resorts.renderfiles.budget_cost_modal')

@endsection

