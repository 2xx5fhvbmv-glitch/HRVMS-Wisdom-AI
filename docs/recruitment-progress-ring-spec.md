# Recruitment Progress Ring — Stage Model Spec

For a backend developer to implement. Research-only deliverable — no backend code was
written for this; it documents the **actual current** status model (verified against the
real migrations, controllers, and config in this repo) and specifies how the applicant
progress-ring percentage should be calculated so it accurately reflects the recruitment
journey from application through a signed contract.

## Why this exists

The progress ring next to an applicant's photo (Talent Acquisition → Applicants) is
currently calculated by **duplicated, drifted, ad-hoc logic in two Blade views**
(`resources/views/resorts/renderfiles/TaUserApplicantsSideBar.blade.php` and
`resources/views/resorts/talentacquisition/Applicants/gridviwe.blade.php`). They disagree
with each other for the same applicant (see "Current bugs this fixes" below), and neither
extends past `Selected` — an applicant who has a signed contract shows the same ring as one
who was merely marked `Selected`, and the grid-card version actually *regresses* for
hired/rejected applicants. This spec defines one canonical stage model so both views (and
any future one) compute the same number from one source of truth.

## The applicant status enum

Column `applicant_wise_statuses.status`, enum (`database/migrations/2026_02_28_100002_add_offer_contract_statuses_to_applicant_wise_statuses_table.php`):

```
Sortlisted By Wisdom AI, Rejected By Wisdom AI, Sortlisted, Round, Rejected,
Selected, Complete, Pending, Offer Letter Sent, Offer Letter Accepted,
Offer Letter Rejected, Contract Sent, Contract Accepted, Contract Rejected
```

**Two values are dead code** — never written anywhere in the app, only ever read/displayed:
- `Rejected By Wisdom AI` — no write site exists. Treat as a synonym of `Rejected` at the
  application-triage stage if you ever see it in old data.
- `Pending` — the column's DEFAULT only. Never actually written to this table (a different
  column, `applicant_inter_view_details.Status`, uses the string `'Pending'` for something
  unrelated — don't confuse the two).

**Multiple rows exist per applicant** (`applicant_wise_statuses.Applicant_id`) — it's a
history table. The applicant's *current* status is the row with `MAX(id)` for that
`Applicant_id`. Some transitions **append** a new row (new interview round, HR-shortlist
promotion); others **update the existing row in place** (Round→Complete same round,
Selected, Offer/Contract Sent/Accepted/Rejected). Either way, always compute progress from
the MAX(id) row only — don't sum across history.

## What each status actually means (verified against the code that writes it)

| Status | Who/what sets it | Where |
|---|---|---|
| `Sortlisted By Wisdom AI` | **Automatic**, unconditional, on every public application submission — despite the name, there is no AI scoring gate here today. Effectively means "new application, awaiting HR triage." | `ApplicantController.php:677-681` |
| `Sortlisted` | HR clicks "HR Shortlisted" (promotes out of the AI/triage stage) **or** HR books the next interview round after the previous one completed | `ApplicantsController.php:1379-1383`, `:1371-1385` |
| `Round` | HR/interviewer clicks "\<Round Name> Round" — interview scheduled/in progress for that round | `ApplicantsController.php:1709` |
| `Complete` | Interviewer clicks "\<Round Name> Complete" — that round finished | same, `Rank='Complete'` |
| `Selected` | HR clicks "Select Candidate", only enabled once the *final* round is `Complete` | `ApplicantsController.php:334-339`, `:1676-1677` |
| `Rejected` | HR/interviewer rejects at any stage from triage through the final round decision | `ApplicantsController.php:1712-1714` and callers |
| `Offer Letter Sent` | HR sends the offer letter (only available once `Selected`) | `ApplicantsController.php:2993-2996` |
| `Offer Letter Accepted` | **Candidate** accepts via a public, unauthenticated token link (not the mobile app — there is no applicant-facing API for this) | `OfferLetterResponseController.php:76-77` |
| `Offer Letter Rejected` | Candidate declines the offer via the same public link | `OfferLetterResponseController.php:113-114` |
| `Contract Sent` | HR sends the contract (only available once `Offer Letter Accepted`) | `ApplicantsController.php:3119-3122` |
| `Contract Accepted` | Candidate accepts via a public token link; this also creates their `Employee` record | `ContractResponseController.php:83-84` |
| `Contract Rejected` | Candidate declines the contract via the same link | `ContractResponseController.php:139-140` |

