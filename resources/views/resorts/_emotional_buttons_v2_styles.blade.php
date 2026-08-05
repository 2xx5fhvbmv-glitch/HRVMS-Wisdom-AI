{{--
    Presentation-only CSS for the emotional-button design system, shared
    across every remaining module (Accommodation, Incident, Survey, Reports,
    Support, Visa, Grievance & Disciplinary, File Management, SOS,
    Compliance). Same system already shipped per-module-prefixed on
    Performance/Leave/Payroll/Talent Acquisition/Time & Attendance/Workforce
    Planning/Learning — this one is intentionally SHARED (one file, one
    `eb-` prefix) instead of ten near-identical per-module copies, since
    none of these ten modules has enough distinct button volume to justify
    its own partial. Scoped to `eb-` prefixed classes so nothing here leaks
    onto other modules still using btn-theme/btn-themeBlue/btn-danger/etc.

    :active rules are declared LAST, after every :hover rule — both are
    simultaneously true on a real click and the later-declared rule needs
    to win at equal specificity for the press/"zoom" feedback to show.
--}}
<style>
    .eb-btn-primary, .eb-btn-secondary, .eb-btn-positive,
    .eb-btn-accent, .eb-btn-hero, .eb-btn-neutral, .eb-btn-critical {
        transition: transform .16s cubic-bezier(.2,.8,.2,1), box-shadow .16s ease, background .16s ease, border-color .16s ease;
    }
    .eb-btn-primary:focus-visible, .eb-btn-secondary:focus-visible, .eb-btn-positive:focus-visible,
    .eb-btn-accent:focus-visible, .eb-btn-hero:focus-visible, .eb-btn-neutral:focus-visible, .eb-btn-critical:focus-visible {
        outline: 2px solid #014653;
        outline-offset: 2px;
    }

    /* Main forward action — Submit/Save/Update/Send. */
    .eb-btn-primary { background: #014653; color: #fff; }
    .eb-btn-primary:hover { background: #014653; color: #fff; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.35); }

    /* Low-stakes, recedes on purpose — View/Back/Download/Export. */
    .eb-btn-secondary { background: transparent; color: #014653; border: 1.5px solid #C9D6D7; }
    .eb-btn-secondary:hover { background: #F9F8F1; border-color: #014653; color: #014653; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.18); }

    /* Affirming — Approve, Resolve, Restore, Mark Safe/Complete. */
    .eb-btn-positive { background: #E4F3E9; color: #2E9E5B; }
    .eb-btn-positive:hover { background: #2E9E5B; color: #fff; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.25); }

    /* Additive/construction, general — Create/Add New/Upload/Raise Request. */
    .eb-btn-accent { background: #E6F0F1; color: #035b6c; }
    .eb-btn-accent:hover { background: #035b6c; color: #fff; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.25); }

    /* The brand lime — reserved for a single standout hero CTA per page,
       not blanketed across every additive button. */
    .eb-btn-hero { background: #E0FF02; color: #17260a; }
    .eb-btn-hero:hover { background: #E0FF02; color: #17260a; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.35); }

    /* Backing out / low-stakes state change, not a decision — Cancel,
       Close, Clear, Dismiss. */
    .eb-btn-neutral { background: #DEDEDE; color: #222; }
    .eb-btn-neutral:hover { background: #F5F8F8; color: #222; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.18); }

    /* The hard-stop decision — Reject, Delete, Escalate. */
    .eb-btn-critical { background: #FFDED9; color: #FF2400; }
    .eb-btn-critical:hover { background: #FF2400; color: #fff; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.25); }

    .eb-btn-primary:active, .eb-btn-secondary:active, .eb-btn-positive:active,
    .eb-btn-accent:active, .eb-btn-hero:active, .eb-btn-neutral:active, .eb-btn-critical:active {
        transition-duration: .07s;
        transform: translateY(0) scale(.94);
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
    }

    /* Disabled state — without this, the sitewide `.btn[disabled]` rule
       (background: #6fa329, an olive green unrelated to any brand color)
       wins on specificity and silently repaints any disabled button in
       this system that color. */
    .eb-btn-primary[disabled], .eb-btn-secondary[disabled], .eb-btn-positive[disabled],
    .eb-btn-accent[disabled], .eb-btn-hero[disabled], .eb-btn-neutral[disabled], .eb-btn-critical[disabled] {
        opacity: .55;
        cursor: not-allowed;
        transform: none;
    }
    .eb-btn-primary[disabled] { background: #014653; color: #fff; }
    .eb-btn-secondary[disabled] { background: transparent; color: #014653; }
    .eb-btn-positive[disabled] { background: #E4F3E9; color: #2E9E5B; }
    .eb-btn-accent[disabled] { background: #E6F0F1; color: #035b6c; }
    .eb-btn-hero[disabled] { background: #E0FF02; color: #17260a; }
    .eb-btn-neutral[disabled] { background: #DEDEDE; color: #222; }
    .eb-btn-critical[disabled] { background: #FFDED9; color: #FF2400; }

    /* .page-hedding sits directly on the dark teal body::before band
       (--green, #014653, 315px tall behind every page header). Secondary's
       transparent-bg + dark-teal-text combo disappears into that band —
       only the faint light border shows. Scoped override: Secondary keeps
       its normal look everywhere else (inside a .card, which has its own
       opaque white background), just goes light-on-teal here instead. */
    .page-hedding .eb-btn-secondary { color: #fff; border-color: rgba(255,255,255,.55); }
    .page-hedding .eb-btn-secondary:hover { background: rgba(255,255,255,.14); border-color: #fff; color: #fff; }

    /* Row-action icon for the hard-stop decision (Delete/Reject/Escalate) —
       the shared global .btnIcon-danger is an old dark red (#A90000), not
       the agreed brand Critical scarlet; this scoped class replaces it
       wherever these modules render a delete/reject icon, without touching
       the global class other modules still rely on. */
    .eb-icon-critical { color: #FF2400; background: rgba(255,36,0,.09); }
    .eb-icon-critical:hover { color: #fff; background: #FF2400; }

    /* Row-action icon for a low-stakes dismissal (cancel an inline edit,
       not a delete) — distinct from Critical so "cancel" doesn't read as
       alarming as "delete". */
    .eb-icon-neutral { color: #6b6b6b; background: rgba(107,107,107,.09); }
    .eb-icon-neutral:hover { color: #fff; background: #6b6b6b; }
</style>
