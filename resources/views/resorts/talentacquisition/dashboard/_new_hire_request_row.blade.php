{{--
    One "New Hire Requests" tile — shared by _new_hire_requests_card.blade.php
    (initial page render) and renderfiles/FreshVacancies.blade.php (the
    #FreshHiringRequest AJAX refresh after Respond/Approve/Reject/Hold).
    Both render the SAME Common::GetTheFreshVacancies() rows; before this,
    they duplicated this markup independently and had drifted — the AJAX
    copy was missing data-NoOfVacnacy/data-createdby/data-creatorrank
    entirely (leaving the Respond modal showing "undefined" after any
    refresh) and resolved the requester photo off vacancy->resort_id
    instead of the requester's own admin id. One shared partial means
    that can't happen again.

    Expects $vacancy (a single row from GetTheFreshVacancies()) and,
    optionally, $nhrPhoto pre-resolved by the caller via
    Common::getResortUserPicturesBatch() (one query for the whole list
    instead of one per row — both callers of this partial pass it). Falls
    back to resolving it here per-row if a caller ever doesn't, so this
    partial still works correctly on its own.
--}}
@php
    $nhrDefaultPhoto = url(config('settings.default_picture'));
    $nhrPhoto = $nhrPhoto ?? Common::getResortUserPicture($vacancy->creator_admin_id ?? null);
@endphp
<div class="nhr-req">
    <div class="nhr-qbig"><span class="n">{{ $vacancy->NoOfVacnacy }}</span><span class="x">need</span></div>
    <div class="nhr-body">
        <div class="nhr-role">{{ $vacancy->Position ?? 'Position' }}</div>
        <div class="nhr-who">
            {{-- Portrait avatar; the requester's gender isn't in the row, so no photo shows the neutral figure. --}}
            <span class="nhr-mini"><x-wai-avatar :photo="$nhrPhoto !== $nhrDefaultPhoto ? $nhrPhoto : null" :name="$vacancy->created_by_name" :id="$vacancy->creator_admin_id ?? $vacancy->created_by_name" :size="20" /></span>
            <span class="nhr-wn"><b>{{ $vacancy->created_by_name }}</b> &middot; {{ $vacancy->Department }} &middot; {{ $vacancy->creator_rank_name }}</span>
        </div>
    </div>
    <a href="javascript:void(0)" class="nhr-respond respondOfFreshmodal" title="Respond" aria-label="Respond"
            data-images="{{ $nhrPhoto }}"
            data-ta_id="{{ $vacancy->ta_id }}"
            data-departmentName="{{ $vacancy->Department }}"
            data-rank="{{ $vacancy->rank_name }}"
            data-position="{{ $vacancy->Position }}"
            data-NoOfVacnacy="{{ $vacancy->NoOfVacnacy }}"
            data-Child_ta_id="{{ $vacancy->Child_ta_id }}"
            data-createdby="{{ $vacancy->created_by_name }}"
            data-creatorrank="{{ $vacancy->creator_rank_name }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
    </a>
</div>
