# Reporting Manager Dropdown — Missing Department Validation

**Status:** Findings + proposed fix, ready for backend implementation.
**Reported by:** HR ops, discovered via a broken Organization Chart rendering.
**Scope:** Backend only (`app/Http/Controllers/Resorts/People/Employee/EmployeeController.php`, optionally a new `Common.php` helper). No frontend changes needed beyond what already exists.

---

## 1. How this was found

The Organization Chart (People module) showed an Accounting Clerk (Anastasia Volkova, employee id 173) rendered as a *sibling* of the department's Director of Finance, instead of nested under her actual manager. Investigation traced it to bad data: her `employees.reporting_to` value pointed at **employee 177, the F&B Manager** — a different department entirely, with no organizational relationship to Accounting. Her two peers with the identical job title ("Accounting Clerk") both correctly report to employee 182, the Finance Manager in their own department.

The org chart itself is not at fault — it correctly reads `reporting_to` and correctly falls back to the department root when the manager isn't in the same department (`OrganizationChartController.php:126-137`, `sameDept` check). That fallback is a *symptom display*, not the *cause*. The actual cause is upstream: **nothing stopped an HR user from picking an out-of-department, hierarchically-nonsensical manager in the first place.**

## 2. Root cause — confirmed

**File:** `app/Http/Controllers/Resorts/People/Employee/EmployeeController.php`
**Method:** `getReportingPerson()`, ~lines 2544-2572

This one endpoint feeds the "Reporting Manager" dropdown on **both** places `reporting_to` can be set:
- Create Employee form → `resources/views/resorts/people/employee/create.blade.php:701-702` (`name="reporting_person"`), submitted via `EmployeeController::store()` (line 605: `'reporting_to' => $request->reporting_person`).
- Edit Employee → Employment Data tab → `resources/views/resorts/people/employee/detail.blade.php:1021` (`name="reporting_to"`), submitted via `updateEmploymentData()` (line 1846).

Both frontends already call the endpoint with a `department_id` param (`create.blade.php` `getReportingPerson(departmentId)` ~3109-3133; `detail.blade.php` `loadHODList()` ~2009-2018) — **the frontend is already sending the right data.** The bug is entirely backend:

```php
public function getReportingPerson(Request $request){
    $Dept_id = $request->department_id;   // <-- captured, then NEVER used again
    $targetRanks = [
        array_search('HOD', config('settings.Position_Rank')),
        array_search('MGR', config('settings.Position_Rank')),
        array_search('GM', config('settings.Position_Rank')),
        array_search('SUP', config('settings.Position_Rank')),
        array_search('EXCOM', config('settings.Position_Rank'))
    ];
    $reportingEmployees = DB::table('employees')
        ->join('resort_admins', 'employees.Admin_Parent_id', '=', 'resort_admins.id')
        ->where('employees.resort_id', $this->resort->resort_id)
        ->where('employees.status', '!=', 'Inactive')
        ->whereIn('employees.rank', $targetRanks)
        ->select(...)
        ->orderBy('employees.rank', 'asc')->orderBy('resort_admins.first_name', 'asc')
        ->get();
    return response()->json(['success' => true, 'data' => $reportingEmployees]);
}
```

Result: **every** HOD/EXCOM/Manager/Supervisor/GM in the entire resort is offered as a candidate manager for **any** employee, regardless of department. `resort_id` scoping is present (multi-tenancy is fine), but nothing scopes by `Dept_id`. This is why a human could accidentally pick an F&B manager for an Accounting Clerk — the dropdown never should have shown that option.

