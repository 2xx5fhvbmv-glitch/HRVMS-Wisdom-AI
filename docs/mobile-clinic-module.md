# Clinic Module API — Mobile Team Guide

Controller: `app/Http/Controllers/API/ClinicController.php`
Routes: `routes/api.php` (Clinic section, ~lines 390-412)

## Two role tiers

The Clinic module has two access tiers on the same controller:

1. **Employee Clinic APIs** — plain `auth:api` middleware. Any authenticated
   employee can call these (book an appointment, view their own history,
   etc.). Routes at `routes/api.php:405-412`.
2. **Clinic Manager APIs** — gated behind `['auth:api', 'check.rank:CLINIC_STAFF']`
   (`routes/api.php:391-401`). Only an employee whose `rank` resolves to
   `CLINIC_STAFF` via `config('settings.Position_Rank')` can call these — this
   is the clinic doctor/nurse-facing side (dashboard, treatment records,
   medical certificates, leave sign-off).

`CheckUserRankForAPI` (`app/Http/Middleware/CheckUserRankForAPI.php`) is what
enforces tier 2: it loads `config('settings.Position_Rank')`, looks up the
caller's `rank` (or `main_rank`) in that map, uppercases it, and checks it
against the middleware's rank list (`CLINIC_STAFF` here). If the rank isn't
in the config map at all, the lookup resolves to `''` and the check always
fails — which is exactly what was broken until today.

### Two bugs fixed today — this module is now genuinely testable

1. **`config/settings.php` `Position_Rank` was missing `12 => 'CLINIC_STAFF'`.**
   Every Clinic Manager route (tier 2 above) depends on this mapping. With it
   missing, no employee — regardless of their `rank` column value — could
   ever pass `check.rank:CLINIC_STAFF`, so all 10 Clinic Manager endpoints
   were unreachable. This has been restored, so an employee with `rank = 12`
   can now reach them.
2. **`app/Models/ClinicAppointmentCategories.php`'s `boot()` hook never set
   `modified_by`** on create (only `created_by` was set). `modified_by` is
   `NOT NULL` with no default, so this silently stored `0` locally under a
   lenient `sql_mode` but would hard-fail on a stricter environment. Fixed —
   `modified_by` is now set alongside `created_by` on creation.

A migration (`2026_07_25_233803_seed_default_clinic_appointment_categories.php`)
also seeded 5 default appointment categories per resort: **General Checkup**
(`#2E86AB`), **Follow-up** (`#5CB85C`), **Emergency** (`#D9534F`),
**Vaccination** (`#F0AD4E`), **Medical Certificate** (`#9E5CF7`). Mobile
should expect `GET clinic/appointment-categories` to return these out of the
box on any resort, even before a Clinic Manager adds their own.

A real rank-12 test employee now exists locally (from
`2026_08_09_233000_seed_clinic_module_test_accounts.php`), so the Clinic
Manager endpoints are reachable today via the normal `POST login` flow with
that employee's `emp_id`/password — no separate setup needed for that path.

## Temporary (third-party/agency) doctor accounts — a second way into the Clinic Manager tier

Some resorts contract a clinic doctor from an outside agency rather than
employing one directly. That account is **not** an `Employee` row — it's a
standalone identity (`temporary_clinic_doctors` table /
`App\Models\TemporaryClinicDoctor`), created and managed entirely from the
web portal by HR (Clinic > Temporary Doctors). Mobile only needs to know how
it authenticates and what it can/can't do — HR password management, session
revocation, and account creation are all web-side, not mobile endpoints.

### Login — separate endpoint, separate guard

```
POST clinic-doctor/login
Body: { "email": "...", "password": "..." }
```
Same token mechanics as the regular `POST login` (`$model->createToken()->accessToken`
— no OAuth client/password-grant flow, use the returned `token` directly as
a Bearer token). Response also includes the account's granted capabilities
so the app can build its own UI without a separate permissions call:
```json
{
  "success": true,
  "message": "Login successful",
  "token": "...",
  "doctor": {
    "id": 3, "name": "Dr. Jane Doe", "email": "jane@agency.example", "agency_name": "Island Medical Partners",
    "permissions": {
      "can_view_appointments": true,
      "can_manage_treatment": true,
      "can_view_medical_history": false,
      "can_issue_medical_certificate": false
    }
  }
}
```
`POST clinic-doctor/logout` (Bearer token required) revokes the token, same
as the regular logout.

