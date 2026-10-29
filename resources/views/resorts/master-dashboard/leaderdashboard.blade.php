{{--
    Master dashboard for NON-HR leaders: non-HR department HODs, non-HR ExComs and the General Manager.
    HR roles keep resorts/master-dashboard/hrdashboard.blade.php (untouched).

    Every figure comes from variables MasterDashboardController::hod_dashboard() / gm_dashboard() already
    pass (names below); whatever those don't provide is filled in by LeaderDashboard::viewData(), called
    right below (app/Services/LeaderDashboard.php — $leader['requests'/'events'/'health'/'absent_delta'],
    plus pendingResignationsForHod/monthlyCheckinPerformance/totalOverallWorkingHours when missing). Still
    shows an honest empty state / "—" for anyone with no linked employee record
    (LeaderDashboard::forCurrentUser() returns null) rather than fake data.
    Styles: public/resorts_assets/css/leader-dashboard.css, behaviour: .../js/leader-dashboard.js.
--}}
@extends('resorts.layouts.app')
@section('page_tab_title' ,"Dashboard")

@if ($message = Session::get('success'))
    <div class="alert alert-success">
        <p>{{ $message }}</p>
    </div>
@endif

@section('import-css')
<link href="{{ URL::asset('resorts_assets/css/leader-dashboard.css') }}?v={{ @filemtime(public_path('resorts_assets/css/leader-dashboard.css')) }}" rel="stylesheet">
@endsection

