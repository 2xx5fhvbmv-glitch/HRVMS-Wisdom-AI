{{--
    Create Disciplinary — shared presentation-only CSS. Included once by
    create.blade.php (the single view/route rendered for every role that
    can open this screen — CreateDisciplinary() has no HOD/HR/EXCOM variant).

    Font sizes follow DASHBOARD_FONT_SIZES.md's production scale, consistent
    with the Disciplinary Investigation screen: 15px card/section titles,
    14px body/inputs, 11px uppercase labels, 12px small captions. No
    [data-theme] rules — the theme engine is disabled; do not reintroduce it.

    Spacing throughout (card padding, field gaps, list-item padding) is
    intentionally tight — the goal is for both .cdi-shell columns to fit a
    typical dataset in one screen with no scrollbar at all, not to fit via
    a capped/scrolling card. .cdi-ao-list keeps its own overflow-y:auto as
    a quiet safety net only — with no height cap paired to it, it stays
    invisible unless a genuinely unusual number of active offences would
    otherwise blow out the layout.
--}}
<style>
    .cdi-wrap{ --cdi-radius:16px; }

    .cdi-card{ background:var(--card); border:1px solid var(--line); border-radius:var(--cdi-radius); box-shadow:var(--shadow); padding:18px 20px; }
    .cdi-sec-h{ font-size:15px; font-weight:600; color:var(--ink); margin-bottom:14px; }

    .cdi-shell{ display:grid; grid-template-columns:1fr 360px; gap:16px; align-items:stretch; }
    @media (max-width:1100px){ .cdi-shell{ grid-template-columns:1fr; } }

    .cdi-grid2{ display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    .cdi-grid3{ display:grid; grid-template-columns:repeat(3,1fr); gap:14px; }
    @media (max-width:600px){ .cdi-grid2, .cdi-grid3{ grid-template-columns:1fr; } }
    .cdi-fld{ display:flex; flex-direction:column; gap:6px; margin-bottom:14px; }
    .cdi-fld:last-child{ margin-bottom:0; }
    .cdi-fld label{ font-size:11px; font-weight:600; letter-spacing:.4px; text-transform:uppercase; color:var(--muted); display:flex; align-items:center; gap:8px; }
    .cdi-fld .req{ color:var(--error); }
    .cdi-autobadge{ display:inline-flex; align-items:center; gap:4px; font-size:9px; font-weight:600; letter-spacing:.3px; text-transform:uppercase; color:var(--teal); background:var(--teal-3); padding:2px 7px; border-radius:20px; margin-left:auto; }
    .cdi-optional{ color:var(--faint); font-weight:500; text-transform:none; letter-spacing:0; }

    /* Locked/auto-filled select2 (the page's own .is-readonly class already
       drives the lock timing via JS — this just gives it the reference's
       muted "auto" look instead of the site-wide default select2 chrome). */
    .cdi-fld .select2.is-readonly .select2-selection{ background:var(--line-2) !important; border-color:var(--line) !important; color:var(--muted) !important; cursor:not-allowed !important; }

    /* The site-wide .form-control padding (14px 18px 15px) and Select2's
       resulting rendered height (~55px per field) are the single biggest
       contributor to this form's total height — with 11 fields on this
       page that's the real reason spacing/gap trims alone can't get it to
       fit one screen. Compact just the controls on this page instead of
       the shared global input/select2 styling. */
    .cdi-card .ctrl.form-control{ padding:10px 14px; }
    .cdi-wrap .select2-container .select2-selection--single{ height:42px; }
    .cdi-wrap .select2-container--default .select2-selection--single .select2-selection__rendered{ line-height:40px; padding-left:14px; }
    .cdi-wrap .select2-container--default .select2-selection--single .select2-selection__arrow{ height:40px; }
    .cdi-wrap .select2-container--default.select2-container--multiple .select2-selection--multiple{ min-height:42px; }

    .cdi-upload{ display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
    .cdi-upload .hint{ font-size:12px; color:var(--faint); }
    .cdi-files{ display:flex; flex-direction:column; gap:4px; margin-top:6px; list-style:none; padding:0; }
    .cdi-filerow{ display:flex; align-items:center; gap:9px; max-width:100%; font-size:14px; }
    .cdi-filerow svg{ color:var(--faint); flex:none; }
    .cdi-filerow .fn{ white-space:nowrap; overflow:hidden; text-overflow:ellipsis; min-width:0; color:var(--ink); font-weight:500; }

    .cdi-mfoot{ display:flex; justify-content:flex-end; margin-top:12px; padding-top:12px; border-top:1px solid var(--line-2); }

    /* Same fix as the Disciplinary Investigation page's Upload file button:
       the real file input sits invisibly on top of the "Upload file" button
       (.uploadFile-block's site-wide overlay trick, needed so clicking the
       button opens the OS file picker) — that puts the INPUT, not the
       button, under the cursor, so .eb-btn-accent:hover/:active never fires
       on it. :hover/:active still bubble to this wrapper though, so
       re-apply the identical values from resorts._emotional_buttons_v2_styles
       here, scoped to just these two buttons (Attachment + Signed document
       share the same .uploadFile-btn class, so one rule covers both). */
    .uploadFile-btn:hover .eb-btn-accent{ background:var(--teal-2); color:#fff; transform:translateY(-2px); box-shadow:0 8px 18px -8px rgba(20,35,42,.25); }
    .uploadFile-btn:active .eb-btn-accent{ transition-duration:.07s; transform:translateY(0) scale(.94); box-shadow:0 1px 1px rgba(0,0,0,.04); }

    /* ---- Active Offences — vertical stacked list ---- */
    .cdi-ao{ display:flex; flex-direction:column; padding:18px 20px; height:100%; }
    .cdi-ao .cdi-ao-h{ display:flex; align-items:center; justify-content:space-between; margin-bottom:4px; }
    .cdi-ao .cdi-ao-h .ttl{ font-size:15px; font-weight:600; color:var(--ink); }
    .cdi-ao .cdi-ao-h .cnt{ font-size:11px; font-weight:600; color:var(--muted); background:var(--line-2); padding:2px 9px; border-radius:20px; font-variant-numeric:tabular-nums; }
    .cdi-ao .cdi-ao-sub{ font-size:12px; color:var(--faint); margin-bottom:10px; }
    .cdi-ao-list{ flex:1; min-height:0; overflow-y:auto; display:flex; flex-direction:column; gap:8px; padding-right:2px; }
    .cdi-ao-empty{ font-size:14px; color:var(--faint); padding:6px 0; }
    .cdi-ao-item{ display:block; border:1px solid var(--line); border-radius:10px; padding:10px 12px; text-decoration:none; color:inherit; transition:border-color .15s, background .15s; }
    .cdi-ao-item:hover{ border-color:var(--teal-3); background:var(--teal-soft); }
    .cdi-ao-top{ display:flex; align-items:flex-start; justify-content:space-between; gap:10px; }
    .cdi-ao-off{ font-size:14px; font-weight:600; color:var(--ink); line-height:1.25; }
    .cdi-ao-act{ font-size:10.5px; font-weight:600; padding:2px 8px; border-radius:20px; white-space:nowrap; flex:none; }
    .cdi-ao-act.written{ background:var(--warning-bg); color:var(--warning); }
    .cdi-ao-act.verbal{ background:var(--line-2); color:var(--muted); }
    .cdi-ao-act.final{ background:var(--error-bg); color:var(--error); }
    /* Category + valid-until share one line instead of stacking as two,
       dropping the border-top divider row that used to sit between them —
       the single biggest per-item saving once the form's own fields were
       already compacted (7 real entries at the old ~96px each was what
       kept forcing this card taller than the form). */
    .cdi-ao-meta{ display:flex; align-items:baseline; justify-content:space-between; gap:10px; margin-top:3px; font-size:12px; }
    .cdi-ao-cat{ color:var(--muted); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; min-width:0; }
    .cdi-ao-valid{ color:var(--faint); flex:none; }
    .cdi-ao-valid b{ color:var(--ink); font-weight:600; }

    /* DataTables engine stays bound to a real <table> (server-side ajax) —
       only the table body itself is hidden; the drawCallback renders the
       visible cards above. */
    .cdi-ao-datatable-host{ display:none; }
    .cdi-ao .dataTables_processing{ font-size:12px; color:var(--faint); text-align:center; }
</style>
