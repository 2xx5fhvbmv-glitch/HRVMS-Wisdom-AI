# Extend an Approved Leave — Mobile API

For the mobile team. Ticket: line worker wants to extend a leave that's
already been approved.

## Design

No infrastructure existed for this before. Rather than building a separate
amendment/approval system, an "extension" is just a **normal new leave
request** for the additional days, tagged back to the original approved
leave via a new `extends_leave_id` column. It goes through the exact same
approval chain, notifications, and leave-balance logic every other leave
request already uses — nothing new to learn on the approver side.

## `POST resort/leave-add` — now accepts `extends_leave_id`

Same endpoint and fields as a normal leave request (see
`docs/mobile-approval-workflows.md` section 1), plus one new optional field:

```json
{
  "leave_category_id": [58],
  "from_date": ["2025-12-03"],
  "to_date": ["2025-12-04"],
  "reason": "Extending my approved leave by 2 days",
  "extends_leave_id": 240
}
```

- `extends_leave_id` — the id of the **already-approved** leave being
  extended. Must belong to the calling employee and have
  `status = "Approved"`, or the request is rejected:
  - Not found / not yours: `{"success": false, "message": "The leave you are trying to extend was not found."}`
  - Not yet approved: `{"success": false, "message": "You can only extend a leave that has already been approved."}`
- Submit the **additional date range only** (e.g. the original was Dec 1–2,
  the extension is Dec 3–4) — not the full combined range. The existing
  overlapping-date-range check will reject a resubmission of dates that
  already belong to another leave, including the original one you're
  extending.
- Response on success is the same as any other leave submission:
  `{"success": true, "message": "Leave application submitted successfully!"}`

## Viewing an extension request

`GET resort/view-leave-request/{leave_id}` (and the HR/HOD equivalent) now
includes an `original_leave` field when the viewed request is an extension:

```json
{
  "status": true,
  "message": "Leave Details",
  "leave_request": {
    "id": 316,
    "extends_leave_id": 240,
    "from_date": "2025-12-03",
    "to_date": "2025-12-04",
    "...": "...all the normal leave detail fields...",
    "original_leave": {
      "id": 240,
      "from_date": "2025-12-01",
      "to_date": "2025-12-02",
      "total_days": 2,
      "status": "Approved"
    }
  }
}
```
`original_leave` is `null` for a normal (non-extension) leave request.

Verified end-to-end against a real approved leave in a rolled-back test —
ownership/status validation, record creation, and this enrichment all
confirmed working.
