# Notification text bugs found across modules (Survey-bug sweep)

Prompted by a user-reported bug in Survey Request notifications: literal `**` markdown
characters showing in the notification panel instead of bold text, and raw ISO dates
(`2026-05-07`) instead of the resort's configured display format. That specific bug was
already fixed in Survey (commit `a77675c33`, 2026-08-15) — `SurveyController.php:317-319`
now builds plain text and routes dates through `Common::formatDate()`. The old notification
row the user saw predates that fix; stored notification text is never retroactively
reformatted.

This doc is a full sweep of every other module's notification-message-building code for the
same two bug classes, so the fix can be applied everywhere at once instead of one report at a
time. **Not fixed here** — this is a frontend/presentation-only pass (dropdown unification +
text proofreading); these are all backend controller changes. Listed for a developer to pick
up.

## Root cause shape

`Common::ResortNotification()` (`app/Helpers/Common.php:~6809`) echoes the stored `message`
string raw into the panel HTML — no markdown parsing, no date reformatting at render time.
Whatever plain-text string a controller builds at notification-creation time is exactly what
displays forever, both in the web bell and (same stored string) in the mobile in-app list and
FCM push body. So the fix has to happen at the point each message is *built*, not at render
time — same shape as the Survey fix: swap raw/hardcoded dates for `Common::formatDate($date)`
(and `Common::formatDisplayTime($time)` where a time is embedded too).

## Bug 1 — literal `**` markdown: not found elsewhere

Checked every PHP string literal under `app/` for `**`. Zero other notification builders use
it — Survey was the only instance in the codebase. Not a pattern to fix elsewhere.

**Sibling variant worth flagging though:** the Learning module (see below) embeds raw
`<strong>` HTML tags instead of `**`. Since `ResortNotification()` echoes raw, that *does*
render bold in the web bell — but the identical stored string is served verbatim to the
mobile app's in-app notification list and the FCM push body, where users see literal
`<strong>...</strong>` tags. Same underlying problem as the `**` bug, different markup.

## Bug 2 — unformatted dates: 22 broken sites across 10 modules (~37 notification strings)

Two severities:
- **A — raw value** (identical to the original Survey bug): a raw DB column or request input
  interpolated directly, e.g. `{$request->start_date}` → renders `2026-05-07`.
- **B — hardcoded format**: uses `Carbon::parse(...)->format('d M Y')` or `date('d M Y', ...)`
  directly instead of `Common::formatDate()`. Readable, but ignores the resort's actual
  configured `date_format` setting, so these notifications disagree with every other date
  shown in the app for that resort.

---

### Learning — 4 sites

- **A** — `app/Http/Controllers/Resorts/Learning/LearningController.php:184-189`
  ```php
  $datesText = $request->filled('end_date')
      ? "Dates: {$request->start_date} to {$request->end_date}."
      : "Expected start: {$request->start_date}.";
  ```
- **A + raw `<strong>` HTML** — `LearningController.php:442-444`
  ```php
  'Approved' => "<strong>Good news!</strong> Your learning request for <strong>'{$trainingName}'</strong> has been <strong>approved</strong>. "
      . "<strong>Training Dates:</strong> {$learningRequest->start_date} - {$learningRequest->end_date}. "
  ```
- **A + raw `<strong>` HTML** — `LearningController.php:476-479` (same shape, "Congratulations!" / selected-for-training message)
- **B** — `app/Http/Controllers/Resorts/Learning/DashboardController.php:593`
  ```php
  ' by ' . \Carbon\Carbon::parse($p->due_on)->format('d M Y')
  ```

Model of correct usage already in the same module for reference:
`Learning/TrainingScheduleController.php:337,563`.

### Performance — 5 sites

- **A + B** (worst case — hardcoded format, raw fallback, *and* a raw time) —
  `app/Http/Controllers/Resorts/Performance/MonthlyCheckingController.php:577`
  ```php
  $msg = 'A monthly check-in meeting has been scheduled with you on '.($formatted ? date('d M Y', strtotime($formatted)) : $request->date_discussion).' at '.$request->start_time.'. Please approve or reject.';
  ```
