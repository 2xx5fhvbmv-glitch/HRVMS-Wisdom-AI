{{--
    Presentation-only CSS for the Performance emotional-button pass — same
    system as Leave/Payroll/Talent Acquisition/Time and Attendance, scoped
    to perf- prefixed classes so nothing here leaks onto other modules
    still using btn-theme/btn-themeBlue/btn-themeSkyblue/btn-danger/etc.

    :active rules are declared LAST, after every :hover rule — both are
    simultaneously true on a real click and the later-declared rule needs
    to win at equal specificity for the press/"zoom" feedback to show.
--}}
<style>
    .perf-btn-primary, .perf-btn-secondary, .perf-btn-positive,
    .perf-btn-accent, .perf-btn-hero, .perf-btn-neutral, .perf-btn-critical {
        transition: transform .16s cubic-bezier(.2,.8,.2,1), box-shadow .16s ease, background .16s ease, border-color .16s ease;
    }
    .perf-btn-primary:focus-visible, .perf-btn-secondary:focus-visible, .perf-btn-positive:focus-visible,
    .perf-btn-accent:focus-visible, .perf-btn-hero:focus-visible, .perf-btn-neutral:focus-visible, .perf-btn-critical:focus-visible {
        outline: 2px solid #014653;
        outline-offset: 2px;
    }

    /* Main forward action — Submit/Save/Send, wizard Next/final Submit,
       Attach, Approve-request-style submits. */
    .perf-btn-primary { background: #014653; color: #fff; }
    .perf-btn-primary:hover { background: #014653; color: #fff; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.35); }

    /* Low-stakes, recedes on purpose — View/View Details/Back/View all/
       View Archived/Download/Export, KPI Config nav, wizard Back. */
    .perf-btn-secondary { background: transparent; color: #014653; border: 1.5px solid #C9D6D7; }
    .perf-btn-secondary:hover { background: #F9F8F1; border-color: #014653; color: #014653; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.18); }

    /* Affirming — Approve, Restore (bringing an archived plan back). */
    .perf-btn-positive { background: #E4F3E9; color: #2E9E5B; }
    .perf-btn-positive:hover { background: #2E9E5B; color: #fff; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.25); }

    /* Additive/construction, general — Create KPI/Meeting/Template, Add to
       PIP/PDP, Add More (repeatable rows), Add Training, Add New. Neutral
       teal tint, not the brand lime — lime is reserved for the one hero
       CTA per page (see .perf-btn-hero) so it doesn't get diluted by
       every "add" button on the page reading as equally important. */
    .perf-btn-accent { background: #E6F0F1; color: #035b6c; }
    .perf-btn-accent:hover { background: #035b6c; color: #fff; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.25); }

    /* The brand lime — reserved for a single standout hero CTA per page
       (e.g. "Create New Cycle" on an otherwise-empty state), not blanketed
       across every additive button. Also what actually survives against
       the dark teal band behind the page header (body::before) — a
       transparent/dark-text button disappears into that band, lime doesn't. */
    .perf-btn-hero { background: #E0FF02; color: #17260a; }
    .perf-btn-hero:hover { background: #E0FF02; color: #17260a; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.35); }

    /* Backing out / low-stakes state change, not a decision — Cancel,
       Close, Clear (filters), Archive, Remove an unsaved row. */
    .perf-btn-neutral { background: #DEDEDE; color: #222; }
    .perf-btn-neutral:hover { background: #F5F8F8; color: #222; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.18); }

    /* The hard-stop decision — Reject, Delete, Remove from PIP/PDP. */
    .perf-btn-critical { background: #FFDED9; color: #FF2400; }
    .perf-btn-critical:hover { background: #FF2400; color: #fff; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.25); }

    .perf-btn-primary:active, .perf-btn-secondary:active, .perf-btn-positive:active,
    .perf-btn-accent:active, .perf-btn-hero:active, .perf-btn-neutral:active, .perf-btn-critical:active {
        transition-duration: .07s;
        transform: translateY(0) scale(.94);
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
    }

    /* Disabled state — without this, the sitewide `.btn[disabled]` rule
       (background: #6fa329, an olive green unrelated to any brand color)
       wins on specificity and silently repaints any disabled button in
       this system that color. */
    .perf-btn-primary[disabled], .perf-btn-secondary[disabled], .perf-btn-positive[disabled],
    .perf-btn-accent[disabled], .perf-btn-hero[disabled], .perf-btn-neutral[disabled], .perf-btn-critical[disabled] {
        opacity: .55;
        cursor: not-allowed;
        transform: none;
    }
    .perf-btn-primary[disabled] { background: #014653; color: #fff; }
    .perf-btn-secondary[disabled] { background: transparent; color: #014653; }
    .perf-btn-positive[disabled] { background: #E4F3E9; color: #2E9E5B; }
    .perf-btn-accent[disabled] { background: #E6F0F1; color: #035b6c; }
    .perf-btn-hero[disabled] { background: #E0FF02; color: #17260a; }
    .perf-btn-neutral[disabled] { background: #DEDEDE; color: #222; }
    .perf-btn-critical[disabled] { background: #FFDED9; color: #FF2400; }

    /* .page-hedding sits directly on the dark teal body::before band
       (--green, #014653, 315px tall behind every page header). Secondary's
       transparent-bg + dark-teal-text combo disappears into that band —
       only the faint light border shows. Scoped override: Secondary keeps
       its normal look everywhere else (inside a .card, which has its own
       opaque white background), just goes light-on-teal here instead. */
    .page-hedding .perf-btn-secondary { color: #fff; border-color: rgba(255,255,255,.55); }
    .page-hedding .perf-btn-secondary:hover { background: rgba(255,255,255,.14); border-color: #fff; color: #fff; }

</style>
