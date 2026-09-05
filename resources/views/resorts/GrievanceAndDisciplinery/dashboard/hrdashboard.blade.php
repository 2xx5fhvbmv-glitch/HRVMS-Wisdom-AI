@extends('resorts.layouts.app')
@section('page_tab_title' ," People Relation Dashboard")

@if ($message = Session::get('success'))
    <div class="alert alert-success">
        <p>{{ $message }}</p>
    </div>
@endif

@section('content')
<style>
    /* Same requested push as the other module dashboards/pages (Payroll /
       Talent Acquisition / People / Time and Attendance / Leave /
       Performance / Learning / Accommodation / Incident / Survey /
       Reports / Support / Visa) — extra breathing room between the hero
       and the KPI row below it, scoped to this page (.page-hedding's own
       margin-bottom is shared by every page's hero). padding-bottom, not
       margin: adjacent sibling margins collapse to the larger of the two
       rather than summing. Below Bootstrap's sm breakpoint the extra
       padding pushes the KPI row's first card into the teal hero curve's
       rounded bottom-left corner (body::before, border-radius 0 0 50px
       50px) — same collision found on Payroll — neutralized below 576px. */
    #grievance-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #grievance-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid dbg-wrap">
        <div class="page-hedding" id="grievance-hero">
            <div class="row  g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>{{ $page_title ?? 'People Relation' }}</span>
                        <h1>Dashboard</h1>
                    </div>
                </div>
                {{-- "All Cases Combined" filter — commented out per request, not
                     wired to anything yet. Uncomment when the filter is ready. --}}
                {{--
                <div class="col-xxl-2 col-auto ms-auto">
                    <select class="form-select select2t-none" id="select-budgeted"
                        aria-label="Default select example">
                        <option selected>All Cases Combined</option>
                    </select>
                </div>
                --}}
            </div>
        </div>

        @php
            // Arrow icon was loading from a relative path that broke on
            // any non-root URL — switch to the absolute asset() helper.
            $arrowIcon = asset('resorts_assets/images/arrow-right-circle.svg');
            $grievanceListUrl = route('GrievanceAndDisciplinery.grivance.GrivanceIndex');
            $disciplinaryListUrl = route('GrievanceAndDisciplinery.Disciplinary.DisciplinaryIndex');

            // Grievance category ranking — same $grivanceCategoryWiseCount both
            // HR_Dashobard() and Hod_dashboard() already pass, just sorted/capped
            // here for the ranked-bar card (no new query, pure view-layer sort).
            $dbgGrCats = collect($grivanceCategoryWiseCount ?? [])->map(fn ($c, $k) => ['name' => $k, 'count' => $c])->values()->sortByDesc('count')->values();
            $dbgGrCatsTop = $dbgGrCats->take(6);
            $dbgGrCatsOtherCount = $dbgGrCats->slice(6)->sum('count');
            $dbgGrCatsOtherN = $dbgGrCats->count() - $dbgGrCatsTop->count();
            $dbgGrCatsMax = max(1, $dbgGrCats->max('count') ?? 1);

            // Disciplinary offence ranking — already computed by
            // buildGrievanceInsights()'s hotspots card (grievanceInsights is
            // only passed by HR_Dashobard(), so this is HR-only; HOD falls
            // back to the "not available" state below rather than a new query).
            $dbgOffences = collect($grievanceInsights['hotspots']['details']['offenses'] ?? [])->map(fn ($o) => ['name' => $o['offense'], 'count' => $o['count']])->values();
            $dbgOffencesTop = $dbgOffences->take(6);
            $dbgOffencesOtherCount = $dbgOffences->slice(6)->sum('count');
            $dbgOffencesOtherN = $dbgOffences->count() - $dbgOffencesTop->count();
            $dbgOffencesMax = max(1, $dbgOffences->max('count') ?? 1);

            // Case Mix — prefer the true all-status totals already computed by
            // the insights "volume" card; fall back to summing the open/pending/
            // closed splits (available on both HR and HOD routes) if insights
            // aren't loaded. Either way, no new query.
            $dbgMixGr = $grievanceInsights['volume']['details']['grievance'] ?? (($openGrievance ?? 0) + ($pendingGrievance ?? 0) + ($closedGrievance ?? 0));
            $dbgMixDi = $grievanceInsights['volume']['details']['disciplinary'] ?? (($openDisciplinary ?? 0) + ($pendingDisciplinary ?? 0) + ($closedDisciplinary ?? 0));
            $dbgMixTotal = max(1, $dbgMixGr + $dbgMixDi);
            $dbgMixGrPct = round($dbgMixGr / $dbgMixTotal * 100);
        @endphp

        {{-- KPI capsules --}}
        <div class="row g-3 g-xxl-4">
            <div class="col-xl-3 col-sm-6">
                <div class="dbg-kpi">
                    <div class="dbg-kpi-top">
                        <div>
                            <div class="dbg-kpi-num">{{ $openCases ?? 0 }}</div>
                            <div class="dbg-kpi-lbl">Open Cases</div>
                        </div>
                        <a href="{{ $grievanceListUrl }}" class="dbg-kpi-go" aria-label="View open cases"><img src="{{ $arrowIcon }}" alt="" class="img-fluid"></a>
                    </div>
                    <div class="dbg-kpi-split">
                        <a href="{{ $grievanceListUrl }}">Grievance {{ $openGrievance ?? 0 }}</a><span class="dbg-dot">&middot;</span><a href="{{ $disciplinaryListUrl }}">Disciplinary {{ $openDisciplinary ?? 0 }}</a>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="dbg-kpi">
                    <div class="dbg-kpi-top">
                        <div>
                            <div class="dbg-kpi-num">{{ $pendingCases ?? 0 }}</div>
                            <div class="dbg-kpi-lbl">Pending Cases</div>
                        </div>
                        <a href="{{ $grievanceListUrl }}" class="dbg-kpi-go" aria-label="View pending cases"><img src="{{ $arrowIcon }}" alt="" class="img-fluid"></a>
                    </div>
                    <div class="dbg-kpi-split">
                        <a href="{{ $grievanceListUrl }}">Grievance {{ $pendingGrievance ?? 0 }}</a><span class="dbg-dot">&middot;</span><a href="{{ $disciplinaryListUrl }}">Disciplinary {{ $pendingDisciplinary ?? 0 }}</a>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="dbg-kpi">
                    <div class="dbg-kpi-top">
                        <div>
                            <div class="dbg-kpi-num">{{ $closedCases ?? 0 }}</div>
                            <div class="dbg-kpi-lbl">Closed Cases</div>
                        </div>
                        <a href="{{ $grievanceListUrl }}" class="dbg-kpi-go" aria-label="View closed cases"><img src="{{ $arrowIcon }}" alt="" class="img-fluid"></a>
                    </div>
                    <div class="dbg-kpi-split">
                        <a href="{{ $grievanceListUrl }}">Grievance {{ $closedGrievance ?? 0 }}</a><span class="dbg-dot">&middot;</span><a href="{{ $disciplinaryListUrl }}">Disciplinary {{ $closedDisciplinary ?? 0 }}</a>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="dbg-kpi">
                    <div class="dbg-kpi-top">
                        <div>
                            <div class="dbg-kpi-num">{{ $expiredOffense ?? 0 }}</div>
                            <div class="dbg-kpi-lbl">Expired Offences</div>
                        </div>
                        <a href="{{ $disciplinaryListUrl }}" class="dbg-kpi-go" aria-label="View disciplinary cases"><img src="{{ $arrowIcon }}" alt="" class="img-fluid"></a>
                    </div>
                    <div class="dbg-kpi-split"><span class="dbg-muted">Disciplinary offences</span></div>
                </div>
            </div>
        </div>

        {{-- TIER 1 — Act on these first --}}
        <div class="dbg-tier">
            <span class="dbg-tl">Tier 1 &middot; Priority</span>
            <span class="dbg-ts">Act on these first</span>
        </div>
        <div class="row g-3 g-xxl-4">
            <div class="col-xl-6">
                <div class="card card-wiINsightGriev wai-narrative h-100" id="card-wiINsightGriev">
                    @php $grMeta = $grievanceInsights['_meta'] ?? null; @endphp
                    <div class="wai-head">
                        <h2>WAI Insights</h2>
                        @if($grMeta)
                            <div class="wai-head-meta">
                                <span>Updated {{ $grMeta['generated_at']->diffForHumans() }}</span>
                                @if($grMeta['can_regenerate'])
                                    <a href="?regenerate_insights=1">Regenerate</a>
                                @else
                                    <span title="{{ $grMeta['next_available']->format('d M Y, H:i') }}">&middot; Regenerate {{ $grMeta['next_available']->diffForHumans() }}</span>
                                @endif
                            </div>
                        @endif
                    </div>
                    <div class="leaveUser-main wai-narrative-body">
                        @foreach([['key'=>'volume','modal'=>'grievInsightVolumeModal'],['key'=>'sla','modal'=>'grievInsightSlaModal'],['key'=>'hotspots','modal'=>'grievInsightHotspotsModal'],['key'=>'outcomes','modal'=>'grievInsightOutcomesModal']] as $gc)
                            @php
                                $hasRecommendation = !empty($grievanceInsights[$gc['key']]['recommendation']);
                                // SLA breach is the most urgent signal on this card — give
                                // it the error/alert icon instead of the generic amber
                                // "flagged" icon whenever there's an active overdue count.
                                $isSlaBreach = $gc['key'] === 'sla' && (($grievanceInsights['sla']['details']['overdue'] ?? 0) > 0);
                                $rowIconClass = $isSlaBreach ? 'is-critical' : ($hasRecommendation ? 'is-flagged' : 'is-ok');
                                $rowIcon = $isSlaBreach ? 'fa-circle-exclamation' : ($hasRecommendation ? 'fa-triangle-exclamation' : 'fa-check');
                            @endphp
                            <div class="wai-row">
                                <div class="wai-row-icon {{ $rowIconClass }}">
                                    <i class="fa-solid {{ $rowIcon }}"></i>
                                </div>
                                <div class="wai-row-body">
                                    <h6>{{ $grievanceInsights[$gc['key']]['title'] ?? '' }}</h6>
                                    <p class="wai-row-text">{{ $grievanceInsights[$gc['key']]['body'] ?? '' }}</p>
                                    <div class="lnkrow">
                                        @if($hasRecommendation)
                                            <button type="button" class="lnk-rec"
                                                data-title="{{ $grievanceInsights[$gc['key']]['title'] ?? '' }}"
                                                data-rec="{{ $grievanceInsights[$gc['key']]['recommendation'] }}"
                                                data-details="{{ $gc['modal'] }}">View recommendation &rarr;</button>
                                            <span class="sep"></span>
                                        @endif
                                        <a href="#" class="lnk" data-details="{{ $gc['modal'] }}">View details &rarr;</a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="dbg-card h-100">
                    <div class="dbg-card-h">
                        <h3 class="dbg-ttl">Case Timelines</h3>
                        <a href="{{ $grievanceListUrl }}" class="dbg-viewall">View all &rarr;</a>
                    </div>
                    @forelse(($caseTimelines ?? collect()) as $tl)
                        @php
                            // Color the progress bar by how close we are to the
                            // deadline: <40 % green, 40–70 % amber, >70 % red.
                            $pct = $tl['progress_pct'];
                            $color = $pct < 40 ? 'progress-themeGreen' : ($pct < 70 ? 'progress-themeWarning' : 'progress-themeRed');
                            // Overdue badge — derived from the existing filed/deadline
                            // strings already computed by the controller (no new query),
                            // just compared against "now" here in the view.
                            $tlIsOverdue = false;
                            try {
                                $tlIsOverdue = \Carbon\Carbon::createFromFormat('d M Y', $tl['deadline'])->endOfDay()->isPast();
                            } catch (\Throwable $e) {}
                        @endphp
                        <div class="dbg-tl-row" title="Filed {{ $tl['filed_date'] }} &rarr; Deadline {{ $tl['deadline'] }} ({{ $pct }}% elapsed)">
                            <div class="dbg-tl-top">
                                <span class="dbg-tl-cat">{{ $tl['name'] }}</span>
                                @if($tlIsOverdue)
                                    <span class="dbg-tl-badge">Overdue</span>
                                @endif
                            </div>
                            <div class="progress progress-custom progress-customDot {{ $color }}">
                                <div class="progress-bar" role="progressbar" style="width: {{ $pct }}%"
                                    aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="dbg-tl-dates">
                                <div>Filed <span>{{ $tl['filed_date'] }}</span></div>
                                <div>Deadline <span>{{ $tl['deadline'] }}</span></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No active cases on the timeline.</p>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="row g-3 g-xxl-4 mt-1">
            <div class="col-xl-3 col-sm-6">
                <div class="dbg-mini">
                    <div class="dbg-mini-ic"><i class="fa-solid fa-hourglass-half"></i></div>
                    <div>
                        <div class="dbg-mini-val">{{ $PendingApprovals ?? 0 }}</div>
                        <div class="dbg-mini-lbl">Pending Approvals</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="dbg-mini">
                    <div class="dbg-mini-ic"><i class="fa-solid fa-user-group"></i></div>
                    <div>
                        <div class="dbg-mini-val">{{ $DelegatedCases ?? 0 }}</div>
                        <div class="dbg-mini-lbl">Delegated Cases</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 col-sm-12">
                <div class="dbg-card">
                    <div class="dbg-card-h">
                        <h3 class="dbg-ttl">Confidential Cases</h3>
                    </div>
                    <div class="dbg-stat" title="{{ $confidentialResolvedPct ?? 0 }}% of confidential cases resolved">
                        <div class="dbg-stat-top"><span class="dbg-nm">Resolved</span><span class="dbg-v">{{ $confidentialResolvedPct ?? 0 }}%</span></div>
                        <div class="dbg-stat-track"><div class="dbg-stat-fill" style="width: {{ $confidentialResolvedPct ?? 0 }}%"></div></div>
                    </div>
                    <div class="dbg-stat" title="{{ $confidentialUnresolvedPct ?? 0 }}% of confidential cases unresolved">
                        <div class="dbg-stat-top"><span class="dbg-nm">Unresolved</span><span class="dbg-v">{{ $confidentialUnresolvedPct ?? 0 }}%</span></div>
                        <div class="dbg-stat-track"><div class="dbg-stat-fill dbg-fill-warn" style="width: {{ $confidentialUnresolvedPct ?? 0 }}%"></div></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- TIER 2 — Understand the pattern --}}
        <div class="dbg-tier">
            <span class="dbg-tl">Tier 2 &middot; Analysis</span>
            <span class="dbg-ts">Understand the pattern</span>
        </div>
        <div class="row g-3 g-xxl-4 @if(Common::checkRouteWisePermission('GrievanceAndDisciplinery.grivance.GrivanceIndex',config('settings.resort_permissions.view')) == false) d-none @endif">
            <div class="col-xl-6">
                <div class="dbg-card h-100">
                    <div class="dbg-card-h">
                        <h3 class="dbg-ttl">Grievances by Category</h3>
                        @if($dbgGrCatsOtherN > 0)
                            <a href="{{ $grievanceListUrl }}" class="dbg-viewall">View all {{ $dbgGrCats->count() }} &rarr;</a>
                        @endif
                    </div>
                    @forelse($dbgGrCatsTop as $cat)
                        <div class="dbg-catrow" title="{{ $cat['name'] }}: {{ $cat['count'] }}">
                            <div class="dbg-catrow-h"><span class="dbg-nm">{{ $cat['name'] }}</span><span class="dbg-val">{{ $cat['count'] }}</span></div>
                            <div class="dbg-catrow-track"><div class="dbg-catrow-fill" style="width: {{ round($cat['count'] / $dbgGrCatsMax * 100) }}%"></div></div>
                        </div>
                    @empty
                        <p class="dbg-cat-empty">No grievance categories recorded yet.</p>
                    @endforelse
                    @if($dbgGrCatsOtherN > 0)
                        <div class="dbg-catrow-other">Other &middot; {{ $dbgGrCatsOtherN }} {{ Str::plural('category', $dbgGrCatsOtherN) }} ({{ $dbgGrCatsOtherCount }})</div>
                    @endif
                </div>
            </div>
            <div class="col-xl-6">
                <div class="dbg-card h-100">
                    <div class="dbg-card-h">
                        <h3 class="dbg-ttl">Disciplinary Offences</h3>
                        @if($dbgOffencesOtherN > 0)
                            <a href="{{ $disciplinaryListUrl }}" class="dbg-viewall">View all {{ $dbgOffences->count() }} &rarr;</a>
                        @endif
                    </div>
                    @if(isset($grievanceInsights['hotspots']['details']['offenses']))
                        @forelse($dbgOffencesTop as $off)
                            <div class="dbg-catrow dbg-catrow-d" title="{{ $off['name'] }}: {{ $off['count'] }}">
                                <div class="dbg-catrow-h"><span class="dbg-nm">{{ $off['name'] }}</span><span class="dbg-val">{{ $off['count'] }}</span></div>
                                <div class="dbg-catrow-track"><div class="dbg-catrow-fill" style="width: {{ round($off['count'] / $dbgOffencesMax * 100) }}%"></div></div>
                            </div>
                        @empty
                            <p class="dbg-cat-empty">No disciplinary offences recorded yet.</p>
                        @endforelse
                        @if($dbgOffencesOtherN > 0)
                            <div class="dbg-catrow-other">Other &middot; {{ $dbgOffencesOtherN }} {{ Str::plural('offence', $dbgOffencesOtherN) }} ({{ $dbgOffencesOtherCount }})</div>
                        @endif
                    @else
                        <p class="dbg-cat-empty">Not available on this view.</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- TIER 3 — Resolution & case mix --}}
        <div class="dbg-tier">
            <span class="dbg-tl">Tier 3 &middot; Overview</span>
            <span class="dbg-ts">Resolution &amp; case mix</span>
        </div>
        <div class="row g-3 g-xxl-4">
            <div class="col-xl-6 col-sm-6">
                <div class="dbg-card h-100">
                    <div class="dbg-card-h"><h3 class="dbg-ttl">Resolution Rate</h3></div>
                    <div class="dbg-donut-row">
                        <div class="dbg-donut" title="{{ $totalPercengate ?? 0 }}% of grievances resolved"
                            style="background: conic-gradient(var(--teal) 0% {{ $totalPercengate ?? 0 }}%, var(--line-2) {{ $totalPercengate ?? 0 }}% 100%);">
                            <div class="dbg-hole"><b>{{ $totalPercengate ?? 0 }}%</b><span>Resolved</span></div>
                        </div>
                        <div class="dbg-legend">
                            <div class="dbg-lg"><span class="dbg-lg-dot" style="background:var(--teal)"></span><span class="dbg-lg-nm">Grievances resolved</span><span class="dbg-lg-v">{{ $totalPercengate ?? 0 }}%</span></div>
                            <div class="dbg-avgline">Avg resolution time &middot; {{ $avgResolutionHours ?? 0 }} {{ Str::plural('hr', $avgResolutionHours ?? 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 col-sm-6">
                <div class="dbg-card h-100">
                    <div class="dbg-card-h"><h3 class="dbg-ttl">Case Mix</h3></div>
                    <div class="dbg-donut-row">
                        <div class="dbg-donut" title="Grievance {{ $dbgMixGr }} &middot; Disciplinary {{ $dbgMixDi }}"
                            style="background: conic-gradient(var(--teal) 0% {{ $dbgMixGrPct }}%, var(--aqua) {{ $dbgMixGrPct }}% 100%);">
                            <div class="dbg-hole"><b>{{ $dbgMixGrPct }}%</b><span>Grievance</span></div>
                        </div>
                        <div class="dbg-legend">
                            <div class="dbg-lg"><span class="dbg-lg-dot" style="background:var(--teal)"></span><span class="dbg-lg-nm">Grievance</span><span class="dbg-lg-v">{{ $dbgMixGr }}</span></div>
                            <div class="dbg-lg"><span class="dbg-lg-dot" style="background:var(--aqua)"></span><span class="dbg-lg-nm">Disciplinary</span><span class="dbg-lg-v">{{ $dbgMixDi }}</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Appeals Section hidden per request — not producing useful data
             yet (0 appeals filed on real resorts so far). Uncomment to
             bring it back; $appealsSubmitted/$hearingsPending/
             $hearingsResolved/$appealsByCategoryLabels/Data are still
             computed and passed by the controller.
        <div class="col-xl-6 d-flex">
            <div class="card card-appealsSection h-100 w-100">
                <div class="card-title">
                    <h3>Appeals Section</h3>
                </div>
                <p>Total Appeals Submitted: {{ $appealsSubmitted ?? 0 }} | Average Resolution Time: {{ $avgResolutionHours ?? 0 }} Hrs</p>
                <div class="row g-3 g-xxl-4">
                    <div class="col-md-6  col-sm-7">
                        <div class="bg-themeGrayLight">
                            <h6>Appeals by category</h6>
                            <canvas id="appealsByCategory" width="349" height="199"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-5">
                        <div class="bg-themeGrayLight text-center text-md-start">
                            <h6>Pending vs. resolved Hearings</h6>
                            <div class="row gx-2 gy-0 align-items-center">
                                <div class=" col-xxl-8 col-xl-12 col-md-8">
                                    <div class="payrollDistr-chart">
                                        <canvas id="myDoughnutChartPeopleRelation"></canvas>
                                    </div>
                                </div>
                                <div class="col-xxl-4 col-xl-12 col-md-4">
                                    <div class="row g-2 justify-content-center">
                                        <div class="col-xxl-12 col-xl-auto col-md-12 col-auto">
                                            <div class="doughnut-label">
                                                <span class="bg-theme"></span>Pending <br>{{ $hearingsPending ?? 0 }}
                                            </div>
                                        </div>
                                        <div class="col-xxl-12 col-xl-auto col-md-12 col-auto">
                                            <div class="doughnut-label">
                                                <span class="bg-themeLightBlue"></span>Resolved
                                                <br>{{ $hearingsResolved ?? 0 }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        --}}

        {{-- Retaliation Reports Filed tile hidden per the latest
             design — uncomment to bring it back without re-wiring
             the controller (the $retaliationReports variable is
             still passed to the view).
        <div class="col-12">
            <div class="card dashboard-boxcard timeAttend-boxcard">
                <div class="d-flex align-items-center justify-content-between">
                    <p class="mb-0  fw-600">Retaliation Reports Filed</p>
                    <strong>{{ $retaliationReports ?? 0 }}</strong>
                </div>
            </div>
        </div>
        --}}

        <!-- <div class="col-xl-6 order-sm-3 order-xl-0">
            <div class="card h-auto" id="card-breakdownCases">
                <div class=" card-title">
                    <div class="row justify-content-between align-items-center g-md-3 g-1">
                        <div class="col">
                            <h3 class="text-nowrap">Breakdown Of Cases</h3>
                        </div>
                        <div class="col-auto">
                            <div class="form-group">
                                <select class="form-select" aria-label="Default select example">
                                    <option selected="">By Category</option>
                                    <option value="1">AAA</option>
                                    <option value="2">AAA</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <canvas id="breakdownCases"></canvas>
            </div>
        </div> -->
        <!-- <div class="col-xl-3 col-md-6 order-sm-4 order-xl-0">
            <div class="card card-offenseNearingExpiry" id="card-offenseNearingExpiry">
                <div class=" card-title">
                    <div class="row justify-content-between align-items-center g-md-3 g-1">
                        <div class="col">
                            <h3 class="text-nowrap">Offense Nearing To Expiry</h3>
                        </div>
                        <div class="col-auto">
                            <a href="#" class="a-link">View All</a>
                        </div>
                    </div>
                </div>
                <div class="leaveUser-main">
                    <div class="leaveUser-block">
                        <div>
                            <h6>Lorem Ipsum is dummy text</h6>
                            <p>Lorem ipsum is simply dummy text of the typesetting industry
                                Lorem typesetting
                                industry ipsum. Lorem ipsum is simply dummy text of the
                                typesetting industry
                                Lorem typesetting industry ipsum.
                            </p>
                            <div>
                                <a href="#" class="a-linkTheme me-1 me-md-2">Close</a>
                                <a href="#" class="a-link">Extend</a>
                            </div>
                        </div>
                    </div>
                    <div class="leaveUser-block">

                        <div>
                            <h6>typesetting industry Lorem typesetting industry ipsum.</h6>
                            <p>Lorem ipsum is simply dummy text of the typesetting industry
                                Lorem typesetting
                                industry ipsum. Lorem ipsum is simply dummy text of the
                                typesetting industry
                                Lorem typesetting industry ipsum.
                            </p>
                            <div>
                                <a href="#" class="a-linkTheme me-1 me-md-2">Close</a>
                                <a href="#" class="a-link">Extend</a>
                            </div>
                        </div>
                    </div>
                    <div class="leaveUser-block">

                        <div>
                            <h6>typesetting industry Lorem typesetting industry ipsum.</h6>
                            <p>Lorem ipsum is simply dummy text of the typesetting industry
                                Lorem typesetting
                                industry ipsum. Lorem ipsum is simply dummy text of the
                                typesetting industry
                                Lorem typesetting industry ipsum.
                            </p>
                            <div>
                                <a href="#" class="a-linkTheme me-1 me-md-2">Close</a>
                                <a href="#" class="a-link">Extend</a>
                            </div>
                        </div>
                    </div>
                    <div class="leaveUser-block">

                        <div>
                            <h6>typesetting industry Lorem typesetting industry ipsum.</h6>
                            <p>Lorem ipsum is simply dummy text of the typesetting industry
                                Lorem typesetting
                                industry ipsum. Lorem ipsum is simply dummy text of the
                                typesetting industry
                                Lorem typesetting industry ipsum.
                            </p>
                            <div>
                                <a href="#" class="a-linkTheme me-1 me-md-2">Close</a>
                                <a href="#" class="a-link">Extend</a>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 order-sm-5 order-xl-0">
            <div class="card card-wiINsightPayroll card-wiINsightperforHod" id="card-wiINsight">
                <div class=" card-title">
                    <div class="row justify-content-between align-items-center g-md-3 g-1">
                        <div class="col">
                            <h3 class="text-nowrap">WI Insight's</h3>
                        </div>
                        <div class="col-auto">
                            <a href="#" class="a-link">View All</a>
                        </div>
                    </div>
                </div>
                <div class="leaveUser-main">
                    <div class="leaveUser-block">
                        <div class="img">
                            <img src="assets/images/wisdom-ai-small.svg" alt="image">
                        </div>
                        <div>
                            <h6>Lorem Ipsum is dummy text</h6>
                            <p>Lorem ipsum is simply dummy text of the typesetting industry
                                Lorem typesetting
                                industry ipsum. Lorem ipsum is simply dummy text of the
                                typesetting industry
                                Lorem typesetting industry ipsum.
                            </p>
                            <div>
                                <a href="#" class="a-linkTheme">View Details</a>
                            </div>
                        </div>
                    </div>
                    <div class="leaveUser-block">
                        <div class="img">
                            <img src="assets/images/wisdom-ai-small.svg" alt="image">
                        </div>
                        <div>
                            <h6>typesetting industry Lorem typesetting industry ipsum.</h6>
                            <p>Lorem ipsum is simply dummy text of the typesetting industry
                                Lorem typesetting
                                industry ipsum. Lorem ipsum is simply dummy text of the
                                typesetting industry
                                Lorem typesetting industry ipsum.
                            </p>
                            <div>
                                <a href="#" class="a-linkTheme">View Details</a>
                            </div>
                        </div>
                    </div>
                    <div class="leaveUser-block">
                        <div class="img">
                            <img src="assets/images/wisdom-ai-small.svg" alt="image">
                        </div>
                        <div>
                            <h6>typesetting industry Lorem typesetting industry ipsum.</h6>
                            <p>Lorem ipsum is simply dummy text of the typesetting industry
                                Lorem typesetting
                                industry ipsum. Lorem ipsum is simply dummy text of the
                                typesetting industry
                                Lorem typesetting industry ipsum.
                            </p>
                            <div>
                                <a href="#" class="a-linkTheme">View Details</a>
                            </div>
                        </div>
                    </div>
                    <div class="leaveUser-block">
                        <div class="img">
                            <img src="assets/images/wisdom-ai-small.svg" alt="image">
                        </div>
                        <div>
                            <h6>typesetting industry Lorem typesetting industry ipsum.</h6>
                            <p>Lorem ipsum is simply dummy text of the typesetting industry
                                Lorem typesetting
                                industry ipsum. Lorem ipsum is simply dummy text of the
                                typesetting industry
                                Lorem typesetting industry ipsum.
                            </p>
                            <div>
                                <a href="#" class="a-linkTheme">View Details</a>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div> -->
    </div>
</div>
@include('resorts.GrievanceAndDisciplinery.dashboard._dashboard_styles')
@includeWhen(isset($grievanceInsights), 'resorts.GrievanceAndDisciplinery.dashboard._insight_modals')
@includeWhen(isset($grievanceInsights), 'partials._wai_insight_modals')
@endsection

@section('import-css')
<style>
    /* WAI Insights — same gradient-header treatment as the other modules'
       WAI Insights cards. Narrative (title + body + optional recommendation),
       not pass/fail counts, so no hero — icon is amber when a recommendation
       is present, teal tick otherwise. Fixed height, list scrolls inside. */
    .card-wiINsightGriev {
        height: 100% !important;
        max-height: 460px !important;
        display: flex;
        flex-direction: column;
        padding: 0;
        overflow: hidden;
        border-radius: 16px;
    }
    .card-wiINsightGriev .leaveUser-main {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
    }

    .wai-narrative .wai-head { position: relative; overflow: hidden; padding: 17px 18px; flex-shrink: 0; }
    .wai-narrative .wai-head::before {
        content: ""; position: absolute; inset: 0; pointer-events: none;
        background: linear-gradient(110deg, var(--teal) 0%, #0e8a9e 40%, #7fa61e 70%, var(--lime) 100%);
    }
    .wai-narrative .wai-head::after {
        content: ""; position: absolute; inset: 0; pointer-events: none;
        background: linear-gradient(110deg, rgba(1,40,48,.35), transparent 55%);
    }
    .wai-narrative .wai-head h2 { position: relative; color: #fff; font-size: 18px; font-weight: 600; margin: 0; }
    .wai-narrative .wai-head-meta { position: relative; margin-top: 4px; font-size: 10.5px; font-weight: 500; color: rgba(255,255,255,.75); display: flex; gap: 6px; }
    .wai-narrative .wai-head-meta a { color: #fff; font-weight: 600; text-decoration: underline; }

    .wai-narrative-body { padding: 16px; }
    .wai-narrative .wai-row { display: flex; align-items: flex-start; gap: 12px; padding: 12px 2px; border-bottom: 1px solid #F2F6F6; }
    .wai-narrative .wai-row:last-child { border-bottom: none; }
    .wai-narrative .wai-row-icon { width: 32px; height: 32px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0; margin-top: 2px; }
    .wai-narrative .wai-row-icon.is-ok { background: var(--positive-bg); color: var(--positive); }
    .wai-narrative .wai-row-icon.is-flagged { background: var(--warning-bg); color: var(--warning); }
    .wai-narrative .wai-row-body { flex: 1 1 auto; min-width: 0; }
    .wai-narrative .wai-row-body h6 { margin: 0 0 4px; font-size: 14px; font-weight: 600; color: var(--ink); }
    .wai-narrative .wai-row-text { margin: 0 0 4px; font-size: 14px; color: var(--muted); line-height: 1.5; }
    .wai-narrative .wai-row-link { display: inline-block; margin-top: 2px; font-size: 14px; font-weight: 600; color: var(--teal); }
</style>
@endsection

@section('import-scripts')
<script type="text/javascript">
    $(document).ready(function () {
        $('.data-Table').dataTable({
            "searching": false,
            "bLengthChange": false,
            "bFilter": true,
            "bInfo": false,
            "bAutoWidth": false,
            scrollX: true,
            "iDisplayLength": 10,
        });
    });

    //    equal heigth js
    function equalizeHeights() {
        // Get the elements
        const block1 = document.getElementById('card-breakdownCases');
        const block2_1 = document.getElementById('card-offenseNearingExpiry');
        const block2_2 = document.getElementById('card-wiINsight');

        // Check if elements exist
        if (block1 && block2_1 && block2_2) {
            // Get the height of block1
            const block1Height = block1.offsetHeight;

            // Set the height of block2 elements to match block1's height
            block2_1.style.height = block1Height + 'px';
            block2_2.style.height = block1Height + 'px';
        }
    }

    window.onload = equalizeHeights; // Initial height adjustment

    // Adjust heights on window resize
    window.onresize = equalizeHeights;

    document.addEventListener("DOMContentLoaded", function () {
        const progressBars = document.querySelectorAll('.progress.progress-custom .progress-bar'); // Ensure parent has progress-custom class

        progressBars.forEach((progressBar) => {
            const valueNow = parseInt(progressBar.getAttribute('aria-valuenow'), 10);
            const parentProgress = progressBar.closest('.progress'); // Get the parent .progress element

            // Add specific classes to the parent based on aria-valuenow
            if (valueNow === 100) {
                parentProgress.classList.add('value-100');
            } else if (valueNow === 0) {
                parentProgress.classList.add('value-0');
            }
        });
    });
</script>
<script type="module">
    // The canvases referenced below (appealsByCategory, myDoughnutChartPeopleRelation,
    // breakdownCases) live inside HTML blocks that are currently commented out.
    // Guard each Chart init so getElementById(...).getContext() doesn't crash
    // and prevent the rest of the page JS from running.
    const _appealsByCategoryEl = document.getElementById('appealsByCategory');
    if (_appealsByCategoryEl) {
    const cty = _appealsByCategoryEl.getContext('2d');
    // Wired to live data from the controller. Falls back to a single
    // empty bucket so the canvas still renders if there are no appeals.
    const _appealsLabels = @json($appealsByCategoryLabels ?? []);
    const _appealsData   = @json($appealsByCategoryData ?? []);
    var _pGadH1 = window.WaiChart ? window.WaiChart.palette().teal : '#014653';
    const appealsByCategory = new Chart(cty, {
        type: 'bar',
        data: {
            labels: _appealsLabels.length ? _appealsLabels : ['No appeals yet'],
            datasets: [
                {
                    data: _appealsData.length ? _appealsData : [0],
                    backgroundColor: _pGadH1,
                    borderColor: _pGadH1,
                    borderWidth: 1,
                    borderRadius: 6,
                    barThickness: 25
                },
            ]
        },
        options: {
            plugins: {
                legend: {
                    display: false
                },
                layout: {
                    padding: {
                        top: 0,
                        bottom: 0,
                        left: 0,
                        right: 0
                    }
                },
                tooltip: {
                    enabled: true, // Enable tooltips
                    callbacks: {
                        label: function (tooltipItem) {
                            // const datasetLabel = tooltipItem.dataset.label || '';
                            const value = tooltipItem.raw.toLocaleString(); // Format the value with commas
                            return formatAmount(value, 'USD'); // Custom tooltip format
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true, // Start x-axis at zero
                    grid: {
                        display: false // Hide grid lines on the x-axis
                    },
                    border: {
                        display: true // Show the x-axis border
                    }
                },
                y: {
                    beginAtZero: true, // Do not start y-axis at zero
                    grid: {
                        display: false // Hide grid lines on the y-axis
                    }, ticks: {
                        stepSize: 5,
                    },
                    border: {
                        display: true // Show the y-axis border
                    },
                }
            }
        }
    });
    if (window.WaiChart) window.WaiChart.registerForTheme(appealsByCategory, function (c, p) {
        c.data.datasets[0].backgroundColor = c.data.datasets[0].borderColor = p.teal;
    });


    } // end appealsByCategory guard

    const _doughnutEl = document.getElementById('myDoughnutChartPeopleRelation');
    if (_doughnutEl) {
    var ctx = _doughnutEl.getContext('2d');

    // Custom plugin only registered for this chart
    const doughnutLabelsInside = {
        id: 'doughnutLabelsInside',
        afterDraw: function (chart) {
            var ctx = chart.ctx;
            chart.data.datasets.forEach(function (dataset, i) {
                var meta = chart.getDatasetMeta(i);
                if (!meta.hidden) {
                    meta.data.forEach(function (element, index) {
                        var dataValue = dataset.data[index];
                        var label = chart.data.labels[index];

                        var total = dataset.data.reduce(function (acc, val) {
                            return acc + val;
                        }, 0);
                        var percentage = ((dataValue / total) * 100) + '%';

                        var position = element.tooltipPosition();

                        ctx.fillStyle = '#fff';
                        ctx.font = 'bold 16px Poppins';
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';

                        ctx.fillText(percentage, position.x, position.y);
                    });
                }
            });
        }
    };

    // Live counts from the controller. If both are zero, seed [1,0] so
    // the doughnut still renders (Chart.js draws nothing for [0,0]).
    const _hearingsPending  = parseInt(@json($hearingsPending ?? 0), 10) || 0;
    const _hearingsResolved = parseInt(@json($hearingsResolved ?? 0), 10) || 0;
    const _hearingsData = (_hearingsPending + _hearingsResolved) === 0
        ? [1, 0]
        : [_hearingsPending, _hearingsResolved];
    var _pGadH2 = window.WaiChart ? window.WaiChart.palette() : { teal: '#014653', aqua: '#2EACB3' };
    var myDoughnutChartPeopleRelation = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Resolved'],
            datasets: [{
                data: _hearingsData,
                backgroundColor: [_pGadH2.teal, _pGadH2.aqua], borderWidth: 0 // Removes the border
            }]
        },
        options: {
            responsive: true,
            plugins: {
                doughnutLabelsInside: true, // Enable the custom plugin
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
            },
            hover: {
                onHover: function (event, activeElements) {
                    if (activeElements.length > 0) {
                        const chartSegment = activeElements[0];
                        chartSegment.element.options.hoverOffset = 100;
                    } else {
                        myDoughnutChartPeopleRelation.data.datasets[0].hoverOffset = 10;
                    }
                    myDoughnutChartPeopleRelation.update();
                }
            },
            hoverOffset: 30
        },
        plugins: [doughnutLabelsInside] // Attach the plugin to this chart only
    });
    if (window.WaiChart) window.WaiChart.registerForTheme(myDoughnutChartPeopleRelation, function (c, p) {
        c.data.datasets[0].backgroundColor = [p.teal, p.aqua];
    });

    } // end myDoughnutChartPeopleRelation guard

    const _breakdownEl = document.getElementById('breakdownCases');
    if (_breakdownEl) {
    const ctz = _breakdownEl.getContext('2d');
    var _pGadH3 = window.WaiChart ? window.WaiChart.palette().teal : '#014653';
    const breakdownCases = new Chart(ctz, {
        type: 'bar',
        data: {
            labels: ['Category 1', 'Category 2', 'Category 3', 'Category 4', 'Category 5', 'Category 6', 'Category 7',],
            datasets: [
                {
                    // label: 'Preplannned OT',
                    data: [80, 70, 90, 76, 96, 62, 80, 90, 74, 80, 90, 60],
                    backgroundColor: _pGadH3,
                    borderColor: _pGadH3,
                    borderWidth: 1,
                    borderRadius: 6,
                    barThickness: 25
                },
            ]
        },
        options: {
            plugins: {
                legend: {
                    display: false
                },
                layout: {
                    padding: {
                        top: 0,
                        bottom: 0,
                        left: 0,
                        right: 0
                    }
                },
                tooltip: {
                    enabled: true, // Enable tooltips
                    callbacks: {
                        label: function (tooltipItem) {
                            // const datasetLabel = tooltipItem.dataset.label || '';
                            const value = tooltipItem.raw.toLocaleString(); // Format the value with commas
                            return formatAmount(value, 'USD'); // Custom tooltip format
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true, // Start x-axis at zero
                    grid: {
                        display: false // Hide grid lines on the x-axis
                    },
                    border: {
                        display: true // Show the x-axis border
                    }
                },
                y: {
                    beginAtZero: true, // Do not start y-axis at zero
                    grid: {
                        display: false // Hide grid lines on the y-axis
                    }, ticks: {
                        stepSize: 20,
                    },
                    border: {
                        display: true // Show the y-axis border
                    },
                }
            }
        }
    });
    if (window.WaiChart) window.WaiChart.registerForTheme(breakdownCases, function (c, p) {
        c.data.datasets[0].backgroundColor = c.data.datasets[0].borderColor = p.teal;
    });
    } // end breakdownCases guard
</script>
@endsection
