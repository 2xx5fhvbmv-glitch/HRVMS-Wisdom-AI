# Learning & Development — Employee + Manager Mobile API

For the mobile team. Controller: `app/Http/Controllers/API/LearningController.php`.
This module is already fully built on the backend — this doc is the
integration reference for both the employee-facing and manager-facing
Figma screen sets.

## Figma screen → endpoint map

**L&D (Employee)** — any authenticated employee, no rank/role gate:

| Screen | Endpoint |
|---|---|
| `employee-dashboard` | `GET learning/employee-learning-dashboard` (alias `learning/emp-lt-dashboard`) |
| `Training-calendar` | `POST learning/employee-training-calendar` |
| `Training-details-employee` | `GET learning/training-details/{schedule_id}` |
| `Feedback Form Screen` (submit) | `GET learning/feedback-from-list` (form to render) + `POST learning/feedback-data-store` (submit) |
| `Evaluation Form Screen` (submit) | `GET learning/evaluation-from-list` (form to render) + `POST learning/evaluation-data-store` (submit) |

**L&D Manager** — gated by role/position (`check.rank:EXCOM` for the two
`manager-*` endpoints below; `ld.manager` — L&D position title or L&D
department, not rank — for everything under `ld-manager/*`, documented in
full in `docs/mobile-hr-ld-onboarding-manager.md`):

