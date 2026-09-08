# Notification Events — Web Portal

Reference for backend developers implementing notification triggers for actions performed
in the **web portal** (`resort-admin` guard). Companion doc:
`docs/notification-events-mobile-app.md` (the mobile app's own backend, `api` guard).

Compiled by reading the actual controller code across every module — not inferred from
method names. Every row below has a file:line you can jump to. This is a snapshot as of
2026-09-05; re-verify file:line references before acting on them if much time has passed.

---

## Part 1 — How notifications actually work here

Skip this if you already know it. Get it wrong and your notification will silently not
arrive — every bug found in this audit traces back to one of these rules.

### The three delivery rails

| Rail | What it does | Reaches a backgrounded/closed mobile app? |
|---|---|---|
| **In-app row** (`resort_notifications` table) | What the mobile in-app list and the web bell dropdown both read | No — only visible next time the app/page is opened |
| **Push (FCM)** | A real push notification via Firebase Cloud Messaging | **Yes** — this is the only rail that does |
| **Pusher (web real-time)** | WebSocket broadcast to the web bell while a browser tab is open | No — web-only, and does not touch the phone at all |

Two legacy rails (`BASE_URL`/Node-socket, `NOTIFICATION_URL` webhook) exist in the code but
are **dead** — both env vars are commented out in `.env`. Don't build against them.

### The four functions, and which rail(s) each one actually uses

| Function | Where | DB row? | Push? | Notes |
|---|---|---|---|---|
| `Common::nofitication($resortId, $type, ...)` — **`app/Helpers/Common.php:1447`** | | **Only if `$type === 10`** | **Only if `$type === 10`** | Every other `$type` (1–9, 11) is broadcast-only: renders a Blade partial for the web bell's Pusher channel and returns. No DB row, no push, nothing reaches mobile. **If you need this to reach a phone or survive a page reload, `$type` must be `10`.** |
| `Common::sendMobileNotification($resortId, $type, $feedbackFormId, $trainingId, $title, $message, $module, $sendto[], $requestId, $skipDbInsert, $pageId)` — **`Common.php:7359`** | Yes, unless `$skipDbInsert=true` | Yes | `$sendto` is an **array** of employee ids (multi-recipient in one call). `$type` must be a value the function recognizes (currently 1–4) or the payload build silently produces nothing and the function returns `null` — **no error, no push, no log**. `$type=2` is the general-purpose one; copy it unless you have a specific reason not to. |
| **`Common::notifyEmployees($resortId, $empIds[], $title, $message, $module, $requestId, $pageId)` — `Common.php:4396`** | Yes | Yes | **The recommended default for new code.** A wrapper that does `nofitication(type 10)` per recipient (DB row + push) with no duplicate-row risk. Correctly used in Performance and PipPdp; should be the template everywhere else. |
| `Common::sendPushNotificationForMobile($deviceTokens[], ...)` — `Common.php:8876` | **No** | Yes | Push-only, no DB row. If the phone is offline at send time, the notification is gone forever — nothing to see when they come back online. Only appropriate for genuinely ephemeral alerts (e.g. SOS "all clear"). Don't use this as your only call for anything the recipient needs a persistent record of. |

### The one rule that breaks the most things: `$sendto` must be `employees.id`

Every consumer of the DB row is keyed on `employees.id`:
- The web bell (`Common::ResortNotification()`, `Common.php:6813`) joins `resort_notifications.user_id` to `employees.id`.
- The mobile in-app list (`API/InAppNotificationController.php:63`) queries `where('user_id', $this->user->GetEmployee->id)`.
- The push device-token lookup (`Common.php:7549`) is `Employee::whereIn('id', $sendto)->pluck('device_token')`.

Pass a `resort_admins.id`, a `shopkeeper_id`, or anything else, and the notification either
goes to a random unrelated employee who happens to share that numeric id, fails the DB
insert's foreign key outright, or resolves to zero device tokens — all **silently**, with no
exception surfaced to the caller. **This exact bug is why chat notifications don't work
today** (see Part 3). Before wiring a new trigger, confirm your recipient variable is really
an `employees.id`, not a `resort_admins.id` (the web-portal login identity) — they are
different id spaces on different tables, and it's an easy mix-up specifically because
you're writing web-portal code where the "current user" is usually a ResortAdmin.

### Reference implementation — copy this pattern

`app/Http/Controllers/Resorts/TimeAndAttendance/DutyRosterController.php:632`, inside
`StoreDutyRoster()`, per-recipient loop, before `DB::commit()`:

```php
try {
    Common::sendMobileNotification(
        $resort_id,
        2,                          // $type — a type sendMobileNotification actually handles
        null, null,                 // feedbackFormId, trainingId — unused here
        'New Duty Roster Assigned', // title
        'A new duty roster has been assigned to you starting ' . $startingDate->format('d M Y') . '.',
        'DutyRoster',               // module
        [$Employee],                // $sendto — ARRAY of employees.id
        $DutyRoster->id,            // request_id — for deep-linking
        false,                      // skipDbInsert — false = write the DB row too
        'duty-roster-created',      // pageId — deep-link slug for the mobile app
    );
} catch (\Exception $notificationException) {
    \Log::warning('Duty roster notification failed for employee ' . $Employee . ': ' . $notificationException->getMessage());
}
```

Why this is the template: `$type` is one `sendMobileNotification` handles; `$sendto` is
a real `employees.id` array; `$skipDbInsert=false` so both the mobile list and the web bell
get it; `$requestId` + `$pageId` set so the notification can deep-link; wrapped in
try/catch + `Log::warning` so a notification failure never rolls back the actual business
transaction.

### Footguns seen repeatedly in the existing code — don't repeat these

1. **Using `nofitication()` with `$type` ≠ 10** when the recipient needs to see this on their
   phone or after a reload. It silently does nothing beyond the live web bell.
2. **Double-pushing.** Pairing `nofitication(type 10)` (which already pushes at
   `Common.php:1553`) with a follow-up `sendMobileNotification(..., skipDbInsert:true)`
   (which pushes *again* at `Common.php:7549`) sends the same recipient two pushes for one
   event. `Incident`, `MonthlyCheckIn`, `Profile`, and `Resignation` all currently do this.
   If you need both a DB row and a push and want exactly one push, use `notifyEmployees()`.
3. **Positional-argument drift.** Both `nofitication()` and `sendMobileNotification()` take
   long positional argument lists whose meaning changes by `$type`. A confirmed live bug:
   `Survey/SurveyController.php:1070` passes the literal string `'Survey'` into the
   `$other`/request-id slot, corrupting `resort_notifications.request_id` for that row. A
   near-miss is documented in code comments at `Incident/IncidentController.php:645-653`
   (an omitted argument shifted everything after it and 500'd the endpoint). Count your
   arguments against the function signature every time, or use `notifyEmployees()`, whose
   signature is short and named-enough to be harder to get wrong.
4. **Raw `DB::table('resort_notifications')->insert(...)`** instead of a helper function —
   used in Payroll (4 call sites) and `Payroll/ShopkeeperController.php:406`. This writes an
   in-app row only; there is no push leg at all, ever, for these events.
5. **Commented-out notification calls left in shipped code.** Confirmed at
   `WorkforcePlanning/ManningResponseController.php:210`,
   `ResortAllNotificationController.php:365` and `:437`, and
   `People/Employee/EmployeeController.php:1085` and `:1131`. Someone already wrote the
   correct call and then disabled it — these are the fastest gaps to close, since the code
   to re-enable already exists.

---

## Part 2 — Confirmed working triggers, by module

What's already implemented, so you don't duplicate work and can see real in-context examples
of each pattern above.

### Leave
| Event | file:line | Rail |
|---|---|---|
| Employee applies → each approver in chain | `Leave/LeaveController.php:2145` | in-app only |
| Applied leave delegates a task | `LeaveController.php:2160` | in-app only |
| Fully approved → applicant | `LeaveController.php:2652` | in-app only |
| Fully approved → delegate | `LeaveController.php:2665` | in-app only |
| Partially approved → applicant | `LeaveController.php:2679` | in-app only |
| Rejected → applicant | `LeaveController.php:2716` | in-app only |
| Rejected → delegate cancelled | `LeaveController.php:2731` | in-app only |
| Approver suggests alternate dates | `LeaveController.php:2819` → `EmployeeLeave.php:72` | **email only** |
| Boarding pass approved/rejected | `BoardingPassController.php:295`, `:335` | in-app only |
| Birthday broadcast | `DashboardController.php:1244` | in-app only |

**Whole-module finding: Leave never calls `sendMobileNotification` once.** Every leave event
here is in-app-bell-only — an employee whose leave is approved/rejected gets nothing on
their phone. Leave is the highest-volume employee workflow in the app; this is the single
biggest push gap found in the entire audit.

### Time & Attendance
| Event | file:line | Rail |
|---|---|---|
| Duty roster created | `TimeAndAttendance/DutyRosterController.php:633` | push + in-app (the reference pattern, see Part 1) |

That's the only notification in the module.

### Survey
| Event | file:line | Rail |
|---|---|---|
| Survey created & published | `Survey/SurveyController.php:528`+`548` | push + in-app |
| Survey edited/published from edit | `SurveyController.php:327`+`340` | push + in-app |
| HR manually pings participants ("Notify") | `SurveyController.php:1070` | in-app only, **and** has the `$other='Survey'` argument-corruption bug from Part 1 §3 |

### Performance
Best-instrumented module — the only one using `notifyEmployees()` throughout.
| Event | file:line | Rail |
|---|---|---|
| Cycle created → participants, → managers | `CycleController.php:477`, `:486` | push + in-app |
| Self review submitted → manager, → HR | `ReviewController.php:225`, `:242` | push + in-app |
| Review completed → employee, → HR | `ReviewController.php:413`, `:436` | push + in-app |
| PIP/PDP assigned | `PipPdpController.php:467` | push + in-app |
| PIP/PDP form submitted | `PipPdpController.php:343` | push + in-app |
| Meeting scheduled | `PerformanceMeetingController.php:473` | push + in-app + email |
| Meeting response recorded | `PerformanceMeetingController.php:864` | push + in-app |
| KPI created / responded / approved / rejected | `KpiController.php:685+695`, `:718+728`, `:432+434`, `:472+473` | push + in-app (double-push, see Part 1 §2) |
| Monthly check-in scheduled / approved / rejected / finalized | `MonthlyCheckingController.php:580+584`, `:674+677`, `:726+729`, `:808+810` | push + in-app (double-push) |

### Grievance & Disciplinary
| Event | file:line | Rail |
|---|---|---|
| Grievance submitted (web) | `GrivanceController.php:377` | push + in-app |
| Grievance assigned to committee | `GrivanceController.php:507` | in-app only |
| Witness statement requested (grievance) | `GrivanceController.php:855`+`862` | push + in-app |
| Disciplinary case filed → committee | `DisciplinaryController.php:410` | in-app only |
| Disciplinary case filed → accused | `DisciplinaryController.php:444-470` | **email only** |
| Witness statement requested (disciplinary) | `DisciplinaryController.php:598` | in-app only |
| Committee investigation update | `DisciplinaryController.php:711` | in-app only |
| Appeal filed / hearing scheduled / decided / closed | `GrievanceAppealController.php:164`, `:232`, `:284`, `:290`/`:305` | in-app only |
| Committee created/edited (grievance) | `ConfigurationController.php:3336`, `:3489` | in-app only |

### Incident
| Event | file:line | Rail |
|---|---|---|
| Assigned to committee | `IncidentController.php:363` | in-app + email |
| Statements requested | `IncidentController.php:617`+`654` | push + in-app + email |
| Investigation report → GM | `IncidentController.php:693` | in-app + email |
| Routed to GM's delegate | `IncidentController.php:726` | in-app + email |
| Meeting scheduled | `IncidentMeetingController.php:298` | in-app + email |
| Meeting rescheduled | `IncidentMeetingController.php:450` | in-app only |

### Payroll
| Event | file:line | Rail |
|---|---|---|
| Compliance breaches (service charge, pension, EWT ×2) | `PayrollController.php:827`, `:912`, `:2961`, `:3004` | push + in-app |
| Payroll sent for approval / advanced / rejected / fully approved | `PayrollController.php:1462`, `:1311`, `:1338`, `:1358` | **in-app only — raw DB insert, no push, ever** |
| Staff-shop payments marked Paid | `ShopkeeperController.php:406` | **in-app only, raw DB insert** |
| Shop payment consent confirmed | `PaymentConsentController.php:131` | **broken — see Part 3** |

### Visa
| Event | file:line | Rail |
|---|---|---|
| Payment request created | `PaymentRequestController.php:748` | push + in-app |
| Individual fee marked Paid | `XpactEmployeeController.php:707` | push + in-app |
| Bulk renewal paid | `PaymentRequestController.php:1293` | push + in-app |
| **Scheduled**: visa/permit/insurance/medical/passport/slot expiry reminder | `app/Console/Commands/CheckVisaExpiryReminders.php:166` (daily 09:00) | push + in-app, to employee + all HR |
| **Scheduled**: deposit-refund pending reminder | `app/Console/Commands/CheckDepositRefundReminders.php:126` (daily 09:15) | push + in-app, to employee + all HR |

### Workforce Planning
All live notifications are in `ResortAllNotificationController.php`, not the dashboard/budget
controllers.
| Event | file:line | Rail |
|---|---|---|
| Manning request dispatched to departments | `ResortAllNotificationController.php:125`+`131` | in-app only (hand-written `ResortNotification::create` since `nofitication(type 2)` alone doesn't persist) |
| Same, confirmation copy to HR | `:153`+`158` | in-app only |
| Reminder to non-responding departments | `:244` (live-only) + `:272` (persisted) | push + in-app |

Highest gap density of any module in the audit — see Part 3.

### Accommodation
Cleanest module: `MaintananceContorller::createNotification()` (`:34-61`) is a proper shared
helper writing both the DB row and the push in one place.
| Event | file:line | Rail |
|---|---|---|
| Maintenance request submitted | `MaintananceContorller.php:218` | push + in-app |
| Maintenance event created (occupants, HR, creator) | `:253`, `:260`, `:267`, `:277` | push + in-app |
| Request forwarded to HOD / rejected / held / closed | `:327`, `:712`+`716`, `:781`+`787` | push + in-app (duplicate push on reject/hold/close, see Part 1 §2) |
| Task assigned to technician + requester informed | `:1099`, `:1101` | push + in-app |

### Talent Acquisition
| Event | file:line | Rail |
|---|---|---|
| Vacancy min-wage / reserved-position breach | `VacancyController.php:359`, `:384`+`394` | push + in-app |
| Hiring request submitted | `VacancyController.php:462`+`482` | push + in-app |
| Hiring request re-raised on edit | `VacancyController.php:881`+`890` | push + in-app |
| Hiring request approved (any stage, incl. final) | `ConfigController.php:649`+`667` | push + in-app, to requester + all HR + Finance + GM |
| Job application submitted (public form) | `ApplicantController.php:698` | push + in-app, to all HR |

The approval path is well wired; the candidate/interview pipeline is not — see Part 3.

### People
The best-covered module — salary advance, transfer, promotion, salary increment, resignation
and exit clearance all have real, multi-stakeholder notification chains. Full tables in the
underlying audit; representative examples:
- Salary advance: HR/Finance/GM approve or reject → employee + all three role-pools notified
  each stage (`AdvanceSalaryController.php:611-779`).
- Transfer: submission, stage-advance, rejection, hold, full approval, and a **scheduled**
  "effective today" push (`NotifyTransferOnEffectiveDate.php`, `Kernel.php:41`, 06:00 daily)
  all notify the relevant department leads + employee + HR (`TransferController.php`, several
  sites 278-1749).
- Promotion: same shape, plus its own scheduled effective-date push
  (`NotifyPromotionOnEffectiveDate.php`, `Kernel.php:48`, 06:20 daily).
- Exit clearance: form assignment, submission, and completion all notify
  (`ExitClearanceController.php`, helper `notifyExit:47`).
- Onboarding: itinerary publication fans out to the new hire, pickup driver, medical escort,
  and every meeting participant in one call (`OnboardingController.php:1512-1586`), plus a
  scheduled new-hire arrival reminder.

---

## Part 3 — Gaps to implement, prioritized

Every row below: no notification call was found in the method, verified by reading the code
(not inferred from the method name). Highest-value items first.

### P0 — confirmed broken, not just missing

| # | What's broken | Where | Fix |
|---|---|---|---|
| 1 | **Chat notifications go to the wrong recipient or nowhere.** `sendMessage()` passes `resort_admins.id` values as `$sendto` into `sendMobileNotification`, which expects `employees.id` everywhere (device-token lookup, `resort_notifications.user_id` FK). Confirmed by the controller's own comment. | `app/Http/Controllers/API/ChatBoat/ConversationController.php:253-266` (this is the **live** implementation for both web and mobile — `routes/resort_route.php:2170-2181` and `routes/api.php:616` both route here) | Map ResortAdmin ids to Employee ids before calling `sendMobileNotification`: `Employee::whereIn('Admin_Parent_id', $resortAdminIds)->pluck('id')`. One-line fix, repairs push + web bell + mobile list simultaneously since all three read the same `$sendto`. |
| 2 | Web portal chat real-time is subscribed to a channel nobody broadcasts on — `resources/views/resorts/chat/view.blade.php:81` listens on `conversation.{id}`; `app/Events/MessageSent.php:30-35` broadcasts on `chat.{id}`/`group.{id}`. Web portal real-time chat delivery is a complete no-op regardless of the push fix above. | as above | Point the JS subscription at the channel the event actually broadcasts on, or vice versa. |
| 3 | **`Resorts/ChatBoat/*` controllers are dead code** — not routed anywhere, zero notification logic inside them either. If anyone edits these believing they're fixing chat, they're editing nothing live. | `app/Http/Controllers/Resorts/ChatBoat/ChatController.php`, `ConversationController.php` | Flag for deletion, or at minimum leave a comment pointing to the real implementation. |
| 4 | `PaymentConsentController.php:131` calls `nofitication()` with a malformed/miscounted argument list (`$type[9]`, no `$sendto`) — no row is written, nothing is sent. Shopkeeper never learns their consent was recorded. | `Payroll/PaymentConsentController.php:131` | Rewrite the call with the correct positional arguments, or switch to `sendMobileNotification`. |
| 5 | Three notification calls are **commented out** in shipped code. | `WorkforcePlanning/ManningResponseController.php:210` (HOD's manning response never reaches HR); `ResortAllNotificationController.php:365` (budget sent up the chain, next approver not told); `:437` (budget sent back for revision, department head not told) | Uncomment and verify the recipient logic is still correct. |
| 6 | Two more commented-out compliance notifications. | `People/Employee/EmployeeController.php:1085`, `:1131` (TIN-required and min-wage breach — the `Compliance` row is still written, the alert to HR is not) | Same — uncomment, verify. |

### P1 — the recurring pattern: "request notifies, decision doesn't"

The single most common gap shape in this audit. A "please review this" notification exists;
the resulting approve/reject/resolve never notifies anyone.

| Event | file:line |
|---|---|
| Incident approved/rejected by GM | `Incident/IncidentController.php:750` |
| Grievance GM decision on an escalated case | `GrievanceAndDisciplinery/GrivanceController.php:683-700` |
| Grievance resolved (complainant never told the outcome of their own grievance) | `GrivanceController.php:604-620` |
| Disciplinary case resolved (accused never told) | `DisciplinaryController.php:666-676` |
| Visa payment request rejected | `Visa/PaymentRequestController.php:846-865` |
| Budget approved by GM | `WorkforcePlanning/BudgetController.php:2412-2481` |
| Talent Acquisition hiring request rejected | `TalentAcquisition/ConfigController.php:465-530` |
| Talent Acquisition hiring request put on hold | `ConfigController.php:411-464` |
| Payroll locked/finalized | `Payroll/PayrollController.php:1116-1202` |

### P1 — the recurring pattern: "create notifies, cancel/delete/edit doesn't"

| Event | file:line |
|---|---|
| Duty roster **edited** (creation is the working reference pattern) | `TimeAndAttendance/DutyRosterController.php:670` |
| Incident meeting cancelled (scheduling and rescheduling both notify) | `Incident/IncidentMeetingController.php:465` |
| Appeal hearing cancelled/rescheduled (scheduling notifies) | `GrievanceAndDisciplinery/GrievanceAppealController.php:242` |
| Performance cycle deleted | `Performance/CycleController.php:828` |
| KPI edited or deleted after HOD/XCOM was already notified | `Performance/KpiController.php:156`, `:195` |
| PIP/PDP archived/restored (assignment notifies) | `Performance/PipPdpController.php:90`, `:100`, `:189`, `:199` |
| Survey status changed manually / deleted | `Survey/SurveyController.php:769`, `:738` |
| Onboarding itinerary **edited** after publication (publishing notifies) | `People/Onboarding/OnboardingController.php:1815` |
| Exit clearance assignment deadline changed (creation notifies) | `People/ExitClearance/ExitClearanceController.php:564` |
| Boarding pass edited / self-cancelled | `Leave/BoardingPassController.php` (mobile side — see mobile doc; web side has no separate handler) |

### P1 — money and employment-status changes with no notification at all

| Event | file:line |
|---|---|
| Employee salary updated directly (bypasses the entire SalaryIncrement approval-chain module) | `People/Employee/EmployeeController.php:2146` |
| Employee status changed (activate/deactivate/terminate) | `EmployeeController.php:1590`, `:1745` |
| Bank details added/updated (classic fraud vector, no HR/Finance alert) | `EmployeeController.php:2237`, `:2270` |
| Probation confirmed / failed / extended | `People/Probation/ProbationController.php:583`, `:601`, `:677` (whole file has zero notification calls) |
| Advance-salary repayment: installment paid, note added, loan marked fully repaid | `People/Employee/AdvanceSalaryRepaymentTrackerController.php:153`, `:212`, `:237` |
| Guarantor approval required for a salary advance, but the guarantor is never asked to act (the approval silently blocks on this) | Guarantor logic referenced at `AdvanceSalaryController.php:563`, `:596` |

### P2 — module-by-module remaining gaps

**Leave**: alternate-date suggestion is email-only (`LeaveController.php:2791`); boarding-pass
mid-chain approval doesn't notify the next approver (`BoardingPassController.php:222`); the
`boardingpass.store`/`handlePassAction` routes point at controller methods that **don't
exist** (`routes/resort_route.php:681-682`).

**Time & Attendance**: all three overtime approve/reject paths are silent
(`DutyRosterController.php:1449`, `AttandanceRegisterController.php:83`,
`EmployeeController.php:1078`); HR editing an employee's punch times is silent
(`EmployeeController.php:1030`); geofence create/update/delete/toggle is silent
(`ConfigrationController.php:223-301`).

**Survey**: the entire `Reminder_notification` feature is dead — configured on the form,
persisted, read back into the edit form, and **never consumed by any job or scheduler**
(`SurveyController.php:262`,`456`,`170`; grep confirms no other reader exists). HR believes
reminders are firing; they are not. Also: no web-side response-submission handler exists at
all, so survey owners are never told a response arrived.

**Performance**: KPI actuals logged/removed doesn't notify the approving GM
(`KpiController.php:535`,`592`); cycle template attached doesn't notify reviewers who were
known to be blocked waiting for it (`CycleController.php:518`).

**Grievance/Disciplinary**: committee-assignment notification is inconsistent across the three
sibling modules — Grievance notifies on committee create+edit, Disciplinary and Incident do
not (`ConfigurationController.php:1278`,`1431` vs `:3336`,`3489`).

**Incident**: committee creation doesn't notify members (only later assignment does,
`Incident/ConfigurationController.php:384`); verify whether `CheckIncidentCompliance` and
`IncidentMeetingReminder` commands actually notify or only write silent `Compliance` rows —
the precedent elsewhere in the codebase (duty-roster's own `Compliance` row,
`DutyRosterController.php:612`) is silent, so don't assume these do either without checking.

**Payroll**: payslip emailed has no in-app companion; Full & Final settlement
creation/finalization is completely silent to the departing employee
(`PayslipController.php:312-1030`); config changes (cut-off day, deduction rules) affecting
everyone's pay are silent (`ConfigController.php:81-226`).

**Visa**: rejection doesn't notify (P1 above); wallet-to-wallet fund transfers move real money
with no notification (`FundTransferController.php:37-105`); deposit refund issued/deferred
doesn't tell the employee who's actively being reminded about it by the cron
(`PaymentDepositRequestController.php:47`); reminder-threshold config changes are silent
(`Visa/ConfigurationController.php:424`).

**Workforce Planning**: division/department/section/position create/edit/**delete** across 12
methods are all silent (`ManningController.php`, several sites); budget consolidation and
per-cell edits are silent (`BudgetController.php`).

**Accommodation**: the module's headline action — assigning an employee to a bed — doesn't
notify the employee (`AssignAccommodationController.php:261-377`), nor does moving them
(`:417`) or unassigning them (`InventoryController.php:489`). Escalation-day and
occupancy-threshold configs are display-only (colour a table row) with **no scheduled command
that ever fires on breach** — contrast Visa's proper cron-driven reminders.

**Talent Acquisition**: interview scheduling never notifies the interviewer, despite the
system knowing exactly who's being booked (`ApplicantsController.php:1313`); applicant status
advancing a round never notifies the next-round interviewer (`:1630`); offer letter and
contract sends are email-only with no internal tracking notification (`:2909`, `:3038`).

**People**: onboarding events and applicant-to-employee conversion are silent
(`OnboardingController.php:682-749`, `:184`); salary increment approves individually but not
in bulk (`SalaryIncrementController.php:937`,`973`); five controller files import
`ResortNotificationEvent` and **never call it** — reliable signal of an intended-but-unbuilt
notification: `Visa/DocumentController.php:30`, `TalentAcquisition/ApplicantsController.php:31`,
`People/OrgChart/OrganizationChartController.php:8`,
`People/Liability/LiabilityEstimationController.php:9`,
`People/Onboarding/FacilityTourCategoryController.php:8`.

**SOS**: the web-portal half of this module has exactly one notification, and it's push-only
with no DB row (`SOS/DashboardController.php:385`, mass instruction broadcast). Escalating an
SOS from Pending to Real-Active notifies nobody (`DashboardController.php:187`) — the single
highest-value gap in this module; team assignment/role/permission changes are all silent
(`SOS/ConfigurationController.php`, ~10 methods). (The SOS *alert lifecycle itself* — raise,
acknowledge, dispatch — lives in the mobile API and is well covered; see the mobile doc.)

**Support**: zero push/in-app notifications anywhere in the module — email and Pusher
(real-time-only) are the only channels. A ticket submitted with any support preference other
than "Email" notifies **literally no one** (`SupportController.php:322`).

**File Management**: best-instrumented of the smaller modules (share, upload×3 all correctly
use `employees.id`); gaps are share-revoked, permission-changes-at-scale, delete/rename/move,
and new-version-supersedes-old-file not telling anyone their document changed underneath them.

**Learning**: four call sites write the literal string `'Learning'` into the integer
`request_id` column (`LearningController.php:452`,`478`; `TrainingScheduleController.php:339`,
`566`) — breaks deep-linking from the notification into the actual record; use
`Common::notifyEmployees()` instead, which gets this right by construction. Calendar session
creation never notifies participants (`LearningCalendarController.php:57`) — largest gap in
the module. Probationary reminders fire **during a dashboard page render**, not on a schedule
(`DashboardController.php:600`) — an employee who never opens that dashboard is never
reminded. The configured `evaluation_reminder` setting has **no consumer at all**
(`ConfigurationController.php:428`) — same dead-feature shape as Survey's reminder field.

---

## Appendix — module → primary controller file map

For orientation when you go looking for something not listed above.

| Module | Primary controllers |
|---|---|
| Leave | `app/Http/Controllers/Resorts/Leave/{LeaveController,BoardingPassController,ConfigController}.php` |
| Time & Attendance | `.../TimeAndAttendance/{DutyRosterController,AttandanceRegisterController,EmployeeController,ConfigrationController}.php` |
| Survey | `.../Survey/SurveyController.php` |
| Performance | `.../Performance/{CycleController,ReviewController,PipPdpController,PerformanceMeetingController,KpiController,MonthlyCheckingController}.php` |
| Grievance & Disciplinary | `.../GrievanceAndDisciplinery/{GrivanceController,DisciplinaryController,GrievanceAppealController,ConfigurationController}.php` |
| Incident | `.../Incident/{IncidentController,IncidentMeetingController,ConfigurationController}.php` |
| Payroll | `.../Payroll/{PayrollController,PayslipController,ShopkeeperController,PaymentConsentController,ConfigController}.php` |
| Visa | `.../Visa/{PaymentRequestController,XpactEmployeeController,RenewalController,FundTransferController,PaymentDepositRequestController,ConfigurationController}.php` |
| Workforce Planning | `WorkforcePlanningDashboardController.php`, `.../ResortAllNotificationController.php`, `ManningController.php`, `ManningResponseController.php`, `BudgetController.php`, `BudgetCostController.php` |
| Accommodation | `.../Accommodation/{MaintananceContorller,AssignAccommodationController,InventoryController,AccommodationMasterController,ConfigrationController,AccommodationDashboardController}.php` |
| Talent Acquisition | `.../TalentAcquisition/{VacancyController,ConfigController,ApplicantsController,InterviewAssessmentController,OfflineInterviewController,JobAdvertisementController,JobDescriptionController}.php`, `Resorts/ApplicantController.php` |
| People | `.../People/Employee/{EmployeeController,AdvanceSalaryController,AdvanceSalaryRepaymentTrackerController,InfoUpdateController,EmployeeResignationController}.php`, `.../People/{Transfer/TransferController,Promotion/PromotionController,SalaryIncrementController,AnnouncementController}.php`, `.../People/ExitClearance/ExitClearanceController.php`, `.../People/Onboarding/OnboardingController.php`, `.../People/Probation/ProbationController.php`, `.../People/Compliances/ComplianceController.php` |
| Chat | Routed to `app/Http/Controllers/API/ChatBoat/ConversationController.php` — see mobile doc; `Resorts/ChatBoat/*` is dead code |
| SOS | `.../SOS/{DashboardController,ConfigurationController}.php` (web half only — see mobile doc for the alert lifecycle) |
| Support | `.../Support/{SupportController,SupportChatController}.php` |
| File Management | `.../FileManagment/{FileShareController,FileManageController,FilePermissionController}.php` |
| Learning | `.../learning/{LearningController,TrainingScheduleController,DashboardController,LearningCalendarController,ConfigurationController,AttendanceController,FeedbackFormController,EvaluationFormController,LearningProgramController}.php` |

Core notification functions: `app/Helpers/Common.php` — `nofitication()` (`:1447`),
`sendMobileNotification()` (`:7359`), `notifyEmployees()` (`:4396`),
`sendPushNotificationForMobile()` (`:8876`), `FCMTokenPushNotification()` (`:8815`),
device-token helpers `decodeDeviceTokens()`/`addDeviceToken()`/`removeDeviceToken()`
(`:8769`,`8786`,`8804`). Schema: `database/migrations/2025_02_28_113012_create_resort_notifications_table.php`.
Model: `app/Models/ResortNotification.php`.