@section('content')
@php
    // Fills in {leader, pendingResignationsForHod, monthlyCheckinPerformance, totalOverallWorkingHours}
    // from LeaderDashboard (app/Services/LeaderDashboard.php) for whichever of these the controller
    // didn't already pass — e.g. gm_dashboard() passes none of the three today; hod_dashboard() already
    // passes pendingResignationsForHod. get_defined_vars() here is only the controller's own compact()
    // variables, since nothing below has been defined yet; viewData() never overrides a key that's
    // already present, and EXTR_SKIP is a second guard against that.
    $ldSvc  = \App\Services\LeaderDashboard::forCurrentUser();
    $ldData = $ldSvc ? $ldSvc->viewData(get_defined_vars()) : ['leader' => []];
    extract($ldData, EXTR_SKIP);

    $none       = new \Illuminate\Database\Eloquent\Collection();
    $today      = \Carbon\Carbon::today();
    $horizon    = $today->copy()->addDays(60);
    $defaultPic = url(config('settings.default_picture'));
    $isXcom     = strtoupper((string) request('dashboard_label', '')) === 'XCOM';
    $leaveRoute = $isXcom ? 'leave.excomdashboard' : 'leave.hoddashboard';
    $plural     = fn ($n, $one, $many = null) => $n . ' ' . ($n == 1 ? $one : ($many ?? $one . 's'));
    $span       = fn ($from, $to) => $from->isSameDay($to) ? $from->format('d M') : $from->format('d M') . ' – ' . $to->format('d M');

    // ---- source collections (all provided by the controller; guarded because GM and HOD pass different sets) ----
    $todayLeaveC    = $todayleaveUsers ?? $none;
    $upcomingLeaveC = $upcomingLeaveUsers ?? $none;
    $probationC     = $probationEmployees ?? $none;
    $resignC        = $pendingResignationsForHod ?? $none;
    $exitFormC      = $pendingExitClearanceForms ?? $none;
    $checkinC       = $monthlyCheckinPerformance ?? $none;
    $leader         = $leader ?? [];

    // One batched relation load per collection (no per-row queries).
    $leaveAll = $todayLeaveC->merge($upcomingLeaveC)->loadMissing(['employee.resortAdmin', 'employee.position', 'LeaveCategory']);
    $probationC->loadMissing(['resortAdmin', 'position']);

    // One batched photo lookup for every person shown on the page.
    $adminIds = collect()
        ->merge($leaveAll->map(fn ($l) => optional($l->employee)->Admin_Parent_id))
        ->merge($probationC->pluck('Admin_Parent_id'))
        ->merge($resignC->map(fn ($r) => optional($r->employee)->Admin_Parent_id))
        ->merge($exitFormC->map(fn ($f) => optional(optional($f->employeeResignation)->employee)->Admin_Parent_id))
        ->filter()->unique()->values()->all();
    $pics    = \App\Helpers\Common::getResortUserPicturesBatch($adminIds);
    $photoOf = fn ($adminId) => (($u = $pics[$adminId] ?? '') && $u !== $defaultPic) ? $u : '';
    $nameOf  = fn ($emp) => optional(optional($emp)->resortAdmin)->full_name ?: '—';
    $posOf   = fn ($emp) => optional(optional($emp)->position)->position_title ?: '';

    // ---- Leave card ----
    $leaveRow = function ($l) use ($photoOf, $nameOf, $posOf) {
        $e = $l->employee;
        return [
            'n'     => $nameOf($e),
            'r'     => $posOf($e),
            'photo' => $photoOf(optional($e)->Admin_Parent_id),
            't'     => optional($l->LeaveCategory)->leave_type ?: 'Leave',
            'c'     => optional($l->LeaveCategory)->color ?: '#014653', // configured leave colour (same as the leave module)
        ];
    };
    $lvToday = $todayLeaveC->map(function ($l) use ($leaveRow, $today) {
        $from = \Carbon\Carbon::parse($l->from_date)->startOfDay();
        $to   = \Carbon\Carbon::parse($l->to_date)->startOfDay();
        return $leaveRow($l) + [
            'from' => $from->format('d M'), 'to' => $to->format('d M'),
            'day'  => (int) $from->diffInDays($today, true) + 1, 'of' => (int) $from->diffInDays($to, true) + 1,
            'back' => $to->copy()->addDay()->format('D, d M'),
        ];
    })->values();
    $lvUp = $upcomingLeaveC
        ->filter(fn ($l) => \Carbon\Carbon::parse($l->from_date)->lte($horizon))
        ->sortBy('from_date')
        ->map(function ($l) use ($leaveRow, $today, $span, $plural) {
            $from = \Carbon\Carbon::parse($l->from_date)->startOfDay();
            $to   = \Carbon\Carbon::parse($l->to_date)->startOfDay();
            $days = (int) ($l->total_days ?: $from->diffInDays($to, true) + 1);
            $in   = (int) $today->diffInDays($from, true);
            return $leaveRow($l) + [
                'dates' => $span($from, $to), 'len' => $plural($days, 'day'),
                'in'    => $in === 1 ? 'Starts tomorrow' : 'Starts in ' . $plural($in, 'day'),
                'st'    => $l->status === 'Approved' ? 'ok' : 'pend',
            ];
        })->values();

    // ---- To do (Performance monthly check-ins waiting; Time & Attendance items are added client-side) ----
    $todoItems = $checkinC->filter(fn ($c) => ($c->status ?? '') === 'Pending')->take(5)->map(fn ($c) => [
        'n'     => trim(($c->first_name ?? '') . ' ' . ($c->last_name ?? '')),
        'photo' => '',
        'sub'   => 'Monthly check-in · ' . ($c->new_date_of_dicussion ?? ''),
        'label' => 'Open',
        'url'   => route('Performance.GetMonthlyCheckInDetails', base64_encode($c->id)),
        'cls'   => '',
    ])->values();

    // ---- Action inbox ----
    $inboxExit = $exitFormC->map(function ($f) use ($photoOf, $nameOf, $posOf) {
        $rs = $f->employeeResignation; $emp = optional($rs)->employee;
        $dl = $f->deadline_date ? \Carbon\Carbon::parse($f->deadline_date) : null;
        return [
            't' => 'exit', 'n' => $nameOf($emp), 'r' => $posOf($emp), 'photo' => $photoOf(optional($emp)->Admin_Parent_id),
            'd' => optional($f->exitClearanceForm)->form_name ?: 'Exit clearance form',
            'm' => optional($rs)->last_working_day ? 'Last day ' . \Carbon\Carbon::parse($rs->last_working_day)->format('d M Y') : '',
            'dl' => $dl ? 'Deadline ' . $dl->format('d M Y') . ($dl->isPast() ? ', overdue' : '') : '', 'od' => (bool) ($dl && $dl->isPast()),
            'url' => route('people.exit-clearance.department-form', base64_encode($f->emp_resignation_id)),
        ];
    })->values();
    $inboxRes = $resignC->map(function ($r) use ($photoOf, $nameOf, $posOf) {
        $emp = $r->employee;
        return [
            't' => 'res', 'n' => $nameOf($emp), 'r' => $posOf($emp), 'photo' => $photoOf(optional($emp)->Admin_Parent_id),
            'd' => $r->resignation_date ? 'Resignation submitted ' . \Carbon\Carbon::parse($r->resignation_date)->format('d M Y') : 'Resignation submitted',
            'm' => $r->last_working_day ? 'Last working day ' . \Carbon\Carbon::parse($r->last_working_day)->format('d M Y') : '',
            'dl' => '', 'od' => false,
            'url' => route('people.employee-resignation.show', ['id' => base64_encode($r->id)]),
        ];
    })->values();
    // Requests awaiting this user's approval — $leader['requests'], from LeaderDashboard::requests().
    $inboxReq = collect($leader['requests'] ?? [])->map(fn ($q) => array_merge(['photo' => '', 'dl' => '', 'od' => false], (array) $q, ['t' => 'req']))->values();
    $inbox = $inboxExit->concat($inboxRes)->concat($inboxReq)->values();

    // ---- Calendar + upcoming events (this user's scope only) ----
    $events = [];
    foreach ($upcomingLeaveC->filter(fn ($l) => \Carbon\Carbon::parse($l->from_date)->lte($horizon)) as $l) {
        $from = \Carbon\Carbon::parse($l->from_date); $to = \Carbon\Carbon::parse($l->to_date); $row = $leaveRow($l);
        $events[] = ['date' => $from->toDateString(), 'title' => 'Leave · ' . $row['n'], 'sub' => $row['t'] . ' · ' . $span($from, $to), 'people' => [['n' => $row['n'], 'p' => $row['photo']]]];
    }
    $probDue = $probationC->filter(fn ($e) => $e->probation_end_date && \Carbon\Carbon::parse($e->probation_end_date)->between($today, $horizon))->groupBy(fn ($e) => \Carbon\Carbon::parse($e->probation_end_date)->toDateString());
    foreach ($probDue as $date => $group) {
        $people = $group->take(3)->map(fn ($e) => ['n' => $nameOf($e), 'p' => $photoOf($e->Admin_Parent_id)])->all();
        $events[] = ['date' => $date, 'title' => $group->count() === 1 ? 'Probation ends · ' . $nameOf($group->first()) : 'Probation ends · ' . $plural($group->count(), 'staff', 'staff'),
                     'sub' => $group->count() === 1 ? $posOf($group->first()) : $group->take(3)->map(fn ($e) => $nameOf($e))->implode(' · '), 'people' => $people];
    }
    foreach ($resignC->filter(fn ($r) => $r->last_working_day && \Carbon\Carbon::parse($r->last_working_day)->gte($today)) as $r) {
        $events[] = ['date' => \Carbon\Carbon::parse($r->last_working_day)->toDateString(), 'title' => 'Last working day · ' . $nameOf($r->employee), 'sub' => 'Exit clearance required', 'people' => [['n' => $nameOf($r->employee), 'p' => $photoOf(optional($r->employee)->Admin_Parent_id)]]];
    }
    foreach ($checkinC->filter(fn ($c) => !empty($c->date_discussion) && \Carbon\Carbon::parse($c->date_discussion)->gte($today)) as $c) {
        $nm = trim(($c->first_name ?? '') . ' ' . ($c->last_name ?? ''));
        $events[] = ['date' => \Carbon\Carbon::parse($c->date_discussion)->toDateString(), 'title' => 'Monthly check-in · ' . $nm, 'sub' => $c->PositionName ?? '', 'people' => [['n' => $nm, 'p' => '']]];
    }
    // Team events (upcoming trainings) — $leader['events'], from LeaderDashboard::events(). No drills or
    // Employee of the Month: neither is scheduled/tracked in the system, so neither is shown (no fake data).
    foreach ($leader['events'] ?? [] as $ev) { $events[] = (array) $ev + ['sub' => '', 'people' => []]; }
    usort($events, fn ($a, $b) => strcmp($a['date'], $b['date']));

    // ---- Team today + health ----
    $tt      = (int) ($total_employees ?? 0);
    $pct     = fn ($n) => $tt > 0 ? round($n / $tt * 100) : 0;
    $present = (int) ($present_employee_counts ?? 0);
    $absent  = (int) ($absent_employee_counts ?? 0);
    $onLeave = (int) ($leave_employee_counts ?? 0);
    $depts   = $resort_departments ?? $none;
    $deptLbl = $depts->count() === 1 ? $depts->first()->name : 'All departments';
    $absDelta = $leader['absent_delta'] ?? null; // percentage points vs the same weekday last week (optional)
    $health  = $leader['health'] ?? [];
    $hv      = fn ($k, $fallback = null) => $health[$k] ?? $fallback;
    $dash    = fn ($v) => $v === null || $v === '' ? '—' : $v;

    $LD = [
        'urls' => [
            'wai'      => route('resort.wisdom.chat'),
            'waiHistory' => route('resort.wisdom.history'),
            'todo'     => route('resort.timeandattendance.todolist'),
            'todoPage' => route('resort.timeandattendance.todolist'),
            'roster'   => \Illuminate\Support\Facades\Route::has('resort.master.leader_roster') ? route('resort.master.leader_roster') : null,
        ],
        'today'  => $today->toDateString(),
        'nowHm'  => now()->format('H:i'),
        'leave'  => ['today' => $lvToday, 'up' => $lvUp],
        'todo'   => $todoItems,
        'inbox'  => $inbox,
        'events' => $events,
    ];