| Screen | Endpoint |
|---|---|
| `training-calendar` (general — every training + approved learning request, with participant avatars) | `POST learning/manager-training-calendar` |
| `Training-details` (Trainer, Participants, Present/Absent) | `GET learning/training-details/{schedule_id}` — same endpoint as the employee screen |
| `l&d-manager-mark-attendance` | `GET ld-manager/mark-attendance/trainings` → `GET ld-manager/mark-attendance/participants/{id}` → `POST ld-manager/mark-attendance` — see `docs/mobile-hr-ld-onboarding-manager.md` |
| `feedback-form` (manager reviewing everyone's responses for a session) | `POST learning/participant-feedback-from-list` |
| `evaluation-form` (manager reviewing everyone's responses for a session) | `POST learning/participant-evaluation-from-list` |
| L&D Request screen (**new — see below**) | `GET learning/manager-request-list` |

**Two different "manager calendar" endpoints exist — don't conflate them:**
`learning/manager-training-calendar` (this doc) is the general resort-wide
L&D calendar shown in this Figma set. `ld-manager/training-calendar`
(the *other* doc) is a purpose-built variant scoped to the new-hire
onboarding cohort, built for the onboarding module's L&D Manager dashboard
— different data, different screen, same-sounding name. Use
`manager-training-calendar` for this Figma set.

Evaluation forms are now built — own form/response tables (`evaluation_form`
/ `evaluation_form_responses`), same shape as feedback. See "Evaluation
forms" section below.

## L&D Request screen — already built

`GET learning/manager-request-list` (gated by `ld.manager`, same as the
other `ld-manager/*` endpoints) returns exactly the fields asked for:
```json
{
  "success": true,
  "message": "Learning request list fetched successfully",
  "learning_request_list": [
    {
      "id": 68,
      "learning_name": "Customer Service with a Smile",
      "suggested_employees": [ { "employee_id": 205, "name": "Christian Slatter" } ],
      "requested_by": "Roshan Faruk",
      "reason": "...",
      "start_date": "2026-08-20",
      "end_date": "2026-08-21",
      "action_note": "To approve, reject, or schedule this training, please use the web portal.",
      "attachments": [ { "id": 4, "url": "https://..." } ]
    }
  ]
}
```
Scoped to `Pending` requests only — this is a read-only queue, matching the
tooltip requirement exactly (`action_note` is that tooltip's text; approve/
reject/schedule stays web-only, no mobile write endpoint for it).

## GET learning/employee-learning-dashboard (alias: learning/emp-lt-dashboard)

Employee's own L&D summary, all-time (not scoped to the current month):
```json
{
  "success": true,
  "emp_dashboard_data": {
    "training_completed_hours": 6.5,
    "pending_training_count": 2,
    "ongoing_training_count": 1,
    "completed_training_count": 4,
    "assign_trainig_prog_comp_percen": 40,
    "assign_trainig_programs": [
      {
        "id": 12, "title": "Fire Safety Refresher",
        "session_date": "2026-08-05",
        "start_time": "09:00 AM", "end_time": "11:00 AM",
        "description": "...", "status": "Ongoing"
      }
    ]
  }
}
```
`ongoing_training_count`/`completed_training_count`/`pending_training_count`
are derived from each session's `start_date`/`end_date` vs today (ongoing:
started, not yet ended; completed: ended; pending: not yet started) — **not**
the raw `status` column on `training_schedules`, which is unreliable across
the whole table (every row is stuck at `'Scheduled'` regardless of its real
dates, confirmed against live data). Same date-derived approach the web
HOD dashboard already uses for the identical reason — this is what makes
the three counts match web instead of drifting from it again.

`assign_trainig_programs` mixes two source types with different id spaces —
scheduled trainings (`TrainingSchedule`, id = the linked `learningProgram.id`)
and standalone approved `LearningRequest`s (id = `learning_request.learning_id`,
title prefixed `"Learning Request: "`) — both rendered as one combined list
for display, not meant to be treated as a single foreign key. Note each
item's own `status` field here is still the raw (unreliable) column — use
the three top-level counts above for anything status-driven, not this field.

## POST learning/employee-training-calendar

Body: `{"start_date": "2026-08-01", "end_date": "2026-08-31"}` (both optional —
defaults to current calendar month). Returns only trainings the authenticated
employee is a participant in:
```json
{"success": true, "emp_calender_learning_data": [
  {"id": 12, "title": "...", "session_date": "2026-08-05", "start_time": "09:00 AM", "end_time": "11:00 AM", "description": "...", "color": "#28a745"}
]}
```

## GET learning/training-list

All `Ongoing` trainings at the resort (not scoped to the employee — used to
populate a picker):
```json
{"success": true, "training_list": [{"id": 12, "training_schedule_id": 45, "name": "Fire Safety Refresher"}]}
```
Note `id` (learning program id) vs `training_schedule_id` (the specific
scheduled session) — `GET learning/training-details/{schedule_id}` below
takes `training_schedule_id`, base64-encoded.

## GET learning/training-details/{schedule_id}

`{schedule_id}` is base64-encoded `training_schedules.training_id`. Full
session detail including trainer and every participant's attendance ratio:
```json
{"success": true, "training_data": {
  "training_id": 12, "training_name": "Fire Safety Refresher",
  "training_start_date": "2026-08-05", "training_end_date": "2026-08-05",
  "training_start_time": "09:00:00", "training_end_time": "11:00:00",
  "category": "Compliance", "description": "...",
  "trainer_first_name": "...", "trainer_last_name": "...", "trainer_profile": "https://...",
  "participants": [
    {"id": 88, "Emp_ID": "DR-88", "profile": "https://...", "employee_name": "...", "position": "...", "attendance": "2/3", "rank": "..."}
  ]
}}
```
`attendance` is `"present_days/total_scheduled_days"` for that participant in
that session.

## POST learning/mark-attendance

For the trainer/supervisor marking a session's attendance (not employee
self-service). Only works while `today` falls inside the schedule's own
`start_date`–`end_date` window — outside that range you get a 200 with
`success:false` and an explanatory message, not an error.
```json
{
  "training_schedule_id": 45,
  "employees": [
    {"employee_id": 88, "status": "Present"},
    {"employee_id": 91, "status": "Absent"}
  ]
}
```
`status` is one of `Present|Absent|Late`. Idempotent per employee per day
(re-marking the same employee the same day updates their row, doesn't
duplicate it).

## GET learning/training-based-participant/{schedule_id}

