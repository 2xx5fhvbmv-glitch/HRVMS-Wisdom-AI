# Keeping the notification system fixed — guardrails

Companion to `docs/notification-events-web-portal.md` and `docs/notification-events-mobile-app.md`.
Those two docs are a one-time catalog — implementing every item in them fixes what's broken
*today*. This doc is the smaller, ongoing piece: three lightweight additions that stop the
same class of bug from quietly reappearing on the next feature, without building a new
observability platform.

Priority order below is deliberate — #1 is the one thing worth doing even if nothing else
here happens.

---

## 1. A guard inside the shared functions, not 150 call sites

The chat bug wasn't "forgot to call the function." It was calling the function with the
wrong *kind* of id — a `resort_admins.id` where `employees.id` was expected — and every
layer downstream failed silently: no exception, no log entry pointing at the real cause,
just nothing arriving. That's the most expensive kind of bug, because nobody sees it happen;
someone eventually reports "notifications don't work" weeks later with no lead.

**The fix is one check, in one place**, not a review pass over every caller:

In `Common::sendMobileNotification()` and `Common::notifyEmployees()`
(`app/Helpers/Common.php:7359` and `:4396`), before doing anything else with `$sendto`:

```php
$validIds = Employee::whereIn('id', (array) $sendto)->pluck('id')->all();
$invalidIds = array_diff((array) $sendto, $validIds);
if (!empty($invalidIds)) {
    \Log::error('sendMobileNotification: $sendto contains id(s) not present in employees table — likely a resort_admins.id or other wrong id-domain', [
        'module' => $module,
        'invalid_ids' => $invalidIds,
        'resort_id' => $resortId,
    ]);
}
// proceed with $validIds only — don't let one bad id silently drop the whole batch either
```

This doesn't change behavior for any correct caller — it only turns *today's* silent failure
mode into a specific, greppable log line the moment it happens, for every future caller too,
without relying on anyone remembering to check by hand. It would have caught the chat bug on
day one instead of after users reported it.

**Effort:** one function, a few lines, no schema change. This is the highest-leverage single
change in this whole doc.

---

## 2. Surface push failures somewhere a human will actually see them

Right now, a failed push (bad FCM credentials, network error, auth-token fetch failure) does
this: `\Log::error(...)` or `\Log::warning(...)` inside `sendPushNotificationForMobile()`
(`Common.php:8876`) and stops. That line sits in a log file nobody tails. The one exception
already handled well: `NotRegistered`/`InvalidRegistration` responses trigger automatic
dead-token cleanup (`removeDeviceToken()`) — that part doesn't need touching.

Two options, cheapest first:

- **Minimal — a scheduled digest.** A new `app/Console/Commands` entry, run daily, that
  greps the last 24h of the log for the FCM error patterns already being written and emails
  (or posts to whatever channel HR/dev already watches) a one-line count: "N push failures in
  the last 24h, top modules: X, Y." Cheap, no schema change, tells you *when something is
  wrong* without telling you every detail.

- **Better — a small failure table.** Add a `notification_failures` table
  (`resort_id, module, recipient_employee_id, reason, created_at`), and have
  `sendPushNotificationForMobile()` write a row there instead of/alongside the log line on
  genuine send failures (not on the already-handled dead-token case). This is queryable —
  "show me every failure for module X this week," "which employee's token keeps failing" —
  and could sit behind a simple list view using the same list-modal component already built
  for Pending Departments if you want it visible in the admin UI later. More useful than the
  digest, still small (one table, one write site).

Recommend starting with the digest — it's near-zero effort and already tells you whether #1's
new log line (or any other failure) is firing at all. Upgrade to the table only if the digest
shows this is a recurring problem worth drilling into.

---

## 3. A short checklist for anyone adding a new "this should notify" feature

Not a process, not a gate that blocks anyone — just the six questions that, if asked, would
have prevented every bug pattern found in the audit. Worth pinning wherever this team keeps
PR/review conventions, or dropping into `CLAUDE.md` as a standing rule the same way the
existing hard invariants are documented there.

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
   business transaction it's attached to. Copy the try/catch shown in both audit docs' "
   reference implementation" section.
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
