@extends('resorts.layouts.app')
@section('page_tab_title' ,"Dashboard")

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@php
    // Tiny inline-SVG icon set — currentColor, no icon font dependency. Defined
    // here (not in import-scripts) because Blade sections execute top-to-bottom
    // as the file runs, and 'content' below needs these before 'import-scripts' runs.
    $arrowIcon = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6l6 6-6 6"/></svg>';
    $clockIcon = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8v5l3 2"/><circle cx="12" cy="12" r="9"/></svg>';
    $checkIcon = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>';
    $warnIcon  = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="8" x2="12" y2="13"/><circle cx="12" cy="16.5" r="1" fill="currentColor" stroke="none"/></svg>';
@endphp

@section('content')
<style>
    /* Same requested push as the Payroll / Talent Acquisition / People /
       Time and Attendance / Leave / Performance dashboards — extra
       breathing room between the hero and the stat-card grid below it,
       scoped to this page (.page-hedding's own margin-bottom is shared by
       every page's hero). padding-bottom, not margin: adjacent sibling
       margins collapse to the larger of the two rather than summing.
       Below Bootstrap's sm breakpoint the extra padding pushes the first
       stat card into the teal hero curve's rounded bottom-left corner
       (body::before, border-radius 0 0 50px 50px) — same collision found
       on Payroll — neutralized below 576px. */
    #learning-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #learning-hero { padding-bottom: 0; }
    }