`Contract Accepted` is the terminal, successful end of this pipeline — **the ring should
close (100%) here and nowhere else.** There is no status after it; onboarding progress from
there on lives on `employees.status`, a separate concern.

## Interview rounds — how many, and in what order

Not per-resort, not in the database — a fixed global config
(`app/Helpers/Common.php:2588-2604`, `config/settings.php:681-693`):

```php
'InterViewRound' => ['3'=>'HR','2'=>'HOD','8'=>'GM'],   // fallback: 3 rounds
'PositionInterviewRounds' => [
  6 => ['3'=>'HR','2'=>'HOD'],   // Line Worker — 2 rounds
  5 => ['3'=>'HR','2'=>'HOD'],   // Supervisor  — 2 rounds
  4 => ['3'=>'HR','2'=>'HOD','8'=>'GM'],   // Manager and everything else — 3 rounds
  // ... (2, 1, 8, 7, 3 all 3-round; ranks with no entry — including 9/10/11/12 — use the fallback)
],
```

So there are only ever **2 rounds** (HR → HOD) or **3 rounds** (HR → HOD → GM), keyed by the
*vacancy's* rank (`applicant_form_data.Parent_v_id → vacancies.rank`), resolved via
`Common::getInterviewRoundsForPosition($vacancyRank)`. Array order is always HR first, HOD
second, GM third when present — this matches the order you described (HR interview, then
HOD interview, then further rounds for senior positions).

`As_ApprovedBy` on the status row holds the **rank code currently under review**: `3` = HR,
`2` = HOD, `8` = GM, `0` = pre-HR / triage stage.

## The stage model to implement

Let `rounds` = the ordered round list for this applicant's vacancy rank (from
`Common::getInterviewRoundsForPosition`), `roundCount` = count of that list, and `i` = the
0-based index of the round currently pointed to by `As_ApprovedBy` in that list (so `i=0` is
always HR, `i=1` is always HOD, `i=2` is GM when present).

**Total steps** = `2 + (roundCount * 2) + 1 + 4`
— 2 (application submitted + HR-shortlisted) + 2 per round (started + completed) + 1
(Selected) + 4 (offer sent, offer accepted, contract sent, contract accepted).

| Round count | Total steps |
|---|---|
| 2 (Line Worker, Supervisor) | **11** |
| 3 (everyone else) | **13** |

**Step number by status:**

| Status | `As_ApprovedBy` condition | Step |
|---|---|---|
| `Sortlisted By Wisdom AI` | `0` | 1 |
| `Sortlisted` | rank at round index `i` | `2 + 2i` (this is what makes it round-relative — see below) |
| `Round` | rank at round index `i` | `3 + 2i` |
| `Complete` | rank at round index `i` | `4 + 2i` |
| `Selected` | final round's rank | `2 + 2·roundCount + 1` |
| `Offer Letter Sent` | *(ignore `As_ApprovedBy` from here on — see note)* | `Selected step + 1` |
| `Offer Letter Accepted` | | `Selected step + 2` |
| `Contract Sent` | | `Selected step + 3` |
| `Contract Accepted` | | `Selected step + 4` = **total steps → 100%, ring closes** |

Display percent = `round(step / totalSteps * 100, 2)` — same rounding already used in both
existing implementations, no change needed there.

**Why `Sortlisted` uses `2 + 2i` for every round, not a fixed step 2:** today both existing
implementations hard-code any `Sortlisted` status to step 2, but the code path that books
the *next* round after one completes (`ApplicantsController.php:1371-1385`) writes
`Sortlisted` with `As_ApprovedBy` set to the **next** round's rank — e.g. `Sortlisted`
at HOD (`As_ApprovedBy=2`) genuinely means "passed the HR round, about to start the HOD
round," which is real progress past the HR-shortlist point. The formula above naturally
produces the right step for both cases: `i=0` (HR-shortlisted right after triage) → step 2,
and `i=1` (HOD round about to start, after HR round Complete) → step 4, matching the
`Complete` step of the prior round exactly, since that's the same point in the journey.

**Important implementation note on `As_ApprovedBy` for offer/contract statuses:** the
offer/contract transitions **overwrite the same row** rather than appending a new one, so
once status is one of the `Offer Letter *` / `Contract *` family, `As_ApprovedBy` still
holds the *final interview round's* rank code, not anything meaningful about the offer/contract
stage. The step calculation must switch to keying off `status` alone for these four values
and must not consult `As_ApprovedBy` at all once status has left the interview-round family.

## Rejection / terminal-failure handling

