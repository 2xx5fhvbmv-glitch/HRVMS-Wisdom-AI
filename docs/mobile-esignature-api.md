# E-signature — Mobile API

For the mobile app developer. **Status: built and live**, verified directly
against `app/Http/Controllers/API/ProfileController.php`,
`API/LeaveController.php`, `API/OnBoardingController.php` and
`routes/api.php` — this replaces the earlier "not built yet" version of this
doc. All routes below sit under the standard mobile auth group
(`auth:api` Passport bearer token + `applyResortSmtp`), same as every other
`API/` endpoint.

---

## 1. Signature capture — upload once, from profile

Three endpoints. Flow: **preview** (upload raw photo, server removes
background, returns a preview) → user taps "Is this clear?" → **confirm**
(saves it) or discard client-side (no reject/cancel call needed — the temp
file just expires). **get** reads back whatever's currently saved.

| Method | Endpoint |
|---|---|
| POST | `resort/profile-signature-preview` |
| POST | `resort/profile-signature-confirm` |
| GET  | `resort/profile-signature` |

### `POST resort/profile-signature-preview`

Multipart body, field `signature_image` — required, file, mimes:
`jpg,jpeg,png,gif,webp,heic,heif`.

Success `200`:
```json
{
  "success": true,
  "preview_url": "https://...",   // signed URL, expires in 15 min
  "preview_token": "..."           // 40-char string, pass to confirm
}
```

Failure shapes:
- Not authenticated → `401`, `{ "success": false, "message": "Unauthorized" }`
- Validation (bad/missing file) → `400`, `{ "success": false, "errors": {...} }`
- Employee record not resolved → `200`, `{ "success": false, "message": "Employee not found" }`
- Server error → `500`, `{ "success": false, "message": "Server error" }`

Nothing is written to the employee's real signature at this point.
`preview_token` is only valid for **10 minutes** and only for the account
that requested it.

### `POST resort/profile-signature-confirm`

Body: `{ "preview_token": "..." }` — required, string.

Success `200`:
```json
{
  "success": true,
  "message": "Signature saved",
  "signature_url": "https://..."   // signed URL, expires in 30 min
}
```

Failure shapes:
- Not authenticated → `401`, same shape as above
- Missing/non-string `preview_token` → `400`, `{ "success": false, "errors": {...} }`
- Token expired, wrong account, or already consumed → `200`, `{ "success": false, "message": "Preview expired, please retake" }`
- Employee record not resolved → `200`, `{ "success": false, "message": "Employee not found" }`
- Server error → `500`, `{ "success": false, "message": "Server error" }`

On success this is the point the employee's `signature_img` actually
changes — every later approval starts using it immediately.

### `GET resort/profile-signature`

No params.

Success `200`, always `success: true`:
```json
{ "success": true, "signature_url": "https://..." }
```
or, if nothing's been uploaded yet (**not a 404** — same 200 shape, field
just null):
```json
{ "success": true, "signature_url": null }
```
`signature_url` is a fresh 30-minute signed URL each call, not a stable
link — don't cache it past that window.

---

## 2. Leave & Island Pass approval — unaffected, no signature required

`POST resort/handle-leave-action` (shared handler for Leave and Island
Pass approve/reject) has **no signature gate and never calls the snapshot
helper** — confirmed in code, with an explicit comment explaining why:
these requests are never turned into a PDF, so there's nowhere for a
signature to render. Approving/rejecting either type works exactly as
before; nothing changes on the app side and no signature upload is a
prerequisite for this action.

Payload is unchanged: `leave_id` (required), `action` (required,
`Approved`/`Rejected`), `reason` (required only when `action` is
`Rejected`).

---

## 3. Onboarding acknowledgment — signature required, gated

`POST on-boarding/store-acknowledgement` **does** require a signature on
file before it will accept a submission — this is very likely the first
time a new employee ever uploads one (right at the pre-hire→hired
transition), so the app should check `GET resort/profile-signature` first
and route to the upload flow (§1) if `signature_url` comes back null,
rather than letting the user hit the gate error blind.

Request:
```json
{
  "acknowledgements": [
    {
      "acknowledgement_type": "Contract Signed",
      "acknowledged_date": "2026-09-18",
      "status": "Yes"
    }
  ]
}
```
`acknowledgements` — required array, min 1. Per item: `acknowledgement_type`
(required string), `acknowledged_date` (required date), `status` (required,
`Yes` or `No`).

Gate error (no signature on file) — `422`:
```json
{ "success": false, "message": "Authorized signature is missing. Please upload it first from your profile page." }
```

Validation failure → `422`, `{ "success": false, "errors": {...} }`.

Success → `201`:
```json
{
  "success": true,
  "message": "Acknowledgements stored successfully.",
  "data": [ /* created records, each including signature_img/signature_name/signed_at */ ],
  "duplicates": []   // acknowledgement_type values already submitted before, silently skipped
}
```
Re-submitting the same `(acknowledgement_type, acknowledged_date)` pair for
the same employee is not an error — it's skipped and listed in
`duplicates`, and the success message gets an "Already stored: X, Y" suffix.

---

## Out of scope for mobile today

- No PDF is ever built or shown by the app — entirely a web-backend
  concern. The app only ever calls the three signature endpoints above,
  plus whatever approval/submit endpoint it already calls.
- Resignation (`API/ResignationController.php`) has zero signature
  integration currently — nothing to build against there yet.
- Grievance, Incident, Budget, Performance, Talent Acquisition approvals —
  same as before, no mobile-side signature work until the corresponding
  web-side PDF/approval-chain work lands.
