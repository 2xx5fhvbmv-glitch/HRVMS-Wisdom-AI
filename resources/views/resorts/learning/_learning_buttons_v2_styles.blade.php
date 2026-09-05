{{--
    Presentation-only CSS for the Learning & Development emotional-button
    pass — same system as Performance/Leave/Payroll/Talent Acquisition/Time
    and Attendance, scoped to lnd- prefixed classes so nothing here leaks
    onto other modules still using btn-theme/btn-themeBlue/btn-danger/etc.

    :active rules are declared LAST, after every :hover rule — both are
    simultaneously true on a real click and the later-declared rule needs
    to win at equal specificity for the press/"zoom" feedback to show.
--}}
<style>
    .lnd-btn-primary, .lnd-btn-secondary, .lnd-btn-positive,
    .lnd-btn-accent, .lnd-btn-hero, .lnd-btn-neutral, .lnd-btn-critical {
        transition: transform .16s cubic-bezier(.2,.8,.2,1), box-shadow .16s ease, background .16s ease, border-color .16s ease;
    }
    .lnd-btn-primary:focus-visible, .lnd-btn-secondary:focus-visible, .lnd-btn-positive:focus-visible,
    .lnd-btn-accent:focus-visible, .lnd-btn-hero:focus-visible, .lnd-btn-neutral:focus-visible, .lnd-btn-critical:focus-visible {
        outline: 2px solid var(--teal);
        outline-offset: 2px;
    }

    /* Main forward action — Submit/Save/Update/Schedule. Text stays
       literal #fff (contrast-on-solid-teal) — box-shadows stay literal
       rgba(20,35,42,…) throughout this file. */
    .lnd-btn-primary { background: var(--teal); color: #fff; }
    .lnd-btn-primary:hover { background: var(--teal); color: #fff; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.35); }

    /* Low-stakes, recedes on purpose — View/Back/Cancel-nav.
       #C9D6D7 border has no token match — left literal. */
    .lnd-btn-secondary { background: transparent; color: var(--teal); border: 1.5px solid #C9D6D7; }
    .lnd-btn-secondary:hover { background: var(--paper); border-color: var(--teal); color: var(--teal); transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.18); }

    /* Affirming — Approve, Update (inline row confirm). */
    .lnd-btn-positive { background: var(--positive-bg); color: var(--positive); }
    .lnd-btn-positive:hover { background: var(--positive); color: #fff; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.25); }

    /* Additive/construction, general — Add Learning Schedule, Create
       Evaluation/Feedback Form, Upload, Add More, Mark Attendance. */
    .lnd-btn-accent { background: var(--teal-3); color: var(--teal-2); }
    .lnd-btn-accent:hover { background: var(--teal-2); color: #fff; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.25); }

    /* The brand lime — reserved for a single standout hero CTA per page,
       not blanketed across every additive button. */
    .lnd-btn-hero { background: var(--lime); color: #17260a; }
    .lnd-btn-hero:hover { background: var(--lime); color: #17260a; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.35); }

    /* Backing out / low-stakes state change, not a decision — Cancel,
       On Hold, Remove an unsaved row, Back to Dashboard. */
    .lnd-btn-neutral { background: var(--neutral-bg); color: var(--darkblack); }
    .lnd-btn-neutral:hover { background: var(--teal-soft); color: var(--darkblack); transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.18); }

    /* The hard-stop decision — Deny, Remove, Delete. */
    .lnd-btn-critical { background: var(--critical-bg); color: var(--critical); }
    .lnd-btn-critical:hover { background: var(--critical); color: #fff; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.25); }

    .lnd-btn-primary:active, .lnd-btn-secondary:active, .lnd-btn-positive:active,
    .lnd-btn-accent:active, .lnd-btn-hero:active, .lnd-btn-neutral:active, .lnd-btn-critical:active {
        transition-duration: .07s;
        transform: translateY(0) scale(.94);
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
    }

    /* Disabled state — without this, the sitewide `.btn[disabled]` rule
       (background: #6fa329, an olive green unrelated to any brand color)
       wins on specificity and silently repaints any disabled button in
       this system that color. */
    .lnd-btn-primary[disabled], .lnd-btn-secondary[disabled], .lnd-btn-positive[disabled],
    .lnd-btn-accent[disabled], .lnd-btn-hero[disabled], .lnd-btn-neutral[disabled], .lnd-btn-critical[disabled] {
        opacity: .55;
        cursor: not-allowed;
        transform: none;
    }
    .lnd-btn-primary[disabled] { background: var(--teal); color: #fff; }
    .lnd-btn-secondary[disabled] { background: transparent; color: var(--teal); }
    .lnd-btn-positive[disabled] { background: var(--positive-bg); color: var(--positive); }
    .lnd-btn-accent[disabled] { background: var(--teal-3); color: var(--teal-2); }
    .lnd-btn-hero[disabled] { background: var(--lime); color: #17260a; }
    .lnd-btn-neutral[disabled] { background: var(--neutral-bg); color: var(--darkblack); }
    .lnd-btn-critical[disabled] { background: var(--critical-bg); color: var(--critical); }

    /* .page-hedding sits directly on the dark teal body::before band
       (var(--teal), 315px tall behind every page header — itself already
       tokenized). Secondary's transparent-bg + dark-teal-text combo
       disappears into that band — only the faint light border shows.
       Scoped override: Secondary keeps its normal look everywhere else
       (inside a .card, which has its own opaque white background), just
       goes light-on-teal here instead. Left literal (not --card): this
       is contrast-on-the-teal-band white, not a surface color. */
    .page-hedding .lnd-btn-secondary { color: #fff; border-color: rgba(255,255,255,.55); }
    .page-hedding .lnd-btn-secondary:hover { background: rgba(255,255,255,.14); border-color: #fff; color: #fff; }

    /* Row-action icon for the hard-stop decision (Delete/Remove) — the
       shared global .btnIcon-danger is an old dark red (#A90000), not the
       agreed brand Critical scarlet; this scoped class replaces it
       wherever Learning renders a delete icon, without touching the
       global class other modules still rely on. rgba(255,36,0,.09) is
       --critical's exact RGB as a tint — no --critical-rgb primitive
       exists (this phase adds no new tokens), left literal. */
    .lnd-icon-critical { color: var(--critical); background: rgba(255,36,0,.09); }
    .lnd-icon-critical:hover { color: #fff; background: var(--critical); }
</style>
