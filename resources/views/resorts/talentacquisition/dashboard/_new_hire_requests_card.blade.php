{{--
    Shared "New Hire Requests" card — admin/hr/hod dashboards all @include
    this with the SAME $Vacancies collection (Common::GetTheFreshVacancies())
    they already compute; no new queries. The Respond flow is untouched:
    .respondOfFreshmodal and every data-* attribute stay exactly as before
    (now defined once in _new_hire_request_row.blade.php, shared with the
    AJAX-refresh partial below) so the existing modal-population JS
    (hrdashboard/admindashboard/hoddashboard all bind
    $(document).on("click", ".respondOfFreshmodal", ...) themselves) keeps
    working unmodified.

    #FreshHiringRequest's innerHTML also gets replaced after Respond/Approve/
    Reject/Hold via AJAX with resources/views/resorts/renderfiles/
    FreshVacancies.blade.php (shared with the notification bell/broadcast
    system elsewhere — out of scope to touch beyond keeping it visually in
    sync). That partial now @includes the same _new_hire_request_row row
    partial this one does, so a refreshed list renders identically instead
    of reverting to the old design.

    Pass $newHireRequestsTitle to override the header text (HOD dashboard
    uses "Hire requests pending your approval" instead of "New hire requests").
--}}
@php
    $nhrTitle = $newHireRequestsTitle ?? 'New hire requests';
    $nhrRows = isset($Vacancies) ? $Vacancies->take(5) : collect();
    // One query for every requester photo on this card instead of one per
    // row (Common::getResortUserPicture() inside the row partial would
    // otherwise run up to 5 times here).
    $nhrPhotoMap = Common::getResortUserPicturesBatch($nhrRows->pluck('creator_admin_id')->filter()->all());
    $nhrDefaultPhoto = url(config('settings.default_picture'));
@endphp
<div class="card nhr-card ta-toprow-card">
    <div class="nhr-ct">
        <h2>{{ $nhrTitle }}</h2>
        <span class="nhr-count">{{ isset($Vacancies) ? $Vacancies->count() : 0 }}</span>
        <span class="nhr-spacer"></span>
        <a href="{{ route('resort.ta.ViewVacancies') }}" class="nhr-link">View all <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
    </div>
    <div class="nhr-list" id="FreshHiringRequest">
        @if($nhrRows->isNotEmpty())
            @foreach ($nhrRows as $vacancy)
                @include('resorts.talentacquisition.dashboard._new_hire_request_row', [
                    'vacancy' => $vacancy,
                    'nhrPhoto' => $nhrPhotoMap[$vacancy->creator_admin_id ?? 0] ?? $nhrDefaultPhoto,
                ])
            @endforeach
        @else
            <p class="nhr-empty">No new hire requests available.</p>
        @endif
    </div>
</div>
