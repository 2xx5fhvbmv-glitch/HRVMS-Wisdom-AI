# Security audit — deliberately deferred items

These are not "not done because of time" — each one was looked at and skipped
for a specific reason: it needs a product/schema decision, a source document
that isn't available, or carries real risk of breaking something else if
guessed at. Companion to `SECURITY_AUDIT_REMAINING.md` (which tracks
everything else from the audit, fixed or still open).

## PE-04 — "~50 tables print raw employee name" (XSS hardening)

**Claim:** employee names are echoed unescaped across roughly 50 DataTables
across the codebase.

**Why deferred:** `grep -rl "rawColumns" app/Http/Controllers/Resorts/` hits
**88 files**. Finding which of those 88 actually echo a *name* field raw
(vs. a genuinely-safe computed HTML column) needs either the original
audit's specific file/line list, or a manual read of every one of those
files — not something a blind grep can responsibly finish without risking a
wrong fix or a missed one. Two prior sessions independently reached the same
conclusion and declined to guess.

**Real-world risk level:** lower than it sounds. The self-edit angle this
finding's claim leaned on was checked and does **not** hold — an employee
changing their own name goes through a Pending `EmployeeInfoUpdateRequest`
that HR must approve; nothing writes to `employees`/`resort_admins` directly.
So exploiting this needs a malicious/compromised HR account deliberately
setting a payload as someone's name, not an ordinary employee self-service
bypass. Worth fixing for defense-in-depth, not an active open door.

**To unblock:** re-paste the original audit's specific list (which
table/view, which column) if it still exists. Failing that, a dedicated
sweep session scoped to *just* this would need to read through the 88 files
systematically — a multi-hour task on its own, not a drive-by addition to
another part's work.

## P-02 — server-side payroll recompute from attendance/benefit-grid

**Claim:** payroll earnings should be recomputed server-side from attendance
and the benefit grid, not trusted from whatever the draft form submitted.

**Why deferred:** this is a financial-logic feature, not a security patch.
The status-lock half of P-02 (a payroll can only be edited while
`status === 'draft'`) is already fixed and verified against real data. The
recompute itself touches live payroll math — get the formula wrong and you
silently mis-pay real employees, which is a worse outcome than the gap it
would close. Not something to guess at without payroll/finance sign-off on
the exact computation rules.

**To unblock:** a payroll/finance stakeholder needs to confirm the exact
recompute formula (which attendance statuses count, how partial days/OT
factor in, how the benefit grid's rate interacts with it) before this gets
implemented as code.

## S2-10 — grievance unique constraint is global, not per-resort

**Claim:** a database unique constraint that should be scoped per-resort is
currently global, letting one resort's data collide with another's.

**Why deferred:** fixing this needs a schema migration that changes a
**unique constraint**, not just adding a column. That's a hard-to-reverse,
production-risk change — if any existing rows already violate the
corrected (per-resort) constraint, the migration fails outright on real
data, and if it does apply cleanly, rolling it back isn't free either. This
is exactly the class of change flagged in this project's own working rules
as needing explicit sign-off before touching, not something to run blind
against a live schema.

**To unblock:** get product/DB-owner sign-off on the exact new constraint
shape, then check real production data for existing violations *before*
writing the migration (a dry-run query, not a live ALTER).

## X-01 — route-permission enforcement is still report-only

**Claim:** ~1,370 of 1,512 portal routes are open to any logged-in portal
user of any rank, because they were never registered in `module_pages` or
the newer `config/route_permissions.php` map.

**Why deferred:** the enforcement *mechanism* is built and live
(`Common::checkRoutePermissionsMap()`), but `enforce_unmapped` is
deliberately left `false` — flipping it to `true` would start **denying**
every one of those ~1,370 unmapped routes to everyone except whatever the
(currently mostly-empty) permission map allows. Without first running the
report-only logging against real traffic and filling in every legitimate
gap, flipping this switch would lock real HR/HOD/GM users out of pages they
currently use every day. This is the single highest-blast-radius item in
the whole audit — getting it wrong breaks the app for everyone, not just
closes a hole for some.

**To unblock:** run the existing report-only logging
(`UNMAPPED_ROUTE`/`ROUTE_PERMISSIONS_WOULD_DENY`) against real production
traffic for a representative period, review `storage/app/unprotected_routes.txt`,
fill in the legitimate entries in `config/route_permissions.php`, *then*
flip `enforce_unmapped` to `true`.

## IN-02 — Incident investigation committee check isn't assignment-specific

**Claim:** the committee-membership check only verifies the caller is *a*
committee member somewhere, not specifically assigned to *this* incident.

**Why deferred:** Grievance and Disciplinary both already have a
per-case `committee_id`/assignment column to check against — Incidents
doesn't. Closing this properly means adding that assignment structure
(a schema change plus the UI to assign a committee to a specific incident),
not a one-line guard-clause fix. That's a feature-shaped change, and
guessing at the schema without seeing how Incidents' committee-assignment
flow is meant to work risks building the wrong shape and needing to redo it.

**To unblock:** a design decision on how Incidents should track
per-case committee assignment (mirroring Grievance/Disciplinary's existing
`committee_id` pattern, or something else), then a migration + the
assignment UI, then the same `incidentAssignedCommitteeIds()`-style guard
Grievance/Disciplinary already use.
