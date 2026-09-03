# Incident Dashboard — "Pending Resolution Approval" Stuck on "Loading…"

**Status:** Root cause confirmed, ready for backend implementation.
**Reported by:** HR ops, on the Incident HR Dashboard (`/resort/incident/hr-dashboard`).
**Scope:** Backend only — `app/Http/Controllers/Resorts/Incident/DashboardController.php`. No frontend change needed; the widget's own JS is already correct and doesn't need to touch this fix at all.

---

## 1. The symptom

On the Incident HR Dashboard, the "Pending Resolution Approval" card never loads — it sits on "Loading…" forever. Same widget appears on the admin and HOD incident dashboards too.

## 2. Root cause — confirmed

The AJAX call behind the widget (`GET /resort/incidents/pending-resolutions`) returns a **500 error**, not a slow response:

```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'ii.follow_up_actions' in 'field list'
(SQL: select `ii`.`id`, `i`.`incident_name`, `ii`.`investigation_findings`, `ii`.`follow_up_actions`,
 `iat`.`action_taken`, `iot`.`outcome_type` from `incidents_investigation` as `ii`
 inner join `incidents` as `i` on `i`.`id` = `ii`.`incident_id`
 left join `incident_outcome_types` as `iot` on `iot`.`id` = `ii`.`outcome_type`
 left join `incident_actions_taken` as `iat` on `iat`.`id` = `ii`.`action_taken`
 where `i`.`id` in (1,2,3,...) and `ii`.`approval` = 1 and `ii`.`approved_by` is null
 order by `ii`.`created_at` desc limit 5)
```

`incidents_investigation` genuinely has no `follow_up_actions` column — and hasn't for a while. This isn't a missing migration; it's a **stale reference to a column that was intentionally renamed and restructured**, and the controller was never updated to match.

**The rename**, per `database/migrations/2025_05_02_182355_add_incident_investigationfields.php` (2025-05-02): `follow_up_actions` (a plain text column) was **dropped** and replaced with **`folloup_action`** (yes — misspelled in the actual migration, that's the real column name today) — changed from free text into a foreign key pointing at a new lookup table, `incident_followup_actions`.

The model was updated correctly at the time and confirms this is the real, current shape: `app/Models/IncidentsInvestigation.php`'s `$fillable` lists `folloup_action` (not `follow_up_actions`), and it has:
```php
public function followupAction() {
    return $this->belongsTo(IncidentFollowupActions::class, 'folloup_action', 'id');
}
```

`DashboardController.php` is the only place that never got the memo — it still queries the pre-rename column name, **in three separate places**:

| Line | Method | Feeds |
|---|---|---|
| **528** | `getPendingResolutionApprovals()` | HR dashboard's "Pending Resolution Approval" widget — `resources/views/resorts/incident/dashboard/hrdashboard.blade.php` + `admindashboard.blade.php` (route `incident.pendingResolutions`) — **this is the one causing the reported bug** |
| **588** | `pendingApprovalsList()` | The fuller "View all →" DataTable list at `resources/views/resorts/incident/incident/pending-approvals.blade.php` (route `incident.pending-approvals`) — same crash, same cause |
| **1008** | `getPendingResolutionApprovalsforHOD()` | HOD dashboard's identical widget — `resources/views/resorts/incident/dashboard/hoddashboard.blade.php` (route `incident.hod-pending-approvals`) |

Per this codebase's own convention (duplicate query paths per view are the norm — CLAUDE.md invariant #7), all three are the same widget re-implemented per role, and all three carry the identical bug.

## 3. A relevant wrinkle: the column isn't actually displayed anywhere

Checked every frontend consumer of these three endpoints:
- The dashboard widget's own JS (`resources/views/resorts/incident/dashboard/_dashboard_scripts.blade.php:143-147`, `dbiLoadPendingResolutions()`) only reads `item.incident_name` and `item.investigation_findings` from the response. It never reads `follow_up_actions`, `action_taken`, or `outcome_type` at all.
- The fuller DataTable list (`resources/views/resorts/incident/incident/pending-approvals.blade.php:88-93`) renders columns for `incident_name`, `investigation_findings`, `outcome_type`, `action_taken` — but **no `follow_up_actions` column either**.

So across all three call sites, the `follow_up_actions` field being selected was already dead output even before the rename broke it outright — nothing on the frontend was ever consuming it.

## 4. Two valid fixes — pick one

**Option A — drop it (simplest, matches current actual usage):**
Since nothing renders this field anywhere, the smallest correct fix is to just remove `ii.follow_up_actions` from the three `->select(...)` calls (lines 528, 588, 1008). Zero behavior change to what users see today; the crash goes away because the query stops asking for a nonexistent column.

**Option B — restore it properly (if a follow-up-action label is actually wanted on these views):**
Replace `ii.follow_up_actions` with the renamed FK column plus a join to resolve it to a readable label, the same pattern already used for `outcome_type`/`action_taken` in these same queries:
```php
->leftJoin('incident_followup_actions as ifa', 'ifa.id', '=', 'ii.folloup_action')
->select(..., 'ifa.<label_column> as follow_up_action_label', ...)
```
(Check `incident_followup_actions`'s actual label column name — likely `name` or `action`, matching `incident_outcome_types`/`incident_actions_taken`'s own shape.) This would also need one line added to `pending-approvals.blade.php`'s `columns:` array and `_dashboard_scripts.blade.php`'s render function if the intent is to actually surface it to users.

Given nothing currently displays this field, **Option A is the recommended fix** — Option B is only worth it if there's a product reason to show "follow-up action" on these widgets that just hasn't been built yet.

## 5. Acceptance criteria

- [ ] `GET /resort/incidents/pending-resolutions` (HR/admin dashboard) returns 200, not 500.
- [ ] `GET /resort/incidents/pending-approvals` (the "View all" DataTable) returns 200, not 500.
- [ ] The HOD dashboard's equivalent widget (`getPendingResolutionApprovalsforHOD`) also fixed — same bug, same file, line 1008.
- [ ] "Pending Resolution Approval" no longer stuck on "Loading…" on HR, admin, and HOD incident dashboards.
- [ ] If Option B is chosen instead of A: confirm `incident_followup_actions`'s actual label column name before writing the join, and update the two frontend render points listed above to actually show it.
