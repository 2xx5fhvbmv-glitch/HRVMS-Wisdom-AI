{{--
    Emotional button system for Time and Attendance — same pattern as
    Talent Acquisition (ta-btn-*), Workforce Planning (wfp-btn-*), and
    Payroll (payroll-btn-*). Scoped to taa-btn- prefixed classes so nothing
    here leaks onto other modules. Only the variants this module actually
    uses are defined — no Accent/Celebrate/Ghost here, add them if a real
    header-CTA or on-teal-surface case shows up later.
--}}
<style>
    .taa-btn-primary, .taa-btn-secondary, .taa-btn-positive, .taa-btn-attention,
    .taa-btn-neutral, .taa-btn-critical {
        transition: transform .16s cubic-bezier(.2,.8,.2,1), box-shadow .16s ease, background .16s ease, border-color .16s ease;
    }
    .taa-btn-primary:focus-visible, .taa-btn-secondary:focus-visible, .taa-btn-positive:focus-visible,
    .taa-btn-attention:focus-visible, .taa-btn-neutral:focus-visible, .taa-btn-critical:focus-visible {
        outline: 2px solid #014653;
        outline-offset: 2px;
    }

    /* Everyday action — Submit, Save. */
    .taa-btn-primary { background: #014653; color: #fff; }
    .taa-btn-primary:hover { background: #014653; color: #fff; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.35); }

    /* Low-stakes, recedes on purpose — Cancel, Back, View, Download, Edit-a-row, toolbar tools. */
    .taa-btn-secondary { background: transparent; color: #014653; border: 1.5px solid #C9D6D7; }
    .taa-btn-secondary:hover { background: #F9F8F1; border-color: #014653; color: #014653; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.18); }

    /* Affirming, additive — Approve, Add Shift/Entry/Zone/Holiday. */
    .taa-btn-positive { background: #E4F3E9; color: #2E9E5B; }
    .taa-btn-positive:hover { background: #2E9E5B; color: #fff; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.25); }

    /* Consequential/needs-a-decision, not destructive — Reject, manual
       Check-In/Check-Out, Update Overtime Status, Pause zone. */
    .taa-btn-attention { background: #FBF0DC; color: #D98A00; }
    .taa-btn-attention:hover { background: #D98A00; color: #fff; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.25); }

    /* Removes an unsaved draft row, not a real delete — remove shift/OT/entry row. */
    .taa-btn-neutral { background: #DEDEDE; color: #222; }
    .taa-btn-neutral:hover { background: #F5F8F8; color: #222; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.18); }

    /* Can't be undone — Delete zone/holiday. */
    .taa-btn-critical { background: #FFDED9; color: #FF2400; }
    .taa-btn-critical:hover { background: #FF2400; color: #fff; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.25); }

    /* Press feedback — after every :hover rule on purpose (hover+active
       tie at equal specificity; later rule wins per property). */
    .taa-btn-primary:active, .taa-btn-secondary:active, .taa-btn-positive:active,
    .taa-btn-attention:active, .taa-btn-neutral:active, .taa-btn-critical:active {
        transition-duration: .07s;
        transform: translateY(0) scale(.94);
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
    }

    /* Disabled/processing — e.g. "Processing..." while a manual check-in
       request is in flight. Without this, the sitewide `.btn[disabled]`
       rule (background: #6fa329, an olive green unrelated to any brand
       color) wins on specificity and silently repaints every disabled
       button in this system that color. Keeping each variant's own hue,
       just dimmed, is what actually reads as "this same button, now
       waiting" rather than "an unrelated button appeared". */
    .taa-btn-primary[disabled], .taa-btn-secondary[disabled], .taa-btn-positive[disabled],
    .taa-btn-attention[disabled], .taa-btn-neutral[disabled], .taa-btn-critical[disabled] {
        opacity: .55;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }
    .taa-btn-primary[disabled] { background: #014653; color: #fff; }
    .taa-btn-secondary[disabled] { background: transparent; color: #014653; }
    .taa-btn-positive[disabled] { background: #E4F3E9; color: #2E9E5B; }
    .taa-btn-attention[disabled] { background: #FBF0DC; color: #D98A00; }
    .taa-btn-neutral[disabled] { background: #DEDEDE; color: #222; }
    .taa-btn-critical[disabled] { background: #FFDED9; color: #FF2400; }

    /* View-mode switcher (Normal/Detailed, Individual/Department, grid/list) —
       wayfinding, not an action, so it doesn't borrow an emotional variant.
       Same pattern as Talent Acquisition's .ta-tabnav. */
    .taa-tabnav { display: inline-flex; gap: 4px; background: #F5F8F8; padding: 4px; border-radius: 10px; }
    .taa-tabnav a, .taa-tabnav button { padding: 6px 12px; border-radius: 7px; font-size: 13px; font-weight: 500; color: #4b5457; border: none; background: transparent; transition: background .16s ease, color .16s ease; }
    .taa-tabnav a:hover, .taa-tabnav button:hover { color: #014653; }
    .taa-tabnav a.active, .taa-tabnav button.active { background: #fff; color: #014653; box-shadow: 0 1px 3px rgba(20,35,42,.12); }
</style>
