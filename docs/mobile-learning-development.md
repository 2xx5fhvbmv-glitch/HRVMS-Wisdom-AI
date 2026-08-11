# Learning & Development — Employee Mobile API

For the mobile team. Controller: `app/Http/Controllers/API/LearningController.php`.
This module is already fully built on the backend — this doc is the
employee-facing integration reference.

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

## Manager/HOD-facing (same controller, not employee self-service)

`managerTrainingCalendar` (`POST learning/manager-training-calendar`) is the
resort-wide version of the calendar (every employee's sessions + approved
learning requests, not just the caller's own) — for a manager/HOD dashboard
view, not the individual employee screen.
