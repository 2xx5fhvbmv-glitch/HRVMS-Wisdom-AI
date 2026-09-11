{{--
    "Add New Vacancy" form — numbered step-spine redesign, matching
    add_vacancy_reference.html / add_vacancy_empty_reference.html. Scoped
    under av- so nothing here leaks onto other forms.

    Selects reuse the app's own canonical .dd component
    (resorts._dropdown_styles / _dropdown_script) exactly as the existing
    form already does for every select on this page — no new select
    implementation. The date field reuses the canonical, app-wide pop-up
    calendar (resorts._datepicker_calendar_styles / _script, the same
    .wcal- component built for the Respond flow's Hold-until date) inside
    a small popover wrapper defined here.

    Employee Type / Recruitment / Status are real native radios and
    checkboxes (not JS-toggled buttons) styled via a hidden-input +
    ~checked sibling label technique — the existing employee-type change
    listener, and jQuery Validate's own handling of these fields, need
    real form controls to keep firing exactly as they do today. No new
    JS needed for the pill/chip/segment look itself.
--}}
<style>
/* step spine */
.av-flow { position: relative; padding-left: 52px; }
.av-flow::before { content: ""; position: absolute; left: 16px; top: 16px; bottom: 12px; width: 2px; background: var(--line, #EEF2F2); }
.av-step { position: relative; margin-bottom: 30px; }
.av-step:last-child { margin-bottom: 0; }
.av-num { position: absolute; left: -52px; top: -2px; width: 30px; height: 30px; border-radius: 50%; background: #fff; color: #99A1A5; font-size: 13px; font-weight: 600; display: grid; place-items: center; border: 1px solid var(--line, #EEF2F2); box-shadow: 0 0 0 4px #fff; }
.av-sh { font-size: 15px; font-weight: 600; color: var(--ink, #14232A); min-height: 34px; display: flex; align-items: center; gap: 8px; margin-bottom: 14px; flex-wrap: wrap; }
.av-sh .av-sub { font-size: 12px; color: #99A1A5; font-weight: 400; }
@media (max-width: 520px) { .av-flow { padding-left: 44px; } .av-flow::before { left: 14px; } .av-num { left: -44px; } }

/* field grid */
.av-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px 22px; }
@media (max-width: 820px) { .av-grid { grid-template-columns: 1fr 1fr; } }
@media (max-width: 520px) { .av-grid { grid-template-columns: 1fr; } }
.av-f { min-width: 0; }
.av-f label { display: flex; align-items: center; gap: 7px; font-size: 13px; font-weight: 600; letter-spacing: .2px; text-transform: uppercase; color: #6B7378; margin-bottom: 8px; }
.av-f label .av-req { color: #C7CDCF; font-weight: 400; }
.av-inp { width: 100%; border: 1px solid var(--line, #EEF2F2); border-radius: 12px; padding: 12px 14px; font: inherit; font-size: 15px; color: var(--ink, #14232A); background: #fff; outline: none; transition: border-color .14s, box-shadow .14s; }
.av-inp::placeholder { color: #99A1A5; }
.av-inp:focus { border-color: #cfe0e1; box-shadow: 0 0 0 3px rgba(1,70,83,.05); }
.av-inp:disabled { color: #6B7378; cursor: default; }

/* date field — canonical .wcal-card calendar inside a small popover
   anchored under a readonly text input (kept as a real, visible,
   focusable input — not type=hidden / display:none — so jQuery
   Validate's default :hidden ignore rule doesn't silently skip its
   required-field check the way it would for a truly hidden input). */
.av-datewrap { position: relative; }
.av-datebtn { padding-right: 38px; cursor: pointer; background: #fff; }
.av-date-ic { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #99A1A5; pointer-events: none; }
.av-date-ic svg { width: 16px; height: 16px; display: block; }
.av-datepop { position: absolute; left: 0; top: calc(100% + 6px); z-index: 30; display: none; }
.av-datewrap.open .av-datepop { display: block; }
.av-datepop .wcal-card { width: 300px; box-shadow: 0 16px 40px rgba(1,70,83,.18); }

/* allowances table */
.av-tbl { width: 100%; border-collapse: separate; border-spacing: 0; border: 1px solid var(--line, #EEF2F2); border-radius: 12px; overflow: hidden; }
.av-tbl th { background: var(--teal-soft, #F5F8F8); text-align: left; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; color: #6B7378; padding: 11px 15px; border-bottom: 1px solid var(--line, #EEF2F2); }
.av-tbl td { padding: 11px 15px; border-bottom: 1px solid #F3F6F6; font-size: 13.5px; color: #3A4145; }
.av-tbl tr:last-child td { border-bottom: none; }
.av-tbl .av-amt { text-align: right; font-variant-numeric: tabular-nums; font-weight: 500; white-space: nowrap; }
.av-tbl .av-pct { color: #99A1A5; font-size: 11.5px; font-weight: 400; }
.av-tbl tbody tr:hover td { background: #fcfdfd; }
.av-tbl .av-totrow td { font-weight: 600; background: #fcfdfd; color: var(--ink, #14232A); font-size: 14px; }

/* allowances empty state — nothing loaded until a position is picked */
.av-emptybox { border: 1px dashed #C7CDCF; border-radius: 12px; padding: 26px 20px; text-align: center; background: #F7F8F8; }
.av-emptybox .av-ei { width: 34px; height: 34px; border-radius: 50%; background: #fff; border: 1px solid var(--line, #EEF2F2); display: grid; place-items: center; margin: 0 auto 10px; color: #99A1A5; }
.av-emptybox .av-ei svg { width: 17px; height: 17px; }
.av-emptybox p { font-size: 13px; color: #6B7378; font-weight: 500; margin: 0; }
.av-emptybox p .av-hi { color: #3A4145; }

/* selection groups */
.av-gl { display: block; font-size: 13px; font-weight: 600; letter-spacing: .2px; text-transform: uppercase; color: #6B7378; margin-bottom: 10px; }
.av-pills { display: flex; flex-wrap: wrap; gap: 9px; }

/* radio styled as a pill — real <input type=radio> kept (just visually
   hidden), so the existing document.querySelectorAll('input[name=
   "employee_type"]') change listener keeps firing unmodified. */
.av-pill-input { position: absolute; opacity: 0; width: 1px; height: 1px; overflow: hidden; }
.av-pill { display: inline-block; border: 1px solid var(--line, #EEF2F2); background: #fff; border-radius: 11px; padding: 9px 15px; font: inherit; font-size: 13.5px; font-weight: 500; color: #3A4145; cursor: pointer; transition: border-color .14s, background .14s, color .14s; }
.av-pill:hover { border-color: #cfe0e1; }
.av-pill-input:checked + .av-pill { background: var(--teal, #014653); color: #fff; border-color: var(--teal, #014653); font-weight: 600; }
.av-pill-input:focus-visible + .av-pill { outline: 2px solid var(--teal, #014653); outline-offset: 2px; }

/* checkbox styled as a chip — same technique, keeps the real
   name="recruitement[]" checkboxes jQuery already reads on submit. */
.av-chipc-input { position: absolute; opacity: 0; width: 1px; height: 1px; overflow: hidden; }
.av-chipc { display: inline-flex; align-items: center; gap: 9px; border: 1px solid var(--line, #EEF2F2); background: #fff; border-radius: 11px; padding: 9px 14px; font: inherit; font-size: 13.5px; font-weight: 500; color: #3A4145; cursor: pointer; transition: border-color .14s, background .14s, color .14s; }
.av-chipc .av-box { width: 18px; height: 18px; flex: none; border: 1.5px solid #C7CDCF; border-radius: 6px; display: grid; place-items: center; transition: background .14s, border-color .14s; }
.av-chipc .av-box svg { width: 12px; height: 12px; color: #fff; opacity: 0; transition: opacity .12s; }
.av-chipc-input:checked + .av-chipc { border-color: var(--teal, #014653); background: var(--teal-soft, #F5F8F8); color: var(--teal, #014653); font-weight: 600; }
.av-chipc-input:checked + .av-chipc .av-box { background: var(--teal, #014653); border-color: var(--teal, #014653); }
.av-chipc-input:checked + .av-chipc .av-box svg { opacity: 1; }
.av-chipc-input:focus-visible + .av-chipc { outline: 2px solid var(--teal, #014653); outline-offset: 2px; }

/* segmented toggle — same technique, for the two-radio Status field and
   the two-radio "For local" field. */
.av-seg { display: inline-flex; background: #F7F8F8; border: 1px solid var(--line, #EEF2F2); border-radius: 11px; padding: 3px; gap: 2px; }
.av-seg-input { position: absolute; opacity: 0; width: 1px; height: 1px; overflow: hidden; }
.av-seg label { border: none; background: none; font: inherit; font-size: 13.5px; font-weight: 600; color: #6B7378; padding: 9px 22px; border-radius: 8px; cursor: pointer; transition: background .14s, color .14s; }
.av-seg-input:checked + label { background: var(--teal, #014653); color: #fff; }
.av-seg-input:focus-visible + label { outline: 2px solid var(--teal, #014653); outline-offset: 2px; }

/* conditional service-provider panel */
.av-sppanel { background: var(--teal-soft, #F5F8F8); border: 1px solid #dcebeb; border-radius: 14px; padding: 18px 20px; margin-top: 18px; }
.av-sph { display: flex; align-items: center; gap: 8px; font-size: 13.5px; font-weight: 600; color: var(--teal, #014653); margin-bottom: 16px; }
.av-sph svg { width: 15px; height: 15px; }
.av-subgrid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px 18px; }
@media (max-width: 760px) { .av-subgrid { grid-template-columns: 1fr 1fr; } }
@media (max-width: 520px) { .av-subgrid { grid-template-columns: 1fr; } }

.av-subrow { display: grid; grid-template-columns: 1fr 1fr; gap: 18px 40px; align-items: start; margin-top: 8px; }
@media (max-width: 640px) { .av-subrow { grid-template-columns: 1fr; } }

/* footer — outside .av-flow so it reads as the end of the form */
.av-foot { display: flex; justify-content: space-between; align-items: center; gap: 14px; margin-top: 28px; padding-top: 22px; border-top: 1px solid var(--line, #EEF2F2); }
.av-draft { background: #fff; color: #3A4145; border: 1px solid var(--line, #EEF2F2); border-radius: 12px; padding: 13px 22px; font: inherit; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; transition: border-color .14s, color .14s; }
.av-draft:hover { border-color: #C7CDCF; color: var(--teal, #014653); }
.av-submit { background: var(--teal, #014653); color: #fff; border: none; border-radius: 12px; padding: 14px 34px; font: inherit; font-size: 15px; font-weight: 600; cursor: pointer; transition: transform .16s cubic-bezier(.34,1.56,.64,1), box-shadow .16s ease, background .16s ease; }
.av-submit:hover { background: var(--teal-2, #035b6c); transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.35); }
.av-submit:active { transition-duration: .07s; transform: translateY(0) scale(.98); }
@media (prefers-reduced-motion: reduce) { .av-pill, .av-chipc, .av-seg label, .av-draft, .av-submit, .av-inp { transition: none; } }
</style>
