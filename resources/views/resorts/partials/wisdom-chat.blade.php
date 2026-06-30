{{--
    Wisdom AI — floating HR chatbot widget.
    Rendered globally from the resort footer, but ONLY for users whose tier is
    not "none" (HR full, GM moderate, or HOD/EXCOM/Manager policy). The backend
    independently enforces the same access model.
--}}
@php
    $wisdomCtx = \App\Services\Wisdom\WisdomAccess::context();
@endphp

@if($wisdomCtx)
@php
    switch ($wisdomCtx['tier']) {
        case \App\Services\Wisdom\WisdomAccess::TIER_FULL:
            $wisdomSuggestions = [
                'How many active employees do we have today?',
                'Who is on approved leave today?',
                'Show me the recruitment pipeline',
                'Summarise the latest payroll',
            ];
            break;
        case \App\Services\Wisdom\WisdomAccess::TIER_GM:
            $wisdomSuggestions = [
                'How many active employees do we have?',
                'Who is on leave today?',
                'Show me the recruitment pipeline',
                'Give me the department headcount breakdown',
            ];
            break;
        default:
            $wisdomSuggestions = [
                'What are the grounds for termination under Maldivian employment law?',
                'What is the company policy on promotions?',
                'How much annual leave are employees entitled to?',
            ];
    }
@endphp

<div id="wai-root"
     data-chat-url="{{ route('resort.wisdom.chat') }}"
     data-history-url="{{ route('resort.wisdom.history') }}"
     data-clear-url="{{ route('resort.wisdom.clear') }}"
     data-tier="{{ $wisdomCtx['tier'] }}"
     data-tier-label="{{ $wisdomCtx['tier_label'] }}"
     data-user-name="{{ $wisdomCtx['user_name'] }}"
     data-suggestions='@json($wisdomSuggestions)'>

    <!-- Launcher -->
    <button type="button" id="wai-launcher" aria-label="Open Wisdom AI assistant">
        <span class="wai-launcher-icon"><img src="{{ URL::asset('resorts_assets/images/wisdom-ai-icon.jpeg') }}" class="wai-bot-img" alt="Wisdom AI"></span>
        <span class="wai-launcher-spark"><i class="fa-solid fa-wand-magic-sparkles"></i></span>
    </button>

    <!-- Chat panel -->
    <div id="wai-panel" role="dialog" aria-label="Wisdom AI chat">
        <div class="wai-header">
            <div class="wai-header-id">
                <div class="wai-avatar"><img src="{{ URL::asset('resorts_assets/images/wisdom-ai-icon.jpeg') }}" class="wai-bot-img" alt="Wisdom AI"></div>
                <div class="wai-titles">
                    <div class="wai-title">Wisdom AI <span class="wai-online"></span></div>
                    <div class="wai-subtitle">{{ $wisdomCtx['tier_label'] }}</div>
                </div>
            </div>
            <div class="wai-header-actions">
                <button type="button" id="wai-clear" title="Clear conversation"><i class="fa-solid fa-trash-can"></i></button>
                <button type="button" id="wai-close" title="Close"><i class="fa-solid fa-xmark"></i></button>
            </div>
        </div>

        <div class="wai-messages" id="wai-messages"></div>

        <div class="wai-suggestions" id="wai-suggestions"></div>

        <form class="wai-input" id="wai-form" autocomplete="off">
            <textarea id="wai-text" rows="1" placeholder="Ask Wisdom AI…" maxlength="2000"></textarea>
            <button type="submit" id="wai-send" aria-label="Send"><i class="fa-solid fa-paper-plane"></i></button>
        </form>
        <div class="wai-foot">Wisdom AI can make mistakes. Verify important HR decisions.</div>
    </div>
</div>

