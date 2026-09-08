# Notification Events — Mobile App

Reference for the backend endpoints the **mobile app** calls (`app/Http/Controllers/API/`,
`api` guard, Passport token auth). Companion doc: `docs/notification-events-web-portal.md`
(the web portal's own controllers, `resort-admin` guard) — read that doc's Part 1 first if
you haven't; the mechanism section below is a condensed repeat of it, written for this side.

Compiled by reading the actual controller code — not inferred from method names. Every row
has a file:line. Snapshot as of 2026-09-05; re-verify before acting on it if much time has
passed. Per this project's own architecture notes, API controllers are organized by feature,
not mirrored from the web folder structure — don't assume a web module name maps to one API
file.

---

## Part 1 — How notifications actually work (condensed)

### Delivery rails
- **In-app row** (`resort_notifications` table) — read by the mobile app's own in-app list
  (`API/InAppNotificationController.php`) and the web bell. Survives being offline.
- **Push (FCM)** — the only rail that reaches a backgrounded/closed app. This is almost
  always what "push notification" means when a mobile developer asks for one.
- **Pusher** — web real-time only. Does not reach the phone at all, foregrounded or not.

### The functions
| Function | DB row? | Push? | Use for |
|---|---|---|---|
| `Common::sendMobileNotification($resortId, $type, $feedbackFormId, $trainingId, $title, $message, $module, $sendto[], $requestId, $skipDbInsert, $pageId)` — `app/Helpers/Common.php:7359` | Yes unless `$skipDbInsert=true` | Yes | **The one you'll use for almost everything.** `$sendto` is an array of `employees.id`. `$type` must be a value the function recognizes (1–4 today) — an unrecognized type silently returns `null`, no error, no push. |
| `Common::nofitication($resortId, $type, ...)` — `Common.php:1447` | Only if `$type===10` | Only if `$type===10` | Used from the API layer for the "both web bell and mobile, single recipient" case (Incident, Resignation, MonthlyCheckIn, Profile all pair it with a `sendMobileNotification(skipDbInsert:true)` call — watch out, this causes a double push, see below). |
| `Common::notifyEmployees($resortId, $empIds[], ...)` — `Common.php:4396` | Yes | Yes | Multi-recipient, exactly-once-push wrapper. Used by `PerformanceReviewController` and `PipPdpController`. Prefer this over manually pairing the two functions above. |
| `Common::sendPushNotificationForMobile($deviceTokens[], ...)` — `Common.php:8876` | **No** | Yes | Push-only. Used directly (bypassing the DB) by SOS for siren-style alerts and by a few others. If the phone is offline, the notification is gone — no record anywhere. Don't use this as the only call for anything that needs a persistent trail. |

### The rule that breaks the most things: `$sendto` must be `employees.id`

Every reader is keyed on `employees.id`: `InAppNotificationController.php:63` does
`ResortNotification::where('user_id', $this->user->GetEmployee->id)`, and the push
device-token lookup (`Common.php:7549`) is `Employee::whereIn('id', $sendto)`. This is
the exact bug in chat today (below) — chat's recipient ids are `resort_admins.id`, a
completely different id space, and everything downstream silently fails or misfires. The
codebase already has the correct fix pattern in two places worth copying:
`MonthlyCheckInController.php:781`/`:846` and `PipPdpController.php:220` —
`Employee::where('Admin_Parent_id', $resortAdminId)->first()` translates a ResortAdmin id
into the Employee id the notification system actually needs.

### Reference implementation — the cleanest example in the API layer

`app/Http/Controllers/API/IncidentController.php:427` (report) and `:1052` (statement
submitted) — pairs `nofitication(type=10)` with `sendMobileNotification(skipDbInsert:true)`
correctly for "both web bell and push, one DB row." (Minor wart: this pattern does send two
pushes, since both functions push independently — see footgun #2 below. `notifyEmployees()`
avoids that; use it for new code instead of manually pairing these two.)

`RequestController.php` (salary advance / loan requests) is the most completely-covered
controller in the whole API layer — every stage (submit, guarantor notified, HR notified,
Finance notified, GM notified, each approve/reject) fires correctly. If you want to see the
full shape of a well-built multi-stakeholder notification chain, read
`RequestController.php:216-541`.

### Footguns

1. **Unrecognized `$type` in `sendMobileNotification`** → silent `null`, nothing sent, no
   error surfaced to your endpoint's response.
2. **Double push.** Pairing `nofitication(type 10)` with `sendMobileNotification(...,
   skipDbInsert:true)` pushes twice — confirmed at `IncidentController.php:427/437`,
   `MonthlyCheckInController.php:785/788` and `:849/852`, `ProfileController.php:240/250`,
   `ResignationController.php:262-297`. Prefer `notifyEmployees()`, which pushes once.
3. **`type=3` (Announcement) never writes a DB row** (`Common.php:7367`) — push-only,
   invisible in the in-app list if the user wasn't there to see the push arrive.
4. **`Common::FindResortHR()` returns only the first matching HR employee.** Several
   controllers still use it where a resort with more than one HR needs
   `Common::getResortHrEmployeeIds()` instead (all HR notified). Still on the old call:
   `DisciplinaryController.php:161`, `StaffAccommodationController.php:385`,
   `AccommodationController.php:1915`, the medical-certificate path in `ClinicController.php`.
5. **Recipient id-domain mix-ups aren't limited to chat.** `ShopController.php:237` writes a
   raw `resort_notifications` insert keyed on `shopkeeper_id` — a third id space, distinct
   from both `employees.id` and `resort_admins.id`. It will never surface in the standard
   employee-scoped readers. If this is intentional (a separate shopkeeper-facing surface),
   fine — just don't copy this pattern expecting it to reach an employee's phone.

---

## Part 2 — Confirmed working triggers, by controller

### LeaveController.php
| Event | file:line | Recipient |
|---|---|---|
| Leave application submitted | `:771` | Every approver in the generated chain (widened to include department EXCOM when the stage is HOD) |
| Approver acts on a stage | `:4130` | Applicant |
| Final approval (rank 3 or 8) | `:4161` | Applicant |
| Rejection | `:4190` | Applicant |

### BoardingPassController.php
The most complete chain-advance pattern in the API layer — copy `:1307` for any other
multi-stage approval you build.
| Event | file:line | Recipient |
|---|---|---|
| Pass request submitted | `:375` | Every approver in the flow (SM, HR, HOD; HOD widened to include EXCOM) |
| Approve/reject a stage | `:1280` | Requester |
| **Stage approved → next approver's turn** | `:1307` | `$nextPendingStatus->approver_id` — the correct "advance the chain" pattern LeaveController is missing |
| Emergency-cancel an approved pass | `:1481` | Requester |
| Manifest created | `:1743`, `:1769` | All employees on the manifest; HR + Security staff |
| Pass assigned to security officers | `:1941` | The assigned officers (note: sends duplicate notifications per officer — the call sits inside a loop but passes the whole array each iteration) |

### SOSController.php
| Event | file:line | Recipient |
|---|---|---|
| Panic button pressed | `:169`+`:171` | The active Security Manager (double-push, see footgun #2) |
| SM rejects | `:270`+`:271` | Initiating employee |
| SM activates with teams | `:360`+`:361` | Selected team members |
| SM activates, initiator told | `:364`+`:365` | Initiator |
| Resort-wide broadcast | `:379`+`:380` | Every active employee except the actor |
| SM sends SOS chat instruction | `:1489`+`:1490` | The initiating employee only |

### RequestController.php (salary advance / loan)
See "Reference implementation" above — fully covered, no gaps found.

### GrievanceController.php (mobile filing)
| Event | file:line | Recipient |
|---|---|---|
| Grievance filed from mobile | `:252` | All HR |
| Identity-disclosure response | `:665` | The requesting committee member |

### IncidentController.php (mobile filing)
| Event | file:line | Recipient |
|---|---|---|
| Incident reported | `:427`+`:437` | All HR + the employee's reporting manager (double-push, footgun #2) |
| Statement submitted | `:1052`+`:1062` | Every unique member of every assigned committee |

Cleanest "web bell + push" pattern in the file; only the double-push wart needs fixing.

### SurveyController.php / DisciplinaryController.php / AnnouncementController.php (mobile)
| Event | file:line | Recipient |
|---|---|---|
| Survey completed | `SurveyController.php:384` | All HR |
| Disciplinary acknowledgement submitted | `DisciplinaryController.php:164` | **Single first-matched HR only** — footgun #4 |
| Announcement "Congratulate" tapped | `AnnouncementController.php:94` | The announcement's employee — `type=3`, so push-only, never in-app (footgun #3) |

### ResignationController.php
| Event | file:line | Recipient |
|---|---|---|
| Resignation submitted | `:262`, `:279-288`, `:297` | HOD (push + web bell) and HR |
| Exit interview form submitted | `:436-445` | HR |

### MonthlyCheckInController.php
| Event | file:line | Recipient |
|---|---|---|
| Meeting scheduled / rescheduled | `:284`,`:302`,`:368` | Employee |
| Employee confirms | `:617`+`:631` | Meeting creator |
| Employee approves / rejects | `:785-790`,`:849-854` | Meeting creator |

### ClinicController.php
| Event | file:line | Recipient |
|---|---|---|
| Appointment booked | `:270` | Assigned doctor |
| Appointment status changed | `:755` | The employee |
| Treatment submitted | `:887` | The patient |
| Medical certificate issued **against a leave request** | `:1293` | Remaining pending leave approvers |
| Sick-leave stage approved/rejected by clinic staff | `:1473`,`:1497` | Employee / next approvers |

### AccommodationController.php
Long, mostly-complete maintenance chain — HR→Eng HOD→technician→complete→HR→employee all
notify (lines 1365-2661). See Part 3 for the one conspicuous hole in the middle of the chain.

### StaffAccommodationController.php
| Event | file:line | Recipient |
|---|---|---|
| Employee raises a room maintenance request | `:387` | HR (single-HR only, footgun #4) |

### CalendarController.php
| Event | file:line | Recipient |
|---|---|---|
| Event created | `:149` | Each invitee, one call per person inside the loop — correct pattern |

### PerformanceReviewController.php / PipPdpController.php
The only two controllers in the API layer using `notifyEmployees()`. No gaps found —
`PipPdpController.php:220` is also where the correct ResortAdmin→Employee id translation
lives; copy it for the chat fix.

### LearningController.php (mobile)
| Event | file:line | Recipient |
|---|---|---|
| Feedback form submitted | `:713→758→1061` | HR + L&D managers |
| Evaluation form submitted | `:892→933→1061` | HR + L&D managers |

### FileManagementController.php (mobile)
| Event | file:line | Recipient |
|---|---|---|
| Document uploaded | `:414`, `:431` | HR (minus uploader) + uploader confirmation |

### ProfileController.php
| Event | file:line | Recipient |
|---|---|---|
| Profile-info change requested | `:240`+`:250` | All HR (double-push, footgun #2) |

### ShopController.php
| Event | file:line | Recipient |
|---|---|---|
| Payroll-deduction consent accepted/rejected | `:237` | Shopkeeper — **raw DB insert, no helper function, no push, and a third id-domain (`shopkeeper_id`)** — see footgun #5 |

---

## Part 3 — Gaps to implement, prioritized

### P0 — confirmed broken, not missing

| # | What's broken | Where | Fix |
|---|---|---|---|
| 1 | **Chat: wrong recipient id-domain, same root cause described in the web-portal doc.** `sendMessage()` resolves `$recipientIds` from `Conversation`/`GroupChatMember`, both of which store `resort_admins.id` (confirmed by the controller's own comment at `:29-31`), then passes them straight into `sendMobileNotification`, which expects `employees.id`. | `app/Http/Controllers/API/ChatBoat/ConversationController.php:253-266` (routed from both `routes/api.php:616` and `routes/resort_route.php:2170`) | `Employee::whereIn('Admin_Parent_id', $recipientIds)->pluck('id')` before the `sendMobileNotification` call. Copy the pattern already correct in `PipPdpController.php:220`. |
| 2 | **A second, entirely separate mobile chat endpoint sends zero notifications by any mechanism.** No `nofitication`, no `sendMobileNotification`, no broadcast. Its only outbound call posts to a dead legacy URL (`BASE_URL`, unset in every env file) inside a try/catch, so it fails silently. | `EmployeeChatController.php:31` (`sendMessage`, routed at `routes/api.php:322`) | Decide whether this endpoint is still meant to be live — if so, add a real `sendMobileNotification` call with correct `employees.id` recipients. Also note `routes/api.php:321` routes to `checkConnection`, a method that **doesn't exist** in the controller. |
| 3 | **An "Unsafe" self-report during an active SOS raises nothing.** Arguably the single most urgent inbound signal in the app. | `SOSController.php:430` (`SOSSafeStatus`) | Add a `sendMobileNotification` to the active Security Manager, same shape as the panic-button trigger at `:169`. |
| 4 | **SOS "all clear" has no persistent record.** `completeSOSUpdateStatus` calls `sendPushNotificationForMobile` only (`:1238`) — no `sendMobileNotification`, so no `resort_notifications` row, unlike every other SOS transition in the file. | `SOSController.php:1191-1238` | Add the paired `sendMobileNotification` call, matching the rest of the file's pattern. |
| 5 | **SOS chat is one-directional.** `sosSendChatMessage` always resolves the recipient as `emp_initiated_by`; when the initiating employee is the one replying, the guard at `:1488` fails silently and the Security Manager never learns of the reply. | `SOSController.php:1450-1490` | Resolve the recipient based on who's *not* the sender, not a hardcoded field. |
| 6 | **Boarding pass requests created inside a leave application bypass approver notification entirely.** `leaveAdd()` creates the travel pass and its approval chain directly; the standalone `BoardingPassController::boardingPassAdd` notifies SM/HR/HOD for the identical record shape — this code path doesn't. | `LeaveController.php:531-549` | Call the same notification logic `BoardingPassController.php:375` uses, or extract it to a shared helper both call. |
| 7 | **`handleLeaveAction` never notifies the next approver in the chain** — only the applicant. Contrast `BoardingPassController.php:1307`, which explicitly looks up and notifies the next pending approver. | `LeaveController.php:4130` (the whole method) | Add the same `$nextPendingStatus`-style lookup and notify call. |
| 8 | **Overtime approve/reject never notifies the employee**, anywhere — confirmed across the entire `TimeAndAttendanceController.php` (zero notification calls in the file) and specifically at `approveRejectOT` (`:3220`). Money-affecting decision with no notice. | `TimeAndAttendanceController.php:3220` | Add a `sendMobileNotification` call to `[$employee->id]` on both approve and reject branches. |

### P1 — sizeable single-file gaps

| Event | file:line |
|---|---|
| `leaveUpdate` — editing a pending leave re-opens the approval chain, notifies nobody | `LeaveController.php:2159` |
| `boardingPassUpdate` / `boardingPassCancel` — approvers not told a pending pass changed or was withdrawn | `BoardingPassController.php:2092`, `:2185` |
| `SOConfirmArrivalDept` — security officer marks departed/arrived, traveller + HR told nothing | `BoardingPassController.php:2043` |
| `passTimeupdateHRAndSM` — employee's travel time changed under them, not notified | `BoardingPassController.php:1845` |
| `drillRealSOS` / `SOSAcknowledge` — SOS classification change and team-member acknowledgement both silent | `SOSController.php:1148`, `:758` |
| **Entire `TimeAndAttendanceController.php`** (4300+ lines) — zero notification calls anywhere. Also flag: `manualCheckIn`/`manualCheckOut` (`:1425`,`:1792`), `geofenceEvent` mid-shift exit (`:2101`), `hodMarkAttendance*` (`:2829`,`:2960`) | whole file |
| **Entire `OnBoardingController.php`** — zero notification calls. `taskAction` (assigned staff approve/reject/complete a pickup or medical-escort task, `:537`), selfie upload (`:685`), handbook acknowledgement (`:745`) | whole file |
| `houseKeepingAssingHODtoEmp` — the one hop in the maintenance chain (HR→HOD is notified, HOD→employee is not) that actually reaches a line worker's phone | `AccommodationController.php:1610` |
| `engDepartmentStaffMaintenanceReqAccept` — every other transition in this chain notifies, this one doesn't | `AccommodationController.php:2319` |
| `empAcceptHousekeeping`, `empAddTaskHousekeeping` — HOD never learns a task was picked up or logged | `AccommodationController.php:1671`, `:1731` |
| `completeTaskStatus` — employee confirms a room repair is done, HR/engineering not told | `StaffAccommodationController.php:550` |

### P2 — smaller/lower-frequency gaps

- `submitWitnessStatement`, `InformalResolution` (grievance) — witness/HR not notified.
  `GrievanceController.php:757`, `:288`.
- `markAttendance`, `ldManagerMarkAttendanceStore` (learning) — no absence notification.
  `LearningController.php:370`, `:1429`.
- `deleteFile` (mobile file management) — delete on an HR-visible document is silent.
  `FileManagementController.php:522`.
- `changePassword`, `changeProfileImage` (profile) — no confirmation notification; password
  change in particular is a security-visibility gap worth a look. `ProfileController.php:313`,
  `:383`.
- `resignationWithdraw`, `empConfirmMeeting` (resignation) — HOD/HR left with a stale queue
  item; compare `MonthlyCheckInController::employeeConfirmMeeting`, which does notify for the
  identical business event in a different module. `ResignationController.php:328`, `:468`.
- `postMeetingEmployeeComment` — manager never told the employee replied on a check-in.
  `MonthlyCheckInController.php:646`.
- `EmployeeDocumentController::employeeDocument` — HR never learns a passport/visa document
  was uploaded, inconsistent with `FileManagementController.php:414`, which does notify on
  upload. `EmployeeDocumentController.php:20`.
- Two commented-out `sendMobileNotification` calls for a Birthday-Wish-reply feature —
  currently disabled. `InAppNotificationController.php:237-238`.

---

## Appendix — controller → business area map

| Area | File |
|---|---|
| Leave | `LeaveController.php` |
| Boarding / island pass | `BoardingPassController.php` |
| SOS | `SOSController.php` |
| Chat (individual + group, shared with web) | `ChatBoat/ConversationController.php`, `ChatBoat/ChatController.php` |
| Employee-to-employee chat (separate, currently non-functional) | `EmployeeChatController.php` |
| Salary advance / loan | `RequestController.php` |
| Grievance | `GrievanceController.php` |
| Incident | `IncidentController.php` |
| Survey / Disciplinary / Announcement | `SurveyController.php`, `DisciplinaryController.php`, `AnnouncementController.php` |
| Resignation / exit | `ResignationController.php` |
| Monthly check-in | `MonthlyCheckInController.php` |
| Clinic | `ClinicController.php` |
| Accommodation / maintenance | `AccommodationController.php`, `StaffAccommodationController.php` |
| Attendance / duty roster viewing / OT | `TimeAndAttendanceController.php` |
| Onboarding | `OnBoardingController.php` |
| Performance review / PIP-PDP | `PerformanceReviewController.php`, `PipPdpController.php` |
| Learning | `LearningController.php` |
| File management | `FileManagementController.php` |
| Profile / device token registration | `ProfileController.php`, `LoginController.php` (device token add/remove on login/logout) |
| Shop / payroll deduction consent | `ShopController.php` |
| Calendar | `CalendarController.php` |
| In-app notification list (read side) | `InAppNotificationController.php` |

Core functions live in `app/Helpers/Common.php` — see the web-portal doc's Appendix for the
full list of line numbers; they're the same functions, called from both sides of the app.
