{{--
    Presentation-only CSS for the Payroll emotional-button pass — same
    system and reasoning as workforce_planning/_wfp_buttons_v2_styles.blade.php,
    scoped to payroll- prefixed classes so nothing here can leak onto the
    380+ other files still using btn-theme/btn-themeBlue/etc.

    No "Celebrate" variant here on purpose: Payroll's highest-stakes
    moments (locking a payroll run, finalizing a settlement) are
    irreversible financial actions, not a personal happy moment the way
    leave approval was in Workforce Planning — a bright gradient there
    would read as gamifying money, not celebrating it. Those actions use
    Critical instead.

    :active rules are declared LAST, after every :hover rule — see the
    identical comment in the Workforce Planning partial for why (the
    press/"zoom" effect needs to win the simultaneous hover+active tie).
--}}
<style>
    .payroll-btn-primary,
    .payroll-btn-secondary,
    .payroll-btn-positive,
    .payroll-btn-attention,
    .payroll-btn-accent,
    .payroll-btn-neutral,
    .payroll-btn-critical,
    .payroll-btn-ghost {
        transition: transform .16s cubic-bezier(.2,.8,.2,1), box-shadow .16s ease, background .16s ease, border-color .16s ease;
    }
    .payroll-btn-primary:focus-visible,
    .payroll-btn-secondary:focus-visible,
    .payroll-btn-positive:focus-visible,
    .payroll-btn-attention:focus-visible,
    .payroll-btn-accent:focus-visible,
    .payroll-btn-neutral:focus-visible,
    .payroll-btn-critical:focus-visible {
        outline: 2px solid var(--teal);
        outline-offset: 2px;
    }
    .payroll-btn-ghost:focus-visible {
        outline: 2px solid #fff;
        outline-offset: 2px;
    }

    /* Main forward action — Run Payroll, Continue/Next, Submit (config
       saves). Text stays literal #fff (contrast-on-solid-teal) —
       box-shadows stay literal rgba(20,35,42,…) throughout this file. */
    .payroll-btn-primary {
        background: var(--teal);
        color: #fff;
    }
    .payroll-btn-primary:hover {
        background: var(--teal);
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 8px 18px -8px rgba(20,35,42,.35);
    }

    /* Low-stakes, recedes on purpose — Back, Cancel, View, Download,
       Clear Filter. Light paper tint on hover, never a solid fill.
       #C9D6D7 border has no token match — left literal. */
    .payroll-btn-secondary {
        background: transparent;
        color: var(--teal);
        border: 1.5px solid #C9D6D7;
    }
    .payroll-btn-secondary:hover {
        background: var(--paper);
        border-color: var(--teal);
        color: var(--teal);
        transform: translateY(-2px);
        box-shadow: 0 8px 18px -8px rgba(20,35,42,.18);
    }

    /* Affirming but not the finale — Approve (per approval step), Confirm
       payment consent, Mark selected as Paid, Update (save an edit). */
    .payroll-btn-positive {
        background: var(--positive-bg);
        color: var(--positive);
    }
    .payroll-btn-positive:hover {
        background: var(--positive);
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 8px 18px -8px rgba(20,35,42,.25);
    }

    /* Consequential workflow step, not destructive — Send Payroll for
       Approval, Reject. */
    .payroll-btn-attention {
        background: var(--warning-bg);
        color: var(--warning);
    }
    .payroll-btn-attention:hover {
        background: var(--warning);
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 8px 18px -8px rgba(20,35,42,.25);
    }

    /* Header-level CTA on the teal band — Run Payroll, Share Payslips.
       Same color as Request Manning in Workforce Planning: it's what
       actually survives against the dark teal band behind the page
       header (body::before) — a solid-teal Primary button disappears
       into that band just like a transparent Secondary one does.
       Explicitly used on more than one button on the Payroll dashboard
       (owner's call) — unlike Workforce Planning's single-CTA header,
       both header actions here get the same lime treatment on purpose. */
    .payroll-btn-accent {
        background: var(--lime);
        color: #17260a;
    }
    .payroll-btn-accent:hover {
        background: var(--lime);
        color: #17260a;
        transform: translateY(-2px);
        box-shadow: 0 8px 18px -8px rgba(20,35,42,.35);
    }

    /* Secondary action that has to share the teal header band with an
       Accent button — Share Payslips, Back to Shopkeepers. Can't be
       lime (only one Accent per page) and can't be the paper-tint
       Secondary style either (teal text on teal band is invisible, same
       failure mode Accent exists to avoid) — so it gets its own
       light/"ghost" treatment: near-white outline at rest, fills to a
       soft white wash on hover, instead of disappearing or competing. */
    .payroll-btn-ghost {
        background: transparent;
        color: #fff;
        border: 1.5px solid rgba(255,255,255,.55);
    }
    .payroll-btn-ghost:hover {
        background: rgba(255,255,255,.14);
        border-color: #fff;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 8px 18px -8px rgba(0,0,0,.35);
    }

    /* The one true "we did it" moment — brand gradient. Payroll is mostly
       too serious for this (see file header reasoning elsewhere in this
       module), but a batch of transactions closing out — Mark Selected as
       Paid — is the one genuine "this is settled" completion. Only ever
       one of these per page. */
    .payroll-btn-celebrate {
        background: var(--grad-celebrate);
        color: #fff;
    }
    .payroll-btn-celebrate:hover {
        background: var(--grad-celebrate-hover);
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 10px 22px -8px rgba(var(--teal-rgb),.45);
    }

    /* Saved, not final — Save as Draft. Its own neutral weight so it
       doesn't read as identical to Cancel or as heavy as the Primary
       Submit action. */
    .payroll-btn-neutral {
        background: var(--neutral-bg);
        color: var(--darkblack);
    }
    .payroll-btn-neutral:hover {
        background: var(--teal-soft);
        color: var(--darkblack);
        transform: translateY(-2px);
        box-shadow: 0 8px 18px -8px rgba(20,35,42,.18);
    }

    /* Irreversible — Confirm & Lock Payroll (both the opening button and
       the modal's real confirm button), Finalize Full & Final Settlement. */
    .payroll-btn-critical {
        background: var(--critical-bg);
        color: var(--critical);
    }
    .payroll-btn-critical:hover {
        background: var(--critical);
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 8px 18px -8px rgba(20,35,42,.25);
    }

    /* Press feedback — declared after every :hover rule above on purpose
       (see file header). transition-duration is shortened here so the
       press-in is visible even on a fast real click (see the identical,
       longer explanation in the Workforce Planning partial). */
    .payroll-btn-primary:active,
    .payroll-btn-secondary:active,
    .payroll-btn-positive:active,
    .payroll-btn-attention:active,
    .payroll-btn-accent:active,
    .payroll-btn-neutral:active,
    .payroll-btn-critical:active,
    .payroll-btn-ghost:active,
    .payroll-btn-celebrate:active {
        transition-duration: .07s;
        transform: translateY(0) scale(.94);
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
    }
</style>
