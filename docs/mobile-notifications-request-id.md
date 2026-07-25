# Notifications — object_id / request_id (app-wide)

For the mobile team. Ticket: "notifications only send page_id, no object id
of the request to fetch details — same for all other notifications."

## The field already existed — it just wasn't being populated

`resort_notifications.request_id` was already a real column, and
`Common::sendMobileNotification()` already had a `$request_id` parameter
(9th positional argument) that writes into it. The gap was purely at the
call sites: ~65 places across the whole app called this function passing
`null` literally instead of the actual record id.

Fixed **every** call site app-wide (boarding pass, leave, grievance, SOS,
clinic, accommodation/maintenance, salary advance/loan requests, file
sharing, talent acquisition, training, incidents, disciplinary, calendar,
announcements, onboarding, resignation, profile info-update, monthly
check-ins, KPI) — each now passes the id of the record the notification is
actually about.

## What mobile gets now

Every notification in the list (`GET .../notifications` or whichever
endpoint mobile uses to fetch them) now has a real `request_id`:

```json
{
  "id": 15011,
  "message": "A boarding pass request has been submitted by Rani Khan.",
  "module": "Boarding Pass",
  "page_id": "boarding-pass-request",
  "type": "Boarding Pass Request",
  "request_id": 505
}
```

**How to use it:** `page_id` tells you which screen to navigate to
(a string tag like `boarding-pass-request`, `leave-approved`,
`clinic-appointment-status`, `grievance-submission`, etc.); `request_id`
is the numeric id of the specific record on that screen (the boarding
pass id, leave id, appointment id, grievance id...). Combine both to
deep-link a tapped notification straight to the right record instead of
just the right screen.

## A few call sites deliberately left as `null`

A handful of notifications are genuinely not about a single record (broad
announcements/reminders with no natural single id) — these are documented
inline in the relevant module's own doc where applicable. If a specific
notification type still shows `request_id: null` and you expected an id,
flag which `type`/`page_id` it is and it can be checked individually.

Every changed call site was reviewed diff-by-diff (not just trusted from
a report) before committing, across several passes covering the full app.
