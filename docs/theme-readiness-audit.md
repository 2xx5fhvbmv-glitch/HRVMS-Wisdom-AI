# Dark/Light Theme Readiness Audit

Report only — no code changed. Compiled from four parallel research passes
across the codebase (677 Blade views, the app's own CSS files, third-party
libraries, images/SVG/PDF/email). One correction made to the raw research
before this write-up: see the note at the top of §1.

## Note on a research error, corrected here

One of the four research passes was accidentally run against an isolated
git worktree instead of the live working directory, and on that basis
claimed `resources/views/resorts/layouts/_design_tokens.blade.php`
"doesn't exist." That's wrong — I read this exact file directly earlier
in this session and have been building against it all day (Incident
Dashboard/Detail/Investigation, the chat widget restyle, meeting detail).
Verified just now: it's a **committed** file (last touched in commit
`ceaafda9f`, already the tip of this branch before this session started),
and it's genuinely wired into the live CSS cascade. §1 below reflects the
correct, verified picture — every other finding from all four passes
checked out and is used as-is.

---

## 1. Token inventory (source of truth)

**There are two, non-communicating token systems loaded together on every
resort-admin/shopkeeper page** — not one, and not zero.

### 1a. The real central token file (global, semantic)

`resources/views/resorts/layouts/_design_tokens.blade.php` — 18 lines,
`:root` scope:

```css
color-scheme: light;  /* explicit comment: "no dark palette exists yet" */
--teal:#014653; --teal-2:#035b6c; --teal-3:#E6F0F1; --teal-soft:#F5F8F8; --lime:#E0FF02;
--paper:#F9F8F1; --ink:#14232A; --muted:#5D6F75; --faint:#93A4A9;
--line:#E2EBEC; --line-2:#EEF4F4; --card:#FFFFFF; --neutral-bg:#DEDEDE;
--positive:#1F9D6B; --positive-bg:#E9F7F0;
--warning:#D98A00; --warning-bg:#FBF0DC;
--critical:#FF2400; --critical-bg:#FFDED9;
--error:#E5573F; --error-bg:#FDEEEB;
--grad-celebrate:linear-gradient(135deg,var(--teal),var(--lime));
--grad-celebrate-hover:linear-gradient(135deg,#013641,#c7e102);
```

**Wired globally** via `resources/views/resorts/layouts/css.blade.php:1`
(→ every resort-admin page) and `resources/views/shopkeeper/layouts/css.blade.php:1`
(→ every shopkeeper page) — both `@include('resorts.layouts._design_tokens')`
as the very first line. Also individually included on the 3 shopkeeper auth
pages directly. **This is the token set every page built/redesigned this
session uses** (Incident Dashboard, Incident Detail, Investigation, the
chat widget, Meeting Detail).

Its own comment is a direct, first-party admission: dark mode was never
built, and `color-scheme: light` is a deliberate guard against
`prefers-color-scheme:dark` heuristically repainting white cards on OSes
that report dark mode (a real bug it prevents today, and something a real
dark theme will need to replace with an intentional dark palette rather
than just delete).

### 1b. The older, smaller token block (also global, non-semantic)

`public/resorts_assets/css/default.css:51-60`, also `:root`, also loaded
on every resort-admin/shopkeeper page (via the same `css.blade.php` files,
further down the include chain) and on `applicant_form_assets/css/default.css`
(byte-identical values, separate public-facing job-application portal):

```css
--green:#014653; --neon-yellow:#2EACB3; --white:#ffffff; --darkblack:#222222;
--black:#000000; --coolblue:#2EACB3; --success:#0CAF00; --danger:#C80000
```

Named by raw color (`--green`, `--white`) rather than by role
(`--teal`, `--card`) — this is the older system, predates the semantic one
above, and is what most of the app's ~677 Blade views and `default.css`
itself actually consume. `--green` happens to equal `--teal` and `--white`
happens to equal `--card`'s base, but nothing routes through the newer
file's names, so the two sets can't be unified by just deleting one.

### 1c. Page-local token blocks (deliberately not shared)

