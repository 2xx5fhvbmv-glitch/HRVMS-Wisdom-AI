@extends('resorts.layouts.app')
@section('page_tab_title' ,"Dashboard")

@if (session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif

@section('content')
@php
    // Presentation helpers only — every figure below comes from variables MasterDashboardController::hr_dashboard() already passes.
    $initials = fn ($name) => collect(explode(' ', trim((string) $name)))->filter()->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->take(2)->implode('') ?: '?';
    $plural   = fn ($n, $one, $many = null) => $n . ' ' . ($n == 1 ? $one : ($many ?? $one . 's'));
    $pctOf    = fn ($n) => $total_employees > 0 ? round($n / $total_employees * 100, 1) : 0;
    $defaultPic = url(config('settings.default_picture'));

    // To do — recruitment items (T&A items are appended client-side from the Time & Attendance to-do endpoint).
    $todoRows = collect(isset($TodoData) && $TodoData->isNotEmpty() ? $TodoData : []);
    $todoPics = \App\Helpers\Common::getResortUserPicturesBatch($todoRows->pluck('user_id')->all());

    // Calendar events — this month's public holidays + upcoming birthdays (one eager load, no per-row queries).
    $birthdayRows = $upcommingBirthdays->take(6)->loadMissing(['resortAdmin', 'department', 'position']);
    $birthdayPics = \App\Helpers\Common::getResortUserPicturesBatch($birthdayRows->pluck('Admin_Parent_id')->all());
    $events = [];
    foreach ($upcommingPublicHoliday as $h) {
        $d = \Carbon\Carbon::parse($h->holiday_date);
        $events[] = ['date' => $d->toDateString(), 'title' => $h->name, 'sub' => 'Public holiday', 'people' => []];
    }
    foreach ($birthdayRows as $b) {
        $name = $b->resortAdmin->full_name ?? '';
        $d = \Carbon\Carbon::parse($b->dob)->year(now()->year);
        $pic = $birthdayPics[$b->Admin_Parent_id] ?? $defaultPic;
        $events[] = [
            'date' => $d->toDateString(),
            'title' => 'Birthday · ' . $name,
            'sub' => trim(($b->position->position_title ?? '') . ' · ' . ($b->department->name ?? ''), ' ·'),
            'people' => [['name' => $name, 'photo' => $pic === $defaultPic ? '' : $pic]],
        ];
    }
    usort($events, fn ($a, $b) => strcmp($a['date'], $b['date']));

    // WAI Insights — built from this page's own counts; each links to the module it concerns.
    $insights = [];
    $absentPct = $pctOf($absent_employee_counts);
    if ($absent_employee_counts > 0) {
        $insights[] = ['module' => 'Attendance', 'sev' => $absentPct >= 10 ? 'high' : 'watch', 'title' => 'Absent today', 'html' => '<b>' . $plural($absent_employee_counts, 'employee') . '</b> absent today (' . $absentPct . '% of the workforce).', 'url' => route('resort.timeandattendance.dashboard')];
    }
    if (($open_grivance_count ?? 0) > 0) {
        $insights[] = ['module' => 'People Relations', 'sev' => 'high', 'title' => 'Open grievances', 'html' => '<b>' . $plural((int) $open_grivance_count, 'grievance case') . '</b> open and awaiting action.', 'url' => route('GrievanceAndDisciplinery.Hrdashboard')];
    }
    if (($open_disciplinary_count ?? 0) > 0) {
        $insights[] = ['module' => 'People Relations', 'sev' => 'high', 'title' => 'Open disciplinary cases', 'html' => '<b>' . $plural((int) $open_disciplinary_count, 'disciplinary case') . '</b> open and awaiting action.', 'url' => route('GrievanceAndDisciplinery.Hrdashboard')];
    }
    if (($openIncidentCounts ?? 0) > 0) {
        $insights[] = ['module' => 'Incidents', 'sev' => 'high', 'title' => 'Open incidents', 'html' => '<b>' . $plural((int) $openIncidentCounts, 'incident') . '</b> still open.', 'url' => route('incident.hr.dashboard')];
    }
    if (isset($pendingPayrollApprovals) && $pendingPayrollApprovals->count() > 0) {
        $insights[] = ['module' => 'Payroll', 'sev' => 'high', 'title' => 'Payroll approval pending', 'html' => '<b>' . $plural($pendingPayrollApprovals->count(), 'payroll run') . '</b> waiting for your approval.', 'url' => route('payroll.dashboard')];
    }
    if ($leaveRequests->count() > 0) {
        $insights[] = ['module' => 'Leave', 'sev' => 'watch', 'title' => 'Leave requests pending', 'html' => '<b>' . $plural($leaveRequests->count(), 'leave request') . '</b> pending approval.', 'url' => route('leave.dashboard')];
    }
    if (($total_application_for_job_in_review ?? 0) > 0) {
        $insights[] = ['module' => 'Recruitment', 'sev' => 'watch', 'title' => 'Applications in review', 'html' => '<b>' . $plural((int) $total_application_for_job_in_review, 'application') . '</b> in review.', 'url' => route('resort.recruitement.hrdashboard')];
    }
    if (($pending_trainings_count ?? 0) > 0) {
        $insights[] = ['module' => 'Learning', 'sev' => 'watch', 'title' => 'Training requests pending', 'html' => '<b>' . $plural((int) $pending_trainings_count, 'training request') . '</b> pending approval.', 'url' => route('learning.hr.dashboard')];
    }
    if (($UnassignedDocumentsCounts ?? 0) > 0) {
        $insights[] = ['module' => 'File Management', 'sev' => 'watch', 'title' => 'Unassigned documents', 'html' => '<b>' . $plural((int) $UnassignedDocumentsCounts, 'document') . '</b> not yet assigned to an employee.', 'url' => route('FileManagment.hr.dashboard')];
    }

    // Duty roster + Action inbox — the same LeaderDashboard service and routes the non-HR
    // Leader dashboard uses (app/Services/LeaderDashboard.php); HR already resolves to
    // resort-wide scope there via Common::getScopedDepartmentIds(), so this needs no backend
    // change. get_defined_vars() here is only hr_dashboard()'s own compact() variables, since
    // nothing below has been defined yet; viewData() never overrides a variable this controller
    // already passed (e.g. monthlyCheckinPerformance), it only fills in what's missing
    // (pendingResignationsForHod, pendingExitClearanceForms) and adds 'leader'.
    $hrLdSvc  = \App\Services\LeaderDashboard::forCurrentUser();
    $hrLdData = $hrLdSvc ? $hrLdSvc->viewData(get_defined_vars()) : ['leader' => []];
    extract($hrLdData, EXTR_SKIP);
    $hrRequests = $leader['requests'] ?? [];
    $hrResignC  = $pendingResignationsForHod ?? collect();
    $hrExitC    = $pendingExitClearanceForms ?? collect();
    $hrRosterUrl = \Illuminate\Support\Facades\Route::has('resort.master.leader_roster') ? route('resort.master.leader_roster') : null;
    $today = \Carbon\Carbon::today();
    $hrNameOf = fn ($emp) => optional(optional($emp)->resortAdmin)->full_name ?: '—';
    $hrPosOf  = fn ($emp) => optional(optional($emp)->position)->position_title ?: '';
    $hrPicIds = $hrResignC->map(fn ($r) => optional($r->employee)->Admin_Parent_id)
        ->merge($hrExitC->map(fn ($f) => optional(optional($f->employeeResignation)->employee)->Admin_Parent_id))
        ->filter()->unique()->values()->all();
    $hrPics   = \App\Helpers\Common::getResortUserPicturesBatch($hrPicIds);
    $hrPhotoOf = fn ($adminId) => (($u = $hrPics[$adminId] ?? '') && $u !== $defaultPic) ? $u : '';
    $hrInboxRes = $hrResignC->map(function ($r) use ($hrNameOf, $hrPosOf, $hrPhotoOf) {
        $emp = $r->employee;
        return [
            't' => 'res', 'n' => $hrNameOf($emp), 'r' => $hrPosOf($emp), 'photo' => $hrPhotoOf(optional($emp)->Admin_Parent_id),
            'd' => $r->resignation_date ? 'Resignation submitted ' . \Carbon\Carbon::parse($r->resignation_date)->format('d M Y') : 'Resignation submitted',
            'm' => $r->last_working_day ? 'Last working day ' . \Carbon\Carbon::parse($r->last_working_day)->format('d M Y') : '',
            'dl' => '', 'od' => false,
            'url' => route('people.employee-resignation.show', ['id' => base64_encode($r->id)]),
        ];
    })->values();
    $hrInboxExit = $hrExitC->map(function ($f) use ($hrNameOf, $hrPosOf, $hrPhotoOf) {
        $rs = $f->employeeResignation; $emp = optional($rs)->employee;
        $dl = $f->deadline_date ? \Carbon\Carbon::parse($f->deadline_date) : null;
        return [
            't' => 'exit', 'n' => $hrNameOf($emp), 'r' => $hrPosOf($emp), 'photo' => $hrPhotoOf(optional($emp)->Admin_Parent_id),
            'd' => optional($f->exitClearanceForm)->form_name ?: 'Exit clearance form',
            'm' => optional($rs)->last_working_day ? 'Last day ' . \Carbon\Carbon::parse($rs->last_working_day)->format('d M Y') : '',
            'dl' => $dl ? 'Deadline ' . $dl->format('d M Y') . ($dl->isPast() ? ', overdue' : '') : '', 'od' => (bool) ($dl && $dl->isPast()),
            'url' => route('people.exit-clearance.department-form', base64_encode($f->emp_resignation_id)),
        ];
    })->values();
    $hrInboxReq = collect($hrRequests)->map(fn ($q) => array_merge(['photo' => '', 'dl' => '', 'od' => false], (array) $q, ['t' => 'req']))->values();
    $hrInbox = $hrInboxExit->concat($hrInboxRes)->concat($hrInboxReq)->values();
@endphp
<div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding">
                <div class="row  g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Master</span>
                            <h1>HR Dashboard</h1>
                        </div>
                    </div>
                    <div class="col-auto ms-auto">
                        <a href="{{ route('people.employees') }}"
                           class="wai-btn wai-btn-primary @if(App\Helpers\Common::checkRouteWisePermission('people.employees', config('settings.resort_permissions.view')) == false) d-none @endif">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>View Employees
                        </a>
                    </div>
                </div>
            </div>

<div id="hrmd">
  <div class="cols">

    <!-- LEFT 23% — payroll + to do -->
    <aside class="col-left">
      <div class="hm-card pay-card" id="payCard">
        <div class="rc-h">
          <span class="t">Payroll</span>
          <button type="button" class="rc-exp" id="rcExp" aria-label="Expand payroll details"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg></button>
        </div>
        <div class="rc-cols">
          <div class="rc-c">
            <div class="rc-num" data-pay="fc">—</div>
            <div class="rc-fillwrap"><div class="rc-fill" style="--h:50%;--fbg:rgba(1,70,83,.07);--fdot:rgba(1,70,83,.30)"></div></div>
            <div class="rc-leg"><span class="l">Forecast</span></div>
          </div>
          <div class="rc-c">
            <div class="rc-num" data-pay="last">—</div>
            <div class="rc-fillwrap"><div class="rc-fill" style="--h:50%;--fbg:rgba(46,140,150,.10);--fdot:rgba(46,140,150,.34)"></div></div>
            <div class="rc-leg"><span class="l">Last month</span></div>
          </div>
          <div class="rc-c">
            <div class="rc-num" data-pay="svc">—</div>
            <div class="rc-fillwrap"><div class="rc-fill" style="--h:50%;--fbg:rgba(224,255,2,.24);--fdot:rgba(150,184,0,.82)"></div></div>
            <div class="rc-leg"><span class="l">Service / staff</span></div>
          </div>
        </div>
      </div>

      <div class="hm-card td-card">
        <div class="td-h"><span class="t">To do</span><span class="n" id="tdCount">0</span></div>
        <div class="td-list" id="tdList">
          @foreach ($todoRows as $t)
            @php
                $isApplicant = isset($t->ApplicantID);
                $person = $isApplicant ? ucfirst($t->first_name) . ' ' . ucfirst($t->last_name) : ($t->rank_name ?? '');
                $photo = $isApplicant ? ($t->profileImg ?? '') : ($todoPics[$t->user_id] ?? '');
                $photo = $photo === $defaultPic ? '' : $photo;
                if (!$isApplicant) {
                    $title = ($t->Position ?? '') . ' vacancy';
                    $sub = ($t->rank_name ?? '') . ' approved';
                    $needsQuestionnaire = ($t->LinkShareOrNot ?? '') === 'No';
                    $btn = $needsQuestionnaire ? 'Add' : 'Create';
                    $sub = $needsQuestionnaire ? 'Questionnaire required to advertise' : $sub;
                    $href = $needsQuestionnaire ? route('resort.ta.add.Questionnaire') : route('resort.recruitement.hrdashboard');
                } elseif (($t->ApplicationStatus ?? '') === 'Sortlisted') {
                    $title = $person;
                    $sub = ($t->Position ?? '') . ' applicant · shortlisted';
                    $btn = 'Invite';
                    $href = route('resort.ta.Applicants', base64_encode($t->V_id));
                } else {
                    $title = $person;
                    $sub = ($t->Position ?? '') . ' applicant';
                    $btn = 'Review';
                    $href = route('resort.ta.Applicants', base64_encode($t->V_id));
                }
            @endphp
            @if (!$isApplicant || in_array($t->ApplicationStatus ?? '', ['Sortlisted', 'Complete']))
            <div class="td-item">
              <span class="td-ico">@if($photo)<img src="{{ $photo }}" alt="{{ $person }}" data-i="{{ $initials($person) }}" onerror="this.parentNode.textContent=this.dataset.i">@else{{ $initials($person) }}@endif</span>
              <span class="td-bd"><span class="td-tt">{{ $title }}<small>{{ $sub }}</small></span></span>
              <a class="td-btn" href="{{ $href }}">{{ $btn }}</a>
            </div>
            @endif
          @endforeach
        </div>
      </div>
    </aside>

    <!-- MIDDLE 54% -->
    <main class="col-mid">

      <!-- WAI Intelligence ask — glass -->
      <div class="wai-hero">
        <div class="wai-glass" id="waiGlass">
          <div class="gl"><span>WAI Intelligence</span><span class="wai-use" id="askUse" hidden></span></div>
          <input class="pin" id="askInput" type="text" placeholder="Ask about payroll, attendance, compliance…" autocomplete="off" maxlength="2000">
          <div class="hm-askrow">
            <span class="hm-askchip" role="button" tabindex="0">Who's absent today?</span>
            <span class="hm-askchip" role="button" tabindex="0">Why is F&amp;B overtime up?</span>
            <span class="hm-askchip" role="button" tabindex="0">Whose permits expire soon?</span>
            <span class="grow"></span>
            <button type="button" class="wai-orb" id="askGo" aria-label="Ask WAI"><i class="b1"></i><i class="b2"></i><i class="b3"></i><i class="b4"></i><span class="orb-glass"></span><span class="orb-sheen"></span><svg class="orb-ar" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5M5 12l7-7 7 7"/></svg></button>
          </div>
        </div>
      </div>

      <!-- WAI Insights spotlight -->
      <div class="hm-card wi-card" id="waiIns">
        <div class="wi-mh"><span class="t">WAI Insights</span><span class="u"><span id="wiTotal">0</span> open</span></div>
        <div class="wi-lens" id="wiLens"></div>
        <div class="wi-swrap"><div class="wi-scard" id="wiScard"></div></div>
        <div class="wi-snav">
          <button type="button" id="wiPrev" aria-label="Previous insight">‹</button>
          <span class="prog"><i id="wiProg"></i></span>
          <span class="ct" id="wiCt"></span>
          <button type="button" id="wiPlay" aria-label="Pause auto-rotate">⏸</button>
          <button type="button" id="wiNext" aria-label="Next insight">›</button>
        </div>
      </div>

      <!-- Workforce metrics -->
      <div class="m-grid" id="wfGrid">
        <div class="hm-card metric-c"><div class="m-val"><span class="mn tnum">{{ $total_employees }}</span></div><div class="m-lbl">Total workforce · {{ $plural($resort_departments_count, 'department') }}</div></div>
        <div class="hm-card metric-c"><div class="m-val"><span class="mn tnum">{{ $present_employee_counts }}</span> <span class="sec tnum">{{ $pctOf($present_employee_counts) }}%</span></div><div class="m-lbl">Present today</div></div>
        <div class="hm-card metric-c"><div class="m-val"><span class="mn tnum">{{ $leave_employee_counts }}</span> <span class="sec tnum">{{ $pctOf($leave_employee_counts) }}%</span></div><div class="m-lbl">On leave</div></div>
        <div class="hm-card metric-c"><div class="m-val"><span class="mn tnum">{{ $absent_employee_counts }}</span> <span class="sec tnum">{{ $absentPct }}%</span></div><div class="m-lbl">Absent</div></div>
      </div>

      <!-- Workforce planning: positions filled vs vacant (the figures the HOD and GM dashboards show) -->
      @php
        $wfpTotal = (int) ($manning_response->total_budgeted_employees ?? 0);
        $wfpFilled = (int) ($manning_response->total_filled_positions_count ?? 0);
        $wfpVacant = (int) ($manning_response->total_vacant_count ?? 0);
        $wfpPct = fn ($n) => $wfpTotal > 0 ? round($n * 100 / $wfpTotal) : 0;
      @endphp
      <div class="m-grid" id="wfpGrid">
        <div class="hm-card metric-c"><div class="m-val"><span class="mn tnum">{{ $wfpTotal }}</span></div><div class="m-lbl">Positions · <a href="{{ route('resort.workforceplan.dashboard') }}">Workforce planning</a></div></div>
        <div class="hm-card metric-c"><div class="m-val"><span class="mn tnum">{{ $wfpFilled }}</span> <span class="sec tnum">{{ $wfpPct($wfpFilled) }}%</span></div><div class="m-lbl">Filled positions</div></div>
        <div class="hm-card metric-c"><div class="m-val"><span class="mn tnum">{{ $wfpVacant }}</span> <span class="sec tnum">{{ $wfpPct($wfpVacant) }}%</span></div><div class="m-lbl">Vacant positions</div></div>
        <div class="hm-card metric-c"><div class="m-val"><span class="mn tnum">{{ (int) ($manning_response->TotalBudgtedemp ?? 0) }}</span></div><div class="m-lbl">Staff in those positions</div></div>
      </div>

      <!-- Attendance trend -->
      <div class="hm-card att-card">
        <div class="att-h">
          <span class="t">Attendance trend</span>
          <span class="att-drop">Last 12 months <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg></span>
        </div>
        <div class="att-chart" id="attChart"><div class="att-tip" id="attTip"></div></div>
        <div class="att-x" id="attX"></div>
      </div>

      <!-- Compliance (grouped, collapsible) -->
      <div class="hm-card cmp-card">
        <div class="cmp-h"><span class="t">Compliance</span><span class="n" id="cmpOpen">0 open</span><a class="va" href="{{ route('people.compliance.index') }}">View all</a></div>
        @foreach ([['crit', 'Critical'], ['high', 'High'], ['med', 'Medium']] as [$k, $label])
        <div class="cmp-group" id="cmp-{{ $k }}">
          <button type="button" class="cmp-gh" data-grp="cmp-{{ $k }}" aria-expanded="true"><span class="chev"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg></span><span class="sev sev-{{ $k }}">{{ $label }}</span><span class="gc">· 0</span></button>
          <div class="cmp-gbody"></div>
        </div>
        @endforeach
      </div>

    </main>

    <!-- RIGHT 23% — calendar + upcoming -->
    <aside class="hm-card col-right">
      <div class="crh"><span class="mo" id="calMo"></span><span class="cal-nav"><button type="button" id="calPrev" aria-label="Previous month">‹</button><button type="button" id="calNext" aria-label="Next month">›</button></span></div>
      <div class="dow-row"><span class="dow">Su</span><span class="dow">Mo</span><span class="dow">Tu</span><span class="dow">We</span><span class="dow">Th</span><span class="dow">Fr</span><span class="dow">Sa</span></div>
      <div class="mg" id="mg"></div>
      <div class="up-l">Upcoming events</div>
      <div class="up" id="upList"></div>
    </aside>

  </div>

  <!-- Duty roster + Action inbox — full width, below the 3-column layout (same data/behaviour as the
       Leader dashboard's cards: app/Services/LeaderDashboard.php, resources/views/.../leaderdashboard.blade.php) -->
  <div class="hr-more">
    <div class="hm-card rs-card">
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

    <section class="hm-card ai-card" aria-labelledby="aiTitle">
      <div class="ai-h"><h3 id="aiTitle">Action inbox</h3><span class="ai-cnt" id="aiCnt">0</span></div>
      <div class="ai-f" id="aiFilters" role="tablist" aria-label="Filter actions">
        <button type="button" class="ai-fc on" data-f="all">All <b data-c="all">0</b></button>
        <button type="button" class="ai-fc" data-f="res">Resignations <b data-c="res">0</b></button>
        <button type="button" class="ai-fc" data-f="req">Requests <b data-c="req">0</b></button>
        <button type="button" class="ai-fc" data-f="exit">Exit clearance <b data-c="exit">0</b></button>
      </div>
      <div id="aiList"></div>
    </section>
  </div>

  <!-- PAYROLL EXPANDED VIEW -->
  <div class="px" id="px" role="dialog" aria-modal="true" aria-label="Payroll details">
    <div class="px-panel">
      <div class="px-glass">
        <div class="px-head"><span class="t">Payroll</span><span class="sub" id="pxSub">— detailed view</span><button type="button" class="px-close" id="pxClose" aria-label="Close">✕</button></div>
        <div class="px-body">
          <div class="px-c">
            <div class="px-lbl">Forecast</div>
            <div class="px-num" data-px="fc">—</div>
            <div class="px-fillwrap"><div class="rc-fill" style="--h:50%;--fbg:rgba(1,70,83,.07);--fdot:rgba(1,70,83,.30)"></div></div>
            <div class="px-rows">
              <div class="px-r"><span>vs last month</span><b data-px="fcDelta">—</b></div>
            </div>
          </div>
          <div class="px-c">
            <div class="px-lbl" id="pxLastLbl">Last month</div>
            <div class="px-num" data-px="last">—</div>
            <div class="px-fillwrap"><div class="rc-fill" style="--h:50%;--fbg:rgba(46,140,150,.10);--fdot:rgba(46,140,150,.34)"></div></div>
            <div class="px-rows">
              <div class="px-r"><span>Staff</span><b>{{ $total_employees }}</b></div>
            </div>
          </div>
          <div class="px-c">
            <div class="px-lbl">Avg service charge</div>
            <div class="px-num"><span data-px="svc">—</span><span style="font-size:14px;font-weight:400;color:var(--muted)"> /staff</span></div>
            <div class="px-fillwrap"><div class="rc-fill" style="--h:50%;--fbg:rgba(224,255,2,.24);--fdot:rgba(150,184,0,.82)"></div></div>
            <div class="px-rows">
              <div class="px-r"><span>Total pool</span><b data-px="pool">—</b></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- COMPLIANCE SUGGESTION MODAL -->
  <div class="focus" id="cmpFix" role="dialog" aria-modal="true" aria-label="Suggested fix">
    <div class="focus-panel" style="height:auto;max-height:82vh;width:min(640px,92vw)">
      <div class="focus-glass">
        <div class="fp-head"><span class="t">WAI Suggestion</span><span class="sub" id="cfSub"></span><button type="button" class="fp-close" id="cfClose" aria-label="Close">✕</button></div>
        <div class="fp-body" id="cfBody"></div>
      </div>
    </div>
  </div>

  <!-- WAI ANSWER MODAL -->
  <div class="focus" id="focus" role="dialog" aria-modal="true" aria-label="WAI Intelligence">
    <div class="focus-panel">
      <div class="focus-glass">
        <div class="fp-head"><span class="t">WAI Intelligence</span><button type="button" class="fp-close" id="fpClose" aria-label="Close">✕</button></div>
        <div class="fp-body" id="fpBody"></div>
        <div class="fp-foot">
          <div class="wai-foot-row"><label class="wai-field"><input id="fpInput" type="text" placeholder="Ask a follow-up…" autocomplete="off" maxlength="2000"><span class="wai-quota" id="fpUse" hidden aria-live="polite"><span class="n" id="fpUseN"></span>&nbsp;left</span></label><button type="button" class="wai-orb" id="fpGo" aria-label="Ask WAI"><i class="b1"></i><i class="b2"></i><i class="b3"></i><i class="b4"></i><span class="orb-glass"></span><span class="orb-sheen"></span><svg class="orb-ar" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5M5 12l7-7 7 7"/></svg></button></div>
          <div class="wai-disclaimer">WAI is smart, not perfect yet — double-check key details. Always improving!</div>
        </div>
      </div>
    </div>
  </div>
</div>

        </div>
</div>
@endsection

@section('import-css')
<style>
#hrmd{
  --teal:#014653; --teal-2:#035b6c; --teal-3:#E6F0F1; --teal-soft:#f1f7f7;
  --ink:#14232A; --g1:#3A4145; --g2:#6B7378; --muted:#5D6F75; --faint:#93A4A9; --g4:#C7CDCF;
  --line:#E2EBEC; --line-2:#EEF4F4; --bg:#EEF2F2; --card:#fff;
  --ok:#1F9D6B; --ok-bg:#E7F4EC; --warn:#B7791F; --warn-bg:#FBF0DC; --err:#E5573F; --err-bg:#FDEEEB;
  --violet:#6B5FC7; --coral:#E0673F; --info:#1E7A85;
  --shadow:0 1px 2px rgba(1,70,83,.04),0 8px 22px rgba(1,70,83,.05);
  --font:'Poppins',-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
}
#hrmd *:not(.a-md *){box-sizing:border-box;margin:0;padding:0} /* answers (.a-md) keep their own spacing from wai-markdown.css */
#hrmd .tnum{font-variant-numeric:tabular-nums}
#hrmd .hm-card{background:var(--card);border:1px solid var(--line);border-radius:16px;box-shadow:var(--shadow)}
#hrmd .cols{display:grid;grid-template-columns:minmax(0,23fr) minmax(0,54fr) minmax(0,23fr);gap:16px;align-items:start}
#hrmd .col-left,#hrmd .col-right{position:sticky;top:20px;height:calc(100vh - 40px);display:flex;flex-direction:column;overflow:hidden}
#hrmd .col-mid{min-width:0;display:flex;flex-direction:column;gap:16px}
@media(max-width:1100px){#hrmd .cols{grid-template-columns:1fr}
#hrmd .col-left,#hrmd .col-right{position:static;height:auto;min-height:420px}}
#hrmd .col-left{padding:0;gap:14px}
#hrmd .pay-card{padding:16px;flex:none;display:flex;flex-direction:column}
#hrmd .rc-h{display:flex;align-items:center;justify-content:space-between}
#hrmd .rc-h .t{font-size:13px;font-weight:600}
#hrmd .rc-exp{width:26px;height:26px;border-radius:8px;border:1px solid var(--line);background:#fff;color:var(--g2);cursor:pointer;display:grid;place-items:center}
#hrmd .rc-exp svg{width:13px;height:13px}
#hrmd .rc-cols{flex:1;display:flex;margin-top:14px;min-height:0}
#hrmd .rc-c{flex:1;display:flex;flex-direction:column;padding:0 10px;min-width:0}
#hrmd .rc-c + .rc-c{border-left:1px solid var(--line-2)}
#hrmd .rc-num{font-size:20px;font-weight:600;letter-spacing:-.6px;line-height:1;color:var(--ink);white-space:nowrap}
#hrmd .rc-num .cur{font-size:13px;font-weight:400;color:var(--g4)}
#hrmd .rc-num .k{font-size:13px;font-weight:400;color:var(--g4)}
#hrmd .rc-fillwrap{flex:1;position:relative;margin-top:12px;min-height:0}
#hrmd .rc-fill{position:absolute;left:0;right:0;bottom:0;height:var(--h);border-radius:9px 9px 0 0;
  background-color:var(--fbg);
  background-image:radial-gradient(circle at center, var(--fdot) 1.5px, transparent 1.7px);
  background-size:9px 9px;background-position:center bottom;
  -webkit-mask-image:linear-gradient(180deg,transparent 0,#000 46%);
  mask-image:linear-gradient(180deg,transparent 0,#000 46%)}
#hrmd .rc-leg{margin-top:12px}
#hrmd .rc-leg .l{font-size:11px;font-weight:500;color:var(--g2);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#hrmd .px{position:fixed;inset:0;z-index:70;display:none;align-items:center;justify-content:center;padding:6vh 5vw;
  background:rgba(3,45,54,.4);backdrop-filter:blur(12px) saturate(115%);-webkit-backdrop-filter:blur(12px) saturate(115%)}
#hrmd .px.open{display:flex;animation:hrmdFade .18s ease}
#hrmd .px-panel{width:min(780px,94vw);max-height:86vh;border-radius:24px;padding:12px;overflow:hidden;display:flex;
  background:
    radial-gradient(58% 92% at 2% 1%, rgba(1,70,83,.5), transparent 56%),
    radial-gradient(56% 92% at 100% 104%, rgba(224,255,2,.42), transparent 55%),
    linear-gradient(135deg,#f9f8f1,#fbfbf4);
  box-shadow:0 40px 110px rgba(1,45,54,.45);animation:hrmdPop .22s cubic-bezier(.34,1.56,.64,1)}
#hrmd .px-glass{flex:1;min-width:0;display:flex;flex-direction:column;border-radius:16px;overflow:hidden;position:relative;
  background:rgba(255,255,255,.78);
  backdrop-filter:blur(26px) saturate(185%);-webkit-backdrop-filter:blur(26px) saturate(185%);
  border:1px solid rgba(255,255,255,.7);box-shadow:inset 0 1px 1px rgba(255,255,255,.8)}
#hrmd .px-head{display:flex;align-items:center;gap:10px;padding:18px 22px;border-bottom:1px solid rgba(1,70,83,.09)}
#hrmd .px-head .t{font-size:15px;font-weight:600;color:var(--teal)}
#hrmd .px-head .sub{font-size:12.5px;color:var(--muted)}
#hrmd .px-close{margin-left:auto;width:34px;height:34px;border-radius:50%;border:1px solid rgba(255,255,255,.9);background:rgba(255,255,255,.55);color:var(--g2);font-size:15px;cursor:pointer;flex:none}
#hrmd .px-close:hover{background:#fff}
#hrmd .px-body{padding:26px 14px 24px;overflow-y:auto;display:flex}
#hrmd .px-c{flex:1;padding:0 20px;min-width:0}
#hrmd .px-c + .px-c{border-left:1px solid var(--line-2)}
#hrmd .px-lbl{font-size:11px;font-weight:600;letter-spacing:.4px;text-transform:uppercase;color:var(--faint)}
#hrmd .px-num{font-size:31px;font-weight:600;letter-spacing:-1px;margin-top:9px;color:var(--ink);white-space:nowrap}
#hrmd .px-num .cur{font-size:17px;font-weight:400;color:var(--g4)}
#hrmd .px-fillwrap{position:relative;height:150px;margin-top:18px}
#hrmd .px-fillwrap .rc-fill{border-radius:11px 11px 0 0}
#hrmd .px-hpct{position:absolute;top:0;left:0;font-size:12.5px;font-weight:600;color:var(--g2)}
#hrmd .px-rows{margin-top:16px}
#hrmd .px-r{display:flex;align-items:center;justify-content:space-between;padding:9px 0;font-size:12px;color:var(--muted);border-top:1px solid var(--line-2)}
#hrmd .px-r:first-child{border-top:none}
#hrmd .px-r b{color:var(--ink);font-weight:600}
@media (max-width:620px){#hrmd .px-body{flex-direction:column;gap:8px}
#hrmd .px-c+.px-c{border-left:none;border-top:1px solid var(--line-2);padding-top:16px}
#hrmd .px-fillwrap{height:96px}}
@media (prefers-reduced-transparency:reduce){#hrmd .px-glass{background:#fff;backdrop-filter:none;-webkit-backdrop-filter:none}
#hrmd .px-close{background:#fff}}
#hrmd .td-card{flex:1;min-height:160px;padding:16px 16px 8px;display:flex;flex-direction:column;overflow:hidden}
#hrmd .td-h{display:flex;align-items:baseline;gap:8px;margin-bottom:2px}
#hrmd .td-h .t{font-size:13px;font-weight:600}
#hrmd .td-h .n{font-size:11px;font-weight:600;color:var(--teal);background:var(--teal-soft);border-radius:20px;padding:1px 8px}
#hrmd .td-h .va{margin-left:auto;font-size:11.5px;font-weight:600;color:var(--teal);cursor:pointer;text-decoration:none}
#hrmd .td-h .va:hover{text-decoration:underline}
#hrmd .td-list{flex:1;overflow-y:auto;margin:10px -4px 0;padding:0 4px;display:flex;flex-direction:column;gap:9px}
#hrmd .td-list::-webkit-scrollbar{width:6px}
#hrmd .td-list::-webkit-scrollbar-thumb{background:var(--g4);border-radius:6px}
#hrmd .td-item{display:flex;align-items:center;gap:11px;padding:11px 12px;border-radius:12px;background:#fafcfc;border:1px solid var(--line-2)}
#hrmd .td-ico{width:32px;height:32px;border-radius:50%;flex:none;display:grid;place-items:center;font-size:10.5px;font-weight:600;color:var(--teal);background:#E1EBEC;overflow:hidden;background-size:cover;background-position:center}
#hrmd .td-ico img{width:100%;height:100%;object-fit:cover}
#hrmd .td-bd{flex:1;min-width:0}
#hrmd .td-tt{font-size:12px;color:var(--ink);line-height:1.3}
#hrmd .td-tt small{display:block;font-size:10.5px;color:var(--muted);margin-top:1px}
#hrmd .td-btn{flex:none;font-size:11px;font-weight:600;border-radius:8px;padding:6px 11px;cursor:pointer;border:1px solid var(--teal-3);background:var(--teal-soft);color:var(--teal)}
#hrmd .td-btn:hover{background:var(--teal-3)}
#hrmd .td-btn.green{border-color:#CFEADB;background:#EAF6EF;color:#1F9D6B}
#hrmd .td-btn.green:hover{background:#DCEFE4}
#hrmd .td-btn.red{border-color:#F6D8D0;background:#FCEFEC;color:#D2543C}
#hrmd .td-btn.red:hover{background:#F9E2DC}
#hrmd .td-btn.amber{border-color:#F1E3BE;background:#FBF4E1;color:#B0791F}
#hrmd .td-btn.amber:hover{background:#F7EBCB}
#hrmd .wai-hero{position:relative;border-radius:18px;padding:14px;overflow:hidden;
  box-shadow:0 1px 2px rgba(1,70,83,.05),0 16px 40px rgba(1,70,83,.10);
  background:
    radial-gradient(78% 125% at 5% 2%, rgba(1,70,83,.52), transparent 58%),
    radial-gradient(70% 120% at 98% 108%, rgba(224,255,2,.48), transparent 56%),
    linear-gradient(135deg,#f9f8f1,#fbfbf4)}
#hrmd .wai-glass{position:relative;border-radius:14px;padding:18px;overflow:hidden;
  background:rgba(255,255,255,.62);
  backdrop-filter:blur(24px) saturate(185%);-webkit-backdrop-filter:blur(24px) saturate(185%);
  border:1px solid rgba(255,255,255,.72);
  box-shadow:inset 0 0 24px rgba(255,255,255,.18),0 10px 28px rgba(1,45,54,.16)}
#hrmd .wai-glass::before{content:"";position:absolute;inset:0;pointer-events:none;background:radial-gradient(220px 150px at var(--mx,75%) var(--my,-10%),rgba(255,255,255,.4),transparent 60%);opacity:.9;transition:opacity .3s}
#hrmd .wai-glass .gl{position:relative;font-size:13px;font-weight:600;color:var(--teal);margin-bottom:12px;display:flex;align-items:center;justify-content:space-between;gap:10px}
#hrmd .wai-glass .pin{position:relative;width:100%;border:none;background:none;outline:none;font:inherit;font-size:15px;color:var(--ink);padding:2px}
#hrmd .wai-glass .pin::placeholder{color:var(--muted)}
#hrmd .wai-use,#hrmd .fp-use{font-size:11px;font-weight:400;color:var(--muted)}
#hrmd .fp-use{padding:0 6px;text-align:right}
#hrmd .wai-use.is-low,#hrmd .fp-use.is-low{color:var(--warn)}
#hrmd .wai-use.is-out,#hrmd .fp-use.is-out{color:var(--err)}
#hrmd .wai-orb:disabled{opacity:.45;cursor:not-allowed;transform:none}
/* ===== WAI "thinking" loader — inline glow dot + pulsing letters ===== */
#hrmd .wai-loader-wrapper{position:relative;display:flex;width:fit-content;margin-top:6px;align-items:center;gap:9px;user-select:none;color:#014653;font-size:12px;font-weight:500;letter-spacing:.3px}
#hrmd .wai-loader{width:20px;height:20px;aspect-ratio:1/1;border-radius:50%;background:transparent;flex:none;animation:waiLoaderRotate 1.5s linear infinite;z-index:0}
@keyframes waiLoaderRotate{
  0%{transform:rotate(90deg);box-shadow:0 1px 1px 0 #fff inset,0 3px 5px 0 #ff5f9f inset,0 4px 4px 0 #0693ff inset}
  50%{transform:rotate(270deg);box-shadow:0 1px 1px 0 #fff inset,0 3px 5px 0 #9f5fff inset,0 4px 4px 0 #28a9ff inset}
  100%{transform:rotate(450deg);box-shadow:0 1px 1px 0 #fff inset,0 3px 5px 0 #ff5f9f inset,0 4px 4px 0 #28a9ff inset}}
#hrmd .wai-letter-wrapper{display:flex;gap:1px}
#hrmd .wai-loader-letter{display:inline-block;opacity:.4;transform:translateY(0);animation:waiLoaderLetter 2s infinite;z-index:1}
#hrmd .wai-loader-letter:nth-child(1){animation-delay:0s}
#hrmd .wai-loader-letter:nth-child(2){animation-delay:.1s}
#hrmd .wai-loader-letter:nth-child(3){animation-delay:.2s}
#hrmd .wai-loader-letter:nth-child(4){animation-delay:.3s}
#hrmd .wai-loader-letter:nth-child(5){animation-delay:.4s}
#hrmd .wai-loader-letter:nth-child(6){animation-delay:.5s}
#hrmd .wai-loader-letter:nth-child(7){animation-delay:.6s}
#hrmd .wai-loader-letter:nth-child(8){animation-delay:.7s}
@keyframes waiLoaderLetter{0%,100%{opacity:.4;transform:translateY(0)}20%{opacity:1;transform:scale(1.15)}40%{opacity:.7;transform:translateY(0)}}
@media (prefers-reduced-motion:reduce){#hrmd .wai-loader{animation-duration:6s}#hrmd .wai-loader-letter{animation:none;opacity:.9}}
/* ===== WAI glass-blob orb (send button — ask card + follow-up bar) ===== */
#hrmd .wai-orb{position:relative;width:38px;height:38px;border-radius:50%;overflow:hidden;isolation:isolate;flex:none;cursor:pointer;display:grid;place-items:center;
  appearance:none;-webkit-appearance:none;border:none;outline:none;padding:0;
  background:radial-gradient(circle at 50% 45%,#04576a,#02404e);
  box-shadow:0 6px 16px rgba(1,70,83,.28);
  transition:transform .14s cubic-bezier(.34,1.56,.64,1),box-shadow .3s,background .22s ease}
#hrmd .wai-orb:hover{transform:scale(1.07)}
#hrmd .wai-orb:focus-visible{box-shadow:0 0 0 3px rgba(224,255,2,.6)}
#hrmd .wai-orb:active{transform:scale(.93);background:radial-gradient(circle at 50% 45%,#eaff6b,#c4e000)}
#hrmd .wai-orb i{position:absolute;border-radius:50%;filter:blur(3px);transition:background .22s ease}
#hrmd .wai-orb i.b1{width:66%;height:66%;background:#E0FF02;left:4%;top:32%;animation:waiOrbF1 2s ease-in-out infinite}
#hrmd .wai-orb i.b2{width:54%;height:54%;background:#7FBEC6;left:48%;top:10%;animation:waiOrbF2 2.6s ease-in-out infinite}
#hrmd .wai-orb i.b3{width:38%;height:38%;background:#E0FF02;left:52%;top:50%;animation:waiOrbF3 1.6s ease-in-out infinite}
#hrmd .wai-orb i.b4{width:48%;height:48%;background:#2E7B86;left:14%;top:4%;animation:waiOrbF4 2.8s ease-in-out infinite}
#hrmd .wai-orb .orb-glass{position:absolute;inset:0;z-index:4;border-radius:50%;pointer-events:none;
  background:linear-gradient(160deg,rgba(255,255,255,.26),rgba(255,255,255,.04));
  backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px)}
#hrmd .wai-orb .orb-sheen{position:absolute;inset:0;z-index:5;border-radius:50%;pointer-events:none;
  background:radial-gradient(circle at 33% 26%,rgba(255,255,255,.5),transparent 46%)}
#hrmd .wai-orb .orb-ar{position:relative;z-index:6;width:16px;height:16px;color:#014653;transform:rotate(38deg);
  filter:drop-shadow(0 0 2px rgba(255,255,255,.85)) drop-shadow(0 1px 1px rgba(255,255,255,.6))}
#hrmd .wai-orb:active .orb-ar{color:#013a46;filter:drop-shadow(0 1px 1px rgba(255,255,255,.5))}
#hrmd .wai-orb:active i.b1,#hrmd .wai-orb:active i.b3{background:#014653}
#hrmd .wai-orb:active i.b2{background:#013a46}
#hrmd .wai-orb:active i.b4{background:#2E7B86}
@keyframes waiOrbF1{0%,100%{transform:translate(0,0) scale(1)}50%{transform:translate(20%,-16%) scale(1.16)}}
@keyframes waiOrbF2{0%,100%{transform:translate(0,0) scale(1)}50%{transform:translate(-18%,14%) scale(1.1)}}
@keyframes waiOrbF3{0%,100%{transform:translate(0,0) scale(.95)}50%{transform:translate(-14%,-12%) scale(1.2)}}
@keyframes waiOrbF4{0%,100%{transform:translate(0,0) scale(1.05)}50%{transform:translate(16%,18%) scale(.9)}}
@media (prefers-reduced-motion:reduce){#hrmd .wai-orb i{animation-duration:30s}}
@media (prefers-reduced-transparency:reduce){#hrmd .wai-orb .orb-glass{backdrop-filter:none;-webkit-backdrop-filter:none;background:rgba(255,255,255,.14)}}
#hrmd .hm-askrow{display:flex;align-items:center;gap:8px;margin-top:20px;position:relative}
#hrmd .hm-askrow .grow{flex:1}
#hrmd .hm-askchip{display:inline-block;font-size:11.5px;color:var(--teal);background:rgba(255,255,255,.55);border:1px solid rgba(255,255,255,.8);border-radius:20px;padding:7px 12px;cursor:pointer;backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);white-space:nowrap}
#hrmd .hm-askchip{transition:transform .15s ease,box-shadow .15s ease}
#hrmd .hm-askchip:hover{transform:translateY(-1px);box-shadow:0 4px 10px rgba(1,70,83,.14)}
@media (prefers-reduced-transparency:reduce){#hrmd .wai-glass{background:#fff;backdrop-filter:none;-webkit-backdrop-filter:none}#hrmd .hm-askchip{background:#fff;backdrop-filter:none;-webkit-backdrop-filter:none}}
#hrmd .wi-card{overflow:hidden}
#hrmd .wi-mh{display:flex;align-items:center;gap:8px;padding:12px 16px;color:#fff;background:linear-gradient(100deg,#013a44 0%,#025a68 44%,#2f8f72 72%,#c7ec3f 132%)}
#hrmd .wi-mh .t{font-size:14px;font-weight:600}
#hrmd .wi-mh .u{margin-left:auto;font-size:11px;color:rgba(255,255,255,.85)}
#hrmd .wi-mh .u u{cursor:pointer}
#hrmd .wi-lens{display:flex;gap:6px;flex-wrap:wrap;padding:12px 16px 2px}
#hrmd .wi-lchip{font-size:11px;font-weight:500;color:var(--g2);background:#fff;border:1px solid var(--line);border-radius:20px;padding:4px 11px;cursor:pointer;display:inline-flex;align-items:center;gap:5px}
#hrmd .wi-lchip .cnt{font-size:10px;opacity:.8}
#hrmd .wi-lchip.on{background:var(--teal);border-color:var(--teal);color:#fff}
#hrmd .wi-swrap{position:relative;margin:16px 18px 6px}
#hrmd .wi-swrap::before,#hrmd .wi-swrap::after{content:"";position:absolute;left:14px;right:14px;border-radius:12px;background:#fff;border:1px solid var(--line)}
#hrmd .wi-swrap::before{top:-6px;height:14px;opacity:.65}
#hrmd .wi-swrap::after{top:-11px;left:24px;right:24px;height:14px;opacity:.35}
#hrmd .wi-scard{position:relative;background:#fff;border:1px solid var(--line);border-radius:12px;padding:16px 16px 14px;min-height:128px}
#hrmd .wi-scard .tag{font-size:10px;font-weight:600;letter-spacing:.5px;text-transform:uppercase;color:var(--faint)}
#hrmd .wi-scard .tag .sv{font-weight:600}
#hrmd .wi-scard .x{font-size:15px;color:var(--ink);line-height:1.5;margin-top:9px;font-weight:400}
#hrmd .wi-scard .x b{font-weight:600}
#hrmd .wi-scard .a{display:flex;align-items:center;gap:14px;margin-top:14px;flex-wrap:wrap}
#hrmd .wi-scard .a a{font-size:12px;font-weight:600;color:var(--teal);cursor:pointer}
#hrmd .wi-scard .a a.mut{color:var(--muted);font-weight:500}
#hrmd .wi-snav{display:flex;align-items:center;gap:12px;padding:10px 18px 14px}
#hrmd .wi-snav button{width:28px;height:28px;border-radius:50%;border:1px solid var(--line);background:#fff;color:var(--g2);cursor:pointer;font-size:14px;display:grid;place-items:center;flex:none}
#hrmd .wi-snav button:hover{background:var(--teal-soft);color:var(--teal)}
#hrmd .wi-snav .prog{flex:1;height:4px;border-radius:4px;background:var(--line-2);overflow:hidden}
#hrmd .wi-snav .prog i{display:block;height:100%;background:linear-gradient(90deg,var(--teal),var(--lime));transition:width .3s}
#hrmd .wi-snav .ct{font-size:11px;color:var(--faint);font-variant-numeric:tabular-nums;white-space:nowrap}
#hrmd .sev-watch{color:var(--teal)}
@media(max-width:640px){}
#hrmd .m-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-top:12px}
@media(max-width:760px){#hrmd .m-grid{grid-template-columns:1fr 1fr}}
#hrmd .metric-c{padding:20px}
#hrmd .m-val{display:flex;align-items:baseline;gap:6px;font-size:50px;font-weight:400;letter-spacing:-2px;line-height:1;color:var(--ink)}
#hrmd .m-val .mn{display:inline-block;transform:scaleX(.88);transform-origin:left center}
#hrmd .m-val .sec{font-size:15px;font-weight:400;color:var(--g4);letter-spacing:-.3px}
#hrmd .m-val .arw{font-size:14px;font-weight:600}
#hrmd .m-val .arw.good{color:var(--ok)}
#hrmd .m-val .arw.bad{color:var(--err)}
#hrmd .m-lbl{font-size:12.5px;color:var(--muted);margin-top:12px}
#hrmd .att-card{padding:16px 18px}
#hrmd .att-h{display:flex;align-items:center;justify-content:space-between}
#hrmd .att-h .t{font-size:14px;font-weight:600}
#hrmd .att-drop{font-size:11.5px;font-weight:600;color:var(--g2);background:#fff;border:1px solid var(--line);border-radius:20px;padding:5px 11px;display:inline-flex;align-items:center;gap:6px;cursor:pointer}
#hrmd .att-drop svg{width:11px;height:11px}
#hrmd .att-chart{position:relative;display:flex;align-items:flex-end;gap:8px;height:158px;margin-top:18px}
#hrmd .att-col{flex:1;height:100%;display:flex;flex-direction:column;justify-content:flex-end;position:relative;cursor:pointer}
#hrmd .att-track{position:absolute;inset:0;border-radius:7px;overflow:hidden;background-color:#fafcfc;
  background-image:repeating-linear-gradient(45deg, var(--line-2) 0 3px, transparent 3px 7px)}
#hrmd .att-bar{position:relative;height:var(--h);border-radius:7px 7px 0 0;opacity:.88;transition:opacity .15s,box-shadow .15s;
  background:linear-gradient(180deg,#E0FF02 0%,#eaf7a3 46%,#e8f0d6 100%);
  background-size:100% 158px;background-position:left bottom;background-repeat:no-repeat}
#hrmd .att-bar::after{content:"";position:absolute;top:5px;left:18%;right:18%;height:2px;border-radius:2px;background:rgba(1,70,83,.28)}
#hrmd .att-col.active .att-bar{opacity:1;box-shadow:0 0 0 1.5px rgba(1,70,83,.22)}
#hrmd .att-col.active .att-bar::after{background:rgba(1,70,83,.5)}
#hrmd .att-x{display:flex;gap:8px;margin-top:9px}
#hrmd .att-x span{flex:1;text-align:center;font-size:9.5px;color:var(--faint);font-weight:500}
#hrmd .att-x span.on{color:var(--teal);font-weight:600}
#hrmd .att-tip{position:absolute;pointer-events:none;z-index:5;left:0;top:0;
  background:rgba(255,255,255,.55);
  backdrop-filter:blur(16px) saturate(180%);-webkit-backdrop-filter:blur(16px) saturate(180%);
  border:1px solid rgba(255,255,255,.85);border-radius:13px;
  box-shadow:0 14px 34px rgba(1,45,54,.22),inset 0 1px 1px rgba(255,255,255,.8);
  padding:11px 13px;min-width:170px;opacity:0;transition:opacity .12s}
#hrmd .att-tip .tm{font-size:11.5px;font-weight:400;color:var(--muted);margin-bottom:9px}
#hrmd .att-tip .tm b{color:var(--ink);font-weight:400}
#hrmd .att-tip .tr{display:flex;align-items:center;justify-content:space-between;gap:18px;font-size:11.5px;padding:3px 0}
#hrmd .att-tip .tr .lb{display:flex;align-items:center;gap:7px;color:var(--muted)}
#hrmd .att-tip .tr .lb i{width:9px;height:9px;border-radius:3px;flex:none;display:inline-block}
#hrmd .att-tip .tr b{color:var(--ink);font-weight:600}
#hrmd .att-tip .tr b .pc{color:var(--faint);font-weight:400;font-size:10.5px;margin-left:4px}
@media (prefers-reduced-transparency:reduce){#hrmd .att-tip{background:#fff;backdrop-filter:none;-webkit-backdrop-filter:none}}
#hrmd .cmp-card{padding:16px 18px}
#hrmd .cmp-h{display:flex;align-items:center;gap:10px;margin-bottom:2px}
#hrmd .cmp-h .t{font-size:14px;font-weight:600}
#hrmd .cmp-h .n{font-size:11px;font-weight:600;color:var(--teal);background:var(--teal-soft);border-radius:20px;padding:1px 8px}
#hrmd .cmp-h .va{margin-left:auto;font-size:11.5px;font-weight:600;color:var(--teal);cursor:pointer;text-decoration:none}
#hrmd .cmp-h .va:hover{text-decoration:underline}
#hrmd .sev{font-weight:600}
#hrmd .sev-crit{color:var(--err)}
#hrmd .sev-high{color:var(--warn)}
#hrmd .sev-med{color:var(--info)}
#hrmd .cmp-group{border-top:1px solid var(--line-2)}
#hrmd .cmp-group:first-of-type{border-top:none;margin-top:6px}
#hrmd .cmp-gh{display:flex;align-items:center;gap:8px;width:100%;background:none;border:none;cursor:pointer;padding:13px 2px;font:inherit;text-align:left}
#hrmd .cmp-gh .chev{color:var(--g4);transition:transform .2s;display:grid;place-items:center}
#hrmd .cmp-gh .chev svg{width:13px;height:13px}
#hrmd .cmp-group.collapsed .cmp-gh .chev{transform:rotate(-90deg)}
#hrmd .cmp-gh .sev{font-size:10.5px;letter-spacing:.5px;text-transform:uppercase}
#hrmd .cmp-gh .gc{color:var(--g4);font-weight:600;font-size:10.5px}
#hrmd .cmp-gbody{overflow:hidden;max-height:600px;transition:max-height .28s ease}
#hrmd .cmp-group.collapsed .cmp-gbody{max-height:0}
#hrmd .cmp-item{display:flex;align-items:center;gap:11px;padding:10px 2px 10px 22px;border-bottom:1px solid var(--line-2)}
#hrmd .cmp-gbody .cmp-item:last-child{border-bottom:none;padding-bottom:14px}
#hrmd .cmp-av{width:30px;height:30px;border-radius:50%;flex:none;display:grid;place-items:center;font-size:10px;font-weight:600;color:var(--teal);background:#E1EBEC;overflow:hidden}
#hrmd .cmp-av img{width:100%;height:100%;object-fit:cover}
#hrmd .cmp-main{flex:1;min-width:0}
#hrmd .cmp-rule{font-size:12.5px;color:var(--ink)}
#hrmd .cmp-sub{font-size:10.5px;color:var(--muted);margin-top:1px}
#hrmd .cmp-rep{font-size:11px;color:var(--muted);white-space:nowrap}
#hrmd .cmp-fix{flex:none;font-size:11px;font-weight:600;color:var(--teal);white-space:nowrap;border:1px solid var(--teal-3);border-radius:9px;padding:6px 12px;cursor:pointer;
  background:radial-gradient(130% 150% at 100% 130%, rgba(224,255,2,.42), transparent 55%),radial-gradient(120% 150% at 0% -30%, rgba(1,70,83,.12), transparent 55%),#fff}
#hrmd .cmp-fix:hover{filter:brightness(.985)}
#hrmd .col-right{padding:18px}
#hrmd .crh{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px}
#hrmd .crh .mo{font-size:14.5px;font-weight:600}
#hrmd .cal-nav{display:flex;gap:5px}
#hrmd .cal-nav button{width:26px;height:26px;border-radius:8px;border:1px solid var(--line);background:#fff;color:var(--g2);cursor:pointer;font-size:14px}
#hrmd .dow-row{display:grid;grid-template-columns:repeat(7,1fr);gap:3px;margin-bottom:4px}
#hrmd .dow{font-size:10px;font-weight:600;color:var(--faint);text-align:center}
#hrmd .mg{display:grid;grid-template-columns:repeat(7,1fr);gap:3px}
#hrmd .dcell{aspect-ratio:1;display:flex;align-items:center;justify-content:center;position:relative;font-size:12.5px;color:var(--g1);border-radius:8px;font-variant-numeric:tabular-nums}
#hrmd .dcell.mut{color:var(--g4)}
#hrmd .dcell.ev{background:var(--teal-soft);color:var(--teal);font-weight:600}
#hrmd .dcell.ev .evd{position:absolute;bottom:4px;left:50%;transform:translateX(-50%);width:4px;height:4px;border-radius:50%;background:var(--teal)}
#hrmd .dcell.today{background:var(--teal);color:#fff;font-weight:600}
#hrmd .dcell.today .evd{background:var(--n-lime,#D9FF33)}
#hrmd .up-l{font-size:11px;font-weight:600;letter-spacing:.5px;text-transform:uppercase;color:var(--faint);margin:16px 0 4px;padding-top:14px;border-top:1px solid var(--line-2)}
#hrmd .up{flex:1;min-height:0;overflow-y:auto;padding-right:4px;display:flex;flex-direction:column;gap:8px}
#hrmd .up::-webkit-scrollbar{width:6px}
#hrmd .up::-webkit-scrollbar-thumb{background:var(--g4);border-radius:6px}
#hrmd .ue{display:flex;gap:11px;align-items:center;padding:11px 12px;border-radius:12px;background:#fafcfc;border:1px solid var(--line-2)}
#hrmd .ue .dchip{flex:none;width:40px;text-align:center}
#hrmd .ue .dchip .dd{font-size:15px;font-weight:600;line-height:1;color:var(--teal)}
#hrmd .ue .dchip .dm{font-size:10px;font-weight:600;letter-spacing:.4px;text-transform:uppercase;color:var(--faint)}
#hrmd .ue .ub{flex:1;min-width:0}
#hrmd .ue .ut{font-size:13px;font-weight:500;color:var(--ink);line-height:1.3}
#hrmd .ue .us{font-size:11.5px;color:var(--muted);margin-top:1px}
#hrmd .ue .ue-right{display:flex;flex-direction:column;align-items:flex-end;gap:5px;flex:none}
#hrmd .ue .uw{font-size:11px;font-weight:600;white-space:nowrap;color:var(--faint)}
#hrmd .ue .uw.soon{color:var(--err)}
#hrmd .ue .uw.today{color:var(--teal)}
#hrmd .ue .av{width:32px;height:32px;border-radius:50%;background:#E1EBEC;color:var(--teal);font-size:10px;font-weight:600;display:grid;place-items:center;flex:none;border:2px solid #fff;box-shadow:0 0 0 1.5px var(--teal-3);overflow:hidden}
#hrmd .ue .av img{width:100%;height:100%;object-fit:cover}
#hrmd .ue .stack{display:flex}
#hrmd .ue .stack .av{margin-left:-11px}
#hrmd .ue .stack .av:first-child{margin-left:0}
#hrmd .ue .stack .more{width:32px;height:32px;border-radius:50%;background:var(--teal-soft);color:var(--teal);font-size:9.5px;font-weight:600;display:grid;place-items:center;border:2px solid #fff;margin-left:-11px}
#hrmd .focus{position:fixed;inset:0;z-index:80;display:none;align-items:center;justify-content:center;padding:5vh 5vw;
  background:rgba(3,45,54,.4);backdrop-filter:blur(12px) saturate(115%);-webkit-backdrop-filter:blur(12px) saturate(115%)}
#hrmd .focus.open{display:flex;animation:hrmdFade .18s ease}
@keyframes hrmdFade{from{opacity:0}to{opacity:1}}
#hrmd .focus-panel{width:80vw;max-width:1120px;height:82vh;border-radius:24px;padding:12px;overflow:hidden;display:flex;
  background:
    radial-gradient(58% 92% at 2% 1%, rgba(1,70,83,.5), transparent 56%),
    radial-gradient(56% 92% at 100% 104%, rgba(224,255,2,.42), transparent 55%),
    linear-gradient(135deg,#f9f8f1,#fbfbf4);
  box-shadow:0 40px 110px rgba(1,45,54,.45);animation:hrmdPop .22s cubic-bezier(.34,1.56,.64,1)}
@keyframes hrmdPop{from{transform:scale(.96);opacity:0}to{transform:scale(1);opacity:1}}
#hrmd .focus-glass{flex:1;min-width:0;display:flex;flex-direction:column;border-radius:16px;overflow:hidden;position:relative;
  background:rgba(255,255,255,.74);
  backdrop-filter:blur(26px) saturate(185%);-webkit-backdrop-filter:blur(26px) saturate(185%);
  border:1px solid rgba(255,255,255,.7);box-shadow:inset 0 1px 1px rgba(255,255,255,.8)}
#hrmd .focus-glass::after{content:"";position:absolute;left:5%;right:5%;top:0;height:1px;pointer-events:none;background:linear-gradient(90deg,transparent,rgba(255,255,255,.9),transparent);z-index:2}
#hrmd .fp-head{display:flex;align-items:center;gap:10px;padding:18px 22px;border-bottom:1px solid rgba(1,70,83,.09)}
#hrmd .fp-head .t{font-size:15px;font-weight:600;color:var(--teal)}
#hrmd .fp-head .sub{font-size:12.5px;color:var(--muted)}
#hrmd .fp-close{margin-left:auto;width:34px;height:34px;border-radius:50%;border:1px solid rgba(255,255,255,.9);background:rgba(255,255,255,.55);color:var(--g2);font-size:15px;cursor:pointer;flex:none}
#hrmd .fp-close:hover{background:#fff}
#hrmd .fp-body{flex:1;min-height:0;overflow-y:auto;padding:22px 26px;display:flex;flex-direction:column;gap:18px}
#hrmd .fp-body::-webkit-scrollbar{width:8px}
#hrmd .fp-body::-webkit-scrollbar-thumb{background:var(--g4);border-radius:8px}
#hrmd .q{align-self:flex-end;max-width:75%;background:var(--teal);color:#fff;border-radius:14px 14px 4px 14px;padding:11px 15px;font-size:13.5px}
#hrmd .a{align-self:flex-start;max-width:88%}
#hrmd .a .a-tag{display:inline-flex;align-items:center;gap:7px;font-size:10.5px;font-weight:600;letter-spacing:.5px;text-transform:uppercase;color:var(--teal);margin-bottom:8px}
#hrmd .a>p{font-size:13.5px;color:var(--g1);line-height:1.55}
#hrmd .a-list{margin-top:12px;border:1px solid rgba(255,255,255,.9);border-radius:12px;overflow:hidden;background:rgba(255,255,255,.62)}
#hrmd .a-row{display:flex;align-items:center;gap:12px;padding:11px 14px;border-bottom:1px solid rgba(1,70,83,.07)}
#hrmd .a-row:last-child{border-bottom:none}
#hrmd .a-row .av{width:32px;height:32px;border-radius:50%;flex:none;display:grid;place-items:center;font-size:11px;font-weight:600;color:#fff;background:var(--teal)}
#hrmd .a-row .ab{flex:1;min-width:0}
#hrmd .a-row .at{font-size:13px;font-weight:500}
#hrmd .a-row .ac{font-size:11.5px;color:var(--muted);margin-top:1px}
#hrmd .a-row .atag{font-size:10.5px;font-weight:600;border-radius:20px;padding:2px 9px;flex:none;background:var(--warn-bg);color:var(--warn)}
#hrmd .a-more{padding:10px 14px;font-size:12px;color:var(--muted);background:rgba(255,255,255,.42)}
#hrmd .fp-foot{padding:16px 22px;border-top:1px solid rgba(1,70,83,.09);display:flex;flex-direction:column;gap:9px}
/* footer: input row + AI disclaimer */
#hrmd .wai-foot-row{display:flex;align-items:center;gap:10px}
#hrmd .wai-field{display:flex;align-items:center;flex:1;border-radius:999px;background:#f1f7f7;border:1px solid #E2EBEC;padding:12px 18px;transition:background .18s,border-color .18s,box-shadow .18s}
#hrmd .wai-field:focus-within{border-color:#014653;box-shadow:0 0 0 3px rgba(1,70,83,.08)} /* keeps the soft fill on focus — the popup auto-focuses the field, so a white focus state read as "always white" and flickered when the tab lost focus */
#hrmd .wai-field>input{flex:1;border:none;outline:none;background:none;font:inherit;font-size:14px;color:#14232A}
#hrmd .wai-field>input::placeholder{color:#5D6F75}
#hrmd .wai-disclaimer{display:flex;align-items:center;justify-content:center;gap:5px;font-size:11px;color:#93A4A9;text-align:center}
@media (prefers-reduced-transparency:reduce){#hrmd .focus-glass{background:#fff;backdrop-filter:none;-webkit-backdrop-filter:none}
#hrmd .fp-close,#hrmd .a-list,#hrmd .a-more{background:#fff}}#hrmd{--lime:#D9FF33;font-family:var(--font);color:var(--ink);font-size:14px;line-height:1.5;letter-spacing:-.005em}
#hrmd .cols{align-items:start}
#hrmd .col-left,#hrmd .col-right{position:sticky;top:20px;height:calc(100vh - 40px);min-height:520px}
@media(max-width:1100px){#hrmd .col-left,#hrmd .col-right{height:auto;min-height:420px}}
#hrmd a.td-btn{text-decoration:none;display:inline-block}
#hrmd .cmp-fix{font-family:inherit}
#hrmd .a-row .av{overflow:hidden}
#hrmd .a-row .av img{width:100%;height:100%;object-fit:cover;border-radius:50%}
#hrmd #cfBody .a-list{background:var(--teal-soft);border-color:var(--teal-3)}
#hrmd #cfBody .a p{text-align:justify}
#hrmd .empty{font-size:12px;color:var(--muted);padding:10px 2px}
#hrmd .wi-scard .a a{text-decoration:none}
#hrmd .ue .ub{min-width:0}
#hrmd .ue .ut,#hrmd .ue .us{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
@media (prefers-reduced-motion:reduce){
  #hrmd *{animation:none!important;transition:none!important}
  #hrmd .wai-glass::before{display:none}
}
@media (prefers-reduced-transparency:reduce){
  #hrmd .wi-mh,#hrmd .wai-hero{background-image:none}
}

/* Duty roster + Action inbox — ported from leader-dashboard.css (#ldmd), same design tokens */
#hrmd .hr-more{margin-top:16px;display:flex;flex-direction:column;gap:16px}
#hrmd .rs-card{padding:16px 18px}
#hrmd .rs-h{display:flex;align-items:center;gap:10px;margin-bottom:2px}
#hrmd .rs-h .t{font-size:14px;font-weight:600}
#hrmd .rs-ctl{margin-left:auto;display:flex;gap:8px}
#hrmd .rs-sel{font:inherit;font-size:11.5px;color:var(--g1);background:#fff;border:1px solid var(--line);border-radius:10px;padding:6px 10px;outline:none;cursor:pointer}
#hrmd .rs-sel:hover{border-color:#cfdcde}
#hrmd .rs-sel:focus{border-color:var(--teal)}
#hrmd .tl{--nw:168px;margin-top:14px;position:relative;padding-top:30px}
#hrmd .tl-axis,#hrmd .tl-row{display:grid;grid-template-columns:var(--nw) minmax(0,1fr);align-items:center}
#hrmd .tl-ticks{position:relative;height:16px}
#hrmd .tl-ticks span{position:absolute;transform:translateX(-50%);font-size:10px;color:var(--faint);font-variant-numeric:tabular-nums}
#hrmd .tl-ticks span:first-child{transform:none}
#hrmd .tl-ticks span:last-child{transform:translateX(-100%)}
#hrmd .tl-body{position:relative}
#hrmd .tl-row{padding:7px 0;border-top:1px solid var(--line-2)}
#hrmd .tl-row:first-child{border-top:none}
#hrmd .tl-who{display:flex;align-items:center;gap:10px;min-width:0;padding-right:12px}
#hrmd .tl-who .rs-main{min-width:0}
#hrmd .rs-nm{font-size:12.5px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#hrmd .rs-ps{font-size:10.5px;color:var(--muted);margin-top:1px}
#hrmd .tl-track{position:relative;height:30px;border-radius:8px;background:repeating-linear-gradient(90deg,var(--line-2) 0 1px,transparent 1px calc(100%/6)),#fafcfc}
#hrmd .tl-bar{position:absolute;top:3px;bottom:3px;left:calc(var(--s)/24*100%);width:calc((var(--e) - var(--s))/24*100%);border-radius:7px;display:flex;align-items:center;padding:0 10px;font-size:11px;font-weight:500;white-space:nowrap;overflow:visible;cursor:default}
#hrmd .tl-bar.am{background:var(--teal);color:#fff}
#hrmd .tl-bar.pm{background:#CFE0E3;color:var(--teal)}
#hrmd .tl-bar.miss{background:#fff;color:var(--err);border:1px dashed rgba(229,87,63,.55)}
#hrmd .tl-bar.lv{color:var(--violet);background:repeating-linear-gradient(135deg,rgba(107,95,199,.10) 0 6px,rgba(107,95,199,.04) 6px 12px);border:1px solid rgba(107,95,199,.22)}
#hrmd .tl-bar[data-tip]:hover::after{content:attr(data-tip);position:absolute;left:50%;bottom:calc(100% + 10px);transform:translateX(-50%);z-index:5;font-size:11px;font-weight:500;color:#fff;white-space:nowrap;padding:7px 11px;border-radius:10px;background:rgba(20,35,42,.82);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);box-shadow:0 8px 20px rgba(1,45,54,.25);pointer-events:none}
#hrmd .tl-bar[data-tip]:hover::before{content:"";position:absolute;left:50%;bottom:calc(100% + 4px);transform:translateX(-50%);z-index:5;border:6px solid transparent;border-bottom:0;border-top-color:rgba(20,35,42,.82);pointer-events:none}
#hrmd .tl-now{position:absolute;top:0;bottom:0;left:calc(var(--nw) + (100% - var(--nw)) * var(--t) / 24);width:0;z-index:3;pointer-events:none}
#hrmd .tl-now::before{content:"";position:absolute;top:20px;bottom:0;left:-.75px;width:1.5px;background:var(--lime,#E0FF02);border-radius:1px}
#hrmd .tl-now span{position:absolute;top:0;left:50%;transform:translateX(-50%);font-size:10.5px;font-weight:600;color:#4a5a00;background:var(--lime,#E0FF02);border-radius:7px;padding:3px 9px;line-height:1.3;white-space:nowrap;font-variant-numeric:tabular-nums}
#hrmd .tl-wrap{position:relative;padding-top:6px}
#hrmd .tl-leg{display:flex;gap:16px;flex-wrap:wrap;margin-top:12px;padding-top:12px;border-top:1px solid var(--line-2)}
#hrmd .tl-leg span{display:inline-flex;align-items:center;gap:6px;font-size:10.5px;color:var(--muted);font-variant-numeric:tabular-nums}
#hrmd .tl-leg i{width:10px;height:10px;border-radius:3px;display:inline-block}
#hrmd .tl-empty{display:block;padding:18px 0;font-size:12px;color:var(--muted);text-align:center}
@media(max-width:760px){#hrmd .tl{--nw:118px}
#hrmd .tl-who .rs-ps{display:none}}

#hrmd .ai-card{padding:16px 18px}
#hrmd .ai-card button{font-family:inherit}
#hrmd .ai-card svg{display:block}
#hrmd .ai-h{display:flex;align-items:center;gap:8px;margin-bottom:12px}
#hrmd .ai-h h3{font-size:13.5px;font-weight:600}
#hrmd .ai-cnt{font-size:10.5px;font-weight:600;border-radius:10px;padding:2px 7px;background:#fdeeea;color:var(--err)}
#hrmd .ai-f{display:flex;gap:6px;margin-bottom:6px;flex-wrap:wrap}
#hrmd .ai-fc{font-size:12px;font-weight:500;color:var(--muted);background:#fff;border:1.5px solid var(--line);border-radius:9px;padding:6px 11px;cursor:pointer;display:inline-flex;gap:6px;align-items:center;transition:background .15s,border-color .15s,color .15s}
#hrmd .ai-fc b{font-weight:600;color:var(--ink)}
#hrmd .ai-fc:hover{border-color:#7FBEC6}
#hrmd .ai-fc.on{background:var(--teal);border-color:var(--teal);color:#fff}
#hrmd .ai-fc.on b{color:var(--lime,#E0FF02)}
#hrmd .ai-row{display:grid;grid-template-columns:112px minmax(0,1.3fr) minmax(0,1.5fr) 164px;gap:14px;align-items:center;padding:12px 4px;border-bottom:1px solid var(--line-2);transition:opacity .28s,transform .28s,background .15s}
#hrmd .ai-row:last-child{border-bottom:none}
#hrmd .ai-row:hover{background:#fafcfc}
#hrmd .ai-row.gone{opacity:0;transform:translateX(20px)}
#hrmd .ai-tag{display:inline-block;font-size:10.5px;font-weight:600;border-radius:6px;padding:3px 7px;white-space:nowrap}
#hrmd .ai-row>span:first-child{justify-self:start}
#hrmd .ai-tag.res{color:#8a3d2c;background:#fdeeea}
#hrmd .ai-tag.req{color:var(--teal);background:var(--teal-3)}
#hrmd .ai-tag.exit{color:#7a5410;background:#fbf1de}
#hrmd .ai-pp{display:flex;align-items:center;gap:10px;min-width:0}
#hrmd .ai-av{width:32px;height:32px;border-radius:50%;display:grid;place-items:center;font-size:11.5px;font-weight:600;flex:none;color:var(--teal);background:#E1EBEC;overflow:hidden}
#hrmd .ai-av img{width:100%;height:100%;object-fit:cover}
#hrmd .ai-nm{font-weight:600;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#hrmd .ai-rl{font-size:11.5px;color:var(--muted)}
#hrmd .ai-md{font-size:12px;color:var(--g1);min-width:0}
#hrmd .ai-mt{font-size:11.5px;color:var(--muted);margin-top:2px}
#hrmd .ai-od{color:var(--err);font-weight:600}
#hrmd .ai-ac{display:flex;gap:6px;align-items:center;justify-content:flex-end}
#hrmd a.ai-act{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:var(--teal);background:var(--teal-soft);border:1.5px solid var(--line);border-radius:10px;padding:7px 12px;cursor:pointer;white-space:nowrap;text-decoration:none;transition:background .15s,border-color .15s,box-shadow .15s,transform .12s}
#hrmd a.ai-act:hover{border-color:var(--teal);background:#f7ffd1;box-shadow:0 4px 14px rgba(224,255,2,.35)}
#hrmd a.ai-act:active{transform:scale(.97)}
#hrmd .ai-act svg{width:13px;height:13px}
#hrmd .ai-icb{width:30px;height:30px;border-radius:9px;border:1.5px solid var(--line);background:#fff;display:grid;place-items:center;cursor:pointer;color:inherit;text-decoration:none;transition:background .15s,border-color .15s}
#hrmd .ai-icb svg{width:14px;height:14px}
#hrmd .ai-icb.ok{color:var(--ok)}
#hrmd .ai-icb.ok:hover{background:#e8f6ef;border-color:var(--ok)}
#hrmd .ai-icb.no{color:var(--err)}
#hrmd .ai-icb.no:hover{background:#fdeeea;border-color:var(--err)}
#hrmd .ai-icb.vw{color:var(--teal)}
#hrmd .ai-icb.vw:hover{background:var(--teal-soft);border-color:var(--teal)}
#hrmd a.ai-act:focus-visible,#hrmd .ai-icb:focus-visible,#hrmd .ai-fc:focus-visible{outline:2px solid var(--teal);outline-offset:2px}
#hrmd .ai-empty{display:flex;flex-direction:column;align-items:center;text-align:center;gap:6px;padding:24px 10px;color:var(--faint,#93A4A9);font-size:12px}
#hrmd .ai-empty .ei{width:36px;height:36px;border-radius:11px;background:var(--teal-soft);display:grid;place-items:center;color:#7FBEC6}
#hrmd .ai-empty .ei svg{width:18px;height:18px}
#hrmd .ai-empty b{color:var(--g1);font-weight:600;font-size:12.5px}
@media (max-width:760px){#hrmd .ai-row{grid-template-columns:1fr auto;grid-template-areas:"tag ac" "pp pp" "md md";row-gap:8px}
#hrmd .ai-row>span:first-child{grid-area:tag}
#hrmd .ai-pp{grid-area:pp}
#hrmd .ai-md{grid-area:md}
#hrmd .ai-ac{grid-area:ac}}
@media (prefers-reduced-motion:reduce){#hrmd .ai-row,#hrmd .ai-fc,#hrmd a.ai-act{transition:none}}
</style>
@endsection

@section('import-scripts')
<script>
(function () {
  'use strict';
  var TOTAL = @json((int) $total_employees);
  var INSIGHTS = @json($insights);
  var EVENTS = @json($events);
  var INBOX = @json($hrInbox);
  var TODAY = @json($today->toDateString());
  var NOW_HM = @json(now()->format('H:i'));
  var URLS = {
    expenses: @json(route('payroll.getExpenses')),
    estimate: @json(route('payroll.dashboard.estimate-breakdown')),
    attendance: @json(route('resort.timeandattendance.GetYearWiseAttandanceData', ['year' => '__y__', 'date' => '__d__'])),
    todo: @json(route('resort.timeandattendance.todolist')),
    todoPage: @json(route('resort.timeandattendance.todolist')),
    taDash: @json(route('resort.recruitement.hrdashboard')),
    compliance: @json(route('people.compliance.list')),
    compliancePage: @json(route('people.compliance.index')),
    wai: @json(route('resort.wisdom.chat')),
    waiHistory: @json(route('resort.wisdom.history')),
    roster: @json($hrRosterUrl)
  };
  var CSRF = document.querySelector('meta[name="csrf-token"]');
  CSRF = CSRF ? CSRF.content : '';
  var root = document.getElementById('hrmd');
  var $ = function (id) { return document.getElementById(id); };
  var AJAX = { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
  function getJson(url) { return fetch(url, { headers: AJAX, credentials: 'same-origin' }).then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); }); }
  function postJson(url, body) {
    return fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin', body: JSON.stringify(body || {}) })
      .then(function (r) { return r.json().catch(function () { return {}; }).then(function (j) { j.__ok = r.ok; return j; }); });
  }
  function toast(type, title, msg, opts) { if (window.wisdomToast) window.wisdomToast(type, title, msg, opts); }
  function initials(n) { return (String(n || '').trim().split(/\s+/).filter(Boolean).slice(0, 2).map(function (p) { return p[0].toUpperCase(); }).join('')) || '?'; }
  function el(tag, cls, text) { var e = document.createElement(tag); if (cls) e.className = cls; if (text != null) e.textContent = text; return e; }
  function fillAvatar(box, photo, name) {
    var ini = initials(name);
    if (!photo) { box.textContent = ini; return; }
    var img = new Image(); img.alt = name || ''; img.src = photo;
    img.onerror = function () { box.textContent = ini; };
    box.appendChild(img);
  }
  function plural(n, one, many) { return n + ' ' + (n === 1 ? one : (many || one + 's')); }

  /* ---------- Payroll (existing payroll.getExpenses + estimate-breakdown endpoints) ---------- */
  (function () {
    var now = new Date(), mo = now.getMonth();
    var MN = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    function money(v) { return (v < 0 ? '-' : '') + '$' + Math.round(Math.abs(v)).toLocaleString('en-US'); }
    function short(v, host) {
      host.textContent = '';
      var cur = el('span', 'cur', v < 0 ? '-$' : '$'); host.appendChild(cur);
      var a = Math.abs(v);
      host.appendChild(document.createTextNode(a >= 1000 ? (a / 1000).toFixed(1) : String(Math.round(a))));
      if (a >= 1000) host.appendChild(el('span', 'k', 'k'));
    }
    $('pxSub').textContent = '— ' + MN[mo] + ' · detailed view';
    $('pxLastLbl').textContent = mo > 0 ? 'Last month · ' + MN[mo - 1] : 'Last month';
    function setFill(sel, pct) { root.querySelectorAll(sel).forEach(function (f) { f.style.setProperty('--h', pct + '%'); }); }
    Promise.all([
      getJson(URLS.expenses + '?year=' + now.getFullYear()).catch(function () { return {}; }),
      getJson(URLS.estimate).catch(function () { return {}; })
    ]).then(function (r) {
      var res = r[0], est = r[1], d = res.data || {};
      var pc = d.payrollCost || [], sc = d.serviceCharge || [];
      // forecast = live estimate for the open cutoff period; once finalized, the locked figure for this month
      // Forecast shows the live estimate whenever one exists — zero and negative included.
      var fc = est.is_estimated ? (+est.net || 0) : (+pc[mo] || 0);
      var last = mo > 0 ? (+pc[mo - 1] || 0) : 0, svcPool = mo > 0 ? (+sc[mo - 1] || 0) : 0;
      var svc = TOTAL > 0 ? svcPool / TOTAL : 0, top = Math.max(fc, last);
      if (est.is_estimated || fc) { short(fc, root.querySelector('[data-pay="fc"]')); root.querySelector('[data-px="fc"]').textContent = money(fc); }
      if (last) { short(last, root.querySelector('[data-pay="last"]')); root.querySelector('[data-px="last"]').textContent = money(last); }
      if (svc) { short(svc, root.querySelector('[data-pay="svc"]')); root.querySelector('[data-px="svc"]').textContent = money(svc); root.querySelector('[data-px="pool"]').textContent = money(svcPool); }
      if (top) { setFill('.rc-c:nth-child(1) .rc-fill,.px-c:nth-child(1) .rc-fill', Math.max(0, Math.round(fc / top * 100))); setFill('.rc-c:nth-child(2) .rc-fill,.px-c:nth-child(2) .rc-fill', Math.round(last / top * 100)); }
      if (fc && last) { var dl = (fc - last) / last * 100; root.querySelector('[data-px="fcDelta"]').textContent = (dl >= 0 ? '▲ ' : '▼ ') + Math.abs(dl).toFixed(1) + '%'; }
    });

    var px = $('px');
    function open() { px.classList.add('open'); document.body.style.overflow = 'hidden'; }
    function shut() { px.classList.remove('open'); document.body.style.overflow = ''; }
    $('rcExp').addEventListener('click', open);
    $('pxClose').addEventListener('click', shut);
    px.addEventListener('click', function (e) { if (e.target === px) shut(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && px.classList.contains('open')) shut(); });
  })();

  /* payroll card height follows the bottom of WAI Insights above 1100px */
  (function () {
    var pay = $('payCard'), ref = $('waiIns');
    function align() {
      pay.style.height = '';
      if (window.innerWidth <= 1100) return;
      var h = ref.getBoundingClientRect().bottom - pay.getBoundingClientRect().top;
      if (h > 0) pay.style.height = h + 'px';
    }
    window.addEventListener('load', align);
    window.addEventListener('resize', align);
    requestAnimationFrame(align);
  })();

  /* ---------- To do: append Time & Attendance items to the server-rendered recruitment items ---------- */
  (function () {
    var list = $('tdList'), count = $('tdCount');
    function refresh() {
      var n = list.querySelectorAll('.td-item').length;
      count.textContent = n;
      if (!n && !list.querySelector('.empty')) list.appendChild(el('div', 'empty', 'Nothing to do right now.'));
    }
    refresh();
    function addItem(photo, name, title, sub, label, href, cls) {
      var item = el('div', 'td-item'), ico = el('span', 'td-ico'), bd = el('span', 'td-bd'), tt = el('span', 'td-tt', title), btn = el('a', 'td-btn' + (cls ? ' ' + cls : ''), label);
      fillAvatar(ico, photo, name); tt.appendChild(el('small', '', sub)); bd.appendChild(tt); btn.href = href;
      item.appendChild(ico); item.appendChild(bd); item.appendChild(btn);
      var empty = list.querySelector('.empty'); if (empty) empty.remove();
      list.appendChild(item);
    }
    // The master controller hands this page an empty recruitment list for HR users whose rank isn't literally 3;
    // the Talent Acquisition dashboard resolves the HR rank itself, so read the list it shows this same user.
    if (!list.querySelector('.td-item')) {
      fetch(URLS.taDash, { credentials: 'same-origin' }).then(function (r) { return r.ok ? r.text() : ''; }).then(function (t) {
        var doc = new DOMParser().parseFromString(t, 'text/html');
        doc.querySelectorAll('#todoList-main .todoList-block').forEach(function (b) {
          var p = b.querySelector('p'), a = b.querySelector('a'), img = b.querySelector('img');
          if (!p) return;
          var text = p.textContent.replace(/\s+/g, ' ').trim(), m, photo = img ? img.getAttribute('src') : '';
          var href = a && a.getAttribute('href') && a.getAttribute('href').indexOf('javascript') !== 0 ? a.href : URLS.taDash;
          if ((m = text.match(/^(.*?) approved the vacancy for (.*)$/))) {
            var q = a && /questionnaire/i.test(a.textContent);
            addItem(photo, m[1], m[2] + ' vacancy', q ? 'Questionnaire required to advertise' : m[1] + ' approved', q ? 'Add' : 'Create', href);
          } else if ((m = text.match(/^(.*?) is shortlisted for (.*)$/))) {
            addItem(photo, m[1], m[1], m[2] + ' applicant · shortlisted', 'Invite', href);
          } else {
            m = text.split(' - ');
            addItem(photo, m[0], m[0], (m[1] || text), 'Review', href);
          }
        });
        refresh();
      }).catch(refresh);
    }
    getJson(URLS.todo + '?length=8').then(function (res) {
      (res.data || []).slice(0, 8).forEach(function (r) {
        var type = r.action_type, btn = el('a', 'td-btn', 'Open'), sub;
        if (type === 'check_in') { btn.textContent = 'Check-in'; btn.className += ' green'; sub = 'Pending check-in · ' + (r.StartTime || '') + ' shift'; }
        else if (type === 'check_out') { btn.textContent = 'Check-out'; btn.className += ' red'; sub = 'Pending check-out · ' + (r.ExpectedEndTime || r.EndTime || ''); }
        else if (type === 'overtime_pending') { btn.textContent = 'Approve'; btn.className += ' amber'; sub = 'Overtime pending'; }
        else return;
        btn.href = URLS.todoPage;
        var item = el('div', 'td-item'), ico = el('span', 'td-ico'), bd = el('span', 'td-bd'), tt = el('span', 'td-tt', r.EmployeeName || '');
        var photo = r.profileImg ? (/^(https?:)?\/\//.test(r.profileImg) || r.profileImg[0] === '/' ? r.profileImg : '/' + r.profileImg) : '';
        fillAvatar(ico, photo, r.EmployeeName);
        tt.appendChild(el('small', '', sub)); bd.appendChild(tt);
        item.appendChild(ico); item.appendChild(bd); item.appendChild(btn);
        var empty = list.querySelector('.empty'); if (empty) empty.remove();
        list.appendChild(item);
      });
      refresh();
    }).catch(refresh);
  })();

  /* ---------- WAI ask → existing Wisdom chat endpoint ---------- */
  (function () {
    var focus = $('focus'), body = $('fpBody'), askInput = $('askInput'), fpInput = $('fpInput');
    // "Thinking" placeholder: glow dot + pulsing letters (pure CSS). Replaced by the answer via a.replaceChild(md, p).
    function thinking() {
      var w = el('div', 'wai-loader-wrapper'), l = el('div', 'wai-letter-wrapper');
      w.setAttribute('role', 'status'); w.setAttribute('aria-label', 'Thinking');
      w.appendChild(el('div', 'wai-loader'));
      'Thinking'.split('').forEach(function (c) { l.appendChild(el('span', 'wai-loader-letter', c)); });
      w.appendChild(l);
      return w;
    }
    // Daily allowance: server sends `usage: {limit, used, remaining}` on chat + history; nothing is shown without it.
    var askUse = $('askUse'), fpUse = $('fpUse'), askGo = $('askGo'), fpGo = $('fpGo'), usageOut = false;
    function applyUsage(u, fromEvent) {
      if (!u || !(u.limit > 0)) return;
      var rem = Math.max(0, u.remaining != null ? u.remaining : u.limit - (u.used || 0));
      var low = rem > 0 && rem <= Math.max(5, Math.ceil(u.limit * 0.2));
      var txt = rem === 0 ? 'Daily limit reached. Try again later.'
        : low ? 'Only ' + rem + ' message' + (rem === 1 ? '' : 's') + ' left today'
        : rem + ' of ' + u.limit + ' messages left today';
      usageOut = rem === 0;
      askUse.hidden = false; askUse.textContent = txt; askUse.className = 'wai-use' + (usageOut ? ' is-out' : low ? ' is-low' : '');
      // follow-up field: "N left" inside the field, full sentence as the tooltip
      fpUse.hidden = false; fpUse.className = 'wai-quota' + (usageOut ? ' is-out' : rem <= 10 ? ' is-low' : '');
      fpUse.setAttribute('data-tip', usageOut ? txt : rem + ' of ' + u.limit + ' messages left today'); WaiMd.roll($('fpUseN'), rem);
      [askInput, fpInput, askGo, fpGo].forEach(function (n) { n.disabled = usageOut; });
      if (!fromEvent) document.dispatchEvent(new CustomEvent('wai:usage', { detail: u })); // keep the chat panel in step
    }
    document.addEventListener('wai:usage', function (e) { applyUsage(e.detail, true); });
    fetch(URLS.waiHistory, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
      .then(function (r) { return r.json().catch(function () { return {}; }); })
      .then(function (j) { if (j && j.usage) applyUsage(j.usage); })
      .catch(function () {});
    function open() { focus.classList.add('open'); document.body.style.overflow = 'hidden'; setTimeout(function () { fpInput.focus(); }, 60); }
    function close() { focus.classList.remove('open'); document.body.style.overflow = ''; askInput.value = ''; }
    function ask(q) {
      if (usageOut) return;
      var qb = el('div', 'q', q); body.appendChild(qb); WaiMd.tideIn(qb);
      var a = el('div', 'a'); a.appendChild(el('div', 'a-tag', 'WAI')); var p = thinking(); a.appendChild(p); body.appendChild(a); WaiMd.tideIn(a, 120);
      body.scrollTop = body.scrollHeight;
      fetch(URLS.wai, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin', body: JSON.stringify({ message: q }) })
        .then(function (r) { return r.json().catch(function () { return {}; }); })
        .then(function (j) { if (j && j.usage) applyUsage(j.usage); var md = el('div', 'a-md'); WaiMd.render(md, j.success ? j.reply : (j.message || 'Something went wrong. Please try again.'), { thread: body, reveal: true }); a.replaceChild(md, p); body.scrollTop = body.scrollHeight; })
        .catch(function () { if (p.parentNode) a.replaceChild(el('p', '', 'Something went wrong. Please try again.'), p); });
    }
    function submitAsk() { var v = askInput.value.trim(); if (!v) return; body.textContent = ''; body._waiSubj = null; ask(v); open(); }
    function submitFp() { var v = fpInput.value.trim(); if (!v) return; ask(v); fpInput.value = ''; }
    $('askGo').addEventListener('click', submitAsk);
    askInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') submitAsk(); });
    root.querySelectorAll('.hm-askchip').forEach(function (c) {
      function fill() { askInput.value = c.textContent.trim(); askInput.focus(); }
      c.addEventListener('click', fill);
      c.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); fill(); } });
    });
    $('fpGo').addEventListener('click', submitFp);
    fpInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') submitFp(); });
    $('fpClose').addEventListener('click', close);
    focus.addEventListener('click', function (e) { if (e.target === focus) close(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && focus.classList.contains('open')) close(); });

    var g = $('waiGlass');
    g.addEventListener('pointermove', function (e) { var r = g.getBoundingClientRect(); g.style.setProperty('--mx', ((e.clientX - r.left) / r.width * 100) + '%'); g.style.setProperty('--my', ((e.clientY - r.top) / r.height * 100) + '%'); });
    g.addEventListener('pointerleave', function () { g.style.setProperty('--mx', '75%'); g.style.setProperty('--my', '-10%'); });
  })();

  /* ---------- WAI Insights spotlight (dismiss is client-side only; reappears on reload) ---------- */
  var addInsight;
  (function () {
    var SEVW = { crit: 'Critical', high: 'High', watch: 'Watch' }, ORDER = { crit: 0, high: 1, watch: 2 };
    var data = INSIGHTS.slice(), ID = 0;
    data.forEach(function (d) { d.id = ++ID; });
    function sortData() { data.sort(function (a, b) { return ORDER[a.sev] - ORDER[b.sev]; }); }
    sortData();
    var lens = 'all', idx = 0, auto = !window.matchMedia('(prefers-reduced-motion:reduce)').matches, timer = null;
    var lensEl = $('wiLens'), prog = $('wiProg'), ct = $('wiCt'), totalN = $('wiTotal'), scard = $('wiScard'), playBtn = $('wiPlay');
    function filtered() { return lens === 'all' ? data : data.filter(function (d) { return d.sev === lens; }); }
    function cnt(s) { return s === 'all' ? data.length : data.filter(function (d) { return d.sev === s; }).length; }
    function renderLens() {
      totalN.textContent = data.length;
      lensEl.textContent = '';
      [['all', 'All'], ['crit', 'Critical'], ['high', 'High'], ['watch', 'Watch']].forEach(function (d) {
        var c = el('span', 'wi-lchip' + (lens === d[0] ? ' on' : ''), d[1] + ' ');
        c.appendChild(el('span', 'cnt', cnt(d[0])));
        c.onclick = function () { lens = d[0]; idx = 0; renderLens(); renderCard(); startAuto(); };
        lensEl.appendChild(c);
      });
    }
    function renderCard() {
      var list = filtered();
      scard.textContent = '';
      if (!list.length) {
        scard.appendChild(el('div', 'x', 'All clear ✨ — nothing needs you right now.')).style.color = 'var(--muted)';
        ct.textContent = '0 / 0'; prog.style.width = '0%'; return;
      }
      if (idx >= list.length) idx = 0;
      var d = list[idx];
      var tag = el('div', 'tag'); tag.appendChild(el('span', 'sv sev-' + (d.sev === 'crit' ? 'crit' : d.sev === 'high' ? 'high' : 'watch'), SEVW[d.sev])); tag.appendChild(document.createTextNode(' · ' + d.module));
      var x = el('div', 'x'); x.innerHTML = d.html; // server-built from integers + fixed strings only
      var a = el('div', 'a'), view = el('a', '', 'View details →'), dis = el('a', 'mut', 'Dismiss');
      view.href = d.url; view.style.cursor = 'pointer';
      dis.onclick = function () { dismiss(d.id); };
      a.appendChild(view); a.appendChild(dis);
      scard.appendChild(tag); scard.appendChild(x); scard.appendChild(a);
      ct.textContent = (idx + 1) + ' / ' + list.length;
      prog.style.width = ((idx + 1) / list.length * 100) + '%';
    }
    function step(n) { var l = filtered(); if (!l.length) return; idx = (idx + n + l.length) % l.length; renderCard(); }
    function dismiss(id) { var k = data.findIndex(function (x) { return x.id === id; }); if (k > -1) data.splice(k, 1); renderLens(); renderCard(); }
    function stopAuto() { if (timer) { clearInterval(timer); timer = null; } }
    function startAuto() { stopAuto(); if (auto) timer = setInterval(function () { step(1); }, 6000); }
    $('wiPrev').onclick = function () { step(-1); startAuto(); };
    $('wiNext').onclick = function () { step(1); startAuto(); };
    function setPlay() { playBtn.textContent = auto ? '⏸' : '▶'; playBtn.setAttribute('aria-label', auto ? 'Pause auto-rotate' : 'Start auto-rotate'); }
    playBtn.onclick = function () { auto = !auto; setPlay(); auto ? startAuto() : stopAuto(); };
    var box = $('waiIns');
    box.addEventListener('mouseenter', stopAuto);
    box.addEventListener('mouseleave', function () { if (auto) startAuto(); });
    addInsight = function (d) { d.id = ++ID; data.push(d); sortData(); renderLens(); renderCard(); };
    setPlay(); renderLens(); renderCard(); startAuto();
  })();

  /* ---------- Attendance trend: last 12 months from the existing Time & Attendance year endpoint ---------- */
  (function () {
    var chart = $('attChart'), xrow = $('attX'), tip = $('attTip');
    var now = new Date(), y = now.getFullYear(), m = now.getMonth();
    var MS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    var MN = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    var today = now.toISOString().slice(0, 10);
    function fetchYear(yr) { return getJson(URLS.attendance.replace('__y__', yr).replace('__d__', today)).then(function (r) { return (r.datasets && r.datasets[0] && r.datasets[0].data) || []; }).catch(function () { return []; }); }
    Promise.all([fetchYear(y - 1), fetchYear(y)]).then(function (yrs) {
      var months = [];
      for (var i = 11; i >= 0; i--) {
        var mm = m - i, yy = y;
        if (mm < 0) { mm += 12; yy -= 1; }
        var arr = yrs[yy === y ? 1 : 0];
        months.push({ m: MS[mm], mo: MN[mm] + ' ' + yy, rate: Math.min(100, Math.max(0, +arr[mm] || 0)) });
      }
      render(months);
    });
    function render(data) {
      var def = data.length - 1, cols = [];
      data.forEach(function (d, i) {
        var col = el('div', 'att-col' + (i === def ? ' active' : ''));
        col.appendChild(el('div', 'att-track'));
        var bar = el('div', 'att-bar'); bar.style.setProperty('--h', d.rate.toFixed(1) + '%'); col.appendChild(bar);
        chart.appendChild(col); cols.push(col);
        xrow.appendChild(el('span', i === def ? 'on' : '', d.m));
      });
      var xs = [].slice.call(xrow.children);
      function setActive(i) { cols.forEach(function (c, j) { c.classList.toggle('active', j === i); }); xs.forEach(function (s, j) { s.classList.toggle('on', j === i); }); }
      function row(color, label, val) {
        var r = el('div', 'tr'), lb = el('span', 'lb'), sw = el('i'); sw.style.background = color;
        lb.appendChild(sw); lb.appendChild(document.createTextNode(label));
        r.appendChild(lb); r.appendChild(el('b', '', val)); return r;
      }
      function showTip(i) {
        var d = data[i];
        tip.textContent = '';
        var tm = el('div', 'tm'); tm.appendChild(el('b', '', d.mo)); tip.appendChild(tm);
        if (d.rate > 0) { tip.appendChild(row('#014653', 'Present', d.rate.toFixed(1) + '%')); tip.appendChild(row('#E0673F', 'Not present', (100 - d.rate).toFixed(1) + '%')); }
        else tip.appendChild(el('div', 'tr', 'No attendance recorded'));
        var cr = chart.getBoundingClientRect(), br = cols[i].getBoundingClientRect(), left = br.left - cr.left + br.width / 2;
        tip.style.opacity = '0'; tip.style.left = left + 'px'; tip.style.top = (cr.height * (1 - d.rate / 100) - 10) + 'px';
        requestAnimationFrame(function () {
          var tw = tip.offsetWidth; left = Math.max(tw / 2 + 2, Math.min(cr.width - tw / 2 - 2, left));
          tip.style.left = left + 'px'; tip.style.transform = 'translate(-50%,-100%)'; tip.style.opacity = '1';
        });
      }
      cols.forEach(function (c, i) { c.addEventListener('pointerenter', function () { setActive(i); showTip(i); }); });
      chart.addEventListener('pointerleave', function () { tip.style.opacity = '0'; setActive(def); });
    }
  })();

  /* ---------- Compliance: existing compliance list endpoint, grouped by severity ---------- */
  (function () {
    var parser = new DOMParser();
    function html(s) { return parser.parseFromString(s || '', 'text/html'); }
    function txt(doc, sel) { var n = doc.querySelector(sel); return n ? n.textContent.trim() : ''; }
    var groups = { crit: [], high: [], med: [] };
    root.querySelectorAll('.cmp-gh[data-grp]').forEach(function (h) {
      h.addEventListener('click', function () {
        var g = $(h.dataset.grp); g.classList.toggle('collapsed');
        h.setAttribute('aria-expanded', g.classList.contains('collapsed') ? 'false' : 'true');
      });
    });
    var cf = $('cmpFix'), cfBody = $('cfBody');
    // AI text is stored HTML-encoded (e.g. &#039;); decode to plain text (textContent below keeps it safe)
    function plain(t) { return new DOMParser().parseFromString(t || '', 'text/html').documentElement.textContent; }
    function closeFix() { cf.classList.remove('open'); document.body.style.overflow = ''; }
    function openFix(it) {
      $('cfSub').textContent = '— ' + it.rule;
      cfBody.textContent = '';
      var a = el('div', 'a'), list = el('div', 'a-list'), r = el('div', 'a-row'), av = el('div', 'av'), ab = el('div', 'ab');
      a.appendChild(el('div', 'a-tag', 'Compliance' + (it.module ? ' · ' + it.module : '')));
      fillAvatar(av, it.photo, it.name); av.style.background = 'var(--teal)';
      ab.appendChild(el('div', 'at', it.name || 'Employee')); ab.appendChild(el('div', 'ac', [it.role, it.rule].filter(Boolean).join(' · ')));
      r.appendChild(av); r.appendChild(ab); r.appendChild(el('span', 'atag', { crit: 'Critical', high: 'High', med: 'Medium' }[it.sev])); list.appendChild(r);
      a.appendChild(list);
      if (it.desc) { var d = el('p', '', it.desc); d.style.marginTop = '14px'; a.appendChild(d); }
      a.appendChild(el('div', 'a-tag', 'Suggested fix')).style.marginTop = '16px';
      a.appendChild(el('p', '', it.fix || 'No suggestion has been generated for this breach yet. Open Compliance to regenerate it.'));
      var go = el('a', '', 'Open in Compliance →'); go.href = URLS.compliancePage; go.style.cssText = 'display:inline-block;margin-top:14px;font-size:12px;font-weight:600;color:var(--teal);text-decoration:none';
      a.appendChild(go);
      cfBody.appendChild(a);
      cf.classList.add('open'); document.body.style.overflow = 'hidden';
    }
    $('cfClose').addEventListener('click', closeFix);
    cf.addEventListener('click', function (e) { if (e.target === cf) closeFix(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && cf.classList.contains('open')) closeFix(); });
    getJson(URLS.compliance + '?length=200').then(function (res) {
      (res.data || []).forEach(function (r) {
        var sev = (r.severity_ai || '').toLowerCase(), key = sev === 'critical' ? 'crit' : sev === 'high' ? 'high' : 'med';
        var emp = html(r.employee_name), av = emp.querySelector('.cc-emp-avatar img');
        var date = txt(html(r.reported_on), 'div').split(' ').slice(0, 2).join(' ');
        var role = txt(emp, '.cc-emp-role');
        groups[key].push({
          rule: txt(html(r.compliance_breached_name), '.cc-rule-name') || r.compliance_breached_name || 'Compliance breach',
          name: txt(emp, '.cc-emp-name'), photo: av ? av.getAttribute('src') : '',
          sub: [txt(emp, '.cc-emp-name'), role, r.module_name].filter(Boolean).join(' · '), date: date,
          role: role, module: r.module_name || '', sev: key,
          desc: plain(r.description_ai || '').replace(/§\s?(\d)/g, 'Section $1') || txt(html(r.description), '.cc-desc-text') || '',
          fix: plain(r.remediation_ai)
        });
      });
      var open = 0;
      Object.keys(groups).forEach(function (k) {
        var list = groups[k], g = $('cmp-' + k), bodyEl = g.querySelector('.cmp-gbody');
        open += list.length;
        g.querySelector('.gc').textContent = '· ' + list.length;
        if (!list.length) { bodyEl.appendChild(el('div', 'empty', 'No ' + (k === 'crit' ? 'critical' : k === 'high' ? 'high' : 'medium') + ' breaches.')); return; }
        list.slice(0, 5).forEach(function (it) {
          var item = el('div', 'cmp-item'), a = el('span', 'cmp-av'), main = el('div', 'cmp-main');
          fillAvatar(a, it.photo, it.name);
          main.appendChild(el('div', 'cmp-rule', it.rule)); main.appendChild(el('div', 'cmp-sub', it.sub));
          var fix = el('button', 'cmp-fix', 'Suggestion'); fix.type = 'button'; fix.onclick = function () { openFix(it); };
          item.appendChild(a); item.appendChild(main); item.appendChild(el('span', 'cmp-rep', it.date)); item.appendChild(fix);
          bodyEl.appendChild(item);
        });
      });
      $('cmpOpen').textContent = open + ' open';
      if (groups.crit.length) addInsight({ module: 'Compliance', sev: 'crit', title: 'Critical breaches', html: '<b>' + plural(groups.crit.length, 'critical compliance breach', 'critical compliance breaches') + '</b> open.', url: URLS.compliancePage });
    }).catch(function () {
      $('cmpOpen').textContent = 'Unavailable';
      $('cmp-crit').querySelector('.cmp-gbody').appendChild(el('div', 'empty', 'Compliance could not be loaded.'));
    });
  })();

  /* ---------- Calendar + upcoming events ---------- */
  (function () {
    var mg = $('mg'), moEl = $('calMo'), up = $('upList');
    var MN = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    var MS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    var now = new Date(), cy = now.getFullYear(), cm = now.getMonth();
    function key(y, m, d) { return y + '-' + String(m + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0'); }
    var evDays = {}; EVENTS.forEach(function (e) { evDays[e.date] = 1; });
    function grid() {
      moEl.textContent = MN[cm] + ' ' + cy;
      var first = new Date(cy, cm, 1).getDay(), dim = new Date(cy, cm + 1, 0).getDate(), prev = new Date(cy, cm, 0).getDate(), tk = key(now.getFullYear(), now.getMonth(), now.getDate());
      mg.textContent = '';
      for (var i = 0; i < first; i++) mg.appendChild(el('div', 'dcell mut', prev - first + 1 + i));
      for (var d = 1; d <= dim; d++) {
        var k = key(cy, cm, d), cls = 'dcell' + (k === tk ? ' today' : evDays[k] ? ' ev' : '');
        var c = el('div', cls, d); if (k === tk || evDays[k]) c.appendChild(el('span', 'evd')); mg.appendChild(c);
      }
      var trail = (7 - (first + dim) % 7) % 7;
      for (var j = 1; j <= trail; j++) mg.appendChild(el('div', 'dcell mut', j));
    }
    $('calPrev').onclick = function () { cm--; if (cm < 0) { cm = 11; cy--; } grid(); };
    $('calNext').onclick = function () { cm++; if (cm > 11) { cm = 0; cy++; } grid(); };
    grid();

    var t0 = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    var upcoming = EVENTS.filter(function (e) { return new Date(e.date + 'T00:00:00') >= t0; });
    if (!upcoming.length) up.appendChild(el('div', 'empty', 'No upcoming events.'));
    upcoming.forEach(function (e) {
      var d = new Date(e.date + 'T00:00:00'), days = Math.round((d - t0) / 864e5);
      var ue = el('div', 'ue'), chip = el('div', 'dchip'), ub = el('div', 'ub'), right = el('div', 'ue-right');
      chip.appendChild(el('div', 'dd tnum', String(d.getDate()).padStart(2, '0'))); chip.appendChild(el('div', 'dm', MS[d.getMonth()]));
      ub.appendChild(el('div', 'ut', e.title)); ub.appendChild(el('div', 'us', e.sub));
      var people = e.people || [];
      if (people.length === 1) { var av = el('span', 'av'); fillAvatar(av, people[0].photo, people[0].name); right.appendChild(av); }
      else if (people.length > 1) { var st = el('span', 'stack'); people.slice(0, 2).forEach(function (p) { var a = el('span', 'av'); fillAvatar(a, p.photo, p.name); st.appendChild(a); }); if (people.length > 2) st.appendChild(el('span', 'more', '+' + (people.length - 2))); right.appendChild(st); }
      right.appendChild(el('span', 'uw' + (days === 0 ? ' today' : days <= 3 ? ' soon' : ''), days === 0 ? 'Today' : days === 1 ? 'Tomorrow' : days <= 3 ? days + ' days' : d.getDate() + ' ' + MS[d.getMonth()]));
      ue.appendChild(chip); ue.appendChild(ub); ue.appendChild(right); up.appendChild(ue);
    });
  })();

  /* ---------- Duty roster timeline (same backend as the Leader dashboard: LeaderDashboard::roster()) ---------- */
  (function () {
    var body = $('tlBody'), pos = $('tlPos'), date = $('tlDate'), now = $('tlNow'), lbl = $('tlNowLbl'), cnt = $('tlCount'), leg = $('tlLeg');
    if (!body) return;
    var rows = [], serverOffset = 0;
    function hm(s) { var p = String(s || '').split(':'); return (+p[0] || 0) + (+p[1] || 0) / 60; }
    function pad(n) { return String(n).padStart(2, '0'); }
    function nowHours() { var d = new Date(); return ((d.getHours() + d.getMinutes() / 60 + serverOffset) % 24 + 24) % 24; }
    // Server clock (app timezone) minus browser clock, so the Now line follows the property's time, not the viewer's.
    serverOffset = hm(NOW_HM) - (new Date().getHours() + new Date().getMinutes() / 60);
    function tick() {
      var isToday = date.value === TODAY, h = nowHours();
      now.style.display = isToday ? '' : 'none';
      now.style.setProperty('--t', h);
      lbl.textContent = 'Now ' + pad(Math.floor(h)) + ':' + pad(Math.floor(h % 1 * 60));
    }
    function legend(list) {
      leg.textContent = '';
      var seen = {};
      function item(sw, text, st) { var s = el('span'), i = el('i'); if (st) i.setAttribute('style', st); else i.style.background = sw; s.appendChild(i); s.appendChild(document.createTextNode(text)); leg.appendChild(s); }
      list.forEach(function (r) {
        if (r.state === 'lv' || r.state === 'miss' || seen[r.shift]) return; seen[r.shift] = 1;
        item(r.tone === 'am' ? 'var(--teal)' : '#CFE0E3', r.shift + ' · ' + pad(Math.floor(r.start)) + ':' + pad(Math.round(r.start % 1 * 60)) + '–' + pad(Math.floor(r.end)) + ':' + pad(Math.round(r.end % 1 * 60)));
      });
      item('', 'No check-in', 'background:#fff;border:1px dashed rgba(229,87,63,.6)');
      item('', 'On leave', 'background:rgba(107,95,199,.16)');
      item('var(--lime,#E0FF02)', 'Now');
    }
    function render() {
      var f = pos.value, list = rows.filter(function (r) { return f === 'all' || r.position === f; });
      cnt.textContent = list.length + ' rostered';
      body.textContent = '';
      if (!list.length) { body.appendChild(el('div', 'tl-empty', URLS.roster ? 'No one rostered for this position on this date.' : 'The duty roster is not connected to this dashboard yet.')); legend([]); return; }
      list.forEach(function (r) {
        var row = el('div', 'tl-row'), who = el('div', 'tl-who'), av = el('span', 'cmp-av'), main = el('div', 'rs-main'), track = el('div', 'tl-track');
        var s = Math.max(0, Math.min(24, +r.start || 0)), e = Math.max(0, Math.min(24, +r.end || 24)); if (e <= s) e = 24;
        var cls = r.state === 'miss' ? 'miss' : r.state === 'lv' ? 'lv' : r.tone, txt = r.state === 'miss' ? 'No check-in' : r.state === 'lv' ? 'On leave' : r.shift;
        fillAvatar(av, r.photo, r.name);
        main.appendChild(el('div', 'rs-nm', r.name)); main.appendChild(el('div', 'rs-ps', r.position));
        who.appendChild(av); who.appendChild(main);
        var bar = el('div', 'tl-bar ' + cls, txt); bar.style.setProperty('--s', s); bar.style.setProperty('--e', e);
        if (r.tip) bar.setAttribute('data-tip', r.tip);
        track.appendChild(bar); row.appendChild(who); row.appendChild(track); body.appendChild(row);
      });
      legend(list);
    }
    function load() {
      if (!URLS.roster) { rows = []; render(); return; }
      body.textContent = ''; body.appendChild(el('div', 'tl-empty', 'Loading…'));
      getJson(URLS.roster + '?date=' + encodeURIComponent(date.value)).then(function (res) {
        rows = (res.rows || []).map(function (r) { r.start = +r.start; r.end = +r.end; r.tone = r.tone || (r.start < 12 ? 'am' : 'pm'); return r; });
        var cur = pos.value, seen = {}; pos.textContent = ''; pos.appendChild(new Option('All positions', 'all'));
        rows.forEach(function (r) { if (!seen[r.position]) { seen[r.position] = 1; pos.appendChild(new Option(r.position, r.position)); } });
        pos.value = seen[cur] ? cur : 'all';
        render(); tick();
      }).catch(function () { rows = []; body.textContent = ''; body.appendChild(el('div', 'tl-empty', 'Could not load the roster. Please try again.')); });
    }
    pos.addEventListener('change', render);
    date.addEventListener('change', function () { load(); tick(); });
    load(); tick(); setInterval(tick, 60000);
  })();

  /* ---------- Action inbox (resignations, leave/overtime requests, exit clearance) ---------- */
  (function () {
    var list = $('aiList'); if (!list) return;
    var ITEMS = (INBOX || []).slice(), filt = 'all';
    var SVG = {
      ok: 'M20 6L9 17l-5-5', no: 'M18 6L6 18M6 6l12 12', chev: 'M9 18l6-6-6-6', pen: 'M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z',
      eye: 'M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z'
    };
    function icon(k, sw) {
      var s = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
      s.setAttribute('viewBox', '0 0 24 24'); s.setAttribute('fill', 'none'); s.setAttribute('stroke', 'currentColor'); s.setAttribute('stroke-width', sw || '2.6'); s.setAttribute('stroke-linecap', 'round'); s.setAttribute('stroke-linejoin', 'round'); s.setAttribute('aria-hidden', 'true');
      var p = document.createElementNS('http://www.w3.org/2000/svg', 'path'); p.setAttribute('d', SVG[k]); s.appendChild(p);
      if (k === 'eye') { var c = document.createElementNS('http://www.w3.org/2000/svg', 'circle'); c.setAttribute('cx', '12'); c.setAttribute('cy', '12'); c.setAttribute('r', '3'); s.appendChild(c); }
      return s;
    }
    var TL = { res: 'Resignation', req: 'Request', exit: 'Exit clearance' };
    function btn(cls, label, k, attrs) {
      var b = el(attrs.href ? 'a' : 'button', cls); if (!attrs.href) b.type = 'button';
      Object.keys(attrs).forEach(function (a) { b.setAttribute(a, attrs[a]); });
      b.appendChild(icon(k, '2.6'));
      return b;
    }
    function row(x) {
      var r = el('div', 'ai-row'), t = el('span'), pp = el('div', 'ai-pp'), av = el('span', 'ai-av'), who = el('div'), md = el('div', 'ai-md'), ac = el('div', 'ai-ac');
      t.appendChild(el('span', 'ai-tag ' + x.t, TL[x.t]));
      fillAvatar(av, x.photo, x.n); who.style.minWidth = '0';
      who.appendChild(el('div', 'ai-nm', x.n)); who.appendChild(el('div', 'ai-rl', x.r));
      pp.appendChild(av); pp.appendChild(who);
      md.appendChild(document.createTextNode(x.d));
      var mt = el('div', 'ai-mt', x.m || '');
      if (x.dl) { if (x.m) mt.appendChild(document.createTextNode(' · ')); mt.appendChild(el('span', x.od ? 'ai-od' : '', x.dl)); }
      md.appendChild(mt);
      if (x.t === 'req') {
        if (x.view) ac.appendChild(btn('ai-icb vw', '', 'eye', { href: x.view, title: 'View', 'aria-label': 'View request' }));
        if (x.ok) ac.appendChild(btn('ai-icb ok', '', 'ok', { title: 'Approve', 'aria-label': 'Approve', 'data-do': 'ok', 'data-i': ITEMS.indexOf(x) }));
        if (x.no) ac.appendChild(btn('ai-icb no', '', 'no', { title: 'Reject', 'aria-label': 'Reject', 'data-do': 'no', 'data-i': ITEMS.indexOf(x) }));
      } else {
        var a = el('a', 'ai-act'); a.href = x.url;
        if (x.t === 'res') { a.appendChild(document.createTextNode('Review & approve')); a.appendChild(icon('chev', '2.4')); }
        else { a.appendChild(icon('pen', '2')); a.appendChild(document.createTextNode('Fill form')); }
        ac.appendChild(a);
      }
      r.appendChild(t); r.appendChild(pp); r.appendChild(md); r.appendChild(ac);
      return r;
    }
    function counts() {
      var c = { all: ITEMS.length, res: 0, req: 0, exit: 0 }; ITEMS.forEach(function (x) { c[x.t]++; });
      Array.prototype.forEach.call(document.querySelectorAll('#hrmd [data-c]'), function (b) { b.textContent = c[b.getAttribute('data-c')]; });
      $('aiCnt').textContent = c.all;
    }
    function renderInbox() {
      list.textContent = '';
      var rows = ITEMS.filter(function (x) { return filt === 'all' || x.t === filt; });
      if (!rows.length) {
        var e = el('div', 'ai-empty'), ei = el('span', 'ei'); ei.appendChild(icon('ok')); e.appendChild(ei); e.appendChild(el('b', '', 'All caught up')); e.appendChild(document.createTextNode('Nothing waiting on you here.'));
        list.appendChild(e); return;
      }
      rows.forEach(function (x) { list.appendChild(row(x)); });
    }
    $('aiFilters').addEventListener('click', function (e) {
      var b = e.target.closest('.ai-fc'); if (!b) return; filt = b.getAttribute('data-f');
      Array.prototype.forEach.call(document.querySelectorAll('#hrmd .ai-fc'), function (x) { x.classList.toggle('on', x === b); });
      renderInbox();
    });
    list.addEventListener('click', function (e) {
      var b = e.target.closest('[data-do]'); if (!b || b.disabled) return;
      var x = ITEMS[+b.getAttribute('data-i')], d = b.getAttribute('data-do'), ok = d === 'ok', rowEl = b.closest('.ai-row');
      b.disabled = true;
      postJson(ok ? x.ok : x.no, { id: x.id }).then(function (j) {
        if (!j.__ok || j.success === false) { b.disabled = false; toast('err', 'Couldn’t ' + (ok ? 'approve' : 'reject') + ' request', j.message || 'Nothing was changed. Please try again.'); return; }
        rowEl.classList.add('gone');
        setTimeout(function () {
          var k = ITEMS.indexOf(x); if (k > -1) ITEMS.splice(k, 1); counts(); renderInbox();
          toast(ok ? 'ok' : 'info', ok ? 'Request approved' : 'Request rejected', x.n + '’s request ' + (x.r || '').replace('Request ', '') + ' has been ' + (ok ? 'approved.' : 'rejected.'));
        }, 280);
      }).catch(function () { b.disabled = false; toast('err', 'Couldn’t ' + (ok ? 'approve' : 'reject') + ' request', 'Nothing was changed. Please try again.'); });
    });
    renderInbox(); counts();
  })();
})();
</script>
@endsection
