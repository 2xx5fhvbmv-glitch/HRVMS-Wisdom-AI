{{--
    Incident Meeting Create — shared presentation-only CSS. Included once by
    create.blade.php (the single view/route rendered for every role that can
    open this screen — incident.meeting.create has no HOD/HR/EXCOM variant).

    Font sizes follow DASHBOARD_FONT_SIZES.md's production scale, consistent
    with the Incident Investigation screen: 15px card titles, 14px body/
    inputs, 11px uppercase field labels, 12px small captions/timestamps. No
    [data-theme] rules — the theme engine is disabled; do not reintroduce it.
--}}
<style>
    .imc-wrap{ --imc-brass:#B08D57; }

    .imc-card{ background:var(--card); border:1px solid var(--line); border-radius:16px; box-shadow:var(--shadow); padding:22px 24px; margin-bottom:16px; }
    .imc-sec-h{ font-size:15px; font-weight:600; color:var(--ink); margin-bottom:18px; }

    .imc-grid3{ display:grid; grid-template-columns:repeat(3,1fr); gap:16px; }
    @media (max-width:900px){ .imc-grid3{ grid-template-columns:1fr 1fr; } }
    @media (max-width:600px){ .imc-grid3{ grid-template-columns:1fr; } }
    .imc-fld{ display:flex; flex-direction:column; gap:8px; }
    .imc-fld label{ font-size:11px; font-weight:600; letter-spacing:.4px; text-transform:uppercase; color:var(--muted); }
    .imc-fld .req{ color:var(--error); margin-left:2px; }
    .imc-fld .ctrl.ro{ background:var(--teal-3); border-color:var(--teal-3); color:var(--teal); font-weight:600; cursor:default; }

    /* ---- Participants ---- */
    .imc-pcols{ display:grid; grid-template-columns:1fr 1fr; gap:0; }
    .imc-pcols > .imc-pcol:first-child{ padding-right:28px; }
    .imc-pcols > .imc-pcol:last-child{ padding-left:28px; border-left:1px solid var(--line-2); }
    @media (max-width:820px){
        .imc-pcols{ grid-template-columns:1fr; }
        .imc-pcols > .imc-pcol:first-child{ padding-right:0; }
        .imc-pcols > .imc-pcol:last-child{ padding-left:0; border-left:none; border-top:1px solid var(--line-2); padding-top:20px; margin-top:4px; }
    }
    .imc-pcol-h{ font-size:11px; font-weight:600; letter-spacing:.5px; text-transform:uppercase; color:var(--muted); margin-bottom:14px; }

    .imc-prow{ display:flex; align-items:flex-end; gap:12px; padding:12px; background:var(--line-2); border:1px solid var(--line); border-radius:12px; margin-bottom:10px; }
    .imc-prow:last-child{ margin-bottom:0; }
    .imc-prow .imc-pfields{ flex:1; display:grid; grid-template-columns:1fr 1fr; gap:12px; min-width:0; }
    @media (max-width:1100px){ .imc-prow .imc-pfields{ grid-template-columns:1fr; } }

    .imc-ext-block{ margin-top:20px; padding-top:18px; border-top:1px solid var(--line-2); }
    .imc-extrow{ display:flex; gap:12px; align-items:center; margin-bottom:10px; }
    .imc-extrow:last-child{ margin-bottom:0; }
    .imc-extrow .ctrl{ flex:1; }

    /* ---- Attachments ---- */
    .imc-upload{ display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
    .imc-upload .hint{ font-size:12px; color:var(--faint); }
    .imc-files{ display:flex; flex-direction:column; gap:6px; margin-top:12px; list-style:none; padding:0; }
    .imc-filerow{ display:flex; align-items:center; gap:9px; max-width:100%; font-size:14px; }
    .imc-filerow svg{ color:var(--faint); flex:none; }
    .imc-filerow .fn{ white-space:nowrap; overflow:hidden; text-overflow:ellipsis; min-width:0; color:var(--ink); font-weight:500; }
    .imc-filerow .fsz{ color:var(--faint); font-size:11px; flex:none; margin-left:auto; padding-left:10px; }

    /* ---- Previous notes ---- */
    .imc-notes{ display:flex; flex-direction:column; }
    .imc-note{ display:flex; align-items:flex-start; justify-content:space-between; gap:18px; padding:14px 0; border-bottom:1px solid var(--line-2); }
    .imc-note:first-child{ padding-top:0; }
    .imc-note:last-child{ border-bottom:none; padding-bottom:0; }
    .imc-note-t{ font-size:14px; font-weight:600; color:var(--ink); }
    .imc-note-d{ font-size:14px; color:var(--muted); margin-top:3px; line-height:1.5; }
    .imc-note-time{ font-size:12px; color:var(--faint); white-space:nowrap; flex:none; margin-top:1px; }
    .imc-empty{ display:flex; flex-direction:column; align-items:center; text-align:center; padding:26px 20px; gap:8px; }
    .imc-empty .g{ width:46px; height:46px; border-radius:14px; background:var(--teal-soft); color:var(--teal); display:grid; place-items:center; }
    .imc-empty .t{ font-size:14px; font-weight:600; color:var(--ink); }
    .imc-empty .s{ font-size:13px; color:var(--muted); max-width:340px; }

    .imc-footer-bar{ display:flex; justify-content:flex-end; gap:12px; margin-top:4px; }
</style>
