{{--
    Configuration (Workforce Planning) — shared chrome for this screen only.
    Every class is `cfg-` prefixed so nothing collides with this app's
    existing global classes — in particular `ul.listing-wrapper li::before`
    in default.css injects a green check-circle icon on every `<li>` of that
    class, shared by two OTHER unrelated pages (Visa document list, Payroll
    run). This page's Configurations list uses fresh `cfg-` classes instead
    of `listing-wrapper`/`a-link`/`card-confingLink`, which is how the old
    always-on tick gets removed here without touching that shared rule (or
    those other pages) at all. Tokens are declared locally on `.cfg-scope`
    rather than on :root so they match the finalized reference exactly
    without touching the app-wide token file — `--local` in particular is
    a color this page alone uses.

    The year dropdown reuses the app's shared .dd component
    (resorts._dropdown_styles / _dropdown_script) rather than a one-off.
--}}
<style>
    .cfg-scope{
        --teal:#014653; --teal-2:#035b6c; --teal-3:#e6f0f1; --teal-soft:#f1f7f7;
        --local:#4E8C96;
        --ink:#14232A; --g1:#3A4145; --g2:#6B7378; --g3:#99A1A5; --g4:#C7CDCF; --g6:#F4F5F5;
        --line:#E2EBEC; --line-2:#EEF4F4; --ok:#1F9D6B; --warn:#B7791F; --err:#E5573F;
        --pop:0 1px 2px rgba(1,70,83,.05),0 10px 30px rgba(1,70,83,.14);
        font-family:'Poppins',-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
        color:var(--ink); font-size:14px; line-height:1.5; letter-spacing:-.005em;
    }
    .cfg-tnum{ font-variant-numeric:tabular-nums; }

    /* 60/40 layout */
    .cfg-grid{ display:grid; grid-template-columns:minmax(0,3fr) minmax(0,2fr); gap:16px; align-items:start; }
    @media (max-width:900px){ .cfg-grid{ grid-template-columns:1fr; } }
    .cfg-left{ display:flex; flex-direction:column; gap:16px; }
    .cfg-card{ background:#fff; border:1px solid var(--line); border-radius:16px;
        box-shadow:0 1px 2px rgba(1,70,83,.04),0 10px 26px rgba(1,70,83,.05); padding:20px 22px; }

    /* section head */
    .cfg-shead{ display:flex; align-items:center; justify-content:space-between; gap:14px; margin-bottom:16px; }
    .cfg-slbl{ font-size:11px; font-weight:600; letter-spacing:.5px; text-transform:uppercase; color:var(--g2); }
    .cfg-req{ color:var(--err); margin-left:1px; }

    /* ratio */
    .cfg-ratio{ display:flex; align-items:flex-end; gap:14px; }
    .cfg-field{ display:flex; flex-direction:column; gap:6px; }
    .cfg-field .cfg-k{ font-size:11px; font-weight:600; letter-spacing:.4px; text-transform:uppercase; color:var(--g3); }
    .cfg-ratio .cfg-colon{ font-size:22px; font-weight:500; color:var(--g4); padding-bottom:8px; }
    .cfg-inp{ width:100%; font:inherit; font-size:14px; font-weight:500; color:var(--ink); background:#fff;
        border:1px solid var(--line); border-radius:10px; padding:10px 12px; transition:border-color .15s,box-shadow .15s; }
    .cfg-inp:hover{ border-color:var(--g4); }
    .cfg-inp:focus{ outline:none; border-color:var(--teal); box-shadow:0 0 0 3px rgba(1,70,83,.12); }
    .cfg-num{ width:104px; text-align:center; }
    .cfg-suffix{ position:relative; }
    .cfg-suffix .cfg-pc{ position:absolute; right:12px; top:50%; transform:translateY(-50%); font-size:12px; color:var(--g3); pointer-events:none; }
    .cfg-suffix .cfg-inp{ padding-right:26px; }
    .cfg-bar{ margin-top:18px; }
    .cfg-bar .cfg-track{ display:flex; height:10px; border-radius:6px; overflow:hidden; background:var(--g6); gap:2px; }
    .cfg-bar .cfg-seg{ height:100%; }
    .cfg-bar .cfg-seg.cfg-x{ background:var(--teal); }
    .cfg-bar .cfg-seg.cfg-l{ background:var(--local); }
    .cfg-bar .cfg-keys{ display:flex; justify-content:space-between; margin-top:8px; font-size:11.5px; font-weight:500; color:var(--g2); }
    .cfg-bar .cfg-keys .cfg-dot{ display:inline-block; width:8px; height:8px; border-radius:50%; margin-right:6px; vertical-align:middle; }
    .cfg-bar .cfg-keys .cfg-dot.cfg-x{ background:var(--teal); } .cfg-bar .cfg-keys .cfg-dot.cfg-l{ background:var(--local); }
    .cfg-bar .cfg-keys b{ font-weight:600; color:var(--ink); }
    .cfg-note{ margin-top:10px; font-size:12px; font-weight:500; color:var(--warn); display:none; align-items:center; gap:6px; }
    .cfg-note.show{ display:flex; }

    /* buttons */
    .cfg-gbtn{ display:inline-flex; align-items:center; gap:7px; font-size:13px; font-weight:500; color:var(--teal);
        background:#fff; border:1px solid var(--line); border-radius:9px; padding:8px 13px; cursor:pointer; text-decoration:none; }
    .cfg-gbtn:hover{ background:var(--teal-3); border-color:#cfe0e1; }
    .cfg-btn{ display:inline-flex; align-items:center; gap:8px; font:inherit; font-size:14px; font-weight:500;
        border-radius:10px; padding:11px 20px; cursor:pointer; border:1px solid transparent; transition:background .15s; }
    .cfg-btn.cfg-primary{ background:var(--teal); color:#fff; }
    .cfg-btn.cfg-primary:hover{ background:var(--teal-2); }
    .cfg-btn.cfg-primary:disabled{ background:var(--g4); cursor:not-allowed; }

    /* upload row — year + file picker + upload on one line */
    .cfg-flabel{ font-size:12px; color:var(--g2); margin:0 0 10px; }
    .cfg-uploadrow{ display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
    .cfg-uploadrow .dd{ flex:0 0 190px; margin-top:0; }
    .cfg-ph{ color:var(--g3); }
    .cfg-choose{ display:inline-flex; align-items:center; gap:8px; font:inherit; font-size:13px; font-weight:500; color:var(--teal);
        background:#fff; border:1px solid var(--line); border-radius:9px; padding:10px 14px; cursor:pointer; flex:none; }
    .cfg-choose:hover{ background:var(--teal-3); border-color:#cfe0e1; }
    .cfg-fname{ font-size:13px; color:var(--g3); flex:1; min-width:90px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .cfg-fname.cfg-set{ color:var(--g1); font-weight:500; }
    .cfg-uploadrow .cfg-btn.cfg-primary{ flex:none; }

    /* form footer submit */
    .cfg-cardfoot{ display:flex; justify-content:flex-end; border-top:1px solid var(--line-2); margin-top:18px; padding-top:16px; }

    /* configuration list (navigation, NOT completion) */
    .cfg-list .cfg-clbl-h{ font-size:11px; font-weight:600; letter-spacing:.5px; text-transform:uppercase; color:var(--g2); margin-bottom:4px; }
    .cfg-list .cfg-sub{ font-size:12px; color:var(--g3); margin-bottom:10px; }
    .cfg-list ul{ list-style:none; margin:0; padding:0; }
    .cfg-list li{ margin:0; }
    .cfg-list li a{ display:flex; align-items:center; gap:13px; padding:13px 12px; border-radius:11px; cursor:pointer;
        transition:background .14s; text-decoration:none; color:inherit; }
    .cfg-list li a:hover{ background:var(--teal-soft); }
    .cfg-list li + li{ border-top:1px solid var(--line-2); }
    .cfg-list li:hover + li{ border-top-color:transparent; }
    .cfg-list .cfg-txt{ flex:1; min-width:0; }
    .cfg-list .cfg-txt b{ display:block; font-size:14px; font-weight:500; color:var(--ink); line-height:1.3; }
    .cfg-list li a:hover .cfg-txt b{ color:var(--teal); }
    .cfg-list .cfg-txt small{ font-size:11.5px; color:var(--g3); }
    .cfg-list .cfg-go{ color:var(--g4); transition:color .14s,transform .14s; flex:none; }
    .cfg-list li a:hover .cfg-go{ color:var(--teal); transform:translateX(2px); }
</style>
