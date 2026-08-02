{{--
    Emotional button system for Talent Acquisition — same pattern as
    Workforce Planning (wfp-btn-*) and Payroll (payroll-btn-*). Scoped to
    ta-btn- prefixed classes so nothing here leaks onto other modules.

    Ghost is the one variant meant to be identical across all three modules:
    a button on a teal surface (header band, hover overlay) needs a white
    outline, not a tint — Secondary's teal-on-transparent disappears there.
    First proven in Payroll as payroll-btn-ghost; promoted here and into
    Workforce Planning so "a button on teal" has one answer everywhere.
--}}
<style>
    .ta-btn-primary, .ta-btn-secondary, .ta-btn-positive, .ta-btn-attention,
    .ta-btn-accent, .ta-btn-neutral, .ta-btn-critical, .ta-btn-celebrate, .ta-btn-ghost {
        transition: transform .16s cubic-bezier(.2,.8,.2,1), box-shadow .16s ease, background .16s ease, border-color .16s ease;
    }
    .ta-btn-primary:focus-visible, .ta-btn-secondary:focus-visible, .ta-btn-positive:focus-visible,
    .ta-btn-attention:focus-visible, .ta-btn-accent:focus-visible, .ta-btn-neutral:focus-visible,
    .ta-btn-critical:focus-visible, .ta-btn-celebrate:focus-visible {
        outline: 2px solid #014653;
        outline-offset: 2px;
    }
    .ta-btn-ghost:focus-visible {
        outline: 2px solid #fff;
        outline-offset: 2px;
    }

    /* Everyday action — Submit, Save. */
    .ta-btn-primary { background: #014653; color: #fff; }
    .ta-btn-primary:hover { background: #014653; color: #fff; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.35); }

    /* Low-stakes, recedes on purpose — Cancel, Back, View, Download. */
    .ta-btn-secondary { background: transparent; color: #014653; border: 1.5px solid #C9D6D7; }
    .ta-btn-secondary:hover { background: #F9F8F1; border-color: #014653; color: #014653; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.18); }

    /* Affirming completion — Approved (vacancy request), Set Default. */
    .ta-btn-positive { background: #E4F3E9; color: #2E9E5B; }
    .ta-btn-positive:hover { background: #2E9E5B; color: #fff; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.25); }

    /* Consequential/external-facing, not destructive — Send Interview,
       Send Offer Letter/Contract, On Hold, Reject request, Extend Ad Link. */
    .ta-btn-attention { background: #FBF0DC; color: #D98A00; }
    .ta-btn-attention:hover { background: #D98A00; color: #fff; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.25); }

    /* Rare, single header-level CTA / AI feature — New Hire, WAI CV,
       Regenerate AI Analysis. */
    .ta-btn-accent { background: #E0FF02; color: #17260a; }
    .ta-btn-accent:hover { background: #E0FF02; color: #17260a; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.35); }

    /* Saved, not final — Save as Draft. */
    .ta-btn-neutral { background: #DEDEDE; color: #222; }
    .ta-btn-neutral:hover { background: #F5F8F8; color: #222; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.18); }

    /* Can't be undone — deletes, Confirm Reject, Yes Delete Slot. */
    .ta-btn-critical { background: #FFDED9; color: #FF2400; }
    .ta-btn-critical:hover { background: #FF2400; color: #fff; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.25); }

    /* The one true "we did it" moment — brand gradient. Confirm Select
       (hiring someone) is the genuine good-news moment in this module.
       Only ever one of these per page. */
    .ta-btn-celebrate { background: linear-gradient(135deg, #014653, #E0FF02); color: #fff; }
    .ta-btn-celebrate:hover { background: linear-gradient(135deg, #013641, #c7e102); color: #fff; transform: translateY(-2px); box-shadow: 0 10px 22px -8px rgba(1,70,83,.45); }

    /* On a teal surface only — header bands, the email-template hover
       overlay. White outline at rest, soft white wash on hover; never a
       solid fill (that's what Accent is for). */
    .ta-btn-ghost { background: transparent; color: #fff; border: 1.5px solid rgba(255,255,255,.55); }
    .ta-btn-ghost:hover { background: rgba(255,255,255,.14); border-color: #fff; color: #fff; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(0,0,0,.35); }

    /* Press feedback — after every :hover rule on purpose (hover+active
       tie at equal specificity; later rule wins per property). */
    .ta-btn-primary:active, .ta-btn-secondary:active, .ta-btn-positive:active,
    .ta-btn-attention:active, .ta-btn-accent:active, .ta-btn-neutral:active,
    .ta-btn-critical:active, .ta-btn-celebrate:active, .ta-btn-ghost:active {
        transition-duration: .07s;
        transform: translateY(0) scale(.94);
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
    }

    /* Page-switcher nav (Shortlisted / Upcoming / Rejected / Reminders) —
       wayfinding, not an action, so it doesn't borrow an emotional variant.
       Sits on the same teal header band Ghost was built for. */
    .ta-tabnav { display: inline-flex; gap: 4px; background: #014653; padding: 5px; border-radius: 10px; flex-wrap: wrap; }
    .ta-tabnav a { padding: 7px 13px; border-radius: 7px; font-size: 13px; font-weight: 500; color: rgba(255,255,255,.72); transition: background .16s ease, color .16s ease; }
    .ta-tabnav a:hover { color: #fff; background: rgba(255,255,255,.1); }
    .ta-tabnav a.active { background: rgba(255,255,255,.16); color: #fff; }

    /* Status indicator, not a button — e.g. "No Slot Found". Never
       clickable (href="javascript:void(0)" with no handler), so it must
       not borrow a warning/attention color that implies it does something. */
    .ta-badge-muted { background: #F9F8F1; color: #8a8a80; border: 1px solid #E3E0D6; cursor: default; }

    /* Text-only tint for actions tucked inside a .dropdown-item — full
       pill buttons don't fit a full-width menu row. Same hues as the
       matching ta-btn-* variant so the same action reads the same way
       whether it's an icon button or a dropdown line. */
    .ta-text-secondary { color: #014653; }
    .ta-text-critical { color: #FF2400; }
</style>
