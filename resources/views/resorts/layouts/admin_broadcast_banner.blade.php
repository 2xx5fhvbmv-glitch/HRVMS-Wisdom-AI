{{-- Admin broadcast banner.
     Renders any active admin notification (notifications table) targeted at
     the current user's resort that they haven't already dismissed. Each
     banner has a × that POSTs to /resort/admin-notifications/{id}/dismiss
     and writes a per-user row to admin_notification_dismissals. --}}
@php
    $__adminBroadcasts = \App\Helpers\Common::getActiveAdminNotifications();
@endphp

@if($__adminBroadcasts->isNotEmpty())
<div id="admin-broadcast-wrap" class="admin-broadcast-wrap">
    @foreach($__adminBroadcasts as $b)
        <div class="admin-broadcast-bar"
             data-id="{{ $b->id }}"
             style="background:{{ $b->notice_color ?: '#fff3cd' }};
                    color:{{ $b->font_color ?: '#664d03' }};">
            <div class="admin-broadcast-content">
                <strong>{{ $b->name }}</strong>
                <span class="ms-2">{!! $b->content !!}</span>
            </div>
            <button type="button"
                    class="admin-broadcast-close"
                    data-id="{{ $b->id }}"
                    aria-label="Dismiss"
                    style="color:{{ $b->font_color ?: '#664d03' }};">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endforeach
</div>

<style>
    .admin-broadcast-wrap { position: relative; z-index: 1030; }
    .admin-broadcast-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 8px 16px;
        font-size: 13px;
        line-height: 1.4;
        border-bottom: 1px solid rgba(0,0,0,0.06);
    }
    .admin-broadcast-content { flex: 1 1 auto; padding-right: 12px; }
    .admin-broadcast-content strong { font-size: 13px; }
    .admin-broadcast-close {
        background: transparent;
        border: 0;
        font-size: 16px;
        line-height: 1;
        cursor: pointer;
        padding: 4px 8px;
        opacity: 0.7;
    }
    .admin-broadcast-close:hover { opacity: 1; }
</style>

<script>
(function () {
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.admin-broadcast-close');
        if (!btn) return;
        var id  = btn.getAttribute('data-id');
        var bar = btn.closest('.admin-broadcast-bar');
        if (!id || !bar) return;
        bar.style.display = 'none'; // optimistic hide
        var token = document.querySelector('meta[name="csrf-token"]');
        fetch('/resort/admin-notifications/' + encodeURIComponent(id) + '/dismiss', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token ? token.getAttribute('content') : ''
            }
        }).then(function (r) {
            if (!r.ok) bar.style.display = ''; // restore on failure
        }).catch(function () {
            bar.style.display = '';
        });
    });
})();
</script>
@endif
