{{--
    Presentation-only CSS for the Action (disciplinary) config table redesign —
    search-only toolbar, inset header bar, capped-width description column,
    inline edit-in-place row. Scoped under .ac-card so nothing leaks onto
    other pages. Reads tokens from the app's live shared palette
    (resorts/layouts/_design_tokens.blade.php) — no new colors. Same
    inset-header-bar / sort-free-table conventions as the Manning redesign
    (resorts.manning._manning_styles), just re-scoped for this single table.
--}}
<style>
    /* toolbar — search box only, no default DataTables filter UI (the JS
       sets dom:"rtip" and drives DataTables' .search() from this input
       instead, same pattern as Manning's #divisions-search etc). */
    .ac-toolbar { display: flex; align-items: center; justify-content: flex-end; padding: 16px 18px; }
    .ac-search { max-width: 320px; width: 100%; }
    .ac-search .form-control { padding: 10px 18px; border-radius: 13px !important; }
    .ac-search > i { top: 20px; color: var(--teal); }

    /* table — inset header bar (floats inside the card, rounded ends,
       matches the Manning table standard). .ac-thwrap wraps just the
       <table>; padding here is what insets it. */
    .ac-thwrap { padding: 0 8px; }
    /* !important throughout: default.css's own blanket
       `.table thead th{padding:0px 10px 12px !important}` plus
       first/last-child padding-zero rules are !important too, so a plain
       rule here would be silently overridden — same fix as Manning. */
    .ac-card table thead th {
        font-size: 10.5px !important; font-weight: 600 !important; letter-spacing: .5px; text-transform: uppercase;
        color: var(--teal) !important; text-align: left; padding: 11px 14px !important;
        background: var(--teal-soft); white-space: nowrap;
        border-top: none; border-bottom: none;
    }
    .ac-card table thead tr th:first-child { border-radius: 9px 0 0 9px; padding-left: 14px !important; }
    .ac-card table thead tr th:last-child { border-radius: 0 9px 9px 0; padding-right: 14px !important; }
    .ac-card table thead th.ac-th-name { width: 300px; }
    .ac-card table thead th.ac-th-act { width: 120px; text-align: right; }
    .ac-card table tbody td { font-size: 13.5px; color: var(--ink); vertical-align: middle; }
    .ac-card table tbody td.ac-td-name { font-weight: 600; }
    .ac-card table tbody td.ac-actcell { text-align: right; }
    /* the controller's action-column HTML wraps the icons in its own
       <div class="d-flex align-items-center"> — a flex container ignores
       the cell's text-align, so it needs its own justify-content to sit
       under the right-aligned "Action" header. */
    .ac-card table tbody td.ac-actcell .d-flex { justify-content: flex-end; }

    /* description — capped so it never stretches edge-to-edge on the
       fluid canvas; the cell itself stays auto-width, only the inner div
       is capped, so remaining space is just quiet whitespace. */
    .ac-desc { max-width: 760px; color: var(--muted); line-height: 1.55; }

    /* action icon buttons already come from the app-wide .btn-tableIcon /
       .btnIcon-yellow / .eb-icon-critical classes the controller returns —
       untouched, no restyle needed here. */

    /* inline edit-in-place row — labelled fields, teal Save + outlined
       Cancel, one row at a time (same visual language as the Training
       Sessions / Learning Schedule inline edit). */
    .ac-card table tbody tr.editing { background: var(--teal-soft); }
    .ac-ef { display: flex; flex-direction: column; gap: 5px; }
    .ac-ef .ac-lbl { font-size: 9.5px; font-weight: 600; letter-spacing: .5px; text-transform: uppercase; color: var(--faint); }
    .ac-inp.form-control { font-size: 13px; color: var(--ink); background: #fff; border: 1px solid var(--line); border-radius: 10px; padding: 9px 11px; }
    .ac-inp.form-control:focus { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(var(--teal-rgb), .10); }
    textarea.ac-inp.form-control { min-height: 84px; line-height: 1.55; resize: vertical; max-width: 760px; }
    .ac-rowbtns { display: flex; justify-content: flex-end; gap: 8px; }
    .ac-rowbtn { height: 38px; padding: 0 16px; border-radius: 10px; font-size: 13px; font-weight: 600; white-space: nowrap; transition: background .15s, border-color .15s, color .15s; }
    .ac-rowbtn.ac-save { background: var(--teal); border-color: var(--teal); color: #fff; }
    .ac-rowbtn.ac-save:hover { background: var(--teal-2); border-color: var(--teal-2); color: #fff; }
    .ac-rowbtn.ac-cancel { background: #fff; border-color: var(--line); color: var(--muted); }
    .ac-rowbtn.ac-cancel:hover { border-color: var(--faint); color: var(--ink); }
    @media (prefers-reduced-motion: reduce) { .ac-rowbtn { transition: none; } }
</style>
