# Guarantor Workflow — Salary Advance / Loan Request

For the mobile team. Ticket: guarantor's requester photo bug, missing
notifications, need all-status list, currency in GET.

## `GET request/request-guarantor-list` — the guarantor's queue

Auth: bearer token. Optional query param:

| Param | Values | Behavior |
|---|---|---|
| `status` | omitted | **Default** — Pending only (unchanged from before, so anything already relying on this as an action queue keeps working) |
| `status` | `all` | Every status (Pending/Approved/Rejected/Hold) |
| `status` | `Approved` / `Rejected` / etc. | Filter to that one specific status |

**Response:**
```json
{
  "success": true,
  "message": "Request List",
  "data": {
    "requests": [
      {
        "id": 132,
        "payroll_advance_id": 132,
        "guarantor_id": 189,
        "status": "Pending",
        "request_type": "Salary Advance",
        "request_amount": "234.00",
        "currency": "MVR",
        "request_date": "2026-07-23",
        "first_name": "Rani",
        "last_name": "Khan",
        "guarantor_profile_picture": "https://.../DR-20/Profile/pic.png",
        "Emp_id": "DR-20",
        "request_data": {
          "id": 132,
          "employee_id": 174,
          "request_type": "Salary Advance",
          "request_amount": "234.00",
          "currency": "MVR",
          "pourpose": "test",
          "status": "Pending",
          "priority": "High",
          "Emp_id": "DR-5",
          "first_name": "Dmitri",
          "last_name": "Petrov",
          "department_name": "F and B Service",
          "emp_profile_picture": "https://.../DR-5/Profile/pic.png"
        }
      }
    ]
  }
}
```

## What changed

1. **Requester photo bug (fixed)** — `request_data.emp_profile_picture` was
   returning the *guarantor's own* photo for every request, because it
   reused the outer row's `Admin_Parent_id` instead of the requester's own.
   Now correctly resolves the requester's (Dmitri's, in the example above)
   photo — it will now differ from the top-level `guarantor_profile_picture`
   whenever the requester and guarantor are different people (always, by
   definition).
2. **Currency (fixed)** — `currency` is now present at the **top level**
   (previously only inside `request_data`). Both copies will always match.
3. **All-status list (added)** — see the `status` query param above.
4. **Guarantor notification on new request (fixed)** — previously only HR
   was notified when a request was submitted; the guarantor named on it was
   never told. Now the guarantor gets a push/in-app notification
   ("{name} has named you as a guarantor for a {type} request") the moment
   the request is created. Approve/reject notifications already existed and
   are unchanged.

## `POST request/guarantor-request-handle` — approve/reject

Unchanged shape:
```json
{"payroll_advance_id": 132, "guarantor_request_id": 45, "status": "Approved"}
```
→ `{"success": true, "message": "Request Approved Successfully."}`

Verified against real distinct requester/guarantor employee pairs — the
photo fix resolves a different (correct) `Admin_Parent_id` than the old
code would have; `status=all` and the default Pending-only filter both
confirmed against real data.
