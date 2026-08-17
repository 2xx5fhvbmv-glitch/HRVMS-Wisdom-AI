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