Any of `Rejected`, `Rejected By Wisdom AI`, `Offer Letter Rejected`, `Contract Rejected` is
terminal — the pipeline stops, it does not continue toward 100%. Recommended treatment
(matches the `danger`/red ring class already present in
`TaUserApplicantsSideBar.blade.php:43`, just needs to also be applied in `gridviwe.blade.php`,
which currently hardcodes `skyblue` unconditionally):

| Status | Step to freeze the ring at | Ring color |
|---|---|---|
| `Rejected By Wisdom AI` | 1 | red / danger |
| `Rejected` | `2 + 2i` where `i` = round index of `As_ApprovedBy` (0 if `As_ApprovedBy` is `0` or not found in the round list) | red / danger |
| `Offer Letter Rejected` | `Selected step + 1` (reached "offer sent", then declined) | red / danger |
| `Contract Rejected` | `Selected step + 3` (reached "contract sent", then declined) | red / danger |

`Contract Accepted` (100%, ring fully closed) should render in a distinct **success**
color, not the same in-progress blue as every step before it — gives HR an at-a-glance
"this one's actually hired" signal, matching the app's existing `badge-themeSuccess` /
`badge-themeDanger` color convention used right next to this same ring for the status badge.

**Known ambiguity to flag to the developer, not silently paper over:** `Rejected` at
`As_ApprovedBy=3` (HR's rank code) is written both when HR rejects during initial triage
*and* when an interviewer rejects during the actual HR interview round — the data doesn't
distinguish these today (both cases use the same rank code). The formula above treats both
as "rejected at the HR stage" (step 2), which is a reasonable, defensible simplification —
just don't build anything that assumes finer granularity is available there without adding
new tracking first.

## Current bugs this fixes (verified by reading both implementations in full)

`gridviwe.blade.php`'s calculation (`resources/views/resorts/talentacquisition/Applicants/gridviwe.blade.php:20-48`)
has two gaps that `TaUserApplicantsSideBar.blade.php`'s does not:
1. No `Rejected` branch at all — a rejected applicant falls through to the `else` default
   (step 2) instead of showing a meaningful step or a red ring.
2. Only `Selected` maps to 100% — every offer/contract status also falls through to the
   `else` default. **A fully-hired candidate with an accepted contract shows the grid card
   at 22-29% complete**, while the sidebar for the same applicant shows 100%. This is the
   single most visible instance of the "diverged duplicate logic" problem.

Both implementations, even where they agree, hard-code every `Sortlisted` status to step 2
regardless of which round it actually belongs to (see the round-relative explanation above),
and both flatten `Selected` through `Contract Accepted` into a single "100%" bucket today,
i.e. neither currently shows the granular offer/contract progress you described wanting
("upon completion of acceptance of everything, maybe the circle closes" — implying it
shouldn't close *before* that).

## Recommended implementation shape

Centralize this in one place instead of leaving it duplicated in two `@php` blocks — that's
what let the two views drift apart in the first place. Suggested (a name/location the
developer can adjust to house style):

```php
// app/Helpers/Common.php, or a small dedicated class
public static function applicantProgress(string $status, $approvedBy, $vacancyRank): array
{
    // returns: ['step' => int, 'total' => int, 'percent' => float,
    //           'state' => 'in_progress'|'success'|'rejected']
}
```

Both `TaUserApplicantsSideBar.blade.php` and `gridviwe.blade.php` should call this one
function and drop their inline `@php` step-counting blocks entirely. Any future view that
needs this (e.g. `talentpool.blade.php`, which currently has no calculation of its own at
all) gets it for free and stays in sync automatically.

## Also worth a look while implementing this (related, not strictly this spec)

The ring's fill animation is driven by client-side JS (`stroke-dashoffset`, reading a
`data-progress` attribute) that has to actually run to reflect any of this. It's currently
either not re-invoked after an AJAX reload of the sidebar, or not defined at all on 3 pages
(`Applicants/rejected.blade.php`, `vacancies/shortlisted.blade.php`,
`vacancies/SortlistedapplicantLinkShare.blade.php`) — worth checking those don't silently
show an empty ring regardless of how correct the percentage math is. That's a JS wiring
issue, separate from the stage-percentage logic this doc specifies.

## Not addressed by this spec (flagging, not deciding)

- Whether `Sortlisted By Wisdom AI` should get an actual AI-scoring gate to match its name,
  or just be relabeled to something like "New Application" — it's currently automatic for
  every submission with no real AI decision behind it. Product decision, not a progress-ring
  concern.
- Onboarding progress after `Contract Accepted` (this pipeline ends there; `employees.status`
  is a separate table/concern not covered here).
