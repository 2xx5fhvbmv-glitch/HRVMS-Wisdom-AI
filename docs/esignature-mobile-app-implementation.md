# E-signature — Mobile App implementation

For the mobile developer. Companion to `esignature-web-portal-implementation.md`
(web/backend — where the signature actually gets embedded into PDFs).
**No code has been changed** — this is a requirements + findings doc from a
full, read-only study of the codebase.

## The gap, in one line

**The web portal has a signature-upload screen (Profile page). The mobile
app has none at all.** No screen, no API endpoint, nothing. Confirmed by
searching every mobile API controller (`app/Http/Controllers/API/*.php`) —
there is no signature-related route anywhere on the mobile side today.

Why this matters beyond "feature parity": HOD/manager approvals for Leave
routinely happen from the mobile app in normal day-to-day use. If the
signature requirement only gets built on web, a large share of real
approvals — done from a phone — would never actually carry a signature,
defeating the point.

(Note: Island Pass is also approved from mobile and shares the same
approval-action code path as Leave, but Island Pass itself has been
excluded from this requirement's scope — see the web doc's module survey
table. It's mentioned here only because the shared code path matters for
the gate described below, not because Island Pass documents need a
signature.)

## What already exists on mobile (the pattern to copy)

Mobile already has a working "update my own profile photo" endpoint —
`app/Http/Controllers/API/ProfileController.php::changeProfileImage()`
(route it's bound to: check `routes/api.php` for the exact path, not
re-derived here since it's a routing detail, not a design decision). It:

1. Validates a single file (`mimes:jpg,jpeg,png,gif,svg,webp,heic,heif`).
2. Resolves the logged-in user via `Auth::guard('api')->user()`.
3. Saves it through `Common::UploadProfileAwsPic()` — the **same** helper
   the web portal's signature upload already uses.
4. Returns `{status, message}`.

**Build the new signature-upload endpoint as a near-copy of this exact
method** (new route, `signature_img` field instead of `profile_picture`,
save target `ResortAdmin.signature_img` instead of `profile_picture`) —
this is not new architecture, it's the same proven pattern with one field
swapped. The one real difference: the new flow needs the background-removal
+ confirm/retry round-trip below, which `changeProfileImage()` doesn't have.

## The upload flow you described — screen by screen

This is the same flow on both mobile and web (the web doc references this
same spec rather than duplicating it — build it once, describe it once).

1. **Capture/select** — user photographs their signature on plain white
   paper, or picks an existing photo, exactly like today's profile-photo
   picker.
2. **Auto background removal** — the raw photo is sent to the backend,
   which strips the background so only the ink strokes remain (see the web
   doc's "Background-removal implementation choice" for the two realistic
   ways to build this server-side — `intervention/image` thresholding vs.
   a third-party AI API. Either way, this step happens **server-side**, not
   on-device, so mobile just uploads the raw photo and receives back a
   processed preview image).
3. **Preview + confirm** — the app shows the processed (background-removed)
   signature to the user with a plain question: *"Is this signature
   clear?"* — Yes / No.
   - **Yes** → the processed image is saved as `signature_img`. Done.
   - **No** → discard the processed preview, return to step 1 ("Please
     retake the photo with better lighting/a plain white background" or
     similar — exact copy is a design decision, not made here), let the
     user retry from scratch. Nothing is saved on a "No."
4. This means the upload is genuinely **two network round-trips**, not one:
   one to upload the raw photo and get a processed preview back, a second
   to confirm-and-save (or nothing, on retry). Design the API accordingly
   — see below.

## New API endpoints needed (web backend work, listed here so mobile knows exactly what to call)

None of these exist today. Naming is illustrative, not mandated — match
whatever convention `routes/api.php` already uses for this controller.

| Endpoint | Purpose | Request | Response |
|---|---|---|---|
| `POST /profile/signature/preview` | Upload the raw signature photo, get back a background-removed preview | multipart file (same mime rules as `changeProfileImage`) | `{ success, preview_url, preview_token }` — a short-lived reference to the processed-but-not-yet-saved image, so step 2 (confirm) doesn't require re-uploading the file |
| `POST /profile/signature/confirm` | User tapped "Yes, it's clear" — persist the previewed image as the real signature | `{ preview_token }` | `{ success, message, signature_url }` |
| *(no reject endpoint needed)* | User tapped "No" | — nothing to call; the app just discards the preview client-side and lets the user retry from step 1 | — |
| `GET /profile/signature` | Fetch the current signature (for showing it back to the user on their own profile screen, same as the photo already does) | — | `{ signature_url }` or `null` if none uploaded yet |

**Why a preview/confirm split instead of one call**: the requirement is
explicit that nothing gets saved until the user confirms the processed
image looks right. A single upload-and-save call would have no clean way
to "undo" if the user says the background removal came out badly — a
short-lived server-side preview (temp file, few-minute expiry, cleaned up
whether confirmed or abandoned) is the standard shape for this kind of
"process, show, confirm-or-discard" flow.

## The other mobile-side change: gate approval actions on having a signature

Mirroring the pattern already proven on web (Interview Assessment/Feedback/
Evaluation forms — see the web doc), **every mobile approval action that a
signed PDF will eventually depend on should check for a signature first**
and block with a clear message if missing, the same way
`InterviewAssessmentController` already does on web:

> "Authorized signature is missing. Please upload it first from your
> profile page."

Concretely, this means `API/LeaveController::handleLeaveAction()` (the
single action handler that covers both Leave and Island Pass
approve/reject/hold, confirmed via reading the controller) needs this same
guard added before it lets a **Leave** approval go through. (Island Pass
shares this same handler but is out of scope, so the guard doesn't need to
distinguish between the two — it's fine for it to also apply there as a
side effect of sharing the code path; it just doesn't matter one way or
the other since no Island Pass PDF exists to need the signature.) Without
this guard, someone could approve a Leave request from their phone having
never uploaded a signature, and the resulting PDF (once the web side builds
the signature block into the Leave PDF, per the web doc) would just show a
blank space where their signature should be — a silent gap, not an error
anyone would notice until someone downloads the document.

**Also confirm this is where the snapshot happens** (see the web doc's
"Which signature gets used — decided" section): the moment this guard
passes and the approval action actually records itself, that's the exact
point the web backend's signature-snapshot mechanism needs to fire — it
copies whatever signature is on the approver's profile *right now* into a
permanent record tied to that approval, forever. This happens entirely
server-side; mobile doesn't need to do anything extra beyond calling the
existing approval endpoint normally. Just know that the timing matters:
the snapshot is taken at the moment of THIS action, not looked up later —
which is exactly why the guard above (block if no signature exists yet)
has to run first.

Whether to hard-block (like Interview Assessment does) or soft-warn
("You haven't uploaded a signature — approvals won't show one until you
do, continue anyway?") is a product decision the web doc leaves open too;
listing it here so both sides make the same choice.

## Also newly in scope: Grievance, Incident, Budget, Performance

Per the latest direction, these four are now in scope too (see the web
doc's "⚠ Grievance, Incident, Budget, Performance" section for the full
detail on what's missing on the backend before any of this can work).
**All four already have mobile controllers today**:
`API/GrievanceController.php`, `API/IncidentController.php`,
`API/BudgetCostController.php` + `API/ManningController.php`, and
`API/PerformanceReviewController.php` — confirmed by direct file search,
so there's nowhere in the app today where these are web-only.

**Nothing for mobile to build yet.** The web backend needs to add a PDF/
email export and (for most of these) a proper per-approver chain first —
none of that exists today (see the web doc). Once that foundation is in
place for a given module, the mobile-side change is exactly the same shape
as the Leave gate above: find whichever method in that module's API
controller actually records the approval/consent/statement, and add the
same "block if no signature uploaded yet" guard before it. This doc flags
it now so it isn't forgotten later, not because there's mobile work to do
today.

## What does NOT need mobile-side work

The actual PDF generation (Leave PDF, Payroll/Settlement PDF, Offer Letter/
Contract PDF, etc.) all happen server-side, triggered from the web portal
or by background jobs — nothing about rendering a PDF happens on-device,
and mobile does not need to implement any snapshot logic itself. Once an
employee has uploaded a signature (via either platform — the field is
shared, `ResortAdmin.signature_img`, there's no separate "mobile signature"
vs "web signature"), the web backend automatically snapshots it into every
approval that person makes from that point on, regardless of whether the
approval action itself was tapped on the app or clicked on the portal.
Mobile's job is entirely: (1) let the user manage their own current
signature with the capture/remove-background/confirm flow above, and
(2) don't let an approval happen from the app without one, per the gate
above. Everything about *freezing* that signature into a specific,
permanent, never-changing approval record is the web backend's job, not
mobile's.

## Verification note

The "no signature endpoint exists on mobile" finding was confirmed by
grepping every file in `app/Http/Controllers/API/` — not inferred from
route naming or assumed. `changeProfileImage()` was read in full
(`app/Http/Controllers/API/ProfileController.php` lines 383-438) as the
concrete pattern to mirror. Island Pass's mobile-only status (no web
controller anywhere) was also confirmed the same way, before being
excluded from scope per your direction.
