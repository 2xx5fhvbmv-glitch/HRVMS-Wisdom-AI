# Talent Acquisition — "Active" / "Inactive" status on Add Vacancy

For the backend developer. Found while reviewing
`/resort/talent-acquisition/add-vacancies`
(`app/Http/Controllers/Resorts/TalentAcquisition/VacancyController.php`).
**No code has been changed** — this is a findings + requirements doc only.

---

## What exists today

The Add Vacancy form (`resources/views/resorts/talentacquisition/vacancies/create.blade.php`,
~line 452) has a "Status" field: a radio choice **Active** / **Inactive**
(Active checked by default). It posts as `status` and gets written straight
onto `vacancies.status`:

```php
// VacancyController::store(), line 341 (identical at update(), line 768)
$vacancy->status = $validatedData['status'];
```

`vacancies.status` is also the field a separate, already-established third
value — **`Draft`** — uses. Draft is set elsewhere in the flow (not from
this radio group) and is treated specially:

```php
// store(), line 174 / update(), line 627
$isDraft = $request->input('status') === 'Draft';
...
// Skip notifications, compliance checks, and approval flow for Draft
if (!$isDraft) {
    // duplicate check, minimum-wage + reserved-position compliance checks,
    // TAnotificationParent/TAnotificationChild creation (the approval
    // chain), and the "Hiring Request" push notification to HR — all of it
    // runs here
}
```

`edit($id)` and `update($request, $id)` (lines 522 and 625) are **both
hard-scoped to Draft only**:

```php
$vacancy = Vacancies::where('id', $id)
    ->where('Resort_id', $resort_id)
    ->where('status', 'Draft')
    ->firstOrFail();
```

And every dashboard/queue query that decides what counts as a "live"
hiring request filters on `status = 'Active'` specifically —
`Common::GetTheFreshVacancies()`, the rank 1/2 vacancies list in
`VacancyController` (line 968), etc. A HOD dashboard "Drafts" card
(`TalentAcquisitionDashboardController.php` line 861) lists `status='Draft'`
rows with an Edit link back into the `edit()`/`update()` pair above.

## The actual gap

**Inactive isn't wired to skip anything.** The `if (!$isDraft)` gate checks
only for the literal string `'Draft'`, so choosing "Inactive" on the form
still runs the entire block: compliance checks fire, the approval chain
(`TAnotificationParent` + one `TAnotificationChild` per approval rank) gets
created, and the "HOD Created new hiring request" push notification goes
out to HR — identically to choosing "Active". The only actual effect of
picking "Inactive" today is that the vacancy is excluded from the
`status='Active'`-filtered dashboard queries afterward, while an approval
chain silently sits there for a request nobody chose to submit yet.

There is also currently **no list anywhere in the app for Inactive
vacancies** (unlike Drafts, which have their own dashboard card), and since
`edit()`/`update()` only accept `status='Draft'`, an Inactive vacancy can't
be reopened and resubmitted through the existing mechanism at all — there's
no path back to it.

## What it should do instead

1. **Choosing "Inactive" at creation time saves all the entered data but
   sends nothing** — same treatment Draft already gets: no compliance
   checks, no approval-chain rows, no HR notification. The `$isDraft` skip
   condition should cover `Inactive` too (e.g.
   `in_array($request->input('status'), ['Draft', 'Inactive'], true)`, or
   whatever shape fits the rest of the method best).
2. **The user can come back later, open that same vacancy, and switch it to
   Active to actually send it.** This is exactly the Draft → Active
   resubmit flow `edit()`/`update()` already implement — it just needs to
   also accept `status='Inactive'` records, not only `'Draft'`. Once
   resubmitted with `status=Active`, the normal pipeline (compliance
   checks, approval chain, HR notification) should fire, same as it does
   for a Draft being submitted today.
3. **A way to find an Inactive vacancy again** — there's no list for it
   today. The existing Drafts card
   (`TalentAcquisitionDashboardController.php` line 861 /
   `docs`-adjacent HOD dashboard "Drafts" card) is the direct precedent to
   follow: an equivalent Inactive list (or Draft+Inactive combined under
   one "not yet submitted" list) pointing at the same `edit()` route.
4. **User-facing copy explaining what each option means**, shown near the
   Active/Inactive field on the Add Vacancy form — e.g. something like:
   - *Active* — "This request will be sent for approval immediately."
   - *Inactive* — "Your entry will be saved, but nobody will be notified
     until you switch this to Active and submit it."

   Point 4 is a **frontend** change (copy + markup on
   `create.blade.php`/`edit.blade.php`), included here only so it isn't
   lost — it should land together with whichever backend change covers
   points 1–3, since the copy is describing that exact behavior.

## Open question for whoever picks this up

Draft and Inactive currently behave identically once fixed per points 1–3
above (save silently, resumable later, submits into the same pipeline on
Active). Worth deciding whether:
- **(a)** they stay two separate status values with identical skip-logic
  (simplest diff, keeps today's data model), or
- **(b)** Inactive is actually redundant with Draft and the "Status:
  Active/Inactive" radio on the create form should be removed in favor of
  the app's existing Draft mechanism (a "Save as Draft" action instead of a
  status radio).

This doc doesn't pick one — either satisfies the requirement above; (a) is
the smaller change if a fast fix is wanted, (b) is worth considering since
the two concepts read as the same thing today.
