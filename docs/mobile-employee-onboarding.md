# Employee Onboarding API

For the mobile team — covers the "On Boarding" Figma set (dashboard,
Employee-Accommodation, Employee-Key-Contacts, Acknowledge, Employee
Handbook, Harassment Prevention, Send Selfie, Employee-Onboarding
itinerary, Access Details, Assigned-Staff dashboard, Scheduled Tasks
calendar, Virtual Facility Tour, Hotel Details, Cultural Insights,
Meetings). Already built and live on `demo`/`master`; this documents it.

Controller: `app/Http/Controllers/API/OnBoardingController.php`. All routes
sit under `auth:api`. Everything below is scoped to **the caller's own**
employee/itinerary record unless the section says otherwise (Assigned-Staff
and HR screens are the exceptions).

## Screen → endpoint map

| Figma screen | Endpoint |
|---|---|
| `dashboard` | `GET on-boarding/on-boarding-dashboard` |
| `Employee-accommodation` | `GET on-boarding/accommodation-detail` |
| `Employee-key-contacts` | `GET on-boarding/key-contacts` |
| `Acknowledge` | `GET on-boarding/acknowledgement-view-files`, `POST on-boarding/store-acknowledgement` |
| `employee-handbook` | `GET on-boarding/employee-handbook`, `GET on-boarding/policy-content/{type}` |
| `Harassment-prevention` | `GET on-boarding/harassment-prevention-content` |
| `send-my-selfie` | `POST on-boarding/send-selfie-image` |
| `Employee-Onboarding` (itinerary, Day 1/2/3) | `GET on-boarding/itinerary-timeline` |
| `Access-Details` | `GET on-boarding/access-details` |
| `Assigned-Staff` dashboard (incl. Rejection Reason modal) | `GET on-boarding/assigned-staff-dashboard`, `POST on-boarding/task-action` |
| `Scheduled-tasks` calendar | `POST on-boarding/schedule-task-calendar` |
| `Virtual Facility Tour` | `GET on-boarding/get-onboarding-virtual-facility` |
| `Hotel-details` | `GET on-boarding/hotel-details` |
| `Cultural-insights` | `GET on-boarding/cultural-insights` |
| `Meetings` (gm-meeting-schedule) | `GET on-boarding/meetings`, `POST on-boarding/meetings/add-link` |

## GET on-boarding/on-boarding-dashboard

The combined payload — useful for a single-fetch dashboard, but every
section below also has its own standalone endpoint so a sub-screen doesn't
have to pull the whole thing. 404-shaped `{"status": false, "message":
"Onboarding data not found"}` if the employee has no itinerary at all yet.
Returns the full `employee_itineraries` row plus:
```json
{
  "success": true,
  "on_boarding_data": {
    "...": "raw employee_itineraries columns (arrival_date, hotel_name, etc.)",
    "pickup_employee_name": "George Franklin",
    "pickup_employee_phone": "...",
    "pickup_employee_profile": "https://...",
    "pick_up_view_selfie_image": "https://...",
    "medical_employee_name": "...",
    "medical_employee_profile": "https://...",
    "accommodation_details": { "...": "see accommodation-detail below" },
    "key_contacts": { "...": "see key-contacts below" },
    "cultural_insights": "...",
    "itinerary_template": { "id": 3, "name": "...", "fields": "..." },
    "meeting_schedule": [ { "id": 5, "meeting_date": "...", "meeting_link": "..." } ]
  }
}
```

## GET on-boarding/accommodation-detail

```json
{
  "success": true,
  "data": {
    "building_name": "A Building",
    "room_no": "...",
    "bed_no": "...",
    "accommodation_type": "Shared",
    "accommodation_shared_people_count": 2,
    "accommodation_shared_people": [ { "first_name": "Christian", "last_name": "Slatter", "profileImg": "https://..." } ],
    "accommodation_inventory_items": [ { "ItemName": "..." } ]
  }
}
```
`accommodation_shared_people` is only populated when the accommodation type
is Shared (`Accommodation_type_id == 3`) — empty array otherwise.

## GET on-boarding/key-contacts

```json
{
  "success": true,
  "data": {
    "members_in_your_department": [
      { "id": 205, "first_name": "Michael", "last_name": "Chen", "personal_phone": "...", "profile_picture": "https://...", "position_title": "...", "department_name": "..." }
    ],
    "hod_of_your_division": [
      { "id": 177, "first_name": "John", "last_name": "Doe", "profile_picture": "https://...", "position_title": "HOD" }
    ]
  }
}
```
`hod_of_your_division` includes both HOD (rank 2) and EXCOM (rank 1) —
covers a division headed by an EXCOM with no dedicated HOD.

## Acknowledge screen

Four fixed items, each with a checkbox + date + "View File" link:
`Contract Signed`, `Benefit Grid Received`, `Employee Handbook Received and
Acknowledged`, `Disciplinary Process`.

