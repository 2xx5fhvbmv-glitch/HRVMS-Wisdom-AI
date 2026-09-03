{{--
    Grievance Investigation Report — shared presentation-only CSS. Included
    once by investigationreport.blade.php (the single view/route rendered
    for every role that can open this screen — InvestigationReport() has no
    HOD/HR/EXCOM variant; role-specific behaviour is gated inline via
    $isCommitteeMember/$canViewIdentity, not separate views).

    Font sizes follow DASHBOARD_FONT_SIZES.md's production scale, consistent
    with the Disciplinary Investigation screen: 22px case title, 15px card
    titles, 14px body/inputs, 11px uppercase labels, 10.5-12px captions.
    13.5px is used only inside the dense investigation-report panel body,
    an explicitly allowed exception for that one context. No [data-theme]
    rules — the theme engine is disabled; do not reintroduce it.
--}}
<style>
    .gvi-wrap{ --gvi-radius:16px; }

    .gvi-card{ background:var(--card); border:1px solid var(--line); border-radius:var(--gvi-radius); box-shadow:var(--shadow); }
    .gvi-sec-h{ font-size:15px; font-weight:600; color:var(--ink); margin-bottom:16px; }

    /* ---- Hero ---- */
    .gvi-hero{ align-items:stretch; margin-bottom:16px; }
    .gvi-summary{ padding:24px 26px; height:100%; }
    .gvi-title{ font-size:22px; font-weight:600; letter-spacing:-.3px; line-height:1.2; color:var(--ink); margin:0; }
    .gvi-class{ display:flex; align-items:center; gap:8px; font-size:14px; margin-top:10px; flex-wrap:wrap; }
    .gvi-class .cat{ color:var(--teal); font-weight:500; }
    .gvi-class .chev{ color:var(--faint); }
    .gvi-class .sub{ color:var(--muted); }
    .gvi-desc{ margin-top:18px; }
    .gvi-desc .dk{ font-size:10.5px; font-weight:600; letter-spacing:.5px; text-transform:uppercase; color:var(--faint); margin-bottom:5px; }
    .gvi-desc .dv{ font-size:14px; color:var(--ink); line-height:1.55; }
    .gvi-facts{ display:flex; flex-wrap:wrap; gap:16px 26px; margin-top:20px; padding-top:18px; border-top:1px solid var(--line-2); }
    .gvi-fact{ display:flex; flex-direction:column; gap:6px; }
    .gvi-fact .k{ font-size:10.5px; font-weight:600; letter-spacing:.5px; text-transform:uppercase; color:var(--faint); }
    .gvi-fact .pill{ display:inline-flex; align-items:center; gap:7px; font-size:14px; font-weight:500; color:var(--ink); background:var(--line-2); padding:5px 12px; border-radius:20px; }
    .gvi-fact .ref-pill{ background:var(--teal-3); color:var(--teal); font-weight:600; }
    .gvi-fact .lock{ background:var(--warning-bg); color:var(--warning); }
    .gvi-fact .fv{ font-size:14px; font-weight:500; color:var(--ink); }
    .gvi-dot{ width:7px; height:7px; border-radius:50%; flex:none; }

    .gvi-filefact{ display:inline-flex; align-items:center; gap:7px; max-width:210px; font-size:14px; font-weight:500; color:var(--teal); text-decoration:none; }
    .gvi-filefact svg{ color:var(--faint); flex:none; transition:color .15s; }
    .gvi-filefact .fn{ white-space:nowrap; overflow:hidden; text-overflow:ellipsis; min-width:0; }
    .gvi-filefact:hover .fn{ text-decoration:underline; }
    .gvi-filefact:hover svg{ color:var(--teal); }
    .gvi-filemore{ color:var(--teal); font-weight:600; font-size:12.5px; flex:none; }
    .gvi-filenone{ font-size:14px; color:var(--faint); }

    /* ---- Confidentiality-aware rail ---- */
    .gvi-rail{ height:100%; padding:22px 24px; display:flex; flex-direction:column; gap:16px; }
    .gvi-rail .rh{ font-size:11px; font-weight:600; letter-spacing:.7px; text-transform:uppercase; color:var(--teal); }
    .gvi-rail .who{ display:flex; align-items:center; gap:13px; }
    .gvi-av{ width:46px; height:46px; border-radius:50%; flex:none; object-fit:cover; background:var(--teal); color:#fff; display:grid; place-items:center; font-size:15px; font-weight:600; letter-spacing:.3px; }
    .gvi-rail .who .nm{ font-size:16.5px; font-weight:600; color:var(--ink); letter-spacing:-.2px; }
    .gvi-rail .krow{ display:flex; align-items:center; justify-content:space-between; gap:14px; padding:11px 0; border-bottom:1px solid var(--line); }
    .gvi-rail .krow:last-child{ border-bottom:none; padding-bottom:0; }
    .gvi-rail .krow .kk{ font-size:13px; color:var(--muted); flex:none; }
    .gvi-rail .krow .kv{ font-size:14px; font-weight:600; color:var(--ink); text-align:right; word-break:break-word; }
    .gvi-rail .krow .kv.link{ color:var(--teal); font-weight:500; text-decoration:none; }
    .gvi-rail .krow .kv.link:hover{ text-decoration:underline; }

    /* Locked "Confidential" state — replaces the whole rail when the viewer
       can't see identity (server-computed $canViewIdentity, not a client
       toggle). */
    .gvi-rail.locked{ background:var(--teal-soft); justify-content:center; text-align:center; align-items:center; }
    .gvi-rail.locked .rh{ align-self:flex-start; }
    .gvi-lockg{ width:54px; height:54px; border-radius:16px; background:var(--card); border:1px solid var(--line); display:grid; place-items:center; color:var(--warning); }
    .gvi-lockt{ font-size:15px; font-weight:600; color:var(--ink); }
    .gvi-locks{ font-size:12.5px; color:var(--muted); max-width:220px; }

    /* ---- History timeline ---- */
    .gvi-hist{ padding:22px 24px; }
    .gvi-timeline{ position:relative; padding-left:26px; }
    .gvi-timeline::before{ content:""; position:absolute; left:6px; top:6px; bottom:6px; width:2px; background:var(--line); }
    .gvi-hentry{ position:relative; padding-bottom:16px; }
    .gvi-hentry:last-child{ padding-bottom:0; }
    .gvi-hdot{ position:absolute; left:-26px; top:16px; width:14px; height:14px; border-radius:50%; background:var(--card); border:3px solid var(--teal); }
    .gvi-hentry.latest .gvi-hdot{ border-color:var(--positive); box-shadow:0 0 0 4px var(--positive-bg); }
    .gvi-hcard{ border:1px solid var(--line); border-radius:14px; overflow:hidden; }
    .gvi-hhead{ display:flex; align-items:center; gap:12px; padding:13px 16px; flex-wrap:wrap; }
    .gvi-hav{ width:34px; height:34px; border-radius:50%; flex:none; object-fit:cover; background:var(--teal); color:#fff; display:grid; place-items:center; font-size:12px; font-weight:600; }
    .gvi-hwho{ flex:1; min-width:120px; }
    .gvi-hwho .hn{ font-size:14px; font-weight:600; color:var(--ink); }
    .gvi-hwho .hr{ font-size:11.5px; color:var(--muted); }
    .gvi-hpill{ font-size:10.5px; font-weight:600; padding:3px 10px; border-radius:20px; white-space:nowrap; display:inline-flex; align-items:center; gap:5px; }
    .gvi-hpill.stage-init{ background:var(--line-2); color:var(--muted); }
    .gvi-hpill.stage-ongoing{ background:var(--warning-bg); color:var(--warning); }
    .gvi-hpill.stage-done{ background:var(--positive-bg); color:var(--positive); }
    .gvi-hpill.action{ background:var(--teal-3); color:var(--teal); }
    .gvi-hbody{ padding:13px 16px 15px; border-top:1px solid var(--line-2); }
    .gvi-hrow{ margin-bottom:12px; }
    .gvi-hrow:last-child{ margin-bottom:0; }
    .gvi-hrow .hk{ font-size:10px; font-weight:600; letter-spacing:.5px; text-transform:uppercase; color:var(--faint); margin-bottom:4px; }
    .gvi-hrow .hv{ font-size:14px; color:var(--ink); line-height:1.5; }

    /* ---- Expandable full report (native <details>, no JS) ---- */
    details.gvi-report{ border:1px solid var(--teal-3); border-radius:12px; background:var(--teal-soft); margin-top:6px; }
    details.gvi-report > summary{ list-style:none; cursor:pointer; padding:11px 14px; display:flex; align-items:center; gap:9px; font-size:13px; font-weight:600; color:var(--teal); }
    details.gvi-report > summary::-webkit-details-marker{ display:none; }
    details.gvi-report > summary .chev{ margin-left:auto; transition:transform .18s; }
    details.gvi-report[open] > summary .chev{ transform:rotate(90deg); }
    .gvi-report-body{ padding:14px 16px; background:var(--card); border-top:1px solid var(--teal-3); font-size:13.5px; color:var(--ink); line-height:1.6; }
    .gvi-report-body :is(ul,ol){ padding-left:18px; }
    .gvi-report-body p:first-child{ margin-top:0; }
    .gvi-report-body p:last-child{ margin-bottom:0; }

    /* ---- Investigation setup / entries form (Add More redesign) ---- */
    .gvi-block{ padding:22px 24px; margin-bottom:16px; }
    .gvi-block-head{ display:flex; align-items:center; gap:10px; margin-bottom:18px; }
    .gvi-step{ width:24px; height:24px; border-radius:8px; background:var(--teal); color:#fff; font-size:12px; font-weight:600; display:grid; place-items:center; flex:none; }
    .gvi-entries-hint{ font-size:12.5px; color:var(--muted); margin-bottom:14px; }

    .gvi-entry{ border:1px solid var(--line); border-radius:14px; overflow:hidden; margin-bottom:14px; background:var(--card); }
    .gvi-entry:last-child{ margin-bottom:0; }
    .gvi-entry-h{ display:flex; align-items:center; gap:12px; padding:13px 18px; background:var(--teal-soft); border-bottom:1px solid var(--line); cursor:pointer; flex-wrap:wrap; }
    .gvi-entry.collapsed .gvi-entry-b{ display:none; }
    .gvi-entry.collapsed .gvi-entry-h{ border-bottom:none; }
    .gvi-entry-h .caret{ color:var(--faint); flex:none; transition:transform .18s; }
    .gvi-entry.collapsed .gvi-entry-h .caret{ transform:rotate(-90deg); }
    .gvi-entry-h .enum{ display:inline-flex; align-items:center; gap:8px; font-size:13px; font-weight:600; color:var(--teal); }
    .gvi-entry-h .ndot{ width:22px; height:22px; border-radius:7px; background:var(--teal); color:#fff; font-size:11px; display:grid; place-items:center; font-variant-numeric:tabular-nums; }
    .gvi-stagechip{ font-size:10.5px; font-weight:600; padding:3px 10px; border-radius:20px; background:var(--line-2); color:var(--muted); white-space:nowrap; }
    .gvi-stagechip.set{ background:var(--warning-bg); color:var(--warning); }
    /* Colour/hover come from the app's own .eb-btn-critical class (applied
       alongside this one in markup) — same destructive-action red used by
       every other Remove/Delete/Reject control in this app, not a one-off
       shade. This rule only adds the layout this button needs inside the
       entry header. */
    .gvi-entry-h .gvi-rm{ margin-left:auto; display:inline-flex; align-items:center; gap:6px; font-size:12.5px; padding:7px 12px; border-radius:9px; }
    .gvi-entry-h .gvi-rm[hidden]{ display:none; }
    .gvi-entry-b{ padding:20px; }
    .gvi-entry-b .field{ display:flex; flex-direction:column; gap:7px; margin-bottom:16px; }
    .gvi-entry-b .field:last-child{ margin-bottom:0; }
    .gvi-entry-b .flabel{ font-size:11px; font-weight:600; letter-spacing:.5px; text-transform:uppercase; color:var(--faint); }
    .gvi-entry-b .grid2{ display:grid; grid-template-columns:repeat(2,1fr); gap:16px; }
    @media (max-width:820px){ .gvi-entry-b .grid2{ grid-template-columns:1fr; } }

    .gvi-add-entry{ width:100%; display:flex; align-items:center; justify-content:center; gap:9px; border:1.5px dashed var(--line); background:var(--teal-soft); color:var(--teal); border-radius:12px; padding:15px; font-family:inherit; font-size:14px; font-weight:600; cursor:pointer; transition:border-color .15s,background .15s; margin-top:14px; }
    .gvi-add-entry:hover{ border-color:var(--teal); background:var(--teal-3); }

    @media (prefers-reduced-motion:reduce){ .gvi-entry-h .caret, .gvi-add-entry{ transition:none!important; } }

    /* ---- Outcome / Status footer ---- */
    .gvi-foot{ display:flex; align-items:center; justify-content:space-between; gap:20px; flex-wrap:wrap; padding:18px 24px; margin-top:16px; }
    .gvi-foot .fitem{ display:flex; flex-direction:column; gap:5px; }
    .gvi-foot .fk{ font-size:10.5px; font-weight:600; letter-spacing:.5px; text-transform:uppercase; color:var(--faint); }
    .gvi-foot .fv{ font-size:15px; font-weight:600; color:var(--ink); }
    .gvi-foot .fv.none{ color:var(--faint); font-weight:400; font-style:italic; }
    .gvi-foot .fleft{ display:flex; gap:40px; flex-wrap:wrap; }
    .gvi-status-pill{ display:inline-flex; align-items:center; gap:7px; font-size:13px; font-weight:600; padding:7px 14px; border-radius:20px; }
    .gvi-status-pill.pending{ background:var(--warning-bg); color:var(--warning); }
    .gvi-status-pill.in_review{ background:var(--teal-3); color:var(--teal); }
    .gvi-status-pill.resolved{ background:var(--positive-bg); color:var(--positive); }
    .gvi-status-pill.rejected{ background:var(--error-bg); color:var(--error); }

    @media (max-width:900px){
        .gvi-hero{ margin-bottom:16px; }
    }
</style>
