# Grievance Module API

For the mobile team. Controller: `app/Http/Controllers/API/GrievanceController.php`.

## Terminology note: "Offense" vs "Subcategory"

These are the same field, not two separate ones. The column was
originally named `Grivance_offence_id` and was renamed to
`Grivance_Sub_cat` in `2025_04_17_164241_add_grivance_field.php`. The web
portal's grievance create form still has a leftover "SELECT OFFENSE" label
(`resources/views/resorts/GrievanceAndDisciplinery/grivance/create.blade.php:57`)
but its select2 targets `#Grivance_offence_id`, an id nothing in the DOM
binds to anymore — the real, working field on that form is
`#Grivance_Sub_cat` ("Select Grievance Subcategory"). There is no second,
distinct offense value anywhere in the schema for this module.

## POST grievance/grievance-store — submit a grievance

Multipart form (for `Attachments[]`).

| Field | Rule | Notes |
|---|---|---|
| `Grivance_Cat_id` | required | FK → `grievance_categories.id` |
| `Grivance_Sub_cat` | required | FK → `grievance_subcategories.id` ("Offense"/"Subcategory", see above) |
| `Employee_id` | required | The employee the grievance is **about**, not the submitter. Submitter is `created_by`, taken from the authenticated user automatically. |
| `date` | required, date | |
| `Grivance_description` | required | Main description |
| `Grivance_date_time` | required, `Y-m-d H:i:s` | When the incident occurred |
| `location` | required | |
| `witness_id[]` | required, array of employee ids | Creates one `grivance_submission_witnesses` row per id |
| `Grivance_Eexplination_description` | required | The "what resolution are you asking for" field |
| `Confidential` | required, `option1`\|`option2`\|other | `option1` → stored as `Grivance_Submission_Type = "Yes"`; `option2` (checked via a separate `Anonymous` field, not `Confidential` — see code note below) → `"No"`; anything else → `"NotApplicable"` |
| `grievance_informally` | required, `Yes`\|`No` | Whether the employee already tried informal resolution before filing this formal grievance |
| `Attachments[]` | optional, files | Uploaded via `Common::AWSEmployeeFileUpload`, stored as JSON `[{Filename, Child_id}]` on `Attachements` (column name is misspelled in the DB, not a typo here) |

**Code quirk worth knowing:** the confidential/anonymous logic checks
`$request->Confidential == "option1"` first, then `$request->Anonymous ==
"option2"` (a *different* field name) as the elseif — so to get `"No"`
(anonymous) you must send a field literally named `Anonymous=option2`, not
`Confidential=option2`. Sending `Confidential=option2` (or anything besides
exactly `option1`) with no `Anonymous` field falls through to
`"NotApplicable"`.

Response: `{"status": true, "message": "Grievance Created Successfully"}`

Every field submitted here is now returned by the GET endpoints below —
see the field-by-field mapping.

## POST grievance/informal-resolution — standalone pre-check

Separate from `grievance_informally` above. This is for the "did you try
informal resolution first?" dialog shown **before** the employee decides
whether to file a formal grievance at all — if they answer "Yes" and never
file one, this is the only record of that attempt.

```json
{"resolved_informally": "Yes", "description": "Spoke to my supervisor directly"}
```
Stored in `grievance_informal_resolutions` (no FK to a specific grievance —
it's independent, since it may precede a grievance that's never filed).
Response: `{"success": true, "message": "Response recorded successfully.", "data": {...}}`

## GET grievance/my-grievances — listing

Now returns everything submitted, not just id/category/status:
```json
{"status": true, "data": [
  {
    "id": 40, "grievance_id": "GR-0002",
    "category": "Harassment & Misconduct",
    "subcategory": "Sexual Harassment",
    "employee": {"id": 177, "name": "Priya Sharma", "department": "F and B Service", "position": "Food And Beverage Manager"},
    "supervisor": {"id": 176, "name": "Rajesh Patel"},
    "submitted_on": "2026-07-11",
    "status": "pending",
    "location": "jetty",
    "description": "...",
    "confidential": "NotApplicable",
    "resolved_informally": "No",
    "witness_count": 1,
    "has_attachments": false
  }
]}
```
`employee`/`supervisor` are `null` if the employee record can't be resolved.

## GET grievance/{id} — detail

Same fields as listing, plus the full witness/attachment/outcome data:
```json
{"status": true, "data": {
  "id": 40, "grievance_id": "GR-0002",
  "category": "...", "subcategory": "...",
  "employee": {...}, "supervisor": {...},
  "submitted_on": "...", "status": "pending",
  "incident_datetime": "2026-07-11 09:54:00",
  "location": "...", "description": "...", "explanation": "...",
  "confidential": "...", "resolved_informally": "No",
  "witnesses": [{"employee_id": 199, "name": "...", "statement": null}],
  "attachments": [{"filename": "...", "url": "..."}],
  "has_attachments": true,
  "outcome_type": null, "action_taken": null,
  "gm_decision": null, "gm_reason": null, "rejection_reason": null
}}
```
`outcome_type`/`action_taken`/`gm_decision`/`gm_reason`/`rejection_reason`
are only populated once HR/GM has completed the review — `null` until then.
