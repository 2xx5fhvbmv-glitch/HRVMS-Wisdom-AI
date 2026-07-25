# HOD Attendance Dashboard — Real-Time Sync

For the mobile team. Ticket: dashboard showed 0/0 total/present despite a
real check-in existing (visible in "View Attendance"); separately, a 2-minute
delay was observed before a second check-in's count appeared.

## `POST` / `GET timeandattendance/time-attendance-hod-dashboard`

**Confirmed: no backend caching exists.** Read
`TimeAndAttendanceController::timeAttendanceHODDashboard()` in full — every
call runs live DB queries (`Employee`/`ParentAttendace`/`EmployeeLeave`
joins), no `Cache::remember()`, no cached response anywhere in the method
or its call chain. Every request recomputes from scratch.

**Response shape** (unchanged, for reference):
```json
{
  "success": true,
  "message": "...",
  "data": {
    "total_employees": 5,
    "present": 3,
    "absent": 1,
    "on_leave": 1,
    "total_overtime_hours": 4.5
  }
}
```

## Two separate observations, two separate explanations

1. **"Total: 0, Present: 0" despite a real check-in** — scoping is via
   `Common::getSubordinates()`, a recursive walk of `employees.reporting_to`
   (not department). Verified against the specific real employee pair named
   in the ticket (Priya Sharma / Laila Hassan) and `reporting_to` was
   correctly set, so this exact pair doesn't currently reproduce the bug.
   This scoping is shared by every mobile HOD-facing endpoint in this
   codebase (not unique to this dashboard) — if it recurs for a *different*
   employee pair, the most likely cause is that employee's `reporting_to`
   isn't populated (a data-entry gap, not a code bug) rather than something
   dashboard-specific. Worth checking that specific employee's
   `reporting_to` value if it happens again.
2. **~2 minute delay before a count updates** — the endpoint itself has no
   caching (confirmed above), so this isn't a Laravel-side staleness issue.
   Both a `GET` and `POST` route already exist for this exact same endpoint
   (`routes/api.php:148-149`). If mobile is calling the `GET` variant and
   something in front of the app (a CDN or reverse proxy) caches GET
   responses by default, switching to the `POST` variant (not cached by
   default by most infrastructure) would sidestep that without any backend
   change. Recommend trying that first since it's a one-line client change
   and the backend has nothing left to fix on this specific complaint.

No code changes were made for this ticket — investigated and reported
rather than guessed at, since the specific reported scenario didn't
reproduce with real local data.
