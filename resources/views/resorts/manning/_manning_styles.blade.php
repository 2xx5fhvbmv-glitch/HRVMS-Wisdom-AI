{{--
    Presentation-only CSS for the Resort Configuration (Manning) redesign —
    tabs, table look, status/action styling. Everything is scoped under
    .manning-card so nothing leaks onto other pages. Reads tokens from the
    app's live shared palette (resorts/layouts/_design_tokens.blade.php) —
    no new colors. The controller already returns literal HTML for the
    status/action columns (`<span class="text-success">…</span>`,
    `.btn-lg-icon.icon-bg-green` etc.) — that's backend code, out of scope
    to touch, so this file restyles those exact existing classes rather
    than introducing new markup for them.
--}}
<style>
    /* tab bar */
    .manning-card { overflow: hidden; }
    .manning-tabs { display: flex; gap: 2px; padding: 6px 18px 0; border-bottom: 1px solid var(--line); overflow-x: auto; }
    .manning-tabs .nav-link {
        font-family: 'Poppins', sans-serif; font-size: 14px; font-weight: 500; color: var(--muted);
        background: transparent; border: none; padding: 13px 16px 14px; cursor: pointer;
        display: inline-flex; align-items: center; gap: 9px; white-space: nowrap;
        border-bottom: 2px solid transparent; margin-bottom: -1px; transition: color .15s;
        border-radius: 0;
    }
    .manning-tabs .nav-link:hover { color: var(--ink); }
    .manning-tabs .nav-link.active { color: var(--teal); font-weight: 600; border-bottom-color: var(--teal); background: transparent; }
    .manning-count {
        font-size: 11px; font-weight: 600; background: var(--neutral-bg); color: var(--muted);
        border-radius: 12px; padding: 1px 8px; font-variant-numeric: tabular-nums;
    }
    .manning-tabs .nav-link.active .manning-count { background: var(--teal-3); color: var(--teal); }
    @media (prefers-reduced-motion: reduce) { .manning-tabs .nav-link { transition: none; } }

    /* toolbar (search + add-new) replacing the old .card-title per panel */
    .manning-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 16px 18px; flex-wrap: wrap; }
    .manning-toolbar .manning-search { max-width: 280px; width: 100%; }

    /* table — inset header bar (floats inside the card, rounded ends,
       never touches the card edge — standing standard per
       HANDOFF_STANDARDS.md). .manning-thwrap wraps just the <table> in
       the view; padding here is what insets it. */
    .manning-thwrap { padding: 0 8px; }
    /* !important throughout this block: the app's own default.css has a
       blanket `.table thead th{padding:0px 10px 12px !important}` (for
       other, unrelated tables) plus a `th:first-child{padding-left:0
       !important}` — both !important, so they win over any plain
       .manning-card rule regardless of selector specificity, zeroing the
       top padding and crushing the first column's left padding (text sat
       flush against the header bar's top/left edge). Reclaiming with our
       own !important is the only way to actually override an !important
       rule — increasing specificity alone doesn't beat it. */
    .manning-card table thead th {
        font-size: 10.5px !important; font-weight: 600 !important; letter-spacing: .5px; text-transform: uppercase;
        color: var(--teal) !important; text-align: left; padding: 11px 14px !important;
        background: var(--teal-soft); white-space: nowrap;
        border-top: none; border-bottom: none;
    }
    /* default.css's first/last-child padding-zero rules go one selector
       level more specific than the base rule above (they include `tr` in
       the chain) — at equal !important, that wins on specificity, so ours
       needs the same `tr` to reliably override it (source order alone
       isn't enough once specificity ties, but isn't needed here — this
       makes ours the more specific rule outright). */
    .manning-card table thead tr th:first-child { border-radius: 9px 0 0 9px; padding-left: 14px !important; }
    .manning-card table thead tr th:last-child { border-radius: 0 9px 9px 0; padding-right: 14px !important; }
    .manning-card table tbody td { font-size: 13.5px; color: var(--ink); vertical-align: middle; }
    .manning-card table tbody td:first-child { font-weight: 600; }

    /* sort chevron — single indicator, hidden at rest, faint on hover,
       solid teal only on the actively-sorted column. Standing standard
       per HANDOFF_STANDARDS.md, replacing DataTables' default double
       up/down arrows.

       This app runs DataTables 2.1.5, whose sort indicator is NOT the
       classic sorting/sorting_asc/sorting_desc <th> classes — it's
       dt-orderable-asc/dt-orderable-desc (column is sortable at all) and
       dt-ordering-asc/dt-ordering-desc (column is the ACTIVE sort),
       rendered as ::before (▲, always faintly visible at opacity .125)
       and ::after (▼, same) on a <span class="dt-column-order"> that
       DataTables itself injects into the <th> — confirmed against the
       actual dataTables.min.css/js this app loads (checked before writing
       this, since blindly targeting the old 1.x classes here would have
       been a silent no-op). Sorting itself is entirely DataTables' own
       logic — untouched; Action stays orderable:false in the existing
       column config, so it gets no dt-column-order span and shows no
       indicator, matching the requirement without any extra markup. */
    .manning-card table.dataTable thead th.dt-orderable-asc,
    .manning-card table.dataTable thead th.dt-orderable-desc {
        cursor: pointer;
    }
    /* Kill DataTables' own up-arrow glyph (::before) outright — the
       single chevron below reuses ::after for both directions instead of
       stacking two glyphs. */
    .manning-card table.dataTable thead th.dt-orderable-asc span.dt-column-order::before,
    .manning-card table.dataTable thead th.dt-orderable-desc span.dt-column-order::before {
        content: none !important;
    }
    .manning-card table.dataTable thead th.dt-orderable-asc span.dt-column-order::after,
    .manning-card table.dataTable thead th.dt-orderable-desc span.dt-column-order::after {
        content: "" !important; display: block; position: absolute;
        left: 0; right: 0; top: 50%; transform: translateY(-50%); margin: 0 auto;
        width: 0; height: 0; border-left: 4px solid transparent; border-right: 4px solid transparent;
        border-top: 5px solid var(--teal); opacity: 0 !important;
        transition: opacity .15s, transform .15s;
    }
    .manning-card table.dataTable thead th.dt-orderable-asc:hover span.dt-column-order::after,
    .manning-card table.dataTable thead th.dt-orderable-desc:hover span.dt-column-order::after {
        opacity: .4 !important;
    }
    /* :hover itself adds a class-level selector, so a plain
       .dt-ordering-asc rule is LESS specific than the :hover rule above
       and would lose while the actively-sorted column is also being
       hovered (opacity stuck at .4 instead of solid 1). DataTables always
       keeps dt-orderable-* present alongside dt-ordering-* on the sorted
       th, so combining both classes here matches that specificity and
       reliably wins regardless of hover state. */
    .manning-card table.dataTable thead th.dt-orderable-asc.dt-ordering-asc span.dt-column-order::after,
    .manning-card table.dataTable thead th.dt-orderable-desc.dt-ordering-desc span.dt-column-order::after {
        opacity: 1 !important; /* solid on the actively-sorted column, hovered or not */
    }
    .manning-card table.dataTable thead th.dt-orderable-asc.dt-ordering-asc span.dt-column-order::after {
        transform: translateY(-50%) rotate(180deg); /* up = ascending */
    }
    @media (prefers-reduced-motion: reduce) {
        .manning-card table.dataTable thead th.dt-orderable-asc span.dt-column-order::after,
        .manning-card table.dataTable thead th.dt-orderable-desc span.dt-column-order::after { transition: none; }
    }

    /* Division/Department name columns — color to read as an identity
       chain, without touching the backend-returned plain-text cells. */
    #departments-table tbody td:nth-child(2),
    #sections-table tbody td:nth-child(2),
    #positions-table tbody td:nth-child(5) { color: var(--teal); font-weight: 500; }

    /* status — the controller returns literal .text-success/.text-danger
       spans; add the reference's status dot purely via ::before so the
       backend HTML never needs to change. */
    .manning-card table tbody td .text-success,
    .manning-card table tbody td .text-danger { display: inline-flex; align-items: center; gap: 7px; font-size: 12.5px; font-weight: 500; }
    .manning-card table tbody td .text-success::before,
    .manning-card table tbody td .text-danger::before {
        content: ''; width: 7px; height: 7px; border-radius: 50%; background: currentColor; flex: none;
    }

    /* action icon buttons — restyles the controller's existing
       .btn-lg-icon/.icon-bg-green/.icon-bg-red classes, doesn't add new
       ones, so the backend-returned action column needs no change.

       Unified (2026) with the row-action icon button used everywhere else
       in the app — .btn-tableIcon (25x25, 7px radius) + a light tint at
       rest, solid fill + white icon only on hover, no border/lift — same
       box Action config and SOS Team Management render natively via
       .btn-tableIcon.btnIcon-teal/.eb-icon-critical, and the same fix just
       applied to the Reports page's action column. Manning can't use that
       class directly (backend hardcodes .btn-lg-icon), so this reproduces
       the box+tint here instead of leaving Manning as a fourth one-off
       size (was 32px/9px — a self-introduced inconsistency, not this
       app's real convention).

       Colors: edit.svg/trash-red.svg are pre-colored SVGs (#004552 teal /
       #a90000 red baked into the file, not CSS `color`-able), so the tint
       stays teal/critical to match the glyph's own hue — same pairing
       .btnIcon-teal and .eb-icon-critical already use. The icon glyph
       itself turns white on hover via default.css's existing
       `.btn-lg-icon:hover img{filter:brightness(0) invert(1)}` —
       untouched, still in effect. */
    .manning-card .btn-lg-icon {
        width: 25px; height: 25px; border-radius: 7px; border: none;
        display: inline-flex; align-items: center; justify-content: center;
        transition: background .15s;
    }
    .manning-card .btn-lg-icon img { width: 13px; height: 13px; }
    .manning-card .btn-lg-icon.icon-bg-green { background: rgb(0 69 82 / 9%); }
    .manning-card .btn-lg-icon.icon-bg-green:hover { background: var(--teal); }
    .manning-card .btn-lg-icon.icon-bg-red { background: rgba(255, 36, 0, .09); }
    .manning-card .btn-lg-icon.icon-bg-red:hover { background: var(--critical); }
    @media (prefers-reduced-motion: reduce) { .manning-card .btn-lg-icon { transition: none; } }

    /* inline edit-in-place row */
    .manning-card table tbody tr.editing,
    .manning-card table tbody tr:has(.wfp-btn-primary.update-row-btn) { background: var(--teal-soft); }
</style>