`{schedule_id}` base64-encoded, same id as training-details. Lighter
participant list, just name + whether they're marked Present:
```json
{"success": true, "participant_list": [{"id": 88, "first_name": "...", "last_name": "...", "attendance": "Present"}]}
```
`attendance` is `""` (not `"Absent"`) when no Present record exists yet for
today — this endpoint can't distinguish "not yet marked" from "marked
Absent," only from `mark-attendance`'s own POST body.

## Feedback forms (post-training)

- `GET learning/feedback-from-list` — every feedback form template configured at the resort (`form_structure` is the form's JSON schema for rendering).
- `POST learning/feedback-data-store` — employee submits their response once per training:
  ```json
  {"feedback_form_id": 3, "training_schedule_id": 45, "responses": {"q1": "5", "q2": "Great session"}}
  ```
  Submitting twice for the same form+training+employee returns `success:false`
  with "already been submitted," not a duplicate row or an error.
- `GET learning/feedback-from-res-view/{form_res_id}` — a specific submitted response, joined with its form's `form_name`/`form_structure` for re-rendering read-only.
- `GET learning/feedback-form-res-view` is HR/supervisor-facing (list of all responses for a training via `participant-feedback-from-list`), not employee self-service — included here for completeness since it lives in the same controller.

## Evaluation forms (post-training)

Structural mirror of feedback forms above — separate form/response tables
(`evaluation_form` / `evaluation_form_responses`), same request/response
shapes, just `evaluation_form_id` in place of `feedback_form_id`:

- `GET learning/evaluation-from-list` — every evaluation form template configured at the resort (`form_structure` is the form's JSON schema for rendering, same shape as feedback's — see "Dynamic form fields" below).
- `POST learning/evaluation-data-store` — employee submits their response once per training:
  ```json
  {"evaluation_form_id": 2, "training_schedule_id": 45, "responses": {"date-123": "2026-08-30", "select-456": "option-3"}}
  ```
  Submitting twice for the same form+training+employee returns `success:false`
  with "Evaluation has already been submitted for this participant," not a
  duplicate row or an error.
- `GET learning/evaluation-from-res-view/{form_res_id}` — a specific submitted response, joined with its form's `form_name`/`form_structure` for re-rendering read-only.
- `POST learning/participant-evaluation-from-list` — HR/manager-facing, body `{"training_schedule_id": 45}`, every participant's submitted evaluation-form response for that session (same joined shape as `participant-feedback-from-list` below).

## How a form gets assigned to an employee (web-side, for context)

Neither form type is visible to an employee until HR explicitly assigns it
to that employee for a specific training session (web portal, Learning >
Schedule list > "Assign Form" button on a session that's Ongoing or
Completed). Assignment sets `training_participants.train_feedback_form_id`
or `train_evaluation_form_id` and fires a push notification via
`Common::sendMobileNotification`:

| Form type | `page_id` sent to the app | Notification title/message |
|---|---|---|
| Feedback | `training-feedback-form-assigned` | "Learing Feedback Form" / "Recive feedbackform notification" |
| Evaluation | `training-evaluation-form-assigned` | "Learning Evaluation Form" / "You have a new evaluation form to complete." |

Both carry `module: "Learning"`. Use `page_id` to route the push tap straight
to the right form-submit screen (`learning/feedback-from-list` or
`learning/evaluation-from-list`, filtered to the relevant
`training_schedule_id`) rather than the wording, which may change. A form is
assigned at most once per participant per session — re-tapping "Assign Form"
for someone who already has one assigned is a no-op server-side (no duplicate
notification).

## Dynamic form fields (`form_structure`)

Both `feedback-from-list`/`feedback-from-res-view` and their evaluation
equivalents return `form_structure` as the form's field schema — built with
a drag-and-drop form builder (`formbuilder.online`), so treat every field
type below as something that could appear in any order, and don't assume
this is the exhaustive list of types HR could configure. It's a JSON array
of field-definition objects, e.g.:
```json
[
  {"type":"date","required":false,"label":"Date Field","name":"date-1776965511286-0","subtype":"date"},
  {"type":"select","required":false,"label":"Overall, how would you rate this training program?","name":"select-1776965642027-0","multiple":false,
   "values":[{"label":"Poor","value":"option-1"},{"label":"Okay","value":"option-2"},{"label":"Good","value":"option-3"}]}
]
```
Render one input per array element based on `type` (a `select`/`radio`/
`checkbox` field carries its options in `values[]`, `{label, value}` each —
render the `label`, submit the `value`). `name` is the field's stable key.

**The submit payload's `responses` object is keyed by `name`, valued by
whatever the user picked** — for the example above:
```json
{"date-1776965511286-0": "2026-08-30", "select-1776965642027-0": "option-3"}
```
Submit the raw `value` (e.g. `"option-3"`), not the display `label` — the
server stores exactly what it's given and the response-view/HR-review
screens re-resolve `value` → `label` themselves against the form's
`form_structure`.

**Parse `form_structure` defensively** — at least one real form row has it
double-JSON-encoded (a JSON string containing an escaped JSON string,
instead of a plain array). If decoding it once yields a string rather than
an array/object, decode it again before rendering.

## Manager/HOD-facing (same controller, not employee self-service)

### POST `learning/manager-training-calendar`

Gate: `check.rank:EXCOM`. Resort-wide version of the calendar — every
scheduled training session **and** every approved standalone learning
request, not just the caller's own, each carrying its full participant list
for the stacked-avatar display. Body same as `employee-training-calendar`
(`start_date`/`end_date`, both optional, defaults to current month).
```json
{
  "success": true,
  "message": "Calender Traning data fetched Successfully",
  "calender_learning_data": [
    {
      "id": 12, "title": "Fire Safety Refresher", "session_date": "2026-08-05",
      "start_time": "09:00 AM", "end_time": "11:00 AM", "description": "...",
      "color": "#28a745",
      "participants": [ { "name": "John Doe", "image": "https://...", "position": "Sheaf" } ]
    },
    {
      "id": 20, "title": "Learning Request: Customer Service with a Smile",
      "session_date": "2026-08-20", "start_time": "09:00 AM", "end_time": "05:00 PM",
      "description": "Learning request from Roshan Faruk. ...",
      "color": "#ff9800",
      "participants": [ { "name": "Christian Slatter", "image": "https://...", "position": "Sheaf" } ]
    }
  ]
}
```
`color` distinguishes the two source types on the calendar grid (`#28a745`
green = a real scheduled training, `#ff9800` orange = an approved learning
request not yet turned into a scheduled session) — same id-space caveat as
the employee dashboard's `assign_trainig_programs` applies here too.

### POST `learning/participant-feedback-from-list`

Gate: plain `auth:api` (no explicit rank check in the method itself — reachable
by any authenticated user, though only meaningful for whoever manages the
training). Body: `{"training_schedule_id": 45}`. Every participant's
submitted feedback-form response for that session — backs the manager
`feedback-form` review screen (distinct from the employee's own single-form
*submit* screen above):
```json
{
  "success": true,
  "message": "Feedback data retrieved successfully",
  "feedback_listing": [
    {
      "id": 9, "training_id": 45, "participant_id": 88, "feedback_form_id": 3,
      "responses": { "q1": "5", "q2": "Great session" },
      "emp_id": 88, "first_name": "John", "last_name": "Doe",
      "position_title": "Sheaf", "profile_picture": "https://..."
    }
  ]
}
```
Empty array (not an error) if nobody's submitted feedback for that session
yet.

### POST `learning/participant-evaluation-from-list`

Same gate/body/shape as `participant-feedback-from-list` above, evaluation
form's equivalent:
```json
{
  "success": true,
  "message": "Evaluation data retrieved successfully",
  "evaluation_listing": [
    {
      "id": 4, "training_id": 45, "participant_id": 88, "evaluation_form_id": 2,
      "responses": { "date-123": "2026-08-30", "select-456": "option-3" },
      "emp_id": 88, "first_name": "John", "last_name": "Doe",
      "position_title": "Sheaf", "profile_picture": "https://..."
    }
  ]
}
```
Empty array (not an error) if nobody's submitted an evaluation for that
session yet.
