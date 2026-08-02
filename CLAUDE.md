# HRVMS-Wisdom-AI — Architecture & Working Rules

Read this file fully before starting ANY task in this repo. It exists so every session (yours or a future one) has the same context instead of re-discovering it turn by turn. Update it whenever you learn something here was wrong or incomplete — don't let it go stale.

## What this project is

HR / Workforce Management system for Maldives resorts, multi-tenant (one resort = one `resort_id`). Covers the full employee lifecycle: onboarding, time & attendance, duty rosters, leave, island pass (inter-island travel approval), payroll & salary advances, benefit grids (rank-based compensation policy), performance reviews, grievance & disciplinary, incidents, accommodation/maintenance requests, visa/document management, survey, learning & development, workforce planning, talent acquisition. Two front doors:
- **Web portal** (`resort-admin` guard, session) — HR/HOD/EXCOM/GM staff manage their resort.
- **Mobile app** (`api` guard, Passport token) — employees self-serve (apply leave, punch attendance, request island pass, etc.) and managers approve from their phone.
- **Wisdom AI** — a tier-gated HR chatbot addon (`app/Services/Wisdom`, OpenRouter + tool-calling), separate concern from the modules above.

## Architecture map

- **Routes**: `routes/resort_route.php` (web portal, `resort-admin` guard) and `routes/api.php` (mobile, `api` guard) are the two live route files. **`routes/resort_routemain.php` is dead code** — never `require`d anywhere, don't add routes there even if it looks like a plausible spot.
- **Controllers**: `app/Http/Controllers/Resorts/<Module>/...` for web portal (module folders: Accommodation, Leave, Payroll, People, Performance, TimeAndAttendance, Visa, Incident, GrievanceAndDisciplinery, Survey, Learning, TalentAcquisition, SOS, Support, FileManagment, ChatBoat, plus flat report controllers like `ProbationReportController.php`). `app/Http/Controllers/API/...` for mobile — organized by feature, not by mirroring the web folder structure (e.g. `LeaveController.php` in API also contains Island Pass HR/HOD view endpoints).
- **Multi-tenancy**: `resort_id` scoping on nearly every query. Never write a query against a tenant-scoped table without a `resort_id` filter.
- **Shared helper**: `app/Helpers/Common.php` — the household name for cross-cutting logic (currency formatting, approval-flow builders, geofence resolution, benefit grade resolution, AWS/attachment resolution, etc). When a fix applies to more than one controller, it almost always belongs here, not copy-pasted per caller.
- **`app/Helpers/StorageHelper.php`** — the ONLY correct way to read/write uploaded files. Never use raw `Storage::`, `->move()`, `public_path()`, `asset()`, or `URL::asset()` for anything tenant-uploaded. Prod runs `STORAGE_DRIVER=wasabi`; anything not routed through `StorageHelper` works locally and silently breaks in prod.
- **`module_pages` table**: global (no `resort_id`), drives sidebar/menu labels, seeded via `database/seeders/ResortModulePagesSeeder.php`. Editing the seeder alone only affects fresh installs — a live/seeded label change needs a migration too.

## Hard invariants — do not violate

1. **DB changes go through migrations, never raw tinker mutations.** Tinker is for read-only verification (wrap any mutating test in `DB::beginTransaction()`/`DB::rollBack()`).
2. **Storage is disk-agnostic via `StorageHelper`.** No raw filesystem calls for uploads/attachments.
3. **Money**: `budget`/`salary`/cost aggregation tables store USD. `Common::formatCurrency($amount, 'USD')` converts stored-USD to the resort's display currency — correct for `employees.basic_salary`. For a value that IS whatever currency the user picked at submission time (e.g. `payroll_advance.currency`), use `Common::formatRequestCurrency()` instead — no conversion, display the literal currency stored. Don't mix these two up.
4. **FX rate**: MVR↔USD derives from `DollertoMVR` (currently 15.42); never multiply by a stored inverse rate.
5. **Department-scoped access**: every new list/dashboard must gate via `Common::hasFullDataAccess` + `getScopedDepartmentIds` — non-HR/GM HOD/XCOM only ever see their own department.
6. **Every active employee owns a categorized root folder** in `filemangement_systems` (maintained by an `Employee::created` hook + backfill migration) — don't build a parallel folder-creation path.
7. **Duplicate query paths per view are the norm, not the exception.** The same logical page is frequently served by 2-4 independent, near-identical controller methods (list AJAX endpoint, detail view, dashboard variant, HOD variant, mobile API variant). Before declaring any bug fixed, grep for sibling methods rendering the same page/data and fix all of them — fixing only the one in the reported URL is an incomplete fix.

## Before starting any task

1. Re-read this file. Confirm the task doesn't require changing something listed above as an invariant — if it seems to, stop and ask rather than quietly reinterpreting the rule.
2. Understand what module(s) the task actually touches. Grep first, don't assume — find every controller/view/route/API-endpoint that serves the reported page or data (per invariant #7).
3. Form a plan scoped ONLY to what was asked. Do not refactor, rename, restructure folders, or "clean up" adjacent code while you're in there. Do not touch a different module than the one the task concerns, even if you spot something else wrong nearby — note it to the user instead of fixing it unprompted.
4. Pick the smallest correct fix. Before writing new code: can an existing helper (`Common.php`, `StorageHelper`, etc.) already do this? Can the fix be a guard clause / one changed line instead of a new method? Don't add abstractions, config options, or generalized helpers for a problem that only has one caller today. Three similar lines beat a premature shared abstraction; a shared abstraction beats three copies of a real bug fix that all need to change together later.

## After finishing any task

1. Re-read every file you touched, in full, not just the diff hunk — confirm the change is complete and nothing adjacent broke.
2. Trace the actual data path end to end (form field → controller → DB column → the exact response/view the user will see) rather than assuming a plausible-looking fix is correct.
3. Verify against real data (`php -l`, then `php artisan tinker` against real records; wrap any DB mutation in a transaction and roll it back) — don't report a fix as done on code-inspection alone.
4. Confirm you didn't edit any file outside the scope of the request. If you found an unrelated bug while working, report it, don't fix it in the same pass unless asked.

## Business-model notes worth knowing before touching a module

- **Leave approval**: rank-based chain (Line Worker/Supervisor → reporting manager → HOD/EXCOM → GM), with HR/GM able to shortcut-approve. Clinic-staff (rank 12) stage can comment but never flips the leave to Rejected. No rank-override/skip-ahead exists anywhere.
- **Island Pass approval order is `HOD → HR → SM`** (3 steps) — enforced by execution order in the DB, not by the order rows were inserted.
- **Duty rosters**: geofence zone assignment is roster-level (one `duty_rosters` row = one employee's schedule block), not per-day or per-shift. It's a multi-select (JSON array of zone ids in `geofence_zone_id`, a `text` column — do not change it back to a scalar int).
- **Benefit Grid**: per-employee level override exists — an employee's own assigned level always wins over their rank's default grid; multiple grids can share a rank.
