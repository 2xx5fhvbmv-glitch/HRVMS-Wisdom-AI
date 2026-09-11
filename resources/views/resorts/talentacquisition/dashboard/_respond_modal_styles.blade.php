{{--
    "Respond to request" modal — Liquid Glass shell, matching
    respond_modal_reference.html. Still a genuine Bootstrap modal
    (.modal.fade, data-bs-toggle, .modal('show')) — only .modal-content is
    restyled (as .rsp-glass), so Bootstrap's own centering/z-index/Esc-
    close/outside-click-close keep working unchanged; no close/open JS of
    our own needed for that part. Same "restyle .modal-content directly,
    no Bootstrap header/body/footer wrapper divs" approach already used by
    the Payroll Breakdown popover (_estimate_breakdown_styles.blade.php) —
    the established pattern in this app, not a new one.

    Scrim: the reference requires a dark, blurred backdrop behind the
    glass (not Bootstrap's default flat rgba(0,0,0,.5)). Bootstrap's
    .modal-backdrop is a sibling of .modal-content, not a descendant, so
    it can't be scoped via a CSS descendant selector from .rsp-glass —
    scoped instead via a `rsp-modal-open` class toggled on <body> for the
    exact open/close lifetime of THIS modal (see the show.bs.modal /
    hidden.bs.modal listeners added alongside the existing
    respondOfFreshmodal handler in each dashboard). Only one Bootstrap
    modal backdrop is ever on screen at a time in this flow, so this
    can't bleed onto another modal.

    Included by every dashboard that renders #FreshRespond-modal —
    admindashboard/hrdashboard (inline) and
    _partials/vacancy_approval_modals.blade.php (hoddashboard, via the
    shared partial) — one shared stylesheet, one shared look.
--}}
<style>
:root {
    --rsp-ok: #1F7A54; --rsp-ok-soft: #E7F4EC;
    --rsp-amber: #9A6512; --rsp-amber-soft: #FBF1E1;
    --rsp-warn: #B4462F; --rsp-warn-soft: #fbeceb;
}

/* scrim */
body.rsp-modal-open .modal-backdrop { background: rgba(6, 24, 28, .5); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); }

.rsp-dialog { max-width: 440px; }

