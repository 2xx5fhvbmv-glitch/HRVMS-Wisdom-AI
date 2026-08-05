{{--
    Presentation-only CSS for the Leave emotional-button pass — same system
    as Time and Attendance / Payroll / Talent Acquisition, scoped to
    leave- prefixed classes so nothing here leaks onto other modules still
    using btn-theme/btn-themeBlue/btn-danger/etc.

    :active rules are declared LAST, after every :hover rule — both are
    simultaneously true on a real click and the later-declared rule needs
    to win at equal specificity for the press/"zoom" feedback to show.
--}}
<style>
    .leave-btn-primary,
    .leave-btn-secondary,
    .leave-btn-positive,
    .leave-btn-accent,
    .leave-btn-neutral,
    .leave-btn-critical {
        transition: transform .16s cubic-bezier(.2,.8,.2,1), box-shadow .16s ease, background .16s ease, border-color .16s ease;
    }
    .leave-btn-primary:focus-visible,
    .leave-btn-secondary:focus-visible,
    .leave-btn-positive:focus-visible,
    .leave-btn-accent:focus-visible,
    .leave-btn-neutral:focus-visible,
    .leave-btn-critical:focus-visible {
        outline: 2px solid #014653;
        outline-offset: 2px;
    }

    /* Main forward action — Submit (apply leave, save config forms),
       Suggest Alternative Dates submit. */
    .leave-btn-primary {
        background: #014653;
        color: #fff;
    }
    .leave-btn-primary:hover {
        background: #014653;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 8px 18px -8px rgba(20,35,42,.35);
    }

    /* Low-stakes, recedes on purpose — View, View Details, Download,
       Notify All Employees, Regenerate, Send Email to Travel Partner,
       Clear Filter. Light paper tint on hover, never a solid fill. */
    .leave-btn-secondary {
        background: transparent;
        color: #014653;
        border: 1.5px solid #C9D6D7;
    }
    .leave-btn-secondary:hover {
        background: #F9F8F1;
        border-color: #014653;
        color: #014653;
        transform: translateY(-2px);
        box-shadow: 0 8px 18px -8px rgba(20,35,42,.18);
    }

    /* Affirming, the "yes" decision — Approve. */
    .leave-btn-positive {
        background: #E4F3E9;
        color: #2E9E5B;
    }
    .leave-btn-positive:hover {
        background: #2E9E5B;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 8px 18px -8px rgba(20,35,42,.25);
    }

    /* Additive/construction — Add Another Leave Row, Add Leave Category,
       Add Agent, Upload File, Export Employees, Download Template. */
    .leave-btn-accent {
        background: #E6F0F1;
        color: #035b6c;
    }
    .leave-btn-accent:hover {
        background: #035b6c;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 8px 18px -8px rgba(20,35,42,.25);
    }

    /* Backing out, not a decision — Cancel, Dismiss modal. */
    .leave-btn-neutral {
        background: #DEDEDE;
        color: #222;
    }
    .leave-btn-neutral:hover {
        background: #F5F8F8;
        color: #222;
        transform: translateY(-2px);
        box-shadow: 0 8px 18px -8px rgba(20,35,42,.18);
    }

    /* The hard-stop decision — Reject, Delete (leave category, ticket agent). */
    .leave-btn-critical {
        background: #FFDED9;
        color: #FF2400;
    }
    .leave-btn-critical:hover {
        background: #FF2400;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 8px 18px -8px rgba(20,35,42,.25);
    }

    /* Press feedback — declared after every :hover rule above on purpose
       (see file header). Shortened transition so the press-in is visible
       even on a fast real click. */
    .leave-btn-primary:active,
    .leave-btn-secondary:active,
    .leave-btn-positive:active,
    .leave-btn-accent:active,
    .leave-btn-neutral:active,
    .leave-btn-critical:active {
        transition-duration: .07s;
        transform: translateY(0) scale(.94);
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
    }

    /* Disabled state — without this, the sitewide `.btn[disabled]` rule
       (background: #6fa329, an olive green unrelated to any brand color)
       wins on specificity and silently repaints any disabled button in
       this system that color. Keeps each variant's own hue, just dimmed. */
    .leave-btn-primary[disabled], .leave-btn-secondary[disabled], .leave-btn-positive[disabled],
    .leave-btn-accent[disabled], .leave-btn-neutral[disabled], .leave-btn-critical[disabled] {
        opacity: .55;
        cursor: not-allowed;
        transform: none;
    }
    .leave-btn-primary[disabled] { background: #014653; color: #fff; }
    .leave-btn-secondary[disabled] { background: transparent; color: #014653; }
    .leave-btn-positive[disabled] { background: #E4F3E9; color: #2E9E5B; }
    .leave-btn-accent[disabled] { background: #E6F0F1; color: #035b6c; }
    .leave-btn-neutral[disabled] { background: #DEDEDE; color: #222; }
    .leave-btn-critical[disabled] { background: #FFDED9; color: #FF2400; }

</style>