**GET on-boarding/acknowledgement-view-files** — populates the screen (view
files already on record, if any):
```json
{
  "success": true,
  "acknowledgement_files": [
    { "acknowledgement_type": "Contract Signed", "status": "Yes", "acknowledged_date": "2026-01-05", "view_file_url": "https://..." },
    { "acknowledgement_type": "Benefit Grid Received", "status": "No", "acknowledged_date": null, "view_file_url": null }
  ]
}
```
`view_file_url` resolves from that employee's tagged document upload
(existing generic `employee-document` upload endpoint, `document_category`
= `Contract`/`Benefit Grid`/`Employee Handbook`/`Disciplinary Process`) —
there's no separate upload endpoint for this screen, HR/the employee
uploads through the existing document flow and it shows up here once
tagged.

**POST on-boarding/store-acknowledgement** — the Submit button. Body:
```json
{ "acknowledgements": [
  { "acknowledgement_type": "Contract Signed", "acknowledged_date": "2026-01-05", "status": "Yes" }
] }
```
`acknowledgement_type` must exactly match one of the 4 strings above.
Re-submitting the same type+date is a no-op (reported back in the response's
`duplicates` array), not an error.

## Employee Handbook

**GET on-boarding/employee-handbook** — the category list screen (Code of
Conduct, Non-Discrimination Policy, Harassment Prevention, Job Roles &
Responsibilities, Safety & Emergency Procedures, Access Details, Cultural
Insights):
```json
{ "success": true, "data": [
  { "content_type": "code_of_conduct", "title": "Code of Conduct", "has_content": true },
  { "content_type": "non_discrimination", "title": "Non-Discrimination Policy", "has_content": false }
] }
```
`has_content: false` means HR hasn't written that policy yet — grey it out
rather than opening to a blank screen.

**GET on-boarding/policy-content/{type}** — tapping into any category
(`type` = one of the `content_type` values above):
```json
{ "success": true, "data": { "content_type": "code_of_conduct", "title": "Code of Conduct", "content": "..." } }
```

## GET on-boarding/harassment-prevention-content

Same shape as `policy-content/harassment_prevention` — kept as its own named
route since the Figma set has it as a distinct top-level screen, not just a
handbook sub-item:
```json
{ "success": true, "data": { "title": "Harassment Prevention Policy", "content": "..." } }
```
Acknowledging it uses the same `store-acknowledgement` endpoint above —
there's no separate acknowledge-harassment endpoint.

## POST on-boarding/send-selfie-image

Multipart. Field: `selfie_image` (file — jpeg/png/jpg/gif/svg/webp/heic/heif).
```json
{ "success": true, "message": "Selfie image uploaded successfully.", "selfie_data": { "selfie_image": "" } }
```
Note: `selfie_data.selfie_image` currently comes back empty — the uploaded
file is saved (confirm via `on-boarding-dashboard`'s
`pick_up_view_selfie_image` or re-fetching the profile), but this endpoint's
own response doesn't yet echo the resolved URL. Flag if the UI needs it
inline rather than re-fetching.

## GET on-boarding/itinerary-timeline

The Employee-Onboarding "Day 1 / Day 2 / Day 3" screen.
```json
{
  "success": true,
  "data": {
    "days": [
      { "day": "Day 1", "date": "2026-01-05", "items": [
        { "date": "2026-01-05", "time": "18:30", "title": "Airport Pickup", "status": "Pending", "download_pdf": "https://..." },
        { "date": "2026-01-05", "time": "18:30", "title": "Hotel Drop (Grand Tourist)", "status": "Pending", "download_pdf": null }
      ] },
      { "day": "Day 2", "date": "2026-01-06", "items": [
        { "date": "2026-01-06", "time": null, "title": "Work Permit Medical (...)", "status": "Pending", "download_pdf": null }
      ] }
    ],
    "upcoming_tasks": [ "...same item shape, this-week only, not-yet-Completed" ],
    "assigned_tasks": [
      { "task_type": "pickup", "title": "Pick up at the Airport", "assigned_to": "George Franklin", "status": "Pending" },
      { "task_type": "medical", "title": "Escort to Medical Center", "assigned_to": "...", "status": "Pending" }
    ],
    "download_all": [ { "label": "Entry Pass", "url": "https://..." }, { "label": "Flight Ticket", "url": "https://..." } ]
  }
}
```
`status` on pickup/medical items is the real, settable status
(`pickup_status`/`medical_escort_status`, changed via `task-action` below).
Every other item type (hotel drop, domestic flight, speedboat, seaplane,
meetings) has no real completion column — `status` there is a naive
date-passed heuristic (`Completed` once the date is in the past,
`Pending` otherwise). If the business ever needs an explicit "mark hotel
drop done" action, that needs a new column — not built now, flagging rather
than guessing at a fake completion flow.

