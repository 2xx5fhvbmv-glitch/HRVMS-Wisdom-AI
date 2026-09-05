{{-- Shared "WAI Suggestions" popup — a small modal that shows one AI
     suggestion (a rule/finding as the title, the suggested fix as the
     body). Any page can open it with:
         waiSuggestOpen('Rule or finding title', 'The suggested fix text');
     Include this partial once per page and call waiSuggestOpen() from an
     "AI suggested fix" trigger. Closes on the × button, Escape, or a
     backdrop click. Pure vanilla JS, no new libraries. --}}
<div class="wai-sg-overlay" id="waiSgOverlay" onclick="waiSuggestClose()">
    <div class="wai-sg-modal" role="dialog" aria-modal="true" aria-labelledby="waiSgTitle" onclick="event.stopPropagation()">
        <button type="button" class="wai-sg-close" onclick="waiSuggestClose()" aria-label="Close">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
        <div class="wai-sg-kicker"><span class="wai-sg-dot"></span>WAI Suggestions</div>
        <div class="wai-sg-title" id="waiSgTitle"></div>
        <div class="wai-sg-text" id="waiSgText"></div>
    </div>
</div>

<style>
    .wai-sg-overlay {
        position: fixed; inset: 0;
        background: rgba(14,26,32,.4);
        backdrop-filter: blur(3px); -webkit-backdrop-filter: blur(3px);
        display: flex; align-items: center; justify-content: center;
        padding: 24px;
        opacity: 0; visibility: hidden;
        transition: opacity .18s;
        z-index: 1060; /* above Bootstrap's .modal (1055) */
    }
    .wai-sg-overlay.show { opacity: 1; visibility: visible; }
    .wai-sg-modal {
        position: relative;
        width: 410px; max-width: 92vw;
        background: var(--card);
        border-radius: 22px;
        padding: 30px 30px 28px;
        box-shadow: 0 24px 60px rgba(1,70,83,.22);
        border: 1px solid rgba(255,255,255,.6);
        transform: translateY(6px) scale(.985);
        transition: transform .18s;
    }
    .wai-sg-overlay.show .wai-sg-modal { transform: none; }
    .wai-sg-close {
        position: absolute; top: 20px; right: 20px;
        width: 30px; height: 30px; border-radius: 50%;
        background: transparent; border: none; color: var(--faint);
        cursor: pointer; display: grid; place-items: center;
        transition: background .15s, color .15s;
    }
    .wai-sg-close:hover { background: var(--line-2); color: var(--ink); }
    .wai-sg-kicker {
        display: flex; align-items: center; gap: 7px;
        font-size: 10.5px; font-weight: 600; text-transform: uppercase;
        letter-spacing: .9px; color: var(--muted);
    }
    .wai-sg-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--teal); }
    .wai-sg-title {
        font-size: 20px; font-weight: 600; letter-spacing: -.3px;
        color: var(--ink); margin-top: 12px; line-height: 1.25;
    }
    .wai-sg-text {
        font-size: 15px; color: var(--darkblack, #222); line-height: 1.6; margin-top: 12px;
    }
    @media (prefers-reduced-motion: reduce) {
        .wai-sg-overlay, .wai-sg-modal { transition: none !important; }
    }
</style>

<script>
    function waiSuggestOpen(title, text) {
        document.getElementById('waiSgTitle').textContent = title || '';
        document.getElementById('waiSgText').textContent = text || '';
        document.getElementById('waiSgOverlay').classList.add('show');
    }
    function waiSuggestClose() {
        document.getElementById('waiSgOverlay').classList.remove('show');
    }
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') waiSuggestClose();
    });
</script>
