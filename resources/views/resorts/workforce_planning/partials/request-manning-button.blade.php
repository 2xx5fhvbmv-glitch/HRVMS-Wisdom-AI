{{-- Request Manning: asks for next year's manning; greyed out once the GM has approved next year's budget (Common::manningRequestLock). --}}
@php($manningLock = \App\Helpers\Common::manningRequestLock(Auth::guard('resort-admin')->user()->resort_id))
@if($manningLock['locked'])
    <span class="d-inline-block" tabindex="0" title="{{ $manningLock['message'] }}">
        <button type="button" class="wai-btn wai-btn-primary" disabled aria-disabled="true" style="pointer-events:none;opacity:.55"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg>Request Manning</button>
    </span>
@else
    <a href="#sendRequest-modal" data-bs-toggle="modal" class="wai-btn wai-btn-primary" title="Request the {{ $manningLock['year'] }} manning from every department"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg>Request Manning</a>
@endif
