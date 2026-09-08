{{--
    Compare Budget (HOD vs WAI) — shared chrome for this screen only.
    Every selector is scoped under .cb-card and every class is `cb-`
    prefixed so nothing here collides with the app's existing global
    classes (.card, .box, .pos, .money, .reason, .pill, .zero, .hc — all
    already used elsewhere in default.css) or with the legacy
    #compare-budgettable / .table-compareBudget rules that targeted this
    same screen's old markup. Tokens are declared locally on .cb-card
    rather than on :root so they can match the finalized reference
    exactly without touching the app-wide token file.
--}}
<style>
    .cb-card{
        --teal:#014653; --teal-2:#035b6c; --teal-3:#e6f0f1; --teal-soft:#f1f7f7;
        --ink:#14232A; --g1:#3A4145; --g2:#6B7378; --g3:#99A1A5; --g4:#C7CDCF; --g6:#F4F5F5;
        --line:#E2EBEC; --line-2:#EEF4F4; --ok:#1F9D6B; --warn:#B7791F;
        width:100%; background:#fff; border:1px solid var(--line); border-radius:16px;
        box-shadow:0 1px 2px rgba(1,70,83,.04),0 10px 26px rgba(1,70,83,.05);
        padding:20px 22px; font-family:'Poppins',-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
        color:var(--ink); font-size:14px; line-height:1.5; letter-spacing:-.005em;
    }
    .cb-tnum{ font-variant-numeric:tabular-nums; }

    /* header */
    .cb-hd{ display:flex; align-items:center; gap:12px; margin-bottom:18px; }
    .cb-back{ width:32px; height:32px; border-radius:9px; border:1px solid var(--line); background:#fff;
        color:var(--g1); display:grid; place-items:center; cursor:pointer; flex:none; text-decoration:none; }
    .cb-back:hover{ background:var(--teal-soft); }
    .cb-hd .cb-kick{ font-size:11px; font-weight:600; letter-spacing:.5px; text-transform:uppercase; color:var(--g3); }
    .cb-hd .cb-dept{ font-size:18px; font-weight:600; }
    .cb-hd .cb-sp{ flex:1; }
    .cb-regen{ display:inline-flex; align-items:center; gap:7px; font-size:14px; font-weight:500; color:var(--teal);
        background:#fff; border:1px solid var(--line); border-radius:10px; padding:9px 15px; cursor:pointer; }
    .cb-regen:hover{ background:var(--teal-soft); }
    .cb-regen:disabled{ cursor:default; opacity:.7; }
    .cb-regen svg{ transition:transform .5s linear; }
    .cb-regen.spinning svg{ animation:cb-spin 1s linear infinite; }
    @media (prefers-reduced-motion: reduce){ .cb-regen.spinning svg{ animation:none; } }
    @keyframes cb-spin{ to{ transform:rotate(360deg); } }

    /* summary — two structured boxes + central connector */
    .cb-cmp{ display:grid; grid-template-columns:1fr auto 1fr; align-items:center; gap:16px; margin-bottom:20px; }
    .cb-box{ border:1px solid var(--line); border-radius:14px; padding:16px 18px; }
    .cb-box .cb-lbl{ display:flex; align-items:center; gap:8px; font-size:11px; font-weight:600; letter-spacing:.4px;
        text-transform:uppercase; color:var(--g2); }
    .cb-box.cb-w .cb-lbl{ color:var(--teal); }
    .cb-box.cb-w{ text-align:right; }
    .cb-box.cb-w .cb-lbl{ justify-content:flex-end; }
    .cb-box.cb-w .cb-kv .cb-r{ justify-content:flex-end; }
    .cb-box .cb-ai{ display:inline-flex; align-items:center; gap:5px; font-size:10.5px; font-weight:600; color:var(--g3); }
    .cb-box .cb-ai .cb-d{ width:6px; height:6px; border-radius:50%; background:var(--g3); }
    .cb-box .cb-ai.ready .cb-d{ background:var(--ok); }
    .cb-box .cb-big{ font-size:30px; font-weight:500; letter-spacing:-1px; line-height:1; margin-top:10px; }
    .cb-box .cb-big span{ font-size:14px; font-weight:500; color:var(--g3); letter-spacing:0; }
    .cb-box .cb-kv{ margin-top:13px; padding-top:12px; border-top:1px solid var(--line-2); display:flex; flex-direction:column; gap:8px; }
    .cb-box .cb-r{ display:flex; align-items:center; justify-content:flex-start; gap:8px; }
    .cb-box .cb-r .cb-k{ font-size:12.5px; color:var(--g2); }
    .cb-box .cb-r .cb-v{ font-size:13.5px; font-weight:500; color:var(--ink); font-variant-numeric:tabular-nums; }
    .cb-box .cb-r .cb-v .cb-vac{ color:var(--warn); font-weight:600; margin-left:4px; }
    .cb-conn{ display:flex; flex-direction:column; align-items:center; gap:8px; min-width:132px; }
    .cb-conn .cb-arw{ width:40px; height:40px; border-radius:50%; background:var(--teal-soft); color:var(--teal); display:grid; place-items:center; }
    .cb-conn .cb-amt{ font-size:16px; font-weight:700; color:var(--ok); letter-spacing:-.3px; }
    .cb-conn .cb-amt.down{ color:var(--ink); }
    .cb-conn .cb-meta{ font-size:11.5px; font-weight:600; color:var(--g2); }
    @media(max-width:820px){
        .cb-cmp{ grid-template-columns:1fr; }
        .cb-conn{ flex-direction:row; min-width:0; gap:12px; }
        .cb-conn .cb-arw{ transform:rotate(90deg); }
    }

    /* comparison table */
    .cb-tbl-wrap{ border:1px solid var(--line); border-radius:12px; overflow:auto; }
    .cb-card table{ width:100%; border-collapse:separate; border-spacing:0; font-size:14px; }
    .cb-card th, .cb-card td{ padding:12px 14px; text-align:left; vertical-align:top; }
    .cb-card th.cb-num, .cb-card td.cb-num{ text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; }
    .cb-card thead th{ background:var(--teal-soft); color:var(--g2); border-bottom:1px solid var(--line); }
    .cb-card thead .cb-grp{ font-size:11px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; text-align:center; padding:10px 14px; color:var(--g2); border-bottom:none; }
    .cb-card thead .cb-grp.cb-wai{ color:var(--teal); }
    .cb-card thead .cb-sub th{ font-size:10.5px; font-weight:600; letter-spacing:.3px; text-transform:uppercase; color:var(--g3); white-space:nowrap; padding-top:8px; padding-bottom:8px; }
    .cb-card thead .cb-c-pos, .cb-card thead .cb-c-reason{ font-size:11px; font-weight:700; letter-spacing:.4px; text-transform:uppercase; color:var(--g2); vertical-align:middle; }
    {{-- max-width pins these so the table's width:100% redistributes its
         slack into the Reason column (already flexible, no max-width)
         instead of stretching the numeric columns back out. --}}
    .cb-card .cb-c-pos{ width:280px; max-width:280px; }
    .cb-card .cb-c-hc{ width:76px; max-width:76px; }
    .cb-card .cb-c-mo, .cb-card .cb-c-yr{ width:96px; max-width:96px; }
    .cb-card tbody td{ border-bottom:1px solid var(--line-2); }
    .cb-card tbody tr:last-child td{ border-bottom:none; }
    .cb-pos{ font-size:14px; font-weight:500; color:var(--ink); white-space:nowrap; }
    .cb-money{ font-weight:500; color:var(--ink); }
    .cb-hc{ font-weight:500; color:var(--g1); }
    .cb-zero{ color:var(--g4); font-weight:500; }
    .cb-mstack{ display:flex; flex-direction:column; align-items:flex-end; gap:3px; }
    .cb-delta{ font-size:11px; font-weight:700; white-space:nowrap; }
    .cb-delta.cb-up{ color:var(--ok); }
    .cb-pill{ display:inline-block; font-size:10px; font-weight:700; letter-spacing:.3px; border-radius:20px; padding:2px 8px; }
    .cb-pill.cb-new{ background:#fff; border:1px solid #cfe0e1; color:var(--teal); }
    .cb-reason{ white-space:normal; color:var(--g1); line-height:1.5; font-size:14px; min-width:300px; }
    .cb-reason .cb-hint{ color:var(--g4); }
    .cb-reason .cb-risk{ margin-top:6px; font-size:13px; color:var(--g2); }
    .cb-reason .cb-risk b{ font-size:10px; font-weight:700; letter-spacing:.4px; text-transform:uppercase; color:var(--g3); margin-right:5px; }
    .cb-card tfoot td{ background:var(--teal-soft); font-weight:700; border-top:1px solid var(--line); }
    .cb-card tfoot .cb-lbl{ font-size:11px; letter-spacing:.4px; text-transform:uppercase; color:var(--teal); }
</style>
