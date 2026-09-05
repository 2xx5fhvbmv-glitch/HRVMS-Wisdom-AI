{{--
    Shared "Top Hiring Sources" card — admin/hr dashboards both @include this.
    Reuses the existing resort.ta.topHiringSources AJAX endpoint and its
    response shape (per-source monthly counts for one year) unchanged; the
    Period filter (Full Year / Last Quarter / Last 6 Months / a single
    month) is applied entirely client-side by summing whichever months of
    the already-fetched year are in scope, so switching Period never needs
    another request. The Vacancy filter does need a new request (there's no
    per-vacancy breakdown in a single year-fetch), so it re-calls the same
    endpoint with an added optional vacancy_id param
    (see TalentAcquisitionDashboardController::topHiringSources()).

    Vacancy options come from $NewVacancies — already fetched by
    admin_dashboard()/hr_dashboard() for the Open Vacancies table above, no
    new query for the dropdown either.

    Markup/styles only — the JS lives in the sibling
    _top_hiring_sources_scripts.blade.php, @include'd from each dashboard's
    own @section('import-scripts') instead of here, so it runs after
    jQuery has loaded rather than inline in the content body (a <script>
    tag here would parse before this app's jQuery <script src>, which
    loads late — `$` would be undefined at that point).
--}}
<div class="card card-topHiring topHiring-v2 ta-toprow-card">
    <div class="card-title">
        <div class="row justify-content-between align-items-start g-2">
            <div class="col">
                <h3 class="text-nowrap">Top Hiring Sources</h3>
                <div class="topHiring-summary" id="topHiringSummary">&nbsp;</div>
            </div>
        </div>
        <div class="row g-2 topHiring-filters">
            <div class="col-sm-6">
                <label class="topHiring-filter-label" for="topHiringPeriod">Period</label>
                <select class="form-select dd-native-select topHiring-period" id="topHiringPeriod" aria-label="Period">
                    <option value="full_year" selected>Full Year</option>
                    <option value="last_quarter">Last Quarter</option>
                    <option value="last_6_months">Last 6 Months</option>
                    @php
                        $thMonthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
                    @endphp
                    @foreach($thMonthNames as $thMonthIndex => $thMonthName)
                        <option value="{{ $thMonthIndex + 1 }}">{{ $thMonthName }}</option>
                    @endforeach
                </select>
                <div class="dd" data-target="#topHiringPeriod">
                    <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                        <span class="dd-lbl">Full Year</span>
                        <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                    </button>
                    <div class="dd-panel" role="listbox" aria-label="Period">
                        <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a period…"></div>
                        <div class="dd-scroll">
                            <div class="dd-item active" role="option" data-value="full_year"><span class="dd-nm">Full Year</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                            <div class="dd-item" role="option" data-value="last_quarter"><span class="dd-nm">Last Quarter</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                            <div class="dd-item" role="option" data-value="last_6_months"><span class="dd-nm">Last 6 Months</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                            @foreach($thMonthNames as $thMonthIndex => $thMonthName)
                            <div class="dd-item" role="option" data-value="{{ $thMonthIndex + 1 }}"><span class="dd-nm">{{ $thMonthName }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <label class="topHiring-filter-label" for="topHiringVacancy">Vacancy</label>
                <select class="form-select dd-native-select topHiring-vacancy" id="topHiringVacancy" aria-label="Vacancy">
                    <option value="" selected>All vacancies</option>
                    @if(isset($NewVacancies))
                        @foreach($NewVacancies as $vac)
                            <option value="{{ $vac->vacancy_id }}">{{ $vac->positionTitle }} &middot; {{ $vac->Department }}</option>
                        @endforeach
                    @endif
                </select>
                <div class="dd" data-target="#topHiringVacancy">
                    <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                        <span class="dd-lbl">All vacancies</span>
                        <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                    </button>
                    <div class="dd-panel" role="listbox" aria-label="Vacancy">
                        <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a vacancy…"></div>
                        <div class="dd-scroll">
                            <div class="dd-item active" role="option" data-value=""><span class="dd-nm">All vacancies</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                            @if(isset($NewVacancies))
                                @foreach($NewVacancies as $vac)
                                <div class="dd-item" role="option" data-value="{{ $vac->vacancy_id }}"><span class="dd-nm">{{ $vac->positionTitle }} &middot; {{ $vac->Department }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="topHiring-list" id="topHiringList"></div>

    <div class="ta-chart-empty d-none" id="topHiringEmptyState">
        <i class="fa-regular fa-chart-line"></i>
        <p>No applicants from tracked sources for this selection.</p>
    </div>
</div>

@once
<style>
    .topHiring-v2 .topHiring-summary {
        font-size: 14px;
        color: var(--muted);
        margin-top: 2px;
    }
    .topHiring-filters {
        margin-top: 14px;
    }
    .topHiring-filter-label {
        display: block;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .03em;
        color: var(--faint);
        margin-bottom: 4px;
    }
    .topHiring-list {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        padding-right: 4px;
        margin-top: 16px;
    }
    .ta-chart-empty {
        flex: 1 1 auto;
        min-height: 0;
    }
    .ths-row {
        padding: 10px 0;
        border-bottom: 1px solid var(--line);
    }
    .ths-row:first-child { padding-top: 0; }
    .ths-row:last-child { border-bottom: 0; padding-bottom: 0; }
    .ths-row-top {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 8px;
    }
    .ths-rank {
        width: 16px;
        flex-shrink: 0;
        font-size: 11px;
        color: var(--faint);
        font-weight: 600;
    }
    .ths-icon-tile {
        width: 30px;
        height: 30px;
        min-width: 30px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 14px;
        font-weight: 600;
        flex-shrink: 0;
    }
    .ths-icon-tile-globe { background: var(--muted); }
    .ths-name {
        flex: 1 1 auto;
        font-weight: 600;
        color: var(--ink);
        font-size: 14px;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .ths-count {
        font-weight: 600;
        color: var(--ink);
        font-variant-numeric: tabular-nums;
        flex-shrink: 0;
    }
    .ths-pct {
        font-weight: 500;
        color: var(--muted);
        font-variant-numeric: tabular-nums;
        min-width: 52px;
        text-align: right;
        flex-shrink: 0;
    }
    .ths-bar-track {
        height: 6px;
        border-radius: 6px;
        background: var(--line-2);
        overflow: hidden;
        margin-left: 40px;
    }
    .ths-bar-fill {
        height: 100%;
        border-radius: 6px;
    }
</style>
@endonce
