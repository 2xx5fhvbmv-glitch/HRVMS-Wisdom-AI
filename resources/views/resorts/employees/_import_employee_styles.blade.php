{{--
    Import Employee — shared chrome for this screen only.
    `ie-` prefixed classes so nothing here collides with this app's large
    global `.card`/`.card-footer`/`.btn` rules reused across 380+ other
    views. Tokens are declared locally on `.ie-scope` rather than on :root
    so they match the finalized reference exactly without touching the
    app-wide token file.
--}}
<style>
    .ie-scope{
        --teal:#014653; --teal-2:#035b6c; --teal-3:#e6f0f1; --teal-soft:#f1f7f7;
        --ink:#14232A; --g1:#3A4145; --g2:#6B7378; --g3:#99A1A5; --g4:#C7CDCF; --g6:#F4F5F5;
        --line:#E2EBEC; --line-2:#EEF4F4; --ok:#1F9D6B;
        font-family:'Poppins',-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
        color:var(--ink); font-size:14px; line-height:1.5; letter-spacing:-.005em;
    }

    .ie-card{ width:100%; background:#fff; border:1px solid var(--line); border-radius:16px;
        box-shadow:0 1px 2px rgba(1,70,83,.04),0 10px 26px rgba(1,70,83,.05); padding:8px 26px; }

    /* steps — two columns */
    .ie-steps{ display:grid; grid-template-columns:1fr 1fr; }
    .ie-step{ display:flex; gap:16px; padding:24px 28px; }
    .ie-step:first-child{ padding-left:0; }
    .ie-step:last-child{ padding-right:0; border-left:1px solid var(--line-2); }
    @media (max-width:760px){
        .ie-steps{ grid-template-columns:1fr; }
        .ie-step, .ie-step:first-child, .ie-step:last-child{ padding:22px 0; border-left:none; }
        .ie-step + .ie-step{ border-top:1px solid var(--line-2); }
    }
    .ie-badge{ width:28px; height:28px; flex:none; border-radius:50%; background:var(--teal); color:#fff;
        font-size:13px; font-weight:600; display:grid; place-items:center; margin-top:1px; }
    .ie-step .ie-body{ flex:1; min-width:0; }
    .ie-step .ie-t{ font-size:15px; font-weight:600; color:var(--ink); }
    .ie-step .ie-d{ font-size:12.5px; color:var(--g2); margin-top:2px; }
    .ie-controls{ margin-top:14px; }

    /* buttons */
    .ie-btn{ display:inline-flex; align-items:center; gap:8px; font:inherit; font-size:14px; font-weight:500;
        border-radius:10px; padding:11px 18px; cursor:pointer; border:1px solid transparent;
        transition:background .15s,border-color .15s; text-decoration:none; }
    .ie-btn.ie-ghost{ background:#fff; border-color:var(--line); color:var(--teal); }
    .ie-btn.ie-ghost:hover{ background:var(--teal-3); border-color:#cfe0e1; }
    .ie-btn.ie-primary{ background:var(--teal); color:#fff; }
    .ie-btn.ie-primary:hover{ background:var(--teal-2); }
    .ie-btn.ie-primary:disabled{ background:var(--g4); cursor:not-allowed; }

    /* file picker — no drag & drop */
    .ie-filepick{ display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
    .ie-choose{ display:inline-flex; align-items:center; gap:8px; font:inherit; font-size:13px; font-weight:500;
        color:var(--teal); background:#fff; border:1px solid var(--line); border-radius:10px; padding:10px 15px;
        cursor:pointer; flex:none; }
    .ie-choose:hover{ background:var(--teal-3); border-color:#cfe0e1; }
    .ie-fname{ font-size:13px; color:var(--g3); min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .ie-fname.ie-set{ color:var(--g1); font-weight:500; }

    /* footer */
    .ie-foot{ display:flex; justify-content:flex-end; border-top:1px solid var(--line-2); padding:18px 0; margin-top:0; }

    @media (prefers-reduced-motion: reduce){
        .ie-btn, .ie-choose{ transition:none; }
    }
</style>
