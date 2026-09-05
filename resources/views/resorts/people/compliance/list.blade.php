@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

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
           Reports / Support / Visa / Grievance and Disciplinary / File
           Management / SOS) — extra breathing room between the hero and the
           KPI row below it, scoped to this page (.page-hedding's own
           margin-bottom is shared by every page's hero). padding-bottom, not
           margin: adjacent sibling margins collapse to the larger of the two
           rather than summing. Below Bootstrap's sm breakpoint the extra
           padding pushes the KPI row's first card into the teal hero curve's
           rounded bottom-left corner (body::before, border-radius 0 0 50px
           50px) — same collision found on Payroll — neutralized below 576px. */
        #compliance-hero { padding-bottom: 40px; }
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
                              <a class="btn eb-btn-hero" href="{{route('people.compliance.run')}}">Run Compliance Check Now</a>
                              <button type="button" class="btn eb-btn-accent cc-ai-btn" id="ai-anomaly-scan-btn" title="Run an AI-only anomaly scan that catches issues the rule-based engine can't (salary outliers, ratio drifts, etc.)">
                                  @include('resorts.renderfiles.ai_spark', ['class' => 'me-1'])Run AI Anomaly Scan
                              </button>
                              <button type="button" class="btn eb-btn-accent" id="ai-regenerate-btn" title="Re-run the AI explanation + remediation on the next batch of 15 breached compliance rows. Picks never-enriched rows first, then the oldest enrichments. Click again for the next batch.">
                                  <i class="fa-solid fa-rotate me-1"></i>Regenerate AI
                              </button>
                         </div>
                    </div>
               </div>

            {{-- ===== KPI row — same 4 real counts ComplianceController::index()
                 already computes (critical / employees / rules / modules). The
                 reference mockup's own labels ("Rules breached / Employees
                 impacted / Departments / Locations") don't all map to real
                 data here — no department or location count is computed for
                 this screen — so the existing, honest labels are kept and
                 just restyled into the capsule layout. ===== --}}
            <div class="cc-kpis">
                <div class="cc-kpi cc-kpi--alarm">
                    <div class="cc-kicon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg></div>
                    <div class="cc-kn tnum">{{ $complianceSummary['critical'] }}</div><div class="cc-kl">Critical breaches</div>
                </div>
                <div class="cc-kpi">
                    <div class="cc-kicon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/></svg></div>
                    <div class="cc-kn tnum">{{ $complianceSummary['employees'] }}</div><div class="cc-kl">Employees affected</div>
                </div>
                <div class="cc-kpi">
                    <div class="cc-kicon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18h6M10 22h4M12 2a7 7 0 00-4 12.7c.6.5 1 1.3 1 2.3h6c0-1 .4-1.8 1-2.3A7 7 0 0012 2z"/></svg></div>
                    <div class="cc-kn tnum">{{ $complianceSummary['rules'] }}</div><div class="cc-kl">Rules breached</div>
                </div>
                <div class="cc-kpi">
                    <div class="cc-kicon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M6 21V7l6-4 6 4v14M9 9h.01M9 13h.01M9 17h.01"/></svg></div>
                    <div class="cc-kn tnum">{{ $complianceSummary['modules'] }}</div><div class="cc-kl">Modules involved</div>
                </div>
            </div>

            <div>
                <div class="card cc-table-card">
                    <div class="card-header cc-controls">
                        <div class="cc-searchrow">
                            <div class="input-group cc-search">
                                <input type="search" class="form-control search" placeholder="Search by employee, rule, description&hellip;" />
                                <i class="fa-solid fa-search"></i>
                            </div>

                            {{-- Severity segmented control. All/Critical are bound to
                                 real aggregate counts already computed in index();
                                 High/Medium show no count — that per-severity
                                 breakdown isn't computed server-side today and
                                 adding it would mean a new query, out of scope for
                                 a presentation-only pass. --}}
                            <div class="seg" id="cc-sevseg">
                                <button type="button" class="seg-btn active" data-f="" onclick="setSev(this)">All <span class="c tnum">{{ $complianceChips['all'] }}</span></button>
                                <button type="button" class="seg-btn" data-f="Critical" onclick="setSev(this)">Critical <span class="c tnum">{{ $complianceChips['critical'] }}</span></button>
                                <button type="button" class="seg-btn" data-f="High" onclick="setSev(this)">High</button>
                                <button type="button" class="seg-btn" data-f="Medium" onclick="setSev(this)">Medium</button>
                            </div>

                            {{-- Category filter popover — replaces the old flat
                                 chip row (12+ mixed rule/module chips). Real data,
                                 same source as the old chips (complianceChips).
                                 Note: only ONE category (a single module OR a
                                 single rule) can be the live server filter at a
                                 time — list() filters by a single module_name /
                                 compliance_breached_name equality, not an IN(...)
                                 list, and that's out of scope to change here. The
                                 checklist still lets you browse/search all
                                 categories; picking a new one swaps the active
                                 one, same capability as the old chip row just
                                 decluttered, searchable, and grouped. --}}
                            <div class="ctl" id="cc-cat-ctl">
                                <button type="button" class="trigger" onclick="catToggleOpen(event)">
                                    <svg class="funnel" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 3H2l8 9.5V19l4 2v-8.5z"/></svg>
                                    Category<span class="badge" id="cc-cat-badge" style="display:none">0</span>
                                    <svg class="chev" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="menu">
                                    <div class="c-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input id="cc-cat-q" placeholder="Find a category&hellip;" oninput="catFilterList()"></div>
                                    <div id="cc-cat-groups"></div>
                                    <div class="c-foot"><button type="button" class="clr" onclick="catClear()">Clear</button><button type="button" class="c-apply" onclick="catToggleOpen()">Done</button></div>
                                </div>
                            </div>

                            <a id="compliance-download-btn" href="{{route('people.compliance.download')}}" class="btn eb-btn-secondary">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                                Download
                            </a>
                        </div>
                        <div class="chips-live" id="cc-cat-chips"></div>
                    </div>
                    <div class="cc-table-scroll">
                    <table id="table-exitclearance-form" class="table table-exitclearance-form w-100">
                        <thead>
                            <tr>
                                <th class="num">No</th>
                                <th>Rule breached</th>
                                <th>Employee</th>
                                <th>Description</th>
                                <th>Reported</th>
                                <th>Status</th>
                                <th>Action</th>
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

    @include('resorts.partials._wai_suggestions_popup')
    @include('resorts._emotional_buttons_v2_styles')
@endsection

    @section('import-css')
    <style>
        /* Neutral/geometry tokens come from the shared :root palette
           (resorts/layouts/_design_tokens.blade.php) — .compliance-page
           inherits --teal/--teal-2/--teal-3/--teal-soft/--lime/--ink/
           --muted/--faint/--line/--line-2/--card/--shadow from there. */
        .tnum { font-variant-numeric: tabular-nums; }

        /* ---- KPI row ---- */
        .compliance-page .cc-kpis {
            display: grid; grid-template-columns: repeat(4, minmax(0,1fr));
            gap: 16px; margin-bottom: 16px;
        }
        @media (max-width: 900px) { .compliance-page .cc-kpis { grid-template-columns: repeat(2,1fr); } }
        .compliance-page .cc-kpi {
            background: var(--card); border: 1px solid var(--line); border-radius: 16px;
            box-shadow: var(--shadow); padding: 20px 22px; position: relative;
        }
        .compliance-page .cc-kn { font-size: 32px; font-weight: 600; line-height: 1; letter-spacing: -1px; color: var(--ink); }
        .compliance-page .cc-kpi--alarm .cc-kn { color: var(--critical); }
        .compliance-page .cc-kl { font-size: 12.5px; color: var(--muted); margin-top: 8px; }
        .compliance-page .cc-kicon {
            position: absolute; top: 18px; right: 18px; width: 34px; height: 34px;
            border-radius: 10px; display: grid; place-items: center;
            background: var(--line-2); color: var(--faint);
        }
        .compliance-page .cc-kpi--alarm .cc-kicon { background: var(--critical-bg); color: var(--critical); }

        /* ---- Controls (search + severity + category + download) ---- */
        .compliance-page .cc-controls { padding-bottom: 20px; }
        .compliance-page .cc-searchrow { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        .compliance-page .cc-search { flex: 1 1 220px; min-width: 200px; }
        .compliance-page .cc-search .form-control.search { background: #fff; border: 1px solid var(--line); border-radius: 11px 0 0 11px; }
        .compliance-page .cc-search .fa-search { border: 1px solid var(--line); border-left: none; border-radius: 0 11px 11px 0; background: #fff; color: var(--faint); }

        .compliance-page .seg { display: inline-flex; background: var(--line-2); border-radius: 11px; padding: 4px; gap: 2px; }
        .compliance-page .seg-btn {
            font-family: inherit; font-size: 13px; font-weight: 500; color: var(--muted);
            background: transparent; border: none; border-radius: 8px; padding: 8px 14px;
            cursor: pointer; display: inline-flex; align-items: center; gap: 7px; white-space: nowrap;
        }
        .compliance-page .seg-btn .c { font-size: 11px; font-weight: 600; background: #fff; color: var(--muted); border-radius: 12px; padding: 1px 7px; }
        .compliance-page .seg-btn.active { background: #fff; color: var(--ink); box-shadow: var(--shadow); }
        .compliance-page .seg-btn.active[data-f="Critical"] { color: var(--critical); }
        .compliance-page .seg-btn.active[data-f="Critical"] .c { background: var(--critical-bg); color: var(--critical); }
        .compliance-page .seg-btn.active[data-f="High"] { color: #B7791F; }
        .compliance-page .seg-btn.active[data-f="Medium"] { color: #7C9DA3; }

        /* Category filter popover */
        .compliance-page .ctl { position: relative; }
        .compliance-page .ctl .trigger {
            font-family: inherit; font-size: 13.5px; font-weight: 500; color: var(--ink);
            background: #fff; border: 1px solid var(--line); border-radius: 11px; padding: 10px 14px;
            cursor: pointer; display: inline-flex; align-items: center; gap: 9px; white-space: nowrap;
            transition: border-color .15s;
        }
        .compliance-page .ctl .trigger:hover { border-color: var(--faint); }
        .compliance-page .ctl .trigger .chev { color: var(--faint); transition: transform .18s; }
        .compliance-page .ctl .trigger .funnel { color: var(--muted); }
        .compliance-page .ctl .trigger .badge { background: var(--teal); color: #fff; font-size: 11px; font-weight: 600; border-radius: 20px; padding: 1px 8px; }
        .compliance-page .ctl.open .trigger { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(1,70,83,.08); }
        .compliance-page .ctl.open .trigger .chev { transform: rotate(180deg); }
        .compliance-page .ctl .menu {
            position: absolute; top: calc(100% + 8px); right: 0; background: #fff;
            border: 1px solid var(--line); border-radius: 14px;
            box-shadow: 0 16px 40px rgba(1,70,83,.16), 0 2px 8px rgba(1,70,83,.08);
            padding: 10px; min-width: 290px; z-index: 30;
            opacity: 0; visibility: hidden; transform: translateY(-4px); transition: opacity .15s, transform .15s;
        }
        .compliance-page .ctl.open .menu { opacity: 1; visibility: visible; transform: none; }
        .compliance-page .c-search { position: relative; margin-bottom: 8px; }
        .compliance-page .c-search svg { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--faint); }
        .compliance-page .c-search input { width: 100%; font-family: inherit; font-size: 13px; border: 1px solid var(--line); border-radius: 9px; padding: 8px 11px 8px 32px; outline: none; }
        .compliance-page .c-search input:focus { border-color: var(--teal); }
        .compliance-page .grp-h { font-size: 10px; font-weight: 600; letter-spacing: .5px; text-transform: uppercase; color: var(--faint); padding: 8px 8px 5px; }
        .compliance-page .ci { display: flex; align-items: center; gap: 10px; padding: 8px 9px; border-radius: 8px; cursor: pointer; font-size: 13.5px; color: var(--ink); }
        .compliance-page .ci:hover { background: var(--teal-soft); }
        .compliance-page .ci .box { width: 17px; height: 17px; border-radius: 5px; border: 1.5px solid var(--line); flex: none; display: grid; place-items: center; color: #fff; transition: background .12s, border-color .12s; }
        .compliance-page .ci.on .box { background: var(--teal); border-color: var(--teal); }
        .compliance-page .ci .box svg { opacity: 0; } .compliance-page .ci.on .box svg { opacity: 1; }
        .compliance-page .ci .nm { flex: 1; } .compliance-page .ci .ct { font-size: 12px; color: var(--faint); font-variant-numeric: tabular-nums; }
        .compliance-page .c-foot { display: flex; justify-content: space-between; align-items: center; padding: 8px 8px 4px; margin-top: 6px; border-top: 1px solid var(--line-2); }
        .compliance-page .c-foot .clr { font-size: 12.5px; font-weight: 500; color: var(--muted); background: none; border: none; cursor: pointer; }
        .compliance-page .c-foot .clr:hover { color: var(--critical); }
        .compliance-page .c-apply { font-family: inherit; font-size: 12.5px; font-weight: 600; background: var(--teal); color: #fff; border: none; border-radius: 8px; padding: 7px 14px; cursor: pointer; }
        .compliance-page .chips-live { display: flex; flex-wrap: wrap; gap: 7px; align-items: center; margin-top: 14px; }
        .compliance-page .chips-live:empty { display: none; }
        .compliance-page .lchip { display: inline-flex; align-items: center; gap: 7px; font-size: 12.5px; font-weight: 500; color: var(--teal); background: var(--teal-3); border-radius: 20px; padding: 5px 6px 5px 12px; }
        .compliance-page .lchip button { border: none; background: rgba(1,70,83,.12); color: var(--teal); width: 17px; height: 17px; border-radius: 50%; cursor: pointer; display: grid; place-items: center; font-size: 11px; line-height: 1; }
        .compliance-page .clr-all { font-size: 12.5px; font-weight: 500; color: var(--muted); background: none; border: none; cursor: pointer; }
        .compliance-page .clr-all:hover { color: var(--critical); }

        /* ---- Table shell ---- */
        .compliance-page .cc-table-card { border-radius: 14px; overflow: hidden; }
        .compliance-page .cc-table-scroll { overflow-x: auto; }
        #table-exitclearance-form thead th,
        .compliance-page .dataTables_scrollHead th {
            background: var(--teal-soft) !important;
            color: var(--ink);
            text-transform: uppercase;
            font-size: 10.5px;
            letter-spacing: .5px;
            font-weight: 600;
            padding: 14px 16px !important;
        }
        #table-exitclearance-form thead th.num { width: 44px; }
        #table-exitclearance-form tbody tr { background: #fff; border-bottom: 1px solid var(--line-2); }
        #table-exitclearance-form tbody tr:hover { background: var(--teal-soft); }
        #table-exitclearance-form tbody td { font-size: 13.5px; vertical-align: middle; }
        #table-exitclearance-form tbody td:first-child { color: var(--faint); font-variant-numeric: tabular-nums; white-space: nowrap; }

        /* ---- Rule breached cell: severity dot + name + "Severity · Module" ---- */
        .rule { display: flex; align-items: center; gap: 10px; }
        .sevdot { width: 8px; height: 8px; border-radius: 50%; flex: none; }
        .rname { font-weight: 600; color: var(--ink); }
        .rmod { font-size: 11.5px; color: var(--muted); margin-top: 2px; }
        .sevword { font-weight: 600; }

        /* ---- Employee cell ---- */
        .cc-emp { display: flex; align-items: flex-start; gap: 10px; }
        .cc-emp-avatar {
            flex: 0 0 auto; width: 32px; height: 32px; border-radius: 50%;
            background: var(--teal-soft); border: 1px solid var(--line); color: var(--teal);
            font-size: 11px; font-weight: 700; display: flex; align-items: center; justify-content: center; overflow: hidden;
        }
        .cc-emp-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .cc-emp-info { min-width: 0; }
        .cc-emp-name { font-weight: 700; color: var(--ink); }
        .cc-emp-role { font-size: 11.5px; color: var(--faint); margin-top: 1px; }
        .cc-emp-id { font-variant-numeric: tabular-nums; }

        /* ---- Description cell: two-line clamp + AI suggested fix trigger ---- */
        .descell { max-width: 340px; }
        .desc-r { color: var(--ink); font-size: 13.5px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.45; }
        .fixhint {
            display: inline-flex; align-items: center; gap: 6px; margin-top: 6px;
            font-family: inherit; font-size: 11.5px; font-weight: 500; color: var(--teal);
            background: var(--teal-3); border: none; border-radius: 14px; padding: 4px 11px;
            cursor: pointer; transition: background .15s;
        }
        .fixhint:hover { background: #dcebec; }
        .fixhint .fx { color: var(--teal); opacity: .55; }

        /* ---- Badges (status only — severity no longer uses a pill here) ---- */
        .cc-badge { display: inline-block; font-size: 11px; font-weight: 700; letter-spacing: .2px; padding: 3px 10px; border-radius: 20px; }
        .cc-badge--warn { background: #fff6e5; color: var(--warning); }
        .cc-badge--ok   { background: var(--positive-bg); color: var(--positive); }

        /* ---- Action cell ---- */
        .cc-actions { display: flex; align-items: center; gap: 8px; }
        .cc-btn { display: inline-block; font-size: 12.5px; font-weight: 700; padding: 6px 14px; border-radius: 11px; cursor: pointer; text-align: center; }
        .cc-btn--outline { background: #fff; border: 1px solid var(--line); color: var(--muted); }
        .cc-btn--outline:hover { border-color: var(--teal-2); color: var(--teal-2); }
        .cc-btn--outline.disabled { color: var(--faint); cursor: default; }
        .cc-btn--outline.disabled:hover { border-color: var(--line); color: var(--faint); }

        .emptyrow td { padding: 40px; text-align: center; color: var(--faint); font-size: 14px; }

        /* Single visual marker for freshly AI-regenerated rows: a small
           "NEW AI" pill in the first cell. Class `ai-fresh-row` is set
           by the backend on any row whose ai_generated_at is < 30 min
           old (see ComplianceController::list setRowClass) so the pill
           persists across pagination, refresh, and search-induced
           redraws — not just the first render. */
        #table-exitclearance-form tbody tr.ai-fresh-row > td:first-child::before {
            content: "NEW AI";
            display: inline-block;
            background-color: #16a34a;
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.4px;
            padding: 2px 7px;
            border-radius: 10px;
            margin-right: 8px;
            vertical-align: middle;
            box-shadow: 0 1px 3px rgba(22, 163, 74, 0.35);
        }

        /* ---- Shared AI sparkle glyph ---- */
        .ai-spark { width: 14px; height: 14px; flex-shrink: 0; vertical-align: -2px; animation: aiTwinkle 2.4s ease-in-out infinite; }
        @media (prefers-reduced-motion: reduce) { .ai-spark { animation: none; } }
        @keyframes aiTwinkle { 0%, 100% { opacity: .85; } 50% { opacity: 1; } }
        .cc-ai-btn .ai-spark { color: var(--lime); }
    </style>
    @endsection

    @section('import-scripts')

    <script type="text/javascript">
     $(document).ready(function() {
          updateDownloadUrl();

          catGroups(); // build the category popover's checklist once — real data, no server round-trip

          ComplianceIndex();
          $('.search').on('keyup', function() {
               ComplianceIndex();
               updateDownloadUrl();
          });

          function updateDownloadUrl() {
               var searchTerm = $('.search').val();
               var downloadUrl = "{{route('people.compliance.download')}}?searchTerm=" + searchTerm;
               $('#compliance-download-btn').attr('href', downloadUrl);
          }
          window.updateDownloadUrl = updateDownloadUrl;

          $(document).on('click', '.dismmisal', function(e) {
               e.preventDefault();
               e.stopPropagation();
               var complianceId = $(this).data('id');
               wisdomConfirm({
                    role: 'confirm',
                    title: 'Are you sure?',
                    text: "You want to dismiss this compliance?",
                    confirmText: 'Yes, dismiss it!'
               }).then((result) => {
                    if (result.isConfirmed) {
                         $.ajax({
                              url: "{{ route('people.compliances.dismiss', '') }}/" + complianceId,
                              type: 'GET',
                              success: function(response) {
                                   wisdomAlert({
                                       type: 'success',
                                       title: 'Dismissed!',
                                       text: 'Compliance has been dismissed.'
                                   });
                                  ComplianceIndex();
                              },
                              error: function(xhr, status, error) {
                                   wisdomAlert({
                                       type: 'error',
                                       title: 'Error!',
                                       text: 'Something went wrong. Please try again.'
                                   });
                              }
                         });
                    }
               });
          });
     });

     var viewForm;
     // Active filter state — persists across ComplianceIndex() rebuilds.
     // severity: '' | 'Critical' | 'High' | 'Medium'
     // catField: '' | 'module_name' | 'compliance_breached_name'  (which param the active category maps to)
     // catValue: the selected module/rule name, '' when none selected
     // NOTE: only one category can be the live server filter at a time — list()
     // filters module_name / compliance_breached_name by equality, not IN(...),
     // and extending that is a backend change out of scope for this pass.
     // Severity and the one active category DO combine (both are independent
     // optional `where()` clauses server-side already).
     var complianceFilter = { severity: '', catField: '', catValue: '' };

     function ComplianceIndex()
     {
          if ($.fn.DataTable.isDataTable('#table-exitclearance-form')) {
               viewForm.destroy();
          }
          $('#table-exitclearance-form tbody').empty();
          viewForm = $('#table-exitclearance-form').DataTable({
               searching: false,
               bLengthChange: false,
               bFilter: true,
               bInfo: true,
               bAutoWidth: false,
               scrollX: true,
               iDisplayLength: 10,
               processing: true,
               serverSide: true,
               order: [],
               ajax: {
                    url: '{{ route("people.compliance.list") }}',
                    type: 'GET',
                    data: function(d) {
                         d.searchTerm = $('.search').val();
                         if (complianceFilter.severity) { d.severity = complianceFilter.severity; }
                         if (complianceFilter.catField) { d[complianceFilter.catField] = complianceFilter.catValue; }
                    }
               },
               columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'compliance_breached_name', name: 'compliance_breached_name', render: function(data, type, row) { return type === 'display' ? ruleCellHtml(row) : data; } },
                    { data: 'employee_name', name: 'employee_name', render: function(data, type, row) { return type === 'display' ? empCellHtml(row) : data; } },
                    { data: 'description', name: 'description', className: 'descell', render: function(data, type, row) { return type === 'display' ? descCellHtml(row) : data; } },
                    { data: 'reported_on', name: 'reported_on', render: function(data, type, row) { return type === 'display' ? reportedCellHtml(data) : data; } },
                    { data: 'status', name: 'status' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                    { data: 'module_name', name: 'module_name', visible: false, searchable: false },
                    { data: 'employee_id', name: 'employee_id', visible: false, searchable: false },
                    { data: 'created_at', visible: false, searchable: false },
               ],
               drawCallback: function () { applyFreshRowHighlight(); }
          });
     }

    // ─── Cell renderers — build the 3 merged/restyled columns from the
    // SAME row data the server already returns (raw model attributes are
    // present on every row alongside the rendered HTML columns — verified
    // against the live list() response). No backend change, no new
    // request: module_name / employee_id / severity_ai / remediation_ai
    // simply move from their own columns into these three. ──────────────

    var SEV_COLORS = { critical: '#E5573F', high: '#B7791F', medium: '#7C9DA3' };

    function escHtml(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function extractRuleName(html) {
        var m = /<div class="cc-rule-name">([\s\S]*?)<\/div>/.exec(html || '');
        return m ? m[1] : '';
    }

    function ruleCellHtml(row) {
        var name = extractRuleName(row.compliance_breached_name);
        var sev = (row.severity_ai || '').trim();
        var color = SEV_COLORS[sev.toLowerCase()] || 'var(--faint)';
        var mod = row.module_name || '';
        var sub = [];
        if (sev) { sub.push('<span class="sevword" style="color:' + color + '">' + escHtml(sev) + '</span>'); }
        if (mod) { sub.push(escHtml(mod)); }
        return '<div class="rule">'
             + '<span class="sevdot" style="background:' + color + '"></span>'
             + '<div><div class="rname">' + name + '</div>'
             + (sub.length ? '<div class="rmod">' + sub.join(' &middot; ') + '</div>' : '')
             + '</div></div>';
    }

    function empCellHtml(row) {
        var html = row.employee_name || '';
        var id = row.employee_id != null ? String(row.employee_id) : '';
        if (!id || id === '-') { return html; }
        if (/<div class="cc-emp-role">/.test(html)) {
            return html.replace(/(<div class="cc-emp-role">[\s\S]*?)<\/div>/, '$1 &middot; <span class="cc-emp-id">' + escHtml(id) + '</span></div>');
        }
        return html.replace(/(<div class="cc-emp-name">[\s\S]*?<\/div>)/, '$1<div class="cc-emp-role"><span class="cc-emp-id">' + escHtml(id) + '</span></div>');
    }

    function descCellHtml(row) {
        var raw = row.description || '';
        var m = /<div class="cc-desc-text">([\s\S]*?)<\/div>/.exec(raw);
        var innerHtml = m ? m[1] : escHtml(raw);
        var plain = innerHtml.replace(/<[^>]+>/g, '').trim();
        var fixText = row.remediation_ai ? String(row.remediation_ai).trim() : '';
        var btn = '';
        if (fixText) {
            var rule = extractRuleName(row.compliance_breached_name).replace(/<[^>]+>/g, '');
            btn = '<button type="button" class="fixhint" data-rule="' + escHtml(rule) + '" data-fix="' + escHtml(fixText) + '" onclick="openFixPopup(event,this)">'
                + '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18h6M10 22h4M12 2a7 7 0 00-4 12.7c.6.5 1 1.3 1 2.3h6c0-1 .4-1.8 1-2.3A7 7 0 0012 2z"/></svg>AI suggested fix<span class="fx">&rsaquo;</span></button>';
        }
        return '<div class="desc-r" title="' + escHtml(plain) + '">' + innerHtml + '</div>' + btn;
    }

    // Backend renders reported_on as "09 Jun 2026" (Carbon's 'd M Y') —
    // already day/month-abbreviation/year in the right order, just
    // space-separated. Swap to hyphens for the DD-Mon-YYYY display spec;
    // output-only, no change to the stored value or the format Carbon uses.
    function reportedCellHtml(html) {
        return (html || '').replace(/<div>(\d{2}) (\w{3}) (\d{4})<\/div>/, '<div>$1-$2-$3</div>');
    }

    function openFixPopup(ev, el) {
        if (ev) { ev.stopPropagation(); }
        waiSuggestOpen(el.getAttribute('data-rule'), el.getAttribute('data-fix'));
    }

    // IDs of compliance rows freshly AI-regenerated in the most recent
    // batch. Set in the AJAX success handler, consumed by the DataTable
    // drawCallback below to flash the rows green for ~10 s after the
    // table reload. Cleared after the flash so a refresh later doesn't
    // re-highlight stale rows.
    window._aiFreshlyProcessedIds = window._aiFreshlyProcessedIds || [];

    function applyFreshRowHighlight() {
        if (!window._aiFreshlyProcessedIds.length) return;
        var ids = window._aiFreshlyProcessedIds;
        ids.forEach(function (id) {
            var $row = $('#table-exitclearance-form tbody tr[data-compliance-id="' + id + '"]');
            if ($row.length) { $row.addClass('ai-fresh-row'); }
        });
    }

    // ─── Severity segmented control ──────────────────────────────────────
    function setSev(btn) {
        document.querySelectorAll('#cc-sevseg .seg-btn').forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
        complianceFilter.severity = btn.dataset.f || '';
        ComplianceIndex();
    }

    // ─── Category filter popover (searchable, grouped, single active
    // category — see the note on complianceFilter above for why) ────────
    var catSelection = null; // { field, value, nm } | null
    var CATS = [
        @foreach($complianceChips['modules'] as $moduleName => $cnt)
            { field: 'module_name', value: @json($moduleName), nm: @json($moduleName), c: {{ (int) $cnt }}, grp: 'module' },
        @endforeach
        @foreach($complianceChips['rules'] as $ruleName => $cnt)
            { field: 'compliance_breached_name', value: @json($ruleName), nm: @json($ruleName), c: {{ (int) $cnt }}, grp: 'rule' },
        @endforeach
    ];

    function catToggleOpen(ev) {
        if (ev) { ev.stopPropagation(); }
        var el = document.getElementById('cc-cat-ctl');
        var was = el.classList.contains('open');
        el.classList.toggle('open', !was);
    }
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.ctl')) { document.getElementById('cc-cat-ctl').classList.remove('open'); }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { document.getElementById('cc-cat-ctl').classList.remove('open'); }
    });

    // catItemHtml indexes into CATS by position (data-idx) rather than
    // interpolating field/value/name into an inline onclick string — a
    // JSON.stringify()'d value embedded straight into onclick="..." would
    // use double quotes and break out of the (also double-quoted) HTML
    // attribute. A delegated listener on the rendered index avoids that
    // entirely and needs no escaping gymnastics.
    function catItemHtml(c, idx) {
        var on = catSelection && catSelection.field === c.field && catSelection.value === c.value;
        return '<div class="ci' + (on ? ' on' : '') + '" data-idx="' + idx + '">'
             + '<span class="box"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg></span>'
             + '<span class="nm">' + escHtml(c.nm) + '</span><span class="ct">' + c.c + '</span></div>';
    }
    function catGroups() {
        var byModule = CATS.filter(function (c) { return c.grp === 'module'; });
        var byRule = CATS.filter(function (c) { return c.grp === 'rule'; });
        var html = '';
        if (byModule.length) { html += '<div class="grp-h">By module</div>' + byModule.map(function (c) { return catItemHtml(c, CATS.indexOf(c)); }).join(''); }
        if (byRule.length) { html += '<div class="grp-h">By rule</div>' + byRule.map(function (c) { return catItemHtml(c, CATS.indexOf(c)); }).join(''); }
        document.getElementById('cc-cat-groups').innerHTML = html;
        catFilterList();
    }
    $(document).on('click', '#cc-cat-groups .ci', function () {
        var c = CATS[$(this).data('idx')];
        if (c) { catToggle(c.field, c.value, c.nm); }
    });
    function catToggle(field, value, nm) {
        if (catSelection && catSelection.field === field && catSelection.value === value) {
            catSelection = null;
        } else {
            catSelection = { field: field, value: value, nm: nm };
        }
        catGroups();
        catSync();
        complianceFilter.catField = catSelection ? catSelection.field : '';
        complianceFilter.catValue = catSelection ? catSelection.value : '';
        ComplianceIndex();
    }
    function catClear() {
        catSelection = null;
        catGroups();
        catSync();
        complianceFilter.catField = '';
        complianceFilter.catValue = '';
        ComplianceIndex();
    }
    function catSync() {
        var badge = document.getElementById('cc-cat-badge');
        badge.style.display = catSelection ? '' : 'none';
        badge.textContent = catSelection ? '1' : '0';
        var chips = document.getElementById('cc-cat-chips');
        if (!catSelection) { chips.innerHTML = ''; return; }
        chips.innerHTML = '<span class="lchip">' + escHtml(catSelection.nm) + '<button type="button" onclick="catClear()" aria-label="Remove">&times;</button></span>'
            + '<button type="button" class="clr-all" onclick="catClear()">Clear all</button>';
    }
    function catFilterList() {
        var q = (document.getElementById('cc-cat-q').value || '').toLowerCase();
        document.querySelectorAll('#cc-cat-groups .ci .nm').forEach(function (el) {
            el.closest('.ci').style.display = el.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
        document.querySelectorAll('#cc-cat-groups .grp-h').forEach(function (h) {
            var n = h.nextElementSibling, any = false;
            while (n && n.classList.contains('ci')) { if (n.style.display !== 'none') any = true; n = n.nextElementSibling; }
            h.style.display = any ? '' : 'none';
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
