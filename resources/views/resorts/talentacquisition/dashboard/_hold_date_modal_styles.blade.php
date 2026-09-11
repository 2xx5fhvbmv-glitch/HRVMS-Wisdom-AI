{{--
    "Put request on hold" date-picker modal — the shell (.rsp-glass, .rsp-x,
    .rsp-title, .rsp-sub, .rsp-actions) is shared with the Respond modal's
    own styles (_respond_modal_styles.blade.php, included alongside this
    one); the calendar itself is the canonical, app-wide
    resorts._datepicker_calendar_styles component. This file only adds
    what's specific to THIS modal: the "Hold until" label, the live
    confirmation line, and its own Cancel/Submit button colors (teal
    outline / solid teal — distinct from the Respond modal's amber/red/
    green trio, so its own classes rather than reusing .rsp-hold etc.).

    Included by every dashboard that renders #respond-HoldModel —
    admindashboard/hrdashboard (inline) and
    _partials/vacancy_approval_modals.blade.php (hoddashboard).
--}}
<style>
.hd-lbl { position: relative; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; color: #6B7378; margin: 18px 0 9px; }
.hd-selnote { position: relative; font-size: 12.5px; font-weight: 400; color: #6B7378; margin-top: 12px; }
.hd-selnote b { color: var(--ink, #14232A); font-weight: 600; }

.hd-btn { border-radius: 11px; padding: 11px 20px; font: inherit; font-size: 13.5px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; transition: transform .16s cubic-bezier(.34,1.56,.64,1), box-shadow .16s ease, background .14s, border-color .14s, color .14s; }
.hd-cancel { background: #fff; color: var(--teal, #014653); border: 1px solid #cfe0e1; }
.hd-cancel:hover { border-color: var(--teal, #014653); background: var(--teal-soft, #F5F8F8); color: var(--teal, #014653); }
.hd-submit { background: var(--teal, #014653); color: #fff; border: none; }
.hd-submit:hover { background: var(--teal-2, #035b6c); color: #fff; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.4); }
@media (prefers-reduced-motion: reduce) { .hd-btn { transition: none; } }
</style>
