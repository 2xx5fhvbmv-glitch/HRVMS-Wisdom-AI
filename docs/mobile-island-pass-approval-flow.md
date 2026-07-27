# Island (Boarding) Pass — Approval Flow, End to End

For backend/mobile dev reference. Covers submit → approval chain → per-role
dashboard filtering → approve/reject action → completion. Written for
"Issue 1: Island Boarding Pass – Dashboard Not Updating" but documents the
whole flow since that's what was asked for.

## Data model

Two tables:

- **`employee_travel_passes`** — one row per pass (the request itself).
  `status`: `Pending` / `Approved` / `Rejected` / `Cancel`. Holds
  arrival/departure date, time, mode, reason.
- **`employee_travel_pass_status`** — one row **per approver** in the
  chain for that pass. Columns: `travel_pass_id`, `approver_id`,
  `approver_rank` (approver's personal org rank), `approver_role`
  (`HOD`/`HR`/`SM` — which functional stage this row represents,
  independent of personal rank), `status` (`Pending`/`Approved`/`Rejected`),
  `comments`, `approved_at`.

A pass always has an ordered chain of these status rows. Order is fixed at
creation time: **SM → HR → HOD** (created in that order — see below), and
approval always resolves the **most recently created still-Pending** row
first (`orderBy('id', 'desc')`), i.e. the chain runs in reverse creation
order: HOD acts first, then HR, then SM last.

## 1. Submit — `POST boarding/boarding-pass-add`

`BoardingPassController::boardingPassAdd()`. On success:

1. Creates the `employee_travel_passes` row, `status = Pending`.
2. Builds the approver chain by looking up, in this order: SM (by resort
   position title "Security Manager"/"SM"), HR (`Common::getResortHrEmployeeIds()`),
   HOD (`rank = 2` in the employee's own department). Each lookup is
   `status = Active` + `orderBy('id')` only — so it always resolves the one
   real active person, never an inactive/onboarding placeholder.
3. For each approver found, creates one `employee_travel_pass_status` row,
   `status = Pending`, tagged with that approver's `approver_role`.
4. Sends a push notification to **every** approver in the chain immediately
   (not just the first) — each of them can see the request is pending, but
   only the HOD's action actually unblocks anything (see next section).
5. Returns the created pass (with resolved transportation) in `data`.

## 2. Per-role dashboards — who sees what

Each role has its own dashboard endpoint:

- `GET boarding/boarding-hod-dashboard` → `boardingHODDashboard()`
- `GET boarding/boarding-hr-dashboard` → `boardingHRDashboard()`
- `GET boarding/boarding-sm-dashboard` → `boardingSecurityManagerDashboard()`

All three follow the same filter shape on `pass_request`:

```php
EmployeeTravelPass::whereHas('employeeTravelPassStatusData', function($q) use ($currentRank) {
        $q->where('approver_rank', $currentRank)
          ->where('status', 'Pending');
    })
    ->where('status', 'Pending')   // pass itself still open
    ->get();
```

**This means:** a pass only shows up on a given dashboard while (a) the
pass itself is still `Pending` overall, **and** (b) that specific role's
own status row is still `Pending`. Once that role's row flips to
`Approved`/`Rejected`, the pass drops off **that role's** dashboard on the
next fetch — it does not need any extra "hide" logic, the `whereHas`
filter handles it automatically because the row it's matching against no
longer satisfies `status = 'Pending'`.

Each stage's `rank_type` label (shown per approver in `pass_request[].employeeTravelPassStatusData[]`)
comes from `approver_role` directly now (`HOD`/`HR`/`SM`), not from the
approver's personal rank — fixed earlier this session, see
`mobile-island-pass-approver-fixes.md`.

## 3. Approve / reject — `POST boarding/boarding-pass-approve-action`

`boardingPassApprovedAction()`. Body: `pass_id`, `action` (must be exactly
`Approved` or `Rejected` — enum-validated), `reason` (required if
`Rejected`).

Logic:

1. Finds the **current** pending stage: `EmployeeTravelPassStatus::where('travel_pass_id', $passId)->where('status', 'Pending')->orderBy('id', 'desc')->first()`.
   If none — pass is already fully approved, returns early.
2. If the row belongs to someone else (`approver_id != caller`), returns
   `"You cannot {approve/reject} this request. It is currently awaiting
   action from the {role}."` — this is the message reported in the
   "Incorrect HOD Validation" ticket; root cause was a wrong FK in a
   different controller (`LeaveController`), already fixed, see
   `mobile-island-pass-approver-fixes.md` root cause 3.
3. If the caller already actioned their own row previously, returns
   `"You have already {approved/rejected} this request. It is now awaiting
   action from the {role}."` instead of a generic error.
4. Updates that one row: `status = action`, `comments`, `approved_at`.
5. If **all** status rows are now `Approved`, flips the pass itself to
   `Approved`.
6. Sends a notification to the pass owner (approved/rejected), and — if
   there's a next Pending row — a "your approval is required" push to the
   next approver in the chain.

**Nothing here writes back to any list the mobile app might have cached.**
The endpoint's response is just a status confirmation
(`{"status": true, ...}` or `{"success": true, "message": "..."}`) — it
does not push updated dashboard data to the client.

## Issue 1 — why the HOD's own list didn't visually update

Reported symptom: after tapping Approve on the HOD dashboard, the same
pass keeps showing Approve/Reject buttons until the screen is manually
left and reopened.

Traced against the code above: step 3.4 flips the HOD's own
`employee_travel_pass_status` row to `Approved` in the same request that
returned the approve confirmation. The **next** call to
`boarding-hod-dashboard` will not include that pass anymore — the
`whereHas(...->where('status','Pending'))` filter in section 2 no longer
matches it. This was verified directly against the query, not assumed.

**What's needed client-side:** call `boarding-hod-dashboard` again (or
locally remove/update that item from the list) right after
`boarding-pass-approve-action` returns success — don't leave the
previously-loaded list on screen and expect it to reflect the approval
without a new fetch. There is no backend change to make for this one; the
data returned by the next fetch is already correct.

## Quick reference — status meanings

| Where | Value | Meaning |
|---|---|---|
| `employee_travel_passes.status` | `Pending` | at least one approver stage still open |
| | `Approved` | every stage row approved |
| | `Rejected` | action set to Rejected at some stage — chain stops there |
| | `Cancel` | emergency-cancelled (`boarding/emergency-cancel-pass`) |
| `employee_travel_pass_status.status` (per stage) | `Pending` | this approver hasn't acted yet |
| | `Approved` | this approver approved their stage |
| | `Rejected` | this approver rejected — pass-level status also becomes `Rejected` |
