# Backend issues found while unifying dropdowns

Found while doing a frontend-only pass to replace select2/native `<select>` styling with
the new standard dropdown component, module by module, across `resources/views/resorts`.
These are pre-existing backend bugs, unrelated to the dropdown work — not fixed, since this
pass is presentation-only. Listed here for a developer to pick up.

---

## 1. `IndexGrievanceDelegationRule` — wrong view path, page 404s

**File:** `app/Http/Controllers/Resorts/GrievanceAndDisciplinery/ConfigurationController.php:2668`

```php
return view('resorts.GrievanceAndDisciplinery.configurationGIndexGrievanceDelegationRule',compact('GrievanceCategory','page_title'));
```

The view string is missing `.Grievance.` — it reads `configurationGIndexGrievanceDelegationRule`
instead of `configuration.Grievance.IndexGrievanceDelegationRule`. The actual Blade file lives at
`resources/views/resorts/GrievanceAndDisciplinery/configuration/Grievance/IndexGrievanceDelegationRule.blade.php`
and exists correctly — the controller just can't find it, so this page currently throws
`View [resorts.GrievanceAndDisciplinery.configurationGIndexGrievanceDelegationRule] not found`
(confirmed via direct render) and 404s for every user in production.

**Fix:**
```php
return view('resorts.GrievanceAndDisciplinery.configuration.Grievance.IndexGrievanceDelegationRule',compact('GrievanceCategory','page_title'));
```

The Blade view itself has already been updated for the new dropdown component and is ready —
this is the only blocker for that page working at all.

---

## 2. `grivance/create.blade.php` — subcategory cascade only ever keeps the last option

**File:** `resources/views/resorts/GrievanceAndDisciplinery/grivance/create.blade.php`,
`$(document).on("change","#Grivance_Cat_id ", ...)` handler.

This is frontend JS, not backend, but found in the same pass so noting it here too.

```js
var option = "";
$(response.data).each(function(i, a) {
    option = `<option value="${a.id}">${a.Sub_Category_Name}</option>`;  // = overwrites, not +=
});
$("#Grivance_Sub_cat").html(option);
```

`option` is reassigned (`=`) on every loop iteration instead of appended (`+=`), so if a
category has more than one subcategory, only the **last** one from `response.data` ever makes
it into the "Select Offense" dropdown — the rest are silently dropped. Left as-is (not part of
the dropdown-styling task and changes real submission behavior) — the new dropdown component
faithfully mirrors whatever ends up in the `<select>`, so it reproduces this bug rather than
hiding it, which is what surfaced it.

**Fix:** change `option = ` to `option += ` on that line.

---

## 3. Two Grievance & Disciplinary dashboard views are unreachable dead code

**Files:**
`resources/views/resorts/GrievanceAndDisciplinery/dashboard/admindashboard.blade.php`
`resources/views/resorts/GrievanceAndDisciplinery/dashboard/hoddashboard.blade.php`

Neither is ever rendered by any controller in the app:

- **`admindashboard.blade.php`** — its route (`routes/resort_route.php:121,135`,
  `grievance-and-disciplinary/admin-dashboard`) points to
  `DashboardController@Admin_Dashobard`, but **no method with that name exists**
  on `app/Http/Controllers/Resorts/GrievanceAndDisciplinery/DashboardController.php`.
  Hitting this route throws a `BadMethodCallException` for every user right now.
- **`hoddashboard.blade.php`** — the route that should serve it
  (`grievance-and-disciplinary/hod-dashboard` area) maps to `Hod_dashboard()`,
  which actually renders `resorts.GrievanceAndDisciplinery.dashboard.hrdashboard`
  (the same view `HR_Dashobard()` and `excom_dashboard()` use, switched by a
  `dashboard_label` request param) — never `hoddashboard.blade.php`. That file
  has no route pointing at it at all.

Only `hrdashboard.blade.php` is actually live, serving HR/HOD/EXCOM alike via
the `dashboard_label` param.