@endphp

<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Master</span>
                        <h1>{!! $page_header ?? '<span class="arca-font">My</span> Dashboard' !!}</h1>
                    </div>
                </div>
            </div>
        </div>

<div id="ldmd">
  <div class="cols">

    <!-- LEFT 23% — leave + to do -->
    <aside class="col-left">
      <div class="ld-card lv-card" id="leaveCard">
        <div class="lv-h">
          <span class="t">Leave</span>
          <a class="va" href="{{ route($leaveRoute) }}">View all<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg></a>
        </div>
        <div class="lv-tabs" role="tablist" aria-label="Leave">
          <button type="button" class="lv-tab on" data-v="today" role="tab" aria-selected="true">On leave today <span class="c" id="lvCtToday">{{ $lvToday->count() }}</span></button>
          <button type="button" class="lv-tab" data-v="up" role="tab" aria-selected="false">Upcoming <span class="c" id="lvCtUp">{{ $lvUp->count() }}</span></button>
        </div>
        <div class="lv-list" id="lvList"></div>
      </div>

      <div class="ld-card td-card">
        <div class="td-h"><span class="t">To do</span><span class="n" id="tdCount">0</span></div>
        <div class="td-list" id="tdList"></div>
      </div>
    </aside>

    <!-- MIDDLE 54% -->
    <main class="col-mid">

      <!-- WAI ask — company policy + employment law only (the Wisdom chat endpoint enforces the policy tier for these roles) -->
      <div class="wai-hero">
        <div class="wai-glass" id="waiGlass">
          <div class="gl"><span>WAI Intelligence</span><span class="wai-use" id="askUse" hidden></span></div>
          <input class="pin" id="askInput" type="text" placeholder="Ask about company policy or employment law…" autocomplete="off" maxlength="2000">
          <div class="prow">
            <span class="hchip" role="button" tabindex="0">Can unused leave be carried forward?</span>
            <span class="hchip" role="button" tabindex="0">How is public holiday overtime paid?</span>
            <span class="grow"></span>
            <button type="button" class="orb" id="askGo" aria-label="Ask"><i class="b1"></i><i class="b2"></i><i class="b3"></i><i class="b4"></i><span class="orb-glass"></span><span class="orb-sheen"></span><svg class="orb-ar" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5M5 12l7-7 7 7"/></svg></button>
          </div>
        </div>
      </div>

      <!-- Team today -->
      <div class="m-grid" id="teamToday">
        <div class="ld-card metric-c"><div class="m-val"><span class="mn tnum">{{ $tt }}</span></div><div class="m-lbl">My team · {{ $deptLbl }}</div></div>
        <div class="ld-card metric-c"><div class="m-val"><span class="mn tnum">{{ $present }}</span> <span class="sec tnum">{{ $pct($present) }}%</span></div><div class="m-lbl">Present today</div></div>
        <div class="ld-card metric-c"><div class="m-val"><span class="mn tnum">{{ $onLeave }}</span> <span class="sec tnum">{{ $pct($onLeave) }}%</span></div><div class="m-lbl">On leave</div></div>
        <div class="ld-card metric-c"><div class="m-val"><span class="mn tnum">{{ $absent }}</span> <span class="sec tnum">{{ $pct($absent) }}%</span>@if($absDelta !== null && (float) $absDelta != 0) <span class="arw {{ $absDelta > 0 ? 'bad' : 'good' }}">{{ $absDelta > 0 ? '↑' : '↓' }}</span>@endif</div><div class="m-lbl">Absent @if($absDelta !== null && (float) $absDelta != 0)· {{ $absDelta > 0 ? 'up from' : 'down from' }} last week @endif</div></div>
      </div>

      <!-- Team health -->
      <div class="stat-row">
        <div class="ld-card stat"><div class="n tnum">{{ $dash($hv('appraisals_pending')) }}</div><div class="l">Appraisals pending</div>@if($hv('in_pip') !== null || $hv('in_pdp') !== null)<div class="s violet">{{ (int) $hv('in_pip', 0) }} in PIP · {{ (int) $hv('in_pdp', 0) }} in PDP</div>@endif</div>
        <div class="ld-card stat"><div class="n tnum">{{ $dash($hv('overtime_hours', $totalOverallWorkingHours ?? null)) }}@if($hv('overtime_hours', $totalOverallWorkingHours ?? null) !== null)<span class="u">hrs</span>@endif</div><div class="l">Overtime this month</div>@if($hv('overtime_vs_last') !== null)<div class="s coral">{{ $hv('overtime_vs_last') }}× last month</div>@endif</div>
        <div class="ld-card stat"><div class="n tnum">{{ $dash($hv('open_grievances')) }}</div><div class="l">Open grievances</div>@if($hv('disciplinary_cases') !== null)<div class="s coral">{{ $plural((int) $hv('disciplinary_cases'), 'disciplinary case') }}</div>@endif</div>
        <div class="ld-card stat"><div class="n tnum">{{ $dash($hv('turnover_annual')) }}@if($hv('turnover_annual') !== null)<span class="u">%</span>@endif</div><div class="l">Annual turnover</div>@if($hv('turnover_quarter') !== null)<div class="s ok">{{ $hv('turnover_quarter') }}% this quarter</div>@endif</div>
      </div>

      <!-- Duty roster — timeline -->
      <div class="ld-card rs-card">
        <div class="rs-h">
          <span class="t">Duty roster</span><span class="n" id="tlCount">0 rostered</span>
          <span class="rs-ctl">
            <select class="rs-sel" id="tlPos" aria-label="Position"><option value="all">All positions</option></select>
            <input class="rs-sel" id="tlDate" type="date" value="{{ $today->toDateString() }}" aria-label="Date">
          </span>
        </div>
        <div class="tl">
          <div class="tl-now" id="tlNow"><span id="tlNowLbl">Now</span></div>
          <div class="tl-axis"><span></span><div class="tl-ticks"><span style="left:0">00:00</span><span style="left:16.667%">04:00</span><span style="left:33.333%">08:00</span><span style="left:50%">12:00</span><span style="left:66.667%">16:00</span><span style="left:83.333%">20:00</span><span style="left:100%">24:00</span></div></div>
          <div class="tl-wrap">
            <div class="tl-body" id="tlBody"></div>
          </div>
        </div>
        <div class="tl-leg" id="tlLeg"></div>
      </div>

      <!-- Action inbox -->
      <section class="ld-card ai-card" aria-labelledby="aiTitle">
        <div class="ai-h"><h3 id="aiTitle">Action inbox</h3><span class="ai-cnt" id="aiCnt">0</span></div>
        <div class="ai-f" id="aiFilters" role="tablist" aria-label="Filter actions">
          <button type="button" class="ai-fc on" data-f="all">All <b data-c="all">0</b></button>
          <button type="button" class="ai-fc" data-f="res">Resignations <b data-c="res">0</b></button>
          <button type="button" class="ai-fc" data-f="req">Requests <b data-c="req">0</b></button>
          <button type="button" class="ai-fc" data-f="exit">Exit clearance <b data-c="exit">0</b></button>
        </div>
        <div id="aiList"></div>
      </section>

    </main>

    <!-- RIGHT 23% — calendar + upcoming -->
    <aside class="ld-card col-right">
      <div class="crh"><span class="mo" id="calMo"></span><span class="cal-nav"><button type="button" id="calPrev" aria-label="Previous month">‹</button><button type="button" id="calNext" aria-label="Next month">›</button></span></div>
      <div class="dow-row"><span class="dow">Su</span><span class="dow">Mo</span><span class="dow">Tu</span><span class="dow">We</span><span class="dow">Th</span><span class="dow">Fr</span><span class="dow">Sa</span></div>
      <div class="mg" id="mg"></div>
      <div class="up-l">Upcoming events</div>
      <div class="up" id="upList"></div>
    </aside>

  </div>

  <!-- FOCUS MODAL — WAI answer window -->
  <div class="focus" id="focus" role="dialog" aria-modal="true" aria-label="WAI Intelligence">
    <div class="focus-panel">
      <div class="focus-glass">
        <div class="fp-head"><span class="t">WAI Intelligence</span><span class="sub" id="fpSub">Company policy &amp; employment law</span><button type="button" class="fp-close" id="fpClose" aria-label="Close">✕</button></div>
        <div class="fp-body" id="fpBody"></div>
        <div class="fp-foot">
          <div class="wai-foot-row">
            <label class="wai-field"><input id="fpInput" type="text" placeholder="Ask a follow-up…" autocomplete="off" maxlength="2000"><span class="wai-quota" id="fpUse" hidden aria-live="polite"><span class="n" id="fpUseN"></span>&nbsp;left</span></label>
            <button type="button" class="orb" id="fpGo" aria-label="Ask"><i class="b1"></i><i class="b2"></i><i class="b3"></i><i class="b4"></i><span class="orb-glass"></span><span class="orb-sheen"></span><svg class="orb-ar" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5M5 12l7-7 7 7"/></svg></button>
          </div>
          <div class="wai-disclaimer">WAI is smart, not perfect yet — double-check key details. Always improving!</div>
        </div>
      </div>
    </div>
  </div>
</div>

    </div>
</div>
@endsection

@section('import-scripts')
<script>window.LD = @json($LD);</script>
<script src="{{ URL::asset('resorts_assets/js/roster-inbox.js') }}?v={{ @filemtime(public_path('resorts_assets/js/roster-inbox.js')) }}"></script>
<script src="{{ URL::asset('resorts_assets/js/leader-dashboard.js') }}?v={{ @filemtime(public_path('resorts_assets/js/leader-dashboard.js')) }}"></script>
@endsection