**Do not reuse the regular `POST login` endpoint for this account type** —
it looks up an `Employee` by `emp_id`, which this account will never have.

### What this account can reach

All of the existing Clinic Manager + a few Employee-tier "detail" endpoints
documented below are reachable by this identity too — same URLs, same
request/response shapes, no separate versions. Two exceptions, gated by two
new middlewares (`clinic.manager`, `clinic.capability:{flag}`):

| Endpoint | Reachable by a temporary doctor? |
|---|---|
| `clinic/appointment-categories` (read) | Always, regardless of capabilities |
| `clinic/clinic-staff-dashboard`, `clinic/appointment-list-based-filter`, `clinic/appointment-and-leave-list`, `clinic/appointment-details/{id}` | Only if `can_view_appointments` |
| `clinic/treatment-add`, `clinic/treatment-additional-note-update`, `clinic/treatment-details/{id}` | Only if `can_manage_treatment` |
| `clinic/medical-history-list`, `clinic/medical-history-details/{emp_id}` | Only if `can_view_medical_history` |
| `clinic/medical-certificate-store`, `clinic/medical-certificate-details/{id}` | Only if `can_issue_medical_certificate` |
| `clinic/appointment-categories-store` | **Never** — taxonomy/config is HR-only regardless of capabilities |
| `clinic/clinic-staff-leave-action` | **Never** — this account has no employee/approver identity, so it can never validly appear as `approver_id` in the leave chain |
| Every Employee-tier endpoint not listed above (`appointment-store`, `appointment-status-update`, `employee-clinic-dashboard`, `past-medical-history`) | Never — those are patient-facing; a doctor account isn't a patient |

A capability the account doesn't have returns:
```json
{ "success": false, "message": "Your account does not have access to this feature." }
```
HTTP 403. Calling one of the two fully-excluded endpoints returns a plain
`401 Unauthenticated` — those routes only accept the regular employee guard.

On `clinicStaffDashboard`/`appointmentAndLeaveList`, the leave-related
sub-fields (`medical_leave_requests_pending`, `medical_leave_requests_approved`,
`leave_request`) will always be `0`/empty for this account type, since it
has no employee id to match against `approver_id` — this is expected, not a
bug to report.

### General response-shape notes (apply across all 18 endpoints)

- **Most business-rule failures return HTTP 200 with `"success": false`** in
  the body, not a 4xx status — e.g. "Appointment not found", "Category
  already added", invalid status transitions. Only request-validation
  failures use `400` (`{"success": false, "errors": {...}}`, Laravel
  validator format) and missing/invalid auth uses `401`
  (`{"success": false, "message": "Unauthorized"}`). **Mobile should branch
  on the `success` boolean in the body, not the HTTP status code, for most
  of these endpoints.**