Grepped for `reporting_to`/`reporting_person`/`getReportingPerson` across `resources/views` and `app/Http/Controllers` — this is the only endpoint building this list. Fixing it here fixes both the create and edit flows (per this repo's convention of checking for duplicate/sibling code paths before calling a fix complete).

## 3. Reference data (confirmed against live schema + data)

**Rank values** — `config/settings.php` key `Position_Rank` (no dedicated `ranks` table exists):

| rank | meaning |
|---|---|
| 8 | GM |
| 1 | EXCOM |
| 2 | HOD |
| 4 | MGR (Manager) |
| 5 | SUP (Supervisor) |
| 6 | LINE WORKERS |
| 12 | CLINIC_STAFF |
| 3, 7, 9, 10, 11 | HR / Finance / MD / SO / EDHOD — defined in config but **not currently used as `employees.rank`** in live data (they only appear as `main_rank` values) |

**Use `employees.rank`, not `employees.main_rank`, for this logic.** `main_rank` is a separate axis (e.g. distinguishing an HR-track EXCOM from a Finance-track EXCOM — see `Common::isHrAdmin()`) and is not what the org chart, Leave approval chain, or this dropdown should key off. `rank` is a `string` column storing numeric strings (e.g. `'2'`, not `2`) — watch for loose vs strict comparisons.

**GM has a real department** (e.g. "Executive Office"), but every existing rank-based lookup in this codebase (Leave approval, this ticket's fix) finds GM by `rank = 8` **without** a department filter — department is irrelevant to GM lookups. Follow that same pattern; don't try to special-case a "GM department."

**EXCOM is per-department, rank-based** (`rank = 1`), not a resort-wide singleton — a resort can have an EXCOM in HR, Finance, Culinary, Sales & Marketing, etc., each scoped to their own `Dept_id`. There is no `is_excom` boolean anywhere; EXCOM-ness is purely `rank = 1`, same mechanism as HOD (`rank = 2`).

**`division_id`** exists on `resort_departments` but is not used by any rank-hierarchy logic in this codebase (Leave approval, org chart, `FindResortHODDepartment`) — `Dept_id` is the unit that matters, not division. Don't introduce division-based filtering unless explicitly asked.

## 4. Proposed validation rule

This mirrors the existing Leave-approval rank chain (`LeaveController.php:1974-2097`, the codebase's established pattern for rank-based hierarchy logic) so the new code reads consistently with what's already there:

| Employee's own rank | Valid "Reporting Manager" candidates |
|---|---|
| **GM (8)** | None — GM is the top of the chain. Dropdown should be empty/disabled; `reporting_to` stays null. |
| **EXCOM (1)** | GM only (`rank = 8`, resort-wide, no department filter — matches how Leave approval already finds GM). |
| **HOD (2)** | GM (`rank = 8`), **plus** their own department's EXCOM (`rank = 1`, same `Dept_id`) if one exists. If the department has no EXCOM, only GM is offered. (`Common::FindResortHODDepartment()` already does almost exactly this lookup in the opposite direction — see §5.) |
| **MGR (4) / SUP (5) / LINE WORKERS (6)** | Anyone with `rank` in {HOD(2), EXCOM(1), MGR(4), SUP(5)} **within their own `Dept_id`** — i.e. the same rank list the endpoint already builds, just scoped to department, with GM excluded (a line-level employee shouldn't report directly to the GM) and the employee's own record excluded. |
| **CLINIC_STAFF (12)** | Not covered by the stakeholder's rule as stated — **flag for a decision** before implementing. Likely follows the same rule as MGR/SUP/LINE WORKERS (own-department managerial chain), but confirm with HR since clinic staff already have special-cased behavior elsewhere (Leave approval: rank 12 can comment but never reject). |
| **Ranks 3/7/9/10/11 (HR/Finance/MD/SO/EDHOD)** | Not currently used as `rank` values in live data. Don't crash on them — fall back to the same rule as MGR/SUP/LINE WORKERS (own-department scope) as a safe default, and flag to HR if one ever shows up in practice. |

Always exclude the employee being edited from their own candidate list (prevent self-reporting), and exclude `status = 'Inactive'` employees (existing behavior, keep it).

## 5. Implementation guidance

**Don't duplicate the rank-switch logic inline in the controller.** Per this repo's convention (shared cross-cutting logic belongs in `app/Helpers/Common.php`, not copy-pasted per caller), add one new helper there and have `getReportingPerson()` call it:

```php
// Sketch only — needs review/adjustment by whoever implements this.
// Common.php
public static function getValidReportingManagers($resort_id, $employeeRank, $deptId, $excludeEmployeeId = null)
{
    $rank = (string) $employeeRank;

    if ($rank === '8') { // GM — no manager
        return collect();
    }

    if ($rank === '1') { // EXCOM — GM only, resort-wide
        $targetRanks = ['8'];
        $scopeToDept = false;
    } elseif ($rank === '2') { // HOD — GM + own-dept EXCOM
        $targetRanks = ['8', '1'];
        $scopeToDept = 'excom_only'; // GM unscoped, EXCOM scoped to $deptId
    } else { // MGR/SUP/LINE WORKERS/CLINIC_STAFF/unhandled — own-dept managerial chain
        $targetRanks = ['2', '1', '4', '5'];
        $scopeToDept = true;
    }

    $query = DB::table('employees')
        ->join('resort_admins', 'employees.Admin_Parent_id', '=', 'resort_admins.id')
        ->where('employees.resort_id', $resort_id)
        ->where('employees.status', '!=', 'Inactive')
        ->whereIn('employees.rank', $targetRanks);

    if ($scopeToDept === true) {
        $query->where('employees.Dept_id', $deptId);
    } elseif ($scopeToDept === 'excom_only') {
        $query->where(function ($q) use ($deptId) {
            $q->where('employees.rank', '8')
              ->orWhere(function ($q2) use ($deptId) {
                  $q2->where('employees.rank', '1')->where('employees.Dept_id', $deptId);
              });
        });
    }

    if ($excludeEmployeeId) {
        $query->where('employees.id', '!=', $excludeEmployeeId);
    }

    return $query->select(/* same columns as today */)
        ->orderBy('employees.rank', 'asc')
        ->orderBy('resort_admins.first_name', 'asc')
        ->get();
}
```

```php
// EmployeeController.php — getReportingPerson()
public function getReportingPerson(Request $request){
    $deptId = $request->department_id;
    $employeeRank = $request->rank; // form needs to send the employee's own rank — see §6
    $excludeId = $request->employee_id; // needed on the edit form so an employee doesn't see themself

    $reportingEmployees = Common::getValidReportingManagers(
        $this->resort->resort_id, $employeeRank, $deptId, $excludeId
    );

    return response()->json(['success' => true, 'data' => $reportingEmployees]);
}
```

This is a sketch, not a drop-in patch — needs review against the actual column list currently selected, actual request param names, and a decision on §4's open items (clinic staff, unused rank values) before shipping.

## 6. Frontend implication (small — flagging, not fixing here)

The dropdown-fetch call currently sends `department_id`. For the HOD/EXCOM/MGR/SUP branch of the new rule to work, it also needs the **employee's own rank** in the request (to know which branch of the rule applies) — this isn't sent today. Two places to check when implementing:
- `create.blade.php` — rank is presumably already selected elsewhere on the same form (or known from the position being assigned); wire it into the existing `getReportingPerson(departmentId)` call.
- `detail.blade.php` `loadHODList()` — the employee being edited already has a known `rank` server-side; can likely be passed from Blade directly rather than round-tripped through JS.

## 7. Acceptance criteria

- [ ] Creating/editing an Accounting Clerk (Dept 79) in the Reporting Manager dropdown shows only Accounting's HOD/EXCOM/Manager/Supervisor (e.g. Elena Morozova) — not F&B's Priya Sharma.
- [ ] Creating/editing an HOD shows GM + their own department's EXCOM only (or just GM if no EXCOM exists in that department).
- [ ] Creating/editing an EXCOM shows GM only.
- [ ] GM's own Reporting Manager field is empty/disabled.
- [ ] Existing (already-correct) `reporting_to` values for current employees are unaffected — this only constrains the dropdown going forward, it does not retroactively touch existing data. (Separately, HR should still manually correct employee 173's `reporting_to` from 177 → 182, and clean up the two leftover `Dept_id 80` test records with `reporting_to = 0`, id 486/487 — that's a data fix independent of this code change.)
- [ ] Decision recorded on how CLINIC_STAFF (rank 12) and the unused ranks 3/7/9/10/11 should behave, per §4.
