# Island Pass Manifest Workflow (Arrival & Departure)

For the mobile team. Covers the manifest batch-confirmation feature that
sits on top of the per-employee Island Pass approval chain — see
[`mobile-approval-workflows.md`](mobile-approval-workflows.md) section 2 for
how an individual Island Pass gets approved before it can be manifested.

A manifest groups multiple employees (+ optional visitors) travelling on
the same transport at the same date/time, for HR/Security to confirm as a
batch.

## Access control

All manifest endpoints below (except `manifest-schedule`, see note) require
the caller to be **HR HOD/EXCOM** (department = Human Resources, rank 1 or
2) or **any Security department employee** (any rank). Everyone else gets:
```json
{"success": false, "message": "You are not authorized to manage manifests."}
```
with HTTP 403.

## Step 1 — Build the employee picker

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

## Step 2 — Create the manifest

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

## Island Pass reflects manifest assignment

`employee_travel_passes.manifest_id` (new column) is set once a pass is
attached to a manifest, `null` until then. It surfaces on the existing pass
endpoints (`boarding-pass-view`, `boarding-emp-dashboard`, etc.) alongside
the other pass fields — no separate lookup needed to tell whether a given
Island Pass has been manifested yet.

## Listing / viewing manifests

- `POST boarding/manifest-listing` — `{"manifest_type": "departure"}` → all `status: "saved"` manifests of that type for the resort.
- `GET boarding/manifest-details/{base64_manifest_id}` — full manifest with attached employees (photo/name/position) + visitors. Manifest id is base64-encoded in the URL.
- `GET boarding/manifest-details-pdf/{base64_manifest_id}` — same data, PDF-formatted.
- `POST boarding/pass-time-update` — `{"pass_id": 438, "departure_time": "10:00"}` — manual correction to one pass's time outside the manifest flow. HR/Security-gated like the rest.

## Not gated — different purpose

`GET boarding/manifest-schedule` (`manifestScheduleLookup`) is **not**
restricted to HR/Security — it's used by regular employees on the "Apply
Leave" / Departure Pass screen to auto-populate their own departure/arrival
time from an existing manifest's schedule, not to manage manifests:
```json
{"manifest_type": "departure", "transportation_mode": "3", "date": "2026-08-01"}
```
→ `{"success": true, "data": [{"id": 20, "transportation_mode": "3", "transportation_name": "MV Island Express", "date": "2026-08-01", "time": "10:00"}]}`

## Rank config reference — `config/settings.Position_Rank`

```php
['8' => 'GM', '1' => 'EXCOM', '2' => 'HOD', '3' => 'HR', '4' => 'MGR', '5' => 'SUP', '6' => 'LINE WORKERS', '7' => 'Finance']
```
`config('settings.final_rank')`: `{'3' => 'HR', '7' => 'Finance', '8' => 'GM'}`
— these are the "terminal approver" ranks whose approval alone can close
out a request early, independent of remaining stages.
