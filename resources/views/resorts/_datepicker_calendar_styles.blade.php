{{--
    WISDOM AI — canonical pop-up calendar / date picker (shared, app-wide).
    Matches datepicker_calendar_reference.html: a solid white card, month
    label + prev/next chevrons, Sun–Sat headers, and a full 6-row (42-cell)
    grid that always includes greyed, non-clickable adjacent-month days so
    the grid never reflows between months. today = filled teal circle,
    selected = light-teal fill, today+selected = teal fill + seafoam inner
    ring. Single-date select.

    Use this ONE component for every date-selection UI in the app (hold-
    until, filters, interview scheduling, payroll/cutoff dates) instead of
    a native <input type=date>, a library date-picker, or a bespoke one.
    When nested inside a Liquid Glass modal, this card stays solid — no
    glass-on-glass.

    Pairs with resorts._datepicker_calendar_script (the JS that renders/
    navigates the grid and reports the selected date) — include both once
    per page, then call window.wisdomDatepicker.create({...}) per instance
    (see that partial's usage comment).
--}}
<style>
    .wcal-card { position: relative; background: #fff; border: 1px solid var(--line, #EEF2F2); border-radius: 14px; padding: 14px 14px 10px; }
    .wcal-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
    .wcal-head .wcal-m { font-size: 14px; font-weight: 600; color: var(--ink, #14232A); }
    .wcal-nav { display: flex; gap: 6px; }
    .wcal-nav button { width: 28px; height: 28px; border-radius: 8px; border: 1px solid var(--line, #EEF2F2); background: #fff; color: #6B7378; display: grid; place-items: center; cursor: pointer; transition: border-color .14s, color .14s; }
    .wcal-nav button:hover { border-color: #cfe0e1; color: var(--teal, #014653); }
    .wcal-nav svg { width: 14px; height: 14px; }
    .wcal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 3px; }
    .wcal-dow { font-size: 10px; font-weight: 600; color: #99A1A5; text-align: center; padding: 4px 0; }
    .wcal-d { aspect-ratio: 1; display: grid; place-items: center; font-size: 12.5px; color: #3A4145; border-radius: 9px; cursor: pointer; transition: background .12s, color .12s; }
    .wcal-d:hover { background: var(--teal-soft, #F5F8F8); }
    .wcal-d.wcal-mut { color: #C7CDCF; pointer-events: none; cursor: default; }
    .wcal-d.wcal-today { background: var(--teal, #014653); color: #fff; font-weight: 600; }
    .wcal-d.wcal-sel { background: #d3ebe6; color: var(--ink, #14232A); font-weight: 600; }
    .wcal-d.wcal-today.wcal-sel { box-shadow: 0 0 0 2px #4FB49A inset; }
    @media (prefers-reduced-motion: reduce) { .wcal-d, .wcal-nav button { transition: none; } }
</style>