<style>
:root {
    --wai-grad: linear-gradient(135deg, #0b2e37 0%, #11525d 55%, #1c7c81 100%);
    --wai-grad-soft: linear-gradient(135deg, #11525d 0%, #1c7c81 100%);
    --wai-send: linear-gradient(135deg, #c9e814 0%, #aacf00 100%);
    --wai-green: #cfe800;
}
#wai-root * { box-sizing: border-box; }
/* Only force the app font on form controls — NOT on <i> icons, or we'd
   override Font Awesome's icon font and turn every glyph into a tofu box. */
#wai-root button, #wai-root input, #wai-root textarea { font-family: inherit; }

/* Launcher */
#wai-launcher {
    position: fixed; right: 26px; bottom: 26px; z-index: 99990;
    width: 62px; height: 62px; border-radius: 50%; border: none; cursor: pointer;
    background: var(--wai-grad); color: #fff; font-size: 24px;
    box-shadow: 0 10px 28px rgba(11,46,55, .45);
    display: flex; align-items: center; justify-content: center;
    transition: transform .2s ease, box-shadow .2s ease;
    animation: wai-pulse 2.6s infinite;
}
#wai-launcher:hover { transform: translateY(-3px) scale(1.05); box-shadow: 0 14px 34px rgba(11,46,55,.55); }
#wai-launcher.wai-hidden { transform: scale(0); opacity: 0; pointer-events: none; }
.wai-launcher-spark {
    position: absolute; top: -2px; right: -2px; font-size: 13px; color: var(--wai-green);
    background: #0b2e37; width: 22px; height: 22px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 2px 8px rgba(0,0,0,.3);
}
@keyframes wai-pulse {
    0%   { box-shadow: 0 10px 28px rgba(11,46,55,.45), 0 0 0 0 rgba(28,124,129,.45); }
    70%  { box-shadow: 0 10px 28px rgba(11,46,55,.45), 0 0 0 16px rgba(28,124,129,0); }
    100% { box-shadow: 0 10px 28px rgba(11,46,55,.45), 0 0 0 0 rgba(28,124,129,0); }
}

/* Wisdom AI brand mark (replaces the generic robot icon) — fills its round
   container as a clean circle regardless of the container's shape. */
.wai-bot-img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; display: block; }
.wai-launcher-icon { width: 100%; height: 100%; display: flex; }
.wai-avatar, .wai-bot .wai-mini-avatar, .wai-welcome .wai-wel-icon { overflow: hidden; border-radius: 50%; padding: 0; }

/* Panel */
#wai-panel {
    position: fixed; right: 26px; bottom: 26px; z-index: 99991;
    width: 390px; max-width: calc(100vw - 32px);
    height: 600px; max-height: calc(100vh - 80px);
    background: #f7f7fb; border-radius: 18px; overflow: hidden;
    display: flex; flex-direction: column;
    box-shadow: 0 24px 60px rgba(30, 12, 80, .35);
    opacity: 0; transform: translateY(24px) scale(.96); pointer-events: none;
    transition: opacity .22s ease, transform .22s ease;
}
#wai-panel.wai-open { opacity: 1; transform: translateY(0) scale(1); pointer-events: auto; }