Several individual pages declare their own `:root` or class-scoped custom
properties rather than reusing either file above — each with an explicit
comment saying this was deliberate (either "no shared file existed yet" or
"avoid a new cross-page dependency"):

| File | Scope | Token count | Values vs. shared tokens |
|---|---|---|---|
| `resources/views/resorts/payroll/dashboard/_estimate_breakdown_styles.blade.php:11-27` | `:root` | 15 (`--peb-*`) | Same hexes as `_design_tokens.blade.php`, re-declared |
| `resources/views/resorts/partials/wisdom-chat.blade.php:192-197` | `:root` | 4 (`--wai-*`) | Own dark-teal gradient, not shared anywhere |
| `resources/views/resorts/budget/consolidated.blade.php:16-33` | `:root` | 16 (`--wb-*`) | Same hexes as `_design_tokens.blade.php`, re-declared |
| `resources/views/resorts/budget/view_budget_hierarchical.blade.php:74-96` | `:root` | 21 (`--wb-*`) | Same 16 as above + 5 more |
| `resources/views/resorts/people/compliance/list.blade.php:143-146` | `.compliance-page` | 15 | Same hexes as `_design_tokens.blade.php`, re-declared |
| `resources/views/resorts/timeandattendance/dutyroster/CreateDutyRoster.blade.php:994-1008` | `.drc-page` | 14 | Same hexes, re-declared |
| `resources/views/resorts/timeandattendance/dashboard/partials/wai-insights.blade.php:63-66` | `.card-wiINsight` | 15 | Same hexes, re-declared, comment explains why |
| `resources/views/admin/support/chat.blade.php:183-184` | `.chatStatus-text.*` | 2 | Online/offline status colors, unique to this file |

**No value conflicts anywhere** — every repeated token name (across all
three tiers above) resolves to the identical hex in every file that
declares it. The problem for theming isn't disagreement, it's
**duplication**: the same ~15-token palette is hand-copied into at least 7
different files instead of living in one place, so a dark variant would
need to be pasted into all 7 too unless these are consolidated first.

### 1d. Per-portal picture

| Portal | Wrapping layout | CSS include chain entry | Has a token `:root`? |
|---|---|---|---|
| resort-admin | `resources/views/resorts/layouts/app.blade.php` | `resorts.layouts.css` | Yes — both 1a and 1b (shared file) |
| shopkeeper | `resources/views/shopkeeper/layouts/app.blade.php` | `shopkeeper.layouts.css` | Yes — same physical files as resort-admin |
| admin (superadmin) | `resources/views/admin/layouts/app.blade.php` | `admin.layouts.css` → `assets/css/custom.css` | **No — zero CSS custom properties anywhere in this portal's stylesheet chain.** AdminLTE + stock Bootstrap 4/5.0.2 colors throughout. |

The admin portal can't piggyback on either token file above — a dark theme
there is a from-scratch job, not an extension.

---

## 2. Hardcoded-color report (the migration list)

### 2a. The app's own CSS files (not vendor libraries)

| File | Raw color lines | `var(--x)` usage lines | % tokenized |
|---|---|---|---|
| `public/resorts_assets/css/default.css` | **888** | 179 | ~17% |
| `public/resorts_assets/css/developer.min.css` | **90** | 10 | ~9% |
| `public/resorts_assets/css/flatpickr-theme.css` | 58 (mostly `var(x, #fallback)`, only ~6 truly raw) | 42 | ~72% real |
| `public/resorts_assets/css/developer1.min.css` | **33** (near-duplicate/fork of developer.min.css) | 5 | ~13% |
| `public/resorts_assets/css/toastr-theme.css` | 32 (mostly `var(x, #fallback)`, only ~5 truly raw) | 23 | ~72% real |
| `public/resorts_assets/css/skeleton.css` | **10** | 1 | ~9% |
| `public/resorts_assets/css/media.css` | **6** | 0 | 0% |
| `public/admin_assets/dist/css/developer.css` | **2** | 0 | 0% |
| `public/css/app.css` | 0 (dead/empty file, unused Mix/Vite leftover) | — | — |

