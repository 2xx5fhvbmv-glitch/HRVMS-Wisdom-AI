{{--
    Shared "New Hire Requests" card styles — nhr- prefixed, scoped so
    nothing here can leak onto the many OTHER modules that reuse the old
    hireReq-block/img-circle class names (master-dashboard, Performance,
    Survey, timeandattendance). Included by every dashboard that renders
    _new_hire_requests_card.blade.php (admin/hr/hod) — one shared
    stylesheet, one shared look, no per-role forks.

    The outer card shell (background/radius/shadow/base padding) is left
    to the app's existing .card class, matching its siblings in the same
    row (Top Hiring Sources / Top Countries / WAI Insights all do the
    same — no custom shadow/radius of their own either); .ta-toprow-card
    (in _ta_widgets_v2_styles.blade.php) handles the fixed-height/flex
    behavior for that row. Everything below is just this card's internal
    content.
--}}
<style>
/* This card lives in a narrow 4-up row (col-lg-3) as well as wider
   single-column layouts (HOD dashboard's col-lg-12, whose override title
   "Hire requests pending your approval" is much longer) — nowrap +
   min-width:0 + ellipsis on the title keeps the count chip and View all
   always visible on one line instead of the title pushing them onto a
   second line at the narrow width. */
.nhr-ct { display: flex; align-items: center; gap: 9px; margin-bottom: 12px; flex-wrap: nowrap; }
.nhr-ct h2 { font-size: 16px; font-weight: 600; color: var(--ink, #14232A); min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.nhr-count { flex: none; background: var(--teal-soft, #F5F8F8); color: var(--teal, #014653); font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 20px; }
.nhr-spacer { flex: 1; min-width: 6px; }
.nhr-link { flex: none; color: var(--teal, #014653); font-size: 12.5px; font-weight: 600; text-decoration: none; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; }
.nhr-link:hover { color: var(--teal-2, #035b6c); }
.nhr-link svg { width: 13px; height: 13px; }

/* list — flex column so it both lays out the tiles (gap instead of a
   last-child margin hack) AND, when an ancestor forces this card to a
   fixed height (.ta-toprow-card on hrdashboard), fills the remaining
   space and scrolls internally instead of pushing the card taller.
   flex/min-height/overflow are no-ops outside that flex context
   (admindashboard/hoddashboard render this same card at natural
   height), so one rule serves both layouts correctly. */
.nhr-list { display: flex; flex-direction: column; gap: 10px; flex: 1 1 auto; min-height: 0; overflow-y: auto; padding-right: 2px; }
.nhr-empty { color: #99A1A5; margin: 0; padding: 4px 0; font-size: 13.5px; }

/* request tile */
.nhr-req { display: flex; align-items: center; gap: 13px; border: 1px solid var(--line, #EEF2F2); border-radius: 13px; padding: 12px 14px; flex: none; }
.nhr-qbig { width: 44px; height: 44px; flex: none; border-radius: 12px; background: var(--teal-soft, #F5F8F8); color: var(--teal, #014653); display: flex; flex-direction: column; align-items: center; justify-content: center; line-height: 1; }
.nhr-qbig .n { font-size: 19px; font-weight: 600; font-variant-numeric: tabular-nums; }
.nhr-qbig .x { font-size: 8.5px; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; color: #99A1A5; margin-top: 1px; }
.nhr-body { flex: 1; min-width: 0; }
.nhr-role { font-size: 14.5px; font-weight: 600; color: var(--ink, #14232A); }
.nhr-who { display: flex; align-items: center; gap: 7px; margin-top: 6px; min-width: 0; }

/* mini avatar — photo-first with initials fallback painted first (see
   note on _new_hire_request_row.blade.php): a loaded photo covers the
   initials, and onerror="this.remove()" on a broken image reveals them
   underneath again. Flat teal-soft/teal per the finalized design (no
   per-name hashed palette on this card). */
.nhr-mini { position: relative; width: 20px; height: 20px; flex: none; border-radius: 50%; background: var(--teal-soft, #F5F8F8); overflow: hidden; }
.nhr-mini-fallback { position: absolute; inset: 0; display: grid; place-items: center; color: var(--teal, #014653); font-size: 9px; font-weight: 600; }
.nhr-mini img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.nhr-wn { font-size: 12px; color: #6B7378; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; min-width: 0; }
.nhr-wn b { color: #3A4145; font-weight: 600; }

/* Respond is an <a> (preserves the existing .respondOfFreshmodal click
   handler/modal wiring untouched) — color is set explicitly in both
   states rather than left to inherit, because the app's global
   a:hover{color:#005AB8} rule (default.css) has higher specificity than
   a bare class and would otherwise leak through on hover. */
.nhr-respond { background: #fff; color: var(--teal, #014653); border: 1px solid #cfe0e1; border-radius: 9px; width: 34px; height: 34px; display: grid; place-items: center; cursor: pointer; flex: none; text-decoration: none; transition: background .14s, color .14s; }
.nhr-respond:hover, .nhr-respond:focus-visible { background: var(--teal, #014653); color: #fff; }
.nhr-respond svg { width: 16px; height: 16px; }
@media (prefers-reduced-motion: reduce) { .nhr-respond, .nhr-link { transition: none; } }
</style>
