# HOD Leave Dashboard — Missing Sections Ticket

For the mobile team. Ticket: "who's on leave", "upcoming leaves", "upcoming
birthdays" sections missing, and "upcoming holidays" View All button not
working, on the HOD leave dashboard screen.

## Finding: all 4 sections already exist in one endpoint

`GET resort/leave-dashboard-hod` → `LeaveController::leaveDashboardHOD()`
already bundles all 4 sections in a single response — nothing needed adding
on the backend. Verified against real data in a rolled-back test (created a
temporary approved leave, confirmed the sections populate, then rolled
back).

```json
{
  "status": true,
  "message": "HOD Leave Deshboard Details",
  "leave_detail": {
    "total_applied_leaves": 12,
    "total_approved_leaves": 8,
    "total_rejected_leaves": 1,
    "total_pending_leaves": 3,
    "employees_on_leave_today": [
      {
        "leave_id": 315,
        "id": 257,
        "first_name": "Laila",
        "last_name": "Hassan",
        "profile_picture": "https://thewisdom.io/admin_assets/files/user-image.png",
        "leave_type": "Maternity Leave",
        "color": "#0056d6",
        "employee_id": "DR-17",
        "position": "Waitress",
        "department": "F and B Service"
      }
    ],
    "upcoming_employee_leave": [
      {
        "id": 257,
        "first_name": "Laila",
        "last_name": "Hassan",
        "profile_picture": "https://thewisdom.io/admin_assets/files/user-image.png",
        "position": "Waitress",
        "leave_type": "Maternity Leave",
        "color": "#0056d6",
        "from_date": "2026-07-25",
        "to_date": "2026-07-28",
        "total_days": 4
      }
    ],
    "leave_request": [ "...current pending/actionable leave requests, same shape as employee-leave-request-list..." ],
    "island_pass": [ "...pending Island Pass requests for this HOD's dept..." ],
    "upcoming_holidays": [
      {
        "id": 27,
        "resort_id": 26,
        "PublicHolidaydate": "2026-09-14",
        "PublicHolidayName": "Day Maldives Embraced Islam",
        "HolidayId": "20",
        "description": null
      },
      {
        "id": 26,
        "resort_id": 26,
        "PublicHolidaydate": "2026-11-03",
        "PublicHolidayName": "Victory Day",
        "HolidayId": "21",
        "description": null
      }
    ],
    "upcoming_birthdays": [
      { "employee_id": 1, "first_name": "...", "last_name": "...", "profile_picture": "...", "position": "...", "dob": "1992-02-25" }
    ]
  }
}
```

## Section-by-section notes

- **`employees_on_leave_today`** — scoped to the HOD's own department
  (`e.Dept_id = <hod's dept>`), only `status = 'Approved'` leaves covering
  today. Empty is correct if nobody in the department is on leave today —
  this is exactly why local testing showed nothing: no active leave existed
  for the test data at the time.
- **`upcoming_employee_leave`** — approved leaves overlapping the **current
  week** (`Carbon::now()->startOfWeek()` to `endOfWeek()`), dept-scoped same
  as above.
- **`upcoming_birthdays`** — dept-scoped, looks at today/tomorrow/day-after
  (3-day window). Also legitimately empty if nobody's birthday falls in
  that window right now — not a bug, just a narrow window with real local
  data. (Note: this was previously broken by an off-by-one substring bug on
  `dob` and was already fixed earlier this session — see git history if you
  want the before/after.)
- **`upcoming_holidays`** — **no limit, no separate "view all" endpoint** —
  this array already contains every future holiday for the resort. If the
  mobile "View All" button does nothing, the full list is already in this
  same response; a dedicated screen just needs to render this same array
  in full rather than calling anything new.

## Conclusion

No backend gap found — this response has always contained all 4 sections
with correct data. If mobile is only seeing some sections (or none), check:
1. Is `/resort/leave-dashboard-hod` the actual endpoint mobile calls for
   this screen, or is it using something else (e.g. only
   `hod-who-is-on-leave` / `hod-upcoming-employee-leave-list` separately)?
2. Is the client parsing `leave_detail.employees_on_leave_today` /
   `upcoming_employee_leave` / `upcoming_birthdays` / `upcoming_holidays`
   correctly, or expecting different key names?

If a specific department/date genuinely shows nothing where you expect
data, share the exact department + date and I'll check the underlying
records directly.