</style>
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding" id="learning-hero">
                <div class="row  g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Learning & Development</span>
                            <h1>Dashboard</h1>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stat cards --}}
            <div class="ld-grid ld-stats">
                <div class="card ld-stat ld-stat-hero">
                    <span class="ld-stat-badge">Compliance</span>
                    <div class="ld-stat-label">Completed Compulsory Learning</div>
                    <div class="ld-stat-val">70<span>%</span></div>
                    <div class="ld-stat-track"><i style="width:70%"></i></div>
                    <div class="ld-stat-sub">Mandatory compliance across all programs</div>
                </div>
                <a href="{{route('learning.schedule.index')}}?status=Scheduled" class="card ld-stat">
                    <div class="ld-stat-label">Scheduled Learning<span class="ld-chev">{!! $arrowIcon !!}</span></div>
                    <div class="ld-stat-val">{{$scheduled_trainings_count ?? 0}}</div>
                    <div class="ld-stat-sub">Upcoming programs</div>
                </a>
                <a href="{{route('training.history')}}?status=Completed" class="card ld-stat">
                    <div class="ld-stat-label">Completed Learning Programs<span class="ld-chev">{!! $arrowIcon !!}</span></div>
                    <div class="ld-stat-val">{{$completed_trainings_count ?? 0}}</div>
                    <div class="ld-stat-sub">Across all categories</div>
                </a>
                <a href="{{route('learning.request.index')}}?status=Pending" class="card ld-stat">
                    <div class="ld-stat-label">Pending Learning Programs<span class="ld-chev">{!! $arrowIcon !!}</span></div>
                    <div class="ld-stat-val">{{$pending_trainings_count ?? 0}}</div>
                    <div class="ld-stat-sub">{{ ($pending_trainings_count ?? 0) > 0 ? 'Awaiting action' : 'Nothing awaiting action' }}</div>
                </a>
            </div>

            {{-- Combined middle grid — 3 columns x 2 rows. Pending Actions / WAI
                 Insights / Feedback / Onboarding auto-place into the 4 left+center
                 cells in DOM order; Calendar is the only explicitly-positioned item
                 (column 3, spanning both rows), so it naturally stretches to match
                 the combined height of the other two columns — no JS, no height
                 hack, just how CSS grid track-stretching works. --}}
            <div class="ld-grid ld-combo">
                <div class="card ld-card ld-pa-card @if(Common::checkRouteWisePermission('learning.request.add',config('settings.resort_permissions.view')) == false) d-none @endif" id="card-pendingActions">
                    <div class="ld-card-head">
                        <span class="ld-card-title">Pending Actions</span>
                        <a href="{{route('learning.request.index')}}" class="ld-viewall">View all</a>
                    </div>
                    @if($pending_learning_request && count($pending_learning_request))
                        <div class="ld-pa-list">
                            @foreach($pending_learning_request->take(4) as $request)
                                <div class="ld-pa">
                                    <div class="ld-pa-ic">{!! $clockIcon !!}</div>
                                    <div class="ld-pa-body">
                                        <div class="ld-pa-t">{{$request->learning->name}}</div>
                                        <div class="ld-pa-d">{{ \Illuminate\Support\Str::words($request->learning->description, 24, '…') }}</div>
                                        <a href="{{ route('learning.request.details', ['id' => $request->id]) }}" class="ld-lnk">View details &rarr;</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="ld-empty">
                            <div class="ld-empty-ring">{!! $checkIcon !!}</div>
                            <div class="ld-empty-t">All caught up</div>
                            <div class="ld-empty-s">No pending requests right now.</div>
                        </div>
                    @endif
                </div>

                {{-- WAI Insights — gradient shell kept, row content restructured to title / issue / view-recommendation+view-details --}}
                <div class="card ld-wai" id="card-wiINsightLearning">
                    @php $lMeta = $learningInsights['_meta'] ?? null; @endphp
                    <div class="ld-wai-head">
                        <div class="ld-wai-h">WAI Insights</div>
                        @if($lMeta)
                            <div class="ld-wai-meta">
                                <span>Updated {{ $lMeta['generated_at']->diffForHumans() }}</span>
                                @if($lMeta['can_regenerate'])
                                    <a href="?regenerate_insights=1">&middot; Regenerate</a>
                                @else
                                    <span title="{{ $lMeta['next_available']->format('d M Y, H:i') }}">&middot; Regenerate {{ $lMeta['next_available']->diffForHumans() }}</span>
                                @endif
                            </div>
                        @endif
                    </div>
                    <div class="ld-wai-body">
                        @foreach(['completion','mandatory','requests','probationary'] as $lcKey)
                            @php $lc = ['key' => $lcKey, 'modal' => 'learningInsight' . ucfirst($lcKey) . 'Modal']; @endphp
                            @php $hasRecommendation = !empty($learningInsights[$lc['key']]['recommendation']); @endphp
                            <div class="ld-ins">
                                <div class="ld-ins-ic {{ $hasRecommendation ? 'is-flagged' : 'is-ok' }}">
                                    {!! $hasRecommendation ? $warnIcon : $checkIcon !!}
                                </div>
                                <div class="ld-ins-body">
                                    <div class="ld-ins-tt">{{ $learningInsights[$lc['key']]['title'] ?? '' }}</div>
                                    <div class="ld-ins-issue">{{ $learningInsights[$lc['key']]['body'] ?? '' }}</div>
                                    <div class="lnkrow">
                                        @if($hasRecommendation)
                                            <button type="button" class="lnk-rec"
                                                data-title="{{ $learningInsights[$lc['key']]['title'] ?? '' }}"
                                                data-rec="{{ $learningInsights[$lc['key']]['recommendation'] }}"
                                                data-details="{{ $lc['modal'] }}">View recommendation &rarr;</button>
                                            <span class="sep"></span>
                                        @endif
                                        <a href="#" class="lnk" data-details="{{ $lc['modal'] }}">View details &rarr;</a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="card ld-card ld-cal-card @if(Common::checkRouteWisePermission('learning.calendar.index',config('settings.resort_permissions.view')) == false) d-none @endif" id="right-ldDash">
                    <div id="calendar"></div>
                    <div class="ld-card-head ld-sessions-head">
                        <span class="ld-card-title">Upcoming Learning Sessions</span>
                        <a href="{{route('learning.schedule.index')}}" class="ld-viewall">View all &rarr;</a>
                    </div>
                    <div id="leaveUser-main" class="ld-sess-list">
                        <p class="ld-loading-text">Loading sessions&hellip;</p>
                    </div>
                </div>

                <div class="card ld-card" id="card-feedbackEvaluationHR">
                    <div class="ld-card-head"><span class="ld-card-title">Feedback and Evaluation</span></div>
                    <div class="ld-gauge-wrap">
                        <div class="ld-gauge-canvas"><canvas id="gaugeFeedback"></canvas>
                            <div class="ld-gauge-center">
                                <div class="ld-gauge-big" style="color:var(--faint)">{{ is_null($feedbackAvgScore) ? '—' : ($feedbackAvgScore . '%') }}</div>
                                <div class="ld-gauge-lbl">Average feedback scores</div>
                            </div>
                        </div>
                    </div>
                    <div class="ld-gauge-foot"><span>Over time</span><span>Trainer performance</span></div>
                </div>

                <div class="card ld-card">
                    <div class="ld-card-head"><span class="ld-card-title">Onboarding Learning Progress</span></div>
                    <div class="ld-gauge-wrap">
                        <div class="ld-gauge-canvas"><canvas id="gaugeOnboard"></canvas>
                            <div class="ld-gauge-center">
                                <div class="ld-gauge-big">{{ is_null($onboardingProgress) ? '—' : ($onboardingProgress . '%') }}</div>
                                <div class="ld-gauge-lbl">New hires completing compulsory training</div>
                            </div>
                        </div>
                    </div>
                    <div class="ld-gauge-foot"><span>Compulsory modules</span><span>{{ $onboardingProgress ?? 0 }}% complete</span></div>
                </div>
            </div>

            {{-- Learning Hours + Completion Rates --}}
            <div class="ld-grid ld-tworow">
                <div class="card ld-card @if(Common::checkRouteWisePermission('learning.programs.index',config('settings.resort_permissions.view')) == false) d-none @endif">
                    <div class="ld-card-head"><span class="ld-card-title">Learning Hours</span></div>
                    @php $hasLearningHours = ($learningHoursByProg ?? collect())->count() > 0; @endphp
                    @if($hasLearningHours)
                        <div class="ld-chart-box"><canvas id="myStackedBarChart"></canvas></div>
                    @else
                        <p class="text-muted small mb-0">No training programs scheduled yet.</p>
                    @endif
                </div>

                <div class="card ld-card @if(Common::checkRouteWisePermission('learning.request.add',config('settings.resort_permissions.view')) == false) d-none @endif">
                    <div class="ld-card-head">
                        <span class="ld-card-title">Learning Completion Rates</span>
                        <a href="{{route('training.history')}}" class="ld-viewall">View all &rarr;</a>
                    </div>
                    @php
                        // $completionData has one row per scheduled instance, so the same
                        // program can repeat — group by name and average the rate, then cap
                        // to the first 4 so the card stays a fixed, readable height.
                        $completionByProgram = collect($completionData ?? [])
                            ->groupBy('training_name')
                            ->map(fn($rows) => [
                                'training_name' => $rows->first()['training_name'],
                                'completion_rate' => round($rows->avg('completion_rate')),
                            ])
                            ->values();
                        $completionShown = $completionByProgram->take(4);
                        $completionRestCount = max(0, $completionByProgram->count() - $completionShown->count());
                    @endphp
                    @if($completionShown->isNotEmpty())
                        <div class="ld-track-list">
                            @foreach($completionShown as $i => $data)
                                <div class="ld-track">
                                    <div class="ld-track-top">
                                        <span class="ld-track-nm">{{ $data['training_name'] }}</span>
                                        <span class="ld-track-val" style="color:var(--ld-ramp-{{ $i % 5 }})">{{ $data['completion_rate'] }}<small>%</small></span>
                                    </div>
                                    <div class="ld-pips">
                                        @php $ldRampIdx = $i % 5; @endphp
                                    @for($p = 0; $p < 10; $p++)
                                            <i style="background:{{ $p < round($data['completion_rate'] / 10) ? "var(--ld-ramp-{$ldRampIdx})" : 'var(--line-2)' }}"></i>
                                        @endfor
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if($completionRestCount > 0)
                            <div class="ld-others"><span>+ {{ $completionRestCount }} more programs</span><a href="{{route('training.history')}}" class="ld-viewall">View all &rarr;</a></div>
                        @endif
                    @else
                        <p class="text-muted small mb-0">No completion data yet.</p>
                    @endif
                </div>
            </div>

            {{-- Learning History + Learning Attendance. History's own entry grid is
                 capped to a fixed height with internal scroll (.ld-history) so it
                 stays the same height as Attendance regardless of entry count —
                 2 per row is always visible, more scroll under a thin scrollbar. --}}
            <div class="ld-grid ld-histrow @if(Common::checkRouteWisePermission('learning.schedule',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card ld-card" id="card-trainingHistory">
                    <div class="ld-card-head">
                        <span class="ld-card-title">Learning History</span>
                        <a href="{{ route('training.history') }}" class="ld-viewall">View all</a>
                    </div>
                    <div class="ld-grid ld-history">
                        @if($trainings->isEmpty())
                            <p class="text-muted small mb-0">No training history available.</p>
                        @else
                            @foreach ($trainings->take(5) as $training)
                                @php
                                    $totalTrainingDays = \Carbon\Carbon::parse($training->start_date)->diffInDays(\Carbon\Carbon::parse($training->end_date)) + 1;
                                    $totalParticipants = $training->participants->count();
                                    $totalExpectedAttendance = $totalTrainingDays * $totalParticipants;
                                    $actualAttendance = $training->trainingAttendances->where('status', 'Present')->count();
                                    $attendancePercentage = ($totalExpectedAttendance > 0)
                                        ? round(($actualAttendance / $totalExpectedAttendance) * 100, 2)
                                        : 0;
                                @endphp
                                <div class="ld-hist">
                                    <div class="ld-hist-range">{{ date('d M Y', strtotime($training->start_date)) . ' – ' . date('d M Y', strtotime($training->end_date)) }}</div>
                                    <div class="ld-hist-t">{{ $training->learningProgram->name ?? 'Learning Program' }}</div>
                                    <div class="ld-hist-d">{{ \Illuminate\Support\Str::words($training->description, 22, '…') }}</div>
                                    <div class="ld-hist-foot">
                                        <span class="ld-hist-att">Attendance
                                            @if($attendancePercentage == 100)
                                                <b class="ok">{{ $attendancePercentage }}%</b>
                                            @else
                                                <span class="ld-status-pill {{ $attendancePercentage == 0 ? 'warn' : '' }}">{{ $attendancePercentage }}%</span>
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>

                <div class="card ld-card @if(Common::checkRouteWisePermission('learning.programs.index',config('settings.resort_permissions.view')) == false) d-none @endif">
                    <div class="ld-card-head">
                        <span class="ld-card-title">Learning Attendance</span>
                        <a href="{{route('training.history')}}" class="ld-viewall">View all &rarr;</a>
                    </div>
                    <p id="lateAttendanceText" class="ld-late-text">Late attendance: &mdash;</p>
                    <div class="ld-dial-list" id="attList"><p class="ld-loading-text">Loading&hellip;</p></div>
                    <div class="ld-others" id="attOthersRow" style="display:none"><span>Others</span><b id="attOthers"></b></div>
                </div>
            </div>

        </div>
    </div>

    @include('partials._wai_insight_modals')