- **B** — `MonthlyCheckingController.php:666` and `:805`
  ```php
  date('d M Y', strtotime($checkin->date_discussion))
  ```
- **A + B** — `app/Http/Controllers/Resorts/Performance/PerformanceMeetingController.php:476`
  ```php
  Carbon::parse($request->date)->format('d M Y') . ' at ' . $request->start_time
  ```
- **A** — `app/Http/Controllers/API/MonthlyCheckInController.php:357`
  ```php
  $msg = 'Meeting Rescheduled by HR for Monthly Check-In Date '.$request->date_discussion;
  ```
- **B** — `API/MonthlyCheckInController.php:776`
  ```php
  date('d M Y', strtotime($checkin->date_discussion))
  ```

### People — 3 date sources feeding 16 notification strings, all **B**

- `app/Http/Controllers/Resorts/People/Transfer/TransferController.php:273`
  ```php
  "\n📅 Effective Date: " . Carbon::parse($transfer->effective_date)->format('d M Y')
  ```
- `TransferController.php:1309-1311` (`$effective`) → used in messages at `:1324`, `:1336`, `:1351`
- `TransferController.php:1374-1382` (`$effectiveDate` + `$temporaryNote`) → `:1388`, `:1398`, `:1409`, `:1424`, `:1435`
- `TransferController.php:1732-1741` (same pair) → `:1764`, `:1782`, `:1797`, `:1814`
- `app/Http/Controllers/Resorts/People/SalaryIncrementController.php:170-172`, `:525-527`, `:898-900`
  ```php
  $effectiveFmt = Carbon::parse(...)->format('d M Y')
  ```
  → messages at `:188`, `:528`, `:901` (`"\n📅 Effective Date: " . $effectiveFmt`)
- `app/Http/Controllers/Resorts/People/Promotion/PromotionController.php:1016-1018` (`$tillDate`) →
  message at `:1023` (`"...put on hold by {$actorLabel} till {$tillDate}"`).
  **Note:** `PromotionController.php:280` in the *same file* already uses `Common::formatDate`
  correctly — inconsistent within one file, easiest of the People fixes.

### Leave — 3 sites

- **A** — `app/Http/Controllers/API/LeaveController.php:4167` and `:4196`
  ```php
  ... . 'Your leave request from ' . $leave->from_date . ' to ' . $leave->to_date . ' has been ' . $action . '.',
  ```
  Also has an unrelated small text bug on the same line: missing space —
  `...last_name . 'Your leave request...'` concatenates directly onto the name with no
  separating space.
- **B** — `app/Http/Controllers/Resorts/Leave/LeaveController.php:2138-2139` → messages at `:2148`, `:2162`

### Boarding Pass — 2 sites, both **A**

- `app/Http/Controllers/API/BoardingPassController.php:1749`
  ```php
  $request->transportation_mode . ' ' . $request->date . ' at ' . $request->time . ...
  ```
- `BoardingPassController.php:1775`
  ```php
  ... ') on ' . $request->date . ' at ' . $request->time . ' created with ...'
  ```

### Incident — 1 site, **A**

- `app/Console/Commands/IncidentMeetingReminder.php:79`
  ```php
  $msg = "📝 Meeting: {$meeting->meeting_subject}\n📅 Date: {$meeting->meeting_date}\n⏰ Time: {$meeting->meeting_time}\n📍 Location: {$meeting->location}";
  ```
  Directly contradicts `Resorts/Incident/IncidentMeetingController.php:296,447` in the same
  module, which already formats the same fields correctly — copy the pattern from there.

### Payroll — 1 date source, 4 strings, **B**

- `app/Http/Controllers/Resorts/Payroll/PayrollController.php:1301`
  ```php
  $period = Carbon::parse($payroll->start_date)->format('d M Y') . ' - ' . ...->format('d M Y')
  ```
  → raw `resort_notifications` inserts at `:1316`, `:1343`, `:1363`, `:1467`

### Time & Attendance — 1 site, **B**

- `app/Http/Controllers/Resorts/TimeAndAttendance/DutyRosterController.php:639`
  ```php
  'A new duty roster has been assigned to you starting ' . $startingDate->format('d M Y') . '.'
  ```

