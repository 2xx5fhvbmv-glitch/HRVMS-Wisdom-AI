{{--
    View Manning — shared chrome for this screen only. Every selector is
    scoped under .vm-card and every class is `vm-` prefixed so nothing
    here collides with the app's existing global classes (.card, .dept
    doesn't exist elsewhere but .pos/.rank/.nat etc. are generic enough to
    be risky) or with the legacy .table-viewMannAccording / developer.min.css
    rules that targeted this same screen's old nested-table markup — this
    screen no longer uses that table at all. Tokens are declared locally
    on .vm-card rather than on :root so they match the finalized reference
    exactly without touching the app-wide token file.

    The year-filter dropdown reuses the app's shared .dd component
    (resorts._dropdown_styles / _dropdown_script) rather than a one-off —
    only this table/accordion chrome lives here.
--}}
<style>
    .vm-card{
        --teal:#014653; --teal-3:#e6f0f1; --teal-soft:#f1f7f7;
        --ink:#14232A; --g1:#3A4145; --g2:#6B7378; --g3:#99A1A5; --g4:#C7CDCF; --g6:#F4F5F5;
        --line:#E2EBEC; --line-2:#EEF4F4; --ok:#1F9D6B; --warn:#B7791F;
        font-family:'Poppins',-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
        color:var(--ink); font-size:14px; line-height:1.5; letter-spacing:-.005em;
    }
    .vm-tnum{ font-variant-numeric:tabular-nums; }

    .vm-tools{ display:flex; align-items:center; justify-content:flex-end; margin-bottom:14px; }
    .vm-tools .dd{ width:auto; min-width:120px; }
    .vm-tools .dd-panel{ right:0; left:auto; }

    /* department accordion */
    .vm-dept{ border:1px solid var(--line); border-radius:14px; overflow:hidden; margin-bottom:12px; }
    .vm-dept:last-child{ margin-bottom:0; }
    .vm-dhd{ display:flex; align-items:center; gap:14px; padding:14px 16px; cursor:pointer; user-select:none; background:#fff; transition:background .15s; }
    .vm-dhd:hover{ background:var(--teal-soft); }
    .vm-dept.open .vm-dhd{ border-bottom:1px solid var(--line-2); }
    .vm-chev{ width:26px; height:26px; border-radius:8px; display:grid; place-items:center; color:var(--g2); flex:none; transition:transform .2s; }
    .vm-dept.open .vm-chev{ transform:rotate(180deg); color:var(--teal); }
    .vm-dname{ font-size:16px; font-weight:500; color:var(--ink); }
    .vm-dmeta{ font-size:12px; color:var(--g3); margin-top:1px; font-weight:500; }
    .vm-dmeta .vm-vac{ color:var(--warn); }
    .vm-dhd .vm-sp{ flex:1; }
    .vm-revise{ display:inline-flex; align-items:center; gap:7px; font-size:13px; font-weight:500; color:var(--teal);
        background:#fff; border:1px solid var(--line); border-radius:9px; padding:8px 13px; cursor:pointer; flex:none; text-decoration:none; }
    .vm-revise:hover{ background:var(--teal-3); border-color:#cfe0e1; }
    @media (prefers-reduced-motion: reduce){ .vm-chev{ transition:none; } }

    /* body / table */
    .vm-dbody{ display:none; }
    .vm-dept.open .vm-dbody{ display:block; }
    .vm-card table{ width:100%; border-collapse:separate; border-spacing:0; font-size:14px; }
    .vm-card thead th{ background:var(--teal-soft); text-align:left; font-size:11px; font-weight:600; letter-spacing:.4px;
        text-transform:uppercase; color:var(--g2); padding:9px 16px; white-space:nowrap; border-bottom:1px solid var(--line); }
    .vm-card th.vm-c-no, .vm-card td.vm-c-no{ text-align:center; }
    .vm-c-pos{ width:24%; } .vm-c-no{ width:12%; } .vm-c-emp{ width:26%; } .vm-c-rank{ width:19%; } .vm-c-nat{ width:19%; }
    .vm-card tbody td{ padding:11px 16px; vertical-align:middle; border-bottom:1px solid var(--line-2); }
    .vm-card tbody tr:last-child td{ border-bottom:none; }
    .vm-card tbody tr:hover td{ background:#fbfdfd; }
    .vm-card tbody tr.vm-pstart td{ border-top:1px solid var(--line); }
    .vm-card tbody tr.vm-pstart:first-child td{ border-top:none; }
    .vm-pos-c{ vertical-align:top; padding-top:13px; }
    .vm-pos{ font-size:14px; font-weight:400; color:var(--ink); line-height:1.35; }
    .vm-no{ font-size:14px; font-weight:400; color:var(--g1); vertical-align:top; padding-top:13px; }
    .vm-no.vm-zero{ color:var(--g4); }

    /* employee cell */
    .vm-emp{ display:flex; align-items:center; gap:10px; min-width:0; }
    .vm-av{ width:28px; height:28px; border-radius:50%; flex:none; display:grid; place-items:center; font-size:11px;
        font-weight:600; color:var(--teal); background:var(--teal-3); overflow:hidden; }
    .vm-av img{ width:100%; height:100%; object-fit:cover; }
    .vm-ename{ font-size:14px; font-weight:400; color:var(--ink); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .vm-oob{ display:inline-block; font-size:10px; font-weight:500; letter-spacing:.2px; color:var(--critical);
        background:var(--critical-bg); border-radius:6px; padding:2px 7px; margin-left:6px; vertical-align:middle; }
    .vm-vacant{ display:inline-flex; align-items:center; gap:6px; font-size:11px; font-weight:600; letter-spacing:.2px;
        color:var(--g2); background:var(--g6); border-radius:20px; padding:4px 11px; }
    .vm-vacant .vm-vd{ width:6px; height:6px; border-radius:50%; background:var(--g4); }

    .vm-rank{ display:inline-block; font-size:10.5px; font-weight:500; letter-spacing:.4px; text-transform:uppercase;
        color:var(--g1); background:var(--g6); border-radius:6px; padding:3px 9px; }
    .vm-nat{ font-size:14px; color:var(--g1); }
    .vm-dash{ color:var(--g4); }
</style>
