# Performance Management — Mobile API

For the mobile team. Ticket asked for: appraisal form listing with status,
open/complete/submit forms, notification on assignment, and performance
meeting calendar sync.

## Finding: this is already fully built and working

All 4 items in the ticket already exist and were verified against real data
(resort 26, employee id 202) — no backend gap found. Controller:
`app/Http/Controllers/API/PerformanceReviewController.php`.

If mobile doesn't have a Performance Management screen yet, that's a
Flutter-side build against these existing endpoints, not a missing backend
API — this doc gives you the verified real response shapes to build against.

---

## 1. `GET performance/my-reviews` — listing with status

Every appraisal assigned to the employee, both as the person being reviewed
("self") and as a reporting manager doing someone else's review ("manager").

**Real verified response** (employee 202, resort 26):
```json
{
  "success": true,
  "data": [
    {
      "id": 38,
      "role": "self",
      "cycle_name": "Final New Test Cycle",
      "cycle_start": "2026-05-25",
      "cycle_end": "2026-05-25",
      "status": "Submitted",
      "submitted_at": "2026-05-25",
      "window": { "open": false, "message": "Review window closed on 25 May 2026", "status": "closed" }
    },
    {
      "id": 12,
      "role": "self",
      "cycle_name": "90-Day Performance Review Cycle",
      "cycle_start": "2026-04-25",
      "cycle_end": "2026-04-26",
      "status": "Pending Action",
      "submitted_at": null,
      "window": { "open": false, "message": "Review window closed on 30 Apr 2026", "status": "closed" }
    },
    {
      "id": 32,
      "role": "manager",
      "cycle_name": "Ram's Probation Review",
      "cycle_start": "2026-05-21",
      "cycle_end": "2026-05-22",
      "status": "Waiting for Self Review",
      "submitted_at": null,
      "window": { "open": false, "message": "Review window closed on 22 May 2026", "status": "closed" }
    }
  ]
}
```

**Status values:**
- `role: "self"` → `"Submitted"` or `"Pending Action"`.
- `role: "manager"` → `"Waiting for Self Review"` (employee hasn't submitted
  their self review yet — manager can't act), `"Pending Action"` (self
  review done, manager review is due), or `"Submitted"`.

`window` tells you whether the review period is currently open
(`status: "open"|"upcoming"|"closed"|"no_window"`) — use this to
show/hide the submit action even if status is "Pending Action" (a
past-due cycle can still be listed as pending but no longer accept
submissions).

---

## 2. `GET performance/review/{id}` — open a form

Returns the form template (question structure) plus any already-saved
answers, with role-based field gating already applied (`_readonly`/
`_assigned_to` on each field — some questions are self-only, some
manager-only).

**Real verified response** (id 12, a cycle with no template configured —
legitimate empty state, not a bug):
```json
{
  "success": true,
  "data": {
    "id": 12,
    "role": "self",
    "cycle_name": "90-Day Performance Review Cycle",
    "template": null,
    "existing_data": [],
    "self_review_data": [],
    "self_review_status": "pending",
    "manager_review_status": "pending",
    "window": { "open": false, "message": "Review window closed on 30 Apr 2026", "status": "closed" }
  }
}
```
When a template IS configured, `template` looks like:
```json
{
  "name": "90 Day Probation Form",
  "type": "90 Day",
  "structure": [
    {
      "name": "goals_achieved",
      "label": "Goals Achieved",
      "type": "textarea",
      "required": true,
      "responder_roles": ["Self"],
      "_readonly": false,
      "_assigned_to": ""
    },
    {
      "name": "manager_rating",
      "label": "Manager Rating",
      "type": "ratingTable",
      "required": true,
      "responder_roles": ["Manager"],
      "_readonly": true,
      "_assigned_to": "Manager"
    }
  ]
}
```
`_readonly: true` means the CURRENT viewer can't fill this field (it belongs
to the other role) — render it disabled/hidden, don't just rely on the
client to know.

---

## 3. `POST performance/review/{id}/submit` — submit

Send the answered fields as the request body (field `name` → value, matching
the template's `structure[].name`). Role (self vs manager) is auto-detected
server-side from who's assigned to this cycle — don't send a role field.

- Rejects submission if the review window is closed (`422`, message =
  the window's `message` string).
- Manager submission is blocked until the self review is `completed`
  (`422`, `"The employee has not completed their self review yet."`).
- Any field the caller isn't allowed to fill (per `responder_roles`) is
  rejected if it has a real value: `422`,
  `"You are not authorised to fill these fields: {labels}"`.
- Missing required fields: `422`,
  `{"success": false, "message": "Please fill all required fields", "errors": {"field_name": "Label is required."}}`.

**Response (success):**
```json
{ "success": true, "message": "Self review submitted successfully." }
```
(or `"Manager review submitted successfully."`)

---

## 4. Notification on assignment — already wired

When HR creates a new review cycle (`CycleController::CycleStore()`, web
side), it notifies:
- Every participant: title `"New Performance Review Assigned"`, module
  `Performance`, `request_id` = the child-cycle row id, pageId
  `appraisal-self-review-assigned`.
- Every participant's manager: title `"Team Performance Review Assigned"`,
  pageId `appraisal-team-review-assigned`.

Submission itself also notifies onward (self review completed → notifies
the manager + HR; manager review completed → notifies the employee + HR) —
see `submitSelfReview()`/`submitManagerReview()` in the controller for the
exact wording per stage.

---

## 5. Performance meeting → calendar sync — already wired

When a performance meeting is scheduled (web side,
`PerformanceMeetingController`), it creates one shared `Events` row (the
same table the mobile Calendar module reads) plus one `ChildEvents` row per
invited employee — tagged `category: "Performance Meeting"`. No separate
mobile endpoint is needed for this: an invited employee will see the
meeting show up in their existing Calendar screen automatically, alongside
any other calendar entries.

`peformance_meetings` table also has 12 real rows for resort 26 already —
if a specific employee doesn't see a meeting they expect, check whether
they're an actual `PerformanceMeetingParticipant` on that meeting id, and
that the meeting's `Events`/`ChildEvents` rows exist (should always be
created together, same transaction).
