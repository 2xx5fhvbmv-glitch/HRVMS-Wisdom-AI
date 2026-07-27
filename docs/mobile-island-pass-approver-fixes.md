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

## Root cause 3: wrong foreign key in the Leave-linked Island Pass path (fixed)

This is the real explanation for the exact repro in "Incorrect HOD
Validation" (Rani Khan / F&B, message *"You cannot approve this. The
request must first be approved by the HOD."*) — that exact string only
exists in the **Leave** endpoint, which is a strong hint the request was a
**combined Leave + Island Pass submission** (a leave application that also
includes travel dates creates both records together). That combined-
creation code lives in `LeaveController::leaveAdd()`/`leaveUpdate()` — a
third, separate copy of the same approver-assignment logic covered by
Root Cause 2 above, not `BoardingPassController::boardingPassAdd()`.

While hardening this third copy, found something more serious than a
missing status filter: `leaveUpdate()`'s version was writing
`employee_travel_pass_status.travel_pass_id` from **`$entryPass->id`** —
which is `EmployeesLeaveTransportation` (a transportation-entry row from
an unrelated loop a few lines earlier), not `EmployeeTravelPass` (the
actual pass just created in the same block). Confirmed with real data:
these are two completely different tables with unrelated ids (e.g. 67 vs
504 in testing) — so every leave update that included travel dates was
writing its approval-chain rows against the wrong pass id entirely (or a
nonexistent one).

This is a far more direct explanation for the "stray wrong-department HOD
approver" pattern than a department mismatch — it explains why two prior
one-off data migrations
(`2026_07_16_010000_purge_wrong_department_pending_hod_travel_pass_approvers.php`,
`2026_07_19_010000_purge_stray_wrong_department_hod_travel_pass_approvers.php`)
kept finding new instances of it: the code that created them was never
fixed, only the symptoms were cleaned up reactively each time.

**Fix:** corrected the FK to `$boardingPass->id`, and applied the same
Active-only/deterministic-ordering/`approver_role` hardening from Root
Causes 1–2 to this third copy, including fixing its HR lookup (it still
had the old raw `rank = 3` check, not `getResortHrEmployeeIds()`).

Passes created via this path **before** this fix may still have a
corrupted `travel_pass_id` on their status rows — if a specific historical
pass still misbehaves after this, share its id and it can be checked/
cleaned up individually the same way the two prior migrations did.
