# SOS Module — Mobile API Fixes & Feature Gaps

For the backend developer implementing with Claude Code. This is a fix/build
list for the **mobile API** side of the SOS module
(`app/Http/Controllers/API/SOSController.php`,
`app/Http/Middleware/EnsureSOSSecurityManagerAccess.php`,
`EnsureSOSSecurityStaffAccess.php`, plus the SOS models). Its companion,
`docs/sos-fixes-web-portal.md`, covers the web portal side — several items
below need a matching change there; each says so. `docs/mobile-sos-api.md`
is the existing, still-accurate API *contract* reference (every endpoint,
payload shape, role rule) — this doc doesn't repeat that, only what needs to
change.

Confirmed with the product owner: **Drill and Real SOS behave identically
everywhere except reporting** — a drill exists purely so the system can
count "how many drills were run" separately from real incidents. Nothing
below should special-case Drill-Active differently from Real-Active/Active
in the *operational* code paths (dispatch, acknowledge, complete, location
tracking) — only in report aggregation.

---

## P0 — Root cause: `sos_history.status` enum drift, mobile-facing instances

Full history of the enum drift is in `docs/sos-fixes-web-portal.md` §P0 —
read that first. The short version: the current real enum is `Completed,
Active, Pending, Rejected, Real-Active, In-Progress, Drill-Active,
Drill-Rejected, Drill-Completed`, and several hardcoded status lists in this
file were written against older/incomplete versions of it.

**Fix once, centrally** — add to `app/Helpers/Common.php`:

```php
public static function sosOpenStatuses(): array
{
    return ['Active', 'Drill-Active', 'Real-Active', 'In-Progress'];
}

public static function sosClosedStatuses(): array
{
    return ['Completed', 'Rejected', 'Drill-Completed', 'Drill-Rejected'];
}
```

Then update every one of these in `API/SOSController.php` to use it instead
of a local list:

| Method | Line(s) | Current | Notes |
|---|---|---|---|
| `managerDashboard()` | ~1382 | `whereIn('status', ['Active', 'Real-Active', 'In-Progress'])` | Missing `Drill-Active`. **Confirm with product first** — this feeds the headline "active SOS count," and it's a legitimate open question whether a drill should inflate that number. If yes, swap to `Common::sosOpenStatuses()`; if no, leave it as an intentionally-scoped list but rename it to make that explicit (e.g. `nonDrillOpenStatuses()`) so the next person doesn't "fix" it into matching everywhere else. |
| `handleSOSActionWithTeam()` | ~230 | `whereIn('status', ['Pending','Drill-Active','Real-Active'])` | This one is a *pre-approval* gate (combined with `where('sos_approved_by', null)`), not a general "is it open" check — don't blindly swap to the shared helper here without checking the actual intended semantics (should an SOS already sitting at `Drill-Active`/`Real-Active` with no approver ever legitimately reach this branch? Trace it against `drillRealSOS()`'s flow, described below, before changing). |
| `employeeAndTeamLocation()`, `SOSDetails()`, `completeSOSUpdateStatus()` | 579, 678, 1213 | Already correctly use `['Active', 'Drill-Active', 'Real-Active', 'In-Progress']` (fixed in a prior pass, per their own inline comments) | Just swap these three to call `Common::sosOpenStatuses()` instead of the literal array, so there's one source of truth going forward. |

---

## P1 — No broadcast event when an SOS is triggered or dispatched

This is the API-side half of the web dashboard's "no real-time alert"
problem (full context in `docs/sos-fixes-web-portal.md` §P1). Confirmed: no
`SOS`-related broadcast event exists anywhere in `app/Events/`.

Add a new event, e.g. `App\Events\SosTriggered implements ShouldBroadcast`,
and dispatch it from two places:

1. **`SOSStore()`** — right after `$SOSHistoryAdd` is created (a brand-new
   Pending SOS). This is the moment the web dashboard most needs to know
   about — someone just hit the panic button.
2. **`handleSOSActionWithTeam()`** — after the status update, so the
   dashboard can reflect Approved/Rejected/Dispatched transitions too, not
   just the initial trigger.

Broadcast on a resort-scoped private channel, e.g.
`private-resort.{resort_id}.sos`, with a payload the web dashboard can
render directly without a second round-trip: `sos_id`, `status`,
`emergency_name`, `location`, `initiator_name`, `initiator_photo`. Follow
the existing private-channel + broadcast-auth pattern already used
elsewhere in the API (check how the chat module's `MessageSent`/
`NewConversationMessage` events are structured — same shape applies here).

---

## P1 — Broken de-dupe in the incident timeline

`SOSHistoryDetails($sosId)`, around line 951:

```php
$displayedStatuses = ['data' => []];
// ...
foreach($sosHistory as $m) {
    // ...
    if(!in_array($m->sos_status, $displayedStatuses)) {
        $displayedStatuses['data'][] = [...];
    }
}
```

`$displayedStatuses` is `['data' => []]`, an associative array with one key
— `in_array($m->sos_status, $displayedStatuses)` checks whether the status
string exists as a *value* in that one-element array, which it never will.
The condition is always true, so nothing is actually deduplicated.

Real impact: `acknowledgements_received_from_team_members` gets written
once *per team member* who acknowledges (`SOSAcknowledge()` inserts a new
`ChildSOSHistoryStatus` row every time, no dedupe on the write side either).
Any incident with 2+ acknowledging members will show that timeline step
duplicated once per member on the "SOS Details" timeline this method
returns.

**Fix:** track a flat list of statuses already added, not the wrapper
array:

