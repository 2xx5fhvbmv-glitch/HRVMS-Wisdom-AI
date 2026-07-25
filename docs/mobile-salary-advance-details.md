# Salary Advance — Request Details API

For the mobile team. Ticket: request details screen missing currency,
guarantor info, repayment schedule, and deduction history; unnecessary
Employee ID shown.

## `GET request/salary-advance-details/{id}`

Auth: same bearer token as other mobile endpoints. `{id}` is the numeric
`payroll_advance.id` (from `request-dashboard`'s listing).

**Response:**
```json
{
  "success": true,
  "message": "Salary advance details fetched successfully",
  "data": {
    "request_information": {
      "requested_date": "2026-07-23",
      "status": "Pending",
      "priority": "High",
      "amount": "234.00",
      "currency": "MVR",
      "purpose": "...",
      "recovery_status": "Pending"
    },
    "guarantor_information": {
      "guarantor_name": "Dmitri Petrov",
      "guarantor_approval_status": "Approved",
      "guarantor_approval_date": "12/07/2026 05:23"
    },
    "repayment_schedule": [
      { "month": "August 2026", "amount": "100.00", "remark": "First installment" }
    ],
    "deduction_history": [
      { "payroll_month": "August 2026", "deducted_amount": "100.00", "status": "Completed" }
    ]
  }
}
```
`{"success": false, "message": "Request not found."}` if the id doesn't
belong to the calling employee.

## Field notes

- **No Employee ID** in this response, per the ticket — it's implicit
  (you're viewing your own request).
- **`guarantor_information`** is `null` if the request has no guarantor
  assigned. `guarantor_approval_date` is derived from the guarantor row's
  `updated_at` — there's no dedicated approval-timestamp column in the
  schema, so it's `null` until the guarantor has actually approved/rejected
  (a still-`Pending` guarantor row never touched `updated_at` meaningfully).
- **`repayment_schedule`** and **`deduction_history`** read from the same
  underlying table (`payroll_recovery_schedule`) the web portal's Loan
  Repayment Tracker uses — the web "Deduction History" section is a
  relabeling of the same rows, not a separate ledger. Expect both arrays to
  always contain the same entries, just under different key names
  (`month`/`amount`/`remark` vs `payroll_month`/`deducted_amount`/`status`).
  `status` in `deduction_history` is `"Completed"` when the schedule row's
  raw status is `"Paid"`, otherwise `"Pending"`.
- Both arrays are empty (`[]`) if no repayment schedule has been generated
  for the request yet (e.g. still pending approval).

Verified end-to-end against a real request with a seeded repayment
schedule in a rolled-back test.
