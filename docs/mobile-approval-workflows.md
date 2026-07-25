# Approval Workflow Documentation — Leave & Island Pass ("Exit Pass")

For the mobile team. Covers how approvals are assigned, how status
progresses, and the exact API request/response shapes for both workflows.

Note: there is no separate "Exit Pass" feature in the backend — mobile's
"Exit Pass" is this app's **Island Pass / Boarding Pass** feature. Same
tables, same endpoints, covered in full below.

## Shared pattern (both workflows)

Every approval request has two tables:
- **Parent row** — one per request (`employees_leaves` / `employee_travel_passes`).
  Its `status` column is coarse: `Pending` → `Approved` / `Rejected`. It stays
  `"Pending"` through the **entire multi-stage chain** and only flips once
  every stage has approved (or immediately to `Rejected` on any single reject).
- **Per-stage status rows** — one per approver
  (`employees_leaves_status` / `employee_travel_pass_status`), each with
  `approver_id`, `approver_rank`, `status` (`Pending`/`Approved`/`Rejected`),
  `comments`, `approved_at`.

Approvers are assigned **once, at submission time**, by looking up
department + rank (Island Pass) or the employee's own rank (Leave) — not by
following `employees.reporting_to` at approval time. Approving/rejecting
always acts on the current pending stage row, validates the logged-in user
is that row's `approver_id`, then re-checks whether all stages are approved
to decide whether to flip the parent.

**Implication for the UI:** don't rely on the parent `status` alone to show
"which stage are we at now" — it only ever says Pending/Approved/Rejected.
To show "Pending by HR" etc., inspect the per-stage array for the first row
still `Pending`, in submission order.

---

## 1. Leave Workflow

Controller: `app/Http/Controllers/API/LeaveController.php`

### Approver assignment (rank cascade, decided at submission)

Based on the **applicant's own rank**, not their manager chain:

| Applicant rank | Approval chain |
|---|---|
| 5/6 (Supervisor/Line) | EXCOM (or HOD if no EXCOM) → HR |
| 4 (MGR) | EXCOM → HR |
| 2/8 (HOD/GM) | HR |
| 1/3 (EXCOM/HR) | GM |

Plus: a clinic-staff approver stage (rank `12`) is auto-injected for sick
leave ≥2 days, maternity leave ≥30 days, or the applicant's >15th single-day
sick leave in the year. Clinic staff can comment but **cannot reject** the
leave (a clinic-stage reject does not flip the parent to Rejected).

If the request also includes departure/arrival travel details, a linked
Island Pass (`employee_travel_passes` + `_status`, chain SM→HR→HOD) is
created alongside it, joined via `leave_request_id`.

### `POST resort/leave-add` — submit

Request:
```json
{
  "leave_category_id": [1],
  "from_date": ["2026-08-01"],
  "to_date": ["2026-08-03"],
  "reason": "Family event",
  "task_delegation": 42,
  "transportation": [1],
  "dept_date": "2026-08-01", "dept_time": "09:00", "dept_transportation": "Ferry", "dept_reason": "...",
  "arrival_date": "2026-08-03", "arrival_time": "17:00", "arrival_transportation": "Ferry", "arrival_reason": "..."
}
```
Response: `{"success": true, "message": "Leave application submitted successfully!"}`

### `POST resort/handle-leave-action` — approve/reject

Request: `{"leave_id": 123, "action": "Approved", "reason": null}`
(`reason` required when `action` is `"Rejected"`)

- Validates the caller is the `approver_id` on the current pending
  `employees_leaves_status` row — else `{"status": false, "message": "You cannot approve/reject this request. The request must first be approved by the {rank}."}`
- On approve: updates that stage row; if it was the last stage (or its
  `approver_rank` is `3`/HR or `8`/GM — terminal ranks), parent flips to
  `Approved`.
- On reject: parent flips to `Rejected` immediately, **unless** the
  rejecting stage is clinic staff (rank `12`).

Response: `{"status": true, "message": "Leave approved successfully."}` /
`{"status": true, "message": "Leave Rejected."}`

### `GET resort/view-leave-request/{leave_id}` / `employee-leave-request-view/{leave_id}` — view

Response: `{"success": true, "message": "Leave Details", "leave_request": {...}}`
with employee/leave/type/transportation/attachment fields flattened onto one
object — this endpoint does **not** return the per-stage array; it's for
detail display, not for deriving current stage.

### `POST resort/employee-leave-request-list` — approver's queue (HR/HOD)