/* glass shell */
.rsp-glass {
    position: relative; padding: 22px 24px 20px; overflow: hidden;
    font-family: 'Poppins', sans-serif; color: var(--ink, #14232A);
    background: rgba(255,255,255,.82);
    backdrop-filter: blur(22px) saturate(200%) brightness(1.05);
    -webkit-backdrop-filter: blur(22px) saturate(200%) brightness(1.05);
    border: 1px solid rgba(255,255,255,.5); border-radius: 22px;
    box-shadow: inset 0 1px 0 rgba(255,255,255,.6), inset 0 0 0 1px rgba(255,255,255,.08), 0 26px 74px rgba(0,0,0,.34);
}
/* pointer-tracking specular highlight, driven by --mx/--my (set on
   pointermove — see the script added alongside respondOfFreshmodal) */
.rsp-glass::before {
    content: ""; position: absolute; inset: 0; border-radius: inherit; pointer-events: none; z-index: 0;
    background: radial-gradient(180px circle at var(--mx, 50%) var(--my, 0%), rgba(255,255,255,.4), transparent 62%);
    opacity: .55; transition: opacity .2s;
}
/* top sheen */
.rsp-glass::after {
    content: ""; position: absolute; left: 0; right: 0; top: 0; height: 42%; pointer-events: none; z-index: 0;
    background: linear-gradient(180deg, rgba(255,255,255,.4), transparent);
}
.rsp-glass > * { position: relative; z-index: 1; }

.rsp-x {
    position: absolute; top: 22px; right: 24px; width: 32px; height: 32px; flex: none; z-index: 2;
    border-radius: 9px; border: 1px solid rgba(20,35,42,.12); background: rgba(255,255,255,.6);
    color: #6B7378; display: grid; place-items: center; cursor: pointer; transition: background .14s, color .14s;
}
.rsp-x:hover { background: #fff; color: var(--ink, #14232A); }
.rsp-x svg { width: 15px; height: 15px; }

.rsp-title { font-size: 17px; font-weight: 600; color: var(--ink, #14232A); padding-right: 40px; }
.rsp-sub { font-size: 12.5px; font-weight: 400; color: #6B7378; margin-top: 3px; padding-right: 40px; }

/* .respond-main (the div the JS injects .rsp-req into) carries a
   site-wide default.css rule — min-height:210px, meant for the old
   .respond-block layout — that left a large empty gap below this much
   shorter card before the actions row. Neutralized here, scoped so the
   site-wide rule stays intact for whatever else still relies on it. */
.rsp-glass .respond-main { height: auto; min-height: 0; max-height: none; overflow: visible; padding-right: 0; }

/* request summary — solid, no glass-on-glass */
.rsp-req { position: relative; display: flex; align-items: center; gap: 14px; background: #fff; border: 1px solid var(--line, #EEF2F2); border-radius: 14px; padding: 15px 16px; margin: 18px 0; }
.rsp-av { position: relative; width: 48px; height: 48px; flex: none; border-radius: 50%; background: var(--teal-soft, #F5F8F8); color: var(--teal, #014653); font-size: 15px; font-weight: 600; display: grid; place-items: center; overflow: hidden; }
.rsp-av img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.rsp-rbody { flex: 1; min-width: 0; }
.rsp-rtop { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.rsp-rname { font-size: 14.5px; font-weight: 600; color: var(--ink, #14232A); }
.rsp-tag { background: #F7F8F8; color: #6B7378; font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: .3px; padding: 2px 7px; border-radius: 6px; }
.rsp-rsub { display: flex; align-items: center; gap: 8px; margin-top: 8px; flex-wrap: wrap; }
.rsp-lbl { font-size: 12.5px; font-weight: 400; color: #6B7378; }
.rsp-rolepill { display: inline-flex; align-items: center; gap: 6px; background: var(--teal-soft, #F5F8F8); color: var(--teal, #014653); font-size: 12.5px; font-weight: 600; padding: 4px 11px; border-radius: 8px; }
.rsp-qty { background: var(--teal, #014653); color: #fff; font-size: 10.5px; font-weight: 600; min-width: 16px; height: 16px; padding: 0 4px; border-radius: 5px; display: inline-grid; place-items: center; }

/* actions — text only, right-aligned */
.rsp-actions { display: flex; gap: 10px; justify-content: flex-end; }
.rsp-btn { border: none; border-radius: 11px; padding: 11px 18px; font: inherit; font-size: 13.5px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 7px; transition: transform .16s cubic-bezier(.34,1.56,.64,1), box-shadow .16s ease, background .14s, border-color .14s, color .14s; }
.rsp-hold { background: var(--rsp-amber-soft); color: var(--rsp-amber); border: 1px solid #eaddc2; }
.rsp-hold:hover { background: #f6e8cf; color: var(--rsp-amber); }
.rsp-reject { background: var(--rsp-warn-soft); color: var(--rsp-warn); border: 1px solid #f0d5d1; }
.rsp-reject:hover { background: #f7ddd8; color: var(--rsp-warn); }
.rsp-approve { background: var(--rsp-ok); color: #fff; margin-left: 2px; }
.rsp-approve:hover { background: #1b6b49; color: #fff; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(31,122,84,.55); }

@media (prefers-reduced-transparency: reduce) {
    body.rsp-modal-open .modal-backdrop { background: rgba(6, 24, 28, .6); backdrop-filter: none; -webkit-backdrop-filter: none; }
    .rsp-glass { background: #fff; backdrop-filter: none; -webkit-backdrop-filter: none; }
    .rsp-glass::before, .rsp-glass::after { display: none; }
}
@media (prefers-reduced-motion: reduce) {
    .rsp-glass::before { display: none; }
    .rsp-glass, .rsp-btn { transition: none; }
}
</style>
