{{--
    Replaces #FreshHiringRequest's innerHTML (the .nhr-list inside the New
    Hire Requests card — see _new_hire_requests_card.blade.php) after a
    Respond/Approve/Reject/Hold action. Renders the same
    _new_hire_request_row partial that card uses on initial page load, so
    the list stays visually and functionally identical after a refresh
    instead of reverting to old markup.
--}}
@php
    $nhrRows = isset($getNotifications['FreshVacancies']) ? $getNotifications['FreshVacancies'] : collect();
    // Same one-query batch as the initial card render (see
    // _new_hire_requests_card.blade.php) instead of resolving each row's
    // photo individually.
    $nhrPhotoMap = Common::getResortUserPicturesBatch($nhrRows->pluck('creator_admin_id')->filter()->all());
    $nhrDefaultPhoto = url(config('settings.default_picture'));
@endphp
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
