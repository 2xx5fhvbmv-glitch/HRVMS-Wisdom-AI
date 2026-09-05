{{--
    Shared presentation-only CSS for Phase 2 of the TA dashboard redesign:
    New Hire Requests, Open Vacancies, Top Hiring Sources, Approval History.
    Included once by admindashboard/hrdashboard/hoddashboard. Every rule here
    is scoped under a new "-v2" class so it can never leak onto the many
    OTHER modules that reuse the same base class names (hireReq-block,
    img-circle, table-vacRec, etc. — e.g. master-dashboard, Performance,
    Survey, timeandattendance all render .hireReq-block too).
--}}
<style>
    /* --- New Hire Requests ---
       Phase 2a tokenisation: exact-hex matches now point at the SSOT
       (--line/--muted/--ink/--faint/--teal/--teal-3). #fff on
       .hireReq-initials stays literal — contrast-on-a-colored-avatar
       text, not a surface. #F1F7F7 (row hover tint) has no exact token
       match (nearest is --teal-soft #F5F8F8, off by 4 in R — outside the
       ≤2-per-channel near-duplicate bar) — left literal. */
    .hireReq-card-v2 { border: 1px solid var(--line); }
    .hireReq-card-v2 .hireReq-initials {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 600;
        font-size: 13px;
    }
    .hireReq-card-v2 .hireReq-level-badge {
        margin-left: 6px;
        font-size: 10px;
        padding: 2px 8px;
        vertical-align: 1px;
    }
    .hireReq-card-v2 .hireReq-block p { color: var(--muted); }
    .hireReq-card-v2 .hireReq-block h6 { color: var(--ink); }
    .hireReq-card-v2 .hireReq-empty { color: var(--muted); margin: 0; }

    /* --- Open Vacancies ---
       Header font-size is intentionally NOT overridden here — it inherits
       the site-wide .table thead th rule (16px) so it matches the body
       row text (position names use the site-wide .table tbody td 14px)
       instead of looking undersized next to it. */
    .vac-table-v2 thead th {
        background: var(--teal-3) !important;
        color: var(--teal);
        border-bottom: 0 !important;
        /* The site-wide .table thead th rule sets padding: 0 10px 12px
           !important — zero top padding — which left header text sitting
           flush against the top of the card with no breathing room above
           it. Restoring a top padding here vertically centers it to match
           the existing 12px bottom padding. */
        padding-top: 12px !important;
    }
    .vac-table-v2 tbody tr {
        transition: background .15s ease;
    }
    .vac-table-v2 tbody tr:hover {
        background: var(--line-2);
    }
    .vac-table-v2 .vac-col-num,
    .vac-table-v2 th.vac-col-action {
        text-align: center !important;
        padding-left: 12px !important;
        padding-right: 12px !important;
    }
    .vac-table-v2 .vac-col-num {
        font-variant-numeric: tabular-nums;
        font-weight: 600;
        color: var(--ink);
    }
    .vac-table-v2 .vac-col-num.vac-col-muted {
        color: var(--faint);
        font-weight: 500;
    }
    .vac-table-v2 td.vac-col-action {
        text-align: center !important;
        padding-right: 0 !important;
    }

    /* --- Top Hiring Sources: empty state --- */
    .ta-chart-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 48px 20px;
        color: var(--faint);
        min-height: 260px;
    }
    .ta-chart-empty i {
        font-size: 28px;
        color: var(--faint);
        margin-bottom: 12px;
    }
    .ta-chart-empty p {
        margin: 0;
        font-size: 14px;
        line-height: 1.6;
        color: var(--muted);
    }

    /* --- Approval History --- */
    .appr-history-v2 { border: 1px solid var(--line); }
    .appr-history-v2 .appr-subtitle {
        font-size: 14px;
        color: var(--muted);
        font-weight: 500;
    }
    /* Caps the list to roughly 3-4 visible rows (same max-height convention
       already used by .hireReq-main/.talentPool-main elsewhere on this
       dashboard) so a long history scrolls internally instead of growing
       the card indefinitely. */
    .appr-history-list {
        max-height: 350px;
        overflow-y: auto;
        padding-right: 4px;
    }
    /* Grid, not flex — every row always has exactly 3 approval stages in a
       fixed HR/Finance/GM order (missing ones render as a placeholder
       pill, see the "is_missing" handling below), so a 4-column grid
       (Position | HR | Finance | GM) lines each one up at the same X
       position on every row, like a table, instead of packing left based
       on that row's own content width (which is what flex was doing —
       first stretching the pills apart across the row, then, once that
       was fixed, leaving a gap between Position and HR that grew or
       shrank per row). */
    .appr-row {
        display: grid;
        grid-template-columns: 200px repeat(3, minmax(150px, 1fr));
        align-items: start;
        column-gap: 16px;
        row-gap: 6px;
        padding: 14px 0;
        border-bottom: 1px solid var(--line);
    }
    .appr-row:last-child { border-bottom: 0; padding-bottom: 0; }
    .appr-row:first-child { padding-top: 0; }
    .appr-row-heading { grid-column: 1; padding-top: 2px; min-width: 0; }
    .appr-row-heading h6 { margin-bottom: 2px; color: var(--ink); font-weight: 600; }
    .appr-row-heading span { font-size: 14px; color: var(--muted); }
    /* display: contents removes .appr-chain from the box model entirely —
       its 3 .appr-chain-item children become direct grid items of
       .appr-row above (columns 2/3/4), without needing to change the
       Blade markup that still wraps them in this div. */
    .appr-chain { display: contents; }
    .appr-chain-item:nth-child(1) { grid-column: 2; }
    .appr-chain-item:nth-child(2) { grid-column: 3; }
    .appr-chain-item:nth-child(3) { grid-column: 4; }
    /* Each approval (HR/Finance/GM) keeps its OWN timestamp underneath its
       pill — a single shared date for the whole group would misrepresent
       the other two approvals, which genuinely happen at different times. */
    .appr-chain-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .appr-chain-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 11px;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 20px;
        white-space: nowrap;
    }
    /* Approved/Hold/Pending are exact-hex matches for --positive/--warning/
       --muted, now pointing there too — their rgba(…) tints stay literal
       (no --positive-rgb/--warning-rgb/--muted-rgb primitive exists; this
       phase adds no new tokens). Rejected/Missing's #C23A3A is now
       --rejected (Phase 2b addition — distinct from --critical/--error).
       --rejected-bg's Light value is exactly Rejected's rgba(194,58,58,.1)
       background; Missing's OTHER two alphas (.08 background, .45 border)
       are genuinely different values, not the same token — left literal. */
    .appr-chain-pill i { font-size: 10px; }
    .appr-chain-pill-approved { color: var(--positive); background: rgba(31, 157, 107, .1); }
    .appr-chain-pill-rejected { color: var(--rejected); background: var(--rejected-bg); }
    .appr-chain-pill-hold { color: var(--warning); background: rgba(217, 138, 0, .1); }
    /* Pending stage — muted/neutral so it reads as "not decided yet" rather
       than competing with the green (approved) / red (rejected) / amber
       (hold) states that represent an actual completed action. */
    .appr-chain-pill-pending { color: var(--muted); background: rgba(93, 111, 117, .1); }
    /* A stage with no approval record at all (a data gap, not a normal
       queued/pending step) — a distinct red variation, dashed border so
       it doesn't read as "Rejected" (which uses a solid fill). */
    .appr-chain-pill-missing {
        color: var(--rejected);
        background: rgba(194, 58, 58, .08);
        border: 1px dashed rgba(194, 58, 58, .45);
    }
    .appr-chain-date-missing { color: var(--rejected); }
    .appr-chain-date {
        font-size: 11px;
        color: var(--faint);
        padding-left: 2px;
    }

    /* --- Top Hiring Sources / Top Countries / WAI Insights / New Hire
       Requests row — same fixed height (matches the 450px already used by
       WAI Insights/Talent Pool elsewhere) so all four bottoms line up,
       with each card's own content area scrolling internally instead.
       Scoped under .ta-toprow-section (only present on hrdashboard's new
       full-width row) rather than the bare .ta-toprow-card class, since
       _top_hiring_sources.blade.php and _new_hire_requests_card.blade.php
       are also loaded by admindashboard.blade.php in a different layout —
       this must not force a height there too. */
    .ta-toprow-section .ta-toprow-card {
        height: 450px !important;
        max-height: 450px !important;
        display: flex;
        flex-direction: column;
    }
    .ta-toprow-section .ta-toprow-card .ta-toprow-scroll {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
    }
    /* .hireReq-main's own site-wide rule caps it at max-height:350px with
       no flex — override just inside this scoped card so it fills the
       remaining space of the fixed 450px card instead. */
    .ta-toprow-section .ta-toprow-card.hireReq-card-v2 .hireReq-main {
        flex: 1 1 auto;
        min-height: 0;
        max-height: none;
    }

    /* Spacing between the main content row above and this full-width row —
       Bootstrap's row gutters (g-3/g-xxl-4) only add space BETWEEN columns
       within the same row, not between two separate sibling rows. */
    .ta-toprow-section {
        margin-top: 1rem;
    }
    @media (min-width: 1400px) {
        .ta-toprow-section {
            margin-top: 1.5rem;
        }
    }
</style>