**`default.css` alone is 79% of all raw-color lines in this app's own CSS
(888 of 1119).** It's the single highest-leverage file for a retrofit.
Worth noting from the raw-frequency count: `#DDDDDD` appears 82 times,
`#014653` (== `--green`/`--teal`) appears **raw 70 times vs. `var()` only
35 times** — i.e. even where a token already exists, the file re-types the
literal more often than it uses the variable. There's also an untokenized
sprawl of near-duplicate grays (`#DDDDDD`/`#E7E7E7`/`#D9D9D9`/`#C9C9C9`/
`#D6E1E3`/`#DEDEDE`/`#D5D5D5`) and reds (`#C80000`==`--danger`, plus
`#A90000`, `#BE0000`, `#dc3545`) with no shared name.

`toastr-theme.css` and `flatpickr-theme.css` are the two best-prepared
files in the app — both already use `var(--token, #hexFallback)`
throughout, so a dark pass mostly needs new `:root` values, not a rewrite.

### 2b. Blade templates (`<style>` blocks + inline `style=""`)

Full-corpus scan of all 677 files:

- **208 distinct files** (~31%) contain at least one hardcoded color, in a
  `<style>` block or an inline `style=""` attribute.
- **2,892 total instances** combined.

**Top 10 worst offenders:**

| # | Count | File |
|---|---|---|
| 1 | 146 | `resources/views/resorts/timeandattendance/dutyroster/ViewDutyRoster.blade.php` |
| 2 | 131 | `resources/views/resorts/timeandattendance/dutyroster/CreateDutyRoster.blade.php` |
| 3 | 114 | `resources/views/resorts/partials/wisdom-chat.blade.php` |
| 4 | 102 | `resources/views/resorts/benifitgrid/pdf.blade.php` (PDF export — see §3) |
| 5 | 88 | `resources/views/resorts/timeandattendance/attandanceregister/index.blade.php` |
| 6 | 75 | `resources/views/resorts/payroll/dashboard/dashboard.blade.php` |
| 7 | 73 | `resources/views/resorts/_emotional_buttons_v2_styles.blade.php` (shared button system, used in **179 files**) |
| 8 | 60 | `resources/views/resorts/talentacquisition/_ta_buttons_v2_styles.blade.php` |
| 9 | 58 | `resources/views/resorts/learning/_learning_buttons_v2_styles.blade.php` |
| 10 | 56 | `resources/views/resorts/workforce_planning/dashboard.blade.php` |

The `*_buttons_v2_styles.blade.php` / `*_widgets_v2_styles.blade.php`
family (one near-duplicate per module: Performance, Payroll, Leave, TA,
Workforce Planning, Time & Attendance, Learning, plus the shared
`_emotional_buttons_v2_styles.blade.php`) accounts for **~380+ instances
on its own** — largely copy-pasted from each other with hardcoded
`#014653`/`#fff`/`rgba(20,35,42,...)` instead of referencing a shared
token, even inconsistently within the same file.

**Sanity check on this session's own work** — the incident/ module (which
I know was deliberately built with `var(--teal)` etc. this session) scores
low as expected: `_dashboard_styles.blade.php` 11, `investigation.blade.php`
17 (mostly one `rgba(1,70,83,.0X)` teal-tint repeated at different
opacities — no `--teal-rgb` token exists, so opacity variants are spelled
out numerically), `view.blade.php` 9, `meeting/detail.blade.php` 8. One
real inconsistency found in the same family: the Talent Acquisition
dashboard's shared widget partial, `_ta_widgets_v2_styles.blade.php`, has
**31 raw hex instances across 10 distinct colors — including the
approval-chain status pill colors — with zero `var()` usage**, unlike the
dashboard pages themselves. Worth a look if that partial wasn't intended
to be this hardcoded.

