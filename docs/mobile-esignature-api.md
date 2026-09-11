# E-signature — Mobile API

For the mobile team. **Status: not built yet.** This doc is the proposed
contract for endpoints that don't exist in `app/Http/Controllers/API/`
today — confirmed by grep, nothing under `API/` references `signature_img`.
Treat everything below as spec to build against, not a description of live
code (unlike most docs in this folder).

## What already exists (verified against real code, web side)

- `resort_admins.signature_img` — real column (`app/Models/ResortAdmin.php:52`).
  Same table/model as `profile_picture`, resolved via
  `Common::getResortUserPicture($userId, 1)` (type `1` = signature, `0` =
  profile photo).
- Web portal upload: `ResortLoginController::UpdateResortProfile`
  (`routes/resort_route.php:196`, `POST /update/user/profile`) — **plain
  raw file upload**, straight to `Common::UploadProfileAwsPic()`, no
  processing. No background removal, no preview/confirm step exists
  anywhere in this codebase today, web included — that part of the design
  below is new for everyone, not a mobile-catches-up-to-web situation.
- Approval gate pattern (already live, 3 places): `InterviewAssessmentController.php:346`,
  `FeedbackFormController.php:171`, `EvaluationFormController.php:171` all
  block submission with *"Authorized signature is missing. Please upload it
  first from your profile page."* when `signature_img` is empty.
- Snapshot pattern (already live): at submit time these same controllers copy
  `signature_img` into a permanent `interviewer_signature` field on the
  response row (`InterviewAssessmentController.php:447,456`) — never
  re-derived later, so a signature changed afterward doesn't retroactively
  alter past records. Leave/Island Pass approval needs the same treatment
  (not yet built — see below).
- Mobile equivalent of the upload pattern to mirror:
  `API/ProfileController::changeProfileImage()` (`app/Http/Controllers/API/ProfileController.php:415`) —
  validates one file, resolves user via `Auth::guard('api')->user()` →
  `ResortAdmin::find($user->id)`, uploads via `Common::UploadProfileAwsPic()`,
  saves, returns `{status, message}`.

## Proposed endpoints (new — to be built in `API/ProfileController.php`)

All under `auth:api` (Passport Bearer token), same as every other mobile
endpoint in this controller.

| Method | Endpoint | Purpose |
|---|---|---|
| POST | `profile/signature/preview` | Upload a raw signature photo. Server runs background removal, stores the *processed* result under a short-lived temp path, returns a preview image URL + `preview_token`. Nothing persisted to `signature_img` yet. |
| POST | `profile/signature/confirm` | Body: `preview_token`. Moves the previously-processed temp file to the permanent path and sets `resort_admins.signature_img`. |
| GET | `profile/signature` | Returns current `signature_img` URL, or `null` if none uploaded. |

No reject/cancel endpoint — on "No" the app just discards the preview and
never calls `confirm`. The temp file expires/gets swept, nothing to clean up
client-side.

### `POST profile/signature/preview`

Request: multipart, field `signature_image` (image file, same mime allowlist
as `changeProfileImage`: jpg, jpeg, png, gif, svg, webp, heic, heif).

Response:
```json
{ "status": true, "message": "Preview ready", "preview_url": "...", "preview_token": "..." }
```
On failure (bad file, processing error): `{ "status": false, "message": "..." }`.

### `POST profile/signature/confirm`

Request: `{ "preview_token": "..." }`

Response: `{ "status": true, "message": "Signature saved" }` — same shape as
`changeProfileImage()`'s response, mirrored for consistency.

`preview_token` not found/expired → `{ "status": false, "message": "Preview expired, please retake" }`.

### `GET profile/signature`

Response: `{ "status": true, "signature_url": "..." }` or
`{ "status": true, "signature_url": null }` if nothing uploaded yet.

## Approval-gate change (server-side, no app work needed)

`API/LeaveController::handleLeaveAction()` — the shared handler for both
Leave and Island Pass approve/reject/hold — will get the same "signature
missing" guard described above. This is a byproduct of Leave and Island Pass
sharing one code path, not a new requirement on Island Pass: **Island Pass
does not put a signature on its own PDF**, but an approver with no
`signature_img` will be blocked from approving *either* type until they
upload one. Not yet added (confirmed: zero `signature_img` references in
`API/LeaveController.php` today).

Expected error shape when blocked, matching the web pattern:
```json
{ "success": false, "message": "Authorized signature is missing. Please upload it first from your profile page." }
```

## Out of scope for mobile

- PDF generation / embedding the signature image into any document — entirely
  web-backend's job.
- The approval-time snapshot itself (copying `signature_img` into a
  permanent record) — server-side, fires automatically wherever
  `handleLeaveAction()` records the decision. Mobile just calls the existing
  approve/reject/hold endpoints as today.
- Grievance, Incident, Budget, Performance — mobile controllers for these
  already exist, but web-side PDF/export + approver chains aren't ready yet.
  Nothing to build against for these four until that lands.
