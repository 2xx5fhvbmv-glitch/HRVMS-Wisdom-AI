{{--
    Shared empty state for the Notifications panel — used wherever the bell
    dropdown has nothing to show (Common::ResortNotification() on initial
    load, and the admin-broadcast push partial when it replaces the whole
    panel body with zero notifications). One partial so every role sees the
    identical state; not used by the per-recipient push partial
    (birthday_notification.blade.php), since that one PREPENDS a single new
    row onto an existing list rather than replacing it — its own fallback
    branch is a malformed-payload guard, not a true "list is empty" moment.

    Pure inline SVG + CSS transforms (see default.css for .ntf-empty*/.ntf-scene/
    .ntf-sun/.ntf-wave rules) — no images, no JS, no new libraries.
--}}
<div class="notification-box ntf-empty">
    <div class="ntf-scene">
        <svg width="84" height="84" viewBox="0 0 84 84">
            <defs>
                <clipPath id="ntfEmptyClip"><circle cx="42" cy="42" r="42"/></clipPath>
                <linearGradient id="ntfEmptySky" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0" stop-color="#f2f8f7"/><stop offset="1" stop-color="#e2f0ef"/>
                </linearGradient>
                <radialGradient id="ntfEmptySun" cx="50%" cy="45%" r="55%">
                    <stop offset="0" stop-color="#FFE39A"/><stop offset="60%" stop-color="#FFC24D"/><stop offset="100%" stop-color="#F79A3C"/>
                </radialGradient>
            </defs>
            <g clip-path="url(#ntfEmptyClip)">
                <rect width="84" height="84" fill="url(#ntfEmptySky)"/>
                <circle class="ntf-sun" cx="42" cy="46" r="11" fill="url(#ntfEmptySun)"/>
                <!-- waves: paths 164 wide so the -80 loop is seamless -->
                <g class="ntf-wave ntf-w2" opacity="0.5"><path d="M0 54 q20 -7 40 0 t40 0 t40 0 t40 0 V84 H0 Z" fill="#2EACB3"/></g>
                <g class="ntf-wave ntf-w1" opacity="0.9"><path d="M0 60 q20 -8 40 0 t40 0 t40 0 t40 0 V84 H0 Z" fill="#014653"/></g>
            </g>
            <circle cx="42" cy="42" r="41" fill="none" stroke="rgba(1,70,83,.10)"/>
        </svg>
    </div>
    <div class="ntf-empty-title">Nothing needs you right now</div>
    <div class="ntf-empty-sub">A calm inbox is a good sign. We&rsquo;ll surface anything the moment it matters.</div>
</div>
