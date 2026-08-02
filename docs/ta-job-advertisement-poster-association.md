# Talent Acquisition — job advertisement poster not associated with a vacancy

For the backend dev. Found while doing a design/copy pass on the Vacancies
grid view (`/resort/talent-acquisition/fresh-applicant`, grid layout).
Reported by the user as "the wrong poster shows on the vacancy card" — this
is a real data-model gap, not a display bug, so it wasn't touched as part of
that design pass.

## What's visibly wrong

Every vacancy card in the grid shows the same job advertisement poster
image, regardless of which vacancy it is. The "View Job Advertisement"
modal on the same card likely has the same problem, since it's fed by the
same data.

## Root cause

`job_advertisements` has no relationship to `vacancies` at all:

```php
// database/migrations/2024_11_14_100848_create_job_advertisements_table.php
Schema::create('job_advertisements', function (Blueprint $table) {
    $table->id();
    $table->unsignedInteger('Resort_id');
    $table->string('Jobadvimg');
    $table->integer('created_by')->nullable();
    $table->integer('modified_by')->nullable();
    $table->foreign('Resort_id')->references('id')->on('resorts');
    $table->timestamps();
});
```

It's a flat, resort-wide pool of uploaded poster images — no `vacancy_id`,
no `position_id`, nothing that says "this poster belongs to that vacancy."

`VacancyController::GridViewData()` (~line 1555) reflects that gap exactly:

```php
$gridAllJobAdImages = \App\Models\JobAdvertisement::where('Resort_id', $resort_id)->get()->map(function($ad) use ($resort_id) {
    return Common::GetJobAdvertisementImage($resort_id, $ad->Jobadvimg);
})->values()->toArray();

$NewVacancies->getCollection()->transform(function ($vacancy) use ($gridAllJobAdImages, $resort_id) {
    $vacancy->image = !empty($gridAllJobAdImages) ? $gridAllJobAdImages[0] : null;
    $vacancy->allJobAdImages = $gridAllJobAdImages;
    return $vacancy;
});
```

It fetches **every** poster ever uploaded for the resort, then hands the
exact same array — and specifically `[0]`, whichever poster happens to sort
first — to **every vacancy row** in the transform. There's no per-vacancy
filter because there's no column to filter on.

## What a real fix needs

1. A way to know which poster belongs to which vacancy. Check first whether
   this association already exists somewhere else in the job-advertisement
   creation flow (e.g. does the vacancy/job-ad creation UI let a user pick
   or upload a poster *for that specific vacancy*, and if so, where does
   that choice currently get stored — if anywhere?). If nothing stores it
   today, this needs a new column (e.g. `job_advertisements.vacancy_id` or
   `.position_id`) plus a migration to backfill or accept that historical
   rows can't be reliably re-associated.
2. Once that link exists, `GridViewData()`'s image lookup needs to filter
   per vacancy instead of pulling the whole resort's pool and slicing
   `[0]`. Also check `VacancyController.php` for other places that consume
   `allJobAdImages`/`data-alljobimages` the same way (the "View Job
   Advertisement" button on this same card, at minimum) — same root cause,
   same fix needed there.

## Not part of this

The upload validation for `Jobadvimg` (`JobAdvertisementController.php:114`)
only allows image mime types (jpg, jpeg, png, gif, svg, webp, heic, heif) —
no PDF path exists for this field, so no PDF-vs-image handling is needed
anywhere in this fix.