### Visa — 1 date source, 2 strings, **B**

- `app/Console/Commands/CheckVisaExpiryReminders.php:144`
  ```php
  $expiryFormatted = Carbon::parse($expiryDate)->format('d M Y')
  ```
  → messages at `:147`, `:149`

### SOS — 1 site, **B**

- `app/Http/Controllers/API/SOSController.php:161-162`
  ```php
  "Date: " . Carbon::now()->format('d M Y')
  "Time: " . Carbon::now()->format('h:i A')
  ```
  Persists in the bell — goes through `sendMobileNotification(..., skipDbInsert=false, ...)`
  at `:171`.

---

## Already clean (checked, no action needed)

Survey (`SurveyController.php:317,514,1062` — the fixed baseline) · Learning
`TrainingScheduleController` (`:337,563`) · People `OnboardingController`
(`:1537,1556,1580`) · People `Promotion` (`:280,920,924,953`) · People `Transfer` (`:958`) ·
Incident `IncidentMeetingController` (`:296,447`) · Incident `IncidentController` · People
`ExitClearance`, `Employee/AdvanceSalaryController`, `EmployeeResignationController`,
`InfoUpdateController`, `Probation`, `Compliances` · Performance
Review/Cycle/Kpi/PipPdp · GrievanceAndDisciplinery
Grivance/Disciplinary/Configuration/GrievanceAppeal · Visa
PaymentRequest/XpactEmployee · Accommodation `MaintananceContorller` +
`API/AccommodationController` (all 6 sites) · `API/ClinicController` · FileManagment
FileManage/FileShare · TalentAcquisition Vacancy/Config/OfflineInterview/InterviewAssessment
+ `ApplicantController` · Payroll `ShopkeeperController` + `API/ShopController` +
`Shopkeeper/PaymentController` + `PaymentConsentController` · `MasterDashboardController` +
`Leave/DashboardController` (birthday) · `Leave/BoardingPassController` ·
`SOS/DashboardController` · `ResortAllNotificationController` · `Admin/NotificationController`
· `Listeners/SendNotification` · API: Survey, Learning, Incident, Grievance, Disciplinary,
Resignation, Profile, FileManagement, Announcement, StaffAccommodation, RequestController,
Calendar, PerformanceReview, PipPdp, ChatBoat · Console: CheckDepositRefundReminders,
OnboardingNewEmpHirePushNotification, CalenderPushNotification. Support module has no
notification writes at all.

## One near-miss, deliberately excluded

`app/Http/Controllers/Resorts/People/Compliances/ComplianceController.php:1706-1711` writes
`format('d M Y')` into a *compliance record* `description` field — not the
`resort_notifications` table. Same smell (hardcoded format, ignores resort setting), different
table/feature. Flagging in case it's worth the same fix while a developer is in this area, but
it's out of scope for "notification text."

## Suggested fix pattern (matches the Survey commit)

For **A** sites: wrap the raw value in `Common::formatDate($value)` (and
`Common::formatDisplayTime($value)` for a time-only field).

For **B** sites: replace `Carbon::parse($x)->format('d M Y')` / `date('d M Y', strtotime($x))`
with `Common::formatDate($x)` — same output shape today (resort's default is `d-M-y`), but
correctly reflects each resort's actual configured `date_format` going forward, and matches
every other date shown in the app.

For the Learning module's raw `<strong>` HTML (bug 1's sibling): strip the tags the same way
Survey's `**` was stripped — `ResortNotification()` has no markdown/HTML rendering contract,
and the mobile app/FCM push consumers show the tags literally.

## Summary

- **Markdown bug: 0 other sites** — Survey was the only instance.
- **Date-format bug: 22 sites across 10 modules** (Learning, Performance, People, Leave,
  Boarding Pass, Incident, Payroll, Time & Attendance, Visa, SOS) producing ~37 distinct
  user-facing notification strings.
  - **Severity A (raw value): 9 sites** — Learning ×3, Performance ×2, Leave ×2, Boarding
    Pass ×2, Incident ×1 (two of these are A+B hybrids, counted once here).
  - **Severity B (hardcoded format): 14 sites.**
