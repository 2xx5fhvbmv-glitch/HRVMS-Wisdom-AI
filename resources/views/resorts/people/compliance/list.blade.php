@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif

    @section('content')
    @php
        // Existing routes only: the screen where a breach of each module is usually fixed. A module with no obvious page has no link.
        $ccPages = collect([
            'Time & Attendance'  => ['resort.timeandattendance.dashboard', 'Time & Attendance'],
            'Overtime Agreement' => ['resort.timeandattendance.OverTime', 'Overtime'],
            'Leave'              => ['leave.request', 'Leave requests'],
            'Payroll Compliance' => ['payroll.dashboard', 'Payroll'],
            'Pension Compliance' => ['payroll.dashboard', 'Payroll'],
            'Visa'               => ['resort.visa.DocumentManage', 'Visa documents'],
            'Workforce Planning' => ['resort.workforceplan.dashboard', 'Workforce Planning'],
            'People'             => ['people.info-update.index', 'People › Employee Info Update'],
            'People Management'  => ['people.info-update.index', 'People › Employee Info Update'],
        ])->filter(fn ($v) => \Illuminate\Support\Facades\Route::has($v[0]))->map(fn ($v) => ['url' => route($v[0]), 'label' => $v[1]]);
        $ccCanDismiss = App\Helpers\Common::canManageEmployeeRecords();
    @endphp
    <style>
        /* Same requested push as the other module dashboards/pages — extra breathing room between the hero and the
           KPI row below it, scoped to this page. padding-bottom, not margin (adjacent sibling margins collapse).
           Below Bootstrap's sm breakpoint the extra padding pushes the KPI row into the teal hero curve — neutralized below 576px. */
        #compliance-hero { padding-bottom: 40px; position: relative; z-index: 5; } /* above the KPI cards so the split-button menu isn't covered */
        @media (max-width: 575.98px) {
            #compliance-hero { padding-bottom: 0; }
        }
    </style>
    <div class="body-wrapper pb-5">
        <div class="container-fluid compliance-page">
               <div class="page-hedding" id="compliance-hero">
                    <div class="row  g-3">
                         <div class="col-auto">
                         <div class="page-title">
                              <span>People</span>
                              <h1>{{ $page_title }}</h1>
                         </div>
                         </div>
                         <div class="col-auto ms-auto d-flex gap-2 flex-wrap @if(App\Helpers\Common::checkRouteWisePermission('people.compliance.index',config('settings.resort_permissions.create')) == false) d-none @endif">
                              {{-- One split button. Main = rule check (free). Caret = the two AI actions (use AI credits); they keep their old ids and confirm dialog. --}}
                              <div class="cc-split" id="cc-split">
                                   <button type="button" class="wai-btn wai-btn-primary cc-run" id="cc-run" data-url="{{ route('people.compliance.run') }}"><svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M20 12a8 8 0 1 1-2.3-5.7M20 4v5h-5"/></svg><span id="cc-run-l">Run compliance check</span><i class="cc-pg"></i></button>
                                   <button type="button" class="wai-btn wai-btn-primary cc-caret" id="cc-caret" aria-label="More checks" aria-haspopup="true" aria-expanded="false"><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></button>
                                   <div class="cc-dd" id="cc-dd" role="menu">
                                        <div class="cc-dk">Rule check — free, runs on your rules</div>
                                        <button type="button" role="menuitem" data-act="run"><span class="cc-di"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M20 12a8 8 0 1 1-2.3-5.7M20 4v5h-5"/></svg></span><span><b>Run compliance check</b><small>Checks every employee against all rules</small></span></button>
                                        <div class="cc-dk">AI — uses AI credits</div>
                                        <button type="button" role="menuitem" id="ai-anomaly-scan-btn" title="Run an AI-only anomaly scan that catches issues the rule-based engine can't (salary outliers, ratio drifts, etc.)"><span class="cc-di ai"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="11" cy="11" r="6"/><path d="m20 20-4-4M11 8v6M8 11h6"/></svg></span><span><b>Run AI anomaly scan</b><small>Looks for unusual patterns the rules don’t cover</small></span><em>AI</em></button>
                                        <button type="button" role="menuitem" id="ai-regenerate-btn" title="Re-run the AI explanation and remediation on the next batch of 15 breached compliance rows. Picks never-enriched rows first, then the oldest enrichments. Click again for the next batch."><span class="cc-di ai"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M12 3l1.8 4.6L18 9l-4.2 1.4L12 15l-1.8-4.6L6 9l4.2-1.4z"/><path d="M19 15l.8 2 2 .8-2 .8-.8 2-.8-2-2-.8 2-.8z"/></svg></span><span><b>Regenerate AI suggestions</b><small>Rewrites the suggested fix for the next 15 open issues</small></span><em>AI</em></button>
                                   </div>
                              </div>
                         </div>
                    </div>
               </div>

            {{-- Four separate cards, label + number only. Same 4 real counts ComplianceController::index() computes. --}}
            <div class="cc-kpis" id="cc-kpis">
                <div class="cc-kpi cc-kpi--alarm"><span class="cc-kl">Critical breaches</span><div class="cc-kn tnum">{{ $complianceSummary['critical'] }}</div></div>
                <div class="cc-kpi"><span class="cc-kl">Employees affected</span><div class="cc-kn tnum">{{ $complianceSummary['employees'] }}</div></div>
                <div class="cc-kpi"><span class="cc-kl">Rules breached</span><div class="cc-kn tnum">{{ $complianceSummary['rules'] }}</div></div>
                <div class="cc-kpi"><span class="cc-kl">Modules involved</span><div class="cc-kn tnum">{{ $complianceSummary['modules'] }}</div></div>
            </div>

            <div>
                <div class="card cc-table-card">
                    <div class="card-header cc-controls">
                        <div class="cc-searchrow">
                            <label class="cc-search">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                                <input type="search" class="search" placeholder="Search employee or issue" aria-label="Search" autocomplete="off" />
                            </label>

                            <div class="ctl" id="cc-cat-ctl">
                                <button type="button" class="trigger" id="cc-cat-btn" aria-haspopup="true" aria-expanded="false">
                                    <svg class="funnel" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
                                    <span id="cc-cat-l">Category</span>
                                    <svg class="chev" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                                </button>
                                <div class="menu" id="cc-cat-menu"></div>
                            </div>

                            <span class="cc-sp"></span>

                            <span class="cc-seg" id="cc-grp"><button type="button" data-g="list" class="on">All issues</button><button type="button" data-g="rule">By rule</button></span>

                            <a id="compliance-download-btn" href="{{route('people.compliance.download')}}" class="cc-dl" title="Download as Excel" aria-label="Download as Excel">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 4v11M7 10l5 5 5-5M5 20h14"/></svg>
                            </a>
                        </div>
                    </div>
                    <div class="cc-table-scroll">
                    <table id="table-exitclearance-form" class="table table-exitclearance-form w-100">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Issue</th>
                                <th class="c-mod">Module</th>
                                <th class="c-age">Reported</th>
                                <th>Status</th>
                                <th class="d-none"></th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Side panel: opens from a row. --}}
    <div class="cc-ov" id="cc-ov"></div>
    <aside class="cc-pn" id="cc-pn" role="dialog" aria-label="Issue details" aria-hidden="true">
        <button class="cc-x" id="cc-px" type="button" aria-label="Close"><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
        <div class="cc-pb" id="cc-pb"></div>
        <div class="cc-pf">
            @if($ccCanDismiss)
            <select id="cc-dis" aria-label="Dismiss"><option value="">Dismiss…</option><option>Not a real breach</option><option>Approved exception</option><option>Already fixed outside the system</option></select>
            @endif
            <span class="cc-sp"></span>
            <span class="cc-nav"><button type="button" data-nv="-1" aria-label="Previous issue"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="m18 15-6-6-6 6"/></svg></button><button type="button" data-nv="1" aria-label="Next issue"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></button></span>
        </div>
    </aside>

    {{-- Shared confirm modal for the two AI action buttons. The JS fills
         in the title + body + confirm-button label/colour and stamps a
         data-action onto #ai-confirm-modal-confirm so a single handler
         can fan out to either runAiRegenerate() or runAiAnomalyScan().
         Sizing matches the existing Dismiss/Revise modals on this page. --}}
    <div class="modal fade" id="ai-confirm-modal" tabindex="-1" aria-labelledby="ai-confirm-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="ai-confirm-modal-title">Confirm</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="ai-confirm-modal-body">
                    Are you sure?
                </div>
                <div class="modal-footer justify-content-end">
                    <button type="button" class="btn eb-btn-neutral btn-sm me-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn eb-btn-primary btn-sm" id="ai-confirm-modal-confirm">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    @include('resorts._emotional_buttons_v2_styles')
