# SOS Module — Web Portal Fixes & Feature Gaps

For the backend developer implementing with Claude Code. This is a fix/build
list for the **web portal** side of the SOS module
(`app/Http/Controllers/Resorts/SOS/ConfigurationController.php`,
`DashboardController.php`, `app/Http/Controllers/Resorts/SosReportController.php`,
and `resources/views/resorts/SOS/**`). Its companion,
`docs/sos-fixes-mobile-api.md`, covers the mobile API side — several items
below need a change on both sides; each says so explicitly. The existing
`docs/mobile-sos-api.md` is the API *contract* reference (endpoints,
payloads, role rules) — read it alongside this if you touch anything that
crosses the web/mobile boundary.

Everything here was found by reading the actual code end to end (not
guessed) and, where noted, reproduced read-only against real data. Confirmed
with the product owner: **Drill and Real SOS are meant to behave identically
everywhere except reporting** — a drill exists purely so the system can
count "how many drills were run" separately from real incidents. Any fix
below that touches the status list must keep Active/Drill-Active/Real-Active/
In-Progress grouped together for every "is this incident still open"
check, and only split them apart in the *reporting* layer.

---

## P0 — Root cause: the `sos_history.status` enum drifted and the code never fully caught up

`sos_history.status` was changed across three migrations:

1. `2025_05_02_...create_sos_history_table.php` — `Completed, Active, Drill-active, Pending, Rejected`
2. `2025_05_28_...add_fields_rejected_message.php` — renamed `Drill-active` → `Drill-Active`, added `Real-Active, Under-Control, In-Progress, Drill-Rejected, Drill-Completed, Drill-Under-Control`
3. `2025_06_17_...add_fields_mass_instructions.php` — dropped `Under-Control` and `Drill-Under-Control` again

**Current real enum:** `Completed, Active, Pending, Rejected, Real-Active, In-Progress, Drill-Active, Drill-Rejected, Drill-Completed`

Every place in the code that hardcodes "what counts as active/open" was
written against an earlier version of this list and never updated. Fix this
**once**, centrally, then point every call site at it — don't patch each
site with another local list.

**Recommended fix:** add two helpers to `app/Helpers/Common.php` (or a small
`SosStatus` class if you prefer that pattern elsewhere in the codebase):

```php
public static function sosOpenStatuses(): array
{
    // "still in flight" — not yet resolved either way. Drill and Real
    // are deliberately grouped together here; only the reporting layer
    // (SosReportController) needs to tell them apart.
    return ['Active', 'Drill-Active', 'Real-Active', 'In-Progress'];
}

public static function sosClosedStatuses(): array
{
    return ['Completed', 'Rejected', 'Drill-Completed', 'Drill-Rejected'];
}
```

Then replace every one of these with a call to the helper:

