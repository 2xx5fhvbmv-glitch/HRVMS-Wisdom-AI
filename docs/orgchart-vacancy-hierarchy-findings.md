# Org Chart — Vacant Positions Attach to the Wrong Tier

**Status:** Findings, ready for backend implementation.
**Reported by:** HR ops, found in the F&B Service department's Organization Chart.
**Scope:** Backend only — `app/Http/Controllers/Resorts/People/OrgChart/OrganizationChartController.php`. This is **not** fixable from the Blade/JS side (see §4) — confirmed the client only renders whatever `pid` the server assigns for every node, with no rank/section awareness of its own.

---

## 1. The symptom

F&B Service department org chart:
- Rajesh Patel — Executive Chef (EXCOM, `rank = 1`)
- Priya Sharma — F&B Manager (`rank = 4`), correctly nested under Rajesh
- Two **vacant** Waitress positions (open reqs, no employee assigned)

Expected: a vacant Waitress position should nest under the nearest actual superior in the reporting chain — Supervisor if one exists in that department, else Manager, else HOD, else EXCOM (the same "walk up until you find someone" logic HR already uses mentally, and the same shape as this codebase's existing Leave-approval rank chain). Concretely here: Priya Sharma (Manager), since there's no Supervisor in F&B Service.

Actual: both vacant Waitress slots render as **siblings of Priya Sharma**, directly under Rajesh Patel — as if a line-level vacancy and the department's EXCOM are the same tier.

## 2. Root cause — confirmed

**File:** `app/Http/Controllers/Resorts/People/OrgChart/OrganizationChartController.php`
**Method:** `getEmployeesData()`, lines 159–230

Vacant nodes are **not** read from the `vacancies` table. They're synthesized from pure headcount math:

- Lines 166–176: join `manning_responses` → `position_monthly_data` → `resort_positions` to get each position's budgeted headcount for the current year.
- Lines 178–187: subtract currently-filled headcount (active employees per `Position_id`) → `vacant_count` per position.
- Lines 189–197: for **each department**, find exactly **one** department head — first active employee with `rank` in `[2 (HOD), 1 (EXCOM)]` — and cache it (`$deptHeads[$dept_id]`).
- Lines 202–230: every vacant slot in that department, regardless of which position/rank it actually is, gets:
  ```php
  'pid' => isset($deptHeads[$row->dept_id])
      ? ('emp_' . $deptHeads[$row->dept_id])
      : ('dept_' . $row->dept_id),
  ```

**The bug:** this is a single flat per-department lookup, not per-position. A vacant Waitress (line-level) and a vacant Sous Chef (if one existed) in the same department would get the *exact same parent* — whichever of HOD/EXCOM the code finds first. The logic never looks at the vacant position's own rank, and never walks down through Supervisor/Manager tiers the way it should — it jumps straight to rank ≤ 2.

Worth noting: the headcount query (lines 166–176) doesn't even `SELECT` `resort_positions.Rank` or `resort_positions.section_id` — the data needed to place a vacancy correctly isn't in scope of the query at all right now.

## 3. Data available but unused

- **`resort_positions.Rank`** (int, `Position_Rank` values — same convention documented in the earlier reporting-manager findings doc) exists on every position row, but isn't selected or used here.
- **`resort_positions.section_id`** (nullable FK to `resort_sections`) also exists but isn't used here.
- **The `vacancies` table** (`App\Models\Vacancies`, migration `2024_11_07_103606_create_vacancies_table.php`) is more directly useful than either of the above — its `$fillable` includes `reporting_to` (FK to `employees.id`), `rank`, and `section`. These are populated when a real vacancy requisition is created (Talent Acquisition / manning flow) and would tell you exactly who a specific open position should report to — but `getEmployeesData()` never queries the `Vacancies` model or `vacancies` table at all. It only ever touches `manning_responses`/`position_monthly_data`/`resort_positions` for the headcount subtraction.

So there are two independent signals available and currently ignored: an explicit `vacancies.reporting_to` (when a real requisition row exists and has it set), and a derivable rank-chain lookup via `resort_positions.Rank` + `Dept_id` (works even for headcount-only vacant slots with no requisition row).

## 4. Confirmed: not fixable in the frontend

`resources/views/resorts/people/orgchart/index.blade.php` (~lines 594–624) does exactly two things to `pid` after receiving the tree from the server:
```js
nodes.forEach(n => {
    if (n.pid && !idSet.has(n.pid)) n.pid = null;   // orphan -> root promotion only
});
...
rootNodes.forEach(node => { node.pid = 'root_org'; }); // wrap true roots
```
Vacant nodes always arrive with a valid `pid` that exists in the node set (`emp_<id>` or `dept_<id>`), so neither branch fires — there is no rank-aware or section-aware re-parenting anywhere on the client. Tier is 100% a function of what the PHP controller assigns. This has to be a backend fix.

## 5. Proposed fix

Mirror the existing rank-chain pattern already used elsewhere in this codebase (Leave approval chain in `LeaveController.php`, and `Common::FindResortHODDepartment()` for the HOD→EXCOM fallback) rather than inventing a new style. Suggested approach, in priority order per vacant slot:

1. **If a matching `Vacancies` row exists for this position/department with `reporting_to` set**, use that employee directly (`emp_<reporting_to>`) — this is the most accurate signal when it exists, since it reflects what was actually requisitioned.
2. **Otherwise, derive it from rank**: walk the vacant position's own `Rank` down the chain within the same `Dept_id` — for a line-level vacancy (rank 5/6), look for the nearest present superior in order **Supervisor (5) → Manager (4) → HOD (2) → EXCOM (1)**, same "first one that exists wins" idea as `Common::FindResortHODDepartment()`, just extended two more tiers down instead of stopping at HOD/EXCOM. A manager-level vacancy (rank 4) would start its search at HOD instead of Supervisor, and so on — the walk should start one tier above the vacant position's own rank, not always start at Supervisor.
3. **Fall back to today's behavior** (department root / HOD / EXCOM) only if neither of the above resolves — e.g. a department with literally no staff at all yet.

This resolves the reported case correctly without needing section-awareness: F&B Service has no Supervisor, so the walk lands on Priya Sharma (Manager) — matching what HR expects. Section-scoped placement (e.g. preferring a supervisor of the *same section* over a department-wide one) is a possible future refinement — `resort_positions.section_id` exists, but there's no `manager_id`/`head_employee_id` on `resort_sections` to resolve "who runs this section" directly, so that would need its own follow-up if HR wants section-level precision later. Not required to fix the reported bug.

### Sketch (not a drop-in patch — needs review against the actual query structure and column names in `getEmployeesData()`)

```php
// Replace the current per-department single deptHeads lookup (lines 189-197)
// with a helper that finds the nearest superior at-or-above a given rank,
// within a department:
function findNearestSuperior(array $deptEmployeesByRank, string $vacantRank): ?int
{
    // Walk the chain starting one tier above the vacancy's own rank.
    $chain = ['5', '4', '2', '1']; // SUP, MGR, HOD, EXCOM
    $startIndex = array_search($vacantRank, $chain, true);
    $searchFrom = $startIndex === false ? 0 : $startIndex; // unranked/unknown -> start at SUP
    for ($i = $searchFrom; $i < count($chain); $i++) {
        if (!empty($deptEmployeesByRank[$chain[$i]])) {
            return $deptEmployeesByRank[$chain[$i]][0]->id; // first active match
        }
    }
    return null; // caller falls back to dept_<id> root, same as today
}
```
And `resort_positions.Rank`/`section_id` need to be added to the `SELECT` in the headcount query (lines 166–176) so each vacant row actually carries its own rank into the parenting step — right now that data is discarded before it reaches the point where `pid` gets decided.

## 6. Acceptance criteria

- [ ] A vacant Waitress (or any line-level position) in F&B Service nests under Priya Sharma (Manager), not Rajesh Patel (EXCOM).
- [ ] A vacant position in a department that genuinely has no Supervisor *or* Manager still correctly falls through to HOD, then EXCOM, then department root — i.e. today's existing fallback chain still works as the last resort, just no longer as the *first* resort.
- [ ] If a real `Vacancies` requisition row exists with `reporting_to` explicitly set, that takes priority over the rank-walk.
- [ ] Multiple vacant positions of *different* ranks in the same department each attach to their own correct tier (this is the core bug — today they're all forced to the same single parent per department).
- [ ] No change to how *filled* employee nodes are parented (`reporting_to`-based logic, already correct — untouched by this fix).