/* Header */
.wai-header {
    background: var(--wai-grad); color: #fff; padding: 14px 16px;
    display: flex; align-items: center; justify-content: space-between;
}
.wai-header-id { display: flex; align-items: center; gap: 11px; }
.wai-avatar {
    width: 40px; height: 40px; border-radius: 12px; background: rgba(255,255,255,.18);
    display: flex; align-items: center; justify-content: center; font-size: 19px;
}
.wai-title { font-weight: 700; font-size: 15.5px; display: flex; align-items: center; gap: 7px; }
.wai-online { width: 8px; height: 8px; border-radius: 50%; background: #cfe800; box-shadow: 0 0 0 3px rgba(207,232,0,.35); }
.wai-subtitle { font-size: 11.5px; opacity: .85; margin-top: 1px; }
.wai-header-actions { display: flex; gap: 4px; }
.wai-header-actions button {
    background: rgba(255,255,255,.12); border: none; color: #fff; cursor: pointer;
    width: 32px; height: 32px; border-radius: 9px; font-size: 13px; transition: background .15s;
}
.wai-header-actions button:hover { background: rgba(255,255,255,.28); }

/* Messages */
.wai-messages { flex: 1; overflow-y: auto; padding: 16px 14px 8px; display: flex; flex-direction: column; gap: 12px; }
.wai-messages::-webkit-scrollbar { width: 7px; }
.wai-messages::-webkit-scrollbar-thumb { background: #cdd9d5; border-radius: 8px; }
.wai-row { display: flex; gap: 9px; align-items: flex-end; max-width: 100%; }
.wai-row.wai-user { flex-direction: row-reverse; }
.wai-bubble {
    padding: 10px 13px; border-radius: 15px; font-size: 14px; line-height: 1.5;
    max-width: 80%; word-wrap: break-word; overflow-wrap: anywhere;
}
.wai-bot .wai-bubble { background: #fff; color: #25243a; border-bottom-left-radius: 5px; box-shadow: 0 2px 10px rgba(40,20,90,.06); }
.wai-user .wai-bubble { background: var(--wai-grad-soft); color: #fff; border-bottom-right-radius: 5px; }
.wai-mini-avatar {
    width: 28px; height: 28px; border-radius: 9px; flex: 0 0 28px; font-size: 13px;
    display: flex; align-items: center; justify-content: center; color: #fff;
}
.wai-bot .wai-mini-avatar { background: var(--wai-grad); }
.wai-user .wai-mini-avatar { background: #c9c4dd; color: #4a456b; }
.wai-bubble p { margin: 0 0 8px; } .wai-bubble p:last-child { margin-bottom: 0; }
.wai-bubble ul, .wai-bubble ol { margin: 6px 0; padding-left: 20px; }
.wai-bubble li { margin: 2px 0; }
.wai-bubble code { background: rgba(28,124,129,.1); padding: 1px 5px; border-radius: 5px; font-size: 12.5px; }
.wai-bubble pre { background: #2a2540; color: #f3f0ff; padding: 10px; border-radius: 10px; overflow-x: auto; font-size: 12.5px; }
.wai-bubble pre code { background: none; padding: 0; }
.wai-bubble table { border-collapse: collapse; width: 100%; font-size: 12.5px; margin: 6px 0; }
.wai-bubble th, .wai-bubble td { border: 1px solid #dce6e2; padding: 4px 7px; text-align: left; }
.wai-bubble a { color: #13706f; }

/* Welcome */
.wai-welcome { text-align: center; padding: 18px 12px 6px; color: #5a5570; }
.wai-welcome .wai-wel-icon {
    width: 56px; height: 56px; border-radius: 16px; background: var(--wai-grad); color: #fff;
    display: inline-flex; align-items: center; justify-content: center; font-size: 26px; margin-bottom: 10px;
    box-shadow: 0 8px 22px rgba(11,46,55,.35);
}
.wai-welcome h4 { margin: 0 0 4px; font-size: 17px; color: #2a2444; font-weight: 700; }
.wai-welcome p { margin: 0; font-size: 13px; }

/* Typing */
.wai-typing { display: flex; gap: 4px; padding: 4px 2px; }
.wai-typing span { width: 7px; height: 7px; border-radius: 50%; background: #9fb3ae; animation: wai-bounce 1.2s infinite; }
.wai-typing span:nth-child(2) { animation-delay: .15s; } .wai-typing span:nth-child(3) { animation-delay: .3s; }
@keyframes wai-bounce { 0%,60%,100% { transform: translateY(0); opacity: .5; } 30% { transform: translateY(-5px); opacity: 1; } }

/* Suggestions */
.wai-suggestions { padding: 0 14px 6px; display: flex; flex-wrap: wrap; gap: 7px; }
.wai-chip {
    background: #fff; border: 1px solid #d8e2de; color: #11525d; font-size: 12px;
    padding: 6px 11px; border-radius: 20px; cursor: pointer; transition: all .15s; line-height: 1.3;
}
.wai-chip:hover { background: var(--wai-grad-soft); color: #fff; border-color: transparent; }

/* Input */
.wai-input { display: flex; align-items: flex-end; gap: 8px; padding: 10px 12px 6px; background: #f7f7fb; }
#wai-text {
    flex: 1; resize: none; border: 1px solid #d8e2de; border-radius: 14px; padding: 10px 13px;
    font-size: 14px; max-height: 110px; outline: none; background: #fff; color: #25243a; line-height: 1.4;
}
#wai-text:focus { border-color: #1c7c81; box-shadow: 0 0 0 3px rgba(28,124,129,.18); }
#wai-send {
    width: 42px; height: 42px; flex: 0 0 42px; border: none; border-radius: 13px; cursor: pointer;
    background: var(--wai-send); color: #0b2e37; font-size: 16px; transition: transform .15s, opacity .15s;
}
#wai-send:hover { transform: scale(1.06); }
#wai-send:disabled { opacity: .5; cursor: not-allowed; transform: none; }
.wai-foot { text-align: center; font-size: 10.5px; color: #a09ab5; padding: 0 12px 10px; }

@media (max-width: 480px) {
    #wai-panel { right: 8px; bottom: 8px; width: calc(100vw - 16px); height: calc(100vh - 90px); }
    #wai-launcher { right: 16px; bottom: 16px; }
}
</style>

<script>
(function () {
    var root = document.getElementById('wai-root');
    if (!root || root.dataset.waiInit) return;
    root.dataset.waiInit = '1';

    var CHAT_URL    = root.dataset.chatUrl;
    var HISTORY_URL = root.dataset.historyUrl;
    var CLEAR_URL   = root.dataset.clearUrl;
    var USER_NAME   = root.dataset.userName || 'there';
    var SUGGESTIONS = [];
    try { SUGGESTIONS = JSON.parse(root.dataset.suggestions || '[]'); } catch (e) {}
    var CSRF = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    var launcher = document.getElementById('wai-launcher');
    var panel    = document.getElementById('wai-panel');
    var closeBtn = document.getElementById('wai-close');
    var clearBtn = document.getElementById('wai-clear');
    var msgs     = document.getElementById('wai-messages');
    var sugWrap  = document.getElementById('wai-suggestions');
    var form     = document.getElementById('wai-form');
    var input    = document.getElementById('wai-text');
    var sendBtn  = document.getElementById('wai-send');

    var loaded = false, busy = false;

    // ---- helpers -------------------------------------------------------
    function escapeHtml(s) {
        return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    // Minimal, safe Markdown → HTML (escape first, then format).
    function renderMarkdown(text) {
        var src = escapeHtml(text);
        // fenced code blocks
        src = src.replace(/```([\s\S]*?)```/g, function (m, c) { return '<pre><code>' + c.replace(/^\n/, '') + '</code></pre>'; });
        // inline code
        src = src.replace(/`([^`]+)`/g, '<code>$1</code>');
        // bold / italic
        src = src.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        src = src.replace(/(^|[^*])\*([^*]+)\*/g, '$1<em>$2</em>');
        // links [text](url)
        src = src.replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>');

        var lines = src.split('\n'), html = '', listType = null;
        function closeList() { if (listType) { html += '</' + listType + '>'; listType = null; } }
        for (var i = 0; i < lines.length; i++) {
            var ln = lines[i];
            if (/^\s*[-*]\s+/.test(ln)) {
                if (listType !== 'ul') { closeList(); html += '<ul>'; listType = 'ul'; }
                html += '<li>' + ln.replace(/^\s*[-*]\s+/, '') + '</li>';
            } else if (/^\s*\d+\.\s+/.test(ln)) {
                if (listType !== 'ol') { closeList(); html += '<ol>'; listType = 'ol'; }
                html += '<li>' + ln.replace(/^\s*\d+\.\s+/, '') + '</li>';
            } else if (ln.trim() === '') {
                closeList();
            } else {
                closeList(); html += '<p>' + ln + '</p>';
            }
        }
        closeList();
        return html;
    }

    var WAI_BOT_ICON = "{{ URL::asset('resorts_assets/images/wisdom-ai-icon.jpeg') }}";
    function botAvatar() { return '<div class="wai-mini-avatar"><img src="' + WAI_BOT_ICON + '" class="wai-bot-img" alt="Wisdom AI"></div>'; }
    function userAvatar() { return '<div class="wai-mini-avatar"><i class="fa-solid fa-user"></i></div>'; }

    function addMessage(role, text) {
        clearWelcome();
        var row = document.createElement('div');
        row.className = 'wai-row ' + (role === 'user' ? 'wai-user' : 'wai-bot');
        var bubble = '<div class="wai-bubble">' + (role === 'user' ? escapeHtml(text).replace(/\n/g, '<br>') : renderMarkdown(text)) + '</div>';
        row.innerHTML = (role === 'user' ? userAvatar() : botAvatar()) + bubble;
        msgs.appendChild(row);
        scrollDown();
    }

    function showTyping() {
        var row = document.createElement('div');
        row.className = 'wai-row wai-bot'; row.id = 'wai-typing-row';
        row.innerHTML = botAvatar() + '<div class="wai-bubble"><div class="wai-typing"><span></span><span></span><span></span></div></div>';
        msgs.appendChild(row); scrollDown();
    }
    function hideTyping() { var t = document.getElementById('wai-typing-row'); if (t) t.remove(); }

    function scrollDown() { msgs.scrollTop = msgs.scrollHeight; }

    function clearWelcome() { var w = msgs.querySelector('.wai-welcome'); if (w) w.remove(); }

    function showWelcome() {
        msgs.innerHTML =
            '<div class="wai-welcome">' +
                '<div class="wai-wel-icon"><img src="' + WAI_BOT_ICON + '" class="wai-bot-img" alt="Wisdom AI"></div>' +
                '<h4>Hi ' + escapeHtml(USER_NAME.split(' ')[0]) + ' 👋</h4>' +
                '<p>I\'m Wisdom AI, your HR assistant. Ask me anything I\'m allowed to help with.</p>' +
            '</div>';
    }

    function renderSuggestions() {
        sugWrap.innerHTML = '';
        SUGGESTIONS.forEach(function (s) {
            var chip = document.createElement('div');
            chip.className = 'wai-chip'; chip.textContent = s;
            chip.addEventListener('click', function () { if (!busy) { input.value = s; sendMessage(); } });
            sugWrap.appendChild(chip);
        });
    }
    function toggleSuggestions(show) { sugWrap.style.display = show ? 'flex' : 'none'; }

    // ---- networking ----------------------------------------------------
    function loadHistory() {
        if (loaded) return; loaded = true;
        fetch(HISTORY_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data && data.success && data.messages && data.messages.length) {
                    msgs.innerHTML = '';
                    data.messages.forEach(function (m) { addMessage(m.role, m.content); });
                    toggleSuggestions(false);
                } else {
                    showWelcome(); toggleSuggestions(true);
                }
            })
            .catch(function () { showWelcome(); toggleSuggestions(true); });
    }

    function sendMessage() {
        var text = input.value.trim();
        if (!text || busy) return;
        busy = true; sendBtn.disabled = true;
        addMessage('user', text);
        input.value = ''; autoGrow(); toggleSuggestions(false); showTyping();

        fetch(CHAT_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ message: text })
        })
        .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
        .then(function (res) {
            hideTyping();
            if (res.ok && res.body.success) {
                addMessage('assistant', res.body.reply);
            } else {
                addMessage('assistant', '⚠️ ' + (res.body.message || 'Something went wrong. Please try again.'));
            }
        })
        .catch(function () { hideTyping(); addMessage('assistant', '⚠️ Network error. Please try again.'); })
        .finally(function () { busy = false; sendBtn.disabled = false; input.focus(); });
    }

    function clearChat() {
        if (busy) return;
        fetch(CLEAR_URL, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function () {
                msgs.innerHTML = ''; showWelcome(); toggleSuggestions(true);
            });
    }

    // ---- UI wiring -----------------------------------------------------
    function openPanel() {
        panel.classList.add('wai-open'); launcher.classList.add('wai-hidden');
        loadHistory(); setTimeout(function () { input.focus(); }, 250);
    }
    function closePanel() { panel.classList.remove('wai-open'); launcher.classList.remove('wai-hidden'); }

    function autoGrow() { input.style.height = 'auto'; input.style.height = Math.min(input.scrollHeight, 110) + 'px'; }

    launcher.addEventListener('click', openPanel);
    closeBtn.addEventListener('click', closePanel);
    clearBtn.addEventListener('click', clearChat);
    form.addEventListener('submit', function (e) { e.preventDefault(); sendMessage(); });
    input.addEventListener('input', autoGrow);
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && panel.classList.contains('wai-open')) closePanel(); });

    renderSuggestions();
})();
</script>
@endif
