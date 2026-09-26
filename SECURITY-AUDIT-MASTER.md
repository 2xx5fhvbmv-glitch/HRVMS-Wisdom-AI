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
| — | 2 | — | Grievance module (committee edit, API read, identity) | PENDING AUDIT |
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

## 3. STAGE 2 — Grievance module  ·  PENDING AUDIT
## 4. STAGE 3 — Super-admin support view XSS  ·  PENDING AUDIT
## 5. STAGE 4 — Passport token expiry  ·  PENDING AUDIT
## 6. STAGE 5 — CORS  ·  PENDING AUDIT
## 7. STAGE 6+ — Module-by-module sweep  ·  PENDING AUDIT

---

## 9. FOUND WHILE FIXING (Claude Code appends here — do not fix in the same change)

| Found during | File:line | What | Severity guess |
|---|---|---|---|
| | | | |
