# HR & L&D Manager Onboarding API

For the mobile team — covers the three screens in the "On Boarding" Figma
set: `hr-dashboard`, `l&d-manager-dashboard`, and `l&d-manager` mark
attendance. Already built and live on `demo`/`master`; this documents it.

Controllers: `app/Http/Controllers/API/OnBoardingController.php` (`hrDashboard`)
and `app/Http/Controllers/API/LearningController.php` (`ldManager*`). All
routes sit under `auth:api`.

## Role separation

| Screen | Endpoint gate | Who passes |
|---|---|---|
| `hr-dashboard` | Self-checked inside the controller (not route middleware) | HR department only (`Common::isHRDepartment`) — 403 otherwise |
| `l&d-manager-dashboard`, training calendar, mark-attendance (all 3 steps) | Route middleware `ld.manager` | Employee whose position title is `Training Director` / `L&D Manager` / `Learning & Development Head`, OR anyone in the L&D department (`Common::isLDDepartment`). A resort master admin bypasses both gates. |

Neither gate is rank-based — HOD/EXCOM/HR/GM rank alone does **not** unlock
either screen; it's department/title only, matching who these dashboards
are actually built for.

## GET on-boarding/hr-dashboard

No params. Response:
```json
{
  "success": true,
  "message": "HR onboarding dashboard fetched successfully.",
  "stats": {
    "total_upcoming_arrivals": 20,
    "completed_tasks": 15,
    "pending_tasks": 5,
    "average_time_days": 2.0
  },
  "upcoming_arrivals": [
    {
      "itinerary_id": 12,
      "employee_id": 205,
      "employee_name": "Christian Slatter",
      "employee_photo": "https://...",
      "arrival_date": "2026-01-05",
      "arrival_time": "18:30",
      "representatives": [
        { "role": "Pickup", "name": "Brijesh Pandey", "contact": "251 541 1548" },
        { "role": "Medical Escort", "name": "...", "contact": "..." }
      ],
      "send_my_selfie_available": true,
      "flight_ticket_available": false,
      "status": "Itinerary Created"
    }
  ],
  "onboarding_itineraries": [
    { "itinerary_id": 12, "date": "2026-10-28", "itinerary_title": "Onboarding — Christian Slatter", "pending_tasks_count": 15 }
  ],
  "assigned_tasks": [
    {
      "itinerary_id": 12,
      "task": "Pick up Christian Slatter at the Airport",
      "date": "2026-01-05", "time": "18:30",
      "assignee": "George Franklin",
      "assignee_contact": "...",
      "status": "Pending"
    }
  ],
  "upcoming_meetings": [
    { "meeting_id": 3, "title": "Zoom Meeting with EXCOM", "date": "2026-01-05", "time": "18:30", "meeting_link": "https://meet.google.com/landing" }
  ]
}
```

Card mapping: `stats.total_upcoming_arrivals`/`completed_tasks`/
`pending_tasks`/`average_time_days` are the 4 top tiles. `upcoming_arrivals`
drives the "Upcoming Arrivals" list (each card's Contact button uses a
representative's `contact`, "Access Flight Tickets" gates on
`flight_ticket_available`). `onboarding_itineraries` → "Onboarding
Itinerary" list, `pending_tasks_count` is the "Pending Tasks: N" line.
`assigned_tasks` → "Assigned Tasks" list, `status` drives strikethrough/done
styling. `upcoming_meetings` → "Upcoming Meetings", `meeting_link` is the
tappable Zoom/Meet URL.

`average_time_days` is the average gap between an itinerary being created
and the employee's `arrival_date`, resort-wide (`Common::averageOnboardingLeadDays`).

## GET ld-manager/dashboard

No params. Response:
```json
{
  "success": true,
  "message": "L&D Manager dashboard fetched successfully.",
  "stats": {
    "total_upcoming_arrivals": 20,
    "average_time_days": 2.0,
    "onboarding_trainings_scheduled_count": 15,
    "pending_onboarding_trainings_scheduled_count": 5,
    "total_itineraries_created_count": 16
  },
  "upcoming_arrivals": [
    {
      "itinerary_id": 12,
      "employee_id": 205,
      "employee_name": "Christian Slatter",
      "employee_photo": "https://...",
      "arrival_date": "2026-01-05",
      "arrival_time": "18:30",
      "representatives": [ { "role": "Pickup", "name": "...", "contact": "..." } ],
      "flight_ticket_available": false,
      "status": "Itinerary Created",
      "training_status": "Training Scheduled"
    }
  ]
}
```
Top tiles: `total_upcoming_arrivals`, `average_time_days` (same figure as
the HR dashboard — one shared calc), `onboarding_trainings_scheduled_count`,
`pending_onboarding_trainings_scheduled_count`, and the standalone
`total_itineraries_created_count` tile. `training_status` on each arrival
(`"Training Scheduled"` / `"Not Scheduled"`) is the underlined status line
under the employee's name in the Figma card.

