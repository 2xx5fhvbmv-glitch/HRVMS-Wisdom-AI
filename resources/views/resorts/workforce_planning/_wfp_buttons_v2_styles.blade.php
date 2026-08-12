{{--
    Presentation-only CSS for the Workforce Planning emotional-button pass.
    Dedicated classes rather than reusing the sitewide btn-theme* classes —
    those matched on resting-state color but not on hover behaviour or
    motion, which is what this system actually depends on (see: Request
    Manning disappearing against the body::before header band when it
    briefly used btn-themeBlue, and Download's hover filling solid teal
    instead of the light tint this system uses everywhere else).
    Scoped to wfp- prefixed classes only, so nothing here can leak onto
    the 380+ other files still using btn-theme/btn-themeBlue/etc.
    Included by all 5 Workforce Planning views.

    :active rules are declared LAST in this file, after every :hover rule.
    While a real click is in progress the cursor is still over the button,
    so :hover and :active are both true at once — at equal specificity the
    later rule wins per property, so :active has to come after :hover or
    the press/"zoom" effect never wins the tie and silently never shows.
--}}
<style>
    .wfp-btn-primary,
    .wfp-btn-secondary,
    .wfp-btn-positive,
    .wfp-btn-attention,
    .wfp-btn-accent,
    .wfp-btn-neutral,
    .wfp-btn-critical,
    .wfp-icon-positive {
        transition: transform .16s cubic-bezier(.2,.8,.2,1), box-shadow .16s ease, background .16s ease, border-color .16s ease;
    }
    .wfp-btn-primary:focus-visible,
    .wfp-btn-secondary:focus-visible,
    .wfp-btn-positive:focus-visible,
    .wfp-btn-attention:focus-visible,
    .wfp-btn-accent:focus-visible,
    .wfp-btn-neutral:focus-visible,
    .wfp-btn-critical:focus-visible,
    .wfp-icon-positive:focus-visible {
        outline: 2px solid #014653;
        outline-offset: 2px;
    }

    /* Everyday action — Submit, Send Response, Change Password, Save Crop, Upload File. */
    .wfp-btn-primary {
        background: #014653;
        color: #fff;
    }
    .wfp-btn-primary:hover {
        background: #014653;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 8px 18px -8px rgba(20,35,42,.35);
    }

    /* Low-stakes, recedes on purpose — Cancel, Download, Back. Hover is a
       light paper tint (the app's #F9F8F1 neutral, per the confirmed
       proposal), never a solid fill, so it never reads as heavier than
       the page's actual primary action. */
    .wfp-btn-secondary {
        background: transparent;
        color: #014653;
        border: 1.5px solid #C9D6D7;
    }
    .wfp-btn-secondary:hover {
        background: #F9F8F1;
        border-color: #014653;
        color: #014653;
        transform: translateY(-2px);
        box-shadow: 0 8px 18px -8px rgba(20,35,42,.18);
    }

    /* Additive action — Import Occupancy. */
    .wfp-btn-positive {
        background: var(--positive-bg);
        color: var(--positive);
    }
    .wfp-btn-positive:hover {
        background: var(--positive);
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 8px 18px -8px rgba(20,35,42,.25);
    }

    /* Additive icon action — the "+ Add Occupancy" circular button. */
    .wfp-icon-positive {
        width: 25px;
        height: 25px;
        border-radius: 7px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--positive);
        background: rgba(31, 157, 107, .09);
    }
    .wfp-icon-positive:hover {
        color: #fff;
        background: var(--positive);
        transform: translateY(-2px);
        box-shadow: 0 8px 18px -8px rgba(20,35,42,.25);
    }

    /* Waiting on someone else — Send Reminder. */
    .wfp-btn-attention {
        background: #FBF0DC;
        color: #D98A00;
    }
    .wfp-btn-attention:hover {
        background: #D98A00;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 8px 18px -8px rgba(20,35,42,.25);
    }

    /* Rare, single header-level CTA — Request Manning. Deliberately the
       only lime button on the page; it's what stands out against the
       dark teal band behind the page header (body::before), which is
       exactly why a solid-teal button disappeared into it here before. */
    .wfp-btn-accent {
        background: #E0FF02;
        color: #17260a;
    }
    .wfp-btn-accent:hover {
        background: #E0FF02;
        color: #17260a;
        transform: translateY(-2px);
        box-shadow: 0 8px 18px -8px rgba(20,35,42,.35);
    }

    /* Saved, not final — Save As Draft. Its own neutral weight so it
       doesn't read as identical to Cancel or as heavy as Submit. */
    .wfp-btn-neutral {
        background: #DEDEDE;
        color: #222;
    }
    .wfp-btn-neutral:hover {
        background: #F5F8F8;
        color: #222;
        transform: translateY(-2px);
        box-shadow: 0 8px 18px -8px rgba(20,35,42,.18);
    }

    /* Can't be undone — Remove/Delete on dynamically-added rows. Scarlet,
       finalized. Same soft-tint-at-rest / solid-on-hover pattern as
       Positive and Attention, so weight scales the same way across the
       whole system — only the hue changes. */
    .wfp-btn-critical {
        background: #FFDED9;
        color: #FF2400;
    }
    .wfp-btn-critical:hover {
        background: #FF2400;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 8px 18px -8px rgba(20,35,42,.25);
    }

    /* The one true "we did it" moment — brand gradient, reserved for a
       final, whole-cycle completion (e.g. Approve Budget), not routine
       positives. Only ever one of these per page. */
    .wfp-btn-celebrate {
        background: linear-gradient(135deg, #014653, #E0FF02);
        color: #fff;
    }
    .wfp-btn-celebrate:hover {
        background: linear-gradient(135deg, #013641, #c7e102);
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 10px 22px -8px rgba(1,70,83,.45);
    }

    /* Press feedback — declared after every :hover rule above on purpose
       (see file header). Wins the simultaneous hover+active tie so the
       button actually shrinks back down while the mouse is held down.

       transition-duration is shortened here on purpose: a real click's
       mousedown-to-mouseup window is often well under the base 160ms, so
       at that speed the press-in never finished animating before the
       release-transition (back to hover, at the slower 160ms) took back
       over — the shrink was real but too brief to register. Entering the
       press at 70ms makes it visible even on a fast, real click; leaving
       it still eases out at the slower, smoother 160ms from the base rule. */
    .wfp-btn-primary:active,
    .wfp-btn-secondary:active,
    .wfp-btn-positive:active,
    .wfp-btn-attention:active,
    .wfp-btn-accent:active,
    .wfp-btn-neutral:active,
    .wfp-btn-critical:active,
    .wfp-btn-celebrate:active,
    .wfp-icon-positive:active {
        transition-duration: .07s;
        transform: translateY(0) scale(.94);
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
    }
</style>