**Module concentration** (heaviest to cleanest):
timeandattendance (480) > Performance (339) > payroll (208) > learning
(199) > shared partials (193, mostly the chat widget) > talentacquisition
(167) > people (139, spread thin) > leaves (118) > workforce_planning
(106) > benifitgrid (102, all PDF) > layouts (96) > Survey (64) >
**incident (53 — low, as expected)** > budget (46) > Accommodation (32) >
GrievanceAndDisciplinery (28) > **auth pages (4 — cleanest in the app,
essentially untouched Bootstrap defaults)** > SOS (4).

### 2c. Colors set from JS/jQuery (exhaustive, not sampled)

- **`.css('color'|'background', literal)` calls — 16 instances**, all one
  copy-pasted star-rating snippet (`#EFB408` filled / `#ccc` empty) across
  8 files in Learning + Performance (e.g.
  `resources/views/resorts/learning/feedbackform/edit.blade.php:118-119`).
- **`.css({...})` object-literal calls — 2 instances**, both in
  `resources/views/resorts/renderfiles/budget_cost_modal.blade.php:864-895`.
- **Chart.js `backgroundColor`/`borderColor` arrays — 26 array
  declarations across 13 files, 66 hex literals total.** No shared JS
  palette constant exists anywhere (confirmed zero hits for
  `chartColors`/`CHART_COLORS`/`themeColors =`). Every chart hardcodes its
  own colors inline — see §4, this is the single biggest lift in the whole
  audit because CSS cannot reach a `<canvas>` bitmap at all.
- **Inline SVG hardcoded `fill=`/`stroke=` (not `currentColor`) — 25
  instances across 6 files** (org-chart nodes, a meeting-calendar icon, an
  applicant-form success/error icon pair, a notification empty-state
  illustration, the Laravel welcome-page demo SVG).

---

## 3. Baked-in colors CSS can't invert

### 3a. Raster/vector image assets carrying UI color

| Asset | Used for | Where |
|---|---|---|
| `public/resorts_assets/images/wisdom-ai.png` | Main brand logo | Every auth screen + header, both resort-admin and shopkeeper (12 call sites) |
| `public/resorts_assets/images/wisdom-ai-small.svg` | Decorative watermark badge | Dozens of dashboard cards across nearly every module |
| `public/resorts_assets/images/wisdom-ai-icon.jpeg` | Chat-bot avatar | `resources/views/resorts/partials/wisdom-chat.blade.php` |
| `public/resorts_assets/images/login.jpg` | Full-bleed auth side-panel photo | All login/reset/request-password pages, both portals |
| `public/assets/images/header_logo.png` | Email header logo | `resources/views/emails/layouts/header.blade.php` |
| `public/admin_assets/logo/{logo,header_logo,footer_logo}.png` | Admin panel logo | Fallback only — real logo is DB/user-uploaded via `config('settings.site_logo_folder')`, out of scope |
| ~90 pre-colored status/action icon files (`check-circle-green.svg`, `trash-red.svg`, `download-green.svg`, etc.) | Toolbar/status icons, `<img src=>` not inline `<svg>` | Recur across essentially every module |
| Country flag `.webp` files, payment-card SVGs, map pin PNGs | Flags, card brands, map markers | Scattered, brand/geography-mandated colors, can't invert regardless |

None of these can be recolored by a CSS theme — they're baked raster/flat
files. The ~90 status-icon SVGs are the most relevant ongoing cost: unlike
an inline `<svg>`, an `<img src="...svg">` can't pick up `currentColor`
from its container, so each would need either a dark-mode-specific asset
variant or a CSS `filter: invert()`-style hack.

### 3b. Inline SVGs — mostly already theme-safe

39 files, 89 `<svg>` tags. **~17% of `fill` attributes and ~14% of
`stroke` attributes hardcode a literal color; the rest already use
`currentColor` or `none`.** The hardcoded ones cluster narrowly: org-chart
node rendering (`people/orgchart/index.blade.php` and its `index1.blade.php`
duplicate), one meeting-calendar icon
(`resources/views/resorts/incident/meeting/calendar.blade.php:124-129`),
a notification empty-state illustration
(`resources/views/partials/_notifications_empty.blade.php:30-31`), and two
JS-injected file-upload status icons. One nice existing example worth
copying elsewhere: `resources/views/resorts/learning/attendance/index.blade.php:43`
already writes `stroke="var(--line-2, #EEF4F4)"` directly on an SVG
attribute — proof the pattern works in this codebase today.