**"Onboarding training"** has no dedicated flag in the schema — it's
derived as any `training_schedules` row with at least one participant who
is also a new-arrival employee (has an `employee_itineraries` row). If L&D
needs a real onboarding-vs-regular-training distinction later, that's a
schema addition, not something this endpoint can infer more precisely today.

## GET ld-manager/training-calendar

Query params (all optional): `view` (`day`\|`weekly`\|`monthly`, default
`monthly`), `date` (`YYYY-MM-DD`, defaults to today — the calendar window is
computed around this), `category_id` (omit for "All Category").

```json
{
  "success": true,
  "message": "Training calendar fetched successfully.",
  "view": "monthly",
  "start_date": "2026-01-01",
  "end_date": "2026-01-31",
  "categories": [
    { "id": null, "category": "All Category" },
    { "id": 4, "category": "Safety" }
  ],
  "events": [
    {
      "training_schedule_id": 9,
      "training_id": 3,
      "title": "Fire Safety Refresher",
      "category": "Safety",
      "session_date": "2026-01-08",
      "end_date": "2026-01-08",
      "start_time": "09:00 AM",
      "end_time": "11:00 AM",
      "participants_count": 6
    }
  ],
  "employee_cards": [
    {
      "employee_id": 205,
      "employee_name": "Emily Watson",
      "employee_photo": "https://...",
      "upcoming_training": { "title": "Fire Safety Refresher", "date": "2026-01-08", "time": "05:00 PM" },
      "on_boarding_trainings_count": 3,
      "completed_count": 1,
      "pending_count": 2
    }
  ]
}
```
`events` are the dots plotted on the calendar grid (`session_date` marks the
day). `categories` populates the "All Category" dropdown — `id: null` means
don't filter. `employee_cards` is the scrollable list below the calendar
("Emily Watson — Upcoming Training - 08 Jan 5:00pm" + the 3-number
breakdown), scoped to the new-arrival cohort only, same as the dashboard.

## Mark Attendance (3-step flow)

**Step 1 — GET ld-manager/mark-attendance/trainings**: populates the
"Select Training" dropdown. Only returns trainings **currently in
progress** (`start_date <= today <= end_date`), not the raw `status`
column — every `training_schedules` row is stuck at `Scheduled` regardless
of real dates in this dataset, so status can't be trusted for this filter.
```json
{ "success": true, "message": "Trainings fetched successfully.",
  "trainings": [ { "training_schedule_id": 9, "name": "Training 1", "start_date": "2026-01-08", "end_date": "2026-01-08" } ] }
```

**Step 2 — GET ld-manager/mark-attendance/participants/{training_schedule_id}**:
the "Select Present Employees" checklist.
```json
{
  "success": true,
  "message": "Participants fetched successfully.",
  "training": { "training_schedule_id": 9, "name": "Training 1", "start_date": "2026-01-08", "end_date": "2026-01-08" },
  "participants": [
    { "employee_id": 205, "name": "Christian Slatter", "photo": "https://...", "department": "F&B", "position": "Sheaf", "is_present": true },
    { "employee_id": 240, "name": "Emily Watson", "photo": "https://...", "department": "F&B", "position": "Sheaf", "is_present": false }
  ]
}
```
`is_present` pre-checks the box **only** if a `TrainingAttendance` row
already exists for *today* with status `Present` — gives the Figma's mixed
pre-checked/unchecked default for free rather than assuming everyone starts
checked.

**Step 3 — POST ld-manager/mark-attendance**: the Submit button.
Body:
```json
{
  "training_schedule_id": 9,
  "employees": [
    { "employee_id": 205, "status": "Present" },
    { "employee_id": 240, "status": "Absent" }
  ]
}
```
Every id is resort-scoped in the validator itself
(`Rule::exists(...)->where('resort_id', ...)`) — no cross-tenant gap.
Send **every** participant from Step 2's list with their final checkbox
state, not just the ones that changed — this does an `updateOrCreate` per
row for today's date, so a participant left out simply keeps whatever
attendance record already existed (or none, if this is the first submit).
```json
{ "success": true, "message": "Attendance marked successfully." }
```

## Learning request list (L&D Manager, view-only)

`GET learning/manager-request-list` — same `ld.manager` gate. Not part of
the Figma set above but built alongside it: the L&D Manager's incoming
training-request queue, read-only (approve/reject/schedule stays web-only —
each item includes `action_note` saying so). Not documented in full detail
here; ask if you need the response shape for this screen too.
