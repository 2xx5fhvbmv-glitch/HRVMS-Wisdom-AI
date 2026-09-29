# Mobile API spec — Job Description, Day-Off balance, Change Password, Performance signature gate

For Bharat. Covers 4 pieces of mobile work: a new Job Description screen, a Day-Off leave balance
enhancement, Change Password + forced password change, and wiring the existing signature gate
into Performance Review. Every endpoint below is verified against the current backend source.

Base URL prefix for every path: `api/` (some paths add `resort/` on top, some don't — copy the
path exactly as shown per endpoint). Auth: `Authorization: Bearer <token>` (guard `api`) on
everything except login.

---

## 1. Job Description

### 1.1 List
`GET api/job-description`
```json
{
  "success": true,
  "data": [
    {
      "id": 12,
      "job_title": "Front Office Supervisor",
      "department": "Front Office",
      "status": "Pending",
      "can_act": true,
      "sent_at": "2026-09-20T09:15:00.000000Z",
      "signed_at": null,
      "decline_reason": null
    }
  ]
}
```
`status`: `Pending` / `Signed` / `Declined`. `can_act` true only when `status = Pending`.

### 1.2 Detail
`GET api/job-description/{id}`
```json
{
  "success": true,
  "data": { "...same fields as list row, plus all job_description_employee_records columns..." },
  "pdf_url": "https://.../api/job-description/12/pdf",
  "has_signature": true
}
```
- `pdf_url` is `null` when no PDF generated yet.
- `has_signature`: true only if the employee's profile signature actually decrypts to an image
  (not merely "a path is stored").
- 404 `{"success": false, "message": "Job description not found"}` if the id isn't this
  employee's own record.

### 1.3 PDF
`GET api/job-description/{id}/pdf` → raw bytes, `Content-Type: application/pdf`,
`Content-Disposition: inline`. Decrypted server-side — stream/open directly, don't cache or
re-upload.

### 1.4 Consent (sign)
`POST api/job-description/{id}/consent`, no body.
- 200 `{"success": true, "message": "Job description signed."}` — refetch 1.2/1.3 after, the PDF
  is re-rendered with the employee's signature added.
- 422 `{"success": false, "message": "This job description has already been actioned."}`
- 422 `{"success": false, "message": "No signature on file. Please add your signature in your profile first."}`

### 1.5 Decline
`POST api/job-description/{id}/decline`
```json
{ "reason": "string, required, max 1000 chars" }
```
- 200 `{"success": true, "message": "Job description declined."}`
- 400 `{"success": false, "errors": {"reason": ["The reason field is required."]}}` (standard
  Laravel validator shape — `errors.reason` is an array of strings)
- 422 `{"success": false, "message": "This job description has already been actioned."}`

### 1.6 Push notification
"Job Description Ready for Review" — payload carries the record id; deep-link to 1.2.

---

## 2. Day-Off balance

### 2.1 Leave category listing (add the two new top-level fields)
`GET api/resort/leave-category`
```json
{
  "success": true,
  "message": "Leave Category Listing.",
  "leave_category": [ "...unchanged array, per-category rows..." ],
  "day_off_balance": 4,
  "day_off_max": 10
}
```
`day_off_max` is `null` when the resort has no cap configured on the "Day Off" leave category
(uncapped). `day_off_balance` is always a number (0 if none).

The same two fields (`day_off_balance` / `day_off_max`) are also present on an individual leave's
detail response, for a leave that actually used day-off days.

### 2.2 Apply for leave — optional Day Off combine
`POST api/resort/leave-add` (existing endpoint, one new optional field)
```json
{
  "leave_category_id": [5],
  "from_date": ["2026-10-01"],
  "to_date": ["2026-10-03"],
  "reason": "string",
  "include_day_off_days": 2
}
```
- `include_day_off_days`: integer, optional, `min:0`.
- **Only honored when `leave_category_id` has exactly one entry** — a combined (2-category)
  submission ignores it silently (server-side, no error).
- Server clamps the value to `min(requested, actual day_off_balance, working days in the
  range)` — the app does not need to pre-validate the cap, just send what the employee typed.

---

## 3. Change Password + forced password change

### 3.1 Change password
`POST api/resort/profile-change-password`
```json
{
  "old_password": "string, required",
  "password": "string, required, 12+ chars, upper+lower+number, not a known-breached password",
  "confirm_password": "string, required, must match password"
}
```
Responses (all HTTP 200 — this endpoint reports errors via `status`, not HTTP status codes):
- `{"status": true, "message": "Password updated"}` — on success the server revokes **every**
  active token for the account (all devices, not just this one) and logs the guard out. The app
  must treat this as a forced logout — return to the login screen.
- `{"status": false, "message": "Password is required"}`
- `{"status": false, "message": "Confirm password is required"}`
- `{"status": false, "message": "Confirm password does not match"}`
- `{"status": false, "message": "<password policy message, e.g. 'The password must be at least 12 characters.'>"}`
- `{"status": false, "message": "Old password is incorrect"}`

### 3.2 Forced change on login — ⚠️ backend not wired yet, don't build against this shape until confirmed
`POST api/login` currently returns only:
```json
{ "success": true, "message": "User Login Successfully", "token": "...", "redirect_url": "..." }
```
`must_change_password` is not in this response yet — it's coming (the web login already returns
it in the same shape: `must_change_password: true`), but mobile's login endpoint hasn't been
updated. Build the forced-change screen/routing against that field name, but hold off wiring the
login-response check until we confirm it's live — will follow up once it ships.

---

## 4. Signature gate on Performance Review

### 4.1 Submit endpoint — no signature check today, payload is dynamic
`POST api/performance/review/{id}/submit` has no signature gate on the backend today. The
payload is **template-driven** (whatever fields the assigned review template defines, not a
fixed shape) — send the same dynamic field map the form is already building, no schema change
needed here.

### 4.2 What to build (client-side only for now)
Reuse `SignatureCubit`'s existing check (same one `emp_onboarding_key_acknowledge_screen.dart`
already uses) to test "does this employee have a signature on file" **before** calling 4.1's
submit endpoint. If false, show `SignatureRequiredSheet` with "Upload signature" deep-linking to
`my_signature_screen.dart`. The signature-on-file check itself already exists as an app-side
capability (via the same profile-signature endpoints Job Description's `has_signature` also
reads server-side) — no new backend endpoint needed for the gate itself.

Backend snapshotting a signature onto a mobile-submitted review (so the review PDF shows it, the
way Job Description's consent step does) isn't built yet — out of scope for this round, the
client-side gate above doesn't depend on it.

---

## Known gaps — don't block on these, just flag if you hit them
1. §3.2 — `must_change_password` isn't in the mobile login response yet; build the screen, wire
   the check once it ships.
2. §4 — Performance Review submit isn't hard-blocked server-side without a signature (Job
   Description is) — the client-side gate is what's being built now, backend enforcement is a
   separate follow-up.