```php
$seen = [];
$displayedStatuses = ['data' => []];
foreach ($sosHistory as $m) {
    if (in_array($m->sos_status, $seen, true)) continue;
    $seen[] = $m->sos_status;
    $displayedStatuses['data'][] = [
        'sos_status' => $m->sos_status,
        'date' => $dateTime->format('Y-m-d'),
        'time' => $dateTime->format('H:i:s'),
    ];
}
```

(Decide with product whether "first occurrence" or "most recent occurrence"
of a repeated status is what should show in the timeline — the fix above
keeps the first one, matching current iteration order.)

---

## P1 — Mass Instructions: no read endpoint at all, and no history to read

Confirmed: `grep -n "mass_instruction" app/Http/Controllers/API/SOSController.php`
returns **zero matches**. The web portal writes `sos_history.mass_instructions`
(a single overwritable string column — see `docs/sos-fixes-web-portal.md`
§P2 for the schema fix needed there) and pushes a notification at send time,
but nothing in the mobile API ever exposes that field back to the app. The
only way an employee currently sees an instruction is the fleeting push
notification banner — there's no way to view the current (or past)
instruction on-demand from an "SOS active" screen.

Once the web-side schema fix lands (new `sos_mass_instructions` table
instead of the single column — see the companion doc), add:

```
GET sos/mass-instructions/{sos_id}
```

Returns the ordered history (newest first) for that incident: `message`,
`sent_by` (name), `created_at`. Gate it the same as the other "any
authenticated employee" endpoints — anyone with visibility into the
incident should be able to see what instructions were sent, not just the
one who happened to have the app open at send time.

---

## P1 — Live location payload is missing a phone number

Per the product ask: the Live Location map's info window should let a
manager contact someone directly, which needs a phone number in the
response. Two endpoints currently omit it:

- `employeeAndTeamLocation($sosId)` — the general live-map endpoint (mobile-facing).
- The web's `filterMapEmployeeList()` (in `DashboardController.php`, web side — see companion doc) builds its own separate `$locations` array and also omits it.

Add whatever field this app already uses for an employee's phone number
(check `Employee`/`ResortAdmin` for the existing column — likely
`contact_number` or similar, follow whatever's already used in other mobile
endpoints like the Employee Directory) to both the `select()` in
`employeeAndTeamLocation()` and the `filterMapEmployeeList()` map on the web
side, so both consumers get it from one change per file.

---

## P2 — Emergency Types' "default team" never reaches the manager's dispatch/detail screens

Full context in `docs/sos-fixes-web-portal.md` §P3. Short version:
`sos_child_emergency_types` (emergency type → default team(s), configured
on the web Configuration page) is read by exactly one mobile endpoint —
`getAnySOSEmergency()`, the *initiating employee's* own status check — and
by nothing the Security Manager sees. Neither `handleSOSActionWithTeam()`
(the actual dispatch action) nor `SOSDetails()` (the manager's incident
detail view) ever queries it.

Recommend adding the configured default team(s) to `SOSDetails()`'s
response (join `SOSChildEmergencyType`/`SOSTeamManagementModel` on the
incident's `emergency_id`, same pattern already used in
`getAnySOSEmergency()`) so the mobile dispatch screen can show/pre-select
them instead of requiring the manager to remember what was configured.

---

## P2 — Dead/misleading model relation

`SOSTeamMemeberModel::employee()` (`belongsTo(Employee::class, 'emp_id')`)
is stale — the `emp_id` FK on `sos_team_members` was deliberately repointed
from `employees.id` to `resort_admins.id` in
`2025_05_09_...update_emp_id_foreign_on_sos_team_members_table.php`, and
every real join in this controller already correctly treats it as
`resort_admins.id`. This relation was never cleaned up after that migration
and would silently return wrong/empty data if used. Confirmed unused
repo-wide via grep — safe to delete.

---

## P3 — Things I can't verify from the backend alone, confirm with the mobile app team

- **Does the mobile app actually have a Safe/Unsafe button?** The backend
  endpoint (`POST sos/sos-safe-status`) exists, is correctly implemented,
  and is documented in `docs/mobile-sos-api.md`. Whether the on-device UI
  calls it is outside this repo's visibility — confirm before assuming
  it's a backend gap.
- **Does the mobile app enforce `permission_names`** (returned by
  `getAnySOSEmergency()`, derived from `sos_role_management`) to gate what
  actions it shows a given team member? Backend computes and returns this
  correctly; enforcement is client-side.
- **Acknowledgement not registering** (see the Team Activity section of
  `docs/sos-fixes-web-portal.md`) — the leading theory is a `team_member_id`
  mismatch between what the app caches and the current
  `sos_team_member_activity.id` on the server, causing `SOSAcknowledge()`
  to silently return `success:false` without writing anything. Recommend
  adding a log line on that not-found branch (there currently isn't one) so
  a live test run produces evidence either way, then compare the app's
  request payload against `GET sos/get-team-acknowledged/{sos_id}` for the
  same incident.

---

## Reference

Full endpoint list, payload shapes, and role rules: `docs/mobile-sos-api.md`
(unchanged by this doc — still accurate). That file's own "Known gaps"
section already flags two things worth re-reading alongside this list: no
persisted GPS trail (current-position-only, no movement replay), and
`sos-history-listing`/`sos-history-details` being resort-wide rather than
scoped to the calling employee (both flagged there as intentional-per-existing-code,
not introduced by this pass — re-confirm with product if either needs
narrowing before more work builds on top of them).