### 3c. Canvas / charting

Confirmed library: **Chart.js v4.4.4**, self-hosted at
`public/resorts_assets/js/chart.js`, loaded via
`resources/views/resorts/layouts/js.blade.php:9` (and the shopkeeper/`js1`
equivalents). **~95 `<canvas>` elements across ~45 Blade files** — nearly
every module's dashboard. As noted in §2c, every chart hardcodes its own
`backgroundColor`/`borderColor` inline with no shared palette — this is
the part of the audit that CSS genuinely cannot fix; it needs JS changes
at every call site (or a shared color helper introduced first, then every
`new Chart(...)` migrated to it).

### 3d. PDF / print / email — already fully isolated (good news)

- PDF engine: `barryvdh/laravel-dompdf`. **14 dedicated PDF Blade views**
  (payslip, benefit grid, leave, survey export, promotion/probation
  letters, compliance, etc.) — every one has its **own embedded
  `<style>` block** with its own `@font-face` (Poppins, loaded via
  `public_path()`), completely separate from `default.css` or the design
  tokens. None link the app's shared stylesheet.
- 5 files use `@media print` rules directly in application Blade code
  (probation detail, final settlement review, 2 workforce-planning
  dashboards, a Performance Review export).
- 9 transactional email templates under `resources/views/emails/` — all
  inline `style=` attributes, all hardcoded hex (`#014653`, `#DA2128`,
  `#004552`, plain `color:blue`), none reference app tokens (expected —
  email clients don't reliably support CSS variables).

**This means a dark-mode CSS change is naturally scoped away from
PDFs/print/email already — no risk of accidental leakage either
direction, and no work needed here since these should stay light-only by
design.**

---

## 4. Third-party / component colors

| Library | Confirmed used? | Theming today | Verdict |
|---|---|---|---|
| **Flatpickr** | Yes — `flatpickr-theme.css` (278 lines) + `flatpickr.min.css`, 88 init call sites, zero JS `theme:` overrides | 100% via `flatpickr-theme.css`, which **already uses `var(--token, #fallback)` throughout** (its own header comment documents the intent) | **Best-prepared component in the app** — a dark override just needs new fallback-free `:root[data-theme=dark]` values, no rewrite |
| **Bootstrap 5 (resort-admin/shopkeeper)** | v5.0.2, stock `--bs-*` vars untouched | Branding comes from custom classes (`.btn-theme*`, `.eb-btn-*`) layered on top, not Bootstrap variable overrides — mostly hardcoded hex, inconsistent even within the shared `_emotional_buttons_v2_styles.blade.php` (some rules use `var(--positive)`, most don't) | Partial — override extends existing custom CSS, but needs real cleanup first |
| **Bootstrap (admin/AdminLTE portal)** | Yes, separate v4-era AdminLTE | Stock, unrecolored. 31 raw `btn-primary` (Bootstrap default blue) uses across 15 admin files | **Unthemed — from-scratch job** |
| **Bootstrap (root Laravel auth scaffolding)** | Yes (`resources/views/auth/*`) | Stock `laravel/ui` scaffolding, never touched | Unthemed, low-traffic though |
| **SweetAlert2 (resort-admin)** | Yes, local copy + shared `_confirm.blade.php` wrapper (`wisdomConfirm`/`wisdomAlert`, 11 files) | Wrapper's own `<style>` hardcodes hex with `!important`, despite equivalent tokens existing in `_design_tokens.blade.php` | Partial — extend existing wrapper CSS |
| **SweetAlert2 (admin portal)** | Yes — but a **different, older v6.6.9 loaded from a CDN**, separate from the resort app's local copy | **15 files still call the old `swal({confirmButtonColor:"#DD6B55"})` API — `#DD6B55` is the literal library-default color, never branded at all**, 30 occurrences | **Red flag** — never themed once; needs per-file JS edits, not a CSS override |
| **Toastr** | Yes, app-wide across all 3 portals via one shared `toastr-theme.css` | Frosted-glass `rgba(255,255,255,.74)` + `backdrop-filter`, hardcoded not tokenized | Already unified app-wide, but needs new literal values for dark, not a var swap |
| **Chart.js v4.4.4** | Yes, confirmed, self-hosted | Hardcoded hex per-chart, ~95 canvas instances, no shared palette | **Biggest lift in the audit** — CSS cannot reach it at all |
| **Select2** | Yes — resort-admin uses one theme, **admin portal uses a separate stock `select2-bootstrap4-theme`** | Resort-admin: mixed hardcoded/token in `default.css`. Admin: fully stock | Partial (resort-admin) / unthemed (admin) |
| **DataTables** | Yes, separate copies per portal (resort-admin custom-styled, admin stock Bootstrap4) | Same split as Select2 | Partial (resort-admin) / unthemed (admin) |
| **Date-range picker** | Yes, 12 init sites, 3 separate vendor CSS copies | Resort-admin override mostly hardcoded, including a hand-computed `rgb(0 69 82/75%)` instead of `var(--teal)` + opacity | Partial, needs normalizing before a dark pass |
| **bootstrap-datepicker** | **Yes — still actively used in ~20 files, not dead**, in parallel with Flatpickr | Only the `.active`/selected-day state is overridden (`var(--teal) !important`); other states likely still stock blue | Partial, needs a full pass — or consider finishing the Flatpickr migration instead of theming two pickers |

---

## 5. Theme mechanism & app shell

**Nothing exists.** Confirmed by direct search, not inference:

- No dark-mode/theme-toggle UI element anywhere (button, switch, menu item) in any of the 3 portals.
- `prefers-color-scheme` appears exactly once in the whole repo — in `resources/views/welcome.blade.php`, the **stock, unmodified Laravel scaffold splash page** at `/`, which isn't part of any real portal and isn't wired to anything.
- `data-theme` attribute exists only on a commented-out, unrelated feature (per-status attendance-color coding via a `color_themes` DB table — nothing to do with UI dark/light mode).
- **`data-bs-theme="auto"`** is already sitting on `<html>` in resort-admin, shopkeeper, and the applicant-form portal (`resources/views/resorts/layouts/app.blade.php:2` and equivalents) — but it's **inert**: Bootstrap 5.0.2 predates `[data-bs-theme=dark]` support entirely (0 matches for it in the bundled `bootstrap.min.css`). Nothing ever sets it to `dark`. If Bootstrap is ever upgraded to 5.3+, this attribute starts working for free; today it does nothing. The admin portal's `<html>` has no such attribute at all.
- AdminLTE's own vendor dark-mode JS (`body.dark-mode` toggle in `admin_assets/dist/js/demo.js`) exists in the vendor bundle but is **dead code here** — that file is never `@include`d, and the DOM structure it needs doesn't exist in this app's admin views.
- No localStorage/cookie/session key with a theme-ish name anywhere (JS or PHP).
- No DB column for a theme/dark-mode preference on any user/settings table (the only `color_themes` migration is the unrelated attendance-status feature above).
- No mobile-API hook for theme/dark-mode either.

**The wrapping layout per portal** (where a `data-theme` attribute could
be set once and cascade everywhere):

| Portal | File | `<html>` today | `<body>` today |
|---|---|---|---|
| resort-admin | `resources/views/resorts/layouts/app.blade.php` | `<html lang=en data-bs-theme=auto>` | `<body id="body-content " class="Dashboard-page">` |
| shopkeeper | `resources/views/shopkeeper/layouts/app.blade.php` | `<html lang=en data-bs-theme=auto>` | `<body id="body-content">` (note: this file also has a pre-existing, unrelated markup bug — a second `<head>` tag opened at line 13 instead of closing the first one) |
| admin | `resources/views/admin/layouts/app.blade.php` | `<html lang="en">` (no theme hook at all) | `<body class="hold-transition sidebar-mini layout-fixed">` |

**Things that would fight a global theme toggle:**

- `body { background: hsla(190, 98%, 16%, 0.05); ... }` — hardcoded, not a
  token — `public/resorts_assets/css/default.css:62-70` (resort-admin +
  shopkeeper, since they share this file).
- `body{...background-color:#fff}` hardcoded directly in the vendored
  `adminlte.min.css` bundle the admin portal loads — no variable at all.
- 110 total `!important` rules in `default.css`; most already reference a
  token, but several hardcode raw colors under `!important` (e.g.
  `border-top-color: #DDDDDD !important;` at `default.css:12130`,
  `color: rgb(223, 255, 0) !important;` at `:14188`) — these will resist a
  CSS-variable-only override and need direct edits.
- No iframes found in any of the 3 layout chains (a common theme-fighting
  culprit) — clean on that front.

---

## Open questions / risks before implementing

1. **Two token systems, ~7 duplicated palette copies.** Decide whether to
   consolidate `_design_tokens.blade.php` and `default.css`'s `:root`
   block (and the 6 page-local `--peb-*`/`--wai-*`/`--wb-*`/scoped
   copies) into one file before adding dark values — otherwise every dark
   color needs to be pasted into 7+ places to stay consistent, same as
   light is today.
2. **Chart.js is the real scope-driver, not CSS.** ~95 canvas instances
   across 45 files, no shared palette, can't be touched by a CSS theme at
   all. Worth estimating/scoping this as its own workstream, separate from
   (and probably larger than) the CSS/token migration.
3. **Is the admin (superadmin) portal in scope?** It's a functionally
   separate app (AdminLTE, Bootstrap 4/5.0.2 stock, a different SweetAlert2
   version via CDN, separate vendor copies of Select2/DataTables) with
   zero existing tokens and 15 files that never got branded once (still
   showing SweetAlert's literal default `#DD6B55`). If dark mode needs to
   cover it, that's a from-scratch build, not an extension of the
   resort-admin work.
4. **~90 pre-colored status-icon image files** (`check-circle-green.svg`,
   `trash-red.svg`, etc., loaded as `<img src>` not inline `<svg>`) can't
   pick up `currentColor`. Decide: dark-specific asset variants, a CSS
   filter hack, or leave them as-is (they're mostly semantic status colors
   that may read fine unchanged on a dark background — worth a visual
   check rather than assuming they need work).
5. **`toastr-theme.css`'s frosted-glass background and the SweetAlert
   `_confirm.blade.php` wrapper** are both hardcoded `rgba(255,255,255,…)`
   — "already themed" in the sense of being centralized, but will need
   genuinely new dark values written, not a variable swap.
6. **bootstrap-datepicker vs. Flatpickr** — both are live in parallel
   (~20 files still use the older picker). Worth deciding whether to
   finish migrating those call sites to Flatpickr (which is already
   var-driven and dark-ready) rather than separately theming two date
   pickers.
7. **PDF/print/email are naturally out of scope already** — confirmed
   fully isolated from the app's CSS, no action needed, low risk either
   direction.

## Rough token-cleanliness estimate

Combining the CSS-file ratios (§2a: `default.css` ~17%, `developer.min.css`
~9%, `skeleton.css` ~9%, `toastr-theme.css`/`flatpickr-theme.css` ~72%
*if* their fallback-value pattern counts as "tokenized") with the
Blade-template picture (§2b: 2,892 raw instances vs. no comparable
app-wide `var()` count taken, but every per-file ratio sampled came out
similarly low except this session's own newly-built pages) —

**roughly 10-15% of this app's color declarations already reference a
token; the remaining 85-90% are raw literals.** That figure is dragged up
by two well-prepared vendor-override files and a handful of pages built
this session, and dragged down by `default.css` (79% of all raw CSS-file
colors on its own), the `*_buttons_v2_styles` family (~380+ instances),
and the entire admin/AdminLTE portal (0% tokenized). This is an estimate
from sampled ratios across the four research passes, not a single
exhaustive corpus-wide calculation — treat it as directionally right, not
exact.