Grouped by submission date, each leave carries its own stage array:
```json
{"leave_request": [
  {"applied_on": "2026-07-24", "leaves": [
    {
      "...leave fields...",
      "approve_data": [
        {"approver_rank": 2, "approver_id": 177, "status": "Approved", "rank_type": "HOD"},
        {"approver_rank": 3, "approver_id": 55,  "status": "Pending",  "rank_type": "HR"}
      ]
    }
  ]}
]}
```
**This `approve_data` array is how the mobile app should compute "current
pending stage"**: find the first entry with `status: "Pending"` (in
submission order) and show "Pending by {rank_type}". There is currently no
separate precomputed label field on this endpoint — the base row's
`approver_rank`/`approver_id` are deliberately unset to avoid the UI reading
those directly instead of the array.

### `GET resort/leave-dashboard` — own leave balances

Returns `leave_balances[]` (`{leave_category_id, leave_type, color,
carry_forward, carry_max, allocated_days, used_days, available_days}`),
`total_allocated_days`, `total_available_days`, `default_destination`.

---

## 2. Island Pass / Boarding Pass ("Exit Pass") Workflow

Controller: `app/Http/Controllers/API/BoardingPassController.php`

### Approver assignment (department + rank / position, decided at submission)

Fixed 3-stage chain, always in this order:

1. **HOD** — `Employee::where('rank', 2)->where('resort_id', ...)->where('Dept_id', $employee->Dept_id)->first()`
2. **HR** — first employee in `Common::getResortHrEmployeeIds($resort_id)`
3. **Security Manager** — resolved by `ResortPosition.position_title` (`'Security Manager'`/`'SM'`), not by the numeric rank config

(Internally the rows are inserted SM→HR→HOD and the approve action reads
the most-recently-inserted `Pending` row first — net effect is HOD approves
first, then HR, then Security Manager last. Don't rely on row insertion
order in the mobile app; use the `status`/`approver_rank` fields.)

### `POST boarding/boarding-pass-add` — submit

Request (need at least one full leg — departure or arrival — each requires
date+time+transportation+reason together):
```json
{
  "dept_date": "2026-08-01", "dept_time": "09:00", "dept_transportation": "Ferry", "dept_reason": "Home visit",
  "arrival_date": "2026-08-05", "arrival_time": "17:00", "arrival_transportation": "Ferry", "arrival_reason": "Returning"
}
```
Duplicate check only blocks against existing `Pending`/`Approved` passes —
a Rejected or Cancelled pass doesn't block resubmission.

### `POST boarding/boarding-pass-approve-action` — approve/reject

Request: `{"pass_id": 438, "action": "Approved", "reason": null, "arrival_time": null, "departure_time": null}`
(`reason` required when `action` is `"Rejected"`)

Same actor/stage validation as Leave. Response on approve:
```json
{"status": true, "isAssigned": true, "pass_status": "Pending", "all_approved": false, "message": "Boarding pass approved successfully."}
```
`pass_status` here is still the coarse parent status — use
`current_status_label` (below) instead for display.

### `GET boarding/boarding-emp-dashboard` — employee's own dashboard

```json
{"emp_boarding_data": {
  "departed_count": 2, "arrived_count": 2,
  "current_year_departed_count": 1, "current_year_arrived_count": 1,
  "emp_req_data": [
    {
      "id": 438, "status": "Pending",
      "current_status_label": "Pending by HR",
      "employeeTravelPassStatusData": [
        {"approver_rank": 2, "rank_type": "HOD", "status": "Approved", "approver_id": 177},
        {"approver_rank": 3, "rank_type": "HR",  "status": "Pending",  "approver_id": 55}
      ]
    }
  ]
}}
```
`current_status_label` was just added (2026-07-25) specifically so mobile
doesn't have to derive the current stage from
`employeeTravelPassStatusData` itself — it's `"Pending by {rank}"` while
any stage is outstanding, or the parent's terminal value
(`"Approved"`/`"Rejected"`) once resolved. **Use this field directly** on
the dashboard instead of the raw `status` column.

### `GET boarding/boarding-pass-view/{pass_id}` — single pass detail

