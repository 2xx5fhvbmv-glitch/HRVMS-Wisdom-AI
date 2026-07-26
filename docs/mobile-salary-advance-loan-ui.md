# Request Module — Salary Advance/Loan Form & Card UI

For mobile team. Reply to "Salary Advance/Loan Form and Card UI Improvements".

## 1. Guarantor Employee field has no search

Real gap — there was no endpoint at all for this picker to search against.

**New:** `GET request/guarantor-employee-list?search=<text>` (auth required).
Returns active resort employees excluding the caller, matching `search`
against first name, last name, or `Emp_id`. Omit `search` for the full list.

```json
{
  "success": true,
  "message": "Employee list fetched successfully",
  "data": [
    { "id": 171, "Emp_id": "DR-2", "first_name": "Ibrahim", "last_name": "Manik",
      "position_title": "Director Of Finance", "employee_name": "Ibrahim Manik",
      "profile_picture": "https://..." }
  ]
}
```

Wire the picker to this instead of whatever full-list source it currently
uses, and filter as the user types via `?search=`.

## 2. Currency selection (MVR/USD)

Already supported — `RequestStore` (`request/request-store`) has accepted
`currency: 'nullable|in:MVR,USD'` since earlier this session, and your own
log shows it working (`"currency": "MVR"` on the newly-submitted request).
Older requests (e.g. id 126, submitted before this field existed) show
`currency: null` — that's historical data, not a bug; nothing to do for
those rows.

## 3. Upload File not working

Your own log shows it actually working end-to-end: `request-store`'s
response includes `payroll_advance_attachment` with a real `Child_id`
(211). The real gap was on the **read side** — `request-dashboard` never
included the `attachments` field per request, so a successfully-uploaded
file was invisible again after submit. That was already fixed earlier
this session (commit `f7bd7051f`) but hadn't been deployed yet — it's why
your test still showed no attachments back. It's live on this branch now
and every request in the list carries:

```json
"attachments": [{ "filename": "sample_image_-Google_Search.pdf", "url": "https://..." }]
```

## 4. Unclear/incomplete date on request card

Backend already sends a full date+time, not just a year —
`created_at`/`updated_at` are formatted per resort settings (e.g.
`"23/07/2026 22:32"`), and `request_date` is a full ISO date
(`"2026-07-23"`). If the card is showing just a year, that's a client-side
formatting/truncation choice, not a backend gap — nothing to change here
unless you tell me a specific field is missing.

## 5 & 6. Card shows too little info / tapping card shows nothing more

`GET request/salary-advance-details/{id}` already exists for the detail
view (built earlier this session) and just got two real fixes:

- It only ever returned **one** guarantor (`hasOne(...)->latest()`), even
  though a request can have multiple (`guarantor_id` is submitted as an
  array). Now returns `guarantors_information` as an **array**, one entry
  per guarantor.
- It never returned uploaded files at all. Now includes `attachments`
  (same `{filename, url}` shape as the dashboard).

Full response shape now:
```json
{
  "success": true,
  "data": {
    "request_information": { "requested_date", "status", "priority", "amount", "currency", "purpose", "recovery_status" },
    "guarantors_information": [
      { "guarantor_id", "guarantor_name", "guarantor_approval_status", "guarantor_approval_date" }
    ],
    "attachments": [{ "filename", "url" }],
    "repayment_schedule": [{ "month", "amount", "remark" }],
    "deduction_history": [{ "payroll_month", "deducted_amount", "status" }]
  }
}
```
Note: `guarantors_information` replaces the old singular `guarantor_information`
key — this endpoint was only added this session and hasn't shipped to
prod, so this is a clean rename, not a breaking change against a live
client.

For the request-dashboard card summary, `requests[]` already includes
`request_amount`, `currency`, `pourpose`, `priority`, and now `attachments`
— enough to build a richer card without a second call.

## 7. Add (+) button missing from Request dashboard

Pure client-side placement — the endpoints for creating a request
(`request/request-store`) don't change based on which screen the button
lives on. Add the button to the dashboard screen same as it already
appears under View All.

## Summary for backend vs. app dev

| # | Issue | Fix |
|---|---|---|
| 1 | Guarantor search | New endpoint added (backend, done) |
| 2 | Currency select | Already supported (backend, done) |
| 3 | Upload file "not working" | Was a read-side gap, fixed + deployed to this branch (backend, done) |
| 4 | Date format | Backend already sends full date — client rendering (app dev) |
| 5/6 | Card info / detail view | Detail endpoint fixed for multi-guarantor + attachments (backend, done) — wire card tap to `salary-advance-details/{id}` (app dev) |
| 7 | Add (+) button placement | Client-side only (app dev) |
