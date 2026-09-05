# Commit `b59ab43dc` — Incident Dashboard Rebuild (verify before deploying)

**Branch:** `Amey/design` · **Scope:** Frontend only, Incident module.

## What changed
The HR / Admin / HOD Incident dashboards no longer use Chart.js — their widgets (KPI tiles, category/severity charts, trend chart, department participation) are rebuilt with plain CSS + inline SVG via 3 new shared partials. The Investigation page, Incident detail view, and Meeting calendar/detail pages were updated to match the same visual style.

## Files
- `resources/views/resorts/incident/dashboard/{admindashboard,hoddashboard,hrdashboard}.blade.php`
- `resources/views/resorts/incident/dashboard/_dashboard_{body,scripts,styles}.blade.php` (new)
- `resources/views/resorts/incident/incident/{investigation,view}.blade.php`
- `resources/views/resorts/incident/meeting/{calendar,detail}.blade.php`

## Please verify before pushing to production
- [ ] All 3 role dashboards (HR / Admin / HOD) load without console errors and every widget renders (KPIs, category/severity, trend, department participation, upcoming meetings, preventive measures).
- [ ] Incident List → Investigation page and Incident detail view still submit/save correctly.
- [ ] Meeting calendar and meeting detail pages still work (schedule, view, respond).
- [ ] No AJAX 404/500s on incident-dashboard endpoints (check network tab).

## Not included in this commit
No backend/route/migration changes, and no theme (dark/light) work — that's a separate, still-unfinished initiative kept out of this push on purpose.