@includeWhen(isset($learningInsights), 'resorts.learning.dashboard._insight_modals')
@endsection

@section('import-css')
<style>
    /* Category ramp — teal family + one muted positive/warning + neutral. No blue. */
    :root {
        --ld-ramp-0: var(--teal);
        --ld-ramp-1: var(--teal-bright, #2EACB3);
        --ld-ramp-2: var(--positive);
        --ld-ramp-3: var(--warning);
        --ld-ramp-4: var(--faint);
    }

    /* Layout grids — CSS Grid throughout the body (stat cards down), matching
       the reference exactly rather than Bootstrap's row/col system. */
    .ld-grid { display: grid; gap: 16px; margin-bottom: 16px; }
    .ld-stats { grid-template-columns: repeat(4, 1fr); }
    .ld-stat { display: block; padding: 18px 20px; text-decoration: none; color: inherit; border-radius: 16px; }
    .ld-stat-label { font-size: 14px; font-weight: 600; color: var(--muted); display: flex; align-items: center; justify-content: space-between; }
    .ld-chev { width: 26px; height: 26px; border-radius: 50%; background: var(--line-2); display: grid; place-items: center; color: var(--muted); }
    .ld-chev svg { width: 13px; height: 13px; }
    .ld-stat-val { font-size: 32px; font-weight: 600; letter-spacing: -.5px; margin-top: 14px; line-height: 1; color: var(--ink); font-variant-numeric: tabular-nums; }
    .ld-stat-val span { font-size: 14px; color: var(--muted); }
    .ld-stat-sub { font-size: 11px; color: var(--faint); margin-top: 6px; }
    .ld-stat-hero { position: relative; }
    .ld-stat-badge { position: absolute; top: 16px; right: 18px; font-size: 10px; font-weight: 600; letter-spacing: .5px; text-transform: uppercase; color: var(--teal); background: var(--teal-3); padding: 3px 8px; border-radius: 20px; }
    .ld-stat-track { height: 5px; border-radius: 5px; background: var(--line-2); margin-top: 14px; overflow: hidden; }
    .ld-stat-track > i { display: block; height: 100%; border-radius: 5px; background: linear-gradient(90deg, var(--teal), var(--lime)); }

    /* combined middle grid — Calendar is the only explicitly-placed item (col 3,
       spanning both rows); Pending Actions/WAI/Feedback/Onboarding auto-place
       into the remaining 4 cells in DOM order (row 1 fills left-to-right, then
       row 2), so the calendar naturally stretches to their combined height. */
    .ld-combo { grid-template-columns: 1fr 1.4fr 0.9fr; grid-template-rows: auto auto; align-items: stretch; }
    .ld-combo .ld-cal-card { grid-column: 3; grid-row: 1 / span 2; }
    .ld-tworow { grid-template-columns: 1.6fr 1fr; }
    .ld-histrow { grid-template-columns: 1.7fr 1fr; align-items: start; }
    @media (max-width: 1100px) {
        .ld-stats { grid-template-columns: repeat(2, 1fr); }
        .ld-combo { grid-template-columns: 1fr; grid-template-rows: none; }
        .ld-combo > * { grid-column: auto !important; grid-row: auto !important; }
        .ld-tworow, .ld-histrow { grid-template-columns: 1fr; }
    }

    .ld-card { padding: 20px 22px; }
    .ld-card-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
    .ld-card-title { font-size: 18px; font-weight: 600; color: var(--ink); }
    .ld-viewall { font-size: 14px; font-weight: 600; color: var(--teal); text-decoration: none; }
    .ld-viewall:hover { text-decoration: underline; }
    .ld-lnk { font-size: 14px; font-weight: 600; color: var(--teal); text-decoration: none; }
    .ld-lnk:hover { text-decoration: underline; }

    /* Pending Actions */
    .ld-pa { display: flex; gap: 12px; padding: 16px 0; border-bottom: 1px solid var(--line); }
    .ld-pa:last-child { border-bottom: none; }
    .ld-pa-ic { flex: none; width: 30px; height: 30px; border-radius: 9px; background: var(--warning-bg); color: var(--warning); display: grid; place-items: center; }
    .ld-pa-ic svg { width: 15px; height: 15px; }
    .ld-pa-t { font-weight: 600; font-size: 14px; color: var(--ink); }
    .ld-pa-d { font-size: 14px; color: var(--muted); line-height: 1.5; margin-top: 3px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .ld-pa-body .ld-lnk { display: inline-block; margin-top: 8px; }
    .ld-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; color: var(--faint); padding: 40px 10px; }
    .ld-empty-ring { width: 52px; height: 52px; border-radius: 50%; border: 2px dashed var(--line); display: grid; place-items: center; margin-bottom: 14px; color: var(--faint); }
    .ld-empty-t { font-weight: 600; color: var(--muted); font-size: 14px; }
    .ld-empty-s { font-size: 11px; margin-top: 4px; }
    .ld-loading-text { color: var(--faint); font-size: 14px; padding: 8px 0; margin: 0; }

    /* WAI Insights — gradient shell kept, rows restructured */
    .ld-wai { padding: 0; overflow: hidden; height: 100%; display: flex; flex-direction: column; border-radius: 25px; }
    .ld-wai-head { position: relative; overflow: hidden; padding: 20px 22px 17px; flex-shrink: 0; background: linear-gradient(110deg, var(--teal) 0%, #0e8a9e 40%, #7fa61e 70%, var(--lime) 100%); }
    .ld-wai-h { position: relative; color: #fff; font-size: 18px; font-weight: 600; display: flex; align-items: center; gap: 7px; margin-bottom: 4px; }
    .ld-wai-meta { position: relative; font-size: 10.5px; font-weight: 500; color: rgba(255,255,255,.8); }
    .ld-wai-meta a, .ld-wai-meta span { color: #fff; }
    .ld-wai-body { padding: 6px 20px 18px; flex: 1 1 auto; min-height: 0; overflow-y: auto; }
    .ld-ins { display: flex; gap: 12px; padding: 14px 0; border-bottom: 1px solid var(--line-2); }
    .ld-ins:last-child { border-bottom: none; }
    .ld-ins-ic { flex: none; width: 30px; height: 30px; border-radius: 9px; display: grid; place-items: center; margin-top: 2px; }
    .ld-ins-ic svg { width: 14px; height: 14px; }
    .ld-ins-ic.is-ok { background: var(--positive-bg); color: var(--positive); }
    .ld-ins-ic.is-flagged { background: var(--warning-bg); color: var(--warning); }
    .ld-ins-tt { font-weight: 600; font-size: 14px; color: var(--ink); }
    .ld-ins-issue { font-size: 14px; color: var(--muted); margin-top: 3px; line-height: 1.5; }

    /* Calendar (FullCalendar v3 reskin — plugin/AJAX logic untouched) + sessions
       list. .ld-combo's grid-row:1/span 2 plus align-items:stretch (native CSS
       Grid behavior, no JS/height hack needed) stretches this card to the full
       combined height of the other two rows; the sessions list grows to fill
       it via flex:1 below, so there's no empty gap at the bottom. */
    .ld-cal-card { display: flex; flex-direction: column; }
    .ld-sess-list { flex: 1; overflow-y: auto; }
    .ld-cal-card .fc-toolbar { margin-bottom: 12px; }
    .ld-cal-card .fc-toolbar h2 { font-size: 14px; font-weight: 600; color: var(--ink); }
    .ld-cal-card .fc-day-header { font-weight: 600; color: var(--muted); font-size: 11px; text-transform: uppercase; letter-spacing: .3px; }
    .ld-cal-card .fc-button { background: #fff; border: 1px solid var(--line); color: var(--muted); box-shadow: none; text-shadow: none; border-radius: 8px; }
    .ld-cal-card .fc-button:hover { background: var(--line-2); }
    .ld-cal-card .fc-state-active, .ld-cal-card .fc-button:active { background: var(--teal-3); }
    .ld-cal-card .fc-basic-view .fc-day-top .fc-day-number { font-size: 14px; color: var(--ink); padding: 6px 0; }
    /* today's own dark-teal pill (default.css) needs white text, not --ink —
       same specificity as the rule above so it has to win explicitly */
    .ld-cal-card .fc-today .fc-day-number { color: #fff !important; }
    .ld-cal-card .fc-day-top { position: relative; }
    .fc-day.custom-dot { position: relative; }
    .fc-day.custom-dot::after { content: ''; position: absolute; left: 50%; bottom: 6px; transform: translateX(-50%); width: 5px; height: 5px; background: var(--teal-bright, #2EACB3); border-radius: 50%; }
    .fc-today.custom-dot::after { background: var(--lime); }

    .ld-sessions-head { margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--line); margin-bottom: 4px; }
    .ld-sess { display: flex; gap: 12px; padding: 14px 0; border-bottom: 1px solid var(--line); }
    .ld-sess:last-child { border-bottom: none; }
    .ld-sess-list .date-block { flex: none; width: 46px; text-align: center; background: var(--line-2); border-radius: 10px; padding: 6px 0; align-self: flex-start; font-size: 10.5px; font-weight: 600; letter-spacing: .5px; color: var(--faint); text-transform: uppercase; }
    .ld-sess-list .date-block h5 { font-size: 22px; font-weight: 600; color: var(--teal); margin: 2px 0; }
    /* Root cause of the recurring avatar-clipping bug: default.css has a bare,
       unscoped `.leaveUser-block { display:flex; flex-wrap:wrap; gap:6px }`
       rule (a leftover from some other feature reusing this class name). That
       makes the title chip / description / time / avatars all flex items that
       wrap onto shared lines instead of stacking — which is what put time and
       the avatar row side-by-side on one line, stretched them to match each
       other's height, and ultimately clipped the circles. Killing the flex
       here restores plain block stacking. */
    .ld-sess-list .leaveUser-block { display: block; }
    .ld-sess-list .leaveUser-bgBlock { background: var(--line-2); border-radius: 8px; padding: 6px 10px; display: inline-block; margin-bottom: 4px; }
    .ld-sess-list .leaveUser-bgBlock h6 { font-size: 14px; font-weight: 600; color: var(--ink); margin: 0; }
    .ld-sess-list .leaveUser-block p { font-size: 14px; color: var(--muted); margin: 0 0 6px; line-height: 1.45; }
    /* Time (icon glued to the text, never wraps) sits on its own line right
       below the description; avatars get their own line below that — two
       stacked left-aligned rows, not squeezed into one shared row. */
    .ld-sess-list .time { display: flex; align-items: center; gap: 5px; font-size: 11px; color: var(--faint); white-space: nowrap; margin-top: 8px; }
    .ld-sess-list .user-ovImg { display: flex; margin-top: 8px; }
    /* default.css's own generic .img-circle (47px, no ancestor scoping) and its
       .leaveUser-block .img-circle variant tie this rule's specificity — forcing
       the shape so this list's circle size wins regardless of load order.
       24px = 20px + 20%. box-sizing:border-box because this app has no global
       reset — with the default content-box, the 2px border would add on top
       of the declared 24px, rendering a 28px box and clipping against
       whatever assumed a 24px circle. */
    .ld-sess-list .img-circle { box-sizing: border-box !important; width: 24px !important; height: 24px !important; min-width: 24px !important; border-radius: 50% !important; overflow: hidden !important; margin-left: -7px; border: 2px solid #fff; }
    .ld-sess-list .img-circle:first-child { margin-left: 0; }
    .ld-sess-list .img-circle img { width: 100% !important; height: 100% !important; object-fit: cover !important; }
    .ld-sess-list .num { box-sizing: border-box; width: 24px; height: 24px; border-radius: 50%; background: var(--line-2); color: var(--muted); font-size: 10.5px; display: grid; place-items: center; margin-left: -7px; border: 2px solid #fff; }
    .ld-view-all-row { padding-top: 10px; }

    /* Gauges */
    .ld-gauge-wrap { display: flex; flex-direction: column; align-items: center; text-align: center; padding: 6px 0; }
    .ld-gauge-canvas { position: relative; width: 160px; height: 160px; }
    .ld-gauge-center { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; }
    .ld-gauge-big { font-size: 22px; font-weight: 600; letter-spacing: -.5px; color: var(--ink); font-variant-numeric: tabular-nums; }
    .ld-gauge-lbl { font-size: 10.5px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .4px; max-width: 120px; margin-top: 4px; line-height: 1.3; }
    .ld-gauge-foot { display: flex; justify-content: space-between; width: 100%; margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--line); font-size: 11px; }
    .ld-gauge-foot span:first-child { color: var(--faint); }
    .ld-gauge-foot span:last-child { color: var(--ink); font-weight: 600; }

    /* Learning Attendance — mini radial dials */
    .ld-late-text { font-size: 11px; color: var(--muted); margin: -6px 0 12px; }
    .ld-dial-list { display: flex; flex-direction: column; gap: 16px; }
    .ld-dial-item { display: flex; align-items: center; gap: 14px; }
    .ld-dial { position: relative; width: 52px; height: 52px; flex: none; }
    .ld-dial-num { position: absolute; inset: 0; display: grid; place-items: center; font-size: 11px; font-weight: 600; font-variant-numeric: tabular-nums; }
    .ld-dial-meta { flex: 1; min-width: 0; }
    .ld-dial-nm { font-size: 14px; font-weight: 600; color: var(--ink); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ld-dial-s { font-size: 11px; color: var(--faint); margin-top: 2px; }
    .ld-others { margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center; font-size: 14px; color: var(--muted); }
    .ld-others b { color: var(--ink); }

    /* Learning Hours */
    .ld-chart-box { position: relative; height: 260px; }
    .ld-chart-box canvas { max-height: 100%; }

    /* Learning Completion Rates — segmented tracks */
    .ld-track-list { display: flex; flex-direction: column; gap: 18px; }
    .ld-track-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
    .ld-track-nm { font-size: 14px; font-weight: 600; color: var(--ink); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; padding-right: 10px; }
    .ld-track-val { font-size: 14px; font-weight: 600; font-variant-numeric: tabular-nums; flex: none; }
    .ld-track-val small { color: var(--faint); font-weight: 600; }
    .ld-pips { display: flex; gap: 3px; }
    .ld-pips i { flex: 1; height: 10px; border-radius: 3px; display: block; }

    /* Learning History — capped to a fixed height with internal scroll, so its
       height always matches Learning Attendance beside it regardless of how
       many entries there are (2 per row visible, rest scroll under a thin bar). */
    .ld-history { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; max-height: 300px; overflow-y: auto; padding-right: 6px; align-content: start; margin-bottom: 0; }
    .ld-history::-webkit-scrollbar { width: 6px; }
    .ld-history::-webkit-scrollbar-thumb { background: var(--line); border-radius: 3px; }
    .ld-history::-webkit-scrollbar-track { background: transparent; }
    @media (max-width: 1100px) { .ld-history { grid-template-columns: 1fr; } }
    .ld-hist { border: 1px solid var(--line); border-radius: 14px; padding: 16px 18px; display: flex; flex-direction: column; gap: 8px; background: #fff; height: 100%; }
    .ld-hist-range { font-size: 11px; font-weight: 600; color: var(--faint); letter-spacing: .3px; }
    .ld-hist-t { font-weight: 600; font-size: 14px; color: var(--ink); }
    .ld-hist-d { font-size: 14px; color: var(--muted); line-height: 1.45; flex: 1; }
    /* No divider line above the attendance pill — the card's own border is enough. */
    .ld-hist-foot { display: flex; align-items: center; justify-content: space-between; margin-top: 2px; }
    .ld-hist-att { font-size: 11px; color: var(--muted); }
    .ld-hist-att b.ok { color: var(--positive); font-weight: 600; }
    .ld-status-pill { font-size: 10px; font-weight: 600; letter-spacing: .4px; padding: 3px 9px; border-radius: 20px; background: var(--line-2); color: var(--muted); }
    .ld-status-pill.warn { background: var(--warning-bg); color: var(--warning); }

    /* WAI Insight trigger-link row + frosted modal chrome (including the
       .m-empty "no data yet" state) now live in the shared
       partials/_wai_insight_modals.blade.php, included below — reused by
       every module's dashboard instead of copied per page. */
</style>
@endsection

@section('import-scripts')
    <script type="text/javascript">
        $(document).ready(function () {
            $('.data-Table').dataTable({
                "searching": false, "bLengthChange": false, "bFilter": true,
                "bInfo": false, "bAutoWidth": false, scrollX: true, "iDisplayLength": 10,
            });

            fetchUpcomingSessions();
            fetchTrainingAttendance();
        });

        // Recommendation/details modal open-close logic now lives in the
        // shared partials/_wai_insight_modals.blade.php include below.

        $(function () {
            var cal = $('#calendar').fullCalendar({
                header: { left: 'prev', center: 'title', right: 'next' },
                editable: false,
                eventLimit: 0,
                navLinks: false,
                contentHeight: 'auto',
                events: function(start, end, timezone, callback) {
                    $.ajax({
                        url: "{{ route('get.learning.sessions') }}",
                        type: "GET",
                        data: { start_date: start.format('YYYY-MM-DD'), end_date: end.format('YYYY-MM-DD') },
                        success: function(response) {
                            window._learningSessions = response.data || [];
                            callback([]);
                            setTimeout(paintLearningDots, 0);
                        },
                        error: function(xhr) { console.error("Error fetching training sessions", xhr); }
                    });
                },
                viewRender: function (view) {
                    fetchUpcomingSessions();
                    setTimeout(paintLearningDots, 0);
                },
                dayClick: function(date, jsEvent, view) {
                    $.ajax({
                        url: "{{ route('get.learning.sessions') }}",
                        type: "GET",
                        data: { start_date: date.format('YYYY-MM-DD'), end_date: date.format('YYYY-MM-DD') },
                        success: function(response) {
                            // Day-click is a deliberate drill-in, so show every session
                            // on that day (unlike the passive dashboard list below).
                            $("#leaveUser-main").html(buildSessionsHtml(response.data || []));
                        },
                        error: function(xhr) { console.error("Error fetching training sessions", xhr); }
                    });
                }
            });
        });

        // Paint a marker dot on every day each cached training session covers.
        function paintLearningDots() {
            $('.fc-day').removeClass('custom-dot');
            var sessions = window._learningSessions || [];
            sessions.forEach(function (session) {
                var startStr = session.start_date || session.session_date;
                var endStr   = session.end_date   || session.session_date;
                if (!startStr) return;
                var startDate = moment(startStr);
                var endDate   = endStr ? moment(endStr) : startDate.clone();
                if (!startDate.isValid()) return;
                if (!endDate.isValid() || endDate.isBefore(startDate, 'day')) endDate = startDate.clone();
                var cursor = startDate.clone();
                while (!cursor.isAfter(endDate, 'day')) {
                    var dayCell = $('.fc-day[data-date="' + cursor.format('YYYY-MM-DD') + '"]');
                    if (dayCell.length) dayCell.addClass('custom-dot');
                    cursor.add(1, 'day');
                }
            });
        }

        // Shared card-style renderer for a list of session objects — used by both
        // the passive dashboard load and the day-click drill-in. Both render every
        // session passed in; the dashboard list scrolls internally (.ld-sess-list)
        // instead of truncating, so nothing needs capping here.
        function buildSessionsHtml(sessions) {
            if (!sessions.length) return '<p class="text-muted small mb-0">No training sessions.</p>';
            var html = '';
            sessions.forEach(function (session) {
                var d = new Date(session.session_date);
                var day = d.getDate();
                var month = d.toLocaleString('en-US', { month: 'short' }).toUpperCase();
                var weekday = d.toLocaleString('en-US', { weekday: 'short' }).toUpperCase();
                var attendeeHtml = '';
                if (session.participants && session.participants.length) {
                    session.participants.slice(0, 5).forEach(function (a) {
                        attendeeHtml += '<div class="img-circle"><img src="' + a.image + '" alt="' + a.name + '"></div>';
                    });
                    if (session.participants.length > 5) {
                        attendeeHtml += '<div class="num">+' + (session.participants.length - 5) + '</div>';
                    }
                }
                html += '' +
                    '<div class="ld-sess">' +
                        '<div class="date-block">' + month + '<h5>' + day + '</h5>' + weekday + '</div>' +
                        '<div class="leaveUser-block flex-fill">' +
                            '<div class="leaveUser-bgBlock"><h6>' + session.title + '</h6></div>' +
                            '<p>' + (session.description || 'No description available') + '</p>' +
                            '<div class="time"><i class="fa-regular fa-clock"></i> ' + session.start_time + ' to ' + session.end_time + '</div>' +
                            '<div class="user-ovImg">' + attendeeHtml + '</div>' +
                        '</div>' +
                    '</div>';
            });
            return html;
        }

        function fetchUpcomingSessions() {
            $.ajax({
                url: '{{ route("get.learning.sessions") }}',
                type: 'GET',
                data: {
                    start_date: new Date().toISOString().split('T')[0],
                    end_date: new Date(new Date().setDate(new Date().getDate() + 30)).toISOString().split('T')[0]
                },
                success: function(response) {
                    // Card height is capped with the list scrolling internally now
                    // (see .ld-cal-card / .ld-sess-list), so render every upcoming
                    // session instead of truncating to 2 — scrolling reveals the rest.
                    $('#leaveUser-main').html(buildSessionsHtml(response.data || []));
                },
                error: function(error) { console.error('Error fetching training sessions:', error); }
            });
        }

        var LD_RAMP = ['#014653', '#2EACB3', '#4A7C64', '#A8823F', '#7C9DA3'];

        function fetchTrainingAttendance() {
            $.ajax({
                url: "{{ route('learning.attendance.chart-data') }}",
                type: "GET",
                success: function (response) {
                    if (response.success) {
                        renderAttendanceDials(response.data);
                        $("#lateAttendanceText").text('Late attendance: ' + response.data.late_percentage + '%');
                    } else {
                        toastr.error(response.message, "Error", { positionClass: 'toast-bottom-right' });
                    }
                },
                error: function () {
                    toastr.error("Failed to fetch attendance data.", "Error", { positionClass: 'toast-bottom-right' });
                }
            });
        }

        var MAX_ATT_DIALS = 4;
        function renderAttendanceDials(data) {
            var labels = data.labels || [];
            var values = data.values || [];
            var el = document.getElementById('attList');
            el.innerHTML = '';
            if (!labels.length) {
                el.innerHTML = '<p class="text-muted small mb-0">No attendance recorded yet.</p>';
                document.getElementById('attOthersRow').style.display = 'none';
                return;
            }
            var recent = labels.slice(0, MAX_ATT_DIALS).map(function (l, i) { return { name: l, value: values[i] || 0 }; });
            var rest = labels.slice(MAX_ATT_DIALS).map(function (l, i) { return values[i + MAX_ATT_DIALS] || 0; });

            recent.forEach(function (r, i) {
                var cid = 'attDial' + i;
                var item = document.createElement('div');
                item.className = 'ld-dial-item';
                item.innerHTML = '<div class="ld-dial"><canvas id="' + cid + '"></canvas>' +
                    '<div class="ld-dial-num" style="color:' + LD_RAMP[i % LD_RAMP.length] + '">' + Math.round(r.value) + '%</div></div>' +
                    '<div class="ld-dial-meta"><div class="ld-dial-nm">' + r.name + '</div><div class="ld-dial-s">Attendance rate</div></div>';
                el.appendChild(item);
                // LD_RAMP itself is left literal (only 2 of 5 entries match
                // SSOT tokens); the track colour (2nd slot) is an exact
                // --line-2 match, migrated.
                var _pDial = window.WaiChart ? window.WaiChart.palette().lineSoft : '#EEF4F4';
                var _dialChart = new Chart(document.getElementById(cid), {
                    type: 'doughnut',
                    data: { datasets: [{ data: [r.value, Math.max(0, 100 - r.value)], backgroundColor: [LD_RAMP[i % LD_RAMP.length], _pDial], borderWidth: 0 }] },
                    options: { cutout: '74%', rotation: -90, circumference: 360, responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { enabled: false } } }
                });
                if (window.WaiChart) window.WaiChart.registerForTheme(_dialChart, function (c, p) {
                    c.data.datasets[0].backgroundColor[1] = p.lineSoft;
                });
            });

            var othersRow = document.getElementById('attOthersRow');
            if (rest.length) {
                var avg = Math.round(rest.reduce(function (s, v) { return s + v; }, 0) / rest.length);
                document.getElementById('attOthers').textContent = rest.length + ' programs · ' + avg + '%';
                othersRow.style.display = 'flex';
            } else {
                othersRow.style.display = 'none';
            }
        }

        // colorRole: optional palette key ('teal', etc.) so the value slice
        // re-resolves on theme switch too; omit for a literal with no token match.
        function gauge(id, value, color, colorRole) {
            var canvas = document.getElementById(id);
            if (!canvas) return;
            var _pGaugeTrack = window.WaiChart ? window.WaiChart.palette().lineSoft : '#EEF4F4';
            var _gaugeChart = new Chart(canvas, {
                type: 'doughnut',
                data: { datasets: [{ data: [value, Math.max(0, 100 - value)], backgroundColor: [color, _pGaugeTrack], borderWidth: 0 }] },
                options: { cutout: '78%', rotation: -90, circumference: 360, responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { enabled: false } } }
            });
            if (window.WaiChart) window.WaiChart.registerForTheme(_gaugeChart, function (c, p) {
                if (colorRole) c.data.datasets[0].backgroundColor[0] = p[colorRole];
                c.data.datasets[0].backgroundColor[1] = p.lineSoft;
            });
        }
        // '#C7CDCF' has no exact SSOT token match — left literal.
        gauge('gaugeFeedback', {{ $feedbackAvgScore ?? 0 }}, '#C7CDCF');
        gauge('gaugeOnboard', {{ $onboardingProgress ?? 0 }}, window.WaiChart ? window.WaiChart.palette().teal : '#014653', 'teal');
    </script>
    <script type="module">
        // Learning Hours — horizontal bar per program, on-brand ramp (was blue/pink/yellow).
        var learningHoursRows = @json($learningHoursByProg ?? []);
        var learningHoursCanvas = document.getElementById('myStackedBarChart');

        if (learningHoursCanvas && learningHoursRows.length) {
            var labels = learningHoursRows.map(function (r) { return r.name || 'Untitled'; });
            var values = learningHoursRows.map(function (r) { return parseFloat(r.total_hours || 0); });
            var colors = learningHoursRows.map(function (_r, i) { return LD_RAMP[i % LD_RAMP.length]; });
            var meta   = learningHoursRows.map(function (r) { return { sessions: r.session_count || 0 }; });

            // colors (from LD_RAMP) left literal — see LD_RAMP note above.
            var _pLearnHoursGrid = window.WaiChart ? window.WaiChart.palette().lineSoft : '#EEF4F4';
            var _learningHoursChart = new Chart(learningHoursCanvas.getContext('2d'), {
                type: 'bar',
                data: { labels: labels, datasets: [{ label: 'Hours', data: values, backgroundColor: colors, borderRadius: 6 }] },
                options: {
                    indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: {
                            title: function (items) { return items[0].label; },
                            label: function (item) { var m = meta[item.dataIndex] || {}; return ' ' + item.formattedValue + ' hrs · ' + (m.sessions || 0) + ' sessions'; }
                        } }
                    },
                    scales: {
                        x: { beginAtZero: true, grid: { color: _pLearnHoursGrid }, ticks: { precision: 0 }, title: { display: true, text: 'Hours' } },
                        y: { grid: { display: false }, ticks: { callback: function (value) { var l = this.getLabelForValue(value); return l && l.length > 18 ? l.slice(0, 18) + '…' : l; } } }
                    }
                }
            });
            if (window.WaiChart) window.WaiChart.registerForTheme(_learningHoursChart);
        }
    </script>
@endsection
