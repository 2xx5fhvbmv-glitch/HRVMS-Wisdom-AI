# E-signature on approved/consented documents — Web Portal implementation

For the backend/web team. Companion to `esignature-mobile-app-implementation.md`
(mobile — signature capture + upload). **No code has been changed** — this is
a requirements + findings doc from a full, read-only study of the codebase.

## The requirement, restated precisely

Every employee uploads one e-signature (once, from their profile — already
exists as a screen, see "What already exists" below). From then on:

- **Any document that (a) represents an approval or a consent given by a
  specific person, AND (b) can be downloaded as a PDF or is emailed as a
  PDF attachment** — put that person's signature, full name, and the exact
  date/time they approved/consented, at the bottom of the relevant section
  of that PDF, plus a small caption under it (e.g. "*Electronically signed*")
  so it reads as a real signature block, not a stray image.
- If a screen has an approval/consent action but **no PDF/email output
  exists at all**, it's out of scope for now — per your own framing,
  signing something with no way to produce/see the signed document doesn't
  make sense yet. (Listed below anyway, marked "no PDF today", so it's not
  missed if a PDF export gets added to that module later.)
- **Which signature gets used — decided: snapshot at the moment of the
  approval/consent action, never re-resolved later.** Whatever signature is
  currently on the approver's/consenter's profile *at the instant they
  approve/consent* is what gets automatically pulled in — no manual
  selection, nothing for the user to choose. But that image must then be
  **copied into a permanent, immutable location tied to that specific
  approval event**, not just referenced live from `ResortAdmin.signature_img`.
  Reasoning (explicit requirement): someone must be able to download the
  same document five years from now and see the exact signature that was
  used at the time of approval, even if that person has since re-uploaded a
  new signature, left the company, or their `signature_img` has changed for
  any other reason. A live lookup at download time would silently show the
  *current* signature instead of the historical one — wrong, and not
  acceptable here.
  **Concrete implication for the build**: this needs a genuine snapshot
  mechanism, not just a foreign key to `ResortAdmin`. At minimum, whatever
  table/action records an approval (a new `TAnotificationChild`-style row,
  a `finalized`/approval event on Settlement, a Leave approval action, etc.)
  needs its own copy of the signature image (e.g. copied to a
  `.../approvals/{record_id}/signature.png`-style permanent path at the
  moment of approval, with that path stored on the approval record itself)
  plus the approver's name and the exact timestamp, all frozen at that
  moment. The PDF template then renders from the approval record's own
  frozen fields — it never calls back to `ResortAdmin.signature_img` for a
  past approval. See "What the web backend needs to build" below for the
  concrete piece of work this implies.
- Terms & Conditions acceptance (candidate application form today — see
  below) should be brought into the same model: an accept action is a
  consent, so it gets the same name/signature/timestamp treatment wherever
  the accepted terms are shown in a PDF.

## What already exists (verified in code, nothing assumed)

### Two separate, disconnected signature systems today

1. **Per-employee signature** — `ResortAdmin.signature_img`, uploaded via
   the employee's own Profile page
   (`resources/views/resorts/workforce_planning/profile.blade.php`, saved by
   `ResortLoginController::UpdateResortProfile()`). Stored via
   `Common::UploadProfileAwsPic()` — a plain file upload, no encryption, no
   background removal, no confirmation step. This is the field the whole
   requirement below builds on.
