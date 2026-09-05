{{--
    Disciplinary Investigation — shared presentation-only CSS. Included once
    by investigationreport.blade.php (the single view/route rendered for
    every role that can open this screen — DisciplineryInvestigation() has
    no HOD/HR/EXCOM variant, unlike the dashboard).

    Font sizes follow DASHBOARD_FONT_SIZES.md's production scale, matched to
    the Incident Investigation screen: 22px case title, 15px card titles,
    14px body/values/inputs, 10.5-11px uppercase labels. No [data-theme]
    rules — the theme engine is disabled; do not reintroduce it here.
--}}
<style>
    .dvi-wrap{ --dvi-radius:16px; }

    .dvi-card{ background:var(--card); border:1px solid var(--line); border-radius:var(--dvi-radius); box-shadow:var(--shadow); }
    .dvi-sec-h{ font-size:15px; font-weight:600; color:var(--ink); margin-bottom:16px; }

    /* ---- Hero ---- */
    .dvi-hero{ align-items:stretch; margin-bottom:16px; }
    .dvi-summary{ padding:24px 26px; height:100%; }
    .dvi-class{ display:flex; align-items:center; gap:8px; font-size:14px; margin-top:10px; flex-wrap:wrap; }
    .dvi-class .cat{ color:var(--teal); font-weight:500; }
    .dvi-class .chev{ color:var(--faint); }
    .dvi-class .sub{ color:var(--muted); }
    .dvi-title{ font-size:22px; font-weight:600; letter-spacing:-.3px; line-height:1.2; color:var(--ink); text-transform:capitalize; margin:0; }
    .dvi-desc{ margin-top:18px; }
    .dvi-desc .dk{ font-size:10.5px; font-weight:600; letter-spacing:.5px; text-transform:uppercase; color:var(--faint); margin-bottom:5px; }
    .dvi-desc .dv{ font-size:14px; color:var(--ink); line-height:1.5; }
    .dvi-facts{ display:flex; flex-wrap:wrap; gap:16px 26px; margin-top:20px; padding-top:18px; border-top:1px solid var(--line-2); }
    .dvi-fact{ display:flex; flex-direction:column; gap:6px; }
    .dvi-fact .k{ font-size:10.5px; font-weight:600; letter-spacing:.5px; text-transform:uppercase; color:var(--faint); }
    .dvi-fact .pill{ display:inline-flex; align-items:center; gap:7px; font-size:14px; font-weight:500; color:var(--ink); background:var(--line-2); padding:5px 12px; border-radius:20px; }
    .dvi-fact .ref-pill{ background:var(--teal-3); color:var(--teal); font-weight:600; }
    .dvi-fact .fv{ font-size:14px; font-weight:500; color:var(--ink); }
    .dvi-dot{ width:7px; height:7px; border-radius:50%; flex:none; }

    /* file link in the facts row — truncates, full name on hover via title= */
    .dvi-fact .filefact{ display:inline-flex; align-items:center; gap:7px; max-width:190px; font-size:14px; font-weight:500; color:var(--teal); text-decoration:none; }
    .dvi-fact .filefact svg{ color:var(--faint); flex:none; transition:color .15s; }
    .dvi-fact .filefact .fn{ white-space:nowrap; overflow:hidden; text-overflow:ellipsis; min-width:0; }
    .dvi-fact .filefact:hover .fn{ text-decoration:underline; }
    .dvi-fact .filefact:hover svg{ color:var(--teal); }
    .dvi-fact .filemore{ color:var(--teal); font-weight:600; font-size:12.5px; flex:none; cursor:default; }
    .dvi-fact .filenone{ font-size:14px; color:var(--faint); }

    /* ---- Employee rail ---- */
    .dvi-rail{ background:var(--teal-soft); border:1px solid var(--line); border-radius:var(--dvi-radius); padding:22px 24px; display:flex; flex-direction:column; gap:16px; height:100%; }
    .dvi-rail .rh{ font-size:11px; font-weight:600; letter-spacing:.7px; text-transform:uppercase; color:var(--teal); }
    .dvi-rail .who{ display:flex; align-items:center; gap:13px; }
    .dvi-av{ width:46px; height:46px; border-radius:50%; flex:none; object-fit:cover; background:var(--teal); color:#fff; display:grid; place-items:center; font-size:15px; font-weight:600; letter-spacing:.3px; }
    .dvi-rail .who .nm{ font-size:16.5px; font-weight:600; color:var(--ink); letter-spacing:-.2px; }
    .dvi-rail .krow{ display:flex; align-items:center; justify-content:space-between; gap:14px; padding:11px 0; border-bottom:1px solid var(--line); }
    .dvi-rail .krow:last-child{ border-bottom:none; padding-bottom:0; }
    .dvi-rail .krow .kk{ font-size:13px; color:var(--muted); flex:none; }
    .dvi-rail .krow .kv{ font-size:14px; font-weight:600; color:var(--ink); text-align:right; word-break:break-word; }
    .dvi-rail .krow .kv.link{ color:var(--teal); font-weight:500; text-decoration:none; }
    .dvi-rail .krow .kv.link:hover{ text-decoration:underline; }

    /* ---- History ---- */
    .dvi-hist{ margin-bottom:16px; padding:22px 24px; }
    .dvi-hist table{ width:100%; border-collapse:collapse; }
    .dvi-hist thead th{ font-size:10.5px; font-weight:600; letter-spacing:.4px; text-transform:uppercase; color:var(--faint); text-align:left; padding:0 10px 10px; border-bottom:1px solid var(--line); }
    .dvi-hist tbody td{ font-size:14px; color:var(--ink); padding:12px 10px; border-bottom:1px solid var(--line-2); vertical-align:top; }
    .dvi-hist tbody tr:last-child td{ border-bottom:none; }
    .dvi-hist .dvi-sub{ background:var(--line-2); border-radius:12px; padding:12px 14px; margin:0; }
    .dvi-hist .dvi-sub th{ font-size:10px; padding:0 8px 6px; }
    .dvi-hist .dvi-sub td{ font-size:13px; padding:8px; border-bottom:1px solid var(--line); }

    /* ---- Form ---- */
    .dvi-form-grid{ align-items:stretch; }
    .dvi-form-grid > [class*="col-"] > .dvi-card{ height:100%; display:flex; flex-direction:column; }
    .dvi-pad{ padding:22px 24px; flex:1 1 auto; display:flex; flex-direction:column; }
    .dvi-fld{ display:flex; flex-direction:column; gap:8px; margin-bottom:18px; }
    .dvi-fld.dvi-grow{ flex:1; }
    .dvi-fld.dvi-grow textarea{ flex:1; min-height:140px; }
    .dvi-fld label{ font-size:11px; font-weight:600; letter-spacing:.4px; text-transform:uppercase; color:var(--muted); }
    .dvi-frow{ display:grid; grid-template-columns:1fr 1fr auto; gap:14px; align-items:end; }
    .dvi-frow.dvi-two{ grid-template-columns:1fr 1fr; }
    @media (max-width:600px){ .dvi-frow, .dvi-frow.dvi-two{ grid-template-columns:1fr; } }
    .dvi-mfoot{ display:flex; justify-content:flex-end; margin-top:20px; }
    .dvi-hint{ font-size:12px; color:var(--faint); margin-top:6px; }

    /* The real file input sits invisibly on top of the "Upload file"
       button (.uploadFile-block's own site-wide overlay trick, needed so
       clicking the button opens the OS file picker) — that puts the INPUT,
       not the button, under the cursor, so .eb-btn-accent:hover/:active
       never fires on it. :hover/:active still bubble to this wrapper
       though, so re-apply the identical values from
       resorts._emotional_buttons_v2_styles here, scoped to just this
       button — not a new animation, the same one every other button here
       already has. */
    .uploadFile-btn:hover .eb-btn-accent{ background:var(--teal-2); color:#fff; transform:translateY(-2px); box-shadow:0 8px 18px -8px rgba(20,35,42,.25); }
    .uploadFile-btn:active .eb-btn-accent{ transition-duration:.07s; transform:translateY(0) scale(.94); box-shadow:0 1px 1px rgba(0,0,0,.04); }

    @media (max-width:900px){
        .dvi-hero{ margin-bottom:16px; }
    }
</style>
