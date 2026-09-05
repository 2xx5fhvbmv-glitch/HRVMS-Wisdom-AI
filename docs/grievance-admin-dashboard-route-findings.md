# People Relation — Admin Dashboard Route Is Broken

**Status:** Root cause confirmed, not fixed (frontend-only pass — out of scope for this change).
**Scope:** Backend only — `routes/resort_route.php` + `app/Http/Controllers/Resorts/GrievanceAndDisciplinery/DashboardController.php`.

## The bug

Two routes point at a controller method that doesn't exist:

```
routes/resort_route.php:121  Route::get('grievance-and-disciplinary/admin-dashboard', 'GrievanceAndDisciplinery\DashboardController@Admin_Dashobard')->name('GrievanceAndDisciplinery.Admindashboard');
routes/resort_route.php:135  (duplicate of the same route/name, same typo)
```

Both call `Admin_Dashobard` (capital **D**, "Dashobard"). The controller only defines:

```php
// DashboardController.php:36
public function Admin_dashboard(Request $request)
{
    // empty — no body
}
```

Note the case difference (`Admin_dashboard`, lowercase d) — PHP method names are case-insensitive, so *that* alone wouldn't break it, but the route string is `Admin_Dashobard` with the same "-obard" typo `HR_Dashobard`/`Hod_dashboard` carry elsewhere in this file, and no method by that exact spelling exists at all. Hitting either route throws a fatal "method does not exist" error.

Even if the typo were fixed, `Admin_dashboard()` is currently an **empty stub** — it returns nothing, no view, no data.

## Also found: two dead view files in the same folder

Neither of these is reachable by any live route or method — safe to ignore, delete, or repurpose:

- `resources/views/resorts/GrievanceAndDisciplinery/dashboard/admindashboard.blade.php` — static dummy markup, never rendered (the controller method that would render it is the empty stub above).
- `resources/views/resorts/GrievanceAndDisciplinery/dashboard/hoddashboard.blade.php` — not Grievance content at all; it's leftover Accommodation-module markup. `Hod_dashboard()` actually renders `hrdashboard.blade.php` (the same view HR and EXCOM use), not this file.

## Suggested fix

1. Fix the route typo(s) to call the real method name `Admin_dashboard`.
2. Implement `Admin_dashboard()` — likely the same query/compact() pattern as `HR_Dashobard()`/`Hod_dashboard()` (both already in the file, lines ~40 and ~270), scoped for whatever the Admin role should see.
3. Decide what `admindashboard.blade.php` should actually contain, or delete it and point the route at `hrdashboard.blade.php` like the other two roles do.
4. Delete or repurpose `hoddashboard.blade.php` — it's not used by this module and its content belongs to Accommodation.
