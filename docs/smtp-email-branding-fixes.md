# Outbound email — wrong sender & wrong logo

For the backend developer. Found while investigating three reported issues:
payslip emails showing Wisdom's address instead of the client's, the
probation-letter email showing a garbled sender address, and Wisdom's logo
appearing on emails that should be client-branded. **No code has been
changed** — this is a findings + requirements doc only, from a full
read-only investigation of the mail-sending code.

---

## How outbound mail identity works today (context for everything below)

- `resort_smtp_configs` (model `App\Models\ResortSmtpConfig`) — one row per
  resort: `host`, `port`, `username`, `password` (encrypted), `encryption`,
  `from_address`, `from_name`. Managed from **Resort > Email Configuration**
  (`EmailConfigController`, view `resources/views/resorts/emailconfig/index.blade.php`).
- `App\Http\Middleware\ApplyResortSmtpConfig` runs before every
  resort-admin (web) and mobile (`api` guard) request. If a
  `resort-admin`/`api`/`temp-clinic-doctor` user is authenticated, it calls
  `Common::applyResortSmtpConfig($user->resort_id)`
  ([Common.php:2992](../app/Helpers/Common.php#L2992)), which looks up that
  resort's `resort_smtp_configs` row and overrides, **all in one call**:
  `mail.mailers.smtp.host/port/username/password/encryption` **and**
  `mail.from.address/mail.from.name`.
- It's registered on the whole `resort/*` route group
  (`routes/resort_route.php:63`) and the main `auth:api` group
  (`routes/api.php:24`), so it covers essentially every controller action
  in the app.
- If no row exists for that `resort_id`, the function returns early and
  does **nothing** — `mail.from.*` and `mail.mailers.smtp.*` stay at the
  `.env` defaults, which are Wisdom's own Zoho mailbox
  (`MAIL_HOST=smtppro.zoho.com`, `MAIL_FROM_ADDRESS=hello@thewisdom.ai`).
- No mail-sending file anywhere in the app calls `->from()` to override
  this (checked every `Mail::`/`Mailable` call site) — so this one
  middleware is the single source of truth for sender identity on
  everything except the two flows described in "What's already correct"
  below.

---

## Issue 1 — Payslip (and potentially others) sending from Wisdom's address

**Root cause: a silent fallback, not a code fork.** `Payslip` sending
(web: `PayslipController::sharePayslip()` line 375; mobile:
`API/PayrollController::shareEmailPayslip()` line 825) uses the exact same
`Mail::to(...)->send(new SharePayslipMail(...))` pattern, under the exact
same middleware, as every other resort-scoped email in the app — there is
no special-cased "use Wisdom's identity for payslips" branch anywhere.
That means the most likely explanation is that
`ResortSmtpConfig::where('resort_id', $resortId)->first()` returned `null`
for the resort/account this was tested on — i.e. no SMTP config row exists
(or was saved) for that specific resort — at which point the app silently
falls back to Wisdom's Zoho identity with **no log line, no warning, no
way to tell from the email itself that this happened.**

**What to check/fix:**

1. Confirm a `resort_smtp_configs` row actually exists for the resort that
   was tested, with `resort_id` matching that resort's actual `resorts.id`
   (not a typo'd/duplicate resort record). A read-only tinker check against
   this project's local dev DB found **zero rows** in
   `resort_smtp_configs` — worth running the same query
   (`ResortSmtpConfig::all()`) against the environment the bug was seen in
   to confirm the row is really there.
2. Add a log line (e.g. `Log::warning`) inside
   `Common::applyResortSmtpConfig()` when `$config` is null, so a missing
   SMTP config is visible in the logs instead of silently sending as
   Wisdom — this alone would have made the payslip issue diagnosable in
   minutes instead of needing this investigation.
3. **Latent bug worth fixing at the same time, not urgent today:**
   `app/Jobs/TaEmailSent.php` is a queued job (`ShouldQueue`) that
   explicitly re-calls `Common::applyResortSmtpConfig()` inside its own
   `handle()`, with a comment explaining queue workers run in a separate
   process from the web request and don't inherit the middleware's
   `config()` override. `QUEUE_CONNECTION=sync` in `.env` currently masks
   this everywhere else (queued jobs run inline in the same process), but
   no other mailable/job in the app does this defensive re-apply. If the
   queue driver is ever changed to `database`/`redis`/`sqs` — a completely
   normal infra change — every other queued email will silently start
   sending as Wisdom again. Worth either (a) auditing every
   `ShouldQueue` mailable/job for the same re-apply `TaEmailSent` already
   does, or (b) moving the SMTP-config resolution into the `Mailable`s
   themselves (e.g. an overridden `build()` base method) so it doesn't
   depend on which process sends the email.

---

## Issue 2 — Probation-letter email shows a garbled sender address

Reported actual value seen:
`wisdom.ai2024_at_gmail_com_7vckxc5vtv2933_d9a18aba@icloud.com` instead of
`wisdom.ai2024@gmail.com`.

**This is Apple/iCloud Mail's own synthetic "unverified sender"
placeholder**, not something generated by this app's templates. iCloud
(and other DMARC-enforcing receivers) substitutes a masked address in this
exact shape — original address embedded with substitutions, a random
token, a short hash, `@icloud.com` domain — when a message's `From:`
header claims a domain (here `gmail.com`) that the message **fails to
authenticate for** (SPF/DKIM/DMARC) on delivery. The message still arrives
in the inbox; only the displayed sender is masked.

**Why this can happen here:** `Common::applyResortSmtpConfig()`
([Common.php:3004-3012](../app/Helpers/Common.php#L3004)) sets the SMTP
transport (`host`/`port`/`username`/`password`/`encryption`) and the
`From:` header (`from_address`/`from_name`) from the **same**
`resort_smtp_configs` row, in one call. SMTP itself does not require these
to be consistent — you can authenticate to any SMTP server and still write
any `From:` address into the message body; the server will relay it
regardless. It's the *receiving* provider that checks whether the actual
sending server was authorized (via SPF/DKIM records) to send on behalf of
the claimed domain. Since the `from_address` correctly resolved to
`wisdom.ai2024@gmail.com`, the row exists and is being read — but the
`host`/`username`/`password` on that same row are what actually
authenticate the SMTP session, and if those don't genuinely correspond to
Google's own authenticated relay for that mailbox, Gmail's domain-level
DMARC policy will fail on delivery and a strict receiver like iCloud masks
the sender rather than bouncing it outright.

**What to check/fix:**

1. Check the actual `host`/`port`/`encryption`/`username` saved on this
   resort's `resort_smtp_configs` row. If `from_address` is a Gmail
   address, `host` needs to genuinely be `smtp.gmail.com` (port 587 +
   `tls`, or port 465 + `ssl`) with a **Gmail App Password** for that exact
   mailbox — the normal account password will not work for SMTP once
   2-Step Verification is on, which is an extremely common setup mistake.
2. **Form gap that makes this mistake easy in the first place:**
   `resources/views/resorts/emailconfig/index.blade.php` (lines 43–73) —
   "SMTP Host," "SMTP Username," "SMTP Password," and "From Address" are
   four unrelated free-text fields with zero guidance that they need to be
   a *consistent* set (i.e. the host/credentials must actually correspond
   to the domain being claimed in "From Address"). There's also no
   placeholder/example distinguishing "From Address" (the email) from
   "From Name" (the display name) — worth adding help text for both, since
   a client could just as easily type their own address into *both*
   fields, which produces the same kind of garbled display in many mail
   clients.
3. The existing **"Send Test Email"** feature
   (`EmailConfigController::test()`) is the right place to catch this
   before it reaches real employees — but only if the test is sent to an
   address on a strict, DMARC-enforcing provider (iCloud, Outlook) rather
   than back to a Gmail/Google Workspace address, since Gmail-to-Gmail
   delivery doesn't necessarily surface the same masking. Consider adding
   a note near the "Recipient Address" field on the test-email form
   suggesting the client test with a non-Gmail address if their "From"
   domain is Gmail.

---

## Issue 3 — Wisdom's logo (and branding) shows on every email, not the client's

**Root cause, found directly:**
`resources/views/emails/layouts/header.blade.php` hardcodes:

```blade
<img src="{{ URL::asset('assets/images/header_logo.png') }}" style="max-width: 185px;"/>
```

— Wisdom's own static logo, with zero resort-awareness. Likewise
`resources/views/emails/layouts/footer.blade.php` hardcodes
`Copyright © ... HRVMS-WisdomAI` with no resort-name substitution.

`resources/views/emails/layouts/content.blade.php` (`@include`s both of
the above) is the shared wrapper behind essentially every transactional
email in the app: `emails.commonEmail` (payslip, probation letters,
promotion/transfer/exit-clearance letters, SMTP test emails, and more —
confirmed by grepping every `Mail::`/`Mailable` call site) and the
candidate-facing `emails.offerLetter` / `emails.contract` templates all
`@extends('emails.layouts.content')`. One hardcoded header/footer,
inherited everywhere.

**The fix is cheap — the pieces already exist, they're just not wired
together:**

- `resorts.email_logo` — a column that **already exists** on the `resorts`
  table (in `App\Models\Resort`'s `$fillable`) with a name that makes its
  intended purpose obvious, but nothing in the codebase reads it, writes
  it, or exposes a form field for it. It's a dead, unfinished column.
- `resorts.logo` (the "Brand Logo") — this one is fully alive and already
  working: uploadable from both the super-admin resort-edit page
  (`Admin\ResortsController`) and the resort's own Site Settings page
  (`Resorts\SitesettignsController`), with a ready-made, disk-agnostic
  accessor: `Common::GetResortLogo($resortId)`
  ([Common.php:1253](../app/Helpers/Common.php#L1253)).
  `ProbationController`, `TransferController`, `ExitClearanceController`,
  `EmployeeController`, `TaEmailTemplateController`, and mobile
  `API/PayrollController` **already call `GetResortLogo()`** — but only to
  stamp the resort's logo onto the **PDF attachment's** letterhead, never
  into the email body itself.

**Recommended fix:** pass `Common::GetResortLogo($resortId)` into
`emails.layouts.header` (e.g. via a `$data['resortLogo']` key every
Mailable/notification already building `$data` for `emails.commonEmail`
adds, with a fallback to the current Wisdom logo only for the two flows
below that should keep it) and swap the hardcoded `<img>` for that value.
Same treatment for the footer's resort-name substitution if wanted.
Whether to also finish out the dormant `email_logo` column as a
*separate*, email-specific logo (distinct from the app/PDF brand logo) or
just reuse `resorts.logo` for both is a product call — reusing `logo` is
the smaller change since it already has a working upload UI and existing
data for resorts that have set one.

---

## What's already correct — do not change these two flows

Per your instruction that Wisdom's identity should be used **only** for
(a) the super-admin-panel resort-admin account-creation email, and (b)
password resets:

- **Super-admin creates a resort-admin account**
  (`Admin/ResortsController.php:342` →
  `ResortRegistrationEmail` notification,
  `app/Notifications/ResortRegistrationEmail.php`) explicitly calls
  `->from(config('mail.from.address'), config('mail.from.name'))`. A
  super-admin session never authenticates against the
  `resort-admin`/`api` guards the SMTP-override middleware checks, so this
  naturally stays on Wisdom's `.env` default. **Currently correct.**
- **Password reset** (`app/Notifications/ResetPassword.php`, view
  `emails.passwordEmail`) fires before login (no authenticated resort-admin
  session yet), so the same middleware never overrides anything here
  either. **Currently correct.**

Both of these still go through the hardcoded Wisdom logo/footer
(`emails.layouts.content`) — that's correct and intentional for these two,
so whatever fix ships for Issue 3 needs to **keep** Wisdom's own logo for
these two flows specifically, not resort-ize them too.

---

## Open question for you to confirm before this ships

The same `ResortRegistrationEmail` notification (used above for the
genuine super-admin case) is **also** fired from two other places, via a
second trigger method `ResortAdmin::sendResortemployee()`
([ResortAdmin.php:169](../app/Models/ResortAdmin.php#L169)):

- Bulk employee import (`app/Imports/EmployeeImport.php:336`)
- A resort-admin manually adding one employee
  (`app/Http/Controllers/Resorts/People/Employee/EmployeeController.php:1639`)

Both of these run as an **authenticated resort-admin**, so
`ApplyResortSmtpConfig` has already overridden `mail.from.*` to that
resort's own config *before* this notification's
`->from(config('mail.from.address'), ...)` line reads it — meaning these
two credential emails **already send from the client's own address**, not
Wisdom's, which matches your stated rule ("all other cases, client's
email"). Flagging this so it isn't accidentally "fixed" the other way —
but they still carry Wisdom's hardcoded logo (Issue 3), so a resort-admin
adding an employee today gets an email correctly *from* the client but
visually branded as Wisdom. Please confirm this two-trigger split (Wisdom
identity only for the super-admin panel case, client identity for
resort-admin-initiated employee creation) is the behavior you want kept —
if so, only the logo needs fixing on these two; if not, let me know and
the `.md` scope needs to note giving these their own explicit `->from()`
override instead of relying on guard-scoping as an implicit rule.

---

## Summary of concrete backend changes

1. Log (don't silently swallow) a missing `resort_smtp_configs` row in
   `Common::applyResortSmtpConfig()`.
2. Audit every `ShouldQueue` mailable/job for the same
   `Common::applyResortSmtpConfig()` re-apply `TaEmailSent` already does,
   so this doesn't silently break the day `QUEUE_CONNECTION` changes from
   `sync`.
3. Add inline help text to the Email Configuration form
   (`resources/views/resorts/emailconfig/index.blade.php`) clarifying that
   Host/Username/Password must be a real, authenticated relay for the
   domain used in "From Address," and that "From Name" is a display name,
   not another email field.
4. Wire `Common::GetResortLogo($resortId)` into
   `resources/views/emails/layouts/header.blade.php` (and resort-name into
   the footer) for every resort-scoped email, while explicitly keeping
   Wisdom's own logo for the super-admin-registration and password-reset
   flows.
5. Confirm the open question above about the two `sendResortemployee()`
   triggers before touching their branding.