@endsection

    @section('import-css')
    <style>
        /* Neutral/geometry tokens come from the shared :root palette (resorts/layouts/_design_tokens.blade.php). */
        .tnum { font-variant-numeric: tabular-nums; }
        :root, .compliance-page {
            --cc-crit: #C2402B; --cc-high: #C47A1B; --cc-med: #3F7A8A; --cc-nr: #A3ABAE;
            --cc-g1: #3A4145; --cc-g2: #5F686D; --cc-g3: #8E979B; --cc-g4: #C9CFD1;
            --cc-ease: cubic-bezier(.16,1,.3,1); --cc-spring: cubic-bezier(.34,1.56,.64,1);
        }
        .compliance-page .cc-sp { flex: 1; }

        /* ---- Split button (hero) ---- */
        .cc-split { position: relative; display: flex; align-items: stretch; }
        .cc-split .cc-run { border-radius: 13px 0 0 13px; min-width: 190px; justify-content: center; overflow: hidden; }
        .cc-split .cc-run::before, .cc-split .cc-run::after { border-radius: 12px 0 0 12px; }
        .cc-split .cc-caret { border-radius: 0 13px 13px 0; padding: 13px 12px; box-shadow: inset 1px 0 0 rgba(1,70,83,.18); }
        .cc-split .cc-caret::before, .cc-split .cc-caret::after { border-radius: 0 12px 12px 0; }
        .cc-caret svg { transition: transform .25s var(--cc-ease, ease); }
        .cc-caret[aria-expanded="true"] svg { transform: rotate(180deg); }
        .cc-run .cc-pg { position: absolute; left: 0; bottom: 0; height: 3px; width: 100%; background: var(--teal); transform-origin: left; transform: scaleX(0); }
        .cc-run.busy .cc-pg { animation: ccProg 28s cubic-bezier(.16,1,.3,1) forwards; }
        .cc-run.busy svg { animation: ccRot .9s linear infinite; }
        .cc-run:disabled { opacity: 1; }
        @keyframes ccProg { to { transform: scaleX(.94); } }
        @keyframes ccRot { to { transform: rotate(360deg); } }
        .cc-dd {
            position: absolute; right: 0; top: calc(100% + 8px); z-index: 1050; width: 320px; background: #fff; color: var(--ink);
            border-radius: 16px; padding: 6px; box-shadow: 0 22px 48px rgba(1,30,36,.24), 0 0 0 1px var(--line);
            opacity: 0; visibility: hidden; transform: translate3d(0,-6px,0) scale(.98); transform-origin: top right;
            transition: opacity .18s, transform .28s var(--cc-ease, ease), visibility 0s .28s;
        }
        .cc-dd.on { opacity: 1; visibility: visible; transform: none; transition-delay: 0s; }
        .cc-dd .cc-dk { font-size: 10.5px; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; color: var(--cc-g3); padding: 9px 10px 5px; }
        .cc-dd button { display: flex; align-items: center; gap: 11px; width: 100%; text-align: left; border: 0; background: none; border-radius: 11px; padding: 9px 10px; cursor: pointer; font-family: inherit; color: var(--ink); transition: background .15s; }
        .cc-dd button:hover, .cc-dd button:focus-visible { background: var(--teal-soft); }
        .cc-dd button:disabled { opacity: .6; cursor: default; }
        .cc-dd .cc-di { width: 32px; height: 32px; border-radius: 10px; display: grid; place-items: center; flex: none; background: var(--teal-3); color: var(--teal); }
        .cc-dd .cc-di.ai { background: var(--teal); color: var(--lime); }
        .cc-dd .cc-di svg { width: 16px; height: 16px; }
        .cc-dd b { display: block; font-size: 13px; font-weight: 600; }
        .cc-dd small { display: block; font-size: 11.5px; color: var(--cc-g3); }
        .cc-dd em { margin-left: auto; font-style: normal; font-size: 10px; font-weight: 700; color: var(--teal); background: var(--lime); border-radius: 4px; padding: 1px 5px; }
        .cc-dd button .fa-spin { color: var(--teal); }

        /* ---- KPI row: four separate cards, label + number ---- */
        .compliance-page .cc-kpis { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 16px; margin-bottom: 16px; }
        @media (max-width: 900px) { .compliance-page .cc-kpis { grid-template-columns: repeat(2,1fr); } }
        .compliance-page .cc-kpi { background: var(--card); border: 1px solid var(--line); border-radius: 16px; box-shadow: var(--shadow); padding: 20px 22px; min-width: 0; }
        .compliance-page .cc-kl { display: block; font-size: 12.5px; color: var(--muted); }
        .compliance-page .cc-kn { font-size: 32px; font-weight: 600; line-height: 1; letter-spacing: -1px; color: var(--ink); margin-top: 10px; }
        .compliance-page .cc-kpi--alarm .cc-kn { color: var(--cc-crit); }
        .compliance-page .cc-kn.bump { animation: ccBump .45s var(--cc-spring); }
        @keyframes ccBump { 40% { transform: scale(1.18); } }
        .cc-rv { opacity: 0; transform: translate3d(0,8px,0); transition: opacity .4s var(--cc-ease, ease), transform .4s var(--cc-ease, ease); transition-delay: var(--d, 0ms); }
        .cc-rv.in { opacity: 1; transform: none; }

        /* ---- Toolbar (one line) ---- */
        .compliance-page .cc-controls { padding: 14px 18px; }
        .compliance-page .cc-searchrow { display: flex; gap: 10px; align-items: center; }
        .compliance-page .cc-search, .compliance-page .ctl .trigger, .compliance-page .cc-seg, .compliance-page .cc-dl { height: 34px; }
        .compliance-page .cc-search { display: flex; align-items: center; gap: 8px; margin: 0; border: 1px solid var(--line); border-radius: 10px; padding: 0 11px; width: min(320px, 46vw); background: #fff; transition: border-color .15s; }
        .compliance-page .cc-search:focus-within { border-color: var(--teal); }
        .compliance-page .cc-search svg { color: var(--cc-g3); flex: none; }
        .compliance-page .cc-search input { border: 0; outline: 0; background: none; width: 100%; font-size: 13px; font-family: inherit; }
        .compliance-page .cc-seg { display: inline-flex; background: var(--teal-soft); border: 1px solid var(--line); border-radius: 10px; padding: 2px; flex: none; }
        .compliance-page .cc-seg button { border: 0; background: none; border-radius: 8px; padding: 0 12px; font-family: inherit; font-size: 12.5px; font-weight: 500; color: var(--cc-g2); cursor: pointer; white-space: nowrap; transition: background .15s, color .15s; }
        .compliance-page .cc-seg button.on { background: #fff; color: var(--ink); box-shadow: 0 1px 2px rgba(20,35,42,.08); }
        .compliance-page .cc-dl { display: grid; place-items: center; width: 34px; flex: none; border: 1px solid var(--line); background: #fff; border-radius: 10px; color: var(--cc-g2); transition: border-color .15s, color .15s; }
        .compliance-page .cc-dl:hover { border-color: var(--teal); color: var(--teal); }
        @media (max-width: 760px) { .compliance-page .cc-searchrow { flex-wrap: wrap; } .compliance-page .cc-search { width: 100%; } }

        /* Category popover (checkboxes) */
        .compliance-page .ctl { position: relative; }
        .compliance-page .ctl .trigger { font-family: inherit; font-size: 13px; font-weight: 500; color: var(--cc-g1); background: #fff; border: 1px solid var(--line); border-radius: 10px; padding: 0 11px; cursor: pointer; display: inline-flex; align-items: center; gap: 7px; white-space: nowrap; transition: border-color .15s; }
        .compliance-page .ctl .trigger:hover, .compliance-page .ctl .trigger.has { border-color: var(--teal); color: var(--teal); }
        .compliance-page .ctl .trigger .chev { transition: transform .25s var(--cc-ease, ease); }
        .compliance-page .ctl .trigger[aria-expanded="true"] .chev { transform: rotate(180deg); }
        .compliance-page .ctl .trigger b { font-weight: 600; background: var(--teal); color: #fff; border-radius: 999px; font-size: 10.5px; padding: 0 6px; margin-left: 2px; }
        .compliance-page .ctl .menu {
            position: absolute; left: 0; top: calc(100% + 6px); z-index: 25; width: 300px; background: #fff; border-radius: 14px;
            box-shadow: 0 20px 44px rgba(1,30,36,.2), 0 0 0 1px var(--line); opacity: 0; visibility: hidden; transform: translate3d(0,-6px,0);
            transition: opacity .18s, transform .26s var(--cc-ease, ease), visibility 0s .26s;
        }
        .compliance-page .ctl .menu.on { opacity: 1; visibility: visible; transform: none; transition-delay: 0s; }
        .compliance-page .ctl .cb { max-height: 340px; overflow: auto; padding: 6px; }
        .compliance-page .ctl .ck2 { font-size: 10.5px; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; color: var(--cc-g3); padding: 8px 8px 4px; }
        .compliance-page .ctl label { display: flex; align-items: center; gap: 9px; padding: 7px 8px; border-radius: 9px; cursor: pointer; font-size: 12.5px; color: var(--cc-g1); margin: 0; }
        .compliance-page .ctl label:hover { background: var(--teal-soft); }
        .compliance-page .ctl label span { min-width: 0; overflow-wrap: anywhere; }
        .compliance-page .ctl label em { margin-left: auto; font-style: normal; font-size: 11.5px; color: var(--cc-g3); }
        .compliance-page .ctl input[type=checkbox] { appearance: none; -webkit-appearance: none; width: 16px; height: 16px; border-radius: 5px; border: 1.5px solid var(--cc-g4); display: grid; place-items: center; flex: none; transition: background .15s, border-color .15s; margin: 0; }
        .compliance-page .ctl input[type=checkbox]:checked { background: var(--teal); border-color: var(--teal); }
        .compliance-page .ctl input[type=checkbox]:checked::after { content: ""; width: 8px; height: 4px; border-left: 2px solid #fff; border-bottom: 2px solid #fff; transform: translate(0,-1px) rotate(-45deg); }
        .compliance-page .ctl .cf { display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; border-top: 1px solid var(--line); }
        .compliance-page .ctl .cf button { border: 0; background: none; font-family: inherit; font-size: 12.5px; font-weight: 600; cursor: pointer; color: var(--cc-g2); }
        .compliance-page .ctl .cf button.p { background: var(--teal); color: #fff; border-radius: 9px; padding: 7px 14px; }

        /* ---- Table ---- */
        .compliance-page .cc-table-card { border-radius: 14px; overflow: hidden; }
        .compliance-page .cc-table-scroll { overflow-x: auto; }
        #table-exitclearance-form { table-layout: fixed; min-width: 840px; margin: 0; }
        #table-exitclearance-form thead th {
            background: var(--teal-soft) !important; color: var(--ink); text-transform: uppercase; font-size: 10.5px; letter-spacing: .5px; font-weight: 600;
            padding: 14px 16px !important; border-bottom: 1px solid var(--line);
        }
        #table-exitclearance-form thead th::before, #table-exitclearance-form thead th::after { display: none !important; }
        #table-exitclearance-form thead th:nth-child(1) { width: 24%; }
        #table-exitclearance-form thead th:nth-child(2) { width: 38%; }
        #table-exitclearance-form thead th:nth-child(3) { width: 14%; }
        #table-exitclearance-form thead th:nth-child(4) { width: 12%; }
        #table-exitclearance-form thead th:nth-child(5) { width: 10%; }
        #table-exitclearance-form tbody tr { background: #fff; border-bottom: 1px solid var(--line-2); cursor: pointer; transition: background .15s, opacity .3s; }
        #table-exitclearance-form tbody tr:hover { background: var(--teal-soft); }
        #table-exitclearance-form tbody tr.on { background: #EAF3F3; }
        #table-exitclearance-form tbody tr:focus-visible { outline: 2px solid var(--teal); outline-offset: -2px; }
        #table-exitclearance-form tbody td { font-size: 13.5px; vertical-align: middle; padding: 12px 14px; }
        #table-exitclearance-form tbody tr.gh, #table-exitclearance-form tbody tr.cc-none { cursor: default; }
        #table-exitclearance-form tbody tr.gh:hover { background: #fff; }
        #table-exitclearance-form tbody td.dataTables_empty { padding: 0; }
        @media (max-width: 980px) { #table-exitclearance-form .c-mod, #table-exitclearance-form .c-age { display: none; } }

        /* Rows reveal once (opacity on the row, rise on its cells); a dismissed row fades and slides out */
        #table-exitclearance-form tbody tr.cc-rv { opacity: 0; transform: none; }
        #table-exitclearance-form tbody tr.cc-rv.in { opacity: 1; }
        #table-exitclearance-form tbody tr.cc-rv td > * { transform: translate3d(0,8px,0); transition: transform .4s var(--cc-ease, ease); transition-delay: var(--d, 0ms); }
        #table-exitclearance-form tbody tr.cc-rv.in td > * { transform: none; }
        #table-exitclearance-form tbody tr.leave { opacity: 0; }
        #table-exitclearance-form tbody tr.leave td > * { transform: translate3d(30px,0,0); transition: transform .35s var(--cc-ease, ease); }

        .cc-emp { display: flex; align-items: center; gap: 11px; min-width: 0; }
        .cc-emp > div { min-width: 0; }
        .cc-emp-name { font-weight: 600; color: var(--ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .cc-emp-role { font-size: 11.5px; color: var(--faint); margin-top: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .cc-iss { min-width: 0; }
        .cc-iss b { display: block; font-weight: 600; color: var(--ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .cc-iss p { margin: 0; font-size: 12px; color: var(--cc-g2); display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
        .cc-mod { font-size: 12px; color: var(--cc-g2); overflow-wrap: anywhere; }
        .cc-age { font-size: 12px; color: var(--cc-g2); }
        .cc-age small { display: block; font-size: 11px; color: var(--cc-g3); }
        .cc-stt { font-size: 12.5px; font-weight: 500; color: var(--cc-crit); }
        .cc-gh { display: flex; align-items: center; gap: 10px; padding: 14px 18px 8px; }
        .cc-gh i { width: 8px; height: 8px; border-radius: 50%; flex: none; }
        .cc-gh b { font-size: 13px; font-weight: 600; color: var(--ink); }
        .cc-gh span { font-size: 12px; color: var(--cc-g3); }
        #table-exitclearance-form tbody tr.gh td { padding: 0; background: #fff; }

        .cc-empty { padding: 56px 20px; text-align: center; color: var(--cc-g2); white-space: normal; }
        .cc-empty .ok { width: 52px; height: 52px; border-radius: 50%; margin: 0 auto 12px; display: grid; place-items: center; background: var(--lime); color: var(--teal); animation: ccPop .5s var(--cc-spring); }
        .cc-empty b { display: block; font-size: 16px; color: var(--ink); margin-bottom: 2px; }
        .cc-empty a { color: var(--teal); font-weight: 600; }
        @keyframes ccPop { from { transform: scale(.5); opacity: 0; } }

        /* Freshly AI-regenerated rows: small "NEW AI" pill before the issue name (set by the server, survives paging) */
        #table-exitclearance-form tbody tr.ai-fresh-row .cc-iss b::before {
            content: "NEW AI"; display: inline-block; background-color: #16a34a; color: #fff; font-size: 10px; font-weight: 700; letter-spacing: .4px;
            padding: 2px 7px; border-radius: 10px; margin-right: 8px; vertical-align: middle;
        }

        /* ---- Side panel ---- */
        .cc-ov { position: fixed; inset: 0; background: rgba(1,30,36,.28); opacity: 0; visibility: hidden; transition: opacity .25s, visibility 0s .25s; z-index: 1060; }
        .cc-ov.on { opacity: 1; visibility: visible; transition-delay: 0s; }
        .cc-pn { position: fixed; top: 0; right: 0; bottom: 0; width: min(500px,100%); background: #fff; z-index: 1061; display: flex; flex-direction: column; transform: translate3d(104%,0,0); visibility: hidden; transition: transform .45s var(--cc-ease, ease), visibility 0s .45s; box-shadow: -24px 0 60px rgba(1,40,48,.16); }
        .cc-pn.on { transform: none; visibility: visible; transition-delay: 0s; }
        .cc-x { position: absolute; top: 14px; right: 14px; width: 34px; height: 34px; border-radius: 10px; border: 1px solid var(--line); background: #fff; cursor: pointer; display: grid; place-items: center; z-index: 2; color: var(--ink); }
        .cc-pb { flex: 1; overflow: auto; padding: 22px 24px; }
        .cc-pb.sw { animation: ccFade .3s var(--cc-ease, ease); }
        @keyframes ccFade { from { opacity: 0; transform: translate3d(0,8px,0); } }
        .cc-who { display: flex; align-items: center; gap: 13px; padding-right: 44px; }
        .cc-who .wav { width: 52px; height: 52px; }
        .cc-who b { display: block; font-size: 15px; font-weight: 600; color: var(--ink); }
        .cc-who small { font-size: 12px; color: var(--cc-g2); }
        .cc-who a { font-family: inherit; font-size: 12px; color: var(--teal); font-weight: 600; text-decoration: none; margin-left: 2px; }
        .cc-sevw { display: block; font-size: 11.5px; font-weight: 600; color: var(--c); margin-top: 20px; }
        .cc-sevw .sm { color: var(--cc-g3); font-weight: 500; }
        .cc-pb h2 { font-size: 19px; font-weight: 600; line-height: 1.3; margin: 3px 0 6px; letter-spacing: -.01em; color: var(--ink); }
        .cc-pb .what { font-size: 13.5px; line-height: 1.55; color: var(--cc-g1); margin: 0; }
        .cc-basis { display: flex; gap: 8px; align-items: flex-start; margin-top: 12px; font-size: 12.5px; line-height: 1.5; color: var(--cc-g2); }
        .cc-basis svg { flex: none; margin-top: 2px; color: var(--cc-g3); }
        .cc-fx { margin-top: 22px; border: 1px solid var(--line); border-radius: 16px; overflow: hidden; }
        .cc-fx .fh { display: flex; align-items: center; gap: 8px; padding: 14px 16px; border-bottom: 1px solid var(--line-2); }
        .cc-fx .fh b { font-size: 13px; font-weight: 600; color: var(--ink); }
        .cc-fx .fh .ai { font-size: 10px; font-weight: 700; letter-spacing: .04em; color: var(--teal); background: var(--lime); border-radius: 4px; padding: 1px 5px; }
        .cc-fx .fd { padding: 16px 16px 0; font-size: 13.5px; line-height: 1.55; color: var(--cc-g1); text-align: left; }
        .cc-fx .fp { margin: 14px 16px 0; padding: 14px 16px; border-radius: 12px; background: var(--teal-soft); }
        .cc-fx .fp .fpr { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 12px; }
        .cc-fx .fp .fpk { display: block; font-size: 10.5px; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; color: var(--cc-g3); }
        .cc-fx .fp .fpv { display: block; margin-top: 2px; font-size: 15px; font-weight: 600; }
        .cc-fx .fp .fpv.bad { color: var(--cc-crit); text-decoration: line-through; }
        .cc-fx .fp .fpv.good { color: #1F8A5B; }
        .cc-fx .fp svg { color: var(--cc-g3); }
        .cc-fx .fp .fpn { margin: 10px 0 0; font-size: 12.5px; line-height: 1.5; color: var(--cc-g2); }
        .cc-fx .fact { display: flex; gap: 8px; padding: 16px; }
        .cc-go { font-family: inherit; text-transform: none; letter-spacing: 0; display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 600; color: var(--teal); text-decoration: none; border: 1px solid var(--line); border-radius: 11px; padding: 9px 13px; background: #fff; transition: border-color .15s; }
        .cc-go:hover { border-color: var(--teal); color: var(--teal); }
        .cc-same { margin-top: 18px; font-size: 12.5px; color: var(--cc-g2); padding: 11px 13px; border-radius: 12px; background: var(--line-2); }
        .cc-same button { border: 0; background: none; color: var(--teal); font-weight: 600; cursor: pointer; font-size: 12.5px; font-family: inherit; padding: 0; margin-left: 4px; }
        .cc-tl { margin-top: 22px; }
        .cc-tl h4 { font-size: 11px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--cc-g3); margin: 0 0 10px; }
        .cc-tl ol { list-style: none; position: relative; padding-left: 20px; margin: 0; }
        .cc-tl ol::before { content: ""; position: absolute; left: 5px; top: 6px; bottom: 6px; width: 1.5px; background: var(--line); }
        .cc-tl li { position: relative; font-size: 12.5px; color: var(--cc-g1); padding-bottom: 10px; }
        .cc-tl li::before { content: ""; position: absolute; left: -19px; top: 5px; width: 9px; height: 9px; border-radius: 50%; background: #fff; border: 2px solid var(--cc-g4); }
        .cc-tl li.now::before { border-color: var(--teal); background: var(--teal); }
        .cc-tl li small { display: block; font-size: 11px; color: var(--cc-g3); }
        .cc-pf { display: flex; align-items: center; gap: 10px; padding: 13px 24px; border-top: 1px solid var(--line); }
        .cc-pf select { -webkit-appearance: none; appearance: none; border: 1px solid var(--line); border-radius: 10px; padding: 8px 34px 8px 12px; font-family: inherit; font-size: 12.5px; color: var(--cc-g1); cursor: pointer;
            background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23014653' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E") no-repeat right 12px center; }
        .cc-nav { display: flex; gap: 4px; }
        .cc-nav button { width: 32px; height: 32px; border-radius: 9px; border: 1px solid var(--line); background: #fff; cursor: pointer; color: var(--cc-g2); display: grid; place-items: center; }
        .cc-nav button:hover { border-color: var(--teal); color: var(--teal); }

        @media (prefers-reduced-motion: reduce) {
            .cc-rv, .cc-pn, .cc-ov, .cc-dd, .compliance-page .ctl .menu, #table-exitclearance-form tbody tr, #table-exitclearance-form tbody tr td > * { transition-duration: .01ms !important; animation-duration: .01ms !important; transition-delay: 0s !important; }
            .cc-rv { opacity: 1; transform: none; }
            .cc-run.busy svg, .cc-empty .ok, .cc-kn.bump, .cc-pb.sw { animation: none; }
        }

        /* ---- Shared AI sparkle glyph ---- */
        .ai-spark { width: 14px; height: 14px; flex-shrink: 0; vertical-align: -2px; animation: aiTwinkle 2.4s ease-in-out infinite; }
        @media (prefers-reduced-motion: reduce) { .ai-spark { animation: none; } }
        @keyframes aiTwinkle { 0%, 100% { opacity: .85; } 50% { opacity: 1; } }
    </style>
    @endsection

    @section('import-scripts')

    <script type="text/javascript">
    var viewForm;
    var CC = { q: '', mods: [], rules: [], pend: null, grp: 'list', cur: null, anim: true, kp0: true };
    var CC_PAGES = @json($ccPages);
    var CC_PROFILE = @json(route('people.employees.details', '__id__'));
    var CC_DISMISS = @json(route('people.compliances.dismiss', '__id__'));
    var CC_RESTORE = @json(route('people.compliances.restore', '__id__'));
    var CC_COUNTS = @json(route('people.compliance.counts'));
    var CC_RM = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
    var CC_SEV = { critical: ['Critical', 'var(--cc-crit)'], high: ['High', 'var(--cc-high)'], medium: ['Medium', 'var(--cc-med)'] };
    var CC_NR = ['Unrated', 'var(--cc-nr)'];
    // Plain-language notes for rules whose basis and fix are fixed by the app itself (the rule only fires for these facts); every other rule uses the AI text.
    var CC_PLAY = {
        'TIN Requirement': { basis: 'MIRA — a TIN is needed once earnings pass the withholding threshold.', fix: 'Add the employee’s TIN to their record.', field: 'TIN', from: 'Missing', to: 'TIN on file', note: 'Once the TIN is saved, the next compliance check clears this issue.' }
    };
    // First statute reference in a sentence, e.g. "Section 59 of the Employment Act 2008".
    function firstCite(t) {
        var m = /((?:Article|Section|Clause|Regulation)\s+\d+[A-Za-z]?(?:\s+of\s+the\s+[A-Za-z ]+?(?:Act|Regulation|Law)(?:\s+\d{4})?)?)/.exec(t || '');
        return m ? m[1] : '';
    }
    function $1(s, r) { return (r || document).querySelector(s); }

    // list() returns module_name / remediation_ai / severity_ai already HTML-escaped ("Time &amp; Attendance"). Undo that once; escHtml re-escapes for output.
    function unesc(s) {
        return String(s == null ? '' : s).replace(/&(lt|gt|quot|#0?39|amp);/g, function (m, e) {
            return { lt: '<', gt: '>', quot: '"', '#39': "'", '#039': "'", amp: '&' }[e];
        });
    }
    function escHtml(s) { return wavEsc(s); }
    function plain(html) { return unesc(String(html || '').replace(/<[^>]+>/g, '')).replace(/\s+/g, ' ').trim(); }
    function firstMatch(html, re) { var m = re.exec(html || ''); return m ? m[1] : ''; }
    // "Time and Attendance" / "Time & Attendance" / trailing spaces → one display name (display and grouping only; stored values are untouched)
    function modName(m) { return String(m || '').replace(/\s+/g, ' ').trim().replace(/\band\b/gi, '&'); }

    // One compact record per breach, built once from the row list() already returns (no extra request, nothing added to the query).
    function ccMap(r) {
        var e = r.employee || {}, a = e.resort_admin || {}, p = e.position || {};
        var name = ((a.first_name || '') + ' ' + (a.last_name || '')).trim() || plain(firstMatch(r.employee_name, /<div class="cc-emp-name">([\s\S]*?)<\/div>/)) || '—';
        var ph = firstMatch(r.employee_name, /<img src="([^"]+)"/);
        var code = r.employee_id && r.employee_id !== '-' ? String(r.employee_id) : '';
        var sev = unesc(r.severity_ai).trim(), sk = sev.toLowerCase();
        var dm = /(\d{2}) (\w{3}) (\d{4})/.exec(r.reported_on || ''), tm = firstMatch(r.reported_on, /(\d{2}:\d{2})/);
        var days = dm ? Math.floor((Date.now() - new Date(dm[1] + ' ' + dm[2] + ' ' + dm[3]).getTime()) / 864e5) : null;
        var mod = modName(unesc(r.module_name));
        return {
            id: r.id, name: name, role: unesc(String(p.position_title || '')).trim(), code: code, emp: e.id || '', gender: a.gender || '',
            photo: ph ? unesc(ph) : '', rule: plain(firstMatch(r.compliance_breached_name, /<div class="cc-rule-name">([\s\S]*?)<\/div>/)),
            sk: CC_SEV[sk] ? sk : '', desc: plain(firstMatch(r.description, /<div class="cc-desc-text">([\s\S]*?)<\/div>/)),
            fix: unesc(r.remediation_ai).trim(), mod: mod, date: dm ? dm[1] + '-' + dm[2] + '-' + dm[3] : '', time: tm, days: days,
            DT_RowAttr: r.DT_RowAttr, DT_RowClass: r.DT_RowClass // set by the server: AI-fresh flag + row id
        };
    }

    $(document).ready(function () {
        updateDownloadUrl();
        ccKpiIn();
        ComplianceIndex();

        // Search: employee, role, ID, issue, module — filters the rows already loaded, so no request per keystroke.
        var st;
        $('.search').on('input', function () {
            var v = this.value.trim().toLowerCase();
            clearTimeout(st);
            st = setTimeout(function () { CC.q = v; CC.anim = false; viewForm.draw(); updateDownloadUrl(); }, 120);
        });

        function updateDownloadUrl() {
            var searchTerm = $('.search').val();
            $('#compliance-download-btn').attr('href', "{{route('people.compliance.download')}}?searchTerm=" + encodeURIComponent(searchTerm));
        }
        window.updateDownloadUrl = updateDownloadUrl;
    });

    // Filters over the loaded rows: text search + ticked modules + ticked rules.
    $.fn.dataTable.ext.search.push(function (settings, _row, idx) {
        if (settings.nTable.id !== 'table-exitclearance-form') { return true; }
        var d = settings.aoData[idx]._aData;
        if (CC.mods.length && CC.mods.indexOf(d.mod) < 0) { return false; }
        if (CC.rules.length && CC.rules.indexOf(d.rule) < 0) { return false; }
        return !CC.q || (d.name + ' ' + d.role + ' ' + d.code + ' ' + d.rule + ' ' + d.desc + ' ' + d.mod).toLowerCase().indexOf(CC.q) > -1;
    });

    function ComplianceIndex() {
        viewForm = $('#table-exitclearance-form').DataTable({
            searching: true, dom: 'rt<"d-flex align-items-center justify-content-between flex-wrap p-3"ip>',
            bLengthChange: false, bInfo: true, bAutoWidth: false, iDisplayLength: 10, processing: true, order: [], columnDefs: [{ orderable: false, targets: '_all' }], // no header sorting; By rule orders through the API
            ajax: {
                url: '{{ route("people.compliance.list") }}', type: 'GET',
                data: { start: 0, length: -1 }, // every open breach once; paging, search and filters then run in the browser
                dataSrc: function (j) { return (j.data || []).map(ccMap); }
            },
            columns: [
                { data: 'name', render: function (v, t, d) { return t !== 'display' ? v : empCell(d); } },
                { data: 'rule', render: function (v, t, d) { return t !== 'display' ? v : '<div class="cc-iss"><b title="' + escHtml(d.rule) + '">' + escHtml(d.rule) + '</b>' + (d.desc ? '<p title="' + escHtml(d.desc) + '">' + escHtml(d.desc) + '</p>' : '') + '</div>'; } },
                { data: 'mod', className: 'c-mod', render: function (v, t) { return t !== 'display' ? v : '<span class="cc-mod" title="' + escHtml(v) + '">' + escHtml(v) + '</span>'; } },
                { data: 'date', className: 'c-age', render: function (v, t, d) { return t !== 'display' ? v : (v ? '<div class="cc-age"><span class="tnum">' + escHtml(v) + '</span>' + (d.time ? '<small class="tnum">' + escHtml(d.time) + '</small>' : '') + '</div>' : '<span class="cc-age">N/A</span>'); } },
                { data: null, defaultContent: '<span class="cc-stt">Breached</span>' },
                { data: 'rule', visible: false }
            ],
            language: {
                emptyTable: '<div class="cc-empty"><div class="ok"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg></div><b>All clear</b>No open compliance issues.</div>',
                zeroRecords: '<div class="cc-empty"><b>Nothing here</b>No issues match these filters. <a href="#" data-clr>Clear filters</a></div>',
                info: 'Showing _START_ to _END_ of _TOTAL_ issues', infoEmpty: '', infoFiltered: '', paginate: { previous: '&lsaquo;', next: '&rsaquo;' }
            },
            createdRow: function (tr, d) { tr.dataset.id = d.id; tr.tabIndex = 0; tr.setAttribute('role', 'button'); tr.setAttribute('aria-label', d.rule + ' — ' + d.name); },
            drawCallback: ccDraw
        });
    }

    function empCell(d) {
        var sub = [d.role, d.code].filter(Boolean).join(' · ');
        return '<div class="cc-emp">' + wavHTML({ photo: d.photo, name: d.name, gender: d.gender, id: d.code }) + '<div><div class="cc-emp-name" title="' + escHtml(d.name) + '">' + escHtml(d.name) + '</div>'
            + (sub ? '<div class="cc-emp-role" title="' + escHtml(sub) + '">' + escHtml(sub) + '</div>' : '') + '</div></div>';
    }

    function ccDraw() {
        var api = this.api(), page = api.rows({ page: 'current' }), nodes = page.nodes().toArray(), data = page.data().toArray();
        $(api.table().body()).find('tr.gh').remove();
        if (CC.grp === 'rule') {
            var all = api.rows({ search: 'applied' }).data().toArray(), n = {}, last = null;
            all.forEach(function (d) { n[d.rule] = (n[d.rule] || 0) + 1; });
            data.forEach(function (d, i) {
                if (d.rule === last) { return; }
                last = d.rule;
                var col = (CC_SEV[d.sk] || CC_NR)[1];
                $(nodes[i]).before('<tr class="gh"><td colspan="5"><div class="cc-gh"><i style="background:' + col + '"></i><b>' + escHtml(d.rule) + '</b><span>' + n[d.rule] + ' employee' + (n[d.rule] > 1 ? 's' : '') + ' · ' + escHtml(d.mod) + '</span></div></td></tr>');
            });
        }
        if (CC.cur != null) { nodes.forEach(function (tr) { tr.classList.toggle('on', +tr.dataset.id === CC.cur); }); }
        if (CC.anim && !CC_RM) {
            nodes.forEach(function (tr, i) { tr.style.setProperty('--d', (i * 35) + 'ms'); tr.classList.add('cc-rv'); });
            requestAnimationFrame(function () { requestAnimationFrame(function () { nodes.forEach(function (tr) { tr.classList.add('in'); }); }); });
        }
        CC.anim = true;
        applyFreshRowHighlight();
    }

    // Four numbers: rise in once on load; a number bumps when it changes (after a check or a dismiss).
    function ccKpiIn() {
        if (!CC.kp0 || CC_RM) { return; }
        CC.kp0 = false;
        var k = document.querySelectorAll('#cc-kpis .cc-kpi');
        k.forEach(function (el, i) { el.style.setProperty('--d', (i * 60) + 'ms'); el.classList.add('cc-rv'); });
        requestAnimationFrame(function () { requestAnimationFrame(function () { k.forEach(function (el) { el.classList.add('in'); }); }); });
    }
    // The 4 KPI numbers from the lightweight counts endpoint (same numbers index() renders), in the cards' order.
    function ccKpis() {
        return fetch(CC_COUNTS, { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (j) {
                if (!j || !j.summary) { return; }
                var vals = [j.summary.critical, j.summary.employees, j.summary.rules, j.summary.modules];
                document.querySelectorAll('#cc-kpis .cc-kn').forEach(function (el, i) {
                    var v = vals[i] == null ? null : String(vals[i]);
                    if (v != null && v !== el.textContent.trim()) { el.textContent = v; el.classList.remove('bump'); void el.offsetWidth; el.classList.add('bump'); }
                });
            }).catch(function () {});
    }

    // ─── Rows: open the panel; clear filters ────────────────────────────
    $('#table-exitclearance-form tbody').on('click', 'tr', function (e) {
        if (e.target.closest('[data-clr]')) { e.preventDefault(); ccClear(); return; }
        if (this.dataset.id) { ccOpen(+this.dataset.id); }
    }).on('keydown', 'tr', function (e) {
        if (this.dataset.id && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); ccOpen(+this.dataset.id); }
    });
    function ccClear() {
        CC.q = ''; CC.mods = []; CC.rules = []; $('.search').val(''); ccCatLabel(); viewForm.draw(); updateDownloadUrl();
    }

    // ─── All issues / By rule ───────────────────────────────────────────
    $('#cc-grp').on('click', 'button', function () {
        var g = this.dataset.g;
        if (g === CC.grp) { return; }
        CC.grp = g;
        $('#cc-grp button').removeClass('on'); $(this).addClass('on');
        viewForm.order(g === 'rule' ? [5, 'asc'] : []).draw(); // 'list' keeps the server's order
    });

    // ─── Category: ticked modules / rules, with counts ──────────────────
    var catBtn = $1('#cc-cat-btn'), catMenu = $1('#cc-cat-menu');
    function ccCatLabel() {
        var n = CC.mods.length + CC.rules.length;
        catBtn.classList.toggle('has', !!n);
        $1('#cc-cat-l').innerHTML = n ? 'Category <b class="tnum">' + n + '</b>' : 'Category';
    }
    function ccCatBox(k, v, n) {
        var on = CC.pend[k].indexOf(v) > -1;
        return '<label><input type="checkbox" data-k="' + k + '" value="' + escHtml(v) + '"' + (on ? ' checked' : '') + '><span>' + escHtml(v) + '</span><em class="tnum">' + n + '</em></label>';
    }
    function ccCatOpen() {
        var mods = {}, rls = {};
        viewForm.rows().data().each(function (d) { mods[d.mod] = (mods[d.mod] || 0) + 1; rls[d.rule] = (rls[d.rule] || 0) + 1; });
        CC.pend = { m: CC.mods.slice(), r: CC.rules.slice() };
        catMenu.innerHTML = '<div class="cb"><div class="ck2">By module</div>' + Object.keys(mods).sort().map(function (m) { return ccCatBox('m', m, mods[m]); }).join('')
            + '<div class="ck2">By rule</div>' + Object.keys(rls).sort().map(function (r) { return ccCatBox('r', r, rls[r]); }).join('')
            + '</div><div class="cf"><button type="button" data-cc>Clear</button><button type="button" class="p" data-cd>Done</button></div>';
        catMenu.classList.add('on'); catBtn.setAttribute('aria-expanded', 'true');
    }
    function ccCatClose() { catMenu.classList.remove('on'); catBtn.setAttribute('aria-expanded', 'false'); }
    catBtn.addEventListener('click', function (e) { e.stopPropagation(); catMenu.classList.contains('on') ? ccCatClose() : ccCatOpen(); });
    catMenu.addEventListener('click', function (e) {
        e.stopPropagation();
        if (e.target.closest('[data-cc]')) { CC.pend = { m: [], r: [] }; catMenu.querySelectorAll('input').forEach(function (c) { c.checked = false; }); return; }
        if (e.target.closest('[data-cd]')) { CC.mods = CC.pend.m; CC.rules = CC.pend.r; ccCatClose(); ccCatLabel(); viewForm.draw(); }
    });
    catMenu.addEventListener('change', function (e) {
        var c = e.target, a = CC.pend[c.dataset.k], i = a.indexOf(c.value);
        if (c.checked && i < 0) { a.push(c.value); } else if (!c.checked && i > -1) { a.splice(i, 1); }
    });

    // ─── Split button ───────────────────────────────────────────────────
    var dd = $1('#cc-dd'), caret = $1('#cc-caret');
    function ccDdClose() { dd.classList.remove('on'); caret.setAttribute('aria-expanded', 'false'); }
    caret.addEventListener('click', function (e) { e.stopPropagation(); var on = dd.classList.toggle('on'); caret.setAttribute('aria-expanded', on); });
    dd.addEventListener('click', function (e) {
        e.stopPropagation();
        var b = e.target.closest('button');
        if (!b || b.disabled) { return; }
        ccDdClose();
        if (b.dataset.act === 'run') { ccRun(); }
    });
    document.addEventListener('click', function (e) {
        if (!e.target.closest('#cc-split')) { ccDdClose(); }
        if (!e.target.closest('#cc-cat-ctl')) { ccCatClose(); }
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { ccDdClose(); ccCatClose(); } });
    $('#cc-run').on('click', ccRun);

    // The rule check is the existing route; it redirects back to this page when it finishes.
    function ccRun() {
        var b = $1('#cc-run'), l = $1('#cc-run-l');
        if (b.classList.contains('busy')) { return; }
        b.classList.add('busy'); b.disabled = true; l.textContent = 'Checking…';
        fetch(b.dataset.url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { if (!r.ok) { throw new Error(r.status); } })
            .then(function () {
                viewForm.ajax.reload(function () {
                    wisdomToast('ok', 'Compliance check complete', viewForm.rows().count() + ' open ' + (viewForm.rows().count() === 1 ? 'issue' : 'issues') + '.');
                }, false);
                ccKpis();
            })
            .catch(function () { wisdomToast('err', 'Compliance check failed', 'Something went wrong. Please try again.'); })
            .then(function () { b.classList.remove('busy'); b.disabled = false; l.textContent = 'Run compliance check'; });
    }

    // ─── Side panel ─────────────────────────────────────────────────────
    var pn = $1('#cc-pn'), ov = $1('#cc-ov');
    function ccRow(id) { var r = null; viewForm.rows().data().each(function (d) { if (d.id === id) { r = d; } }); return r; }
    function ccOrder() { return viewForm.rows({ search: 'applied', order: 'current' }).data().toArray().map(function (d) { return d.id; }); }
    function ccOpen(id) {
        CC.cur = id; ccFill(true);
        pn.classList.add('on'); ov.classList.add('on'); pn.setAttribute('aria-hidden', 'false');
        $('#table-exitclearance-form tbody tr').each(function () { this.classList.toggle('on', +this.dataset.id === id); });
        setTimeout(function () { $1('#cc-px').focus({ preventScroll: true }); }, 60);
    }
    function ccClose() {
        pn.classList.remove('on'); ov.classList.remove('on'); pn.setAttribute('aria-hidden', 'true'); CC.cur = null;
        $('#table-exitclearance-form tbody tr.on').removeClass('on');
    }
    function ccFill(sw) {
        var d = ccRow(CC.cur);
        if (!d) { return; }
        var sv = CC_SEV[d.sk] || CC_NR, pg = CC_PAGES[d.mod], pb = $1('#cc-pb'), same = [], h = '';
        viewForm.rows().data().each(function (x) { if (x.rule === d.rule && x.id !== d.id) { same.push(x); } });
        var prof = d.emp ? '<a href="' + escHtml(CC_PROFILE.replace('__id__', encodeURIComponent(btoa(String(d.emp))))) + '">Profile</a>' : '';
        h += '<div class="cc-who">' + wavHTML({ photo: d.photo, name: d.name, gender: d.gender, id: d.code }) + '<div><b>' + escHtml(d.name) + '</b><small>' + escHtml([d.role, d.code].filter(Boolean).join(' · ')) + (prof ? ' · ' + prof : '') + '</small></div></div>';
        h += '<div class="cc-sevw" style="--c:' + sv[1] + '">' + sv[0] + (d.mod ? '<span class="sm"> · ' + escHtml(d.mod) + '</span>' : '') + '</div>';
        h += '<h2>' + escHtml(d.rule) + '</h2>' + (d.desc ? '<p class="what">' + escHtml(d.desc) + '</p>' : '');
        // Legal basis: the rule's own note when we have one, else the statute reference the AI explanation already cites.
        var pk = CC_PLAY[d.rule] || {}, cite = pk.basis || firstCite(d.desc);
        if (cite) { h += '<div class="cc-basis"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v18M5 21h14M5 7h14M5 7l-3 8a4 4 0 0 0 6 0L5 7zM19 7l-3 8a4 4 0 0 0 6 0l-3-8z"/></svg><span>' + escHtml(cite) + '</span></div>'; }
        var go = pg ? '<div class="fact"><a class="cc-go" href="' + escHtml(pg.url) + '">Open ' + escHtml(pg.label) + ' <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a></div>' : '<div class="fact" style="padding:0 0 8px"></div>';
        var fixText = d.fix || pk.fix;
        if (fixText) {
            h += '<div class="cc-fx"><div class="fh"><b>Suggested fix</b>' + (d.fix ? '<span class="ai">AI</span>' : '') + '</div><div class="fd">' + escHtml(fixText) + '</div>';
            if (pk.from) {
                h += '<div class="fp"><div class="fpr"><div><span class="fpk">' + escHtml(pk.field) + '</span><span class="fpv bad">' + escHtml(pk.from) + '</span></div>'
                    + '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>'
                    + '<div><span class="fpk">Needed</span><span class="fpv good">' + escHtml(pk.to) + '</span></div></div>'
                    + (pk.note ? '<p class="fpn">' + escHtml(pk.note) + '</p>' : '') + '</div>';
            }
            h += go + '</div>';
        } else if (pg) {
            h += '<div class="cc-fx">' + go + '</div>';
        }
        if (same.length) {
            h += '<div class="cc-same">Same issue for ' + same.slice(0, 8).map(function (x) { return '<button type="button" data-peer="' + x.id + '">' + escHtml(x.name) + '</button>'; }).join(', ') + (same.length > 8 ? ' and ' + (same.length - 8) + ' more' : '') + '.</div>';
        }
        if (d.date) {
            h += '<div class="cc-tl"><h4>History</h4><ol><li>Reported by the compliance check · Breached<small>' + escHtml(d.date + (d.time ? ', ' + d.time : '')) + '</small></li>'
                + '<li class="now">Still breached' + (d.days != null && d.days >= 0 ? ' — ' + d.days + (d.days === 1 ? ' day' : ' days') : '') + '<small>Today</small></li></ol></div>';
        }
        pb.innerHTML = h;
        if (sw) { pb.classList.remove('sw'); void pb.offsetWidth; pb.classList.add('sw'); }
        pb.scrollTop = 0;
        var dis = $1('#cc-dis'); if (dis) { dis.value = ''; }
    }
    $('#cc-pb').on('click', '[data-peer]', function () { ccOpen(+this.dataset.peer); });
    $('#cc-pn').on('click', '[data-nv]', function () {
        var o = ccOrder(), k = o.indexOf(CC.cur) + (+this.dataset.nv);
        if (o[k] !== undefined) { ccOpen(o[k]); }
    });
    $1('#cc-px').addEventListener('click', ccClose); ov.addEventListener('click', ccClose);
    document.addEventListener('keydown', function (e) {
        if (!pn.classList.contains('on')) { return; }
        if (e.key === 'Escape') { ccClose(); }
        if ((e.key === 'ArrowDown' || e.key === 'ArrowUp') && !/SELECT|INPUT/.test(document.activeElement.tagName)) {
            e.preventDefault();
            var o = ccOrder(), k = o.indexOf(CC.cur) + (e.key === 'ArrowDown' ? 1 : -1);
            if (o[k] !== undefined) { ccOpen(o[k]); }
        }
    });

    // ─── Dismiss: same confirm and same route as before. The route takes no reason, so the choice is not stored. ───
    $('#cc-dis').on('change', function () {
        var sel = this, id = CC.cur, d = ccRow(id);
        if (!sel.value || !d) { return; }
        wisdomConfirm({
            role: 'confirm', title: 'Are you sure?', text: "You want to dismiss this compliance?", confirmText: 'Yes, dismiss it!'
        }).then(function (result) {
            if (!result.isConfirmed) { sel.value = ''; return; }
            $.ajax({
                url: CC_DISMISS.replace('__id__', btoa(String(id))), type: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function () {
                    var o = ccOrder(), k = o.indexOf(id), nx = o[k + 1] !== undefined ? o[k + 1] : o[k - 1];
                    $('#table-exitclearance-form tbody tr[data-id="' + id + '"]').addClass('leave');
                    setTimeout(function () {
                        viewForm.ajax.reload(function () {
                            if (nx !== undefined && ccRow(nx)) { ccOpen(nx); } else { ccClose(); }
                            wisdomToast('ok', 'Dismissed', d.name + '’s issue was dismissed.', { undo: function () { ccRestore(id, d.name); } });
                        }, false);
                        ccKpis();
                    }, CC_RM ? 0 : 320);
                },
                error: function () { sel.value = ''; wisdomAlert({ type: 'error', title: 'Error!', text: 'Something went wrong. Please try again.' }); }
            });
        });
    });

    // Undo a dismissal (from the "Dismissed" toast): back to Pending, then refresh the list and the KPIs.
    function ccRestore(id, name) {
        $.ajax({
            url: CC_RESTORE.replace('__id__', btoa(String(id))), type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function () {
                viewForm.ajax.reload(function () { if (ccRow(id)) { ccOpen(id); } }, false);
                ccKpis();
                wisdomToast('ok', 'Restored', name + '’s issue is open again.');
            },
            error: function () { wisdomAlert({ type: 'error', title: 'Error!', text: 'Could not undo the dismissal. Please try again.' }); }
        });
    }

    // IDs of compliance rows freshly AI-regenerated in the most recent batch. Set in the AJAX success handlers below,
    // consumed by the table draw to flag the rows for ~12 s after the reload. Cleared afterwards so a refresh doesn't re-flag stale rows.
    window._aiFreshlyProcessedIds = window._aiFreshlyProcessedIds || [];

    function applyFreshRowHighlight() {
        if (!window._aiFreshlyProcessedIds.length) return;
        window._aiFreshlyProcessedIds.forEach(function (id) {
            var $row = $('#table-exitclearance-form tbody tr[data-compliance-id="' + id + '"]');
            if ($row.length) { $row.addClass('ai-fresh-row'); }
        });
    }

    // ─── AI action buttons ──────────────────────────────────────────────
    // ONE batch of 15 rows per click. Picks never-enriched rows first,
    // then the oldest enrichments (rotation), so the same row never
    // gets re-processed until everything else has had its turn. Each
    // click is a single round-trip (~15-25s) — no recursive auto-paging.
    function runAiRegenerate() {
        var $b   = $('#ai-regenerate-btn');
        var $tbl = $('#table-exitclearance-form').DataTable
            ? $('#table-exitclearance-form').DataTable()
            : null;
        var orig = $b.html();
        $b.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i>Regenerating 15…');

        $.ajax({
            url: '{{ route("people.compliance.regenerateAi") }}',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}', limit: 15 },
            success: function (resp) {
                if (!resp || !resp.success) {
                    toastr.error((resp && resp.message) || 'AI regeneration failed.', 'Error', { positionClass: 'toast-bottom-right' });
                    return;
                }
                window._aiFreshlyProcessedIds = Array.isArray(resp.processed_ids) ? resp.processed_ids.slice() : [];

                if ($tbl) {
                    $tbl.page(0);
                    $tbl.ajax.reload(null, false);
                }

                var enriched = resp.enriched      || 0;
                var failed   = resp.failed        || 0;
                var remain   = resp.remaining_eligible;

                var summary = 'Regenerated AI for ' + enriched + ' row' + (enriched === 1 ? '' : 's');
                if (failed)            summary += ' (' + failed + ' failed — see logs)';
                if (typeof remain === 'number' && remain > 0) {
                    summary += '. ' + remain + ' more row' + (remain === 1 ? '' : 's') + ' eligible — click Regenerate again for the next batch.';
                } else if (typeof remain === 'number' && remain === 0) {
                    summary += '. All breached rows have fresh AI insights.';
                }
                toastr.success(summary, 'AI regeneration complete', { positionClass: 'toast-bottom-right', timeOut: 8000 });

                setTimeout(function () { window._aiFreshlyProcessedIds = []; }, 12000);
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'AI regeneration failed.';
                toastr.error(msg, 'Error', { positionClass: 'toast-bottom-right' });
            },
            complete: function () { $b.prop('disabled', false).html(orig); }
        });
    }

    // Run the AI-only anomaly scan (called after modal confirmation).
    function runAiAnomalyScan() {
        var $b = $('#ai-anomaly-scan-btn');
        var orig = $b.html();
        $b.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i>Scanning… (~60s)');
        $.ajax({
            url: '{{ route("people.compliance.anomalyScan") }}',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function (resp) {
                if (resp && resp.success) {
                    toastr.success(resp.message, 'AI scan complete', { positionClass: 'toast-bottom-right', timeOut: 8000 });
                    window._aiFreshlyProcessedIds = Array.isArray(resp.processed_ids) ? resp.processed_ids.slice() : [];
                    var $tbl = $('#table-exitclearance-form').DataTable();
                    if ($tbl) {
                        $tbl.page(0);
                        $tbl.ajax.reload(null, false);
                    }
                    setTimeout(function () { window._aiFreshlyProcessedIds = []; }, 12000);
                } else {
                    toastr.error((resp && resp.message) || 'AI scan failed.', 'Error', { positionClass: 'toast-bottom-right' });
                }
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'AI scan failed.';
                toastr.error(msg, 'Error', { positionClass: 'toast-bottom-right' });
            },
            complete: function () { $b.prop('disabled', false).html(orig); }
        });
    }

    $(document).on('click', '#ai-regenerate-btn', function () {
        $('#ai-confirm-modal-title').text('Regenerate AI insights?');
        $('#ai-confirm-modal-body').html(
            'This will re-run the AI explanation and remediation on the <strong>next batch of 15 compliance rows</strong>.<br><br>' +
            'Rows that have never been AI-enriched are picked first, then the oldest AI-enriched rows. ' +
            'Click again to process the next batch — there is no auto-paging.<br><br>' +
            'Existing AI text on the processed rows is overwritten. The original deterministic description (and severity) is unaffected. ' +
            'Typical run time: 15–25 seconds.'
        );
        $('#ai-confirm-modal-confirm').text('Yes, regenerate next 15')
            .removeClass('eb-btn-primary').addClass('eb-btn-accent')
            .data('action', 'regenerate');
        var modal = new bootstrap.Modal(document.getElementById('ai-confirm-modal'));
        modal.show();
    });

    $(document).on('click', '#ai-anomaly-scan-btn', function () {
        $('#ai-confirm-modal-title').text('Run AI anomaly scan?');
        $('#ai-confirm-modal-body').html(
            'This sends a resort-level snapshot (per-position salary distribution, per-department headcount + expat ratio, probation cohort) to the AI service ' +
            'and files each employee-specific anomaly as a new compliance row under <strong>"AI Anomaly Detection"</strong>.<br><br>' +
            'New rows appear at the <strong>top of the list with the green NEW AI pill</strong> (same as Regenerate). ' +
            'Resort-level findings (department-wide patterns) are logged separately but not filed as rows — they have no specific employee to display.<br><br>' +
            'Typical run time: <strong>up to 60 seconds</strong>. Up to 20 anomalies per scan. Existing rows are NOT modified.'
        );
        $('#ai-confirm-modal-confirm').text('Yes, run scan')
            .removeClass('eb-btn-accent').addClass('eb-btn-primary')
            .data('action', 'scan');
        var modal = new bootstrap.Modal(document.getElementById('ai-confirm-modal'));
        modal.show();
    });

    $(document).on('click', '#ai-confirm-modal-confirm', function () {
        var action = $(this).data('action');
        var modalEl = document.getElementById('ai-confirm-modal');
        var modal   = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
        if (action === 'regenerate')   runAiRegenerate();
        else if (action === 'scan')    runAiAnomalyScan();
    });
    </script>
    @endsection
