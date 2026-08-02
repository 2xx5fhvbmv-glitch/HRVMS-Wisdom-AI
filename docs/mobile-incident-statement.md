# Incident — Employee/Witness Statement Flow

For the mobile team. This is why the push notification currently leads
nowhere: the fetch endpoint the screen needs didn't exist. It exists now —
`GET incident/statement-request/{incident_id}`, added alongside this doc.

## The gap that was blocking you

1. HR clicks "Request Statement" on the web portal → `POST resort/incidents/request-statement` → notifies every involved employee + witness.
2. The push notification (see shape below) reaches the app.
3. **Nothing existed for the app to call next.** `incident/incident-detail/{id}` (the only other incident-fetch endpoint) is scoped to `created_by` — only the person who originally logged the incident can use it. An involved employee or witness is a different person entirely, so that call always failed silently for them.
4. The submit endpoint (`incident/incident-statement`) always existed and works fine — it just had nothing feeding it.

## Step 1 — Push notification received

Fired by `IncidentController::requestEmployeeStatements()` (web,
`app/Http/Controllers/Resorts/Incident/IncidentController.php:560-644`) via
`Common::sendMobileNotification(...)`. Payload shape (built in
`app/Helpers/Common.php:6776-6791` for this notification type):

```json
{
  "id": 123,
  "resortid": 26,
  "title": "Statement Requested",
  "message": "You are requested to provide a statement regarding an incident.",
  "status": "...",
  "module": "Incident",
  "page_id": "incident-notification",
  "request_id": 1,
  "sendto": "...",
  "created_at": "..."
}
```

`request_id` is the real `incidents.id` — that's what you pass to step 2.
There is no separate "statement_id" (no pending row is created at request
time — the `incident_employee_statements` row only gets created when the
employee actually submits).

## Step 2 — Fetch incident context (NEW)

`GET incident/statement-request/{incident_id}` (plain int id, `request_id`
from the push — base64 also accepted for consistency with
`incident-detail`, but not required).

Auth: `api` guard. Authorization: caller must be in the incident's
`involved_employees` list OR be a registered witness — same check
`provideStatement()` already uses, kept in sync on purpose so "can view"
and "can submit" never drift apart.

Response:
```json
{
  "success": true,
  "message": "Incident statement request fetched successfully",
  "data": {
    "incident": {
      "id": 1,
      "incident_id": "YOPN7093",
      "incident_name": "Test",
      "incident_date": "11 Dec 2025",
      "incident_time": "12:07:00",
      "location": "test",
      "category_name": "Workplace Injuries",
      "subcategory_name": "Slips, trips, and falls",
      "description": "test",
      "attachments": []
    },
    "your_role": "witness",
    "existing_statement": null,
    "already_submitted": false,
    "can_submit": true
  }
}
```
`your_role` is `"witness"` or `"involved_employee"` — use it to adjust
copy if you want ("Describe what you witnessed" vs "Describe what
happened"), not required. If the incident already has a statement from
this employee (re-opening after a partial flow, or checking status later),
`existing_statement` is the previously submitted text, `already_submitted`
is `true`, and `can_submit` is `false` — show it read-only instead of a
blank form. Unauthorized caller: `{"success": false, "message": "You are
not authorized to view this incident."}`, 200.

## Step 3 — Submit

`POST incident/incident-statement` (already existed, unchanged contract):
```
incident_id: 1              // required, must exist
statement: "..."             // required, free text
attatchements[]: <file>      // optional, jpeg/png/jpg/heic/heif/mp4/mov/doc/docx/pdf
```
(Note the field name really is `attatchements` — typo baked into the API,
not fixable without breaking whatever already calls it.)

Response:
```json
{
  "status": true,
  "message": "Your statement for incident #YOPN7093 has been successfully submitted",
  "incident_data": {
    "employee_statement": "Employee statement given successfully",
    "incident": {"id": 1, "incident_name": "Test", "incident_date": "11 Dec 2025"}
  }
}
```
(`witness_statement` instead of `employee_statement` if the caller is a
witness, not involved-employee; both keys present if somehow both.)
Not authorized: `{"success": false, "message": "You are not authorized to
submit a statement for this incident."}`, 200.

## Also fixed alongside this

Attachment storage on submit was writing to a raw filesystem path that
never touched the actual configured storage disk (same root-cause class as
the earlier accommodation-attachments fix) — any photo/document attached to
a statement was silently unviewable afterward on the HR investigation page.
Fixed to go through the app's real storage disk; verified end-to-end with a
real upload + fetch-back.
