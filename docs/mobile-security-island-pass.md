# Security — Island Pass & Manifest Mobile API

For the mobile team. Controller: `app/Http/Controllers/API/BoardingPassController.php`.
This is mostly already built — this documents the existing flow against
the ticket's 4 requirements and flags the one real inconsistency found.

## The 4 requirements, mapped to what exists

**1. "All security officers can access Island Pass, Departure Manifest,
Arrival Manifest"** — the manifest endpoints already enforce this
correctly via `userCanManageManifests()`: any active employee in the
Security department (any rank, via `Common::isSecurityDepartment`), or an
HR-department HOD/EXCOM. **Flag**: `SODashboard()` (`GET
boarding/security-officer-dashboard`) uses a *different*, narrower scope —
`EmployeeTravelPassAssign.employee_id = <caller>` — i.e. it only shows
passes specifically assigned to that individual officer, not "any security
officer." If the Figma dashboard is meant to be department-wide like the
manifest screens, this needs a scoping change; if it's deliberately "my own
assigned duties," it's correct as-is. Didn't change it without knowing
which — flagging for a decision.

**2. "Approved Island Passes flow through HOD → HR → Security approval"**
— already correct and already fixed this session (see the Security
Manager queue-empty bug fix, and the HOD-self-approval fix). Not
Island-Pass-Manifest specific, just confirming it's solid.

**3. "Security creates the Manifest first; only then employees appear in
the list"** — already correct: `transportationDateBasedEmp()` (the
employee picker for `manifest-store`) only returns passes where *every*
approval stage (HOD→HR→SM) is `Approved` — a pass still pending anywhere
in the chain never shows up as manifest-ready.

**4. "Clicking an employee opens their approved pass/boarding pass"** —
the pieces exist: `manifest-details/{id}` returns each employee's name/
photo/position; to open that person's full pass, call
`boarding-pass-view/{pass_id}` for their `EmployeeTravelPass.id` (visible
via `boardingEmpDashboard`/`boarding-pass-approve-list` for that employee,
or resolvable by employee_id + manifest_id). No dedicated
"manifest-employee → pass detail" single-call endpoint exists yet — flag
if the app needs one to avoid a second lookup.

## Endpoints

| Endpoint | Method | Gate | Purpose |
|---|---|---|---|
| `boarding/security-officer-dashboard` (alias `boarding/so-dashboard`) | GET/POST | any authenticated employee (see flag above) | Today's departures/arrivals assigned to *this* officer via `EmployeeTravelPassAssign` |
| `boarding/transportation-date-based-emp` | POST | `sos.manager`-style: Security dept or HR HOD/EXCOM | Employee picker for a manifest — fully-approved passes only, filtered by transportation + date + arrival/departure |
| `boarding/manifest-store` | POST | same | Create a manifest, attach employees + visitors, stamps confirmed time/mode on each pass |
| `boarding/manifest-listing` | POST | same | `{"manifest_type": "arrival"\|"departure"}` → all `saved`-status manifests of that type |
| `boarding/manifest-schedule` | GET | any authenticated employee | Look up an existing manifest by transportation+date (auto-fill helper) |
| `boarding/manifest-details/{manifest_id}` (base64 id) | GET | Security dept or HR HOD/EXCOM | One manifest's full employee + visitor list |
| `boarding/manifest-details-pdf/{manifest_id}` | GET | same | Downloadable PDF of the manifest |
| `boarding/boarding-pass-view/{pass_id}` | GET | any authenticated employee (own-resort scoped) | Full Island Pass detail for one employee — this is the "click an employee" destination |

### GET boarding/manifest-details/{manifest_id}

```json
{
  "success": true,
  "message": "Manifest details fetched successfully",
  "data": {
    "id": 24, "manifest_type": "arrival", "transportation_mode": "Speedboat", "transportation_name": "Sea Rider 1",
    "date": "2026-08-05", "time": "09:00",
    "employees": [
      { "id": 5, "employee": { "id": 189, "Emp_id": "DR-20", "resortAdmin": { "first_name": "Rani", "last_name": "Khan", "profile_picture": "https://..." }, "position": { "position_title": "Waitress" } } }
    ],
    "visitors": [ { "id": 2, "visitor_name": "..." } ]
  }
}
```

## Known gap (flagging, not guessing)

No Figma reference was available for this pass, so the "Security dashboard"
screen's layout/buttons weren't verified pixel-for-pixel — only the data
endpoints behind it. If a specific button/section still does nothing after
wiring to the endpoints above, point me at that one element with the
Figma reference and I'll check the exact data it needs.