2. **Resort-wide "Letterhead & E-signature"** — `LetterheadSetting`, one row
   per resort (People → Configuration → Letterhead): one static
   signature image + one signatory name/title, configured once, used on a
   few HR letters (Transfer Letter live; Probation/Promotion built to use it
   but not fully wired per the code's own comments). **This is not the
   per-approver signature the requirement describes** — it's a fixed
   "company letterhead" signature regardless of who actually approved a
   given request. Keep it as-is; it's answering a different question
   (what does this resort's letterhead look like) than the one this task
   answers (who personally approved this specific record).

### Where the per-employee signature is already correctly wired (the pattern to copy)

**Interview Assessment, Feedback Form, and Evaluation Form**
(`app/Http/Controllers/Resorts/TalentAcquisition/InterviewAssessmentController.php`,
`Resorts/Learning/FeedbackFormController.php`,
`Resorts/Learning/EvaluationFormController.php`) all do this correctly:

```php
if (!$this->resort->signature_img) {
    return response()->json(['success' => false,
        'message' => 'Authorized signature is missing. Please upload it first from your profile page.'], 422);
}
$signature = $this->resort->signature_img; // the ACTUAL logged-in user's own signature
```

This is the exact pattern to reuse everywhere below: block the approve/
consent action if the actor has no signature uploaded, otherwise pull
`Auth::guard('resort-admin')->user()->signature_img` (or the mobile-side
equivalent) at the moment of the action. Note: even here, there's no PDF
export for these three forms today — so this pattern is proven, but nothing
currently visualizes the result in a PDF. Wiring the PDF template for these
three would be a quick, low-risk first proof of the full pattern before
tackling payroll/HR letters.

## Every module surveyed — approval/consent status vs. PDF/email status

| Module | Approval/consent exists? | PDF or email export? | Signature/name/date embedded today? | Action needed |
|---|---|---|---|---|
| **Payroll — Payslip** | N/A (payslip is a record, not itself approved) | Yes, PDF (`PayslipController`, `payslip-view.blade.php`) | No | Out of scope unless payslip PDF later shows an approval chain |
| **Payroll — Full & Final Settlement** | Yes — a `finalized` status with one `finalized_at` timestamp, but **no per-approver breakdown** (no individual HR/Finance/GM names or times, just one "finalized" flag) | Yes, PDF (`final_settlement_review.blade.php`) | No | **In scope.** Needs the actual approval chain first (see "Dependency" below), then embed each approver's name + signature + their own timestamp |
| **Talent Acquisition — Vacancy approval (HR→Finance→GM)** | Yes — a real per-rank approval chain already exists (`t_anotification_parents`/`t_anotification_children`, the same data driving the New Hire Requests / Approval History dashboard cards) | **No PDF exists today** | N/A | Flag only — no PDF to embed into yet |
| **Talent Acquisition — Job Description** | No (it's an authored document, not approved/consented) | Yes, PDF (`JobDescriptionController`, `jobdescription/download.blade.php`) | No | Out of scope — not an approval/consent document |
| **Talent Acquisition — Offer Letter** | Yes — candidate accepts/rejects via a tokenised link (`ContractResponseController::accept/reject`) | **Yes — PDF generated AND emailed** (`ApplicantsController.php` ~line 3014, `Mail::send('emails.offerLetter', ...)` with the PDF attached) | **No** — the signature line is a hardcoded blank: `'____________________________'` (`Common.php` ~line 10891) | **In scope, high value.** But: the signer here is a *candidate*, not yet an employee — they have no `ResortAdmin.signature_img` at accept time. See "Candidate signatures" below for how this needs to differ from the internal-employee case |
| **Talent Acquisition — Contract (post-offer)** | Same accept flow | **Yes — PDF + email** (`ApplicantsController.php` ~line 3140, `emails.contract`) | No, same blank-line placeholder | Same as Offer Letter above |
| **Talent Acquisition — Offline interview offer letter** | Sent via `OfflineInterviewController.php` ~line 704 (`OfflineOfferLetterMail`) | Yes — emailed PDF | No | Same candidate-signature caveat as above |
| **People — Transfer Letter** | Yes (HR-initiated, letter issued) | Yes, PDF | Static resort letterhead signatory only (see above), not the actual approver | Decide: does this letter need the *specific HR person who processed it*, or is the resort-wide letterhead intentionally used here? If the former, same pattern as Interview Assessment |
| **People — Probation, Promotion, Exit Clearance letters** | Yes | Yes, PDF (all confirmed via `Dompdf`/`Pdf::loadView` in their controllers) | Same static letterhead pattern, and per the code's comments, not even fully wired for Probation/Promotion yet | Same decision as Transfer Letter |
| **People — Salary Increment, Advance Salary Repayment Tracker** | Yes (approval-driven) | Yes, PDF (confirmed via grep) | Not checked field-by-field — worth a template audit alongside the above | Flag for the same audit |
| **Leave** | Yes — the existing rank-based approval chain (Line Worker/Supervisor → reporting manager → HOD/EXCOM → GM) | Yes, PDF (`LeaveController.php` ~line 2535, `resorts.leaves.leave.pdf`) | No — verified zero signature/approval references in the template | **In scope** |
| **Island Pass** | Yes (HOD → HR → SM approval chain) | No PDF exists, mobile-only feature | N/A | **Excluded from scope** — confirmed with the product owner; not part of this work |
| **Boarding Pass** | Approval-driven | **PDF generation code exists but is commented out / dead** (`API/BoardingPassController.php` ~line 2447-2456) — no live PDF today | N/A | Flag only; if/when this is re-enabled, build it with the signature block from day one rather than retrofitting later |
| **Grievance & Disciplinary** | Yes — investigation stages exist (`DisciplinaryInvestigationParent`/`...Child`), but **no per-person approval-chain record** the way Vacancy/Leave have (just stage notes, not "who signed off, when") | **No PDF export exists in this module at all** | N/A | **Now in scope, per your direction — needs foundational work first.** See "⚠ Grievance, Incident, Budget, Performance" below |
| **Incident** | Yes — `IncidentsEmployeeStatements` already captures `employee_id` + `statement` + `status` per person, the closest thing to a ready attachment point among these four | **No PDF export exists in this module at all** | N/A | **Now in scope, per your direction.** See "⚠ Grievance, Incident, Budget, Performance" below |
| **Accommodation, Visa** | Have consent/approval-adjacent flows | **No PDF export found in either controller** | N/A | Flag only for now — not requested in scope yet |
| **Learning & Development (course/training records)** | Not an approval flow in the sense above | No PDF export found | N/A | Out of scope |
| **Terms & Conditions (candidate application)** | Yes — `Applicant_form_data.terms_conditions`, a plain boolean checkbox on the application form | Tied to the same Offer Letter / Contract PDF+email flow above | No | Fold into the Offer Letter/Contract work above — the T&C acceptance timestamp should sit alongside the candidate's signature block, not be a separate feature |
| **Workforce Planning / Budget** | `BudgetStatus` records one status + comment per department per budget cycle — **not a multi-stage per-approver chain** | **No PDF export exists in this module at all** | N/A | **Now in scope, per your direction.** See "⚠ Grievance, Incident, Budget, Performance" below |
| **Performance (reviews)** | KPI/review-content models exist (`PerformanceKpiParent`/`Child`, `PerformanceCycle`), but **no reviewer sign-off or approval-chain model found at all** — the least-built of these four | **No PDF export exists in this module at all** | N/A | **Now in scope, per your direction.** See "⚠ Grievance, Incident, Budget, Performance" below |
| **Survey** | No — a participant fills in answers, nothing is "approved" | Yes, PDF (`SurveyController.php` ~line 1596, `survey-download-pdf`) | No | Out of scope — this is a results report, not an approval/consent document |

## Candidate signatures — resolved

**Decided:** a candidate has no `ResortAdmin` record and no signature to
pull from, so the Offer Letter / Contract accept flow (still pre-hire, at
that point) keeps the current typed-name + blank-line convention — nothing
changes there. **The moment that person becomes an employee and uploads
their e-signature from their profile, the system automatically starts
using it for every subsequent approval/consent they make** — no manual
step, no "which signature do I use" prompt, ever. This is the same
"automatic, current-at-the-moment-of-the-action" behavior described in
"Which signature gets used" above, just stated here specifically for the
pre-hire → hired transition: signature capture only ever happens once, on
the Profile page, for everyone (never a one-off per-document capture),
and it's simply not available yet for someone who hasn't been onboarded.

## ⚠ Payroll (Full & Final Settlement) — needs a real approval chain BEFORE any signature work starts

This was your lead example (HR approves → Finance approves → GM approves →
signatures appear on the downloaded PDF), so calling it out clearly on its
own rather than burying it as a footnote: **this cannot be built as "add a
signature block to the existing PDF."** It requires new backend work first,
in this order:

1. **There is currently no per-stage approval record for Settlement at
   all.** Today the Settlement model has exactly one status field
   (`finalized`) and one `finalized_at` timestamp — a single yes/no gate,
   not a chain. There is nowhere to record "HR approved at 10:03am, Finance
   approved at 2:15pm, GM approved at 4:40pm" because that data has no
   home yet.
2. **The pattern to copy already exists elsewhere in this codebase** — the
   Vacancy approval chain (`TAnotificationParent` / `TAnotificationChild`,
   one child row per approval rank: HR=3, Finance=7, GM=8, each with its
   own `status` and timestamp) is exactly this shape, already built and
   already driving the New Hire Requests / Approval History dashboard.
   Leave's rank-based chain is the same idea. **Settlement needs an
   equivalent — either reuse the same `t_anotification_*` tables with a
   new parent type, or a dedicated `settlement_approvals` table — one row
   per approval stage, each with its own approver id, status, and
   timestamp.**
3. **Only once that exists** does "put a signature block on the Settlement
   PDF" become a normal instance of the same reusable signature-block
   partial (see "What the web backend needs to build" below) used
   everywhere else — one block per approval-chain row, same as it would be
   for Leave or Vacancy once those get PDF signature blocks too.
4. **Sequencing note for whoever plans this**: steps 1-2 are a genuine
   payroll-approval-workflow feature (new table, new controller logic for
   HR/Finance/GM to actually approve/reject a settlement stage-by-stage,
   presumably new UI for that flow) — treat it as its own ticket, not a
   sub-task of "add e-signatures." The signature work for Settlement is
   blocked on it, not bundled with it.

## ⚠ Grievance, Incident, Budget, Performance — also confirmed in scope, same "build the foundation first" caveat as Payroll

You've asked for these four to be included too. Same rule as Payroll
applies to all of them: **none of these four has a PDF/email export today,
and none has a ready-made per-person approval-chain record** the way
Vacancy and Leave do — so for every one of them, this is genuinely two
pieces of work, not one:

- **(a) Build the missing foundation** — a real PDF (or email) export for
  the relevant document in that module, and, for whichever of these
  represent a multi-person approval/consent (not all four necessarily do —
  see per-module notes below), a per-stage approval-chain record shaped
  like `TAnotificationParent`/`TAnotificationChild`.
- **(b) Only then** does adding the signature block become the same small,
  repeatable task described in "What the web backend needs to build"
  below.

Don't let "add e-signatures" tickets for these four quietly turn into
scope for (a) without anyone noticing — (a) is the much bigger piece of
work in every case here. Per-module notes, from what's actually in the
codebase today:

- **Grievance & Disciplinary** — has investigation-stage tracking
  (`DisciplinaryInvestigationParent`/`...Child`) and a configurable set of
  approval roles (`DisciplinaryApprovalRoles`), but the investigation
  records track *notes and stage progress*, not *which specific person
  signed off and when*. Needs a proper per-approver chain, plus a PDF
  export for the case outcome/resolution letter, before signatures apply.
- **Incident** — the best-positioned of the four: `IncidentsEmployeeStatements`
  already stores one row per `employee_id` with their `statement` and a
  `status` — an employee giving a statement is naturally a consent action
  (they're attesting "this is my account of what happened"), so this is
  the most natural first candidate to sign. Still needs (a) a PDF export
  for the statement/incident report, and confirmation of whether a
  timestamp is already captured on that row (worth checking before
  assuming `created_at` is sufficient, since a statement could be edited
  after submission).
- **Budget / Workforce Planning** — `BudgetStatus` records a single
  status + comment per department per budget cycle, not a multi-stage
  chain. Needs the same kind of foundational approval-tracking work as
  Payroll Settlement, plus a PDF export for the approved budget itself
  (neither exists today).
- **Performance (reviews)** — the least-built of the four for this
  purpose: KPI/review-content models exist
  (`PerformanceKpiParent`/`PerformanceKpiChild`, `PerformanceCycle`), but
  there's no reviewer/employee sign-off or approval-chain model at all —
  nothing resembling "manager reviewed and signed this on this date."
  Treat this as starting closer to scratch than the other three.

**All four also have mobile app controllers already**
(`API/GrievanceController.php`, `API/IncidentController.php`,
`API/BudgetCostController.php` + `API/ManningController.php`,
`API/PerformanceReviewController.php`) — see the mobile doc for the
corresponding note on gating their approval/consent actions once the
foundational work above lands.

## What the web backend needs to build (summary of net-new work)

1. **A reusable "signature block" PDF partial** — one Blade partial taking
   `{name, signatureImagePath, timestamp}` (or a list of these, for a
   multi-approver document), rendered as: signature image, name, "Signed on
   {date} at {time}", and a small caption ("Electronically signed via
   Wisdom AI" or similar — exact wording is a copy decision, not made
   here). Include it in every template listed "In scope" above. Reads from
   the **frozen fields on the approval record** (point 2 below) — never
   from `ResortAdmin.signature_img` directly, since that's the live/current
   value, not the historical one this partial needs.
2. **A signature-snapshot mechanism, used at the moment any approval/
   consent action is recorded** — this is the piece that makes "see the
   signature that was actually used, even 5 years later" real:
   - A small helper (e.g. `Common::snapshotSignature(int $resortAdminId,
     string $approvalContext, int|string $recordId): array`) that, when an
     approval/consent action fires, copies the approver's *current*
     `signature_img` file to a permanent path scoped to that specific
     record (never overwritten, never re-derived later) and returns
     `{name, signatureImagePath, timestamp}` for the caller to store.
   - Every approval-recording write (a new `TAnotificationChild` row, a
     Settlement approval-stage row once it exists, a Leave approval action,
     a candidate's Offer Letter accept, etc.) calls this **once, at the
     moment that row is created** and stores the returned path + name +
     timestamp directly on that row (or a small side table keyed to it).
   - The reusable signature-block partial (point 1) reads only from these
     stored fields. If an approver later re-uploads a new signature or is
     removed from the system, past approval records are completely
     unaffected — they already have their own permanent copy.
   - Blank/missing-signature handling stays the same as today's proven
     pattern (block the action with "Authorized signature is missing.
     Please upload it first from your profile page." — see above) — this
     just adds "and copy it permanently" to what already happens at that
     moment.
3. **The background-removal + confirm-before-save upload flow** on the
   existing Profile page (`workforce_planning/profile.blade.php`) — see the
   mobile doc for the full UX spec, since the flow needs to be identical on
   both platforms; only the endpoint differs (web already has
   `resort.Updateuser.profile`, this needs the new
   "generate preview / confirm / retry" round-trip added to it).
4. **Background-removal implementation choice** — `intervention/image`
   (`^2.7`) is already a project dependency and can do simple
   threshold-based removal for a signature photographed on plain white
   paper (convert to grayscale, threshold, make near-white pixels
   transparent) with no new API dependency. A third-party AI
   background-removal API (remove.bg, Clipdrop, etc.) would handle messier
   photos (shadows, off-white paper, uneven lighting) more robustly but
   adds a paid external dependency and a network call to the upload flow.
   Neither is wired today — this is a real choice to make, not a "just
   turn it on" toggle.
5. **Fix along the way (small, same area)**: `LetterheadSetting::
   imageAbsolutePath()`/`imageUrl()` use raw `public_path()`/`asset()`
   instead of `StorageHelper` — per this app's own storage rules, that
   silently breaks on the production Wasabi disk. Worth fixing while
   whoever picks this up is already touching signature-image resolution
   code, even though it's a pre-existing, unrelated bug.

## Verification note

Everything in the tables above was confirmed by reading the actual
controller/template code (grep + direct file reads), not inferred from
names. Where I could not find a PDF export for a module, I searched for
`Dompdf`/`PDF::loadView`/`Pdf::loadView` across that module's controllers
directly — absence means "not found in this codebase today," not "assumed
absent."
