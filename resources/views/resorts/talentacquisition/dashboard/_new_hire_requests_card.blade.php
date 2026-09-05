{{--
    Shared "New Hire Requests" card — admin/hr/hod dashboards all @include
    this with the SAME $Vacancies collection (Common::GetTheFreshVacancies())
    they already compute; no new queries. The Respond flow is untouched:
    .respondOfFreshmodal and every data-* attribute stay exactly as before so
    the existing modal-population JS (hrdashboard/admindashboard/hoddashboard
    all bind $(document).on("click", ".respondOfFreshmodal", ...) themselves)
    keeps working unmodified.

    #FreshHiringRequest's innerHTML also gets replaced after Respond/Approve/
    Reject/Hold via AJAX with resources/views/resorts/renderfiles/FreshVacancies.blade.php
    (a partial shared with the notification bell/broadcast system elsewhere —
    out of scope to touch). That partial reuses the identical class names
    (hireReq-block, img-circle, respondOfFreshmodal), so the CSS in
    _ta_widgets_v2_styles.blade.php — scoped under the ancestor
    .hireReq-card-v2 wrapper below, which never gets replaced by the AJAX
    call — keeps applying automatically after a refresh too.

    Pass $newHireRequestsTitle to override the header text (HOD dashboard
    uses "Hire Requests Pending Your Approval" instead of "New Hire Requests").
--}}
@php
    $nhrTitle = $newHireRequestsTitle ?? 'New Hire Requests';
    $nhrDefaultPhoto = url(config('settings.default_picture'));
    $nhrPalette = ['#6B4FA0', '#0E8A9E', 'var(--positive)', 'var(--warning)', '#4A5F8A', '#A0527A'];
    $nhrInitials = function ($name) {
        $parts = preg_split('/\s+/', trim((string) $name));
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        }
        return $initials !== '' ? $initials : '?';
    };
    $nhrAvatarColor = function ($name) use ($nhrPalette) {
        $hash = 0;
        foreach (str_split((string) $name) as $ch) {
            // Bounded modulo every step — without this, a long enough name
            // overflows PHP's int range into a float, and abs()/% on that
            // float can produce a negative or out-of-range array index.
            $hash = (ord($ch) + (($hash << 5) - $hash)) % 1000000007;
        }
        return $nhrPalette[abs($hash) % count($nhrPalette)];
    };
@endphp
<div class="card hireReq-card-v2 ta-toprow-card">
    <div class="card-title">
        <div class="row justify-content-between align-items-center g-3">
            <div class="col">
                <h3>{{ $nhrTitle }}</h3>
            </div>
            <div class="col-auto">
                <a href="{{ route('resort.ta.ViewVacancies') }}" class="a-link">View all</a>
            </div>
        </div>
    </div>
    <div class="hireReq-main" id="FreshHiringRequest">
        @if(isset($Vacancies) && $Vacancies->count() > 0)
            @foreach ($Vacancies->take(5) as $vacancy)
                @php
                    // The requester's own avatar — creator_admin_id (creator.id
                    // from the vacancies.created_by join) was added to
                    // Common::GetTheFreshVacancies()'s SELECT specifically for
                    // this; the card previously (incorrectly) passed
                    // $vacancy->resort_id here, which isn't a ResortAdmin id.
                    $nhrPhoto = Common::getResortUserPicture($vacancy->creator_admin_id ?? null);
                    $nhrHasPhoto = $nhrPhoto !== $nhrDefaultPhoto;
                @endphp
                <div class="hireReq-block">
                    <div class="img-circle">
                        @if($nhrHasPhoto)
                            <img src="{{ $nhrPhoto }}" alt="image">
                        @else
                            <span class="hireReq-initials" style="background:{{ $nhrAvatarColor($vacancy->created_by_name) }};">{{ $nhrInitials($vacancy->created_by_name) }}</span>
                        @endif
                    </div>
                    <div>
                        <h6>{{ $vacancy->Department }} <span class="badge badge-themeBlue hireReq-level-badge">{{ $vacancy->rank_name }}</span></h6>
                        <p><strong>{{ $vacancy->created_by_name }}</strong> requested hire &mdash; {{ $vacancy->NoOfVacnacy }} {{ $vacancy->Position ?? 'Position' }}</p>
                    </div>
                    <div class="icon">
                        <a href="javascript:void(0)" class="respondOfFreshmodal a-link"
                                data-images="{{ $nhrPhoto }}"
                                data-ta_id="{{ $vacancy->ta_id }}"
                                data-departmentName="{{ $vacancy->Department }}"
                                data-rank="{{ $vacancy->rank_name }}"
                                data-position="{{ $vacancy->Position }}"
                                data-NoOfVacnacy="{{ $vacancy->NoOfVacnacy }}"
                                data-Child_ta_id="{{ $vacancy->Child_ta_id }}"
                                data-createdby="{{ $vacancy->created_by_name }}"
                                data-creatorrank="{{ $vacancy->creator_rank_name }}">
                            Respond
                        </a>
                    </div>
                </div>
            @endforeach
        @else
            <p class="hireReq-empty">No new hire requests available.</p>
        @endif
    </div>
</div>
