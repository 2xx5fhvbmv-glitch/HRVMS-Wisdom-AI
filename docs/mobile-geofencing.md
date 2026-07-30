# Geofencing — Auto vs Manual Attendance

For the mobile team. Covers geofence zone data, how "auto" and "manual"
check-in/out actually differ in the backend, and exact request/response
shapes.

Controller: `app/Http/Controllers/API/TimeAndAttendanceController.php`
Model: `app/Models/ResortGeofence.php` (table `resort_geofences`)
Geometry helper: `Common::isWithinGeofence()` (`app/Helpers/Common.php`) —
Haversine distance for `circle`, point-in-polygon for `polygon`.

## The actual auto/manual distinction

There is **no** per-resort or per-employee "auto-checkin on/off" setting.
"Auto" and "manual" are just two different endpoints, distinguished by what
triggers them and whether the geofence result blocks the punch:

| | Manual | Auto |
|---|---|---|
| Triggered by | Employee taps check-in/out button | Mobile app's own background OS geofence monitoring (native `CLLocationManager` / Android Geofencing API) firing an enter/exit event |
| Endpoint | `POST timeandattendance/manual-check-in` / `manual-check-out` | `POST timeandattendance/geofence-event` |
| Geofence result | **Recorded only** — `within=false` never blocks the punch. It's audit data for HR, not a gate | **Enforced** — on `event=enter`, server re-validates via the same `resolveGeofenceCheck()`; if not inside the zone, hard-rejects `422`, no punch happens |
| `flag` on the attendance row | Whatever string the client sends (`CheckInCheckOut_Type`) | Always `'Geofence-Auto'` |

Internally, `geofenceEvent()` doesn't duplicate the check-in logic — on a
valid `enter` it builds a synthetic request and calls `manualCheckIn()`
directly (same for `exit` → `manualCheckOut()`). Same duty-roster
resolution, shift lookup, overtime calc either way; the only difference is
the extra gate in front of the auto path.

## Zone data (`ResortGeofence`)

| Field | Type | Notes |
|---|---|---|
| `resort_id` | FK | cascade delete |
| `name` | string | |
| `color` | string | hex, default `#FF4444` |
| `shape_type` | enum | `polygon` \| `circle` (default `polygon`) |
| `coordinates` | JSON (stored as text) | polygon: `{"points":[{"lat":.., "lng":..}, ...]}`; circle: `{"center":{"lat":..,"lng":..}, "radius":<meters>}` |
| `grace_period` | int, minutes | default 10 — **stored but not read anywhere in the check-in flow**, don't assume it does anything server-side yet |
| `status` | enum | `active` \| `paused` — a punch only ever checks against a zone with `status='active'` |

Zones are assigned to a duty roster via `duty_rosters.geofence_zone_id`, not
directly to the employee. Every consumer (`resolveGeofenceCheck()`,
`myGeofenceZone()`) reads it as a single plain integer id.

**Known issue, not yet fixed**: the roster-creation path
(`DutyRosterController.php`) reads `geofence_zone_ids` (plural, array) from
the request form and `json_encode()`s it into that same single
`unsignedBigInteger` column. A value like `"[3]"` written there won't read
back as a usable int on the consumer side. If a zone silently stops
resolving for a specific roster, check whether that roster's
`geofence_zone_id` is a JSON-encoded array string instead of a plain
integer.

## Manual check-in — `POST timeandattendance/manual-check-in`

Request:
```json
{
  "current_time": "09:03:00",
  "current_date": "2026-08-01",
  "in_time_location": {"latitude": 4.1755, "longitude": 73.5093, "accuracy": 12.5},
  "flag": "Manual"
}
```
`in_time_location` also accepts a Google Maps Embed "view" URL string
(`.../embed/v1/view?...&center=4.1755,73.5093&zoom=12`) — coordinates are
extracted via regex if it's not a plain object. `current_time` is ignored
for the actual stored time; the server always uses its own clock
(`Carbon::now()`) to avoid 12-hour-format AM/PM ambiguity from the device.

Response (success): `{"status": true, "message": "...", "attendance_data": {"parent_attendance_data": {...}, "child_attendance_data": {...}}}`
Response (no roster entry for the date): `{"status": false, "message": "Employee does not have a duty roster entry for this date. Please contact your HOD first."}`
Response (already checked in): `{"status": true, "message": "Already Checked In Today", "attendance_data": {...}}`

`child_attendaces` records the geofence result alongside the punch:
`InTime_Latitude`, `InTime_Longitude`, `InTime_Accuracy`, `InTime_WithinGeofence`, `InTime_GeofenceName` (same `OutTime_*` fields on check-out). None of these block anything — they're for HR review.

## Manual check-out — `POST timeandattendance/manual-check-out`

Same shape, using `out_time_location` instead of `in_time_location`, plus `attendace_id` (the `parent_attendaces.id` to close out).

## Auto (geofence-triggered) — `POST timeandattendance/geofence-event`

Request:
```json
{
  "event": "enter",
  "latitude": 4.1755,
  "longitude": 73.5093,
  "accuracy": 8.0
}
```
`event` is `enter` or `exit`.

- **`enter`**: looks up the employee's current duty roster's assigned zone, runs `resolveGeofenceCheck()`. If `within !== true` → `422 {"success": false, "message": "Reported location is not within the assigned geofence zone; no auto check-in."}`. If inside, calls through to `manualCheckIn()` with `flag='Geofence-Auto'` — same response shape as manual check-in above.
- **`exit`**: if there's an open check-in today (no checkout yet), calls through to `manualCheckOut()`. If nothing is open (already checked out, or never checked in), no-ops: `{"success": true, "message": "No active check-in to close out."}` — this is not an error, just nothing to do.

## Home-screen zone lookup — `POST timeandattendance/my-geofence-zone`

Read-only — draws the zone on the map and shows within/outside without
attempting a punch. Request: `{"latitude": 4.1755, "longitude": 73.5093}`
(both optional — omit to just get zone shape/color without a `within`
verdict).

Response:
```json
{
  "success": true,
  "message": "Geofence zone fetched successfully.",
  "geofence": {
    "id": 12,
    "name": "Staff Village",
    "color": "#FF4444",
    "shape_type": "circle",
    "coordinates": {"center": {"lat": 4.1755, "lng": 73.5093}, "radius": 150},
    "grace_period": 10,
    "within": true
  }
}
```
If no roster, no assigned zone, or the assigned zone is `paused`:
`{"success": true, "message": "...", "geofence": null}` — not an error, treat as "no zone to draw".

Uses the employee's **latest** `duty_rosters` row (`orderByDesc('id')`) —
an employee can accumulate several roster template rows over time, so an
old/superseded one's zone assignment doesn't win over the current one.

## Zone CRUD (web portal, not mobile-facing)

`app/Http/Controllers/Resorts/TimeAndAttendance/ConfigrationController.php` —
`geofenceList`/`geofencePage`/`geofenceStore`/`geofenceUpdate`/`geofenceDelete`/`geofenceToggle`.
Toggle flips `status` between `active`/`paused`. Routes in
`routes/resort_route.php` and `routes/resort_routemain.php`. Zones are
created/edited by HR/admin on the web portal only — mobile only ever reads
zone data via `my-geofence-zone` and `geofence-event`.
