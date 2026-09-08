{{--
    Create/Edit Benefit Grid — shared chrome for this screen only.
    PAYROLL-CONNECTED FORM: this file is presentation only. Every selector
    here is `bg-` prefixed so nothing collides with the app's existing
    global classes. Tokens are declared locally on `.bg-scope` rather than
    on :root so they match the finalized reference exactly without
    touching the app-wide token file.

    Select2: every real <select class="select2t-none"> on this page is
    auto-initialized as a Select2 widget by the app's global script
    (resorts/layouts/js.blade.php — minimumResultsForSearch:-1, so the
    search box is already off site-wide). Select2 replaces the native
    control with its own DOM, so this partial restyles Select2's actual
    generated elements (.select2-selection, .select2-dropdown, etc.)
    rather than the hidden native <select> — the field stays a real
    Select2 (multi-select chips on Rank(s)/Laundry keep working), only
    its skin changes. `.s2-empty-opt` is an existing hook the same global
    script already toggles based on the select's value — reused here for
    the placeholder-grey look, no new JS needed for that part.
--}}
<style>
    .bg-scope{
        --teal:#014653; --teal-2:#035b6c; --teal-3:#e6f0f1; --teal-soft:#f1f7f7;
        --ink:#14232A; --g1:#3A4145; --g2:#6B7378; --g3:#99A1A5; --g4:#C7CDCF; --g6:#F4F5F5;
        --line:#E2EBEC; --line-2:#EEF4F4; --err:#E5573F;
        --pop:0 1px 2px rgba(1,70,83,.05),0 10px 30px rgba(1,70,83,.14);
        font-family:'Poppins',-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
        color:var(--ink); font-size:14px; line-height:1.5; letter-spacing:-.005em;
    }

    /* layout: form + sticky section nav. Nav renders first in the markup
       (so it stays first in source/tab order) but sits on the RIGHT visually
       — grid `order` flips it without touching the HTML. */
    .bg-layout{ display:grid; grid-template-columns:1fr 216px; gap:20px; align-items:start; }
    @media (max-width:920px){ .bg-layout{ grid-template-columns:1fr; } }

    .bg-snav{ order:2; position:sticky; top:14px; background:#fff; border:1px solid var(--line); border-radius:14px;
        padding:8px; box-shadow:0 1px 2px rgba(1,70,83,.04); }
    .bg-snav a{ display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:10px; font-size:13px;
        font-weight:500; color:var(--g2); text-decoration:none; transition:background .14s,color .14s; }
    .bg-snav a .bg-n{ font-size:11px; font-weight:600; color:var(--g4); width:16px; flex:none; font-variant-numeric:tabular-nums; }
    .bg-snav a:hover{ background:var(--teal-soft); color:var(--g1); }
    .bg-snav a.active{ background:var(--teal-soft); color:var(--teal); }
    .bg-snav a.active .bg-n{ color:var(--teal); }
    @media (max-width:920px){
        /* single-column below this width — keep the nav strip above the
           form here (its usual, more usable spot as a quick-jump bar on a
           narrow screen) rather than carrying the right-side order over. */
        .bg-snav{ order:0; position:static; display:flex; overflow-x:auto; gap:4px; }
        .bg-forms{ order:1; }
        .bg-snav a{ white-space:nowrap; }
        .bg-snav a .bg-n{ display:none; }
    }

    .bg-forms{ order:1; display:flex; flex-direction:column; gap:16px; min-width:0; }
    .bg-card{ background:#fff; border:1px solid var(--line); border-radius:16px;
        box-shadow:0 1px 2px rgba(1,70,83,.04),0 10px 26px rgba(1,70,83,.05); padding:22px 24px; scroll-margin-top:14px; }
    .bg-sec-h{ margin-bottom:18px; padding-bottom:14px; border-bottom:1px solid var(--line-2); }
    .bg-sec-t{ font-size:15px; font-weight:600; color:var(--ink); }
    .bg-sec-h.bg-rowh{ display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; border-bottom:none; padding-bottom:0; margin-bottom:0; }
    .bg-sec-h.bg-rowh + *{ margin-top:18px; padding-top:14px; border-top:1px solid var(--line-2); }
    .bg-addsport{ display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
    .bg-addsport .bg-inp{ width:210px; }
    @media (max-width:560px){ .bg-addsport .bg-inp{ width:150px; } }

    /* fields */
    .bg-fgrid{ display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px 20px; }
    .bg-fgrid.bg-c3{ grid-template-columns:repeat(3,minmax(0,1fr)); }
    .bg-f{ display:flex; flex-direction:column; gap:6px; min-width:0; }
    .bg-f.bg-full{ grid-column:1/-1; }
    .bg-f label{ font-size:12.5px; font-weight:600; color:var(--g1); }
    .bg-req{ color:var(--err); margin-left:2px; }
    .bg-f .bg-help{ font-size:11.5px; color:var(--g3); }
    .bg-inp{ width:100%; font:inherit; font-size:14px; color:var(--ink); background:#fff; border:1px solid var(--line);
        border-radius:10px; padding:10px 12px; transition:border-color .15s,box-shadow .15s; }
    .bg-inp::placeholder{ color:var(--g3); }
    .bg-inp:hover{ border-color:var(--g4); }
    .bg-inp:focus{ outline:none; border-color:var(--teal); box-shadow:0 0 0 3px rgba(1,70,83,.12); }
    .bg-inp.bg-sm{ padding:9px 11px; }

    /* checkboxes */
    .bg-checks{ display:flex; flex-wrap:wrap; gap:10px 18px; padding-top:6px; }
    .bg-check{ display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:13.5px; color:var(--g1); }
    .bg-check input{ width:17px; height:17px; accent-color:var(--teal); cursor:pointer; }
    .bg-checkgrid{ display:grid; grid-template-columns:repeat(auto-fill,minmax(190px,1fr)); gap:12px 16px; }
    .bg-checkgrid .SportsAddCheckbox{ display:flex; align-items:center; justify-content:space-between; gap:8px; }
    .bg-subblock{ margin-top:20px; padding-top:18px; border-top:1px solid var(--line-2); }
    .bg-blocklbl2{ display:block; font-size:12.5px; font-weight:600; color:var(--g1); margin-bottom:12px; }

    /* leave table */
    .bg-ltbl{ width:100%; border-collapse:separate; border-spacing:0; border:1px solid var(--line); border-radius:12px; overflow:hidden; }
    .bg-ltbl th{ background:var(--teal-soft); text-align:left; font-size:11px; font-weight:600; letter-spacing:.4px;
        text-transform:uppercase; color:var(--g2); padding:11px 16px; border-bottom:1px solid var(--line); }
    .bg-ltbl td{ padding:10px 16px; border-bottom:1px solid var(--line-2); vertical-align:middle; }
    .bg-ltbl tr:last-child td{ border-bottom:none; }
    .bg-ltbl tr:hover td{ background:#fbfdfd; }
    .bg-ltbl .bg-lt{ font-size:14px; font-weight:500; color:var(--ink); }
    .bg-ltbl .bg-amt{ font-size:11px; color:var(--g3); margin-left:7px; }
    .bg-ltbl .bg-c-mid{ width:190px; }
    .bg-ltbl .bg-c-elig{ width:34%; }

    /* add-row sub-sections */
    .bg-addwrap{ margin-top:6px; display:flex; flex-direction:column; gap:12px; }
    .bg-addedrow{ display:grid; grid-template-columns:1fr 1fr auto; gap:12px; align-items:center; }
    .bg-rmbtn{ width:38px; height:38px; flex:none; border:1px solid var(--line); background:#fff; border-radius:10px;
        color:var(--g3); cursor:pointer; display:grid; place-items:center; }
    .bg-rmbtn:hover{ background:#fdf0ee; border-color:#f3cfc8; color:var(--err); }
    .bg-addbtn2{ display:inline-flex; align-items:center; gap:7px; font:inherit; font-size:13px; font-weight:500;
        color:var(--teal); background:var(--teal-3); border:1px solid #cfe0e1; border-radius:9px; padding:9px 14px; cursor:pointer; }
    .bg-addbtn2:hover{ background:#dcebeb; }
    .bg-blocklbl{ font-size:12.5px; font-weight:600; color:var(--g1); margin-bottom:2px; }
    .bg-customhdr{ display:flex; align-items:center; justify-content:space-between; gap:14px; margin-top:20px; }

    /* Cancel/Submit sit inside the Special Rates card itself, right after
       Custom Fields — not a separate floating bar below it, which read as
       a second disconnected white box. Plain border-top + margin for
       separation from the fields above; no background/shadow/sticky, since
       it's just the tail end of the same card surface now. Buttons use the
       shared wfp-btn-secondary/wfp-btn-primary classes (from
       _wfp_buttons_v2_styles, included alongside this file) so they share
       the same hover-lift/press motion and teal fill as every other
       Cancel/Submit pair in the app. */
    .bg-actionbar{ margin-top:20px; padding-top:16px; border-top:1px solid var(--line-2);
        display:flex; justify-content:flex-end; gap:12px; }
    .bg-actionbar .wfp-btn-primary:disabled{ background:var(--g4); color:#fff; cursor:not-allowed; }

    /* ---- Select2 reskin (real widget kept — search already off site-wide
       via minimumResultsForSearch:-1; only the skin changes here) ---- */
    .bg-scope .select2-container{ width:100% !important; }
    .bg-scope .select2-container .select2-selection--single,
    .bg-scope .select2-container .select2-selection--multiple{
        border:1px solid var(--line) !important; border-radius:10px !important; background:#fff;
        min-height:41px; transition:border-color .15s,box-shadow .15s;
    }
    .bg-scope .select2-container:hover .select2-selection--single,
    .bg-scope .select2-container:hover .select2-selection--multiple{ border-color:var(--g4) !important; }
    .bg-scope .select2-container--focus .select2-selection--single,
    .bg-scope .select2-container--focus .select2-selection--multiple,
    .bg-scope .select2-container--open .select2-selection--single,
    .bg-scope .select2-container--open .select2-selection--multiple{
        border-color:var(--teal) !important; box-shadow:0 0 0 3px rgba(1,70,83,.12);
    }
    .bg-scope .select2-selection--single .select2-selection__rendered{
        font-size:14px; color:var(--ink); line-height:39px; padding-left:12px; padding-right:30px;
    }
    .bg-scope .select2-selection--single .select2-selection__rendered.s2-empty-opt{ color:var(--g3); }
    .bg-scope .select2-selection--single .select2-selection__arrow{ height:39px; right:8px; }
    .bg-scope .select2-selection--single .select2-selection__arrow b{ border-color:var(--g3) transparent transparent transparent; }
    .bg-scope .select2-container--open .select2-selection--single .select2-selection__arrow b{
        border-color:transparent transparent var(--g3) transparent;
    }
    .bg-scope .select2-selection--multiple .select2-selection__rendered{ padding:5px 8px; display:flex; flex-wrap:wrap; gap:5px; }
    .bg-scope .select2-selection--multiple .select2-selection__rendered.s2-empty-opt{ color:var(--g3); }
    .bg-scope .select2-selection--multiple .select2-selection__choice{
        background:var(--teal-3); border:1px solid #cfe0e1; color:var(--teal); border-radius:7px;
        padding:2px 8px; font-size:12.5px; font-weight:500; margin:0;
    }
    .bg-scope .select2-selection--multiple .select2-selection__choice__remove{ color:var(--teal); margin-right:5px; }
    .bg-scope .select2-selection--multiple .select2-search__field{ font:inherit; font-size:14px; margin-top:2px; }
    .bg-scope .select2-dropdown{ border:1px solid var(--line) !important; border-radius:12px !important; box-shadow:var(--pop);
        overflow:hidden; padding:5px; }
    .bg-scope .select2-results__option{ font-size:14px; border-radius:8px; padding:9px 10px; }
    .bg-scope .select2-results__option--highlighted[aria-selected]{ background:var(--teal-soft) !important; color:var(--ink) !important; }
    .bg-scope .select2-results__option[aria-selected="true"]{ background:var(--teal-3) !important; color:var(--teal) !important; }

    @media (prefers-reduced-motion: reduce){
        .bg-snav a, .bg-inp, .bg-rmbtn, .bg-addbtn2,
        .bg-scope .select2-container .select2-selection--single,
        .bg-scope .select2-container .select2-selection--multiple{ transition:none; }
    }
</style>
