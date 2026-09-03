{{--
    WISDOM AI — standard dropdown/select component (shared, app-wide).
    Canonical look: trigger + rounded popover panel, optional search, teal
    hover/active + check on the selected item, chevron rotates on open.
    Reads tokens from the live shared palette (resorts/layouts/_design_
    tokens.blade.php) — no local :root, no new colors, theme-aware
    (dark/teal included) same as every other shared partial this session.

    Pairs with resorts._dropdown_script (event-delegated JS — include once
    per page, works for any number of .dd instances on that page).

    Two usage shapes, same markup/CSS for both:
    1) Menu of links (e.g. Predefined Reports) — .dd-item is an <a href>,
       no data-value needed; clicking just navigates, JS only opens/closes/
       filters.
    2) Mirrors a real <select> (progressive enhancement) — give the .dd
       root data-target="#the-select-id", each .dd-item a data-value
       matching an <option value>; picking an item sets the select's value
       and dispatches a real `change` event, so existing change handlers/
       cascades/validation keep firing unchanged. The real <select> stays
       in the DOM (visually hidden, not removed) as the actual form field.
--}}
<style>
    .dd { position: relative; width: 100%; }
    .dd-trigger {
        display: inline-flex; align-items: center; gap: 12px; justify-content: space-between;
        width: 100%; font-family: 'Poppins', sans-serif; font-size: 13.5px; font-weight: 500;
        color: var(--ink); background: #fff; border: 1px solid var(--line); border-radius: 11px;
        padding: 10px 14px; cursor: pointer; box-shadow: 0 1px 2px rgba(var(--teal-rgb), .04);
        transition: border-color .15s;
    }
    .dd-trigger:hover { border-color: var(--faint); }
    .dd.open .dd-trigger { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(var(--teal-rgb), .08); }
    .dd-trigger .dd-lbl { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .dd-trigger .dd-chev { color: var(--faint); transition: transform .18s; flex: none; }
    .dd.open .dd-trigger .dd-chev { transform: rotate(180deg); }
    .dd-trigger:disabled, .dd-trigger[aria-disabled="true"] { cursor: not-allowed; opacity: .6; }
    .dd-trigger.is-invalid { border-color: #dc3545; }

    /* Width: never narrower than the trigger (min-width:100%), but grows
       to fit its widest item (width:max-content) instead of truncating —
       a short list (e.g. Priority Level) stays exactly trigger-width since
       max-content can't be smaller than min-width; a list with long
       labels (e.g. Predefined Reports' "Salary Advance & Loan Reports")
       widens the panel itself rather than ellipsis-cutting the text.
       max-width caps it so it can't run off-screen or turn into an
       absurdly wide overlay when one label is truly long. */
    .dd-panel {
        position: absolute; top: calc(100% + 8px); left: 0; z-index: 1040;
        min-width: 100%; width: max-content; max-width: min(90vw, 420px);
        background: var(--card); border: 1px solid var(--line); border-radius: 14px;
        box-shadow: 0 16px 40px rgba(var(--teal-rgb), .16), 0 2px 8px rgba(var(--teal-rgb), .07);
        padding: 6px; opacity: 0; visibility: hidden; transform: translateY(-4px);
        transition: opacity .15s, transform .15s;
    }
    .dd.open .dd-panel { opacity: 1; visibility: visible; transform: none; }

    .dd-search { position: relative; margin: 2px 2px 6px; }
    .dd-search svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--faint); pointer-events: none; }
    .dd-search input {
        width: 100%; font-family: 'Poppins', sans-serif; font-size: 13px; color: var(--ink);
        background: var(--card); border: 1px solid var(--line); border-radius: 9px;
        padding: 9px 12px 9px 34px; outline: none;
    }
    .dd-search input:focus { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(var(--teal-rgb), .08); }

    .dd-scroll { max-height: 300px; overflow: auto; padding: 1px; }
    .dd-scroll::-webkit-scrollbar { width: 8px; }
    .dd-scroll::-webkit-scrollbar-thumb { background: var(--faint); border-radius: 5px; border: 2px solid var(--card); }

    .dd-item {
        display: flex; align-items: center; gap: 11px; padding: 10px 12px; border-radius: 9px;
        font-size: 13.5px; color: var(--ink); cursor: pointer; transition: background .12s, color .12s;
        text-decoration: none;
    }
    .dd-item:hover { background: var(--teal-soft); color: var(--ink); }
    .dd-item.active { color: var(--teal); font-weight: 600; background: var(--teal-soft); }
    .dd-item .dd-nm { flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .dd-item .dd-tick { margin-left: auto; color: var(--teal); opacity: 0; flex: none; }
    .dd-item.active .dd-tick { opacity: 1; }
    /* mirrors a real disabled <option> — e.g. an already-paid payroll
       period that shouldn't be re-selectable from the list. */
    .dd-item[aria-disabled="true"] { opacity: .45; cursor: not-allowed; pointer-events: none; }
    .dd-empty { padding: 16px 12px; font-size: 12.5px; color: var(--faint); text-align: center; }

    /* mode 2: the real <select> stays the source of truth, just hidden —
       never display:none (some plugins/validators skip those). */
    .dd-native-select { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
        overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0; }

    @media (prefers-reduced-motion: reduce) {
        .dd-trigger, .dd-trigger .dd-chev, .dd-panel, .dd-item { transition: none; }
    }
</style>
