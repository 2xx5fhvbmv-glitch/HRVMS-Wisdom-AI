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
  `qr_code`/similar field on `VisaEmployeeExpiryData`. If the Figma design's
  "Scan QR Code" element is meant to encode something (e.g. a verification
  link, or the extracted Work Permit Number), that needs a decision on what
  it should contain before I can add it — same convention question as the
  Third-Party Shop QR ticket. Let me know what it should encode and I'll
  wire it up.

Verified against real resort 26 data — an employee with an actual uploaded
Work Permit Entry Pass document and its real AI-extracted field.
