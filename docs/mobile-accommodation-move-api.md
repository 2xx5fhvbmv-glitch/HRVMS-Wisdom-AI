# Accommodation — Move/Reassignment Mobile API

For the mobile team, from the "Accommodation – Room Info & Bed Assignment –
Redesign & Dynamic Data" card. Controller:
`app/Http/Controllers/API/AccommodationController.php`, method
`moveAccommodationForEmp()`.

## Why this is a separate endpoint from Assign

`POST accommodation/assign-accommodation-to-emp` is **first-time only** — if
the employee already has a bed, it returns
`{"status": false, "message": "Bed Already assigned."}` and does nothing.
Since most employees already have accommodation, moving them needs this new
endpoint instead. It mirrors the web portal's "Move to Next" flow
(`AssignAccommodationController::MoveToNext()`): frees the employee's
current bed, assigns the new one, writes accommodation history, and
notifies the employee.

## POST accommodation/move-accommodation-for-emp

Same auth/rank gate as the other HR accommodation endpoints
(`auth:api`, rank HR/GM/HOD/EXCOM).

**Request body:**
```json
{
  "emp_id": 173,
  "assign_id": 7,
  "accommodation_type_id": 1,
  "building_id": 1,
  "floor": 1,
  "room": 1,
  "bed": 1,
  "reason": "Requested by employee"
}
```
- `emp_id` — the employee to move (numeric `employees.id`, not the
  `DR-xxx` string).
- `assign_id` — the **new** bed's id, i.e. an `assing_accommodations.id`
  row for an empty bed (`emp_id = 0`). This is the only field the backend
  actually uses to pick the destination — same convention as `assign-
  accommodation-to-emp`.
- `accommodation_type_id`, `building_id`, `floor`, `room`, `bed` — required
  (matches the field list from the card), but only for validation/logging
  parity with the Assign endpoint; the backend doesn't need them to resolve
  anything since `assign_id` already identifies the exact bed. Send
  whatever your Select Building → Select Floor/Room → Select Bed flow
  currently has in state.
- `reason` — optional, freeform text, stored on the history row (defaults
  to `"Moved via mobile"` if omitted).
- You do **not** need to send the employee's *current*/old bed — it's
  resolved server-side from `emp_id`. If the employee has no current
  accommodation at all, this returns a 422 telling you to use
  `assign-accommodation-to-emp` instead (this endpoint is a move, not a
  first assignment).

**Success response (200):**
```json
{
  "status": true,
  "message": "Bed moved successfully.",
  "bed_assign_data": {
    "employee": {
      "name": "Anastasia Volkova",
      "position": "Accounting Clerk",
      "profile_picture": "https://thewisdom.io/.../profile.jpg",
      "emp_id": "DR-4"
    },
    "accommodation": {
      "building_name": "Building B",
      "floor": 2,
      "room_no": "201",
      "bed_no": "BedNo-3",
      "facilities": ["Dustbin", "Study Table", "Wardrobe Cabinet", "Mattress (Single)", "Sofa"],
      "RoomStatus": "Available",
      "color": "DefaultColor",
      "accommodation_name": "Four Share"
    },
    "previous_accommodation": {
      "building_name": "Building A",
      "floor": 1,
      "room_no": "1",
      "bed_no": "BedNo-1"
    }
  }
}
```
`previous_accommodation` is only present here (not on the plain Assign
endpoint) — use it for a "moved from X to Y" confirmation screen.

**Error responses:**
- `401` — not authenticated.
- `400` — validation failed (`errors` object, same shape as Assign).
- `404` — `{"success": false, "message": "Accommodation not found."}` (bad
  `assign_id`) or `{"success": false, "message": "Employee not found."}`.
- `422` — `{"success": false, "message": "Employee has no current accommodation to move from — use assign-accommodation-to-emp instead."}`,
  or `{"success": false, "message": "Employee is already assigned to this bed."}`.
- `500` — `{"success": false, "error": "Failed to move bed", "message": "Failed to move accommodation"}`.

## What happens server-side

1. Resolves the employee's current bed (`assing_accommodations` row where
   `emp_id` matches).
2. Frees it (`emp_id = 0`, `effected_date` cleared) and assigns the new bed
   (`emp_id`, `effected_date = today`).
3. Writes one `transfer_accommodations` row (old accommodation id/date →
   new accommodation id/date + reason) — this is what feeds the web
   portal's accommodation-history view for that employee, and is the same
   helper (`Common::recordAccommodationHistory()`) every other
   assign/unassign path in the app now uses. Never overwrites — every move
   adds a new row, so the full history stays intact.
4. Adjusts inventory occupancy: decrements the room being left, increments
   the room being moved into (both legs — the web portal's two existing
   methods for this each only do one half, which was a latent bug; this
   endpoint does both, correctly).
5. Sends a push/in-app notification to the employee: "You have been moved
   to bed {bed_no}." — same as web's Move to Next.

## Known pre-existing issue you don't need to work around

Before this change, `AssingAccommodation.effected_date` wasn't in the
model's mass-assignment list, so it silently never got updated by *any*
existing assign/unassign call (web or mobile) — this has been fixed at the
model level (`app/Models/AssingAccommodation.php`), so `effected_date` now
correctly reflects "today" after any assign/move, everywhere in the app,
not just this new endpoint.
