# Island Pass — Approver Resolution Fixes

For the mobile team. Covers three related tickets: "HOD Not Receiving
Request Data", "Incorrect HOD Validation After Approval", and general
confusion about stage labels ("Pending by HOD" not updating).

## Root cause 1: stage label derived from the wrong thing (fixed)

`rank_type` (the label shown for each approval stage — "HOD"/"HR"/"Security
Manager") was computed from the approver's own **personal** organizational
rank (`approver_rank`), not from which **stage** of the chain they actually
represent. An HR department head or a Security Manager can personally hold
rank 2 ("HOD") the same as a real department HOD — confirmed with real
data: a pass's three approvers (HOD/HR/SM) all had `approver_rank = 2`, so
every single stage showed "HOD" regardless of which one was actually
pending.

**Fix:** added a new `approver_role` column, set explicitly at submission
time from which lookup (HOD-by-department, HR, or Security-Manager-by-
position) actually found that approver — independent of their personal
rank. All stage-label computations now prefer this field. This directly
fixes the `current_status_label` field on `boarding-emp-dashboard`
(documented in `mobile-approval-workflows.md`) actually saying "Pending by
HR" / "Pending by Security Manager" once the HOD has approved, instead of
staying stuck on "Pending by HOD".

Note: passes created **before** this fix keep the old (potentially
mislabeled) fallback — the fix applies going forward, not retroactively,
since the correct historical stage can't be reliably re-derived.

## Root cause 2: approver lookup could resolve an inactive placeholder (fixed)

`boardingPassAdd()`'s HOD/HR/Security-Manager lookups had no `status =
'Active'` filter and no deterministic ordering. Confirmed with real data:
one department had 3 employees with `rank = 2` — one real Active HOD, two
`Onboarding`-status placeholder records. Nothing prevented an onboarding
placeholder from being resolved as "the HOD approver" for a new pass
instead of the real one — which would explain both a HOD never being
notified of a request that was organizationally theirs, and later seeing
"you cannot approve — awaiting the HOD" even though they *are* the HOD
(because the pass's actual `approver_id` pointed at someone else).

**Fix:** added `where('status', 'Active')` and `orderBy('id')` to all three
lookups, so the result no longer depends on implementation-defined query
ordering or includes not-yet-active employees.

## Still open — worth checking on your end

The exact repro in "Incorrect HOD Validation" showed the message *"You
cannot approve this. The request must first be approved by the HOD."* —
that specific string only exists in the **Leave** approval endpoint
(`LeaveController::handleLeaveAction`), not the Boarding Pass endpoint
(which says *"...currently awaiting action from the..."*, worded
differently). If you still see the exact Leave-worded message on an Island
Pass screen after these fixes, it likely means that specific request is a
combined Leave+Island-Pass submission (travel details attached to a leave
application create both records) and the Approve/Reject tap on the mobile
Island Pass card is actually hitting the Leave endpoint for it, not the
Boarding Pass one — worth confirming which endpoint that button calls for
this request type.
