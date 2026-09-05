# Visa Information Screen — Mobile API

For the mobile team. Covers the "Visa Information" list (Employment Entry
Pass, Visa, Work Permit Card, Medical Report, Insurance, etc.) and each
category's detail screen.

Both endpoints already exist — `app/Http/Controllers/API/ProfileController.php`.

## `GET profile/visa-category` — the list

**Request:** no params.

**Response:**
```json
{
  "status": true,
  "message": "Visa categories fetched successfully",
  "visa_category": {
    "Insurance": "Insurance",
    "Passport_Copy": "Passport Copy",
    "Work_Permit_Entry_Pass": "Work Permit Entry Pass",
    "Visa": "Visa",
    "Work_Permit_Card": "Work Permit Card",
    "Medical_Report": "Medical Report"
  }
}
```
Key = the value to pass to `visa-data/{key}` below; value = the display
label for the row (e.g. render "Work Permit Entry Pass" for the row, but
call `visa-data/Work_Permit_Entry_Pass`). This list comes from a config
array (`settings.VisaCategory`) — if you need more categories added (the
screenshot also showed "Xpat Insurance"/"Health Insurance" as separate
rows, which aren't distinct keys here — only `Insurance` exists), say so
and I'll add them.

## `GET profile/visa-data/{visa_category}` — one category's detail

`{visa_category}` is one of the keys from the list above (e.g.
`Work_Permit_Entry_Pass`).

**Real verified response** (employee 202, category `Work_Permit_Entry_Pass`):
```json
{
  "success": true,
  "message": "Visa data fetched successfully",
  "visa_data": {
    "id": 4,
    "resort_id": 26,
    "employee_id": 202,
    "File_child_id": null,
    "DocumentName": "Work_Permit_Entry_Pass",
    "file_url": { "success": false, "NewURLshow": null, "mimeType": null },
    "Ai_extracted_data": {
      "extracted_fields": [
        { "Title": "Last Entry Allowed", "Value": "2026-06-12" }
      ]
    }
  }
}
```
`{"success": false, "message": "No visa data found for this category"}` if
the employee has no document uploaded for that category yet.

**Key structural points:**
- `Ai_extracted_data.extracted_fields` is an **array of `{Title, Value}`
  objects**, not a flat key-value map — build the detail screen (Name /
  Nationality / Passport Number / Work Permit Number / etc.) by iterating
  this array and rendering each `Title` as a label, `Value` as the value.
  The actual fields present vary per document — whatever the AI extraction
  pulled from that specific uploaded file. For a Work Permit Card, expect
  fields like `Name`, `Nationality`, `Passport Number`, `Date of Birth`,
  `Profession`, `Work Permit Number`, `ID expiry Date` (matching the
  Figma design) — but don't hardcode an exact field list; render whatever
  comes back.
- `file_url.NewURLshow` is the resolved document URL (for a "view original
  file" action) — `null` if no file was ever uploaded for this category (as
  in the example above) or if resolution failed.
- **QR code**: neither endpoint returns a QR value — there's no
  `qr_code`/similar field on `VisaEmployeeExpiryData`. Not implemented yet;
  build the screen without it for now, we'll add the field once what it
  should encode is decided.

Verified against real resort 26 data — an employee with an actual uploaded
Work Permit Entry Pass document and its real AI-extracted field.

---

## How the document gets there in the first place (web portal → mobile)

Added for the web-portal developer, who asked how a file uploaded in Visa
Management ends up available on the two mobile endpoints above. Read this
section before changing anything.

### Plain-English version

Think of it as three layers, not two separate modules:

1. **File Management is the warehouse.** Every file uploaded anywhere in the
   app (visa docs, grievance attachments, maintenance photos, an employee's
   own personal files) physically lands in the same two tables —
   `filemangement_systems` (folders) and `child_file_management` (files) —
   through one shared helper, `Common::AWSEmployeeFileUpload()`. Every
   employee already has their own root folder there (created automatically
   the moment their employee record is created).
2. **Visa Management is one of several "departments" that use the
   warehouse.** When HR uploads/renews a Visa, Insurance, or Medical Report
   document on the Renewal page, the code (a) puts the actual file in the
   warehouse via the helper above, (b) saves the renewal's own numbers
   (dates, policy number, etc.) into its own table (`visa_renewals`,
   `employee_insurances`, `work_permit_medical_renewals`), **and** (c) saves
   a copy of the AI-extracted field data into one shared table,
   `visa_employee_expiry_data`, tagged with which category it is
   (`DocumentName`).
3. **The mobile Visa Information screen reads only from that third,
   shared table** — it doesn't touch `visa_renewals` /
   `employee_insurances` / File Management directly. That's why it's a
   single small, already-working pair of endpoints (above) instead of five
   different ones per document type.

So the two places you mentioned aren't competing — Visa Management is where
staff upload and manage these documents, and File Management is just the
storage engine underneath it that Visa Management (and several other
modules) already shares. There's no missing bridge between them to build.
The visa sub-folders are deliberately hidden from the employee's own
"My Drive" folder browsing on mobile (flagged `is_system_generated`), so a
visa PDF won't also clutter their personal file list — that's correct
existing behaviour, not a bug.

**The actual gap is narrower than "how do we connect these two modules" —
it's that the tag written in step (c) doesn't always spell the category
the same way the mobile screen asks for it.**

### The technical gap

`ProfileController::getVisaData($categoryType)` does an **exact string
match**: `VisaEmployeeExpiryData::where('DocumentName', $categoryType)`.
The category strings mobile will ever send are the six config keys in
`config/settings.php` → `VisaCategory`:

```
Insurance, Passport_Copy, Work_Permit_Entry_Pass, Visa, Work_Permit_Card, Medical_Report
```

But the main day-to-day upload screen — **Visa → Renewal**
(`resources/views/resorts/Visa/Renewal/index.blade.php`), backed by
`RenewalController::UploadSeparetFileUsingAi()` — writes `DocumentName` as
whatever `$doc_type` resolves to at
`app/Http/Controllers/Resorts/Visa/RenewalController.php:310-325`:

```php
$doc_type = $request->flag;                 // "insurance" | "work_permit_card_Test_Fee" | "visa"
if ($doc_type == "insurance") { $doc_type = "insurance"; }
elseif ($doc_type == "work_permit_card_Test_Fee") { $doc_type = "medical_report"; }
elseif ($doc_type == "visa") { $doc_type = "visa"; }
```

That's lower_snake_case (`insurance`, `visa`, `medical_report`), not the
`Insurance` / `Visa` / `Medical_Report` the mobile endpoints look for. A
document renewed on this page today gets correctly stored and shown on the
**web** portal, but `GET profile/visa-data/Insurance` on mobile will report
"No visa data found" for it — the row exists, just under the wrong tag.

### There are actually TWO upload screens — this matters

This app has two separate places that upload into the same shared table,
and they behave differently:

**Screen A — Visa → Xpact Employee → (pick an employee) → "Upload
Document" button.**
Route: `resort.visa.XpactEmpDetails` (`/resort/visa/xpact-employee/details/{id}`),
upload handled by `XpactEmployeeController::EmployeeWiseVisaDocumentUpload()`
(`app/Http/Controllers/Resorts/Visa/XpactEmployeeController.php:891`), view
at `resources/views/resorts/Visa/employee/XpatEmployeeDetails.blade.php:426-474`.
This is a real, deliberate, one-document-at-a-time upload: a modal with a
**"Document Type" dropdown listing all six categories by name** —
Insurance, Passport Copy, **Work Permit Entry Pass**, Visa, Work Permit
Card, Medical Report (`XpatEmployeeDetails.blade.php:438-444`) — plus a
file picker. Whoever uploads picks the exact type from the list, so there's
no ambiguity about what the file is. The controller writes
`VisaEmployeeExpiryData.DocumentName` using that exact dropdown value
(`XpactEmployeeController.php:985-993`), which **already matches** the
`VisaCategory` config keys mobile expects — so **documents uploaded here,
including Work Permit Entry Pass, already sync to the mobile app
correctly today.** This is also the natural place to upload documents at
onboarding time (or any time), since it's per-employee and not tied to a
yearly cycle — nothing new needs to be built for that use case.

**Screen B — Visa → Renewal.** The once-a-year bulk renewal screen (the
one with the auto-detected AI screenshots you described), route/view
`resources/views/resorts/Visa/Renewal/index.blade.php`, upload handled by
`RenewalController::UploadSeparetFileUsingAi()`. This only has **three**
renewal cards — Insurance, Visa, and Medical Report (the "work permit
card" label on its medical card is a leftover naming mix-up in the code,
not an actual Work Permit Card feature) — and, as described below, mis-tags
what it uploads. **This screen has no Work Permit Entry Pass option and
was never meant to** — it's for the three documents that renew annually
with a fixed expiry cycle. Work Permit Card and Work Permit Entry Pass
aren't things you "renew" the same way, which is presumably why they were
built into Screen A instead.

So, to directly answer "where can we deliberately upload Passport Copy /
Visa Copy / Work Permit Copy / Work Permit Entry Pass, one at a time, with
no ambiguity about which is which": **that already exists, at Screen A.**
Nothing needs to be added there for Work Permit Entry Pass specifically.

### The actual bug — scoped to Screen A's sibling, Screen B, only

`ProfileController::getVisaData($categoryType)` does an **exact string
match**: `VisaEmployeeExpiryData::where('DocumentName', $categoryType)`.
Screen B's `UploadSeparetFileUsingAi()` writes `DocumentName` in
lower_snake_case (`insurance`, `visa`, `medical_report` —
`RenewalController.php:310-325`) instead of the `Insurance` / `Visa` /
`Medical_Report` config keys mobile looks for. A document renewed on
Screen B today saves and displays fine on the **web** portal, but
`GET profile/visa-data/Insurance` on mobile will report "No visa data
found" for it — the row exists, just under the wrong tag. Screen A is
unaffected — its dropdown values are already correctly cased.

### Suggested fix (small, no new tables/endpoints, no new screens)

1. In `RenewalController::UploadSeparetFileUsingAi()`
   (`app/Http/Controllers/Resorts/Visa/RenewalController.php:314-325`),
   change the three `$doc_type` assignments to the exact `VisaCategory`
   config keys (`Insurance`, `Visa`, `Medical_Report`) instead of the
   lowercase strings, so every `VisaEmployeeExpiryData::create(...)` call
   in that method (lines ~450, ~516, ~673) writes a `DocumentName` mobile
   can actually query. This is the one change that unblocks the annual
   Renewal screen's three document types on mobile.
2. Grep the codebase for `->where('DocumentName',` and
   `'DocumentName' =>` before shipping, and confirm every write site uses
   one of the six config keys verbatim — don't rely on this doc's list
   being exhaustive.
3. **QR code** (Work Permit Card screen): still unresolved from the
   earlier note above — nothing in `VisaEmployeeExpiryData` or
   `WorkPermit` holds a QR value today. Needs a decision on what it should
   encode (a verification link? the Work Permit Number as plain text?)
   before any code is written. `simplesoftwareio/simple-qrcode` is already
   a project dependency (used elsewhere, e.g. Shopkeeper), so generating
   the image itself is a non-issue once the content is decided.

None of this needs a new table, a new endpoint, or a new screen — Screen A
already covers deliberate per-document upload for all six types including
Work Permit Entry Pass, and Screen B just needs its three tags corrected
to match.