- Two endpoints (`medicalCertificateStore`'s early leave-lookup guard, and
  `clinicStaffLeaveAction`'s leave-not-found guard) break this pattern and
  return `{"status": "error", "message": "..."}` (key `status`, not
  `success`) — called out individually below.
- Push/in-app notifications go through `Common::sendMobileNotification($resortId, $type, ..., $title, $message, $module, $sendto, $request_id, $skipDbInsert, $pageId)`.
  The `$request_id` argument is generally the id of the record the
  notification is about (appointment id, certificate id, leave id) and
  `$pageId` is a string tag (e.g. `clinic-appointment-request`) mobile can use
  to route a notification tap to the right screen. Each entry in `$sendto`
  gets its own `ResortNotification` row.

---

## Employee Clinic APIs

*(all under plain `auth:api` — any authenticated employee)*

### GET `clinic/appointment-categories`

Fetch the list of appointment categories (types + colors) available for
booking, scoped to the caller's resort.

**Request:** no params.

**Controller:** `appointmentCategories()`. Query: `ClinicAppointmentCategories::where('resort_id', ...)->orderBy('created_at', 'desc')->get()`.

**Response:**
```json
{
  "success": true,
  "message": "Appointment Categories Fetched Successfully",
  "appointment_category_data": [
    {
      "id": 1,
      "resort_id": 3,
      "appointment_type": "General Checkup",
      "color": "#2E86AB",
      "created_by": 202,
      "modified_by": 202,
      "created_at": "2026-07-25T23:38:03.000000Z",
      "updated_at": "2026-07-25T23:38:03.000000Z"
    }
  ]
}
```

---

### POST `clinic/appointment-store`

Book a new appointment, or update/reschedule an existing one (dual-purpose
endpoint — presence of `appointment_id` in the payload decides the mode).

**Request body:**

| Field | Rule | Notes |
|---|---|---|
| `date` | required | Appointment date |
| `time` | required | Appointment time |
| `appointment_category_id` | required | FK → `clinic_appointment_categories.id` |
| `description` | optional | Free text |
| `appointment_id` | optional | If present → **update/reschedule** an existing appointment instead of creating a new one |
| `attachments[]` | optional, files | Only used on the **create** path (see quirk below) |

**Create path** (no `appointment_id`): looks up the resort's doctor via
`Employee::where('rank', 12)->first()` and uses that employee as
`doctor_id`. Blocks a duplicate: same employee + doctor + date + time +
category with `status = 'Pending'` already existing returns
`{"success": false, "message": "Appointment already exists for this date and time"}`.
Otherwise creates the `ClinicAppointment` with `status = 'Pending'`, uploads
any `attachments[]` via `Common::AWSEmployeeFileUpload` (subfolder
`IncidentAttatchements` — misspelling is in the live code, not a typo here)
and creates one `ClinicAppointmentAttachment` row per file.

**Update path** (`appointment_id` present): looks up the appointment by
`id` + `employee_id` (must belong to the calling employee) + `resort_id`. If
not found: `{"success": false, "message": "Appointment not found"}`.
Otherwise updates `appointment_category_id`/`date`/`time`/`description` and
**unconditionally sets `status` to `"Reschedule"`** regardless of the
appointment's prior status.

**Known quirk (update path):** the notification call after the if/else block
references `$fetchDoctorId->id` and `$updateCompleteTime->id`, both of which
are only assigned inside the **create** branch. On the update/reschedule
path neither variable is set, so the notification is sent with no real
recipient and a `null` request id. The appointment update itself still
succeeds and the API still returns success — it's only the
push-notification-on-reschedule behavior that's silently broken. Don't rely
on a notification firing when testing the reschedule flow.

**Response (success):**
```json
{ "success": true, "message": "Appointment created successfully" }
```
(or `"Appointment updated successfully"` on the update path)

**Notification (create path only):** `Common::sendMobileNotification` sends
to `[$fetchDoctorId->id]` (the clinic doctor), title `Appointment Request`,
module `Clinic`, `request_id` = new appointment id, `pageId` =
`clinic-appointment-request`.

---

### GET `clinic/employee-clinic-dashboard`

The employee's own clinic home-screen summary.

**Request:** no params.

**Controller:** `employeeClinicDashboard()`.

**Response:**
```json
{
  "success": true,
  "message": "Employee Clinic Dashboard Fetched Successfully",
  "dashboard_data": {
    "upcoming_appointments_count": 2,
    "next_appointment": [
      {
        "id": 10,
        "resort_id": 3,
        "employee_id": 202,
        "doctor_id": 55,
        "appointment_category_id": 1,
        "date": "2026-08-01",
        "time": "10:00:00",
        "status": "Pending",
        "doctor_first_name": "Jane",
        "doctor_last_name": "Smith"
      }
    ],
    "pending_leave_request": [
      {
        "id": 44,
        "emp_id": 202,
        "from_date": "2026-08-02",
        "to_date": "2026-08-03",
        "status": "Pending",
        "reason": "Flu",
        "first_name": "Roshan",
        "last_name": "...",
        "leave_category": "Sick Leave",
        "position": "Housekeeping Attendant"
      }
    ],
    "medical_certificate_issue_count": 1
  }
}
```
`next_appointment` is the list of the employee's upcoming
Pending/Approved/Reschedule appointments (not a single object, despite the
singular key name). `pending_leave_request` is the employee's own leave
requests still awaiting the Clinic Manager's (`approver_rank = 12`) sign-off.

---

### GET `clinic/appointment-details/{appointment_id}`

**Path param:** `appointment_id` — **base64-encoded** id
(`base64_decode($appointment_id)` is called in the controller — the mobile
client must base64-encode the numeric id before building the URL).

**Controller:** `appointmentDetails($appointment_id)`.

**Access-scope note:** the query filters only by `resort_id` + `id`, **not**
by the calling employee's own `employee_id`. Any authenticated employee in
the resort can fetch any appointment's details by id, not just their own.

**Response:**
```json
{
  "success": true,
  "message": "Appointment Details Fetched Successfully",
  "appointment_data": {
    "id": 10,
    "resort_id": 3,
    "employee_id": 202,
    "doctor_id": 55,
    "appointment_category_id": 1,
    "date": "2026-08-01",
    "time": "10:00:00",
    "status": "Pending",
    "employee_age": 34,
    "appointmentCategory": { "id": 1, "appointment_type": "General Checkup", "color": "#2E86AB" },
    "employee": {
      "id": 202,
      "Admin_Parent_id": 500,
      "Position_id": 12,
      "dob": "1992-03-14",
      "resortAdmin": { "id": 500, "first_name": "Roshan", "last_name": "...", "profile_picture": "https://.../pic.jpg", "gender": "Male" },
      "position": { "id": 12, "position_title": "Housekeeping Attendant" }
    }
  }
}
```
`profile_picture` is overwritten with a resolved URL from
`Common::getResortUserPicture()` before the response is built.

---

### POST `clinic/appointment-status-update`

Transitions an appointment's status through a fixed state machine.

**Request body:**

| Field | Rule |
|---|---|
| `appointment_id` | required |
| `status` | required |

**Allowed transitions** (enforced in code — anything else returns
`{"success": false, "message": "Invalid status transition from \"X\" to \"Y\""}`):

| From | To (allowed) |
|---|---|
| `Pending` | `Approved`, `Cancel`, `Rejected` |
| `Reschedule` | `Approved`, `Cancel`, `Rejected` |
| `Approved` | `Treatment` |
| `Treatment` | `Medical Certificate` |

**Access-scope note:** same as `appointment-details` — the appointment is
looked up by `id` + `resort_id` only, with no ownership check against the
calling employee. Any authenticated employee's token can transition any
appointment in the resort (this is the same plain `auth:api` route, not
Clinic-Manager-gated).

**Response (success):**
```json
{ "success": true, "message": "Appointment status updated successfully" }
```

**Notification:** sent to `[$appointment->employee_id]` (the appointment
owner), title `Appointment {status}`, module `Clinic`, `request_id` =
appointment id, `pageId` = `clinic-appointment-status`.

---

### GET `clinic/treatment-details/{treatment_id}`

**Path param:** `treatment_id` — plain numeric id (not base64-encoded, unlike
`appointment-details`).

**Controller:** `treatmentDetails($treatment_id)`. Scoped by `resort_id` +
`id` only (no employee ownership check, same pattern as above).

Enriches the raw `clinic_treatment` row with: `profile_picture` (resolved),
`rank` (overwritten with the human-readable label from
`config('settings.Position_Rank')` — this now resolves correctly for rank 12
thanks to today's config fix), `age` (from dob), `medical_certificate_issue`
(`"Yes"`/`"No"` — whether a `ClinicMedicalCertificate` exists for this
treatment id), `doctor_name` (looked up via `Employee::where('rank', 12)->first()`
joined to `resort_admins` — assumes a single active clinic doctor), and
`attachments` (array of resolved URLs built from
`config('settings.clinicTreatmentAttachments')` = `uploads/clinic_treatment_attachments`).

**Response:**
```json
{
  "success": true,
  "message": "Treatment details fetched successfully",
  "medical_history_details": {
    "id": 7,
    "resort_id": 3,
    "appointment_id": 10,
    "employee_id": 202,
    "appointment_category_id": 1,
    "date": "2026-08-01",
    "time": "2026-08-01 09:15:00",
    "treatment_provided": "Paracetamol prescribed",
    "additional_notes": "Follow up in 3 days if fever persists",
    "priority": "Normal",
    "Admin_Parent_id": 500,
    "rank": "",
    "gender": "Male",
    "personal_phone": "+9607xxxxxx",
    "first_name": "Roshan",
    "last_name": "...",
    "position": "Housekeeping Attendant",
    "appointment_type": "General Checkup",
    "profile_picture": "https://.../pic.jpg",
    "age": 34,
    "medical_certificate_issue": "No",
    "doctor_name": "Jane Smith",
    "attachments": ["https://.../uploads/clinic_treatment_attachments/RESORT_ID/EMP_ID/file.pdf"]
  }
}
```
(Response key is `medical_history_details` even though this is a single
treatment record, as coded.)

---

### GET `clinic/medical-certificate-details/{medical_cert_id}`

**Path param:** `medical_cert_id` (route param name; controller argument is
`$medicalCertiId`) — plain numeric id.

**Controller:** `medicalCertificateDetail($medicalCertiId)`. Scoped by
`resort_id` + `id`, no ownership check.

Enriches with `profile_picture`, `rank` (label from config), `age`, and
`attachment` (resolved URL from
`config('settings.clinicMedicalCertificateAttachments')` = `uploads/clinic_medical_certificate`,
only rewritten if an attachment was actually stored).

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 3,
    "resort_id": 3,
    "appointment_id": 10,
    "clinic_treatment_id": 7,
    "leave_request_id": 44,
    "employee_id": 202,
    "appointment_category_id": 5,
    "start_date": "2026-08-01",
    "end_date": "2026-08-03",
    "description": "2 days rest advised",
    "Admin_Parent_id": 500,
    "rank": "",
    "gender": "Male",
    "first_name": "Roshan",
    "last_name": "...",
    "appointment_type": "Medical Certificate",
    "position": "Housekeeping Attendant",
    "profile_picture": "https://.../pic.jpg",
    "age": 34,
    "attachment": "https://.../uploads/clinic_medical_certificate/RESORT_ID/EMP_ID/file.pdf"
  }
}
```
Note the top-level key is `data`, not `medical_certificate_data` or similar
— inconsistent with most other endpoints in this controller.

---

### GET `clinic/past-medical-history`

The employee's own combined appointment + medical-certificate history.

**Request:** no params.

**Controller:** `pastMedicalHistory()`. Merges the employee's own
`ClinicTreatment` rows (`type: "appointment"`) and `ClinicMedicalCertificate`
rows (`type: "medical_certificate"`), sorted by `date`, then `time`.

**Response:**
```json
{
  "success": true,
  "message": "Past Medical History Fetched Successfully",
  "past_medical_history": [
    {
      "treatment_id": 7,
      "resort_id": 3,
      "appointment_id": 10,
      "employee_id": 202,
      "appointment_category_id": 1,
      "date": "2026-08-01",
      "time": "2026-08-01 09:15:00",
      "treatment_provided": "Paracetamol prescribed",
      "additional_notes": "Follow up in 3 days if fever persists",
      "type": "appointment"
    },
    {
      "medical_certificate_id": 3,
      "resort_id": 3,
      "appointment_id": 10,
      "clinic_treatment_id": 7,
      "employee_id": 202,
      "appointment_category_id": 5,
      "date": "2026-08-01",
      "time": null,
      "description": "2 days rest advised",
      "type": "medical_certificate"
    }
  ]
}
```

---

## Clinic Manager APIs

*(all under `['auth:api', 'check.rank:CLINIC_STAFF']` — caller's employee
`rank` must map to `CLINIC_STAFF` via `config('settings.Position_Rank')`,
i.e. `rank = 12`)*

### POST `clinic/appointment-categories-store`

Create a new appointment category for the resort.

**Request body:**

| Field | Rule |
|---|---|
| `appointment_type` | required |
| `color` | required |

**Controller:** `appointmentCategoriesStore()`. Blocks duplicates: if a
category with the same `appointment_type` already exists for the resort,
returns `{"success": false, "message": "Category already added. Please enter a new category"}`
(HTTP 200). Otherwise creates the row (`created_by`/`modified_by` are now
both set correctly via the model's `boot()` hook fix from today).

**Response:**
```json
{
  "success": true,
  "message": "Appointment Categories Created Successfully",
  "appointment_category_data": {
    "resort_id": 3,
    "appointment_type": "Dental",
    "color": "#7B4EA3",
    "created_by": 202,
    "modified_by": 202,
    "id": 6,
    "updated_at": "2026-07-26T00:00:00.000000Z",
    "created_at": "2026-07-26T00:00:00.000000Z"
  }
}
```

---

### GET `clinic/clinic-staff-dashboard`

The Clinic Manager's home-screen dashboard.

**Request:** no params.

**Controller:** `clinicStaffDashboard()`.

**Known issue — missing resort scoping:** `upcoming_appointments_count` is
computed as `ClinicAppointment::whereDate('date', '>=', Carbon::today())->count()`
with **no `resort_id` filter**, unlike every other metric in this same
method (`medical_history_count`, the medical-certificate counts, the leave
counts — all correctly scoped to `resort_id` / the caller). On any DB with
more than one resort's clinic data, this count will include other resorts'
upcoming appointments. Worth flagging to mobile QA if the number looks too
high in a multi-resort test environment.

**Response:**
```json
{
  "success": true,
  "message": "Employee Clinic Dashboard Fetched Successfully",
  "dashboard_data": {
    "upcoming_appointments_count": 5,
    "medical_history_count": 12,
    "medical_certificate_daily": 1,
    "medical_certificate_weekly": 4,
    "medical_certificate_monthly": 9,
    "categories": [
      { "id": 1, "appointment_type": "General Checkup", "color": "#2E86AB", "employee_count": 8, "percentage": 40 }
    ],
    "medical_leave_requests_pending": 2,
    "medical_leave_requests_approved": 6,
    "appointment_requests": [
      { "id": 10, "employee_id": 202, "first_name": "Roshan", "last_name": "...", "position": "Housekeeping Attendant", "date": "2026-08-01", "time": "10:00:00", "status": "Pending" }
    ]
  }
}
```
`appointment_requests` is limited to the 2 soonest non-cancelled/rejected
appointments (`->limit(2)`).

---

### POST `clinic/appointment-list-based-filter`

Filtered appointment list for the manager's calendar/list view.

**Request body:**

| Field | Rule | Notes |
|---|---|---|
| `filter` | not validated (no `Validator::make` call) | `"today"`, `"weekly"`, or `"monthly"`; anything else (including missing) falls back to today's range |

**Controller:** `appointmentListBasedonFilter(Request $request)`. Only
`Pending` and `Reschedule` status appointments are included.

**Known issue — missing resort scoping:** like the dashboard above, this
query has **no `resort_id` filter at all** — it will return matching
appointments across every resort in the system, not just the caller's own.

**Response:**
```json
{
  "success": true,
  "message": "Appointment List Fetched Successfully",
  "appointments": [
    {
      "id": 10,
      "resort_id": 3,
      "employee_id": 202,
      "date": "2026-08-01",
      "time": "10:00:00",
      "status": "Pending",
      "Admin_Parent_id": 500,
      "first_name": "Roshan",
      "last_name": "...",
      "position_title": "Housekeeping Attendant",
      "profile_picture": "https://.../pic.jpg"
    }
  ]
}
```

---

### GET `clinic/appointment-and-leave-list`

Combined pending-appointment-requests + pending-leave-approvals list for the
manager, correctly scoped to `resort_id` this time.

**Request:** no params.

**Controller:** `appointmentAndLeaveList()`. `leave_request` is filtered to
leaves where `els.approver_id` = the calling manager's employee id and
`els.approver_rank = 12`, `status = 'Pending'`.

**Response:**
```json
{
  "success": true,
  "message": "Employee Clinic Dashboard Fetched Successfully",
  "dashboard_data": {
    "appointment_requests": [
      {
        "id": 10,
        "employee_id": 202,
        "first_name": "Roshan",
        "last_name": "...",
        "position": "Housekeeping Attendant",
        "appointment_type": "General Checkup",
        "Admin_Parent_id": 500,
        "treatment_id": null,
        "profile_picture": "https://.../pic.jpg",
        "status": "Pending"
      }
    ],
    "leave_request": [
      {
        "id": 44,
        "employee_id": 202,
        "from_date": "2026-08-02",
        "to_date": "2026-08-03",
        "status": "Pending",
        "reason": "Flu",
        "Admin_Parent_id": 500,
        "first_name": "Roshan",
        "last_name": "...",
        "leave_category": "Sick Leave",
        "position": "Housekeeping Attendant",
        "emp_leave_status_id": 88,
        "approver_rank": 12,
        "approver_id": 999,
        "approved_at": null,
        "approve_status": "Pending",
        "profile_picture": "https://.../pic.jpg"
      }
    ]
  }
}
```
`treatment_id` on each appointment row is a correlated-subquery lookup — the
id of the first `clinic_treatment` row tied to that appointment, or `null`
if none exists yet (useful for mobile to decide whether to show "Add
Treatment" vs "View Treatment").

---

### POST `clinic/treatment-add`

Record a treatment for an employee (optionally against an existing
appointment).

**Request body:**

| Field | Rule | Notes |
|---|---|---|
| `employee_id` | required | The patient |
| `appointment_category_id` | required | |
| `date` | required | **Not actually stored** — see quirk below |
| `time` | required | **Not actually stored** — see quirk below |
| `treatment_provided` | required | |
| `additional_notes` | required | |
| `priority` | required | |
| `appointment_id` | optional | If given, that appointment's `status` is set to `Treatment` |
| `external_consultation` | optional | |
| `attachments` | optional, file(s) | See quirk below |

**Known quirk — `date`/`time` are validated but discarded:** the create call
always sets `'date' => now(), 'time' => now()` on the `ClinicTreatment` row,
ignoring the submitted `date`/`time` values entirely, even though they're
`required` in the validator. The treatment record's date/time will always
be "now", not whatever the client sent.

**Known quirk — attachment loop:** the code does
`foreach($request->attachments as $file) { $file = $request->file('attachments'); ... }`
— it reassigns `$file` from `$request->file('attachments')` inside the loop
instead of using the loop's `$file`, so multi-file upload semantics aren't
reliable. Treat this endpoint as effectively single-attachment for now.

**Response (success):**
```json
{
  "success": true,
  "message": "Treatment Added Successfully",
  "treatment_data": {
    "resort_id": 3,
    "appointment_id": 10,
    "employee_id": 202,
    "appointment_category_id": 1,
    "date": "2026-07-26 00:00:00",
    "time": "2026-07-26 00:00:00",
    "treatment_provided": "Paracetamol prescribed",
    "additional_notes": "Follow up in 3 days",
    "external_consultation": null,
    "priority": "Normal",
    "created_by": 999,
    "id": 8,
    "updated_at": "2026-07-26T00:00:00.000000Z",
    "created_at": "2026-07-26T00:00:00.000000Z"
  }
}
```
No mobile notification is sent by this endpoint.

---

### GET `clinic/medical-history-list`

List of employees who have treatment history, for the manager's medical
history index screen.

**Request:** no params.

**Controller:** `medicalHistoryList()`. Groups `clinic_treatment` rows by
`employee_id` (scoped to `resort_id`), so it returns one row per employee
(MySQL "loose" `GROUP BY` semantics — the specific treatment fields
returned per employee are whichever row the engine picks first, not
necessarily their latest).

**Response:**
```json
{
  "success": true,
  "message": "Treatment Fetched Successfully",
  "treatment_data": [
    { "id": 7, "employee_id": 202, "treatment_provided": "Paracetamol prescribed", "first_name": "Roshan", "last_name": "..." }
  ]
}
```

---

### GET `clinic/medical-history-details/{emp_id}`

Full medical history for one employee — the manager's per-employee detail
screen.

**Path param:** `emp_id` — plain numeric `employees.id`.

**Controller:** `medicalHistoryDetails($emp_id)`. Computes: employee profile
fields + `rank` (label from `config('settings.Position_Rank')` — resolves
correctly for `12` now that the config fix is in), `age`, `appointments_count`,
`medical_certificate` (count), `sick_leave_count` (sum of `total_days` across
all `Approved` leaves whose category name contains "sick", case-insensitive),
and `history` — merged treatment + medical-certificate records sorted by
date/time.

**Response:**
```json
{
  "success": true,
  "message": "Medical history fetched successfully.",
  "medical_history_details": {
    "employee_data": {
      "id": 202,
      "Admin_Parent_id": 500,
      "rank": "CLINIC_STAFF",
      "dob": "1992-03-14",
      "gender": "Male",
      "personal_phone": "+9607xxxxxx",
      "first_name": "Roshan",
      "last_name": "...",
      "position": "Housekeeping Attendant",
      "profile_picture": "https://.../pic.jpg",
      "age": 34
    },
    "appointments_count": 3,
    "medical_certificate": 1,
    "sick_leave_count": 5,
    "history": [
      { "treatment_id": 7, "resort_id": 3, "appointment_id": 10, "employee_id": 202, "appointment_category_id": 1, "date": "2026-08-01", "time": "2026-08-01 09:15:00", "treatment_provided": "Paracetamol prescribed", "additional_notes": "Follow up in 3 days", "type": "appointment" },
      { "medical_certificate_id": 3, "resort_id": 3, "appointment_id": 10, "clinic_treatment_id": 7, "employee_id": 202, "appointment_category_id": 5, "date": "2026-08-01", "time": null, "description": "2 days rest advised", "type": "medical_certificate" }
    ]
  }
}
```
Note `employee_data.rank` in the example above shows what the label
resolves to for a rank-12 employee purely to illustrate the fixed config —
in practice this endpoint is showing the *patient's* rank, who is typically
not the clinic staff member themselves.

---

### POST `clinic/treatment-additional-note-update`

Edit the additional notes on an existing treatment record.

**Request body:**

| Field | Rule |
|---|---|
| `clinic_treatment_id` | required |
| `additional_notes` | required |

**Controller:** `treatmentAdditionalNoteUpdate()`. Looked up by `id` +
`resort_id`; if missing: `{"success": false, "message": "Appointment and Treatment not found."}`.

**Response (success):**
```json
{ "success": true, "message": "Additional notes updated successfully." }
```
No notification sent.

---

### POST `clinic/medical-certificate-store`

Issue a medical certificate to an employee.

**Request body:**

| Field | Rule | Notes |
|---|---|---|
| `employee_id` | required | |
| `start_date` | required | |
| `end_date` | required | |
| `appointment_category_id` | required | |
| `description` | required | |
| `leave_request_id` | **not in the validator, but effectively required** | See critical quirk below |
| `appointment_id` | optional | If given, sets that appointment's `status` to `Medical Certificate` |
| `clinic_treatment_id` | optional | If given, must resolve to an existing treatment in this resort or the request is rejected |
| `attachments` | optional, file | See critical quirk below — must be sent under this exact key |

**Critical quirk — `leave_request_id` is a hard, undocumented requirement:**
right after validation, the controller runs
`$leave = EmployeeLeave::find($request->leave_request_id);` **unconditionally**
— even though `leave_request_id` isn't in the validator at all. If it's
missing or doesn't resolve to a real `EmployeeLeave` row, the endpoint
immediately returns:
```json
{ "status": "error", "message": "Leave request not found." }
```
(HTTP 200, and note the key is `status`, not `success` — the one exception
to the general response-shape rule at the top of this doc, alongside
`clinicStaffLeaveAction` below.) In practice: **you cannot issue a medical
certificate that isn't tied to an existing leave request id**, regardless of
what the validation rules imply.

**Critical quirk — attachment field-name mismatch:** the code checks
`$request->hasFile('attachment')` (singular) but then reads
`$request->file('attachments')` (**plural**) to get the actual file. If the
client sends the file under the key `attachment` (singular — which is what
the `hasFile` check implies it should be named), `$request->file('attachments')`
will return `null`, and the subsequent `$file->getClientOriginalName()` call
will error on `null`. **Mobile must send the attachment under the field name
`attachments` (plural)** for the upload path to work at all; sending it as
`attachment` will not be recognized correctly.

Also: `clinic_treatment_id` existence is only checked if provided; if the
resolved `clinic_treatment_id` doesn't belong to this resort:
`{"success": false, "message": "Invalid clinic_treatment_id. Treatment not found."}`.

**Response (success):**
```json
{ "success": true, "message": "Medical certificate uploaded successfully." }
```
(No `data`/created-record payload is returned on success, unlike
`appointmentCategoriesStore`/`treatmentAdd`.)

**Notification:** only sent if `leave_request_id` was provided (which, per
the quirk above, is effectively always) — goes to the *other* still-pending
approvers on that leave request (excluding the calling manager), title
`Medical Certificate Issued`, module `Clinic`, `request_id` = new
certificate id, `pageId` = `clinic-medical-certificate`.

---

### POST `clinic/clinic-staff-leave-action`

Approve or reject a sick-leave request that's routed to the Clinic Manager
for sign-off.

**Request body:**

| Field | Rule |
|---|---|
| `leave_id` | required |
| `status` | required, `in:Approved,Rejected` |

**Controller:** `clinicStaffLeaveAction()`. If `EmployeeLeave::find($leave_id)`
doesn't resolve, returns the same non-standard shape as above:
```json
{ "status": "error", "message": "Leave request not found." }
```
The actual status update only affects the `employees_leaves_status` row
where `leave_request_id` matches, `approver_id` = the calling manager's
employee id, and current `status = 'Pending'` — i.e. this only "consumes"
the calling manager's own pending approval step, not the whole leave
request. If no such row exists (already actioned, or not actually assigned
to this approver), you get:
```json
{ "success": false, "message": "Failed to update leave status. Maybe already processed." }
```

**Response (success):**
```json
{ "success": true, "message": "Leave has been Approved successfully." }
```

**Notification:**
- On `Rejected`: sent to `[leave->emp_id]` (the leave requester), title
  `Leave Request`, **module `Leave`** (not `Clinic` — worth knowing if mobile
  filters notifications by module), `pageId` = `leave-rejected`.
- On `Approved`: sent to the *other* still-pending approvers for that leave
  request (excluding the calling manager), same title/module, `pageId` =
  `leave-approved`.
