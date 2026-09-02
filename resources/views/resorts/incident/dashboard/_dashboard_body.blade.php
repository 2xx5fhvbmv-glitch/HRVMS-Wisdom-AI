{{--
    Shared Incident Dashboard body — one partial included identically by
    hrdashboard.blade.php, hoddashboard.blade.php (also used for XCOM, since
    excom_dashboard() renders hoddashboard.blade.php directly), and
    admindashboard.blade.php, so every role sees the same design. Per-role
    differences are handled by which variables the *controller* already
    computes for that role — this partial never queries anything itself,
    it only decides how to display what it's given:

    - $incidentInsights (isset) — only HR_Dashobard() computes this today,
      so the WAI Insights card only renders there. hod_dashboard() /
      admin_dashboard() don't call getCachedIncidentInsights() — adding
      that is a controller change, out of scope for this frontend-only
      pass. Flagged back to the user rather than added unprompted.
    - $committeeSummary is gated behind Common::hasFullDataAccess() — for
      HOD it's computed resort-wide (every committee's incidents,
      unfiltered by department; see hod_dashboard() in
      DashboardController.php), so rendering it on the HOD dashboard would
      leak cross-department data. hasFullDataAccess() is false for
      HOD/XCOM, so this card is simply never shown to them — matches the
      dept-scoping rule in CLAUDE.md's invariant #5 and is exactly why the
      original hoddashboard.blade.php never rendered it either, despite
      the variable being passed.
    - $kpi2Label / $kpi2Value — HR/Admin's second KPI tile is "Open
      Incidents" ($open_incidents = anything not Resolved); HOD's is a
      narrower "Pending Incidents" ($pending_incidents = status=Reported
      only). Different metric, so it keeps its own true label rather than
      being relabelled to match HR's.
--}}
@php
    // ---- Incidents by Category: top 6 + "Other" (scales to any count) ----
    $dbiCatPairs = array_combine($categoryLabels ?? [], $categoryData ?? []);
    arsort($dbiCatPairs);
    $dbiCatTotal = array_sum($dbiCatPairs);
    $dbiCatTop = array_slice($dbiCatPairs, 0, 6, true);
    $dbiCatRest = array_slice($dbiCatPairs, 6, null, true);
    $dbiCatOtherSum = array_sum($dbiCatRest);
    $dbiCatMax = $dbiCatTop ? max($dbiCatTop) : 0;

    // ---- Incident Severity — bar length relative to the largest bucket ----
    $dbiSeverityMax = max($severityCounts['Minor'] ?? 0, $severityCounts['Moderate'] ?? 0, $severityCounts['Severe'] ?? 0);

    // ---- Incident Meeting Schedule donut — Resolved vs Unresolved ----
    $dbiMeetingTotal = ($resolvedCount ?? 0) + ($unresolvedCount ?? 0);
    $dbiResolvedPct = $dbiMeetingTotal > 0 ? round(($resolvedCount / $dbiMeetingTotal) * 100) : 0;

    $dbiShowCommittee = !empty($committeeSummary) && \App\Helpers\Common::hasFullDataAccess();