```json
{
  "success": true,
  "borading_pass_details": {"...pass row...", "employeeTravelPassStatusData": [...]},
  "view_display": {
    "employee": {"name": "...", "designation": "...", "profile_picture": "...", "status": "Pending"},
    "travel_segments": [{"segment_type": "departure", "label": "Ferry", "departure_date": "2026-08-01", "departure_time": "09:00", "departure_date_display": "01 Aug 2026, Sat"}],
    "reason": "..."
  }
}
```
Note: the response key is `borading_pass_details` (typo, preserved in the
live API — don't "fix" it client-side without a backend key rename first).

### Role dashboards — `boarding-hod-dashboard` / `boarding-hr-dashboard` / `boarding-sm-dashboard`

Same shape for all three, key `emp_boarding_data`:
```json
{"emp_boarding_data": {
  "pending_count": 2, "approved_count": 5, "rejected_count": 1, "emergency_cancel_count": "0",
  "employees_outside": 3, "scheduled_arrivals": 1,
  "total_exit_entry_current_year": [{"employee_id": 1, "first_name": "..", "departures": 2, "arrivals": 2, "current_year": 2026}],
  "pass_request": [{"...pass row...", "employeeTravelPassStatusData": [...]}]
}}
```
`pass_request` on each dashboard is pre-filtered to passes where **that
role's own stage row** is currently `Pending` — i.e. the HOD dashboard only
lists passes actually awaiting HOD action right now, HR only lists what's
awaiting HR, etc. HOD's list is further scoped to their own subordinates;
HR/SM dashboards are resort-wide.

---

## 3. Island Pass Manifest Workflow (Arrival & Departure)

A manifest groups multiple employees (+ optional visitors) travelling on
the same transport at the same date/time, for HR/Security to confirm as a
batch — separate from the per-employee Island Pass approval chain above.

### Access control

All manifest endpoints below (except `manifest-schedule`, see note) require
the caller to be **HR HOD/EXCOM** (department = Human Resources, rank 1 or
2) or **any Security department employee** (any rank). Everyone else gets:
```json
{"success": false, "message": "You are not authorized to manage manifests."}
```
with HTTP 403.

### Step 1 — Build the employee picker

`POST boarding/transportation-date-emp`
```json
{"transportation_id": 3, "date": "2026-08-01", "type": "departure"}
```
Returns only employees whose Island Pass is **fully approved** (parent
`status == "Approved"`) for that transport mode + date + direction:
```json
{"success": true, "emp_list": [
  {"id": 189, "first_name": "...", "last_name": "...", "full_name": "...",
   "profile_picture": "https://.../photo.jpg", "pass_type": "departure", "date": "2026-08-01"}
]}
```

### Step 2 — Create the manifest

`POST boarding/manifest-store`
```json
{
  "manifest_type": "departure",
  "transportation_mode": "3",
  "transportation_name": "MV Island Express",
  "date": "2026-08-01",
  "time": "10:00",
  "employee_ids": [189, 201],
  "visitors": ["Jane Visitor"]
}
```
- Blocks duplicate creation (same type + transport mode + transport name + date already exists).
- On success, every listed employee's Island Pass gets its `departure_time`/`arrival_time` set to match, and gets `manifest_id` stamped (see below) — scoped to only this resort and only these employees (not every pass matching the date resort-wide).
- Sends a notification to the included employees, and a **separate** notification to all HR + all Security department employees.

Response:
```json
{"success": true, "message": "Manifest created successfully", "data": {"id": 20, "manifest_type": "departure", "...": "...", "employees": [...], "visitors": [...]}}
```

### Island Pass reflects manifest assignment

`employee_travel_passes.manifest_id` (new column) is set once a pass is
attached to a manifest, `null` until then. It surfaces on the existing pass
endpoints (`boarding-pass-view`, `boarding-emp-dashboard`, etc.) alongside
the other pass fields — no separate lookup needed to tell whether a given
Island Pass has been manifested yet.

### Listing / viewing manifests

- `POST boarding/manifest-listing` — `{"manifest_type": "departure"}` → all `status: "saved"` manifests of that type for the resort.
- `GET boarding/manifest-details/{base64_manifest_id}` — full manifest with attached employees (photo/name/position) + visitors. Manifest id is base64-encoded in the URL.
- `GET boarding/manifest-details-pdf/{base64_manifest_id}` — same data, PDF-formatted.
- `POST boarding/pass-time-update` — `{"pass_id": 438, "departure_time": "10:00"}` — manual correction to one pass's time outside the manifest flow. HR/Security-gated like the rest.

### Not gated — different purpose

`GET boarding/manifest-schedule` (`manifestScheduleLookup`) is **not**
restricted to HR/Security — it's used by regular employees on the "Apply
Leave" / Departure Pass screen to auto-populate their own departure/arrival
time from an existing manifest's schedule, not to manage manifests:
```json
{"manifest_type": "departure", "transportation_mode": "3", "date": "2026-08-01"}
```
→ `{"success": true, "data": [{"id": 20, "transportation_mode": "3", "transportation_name": "MV Island Express", "date": "2026-08-01", "time": "10:00"}]}`

### Rank config reference — `config/settings.Position_Rank`

```php
['8' => 'GM', '1' => 'EXCOM', '2' => 'HOD', '3' => 'HR', '4' => 'MGR', '5' => 'SUP', '6' => 'LINE WORKERS', '7' => 'Finance']
```
`config('settings.final_rank')`: `{'3' => 'HR', '7' => 'Finance', '8' => 'GM'}`
— these are the "terminal approver" ranks whose approval alone can close
out a request early, independent of remaining stages.
