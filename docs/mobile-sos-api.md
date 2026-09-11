# SOS Module API

For the mobile team. This is not a new module — the backend already has a
full, working API surface for SOS trigger → team dispatch → live tracking →
resolution. If the app screen reads as static, it's not wired to these
endpoints yet, not a missing backend.

Controller: `app/Http/Controllers/API/SOSController.php`. All routes below
sit under `auth:api` (Passport Bearer token — same header as every other
mobile call). `sos_id` in a URL path is **base64-encoded** everywhere except
where explicitly noted otherwise — encode the numeric id before building the
URL.

**Two real bugs fixed alongside this doc**: (1) approve/reject/dispatch/
complete actions had no role check at all — any employee could act on
someone else's SOS; now gated (see §Role separation). (2) the SOS-trigger
notification target lookup required `rank==4`, but no real Security Manager
record has rank 4 (the actual one is rank 2/HOD) — so **triggering an SOS
never actually notified anyone** in practice. Both fixed server-side, no
app change needed for either.

## Role separation (server-enforced)

| Role | How it's determined | Can do |
|---|---|---|
| **Any employee** | logged in, no further check | Trigger SOS, mark self safe/unsafe, send continuous location pings, view live map for an active SOS, view team roster, view own acknowledgement status, view incident chat, view SOS history |
| **Security Manager** | `employees.position` → `position_title == 'Security Manager'` (title-only match; no rank restriction) | Everything above, **plus**: approve/reject a triggered SOS, dispatch response team(s), mark drill-vs-real, mark an incident complete, view the manager dashboard (pending approvals + active count) |
| **Security department staff** | `Common::isSecurityDepartment($employee->Dept_id)` — includes the Security Manager | View the security-staff dashboard (active/pending incidents, read-only) |

Middleware: `app/Http/Middleware/EnsureSOSSecurityManagerAccess.php` (alias
`sos.manager`) and `EnsureSOSSecurityStaffAccess.php` (alias `sos.security`).
A resort master admin (`is_master_admin == 1`) bypasses both. Anything not
listed under Security Manager / Security staff above is open to any
authenticated employee — that's intentional (any employee needs to be able
to trigger an SOS, respond to one they're dispatched to, or check their own
history).