@endphp
<div class="dbi-wrap">

    {{-- KPI row --}}
    <div class="dbi-kpis">
        <a class="dbi-kpi" href="{{ route('incident.index') }}">
            <span class="go"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
            <div class="num tnum">{{ $total_incidents ?? 0 }}</div>
            <div class="lbl">Total Incidents</div>
            <span class="hint neu"><span class="d"></span>All time</span>
        </a>
        <a class="dbi-kpi" href="{{ route('incident.index') }}">
            <span class="go"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
            <div class="num tnum">{{ $kpi2Value ?? ($open_incidents ?? 0) }}</div>
            <div class="lbl">{{ $kpi2Label ?? 'Open Incidents' }}</div>
            <span class="hint {{ ($kpi2Value ?? $open_incidents ?? 0) > 0 ? 'warn' : 'ok' }}"><span class="d"></span>{{ ($kpi2Value ?? $open_incidents ?? 0) > 0 ? 'Needs action' : 'On track' }}</span>
        </a>
        <a class="dbi-kpi" href="{{ route('incident.index') }}">
            <span class="go"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
            <div class="num tnum">{{ $under_investigation_incidents ?? 0 }}</div>
            <div class="lbl">Under Investigation</div>
            <span class="hint {{ ($under_investigation_incidents ?? 0) > 0 ? 'warn' : 'neu' }}"><span class="d"></span>{{ ($under_investigation_incidents ?? 0) > 0 ? 'Needs action' : 'None active' }}</span>
        </a>
        <a class="dbi-kpi" href="{{ route('incident.index') }}">
            <span class="go"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
            <div class="num tnum">{{ $averageResolutionDays ? round($averageResolutionDays, 1) : 0 }} <small>Business Days</small></div>
            <div class="lbl">Avg Resolution Time</div>
            <span class="hint ok"><span class="d"></span>On track</span>
        </a>
    </div>

    <div class="dbi-grid">

        {{-- ===== TIER 1 · PRIORITY ===== --}}
        <div class="dbi-tier"><span class="tl">Priority</span><span class="ts">Act on these first</span></div>

        @if(isset($incidentInsights))
            {{-- WAI Insights — same shared modal hooks (.lnk / .lnk-rec /
                 data-details) as before, just re-skinned. Regenerate stays
                 the exact same plain query-string reload. --}}
            <div class="dbi-card dbi-wai dbi-sp2">
                <div class="dbi-wai-head">
                    @php $dbiMeta = $incidentInsights['_meta'] ?? null; @endphp
                    <div class="wt">WAI Insights</div>
                    @if($dbiMeta)
                        <div class="ws">
                            Updated {{ $dbiMeta['generated_at']->diffForHumans() }}
                            @if($dbiMeta['can_regenerate'])
                                &middot; <a href="?regenerate_insights=1">Regenerate</a>
                            @else
                                &middot; <span title="{{ $dbiMeta['next_available']->format('d M Y, H:i') }}">Regenerate {{ $dbiMeta['next_available']->diffForHumans() }}</span>
                            @endif
                        </div>
                    @endif
                </div>
                <div class="dbi-wai-body">
                    @foreach([['key'=>'volume','modal'=>'incidentInsightVolumeModal'],['key'=>'hotspots','modal'=>'incidentInsightHotspotsModal'],['key'=>'outcomes','modal'=>'incidentInsightOutcomesModal']] as $dbiIc)
                        @php $dbiHasRec = !empty($incidentInsights[$dbiIc['key']]['recommendation']); @endphp
                        <div class="dbi-wai-item">
                            <div class="dbi-wai-ic"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M20 6L9 17l-5-5"/></svg></div>
                            <div>
                                <div class="wtitle">{{ $incidentInsights[$dbiIc['key']]['title'] ?? '' }}</div>
                                <div class="wdesc">{{ $incidentInsights[$dbiIc['key']]['body'] ?? '' }}</div>
                                <div class="dbi-lnkrow">
                                    @if($dbiHasRec)
                                        <button type="button" class="lnk-rec dbi-wlink"
                                            data-title="{{ $incidentInsights[$dbiIc['key']]['title'] ?? '' }}"
                                            data-rec="{{ $incidentInsights[$dbiIc['key']]['recommendation'] }}"
                                            data-details="{{ $dbiIc['modal'] }}">View recommendation &rarr;</button>
                                    @else
                                        <a href="#" class="lnk dbi-wlink" data-details="{{ $dbiIc['modal'] }}">View details &rarr;</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Incident List --}}
        <div class="dbi-card dbi-card-flush dbi-sp2">
            <div class="dbi-card-h"><div class="ttl">Incident List <em>Most recent reports</em></div><a class="dbi-viewall" href="{{ route('incident.index') }}">View all &rarr;</a></div>
            <div id="dbiIncidentList"><div class="dbi-empty"><div class="t">Loading&hellip;</div></div></div>
        </div>

        {{-- Resolution Timelines --}}
        <div class="dbi-card dbi-sp2">
            <div class="dbi-card-h"><div class="ttl">Resolution Timelines</div></div>
            <div class="dbi-res"><div class="l"><span class="ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>Cases Nearing Deadline</div><span class="v tnum" id="casesNearingDeadline">&ndash;</span></div>
            <div class="dbi-res"><div class="l"><span class="ic err"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4M12 17h.01M10.3 3.9L2 18a2 2 0 001.7 3h16.6a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg></span>Breached Timelines</div><span class="v err tnum" id="breachedTimelines">&ndash;</span></div>
            <div class="dbi-res"><div class="l"><span class="ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg></span>Resolved Cases</div><span class="v tnum" id="resolvedCases">&ndash;</span></div>
            <div class="dbi-res"><div class="l"><span class="ic warn"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7l2-2h5l2 2h7a1 1 0 011 1v9a2 2 0 01-2 2H4a2 2 0 01-2-2V7z"/></svg></span>Open Investigations</div><span class="v tnum" id="openInvestigations">&ndash;</span></div>
        </div>

        {{-- Pending Resolution Approval --}}
        <div class="dbi-card dbi-sp2">
            <div class="dbi-card-h"><div class="ttl">Pending Resolution Approval</div><a class="dbi-viewall" href="{{ route('incident.pending-approvals') }}">View all &rarr;</a></div>
            <div id="dbiPendingResolutions"><div class="dbi-empty"><div class="t">Loading&hellip;</div></div></div>
        </div>

        {{-- ===== TIER 2 · ANALYSIS ===== --}}
        <div class="dbi-tier"><span class="tl">Analysis</span><span class="ts">Understand the pattern</span></div>

        {{-- Incidents by Category — ranked bars (top 6 + Other, scales past 20) --}}
        <div class="dbi-card">
            <div class="dbi-card-h"><div class="ttl">Incidents by Category</div><span class="dbi-ttl-meta">{{ $dbiCatTotal }} total</span></div>
            <div class="dbi-catbars">
                @forelse($dbiCatTop as $dbiCatName => $dbiCatCount)
                    <div class="dbi-catrow">
                        <div class="crh"><span class="nm">{{ $dbiCatName }}</span><span class="val tnum">{{ $dbiCatCount }}<em>{{ $dbiCatTotal > 0 ? round(($dbiCatCount / $dbiCatTotal) * 100) : 0 }}%</em></span></div>
                        <div class="track"><i style="width:{{ $dbiCatMax > 0 ? round(($dbiCatCount / $dbiCatMax) * 100) : 0 }}%"></i></div>
                    </div>
                @empty
                    <div class="dbi-empty"><div class="t">No categorized incidents yet</div></div>
                @endforelse
                @if(count($dbiCatRest))
                    <div class="dbi-catrow other">
                        <div class="crh"><span class="nm">Other &middot; {{ count($dbiCatRest) }} {{ Str::plural('category', count($dbiCatRest)) }}</span><span class="val tnum">{{ $dbiCatOtherSum }}<em>{{ $dbiCatTotal > 0 ? round(($dbiCatOtherSum / $dbiCatTotal) * 100) : 0 }}%</em></span></div>
                        <div class="track"><i style="width:{{ $dbiCatMax > 0 ? round(($dbiCatOtherSum / $dbiCatMax) * 100) : 0 }}%"></i></div>
                    </div>
                @endif
                @if(count($categoryLabels ?? []) > 6)
                    <a href="{{ route('incident.index') }}" class="dbi-viewall dbi-viewall-block">View all {{ count($categoryLabels) }} &rarr;</a>
                @endif
            </div>
        </div>

        {{-- Incident Severity --}}
        <div class="dbi-card">
            <div class="dbi-card-h"><div class="ttl">Incident Severity</div></div>
            <div class="dbi-stat"><span class="dot" style="background:var(--positive)"></span><span class="nm">Minor</span><span class="track"><i style="width:{{ $dbiSeverityMax > 0 ? round((($severityCounts['Minor'] ?? 0) / $dbiSeverityMax) * 100) : 0 }}%;background:var(--positive)"></i></span><span class="v tnum">{{ $severityCounts['Minor'] ?? 0 }}</span></div>
            <div class="dbi-stat"><span class="dot" style="background:var(--warning)"></span><span class="nm">Moderate</span><span class="track"><i style="width:{{ $dbiSeverityMax > 0 ? round((($severityCounts['Moderate'] ?? 0) / $dbiSeverityMax) * 100) : 0 }}%;background:var(--warning)"></i></span><span class="v tnum">{{ $severityCounts['Moderate'] ?? 0 }}</span></div>
            <div class="dbi-stat"><span class="dot" style="background:var(--error)"></span><span class="nm">Severe</span><span class="track"><i style="width:{{ $dbiSeverityMax > 0 ? round((($severityCounts['Severe'] ?? 0) / $dbiSeverityMax) * 100) : 0 }}%;background:var(--error)"></i></span><span class="v tnum">{{ $severityCounts['Severe'] ?? 0 }}</span></div>
        </div>

        {{-- Incident Trends (line, built from getIncidentTrends()/gethodIncidentTrends() JSON — {labels:[], data:[]}) --}}
        <div class="dbi-card dbi-sp2">
            <div class="dbi-card-h"><div class="ttl">Incident Trends</div><select class="dbi-sel" id="dbiTrendYear"></select></div>
            <div id="dbiTrendChart" class="dbi-line"><div class="dbi-empty"><div class="t">Loading&hellip;</div></div></div>
        </div>

        {{-- ===== TIER 3 · OVERVIEW ===== --}}
        <div class="dbi-tier"><span class="tl">Overview</span><span class="ts">Supporting context</span></div>

        @if($dbiShowCommittee)
            {{-- Delegated Cases to Committee — full-access roles only (HR/GM/Admin).
                 Resort-wide by design for this role; never shown to HOD/XCOM
                 since their committeeSummary isn't department-scoped
                 (see hod_dashboard() in the controller) — showing it there
                 would leak other departments' committee case counts. --}}
            <div class="dbi-card dbi-sp2">
                <div class="dbi-card-h"><div class="ttl">Delegated Cases to Committee</div></div>
                <table class="dbi-table">
                    <thead><tr><th>Committee Name</th><th class="c">Open Cases</th><th class="r">Status</th></tr></thead>
                    <tbody>
                        @forelse($committeeSummary as $dbiRow)
                            <tr>
                                <td class="name">{{ $dbiRow['name'] }}</td>
                                <td class="cases {{ $dbiRow['open'] == 0 ? 'zero' : '' }}">{{ $dbiRow['open'] }}</td>
                                <td class="status">
                                    @if($dbiRow['status'] === 'No Incidents')
                                        <span class="none">&mdash;</span>
                                    @else
                                        <span class="dbi-tagpill"><span class="d"></span>{{ $dbiRow['status'] }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="name">No committees configured</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Incident Meeting Schedule — pure CSS conic-gradient donut, Resolved vs Unresolved --}}
        <div class="dbi-card">
            <div class="dbi-card-h"><div class="ttl">Incident Meeting Schedule</div></div>
            <div class="dbi-donut-row">
                <div class="dbi-donut" style="background:conic-gradient(var(--teal) 0 {{ $dbiResolvedPct }}%, var(--teal-3) {{ $dbiResolvedPct }}% 100%)">
                    <div class="hole"><b class="tnum">{{ $dbiResolvedPct }}%</b><span>Resolved</span></div>
                </div>
                <div class="dbi-legend">
                    <div class="lg"><span class="sq" style="background:var(--teal)"></span>Resolved<span class="pc">{{ $dbiResolvedPct }}%</span><span class="lv tnum">{{ $resolvedCount ?? 0 }}</span></div>
                    <div class="lg"><span class="sq" style="background:var(--teal-3)"></span>Unresolved<span class="pc">{{ 100 - $dbiResolvedPct }}%</span><span class="lv tnum">{{ $unresolvedCount ?? 0 }}</span></div>
                </div>
            </div>
        </div>

        {{-- Upcoming Meetings --}}
        <div class="dbi-card">
            <div class="dbi-card-h"><div class="ttl">Upcoming Meetings</div><a class="dbi-viewall" href="{{ route('incident.meeting') }}">View all &rarr;</a></div>
            <div id="dbiUpcomingMeetings"><div class="dbi-empty"><div class="t">Loading&hellip;</div></div></div>
        </div>

        {{-- Department-wise Participation — pure CSS stacked columns, categorical ramp --}}
        <div class="dbi-card dbi-sp2">
            <div class="dbi-card-h"><div class="ttl">Department-wise Participation</div><span class="dbi-ttl-meta" id="dbiDeptYear">{{ date('Y') }}</span></div>
            <div id="dbiDeptChart" class="dbi-donut-row" style="align-items:flex-start"><div class="dbi-empty"><div class="t">Loading&hellip;</div></div></div>
        </div>

        {{-- Preventive Measures --}}
        <div class="dbi-card dbi-sp2">
            <div class="dbi-card-h"><div class="ttl">Preventive Measures</div><a class="dbi-viewall" href="{{ $preventiveViewAllRoute ?? route('incident.preventive') }}">View all &rarr;</a></div>
            <div id="dbiPreventiveList"><div class="dbi-empty"><div class="t">Loading&hellip;</div></div></div>
        </div>

    </div>
</div>