## GET on-boarding/access-details

```json
{ "success": true, "data": {
  "email": "johndoe@email.com", "passport_number": "...", "access_code": "...",
  "title": "Access Details", "content": "..."
} }
```
`access_code` is resort-wide (shared door/gate code), not per-employee.

## GET on-boarding/hotel-details

```json
{ "success": true, "data": {
  "hotel_name": "Grand Tourist", "hotel_contact_no": "...", "hotel_address": "...",
  "booking_reference": "...", "medical_center_name": "...", "medical_center_contact_no": "...", "medical_type": "..."
} }
```

## GET on-boarding/cultural-insights

```json
{ "success": true, "data": { "cultural_insights": "...", "download_all": [ { "label": "Entry Pass", "url": "https://..." } ] } }
```
The "Download All Itinerary PDF" button on this screen works here too
(same data as `itinerary-timeline`'s `download_all`) since the Figma shows
it living on this page as well as the itinerary screen.

## Virtual Facility Tour

**GET on-boarding/get-onboarding-virtual-facility**:
```json
{ "success": true, "facility_tour_categories_image": [
  { "id": 1, "name": "Resort Overview", "thumbnail_image_path": {"...": "GetAWSFile result"},
    "facility_tour_images": [ { "id": 4, "imagePath": {"...": "GetAWSFile result"} } ] }
] }
```
`thumbnail_image_path`/`imagePath` are the raw `Common::GetAWSFile()`
return shape (an object with `success`/`NewURLshow`, not a bare URL string)
— read `.NewURLshow` off each, not the field directly.

## Assigned-Staff screens (pickup/medical-escort staff, not the arriving employee)

**GET on-boarding/assigned-staff-dashboard** — every task assigned to the
caller as pickup or medical escort, across all itineraries:
```json
{ "success": true, "tasks": [
  { "itinerary_id": 12, "task_type": "pickup", "status": "Pending", "type": "Pick up at the Airport",
    "name": "Christian Slatter", "profile_picture": "https://...", "date": "2026-01-05", "time": "18:30",
    "view_selfie": "https://...", "download_pdf": [ {"label":"Entry Pass","url":"..."} ] },
  { "itinerary_id": 13, "task_type": "medical", "status": "Pending", "type": "City Clinic To Medical Center",
    "name": "...", "date": "2026-01-08", "time": "10:00", "location": "City Clinic" }
], "upcoming_tasks": [ "...same shape, this-week only" ] }
```

**POST on-boarding/task-action** — Accept / Reject (with the Rejection
Reason modal — pass it as `reason` if you want it stored; not currently a
required field, see note below) / Complete:
```json
{ "itinerary_id": 12, "task_type": "pickup", "action": "reject" }
```
`action` is `approve`\|`reject`\|`complete`. 403 if the task isn't actually
assigned to the caller (checked against `pickup_employee_id`/
`accompany_medical_employee_id`, not just itinerary ownership).
```json
{ "success": true, "data": { "itinerary_id": 12, "task_type": "pickup", "status": "Rejected" } }
```
**Note**: the Figma's Rejection Reason modal has a free-text field, but
`taskAction()` doesn't currently accept/store a reason string — only the
status flips. If the reason needs to persist (visible to HR later), that's
a small follow-up (one column + one validator line), flag if needed.

## POST on-boarding/schedule-task-calendar

Body (both optional, defaults to current month): `{"year": "2026",
"month": "01"}`. Same ownership scope as the Assigned-Staff dashboard —
only the caller's own pickup/medical tasks for that month.
```json
{ "success": true, "data": [
  { "type": "Pick up", "name": "Christian Slatter", "time": "18:30", "date": "2026-01-08", "profile_picture": "https://..." }
] }
```

## Meetings

**GET on-boarding/meetings** — scoped to meetings the caller has a real
stake in (their own itinerary, assigned as pickup/medical staff, or listed
as a participant) — never a resort-wide unfiltered list:
```json
{ "success": true, "data": [
  { "id": 5, "itinerary_id": 12, "title": "Zoom Meeting with EXCOM", "date": "2026-01-05", "time": "18:30",
    "meeting_link": "https://meet.google.com/landing", "participants": [ { "id": 205, "name": "..." } ] }
] }
```

**POST on-boarding/meetings/add-link** — Body: `{"meeting_id": 5,
"meeting_link": "https://..."}`. 403 if the caller has no stake in that
meeting.
```json
{ "success": true, "data": { "id": 5, "meeting_link": "https://..." } }
```

## Known gaps (flagging, not guessing)

- `send-selfie-image`'s own response doesn't echo the resolved image URL — re-fetch the dashboard if you need it immediately after upload.
- Task rejection reason (the modal's free-text field) isn't persisted server-side yet.
- Non-pickup/medical itinerary items (hotel drop, flights, meetings) have no real "mark complete" action — their `status` is date-inferred only.
