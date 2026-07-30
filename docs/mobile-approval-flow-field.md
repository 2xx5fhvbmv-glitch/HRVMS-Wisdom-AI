# `approval_flow` — Standardized Multi-Step Approval Payload

For the mobile team. Covers the new `approval_flow` field added to every Leave
and Island Pass API response, why it's shaped the way it is, and the two
confirmed business rules behind it. See also
[`mobile-approval-workflows.md`](mobile-approval-workflows.md) for the
underlying per-rank approval-chain rules this field surfaces.

## What changed

Every Leave and Island Pass object returned by the endpoints below now
carries, alongside all pre-existing fields (`approve_data` etc. — unchanged,
kept for compatibility):

```json
{
  "approval_flow": {
    "0": {
      "step": 0,
      "approver_id": "177",
      "name": "Priya Sharma",
      "position": "Food And Beverage Manager",
      "rank_type": "HOD",
      "status": "Pending",
      "comment": null,
      "approved_at": null,
      "can_approve": true,
      "can_reject": true,
      "is_current_step": true
    },
    "1": {
      "step": 1,
      "approver_id": "176",
      "name": "Rajesh Patel",
      "position": "Executive Chef",
      "rank_type": "EXCOM",
      "status": "Pending",
      "comment": null,
      "approved_at": null,
      "can_approve": false,
      "can_reject": false,
      "is_current_step": false
    }
  },
  "can_approve": true,
  "can_reject": true,
  "current_pending_rank": "HOD",
  "overall_status": "Pending"
}
```

`approval_flow` is a JSON **object** keyed `"0"`, `"1"`, ... in real execution
order (see ordering rule below) — not an array. Each step's own
`can_approve`/`can_reject`/`is_current_step` describe whether *that stage's
approver* could act right now, regardless of who's asking. The **top-level**
`can_approve`/`can_reject` are relative to the **logged-in viewer** — use
these to show/hide your own action buttons without re-deriving anything.
`current_pending_rank` is the `rank_type` of whichever step is current, or
`null` once the flow has resolved. `overall_status` is `Pending`, `Approved`,
`Rejected`, or `Cancelled` (Island Pass only).

## Two things confirmed before building this (don't assume otherwise)

1. **Island Pass order is `HOD → HR → SM`** — 3 steps. There is no MGR or SO
   stage in the backend at all; if your UI currently assumes a 5-step order,
   update it. HOD acts first even though its row is technically inserted
   last (highest id) — the backend already accounts for this, you just
   consume the array in the order given.
2. **No rank override / skip-ahead, on either module.** Only the approver on
   the current step can act — a higher rank cannot jump ahead of a still-
   pending earlier step. If you were planning UI that lets e.g. an HOD
   approve before HR finishes, that's not how the backend enforces it today;
   `can_approve` will be `false` for every step except the current one, full
   stop.

## Endpoints carrying `approval_flow`

**Leave** (`app/Http/Controllers/API/LeaveController.php` unless noted):
- `GET resort/leave-dashboard` — on all 3 sub-objects (`leave_request`, `my_leave_request`, each `leave_history` item)
- `GET resort/leave-dashboard-hr`
- `GET resort/leave-dashboard-hod`
- `POST resort/leave-request`
- `POST resort/employee-leave-request-list`
- `POST resort/employee-leave-request-view/{id}` — **also fixed**: previously only returned the viewer's own stage row, so the timeline UI had nothing to render for other stages. Now returns the full chain.
- `GET employee-management/leave-request-list` (`EmployeeManagementController`) — previously had no approval data at all.
- `POST resort/handle-leave-action` — every response branch (approve, reject, already-actioned, wrong-approver, invalid action) now includes the post-action `approval_flow` for the leave just acted on. No more separate re-fetch needed after approving/rejecting.

**Island Pass** (`app/Http/Controllers/API/BoardingPassController.php` unless noted):
- `GET boarding/boarding-emp-dashboard`
- `GET boarding/boarding-hod-dashboard`
- `GET boarding/boarding-hr-dashboard`
- `GET boarding/boarding-sm-dashboard`
- `GET boarding/boarding-pass-view/{id}` — on both `borading_pass_details` and `view_display`
- `GET resort/island-pass-view-hr` (`LeaveController`) — **also fixed**: the per-stage `status` field was silently echoing the parent pass's own status instead of each stage's real status; now correct.
- `GET resort/island-pass-view-hod` (`LeaveController`) — same status fix, plus the same "only my own row" gap `employee-leave-request-view` had — `approval_flow` (not the older `approve_data`) always shows the full chain regardless.
- `POST boarding/boarding-pass-approve-action` — every response branch now includes post-action `approval_flow`, same as `handle-leave-action`.

## Things to know that aren't bugs

- **A stage can show `status: "Pending"` while `overall_status` is already
  resolved.** This happens after an HR/GM (Leave) or GM-equivalent (Island
  Pass) terminal-rank approval shortcut flips the parent immediately without
  updating every remaining stage row. Always trust `overall_status`, not
  whether an individual stage still says Pending — `can_approve`/`can_reject`
  are already `false` on every step once `overall_status` isn't `Pending`,
  precisely to prevent this from being misread as still-actionable.
- **Clinic-staff (Leave rank 12) never shows `can_reject: true`.** They can
  approve/comment on a leave stage, but a reject from them never flips the
  leave to Rejected (informational only) — so `can_reject` is hard-suppressed
  for that stage to avoid implying otherwise. No Island Pass equivalent.
- **`rank_type` can legitimately repeat across steps** (e.g. two "HOD"
  entries) when a resort's HR or Security Manager position happens to be
  filled by someone whose *personal* rank is also HOD-level. Island Pass
  uses `approver_role` (the functional stage) as the label whenever it's
  set; older rows created before that column existed fall back to the
  personal-rank-derived label, which is where the coincidence shows up.
- **`approval_flow` can be an empty object `{}`** if a leave/pass genuinely
  has no stage rows recorded (a data gap on a handful of legacy records, not
  something you need to handle specially beyond "nothing to render").
