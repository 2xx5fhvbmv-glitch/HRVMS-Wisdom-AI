{{--
    People Relation dashboard — shared presentation-only CSS. Included once
    by hrdashboard.blade.php (the one view rendered by HR_Dashobard(),
    Hod_dashboard() and excom_dashboard()/whichever role routes map to it —
    all three share this exact template, so this partial is universal by
    construction, not a per-role fork).

    Font sizes follow DASHBOARD_FONT_SIZES.md, matched to the Incident
    dashboard's already-fixed values (32 / 22 / 18 / 14 / 11 / 10.5, weight
    never above 600). Colors resolve to the app's real global tokens
    (resources/views/resorts/layouts/_design_tokens.blade.php) — brass is
    scoped locally under .dbg-wrap exactly like Incident's --dbi-c4, since
    it isn't a global token. No [data-theme] rules — the theme engine is
    disabled; do not reintroduce it here.
--}}
<style>
    .dbg-wrap{ --dbg-brass:#B08D57; }

    /* ---- Tier label + hairline divider ---- */
    .dbg-tier{
        display:flex; align-items:baseline; gap:10px;
        margin:28px 0 12px;
    }
    .dbg-tier:first-of-type{ margin-top:20px; }
    .dbg-tier .dbg-tl{
        font-size:11px; font-weight:600; letter-spacing:.04em; text-transform:uppercase;
        color:var(--teal); white-space:nowrap;
    }
    .dbg-tier .dbg-ts{
        font-size:14px; font-weight:500; color:var(--faint); white-space:nowrap;
    }
    .dbg-tier::after{
        content:""; flex:1 1 auto; height:1px; background:var(--line);
    }

    /* ---- KPI capsules ---- */
    .dbg-kpi{
        background:var(--card); border:1px solid var(--line); border-radius:16px;
        padding:18px; height:100%;
    }
    .dbg-kpi-top{ display:flex; align-items:flex-start; justify-content:space-between; gap:8px; }
    .dbg-kpi-num{ font-size:32px; font-weight:600; color:var(--ink); line-height:1.2; }
    .dbg-kpi-lbl{ font-size:14px; font-weight:500; color:var(--muted); margin-top:2px; }
    .dbg-kpi-go{ flex-shrink:0; opacity:.7; transition:opacity .15s ease, transform .15s ease; }
    .dbg-kpi-go:hover{ opacity:1; transform:translateX(2px); }
    .dbg-kpi-go img{ width:20px; height:20px; }
    .dbg-kpi-split{ margin-top:12px; font-size:14px; font-weight:600; }
    .dbg-kpi-split a{ color:var(--teal); text-decoration:none; }
    .dbg-kpi-split a:hover{ text-decoration:underline; }
    .dbg-kpi-split .dbg-dot{ color:var(--faint); margin:0 6px; font-weight:400; }
    .dbg-kpi-split .dbg-muted{ color:var(--muted); font-weight:500; }

    /* ---- Generic card chrome shared by the redesigned tier cards ---- */
    .dbg-card{ background:var(--card); border:1px solid var(--line); border-radius:16px; padding:18px; height:100%; }
    .dbg-card-h{ display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:14px; }
    .dbg-card-h .dbg-ttl{ font-size:18px; font-weight:600; color:var(--ink); margin:0; }
    .dbg-viewall{ font-size:14px; font-weight:600; color:var(--teal); text-decoration:none; white-space:nowrap; }
    .dbg-viewall:hover{ text-decoration:underline; }

    /* ---- Case Timelines ---- */
    .dbg-tl-row{ padding:12px 0; border-bottom:1px solid var(--line-2); }
    .dbg-tl-row:last-child{ border-bottom:none; padding-bottom:0; }
    .dbg-tl-row:first-child{ padding-top:0; }
    .dbg-tl-top{ display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:8px; }
    .dbg-tl-cat{ font-size:14px; font-weight:600; color:var(--ink); }
    .dbg-tl-badge{
        font-size:10.5px; font-weight:600; text-transform:uppercase; letter-spacing:.03em;
        padding:2px 8px; border-radius:20px; white-space:nowrap;
        color:var(--error); background:var(--error-bg);
    }
    .dbg-tl-dates{ display:flex; justify-content:space-between; margin-top:6px; font-size:10.5px; color:var(--faint); }
    .dbg-tl-dates span{ font-weight:600; color:var(--muted); }

    /* ---- WAI Insight SLA row — error/alert icon override for an active SLA breach ---- */
    .wai-narrative .wai-row-icon.is-critical{ background:var(--error-bg); color:var(--error); }

    /* ---- Mini stat cards (Pending Approvals / Delegated Cases) ---- */
    .dbg-mini{
        background:var(--card); border:1px solid var(--line); border-radius:16px;
        padding:16px 18px; height:100%; display:flex; align-items:center; gap:14px;
    }
    .dbg-mini-ic{
        width:38px; height:38px; border-radius:10px; flex-shrink:0;
        display:flex; align-items:center; justify-content:center;
        background:var(--teal-3); color:var(--teal); font-size:16px;
    }
    .dbg-mini-lbl{ font-size:14px; font-weight:500; color:var(--muted); }
    .dbg-mini-val{ font-size:32px; font-weight:600; color:var(--ink); line-height:1.15; }

    /* ---- Confidential Cases ---- */
    .dbg-stat{ margin-bottom:12px; }
    .dbg-stat:last-child{ margin-bottom:0; }
    .dbg-stat-top{ display:flex; justify-content:space-between; font-size:14px; margin-bottom:6px; }
    .dbg-stat-top .dbg-nm{ font-weight:500; color:var(--muted); }
    .dbg-stat-top .dbg-v{ font-weight:600; color:var(--ink); }
    .dbg-stat-track{ height:8px; border-radius:8px; background:var(--line-2); overflow:hidden; }
    .dbg-stat-fill{ height:100%; border-radius:8px; background:var(--teal); }
    .dbg-stat-fill.dbg-fill-warn{ background:var(--warning); }

    /* ---- Ranked-bar category cards (Grievances / Disciplinary Offences) ---- */
    .dbg-catrow{ padding:10px 0; }
    .dbg-catrow:first-child{ padding-top:0; }
    .dbg-catrow-h{ display:flex; justify-content:space-between; font-size:14px; margin-bottom:6px; gap:8px; }
    .dbg-catrow-h .dbg-nm{ font-weight:500; color:var(--ink); min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .dbg-catrow-h .dbg-val{ font-weight:600; color:var(--muted); flex-shrink:0; font-variant-numeric:tabular-nums; }
    .dbg-catrow-track{ height:7px; border-radius:7px; background:var(--line-2); overflow:hidden; }
    .dbg-catrow-fill{ height:100%; border-radius:7px; background:var(--teal); }
    .dbg-catrow.dbg-catrow-d .dbg-catrow-fill{ background:var(--dbg-brass); }
    .dbg-catrow-other{ padding-top:8px; margin-top:4px; border-top:1px dashed var(--line); font-size:11px; font-weight:600; color:var(--faint); text-transform:uppercase; letter-spacing:.03em; }
    .dbg-cat-empty{ font-size:14px; color:var(--muted); margin:0; }

    /* ---- Donuts (Resolution Rate / Case Mix) — pure CSS conic-gradient ---- */
    .dbg-donut-row{ display:flex; align-items:center; gap:20px; }
    .dbg-donut{
        width:120px; height:120px; border-radius:50%; flex-shrink:0; position:relative;
    }
    .dbg-donut .dbg-hole{
        position:absolute; inset:14px; border-radius:50%; background:var(--card);
        display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center;
    }
    .dbg-donut .dbg-hole b{ font-size:22px; font-weight:600; color:var(--ink); line-height:1.1; }
    .dbg-donut .dbg-hole span{ font-size:10.5px; font-weight:500; color:var(--faint); text-transform:uppercase; letter-spacing:.03em; margin-top:2px; }
    .dbg-legend{ display:flex; flex-direction:column; gap:10px; flex:1 1 auto; min-width:0; }
    .dbg-lg{ display:flex; align-items:center; gap:8px; font-size:14px; }
    .dbg-lg-dot{ width:9px; height:9px; border-radius:50%; flex-shrink:0; }
    .dbg-lg-nm{ color:var(--muted); font-weight:500; }
    .dbg-lg-v{ margin-left:auto; font-weight:600; color:var(--ink); font-variant-numeric:tabular-nums; }
    .dbg-avgline{ margin-top:10px; font-size:11px; color:var(--faint); font-weight:600; text-transform:uppercase; letter-spacing:.03em; }

    @media (max-width: 767px){
        .dbg-tier{ margin-top:22px; }
        .dbg-donut-row{ flex-wrap:wrap; }
    }
</style>