Not fixed as part of this pass — both files were still updated for the new
dropdown component for consistency (in case they're wired up later), but as
of now a developer should either connect them to real routes/controller
methods or delete them as leftover dead code.

---

## 4. `configuration/index.blade.php` — several entire feature blocks are commented out

**File:** `resources/views/resorts/GrievanceAndDisciplinery/configuration/index.blade.php`

This single "Configuration" hub page (4,300+ lines) has multiple whole sections wrapped in
`{{-- --}}` / `<!-- -->` comments, so their forms, "Add More" buttons, and the AJAX
cascades behind them are all unreachable in production. Confirmed by locating every
matching comment-block boundary in the file, not by inspection of a few lines (an earlier,
shallower read of this file briefly and wrongly concluded one of these sections was live —
worth being aware the boundaries are non-obvious, some spans run 100+ lines with ordinary
markup in the middle):

- Grievance Delegation Rules (static form + its "Add More" JS template)
- Investigation Templates (Grievance side)
- Grievance Appeal Type / Committee config
- Hearing & Investigation Templates (Disciplinary side)
- Disciplinary Delegation Rules (static form + its "Add More" JS template)
- Approval Roles
- Disciplinary Appeal Type / Committee config (+ the shared `.on('change', '.Appeal_type', ...)`
  cascade that populates it — unreachable on both the Grievance and Disciplinary side)

None of this was changed — flagging only, since fixing/removing it is a product decision,
not part of a presentation-layer pass. The dropdown-component conversion in this same pass
only touched the sections confirmed **live** (rendered by the page today); the dead
sections' `<select>`s were left on their old styling since converting unreachable markup
provides no user-facing benefit and this note gives a developer a precise map instead. A
resort-side user browsing this page today only ever sees: Grievance Categories,
Grievance Sub-Categories, Grievance Categories & Sub-Categories (combined), Resolution
Timelines, Escalation, Key Personnel, Grievance Committees, Follow-up Actions, Code of
Conduct, Disciplinary Committees, Grievance Template, and Email Template — everything
else listed above is invisible to them regardless of permissions.

---

## 5. Performance module: HOD/Admin/XCOM dashboards are empty controller stubs — routes return nothing

**File:** `app/Http/Controllers/Resorts/Performance/PerformanceDashboardController.php:34-37,386-390`

```php
public function Admin_dashboard()
{

}

public function Hod_dashboard(Request $request)
{
    $dashboardLabel = request('dashboard_label', 'HOD');
    $page_header = '<span class="arca-font">'.$dashboardLabel.'</span> Dashboard';
}

public function excom_dashboard()
{
    request()->merge(['dashboard_label' => 'XCOM']);
    return $this->Hod_dashboard(request());
}
```

None of these three methods return a `view(...)` — they fall through to an implicit `null`
return. The three routes that hit them (`routes/resort_route.php:117-118,175`:
`performance/hod-dashboard`, `performance/admin-dashboard`, `performance/xcom-dashboard`)
are all currently broken for every user — only `HR_Dashobard()` (line 38, `performance/hr-dashboard`)
actually builds data and returns a view (`resorts.Performance.dashboard.hrdashboard`).

The two Blade files that look like they should back these routes —
`resources/views/resorts/Performance/dashboard/hoddashboard.blade.php` and
`resources/views/resorts/Performance/dashboard/admindashboard.blade.php` — are themselves
present, non-trivial, and reference real Time & Attendance routes/endpoints (e.g.
`resort.timeandattendance.GetYearWiseAttandanceData`, `resort.timeandattendance.HodDashboardCount`),
suggesting they were copied from `resources/views/resorts/timeandattendance/dashboard/` as a
starting point and never wired up. They are dead code today — unreachable by any route.

Not fixed as part of this pass (same reasoning as finding #3 above) — both files were still
updated for the new dropdown component for consistency, in case a developer finishes wiring
`Hod_dashboard`/`Admin_dashboard`/`excom_dashboard` up to return
`view('resorts.Performance.dashboard.hoddashboard', ...)` / `admindashboard` later.

---

## 6. `admindashboard.blade.php` (Performance) — malformed Blade tags break compilation of the whole page

**File:** `resources/views/resorts/Performance/dashboard/admindashboard.blade.php`, "Send Interview
Request" modal, the "Applicant's Clock" time-slot list (originally around lines 714-738, before
this pass's edits).

Six repeated rows are missing the closing `)` on `URL::asset(...)`:

```blade
<img src="{{ URL::asset('resorts_assets/images/flag-maldives.webp'}}" alt="flag">10:00am
```

(and one row is worse — no `{{ URL::asset(` at all: `src="assets/images/flag-maldives.webp'}}"`).
Every correctly-formed `flag-India.webp` tag two lines below each of these confirms what was
intended. Because these are literal syntax errors, not runtime bugs, they make the **entire
view fail to compile** — confirmed via `Blade::compileString()` throwing "unexpected token
';', expecting ')'" for the whole file, and via `view(...)->render()` throwing the same. This
page cannot render at all for anyone, on top of already being unreachable per finding #5.

Not fixed here — out of scope for a presentation-only dropdown pass, and this view has no live
route today anyway. To verify this pass's own dropdown markup was correct despite the page-wide
compile failure, the "Top Hiring Sources" card fragment was extracted and compiled/rendered in
isolation rather than the full page.

**Fix:** add the missing `)` before `}}` on each of the six malformed lines, and add the missing
`{{ URL::asset('resorts_assets/images/` prefix to the one that's missing it entirely.

---

## 7. `timeandattendance/dashboard/admindashboard.blade.php` — dead duplicate of the Performance one, same typo, also unreachable

**Files:**
`resources/views/resorts/timeandattendance/dashboard/admindashboard.blade.php`
`app/Http/Controllers/Resorts/TimeandAttendanceDashboardController.php:384` (`admin_dashboard()`)

This file is a near-identical copy of finding #6's Performance-module twin — same "Top Hiring
Sources" mock card duplicated once live and once inside a `{{-- --}}` comment (lines 279-330),
same six malformed `URL::asset('resorts_assets/images/flag-maldives.webp'}}` tags (missing
closing `)`, lines 714-738) that make the **entire view fail to compile**.

Unlike the Performance case, `admin_dashboard()` here is not an empty stub — it builds real
`ResortPosition`/`ResortDepartment`/`EmployeesCount` data — but it returns
`view('resorts.timeandattendance.dashboard.hrdashboard', ...)`, not `admindashboard`. The route
`time-and-attendance/admin-dashboard` (`resort.timeandattendance.admindashboard`) therefore
renders `hrdashboard.blade.php` for every user; `admindashboard.blade.php` has no route pointing
at it anywhere in the app and is dead code, exactly like its Performance-module counterpart.

Not fixed here, same reasoning as finding #6 — the live "Top Hiring Sources" select was still
converted to the dropdown component for consistency (verified via an isolated fragment
compile/render, since the full page won't compile), in case a developer later wires this view
up or copies its markup elsewhere.

**Fix:** either point `admin_dashboard()` at `resorts.timeandattendance.dashboard.admindashboard`
(if this view was meant to be the real admin dashboard) and fix the six malformed tags, or delete
the file as dead leftover — a developer familiar with the module's intent should decide which.

---

## 8. `timeandattendance/dutyroster/CreateDutyRoster1.blade.php` — unreachable draft, superseded by `CreateDutyRoster.blade.php`

**File:** `resources/views/resorts/timeandattendance/dutyroster/CreateDutyRoster1.blade.php`

No route or controller method anywhere in the app returns this view (confirmed via a full grep
of `app/Http/Controllers` and `routes/`) — it appears to be an earlier draft of
`dutyroster/CreateDutyRoster.blade.php` (the live "Create Duty Roster" page, same route's
actual view) left behind after that page was rebuilt. It compiles fine on its own and isn't
broken by a typo like findings #6/#7 — it's simply never linked to from anywhere.

Not fixed/deleted here — out of scope for a presentation-only pass, and deleting a whole file
is a bigger call than this pass should make unilaterally. The six selects in it were still
converted to the dropdown component for consistency (verified via isolated fragment
compile/render + interaction test, not a full-page render, since populating this page's
`$Rosterdata`/`$RosterInternalData` roster-table fake data wasn't worth the effort for a page
nobody can reach).

**Fix:** delete the file, unless a developer confirms it's meant to replace the live
`CreateDutyRoster.blade.php` (in which case wire up a route/controller method for it instead).

---

## 9. `resources/views/resorts/Survey/talentacquisition/` — an entire 18-file, 63-select subtree is dead, duplicating the real Talent Acquisition module

**Files:** everything under `resources/views/resorts/Survey/talentacquisition/` (Applicants, Emaildrafting,
config, dashboard, interview-assessment, jobdescription, questionnaire, vacancies — 18 files
with `<select>`s, plus more without).

This is a near-complete second copy of the real Talent Acquisition module (which lives at
`resources/views/resorts/talentacquisition/`, unrelated to Survey), sitting inside the Survey
folder. Checked every controller and route in the app for `resorts.Survey.talentacquisition.*`
— zero matches anywhere. No route points at it, no controller renders it, nothing includes it.
The only genuinely live views under `resources/views/resorts/Survey/` are `SurveyPages/*` and
`dashboard/{admindashboard,hrdashboard}.blade.php` (11 selects total, not 74).

Not converted or fixed here — flagged to the user directly before starting the "Survey" pass
(since 63 of the module's 74 selects turned out to live in this dead subtree), and the user
asked to skip it entirely rather than convert dead code at this scale. Left completely
untouched, old select2/native styling and all.

**Fix:** delete the `resources/views/resorts/Survey/talentacquisition/` directory — a developer
familiar with the module's history should confirm it's genuinely a leftover copy (e.g. from a
folder move or an abandoned "Survey ↔ hiring pipeline" integration) before removing it, in case
something outside this app's own routing (a queued job, an external link) still depends on the
path.

---

## 10. `configration/FloorRoomIndex.blade.php` — the inline-edit Building dropdown isn't actually submitted

**File:** `resources/views/resorts/Accommodation/configration/FloorRoomIndex.blade.php`, the
`#FloorAndRoomIndex .edit-row-btn` / `.update-row-btn_cat` handlers.

Clicking a row's edit icon swaps that row's cells for an editable form, including a Building
select. The "Submit" button, however, reads the building id from its own
`data-buildingId="${Builing_id}"` attribute — stamped once, at row-edit-open time, from the
row's *original* building — not from the select's current value. Changing the dropdown has no
effect on what gets submitted; `Floor`/`Room` (plain text inputs) are the only fields the submit
handler actually re-reads from the DOM. Pre-existing, not introduced by this pass — the same gap
existed with the old select2 widget, just harder to notice since select2 didn't make the
disconnect any more or less obvious.

Related: the edit form always reuses the literal id `id="building_id"` for every row's select,
with no per-row uniqueness. Opening a second row's edit form while a first is still open (the
code has no guard against this — there's no "close previous edit" step) leaves two elements with
the same id in the DOM; `document.querySelector`-based lookups (this pass's `.dd` sync included)
then resolve to whichever copy is first in DOM order, not necessarily the one the user is looking
at. Not fixed here — reproducing this exact one-id-in-use-at-a-time assumption is what the
original code already relied on (its own `$("#building_id").select2(...)` call had the identical
issue), so this pass's `.dd` conversion is no worse than before, just not better either.

Not fixed — both are pre-existing JS logic bugs, out of scope for a presentation-only pass.

**Fix:** on `.update-row-btn_cat` click, read `$row.find('select[name="building_id"]').val()`
instead of the button's stale `data-buildingId`. For the id-collision issue, give each opened
edit-row a unique select id (e.g. suffixed with the row's `Main_id`) instead of the fixed
`building_id`, and update the `data-target` to match.

## 11. `Maintanance/MaintanaceRequestlist.blade.php` — `#ForwardToHOD-Model` modal and its `#select_emp` select are dead

**File:** `resources/views/resorts/Accommodation/Maintanance/MaintanaceRequestlist.blade.php`,
lines 94-126 (modal markup) and the `$(document).ready` select2 init at the top of
`import-scripts`.

The `.ForwardToHOD` click handler (the row action that used to open this modal) was rewritten to
use a `wisdomConfirm` SweetAlert auto-approve flow instead — the comment directly above the
`$(document).ready` block even says so ("ForwardToHOD now uses SweetAlert auto-approve flow (see
below)"). Nothing in the file calls `$("#ForwardToHOD-Model").modal('show')` any more, so the
modal, its `#ForwardToHODForm`, and its `#select_emp` HOD-picker select are all unreachable
markup — confirmed via grep, zero references beyond the modal's own definition. This is the
sibling file `Maintanance/HODMaintanaceRequestlist.blade.php`'s *live* `#select_emp` modal,
left behind here after that file's version was migrated to the SweetAlert flow but this one's
dead copy was never deleted.

Left `#select_emp` on the old select2 widget (not converted to `.dd`) since converting dead
markup provides no value and this pass only touches genuinely live selects. `#ResortDepartment`
(the live filter-row select, also in this file) was converted normally.

**Fix:** delete the `#ForwardToHOD-Model` modal block (lines 94-126), the `#ForwardToHODForm`
Parsley/select2 leftovers, and the dead `$("#select_emp").select2(...)` init once a developer
confirms no other flow still expects this modal to exist.

**Update:** the same dead `#ForwardToHOD-Model` / `#select_emp` leftover also exists in
`resources/views/resorts/Accommodation/dashboard/hrdashboard.blade.php` — same cause (its
`.ForwardToHOD` handler was likewise migrated to the `wisdomConfirm` SweetAlert auto-approve
flow, confirmed via grep: no `.modal('show')` targets `#ForwardToHOD-Model` anywhere in that
file either, only two harmless `.modal('hide')` calls). Left `#select_emp` on its old select2
widget there too, for the same reason. `admindashboard.blade.php`, by contrast, still opens this
modal via its own `.ForwardToHOD` handler (`$("#ForwardToHOD-Model").modal('show')` is present
there) — its `#select_emp` is genuinely live and was converted normally.

## 12. `configration/index.blade.php` — the `Add More` row templates' error-container ids don't actually vary per row

**File:** `resources/views/resorts/Accommodation/configration/index.blade.php`, the
`.addMore-availabel` and `.addMore-Occupancy_threshod` click handlers' JS template strings.

Several dynamically-added rows in both "Add More" flows give their Parsley error-container
`<div>`s an id like `#type_accom_b_error_{{$k}}` / `#type_accom_type_accom_r_type_error_{{$k}}` /
`#type_accom_type_accom_cleaningschedule_error_{{$k}}`. `$k` here is not a per-row counter — it's
the loop variable Blade left behind from the **RoomType** `@foreach ($ROOM as $k=>$a)` on that
same static row (`config('settings.eligibilty')`'s keys, e.g. `8`/`1`/`2`/`4`/`5`/`6`), reused
because Blade compiles the whole file as one PHP scope, so `$k` keeps whatever value the last
completed `@foreach` left it at. It is NOT scoped to "this JS-added row" — every row added via
"Add More" gets the exact same literal id (Blade evaluates `{{$k}}` once, at page-render time,
long before any row is actually cloned in the browser), so `#type_accom_b_error_6` (or whichever
key the config's last entry has) is duplicated across every dynamically-added row. This means
Parsley's `data-parsley-errors-container` lookups for these three fields can only ever place an
error message in the FIRST such div in the DOM, not the one belonging to the row the user is
actually filling in.

Not fixed here — this is a pre-existing JS/Blade-scoping bug in the row-cloning logic itself,
unrelated to and no worse for the dropdown conversion (the newly added `.dd` markup for these
selects doesn't reference `$k` at all, so it isn't itself affected — only the neighboring error
`<div>` ids are).

**Fix:** use the row's own counter (`${AvailableCount}` / `${AvailablethreshodCount}`) for these
three error-container ids instead of the leftover `{{$k}}`, matching how every other field on the
same row already does it (e.g. `#type_accom_f_error_{{$k}}` should really read
`#type_accom_f_error_${AvailableCount}`, which is also affected — grep the file for `{{$k}}`
inside a JS template literal to find every occurrence).

## 13. `talentacquisition/Applicants/shortlisted.blade.php` — entire file is dead, duplicating `vacancies/shortlisted.blade.php`

**File:** `resources/views/resorts/talentacquisition/Applicants/shortlisted.blade.php`.

Zero references anywhere in `app/` or `routes/` — no controller `view()` call, no route. The
live "shortlisted applicants" page is `VacancyController::shortlisted()`, which renders
`resorts.talentacquisition.vacancies.shortlisted` (a different, real file with dynamic data).
This `Applicants/shortlisted.blade.php` is a leftover: hardcoded mock rows ("John Doe",
"Christian Slater", lorem.com emails, "97%" scores) and a `<select>` filter with literal
`abc`/`abc` options — nothing on the page reads any variable except `$page_title`, and its
`import-scripts` block is empty (no JS wiring for the select or the table at all).

Not fixed here — left the page's one `<select>` unconverted (still plain `class="form-select"`,
no `.dd`) since converting dead markup provides no value and this pass only touches genuinely
live selects, matching the same call made for Accommodation's dead `#ForwardToHOD-Model`
leftovers (see finding #11).

**Fix:** delete the file once a developer confirms nothing else expects it to exist.

## 14. `talentacquisition/vacancies/index.blade.php` — the three filter dropdowns are unwired mocks

**File:** `resources/views/resorts/talentacquisition/vacancies/index.blade.php`, the card-header
filter row (Vacancy / Department / Positions selects).

Unlike `Applicants/shortlisted.blade.php` (finding #13), this page IS the live, routed view for
`resort.vacancies.index` (`/talent-acquisition/view-vacancies` → `VacancyController::index()`),
so its selects were converted normally for visual consistency. But all three are still
placeholder mocks: hardcoded `<option value="1">abc</option>` / `<option value="2">abc</option>`
options, and `import-scripts` (confirmed by reading it in full) never reads any of their values —
no `.on('change', ...)` handler, no `.val()` call anywhere. The table below them is a plain
client-side `.data-Table` init with no server-side `ajax:` source and no column tied to these
selects either, so picking any option changes the dropdown's own label and nothing else on the
page.

Not fixed here — wiring up real filtering is a feature gap, not a dropdown-styling issue; out of
scope for a presentation-only pass. The `.dd` conversion itself is correct and matches the
mock data verbatim.

**Fix:** either wire these three selects to real data + a table filter (most likely intent, given
every sibling list page in this module has working Vacancy/Department/Position filters), or
remove them if this page is meant to stay a bare table.

## 15. `talentacquisition/questionnaire/createOne.blade.php` and `createtwo.blade.php` — dead draft duplicates of `create.blade.php`

**Files:** `resources/views/resorts/talentacquisition/questionnaire/createOne.blade.php` (8
selects) and `.../createtwo.blade.php` (10 selects).

Zero references anywhere in `app/` or `routes/` — no controller `view()` call, no route, for
either file. The live "Add Questionnaire" page is `QuestionnaireController::create()` →
`resorts.talentacquisition.questionnaire.create` (already converted in this pass). These two
read as earlier draft iterations of that same form (same `#StoreQuestionnaire` form id, same
Division/Department/Position/question-type cascade, same "Add More" row-cloning), abandoned once
`create.blade.php` replaced them, but never deleted. `createOne.blade.php` even still includes
`resources.resorts._emotional_buttons_v2_styles` — the *Accommodation* module's button-style
partial, not this module's own `_ta_buttons_v2_styles` — a leftover copy-paste detail no one
caught because the page is never rendered.

`createOne.blade.php`'s selects were already converted to `.dd` before this dead-ness was
noticed (checked only after finishing the file, breaking from this pass's usual practice of
checking liveness first) — left the conversion in place since it's correct and harmless on an
unreachable page, not worth reverting. `createtwo.blade.php` was left unconverted (plain
selects, no `.dd`) once its dead status was caught before starting it.

**Fix:** delete both files once a developer confirms nothing references them.

## 16. `people/employee/advance-salary/show.blade.php` — an overdue installment's month picker never actually shows as selected, and its own error-rollback re-triggers the same gap

**File:** `resources/views/resorts/people/employee/advance-salary/show.blade.php`, the
`.month-select` inside the Pending-installment branch of the Repayment Schedule table.

`$availableMonths` (built in `AdvanceSalaryController::show()`) is always "today forward 36
months" — it never includes any month before the current one. The select's `<option>`s are
built from that list, with `selected` applied to whichever one equals `$month` (the row's real
`repayment_date`, formatted). For a still-`Pending` installment whose month has already passed
(a realistic case — an approved schedule can run behind if not marked Paid on time; reproduced
live with a real `payroll_advance_id=128` row on 2026-09-03, where a Pending row's month was
"July 2026", two months before "now"), `$month` is not in `$availableMonths` at all, so **no**
`<option>` gets `selected` — the browser silently defaults to showing the *first* option
(the current month) while the row's `data-original="{{ $month }}"` attribute still claims the
stale, unmatched month. Confirmed via live render: `.val()` on page load actually reads the
first `$availableMonths` entry, not `$month`.

The `change` handler's own error/reject path compounds this: it rolls back via
`$select.val(previousMonth)` where `previousMonth = $select.data('original')` — i.e. the same
stale, never-actually-selected month — leaving the select with no matching option
(`selectedIndex` becomes `-1`) after any failed month-change request on one of these rows.

Not fixed here — this is a pre-existing data/JS mismatch in `AdvanceSalaryController::show()`
and the page's own rollback logic, unrelated to the dropdown conversion. The `.dd` markup was
authored to faithfully mirror what the real `<select>` actually renders (first-option fallback)
rather than trusting `$month` for its initial label/active state, so the conversion doesn't
introduce a NEW visible mismatch — the `.dd`'s `data-original` attribute was deliberately left as
the original (buggy) `{{ $month }}` value to match pre-existing behavior byte-for-byte, not
silently corrected.

**Fix:** either extend `$availableMonths` to include months from the recovery schedule's actual
start date (so a legitimately-overdue month is a selectable, correctly-preselected option), or
have the controller detect this case and flag the row for the HR/Finance user instead of quietly
defaulting to the wrong month.

---

## 17. `.dd` component limitation — a select rendered inside a SweetAlert2 modal's `html` cannot use it (`people/probation/detail.blade.php`'s `#employmentTypeSelect`)

**File:** `resources/views/resorts/people/probation/detail.blade.php`, the Employment Type
picker inside `promptConfirmSuccess()`'s `wisdomConfirm({ extra: { html: ... } })` call.

Not a bug — a structural limitation of the shared `.dd` component, discovered while attempting
this conversion, worth recording so a future pass doesn't repeat the attempt. SweetAlert2 always
renders its `html` option's content inside `.swal2-html-container`, which SweetAlert2 sets to
`overflow: auto` (so long modal bodies scroll) regardless of the `customClass` overrides this app
already applies. `.dd-panel` is `position: absolute` and opens *below* the trigger, i.e. outside
the container's own content box — inside a normal page that's fine (nothing clips it), but inside
`.swal2-html-container` the overflow:auto clips it: confirmed live that `.dd.open` and
`.dd-panel`'s opacity/visibility all report correctly opened, yet the panel is genuinely invisible
on screen (`getComputedStyle` confirms the ancestor's `overflow-y: auto` is doing the clipping).
Reverted to the plain native `<select class="swal2-input">` for this one control rather than ship
a dropdown that silently fails to render when opened.

**Fix (if this is ever revisited):** either give `.dd-panel` a `position: fixed` variant used only
when its `.dd` ancestor is inside `.swal2-html-container` (computed at open-time, not CSS alone,
since a fixed-position panel needs its own top/left math from the trigger's `getBoundingClientRect()`),
or skip the SweetAlert case entirely and keep native selects for any future modal-embedded picker —
this app doesn't currently have another modal-embedded `<select>` to prioritize the general fix.

---

## 18. `people/orgchart/index1.blade.php` — entire file is dead, no controller returns it

**File:** `resources/views/resorts/people/orgchart/index1.blade.php`.

`OrganizationChartController::index()` (the only controller method with a `people.org-chart`
route) returns `resorts.people.orgchart.index`, not `index1`. Grepped the whole app for
`orgchart.index1` / `orgchart/index1` — zero references anywhere. This file's `#departmentSelect`
was already converted to the `.dd` component before this was discovered (found while looking up
its route to live-verify the conversion); left as converted rather than reverted, since a dead
file's markup being on the new component is harmless and matches the precedent set with
`talentacquisition/questionnaire/createOne.blade.php` (finding #15) — not fixed/removed here
since deleting dead files is outside this pass's frontend-styling-only scope.

---

## 22. `.dd` component limitation — same clipping problem as #17, this time inside a `.table-responsive` wrapper (`people/promotion/hrdashboard.blade.php`'s per-row position picker)

**File:** `resources/views/resorts/people/promotion/hrdashboard.blade.php`, the dynamically
inserted `#edit-new-position-{id}` select inside `#promotionTable .edit-row-btn`'s click handler.

Second confirmed instance of the same structural gap noted in finding #17. `#promotionTable` sits
inside Bootstrap's `.table-responsive` wrapper (`overflow: auto` on both axes, needed so the wide
table scrolls horizontally on small screens). `.dd-panel` is `position: absolute` and opens below
its trigger — outside the wrapper's own content box — so `.table-responsive`'s overflow clips it
invisible, exactly like `.swal2-html-container` did in #17: confirmed live (`.dd.open` true,
`panelOpacity: 1`, but `panel.getBoundingClientRect().bottom` well past
`wrapper.getBoundingClientRect().bottom`). The original code's `select2({dropdownParent:
$('#promotionTable')})` exists specifically to dodge this — select2 renders its dropdown outside
the clipping ancestor. Reverted this one select to that original select2 setup rather than ship an
invisible dropdown; the other 4 selects on this page (`basicSalaryFilter`, `empFilter`,
`deptFilter`, `statusFilter` — none inside a scrolling wrapper) converted to `.dd` normally.

**Fix (if this is ever revisited):** same general fix as #17 — a `position: fixed` panel variant
for a `.dd` whose ancestor chain includes a scrolling/clipping container, computed from the
trigger's `getBoundingClientRect()` at open-time. Two confirmed real occurrences now (a SweetAlert2
modal and a `.table-responsive` wrapper) make this worth prioritizing if a third comes up.

---

## 19. `people/liability/add-cost.blade.php` — routed and controller-served, but its only UI link is commented out, and its form is a hardcoded mock

**File:** `resources/views/resorts/people/liability/add-cost.blade.php`, and its only
in-app link at `resources/views/resorts/people/liability/index.blade.php:275`.

`LiabilityEstimationController::addCost()` does return this view (route
`people.liability.addCost` works if visited directly), so it's not dead code the way the
already-documented cases are — but the only `<a>` that ever pointed to it is commented out, so no
normal user flow reaches it. Even if reached, the page is non-functional: both selects
(`#employee_name`, `#category`) have hardcoded placeholder options (`"aaa"`/`"aaa"`), and the
Submit button is a bare `<a href="#">` with no click handler anywhere in the file — nothing here
actually saves a cost. Converted anyway (same reasoning as finding #14's mock filters — a
routed, controller-served page, not a confirmed-dead file/modal) rather than skipped.

**Fix:** either wire this page up for real (real employee/category options, an actual submit
handler posting to a store endpoint) and un-comment its link, or remove the route/view/link
together if the feature was superseded by something else and simply never cleaned up.

---

## 20. `people/config/index1.blade.php` — entire file is dead, no controller returns it

**File:** `resources/views/resorts/people/config/index1.blade.php` (906 lines, 2 selects).

Grepped the whole app for `config.index1` / `config/index1` — zero references anywhere; no
controller returns this view. `resources/views/resorts/people/config/index.blade.php` (1261
lines) is the live counterpart actually served by `PeopleConfigController` (route
`people.config.index`). Skipped — not converted, matching the precedent for other confirmed-dead
sibling files (findings #13, #18).

---

## 21. `people/onboarding/index1.blade.php` — entire file is dead, no controller returns it

**File:** `resources/views/resorts/people/onboarding/index1.blade.php` (1209 lines, 4 selects).

Grepped the whole app for `onboarding.index1` — zero references. `resources/views/resorts/people/onboarding/index.blade.php`
(1735 lines) is the live counterpart. Skipped, matching the precedent for the other dead
`index1` siblings (findings #18, #20) — this app has a recurring pattern of an abandoned
`index1.blade.php` left alongside the real `index.blade.php` in several People sub-modules.

---

## 23. `people/employee/list1.blade.php` — entire file is dead, no controller returns it

**File:** `resources/views/resorts/people/employee/list1.blade.php` (863 lines, 6 selects).

Grepped the whole app for `employee.list1` / `employee/list1` — zero references. `resources/views/resorts/people/employee/list.blade.php`
(631 lines) is the live counterpart. Same recurring "abandoned numbered-suffix sibling" pattern
as findings #18/#20/#21 — skipped, not converted.

---

## 24. `people/employee/detail1.blade.php` — entire file is dead, no controller returns it

**File:** `resources/views/resorts/people/employee/detail1.blade.php` (2693 lines, 27 selects).

Grepped the whole app for `employee.detail1` / `employee/detail1` — zero references.
`resources/views/resorts/people/employee/detail.blade.php` (3951 lines) is the live counterpart.
Same recurring "abandoned numbered-suffix sibling" pattern as findings #18/#20/#21/#23 — skipped,
not converted.

---

## 25. `people/employee/detail.blade.php`'s Leave Destination field — remote AJAX-search select2, out of scope for the `.dd` component

**File:** `resources/views/resorts/people/employee/detail.blade.php`, the `select2-airport-search`
select (name `leave_destination`).

Not converted — this is a genuinely different UI paradigm from every other select in this
unification pass. Every other converted select renders its full option list up front and the
`.dd` component filters that list client-side; this one has almost no options rendered
server-side (just the employee's current value, if any) and instead calls
`route('resort.airports.search')` over AJAX on every keystroke (`minimumInputLength: 1`,
`delay: 200`) to populate results live — a remote-data-source pattern the `.dd` component has no
equivalent for today. Left on select2 (its own dedicated init block, separate from the global
`.select2t-none` one, so no shared code needed touching). Out of scope, same as the already-
established "genuine multi-select" exclusion — not a bug, just a different component need.

---

## 26. `people/employee/detail.blade.php`'s "Add Allowance" row index always starts at 0 — wrong property name

**File:** `resources/views/resorts/people/employee/detail.blade.php`, the `#add-allowance` click
handler's `allowanceIndex` initialization.

```php
let allowanceIndex = {{ isset($employee->allowances) ? count($employee->allowances) : 0 }};
```

`Employee` only has a relation named `allowance` (singular) — confirmed in `app/Models/Employee.php`.
`$employee->allowances` (plural) doesn't exist, so `isset()` is always false and `allowanceIndex`
always starts at `0`, regardless of how many allowance rows were server-rendered above it in the
same edit-mode block (which correctly loops `$employee->allowance as $index => $allowance`, using
the real per-row `$index`). Clicking "Add Allowance" on an employee who already has existing
allowance rows creates a new row whose `name="allowances[0][...]"` collides with the *first*
existing row's index — the same key ends up submitted twice, and the server will keep whichever
one PHP's array-merge behavior lands on last, silently dropping the other.

Not fixed here — this pass is presentation-only. For the dropdown conversion itself, the new
row's `.dd` elements use a *separately* seeded, correctly-computed counter
(`allowanceDdRowIndex`, seeded from `count($employee->allowance)`, the real singular relation) so
the conversion doesn't compound this with an id collision of its own — but the `name` attribute's
index, and the underlying data-collision bug it causes, is unchanged.

**Fix:** change `$employee->allowances` to `$employee->allowance` on the `allowanceIndex` line.