**Note for app dev**: if your build has a different concept of "who manages
SOS" than "the employee whose position title is literally 'Security
Manager'" (e.g. a per-resort configurable role instead of a fixed title),
say so — this matches the one existing convention in the codebase
(`SOSStore()`'s original routing target), not a new design.

## REST endpoints

### General (any employee)

| Method | Endpoint | Purpose |
|---|---|---|
| GET | `sos/emergency-types` | List of emergency types configured for this resort (Fire, Medical, Security, etc.) — populate the trigger screen's picker |
| POST | `sos/sos-store` | **Trigger an SOS.** Body: `emergency_id`, `location`, `latitude`, `longitude`, `emergency_description` (optional). Creates the incident (`status: Pending`) and pushes/notifies the resort's Security Manager |
| GET | `sos/sos-team-listing` | All SOS response teams for this resort (name/description only — see `fire-team-members` for members) |
| GET | `sos/fire-team-members` | All response teams **with members** (name, photo, role) — the "who's on the fire team" screen |
| POST | `sos/sos-safe-status` | Mark yourself Safe/Unsafe during an active incident. Body: `sos_history_id`, `status` (`Safe`\|`Unsafe`), `address`, `latitude`, `longitude` |
| POST | `sos/location-update` | **Continuous location ping** — see §Live location tracking below |
| GET | `sos/employee-team-location/{sos_id}` | Live map: every employee's + dispatched team member's last-known location for an active incident |
| GET | `sos/sos-details/{sos_id}` | Your own team-member view of an incident (only returns data if you're on a dispatched team for it) |
| POST | `sos/sos-acknowledge` | A dispatched team member acknowledges being on their way. Body: `sos_history_id`, `team_member_id`, `status`, `address`, `latitude`, `longitude` |
| GET | `sos/get-team-acknowledged/{sos_id}` | Split list of acknowledged vs unacknowledged team members for an incident |
| GET | `sos/get-any-sos-emergency` | Today's open incident (if any) plus your own role/permissions on it, for the home-screen SOS banner |
| GET | `sos/sos-history-listing` | All resolved incidents (`Completed`\|`Rejected`\|`Drill-Completed`\|`Drill-Rejected`) for this resort |
| GET | `sos/sos-history-details/{sos_id}` | Full detail + status timeline for one incident (works for both active and resolved) |
| GET | `sos/chat-logs/{sos_id}` | Incident chat log (isolated per-SOS chat, not the general Chat module) |
| POST | `sos/send-chat-message` | Post a message to an incident's chat. Body: `sos_history_id`, `message` |

### Security Manager only (`sos.manager`)

| Method | Endpoint | Purpose |
|---|---|---|
| POST | `sos/handle-sos-action-with-team` | Approve or reject a Pending SOS, and (if approving) dispatch team(s). Body: `sos_id`, `action` (`Active`\|`Drill-Active`\|`Rejected`\|`Drill-Rejected`), `team_id[]` (required if approving), `team_message` (required if approving), `rejected_message` (required if rejecting), `employee_message` (optional, broadcast to all resort employees on approve) |
| POST | `sos/drill-real-sos` | Flip a Pending SOS to `Real-Active` or `Drill-Active` before the full team-dispatch flow. Body: `sos_id`, `action` |
| POST | `sos/complete-sos-update-status` | Mark an Active/Drill-Active/In-Progress incident `Completed`. Body: `sos_id`, `status` (must be `Completed`) |
| GET | `sos/manager-dashboard` | Pending-approval list + count, active-incident count |

### Security staff (`sos.security`)

| Method | Endpoint | Purpose |
|---|---|---|
| GET | `sos/security-staff-dashboard` | Read-only: all open incidents (Pending/Active/Real-Active/In-Progress) + count |

## Live location tracking (the "continuous tracking" ask)

There's no separate GPS-trail table — a live map only ever needs each
person's *current* position, not history, so pings overwrite the same row
instead of accumulating a log.

**Flow:**
1. Employee triggers or is dispatched to an SOS (`sos-store` / gets notified via `handle-sos-action-with-team`'s push).
2. App starts a location timer (recommend **every 15–30 seconds** while the incident stays open — no server-side rate limit, but there's no reason to go faster than the map redraws).
3. Each tick: `POST sos/location-update` with `sos_history_id`, `latitude`, `longitude`, `address` (optional).
   - Writes/overwrites `sos_history_employee_status` (your row for this incident) — this is what every employee's dot on the map reads from.
   - If you're also a dispatched team member on this incident, your `sos_team_member_activity` row gets the same update in the same call — one ping updates both map layers, no second endpoint to call.
   - Server rejects (`404`) if the incident is already `Completed`/`Rejected`/`Drill-Completed`/`Drill-Rejected` — **stop the timer** when you get that response, don't keep pinging a closed incident.
4. Anyone viewing the map calls `GET sos/employee-team-location/{sos_id}` — merges both position sources, returns one flat list with a `type` field (`employee` or `team_member`) per entry.

`sos/location-update` response:
```json
{
  "success": true,
  "message": "Location updated successfully.",
  "data": {
    "id": 1,
    "sos_history_id": 47,
    "emp_id": 205,
    "latitude": "4.1755",
    "longitude": "73.5093",
    "address": "Main Building",
    "status": "Unknown"
  }
}
```

**Stop the timer** on: incident resolution (`Completed`/`Rejected`/drill
variants), app backgrounding for an extended period (resume on foreground),
or the employee explicitly marking themselves Safe.

## Response formats

Every endpoint follows the shared `{"success": bool, "message": str, "data": ...}`
envelope. Validation failures return `{"success": false, "errors": {...}}`
at `400`; role/auth failures return `{"success": false, "message": "..."}`
at `401`/`403`; a missing/expired incident returns `{"success": false,
"message": "..."}` at `200` or `404` depending on the endpoint (inconsistent
in a few older methods — treat any `success: false` as "not found/not
allowed" regardless of HTTP status, don't rely on status code alone for
that distinction).

**`sos-store` response:**
```json
{
  "success": true,
  "message": "SOS Add Successfully.",
  "data": {
    "id": 47, "resort_id": 26, "emergency_id": 3,
    "emp_initiated_by": 205, "location": "Beach Villa 12",
    "latitude": "4.1755", "longitude": "73.5093",
    "status": "Pending", "date": "2026-08-17", "time": "14:32:10",
    "emergency_description": "Guest collapsed near the pool"
  }
}
```

**`handle-sos-action-with-team` response** (same shape for approve/reject):
```json
{
  "success": true,
  "message": "SOS Active successfully.",
  "data": { "...": "full sos_history row, status now Active/Rejected/etc." }
}
```

**`employee-team-location/{sos_id}` response:**
```json
{
  "success": true,
  "message": "SOS Security Staff Fetched successfully.",
  "data": [
    { "type": "employee", "emp_id": 205, "status": "Safe", "latitude": "4.1755", "longitude": "73.5093", "first_name": "Jayanka", "last_name": "Murli", "profile_picture": "https://..." },
    { "type": "team_member", "emp_id": 475, "status": "Acknowledged", "team_id": 3, "team_name": "Fire Response", "latitude": "4.1760", "longitude": "73.5095", "first_name": "...", "last_name": "..." }
  ]
}
```

**`manager-dashboard` response:**
```json
{
  "success": true,
  "message": "Manager dashboard fetched successfully.",
  "data": {
    "pending_approval_count": 1,
    "pending_approval": [ { "...": "sos_history rows, status=Pending" } ],
    "active_sos_count": 2
  }
}
```

**`security-staff-dashboard` response:**
```json
{
  "success": true,
  "message": "Security staff dashboard fetched successfully.",
  "data": {
    "active_sos_count": 2,
    "active_sos": [ { "...": "sos_history rows, status in Pending/Active/Real-Active/In-Progress" } ]
  }
}
```

## Known gaps (not built, flagging rather than guessing)

- **No persisted GPS trail.** `sos_history_employee_status`/`sos_team_member_activity` hold current position only — no "replay the last 10 minutes of movement" capability. Flag if the app needs that; it's a new table + a small change to `location-update`, not built now.
- **`sos-history-listing`/`sos-history-details` are resort-wide, not scoped to the caller.** Any employee can see any past incident's history for their resort. Intentional per existing code (not something introduced here) — flag if that needs narrowing.
- Video reference in the ticket comment wasn't accessible from here — if the flow it shows differs from what's documented above (e.g. a different role model, additional screens), point out the specific gap and it'll get built/adjusted rather than guessed at.

## 2026-09-09 — Figma review against the real backend, web dashboard fixes

Compared the mobile Figma screens (Trigger SOS / Pending Approval / Receive
SOS / Employee Locations, Security Manager's Emergency Alerts + Select Team
+ SOS Confirmed, SOS History + Chat Logs, and the 3 web dashboard screens —
Team Activity, Employee Safety Status, Live Locations) against what's
actually built. Net result: **the mobile API surface Figma needs already
exists almost entirely** (documented above); the 3 web pages reported as
"not working" have one real code bug (fixed) and one data-completeness
issue that isn't a code bug at all.

### Mobile screens vs existing API — mapped, nothing new needed

| Figma screen | Backend endpoint |
|---|---|
| Trigger SOS (Fire/Medical/Security type picker) | `GET sos/emergency-types` + `POST sos/sos-store` |
| Pending-approval state (yellow ring, play icon) | `GET sos/get-any-sos-emergency` — poll this for the employee's own incident's current status |
| Receive SOS / "Hold to call" | `GET sos/get-any-sos-emergency` (own role/permissions on it) — the Call button is a plain `tel:` action, no API |
| Employees Location (map + roster) | `GET sos/employee-team-location/{sos_id}` |
| Security Manager: Emergency Alerts (Confirm & Notify / Reject) | `POST sos/handle-sos-action-with-team` (`action: Active|Drill-Active|Rejected|Drill-Rejected`) |
| Security Manager: Select Team + Pertinent Information + Submit | Same call — `team_id[]` + `team_message` are exactly "Select Team" + "Pertinent Information" |
| SOS Confirmed (Fire Activated, Under Control / Disable, View Employees Status) | `POST sos/complete-sos-update-status` (Disable→Completed); "Under Control" has no dedicated status today — see gap below |
| SOS History listing + detail (acknowledged count, incident timeline) | `GET sos/sos-history-listing`, `GET sos/sos-history-details/{sos_id}` |
| Chat Logs | `GET sos/chat-logs/{sos_id}`, `POST sos/send-chat-message` |
| Team Activity (web) — total/acknowledged/pending, per-member row | `GET sos/get-team-acknowledged/{sos_id}` mobile-side; web page below reads the same tables directly |

**One real gap**: the Figma "SOS Confirmed" screen has an **"Under Control"**
button distinct from "Disable" — today there's only `complete-sos-update-status`
(→ `Completed`, matches "Disable"). Nothing marks an incident "handled but
still open" separately from fully closing it. If that distinction matters
to the workflow (vs. just a UI label), it needs a new status value or a
boolean flag on `sos_history` — not built, flagging rather than guessing
at what "Under Control" should actually change.

### Web dashboard: what was actually broken

**`sos/employees-live-location/{id}` — fixed.** `employeeLiveLocation.blade.php`
hardcoded a literal Google Maps API key directly in the script tag instead
of reading `env('GOOGLE_MAPS_API_KEY')` — the pattern every other map page in
this app already uses correctly (`geofence-zones.blade.php`,
`timeandattendance/Configration/index.blade.php`). That stray key is almost
certainly invalid/restricted/over-quota, which is exactly the generic
"Sorry! Something went wrong. This page didn't load Google Maps correctly"
error Google's JS SDK shows for any bad key — not a data or logic problem.
Fixed to use the real configured key.

**`sos/view-team-activity/{id}` and `sos/view-employee-safety-status/{id}` —
not a code bug, a data-completeness issue for this specific test incident.**
Checked incident 457 (the id in both reported URLs) directly against the
database: it's a real row (`resort_id: 26`, `status: Drill-Active`), but
`sos_team_member_activity`, `sos_history_employee_status`, and
`child_sos_history` all have **zero rows** for it. Both controller
methods' queries are correct and simple (`where('sos_history_id', $id)` —
nothing wrong with the read side).

Root cause: those 3 tables are only ever populated inside
`handleSOSActionWithTeam()` (`sos/handle-sos-action-with-team`), and only
on the `Active`/`Drill-Active` branch (bulk-inserts one
`sos_history_employee_status` row per active employee, plus one
`child_sos_history` + `sos_team_member_activity` row per dispatched team
member). But `drillRealSOS()` (`sos/drill-real-sos`) — a **separate,
earlier** endpoint that classifies a Pending incident as
`Real-Active`/`Drill-Active` before team dispatch, by its own explicit
comment ("no team is assigned yet at this point") — sets that exact same
status value on its own, with no team-dispatch side effect at all.

So an incident can reach `Drill-Active` status two ways: (1) through
`drill-real-sos` alone (classification only, no team/employee rows), or
(2) through `handle-sos-action-with-team` (full dispatch, all 3 tables
populated). Incident 457 went through path (1) only — classified but never
actually dispatched to a team — which is why every web dashboard page that
reads those 3 tables shows empty. This is expected behavior for an
incident in that state, not a bug in the pages themselves. Two ways to
close this out, depending on what's true operationally:
- If every real incident is expected to always get dispatched (path 2)
  shortly after classification, this is just incomplete test data — no
  code change needed, re-test against an incident that went through the
  real Security Manager dispatch flow.
- If an incident can legitimately sit "classified but not yet dispatched"
  for a while in real usage, the web pages should say so explicitly
  ("No team dispatched yet" / "No employee status recorded yet") instead
  of silently rendering an empty table — a small, contained view-level
  fix, not built here since it's a product decision (show a real empty
  state, or treat "classified but undispatched" as not reachable in
  practice).

Confirmed via web search of the actual controllers that the web portal has
**no path of its own** that sets `sos_history.status` — every status
transition is mobile/API-only (`drillRealSOS`, `handleSOSActionWithTeam`,
`completeSOSUpdateStatus`). The web dashboard is read-only monitoring by
design, which matches the Figma web screens shown (Team Activity /
Employee Safety Status / Live Locations are all display + filter + "Send
Mass Instructions", no dispatch action anywhere in them) — consistent,
not a gap.

### Module status summary

**Done:**
- Full SOS trigger → classify → dispatch → acknowledge → resolve API
  surface (mobile), matching every mobile Figma screen shown.
- Web configuration (teams, roles, emergency types) — already comprehensive
  per your own note.
- Web monitoring dashboards (Team Activity, Employee Safety Status, Live
  Locations) — correct read-side queries, filters (`All Teams`/`All
  Department` dropdowns, `Unacknowledged Only`/`Unknown Status Only`
  toggles), and `Send Mass Instructions` (`updateMassInstruction()`) all
  already built and wired to real endpoints.
- Google Maps live-location page — fixed this pass.

**Pending / needs a decision, not yet built:**
- "Under Control" as a distinct SOS Confirmed-screen state (see gap above)
  — needs a product decision on what it should actually change before
  it's built.
- Empty-state messaging on the 2 monitoring pages for a classified-but-
  undispatched incident (see above) — small, only worth doing if that
  state is actually reachable in real usage rather than test-only.
- No persisted GPS trail / resort-wide (not caller-scoped) SOS history —
  both already flagged above, unchanged by this pass.

### For app dev: confirm dialog (add before shipping)

Every irreversible SOS action should get a native confirm dialog before
the API call fires — none of this needs a new endpoint, it's purely a
client-side gate in front of calls that already exist:

- **Triggering an SOS** (`sos/sos-store`) — "Are you sure you want to
  trigger an SOS? This will alert Security and cannot be undone." /
  Confirm / Cancel. This is the single highest-value confirm to add: a
  misclick here pages real people.
- **Security Manager: Confirm & Notify / Reject** (`handle-sos-action-with-team`)
  — confirm before dispatching a real team, and separately before
  rejecting (in case of misclick on a real emergency).
- **Disable / mark Completed** (`complete-sos-update-status`) — confirm
  before closing out an incident that might still be active on the
  ground.

Recommend the confirm copy restates the consequence in plain language
(not just "Are you sure?") — e.g. "This will notify the Fire Team and all
employees at this resort" — since the cost of a false-negative cancel is
low but the cost of an accidental real dispatch or an accidental close-out
is high.
