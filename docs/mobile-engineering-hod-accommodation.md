# Engineering HOD — Accommodation Maintenance Mobile API

For the mobile team. Controller: `app/Http/Controllers/API/AccommodationController.php`.
This documents the existing, working Engineering HOD + Engineering
Employee accommodation-maintenance workflow — all endpoints below were
verified against real data (resort 26, Engineering HOD Emp 202 / DR-22).

## Flow overview

```
HR raises/escalates a maintenance request
        |
        v
Engineering HOD/XCOM dashboard  --assign-->  Engineering Employee
        |                                          |
        v                                          v
   tracks status                         accept -> complete (w/ remarks)
        |                                          |
        +------------------ complete, sent back to HR
```

## Engineering HOD / XCOM endpoints

| Endpoint | Method | Purpose |
|---|---|---|
| `accommodation/engi-department-hod-main-req-dashboard` | GET | Dashboard counts + recent requests for the HOD's department |
| `accommodation/engi-department-hod-main-req-list` | GET | Full list of maintenance requests routed to Engineering |
| `accommodation/engi-department-hod-under-emp` | GET | Employees under this HOD, for the assignment picker |
| `accommodation/engi-department-hod-assign-emp` | POST | Assign a request to one or more engineering employees |
| `accommodation/engi-department-hod-assign-req-list` | GET | Requests already assigned, with assignee + status |
| `accommodation/engi-department-hod-complete-sendto-hr` | POST | Mark a request complete and hand it back to HR |

### GET accommodation/engi-department-hod-main-req-dashboard

```json
{
  "status": true,
  "message": "Maintances Request retrieved successfully",
  "maintanace_request_data": {
    "maintanace_request": [
      { "id": 168, "resort_id": 26, "building_id": 18, "descriptionIssues": "...", "priority": "High", "Status": "pending", "date": "2026-04-..." }
    ]
  }
}
```

### GET accommodation/engi-department-hod-under-emp

```json
{
  "status": true,
  "message": "Employees retrieved successfully",
  "employee_list": [
    { "id": 203, "first_name": "Ram", "last_name": "Niketan" },
    { "id": 204, "first_name": "...", "last_name": "..." }
  ]
}
```

Use these ids as the assignee(s) for `engi-department-hod-assign-emp`.

## Engineering Employee endpoints

| Endpoint | Method | Purpose |
|---|---|---|
| `accommodation/engi-department-staff-main-req-dashboard` | GET | Tasks assigned to the logged-in engineering employee |
| `accommodation/engi-department-staff-accept-req` | POST | Accept an assigned task |
| `accommodation/engi-department-staff-complete-req` | POST | Complete a task (remarks/photos per the request body) |

## Verified against real data

All 3 HOD-tier endpoints above were called live as Emp 202 (DR-22, the
exact employee named in the "staff-accommodation-dashboard 500" bug
report) and returned correct data with no errors. `staff-accommodation-dashboard`
itself (a *different*, employee-self-service endpoint, not part of this
HOD flow) also returned correctly for this same employee locally —
see the separate reply on that ticket for why the reported 500 couldn't
be reproduced.

## What's genuinely missing (not built here)

- **Flutter screens** for all of the above — the backend is complete;
  nothing currently calls these endpoints from the app. This is the
  actual reason the workflow "doesn't exist" from a user's perspective.
- **HR maintenance request creation/tracking screens** — same story:
  `hr-maintenance-req-dashboard`, `hr-maintenance-req-action`,
  `hr-maintenance-req-sendto-staff-emp` all exist and work; no mobile
  screen calls them yet.
- **Housekeeping ↔ Benefit Grid integration** — genuinely disconnected.
  `resort_benifit_grids.housekeeping` is a single free-text/select
  string (a cleaning *frequency* like "Daily"/"Weekly", not a catalog
  of specific services), while the housekeeping schedule creation flow
  (`housekeeping-add-schedules`) takes a free-text `special_instructions`
  field with no link to that column at all. Before building this
  connection, need clarification on what "predefined housekeeping
  requests" should actually mean — a service checklist doesn't
  currently exist anywhere in the data model, so this may need a new
  field/table, not just wiring up an existing one.