| File | Line(s) | Current (broken/stale) | Fix |
|---|---|---|---|
| `DashboardController::index()` | ~114-126 | `switch($row->status)` cases only `Completed`/`Drill-active` (old casing)/`Active`/`Rejected`, everything else falls to `default` → shows "Pending" badge | Add cases (or a lookup map) for every current enum value with the right badge class; fix `Drill-active` → `Drill-Active` |
| `dashboard/index.blade.php` | ~55-60 | Status filter `<select>` offers `Completed, Pending, Drilled, Rejected` — `"Drilled"` isn't a real value, matches zero rows | Rebuild the option list from the real enum (ideally render it server-side from the same helper so it can't drift again) |
| `DashboardController::index()` | 156 | `$hasPendingSOS = SOSHistoryModel::where('status','Pending')->exists();` — **no `resort_id` filter** | Add `->where('resort_id', $this->resort->resort_id)` — this is a live cross-tenant leak, fix regardless of the enum work |
| `SosReportController::ACTIVE_STATUSES` | 30 | `['Active', 'Pending', 'Drill-active']` — stale casing, missing `Real-Active`/`In-Progress` | Replace with `Common::sosOpenStatuses()` (drop `Pending` from this one specifically — a still-unapproved incident isn't "active" for reporting purposes; confirm with product if that's actually what's wanted) |
| `SosReportController::index()` | ~71 | Status filter dropdown same stale 5-value list | Same fix as the dashboard filter |

Also verify `app/Http/Controllers/API/SOSController.php::managerDashboard()`
(`active_sos_count`, line ~1382) — it's missing `Drill-Active` from its
`whereIn`. Per the drill/real clarification above, decide with product
whether a drill should count toward the manager dashboard's headline
"active" number (arguably no — that's a real-emergency count) before
"fixing" it either way; don't just add it back without that call. Covered
again in `docs/sos-fixes-mobile-api.md` since it's an API file — do both in
the same change.

---

## P1 — No real-time indication on the web dashboard when an SOS is triggered

Confirmed structurally: there is **no broadcast infrastructure for SOS at
all** (`grep -r SOS app/Events/` returns nothing) and the dashboard view
(`resources/views/resorts/SOS/dashboard/index.blade.php`) has no
Echo/Pusher listener and no polling `setInterval` — the DataTable only
reloads after the current user's own archive/delete action. A Security
Manager staring at the dashboard has no way to know a new SOS came in
except manually refreshing the page.

This is genuinely new work, not a one-line fix. Recommended shape (mirrors
patterns already used elsewhere in the app — see `wisdom-chat.blade.php`'s
Echo usage this session for the established pattern of "private channel +
CustomEvent → UI update"):

1. **New broadcast event**, e.g. `App\Events\SosTriggered` (`ShouldBroadcast`), fired from the mobile API's `SOSStore()` (new SOS raised) and again from `handleSOSActionWithTeam()` (approved/rejected/dispatched) — see `docs/sos-fixes-mobile-api.md` for the API-side half of this.
2. Broadcast on a resort-scoped private channel (e.g. `private-resort.{resort_id}.sos`), payload: `sos_id`, `status`, `emergency_name`, `location`, `initiator_name`.
3. On the web dashboard, subscribe via the app's existing Pusher/Echo setup (`partials.pusher-init`, already used by the chat widget) and on receipt: (a) reload the DataTable, (b) show a persistent, hard-to-miss banner/toast (not a dismissible-and-forgotten toastr — an active SOS should stay visible until acknowledged or the incident resolves), (c) consider a sound, matching how urgent the underlying event is.
4. Scope this to whichever pages should show it — at minimum the SOS Dashboard itself; ask the product owner whether it should also appear as a persistent header-level indicator across the whole portal (matching "whoever is on the dashboard also gets a similar seriousness of SOS" — that phrasing suggests global, not just the SOS page).

---

## P1 — View Team Activity Details (`sos.viewTeamActivityDetails` → `view-team-activity-detail.blade.php`)

Reported as broken: acknowledgements don't show, timestamps read "6 hours
before" incorrectly, the map icon does nothing, the acknowledge filter
doesn't work, the team filter only ever shows one team.

What I could verify by reading the code (I can't reproduce live mobile
acknowledgement traffic from here, so some of this needs a live test pass
together with the mobile team):

- **The "map icon" doesn't exist in this view at all.** I read
  `resources/views/resorts/renderfiles/SosTeamMembersActivityList.blade.php`
  (the partial that actually renders the list) top to bottom — there is no
  map icon, no per-row "view on map" affordance, anywhere in the markup.
  This isn't a broken feature, it's an **unbuilt** one. Decide with product
  whether this is a small modal (embed the same Google Map used on the Live
  Location screen, centered on that one member's `latitude`/`longitude`
  from `sos_team_member_activity`) or should just link out to the existing
  Live Location screen pre-filtered to that person.
- **Acknowledged/Not-Acknowledged rendering itself looks correct in code**
  (`SosTeamMembersActivityList.blade.php:28-37` branches cleanly on
  `$teamMember->status`). If it's not showing acknowledgements in practice,
  the likely failure point is upstream: `API/SOSController::SOSAcknowledge()`
  only flips a row to `Acknowledged` when it finds an EXACT match on
  `sos_history_id` + `emp_id` ($this->user->id, a resort_admins.id) +
  `id` (**the specific `sos_team_member_activity.id`**, sent by the app as
  `team_member_id`) + `status = 'Unacknowledged'`. If the mobile app is
  caching/reusing a stale `team_member_id` from an earlier fetch, or the
  team roster shown on-device doesn't match the current DB row IDs, the
  acknowledge call returns `{"success": false, "message": "SOS Already
  Acknowledged"}` and **silently no-ops** — nothing gets written, and the
  web dashboard is accurately showing "still Unacknowledged" because that's
  the truth in the DB. Recommend: log a warning in `SOSAcknowledge()` on the
  not-found branch (currently just returns the message, nothing logged) so
  you can see from server logs whether this is actually happening, then
  confirm with the mobile team what `team_member_id` they're sending vs.
  what `GET sos/get-team-acknowledged/{sos_id}` returns for the same
  incident.
- **The "6 hours before" timestamp** comes from
  `\Carbon\Carbon::flexible($teamMember->updated_at)->diffForHumans()` —
  `updated_at` on `sos_team_member_activity`. For a member who hasn't
  acknowledged yet, `updated_at` is still the dispatch-time value (the row
  was bulk-inserted with an explicit timestamp when the team was dispatched
  — see `handleSOSActionWithTeam()`), so "X hours ago" for an unacknowledged
  member is actually correctly answering "how long ago was this person
  dispatched," which will legitimately grow to look like "6 hours before"
  the longer an incident stays open unacknowledged. That's arguably correct
  behavior being misread as a bug — but combine it with the point above: if
  acknowledgement itself isn't landing, the timestamp for someone who
  *thinks* they acknowledged will also never move, compounding the
  confusion. Fix the acknowledge-write issue first, then re-check whether
  the timestamp still looks wrong.
- **Acknowledge filter** — `filterTeamActivityDetails()` reads
  `$request->show_status` and compares `== "true"`; the view's JS
  (`view-team-activity-detail.blade.php:149-161`) sends
  `show_status: $('#unAckFilter').is(':checked')`, which jQuery serializes
  as the string `"true"`/`"false"` — this pairing looks correct on paper.
  If it's not filtering in practice, check the browser network tab for the
  actual POST body during a live test; I don't have a way to reproduce the
  client-side checkbox interaction from here.
- **Team filter only shows one team ("Fire Team")** — `viewTeamActivityDetails()`
  populates the filter from `ChildSosHistory::where('sos_history_id', $id)->get()`,
  i.e. only the team(s) actually dispatched **to that specific incident**,
  not every team the resort has configured. If only one team was dispatched
  to the SOS you tested with, seeing only that one team in the filter is
  correct, expected behavior, not a bug. Re-test with an incident that had
  multiple teams dispatched (`handleSOSActionWithTeam`'s `team_id[]` accepts
  an array) before treating this as broken.

---

## P1 — View Employee Safety Status (`sos.viewEmployeeSafetyDetails` → `ViewEmployeeSafetyStatus.blade.php`)

Reported as broken: no way to see anyone's location.

**Confirmed, real gap.** The list partial
(`resources/views/resorts/renderfiles/SosEmployeesStatusList.blade.php:26`)
only ever renders `{{ $employeeStatus->address }}` — a free-text address
string. That field is **never populated** at the point an SOS goes live:
`handleSOSActionWithTeam()` bulk-seeds every active employee's
`sos_history_employee_status` row with `latitude`/`longitude` copied from
their last-known position, but leaves `address` null. It only gets filled
in if/when the employee's own device calls `sos/location-update` with an
explicit address string — which most employees, especially ones who never
open the app during the incident, never will. So for most rows this column
renders as an empty list item: no visible location at all, exactly as
reported.

Fix (pick one, or both):
1. **Cheapest:** reverse-geocode `latitude`/`longitude` server-side (or
   client-side via the Google Maps JS you're already loading on the Live
   Location page) whenever `address` is empty, so there's always a
   human-readable fallback.
2. **More useful:** add a small "view on map" pin icon per row (same
   pattern as the map-icon gap noted for Team Activity above) that opens a
   focused map centered on that employee's stored lat/lng — this is more in
   line with what "we need to see where anyone is" actually wants, since a
   text address is a weaker signal than a pin during a live emergency.

Also worth fixing while in this file: `$onlyUnsafeEmpCount` (line ~324 in
`DashboardController::viewEmployeeSafetyDetails()`) is a misleadingly-named
variable — it queries `status = 'Unknown'`, not `status = 'Unsafe'`. The
Blade label is correctly "Unknown Status" so this isn't user-facing, but
rename the variable to match what it actually counts, and — more
importantly — **add a genuine "Marked Unsafe" tile**, querying
`status = 'Unsafe'` for real. Right now nobody who explicitly reports being
unsafe gets a dedicated count anywhere on this screen; they're invisible
inside "everyone who isn't Safe." That's the single most operationally
important number on this page and it currently doesn't exist.

---

## P1 — View Employees Live Location (`sos.showMap` → `employeeLiveLocation.blade.php`)

Reported as broken: colors aren't right (need a third neutral color, not
just red/green), hovering doesn't show info, no phone number shown, right
side list also just shows "unknown" for everyone.

All of these are real, confirmed in the actual JS/Blade — this page is the
most fully-built of the four action-button screens (real Google Map, live
polling every 10s, colored markers, click-to-open info windows), which
makes the specific bugs easy to pin down precisely:

1. **Status is only ever 2-way (Safe vs everything-else), needs to be 3-way
   (Safe / Unsafe / Unknown).** Confirmed in three separate places, all with
   the identical bug shape:
   - `employeeLiveLocation.blade.php:199` — marker icon:
     `(status === 'Unsafe' || status === 'Unknown') ? red-dot : green-dot`
   - `employeeLiveLocation.blade.php:210` — info-window text color, same ternary
   - `resources/views/resorts/renderfiles/EmployeeListLiveLocationView.blade.php:20-28` — right-side list badge: `@if(status == 'Safe') green @else red @endif`

   Fix all three together with one 3-way rule: `Safe` → green,
   `Unsafe` → red, `Unknown` → a distinct neutral color (grey/amber — your
   call, just make it visually distinct from both red and green so "hasn't
   responded" doesn't read as "in danger"). Do this as one pass across all
   three files so they can't drift apart from each other again.

2. **Marker color never updates after first creation.** `updateMapMarkers()`
   (`employeeLiveLocation.blade.php:179-236`): for a marker that already
   exists, the code only does `markers[user.id].setPosition(pos)` — it never
   re-sets `.setIcon(...)` on subsequent polls. Since the page polls every
   10 seconds, if someone's status changes from Unknown to Safe (or to
   Unsafe) *while the map is open*, the dot's position will keep moving but
   its color will be stuck at whatever it was when the marker was first
   drawn. Fix: also call `.setIcon(...)` on the position-update branch, not
   just at marker creation.

3. **Info window opens on click, not hover.** `marker.addListener("click", ...)`
   at line 228. The description of the desired behavior ("find my
   friends"-style, mouse-over reveals name/photo/etc.) implies hover. Click
   is arguably the more standard, more robust pattern for a map (works
   consistently regardless of input device) — flag this to product as a
   design choice to confirm rather than assume it needs to change; if hover
   is genuinely wanted, swap the listener to `mouseover`/`mouseout` and add
   a small delay so the window doesn't flicker on cursor pass-through.

4. **No phone number in the info window or anywhere else on this screen.**
   Wanted per the report ("even mobile number, if it is registered"). Two
   changes needed together:
   - **Web:** add a phone-number field to the info-window template
     (`employeeLiveLocation.blade.php:202-215`).
   - **Web + API:** `DashboardController::filterMapEmployeeList()` builds
     the `$locations` array (line ~476-493) and doesn't select a phone
     number at all — add it there (check what field the app already uses
     for employee phone, likely on `ResortAdmin` or `Employee`, and pass it
     through `$locations[]['phone']`).

5. **Right-side list panel doesn't refresh on the 10-second auto-poll.**
   `loadLiveData()` (line 238) only calls `updateMapMarkers(response.locations)`
   — the `#employeStatusSection` list is only refreshed when the filter form
   is submitted or reset. So the map dots move/update live but the adjacent
   list can sit stale. Recommend also refreshing the list HTML on each poll
   tick (the endpoint already returns `html` in its response, just isn't
   being used during the interval refresh — only on form submit/reset).

6. **Missing null-safety, will crash for some employees.**
   `EmployeeListLiveLocationView.blade.php:17` —
   `{{ $employeeStatus->employee->position->short_title }} • {{ $employeeStatus->employee->department->name }}`
   has no `optional()`/null-coalescing, unlike the sibling partial
   (`SosEmployeesStatusList.blade.php`) which correctly wraps the same kind
   of lookup. Any employee with no `position` or `department` assigned will
   throw "Attempt to read property on null" and break rendering of the
   entire right-side list, not just that one row. Wrap both the same way
   the other partial does.

7. **Google Maps API key is hardcoded in the Blade source**
   (`employeeLiveLocation.blade.php:143`:
   `...maps/api/js?key=AIzaSyBZjz2AtrseoGKhTyZfTeZoUVvD9aFSS6Q`). This is a
   security/ops hygiene issue independent of the functional bugs above —
   move it to `.env`/`config()` like other third-party keys in this
   codebase, and check whether that key needs rotating given it's currently
   sitting in the repo (and git history) in plain text.

---

## P2 — Send Mass Instructions has no history

Confirmed structurally: `sos_history.mass_instructions` is a **single
string column**, not a related table — every time
`DashboardController::updateMassInstruction()` runs, it overwrites the
previous instruction. There is no way, anywhere in the schema, to see what
was sent before the most recent message. The push notification does go out
correctly at send time (`Common::sendPushNotificationForMobile(...)`, wired
correctly), but nothing persists the message beyond that single push.

Fix requires a schema change:

1. New table, e.g. `sos_mass_instructions` (`id`, `resort_id`,
   `sos_history_id`, `message`, `created_by`, `created_at`).
2. `updateMassInstruction()` inserts a new row instead of updating the
   `sos_history.mass_instructions` column (you can leave the existing
   column alone for backward compat, or migrate it into the new table as
   the first row — your call, but stop writing to it going forward either
   way).
3. Add a small "instruction history" panel to
   `ViewEmployeeSafetyStatus.blade.php` (where the Send Mass Instructions
   form already lives) listing past messages, newest first, with the
   sender's name and timestamp.
4. **Mobile side needed too** (see `docs/sos-fixes-mobile-api.md`): there is
   currently no GET endpoint that exposes mass instructions to the mobile
   app at all — the only way an employee sees one right now is the fleeting
   push notification banner. If the "SOS active" screen you referenced is
   meant to show the current/latest instruction (and now, history), the
   mobile API needs a new endpoint reading from this table.

---

## P2 — Confirmed bugs from the general module audit (web side)

- **Validation rule silently dropped** —
  `ConfigurationController::SOSRolesAndPermissionStore()` defines
  `'sos.*.assign_permission.*'` twice in the same rules array
  (`required|array|min:1` then `string`); the second entry overwrites the
  first in PHP's associative array, so the required/array/min:1 constraint
  never actually applies. Merge into one rule:
  `'required|array|min:1'` (drop the redundant `string` sub-rule, or express
  it as `'sos.*.assign_permission.*.string' => 'string'` if you need
  per-element type checking too).
- **Malformed HTML attribute (currently dead, but a landmine)** —
  `ConfigurationController::IndexSOSTeamManagement()`, Action column:
  `data-teamMembers="'.$row->members.'"` string-concatenates an Eloquent
  Collection straight into an unescaped double-quoted attribute. Laravel
  Collections stringify to raw JSON, which contains embedded `"` — breaks
  the attribute at the first one. Confirmed via a read-only reproduction
  that nothing currently reads this attribute (the edit-team modal fetches
  fresh data via its own AJAX call instead), so there's no live bug today —
  but it's genuinely malformed markup shipped to every page load and a trap
  for whoever reaches for it next. Fix the same way the very similar case a
  few hundred lines away already does it correctly —
  `IndexSOSEmergencyTypes()`'s `data-assigned_default_team` attribute uses
  `htmlspecialchars(json_encode(...), ENT_QUOTES, 'UTF-8')`. Copy that
  pattern here, or just delete the attribute since nothing consumes it.
- **Dead/misleading model relation** — `SOSTeamMemeberModel::employee()`
  (`belongsTo(Employee::class, 'emp_id')`) is stale. The `emp_id` FK on
  both `sos_team_members` and `sos_team_member_activity` was deliberately
  repointed from `employees.id` to `resort_admins.id` in two later
  migrations (`2025_05_09_...update_emp_id_foreign_on_sos_team_members_table.php`,
  `2025_05_13_...update_emp_id_foreign_on_sos_team_member_activity_table.php`),
  and every real code path (web team-store, every mobile API join) already
  correctly treats it as `resort_admins.id`. This one relation was never
  cleaned up and would silently return wrong/empty data if anyone reached
  for `->employee` on a team-member row instead of the correct
  `->resortAdmin`/`->teamMember`. Confirmed unused today via a repo-wide
  grep — safe to delete outright.
- **Cascading delete wipes incident history** — `sos_teams` →
  `child_sos_history` and `sos_teams` → `sos_child_emergency_types` are both
  `onDelete('cascade')`. Deleting a team from Configuration doesn't just
  remove the config row, it silently erases the historical record of which
  teams responded to *past* incidents and severs old emergency-type→team
  assignments. For a module whose other half is an incident/compliance
  report (`SosReportController`), that's very likely the wrong default —
  recommend changing both to `restrict` (block deletion of a team that has
  incident history — force the admin to reassign/archive instead) or, if
  teams need to stay freely deletable, denormalize the team **name** onto
  `child_sos_history` at insert time so the historical record survives even
  after the config row is gone. Confirm which approach product wants before
  implementing — this is a real behavior change, not a pure bug fix.

---

## P3 — Configuration ↔ Mobile: does Team Management / Emergency Types / Roles & Permissions actually reach the mobile app correctly?

Emergency Contact Numbers (`EmergencyContactsStore`) is confirmed
straightforward and working: it writes to `resort_site_settings`, and
`API\SOSController::getEmergencyContacts()` reads the same three fields
back. No issue there.

The other three are more nuanced — they're technically "connected" to
mobile, but with one real gap:

- **Team Management** — teams and members are correctly visible to mobile
  via `sos/sos-team-listing` (names only) and `sos/fire-team-members`
  (names + members + roles). Working as designed.
- **Roles & Permissions** — `sos_role_management` + `sos_team_members.role_id`
  correctly flow through to `API\SOSController::getAnySOSEmergency()`,
  which computes `role_assigned` and `permission_names` for the calling
  employee on the current incident. The backend side of this is correctly
  wired. **What I can't verify from this repo:** whether the mobile app
  actually reads and enforces `permission_names` to gate what UI it shows —
  that's mobile-app code, outside this repo's visibility. Confirm with the
  mobile team that this is being consumed, not just returned and ignored.
- **Emergency Types' "default team" assignment doesn't reach the Security
  Manager's dispatch flow.** `sos_child_emergency_types` (emergency type →
  default team(s), configured on the Configuration page) is read by exactly
  one mobile endpoint: `getAnySOSEmergency()` — which is the *initiating
  employee's* "is there an active emergency I started" check, not anything
  the Security Manager sees. Neither `handleSOSActionWithTeam()` (the
  dispatch action itself) nor `SOSDetails()` (the manager's incident-detail
  view) ever queries `SOSChildEmergencyType` for the SOS's `emergency_id`.
  So today, a manager approving an incident gets **no server-suggested
  default team** — they have to already know/remember what was configured
  on the Configuration page, or the app has to hardcode/guess it
  client-side. Recommend adding the configured default team(s) to
  `SOSDetails()`'s response (and/or a dedicated
  `GET sos/emergency-type-teams/{emergency_id}` endpoint) so the mobile
  dispatch screen can pre-select/suggest them instead of requiring the
  manager to remember. This is the one genuine web-config→mobile
  disconnect in this trio; flagged in `docs/sos-fixes-mobile-api.md` too
  since it needs an API change.

---

## P3 — Minor cleanup

- `use Google\Service\CloudControlsPartnerService\Console;` sits unused (an
  accidental IDE auto-import, not invoked anywhere) in both
  `ConfigurationController.php` and `DashboardController.php`. Harmless,
  just delete both.
- The large commented-out blocks in `view-team-activity-detail.blade.php`
  (lines ~89-134) and `employeeLiveLocation.blade.php` (lines ~272-311) are
  earlier, superseded implementations of the same UI left in place instead
  of deleted. Safe to remove once the fixes above are in and confirmed
  working — no need to carry dead alternate implementations forward.

---

## Open questions for product before implementing

1. Should a Drill count toward the manager dashboard's "active SOS" number, or only real ones? (Affects the exact status list used in `managerDashboard()`.)
2. Real-time dashboard alert — should it be scoped to the SOS Dashboard page only, or a persistent indicator visible from anywhere in the portal while an SOS is open?
3. "View on map" for a single person (Team Activity, Employee Safety Status) — new modal reusing the existing map component, or a link out to the full Live Location screen pre-filtered to that person?
4. Neutral color for "Unknown" status — any preference, or backend/design's call?
5. Team deletion when it has incident history — block deletion, or preserve history by denormalizing the team name?
