# Keeping the notification system fixed — guardrails

Companion to `docs/notification-events-web-portal.md` and `docs/notification-events-mobile-app.md`.
Those two docs are a one-time catalog — implementing every item in them fixes what's broken
*today*. This doc is the smaller, ongoing piece: three lightweight additions that stop the
same class of bug from quietly reappearing on the next feature, without building a new
observability platform.

Priority order below is deliberate — #1 is the one thing worth doing even if nothing else
here happens.

---

## 1. A guard inside the shared functions, not 150 call sites — DONE

The chat bug wasn't "forgot to call the function." It was calling the function with the
wrong *kind* of id — a `resort_admins.id` where `employees.id` was expected — and every
layer downstream failed silently: no exception, no log entry pointing at the real cause,
just nothing arriving. That's the most expensive kind of bug, because nobody sees it happen;
someone eventually reports "notifications don't work" weeks later with no lead.

Implemented in `Common::sendMobileNotification()` and `Common::notifyEmployees()`
(`app/Helpers/Common.php`): before doing anything else with `$sendto`/`$empIds`, both now
filter against `Employee::whereIn('id', ...)`, log any id that doesn't resolve
(`local.ERROR`, tagged with the invalid ids + module + resort), and proceed with only the
valid ids instead of dropping the whole batch. Verified against real data: a batch mixing a
real `employees.id` with a `resort_admins.id` now notifies the real employee and logs the bad
one, instead of silently notifying nobody.

---

## 2. Surface push failures somewhere a human will actually see them — DONE (minimal)

Right now, a failed push (bad FCM credentials, network error, auth-token fetch failure) does
this: `\Log::error(...)` or `\Log::warning(...)` inside `sendPushNotificationForMobile()`
(`Common.php:8876`) and stops. That line sits in a log file nobody tails. The one exception
already handled well: `NotRegistered`/`InvalidRegistration` responses trigger automatic
dead-token cleanup (`removeDeviceToken()`) — that part doesn't need touching.

Implemented the minimal option: `app/Console/Commands/NotificationFailureDigest.php`
(`notifications:failure-digest`, scheduled daily at 08:00 in `Kernel.php`) scans the last 24h
of `storage/logs/laravel.log` for the known FCM failure patterns (credentials missing, JWT
signing failed, OAuth token failed, cURL error, send error) plus the two new guard-#1 log
lines, and writes one `NOTIFICATION_FAILURE_DIGEST` summary line with per-pattern counts.
Verified against the real local log (69 failures counted correctly across 3 patterns).

Upgrade path if this shows the problem is recurring and worth drilling into: a
`notification_failures` table (`resort_id, module, recipient_employee_id, reason,
created_at`), written alongside/instead of the log line on genuine send failures, queryable
("show me every failure for module X this week") and listable in the admin UI later using the
same list-modal component already built for Pending Departments. Not built — the digest is
enough until it proves there's real volume to dig into.

---

## 3. A short checklist for anyone adding a new "this should notify" feature — DONE (see CLAUDE.md)

Not a process, not a gate that blocks anyone — just the six questions that, if asked, would
have prevented every bug pattern found in the audit. Added to `CLAUDE.md` under "Before
adding a notification feature" as a standing rule, the same way the existing hard invariants
are documented there, rather than left as a one-off doc nobody re-reads:

1. **Is `$sendto` actually `employees.id`?** Not `resort_admins.id` (the web-portal login
   identity — easy to reach for by accident since that's usually "the current user" in
   web-portal code), not a shopkeeper id, not anything else. This is the one that broke chat.
2. **Are you using `Common::notifyEmployees()`?** That's the one function that gets both
   channels right with one call and no duplicate rows. Reach for it by default. Only step
   outside it if you have a specific reason (e.g. a genuinely ephemeral, no-record-needed
   alert, where `sendPushNotificationForMobile()` alone is fine).
3. **Did you set `$requestId` and `$pageId`?** Without them the notification can't deep-link
   to the record it's about — the recipient gets a title and a body and nowhere to tap.
4. **Is the call wrapped in try/catch?** A notification failure should never roll back the
   business transaction it's attached to. Copy the try/catch shown in both audit docs'
   "reference implementation" section.
5. **If this is one side of an approval/request flow, does the other side notify too?** The
   single most common gap found in the audit was "the request pings someone, the decision
   doesn't" (or vice versa). If you're adding "HR gets notified when X is submitted," ask in
   the same PR whether the submitter gets notified when X is approved/rejected.
6. **If this is a create action, what happens on edit/cancel/delete?** The second most common
   gap: creation notifies, changing or removing the same record doesn't. Doesn't need to be
   built in the same PR, but it should be a conscious decision, not an oversight — note it in
   the two catalog docs if you're deliberately deferring it.

---

## What this doesn't cover

Same caveats as before, worth repeating here since this doc is specifically about durability:
this is backend-only (the mobile app's own push-handling/permissions/deep-link routing is a
separate, unaudited surface), and it doesn't replace actually testing on real devices after
implementation — a guardrail that catches a wrong id in a log line is not the same as
confirming a real phone received a real push.
