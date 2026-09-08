{{--
    Shared "list modal" chrome — the standard for every "here's the list"
    popup app-wide (Pending Departments, Pending Positions, "N employees"
    drill-lists, etc.): clean modal card + title + optional subtitle + count
    chip + a styled, internally-scrollable table. Include once per page,
    then build each modal's own markup with these classes — only the
    title/subtitle/columns/data change.

    Sits INSIDE the existing Bootstrap .modal/.modal-dialog/.modal-content
    wrapper (keeps Bootstrap's own show/hide, backdrop-click, and Esc
    handling — no new JS needed for any of that). All rules are scoped
    under .list-modal so this can't leak onto unrelated tables/modals on
    the same page. Colours are the app's existing design tokens
    (_design_tokens.blade.php) — no new palette introduced.
--}}
<style>
    .list-modal-dialog{ max-width:460px; }
    .list-modal{
        border:0; border-radius:20px; overflow:hidden;
        box-shadow:0 24px 60px rgba(var(--teal-rgb),.20), 0 4px 14px rgba(var(--teal-rgb),.08);
    }
    .list-modal .lm-head{
        display:flex; align-items:flex-start; gap:12px;
        padding:22px 16px 16px; border:0;
    }
    .list-modal .lm-titles{ flex:1; min-width:0; }
    .list-modal .lm-title{ font-size:18px; font-weight:600; color:var(--ink); letter-spacing:-.2px; }
    .list-modal .lm-sub{
        font-size:12.5px; color:var(--muted); margin-top:4px; line-height:1.4;
        white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    }
    /* The count chip: lives inline in .lm-tcap (right after the caption
       text) rather than in the header, so .lm-sub keeps the full header
       width to itself and fits on one line. */
    .list-modal .lm-count{
        font-size:11px; font-weight:600; color:var(--teal); background:var(--teal-3);
        border-radius:20px; padding:3px 10px; font-variant-numeric:tabular-nums;
        display:inline-block; vertical-align:middle; margin-left:6px; text-transform:none;
        letter-spacing:normal;
    }
    .list-modal .lm-x{
        width:30px; height:30px; border-radius:50%; background:transparent; border:none;
        color:var(--faint); cursor:pointer; display:grid; place-items:center; flex:none;
        transition:background .15s ease, color .15s ease;
    }
    .list-modal .lm-x:hover{ background:var(--line-2); color:var(--ink); }
    @media (prefers-reduced-motion: reduce){
        .list-modal .lm-x{ transition:none; }
    }

    .list-modal .lm-tablewrap{
        margin:0 16px 16px; border:1px solid var(--line); border-radius:14px; overflow:hidden;
    }
    .list-modal .lm-tcap{
        font-size:10px; font-weight:600; letter-spacing:.5px; text-transform:uppercase;
        color:var(--faint); padding:11px 16px; border-bottom:1px solid var(--line); background:var(--teal-soft);
    }
    .list-modal .lm-tscroll{ max-height:326px; overflow-y:auto; }
    .list-modal .lm-tscroll::-webkit-scrollbar{ width:8px; }
    .list-modal .lm-tscroll::-webkit-scrollbar-thumb{ background:var(--neutral-bg); border-radius:5px; border:2px solid #fff; }

    .list-modal table{ width:100%; border-collapse:collapse; margin:0; }
    .list-modal thead th{
        position:sticky; top:0; background:#fff; font-size:10px; font-weight:600;
        letter-spacing:.4px; text-transform:uppercase; color:var(--faint); text-align:left;
        padding:11px 16px; border-bottom:1px solid var(--line);
    }
    .list-modal thead th:first-child{ width:74px; }
    .list-modal tbody td{
        padding:12px 16px; border-bottom:1px solid var(--line-2); font-size:14px; vertical-align:middle;
    }
    .list-modal tbody tr:last-child td{ border-bottom:none; }
    .list-modal tbody tr:hover td{ background:var(--teal-soft); }
    @media (prefers-reduced-motion: reduce){
        .list-modal tbody tr td{ transition:none; }
    }
    /* First/second column styled positionally (Sr No / list value) rather
       than by class, since row markup for some existing lists is built
       server-side without column classes — this way the same stylesheet
       works whether or not a given caller's rows carry .sr/.nm. */
    .list-modal tbody td:first-child{ color:var(--faint); font-variant-numeric:tabular-nums; width:74px; }
    .list-modal tbody td:nth-child(2){ font-weight:500; color:var(--ink); }
    .list-modal tbody td.sr{ color:var(--faint); font-variant-numeric:tabular-nums; width:74px; }
    .list-modal tbody td.nm{ font-weight:500; color:var(--ink); }
</style>
